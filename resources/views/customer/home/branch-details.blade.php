@extends('layouts.app')

@section('title', $branch->branch_name)

@section('content')
    <!-- Branch Hero Section -->
    <section class="relative bg-gradient-to-br from-[#f5f0eb] to-[#e6ddd4] py-8 md:py-12">
        <div class="container mx-auto px-3">
            <div class="max-w-4xl mx-auto">
                <div class="flex flex-col md:flex-row items-center gap-6">
                    <!-- Branch Profile Image -->
                    <div class="flex-shrink-0">
                        @if ($branch->branch_profile)
                            <img src="{{ asset('storage/app/public/' . ltrim($branch->branch_profile, '/')) }}" 
                                alt="{{ $branch->branch_name }}"
                                class="w-32 h-32 md:w-40 md:h-40 object-cover rounded-full border-4 border-white shadow-lg">
                        @else
                            <div
                                class="w-32 h-32 md:w-40 md:h-40 bg-gradient-to-br from-[#9c6644] to-[#7f5539] rounded-full border-4 border-white shadow-lg flex items-center justify-center">
                                <i class="fas fa-store text-white text-4xl"></i>
                            </div>
                        @endif
                    </div>

                    <!-- Branch Info -->
                    <div class="flex-1 text-center md:text-left">
                        <div class="flex flex-col md:flex-row md:items-center justify-between mb-3">
                            <h1 class="text-2xl md:text-3xl font-bold text-[#4a3429] mb-2 md:mb-0">
                                {{ $branch->branch_name }}</h1>
                            <span
                                class="inline-block px-3 py-1 text-xs rounded-full {{ $branch->branch_status ? 'bg-[#f5f0eb] text-[#7f5539] border border-[#7f5539]' : 'bg-red-100 text-red-800 border border-red-300' }}">
                                {{ $branch->branch_status ? 'Open' : 'Closed' }}
                            </span>
                        </div>

                        <!-- Location -->
                        <div class="mb-4">
                            <div class="flex items-center justify-center md:justify-start text-gray-700 mb-2">
                                <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                                <span class="text-sm">{{ $branch->location }}</span>
                            </div>
                            <button onclick="window.open('{{ $branch->google_map_url }}', '_blank')"
                                class="text-[#7f5539] hover:text-[#6b4f3c] font-medium text-sm flex items-center justify-center md:justify-start transition-colors">
                                <i class="fas fa-route mr-2"></i> Get Directions
                            </button>
                        </div>

                        <!-- Operating Hours -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-clock text-[#b08968] mr-2 w-4"></i>
                                <span class="text-sm">
                                    @if ($branch->open_time && $branch->close_time)
                                        {{ \Carbon\Carbon::parse($branch->open_time)->format('h:i A') }}
                                        -
                                        {{ \Carbon\Carbon::parse($branch->close_time)->format('h:i A') }}
                                    @else
                                        {{ $branch->open_hours ?? 'Not specified' }}
                                    @endif
                                </span>
                            </div>
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-calendar text-[#b08968] mr-2 w-4"></i>
                                <span class="text-sm">{{ $branch->open_days }}</span>
                            </div>
                        </div>

                        <!-- Features Preview -->
                        @if ($branch->features)
                            <div class="mb-4">
                                <h3 class="text-sm font-semibold text-[#4a3429] mb-1">Features:</h3>
                                <p class="text-gray-600 text-sm">{{ Str::limit($branch->features, 100) }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-8 bg-white">
        <div class="container mx-auto px-3">
            <div class="text-center mb-6">
                <h2 class="text-lg font-bold text-[#4a3429] mb-2 section-title">Available Services</h2>
                <p class="text-gray-600 max-w-md mx-auto text-xs section-subtitle">Explore our premium service categories
                </p>
            </div>

            @if ($branch->serviceCategories->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-3 gap-6">
                    @foreach ($branch->serviceCategories as $category)
                        @php
                            // Check if this category is recommended
                            $isRecommended = false;
                            $recommendationReason = '';

                            // Example recommendation logic based on ratings
                            $categoryRating = $category->feedbacks_avg_rating ?? 0;
                            $categoryReviews = $category->feedbacks_count ?? 0;

                            // Recommend categories with high ratings and good review count
                            if ($categoryRating >= 4.0 && $categoryReviews >= 3) {
                                $isRecommended = true;
                                $recommendationReason = 'Highly rated by customers';
                            }

                            // Or based on popularity (number of services)
                            $serviceCount = $category->serviceNames->count();
                            if ($serviceCount >= 5 && !$isRecommended) {
                                $isRecommended = true;
                                $recommendationReason = 'Wide variety of services available';
                            }
                        @endphp

                        <div
                            class="bg-white rounded-lg border border-[#e6ddd4] overflow-hidden hover:shadow-md transition duration-300 service-card shadow-sm @if ($isRecommended) border-2 border-[#7f5539] @endif flex flex-col h-full">

                            <!-- Category Header with Icon -->
                            <div class="p-4 bg-gradient-to-r from-[#f5f0eb] to-[#e6ddd4]">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-concierge-bell text-[#7f5539] text-lg"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex justify-between items-center">
                                            <h3 class="text-base font-semibold text-[#4a3429]">
                                                {{ $category->service_category }}</h3>
                                            <span
                                                class="px-2 py-0.5 text-xs rounded-full {{ $category->service_category_status ? 'bg-[#7f5539] text-white' : 'bg-red-100 text-red-800' }}">
                                                {{ $category->service_category_status ? 'Available' : 'Unavailable' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Category Content -->
                            <div class="p-4 flex-1 flex flex-col">
                                @if ($isRecommended && $recommendationReason)
                                    <div class="mb-3 p-2 bg-[#f5f0eb] rounded border border-[#e6ddd4]">
                                        <p class="text-xs text-gray-600 flex items-center">
                                            <i class="fas fa-lightbulb text-[#b08968] mr-1"></i>
                                            {{ $recommendationReason }}
                                        </p>
                                    </div>
                                @endif

                                @if ($category->description)
                                    <p class="text-gray-600 text-xs mb-3">{{ Str::limit($category->description, 120) }}</p>
                                @endif

                                <!-- Recommended Badge -->
                                @if ($isRecommended)
                                    <span
                                        class="bg-[#7f5539] text-white text-xs px-2 py-1 rounded-full inline-flex items-center shadow-sm w-auto self-start mb-3">
                                        <i class="fas fa-star mr-1"></i>Recommended
                                    </span>
                                @endif

                                <!-- Services Info -->
                                <div class="mb-4 space-y-2">
                                    <div class="flex items-center text-xs text-gray-500">
                                        <i class="fas fa-list text-[#b08968] mr-2 w-3"></i>
                                        <span>{{ $category->serviceNames->count() }}
                                            service{{ $category->serviceNames->count() !== 1 ? 's' : '' }} available</span>
                                    </div>

                                    @if ($categoryReviews > 0)
                                        <div class="flex items-center text-xs text-gray-500">
                                            <i class="fas fa-star text-yellow-500 mr-2 w-3"></i>
                                            <span>{{ number_format($categoryRating, 1) }} ({{ $categoryReviews }}
                                                reviews)</span>
                                        </div>
                                    @endif

                                    <!-- Price Range if available -->
                                    @php
                                        $minPrice = $category->serviceNames->min('price');
                                        $maxPrice = $category->serviceNames->max('price');
                                    @endphp
                                    @if ($minPrice && $maxPrice)
                                        <div class="flex items-center text-xs text-gray-500">
                                            <i class="fas fa-tag text-[#b08968] mr-2 w-3"></i>
                                            <span>₱{{ number_format($minPrice, 0) }} -
                                                ₱{{ number_format($maxPrice, 0) }}</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Popular Services Preview -->
                                @php
                                    $popularServices = $category
                                        ->serviceNames()
                                        ->where('active', 1)
                                        ->where('service_name_status', 1)
                                        ->orderBy('price')
                                        ->limit(2)
                                        ->get();
                                @endphp

                                @if ($popularServices->count() > 0)
                                    <div class="mb-4">
                                        <h4 class="text-xs font-semibold text-[#4a3429] mb-2">Popular Services:</h4>
                                        <ul class="space-y-1">
                                            @foreach ($popularServices as $service)
                                                <li class="flex items-center text-xs text-gray-600">
                                                    <i class="fas fa-check text-green-500 mr-2 text-xs"></i>
                                                    <span class="truncate">{{ $service->service_name }}</span>
                                                    <span
                                                        class="ml-auto text-[#7f5539] font-medium">₱{{ number_format($service->price, 0) }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <!-- Spacer to push button to bottom -->
                                <div class="flex-1"></div>

                                <!-- Action Button - Always at bottom -->
                                <div class="mt-4 pt-4 border-t border-[#e6ddd4]">
                                    <a href="{{ route('sub_three.home.service.category', ['branch_uuid' => $branch->uuid, 'service_category_uuid' => $category->uuid]) }}"
                                        class="block w-full bg-[#7f5539] hover:bg-[#6b4f3c] text-white text-center py-2 px-3 rounded text-xs font-semibold transition duration-300 flex items-center justify-center">
                                        <i class="fas fa-eye mr-2"></i> View All Services
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <i class="fas fa-concierge-bell text-[#e6ddd4] text-4xl mb-3"></i>
                    <h3 class="text-lg font-semibold text-[#4a3429] mb-2">No service categories available</h3>
                    <p class="text-gray-500 text-sm">This branch doesn't have any service categories yet.</p>
                </div>
            @endif
        </div>
    </section>

    <!-- Gallery Section -->
    @php
        $hasGalleryImages = false;
        foreach ($branch->serviceCategories as $category) {
            if ($category->service_img && count($category->service_img) > 0) {
                $hasGalleryImages = true;
                break;
            }
        }
    @endphp

    @if ($hasGalleryImages)
        <section id="gallery" class="py-8 bg-[#f5f0eb]">
            <div class="container mx-auto px-3">
                <div class="text-center mb-6">
                    <h2 class="text-lg font-bold text-[#4a3429] mb-2 section-title">Service Gallery</h2>
                    <p class="text-gray-600 max-w-md mx-auto text-xs section-subtitle">Explore images from our service
                        categories</p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    @php
                        $allImages = [];
                        foreach ($branch->serviceCategories as $category) {
                            if ($category->service_img && count($category->service_img) > 0) {
                                foreach ($category->service_img as $image) {
                                $imageName = str_replace('service_images/', '', ltrim($image, '/'));
                                    $allImages[] = [
                                        'url' => asset('storage/app/public/service_images/' . $imageName),
                                        'category' => $category->service_category,
                                        'category_id' => $category->id,
                                    ];
                                }
                            }
                        }
                        // Limit to 12 images
                        $allImages = array_slice($allImages, 0, 12);
                    @endphp

                    @if (count($allImages) > 0)
                        @foreach ($allImages as $index => $image)
                            <div class="relative aspect-square overflow-hidden rounded-lg cursor-pointer gallery-item hover:shadow-lg transition duration-300"
                                data-index="{{ $index }}" data-category="{{ $image['category_id'] }}"
                                data-image="{{ $image['url'] }}">
                                <img src="{{ $image['url'] }}" alt="{{ $image['category'] }}"
                                    class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">
                                <div
                                    class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 hover:opacity-100 transition-opacity duration-300">
                                    <div class="absolute bottom-2 left-2 right-2">
                                        <p class="text-white text-xs truncate">{{ $image['category'] }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="col-span-full text-center py-8">
                            <i class="fas fa-images text-[#e6ddd4] text-4xl mb-3"></i>
                            <p class="text-gray-500 text-sm">No images available for this branch.</p>
                        </div>
                    @endif
                </div>

                @if (count($allImages) > 12)
                    <div class="text-center mt-6">
                        <button class="text-[#7f5539] hover:text-[#6b4f3c] text-sm font-medium">
                            View All Images <i class="fas fa-arrow-right ml-1"></i>
                        </button>
                    </div>
                @endif
            </div>
        </section>
    @endif


    <!-- Reviews Section -->
    <section id="reviews" class="py-8 bg-white">
        <div class="container mx-auto px-3">
            <div class="text-center mb-6">
                <h2 class="text-lg font-bold text-[#4a3429] mb-2 section-title">Customer Reviews</h2>
                <p class="text-gray-600 max-w-md mx-auto text-xs section-subtitle">What our customers say about this branch
                </p>
            </div>

            <div class="max-w-2xl mx-auto">
                <!-- Branch Overall Rating -->
                <div class="bg-[#f5f0eb] rounded-lg p-4 mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between">
                        <div class="text-center sm:text-left mb-3 sm:mb-0">
                            <h3 class="text-base font-semibold text-[#4a3429] mb-1">Overall Branch Rating</h3>
                            <div class="flex items-center justify-center sm:justify-start">
                                <div class="flex text-yellow-500 text-lg mr-2">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i
                                            class="fas fa-star {{ $i <= round($branch->feedbacks_avg_rating ?? 0) ? 'text-yellow-500' : 'text-gray-300' }}"></i>
                                    @endfor
                                </div>
                                <span class="text-gray-700 font-bold text-lg">
                                    {{ number_format($branch->feedbacks_avg_rating ?? 0, 1) }}/5.0
                                </span>
                            </div>
                            <p class="text-gray-600 text-xs mt-1">
                                Based on {{ $branch->feedbacks_count ?? 0 }}
                                review{{ $branch->feedbacks_count != 1 ? 's' : '' }}
                            </p>
                        </div>
                        <a href="{{ route('sub_three.home.branch.feedbacks', $branch->uuid) }}"
                            class="bg-[#7f5539] hover:bg-[#6b4f3c] text-white py-2 px-4 rounded text-xs font-semibold transition duration-300 inline-flex items-center justify-center">
                            <i class="fas fa-star mr-2"></i> View All Reviews
                        </a>
                    </div>
                </div>

                <!-- Recent Reviews Preview -->
                <div>
                    <h3 class="text-base font-semibold text-[#4a3429] mb-3">Recent Reviews</h3>
                    <div class="space-y-4">
                        @php
                            $recentFeedbacks = \App\Models\Feedback::whereHas(
                                'booking.serviceName.serviceCategory',
                                function ($query) use ($branch) {
                                    $query->where('branch_id', $branch->id);
                                },
                            )
                                ->with('customerAccount', 'booking.serviceName.serviceCategory')
                                ->approved()
                                ->active()
                                ->orderBy('created_at', 'desc')
                                ->limit(3)
                                ->get();
                        @endphp

                        @if ($recentFeedbacks->count() > 0)
                            @foreach ($recentFeedbacks as $feedback)
                                <div class="bg-white border border-[#e6ddd4] rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h4 class="text-sm font-semibold text-[#4a3429]">
                                                Anonymous
                                            </h4>
                                            <p class="text-gray-500 text-xs">
                                                {{ $feedback->booking->serviceName->serviceCategory->service_category ?? 'Service' }}
                                            </p>
                                        </div>
                                        <div class="flex text-yellow-500 text-xs">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <i
                                                    class="fas fa-star {{ $i <= round($feedback->rating) ? 'text-yellow-500' : 'text-gray-300' }}"></i>
                                            @endfor
                                        </div>
                                    </div>
                                    <p class="text-gray-600 text-xs mb-2">
                                        "{{ Str::limit($feedback->comment, 150) }}"
                                    </p>
                                    <p class="text-gray-400 text-xs">
                                        {{ \Carbon\Carbon::parse($feedback->created_at)->diffForHumans() }}
                                    </p>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-6 bg-[#f5f0eb] rounded-lg">
                                <i class="fas fa-comment-alt text-[#e6ddd4] text-3xl mb-3"></i>
                                <p class="text-gray-500 text-sm">No reviews yet for this branch.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="bg-[#4a3429] text-white py-4">
        <div class="container mx-auto px-3">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center space-x-2 mb-2 md:mb-0">
                    <span class="text-base font-bold">LinkudHub</span>
                    <span class="text-xs text-gray-300">• {{ $branch->branch_name }}</span>
                </div>
                <div class="text-gray-300 text-xs">
                    <p>&copy; 2025 LinkudHub. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>


    <!-- Image Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-[9999] hidden">
        <div class="relative w-full max-w-4xl max-h-screen p-4">
            <button id="closeModal" class="absolute top-4 right-4 text-white text-2xl z-10 hover:text-gray-300">
                <i class="fas fa-times"></i>
            </button>

            <button id="prevImage"
                class="absolute left-4 top-1/2 transform -translate-y-1/2 text-white text-2xl z-10 hover:text-gray-300 bg-black/50 rounded-full p-2">
                <i class="fas fa-chevron-left"></i>
            </button>

            <button id="nextImage"
                class="absolute right-4 top-1/2 transform -translate-y-1/2 text-white text-2xl z-10 hover:text-gray-300 bg-black/50 rounded-full p-2">
                <i class="fas fa-chevron-right"></i>
            </button>

            <img id="modalImage" src="" alt=""
                class="w-full h-auto max-h-[80vh] object-contain rounded-lg">

            <div id="modalCaption" class="text-white text-center mt-3 text-sm"></div>
        </div>
    </div>

    <style>
        .section-reveal {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s ease-out;
        }

        .section-reveal.revealed {
            opacity: 1;
            transform: translateY(0);
        }

        .service-card,
        .gallery-item {
            opacity: 0;
            transform: translateY(15px);
            transition: all 0.5s ease-out;
        }

        .service-card.revealed,
        .gallery-item.revealed {
            opacity: 1;
            transform: translateY(0);
        }

        .service-card.revealed:nth-child(1) {
            transition-delay: 0.1s;
        }

        .service-card.revealed:nth-child(2) {
            transition-delay: 0.2s;
        }

        .service-card.revealed:nth-child(3) {
            transition-delay: 0.3s;
        }

        header.scrolled {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }

        .nav-link.active {
            color: #7f5539;
            font-weight: 600;
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Pulse animation for recommended service categories */
        @keyframes pulse-border-brown {
            0% {
                box-shadow: 0 0 0 0 rgba(127, 85, 57, 0.7);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(127, 85, 57, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(127, 85, 57, 0);
            }
        }

        .service-card.border-2 {
            animation: pulse-border-brown 2s infinite;
        }

        /* Netflix-style scrollbar hide */
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        /* Card hover effects */
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 640px) {
            nav {
                font-size: 0.7rem;
            }

            nav .nav-link {
                padding: 0.15rem 0.3rem;
            }

            .service-card {
                margin-bottom: 1rem;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gallery functionality
            let galleryImages = [];
            let currentGalleryIndex = 0;

            const galleryItems = document.querySelectorAll('.gallery-item');
            galleryItems.forEach((item, index) => {
                galleryImages.push({
                    url: item.dataset.image,
                    category: item.querySelector('img').alt
                });

                item.addEventListener('click', function() {
                    currentGalleryIndex = index;
                    openGalleryModal();
                });
            });

            // Image modal functionality
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            const modalCaption = document.getElementById('modalCaption');
            const closeModal = document.getElementById('closeModal');
            const prevImage = document.getElementById('prevImage');
            const nextImage = document.getElementById('nextImage');

            function openGalleryModal() {
                if (galleryImages.length > 0) {
                    modalImage.src = galleryImages[currentGalleryIndex].url;
                    modalCaption.textContent = galleryImages[currentGalleryIndex].category;
                    modal.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                }
            }

            // Close modal
            closeModal.addEventListener('click', function() {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            });

            // Previous image in modal
            prevImage.addEventListener('click', function() {
                if (galleryImages.length > 0) {
                    currentGalleryIndex = (currentGalleryIndex - 1 + galleryImages.length) % galleryImages
                        .length;
                    modalImage.src = galleryImages[currentGalleryIndex].url;
                    modalCaption.textContent = galleryImages[currentGalleryIndex].category;
                }
            });

            // Next image in modal
            nextImage.addEventListener('click', function() {
                if (galleryImages.length > 0) {
                    currentGalleryIndex = (currentGalleryIndex + 1) % galleryImages.length;
                    modalImage.src = galleryImages[currentGalleryIndex].url;
                    modalCaption.textContent = galleryImages[currentGalleryIndex].category;
                }
            });

            // Keyboard navigation
            document.addEventListener('keydown', function(e) {
                if (!modal.classList.contains('hidden')) {
                    if (e.key === 'Escape') {
                        modal.classList.add('hidden');
                        document.body.style.overflow = 'auto';
                    } else if (e.key === 'ArrowLeft') {
                        prevImage.click();
                    } else if (e.key === 'ArrowRight') {
                        nextImage.click();
                    }
                }
            });

            // Close modal when clicking outside image
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                    document.body.style.overflow = 'auto';
                }
            });

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

                updateActiveNavLinkOnScroll();
            });

            function updateActiveNavLinkOnScroll() {
                const sections = document.querySelectorAll('section[id]');
                let currentSection = '';
                const scrollPosition = window.scrollY + 80;

                sections.forEach(section => {
                    const sectionTop = section.offsetTop;
                    const sectionHeight = section.clientHeight;
                    if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                        currentSection = '#' + section.getAttribute('id');
                    }
                });

                document.querySelectorAll('.nav-link').forEach(link => {
                    link.classList.remove('active');
                });

                if (currentSection) {
                    const correspondingLink = document.querySelector(`.nav-link[href="${currentSection}"]`);
                    if (correspondingLink) {
                        correspondingLink.classList.add('active');
                    }
                }
            }

            // Simple animations
            const sections = document.querySelectorAll('section');
            const serviceCards = document.querySelectorAll('.service-card');
            const galleryItemsAnim = document.querySelectorAll('.gallery-item');

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

            // Observe elements
            serviceCards.forEach(card => {
                elementObserver.observe(card);
            });

            galleryItemsAnim.forEach(item => {
                elementObserver.observe(item);
            });

            // Also reveal elements that are already in viewport
            setTimeout(() => {
                const allElements = [...serviceCards, ...galleryItemsAnim];
                allElements.forEach(element => {
                    const rect = element.getBoundingClientRect();
                    if (rect.top < window.innerHeight && rect.bottom > 0) {
                        element.classList.add('revealed');
                    }
                });
            }, 200);

            // Update active nav link
            setTimeout(updateActiveNavLinkOnScroll, 150);
        });
    </script>
@endsection
