<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Preview & Payment - {{ $bookingDetails['service_name']->service_name ?? 'Booking Preview' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="{{ asset('storage/logo.png') }}" type="image/png">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        /* Custom styles */
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

        /* Modal styles */
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

        .qr-code-container {
            border: 1px solid #e6ddd4;
            border-radius: 0.5rem;
            background: white;
            padding: 1rem;
        }

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

        @media (min-width: 1024px) {
            .sticky-column-lg {
                position: sticky;
                top: 1rem;
                align-self: flex-start;
                height: fit-content;
            }
        }

        .payment-method-item {
            position: relative;
        }

        .payment-method-radio {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
        }

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

        .payment-option.selected {
            border: 2px solid #7f5539;
            background-color: #f5f0eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(127, 85, 57, 0.15);
        }

        #paymentFormSection {
            transition: all 0.3s ease;
        }

        #paymentFormSection.hidden {
            display: none;
        }

        /* Rewards Styles */
        .reward-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid #e6ddd4;
        }

        .reward-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(127, 85, 57, 0.1);
        }

        .reward-card.selected {
            border-color: #7f5539;
            background-color: #f5f0eb;
            box-shadow: 0 4px 12px rgba(127, 85, 57, 0.15);
        }

        .reward-card.applied {
            border-color: #16a34a;
            background-color: #f0fdf4;
            cursor: default;
        }

        .reward-card.applied:hover {
            transform: none;
            box-shadow: none;
        }

        .reward-badge {
            background: #7f5539;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.65rem;
            font-weight: 600;
        }

        .reward-badge-expiring {
            background: #f59e0b;
            color: white;
        }

        .reward-badge-percentage {
            background: #8b5cf6;
            color: white;
        }

        .reward-badge-free {
            background: #10b981;
            color: white;
        }

        /* Terms & Conditions Modal Styles */
        .terms-modal-content {
            max-width: 650px;
            width: 95%;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            padding: 1.5rem;
            overflow: hidden;
        }

        @media (max-width: 640px) {
            .terms-modal-content {
                padding: 1rem;
                width: 92%;
            }
        }

        .terms-modal-content .border-b {
            flex-shrink: 0;
        }

        .terms-content-scrollable {
            max-height: 350px;
            overflow-y: auto;
            border: 1px solid #e6ddd4;
            border-radius: 0.5rem;
            background: #faf8f5;
            scrollbar-width: thin;
            scrollbar-color: #7f5539 #e6ddd4;
            flex-shrink: 1;
        }

        .terms-content-scrollable::-webkit-scrollbar {
            width: 6px;
        }

        .terms-content-scrollable::-webkit-scrollbar-track {
            background: #e6ddd4;
            border-radius: 10px;
        }

        .terms-content-scrollable::-webkit-scrollbar-thumb {
            background: #7f5539;
            border-radius: 10px;
        }

        .terms-content-scrollable::-webkit-scrollbar-thumb:hover {
            background: #6b4f3c;
        }

        .terms-checkbox {
            flex-shrink: 0;
            background: white;
        }

        .terms-modal-content .flex.flex-col {
            flex-shrink: 0;
            margin-top: 0.5rem;
        }

        .terms-checkbox input[type="checkbox"] {
            accent-color: #7f5539;
        }

        .terms-checkbox label {
            cursor: pointer;
            user-select: none;
            color: #4a3429;
        }

        .confirm-btn:disabled,
        #confirmTermsBtn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: #7f5539;
        }

        #confirmTermsBtn:disabled:hover {
            background-color: #7f5539;
            transform: none;
        }

        @media (max-width: 480px) {
            .terms-modal-content .flex-col.sm\:flex-row {
                flex-direction: column-reverse;
            }

            .terms-modal-content .flex-col.sm\:flex-row button {
                width: 100%;
                margin: 0;
                padding: 0.75rem;
            }

            .terms-content-scrollable {
                max-height: 300px;
            }
        }

        @keyframes fadeInTerms {
            from {
                opacity: 0;
                transform: translate(-50%, -30%);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }

        .terms-modal-content {
            animation: fadeInTerms 0.3s ease-out;
        }

        [x-cloak] {
            display: none !important;
        }

        .rewards-scroll {
            max-height: 250px;
            overflow-y: auto;
        }

        .rewards-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .rewards-scroll::-webkit-scrollbar-track {
            background: #f5f0eb;
            border-radius: 10px;
        }

        .rewards-scroll::-webkit-scrollbar-thumb {
            background: #d4c4b2;
            border-radius: 10px;
        }

        .rewards-scroll::-webkit-scrollbar-thumb:hover {
            background: #b08968;
        }
    </style>
</head>

<body class="bg-[#f5f0eb] min-h-screen">
    @php
        // Prepare COMPLETE booking details for payment processing
        $paymentBookingDetails = [];

        function getValue($data, $key, $default = null) {
            if (is_object($data)) {
                return $data->$key ?? $default;
            } elseif (is_array($data)) {
                return $data[$key] ?? $default;
            }
            return $default;
        }

        if (isset($bookingDetails['branch'])) {
            $paymentBookingDetails['branch'] = [
                'id' => getValue($bookingDetails['branch'], 'id'),
                'uuid' => getValue($bookingDetails['branch'], 'uuid'),
            ];
        }

        if (isset($bookingDetails['service_category'])) {
            $paymentBookingDetails['service_category'] = [
                'id' => getValue($bookingDetails['service_category'], 'id'),
                'uuid' => getValue($bookingDetails['service_category'], 'uuid'),
            ];
        }

        if (isset($bookingDetails['service_name'])) {
            $paymentBookingDetails['service_name'] = [
                'id' => getValue($bookingDetails['service_name'], 'id'),
                'uuid' => getValue($bookingDetails['service_name'], 'uuid'),
                'price' => getValue($bookingDetails['service_name'], 'price', 0),
                'time_duration' => getValue($bookingDetails['service_name'], 'time_duration', ''),
            ];
        }

        if (isset($bookingDetails['seat']) && $bookingDetails['seat']) {
            $paymentBookingDetails['seat'] = [
                'id' => getValue($bookingDetails['seat'], 'id'),
            ];
        }

        $paymentBookingDetails['date_from'] = $bookingDetails['date_from'] ?? null;
        $paymentBookingDetails['date_to'] = $bookingDetails['date_to'] ?? null;
        $paymentBookingDetails['booking_time'] = $bookingDetails['booking_time'] ?? null;
        $paymentBookingDetails['end_time'] = $bookingDetails['end_time'] ?? null;
        $paymentBookingDetails['main_duration'] = $bookingDetails['main_duration'] ?? 0;
        $paymentBookingDetails['total_duration'] = $bookingDetails['total_duration'] ?? 0;
        $paymentBookingDetails['additional_hours'] = $bookingDetails['additional_hours'] ?? 0;
        $paymentBookingDetails['additional_minutes'] = $bookingDetails['additional_minutes'] ?? 0;
        $paymentBookingDetails['additional_price'] = $bookingDetails['additional_price'] ?? 0;
        $paymentBookingDetails['total_price'] = $bookingDetails['total_price'] ?? 0;
        $paymentBookingDetails['extended_start_time'] = $bookingDetails['extended_start_time'] ?? null;
        $paymentBookingDetails['extended_end_time'] = $bookingDetails['extended_end_time'] ?? null;
        $paymentBookingDetails['extended_date_start'] = $bookingDetails['extended_date_start'] ?? null;
        $paymentBookingDetails['extended_date_end'] = $bookingDetails['extended_date_end'] ?? null;
        $paymentBookingDetails['extended_duration_total'] = $bookingDetails['extended_duration_minutes'] ?? 0;

        $encodedBookingDetails = base64_encode(json_encode($paymentBookingDetails));

        // Get reward data from controller
        $availableRewards = $availableRewards ?? collect();
        $originalTotalPrice = $bookingDetails['total_price'] ?? 0;
    @endphp

    @if ($errors->any())
        <div class="mb-4">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <strong class="font-bold">Payment Error!</strong>
                <ul class="mt-2 list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        </div>
    @endif

    <!-- Conflict Modal -->
    <div id="conflictPaymentModal" class="modal">
        <div class="modal-content">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-orange-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-orange-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-[#4a3429] mb-2">Booking Already Exists</h3>
                <p class="text-sm text-gray-500 mb-4">
                    This booking information already exists by another user. <br><br>
                    Kindly choose another booking information. Your payment can be used for another booking information
                    and you can add payment if the total amount is above the paid amount.
                </p>
                <div class="flex gap-3 justify-center">
                    <a href="{{ session('conflict_back_url') ?? '#' }}"
                        class="px-4 py-2 bg-[#7f5539] text-white rounded-md hover:bg-[#6b4f3c] transition duration-200 font-medium">
                        Back to Booking Form
                    </a>
                </div>
            </div>
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
                    Going back to edit will reset all your current booking data.
                    All selected dates, times, and seats will be cleared.
                </p>
                <div class="flex gap-3 justify-center">
                    <button type="button" id="modalCancel"
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition duration-200 font-medium">
                        Cancel
                    </button>
                    <a href="#" id="modalConfirmBack"
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

    <!-- Terms & Conditions Modal -->
    <div id="termsModal" class="modal">
        <div class="modal-content terms-modal-content">
            <div class="text-center mb-4 md:mb-6 border-b border-gray-200 pb-4">
                <div class="mx-auto flex items-center justify-center h-12 w-12 md:h-16 md:w-16 rounded-full bg-[#7f5539] mb-3 md:mb-4">
                    <i class="fas fa-file-contract text-white text-xl md:text-2xl"></i>
                </div>
                <h3 class="text-lg md:text-xl font-bold text-[#4a3429] mb-1">Terms & Conditions</h3>
                <p class="text-xs md:text-sm text-gray-600">Please read and accept the terms before proceeding</p>
            </div>

            <div class="terms-content-scrollable mb-4 md:mb-5">
                <div class="terms-content p-4 md:p-6">
                    <h4 class="font-bold text-[#4a3429] mb-3 text-sm md:text-base">Booking Terms & Conditions</h4>

                    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-3 md:p-4 mb-4 rounded-r-lg">
                        <p class="font-bold text-[#4a3429] mb-1 text-xs md:text-sm flex items-center">
                            <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                            Important Notice:
                        </p>
                        <p class="text-xs md:text-sm text-gray-700">All bookings are <strong class="text-[#7f5539]">non-cancellable</strong> once payment is confirmed. Please review your booking details carefully before proceeding.</p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <p class="font-semibold text-[#4a3429] mb-2 text-xs md:text-sm flex items-center">
                                <span class="w-5 h-5 rounded-full bg-[#7f5539] text-white flex items-center justify-center text-xs mr-2 flex-shrink-0">1</span>
                                <span>Booking Policy</span>
                            </p>
                            <ul class="space-y-1.5 pl-7">
                                <li class="text-xs md:text-sm text-gray-700 flex items-start">
                                    <i class="fas fa-circle text-[#7f5539] text-[6px] mt-1.5 mr-2 flex-shrink-0"></i>
                                    <span>All bookings require down payment to be considered confirmed</span>
                                </li>
                                <li class="text-xs md:text-sm text-gray-700 flex items-start">
                                    <i class="fas fa-circle text-[#7f5539] text-[6px] mt-1.5 mr-2 flex-shrink-0"></i>
                                    <span>Once payment is processed, bookings cannot be cancelled</span>
                                </li>
                                <li class="text-xs md:text-sm text-gray-700 flex items-start">
                                    <i class="fas fa-circle text-[#7f5539] text-[6px] mt-1.5 mr-2 flex-shrink-0"></i>
                                    <span>Refunds are not available for any bookings</span>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <p class="font-semibold text-[#4a3429] mb-2 text-xs md:text-sm flex items-center">
                                <span class="w-5 h-5 rounded-full bg-[#7f5539] text-white flex items-center justify-center text-xs mr-2 flex-shrink-0">2</span>
                                <span>Rescheduling Policy</span>
                            </p>
                            <ul class="space-y-1.5 pl-7">
                                <li class="text-xs md:text-sm text-gray-700 flex items-start">
                                    <i class="fas fa-circle text-[#7f5539] text-[6px] mt-1.5 mr-2 flex-shrink-0"></i>
                                    <span>Rescheduling is only available for bookings marked as "No-Show."</span>
                                </li>
                                <li class="text-xs md:text-sm text-gray-700 flex items-start">
                                    <i class="fas fa-circle text-[#7f5539] text-[6px] mt-1.5 mr-2 flex-shrink-0"></i>
                                    <span>Additional fees may apply for extension.</span>
                                </li>
                                <li class="text-xs md:text-sm text-gray-700 flex items-start">
                                    <i class="fas fa-circle text-[#7f5539] text-[6px] mt-1.5 mr-2 flex-shrink-0"></i>
                                    <span>Rescheduling is subject to seat/room availability.</span>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <p class="font-semibold text-[#4a3429] mb-2 text-xs md:text-sm flex items-center">
                                <span class="w-5 h-5 rounded-full bg-[#7f5539] text-white flex items-center justify-center text-xs mr-2 flex-shrink-0">3</span>
                                <span>No-Show Policy</span>
                            </p>
                            <ul class="space-y-1.5 pl-7">
                                <li class="text-xs md:text-sm text-gray-700 flex items-start">
                                    <i class="fas fa-circle text-[#7f5539] text-[6px] mt-1.5 mr-2 flex-shrink-0"></i>
                                    <span>If a customer fails to attend, they may mark the booking as "No-Show."</span>
                                </li>
                                <li class="text-xs md:text-sm text-gray-700 flex items-start">
                                    <i class="fas fa-circle text-[#7f5539] text-[6px] mt-1.5 mr-2 flex-shrink-0"></i>
                                    <span>No-show bookings forfeit their payment and time slot.</span>
                                </li>
                                <li class="text-xs md:text-sm text-gray-700 flex items-start">
                                    <i class="fas fa-circle text-[#7f5539] text-[6px] mt-1.5 mr-2 flex-shrink-0"></i>
                                    <span>Once marked as "No-Show," the booking may be rescheduled.</span>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <p class="font-semibold text-[#4a3429] mb-2 text-xs md:text-sm flex items-center">
                                <span class="w-5 h-5 rounded-full bg-[#7f5539] text-white flex items-center justify-center text-xs mr-2 flex-shrink-0">4</span>
                                <span>Payment Terms</span>
                            </p>
                            <ul class="space-y-1.5 pl-7">
                                <li class="text-xs md:text-sm text-gray-700 flex items-start">
                                    <i class="fas fa-circle text-[#7f5539] text-[6px] mt-1.5 mr-2 flex-shrink-0"></i>
                                    <span>Payments via GCash only</span>
                                </li>
                                <li class="text-xs md:text-sm text-gray-700 flex items-start">
                                    <i class="fas fa-circle text-[#7f5539] text-[6px] mt-1.5 mr-2 flex-shrink-0"></i>
                                    <span>Failed payments due to conflict may be used for another booking</span>
                                </li>
                                <li class="text-xs md:text-sm text-gray-700 flex items-start">
                                    <i class="fas fa-circle text-[#7f5539] text-[6px] mt-1.5 mr-2 flex-shrink-0"></i>
                                    <span>Successful payments are final—no changes or cancellations</span>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <p class="font-semibold text-[#4a3429] mb-2 text-xs md:text-sm flex items-center">
                                <span class="w-5 h-5 rounded-full bg-[#7f5539] text-white flex items-center justify-center text-xs mr-2 flex-shrink-0">5</span>
                                <span>General Terms</span>
                            </p>
                            <ul class="space-y-1.5 pl-7">
                                <li class="text-xs md:text-sm text-gray-700 flex items-start">
                                    <i class="fas fa-circle text-[#7f5539] text-[6px] mt-1.5 mr-2 flex-shrink-0"></i>
                                    <span>Please arrive 2 hours before your scheduled time</span>
                                </li>
                                <li class="text-xs md:text-sm text-gray-700 flex items-start">
                                    <i class="fas fa-circle text-[#7f5539] text-[6px] mt-1.5 mr-2 flex-shrink-0"></i>
                                    <span>Late arrivals may result in reduced service time</span>
                                </li>
                                <li class="text-xs md:text-sm text-gray-700 flex items-start">
                                    <i class="fas fa-circle text-[#7f5539] text-[6px] mt-1.5 mr-2 flex-shrink-0"></i>
                                    <span>Right to refuse service to policy violators</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <p class="text-xs md:text-sm text-gray-600 mt-4 pt-3 border-t border-gray-200 italic">
                        By proceeding, you acknowledge that you have read and agree to all terms above.
                    </p>
                </div>
            </div>

            <div class="terms-checkbox mb-4 px-1 border-t border-gray-100 pt-3">
                <div class="flex items-start gap-2 md:gap-3">
                    <input type="checkbox" id="agreeTerms" class="mt-1 w-4 h-4 md:w-5 md:h-5 accent-[#7f5539] cursor-pointer flex-shrink-0">
                    <label for="agreeTerms" class="text-xs md:text-sm text-gray-700 cursor-pointer leading-relaxed">
                        I have read, understood, and agree to the Terms & Conditions.
                    </label>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 justify-center mt-2">
                <button type="button" id="cancelTermsBtn"
                    class="px-4 py-2.5 md:px-6 md:py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-semibold transition duration-200 flex items-center justify-center text-sm md:text-base order-2 sm:order-1 w-full sm:w-auto">
                    <i class="fas fa-times mr-2"></i>
                    Cancel
                </button>
                <button type="button" id="confirmTermsBtn"
                    class="px-4 py-2.5 md:px-6 md:py-3 bg-[#7f5539] hover:bg-[#6b4f3c] text-white rounded-lg font-semibold transition duration-200 flex items-center justify-center text-sm md:text-base order-1 sm:order-2 w-full sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled>
                    <i class="fas fa-check-circle mr-2"></i>
                    <span>Confirm & Proceed</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-[#f5f0eb] to-[#e6ddd4] py-6">
        <div class="container mx-auto px-3">
            <div class="max-w-2xl mx-auto text-center">
                <h1 class="text-xl md:text-2xl font-bold text-[#4a3429] mb-2 leading-tight">Booking Preview & Payment</h1>
                <p class="text-gray-600 text-xs md:text-sm">Review your booking details and complete payment</p>
            </div>
        </div>
    </section>

    <div class="container mx-auto px-3 py-6">
        @if (!isset($bookingDetails) || is_null($bookingDetails))
            <div class="card">
                <div class="bg-red-100 text-red-800 p-6 text-center border-b border-red-200">
                    <i class="fas fa-exclamation-triangle text-4xl mb-3"></i>
                    <h1 class="text-2xl font-bold">Booking Not Found</h1>
                    <p class="text-red-600">Your booking session has expired or is invalid</p>
                </div>
                <div class="p-6 text-center">
                    <p class="text-gray-600 mb-6">Please start your booking process again.</p>
                    <a href="{{ route('sub_three.home.showHome') }}"
                        class="bg-[#7f5539] hover:bg-[#6b4f3c] text-white px-6 py-3 rounded-lg font-semibold transition duration-300 inline-flex items-center">
                        <i class="fas fa-home mr-2"></i>
                        Return to Home
                    </a>
                </div>
            </div>
        @else
            <div class="@if ($showPayment) flex flex-col lg:flex-row gap-6 @else max-w-4xl mx-auto @endif">
                <!-- Left Column - Booking Preview Card -->
                <div class="@if ($showPayment) lg:w-1/2 @endif @if ($showPayment) sticky-column-lg @endif">
                    <div class="card">
                        <div class="bg-[#7f5539] text-white p-6 text-center rounded-t-lg">
                            <i class="fas fa-check-circle text-4xl mb-3"></i>
                            <h1 class="text-2xl font-bold">Booking Preview</h1>
                            <p class="text-[#f5f0eb]">Review your booking details</p>
                        </div>

                        <div class="p-6">
                            <!-- Booking Summary -->
                            <div class="bg-gray-50 rounded-lg p-6 mb-6">
                                <h3 class="text-xl font-semibold text-gray-800 mb-4">Booking Summary</h3>
                                <div class="grid grid-cols-1 gap-6">
                                    <div>
                                        <h4 class="font-medium text-gray-700 mb-2">Service Details</h4>
                                        <div class="space-y-2 text-sm">
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Service:</span>
                                                <span class="font-medium">{{ $bookingDetails['service_name']->service_name ?? 'N/A' }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Category:</span>
                                                <span class="font-medium">{{ $bookingDetails['service_category']->service_category ?? 'N/A' }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Branch:</span>
                                                <span class="font-medium">{{ $bookingDetails['branch']->branch_name ?? 'N/A' }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Location:</span>
                                                <span class="font-medium text-right">{{ $bookingDetails['branch']->location ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-700 mb-2">Booking Information</h4>
                                        <div class="space-y-2 text-sm">
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Date From:</span>
                                                <span class="font-medium">
                                                    @if (isset($bookingDetails['date_from']))
                                                        {{ \Carbon\Carbon::parse($bookingDetails['date_from'])->format('F j, Y') }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Date To:</span>
                                                <span class="font-medium">
                                                    @if (isset($bookingDetails['date_to']))
                                                        {{ \Carbon\Carbon::parse($bookingDetails['date_to'])->format('F j, Y') }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Time:</span>
                                                <span class="font-medium">
                                                    @if (isset($bookingDetails['booking_time']))
                                                        {{ \Carbon\Carbon::parse($bookingDetails['booking_time'])->format('h:i A') }}
                                                    @else
                                                        N/A
                                                    @endif
                                                    -
                                                    @if (isset($bookingDetails['end_time']))
                                                        {{ \Carbon\Carbon::parse($bookingDetails['end_time'])->format('h:i A') }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Duration:</span>
                                                <span class="font-medium">{{ $bookingDetails['service_name']->time_duration ?? 'N/A' }}</span>
                                            </div>
                                            @if (isset($bookingDetails['seat']) && $bookingDetails['seat'])
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600">Seat/Room:</span>
                                                    <span class="font-medium">
                                                        {{ $bookingDetails['seat']->seat_no ? 'Seat ' . $bookingDetails['seat']->seat_no : 'Room ' . $bookingDetails['seat']->room_no }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    @if (isset($bookingDetails['additional_hours']) && $bookingDetails['additional_hours'] > 0)
                                        <div class="bg-purple-50 rounded-lg p-6 mb-6 additional-time-section">
                                            <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                                                <i class="fas fa-plus-circle text-purple-500 mr-2"></i>
                                                Additional Time Summary
                                            </h3>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <div class="space-y-3">
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600 font-medium">Main Booking Start:</span>
                                                        <span class="font-semibold">
                                                            {{ \Carbon\Carbon::parse($bookingDetails['booking_time'])->format('h:i A') }}
                                                        </span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600 font-medium">Main Booking End:</span>
                                                        <span class="font-semibold">
                                                            {{ \Carbon\Carbon::parse($bookingDetails['end_time'])->format('h:i A') }}
                                                        </span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600 font-medium">Extended Start:</span>
                                                        <span class="font-semibold">
                                                            @if (isset($bookingDetails['extended_start_time']))
                                                                {{ \Carbon\Carbon::parse($bookingDetails['extended_start_time'])->format('h:i A') }}
                                                            @else
                                                                N/A
                                                            @endif
                                                        </span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600 font-medium">Extended End:</span>
                                                        <span class="font-semibold">
                                                            @if (isset($bookingDetails['extended_end_time']))
                                                                {{ \Carbon\Carbon::parse($bookingDetails['extended_end_time'])->format('h:i A') }}
                                                            @else
                                                                N/A
                                                            @endif
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="space-y-3">
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600 font-medium">Date Start:</span>
                                                        <span class="font-semibold">
                                                            {{ \Carbon\Carbon::parse($bookingDetails['date_from'])->format('M j, Y') }}
                                                        </span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600 font-medium">Date End:</span>
                                                        <span class="font-semibold">
                                                            {{ \Carbon\Carbon::parse($bookingDetails['date_to'])->format('M j, Y') }}
                                                        </span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600 font-medium">Extended Date Start:</span>
                                                        <span class="font-semibold">
                                                            @if (isset($bookingDetails['extended_date_start']))
                                                                {{ \Carbon\Carbon::parse($bookingDetails['extended_date_start'])->format('M j, Y') }}
                                                            @else
                                                                N/A
                                                            @endif
                                                        </span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600 font-medium">Extended Date End:</span>
                                                        <span class="font-semibold">
                                                            @if (isset($bookingDetails['extended_date_end']))
                                                                {{ \Carbon\Carbon::parse($bookingDetails['extended_date_end'])->format('M j, Y') }}
                                                            @else
                                                                N/A
                                                            @endif
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-4 pt-4 border-t border-purple-200">
                                                <div class="flex justify-between items-center">
                                                    <div>
                                                        <div class="text-sm text-gray-600">Main Booking Price:</div>
                                                        <div class="text-sm text-gray-600">Additional Time Price:</div>
                                                        <div class="text-lg font-bold text-gray-800 mt-1">Total Price:</div>
                                                    </div>
                                                    <div class="text-right">
                                                        <div class="text-sm text-gray-600">
                                                            ₱{{ number_format($bookingDetails['service_name']->price, 2) }}
                                                        </div>
                                                        <div class="text-sm text-gray-600">
                                                            +₱{{ number_format($bookingDetails['additional_price'] ?? 0, 2) }}
                                                        </div>
                                                        <div class="text-lg font-bold text-purple-700 mt-1">
                                                            ₱{{ number_format($bookingDetails['total_price'], 2) }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-2 text-xs text-purple-600">
                                                    Additional time: {{ $bookingDetails['extended_duration'] ?? '0 hours' }}
                                                </div>
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

                            <!-- Pricing -->
                            <div class="bg-gray-50 rounded-lg p-6">
                                <h3 class="text-xl font-semibold text-gray-800 mb-4">Pricing</h3>
                                <div class="space-y-2 text-sm" id="pricingSection">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Service Price:</span>
                                        <span class="font-medium">
                                            ₱{{ number_format($bookingDetails['service_name']->price ?? 0, 2) }}
                                        </span>
                                    </div>

                                    @if (isset($bookingDetails['additional_price']) && $bookingDetails['additional_price'] > 0)
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Additional Time:</span>
                                            <span class="font-medium">
                                                +₱{{ number_format($bookingDetails['additional_price'] ?? 0, 2) }}
                                            </span>
                                        </div>
                                    @endif

                                    <!-- Reward Discount Display -->
                                    <div id="rewardDiscountDisplay" style="display: none;">
                                        <div class="flex justify-between text-green-600 font-medium">
                                            <span>Reward Discount:</span>
                                            <span>-₱<span id="rewardDiscountAmountDisplay">0.00</span></span>
                                        </div>
                                    </div>

                                    <div class="flex justify-between border-t border-gray-200 pt-2">
                                        <span class="text-lg font-semibold text-gray-800">Total Amount:</span>
                                        <span class="text-lg font-bold text-green-600" id="totalAmountDisplay">
                                            ₱{{ number_format($bookingDetails['total_price'] ?? 0, 2) }}
                                        </span>
                                    </div>
                                    <p id="discountNote" style="display: none;" class="text-xs text-green-600 text-right">
                                        *Discount applied
                                    </p>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-col md:flex-row justify-between space-y-4 md:space-y-0 md:space-x-4 pt-6 border-t border-gray-200">
                                <button type="button" id="backToEditBtn"
                                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold transition duration-300 flex items-center justify-center">
                                    <i class="fas fa-arrow-left mr-2"></i>
                                    Back to Booking Form
                                </button>

                                @if (!$showPayment)
                                    <button type="button" id="proceedToPaymentBtn"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold transition duration-300 flex items-center justify-center w-full">
                                        <i class="fas fa-credit-card mr-2"></i>
                                        Proceed to Payment
                                    </button>

                                    <form action="{{ route('sub_three.home.payment.options') }}" method="POST"
                                        id="proceedToPaymentForm" style="display: none;">
                                        @csrf
                                        <input type="hidden" name="branch_id" value="{{ $bookingDetails['branch']->id ?? '' }}">
                                        <input type="hidden" name="branch_uuid" value="{{ $bookingDetails['branch']->uuid ?? '' }}">
                                        <input type="hidden" name="branch_name" value="{{ $bookingDetails['branch']->branch_name ?? '' }}">
                                        <input type="hidden" name="branch_location" value="{{ $bookingDetails['branch']->location ?? '' }}">
                                        <input type="hidden" name="branch_open_time" value="{{ $bookingDetails['branch']->open_time ?? '' }}">
                                        <input type="hidden" name="branch_close_time" value="{{ $bookingDetails['branch']->close_time ?? '' }}">
                                        <input type="hidden" name="service_category_id" value="{{ $bookingDetails['service_category']->id ?? '' }}">
                                        <input type="hidden" name="service_category_uuid" value="{{ $bookingDetails['service_category']->uuid ?? '' }}">
                                        <input type="hidden" name="service_category_name" value="{{ $bookingDetails['service_category']->service_category ?? '' }}">
                                        <input type="hidden" name="service_name_id" value="{{ $bookingDetails['service_name']->id ?? '' }}">
                                        <input type="hidden" name="service_name_uuid" value="{{ $bookingDetails['service_name']->uuid ?? '' }}">
                                        <input type="hidden" name="service_name" value="{{ $bookingDetails['service_name']->service_name ?? '' }}">
                                        <input type="hidden" name="service_time_duration" value="{{ $bookingDetails['service_name']->time_duration ?? '' }}">
                                        <input type="hidden" name="service_price" value="{{ $bookingDetails['service_name']->price ?? 0 }}">
                                        <input type="hidden" name="service_space_type" value="{{ $bookingDetails['service_name']->space_type ?? '' }}">
                                        @if (isset($bookingDetails['seat']) && $bookingDetails['seat'])
                                            <input type="hidden" name="seat_id" value="{{ $bookingDetails['seat']->id ?? '' }}">
                                            <input type="hidden" name="seat_display_label" value="{{ $bookingDetails['seat']->display_label ?? ($bookingDetails['seat']->seat_no ?? ($bookingDetails['seat']->room_no ?? '')) }}">
                                        @endif
                                        <input type="hidden" name="date_from" value="{{ $bookingDetails['date_from'] ?? '' }}">
                                        <input type="hidden" name="date_to" value="{{ $bookingDetails['date_to'] ?? '' }}">
                                        <input type="hidden" name="booking_time" value="{{ $bookingDetails['booking_time'] ?? '' }}">
                                        <input type="hidden" name="end_time" value="{{ $bookingDetails['end_time'] ?? '' }}">
                                        <input type="hidden" name="main_duration" value="{{ $bookingDetails['main_duration'] ?? 0 }}">
                                        <input type="hidden" name="total_duration" value="{{ $bookingDetails['total_duration'] ?? ($bookingDetails['main_duration'] ?? 0) }}">
                                        <input type="hidden" name="additional_hours" value="{{ $bookingDetails['additional_hours'] ?? 0 }}">
                                        <input type="hidden" name="additional_minutes" value="{{ $bookingDetails['additional_minutes'] ?? 0 }}">
                                        <input type="hidden" name="additional_price" value="{{ $bookingDetails['additional_price'] ?? 0 }}">
                                        <input type="hidden" name="total_price" value="{{ $bookingDetails['total_price'] ?? ($bookingDetails['service_name']->price ?? 0) }}">
                                        @if (isset($bookingDetails['extended_start_time']))
                                            <input type="hidden" name="extended_start_time" value="{{ $bookingDetails['extended_start_time'] ?? '' }}">
                                            <input type="hidden" name="extended_end_time" value="{{ $bookingDetails['extended_end_time'] ?? '' }}">
                                            <input type="hidden" name="extended_start_date" value="{{ $bookingDetails['extended_date_start'] ?? ($bookingDetails['date_to'] ?? '') }}">
                                            <input type="hidden" name="extended_end_date" value="{{ $bookingDetails['extended_date_end'] ?? ($bookingDetails['date_to'] ?? '') }}">
                                            <input type="hidden" name="extended_duration_total" value="{{ $bookingDetails['extended_duration_minutes'] ?? ($bookingDetails['parsed_extended_duration_minutes'] ?? 0) }}">
                                        @endif
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Payment Form & Rewards -->
                @if ($showPayment)
                    <div class="@if ($showPayment) lg:w-1/2 @endif space-y-6">
                        <!-- ===== REWARDS SECTION ===== -->
                        <div class="card">
                            <div class="bg-gradient-to-r from-purple-600 to-purple-800 text-white p-4 text-center rounded-t-lg">
                                <i class="fas fa-gift text-3xl mb-2"></i>
                                <h2 class="text-xl font-bold">🎁 Available Rewards</h2>
                                <p class="text-sm text-purple-100" id="rewardsStatusText">
                                    @if ($availableRewards->count() > 0)
                                        You have {{ $availableRewards->count() }} reward(s) available!
                                    @else
                                        No rewards available
                                    @endif
                                </p>
                            </div>

                            <div class="p-4">
                                @if ($availableRewards->count() > 0)
                                    <div class="rewards-scroll space-y-3">
                                        @foreach ($availableRewards as $reward)
                                            <div class="reward-card bg-white rounded-xl p-4" 
                                                 data-reward-id="{{ $reward['id'] }}"
                                                 data-reward-type="{{ $reward['reward_type'] }}"
                                                 data-discount-value="{{ $reward['discount_value'] }}"
                                                 data-is-percentage="{{ $reward['is_percentage'] ? 'true' : 'false' }}"
                                                 data-percentage="{{ $reward['percentage'] }}"
                                                 data-voucher-code="{{ $reward['voucher_code'] }}"
                                                 data-description="{{ $reward['description'] }}">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex-1">
                                                        <div class="flex items-center gap-2 flex-wrap">
                                                            <span class="font-medium text-gray-800">{{ $reward['description'] ?? 'Reward' }}</span>
                                                            @if (isset($reward['days_left']) && $reward['days_left'] !== null && $reward['days_left'] >= 0)
                                                                <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full">
                                                                    {{ $reward['days_left'] }} days left
                                                                </span>
                                                            @endif
                                                            @if ($reward['is_percentage'] ?? false)
                                                                <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded-full">
                                                                    {{ $reward['percentage'] }}% off
                                                                </span>
                                                            @endif
                                                            @if (($reward['reward_type'] ?? '') === 'free_service')
                                                                <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">
                                                                    Free Service
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <div class="text-sm text-gray-600 mt-1">{{ $reward['item_name'] ?? '' }}</div>
                                                        <div class="text-xs text-gray-500 mt-1 font-mono">Voucher: {{ $reward['voucher_code'] ?? 'N/A' }}</div>
                                                    </div>
                                                    <div class="text-right ml-4">
                                                        <div class="font-bold text-green-600">{{ $reward['discount_display'] ?? '₱0.00' }}</div>
                                                        <div class="text-xs text-gray-500">Expires: {{ $reward['expiration_date'] ?? 'No expiry' }}</div>
                                                    </div>
                                                </div>
                                                <div class="mt-2 text-xs text-purple-600 flex items-center gap-1 reward-select-indicator" style="display: none;">
                                                    <svg class="w-4 h-4 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                    </svg>
                                                    Selected - Click "Apply Reward" to use
                                                </div>
                                                <div class="mt-2 text-xs text-green-600 flex items-center gap-1 reward-applied-indicator" style="display: none;">
                                                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                    </svg>
                                                    ✓ Applied to this booking
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="mt-4 flex flex-wrap gap-3 justify-end">
                                        <button type="button" id="applyRewardBtn"
                                            class="px-6 py-2 bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-xl font-medium hover:from-purple-700 hover:to-purple-800 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                            Apply Reward
                                        </button>
                                        <button type="button" id="removeRewardBtn"
                                            class="px-6 py-2 bg-red-600 text-white rounded-xl font-medium hover:bg-red-700 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                            Remove Reward
                                        </button>
                                    </div>

                                    <div id="appliedRewardSummary" style="display: none;" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-xl">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <span class="text-sm font-medium text-green-800">✅ Reward Applied</span>
                                                <p class="text-sm text-green-700 mt-1" id="appliedRewardDescription"></p>
                                                <p class="text-xs text-green-600" id="appliedRewardDiscount"></p>
                                                <p class="text-xs text-green-600 font-mono" id="appliedRewardVoucher"></p>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="text-center py-4 text-gray-600">
                                        <svg class="h-12 w-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                        </svg>
                                        <p class="text-sm">No rewards available for this booking.</p>
                                        <p class="text-xs text-gray-400 mt-1">Complete more bookings to earn rewards!</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Payment Options Section -->
                        @if (isset($bookingDetails['additional_price']) && $bookingDetails['additional_price'] > 0)
                            <div class="card">
                                <div class="bg-[#7f5539] text-white p-4 text-center rounded-t-lg">
                                    <i class="fas fa-credit-card text-3xl mb-2"></i>
                                    <h2 class="text-xl font-bold">Choose Payment Option</h2>
                                    <p class="text-sm text-[#f5f0eb]">Select how you want to pay for your booking</p>
                                </div>

                                <div class="p-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="payment-option p-4 rounded-lg bg-white cursor-pointer"
                                            data-payment-type="full" 
                                            data-payment-amount="{{ $bookingDetails['total_price'] }}"
                                            data-payment-display="Pay Full Amount: ₱{{ number_format($bookingDetails['total_price'], 2) }}">
                                            <div class="text-center">
                                                <div class="w-12 h-12 mx-auto mb-3 icon-box flex items-center justify-center">
                                                    <i class="fas fa-check-circle text-[#7f5539] text-xl"></i>
                                                </div>
                                                <h3 class="text-base font-semibold text-[#4a3429] mb-1">Pay Full Amount</h3>
                                                <p class="text-[#7f5539] font-bold text-lg mb-2">
                                                    ₱{{ number_format($bookingDetails['total_price'], 2) }}
                                                </p>
                                                <div class="text-left text-xs text-gray-600 space-y-1">
                                                    <div class="flex items-center">
                                                        <i class="fas fa-check text-[#7f5539] mr-2"></i>
                                                        <span>Service: ₱{{ number_format($bookingDetails['service_name']->price, 2) }}</span>
                                                    </div>
                                                    <div class="flex items-center">
                                                        <i class="fas fa-check text-[#7f5539] mr-2"></i>
                                                        <span>Extended Time: ₱{{ number_format($bookingDetails['additional_price'], 2) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="payment-option p-4 rounded-lg bg-white cursor-pointer"
                                            data-payment-type="service_only"
                                            data-payment-amount="{{ $bookingDetails['service_name']->price }}"
                                            data-payment-display="Pay Service Only: ₱{{ number_format($bookingDetails['service_name']->price, 2) }}">
                                            <div class="text-center">
                                                <div class="w-12 h-12 mx-auto mb-3 icon-box flex items-center justify-center">
                                                    <i class="fas fa-clock text-[#7f5539] text-xl"></i>
                                                </div>
                                                <h3 class="text-base font-semibold text-[#4a3429] mb-1">Pay Service Only</h3>
                                                <p class="text-[#7f5539] font-bold text-lg mb-2">
                                                    ₱{{ number_format($bookingDetails['service_name']->price, 2) }}
                                                </p>
                                                <div class="text-left text-xs text-gray-600 space-y-1">
                                                    <div class="flex items-center">
                                                        <i class="fas fa-check text-[#7f5539] mr-2"></i>
                                                        <span>Service: ₱{{ number_format($bookingDetails['service_name']->price, 2) }}</span>
                                                    </div>
                                                    <div class="flex items-center">
                                                        <i class="fas fa-clock text-[#9c6644] mr-2"></i>
                                                        <span>Extended Time: Pay later</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Payment Form Section -->
                        <div id="paymentFormSection" class="card @if (isset($bookingDetails['additional_price']) && $bookingDetails['additional_price'] > 0) hidden @endif">
                            <div class="bg-[#7f5539] text-white p-4 text-center rounded-t-lg">
                                <i class="fas fa-credit-card text-3xl mb-2"></i>
                                <h2 id="paymentFormTitle" class="text-xl font-bold">
                                    @if (isset($bookingDetails['additional_price']) && $bookingDetails['additional_price'] > 0)
                                        Select a Payment Option Above
                                    @else
                                        Complete Your Payment
                                    @endif
                                </h2>
                                <p id="paymentAmountText" class="text-sm text-[#f5f0eb]">
                                    @if (!isset($bookingDetails['additional_price']) || $bookingDetails['additional_price'] <= 0)
                                        Pay the service amount: ₱{{ number_format($bookingDetails['service_name']->price, 2) }}
                                    @else
                                        Choose payment option first
                                    @endif
                                </p>
                            </div>

                            <div class="p-4">
                                <form action="{{ route('sub_three.home.processPayment') }}" method="POST"
                                    enctype="multipart/form-data" class="space-y-4" id="paymentForm">
                                    @csrf

                                    <input type="hidden" name="booking_details" value="{{ $encodedBookingDetails }}">
                                    <input type="hidden" name="payment_type" id="paymentTypeInput" 
                                        value="@if (!isset($bookingDetails['additional_price']) || $bookingDetails['additional_price'] <= 0) full @endif">
                                    
                                    <!-- Reward Hidden Fields -->
                                    <input type="hidden" name="customer_reward_id" id="customerRewardId" value="">
                                    <input type="hidden" name="reward_discount_amount" id="rewardDiscountAmount" value="0">
                                    <input type="hidden" name="reward_voucher_code" id="rewardVoucherCode" value="">

                                    <!-- Payment Instructions -->
                                    <div class="bg-[#f5f0eb] border border-[#e6ddd4] rounded-lg p-4">
                                        <h4 class="font-semibold text-[#4a3429] mb-3 flex items-center">
                                            <i class="fas fa-info-circle mr-2 text-[#7f5539]"></i>GCash Payment Instructions
                                        </h4>
                                        
                                        <div id="paymentInstructions">
                                            @if (isset($ownerGcashQrCode) && count($ownerGcashQrCode) > 0)
                                                <!-- Will be populated by JavaScript -->
                                            @else
                                                <p class="text-sm text-[#4a3429] mb-4">
                                                    G-Cash QR codes are currently unavailable. Please contact the branch through their social media for more payment details.
                                                </p>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Payment Method -->
                                    <div>
                                        <label class="block text-sm font-medium text-[#4a3429] mb-2">Payment Method</label>
                                        <div class="bg-[#f5f0eb] rounded-lg p-4 border border-[#e6ddd4] payment-method-item">
                                            <div class="flex items-center space-x-3">
                                                <input type="radio" name="payment_method" value="1" checked class="payment-method-radio">
                                                <div class="w-10 h-10 icon-box flex items-center justify-center">
                                                    <i class="fas fa-mobile-alt text-[#7f5539]"></i>
                                                </div>
                                                <div class="flex-1">
                                                    <h4 class="font-semibold text-[#4a3429]">GCash</h4>
                                                    <p class="text-sm text-gray-600">Pay using your GCash account</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- GCash Reference Number -->
                                    <div>
                                        <label class="block text-sm font-medium text-[#4a3429] mb-2">GCash Reference Number</label>
                                        <input type="text" name="gcash_ref_no" required
                                            placeholder="Enter GCash reference number" class="form-input">
                                        <p class="text-sm text-gray-500 mt-1">Find this in your GCash transaction history</p>
                                    </div>

                                    <!-- Notes -->
                                    <div>
                                        <label class="block text-sm font-medium text-[#4a3429] mb-2">Additional Notes (Optional)</label>
                                        <textarea name="notes" placeholder="Add any additional notes or instructions for your booking..." rows="3"
                                            class="form-input resize-none"></textarea>
                                        <p class="text-sm text-gray-500 mt-1">You can add special requests or notes about your booking</p>
                                    </div>

                                    <!-- GCash Receipt Upload -->
                                    <div>
                                        <label class="block text-sm font-medium text-[#4a3429] mb-2">GCash Receipt Screenshot</label>
                                        <div class="file-input-wrapper">
                                            <input type="file" name="gcash_receipt_img" accept="image/*" required 
                                                class="hidden" id="paymentFileInput">
                                            
                                            <div id="paymentUploadArea" class="file-upload-area rounded-lg p-6 text-center cursor-pointer flex flex-col items-center justify-center">
                                                <div id="paymentDefaultState" class="text-center">
                                                    <div class="w-12 h-12 mx-auto mb-4 icon-box flex items-center justify-center">
                                                        <i class="fas fa-cloud-upload-alt text-[#7f5539] text-xl"></i>
                                                    </div>
                                                    <p class="text-[#4a3429] mb-2 font-medium">Upload your GCash receipt</p>
                                                    <p class="text-xs text-gray-500 mb-4">Supports: JPG, PNG, GIF • Max: 2MB</p>
                                                    <button type="button" class="choose-file-btn">
                                                        <i class="fas fa-folder-open mr-2"></i>Choose File
                                                    </button>
                                                </div>
                                                
                                                <div id="paymentImagePreviewState" class="hidden text-center w-full">
                                                    <div class="mb-4 image-preview-container">
                                                        <img id="paymentImagePreview" src="" alt="Receipt Preview" class="image-preview mx-auto">
                                                        <div class="image-preview-overlay" data-action="zoom"></div>
                                                    </div>
                                                    <div class="flex justify-center gap-3">
                                                        <button type="button" class="choose-file-btn">
                                                            <i class="fas fa-sync-alt mr-2"></i>Change File
                                                        </button>
                                                        <button type="button" class="choose-file-btn bg-red-600 hover:bg-red-700" id="paymentRemoveImageBtn">
                                                            <i class="fas fa-trash mr-2"></i>Remove
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="paymentFileError" class="text-red-500 text-sm mt-2 hidden"></div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="pt-4">
                                        <button type="submit" id="submitPaymentBtn"
                                            class="w-full px-6 py-3 bg-[#7f5539] hover:bg-[#6b4f3c] text-white rounded-md font-semibold transition duration-300 flex items-center justify-center">
                                            <i class="fas fa-lock mr-2"></i>
                                            <span id="submitButtonText">
                                                @if (!isset($bookingDetails['additional_price']) || $bookingDetails['additional_price'] <= 0)
                                                    Complete Payment
                                                @else
                                                    Select Payment Option First
                                                @endif
                                            </span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
    // ================================================================
    // REWARDS FUNCTIONALITY
    // ================================================================
    const rewardCards = document.querySelectorAll('.reward-card');
    const applyRewardBtn = document.getElementById('applyRewardBtn');
    const removeRewardBtn = document.getElementById('removeRewardBtn');
    const appliedRewardSummary = document.getElementById('appliedRewardSummary');
    const appliedRewardDescription = document.getElementById('appliedRewardDescription');
    const appliedRewardDiscount = document.getElementById('appliedRewardDiscount');
    const appliedRewardVoucher = document.getElementById('appliedRewardVoucher');

    let selectedRewardId = null;
    let appliedRewardData = null;
    let originalTotal = {{ $originalTotalPrice }};
    let currentPaymentAmount = originalTotal;
    let selectedPaymentType = 'full';

    // Only initialize rewards if there are reward cards
    if (rewardCards.length > 0 && applyRewardBtn && removeRewardBtn) {
        // Initialize reward card clicks
        rewardCards.forEach(card => {
            card.addEventListener('click', function() {
                // If a reward is already applied, don't allow selection
                if (appliedRewardData) {
                    return;
                }

                // Deselect all cards
                rewardCards.forEach(c => {
                    c.classList.remove('selected');
                    const indicator = c.querySelector('.reward-select-indicator');
                    if (indicator) indicator.style.display = 'none';
                });

                // Select this card
                this.classList.add('selected');
                const indicator = this.querySelector('.reward-select-indicator');
                if (indicator) indicator.style.display = 'flex';
                selectedRewardId = this.dataset.rewardId;

                // Enable apply button
                applyRewardBtn.disabled = false;
            });
        });

        // Apply Reward
        applyRewardBtn.addEventListener('click', function() {
            if (!selectedRewardId) {
                alert('Please select a reward first.');
                return;
            }

            if (appliedRewardData) {
                alert('A reward is already applied. Please remove it first.');
                return;
            }

            // Find the selected card
            const selectedCard = document.querySelector(`.reward-card[data-reward-id="${selectedRewardId}"]`);
            if (!selectedCard) return;

            const rewardType = selectedCard.dataset.rewardType;
            const discountValue = parseFloat(selectedCard.dataset.discountValue) || 0;
            const isPercentage = selectedCard.dataset.isPercentage === 'true';
            const percentage = parseFloat(selectedCard.dataset.percentage) || 0;
            const voucherCode = selectedCard.dataset.voucherCode || '';
            const description = selectedCard.dataset.description || 'Reward';

            // Calculate discount
            let finalDiscount = 0;
            if (isPercentage) {
                finalDiscount = (currentPaymentAmount * percentage) / 100;
            } else if (rewardType === 'free_service') {
                finalDiscount = Math.min(discountValue, currentPaymentAmount);
            } else {
                finalDiscount = Math.min(discountValue, currentPaymentAmount);
            }

            // Apply the reward
            appliedRewardData = {
                id: selectedRewardId,
                description: description,
                discount_value: finalDiscount,
                voucher_code: voucherCode,
                reward_type: rewardType
            };

            // Update UI
            selectedCard.classList.remove('selected');
            selectedCard.classList.add('applied');
            const selectIndicator = selectedCard.querySelector('.reward-select-indicator');
            if (selectIndicator) selectIndicator.style.display = 'none';
            const appliedIndicator = selectedCard.querySelector('.reward-applied-indicator');
            if (appliedIndicator) appliedIndicator.style.display = 'flex';

            // Update hidden fields
            const customerRewardId = document.getElementById('customerRewardId');
            const rewardDiscountAmount = document.getElementById('rewardDiscountAmount');
            const rewardVoucherCode = document.getElementById('rewardVoucherCode');
            if (customerRewardId) customerRewardId.value = selectedRewardId;
            if (rewardDiscountAmount) rewardDiscountAmount.value = finalDiscount;
            if (rewardVoucherCode) rewardVoucherCode.value = voucherCode;

            // Calculate new total
            const newTotal = Math.max(0, currentPaymentAmount - finalDiscount);
            
            // Update total amount display
            const totalDisplay = document.getElementById('totalAmountDisplay');
            if (totalDisplay) totalDisplay.textContent = '₱' + newTotal.toFixed(2);

            // Update payment instructions with new amount
            updatePaymentInstructionsWithAmount(newTotal);

            // Show discount display
            const rewardDiscountDisplay = document.getElementById('rewardDiscountDisplay');
            const rewardDiscountAmountDisplay = document.getElementById('rewardDiscountAmountDisplay');
            const discountNote = document.getElementById('discountNote');
            if (rewardDiscountDisplay) rewardDiscountDisplay.style.display = 'block';
            if (rewardDiscountAmountDisplay) rewardDiscountAmountDisplay.textContent = finalDiscount.toFixed(2);
            if (discountNote) discountNote.style.display = 'block';

            // Show applied reward summary
            if (appliedRewardSummary) appliedRewardSummary.style.display = 'block';
            if (appliedRewardDescription) appliedRewardDescription.textContent = description;
            if (appliedRewardDiscount) appliedRewardDiscount.textContent = 'Discount: ₱' + finalDiscount.toFixed(2);
            if (appliedRewardVoucher) appliedRewardVoucher.textContent = 'Voucher: ' + voucherCode;

            // Disable apply button, enable remove button
            applyRewardBtn.disabled = true;
            removeRewardBtn.disabled = false;

            // Update rewards status text
            const rewardsStatusText = document.getElementById('rewardsStatusText');
            if (rewardsStatusText) rewardsStatusText.textContent = '✅ Reward applied: ' + description;

            // Deselect all cards
            rewardCards.forEach(c => {
                c.classList.remove('selected');
                const ind = c.querySelector('.reward-select-indicator');
                if (ind) ind.style.display = 'none';
            });
            selectedRewardId = null;
        });

        // Remove Reward
        removeRewardBtn.addEventListener('click', function() {
            if (!appliedRewardData) return;

            // Find the applied card
            const appliedCard = document.querySelector(`.reward-card[data-reward-id="${appliedRewardData.id}"]`);
            if (appliedCard) {
                appliedCard.classList.remove('applied');
                const appliedInd = appliedCard.querySelector('.reward-applied-indicator');
                if (appliedInd) appliedInd.style.display = 'none';
            }

            // Reset total to original
            currentPaymentAmount = originalTotal;
            
            // Reset total display
            const totalDisplay = document.getElementById('totalAmountDisplay');
            if (totalDisplay) totalDisplay.textContent = '₱' + originalTotal.toFixed(2);

            // Update payment instructions with original amount
            updatePaymentInstructionsWithAmount(originalTotal);

            // Reset hidden fields
            const customerRewardId = document.getElementById('customerRewardId');
            const rewardDiscountAmount = document.getElementById('rewardDiscountAmount');
            const rewardVoucherCode = document.getElementById('rewardVoucherCode');
            if (customerRewardId) customerRewardId.value = '';
            if (rewardDiscountAmount) rewardDiscountAmount.value = '0';
            if (rewardVoucherCode) rewardVoucherCode.value = '';

            // Hide discount display
            const rewardDiscountDisplay = document.getElementById('rewardDiscountDisplay');
            const discountNote = document.getElementById('discountNote');
            if (rewardDiscountDisplay) rewardDiscountDisplay.style.display = 'none';
            if (discountNote) discountNote.style.display = 'none';

            // Hide applied reward summary
            if (appliedRewardSummary) appliedRewardSummary.style.display = 'none';

            // Reset buttons
            applyRewardBtn.disabled = false;
            removeRewardBtn.disabled = true;

            // Reset rewards status
            const rewardsStatusText = document.getElementById('rewardsStatusText');
            if (rewardsStatusText) {
                rewardsStatusText.textContent = 'You have ' + rewardCards.length + ' reward(s) available!';
            }

            appliedRewardData = null;
        });
    }

    // ================================================================
    // FUNCTION TO UPDATE PAYMENT INSTRUCTIONS WITH AMOUNT
    // ================================================================
    function updatePaymentInstructionsWithAmount(amount) {
        const paymentInstructions = document.getElementById('paymentInstructions');
        if (!paymentInstructions) return;

        // Get the current payment type
        const selectedPaymentOption = document.querySelector('.payment-option.selected');
        let paymentType = 'full';
        if (selectedPaymentOption) {
            paymentType = selectedPaymentOption.getAttribute('data-payment-type') || 'full';
        }

        let html = '';
        
        @if (isset($ownerGcashQrCode) && count($ownerGcashQrCode) > 0)
            html = `
            <div class="mt-4">
                <p class="text-sm text-[#4a3429] mb-4">
                    Please scan any of the following QR codes to pay via GCash:
                </p>
                
                <div class="relative">
                    <div id="qrCarousel" class="overflow-hidden">
                        <div class="flex transition-transform duration-300" id="qrCarouselTrack">
                            @foreach ($ownerGcashQrCode as $index => $qrCodePath)
                                @php
                                    $qrNumber = $index + 1;
                                    $fullPath = str_starts_with($qrCodePath, 'storage/') ? 
                                                'app/public/' . substr($qrCodePath, 8) : 
                                                'app/public/' . $qrCodePath;
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
                    
                    @if (count($ownerGcashQrCode) > 1)
                        <button id="prevBtn" class="absolute left-0 top-1/2 transform -translate-y-1/2 bg-[#7f5539] text-white p-2 rounded-full hover:bg-[#6b4f3c] transition">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button id="nextBtn" class="absolute right-0 top-1/2 transform -translate-y-1/2 bg-[#7f5539] text-white p-2 rounded-full hover:bg-[#6b4f3c] transition">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        
                        <div class="flex justify-center mt-4 space-x-2">
                            @foreach ($ownerGcashQrCode as $index => $qrCodePath)
                                <button class="w-2 h-2 rounded-full bg-gray-300 carousel-dot {{ $index === 0 ? 'bg-[#7f5539]' : '' }}" 
                                        data-index="{{ $index }}"></button>
                            @endforeach
                        </div>
                    @endif
                </div>
                
                <div class="mt-6">
                    <ol class="text-[#4a3429] text-sm space-y-1 list-decimal list-inside">
                        <li>Open your GCash app</li>
                        <li>Go to "Send Money" or "Scan QR"</li>
                        <li>Scan any of the QR codes above</li>
                        <li>Amount: <strong>₱${amount.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong></li>
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
                    Amount to pay: ₱${amount.toLocaleString('en-US', {minimumFractionDigits: 2})}
                </p>
            </div>`;
        @endif

        paymentInstructions.innerHTML = html;
        
        @if (isset($ownerGcashQrCode) && count($ownerGcashQrCode) > 0)
            initializeQRCarousel();
        @endif
    }

    // ================================================================
    // FILE UPLOAD FUNCTIONALITY
    // ================================================================
    initializeImageUpload('paymentUploadArea', 'paymentFileInput', 'paymentDefaultState', 
                         'paymentImagePreviewState', 'paymentImagePreview', 'paymentFileError');

    // ================================================================
    // IMAGE PREVIEW MODAL
    // ================================================================
    const imagePreviewModal = document.getElementById('imagePreviewModal');
    const modalImage = document.getElementById('modalImage');
    const zoomInBtn = document.getElementById('zoomInBtn');
    const zoomOutBtn = document.getElementById('zoomOutBtn');
    const resetZoomBtn = document.getElementById('resetZoomBtn');
    const closeImageModalBtn = document.getElementById('closeImageModalBtn');

    let currentScale = 1;
    const scaleStep = 0.2;

    document.addEventListener('click', function(e) {
        const imagePreview = e.target.closest('.image-preview-overlay');
        const imageElement = e.target.closest('.image-preview');

        if (imagePreview || (imageElement && !e.target.closest('.choose-file-btn'))) {
            const imgSrc = imageElement ? imageElement.src : imagePreview.previousElementSibling.src;
            if (modalImage) {
                modalImage.src = imgSrc;
                currentScale = 1;
                modalImage.style.transform = `scale(${currentScale})`;
                if (imagePreviewModal) {
                    imagePreviewModal.style.display = 'block';
                    document.body.style.overflow = 'hidden';
                }
            }
            e.preventDefault();
            e.stopPropagation();
        }
    });

    if (zoomInBtn) {
        zoomInBtn.addEventListener('click', function() {
            currentScale += scaleStep;
            if (modalImage) modalImage.style.transform = `scale(${currentScale})`;
        });
    }

    if (zoomOutBtn) {
        zoomOutBtn.addEventListener('click', function() {
            if (currentScale > scaleStep) {
                currentScale -= scaleStep;
                if (modalImage) modalImage.style.transform = `scale(${currentScale})`;
            }
        });
    }

    if (resetZoomBtn) {
        resetZoomBtn.addEventListener('click', function() {
            currentScale = 1;
            if (modalImage) modalImage.style.transform = `scale(${currentScale})`;
        });
    }

    if (closeImageModalBtn) {
        closeImageModalBtn.addEventListener('click', function() {
            if (imagePreviewModal) {
                imagePreviewModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    }

    if (imagePreviewModal) {
        imagePreviewModal.addEventListener('click', function(e) {
            if (e.target === imagePreviewModal) {
                imagePreviewModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    }

    // ================================================================
    // TERMS & CONDITIONS MODAL
    // ================================================================
    const termsModal = document.getElementById('termsModal');
    const proceedToPaymentBtn = document.getElementById('proceedToPaymentBtn');
    const cancelTermsBtn = document.getElementById('cancelTermsBtn');
    const confirmTermsBtn = document.getElementById('confirmTermsBtn');
    const agreeTermsCheckbox = document.getElementById('agreeTerms');
    const proceedToPaymentForm = document.getElementById('proceedToPaymentForm');

    if (proceedToPaymentBtn) {
        proceedToPaymentBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (termsModal) {
                termsModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        });
    }

    if (cancelTermsBtn) {
        cancelTermsBtn.addEventListener('click', function() {
            if (termsModal) {
                termsModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    }

    if (agreeTermsCheckbox) {
        agreeTermsCheckbox.addEventListener('change', function() {
            if (confirmTermsBtn) {
                confirmTermsBtn.disabled = !this.checked;
            }
        });
    }

    if (confirmTermsBtn) {
        confirmTermsBtn.addEventListener('click', function() {
            if (!agreeTermsCheckbox || !agreeTermsCheckbox.checked) {
                alert('Please agree to the Terms & Conditions to proceed.');
                return;
            }

            if (proceedToPaymentForm) {
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
                if (termsModal) {
                    termsModal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
                setTimeout(() => {
                    proceedToPaymentForm.submit();
                }, 500);
            }
        });
    }

    if (termsModal) {
        termsModal.addEventListener('click', function(e) {
            if (e.target === termsModal) {
                termsModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    }

    // ================================================================
    // PAYMENT OPTIONS
    // ================================================================
    const paymentOptions = document.querySelectorAll('.payment-option');
    const paymentFormSection = document.getElementById('paymentFormSection');
    const paymentFormTitle = document.getElementById('paymentFormTitle');
    const paymentAmountText = document.getElementById('paymentAmountText');
    const paymentTypeInput = document.getElementById('paymentTypeInput');
    const submitPaymentBtn = document.getElementById('submitPaymentBtn');
    const submitButtonText = document.getElementById('submitButtonText');
    const paymentInstructions = document.getElementById('paymentInstructions');

    const totalPrice = parseFloat(@json($bookingDetails['total_price'] ?? 0));
    const hasMultipleOptions = paymentOptions.length > 0;

    if (hasMultipleOptions && paymentFormSection && submitPaymentBtn) {
        submitPaymentBtn.disabled = true;
        submitPaymentBtn.classList.add('opacity-50', 'cursor-not-allowed');
        paymentFormSection.classList.add('opacity-50');
    }

    paymentOptions.forEach(option => {
        option.addEventListener('click', function() {
            paymentOptions.forEach(opt => {
                opt.classList.remove('selected');
            });

            this.classList.add('selected');

            const paymentType = this.getAttribute('data-payment-type');
            const paymentAmount = parseFloat(this.getAttribute('data-payment-amount'));
            const paymentDisplay = this.getAttribute('data-payment-display');

            // Store the selected payment type
            selectedPaymentType = paymentType;
            
            // Update current payment amount (base amount without discount)
            // If reward is applied, we need to subtract discount from this amount
            let finalAmount = paymentAmount;
            if (appliedRewardData) {
                finalAmount = Math.max(0, paymentAmount - appliedRewardData.discount_value);
            }
            currentPaymentAmount = finalAmount;

            if (paymentFormSection && paymentFormTitle && paymentAmountText && paymentTypeInput && submitPaymentBtn) {
                paymentFormSection.classList.remove('opacity-50', 'hidden');
                submitPaymentBtn.disabled = false;
                submitPaymentBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                
                paymentFormTitle.textContent = 'Complete Your Payment';
                paymentAmountText.textContent = `Pay: ₱${finalAmount.toFixed(2)}`;
                paymentTypeInput.value = paymentType;
                if (submitButtonText) submitButtonText.textContent = 'Complete Payment';

                paymentFormSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });

                // Update payment instructions with the correct amount
                updatePaymentInstructionsWithAmount(finalAmount);
            }
        });
    });

    // Initialize payment instructions with default amount
    function initializePaymentInstructions() {
        const defaultAmount = totalPrice;
        currentPaymentAmount = defaultAmount;
        updatePaymentInstructionsWithAmount(defaultAmount);
    }

    // ================================================================
    // QR CAROUSEL FUNCTION
    // ================================================================
    function initializeQRCarousel() {
        const carouselTrack = document.getElementById('qrCarouselTrack');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const dots = document.querySelectorAll('.carousel-dot');

        if (carouselTrack && prevBtn && nextBtn) {
            let currentIndex = 0;
            const totalSlides = {{ count($ownerGcashQrCode ?? []) }};
            
            function updateCarousel() {
                carouselTrack.style.transform = `translateX(-${currentIndex * 100}%)`;
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
            
            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => {
                    currentIndex = index;
                    updateCarousel();
                });
            });
        }
    }

    // Initialize payment instructions when page loads
    initializePaymentInstructions();

    // ================================================================
    // DATA LOSS WARNING MODAL
    // ================================================================
    const dataLossModal = document.getElementById('dataLossModal');
    const backToEditBtn = document.getElementById('backToEditBtn');
    const modalCancel = document.getElementById('modalCancel');
    const modalConfirmBack = document.getElementById('modalConfirmBack');

    const backUrl = "{{ route('sub_three.home.booking.form', [
        $bookingDetails['branch']->uuid ?? '',
        $bookingDetails['service_category']->uuid ?? '',
        $bookingDetails['service_name']->uuid ?? '',
    ]) }}";

    if (backToEditBtn) {
        backToEditBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (modalConfirmBack) modalConfirmBack.href = backUrl;
            if (dataLossModal) {
                dataLossModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        });
    }

    if (modalCancel) {
        modalCancel.addEventListener('click', function() {
            if (dataLossModal) {
                dataLossModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
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

    // ================================================================
    // CONFLICT MODAL
    // ================================================================
    @if (session('show_conflict_modal'))
        const conflictModal = document.getElementById('conflictPaymentModal');
        if (conflictModal) {
            conflictModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    @endif

    // ================================================================
    // PAYMENT FORM VALIDATION
    // ================================================================
    const paymentForm = document.getElementById('paymentForm');
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const fileInput = this.querySelector('input[type="file"]');
            const refNoInput = this.querySelector('input[name="gcash_ref_no"]');
            const bookingDetailsInput = this.querySelector('input[name="booking_details"]');

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

            checkAvailabilityBeforeSubmit(this);
        });
    }
});

// ================================================================
// FILE UPLOAD FUNCTION
// ================================================================
function initializeImageUpload(uploadAreaId, fileInputId, defaultStateId, imagePreviewStateId, imagePreviewId, fileErrorId) {
    const uploadArea = document.getElementById(uploadAreaId);
    const fileInput = document.getElementById(fileInputId);
    const defaultState = document.getElementById(defaultStateId);
    const imagePreviewState = document.getElementById(imagePreviewStateId);
    const imagePreview = document.getElementById(imagePreviewId);
    const fileError = document.getElementById(fileErrorId);

    if (!uploadArea || !fileInput) return;

    const chooseFileBtns = uploadArea.querySelectorAll('.choose-file-btn');
    chooseFileBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            fileInput.click();
        });
    });

    const removeImageBtn = uploadArea.querySelector('#paymentRemoveImageBtn');
    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            resetFileInput();
        });
    }

    fileInput.addEventListener('change', function(e) {
        handleFileSelection(e.target.files[0]);
    });

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

    function handleFileSelection(file) {
        if (file) {
            const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
            if (!validTypes.includes(file.type.toLowerCase())) {
                showError('Please select a valid image file (JPEG, PNG, GIF)');
                return;
            }

            const maxSize = 2 * 1024 * 1024;
            if (file.size > maxSize) {
                showError('File size must be less than 2MB');
                return;
            }

            hideError();

            const objectUrl = URL.createObjectURL(file);

            if (imagePreview) {
                imagePreview.src = objectUrl;
            }
            if (defaultState) defaultState.classList.add('hidden');
            if (imagePreviewState) imagePreviewState.classList.remove('hidden');
            uploadArea.classList.add('has-file');

            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            fileInput.files = dataTransfer.files;

            fileInput._objectUrl = objectUrl;
        }
    }

    function resetFileInput() {
        if (fileInput._objectUrl) {
            URL.revokeObjectURL(fileInput._objectUrl);
            delete fileInput._objectUrl;
        }

        fileInput.value = '';
        if (defaultState) defaultState.classList.remove('hidden');
        if (imagePreviewState) imagePreviewState.classList.add('hidden');
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

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
}

// ================================================================
// AVAILABILITY CHECK
// ================================================================
function checkAvailabilityBeforeSubmit(form) {
    const bookingDetailsInput = form.querySelector('input[name="booking_details"]');
    if (!bookingDetailsInput || !bookingDetailsInput.value) {
        return true;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Checking availability...';

    fetch('/api/check-booking-availability', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            booking_details: bookingDetailsInput.value
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.available) {
            form.submit();
        } else {
            alert(data.message || 'This booking slot is no longer available. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error checking availability:', error);
        form.submit();
    });

    return false;
}
    </script>
</body>

</html>