@extends('layouts.app')

@section('title', $serviceCategory->service_category . ' - ' . $branch->branch_name)

@section('content')
<div class="flex flex-col min-h-screen">
    <div class="flex-grow">
        <!-- Hero Section for Category -->
        <section class="relative bg-gradient-to-br from-[#f5f0eb] to-[#e6ddd4] py-8 md:py-12">
            <div class="container mx-auto px-3">
                <div class="max-w-xl mx-auto text-center">
                    <!-- Breadcrumb -->
                    <div class="flex justify-center mb-4">
                        <nav class="flex" aria-label="Breadcrumb">
                            <ol class="inline-flex items-center space-x-1 md:space-x-2">
                                <li>
                                    <div class="flex items-center">
                                        <a href="{{ route('sub_three.home.branch.details', $branch->uuid) }}"
                                            class="ml-1 text-xs font-medium text-gray-700 hover:text-[#7f5539]">
                                            {{ Str::limit($branch->branch_name, 20) }}
                                        </a>
                                    </div>
                                </li>
                                <li aria-current="page">
                                    <div class="flex items-center">
                                        <i class="fas fa-chevron-right text-gray-400 mx-1 text-xs"></i>
                                        <span class="ml-1 text-xs font-medium text-[#7f5539]">
                                            {{ Str::limit($serviceCategory->service_category, 20) }}
                                        </span>
                                    </div>
                                </li>
                            </ol>
                        </nav>
                    </div>
    
                    <h1 class="text-xl md:text-2xl font-bold text-[#4a3429] mb-3 leading-tight">
                        {{ $serviceCategory->service_category }}
                    </h1>
                    <p class="text-gray-600 mb-4 max-w-md mx-auto text-xs">
                        Available at <span class="font-semibold text-[#4a3429]">{{ $branch->branch_name }}</span> •
                        <span class="text-gray-500">{{ $branch->location }}</span>
                    </p>
    
                    <!-- Category Statistics -->
                    <div class="flex items-center justify-center space-x-4 mb-4">
                        <div class="text-center">
                            <span
                                class="block text-lg font-bold text-[#7f5539]">{{ $serviceCategory->serviceNames->count() }}</span>
                            <span class="text-xs text-gray-600">Services</span>
                        </div>
                        <div class="text-center">
                            <span
                                class="block text-lg font-bold text-[#7f5539]">{{ number_format($serviceCategory->feedbacks_avg_rating ?? 0, 1) }}</span>
                            <span class="text-xs text-gray-600">Category Rating</span>
                        </div>
                        <div class="text-center">
                            <span
                                class="block text-lg font-bold text-[#7f5539]">{{ $serviceCategory->feedbacks_count ?? 0 }}</span>
                            <span class="text-xs text-gray-600">Reviews</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    
        <!-- Services Section -->
        <section id="services" class="py-8 bg-white">
            <div class="container mx-auto px-3">
                <div class="flex flex-col items-center text-center mb-6 space-y-2 sm:space-y-3">
                    <h2 class="text-lg font-bold text-[#4a3429] section-title">
                        Available {{ $serviceCategory->service_category }} Services
                    </h2>
                    <p class="text-gray-600 max-w-md mx-auto text-xs section-subtitle">
                        Choose from services designed for your needs
                    </p>
                </div>

                @if ($sortedServices->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                        @foreach ($sortedServices as $service)
                            @php
                                $feedbackData = $serviceFeedbacks[$service->id] ?? [
                                    'average_rating' => 0,
                                    'total_reviews' => 0,
                                    'feedbacks' => collect(),
                                    'rating_breakdown' => collect(),
                                ];

                                // Discount calculation
                                $hasDiscount = $service->discount && $service->discount > 0;
                                $oldPrice = $service->old_price ?: $service->price;
                                $currentPrice = $service->price;
                                $discountAmount = $oldPrice - $currentPrice;
                                $discountPercentage = $oldPrice > 0 ? round(($discountAmount / $oldPrice) * 100) : 0;
                            @endphp

                            <!-- Service Card -->
                            <div class="relative group h-full">
                                <div class="bg-white rounded-lg border border-[#e6ddd4] overflow-hidden hover:shadow-md transition duration-300 h-full flex flex-col service-card shadow-sm">
                                    <div class="p-3 flex-1">
                                        <!-- Service Header with Branch Info and Price aligned -->
                                        <div class="mb-2">
                                            <div class="flex items-start justify-between mb-1">
                                                <div class="flex items-center flex-1 min-w-0">
                                                    @if ($branch->branch_profile)
                                                        <img src="{{ asset('storage/app/public/' . ltrim($branch->branch_profile, '/')) }}"
                                                            alt="{{ $branch->branch_name }}"
                                                            class="w-10 h-10 rounded object-cover mr-2">
                                                    @else
                                                        <div class="w-10 h-10 bg-[#f5f0eb] rounded flex items-center justify-center mr-2">
                                                            <i class="fas fa-store text-[#b08968] text-base"></i>
                                                        </div>
                                                    @endif
                                                    <div class="flex-1 min-w-0">
                                                        <h3 class="text-sm font-semibold text-[#4a3429] truncate">
                                                            {{ $service->service_name }}
                                                        </h3>
                                                        <div class="flex items-baseline justify-between mt-0.5">
                                                            <p class="text-xs text-gray-500 truncate flex-1 mr-2">
                                                                {{ $branch->branch_name }}
                                                            </p>
                                                            <!-- Price aligned with branch name -->
                                                            <div class="flex flex-col items-end">
                                                                @if ($hasDiscount && $discountAmount > 0)
                                                                    <div class="space-y-1">
                                                                        <div class="flex items-center gap-2">
                                                                            <div class="text-gray-400 line-through text-xs">
                                                                                ₱{{ number_format($oldPrice, 0) }}
                                                                            </div>
                                                                            <div class="text-gray-900 font-bold text-sm whitespace-nowrap">
                                                                                ₱{{ number_format($currentPrice, 0) }}
                                                                            </div>
                                                                        </div>
                                                                        <div class="flex items-center gap-1">
                                                                            <span class="bg-red-100 text-red-700 text-[10px] px-1.5 py-0.5 rounded-full font-medium">
                                                                                {{ $discountPercentage }}% OFF
                                                                            </span>
                                                                            <span class="text-green-600 text-[10px] font-medium">
                                                                                Save ₱{{ number_format($discountAmount, 0) }}
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                @else
                                                                    <p class="text-gray-900 font-bold text-sm whitespace-nowrap">
                                                                        ₱{{ number_format($service->price, 0) }}
                                                                    </p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Category Badge with Status on the Right -->
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="inline-block bg-[#f5f0eb] text-[#7f5539] text-xs px-2 py-1 rounded font-medium">
                                                {{ $serviceCategory->service_category }}
                                            </span>
                                            
                                            <!-- Availability Status -->
                                            <span class="px-2 py-1 text-xs rounded-full {{ $service->service_name_status ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200' }}">
                                                {{ $service->service_name_status ? 'Available' : 'Unavailable' }}
                                            </span>
                                        </div>

                                        <!-- Service Details -->
                                        <div class="mb-3 space-y-2">
                                            <div class="flex items-center text-xs text-gray-600">
                                                <i class="fas fa-clock text-[#b08968] mr-2 w-3"></i>
                                                <span>{{ $service->time_duration }}</span>
                                                @if (is_numeric($service->time_duration))
                                                    minutes
                                                @endif
                                            </div>
                                            <div class="flex items-center text-xs text-gray-600">
                                                <i class="fas fa-expand-arrows-alt text-[#b08968] mr-2 w-3"></i>
                                                <span>{{ ucfirst(str_replace('_', ' ', $service->space_type)) }}</span>
                                            </div>
                                            @if ($service->description)
                                                <p class="text-xs text-gray-600 mt-1 line-clamp-2">
                                                    {{ Str::limit($service->description, 80) }}
                                                </p>
                                            @endif
                                        </div>

                                        <!-- Rating Section -->
                                        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-2 gap-2">
                                            <div class="flex items-center">
                                                <div class="flex text-yellow-500 mr-1">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        @php
                                                            $rating = $feedbackData['average_rating'];
                                                            $fullStars = floor($rating);
                                                            $halfStar = $rating - $fullStars >= 0.5;
                                                        @endphp

                                                        @if ($i <= $fullStars)
                                                            <i class="fas fa-star text-yellow-500 text-xs"></i>
                                                        @elseif ($i == $fullStars + 1 && $halfStar)
                                                            <i class="fas fa-star-half-alt text-yellow-500 text-xs"></i>
                                                        @else
                                                            <i class="far fa-star text-gray-300 text-xs"></i>
                                                        @endif
                                                    @endfor
                                                </div>
                                                <span class="text-xs text-gray-600 ml-1">
                                                    {{ number_format($feedbackData['average_rating'], 1) }}
                                                    @if ($feedbackData['total_reviews'] > 0)
                                                        ({{ $feedbackData['total_reviews'] }} reviews)
                                                    @endif
                                                </span>
                                            </div>

                                            @if ($feedbackData['total_reviews'] > 0)
                                                <a href="{{ route('sub_three.home.service.feedbacks', $service->uuid) }}"
                                                    class="text-[#7f5539] hover:text-[#6b4f3c] text-xs block">
                                                    See reviews
                                                </a>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Action buttons -->
                                    <div class="p-3 bg-[#f5f0eb] border-t border-[#e6ddd4]">
                                        <div class="space-y-2">
                                            @if ($service->service_name_status)
                                                <a href="{{ route('sub_three.home.booking.form', [
                                                    'branch_uuid' => $branch->uuid,
                                                    'service_category_uuid' => $serviceCategory->uuid,
                                                    'service_name_uuid' => $service->uuid,
                                                ]) }}"
                                                    class="w-full bg-[#7f5539] hover:bg-[#6b4f3c] text-white py-2 px-3 rounded font-semibold transition duration-300 flex items-center justify-center text-xs shadow-sm">
                                                    <i class="fas fa-calendar-plus mr-2"></i>Book Now
                                                </a>
                                            @else
                                                <button disabled
                                                    class="w-full bg-gray-400 text-white py-2 px-3 rounded font-semibold flex items-center justify-center text-xs cursor-not-allowed">
                                                    <i class="fas fa-times-circle mr-2"></i>Currently Unavailable
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-concierge-bell text-[#e6ddd4] text-4xl mb-3"></i>
                        <h3 class="text-lg font-semibold text-[#4a3429] mb-2">No services found</h3>
                        <p class="text-gray-500 text-sm">There are no services available in this category.</p>
                    </div>
                @endif
            </div>
        </section>
    </div>
    
    <!-- Footer -->
    <footer class="bg-[#4a3429] text-white py-4 mt-auto">
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

    .service-card {
        opacity: 0;
        transform: translateY(15px);
        transition: all 0.5s ease-out;
    }

    .service-card.revealed {
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

    .service-card.revealed:nth-child(4) {
        transition-delay: 0.4s;
    }

    .price-container {
        position: relative;
        z-index: 5;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sections = document.querySelectorAll('section');
        const serviceCards = document.querySelectorAll('.service-card');

        setTimeout(() => {
            sections.forEach(section => {
                section.classList.add('revealed');
            });
        }, 100);

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

        serviceCards.forEach(card => {
            elementObserver.observe(card);
        });

        setTimeout(() => {
            serviceCards.forEach(card => {
                const rect = card.getBoundingClientRect();
                if (rect.top < window.innerHeight && rect.bottom > 0) {
                    card.classList.add('revealed');
                }
            });
        }, 200);
    });
</script>
@endsection