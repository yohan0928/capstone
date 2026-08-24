<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\CustomerAccount;
use App\Models\CustomerCheckin;
use App\Models\CustomerReward;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\LoyaltyTier;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\OwnerAccount;
use App\Models\Product;
use App\Models\RewardRedemption;
use App\Models\StaffAccount;
use App\Notifications\Owner\POSNotification;
use App\Notifications\Owner\ProductNotification;
use App\Notifications\Staff\POSStaffNotification;
use App\Notifications\Staff\ProductStaffNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Throwable;

class PointOfSaleController extends Controller
{
    // Status helper methods
    protected function getOrderStatusText($status)
    {
        return match ($status) {
            1       => 'Ordered',
            default => 'Unknown'
        };
    }

    protected function getOrderItemStatusText($status)
    {
        return match ($status) {
            1       => 'Bought',
            default => 'Unknown'
        };
    }

    protected function getPaymentMethodText($method)
    {
        return match ($method) {
            0       => 'Cash',
            1       => 'GCash',
            2       => 'Debit Card',
            3       => 'Pay Later',
            default => 'Unknown'
        };
    }

    protected function getPaymentStatusText($status)
    {
        return match ($status) {
            0       => 'Unpaid',
            1       => 'Paid',
            default => 'Unknown'
        };
    }

    /**
     * Get reward type label for display
     */
    protected function getRewardTypeLabel($rewardType)
    {
        $labels = [
            'free_service' => 'Free Service',
            'free_product' => 'Free Product',
            'percentage_discount' => 'Percentage Discount',
            'fixed_discount' => 'Fixed Discount',
            'product_discount' => 'Product Discount',
            'reward' => 'Reward Discount',
            'global' => 'Global Discount',
            'item_level' => 'Item-Level Discount',
            'none' => 'None'
        ];
        return $labels[$rewardType] ?? 'Unknown';
    }

    /**
     * Get reward type badge class for display
     */
    protected function getRewardTypeBadgeClass($rewardType)
    {
        $classes = [
            'free_service' => 'bg-pink-100 text-pink-800',
            'free_product' => 'bg-indigo-100 text-indigo-800',
            'percentage_discount' => 'bg-orange-100 text-orange-800',
            'fixed_discount' => 'bg-blue-100 text-blue-800',
            'product_discount' => 'bg-teal-100 text-teal-800',
            'reward' => 'bg-green-100 text-green-800',
            'global' => 'bg-purple-100 text-purple-800',
            'item_level' => 'bg-blue-100 text-blue-800',
            'none' => 'bg-gray-100 text-gray-500'
        ];
        return $classes[$rewardType] ?? 'bg-gray-100 text-gray-500';
    }

    // Generate order reference number
    protected function generateOrderReference()
    {
        $today     = date('Ymd');
        $lastOrder = Order::where('order_ref_no', 'like', "ORD-{$today}-%")
            ->orderBy('order_ref_no', 'desc')
            ->first();

        $nextNumber = $lastOrder
            ? ((int) substr($lastOrder->order_ref_no, -4)) + 1
            : 1;

        return 'ORD-' . $today . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    // Show POS Interface
    public function index(Request $request)
    {
        $owner   = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        $prefilledCustomer = null;
        $bookingReference  = null;
        $checkinBranchId   = null;
        $customerRewards   = [];

        if ($request->has('chk') || $request->has('checkin_uuid')) {
            $checkinUuid = $request->get('chk') ?? $request->get('checkin_uuid');

            $checkin = CustomerCheckin::with([
                'customerAccount' => fn($q) => $q->select('id', 'uuid', 'first_name', 'last_name', 'email', 'contact_no'),
                'booking'         => fn($q) => $q->select('id', 'uuid', 'booking_ref_no', 'branch_id'),
                'branch'          => fn($q) => $q->select('id', 'uuid', 'branch_name'),
            ])
                ->where('uuid', $checkinUuid)
                ->where('active', 1)
                ->first();

            if ($checkin?->customerAccount) {
                $prefilledCustomer = $checkin->customerAccount;
                $checkinBranchId   = $checkin->branch_id;

                $bookingReference = [
                    'booking_uuid'   => $checkin->booking->uuid        ?? $request->get('bkg'),
                    'booking_id'     => $checkin->booking->id          ?? $request->get('booking_id'),
                    'booking_ref_no' => $checkin->booking->booking_ref_no ?? $request->get('ref'),
                    'checkin_uuid'   => $checkin->uuid                 ?? $request->get('chk'),
                    'checkin_id'     => $checkin->id                   ?? $request->get('checkin_id'),
                    'branch_uuid'    => $checkin->branch->uuid         ?? $request->get('brn'),
                    'branch_id'      => $checkinBranchId,
                    'customer_uuid'  => $checkin->customerAccount->uuid ?? $request->get('cust'),
                ];

                session(['pos_booking_reference' => $bookingReference]);

                // Load customer rewards
                $customerRewards = $this->getCustomerAvailableRewards($checkin->customerAccount->id, $checkinBranchId);
            }
        }

        // If customer is passed directly via URL parameter
        if ($request->has('cust') && !$prefilledCustomer) {
            $customerUuid = $request->get('cust');
            $customer = CustomerAccount::where('uuid', $customerUuid)->where('active', 1)->first();
            if ($customer) {
                $prefilledCustomer = $customer;
                
                // Load customer rewards
                $branchId = $request->get('brn') ? Branch::where('uuid', $request->get('brn'))->first()?->id : null;
                if ($branchId) {
                    $customerRewards = $this->getCustomerAvailableRewards($customer->id, $branchId);
                }
            }
        }

        $branches = Branch::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->get(['id', 'uuid', 'branch_name']);

        $productsBranchId = $checkinBranchId ?? ($branches->first()->id ?? null);

        $products = Product::with([
            'productIngredients.ingredient' => fn($q) => $q->select(
                'id', 'ingredient_name', 'stock_quantity_in',
                'stock_quantity_threshold', 'unit', 'unit_conversion',
                'converted_stock_quantity_in', 'converted_unit'
            ),
        ])
            ->where('owner_account_id', $ownerId)
            ->where('branch_id', $productsBranchId)
            ->where('product_status', 1)
            ->where('active', 1)
            ->get();

        return view('owner.pos.index', compact(
            'products', 
            'branches', 
            'prefilledCustomer', 
            'bookingReference',
            'customerRewards'
        ));
    }

    /**
     * Get customer's available rewards for POS
     */
    public function getCustomerRewards(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_id' => 'required|exists:customer_accounts,id',
                'branch_id' => 'required|exists:branches,id'
            ]);

            $rewards = $this->getCustomerAvailableRewards(
                $validated['customer_id'],
                $validated['branch_id']
            );

