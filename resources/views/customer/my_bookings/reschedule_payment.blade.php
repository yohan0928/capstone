<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Re-schedule Payment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Include Flatpickr if needed for other pages, though this page is primarily payment -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body class="bg-[#f5f0eb] min-h-screen">
    <!-- PHP Logic Block -->
    <!-- This block handles data preparation. In a real Blade file, this runs on the server. -->
    <div style="display:none;">
        @php
            // Prepare COMPLETE booking details for payment processing
            $paymentBookingDetails = [];

            // Branch data
            if (isset($booking)) {
                $paymentBookingDetails['branch'] = [
                    'id' => $booking->branch_id,
                    'uuid' => $booking->branch->uuid ?? '',
                ];
            }

            // Service category data
            if (isset($booking)) {
                $paymentBookingDetails['service_category'] = [
                    'id' => $booking->service_category_id,
                    'uuid' => $booking->serviceCategory->uuid ?? '',
                ];
            }

            // Service name data
            if (isset($booking)) {
                $paymentBookingDetails['service_name'] = [
                    'id' => $booking->service_name_id,
                    'uuid' => $booking->serviceName->uuid ?? '',
                    'price' => $booking->serviceName->price ?? 0,
                    'time_duration' => $booking->serviceName->time_duration ?? '',
                ];
            }

            // Seat data
            if (isset($booking) && $booking->seat) {
                $paymentBookingDetails['seat'] = [
                    'id' => $booking->seat_id,
                ];
            }

            // CRITICAL: Add ALL booking date/time information from reschedule data
            $paymentBookingDetails['date_from'] = $rescheduleData['date_from'] ?? null;
            $paymentBookingDetails['date_to'] = $rescheduleData['date_to'] ?? null;
            $paymentBookingDetails['booking_time'] = $rescheduleData['booking_time'] ?? null;
            $paymentBookingDetails['end_time'] = $rescheduleData['end_time'] ?? null;

            // Duration and pricing
            $paymentBookingDetails['main_duration'] = $rescheduleData['main_duration'] ?? 0;
            $paymentBookingDetails['total_duration'] = $rescheduleData['total_duration'] ?? 0;
            $paymentBookingDetails['additional_hours'] = $rescheduleData['additional_hours'] ?? 0;
            $paymentBookingDetails['additional_minutes'] = $rescheduleData['additional_minutes'] ?? 0;
            $paymentBookingDetails['additional_price'] = $rescheduleData['additional_price'] ?? 0;
            $paymentBookingDetails['total_price'] = $rescheduleData['additional_price'] ?? 0; // Only extended time price for reschedule

            // Extended time fields
            $paymentBookingDetails['extended_start_time'] = $rescheduleData['extended_start_time'] ?? null;
            $paymentBookingDetails['extended_end_time'] = $rescheduleData['extended_end_time'] ?? null;
            $paymentBookingDetails['extended_date_start'] = $rescheduleData['extended_start_date'] ?? null;
            $paymentBookingDetails['extended_date_end'] = $rescheduleData['extended_end_date'] ?? null;
            $paymentBookingDetails['extended_duration_total'] = $rescheduleData['extended_duration_total'] ?? 0;

            // Add booking ID for reference
            $paymentBookingDetails['booking_id'] = $booking->id;
            $paymentBookingDetails['is_reschedule'] = true;
            $paymentBookingDetails['original_booking_data'] = $rescheduleData['original_booking_data'] ?? [];

            // Encode the booking details for the hidden input
            $encodedBookingDetails = base64_encode(json_encode($paymentBookingDetails));

            // Get the total amount for payment options
            $additionalPrice = $rescheduleData['additional_price'] ?? 0;
            $servicePrice = $booking->serviceName->price ?? 0;
            $totalPrice = $servicePrice + $additionalPrice;
        @endphp
    </div>

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

        .additional-time-section {
            border-left: 4px solid #7f5539;
            background: linear-gradient(135deg, #f5f0eb 0%, #e6ddd4 100%);
        }

        .payment-option {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid #e6ddd4;
        }

        .payment-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(127, 85, 57, 0.1);
        }

        .payment-option.selected {
            border-color: #7f5539;
            background-color: #f5f0eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(127, 85, 57, 0.15);
        }

        .file-upload-area {
            transition: all 0.3s ease;
            min-height: 120px;
            border: 2px dashed #d4c4b2;
            background-color: #f5f0eb;
        }

        .file-upload-area:hover {
            border-color: #7f5539;
            background-color: #e6ddd4;
        }

        .file-upload-area.drag-over {
            border-color: #7f5539;
            background-color: #e6ddd4;
            transform: scale(1.02);
        }

        .file-upload-area.has-file {
            border-color: #7f5539;
            background-color: #f5f0eb;
        }

        .choose-file-btn {
            background-color: #7f5539;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .choose-file-btn:hover {
            background-color: #6b4f3c;
            transform: translateY(-2px);
        }

        .file-preview-container {
            background-color: white;
            border: 1px solid #e6ddd4;
            border-radius: 0.375rem;
        }

        .file-preview-icon {
            color: #7f5539;
        }

        .total-price {
            font-weight: bold;
            color: #4a3429;
            font-size: 1.25rem;
        }

        .summary-item {
            border-bottom: 1px solid #e6ddd4;
            padding: 0.5rem 0;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .icon-box {
            background-color: #f5f0eb;
            border-radius: 0.375rem;
            color: #7f5539;
        }

        /* Modal styles - FIXED POSITIONING */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            overflow: hidden;
        }

        .modal-content {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* Image preview modal */
        .image-modal-content {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: transparent;
            padding: 1rem;
            border-radius: 0.5rem;
            max-width: 90%;
            max-height: 90%;
            width: auto;
        }

        .modal-image {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        /* MODAL CONTROLS AT BOTTOM OF PAGE */
        .modal-controls {
            position: fixed;
            bottom: 10px;
            left: 0;
            width: 100%;
            display: flex;
            justify-content: center;
            padding: 0 10px;
            z-index: 10001;
            pointer-events: none;
        }

        .modal-controls-content {
            background: rgba(255, 255, 255, 0.95);
            padding: 8px 12px;
            border-radius: 8px;
            display: flex;
            gap: 6px;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.15);
            max-width: 95%;
            overflow: hidden;
            pointer-events: auto;
            flex-wrap: nowrap;
            align-items: center;
        }

        .modal-control-btn {
            background: #7f5539;
            color: white;
            border: none;
            padding: 6px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 1;
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .modal-control-btn i {
            margin-right: 4px;
            font-size: 11px;
        }

        .modal-control-btn:hover {
            background: #6b4f3c;
            transform: translateY(-1px);
        }

        .modal-control-btn:active {
            transform: translateY(0);
        }

        /* Responsive adjustments */
        @media (max-width: 640px) {
            .modal-controls {
                bottom: 5px;
                padding: 0 5px;
            }

            .modal-controls-content {
                padding: 6px 8px;
                gap: 4px;
                border-radius: 6px;
                max-width: 98%;
            }

            .modal-control-btn {
                padding: 5px 8px;
                font-size: 11px;
                min-width: 60px;
            }

            .modal-control-btn i {
                margin-right: 3px;
                font-size: 10px;
            }
        }

        @media (max-width: 480px) {
            .modal-controls-content {
                flex-wrap: wrap;
                justify-content: center;
            }

            .modal-control-btn {
                min-width: 70px;
                padding: 4px 6px;
                font-size: 10px;
            }

            .modal-control-btn span {
                display: none;
            }

            .modal-control-btn i {
                margin-right: 0;
                font-size: 12px;
            }
        }

        @media (max-width: 360px) {
            .modal-control-btn {
                min-width: 50px;
                padding: 3px 5px;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translate(-50%, -40%);
            }

            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }

        .modal-content {
            animation: fadeIn 0.3s ease-out;
        }

        /* Status badges */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-badge.success {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-badge.info {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-badge.warning {
            background-color: #fef3cd;
            color: #92400e;
        }

        /* QR code styling */
        .qr-code-container {
            border: 1px solid #e6ddd4;
            border-radius: 0.5rem;
            background: white;
            padding: 1rem;
        }

        /* Form input styling */
        .form-input {
            border: 1px solid #d4c4b2;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            width: 100%;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #7f5539;
            box-shadow: 0 0 0 3px rgba(127, 85, 57, 0.1);
        }

        /* Image preview styling */
        .image-preview {
            max-width: 100%;
            max-height: 200px;
            object-fit: contain;
            border-radius: 0.375rem;
            border: 1px solid #e6ddd4;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .image-preview:hover {
            transform: scale(1.02);
        }

        /* Sticky column */
        @media (min-width: 1024px) {
            .sticky-column-lg {
                position: sticky;
                top: 1rem;
                align-self: flex-start;
                height: fit-content;
            }
        }

        /* Payment method radio button styling */
        .payment-method-item {
            position: relative;
        }

        .payment-method-radio {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
        }

        /* Prevent choose file button from opening when clicking image */
        .image-preview-container {
            position: relative;
        }

        .image-preview-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            cursor: zoom-in;
            z-index: 1;
        }

        .opacity-50 {
            opacity: 0.5;
        }

        .cursor-not-allowed {
            cursor: not-allowed;
        }

        /* Smooth transition for payment form */
        #paymentFormSection {
            transition: all 0.3s ease;
        }

        #paymentFormSection.hidden {
            display: none;
        }

        /* Re-schedule specific styles */
        .timeline-change {
            position: relative;
            padding-left: 30px;
        }

        .timeline-change::before {
            content: "→";
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f5539;
            font-weight: bold;
        }

        .change-indicator {
            background: linear-gradient(135deg, #f5f0eb 0%, #e6ddd4 100%);
            border-left: 4px solid #9c6644;
        }

        /* Pay Later specific styling */
        .pay-later-option {
            background: linear-gradient(135deg, #fef3cd 0%, #fde68a 100%);
        }

        .pay-later-option.selected {
            border-color: #f59e0b;
            background: linear-gradient(135deg, #fef3cd 0%, #fde68a 100%);
        }

        .pay-later-option .icon-box {
            background-color: #fef3cd;
            color: #d97706;
        }
    </style>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-[#f5f0eb] to-[#e6ddd4] py-6">
        <div class="container mx-auto px-3">
            <div class="max-w-2xl mx-auto text-center">
                <h1 class="text-xl md:text-2xl font-bold text-[#4a3429] mb-2 leading-tight">Re-schedule Payment</h1>
                <p class="text-gray-600 text-xs md:text-sm">Complete payment for extended time in your re-scheduled booking
                </p>
            </div>
        </div>
    </section>

    <div class="container mx-auto px-3 py-6">
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Left Column - Booking Preview Card -->
            <div class="lg:w-1/2 sticky-column-lg">
                <div class="card">
                    <div class="bg-[#7f5539] text-white p-6 text-center rounded-t-lg">
                        <i class="fas fa-calendar-alt text-4xl mb-3"></i>
                        <h1 class="text-2xl font-bold">Re-schedule Summary</h1>
                        <p class="text-[#f5f0eb]">Review your booking changes</p>
                    </div>

                    <div class="p-6">
                        <!-- Original vs New Booking Comparison -->
                        <div class="bg-gray-50 rounded-lg p-6 mb-6">
                            <h3 class="text-xl font-semibold text-gray-800 mb-4">Booking Changes</h3>
                            <div class="space-y-6">
                                <!-- Date Change -->
                                <div class="change-indicator rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-medium text-gray-700">Booking Date</h4>
                                        <span class="text-sm bg-[#7f5539] text-white px-2 py-1 rounded">Changed</span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-sm text-gray-500 mb-1">Original</p>
                                            <p class="font-medium">
                                                {{ \Carbon\Carbon::parse($rescheduleData['original_booking_data']['date_from'])->format('M j, Y') }}
                                            </p>
                                        </div>
                                        <div class="timeline-change">
                                            <p class="text-sm text-gray-500 mb-1">New Date</p>
                                            <p class="font-medium text-[#7f5539]">
                                                {{ \Carbon\Carbon::parse($rescheduleData['date_from'])->format('M j, Y') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Time Change -->
                                <div class="change-indicator rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-medium text-gray-700">Booking Time</h4>
                                        <span class="text-sm bg-[#7f5539] text-white px-2 py-1 rounded">Changed</span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-sm text-gray-500 mb-1">Original Time</p>
                                            <p class="font-medium">
                                                {{ \Carbon\Carbon::parse($rescheduleData['original_booking_data']['start_time'])->format('g:i A') }}
                                                -
                                                {{ \Carbon\Carbon::parse($rescheduleData['original_booking_data']['end_time'])->format('g:i A') }}
                                            </p>
                                        </div>
                                        <div class="timeline-change">
                                            <p class="text-sm text-gray-500 mb-1">New Time</p>
                                            <p class="font-medium text-[#7f5539]">
                                                {{ \Carbon\Carbon::parse($rescheduleData['booking_time'])->format('g:i A') }}
                                                -
                                                {{ \Carbon\Carbon::parse($rescheduleData['end_time'])->format('g:i A') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Extended Time Section -->
                                @if ($rescheduleData['extended_duration'] > 0)
                                    <div class="additional-time-section rounded-lg p-6">
                                        <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                                            <i class="fas fa-plus-circle text-purple-500 mr-2"></i>
                                            Additional Time Added
                                        </h3>
                                        <div class="space-y-3">
                                            <div class="flex justify-between">
                                                <span class="text-gray-600 font-medium">Extended Duration:</span>
                                                <span class="font-semibold">
                                                    {{ floor($rescheduleData['extended_duration'] / 60) }}h
                                                    {{ $rescheduleData['extended_duration'] % 60 }}m
                                                </span>
                                            </div>
                                            @if ($rescheduleData['extended_start_time'])
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600 font-medium">Extended Start:</span>
                                                    <span class="font-semibold">
                                                        {{ \Carbon\Carbon::parse($rescheduleData['extended_start_time'])->format('g:i A') }}
                                                    </span>
                                                </div>
                                            @endif
                                            @if ($rescheduleData['extended_end_time'])
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600 font-medium">Extended End:</span>
                                                    <span class="font-semibold">
                                                        {{ \Carbon\Carbon::parse($rescheduleData['extended_end_time'])->format('g:i A') }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Customer Information -->
                        <div class="bg-gray-50 rounded-lg p-6 mb-6">
                            <h3 class="text-xl font-semibold text-gray-800 mb-4">Customer Information</h3>
                            <div class="space-y-4 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Name:</span>
                                    <span class="font-medium">
                                        {{ auth()->guard('customer')->user()->first_name }}
                                        {{ auth()->guard('customer')->user()->last_name }}
                                    </span>
                                </div>

                                <div class="flex justify-between">
                                    <span class="text-gray-600">Email:</span>
                                    <span class="font-medium">
                                        {{ auth()->guard('customer')->user()->email }}
                                    </span>
                                </div>

                                <div class="flex justify-between">
                                    <span class="text-gray-600">Phone:</span>
                                    <span class="font-medium">
                                        {{ auth()->guard('customer')->user()->contact_no }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Extended Time Pricing -->
                        @if ($rescheduleData['extended_duration'] > 0)
                            <div class="bg-gray-50 rounded-lg p-6">
                                <h3 class="text-xl font-semibold text-gray-800 mb-4">Pricing</h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Original Service Price:</span>
                                        <span class="font-medium">
                                            ₱{{ number_format($servicePrice, 2) }}
                                        </span>
                                    </div>

                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Additional Time Price:</span>
                                        <span class="font-medium text-green-600">
                                            +₱{{ number_format($additionalPrice, 2) }}
                                        </span>
                                    </div>

                                    <div class="flex justify-between border-t border-gray-200 pt-2">
                                        <span class="text-lg font-semibold text-gray-800">Total to Pay:</span>
                                        <span class="text-lg font-bold text-green-600">
                                            ₱{{ number_format($additionalPrice, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="bg-green-50 rounded-lg p-6">
                                <div class="text-center">
                                    <i class="fas fa-check-circle text-green-500 text-4xl mb-3"></i>
                                    <h3 class="text-lg font-semibold text-gray-800 mb-2">No Additional Payment Required</h3>
                                    <p class="text-gray-600 text-sm">Your re-schedule doesn't include extended time. You can
                                        proceed without payment.</p>
                                </div>
                            </div>
                        @endif

                        <!-- Action Buttons -->
                        @if ($rescheduleData['extended_duration'] > 0)
                            <div class="pt-6 border-t border-gray-200">
                                <div class="text-center">
                                    <p class="text-sm text-gray-600 mb-4">Choose a payment option to confirm your re-scheduled booking</p>
                                </div>
                            </div>
                        @else
                            <div class="pt-6 border-t border-gray-200">
                                <form action="{{ route('sub_three.my_bookings.reschedule.process', $booking->uuid) }}"
                                    method="POST">
                                    @csrf
                                    <input type="hidden" name="payment_method" value="3"> <!-- Pay Later -->
                                    <input type="hidden" name="payment_status" value="2"> <!-- Unpaid -->
                                    <input type="hidden" name="booking_details" value="{{ $encodedBookingDetails }}">
                                    <button type="submit"
                                        class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-md font-semibold transition duration-300 flex items-center justify-center">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        Confirm Re-schedule Without Payment
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Column - Payment Form (Only shows when there's extended time) -->
            @if ($rescheduleData['extended_duration'] > 0)
                <div class="lg:w-1/2 space-y-6">
                    <!-- Payment Options Section -->
                    <div class="card">
                        <div class="bg-[#7f5539] text-white p-6 text-center rounded-t-lg">
                            <i class="fas fa-credit-card text-4xl mb-3"></i>
                            <h1 class="text-2xl font-bold">Choose Payment Option</h1>
                            <p class="text-[#f5f0eb]">Select how you want to pay for the extended time</p>
                        </div>

                        <div class="p-6">
                            <!-- Payment Amount Display -->
                            <div class="bg-[#f5f0eb] rounded-lg p-6 mb-6 text-center">
                                <p class="text-gray-600 mb-2">Amount to Pay</p>
                                <p class="text-3xl font-bold text-[#7f5539]">
                                    ₱{{ number_format($additionalPrice, 2) }}
                                </p>
                                <p class="text-sm text-gray-500 mt-2">
                                    {{ floor($rescheduleData['extended_duration'] / 60) }} hours
                                    {{ $rescheduleData['extended_duration'] % 60 }} minutes
                                </p>
                            </div>

                            <!-- Payment Options -->
                            <div class="grid grid-cols-1 gap-6 mb-6">
                                <!-- Option 1: Pay Now with GCash -->
                                <div class="payment-option p-6 rounded-lg bg-white cursor-pointer"
                                    data-payment-method="1" 
                                    data-payment-status="1"
                                    data-payment-type="pay_now"
                                    data-payment-amount="{{ $additionalPrice }}"
                                    data-payment-display="Pay Now with GCash: ₱{{ number_format($additionalPrice, 2) }}">
                                    <div class="text-center">
                                        <div class="w-16 h-16 mx-auto mb-4 icon-box flex items-center justify-center">
                                            <i class="fas fa-mobile-alt text-[#7f5539] text-2xl"></i>
                                        </div>
                                        <h3 class="text-lg font-semibold text-[#4a3429] mb-2">Pay Now with GCash</h3>
                                        <p class="text-[#7f5539] font-bold text-xl mb-3">
                                            ₱{{ number_format($additionalPrice, 2) }}
                                        </p>
                                        <div class="text-left text-sm text-gray-600 space-y-1">
                                            <div class="flex items-center">
                                                <i class="fas fa-check text-[#7f5539] mr-2"></i>
                                                <span>Extended Time: ₱{{ number_format($additionalPrice, 2) }}</span>
                                            </div>
                                            <div class="flex items-center">
                                                <i class="fas fa-check text-[#7f5539] mr-2"></i>
                                                <span>Payment Status: Paid immediately</span>
                                            </div>
                                            <div class="flex items-center">
                                                <i class="fas fa-check text-[#7f5539] mr-2"></i>
                                                <span>Booking Status: Confirmed immediately</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Option 2: Pay Later -->
                                <div class="payment-option pay-later-option p-6 rounded-lg bg-white cursor-pointer"
                                    data-payment-method="3" 
                                    data-payment-status="2"
                                    data-payment-type="pay_later"
                                    data-payment-amount="{{ $additionalPrice }}"
                                    data-payment-display="Pay Later: ₱{{ number_format($additionalPrice, 2) }}">
                                    <div class="text-center">
                                        <div class="w-16 h-16 mx-auto mb-4 icon-box flex items-center justify-center">
                                            <i class="fas fa-clock text-[#d97706] text-2xl"></i>
                                        </div>
                                        <h3 class="text-lg font-semibold text-[#4a3429] mb-2">Pay Later</h3>
                                        <p class="text-[#d97706] font-bold text-xl mb-3">
                                            ₱{{ number_format($additionalPrice, 2) }}
                                        </p>
                                        <div class="text-left text-sm text-gray-600 space-y-1">
                                            <div class="flex items-center">
                                                <i class="fas fa-clock text-[#d97706] mr-2"></i>
                                                <span>Extended Time: ₱{{ number_format($additionalPrice, 2) }}</span>
                                            </div>
                                            <div class="flex items-center">
                                                <i class="fas fa-clock text-[#d97706] mr-2"></i>
                                                <span>Payment Status: Unpaid (Pay at branch)</span>
                                            </div>
                                            <div class="flex items-center">
                                                <i class="fas fa-clock text-[#d97706] mr-2"></i>
                                                <span>Booking Status: Confirmed but payment pending</span>
                                            </div>
                                            <div class="mt-2 text-xs text-[#92400e]">
                                                <i class="fas fa-info-circle mr-1"></i> You can pay this amount when you visit the branch
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- GCash Payment Form Section (Initially hidden) -->
                            <div id="gcashPaymentFormSection" class="hidden">
                                <form action="{{ route('sub_three.my_bookings.reschedule.process', $booking->uuid) }}"
                                    method="POST" enctype="multipart/form-data" class="space-y-6" id="gcashPaymentForm">
                                    @csrf

                                    <!-- IMPORTANT: Add booking_details as hidden field -->
                                    <input type="hidden" name="booking_details" value="{{ $encodedBookingDetails }}">

                                    <!-- Payment Type (will be set by JS) -->
                                    <input type="hidden" name="payment_type" id="paymentTypeInput" value="full">
                                    <input type="hidden" name="payment_method" id="paymentMethodInput" value="1">
                                    <input type="hidden" name="payment_status" id="paymentStatusInput" value="1">

                                    <!-- GCash Reference Number -->
                                    <div>
                                        <label class="block text-sm font-medium text-[#4a3429] mb-2">GCash Reference
                                            Number</label>
                                        <input type="text" name="gcash_ref_no" required
                                            placeholder="Enter GCash reference number" class="form-input">
                                        <p class="text-sm text-gray-500 mt-1">Find this in your GCash transaction history
                                        </p>
                                    </div>

                                    {{-- Notes --}}
                                    <div>
                                        <label class="block text-sm font-medium text-[#4a3429] mb-2">Additional Notes
                                            (Optional)</label>
                                        <textarea name="notes" placeholder="Add any additional notes about this payment..." rows="3"
                                            class="form-input resize-none"></textarea>
                                        <p class="text-sm text-gray-500 mt-1">You can add special requests or notes about
                                            your re-schedule</p>
                                    </div>

                                    <!-- GCash Receipt Upload -->
                                    <div>
                                        <label class="block text-sm font-medium text-[#4a3429] mb-2">GCash Receipt
                                            Screenshot</label>
                                        <div class="file-input-wrapper">
                                            <input type="file" name="gcash_receipt_img" accept="image/*" required
                                                class="hidden" id="paymentFileInput">

                                            <div id="paymentUploadArea"
                                                class="file-upload-area rounded-lg p-6 text-center cursor-pointer flex flex-col items-center justify-center">
                                                <div id="paymentDefaultState" class="text-center">
                                                    <div
                                                        class="w-12 h-12 mx-auto mb-4 icon-box flex items-center justify-center">
                                                        <i class="fas fa-cloud-upload-alt text-[#7f5539] text-xl"></i>
                                                    </div>
                                                    <p class="text-[#4a3429] mb-2 font-medium">Upload your GCash receipt
                                                    </p>
                                                    <p class="text-xs text-gray-500 mb-4">Supports: JPG, PNG, GIF • Max:
                                                        2MB</p>
                                                    <button type="button" class="choose-file-btn">
                                                        <i class="fas fa-folder-open mr-2"></i>Choose File
                                                    </button>
                                                </div>

                                                <div id="paymentImagePreviewState" class="hidden text-center w-full">
                                                    <div class="mb-4 image-preview-container">
                                                        <img id="paymentImagePreview" src=""
                                                            alt="Receipt Preview" class="image-preview mx-auto">
                                                        <div class="image-preview-overlay" data-action="zoom"></div>
                                                    </div>
                                                    <div class="flex justify-center gap-3">
                                                        <button type="button" class="choose-file-btn">
                                                            <i class="fas fa-sync-alt mr-2"></i>Change File
                                                        </button>
                                                        <button type="button"
                                                            class="choose-file-btn bg-red-600 hover:bg-red-700"
                                                            id="paymentRemoveImageBtn">
                                                            <i class="fas fa-trash mr-2"></i>Remove
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="paymentFileError" class="text-red-500 text-sm mt-2 hidden"></div>
                                        </div>
                                    </div>

                                    <!-- Payment Instructions with QR Code -->
                                    <div class="bg-[#f5f0eb] border border-[#e6ddd4] rounded-lg p-4">
                                        <h4 class="font-semibold text-[#4a3429] mb-3 flex items-center">
                                            <i class="fas fa-info-circle mr-2 text-[#7f5539]"></i>GCash Payment
                                            Instructions
                                        </h4>

                                        <!-- Container for JS injected content -->
                                        <div id="paymentInstructions">
                                            <div class="text-center py-4 text-gray-500">
                                                Loading payment details...
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="pt-6">
                                        <button type="submit" id="submitPaymentBtn"
                                            class="w-full px-6 py-3 bg-[#7f5539] hover:bg-[#6b4f3c] text-white rounded-md font-semibold transition duration-300 flex items-center justify-center">
                                            <i class="fas fa-lock mr-2"></i>
                                            <span id="submitButtonText">Complete Payment & Confirm Re-schedule</span>
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Pay Later Form Section (Initially hidden) -->
                            <div id="payLaterFormSection" class="hidden">
                                <form action="{{ route('sub_three.my_bookings.reschedule.process', $booking->uuid) }}"
                                    method="POST" class="space-y-6" id="payLaterForm">
                                    @csrf

                                    <!-- IMPORTANT: Add booking_details as hidden field -->
                                    <input type="hidden" name="booking_details" value="{{ $encodedBookingDetails }}">

                                    <!-- Payment Type -->
                                    <input type="hidden" name="payment_type" value="full">
                                    <input type="hidden" name="payment_method" value="3"> <!-- Pay Later -->
                                    <input type="hidden" name="payment_status" value="2"> <!-- Unpaid -->

                                    {{-- Notes for Pay Later --}}
                                    <div>
                                        <label class="block text-sm font-medium text-[#4a3429] mb-2">Additional Notes
                                            (Optional)</label>
                                        <textarea name="notes" placeholder="Add any special instructions or notes for your Pay Later payment..." rows="3"
                                            class="form-input resize-none"></textarea>
                                        <p class="text-sm text-gray-500 mt-1">These notes will help the branch staff when you come to pay</p>
                                    </div>

                                    <!-- Pay Later Instructions -->
                                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                        <h4 class="font-semibold text-[#92400e] mb-3 flex items-center">
                                            <i class="fas fa-info-circle mr-2 text-[#d97706]"></i>Pay Later Instructions
                                        </h4>
                                        <ul class="text-sm text-[#92400e] space-y-2 list-disc list-inside">
                                            <li>Your booking will be confirmed immediately</li>
                                            <li>Payment status will be marked as "Unpaid"</li>
                                            <li>You need to pay ₱{{ number_format($additionalPrice, 2) }} when you visit the branch</li>
                                            <li>Payment can be made in cash or other available methods at the branch</li>
                                            <li>Please bring your booking reference: <strong>{{ $booking->booking_ref_no }}</strong></li>
                                            <li>Payment must be made during your visit, before or after your booking time</li>
                                        </ul>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="pt-6">
                                        <button type="submit" id="submitPayLaterBtn"
                                            class="w-full px-6 py-3 bg-yellow-600 hover:bg-yellow-700 text-white rounded-md font-semibold transition duration-300 flex items-center justify-center">
                                            <i class="fas fa-clock mr-2"></i>
                                            <span id="submitPayLaterText">Confirm Re-schedule with Pay Later</span>
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Default State (when no option selected) -->
                            <div id="noSelectionState" class="text-center py-8">
                                <div class="w-16 h-16 mx-auto mb-4 icon-box flex items-center justify-center">
                                    <i class="fas fa-credit-card text-[#7f5539] text-2xl"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-[#4a3429] mb-2">Select a Payment Option</h3>
                                <p class="text-gray-600 text-sm">Choose how you want to pay for the extended time to proceed with your re-schedule</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Data Loss Warning Modal -->
    <div id="dataLossModal" class="modal">
        <div class="modal-content">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-[#4a3429] mb-2">Warning: Data Loss</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Going back to edit will reset all your current re-schedule data.
                    All selected dates, times, and extended time will be cleared.
                </p>
                <div class="flex gap-3 justify-center">
                    <button type="button" id="modalCancel"
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition duration-200 font-medium">
                        Cancel
                    </button>
                    <a href="{{ route('sub_three.my_bookings.reschedule.form', $booking->uuid) }}" id="modalConfirmBack"
                        class="px-4 py-2 bg-[#7f5539] text-white rounded-md hover:bg-[#6b4f3c] transition duration-200 font-medium">
                        Yes, Go Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div id="imagePreviewModal" class="modal">
        <div class="image-modal-content">
            <img id="modalImage" src="" alt="Preview" class="modal-image">
        </div>
        <!-- Controls at bottom of page -->
        <div class="modal-controls">
            <div class="modal-controls-content">
                <button id="zoomInBtn" class="modal-control-btn">
                    <i class="fas fa-search-plus mr-1"></i> Zoom In
                </button>
                <button id="zoomOutBtn" class="modal-control-btn">
                    <i class="fas fa-search-minus mr-1"></i> Zoom Out
                </button>
                <button id="resetZoomBtn" class="modal-control-btn">
                    <i class="fas fa-sync-alt mr-1"></i> Reset
                </button>
                <button id="closeImageModalBtn" class="modal-control-btn">
                    <i class="fas fa-times mr-1"></i> Close
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize file upload functionality with image preview
            initializeImageUpload('paymentUploadArea', 'paymentFileInput', 'paymentDefaultState',
                'paymentImagePreviewState', 'paymentImagePreview', 'paymentFileError');

            // Image preview modal functionality
            const imagePreviewModal = document.getElementById('imagePreviewModal');
            const modalImage = document.getElementById('modalImage');
            const zoomInBtn = document.getElementById('zoomInBtn');
            const zoomOutBtn = document.getElementById('zoomOutBtn');
            const resetZoomBtn = document.getElementById('resetZoomBtn');
            const closeImageModalBtn = document.getElementById('closeImageModalBtn');

            let currentScale = 1;
            const scaleStep = 0.2;

            // Open image modal when clicking on image preview overlay
            document.addEventListener('click', function(e) {
                // Check if click is on image preview overlay or directly on image
                const imagePreview = e.target.closest('.image-preview-overlay');
                const imageElement = e.target.closest('.image-preview');

                if (imagePreview || (imageElement && !e.target.closest('.choose-file-btn'))) {
                    const imgSrc = imageElement ? imageElement.src : imagePreview.previousElementSibling
                        .src;
                    modalImage.src = imgSrc;
                    currentScale = 1;
                    modalImage.style.transform = `scale(${currentScale})`;
                    imagePreviewModal.style.display = 'block';
                    document.body.style.overflow = 'hidden';
                    e.preventDefault();
                    e.stopPropagation();
                }
            });

            // Zoom functionality
            zoomInBtn.addEventListener('click', function() {
                currentScale += scaleStep;
                modalImage.style.transform = `scale(${currentScale})`;
            });

            zoomOutBtn.addEventListener('click', function() {
                if (currentScale > scaleStep) {
                    currentScale -= scaleStep;
                    modalImage.style.transform = `scale(${currentScale})`;
                }
            });

            resetZoomBtn.addEventListener('click', function() {
                currentScale = 1;
                modalImage.style.transform = `scale(${currentScale})`;
            });

            // Close modal
            closeImageModalBtn.addEventListener('click', function() {
                imagePreviewModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });

            // Close modal when clicking outside
            imagePreviewModal.addEventListener('click', function(event) {
                if (event.target === imagePreviewModal) {
                    imagePreviewModal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });

            // Payment options functionality
            const paymentOptions = document.querySelectorAll('.payment-option');
            const gcashPaymentFormSection = document.getElementById('gcashPaymentFormSection');
            const payLaterFormSection = document.getElementById('payLaterFormSection');
            const noSelectionState = document.getElementById('noSelectionState');
            const paymentMethodInput = document.getElementById('paymentMethodInput');
            const paymentStatusInput = document.getElementById('paymentStatusInput');
            const paymentInstructions = document.getElementById('paymentInstructions');
            const submitPaymentBtn = document.getElementById('submitPaymentBtn');
            const submitButtonText = document.getElementById('submitButtonText');

            // Get prices
            const additionalPrice = parseFloat(@json($additionalPrice));

            // Handle payment option selection
            paymentOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Remove selected class from all options
                    document.querySelectorAll('.payment-option').forEach(opt => {
                        opt.classList.remove('selected');
                    });

                    // Add selected class to clicked option
                    this.classList.add('selected');

                    // Get payment details from data attributes
                    const paymentMethod = this.getAttribute('data-payment-method');
                    const paymentStatus = this.getAttribute('data-payment-status');
                    const paymentType = this.getAttribute('data-payment-type');
                    const paymentAmount = this.getAttribute('data-payment-amount');
                    const paymentDisplay = this.getAttribute('data-payment-display');

                    // Hide all forms and show the appropriate one
                    if (noSelectionState) noSelectionState.style.display = 'none';
                    if (gcashPaymentFormSection) gcashPaymentFormSection.classList.add('hidden');
                    if (payLaterFormSection) payLaterFormSection.classList.add('hidden');

                    // Show appropriate form based on selection
                    if (paymentType === 'pay_now' && gcashPaymentFormSection) {
                        gcashPaymentFormSection.classList.remove('hidden');
                        
                        // Update hidden inputs
                        if (paymentMethodInput) paymentMethodInput.value = paymentMethod;
                        if (paymentStatusInput) paymentStatusInput.value = paymentStatus;
                        
                        // Scroll to form
                        gcashPaymentFormSection.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });

                        // Update payment instructions with the selected amount
                        updatePaymentInstructions(paymentAmount);
                    } 
                    else if (paymentType === 'pay_later' && payLaterFormSection) {
                        payLaterFormSection.classList.remove('hidden');
                        
                        // Scroll to form
                        payLaterFormSection.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Function to update payment instructions based on selected amount
            function updatePaymentInstructions(amount) {
                if (!paymentInstructions) return;

                let html = '';
                
                @if (isset($staffGcashQrCode) && count($staffGcashQrCode) > 0)
                    html = `
                    <div class="mt-4">
                        <p class="text-sm text-[#4a3429] mb-4">
                            Please scan any of the following QR codes to pay via GCash:
                        </p>
                        
                        <!-- QR Code Carousel -->
                        <div class="relative">
                            <div id="qrCarousel" class="overflow-hidden">
                                <div class="flex transition-transform duration-300" id="qrCarouselTrack">
                                    @foreach ($staffGcashQrCode as $index => $qrCodePath)
                                        @php
                                            $qrNumber = $index + 1;
                                            $fullPath = str_starts_with($qrCodePath, 'storage/') ? 'app/public/' . substr($qrCodePath, 8) : 'app/public/' . $qrCodePath;
                                        @endphp
                                        
                                        <div class="flex-shrink-0 w-full p-4">
                                            <div class="flex flex-col items-center p-4 bg-white rounded-lg border border-gray-200">
                                                <div class="mb-2">
                                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-[#7f5539] text-white text-sm font-bold">
                                                        {{ $qrNumber }}
                                                    </span>
                                                </div>
                                                
                                                <img src="{{ asset('storage/' . $fullPath) }}" 
                                                     alt="GCash QR Code {{ $qrNumber }}"
                                                     class="w-40 h-40 object-contain cursor-pointer hover:opacity-80 transition-opacity mb-3"
                                                     onclick="window.open('{{ asset('storage/' . $fullPath) }}', '_blank')">
                                                
                                                <div class="flex gap-2">
                                                    <a href="{{ asset('storage/' . $fullPath) }}" 
                                                       download="gcash-qr-code-{{ $qrNumber }}.png"
                                                       class="inline-flex items-center px-3 py-1 bg-[#7f5539] hover:bg-[#6b4f3c] text-white text-xs font-medium rounded-lg shadow-sm transition duration-300">
                                                        <i class="fas fa-download mr-1"></i>Download
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            
                            @if (count($staffGcashQrCode) > 1)
                                <!-- Carousel Controls -->
                                <button id="prevBtn" class="absolute left-0 top-1/2 transform -translate-y-1/2 bg-[#7f5539] text-white p-2 rounded-full hover:bg-[#6b4f3c] transition">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button id="nextBtn" class="absolute right-0 top-1/2 transform -translate-y-1/2 bg-[#7f5539] text-white p-2 rounded-full hover:bg-[#6b4f3c] transition">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                                
                                <!-- Dots Indicator -->
                                <div class="flex justify-center mt-4 space-x-2">
                                    @foreach ($staffGcashQrCode as $index => $qrCodePath)
                                        <button class="w-2 h-2 rounded-full bg-gray-300 carousel-dot {{ $index === 0 ? 'bg-[#7f5539]' : '' }}" 
                                                data-index="{{ $index }}"></button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        
                        <!-- Instructions -->
                        <div class="mt-6">
                            <ol class="text-[#4a3429] text-sm space-y-1 list-decimal list-inside">
                                <li>Open your GCash app</li>
                                <li>Go to "Send Money" or "Scan QR"</li>
                                <li>Scan any of the QR codes above</li>
                                <li>Amount: <strong>₱${parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</strong></li>
                                <li>Complete the transaction and take a screenshot</li>
                                <li>Upload the screenshot here and enter reference number above</li>
                            </ol>
                        </div>
                    </div>`;
                @else
                    html = `
                    <div class="mt-4">
                        <p class="text-sm text-[#4a3429] mb-4">
                            G-Cash QR codes are currently unavailable. Please contact the branch through their social media for more payment details.
                        </p>
                        <p class="text-sm text-[#4a3429] font-bold">
                            Amount to pay: ₱${parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2})}
                        </p>
                    </div>`;
                @endif

                paymentInstructions.innerHTML = html;
                
                // Reinitialize carousel if QR codes exist
                @if (isset($staffGcashQrCode) && count($staffGcashQrCode) > 0)
                    initializeQRCarousel();
                @endif
            }

            // Initialize QR code carousel
            function initializeQRCarousel() {
                const carouselTrack = document.getElementById('qrCarouselTrack');
                const prevBtn = document.getElementById('prevBtn');
                const nextBtn = document.getElementById('nextBtn');
                const dots = document.querySelectorAll('.carousel-dot');

                if (carouselTrack && prevBtn && nextBtn) {
                    let currentIndex = 0;
                    const qrCodes = @json($staffGcashQrCode ?? []);
                    const totalSlides = qrCodes.length;

                    function updateCarousel() {
                        carouselTrack.style.transform = `translateX(-${currentIndex * 100}%)`;

                        // Update dots
                        dots.forEach((dot, index) => {
                            dot.classList.toggle('bg-[#7f5539]', index === currentIndex);
                            dot.classList.toggle('bg-gray-300', index !== currentIndex);
                        });
                    }

                    prevBtn.addEventListener('click', () => {
                        currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
                        updateCarousel();
                    });

                    nextBtn.addEventListener('click', () => {
                        currentIndex = (currentIndex + 1) % totalSlides;
                        updateCarousel();
                    });

                    // Dot click events
                    dots.forEach((dot, index) => {
                        dot.addEventListener('click', () => {
                            currentIndex = index;
                            updateCarousel();
                        });
                    });
                }
            }

            // Form validation for GCash payment
            const gcashPaymentForm = document.getElementById('gcashPaymentForm');
            if (gcashPaymentForm) {
                gcashPaymentForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    // Basic validation
                    const fileInput = this.querySelector('input[type="file"]');
                    const refNoInput = this.querySelector('input[name="gcash_ref_no"]');
                    const bookingDetailsInput = this.querySelector('input[name="booking_details"]');
                    const notesTextarea = this.querySelector('textarea[name="notes"]');

                    if (fileInput && !fileInput.files.length) {
                        alert('Please upload GCash receipt screenshot');
                        return false;
                    }

                    if (refNoInput && !refNoInput.value.trim()) {
                        alert('Please enter GCash reference number');
                        refNoInput.focus();
                        return false;
                    }

                    if (!bookingDetailsInput || !bookingDetailsInput.value) {
                        alert('Invalid booking data. Please refresh the page and try again.');
                        return false;
                    }

                    // Optional: Validate notes length if provided
                    if (notesTextarea && notesTextarea.value.length > 1000) {
                        alert('Notes cannot exceed 1000 characters.');
                        notesTextarea.focus();
                        return false;
                    }

                    // Show loading state
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';

                    // Submit the form
                    this.submit();
                });
            }

            // Form validation for Pay Later
            const payLaterForm = document.getElementById('payLaterForm');
            if (payLaterForm) {
                payLaterForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const bookingDetailsInput = this.querySelector('input[name="booking_details"]');
                    const notesTextarea = this.querySelector('textarea[name="notes"]');

                    if (!bookingDetailsInput || !bookingDetailsInput.value) {
                        alert('Invalid booking data. Please refresh the page and try again.');
                        return false;
                    }

                    // Optional: Validate notes length if provided
                    if (notesTextarea && notesTextarea.value.length > 1000) {
                        alert('Notes cannot exceed 1000 characters.');
                        notesTextarea.focus();
                        return false;
                    }

                    // Show loading state
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';

                    // Submit the form
                    this.submit();
                });
            }

            // Character counter for notes textareas
            const notesTextareas = document.querySelectorAll('textarea[name="notes"]');

            notesTextareas.forEach(textarea => {
                // Create counter element
                const counter = document.createElement('div');
                counter.className = 'text-xs text-gray-500 mt-1 text-right';
                counter.innerHTML = '<span class="current-count">0</span>/1000 characters';

                textarea.parentNode.appendChild(counter);

                // Update counter on input
                textarea.addEventListener('input', function() {
                    const count = this.value.length;
                    const counterSpan = counter.querySelector('.current-count');
                    counterSpan.textContent = count;

                    // Add warning class if approaching limit
                    if (count > 900) {
                        counter.classList.add('text-yellow-600');
                        counter.classList.remove('text-gray-500');
                    } else if (count > 950) {
                        counter.classList.add('text-red-600');
                        counter.classList.remove('text-yellow-600');
                    } else {
                        counter.classList.remove('text-yellow-600', 'text-red-600');
                        counter.classList.add('text-gray-500');
                    }
                });

                // Initialize counter
                textarea.dispatchEvent(new Event('input'));
            });

            // Data Loss Warning Modal
            const dataLossModal = document.getElementById('dataLossModal');
            const modalCancel = document.getElementById('modalCancel');
            const modalConfirmBack = document.getElementById('modalConfirmBack');

            // Add event listener to any "Back" buttons
            const backButtons = document.querySelectorAll('a[href*="reschedule.form"]');
            backButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    modalConfirmBack.href = this.href;
                    dataLossModal.style.display = 'block';
                    document.body.style.overflow = 'hidden';
                });
            });

            if (modalCancel) {
                modalCancel.addEventListener('click', function() {
                    dataLossModal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                });
            }

            if (dataLossModal) {
                dataLossModal.addEventListener('click', function(event) {
                    if (event.target === dataLossModal) {
                        dataLossModal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                    }
                });
            }
        });

        // Enhanced File upload function with IMAGE PREVIEW
        function initializeImageUpload(uploadAreaId, fileInputId, defaultStateId, imagePreviewStateId, imagePreviewId,
            fileErrorId) {
            const uploadArea = document.getElementById(uploadAreaId);
            const fileInput = document.getElementById(fileInputId);
            const defaultState = document.getElementById(defaultStateId);
            const imagePreviewState = document.getElementById(imagePreviewStateId);
            const imagePreview = document.getElementById(imagePreviewId);
            const fileError = document.getElementById(fileErrorId);

            if (!uploadArea || !fileInput) return;

            // Make choose file buttons clickable
            const chooseFileBtns = uploadArea.querySelectorAll('.choose-file-btn');
            chooseFileBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    fileInput.click();
                });
            });

            // Remove image button
            const removeImageBtn = uploadArea.querySelector('#paymentRemoveImageBtn');
            if (removeImageBtn) {
                removeImageBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    resetFileInput();
                });
            }

            // File input change
            fileInput.addEventListener('change', function(e) {
                handleFileSelection(e.target.files[0]);
            });

            // Drag and drop functionality
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                uploadArea.classList.add('drag-over');
            });

            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                if (!uploadArea.contains(e.relatedTarget)) {
                    uploadArea.classList.remove('drag-over');
                }
            });

            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('drag-over');

                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    handleFileSelection(files[0]);
                }
            });

            // Handle file selection
            function handleFileSelection(file) {
                if (file) {
                    // Validate file type
                    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                    if (!validTypes.includes(file.type.toLowerCase())) {
                        showError('Please select a valid image file (JPEG, PNG, GIF)');
                        return;
                    }

                    // Validate file size (2MB)
                    const maxSize = 2 * 1024 * 1024;
                    if (file.size > maxSize) {
                        showError('File size must be less than 2MB');
                        return;
                    }

                    // Clear any previous errors
                    hideError();

                    // Create object URL for image preview
                    const objectUrl = URL.createObjectURL(file);

                    // Update UI - show image preview
                    imagePreview.src = objectUrl;
                    defaultState.classList.add('hidden');
                    imagePreviewState.classList.remove('hidden');
                    uploadArea.classList.add('has-file');

                    // Create a new FileList to set on the input
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    fileInput.files = dataTransfer.files;

                    // Clean up object URL when file is removed
                    fileInput._objectUrl = objectUrl;
                }
            }

            function resetFileInput() {
                // Clean up object URL if it exists
                if (fileInput._objectUrl) {
                    URL.revokeObjectURL(fileInput._objectUrl);
                    delete fileInput._objectUrl;
                }

                fileInput.value = '';
                defaultState.classList.remove('hidden');
                imagePreviewState.classList.add('hidden');
                uploadArea.classList.remove('has-file');
                hideError();
            }

            function showError(message) {
                if (fileError) {
                    fileError.textContent = message;
                    fileError.classList.remove('hidden');
                }
                resetFileInput();
            }

            function hideError() {
                if (fileError) {
                    fileError.classList.add('hidden');
                }
            }

            // Prevent default drag behaviors
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
        }
    </script>
</body>
</html>