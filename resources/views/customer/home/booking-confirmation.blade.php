<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - {{ $booking->booking_ref_no ?? 'LinkudHub' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="{{ asset('storage/logo.png') }}" type="image/png">
    <style>
        /* Custom styles matching home page */
        .section-title {
            color: #4a3429;
            font-weight: bold;
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

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 8px 0;
            border-bottom: 1px solid #e6ddd4;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #666;
            font-weight: 500;
            min-width: 120px;
            padding-right: 15px;
        }

        .info-value {
            font-weight: 600;
            color: #4a3429;
            text-align: right;
            flex: 1;
            word-break: break-word;
        }

        /* Image Modal Styles */
        .image-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            z-index: 10000;
            overflow: hidden;
        }

        .image-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 90%;
            max-height: 90%;
        }

        .modal-image {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .modal-controls {
            position: fixed;
            bottom: 20px;
            left: 0;
            width: 100%;
            display: flex;
            justify-content: center;
            padding: 0 20px;
            z-index: 10001;
            pointer-events: none;
        }

        .modal-controls-content {
            background: rgba(255, 255, 255, 0.95);
            padding: 10px 15px;
            border-radius: 8px;
            display: flex;
            gap: 10px;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            pointer-events: auto;
        }

        .modal-control-btn {
            background: #7f5539;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 100px;
        }

        .modal-control-btn:hover {
            background: #6b4f3c;
            transform: translateY(-1px);
        }

        .modal-control-btn i {
            margin-right: 6px;
        }

        .close-modal-btn {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            z-index: 10002;
            transition: color 0.3s;
        }

        .close-modal-btn:hover {
            color: #f5f0eb;
        }

        /* Receipt preview styling */
        .receipt-preview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e6ddd4;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .receipt-preview:hover {
            transform: scale(1.05);
            border-color: #7f5539;
            box-shadow: 0 4px 12px rgba(127, 85, 57, 0.2);
        }

        .receipt-container {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
        }

        .receipt-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        /* QR code styling */
        .qr-code-container {
            border: 1px solid #e6ddd4;
            border-radius: 0.5rem;
            background: white;
            padding: 1rem;
        }

        /* For small screens */
        @media (max-width: 640px) {
            .info-item {
                flex-direction: column;
                align-items: stretch;
                padding: 10px 0;
            }

            .info-label {
                min-width: auto;
                padding-right: 0;
                margin-bottom: 4px;
                font-size: 0.875rem;
            }

            .info-value {
                text-align: left;
                font-size: 1rem;
            }

            /* Modal controls responsive */
            .modal-controls-content {
                flex-wrap: wrap;
                padding: 8px 10px;
            }

            .modal-control-btn {
                min-width: 80px;
                padding: 6px 10px;
                font-size: 12px;
            }

            .receipt-container {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        /* Animation */
        @keyframes checkmark {
            0% {
                transform: scale(0);
                opacity: 0;
            }

            50% {
                transform: scale(1.2);
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .checkmark-animation {
            animation: checkmark 0.6s ease-out;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .card {
                border: 1px solid #000 !important;
                box-shadow: none !important;
            }

            body {
                background: white !important;
                color: black !important;
            }

            .image-modal,
            .modal-controls {
                display: none !important;
            }
        }
    </style>
</head>

<body class="bg-[#f5f0eb] min-h-screen">
    <!-- Image Modal -->
    <div id="imageModal" class="image-modal">
        <span class="close-modal-btn">&times;</span>
        <div class="image-modal-content">
            <img id="modalImage" src="" alt="Receipt Preview" class="modal-image">
        </div>
        <div class="modal-controls">
            <div class="modal-controls-content">
                <button id="zoomInBtn" class="modal-control-btn">
                    <i class="fas fa-search-plus"></i> Zoom In
                </button>
                <button id="zoomOutBtn" class="modal-control-btn">
                    <i class="fas fa-search-minus"></i> Zoom Out
                </button>
                <button id="resetZoomBtn" class="modal-control-btn">
                    <i class="fas fa-sync-alt"></i> Reset
                </button>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-3 py-6 max-w-4xl">
        @if (!$booking)
            <!-- No Booking Found State -->
            <div class="card">
                <div class="bg-red-100 text-red-800 p-6 text-center border-b border-red-200">
                    <i class="fas fa-exclamation-triangle text-4xl mb-3"></i>
                    <h1 class="text-2xl font-bold">Booking Not Found</h1>
                    <p class="text-red-600">We couldn't find your booking details</p>
                </div>
                <div class="p-6 text-center">
                    <p class="text-gray-600 mb-6">
                        {{ $error ?? 'Please check your booking history or contact support.' }}</p>
                    <a href="{{ route('sub_three.home.showHome') }}"
                        class="bg-[#7f5539] hover:bg-[#6b4f3c] text-white px-6 py-3 rounded-lg font-semibold transition duration-300 inline-flex items-center">
                        <i class="fas fa-home mr-2"></i>
                        Return to Home
                    </a>
                </div>
            </div>
        @else
            <!-- Success Hero Section -->
            <section class="relative bg-gradient-to-br from-[#f5f0eb] to-[#e6ddd4] py-6 mb-6 rounded-lg">
                <div class="container mx-auto px-3">
                    <div class="max-w-2xl mx-auto text-center">
                        <div
                            class="w-20 h-20 mx-auto mb-4 bg-white rounded-full flex items-center justify-center checkmark-animation shadow-lg">
                            <i class="fas fa-check text-4xl text-[#7f5539]"></i>
                        </div>
                        <h1 class="text-xl md:text-2xl font-bold text-[#4a3429] mb-2 leading-tight">Booking Submitted
                            Successfully!</h1>
                        <p class="text-[#7f5539] font-medium">Reference Number: <strong
                                class="text-[#4a3429]">{{ $booking->booking_ref_no }}</strong></p>
                    </div>
                </div>
            </section>

            <!-- Main Confirmation Card -->
            <div class="card mb-6">
                <div class="bg-[#7f5539] text-white p-6 text-center rounded-t-lg">
                    <h1 class="text-2xl font-bold">Booking Confirmation</h1>
                    <p class="text-[#f5f0eb]">Your booking has been successfully submitted</p>
                </div>

                <div class="p-6">
                    <!-- Flash Message & Status -->
                    <div class="text-center mb-6">
                        @if (session('success'))
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4 max-w-md mx-auto">
                                <p class="text-green-800 flex items-center justify-center">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    {{ session('success') }}
                                </p>
                            </div>
                        @endif

                        <div class="inline-block px-6 py-2 rounded-full bg-[#f5f0eb] border border-[#e6ddd4]">
                            <p class="text-[#4a3429] font-medium flex items-center">
                                <i class="fas fa-clock mr-2 text-[#7f5539]"></i>
                                Booking Status: <span
                                    class="ml-2 font-semibold {{ $booking->booking_status == 2 ? 'text-[#f59e0b]' : 'text-[#7f5539]' }}">
                                    @if ($booking->booking_status == 1)
                                        Confirmed
                                    @elseif($booking->booking_status == 2)
                                        Pending
                                    @elseif($booking->booking_status == 0)
                                        Cancelled
                                    @elseif($booking->booking_status == 3)
                                        No Show
                                    @elseif($booking->booking_status == 4)
                                        Completed
                                    @else
                                        Unknown
                                    @endif
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Customer Information -->
<div class="mb-6">
    <h4 class="font-medium text-[#4a3429] mb-3 pb-2 border-b border-[#e6ddd4]">Customer Information</h4>
    <div class="space-y-3">
        <div class="info-item">
            <span class="info-label">Name:</span>
            <span class="info-value">
                {{ optional($booking->customerAccount)->first_name }} {{ optional($booking->customerAccount)->last_name }}
            </span>
        </div>
        <div class="info-item">
            <span class="info-label">Email:</span>
            <span class="info-value">
                {{ optional($booking->customerAccount)->email }}
            </span>
        </div>
        <div class="info-item">
            <span class="info-label">Phone:</span>
            <span class="info-value">
                {{ optional($booking->customerAccount)->contact_no }}
            </span>
        </div>
    </div>
</div>

                    <!-- Booking Summary Section -->
                    <div class="bg-gray-50 rounded-lg p-6 mb-6">
                        <h3 class="text-xl font-semibold text-[#4a3429] mb-4 flex items-center">
                            <i class="fas fa-receipt mr-3 text-[#7f5539]"></i>
                            Booking Summary
                        </h3>

                        <!-- 1. Booking Details -->
                        <div class="mb-6">
                            <h4 class="font-medium text-[#4a3429] mb-3 pb-2 border-b border-[#e6ddd4]">Service Details
                            </h4>
                            <div class="space-y-3">
                                <div class="info-item">
                                    <span class="info-label">Branch:</span>
                                    <span
                                        class="info-value">{{ optional($booking->branch)->branch_name ?? 'N/A' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Location:</span>
                                    <span class="info-value">{{ optional($booking->branch)->location ?? 'N/A' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Category:</span>
                                    <span
                                        class="info-value">{{ optional($booking->serviceCategory)->service_category ?? 'N/A' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Service:</span>
                                    <span
                                        class="info-value">{{ optional($booking->serviceName)->service_name ?? 'N/A' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Duration:</span>
                                    <span
                                        class="info-value">{{ optional($booking->serviceName)->time_duration ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Main Time Information -->
                        <div class="mb-6">
                            <h4 class="font-medium text-[#4a3429] mb-3 pb-2 border-b border-[#e6ddd4]">Main Schedule
                            </h4>
                            <div class="space-y-3">
                                <div class="info-item">
                                    <span class="info-label">Date:</span>
                                    <span class="info-value">
                                        {{ \Carbon\Carbon::parse($booking->date_start)->format('F j, Y') }}
                                        @if ($booking->date_start != $booking->date_end)
                                            - {{ \Carbon\Carbon::parse($booking->date_end)->format('F j, Y') }}
                                        @endif
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Time:</span>
                                    <span class="info-value">
                                        {{ \Carbon\Carbon::parse($booking->start_time)->format('h:i A') }} -
                                        {{ \Carbon\Carbon::parse($booking->end_time)->format('h:i A') }}
                                    </span>
                                </div>
                                @if ($booking->seat)
                                    <div class="info-item">
                                        <span class="info-label">Seat/Room:</span>
                                        <span class="info-value">
                                            {{ $booking->seat->seat_no ? 'Seat ' . $booking->seat->seat_no : 'Room ' . $booking->seat->room_no }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @php
                            // Get all active payments for this booking
                            $payment = $booking->payment()->where('active', 1)->get();

                            // Separate main and extension payments
                            $mainPayment = $payment->where('payment_category', 1)->first();
                            $extensionPayment = $payment->where('payment_category', 0)->first();

                            // Calculate totals
                            $mainAmount = $mainPayment ? $mainPayment->total_amount : 0;
                            $extensionAmount = $extensionPayment ? $extensionPayment->total_amount : 0;
                            $totalBookingValue = $mainAmount + $extensionAmount;

                            $mainPaid = $mainPayment && $mainPayment->payment_status == 1;
                            $extensionPaid = $extensionPayment && $extensionPayment->payment_status == 1;
                            $totalPaid = ($mainPaid ? $mainAmount : 0) + ($extensionPaid ? $extensionAmount : 0);
                        @endphp

                        <!-- 3. Main Payment Information (Displayed First) -->
                        @if ($mainPayment)
                            <div class="mb-6">
                                <h4 class="font-medium text-[#4a3429] mb-3 pb-2 border-b border-[#e6ddd4]">Main Payment
                                    Information</h4>
                                <div class="bg-white rounded-lg p-4 border border-[#e6ddd4]">
                                    <div class="space-y-3">
                                        <div class="info-item">
                                            <span class="info-label">Payment Method:</span>
                                            <span class="info-value">
                                                @if ($mainPayment->payment_method == 0)
                                                    <i class="fas fa-money-bill-wave mr-2"></i>Cash
                                                @elseif($mainPayment->payment_method == 1)
                                                    <i class="fas fa-mobile-alt mr-2"></i>GCash
                                                @elseif($mainPayment->payment_method == 2)
                                                    <i class="fas fa-credit-card mr-2"></i>Debit Card
                                                @elseif($mainPayment->payment_method == 3)
                                                    <i class="fas fa-clock mr-2"></i>Pay Later
                                                @endif
                                            </span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Payment Date:</span>
                                            <span class="info-value">
                                                @if ($mainPayment->payment_date)
                                                    {{ \Carbon\Carbon::parse($mainPayment->payment_date)->format('F j, Y h:i A') }}
                                                @elseif($mainPayment->date_created)
                                                    {{ \Carbon\Carbon::parse($mainPayment->date_created)->format('F j, Y h:i A') }}
                                                @else
                                                    Pending
                                                @endif
                                            </span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Status:</span>
                                            <span
                                                class="info-value {{ $mainPayment->payment_status == 1 ? 'text-green-600' : ($mainPayment->payment_status == 2 ? 'text-yellow-600' : 'text-red-600') }}">
                                                @if ($mainPayment->payment_status == 1)
                                                    <span
                                                        class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Paid</span>
                                                @elseif($mainPayment->payment_status == 2)
                                                    <span
                                                        class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">Unpaid</span>
                                                @elseif($mainPayment->payment_status == 0)
                                                    <span
                                                        class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">Invalid</span>
                                                @endif
                                            </span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Total Amount:</span>
                                            <span
                                                class="info-value">₱{{ number_format($mainPayment->total_amount, 2) }}</span>
                                        </div>
                                        @if ($mainPayment->amount_paid > 0)
                                            <div class="info-item">
                                                <span class="info-label">Amount Paid:</span>
                                                <span
                                                    class="info-value">₱{{ number_format($mainPayment->amount_paid, 2) }}</span>
                                            </div>
                                        @endif
                                        @if ($mainPayment->change > 0)
                                            <div class="info-item">
                                                <span class="info-label">Change:</span>
                                                <span
                                                    class="info-value">₱{{ number_format($mainPayment->change, 2) }}</span>
                                            </div>
                                        @endif

                                        <!-- GCash Details for Main Payment -->
                                        @if ($mainPayment->payment_method == 1 && ($mainPayment->gcash_ref_no || $mainPayment->gcash_receipt_img))
                                            <div class="mt-4 pt-4">
                                                <h5 class="font-medium text-[#4a3429] mb-3 text-sm">GCash Payment
                                                    Details:</h5>
                                                @if ($mainPayment->gcash_ref_no)
                                                    <div class="info-item">
                                                        <span class="info-label">Reference Number:</span>
                                                        <span
                                                            class="info-value">{{ $mainPayment->gcash_ref_no }}</span>
                                                    </div>
                                                @endif
                                                @if ($mainPayment->gcash_receipt_img)
                                                    <div class="mt-3">
                                                        <div class="info-item">
                                                            <span class="info-label">Payment Receipt:</span>
                                                            <div class="receipt-container">
                                                                <img src="{{ asset('storage/app/public/' . $mainPayment->gcash_receipt_img) }}"
                                                                    alt="GCash Receipt" class="receipt-preview"
                                                                    data-receipt-src="{{ asset('storage/app/public/' . $mainPayment->gcash_receipt_img) }}"
                                                                    data-receipt-name="GCash Receipt - {{ $booking->booking_ref_no }}">
                                                                <div class="receipt-info">
                                                                    <div class="text-sm text-gray-500">
                                                                        <i class="fas fa-info-circle mr-1"></i>
                                                                        Click to view receipt
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif

                                        <!-- Display notes if any -->
                                        @if ($mainPayment->notes && is_array($mainPayment->notes) && count($mainPayment->notes) > 0)
                                            <div class="mt-4 pt-4 border-t border-gray-200">
                                                <h5 class="font-medium text-[#4a3429] mb-2 text-sm">Notes:</h5>
                                                @foreach ($mainPayment->notes as $note)
                                                    @if (is_array($note) && isset($note['content']))
                                                        <div class="bg-gray-50 p-3 rounded mb-2">
                                                            <p class="text-sm text-gray-700">{{ $note['content'] }}
                                                            </p>
                                                            @if (isset($note['added_at']))
                                                                <p class="text-xs text-gray-500 mt-1">
                                                                    Added:
                                                                    {{ \Carbon\Carbon::parse($note['added_at'])->format('M j, Y h:i A') }}
                                                                    @if (isset($note['added_by_type']))
                                                                        by {{ $note['added_by_type'] }}
                                                                    @endif
                                                                </p>
                                                            @endif
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- 4. Additional Time Summary (Displayed Second) -->
                        @if ($booking->extended_start_time || $booking->additional_price > 0 || $extensionPayment)
                            <div class="mb-6">
                                <h4 class="font-medium text-[#4a3429] mb-3 pb-2 border-b border-[#e6ddd4]">Additional
                                    Time Summary</h4>
                                <div class="bg-white rounded-lg p-4 border border-[#e6ddd4]">
                                    <!-- Extended Time Schedule -->
                                    @if (
                                        $booking->extended_start_time ||
                                            $booking->extended_end_time ||
                                            $booking->extended_date_start ||
                                            $booking->extended_date_end)
                                        <div class="mb-4">
                                            <h5 class="font-medium text-[#4a3429] mb-3 text-sm">Extended Time Schedule:
                                            </h5>
                                            <div class="space-y-3">
                                                @if ($booking->extended_start_time || $booking->extended_end_time)
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                        @if ($booking->extended_start_time)
                                                            <div>
                                                                <p class="text-sm text-gray-600">Extended Start:</p>
                                                                <p class="font-medium text-[#4a3429]">
                                                                    {{ \Carbon\Carbon::parse($booking->extended_start_time)->format('h:i A') }}
                                                                </p>
                                                            </div>
                                                        @endif
                                                        @if ($booking->extended_end_time)
                                                            <div>
                                                                <p class="text-sm text-gray-600">Extended End:</p>
                                                                <p class="font-medium text-[#4a3429]">
                                                                    {{ \Carbon\Carbon::parse($booking->extended_end_time)->format('h:i A') }}
                                                                </p>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif

                                                @if ($booking->extended_date_start || $booking->extended_date_end)
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                        @if ($booking->extended_date_start)
                                                            <div>
                                                                <p class="text-sm text-gray-600">Extended Date Start:
                                                                </p>
                                                                <p class="font-medium text-[#4a3429]">
                                                                    {{ \Carbon\Carbon::parse($booking->extended_date_start)->format('M j, Y') }}
                                                                </p>
                                                            </div>
                                                        @endif
                                                        @if ($booking->extended_date_end)
                                                            <div>
                                                                <p class="text-sm text-gray-600">Extended Date End:</p>
                                                                <p class="font-medium text-[#4a3429]">
                                                                    {{ \Carbon\Carbon::parse($booking->extended_date_end)->format('M j, Y') }}
                                                                </p>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif

                                                @if ($booking->extended_duration_minutes)
                                                    <div>
                                                        <p class="text-sm text-gray-600">Additional Duration:</p>
                                                        <p class="font-medium text-[#4a3429]">
                                                            <i class="fas fa-clock mr-1"></i>
                                                            {{ floor($booking->extended_duration_minutes / 60) }}
                                                            hour{{ floor($booking->extended_duration_minutes / 60) != 1 ? 's' : '' }}
                                                            @if ($booking->extended_duration_minutes % 60 > 0)
                                                                {{ $booking->extended_duration_minutes % 60 }}
                                                                minute{{ $booking->extended_duration_minutes % 60 != 1 ? 's' : '' }}
                                                            @endif
                                                        </p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Extended Time Payment Information -->
                                    @if ($extensionPayment)
                                        <div class="mt-4 pt-4 border-t border-gray-200">
                                            <h5 class="font-medium text-[#4a3429] mb-3">Extended Time Payment</h5>
                                            <div class="space-y-3">
                                                <div class="info-item">
                                                    <span class="info-label">Status:</span>
                                                    <span
                                                        class="info-value {{ $extensionPayment->payment_status == 1 ? 'text-green-600' : ($extensionPayment->payment_status == 2 ? 'text-yellow-600' : 'text-red-600') }}">
                                                        @if ($extensionPayment->payment_status == 1)
                                                            <span
                                                                class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Paid</span>
                                                        @elseif($extensionPayment->payment_status == 2)
                                                            <span
                                                                class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">Unpaid</span>
                                                        @elseif($extensionPayment->payment_status == 0)
                                                            <span
                                                                class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">Invalid</span>
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="info-item">
                                                    <span class="info-label">Payment Method:</span>
                                                    <span class="info-value">
                                                        @if ($extensionPayment->payment_method == 0)
                                                            <i class="fas fa-money-bill-wave mr-2"></i>Cash
                                                        @elseif($extensionPayment->payment_method == 1)
                                                            <i class="fas fa-mobile-alt mr-2"></i>GCash
                                                        @elseif($extensionPayment->payment_method == 2)
                                                            <i class="fas fa-credit-card mr-2"></i>Debit Card
                                                        @elseif($extensionPayment->payment_method == 3)
                                                            <i class="fas fa-clock mr-2"></i>Pay Later
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="info-item">
                                                    <span class="info-label">Payment Date:</span>
                                                    <span class="info-value">
                                                        @if ($extensionPayment->payment_date)
                                                            {{ \Carbon\Carbon::parse($extensionPayment->payment_date)->format('F j, Y h:i A') }}
                                                        @elseif($extensionPayment->date_created)
                                                            {{ \Carbon\Carbon::parse($extensionPayment->date_created)->format('F j, Y h:i A') }}
                                                        @else
                                                            Pending
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="info-item">
                                                    <span class="info-label">Total Amount:</span>
                                                    <span
                                                        class="info-value">₱{{ number_format($extensionPayment->total_amount, 2) }}</span>
                                                </div>

                                                @if ($extensionPayment->gcash_ref_no)
                                                    <div class="info-item">
                                                        <span class="info-label">GCash Reference No:</span>
                                                        <span
                                                            class="info-value">{{ $extensionPayment->gcash_ref_no }}</span>
                                                    </div>
                                                @endif

                                                @if ($extensionPayment->amount_paid > 0)
                                                    <div class="info-item">
                                                        <span class="info-label">Amount Paid:</span>
                                                        <span
                                                            class="info-value">₱{{ number_format($extensionPayment->amount_paid, 2) }}</span>
                                                    </div>
                                                @endif

                                                @if ($extensionPayment->change > 0)
                                                    <div class="info-item">
                                                        <span class="info-label">Change:</span>
                                                        <span
                                                            class="info-value">₱{{ number_format($extensionPayment->change, 2) }}</span>
                                                    </div>
                                                @endif

                                                <!-- GCash Receipt for Extension Payment -->
                                                @if ($extensionPayment->payment_method == 1 && $extensionPayment->gcash_receipt_img)
                                                    <div class="mt-3 pt-3">
                                                        <div class="info-item">
                                                            <span class="info-label">GCash Receipt:</span>
                                                            <div class="receipt-container">
                                                                <img src="{{ asset('storage/app/public/' . $extensionPayment->gcash_receipt_img) }}"
                                                                    alt="Extension Payment Receipt"
                                                                    class="receipt-preview"
                                                                    data-receipt-src="{{ asset('storage/app/public/' . $extensionPayment->gcash_receipt_img) }}"
                                                                    data-receipt-name="Extension Payment Receipt - {{ $booking->booking_ref_no }}">
                                                                <div class="receipt-info">
                                                                    <div class="text-sm text-gray-500">
                                                                        <i class="fas fa-info-circle mr-1"></i>
                                                                        Click to view extension payment receipt
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                                <!-- Notes for Extension Payment -->
                                                @if ($extensionPayment->notes && is_array($extensionPayment->notes) && count($extensionPayment->notes) > 0)
                                                    <div class="mt-3 pt-3 border-t border-gray-200">
                                                        <h6 class="font-medium text-[#4a3429] mb-2 text-sm">Payment
                                                            Notes:</h6>
                                                        @foreach ($extensionPayment->notes as $note)
                                                            @if (is_array($note) && isset($note['content']))
                                                                <div class="bg-gray-50 p-3 rounded mb-2">
                                                                    <p class="text-sm text-gray-700">
                                                                        {{ $note['content'] }}</p>
                                                                    @if (isset($note['added_at']))
                                                                        <p class="text-xs text-gray-500 mt-1">
                                                                            Added:
                                                                            {{ \Carbon\Carbon::parse($note['added_at'])->format('M j, Y h:i A') }}
                                                                            @if (isset($note['added_by_type']))
                                                                                by {{ $note['added_by_type'] }}
                                                                            @endif
                                                                        </p>
                                                                    @endif
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @elseif($booking->additional_price > 0)
                                        <!-- Show if extension time exists but no payment record -->
                                        <div class="mt-4 pt-4 border-t border-gray-200">
                                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                                <p class="text-yellow-800 text-sm font-medium flex items-center">
                                                    <i class="fas fa-info-circle mr-2"></i>
                                                    Extended time booked
                                                    (₱{{ number_format($booking->additional_price, 2) }}) - Payment
                                                    pending.
                                                </p>
                                                <p class="text-yellow-700 text-xs mt-2">
                                                    Payment for extended time will be collected separately.
                                                </p>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Price Summary for Additional Time -->
                                    <div class="mt-4 pt-4 border-t border-gray-200">
                                        <h5 class="font-medium text-[#4a3429] mb-3">Price Summary</h5>
                                        <div class="space-y-2">
                                            <div class="flex justify-between items-center">
                                                <span class="text-gray-600">Main Booking Price:</span>
                                                <span
                                                    class="font-medium text-[#4a3429]">₱{{ number_format(optional($booking->serviceName)->price ?? 0, 2) }}</span>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <span class="text-gray-600">Additional Time Price:</span>
                                                <span
                                                    class="font-medium text-[#4a3429]">₱{{ number_format($extensionAmount ?? ($booking->additional_price ?? 0), 2) }}</span>
                                            </div>
                                            <div
                                                class="flex justify-between items-center pt-2 border-t border-gray-200">
                                                <span class="font-bold text-[#4a3429]">Total Price:</span>
                                                <span class="font-bold text-lg text-[#7f5539]">
                                                    ₱{{ number_format((optional($booking->serviceName)->price ?? 0) + ($extensionAmount ?? ($booking->additional_price ?? 0)), 2) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                    </div>

                    <!-- Next Steps & Info -->
<div class="bg-gray-50 rounded-lg p-6 mb-6 border border-[#e6ddd4]">
    <h3 class="text-xl font-semibold text-[#4a3429] mb-4 flex items-center">
        <i class="fas fa-list-check mr-3 text-[#7f5539]"></i> What Happens Next?
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="flex items-start">
            <div
                class="w-10 h-10 bg-[#f5f0eb] rounded-md flex items-center justify-center mr-3 flex-shrink-0">
                <i class="fas fa-envelope text-[#7f5539]"></i>
            </div>
            <div>
                <p class="font-medium text-[#4a3429]">Email Confirmation</p>
                <p class="text-gray-600 text-sm">Check your email (including Spam)</p>
            </div>
        </div>
        <div class="flex items-start">
            <div
                class="w-10 h-10 bg-[#f5f0eb] rounded-md flex items-center justify-center mr-3 flex-shrink-0">
                <i class="fas fa-qrcode text-[#7f5539]"></i>
            </div>
            <div>
                <p class="font-medium text-[#4a3429]">QR Code</p>
                <p class="text-gray-600 text-sm">Save your QR code attached below the email for check-in</p>
            </div>
        </div>
        <div class="flex items-start">
            <div
                class="w-10 h-10 bg-[#f5f0eb] rounded-md flex items-center justify-center mr-3 flex-shrink-0">
                <i class="fas fa-id-card text-[#7f5539]"></i>
            </div>
            <div>
                <p class="font-medium text-[#4a3429]">Valid ID Required</p>
                <p class="text-gray-600 text-sm">Bring a valid government-issued ID for verification</p>
            </div>
        </div>
    </div>
</div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200">
                        <a href="{{ route('sub_three.home.showHome') }}"
                            class="btn-primary px-6 py-3 rounded-lg font-semibold text-center flex-1">
                            <i class="fas fa-home mr-2"></i> Back to Home
                        </a>
                        <a href="{{ route('sub_three.my_bookings.showMyBookings', ['brn' => $booking->booking_ref_no]) }}"
                            class="btn-secondary px-6 py-3 rounded-lg font-semibold text-center flex-1">
                            <i class="fas fa-calendar-alt mr-2"></i> View My Bookings
                        </a>
                    </div>
                </div>
            </div>

            <!-- QR Code Section -->
            @if (isset($booking->qr_code_path) && $booking->qr_code_path)
                <div class="card mb-6">
                    <div class="p-6 text-center">
                        <h3 class="text-lg font-bold text-[#4a3429] mb-4">Your Check-in QR Code</h3>
                        <div class="qr-code-container inline-block mb-4">
                            <img src="{{ $booking->qr_code_path }}" alt="QR Code" class="w-48 h-48 object-contain">
                        </div>
                        <div class="mt-4">
                            <a href="{{ $booking->qr_code_path }}" download="booking-qr.png"
                                class="px-4 py-2 bg-[#7f5539] hover:bg-[#6b4f3c] text-white text-sm font-medium rounded-lg inline-flex items-center no-print">
                                <i class="fas fa-download mr-2"></i>Download
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>

    <script>
        // Image Modal Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const imageModal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            const closeModalBtn = document.querySelector('.close-modal-btn');
            const zoomInBtn = document.getElementById('zoomInBtn');
            const zoomOutBtn = document.getElementById('zoomOutBtn');
            const resetZoomBtn = document.getElementById('resetZoomBtn');

            let currentScale = 1;
            const scaleStep = 0.2;
            let currentImageSrc = '';
            let currentImageName = '';

            // Open modal when clicking on receipt preview
            document.querySelectorAll('.receipt-preview').forEach(preview => {
                preview.addEventListener('click', function() {
                    currentImageSrc = this.getAttribute('data-receipt-src');
                    currentImageName = this.getAttribute('data-receipt-name') || 'receipt';

                    modalImage.src = currentImageSrc;
                    modalImage.alt = currentImageName;

                    // Reset zoom
                    currentScale = 1;
                    modalImage.style.transform = `scale(${currentScale})`;

                    // Show modal
                    imageModal.style.display = 'block';
                    document.body.style.overflow = 'hidden';
                });
            });

            // Close modal
            function closeModal() {
                imageModal.style.display = 'none';
                document.body.style.overflow = 'auto';
                currentScale = 1;
                modalImage.style.transform = `scale(${currentScale})`;
            }

            closeModalBtn.addEventListener('click', closeModal);

            imageModal.addEventListener('click', function(e) {
                if (e.target === imageModal) {
                    closeModal();
                }
            });

            // Keyboard support
            document.addEventListener('keydown', function(e) {
                if (imageModal.style.display === 'block') {
                    if (e.key === 'Escape') {
                        closeModal();
                    } else if (e.key === '+') {
                        zoomIn();
                    } else if (e.key === '-') {
                        zoomOut();
                    } else if (e.key === '0') {
                        resetZoom();
                    }
                }
            });

            // Zoom functions
            function zoomIn() {
                currentScale += scaleStep;
                modalImage.style.transform = `scale(${currentScale})`;
            }

            function zoomOut() {
                if (currentScale > scaleStep) {
                    currentScale -= scaleStep;
                    modalImage.style.transform = `scale(${currentScale})`;
                }
            }

            function resetZoom() {
                currentScale = 1;
                modalImage.style.transform = `scale(${currentScale})`;
            }

            zoomInBtn.addEventListener('click', zoomIn);
            zoomOutBtn.addEventListener('click', zoomOut);
            resetZoomBtn.addEventListener('click', resetZoom);

            // Touch gestures for mobile
            let touchStartDistance = 0;

            modalImage.addEventListener('touchstart', function(e) {
                if (e.touches.length === 2) {
                    touchStartDistance = Math.hypot(
                        e.touches[0].clientX - e.touches[1].clientX,
                        e.touches[0].clientY - e.touches[1].clientY
                    );
                }
            });

            modalImage.addEventListener('touchmove', function(e) {
                if (e.touches.length === 2) {
                    e.preventDefault();
                    const touchEndDistance = Math.hypot(
                        e.touches[0].clientX - e.touches[1].clientX,
                        e.touches[0].clientY - e.touches[1].clientY
                    );

                    if (touchEndDistance > touchStartDistance + 50) {
                        zoomIn();
                        touchStartDistance = touchEndDistance;
                    } else if (touchEndDistance < touchStartDistance - 50) {
                        zoomOut();
                        touchStartDistance = touchEndDistance;
                    }
                }
            });

            // Double click to zoom in/out
            modalImage.addEventListener('dblclick', function(e) {
                e.preventDefault();
                if (currentScale === 1) {
                    currentScale = 2;
                } else {
                    currentScale = 1;
                }
                modalImage.style.transform = `scale(${currentScale})`;
            });
        });
    </script>
</body>

</html>
