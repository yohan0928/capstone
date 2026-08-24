<?php

namespace App\Http\Controllers\Staff;

use Carbon\Carbon;
use App\Models\Branch;
use App\Models\Booking;
use App\Models\LoyaltyTier;
use Illuminate\Http\Request;
use App\Models\CustomerReward;
use App\Models\CustomerAccount;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\StaffActivityLogger;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerRewardController extends Controller
{
    /**
     * Display a listing of customer rewards tracking.
     */
    public function index(Request $request)
    {
        try {
            $staff = Auth::guard('staff')->user();

            if (!$staff) {
                throw new \Exception('No authenticated staff user');
            }

            $ownerId = $staff->owner_account_id;
            $staffBranchId = $staff->branch_id;

            // Get only the staff's branch for filter dropdown
            $branches = Branch::where('owner_account_id', $ownerId)
                ->where('id', $staffBranchId)
                ->where('active', 1)
                ->select('id', 'branch_name')
                ->get();

            // Get active loyalty tiers for staff's branch
            $loyaltyTiers = LoyaltyTier::with(['branch'])
                ->where('owner_account_id', $ownerId)
                ->where(function ($query) use ($staffBranchId) {
                    $query->where('branch_id', $staffBranchId)
                        ->orWhereNull('branch_id');
                })
                ->where('active', 1)
                ->get();

            // Get customer reward tracking data filtered by staff branch
            $customerRewards = $this->getCustomerRewardTracking($ownerId, $staffBranchId, $request, $loyaltyTiers);

            // Calculate stats filtered by staff branch
            $stats = $this->calculateStats($ownerId, $staffBranchId);

            // Prepare dynamic data structures for Alpine JS binding
            $responseData = [
                'success' => true,
                'data' => $customerRewards->items(),
                'pagination' => [
                    'current_page' => $customerRewards->currentPage(),
                    'last_page' => $customerRewards->lastPage(),
                    'per_page' => $customerRewards->perPage(),
                    'total' => $customerRewards->total(),
                    'from' => $customerRewards->firstItem(),
                    'to' => $customerRewards->lastItem(),
                ],
                'stats' => $stats,
                'branches' => $branches,
                'loyalty_tiers' => $loyaltyTiers
            ];

            // Return JSON for AJAX requests
            if ($request->ajax() || $request->has('ajax')) {
                return response()->json($responseData);
            }

            // Normal load
            return view('staff.customer_rewards.tracking', [
                'customersJson' => json_encode($customerRewards->items()),
                'paginationJson' => json_encode([
                    'current_page' => $customerRewards->currentPage(),
                    'last_page' => $customerRewards->lastPage(),
                    'per_page' => $customerRewards->perPage(),
                    'total' => $customerRewards->total(),
                    'from' => $customerRewards->firstItem(),
                    'to' => $customerRewards->lastItem(),
                ]),
                'statsJson' => json_encode($stats),
                'branchesJson' => json_encode($branches),
                'loyaltyTiersJson' => json_encode($loyaltyTiers),
                // Blade View Fallbacks
                'customerRewards' => $customerRewards,
                'branches' => $branches,
                'stats' => $stats,
                'loyaltyTiers' => $loyaltyTiers
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in Staff CustomerRewardController@index: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            $emptyPaginator = new LengthAwarePaginator([], 0, 50, 1);
            $emptyStats = [
                'total_earned_rewards' => 0,
                'redeemed_rewards' => 0,
                'available_rewards' => 0,
                'unique_customers' => 0,
                'potential_rewards' => 0
            ];

            if ($request->ajax() || $request->has('ajax')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error loading customer rewards: ' . $e->getMessage(),
                    'data' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => 50,
                        'total' => 0,
                        'from' => null,
                        'to' => null,
                    ],
                    'stats' => $emptyStats,
                    'branches' => [],
                    'loyalty_tiers' => []
                ], 500);
            }

            return view('staff.customer_rewards.tracking', [
                'customersJson' => json_encode([]),
                'paginationJson' => json_encode([
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 50,
                    'total' => 0,
                    'from' => null,
                    'to' => null,
                ]),
                'statsJson' => json_encode($emptyStats),
                'branchesJson' => json_encode([]),
                'loyaltyTiersJson' => json_encode([]),
                'customerRewards' => $emptyPaginator,
                'branches' => [],
                'stats' => $emptyStats,
                'loyaltyTiers' => [],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get customer reward tracking data with filters.
     */
    private function getCustomerRewardTracking($ownerId, $staffBranchId, $request, $loyaltyTiers)
    {
        try {
            $query = CustomerAccount::with([
                'bookings' => function ($query) use ($ownerId, $staffBranchId) {
                    $query
                        ->with(['branch', 'serviceName'])
                        ->whereHas('branch', function ($q) use ($ownerId, $staffBranchId) {
                            $q->where('owner_account_id', $ownerId)
                              ->where('id', $staffBranchId);
                        })
                        ->where('booking_status', 4)
                        ->where('active', 1)
                        ->orderBy('date_start', 'desc');
                },
                'rewards' => function ($query) use ($ownerId) {
                    $query
                        ->with(['loyaltyTier', 'loyaltyTier.branch', 'branch'])
                        ->whereHas('loyaltyTier', function ($q) use ($ownerId) {
                            $q->where('owner_account_id', $ownerId);
                        })
                        ->where('active', 1)
                        ->orderBy('date_created', 'desc');
                }
            ])
                ->whereHas('bookings', function ($query) use ($ownerId, $staffBranchId) {
                    $query
                        ->whereHas('branch', function ($q) use ($ownerId, $staffBranchId) {
                            $q->where('owner_account_id', $ownerId)
                              ->where('id', $staffBranchId);
                        })
                        ->where('booking_status', 4)
                        ->where('active', 1);
                });

            // Search Filter
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('first_name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$searchTerm}%")
                        ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('contact_no', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Bookings depth filter
            if ($request->filled('min_bookings')) {
                $minBookings = (int) $request->min_bookings;
                $query->whereHas('bookings', function ($q) use ($minBookings) {
                    $q->where('booking_status', 4)
                        ->where('active', 1)
                        ->havingRaw('COUNT(*) >= ?', [$minBookings]);
                });
            }

            $customers = $query
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->paginate(50);

            // Compute actual reward points and levels
            foreach ($customers as $customer) {
                $completedBookings = $customer->bookings->where('booking_status', 4);
                $customer->total_completed_bookings = $completedBookings->count();
                $customer->reward_progress = $this->calculateCustomerProgress($customer, $loyaltyTiers, $staffBranchId);
                $customer->earned_rewards_count = $customer->rewards ? $customer->rewards->count() : 0;
            }

            return $customers;
        } catch (\Exception $e) {
            \Log::error('Error in Staff getCustomerRewardTracking: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return new LengthAwarePaginator([], 0, 50, 1);
        }
    }

    /**
     * Calculate customer progress for all loyalty tiers based on actual rewards.
     */
    private function calculateCustomerProgress($customer, $loyaltyTiers, $staffBranchId)
    {
        $progress = [];

        foreach ($loyaltyTiers as $tier) {
            $customerReward = $customer->rewards->first(function ($reward) use ($tier) {
                return $reward->loyalty_tier_id === $tier->id;
            });

            $isEarned = !is_null($customerReward);
            $isClaimed = false;
            $isPending = false;
            $isExpired = false;
            $isDeclined = false;

            $requiredBookings = $tier->required_bookings ?? $tier->booking_required ?? 0;
            $bookingCount = $this->getCustomerBookingCountForTier($customer, $tier, $staffBranchId);
            $progressPercentage = 0;

            if ($customerReward) {
                $isClaimed = $customerReward->claim_status == CustomerReward::CLAIM_STATUS_CLAIMED;
                $isPending = $customerReward->claim_status == CustomerReward::CLAIM_STATUS_PENDING;
                $isExpired = $customerReward->isExpired();
                $isDeclined = $customerReward->claim_status == CustomerReward::CLAIM_STATUS_DECLINED;
                $progressPercentage = 100;
            } else {
                $progressPercentage = $requiredBookings > 0
                    ? min(($bookingCount / $requiredBookings) * 100, 100)
                    : 0;
            }

            $progress[] = [
                'loyalty_tier' => $tier,
                'booking_count' => $bookingCount,
                'booking_required' => $requiredBookings,
                'progress_percentage' => $progressPercentage,
                'is_earned' => $isEarned,
                'is_claimed' => $isClaimed,
                'is_pending' => $isPending,
                'is_expired' => $isExpired,
                'is_declined' => $isDeclined,
                'customer_reward' => $customerReward,
                'reward_type_label' => $this->getRewardTypeLabel($tier),
                'value_display' => $this->getRewardValueDisplay($tier),
            ];
        }

        return $progress;
    }

    /**
     * Get reward type label.
     */
    private function getRewardTypeLabel($tier)
    {
        if (!$tier->redeemableItem) {
            return 'Custom';
        }
        
        $labels = [
            'free_service' => 'Free Service',
            'free_product' => 'Free Product',
            'fixed_discount' => 'Fixed Discount',
            'percentage_discount' => 'Percentage Discount'
        ];
        
        return $labels[$tier->redeemableItem->reward_type] ?? 'Custom';
    }

    /**
     * Get reward value display.
     */
    private function getRewardValueDisplay($tier)
    {
        if (!$tier->redeemableItem) {
            return 'N/A';
        }
        
        $item = $tier->redeemableItem;
        
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

    /**
     * Get customer booking count for a specific tier.
     */
    private function getCustomerBookingCountForTier($customer, $tier, $staffBranchId)
    {
        if (!$customer->bookings || $customer->bookings->isEmpty()) {
            return 0;
        }

        // Get completed bookings matching staff's branch
        $completedBookings = $customer->bookings
            ->where('booking_status', 4)
            ->where('branch_id', $staffBranchId);

        // Filter by date range if specified
        if ($tier->date_start || $tier->date_end) {
            $completedBookings = $completedBookings->filter(function ($booking) use ($tier) {
                $bookingDate = Carbon::parse($booking->date_start);
                
                if ($tier->date_start && $bookingDate->lt(Carbon::parse($tier->date_start))) {
                    return false;
                }
                if ($tier->date_end && $bookingDate->gt(Carbon::parse($tier->date_end))) {
                    return false;
                }
                return true;
            });
        }

        // Get UNIQUE dates
        $uniqueDates = $completedBookings->pluck('date_start')->map(function ($date) {
            return Carbon::parse($date)->format('Y-m-d');
        })->unique()->values();

        // Check if streak tier or frequent tier
        $isStreak = isset($tier->booking_type) && $tier->booking_type == 0;
        
        if ($isStreak) {
            return $this->calculateConsecutiveStreakFromRecent($uniqueDates);
        } else {
            return $uniqueDates->count();
        }
    }

    /**
     * Calculate consecutive streak from most recent date.
     */
    private function calculateConsecutiveStreakFromRecent($uniqueDates)
    {
        if ($uniqueDates->isEmpty()) {
            return 0;
        }

        $sortedDates = $uniqueDates->sort()->values();
        $datesCount = $sortedDates->count();
        $currentStreak = 1;

        for ($i = $datesCount - 1; $i > 0; $i--) {
            $currentDate = Carbon::parse($sortedDates[$i]);
            $previousDate = Carbon::parse($sortedDates[$i - 1]);

            if ($currentDate->diffInDays($previousDate) === 1) {
                $currentStreak++;
            } else {
                break;
            }
        }

        return $currentStreak;
    }

    /**
     * Calculate statistics for the dashboard.
     */
    private function calculateStats($ownerId, $staffBranchId)
    {
        try {
            $totalEarnedRewards = CustomerReward::whereHas('loyaltyTier', function ($q) use ($ownerId, $staffBranchId) {
                $q->where('owner_account_id', $ownerId)
                  ->where(function ($query) use ($staffBranchId) {
                      $query->where('branch_id', $staffBranchId)
                            ->orWhereNull('branch_id');
                  });
            })->where('active', 1)->count();

            $redeemedRewards = CustomerReward::whereHas('loyaltyTier', function ($q) use ($ownerId, $staffBranchId) {
                $q->where('owner_account_id', $ownerId)
                  ->where(function ($query) use ($staffBranchId) {
                      $query->where('branch_id', $staffBranchId)
                            ->orWhereNull('branch_id');
                  });
            })->where('claim_status', CustomerReward::CLAIM_STATUS_CLAIMED)
              ->where('active', 1)->count();

            $availableRewards = CustomerReward::whereHas('loyaltyTier', function ($q) use ($ownerId, $staffBranchId) {
                $q->where('owner_account_id', $ownerId)
                  ->where(function ($query) use ($staffBranchId) {
                      $query->where('branch_id', $staffBranchId)
                            ->orWhereNull('branch_id');
                  });
            })->where('claim_status', CustomerReward::CLAIM_STATUS_CLAIMED)
              ->where('redemption_status', '!=', CustomerReward::REDEMPTION_STATUS_REDEEMED)
              ->where('active', 1)->count();

            $uniqueCustomers = CustomerAccount::whereHas('bookings', function ($query) use ($ownerId, $staffBranchId) {
                $query->whereHas('branch', function ($q) use ($ownerId, $staffBranchId) {
                    $q->where('owner_account_id', $ownerId)
                      ->where('id', $staffBranchId);
                })->where('booking_status', 4)->where('active', 1);
            })->count();

            $potentialRewards = $this->calculatePotentialRewards($ownerId, $staffBranchId);

            return [
                'total_earned_rewards' => $totalEarnedRewards,
                'redeemed_rewards' => $redeemedRewards,
                'available_rewards' => $availableRewards,
                'unique_customers' => $uniqueCustomers,
                'potential_rewards' => $potentialRewards,
            ];
        } catch (\Exception $e) {
            \Log::error('Error in calculateStats: ' . $e->getMessage());
            return [
                'total_earned_rewards' => 0,
                'redeemed_rewards' => 0,
                'available_rewards' => 0,
                'unique_customers' => 0,
                'potential_rewards' => 0,
            ];
        }
    }

    /**
     * Calculate potential rewards.
     */
    private function calculatePotentialRewards($ownerId, $staffBranchId)
    {
        try {
            $loyaltyTiers = LoyaltyTier::where('owner_account_id', $ownerId)
                ->where(function ($query) use ($staffBranchId) {
                    $query->where('branch_id', $staffBranchId)
                          ->orWhereNull('branch_id');
                })
                ->where('active', 1)
                ->get();

            if ($loyaltyTiers->isEmpty()) {
                return 0;
            }

            $potentialCount = 0;

            $customers = CustomerAccount::whereHas('bookings', function ($query) use ($ownerId, $staffBranchId) {
                $query->whereHas('branch', function ($q) use ($ownerId, $staffBranchId) {
                    $q->where('owner_account_id', $ownerId)
                      ->where('id', $staffBranchId);
                })->where('booking_status', 4)->where('active', 1);
            })->with(['bookings' => function ($query) use ($ownerId, $staffBranchId) {
                $query->whereHas('branch', function ($q) use ($ownerId, $staffBranchId) {
                    $q->where('owner_account_id', $ownerId)
                      ->where('id', $staffBranchId);
                })->where('booking_status', 4)->where('active', 1);
            }])->get();

            foreach ($loyaltyTiers as $tier) {
                foreach ($customers as $customer) {
                    if (!$customer->bookings) {
                        continue;
                    }
                    
                    $bookingCount = $this->getCustomerBookingCountForTier($customer, $tier, $staffBranchId);
                    $requiredBookings = $tier->required_bookings ?? $tier->booking_required ?? 0;

                    if ($bookingCount >= $requiredBookings) {
                        $existingReward = CustomerReward::where('customer_account_id', $customer->id)
                            ->where('loyalty_tier_id', $tier->id)
                            ->where('active', 1)
                            ->first();

                        if (!$existingReward) {
                            $potentialCount++;
                        }
                    }
                }
            }

            return $potentialCount;
        } catch (\Exception $e) {
            \Log::error('Error calculating potential rewards: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get customer progress details for the modal.
     */
    public function getCustomerProgress($customerId, Request $request)
    {
        try {
            $staff = Auth::guard('staff')->user();

            if (!$staff) {
                throw new \Exception('No authenticated staff user');
            }

            $ownerId = $staff->owner_account_id;
            $staffBranchId = $staff->branch_id;

            // Confirm bookings presence
            $hasBranchBookings = CustomerAccount::where('id', $customerId)
                ->whereHas('bookings', function ($query) use ($ownerId, $staffBranchId) {
                    $query->whereHas('branch', function ($q) use ($ownerId, $staffBranchId) {
                        $q->where('owner_account_id', $ownerId)
                          ->where('id', $staffBranchId);
                    })->where('booking_status', 4)->where('active', 1);
                })->exists();

            if (!$hasBranchBookings) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found or has no bookings in your branch'
                ], 404);
            }

            $customer = CustomerAccount::with([
                'bookings' => function ($query) use ($ownerId, $staffBranchId) {
                    $query->with(['branch', 'serviceName'])
                        ->whereHas('branch', function ($q) use ($ownerId, $staffBranchId) {
                            $q->where('owner_account_id', $ownerId)
                              ->where('id', $staffBranchId);
                        })
                        ->where('booking_status', 4)
                        ->where('active', 1)
                        ->orderBy('date_start', 'desc');
                },
                'rewards' => function ($query) use ($ownerId) {
                    $query->with(['loyaltyTier', 'loyaltyTier.branch', 'branch'])
                        ->whereHas('loyaltyTier', function ($q) use ($ownerId) {
                            $q->where('owner_account_id', $ownerId);
                        })
                        ->where('active', 1)
                        ->orderBy('date_created', 'desc');
                }
            ])->findOrFail($customerId);

            // Fetch active loyalty tiers
            $loyaltyTiers = LoyaltyTier::with(['branch'])
                ->where('owner_account_id', $ownerId)
                ->where(function ($query) use ($staffBranchId) {
                    $query->where('branch_id', $staffBranchId)
                          ->orWhereNull('branch_id');
                })
                ->where('active', 1)
                ->get();

            $progress = [];
            foreach ($loyaltyTiers as $tier) {
                $customerReward = $customer->rewards->first(function ($reward) use ($tier) {
                    return $reward->loyalty_tier_id === $tier->id;
                });

                $isEarned = !is_null($customerReward);
                $isClaimed = false;
                $isPending = false;
                $isExpired = false;
                $isDeclined = false;

                $requiredBookings = $tier->required_bookings ?? $tier->booking_required ?? 0;
                $bookingCount = $this->getCustomerBookingCountForTier($customer, $tier, $staffBranchId);
                $progressPercentage = 0;

                if ($customerReward) {
                    $isClaimed = $customerReward->claim_status == CustomerReward::CLAIM_STATUS_CLAIMED;
                    $isPending = $customerReward->claim_status == CustomerReward::CLAIM_STATUS_PENDING;
                    $isExpired = $customerReward->isExpired();
                    $isDeclined = $customerReward->claim_status == CustomerReward::CLAIM_STATUS_DECLINED;
                    $progressPercentage = 100;
                } else {
                    $progressPercentage = $requiredBookings > 0
                        ? min(($bookingCount / $requiredBookings) * 100, 100)
                        : 0;
                }

                $progress[] = [
                    'loyalty_tier' => $tier,
                    'booking_count' => $bookingCount,
                    'booking_required' => $requiredBookings,
                    'progress_percentage' => $progressPercentage,
                    'is_earned' => $isEarned,
                    'is_claimed' => $isClaimed,
                    'is_pending' => $isPending,
                    'is_expired' => $isExpired,
                    'is_declined' => $isDeclined,
                    'customer_reward' => $customerReward,
                    'reward_type_label' => $this->getRewardTypeLabel($tier),
                    'value_display' => $this->getRewardValueDisplay($tier),
                ];
            }

            $totalBookings = $customer->bookings ? $customer->bookings->count() : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'customer' => $customer,
                    'progress' => $progress,
                    'total_bookings' => $totalBookings
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in Staff getCustomerProgress: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading customer progress: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get reward details.
     */
    public function getCustomerRewardDetails($rewardId, Request $request)
    {
        try {
            $staff = Auth::guard('staff')->user();
            $ownerId = $staff->owner_account_id;
            $staffBranchId = $staff->branch_id;

            $reward = CustomerReward::with([
                'customer' => function ($query) {
                    $query->select('id', 'first_name', 'last_name', 'email', 'contact_no');
                },
                'loyaltyTier' => function ($query) {
                    $query->select('id', 'tier_name', 'reward_description', 'required_bookings', 'expiration_days');
                },
                'branch' => function ($query) {
                    $query->select('id', 'branch_name');
                }
            ])
                ->whereHas('loyaltyTier', function ($q) use ($ownerId) {
                    $q->where('owner_account_id', $ownerId);
                })
                ->where('branch_id', $staffBranchId)
                ->where('id', $rewardId)
                ->where('active', 1)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $reward
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching staff reward details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading reward details: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update Reward status (Approve / Decline) for dynamic claims.
     */
    public function updateRewardStatus(Request $request, $rewardId)
    {
        $request->validate([
            'action' => 'required|in:approve,decline',
            'decline_reason' => 'required_if:action,decline|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $staff = Auth::guard('staff')->user();
            $ownerId = $staff->owner_account_id;
            $staffBranchId = $staff->branch_id;

            $reward = CustomerReward::whereHas('loyaltyTier', function ($q) use ($ownerId) {
                $q->where('owner_account_id', $ownerId);
            })
                ->where('branch_id', $staffBranchId)
                ->where('id', $rewardId)
                ->where('active', 1)
                ->firstOrFail();

            if ($request->action === 'approve') {
                $reward->claim_status = CustomerReward::CLAIM_STATUS_CLAIMED;
                $reward->date_updated = now();
                $reward->save();
                $message = 'Reward approved successfully!';
                $action = 'approved';
            } else {
                $reward->claim_status = CustomerReward::CLAIM_STATUS_DECLINED;
                $reward->decline_reason = $request->decline_reason;
                $reward->date_updated = now();
                $reward->save();
                $message = 'Reward declined successfully!';
                $action = 'declined';
            }

            // Record action in logs
            StaffActivityLogger::logUpdateRewardStatus(
                $reward,
                $action,
                $request->decline_reason ?? null,
                $request
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $reward
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating reward status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update reward status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Staff Reward Processing triggers.
     */
    public function processRewards(Request $request)
    {
        try {
            DB::beginTransaction();

            $staff = Auth::guard('staff')->user();
            $ownerId = $staff->owner_account_id;
            $staffBranchId = $staff->branch_id;

            $loyaltyTiers = LoyaltyTier::where('owner_account_id', $ownerId)
                ->where(function ($query) use ($staffBranchId) {
                    $query->where('branch_id', $staffBranchId)
                          ->orWhereNull('branch_id');
                })
                ->where('active', 1)
                ->get();

            if ($loyaltyTiers->isEmpty()) {
                DB::commit();
                return response()->json(['success' => true, 'message' => 'No active loyalty tiers to process.']);
            }

            $customers = CustomerAccount::whereHas('bookings', function ($query) use ($ownerId, $staffBranchId) {
                $query->whereHas('branch', function ($q) use ($ownerId, $staffBranchId) {
                    $q->where('owner_account_id', $ownerId)
                      ->where('id', $staffBranchId);
                })->where('booking_status', 4)->where('active', 1);
            })->with(['bookings' => function ($query) use ($ownerId, $staffBranchId) {
                $query->whereHas('branch', function ($q) use ($ownerId, $staffBranchId) {
                    $q->where('owner_account_id', $ownerId)
                      ->where('id', $staffBranchId);
                })->where('booking_status', 4)->where('active', 1);
            }])->get();

            $newRewardsCreated = 0;
            $startTime = microtime(true);

            foreach ($customers as $customer) {
                foreach ($loyaltyTiers as $tier) {
                    $existingReward = CustomerReward::where('customer_account_id', $customer->id)
                        ->where('loyalty_tier_id', $tier->id)
                        ->where('active', 1)
                        ->exists();

                    if ($existingReward) {
                        continue;
                    }

                    $bookingCount = $this->getCustomerBookingCountForTier($customer, $tier, $staffBranchId);
                    $requiredBookings = $tier->required_bookings ?? $tier->booking_required ?? 0;

                    if ($bookingCount >= $requiredBookings) {
                        CustomerReward::create([
                            'customer_account_id' => $customer->id,
                            'loyalty_tier_id' => $tier->id,
                            'branch_id' => $staffBranchId,
                            'date_created' => now(),
                            'claim_status' => CustomerReward::CLAIM_STATUS_PENDING,
                            'redemption_status' => CustomerReward::REDEMPTION_STATUS_READY,
                            'active' => 1,
                        ]);
                        $newRewardsCreated++;
                    }
                }
            }

            $processingTime = round(microtime(true) - $startTime, 2);

            StaffActivityLogger::logProcessRewards(
                [
                    'new_rewards_created' => $newRewardsCreated,
                    'total_customers_processed' => $customers->count(),
                    'total_loyalty_tiers' => $loyaltyTiers->count(),
                    'processing_time' => $processingTime . ' seconds'
                ],
                $request
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Reward processing completed. {$newRewardsCreated} new rewards created.",
                'new_rewards_created' => $newRewardsCreated
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error processing rewards: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to process rewards: ' . $e->getMessage()
            ], 500);
        }
    }
}