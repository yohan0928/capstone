<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Branch;
use App\Models\CustomerAccount;
use App\Models\CustomerCheckin;
use App\Models\CustomerReward;
use App\Models\LoyaltyTier;
use App\Models\OwnerAccount;
use App\Models\RewardRedemption;
use App\Models\Seat;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use App\Models\StaffAccount;
use App\Notifications\Owner\BookNowNotification;
use App\Notifications\Staff\BookNowStaffNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class BookNowController extends Controller
{
    public function create()
    {
        // Get the currently authenticated owner
        $owner = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        // Retrieve only the active records owned by this owner
        $branches = Branch::where('active', 1)
            ->where('owner_account_id', $ownerId)
            ->get();

        $serviceCategories = ServiceCategory::where('active', 1)
            ->where('owner_account_id', $ownerId)
            ->get();

        // Filter services where time_duration contains "hour"
        $serviceNames = ServiceName::where('active', 1)
            ->where('time_duration', 'like', '%hour%')
            ->where('owner_account_id', $ownerId)
            ->get();

        $seats = Seat::where('active', 1)
            ->where('owner_account_id', $ownerId)
            ->get();

        return view('owner.booking.book_now', compact(
            'branches',
            'serviceCategories',
            'serviceNames',
            'seats'
        ));
    }

    public function getReturningCustomers()
    {
        try {
            // Get customers who have existing bookings using customer_account_id
            $returningCustomers = CustomerAccount::whereIn('id', function ($query) {
                $query
                    ->select('customer_account_id')
                    ->from('bookings')
                    ->where('active', 1)
                    ->groupBy('customer_account_id');
            })
                ->where('active', 1)
                ->select('id', 'first_name', 'last_name', 'email', 'contact_no')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get();

            return response()->json([
                'success' => true,
                'customers' => $returningCustomers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching returning customers',
                'customers' => []
            ], 500);
        }
    }

    public function getAvailableSeats(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'service_category_id' => 'required|exists:service_categories,id',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
        ]);

        // Get all seats that match the branch and category
        $allSeatIdsForCategory = Seat::where('active', 1)
            ->where('branch_id', $validated['branch_id'])
            ->where('service_category_id', $validated['service_category_id'])
            ->pluck('id');

        // Find seat IDs that are currently occupied
        $unavailableSeatIds = Booking::whereIn('seat_id', $allSeatIdsForCategory)
            ->where('active', 1)
            ->whereDate('date_start', $validated['date_start'])
            ->where(function ($query) {
                $query
                    ->where('booking_status', '!=', 4)  // Booking not completed
                    ->orWhere(function ($q) {
                        // Or booking is completed but check-in is still active
                        $q
                            ->where('booking_status', 4)
                            ->whereHas('customerCheckin', function ($checkinQuery) {
                                $checkinQuery
                                    ->where('active', 1)
                                    ->where('checkin_status', 1);  // Still checked-in
                            });
                    });
            })
            ->pluck('seat_id')
            ->unique();

        // Available seats are those NOT in the unavailable list
        $availableSeats = Seat::whereIn('id', $allSeatIdsForCategory)
            ->whereNotIn('id', $unavailableSeatIds)
            ->get(['id', 'seat_no', 'room_no']);

        return response()->json($availableSeats);
    }

    public function getServicePrice(Request $request)
    {
        $request->validate([
            'service_name_id' => 'required|exists:service_names,id'
        ]);

        $service = ServiceName::find($request->service_name_id);

        if (!$service) {
            return response()->json(['success' => false, 'message' => 'Service not found'], 404);
        }

        return response()->json([
            'success' => true,
            'price' => $service->price ?? 0,
            'service_name' => $service->service_name
        ]);
    }
    
    /**
     * Get available rewards for a customer
     * Only shows rewards that are:
     * - Claimed (claim_status = 1)
     * - Not yet redeemed (redemption_status = 'ready' or 2)
     * - Not expired
     * - Matches the branch or is global
     */
    public function getCustomerRewards(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_id' => 'required|exists:customer_accounts,id',
                'branch_id' => 'nullable|exists:branches,id'
            ]);

            $customerId = $validated['customer_id'];
            $branchId = $validated['branch_id'] ?? null;

            // Get customer rewards that are:
            // 1. Claimed (claim_status = 1)
            // 2. Not redeemed yet (redemption_status = 'ready')
            // 3. Not expired
            // 4. Active
            $customerRewardsQuery = CustomerReward::where('customer_account_id', $customerId)
                ->where('active', 1)
                ->where('claim_status', CustomerReward::CLAIM_STATUS_CLAIMED) // 1 = Claimed
                ->where('redemption_status', CustomerReward::REDEMPTION_STATUS_READY) // 'ready' for redemption
                ->where(function($query) {
                    $query->whereNull('expiration_date')
                          ->orWhere('expiration_date', '>', now());
                });

            // Filter by branch if specified (show rewards for this branch OR global rewards)
            if ($branchId) {
                $customerRewardsQuery->where(function($query) use ($branchId) {
                    $query->where('branch_id', $branchId)
                          ->orWhereNull('branch_id');
                });
            }

            $customerRewards = $customerRewardsQuery
                ->with(['loyaltyTier', 'loyaltyTier.redeemableItem', 'loyaltyTier.redeemableItem.targetService'])
                ->get();
                
            // Format rewards for frontend - only show service, percentage, and fixed discount types
            $rewards = $customerRewards->map(function ($reward) {
                $loyaltyTier = $reward->loyaltyTier;
                
                if (!$loyaltyTier) {
                    return null;
                }

                // Get the redeemable item to check the reward type
                $redeemableItem = $loyaltyTier->redeemableItem;
                
                if (!$redeemableItem) {
                    return null;
                }

                // Only show service, percentage discount, and fixed discount
                $allowedTypes = ['free_service', 'percentage_discount', 'fixed_discount'];
                if (!in_array($redeemableItem->reward_type, $allowedTypes)) {
                    return null;
                }

                $discountValue = 0;
                $isPercentage = false;
                $percentage = 0;
                $itemName = '';
                $rewardTypeLabel = '';
                $targetServiceId = null;
                $serviceCategoryId = null;
                
                // Determine reward type and value
                switch ($redeemableItem->reward_type) {
                    case 'free_service':
                        $rewardTypeLabel = 'Free Service';
                        $discountValue = $redeemableItem->monetary_value ?? 0;
                        $itemName = $redeemableItem->targetService 
                            ? $redeemableItem->targetService->service_name 
                            : 'Free Service';
                            
                        if ($redeemableItem->targetService) {
                            $targetServiceId = $redeemableItem->target_service_id;
                            $serviceCategoryId = $redeemableItem->targetService->service_category_id;
                        }
                        break;
                        
                    case 'percentage_discount':
                        $rewardTypeLabel = 'Percentage Discount';
                        $isPercentage = true;
                        $percentage = $redeemableItem->discount_percentage ?? 0;
                        $discountValue = 0; // Will be calculated on apply
                        $itemName = $percentage . '% OFF';
                        break;
                        
                    case 'fixed_discount':
                        $rewardTypeLabel = 'Fixed Discount';
                        $discountValue = $redeemableItem->monetary_value ?? 0;
                        $itemName = '₱' . number_format($discountValue, 2) . ' off';
                        break;
                        
                    default:
                        return null;
                }

                // Get days left until expiry
                $daysLeft = null;
                if ($reward->expiration_date) {
                    $daysLeft = now()->diffInDays($reward->expiration_date, false);
                    if ($daysLeft < 0) {
                        return null; // Expired
                    }
                }

                return [
                    'id' => $reward->id,
                    'voucher_code' => $reward->voucher_code ?? 'N/A',
                    'description' => $loyaltyTier->reward_description ?? $loyaltyTier->tier_name ?? 'Reward',
                    'item_name' => $itemName,
                    'reward_type' => $redeemableItem->reward_type,
                    'reward_type_label' => $rewardTypeLabel,
                    'discount_value' => $discountValue,
                    'is_percentage' => $isPercentage,
                    'percentage' => $percentage,
                    'discount_display' => $isPercentage 
                        ? $percentage . '% OFF' 
                        : ($discountValue > 0 ? '₱' . number_format($discountValue, 2) : 'Free'),
                    'expiration_date' => $reward->expiration_date ? date('M d, Y', strtotime($reward->expiration_date)) : 'No expiry',
                    'days_left' => $daysLeft,
                    'loyalty_tier_id' => $loyaltyTier->id,
                    'monetary_value' => $loyaltyTier->monetary_value ?? 0,
                    'discount_percentage' => $loyaltyTier->discount_percentage ?? 0,
                    'branch_id' => $reward->branch_id,
                    'target_service_id' => $targetServiceId,
                    'service_category_id' => $serviceCategoryId,
                ];
            })->filter()->values();

            return response()->json([
                'success' => true,
                'rewards' => $rewards
            ]);

        } catch (\Exception $e) {
            \Log::error('Error loading customer rewards: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error loading rewards: ' . $e->getMessage(),
                'rewards' => []
            ], 500);
        }
    }

    /**
     * Apply a reward to the current booking
     */
    public function applyReward(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_reward_id' => 'required|exists:customer_rewards,id',
                'total_amount' => 'required|numeric|min:0'
            ]);

            $customerReward = CustomerReward::with(['loyaltyTier', 'loyaltyTier.redeemableItem'])
                ->findOrFail($validated['customer_reward_id']);

            // Check if reward is still valid
            if ($customerReward->redemption_status == CustomerReward::REDEMPTION_STATUS_REDEEMED) {
                return response()->json([
                    'success' => false,
                    'message' => 'This reward has already been redeemed.'
                ], 400);
            }

            if ($customerReward->expiration_date && $customerReward->expiration_date < now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This reward has expired.'
                ], 400);
            }

            $totalAmount = $validated['total_amount'];
            $loyaltyTier = $customerReward->loyaltyTier;
            $redeemableItem = $loyaltyTier ? $loyaltyTier->redeemableItem : null;
            
            $discountValue = 0;
            $isPercentage = false;
            $percentage = 0;
            $rewardType = '';

            if ($redeemableItem) {
                $rewardType = $redeemableItem->reward_type;
                
                switch ($rewardType) {
                    case 'free_service':
                        $discountValue = $redeemableItem->monetary_value ?? 0;
                        // For free service, discount the full amount
                        $discountValue = min($discountValue, $totalAmount);
                        break;
                        
                    case 'percentage_discount':
                        $isPercentage = true;
                        $percentage = $redeemableItem->discount_percentage ?? 0;
                        $discountValue = ($totalAmount * $percentage) / 100;
                        break;
                        
                    case 'fixed_discount':
                        $discountValue = $redeemableItem->monetary_value ?? 0;
                        break;
                        
                    default:
                        // If no specific type, try monetary_value or percentage
                        if (isset($loyaltyTier->discount_percentage) && $loyaltyTier->discount_percentage > 0) {
                            $isPercentage = true;
                            $percentage = $loyaltyTier->discount_percentage;
                            $discountValue = ($totalAmount * $percentage) / 100;
                        } elseif (isset($loyaltyTier->monetary_value) && $loyaltyTier->monetary_value > 0) {
                            $discountValue = $loyaltyTier->monetary_value;
                        }
                }
            }

            // Ensure discount doesn't exceed total
            $discountValue = min($discountValue, $totalAmount);
            $newTotal = max(0, $totalAmount - $discountValue);

            return response()->json([
                'success' => true,
                'discount_value' => $discountValue,
                'new_total' => $newTotal,
                'is_percentage' => $isPercentage,
                'percentage' => $percentage,
                'reward_type' => $rewardType,
                'message' => 'Reward applied successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error applying reward: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error applying reward: ' . $e->getMessage()
            ], 500);
        }
    }

    // This function is only intended for WALK-IN BOOKING
    public function store(Request $request)
{
    $validated = $request->validate([
        'customer_account_id' => 'nullable|exists:customer_accounts,id',
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'contact_no' => 'required|string|max:20',
        'branch_id' => 'required|exists:branches,id',
        'service_category_id' => 'required|exists:service_categories,id',
        'service_name_id' => 'required|exists:service_names,id',
        'seat_id' => 'required|exists:seats,id',
        'payment_method' => 'required|integer|between:0,3',
        'total_amount' => 'required|numeric|min:0',
        'amount_paid' => 'nullable|numeric|min:0',
        'change' => 'nullable|numeric',
        'generated_password' => 'nullable|string',
        'notes' => 'nullable|string|max:500',
        'customer_reward_id' => 'nullable|exists:customer_rewards,id',
        'reward_discount_amount' => 'nullable|numeric|min:0',
        'reward_voucher_code' => 'nullable|string',
    ]);

    $paymentMethod = (int) $validated['payment_method'];

    // For GCash and Debit Card, automatically set amount_paid to total_amount
    if ($paymentMethod === 1 || $paymentMethod === 2) {
        $validated['amount_paid'] = $validated['total_amount'];
        $validated['change'] = 0;
    }

    if ($paymentMethod !== 3) {  // Not pay later
        if (!isset($validated['amount_paid']) || $validated['amount_paid'] === '' || $validated['amount_paid'] < $validated['total_amount']) {
            return back()->withErrors([
                'amount_paid' => 'Amount paid must be greater than or equal to total amount for selected payment method.'
            ])->withInput();
        }
    }

    return DB::transaction(function () use ($validated, $paymentMethod) {
        $customer = null;
        $isNewCustomer = false;
        $generatedPassword = $validated['generated_password'] ?? null;

        if (!empty($validated['customer_account_id'])) {
            $customer = CustomerAccount::find($validated['customer_account_id']);
        } else {
            if (empty($generatedPassword)) {
                $generatedPassword = Str::random(12);
            }

            $customer = CustomerAccount::firstOrCreate(
                ['email' => $validated['email']],
                [
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'contact_no' => $validated['contact_no'],
                    'password' => Hash::make($generatedPassword),
                    'regular' => 0,
                    'role' => 3,
                    'date_joined' => Carbon::now(),
                    'account_status' => 1,
                    'active' => 1,
                ]
            );

            if ($customer->wasRecentlyCreated) {
                $isNewCustomer = true;
            } else {
                $isNewCustomer = false;
                $generatedPassword = null;
            }
        }

        if (!$customer) {
            throw new \Exception('Customer could not be found or created.');
        }

        $serviceName = ServiceName::find($validated['service_name_id']);
        $bookingRefNo = 'BRN' . Carbon::now()->format('Ymd') . Str::upper(Str::random(4));

        $seat = Seat::find($validated['seat_id']);
        $branch = Branch::find($validated['branch_id']);
        $serviceCategory = ServiceCategory::find($validated['service_category_id']);

        // Create Booking
        $bookingData = [
            'booking_ref_no' => $bookingRefNo,
            'customer_account_id' => $customer->id,
            'branch_id' => $validated['branch_id'],
            'service_category_id' => $validated['service_category_id'],
            'service_name_id' => $validated['service_name_id'],
            'seat_id' => $validated['seat_id'],
            'date_start' => Carbon::now()->format('Y-m-d'),
            'start_time' => Carbon::now()->format('H:i:s'),
            'booking_date' => now(),
            'booking_status' => 1,
            'booking_type' => 0,
            'active' => 1,
        ];

        $booking = new Booking($bookingData);

        if (!$booking->created_by) {
            $booking->created_by = Auth::guard('owner')->id();
            $booking->created_by_type = 'owner';
            $booking->date_created = now();
        }

        $booking->save();

        // Create Booking Payment
        $paymentStatus = ($paymentMethod == 3) ? 2 : 1;
        $amountPaid = ($paymentMethod == 3) ? 0 : $validated['amount_paid'];
        $change = ($paymentMethod == 3) ? 0 : ($validated['change'] ?? 0);

        // Handle notes
        $notesValue = null;
        if (!empty($validated['notes'])) {
            $notesArray = [[
                'content' => $validated['notes'],
                'added_by_type' => 'Owner',
                'added_at' => now()->toDateTimeString(),
            ]];
            $notesValue = json_encode($notesArray);
        }

        $paymentData = [
            'customer_account_id' => $customer->id,
            'branch_id' => $validated['branch_id'],
            'booking_id' => $booking->id,
            'payment_date' => ($paymentMethod == 3) ? null : now(),
            'payment_method' => $paymentMethod,
            'total_amount' => $validated['total_amount'],
            'amount_paid' => $amountPaid,
            'change' => $change,
            'payment_category' => 1,
            'payment_status' => $paymentStatus,
            'notes' => $notesValue,
            'active' => 1,
        ];

        $bookingPayment = new BookingPayment($paymentData);

        if (!$bookingPayment->created_by) {
            $bookingPayment->created_by = Auth::guard('owner')->id();
            $bookingPayment->created_by_type = 'owner';
            $bookingPayment->date_created = now();
        }

        if (!$bookingPayment->uuid) {
            $bookingPayment->uuid = Str::uuid();
        }

        $bookingPayment->save();

        // Create Customer Checkin Record
        $checkinData = [
            'customer_account_id' => $customer->id,
            'branch_id' => $validated['branch_id'],
            'service_category_id' => $validated['service_category_id'],
            'service_name_id' => $validated['service_name_id'],
            'seat_id' => $validated['seat_id'],
            'booking_id' => $booking->id,
            'time_used' => 0,
            'checkin_status' => 1,
            'date_checked_in' => now(),
            'active' => 1,
        ];

        $customerCheckin = new CustomerCheckin($checkinData);

        if (!$customerCheckin->created_by) {
            $customerCheckin->created_by = Auth::guard('owner')->id();
            $customerCheckin->created_by_type = 'owner';
            $customerCheckin->date_created = now();
        }

        $customerCheckin->save();

        // ================================================================
        // REWARD REDEMPTION - If a reward was applied, mark it as redeemed
        // ================================================================
        if (!empty($validated['customer_reward_id'])) {
    $customerReward = CustomerReward::with(['loyaltyTier', 'loyaltyTier.redeemableItem'])
        ->find($validated['customer_reward_id']);
        
    if ($customerReward && $customerReward->customer_account_id == $customer->id) {
        
        // Get the reward details for redemption history
        $loyaltyTier = $customerReward->loyaltyTier;
        $redeemableItem = $loyaltyTier ? $loyaltyTier->redeemableItem : null;
        
        // Get original price from service_name
        $serviceNamePrice = $serviceName ? $serviceName->price : 0;
        $totalAmount = $validated['total_amount'];
        $discountAmount = abs($validated['reward_discount_amount'] ?? 0);
        
        // Calculate final amount: original price - discount, ensure it never goes negative
        $originalAmount = $serviceNamePrice > 0 ? $serviceNamePrice : $totalAmount;
        $finalAmount = max(0, $originalAmount - $discountAmount);
        
        // Get the authenticated owner user
        $owner = Auth::guard('owner')->user();
        
        // Create reward redemption record with audit fields
        $redemptionData = [
            'uuid' => (string) Str::uuid(),
            'customer_reward_id' => $customerReward->id,
            'customer_account_id' => $customer->id,
            'booking_id' => $booking->id,
            'service_category_id' => $validated['service_category_id'],
            'service_name_id' => $validated['service_name_id'],
            'reward_type' => $redeemableItem ? $redeemableItem->reward_type : null,
            'target_type' => 'service',
            'discount_value' => $redeemableItem ? (
                $redeemableItem->reward_type === 'percentage_discount' 
                    ? $redeemableItem->discount_percentage 
                    : $redeemableItem->monetary_value
            ) : null,
            'discount_amount' => $discountAmount,
            'original_amount' => $originalAmount,
            'final_amount' => $finalAmount,
            'receipt_number' => $bookingRefNo,
            'redeemed_by' => $owner->id,
            'redeemed_by_type' => 'OwnerAccount',
            'branch_id' => $validated['branch_id'],
            'notes' => 'Redeemed during walk-in booking: ' . $bookingRefNo,
            'redeemed_at' => now(),
            'active' => 1,
            // Audit fields - using lowercase 'owner' to match Booking model pattern
            'created_by' => $owner->id,
            'created_by_type' => 'owner',
            'date_created' => now(),
        ];
        
        $redemption = RewardRedemption::create($redemptionData);
        
        // Update customer reward status
        $customerReward->redemption_status = CustomerReward::REDEMPTION_STATUS_REDEEMED;
        $customerReward->redeemed_at = now();
        $customerReward->redeemed_at_branch_id = $validated['branch_id'];
        $customerReward->date_updated = now();
        $customerReward->save();
        
        \Log::info('Reward redeemed successfully', [
            'customer_reward_id' => $customerReward->id,
            'booking_id' => $booking->id,
            'voucher_code' => $customerReward->voucher_code,
            'original_amount' => $originalAmount,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
            'created_by' => $owner->id,
            'created_by_type' => 'owner'
        ]);
    }
}

        // Send notification for branch update
        $actor = Auth::guard('owner')->user();
        $bookingBranch = Branch::find($booking->branch_id);
        $customer = CustomerAccount::find($booking->customer_account_id);
        $owner = Auth::guard('owner')->user();
        $owners = OwnerAccount::where('id', $owner->id)->get();

        // Send notification
        Notification::send($owners, new BookNowNotification(
            $booking,
            $bookingBranch,
            $customer,
            $actor,
            'created'
        ));

        // Notify Staff in the same branch
        $staffMembers = StaffAccount::where('branch_id', $booking->branch_id)
            ->where('owner_account_id', $actor->id)
            ->where('active', 1)
            ->get();

        Notification::send($staffMembers, new BookNowStaffNotification(
            $booking,
            $bookingBranch,
            $customer,
            $actor,
            'created'
        ));

        // Send QR Code Email with password for new customers
        $this->sendQRCodeEmail($customer, $booking, $serviceName, $seat, $isNewCustomer, $generatedPassword);

        return redirect()
            ->route('sub_one.customer_checkins.index', ['brn' => $bookingRefNo])
            ->with('booking_success', 'Booking created successfully! Reference Number: ' . $bookingRefNo . '. QR code has been sent to customer email.')
            ->with('checkin_success', 'Customer checked-in.');
    });
}

    public function sendQRCodeEmail($customer, $booking, $serviceName, $seat, $isNewCustomer = false, $generatedPassword = null)
    {
        $qrCodeData = null;
        $tempPath = null;

        try {
            // Get additional data needed for the email
            $branch = Branch::find($booking->branch_id);
            $serviceCategory = ServiceCategory::find($booking->service_category_id);
            $bookingPayment = BookingPayment::where('booking_id', $booking->id)->first();

            // Generate QR Code and get the raw image data
            $qrCodeData = $this->generateQRCode($booking);

            if ($qrCodeData) {
                $tempDir = storage_path('app/temp');
                if (!file_exists($tempDir)) {
                    mkdir($tempDir, 0777, true);
                }
                $tempPath = $tempDir . '/' . Str::random(40) . '.png';
                file_put_contents($tempPath, $qrCodeData);
            }

            $emailData = [
                'customer' => $customer,
                'booking' => $booking,
                'serviceName' => $serviceName,
                'serviceCategory' => $serviceCategory,
                'branch' => $branch,
                'seat' => $seat,
                'bookingPayment' => $bookingPayment,
                'qrCodePath' => $tempPath,
                'appName' => config('app.name', 'LinkudHub'),
                'bookingRefNo' => $booking->booking_ref_no,
                'isNewCustomer' => $isNewCustomer,
                'generatedPassword' => $generatedPassword
            ];

            try {
                Mail::send('owner.booking.send_checkout_email_qr_code', $emailData, function ($message) use ($customer, $booking, $tempPath) {
                    $message
                        ->to($customer->email)
                        ->subject('From LinkudHub')
                        ->from(config('mail.from.address'), config('mail.from.name'));

                    if ($tempPath && file_exists($tempPath)) {
                        $message->attach($tempPath, [
                            'as' => 'qr-code.png',
                            'mime' => 'image/png',
                        ]);
                    }
                });
            } finally {
                if ($tempPath && file_exists($tempPath)) {
                    unlink($tempPath);
                }
            }
        } catch (\Exception $e) {
            // Send fallback email without QR code
            $this->sendEmailWithoutQRCode($customer, $booking, $serviceName, $seat, $isNewCustomer, $generatedPassword);
        }
    }

    private function generateQRCode($booking)
    {
        try {
            $qrContent = json_encode([
                'booking_ref' => $booking->booking_ref_no,
                'customer_id' => $booking->customer_account_id,
                'booking_id' => $booking->id,
                'timestamp' => now()->timestamp
            ]);

            // QR-CODE MONKEY API
            $apiUrl = 'https://api.qrcode-monkey.com/qr/custom';

            $postData = [
                'data' => $qrContent,
                'config' => [
                    'body' => 'circle',
                    'eye' => 'frame0',
                    'eyeBall' => 'ball0',
                    'bodyColor' => '#000000',
                    'bgColor' => '#FFFFFF',
                    'eye1Color' => '#000000',
                    'eye2Color' => '#000000',
                    'eye3Color' => '#000000',
                    'eyeBall1Color' => '#000000',
                    'eyeBall2Color' => '#000000',
                    'eyeBall3Color' => '#000000',
                ],
                'size' => 300,
                'download' => true,
                'file' => 'png'
            ];

            $client = new \GuzzleHttp\Client();
            $response = $client->post($apiUrl, [
                'json' => $postData,
                'headers' => ['Content-Type' => 'application/json'],
                'timeout' => 15
            ]);

            if ($response->getStatusCode() === 200) {
                $jsonBody = $response->getBody()->getContents();
                $data = json_decode($jsonBody);

                if ($data && isset($data->imageUrl)) {
                    $imageUrl = 'https:' . $data->imageUrl;
                    $imageResponse = $client->get($imageUrl, [
                        'timeout' => 3
                    ]);

                    if ($imageResponse->getStatusCode() === 200) {
                        $imageContent = $imageResponse->getBody()->getContents();
                        return $imageContent;
                    } else {
                        return null;
                    }
                } else {
                    return null;
                }
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function sendEmailWithoutQRCode($customer, $booking, $serviceName, $seat, $isNewCustomer = false, $generatedPassword = null)
    {
        try {
            $branch = Branch::find($booking->branch_id);
            $serviceCategory = ServiceCategory::find($booking->service_category_id);
            $bookingPayment = BookingPayment::where('booking_id', $booking->id)->first();

            $emailData = [
                'customer' => $customer,
                'booking' => $booking,
                'serviceName' => $serviceName,
                'serviceCategory' => $serviceCategory,
                'branch' => $branch,
                'seat' => $seat,
                'bookingPayment' => $bookingPayment,
                'qrCodePath' => null,
                'appName' => config('app.name', 'LinkudHub'),
                'isNewCustomer' => $isNewCustomer,
                'generatedPassword' => $generatedPassword
            ];

            Mail::send('owner.booking.send_checkout_email_qr_code', $emailData, function ($message) use ($customer, $booking) {
                $message
                    ->to($customer->email)
                    ->subject('From LinkudHub')
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}