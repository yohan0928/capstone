<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CustomerAccount;
use App\Models\CustomerReward;
use App\Models\OwnerAccount;
use App\Models\LoyaltyTier;
use App\Models\RedeemableItem;
use App\Models\RewardRedemption;
use App\Models\StaffAccount;
use App\Notifications\Customer\CustomerRewardNotification;
use App\Notifications\Owner\CustomerRewardOwnerNotification;
use App\Notifications\Staff\CustomerRewardStaffNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class MyRewardTrackerController extends Controller
{
    // Claim status constants
    const CLAIM_STATUS_PENDING = 2;
    const CLAIM_STATUS_CLAIMED = 1;
    const CLAIM_STATUS_DECLINED = 0;
    const CLAIM_STATUS_EXPIRED = 3;
    
    // Redemption status constants
    const REDEMPTION_STATUS_PENDING = 'pending';
    const REDEMPTION_STATUS_READY = 'ready';
    const REDEMPTION_STATUS_REDEEMED = 'redeemed';
    const REDEMPTION_STATUS_CANCELLED = 'cancelled';

    /**
     * Ensure the database foreign key constraints are correctly configured.
     */
    private function ensureCorrectDatabaseSchema()
    {
        try {
            $wrongConstraint = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'customer_rewards' 
                  AND COLUMN_NAME = 'loyalty_tier_id' 
                  AND REFERENCED_TABLE_NAME = 'reward_tiers'
                LIMIT 1
            ");

            if (!empty($wrongConstraint)) {
                $constraintName = $wrongConstraint[0]->CONSTRAINT_NAME;
                DB::statement("ALTER TABLE customer_rewards DROP FOREIGN KEY `{$constraintName}`");
                Log::info("Self-Healing Schema: Dropped incorrect foreign key constraint '{$constraintName}' on 'customer_rewards'.");
            }

            $correctConstraint = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'customer_rewards' 
                  AND COLUMN_NAME = 'loyalty_tier_id' 
                  AND REFERENCED_TABLE_NAME = 'loyalty_tiers'
                LIMIT 1
            ");

            if (empty($correctConstraint)) {
                DB::statement("ALTER TABLE customer_rewards ADD CONSTRAINT `customer_rewards_loyalty_tier_id_foreign` FOREIGN KEY (`loyalty_tier_id`) REFERENCES `loyalty_tiers` (`id`) ON DELETE CASCADE");
                Log::info("Self-Healing Schema: Created correct foreign key constraint referencing 'loyalty_tiers(id)'.");
            }
        } catch (\Exception $e) {
            Log::warning("Self-Healing Schema Warning: " . $e->getMessage());
        }
    }

    public function showMyRewards(Request $request)
    {
        $this->ensureCorrectDatabaseSchema();

        try {
            $customer = Auth::guard('customer')->user();

            if (!$customer) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Authentication required. Please login.',
                        'redirect' => route('showLoginForm')
                    ], 401);
                }
                return redirect()->route('showLoginForm')->with('error', 'Please login to view your rewards.');
            }

            $customer = CustomerAccount::with([
                'bookings' => function ($query) {
                    $query
                        ->with(['branch', 'serviceName'])
                        ->where('booking_status', 4)
                        ->where('active', 1)
                        ->orderBy('date_start', 'desc');
                },
                'rewards' => function ($query) {
                    $query
                        ->with(['loyaltyTier', 'loyaltyTier.branch', 'loyaltyTier.redeemableItem'])
                        ->where('active', 1)
                        ->orderBy('date_created', 'desc');
                }
            ])->find($customer->id);

            if (!$customer) {
                throw new \Exception('Customer account not found.');
            }

            $totalCompletedBookings = $customer->bookings->where('booking_status', 4)->count();
            
            $branchIds = $customer->bookings()
                ->where('booking_status', 4)
                ->where('active', 1)
                ->distinct()
                ->pluck('branch_id')
                ->toArray();

            // ============================================================
            // GET ALL REWARDS BY STATUS
            // ============================================================
            
            // 1. Claimed Rewards (CLAIM_STATUS_CLAIMED = 1)
            $claimedRewards = CustomerReward::with([
                'loyaltyTier', 
                'loyaltyTier.redeemableItem',
                'loyaltyTier.redeemableItem.targetService',
                'loyaltyTier.redeemableItem.targetService.serviceCategory',
                'loyaltyTier.redeemableItem.targetProduct',
                'loyaltyTier.redeemableItem.targetProduct.productIngredients',
                'loyaltyTier.redeemableItem.targetProduct.productIngredients.ingredient',
                'branch'
            ])
            ->where('customer_account_id', $customer->id)
            ->where('claim_status', self::CLAIM_STATUS_CLAIMED)
            ->where('active', 1)
            ->orderBy('date_updated', 'desc')
            ->get();
            
            // 2. Pending Rewards (CLAIM_STATUS_PENDING = 2)
            $pendingRewards = CustomerReward::with([
                'loyaltyTier', 
                'loyaltyTier.redeemableItem',
                'loyaltyTier.redeemableItem.targetService',
                'loyaltyTier.redeemableItem.targetService.serviceCategory',
                'loyaltyTier.redeemableItem.targetProduct',
                'loyaltyTier.redeemableItem.targetProduct.productIngredients',
                'loyaltyTier.redeemableItem.targetProduct.productIngredients.ingredient',
                'branch'
            ])
            ->where('customer_account_id', $customer->id)
            ->where('claim_status', self::CLAIM_STATUS_PENDING)
            ->where('active', 1)
            ->orderBy('date_created', 'desc')
            ->get();

            // 3. Redeemed Rewards (REDEMPTION_STATUS_REDEEMED = 'redeemed')
            $redeemedRewards = CustomerReward::with([
                'loyaltyTier', 
                'loyaltyTier.redeemableItem',
                'loyaltyTier.redeemableItem.targetService',
                'loyaltyTier.redeemableItem.targetService.serviceCategory',
                'loyaltyTier.redeemableItem.targetProduct',
                'loyaltyTier.redeemableItem.targetProduct.productIngredients',
                'loyaltyTier.redeemableItem.targetProduct.productIngredients.ingredient',
                'branch',
                'redeemedAtBranch',
                'redemptions'
            ])
            ->where('customer_account_id', $customer->id)
            ->where('redemption_status', self::REDEMPTION_STATUS_REDEEMED)
            ->where('active', 1)
            ->orderBy('redeemed_at', 'desc')
            ->get();

            // 4. Expired Rewards
            $expiredRewards = CustomerReward::with([
                'loyaltyTier', 
                'loyaltyTier.redeemableItem',
                'branch'
            ])
            ->where('customer_account_id', $customer->id)
            ->where('claim_status', self::CLAIM_STATUS_EXPIRED)
            ->where('active', 1)
            ->orderBy('date_updated', 'desc')
            ->get();

            // ============================================================
            // GET REDEMPTION HISTORY
            // ============================================================
            $redemptionHistory = RewardRedemption::with([
                'customerReward',
                'customerReward.loyaltyTier',
                'customerReward.loyaltyTier.redeemableItem',
                'serviceName',
                'serviceCategory',
                'product',
                'branch',
                'booking',
                'order'
            ])
            ->where('customer_account_id', $customer->id)
            ->where('active', 1)
            ->orderBy('redeemed_at', 'desc')
            ->paginate(15);

            // ============================================================
            // GET ACTIVE LOYALTY TIERS
            // ============================================================
            $loyaltyTiers = collect();
            $tierSource = 'none';

            $allActiveLoyaltyTiers = LoyaltyTier::with([
                'branch', 
                'redeemableItem',
                'redeemableItem.targetService',
                'redeemableItem.targetService.serviceCategory',
                'redeemableItem.targetProduct',
                'redeemableItem.targetProduct.productIngredients',
                'redeemableItem.targetProduct.productIngredients.ingredient'
            ])
            ->where('reward_tier_status', 1)
            ->where('active', 1)
            ->orderBy('reward_required', 'asc')
            ->get();

            if (!empty($branchIds)) {
                $loyaltyTiers = LoyaltyTier::with([
                    'branch', 
                    'redeemableItem',
                    'redeemableItem.targetService',
                    'redeemableItem.targetService.serviceCategory',
                    'redeemableItem.targetProduct',
                    'redeemableItem.targetProduct.productIngredients',
                    'redeemableItem.targetProduct.productIngredients.ingredient'
                ])
                ->where('reward_tier_status', 1)
                ->where('active', 1)
                ->whereIn('branch_id', $branchIds)
                ->orderBy('reward_required', 'asc')
                ->get();
                
                if ($loyaltyTiers->isNotEmpty()) {
                    $tierSource = 'branch_match';
                }
            }

            if ($loyaltyTiers->isEmpty()) {
                $loyaltyTiers = LoyaltyTier::with([
                    'branch', 
                    'redeemableItem',
                    'redeemableItem.targetService',
                    'redeemableItem.targetService.serviceCategory',
                    'redeemableItem.targetProduct',
                    'redeemableItem.targetProduct.productIngredients',
                    'redeemableItem.targetProduct.productIngredients.ingredient'
                ])
                ->where('reward_tier_status', 1)
                ->where('active', 1)
                ->whereNull('branch_id')
                ->orderBy('reward_required', 'asc')
                ->get();
                
                if ($loyaltyTiers->isNotEmpty()) {
                    $tierSource = 'global_tiers';
                }
            }

            if ($loyaltyTiers->isEmpty()) {
                $loyaltyTiers = $allActiveLoyaltyTiers;
                if ($loyaltyTiers->isNotEmpty()) {
                    $tierSource = 'all_active_tiers';
                }
            }

            $uniqueMilestones = $loyaltyTiers->pluck('reward_required')->unique()->sort()->values();
            $maxRequired = $loyaltyTiers->isNotEmpty() ? $loyaltyTiers->max('reward_required') : 0;
            $progressPercentage = $maxRequired > 0 ? min(($totalCompletedBookings / $maxRequired) * 100, 100) : 0;

            // ============================================================
            // CALCULATE STATS
            // ============================================================
            $totalEarnedRewards = $customer->rewards->where('active', 1)->count();
            $claimedCount = $claimedRewards->count();
            $redeemedCount = $redeemedRewards->count();
            $pendingCount = $pendingRewards->count();
            $expiredCount = $expiredRewards->count();
            $redemptionHistoryCount = $redemptionHistory->total();
            
            $completedTiers = 0;
            foreach ($loyaltyTiers as $tier) {
                if ($totalCompletedBookings >= $tier->reward_required) {
                    $completedTiers++;
                }
            }

            $stats = [
                'total_earned_rewards' => $totalEarnedRewards,
                'claimed_rewards' => $claimedCount,
                'redeemed_rewards' => $redeemedCount,
                'pending_rewards' => $pendingCount,
                'expired_rewards' => $expiredCount,
                'available_rewards' => $pendingCount,
                'total_bookings' => $totalCompletedBookings,
                'completion_rate' => $loyaltyTiers->count() > 0 ? round(($completedTiers / $loyaltyTiers->count()) * 100, 1) : 0,
                'current_streak' => $this->calculateCurrentStreak($customer),
                'redemption_history_count' => $redemptionHistoryCount
            ];

            // ============================================================
            // PROCESS LOYALTY TIERS FOR DISPLAY
            // ============================================================
            $processedLoyaltyTiers = $loyaltyTiers->map(function ($tier) use ($customer) {
                $rewardType = $tier->redeemableItem ? $tier->redeemableItem->reward_type : null;
                $tier->reward_type_label = $this->getRewardTypeLabel($rewardType);
                
                if ($tier->redeemableItem) {
                    $tier->redemption_item_name = $tier->redeemableItem->item_name;
                    $tier->redemption_item_type = $tier->redeemableItem->reward_type;
                    
                    $targetDetails = $tier->redeemableItem->target_details;
                    if ($targetDetails) {
                        $tier->target_details = $targetDetails;
                        $tier->target_name = $targetDetails['name'] ?? null;
                        $tier->target_type = $targetDetails['type'] ?? null;
                        $tier->target_category = $targetDetails['category'] ?? null;
                        $tier->target_branch = $targetDetails['branch'] ?? null;
                    }
                    
                    if ($rewardType === 'percentage_discount') {
                        $tier->redemption_value = $tier->redeemableItem->discount_percentage . '% off';
                        $tier->discount_percentage = $tier->redeemableItem->discount_percentage;
                    } else {
                        $tier->redemption_value = '₱' . number_format($tier->redeemableItem->monetary_value ?? 0, 2);
                        $tier->monetary_value = $tier->redeemableItem->monetary_value;
                    }
                } else {
                    $tier->redemption_item_name = 'Reward Item';
                    $tier->redemption_item_type = 'custom';
                    $tier->redemption_value = '₱0.00';
                    $tier->monetary_value = 0;
                    $tier->discount_percentage = null;
                    $tier->target_details = null;
                    $tier->target_name = null;
                    $tier->target_type = null;
                }
                
                $tier->claimed = CustomerReward::where('customer_account_id', $customer->id)
                    ->where('loyalty_tier_id', $tier->id)
                    ->where('claim_status', self::CLAIM_STATUS_CLAIMED)
                    ->where('active', 1)
                    ->exists();
                    
                $tier->pending = CustomerReward::where('customer_account_id', $customer->id)
                    ->where('loyalty_tier_id', $tier->id)
                    ->where('claim_status', self::CLAIM_STATUS_PENDING)
                    ->where('active', 1)
                    ->exists();
                
                $tier->redeemed = CustomerReward::where('customer_account_id', $customer->id)
                    ->where('loyalty_tier_id', $tier->id)
                    ->where('redemption_status', self::REDEMPTION_STATUS_REDEEMED)
                    ->where('active', 1)
                    ->exists();
                
                $tier->is_currently_claimable = $this->isLoyaltyTierCurrentlyClaimable($tier, now()) && !$tier->claimed && !$tier->pending && !$tier->redeemed;
                $tier->availability_message = $this->getAvailabilityMessage($tier, now());
                
                return $tier;
            });

            // Pass all data to the view
            return view('customer.my_rewards.my_reward_tracker', [
                'customer' => $customer,
                'loyaltyTiers' => $loyaltyTiers,
                'processedLoyaltyTiers' => $processedLoyaltyTiers,
                'uniqueMilestones' => $uniqueMilestones,
                'totalCompletedBookings' => $totalCompletedBookings,
                'maxRequired' => $maxRequired,
                'progressPercentage' => $progressPercentage,
                'stats' => $stats,
                'claimedRewards' => $claimedRewards,
                'pendingRewards' => $pendingRewards,
                'redeemedRewards' => $redeemedRewards,
                'expiredRewards' => $expiredRewards,
                'redemptionHistory' => $redemptionHistory,
                'debug' => [
                    'tier_source' => $tierSource,
                    'branch_ids' => $branchIds,
                    'loyalty_tier_count' => $loyaltyTiers->count(),
                    'all_active_tiers_count' => $allActiveLoyaltyTiers->count(),
                    'processed_count' => $processedLoyaltyTiers->count()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in showMyRewards: ' . $e->getMessage(), [
                'customer_id' => Auth::guard('customer')->id(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error loading reward tracker: ' . $e->getMessage()
                ], 500);
            }

            return $this->renderEmptyState($request, Auth::guard('customer')->user(), $e->getMessage());
        }
    }

    /**
     * Get reward type label
     */
    private function getRewardTypeLabel($rewardType)
    {
        $labels = [
            'free_service' => 'Free Service',
            'free_product' => 'Free Product',
            'fixed_discount' => 'Fixed Discount',
            'percentage_discount' => 'Percentage Discount'
        ];
        return $labels[$rewardType] ?? 'Custom';
    }

    /**
     * Get redemption value display
     */
    private function getRedemptionValueDisplay($item)
    {
        if (!$item) return 'N/A';
        
        switch ($item->reward_type) {
            case 'free_service':
                return $item->targetService ? $item->targetService->service_name : 'Free Service';
            case 'free_product':
                return $item->targetProduct ? $item->targetProduct->product_name : 'Free Product';
            case 'fixed_discount':
                return '₱' . number_format($item->monetary_value, 2) . ' off';
            case 'percentage_discount':
                return $item->discount_percentage . '% off';
            default:
                return 'N/A';
        }
    }

    private function renderEmptyState($request, $customer, $error = null)
    {
        $totalBookings = $customer ? $customer->bookings->where('booking_status', 4)->count() : 0;
        
        $stats = [
            'total_earned_rewards' => 0,
            'claimed_rewards' => 0,
            'redeemed_rewards' => 0,
            'pending_rewards' => 0,
            'expired_rewards' => 0,
            'available_rewards' => 0,
            'total_bookings' => $totalBookings,
            'completion_rate' => 0,
            'current_streak' => 0,
            'redemption_history_count' => 0
        ];

        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $error ?? 'No rewards available yet. Complete more bookings to earn rewards!',
                'data' => [
                    'loyalty_tiers' => [],
                    'unique_milestones' => [],
                    'total_bookings' => $totalBookings,
                    'stats' => $stats,
                    'claimed_rewards' => [],
                    'pending_rewards' => [],
                    'redeemed_rewards' => [],
                    'expired_rewards' => [],
                    'redemption_history' => []
                ]
            ]);
        }

        return view('customer.my_rewards.my_reward_tracker', [
            'customer' => $customer,
            'loyaltyTiers' => collect([]),
            'processedLoyaltyTiers' => collect([]),
            'uniqueMilestones' => collect([]),
            'totalCompletedBookings' => $totalBookings,
            'maxRequired' => 0,
            'progressPercentage' => 0,
            'stats' => $stats,
            'claimedRewards' => collect([]),
            'pendingRewards' => collect([]),
            'redeemedRewards' => collect([]),
            'expiredRewards' => collect([]),
            'redemptionHistory' => collect([]),
            'error' => $error
        ]);
    }

    private function isLoyaltyTierCurrentlyClaimable($tier, $now)
    {
        if ($tier->date_start && $now->lt(Carbon::parse($tier->date_start)->startOfDay())) {
            return false;
        }
        
        if ($tier->date_end && $now->gt(Carbon::parse($tier->date_end)->endOfDay())) {
            return false;
        }
        
        if ($tier->start_time) {
            $startTime = Carbon::parse($tier->start_time);
            $currentTime = Carbon::parse($now->format('H:i:s'));
            if ($currentTime->lt($startTime)) {
                return false;
            }
        }
        
        if ($tier->end_time) {
            $endTime = Carbon::parse($tier->end_time);
            $currentTime = Carbon::parse($now->format('H:i:s'));
            if ($currentTime->gt($endTime)) {
                return false;
            }
        }
        
        return true;
    }

    private function getAvailabilityMessage($tier, $now)
    {
        $messages = [];
        
        if ($tier->date_start && $tier->date_end) {
            $startDate = Carbon::parse($tier->date_start);
            $endDate = Carbon::parse($tier->date_end);
            
            if ($startDate->equalTo($endDate)) {
                $messages[] = 'Available on ' . $startDate->format('M d, Y');
            } else {
                $messages[] = 'Available from ' . $startDate->format('M d') . ' to ' . $endDate->format('M d, Y');
            }
        } elseif ($tier->date_start) {
            $startDate = Carbon::parse($tier->date_start);
            $messages[] = 'Available from ' . $startDate->format('M d, Y');
        } elseif ($tier->date_end) {
            $endDate = Carbon::parse($tier->date_end);
            $messages[] = 'Available until ' . $endDate->format('M d, Y');
        }
        
        if ($tier->start_time && $tier->end_time) {
            $startTime = Carbon::parse($tier->start_time);
            $endTime = Carbon::parse($tier->end_time);
            $messages[] = $startTime->format('g:i A') . ' - ' . $endTime->format('g:i A');
        } elseif ($tier->start_time) {
            $startTime = Carbon::parse($tier->start_time);
            $messages[] = 'From ' . $startTime->format('g:i A');
        } elseif ($tier->end_time) {
            $endTime = Carbon::parse($tier->end_time);
            $messages[] = 'Until ' . $endTime->format('g:i A');
        }
        
        return implode(' • ', $messages);
    }

    private function calculateCurrentStreak($customer)
    {
        $bookingDates = $customer->bookings
            ->where('booking_status', 4)
            ->map(function ($booking) {
                return Carbon::parse($booking->date_start)->format('Y-m-d');
            })
            ->unique()
            ->sort()
            ->values();

        if ($bookingDates->isEmpty()) {
            return 0;
        }

        $streak = 1;
        $mostRecentDate = Carbon::parse($bookingDates->last());
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        if ($mostRecentDate->format('Y-m-d') !== $today && 
            $mostRecentDate->format('Y-m-d') !== $yesterday) {
            return 0;
        }

        for ($i = $bookingDates->count() - 1; $i > 0; $i--) {
            $currentDate = Carbon::parse($bookingDates[$i]);
            $previousDate = Carbon::parse($bookingDates[$i - 1]);

            if ($currentDate->diffInDays($previousDate) === 1) {
                $streak++;
            } else {
                break;
            }
        }

        return $streak;
    }

    public function claimReward(Request $request)
    {
        $this->ensureCorrectDatabaseSchema();

        try {
            $customer = Auth::guard('customer')->user();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required. Please login.'
                ], 401);
            }

            $request->validate([
                'loyalty_tier_id' => 'required|exists:loyalty_tiers,id',
            ]);

            $loyaltyTierId = $request->loyalty_tier_id;

            DB::beginTransaction();

            $loyaltyTier = LoyaltyTier::with([
                'redeemableItem',
                'redeemableItem.targetService',
                'redeemableItem.targetProduct',
                'branch'
            ])->find($loyaltyTierId);

            if (!$loyaltyTier) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Loyalty tier not found.'
                ], 404);
            }

            $existingReward = CustomerReward::where('customer_account_id', $customer->id)
                ->where('loyalty_tier_id', $loyaltyTierId)
                ->whereIn('claim_status', [
                    self::CLAIM_STATUS_CLAIMED,
                    self::CLAIM_STATUS_PENDING
                ])
                ->where('active', 1)
                ->first();

            if ($existingReward) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'You have already claimed or are processing this reward.'
                ], 400);
            }

            $totalBookings = $customer->bookings->where('booking_status', 4)->count();

            if ($totalBookings < $loyaltyTier->reward_required) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "You need {$loyaltyTier->reward_required} bookings to claim this reward. You have {$totalBookings}."
                ], 400);
            }

            $voucherCode = $this->generateVoucherCode($loyaltyTier);

            $monetaryValue = null;
            if ($loyaltyTier->redeemableItem) {
                if ($loyaltyTier->redeemableItem->reward_type === 'percentage_discount') {
                    $monetaryValue = null;
                } else {
                    $monetaryValue = $loyaltyTier->redeemableItem->monetary_value;
                }
            }

            $expirationDate = null;
            if ($loyaltyTier->date_end) {
                $expirationDate = Carbon::parse($loyaltyTier->date_end)->endOfDay();
            } elseif ($loyaltyTier->expiry_duration && $loyaltyTier->expiry_duration > 0) {
                $expirationDate = now()->addDays($loyaltyTier->expiry_duration);
            } else {
                $expirationDate = now()->addDays(30);
            }

            $customerReward = CustomerReward::create([
                'uuid' => (string) Str::uuid(),
                'customer_account_id' => $customer->id,
                'loyalty_tier_id' => $loyaltyTier->id,
                'voucher_code' => $voucherCode,
                'monetary_value' => $monetaryValue,
                'branch_id' => $loyaltyTier->branch_id,
                'claim_status' => self::CLAIM_STATUS_CLAIMED,
                'redemption_status' => self::REDEMPTION_STATUS_READY,
                'expiration_date' => $expirationDate,
                'date_created' => now(),
                'active' => 1,
            ]);

            DB::commit();

            $this->sendRewardNotifications($customerReward, $loyaltyTier, $customer);

            return response()->json([
                'success' => true,
                'message' => 'Reward claimed successfully! You can now view your voucher.',
                'data' => [
                    'customer_reward' => $customerReward->load([
                        'loyaltyTier', 
                        'loyaltyTier.redeemableItem',
                        'loyaltyTier.redeemableItem.targetService',
                        'loyaltyTier.redeemableItem.targetProduct'
                    ]),
                    'voucher_code' => $voucherCode,
                    'monetary_value' => $monetaryValue,
                    'expiration_date' => $expirationDate ? $expirationDate->format('Y-m-d H:i:s') : null,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error claiming reward: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to claim reward: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generateVoucherCode($loyaltyTier)
    {
        $prefix = $loyaltyTier->voucher_prefix ?? 'RWD';
        $timestamp = now()->format('YmdHis');
        $random = strtoupper(Str::random(6));
        
        $code = $prefix . '-' . $timestamp . '-' . $random;
        
        $counter = 0;
        while (CustomerReward::where('voucher_code', $code)->exists() && $counter < 10) {
            $random = strtoupper(Str::random(6));
            $code = $prefix . '-' . $timestamp . '-' . $random;
            $counter++;
        }
        
        return $code;
    }

    private function sendRewardNotifications($customerReward, $loyaltyTier, $customer)
    {
        try {
            $branch = Branch::find($customerReward->branch_id);
            $owner = OwnerAccount::where('id', $loyaltyTier->owner_account_id)->first();
            $staffs = StaffAccount::where('branch_id', $customerReward->branch_id)
                ->where('active', 1)
                ->get();
            $actor = Auth::guard('customer')->user();

            if ($owner) {
                Notification::send($owner, new CustomerRewardOwnerNotification(
                    $customerReward,
                    $customer,
                    $branch,
                    $loyaltyTier,
                    $actor,
                    'claimed',
                ));
            }

            if ($staffs->isNotEmpty()) {
                Notification::send($staffs, new CustomerRewardStaffNotification(
                    $customerReward,
                    $customer,
                    $branch,
                    $loyaltyTier,
                    $actor,
                    'claimed',
                ));
            }

            Notification::send($customer, new CustomerRewardNotification(
                $customerReward,
                $customer,
                $branch,
                $loyaltyTier,
                'claimed',
            ));
        } catch (\Exception $e) {
            Log::error('Error sending reward notifications: ' . $e->getMessage());
        }
    }

    public function getRewardDetails($rewardId)
    {
        try {
            $customer = Auth::guard('customer')->user();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required.'
                ], 401);
            }

            $reward = CustomerReward::with([
                'loyaltyTier', 
                'loyaltyTier.branch', 
                'loyaltyTier.redeemableItem',
                'loyaltyTier.redeemableItem.targetService',
                'loyaltyTier.redeemableItem.targetService.serviceCategory',
                'loyaltyTier.redeemableItem.targetProduct',
                'loyaltyTier.redeemableItem.targetProduct.productIngredients',
                'loyaltyTier.redeemableItem.targetProduct.productIngredients.ingredient',
                'branch',
                'redeemedAtBranch',
                'redemptions'
            ])
                ->where('customer_account_id', $customer->id)
                ->where('id', $rewardId)
                ->where('active', 1)
                ->first();

            if (!$reward) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reward not found.'
                ], 404);
            }

            $reward->is_expired = $reward->expiration_date ? Carbon::now()->gt(Carbon::parse($reward->expiration_date)) : false;
            $reward->days_left = $reward->expiration_date ? Carbon::now()->diffInDays(Carbon::parse($reward->expiration_date), false) : 'N/A';
            $reward->status_label = $this->getStatusLabel($reward);
            $reward->redemption_status_label = $this->getRedemptionStatusLabel($reward);

            if ($reward->loyaltyTier && $reward->loyaltyTier->redeemableItem) {
                $reward->reward_type_label = $this->getRewardTypeLabel($reward->loyaltyTier->redeemableItem->reward_type);
                $reward->value_display = $this->getRedemptionValueDisplay($reward->loyaltyTier->redeemableItem);
                
                $targetDetails = $reward->loyaltyTier->redeemableItem->target_details;
                if ($targetDetails) {
                    $reward->target_details = $targetDetails;
                }
            }

            if ($reward->redemptions && $reward->redemptions->isNotEmpty()) {
                $reward->redemption_details = $reward->redemptions->first();
            }

            return response()->json([
                'success' => true,
                'data' => $reward
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getRewardDetails: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading reward details: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getStatusLabel($reward)
    {
        $statusMap = [
            self::CLAIM_STATUS_PENDING => 'Pending Approval',
            self::CLAIM_STATUS_CLAIMED => 'Claimed',
            self::CLAIM_STATUS_DECLINED => 'Declined',
            self::CLAIM_STATUS_EXPIRED => 'Expired',
        ];
        return $statusMap[$reward->claim_status] ?? 'Unknown';
    }

    private function getRedemptionStatusLabel($reward)
    {
        $statusMap = [
            'pending' => 'Pending',
            'ready' => 'Ready for Redemption',
            'redeemed' => 'Redeemed',
            'cancelled' => 'Cancelled',
        ];
        return $statusMap[$reward->redemption_status] ?? 'Unknown';
    }

    public function getTransactionHistory(Request $request)
    {
        try {
            $customer = Auth::guard('customer')->user();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required.'
                ], 401);
            }

            $transactions = CustomerReward::with([
                'loyaltyTier', 
                'loyaltyTier.redeemableItem',
                'loyaltyTier.redeemableItem.targetService',
                'loyaltyTier.redeemableItem.targetProduct'
            ])
            ->where('customer_account_id', $customer->id)
            ->where('active', 1)
            ->orderBy('date_created', 'desc')
            ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $transactions
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getTransactionHistory: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading transaction history: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getVoucherDetails($voucherCode)
    {
        try {
            $reward = CustomerReward::with([
                'customer',
                'loyaltyTier',
                'loyaltyTier.redeemableItem',
                'loyaltyTier.redeemableItem.targetService',
                'loyaltyTier.redeemableItem.targetProduct',
                'branch',
                'redeemedAtBranch',
                'redemptions'
            ])
            ->where('voucher_code', $voucherCode)
            ->where('active', 1)
            ->first();

            if (!$reward) {
                return response()->json([
                    'success' => false,
                    'message' => 'Voucher not found.'
                ], 404);
            }

            $isExpired = $reward->expiration_date ? Carbon::now()->gt(Carbon::parse($reward->expiration_date)) : false;

            if ($isExpired) {
                return response()->json([
                    'success' => false,
                    'message' => 'This voucher has expired.',
                    'data' => $reward
                ], 400);
            }

            if ($reward->redemption_status === self::REDEMPTION_STATUS_REDEEMED) {
                return response()->json([
                    'success' => false,
                    'message' => 'This voucher has already been redeemed.',
                    'data' => $reward
                ], 400);
            }

            if ($reward->redemption_status === self::REDEMPTION_STATUS_CANCELLED) {
                return response()->json([
                    'success' => false,
                    'message' => 'This voucher has been cancelled.',
                    'data' => $reward
                ], 400);
            }

            if ($reward->redemption_status !== self::REDEMPTION_STATUS_READY) {
                return response()->json([
                    'success' => false,
                    'message' => 'This voucher is not yet ready for redemption.',
                    'data' => $reward,
                    'status' => $this->getRedemptionStatusLabel($reward)
                ], 400);
            }

            if ($reward->loyaltyTier && $reward->loyaltyTier->redeemableItem) {
                $reward->reward_type_label = $this->getRewardTypeLabel($reward->loyaltyTier->redeemableItem->reward_type);
                $reward->value_display = $this->getRedemptionValueDisplay($reward->loyaltyTier->redeemableItem);
                $targetDetails = $reward->loyaltyTier->redeemableItem->target_details;
                if ($targetDetails) {
                    $reward->target_details = $targetDetails;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $reward,
                'voucher_code' => $voucherCode,
                'monetary_value' => $reward->monetary_value,
                'customer_name' => $reward->customer ? $reward->customer->first_name . ' ' . $reward->customer->last_name : null,
                'can_be_redeemed' => true
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getVoucherDetails: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading voucher details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get redemption history for the customer
     *
     * FIX: The frontend "view details" modal calls this endpoint with an
     * `?id=` query param, expecting to look up ONE specific redemption.
     * Previously that param was silently ignored — the endpoint always ran
     * the full paginated list query, so if the requested redemption wasn't
     * on the currently-configured page/filter, the frontend's `.find()`
     * would come back empty. We now short-circuit and return exactly that
     * single record (wrapped in the same `data.data` array shape the
     * frontend already expects) whenever `id` is present.
     *
     * FIX: All monetary fields (`original_price`, `discount_amount`,
     * `final_amount`, `computed_final_amount`) are now explicitly cast to
     * `(float)` before being returned. Laravel/MySQL decimal columns often
     * serialize to JSON as numeric STRINGS (e.g. "150.00"), and JS's
     * `.toFixed()` only exists on the Number prototype — calling it on a
     * string throws `TypeError: originalPrice.toFixed is not a function`,
     * which is exactly the error you were seeing.
     */
    public function getRedemptionHistory(Request $request)
    {
        try {
            $customer = Auth::guard('customer')->user();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required.'
                ], 401);
            }

            // ------------------------------------------------------------
            // FIX: Single-record lookup for the "view details" modal.
            // ------------------------------------------------------------
            if ($request->filled('id')) {
                $redemption = RewardRedemption::with([
                    'customerReward',
                    'customerReward.loyaltyTier',
                    'customerReward.loyaltyTier.redeemableItem',
                    'serviceName',
                    'serviceCategory',
                    'product',
                    'branch',
                    'booking',
                    'order'
                ])
                ->where('customer_account_id', $customer->id)
                ->where('active', 1)
                ->where('id', $request->input('id'))
                ->first();

                if (!$redemption) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Redemption not found.'
                    ], 404);
                }

                $this->applyNumericCasts($redemption);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'data' => [$redemption],
                    ],
                ]);
            }

            $perPage = $request->get('per_page', 15);
            $filter = $request->get('filter', 'all');
            $search = $request->get('search', '');

            $query = RewardRedemption::with([
                'customerReward',
                'customerReward.loyaltyTier',
                'customerReward.loyaltyTier.redeemableItem',
                'serviceName',
                'serviceCategory',
                'product',
                'branch',
                'booking',
                'order'
            ])
            ->where('customer_account_id', $customer->id)
            ->where('active', 1);

            if ($filter !== 'all') {
                if ($filter === 'service') {
                    $query->where('target_type', 'service');
                } elseif ($filter === 'product') {
                    $query->where('target_type', 'product');
                } else {
                    $query->where('reward_type', $filter);
                }
            }

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('receipt_number', 'LIKE', "%{$search}%")
                      ->orWhereHas('customerReward', function($subQ) use ($search) {
                          $subQ->where('voucher_code', 'LIKE', "%{$search}%");
                      })
                      ->orWhereHas('serviceName', function($subQ) use ($search) {
                          $subQ->where('service_name', 'LIKE', "%{$search}%");
                      })
                      ->orWhereHas('serviceCategory', function($subQ) use ($search) {
                          $subQ->where('service_category', 'LIKE', "%{$search}%");
                      })
                      ->orWhereHas('product', function($subQ) use ($search) {
                          $subQ->where('product_name', 'LIKE', "%{$search}%");
                      });
                });
            }

            $history = $query->orderBy('redeemed_at', 'desc')->paginate($perPage);

            // Transform to include accessor values
            $history->getCollection()->transform(function ($item) {
                $this->applyNumericCasts($item);
                $item->redeemed_by_name = $item->redeemed_by_name;
                $item->redeemed_by_role = $item->redeemed_by_role;
                return $item;
            });

            $summary = [
                'total_redemptions' => $query->count(),
                'total_free_services' => $query->where('reward_type', 'free_service')->count(),
                'total_free_products' => $query->where('reward_type', 'free_product')->count(),
                'total_fixed_discounts' => $query->where('reward_type', 'fixed_discount')->count(),
                'total_percentage_discounts' => $query->where('reward_type', 'percentage_discount')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $history,
                'summary' => $summary
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getRedemptionHistory: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error loading redemption history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Attach explicitly numeric (float) versions of the price/discount
     * fields onto a RewardRedemption record so the frontend never receives
     * a numeric-looking string where it expects a JS number.
     */
    private function applyNumericCasts(RewardRedemption $item): void
    {
        $originalPrice = (float) ($item->serviceName->price ?? $item->product->selling_price ?? 0);
        $discountAmount = (float) ($item->discount_amount ?? 0);
        $finalAmount = (float) ($item->final_amount ?? max(0, $originalPrice - $discountAmount));

        $item->original_price = $originalPrice;
        $item->discount_amount = $discountAmount;
        $item->final_amount = $finalAmount;
        $item->computed_final_amount = max(0, $originalPrice - $discountAmount);
        $item->discount_value = $item->discount_value !== null ? (float) $item->discount_value : null;
    }

    /**
     * Show redemption history page
     */
    public function showRedemptionHistory(Request $request)
    {
        try {
            $customer = Auth::guard('customer')->user();

            if (!$customer) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Authentication required.',
                        'redirect' => route('showLoginForm')
                    ], 401);
                }
                return redirect()->route('showLoginForm')->with('error', 'Please login to view your redemption history.');
            }

            $perPage = $request->get('per_page', 15);

            $redemptionHistory = RewardRedemption::with([
                'customerReward',
                'customerReward.loyaltyTier',
                'customerReward.loyaltyTier.redeemableItem',
                'serviceName',
                'serviceCategory',
                'product',
                'branch',
                'booking',
                'order'
            ])
            ->where('customer_account_id', $customer->id)
            ->where('active', 1)
            ->orderBy('redeemed_at', 'desc')
            ->paginate($perPage);

            // Add original price (and casted numeric fields) from service_names / products
            $redemptionHistory->getCollection()->transform(function ($item) {
                $this->applyNumericCasts($item);
                return $item;
            });

            $summary = [
                'total_redemptions' => $redemptionHistory->total(),
                'total_free_services' => RewardRedemption::where('customer_account_id', $customer->id)
                    ->where('active', 1)
                    ->where('reward_type', 'free_service')
                    ->count(),
                'total_free_products' => RewardRedemption::where('customer_account_id', $customer->id)
                    ->where('active', 1)
                    ->where('reward_type', 'free_product')
                    ->count(),
                'total_fixed_discounts' => RewardRedemption::where('customer_account_id', $customer->id)
                    ->where('active', 1)
                    ->where('reward_type', 'fixed_discount')
                    ->count(),
                'total_percentage_discounts' => RewardRedemption::where('customer_account_id', $customer->id)
                    ->where('active', 1)
                    ->where('reward_type', 'percentage_discount')
                    ->count(),
            ];

            return view('customer.my_rewards.redemption_history', [
                'redemptionHistory' => $redemptionHistory,
                'summary' => $summary,
                'customer' => $customer
            ]);

        } catch (\Exception $e) {
            Log::error('Error in showRedemptionHistory: ' . $e->getMessage(), [
                'customer_id' => Auth::guard('customer')->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Error loading redemption history: ' . $e->getMessage());
        }
    }

    /**
     * Redeem a reward against a booking
     */
    public function redeemReward(Request $request)
    {
        try {
            $customer = Auth::guard('customer')->user();
            if (!$customer) {
                return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
            }

            $request->validate([
                'customer_reward_id' => 'required|exists:customer_rewards,id',
                'booking_id' => 'required|exists:bookings,id',
                'service_name_id' => 'nullable|exists:service_names,id',
                'service_category_id' => 'nullable|exists:service_categories,id',
                'original_amount' => 'required|numeric|min:0',
            ]);

            DB::beginTransaction();

            $customerReward = CustomerReward::with(['loyaltyTier', 'loyaltyTier.redeemableItem'])
                ->where('id', $request->customer_reward_id)
                ->where('customer_account_id', $customer->id)
                ->where('active', 1)
                ->where('redemption_status', self::REDEMPTION_STATUS_READY)
                ->first();

            if (!$customerReward) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Reward not found or not ready for redemption.'
                ], 404);
            }

            if ($customerReward->isExpired()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'This reward has expired.'
                ], 400);
            }

            $redeemableItem = $customerReward->loyaltyTier->redeemableItem;
            $originalAmount = $request->original_amount;
            $discountAmount = 0;
            $finalAmount = $originalAmount;

            switch ($redeemableItem->reward_type) {
                case 'free_service':
                case 'free_product':
                    $discountAmount = $originalAmount;
                    $finalAmount = 0;
                    break;
                case 'fixed_discount':
                    $discountAmount = min($redeemableItem->monetary_value, $originalAmount);
                    $finalAmount = $originalAmount - $discountAmount;
                    break;
                case 'percentage_discount':
                    $discountAmount = ($redeemableItem->discount_percentage / 100) * $originalAmount;
                    $discountAmount = min($discountAmount, $originalAmount);
                    $finalAmount = $originalAmount - $discountAmount;
                    break;
            }

            $redemption = RewardRedemption::create([
                'uuid' => (string) Str::uuid(),
                'customer_reward_id' => $customerReward->id,
                'customer_account_id' => $customer->id,
                'booking_id' => $request->booking_id,
                'service_name_id' => $request->service_name_id,
                'service_category_id' => $request->service_category_id,
                'product_id' => $request->product_id,
                'reward_type' => $redeemableItem->reward_type,
                'target_type' => $redeemableItem->reward_type === 'free_product' ? 'product' : 'service',
                'discount_value' => $redeemableItem->discount_percentage ?? $redeemableItem->monetary_value,
                'discount_amount' => $discountAmount,
                'original_amount' => $originalAmount,
                'final_amount' => $finalAmount,
                'receipt_number' => $request->receipt_number,
                'redeemed_by' => auth()->guard('staff')->id() ?? null,
                'redeemed_by_type' => auth()->guard('staff')->check() ? 'StaffAccount' : null,
                'branch_id' => $request->branch_id ?? $customerReward->branch_id,
                'notes' => $request->notes,
                'redeemed_at' => now(),
                'date_created' => now(),
                'active' => 1,
            ]);

            $customerReward->redemption_status = self::REDEMPTION_STATUS_REDEEMED;
            $customerReward->redeemed_at = now();
            $customerReward->redeemed_at_branch_id = $request->branch_id ?? $customerReward->branch_id;
            $customerReward->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reward redeemed successfully!',
                'data' => [
                    'redemption' => $redemption,
                    'discount_amount' => $discountAmount,
                    'final_amount' => $finalAmount,
                    'savings' => $discountAmount,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error redeeming reward: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to redeem reward: ' . $e->getMessage()
            ], 500);
        }
    }
}