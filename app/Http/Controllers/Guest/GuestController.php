<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Customer\HomeController;
use App\Models\Branch;
use App\Models\Feedback;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GuestController extends Controller
{
    /**
     * Display the guest home page with TOP BRANCHES only
     * Same as customer home page when no user data exists
     */
    public function showHome(Request $request)
    {
        // ============================================================
        // GET GUEST LOCATION
        // ============================================================
        $guestLocation = $this->getGuestLocation($request);

        // ============================================================
        // GET TOP BRANCHES (SAME AS CUSTOMER)
        // ============================================================
        $topBranches = $this->getGuestTopBranches($guestLocation);

        // ============================================================
        // HANDLE SEARCH
        // ============================================================
        $searchQuery = $request->input('search', '');
        $searchResults = collect();

        if ($searchQuery) {
            $searchResults = $this->handleGuestSearch($searchQuery);
            $topBranches = $this->filterBranchesBySearch($topBranches, $searchQuery);
        }

        // ============================================================
        // GET FILTER DATA
        // ============================================================
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

        // Get unique features from HomeController
        $homeController = app(HomeController::class);
        $uniqueFeatures = $homeController->getAllUniqueFeatures();

        // Guest has no user data
        $hasUserData = false;
        $customerPreference = null;
        $showPreferencesModal = false;
        $preferenceStrength = 0;
        $recommendedBranch = null;

        // Pass location to view
        $hasLocation = !is_null($guestLocation);
        $locationSource = $guestLocation['source'] ?? 'unknown';
        $locationErrorMessage = null;

        if (!$guestLocation) {
            $locationErrorMessage = 'Please share your current location to get accurate branch recommendations.';
        }

        return view('guests.home', compact(
            'allBranches',
            'serviceCategories',
            'serviceNames',
            'uniqueFeatures',
            'recommendedBranch',
            'topBranches',
            'showPreferencesModal',
            'customerPreference',
            'searchQuery',
            'searchResults',
            'hasUserData',
            'guestLocation',
            'locationErrorMessage',
            'hasLocation',
            'locationSource',
            'preferenceStrength'
        ));
    }

    /**
     * Get guest location (session only, not stored in DB)
     */
    private function getGuestLocation($request)
    {
        // Priority 1: Check session for location
        if (session()->has('guest_location')) {
            $location = session('guest_location');
            
            if (isset($location['expires_at']) && $location['expires_at'] > now()) {
                return [
                    'latitude' => (float) $location['latitude'],
                    'longitude' => (float) $location['longitude'],
                    'source' => 'browser',
                    'expires_at' => $location['expires_at']
                ];
            }
            session()->forget('guest_location');
        }

        // Priority 2: Check if location was passed via AJAX
        if ($request->has('latitude') && $request->has('longitude')) {
            session(['guest_location' => [
                'latitude' => (float) $request->latitude,
                'longitude' => (float) $request->longitude,
                'expires_at' => now()->addMinutes(30)
            ]]);
            
            return [
                'latitude' => (float) $request->latitude,
                'longitude' => (float) $request->longitude,
                'source' => 'browser',
                'expires_at' => now()->addMinutes(30)
            ];
        }

        // Priority 3: Get location from IP
        $ipLocation = $this->getLocationFromIP();
        if ($ipLocation) {
            return [
                'latitude' => (float) $ipLocation['latitude'],
                'longitude' => (float) $ipLocation['longitude'],
                'source' => 'ip',
                'expires_at' => now()->addMinutes(60)
            ];
        }

        // Priority 4: Default fallback (Davao City)
        return [
            'latitude' => 7.083333,
            'longitude' => 125.616667,
            'source' => 'default',
            'expires_at' => now()->addHours(24)
        ];
    }

    /**
     * Get location from IP address
     */
    private function getLocationFromIP()
    {
        try {
            $ip = request()->ip();
            $response = \Illuminate\Support\Facades\Http::timeout(5)->get("http://ip-api.com/json/{$ip}?fields=status,lat,lon");
            
            if ($response->successful() && $response->json('status') === 'success') {
                return [
                    'latitude' => $response->json('lat'),
                    'longitude' => $response->json('lon')
                ];
            }
        } catch (\Exception $e) {
            Log::warning('IP Geolocation failed for guest', ['error' => $e->getMessage()]);
        }
        return null;
    }

    /**
     * Calculate distance between two points
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        if (!$lat1 || !$lon1 || !$lat2 || !$lon2) {
            return null;
        }

        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }

    /**
     * Get distance score
     */
    private function getDistanceScore($distance)
    {
        if ($distance === null) return 0;
        $maxDistance = 50;
        if ($distance >= $maxDistance) return 0;
        return round((1 - ($distance / $maxDistance)) * 100);
    }

    /**
     * Get top branches for guests (cached) - Same as customer
     */
    private function getGuestTopBranches($guestLocation = null)
    {
        $cacheKey = 'guest_top_branches_v2';
        $cacheDuration = 1800;

        return Cache::remember($cacheKey, $cacheDuration, function () use ($guestLocation) {
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
                    ->map(function ($branch) use ($guestLocation) {
                        $recentBookings = $branch->recent_bookings ?? 0;
                        $totalBookings = $branch->total_bookings ?? 0;
                        $avgRating = $branch->feedbacks_avg_rating ?? 0;
                        $reviewCount = $branch->feedbacks_count ?? 0;
                        $serviceCount = $branch->active_services_count ?? 0;

                        $distanceScore = 0;
                        $distance = null;
                        $distanceLabel = 'Unknown distance';
                        
                        if ($guestLocation && $branch->latitude && $branch->longitude) {
                            $distance = $this->calculateDistance(
                                $guestLocation['latitude'],
                                $guestLocation['longitude'],
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

                        $finalScore = ($distanceScore * 0.3) + ($bookingScore * 0.4) + ($ratingScore * 0.3) + $serviceBonus;

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
                Log::error('Error in getGuestTopBranches', [
                    'error' => $e->getMessage()
                ]);
            }

            return $recommendations;
        });
    }

    /**
     * Get features as array
     */
    private function getFeaturesArray($features)
    {
        if (empty($features)) {
            return [];
        }
        
        if (is_array($features)) {
            return array_map('trim', $features);
        }
        
        if (is_string($features)) {
            $featureArray = array_map('trim', explode(',', $features));
            return array_filter($featureArray);
        }
        
        return [];
    }

    /**
     * Filter branches by search query
     */
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

    /**
     * Handle search for guests
     */
    private function handleGuestSearch($query)
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

    /**
     * Update guest location via AJAX
     */
    public function updateLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180'
        ]);

        try {
            session(['guest_location' => [
                'latitude' => (float) $request->latitude,
                'longitude' => (float) $request->longitude,
                'expires_at' => now()->addMinutes(30)
            ]]);

            // Clear caches
            Cache::forget('guest_top_branches_v2');

            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully! (Valid for 30 minutes)',
                'expires_at' => now()->addMinutes(30)->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating guest location: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update location. Please try again.'
            ], 500);
        }
    }

    /**
     * Clear guest location
     */
    public function clearLocation(Request $request)
    {
        try {
            session()->forget('guest_location');
            
            Cache::forget('guest_top_branches_v2');

            return response()->json([
                'success' => true,
                'message' => 'Location cleared successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error clearing guest location: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear location. Please try again.'
            ], 500);
        }
    }

    // =========================================================================
    // 📧 CONTACT MESSAGE
    // =========================================================================

    public function sendMessage(Request $request)
    {
        // Honeypot check
        if ($request->filled('website')) {
            return back()->with('success', 'Message sent!');
        }
    
        // Timing check
        $formLoadedAt = $request->input('form_loaded_at');
        if ($formLoadedAt && (time() - $formLoadedAt) < 3) {
            return back()->with('success', 'Message sent!');
        }
    
        // Block auto-responder emails
        $blockedEmailPatterns = ['noreply', 'no-reply', 'mailer-daemon', 'postmaster', 'do-not-reply', 'bounce'];
        foreach ($blockedEmailPatterns as $pattern) {
            if (str_contains(strtolower($request->input('email', '')), $pattern) ||
                str_contains(strtolower($request->input('name', '')), $pattern)) {
                return back()->with('success', 'Message sent!');
            }
        }
    
        // Block spam message content
        $blockedMessagePatterns = [
            'jackpot', 'you win', 'congratulations', 'winner', 'winning',
            'prize', 'claim your', 'click here', 'free money', 'lottery',
            'https://', 'http://', '.xyz', '.tk', '.ml', '.ga', '.cf',
            'user id:', 'userid:', 'screen of success', '$27,000,000',
            'casino', 'bitcoin', 'crypto', 'investment opportunity',
        ];
    
        $messageInput = strtolower($request->input('message', ''));
        $nameInput = strtolower($request->input('name', ''));
    
        foreach ($blockedMessagePatterns as $pattern) {
            if (str_contains($messageInput, strtolower($pattern)) ||
                str_contains($nameInput, strtolower($pattern))) {
                return back()->with('success', 'Message sent!');
            }
        }
    
        // Validate email domain
        $emailDomain = substr(strrchr($request->input('email', ''), '@'), 1);
        if (!$emailDomain || !checkdnsrr($emailDomain, 'MX')) {
            return back()->with('error', 'Please enter a valid email address.');
        }
    
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'message' => 'required|string|max:1000',
        ]);
    
        try {
            // Send to admin
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
    
            // Send thank you
            $thankYouContent = "
    Dear {$validated['name']},
    
    Thank you for contacting LinkudHub! ✨
    
    We're excited to hear from you and appreciate you taking the time to reach out. Your message has been successfully delivered to our team.
    
    📋 Message Summary:
    {$validated['message']}
    
    ⏰ What happens next?
    - Your message has been queued for review
    - Our team will respond within 24-48 hours
    - We'll address all your questions and concerns
    
    📍 In the meantime, you can:
    - Browse our website: linkudhub.com
    - Check branch locations and open hours
    - Learn about premium spaces
    
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

    // =========================================================================
    // ⭐ FEEDBACK & RATING METHODS
    // =========================================================================

    public function showFeedbacks(Request $request)
    {
        $homeController = app(HomeController::class);

        $query = Feedback::with(['serviceName', 'branch', 'serviceCategory'])
            ->where('approved', 1)
            ->where('active', 1)
            ->latest();

        if ($request->filled('service_category')) {
            $query->where('service_category_id', $request->service_category);
        }
        if ($request->filled('service_name')) {
            $query->where('service_name_id', $request->service_name);
        }
        if ($request->filled('branch')) {
            $query->where('branch_id', $request->branch);
        }
        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        $feedbacks = $query->paginate(10);
        
        $averageRatings = $homeController->getAverageRatings();

        $averageRatings = array_merge([
            'service_categories' => collect(),
            'service_names' => collect(),
            'branches' => collect(),
        ], $averageRatings ?? []);

        $serviceCategories = ServiceCategory::where('active', 1)
            ->where('service_category_status', 1)
            ->orderBy('service_category')
            ->get(['id', 'service_category']);

        $serviceNames = ServiceName::where('active', 1)
            ->where('service_name_status', 1)
            ->orderBy('service_name')
            ->get(['id', 'service_name']);

        $branches = Branch::where('active', 1)
            ->where('branch_status', 1)
            ->orderBy('branch_name')
            ->get(['id', 'branch_name']);

        return view('guests.feedbacks.index', compact(
            'feedbacks', 
            'averageRatings', 
            'serviceCategories', 
            'serviceNames', 
            'branches'
        ));
    }

    public function showServiceFeedbacks($serviceUuid, Request $request)
    {
        $homeController = app(HomeController::class);

        $service = ServiceName::with(['serviceCategory', 'branch'])
            ->where('uuid', $serviceUuid)
            ->where('active', 1)
            ->where('service_name_status', 1)
            ->firstOrFail();

        $query = Feedback::with(['serviceName', 'branch', 'serviceCategory'])
            ->where('service_name_id', $service->id)
            ->where('approved', 1)
            ->where('active', 1)
            ->latest();

        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        $feedbacks = $query->paginate(10);
        
        $feedbackData = $homeController->getServiceFeedbacks($serviceUuid);
        $averageRating = $feedbackData['average_rating'] ?? 0;
        $totalReviews = $feedbackData['total_reviews'] ?? 0;
        $ratingBreakdown = $feedbackData['rating_breakdown'] ?? [];

        $relatedServices = ServiceName::where('branch_id', $service->branch_id)
            ->where('service_category_id', $service->service_category_id)
            ->where('id', '!=', $service->id)
            ->where('active', 1)
            ->where('service_name_status', 1)
            ->limit(4)
            ->get();

        return view('guests.feedbacks.service_feedbacks', compact(
            'service', 
            'feedbacks', 
            'averageRating', 
            'totalReviews', 
            'ratingBreakdown', 
            'relatedServices'
        ));
    }

    public function showBranchFeedbacks($branchUuid, Request $request)
    {
        $homeController = app(HomeController::class);

        $branch = Branch::where('uuid', $branchUuid)
            ->where('active', 1)
            ->where('branch_status', 1)
            ->firstOrFail();

        $query = Feedback::with(['serviceName', 'serviceCategory'])
            ->where('branch_id', $branch->id)
            ->where('approved', 1)
            ->where('active', 1)
            ->latest();

        if ($request->filled('service_category')) {
            $query->where('service_category_id', $request->service_category);
        }
        if ($request->filled('service_name')) {
            $query->where('service_name_id', $request->service_name);
        }
        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        $feedbacks = $query->paginate(10);
        
        $feedbackData = $homeController->getBranchFeedbacks($branchUuid);
        $averageRating = $feedbackData['average_rating'] ?? 0;
        $totalReviews = $feedbackData['total_reviews'] ?? 0;
        $ratingBreakdown = $feedbackData['rating_breakdown'] ?? [];

        $serviceCategories = ServiceCategory::whereHas('serviceNames', function ($q) use ($branch) {
            $q->where('branch_id', $branch->id)->where('active', 1)->where('service_name_status', 1);
        })->where('active', 1)->where('service_category_status', 1)->get(['id', 'service_category']);

        $serviceNames = ServiceName::where('branch_id', $branch->id)
            ->where('active', 1)
            ->where('service_name_status', 1)
            ->get(['id', 'service_name']);

        return view('guests.feedbacks.branch_feedbacks', compact(
            'branch', 
            'feedbacks', 
            'averageRating', 
            'totalReviews', 
            'ratingBreakdown', 
            'serviceCategories', 
            'serviceNames'
        ));
    }
}