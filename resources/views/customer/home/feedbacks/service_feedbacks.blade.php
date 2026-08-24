@extends('layouts.app')

@section('title', 'Reviews - ' . $service->service_name)

@section('content')
    <!-- Header -->
    <header class="bg-white sticky top-[63px] z-10 transition-all duration-300" id="main-header">
        <div class="container mx-auto px-6 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-1">
                    <!-- Logo removed as requested -->
                </div>
                <nav class="flex space-x-2 text-xs">
                    <a href="{{ route('sub_three.home.feedbacks') }}"
                        class="text-gray-700 hover:text-[#7f5539] font-medium transition-colors nav-link px-2 py-1">
                        All Reviews
                    </a>
                    <a href="#reviews" class="text-[#7f5539] font-medium transition-colors nav-link px-2 py-1 active">
                        Service Reviews
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Service Header -->
    <section class="relative bg-gradient-to-br from-[#f5f0eb] to-[#e6ddd4] py-8 md:py-12">
        <div class="container mx-auto px-3">
            <div class="max-w-4xl mx-auto">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between">
                    <div class="flex-1">
                        <h1 class="text-xl md:text-2xl font-bold text-[#4a3429] mb-2">{{ $service->service_name }}</h1>
                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            <span class="inline-block bg-[#f5f0eb] text-[#7f5539] text-xs px-2 py-1 rounded border border-[#e6ddd4]">
                                <i class="fas fa-tag mr-1"></i>{{ $service->serviceCategory->service_category }}
                            </span>
                            <span class="inline-block bg-[#e6f7f0] text-[#2e8b57] text-xs px-2 py-1 rounded border border-[#d1f0e1]">
                                <i class="fas fa-store mr-1"></i>{{ $service->branch->branch_name }}
                            </span>
                            <span class="inline-block bg-[#f0e6f7] text-[#7b3f9e] text-xs px-2 py-1 rounded border border-[#e6d1f0]">
                                <i class="fas fa-map-marker-alt mr-1 text-red-500"></i>{{ $service->branch->location }}
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-2 mb-2">
                            <span class="inline-block bg-[#f5f0eb] text-[#4a3429] text-xs px-2 py-0.5 rounded">
                                <i class="fas fa-clock mr-1"></i>{{ $service->time_duration }}
                            </span>
                            <span class="inline-block bg-[#f0f7e6] text-[#5c7c3f] text-xs px-2 py-0.5 rounded">
                                <i class="fas fa-cube mr-1"></i>{{ $service->space_type }}
                            </span>
                            <span class="inline-block bg-[#e6f0f7] text-[#3f7c7c] text-xs px-2 py-0.5 rounded">
                                <i class="fas fa-money-bill-wave mr-1"></i>₱{{ number_format($service->price, 2) }}
                            </span>
                        </div>
                        <p class="text-gray-600 text-xs">{{ $service->description ?? 'No description available.' }}</p>
                    </div>
                    <div class="mt-4 md:mt-0 md:ml-6 text-center">
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-[#e6ddd4]">
                            <div class="text-2xl font-bold text-[#4a3429] mb-1">{{ number_format($averageRating, 1) }}</div>
                            <div class="flex justify-center text-yellow-500 mb-1">
                                @for ($i = 1; $i <= 5; $i++)
                                    <i
                                        class="fas fa-star {{ $i <= round($averageRating) ? 'text-yellow-500' : 'text-gray-300' }} text-sm"></i>
                                @endfor
                            </div>
                            <div class="text-xs text-gray-500">{{ $totalReviews }} reviews</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Search & Filter -->
    <section class="bg-white py-4 border-b border-[#e6ddd4]">
        <div class="container mx-auto px-3">
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-lg p-3 shadow-sm border border-[#e6ddd4]">
                    <div class="flex gap-2 items-center">
                        <form method="GET" class="flex-1">
                            <div class="relative">
                                <input type="text" name="search" placeholder="Search reviews..." 
                                       class="w-full px-3 py-2 text-xs text-gray-800 rounded border border-[#d4c4b2] focus:outline-none focus:ring-1 focus:ring-[#7f5539] focus:border-transparent"
                                       value="{{ request('search') }}">
                                <div class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400">
                                    <i class="fas fa-search text-xs"></i>
                                </div>
                            </div>
                        </form>
                        <!-- Filter Button -->
                        <button type="button" id="openFilterModal" 
                                class="text-gray-700 hover:text-[#7f5539] font-medium transition-colors px-3 py-2 border border-[#d4c4b2] rounded text-xs flex items-center whitespace-nowrap">
                            <i class="fas fa-filter mr-1"></i>Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Filter Modal -->
    <div id="filterModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg w-full max-w-md max-h-[80vh] flex flex-col">
                <div class="p-4 border-b border-[#e6ddd4] flex-shrink-0">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-[#4a3429]">Filter Reviews</h3>
                        <button id="closeModal" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <div class="overflow-y-auto flex-1">
                    <form id="filterForm" method="GET" class="p-4 space-y-4">
                        <!-- Rating Filter -->
                        <div>
                            <label class="block text-sm font-medium text-[#4a3429] mb-1">Minimum Rating</label>
                            <select name="rating"
                                class="w-full px-3 py-2 text-sm border border-[#d4c4b2] rounded focus:outline-none focus:ring-1 focus:ring-[#7f5539]">
                                <option value="">All Ratings</option>
                                <option value="5" {{ request('rating') == '5' ? 'selected' : '' }}>5 Stars & above</option>
                                <option value="4" {{ request('rating') == '4' ? 'selected' : '' }}>4 Stars & above</option>
                                <option value="3" {{ request('rating') == '3' ? 'selected' : '' }}>3 Stars & above</option>
                                <option value="2" {{ request('rating') == '2' ? 'selected' : '' }}>2 Stars & above</option>
                                <option value="1" {{ request('rating') == '1' ? 'selected' : '' }}>1 Star & above</option>
                            </select>
                        </div>

                        <!-- Sort Options -->
                        <div>
                            <label class="block text-sm font-medium text-[#4a3429] mb-1">Sort By</label>
                            <select name="sort"
                                class="w-full px-3 py-2 text-sm border border-[#d4c4b2] rounded focus:outline-none focus:ring-1 focus:ring-[#7f5539]">
                                <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest First</option>
                                <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest First</option>
                                <option value="highest_rating" {{ request('sort') == 'highest_rating' ? 'selected' : '' }}>Highest Rating</option>
                                <option value="lowest_rating" {{ request('sort') == 'lowest_rating' ? 'selected' : '' }}>Lowest Rating</option>
                            </select>
                        </div>
                    </form>
                </div>

                <!-- Modal Actions -->
                <div class="p-4 border-t border-[#e6ddd4] flex-shrink-0">
                    <div class="flex space-x-2">
                        <button type="button" id="clearFilters"
                            class="flex-1 px-4 py-2 text-sm border border-[#d4c4b2] rounded text-gray-700 hover:bg-[#f5f0eb]">
                            Clear All
                        </button>
                        <button type="button" id="applyFilters"
                            class="flex-1 px-4 py-2 text-sm bg-[#7f5539] text-white rounded hover:bg-[#6b4f3c]">
                            Apply Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rating Breakdown -->
    <section class="bg-white py-6">
        <div class="container mx-auto px-3">
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-6">
                    <h3 class="text-sm font-semibold text-[#4a3429] mb-2">Rating Distribution</h3>
                    <p class="text-gray-600 text-xs">How customers rate this service</p>
                </div>
                <div class="space-y-2">
                    @foreach ($ratingBreakdown as $breakdown)
                        @php
                            $rating = $breakdown['rating'];
                            $ratingCount = $breakdown['count'];
                            $percentage = $totalReviews > 0 ? ($ratingCount / $totalReviews) * 100 : 0;
                        @endphp
                        <div class="flex items-center group hover:bg-[#f5f0eb] p-2 rounded transition-colors">
                            <span class="text-xs text-[#4a3429] font-medium w-12">{{ $rating }} star</span>
                            <div class="flex-1 mx-3">
                                <div class="bg-gray-200 rounded-full h-2">
                                    <div class="bg-yellow-500 h-2 rounded-full transition-all duration-500"
                                        style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                            <div class="flex items-center justify-end w-24">
                                <span class="text-xs font-medium text-[#4a3429] mr-2">{{ $ratingCount }}</span>
                                <span class="text-xs text-gray-500">({{ number_format($percentage, 1) }}%)</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- Reviews Section -->
    <section id="reviews" class="py-8 bg-[#f5f0eb]">
        <div class="container mx-auto px-3">
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-6">
                    <h2 class="text-lg font-bold text-[#4a3429] mb-2 section-title">Customer Reviews</h2>
                    <p class="text-gray-600 text-xs section-subtitle">What customers are saying about this service</p>
                </div>

                @if ($feedbacks->count() > 0)
                    <div class="space-y-4">
                        @foreach ($feedbacks as $feedback)
                            <div
                                class="bg-white rounded-lg border border-[#e6ddd4] p-4 hover:shadow-md transition duration-300 shadow-sm review-card">
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gradient-to-br from-[#f5f0eb] to-[#e6ddd4] rounded-full flex items-center justify-center mr-3">
    <span class="text-[#7f5539] font-bold text-sm">
        {{ strtoupper(substr($feedback->customer_name ?? 'AN', 0, 2)) }}
    </span>
</div>

                                        <div>
                                            <h4 class="text-sm font-semibold text-[#4a3429]">Anonymous</h4>
                                            <p class="text-xs text-gray-500">{{ $feedback->created_at->format('M d, Y') }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <div class="flex text-yellow-500 mr-1">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <i
                                                    class="fas fa-star {{ $i <= $feedback->rating ? 'text-yellow-500' : 'text-gray-300' }} text-xs"></i>
                                            @endfor
                                        </div>
                                        <span class="text-xs font-medium text-[#4a3429] ml-1">{{ $feedback->rating }}.0</span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <p class="text-gray-600 text-xs leading-relaxed">{{ $feedback->comment }}</p>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex justify-end pt-3 border-t border-[#e6ddd4]">
                                    <button onclick="shareReview('{{ $feedback->id }}')" 
                                            class="text-[#7f5539] hover:text-[#6b4f3c] text-xs font-medium flex items-center">
                                        <i class="fas fa-share-alt mr-1"></i> Share
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $feedbacks->links() }}
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gradient-to-br from-[#f5f0eb] to-[#e6ddd4] rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-comment-slash text-[#b08968] text-xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-[#4a3429] mb-2">No reviews yet</h3>
                        <p class="text-gray-500 text-sm mb-4">Be the first to review this service!</p>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <!-- Service Stats -->
    <section class="py-8 bg-white">
        <div class="container mx-auto px-3">
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-6">
                    <h3 class="text-sm font-semibold text-[#4a3429] mb-2">Service Statistics</h3>
                    <p class="text-gray-600 text-xs">Performance overview</p>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-lg border border-[#e6ddd4] p-4 text-center hover:shadow-md transition duration-300 shadow-sm">
                        <div class="w-12 h-12 bg-gradient-to-br from-[#f5f0eb] to-[#e6ddd4] rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-comments text-[#7f5539] text-lg"></i>
                        </div>
                        <h4 class="text-xl font-bold text-[#4a3429]">{{ $totalReviews }}</h4>
                        <p class="text-xs text-gray-600">Total Reviews</p>
                    </div>
                    <div class="bg-white rounded-lg border border-[#e6ddd4] p-4 text-center hover:shadow-md transition duration-300 shadow-sm">
                        <div class="w-12 h-12 bg-gradient-to-br from-[#f5f0eb] to-[#e6ddd4] rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-star text-yellow-500 text-lg"></i>
                        </div>
                        <h4 class="text-xl font-bold text-[#4a3429]">{{ number_format($averageRating, 1) }}</h4>
                        <p class="text-xs text-gray-600">Average Rating</p>
                    </div>
                    @php
                        $fiveStarCount = $feedbacks->where('rating', 5)->count();
                        $fourStarPlusCount = $feedbacks->where('rating', '>=', 4)->count();
                    @endphp
                    <div class="bg-white rounded-lg border border-[#e6ddd4] p-4 text-center hover:shadow-md transition duration-300 shadow-sm">
                        <div class="w-12 h-12 bg-gradient-to-br from-[#f5f0eb] to-[#e6ddd4] rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-thumbs-up text-green-500 text-lg"></i>
                        </div>
                        <h4 class="text-xl font-bold text-[#4a3429]">{{ $fiveStarCount }}</h4>
                        <p class="text-xs text-gray-600">5-Star Reviews</p>
                    </div>
                    <div class="bg-white rounded-lg border border-[#e6ddd4] p-4 text-center hover:shadow-md transition duration-300 shadow-sm">
                        <div class="w-12 h-12 bg-gradient-to-br from-[#f5f0eb] to-[#e6ddd4] rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-chart-line text-blue-500 text-lg"></i>
                        </div>
                        <h4 class="text-xl font-bold text-[#4a3429]">{{ $totalReviews > 0 ? number_format(($fourStarPlusCount / $totalReviews) * 100, 0) : 0 }}%</h4>
                        <p class="text-xs text-gray-600">4+ Star Rating</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Related Services -->
    @if ($relatedServices->count() > 0)
        <section class="py-8 bg-[#f5f0eb]">
            <div class="container mx-auto px-3">
                <div class="max-w-4xl mx-auto">
                    <div class="text-center mb-6">
                        <h2 class="text-lg font-bold text-[#4a3429] mb-2">Other Services at {{ $service->branch->branch_name }}</h2>
                        <p class="text-gray-600 text-xs">Explore more services from this branch</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($relatedServices as $relatedService)
                            <div class="bg-white rounded-lg border border-[#e6ddd4] p-4 hover:shadow-md transition duration-300 shadow-sm">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h3 class="text-sm font-semibold text-[#4a3429] mb-1">{{ $relatedService->service_name }}</h3>
                                        <div class="flex items-center mb-2">
                                            <span class="inline-block bg-[#f5f0eb] text-[#7f5539] text-xs px-2 py-0.5 rounded">
                                                {{ $relatedService->serviceCategory->service_category }}
                                            </span>
                                        </div>
                                        <div class="flex items-center">
                                            <div class="flex text-yellow-500 mr-1">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <i class="fas fa-star text-xs"></i>
                                                @endfor
                                            </div>
                                            <span class="text-xs text-gray-600">({{ $relatedService->feedbacks_count ?? 0 }} reviews)</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end">
                                        <span class="text-sm font-bold text-[#4a3429] mb-2">₱{{ number_format($relatedService->price, 0) }}</span>
                                        <a href="{{ route('sub_three.home.service.feedbacks', $relatedService->uuid) }}"
                                            class="text-[#7f5539] hover:text-[#6b4f3c] text-xs font-medium flex items-center">
                                            View Reviews <i class="fas fa-arrow-right ml-1 text-xs"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- Footer -->
    <footer class="bg-[#4a3429] text-white py-4">
        <div class="container mx-auto px-3">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center space-x-2 mb-2 md:mb-0">
                    <span class="text-base font-bold">LinkudHub</span>
                </div>
                <div class="text-gray-300 text-xs">
                    <p>&copy; 2025 LinkudHub. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <style>
        /* Match home page styles */
        .section-reveal {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s ease-out;
        }

        .section-reveal.revealed {
            opacity: 1;
            transform: translateY(0);
        }

        .review-card {
            opacity: 0;
            transform: translateY(15px);
            transition: all 0.5s ease-out;
        }

        .review-card.revealed {
            opacity: 1;
            transform: translateY(0);
        }

        header.scrolled {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }

        .nav-link.active {
            color: #7f5539;
            font-weight: 600;
        }

        /* Staggered animation for review cards */
        .review-card.revealed:nth-child(1) { transition-delay: 0.1s; }
        .review-card.revealed:nth-child(2) { transition-delay: 0.2s; }
        .review-card.revealed:nth-child(3) { transition-delay: 0.3s; }
        .review-card.revealed:nth-child(4) { transition-delay: 0.4s; }
        .review-card.revealed:nth-child(5) { transition-delay: 0.5s; }
        .review-card.revealed:nth-child(6) { transition-delay: 0.6s; }

        /* Custom pagination styling */
        .pagination {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
            gap: 0.5rem;
        }

        .pagination li {
            margin: 0;
        }

        .pagination li a,
        .pagination li span {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border: 1px solid #e6ddd4;
            border-radius: 0.25rem;
            color: #4a3429;
            text-decoration: none;
            font-size: 0.75rem;
            transition: all 0.3s;
        }

        .pagination li.active span {
            background-color: #7f5539;
            color: white;
            border-color: #7f5539;
        }

        .pagination li a:hover:not(.disabled) {
            background-color: #f5f0eb;
            border-color: #d4c4b2;
        }

        .pagination li.disabled span {
            color: #d4c4b2;
            cursor: not-allowed;
        }

        @media (max-width: 640px) {
            nav {
                font-size: 0.7rem;
            }
            
            nav .nav-link {
                padding: 0.15rem 0.3rem;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Modal functionality
            const filterModal = document.getElementById('filterModal');
            const filterButton = document.getElementById('openFilterModal');
            const closeModal = document.getElementById('closeModal');
            const clearFiltersBtn = document.getElementById('clearFilters');
            const applyFiltersBtn = document.getElementById('applyFilters');

            // Open modal
            if (filterButton && filterModal) {
                filterButton.addEventListener('click', function() {
                    filterModal.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                });
            }

            // Close modal
            if (closeModal && filterModal) {
                closeModal.addEventListener('click', function() {
                    filterModal.classList.add('hidden');
                    document.body.style.overflow = '';
                });
            }

            // Close modal when clicking outside
            if (filterModal) {
                filterModal.addEventListener('click', function(e) {
                    if (e.target === filterModal) {
                        filterModal.classList.add('hidden');
                        document.body.style.overflow = '';
                    }
                });
            }

            // Clear filters
            if (clearFiltersBtn) {
                clearFiltersBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const cleanUrl = window.location.origin + window.location.pathname;

                    if (filterModal) {
                        filterModal.classList.add('hidden');
                        document.body.style.overflow = '';
                    }

                    window.location.href = cleanUrl;
                });
            }

            // Apply filters
            if (applyFiltersBtn) {
                applyFiltersBtn.addEventListener('click', function() {
                    const form = document.getElementById('filterForm');
                    const formData = new FormData(form);

                    // Remove empty values
                    const params = new URLSearchParams();
                    for (const [key, value] of formData.entries()) {
                        if (value !== '' && value !== null) {
                            params.set(key, value);
                        }
                    }

                    // Add search parameter if it exists
                    const searchInput = document.querySelector('input[name="search"]');
                    if (searchInput && searchInput.value) {
                        params.set('search', searchInput.value);
                    }

                    // Build clean URL
                    const cleanUrl = `${window.location.pathname}?${params.toString()}`;

                    // Close modal
                    if (filterModal) {
                        filterModal.classList.add('hidden');
                        document.body.style.overflow = '';
                    }

                    // Navigate to clean URL
                    window.location.href = cleanUrl;
                });
            }

            // Animation for elements
            const sections = document.querySelectorAll('section');
            const reviewCards = document.querySelectorAll('.review-card');

            // Immediately reveal all sections
            setTimeout(() => {
                sections.forEach(section => {
                    section.classList.add('revealed');
                });
            }, 100);

            // Animate individual elements on scroll
            const elementObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('revealed');
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -10px 0px'
            });

            // Observe review cards
            reviewCards.forEach(card => {
                elementObserver.observe(card);
            });

            // Also reveal elements that are already in viewport
            setTimeout(() => {
                reviewCards.forEach(card => {
                    const rect = card.getBoundingClientRect();
                    if (rect.top < window.innerHeight && rect.bottom > 0) {
                        card.classList.add('revealed');
                    }
                });
            }, 200);

            // Share review function
            window.shareReview = function(reviewId) {
                if (navigator.share) {
                    navigator.share({
                        title: 'LinkudHub Review',
                        text: 'Check out this review on LinkudHub!',
                        url: window.location.origin + '/reviews/' + reviewId
                    })
                    .catch(console.error);
                } else {
                    // Fallback: Copy to clipboard
                    navigator.clipboard.writeText(window.location.origin + '/reviews/' + reviewId)
                        .then(() => {
                            alert('Review link copied to clipboard!');
                        })
                        .catch(console.error);
                }
            };
        });
    </script>
@endsection