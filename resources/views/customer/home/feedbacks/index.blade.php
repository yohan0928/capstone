@extends('layouts.app')

@section('title', 'Customer Reviews & Ratings')

@section('content')
    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-[#f5f0eb] to-[#e6ddd4] py-8 md:py-12">
        <div class="container mx-auto px-3">
            <div class="max-w-xl mx-auto text-center">
                <h1 class="text-xl md:text-2xl font-bold text-[#4a3429] mb-3 leading-tight">Customer Reviews & Ratings</h1>
                <p class="text-gray-600 mb-4 max-w-md mx-auto text-xs">See what our customers are saying about our services
                </p>
            </div>
        </div>
    </section>

    <!-- Search Bar -->
    <section class="bg-white py-4 border-b border-[#e6ddd4]">
        <div class="container mx-auto px-3">
            <div class="max-w-2xl mx-auto">
                <div class="bg-white rounded-lg p-3 shadow-sm border border-[#e6ddd4]">
                    <form action="{{ route('sub_three.home.feedbacks') }}" method="GET" class="flex gap-2 items-center">
                        <div class="relative flex-1">
                            <input type="text" name="search" placeholder="Search reviews by customer name or comment..."
                                class="w-full px-3 py-2 text-xs text-gray-800 rounded border border-[#d4c4b2] focus:outline-none focus:ring-1 focus:ring-[#7f5539] focus:border-transparent"
                                value="{{ request('search') }}">
                            <div class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400">
                                <i class="fas fa-search text-xs"></i>
                            </div>
                        </div>
                        <!-- Filter Button -->
                        <button type="button" id="openFilterModal"
                            class="text-gray-700 hover:text-[#7f5539] font-medium transition-colors px-3 py-2 border border-[#d4c4b2] rounded text-xs flex items-center whitespace-nowrap">
                            <i class="fas fa-filter mr-1"></i>Filters
                        </button>
                    </form>
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
                    <form id="filterForm" action="{{ route('sub_three.home.feedbacks') }}" method="GET"
                        class="p-4 space-y-4">
                        <!-- Service Category Filter -->
                        <div>
                            <label class="block text-sm font-medium text-[#4a3429] mb-1">Service Category</label>
                            <select name="service_category"
                                class="w-full px-3 py-2 text-sm border border-[#d4c4b2] rounded focus:outline-none focus:ring-1 focus:ring-[#7f5539]">
                                <option value="">All Categories</option>
                                @foreach ($serviceCategories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ request('service_category') == $category->id ? 'selected' : '' }}>
                                        {{ $category->service_category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Service Name Filter -->
                        <div>
                            <label class="block text-sm font-medium text-[#4a3429] mb-1">Service Name</label>
                            <select name="service_name"
                                class="w-full px-3 py-2 text-sm border border-[#d4c4b2] rounded focus:outline-none focus:ring-1 focus:ring-[#7f5539]">
                                <option value="">All Services</option>
                                @foreach ($serviceNames as $service)
                                    <option value="{{ $service->id }}"
                                        {{ request('service_name') == $service->id ? 'selected' : '' }}>
                                        {{ $service->service_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Branch Filter -->
                        <div>
                            <label class="block text-sm font-medium text-[#4a3429] mb-1">Branch</label>
                            <select name="branch"
                                class="w-full px-3 py-2 text-sm border border-[#d4c4b2] rounded focus:outline-none focus:ring-1 focus:ring-[#7f5539]">
                                <option value="">All Branches</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}"
                                        {{ request('branch') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->branch_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Rating Filter -->
                        <div>
                            <label class="block text-sm font-medium text-[#4a3429] mb-1">Minimum Rating</label>
                            <select name="rating"
                                class="w-full px-3 py-2 text-sm border border-[#d4c4b2] rounded focus:outline-none focus:ring-1 focus:ring-[#7f5539]">
                                <option value="">All Ratings</option>
                                <option value="5" {{ request('rating') == '5' ? 'selected' : '' }}>5 Stars & above
                                </option>
                                <option value="4" {{ request('rating') == '4' ? 'selected' : '' }}>4 Stars & above
                                </option>
                                <option value="3" {{ request('rating') == '3' ? 'selected' : '' }}>3 Stars & above
                                </option>
                                <option value="2" {{ request('rating') == '2' ? 'selected' : '' }}>2 Stars & above
                                </option>
                                <option value="1" {{ request('rating') == '1' ? 'selected' : '' }}>1 Star & above
                                </option>
                            </select>
                        </div>

                        <!-- Sort Options -->
                        <div>
                            <label class="block text-sm font-medium text-[#4a3429] mb-1">Sort By</label>
                            <select name="sort"
                                class="w-full px-3 py-2 text-sm border border-[#d4c4b2] rounded focus:outline-none focus:ring-1 focus:ring-[#7f5539]">
                                <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest First
                                </option>
                                <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest First
                                </option>
                                <option value="highest_rating" {{ request('sort') == 'highest_rating' ? 'selected' : '' }}>
                                    Highest Rating</option>
                                <option value="lowest_rating" {{ request('sort') == 'lowest_rating' ? 'selected' : '' }}>
                                    Lowest Rating</option>
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

    <!-- Overall Ratings Section -->
    <section class="py-6 bg-white">
        <div class="container mx-auto px-3">
            <div class="text-center mb-6">
                <h2 class="text-lg font-bold text-[#4a3429] mb-2 section-title">Overall Ratings Summary</h2>
                <p class="text-gray-600 max-w-md mx-auto text-xs section-subtitle">Average ratings across all services and
                    branches</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-4xl mx-auto">
                <!-- Service Categories Ratings -->
                <div
                    class="bg-white rounded-lg border border-[#e6ddd4] p-4 hover:shadow-md transition duration-300 shadow-sm">
                    <h3 class="text-sm font-semibold text-[#4a3429] mb-3 flex items-center">
                        <i class="fas fa-list-alt text-[#b08968] mr-2"></i> Service Categories
                    </h3>
                    <div class="space-y-3">
                        @foreach ($averageRatings['service_categories']->take(3) as $category)
                            <div
                                class="flex items-center justify-between group hover:bg-[#f5f0eb] p-2 rounded transition-colors">
                                <div class="flex-1 min-w-0">
                                    <span
                                        class="text-xs text-gray-700 font-medium block truncate">{{ $category->service_category }}</span>
                                </div>
                                <div class="flex items-center ml-2">
                                    <div class="flex text-yellow-500 mr-1">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i
                                                class="fas fa-star {{ $i <= round($category->average_rating) ? 'text-yellow-500' : 'text-gray-300' }} text-xs"></i>
                                        @endfor
                                    </div>
                                    <span
                                        class="text-xs font-medium text-[#4a3429] ml-1">{{ number_format($category->average_rating, 1) }}</span>
                                    <span class="text-xs text-gray-500 ml-1">({{ $category->review_count }})</span>
                                </div>
                            </div>
                        @endforeach
                        @if ($averageRatings['service_categories']->count() > 3)
                            <div class="text-center pt-2">
                                <a href="#reviews" class="text-[#7f5539] hover:text-[#6b4f3c] text-xs font-medium">
                                    View all {{ $averageRatings['service_categories']->count() }} categories
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Services Ratings -->
                <div
                    class="bg-white rounded-lg border border-[#e6ddd4] p-4 hover:shadow-md transition duration-300 shadow-sm">
                    <h3 class="text-sm font-semibold text-[#4a3429] mb-3 flex items-center">
                        <i class="fas fa-concierge-bell text-[#b08968] mr-2"></i> Popular Services
                    </h3>
                    <div class="space-y-3">
                        @foreach ($averageRatings['service_names']->take(3) as $service)
                            <div
                                class="flex items-center justify-between group hover:bg-[#f5f0eb] p-2 rounded transition-colors">
                                <div class="flex-1 min-w-0">
                                    <span
                                        class="text-xs text-gray-700 font-medium block truncate">{{ $service->service_name }}</span>
                                </div>
                                <div class="flex items-center ml-2">
                                    <div class="flex text-yellow-500 mr-1">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i
                                                class="fas fa-star {{ $i <= round($service->average_rating) ? 'text-yellow-500' : 'text-gray-300' }} text-xs"></i>
                                        @endfor
                                    </div>
                                    <span
                                        class="text-xs font-medium text-[#4a3429] ml-1">{{ number_format($service->average_rating, 1) }}</span>
                                    <span class="text-xs text-gray-500 ml-1">({{ $service->review_count }})</span>
                                </div>
                            </div>
                        @endforeach
                        @if ($averageRatings['service_names']->count() > 3)
                            <div class="text-center pt-2">
                                <a href="#reviews" class="text-[#7f5539] hover:text-[#6b4f3c] text-xs font-medium">
                                    View all {{ $averageRatings['service_names']->count() }} services
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Branches Ratings -->
                <div
                    class="bg-white rounded-lg border border-[#e6ddd4] p-4 hover:shadow-md transition duration-300 shadow-sm">
                    <h3 class="text-sm font-semibold text-[#4a3429] mb-3 flex items-center">
                        <i class="fas fa-store text-[#b08968] mr-2"></i> Branches
                    </h3>
                    <div class="space-y-3">
                        @foreach ($averageRatings['branches']->take(3) as $branch)
                            <div
                                class="flex items-center justify-between group hover:bg-[#f5f0eb] p-2 rounded transition-colors">
                                <div class="flex-1 min-w-0">
                                    <span
                                        class="text-xs text-gray-700 font-medium block truncate">{{ $branch->branch_name }}</span>
                                </div>
                                <div class="flex items-center ml-2">
                                    <div class="flex text-yellow-500 mr-1">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i
                                                class="fas fa-star {{ $i <= round($branch->average_rating) ? 'text-yellow-500' : 'text-gray-300' }} text-xs"></i>
                                        @endfor
                                    </div>
                                    <span
                                        class="text-xs font-medium text-[#4a3429] ml-1">{{ number_format($branch->average_rating, 1) }}</span>
                                    <span class="text-xs text-gray-500 ml-1">({{ $branch->review_count }})</span>
                                </div>
                            </div>
                        @endforeach
                        @if ($averageRatings['branches']->count() > 3)
                            <div class="text-center pt-2">
                                <a href="#reviews" class="text-[#7f5539] hover:text-[#6b4f3c] text-xs font-medium">
                                    View all {{ $averageRatings['branches']->count() }} branches
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Reviews Section -->
    <section id="reviews" class="py-8 bg-[#f5f0eb]">
        <div class="container mx-auto px-3">
            <div class="text-center mb-6">
                <h2 class="text-lg font-bold text-[#4a3429] mb-2 section-title">Customer Reviews</h2>
                <p class="text-gray-600 max-w-md mx-auto text-xs section-subtitle">What our customers are saying about
                    their experience</p>
            </div>

            @if ($feedbacks->count() > 0)
                <!-- Changed to 3 columns and increased max-width -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-7xl mx-auto">
                    @foreach ($feedbacks as $feedback)
                        <div
                            class="bg-white rounded-lg border border-[#e6ddd4] p-4 hover:shadow-md transition duration-300 shadow-sm review-card">
                            <!-- Header -->
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex items-center">
                                    <div
                                        class="w-10 h-10 bg-gradient-to-br from-[#f5f0eb] to-[#e6ddd4] rounded-full flex items-center justify-center mr-3">
                                        <span
                                            class="text-[#7f5539] font-bold text-sm">{{ strtoupper(substr($feedback->customer_name, 0, 1)) }}</span>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-[#4a3429]">{{ $feedback->customer_name }}
                                        </h4>
                                        <!-- Added Anonymous above date -->
                                        <p class="text-xs text-gray-500 mb-0.5">Anonymous</p>
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

                            <!-- Service Details -->
                            <div class="mb-3">
                                <div class="flex flex-wrap gap-1 mb-2">
                                    <span
                                        class="inline-block bg-[#f5f0eb] text-[#7f5539] text-xs px-2 py-0.5 rounded border border-[#e6ddd4]">
                                        <i class="fas fa-tag mr-1"></i>{{ $feedback->serviceCategory->service_category }}
                                    </span>
                                    <span
                                        class="inline-block bg-[#e6f7f0] text-[#2e8b57] text-xs px-2 py-0.5 rounded border border-[#d1f0e1]">
                                        <i
                                            class="fas fa-concierge-bell mr-1"></i>{{ $feedback->serviceName->service_name }}
                                    </span>
                                    <span
                                        class="inline-block bg-[#f0e6f7] text-[#7b3f9e] text-xs px-2 py-0.5 rounded border border-[#e6d1f0]">
                                        <i class="fas fa-store mr-1"></i>{{ $feedback->branch->branch_name }}
                                    </span>
                                </div>
                            </div>

                            <!-- Comment -->
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
                <div class="mt-6 flex justify-center">
                    {{ $feedbacks->links() }}
                </div>
            @else
                <div class="text-center py-8">
                    <div
                        class="w-16 h-16 bg-gradient-to-br from-[#f5f0eb] to-[#e6ddd4] rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-comment-slash text-[#b08968] text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-[#4a3429] mb-2">No reviews found</h3>
                </div>
            @endif
        </div>
    </section>

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
        .review-card.revealed:nth-child(1) {
            transition-delay: 0.1s;
        }

        .review-card.revealed:nth-child(2) {
            transition-delay: 0.2s;
        }

        .review-card.revealed:nth-child(3) {
            transition-delay: 0.3s;
        }

        .review-card.revealed:nth-child(4) {
            transition-delay: 0.4s;
        }

        .review-card.revealed:nth-child(5) {
            transition-delay: 0.5s;
        }

        .review-card.revealed:nth-child(6) {
            transition-delay: 0.6s;
        }

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

        /* Stats card hover effect */
        .hover\\:shadow-md:hover {
            box-shadow: 0 4px 6px -1px rgba(127, 85, 57, 0.1), 0 2px 4px -1px rgba(127, 85, 57, 0.06);
        }

        @media (max-width: 640px) {
            nav {
                font-size: 0.7rem;
            }

            nav .nav-link {
                padding: 0.15rem 0.3rem;
            }

            .grid-cols-2 {
                grid-template-columns: 1fr;
            }

            .pagination {
                flex-wrap: wrap;
                justify-content: center;
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

                    // Build clean URL
                    const cleanUrl = `${form.action}?${params.toString()}`;

                    // Close modal
                    if (filterModal) {
                        filterModal.classList.add('hidden');
                        document.body.style.overflow = '';
                    }

                    // Navigate to clean URL
                    window.location.href = cleanUrl;
                });
            }

            // Smooth scroll for navigation
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    const target = document.querySelector(targetId);

                    if (target) {
                        const headerHeight = document.getElementById('main-header').offsetHeight;
                        const targetPosition = target.offsetTop - headerHeight - 10;

                        window.scrollTo({
                            top: targetPosition,
                            behavior: 'smooth'
                        });
                    }
                });
            });

            // Header scroll effect
            const header = document.getElementById('main-header');
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            });

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