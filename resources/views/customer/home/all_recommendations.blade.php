@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="min-h-screen bg-[#f5f0eb] py-8">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex flex-col-reverse md:flex-row md:items-center justify-between mb-4 gap-4 md:gap-0">
                <div>
                    <h1 class="text-2xl font-bold text-[#4a3429]">{{ $title }}</h1>
                    <p class="text-gray-600 mt-1">
                        @if($type == 'you-might-like')
                            Services you might like based on overall popularity and ratings
                        @elseif($type == 'trending-now')
                            Currently popular and trending services
                        @elseif($type == 'recommended-for-you')
                            Personalized hybrid recommendations based on your preferences, history, and similar users
                        @elseif($type == 'your-ideal-matches')
                            Perfect matches to your preferences and booking history
                        @elseif($type == 'popular-similar-users')
                            Popular among users with similar tastes and preferences
                        @elseif($type == 'top-branches')
                            @if($hasUserData)
                                Personalized top branches based on your preferences
                            @else
                                Best rated branches with excellent services
                            @endif
                        @endif
                    </p>
                </div>
                <a href="{{ route('sub_three.home.showHome') }}" 
                   class="text-[#7f5539] hover:text-[#6b4f3c] text-sm font-medium flex items-center self-start md:self-auto">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Home
                </a>
            </div>
        </div>

        <!-- Items Grid -->
        @if($itemType == 'services')
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-y-8 gap-x-6">
                @foreach($items as $index => $item)
                    @php
                        // Handle recommendation data format
                        $service = $item['service'];
                        $rank = $item['rank'] ?? $index + 1;
                        $reason = $item['reason'] ?? null;
                        $stats = $item['stats'] ?? null;
                        $recType = $item['type'] ?? null;
                        
                        $feedbackData = app('App\Http\Controllers\Customer\HomeController')->getServiceFeedbacks($service->uuid);
                        
                        // Determine colors based on recommendation type
                        $numberColor = 'text-gray-300/30';
                        $gradientColor = 'from-[#9c6644] to-[#7f5539]';
                        $icon = 'fa-couch';
                        $buttonColor = 'bg-[#7f5539] hover:bg-[#6b4f3c]';
                        $iconColor = 'text-[#b08968]';
                        $badgeColor = 'bg-blue-500/90';
                        $badgeText = 'Popular';

                        if($type == 'you-might-like') {
                            $numberColor = 'text-gray-400/30';
                            $gradientColor = 'from-[#9c6644] to-[#7f5539]';
                            $icon = 'fa-lightbulb';
                            $iconColor = 'text-amber-500';
                            $badgeColor = 'bg-blue-500/90';
                            $badgeText = 'Popular';
                        } elseif($type == 'trending-now') {
                            $numberColor = 'text-amber-500/20';
                            $gradientColor = 'from-amber-500 to-yellow-500';
                            $icon = 'fa-fire';
                            $iconColor = 'text-amber-500';
                            $badgeColor = 'bg-amber-500/90';
                            $badgeText = 'Trending';
                            $buttonColor = 'bg-amber-500 hover:bg-amber-600';
                        } elseif($type == 'recommended-for-you') {
                            $numberColor = 'text-[#7f5539]/20';
                            $gradientColor = 'from-[#7f5539] to-[#9c6644]';
                            $icon = 'fa-star';
                            $iconColor = 'text-[#7f5539]';
                            $badgeColor = 'bg-[#7f5539]/90';
                            $badgeText = 'Recommended For You';
                        } elseif($type == 'your-ideal-matches') {
                            $numberColor = 'text-[#4a3429]/20';
                            $gradientColor = 'from-[#7f5539] to-[#9c6644]';
                            $icon = 'fa-heart';
                            $iconColor = 'text-gray-600';
                            $badgeColor = 'bg-[#7f5539]/90';
                            $badgeText = 'Perfect Match';
                            $buttonColor = 'bg-[#7f5539] hover:bg-[#6b4f3c]';
                        } elseif($type == 'popular-similar-users') {
                            $numberColor = 'text-[#7f5539]/20';
                            $gradientColor = 'from-[#7f5539] to-[#9c6644]';
                            $icon = 'fa-users';
                            $iconColor = 'text-[#7f5539]';
                            $badgeColor = 'bg-[#7f5539]/90';
                            $badgeText = 'Similar Users';
                            $buttonColor = 'bg-[#7f5539] hover:bg-[#6b4f3c]';
                        }
                    @endphp

                    <div class="relative group pt-2 h-full">
                        <!-- Big Number Label -->
                        <div class="absolute {{ $rank >= 10 ? '-left-2' : 'left-6' }} bottom-0 z-0 flex items-end justify-start pointer-events-none">
                            <div class="text-[140px] font-black leading-none tracking-tighter select-none transition-transform duration-300 group-hover:scale-105 origin-bottom-left {{ $numberColor }}">
                                {{ $rank }}
                            </div>
                        </div>

                        <div class="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-lg transition duration-300 relative z-10 ml-24 border border-[#e6ddd4] h-full flex flex-col">
                            <!-- Service Image -->
                            <div class="h-40 bg-gradient-to-r {{ $gradientColor }} relative flex-shrink-0">
                                @if($service->branch->branch_profile)
                                    <img src="{{ asset('storage/app/public/' . ltrim($service->branch->branch_profile, '/')) }}" 
                                         alt="{{ $service->branch->branch_name }}"
                                         class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <i class="fas {{ $icon }} text-white text-4xl"></i>
                                    </div>
                                @endif
                                
                                <!-- Recommendation Type Badge -->
                                @if($badgeText)
                                    <div class="absolute top-2 right-2">
                                        <span class="px-2 py-1 text-xs rounded-full {{ $badgeColor }} text-white">
                                            {{ $badgeText }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <!-- Service Info -->
                            <div class="p-4 flex-1 flex flex-col">
                                <!-- Service Name with Price aligned to the right -->
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-sm text-[#4a3429] truncate" title="{{ $service->service_name }}">{{ $service->service_name }}</h3>
                                    </div>
                                    <div class="ml-2 flex-shrink-0">
                                        <span class="text-sm font-bold text-[#7f5539] whitespace-nowrap">
                                            ₱{{ number_format($service->price, 0) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Category below service name -->
                                <div class="flex items-center text-xs text-gray-500 mb-2">
                                    <i class="fas fa-list mr-1 text-[#b08968] flex-shrink-0"></i>
                                    <span class="truncate">{{ $service->serviceCategory->service_category }}</span>
                                </div>

                                <!-- Branch -->
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-store text-gray-400 mr-1 text-xs"></i>
                                    <span class="text-xs text-gray-600 truncate">{{ $service->branch->branch_name }}</span>
                                </div>

                                <!-- Rating -->
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center">
                                        <div class="flex text-yellow-500 text-xs mr-1">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="fas fa-star {{ $i <= round($feedbackData['average_rating']) ? 'text-yellow-500' : 'text-gray-300' }}"></i>
                                            @endfor
                                        </div>
                                        <span class="text-xs text-gray-600">
                                            {{ number_format($feedbackData['average_rating'], 1) }}
                                        </span>
                                    </div>
                                    @if($feedbackData['total_reviews'] > 0)
                                        <span class="text-xs text-gray-500">
                                            {{ $feedbackData['total_reviews'] }} review{{ $feedbackData['total_reviews'] > 1 ? 's' : '' }}
                                        </span>
                                    @endif
                                </div>

                                <!-- Recommendation Reason -->
                                @if($reason)
                                    <div class="mb-3">
                                        <p class="text-xs text-gray-600 line-clamp-2">
                                            <i class="fas {{ $icon }} {{ $iconColor }} mr-1"></i>
                                            {{ $reason }}
                                        </p>
                                    </div>
                                @endif

                                <!-- Stats (if available) -->
                                @if($stats)
                                    <div class="mb-3 text-xs text-gray-500 space-y-1">
                                        @if(isset($stats['total_bookings']))
                                            <div class="flex justify-between">
                                                <span>Total Bookings:</span>
                                                <span class="font-medium">{{ $stats['total_bookings'] }}</span>
                                            </div>
                                        @endif
                                        @if(isset($stats['recent_bookings']))
                                            <div class="flex justify-between">
                                                <span>Recent Bookings:</span>
                                                <span class="font-medium">{{ $stats['recent_bookings'] }}</span>
                                            </div>
                                        @endif
                                        @if(isset($stats['review_count']))
                                            <div class="flex justify-between">
                                                <span>Reviews:</span>
                                                <span class="font-medium">{{ $stats['review_count'] }}</span>
                                            </div>
                                        @endif
                                        @if(isset($stats['similar_user_bookings']))
                                            <div class="flex justify-between">
                                                <span>Similar User Bookings:</span>
                                                <span class="font-medium">{{ $stats['similar_user_bookings'] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <!-- Action Button -->
                                <a href="{{ route('sub_three.home.booking.form', [
                                    'branch_uuid' => $service->branch->uuid,
                                    'service_category_uuid' => $service->serviceCategory->uuid,
                                    'service_name_uuid' => $service->uuid,
                                ]) }}"
                                   class="block w-full {{ $buttonColor }} text-white py-2 px-3 rounded text-xs font-medium text-center transition-colors mt-auto">
                                    <i class="fas fa-calendar-plus mr-1"></i> Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

        @elseif($itemType == 'branches')
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-y-8 gap-x-6">
                @foreach($items as $index => $item)
                    @php
                        // Handle recommendation data format
                        $branch = $item['branch'];
                        $rank = $item['rank'] ?? $index + 1;
                        $reason = $item['reason'] ?? null;
                        $stats = $item['stats'] ?? null;
                        $recType = $item['type'] ?? null;
                        
                        $feedbackData = app('App\Http\Controllers\Customer\HomeController')->getBranchFeedbacks($branch->uuid);
                        
                        // Determine colors
                        $numberColor = 'text-[#4a3429]/20';
                        $icon = 'fa-store';
                        $badgeColor = 'bg-blue-500/90';
                        $badgeText = 'Popular';
                        $iconColor = 'text-[#7f5539]';

                        if($type == 'top-branches' && $hasUserData) {
                            $badgeColor = 'bg-green-500/90';
                            $badgeText = 'Personalized';
                        }

                        // Get unique features
                        $featuresToShow = [];
                        if ($branch->features) {
                            $allFeatures = explode(',', $branch->features);
                            $allFeatures = array_map('trim', $allFeatures);
                            $allFeatures = array_filter($allFeatures);
                            $uniqueFeatures = array_unique($allFeatures);
                            $featuresToShow = array_slice($uniqueFeatures, 0, 2);
                        }
                    @endphp

                    <div class="relative group pt-2 h-full">
                        <!-- Big Number Label -->
                        <div class="absolute {{ $rank >= 10 ? '-left-2' : 'left-6' }} bottom-0 z-0 flex items-end justify-start pointer-events-none">
                            <div class="text-[140px] font-black leading-none tracking-tighter select-none transition-transform duration-300 group-hover:scale-105 origin-bottom-left {{ $numberColor }}">
                                {{ $rank }}
                            </div>
                        </div>

                        <div class="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-lg transition duration-300 relative z-10 ml-24 border border-[#e6ddd4] h-full flex flex-col">
                            <!-- Branch Image -->
                            <div class="h-40 bg-gradient-to-r from-[#7f5539] to-[#9c6644] relative flex-shrink-0">
                                @if($branch->branch_profile)
                                    <img src="{{ asset('storage/' . ltrim($branch->branch_profile, '/')) }}" 
                                         alt="{{ $branch->branch_name }}"
                                         class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <i class="fas {{ $icon }} text-white text-4xl"></i>
                                    </div>
                                @endif
                                
                                <!-- Branch Status -->
                                <div class="absolute top-2 right-2">
                                    <span class="px-2 py-1 text-xs rounded-full {{ $branch->branch_status ? 'bg-green-500/90 text-white' : 'bg-red-500/90 text-white' }}">
                                        {{ $branch->branch_status ? 'Open' : 'Closed' }}
                                    </span>
                                </div>
                                
                                <!-- Recommendation Type Badge -->
                                @if($badgeText)
                                    <div class="absolute top-2 left-2">
                                        <span class="px-2 py-1 text-xs rounded-full {{ $badgeColor }} text-white">
                                            {{ $badgeText }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <!-- Branch Info -->
                            <div class="p-4 flex-1 flex flex-col">
                                <div class="mb-2">
                                    <h3 class="font-bold text-sm text-[#4a3429] mb-1 truncate">{{ $branch->branch_name }}</h3>
                                    <div class="flex items-center text-xs text-gray-500">
                                        <i class="fas fa-map-marker-alt text-red-500 mr-1 flex-shrink-0"></i>
                                        <span class="truncate">{{ $branch->location }}</span>
                                    </div>
                                </div>

                                <!-- Features -->
                                @if(!empty($featuresToShow))
                                    <div class="mb-2 flex flex-wrap gap-1 h-6 overflow-hidden">
                                        @foreach($featuresToShow as $feature)
                                            <span class="inline-block bg-[#f5f0eb] text-[#7f5539] text-[10px] px-1.5 py-0.5 rounded">
                                                {{ $feature }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- Rating -->
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center">
                                        <div class="flex text-yellow-500 text-xs mr-1">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="fas fa-star {{ $i <= round($feedbackData['average_rating']) ? 'text-yellow-500' : 'text-gray-300' }}"></i>
                                            @endfor
                                        </div>
                                        <span class="text-xs text-gray-600">
                                            {{ number_format($feedbackData['average_rating'], 1) }}
                                        </span>
                                    </div>
                                    @if($feedbackData['total_reviews'] > 0)
                                        <span class="text-xs text-gray-500">
                                            {{ $feedbackData['total_reviews'] }} review{{ $feedbackData['total_reviews'] > 1 ? 's' : '' }}
                                        </span>
                                    @endif
                                </div>

                                <!-- Recommendation Reason -->
                                @if($reason)
                                    <div class="mb-2">
                                        <p class="text-xs text-gray-600 line-clamp-2">
                                            <i class="fas fa-info-circle {{ $iconColor }} mr-1"></i>
                                            {{ $reason }}
                                        </p>
                                    </div>
                                @endif

                                <!-- Services Count -->
                                <div class="mb-3">
                                    <p class="text-xs text-gray-600">
                                        <i class="fas fa-couch text-[#b08968] mr-1"></i>
                                        {{ $branch->active_services_count ?? 0 }} services
                                    </p>
                                </div>

                                <!-- Stats (if available) -->
                                @if($stats)
                                    <div class="mb-3 text-xs text-gray-500 space-y-1">
                                        @if(isset($stats['recent_bookings']))
                                            <div class="flex justify-between">
                                                <span>Recent Bookings:</span>
                                                <span class="font-medium">{{ $stats['recent_bookings'] }}</span>
                                            </div>
                                        @endif
                                        @if(isset($stats['total_bookings']))
                                            <div class="flex justify-between">
                                                <span>Total Bookings:</span>
                                                <span class="font-medium">{{ $stats['total_bookings'] }}</span>
                                            </div>
                                        @endif
                                        @if(isset($stats['service_count']))
                                            <div class="flex justify-between">
                                                <span>Services:</span>
                                                <span class="font-medium">{{ $stats['service_count'] }}</span>
                                            </div>
                                        @endif
                                        @if(isset($stats['personal_bonus']))
                                            <div class="flex justify-between">
                                                <span>Personal Bonus:</span>
                                                <span class="font-medium">{{ $stats['personal_bonus'] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <!-- Action Buttons -->
                                <div class="space-y-2 mt-auto">
                                    <a href="{{ route('sub_three.home.branch.details', $branch->uuid) }}" 
                                       class="block w-full bg-[#7f5539] hover:bg-[#6b4f3c] text-white py-2 px-3 rounded text-xs font-medium text-center transition-colors">
                                        <i class="fas fa-eye mr-1"></i> View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Empty State -->
        @if(empty($items))
            <div class="text-center py-12">
                <div class="mb-4">
                    <i class="fas fa-search text-gray-400 text-4xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No recommendations found</h3>
                <p class="text-gray-600 max-w-md mx-auto">
                    @if($type == 'you-might-like')
                        No popular services available at the moment.
                    @elseif($type == 'trending-now')
                        No trending services at the moment. Check back later!
                    @elseif($type == 'recommended-for-you')
                        We need more information to provide personalized recommendations. Try setting your preferences or making a booking.
                    @elseif($type == 'your-ideal-matches')
                        No perfect matches found based on your preferences. Try updating your preferences or exploring different categories.
                    @elseif($type == 'popular-similar-users')
                        No recommendations from similar users at the moment. Try setting your preferences first.
                    @elseif($type == 'top-branches')
                        @if($hasUserData)
                            No personalized branch recommendations available yet. Try setting your preferences.
                        @else
                            No branch recommendations available yet.
                        @endif
                    @else
                        No items found in this category.
                    @endif
                </p>
                <div class="mt-6">
                    <a href="{{ route('sub_three.home.showHome') }}" 
                       class="inline-flex items-center px-4 py-2 bg-[#7f5539] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#6b4f3c] focus:outline-none transition ease-in-out duration-150">
                        <i class="fas fa-home mr-2"></i> Return to Home
                    </a>
                    @if(in_array($type, ['recommended-for-you', 'your-ideal-matches', 'popular-similar-users', 'top-branches']) && !$hasUserData)
                        <a href="{{ route('sub_three.home.preferences.form') }}" 
                           class="inline-flex items-center px-4 py-2 ml-3 bg-green-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-600 focus:outline-none transition ease-in-out duration-150">
                            <i class="fas fa-sliders-h mr-2"></i> Set Preferences
                        </a>
                    @endif
                </div>
            </div>
        @endif

        <!-- Pagination (if applicable) -->
        @if(is_object($items) && method_exists($items, 'links'))
            <div class="mt-8">
                {{ $items->links() }}
            </div>
        @endif
    </div>
</div>

<style>
    .shadow-md {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    
    .hover\:shadow-lg:hover {
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    /* Ensure numbers don't overflow grid cells unpleasantly */
    .grid > div {
        isolation: isolate;
    }
    
    /* Line clamp for text */
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endsection