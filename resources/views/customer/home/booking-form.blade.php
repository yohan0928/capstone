<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Form - {{ $service->service_name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    <link rel="icon" href="{{ asset('storage/logo.png') }}" type="image/png">
    <style>
        /* Custom styles matching home page */
        .image-container {
            position: relative;
            overflow: hidden;
            border-radius: 0.5rem;
            cursor: pointer;
        }

        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(127, 85, 57, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .image-container:hover .image-overlay {
            opacity: 1;
        }

        .flatpickr-input {
            background-color: white;
        }

        .datetime-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 640px) {
            .datetime-group {
                grid-template-columns: 1fr;
            }
        }

        .seat-item.selected label {
            border-color: #7f5539;
            background-color: #f5f0eb;
            box-shadow: 0 4px 6px -1px rgba(127, 85, 57, 0.1);
        }

        .time-slot {
            transition: all 0.2s ease;
        }

        .time-slot:hover {
            transform: translateY(-1px);
        }

        .time-slot.selected {
            background-color: #7f5539;
            color: white;
            border-color: #7f5539;
        }

        .time-slot.disabled {
            background-color: #f5f0eb;
            color: #b08968;
            border-color: #e6ddd4;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .date-section {
            border: 1px solid #e6ddd4;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            background-color: white;
        }

        .date-header {
            background-color: #f5f0eb;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e6ddd4;
            font-weight: 600;
            color: #4a3429;
        }

        .time-slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 0.5rem;
            padding: 1rem;
            max-height: 300px;
            overflow-y: auto;
        }

        .closed-hours-label {
            background-color: #fef3cd;
            border: 1px solid #fde68a;
            color: #92400e;
            padding: 0.5rem;
            text-align: center;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            margin: 0.5rem 1rem;
        }

        .period-label {
            background-color: #e0f2fe;
            color: #0369a1;
            padding: 0.5rem;
            text-align: center;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            margin: 0.5rem 0;
        }

        .period-open {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .period-overnight {
            background-color: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .period-break {
            background-color: #fef3cd;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .quantity-input {
            display: flex;
            align-items: center;
            border: 1px solid #d4c4b2;
            border-radius: 0.375rem;
            overflow: hidden;
            max-width: 200px;
        }

        .quantity-btn {
            background-color: #f5f0eb;
            border: none;
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: background-color 0.2s;
            color: #4a3429;
        }

        .quantity-btn:hover {
            background-color: #e6ddd4;
        }

        .quantity-btn:disabled {
            background-color: #f9fafb;
            color: #9ca3af;
            cursor: not-allowed;
        }

        .quantity-display {
            padding: 0.5rem 1rem;
            background-color: white;
            min-width: 80px;
            text-align: center;
            font-weight: 600;
            color: #4a3429;
        }

        .extended-time-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 640px) {
            .extended-time-inputs {
                grid-template-columns: 1fr;
            }
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            max-width: 400px;
            width: 90%;
        }

        .no-slots-message {
            background-color: #fef3cd;
            border: 1px solid #fde68a;
            color: #92400e;
            padding: 1rem;
            text-align: center;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            margin: 1rem 0;
        }

        .price-breakdown {
            background-color: #f8fafc;
            border-radius: 0.375rem;
            padding: 0.75rem;
            margin: 0.5rem 0;
            border: 1px solid #e6ddd4;
        }

        .price-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.25rem 0;
        }

        .price-item-label {
            font-size: 0.875rem;
            color: #4b5563;
        }

        .price-item-value {
            font-size: 0.875rem;
            font-weight: 500;
            color: #111827;
        }

        .subtotal {
            border-top: 1px solid #d4c4b2;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            font-weight: 600;
        }

        .total-section {
            background-color: #eff6ff;
            border: 1px solid #dbeafe;
            border-radius: 0.375rem;
            padding: 0.75rem;
            margin-top: 0.5rem;
        }

        .closing_time-slot {
            border-left: 3px solid #9ca3af !important;
            background-color: #4a3429 !important;
            color: white !important;
        }

        .closing_time-slot.selected {
            background-color: #7f5539 !important;
            color: white !important;
            border-color: #6b4f3c !important;
        }

        .booked-slot {
            background-color: #fef2f2 !important;
            border-color: #fecaca !important;
            color: #991b1b !important;
            cursor: not-allowed !important;
            opacity: 0.7 !important;
            position: relative;
        }

        .booked-slot:hover {
            background-color: #fef2f2 !important;
            border-color: #fecaca !important;
            transform: none !important;
        }

        .booked-slot::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 49%, #fca5a5 49%, #fca5a5 51%, transparent 51%);
            background-size: 4px 4px;
            opacity: 0.3;
        }

        .conflicting-slot {
            background-color: #fef3c7 !important;
            border-color: #fbbf24 !important;
            color: #92400e !important;
        }

        .conflicting-slot:hover {
            background-color: #fde68a !important;
            border-color: #f59e0b !important;
        }

        .booking-conflict-message {
            animation: fadeIn 0.3s ease-in-out;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .flatpickr-calendar {
            z-index: 9999 !important;
        }

        .flatpickr-wrapper {
            position: relative;
            z-index: 1;
        }

        .modal {
            z-index: 10000 !important;
        }

        /* Home page matching styles */
        .section-title {
            color: #4a3429;
            font-weight: bold;
        }

        .section-subtitle {
            color: #666;
        }

        .btn-primary {
            background-color: #7f5539;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #6b4f3c;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background-color: #9c6644;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background-color: #7f5539;
            transform: translateY(-1px);
        }

        .card {
            background: white;
            border: 1px solid #e6ddd4;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 4px 12px rgba(127, 85, 57, 0.1);
        }

        .text-primary {
            color: #4a3429;
        }

        .text-secondary {
            color: #7f5539;
        }

        .bg-light {
            background-color: #f5f0eb;
        }

        .bg-dark {
            background-color: #4a3429;
        }

        .border-light {
            border-color: #e6ddd4;
        }

        /* Scrollbar styling */
        .time-slots-grid::-webkit-scrollbar {
            width: 6px;
        }

        .time-slots-grid::-webkit-scrollbar-track {
            background: #f5f0eb;
            border-radius: 3px;
        }

        .time-slots-grid::-webkit-scrollbar-thumb {
            background: #b08968;
            border-radius: 3px;
        }

        .time-slots-grid::-webkit-scrollbar-thumb:hover {
            background: #7f5539;
        }

        /* Existing booking styles */
        .existing-booking-slot {
            background-color: #f3e8ff !important;
            border-color: #d8b4fe !important;
            color: #6b21a8 !important;
            position: relative;
            cursor: pointer;
        }

        .existing-booking-slot::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: repeating-linear-gradient(45deg,
                    transparent,
                    transparent 2px,
                    rgba(168, 85, 247, 0.1) 2px,
                    rgba(168, 85, 247, 0.1) 4px);
        }

        .existing-booking-slot.selected {
            background-color: #6b21a8 !important;
            color: white !important;
            border-color: #6b21a8 !important;
        }

        .existing-booking-info {
            background-color: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 0.375rem;
            padding: 0.75rem;
            margin-bottom: 1rem;
        }

        /* Style for the period indicator/label below time slot */
        .period-indicator {
            margin-top: 4px;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 500;
            text-transform: capitalize;
        }

        /* Color variations for different period types */
        .period-overnight {
            background-color: #dbeafe;
            /* Light blue */
            color: #1e40af;
            /* Dark blue */
            border: 1px solid #bfdbfe;
        }

        .period-day {
            background-color: #dcfce7;
            /* Light green */
            color: #166534;
            /* Dark green */
            border: 1px solid #bbf7d0;
        }

        .period-open {
            background-color: #dcfce7;
            /* Light green */
            color: #166534;
            /* Dark green */
            border: 1px solid #bbf7d0;
        }

        .period-break {
            background-color: #fef3cd;
            /* Light yellow */
            color: #92400e;
            /* Dark orange */
            border: 1px solid #fde68a;
        }

        .period-closed {
            background-color: #f3f4f6;
            /* Light gray */
            color: #6b7280;
            /* Gray */
            border: 1px solid #e5e7eb;
        }

        .period-late-night {
            background-color: #ede9fe;
            /* Light purple */
            color: #6d28d9;
            /* Dark purple */
            border: 1px solid #ddd6fe;
        }

        /* For the "closing_time" period type in end time slots */
        .period-closing_time {
            background-color: #f3f4f6;
            /* Light gray */
            color: #374151;
            /* Dark gray */
            border: 1px solid #d1d5db;
            font-size: 0.65rem;
        }

        /* If you want to match the home page color scheme */
        .period-indicator-brown {
            background-color: #f5f0eb;
            /* Light brown from home page */
            color: #7f5539;
            /* Brown from home page */
            border: 1px solid #e6ddd4;
        }

        /* Alternative: Match the exact home page colors */
        .period-indicator-primary {
            background-color: #f5f0eb;
            /* Light brown */
            color: #4a3429;
            /* Dark brown */
            border: 1px solid #e6ddd4;
        }

        .period-indicator-secondary {
            background-color: #e6ddd4;
            /* Medium light brown */
            color: #7f5539;
            /* Medium brown */
            border: 1px solid #d4c4b2;
        }

        /* If you want the labels to look like badges */
        .period-badge {
            margin-top: 2px;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .period-badge.overnight {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .period-badge.day {
            background-color: #dcfce7;
            color: #166534;
        }

        /* For your specific use case - if you want the period label to be more prominent */
        .time-slot .period-label {
            display: block;
            margin-top: 4px;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.7rem;
            font-weight: 500;
            background-color: #f8fafc;
            color: #4b5563;
            border: 1px solid #e5e7eb;
        }

        /* If you want the period indicator to only show on hover */
        .time-slot:hover .period-indicator {
            background-color: #7f5539;
            color: white;
        }

        /* Responsive styling for mobile */
        @media (max-width: 640px) {
            .period-indicator {
                font-size: 0.6rem;
                padding: 1px 4px;
            }

            .time-slot .period-label {
                font-size: 0.6rem;
                padding: 1px 4px;
            }
        }

        /* Animation for period indicator */
        .period-indicator {
            transition: all 0.2s ease;
        }

        .time-slot.selected .period-indicator {
            background-color: white;
            color: #7f5539;
            border-color: #7f5539;
        }

        /* Color coding based on availability */
        .time-slot.available .period-indicator {
            background-color: #dcfce7;
            color: #166534;
        }

        .time-slot.unavailable .period-indicator {
            background-color: #fef2f2;
            color: #991b1b;
        }

        .time-slot.booked .period-indicator {
            background-color: #f3f4f6;
            color: #6b7280;
            text-decoration: line-through;
        }
    </style>
</head>

<body class="bg-[#f5f0eb] min-h-screen">
    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-[#f5f0eb] to-[#e6ddd4] py-6">
        <div class="container mx-auto px-3">
            <div class="max-w-2xl mx-auto text-center">
                <h1 class="text-xl md:text-2xl font-bold text-[#4a3429] mb-2 leading-tight">Book Your Service</h1>
                <p class="text-gray-600 text-xs md:text-sm">Welcome, {{ $customer->first_name }}
                    {{ $customer->last_name }}! Complete your booking details below.</p>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mx-auto px-3 py-6">
        <div class="max-w-6xl mx-auto">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column - Service Details -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm border border-[#e6ddd4] p-5 sticky top-5">
                        <h3 class="text-base font-bold text-[#4a3429] mb-4 flex items-center">
                            <i class="fas fa-info-circle mr-2 text-[#7f5539]"></i>Service Details
                        </h3>

                        <!-- Service Image -->
                        <div class="mb-4">
                            @if ($serviceCategory->service_img)
                                @php
                                    // Determine image URL
                                    if (is_array($serviceCategory->service_img)) {
                                        $firstImage = !empty($serviceCategory->service_img[0]) ? $serviceCategory->service_img[0] : null;
                                    } else {
                                        $firstImage = $serviceCategory->service_img;
                                    }
                                    
                                    if ($firstImage) {
                                        $firstImage = ltrim(str_replace('service_images/', '', $firstImage), '/');
                                    }
                        
                                    // Full URL
                                    $imageUrl = $firstImage ? asset('storage/app/public/service_images/' . $firstImage) : null;
                                @endphp
                                @if ($imageUrl)
                                    <div class="image-container">
                                        <img src="{{ $imageUrl }}" alt="{{ $serviceCategory->service_category }}"
                                            class="w-full h-48 object-cover rounded-lg">
                                        <a href="{{ $imageUrl }}" target="_blank" class="image-overlay">
                                            <button
                                                class="bg-white text-[#7f5539] px-4 py-2 rounded-md font-medium flex items-center text-sm">
                                                <i class="fas fa-expand mr-2"></i> View Full Image
                                            </button>
                                        </a>
                                    </div>
                                @else
                                    <div class="w-full h-48 bg-[#f5f0eb] rounded-lg flex items-center justify-center">
                                        <i class="fas fa-concierge-bell text-[#b08968] text-4xl"></i>
                                    </div>
                                @endif
                            @else
                                <div class="w-full h-48 bg-[#f5f0eb] rounded-lg flex items-center justify-center">
                                    <i class="fas fa-concierge-bell text-[#b08968] text-4xl"></i>
                                </div>
                            @endif
                            <p class="text-xs text-gray-500 mt-2 text-center">
                                <i class="fas fa-info-circle text-[#7f5539] mr-1"></i>
                                Refer to this image for {{ $service->space_type }} layout before selecting.
                            </p>
                        </div>

                        <!-- Service Information -->
                        <div class="space-y-3">
                            <div class="flex items-center text-gray-700">
                                <i class="fas fa-store text-[#7f5539] mr-3 w-4"></i>
                                <div>
                                    <p class="text-xs text-gray-500">Branch</p>
                                    <p class="text-sm font-medium">{{ $branch->branch_name }}</p>
                                </div>
                            </div>

                            <div class="flex items-center text-gray-700">
                                <i class="fas fa-tag text-[#7f5539] mr-3 w-4"></i>
                                <div>
                                    <p class="text-xs text-gray-500">Category</p>
                                    <p class="text-sm font-medium">{{ $serviceCategory->service_category }}</p>
                                </div>
                            </div>

                            <div class="flex items-center text-gray-700">
                                <i class="fas fa-clock text-[#7f5539] mr-3 w-4"></i>
                                <div>
                                    <p class="text-xs text-gray-500">Duration</p>
                                    <p class="text-sm font-medium">{{ $service->time_duration }}</p>
                                </div>
                            </div>

                            <div class="flex items-center text-gray-700">
                                <i class="fas fa-map-marker-alt text-[#7f5539] mr-3 w-4"></i>
                                <div>
                                    <p class="text-xs text-gray-500">Space Type</p>
                                    <p class="text-sm font-medium">{{ $service->space_type }}</p>
                                </div>
                            </div>

                            <div class="flex items-center text-gray-700">
                                <i class="fas fa-door-open text-[#7f5539] mr-3 w-4"></i>
                                <div>
                                    <p class="text-xs text-gray-500">Operating Hours</p>
                                    <p class="text-sm font-medium">{{ $openTimeFormatted }} -
                                        {{ $closeTimeFormatted }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Price Section -->
                        <div class="mt-5 pt-4 border-t border-[#e6ddd4]">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm text-gray-500">Base Price</p>
                                    <p class="text-lg font-bold text-[#4a3429]">
                                        ₱{{ number_format($service->price, 2) }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500">Per {{ $service->time_duration }}</p>
                                    <p class="text-xs text-[#7f5539]">All fees included</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Booking Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm border border-[#e6ddd4] p-5">
                        <h2 class="text-lg font-bold text-[#4a3429] mb-5 flex items-center">
                            <i class="fas fa-calendar-alt mr-2 text-[#7f5539]"></i>Booking Information
                        </h2>

                        <!-- Existing Bookings Info -->
                        <div id="existingBookingsInfo" class="hidden mb-5 existing-booking-info">
                            <div class="flex items-start">
                                <i class="fas fa-info-circle text-blue-500 mt-0.5 mr-2"></i>
                                <div>
                                    <p class="font-medium text-blue-800">Viewing Existing Bookings</p>
                                    <p class="text-sm text-blue-700 mt-1">
                                        Existing bookings for the selected date range are shown in <span
                                            class="font-medium text-purple-700">purple</span>.
                                        Click on them to see details.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Booking Form -->
                        <form id="bookingForm" action="{{ route('sub_three.home.booking.preview') }}" method="POST"
                            class="space-y-6">
                            @csrf

                            <!-- Hidden fields (same as original) -->
                            <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                            <input type="hidden" name="branch_uuid" value="{{ $branch->uuid }}">
                            <input type="hidden" name="branch_name" value="{{ $branch->branch_name }}">
                            <input type="hidden" name="branch_location" value="{{ $branch->location }}">
                            <input type="hidden" name="branch_open_time" value="{{ $branch->open_time }}">
                            <input type="hidden" name="branch_close_time" value="{{ $branch->close_time }}">

                            <input type="hidden" name="service_category_id" value="{{ $serviceCategory->id }}">
                            <input type="hidden" name="service_category_uuid" value="{{ $serviceCategory->uuid }}">
                            <input type="hidden" name="service_category_name"
                                value="{{ $serviceCategory->service_category }}">

                            <input type="hidden" name="service_name_id" value="{{ $service->id }}">
                            <input type="hidden" name="service_name_uuid" value="{{ $service->uuid }}">
                            <input type="hidden" name="service_name" value="{{ $service->service_name }}">
                            <input type="hidden" name="service_time_duration"
                                value="{{ $service->time_duration }}">
                            <input type="hidden" name="service_price" value="{{ $service->price }}">
                            <input type="hidden" name="service_space_type" value="{{ $service->space_type }}">

                            <!-- Dynamic fields -->
                            <input type="hidden" name="seat_id" id="hidden_seat_id">
                            <input type="hidden" name="seat_display_label" id="hidden_seat_display_label">
                            <input type="hidden" name="date_from" id="hidden_date_from">
                            <input type="hidden" name="date_to" id="hidden_date_to">
                            <input type="hidden" name="booking_time" id="hidden_booking_time">
                            <input type="hidden" name="end_time" id="hidden_end_time">
                            <input type="hidden" name="additional_hours" id="hidden_additional_hours"
                                value="0">
                            <input type="hidden" name="additional_minutes" id="hidden_additional_minutes"
                                value="0">
                            <input type="hidden" name="additional_price" id="hidden_additional_price"
                                value="0">
                            <input type="hidden" name="total_price" id="hidden_total_price"
                                value="{{ $service->price }}">
                            <input type="hidden" name="main_duration" id="hidden_main_duration"
                                value="{{ $defaultDuration }}">
                            <input type="hidden" name="total_duration" id="hidden_total_duration"
                                value="{{ $defaultDuration }}">

                            <!-- Extended time fields -->
                            <input type="hidden" name="extended_start_time" id="hidden_extended_start_time">
                            <input type="hidden" name="extended_end_time" id="hidden_extended_end_time">
                            <input type="hidden" name="extended_start_date" id="hidden_extended_start_date">
                            <input type="hidden" name="extended_end_date" id="hidden_extended_end_date">
                            <input type="hidden" name="extended_duration_total" id="hidden_extended_duration_total"
                                value="0">

                            <!-- Step 1: Date Selection -->
                            <div class="card p-4">
                                <h3 class="text-sm font-bold text-[#4a3429] mb-3 flex items-center">
                                    <span
                                        class="w-6 h-6 bg-[#7f5539] text-white rounded-full flex items-center justify-center mr-2 text-xs">1</span>
                                    Select Date Range
                                </h3>
                                <div class="datetime-group">
                                    <div class="relative">
                                        <label class="block text-xs text-gray-500 mb-1">From Date</label>
                                        <input type="text" id="fromDatePicker"
                                            class="w-full p-3 border border-[#d4c4b2] rounded-md focus:outline-none focus:ring-1 focus:ring-[#7f5539] focus:border-transparent text-sm"
                                            placeholder="Select start date" readonly>
                                        <i class="fas fa-calendar-alt absolute right-3 top-9 text-gray-400"></i>
                                    </div>
                                    <div class="relative">
                                        <label class="block text-xs text-gray-500 mb-1">To Date</label>
                                        <input type="text" id="toDatePicker"
                                            class="w-full p-3 border border-[#d4c4b2] rounded-md focus:outline-none focus:ring-1 focus:ring-[#7f5539] focus:border-transparent text-sm"
                                            placeholder="Select end date" readonly>
                                        <i class="fas fa-calendar-alt absolute right-3 top-9 text-gray-400"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2: Seat/Room Selection -->
                            <div id="seatsSection" class="hidden card p-4">
                                <h3 class="text-sm font-bold text-[#4a3429] mb-3 flex items-center">
                                    <span
                                        class="w-6 h-6 bg-[#9c6644] text-white rounded-full flex items-center justify-center mr-2 text-xs">2</span>
                                    Select {{ $actualSpaceType == 'room' ? 'Room' : 'Seat' }}
                                </h3>
                                <div class="p-3 bg-[#f5f0eb] rounded border border-[#e6ddd4]">
                                    @if ($seats->count() > 0)
                                        <p class="text-sm mb-3 text-gray-600">{{ $seats->count() }}
                                            {{ $actualSpaceType == 'room' ? 'room(s)' : 'seat(s)' }} available
                                        </p>
                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3" id="seatSelection">
                                            @foreach ($seats as $seat)
                                                <div class="seat-item">
                                                    <input type="radio" name="selected_seat"
                                                        id="seat_{{ $seat->id }}" value="{{ $seat->id }}"
                                                        class="hidden">
                                                    <label for="seat_{{ $seat->id }}"
                                                        class="block p-3 border-2 border-[#e6ddd4] rounded-md text-center cursor-pointer transition-all duration-200 hover:border-[#7f5539] hover:bg-[#f5f0eb] h-full flex flex-col justify-center">
                                                        <span class="font-medium text-[#4a3429]">
                                                            {{ $seat->display_label }}
                                                        </span>
                                                        <div class="mt-2">
                                                            <span
                                                                class="inline-block px-2 py-1 text-xs bg-[#dcfce7] text-[#166534] rounded-full">
                                                                Available
                                                            </span>
                                                        </div>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-yellow-600 text-sm">
                                            {{ $actualSpaceType == 'room' ? 'No rooms available' : 'No seats available' }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <!-- Step 3: Time Selection -->
                            <div id="timeRangeSection" class="hidden card p-4">
                                <h3 class="text-sm font-bold text-[#4a3429] mb-3 flex items-center">
                                    <span
                                        class="w-6 h-6 bg-[#b08968] text-white rounded-full flex items-center justify-center mr-2 text-xs">3</span>
                                    Select Time Range
                                </h3>

                                <!-- Start Time Selection -->
                                <div class="mb-4">
                                    <label class="block text-xs text-gray-500 mb-2">Start Time</label>
                                    <div id="startTimeSlotsContainer" class="space-y-3">
                                        <!-- Time slots will be populated here by JavaScript -->
                                    </div>
                                </div>

                                <!-- End Time Selection -->
                                <div>
                                    <label class="block text-xs text-gray-500 mb-2">End Time</label>
                                    <div id="endTimeSlotsContainer" class="space-y-3">
                                        <p class="text-gray-500 text-sm">Please select start time first</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 4: Extended Time (Optional) -->
                            <div id="extendedTimeSection" class="hidden card p-4">
                                <div class="flex justify-between items-center mb-3">
                                    <h3 class="text-sm font-bold text-[#4a3429] flex items-center">
                                        <span
                                            class="w-6 h-6 bg-[#7f5539] text-white rounded-full flex items-center justify-center mr-2 text-xs">4</span>
                                        Extended Time (Optional)
                                    </h3>
                                    <button type="button" id="removeExtendedTime"
                                        class="text-red-500 hover:text-red-700 text-sm">
                                        <i class="fas fa-times mr-1"></i> Remove
                                    </button>
                                </div>
                                <div class="p-4 bg-[#f5f0eb] rounded-lg border border-[#e6ddd4]">
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-[#4a3429] mb-3">Add Extra
                                            Time</label>
                                        <div class="extended-time-inputs">
                                            <!-- Hours Input -->
                                            <div>
                                                <label class="block text-xs text-gray-500 mb-2">Additional
                                                    Hours</label>
                                                <div class="quantity-input">
                                                    <button type="button" class="quantity-btn" id="decreaseHours">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <div class="quantity-display">
                                                        <span id="extendedHours">0</span> hours
                                                    </div>
                                                    <button type="button" class="quantity-btn" id="increaseHours">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <!-- Minutes Input -->
                                            <div>
                                                <label class="block text-xs text-gray-500 mb-2">Additional
                                                    Minutes</label>
                                                <div class="quantity-input">
                                                    <button type="button" class="quantity-btn" id="decreaseMinutes">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <div class="quantity-display">
                                                        <span id="extendedMinutes">0</span> minutes
                                                    </div>
                                                    <button type="button" class="quantity-btn" id="increaseMinutes">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center text-sm text-[#7f5539] bg-[#e6ddd4] p-2 rounded">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        <span>This will extend your booking duration beyond the default time.</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Add Extended Time Button -->
                            <div id="addTimeButtonContainer" class="pt-2">
                                <button type="button" id="addExtendedTime"
                                    class="flex items-center text-[#7f5539] hover:text-[#6b4f3c] transition duration-200 font-medium text-sm">
                                    <i class="fas fa-plus-circle mr-2"></i>
                                    Add Extended Time
                                </button>
                                <div id="cannotExtendMessage"
                                    class="hidden mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                                    <div class="flex items-start">
                                        <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5 mr-2"></i>
                                        <div>
                                            <p class="text-sm font-medium text-yellow-800">Cannot Add Extended Time</p>
                                            <p class="text-xs text-yellow-700">
                                                Your booking ends at branch closing time. Extended time cannot be added
                                                as the branch will be closed.
                                                Please select an earlier start time if you need longer duration.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Summary Section -->
                            <div id="bookingSummary" class="hidden card p-5">
                                <h3 class="font-bold text-[#4a3429] mb-4 text-lg flex items-center">
                                    <i class="fas fa-receipt mr-2 text-[#7f5539]"></i>Booking Summary
                                </h3>
                                <div class="space-y-3 text-sm">
                                    <!-- Main Booking -->
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Date Range:</span>
                                        <span id="summaryDateRange" class="font-medium text-[#4a3429]">-</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Main Time Range:</span>
                                        <span id="summaryMainTimeRange" class="font-medium text-[#4a3429]">-</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Main Duration:</span>
                                        <span id="summaryMainDuration"
                                            class="font-medium text-[#4a3429]">{{ $defaultDuration }} minutes</span>
                                    </div>
                                    <!-- Seat/Room -->
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">
                                            {{ $actualSpaceType == 'room' ? 'Room:' : 'Seat:' }}
                                        </span>
                                        <span id="summarySeat" class="font-medium text-[#4a3429]">-</span>
                                    </div>
                                    <!-- Extended Time Section -->
                                    <div id="extendedTimeSummary"
                                        class="hidden border-t border-[#e6ddd4] pt-3 mt-3 space-y-3">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Extended Date Range:</span>
                                            <span id="summaryExtendedDateRange"
                                                class="font-medium text-[#4a3429]">-</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Extended Time Range:</span>
                                            <span id="summaryExtendedTime" class="font-medium text-[#4a3429]">-</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Extended Duration:</span>
                                            <span id="summaryExtendedDuration"
                                                class="font-medium text-[#4a3429]">-</span>
                                        </div>
                                    </div>
                                    <!-- Price Breakdown -->
                                    <div class="border-t border-[#e6ddd4] pt-3 mt-3">
                                        <div class="flex justify-between mb-1">
                                            <span class="text-gray-600">Main Booking Price:</span>
                                            <span id="summaryMainPrice"
                                                class="font-medium text-[#4a3429]">₱{{ number_format($service->price, 2) }}</span>
                                        </div>
                                        <!-- Extended Price -->
                                        <div id="extendedPriceSummary" class="hidden">
                                            <div class="flex justify-between mb-1">
                                                <span class="text-gray-600">Extended Time Price:</span>
                                                <span id="summaryExtendedPrice"
                                                    class="font-medium text-[#4a3429]">-</span>
                                            </div>
                                        </div>
                                        <!-- Subtotal -->
                                        <div class="flex justify-between pt-2 border-t border-[#d4c4b2] mt-2">
                                            <span class="text-[#4a3429] font-bold">Subtotal:</span>
                                            <span id="summarySubtotal"
                                                class="font-bold text-[#7f5539]">₱{{ number_format($service->price, 2) }}</span>
                                        </div>
                                    </div>
                                    <!-- Total Price -->
                                    <div class="flex justify-between pt-3 border-t border-[#e6ddd4]">
                                        <span class="text-[#4a3429] font-bold">Total:</span>
                                        <span id="summaryTotal"
                                            class="text-[#7f5539] font-bold text-lg">₱{{ number_format($service->price, 2) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t border-[#e6ddd4]">
                                <a href="{{ route('sub_three.home.service.category', [$branch->uuid, $serviceCategory->uuid]) }}"
                                    class="flex-1 px-6 py-3 border border-[#d4c4b2] text-[#4a3429] rounded-md hover:bg-[#f5f0eb] transition duration-200 text-center font-medium">
                                    <i class="fas fa-arrow-left mr-2"></i>Back to Services
                                </a>
                                <button type="submit" id="submitBtn"
                                    class="flex-1 px-6 py-3 bg-[#7f5539] text-white rounded-md hover:bg-[#6b4f3c] transition duration-200 font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fas fa-calendar-check mr-2"></i>Preview Booking
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Duration Exceeded Modal -->
    <div id="durationModal" class="modal">
        <div class="modal-content">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-[#4a3429] mb-2">Duration Exceeded</h3>
                <p class="text-sm text-gray-500 mb-4" id="modalMessage">
                    The selected time range exceeds the maximum allowed duration for this service.
                </p>
                <div class="flex gap-3 justify-center">
                    <button type="button" id="modalConfirm"
                        class="px-4 py-2 bg-[#7f5539] text-white rounded-md hover:bg-[#6b4f3c]">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Exceeding Time Modal -->
    <div id="exceedingTimeModal" class="modal">
        <div class="modal-content">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-[#4a3429] mb-2">Exceeding Time</h3>
                <p class="text-sm text-gray-500 mb-4" id="exceedingTimeMessage">
                    The selected time exceeds branch operating hours.
                </p>
                <div class="flex gap-3 justify-center">
                    <button type="button" id="exceedingTimeConfirm"
                        class="px-4 py-2 bg-[#7f5539] text-white rounded-md hover:bg-[#6b4f3c]">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Conflict Modal -->
    <div id="bookingConflictModal" class="modal">
        <div class="modal-content">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-ban text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-[#4a3429] mb-2">Booking Unavailable</h3>
                <p class="text-sm text-gray-500 mb-4">
                    This booking information is already selected by another user.<br>
                    Kindly select another booking information.
                </p>
                <div class="flex gap-3 justify-center">
                    <button type="button" id="conflictModalOk"
                        class="px-4 py-2 bg-[#7f5539] text-white rounded-md hover:bg-[#6b4f3c]">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Time slots data from PHP - ensure it's properly formatted
            const timeSlots = @json($timeSlots);
            const defaultDuration = {{ $defaultDuration }};
            const spaceType = '{{ $actualSpaceType }}'; // 'room' or 'seat'
            let selectedStartTime = null;
            let selectedEndTime = null;
            let extendedHours = 0;
            let extendedMinutes = 0;
            let isRendering = false;
            let renderTimeout = null;
            let isAutoAdjustingDates = false;
            let lastNotifiedDates = null;
            let lastNotifiedExtendedDuration = 0;
            let isOvernightNotificationVisible = false;
            let lastOvernightInfoData = null;
            let isOvernightInfoVisible = false;
            let validationTimeout = null;

            // Modal elements
            const durationModal = document.getElementById('durationModal');
            const modalMessage = document.getElementById('modalMessage');
            const modalConfirm = document.getElementById('modalConfirm');
            
            // Conflict Modal elements
            const bookingConflictModal = document.getElementById('bookingConflictModal');
            const conflictModalOk = document.getElementById('conflictModalOk');

            // Get confirm booking button
            const confirmBookingBtn = document.querySelector('button[type="submit"]');
            const timeRangeSection = document.getElementById('timeRangeSection');
            const seatsSection = document.getElementById('seatsSection');

            // Extended time elements
            const extendedHoursDisplay = document.getElementById('extendedHours');
            const extendedMinutesDisplay = document.getElementById('extendedMinutes');
            const increaseHoursBtn = document.getElementById('increaseHours');
            const decreaseHoursBtn = document.getElementById('decreaseHours');
            const increaseMinutesBtn = document.getElementById('increaseMinutes');
            const decreaseMinutesBtn = document.getElementById('decreaseMinutes');

            // Initialize date pickers with proper configuration to prevent overlapping
            let fromDatePicker = null;
            let toDatePicker = null;

            function initializeDatePickers() {
                // Clear any existing instances
                const fromInput = document.getElementById('fromDatePicker');
                const toInput = document.getElementById('toDatePicker');

                // Get valid dates from timeSlots (only dates with slots are generated by PHP)
                const validDates = Object.keys(timeSlots);

                // Remove any existing Flatpickr instances
                if (fromInput._flatpickr) {
                    fromInput._flatpickr.destroy();
                }
                if (toInput._flatpickr) {
                    toInput._flatpickr.destroy();
                }

                // Clear input values
                fromInput.value = '';
                toInput.value = '';

                // Initialize from date picker
                fromDatePicker = flatpickr("#fromDatePicker", {
                    minDate: "today",
                    enable: validDates, // Only enable dates that are valid open days
                    dateFormat: "Y-m-d",
                    allowInput: true,
                    clickOpens: true,
                    onOpen: function(selectedDates, dateStr, instance) {
                        // Close the other picker when one opens
                        if (toDatePicker) {
                            toDatePicker.close();
                        }
                    },
                    onChange: function(selectedDates, dateStr, instance) {
                        if (!isAutoAdjustingDates) {
                            toDatePicker.set('minDate', dateStr);
                            handleDateSelection();
                            updateBookingSummary();
                            updateConfirmButton();
                        }
                    }
                });

                // Initialize to date picker
                toDatePicker = flatpickr("#toDatePicker", {
                    minDate: "today",
                    enable: validDates, // Only enable dates that are valid open days
                    dateFormat: "Y-m-d",
                    allowInput: true,
                    clickOpens: true,
                    onOpen: function(selectedDates, dateStr, instance) {
                        // Close the other picker when one opens
                        if (fromDatePicker) {
                            fromDatePicker.close();
                        }
                    },
                    onChange: function(selectedDates, dateStr, instance) {
                        if (!isAutoAdjustingDates) {
                            handleDateSelection();
                            updateBookingSummary();
                            updateConfirmButton();
                        }
                    }
                });
            }

            // Initialize date pickers on page load
            initializeDatePickers();

            // Reset all selections
            function resetAllSelections() {
                // Reset time selections
                selectedStartTime = null;
                selectedEndTime = null;

                // Reset extended time
                extendedHours = 0;
                extendedMinutes = 0;
                updateExtendedTimeDisplay();

                // Reset seat selection
                clearSeatSelection();

                // Clear time slots
                document.getElementById('startTimeSlotsContainer').innerHTML =
                    '<p class="text-gray-500 text-sm">Please select a ' +
                    '{{ $actualSpaceType === 'room' ? 'room' : 'seat' }}' + ' first</p>';
                document.getElementById('endTimeSlotsContainer').innerHTML =
                    '<p class="text-gray-500 text-sm">Please select start time first</p>';

                // Hide time range section
                document.getElementById('timeRangeSection').classList.add('hidden');

                // Hide extended time section
                document.getElementById('extendedTimeSection').classList.add('hidden');
                document.getElementById('addTimeButtonContainer').classList.remove('hidden');

                // Remove overnight notification
                lastNotifiedDates = null;
                lastNotifiedExtendedDuration = 0;
                isOvernightNotificationVisible = false;

                // Clear any validation messages
                removeExtendedTimeValidationMessage();
                const infoMessage = document.getElementById('extendedTimeInfoMessage');
                if (infoMessage) {
                    infoMessage.remove();
                }

                // Reset overnight tracking
                isOvernightInfoVisible = false;
                lastOvernightInfoData = null;
            }

            // Reset time range only
            function resetTimeRange() {
                // Reset time selections
                selectedStartTime = null;
                selectedEndTime = null;

                // Clear time slots
                document.getElementById('startTimeSlotsContainer').innerHTML =
                    '<p class="text-gray-500 text-sm">Please select a ' + '{{ $service->space_type }}' +
                    ' first</p>';
                document.getElementById('endTimeSlotsContainer').innerHTML =
                    '<p class="text-gray-500 text-sm">Please select start time first</p>';

                // Hide extended time section
                document.getElementById('extendedTimeSection').classList.add('hidden');
                document.getElementById('addTimeButtonContainer').classList.remove('hidden');
            }

            let lastFromDate = null;
            let lastToDate = null;

            // Handle date selection - show seats/rooms when dates are selected
            function handleDateSelection() {
                const fromDate = document.getElementById('fromDatePicker').value;
                const toDate = document.getElementById('toDatePicker').value;
                const seatsSection = document.getElementById('seatsSection');
                const timeRangeSection = document.getElementById('timeRangeSection');

                if (fromDate && toDate) {
                    // Reset all selections when date range changes
                    resetAllSelections();
                    seatsSection.classList.remove('hidden');

                    // Show existing bookings info if there are any
                    if (window.existingBookings && window.existingBookings.length > 0) {
                        document.getElementById('existingBookingsInfo').classList.remove('hidden');
                    }
                } else {
                    seatsSection.classList.add('hidden');
                    timeRangeSection.classList.add('hidden');
                    document.getElementById('existingBookingsInfo').classList.add('hidden');
                }

                // Clear existing bookings reference
                window.existingBookings = null;

                updateBookingSummary();
                updateConfirmButton();
            }

            // Extended time quantity handlers
            increaseHoursBtn.addEventListener('click', function() {
                extendedHours++;
                updateBookingSummary();
                updateExtendedTimeDisplay();
                debouncedValidateAndUpdateExtendedTime();
                updateConfirmButton();

                if (selectedStartTime && selectedEndTime) {
                    checkAndAdjustOvernightBooking();
                }

                updateHiddenFields();
            });

            decreaseHoursBtn.addEventListener('click', function() {
                if (extendedHours > 0) {
                    extendedHours--;
                    updateBookingSummary();
                    updateExtendedTimeDisplay();
                    debouncedValidateAndUpdateExtendedTime();
                    updateConfirmButton();

                    if (selectedStartTime && selectedEndTime) {
                        checkAndAdjustOvernightBooking();
                    }

                    updateHiddenFields();
                }
            });

            increaseMinutesBtn.addEventListener('click', function() {
                if (extendedMinutes < 45) {
                    extendedMinutes += 15;
                } else {
                    extendedMinutes = 0;
                    extendedHours++;
                }
                updateBookingSummary();
                updateExtendedTimeDisplay();
                debouncedValidateAndUpdateExtendedTime();
                updateConfirmButton();

                if (selectedStartTime && selectedEndTime) {
                    checkAndAdjustOvernightBooking();
                }

                updateHiddenFields();
            });

            decreaseMinutesBtn.addEventListener('click', function() {
                if (extendedMinutes > 0) {
                    extendedMinutes -= 15;
                } else if (extendedHours > 0) {
                    extendedHours--;
                    extendedMinutes = 45;
                }
                updateBookingSummary();
                updateExtendedTimeDisplay();
                debouncedValidateAndUpdateExtendedTime();
                updateConfirmButton();

                if (selectedStartTime && selectedEndTime) {
                    checkAndAdjustOvernightBooking();
                }

                updateHiddenFields();
            });

            // Debounced validation function
            function debouncedValidateAndUpdateExtendedTime() {
                // Clear any existing timeout
                if (validationTimeout) {
                    clearTimeout(validationTimeout);
                }

                // Set new timeout
                validationTimeout = setTimeout(() => {
                    validateAndUpdateExtendedTime();
                }, 300); // 300ms delay
            }

            function updateExtendedTimeDisplay() {
                extendedHoursDisplay.textContent = extendedHours;
                extendedMinutesDisplay.textContent = extendedMinutes;

                decreaseHoursBtn.disabled = extendedHours === 0;
                decreaseMinutesBtn.disabled = extendedHours === 0 && extendedMinutes === 0;
            }

            // Check if booking exceeds into next day and adjust dates accordingly
            function checkAndAdjustOvernightBooking(startTime) {
                if (!startTime || !selectedStartTime) return false;

                const fromDate = document.getElementById('fromDatePicker').value;
                const toDateInput = document.getElementById('toDatePicker');
                const currentToDate = toDateInput.value;

                if (!fromDate || !currentToDate) return false;

                // Calculate expected end time based on start time and duration
                const startDateTime = new Date(selectedStartTime.datetime);
                const expectedEndDateTime = new Date(startDateTime.getTime() + (defaultDuration * 60 * 1000));

                // Check if booking extends to next day
                const startDateLocal = startDateTime.toLocaleDateString('en-CA');
                const expectedEndDateLocal = expectedEndDateTime.toLocaleDateString('en-CA');
                const wouldExtendOvernight = startDateLocal !== expectedEndDateLocal;

                // Get branch data for the start date
                const branchData = timeSlots[startDateLocal];
                if (!branchData) return false;

                if (wouldExtendOvernight) {
                    // Booking extends overnight, adjust toDate to next day
                    const nextDay = new Date(fromDate);
                    nextDay.setDate(nextDay.getDate() + 1);
                    const nextDayFormatted = nextDay.toLocaleDateString('en-CA');

                    if (currentToDate !== nextDayFormatted) {
                        isAutoAdjustingDates = true;
                        toDatePicker.setDate(nextDayFormatted, true);
                        toDateInput.value = nextDayFormatted;
                        isAutoAdjustingDates = false;
                        isOvernightBooking = true;
                    }
                } else if (isOvernightBooking) {
                    // Booking no longer extends overnight, reset to original date
                    isAutoAdjustingDates = true;
                    toDatePicker.setDate(fromDate, true);
                    toDateInput.value = fromDate;
                    isAutoAdjustingDates = false;
                    isOvernightBooking = false;
                }

                return wouldExtendOvernight;
            }

            // Update confirm button state
            function updateConfirmButton() {
                const fromDate = document.getElementById('fromDatePicker').value;
                const toDate = document.getElementById('toDatePicker').value;
                const selectedSeat = document.querySelector('input[name="selected_seat"]:checked');
                const hasExtendedTime = !document.getElementById('extendedTimeSection').classList.contains(
                    'hidden');

                let isValid = true;

                // Check main booking requirements
                if (!fromDate || !toDate || !selectedSeat || !selectedStartTime || !selectedEndTime) {
                    isValid = false;
                }

                // Check extended time requirements if extended time section is visible
                if (hasExtendedTime && (extendedHours === 0 && extendedMinutes === 0)) {
                    isValid = false;
                }

                if (isValid) {
                    confirmBookingBtn.disabled = false;
                    confirmBookingBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    confirmBookingBtn.classList.add('hover:bg-blue-700');
                } else {
                    confirmBookingBtn.disabled = true;
                    confirmBookingBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    confirmBookingBtn.classList.remove('hover:bg-blue-700');
                }
            }

            // Check if a time slot is in the past
            function isTimeSlotInPast(dateKey, timeValue) {
                const now = new Date();
                const currentDate = now.toLocaleDateString('en-CA');

                // If the date is not today, it's not in the past
                if (dateKey !== currentDate) {
                    return false;
                }

                // Parse the time value (HH:MM format)
                const [hours, minutes] = timeValue.split(':').map(Number);
                const slotTime = new Date();
                slotTime.setHours(hours, minutes, 0, 0);

                // Check if the slot time is before current time
                return slotTime < now;
            }

            // Render time slots based on selected dates
            function renderTimeSlots() {
                if (isRendering) return;
                isRendering = true;

                // Clear any pending render timeout
                if (renderTimeout) {
                    clearTimeout(renderTimeout);
                    renderTimeout = null;
                }

                const fromDate = document.getElementById('fromDatePicker').value;
                const toDate = document.getElementById('toDatePicker').value;

                const startContainer = document.getElementById('startTimeSlotsContainer');
                const endContainer = document.getElementById('endTimeSlotsContainer');

                selectedStartTime = null;
                selectedEndTime = null;

                document.getElementById('extendedTimeSection').classList.add('hidden');
                document.getElementById('addTimeButtonContainer').classList.remove('hidden');

                extendedHours = 0;
                extendedMinutes = 0;
                updateExtendedTimeDisplay();

                if (!fromDate || !toDate) {
                    startContainer.innerHTML =
                        '<p class="text-gray-500 text-sm">Please select date range first</p>';
                    endContainer.innerHTML = '<p class="text-gray-500 text-sm">Please select start time first</p>';
                    updateConfirmButton();
                    isRendering = false;
                    return;
                }

                renderDateTimeSlots(startContainer, fromDate, toDate, 'start');

                endContainer.innerHTML = '<p class="text-gray-500 text-sm">Please select start time first</p>';
                updateConfirmButton();

                // After rendering time slots, fetch and mark existing bookings
                setTimeout(() => {
                    fetchAndMarkExistingBookings();
                }, 100);

                isRendering = false;
            }

            function renderDateTimeSlots(container, fromDate, toDate, type) {
                // Don't clear the container immediately - we'll update slots while preserving structure
                const isSameDay = fromDate === toDate;

                if (!fromDate || !toDate) {
                    container.innerHTML = '<p class="text-gray-500 text-sm">Please select date range first</p>';
                    return;
                }

                let hasSlots = false;
                let allSlots = [];

                if (type === 'start') {
                    // For start time, ONLY show slots from the fromDate
                    if (timeSlots[fromDate] && timeSlots[fromDate].slots && timeSlots[fromDate].slots.length > 0) {
                        timeSlots[fromDate].slots.forEach(slot => {
                            if (slot.date_key === fromDate) {
                                allSlots.push({
                                    ...slot,
                                    date_key: fromDate,
                                    date_label: timeSlots[fromDate].label
                                });
                            }
                        });
                        hasSlots = true;
                    }
                }
                // For END time slots, determine which dates to show based on overnight duration
                else if (type === 'end' && selectedStartTime) {
                    // Check if the booking would extend overnight with default duration
                    const startDateTime = new Date(selectedStartTime.datetime);
                    const expectedEndDateTime = new Date(startDateTime.getTime() + (defaultDuration * 60 * 1000));

                    const startDateLocal = startDateTime.toLocaleDateString('en-CA');
                    const expectedEndDateLocal = expectedEndDateTime.toLocaleDateString('en-CA');

                    const wouldExtendOvernight = startDateLocal !== expectedEndDateLocal;

                    if (wouldExtendOvernight) {
                        // Booking extends overnight - show slots ONLY for the NEXT day (toDate)
                        if (timeSlots[toDate] && timeSlots[toDate].slots && timeSlots[toDate].slots.length > 0) {
                            timeSlots[toDate].slots.forEach(slot => {
                                if (slot.date_key === toDate) {
                                    allSlots.push({
                                        ...slot,
                                        date_key: toDate,
                                        date_label: timeSlots[toDate].label
                                    });
                                }
                            });
                            hasSlots = true;
                        }
                    } else {
                        // Booking does NOT extend overnight - show slots ONLY for the SAME day (fromDate)
                        
                        if (timeSlots[fromDate] && timeSlots[fromDate].slots && timeSlots[fromDate].slots.length >
                            0) {
                            timeSlots[fromDate].slots.forEach(slot => {
                                if (slot.date_key === fromDate) {
                                    allSlots.push({
                                        ...slot,
                                        date_key: fromDate,
                                        date_label: timeSlots[fromDate].label
                                    });
                                }
                            });
                            hasSlots = true;
                        }
                    }
                } else {
                    // For END time when no start time selected yet
                    container.innerHTML = '<p class="text-gray-500 text-sm">Please select start time first</p>';
                    return;
                }

                if (!hasSlots) {
                    container.innerHTML =
                        '<p class="text-gray-500 text-sm">No time slots available for selected date range</p>';
                    return;
                }

                const slotsByDate = {};
                allSlots.forEach(slot => {
                    if (!slotsByDate[slot.date_key]) {
                        slotsByDate[slot.date_key] = [];
                    }
                    slotsByDate[slot.date_key].push(slot);
                });

                // Clear container
                container.innerHTML = '';

                // Use document fragment for batch DOM updates
                const fragment = document.createDocumentFragment();

                Object.keys(slotsByDate).sort().forEach(dateKey => {
                    const dateSlots = slotsByDate[dateKey];
                    const dateLabel = timeSlots[dateKey] ? timeSlots[dateKey].label : new Date(dateKey +
                        'T00:00:00').toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric',
                        timeZone: 'UTC'
                    });

                    const dateSection = document.createElement('div');
                    dateSection.className = 'date-section';

                    const dateHeader = document.createElement('div');
                    dateHeader.className = 'date-header';
                    dateHeader.textContent = dateLabel;

                    const timeSlotsGrid = document.createElement('div');
                    timeSlotsGrid.className = 'time-slots-grid';

                    let currentPeriodType = null;
                    let hasSlotsInThisDate = false;
                    let hasAddedClosedHeader = false;

                    dateSlots.forEach(slot => {
                        // Check for period type change
                        const periodChanged = currentPeriodType !== slot.period_type;
                        currentPeriodType = slot.period_type;

                        // Add header/label for period changes
                        if (periodChanged && slot.period_type !== 'closing_time') {
                            if (!slot.available) {
                                // For break/closed periods
                                if (!hasAddedClosedHeader) {
                                    const closedLabel = document.createElement('div');
                                    closedLabel.className = 'closed-hours-label';
                                    closedLabel.textContent = slot.period_label || 'Branch Closed';
                                    closedLabel.style.gridColumn = '1 / -1';
                                    timeSlotsGrid.appendChild(closedLabel);
                                    hasAddedClosedHeader = true;
                                }
                            } else if (slot.period_label && slot.period_type !== 'open') {
                                // For other period types
                                const periodLabel = document.createElement('div');
                                periodLabel.className = 'period-label';

                                if (slot.period_type === 'day') {
                                    periodLabel.textContent = slot.period_label ||
                                        "Available Hours";
                                    periodLabel.style.backgroundColor = '#dcfce7';
                                    periodLabel.style.color = '#166534';
                                } else if (slot.period_type === 'overnight') {
                                    periodLabel.textContent = slot.period_label;
                                    periodLabel.style.backgroundColor = '#dbeafe';
                                    periodLabel.style.color = '#1e40af';
                                }

                                periodLabel.style.gridColumn = '1 / -1';
                                timeSlotsGrid.appendChild(periodLabel);
                            }
                        }

                        const isPast = isTimeSlotInPast(slot.date_key, slot.value);

                        if (type === 'start') {
                            // For start time, skip closing_time slots AND past slots
                            if (slot.period_type !== 'closing_time' && slot.available && !isPast) {
                                createTimeSlotButton(timeSlotsGrid, slot, type, isSameDay);
                                hasSlotsInThisDate = true;
                            }
                        } else {
                            // For end time, include closing_time slots (but not past slots)
                            if (slot.available && !isPast) {
                                createTimeSlotButton(timeSlotsGrid, slot, type, isSameDay);
                                hasSlotsInThisDate = true;
                            }
                        }
                    });

                    if (hasSlotsInThisDate) {
                        dateSection.appendChild(dateHeader);
                        dateSection.appendChild(timeSlotsGrid);
                        fragment.appendChild(dateSection);
                    }
                });

                // Append everything at once
                container.appendChild(fragment);
            }

            async function fetchAndMarkExistingBookings() {
                const fromDate = document.getElementById('fromDatePicker').value;
                const toDate = document.getElementById('toDatePicker').value;
                const selectedSeat = document.querySelector('input[name="selected_seat"]:checked');

                if (!fromDate || !toDate || !selectedSeat) {
                    return;
                }

                try {
                    const response = await fetch('{{ route('sub_three.home.api.get.existing.bookings') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            branch_id: {{ $branch->id }},
                            service_category_id: {{ $serviceCategory->id }},
                            service_name_id: {{ $service->id }},
                            seat_id: selectedSeat.value,
                            date_start: fromDate,
                            date_end: toDate
                        })
                    });

                    const existingBookings = await response.json();

                    // Store bookings globally for validation in other functions
                    window.existingBookingsData = existingBookings;

                    // Mark existing booking slots as unavailable
                    markExistingSlotsAsUnavailable(existingBookings);

                } catch (error) {
                }
            }

            // Function to mark existing booking slots as unavailable
            function markExistingSlotsAsUnavailable(existingBookings) {
                if (!existingBookings || existingBookings.length === 0) return;

                // Process each existing booking
                existingBookings.forEach(booking => {
                    // NEW: Check if end_time is missing. If so, block all slots for the start/end dates.
                    // This covers the requirement: "if it only contains values of start_time and date_start... time slots will turn red disabled"
                    if (!booking.end_time) {
                        markAllSlotsAsBooked(booking.date_start);
                        
                        // If date_end exists and is different, block that too
                        if (booking.date_end && booking.date_end !== booking.date_start) {
                            markAllSlotsAsBooked(booking.date_end);
                        }
                        
                        // Also check extended dates if they exist without end times, or just generally if the main booking has no end time
                        // assuming if main booking has no end time, it blocks everything. 
                        // If extended booking exists but has no extended_end_time
                        if (booking.extended_date_start && !booking.extended_end_time) {
                             markAllSlotsAsBooked(booking.extended_date_start);
                             if (booking.extended_date_end && booking.extended_date_end !== booking.extended_date_start) {
                                 markAllSlotsAsBooked(booking.extended_date_end);
                             }
                        }
                        return; // Skip standard range processing for this booking since we blocked the whole day
                    }
                    
                    // Also check specifically for extended time missing end time (if main time was present)
                    if (booking.extended_date_start && !booking.extended_end_time) {
                        markAllSlotsAsBooked(booking.extended_date_start);
                         if (booking.extended_date_end && booking.extended_date_end !== booking.extended_date_start) {
                             markAllSlotsAsBooked(booking.extended_date_end);
                         }
                         // Continue to process main time normally below, but we already handled the extension
                         // We can set a flag or just let the loop continue, but we need to ensure we don't error out on missing extended_end_time later
                    }

                    // Convert times to 24-hour format for comparison
                    const convertTo24Hour = (time12h) => {
                        if (!time12h) return null;

                        // If time is already in 24-hour format (HH:MM:SS)
                        if (/^\d{2}:\d{2}:\d{2}$/.test(time12h)) {
                            return time12h.substring(0, 5); // Return HH:MM
                        }

                        // If time is in 12-hour format (HH:MM AM/PM)
                        if (/^\d{1,2}:\d{2}\s?(AM|PM)$/i.test(time12h)) {
                            const [time, period] = time12h.split(/\s+/);
                            let [hours, minutes] = time.split(':');

                            hours = parseInt(hours);
                            minutes = parseInt(minutes);

                            if (period.toUpperCase() === 'PM' && hours < 12) {
                                hours += 12;
                            }
                            if (period.toUpperCase() === 'AM' && hours === 12) {
                                hours = 0;
                            }

                            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
                        }

                        return null;
                    };

                    // Main booking time slots
                    const mainStartTime24 = convertTo24Hour(booking.start_time);
                    const mainEndTime24 = convertTo24Hour(booking.end_time);

                    if (mainStartTime24 && mainEndTime24) {
                        markTimeSlotsAsBooked(booking.date_start, mainStartTime24, booking.date_end,
                            mainEndTime24, 'main');
                    }

                    // Extended booking time slots (if exists)
                    if (booking.extended_start_time && booking.extended_end_time) {
                        const extendedStartTime24 = convertTo24Hour(booking.extended_start_time);
                        const extendedEndTime24 = convertTo24Hour(booking.extended_end_time);
                        const extendedStartDate = booking.extended_date_start || booking.date_start;
                        const extendedEndDate = booking.extended_date_end || booking.date_end;

                        if (extendedStartTime24 && extendedEndTime24) {
                            markTimeSlotsAsBooked(
                                extendedStartDate,
                                extendedStartTime24,
                                extendedEndDate,
                                extendedEndTime24,
                                'extended'
                            );
                        }
                    }
                });
            }

            // Function to mark specific time slots as booked
            function markTimeSlotsAsBooked(startDateKey, startTime24, endDateKey, endTime24, type) {
                // Ensure dates are in YYYY-MM-DD format
                const formattedStartDateKey = new Date(startDateKey + 'T00:00:00').toLocaleDateString('en-CA');
                const formattedEndDateKey = new Date(endDateKey + 'T00:00:00').toLocaleDateString('en-CA');

                // Find all time slot buttons
                const allTimeSlots = document.querySelectorAll('.time-slot');

                // Convert times to minutes for comparison
                const startTimeMinutes = timeToMinutes(startTime24);
                const endTimeMinutes = timeToMinutes(endTime24);

                if (isNaN(startTimeMinutes) || isNaN(endTimeMinutes)) {
                    return;
                }

                let markedCount = 0;

                allTimeSlots.forEach(slot => {
                    const slotDate = slot.dataset.date;
                    const slotTime = slot.dataset.value; // This is in "HH:MM" format

                    if (slotDate) {
                        const slotDateFormatted = new Date(slotDate + 'T00:00:00').toLocaleDateString(
                            'en-CA');
                        const slotTimeMinutes = timeToMinutes(slotTime);

                        // Check if this slot falls within the booked date range and time range
                        if (slotDateFormatted >= formattedStartDateKey && slotDateFormatted <=
                            formattedEndDateKey) {
                            // For single day bookings
                            if (formattedStartDateKey === formattedEndDateKey) {
                                if (slotTimeMinutes >= startTimeMinutes && slotTimeMinutes <
                                    endTimeMinutes) {
                                    markSingleSlot(slot);
                                    markedCount++;
                                }
                            } else {
                                // For multi-day bookings
                                if (slotDateFormatted === formattedStartDateKey) {
                                    // First day - only mark slots after start time
                                    if (slotTimeMinutes >= startTimeMinutes) {
                                        markSingleSlot(slot);
                                        markedCount++;
                                    }
                                } else if (slotDateFormatted === formattedEndDateKey) {
                                    // Last day - only mark slots before end time
                                    if (slotTimeMinutes < endTimeMinutes) {
                                        markSingleSlot(slot);
                                        markedCount++;
                                    }
                                } else {
                                    // Middle days - mark all slots
                                    markSingleSlot(slot);
                                    markedCount++;
                                }
                            }
                        }
                    }
                });

            }

            // NEW: Helper function to mark ALL slots for a specific date as booked
            function markAllSlotsAsBooked(dateKey) {
                // Ensure date is in YYYY-MM-DD format
                const formattedDateKey = new Date(dateKey + 'T00:00:00').toLocaleDateString('en-CA');
                
                const allTimeSlots = document.querySelectorAll('.time-slot');
                
                allTimeSlots.forEach(slot => {
                    const slotDate = slot.dataset.date;
                    if (slotDate) {
                        const slotDateFormatted = new Date(slotDate + 'T00:00:00').toLocaleDateString('en-CA');
                        
                        // Check if this slot belongs to the target date
                        if (slotDateFormatted === formattedDateKey) {
                            markSingleSlot(slot);
                        }
                    }
                });
            }

            // Helper function to mark a single time slot
            function markSingleSlot(slot) {
                // Get the original label before modifying
                const originalLabel = slot.querySelector('.font-medium')?.textContent || slot.dataset.label ||
                    'Time Slot';

                // Remove any existing pseudo-element styles
                slot.style.backgroundImage = 'none';
                slot.style.position = 'relative';

                // Mark as booked/unavailable
                slot.disabled = true;
                slot.classList.add('booked-slot');
                slot.classList.remove('available-slot', 'hover:bg-gray-50', 'hover:border-blue-400');

                // Preserve the button structure but change content
                slot.innerHTML = `
        <span class="font-medium">${originalLabel}</span>
        <span class="text-xs mt-1 text-red-500 font-bold">
            <i class="fas fa-ban mr-1"></i>Booked
        </span>
    `;

                // Remove click event
                slot.onclick = null;
            }

            // Helper function to convert "HH:MM" to minutes
            function timeToMinutes(timeStr) {
                if (!timeStr) return NaN;

                // Handle both "HH:MM" and "HH:MM:SS" formats
                const parts = timeStr.split(':');
                if (parts.length < 2) return NaN;

                const hours = parseInt(parts[0], 10);
                const minutes = parseInt(parts[1], 10);

                if (isNaN(hours) || isNaN(minutes)) return NaN;

                return (hours * 60) + minutes;
            }

            // Add this CSS for booked slots
            const bookedSlotStyle = document.createElement('style');
            bookedSlotStyle.textContent = `
    .booked-slot {
        background-color: #fef2f2 !important;
        border-color: #fecaca !important;
        color: #991b1b !important;
        cursor: not-allowed !important;
        opacity: 0.7 !important;
    }
    
    .booked-slot:hover {
        background-color: #fef2f2 !important;
        border-color: #fecaca !important;
        transform: none !important;
    }
    
    .booked-slot::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, transparent 49%, #fca5a5 49%, #fca5a5 51%, transparent 51%);
        background-size: 4px 4px;
        opacity: 0.3;
    }
`;
            document.head.appendChild(bookedSlotStyle);

            // Update the createTimeSlotButton function to check for closing time issues
            function createTimeSlotButton(container, slot, type, isSameDay) {
                // DON'T create closing_time slots for START time selection
                if (type === 'start' && slot.period_type === 'closing_time') {
                    return; // Skip creating this button for start time
                }

                const timeButton = document.createElement('button');
                timeButton.type = 'button';

                // Check if slot is already marked as booked
                const isBooked = slot.classList && slot.classList.contains('booked-slot');

                let baseClasses =
                    'time-slot border rounded-md px-3 py-2 text-sm font-medium transition-all duration-200 flex flex-col items-center justify-center';

                // Check if this start time would exceed closing (only for START time slots)
                let wouldExceedClosing = false;
                if (type === 'start') {
                    wouldExceedClosing = checkWouldExceedClosing(slot.value, slot.date_key);
                }

                if (isBooked) {
                    // Don't create button for booked slots
                    return;
                }

                // SPECIAL HANDLING FOR CLOSING TIME (only for END time)
                if (slot.period_type === 'closing_time') {
                    // This will only be called for END time (since we returned early for START time)
                    baseClasses += ' closing_time-slot bg-gray-800 text-white';
                    timeButton.className = baseClasses + ' hover:bg-gray-700 hover:border-gray-500';
                    timeButton.disabled = false;
                }
                // Handle START time slots that would exceed closing
                else if (type === 'start' && slot.available && wouldExceedClosing) {
                    // Start time would exceed closing - make it more obvious
                    baseClasses += ` ${slot.period_type ? slot.period_type + '-slot' : ''}`;
                    timeButton.className = baseClasses +
                        ' bg-red-50 border-red-300 text-red-700 hover:bg-red-100 hover:border-red-400 cursor-not-allowed';
                    timeButton.disabled = true;
                    timeButton.title =
                        'This start time would extend past branch closing hours with the selected duration';

                    // Add strong warning icon
                    const warningIcon = document.createElement('span');
                    warningIcon.className = 'text-red-500 text-xs mt-1';
                    warningIcon.innerHTML = '<i class="fas fa-ban"></i>';
                    warningIcon.title = 'Exceeds closing time with current duration';
                    timeButton.appendChild(warningIcon);
                } else if (slot.period_type) {
                    baseClasses += ` ${slot.period_type}-slot`;

                    // For other period types
                    if (slot.available) {
                        timeButton.className = baseClasses +
                            ' bg-white border-gray-300 text-gray-700 hover:bg-gray-50 hover:border-blue-400';
                        timeButton.disabled = false;
                    } else {
                        timeButton.className = baseClasses +
                            ' bg-gray-100 border-gray-200 text-gray-400 cursor-not-allowed';
                        timeButton.disabled = true;
                    }
                } else {
                    // Default styling
                    if (slot.available) {
                        timeButton.className = baseClasses +
                            ' bg-white border-gray-300 text-gray-700 hover:bg-gray-50 hover:border-blue-400';
                        timeButton.disabled = false;
                    } else {
                        timeButton.className = baseClasses +
                            ' bg-gray-100 border-gray-200 text-gray-400 cursor-not-allowed';
                        timeButton.disabled = true;
                    }
                }

                const timeLabel = document.createElement('span');
                timeLabel.className = 'font-medium';
                timeLabel.textContent = slot.label;

                timeButton.appendChild(timeLabel);

                if (!isSameDay || type === 'end') {
                    const dateLabelSmall = document.createElement('span');
                    dateLabelSmall.className = 'text-xs mt-1';
                    dateLabelSmall.textContent = slot.date_label;
                    timeButton.appendChild(dateLabelSmall);
                }

                // Don't show period indicator for closing_time to avoid duplicate labels
                if (slot.period_type && slot.period_type !== 'closing_time' && slot.period_type !== 'open') {
                    const periodIndicator = document.createElement('span');
                    periodIndicator.className = `period-indicator period-${slot.period_type}`;
                    periodIndicator.textContent = slot.period_type.charAt(0).toUpperCase() + slot.period_type.slice(
                        1);
                    timeButton.appendChild(periodIndicator);
                }

                // Add special icon for closing time (only for end time)
                if (slot.period_type === 'closing_time') {
                    const closingIcon = document.createElement('span');
                    closingIcon.className = 'text-gray-300 text-xs mt-1';
                    closingIcon.innerHTML = '<i class="fas fa-door-closed"></i>';
                    closingIcon.title = 'Closing time - available as end time only';
                    timeButton.appendChild(closingIcon);
                }

                timeButton.dataset.value = slot.value;
                timeButton.dataset.date = slot.date_key;
                timeButton.dataset.timestamp = slot.timestamp;
                timeButton.dataset.available = slot.available;
                timeButton.dataset.dateLabel = slot.date_label;
                timeButton.dataset.periodType = slot.period_type;
                timeButton.dataset.wouldExceedClosing = wouldExceedClosing;
                timeButton.dataset.label = slot.label;

                timeButton.addEventListener('click', function() {
                    if (!this.disabled) {
                        // Special handling for closing time - only allowed as end time
                        if (this.dataset.periodType === 'closing_time' && type === 'end') {
                            handleTimeSlotSelection(this, type, slot.date_key, slot.value, slot.timestamp,
                                slot.date_label);
                        } else if (this.dataset.available === 'true') {
                            handleTimeSlotSelection(this, type, slot.date_key, slot.value, slot.timestamp,
                                slot.date_label);
                        }
                    }
                });

                container.appendChild(timeButton);
            }

            // Helper function to check if booking would end exactly at closing
            function checkWouldEndExactlyAtClosing(startTime, date) {
                const branchData = timeSlots[date];
                if (!branchData || !branchData.close_time) return false;

                const closeTime = branchData.close_time;
                const isOvernight = branchData.is_overnight;

                // Parse closing time
                const closeTimeParts = closeTime.split(':');
                if (closeTimeParts.length < 2) return false;

                const closeHours = parseInt(closeTimeParts[0]);
                const closeMinutes = parseInt(closeTimeParts[1]);

                if (isNaN(closeHours) || isNaN(closeMinutes)) return false;

                // Calculate when booking would end
                const startDateTime = new Date(date + 'T' + startTime + ':00');
                const bookingEndTime = new Date(startDateTime.getTime() + (defaultDuration * 60 * 1000));

                // Calculate branch closing time
                let branchCloseTime = new Date(startDateTime);

                if (isOvernight) {
                    const startHour = startDateTime.getHours();
                    const startMinute = startDateTime.getMinutes();

                    if (startHour < closeHours || (startHour === closeHours && startMinute < closeMinutes)) {
                        branchCloseTime.setHours(closeHours, closeMinutes, 0, 0);
                    } else {
                        branchCloseTime.setDate(branchCloseTime.getDate() + 1);
                        branchCloseTime.setHours(closeHours, closeMinutes, 0, 0);
                    }
                } else {
                    branchCloseTime.setHours(closeHours, closeMinutes, 0, 0);
                }

                return bookingEndTime.getTime() === branchCloseTime.getTime();
            }

            // Helper function to check if booking would exceed closing
            function checkWouldExceedClosing(startTime, date) {
                const branchData = timeSlots[date];
                if (!branchData || !branchData.close_time) return false;

                const closeTime = branchData.close_time;
                const isOvernight = branchData.is_overnight;

                // Parse closing time
                const closeTimeParts = closeTime.split(':');
                if (closeTimeParts.length < 2) return false;

                const closeHours = parseInt(closeTimeParts[0]);
                const closeMinutes = parseInt(closeTimeParts[1]);

                if (isNaN(closeHours) || isNaN(closeMinutes)) return false;

                // Calculate when booking would end
                const startDateTime = new Date(date + 'T' + startTime + ':00');
                const bookingEndTime = new Date(startDateTime.getTime() + (defaultDuration * 60 * 1000));

                // Calculate branch closing time
                let branchCloseTime = new Date(startDateTime);

                if (isOvernight) {
                    const startHour = startDateTime.getHours();
                    const startMinute = startDateTime.getMinutes();

                    if (startHour < closeHours || (startHour === closeHours && startMinute < closeMinutes)) {
                        branchCloseTime.setHours(closeHours, closeMinutes, 0, 0);
                    } else {
                        branchCloseTime.setDate(branchCloseTime.getDate() + 1);
                        branchCloseTime.setHours(closeHours, closeMinutes, 0, 0);
                    }
                } else {
                    branchCloseTime.setHours(closeHours, closeMinutes, 0, 0);
                }

                return bookingEndTime.getTime() > branchCloseTime.getTime();
            }

            // Show warning for start times that would extend past closing
            function showOvernightWarningForStartTime(button, type, date, time, timestamp, dateLabel) {
                const startDateTime = new Date(date + 'T' + time + ':00');
                const branchData = timeSlots[date];

                if (!branchData) return;

                const closeTime = branchData.close_time;
                const [closeHours, closeMinutes] = closeTime.split(':').map(Number);

                // Calculate when the branch actually closes
                const branchCloseTime = new Date(startDateTime);
                branchCloseTime.setDate(branchCloseTime.getDate() + 1); // Next day
                branchCloseTime.setHours(closeHours, closeMinutes, 0, 0);

                // Calculate booking end time with default duration
                const bookingEndTime = new Date(startDateTime.getTime() + (defaultDuration * 60 * 1000));

                // Format times for display
                const startTimeFormatted = startDateTime.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                const endTimeFormatted = bookingEndTime.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                const branchCloseFormatted = branchCloseTime.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });

                // Show confirmation modal
                if (confirm(`⚠️ Overnight Booking Warning\n\n` +
                        `Starting at ${startTimeFormatted} would extend your booking until ${endTimeFormatted}.\n` +
                        `This extends past branch closing time (${branchCloseFormatted}).\n\n` +
                        `You will need to:\n` +
                        `1. Book extended time to cover the gap\n` +
                        `2. Or adjust your booking duration\n\n` +
                        `Do you want to proceed with this start time?`)) {
                    // User confirmed, proceed with selection
                    handleTimeSlotSelection(button, type, date, time, timestamp, dateLabel);
                }
            }

            // Add this JavaScript function to update hidden fields
            function updateHiddenFields() {
                // Update date fields
                document.getElementById('hidden_date_from').value = document.getElementById('fromDatePicker').value;
                document.getElementById('hidden_date_to').value = document.getElementById('toDatePicker').value;

                // Update seat fields
                const selectedSeat = document.querySelector('input[name="selected_seat"]:checked');
                if (selectedSeat) {
                    const seatItem = selectedSeat.closest('.seat-item');
                    const seatLabel = seatItem.querySelector('.font-medium').textContent;
                    document.getElementById('hidden_seat_id').value = selectedSeat.value;
                    document.getElementById('hidden_seat_display_label').value = seatLabel;
                }

                // Update time fields
                if (selectedStartTime) {
                    document.getElementById('hidden_booking_time').value = selectedStartTime.time;
                }
                if (selectedEndTime) {
                    document.getElementById('hidden_end_time').value = selectedEndTime.time;
                }

                // Update extended time fields
                const hasExtendedTime = !document.getElementById('extendedTimeSection').classList.contains(
                    'hidden');
                if (hasExtendedTime && (extendedHours > 0 || extendedMinutes > 0)) {
                    document.getElementById('hidden_additional_hours').value = extendedHours;
                    document.getElementById('hidden_additional_minutes').value = extendedMinutes;

                    // Calculate extended time details
                    const extendedDuration = (extendedHours * 60) + extendedMinutes;
                    const mainDuration = getTimeDifferenceInMinutes(selectedStartTime.datetime, selectedEndTime
                        .datetime);
                    const totalDuration = mainDuration + extendedDuration;

                    document.getElementById('hidden_extended_duration_total').value = extendedDuration;
                    document.getElementById('hidden_total_duration').value = totalDuration;

                    // Calculate extended time start/end
                    if (selectedEndTime) {
                        const mainEndDateTime = new Date(selectedEndTime.datetime);
                        const extendedEndDateTime = new Date(mainEndDateTime.getTime() + (extendedDuration * 60 *
                            1000));

                        document.getElementById('hidden_extended_start_time').value = selectedEndTime.time;
                        document.getElementById('hidden_extended_start_date').value = selectedEndTime.date;
                        document.getElementById('hidden_extended_end_time').value = extendedEndDateTime
                            .toLocaleTimeString('en-US', {
                                hour12: false,
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                        document.getElementById('hidden_extended_end_date').value = extendedEndDateTime
                            .toLocaleDateString('en-CA');
                    }

                    // Calculate prices
                    const currentServicePrice = parseFloat({{ $service->price }}) || 0;
                    let extendedPrice = 0;
                    let totalPrice = currentServicePrice;

                    const upgradedPackage = findUpgradedPackage(extendedDuration);

                    if (upgradedPackage && upgradedPackage.price) {
                        // Package price (full amount)
                        totalPrice = upgradedPackage.price;
                        extendedPrice = upgradedPackage.price - currentServicePrice;
                    } else {
                        // Calculate extended time price
                        extendedPrice = calculateExtendedTimePriceDirect(extendedDuration);
                        totalPrice = currentServicePrice + extendedPrice;
                    }

                    document.getElementById('hidden_additional_price').value = extendedPrice;
                    document.getElementById('hidden_total_price').value = totalPrice;
                } else {
                    // Reset extended time fields
                    document.getElementById('hidden_additional_hours').value = 0;
                    document.getElementById('hidden_additional_minutes').value = 0;
                    document.getElementById('hidden_additional_price').value = 0;
                    document.getElementById('hidden_total_price').value = {{ $service->price }};
                    document.getElementById('hidden_extended_duration_total').value = 0;
                    document.getElementById('hidden_total_duration').value = {{ $defaultDuration }};
                }
            }

            // Update hidden fields whenever relevant data changes
            document.querySelectorAll('input[name="selected_seat"]').forEach(input => {
                input.addEventListener('change', updateHiddenFields);
            });

            document.getElementById('fromDatePicker').addEventListener('change', updateHiddenFields);
            document.getElementById('toDatePicker').addEventListener('change', updateHiddenFields);

            // unction to check for booking overlaps
            function checkForBookingOverlap(startTime, duration) {
                const fromDate = document.getElementById('fromDatePicker').value;
                const selectedSeat = document.querySelector('input[name="selected_seat"]:checked');

                if (!selectedSeat || !fromDate) {
                    return {
                        overlap: false,
                        existingBookings: []
                    };
                }

                // Convert start time to timestamp for comparison
                const startDateTime = new Date(`${fromDate} ${startTime.time}:00`);
                const endDateTime = new Date(startDateTime.getTime() + (duration * 60 * 1000));

                // Get all existing bookings for this seat
                const existingBookings = getExistingBookingsForSeat(selectedSeat.value, fromDate);

                const overlappingBookings = existingBookings.filter(booking => {
                    const bookingStart = new Date(`${booking.date_start} ${booking.start_time}`);
                    const bookingEnd = new Date(`${booking.date_end} ${booking.end_time}`);

                    // Check for overlap
                    return (
                        (startDateTime >= bookingStart && startDateTime < bookingEnd) ||
                        // New start is within existing booking
                        (endDateTime > bookingStart && endDateTime <= bookingEnd) ||
                        // New end is within existing booking
                        (startDateTime <= bookingStart && endDateTime >=
                        bookingEnd) // New booking fully contains existing
                    );
                });

                return {
                    overlap: overlappingBookings.length > 0,
                    existingBookings: overlappingBookings
                };
            }

            // Function to get existing bookings for a specific seat
            function getExistingBookingsForSeat(seatId, date) {
                // This should return the bookings you fetched earlier
                // You'll need to store them in a variable when you fetch them
                return window.existingBookings || [];
            }

            // Handle time slot selection
            function handleTimeSlotSelection(button, type, date, time, timestamp, dateLabel) {
                const fullDateTime = `${date} ${time}`;

                const containerId = getContainerId(type);
                document.querySelectorAll(`#${containerId} .time-slot.selected`).forEach(el => {
                    el.classList.remove('selected');
                });

                button.classList.add('selected');

                const timeData = {
                    datetime: fullDateTime,
                    timestamp: timestamp,
                    date: date,
                    time: time,
                    dateLabel: dateLabel
                };

                switch (type) {
                    case 'start':
                        selectedStartTime = timeData;
                        // Clear any previous timeout
                        if (renderTimeout) {
                            clearTimeout(renderTimeout);
                        }
                        // Immediate render with proper date adjustment
                        renderTimeout = setTimeout(() => {
                            renderEndTimeSlots();
                        }, 50);
                        break;

                    case 'end':
                        selectedEndTime = timeData;

                        // Check if booking ends at closing time and update button/message
                        const endsAtClosing = checkIfBookingEndsAtClosing();
                        updateExtendedTimeButtonVisibility(endsAtClosing);

                        // Scroll to the manually selected end time
                        setTimeout(() => {
                            scrollToSelectedEndTime();
                        }, 100);

                        checkAndAdjustOvernightBooking();
                        break;
                }

                updateBookingSummary();
                updateConfirmButton();
                // Update hidden fields after time selection
                updateHiddenFields();
            }

            // Get container ID based on type
            function getContainerId(type) {
                switch (type) {
                    case 'start':
                        return 'startTimeSlotsContainer';
                    case 'end':
                        return 'endTimeSlotsContainer';
                    default:
                        return '';
                }
            }

            // Find the exact time slot that matches the duration
            function findExactDurationEndTime(startTimestamp, availableSlots) {
                const targetTimestamp = startTimestamp + (defaultDuration * 60);
                let exactMatch = null;
                let closestMatch = null;
                let minDifference = Infinity;

                availableSlots.forEach(slot => {
                    const slotTimestamp = parseInt(slot.dataset.timestamp);
                    const difference = Math.abs(slotTimestamp - targetTimestamp);

                    if (slotTimestamp === targetTimestamp) {
                        exactMatch = slot;
                    }

                    // Track closest match that doesn't cross closed hours
                    if (difference < minDifference && slotTimestamp > startTimestamp) {
                        minDifference = difference;
                        closestMatch = slot;
                    }
                });

                return exactMatch || closestMatch;
            }

            // Render end time slots based on selected start time with duration validation
            function renderEndTimeSlots() {
                if (!selectedStartTime) return;

                const container = document.getElementById('endTimeSlotsContainer');
                const fromDate = document.getElementById('fromDatePicker').value;
                const toDate = document.getElementById('toDatePicker').value;

                // Clear container first to show loading state
                container.innerHTML = '<p class="text-gray-500 text-sm">Loading end time slots...</p>';

                // Use a small delay to ensure DOM is ready
                setTimeout(() => {
                    renderDateTimeSlots(container, fromDate, toDate, 'end');

                    // Process the slots after rendering
                    setTimeout(() => {
                        processEndTimeSlotsAfterRender();
                    }, 100);
                }, 50);
            }

            function processEndTimeSlotsAfterRender() {
                const container = document.getElementById('endTimeSlotsContainer');
                const startTimestamp = selectedStartTime.timestamp;
                const endTimeSlots = document.querySelectorAll('#endTimeSlotsContainer .time-slot');
                const availableSlots = [];

                // NEW: Check for conflict with existing bookings immediately
                if (window.existingBookingsData && window.existingBookingsData.length > 0) {
                    const startTsMs = startTimestamp * 1000;
                    const projectedEndTsMs = startTsMs + (defaultDuration * 60 * 1000);
                    
                    let nearestBookingStartTs = Infinity;
                    let conflictingBooking = null;

                    window.existingBookingsData.forEach(booking => {
                        // Helper to parse booking time to timestamp (handles YYYY-MM-DD + HH:MM:SS/AM/PM)
                        const parseBookingTime = (dateStr, timeStr) => {
                            let time24 = timeStr;
                            if (/am|pm/i.test(timeStr)) {
                               const [t, period] = timeStr.split(/\s+/);
                               let [h, m] = t.split(':').map(Number);
                               if (period.toLowerCase() === 'pm' && h < 12) h += 12;
                               if (period.toLowerCase() === 'am' && h === 12) h = 0;
                               // Ensure seconds exist
                               if (t.split(':').length === 2) time24 = `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:00`;
                               else time24 = `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${t.split(':')[2]}`;
                            }
                            return new Date(`${dateStr}T${time24}`).getTime();
                        };
                        
                        try {
                            const bookingStartTs = parseBookingTime(booking.date_start, booking.start_time);
                            
                            // Find the nearest booking that starts AFTER our selected start time
                            // We use >= to catch bookings starting at the exact same time (though those should be disabled in start slots already)
                            if (bookingStartTs >= startTsMs) {
                                if (bookingStartTs < nearestBookingStartTs) {
                                    nearestBookingStartTs = bookingStartTs;
                                    conflictingBooking = booking;
                                }
                            }
                        } catch(e) {
                            console.error("Error parsing booking time", e);
                        }
                    });
                    
                    // If the projected end time exceeds the start of the next booking
                    if (projectedEndTsMs > nearestBookingStartTs) {
                         const messageDiv = document.createElement('div');
                         messageDiv.className = 'no-slots-message';
                         const nextBookingTimeStr = new Date(nearestBookingStartTs).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                         
                         messageDiv.innerHTML = `
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-red-500 mr-2 mt-0.5"></i>
                            <div>
                                <p class="font-medium">Exceeds Available Time</p>
                                <p class="text-sm mt-1">
                                    The selected start time with ${defaultDuration/60} hour duration would overlap with an existing booking starting at ${nextBookingTimeStr}.
                                    Please choose an earlier start time.
                                </p>
                            </div>
                        </div>
                        `;
                        
                        // Clear container (except for loading msg if any, but we rewrite it) and show error
                        // We need to keep the slots in DOM but disabled, or just clear and show error. 
                        // The pattern usually keeps slots but disables them.
                        // However, we need to insert the message at the top.
                        const existingMessages = container.querySelectorAll('.no-slots-message, .info-message');
                        existingMessages.forEach(msg => msg.remove());
                        
                        container.insertBefore(messageDiv, container.firstChild);
                        
                        // Disable ALL slots in the container
                        endTimeSlots.forEach(button => {
                            button.disabled = true;
                            button.classList.add('opacity-50', 'cursor-not-allowed');
                            button.classList.remove('hover:bg-gray-50', 'hover:border-blue-400', 'selected');
                        });
                        
                        selectedEndTime = null;
                        updateBookingSummary();
                        updateConfirmButton();
                        return; // Exit function early
                    }
                }

                // Get branch data for the start date
                const startDate = new Date(startTimestamp * 1000).toLocaleDateString('en-CA');
                const branchData = timeSlots[startDate];

                if (!branchData || !branchData.close_time) {
                    container.innerHTML =
                        '<p class="text-gray-500 text-sm">No branch closing time data available</p>';
                    selectedEndTime = null;
                    updateBookingSummary();
                    updateConfirmButton();
                    return;
                }

                const closeTime = branchData.close_time; // e.g., "07:00:00"
                const isOvernight = branchData.is_overnight;

                // Parse closing time from the branch data
                const closeTimeParts = closeTime.split(':');
                if (closeTimeParts.length < 2) {
                    container.innerHTML = '<p class="text-gray-500 text-sm">Invalid branch closing time format</p>';
                    selectedEndTime = null;
                    updateBookingSummary();
                    updateConfirmButton();
                    return;
                }

                const closeHours = parseInt(closeTimeParts[0]);
                const closeMinutes = parseInt(closeTimeParts[1]);

                // Validate parsed hours/minutes
                if (isNaN(closeHours) || isNaN(closeMinutes)) {
                    container.innerHTML = '<p class="text-gray-500 text-sm">Invalid branch closing time values</p>';
                    selectedEndTime = null;
                    updateBookingSummary();
                    updateConfirmButton();
                    return;
                }

                // Calculate branch closing time
                let branchCloseTime = new Date(startTimestamp * 1000);

                if (isOvernight) {
                    // For overnight operations (close at 7:00 AM, open at 11:00 AM)
                    const startHour = new Date(startTimestamp * 1000).getHours();
                    const startMinute = new Date(startTimestamp * 1000).getMinutes();

                    if (startHour < closeHours || (startHour === closeHours && startMinute < closeMinutes)) {
                        // Start time is in overnight period, closing time is today
                        branchCloseTime.setHours(closeHours, closeMinutes, 0, 0);
                    } else {
                        // Start time is in day period, closing time is tomorrow
                        branchCloseTime.setDate(branchCloseTime.getDate() + 1);
                        branchCloseTime.setHours(closeHours, closeMinutes, 0, 0);
                    }
                } else {
                    // For same-day operations
                    branchCloseTime.setHours(closeHours, closeMinutes, 0, 0);
                }

                // Calculate when booking would end with default duration
                const bookingEndTime = new Date(startTimestamp * 1000 + (defaultDuration * 60 * 1000));

                // Check if booking would end exactly at closing time
                const wouldEndExactlyAtClosing = bookingEndTime.getTime() === branchCloseTime.getTime();

                // Check if booking would extend past closing time
                const wouldExtendPastClosing = bookingEndTime > branchCloseTime;

                // Reset all slots first
                endTimeSlots.forEach(button => {
                    button.disabled = false;
                    button.classList.remove('opacity-50', 'cursor-not-allowed', 'selected');
                    button.classList.add('hover:bg-gray-50', 'hover:border-blue-400');
                });

                // Collect available slots
                endTimeSlots.forEach(button => {
                    const buttonTimestamp = parseInt(button.dataset.timestamp);
                    const isAvailable = button.dataset.available === 'true';

                    // Only consider slots that are after start time
                    if (buttonTimestamp > startTimestamp && isAvailable) {
                        availableSlots.push(button);
                    } else {
                        // Disable slots that are before start time or not available
                        button.disabled = true;
                        button.classList.add('opacity-50', 'cursor-not-allowed');
                        button.classList.remove('hover:bg-gray-50', 'hover:border-blue-400');
                    }
                });

                // Sort available slots by timestamp
                availableSlots.sort((a, b) => parseInt(a.dataset.timestamp) - parseInt(b.dataset.timestamp));

                // Remove any existing messages
                const existingMessages = container.querySelectorAll('.no-slots-message, .info-message');
                existingMessages.forEach(msg => msg.remove());

                // Find the LAST available slot before closing time (e.g., 6:45 AM)
                let lastAvailableSlotBeforeClosing = null;
                const closingTimestamp = branchCloseTime.getTime() / 1000;

                // Find slots before closing time
                for (let i = availableSlots.length - 1; i >= 0; i--) {
                    const slot = availableSlots[i];
                    const slotTimestamp = parseInt(slot.dataset.timestamp);

                    if (slotTimestamp < closingTimestamp) {
                        lastAvailableSlotBeforeClosing = slot;
                        break;
                    }
                }

                // Try to find the exact closing time slot (e.g., 7:00 AM)
                const closingTimestampExact = closingTimestamp;
                let exactClosingSlot = null; // CHANGED: Renamed from closingSlot to exactClosingSlot

                // Look through ALL slots (not just available ones) for the closing time
                endTimeSlots.forEach(button => {
                    const buttonTimestamp = parseInt(button.dataset.timestamp);
                    if (buttonTimestamp === closingTimestampExact) {
                        exactClosingSlot = button; // CHANGED: Using new variable name
                    }
                });

                if (wouldExtendPastClosing) {
                    if (wouldEndExactlyAtClosing) {
                        // Try to find the closing time slot
                        const closingTimeSlot = Array.from(endTimeSlots).find(button =>
                            button.dataset.periodType === 'closing_time' ||
                            (button.dataset.value ===
                                `${closeHours.toString().padStart(2, '0')}:${closeMinutes.toString().padStart(2, '0')}`
                            )
                        );

                        if (closingTimeSlot) {

                            // Enable and auto-select the closing time slot
                            closingTimeSlot.disabled = false;
                            closingTimeSlot.classList.remove('opacity-50', 'cursor-not-allowed');
                            closingTimeSlot.classList.add('selected');

                            // Disable all other slots
                            endTimeSlots.forEach(button => {
                                if (button !== closingTimeSlot) {
                                    button.disabled = true;
                                    button.classList.add('opacity-50', 'cursor-not-allowed');
                                    button.classList.remove('hover:bg-gray-50', 'hover:border-blue-400');
                                }
                            });

                            // Auto-select this as end time
                            selectedEndTime = {
                                datetime: `${closingTimeSlot.dataset.date} ${closingTimeSlot.dataset.value}`,
                                timestamp: parseInt(closingTimeSlot.dataset.timestamp),
                                date: closingTimeSlot.dataset.date,
                                time: closingTimeSlot.dataset.value,
                                dateLabel: closingTimeSlot.dataset.dateLabel
                            };

                            // Show info message
                            const messageDiv = document.createElement('div');
                            messageDiv.className = 'no-slots-message';
                            messageDiv.style.backgroundColor = '#f0f9ff';
                            messageDiv.style.borderColor = '#bae6fd';
                            messageDiv.style.color = '#0369a1';
                            messageDiv.innerHTML = `
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-500 mr-2 mt-0.5"></i>
                    <div>
                        <p class="font-medium">Booking Ends at Closing Time</p>
                        <p class="text-sm mt-1">
                            Your booking will end exactly at branch closing time (${formatTimeForDisplay(branchCloseTime)}).
                        </p>
                    </div>
                </div>
            `;
                            container.insertBefore(messageDiv, container.firstChild);

                            // Scroll to selected slot
                            setTimeout(() => {
                                closingTimeSlot.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'center'
                                });
                            }, 300);

                            setTimeout(() => {
                                const endsAtClosing = checkIfBookingEndsAtClosing();
                                updateExtendedTimeButtonVisibility(endsAtClosing);
                            }, 100);

                            updateBookingSummary();
                            updateConfirmButton();
                            return; // Exit early since we've handled this case
                        }
                    } else {
                        // BOOKING WOULD EXCEED CLOSING TIME - SHOW ERROR MESSAGE ONLY
                        // Don't auto-select any slot, just show the error

                        const messageDiv = document.createElement('div');
                        messageDiv.className = 'no-slots-message';
                        messageDiv.innerHTML = `
            <div class="flex items-start">
                <i class="fas fa-exclamation-triangle text-red-500 mr-2 mt-0.5"></i>
                <div>
                    <p class="font-medium">Exceeds Operating Hours</p>
                    <p class="text-sm mt-1">
                        The selected start time with ${defaultDuration/60} hour duration would extend past branch closing time (${formatTimeForDisplay(branchCloseTime)}).
                        Please choose an earlier start time or reduce your booking duration.
                    </p>
                </div>
            </div>
        `;
                        container.insertBefore(messageDiv, container.firstChild);

                        // Disable ALL available slots (don't auto-select anything)
                        availableSlots.forEach(button => {
                            button.disabled = true;
                            button.classList.add('opacity-50', 'cursor-not-allowed');
                            button.classList.remove('hover:bg-gray-50', 'hover:border-blue-400');
                        });

                        // Clear selected end time
                        selectedEndTime = null;

                        updateBookingSummary();
                        updateConfirmButton();
                        return; // Exit early
                    }
                } else if (availableSlots.length === 0) {
                    // No available slots at all
                    const messageDiv = document.createElement('div');
                    messageDiv.className = 'no-slots-message';
                    messageDiv.textContent = 'No time slots available for the selected duration.';
                    container.insertBefore(messageDiv, container.firstChild);
                    selectedEndTime = null;
                } else {
                    // Booking fits within operating hours - normal behavior
                    const recommendedEndTime = findExactDurationEndTime(startTimestamp, availableSlots);

                    if (recommendedEndTime) {
                        const recommendedEndTimestamp = parseInt(recommendedEndTime.dataset.timestamp);
                        const availableDuration = (recommendedEndTimestamp - startTimestamp) / 60;

                        if (availableDuration >= defaultDuration) {
                            // Auto-select the recommended end time
                            availableSlots.forEach(button => {
                                if (button !== recommendedEndTime) {
                                    button.disabled = true;
                                    button.classList.add('opacity-50', 'cursor-not-allowed');
                                    button.classList.remove('hover:bg-gray-50', 'hover:border-blue-400');
                                } else {
                                    button.disabled = false;
                                    button.classList.remove('opacity-50', 'cursor-not-allowed');
                                    button.classList.add('selected');
                                }
                            });

                            selectedEndTime = {
                                datetime: `${recommendedEndTime.dataset.date} ${recommendedEndTime.dataset.value}`,
                                timestamp: recommendedEndTimestamp,
                                date: recommendedEndTime.dataset.date,
                                time: recommendedEndTime.dataset.value,
                                dateLabel: recommendedEndTime.dataset.dateLabel
                            };

                            // Auto-scroll to the selected end time
                            setTimeout(() => {
                                scrollToSelectedEndTime();
                            }, 300);
                        } else {
                            // Not enough time available
                            if (lastAvailableSlotBeforeClosing) {
                                // Highlight the last available slot
                                setTimeout(() => {
                                    lastAvailableSlotBeforeClosing.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'center'
                                    });
                                }, 300);
                            }
                        }
                    } else {
                        // Let user choose manually
                        if (availableSlots.length > 0) {
                            const latestSlot = availableSlots[availableSlots.length - 1];
                            setTimeout(() => {
                                latestSlot.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'center'
                                });
                            }, 300);
                        }
                    }
                }

                updateBookingSummary();
                updateConfirmButton();
            }

            // Add this helper function
            function formatTimeForDisplay(date) {
                return date.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });
            }

            // Flag at the top with other variables


            // Function to handle overnight duration adjustment
            function handleOvernightDurationAdjustment(startTimestamp, requiredDuration) {
                const fromDate = document.getElementById('fromDatePicker').value;
                const toDate = document.getElementById('toDatePicker').value;

                // Calculate the expected end time including duration
                const startDateTime = new Date(startTimestamp * 1000);
                const expectedEndDateTime = new Date(startDateTime.getTime() + (requiredDuration * 60 * 1000));

                const startDateLocal = startDateTime.toLocaleDateString('en-CA');
                const expectedEndDateLocal = expectedEndDateTime.toLocaleDateString('en-CA');

                let wasAdjusted = false;

                // If booking would extend to next day AND we're currently on single day booking
                if (startDateLocal !== expectedEndDateLocal && fromDate === toDate) {

                    // Set the flag to prevent unwanted resets
                    isAutoAdjustingDates = true;

                    // Update the toDate picker to include the next day
                    toDatePicker.setDate(expectedEndDateLocal, true);

                    // Show notification to user
                    showOvernightAdjustmentNotification(fromDate, expectedEndDateLocal);

                    wasAdjusted = true;

                    // Reset the flag after a short delay
                    setTimeout(() => {
                        isAutoAdjustingDates = false;
                    }, 500);
                }
                // If booking would NOT extend overnight AND we have multi-day selection, reset to single day
                else if (startDateLocal === expectedEndDateLocal && fromDate !== toDate) {

                    // Reset toDate to be same as fromDate
                    toDatePicker.setDate(startDateLocal, true);

                    wasAdjusted = true;

                    // Reset the flag after a short delay
                    setTimeout(() => {
                        isAutoAdjustingDates = false;
                    }, 500);
                }

                return wasAdjusted;
            }

            // Add this function to debug the available time slots
            function checkAvailableTimeSlots() {
                const startDate = selectedStartTime ? new Date(selectedStartTime.timestamp * 1000)
                    .toLocaleDateString('en-CA') : null;
                if (!startDate || !timeSlots[startDate]) return;

                const branchData = timeSlots[startDate];

                // Find 7:00 AM slot
                const sevenAMSlot = branchData.slots ? branchData.slots.find(slot =>
                    slot.value.startsWith('07:00')
                ) : null;

                // Show all available slots
                if (branchData.slots) {
                    const availableSlots = branchData.slots.filter(s => s.available);
                }
            }

            // Scrolling when user manually selects an end time
            function handleTimeSlotSelection(button, type, date, time, timestamp, dateLabel) {

                const fullDateTime = `${date} ${time}`;

                const containerId = getContainerId(type);
                document.querySelectorAll(`#${containerId} .time-slot.selected`).forEach(el => {
                    el.classList.remove('selected');
                });

                button.classList.add('selected');

                const timeData = {
                    datetime: fullDateTime,
                    timestamp: timestamp,
                    date: date,
                    time: time,
                    dateLabel: dateLabel
                };

                switch (type) {
                    case 'start':
                        selectedStartTime = timeData;

                        // Check if this start time would cause overnight booking with default duration
                        const wouldBeOvernight = handleOvernightTimeSlotAdjustment(timestamp, defaultDuration);

                        // Clear any previous timeout
                        if (renderTimeout) {
                            clearTimeout(renderTimeout);
                        }

                        // Render end time slots with adjusted dates if needed
                        renderTimeout = setTimeout(() => {
                            renderEndTimeSlots();
                        }, 50);
                        break;

                    case 'end':
                        selectedEndTime = timeData;

                        // Scroll to the manually selected end time
                        setTimeout(() => {
                            scrollToSelectedEndTime();
                        }, 100);

                        checkAndAdjustOvernightBooking();
                        break;
                }

                updateBookingSummary();
                updateConfirmButton();
            }

            // Add CSS for better scroll behavior
            const scrollStyle = document.createElement('style');
            scrollStyle.textContent = `
    #endTimeSlotsContainer {
        scroll-behavior: smooth;
    }
    .time-slot.selected {
        animation: pulse-glow 2s ease-in-out;
    }
    @keyframes pulse-glow {
        0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
        50% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
        100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
    }
`;
            document.head.appendChild(scrollStyle);

            // Add this function to scroll to the selected end time
            function scrollToSelectedEndTime() {
                const selectedEndTimeElement = document.querySelector('#endTimeSlotsContainer .time-slot.selected');
                if (selectedEndTimeElement) {
                    // Use setTimeout to ensure the DOM has been updated
                    setTimeout(() => {
                        selectedEndTimeElement.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center',
                            inline: 'center'
                        });

                        // Add a highlight effect
                        selectedEndTimeElement.style.transform = 'scale(1.05)';
                        selectedEndTimeElement.style.transition = 'transform 0.3s ease';

                        setTimeout(() => {
                            selectedEndTimeElement.style.transform = 'scale(1)';
                        }, 300);
                    }, 200);
                }
            }

            // Add this function to scroll to the recommended end time even if not selected yet
            function scrollToRecommendedEndTime() {
                const recommendedEndTime = findExactDurationEndTime(selectedStartTime.timestamp,
                    Array.from(document.querySelectorAll('#endTimeSlotsContainer .time-slot:not([disabled])')));

                if (recommendedEndTime) {
                    setTimeout(() => {
                        recommendedEndTime.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center',
                            inline: 'center'
                        });

                    }, 200);
                }
            }

            // Function to handle overnight time slot adjustments
            function handleOvernightTimeSlotAdjustment(startTimestamp, requiredDuration) {
                const fromDate = document.getElementById('fromDatePicker').value;
                const toDate = document.getElementById('toDatePicker').value;

                // Calculate the expected end time including duration
                const startDateTime = new Date(startTimestamp * 1000);
                const expectedEndDateTime = new Date(startDateTime.getTime() + (requiredDuration * 60 * 1000));

                const startDateLocal = startDateTime.toLocaleDateString('en-CA');
                const expectedEndDateLocal = expectedEndDateTime.toLocaleDateString('en-CA');

                const isCurrentlyOvernight = fromDate !== toDate;
                const wouldBeOvernight = startDateLocal !== expectedEndDateLocal;

                let wasAdjusted = false;

                // If booking would extend to next day AND we're currently on single day booking
                if (wouldBeOvernight && !isCurrentlyOvernight) {
                    // Set the flag to prevent unwanted resets
                    isAutoAdjustingDates = true;

                    // Update the toDate picker to include the next day
                    toDatePicker.setDate(expectedEndDateLocal, true);

                    // Show notification to user
                    showOvernightAdjustmentNotification(fromDate, expectedEndDateLocal);

                    wasAdjusted = true;

                    // Reset the flag after a short delay
                    setTimeout(() => {
                        isAutoAdjustingDates = false;
                    }, 500);
                }
                // If booking would NOT extend overnight AND we have multi-day selection, reset to single day
                else if (!wouldBeOvernight && isCurrentlyOvernight) {
                    // Set the flag
                    isAutoAdjustingDates = true;

                    // Reset toDate to be same as fromDate
                    toDatePicker.setDate(startDateLocal, true);

                    wasAdjusted = true;

                    // Reset the flag after a short delay
                    setTimeout(() => {
                        isAutoAdjustingDates = false;
                    }, 500);
                }

                return wasAdjusted;
            }

            // Function to show overnight adjustment notification
            function showOvernightAdjustmentNotification(originalDate, newEndDate) {
                // Remove any existing notification
                const existingNotification = document.getElementById('overnightAdjustmentNotification');
                if (existingNotification) {
                    existingNotification.remove();
                }

                const notification = document.createElement('div');
                notification.id = 'overnightAdjustmentNotification';
                notification.className = 'mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md';

                const originalFormatted = new Date(originalDate + 'T00:00:00').toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
                const newEndFormatted = new Date(newEndDate + 'T00:00:00').toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });

                notification.innerHTML = `
        <div class="flex items-start">
            <i class="fas fa-moon text-blue-500 mt-0.5 mr-2"></i>
            <div>
                <p class="text-sm font-medium text-blue-800">Overnight Booking Detected</p>
                <p class="text-xs text-blue-600">
                    Your booking extends to the next day. We've automatically extended your booking period to 
                    <strong>${originalFormatted} - ${newEndFormatted}</strong>. 
                    End time slots now show available times for <strong>${newEndFormatted}</strong>.
                </p>
                <p class="text-xs text-blue-600 mt-1">
                    <i class="fas fa-info-circle mr-1"></i>
                    Your seat selection has been preserved.
                </p>
            </div>
        </div>
    `;

                // Insert after date range picker section
                const dateRangeSection = document.querySelector('div').querySelector('[for="fromDatePicker"]');
                if (dateRangeSection && dateRangeSection.parentNode) {
                    const dateRangeParent = dateRangeSection.closest('div').parentNode;
                    dateRangeParent.parentNode.insertBefore(notification, dateRangeParent.nextSibling);
                }
            }

            // Check if the booking duration would extend into branch closed hours
            function checkIfWouldCrossClosedHours(startTimestamp, requiredDuration, availableSlots, closedSlots) {
                const startDateTime = new Date(startTimestamp * 1000);
                const startDate = startDateTime.toLocaleDateString('en-CA');

                // Get branch hours for the start date
                const branchData = timeSlots[startDate];
                if (!branchData) return false;

                const closeTime = branchData.close_time; // e.g., "07:00:00"
                const isOvernight = branchData.is_overnight;
                const [closeHours, closeMinutes] = closeTime.split(':').map(Number);

                // Calculate branch closing time for the day
                let branchCloseTime = new Date(startDateTime);

                if (isOvernight) {
                    // For overnight operations, closing time is next day morning
                    // Check if we're in the overnight period (before close time)
                    const currentHour = startDateTime.getHours();
                    const currentMinutes = startDateTime.getMinutes();

                    if (currentHour < closeHours || (currentHour === closeHours && currentMinutes < closeMinutes)) {
                        // We're in overnight period, closing time is today
                        branchCloseTime.setHours(closeHours, closeMinutes, 0, 0);
                    } else {
                        // We're in day period, closing time is tomorrow
                        branchCloseTime.setDate(branchCloseTime.getDate() + 1);
                        branchCloseTime.setHours(closeHours, closeMinutes, 0, 0);
                    }
                } else {
                    // For same-day operations
                    branchCloseTime.setHours(closeHours, closeMinutes, 0, 0);
                }

                // Calculate when the booking would end
                const bookingEndTime = new Date(startTimestamp * 1000 + (requiredDuration * 60 * 1000));

                // Check if booking would extend PAST branch closing (not equal to)
                return bookingEndTime > branchCloseTime;
            }

            // Check if the booking duration would extend into branch closed hours
            function checkIfExtendsIntoClosedHours(startTimestamp, availableDuration, requiredDuration) {
                const startDateTime = new Date(startTimestamp * 1000);
                const startDate = startDateTime.toLocaleDateString('en-CA');

                // Get branch hours for the start date
                const branchData = timeSlots[startDate];
                if (!branchData) return false;

                const openTime = branchData.open_time; // "11:00:00"
                const closeTime = branchData.close_time; // "07:00:00"
                const isOvernight = branchData.is_overnight;

                // Parse branch hours
                const [openHours, openMinutes] = openTime.split(':').map(Number);
                const [closeHours, closeMinutes] = closeTime.split(':').map(Number);

                // Calculate branch closing time for the day
                let branchCloseTime = new Date(startDateTime);

                if (isOvernight) {
                    // For overnight operations, closing time is next day morning
                    // Check if we're in the overnight period (before close time)
                    const currentHour = startDateTime.getHours();
                    const currentMinutes = startDateTime.getMinutes();

                    if (currentHour < closeHours || (currentHour === closeHours && currentMinutes < closeMinutes)) {
                        // We're in overnight period, closing time is today
                        branchCloseTime.setHours(closeHours, closeMinutes, 0, 0);
                    } else {
                        // We're in day period, closing time is tomorrow
                        branchCloseTime.setDate(branchCloseTime.getDate() + 1);
                        branchCloseTime.setHours(closeHours, closeMinutes, 0, 0);
                    }
                } else {
                    // For same-day operations
                    branchCloseTime.setHours(closeHours, closeMinutes, 0, 0);
                }

                // Calculate when the booking would end
                const bookingEndTime = new Date(startTimestamp * 1000 + (requiredDuration * 60 * 1000));

                // Check if booking would extend past branch closing on the same day
                // Only return true if it extends into closed hours but stays on the same day
                const bookingEndDate = bookingEndTime.toLocaleDateString('en-CA');
                const sameDayExtension = bookingEndTime > branchCloseTime && bookingEndDate === startDate;

                return sameDayExtension;
            }

            function getSpaceTypeLabel() {
                return spaceType === 'room' ? 'room' : 'seat';
            }

            // Add extended time button
            document.getElementById('addExtendedTime').addEventListener('click', function() {
                document.getElementById('extendedTimeSection').classList.remove('hidden');
                document.getElementById('addTimeButtonContainer').classList.add('hidden');
                validateAndUpdateExtendedTime();
                updateBookingSummary();
                updateConfirmButton();

                if (selectedStartTime && selectedEndTime) {
                    checkAndAdjustOvernightBooking();
                }
            });

            // Remove extended time button
            document.getElementById('removeExtendedTime').addEventListener('click', function() {
                document.getElementById('extendedTimeSection').classList.add('hidden');
                document.getElementById('addTimeButtonContainer').classList.remove('hidden');
                extendedHours = 0;
                extendedMinutes = 0;
                updateExtendedTimeDisplay();

                // Check if we should show button or message
                const endsAtClosing = checkIfBookingEndsAtClosing();
                updateExtendedTimeButtonVisibility(endsAtClosing);

                // Clear ALL validation messages
                removeExtendedTimeValidationMessage();
                const existingInfoMessage = document.getElementById('extendedTimeInfoMessage');
                if (existingInfoMessage) {
                    existingInfoMessage.remove();
                }

                updateBookingSummary();
                updateConfirmButton();

                if (selectedStartTime && selectedEndTime) {
                    checkAndAdjustOvernightBooking();
                }
            });

            // Seat selection handling
            const seatInputs = document.querySelectorAll('input[name="selected_seat"]');
            const style = document.createElement('style');
            style.textContent = `
    .seat-item.selected label {
        border-color: #3b82f6 !important;
        background-color: #dbeafe !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    .seat-item label {
        transition: all 0.2s ease-in-out;
    }
`;
            document.head.appendChild(style);

            seatInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const spaceType = '{{ $actualSpaceType }}';
                    const seatLabel = spaceType === 'room' ? 'room' : 'seat';

                    // Remove selected class from ALL seats first
                    document.querySelectorAll('.seat-item').forEach(item => {
                        item.classList.remove('selected');
                        // Also remove any visual selection indicators
                        const label = item.querySelector('label');
                        if (label) {
                            label.classList.remove('border-blue-500', 'bg-blue-50');
                            label.classList.add('border-gray-200', 'bg-white');
                        }
                    });

                    // Add selected class only to the chosen seat
                    if (this.checked) {
                        const seatItem = this.closest('.seat-item');
                        if (seatItem) {
                            seatItem.classList.add('selected');
                            const label = seatItem.querySelector('label');
                            if (label) {
                                label.classList.remove('border-gray-200', 'bg-white');
                                label.classList.add('border-blue-500', 'bg-blue-50');
                            }
                        }
                    }

                    // Reset time range when seat changes
                    resetTimeRange();

                    // Show time range section when seat is selected
                    // Use the already declared timeRangeSection variable
                    if (this.checked && timeRangeSection) {
                        timeRangeSection.classList.remove('hidden');
                        renderTimeSlots(); // Render time slots when seat is selected
                    } else if (timeRangeSection) {
                        timeRangeSection.classList.add('hidden');
                    }

                    updateBookingSummary();
                    updateConfirmButton();
                });
            });

            // Call when dates change too
            document.getElementById('fromDatePicker').addEventListener('change', function() {
                setTimeout(() => {
                    if (document.querySelector('input[name="selected_seat"]:checked')) {
                        fetchAndMarkExistingBookings();
                    }
                }, 300);
            });

            document.getElementById('toDatePicker').addEventListener('change', function() {
                setTimeout(() => {
                    if (document.querySelector('input[name="selected_seat"]:checked')) {
                        fetchAndMarkExistingBookings();
                    }
                }, 300);
            });

            document.querySelectorAll('.seat-item label').forEach(label => {
                label.addEventListener('click', function(e) {
                    // Prevent double triggering since the input change will also fire
                    e.stopPropagation();

                    const input = this.previousElementSibling;
                    if (input && input.type === 'radio') {
                        input.checked = true;
                        // Manually trigger the change event
                        input.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));
                    }
                });
            });

            // Modal event handlers
            modalConfirm.addEventListener('click', function() {
                durationModal.style.display = 'none';
            });
            
            conflictModalOk.addEventListener('click', function() {
                bookingConflictModal.style.display = 'none';
                // Re-render slots to show the newly booked ones as disabled
                renderTimeSlots();
            });

            window.addEventListener('click', function(event) {
                if (event.target === durationModal) {
                    durationModal.style.display = 'none';
                }
                if (event.target === bookingConflictModal) {
                    bookingConflictModal.style.display = 'none';
                    renderTimeSlots();
                }
            });

            // Form submission
            document.getElementById('bookingForm').addEventListener('submit', async function(e) {
                e.preventDefault(); // Stop submission immediately to validate
                
                // Validate required fields
                const fromDate = document.getElementById('fromDatePicker').value;
                const toDate = document.getElementById('toDatePicker').value;
                const selectedSeat = document.querySelector('input[name="selected_seat"]:checked');
                const hasExtendedTime = !document.getElementById('extendedTimeSection').classList.contains(
                    'hidden');

                // Additional validation for extended time
                if (hasExtendedTime && (extendedHours === 0 && extendedMinutes === 0)) {
                    alert('Please add extended time or remove the extended time section.');
                    return;
                }

                // Check all required fields
                if (!fromDate || !toDate || !selectedStartTime || !selectedEndTime || !selectedSeat) {
                    const spaceLabel = spaceType === 'room' ? 'room' : 'seat';
                    alert('Please complete all required fields: date range, time range, and ' + spaceLabel);
                    return;
                }
                
                // Show loading state on button
                const submitBtn = document.getElementById('submitBtn');
                const originalBtnText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Checking Availability...';

                try {
                    // 1. Fetch the LATEST bookings from server (fresh check)
                    await fetchAndMarkExistingBookings();
                    
                    // 2. Perform overlap check with the fresh data
                    const isConflict = checkFreshConflicts();
                    
                    if (isConflict) {
                        // Show conflict modal
                        bookingConflictModal.style.display = 'block';
                        
                        // Reset button
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                        return; // Stop here
                    }

                    // 3. Update hidden fields one last time
                    updateHiddenFields();
                    
                    // 4. Submit the form programmatically since checks passed
                    this.submit();
                    
                } catch (error) {
                    console.error("Validation error:", error);
                    alert("An error occurred while validating booking availability. Please try again.");
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            });
            
            // Function to check conflicts against the currently loaded (fresh) window.existingBookingsData
            function checkFreshConflicts() {
                if (!window.existingBookingsData || window.existingBookingsData.length === 0) {
                    return false;
                }
                
                // Construct selected intervals
                // Interval 1: Main Booking
                const mainStart = new Date(selectedStartTime.datetime).getTime();
                const mainEnd = new Date(selectedEndTime.datetime).getTime();
                
                // Interval 2: Extended Booking (if any)
                let extendedStart = null;
                let extendedEnd = null;
                const hasExtendedTime = !document.getElementById('extendedTimeSection').classList.contains('hidden');
                
                if (hasExtendedTime && (extendedHours > 0 || extendedMinutes > 0)) {
                    const extendedDurationMinutes = (extendedHours * 60) + extendedMinutes;
                    extendedStart = mainEnd; // Extended starts where main ends
                    extendedEnd = mainEnd + (extendedDurationMinutes * 60 * 1000);
                }
                
                // Helper to check overlap between two time ranges [start1, end1] and [start2, end2]
                const doTimesOverlap = (s1, e1, s2, e2) => {
                    return s1 < e2 && e1 > s2;
                };

                let conflictFound = false;

                // Loop through existing bookings
                for (const booking of window.existingBookingsData) {
                    // Helper to parse booking date/time strings to timestamp
                    const parseToTs = (dateStr, timeStr) => {
                         if (!dateStr || !timeStr) return null;
                         
                         let time24 = timeStr;
                         // Handle AM/PM if present
                         if (/am|pm/i.test(timeStr)) {
                           const [t, period] = timeStr.split(/\s+/);
                           let [h, m] = t.split(':').map(Number);
                           if (period.toLowerCase() === 'pm' && h < 12) h += 12;
                           if (period.toLowerCase() === 'am' && h === 12) h = 0;
                           // Ensure seconds
                           const secs = t.split(':').length > 2 ? t.split(':')[2] : '00';
                           time24 = `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${secs}`;
                         }
                         
                         return new Date(`${dateStr}T${time24}`).getTime();
                    };

                    const bMainStart = parseToTs(booking.date_start, booking.start_time);
                    const bMainEnd = parseToTs(booking.date_end, booking.end_time);
                    
                    // Check Main vs Existing Main
                    if (bMainStart && bMainEnd && doTimesOverlap(mainStart, mainEnd, bMainStart, bMainEnd)) {
                        conflictFound = true; 
                        break;
                    }

                    // Check Main vs Existing Extended
                    const bExtStart = parseToTs(booking.extended_date_start, booking.extended_start_time);
                    const bExtEnd = parseToTs(booking.extended_date_end, booking.extended_end_time);
                    if (bExtStart && bExtEnd && doTimesOverlap(mainStart, mainEnd, bExtStart, bExtEnd)) {
                        conflictFound = true;
                        break;
                    }
                    
                    // If we have extended time selected, check that too
                    if (extendedStart && extendedEnd) {
                        // Check Extended vs Existing Main
                        if (bMainStart && bMainEnd && doTimesOverlap(extendedStart, extendedEnd, bMainStart, bMainEnd)) {
                            conflictFound = true; 
                            break;
                        }
                        // Check Extended vs Existing Extended
                        if (bExtStart && bExtEnd && doTimesOverlap(extendedStart, extendedEnd, bExtStart, bExtEnd)) {
                            conflictFound = true; 
                            break;
                        }
                    }
                }
                
                return conflictFound;
            }

            function clearSeatSelection() {
                const selectedSeat = document.querySelector('input[name="selected_seat"]:checked');
                if (selectedSeat) {
                    selectedSeat.checked = false;
                    const seatItem = selectedSeat.closest('.seat-item');
                    if (seatItem) {
                        seatItem.classList.remove('selected');
                        const label = seatItem.querySelector('label');
                        if (label) {
                            label.classList.remove('border-blue-500', 'bg-blue-50');
                            label.classList.add('border-gray-200', 'bg-white');
                        }
                    }
                }
            }

            // Add these variables at the top with your other variables
            let servicePackages = []; // This will store available service packages for the same category
            let baseHourlyRate = 0; // This will store the hourly rate for the current service category

            const hourlyRate = @json($hourlyRate) || 0;

            // Initialize pricing data when the page loads
            function initializePricingData() {
                // Hourly rate is now passed from PHP
                if (hourlyRate === 0) {
                    // Fallback: calculate from current service if hourly rate not available
                    const currentServicePrice = {{ $service->price }};
                    const currentServiceDuration = {{ $defaultDuration }};

                    // Only use as hourly rate if service is exactly 1 hour
                    if (currentServiceDuration === 60) {
                        baseHourlyRate = currentServicePrice;
                    } else {
                        // Otherwise calculate approximate hourly rate
                        baseHourlyRate = calculateHourlyRate(currentServicePrice, currentServiceDuration);
                    }
                } else {
                    baseHourlyRate = hourlyRate;
                }

                // Get all service packages for this branch and category
                servicePackages = @json($servicePackages);
            }

            // Calculate hourly rate based on service price and duration
            function calculateHourlyRate(price, durationMinutes) {
                const durationHours = durationMinutes / 60;
                return price / durationHours;
            }

            // Calculate price for 15 minutes
            function calculateFifteenMinuteRate() {
                return baseHourlyRate / 4;
            }

            // Calculate extended time price
            function calculateExtendedTimePrice(extendedDurationMinutes) {
                const fifteenMinuteRate = calculateFifteenMinuteRate();
                const hourlyRate = baseHourlyRate;

                // Check if extended time qualifies for a package upgrade
                const upgradedPackage = findUpgradedPackage(extendedDurationMinutes);
                if (upgradedPackage) {
                    return upgradedPackage.price;
                }

                // Calculate based on hourly and 15-minute rates
                const fullHours = Math.floor(extendedDurationMinutes / 60);
                const remainingMinutes = extendedDurationMinutes % 60;
                const fifteenMinuteBlocks = Math.ceil(remainingMinutes / 15);

                const hourlyCost = fullHours * hourlyRate;
                const minuteCost = fifteenMinuteBlocks * fifteenMinuteRate;

                const totalExtendedCost = hourlyCost + minuteCost;

                return totalExtendedCost;
            }

            // Find if extended time qualifies for a package upgrade
            function findUpgradedPackage(extendedDurationMinutes) {
                const servicePackages = @json($servicePackages).map(pkg => ({
                    ...pkg,
                    price: parseFloat(pkg.price) || 0
                }));

                if (!servicePackages || servicePackages.length === 0) {
                    return null;
                }

                const currentServiceDuration = {{ $defaultDuration }};
                const totalDuration = currentServiceDuration + extendedDurationMinutes;

                // Sort packages by duration (ascending)
                const sortedPackages = [...servicePackages].sort((a, b) => a.duration_minutes - b.duration_minutes);

                // Round total duration to nearest 15 minutes
                const roundedDuration = Math.round(totalDuration / 15) * 15;

                // Find EXACT match with rounded duration
                let bestMatch = null;

                for (const pkg of sortedPackages) {
                    // Only consider packages with longer duration than current
                    if (pkg.duration_minutes > currentServiceDuration && pkg.price) {
                        // Check for EXACT match with rounded duration
                        if (pkg.duration_minutes === roundedDuration) {
                            bestMatch = pkg;
                            break; // Exact match found, stop searching
                        }
                    }
                }

                return bestMatch;
            }

            // function to calculate the TOTAL price when package is applied
            function calculateTotalPriceWithPackage(extendedDurationMinutes) {
                const currentServicePrice = {{ $service->price }};
                const upgradedPackage = findUpgradedPackage(extendedDurationMinutes);

                if (upgradedPackage) {
                    // When package matches, user pays the full package price
                    return upgradedPackage.price;
                }

                // Otherwise, calculate normally
                const extendedPrice = calculateExtendedTimePriceDirect(extendedDurationMinutes);
                return currentServicePrice + extendedPrice;
            }

            // helper function to calculate what the extended time would cost normally
            function calculateNormalExtendedTimePrice(extendedDurationMinutes) {
                const hourlyRate = @json($hourlyRate);
                const fifteenMinuteRate = hourlyRate / 4;

                const fullHours = Math.floor(extendedDurationMinutes / 60);
                const remainingMinutes = extendedDurationMinutes % 60;
                const fifteenMinuteBlocks = Math.ceil(remainingMinutes / 15);

                const hourlyCost = fullHours * hourlyRate;
                const minuteCost = fifteenMinuteBlocks * fifteenMinuteRate;

                return hourlyCost + minuteCost;
            }

            // Package upgrade message functions
            function showPackageUpgradeMessage(upgradedPackage, currentServicePrice, extendedDuration) {
                // Ensure upgradedPackage is valid and has a numeric price
                if (!upgradedPackage || typeof upgradedPackage.price !== 'number') {
                    hidePackageUpgradeMessage();
                    return;
                }

                let messageContainer = document.getElementById('packageUpgradeMessage');
                if (!messageContainer) {
                    messageContainer = document.createElement('div');
                    messageContainer.id = 'packageUpgradeMessage';
                    messageContainer.className = 'mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-md';
                    document.getElementById('bookingSummary').appendChild(messageContainer);
                }

                // Ensure prices are numbers
                const mainPrice = parseFloat(currentServicePrice) || 0;
                const packagePrice = parseFloat(upgradedPackage.price) || 0;
                const mainDuration = {{ $defaultDuration }};
                const totalDuration = mainDuration + extendedDuration;
                const packageDuration = upgradedPackage.duration_minutes || 0;

                // Helper function to format minutes to HH:MM
                function formatMinutesToHHMM(minutes) {
                    const hours = Math.floor(minutes / 60);
                    const mins = minutes % 60;
                    return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
                }

                // Helper function to format minutes to readable duration
                function formatMinutesToReadable(minutes) {
                    const hours = Math.floor(minutes / 60);
                    const mins = minutes % 60;

                    if (hours === 0) {
                        return `${mins} minute${mins !== 1 ? 's' : ''}`;
                    } else if (mins === 0) {
                        return `${hours} hour${hours !== 1 ? 's' : ''}`;
                    } else {
                        return `${hours} hour${hours !== 1 ? 's' : ''} ${mins} minute${mins !== 1 ? 's' : ''}`;
                    }
                }

                // Calculate what the user would pay without package
                const hourlyRate = parseFloat(@json($hourlyRate)) || 0;
                let normalExtendedPrice = 0;

                if (hourlyRate > 0) {
                    const fifteenMinuteRate = hourlyRate / 4;
                    const fullHours = Math.floor(extendedDuration / 60);
                    const remainingMinutes = extendedDuration % 60;
                    const fifteenMinuteBlocks = Math.ceil(remainingMinutes / 15);
                    normalExtendedPrice = (fullHours * hourlyRate) + (fifteenMinuteBlocks * fifteenMinuteRate);
                }

                const normalTotal = mainPrice + normalExtendedPrice;

                // Calculate savings
                const savings = normalTotal - packagePrice;

                messageContainer.innerHTML = `
        <div class="flex items-start">
            <i class="fas fa-star text-yellow-500 mt-0.5 mr-2"></i>
            <div>
                <p class="text-sm font-medium text-yellow-800">🎉 Exact Package Match Found!</p>
                <p class="text-xs text-yellow-700">
                    <strong>Your booking duration exactly matches a package!</strong><br>
                    Total: ${formatMinutesToHHMM(mainDuration)} + ${formatMinutesToHHMM(extendedDuration)} = ${formatMinutesToHHMM(totalDuration)}<br>
                    (${formatMinutesToReadable(mainDuration)} + ${formatMinutesToReadable(extendedDuration)} = ${formatMinutesToReadable(totalDuration)})<br>
                    Exact match: <strong>${upgradedPackage.service_name || 'Package'}</strong> (${upgradedPackage.time_duration || ''})<br>
                    Package Price: <strong class="text-green-600">₱${packagePrice.toFixed(2)}</strong><br>
                    <span class="text-green-600 font-bold">Save ₱${savings.toFixed(2)} vs normal hourly rate</span>
                </p>
                <p class="text-xs text-yellow-600 mt-1">
                    <i class="fas fa-info-circle"></i> You'll be charged the package price for the entire booking duration.
                </p>
            </div>
        </div>
    `;
            }

            // Helper function to calculate extended time price without package consideration
            function calculateExtendedTimePriceWithoutPackage(extendedDurationMinutes) {
                const hourlyRate = @json($hourlyRate);
                const fifteenMinuteRate = hourlyRate / 4;

                const fullHours = Math.floor(extendedDurationMinutes / 60);
                const remainingMinutes = extendedDurationMinutes % 60;
                const fifteenMinuteBlocks = Math.ceil(remainingMinutes / 15);

                const hourlyCost = fullHours * hourlyRate;
                const minuteCost = fifteenMinuteBlocks * fifteenMinuteRate;

                return hourlyCost + minuteCost;
            }

            function hidePackageUpgradeMessage() {
                const messageContainer = document.getElementById('packageUpgradeMessage');
                if (messageContainer) {
                    messageContainer.remove();
                }
            }

            function calculateExtendedTimePriceDirect(extendedDurationMinutes) {
                // Get the hourly rate from the 1-hour service in the same category
                const hourlyRate = parseFloat(@json($hourlyRate)) || 0;

                // Get the current service price and duration
                const currentServicePrice = parseFloat({{ $service->price }}) || 0;
                const currentServiceDuration = {{ $defaultDuration }};

                // Get all service packages (ensure prices are numbers)
                const servicePackages = @json($servicePackages).map(pkg => ({
                    ...pkg,
                    price: parseFloat(pkg.price) || 0
                }));

                // Calculate total duration (main + extended)
                const totalDurationMinutes = currentServiceDuration + extendedDurationMinutes;

                // Check if total duration EXACTLY matches any package
                const upgradedPackage = findUpgradedPackage(extendedDurationMinutes);

                // If we found an EXACT matching package, use PACKAGE PRICE
                if (upgradedPackage && upgradedPackage.price) {
                    // User pays the full package price
                    // For display purposes, we calculate extended portion as: package price - current service price
                    return upgradedPackage.price - currentServicePrice;
                }

                // If no EXACT package match, calculate based on the 1-hour service rate
                if (hourlyRate > 0) {
                    const fifteenMinuteRate = hourlyRate / 4;
                    const fullHours = Math.floor(extendedDurationMinutes / 60);
                    const remainingMinutes = extendedDurationMinutes % 60;
                    const fifteenMinuteBlocks = Math.ceil(remainingMinutes / 15);

                    const hourlyCost = fullHours * hourlyRate;
                    const minuteCost = fifteenMinuteBlocks * fifteenMinuteRate;

                    return hourlyCost + minuteCost;
                }

                return 0;
            }

            // Call initializePricingData when the page loads
            document.addEventListener('DOMContentLoaded', function() {
                initializePricingData();
                initializeExtendedTimeEvents();
            });

            function initializeExtendedTimeEvents() {
                const increaseHoursBtn = document.getElementById('increaseHours');
                const decreaseHoursBtn = document.getElementById('decreaseHours');
                const increaseMinutesBtn = document.getElementById('increaseMinutes');
                const decreaseMinutesBtn = document.getElementById('decreaseMinutes');

                // Function to update price when extended time changes
                function updateExtendedTimePrice() {
                    updateBookingSummary();
                }

                // Add event listeners to all extended time buttons
                if (increaseHoursBtn) {
                    increaseHoursBtn.addEventListener('click', updateExtendedTimePrice);
                }
                if (decreaseHoursBtn) {
                    decreaseHoursBtn.addEventListener('click', updateExtendedTimePrice);
                }
                if (increaseMinutesBtn) {
                    increaseMinutesBtn.addEventListener('click', updateExtendedTimePrice);
                }
                if (decreaseMinutesBtn) {
                    decreaseMinutesBtn.addEventListener('click', updateExtendedTimePrice);
                }

                // Also update when extended time section is shown/hidden
                const addExtendedTimeBtn = document.getElementById('addExtendedTime');
                const removeExtendedTimeBtn = document.getElementById('removeExtendedTime');

                if (addExtendedTimeBtn) {
                    addExtendedTimeBtn.addEventListener('click', function() {
                        setTimeout(updateBookingSummary, 100);
                    });
                }

                if (removeExtendedTimeBtn) {
                    removeExtendedTimeBtn.addEventListener('click', function() {
                        setTimeout(updateBookingSummary, 100);
                    });
                }
            }

            // Calculate time difference in minutes
            function getTimeDifferenceInMinutes(startDateTime, endDateTime) {
                const start = new Date(startDateTime);
                const end = new Date(endDateTime);
                return Math.round((end - start) / (1000 * 60));
            }

            // Update the helper functions for extended time calculations
            function calculateExtendedDateRange(mainEndDateTime, extendedDurationMinutes) {
                const mainEndDate = new Date(mainEndDateTime);
                const extendedEndDate = new Date(mainEndDate.getTime() + (extendedDurationMinutes * 60 * 1000));

                // Extended Date Range uses the main end date as start and extended end date as end
                const startDate = mainEndDate.toLocaleDateString('en-CA');
                const endDate = extendedEndDate.toLocaleDateString('en-CA');

                return startDate + ' to ' + endDate;
            }

            function calculateExtendedTimeRange(mainEndDateTime, extendedDurationMinutes) {
                const mainEndDate = new Date(mainEndDateTime);
                const extendedEndDate = new Date(mainEndDate.getTime() + (extendedDurationMinutes * 60 * 1000));

                // Extended Time Range uses the main end time as start and extended end time as end
                const startTime = formatTimeForSummary(mainEndDateTime);
                const endTime = formatTimeForSummary(extendedEndDate);
                return startTime + ' to ' + endTime;
            }

            // Keep the formatTimeForSummary function as is
            function formatTimeForSummary(dateTimeStr) {
                let date;
                if (typeof dateTimeStr === 'string') {
                    date = new Date(dateTimeStr);
                } else if (dateTimeStr instanceof Date) {
                    date = dateTimeStr;
                } else {
                    return '-';
                }

                return date.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });
            }

            function validateExtendedTime(extendedDurationMinutes) {
                if (!selectedStartTime || !selectedEndTime) {
                    return {
                        valid: true,
                        wouldExtendOvernight: false,
                        wouldCrossClosedHours: false
                    };
                }

                const mainEndDateTime = new Date(selectedEndTime.datetime);
                const extendedEndDateTime = new Date(mainEndDateTime.getTime() + (extendedDurationMinutes * 60 *
                    1000));

                const startDateLocal = mainEndDateTime.toLocaleDateString('en-CA');
                const endDateLocal = extendedEndDateTime.toLocaleDateString('en-CA');

                const wouldExtendOvernight = startDateLocal !== endDateLocal;

                // Check if extended time would cross into closed hours
                const wouldCrossClosedHours = checkExtendedTimeClosedHours(mainEndDateTime,
                    extendedDurationMinutes);

                return {
                    valid: !wouldCrossClosedHours,
                    wouldExtendOvernight: wouldExtendOvernight,
                    wouldCrossClosedHours: wouldCrossClosedHours,
                    mainEndDateTime: mainEndDateTime,
                    extendedEndDateTime: extendedEndDateTime
                };
            }

            // Check if extended time would cross into closed hours
            function checkExtendedTimeClosedHours(mainEndDateTime, extendedDurationMinutes) {
                const extendedEndDateTime = new Date(mainEndDateTime.getTime() + (extendedDurationMinutes * 60 *
                    1000));
                const startDateKey = mainEndDateTime.toLocaleDateString('en-CA');
                const endDateKey = extendedEndDateTime.toLocaleDateString('en-CA');

                // Get branch data for start date
                const branchData = timeSlots[startDateKey];
                if (!branchData) {
                    return false;
                }

                const closeTime = branchData.close_time; // e.g., "07:00:00"
                const isOvernight = branchData.is_overnight;
                const [closeHours, closeMinutes] = closeTime.split(':').map(Number);

                // Calculate when the branch actually closes
                let branchCloseTime = new Date(mainEndDateTime);
                branchCloseTime.setHours(closeHours, closeMinutes, 0, 0);

                if (isOvernight) {
                    // For overnight operations (close at 7:00 AM, open at 11:00 AM)
                    const currentHour = mainEndDateTime.getHours();
                    const currentMinute = mainEndDateTime.getMinutes();

                    if (currentHour >= closeHours && currentMinute >= closeMinutes) {
                        // If current time is after closing time (7:00 AM or later)
                        // Next closing is tomorrow
                        branchCloseTime.setDate(branchCloseTime.getDate() + 1);
                    }
                    // If current time is before closing time, branchCloseTime is already set correctly
                } else {
                    // For same-day operations
                    // If we're already past closing time, return true immediately
                    if (mainEndDateTime >= branchCloseTime) {
                        return true;
                    }
                }

                // Check if extended end time goes PAST branch closing
                const exceedsClosingTime = extendedEndDateTime > branchCloseTime;

                return exceedsClosingTime;
            }

            // Calculate maximum allowed extended duration without crossing closed hours
            function calculateMaxExtendedDuration() {
                if (!selectedEndTime) return 0;

                const mainEndDateTime = new Date(selectedEndTime.datetime);
                const endDateKey = mainEndDateTime.toLocaleDateString('en-CA');
                const branchData = timeSlots[endDateKey];

                if (!branchData) return 24 * 60; // Default to 24 hours if no branch data

                const closeTime = branchData.close_time;
                const isOvernight = branchData.is_overnight;
                const [closeHours, closeMinutes] = closeTime.split(':').map(Number);

                // Calculate branch closing time
                let branchCloseTime = new Date(mainEndDateTime);

                if (isOvernight) {
                    // For overnight operations (close at 7:00 AM, open at 11:00 AM)
                    branchCloseTime.setHours(closeHours, closeMinutes, 0, 0);

                    // Determine if we're in the overnight period or day period
                    const currentHour = mainEndDateTime.getHours();
                    const currentMinute = mainEndDateTime.getMinutes();

                    if (currentHour >= closeHours && currentMinute >= closeMinutes) {
                        // If current time is after closing time (7:00 AM), branch is already closed
                        // Next closing is tomorrow
                        branchCloseTime.setDate(branchCloseTime.getDate() + 1);
                    } else {
                        // Current time is before closing time, branch closes today at closeHours
                        // Do nothing - branchCloseTime is already set for today
                    }
                } else {
                    // For same-day operations
                    branchCloseTime.setHours(closeHours, closeMinutes, 0, 0);
                    // If we're past closing time, no more time available today
                    if (mainEndDateTime >= branchCloseTime) {
                        return 0;
                    }
                }

                const maxDuration = Math.floor((branchCloseTime - mainEndDateTime) / (1000 * 60));
                return Math.max(0, maxDuration);
            }

            // Show validation message
            function showExtendedTimeValidationMessage(validation, currentExtendedDuration) {
                // Remove any existing messages first
                removeExtendedTimeValidationMessage();

                // Also remove any info messages
                const existingInfo = document.getElementById('extendedTimeInfoMessage');
                if (existingInfo) existingInfo.remove();

                const messageContainer = document.createElement('div');
                messageContainer.id = 'extendedTimeValidationMessage';
                messageContainer.className = 'mt-4 p-3 rounded-md border';

                const maxAllowedDuration = calculateMaxExtendedDuration();

                if (validation.wouldCrossClosedHours) {
                    messageContainer.className += ' bg-yellow-50 border-yellow-200';

                    const availableHours = Math.floor(maxAllowedDuration / 60);
                    const availableMinutes = maxAllowedDuration % 60;

                    messageContainer.innerHTML = `
            <div class="flex items-start">
                <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5 mr-2"></i>
                <div class="flex-1">
                    <p class="text-sm font-medium text-yellow-800">Extended Time Exceeds Branch Hours</p>
                    <p class="text-xs text-yellow-700 mt-1">
                        The selected extended time would extend past branch closing hours. 
                        Maximum allowed extended time: <strong>${availableHours}h ${availableMinutes}m</strong>
                    </p>
                    <div class="mt-2 flex gap-2">
                        <button type="button" id="adjustExtendedTimeBtn" 
                                class="px-3 py-1 bg-yellow-500 text-white text-xs rounded hover:bg-yellow-600 transition duration-200">
                            Adjust to Maximum
                        </button>
                    </div>
                </div>
            </div>
        `;

                    // Add event listener for adjust button
                    setTimeout(() => {
                        const adjustBtn = document.getElementById('adjustExtendedTimeBtn');
                        if (adjustBtn) {
                            adjustBtn.addEventListener('click', function() {
                                adjustExtendedTimeToMax();
                            });
                        }
                    }, 100);

                    // Insert message after extended time section
                    const extendedTimeSection = document.getElementById('extendedTimeSection');
                    if (extendedTimeSection) {
                        extendedTimeSection.appendChild(messageContainer);
                    }
                } else if (validation.wouldExtendOvernight) {
                    // Show info message for overnight extension
                    showOvernightInfoMessage(validation);
                }
            }

            // Check if start time at 8:00 PM or later would cause overnight issues
            function checkStartTimeOvernightIssue(startTime, startDate) {
                if (!startTime || !startDate) return false;

                // Parse the start time (format: "HH:MM")
                const [hours, minutes] = startTime.split(':').map(Number);

                // Check if start time is 8:00 PM (20:00) or later
                if (hours >= 20) {
                    const startDateTime = new Date(startDate + 'T' + startTime + ':00');
                    const branchData = timeSlots[startDate];

                    if (!branchData) return false;

                    const isOvernight = branchData.is_overnight;
                    const closeTime = branchData.close_time; // e.g., "07:00:00"
                    const [closeHours, closeMinutes] = closeTime.split(':').map(Number);

                    if (isOvernight) {
                        // For overnight operations (e.g., close at 7:00 AM, open at 11:00 AM)
                        // If booking starts at 8:00 PM or later, it will definitely extend past closing
                        // because the branch closes at 7:00 AM next day
                        const bookingEndTime = new Date(startDateTime.getTime() + (defaultDuration * 60 * 1000));

                        // Calculate branch closing time (next day at closeHours)
                        const branchCloseTime = new Date(startDateTime);
                        branchCloseTime.setDate(branchCloseTime.getDate() + 1);
                        branchCloseTime.setHours(closeHours, closeMinutes, 0, 0);

                        // Check if booking would end after branch closing
                        return bookingEndTime > branchCloseTime;
                    }
                }

                return false;
            }

            // Remove validation message
            function removeExtendedTimeValidationMessage() {
                const messageContainer = document.getElementById('extendedTimeValidationMessage');
                if (messageContainer) {
                    messageContainer.remove();
                }
            }

            // Adjust extended time to maximum allowed
            function adjustExtendedTimeToMax() {
                const maxAllowedDuration = calculateMaxExtendedDuration();

                if (maxAllowedDuration > 0) {
                    extendedHours = Math.floor(maxAllowedDuration / 60);
                    extendedMinutes = maxAllowedDuration % 60;

                    updateExtendedTimeDisplay();
                    updateBookingSummary();
                    updateConfirmButton();

                    removeExtendedTimeValidationMessage();
                }
            }

            function validateAndUpdateExtendedTime() {
                const extendedDuration = (extendedHours * 60) + extendedMinutes;

                // Only validate if we have extended time
                if (extendedDuration > 0 && selectedStartTime && selectedEndTime) {
                    const validation = validateExtendedTime(extendedDuration);

                    // Remove ALL existing messages first
                    removeExtendedTimeValidationMessage();
                    const existingInfoMessage = document.getElementById('extendedTimeInfoMessage');
                    if (existingInfoMessage) {
                        existingInfoMessage.remove();
                    }

                    // Only show error message if it actually exceeds closing time
                    if (validation.wouldCrossClosedHours) {
                        showExtendedTimeValidationMessage(validation, extendedDuration);
                        // Reset overnight tracking since this is a different type of message
                        isOvernightInfoVisible = false;
                        lastOvernightInfoData = null;
                    }
                    // Show info message for overnight extension (regardless of closing hours)
                    else if (validation.wouldExtendOvernight) {
                        showOvernightInfoMessage(validation);
                    }
                    // If not overnight and not exceeding hours, ensure all messages are cleared
                    else {
                        // Remove any error messages
                        removeExtendedTimeValidationMessage();

                        const infoMessage = document.getElementById('extendedTimeInfoMessage');
                        if (infoMessage) {
                            infoMessage.remove();
                        }

                        // Reset overnight tracking since this is a different type of message
                        isOvernightInfoVisible = false;
                        lastOvernightInfoData = null;
                    }
                } else {
                    // Remove all messages if no extended time
                    removeExtendedTimeValidationMessage();
                    const infoMessage = document.getElementById('extendedTimeInfoMessage');
                    if (infoMessage) {
                        infoMessage.remove();
                    }

                    // Reset overnight tracking since this is a different type of message
                    isOvernightInfoVisible = false;
                    lastOvernightInfoData = null;
                }

                updateBookingSummary();
                updateConfirmButton();

                if (selectedStartTime && selectedEndTime) {
                    checkAndAdjustOvernightBooking();
                }
            }

            // Show overnight info message (not an error)
            function showOvernightInfoMessage(validation) {
                // Create a unique key for this overnight scenario
                const currentExtendedDuration = (extendedHours * 60) + extendedMinutes;
                const fromDate = document.getElementById('fromDatePicker').value;
                const toDate = document.getElementById('toDatePicker').value;
                const currentKey =
                    `${fromDate}|${toDate}|${currentExtendedDuration}|${selectedStartTime?.timestamp}|${selectedEndTime?.timestamp}`;

                // Check if we already showed this exact message
                if (currentKey === lastOvernightInfoData && isOvernightInfoVisible) {
                    return; // Don't show again
                }

                // Remove any existing overnight info message FIRST
                const existingMessage = document.getElementById('extendedTimeInfoMessage');
                if (existingMessage) {
                    existingMessage.remove();
                }

                const messageContainer = document.createElement('div');
                messageContainer.id = 'extendedTimeInfoMessage';
                messageContainer.className = 'mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md';

                const extendedEndDate = validation.extendedEndDateTime.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
                const extendedEndTime = validation.extendedEndDateTime.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });
                const mainEndTime = validation.mainEndDateTime.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });

                messageContainer.innerHTML = `
        <div class="flex justify-between items-start">
            <div class="flex items-start flex-1">
                <i class="fas fa-moon text-blue-500 mt-0.5 mr-2"></i>
                <div class="flex-1">
                    <div class="flex justify-between items-center mb-1">
                        <p class="text-sm font-medium text-blue-800">Overnight Extended Booking</p>
                        <button type="button" id="closeExtendedInfoMessage" 
                                class="text-blue-400 hover:text-blue-600 text-sm">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <p class="text-xs text-blue-700">
                        Your extended time from ${mainEndTime} will continue until ${extendedEndTime} on ${extendedEndDate}.
                        This booking extends overnight but stays within branch operating hours.
                    </p>
                </div>
            </div>
        </div>
    `;

                // Insert message after extended time section
                const extendedTimeSection = document.getElementById('extendedTimeSection');
                if (extendedTimeSection) {
                    extendedTimeSection.appendChild(messageContainer);
                }

                // Update tracking
                lastOvernightInfoData = currentKey;
                isOvernightInfoVisible = true;

                // Event listener for close button
                setTimeout(() => {
                    const closeBtn = document.getElementById('closeExtendedInfoMessage');
                    if (closeBtn) {
                        closeBtn.addEventListener('click', function() {
                            const message = document.getElementById('extendedTimeInfoMessage');
                            if (message) {
                                message.remove();
                                isOvernightInfoVisible = false;
                            }
                        });
                    }
                }, 100);

                // Auto-remove after 8 seconds (longer so user can read it)
                setTimeout(() => {
                    const infoMessage = document.getElementById('extendedTimeInfoMessage');
                    if (infoMessage) {
                        infoMessage.remove();
                    }
                }, 8000);
            }

            // Function to check if booking ends at closing time
            function checkIfBookingEndsAtClosing() {
                if (!selectedStartTime || !selectedEndTime) return false;

                const startDate = selectedStartTime.date;
                const branchData = timeSlots[startDate];

                if (!branchData || !branchData.close_time) return false;

                const closeTime = branchData.close_time;
                const closeTimeParts = closeTime.split(':');
                if (closeTimeParts.length < 2) return false;

                const closeHours = parseInt(closeTimeParts[0]);
                const closeMinutes = parseInt(closeTimeParts[1]);

                if (isNaN(closeHours) || isNaN(closeMinutes)) return false;

                // Get the end time from selectedEndTime
                const endTimeParts = selectedEndTime.time.split(':');
                const endHours = parseInt(endTimeParts[0]);
                const endMinutes = parseInt(endTimeParts[1]);

                // Check if end time matches closing time
                return endHours === closeHours && endMinutes === closeMinutes;
            }

            // Update booking summary
            function updateBookingSummary() {
                const fromDate = document.getElementById('fromDatePicker').value;
                const toDate = document.getElementById('toDatePicker').value;
                const selectedSeat = document.querySelector('input[name="selected_seat"]:checked');
                const hasExtendedTime = !document.getElementById('extendedTimeSection').classList.contains(
                    'hidden');
                const spaceType = '{{ $actualSpaceType }}';

                // Check if booking ends at closing time
                const endsAtClosing = checkIfBookingEndsAtClosing();

                // Get base price from PHP as a number
                const currentServicePrice = parseFloat({{ $service->price }}) || 0;

                // Check if we have a package upgrade
                const extendedDuration = (extendedHours * 60) + extendedMinutes;
                const upgradedPackage = findUpgradedPackage(extendedDuration);

                // Date Range
                if (fromDate && toDate) {
                    document.getElementById('summaryDateRange').textContent = fromDate + ' to ' + toDate;
                } else {
                    document.getElementById('summaryDateRange').textContent = '-';
                }

                // Main Time Range
                if (selectedStartTime && selectedEndTime) {
                    document.getElementById('summaryMainTimeRange').textContent =
                        formatTimeForSummary(selectedStartTime.datetime) + ' to ' + formatTimeForSummary(
                            selectedEndTime.datetime);
                } else {
                    document.getElementById('summaryMainTimeRange').textContent = '-';
                }

                // Main Duration
                let mainDuration = defaultDuration;
                if (selectedStartTime && selectedEndTime) {
                    mainDuration = getTimeDifferenceInMinutes(selectedStartTime.datetime, selectedEndTime.datetime);
                }
                document.getElementById('summaryMainDuration').textContent = formatDuration(mainDuration);

                // Extended Time Section
                if (hasExtendedTime && (extendedHours > 0 || extendedMinutes > 0)) {
                    document.getElementById('extendedTimeSummary').classList.remove('hidden');
                    document.getElementById('extendedPriceSummary').classList.remove('hidden');

                    // Calculate Extended Time Range (actual time display)
                    if (selectedEndTime) {
                        const extendedTimeRange = calculateExtendedTimeRange(selectedEndTime.datetime,
                            extendedDuration);
                        document.getElementById('summaryExtendedTime').textContent = extendedTimeRange;
                    } else {
                        document.getElementById('summaryExtendedTime').textContent = '-';
                    }

                    // Extended Duration
                    document.getElementById('summaryExtendedDuration').textContent = formatDuration(
                        extendedDuration);

                    // Calculate Extended Date Range
                    if (selectedEndTime) {
                        const extendedDateRange = calculateExtendedDateRange(selectedEndTime.datetime,
                            extendedDuration);
                        document.getElementById('summaryExtendedDateRange').textContent = extendedDateRange;
                    } else {
                        document.getElementById('summaryExtendedDateRange').textContent = '-';
                    }

                    // Calculate prices
                    let extendedPrice = 0;
                    let mainPriceDisplay = currentServicePrice;
                    let extendedPriceDisplay = 0;
                    let subtotal = currentServicePrice;

                    // Check for EXACT package match
                    const upgradedPackage = findUpgradedPackage(extendedDuration);

                    if (upgradedPackage && upgradedPackage.price) {
                        // When package matches, user pays the full package price
                        // For display purposes in summary, we show:
                        // Main price: current service price (what they selected)
                        // Extended price: package price - current service price
                        const packagePrice = parseFloat(upgradedPackage.price) || 0;
                        extendedPriceDisplay = packagePrice - currentServicePrice;
                        subtotal = packagePrice; // User pays the full package price

                        // Show package upgrade message
                        showPackageUpgradeMessage(upgradedPackage, currentServicePrice, extendedDuration);
                    } else {
                        // Normal calculation (no package match)
                        extendedPrice = calculateExtendedTimePriceDirect(extendedDuration);
                        extendedPriceDisplay = extendedPrice;
                        subtotal = currentServicePrice + extendedPrice;

                        // Hide any existing package upgrade message
                        hidePackageUpgradeMessage();
                    }

                    // Update price displays
                    document.getElementById('summaryMainPrice').textContent = '₱' + currentServicePrice.toFixed(2);
                    document.getElementById('summaryExtendedPrice').textContent = '₱' + extendedPriceDisplay
                        .toFixed(2);
                    document.getElementById('summarySubtotal').textContent = '₱' + subtotal.toFixed(2);
                    document.getElementById('summaryTotal').textContent = '₱' + subtotal.toFixed(2);

                } else {
                    document.getElementById('extendedTimeSummary').classList.add('hidden');
                    document.getElementById('extendedPriceSummary').classList.add('hidden');
                    hidePackageUpgradeMessage();

                    // Update price displays
                    document.getElementById('summaryMainPrice').textContent = '₱' + currentServicePrice.toFixed(2);
                    document.getElementById('summarySubtotal').textContent = '₱' + currentServicePrice.toFixed(2);
                    document.getElementById('summaryTotal').textContent = '₱' + currentServicePrice.toFixed(2);
                }

                // Seat/Room
                if (selectedSeat) {
                    const seatLabel = selectedSeat.nextElementSibling.querySelector('span').textContent;
                    document.getElementById('summarySeat').textContent = seatLabel;
                } else {
                    document.getElementById('summarySeat').textContent = '-';
                }

                updateExtendedTimeButtonVisibility(endsAtClosing);

                // Show/hide summary section
                if (fromDate || toDate || selectedStartTime || selectedEndTime || selectedSeat) {
                    document.getElementById('bookingSummary').classList.remove('hidden');
                } else {
                    document.getElementById('bookingSummary').classList.add('hidden');
                }
            }

            function updateExtendedTimeButtonVisibility(endsAtClosing) {
                const addTimeButton = document.getElementById('addExtendedTime');
                const addTimeButtonContainer = document.getElementById('addTimeButtonContainer');
                const cannotExtendMessage = document.getElementById('cannotExtendMessage');
                const extendedTimeSection = document.getElementById('extendedTimeSection');

                // If extended time section is already visible, don't change anything
                if (!extendedTimeSection.classList.contains('hidden')) {
                    return;
                }

                if (endsAtClosing) {
                    // Booking ends at closing time - hide button, show message
                    addTimeButton.classList.add('hidden');
                    cannotExtendMessage.classList.remove('hidden');
                } else {
                    // Booking doesn't end at closing time - show button, hide message
                    addTimeButton.classList.remove('hidden');
                    cannotExtendMessage.classList.add('hidden');
                }
            }

            // Helper function to format duration as hours and minutes
            function formatDuration(minutes) {
                if (minutes === 0) return '0 minutes';

                const hours = Math.floor(minutes / 60);
                const mins = minutes % 60;

                if (hours === 0) {
                    return `${mins} minute${mins !== 1 ? 's' : ''}`;
                } else if (mins === 0) {
                    return `${hours} hour${hours !== 1 ? 's' : ''}`;
                } else {
                    return `${hours} hour${hours !== 1 ? 's' : ''} : ${mins} minute${mins !== 1 ? 's' : ''}`;
                }
            }

            // HH:MM format
            function formatDurationHHMM(minutes) {
                const hours = Math.floor(minutes / 60);
                const mins = minutes % 60;
                return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
            }

            // Call this function whenever dates or seat selection changes
            document.addEventListener('DOMContentLoaded', function() {
                // Add event listeners to trigger booking check
                document.getElementById('fromDatePicker').addEventListener('change',
                    fetchAndMarkExistingBookings);
                document.getElementById('toDatePicker').addEventListener('change',
                    fetchAndMarkExistingBookings);

                // Add event listener for seat selection changes
                document.querySelectorAll('input[name="selected_seat"]').forEach(input => {
                    input.addEventListener('change', function() {
                        setTimeout(fetchAndMarkExistingBookings, 100);
                    });
                });

                // Also check when time slots are rendered
                const originalRenderTimeSlots = renderTimeSlots;
                renderTimeSlots = function() {
                    originalRenderTimeSlots();
                    setTimeout(fetchAndMarkExistingBookings, 200);
                };
            });

            // Initial state - hide both sections
            document.getElementById('seatsSection').classList.add('hidden');
            document.getElementById('timeRangeSection').classList.add('hidden');
            updateConfirmButton();
            updateExtendedTimeDisplay();
        });
    </script>
</body>

</html>