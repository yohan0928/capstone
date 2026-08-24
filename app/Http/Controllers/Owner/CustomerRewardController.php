<?php

namespace App\Http\Controllers\Owner;

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
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerRewardController extends Controller
{
    /**
     * Display a listing of customer rewards tracking.
     */
    public function index(Request $request)
    {
        try {
            $owner = Auth::guard('owner')->user();

            if (!$owner) {
                throw new \Exception('No authenticated owner user');
            }

            $ownerId = $owner->id;

            // Get owner's branches for filter dropdown
            $branches = Branch::where('owner_account_id', $ownerId)
                ->where('active', 1)
                ->select('id', 'branch_name')
                ->get();

            // Get all active loyalty tiers for this owner
            $loyaltyTiers = LoyaltyTier::with(['branch'])
                ->where('owner_account_id', $ownerId)
                ->where('active', 1)
                ->get();

            // Get customer reward tracking data
            $customerRewards = $this->getCustomerRewardTracking($ownerId, $request, $loyaltyTiers);

            // Calculate stats
            $stats = $this->calculateStats($ownerId);

            // Prepare data for JSON response
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

            // For regular view, pass data as arrays to avoid issues with LengthAwarePaginator in JSON
            return view('owner.customer_rewards.tracking', [
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
                // Fallback for Blade
                'customerRewards' => $customerRewards,
                'branches' => $branches,
                'stats' => $stats,
                'loyaltyTiers' => $loyaltyTiers
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in CustomerRewardController@index: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Create empty paginator for consistent data structure
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

            return view('owner.customer_rewards.tracking', [
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
    private function getCustomerRewardTracking($ownerId, $request, $loyaltyTiers)
    {
        try {
            // Get distinct customers who have completed bookings (booking_status = 4)
            $query = CustomerAccount::with([
                'bookings' => function ($query) use ($ownerId) {
                    $query
                        ->with(['branch', 'serviceName'])
                        ->whereHas('branch', function ($q) use ($ownerId) {
                            $q->where('owner_account_id', $ownerId);
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
                ->whereHas('bookings', function ($query) use ($ownerId) {
                    $query
                        ->whereHas('branch', function ($q) use ($ownerId) {
                            $q->where('owner_account_id', $ownerId);
                        })
                        ->where('booking_status', 4)
                        ->where('active', 1);
                });

            // Apply search filter
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

            // Apply branch filter
            if ($request->filled('branch_id')) {
                $branchId = $request->branch_id;
                $query->whereHas('bookings', function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                        ->where('booking_status', 4)
                        ->where('active', 1);
                });
            }

            // Apply min bookings filter
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

            // Calculate reward progress for each customer based on actual rewards
            foreach ($customers as $customer) {
                // Count completed bookings for this customer
                $completedBookings = $customer->bookings->where('booking_status', 4);
                $customer->total_completed_bookings = $completedBookings->count();
                
                // Calculate progress based on rewards
                $customer->reward_progress = $this->calculateCustomerProgress($customer, $loyaltyTiers);
                
                // Count earned rewards from the rewards collection
                $customer->earned_rewards_count = $customer->rewards ? $customer->rewards->count() : 0;
                
                // Get claimed rewards count
                $customer->claimed_rewards_count = $customer->rewards 
                    ? $customer->rewards->where('claim_status', CustomerReward::CLAIM_STATUS_CLAIMED)->count() 
                    : 0;
                
                // Get redeemed rewards count
                $customer->redeemed_rewards_count = $customer->rewards 
                    ? $customer->rewards->where('redemption_status', CustomerReward::REDEMPTION_STATUS_REDEEMED)->count() 
                    : 0;
                
                // Get last booking date
                $lastBooking = $customer->bookings->first();
                $customer->last_booking_date = $lastBooking ? $lastBooking->date_start : null;
                $customer->last_booking_branch = $lastBooking && $lastBooking->branch ? $lastBooking->branch->branch_name : null;
                
                // Ensure bookings is always a collection
                if (!$customer->bookings) {
                    $customer->bookings = collect();
                }
            }

            return $customers;
        } catch (\Exception $e) {
            \Log::error('Error in getCustomerRewardTracking: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return new LengthAwarePaginator([], 0, 50, 1);
        }
    }

    /**
     * Calculate customer progress for all loyalty tiers based on actual rewards.
     */
    private function calculateCustomerProgress($customer, $loyaltyTiers)
    {
        $progress = [];

        foreach ($loyaltyTiers as $tier) {
            // Find the reward for this tier if it exists
            $customerReward = $customer->rewards->first(function ($reward) use ($tier) {
                return $reward->loyalty_tier_id === $tier->id;
            });

            $isEarned = !is_null($customerReward);
            $isClaimed = false;
            $isRedeemed = false;
            $isExpired = false;
            
            // Get the required bookings from the tier
            $requiredBookings = $tier->required_bookings ?? $tier->booking_required ?? 0;
            
            // Calculate booking count towards this tier
            $bookingCount = $this->getCustomerBookingCountForTier($customer, $tier);
            
            $progressPercentage = 0;

            if ($customerReward) {
                $isClaimed = $customerReward->claim_status == CustomerReward::CLAIM_STATUS_CLAIMED;
                $isRedeemed = $customerReward->redemption_status == CustomerReward::REDEMPTION_STATUS_REDEEMED;
                $isExpired = $customerReward->isExpired();
                
                $customerReward->days_left = $customerReward->days_left;
                
                // Progress is 100% since reward is earned
                $progressPercentage = 100;
            } else {
                // Calculate progress percentage based on bookings
                $progressPercentage = $requiredBookings > 0
                    ? min(($bookingCount / $requiredBookings) * 100, 100)
                    : 0;
            }

            // Get the appropriate status label
            $statusLabel = 'In Progress';
            $statusClass = 'bg-yellow-100 text-yellow-800';
            
            if ($isEarned) {
                if ($isExpired) {
                    $statusLabel = 'Expired';
                    $statusClass = 'bg-gray-100 text-gray-800';
                } elseif ($isRedeemed) {
                    $statusLabel = 'Redeemed';
                    $statusClass = 'bg-purple-100 text-purple-800';
                } elseif ($isClaimed) {
                    $statusLabel = 'Claimed';
                    $statusClass = 'bg-green-100 text-green-800';
                } else {
                    $statusLabel = 'Earned';
                    $statusClass = 'bg-blue-100 text-blue-800';
                }
            } else {
                // Check if progress is complete (reached 100%)
                if ($progressPercentage >= 100 && $requiredBookings > 0) {
                    $statusLabel = 'Ready to Claim';
                    $statusClass = 'bg-green-100 text-green-800';
                }
            }

            $progress[] = [
                'loyalty_tier' => $tier,
                'booking_count' => $bookingCount,
                'booking_required' => $requiredBookings,
                'progress_percentage' => $progressPercentage,
                'is_earned' => $isEarned,
                'is_claimed' => $isClaimed,
                'is_redeemed' => $isRedeemed,
                'is_expired' => $isExpired,
                'customer_reward' => $customerReward,
                'status_label' => $statusLabel,
                'status_class' => $statusClass,
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
    private function getCustomerBookingCountForTier($customer, $tier)
    {
        if (!$customer->bookings || $customer->bookings->isEmpty()) {
            return 0;
        }

        // Get only completed bookings (status = 4)
        $completedBookings = $customer->bookings->where('booking_status', 4);

        // Filter by branch if specified
        if ($tier->branch_id) {
            $completedBookings = $completedBookings->filter(function ($booking) use ($tier) {
                return $booking->branch && $booking->branch->id === $tier->branch_id;
            });
        }

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

        // Get UNIQUE dates (multiple bookings on same day count as 1 for streak)
        $uniqueDates = $completedBookings->pluck('date_start')->map(function ($date) {
            return Carbon::parse($date)->format('Y-m-d');
        })->unique()->values();

        // Check if this is a streak tier or frequent tier
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
    private function calculateStats($ownerId)
    {
        try {
            // Get earned rewards (all active rewards)
            $totalEarnedRewards = CustomerReward::whereHas('loyaltyTier', function ($q) use ($ownerId) {
                $q->where('owner_account_id', $ownerId);
            })->where('active', 1)->count();

            // Get claimed rewards
            $redeemedRewards = CustomerReward::whereHas('loyaltyTier', function ($q) use ($ownerId) {
                $q->where('owner_account_id', $ownerId);
            })->where('claim_status', CustomerReward::CLAIM_STATUS_CLAIMED)
              ->where('active', 1)->count();

            // Get pending rewards (rewards that are claimed but not redeemed)
            $availableRewards = CustomerReward::whereHas('loyaltyTier', function ($q) use ($ownerId) {
                $q->where('owner_account_id', $ownerId);
            })->where('claim_status', CustomerReward::CLAIM_STATUS_CLAIMED)
              ->where('redemption_status', '!=', CustomerReward::REDEMPTION_STATUS_REDEEMED)
              ->where('active', 1)->count();

            // Get unique customers with completed bookings
            $uniqueCustomers = CustomerAccount::whereHas('bookings', function ($query) use ($ownerId) {
                $query
                    ->whereHas('branch', function ($q) use ($ownerId) {
                        $q->where('owner_account_id', $ownerId);
                    })
                    ->where('booking_status', 4)
                    ->where('active', 1);
            })->count();

            // Calculate potential rewards
            $potentialRewards = $this->calculatePotentialRewards($ownerId);

            return [
                'total_earned_rewards' => $totalEarnedRewards,
                'redeemed_rewards' => $redeemedRewards,
                'available_rewards' => $availableRewards,
                'unique_customers' => $uniqueCustomers,
                'potential_rewards' => $potentialRewards,
            ];
        } catch (\Exception $e) {
            \Log::error('Error calculating stats: ' . $e->getMessage());
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
     * Calculate potential rewards (customers who qualify but haven't been processed).
     */
    private function calculatePotentialRewards($ownerId)
    {
        try {
            $loyaltyTiers = LoyaltyTier::where('owner_account_id', $ownerId)
                ->where('active', 1)
                ->get();

            if ($loyaltyTiers->isEmpty()) {
                return 0;
            }

            $potentialCount = 0;

            $customers = CustomerAccount::whereHas('bookings', function ($query) use ($ownerId) {
                $query
                    ->whereHas('branch', function ($q) use ($ownerId) {
                        $q->where('owner_account_id', $ownerId);
                    })
                    ->where('booking_status', 4)
                    ->where('active', 1);
            })->with(['bookings' => function ($query) use ($ownerId) {
                $query
                    ->whereHas('branch', function ($q) use ($ownerId) {
                        $q->where('owner_account_id', $ownerId);
                    })
                    ->where('booking_status', 4)
                    ->where('active', 1);
            }])->get();

            foreach ($loyaltyTiers as $tier) {
                foreach ($customers as $customer) {
                    if (!$customer->bookings) {
                        continue;
                    }
                    
                    $bookingCount = $this->getCustomerBookingCountForTier($customer, $tier);
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
            $owner = Auth::guard('owner')->user();
            $ownerId = $owner->id;

            // Get customer with bookings, rewards, and their progress
            $customer = CustomerAccount::with([
                'bookings' => function ($query) use ($ownerId) {
                    $query
                        ->with(['branch', 'serviceName'])
                        ->whereHas('branch', function ($q) use ($ownerId) {
                            $q->where('owner_account_id', $ownerId);
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
            ])->findOrFail($customerId);

            // Get all active loyalty tiers for this owner
            $loyaltyTiers = LoyaltyTier::with(['branch'])
                ->where('owner_account_id', $ownerId)
                ->where('active', 1)
                ->get();

            // Calculate progress for each loyalty tier based on actual rewards
            $progress = [];
            foreach ($loyaltyTiers as $tier) {
                // Find the reward for this tier if it exists
                $customerReward = $customer->rewards->first(function ($reward) use ($tier) {
                    return $reward->loyalty_tier_id === $tier->id;
                });

                $isEarned = !is_null($customerReward);
                $isClaimed = false;
                $isRedeemed = false;
                $isExpired = false;
                
                // Get the required bookings from the tier
                $requiredBookings = $tier->required_bookings ?? $tier->booking_required ?? 0;
                
                // Calculate booking count towards this tier
                $bookingCount = $this->getCustomerBookingCountForTier($customer, $tier);
                
                $progressPercentage = 0;

                if ($customerReward) {
                    $isClaimed = $customerReward->claim_status == CustomerReward::CLAIM_STATUS_CLAIMED;
                    $isRedeemed = $customerReward->redemption_status == CustomerReward::REDEMPTION_STATUS_REDEEMED;
                    $isExpired = $customerReward->isExpired();
                    
                    $customerReward->days_left = $customerReward->days_left;
                    
                    // Progress is 100% since reward is earned
                    $progressPercentage = 100;
                } else {
                    // Calculate progress percentage based on bookings
                    $progressPercentage = $requiredBookings > 0
                        ? min(($bookingCount / $requiredBookings) * 100, 100)
                        : 0;
                }

                // Get the appropriate status label
                $statusLabel = 'In Progress';
                $statusClass = 'bg-yellow-100 text-yellow-800';
                
                if ($isEarned) {
                    if ($isExpired) {
                        $statusLabel = 'Expired';
                        $statusClass = 'bg-gray-100 text-gray-800';
                    } elseif ($isRedeemed) {
                        $statusLabel = 'Redeemed';
                        $statusClass = 'bg-purple-100 text-purple-800';
                    } elseif ($isClaimed) {
                        $statusLabel = 'Claimed';
                        $statusClass = 'bg-green-100 text-green-800';
                    } else {
                        $statusLabel = 'Earned';
                        $statusClass = 'bg-blue-100 text-blue-800';
                    }
                } else {
                    // Check if progress is complete (reached 100%)
                    if ($progressPercentage >= 100 && $requiredBookings > 0) {
                        $statusLabel = 'Ready to Claim';
                        $statusClass = 'bg-green-100 text-green-800';
                    }
                }

                $progress[] = [
                    'loyalty_tier' => $tier,
                    'booking_count' => $bookingCount,
                    'booking_required' => $requiredBookings,
                    'progress_percentage' => $progressPercentage,
                    'is_earned' => $isEarned,
                    'is_claimed' => $isClaimed,
                    'is_redeemed' => $isRedeemed,
                    'is_expired' => $isExpired,
                    'customer_reward' => $customerReward,
                    'status_label' => $statusLabel,
                    'status_class' => $statusClass,
                    'reward_type_label' => $this->getRewardTypeLabel($tier),
                    'value_display' => $this->getRewardValueDisplay($tier),
                ];
            }

            // Get total completed bookings count
            $totalBookings = $customer->bookings ? $customer->bookings->count() : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'customer' => $customer,
                    'progress' => $progress,
                    'total_bookings' => $totalBookings,
                    'total_rewards' => $customer->rewards ? $customer->rewards->count() : 0,
                    'claimed_rewards' => $customer->rewards 
                        ? $customer->rewards->where('claim_status', CustomerReward::CLAIM_STATUS_CLAIMED)->count() 
                        : 0,
                    'redeemed_rewards' => $customer->rewards 
                        ? $customer->rewards->where('redemption_status', CustomerReward::REDEMPTION_STATUS_REDEEMED)->count() 
                        : 0,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getCustomerProgress: ' . $e->getMessage(), [
                'customer_id' => $customerId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading customer progress: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer reward details.
     */
    public function getCustomerRewardDetails($rewardId, Request $request)
    {
        try {
            if (Auth::guard('owner')->check()) {
                $owner = Auth::guard('owner')->user();
                $ownerId = $owner->id;

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
                    ->where('id', $rewardId)
                    ->where('active', 1)
                    ->firstOrFail();
            } else if (Auth::guard('staff')->check()) {
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
            } else {
                throw new \Exception('Unauthorized');
            }

            return response()->json([
                'success' => true,
                'data' => $reward
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching reward details: ' . $e->getMessage(), [
                'reward_id' => $rewardId,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error loading reward details: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Redeem a voucher (for staff).
     */
    public function redeemVoucher(Request $request)
    {
        $request->validate([
            'voucher_code' => 'required|string|exists:customer_rewards,voucher_code',
        ]);

        try {
            DB::beginTransaction();

            $staff = Auth::guard('staff')->user();
            $ownerId = $staff->owner_account_id;
            $staffBranchId = $staff->branch_id;

            $reward = CustomerReward::with(['customer', 'loyaltyTier'])
                ->whereHas('loyaltyTier', function ($q) use ($ownerId) {
                    $q->where('owner_account_id', $ownerId);
                })
                ->where('branch_id', $staffBranchId)
                ->where('voucher_code', $request->voucher_code)
                ->where('claim_status', CustomerReward::CLAIM_STATUS_CLAIMED)
                ->where('redemption_status', CustomerReward::REDEMPTION_STATUS_READY)
                ->where('active', 1)
                ->first();

            if (!$reward) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or already redeemed voucher code.'
                ], 400);
            }

            // Check if expired
            if ($reward->isExpired()) {
                $reward->claim_status = CustomerReward::CLAIM_STATUS_EXPIRED;
                $reward->save();
                return response()->json([
                    'success' => false,
                    'message' => 'This voucher has expired.'
                ], 400);
            }

            // Mark as redeemed
            $reward->redemption_status = CustomerReward::REDEMPTION_STATUS_REDEEMED;
            $reward->redeemed_at = now();
            $reward->redeemed_at_branch_id = $staffBranchId;
            $reward->date_updated = now();
            $reward->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Voucher redeemed successfully!',
                'data' => $reward
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error redeeming voucher: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to redeem voucher: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get voucher details by code (for QR scanning).
     */
    public function getVoucherDetails($code)
    {
        try {
            $staff = Auth::guard('staff')->user();
            $ownerId = $staff->owner_account_id;
            $staffBranchId = $staff->branch_id;

            $reward = CustomerReward::with([
                'customer' => function ($query) {
                    $query->select('id', 'first_name', 'last_name', 'email');
                },
                'loyaltyTier' => function ($query) {
                    $query->select('id', 'tier_name', 'reward_description', 'required_bookings');
                }
            ])
                ->whereHas('loyaltyTier', function ($q) use ($ownerId) {
                    $q->where('owner_account_id', $ownerId);
                })
                ->where('branch_id', $staffBranchId)
                ->where('voucher_code', $code)
                ->where('active', 1)
                ->first();

            if (!$reward) {
                return response()->json([
                    'success' => false,
                    'message' => 'Voucher not found.'
                ], 404);
            }

            $isExpired = $reward->isExpired();
            $isRedeemed = $reward->redemption_status == CustomerReward::REDEMPTION_STATUS_REDEEMED;

            return response()->json([
                'success' => true,
                'data' => $reward,
                'is_expired' => $isExpired,
                'is_redeemed' => $isRedeemed,
                'status' => $isExpired ? 'expired' : ($isRedeemed ? 'redeemed' : 'valid')
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting voucher details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading voucher details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get staff redemption history.
     */
    public function getStaffRedemptionHistory(Request $request)
    {
        try {
            $staff = Auth::guard('staff')->user();
            $ownerId = $staff->owner_account_id;
            $staffBranchId = $staff->branch_id;

            $history = CustomerReward::with([
                'customer' => function ($query) {
                    $query->select('id', 'first_name', 'last_name', 'email');
                },
                'loyaltyTier' => function ($query) {
                    $query->select('id', 'tier_name', 'reward_description');
                }
            ])
                ->whereHas('loyaltyTier', function ($q) use ($ownerId) {
                    $q->where('owner_account_id', $ownerId);
                })
                ->where('branch_id', $staffBranchId)
                ->where('redemption_status', CustomerReward::REDEMPTION_STATUS_REDEEMED)
                ->where('active', 1)
                ->orderBy('redeemed_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $history
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting redemption history: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading redemption history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export rewards data.
     */
    public function export(Request $request)
    {
        try {
            $owner = Auth::guard('owner')->user();
            $ownerId = $owner->id;

            $rewards = CustomerReward::with(['customer', 'loyaltyTier', 'branch'])
                ->whereHas('loyaltyTier', function ($q) use ($ownerId) {
                    $q->where('owner_account_id', $ownerId);
                })
                ->where('active', 1)
                ->get();

            // Prepare CSV data
            $csvData = [];
            $csvData[] = [
                'Customer Name',
                'Email',
                'Reward Description',
                'Branch',
                'Status',
                'Claimed Date',
                'Redeemed Date',
                'Expires At'
            ];

            foreach ($rewards as $reward) {
                $status = $reward->status_label;
                $csvData[] = [
                    ($reward->customer ? $reward->customer->first_name . ' ' . $reward->customer->last_name : 'N/A'),
                    ($reward->customer ? $reward->customer->email : 'N/A'),
                    ($reward->loyaltyTier ? $reward->loyaltyTier->reward_description : 'N/A'),
                    ($reward->branch ? $reward->branch->branch_name : 'All Branches'),
                    $status,
                    $reward->date_created ? $reward->date_created->format('Y-m-d H:i') : 'N/A',
                    $reward->redeemed_at ? $reward->redeemed_at->format('Y-m-d H:i') : 'N/A',
                    $reward->expiration_date ? $reward->expiration_date->format('Y-m-d') : 'N/A'
                ];
            }

            // Create CSV
            $filename = 'customer_rewards_' . date('Y-m-d') . '.csv';
            $handle = fopen('php://temp', 'r+');
            
            foreach ($csvData as $row) {
                fputcsv($handle, $row);
            }
            
            rewind($handle);
            $content = stream_get_contents($handle);
            fclose($handle);

            return response($content)
                ->withHeaders([
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ]);
        } catch (\Exception $e) {
            \Log::error('Error exporting rewards: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error exporting rewards: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get stats for the dashboard.
     */
    public function getStats(Request $request)
    {
        try {
            $owner = Auth::guard('owner')->user();
            $ownerId = $owner->id;

            $stats = $this->calculateStats($ownerId);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading stats: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get data for datatables.
     */
    public function getData(Request $request)
    {
        try {
            $owner = Auth::guard('owner')->user();
            $ownerId = $owner->id;

            $query = CustomerReward::with(['customer', 'loyaltyTier', 'branch'])
                ->whereHas('loyaltyTier', function ($q) use ($ownerId) {
                    $q->where('owner_account_id', $ownerId);
                })
                ->where('active', 1);

            // Apply filters
            if ($request->filled('status')) {
                $query->where('claim_status', $request->status);
            }

            if ($request->filled('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('date_created', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('date_created', '<=', $request->date_to);
            }

            $rewards = $query->orderBy('date_created', 'desc')->paginate(50);

            return response()->json([
                'success' => true,
                'data' => $rewards->items(),
                'pagination' => [
                    'current_page' => $rewards->currentPage(),
                    'last_page' => $rewards->lastPage(),
                    'per_page' => $rewards->perPage(),
                    'total' => $rewards->total()
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting reward data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading reward data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get loyalty performance report.
     */
    public function getLoyaltyPerformanceReport(Request $request)
    {
        try {
            $owner = Auth::guard('owner')->user();
            $ownerId = $owner->id;

            $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::now()->subMonth();
            $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::now();

            // Get total rewards earned in period
            $totalEarned = CustomerReward::whereHas('loyaltyTier', function ($q) use ($ownerId) {
                $q->where('owner_account_id', $ownerId);
            })
                ->whereBetween('date_created', [$dateFrom, $dateTo])
                ->where('active', 1)
                ->count();

            // Get redeemed rewards in period
            $totalRedeemed = CustomerReward::whereHas('loyaltyTier', function ($q) use ($ownerId) {
                $q->where('owner_account_id', $ownerId);
            })
                ->whereBetween('redeemed_at', [$dateFrom, $dateTo])
                ->where('redemption_status', CustomerReward::REDEMPTION_STATUS_REDEEMED)
                ->where('active', 1)
                ->count();

            // Get rewards by tier
            $rewardsByTier = CustomerReward::whereHas('loyaltyTier', function ($q) use ($ownerId) {
                $q->where('owner_account_id', $ownerId);
            })
                ->whereBetween('date_created', [$dateFrom, $dateTo])
                ->where('active', 1)
                ->select('loyalty_tier_id', DB::raw('count(*) as count'))
                ->groupBy('loyalty_tier_id')
                ->with('loyaltyTier')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_earned' => $totalEarned,
                    'total_redeemed' => $totalRedeemed,
                    'redemption_rate' => $totalEarned > 0 ? round(($totalRedeemed / $totalEarned) * 100, 2) : 0,
                    'rewards_by_tier' => $rewardsByTier,
                    'date_range' => [
                        'from' => $dateFrom->format('Y-m-d'),
                        'to' => $dateTo->format('Y-m-d')
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting loyalty performance report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get redemption report.
     */
    public function getRedemptionReport(Request $request)
    {
        try {
            $owner = Auth::guard('owner')->user();
            $ownerId = $owner->id;

            $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::now()->subMonth();
            $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::now();

            $redemptions = CustomerReward::with(['customer', 'loyaltyTier', 'branch', 'redeemedAtBranch'])
                ->whereHas('loyaltyTier', function ($q) use ($ownerId) {
                    $q->where('owner_account_id', $ownerId);
                })
                ->whereBetween('redeemed_at', [$dateFrom, $dateTo])
                ->where('redemption_status', CustomerReward::REDEMPTION_STATUS_REDEEMED)
                ->where('active', 1)
                ->orderBy('redeemed_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $redemptions,
                'total' => $redemptions->count(),
                'date_range' => [
                    'from' => $dateFrom->format('Y-m-d'),
                    'to' => $dateTo->format('Y-m-d')
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting redemption report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer loyalty report.
     */
    public function getCustomerLoyaltyReport(Request $request)
    {
        try {
            $owner = Auth::guard('owner')->user();
            $ownerId = $owner->id;

            $customers = CustomerAccount::withCount(['rewards' => function ($query) use ($ownerId) {
                $query->whereHas('loyaltyTier', function ($q) use ($ownerId) {
                    $q->where('owner_account_id', $ownerId);
                })->where('active', 1);
            }])
                ->with(['rewards' => function ($query) use ($ownerId) {
                    $query->whereHas('loyaltyTier', function ($q) use ($ownerId) {
                        $q->where('owner_account_id', $ownerId);
                    })->where('active', 1);
                }])
                ->whereHas('bookings', function ($query) use ($ownerId) {
                    $query->whereHas('branch', function ($q) use ($ownerId) {
                        $q->where('owner_account_id', $ownerId);
                    })->where('booking_status', 4)->where('active', 1);
                })
                ->orderBy('rewards_count', 'desc')
                ->limit(100)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $customers
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting customer loyalty report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export loyalty report.
     */
    public function exportLoyaltyReport(Request $request)
    {
        try {
            $owner = Auth::guard('owner')->user();
            $ownerId = $owner->id;

            $reportType = $request->get('type', 'performance');

            if ($reportType === 'performance') {
                $data = $this->getLoyaltyPerformanceReport($request)->getData(true);
                $reportData = $data['data'] ?? [];
            } elseif ($reportType === 'redemption') {
                $data = $this->getRedemptionReport($request)->getData(true);
                $reportData = $data['data'] ?? [];
            } else {
                $data = $this->getCustomerLoyaltyReport($request)->getData(true);
                $reportData = $data['data'] ?? [];
            }

            // Prepare CSV
            $filename = 'loyalty_report_' . $reportType . '_' . date('Y-m-d') . '.csv';
            
            return response()->json([
                'success' => true,
                'message' => 'Report exported successfully',
                'filename' => $filename,
                'data' => $reportData
            ]);
        } catch (\Exception $e) {
            \Log::error('Error exporting loyalty report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error exporting report: ' . $e->getMessage()
            ], 500);
        }
    }
}