@extends('layouts.app')

@section('title', 'Home - Find Your Workspace')

@section('content')
    <!-- Header with Location -->
    <header class="bg-white sticky top-[63px] z-[50] transition-all duration-300 shadow-sm" id="main-header">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center py-3 space-y-3 md:space-y-0">
                <!-- Location Bar -->
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <div class="flex items-center gap-2 bg-gray-50 rounded-full px-4 py-2 border border-gray-200 hover:border-[#7f5539] transition-colors cursor-pointer min-h-[44px] flex-1 md:flex-initial" onclick="toggleLocationDropdown()">
                        <i class="fas fa-location-dot text-[#7f5539] text-sm"></i>
                        <span class="text-sm text-gray-700 truncate max-w-[200px] md:max-w-[250px]" id="locationDisplay">
                            @if(isset($customerLocation) && isset($customerLocation['source']) && $customerLocation['source'] != 'default' && $customerLocation['source'] != 'unknown')
                                @if(isset($customerLocation['place_name']) && !empty($customerLocation['place_name']))
                                    {{ $customerLocation['place_name'] }}
                                @elseif(isset($customerLocation['full_address']) && !empty($customerLocation['full_address']))
                                    {{ $customerLocation['full_address'] }}
                                @else
                                    Your Location
                                @endif
                            @else
                                Select Location
                            @endif
                        </span>
                        <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                    </div>
                    
                    @if(isset($customerLocation) && isset($customerLocation['source']) && $customerLocation['source'] != 'default' && $customerLocation['source'] != 'unknown')
                        <div class="flex items-center gap-1 bg-green-50 text-green-700 px-3 py-1 rounded-full text-xs">
                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                            <span>Live</span>
                        </div>
                    @else
                        <div class="flex items-center gap-1 bg-yellow-50 text-yellow-700 px-3 py-1 rounded-full text-xs">
                            <i class="fas fa-exclamation-triangle text-[10px]"></i>
                            <span>No location</span>
                        </div>
                    @endif
                </div>

                <!-- Navigation -->
                <div class="relative w-full md:w-auto">
                    <div class="overflow-x-auto thin-scroll">
                        <nav class="flex space-x-1 text-xs min-w-max px-2" id="mainNav">
                            @if($hasUserData && $recommendedBranch)
                                <a href="#recommended-branch" class="nav-link scroll-link active text-[#7f5539] font-medium transition-colors px-3 py-1.5 whitespace-nowrap bg-[#7f5539]/10 hover:bg-[#7f5539]/20 rounded-full flex items-center min-h-[44px] md:min-h-0" data-section="recommended-branch">
                                    <i class="fas fa-star mr-1.5 text-yellow-500"></i> Recommended
                                </a>
                            @endif
                            @if(!empty($topBranches['branches']))
                                <a href="#top-branches" class="nav-link scroll-link text-gray-700 hover:text-[#7f5539] font-medium transition-colors px-3 py-1.5 whitespace-nowrap bg-gray-50 hover:bg-gray-100 rounded-full flex items-center min-h-[44px] md:min-h-0" data-section="top-branches">
                                    <i class="fas fa-fire mr-1.5 text-orange-500"></i> Top
                                </a>
                            @endif
                            <a href="#how-it-works" class="nav-link scroll-link text-gray-700 hover:text-[#7f5539] font-medium transition-colors px-3 py-1.5 whitespace-nowrap bg-gray-50 hover:bg-gray-100 rounded-full flex items-center min-h-[44px] md:min-h-0" data-section="how-it-works">
                                <i class="fas fa-info-circle mr-1.5 text-blue-500"></i> How It Works
                            </a>
                            <a href="{{ route('sub_three.home.feedbacks') }}" class="text-gray-700 hover:text-[#7f5539] font-medium transition-colors px-3 py-1.5 whitespace-nowrap bg-gray-50 hover:bg-gray-100 rounded-full flex items-center min-h-[44px] md:min-h-0">
                                <i class="fas fa-star mr-1.5 text-yellow-400"></i> Reviews
                            </a>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Location Dropdown -->
    <div id="locationDropdown" class="hidden fixed top-[120px] left-0 right-0 z-[60] bg-white shadow-2xl border-b border-gray-100 py-4 px-4">
        <div class="container mx-auto max-w-3xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-800">Your Location</h3>
                <button onclick="toggleLocationDropdown()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="bg-gray-50 rounded-xl p-4 mb-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-[#7f5539]/10 rounded-full flex items-center justify-center">
                            <i class="fas fa-location-dot text-[#7f5539]"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800 text-sm">
                                @if(isset($customerLocation) && isset($customerLocation['source']) && $customerLocation['source'] != 'default' && $customerLocation['source'] != 'unknown')
                                    {{ isset($customerLocation['place_name']) ? $customerLocation['place_name'] : 'Current Location' }}
                                @else
                                    No Location Set
                                @endif
                            </p>
                            <p class="text-xs text-gray-500" id="locationDetails">
                                @if(isset($customerLocation) && isset($customerLocation['source']) && $customerLocation['source'] != 'default' && $customerLocation['source'] != 'unknown')
                                    @if(isset($customerLocation['full_address']) && !empty($customerLocation['full_address']))
                                        <span class="text-gray-700">{{ $customerLocation['full_address'] }}</span>
                                        <span class="text-gray-400 ml-2">({{ ucfirst($customerLocation['source']) }})</span>
                                    @elseif(isset($customerLocation['place_name']) && !empty($customerLocation['place_name']))
                                        <span class="text-gray-700">{{ $customerLocation['place_name'] }}</span>
                                        <span class="text-gray-400 ml-2">({{ ucfirst($customerLocation['source']) }})</span>
                                    @endif
                                    @if(isset($customerLocation['city']) && !empty($customerLocation['city']))
                                        <br>
                                        <span class="text-xs text-gray-400">📍 {{ $customerLocation['city'] }}{{ isset($customerLocation['state']) && !empty($customerLocation['state']) ? ', ' . $customerLocation['state'] : '' }}</span>
                                    @endif
                                @else
                                    Please share your location for accurate recommendations
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        @if(isset($customerLocation) && isset($customerLocation['source']) && $customerLocation['source'] != 'default' && $customerLocation['source'] != 'unknown')
                            <button onclick="clearCustomerLocation()" 
                                class="text-sm text-red-600 hover:text-red-700 px-3 py-1.5 border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
                                Clear
                            </button>
                        @endif
                        <button onclick="getCustomerLocation()" 
                            class="text-sm bg-[#7f5539] text-white px-4 py-1.5 rounded-lg hover:bg-[#6b4f3c] transition-colors">
                            <i class="fas fa-sync-alt mr-1.5"></i> Update
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="text-xs text-gray-400 flex items-center gap-4 flex-wrap">
                <span><i class="far fa-clock mr-1"></i> Updates every 30 min</span>
                <span><i class="fas fa-shield-alt mr-1"></i> Your privacy is protected</span>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-[#f5f0eb] to-[#e6ddd4] py-8 md:py-12">
        <div class="container mx-auto px-3">
            <div class="max-w-xl mx-auto text-center">
                <h1 class="text-xl md:text-2xl font-bold text-[#4a3429] mb-3 leading-tight">
                    @if($hasUserData && $recommendedBranch)
                        🎯 Your Perfect Workspace Found!
                    @elseif($hasUserData && !$recommendedBranch)
                        No Perfect Match Found
                    @elseif($hasUserData)
                        Welcome Back! Find Your Perfect Workspace
                    @else
                        Find Your Perfect Workspace
                    @endif
                </h1>
                <p class="text-gray-600 mb-4 max-w-md mx-auto text-xs">
                    @if($hasUserData && $recommendedBranch)
                        We've found the best match for you based on your preferences and location
                    @elseif($hasUserData && !$recommendedBranch)
                        We couldn't find a branch that matches your preferences. Update your preferences to get better recommendations.
                    @elseif($hasUserData)
                        We've matched branches based on your preferences and location
                    @else
                        Discover the most popular co-working spaces near you
                    @endif
                </p>

                <!-- Search Bar -->
                <div class="bg-white rounded-lg p-3 mb-4 shadow-sm border border-[#e6ddd4]">
                    <form action="{{ route('sub_three.home.showHome') }}" method="GET" id="searchForm">
                        <div class="relative">
                            <input type="text" name="search" placeholder="Search branches"
                                class="w-full px-4 py-3 text-sm text-gray-800 rounded-lg border border-[#d4c4b2] focus:outline-none focus:ring-2 focus:ring-[#7f5539] focus:border-transparent pr-14 min-h-[44px]"
                                value="{{ $searchQuery }}" id="searchInput">
                            <button type="submit"
                                class="absolute right-0 top-0 h-full w-14 flex items-center justify-center text-gray-400 hover:text-[#7f5539] hover:bg-gray-50 rounded-r-lg transition-colors min-w-[44px] min-h-[44px]"
                                aria-label="Search">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Location Button -->
                @if(($hasUserData && $recommendedBranch) || !$hasUserData)
                    <div class="flex flex-col items-center mt-2">
                        <div class="flex flex-wrap items-center justify-center gap-3">
                            @if(isset($customerLocation) && isset($customerLocation['source']) && $customerLocation['source'] != 'default' && $customerLocation['source'] != 'unknown')
                                <div class="flex items-center gap-2 bg-green-50 text-green-700 px-4 py-2 rounded-lg text-sm max-w-full">
                                    <i class="fas fa-check-circle flex-shrink-0"></i>
                                    <span class="truncate max-w-[200px] md:max-w-[300px]">
                                        @if(isset($customerLocation['place_name']) && !empty($customerLocation['place_name']))
                                            {{ $customerLocation['place_name'] }}
                                        @elseif(isset($customerLocation['full_address']) && !empty($customerLocation['full_address']))
                                            {{ $customerLocation['full_address'] }}
                                        @else
                                            Location detected
                                        @endif
                                    </span>
                                    <button onclick="getCustomerLocation()" 
                                        class="text-xs text-[#7f5539] hover:text-[#6b4f3c] ml-2 underline flex-shrink-0">
                                        Update
                                    </button>
                                </div>
                            @else
                                <button onclick="getCustomerLocation()" 
                                    class="inline-flex items-center px-4 py-2 bg-[#7f5539] hover:bg-[#6b4f3c] text-white rounded-lg transition-colors text-sm">
                                    <i class="fas fa-location-dot mr-2"></i>
                                    <span id="locationBtnText">Share My Exact Location</span>
                                </button>
                            @endif
                        </div>
                        
                        <span id="locationStatus" class="mt-2 text-xs text-gray-500 hidden"></span>
                        
                        @if(isset($locationErrorMessage))
                            <p class="text-xs text-red-500 mt-2">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                {{ $locationErrorMessage }}
                            </p>
                        @endif
                    </div>
                @endif

                @if ($searchQuery)
                    <div class="mt-4 text-left">
                        <p class="text-xs text-gray-500">
                            Showing results for: <span class="font-medium text-[#4a3429]">"{{ $searchQuery }}"</span>
                            @php
                                $totalResults = 0;
                                if ($recommendedBranch) {
                                    $totalResults += 1;
                                }
                                if (!empty($topBranches['branches'])) {
                                    $totalResults += count($topBranches['branches']);
                                }
                            @endphp
                            @if ($totalResults > 0)
                                ({{ $totalResults }} branch{{ $totalResults != 1 ? 'es' : '' }} found)
                            @else
                                (No branches found)
                            @endif
                            <button type="button" onclick="clearSearch()" class="ml-2 text-[#7f5539] hover:text-[#6b4f3c] text-xs underline">
                                Clear search
                            </button>
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <!-- ============================================================ -->
    <!-- SECTION: RECOMMENDED BRANCH                                   -->
    <!-- ============================================================ -->
    @if($hasUserData && $recommendedBranch)
        <section id="recommended-branch" class="py-8 bg-white scroll-section">
            <div class="container mx-auto px-4">
                <!-- Section Header -->
                <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-8">
                    <div>
                        <h2 class="text-2xl md:text-3xl font-bold text-[#4a3429]">
                            Recommended Workspace
                        </h2>
                    </div>
                    
                    <a href="{{ route('sub_three.home.preferences.form') }}" 
                        class="text-sm bg-[#7f5539] text-white px-4 py-2 rounded-full hover:bg-[#6b4f3c] transition-colors min-h-[44px] flex items-center gap-2 mt-4 md:mt-0">
                        <i class="fas fa-pen"></i>
                        <span>Update Preferences</span>
                    </a>
                </div>

                @php
                    $branch = $recommendedBranch['branch'];
                    $matchPercentage = $recommendedBranch['match_percentage'];
                    $matchReason = $recommendedBranch['match_reason'];
                    $scores = $recommendedBranch['scores'] ?? [];
                    $contentScore = $recommendedBranch['content_score'] ?? null;
                    $collaborativeScore = $recommendedBranch['collaborative_score'] ?? null;
                    $recommendationType = $recommendedBranch['recommendation_type'] ?? 'content_based';
                    $matchedFeatures = $recommendedBranch['matched_features'] ?? [];
                    
                    // Normalize and deduplicate branch features for display
                    $allBranchFeatures = [];
                    if ($branch->features) {
                        $rawFeatures = array_map('trim', explode(',', $branch->features));
                        // Normalize features for consistent display
                        $normalizationMap = [
                            'wi-fi' => 'Wi-Fi',
                            'wifi' => 'Wi-Fi',
                            'wi fi' => 'Wi-Fi',
                            'free parking' => 'Parking',
                            'parking' => 'Parking',
                            'free coffee' => 'Coffee',
                            'coffee' => 'Coffee',
                        ];
                        foreach ($rawFeatures as $feature) {
                            $lower = strtolower(trim($feature));
                            $normalized = $normalizationMap[$lower] ?? $feature;
                            if (!in_array($normalized, $allBranchFeatures)) {
                                $allBranchFeatures[] = $normalized;
                            }
                        }
                    }
                    
                    $distance = $recommendedBranch['distance'] ?? null;
                    $firstService = $branch->serviceNames->first();
                    $firstCategory = $firstService ? $firstService->serviceCategory : null;
                @endphp

                <!-- Branch Card -->
                <div class="group bg-white rounded-2xl overflow-hidden shadow-xl hover:shadow-2xl transition-all duration-300 border-2 
                    {{ $recommendationType == 'hybrid' ? 'border-blue-500/30 hover:border-blue-500/50' : 
                       ($recommendationType == 'collaborative_only' ? 'border-purple-500/30 hover:border-purple-500/50' : 
                       'border-[#7f5539]/20 hover:border-[#7f5539]/40') }} 
                    max-w-4xl mx-auto">
                    <div class="grid grid-cols-1 md:grid-cols-5">
                        <!-- Image Section -->
                        <div class="md:col-span-2 h-64 md:h-auto relative overflow-hidden">
                            @if ($branch->branch_profile)
                                <img src="{{ asset('storage/app/public/' . ltrim($branch->branch_profile, '/')) }}"
                                    alt="{{ $branch->branch_name }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                    loading="lazy">
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-[#7f5539] to-[#9c6644] flex items-center justify-center">
                                    <i class="fas fa-store text-white text-6xl"></i>
                                </div>
                            @endif
                            
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                            
                            <!-- Match Percentage Badge -->
                            <div class="absolute top-4 right-4">
                                <div class="bg-black/70 backdrop-blur-sm text-white text-sm font-bold px-4 py-2 rounded-full shadow-lg flex items-center gap-2 
                                    {{ $matchPercentage >= 80 ? 'border-green-400' : ($matchPercentage >= 60 ? 'border-blue-400' : 'border-yellow-400') }} border-2">
                                    <i class="fas fa-star text-yellow-400"></i>
                                    {{ $matchPercentage }}% Match
                                </div>
                            </div>

                            <!-- Recommendation Type Badge (on image) -->
                            <div class="absolute top-4 left-4">
                                <span class="px-3 py-1 text-[10px] rounded-full 
                                    {{ $recommendationType == 'hybrid' ? 'bg-blue-500/90' : 
                                       ($recommendationType == 'collaborative_only' ? 'bg-purple-500/90' : 
                                       'bg-gray-500/90') }} 
                                    backdrop-blur-sm text-white flex items-center gap-1">
                                    <i class="fas 
                                        {{ $recommendationType == 'hybrid' ? 'fa-code-branch' : 
                                           ($recommendationType == 'collaborative_only' ? 'fa-users' : 
                                           'fa-sliders-h') }} text-[8px]"></i>
                                    {{ ucfirst($recommendationType == 'collaborative_only' ? 'Collaborative' : 
                                       ($recommendationType == 'hybrid' ? 'Hybrid' : 'Content')) }}
                                </span>
                            </div>

                            <!-- Status Badge -->
                            <div class="absolute bottom-4 left-4">
                                <span class="px-3 py-1 text-xs rounded-full {{ $branch->branch_status ? 'bg-green-500/90 backdrop-blur-sm text-white' : 'bg-red-500/90 backdrop-blur-sm text-white' }}">
                                    {{ $branch->branch_status ? 'Open Now' : 'Closed' }}
                                </span>
                            </div>

                            <!-- Trending Badge -->
                            @if(($branch->recent_bookings ?? 0) >= 10)
                                <div class="absolute bottom-4 right-4">
                                    <span class="px-3 py-1 text-xs rounded-full bg-red-500/90 backdrop-blur-sm text-white animate-pulse flex items-center gap-1.5">
                                        <i class="fas fa-fire text-[10px]"></i>
                                        Trending
                                    </span>
                                </div>
                            @endif
                        </div>

                        <!-- Details Section -->
                        <div class="md:col-span-3 p-6 flex flex-col">
                            <div class="flex items-start justify-between mb-2">
                                <h3 class="font-bold text-xl text-[#4a3429] leading-tight group-hover:text-[#7f5539] transition-colors">
                                    {{ $branch->branch_name }}
                                </h3>
                                <div class="flex items-center gap-1 bg-gray-50 px-2.5 py-1.5 rounded-full">
                                    <i class="fas fa-star text-yellow-400 text-xs"></i>
                                    <span class="text-sm font-semibold text-gray-700">{{ number_format($branch->feedbacks_avg_rating ?? 0, 1) }}</span>
                                    @if(($branch->feedbacks_count ?? 0) > 0)
                                        <span class="text-xs text-gray-400">({{ $branch->feedbacks_count }})</span>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Location -->
                            <div class="flex items-center text-gray-500 text-sm mb-3">
                                <i class="fas fa-map-marker-alt text-red-400 mr-1.5 text-xs"></i>
                                @if (!empty($branch->google_map_url))
                                    <a href="{{ $branch->google_map_url }}" 
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="hover:text-[#7f5539] transition-colors truncate">
                                        {{ $branch->location ?? 'View on Map' }}
                                    </a>
                                @else
                                    <span class="truncate">{{ $branch->location ?? 'View on Map' }}</span>
                                @endif
                            </div>
                            
                            <!-- Distance -->
                            @if(isset($customerLocation) && $customerLocation['source'] != 'default' && $customerLocation['source'] != 'unknown' && $distance !== null)
                                <div class="mb-3 text-sm text-gray-500 flex items-center gap-2">
                                    <i class="fas fa-location-dot text-green-500"></i>
                                    <span class="font-medium">
                                        {{ $distance < 1 ? '< 1 km' : round($distance, 1) . ' km' }} from 
                                        {{ $customerLocation['place_name'] ?? 'your location' }}
                                    </span>
                                    <span class="text-xs text-gray-400">(Top priority)</span>
                                </div>
                            @endif

                            <!-- Priority Score Breakdown -->
                            <div class="mb-4 bg-gray-50 rounded-xl border border-gray-100 overflow-hidden">
                                <button onclick="togglePriorityBreakdown()" 
                                    type="button"
                                    class="w-full px-4 py-3 flex items-center justify-between hover:bg-gray-100/50 transition-colors group">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-chart-simple text-[#7f5539]"></i>
                                        <span class="text-xs font-semibold text-gray-700">📊 Priority Match Breakdown</span>
                                        <span class="text-[10px] text-gray-400 bg-gray-200 px-2 py-0.5 rounded-full">
                                            {{ $matchPercentage }}% Match
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-[#7f5539] bg-white px-2.5 py-1 rounded-full shadow-sm">
                                            {{ $matchPercentage }}% Match
                                        </span>
                                        <i id="accordionIcon" class="fas fa-chevron-down text-gray-400 text-xs group-hover:text-[#7f5539] transition-transform duration-300"></i>
                                    </div>
                                </button>
                                
                                <div id="priorityBreakdownContent" class="px-4 pb-4 pt-2 hidden">
                                    <!-- Content Based Score Display -->
                                    @if($recommendationType == 'content_based' && isset($contentScore))
                                        <div class="mb-3 p-3 bg-gradient-to-r from-gray-50 to-blue-50 rounded-lg border border-gray-200">
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="font-semibold text-gray-700">📊 Content-Based Score</span>
                                                <span class="text-[10px] text-gray-500">Based on your preferences</span>
                                            </div>
                                            <div class="flex items-center gap-4 mt-2">
                                                <div class="flex-1">
                                                    <div class="flex items-center justify-between text-[10px] text-gray-600">
                                                        <span>Content Score</span>
                                                        <span>{{ round($contentScore) }}%</span>
                                                    </div>
                                                    <div class="h-1.5 bg-gray-200 rounded-full mt-0.5">
                                                        <div class="h-full bg-blue-500 rounded-full" style="width: {{ round($contentScore) }}%"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="text-[10px] text-gray-400 mt-2">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                Make {{ 3 - ($bookingCount ?? 0) }} more bookings to unlock collaborative filtering.
                                            </p>
                                        </div>
                                    @endif

                                    <!-- Hybrid Score Display -->
                                    @if($recommendationType == 'hybrid' && isset($contentScore) && isset($collaborativeScore))
                                        <div class="mb-3 p-3 bg-gradient-to-r from-blue-50 to-purple-50 rounded-lg border border-blue-200">
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="font-semibold text-gray-700">🧠 Hybrid Score Breakdown</span>
                                            </div>
                                            <div class="flex items-center gap-4 mt-2">
                                                <div class="flex-1">
                                                    <div class="flex items-center justify-between text-[10px] text-gray-600">
                                                        <span>Content-Based</span>
                                                        <span>{{ round($contentScore) }}%</span>
                                                    </div>
                                                    <div class="h-1.5 bg-gray-200 rounded-full mt-0.5">
                                                        <div class="h-full bg-blue-500 rounded-full" style="width: {{ round($contentScore) }}%"></div>
                                                    </div>
                                                </div>
                                                <div class="text-gray-300 text-xs">+</div>
                                                <div class="flex-1">
                                                    <div class="flex items-center justify-between text-[10px] text-gray-600">
                                                        <span>Collaborative</span>
                                                        <span>{{ round($collaborativeScore) }}%</span>
                                                    </div>
                                                    <div class="h-1.5 bg-gray-200 rounded-full mt-0.5">
                                                        <div class="h-full bg-purple-500 rounded-full" style="width: {{ round($collaborativeScore) }}%"></div>
                                                    </div>
                                                </div>
                                                <div class="text-gray-300 text-xs">=</div>
                                                <div class="flex-1">
                                                    <div class="flex items-center justify-between text-[10px] font-bold text-[#7f5539]">
                                                        <span>Hybrid</span>
                                                        <span>{{ $matchPercentage }}%</span>
                                                    </div>
                                                    <div class="h-1.5 bg-gray-200 rounded-full mt-0.5">
                                                        <div class="h-full bg-[#7f5539] rounded-full" style="width: {{ $matchPercentage }}%"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <!-- Collaborative Only Display -->
                                    @if($recommendationType == 'collaborative_only' && isset($recommendedBranch['collaborative_data']))
                                        <div class="mb-3 p-3 bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg border border-purple-200">
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="font-semibold text-gray-700">🧠 Collaborative Filtering Only</span>
                                                <span class="text-[10px] text-gray-500">
                                                    {{ $recommendedBranch['collaborative_data']['similar_users_count'] ?? 0 }} similar users
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-4 mt-2">
                                                <div class="flex-1">
                                                    <div class="flex items-center justify-between text-[10px] text-gray-600">
                                                        <span>Predicted Rating</span>
                                                        <span>{{ $recommendedBranch['collaborative_data']['predicted_rating'] ?? 0 }} / 5.0</span>
                                                    </div>
                                                    <div class="h-1.5 bg-gray-200 rounded-full mt-0.5">
                                                        <div class="h-full bg-purple-500 rounded-full" style="width: {{ $recommendedBranch['collaborative_data']['score'] ?? 0 }}%"></div>
                                                    </div>
                                                </div>
                                                <div class="flex-1">
                                                    <div class="flex items-center justify-between text-[10px] text-gray-600">
                                                        <span>Location Boost</span>
                                                        <span>{{ $scores['location'] ?? 0 }}%</span>
                                                    </div>
                                                    <div class="h-1.5 bg-gray-200 rounded-full mt-0.5">
                                                        <div class="h-full bg-green-500 rounded-full" style="width: {{ $scores['location'] ?? 0 }}%"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="text-[10px] text-gray-400 mt-2">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                Since you haven't set preferences, recommendations are based on users with similar booking patterns.
                                                <a href="{{ route('sub_three.home.preferences.form') }}" class="text-purple-600 hover:underline">Set preferences →</a>
                                            </p>
                                        </div>
                                    @endif
                                    
                                    <!-- Detailed Score Cards -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        @php
                                            $priorityCategories = [
                                                'location' => ['label' => '📍 Location', 'weight' => 30, 'icon' => 'fa-location-dot', 'color' => 'green'],
                                                'features' => ['label' => '✨ Features', 'weight' => 20, 'icon' => 'fa-cubes', 'color' => 'blue'],
                                                'rate' => ['label' => '💰 Rate', 'weight' => 15, 'icon' => 'fa-tag', 'color' => 'yellow'],
                                                'space_type' => ['label' => '🪑 Space Type', 'weight' => 12, 'icon' => 'fa-chair', 'color' => 'purple'],
                                                'time_slot' => ['label' => '🕐 Time Slot', 'weight' => 10, 'icon' => 'fa-clock', 'color' => 'orange'],
                                                'duration' => ['label' => '⏱️ Duration', 'weight' => 8, 'icon' => 'fa-hourglass-half', 'color' => 'pink'],
                                                'rating' => ['label' => '⭐ Rating', 'weight' => 5, 'icon' => 'fa-star', 'color' => 'amber'],
                                            ];
                                            $iconMap = [
                                                'location' => 'fa-location-dot',
                                                'features' => 'fa-cubes',
                                                'rate' => 'fa-tag',
                                                'space_type' => 'fa-chair',
                                                'time_slot' => 'fa-clock',
                                                'duration' => 'fa-hourglass-half',
                                                'rating' => 'fa-star'
                                            ];
                                        @endphp
                                        @foreach($priorityCategories as $key => $category)
                                            @php
                                                $score = $scores[$key] ?? 0;
                                                $scoreColor = $score >= 80 ? 'text-green-600' : ($score >= 60 ? 'text-blue-600' : ($score >= 40 ? 'text-yellow-600' : 'text-gray-400'));
                                                $barColor = $score >= 80 ? 'bg-green-500' : ($score >= 60 ? 'bg-blue-500' : ($score >= 40 ? 'bg-yellow-500' : 'bg-gray-300'));
                                                $barWidth = round($score);
                                                $icon = $iconMap[$key] ?? 'fa-circle';
                                                $iconColor = $score >= 80 ? 'text-green-500' : ($score >= 60 ? 'text-blue-500' : ($score >= 40 ? 'text-yellow-500' : 'text-gray-400'));
                                            @endphp
                                            <div class="bg-white rounded-lg p-3 border border-gray-100 hover:shadow-sm transition-shadow group/card">
                                                <div class="flex items-center justify-between mb-1.5">
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-6 h-6 rounded-full bg-gray-50 flex items-center justify-center">
                                                            <i class="fas {{ $icon }} {{ $iconColor }} text-xs"></i>
                                                        </div>
                                                        <span class="text-xs font-medium text-gray-700">{{ $category['label'] }}</span>
                                                        <span class="text-[10px] text-gray-400">({{ $category['weight'] }}%)</span>
                                                    </div>
                                                    <span class="text-sm font-bold {{ $scoreColor }}">{{ $barWidth }}%</span>
                                                </div>
                                                <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                                    <div class="h-full {{ $barColor }} rounded-full transition-all duration-1000 ease-out priority-bar" 
                                                         style="width: 0%"
                                                         data-width="{{ $barWidth }}%">
                                                    </div>
                                                </div>
                                                <div class="mt-1 text-[10px] {{ $scoreColor }}">
                                                    @if($score >= 80)
                                                        <i class="fas fa-check-circle mr-1"></i> Excellent match
                                                    @elseif($score >= 60)
                                                        <i class="fas fa-check-circle mr-1"></i> Good match
                                                    @elseif($score >= 40)
                                                        <i class="fas fa-minus-circle mr-1"></i> Partial match
                                                    @else
                                                        <i class="fas fa-times-circle mr-1"></i> Low match
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <!-- Features/Amenities -->
                            @if (!empty($allBranchFeatures))
                                <div class="mb-3">
                                    <div class="flex flex-wrap gap-1.5 features-container" id="features-recommended-{{ $branch->id }}">
                                        @php
                                            // Normalize matched features for comparison
                                            $normalizedMatchedFeatures = [];
                                            $normalizationMap = [
                                                'wi-fi' => 'Wi-Fi',
                                                'wifi' => 'Wi-Fi',
                                                'wi fi' => 'Wi-Fi',
                                                'free parking' => 'Parking',
                                                'parking' => 'Parking',
                                                'free coffee' => 'Coffee',
                                                'coffee' => 'Coffee',
                                            ];
                                            foreach ($matchedFeatures as $mf) {
                                                $lower = strtolower(trim($mf));
                                                $normalizedMatchedFeatures[] = $normalizationMap[$lower] ?? $mf;
                                            }
                                            
                                            $displayFeatures = array_slice($allBranchFeatures, 0, 5);
                                            $hiddenFeatures = array_slice($allBranchFeatures, 5);
                                            $hasMoreFeatures = count($hiddenFeatures) > 0;
                                        @endphp
                                        
                                        @foreach ($displayFeatures as $feature)
                                            @php
                                                $isMatched = in_array($feature, $normalizedMatchedFeatures);
                                            @endphp
                                            <span class="inline-block text-xs px-2.5 py-1 rounded-full {{ $isMatched ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-gray-100 text-gray-600' }}">
                                                @if ($isMatched)
                                                    <i class="fas fa-check text-[10px] mr-1 text-green-600"></i>
                                                @endif
                                                {{ $feature }}
                                            </span>
                                        @endforeach
                                        
                                        @if ($hasMoreFeatures)
                                            <button onclick="toggleFeatures({{ $branch->id }})" 
                                                type="button"
                                                class="inline-block text-xs px-2.5 py-1 rounded-full bg-gray-100 text-[#7f5539] hover:bg-gray-200 transition-colors cursor-pointer features-toggle-btn"
                                                data-branch="{{ $branch->id }}">
                                                +{{ count($hiddenFeatures) }} more
                                            </button>
                                            <div class="hidden-features hidden w-full mt-1 flex flex-wrap gap-1.5" id="hidden-features-recommended-{{ $branch->id }}">
                                                @foreach ($hiddenFeatures as $feature)
                                                    @php
                                                        $isMatched = in_array($feature, $normalizedMatchedFeatures);
                                                    @endphp
                                                    <span class="inline-block text-xs px-2.5 py-1 rounded-full {{ $isMatched ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-gray-100 text-gray-600' }}">
                                                        @if ($isMatched)
                                                            <i class="fas fa-check text-[10px] mr-1 text-green-600"></i>
                                                        @endif
                                                        {{ $feature }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Stats -->
                            <div class="flex items-center justify-between text-xs text-gray-500 mb-4 mt-auto">
                                <div class="flex items-center gap-4">
                                    <span><i class="fas fa-couch mr-1 text-[#7f5539]"></i> {{ $branch->active_services_count ?? 0 }} services</span>
                                    @if(($branch->total_bookings ?? 0) > 0)
                                        <span><i class="fas fa-calendar-check mr-1 text-[#7f5539]"></i> {{ $branch->total_bookings }} bookings</span>
                                    @endif
                                    @if(($branch->recent_bookings ?? 0) > 0)
                                        <span class="text-green-600">
                                            <i class="fas fa-arrow-up text-[10px] mr-1"></i>
                                            +{{ $branch->recent_bookings }} this month
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-3">
                                <a href="{{ route('sub_three.home.branch.details', $branch->uuid) }}" 
                                    class="flex-1 bg-[#7f5539] hover:bg-[#6b4f3c] text-white py-3 px-4 rounded-xl text-sm font-medium text-center transition-colors min-h-[48px] flex items-center justify-center gap-2">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                @if($firstService && $firstCategory)
                                    <a href="{{ route('sub_three.home.booking.form', [
                                        'branch_uuid' => $branch->uuid,
                                        'service_category_uuid' => $firstCategory->uuid,
                                        'service_name_uuid' => $firstService->uuid,
                                    ]) }}" 
                                        class="flex-1 border-2 border-[#7f5539] text-[#7f5539] hover:bg-[#f5f0eb] py-3 px-4 rounded-xl text-sm font-medium text-center transition-colors min-h-[48px] flex items-center justify-center gap-2">
                                        <i class="fas fa-calendar-plus"></i> Book Now
                                    </a>
                                @else
                                    <a href="{{ route('sub_three.home.branch.details', $branch->uuid) }}" 
                                        class="flex-1 border-2 border-[#7f5539] text-[#7f5539] hover:bg-[#f5f0eb] py-3 px-4 rounded-xl text-sm font-medium text-center transition-colors min-h-[48px] flex items-center justify-center gap-2">
                                        <i class="fas fa-calendar-plus"></i> Book
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @elseif($hasUserData && !$recommendedBranch)
        <section id="recommended-branch" class="py-8 bg-white scroll-section">
            <div class="container mx-auto px-4">
                <div class="text-center py-12 max-w-lg mx-auto">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-search text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-[#4a3429] mb-3">No Perfect Match Found</h3>
                    <p class="text-gray-500 text-sm mb-6">
                        We couldn't find a branch that matches all your preferences. 
                        Try updating your preferences to get better recommendations.
                    </p>
                    <div class="flex flex-wrap gap-3 justify-center">
                        <a href="{{ route('sub_three.home.preferences.form') }}" 
                            class="inline-block bg-[#7f5539] hover:bg-[#6b4f3c] text-white py-2.5 px-6 rounded-xl font-medium transition-colors">
                            <i class="fas fa-pen mr-2"></i> Update Preferences
                        </a>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- ============================================================ -->
    <!-- SECTION: TOP BRANCHES                                         -->
    <!-- ============================================================ -->
    @if (!empty($topBranches['branches']))
        <section id="top-branches" class="py-12 bg-[#f8f5f2] scroll-section">
            <div class="container mx-auto px-4">
                <div class="text-center mb-10">
                    <div class="inline-block bg-orange-50 text-orange-600 text-xs font-semibold px-4 py-1.5 rounded-full mb-3">
                        <i class="fas fa-fire mr-1.5"></i> Most Popular
                    </div>
                    <h2 class="text-2xl md:text-3xl font-bold text-[#4a3429]">
                        🏆 Top Rated Branches
                    </h2>
                    <p class="text-gray-500 text-sm mt-2 max-w-2xl mx-auto">
                        Based on booking history, customer ratings, and popularity
                    </p>
                    <div class="w-16 h-1 bg-orange-500 mx-auto mt-3 rounded-full"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($topBranches['branches'] as $rec)
                        @php
                            $branch = $rec['branch'];
                            $rank = $rec['rank'];
                            $reason = $rec['reason'];
                            $avgRating = $rec['stats']['avg_rating'] ?? 0;
                            $reviewCount = $rec['stats']['review_count'] ?? 0;
                            $serviceCount = $rec['stats']['service_count'] ?? 0;
                            $totalBookings = $rec['stats']['total_bookings'] ?? 0;
                            $recentBookings = $rec['stats']['recent_bookings'] ?? 0;
                            $distance = $rec['distance'] ?? null;
                            
                            $firstService = $branch->serviceNames->first();
                            $firstCategory = $firstService ? $firstService->serviceCategory : null;
                            
                            // Normalize and deduplicate branch features for display
                            $allBranchFeatures = [];
                            if ($branch->features) {
                                $rawFeatures = array_map('trim', explode(',', $branch->features));
                                $normalizationMap = [
                                    'wi-fi' => 'Wi-Fi',
                                    'wifi' => 'Wi-Fi',
                                    'wi fi' => 'Wi-Fi',
                                    'free parking' => 'Parking',
                                    'parking' => 'Parking',
                                    'free coffee' => 'Coffee',
                                    'coffee' => 'Coffee',
                                ];
                                foreach ($rawFeatures as $feature) {
                                    $lower = strtolower(trim($feature));
                                    $normalized = $normalizationMap[$lower] ?? $feature;
                                    if (!in_array($normalized, $allBranchFeatures)) {
                                        $allBranchFeatures[] = $normalized;
                                    }
                                }
                            }
                            
                            $rankColors = [
                                1 => 'bg-yellow-500 text-white',
                                2 => 'bg-gray-400 text-white',
                                3 => 'bg-amber-700 text-white',
                            ];
                            $rankColor = $rankColors[$rank] ?? 'bg-[#7f5539] text-white';
                            
                            $hasGoogleMap = !empty($branch->google_map_url);
                            $locationText = $branch->location ?? 'View on Map';
                            $hasValidLocation = isset($customerLocation) && $customerLocation['source'] != 'default' && $customerLocation['source'] != 'unknown';
                        @endphp

                        <div class="group bg-white rounded-2xl overflow-hidden shadow-md hover:shadow-2xl transition-all duration-300 border border-gray-100 hover:border-orange-300 flex flex-col h-full">
                            <div class="h-48 relative overflow-hidden flex-shrink-0">
                                @if ($branch->branch_profile)
                                    <img src="{{ asset('storage/app/public/' . ltrim($branch->branch_profile, '/')) }}"
                                        alt="{{ $branch->branch_name }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                        loading="lazy">
                                @else
                                    <div class="w-full h-full bg-gradient-to-br from-[#7f5539] to-[#9c6644] flex items-center justify-center">
                                        <i class="fas fa-store text-white text-5xl"></i>
                                    </div>
                                @endif
                                
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                                
                                <div class="absolute top-3 right-3">
                                    <div class="{{ $rankColor }} text-xs font-bold px-3 py-1.5 rounded-full shadow-lg flex items-center gap-1.5">
                                        <i class="fas fa-trophy text-[10px]"></i>
                                        #{{ $rank }}
                                    </div>
                                </div>
                                
                                <div class="absolute bottom-3 left-3">
                                    <span class="px-3 py-1 text-xs rounded-full {{ $branch->branch_status ? 'bg-green-500/90 backdrop-blur-sm text-white' : 'bg-red-500/90 backdrop-blur-sm text-white' }}">
                                        {{ $branch->branch_status ? 'Open Now' : 'Closed' }}
                                    </span>
                                </div>
                            </div>

                            <div class="p-5 flex flex-col flex-grow">
                                <div class="flex items-start justify-between mb-2">
                                    <h3 class="font-bold text-lg text-[#4a3429] leading-tight group-hover:text-[#7f5539] transition-colors">
                                        {{ $branch->branch_name }}
                                    </h3>
                                    <div class="flex items-center gap-1 bg-gray-50 px-2 py-1 rounded-full">
                                        <i class="fas fa-star text-yellow-400 text-xs"></i>
                                        <span class="text-sm font-semibold text-gray-700">{{ number_format($avgRating, 1) }}</span>
                                        @if($reviewCount > 0)
                                            <span class="text-xs text-gray-400">({{ $reviewCount }})</span>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="flex items-center text-gray-500 text-sm mb-3">
                                    <i class="fas fa-map-marker-alt text-red-400 mr-1.5 text-xs"></i>
                                    @if ($hasGoogleMap)
                                        <a href="{{ $branch->google_map_url }}" 
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           class="hover:text-[#7f5539] transition-colors truncate">
                                            {{ $locationText }}
                                        </a>
                                    @else
                                        <span class="truncate">{{ $locationText }}</span>
                                    @endif
                                </div>
                                
                                @if($hasValidLocation && $distance !== null)
                                    <div class="mb-3 text-xs text-gray-400 flex items-center gap-1">
                                        <i class="fas fa-location-dot text-green-500"></i>
                                        <span>{{ $distance < 1 ? '< 1 km' : round($distance, 1) . ' km' }} from 
                                        {{ $customerLocation['place_name'] ?? 'your location' }}</span>
                                    </div>
                                @endif

                                <div class="flex items-center gap-4 mb-3 text-xs">
                                    <span class="text-gray-500">
                                        <i class="fas fa-calendar-check text-orange-500 mr-1"></i>
                                        {{ $totalBookings }} bookings
                                    </span>
                                    @if($recentBookings > 0)
                                        <span class="text-green-600">
                                            <i class="fas fa-arrow-up text-[10px] mr-1"></i>
                                            +{{ $recentBookings }} this month
                                        </span>
                                    @endif
                                </div>

                                @if (!empty($allBranchFeatures))
                                    <div class="mb-3">
                                        <div class="flex flex-wrap gap-1.5 features-container" id="features-top-{{ $branch->id }}">
                                            @php
                                                $displayFeatures = array_slice($allBranchFeatures, 0, 5);
                                                $hiddenFeatures = array_slice($allBranchFeatures, 5);
                                                $hasMoreFeatures = count($hiddenFeatures) > 0;
                                            @endphp
                                            
                                            @foreach ($displayFeatures as $feature)
                                                <span class="inline-block text-xs px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
                                                    {{ $feature }}
                                                </span>
                                            @endforeach
                                            
                                            @if ($hasMoreFeatures)
                                                <button onclick="toggleTopFeatures({{ $branch->id }})" 
                                                    type="button"
                                                    class="inline-block text-xs px-2.5 py-1 rounded-full bg-gray-100 text-[#7f5539] hover:bg-gray-200 transition-colors cursor-pointer features-toggle-btn"
                                                    data-branch="{{ $branch->id }}">
                                                    +{{ count($hiddenFeatures) }} more
                                                </button>
                                                <div class="hidden-features hidden w-full mt-1 flex flex-wrap gap-1.5" id="hidden-features-top-{{ $branch->id }}">
                                                    @foreach ($hiddenFeatures as $feature)
                                                        <span class="inline-block text-xs px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
                                                            {{ $feature }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <div class="mb-4 p-2.5 bg-orange-50 rounded-xl mt-auto">
                                    <p class="text-xs text-gray-600 flex items-start gap-2">
                                        <i class="fas fa-info-circle text-orange-500 mt-0.5"></i>
                                        <span>{{ $reason }}</span>
                                    </p>
                                </div>

                                <div class="flex gap-2">
                                    <a href="{{ route('sub_three.home.branch.details', $branch->uuid) }}" 
                                        class="flex-1 bg-[#7f5539] hover:bg-[#6b4f3c] text-white py-2.5 px-4 rounded-xl text-sm font-medium text-center transition-colors min-h-[44px] flex items-center justify-center gap-2">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                    @if($firstService && $firstCategory)
                                        <a href="{{ route('sub_three.home.booking.form', [
                                            'branch_uuid' => $branch->uuid,
                                            'service_category_uuid' => $firstCategory->uuid,
                                            'service_name_uuid' => $firstService->uuid,
                                        ]) }}" 
                                            class="flex-1 border border-[#7f5539] text-[#7f5539] hover:bg-[#f5f0eb] py-2.5 px-4 rounded-xl text-sm font-medium text-center transition-colors min-h-[44px] flex items-center justify-center gap-2">
                                            <i class="fas fa-calendar-plus"></i> Book Now
                                        </a>
                                    @else
                                        <a href="{{ route('sub_three.home.branch.details', $branch->uuid) }}" 
                                            class="flex-1 border border-[#7f5539] text-[#7f5539] hover:bg-[#f5f0eb] py-2.5 px-4 rounded-xl text-sm font-medium text-center transition-colors min-h-[44px] flex items-center justify-center gap-2">
                                            <i class="fas fa-calendar-plus"></i> Book
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-12 bg-[#f8f5f2] scroll-section">
        <div class="container mx-auto px-4">
            <div class="text-center mb-10">
                <div class="inline-block bg-blue-50 text-blue-600 text-xs font-semibold px-4 py-1.5 rounded-full mb-3">
                    <i class="fas fa-info-circle mr-1.5"></i> How It Works
                </div>
                <h2 class="text-2xl md:text-3xl font-bold text-[#4a3429]">Your Path to the Perfect Workspace</h2>
                <p class="text-gray-500 text-sm mt-2 max-w-md mx-auto">Three simple steps to find and book your ideal co-working space.</p>
                <div class="w-16 h-1 bg-blue-500 mx-auto mt-3 rounded-full"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-[#7f5539] to-[#9c6644] rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg shadow-[#7f5539]/20">
                        <span class="text-white font-bold text-xl">1</span>
                    </div>
                    <h3 class="text-lg font-semibold text-[#4a3429] mb-2">Share Your Location</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Allow us to find branches near your current location for personalized recommendations.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-[#9c6644] to-[#b08968] rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg shadow-[#9c6644]/20">
                        <span class="text-white font-bold text-xl">2</span>
                    </div>
                    <h3 class="text-lg font-semibold text-[#4a3429] mb-2">Set Your Preferences</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Tell us your amenities, space type, and schedule to find the perfect match.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-[#b08968] to-[#d4c4b2] rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg shadow-[#b08968]/20">
                        <span class="text-white font-bold text-xl">3</span>
                    </div>
                    <h3 class="text-lg font-semibold text-[#4a3429] mb-2">Book &amp; Work</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Choose your preferred branch and book a service that fits your needs.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-12 bg-[#4a3429] text-white">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-10">
                    <div class="inline-block bg-white/10 text-white text-xs font-semibold px-4 py-1.5 rounded-full mb-3">
                        <i class="fas fa-envelope mr-1.5"></i> Get In Touch
                    </div>
                    <h2 class="text-2xl md:text-3xl font-bold text-white">We're Here to Help</h2>
                    <p class="text-white/70 text-sm mt-2">Have questions or need assistance? Reach out to our support team.</p>
                    <div class="w-16 h-1 bg-[#b08968] mx-auto mt-3 rounded-full"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-6">
                        <h3 class="text-lg font-semibold">Contact Information</h3>
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-phone text-[#b08968]"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium">Phone Numbers</p>
                                    <p class="text-sm text-white/70">09084557940</p>
                                    <p class="text-sm text-white/70">09203365265</p>
                                    <p class="text-sm text-white/70">09659328807</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-envelope text-[#b08968]"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium">Email</p>
                                    <p class="text-sm text-white/70">linkudhub@gmail.com</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-clock text-[#b08968]"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium">Support Hours</p>
                                    <p class="text-sm text-white/70">Mon - Fri: 8:00 AM - 8:00 PM</p>
                                    <p class="text-sm text-white/70">Sat - Sun: 9:00 AM - 6:00 PM</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Send a Message</h3>
                        <form action="{{ route('guest.sendMessage') }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <input type="text" name="name" placeholder="Your Name" required 
                                    class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-[#b08968] focus:border-transparent text-sm min-h-[44px]">
                            </div>
                            <div>
                                <input type="email" name="email" placeholder="Your Email" required 
                                    class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-[#b08968] focus:border-transparent text-sm min-h-[44px]">
                            </div>
                            <div>
                                <textarea name="message" placeholder="Your Message" rows="4" required 
                                    class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-[#b08968] focus:border-transparent text-sm resize-none"></textarea>
                            </div>
                            <button type="submit" 
                                class="w-full bg-[#7f5539] hover:bg-[#6b4f3c] text-white px-6 py-3 rounded-xl font-semibold transition-all shadow-lg shadow-[#7f5539]/20 min-h-[44px]">
                                <i class="fas fa-paper-plane mr-2"></i> Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-[#3a2a20] text-white py-6">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center gap-2 mb-4 md:mb-0">
                    <i class="fas fa-cube text-[#b08968] text-xl"></i>
                    <span class="text-xl font-bold">LinkudHub</span>
                </div>
                <div class="text-white/60 text-sm">
                    <p>&copy; {{ date('Y') }} LinkudHub. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
@endsection

@push('styles')
<style>
    .thin-scroll {
        scrollbar-width: thin !important;
        scrollbar-color: #d4c4b2 transparent !important;
    }

    .thin-scroll::-webkit-scrollbar {
        height: 3px !important;
        width: 3px !important;
    }

    .thin-scroll::-webkit-scrollbar-track {
        background: transparent !important;
        border-radius: 3px !important;
    }

    .thin-scroll::-webkit-scrollbar-thumb {
        background-color: #d4c4b2 !important;
        border-radius: 3px !important;
    }

    .thin-scroll::-webkit-scrollbar-thumb:hover {
        background-color: #7f5539 !important;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.6; }
    }
    
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    #locationDropdown:not(.hidden) {
        animation: slideDown 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    html {
        scroll-behavior: smooth;
    }

    .hidden-features {
        display: none;
    }
    
    .hidden-features.show {
        display: flex;
    }

    .nav-link.active {
        background-color: #7f5539 !important;
        color: white !important;
        box-shadow: 0 2px 8px rgba(127, 85, 57, 0.3);
    }
    
    .nav-link.active i {
        color: white !important;
    }
    
    .nav-link {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .nav-link:hover {
        transform: translateY(-1px);
    }

    #priorityBreakdownContent {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    #priorityBreakdownContent.hidden {
        display: none;
    }

    .priority-bar {
        transition: width 1.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    #accordionIcon {
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .group/card:hover {
        transform: translateY(-1px);
        border-color: #d4c4b2;
        transition: all 0.2s ease;
    }
</style>
@endpush

@push('scripts')
<script>
    // ================================================================
    // TOGGLE FUNCTIONS
    // ================================================================
    
    function togglePriorityBreakdown() {
        const content = document.getElementById('priorityBreakdownContent');
        const icon = document.getElementById('accordionIcon');
        
        if (content) {
            content.classList.toggle('hidden');
            if (icon) {
                icon.classList.toggle('rotate-180');
            }
            
            if (!content.classList.contains('hidden')) {
                setTimeout(function() {
                    document.querySelectorAll('.priority-bar').forEach(function(bar) {
                        const width = bar.getAttribute('data-width');
                        if (width) {
                            bar.style.width = width;
                        }
                    });
                }, 100);
            }
        }
    }

    function toggleFeatures(branchId) {
        const hiddenFeatures = document.getElementById('hidden-features-recommended-' + branchId);
        const toggleBtn = document.querySelector('#features-recommended-' + branchId + ' .features-toggle-btn');
        
        if (hiddenFeatures) {
            hiddenFeatures.classList.toggle('hidden');
            hiddenFeatures.classList.toggle('show');
            
            if (toggleBtn) {
                if (hiddenFeatures.classList.contains('show')) {
                    toggleBtn.textContent = 'Show less';
                } else {
                    const totalHidden = hiddenFeatures.querySelectorAll('span').length;
                    toggleBtn.textContent = '+' + totalHidden + ' more';
                }
            }
        }
    }

    function toggleTopFeatures(branchId) {
        const hiddenFeatures = document.getElementById('hidden-features-top-' + branchId);
        const toggleBtn = document.querySelector('#features-top-' + branchId + ' .features-toggle-btn');
        
        if (hiddenFeatures) {
            hiddenFeatures.classList.toggle('hidden');
            hiddenFeatures.classList.toggle('show');
            
            if (toggleBtn) {
                if (hiddenFeatures.classList.contains('show')) {
                    toggleBtn.textContent = 'Show less';
                } else {
                    const totalHidden = hiddenFeatures.querySelectorAll('span').length;
                    toggleBtn.textContent = '+' + totalHidden + ' more';
                }
            }
        }
    }

    // ================================================================
    // LOCATION HANDLER
    // ================================================================
    
    function getCustomerLocation() {
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser.');
            return;
        }

        const btnText = document.getElementById('locationBtnText');
        const statusSpan = document.getElementById('locationStatus');
        
        if (btnText) {
            btnText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Getting location...';
        }
        
        if (statusSpan) {
            statusSpan.classList.remove('hidden');
            statusSpan.innerHTML = '<span class="text-blue-500">📍 Requesting location permission...</span>';
        }

        navigator.geolocation.getCurrentPosition(
            async function(position) {
                const { latitude, longitude } = position.coords;
                const accuracy = position.coords.accuracy;
                
                if (statusSpan) {
                    statusSpan.innerHTML = `<span class="text-blue-500">📍 Location acquired! (Accuracy: ${Math.round(accuracy)}m) - Getting address...</span>`;
                }
                
                await getAddressFromCoordinates(latitude, longitude);
            },
            function(error) {
                let errorMessage = 'Unable to get your location. ';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage += 'Please enable location permissions in your browser settings.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage += 'Location information is unavailable.';
                        break;
                    case error.TIMEOUT:
                        errorMessage += 'The request to get your location timed out.';
                        break;
                    default:
                        errorMessage += 'Please try again.';
                }
                
                if (statusSpan) {
                    statusSpan.innerHTML = `<span class="text-red-500">❌ ${errorMessage}</span>`;
                }
                
                if (btnText) {
                    btnText.innerHTML = '<i class="fas fa-location-dot mr-2"></i> Share My Exact Location';
                }
            },
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 60000
            }
        );
    }

    async function getAddressFromCoordinates(latitude, longitude) {
        try {
            const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&addressdetails=1&zoom=18`;
            
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'User-Agent': 'LinkudHub/1.0'
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to fetch address');
            }
            
            const data = await response.json();
            const address = data.address || {};
            
            const locationData = {
                latitude: latitude,
                longitude: longitude,
                houseNumber: address.house_number || address.building || '',
                road: address.road || address.street || address.pedestrian || '',
                suburb: address.suburb || address.district || address.neighbourhood || address.quarter || '',
                city: address.city || address.town || address.village || address.municipality || '',
                state: address.state || address.region || address.province || '',
                country: address.country || '',
                postcode: address.postcode || '',
                fullAddress: data.display_name || '',
                placeName: ''
            };
            
            const parts = [];
            if (locationData.houseNumber && locationData.road) {
                parts.push(`${locationData.houseNumber} ${locationData.road}`);
            } else if (locationData.road) {
                parts.push(locationData.road);
            }
            if (locationData.city) {
                parts.push(locationData.city);
            } else if (locationData.suburb) {
                parts.push(locationData.suburb);
            }
            if (locationData.state) {
                parts.push(locationData.state);
            }
            
            locationData.placeName = parts.length > 0 ? parts.join(', ') : locationData.fullAddress || 'Your Location';
            
            updateLocationUI(locationData);
            await sendLocationToServer(locationData);
            
        } catch (error) {
            console.error('Error getting address:', error);
            await getAddressFromBigDataCloud(latitude, longitude);
        }
    }

    async function getAddressFromBigDataCloud(latitude, longitude) {
        try {
            const url = `https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${latitude}&longitude=${longitude}&localityLanguage=en`;
            
            const response = await fetch(url);
            const data = await response.json();
            
            const locationData = {
                latitude: latitude,
                longitude: longitude,
                placeName: (data.city || data.locality || '') + ', ' + (data.principalSubdivision || ''),
                fullAddress: (data.city || data.locality || '') + ', ' + (data.principalSubdivision || '') + ', ' + (data.countryName || ''),
                city: data.city || data.locality || '',
                state: data.principalSubdivision || '',
                country: data.countryName || '',
                postcode: data.postcode || '',
                houseNumber: '',
                road: '',
                suburb: data.locality || ''
            };
            
            updateLocationUI(locationData);
            await sendLocationToServer(locationData);
            
        } catch (error) {
            console.error('BigDataCloud fallback failed:', error);
            await getLocationFromIP();
        }
    }

    async function getLocationFromIP() {
        try {
            const response = await fetch('https://ipapi.co/json/');
            const data = await response.json();
            
            const locationData = {
                latitude: data.latitude || null,
                longitude: data.longitude || null,
                placeName: (data.city || '') + ', ' + (data.region || ''),
                fullAddress: (data.city || '') + ', ' + (data.region || '') + ', ' + (data.country_name || ''),
                city: data.city || '',
                state: data.region || '',
                country: data.country_name || '',
                postcode: data.postal || '',
                houseNumber: '',
                road: '',
                suburb: ''
            };
            
            updateLocationUI(locationData);
            await sendLocationToServer(locationData);
            
        } catch (error) {
            console.error('IP location fallback failed:', error);
            const locationData = {
                latitude: 7.083333,
                longitude: 125.616667,
                placeName: 'Davao City, Davao Region',
                fullAddress: 'Davao City, Davao Region, Philippines',
                city: 'Davao City',
                state: 'Davao Region',
                country: 'Philippines',
                postcode: '8000',
                houseNumber: '',
                road: '',
                suburb: ''
            };
            
            updateLocationUI(locationData);
            await sendLocationToServer(locationData);
        }
    }

    function updateLocationUI(locationData) {
        const locationDisplay = document.getElementById('locationDisplay');
        if (locationDisplay) {
            locationDisplay.textContent = locationData.placeName || locationData.fullAddress || 'Your Location';
        }
        
        const locationDetails = document.getElementById('locationDetails');
        if (locationDetails) {
            let html = `<span class="text-gray-700">${locationData.fullAddress || locationData.placeName || 'Your Location'}</span>`;
            html += `<span class="text-gray-400 ml-2">(GPS)</span>`;
            
            if (locationData.city || locationData.state) {
                html += `<br><span class="text-xs text-gray-400">📍 ${locationData.city || ''} ${locationData.state ? ', ' + locationData.state : ''} ${locationData.postcode ? '- ' + locationData.postcode : ''}</span>`;
            }
            
            if (locationData.houseNumber && locationData.road) {
                html += `<br><span class="text-xs text-gray-400">🏠 ${locationData.houseNumber} ${locationData.road}</span>`;
            }
            
            locationDetails.innerHTML = html;
        }
        
        const statusSpan = document.getElementById('locationStatus');
        if (statusSpan) {
            statusSpan.innerHTML = `<span class="text-green-500">✅ Location found! ${locationData.placeName || locationData.fullAddress || ''}</span>`;
        }
        
        const btnText = document.getElementById('locationBtnText');
        if (btnText) {
            btnText.innerHTML = '<i class="fas fa-check mr-2"></i> Location Saved';
        }
        
        const fields = ['latitude', 'longitude', 'place_name', 'full_address', 'city', 'state', 'country', 'postcode', 'house_number', 'road', 'suburb'];
        fields.forEach(field => {
            const el = document.getElementById(field);
            if (el) {
                const key = field === 'place_name' ? 'placeName' : 
                           field === 'full_address' ? 'fullAddress' : field;
                el.value = locationData[key] || '';
            }
        });
        
        sessionStorage.setItem('userLocation', JSON.stringify(locationData));
    }

    async function sendLocationToServer(locationData) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('latitude', locationData.latitude);
            formData.append('longitude', locationData.longitude);
            formData.append('place_name', locationData.placeName || locationData.fullAddress || '');
            formData.append('full_address', locationData.fullAddress || locationData.placeName || '');
            formData.append('city', locationData.city || '');
            formData.append('state', locationData.state || '');
            formData.append('country', locationData.country || '');
            formData.append('postcode', locationData.postcode || '');
            formData.append('house_number', locationData.houseNumber || '');
            formData.append('road', locationData.road || '');
            formData.append('suburb', locationData.suburb || '');

            const response = await fetch('{{ route("sub_three.home.location.update") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to update location');
            }

            const data = await response.json();
            console.log('Location saved:', data);
            
            showNotification('Location updated successfully!', 'success');
            
        } catch (error) {
            console.error('Error sending location to server:', error);
            showNotification('Failed to save location to server', 'error');
        }
    }

    function showNotification(message, type = 'success') {
        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            info: 'bg-blue-500',
            warning: 'bg-yellow-500'
        };
        
        const notification = document.createElement('div');
        notification.className = `fixed top-20 right-4 z-[100] ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full max-w-sm`;
        notification.innerHTML = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);
        
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }

    function toggleLocationDropdown() {
        const dropdown = document.getElementById('locationDropdown');
        if (dropdown) {
            dropdown.classList.toggle('hidden');
        }
    }

    async function clearCustomerLocation() {
        if (!confirm('Clear your current location? You can always share it again.')) {
            return;
        }

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            const formData = new FormData();
            formData.append('_token', csrfToken);

            const response = await fetch('{{ route("sub_three.home.location.clear") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            
            if (data.success) {
                alert('Location cleared!');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                throw new Error(data.message || 'Failed to clear location');
            }
        } catch (error) {
            console.error('Error clearing location:', error);
            alert(error.message || 'Failed to clear location. Please try again.');
        }
    }

    function clearSearch() {
        const searchInput = document.getElementById('searchInput');
        const searchForm = document.getElementById('searchForm');
        if (searchInput) searchInput.value = '';
        if (searchForm) searchForm.submit();
    }

    // DOM Ready
    document.addEventListener('DOMContentLoaded', function() {
        const storedLocation = sessionStorage.getItem('userLocation');
        if (storedLocation) {
            try {
                const locationData = JSON.parse(storedLocation);
                updateLocationUI(locationData);
            } catch (e) {
                // Ignore
            }
        }

        setTimeout(function() {
            const content = document.getElementById('priorityBreakdownContent');
            if (content && !content.classList.contains('hidden')) {
                document.querySelectorAll('.priority-bar').forEach(function(bar) {
                    const width = bar.getAttribute('data-width');
                    if (width) {
                        bar.style.width = width;
                    }
                });
            }
        }, 500);
    });

    // Attach to window
    window.getCustomerLocation = getCustomerLocation;
    window.toggleLocationDropdown = toggleLocationDropdown;
    window.clearCustomerLocation = clearCustomerLocation;
    window.togglePriorityBreakdown = togglePriorityBreakdown;
    window.toggleFeatures = toggleFeatures;
    window.toggleTopFeatures = toggleTopFeatures;
    window.clearSearch = clearSearch;
</script>
@endpush