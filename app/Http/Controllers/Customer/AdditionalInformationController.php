<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Branch;
use App\Models\CustomerAccount;
use App\Models\CustomerReward;
use App\Models\LoyaltyTier;
use App\Models\OwnerAccount;
use App\Models\RewardRedemption;
use App\Models\Seat;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use App\Models\StaffAccount;
use App\Models\StaffShiftSchedule;
use App\Notifications\Customer\BookingProcessNotification;
use App\Notifications\Staff\BookingProcessStaffNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdditionalInformationController extends Controller
{
    public function showBranch($branch_uuid)
    {
        $branch = Branch::with(['serviceCategories' => function ($q) {
            $q
                ->where('active', 1)
                ->where('service_category_status', 1)
                ->with(['serviceNames' => function ($q) {
                    $q->where('active', 1)->where('service_name_status', 1);
                }]);
        }])
            ->where('uuid', $branch_uuid)
            ->where('active', 1)
            ->where('branch_status', 1)
            ->firstOrFail();

        $branchFeedbackData = $this->getBranchFeedbacks($branch->uuid);

        $branch->feedbacks_avg_rating = $branchFeedbackData['average_rating'];
        $branch->feedbacks_count = $branchFeedbackData['total_reviews'];

        foreach ($branch->serviceCategories as $category) {
            $categoryFeedbackData = $this->getCategoryFeedbacks($category->uuid);
            $category->feedbacks_avg_rating = $categoryFeedbackData['average_rating'];
            $category->feedbacks_count = $categoryFeedbackData['total_reviews'];
        }

        if ($branch->open_time && $branch->close_time) {
            $branch->formatted_open_time = $this->formatTimeTo12Hour($branch->open_time);
            $branch->formatted_close_time = $this->formatTimeTo12Hour($branch->close_time);
        }

        return view('customer.home.branch-details', compact('branch'));
    }

    private function getBranchFeedbacks($branchUuid)
    {
        $branch = Branch::where('uuid', $branchUuid)->first();

        if (!$branch) {
            return [
                'average_rating' => 0,
                'total_reviews' => 0
            ];
        }

        $averageRating = \App\Models\Feedback::where('feedbacks.branch_id', $branch->id)
            ->where('feedbacks.active', 1)
            ->where('feedbacks.approved', 1)
            ->avg('feedbacks.rating');

        $totalReviews = \App\Models\Feedback::where('feedbacks.branch_id', $branch->id)
            ->where('feedbacks.active', 1)
            ->where('feedbacks.approved', 1)
            ->count();

        return [
            'average_rating' => $averageRating ? round($averageRating, 1) : 0,
            'total_reviews' => $totalReviews
        ];
    }

    private function getCategoryFeedbacks($categoryUuid)
    {
        $category = ServiceCategory::where('uuid', $categoryUuid)->first();

        if (!$category) {
            return [
                'average_rating' => 0,
                'total_reviews' => 0
            ];
        }

        $averageRating = \App\Models\Feedback::where('feedbacks.service_category_id', $category->id)
            ->where('feedbacks.active', 1)
            ->where('feedbacks.approved', 1)
            ->avg('feedbacks.rating');

        $totalReviews = \App\Models\Feedback::where('feedbacks.service_category_id', $category->id)
            ->where('feedbacks.active', 1)
            ->where('feedbacks.approved', 1)
            ->count();

        return [
            'average_rating' => $averageRating ? round($averageRating, 1) : 0,
            'total_reviews' => $totalReviews
        ];
    }

    private function formatTimeTo12Hour($time)
    {
        try {
            if (str_contains($time, ':')) {
                if (strlen($time) > 5) {
                    $carbonTime = Carbon::createFromFormat('H:i:s', $time);
                } else {
                    $carbonTime = Carbon::createFromFormat('H:i', $time);
                }
                return $carbonTime->format('h:i A');
            }
            return $time;
        } catch (\Exception $e) {
            return $time;
        }
    }

    public function showServiceCategory($branch_uuid, $service_category_uuid)
    {
        $branch = Branch::where('uuid', $branch_uuid)
            ->where('active', 1)
            ->where('branch_status', 1)
            ->firstOrFail();

        $serviceCategory = ServiceCategory::with(['serviceNames' => function ($q) {
            $q->where('active', 1)->where('service_name_status', 1);
        }])
            ->where('uuid', $service_category_uuid)
            ->where('active', 1)
            ->where('service_category_status', 1)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        $sortedServices = $serviceCategory->serviceNames->sortBy('id');

        $serviceFeedbacks = [];
        foreach ($serviceCategory->serviceNames as $service) {
            $serviceFeedbacks[$service->id] = $this->getServiceFeedbacks($service->uuid);
        }

        $branchFeedbackData = $this->getBranchFeedbacks($branch->uuid);
        $branch->feedbacks_avg_rating = $branchFeedbackData['average_rating'];
        $branch->feedbacks_count = $branchFeedbackData['total_reviews'];

        $categoryFeedbackData = $this->getCategoryFeedbacks($serviceCategory->uuid);
        $serviceCategory->feedbacks_avg_rating = $categoryFeedbackData['average_rating'];
        $serviceCategory->feedbacks_count = $categoryFeedbackData['total_reviews'];

        return view('customer.home.service-category', compact(
            'branch',
            'serviceCategory',
            'sortedServices',
            'serviceFeedbacks'
        ));
    }

    private function getServiceFeedbacks($serviceUuid)
    {
        $service = ServiceName::where('uuid', $serviceUuid)->first();

        if (!$service) {
            return [
                'feedbacks' => collect(),
                'average_rating' => 0,
                'total_reviews' => 0,
                'rating_breakdown' => collect()
            ];
        }

        $feedbacks = \App\Models\Feedback::with(['branch'])
            ->where('feedbacks.service_name_id', $service->id)
            ->approved()
            ->active()
            ->latest()
            ->limit(5)
            ->get();

        $averageRating = \App\Models\Feedback::where('feedbacks.service_name_id', $service->id)
            ->approved()
            ->active()
            ->avg('feedbacks.rating');

        $totalReviews = \App\Models\Feedback::where('feedbacks.service_name_id', $service->id)
            ->approved()
            ->active()
            ->count();

        $ratingBreakdown = $this->getRatingBreakdownByService($service->id);

        return [
            'feedbacks' => $feedbacks,
            'average_rating' => $averageRating ? round($averageRating, 1) : 0,
            'total_reviews' => $totalReviews,
            'rating_breakdown' => $ratingBreakdown
        ];
    }

    private function getRatingBreakdownByService($serviceNameId)
    {
        $breakdown = \App\Models\Feedback::where('feedbacks.service_name_id', $serviceNameId)
            ->approved()
            ->active()
            ->selectRaw('feedbacks.rating, COUNT(*) as count')
            ->groupBy('feedbacks.rating')
            ->orderBy('feedbacks.rating', 'desc')
            ->get();

        $fullBreakdown = collect();
        for ($i = 5; $i >= 1; $i--) {
            $ratingData = $breakdown->where('rating', $i)->first();
            $fullBreakdown->push([
                'rating' => $i,
                'count' => $ratingData ? $ratingData->count : 0
            ]);
        }

        return $fullBreakdown;
    }

    public function showBookingPreview(Request $request)
    {
        if (!$request->has('branch_id')) {
            return redirect()->route('sub_three.home.showHome')->with('error', 'Please start your booking process again.');
        }

        $seat = null;
        if ($request->has('seat_id')) {
            $seat = Seat::find($request->input('seat_id'));
        }

        $bookingDetails = [
            'branch' => [
                'id' => $request->input('branch_id'),
                'uuid' => $request->input('branch_uuid'),
                'branch_name' => $request->input('branch_name'),
                'location' => $request->input('branch_location'),
                'open_time' => $request->input('branch_open_time'),
                'close_time' => $request->input('branch_close_time'),
            ],
            'service_category' => [
                'id' => $request->input('service_category_id'),
                'uuid' => $request->input('service_category_uuid'),
                'service_category' => $request->input('service_category_name'),
            ],
            'service_name' => [
                'id' => $request->input('service_name_id'),
                'uuid' => $request->input('service_name_uuid'),
                'service_name' => $request->input('service_name'),
                'time_duration' => $request->input('service_time_duration'),
                'price' => $request->input('service_price'),
                'space_type' => $request->input('service_space_type'),
            ],
            'seat' => $seat,
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'booking_time' => $request->input('booking_time'),
            'end_time' => $request->input('end_time'),
            'main_duration' => $request->input('main_duration', $this->parseDuration($request->input('service_time_duration'))),
            'total_duration' => $request->input('total_duration'),
            'additional_hours' => $request->input('additional_hours', 0),
            'additional_minutes' => $request->input('additional_minutes', 0),
            'additional_price' => $request->input('additional_price', 0),
            'total_price' => $request->input('total_price'),
        ];

        if ($request->has('extended_start_time') && $request->input('extended_duration_total') > 0) {
            $bookingDetails['extended_start_time'] = $request->input('extended_start_time');
            $bookingDetails['extended_end_time'] = $request->input('extended_end_time');
            $bookingDetails['extended_date_start'] = $request->input('extended_start_date');
            $bookingDetails['extended_date_end'] = $request->input('extended_end_date');

            $extendedDurationMinutes = $request->input('extended_duration_total');
            $bookingDetails['extended_duration'] = $this->formatDuration($extendedDurationMinutes);
            $bookingDetails['extended_duration_minutes'] = $extendedDurationMinutes;
        }

        $bookingDetails['branch'] = (object) $bookingDetails['branch'];
        $bookingDetails['service_category'] = (object) $bookingDetails['service_category'];
        $bookingDetails['service_name'] = (object) $bookingDetails['service_name'];

        $showPayment = $request->has('show_payment') && $request->show_payment === 'true';
        $ownerGcashQrCode = [];

        if ($showPayment) {
            $branchId = $bookingDetails['branch']->id;
            $branch = Branch::find($branchId);
            
            if ($branch && $branch->owner_account_id) {
                $owner = OwnerAccount::find($branch->owner_account_id);
                if ($owner && $owner->gcash_qr_code_img) {
                    if (is_array($owner->gcash_qr_code_img)) {
                        $ownerGcashQrCode = $owner->gcash_qr_code_img;
                    } elseif (is_string($owner->gcash_qr_code_img)) {
                        $decoded = json_decode($owner->gcash_qr_code_img, true);
                        $ownerGcashQrCode = is_array($decoded) ? $decoded : [$owner->gcash_qr_code_img];
                    }
                }
            }
        }

        return view('customer.home.booking-preview', compact('bookingDetails', 'showPayment', 'ownerGcashQrCode'));
    }

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string|max:1000',
        ]);

        try {
            $adminEmailContent = "
        New Contact Message Received
        
        Name: {$validated['name']}
        Email: {$validated['email']}
        Message: 
        {$validated['message']}
        
        Sent from LinkudHub Contact Form
        ";

            Mail::raw($adminEmailContent, function ($message) use ($validated) {
                $message
                    ->to('linkudhub@gmail.com')
                    ->subject('New Contact Message from ' . $validated['name'])
                    ->replyTo($validated['email'], $validated['name']);
            });

            $thankYouContent = "

            Dear {$validated['name']},

            Thank you for contacting LinkudHub! ✨

            We're excited to hear from you and appreciate you taking the time to reach out. Your message has been successfully delivered to our team.

            📋 Message Summary:
            {$validated['message']}

            ⏰ What happens next?
            • Your message has been queued for review
            • Our team will respond within 24-48 hours
            • We'll address all your questions and concerns

            📍 In the meantime, you can:
            • Browse our website: linkudhub.com
            • Check branch locations and open hours
            • Learn about premium spaces

            We're committed to providing you with the best relaxation and workspace experience!

            Warm regards,
            The LinkudHub Team
            linkudhub@gmail.com
            09084557940 | 09203365265 | 09659328807 

            ───────────────────────────────────────────────
            This is an automated response. Please do not reply to this email.
            For urgent matters, please call us directly.
            ───────────────────────────────────────────────
            ";

            Mail::raw($thankYouContent, function ($message) use ($validated) {
                $message
                    ->to($validated['email'])
                    ->subject("Thank You for Contacting LinkudHub - We've Received Your Message!");
            });

            return back()->with('success', 'Message sent successfully! We have sent a confirmation email to you.');
        } catch (\Exception $e) {
            try {
                $adminEmailContent = "
            New Contact Message Received - AUTO-RESPONSE FAILED
            
            Name: {$validated['name']}
            Email: {$validated['email']}
            Message: 
            {$validated['message']}
            
            Note: The auto-responder failed for this user.
            Sent from LinkudHub Contact Form
            ";

                Mail::raw($adminEmailContent, function ($message) use ($validated) {
                    $message
                        ->to('linkudhub@gmail.com')
                        ->subject('New Contact Message from ' . $validated['name'])
                        ->replyTo($validated['email'], $validated['name']);
                });

                return back()->with('warning', 'Message sent! However, we encountered an issue sending the confirmation email. We will still get back to you soon.');
            } catch (\Exception $secondError) {
                return back()->with('error', 'Failed to send message. Please try again later or contact us directly at linkudhub@gmail.com');
            }
        }
    }

    private function formatDuration($minutes)
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($hours == 0) {
            return $mins . ' minute' . ($mins != 1 ? 's' : '');
        } elseif ($mins == 0) {
            return $hours . ' hour' . ($hours != 1 ? 's' : '');
        } else {
            return $hours . ' hour' . ($hours != 1 ? 's' : '') . ' ' . $mins . ' minute' . ($mins != 1 ? 's' : '');
        }
    }

    private function parseDuration($duration)
    {
        if (str_contains($duration, 'hour')) {
            $hours = (int) $duration;
            return $hours * 60;
        } elseif (str_contains($duration, 'minute')) {
            return (int) $duration;
        }
        return 60;
    }

    /**
     * Get available rewards for a customer
     */
    public function getCustomerRewards(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_id' => 'required|exists:customer_accounts,id',
                'branch_id' => 'nullable|exists:branches,id',
                'service_name_id' => 'nullable|exists:service_names,id',
                'service_category_id' => 'nullable|exists:service_categories,id'
            ]);

            $customerId = $validated['customer_id'];
            $branchId = $validated['branch_id'] ?? null;
            $serviceNameId = $validated['service_name_id'] ?? null;
            $serviceCategoryId = $validated['service_category_id'] ?? null;

            $customerRewardsQuery = CustomerReward::where('customer_account_id', $customerId)
                ->where('active', 1)
                ->where('claim_status', CustomerReward::CLAIM_STATUS_CLAIMED)
                ->where('redemption_status', CustomerReward::REDEMPTION_STATUS_READY)
                ->where(function($query) {
                    $query->whereNull('expiration_date')
                          ->orWhere('expiration_date', '>', now());
                });

            if ($branchId) {
                $customerRewardsQuery->where(function($query) use ($branchId) {
                    $query->where('branch_id', $branchId)
                          ->orWhereNull('branch_id');
                });
            }

            $customerRewards = $customerRewardsQuery
                ->with(['loyaltyTier', 'loyaltyTier.redeemableItem', 'loyaltyTier.redeemableItem.targetService'])
                ->get();
                
            $rewards = $customerRewards->map(function ($reward) use ($serviceNameId, $serviceCategoryId) {
                $loyaltyTier = $reward->loyaltyTier;
                
                if (!$loyaltyTier || !$loyaltyTier->redeemableItem) {
                    return null;
                }

                $redeemableItem = $loyaltyTier->redeemableItem;
                $allowedTypes = ['free_service', 'percentage_discount', 'fixed_discount'];
                if (!in_array($redeemableItem->reward_type, $allowedTypes)) {
                    return null;
                }

                if ($serviceNameId && $redeemableItem->target_service_id && $redeemableItem->target_service_id != $serviceNameId) {
                    return null;
                }
                if ($serviceCategoryId && $redeemableItem->target_service_category_id && $redeemableItem->target_service_category_id != $serviceCategoryId) {
                    return null;
                }

                $discountValue = 0;
                $isPercentage = false;
                $percentage = 0;
                $itemName = '';
                $rewardTypeLabel = '';
                
                switch ($redeemableItem->reward_type) {
                    case 'free_service':
                        $rewardTypeLabel = 'Free Service';
                        $discountValue = $redeemableItem->monetary_value ?? 0;
                        $itemName = $redeemableItem->targetService 
                            ? $redeemableItem->targetService->service_name 
                            : 'Free Service';
                        break;
                    case 'percentage_discount':
                        $rewardTypeLabel = 'Percentage Discount';
                        $isPercentage = true;
                        $percentage = $redeemableItem->discount_percentage ?? 0;
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

                $daysLeft = null;
                if ($reward->expiration_date) {
                    $daysLeft = now()->diffInDays($reward->expiration_date, false);
                    if ($daysLeft < 0) {
                        return null;
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
                    'target_service_id' => $redeemableItem->target_service_id ?? null,
                    'service_category_id' => $redeemableItem->target_service_category_id ?? null,
                ];
            })->filter()->values();

            return response()->json([
                'success' => true,
                'rewards' => $rewards
            ]);

        } catch (\Exception $e) {
            \Log::error('Error loading customer rewards: ' . $e->getMessage());
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
                'total_amount' => 'required|numeric|min:0',
                'service_name_id' => 'nullable|exists:service_names,id',
                'service_category_id' => 'nullable|exists:service_categories,id',
            ]);

            $customerReward = CustomerReward::with(['loyaltyTier', 'loyaltyTier.redeemableItem', 'loyaltyTier.redeemableItem.targetService'])
                ->findOrFail($validated['customer_reward_id']);

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
            
            if ($redeemableItem) {
                if ($redeemableItem->target_service_id && 
                    isset($validated['service_name_id']) && 
                    $redeemableItem->target_service_id != $validated['service_name_id']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This reward can only be used with the specific service it was earned for.'
                    ], 400);
                }
                
                if ($redeemableItem->target_service_category_id && 
                    isset($validated['service_category_id']) && 
                    $redeemableItem->target_service_category_id != $validated['service_category_id']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This reward can only be used with the specific service category it was earned for.'
                    ], 400);
                }
            }
            
            $discountValue = 0;
            $isPercentage = false;
            $percentage = 0;
            $rewardType = '';

            if ($redeemableItem) {
                $rewardType = $redeemableItem->reward_type;
                
                switch ($rewardType) {
                    case 'free_service':
                        $discountValue = $redeemableItem->monetary_value ?? 0;
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
                        if (isset($loyaltyTier->discount_percentage) && $loyaltyTier->discount_percentage > 0) {
                            $isPercentage = true;
                            $percentage = $loyaltyTier->discount_percentage;
                            $discountValue = ($totalAmount * $percentage) / 100;
                        } elseif (isset($loyaltyTier->monetary_value) && $loyaltyTier->monetary_value > 0) {
                            $discountValue = $loyaltyTier->monetary_value;
                        }
                }
            }

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
            \Log::error('Error applying reward: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error applying reward: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create reward redemption record when booking is confirmed
     */
    private function createRewardRedemption($customerRewardId, $bookingId, $branchId, $serviceNameId, $serviceCategoryId, $discountAmount, $totalAmount, $finalAmount)
    {
        try {
            if (!$customerRewardId) return null;

            $customerReward = CustomerReward::with(['loyaltyTier', 'loyaltyTier.redeemableItem'])
                ->find($customerRewardId);
                
            if (!$customerReward) return null;

            $loyaltyTier = $customerReward->loyaltyTier;
            $redeemableItem = $loyaltyTier ? $loyaltyTier->redeemableItem : null;
            
            // Get the service name to get the original price
            $serviceName = ServiceName::find($serviceNameId);
            $originalPrice = $serviceName ? $serviceName->price : $totalAmount;
            
            // Ensure discount doesn't exceed original price
            $discountAmount = min($discountAmount, $originalPrice);
            $finalAmount = max(0, $originalPrice - $discountAmount);

            // Create reward redemption record
            $redemptionData = [
                'uuid' => (string) Str::uuid(),
                'customer_reward_id' => $customerReward->id,
                'customer_account_id' => $customerReward->customer_account_id,
                'booking_id' => $bookingId,
                'service_category_id' => $serviceCategoryId,
                'service_name_id' => $serviceNameId,
                'reward_type' => $redeemableItem ? $redeemableItem->reward_type : null,
                'target_type' => 'service',
                'discount_value' => $redeemableItem ? (
                    $redeemableItem->reward_type === 'percentage_discount' 
                        ? $redeemableItem->discount_percentage 
                        : $redeemableItem->monetary_value
                ) : null,
                'discount_amount' => $discountAmount,
                'original_amount' => $originalPrice,
                'final_amount' => $finalAmount,
                'receipt_number' => 'BKG-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'redeemed_by' => $customerReward->customer_account_id,
                'redeemed_by_type' => 'CustomerAccount',
                'branch_id' => $branchId,
                'notes' => 'Redeemed during online booking',
                'redeemed_at' => now(),
                'date_created' => now(),
                'active' => 1,
            ];
            
            $redemption = RewardRedemption::create($redemptionData);
            
            // Update customer reward status
            $customerReward->redemption_status = CustomerReward::REDEMPTION_STATUS_REDEEMED;
            $customerReward->redeemed_at = now();
            $customerReward->redeemed_at_branch_id = $branchId;
            $customerReward->date_updated = now();
            $customerReward->save();
            
            Log::info('Reward redeemed during booking', [
                'customer_reward_id' => $customerReward->id,
                'booking_id' => $bookingId,
                'voucher_code' => $customerReward->voucher_code,
                'discount_amount' => $discountAmount
            ]);
            
            return $redemption;
            
        } catch (\Exception $e) {
            Log::error('Error creating reward redemption: ' . $e->getMessage());
            return null;
        }
    }

    public function showPaymentOptions(Request $request)
    {
        $bookingDetails = null;

        if ($request->has('branch_id') && $request->has('service_name_id')) {
            $bookingDetails = $this->prepareBookingDetailsFromRequest($request);
        }

        if (!$bookingDetails) {
            $bookingDetails = $request->session()->get('booking_details');
        }

        if (!$bookingDetails && ($request->has('branch_id') || $request->has('branch_uuid'))) {
            $bookingDetails = $this->reconstructBookingDetailsFromRequest($request);
        }

        if (!$bookingDetails || !isset($bookingDetails['branch'])) {
            return redirect()
                ->route('sub_three.home.showHome')
                ->with('error', 'No booking details found. Please start your booking again.');
        }

        $request->session()->put('booking_details', $bookingDetails);

        $ownerGcashQrCode = [];
        $branchId = is_object($bookingDetails['branch']) ? $bookingDetails['branch']->id : $bookingDetails['branch']['id'];
        $branch = Branch::find($branchId);

        if ($branch && $branch->owner_account_id) {
            $owner = OwnerAccount::find($branch->owner_account_id);
            if ($owner && $owner->gcash_qr_code_img) {
                if (is_array($owner->gcash_qr_code_img)) {
                    $ownerGcashQrCode = $owner->gcash_qr_code_img;
                } elseif (is_string($owner->gcash_qr_code_img)) {
                    $decoded = json_decode($owner->gcash_qr_code_img, true);
                    $ownerGcashQrCode = is_array($decoded) ? $decoded : [$owner->gcash_qr_code_img];
                }
            }
        }

        // Get customer rewards
        $customer = Auth::guard('customer')->user();
        $availableRewards = collect();

        if ($customer) {
            try {
                $serviceNameId = is_object($bookingDetails['service_name']) 
                    ? $bookingDetails['service_name']->id 
                    : ($bookingDetails['service_name']['id'] ?? null);
                
                $serviceCategoryId = is_object($bookingDetails['service_category']) 
                    ? $bookingDetails['service_category']->id 
                    : ($bookingDetails['service_category']['id'] ?? null);

                $customerRewards = CustomerReward::where('customer_account_id', $customer->id)
                    ->where('active', 1)
                    ->where('claim_status', CustomerReward::CLAIM_STATUS_CLAIMED)
                    ->where('redemption_status', CustomerReward::REDEMPTION_STATUS_READY)
                    ->where(function($query) {
                        $query->whereNull('expiration_date')
                              ->orWhere('expiration_date', '>', now());
                    })
                    ->where(function($query) use ($branchId) {
                        $query->where('branch_id', $branchId)
                              ->orWhereNull('branch_id');
                    })
                    ->with(['loyaltyTier', 'loyaltyTier.redeemableItem', 'loyaltyTier.redeemableItem.targetService'])
                    ->get();

                $availableRewards = $customerRewards->map(function ($reward) use ($serviceNameId, $serviceCategoryId) {
                    $loyaltyTier = $reward->loyaltyTier;
                    
                    if (!$loyaltyTier || !$loyaltyTier->redeemableItem) {
                        return null;
                    }

                    $redeemableItem = $loyaltyTier->redeemableItem;
                    $allowedTypes = ['free_service', 'percentage_discount', 'fixed_discount'];
                    
                    if (!in_array($redeemableItem->reward_type, $allowedTypes)) {
                        return null;
                    }

                    if ($serviceNameId && $redeemableItem->target_service_id && 
                        $redeemableItem->target_service_id != $serviceNameId) {
                        return null;
                    }
                    
                    if ($serviceCategoryId && $redeemableItem->target_service_category_id && 
                        $redeemableItem->target_service_category_id != $serviceCategoryId) {
                        return null;
                    }

                    $discountValue = 0;
                    $isPercentage = false;
                    $percentage = 0;
                    $itemName = '';
                    $rewardTypeLabel = '';
                    
                    switch ($redeemableItem->reward_type) {
                        case 'free_service':
                            $rewardTypeLabel = 'Free Service';
                            $discountValue = $redeemableItem->monetary_value ?? 0;
                            $itemName = $redeemableItem->targetService 
                                ? $redeemableItem->targetService->service_name 
                                : 'Free Service';
                            break;
                        case 'percentage_discount':
                            $rewardTypeLabel = 'Percentage Discount';
                            $isPercentage = true;
                            $percentage = $redeemableItem->discount_percentage ?? 0;
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

                    $daysLeft = null;
                    if ($reward->expiration_date) {
                        $daysLeft = now()->diffInDays($reward->expiration_date, false);
                        if ($daysLeft < 0) {
                            return null;
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
                        'target_service_id' => $redeemableItem->target_service_id ?? null,
                        'service_category_id' => $redeemableItem->target_service_category_id ?? null,
                    ];
                })->filter()->values();

            } catch (\Exception $e) {
                \Log::error('Error loading rewards in showPaymentOptions: ' . $e->getMessage());
                $availableRewards = collect();
            }
        }

        return view('customer.home.booking-preview', [
            'bookingDetails' => $bookingDetails,
            'showPayment' => true,
            'ownerGcashQrCode' => $ownerGcashQrCode,
            'availableRewards' => $availableRewards,
        ]);
    }

    private function prepareBookingDetailsFromRequest(Request $request)
    {
        if (!$request->has('branch_id') || !$request->has('service_name_id')) {
            return null;
        }

        $branch = Branch::find($request->input('branch_id'));
        $serviceCategory = ServiceCategory::find($request->input('service_category_id'));
        $serviceName = ServiceName::find($request->input('service_name_id'));
        $seat = $request->has('seat_id') ? Seat::find($request->input('seat_id')) : null;

        if (!$branch || !$serviceCategory || !$serviceName) {
            return null;
        }

        $additionalPrice = $request->input('additional_price', 0);
        $totalPrice = $request->input('total_price', 0);

        if ($additionalPrice == 0 && ($request->input('additional_hours', 0) > 0 || $request->input('additional_minutes', 0) > 0)) {
            $hourlyRate = $this->calculateHourlyRateFromService($serviceName);
            if ($hourlyRate) {
                $hours = $request->input('additional_hours', 0);
                $minutes = $request->input('additional_minutes', 0);
                $additionalPrice = ($hours * $hourlyRate) + (($minutes / 60) * $hourlyRate);
                $totalPrice = $serviceName->price + $additionalPrice;
            }
        } elseif ($totalPrice == 0) {
            $totalPrice = $serviceName->price + $additionalPrice;
        }

        $bookingDetails = [
            'branch' => $branch,
            'service_category' => $serviceCategory,
            'service_name' => $serviceName,
            'seat' => $seat,
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'booking_time' => $request->input('booking_time'),
            'end_time' => $request->input('end_time'),
            'main_duration' => $request->input('main_duration', $this->parseDuration($serviceName->time_duration)),
            'total_duration' => $request->input('total_duration', $request->input('main_duration', $this->parseDuration($serviceName->time_duration))),
            'additional_hours' => $request->input('additional_hours', 0),
            'additional_minutes' => $request->input('additional_minutes', 0),
            'additional_price' => $additionalPrice,
            'total_price' => $totalPrice,
            'extended_start_time' => $request->input('extended_start_time', null),
            'extended_end_time' => $request->input('extended_end_time', null),
            'extended_date_start' => $request->input('extended_start_date', null),
            'extended_date_end' => $request->input('extended_end_date', null),
        ];

        $extendedDurationMinutes = $request->input('extended_duration_total', 0);
        $bookingDetails['extended_duration'] = $extendedDurationMinutes > 0 ? $this->formatDuration($extendedDurationMinutes) : null;
        $bookingDetails['extended_duration_minutes'] = $extendedDurationMinutes > 0 ? $extendedDurationMinutes : null;

        return $bookingDetails;
    }

    private function calculateHourlyRateFromService($serviceName)
    {
        if (!$serviceName || !$serviceName->time_duration) {
            return null;
        }

        $duration = $this->parseDuration($serviceName->time_duration);
        if ($duration > 0) {
            return $serviceName->price / ($duration / 60);
        }

        return null;
    }

    private function reconstructBookingDetailsFromRequest(Request $request)
    {
        $branch = Branch::where('uuid', $request->input('branch_uuid'))->first();
        $serviceCategory = ServiceCategory::where('uuid', $request->input('service_category_uuid'))->first();
        $serviceName = ServiceName::where('uuid', $request->input('service_name_uuid'))->first();
        $seat = Seat::find($request->input('seat_id'));

        $additionalPrice = $request->input('additional_price', 0);
        $totalPrice = $request->input('total_price', 0);

        if ($additionalPrice == 0 && $request->input('additional_hours', 0) > 0) {
            $additionalPrice = $this->calculateAdditionalPrice(
                $request->input('additional_hours', 0),
                $request->input('additional_minutes', 0)
            );
            $totalPrice = ($serviceName ? $serviceName->price : 0) + $additionalPrice;
        }

        $bookingDetails = [
            'branch' => $branch,
            'service_category' => $serviceCategory,
            'service_name' => $serviceName,
            'seat' => $seat,
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'booking_time' => $request->input('booking_time'),
            'end_time' => $request->input('end_time'),
            'additional_hours' => $request->input('additional_hours', 0),
            'additional_minutes' => $request->input('additional_minutes', 0),
            'additional_price' => $additionalPrice,
            'total_price' => $totalPrice,
        ];

        $bookingDetails['branch'] = (object) ($bookingDetails['branch'] ? $bookingDetails['branch']->toArray() : []);
        $bookingDetails['service_category'] = (object) ($bookingDetails['service_category'] ? $bookingDetails['service_category']->toArray() : []);
        $bookingDetails['service_name'] = (object) ($bookingDetails['service_name'] ? $bookingDetails['service_name']->toArray() : []);

        return $bookingDetails;
    }

    private function calculateAdditionalPrice($hours, $minutes)
    {
        $hourlyRate = 100;
        $totalHours = $hours + ($minutes / 60);
        return $hourlyRate * $totalHours;
    }

    public function processPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'gcash_ref_no' => 'required|string|max:255',
                'gcash_receipt_img' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                'booking_details' => 'required|string',
                'payment_type' => 'required|in:full,service_only',
                'notes' => 'nullable|string|max:1000',
                'customer_reward_id' => 'nullable|exists:customer_rewards,id',
                'reward_discount_amount' => 'nullable|numeric|min:0',
                'reward_voucher_code' => 'nullable|string',
            ]);

            $decoded = base64_decode($validated['booking_details']);
            if ($decoded === false) {
                return back()->with('error', 'Invalid booking data format.')->withInput();
            }

            $bookingDetails = json_decode($decoded, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->with('error', 'Invalid booking data.')->withInput();
            }

            $customer = CustomerAccount::find(Auth::guard('customer')->id());

            if (!$customer) {
                return redirect()
                    ->route('showLoginForm')
                    ->with('error', 'Please login to complete your booking.');
            }

            DB::beginTransaction();
            $executionStage = 'starting_transaction';

            try {
                $executionStage = 'checking_duplicates';
                
                $branchId = $bookingDetails['branch']['id'] ?? null;
                $serviceNameId = $bookingDetails['service_name']['id'] ?? null;
                $serviceCategoryId = $bookingDetails['service_category']['id'] ?? null;
                $dateFrom = $bookingDetails['date_from'] ?? null;
                $dateTo = $bookingDetails['date_to'] ?? null;
                $bookingTime = $bookingDetails['booking_time'] ?? null;
                $endTime = $bookingDetails['end_time'] ?? null;
                $seatId = $bookingDetails['seat']['id'] ?? null;

                if (!$branchId || !$serviceNameId || !$dateFrom || !$bookingTime) {
                    DB::rollBack();
                    return back()
                        ->with('error', 'Missing required booking information. Please try again.')
                        ->withInput();
                }

                $existingBooking = Booking::where(function ($query) use (
                    $branchId, $serviceNameId, $dateFrom, $dateTo,
                    $bookingTime, $endTime, $seatId, $bookingDetails
                ) {
                    $query
                        ->where('branch_id', $branchId)
                        ->where('service_name_id', $serviceNameId)
                        ->where('date_start', $dateFrom)
                        ->where('date_end', $dateTo)
                        ->where('start_time', $bookingTime)
                        ->where('end_time', $endTime);

                    if ($seatId) {
                        $query->where('seat_id', $seatId);
                    }

                    if (isset($bookingDetails['extended_start_time']) && $bookingDetails['extended_start_time']) {
                        $query->where('extended_start_time', $bookingDetails['extended_start_time']);
                    }
                    if (isset($bookingDetails['extended_end_time']) && $bookingDetails['extended_end_time']) {
                        $query->where('extended_end_time', $bookingDetails['extended_end_time']);
                    }
                    if (isset($bookingDetails['extended_date_start']) && $bookingDetails['extended_date_start']) {
                        $query->where('extended_date_start', $bookingDetails['extended_date_start']);
                    }
                    if (isset($bookingDetails['extended_date_end']) && $bookingDetails['extended_date_end']) {
                        $query->where('extended_date_end', $bookingDetails['extended_date_end']);
                    }
                })
                    ->lockForUpdate()
                    ->orderBy('booking_date', 'asc')
                    ->orderBy('id', 'asc')
                    ->first();

                if ($existingBooking) {
                    DB::rollBack();

                    if ($existingBooking->customer_account_id == $customer->id) {
                        return back()
                            ->with('error', 'You already have a booking with identical details. Please check your bookings list.')
                            ->withInput();
                    } else {
                        return back()
                            ->with('error', 'This time slot is no longer available. Please try a different time.')
                            ->withInput();
                    }
                }

                $currentMicroTime = microtime(true);
                $executionStage = 'checking_seat_availability';
                
                if (isset($bookingDetails['seat']) && $bookingDetails['seat']) {
                    $seatId = is_object($bookingDetails['seat'])
                        ? $bookingDetails['seat']->id
                        : $bookingDetails['seat']['id'];

                    $seatConflict = Booking::where('seat_id', $seatId)
                        ->where('booking_status', '!=', 0)
                        ->where(function ($query) use ($bookingDetails) {
                            $query->where(function ($q) use ($bookingDetails) {
                                $q
                                    ->where('date_start', '<=', $bookingDetails['date_to'])
                                    ->where('date_end', '>=', $bookingDetails['date_from'])
                                    ->where('start_time', '<', $bookingDetails['end_time'])
                                    ->where('end_time', '>', $bookingDetails['booking_time']);
                            })->orWhere(function ($q) use ($bookingDetails) {
                                if (isset($bookingDetails['extended_start_time']) &&
                                        isset($bookingDetails['extended_end_time'])) {
                                    $q
                                        ->where('extended_date_start', '<=', $bookingDetails['extended_date_end'] ?? $bookingDetails['date_to'])
                                        ->where('extended_date_end', '>=', $bookingDetails['extended_date_start'] ?? $bookingDetails['date_from'])
                                        ->where('extended_start_time', '<', $bookingDetails['extended_end_time'])
                                        ->where('extended_end_time', '>', $bookingDetails['extended_start_time']);
                                }
                            });
                        })
                        ->lockForUpdate()
                        ->exists();

                    if ($seatConflict) {
                        DB::rollBack();
                        return back()
                            ->with('error', 'The selected seat/room is no longer available for the chosen time slot.')
                            ->withInput();
                    }
                }

                $executionStage = 'uploading_receipt';
                $receiptImagePath = null;
                if ($request->hasFile('gcash_receipt_img')) {
                    $receiptImagePath = $request->file('gcash_receipt_img')->store('gcash-receipts', 'public');
                }

                $executionStage = 'creating_booking_record';
                
                $totalPrice = $bookingDetails['total_price'] ?? 0;
                $rewardDiscount = $validated['reward_discount_amount'] ?? 0;
                $finalTotal = max(0, $totalPrice - $rewardDiscount);
                $bookingDetails['total_price'] = $finalTotal;
                
                $booking = $this->createBooking($customer, $bookingDetails, $currentMicroTime);

                // ================================================================
                // HANDLE REWARD REDEMPTION - Create redemption record
                // ================================================================
                if (!empty($validated['customer_reward_id'])) {
                    // Use lockForUpdate to prevent race conditions
                    $customerReward = CustomerReward::where('id', $validated['customer_reward_id'])
                        ->where('customer_account_id', $customer->id)
                        ->where('redemption_status', CustomerReward::REDEMPTION_STATUS_READY)
                        ->lockForUpdate()
                        ->first();
                        
                    if ($customerReward) {
                        // Verify the reward hasn't expired
                        if ($customerReward->expiration_date && $customerReward->expiration_date < now()) {
                            DB::rollBack();
                            return back()
                                ->with('error', 'This reward has expired and cannot be used.')
                                ->withInput();
                        }
                        
                        // Create redemption record
                        $discountAmount = $validated['reward_discount_amount'] ?? 0;
                        $redemption = $this->createRewardRedemption(
                            $customerReward->id,
                            $booking->id,
                            $branchId,
                            $serviceNameId,
                            $serviceCategoryId,
                            $discountAmount,
                            $totalPrice,
                            $finalTotal
                        );
                        
                        if (!$redemption) {
                            DB::rollBack();
                            return back()
                                ->with('error', 'Failed to process reward redemption. Please try again.')
                                ->withInput();
                        }
                        
                        \Log::info('Reward redeemed during booking', [
                            'reward_id' => $customerReward->id,
                            'customer_id' => $customer->id,
                            'booking_id' => $booking->id,
                            'voucher_code' => $customerReward->voucher_code
                        ]);
                    } else {
                        // If reward not found or not in ready status, log but don't fail the booking
                        \Log::warning('Reward could not be redeemed', [
                            'reward_id' => $validated['customer_reward_id'],
                            'customer_id' => $customer->id,
                            'reason' => 'Reward not found or not in ready status'
                        ]);
                    }
                }

                $executionStage = 'processing_notes_data';
                $notesArray = [];
                if (!empty($validated['notes'])) {
                    $notesArray = [
                        [
                            'content' => trim($validated['notes']),
                            'added_by_type' => 'Customer',
                            'added_at' => now()->toDateTimeString(),
                        ]
                    ];
                }
                
                // Add reward info to notes if applied
                if (!empty($validated['customer_reward_id']) && !empty($validated['reward_voucher_code'])) {
                    $notesArray[] = [
                        'content' => 'Reward applied: ' . $validated['reward_voucher_code'] . ' (Discount: ₱' . number_format($validated['reward_discount_amount'] ?? 0, 2) . ')',
                        'added_by_type' => 'System',
                        'added_at' => now()->toDateTimeString(),
                    ];
                }

                $executionStage = 'processing_payment_records';
                if ($validated['payment_type'] === 'full') {
                    $this->createBookingPayment($customer, $booking, $bookingDetails,
                        $validated['gcash_ref_no'], $receiptImagePath, 1,
                        $finalTotal, 1, $notesArray);

                    if (isset($bookingDetails['additional_price']) && $bookingDetails['additional_price'] > 0) {
                        $this->createBookingPayment($customer, $booking, $bookingDetails,
                            $validated['gcash_ref_no'], $receiptImagePath, 0,
                            $bookingDetails['additional_price'], 1);
                    }
                } else {
                    // Service only payment - apply reward discount to service price only
                    $servicePrice = $bookingDetails['service_name']['price'] ?? 0;
                    $serviceFinalPrice = max(0, $servicePrice - $rewardDiscount);

                    $this->createBookingPayment($customer, $booking, $bookingDetails,
                        $validated['gcash_ref_no'], $receiptImagePath, 1,
                        $serviceFinalPrice, 1, $notesArray);

                    if (isset($bookingDetails['additional_price']) && $bookingDetails['additional_price'] > 0) {
                        $this->createBookingPayment($customer, $booking, $bookingDetails,
                            null, null, 0, $bookingDetails['additional_price'], 2);
                    }
                }

                DB::commit();

                $executionStage = 'sending_notifications';
                $this->sendBookingNotifications($booking, $bookingDetails, $customer);

                session()->put('last_booking_uuid', $booking->uuid);
                session()->put('last_booking_ref_no', $booking->booking_ref_no);

                $request->session()->forget('booking_details');

                return redirect()
                    ->route('sub_three.home.booking.confirmation', ['booking_uuid' => $booking->uuid])
                    ->with([
                        'success' => 'Payment submitted successfully! Your booking is pending verification.',
                        'booking_ref_no' => $booking->booking_ref_no
                    ]);
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Payment processing error at stage ' . $executionStage . ': ' . $e->getMessage(), [
                    'exception' => $e,
                    'customer_id' => Auth::guard('customer')->id(),
                    'booking_details' => $bookingDetails,
                    'stage' => $executionStage
                ]);
                throw new \Exception("Failed at stage '$executionStage': " . $e->getMessage());
            }
        } catch (\Exception $e) {
            \Log::error('Outer Payment Error: ' . $e->getMessage());
            return back()
                ->with('error', 'Process Payment Failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    private function sendBookingNotifications($booking, $bookingDetails, $customer)
    {
        try {
            $branchId = is_object($bookingDetails['branch']) ? $bookingDetails['branch']->id : $bookingDetails['branch']['id'];
            $branch = Branch::find($branchId);

            $actor = Auth::guard('customer')->user();

            if ($branch && $branch->owner_account_id) {
                $owner = \App\Models\OwnerAccount::find($branch->owner_account_id);
                if ($owner) {
                    Notification::send($owner, new BookingProcessStaffNotification(
                        $booking,
                        $branch,
                        $customer,
                        $actor,
                        'created'
                    ));
                }
            }

            $staffs = StaffAccount::where('branch_id', $branchId)->where('active', 1)->get();
            if ($staffs->count() > 0) {
                Notification::send($staffs, new BookingProcessStaffNotification(
                    $booking,
                    $branch,
                    $customer,
                    $actor,
                    'created'
                ));
            }

            Notification::send($customer, new BookingProcessNotification(
                $booking,
                $branch,
                $customer,
                $actor,
                'created'
            ));
        } catch (\Exception $e) {
            \Log::error('Notification Error: ' . $e->getMessage());
        }
    }

    private function createBooking($customer, $bookingDetails, $priorityTimestamp = null)
    {
        $bookingRefNo = 'BRN' . Carbon::now()->format('Ymd') . Str::upper(Str::random(4));

        $timeDuration = '';
        if (isset($bookingDetails['service_name'])) {
            if (is_object($bookingDetails['service_name'])) {
                $timeDuration = $bookingDetails['service_name']->time_duration ?? '';
            } elseif (is_array($bookingDetails['service_name'])) {
                $timeDuration = $bookingDetails['service_name']['time_duration'] ?? '';
            }
        }

        $bookingData = [
            'booking_ref_no' => $bookingRefNo,
            'customer_account_id' => $customer->id,
            'branch_id' => is_object($bookingDetails['branch']) ? $bookingDetails['branch']->id : $bookingDetails['branch']['id'],
            'service_category_id' => is_object($bookingDetails['service_category']) ? $bookingDetails['service_category']->id : $bookingDetails['service_category']['id'],
            'service_name_id' => is_object($bookingDetails['service_name']) ? $bookingDetails['service_name']->id : $bookingDetails['service_name']['id'],
            'seat_id' => isset($bookingDetails['seat']) ? (is_object($bookingDetails['seat']) ? $bookingDetails['seat']->id : $bookingDetails['seat']['id']) : null,
            'date_start' => $bookingDetails['date_from'],
            'date_end' => $bookingDetails['date_to'],
            'start_time' => $bookingDetails['booking_time'],
            'end_time' => $bookingDetails['end_time'],
            'time_duration' => $timeDuration,
            'booking_type' => 1,
            'booking_date' => Carbon::now(),
            'booking_status' => 2,
            'active' => 1,
            'priority_timestamp' => $priorityTimestamp ?? microtime(true),
        ];

        if (isset($bookingDetails['additional_hours'])) {
            $bookingData['additional_hours'] = $bookingDetails['additional_hours'];
            $bookingData['additional_price'] = $bookingDetails['additional_price'] ?? 0;
        }

        $bookingData['extended_start_time'] = $bookingDetails['extended_start_time'] ?? null;
        $bookingData['extended_end_time'] = $bookingDetails['extended_end_time'] ?? null;
        $bookingData['extended_date_start'] = $bookingDetails['extended_date_start'] ?? null;
        $bookingData['extended_date_end'] = $bookingDetails['extended_date_end'] ?? null;
        $bookingData['extended_duration_minutes'] = $bookingDetails['extended_duration_minutes'] ?? null;

        $booking = Booking::create($bookingData);

        return $booking;
    }

    public function checkBookingAvailability(Request $request)
    {
        try {
            $validated = $request->validate([
                'booking_details' => 'required|string',
            ]);

            $decoded = base64_decode($validated['booking_details']);
            if ($decoded === false) {
                return response()->json([
                    'available' => false,
                    'message' => 'Invalid booking data'
                ]);
            }

            $bookingDetails = json_decode($decoded, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'available' => false,
                    'message' => 'Invalid booking data'
                ]);
            }

            $existingBooking = Booking::where(function ($query) use ($bookingDetails) {
                $query
                    ->where('branch_id', is_object($bookingDetails['branch'])
                        ? $bookingDetails['branch']->id
                        : $bookingDetails['branch']['id'])
                    ->where('service_name_id', is_object($bookingDetails['service_name'])
                        ? $bookingDetails['service_name']->id
                        : $bookingDetails['service_name']['id'])
                    ->where('date_start', $bookingDetails['date_from'])
                    ->where('date_end', $bookingDetails['date_to'])
                    ->where('start_time', $bookingDetails['booking_time'])
                    ->where('end_time', $bookingDetails['end_time'])
                    ->where('booking_status', '!=', 0);

                if (isset($bookingDetails['seat']) && $bookingDetails['seat']) {
                    $query->where('seat_id', is_object($bookingDetails['seat'])
                        ? $bookingDetails['seat']->id
                        : $bookingDetails['seat']['id']);
                }

                if (isset($bookingDetails['extended_start_time'])) {
                    $query->where('extended_start_time', $bookingDetails['extended_start_time']);
                }
                if (isset($bookingDetails['extended_end_time'])) {
                    $query->where('extended_end_time', $bookingDetails['extended_end_time']);
                }
            })->exists();

            if ($existingBooking) {
                return response()->json([
                    'available' => false,
                    'message' => 'This booking slot is no longer available'
                ]);
            }

            if (isset($bookingDetails['seat']) && $bookingDetails['seat']) {
                $seatId = is_object($bookingDetails['seat'])
                    ? $bookingDetails['seat']->id
                    : $bookingDetails['seat']['id'];

                $seatConflict = Booking::where('seat_id', $seatId)
                    ->where('booking_status', '!=', 0)
                    ->where(function ($query) use ($bookingDetails) {
                        $query->where(function ($q) use ($bookingDetails) {
                            $q
                                ->where('date_start', '<=', $bookingDetails['date_to'])
                                ->where('date_end', '>=', $bookingDetails['date_from'])
                                ->where('start_time', '<', $bookingDetails['end_time'])
                                ->where('end_time', '>', $bookingDetails['booking_time']);
                        });

                        if (isset($bookingDetails['extended_start_time']) &&
                                isset($bookingDetails['extended_end_time'])) {
                            $query->orWhere(function ($q) use ($bookingDetails) {
                                $q
                                    ->where('extended_date_start', '<=', $bookingDetails['extended_date_end'] ?? $bookingDetails['date_to'])
                                    ->where('extended_date_end', '>=', $bookingDetails['extended_date_start'] ?? $bookingDetails['date_from'])
                                    ->where('extended_start_time', '<', $bookingDetails['extended_end_time'])
                                    ->where('extended_end_time', '>', $bookingDetails['extended_start_time']);
                            });
                        }
                    })
                    ->exists();

                if ($seatConflict) {
                    return response()->json([
                        'available' => false,
                        'message' => 'The selected seat/room is no longer available'
                    ]);
                }
            }

            return response()->json([
                'available' => true,
                'message' => 'Booking slot is available'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'available' => false,
                'message' => 'Error checking availability'
            ], 500);
        }
    }

    private function createBookingPayment($customer, $booking, $bookingDetails, $gcashRefNo, $receiptImagePath,
        $paymentCategory, $amount, $paymentStatus, $notes = null)
    {
        $paymentMethod = 1;

        if ($paymentCategory === 0 && $paymentStatus === 2) {
            $paymentMethod = 3;
        }

        $finalGcashRefNo = ($paymentStatus === 1) ? $gcashRefNo : null;
        $finalReceiptImagePath = ($paymentStatus === 1) ? $receiptImagePath : null;

        $paymentData = [
            'customer_account_id' => $customer->id,
            'branch_id' => is_object($bookingDetails['branch']) ? $bookingDetails['branch']->id : $bookingDetails['branch']['id'],
            'booking_id' => $booking->id,
            'payment_date' => Carbon::now(),
            'payment_category' => $paymentCategory,
            'payment_method' => $paymentMethod,
            'gcash_ref_no' => $finalGcashRefNo,
            'gcash_receipt_img' => $finalReceiptImagePath,
            'total_amount' => $amount,
            'amount_paid' => $paymentStatus === 1 ? $amount : 0,
            'payment_status' => $paymentStatus,
            'active' => 1,
            'date_created' => Carbon::now(),
            'created_by' => $customer->id,
            'created_by_type' => 'customer',
        ];

        if (!empty($notes)) {
            if (is_array($notes)) {
                $paymentData['notes'] = $notes;
            } else {
                $paymentData['notes'] = [
                    [
                        'content' => $notes,
                        'added_at' => now()->toDateTimeString(),
                    ]
                ];
            }
        }

        $bookingPayment = BookingPayment::create($paymentData);

        return $bookingPayment;
    }

    public function showConfirmation($booking_uuid = null)
    {
        if (!$booking_uuid) {
            $booking_uuid = session()->get('last_booking_uuid');
        }

        if (!$booking_uuid) {
            $booking_uuid = session()->get('last_booking_uuid');
            if ($booking_uuid) {
                $booking = Booking::find($booking_uuid);
                if ($booking && $booking->uuid) {
                    $booking_uuid = $booking->uuid;
                }
            }
        }

        if (!$booking_uuid) {
            return view('customer.home.booking-confirmation', [
                'booking' => null,
                'error' => 'No booking found. Please start a new booking.'
            ]);
        }

        $booking = Booking::with([
            'branch',
            'serviceCategory',
            'serviceName',
            'seat',
            'payment' => function ($query) {
                $query
                    ->where('active', 1)
                    ->orderBy('date_created', 'desc');
            },
            'customerAccount' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'email', 'contact_no');
            }
        ])->where('uuid', $booking_uuid)->first();

        if (!$booking) {
            return view('customer.home.booking-confirmation', [
                'booking' => null,
                'error' => 'Booking not found.'
            ]);
        }

        return view('customer.home.booking-confirmation', [
            'booking' => $booking,
        ]);
    }
}