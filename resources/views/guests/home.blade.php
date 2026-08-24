@extends('layouts.app')

@section('title', 'Find Your Workspace - LinkudHub')

@section('content')
    <!-- Header with Location (Same as Customer) -->
    <header class="bg-white sticky top-[63px] z-[50] transition-all duration-300 shadow-sm" id="main-header">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center py-3 space-y-3 md:space-y-0">
                <!-- Location Bar -->
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <div class="flex items-center gap-2 bg-gray-50 rounded-full px-4 py-2 border border-gray-200 hover:border-[#7f5539] transition-colors cursor-pointer min-h-[44px] flex-1 md:flex-initial" onclick="toggleLocationDropdown()">
                        <i class="fas fa-location-dot text-[#7f5539] text-sm"></i>
                        <span class="text-sm text-gray-700 truncate max-w-[150px] md:max-w-[200px]" id="locationDisplay">
                            @if(isset($guestLocation) && $guestLocation['source'] != 'default' && $guestLocation['source'] != 'unknown')
                                {{ $guestLocation['latitude'] ? number_format($guestLocation['latitude'], 4) . ', ' . number_format($guestLocation['longitude'], 4) : 'Your Location' }}
                            @else
                                Select Location
                            @endif
                        </span>
                        <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                    </div>
                    
                    <!-- Location Status Badge -->
                    @if(isset($guestLocation) && $guestLocation['source'] != 'default' && $guestLocation['source'] != 'unknown')
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
                            @if(!empty($topBranches['branches']))
                                <a href="#top-branches" class="nav-link scroll-link active text-[#7f5539] font-medium transition-colors px-3 py-1.5 whitespace-nowrap bg-[#7f5539]/10 hover:bg-[#7f5539]/20 rounded-full flex items-center min-h-[44px] md:min-h-0" data-section="top-branches">
                                    <i class="fas fa-fire mr-1.5 text-orange-500"></i> Top Branches
                                </a>
                            @endif
                            <a href="#how-it-works" class="nav-link scroll-link text-gray-700 hover:text-[#7f5539] font-medium transition-colors px-3 py-1.5 whitespace-nowrap bg-gray-50 hover:bg-gray-100 rounded-full flex items-center min-h-[44px] md:min-h-0" data-section="how-it-works">
                                <i class="fas fa-info-circle mr-1.5 text-blue-500"></i> How It Works
                            </a>
                            <a href="{{ route('guest.feedbacks') }}" class="text-gray-700 hover:text-[#7f5539] font-medium transition-colors px-3 py-1.5 whitespace-nowrap bg-gray-50 hover:bg-gray-100 rounded-full flex items-center min-h-[44px] md:min-h-0">
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
            
            <!-- Current Location Status -->
            <div class="bg-gray-50 rounded-xl p-4 mb-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-[#7f5539]/10 rounded-full flex items-center justify-center">
                            <i class="fas fa-location-dot text-[#7f5539]"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800 text-sm">
                                @if(isset($guestLocation) && $guestLocation['source'] != 'default' && $guestLocation['source'] != 'unknown')
                                    Current Location
                                @else
                                    No Location Set
                                @endif
                            </p>
                            <p class="text-xs text-gray-500" id="locationDetails">
                                @if(isset($guestLocation) && $guestLocation['source'] != 'default' && $guestLocation['source'] != 'unknown')
                                    {{ number_format($guestLocation['latitude'], 4) }}, {{ number_format($guestLocation['longitude'], 4) }}
                                    <span class="text-gray-400 ml-2">
                                        ({{ ucfirst($guestLocation['source']) }})
                                    </span>
                                @else
                                    Please share your location for accurate recommendations
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        @if(isset($guestLocation) && $guestLocation['source'] != 'default' && $guestLocation['source'] != 'unknown')
                            <button onclick="clearGuestLocation()" 
                                class="text-sm text-red-600 hover:text-red-700 px-3 py-1.5 border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
                                Clear
                            </button>
                        @endif
                        <button onclick="getGuestLocation()" 
                            class="text-sm bg-[#7f5539] text-white px-4 py-1.5 rounded-lg hover:bg-[#6b4f3c] transition-colors">
                            <i class="fas fa-sync-alt mr-1.5"></i> Update
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Location Info -->
            <div class="text-xs text-gray-400 flex items-center gap-4">
                <span><i class="far fa-clock mr-1"></i> Updates every 30 min</span>
                <span><i class="fas fa-shield-alt mr-1"></i> Your privacy is protected</span>
                @if(isset($guestLocation['expires_at']) && isset($guestLocation) && $guestLocation['source'] != 'default' && $guestLocation['source'] != 'unknown')
                    <span class="text-yellow-600">
                        <i class="fas fa-hourglass-half mr-1"></i>
                        Expires: {{ \Carbon\Carbon::parse($guestLocation['expires_at'])->format('h:i A') }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-[#f5f0eb] to-[#e6ddd4] py-8 md:py-12">
        <div class="container mx-auto px-3">
            <div class="max-w-xl mx-auto text-center">
                <h1 class="text-xl md:text-2xl font-bold text-[#4a3429] mb-3 leading-tight">
                    Find Your Perfect Workspace
                </h1>
                <p class="text-gray-600 mb-4 max-w-md mx-auto text-xs">
                    Discover the most popular co-working spaces near you
                </p>

                <!-- Search Bar -->
                <div class="bg-white rounded-lg p-3 mb-4 shadow-sm border border-[#e6ddd4]">
                    <form action="{{ route('welcome') }}" method="GET" id="searchForm">
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

                <div class="flex flex-col items-center mt-2">
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        @if(!isset($guestLocation) || $guestLocation['source'] == 'default' || $guestLocation['source'] == 'unknown')
                            <button onclick="getGuestLocation()" 
                                class="inline-flex items-center px-4 py-2 bg-[#7f5539] hover:bg-[#6b4f3c] text-white rounded-lg transition-colors text-sm">
                                <i class="fas fa-location-dot mr-2"></i>
                                <span id="locationBtnText">Share My Location</span>
                            </button>
                        @else
                            <button onclick="getGuestLocation()" 
                                class="inline-flex items-center px-4 py-2 bg-[#7f5539] hover:bg-[#6b4f3c] text-white rounded-lg transition-colors text-sm">
                                <i class="fas fa-sync-alt mr-2"></i>
                                <span id="locationBtnText">Update Location</span>
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

                @if ($searchQuery)
                    <div class="mt-4 text-left">
                        <p class="text-xs text-gray-500">
                            Showing results for: <span class="font-medium text-[#4a3429]">"{{ $searchQuery }}"</span>
                            @php
                                $totalResults = 0;
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
    <!-- SECTION: TOP BRANCHES (Same as Customer - No User Data)       -->
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
                            $distanceLabel = $rec['distance_label'] ?? null;
                            
                            $firstService = $branch->serviceNames->first();
                            $firstCategory = $firstService ? $firstService->serviceCategory : null;
                            
                            $allBranchFeatures = [];
                            if ($branch->features) {
                                $allBranchFeatures = array_map('trim', explode(',', $branch->features));
                                $allBranchFeatures = array_filter($allBranchFeatures);
                            }
                            
                            $rankColors = [
                                1 => 'bg-yellow-500 text-white',
                                2 => 'bg-gray-400 text-white',
                                3 => 'bg-amber-700 text-white',
                            ];
                            $rankColor = $rankColors[$rank] ?? 'bg-[#7f5539] text-white';
                            
                            $hasGoogleMap = !empty($branch->google_map_url);
                            $locationText = $branch->location ?? 'View on Map';
                            $hasValidLocation = isset($guestLocation) && $guestLocation['source'] != 'default' && $guestLocation['source'] != 'unknown';
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
                                
                                @if($hasValidLocation && $distanceLabel)
                                    <div class="mb-3 text-xs text-gray-400 flex items-center gap-1">
                                        <i class="fas fa-location-dot text-green-500"></i>
                                        <span>{{ $distanceLabel }} from your location</span>
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
                                    <a href="{{ route('showLoginForm') }}" 
                                        class="flex-1 bg-[#7f5539] hover:bg-[#6b4f3c] text-white py-2.5 px-4 rounded-xl text-sm font-medium text-center transition-colors min-h-[44px] flex items-center justify-center gap-2">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                    <a href="{{ route('showLoginForm') }}" 
                                        class="flex-1 border border-[#7f5539] text-[#7f5539] hover:bg-[#f5f0eb] py-2.5 px-4 rounded-xl text-sm font-medium text-center transition-colors min-h-[44px] flex items-center justify-center gap-2">
                                        <i class="fas fa-calendar-plus"></i> Book Now
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-12 bg-white scroll-section">
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
                    <h3 class="text-lg font-semibold text-[#4a3429] mb-2">Explore Top Branches</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Browse our top-rated branches and find the perfect workspace for your needs.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-[#b08968] to-[#d4c4b2] rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg shadow-[#b08968]/20">
                        <span class="text-white font-bold text-xl">3</span>
                    </div>
                    <h3 class="text-lg font-semibold text-[#4a3429] mb-2">Sign Up &amp; Book</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Create an account to book your preferred branch and service instantly.</p>
                </div>
            </div>

            <!-- Call to Action -->
            <div class="text-center mt-10">
                <a href="{{ route('showLoginForm') }}" 
                    class="inline-flex items-center bg-[#7f5539] hover:bg-[#6b4f3c] text-white px-8 py-4 rounded-xl font-semibold transition-all shadow-lg shadow-[#7f5539]/20 min-h-[48px]">
                    <i class="fas fa-user-plus mr-2"></i> Get Started Now
                </a>
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
                            <!-- Honeypot -->
                            <div style="display:none !important; position:absolute; left:-9999px;" aria-hidden="true">
                                <input type="text" name="website" tabindex="-1" autocomplete="off" value="">
                            </div>
                            <!-- Timing check -->
                            <input type="hidden" name="form_loaded_at" value="{{ time() }}">
                            
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
</style>
@endpush

@push('scripts')
<script>
    /**
     * Toggle features expand/collapse
     */
    function toggleTopFeatures(branchId) {
        const hiddenContainer = document.getElementById('hidden-features-top-' + branchId);
        const toggleBtn = document.querySelector(`[data-branch="${branchId}"]`);
        
        if (hiddenContainer) {
            hiddenContainer.classList.toggle('show');
            if (hiddenContainer.classList.contains('show')) {
                if (toggleBtn) toggleBtn.textContent = 'Show less';
            } else {
                const hiddenCount = hiddenContainer.querySelectorAll('span').length;
                if (toggleBtn) toggleBtn.textContent = '+' + hiddenCount + ' more';
            }
        }
    }

    /**
     * Location functions
     */
    function toggleLocationDropdown() {
        const dropdown = document.getElementById('locationDropdown');
        dropdown.classList.toggle('hidden');
    }

    function getGuestLocation() {
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
            statusSpan.textContent = 'Requesting location permission...';
        }

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                if (statusSpan) {
                    statusSpan.textContent = 'Location found! Updating...';
                }
                sendGuestLocationToServer(lat, lng);
            },
            function(error) {
                let errorMessage = 'Unable to get your location. ';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage += 'Please enable location permissions in your browser.';
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
                alert(errorMessage);
                
                if (btnText) {
                    btnText.innerHTML = 'Share My Location';
                }
                if (statusSpan) {
                    statusSpan.classList.add('hidden');
                }
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000
            }
        );
    }

    async function sendGuestLocationToServer(latitude, longitude) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('latitude', latitude);
            formData.append('longitude', longitude);

            const response = await fetch('{{ route("guest.location.update") }}', {
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
            
            if (data.success) {
                alert('Location updated successfully!');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                throw new Error(data.message || 'Failed to update location');
            }
        } catch (error) {
            console.error('Error updating location:', error);
            alert(error.message || 'Failed to update location. Please try again.');
        }
    }

    async function clearGuestLocation() {
        if (!confirm('Clear your current location? You can always share it again.')) {
            return;
        }

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            const formData = new FormData();
            formData.append('_token', csrfToken);

            const response = await fetch('{{ route("guest.location.clear") }}', {
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
        // Smooth scroll for navigation links
        const navLinks = document.querySelectorAll('.nav-link');
        const sections = document.querySelectorAll('.scroll-section');
        
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;

                const target = document.querySelector(targetId);
                if (target) {
                    navLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    
                    const mainNavHeight = 63;
                    const headerElement = document.getElementById('main-header');
                    const headerHeight = headerElement ? headerElement.offsetHeight : 0;
                    const offset = mainNavHeight + headerHeight + 20;

                    const elementPosition = target.getBoundingClientRect().top + window.pageYOffset;
                    const offsetPosition = elementPosition - offset;

                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });

        function updateActiveNav() {
            const scrollPosition = window.pageYOffset + 150;
            let currentSection = '';
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionBottom = sectionTop + section.offsetHeight;
                
                if (scrollPosition >= sectionTop && scrollPosition < sectionBottom) {
                    currentSection = section.getAttribute('id');
                }
            });
            
            if (!currentSection && sections.length > 0) {
                currentSection = sections[0].getAttribute('id');
            }
            
            navLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href === '#' + currentSection) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        }

        let isScrolling = false;
        window.addEventListener('scroll', function() {
            if (!isScrolling) {
                window.requestAnimationFrame(function() {
                    updateActiveNav();
                    isScrolling = false;
                });
                isScrolling = true;
            }
        });

        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(updateActiveNav, 200);
        });

        setTimeout(updateActiveNav, 200);

        // Location prompt for guests
        @if(!isset($guestLocation) || $guestLocation['source'] == 'default' || $guestLocation['source'] == 'unknown')
            setTimeout(() => {
                if (confirm('Share your current location to find branches near you?')) {
                    getGuestLocation();
                }
            }, 2000);
        @endif
    });

    // Close location dropdown on outside click
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('locationDropdown');
        const locationBtn = document.querySelector('[onclick="toggleLocationDropdown()"]');
        
        if (dropdown && !dropdown.classList.contains('hidden')) {
            if (!dropdown.contains(event.target) && !locationBtn?.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        }
    });
</script>
@endpush