            return response()->json([
                'success' => true,
                'rewards' => $rewards
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading customer rewards for POS: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading rewards: ' . $e->getMessage(),
                'rewards' => []
            ], 500);
        }
    }

    /**
     * Get customer's available rewards
     */
    private function getCustomerAvailableRewards($customerId, $branchId)
    {
        $customerRewards = CustomerReward::where('customer_account_id', $customerId)
            ->where('active', 1)
            ->where('claim_status', CustomerReward::CLAIM_STATUS_CLAIMED)
            ->where('redemption_status', CustomerReward::REDEMPTION_STATUS_READY)
            ->where(function($query) {
                $query->whereNull('expiration_date')
                      ->orWhere('expiration_date', '>', now());
            });

        // Filter by branch
        if ($branchId) {
            $customerRewards->where(function($query) use ($branchId) {
                $query->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
            });
        }

        $rewards = $customerRewards
            ->with(['loyaltyTier', 'loyaltyTier.redeemableItem', 'loyaltyTier.redeemableItem.targetProduct'])
            ->get();

        $formattedRewards = [];

        foreach ($rewards as $reward) {
            $loyaltyTier = $reward->loyaltyTier;
            if (!$loyaltyTier) continue;

            $redeemableItem = $loyaltyTier->redeemableItem;
            if (!$redeemableItem) continue;

            // Check reward type - include product rewards
            $allowedTypes = ['free_service', 'percentage_discount', 'fixed_discount', 'product_discount', 'free_product'];
            if (!in_array($redeemableItem->reward_type, $allowedTypes)) continue;

            // Use the target_details accessor from RedeemableItem
            $targetDetails = $redeemableItem->target_details;
            
            // Get product info if applicable
            $productId = null;
            $productName = null;
            $productData = null;
            
            $discountValue = $redeemableItem->monetary_value ?? 0;
            $isPercentage = $redeemableItem->reward_type === 'percentage_discount';
            $percentage = $redeemableItem->discount_percentage ?? 0;

            if ($targetDetails && $targetDetails['type'] === 'product') {
                $productId = $targetDetails['id'] ?? null;
                $productName = $targetDetails['name'] ?? null;
                $discountValue = $targetDetails['price'] ?? $discountValue;
                
                $productData = [
                    'id' => $productId,
                    'name' => $productName,
                    'price' => $targetDetails['price'] ?? 0,
                    'quantity' => 1,
                    'product_ingredients' => []
                ];

                if (!empty($targetDetails['has_ingredients']) && !empty($targetDetails['ingredients'])) {
                    $productData['product_ingredients'] = collect($targetDetails['ingredients'])->map(function($ing) {
                        return (object) [
                            'ingredient' => (object) [
                                'id' => $ing['ingredient_id'],
                                'ingredient_name' => $ing['ingredient_name'] ?? 'Unknown'
                            ],
                            'quantity_needed' => $ing['quantity_needed'],
                            'unit' => $ing['unit']
                        ];
                    })->toArray();
                }
            } elseif ($targetDetails && $targetDetails['type'] === 'service') {
                $discountValue = $targetDetails['price'] ?? $discountValue;
            }

            $formattedRewards[] = [
                'id' => $reward->id,
                'voucher_code' => $reward->voucher_code ?? 'N/A',
                'description' => $loyaltyTier->reward_description ?? $loyaltyTier->tier_name ?? 'Reward',
                'item_name' => $redeemableItem->value_display,
                'reward_type' => $redeemableItem->reward_type,
                'reward_type_label' => $redeemableItem->type_label,
                'discount_value' => $discountValue,
                'is_percentage' => $isPercentage,
                'percentage' => $percentage,
                'discount_display' => $isPercentage 
                    ? $percentage . '% OFF' 
                    : ($discountValue > 0 ? '₱' . number_format($discountValue, 2) : 'Free'),
                'expiration_date' => $reward->expiration_date ? $reward->expiration_date->format('M d, Y') : 'No expiry',
                'days_left' => $reward->days_left,
                'loyalty_tier_id' => $loyaltyTier->id,
                'monetary_value' => $loyaltyTier->monetary_value ?? 0,
                'discount_percentage' => $loyaltyTier->discount_percentage ?? 0,
                'branch_id' => $reward->branch_id,
                'product_id' => $productId,
                'product_name' => $productName,
                'product_data' => $productData,
                'target_product_id' => $redeemableItem->target_product_id,
            ];
        }

        return $formattedRewards;
    }

    /**
     * Apply a reward in POS
     */
    public function applyReward(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_reward_id' => 'required|exists:customer_rewards,id',
                'total_amount' => 'required|numeric|min:0'
            ]);

            $customerReward = CustomerReward::with([
                'loyaltyTier', 
                'loyaltyTier.redeemableItem',
                'loyaltyTier.redeemableItem.targetProduct'
            ])->findOrFail($validated['customer_reward_id']);

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
            $productData = null;

            if ($redeemableItem) {
                $rewardType = $redeemableItem->reward_type;
                $targetDetails = $redeemableItem->target_details;
                
                // Check if this is a product reward using the model's target details
                if ($targetDetails && $targetDetails['type'] === 'product') {
                    $productData = [
                        'id' => $targetDetails['id'],
                        'name' => $targetDetails['name'],
                        'price' => $targetDetails['price'] ?? 0,
                        'quantity' => 1,
                        'product_ingredients' => $targetDetails['has_ingredients'] ? $targetDetails['ingredients'] : []
                    ];
                }
                
                switch ($rewardType) {
                    case 'free_product':
                        $discountValue = $targetDetails['price'] ?? ($redeemableItem->monetary_value ?? 0);
                        break;
                        
                    case 'product_discount':
                        if ($redeemableItem->discount_percentage > 0) {
                            $isPercentage = true;
                            $percentage = $redeemableItem->discount_percentage;
                            $targetPrice = $targetDetails['price'] ?? $totalAmount;
                            $discountValue = ($targetPrice * $percentage) / 100;
                        } else {
                            $discountValue = $redeemableItem->monetary_value ?? 0;
                        }
                        break;
                        
                    case 'free_service':
                        $discountValue = $targetDetails['price'] ?? ($redeemableItem->monetary_value ?? 0);
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

            if ($rewardType !== 'free_product' && $rewardType !== 'product_discount') {
                $discountValue = min($discountValue, $totalAmount);
            }
            $newTotal = max(0, $totalAmount - $discountValue);

            return response()->json([
                'success' => true,
                'discount_value' => $discountValue,
                'new_total' => $newTotal,
                'is_percentage' => $isPercentage,
                'percentage' => $percentage,
                'reward_type' => $rewardType,
                'reward_type_label' => $redeemableItem->type_label ?? null,
                'customer_reward_id' => $validated['customer_reward_id'],
                'product_data' => $productData,
                'message' => 'Reward applied successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error applying reward in POS: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error applying reward: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark reward as redeemed with redemption history
     * UPDATED with audit fields
     */
    private function markRewardAsRedeemed($customerRewardId, $branchId, $orderId, $items = [])
    {
        try {
            if (!$customerRewardId) return;

            $customerReward = CustomerReward::with(['loyaltyTier', 'loyaltyTier.redeemableItem'])
                ->find($customerRewardId);
                
            if (!$customerReward) return;

            // Get the reward details for redemption history
            $loyaltyTier = $customerReward->loyaltyTier;
            $redeemableItem = $loyaltyTier ? $loyaltyTier->redeemableItem : null;
            
            // Get the total amount from the order items or calculate from customer reward
            $totalAmount = 0;
            $discountAmount = 0;
            
            // Try to find the reward discount in the items or use the stored value
            if (!empty($items)) {
                // Calculate total from order items
                foreach ($items as $item) {
                    $totalAmount += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
                }
            }
            
            // Get the discount amount from the customer reward (monetary_value)
            if ($redeemableItem) {
                $rewardType = $redeemableItem->reward_type;
                
                switch ($rewardType) {
                    case 'free_product':
                    case 'product_discount':
                        $targetDetails = $redeemableItem->target_details;
                        $productPrice = $targetDetails['price'] ?? 0;
                        if ($rewardType === 'free_product') {
                            $discountAmount = $productPrice;
                        } else {
                            $discountAmount = $redeemableItem->discount_percentage 
                                ? ($productPrice * $redeemableItem->discount_percentage / 100) 
                                : ($redeemableItem->monetary_value ?? 0);
                        }
                        break;
                    case 'free_service':
                        $targetDetails = $redeemableItem->target_details;
                        $discountAmount = $targetDetails['price'] ?? ($redeemableItem->monetary_value ?? 0);
                        break;
                    case 'percentage_discount':
                        $discountAmount = ($totalAmount * $redeemableItem->discount_percentage) / 100;
                        break;
                    case 'fixed_discount':
                        $discountAmount = $redeemableItem->monetary_value ?? 0;
                        break;
                    default:
                        $discountAmount = $redeemableItem->monetary_value ?? 0;
                }
            }
            
            // Ensure discount doesn't exceed total
            $discountAmount = min($discountAmount, $totalAmount);
            $finalAmount = max(0, $totalAmount - $discountAmount);
            
            // Get original amount (total before discount)
            $originalAmount = $totalAmount;
            
            // If we couldn't get the total from items, use a default
            if ($totalAmount == 0) {
                $totalAmount = $discountAmount;
                $originalAmount = $discountAmount;
            }
            
            // Get the authenticated owner user
            $owner = Auth::guard('owner')->user();
            
            // Create reward redemption record with audit fields
            $redemptionData = [
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'customer_reward_id' => $customerReward->id,
                'customer_account_id' => $customerReward->customer_account_id,
                'order_id' => $orderId,
                'reward_type' => $redeemableItem ? $redeemableItem->reward_type : null,
                'target_type' => 'product',
                'discount_value' => $redeemableItem ? (
                    $redeemableItem->reward_type === 'percentage_discount' 
                        ? $redeemableItem->discount_percentage 
                        : $redeemableItem->monetary_value
                ) : null,
                'discount_amount' => $discountAmount,
                'original_amount' => $originalAmount,
                'final_amount' => $finalAmount,
                'receipt_number' => 'POS-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6)),
                'redeemed_by' => $owner->id,
                'redeemed_by_type' => 'OwnerAccount',
                'branch_id' => $branchId,
                'notes' => 'Redeemed during order',
                'redeemed_at' => now(),
                'date_created' => now(),
                'active' => 1,
                // Audit fields - using lowercase 'owner' to match Booking model pattern
                'created_by' => $owner->id,
                'created_by_type' => 'owner',
                'date_created' => now(),
            ];
            
            // Check if product_id should be set
            if ($redeemableItem && $redeemableItem->target_product_id) {
                $redemptionData['product_id'] = $redeemableItem->target_product_id;
            }
            
            $redemption = RewardRedemption::create($redemptionData);
            
            // Update customer reward status
            $customerReward->redemption_status = CustomerReward::REDEMPTION_STATUS_REDEEMED;
            $customerReward->redeemed_at = now();
            $customerReward->redeemed_at_branch_id = $branchId;
            $customerReward->date_updated = now();
            $customerReward->save();
            
            Log::info('Reward redeemed via POS', [
                'customer_reward_id' => $customerReward->id,
                'order_id' => $orderId,
                'voucher_code' => $customerReward->voucher_code,
                'discount_amount' => $discountAmount,
                'created_by' => $owner->id,
                'created_by_type' => 'owner'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error marking reward as redeemed: ' . $e->getMessage());
        }
    }

    // Get customers for POS
    public function getCustomers()
    {
        $customers = CustomerAccount::where('active', 1)
            ->get(['id', 'first_name', 'last_name', 'email', 'contact_no as contact'])
            ->map(fn($c) => [
                'id'      => $c->id,
                'name'    => $c->first_name . ' ' . $c->last_name,
                'email'   => $c->email,
                'contact' => $c->contact,
            ]);

        return response()->json($customers);
    }

    public function storeCustomer(Request $request)
    {
        return response()->json(['message' => 'Customer creation is now handled during order processing.'], 400);
    }

    // Change Branch
    public function changeBranch(Request $request)
    {
        $request->validate(['branch_id' => 'required|exists:branches,id']);

        try {
            /** @var \Illuminate\Database\Eloquent\Model $owner */
            $owner            = Auth::guard('owner')->user();
            $owner->branch_id = $request->branch_id;
            $owner->save();

            return response()->json(['success' => true, 'message' => 'Branch changed successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to change branch: ' . $e->getMessage()], 422);
        }
    }

    // Search Product
    public function searchProduct(Request $request)
    {
        $owner    = Auth::guard('owner')->user();
        $ownerId  = $owner->id;
        $search   = $request->get('search');
        $branchId = $request->get('branch_id', $owner->branch_id);

        $products = Product::with([
            'productIngredients.ingredient' => fn($q) => $q->select(
                'id', 'ingredient_name', 'stock_quantity_in',
                'stock_quantity_threshold', 'unit', 'unit_conversion',
                'converted_stock_quantity_in', 'converted_unit'
            ),
        ])
            ->where('owner_account_id', $ownerId)
            ->where('branch_id', $branchId)
            ->where('product_status', 1)
            ->where('active', 1)
            ->where(function ($q) use ($search) {
                if ($search) {
                    $q->where('product_name', 'LIKE', "%{$search}%")
                      ->orWhere('product_batch_no', 'LIKE', "%{$search}%");
                }
            })
            ->get();

        return response()->json($products);
    }

    /**
     * PROCESS ORDER - COMPLETE METHOD WITH REDEMPTION HISTORY
     */
    public function processOrder(Request $request)
    {
        $isAjax = $request->header('X-Requested-With') === 'XMLHttpRequest' || $request->wantsJson();

        if ($request->has('order_data')) {
            $orderData = json_decode($request->input('order_data'), true);
            $request->merge($orderData);
        }

        $validator = Validator::make($request->all(), [
            'branch_id'                  => 'required|exists:branches,id',
            'items'                      => 'required|array|min:1',
            'items.*.product_id'         => 'required|exists:products,id',
            'items.*.quantity'           => 'required|integer|min:1',
            'items.*.price'              => 'required|numeric|min:0',
            'items.*.discount'           => 'nullable|numeric|min:0|max:999999.99',
            'items.*.discount_type'      => 'nullable|string|in:amount,percentage',
            'payment_method'             => 'required|string',
            'amount_paid'                => 'nullable|numeric|min:0',
            'change'                     => 'nullable|numeric|min:0',
            'vat_sales'                  => 'required|numeric|min:0',
            'vat_amount'                 => 'required|numeric|min:0',
            'customer'                   => 'nullable|array',
            'customer.name'              => 'nullable|string|max:255',
            'customer.email'             => 'nullable|email|max:255',
            'customer.contact'           => 'nullable|string|max:20',
            'customer.address'           => 'nullable|string|max:255',
            'selected_customer_id'       => 'nullable|exists:customer_accounts,id',
            'booking_uuid'               => 'nullable|exists:bookings,uuid',
            'booking_id'                 => 'nullable|exists:bookings,id',
            'booking_ref_no'             => 'nullable|string|max:255',
            'checkin_uuid'               => 'nullable|exists:customer_checkins,uuid',
            'checkin_id'                 => 'nullable|exists:customer_checkins,id',
            'branch_uuid'                => 'nullable|exists:branches,uuid',
            'customer_uuid'              => 'nullable|exists:customer_accounts,uuid',
            'customer_reward_id'         => 'nullable|exists:customer_rewards,id',
            'reward_discount_amount'     => 'nullable|numeric|min:0',
            'reward_type'                => 'nullable|string|max:255',
            'reward_type_label'          => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $isAjax
                ? response()->json(['success' => false, 'message' => $validator->errors()->first()], 422)
                : redirect()->back()->withInput()->with('error', $validator->errors()->first());
        }

        try {
            $order = DB::transaction(function () use ($request) {
                $validated    = $request->all();
                $customerData = $validated['customer'] ?? null;

                $owner       = Auth::guard('owner')->user();
                $userId      = $owner->id;
                $userType    = 'owner';
                $currentTime = now();

                $bookingId     = $request->input('booking_id');
                $bookingUuid   = $request->input('booking_uuid');
                $bookingRefNo  = $request->input('booking_ref_no');
                $checkinId     = $request->input('checkin_id');
                $checkinUuid   = $request->input('checkin_uuid');
                $customerRewardId = $request->input('customer_reward_id');
                $rewardDiscountAmount = $request->input('reward_discount_amount', 0);
                $fallbackRewardType      = $request->input('reward_type');
                $fallbackRewardTypeLabel = $request->input('reward_type_label');

                if ($bookingUuid && !$bookingId) {
                    $booking = Booking::where('uuid', $bookingUuid)->first();
                    if ($booking) $bookingId = $booking->id;
                }

                if ($checkinUuid && !$checkinId) {
                    $checkin = CustomerCheckin::where('uuid', $checkinUuid)->first();
                    if ($checkin) $checkinId = $checkin->id;
                }

                $selectedCustomerId = $request->input('selected_customer_id');
                $customerData       = $validated['customer'] ?? null;
                $customerId         = null;
                $hasBookingInfo     = $bookingUuid || $checkinUuid;

                if ($hasBookingInfo && $selectedCustomerId) {
                    if (CustomerAccount::where('id', $selectedCustomerId)->where('active', 1)->exists()) {
                        $customerId = $selectedCustomerId;
                    }
                } elseif (!$hasBookingInfo && $selectedCustomerId) {
                    if (CustomerAccount::where('id', $selectedCustomerId)->where('active', 1)->exists()) {
                        $customerId = $selectedCustomerId;
                    }
                }

                if (!$customerId && $customerData && !empty(array_filter($customerData)) && !$hasBookingInfo) {
                    $name    = trim($customerData['name']    ?? '');
                    $email   = trim($customerData['email']   ?? '');
                    $contact = trim($customerData['contact'] ?? '');
                    $address = trim($customerData['address'] ?? '');

                    if (!empty($name) && (!empty($email) || !empty($contact))) {
                        $nameParts = explode(' ', $name, 2);
                        $firstName = $nameParts[0] ?? 'Walk-in';
                        $lastName  = $nameParts[1] ?? 'Customer';

                        if (empty($email)) $email = 'walkin_' . time() . '@example.com';

                        $existingCustomer = CustomerAccount::where('email', $email)
                            ->orWhere('contact_no', $contact)
                            ->first();

                        if ($existingCustomer) {
                            $customerId = $existingCustomer->id;
                        } else {
                            $customer   = CustomerAccount::create([
                                'first_name'     => $firstName,
                                'last_name'      => $lastName,
                                'contact_no'     => $contact,
                                'address'        => $address,
                                'email'          => $email,
                                'regular'        => 0,
                                'role'           => 3,
                                'date_joined'    => $currentTime,
                                'account_status' => 1,
                                'active'         => 1,
                            ]);
                            $customerId = $customer->id;
                        }
                    }
                }

                $subtotal = collect($validated['items'])->sum(
                    fn($item) => $item['quantity'] * $item['price']
                );

                $totalDiscount = collect($validated['items'])->sum(function ($item) {
                    $discount     = $item['discount']      ?? 0;
                    $discountType = $item['discount_type'] ?? 'amount';
                    $itemTotal    = $item['quantity'] * $item['price'];

                    return $discountType === 'percentage'
                        ? min($itemTotal, ($itemTotal * $discount) / 100)
                        : min($itemTotal, $discount);
                });

                // Add reward discount
                $totalDiscount += $rewardDiscountAmount;

                $totalAmount = max(0, $subtotal - $totalDiscount);
                $isPayLater  = $validated['payment_method'] == '3';

                $order = Order::create([
                    'order_ref_no'        => $this->generateOrderReference(),
                    'customer_account_id' => $customerId,
                    'branch_id'           => $validated['branch_id'],
                    'booking_id'          => $bookingId,
                    'order_date'          => $currentTime,
                    'order_status'        => 1,
                    'created_by'          => $userId,
                    'created_by_type'     => $userType,
                    'date_created'        => $currentTime,
                    'active'              => 1,
                ]);

                // Store order items for redemption history
                $orderItems = [];

                foreach ($validated['items'] as $item) {
                    $product      = Product::with(['productIngredients.ingredient'])->findOrFail($item['product_id']);
                    $itemDiscount = $item['discount']      ?? 0;
                    $itemDiscountType = $item['discount_type'] ?? 'amount';
                    $itemPrice    = $item['price'];
                    $itemQuantity = $item['quantity'];
                    $itemSubtotal = $itemPrice * $itemQuantity;

                    $discountAmount = 0;
                    if ($itemDiscount > 0) {
                        $discountAmount = $itemDiscountType === 'percentage'
                            ? min($itemSubtotal, ($itemSubtotal * $itemDiscount) / 100)
                            : min($itemSubtotal, $itemDiscount);
                    }

                    $orderItem = OrderItem::create([
                        'customer_account_id' => $customerId,
                        'branch_id'           => $validated['branch_id'],
                        'order_id'            => $order->id,
                        'product_id'          => $product->id,
                        'selling_price'       => $itemPrice,
                        'quantity'            => $itemQuantity,
                        'discount_amount'     => $discountAmount,
                        'discount_type'       => $itemDiscountType,
                        'discount_value'      => $itemDiscount,
                        'sub_total'           => $itemSubtotal - $discountAmount,
                        'original_subtotal'   => $itemSubtotal,
                        'order_item_status'   => 1,
                        'created_by'          => $userId,
                        'created_by_type'     => $userType,
                        'date_created'        => $currentTime,
                        'active'              => 1,
                    ]);

                    // Store item data for redemption
                    $orderItems[] = [
                        'product_id' => $product->id,
                        'price' => $itemPrice,
                        'quantity' => $itemQuantity,
                        'discount_amount' => $discountAmount,
                        'original_subtotal' => $itemSubtotal,
                    ];

                    $this->updateStocks($product, $itemQuantity, $userId, $userType, $currentTime);
                }

                $amountPaid          = $isPayLater ? 0 : ($validated['amount_paid'] ?? $totalAmount);
                $enhancedCustomerData = $customerData ?: [];

                if ($bookingUuid || $bookingRefNo || $checkinUuid) {
                    $enhancedCustomerData['booking_reference'] = [
                        'booking_uuid'  => $bookingUuid,
                        'booking_id'    => $bookingId,
                        'booking_ref_no'=> $bookingRefNo,
                        'checkin_uuid'  => $checkinUuid,
                        'checkin_id'    => $checkinId,
                        'processed_via' => 'checkin_page',
                    ];
                }

                // Resolve reward type from database
                $rewardType = null;
                $rewardTypeLabel = null;

                if ($customerRewardId) {
                    $customerReward = CustomerReward::with(['loyaltyTier.redeemableItem'])->find($customerRewardId);
                    if ($customerReward && $customerReward->loyaltyTier && $customerReward->loyaltyTier->redeemableItem) {
                        $rewardType = $customerReward->loyaltyTier->redeemableItem->reward_type;
                        $rewardTypeLabel = $customerReward->loyaltyTier->redeemableItem->type_label;
                    }
                }

                if (!$rewardType && $fallbackRewardType) {
                    $rewardType = $fallbackRewardType;
                    $rewardTypeLabel = $fallbackRewardTypeLabel;
                }

                if (!$rewardType && $rewardDiscountAmount > 0) {
                    $hasPercentageItem = collect($validated['items'])->some(
                        fn($item) => ($item['discount_type'] ?? '') === 'percentage' && ($item['discount'] ?? 0) > 0
                    );
                    
                    $itemDiscounts = collect($validated['items'])->sum(function ($item) {
                        $discount = $item['discount'] ?? 0;
                        $discountType = $item['discount_type'] ?? 'amount';
                        $itemTotal = $item['quantity'] * $item['price'];
                        return $discountType === 'percentage'
                            ? ($itemTotal * $discount) / 100
                            : $discount;
                    });

                    if ($rewardDiscountAmount > 0 && $rewardDiscountAmount != $itemDiscounts) {
                        $rewardType = 'reward';
                        $rewardTypeLabel = 'Reward Discount';
                    }
                }

                if ($customerRewardId || $rewardDiscountAmount > 0) {
                    $enhancedCustomerData['reward_applied'] = [
                        'customer_reward_id' => $customerRewardId,
                        'discount_amount'    => $rewardDiscountAmount,
                        'reward_type'        => $rewardType,
                        'reward_type_label'  => $rewardTypeLabel,
                    ];
                }

                $discountType = 'none';
                if ($totalDiscount > 0) {
                    $hasItemLevelDiscount = collect($validated['items'])->some(fn($item) => ($item['discount'] ?? 0) > 0);
                    if ($hasItemLevelDiscount) {
                        $discountType = 'item_level';
                    } elseif ($rewardDiscountAmount > 0 && $rewardType) {
                        $discountType = $rewardType;
                    } elseif ($rewardDiscountAmount > 0 || $customerRewardId) {
                        $discountType = 'reward';
                    } else {
                        $discountType = 'global';
                    }
                }

                $enhancedCustomerData['discount_type'] = $discountType;
                $enhancedCustomerData['total_discount'] = $totalDiscount;

                if ($rewardType) {
                    $enhancedCustomerData['reward_type'] = $rewardType;
                }

                $notesValue = null;
                if (!empty($validated['notes'])) {
                    $notesValue = json_encode([
                        'payment_note'   => $validated['notes'],
                        'added_at'       => $currentTime->toDateTimeString(),
                        'added_by_type'  => $userType,
                    ]);
                }

                OrderPayment::create([
                    'customer_account_id'  => $customerId,
                    'branch_id'            => $validated['branch_id'],
                    'order_id'             => $order->id,
                    'payment_date'         => $isPayLater ? null : $currentTime,
                    'payment_method'       => $validated['payment_method'],
                    'total_amount'         => $totalAmount,
                    'discount'             => $totalDiscount,
                    'vat_sales'            => $validated['vat_sales'],
                    'vat_amount'           => $validated['vat_amount'],
                    'amount_paid'          => $amountPaid,
                    'change'               => $validated['change'] ?? 0,
                    'notes'                => $notesValue,
                    'customer_data'        => json_encode(array_merge($enhancedCustomerData, [
                        'is_walk_in'       => !$hasBookingInfo && !$customerId,
                        'has_booking_info' => $hasBookingInfo,
                    ])),
                    'order_payment_status' => $isPayLater ? 0 : 1,
                    'created_by'           => $userId,
                    'created_by_type'      => $userType,
                    'date_created'         => $currentTime,
                    'last_updated_by'      => $userId,
                    'last_updated_by_type' => $userType,
                    'last_date_updated'    => $currentTime,
                    'updated_by'           => $userId,
                    'updated_by_type'      => $userType,
                    'date_updated'         => $currentTime,
                    'active'               => 1,
                ]);

                // ============================================================
                // MARK REWARD AS REDEEMED WITH REDEMPTION HISTORY
                // ============================================================
                if ($customerRewardId) {
                    $this->markRewardAsRedeemed(
                        $customerRewardId, 
                        $validated['branch_id'], 
                        $order->id,
                        $orderItems
                    );
                }

                return $order;
            });

            // ── Post-transaction: notifications ───────────────────
            $actor       = Auth::guard('owner')->user();
            $orderBranch = Branch::find($order->branch_id);
            $customer    = $order->customer_account_id
                ? CustomerAccount::find($order->customer_account_id)
                : null;
            $owners  = OwnerAccount::where('id', $actor->id)->get();
            $booking = $order->booking_id ? Booking::find($order->booking_id) : null;
            $action  = $booking ? 'with_booking' : 'no_booking';

            Notification::send($owners, new POSNotification(
                $order, $booking, $orderBranch, $customer, $actor, $action
            ));

            $staffMembers = StaffAccount::where('branch_id', $order->branch_id)
                ->where('owner_account_id', $actor->id)
                ->where('active', 1)
                ->get();

            Notification::send($staffMembers, new POSStaffNotification(
                $order, $booking, $orderBranch, $customer, $actor, $action
            ));

            // ── Record sale as inventory stock-out ────────────────
            $this->recordSaleInventoryTransaction($order, $actor);

            if ($isAjax) {
                return response()->json([
                    'success'     => true,
                    'order_uuid'  => $order->uuid,
                    'message'     => 'Order successfully processed.',
                ]);
            }

            if ($order->booking_id && $booking = Booking::find($order->booking_id)) {
                return redirect()
                    ->route('sub_one.customer_checkins.index', ['brn' => $booking->booking_ref_no])
                    ->with('success', 'Order processed successfully!');
            }

            return redirect()
                ->route('sub_one.pos.history', ['orn' => $order->order_ref_no])
                ->with('success', 'Walk-in order processed successfully!');

        } catch (Throwable $e) {
            return $isAjax
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 500)
                : redirect()->back()->withInput()->with('error', 'Failed to process order: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────
    // STOCK HELPERS
    // ─────────────────────────────────────────────────────────────
    private function updateStocks($product, $quantity, $userId, $userType, $currentTime)
    {
        if ($product->productIngredients->count() === 0) {
            $product->quantity_in -= $quantity;
            $this->updateProductAudit($product, $userId, $userType, $currentTime);
            $product->save();
            return;
        }

        foreach ($product->productIngredients as $productIngredient) {
            $ingredient = $productIngredient->ingredient;
            if (!$ingredient) continue;

            $quantityToDeduct = $productIngredient->quantity_needed * $quantity;
            $this->updateIngredientStock($ingredient, $quantityToDeduct, $userId, $userType, $currentTime);
        }
    }

    private function updateIngredientStock($ingredient, $quantityToDeduct, $userId, $userType, $currentTime)
    {
        if (!$ingredient->unit_conversion || $ingredient->unit_conversion <= 0) {
            if ($ingredient->stock_quantity_in < $quantityToDeduct) {
                throw new \Exception("Insufficient stock for {$ingredient->ingredient_name}. Available: {$ingredient->stock_quantity_in}, Required: {$quantityToDeduct}");
            }
            $ingredient->stock_quantity_in -= $quantityToDeduct;
        } else {
            $accumulatedDeduction    = ($ingredient->stock_quantity_in * $ingredient->unit_conversion) - $ingredient->converted_stock_quantity_in;
            $newAccumulatedDeduction = $accumulatedDeduction + $quantityToDeduct;

            if ($ingredient->converted_stock_quantity_in < $quantityToDeduct) {
                throw new \Exception("Insufficient stock for {$ingredient->ingredient_name}. Available: {$this->getTotalIngredientQuantity($ingredient)}, Required: {$quantityToDeduct}");
            }

            $ingredient->converted_stock_quantity_in -= $quantityToDeduct;

            if ($newAccumulatedDeduction >= $ingredient->unit_conversion) {
                $unitsToDeduct = floor($newAccumulatedDeduction / $ingredient->unit_conversion);

                if ($ingredient->stock_quantity_in < $unitsToDeduct) {
                    throw new \Exception("Insufficient stock for {$ingredient->ingredient_name}. Available: {$this->getTotalIngredientQuantity($ingredient)}, Required: {$quantityToDeduct}");
                }

                $ingredient->stock_quantity_in           -= $unitsToDeduct;
                $remainingAccumulation                    = $newAccumulatedDeduction - ($unitsToDeduct * $ingredient->unit_conversion);
                $ingredient->converted_stock_quantity_in  = ($ingredient->stock_quantity_in * $ingredient->unit_conversion) - $remainingAccumulation;
            }
        }

        $this->updateIngredientAudit($ingredient, $userId, $userType, $currentTime);
        $ingredient->save();
    }

    private function getTotalIngredientQuantity($ingredient)
    {
        if (!$ingredient->unit_conversion || $ingredient->unit_conversion <= 0) {
            return $ingredient->stock_quantity_in;
        }

        $total = 0;

        if ($ingredient->converted_stock_quantity_in > 0) {
            $total += $ingredient->converted_stock_quantity_in;
        }

        if ($ingredient->stock_quantity_in > 0) {
            $total += $ingredient->stock_quantity_in * $ingredient->unit_conversion;
        }

        return $total;
    }

    private function updateProductAudit($product, $userId, $userType, $currentTime)
    {
        if (!is_null($product->updated_by)) {
            $product->last_updated_by      = $product->updated_by;
            $product->last_updated_by_type = $product->updated_by_type;
            $product->last_date_updated    = $product->date_updated;
        }

        $product->updated_by      = $userId;
        $product->updated_by_type = $userType;
        $product->date_updated    = $currentTime;
    }

    private function updateIngredientAudit($ingredient, $userId, $userType, $currentTime)
    {
        if (!is_null($ingredient->updated_by)) {
            $ingredient->last_updated_by      = $ingredient->updated_by;
            $ingredient->last_updated_by_type = $ingredient->updated_by_type;
            $ingredient->last_date_updated    = $ingredient->date_updated;
        }

        $ingredient->updated_by      = $userId;
        $ingredient->updated_by_type = $userType;
        $ingredient->date_updated    = $currentTime;
    }

    // ─────────────────────────────────────────────────────────────
    // RECORD SALE AS INVENTORY STOCK OUT TRANSACTION
    // ─────────────────────────────────────────────────────────────
    private function recordSaleInventoryTransaction(Order $order, $actor): void
    {
        $order->load('items.product.productIngredients.ingredient');

        if ($order->items->isEmpty()) {
            Log::warning('POS inventory: no items on order', ['order_ref' => $order->order_ref_no]);
            return;
        }

        try {
            $transaction = DB::transaction(function () use ($order, $actor) {
                $txnNo = 'INV-OUT-' . now()->format('YmdHis') . '-' . strtoupper(substr($order->order_ref_no, -4));

                $transaction = InventoryTransaction::create([
                    'owner_account_id'  => $actor->id,
                    'branch_id'         => $order->branch_id,
                    'transaction_no'    => $txnNo,
                    'type'              => 'stock_out',
                    'status'            => 'approved',
                    'reason'            => 'sold',
                    'processed_by'      => $actor->first_name . ' ' . $actor->last_name,
                    'processed_by_type' => 'owner',
                    'approved_by_id'    => $actor->id,
                    'approved_by'       => $actor->first_name . ' ' . $actor->last_name,
                    'approved_at'       => now(),
                    'active'            => 1,
                ]);

                foreach ($order->items as $orderItem) {
                    $product = $orderItem->product;
                    if (!$product) continue;

                    $isMto = $product->productIngredients->isNotEmpty();

                    InventoryTransactionItem::create([
                        'inventory_transaction_id' => $transaction->id,
                        'item_type'                => $isMto ? 'mto_product' : 'product',
                        'product_id'               => $product->id,
                        'ingredient_id'            => null,
                        'quantity'                 => $orderItem->quantity,
                        'unit'                     => $product->unit ?? 'pcs',
                        'reason'                   => 'sold',
                        'note'                     => 'POS Sale (' . ($isMto ? 'MTO' : 'RTD') . ') — ' . $order->order_ref_no,
                    ]);
                }

                foreach ($order->items as $orderItem) {
                    $product = $orderItem->product;
                    if (!$product || $product->productIngredients->isEmpty()) continue;

                    foreach ($product->productIngredients as $productIngredient) {
                        $ingredient = $productIngredient->ingredient;
                        if (!$ingredient) continue;

                        $quantityUsed = $productIngredient->quantity_needed * $orderItem->quantity;
                        $unit = (!empty($ingredient->converted_unit) && $ingredient->unit_conversion > 0)
                            ? $ingredient->converted_unit
                            : ($ingredient->unit ?? '');

                        InventoryTransactionItem::create([
                            'inventory_transaction_id' => $transaction->id,
                            'item_type'                => 'ingredient',
                            'product_id'               => $product->id,
                            'ingredient_id'            => $ingredient->id,
                            'quantity'                 => $quantityUsed,
                            'unit'                     => $unit,
                            'reason'                   => 'used_in_mto',
                            'note'                     => 'POS Sale (MTO: ' . $product->product_name . ' x' . $orderItem->quantity . ') — ' . $order->order_ref_no,
                        ]);
                    }
                }

                return $transaction;
            });

            $branch = Branch::find($order->branch_id);
            if (!$branch) return;

            $owners = OwnerAccount::where('id', $actor->id)->get();
            Notification::send($owners, new ProductNotification($transaction, $branch, $actor, 'stock_out'));

            $staffMembers = StaffAccount::where('branch_id', $order->branch_id)
                ->where('owner_account_id', $actor->id)
                ->where('active', 1)
                ->get();

            if ($staffMembers->isNotEmpty()) {
                Notification::send($staffMembers, new ProductStaffNotification($transaction, $branch, $actor, 'stock_out'));
            }

        } catch (\Throwable $e) {
            Log::error('POS inventory record FAILED', [
                'error'     => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'order_ref' => $order->order_ref_no ?? 'unknown',
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // ORDER HISTORY
    // ─────────────────────────────────────────────────────────────
    public function orderHistory(Request $request)
    {
        $owner   = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        $query = Order::with(['items.product', 'payments', 'branch', 'customer'])
            ->whereHas('items.product', fn($q) => $q->where('owner_account_id', $ownerId))
            ->where('order_status', 1);

        if ($request->filled('orn')) {
            $query->where('order_ref_no', 'LIKE', '%' . $request->orn . '%');
        }

        if ($request->filled('product_id')) {
            $query->where('id', $request->product_id);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('customer', fn($q) => $q->where(
                    DB::raw("CONCAT(first_name,' ',last_name)"), 'LIKE', "%{$searchTerm}%"
                ))->orWhere('order_ref_no', 'LIKE', "%{$searchTerm}%");
            });
        }

        if ($request->has('payment_method') && $request->payment_method !== '') {
            $query->whereHas('payments', fn($q) => $q->where('payment_method', $request->payment_method));
        }

        if ($request->has('payment_status') && $request->payment_status !== '') {
            $query->whereHas('payments', fn($q) => $q->where('order_payment_status', $request->payment_status));
        }

        // Discount type filter - updated to use the new helper methods
        if ($request->has('discount_type') && $request->discount_type !== '') {
            $discountType = $request->discount_type;
            $query->whereHas('payments', function ($q) use ($discountType) {
                if ($discountType === 'none') {
                    $q->where('discount', 0);
                } else {
                    $q->where('discount', '>', 0)
                      ->whereRaw("JSON_EXTRACT(customer_data, '$.discount_type') = ?", [$discountType]);
                }
            });
        }

        if ($request->has('date_start') && $request->date_start) {
            $query->whereDate('date_created', '>=', $request->date_start);
        }

        if ($request->has('date_end') && $request->date_end) {
            $query->whereDate('date_created', '<=', $request->date_end);
        }

        $orders = $query->orderBy('date_created', 'desc')->paginate(50);

        $stats = [
            'total_orders'     => $orders->total(),
            'completed_orders' => (clone $query)->where('order_status', 1)->count(),
        ];

        // Transform orders with discount type information
        $orders->getCollection()->transform(function ($order) {
            $order->status_text = $this->getOrderStatusText($order->order_status);

            $order->payments->each(function ($payment) use ($order) {
                $payment->payment_method_text = $this->getPaymentMethodText($payment->payment_method);
                $payment->payment_status_text = $this->getPaymentStatusText($payment->order_payment_status);

                // Get the discount type from the order
                $discountType = $this->getDiscountTypeForOrder($order);
                $payment->discount_type = $discountType;
                $payment->discount_type_label = $this->getRewardTypeLabel($discountType);
                $payment->discount_type_badge_class = $this->getRewardTypeBadgeClass($discountType);
                
                // Also pass the reward_type directly from redemption if available
                $redemption = RewardRedemption::where('order_id', $order->id)
                    ->where('active', 1)
                    ->first();
                if ($redemption && $redemption->reward_type) {
                    $payment->reward_type = $redemption->reward_type;
                }
            });

            return $order;
        });

        if ($request->ajax()) {
            return response()->json([
                'success'    => true,
                'data'       => $orders->items(),
                'pagination' => $orders->toArray(),
                'stats'      => $stats,
            ]);
        }

        return view('owner.pos.history', compact('orders', 'stats'));
    }

    /**
     * Get the discount type for an order based on trace path logic
     */
    protected function getDiscountTypeForOrder(Order $order): string
    {
        $payment = $order->payments->first();
        
        if (!$payment) {
            return 'none';
        }

        // ============================================================
        // STEP 1: Check reward_redemptions table first (MOST RELIABLE)
        // ============================================================
        $redemption = RewardRedemption::where('order_id', $order->id)
            ->where('active', 1)
            ->first();
        
        if ($redemption && $redemption->reward_type) {
            // Return the actual reward type from the redemption record
            return $redemption->reward_type;
        }

        // ============================================================
        // STEP 2: Check customer_data for reward_applied
        // ============================================================
        $customerData = !empty($payment->customer_data)
            ? json_decode($payment->customer_data, true)
            : null;

        if ($customerData && isset($customerData['reward_applied'])) {
            // Check if reward_type is explicitly set in reward_applied
            if (isset($customerData['reward_applied']['reward_type']) && !empty($customerData['reward_applied']['reward_type'])) {
                $rewardType = $customerData['reward_applied']['reward_type'];
                $validRewardTypes = ['free_service', 'free_product', 'percentage_discount', 'fixed_discount', 'product_discount'];
                if (in_array($rewardType, $validRewardTypes)) {
                    return $rewardType;
                }
            }
            
            // If reward_applied exists but no reward_type, check if there's a customer_reward_id
            if (isset($customerData['reward_applied']['customer_reward_id'])) {
                $customerRewardId = $customerData['reward_applied']['customer_reward_id'];
                if ($customerRewardId) {
                    // First check if there's a redemption record for this customer reward and order
                    $redemptionByReward = RewardRedemption::where('customer_reward_id', $customerRewardId)
                        ->where('order_id', $order->id)
                        ->where('active', 1)
                        ->first();
                        
                    if ($redemptionByReward && $redemptionByReward->reward_type) {
                        return $redemptionByReward->reward_type;
                    }
                    
                    // Fallback to customer reward
                    $customerReward = CustomerReward::with('loyaltyTier.redeemableItem')->find($customerRewardId);
                    if ($customerReward && $customerReward->loyaltyTier && $customerReward->loyaltyTier->redeemableItem) {
                        $rewardType = $customerReward->loyaltyTier->redeemableItem->reward_type;
                        if (!empty($rewardType)) {
                            return $rewardType;
                        }
                    }
                }
                return 'reward';
            }
        }

        // ============================================================
        // STEP 3: Check for discount_type in customer_data
        // ============================================================
        if ($customerData && isset($customerData['discount_type']) && $customerData['discount_type'] !== 'none') {
            $discountType = $customerData['discount_type'];
            $validRewardTypes = ['free_service', 'free_product', 'percentage_discount', 'fixed_discount', 'product_discount'];
            if (in_array($discountType, $validRewardTypes)) {
                return $discountType;
            }
            if ($discountType === 'reward') {
                return 'reward';
            }
            // If it's not a reward type, return as is (item_level, global)
            return $discountType;
        }

        // ============================================================
        // STEP 4: Fallback to payment discount detection
        // ============================================================
        if ($payment->discount > 0) {
            $hasItemDiscount = $order->items->where('discount_amount', '>', 0)->isNotEmpty();
            if ($hasItemDiscount) {
                $percentageItem = $order->items->where('discount_type', 'percentage')->first();
                if ($percentageItem) {
                    return 'percentage_discount';
                }
                return 'fixed_discount';
            }
            return 'global';
        }

        return 'none';
    }

    /**
     * Get the discount breakdown for an order
     */
    protected function getDiscountBreakdown(Order $order): array
    {
        $payment = $order->payments->first();
        if (!$payment) {
            return [];
        }

        $breakdown = [
            'total_discount' => $payment->discount ?? 0,
            'type' => $this->getDiscountTypeForOrder($order),
            'type_label' => $this->getRewardTypeLabel($this->getDiscountTypeForOrder($order)),
            'type_badge_class' => $this->getRewardTypeBadgeClass($this->getDiscountTypeForOrder($order)),
            'details' => []
        ];

        // Get item-level discounts
        $itemDiscounts = $order->items->filter(fn($item) => ($item->discount_amount ?? 0) > 0);
        if ($itemDiscounts->isNotEmpty()) {
            $breakdown['details']['items'] = $itemDiscounts->map(function ($item) {
                return [
                    'product_name' => $item->product->product_name ?? 'Unknown',
                    'discount_amount' => $item->discount_amount ?? 0,
                    'discount_value' => $item->discount_value ?? 0,
                    'discount_type' => $item->discount_type ?? 'amount',
                ];
            });
        }

        // Check for reward discount
        if ($payment->customer_data) {
            $customerData = json_decode($payment->customer_data, true);
            if (isset($customerData['reward_applied'])) {
                $breakdown['details']['reward'] = $customerData['reward_applied'];
                $breakdown['details']['reward']['reward_type'] = $breakdown['type'];
                $breakdown['details']['reward']['reward_type_label'] = $breakdown['type_label'];
            }
        }

        return $breakdown;
    }

    /**
     * Get order discount details for API response
     */
    public function getOrderDiscountDetails($orderId)
    {
        try {
            $owner = Auth::guard('owner')->user();
            
            $order = Order::with(['items.product', 'payments'])
                ->whereHas('items.product', fn($q) => $q->where('owner_account_id', $owner->id))
                ->findOrFail($orderId);

            $discountBreakdown = $this->getDiscountBreakdown($order);

            return response()->json([
                'success' => true,
                'data' => $discountBreakdown
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get discount details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get redemption history for POS orders
     */
    public function getRedemptionHistory(Request $request)
    {
        try {
            $owner = Auth::guard('owner')->user();
            $branchId = $request->get('branch_id');

            $query = RewardRedemption::with([
                'customerReward',
                'customerReward.loyaltyTier',
                'customerReward.loyaltyTier.redeemableItem',
                'customer',
                'product',
                'branch',
                'order',
                'booking'
            ])
            ->where('active', 1);

            // Filter by branch if provided
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            // Filter by owner's branches
            $ownerBranches = Branch::where('owner_account_id', $owner->id)->pluck('id')->toArray();
            if (!empty($ownerBranches)) {
                $query->whereIn('branch_id', $ownerBranches);
            }

            // Apply date filters
            if ($request->has('date_start') && $request->date_start) {
                $query->whereDate('redeemed_at', '>=', $request->date_start);
            }

            if ($request->has('date_end') && $request->date_end) {
                $query->whereDate('redeemed_at', '<=', $request->date_end);
            }

            // Apply reward type filter
            if ($request->has('reward_type') && $request->reward_type !== 'all') {
                $query->where('reward_type', $request->reward_type);
            }

            // Apply search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('receipt_number', 'LIKE', "%{$search}%")
                      ->orWhereHas('customerReward', function($subQ) use ($search) {
                          $subQ->where('voucher_code', 'LIKE', "%{$search}%");
                      })
                      ->orWhereHas('customer', function($subQ) use ($search) {
                          $subQ->where('first_name', 'LIKE', "%{$search}%")
                               ->orWhere('last_name', 'LIKE', "%{$search}%")
                               ->orWhere('email', 'LIKE', "%{$search}%");
                      })
                      ->orWhereHas('product', function($subQ) use ($search) {
                          $subQ->where('product_name', 'LIKE', "%{$search}%");
                      });
                });
            }

            $redemptions = $query->orderBy('redeemed_at', 'desc')->paginate(15);

            // Transform to include computed values
            $redemptions->getCollection()->transform(function ($item) {
                // Get original price from product or from redemption record
                $originalPrice = $item->original_amount ?? 0;
                if ($item->product && $originalPrice == 0) {
                    $originalPrice = $item->product->selling_price ?? 0;
                }
                
                // Get discount amount - make sure it's positive
                $discountAmount = abs($item->discount_amount ?? 0);
                
                // Calculate final amount (original price - discount)
                $finalAmount = max(0, $originalPrice - $discountAmount);
                
                $item->original_price = $originalPrice;
                $item->discount_amount_positive = $discountAmount;
                $item->computed_final_amount = $finalAmount;
                $item->redeemed_by_name = $item->redeemed_by_name;
                $item->redeemed_by_role = $item->redeemed_by_role;
                $item->customer_name = $item->customer ? $item->customer->first_name . ' ' . $item->customer->last_name : 'N/A';
                
                return $item;
            });

            // Summary stats
            $summary = [
                'total_redemptions' => $redemptions->total(),
                'total_savings' => $query->sum('discount_amount'),
                'free_services' => (clone $query)->where('reward_type', 'free_service')->count(),
                'free_products' => (clone $query)->where('reward_type', 'free_product')->count(),
                'fixed_discounts' => (clone $query)->where('reward_type', 'fixed_discount')->count(),
                'percentage_discounts' => (clone $query)->where('reward_type', 'percentage_discount')->count(),
            ];

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $redemptions,
                    'summary' => $summary
                ]);
            }

            return view('owner.pos.redemption_history', [
                'redemptions' => $redemptions,
                'summary' => $summary,
                'branches' => Branch::where('owner_account_id', $owner->id)->where('active', 1)->get(),
                'selectedBranch' => $branchId
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getRedemptionHistory: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error loading redemption history: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Error loading redemption history: ' . $e->getMessage());
        }
    }

    /**
     * Show redemption history page
     */
    public function showRedemptionHistory(Request $request)
    {
        return $this->getRedemptionHistory($request);
    }
}