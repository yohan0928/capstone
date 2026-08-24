<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\CustomerPreference;
use App\Models\Feedback;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class HomeController extends Controller
{
    /**
     * ================================================================
     * RECOMMENDATION ENGINE CONSTANTS
     * ================================================================
     */
    const RECOMMENDATION_WEIGHTS = [
        'location' => 0.30,
        'features' => 0.20,
        'rate' => 0.15,
        'space_type' => 0.12,
        'time_slot' => 0.10,
        'duration' => 0.08,
        'rating' => 0.05,
    ];

    const MINIMUM_MATCH_THRESHOLD = 20;
    const TIE_BREAKER_THRESHOLD = 5;
    const MAX_DISTANCE_KM = 50;

    /**
     * ================================================================
     * HYBRID FUSION WEIGHT
     * Score_Hybrid = alpha * Score_CF + (1 - alpha) * Score_CBF
     * alpha (Collaborative Filtering weight) = 0.4 (40%)
     * (1 - alpha) (Content-Based Filtering weight) = 0.6 (60%)
     * ================================================================
     */
    const COLLABORATIVE_ALPHA = 0.4;

    /**
     * ================================================================
     * COLD START THRESHOLD
     * User needs at least 3 completed bookings to use collaborative
     * filtering meaningfully.
     * ================================================================
     */
    const COLD_START_BOOKING_THRESHOLD = 3;

    /**
     * ================================================================
     * DISPLAY HOME PAGE WITH RECOMMENDATIONS
     * ================================================================
     */
    public function showHome(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        $showPreferencesModal = false;
        $customerPreference = null;
        $hasUserData = false;
        $customerLocation = null;
        $locationErrorMessage = null;

        if ($customer) {
            $customerPreference = CustomerPreference::where('customer_account_id', $customer->id)->first();
            $hasBookingHistory = Booking::where('customer_account_id', $customer->id)
                ->where('booking_status', 4)
                ->exists();

            $hasUserData = $this->hasUserData($customer);
            $customerLocation = $this->getCurrentLocation($request);

            if (!$customerLocation && $hasUserData) {
                $locationErrorMessage = 'Please share your current location to get accurate branch recommendations.';
            }

            // ================================================================
            // COLD START: No preferences AND no booking history
            // Redirect to preferences form
            // ================================================================
            if (!$customerPreference && !$hasBookingHistory) {
                return redirect()->route('sub_three.home.preferences.form');
            }

            $hasEmptyPreferences = false;
            if ($customerPreference) {
                $hasEmptyPreferences = empty($customerPreference->preferred_features) &&
                                      empty($customerPreference->preferred_space_types) &&
                                      empty($customerPreference->preferred_time_slots) &&
                                      empty($customerPreference->preferred_start_time) &&
                                      empty($customerPreference->preferred_category_ids) &&
                                      empty($customerPreference->preferred_service_ids);
            }

            if ($customerPreference && !$customerPreference->isCompleted() &&
                    !$hasBookingHistory &&
                    !session('preferences_shown') &&
                    !$hasEmptyPreferences) {
                $showPreferencesModal = true;
                session(['preferences_shown' => true]);
            }
        }

        $searchQuery = $request->input('search', '');
        $searchResults = collect();

        if ($searchQuery) {
            $searchResults = $this->handleSearch($searchQuery);
        }

        $cleanedParams = $this->cleanFilterParameters($request);
        if ($request->all() !== $cleanedParams) {
            return redirect()->route('sub_three.home.showHome', $cleanedParams);
        }

        $allBranches = Branch::where('active', 1)
            ->where('branch_status', 1)
            ->orderBy('branch_name')
            ->get(['uuid', 'branch_name', 'latitude', 'longitude', 'location']);

        $serviceCategories = ServiceCategory::where('active', 1)
            ->where('service_category_status', 1)
            ->orderBy('service_category')
            ->get(['uuid', 'service_category']);

        $serviceNames = ServiceName::where('active', 1)
            ->where('service_name_status', 1)
            ->orderBy('service_name')
            ->get(['uuid', 'service_name']);

        $uniqueFeatures = $this->getAllUniqueFeatures();

        // ================================================================
        // RECOMMENDATION ENGINE - HYBRID-FIRST ARCHITECTURE
        // ================================================================
        $recommendedBranch = null;
        $topBranches = [];
        $nearbyBranches = $this->getNearbyBranches($customerLocation);

        // Get booking count for the user
        $bookingCount = 0;
        if ($customer) {
            $bookingCount = Booking::where('customer_account_id', $customer->id)
                ->where('booking_status', 4)
                ->count();
        }

        // ================================================================
        // STEP 1: Check if user has data (preferences OR bookings OR feedback)
        // ================================================================
        if ($customer && $hasUserData) {
            
            // ================================================================
            // STEP 2: Get all available data
            // ================================================================
            $hasEnoughHistory = $bookingCount >= self::COLD_START_BOOKING_THRESHOLD;
            $hasPreferences = $customerPreference && $customerPreference->isCompleted();

            // ================================================================
            // STEP 3: Get similar users if we have enough bookings
            // ================================================================
            $similarUsers = $hasEnoughHistory ? $this->findSimilarUsers($customer->id) : [];

            // ================================================================
            // STEP 4: Calculate ALL available scores
            // ================================================================
            $branches = $this->getAllBranchesWithStats();
            $scoredBranches = [];
            $userLat = isset($customerLocation['latitude']) ? $customerLocation['latitude'] : null;
            $userLon = isset($customerLocation['longitude']) ? $customerLocation['longitude'] : null;

            foreach ($branches as $branch) {
                // ============================================================
                // PART A: CONTENT-BASED SCORE (Always calculated if preferences exist)
                // ============================================================
                $contentScore = null;
                if ($hasPreferences) {
                    $locationScore = $this->calculateLocationScore($branch, $userLat, $userLon);
                    $featuresScore = $this->calculateFeaturesMatch($branch, $customerPreference);
                    $rateScore = $this->calculateRateMatch($branch, $customerPreference);
                    $spaceTypeScore = $this->calculateSpaceTypeMatch($branch, $customerPreference);
                    $timeSlotScore = $this->calculateTimeSlotMatch($branch, $customerPreference);
                    $durationScore = $this->calculateDurationMatch($branch, $customerPreference);
                    $ratingScore = $this->calculateRatingScore($branch);

                    $contentScore = (
                        ($locationScore * self::RECOMMENDATION_WEIGHTS['location']) +
                        ($featuresScore * self::RECOMMENDATION_WEIGHTS['features']) +
                        ($rateScore * self::RECOMMENDATION_WEIGHTS['rate']) +
                        ($spaceTypeScore * self::RECOMMENDATION_WEIGHTS['space_type']) +
                        ($timeSlotScore * self::RECOMMENDATION_WEIGHTS['time_slot']) +
                        ($durationScore * self::RECOMMENDATION_WEIGHTS['duration']) +
                        ($ratingScore * self::RECOMMENDATION_WEIGHTS['rating'])
                    );
                }

                // ============================================================
                // PART B: COLLABORATIVE SCORE (Always calculated if enough bookings)
                // ============================================================
                $collaborativeScore = null;
                if ($hasEnoughHistory && !empty($similarUsers)) {
                    $collaborativeScore = $this->calculateCollaborativeScore($customer, $branch, $similarUsers);
                }

                // ============================================================
                // PART C: HYBRID FUSION (Always attempted when both scores exist)
                // ============================================================
                $finalScore = null;
                $recommendationType = null;
                $hybridAlpha = null;
                $scores = [];

                if ($contentScore !== null && $collaborativeScore !== null) {
                    // TRUE HYBRID: Both scores exist → Fuse them
                    $alpha = self::COLLABORATIVE_ALPHA;
                    $hybridAlpha = $alpha;
                    $finalScore = ($alpha * $collaborativeScore) + ((1 - $alpha) * $contentScore);
                    $recommendationType = 'hybrid';
                    $scores = [
                        'location' => round($locationScore ?? 0),
                        'features' => round($featuresScore ?? 0),
                        'rate' => round($rateScore ?? 0),
                        'space_type' => round($spaceTypeScore ?? 0),
                        'time_slot' => round($timeSlotScore ?? 0),
                        'duration' => round($durationScore ?? 0),
                        'rating' => round($ratingScore ?? 0),
                    ];
                } elseif ($contentScore !== null) {
                    // Content-Based Only
                    $finalScore = $contentScore;
                    $recommendationType = 'content_based';
                    $scores = [
                        'location' => round($locationScore ?? 0),
                        'features' => round($featuresScore ?? 0),
                        'rate' => round($rateScore ?? 0),
                        'space_type' => round($spaceTypeScore ?? 0),
                        'time_slot' => round($timeSlotScore ?? 0),
                        'duration' => round($durationScore ?? 0),
                        'rating' => round($ratingScore ?? 0),
                    ];
                } elseif ($collaborativeScore !== null) {
                    // Collaborative Only
                    $finalScore = $collaborativeScore;
                    $recommendationType = 'collaborative_only';
                    $scores = [
                        'location' => round($this->calculateLocationScore($branch, $userLat, $userLon)),
                        'collaborative' => round($collaborativeScore),
                    ];
                }

                // ============================================================
                // PART D: Build the result if score meets threshold
                // ============================================================
                if ($finalScore !== null && $finalScore >= self::MINIMUM_MATCH_THRESHOLD) {
                    $distance = $this->calculateDistance(
                        $userLat,
                        $userLon,
                        $branch->latitude,
                        $branch->longitude
                    );

                    $branchResult = [
                        'branch' => $branch,
                        'distance' => $distance,
                        'match_percentage' => round($finalScore),
                        'scores' => $scores,
                        'content_score' => $contentScore !== null ? round($contentScore) : null,
                        'collaborative_score' => $collaborativeScore !== null ? round($collaborativeScore) : null,
                        'hybrid_score' => ($contentScore !== null && $collaborativeScore !== null) ? round($finalScore) : null,
                        'hybrid_alpha' => $hybridAlpha,
                        'recommendation_type' => $recommendationType,
                        'matched_features' => $hasPreferences ? $this->getMatchedFeatures($branch, $customerPreference) : [],
                        'match_reason' => $this->buildMatchReason(
                            $contentScore, $collaborativeScore, $recommendationType,
                            $scores, $distance, $similarUsers
                        ),
                        'metrics' => [
                            'recent_bookings' => $branch->recent_bookings ?? 0,
                            'total_bookings' => $branch->total_bookings ?? 0,
                            'avg_rating' => $branch->feedbacks_avg_rating ?? 0,
                            'review_count' => $branch->feedbacks_count ?? 0,
                            'service_count' => $branch->active_services_count ?? 0
                        ],
                        'collaborative_data' => ($collaborativeScore !== null) ? $this->getCollaborativeData($customer, $branch, $similarUsers) : null
                    ];

                    $scoredBranches[] = $branchResult;
                }
            }

            // ================================================================
            // STEP 5: Sort and pick the best recommendation
            // ================================================================
            if (!empty($scoredBranches)) {
                $scoredBranches = $this->deduplicateBranches($scoredBranches);
                usort($scoredBranches, function ($a, $b) {
                    return $b['match_percentage'] <=> $a['match_percentage'];
                });

                // Handle ties by distance
                if (count($scoredBranches) > 1 &&
                    $scoredBranches[0]['match_percentage'] - $scoredBranches[1]['match_percentage'] <= self::TIE_BREAKER_THRESHOLD) {
                    $scoredBranches = $this->breakTiesByDistance($scoredBranches);
                }

                $recommendedBranch = $scoredBranches[0];
                $recommendationMode = $recommendedBranch['recommendation_type'];
            } else {
                $recommendationMode = 'popularity';
            }

            // Get top branches (always show popular branches as fallback)
            $topBranches = $this->getTopBranchesGlobal($customerLocation);

        } else {
            // No user data at all → show popular branches
            $topBranches = $this->getTopBranchesGlobal($customerLocation);
            $recommendationMode = 'popularity';
        }

        // Filter by search if needed
        if ($searchQuery) {
            if ($recommendedBranch) {
                $recommendedBranch = $this->filterSingleBranchBySearch($recommendedBranch, $searchQuery);
            }
            $topBranches = $this->filterBranchesBySearch($topBranches, $searchQuery);
            $nearbyBranches = $this->filterNearbyBranchesBySearch($nearbyBranches, $searchQuery);
        }

        $preferenceStrength = 0;
        if ($customerPreference) {
            $preferenceStrength = $customerPreference->preference_strength ?? 0;
        }

        $hasLocation = !is_null($customerLocation);
        $locationSource = isset($customerLocation['source']) ? $customerLocation['source'] : 'unknown';
        $placeName = isset($customerLocation['place_name']) ? $customerLocation['place_name'] : null;
        $fullAddress = isset($customerLocation['full_address']) ? $customerLocation['full_address'] : null;

        return view('customer.home.home', compact(
            'allBranches',
            'serviceCategories',
            'serviceNames',
            'uniqueFeatures',
            'recommendedBranch',
            'topBranches',
            'nearbyBranches',
            'showPreferencesModal',
            'customerPreference',
            'searchQuery',
            'searchResults',
            'hasUserData',
            'customerLocation',
            'locationErrorMessage',
            'hasLocation',
            'locationSource',
            'preferenceStrength',
            'placeName',
            'fullAddress',
            'recommendationMode',
            'bookingCount'
        ));
    }

    // ================================================================
    // ================================================================
    // RECOMMENDATION ENGINE - CORE METHODS
    // ================================================================
    // ================================================================


    // ================================================================
    // ================================================================
    // COLLABORATIVE FILTERING - COSINE SIMILARITY IMPLEMENTATION
    // ================================================================
    // ================================================================

    /**
     * Calculate cosine similarity between two users based on their branch ratings
     * Formula: sim(u,v) = (u·v) / (||u|| · ||v||)
     */
    private function calculateCosineSimilarity($user1Id, $user2Id): float
    {
        try {
            $user1Ratings = Feedback::where('customer_account_id', $user1Id)
                ->where('approved', 1)
                ->where('active', 1)
                ->get(['branch_id', 'rating'])
                ->keyBy('branch_id')
                ->map(function ($item) {
                    return (float) $item->rating;
                })
                ->toArray();

            $user2Ratings = Feedback::where('customer_account_id', $user2Id)
                ->where('approved', 1)
                ->where('active', 1)
                ->get(['branch_id', 'rating'])
                ->keyBy('branch_id')
                ->map(function ($item) {
                    return (float) $item->rating;
                })
                ->toArray();

            $commonBranches = array_intersect(
                array_keys($user1Ratings),
                array_keys($user2Ratings)
            );

            if (empty($commonBranches)) {
                return 0;
            }

            $vector1 = [];
            $vector2 = [];
            foreach ($commonBranches as $branchId) {
                $vector1[] = $user1Ratings[$branchId];
                $vector2[] = $user2Ratings[$branchId];
            }

            $dotProduct = 0;
            $magnitude1 = 0;
            $magnitude2 = 0;

            for ($i = 0; $i < count($vector1); $i++) {
                $dotProduct += $vector1[$i] * $vector2[$i];
                $magnitude1 += $vector1[$i] * $vector1[$i];
                $magnitude2 += $vector2[$i] * $vector2[$i];
            }

            $magnitude1 = sqrt($magnitude1);
            $magnitude2 = sqrt($magnitude2);

            if ($magnitude1 == 0 || $magnitude2 == 0) {
                return 0;
            }

            return $dotProduct / ($magnitude1 * $magnitude2);

        } catch (\Exception $e) {
            Log::error('Error calculating cosine similarity', [
                'user1' => $user1Id,
                'user2' => $user2Id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Find top N similar users using cosine similarity
     */
    private function findSimilarUsers($userId, $limit = 20): array
    {
        try {
            $allUsers = Feedback::where('customer_account_id', '!=', $userId)
                ->where('approved', 1)
                ->where('active', 1)
                ->distinct()
                ->pluck('customer_account_id')
                ->toArray();

            if (empty($allUsers)) {
                return [];
            }

            $similarities = [];

            foreach ($allUsers as $otherUserId) {
                $similarity = $this->calculateCosineSimilarity($userId, $otherUserId);

                if ($similarity > 0) {
                    $similarities[$otherUserId] = $similarity;
                }
            }

            arsort($similarities);

            return array_slice($similarities, 0, $limit, true);

        } catch (\Exception $e) {
            Log::error('Error finding similar users', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Predict rating for a user on a specific branch using collaborative filtering
     * Formula: r̂(u,i) = r̄(u) + [Σ(v∈N(u)) sim(u,v) · (r(v,i) - r̄(v))] / Σ(v∈N(u)) |sim(u,v)|
     */
    private function predictCollaborativeRating($userId, $branchId, $similarUsers = null): float
    {
        try {
            $userAvgRating = Feedback::where('customer_account_id', $userId)
                ->where('approved', 1)
                ->where('active', 1)
                ->avg('rating');

            if ($userAvgRating === null) {
                $userAvgRating = 2.5;
            }

            if ($similarUsers === null) {
                $similarUsers = $this->findSimilarUsers($userId);
            }

            if (empty($similarUsers)) {
                return (float) $userAvgRating;
            }

            $userIds = array_keys($similarUsers);
            $ratings = Feedback::whereIn('customer_account_id', $userIds)
                ->where('branch_id', $branchId)
                ->where('approved', 1)
                ->where('active', 1)
                ->get(['customer_account_id', 'rating'])
                ->keyBy('customer_account_id');

            if ($ratings->isEmpty()) {
                return (float) $userAvgRating;
            }

            $weightedSum = 0;
            $totalWeight = 0;

            foreach ($similarUsers as $similarUserId => $similarity) {
                if ($ratings->has($similarUserId)) {
                    $rating = (float) $ratings[$similarUserId]->rating;

                    $similarUserAvg = Feedback::where('customer_account_id', $similarUserId)
                        ->where('approved', 1)
                        ->where('active', 1)
                        ->avg('rating');

                    if ($similarUserAvg === null) {
                        $similarUserAvg = 2.5;
                    }

                    $weightedSum += $similarity * ($rating - $similarUserAvg);
                    $totalWeight += abs($similarity);
                }
            }

            if ($totalWeight == 0) {
                return (float) $userAvgRating;
            }

            $predictedRating = $userAvgRating + ($weightedSum / $totalWeight);

            return max(1, min(5, $predictedRating));

        } catch (\Exception $e) {
            Log::error('Error predicting collaborative rating', [
                'user_id' => $userId,
                'branch_id' => $branchId,
                'error' => $e->getMessage()
            ]);
            return 2.5;
        }
    }

    /**
     * Calculate collaborative filtering score for a user on a branch
     */
    private function calculateCollaborativeScore($customer, $branch, $similarUsers = null): float
    {
        try {
            if (!$this->hasEnoughBookingHistory($customer)) {
                return 50;
            }

            if ($similarUsers === null) {
                $similarUsers = $this->findSimilarUsers($customer->id);
            }

            if (empty($similarUsers)) {
                return 50;
            }

            $predictedRating = $this->predictCollaborativeRating(
                $customer->id,
                $branch->id,
                $similarUsers
            );

            return $this->ratingToScore($predictedRating);

        } catch (\Exception $e) {
            Log::error('Error in collaborative scoring', [
                'customer_id' => $customer->id,
                'branch_id' => $branch->id,
                'error' => $e->getMessage()
            ]);
            return 50;
        }
    }

    /**
     * Get comprehensive collaborative filtering data for a branch
     */
    private function getCollaborativeData($customer, $branch, $similarUsers = null): array
    {
        try {
            if ($similarUsers === null) {
                $similarUsers = $this->findSimilarUsers($customer->id);
            }

            if (empty($similarUsers)) {
                return [
                    'score' => 50,
                    'predicted_rating' => 2.5,
                    'similar_users_count' => 0,
                    'top_similar_users' => [],
                    'has_data' => false
                ];
            }

            $predictedRating = $this->predictCollaborativeRating(
                $customer->id,
                $branch->id,
                $similarUsers
            );

            $topSimilar = array_slice($similarUsers, 0, 3, true);

            return [
                'score' => $this->ratingToScore($predictedRating),
                'predicted_rating' => round($predictedRating, 2),
                'similar_users_count' => count($similarUsers),
                'top_similar_users' => $topSimilar,
                'has_data' => true
            ];

        } catch (\Exception $e) {
            Log::error('Error getting collaborative data', [
                'customer_id' => $customer->id,
                'branch_id' => $branch->id,
                'error' => $e->getMessage()
            ]);
            return [
                'score' => 50,
                'predicted_rating' => 2.5,
                'similar_users_count' => 0,
                'top_similar_users' => [],
                'has_data' => false
            ];
        }
    }

    private function ratingToScore($rating): float
    {
        return ($rating / 5) * 100;
    }

    /**
     * Check if user has enough booking history for collaborative filtering
     */
    private function hasEnoughBookingHistory($customer): bool
    {
        if (!$customer) {
            return false;
        }

        $bookingCount = Booking::where('customer_account_id', $customer->id)
            ->where('booking_status', 4)
            ->count();

        return $bookingCount >= self::COLD_START_BOOKING_THRESHOLD;
    }

    // ================================================================
    // ================================================================
    // CONTENT-BASED SUB-SCORE CALCULATIONS
    // ================================================================
    // ================================================================

    private function calculateLocationScore($branch, $userLat, $userLon): float
    {
        if (!$userLat || !$userLon || !$branch->latitude || !$branch->longitude) {
            return 50;
        }

        $distance = $this->calculateDistance( // ← Haversine called here
            $userLat,
            $userLon,
            $branch->latitude,
            $branch->longitude
        );

        return $this->getDistanceScore($distance);
    }

    private function calculateFeaturesMatch($branch, $customerPreference): float
    {
        $userFeatures = $customerPreference->preferred_features ?? [];

        if (empty($userFeatures)) {
            return 100;
        }

        if (!$branch->features) {
            return 0;
        }

        $branchFeatures = $this->getFeaturesArray($branch->features);
        $branchFeatures = array_map('strtolower', $branchFeatures);
        $userFeaturesLower = array_map('strtolower', $userFeatures);

        $matchedFeatures = array_intersect($branchFeatures, $userFeaturesLower);

        if (count($userFeatures) > 0) {
            return (count($matchedFeatures) / count($userFeatures)) * 100;
        }

        return 0;
    }

    private function getMatchedFeatures($branch, $customerPreference): array
    {
        $userFeatures = $customerPreference->preferred_features ?? [];

        if (empty($userFeatures) || !$branch->features) {
            return [];
        }

        $branchFeatures = $this->getFeaturesArray($branch->features);
        $branchFeatures = array_map('strtolower', $branchFeatures);
        $userFeaturesLower = array_map('strtolower', $userFeatures);

        return array_intersect($branchFeatures, $userFeaturesLower);
    }

    private function calculateSpaceTypeMatch($branch, $customerPreference): float
    {
        $userSpaceTypes = $customerPreference->preferred_space_types ?? [];

        if (empty($userSpaceTypes)) {
            return 100;
        }

        $branchSpaceTypes = ServiceName::where('branch_id', $branch->id)
            ->where('active', 1)
            ->where('service_name_status', 1)
            ->whereNotNull('space_type')
            ->distinct()
            ->pluck('space_type')
            ->toArray();

        if (empty($branchSpaceTypes)) {
            return 0;
        }

        $matchedSpaceTypes = array_intersect($branchSpaceTypes, $userSpaceTypes);

        if (count($userSpaceTypes) > 0) {
            return (count($matchedSpaceTypes) / count($userSpaceTypes)) * 100;
        }

        return 0;
    }

    private function calculateTimeSlotMatch($branch, $customerPreference): float
    {
        $userTimeSlots = $customerPreference->preferred_time_slots ?? [];

        if (empty($userTimeSlots)) {
            return 100;
        }

        if (!$branch->open_time || !$branch->close_time) {
            return 0;
        }

        $timeSlotRanges = [
            'early_morning' => ['start' => '05:00', 'end' => '08:00'],
            'morning' => ['start' => '08:00', 'end' => '12:00'],
            'afternoon' => ['start' => '12:00', 'end' => '17:00'],
            'evening' => ['start' => '17:00', 'end' => '21:00'],
            'late_night' => ['start' => '21:00', 'end' => '23:59']
        ];

        $branchOpenTime = $branch->open_time;
        $branchCloseTime = $branch->close_time;
        $isOvernight = $this->isOvernightBranch($branchOpenTime, $branchCloseTime);

        $matchedTimeSlots = [];
        $totalSlots = count($userTimeSlots);

        foreach ($userTimeSlots as $timeSlot) {
            if (!isset($timeSlotRanges[$timeSlot])) {
                continue;
            }

            $range = $timeSlotRanges[$timeSlot];

            if ($this->isBranchOpenDuringTimeSlot($branchOpenTime, $branchCloseTime, $range['start'], $range['end'], $isOvernight)) {
                $matchedTimeSlots[] = $timeSlot;
            }
        }

        if ($totalSlots > 0) {
            return (count($matchedTimeSlots) / $totalSlots) * 100;
        }

        return 100;
    }

    private function isOvernightBranch($openTime, $closeTime): bool
    {
        $open = Carbon::createFromTimeString($openTime);
        $close = Carbon::createFromTimeString($closeTime);
        return $close <= $open;
    }

    private function isBranchOpenDuringTimeSlot($branchOpen, $branchClose, $slotStart, $slotEnd, $isOvernight): bool
    {
        $openTime = Carbon::createFromTimeString($branchOpen);
        $closeTime = Carbon::createFromTimeString($branchClose);
        $slotStartTime = Carbon::createFromTimeString($slotStart);
        $slotEndTime = Carbon::createFromTimeString($slotEnd);

        if ($isOvernight) {
            $midnight = Carbon::createFromTimeString('00:00');
            $dayEnd = Carbon::createFromTimeString('23:59');

            $period1Start = $openTime;
            $period1End = $dayEnd;
            $period2Start = $midnight;
            $period2End = $closeTime;

            if ($slotEndTime < $slotStartTime) {
                $firstPartEnd = $dayEnd;
                $firstPartOverlaps = $this->timeRangesOverlap($slotStartTime, $firstPartEnd, $period1Start, $period1End);
                $secondPartStart = $midnight;
                $secondPartOverlaps = $this->timeRangesOverlap($secondPartStart, $slotEndTime, $period2Start, $period2End);
                return $firstPartOverlaps || $secondPartOverlaps;
            }

            $overlapsPeriod1 = $this->timeRangesOverlap($slotStartTime, $slotEndTime, $period1Start, $period1End);
            $overlapsPeriod2 = $this->timeRangesOverlap($slotStartTime, $slotEndTime, $period2Start, $period2End);
            return $overlapsPeriod1 || $overlapsPeriod2;
        } else {
            return $this->timeRangesOverlap($slotStartTime, $slotEndTime, $openTime, $closeTime);
        }
    }

    private function timeRangesOverlap($start1, $end1, $start2, $end2): bool
    {
        if ($end1 >= $start1 && $end2 >= $start2) {
            return $start1 < $end2 && $end1 > $start2;
        }

        if ($end1 < $start1) {
            $midnight = Carbon::createFromTimeString('00:00');
            $dayEnd = Carbon::createFromTimeString('23:59');
            $part1Overlap = $this->timeRangesOverlap($start1, $dayEnd, $start2, $end2);
            $part2Overlap = $this->timeRangesOverlap($midnight, $end1, $start2, $end2);
            return $part1Overlap || $part2Overlap;
        }

        if ($end2 < $start2) {
            $midnight = Carbon::createFromTimeString('00:00');
            $dayEnd = Carbon::createFromTimeString('23:59');
            $part1Overlap = $this->timeRangesOverlap($start1, $end1, $start2, $dayEnd);
            $part2Overlap = $this->timeRangesOverlap($start1, $end1, $midnight, $end2);
            return $part1Overlap || $part2Overlap;
        }

        return false;
    }

    private function calculateDurationMatch($branch, $customerPreference): float
    {
        $preferredDuration = $customerPreference->preferred_session_duration ?? null;

        if (!$preferredDuration) {
            return 100;
        }

        $availableDurations = ServiceName::where('branch_id', $branch->id)
            ->where('active', 1)
            ->where('service_name_status', 1)
            ->whereNotNull('time_duration')
            ->where('time_duration', '!=', '')
            ->distinct()
            ->pluck('time_duration')
            ->toArray();

        if (empty($availableDurations)) {
            return 0;
        }

        $preferredHours = $this->normalizeDurationToHours($preferredDuration);

        foreach ($availableDurations as $duration) {
            $availableHours = $this->normalizeDurationToHours($duration);
            if (abs($availableHours - $preferredHours) <= 1) {
                return 100;
            }
        }

        $closestDiff = PHP_INT_MAX;
        foreach ($availableDurations as $duration) {
            $availableHours = $this->normalizeDurationToHours($duration);
            $diff = abs($availableHours - $preferredHours);
            if ($diff < $closestDiff) {
                $closestDiff = $diff;
            }
        }

        $maxDiff = 5;
        if ($closestDiff <= $maxDiff) {
            return max(0, 100 - (($closestDiff / $maxDiff) * 100));
        }

        return 0;
    }

    private function normalizeDurationToHours($duration): float
    {
        if (is_numeric($duration)) {
            return (float) $duration;
        }

        if (is_string($duration)) {
            $durationLower = strtolower(trim($duration));

            if (strpos($durationLower, 'hour') !== false || strpos($durationLower, 'hr') !== false) {
                preg_match('/(\d+\.?\d*)/', $durationLower, $matches);
                return isset($matches[1]) ? (float) $matches[1] : 0;
            }

            if (strpos($durationLower, 'min') !== false) {
                preg_match('/(\d+\.?\d*)/', $durationLower, $matches);
                $minutes = isset($matches[1]) ? (float) $matches[1] : 0;
                return $minutes / 60;
            }

            preg_match('/(\d+\.?\d*)/', $durationLower, $matches);
            if (isset($matches[1])) {
                return (float) $matches[1];
            }
        }

        return (float) $duration;
    }

    private function calculateRateMatch($branch, $customerPreference): float
    {
        $minRate = $customerPreference->min_rate_preferred ?? null;
        $maxRate = $customerPreference->max_rate_preferred ?? null;

        if (!$minRate && !$maxRate) {
            return 100;
        }

        $availableRates = ServiceName::where('branch_id', $branch->id)
            ->where('active', 1)
            ->where('service_name_status', 1)
            ->whereNotNull('price')
            ->distinct()
            ->pluck('price')
            ->toArray();

        if (empty($availableRates)) {
            return 0;
        }

        $ratesInRange = 0;

        foreach ($availableRates as $rate) {
            $inRange = true;
            if ($minRate && $rate < $minRate) {
                $inRange = false;
            }
            if ($maxRate && $rate > $maxRate) {
                $inRange = false;
            }
            if ($inRange) {
                $ratesInRange++;
            }
        }

        if (count($availableRates) > 0) {
            return ($ratesInRange / count($availableRates)) * 100;
        }

        return 0;
    }

    private function calculateRatingScore($branch): float
    {
        $avgRating = $branch->feedbacks_avg_rating ?? 0;
        $reviewCount = $branch->feedbacks_count ?? 0;

        if ($reviewCount === 0) {
            return 50;
        }

        $ratingScore = ($avgRating / 5) * 100;
        $reviewBonus = min(10, $reviewCount * 0.5);

        return min(100, $ratingScore + $reviewBonus);
    }

    private function getFeaturesArray($features): array
    {
        if (empty($features)) {
            return [];
        }

        if (is_array($features)) {
            return array_map('trim', $features);
        }

        if (is_string($features) && $this->isJson($features)) {
            $decoded = json_decode($features, true);
            if (is_array($decoded)) {
                return array_map('trim', $decoded);
            }
        }

        if (is_string($features)) {
            $featureArray = array_map('trim', explode(',', $features));
            return array_filter($featureArray);
        }

        return [];
    }

    private function isJson($string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    private function getAllBranchesWithStats()
    {
        return Branch::where('active', 1)
            ->where('branch_status', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->withCount(['bookings as recent_bookings' => function ($q) {
                $q->where('booking_status', 4)->where('date_start', '>=', now()->subDays(30));
            }])
            ->withCount(['bookings as total_bookings' => function ($q) {
                $q->where('booking_status', 4);
            }])
            ->withAvg('feedbacks', 'rating')
            ->withCount('feedbacks')
            ->withCount(['serviceNames as active_services_count' => function ($q) {
                $q->where('active', 1)->where('service_name_status', 1);
            }])
            ->with(['serviceNames' => function($q) {
                $q->where('active', 1)->where('service_name_status', 1);
            }])
            ->get();
    }

    private function deduplicateBranches($scoredBranches): array
    {
        $uniqueBranches = [];
        $seenBranchIds = [];

        foreach ($scoredBranches as $scoredBranch) {
            $branchId = $scoredBranch['branch']->id;
            if (!in_array($branchId, $seenBranchIds)) {
                $seenBranchIds[] = $branchId;
                $uniqueBranches[] = $scoredBranch;
            }
        }

        return $uniqueBranches;
    }

    private function breakTiesByDistance($scoredBranches): array
    {
        $topContenders = array_slice($scoredBranches, 0, 3);
        usort($topContenders, function ($a, $b) {
            if ($a['distance'] !== null && $b['distance'] !== null) {
                return $a['distance'] <=> $b['distance'];
            }
            return 0;
        });
        return $topContenders;
    }

    // ================================================================
    // ================================================================
    // MATCH REASON BUILDER - UNIFIED FOR HYBRID-FIRST
    // ================================================================
    // ================================================================

    private function buildMatchReason($contentScore, $collaborativeScore, $recommendationType, $scores, $distance = null, $similarUsers = []): string
    {
        $reasons = [];

        // ============================================================
        // CONTENT-BASED REASONS
        // ============================================================
        if ($contentScore !== null) {
            if (isset($scores['location']) && $scores['location'] >= 80 && $distance !== null) {
                if ($distance < 1) {
                    $reasons[] = "📍 Very close to you (< 1km)";
                } elseif ($distance < 3) {
                    $reasons[] = "📍 Close to you (" . round($distance, 1) . " km)";
                } else {
                    $reasons[] = "📍 Within " . round($distance, 1) . " km from you";
                }
            }

            if (isset($scores['features']) && $scores['features'] >= 80) {
                $reasons[] = "✨ Has all your preferred amenities";
            } elseif (isset($scores['features']) && $scores['features'] >= 50) {
                $reasons[] = "✨ Matches your amenities";
            }

            if (isset($scores['rate']) && $scores['rate'] >= 80) {
                $reasons[] = "💰 Within your budget range";
            }

            if (isset($scores['space_type']) && $scores['space_type'] >= 80) {
                $reasons[] = "🪑 Offers your preferred space types";
            }

            if (isset($scores['time_slot']) && $scores['time_slot'] >= 80) {
                $reasons[] = "🕐 Open during your preferred hours";
            }

            if (isset($scores['duration']) && $scores['duration'] >= 80) {
                $reasons[] = "⏱️ Has your preferred session duration";
            }

            if (isset($scores['rating']) && $scores['rating'] >= 80) {
                $reasons[] = "⭐ Highly rated by customers";
            }
        }

        // ============================================================
        // COLLABORATIVE REASONS
        // ============================================================
        if ($collaborativeScore !== null) {
            $similarCount = count($similarUsers);
            if ($collaborativeScore >= 80) {
                $reasons[] = "🧠 Highly rated by " . $similarCount . " similar users";
            } elseif ($collaborativeScore >= 60) {
                $reasons[] = "🧠 Liked by " . $similarCount . " similar users";
            } elseif ($collaborativeScore >= 40) {
                $reasons[] = "🧠 Based on " . $similarCount . " similar users' preferences";
            } else {
                $reasons[] = "🧠 Recommended by " . $similarCount . " similar users";
            }
        }

        // ============================================================
        // HYBRID REASON (when both exist)
        // ============================================================
        if ($contentScore !== null && $collaborativeScore !== null) {
            $reasons[] = "🔀 Hybrid: " . round($contentScore) . "% content + " . round($collaborativeScore) . "% collaborative";
        }

        if (empty($reasons)) {
            if ($recommendationType === 'popularity') {
                return "Popular branch with good services";
            }
            return "Matches your preferences";
        }

        return implode(" • ", array_slice($reasons, 0, 4));
    }

    // ================================================================
    // ================================================================
    // REVERSE GEOCODING - LOCATION FUNCTIONS
    // ================================================================
    // ================================================================

    private function getAddressFromCoordinates($latitude, $longitude)
    {
        if (!$latitude || !$longitude) {
            return null;
        }

        $cacheKey = 'address_' . round($latitude, 4) . '_' . round($longitude, 4);

        return Cache::remember($cacheKey, 86400, function () use ($latitude, $longitude) {
            try {
                $response = Http::timeout(5)
                    ->withHeaders([
                        'User-Agent' => 'LinkudHub/1.0'
                    ])
                    ->get('https://nominatim.openstreetmap.org/reverse', [
                        'format' => 'json',
                        'lat' => $latitude,
                        'lon' => $longitude,
                        'addressdetails' => 1,
                        'zoom' => 18,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $address = isset($data['address']) ? $data['address'] : [];

                    $houseNumber = isset($address['house_number']) ? $address['house_number'] : (isset($address['building']) ? $address['building'] : '');
                    $road = isset($address['road']) ? $address['road'] : (isset($address['street']) ? $address['street'] : (isset($address['pedestrian']) ? $address['pedestrian'] : ''));
                    $suburb = isset($address['suburb']) ? $address['suburb'] : (isset($address['district']) ? $address['district'] : (isset($address['neighbourhood']) ? $address['neighbourhood'] : (isset($address['quarter']) ? $address['quarter'] : '')));
                    $city = isset($address['city']) ? $address['city'] : (isset($address['town']) ? $address['town'] : (isset($address['village']) ? $address['village'] : (isset($address['municipality']) ? $address['municipality'] : '')));
                    $state = isset($address['state']) ? $address['state'] : (isset($address['region']) ? $address['region'] : (isset($address['province']) ? $address['province'] : ''));
                    $country = isset($address['country']) ? $address['country'] : '';
                    $postcode = isset($address['postcode']) ? $address['postcode'] : '';

                    $displayParts = array_filter([$houseNumber, $road, $city, $state]);
                    $display = !empty($displayParts) ? implode(', ', $displayParts) : (isset($data['display_name']) ? $data['display_name'] : '');

                    $fullParts = array_filter([$houseNumber, $road, $city, $state, $country]);
                    $full = !empty($fullParts) ? implode(', ', $fullParts) : (isset($data['display_name']) ? $data['display_name'] : '');

                    if (empty($houseNumber) && !empty($road) && !empty($city)) {
                        $display = $road . ', ' . $city;
                        if (!empty($state)) {
                            $display .= ', ' . $state;
                        }
                    }

                    if (empty($city) && !empty($suburb)) {
                        $city = $suburb;
                    }

                    return [
                        'display' => $display,
                        'full' => $full,
                        'city' => $city,
                        'state' => $state,
                        'country' => $country,
                        'postcode' => $postcode,
                        'house_number' => $houseNumber,
                        'road' => $road,
                        'suburb' => $suburb,
                        'display_name' => isset($data['display_name']) ? $data['display_name'] : ''
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Nominatim reverse geocoding failed: ' . $e->getMessage());
            }

            // Fallback: BigDataCloud
            try {
                $response = Http::timeout(5)
                    ->get('https://api.bigdatacloud.net/data/reverse-geocode-client', [
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'localityLanguage' => 'en',
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    $city = isset($data['city']) ? $data['city'] : (isset($data['locality']) ? $data['locality'] : '');
                    $state = isset($data['principalSubdivision']) ? $data['principalSubdivision'] : '';
                    $country = isset($data['countryName']) ? $data['countryName'] : '';
                    $postcode = isset($data['postcode']) ? $data['postcode'] : '';

                    $displayParts = array_filter([$city, $state]);
                    $display = !empty($displayParts) ? implode(', ', $displayParts) : 'Unknown location';
                    $fullParts = array_filter([$city, $state, $country]);
                    $full = !empty($fullParts) ? implode(', ', $fullParts) : $display;

                    return [
                        'display' => $display,
                        'full' => $full,
                        'city' => $city,
                        'state' => $state,
                        'country' => $country,
                        'postcode' => $postcode,
                        'house_number' => '',
                        'road' => '',
                        'suburb' => '',
                        'display_name' => $full
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('BigDataCloud fallback failed: ' . $e->getMessage());
            }

            return null;
        });
    }

    private function getCurrentLocation($request)
    {
        // Priority 1: Check session for location
        if (session()->has('customer_location')) {
            $location = session('customer_location');

            if (isset($location['expires_at']) && $location['expires_at'] > now()) {
                if (!isset($location['source'])) {
                    $location['source'] = 'browser';
                }

                if (empty($location['place_name']) && isset($location['latitude']) && isset($location['longitude'])) {
                    $address = $this->getAddressFromCoordinates(
                        $location['latitude'],
                        $location['longitude']
                    );

                    if ($address) {
                        $location['place_name'] = $address['display'];
                        $location['full_address'] = $address['full'];
                        $location['city'] = $address['city'];
                        $location['state'] = $address['state'];
                        $location['country'] = $address['country'];
                        $location['postcode'] = $address['postcode'];
                        $location['house_number'] = $address['house_number'];
                        $location['road'] = $address['road'];
                        $location['suburb'] = $address['suburb'];
                        session(['customer_location' => $location]);
                    }
                }

                return $location;
            }

            session()->forget('customer_location');
        }

        // Priority 2: Check if location was passed via AJAX
        if ($request->has('latitude') && $request->has('longitude')) {
            $address = $this->getAddressFromCoordinates(
                (float) $request->latitude,
                (float) $request->longitude
            );

            $location = [
                'latitude' => (float) $request->latitude,
                'longitude' => (float) $request->longitude,
                'place_name' => isset($address['display']) ? $address['display'] : 'Your Location',
                'full_address' => isset($address['full']) ? $address['full'] : (isset($address['display']) ? $address['display'] : 'Your Location'),
                'city' => isset($address['city']) ? $address['city'] : '',
                'state' => isset($address['state']) ? $address['state'] : '',
                'country' => isset($address['country']) ? $address['country'] : '',
                'postcode' => isset($address['postcode']) ? $address['postcode'] : '',
                'house_number' => isset($address['house_number']) ? $address['house_number'] : '',
                'road' => isset($address['road']) ? $address['road'] : '',
                'suburb' => isset($address['suburb']) ? $address['suburb'] : '',
                'source' => 'browser',
                'expires_at' => now()->addMinutes(30)->toDateTimeString()
            ];

            session(['customer_location' => $location]);
            return $location;
        }

        // Priority 3: Get location from IP address
        $ipLocation = $this->getLocationFromIP();
        if ($ipLocation) {
            $address = $this->getAddressFromCoordinates(
                $ipLocation['latitude'],
                $ipLocation['longitude']
            );

            return [
                'latitude' => (float) $ipLocation['latitude'],
                'longitude' => (float) $ipLocation['longitude'],
                'place_name' => isset($address['display']) ? $address['display'] : 'Your Location',
                'full_address' => isset($address['full']) ? $address['full'] : (isset($address['display']) ? $address['display'] : 'Your Location'),
                'city' => isset($address['city']) ? $address['city'] : '',
                'state' => isset($address['state']) ? $address['state'] : '',
                'country' => isset($address['country']) ? $address['country'] : '',
                'postcode' => isset($address['postcode']) ? $address['postcode'] : '',
                'source' => 'ip',
                'expires_at' => now()->addMinutes(60)->toDateTimeString()
            ];
        }

        // Priority 4: Default location (Davao City)
        return [
            'latitude' => 7.083333,
            'longitude' => 125.616667,
            'place_name' => 'Davao City, Philippines',
            'full_address' => 'Davao City, Davao Region, Philippines',
            'city' => 'Davao City',
            'state' => 'Davao Region',
            'country' => 'Philippines',
            'postcode' => '8000',
            'source' => 'default',
            'expires_at' => now()->addHours(24)->toDateTimeString()
        ];
    }

    private function getLocationFromIP()
    {
        try {
            $ip = request()->ip();

            $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}?fields=status,lat,lon");

            if ($response->successful() && $response->json('status') === 'success') {
                return [
                    'latitude' => $response->json('lat'),
                    'longitude' => $response->json('lon')
                ];
            }
        } catch (\Exception $e) {
            Log::warning('IP Geolocation failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    public function updateLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'place_name' => 'nullable|string|max:255',
            'full_address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postcode' => 'nullable|string|max:20',
            'house_number' => 'nullable|string|max:50',
            'road' => 'nullable|string|max:100',
            'suburb' => 'nullable|string|max:100',
        ]);

        try {
            $placeName = $request->place_name;
            $fullAddress = $request->full_address;

            if (empty($placeName) || empty($fullAddress)) {
                $address = $this->getAddressFromCoordinates(
                    (float) $request->latitude,
                    (float) $request->longitude
                );

                if ($address) {
                    $placeName = isset($address['display']) ? $address['display'] : '';
                    $fullAddress = isset($address['full']) ? $address['full'] : '';
                }
            }

            if (empty($placeName)) {
                $parts = array_filter([$request->city, $request->state]);
                $placeName = !empty($parts) ? implode(', ', $parts) : 'Your Location';
            }

            if (empty($fullAddress)) {
                $parts = array_filter([$request->city, $request->state, $request->country]);
                $fullAddress = !empty($parts) ? implode(', ', $parts) : $placeName;
            }

            $location = [
                'latitude' => (float) $request->latitude,
                'longitude' => (float) $request->longitude,
                'place_name' => $placeName,
                'full_address' => $fullAddress,
                'city' => $request->city ?? '',
                'state' => $request->state ?? '',
                'country' => $request->country ?? '',
                'postcode' => $request->postcode ?? '',
                'house_number' => $request->house_number ?? '',
                'road' => $request->road ?? '',
                'suburb' => $request->suburb ?? '',
                'source' => 'browser',
                'expires_at' => now()->addMinutes(30)->toDateTimeString()
            ];

            session(['customer_location' => $location]);

            Cache::forget('top_branches_global_customer_v2');

            if ($customer = Auth::guard('customer')->user()) {
                Cache::forget('hybrid_recommendation_' . $customer->id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully!',
                'place_name' => $placeName,
                'full_address' => $fullAddress,
                'expires_at' => now()->addMinutes(30)->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating customer location: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update location. Please try again.'
            ], 500);
        }
    }

    public function clearLocation(Request $request)
    {
        try {
            session()->forget('customer_location');

            Cache::forget('top_branches_global_customer_v2');

            if ($customer = Auth::guard('customer')->user()) {
                Cache::forget('hybrid_recommendation_' . $customer->id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Location cleared successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error clearing location: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear location. Please try again.'
            ], 500);
        }
    }

    // ================================================================
    // ================================================================
    // DISTANCE CALCULATION
    // ================================================================
    // ================================================================

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        if (!$lat1 || !$lon1 || !$lat2 || !$lon2) {
            return null;
        }

        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function getDistanceScore($distance): float
    {
        if ($distance === null) {
            return 50;
        }

        if ($distance >= self::MAX_DISTANCE_KM) {
            return 0;
        }

        return round((1 - ($distance / self::MAX_DISTANCE_KM)) * 100);
    }

    private function getNearbyBranches($customerLocation)
    {
        if (!$customerLocation) {
            return [];
        }

        try {
            $branches = Branch::where('active', 1)
                ->where('branch_status', 1)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->withCount(['bookings as recent_bookings' => function ($q) {
                    $q->where('booking_status', 4)->where('date_start', '>=', now()->subDays(30));
                }])
                ->withCount(['bookings as total_bookings' => function ($q) {
                    $q->where('booking_status', 4);
                }])
                ->withAvg('feedbacks', 'rating')
                ->withCount('feedbacks')
                ->withCount(['serviceNames as active_services_count' => function ($q) {
                    $q->where('active', 1)->where('service_name_status', 1);
                }])
                ->get()
                ->map(function ($branch) use ($customerLocation) {
                    $distance = $this->calculateDistance(
                        isset($customerLocation['latitude']) ? $customerLocation['latitude'] : null,
                        isset($customerLocation['longitude']) ? $customerLocation['longitude'] : null,
                        $branch->latitude,
                        $branch->longitude
                    );

                    $branch->distance = $distance;
                    $branch->distance_label = $distance !== null
                        ? ($distance < 1 ? '< 1 km' : round($distance, 1) . ' km')
                        : 'Unknown';
                    $branch->distance_score = $this->getDistanceScore($distance);

                    return $branch;
                })
                ->filter(function ($branch) {
                    return $branch->distance !== null && $branch->distance <= 20;
                })
                ->sortBy('distance')
                ->take(6)
                ->values();

            return $branches;
        } catch (\Exception $e) {
            Log::error('Error getting nearby branches: ' . $e->getMessage());
            return [];
        }
    }

    // ================================================================
    // ================================================================
    // POPULARITY-BASED RECOMMENDATIONS (Fallback)
    // ================================================================
    // ================================================================

    private function getTopBranchesGlobal($customerLocation = null)
    {
        $cacheKey = 'top_branches_global_customer_v2';
        $cacheDuration = 1800;

        return Cache::remember($cacheKey, $cacheDuration, function () use ($customerLocation) {
            $recommendations = ['branches' => []];

            try {
                $branches = Branch::where('active', 1)
                    ->where('branch_status', 1)
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->withCount(['bookings as recent_bookings' => function ($q) {
                        $q->where('booking_status', 4)->where('date_start', '>=', now()->subDays(30));
                    }])
                    ->withCount(['bookings as total_bookings' => function ($q) {
                        $q->where('booking_status', 4);
                    }])
                    ->withAvg('feedbacks', 'rating')
                    ->withCount('feedbacks')
                    ->withCount(['serviceNames as active_services_count' => function ($q) {
                        $q->where('active', 1)->where('service_name_status', 1);
                    }])
                    ->get()
                    ->map(function ($branch) use ($customerLocation) {
                        $recentBookings = $branch->recent_bookings ?? 0;
                        $totalBookings = $branch->total_bookings ?? 0;
                        $avgRating = $branch->feedbacks_avg_rating ?? 0;
                        $reviewCount = $branch->feedbacks_count ?? 0;
                        $serviceCount = $branch->active_services_count ?? 0;

                        $distanceScore = 0;
                        $distance = null;
                        $distanceLabel = 'Unknown distance';

                        if ($customerLocation && isset($customerLocation['latitude']) && isset($customerLocation['longitude']) && $branch->latitude && $branch->longitude) {
                            $distance = $this->calculateDistance(
                                $customerLocation['latitude'],
                                $customerLocation['longitude'],
                                $branch->latitude,
                                $branch->longitude
                            );
                            $distanceScore = $this->getDistanceScore($distance);

                            if ($distance < 1) {
                                $distanceLabel = '< 1 km';
                            } else {
                                $distanceLabel = round($distance, 1) . ' km';
                            }
                        }

                        $bookingScore = 0;
                        $reasons = [];

                        if ($totalBookings > 0) {
                            $bookingScore += $totalBookings * 100;

                            if ($totalBookings >= 100) {
                                $reasons[] = 'Very popular (' . $totalBookings . ' total bookings)';
                            } elseif ($totalBookings >= 50) {
                                $reasons[] = 'Popular choice (' . $totalBookings . ' total bookings)';
                            } elseif ($totalBookings >= 20) {
                                $reasons[] = $totalBookings . ' total bookings';
                            } else {
                                $reasons[] = $totalBookings . ' bookings';
                            }
                        }

                        if ($recentBookings > 0) {
                            $bookingScore += $recentBookings * 150;
                            if ($recentBookings >= 20) {
                                $reasons[] = '🔥 Trending - ' . $recentBookings . ' bookings this month';
                            } elseif ($recentBookings >= 10) {
                                $reasons[] = '📈 Popular this month (' . $recentBookings . ' bookings)';
                            } else {
                                $reasons[] = $recentBookings . ' recent bookings';
                            }
                        }

                        $ratingScore = 0;

                        if ($reviewCount > 0) {
                            $ratingScore += $avgRating * 60;
                            $ratingScore += min($reviewCount * 2, 200);

                            if ($avgRating >= 4.5 && $reviewCount >= 10) {
                                $reasons[] = '⭐ ' . number_format($avgRating, 1) . '★ (' . $reviewCount . ' reviews)';
                            } elseif ($avgRating >= 4.0) {
                                $reasons[] = '⭐ ' . number_format($avgRating, 1) . '★ rated';
                            }
                        } else {
                            $ratingScore = 10;
                            $reasons[] = 'New branch';
                        }

                        $serviceBonus = min($serviceCount * 10, 50);
                        if ($serviceCount >= 10) {
                            $reasons[] = 'Wide variety (' . $serviceCount . ' services)';
                        } elseif ($serviceCount >= 5) {
                            $reasons[] = 'Good selection (' . $serviceCount . ' services)';
                        }

                        if ($distance !== null && $distanceScore > 50) {
                            $reasons[] = '📍 ' . $distanceLabel . ' from you';
                        }

                        $finalScore = ($bookingScore * 0.4) + ($ratingScore * 0.3) + ($distanceScore * 0.3) + $serviceBonus;

                        return [
                            'branch' => $branch,
                            'distance' => $distance,
                            'distance_label' => $distanceLabel,
                            'score' => round($finalScore),
                            'distance_score' => round($distanceScore),
                            'booking_score' => round($bookingScore),
                            'rating_score' => round($ratingScore),
                            'service_bonus' => $serviceBonus,
                            'reasons' => $reasons,
                            'metrics' => [
                                'recent_bookings' => $recentBookings,
                                'total_bookings' => $totalBookings,
                                'avg_rating' => $avgRating,
                                'review_count' => $reviewCount,
                                'service_count' => $serviceCount
                            ]
                        ];
                    });

                $topBranches = $branches->sortByDesc('score')->take(10)->values();

                foreach ($topBranches as $index => $item) {
                    $reason = !empty($item['reasons'])
                        ? implode(' • ', array_slice($item['reasons'], 0, 2))
                        : 'Popular branch with good services';

                    $recommendations['branches'][] = [
                        'rank' => $index + 1,
                        'branch' => $item['branch'],
                        'distance' => $item['distance'],
                        'distance_label' => $item['distance_label'],
                        'score' => $item['score'],
                        'reason' => $reason,
                        'stats' => $item['metrics']
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Error in getTopBranchesGlobal', [
                    'error' => $e->getMessage()
                ]);
            }

            return $recommendations;
        });
    }

    // ================================================================
    // ================================================================
    // UTILITY FUNCTIONS
    // ================================================================
    // ================================================================

    private function hasUserData($customer): bool
    {
        if (!$customer) {
            return false;
        }

        $hasPreference = CustomerPreference::where('customer_account_id', $customer->id)
            ->whereNotNull('preferences_completed_at')
            ->where(function($query) {
                $query->whereNotNull('preferred_features')
                      ->orWhereNotNull('preferred_space_types')
                      ->orWhereNotNull('preferred_time_slots')
                      ->orWhereNotNull('preferred_branch_ids')
                      ->orWhereNotNull('preferred_category_ids')
                      ->orWhereNotNull('preferred_service_ids');
            })
            ->exists();

        $hasBooking = Booking::where('customer_account_id', $customer->id)
            ->where('booking_status', 4)
            ->exists();

        $hasFeedback = Feedback::where('customer_account_id', $customer->id)
            ->exists();

        return $hasPreference || $hasBooking || $hasFeedback;
    }

    public function getAllUniqueFeatures()
    {
        return Cache::remember('all_unique_features_v3', 3600, function () {
            $allFeatures = Branch::where('active', 1)
                ->where('branch_status', 1)
                ->whereNotNull('features')
                ->where('features', '!=', '')
                ->pluck('features')
                ->flatMap(function ($features) {
                    return collect($this->getFeaturesArray($features))
                        ->map(fn($feature) => trim($feature))
                        ->filter();
                })
                ->values();

            $groupedFeatures = [];

            foreach ($allFeatures as $feature) {
                $normalized = strtolower(preg_replace('/[^a-z0-9]/', '', $feature));

                $found = false;
                foreach ($groupedFeatures as $index => $group) {
                    if ($group['normalized'] === $normalized) {
                        $groupedFeatures[$index]['variations'][] = $feature;
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $groupedFeatures[] = [
                        'normalized' => $normalized,
                        'variations' => [$feature]
                    ];
                }
            }

            $uniqueFeatures = [];
            foreach ($groupedFeatures as $group) {
                usort($group['variations'], function($a, $b) {
                    return strlen($a) - strlen($b);
                });
                $uniqueFeatures[] = $group['variations'][0];
            }

            sort($uniqueFeatures);
            return $uniqueFeatures;
        });
    }

    private function filterBranchesBySearch($branchData, $searchQuery)
    {
        if (empty($branchData['branches']) || empty($searchQuery)) {
            return $branchData;
        }

        $searchLower = strtolower($searchQuery);

        $filteredBranches = array_filter($branchData['branches'], function($rec) use ($searchLower) {
            $branch = $rec['branch'];

            $inName = strpos(strtolower($branch->branch_name), $searchLower) !== false;
            $inLocation = strpos(strtolower($branch->location ?? ''), $searchLower) !== false;

            $inFeatures = false;
            if ($branch->features) {
                $features = $this->getFeaturesArray($branch->features);
                foreach ($features as $feature) {
                    if (strpos(strtolower($feature), $searchLower) !== false) {
                        $inFeatures = true;
                        break;
                    }
                }
            }

            return $inName || $inLocation || $inFeatures;
        });

        $branchData['branches'] = array_values($filteredBranches);
        return $branchData;
    }

    private function filterSingleBranchBySearch($branchData, $searchQuery)
    {
        if (empty($branchData) || empty($searchQuery)) {
            return $branchData;
        }

        $searchLower = strtolower($searchQuery);
        $branch = $branchData['branch'];

        $inName = strpos(strtolower($branch->branch_name), $searchLower) !== false;
        $inLocation = strpos(strtolower($branch->location ?? ''), $searchLower) !== false;

        $inFeatures = false;
        if ($branch->features) {
            $features = $this->getFeaturesArray($branch->features);
            foreach ($features as $feature) {
                if (strpos(strtolower($feature), $searchLower) !== false) {
                    $inFeatures = true;
                    break;
                }
            }
        }

        if ($inName || $inLocation || $inFeatures) {
            return $branchData;
        }

        return null;
    }

    private function filterNearbyBranchesBySearch($branches, $searchQuery)
    {
        if (empty($branches) || empty($searchQuery)) {
            return $branches;
        }

        $searchLower = strtolower($searchQuery);

        return $branches->filter(function($branch) use ($searchLower) {
            $inName = strpos(strtolower($branch->branch_name), $searchLower) !== false;
            $inLocation = strpos(strtolower($branch->location ?? ''), $searchLower) !== false;

            $inFeatures = false;
            if ($branch->features) {
                $features = $this->getFeaturesArray($branch->features);
                foreach ($features as $feature) {
                    if (strpos(strtolower($feature), $searchLower) !== false) {
                        $inFeatures = true;
                        break;
                    }
                }
            }

            return $inName || $inLocation || $inFeatures;
        })->values();
    }

    private function handleSearch($query)
    {
        $branches = Branch::where('active', 1)
            ->where('branch_status', 1)
            ->where(function ($q) use ($query) {
                $q->where('branch_name', 'LIKE', "%{$query}%")
                  ->orWhere('location', 'LIKE', "%{$query}%")
                  ->orWhere('features', 'LIKE', "%{$query}%");
            })
            ->limit(10)
            ->get();

        return [
            'branches' => $branches,
            'services' => collect()
        ];
    }

    private function cleanFilterParameters(Request $request)
    {
        $filteredParams = array_filter($request->all(), function ($value) {
            return $value !== '' && $value !== null;
        });
        unset($filteredParams['page']);
        return $filteredParams;
    }

    // ================================================================
    // ================================================================
    // PREFERENCES METHODS
    // ================================================================
    // ================================================================

    public function getPeakHoursOptions()
    {
        $peakHours = [
            ['value' => 'morning', 'label' => 'Morning (6:00 AM - 12:00 PM)', 'icon' => 'fa-sun'],
            ['value' => 'afternoon', 'label' => 'Afternoon (12:00 PM - 5:00 PM)', 'icon' => 'fa-cloud-sun'],
            ['value' => 'evening', 'label' => 'Evening (5:00 PM - 9:00 PM)', 'icon' => 'fa-moon'],
            ['value' => 'late_night', 'label' => 'Late Night (9:00 PM - 6:00 AM)', 'icon' => 'fa-star-of-life']
        ];

        return response()->json($peakHours);
    }

    public function showPreferencesForm()
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return redirect()->route('sub_three.home.showHome');
        }

        $preference = CustomerPreference::where('customer_account_id', $customer->id)->first();
        if (!$preference) {
            $preference = new CustomerPreference([
                'customer_account_id' => $customer->id,
                'has_booking_history' => false,
            ]);
        }

        $customerLocation = $this->getCurrentLocation(request());
        $placeName = isset($customerLocation['place_name']) ? $customerLocation['place_name'] : null;
        $fullAddress = isset($customerLocation['full_address']) ? $customerLocation['full_address'] : null;

        $decodedPreferences = [
            'preferred_features' => $this->decodePreferenceField($preference->preferred_features),
            'preferred_space_types' => $this->decodePreferenceField($preference->preferred_space_types),
            'preferred_peak_hours' => $this->decodePreferenceField($preference->preferred_peak_hours),
            'preferred_time_slots' => $this->decodePreferenceField($preference->preferred_time_slots ?? []),
            'preferred_session_duration' => $preference->preferred_session_duration ?? null,
            'preferred_start_time' => $preference->preferred_start_time ?? null,
            'preferred_end_time' => $preference->preferred_end_time ?? null,
            'min_rate_preferred' => $preference->min_rate_preferred ?? null,
            'max_rate_preferred' => $preference->max_rate_preferred ?? null,
        ];

        $allFeatures = $this->getAllUniqueFeatures();

        $spaceTypes = ServiceName::where('active', 1)
            ->where('service_name_status', 1)
            ->whereNotNull('space_type')
            ->distinct()
            ->pluck('space_type')
            ->map(function ($type) {
                $labels = [
                    'seat' => 'Individual Seat',
                    'room' => 'Private Room',
                    'meeting_room' => 'Meeting Room',
                    'office' => 'Office Space'
                ];
                return [
                    'value' => $type,
                    'label' => $labels[$type] ?? ucfirst($type)
                ];
            })
            ->values();

        $peakHours = [
            ['value' => 'morning', 'label' => 'Morning (6:00 AM - 12:00 PM)'],
            ['value' => 'afternoon', 'label' => 'Afternoon (12:00 PM - 5:00 PM)'],
            ['value' => 'evening', 'label' => 'Evening (5:00 PM - 9:00 PM)'],
            ['value' => 'late_night', 'label' => 'Late Night (9:00 PM - 6:00 AM)']
        ];

        $timeSlots = [
            ['value' => 'early_morning', 'label' => 'Early Morning (5:00 AM - 8:00 AM)'],
            ['value' => 'morning', 'label' => 'Morning (8:00 AM - 12:00 PM)'],
            ['value' => 'afternoon', 'label' => 'Afternoon (12:00 PM - 5:00 PM)'],
            ['value' => 'evening', 'label' => 'Evening (5:00 PM - 9:00 PM)'],
            ['value' => 'late_night', 'label' => 'Late Night (9:00 PM - 11:59 PM)']
        ];

        $durationOptions = ServiceName::where('active', 1)
            ->where('service_name_status', 1)
            ->whereNotNull('time_duration')
            ->where('time_duration', '!=', '')
            ->distinct()
            ->pluck('time_duration')
            ->map(function ($duration) {
                $displayLabel = $this->formatDurationLabel($duration);
                $numericValue = $this->normalizeDurationToHours($duration);
                return [
                    'value' => $numericValue,
                    'label' => $displayLabel,
                    'original' => $duration
                ];
            })
            ->sortBy(function ($item) {
                return $item['value'];
            })
            ->values()
            ->toArray();

        if (empty($durationOptions)) {
            $durationOptions = [
                ['value' => 1, 'label' => '1 hour'],
                ['value' => 2, 'label' => '2 hours'],
                ['value' => 3, 'label' => '3 hours'],
                ['value' => 4, 'label' => '4 hours'],
                ['value' => 6, 'label' => '6 hours'],
                ['value' => 8, 'label' => '8 hours (Full Day)'],
            ];
        }

        $rateOptions = [
            ['value' => 'under_50', 'label' => 'Under ₱50/hr', 'min' => 0, 'max' => 50],
            ['value' => '50_100', 'label' => '₱50 - ₱100/hr', 'min' => 50, 'max' => 100],
            ['value' => '100_200', 'label' => '₱100 - ₱200/hr', 'min' => 100, 'max' => 200],
            ['value' => '200_300', 'label' => '₱200 - ₱300/hr', 'min' => 200, 'max' => 300],
            ['value' => '300_plus', 'label' => '₱300+/hr', 'min' => 300, 'max' => null],
        ];

        $ratingOptions = [
            ['value' => 'any', 'label' => 'Any rating'],
            ['value' => '4.5', 'label' => '⭐ 4.5+ stars'],
            ['value' => '4.0', 'label' => '⭐ 4.0+ stars'],
            ['value' => '3.5', 'label' => '⭐ 3.5+ stars'],
            ['value' => '3.0', 'label' => '⭐ 3.0+ stars'],
        ];

        return view('customer.home.preferences', compact(
            'preference',
            'decodedPreferences',
            'allFeatures',
            'spaceTypes',
            'peakHours',
            'timeSlots',
            'durationOptions',
            'rateOptions',
            'ratingOptions',
            'customerLocation',
            'placeName',
            'fullAddress'
        ));
    }

    private function formatDurationLabel($duration)
    {
        if (empty($duration)) {
            return 'Unknown';
        }

        $durationLower = strtolower(trim($duration));

        if (strpos($durationLower, 'hour') !== false || strpos($durationLower, 'hr') !== false) {
            return $duration;
        }

        if (strpos($durationLower, 'min') !== false) {
            return $duration;
        }

        if (is_numeric($duration)) {
            $hours = (float) $duration;
            if ($hours == 1) {
                return '1 hour';
            } elseif ($hours == floor($hours)) {
                return (int) $hours . ' hours';
            } else {
                return $hours . ' hours';
            }
        }

        return $duration;
    }

    private function decodePreferenceField($field)
    {
        if (empty($field)) {
            return [];
        }

        if (is_array($field)) {
            return $field;
        }

        if (is_string($field)) {
            $decoded = json_decode($field, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    public function savePreferences(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }
            return redirect()->route('login');
        }

        $isAjax = $request->ajax() || $request->wantsJson();

        $rules = [
            'features' => 'nullable',
            'space_types' => 'nullable',
            'peak_hours' => 'nullable',
            'time_slots' => 'nullable',
            'session_duration' => 'nullable',
            'preferred_start_time' => 'nullable',
            'preferred_end_time' => 'nullable',
            'min_rate' => 'nullable|numeric|min:0',
            'max_rate' => 'nullable|numeric|min:0',
            'min_rating' => 'nullable|in:any,4.5,4.0,3.5,3.0',
        ];

        $validated = $request->validate($rules);

        $preference = CustomerPreference::where('customer_account_id', $customer->id)->first();
        if (!$preference) {
            $preference = new CustomerPreference(['customer_account_id' => $customer->id]);
        }

        $features = $request->input('features');
        if (is_string($features)) {
            $features = json_decode($features, true);
        }
        if (!is_array($features)) {
            $features = [];
        }

        $spaceTypes = $request->input('space_types');
        if (is_string($spaceTypes)) {
            $spaceTypes = json_decode($spaceTypes, true);
        }
        if (!is_array($spaceTypes)) {
            $spaceTypes = [];
        }

        $peakHours = $request->input('peak_hours');
        if (is_string($peakHours)) {
            $peakHours = json_decode($peakHours, true);
        }
        if (!is_array($peakHours)) {
            $peakHours = [];
        }

        $timeSlots = $request->input('time_slots');
        if (is_string($timeSlots)) {
            $timeSlots = json_decode($timeSlots, true);
        }
        if (!is_array($timeSlots)) {
            $timeSlots = [];
        }

        $sessionDuration = $request->input('session_duration');
        if ($sessionDuration === '' || $sessionDuration === null) {
            $sessionDuration = null;
        } else {
            $sessionDuration = $this->normalizeDurationToHours($sessionDuration);
            $sessionDuration = round($sessionDuration, 1);
        }

        $preferredStartTime = $request->input('preferred_start_time');
        if ($preferredStartTime === '' || $preferredStartTime === null) {
            $preferredStartTime = null;
        }

        $preferredEndTime = $request->input('preferred_end_time');
        if ($preferredEndTime === '' || $preferredEndTime === null) {
            $preferredEndTime = null;
        }

        $minRate = $request->input('min_rate');
        if ($minRate === '' || $minRate === null) {
            $minRate = null;
        } elseif (is_numeric($minRate)) {
            $minRate = (float) $minRate;
        }

        $maxRate = $request->input('max_rate');
        if ($maxRate === '' || $maxRate === null) {
            $maxRate = null;
        } elseif (is_numeric($maxRate)) {
            $maxRate = (float) $maxRate;
        }

        $minRating = $request->input('min_rating');
        if ($minRating === '' || $minRating === null || $minRating === 'any') {
            $minRating = null;
        } elseif (is_numeric($minRating)) {
            $minRating = (float) $minRating;
        }

        $preference->preferred_features = $features;
        $preference->preferred_space_types = $spaceTypes;
        $preference->preferred_peak_hours = $peakHours;
        $preference->preferred_time_slots = $timeSlots;
        $preference->preferred_session_duration = $sessionDuration;
        $preference->preferred_start_time = $preferredStartTime;
        $preference->preferred_end_time = $preferredEndTime;
        $preference->min_rate_preferred = $minRate;
        $preference->max_rate_preferred = $maxRate;
        $preference->min_rating_preferred = $minRating;

        $preference->preferred_seat_count = null;
        $preference->min_price_preferred = null;
        $preference->max_price_preferred = null;

        $preference->preferences_completed_at = now();
        $preference->preference_strength = $preference->calculateStrength();

        $preference->save();

        // Clear recommendation cache
        Cache::forget('hybrid_recommendation_' . $customer->id);
        Cache::forget('top_branches_global_customer_v2');
        Cache::forget('all_unique_features_v3');

        if (!$isAjax) {
            return redirect()->route('sub_three.home.showHome')
                ->with('success', 'Preferences saved successfully!')
                ->with('preferences_updated', true);
        }

        return response()->json([
            'success' => true,
            'message' => 'Preferences saved successfully',
            'preference_strength' => $preference->preference_strength,
            'redirect_url' => route('sub_three.home.showHome', ['preferences_saved' => 'true'])
        ]);
    }

    /**
     * Get match percentage for a specific branch (AJAX endpoint)
     */
    public function getBranchMatchPercentage(Request $request, $branchId)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $customerPreference = CustomerPreference::where('customer_account_id', $customer->id)->first();
        if (!$customerPreference || !$customerPreference->isCompleted()) {
            return response()->json(['error' => 'No preferences found'], 404);
        }

        $branch = Branch::where('uuid', $branchId)->orWhere('id', $branchId)->first();
        if (!$branch) {
            return response()->json(['error' => 'Branch not found'], 404);
        }

        $customerLocation = $this->getCurrentLocation($request);
        $bookingCount = Booking::where('customer_account_id', $customer->id)
            ->where('booking_status', 4)
            ->count();

        $hasEnoughHistory = $bookingCount >= self::COLD_START_BOOKING_THRESHOLD;
        $similarUsers = $hasEnoughHistory ? $this->findSimilarUsers($customer->id) : [];

        // Calculate Content-Based Score
        $locationScore = $this->calculateLocationScore($branch, 
            isset($customerLocation['latitude']) ? $customerLocation['latitude'] : null,
            isset($customerLocation['longitude']) ? $customerLocation['longitude'] : null
        );
        $featuresScore = $this->calculateFeaturesMatch($branch, $customerPreference);
        $rateScore = $this->calculateRateMatch($branch, $customerPreference);
        $spaceTypeScore = $this->calculateSpaceTypeMatch($branch, $customerPreference);
        $timeSlotScore = $this->calculateTimeSlotMatch($branch, $customerPreference);
        $durationScore = $this->calculateDurationMatch($branch, $customerPreference);
        $ratingScore = $this->calculateRatingScore($branch);

        $contentScore = (
            ($locationScore * self::RECOMMENDATION_WEIGHTS['location']) +
            ($featuresScore * self::RECOMMENDATION_WEIGHTS['features']) +
            ($rateScore * self::RECOMMENDATION_WEIGHTS['rate']) +
            ($spaceTypeScore * self::RECOMMENDATION_WEIGHTS['space_type']) +
            ($timeSlotScore * self::RECOMMENDATION_WEIGHTS['time_slot']) +
            ($durationScore * self::RECOMMENDATION_WEIGHTS['duration']) +
            ($ratingScore * self::RECOMMENDATION_WEIGHTS['rating'])
        );

        // Calculate Collaborative Score (if enough bookings)
        $collaborativeScore = null;
        if ($hasEnoughHistory && !empty($similarUsers)) {
            $collaborativeScore = $this->calculateCollaborativeScore($customer, $branch, $similarUsers);
        }

        // Determine final score
        $finalScore = $contentScore;
        $recommendationType = 'content_based';
        $hybridAlpha = null;

        if ($contentScore !== null && $collaborativeScore !== null) {
            $alpha = self::COLLABORATIVE_ALPHA;
            $hybridAlpha = $alpha;
            $finalScore = ($alpha * $collaborativeScore) + ((1 - $alpha) * $contentScore);
            $recommendationType = 'hybrid';
        } elseif ($collaborativeScore !== null) {
            $finalScore = $collaborativeScore;
            $recommendationType = 'collaborative_only';
        }

        $distance = $this->calculateDistance(
            isset($customerLocation['latitude']) ? $customerLocation['latitude'] : null,
            isset($customerLocation['longitude']) ? $customerLocation['longitude'] : null,
            $branch->latitude,
            $branch->longitude
        );

        $scores = [
            'location' => round($locationScore),
            'features' => round($featuresScore),
            'rate' => round($rateScore),
            'space_type' => round($spaceTypeScore),
            'time_slot' => round($timeSlotScore),
            'duration' => round($durationScore),
            'rating' => round($ratingScore),
        ];

        $response = [
            'success' => true,
            'branch_id' => $branch->id,
            'branch_name' => $branch->branch_name,
            'match_percentage' => round($finalScore),
            'scores' => $scores,
            'content_score' => round($contentScore),
            'collaborative_score' => $collaborativeScore !== null ? round($collaborativeScore) : null,
            'hybrid_score' => ($contentScore !== null && $collaborativeScore !== null) ? round($finalScore) : null,
            'hybrid_alpha' => $hybridAlpha,
            'recommendation_type' => $recommendationType,
            'match_reason' => $this->buildMatchReason(
                $contentScore, $collaborativeScore, $recommendationType,
                $scores, $distance, $similarUsers
            ),
            'matched_features' => $this->getMatchedFeatures($branch, $customerPreference),
            'distance' => $distance,
            'distance_label' => $distance !== null ? ($distance < 1 ? '< 1 km' : round($distance, 1) . ' km') : null,
            'collaborative_data' => ($collaborativeScore !== null) ? $this->getCollaborativeData($customer, $branch, $similarUsers) : null
        ];

        return response()->json($response);
    }

    /**
     * Get match percentages for all branches (AJAX endpoint)
     */
    public function getAllBranchMatchPercentages(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $customerPreference = CustomerPreference::where('customer_account_id', $customer->id)->first();
        if (!$customerPreference || !$customerPreference->isCompleted()) {
            return response()->json(['error' => 'No preferences found'], 404);
        }

        $customerLocation = $this->getCurrentLocation($request);
        $bookingCount = Booking::where('customer_account_id', $customer->id)
            ->where('booking_status', 4)
            ->count();

        $hasEnoughHistory = $bookingCount >= self::COLD_START_BOOKING_THRESHOLD;
        $similarUsers = $hasEnoughHistory ? $this->findSimilarUsers($customer->id) : [];

        $branches = Branch::where('active', 1)
            ->where('branch_status', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $results = [];
        $seenBranchIds = [];

        foreach ($branches as $branch) {
            if (in_array($branch->id, $seenBranchIds)) {
                continue;
            }
            $seenBranchIds[] = $branch->id;

            // Content-Based Score
            $locationScore = $this->calculateLocationScore($branch,
                isset($customerLocation['latitude']) ? $customerLocation['latitude'] : null,
                isset($customerLocation['longitude']) ? $customerLocation['longitude'] : null
            );
            $featuresScore = $this->calculateFeaturesMatch($branch, $customerPreference);
            $rateScore = $this->calculateRateMatch($branch, $customerPreference);
            $spaceTypeScore = $this->calculateSpaceTypeMatch($branch, $customerPreference);
            $timeSlotScore = $this->calculateTimeSlotMatch($branch, $customerPreference);
            $durationScore = $this->calculateDurationMatch($branch, $customerPreference);
            $ratingScore = $this->calculateRatingScore($branch);

            $contentScore = (
                ($locationScore * self::RECOMMENDATION_WEIGHTS['location']) +
                ($featuresScore * self::RECOMMENDATION_WEIGHTS['features']) +
                ($rateScore * self::RECOMMENDATION_WEIGHTS['rate']) +
                ($spaceTypeScore * self::RECOMMENDATION_WEIGHTS['space_type']) +
                ($timeSlotScore * self::RECOMMENDATION_WEIGHTS['time_slot']) +
                ($durationScore * self::RECOMMENDATION_WEIGHTS['duration']) +
                ($ratingScore * self::RECOMMENDATION_WEIGHTS['rating'])
            );

            // Collaborative Score (if enough bookings)
            $collaborativeScore = null;
            if ($hasEnoughHistory && !empty($similarUsers)) {
                $collaborativeScore = $this->calculateCollaborativeScore($customer, $branch, $similarUsers);
            }

            // Determine final score
            $finalScore = $contentScore;
            $recommendationType = 'content_based';
            $hybridAlpha = null;

            if ($contentScore !== null && $collaborativeScore !== null) {
                $alpha = self::COLLABORATIVE_ALPHA;
                $hybridAlpha = $alpha;
                $finalScore = ($alpha * $collaborativeScore) + ((1 - $alpha) * $contentScore);
                $recommendationType = 'hybrid';
            } elseif ($collaborativeScore !== null) {
                $finalScore = $collaborativeScore;
                $recommendationType = 'collaborative_only';
            }

            $distance = $this->calculateDistance(
                isset($customerLocation['latitude']) ? $customerLocation['latitude'] : null,
                isset($customerLocation['longitude']) ? $customerLocation['longitude'] : null,
                $branch->latitude,
                $branch->longitude
            );

            $scores = [
                'location' => round($locationScore),
                'features' => round($featuresScore),
                'rate' => round($rateScore),
                'space_type' => round($spaceTypeScore),
                'time_slot' => round($timeSlotScore),
                'duration' => round($durationScore),
                'rating' => round($ratingScore),
            ];

            $results[] = [
                'branch_id' => $branch->id,
                'branch_name' => $branch->branch_name,
                'match_percentage' => round($finalScore),
                'scores' => $scores,
                'content_score' => round($contentScore),
                'collaborative_score' => $collaborativeScore !== null ? round($collaborativeScore) : null,
                'hybrid_score' => ($contentScore !== null && $collaborativeScore !== null) ? round($finalScore) : null,
                'hybrid_alpha' => $hybridAlpha,
                'recommendation_type' => $recommendationType,
                'match_reason' => $this->buildMatchReason(
                    $contentScore, $collaborativeScore, $recommendationType,
                    $scores, $distance, $similarUsers
                ),
                'distance' => $distance,
                'distance_label' => $distance !== null ? ($distance < 1 ? '< 1 km' : round($distance, 1) . ' km') : null,
            ];
        }

        usort($results, function($a, $b) {
            return $b['match_percentage'] <=> $a['match_percentage'];
        });

        $hasHybrid = false;
        $hasContent = false;
        $hasCollaborative = false;
        foreach ($results as $result) {
            if ($result['recommendation_type'] === 'hybrid') $hasHybrid = true;
            if ($result['recommendation_type'] === 'content_based') $hasContent = true;
            if ($result['recommendation_type'] === 'collaborative_only') $hasCollaborative = true;
        }

        $overallType = 'hybrid';
        if ($hasHybrid) {
            $overallType = 'hybrid';
        } elseif ($hasCollaborative && !$hasContent) {
            $overallType = 'collaborative_only';
        } elseif ($hasContent && !$hasCollaborative) {
            $overallType = 'content_based';
        }

        return response()->json([
            'success' => true,
            'branches' => $results,
            'total' => count($results),
            'recommendation_type' => $overallType,
            'hybrid_alpha' => ($hasHybrid || ($hasContent && $hasCollaborative)) ? self::COLLABORATIVE_ALPHA : null
        ]);
    }

    /**
     * Get customer recommendations for guests
     */
    public function getCustomerRecommendations($limit = 10)
    {
        try {
            $cacheKey = 'guest_ml_recommendations_v2';
            $cacheDuration = 3600;

            return Cache::remember($cacheKey, $cacheDuration, function () use ($limit) {
                $services = ServiceName::where('active', 1)
                    ->where('service_name_status', 1)
                    ->with(['serviceCategory', 'branch'])
                    ->withCount(['bookings as booking_count' => function ($q) {
                        $q->where('booking_status', 4);
                    }])
                    ->withCount(['feedbacks as feedback_count'])
                    ->withAvg('feedbacks', 'rating')
                    ->get();

                if ($services->isEmpty()) {
                    return [];
                }

                $scoredServices = [];
                foreach ($services as $service) {
                    $bookingScore = $service->booking_count ?? 0;
                    $feedbackCount = $service->feedback_count ?? 0;
                    $avgRating = $service->feedbacks_avg_rating ?? 0;

                    $score = ($bookingScore * 0.4) +
                            ($avgRating * 20 * 0.4) +
                            (min($feedbackCount, 100) * 0.2);

                    if ($service->discount && $service->discount > 0) {
                        $score += 10;
                    }

                    $scoredServices[] = [
                        'service' => $service,
                        'score' => $score,
                        'reason' => $this->getRecommendationReason($bookingScore, $feedbackCount, $avgRating, $service),
                        'metrics' => [
                            'bookings' => $bookingScore,
                            'feedback_count' => $feedbackCount,
                            'avg_rating' => $avgRating
                        ]
                    ];
                }

                usort($scoredServices, function ($a, $b) {
                    return $b['score'] <=> $a['score'];
                });

                return array_slice($scoredServices, 0, $limit);
            });
        } catch (\Exception $e) {
            Log::error('Error getting guest recommendations', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function getRecommendationReason($bookings, $feedbackCount, $avgRating, $service = null)
    {
        $reasons = [];

        if ($service && $service->discount && $service->discount > 0) {
            $oldPrice = $service->old_price ?: $service->price;
            $discountAmount = $oldPrice - $service->price;
            $discountPercentage = $oldPrice > 0 ? round(($discountAmount / $oldPrice) * 100) : 0;
            if ($discountPercentage > 0) {
                $reasons[] = "💰 {$discountPercentage}% OFF!";
            }
        }

        if ($bookings >= 50) {
            $reasons[] = "🔥 Very popular with {$bookings} bookings";
        } elseif ($bookings >= 20) {
            $reasons[] = "📈 Popular service with {$bookings} bookings";
        } elseif ($bookings >= 10) {
            $reasons[] = "📊 {$bookings} bookings this month";
        }

        if ($avgRating >= 4.5 && $feedbackCount >= 10) {
            $reasons[] = "⭐ " . number_format($avgRating, 1) . "★ (" . $feedbackCount . " reviews)";
        } elseif ($avgRating >= 4.0 && $feedbackCount >= 5) {
            $reasons[] = "⭐ " . number_format($avgRating, 1) . "★ rated";
        } elseif ($feedbackCount >= 3) {
            $reasons[] = "📝 {$feedbackCount} customer reviews";
        }

        if (empty($reasons)) {
            $reasons[] = "✨ Recommended for you";
        }

        return implode(" • ", array_slice($reasons, 0, 2));
    }

    /**
     * Get service feedbacks
     */
    public function getServiceFeedbacks($serviceUuid)
    {
        try {
            $service = ServiceName::where('uuid', $serviceUuid)->first();
            if (!$service) {
                return [
                    'average_rating' => 0,
                    'total_reviews' => 0,
                    'rating_breakdown' => []
                ];
            }

            $averageRating = Feedback::where('service_name_id', $service->id)
                ->where('approved', 1)
                ->where('active', 1)
                ->avg('rating');

            $totalReviews = Feedback::where('service_name_id', $service->id)
                ->where('approved', 1)
                ->where('active', 1)
                ->count();

            $ratingBreakdown = $this->getRatingBreakdownByService($service->id);

            return [
                'average_rating' => $averageRating ?? 0,
                'total_reviews' => $totalReviews,
                'rating_breakdown' => $ratingBreakdown
            ];
        } catch (\Exception $e) {
            Log::error('Error getting service feedbacks', [
                'service_uuid' => $serviceUuid,
                'error' => $e->getMessage()
            ]);
            return [
                'average_rating' => 0,
                'total_reviews' => 0,
                'rating_breakdown' => []
            ];
        }
    }

    /**
     * Get branch feedbacks
     */
    public function getBranchFeedbacks($branchUuid)
    {
        try {
            $branch = Branch::where('uuid', $branchUuid)->first();
            if (!$branch) {
                return [
                    'average_rating' => 0,
                    'total_reviews' => 0,
                    'rating_breakdown' => []
                ];
            }

            $averageRating = Feedback::where('branch_id', $branch->id)
                ->where('approved', 1)
                ->where('active', 1)
                ->avg('rating');

            $totalReviews = Feedback::where('branch_id', $branch->id)
                ->where('approved', 1)
                ->where('active', 1)
                ->count();

            $ratingBreakdown = $this->getRatingBreakdownByBranch($branch->id);

            return [
                'average_rating' => $averageRating ?? 0,
                'total_reviews' => $totalReviews,
                'rating_breakdown' => $ratingBreakdown
            ];
        } catch (\Exception $e) {
            Log::error('Error getting branch feedbacks', [
                'branch_uuid' => $branchUuid,
                'error' => $e->getMessage()
            ]);
            return [
                'average_rating' => 0,
                'total_reviews' => 0,
                'rating_breakdown' => []
            ];
        }
    }

    private function getRatingBreakdownByService($serviceNameId)
    {
        try {
            $breakdown = Feedback::where('service_name_id', $serviceNameId)
                ->where('approved', 1)
                ->where('active', 1)
                ->select('rating', DB::raw('COUNT(*) as count'))
                ->groupBy('rating')
                ->orderBy('rating', 'desc')
                ->get();

            $fullBreakdown = [];
            for ($i = 5; $i >= 1; $i--) {
                $ratingData = $breakdown->where('rating', $i)->first();
                $fullBreakdown[] = [
                    'rating' => $i,
                    'count' => $ratingData ? $ratingData->count : 0
                ];
            }
            return $fullBreakdown;
        } catch (\Exception $e) {
            Log::error('Error getting rating breakdown by service', [
                'service_name_id' => $serviceNameId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function getRatingBreakdownByBranch($branchId)
    {
        try {
            $breakdown = Feedback::where('branch_id', $branchId)
                ->where('approved', 1)
                ->where('active', 1)
                ->select('rating', DB::raw('COUNT(*) as count'))
                ->groupBy('rating')
                ->orderBy('rating', 'desc')
                ->get();

            $fullBreakdown = [];
            for ($i = 5; $i >= 1; $i--) {
                $ratingData = $breakdown->where('rating', $i)->first();
                $fullBreakdown[] = [
                    'rating' => $i,
                    'count' => $ratingData ? $ratingData->count : 0
                ];
            }
            return $fullBreakdown;
        } catch (\Exception $e) {
            Log::error('Error getting rating breakdown by branch', [
                'branch_id' => $branchId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function getAverageRatings()
    {
        try {
            return Cache::remember('average_ratings_global_v2', 3600, function () {
                $serviceCategories = ServiceCategory::where('active', 1)
                    ->where('service_category_status', 1)
                    ->withAvg('feedbacks', 'rating')
                    ->withCount('feedbacks')
                    ->get(['id', 'uuid', 'service_category'])
                    ->map(function ($category) {
                        return (object) [
                            'id' => $category->id,
                            'uuid' => $category->uuid,
                            'service_category' => $category->service_category,
                            'average_rating' => $category->feedbacks_avg_rating ?? 0,
                            'review_count' => $category->feedbacks_count ?? 0,
                        ];
                    })
                    ->sortByDesc('average_rating')
                    ->values();

                $serviceNames = ServiceName::where('active', 1)
                    ->where('service_name_status', 1)
                    ->withAvg('feedbacks', 'rating')
                    ->withCount('feedbacks')
                    ->get(['id', 'uuid', 'service_name'])
                    ->map(function ($service) {
                        return (object) [
                            'id' => $service->id,
                            'uuid' => $service->uuid,
                            'service_name' => $service->service_name,
                            'average_rating' => $service->feedbacks_avg_rating ?? 0,
                            'review_count' => $service->feedbacks_count ?? 0,
                        ];
                    })
                    ->sortByDesc('average_rating')
                    ->values();

                $branches = Branch::where('active', 1)
                    ->where('branch_status', 1)
                    ->withAvg('feedbacks', 'rating')
                    ->withCount('feedbacks')
                    ->get(['id', 'uuid', 'branch_name'])
                    ->map(function ($branch) {
                        return (object) [
                            'id' => $branch->id,
                            'uuid' => $branch->uuid,
                            'branch_name' => $branch->branch_name,
                            'average_rating' => $branch->feedbacks_avg_rating ?? 0,
                            'review_count' => $branch->feedbacks_count ?? 0,
                        ];
                    })
                    ->sortByDesc('average_rating')
                    ->values();

                return [
                    'service_categories' => $serviceCategories,
                    'service_names'      => $serviceNames,
                    'branches'            => $branches,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error getting average ratings', [
                'error' => $e->getMessage()
            ]);
            return [
                'service_categories' => collect(),
                'service_names'      => collect(),
                'branches'            => collect(),
            ];
        }
    }
}