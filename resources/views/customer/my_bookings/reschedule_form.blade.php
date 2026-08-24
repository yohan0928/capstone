<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Re-schedule Booking - {{ $service->service_name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        /* Reuse all styles from booking form */
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
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .period-day {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .period-open {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .period-break {
            background-color: #fef3cd;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .period-closed {
            background-color: #f3f4f6;
            color: #6b7280;
            border: 1px solid #e5e7eb;
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

        /* Re-schedule specific styles */
        .current-booking-card {
            border-left: 4px solid #7f5539;
            background: linear-gradient(135deg, #f5f0eb 0%, #e6ddd4 100%);
        }

        .reschedule-note {
            border-left: 4px solid #ff6b6b;
            background: linear-gradient(135deg, #fff5f5 0%, #ffe3e3 100%);
        }

        .warning-badge {
            background: linear-gradient(135deg, #ff6b6b 0%, #ff8787 100%);
            color: white;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 107, 107, 0.7);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(255, 107, 107, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(255, 107, 107, 0);
            }
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
    </style>
</head>

<body class="bg-[#f5f0eb] min-h-screen">
    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-[#f5f0eb] to-[#e6ddd4] py-6">
        <div class="container mx-auto px-3">
            <div class="max-w-2xl mx-auto text-center">
                <h1 class="text-xl md:text-2xl font-bold text-[#4a3429] mb-2 leading-tight">Re-schedule Your Booking
                </h1>
                <p class="text-gray-600 text-xs md:text-sm">Select new dates and times for your booking</p>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mx-auto px-3 py-6">
        <div class="max-w-6xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column - Service Details (Fixed) -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm border border-[#e6ddd4] p-5 sticky top-5">
                        <h3 class="text-base font-bold text-[#4a3429] mb-4 flex items-center">
                            <i class="fas fa-info-circle mr-2 text-[#7f5539]"></i>Booking Details
                        </h3>

                        <!-- Warning Note -->
                        <div class="reschedule-note p-4 rounded-lg mb-4">
                            <div class="flex items-start">
                                <i class="fas fa-exclamation-triangle text-red-500 mt-0.5 mr-2"></i>
                                <div>
                                    <p class="text-sm font-medium text-red-800">Re-schedule Rules</p>
                                    <p class="text-xs text-red-700 mt-1">
                                        • You can only change date and time<br>
                                        • Service and seat cannot be changed<br>
                                        • Extended time requires additional payment<br>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Current Booking Card -->
                        <div class="current-booking-card rounded-lg p-4 mb-6">
                            <h4 class="font-semibold text-[#4a3429] mb-3 flex items-center">
                                <i class="fas fa-calendar-alt mr-2 text-[#7f5539]"></i>Current Booking
                            </h4>
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
                                        <p class="text-sm font-medium">{{ $booking->serviceCategory->service_category }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center text-gray-700">
                                    <i class="fas fa-concierge-bell text-[#7f5539] mr-3 w-4"></i>
                                    <div>
                                        <p class="text-xs text-gray-500">Service</p>
                                        <p class="text-sm font-medium">{{ $service->service_name }}</p>
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
                                    <i class="fas fa-chair text-[#7f5539] mr-3 w-4"></i>
                                    <div>
                                        <p class="text-xs text-gray-500">Seat/Room</p>
                                        <p class="text-sm font-medium">
                                            @if ($seat->room_no)
                                                Room {{ $seat->room_no }}
                                            @endif
                                            @if ($seat->seat_no)
                                                @if ($seat->room_no)
                                                    /
                                                @endif
                                                Seat {{ $seat->seat_no }}
                                            @endif
                                        </p>
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

                            <!-- Current Date/Time -->
                            <div class="mt-4 pt-4 border-t border-[#e6ddd4]">
                                <!-- Current Date -->
                                <div class="mb-3">
                                    <p class="text-xs text-gray-500 mb-1">Current Date</p>
                                    <p class="text-sm font-medium">
                                        {{ \Carbon\Carbon::parse($booking->date_start)->format('M j, Y') }}
                                        @if ($booking->date_start != $booking->date_end)
                                            - {{ \Carbon\Carbon::parse($booking->date_end)->format('M j, Y') }}
                                        @endif
                                    </p>
                                </div>

                                <!-- Current Time -->
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Current Time</p>
                                    <p class="text-sm font-medium">
                                        {{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }} -
                                        {{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }}
                                    </p>
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

                <!-- Right Column - Re-schedule Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm border border-[#e6ddd4] p-5">
                        <h2 class="text-lg font-bold text-[#4a3429] mb-5 flex items-center">
                            <i class="fas fa-calendar-alt mr-2 text-[#7f5539]"></i>Select New Booking Information
                        </h2>

                        <!-- Re-schedule Form -->
                        <form id="rescheduleForm"
                            action="{{ route('sub_three.my_bookings.reschedule.preview', $booking->uuid) }}"
                            method="POST" class="space-y-6">
                            @csrf

                            <!-- Hidden fields -->
                            <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                            <input type="hidden" name="service_id" value="{{ $service->id }}">
                            <input type="hidden" name="seat_id" value="{{ $seat->id }}">
                            <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                            <input type="hidden" name="service_category_id"
                                value="{{ $booking->service_category_id }}">

                            <!-- Dynamic fields -->
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
                                value="{{ $durationMinutes }}">
                            <input type="hidden" name="total_duration" id="hidden_total_duration"
                                value="{{ $durationMinutes }}">

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
                                    Select New Date Range
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

                            <!-- Step 2: Time Selection -->
                            <div id="timeRangeSection" class="hidden card p-4">
                                <h3 class="text-sm font-bold text-[#4a3429] mb-3 flex items-center">
                                    <span
                                        class="w-6 h-6 bg-[#b08968] text-white rounded-full flex items-center justify-center mr-2 text-xs">2</span>
                                    Select New Time Range
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

                            <!-- Step 3: Extended Time (Optional) -->
                            <div id="addTimeButtonContainer" class="pt-2">
                                <button type="button" id="addExtendedTime"
                                    class="flex items-center text-[#7f5539] hover:text-[#6b4f3c] transition duration-200 font-medium text-sm">
                                    <i class="fas fa-plus-circle mr-2"></i>
                                    Add Extended Time (Optional)
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

                            <div id="extendedTimeSection" class="hidden card p-4">
                                <div class="flex justify-between items-center mb-3">
                                    <h3 class="text-sm font-bold text-[#4a3429] flex items-center">
                                        <span
                                            class="w-6 h-6 bg-[#7f5539] text-white rounded-full flex items-center justify-center mr-2 text-xs">3</span>
                                        Extended Time (Additional Payment Required)
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
                                        <span>Extended time requires additional payment based on hourly rate.</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Summary Section -->
                            <div id="bookingSummary" class="hidden card p-5">
                                <h3 class="font-bold text-[#4a3429] mb-4 text-lg flex items-center">
                                    <i class="fas fa-receipt mr-2 text-[#7f5539]"></i>Re-schedule Summary
                                </h3>
                                <div class="space-y-3 text-sm">
                                    <!-- New Booking -->
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">New Date Range:</span>
                                        <span id="summaryDateRange" class="font-medium text-[#4a3429]">-</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">New Time Range:</span>
                                        <span id="summaryMainTimeRange" class="font-medium text-[#4a3429]">-</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Main Duration:</span>
                                        <span id="summaryMainDuration"
                                            class="font-medium text-[#4a3429]">{{ $durationMinutes }} minutes</span>
                                    </div>

                                    <!-- Extended Time Section -->
                                    <div id="extendedTimeSummary"
                                        class="hidden border-t border-[#e6ddd4] pt-3 mt-3 space-y-3">
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
                                        <span id="summaryTotal" class="text-[#7f5539] font-bold text-lg">
                                            ₱{{ number_format($service->price, 2) }}
                                        </span>
                                    </div>

                                    <div id="additionalPaymentNote"
                                        class="hidden mt-2 p-2 bg-blue-50 border border-blue-200 rounded">
                                        <p class="text-xs text-blue-700">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Extended time requires additional payment on the next page.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t border-[#e6ddd4]">
                                <a href="{{ route('sub_three.my_bookings.showMyBookings') }}"
                                    class="flex-1 px-6 py-3 border border-[#d4c4b2] text-[#4a3429] rounded-md hover:bg-[#f5f0eb] transition duration-200 text-center font-medium">
                                    <i class="fas fa-arrow-left mr-2"></i>Back to Bookings
                                </a>
                                <button type="submit" id="submitBtn"
                                    class="flex-1 px-6 py-3 bg-[#7f5539] text-white rounded-md hover:bg-[#6b4f3c] transition duration-200 font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fas fa-calendar-check mr-2"></i>Preview Re-Sched
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Extended Time Payment Modal -->
    <div id="extendedTimeModal" class="modal">
        <div class="modal-content">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-4">
                    <i class="fas fa-clock text-blue-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-[#4a3429] mb-2">Add Extended Time</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Adding extended time requires additional payment.
                </p>
                <p class="text-xs text-gray-500 mb-4">
                    You will be redirected to a payment page to complete this transaction.
                </p>
                <div class="flex gap-3 justify-center">
                    <button type="button" id="modalCancel"
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="button" id="modalConfirm"
                        class="px-4 py-2 bg-[#7f5539] text-white rounded-md hover:bg-[#6b4f3c]">
                        I Agree
                    </button>
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
            const defaultDuration = {{ $durationMinutes }};
            const hourlyRate = {{ $hourlyRate }};
            const servicePrice = {{ $service->price }};
            const servicePackages = @json($servicePackages);

            let selectedStartTime = null;
            let selectedEndTime = null;
            let extendedHours = 0;
            let extendedMinutes = 0;
            let isRendering = false;
            let renderTimeout = null;
            let isAutoAdjustingDates = false;
            let isOvernightNotificationVisible = false;
            let lastOvernightInfoData = null;
            let validationTimeout = null;

            // Modal elements
            const durationModal = document.getElementById('durationModal');
            const modalMessage = document.getElementById('modalMessage');
            const modalConfirm = document.getElementById('modalConfirm');

            // Extended Time Modal
            const extendedTimeModal = document.getElementById('extendedTimeModal');
            const modalAdditionalPrice = document.getElementById('modalAdditionalPrice');
            const modalCancel = document.getElementById('modalCancel');
            const extendedModalConfirm = document.getElementById('modalConfirm');

            // Conflict Modal elements
            const bookingConflictModal = document.getElementById('bookingConflictModal');
            const conflictModalOk = document.getElementById('conflictModalOk');

            // Extended time elements
            const extendedHoursDisplay = document.getElementById('extendedHours');
            const extendedMinutesDisplay = document.getElementById('extendedMinutes');
            const increaseHoursBtn = document.getElementById('increaseHours');
            const decreaseHoursBtn = document.getElementById('decreaseHours');
            const increaseMinutesBtn = document.getElementById('increaseMinutes');
            const decreaseMinutesBtn = document.getElementById('decreaseMinutes');

            // Initialize date pickers
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
                    enable: validDates,
                    dateFormat: "Y-m-d",
                    allowInput: true,
                    clickOpens: true,
                    onOpen: function(selectedDates, dateStr, instance) {
                        if (toDatePicker) {
                            toDatePicker.close();
                        }
                    },
                    onChange: function(selectedDates, dateStr, instance) {
                        if (!isAutoAdjustingDates) {
                            toDatePicker.set('minDate', dateStr);
                            handleDateSelection();
                            updateBookingSummary();
                            updateSubmitButton();
                        }
                    }
                });

                // Initialize to date picker
                toDatePicker = flatpickr("#toDatePicker", {
                    minDate: "today",
                    enable: validDates,
                    dateFormat: "Y-m-d",
                    allowInput: true,
                    clickOpens: true,
                    onOpen: function(selectedDates, dateStr, instance) {
                        if (fromDatePicker) {
                            fromDatePicker.close();
                        }
                    },
                    onChange: function(selectedDates, dateStr, instance) {
                        if (!isAutoAdjustingDates) {
                            handleDateSelection();
                            updateBookingSummary();
                            updateSubmitButton();
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

                // Clear time slots
                document.getElementById('startTimeSlotsContainer').innerHTML =
                    '<p class="text-gray-500 text-sm">Please select date range first</p>';
                document.getElementById('endTimeSlotsContainer').innerHTML =
                    '<p class="text-gray-500 text-sm">Please select start time first</p>';

                // Hide time range section
                document.getElementById('timeRangeSection').classList.add('hidden');

                // Hide extended time section
                document.getElementById('extendedTimeSection').classList.add('hidden');
                document.getElementById('addTimeButtonContainer').classList.remove('hidden');

                // Remove overnight notification
                isOvernightNotificationVisible = false;
                lastOvernightInfoData = null;

                // Clear any validation messages
                removeExtendedTimeValidationMessage();
                const infoMessage = document.getElementById('extendedTimeInfoMessage');
                if (infoMessage) {
                    infoMessage.remove();
                }
            }

            // Handle date selection - FIXED VERSION
            function handleDateSelection() {
                const fromDate = document.getElementById('fromDatePicker').value;
                const toDate = document.getElementById('toDatePicker').value;
                const timeRangeSection = document.getElementById('timeRangeSection');

                if (fromDate && toDate) {
                    // Reset all selections when date range changes
                    resetAllSelections();
                    timeRangeSection.classList.remove('hidden');

                    // IMPORTANT: Render time slots immediately
                    setTimeout(() => {
                        renderTimeSlots();
                    }, 50);
                } else {
                    timeRangeSection.classList.add('hidden');
                }

                updateBookingSummary();
                updateSubmitButton();
            }

            // Extended time quantity handlers
            if (increaseHoursBtn) {
                increaseHoursBtn.addEventListener('click', function() {
                    extendedHours++;
                    updateBookingSummary();
                    updateExtendedTimeDisplay();
                    debouncedValidateAndUpdateExtendedTime();
                    updateSubmitButton();

                    if (selectedStartTime && selectedEndTime) {
                        checkAndAdjustOvernightBooking();
                    }

                    updateHiddenFields();
                });
            }

            if (decreaseHoursBtn) {
                decreaseHoursBtn.addEventListener('click', function() {
                    if (extendedHours > 0) {
                        extendedHours--;
                        updateBookingSummary();
                        updateExtendedTimeDisplay();
                        debouncedValidateAndUpdateExtendedTime();
                        updateSubmitButton();

                        if (selectedStartTime && selectedEndTime) {
                            checkAndAdjustOvernightBooking();
                        }

                        updateHiddenFields();
                    }
                });
            }

            if (increaseMinutesBtn) {
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
                    updateSubmitButton();

                    if (selectedStartTime && selectedEndTime) {
                        checkAndAdjustOvernightBooking();
                    }

                    updateHiddenFields();
                });
            }

            if (decreaseMinutesBtn) {
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
                    updateSubmitButton();

                    if (selectedStartTime && selectedEndTime) {
                        checkAndAdjustOvernightBooking();
                    }

                    updateHiddenFields();
                });
            }

            // Debounced validation function
            function debouncedValidateAndUpdateExtendedTime() {
                if (validationTimeout) {
                    clearTimeout(validationTimeout);
                }

                validationTimeout = setTimeout(() => {
                    validateAndUpdateExtendedTime();
                }, 300);
            }

            function updateExtendedTimeDisplay() {
                if (extendedHoursDisplay) extendedHoursDisplay.textContent = extendedHours;
                if (extendedMinutesDisplay) extendedMinutesDisplay.textContent = extendedMinutes;

                if (decreaseHoursBtn) decreaseHoursBtn.disabled = extendedHours === 0;
                if (decreaseMinutesBtn) decreaseMinutesBtn.disabled = extendedHours === 0 && extendedMinutes === 0;
            }

            // Check if booking exceeds into next day and adjust dates accordingly
            function checkAndAdjustOvernightBooking() {
                if (!selectedStartTime) return false;

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
                    }
                } else if (isAutoAdjustingDates) {
                    // Booking no longer extends overnight, reset to original date
                    isAutoAdjustingDates = true;
                    toDatePicker.setDate(fromDate, true);
                    toDateInput.value = fromDate;
                    isAutoAdjustingDates = false;
                }

                return wouldExtendOvernight;
            }

            // Update submit button state
            function updateSubmitButton() {
                const fromDate = document.getElementById('fromDatePicker').value;
                const toDate = document.getElementById('toDatePicker').value;
                const hasExtendedTime = !document.getElementById('extendedTimeSection').classList.contains(
                'hidden');

                let isValid = true;

                // Check main booking requirements
                if (!fromDate || !toDate || !selectedStartTime || !selectedEndTime) {
                    isValid = false;
                }

                // Check extended time requirements if extended time section is visible
                if (hasExtendedTime && (extendedHours === 0 && extendedMinutes === 0)) {
                    isValid = false;
                }

                const submitBtn = document.getElementById('submitBtn');
                if (submitBtn) {
                    if (isValid) {
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                        submitBtn.classList.add('hover:bg-blue-700');
                    } else {
                        submitBtn.disabled = true;
                        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                        submitBtn.classList.remove('hover:bg-blue-700');
                    }
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

            // Render time slots based on selected dates - FIXED VERSION
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

                if (document.getElementById('extendedTimeSection')) {
                    document.getElementById('extendedTimeSection').classList.add('hidden');
                }
                if (document.getElementById('addTimeButtonContainer')) {
                    document.getElementById('addTimeButtonContainer').classList.remove('hidden');
                }

                extendedHours = 0;
                extendedMinutes = 0;
                updateExtendedTimeDisplay();

                if (!fromDate || !toDate) {
                    if (startContainer) {
                        startContainer.innerHTML =
                            '<p class="text-gray-500 text-sm">Please select date range first</p>';
                    }
                    if (endContainer) {
                        endContainer.innerHTML =
                            '<p class="text-gray-500 text-sm">Please select start time first</p>';
                    }
                    updateSubmitButton();
                    isRendering = false;
                    return;
                }

                // Render start time slots
                if (startContainer) {
                    renderDateTimeSlots(startContainer, fromDate, toDate, 'start');
                }

                // Clear end time slots
                if (endContainer) {
                    endContainer.innerHTML = '<p class="text-gray-500 text-sm">Please select start time first</p>';
                }

                updateSubmitButton();
                isRendering = false;
            }

            // Main function to render date/time slots - FIXED VERSION
            function renderDateTimeSlots(container, fromDate, toDate, type) {
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

                // Clear container first
                container.innerHTML = '';

                // Group slots by date (though there should only be one date for start slots)
                const slotsByDate = {};
                allSlots.forEach(slot => {
                    if (!slotsByDate[slot.date_key]) {
                        slotsByDate[slot.date_key] = [];
                    }
                    slotsByDate[slot.date_key].push(slot);
                });

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

            function createTimeSlotButton(container, slot, type, isSameDay) {
                // DON'T create closing_time slots for START time selection
                if (type === 'start' && slot.period_type === 'closing_time') {
                    return;
                }

                const timeButton = document.createElement('button');
                timeButton.type = 'button';

                let baseClasses =
                    'time-slot border rounded-md px-3 py-2 text-sm font-medium transition-all duration-200 flex flex-col items-center justify-center';

                // Check if this start time would exceed closing (only for START time slots)
                let wouldExceedClosing = false;
                if (type === 'start') {
                    wouldExceedClosing = checkWouldExceedClosing(slot.value, slot.date_key);
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

            // Handle time slot selection
            function handleTimeSlotSelection(button, type, date, time, timestamp, dateLabel) {
                const fullDateTime = `${date} ${time}`;

                const containerId = type === 'start' ? 'startTimeSlotsContainer' : 'endTimeSlotsContainer';
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
                        
                        // FIX: Check for overnight adjustment immediately when start time is picked
                        checkAndAdjustOvernightBooking();

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
                updateSubmitButton();
                // Update hidden fields after time selection
                updateHiddenFields();
            }

            // Render end time slots based on selected start time with duration validation
            function renderEndTimeSlots() {
                if (!selectedStartTime) return;

                const container = document.getElementById('endTimeSlotsContainer');
                const fromDate = document.getElementById('fromDatePicker').value;
                const toDate = document.getElementById('toDatePicker').value;

                if (!container) return;

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
                if (!container || !selectedStartTime) return;

                const startTimestamp = selectedStartTime.timestamp;
                const endTimeSlots = container.querySelectorAll('.time-slot');
                const availableSlots = [];

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

                // Find the exact duration end time
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
                        if (availableSlots.length > 0) {
                            const latestSlot = availableSlots[availableSlots.length - 1];
                            setTimeout(() => {
                                if (latestSlot) {
                                    latestSlot.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'center'
                                    });
                                }
                            }, 300);
                        }
                    }
                } else {
                    // Let user choose manually
                    if (availableSlots.length > 0) {
                        const latestSlot = availableSlots[availableSlots.length - 1];
                        setTimeout(() => {
                            if (latestSlot) {
                                latestSlot.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'center'
                                });
                            }
                        }, 300);
                    }
                }

                updateBookingSummary();
                updateSubmitButton();
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

            // Add extended time button
            const addExtendedTimeBtn = document.getElementById('addExtendedTime');
            if (addExtendedTimeBtn) {
                addExtendedTimeBtn.addEventListener('click', function() {
                    // Show modal first
                    showExtendedTimeModal();
                });
            }

            // Remove extended time button
            const removeExtendedTimeBtn = document.getElementById('removeExtendedTime');
            if (removeExtendedTimeBtn) {
                removeExtendedTimeBtn.addEventListener('click', function() {
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
                    updateSubmitButton();

                    if (selectedStartTime && selectedEndTime) {
                        checkAndAdjustOvernightBooking();
                    }
                });
            }

            // Modal handlers
            if (modalCancel) {
                modalCancel.addEventListener('click', function() {
                    extendedTimeModal.style.display = 'none';
                });
            }

            if (extendedModalConfirm) {
                extendedModalConfirm.addEventListener('click', function() {
                    extendedTimeModal.style.display = 'none';
                    document.getElementById('extendedTimeSection').classList.remove('hidden');
                    document.getElementById('addTimeButtonContainer').classList.add('hidden');
                    updateSubmitButton();
                });
            }

            if (conflictModalOk) {
                conflictModalOk.addEventListener('click', function() {
                    bookingConflictModal.style.display = 'none';
                    renderTimeSlots();
                });
            }

            window.addEventListener('click', function(event) {
                if (event.target === extendedTimeModal) {
                    extendedTimeModal.style.display = 'none';
                }
                if (event.target === durationModal) {
                    durationModal.style.display = 'none';
                }
                if (event.target === bookingConflictModal) {
                    bookingConflictModal.style.display = 'none';
                    renderTimeSlots();
                }
            });

            function showExtendedTimeModal() {
                const extendedDuration = (extendedHours * 60) + extendedMinutes;
                let price = 0;

                if (extendedDuration > 0) {
                    price = calculateExtendedTimePrice(extendedDuration);
                }

                if (modalAdditionalPrice) {
                    modalAdditionalPrice.textContent = '₱' + price.toFixed(2);
                }
                if (extendedTimeModal) {
                    extendedTimeModal.style.display = 'block';
                }
            }

            function calculateExtendedTimePrice(extendedDurationMinutes) {
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
                const minuteCost = fifteenMinuteBlocks * (hourlyRate / 4);

                return hourlyCost + minuteCost;
            }

            // Find if extended time qualifies for a package upgrade
            function findUpgradedPackage(extendedDurationMinutes) {
                if (!servicePackages || servicePackages.length === 0) {
                    return null;
                }

                const currentServiceDuration = {{ $durationMinutes }};
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

            function updateExtendedTimeButtonVisibility(endsAtClosing) {
                const addTimeButton = document.getElementById('addExtendedTime');
                const addTimeButtonContainer = document.getElementById('addTimeButtonContainer');
                const cannotExtendMessage = document.getElementById('cannotExtendMessage');
                const extendedTimeSection = document.getElementById('extendedTimeSection');

                // If extended time section is already visible, don't change anything
                if (extendedTimeSection && !extendedTimeSection.classList.contains('hidden')) {
                    return;
                }

                if (endsAtClosing) {
                    // Booking ends at closing time - hide button, show message
                    if (addTimeButton) addTimeButton.classList.add('hidden');
                    if (cannotExtendMessage) cannotExtendMessage.classList.remove('hidden');
                } else {
                    // Booking doesn't end at closing time - show button, hide message
                    if (addTimeButton) addTimeButton.classList.remove('hidden');
                    if (cannotExtendMessage) cannotExtendMessage.classList.add('hidden');
                }
            }

            // Update hidden fields
            function updateHiddenFields() {
                // Update date fields
                const hiddenDateFrom = document.getElementById('hidden_date_from');
                const hiddenDateTo = document.getElementById('hidden_date_to');
                if (hiddenDateFrom) hiddenDateFrom.value = document.getElementById('fromDatePicker').value;
                if (hiddenDateTo) hiddenDateTo.value = document.getElementById('toDatePicker').value;

                // Update time fields
                const hiddenBookingTime = document.getElementById('hidden_booking_time');
                const hiddenEndTime = document.getElementById('hidden_end_time');
                if (hiddenBookingTime && selectedStartTime) hiddenBookingTime.value = selectedStartTime.time;
                if (hiddenEndTime && selectedEndTime) hiddenEndTime.value = selectedEndTime.time;

                // Update extended time fields
                const hiddenAdditionalHours = document.getElementById('hidden_additional_hours');
                const hiddenAdditionalMinutes = document.getElementById('hidden_additional_minutes');
                if (hiddenAdditionalHours) hiddenAdditionalHours.value = extendedHours;
                if (hiddenAdditionalMinutes) hiddenAdditionalMinutes.value = extendedMinutes;

                const hasExtendedTime = document.getElementById('extendedTimeSection') &&
                    !document.getElementById('extendedTimeSection').classList.contains('hidden');

                if (hasExtendedTime && (extendedHours > 0 || extendedMinutes > 0)) {
                    const extendedDuration = (extendedHours * 60) + extendedMinutes;
                    const mainDuration = getTimeDifferenceInMinutes(selectedStartTime.datetime, selectedEndTime
                        .datetime);
                    const totalDuration = mainDuration + extendedDuration;

                    const hiddenExtendedDurationTotal = document.getElementById('hidden_extended_duration_total');
                    const hiddenTotalDuration = document.getElementById('hidden_total_duration');
                    if (hiddenExtendedDurationTotal) hiddenExtendedDurationTotal.value = extendedDuration;
                    if (hiddenTotalDuration) hiddenTotalDuration.value = totalDuration;

                    // Calculate extended time start/end
                    if (selectedEndTime) {
                        const mainEndDateTime = new Date(selectedEndTime.datetime);
                        const extendedEndDateTime = new Date(mainEndDateTime.getTime() + (extendedDuration * 60 *
                            1000));

                        const hiddenExtendedStartTime = document.getElementById('hidden_extended_start_time');
                        const hiddenExtendedStartDate = document.getElementById('hidden_extended_start_date');
                        const hiddenExtendedEndTime = document.getElementById('hidden_extended_end_time');
                        const hiddenExtendedEndDate = document.getElementById('hidden_extended_end_date');

                        if (hiddenExtendedStartTime) hiddenExtendedStartTime.value = selectedEndTime.time;
                        if (hiddenExtendedStartDate) hiddenExtendedStartDate.value = selectedEndTime.date;
                        if (hiddenExtendedEndTime) hiddenExtendedEndTime.value = extendedEndDateTime
                            .toLocaleTimeString('en-US', {
                                hour12: false,
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                        if (hiddenExtendedEndDate) hiddenExtendedEndDate.value = extendedEndDateTime
                            .toLocaleDateString('en-CA');
                    }

                    // Calculate prices
                    const currentServicePrice = servicePrice;
                    let extendedPrice = 0;
                    let totalPrice = currentServicePrice;

                    const upgradedPackage = findUpgradedPackage(extendedDuration);

                    if (upgradedPackage && upgradedPackage.price) {
                        // Package price (full amount)
                        totalPrice = upgradedPackage.price;
                        extendedPrice = upgradedPackage.price - currentServicePrice;
                    } else {
                        // Calculate extended time price
                        extendedPrice = calculateExtendedTimePrice(extendedDuration);
                        totalPrice = currentServicePrice + extendedPrice;
                    }

                    const hiddenAdditionalPrice = document.getElementById('hidden_additional_price');
                    const hiddenTotalPrice = document.getElementById('hidden_total_price');
                    if (hiddenAdditionalPrice) hiddenAdditionalPrice.value = extendedPrice;
                    if (hiddenTotalPrice) hiddenTotalPrice.value = totalPrice;
                } else {
                    // Reset extended time fields
                    const hiddenAdditionalPrice = document.getElementById('hidden_additional_price');
                    const hiddenTotalPrice = document.getElementById('hidden_total_price');
                    const hiddenExtendedDurationTotal = document.getElementById('hidden_extended_duration_total');
                    const hiddenTotalDuration = document.getElementById('hidden_total_duration');

                    if (hiddenAdditionalPrice) hiddenAdditionalPrice.value = 0;
                    if (hiddenTotalPrice) hiddenTotalPrice.value = servicePrice;
                    if (hiddenExtendedDurationTotal) hiddenExtendedDurationTotal.value = 0;
                    if (hiddenTotalDuration) hiddenTotalDuration.value = {{ $durationMinutes }};
                }
            }

            // Calculate time difference in minutes
            function getTimeDifferenceInMinutes(startDateTime, endDateTime) {
                const start = new Date(startDateTime);
                const end = new Date(endDateTime);
                return Math.round((end - start) / (1000 * 60));
            }

            // Validate extended time
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

                const closeTime = branchData.close_time;
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

            // Remove validation message
            function removeExtendedTimeValidationMessage() {
                const messageContainer = document.getElementById('extendedTimeValidationMessage');
                if (messageContainer) {
                    messageContainer.remove();
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
                        isOvernightNotificationVisible = false;
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
                        isOvernightNotificationVisible = false;
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
                    isOvernightNotificationVisible = false;
                    lastOvernightInfoData = null;
                }

                updateBookingSummary();
                updateSubmitButton();

                if (selectedStartTime && selectedEndTime) {
                    checkAndAdjustOvernightBooking();
                }
            }

            async function fetchAndMarkExistingBookings() {
    const fromDate = document.getElementById('fromDatePicker').value;
    const toDate = document.getElementById('toDatePicker').value;
    const selectedSeat = document.querySelector('input[name="selected_seat"]:checked');

    // For re-schedule, we already have a fixed seat from the booking
    const seatId = {{ $seat->id }};
    const bookingId = {{ $booking->id }}; // Current booking being rescheduled

    if (!fromDate || !toDate || !seatId) {
        return;
    }

    try {
        // IMPORTANT: Use your actual route for fetching existing bookings
        // You need to create this route in Laravel if it doesn't exist
        const response = await fetch('{{ route('sub_three.my_bookings.api.get.existing.bookings.reschedule') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                branch_id: {{ $branch->id }},
                service_category_id: {{ $booking->service_category_id }},
                service_name_id: {{ $service->id }},
                seat_id: seatId,
                date_start: fromDate,
                date_end: toDate,
                exclude_booking_id: bookingId // Exclude current booking from conflicts
            })
        });

        const existingBookings = await response.json();

        // Store bookings globally for validation in other functions
        window.existingBookingsData = existingBookings;

        // Mark existing booking slots as unavailable
        markExistingSlotsAsUnavailable(existingBookings);

    } catch (error) {
        console.error('Error fetching existing bookings:', error);
    }
}

// Function to mark existing booking slots as unavailable
function markExistingSlotsAsUnavailable(existingBookings) {
    if (!existingBookings || existingBookings.length === 0) return;

    // Process each existing booking
    existingBookings.forEach(booking => {
        // Check if end_time is missing. If so, block all slots for the start/end dates.
        if (!booking.end_time) {
            markAllSlotsAsBooked(booking.date_start);
            
            // If date_end exists and is different, block that too
            if (booking.date_end && booking.date_end !== booking.date_start) {
                markAllSlotsAsBooked(booking.date_end);
            }
            
            // Also check extended dates if they exist without end times
            if (booking.extended_date_start && !booking.extended_end_time) {
                markAllSlotsAsBooked(booking.extended_date_start);
                if (booking.extended_date_end && booking.extended_date_end !== booking.extended_date_start) {
                    markAllSlotsAsBooked(booking.extended_date_end);
                }
            }
            return; // Skip standard range processing for this booking
        }
        
        // Check specifically for extended time missing end time
        if (booking.extended_date_start && !booking.extended_end_time) {
            markAllSlotsAsBooked(booking.extended_date_start);
            if (booking.extended_date_end && booking.extended_date_end !== booking.extended_date_start) {
                markAllSlotsAsBooked(booking.extended_date_end);
            }
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

    allTimeSlots.forEach(slot => {
        const slotDate = slot.dataset.date;
        const slotTime = slot.dataset.value; // This is in "HH:MM" format

        if (slotDate) {
            const slotDateFormatted = new Date(slotDate + 'T00:00:00').toLocaleDateString('en-CA');
            const slotTimeMinutes = timeToMinutes(slotTime);

            // Check if this slot falls within the booked date range and time range
            if (slotDateFormatted >= formattedStartDateKey && slotDateFormatted <= formattedEndDateKey) {
                // For single day bookings
                if (formattedStartDateKey === formattedEndDateKey) {
                    if (slotTimeMinutes >= startTimeMinutes && slotTimeMinutes < endTimeMinutes) {
                        markSingleSlot(slot);
                    }
                } else {
                    // For multi-day bookings
                    if (slotDateFormatted === formattedStartDateKey) {
                        // First day - only mark slots after start time
                        if (slotTimeMinutes >= startTimeMinutes) {
                            markSingleSlot(slot);
                        }
                    } else if (slotDateFormatted === formattedEndDateKey) {
                        // Last day - only mark slots before end time
                        if (slotTimeMinutes < endTimeMinutes) {
                            markSingleSlot(slot);
                        }
                    } else {
                        // Middle days - mark all slots
                        markSingleSlot(slot);
                    }
                }
            }
        }
    });
}

// Helper function to mark ALL slots for a specific date as booked
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
    const originalLabel = slot.querySelector('.font-medium')?.textContent || slot.dataset.label || 'Time Slot';

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

// Function to check for booking conflicts during form submission
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
    const hasExtendedTime = document.getElementById('extendedTimeSection') &&
        !document.getElementById('extendedTimeSection').classList.contains('hidden');
    
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
                }
            }

            // Calculate maximum allowed extended duration without crossing closed hours
            function calculateMaxExtendedDuration() {
                if (!selectedEndTime) return 0;

                const mainEndDateTime = new Date(selectedEndTime.datetime);
                const endDateKey = mainEndDateTime.toLocaleDateString('en-CA');
                const branchData = timeSlots[endDateKey];

                if (!branchData) return 24 * 60;

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

            // Adjust extended time to maximum allowed
            function adjustExtendedTimeToMax() {
                const maxAllowedDuration = calculateMaxExtendedDuration();

                if (maxAllowedDuration > 0) {
                    extendedHours = Math.floor(maxAllowedDuration / 60);
                    extendedMinutes = maxAllowedDuration % 60;

                    updateExtendedTimeDisplay();
                    updateBookingSummary();
                    updateSubmitButton();

                    removeExtendedTimeValidationMessage();
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
                if (currentKey === lastOvernightInfoData && isOvernightNotificationVisible) {
                    return;
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
                isOvernightNotificationVisible = true;

                // Event listener for close button
                setTimeout(() => {
                    const closeBtn = document.getElementById('closeExtendedInfoMessage');
                    if (closeBtn) {
                        closeBtn.addEventListener('click', function() {
                            const message = document.getElementById('extendedTimeInfoMessage');
                            if (message) {
                                message.remove();
                                isOvernightNotificationVisible = false;
                            }
                        });
                    }
                }, 100);

                // Auto-remove after 8 seconds
                setTimeout(() => {
                    const infoMessage = document.getElementById('extendedTimeInfoMessage');
                    if (infoMessage) {
                        infoMessage.remove();
                    }
                }, 8000);
            }

            // Update booking summary
            function updateBookingSummary() {
                const fromDate = document.getElementById('fromDatePicker').value;
                const toDate = document.getElementById('toDatePicker').value;
                const hasExtendedTime = document.getElementById('extendedTimeSection') &&
                    !document.getElementById('extendedTimeSection').classList.contains('hidden');

                // Check if booking ends at closing time
                const endsAtClosing = checkIfBookingEndsAtClosing();

                // Date Range
                const summaryDateRange = document.getElementById('summaryDateRange');
                if (summaryDateRange) {
                    if (fromDate && toDate) {
                        summaryDateRange.textContent = formatDisplayDate(fromDate) + ' to ' + formatDisplayDate(
                            toDate);
                    } else {
                        summaryDateRange.textContent = '-';
                    }
                }

                // Main Time Range
                const summaryMainTimeRange = document.getElementById('summaryMainTimeRange');
                if (summaryMainTimeRange) {
                    if (selectedStartTime && selectedEndTime) {
                        summaryMainTimeRange.textContent =
                            formatTimeForSummary(selectedStartTime.datetime) + ' to ' + formatTimeForSummary(
                                selectedEndTime.datetime);
                    } else {
                        summaryMainTimeRange.textContent = '-';
                    }
                }

                // Extended Time Section
                const extendedDuration = (extendedHours * 60) + extendedMinutes;
                const extendedTimeSummary = document.getElementById('extendedTimeSummary');
                const extendedPriceSummary = document.getElementById('extendedPriceSummary');
                const additionalPaymentNote = document.getElementById('additionalPaymentNote');
                const submitBtn = document.getElementById('submitBtn');

                if (hasExtendedTime && extendedDuration > 0) {
                    if (extendedTimeSummary) extendedTimeSummary.classList.remove('hidden');
                    if (extendedPriceSummary) extendedPriceSummary.classList.remove('hidden');
                    if (additionalPaymentNote) additionalPaymentNote.classList.remove('hidden');

                    const summaryExtendedTime = document.getElementById('summaryExtendedTime');
                    const summaryExtendedDuration = document.getElementById('summaryExtendedDuration');
                    if (summaryExtendedTime && selectedEndTime) {
                        summaryExtendedTime.textContent =
                            formatTimeForSummary(selectedEndTime.datetime) + ' to ' + calculateExtendedEndTime(
                                selectedEndTime.datetime, extendedDuration);
                    }
                    if (summaryExtendedDuration) {
                        summaryExtendedDuration.textContent = formatDuration(extendedDuration);
                    }

                    // Calculate extended price
                    const upgradedPackage = findUpgradedPackage(extendedDuration);
                    let extendedPrice = 0;
                    let subtotal = servicePrice;

                    if (upgradedPackage && upgradedPackage.price) {
                        // When package matches, user pays the full package price
                        const packagePrice = parseFloat(upgradedPackage.price) || 0;
                        extendedPrice = packagePrice - servicePrice;
                        subtotal = packagePrice;
                    } else {
                        // Normal calculation (no package match)
                        extendedPrice = calculateExtendedTimePrice(extendedDuration);
                        subtotal = servicePrice + extendedPrice;
                    }

                    const summaryExtendedPrice = document.getElementById('summaryExtendedPrice');
                    const summarySubtotal = document.getElementById('summarySubtotal');
                    const summaryTotal = document.getElementById('summaryTotal');

                    if (summaryExtendedPrice) summaryExtendedPrice.textContent = '₱' + extendedPrice.toFixed(2);
                    if (summarySubtotal) summarySubtotal.textContent = '₱' + subtotal.toFixed(2);
                    if (summaryTotal) summaryTotal.textContent = '₱' + subtotal.toFixed(2);

                    // Change button text to Proceed to Payment
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="fas fa-credit-card mr-2"></i>Proceed to Payment';
                    }

                } else {
                    if (extendedTimeSummary) extendedTimeSummary.classList.add('hidden');
                    if (extendedPriceSummary) extendedPriceSummary.classList.add('hidden');
                    if (additionalPaymentNote) additionalPaymentNote.classList.add('hidden');

                    const summarySubtotal = document.getElementById('summarySubtotal');
                    const summaryTotal = document.getElementById('summaryTotal');

                    if (summarySubtotal) summarySubtotal.textContent = '₱' + servicePrice.toFixed(2);
                    if (summaryTotal) summaryTotal.textContent = '₱' + servicePrice.toFixed(2);

                    // Revert button text to Preview Re-Sched
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="fas fa-calendar-check mr-2"></i>Preview Re-Sched';
                    }
                }

                // Main Price (always show)
                const summaryMainPrice = document.getElementById('summaryMainPrice');
                if (summaryMainPrice) summaryMainPrice.textContent = '₱' + servicePrice.toFixed(2);

                updateExtendedTimeButtonVisibility(endsAtClosing);

                // Show/hide summary section
                const bookingSummary = document.getElementById('bookingSummary');
                if (bookingSummary) {
                    if (fromDate || toDate || selectedStartTime || selectedEndTime) {
                        bookingSummary.classList.remove('hidden');
                    } else {
                        bookingSummary.classList.add('hidden');
                    }
                }
            }

            // Helper function to calculate extended end time
            function calculateExtendedEndTime(endDateTime, durationMinutes) {
                const end = new Date(endDateTime);
                const extendedEnd = new Date(end.getTime() + (durationMinutes * 60 * 1000));
                return extendedEnd.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });
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

            // Helper function to format date for display
            function formatDisplayDate(dateString) {
                if (!dateString) return '';
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric'
                });
            }

            // Helper function to format time for summary
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

            // Form submission
            const rescheduleForm = document.getElementById('rescheduleForm');
if (rescheduleForm) {
    rescheduleForm.addEventListener('submit', async function(e) {
        e.preventDefault(); // Stop submission immediately to validate
        
        const fromDate = document.getElementById('fromDatePicker').value;
        const toDate = document.getElementById('toDatePicker').value;
        const hasExtendedTime = document.getElementById('extendedTimeSection') &&
            !document.getElementById('extendedTimeSection').classList.contains('hidden');

        // Additional validation for extended time
        if (hasExtendedTime && (extendedHours === 0 && extendedMinutes === 0)) {
            alert('Please add extended time or remove the extended time section.');
            return;
        }

        // Check all required fields
        if (!fromDate || !toDate || !selectedStartTime || !selectedEndTime) {
            alert('Please complete all required fields: date range and time range');
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
}

            document.getElementById('fromDatePicker').addEventListener('change', function() {
    setTimeout(() => {
        fetchAndMarkExistingBookings();
    }, 300);
});

document.getElementById('toDatePicker').addEventListener('change', function() {
    setTimeout(() => {
        fetchAndMarkExistingBookings();
    }, 300);
});

const originalRenderTimeSlots = renderTimeSlots;
renderTimeSlots = function() {
    originalRenderTimeSlots();
    setTimeout(fetchAndMarkExistingBookings, 200);
};

            // Initialize
            updateSubmitButton();

            setTimeout(() => {
    const fromDate = document.getElementById('fromDatePicker').value;
    const toDate = document.getElementById('toDatePicker').value;
    if (fromDate && toDate) {
        fetchAndMarkExistingBookings();
    }
}, 500);

            // Debug: Log timeSlots to console to verify data is loaded
            console.log('Time slots loaded:', Object.keys(timeSlots).length, 'days available');
            console.log('Sample date slots:', timeSlots[Object.keys(timeSlots)[0]]);
        });
    </script>
</body>

</html>