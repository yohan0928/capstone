@extends('layouts.app')

@section('title', 'Scan QR Code')

@section('content')
    <style>
        :root {
            --accent: #4A2C1D;
        }

        .scanner-frame {
            position: relative;
            width: 16rem;
            height: 16rem;
            border-radius: 1rem;
            overflow: hidden;
        }

        .corner-line {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 4px solid transparent;
            border-radius: 1rem;
            box-shadow: 0 0 20px rgba(33, 150, 243, 0.8);
        }

        .corner-line.tl {
            border-top-color: #2196f3;
            border-left-color: #2196f3;
        }

        .corner-line.tr {
            border-top-color: #2196f3;
            border-right-color: #2196f3;
        }

        .corner-line.bl {
            border-bottom-color: #2196f3;
            border-left-color: #2196f3;
        }

        .corner-line.br {
            border-bottom-color: #2196f3;
            border-right-color: #2196f3;
        }

        .scanner-line {
            position: absolute;
            left: 0;
            right: 0;
            height: 3px;
            background: #2196f3;
            box-shadow: 0 0 20px rgba(33, 150, 243, 1);
            border-radius: 5px;
            animation: scan 2s infinite linear;
        }

        @keyframes scan {
            0% {
                top: 10%;
            }
            50% {
                top: 90%;
            }
            100% {
                top: 10%;
            }
        }

        .camera-backdrop::before {
            content: "";
            position: absolute;
            inset: 0;
            box-shadow: inset 0 0 0 4000px rgba(0, 0, 0, 0.28);
            pointer-events: none;
            border-radius: inherit;
        }

        #qr-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 1rem;
            z-index: 0;
        }

        .corner-line,
        .scanner-line {
            z-index: 1;
            pointer-events: none;
        }

        .file-upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 0.75rem;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background-color: #f9fafb;
        }

        .file-upload-area:hover {
            border-color: var(--accent);
            background-color: #f3f4f6;
        }

        .file-upload-area.dragover {
            border-color: var(--accent);
            background-color: #e5e7eb;
        }

        .file-preview {
            max-width: 200px;
            max-height: 200px;
            margin: 0 auto;
            display: none;
        }

        .uploaded-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 0.5rem;
            object-fit: contain;
        }

        .booking-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .booking-type-online {
            background-color: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        .booking-type-walkin {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #86efac;
        }

        .payment-section {
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .payment-section.main-payment {
            background-color: #f0f9ff;
            border: 1px solid #bae6fd;
        }

        .payment-section.extended-payment {
            background-color: #faf5ff;
            border: 1px solid #e9d5ff;
        }

        .payment-section.combined-total {
            background-color: #f8fafc;
            border: 2px solid #cbd5e1;
        }

        .schedule-section {
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .schedule-section.main-schedule {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
        }

        .schedule-section.extended-schedule {
            background-color: #fff7ed;
            border: 1px solid #fed7aa;
        }

        .extension-badge {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: white;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin-left: 0.5rem;
        }

        .payment-category-badge {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .payment-method-badge,
        .payment-status-badge {
            font-size: 0.75rem;
            font-weight: 600;
            min-width: 70px;
            text-align: center;
        }

        .badge-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .amount-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.25rem 0;
        }

        .amount-row.total {
            font-weight: 600;
            border-top: 1px solid #e5e7eb;
            padding-top: 0.5rem;
            margin-top: 0.5rem;
        }

        .action-button-container {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        @media (min-width: 640px) {
            .action-button-container {
                flex-direction: row;
                justify-content: center;
                flex-wrap: wrap;
            }
        }

        .btn-order {
            background: linear-gradient(135deg, #059669, #047857);
            color: white;
            transition: all 0.2s ease;
        }

        .btn-order:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.4);
        }

        .btn-order:active {
            transform: translateY(0);
        }

        .btn-order-disabled {
            background: #d1d5db;
            color: #6b7280;
            cursor: not-allowed;
        }

        .btn-order-disabled:hover {
            transform: none;
            box-shadow: none;
        }

        .qr-valid {
            border-left: 4px solid #059669;
        }

        .qr-invalid {
            border-left: 4px solid #dc2626;
        }

        .validity-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .validity-status-badge.valid {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #86efac;
        }

        .validity-status-badge.invalid {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .expired-notice {
            background-color: #fef2f2;
            border: 1px solid #fca5a5;
            border-radius: 0.5rem;
            padding: 0.75rem;
            margin-top: 0.5rem;
        }

        .expired-notice .text {
            color: #991b1b;
            font-size: 0.875rem;
        }

        .expired-notice .sub-text {
            color: #7f1d1d;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }

        .unpaid-orders-badge {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #f59e0b;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .order-eligible-badge {
            background-color: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin-left: 0.5rem;
        }
    </style>

    <div class="flex items-center justify-center py-8 px-4">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="px-6 py-6">
                    <h1 class="text-lg font-bold text-[color:var(--accent)] text-center">Scan Booking QR Code</h1>
                    <p class="text-xs text-gray-500 text-center mt-1">Use your camera to scan QR codes in real-time</p>

                    <div class="mt-5">
                        <div id="camera-permission"
                            class="camera-permission bg-amber-50 border border-amber-200 rounded-lg p-4 text-center">
                            <div class="flex items-center justify-center gap-3 mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-amber-500" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <div class="text-left">
                                    <div class="text-sm font-semibold text-amber-800">Camera Access Required</div>
                                    <div class="text-xs text-amber-700">Please allow camera permissions to scan QR codes.
                                    </div>
                                </div>
                            </div>

                            <button id="start-camera"
                                class="mt-2 inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-[color:var(--accent)] text-white font-semibold hover:opacity-95">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path
                                        d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v8a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z" />
                                </svg>
                                Start Camera
                            </button>
                        </div>

                        <div id="scanner-container"
                            class="scanner-container hidden mt-4 rounded-2xl bg-black relative overflow-hidden camera-backdrop"
                            style="height:360px;">
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <div class="scanner-frame">
                                    <video id="qr-video" playsinline></video>
                                    <div class="corner-line tl"></div>
                                    <div class="corner-line tr"></div>
                                    <div class="corner-line bl"></div>
                                    <div class="corner-line br"></div>
                                    <div class="scanner-line"></div>
                                </div>
                                <div class="mt-4 text-white text-sm font-medium pointer-events-none">
                                    Position QR code within the frame
                                </div>
                            </div>
                        </div>

                        <div id="camera-controls"
                            class="camera-controls hidden mt-4 flex items-center justify-center gap-3">
                            <button id="switch-camera"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/90 text-gray-800 shadow-sm hover:opacity-95">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M4 5a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V7a2 2 0 00-2-2h-1.586a1 1 0 01-.707-.293l-1.121-1.121A2 2 0 0011.172 3H8.828a2 2 0 00-1.414.586L6.293 4.707A1 1 0 015.586 5H4zm6 9a3 3 0 100-6 3 3 0 000 6z"
                                        clip-rule="evenodd" />
                                </svg>
                                Switch
                            </button>

                            <button id="stop-camera"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/90 text-gray-800 shadow-sm hover:opacity-95">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z"
                                        clip-rule="evenodd" />
                                </svg>
                                Stop
                            </button>
                        </div>

                        <div class="mt-4 text-center">
                            <div id="scan-result" class="scan-result text-sm font-semibold"></div>
                            <div id="processing-spinner" class="processing-spinner hidden mx-auto mt-3"
                                style="border:4px solid #f3f3f3; border-top:4px solid var(--accent); border-radius:50%; width:36px; height:36px; animation: spin 1s linear infinite;">
                            </div>
                            <div id="success-message"
                                class="success-message hidden mt-3 rounded-lg p-3 bg-green-50 border border-green-100 text-sm text-green-700 inline-flex items-center gap-2 justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                QR Code successfully detected and processed!
                            </div>
                        </div>
                    </div>

                    <div class="file-upload-section bg-purple-50 border border-purple-100 rounded-lg p-4 mt-5">
                        <h3 class="text-sm font-semibold text-[color:var(--accent)]">Upload QR Code Image</h3>
                        <p class="text-xs text-gray-600 mt-1">Upload an image containing a QR code to scan</p>

                        <div class="mt-3">
                            <div id="file-upload-area" class="file-upload-area">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400 mx-auto mb-2"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                <p class="text-sm text-gray-600 mb-1">Click to upload or drag and drop</p>
                                <p class="text-xs text-gray-500">PNG, JPG, JPEG up to 5MB</p>
                                <input type="file" id="file-input" accept="image/*" class="hidden" />
                            </div>

                            <div id="file-preview" class="file-preview mt-3">
                                <img id="uploaded-image" class="uploaded-image" src="" alt="Uploaded QR Code" />
                                <div class="mt-2 flex justify-center gap-2">
                                    <button id="scan-uploaded-image"
                                        class="px-3 py-1 bg-[color:var(--accent)] text-white text-sm rounded-lg hover:opacity-95">Scan
                                        Image</button>
                                    <button id="remove-uploaded-image"
                                        class="px-3 py-1 bg-gray-300 text-gray-800 text-sm rounded-lg hover:opacity-95">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="manual-fallback bg-blue-50 border border-blue-100 rounded-lg p-4 mt-5">
                        <h3 class="text-sm font-semibold text-[color:var(--accent)]">Quick Manual Entry</h3>
                        <p class="text-xs text-gray-600 mt-1">Having trouble with the camera? Enter booking reference
                            directly:</p>

                        <div class="mt-3 flex flex-col sm:flex-row gap-2">
                            <input type="text" id="manual-ref-input" placeholder="Enter booking reference number"
                                class="flex-1 px-4 py-2 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[color:var(--accent)] text-sm" />
                            <button onclick="fetchBookingManual()"
                                class="sm:w-auto w-full px-4 py-2 rounded-lg bg-[color:var(--accent)] text-white font-semibold hover:opacity-95">Search</button>
                        </div>
                    </div>

                    <div id="booking-info" class="hidden mt-5"></div>

                    <div class="mt-6 flex flex-col sm:flex-row gap-3 items-center justify-center">
                        <button onclick="clearAll()"
                            class="w-full sm:w-auto px-4 py-2 rounded-lg bg-gray-300 text-gray-800 font-semibold hover:opacity-95">Clear
                            All</button>
                        <a href="{{ route('sub_two.customer_checkins.index') }}"
                            class="w-full sm:w-auto px-4 py-2 rounded-lg bg-white border border-gray-200 text-gray-800 font-semibold hover:bg-gray-50 text-center">Back
                            to Check-ins</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>

    <script>
        const paymentCategoryMap = {
            0: {
                text: 'Extension',
                class: 'bg-orange-100 text-orange-800 border border-orange-200'
            },
            1: {
                text: 'Main',
                class: 'bg-blue-100 text-blue-800 border border-blue-200'
            }
        };

        const paymentStatusMap = {
            0: {
                text: 'Invalid',
                class: 'bg-gray-100 text-gray-800 border border-gray-200'
            },
            1: {
                text: 'Paid',
                class: 'bg-green-100 text-green-800 border border-green-200'
            },
            2: {
                text: 'Unpaid',
                class: 'bg-red-100 text-red-800 border border-red-200'
            }
        };

        const paymentMethodMap = {
            0: {
                text: 'Cash',
                class: 'bg-green-100 text-green-800 border border-green-200'
            },
            1: {
                text: 'GCash',
                class: 'bg-blue-100 text-blue-800 border border-blue-200'
            },
            2: {
                text: 'Debit Card',
                class: 'bg-purple-100 text-purple-800 border border-purple-200'
            },
            3: {
                text: 'Pay Later',
                class: 'bg-red-100 text-red-800 border border-red-200'
            }
        };

        const bookingTemplate = `
        <div class="bg-white border rounded-xl p-6 mb-6 {qrValidityClass}" id="booking-card">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center flex-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h2 class="text-2xl font-bold text-[color:var(--accent)] booking-header">Booking Found!</h2>
                    {extensionBadge}
                    {unpaidOrdersBadge}
                    {orderEligibleBadge}
                </div>
                <div class="booking-type-badge {bookingTypeClass}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        {bookingTypeIcon}
                    </svg>
                    {bookingTypeText}
                </div>
            </div>
            
            <!-- QR Code Validity Status -->
            <div class="mb-4">
                {qrValidityStatus}
            </div>
            
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <p class="text-lg font-semibold text-gray-800">
                    Booking Ref #: <span class="font-bold text-black tracking-wider booking-ref">{bookingRef}</span>
                </p>
                {expiredNotice}
            </div>

            <div class="text-left space-y-6 border-t border-gray-100 pt-6">
                <div>
                    <h4 class="text-lg font-bold text-[color:var(--accent)] mb-2">Customer</h4>
                    <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                        <p class="text-gray-700">{customerName}</p>
                        <p class="text-gray-600 text-sm">{customerEmail}</p>
                        <p class="text-gray-600 text-sm">{customerContact}</p>
                    </div>
                </div>

                <div>
                    <h4 class="text-lg font-bold text-[color:var(--accent)] mb-2">Service</h4>
                    <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                        <p class="text-gray-700">{branchName}</p>
                        <p class="text-gray-700">{serviceCategory} - {serviceName}</p>
                        {seatNo}
                        {roomNo}
                    </div>
                </div>

                <!-- Main Schedule Section -->
                <div>
                    <h4 class="text-lg font-bold text-[color:var(--accent)] mb-2">Main Schedule</h4>
                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 space-y-3">
                        {mainScheduleStart}
                        {mainScheduleEnd}
                    </div>
                </div>

                <!-- Extended Schedule Section (only if exists) -->
                {extendedScheduleSection}

                <!-- Main Payment Section -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-lg font-bold text-[color:var(--accent)]">Main Payment</h4>
                        <span class="payment-category-badge px-2 py-1 rounded-full text-xs font-medium {mainPaymentCategoryClass}">
                            {mainPaymentCategoryText}
                        </span>
                    </div>
                    <div class="bg-green-50 border border-green-100 rounded-xl p-4 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Method:</span>
                                <span class="payment-method inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium {mainPaymentMethodClass}">
                                    {mainPaymentMethod}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Status:</span>
                                <span class="payment-status-badge px-2 py-1 rounded-full text-xs font-medium {mainStatusClass}">
                                    {mainPaymentStatusText}
                                </span>
                            </div>
                        </div>
                        
                        <div class="space-y-2 pt-2 border-t border-green-100">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Amount:</span>
                                <span class="font-semibold">{mainTotalAmount}</span>
                            </div>
                            {mainAmountPaidLine}
                            {mainChangeLine}
                        </div>
                        
                        {mainGcashRefNo}
                        {mainPaymentDate}
                    </div>
                </div>

                <!-- Extended Payment Section (only if exists) -->
                {extendedPaymentSection}

                <!-- Combined Total Section (if both payments exist) -->
                {combinedTotalSection}
            </div>

            <div id="booking-action-container" class="mt-6">
                <div class="action-button-container">
                    {actionButton}
                    {orderButton}
                </div>
            </div>
        </div>
        `;

        const errorTemplate = `
        <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-red-500 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="text-xl font-bold text-red-700 mb-2">{errorTitle}</h3>
            <p class="text-red-600">{errorMessage}</p>
        </div>
        `;

        let videoStream = null;
        let currentFacingMode = 'environment';
        let scanInterval = null;
        let isScanning = false;

        const videoElement = document.getElementById('qr-video');
        const scannerContainer = document.getElementById('scanner-container');
        const cameraControls = document.getElementById('camera-controls');
        const cameraPermission = document.getElementById('camera-permission');
        const startCameraBtn = document.getElementById('start-camera');
        const switchCameraBtn = document.getElementById('switch-camera');
        const stopCameraBtn = document.getElementById('stop-camera');
        const scanResult = document.getElementById('scan-result');
        const processingSpinner = document.getElementById('processing-spinner');
        const successMessage = document.getElementById('success-message');
        const fileUploadArea = document.getElementById('file-upload-area');
        const fileInput = document.getElementById('file-input');
        const filePreview = document.getElementById('file-preview');
        const uploadedImage = document.getElementById('uploaded-image');
        const scanUploadedImageBtn = document.getElementById('scan-uploaded-image');
        const removeUploadedImageBtn = document.getElementById('remove-uploaded-image');

        startCameraBtn.addEventListener('click', startCamera);
        switchCameraBtn.addEventListener('click', switchCamera);
        stopCameraBtn.addEventListener('click', stopCamera);
        fileUploadArea.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', handleFileSelect);
        scanUploadedImageBtn.addEventListener('click', scanUploadedImage);
        removeUploadedImageBtn.addEventListener('click', removeUploadedImage);

        fileUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUploadArea.classList.add('dragover');
        });

        fileUploadArea.addEventListener('dragleave', () => {
            fileUploadArea.classList.remove('dragover');
        });

        fileUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFileSelect();
            }
        });

        function handleFileSelect() {
            const file = fileInput.files[0];
            if (!file) return;

            if (!file.type.match('image.*')) {
                alert('Please select an image file (PNG, JPG, JPEG)');
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                uploadedImage.src = e.target.result;
                filePreview.style.display = 'block';
                fileUploadArea.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }

        function parseQRCodeData(qrData) {
            console.log('Raw QR Data:', qrData);
            try {
                const qrContent = JSON.parse(qrData);
                console.log('Parsed QR Content:', qrContent);
                if (qrContent.booking_ref) {
                    return qrContent.booking_ref;
                } else {
                    throw new Error('Booking reference not found in QR code');
                }
            } catch (error) {
                console.log('JSON parsing failed, trying direct extraction:', error);
                const bookingRefMatch = qrData.match(/BRF[A-Z0-9]+/);
                if (bookingRefMatch) {
                    return bookingRefMatch[0];
                } else {
                    return qrData.trim();
                }
            }
        }

        function scanUploadedImage() {
            if (!uploadedImage.src) return;

            processingSpinner.classList.remove('hidden');
            scanResult.innerHTML = `<div class="scan-success" style="color:#059669">Scanning uploaded image...</div>`;

            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            const img = new Image();

            img.onload = function() {
                canvas.width = img.width;
                canvas.height = img.height;
                context.drawImage(img, 0, 0, canvas.width, canvas.height);
                const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height);

                if (code) {
                    const bookingRef = parseQRCodeData(code.data);
                    if (bookingRef) {
                        handleQRCodeDetected(bookingRef);
                    } else {
                        processingSpinner.classList.add('hidden');
                        scanResult.innerHTML =
                            `<div class="scan-error" style="color:#dc2626">Invalid QR code format</div>`;
                        setTimeout(() => {
                            scanResult.innerHTML = '';
                        }, 3000);
                    }
                } else {
                    processingSpinner.classList.add('hidden');
                    scanResult.innerHTML =
                        `<div class="scan-error" style="color:#dc2626">No QR code found in the uploaded image</div>`;
                    setTimeout(() => {
                        scanResult.innerHTML = '';
                    }, 3000);
                }
            };

            img.onerror = function() {
                processingSpinner.classList.add('hidden');
                scanResult.innerHTML = `<div class="scan-error" style="color:#dc2626">Error loading image</div>`;
            };

            img.src = uploadedImage.src;
        }

        function removeUploadedImage() {
            fileInput.value = '';
            uploadedImage.src = '';
            filePreview.style.display = 'none';
            fileUploadArea.style.display = 'block';
            scanResult.innerHTML = '';
        }

        async function startCamera() {
            try {
                videoStream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: currentFacingMode,
                        width: {
                            ideal: 1280
                        },
                        height: {
                            ideal: 720
                        }
                    }
                });

                videoElement.srcObject = videoStream;
                scannerContainer.classList.remove('hidden');
                cameraControls.classList.remove('hidden');
                cameraPermission.classList.add('hidden');

                videoElement.addEventListener('loadedmetadata', function() {
                    videoElement.play();
                    startScanning();
                });

            } catch (error) {
                console.error('Error accessing camera:', error);
                scanResult.innerHTML =
                    `<div class="scan-error" style="color:#dc2626">Camera access denied: ${error.message}</div>`;
            }
        }

        async function switchCamera() {
            if (videoStream) {
                videoStream.getTracks().forEach(track => track.stop());
                currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment';
                await startCamera();
            }
        }

        function stopCamera() {
            if (videoStream) {
                videoStream.getTracks().forEach(track => track.stop());
                videoStream = null;
            }

            if (scanInterval) {
                clearInterval(scanInterval);
                scanInterval = null;
            }

            isScanning = false;
            videoElement.srcObject = null;
            scannerContainer.classList.add('hidden');
            cameraControls.classList.add('hidden');
            cameraPermission.classList.remove('hidden');
            scanResult.innerHTML = '';
        }

        function startScanning() {
            if (isScanning) return;
            isScanning = true;
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');

            scanInterval = setInterval(() => {
                if (videoElement.readyState === videoElement.HAVE_ENOUGH_DATA) {
                    canvas.width = videoElement.videoWidth;
                    canvas.height = videoElement.videoHeight;
                    context.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
                    const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                    const code = jsQR(imageData.data, imageData.width, imageData.height);

                    if (code) {
                        const bookingRef = parseQRCodeData(code.data);
                        if (bookingRef) {
                            handleQRCodeDetected(bookingRef);
                        }
                    }
                }
            }, 500);
        }

        function handleQRCodeDetected(bookingRef) {
            if (scanInterval) {
                clearInterval(scanInterval);
                scanInterval = null;
            }
            isScanning = false;
            scanResult.innerHTML = `<div class="scan-success" style="color: #059669">QR Code detected! Processing...</div>`;
            processingSpinner.classList.remove('hidden');
            successMessage.classList.remove('hidden');
            fetchBookingInfo(bookingRef);
        }

        function autoScrollToBookingInfo() {
            const bookingInfo = document.getElementById('booking-info');
            if (bookingInfo) {
                setTimeout(() => {
                    bookingInfo.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }, 300);
            }
        }

        async function fetchBookingInfo(bookingRef) {
            const bookingInfo = document.getElementById('booking-info');
            try {
                const cleanBookingRef = bookingRef.replace(/["']/g, '').trim();
                console.log('Fetching booking info for:', cleanBookingRef);

                const response = await fetch(
                    '{{ route('sub_two.scan_qr_code_bookings.getBookingByBookingRefNo') }}?booking_ref=' +
                    encodeURIComponent(cleanBookingRef));

                if (!response.ok) {
                    let errorMessage = 'Server error occurred';
                    if (response.status === 404) errorMessage = 'Booking not found';
                    else if (response.status === 500) errorMessage = 'Server error - please try again';
                    else if (response.status === 400) errorMessage = 'Booking reference is required';
                    else if (response.status === 403) errorMessage = 'Access denied - you do not have permission to view this booking';
                    throw new Error(errorMessage);
                }

                const data = await response.json();
                if (data.success) {
                    scanResult.innerHTML =
                        `<div class="scan-success" style="color:#059669">Booking loaded successfully!</div>`;
                    processingSpinner.classList.add('hidden');
                    successMessage.classList.remove('hidden');
                    displayBookingInfo(data.booking);
                    autoScrollToBookingInfo();
                } else {
                    throw new Error(data.message || 'Booking not found');
                }
            } catch (error) {
                console.error('Error fetching booking:', error);
                scanResult.innerHTML = `<div class="scan-error" style="color:#dc2626">Error: ${error.message}</div>`;
                processingSpinner.classList.add('hidden');
                successMessage.classList.add('hidden');
                bookingInfo.innerHTML = errorTemplate
                    .replace('{errorTitle}', 'Booking Not Found')
                    .replace('{errorMessage}', error.message || 'The booking reference could not be found.');
                bookingInfo.classList.remove('hidden');
                autoScrollToBookingInfo();

                setTimeout(() => {
                    if (!videoStream) startCamera();
                    else startScanning();
                }, 3000);
            }
        }

        function displayBookingInfo(booking) {
            const bookingInfo = document.getElementById('booking-info');

            const mainPaymentStatus = paymentStatusMap[booking.main_payment_status] || paymentStatusMap[0];
            const mainPaymentMethod = paymentMethodMap[booking.main_payment_method] || paymentMethodMap[0];
            const mainPaymentCategory = paymentCategoryMap[booking.main_payment_category] || paymentCategoryMap[1];

            const extendedPaymentStatus = booking.has_extended_payment ?
                (paymentStatusMap[booking.extended_payment_status] || paymentStatusMap[0]) : null;
            const extendedPaymentMethod = booking.has_extended_payment ?
                (paymentMethodMap[booking.extended_payment_method] || paymentMethodMap[0]) : null;
            const extendedPaymentCategory = booking.has_extended_payment ?
                (paymentCategoryMap[booking.extended_payment_category] || paymentCategoryMap[0]) : null;

            // Main Schedule display
            let mainScheduleStart = '';
            let mainScheduleEnd = '';

            if (booking.main_date_start && booking.main_date_start !== 'N/A') {
                const startDate = formatDateForDisplay(booking.main_date_start);
                const formattedStartTime = booking.main_start_time && booking.main_start_time !== 'N/A' ?
                    formatTimeForDisplay(booking.main_start_time) : 'Time not set';

                mainScheduleStart = `
                    <div class="flex justify-between items-start">
                        <span class="text-gray-600 font-medium">Start:</span>
                        <div class="text-right">
                            <p class="text-gray-700">${startDate}</p>
                            <p class="text-gray-600 text-sm">${formattedStartTime}</p>
                        </div>
                    </div>
                `;
            }

            if (booking.main_date_end && booking.main_date_end !== 'N/A' && booking.main_date_end !== booking
                .main_date_start) {
                const endDate = formatDateForDisplay(booking.main_date_end);
                const formattedEndTime = booking.main_end_time && booking.main_end_time !== 'N/A' ?
                    formatTimeForDisplay(booking.main_end_time) : 'Time not set';

                mainScheduleEnd = `
                    <div class="flex justify-between items-start pt-2 border-t border-blue-100">
                        <span class="text-gray-600 font-medium">End:</span>
                        <div class="text-right">
                            <p class="text-gray-700">${endDate}</p>
                            <p class="text-gray-600 text-sm">${formattedEndTime}</p>
                        </div>
                    </div>
                `;
            } else if (booking.main_end_time && booking.main_end_time !== 'N/A' && booking.main_end_time !== booking
                .main_start_time) {
                const formattedEndTime = formatTimeForDisplay(booking.main_end_time);
                mainScheduleEnd = `
                    <div class="flex justify-between items-start pt-2 border-t border-blue-100">
                        <span class="text-gray-600 font-medium">End Time:</span>
                        <p class="text-gray-700">${formattedEndTime}</p>
                    </div>
                `;
            }

            if (!mainScheduleStart && !mainScheduleEnd) {
                mainScheduleStart = '<p class="text-gray-700 text-center py-2">Schedule not set</p>';
            }

            // Extended Schedule display
            let extendedScheduleSection = '';
            if (booking.has_extended_schedule) {
                let extendedScheduleStart = '';
                let extendedScheduleEnd = '';

                if (booking.extended_date_start && booking.extended_date_start !== 'N/A') {
                    const startDate = formatDateForDisplay(booking.extended_date_start);
                    const formattedStartTime = booking.extended_start_time && booking.extended_start_time !== 'N/A' ?
                        formatTimeForDisplay(booking.extended_start_time) : 'Time not set';

                    extendedScheduleStart = `
                        <div class="flex justify-between items-start">
                            <span class="text-gray-600 font-medium">Start:</span>
                            <div class="text-right">
                                <p class="text-gray-700">${startDate}</p>
                                <p class="text-gray-600 text-sm">${formattedStartTime}</p>
                                <p class="text-xs text-orange-600 font-medium mt-1">Extended Schedule</p>
                            </div>
                        </div>
                    `;
                }

                if (booking.extended_date_end && booking.extended_date_end !== 'N/A' && booking.extended_date_end !==
                    booking.extended_date_start) {
                    const endDate = formatDateForDisplay(booking.extended_date_end);
                    const formattedEndTime = booking.extended_end_time && booking.extended_end_time !== 'N/A' ?
                        formatTimeForDisplay(booking.extended_end_time) : 'Time not set';

                    extendedScheduleEnd = `
                        <div class="flex justify-between items-start pt-2 border-t border-orange-100">
                            <span class="text-gray-600 font-medium">End:</span>
                            <div class="text-right">
                                <p class="text-gray-700">${endDate}</p>
                                <p class="text-gray-600 text-sm">${formattedEndTime}</p>
                            </div>
                        </div>
                    `;
                } else if (booking.extended_end_time && booking.extended_end_time !== 'N/A' && booking.extended_end_time !==
                    booking.extended_start_time) {
                    const formattedEndTime = formatTimeForDisplay(booking.extended_end_time);
                    extendedScheduleEnd = `
                        <div class="flex justify-between items-start pt-2 border-t border-orange-100">
                            <span class="text-gray-600 font-medium">End Time:</span>
                            <p class="text-gray-700">${formattedEndTime}</p>
                        </div>
                    `;
                }

                if (extendedScheduleStart || extendedScheduleEnd) {
                    extendedScheduleSection = `
                        <div>
                            <h4 class="text-lg font-bold text-orange-600 mb-2">Extended Schedule</h4>
                            <div class="bg-orange-50 border border-orange-100 rounded-xl p-4 space-y-3">
                                ${extendedScheduleStart}
                                ${extendedScheduleEnd}
                            </div>
                        </div>
                    `;
                }
            }

            // Main Payment display
            const mainTotalAmountNum = parseFloat(booking.main_payment_total_amount) || 0;
            const mainAmountPaidNum = parseFloat(booking.main_payment_amount_paid) || 0;
            const mainChangeNum = parseFloat(booking.main_payment_change) || 0;

            const mainAmountPaidLine = booking.main_payment_method == 3 ?
                `<div class="flex justify-between"><span class="text-gray-600">Amount Paid:</span><span class="font-semibold">0.00</span></div>` :
                `<div class="flex justify-between"><span class="text-gray-600">Amount Paid:</span><span class="font-semibold">${mainAmountPaidNum.toFixed(2)}</span></div>`;

            const mainChangeLine = booking.main_payment_method == 3 ?
                '' :
                `<div class="flex justify-between"><span class="text-gray-600">Change:</span><span class="font-semibold">${mainChangeNum.toFixed(2)}</span></div>`;

            let mainGcashRefNo = '';
            if (booking.main_gcash_ref_no && booking.main_payment_method == 1) {
                mainGcashRefNo = `
                    <div class="pt-2 border-t border-green-100">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 text-sm">GCash Ref:</span>
                            <span class="font-mono text-sm font-medium">${booking.main_gcash_ref_no}</span>
                        </div>
                    </div>
                `;
            }

            let mainPaymentDate = '';
            if (booking.main_payment_date) {
                mainPaymentDate = `
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 text-sm">Payment Date:</span>
                        <span class="text-sm font-medium">${formatDateForDisplay(booking.main_payment_date)}</span>
                    </div>
                `;
            }

            // Extended Payment display
            let extendedPaymentSection = '';
            if (booking.has_extended_payment) {
                const extendedTotalAmountNum = parseFloat(booking.extended_payment_total_amount) || 0;
                const extendedAmountPaidNum = parseFloat(booking.extended_payment_amount_paid) || 0;
                const extendedChangeNum = parseFloat(booking.extended_payment_change) || 0;

                const extendedAmountPaidLine = booking.extended_payment_method == 3 ?
                    `<div class="flex justify-between"><span class="text-gray-600">Amount Paid:</span><span class="font-semibold">0.00</span></div>` :
                    `<div class="flex justify-between"><span class="text-gray-600">Amount Paid:</span><span class="font-semibold">${extendedAmountPaidNum.toFixed(2)}</span></div>`;

                const extendedChangeLine = booking.extended_payment_method == 3 ?
                    '' :
                    `<div class="flex justify-between"><span class="text-gray-600">Change:</span><span class="font-semibold">${extendedChangeNum.toFixed(2)}</span></div>`;

                let extendedGcashRefNo = '';
                if (booking.extended_gcash_ref_no && booking.extended_payment_method == 1) {
                    extendedGcashRefNo = `
                        <div class="pt-2 border-t border-purple-100">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 text-sm">GCash Ref:</span>
                                <span class="font-mono text-sm font-medium">${booking.extended_gcash_ref_no}</span>
                            </div>
                        </div>
                    `;
                }

                let extendedPaymentDate = '';
                if (booking.extended_payment_date) {
                    extendedPaymentDate = `
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 text-sm">Payment Date:</span>
                            <span class="text-sm font-medium">${formatDateForDisplay(booking.extended_payment_date)}</span>
                        </div>
                    `;
                }

                extendedPaymentSection = `
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-lg font-bold text-purple-600">Extended Payment</h4>
                            <span class="payment-category-badge px-2 py-1 rounded-full text-xs font-medium ${extendedPaymentCategory.class}">
                                ${extendedPaymentCategory.text}
                            </span>
                        </div>
                        <div class="bg-purple-50 border border-purple-100 rounded-xl p-4 space-y-3">
                            <div class="grid grid-cols-2 gap-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Method:</span>
                                    <span class="payment-method inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium ${extendedPaymentMethod.class}">
                                        ${extendedPaymentMethod.text}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Status:</span>
                                    <span class="payment-status-badge px-2 py-1 rounded-full text-xs font-medium ${extendedPaymentStatus.class}">
                                        ${extendedPaymentStatus.text}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="space-y-2 pt-2 border-t border-purple-100">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Amount:</span>
                                    <span class="font-semibold">${extendedTotalAmountNum.toFixed(2)}</span>
                                </div>
                                ${extendedAmountPaidLine}
                                ${extendedChangeLine}
                            </div>
                            
                            ${extendedGcashRefNo}
                            ${extendedPaymentDate}
                        </div>
                    </div>
                `;
            }

            // Combined Total Section
            let combinedTotalSection = '';
            if (booking.has_main_payment && booking.has_extended_payment) {
                const combinedTotal = (parseFloat(booking.main_payment_total_amount) || 0) +
                    (parseFloat(booking.extended_payment_total_amount) || 0);

                combinedTotalSection = `
                    <div>
                        <h4 class="text-lg font-bold text-[color:var(--accent)] mb-3">Combined Total</h4>
                        <div class="bg-gray-50 border-2 border-gray-300 rounded-xl p-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 font-medium">Main Payment:</span>
                                <span class="font-semibold">${(parseFloat(booking.main_payment_total_amount) || 0).toFixed(2)}</span>
                            </div>
                            <div class="flex justify-between items-center mt-2">
                                <span class="text-gray-600 font-medium">Extended Payment:</span>
                                <span class="font-semibold">${(parseFloat(booking.extended_payment_total_amount) || 0).toFixed(2)}</span>
                            </div>
                            <div class="flex justify-between items-center mt-3 pt-3 border-t border-gray-300">
                                <span class="text-gray-800 font-bold">Grand Total:</span>
                                <span class="text-xl font-bold text-[color:var(--accent)]">${combinedTotal.toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                `;
            }

            // Extension badge
            let extensionBadge = '';
            if (booking.has_extended_schedule || booking.has_extended_payment) {
                extensionBadge = `
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-orange-100 text-orange-800 text-xs font-medium border border-orange-200 ml-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Extended
                    </div>
                `;
            }

            // Unpaid Orders Badge
            let unpaidOrdersBadge = '';
            if (booking.has_unpaid_orders && booking.unpaid_orders_count > 0) {
                unpaidOrdersBadge = `
                    <span class="unpaid-orders-badge ml-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        ${booking.unpaid_orders_count} Unpaid Order${booking.unpaid_orders_count > 1 ? 's' : ''}
                    </span>
                `;
            }

            // Order Eligible Badge
            const isCheckedIn = booking.checkin_id !== null && booking.checkin_id !== undefined && booking.checkin_status == 1;
            const isBooked = booking.booking_status == 1;
            const isOrderEligible = isCheckedIn || isBooked;

            let orderEligibleBadge = '';
            if (isOrderEligible) {
                let eligibilityText = '';
                if (isCheckedIn && isBooked) {
                    eligibilityText = '✅ Checked In & Booked';
                } else if (isCheckedIn) {
                    eligibilityText = '✅ Checked In';
                } else if (isBooked) {
                    eligibilityText = '✅ Booked';
                }
                orderEligibleBadge = `
                    <span class="order-eligible-badge">
                        ${eligibilityText}
                    </span>
                `;
            }

            // QR Code Validity Status
            const isQrCodeValid = booking.is_qr_code_valid === true;
            const qrValidityClass = isQrCodeValid ? 'qr-valid border-green-200' : 'border-gray-200';
            
            let qrValidityStatus = '';

            if (isQrCodeValid) {
                qrValidityStatus = `
                    <div class="validity-status-badge valid">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        QR Code is Valid - You may proceed with ordering
                    </div>
                `;
            } else {
                qrValidityStatus = '';
            }

            // Expired Notice
            const isExpired = booking.is_expired === true;
            
            let expiredNotice = '';
            if (isExpired) {
                expiredNotice = `
                    <div class="expired-notice">
                        <div class="text">
                            ⚠️ This booking time has expired, but the QR code is still valid for ordering.
                        </div>
                        <div class="sub-text">
                            ${booking.display_date_start ? `Scheduled for: ${formatDateForDisplay(booking.display_date_start)}` : ''}
                            ${booking.display_start_time ? ` at ${formatTimeForDisplay(booking.display_start_time)}` : ''}
                            ${booking.display_end_time ? ` - ${formatTimeForDisplay(booking.display_end_time)}` : ''}
                        </div>
                    </div>
                `;
            }

            // Booking type styling
            const isOnlineBooking = booking.booking_type === 1;
            const bookingTypeText = isOnlineBooking ? 'Online Booking' : 'Walk-in Booking';
            const bookingTypeClass = isOnlineBooking ? 'booking-type-online' : 'booking-type-walkin';
            const bookingTypeIcon = isOnlineBooking ?
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9" />' :
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z" />';

            // Get today's date in Manila timezone
            const nowManila = new Date(new Date().toLocaleString('en-US', {
                timeZone: 'Asia/Manila'
            }));
            const todayDateString = nowManila.getFullYear() + '-' +
                ('0' + (nowManila.getMonth() + 1)).slice(-2) + '-' +
                ('0' + nowManila.getDate()).slice(-2);

            let actionButtonHtml = '';

            // Determine which date to use for comparison
            const displayDateStart = booking.has_extended_schedule ?
                (booking.extended_date_start || booking.main_date_start) : booking.main_date_start;

            // Parse dates for comparison
            const bookingDate = new Date(displayDateStart + 'T00:00:00');
            const todayDate = new Date(todayDateString + 'T00:00:00');
            const isToday = (displayDateStart === todayDateString);
            const isPastDate = bookingDate < todayDate;
            const isFutureDate = bookingDate > todayDate;

            // Action Button Logic
            if (booking.booking_status === 2) {
                actionButtonHtml = `
                    <div class="inline-flex flex-col items-center gap-3 p-4 bg-amber-50 rounded-lg border border-amber-200 w-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                        <div class="text-center">
                            <h3 class="text-sm font-semibold text-amber-700">Pending Booking</h3>
                            <p class="text-xs text-amber-600 mt-1">This booking is still pending approval</p>
                            <button onclick="redirectToCheckinList('${booking.booking_ref_no}')" 
                                class="mt-2 px-4 py-2 bg-amber-600 text-white text-sm rounded-lg hover:bg-amber-700 transition-colors">
                                View in Checkin Lists
                            </button>
                        </div>
                    </div>
                `;
            } else if (booking.booking_status === 4) {
                actionButtonHtml = `
                    <div class="inline-flex flex-col items-center gap-3 p-4 bg-gray-100 rounded-lg border border-gray-300 w-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="text-center">
                            <h3 class="text-sm font-semibold text-gray-700">Booking Completed</h3>
                            <p class="text-xs text-gray-600 mt-1">This booking has been completed</p>
                            <button onclick="redirectToCheckinList('${booking.booking_ref_no}')" 
                                class="mt-2 px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                                View Details
                            </button>
                        </div>
                    </div>
                `;
            } else if (isToday && !booking.checkin_id && booking.booking_status !== 2) {
                actionButtonHtml = `
                    <button onclick="proceedToCheckin(${booking.booking_id}, ${booking.booking_type}, '${booking.booking_ref_no}')" 
                        class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-[color:var(--accent)] text-white font-semibold hover:opacity-95 transition-all duration-200 hover:scale-105">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                        Proceed to Check-in
                    </button>
                    <p class="text-xs text-gray-600 mt-2">
                        ${isOnlineBooking ? 
                            'This online booking will be saved to check-ins' : 
                            'This walk-in booking will be redirected to check-ins page'}
                    </p>
                `;
            } else if (booking.checkin_id) {
                actionButtonHtml = `
                    <div class="inline-flex flex-col items-center gap-3 p-4 bg-green-50 rounded-lg border border-green-200 w-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="text-center">
                            <h3 class="text-sm font-semibold text-green-700">Already Checked In</h3>
                            <p class="text-xs text-green-600 mt-1">This booking is already checked in</p>
                            <div class="mt-3 flex flex-col sm:flex-row gap-2 justify-center">
                                <button onclick="redirectToCheckinList('${booking.booking_ref_no}')" 
                                    class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                                    View in Checkin Lists
                                </button>
                                <button onclick="proceedToCheckout(${booking.booking_id}, ${booking.checkin_id}, '${booking.booking_ref_no}', '${booking.uuid}')" 
                                    class="px-4 py-2 bg-amber-600 text-white text-sm rounded-lg hover:bg-amber-700 transition-colors">
                                    Check-out Customer
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            } else if (isFutureDate) {
                actionButtonHtml = `
                    <div class="inline-flex flex-col items-center gap-3 p-4 bg-blue-50 rounded-lg border border-blue-200 w-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="text-center">
                            <h3 class="text-sm font-semibold text-blue-700">Not Scheduled for Today</h3>
                            <p class="text-xs text-blue-600 mt-1">Scheduled for ${formatDateForDisplay(displayDateStart)}</p>
                            <button onclick="redirectToCheckinList('${booking.booking_ref_no}')" 
                                class="mt-2 px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                                View Details
                            </button>
                        </div>
                    </div>
                `;
            } else if (isPastDate && !booking.checkin_id) {
                actionButtonHtml = `
                    <div class="inline-flex flex-col items-center gap-3 p-4 bg-gray-100 rounded-lg border border-gray-300 w-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="text-center">
                            <h3 class="text-sm font-semibold text-gray-700">Booking Date Passed</h3>
                            <p class="text-xs text-gray-600 mt-1">The booking date has passed and customer is not checked in</p>
                            <button onclick="redirectToCheckinList('${booking.booking_ref_no}')" 
                                class="mt-2 px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                                View Details
                            </button>
                        </div>
                    </div>
                `;
            } else {
                actionButtonHtml = `
                    <div class="inline-flex flex-col items-center gap-3 p-4 bg-gray-50 rounded-lg border border-gray-200 w-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="text-center">
                            <h3 class="text-sm font-semibold text-gray-700">Unable to Check In</h3>
                            <p class="text-xs text-gray-600 mt-1">Please contact support</p>
                            <button onclick="redirectToCheckinList('${booking.booking_ref_no}')" 
                                class="mt-2 px-4 py-2 bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 transition-colors">
                                View Details
                            </button>
                        </div>
                    </div>
                `;
            }

            const seatNo = booking.seat_no ?
                `<p class="text-gray-700">Seat No: <span class="font-semibold">${booking.seat_no}</span></p>` : '';
            const roomNo = booking.room_no ?
                `<p class="text-gray-700">Room No: <span class="font-semibold">${booking.room_no}</span></p>` : '';

            // Order Button - Shows if customer is checked in
            const showOrderButton = isCheckedIn;

            let orderButtonHtml = '';

            if (showOrderButton) {
                let eligibilityText = '';
                if (isCheckedIn && isBooked) {
                    eligibilityText = ' (Checked In & Booked)';
                } else if (isCheckedIn) {
                    eligibilityText = ' (Checked In)';
                } else if (isBooked) {
                    eligibilityText = ' (Booked)';
                }

                const unpaidText = booking.unpaid_orders_count > 0 ? ` (${booking.unpaid_orders_count} unpaid)` : '';

                orderButtonHtml = `
                    <button onclick="redirectToPos(${booking.booking_id}, ${booking.checkin_id})" 
                        class="btn-order inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold transition-all duration-200 shadow-sm hover:shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 a2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Order${unpaidText}${eligibilityText}
                    </button>
                `;
            }

            // Build the final HTML
            bookingInfo.innerHTML = bookingTemplate
                .replace(/{bookingRef}/g, booking.booking_ref_no)
                .replace(/{customerName}/g, `${booking.customer_first_name} ${booking.customer_last_name}`)
                .replace(/{customerEmail}/g, booking.customer_email || 'N/A')
                .replace(/{customerContact}/g, booking.customer_contact_no || 'N/A')
                .replace(/{branchName}/g, booking.branch_name)
                .replace(/{serviceCategory}/g, booking.service_category)
                .replace(/{serviceName}/g, booking.service_name)
                .replace(/{seatNo}/g, seatNo)
                .replace(/{roomNo}/g, roomNo)
                .replace(/{mainScheduleStart}/g, mainScheduleStart)
                .replace(/{mainScheduleEnd}/g, mainScheduleEnd)
                .replace(/{extendedScheduleSection}/g, extendedScheduleSection)
                .replace(/{mainPaymentMethod}/g, mainPaymentMethod.text)
                .replace(/{mainPaymentMethodClass}/g, mainPaymentMethod.class)
                .replace(/{mainPaymentCategoryText}/g, mainPaymentCategory.text)
                .replace(/{mainPaymentCategoryClass}/g, mainPaymentCategory.class)
                .replace(/{mainTotalAmount}/g, mainTotalAmountNum.toFixed(2))
                .replace(/{mainAmountPaidLine}/g, mainAmountPaidLine)
                .replace(/{mainChangeLine}/g, mainChangeLine)
                .replace(/{mainStatusClass}/g, mainPaymentStatus.class)
                .replace(/{mainPaymentStatusText}/g, mainPaymentStatus.text)
                .replace(/{mainGcashRefNo}/g, mainGcashRefNo)
                .replace(/{mainPaymentDate}/g, mainPaymentDate)
                .replace(/{extendedPaymentSection}/g, extendedPaymentSection)
                .replace(/{combinedTotalSection}/g, combinedTotalSection)
                .replace(/{bookingTypeClass}/g, bookingTypeClass)
                .replace(/{bookingTypeIcon}/g, bookingTypeIcon)
                .replace(/{bookingTypeText}/g, bookingTypeText)
                .replace(/{actionButton}/g, actionButtonHtml)
                .replace(/{orderButton}/g, orderButtonHtml)
                .replace(/{extensionBadge}/g, extensionBadge)
                .replace(/{unpaidOrdersBadge}/g, unpaidOrdersBadge)
                .replace(/{orderEligibleBadge}/g, orderEligibleBadge)
                .replace(/{qrValidityClass}/g, qrValidityClass)
                .replace(/{qrValidityStatus}/g, qrValidityStatus)
                .replace(/{expiredNotice}/g, expiredNotice);

            bookingInfo.classList.remove('hidden');
        }

        // =====================================================================
        // REDIRECT TO POS FUNCTION
        // =====================================================================
        async function redirectToPos(bookingId, checkinId) {
            try {
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                if (!csrfMeta) throw new Error('CSRF token meta tag not found');

                const csrfToken = csrfMeta.getAttribute('content');
                const routeUrl = '{{ route('sub_two.scan_qr_code_bookings.redirectToPos') }}';

                const actionContainer = document.querySelector('#booking-action-container');
                if (actionContainer) {
                    actionContainer.innerHTML = `
                        <div class="inline-flex flex-col items-center gap-3 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="w-8 h-8 border-2 border-blue-300 border-t-blue-600 rounded-full animate-spin"></div>
                            <div class="text-center">
                                <h3 class="text-sm font-semibold text-blue-700">Redirecting to POS</h3>
                                <p class="text-xs text-blue-600 mt-1">Loading point of sale...</p>
                            </div>
                        </div>
                    `;
                }

                const response = await fetch(routeUrl + `?booking_id=${bookingId}&checkin_id=${checkinId}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    }
                });

                if (!response.ok) {
                    let errorMessage = `Server error (${response.status})`;
                    try {
                        const errorData = await response.json();
                        errorMessage = errorData.message || errorMessage;
                    } catch (e) {
                        errorMessage = response.statusText || errorMessage;
                    }
                    throw new Error(errorMessage);
                }

                const data = await response.json();

                if (data.success && data.redirect_url) {
                    if (actionContainer) {
                        actionContainer.innerHTML = `
                            <div class="inline-flex flex-col items-center gap-3 p-4 bg-green-50 rounded-lg border border-green-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="text-center">
                                    <h3 class="text-sm font-semibold text-green-700">Redirecting to POS</h3>
                                    <p class="text-xs text-green-600 mt-1">Taking you to the point of sale...</p>
                                </div>
                            </div>
                        `;
                    }

                    scanResult.innerHTML = `<div class="scan-success" style="color:#059669">Redirecting to POS...</div>`;

                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 1000);

                } else {
                    throw new Error(data.message || 'Failed to redirect to POS');
                }

            } catch (error) {
                console.error('Error redirecting to POS:', error);

                const actionContainer = document.querySelector('#booking-action-container');
                if (actionContainer) {
                    actionContainer.innerHTML = `
                        <div class="inline-flex flex-col items-center gap-3 p-4 bg-red-50 rounded-lg border border-red-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="text-center">
                                <h3 class="text-sm font-semibold text-red-700">Redirect Failed</h3>
                                <p class="text-xs text-red-600 mt-1">${error.message}</p>
                            </div>
                            <button onclick="redirectToPos(${bookingId}, ${checkinId})" 
                                class="mt-2 px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition-colors">
                                Try Again
                            </button>
                        </div>
                    `;
                }

                scanResult.innerHTML = `<div class="scan-error" style="color:#dc2626">Redirect failed: ${error.message}</div>`;
            }
        }

        // =====================================================================
        // PROCEED TO CHECKOUT FUNCTION
        // =====================================================================
        async function proceedToCheckout(bookingId, checkinId, bookingRefNo, bookingUuid) {
            try {
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                if (!csrfMeta) throw new Error('CSRF token meta tag not found');

                const csrfToken = csrfMeta.getAttribute('content');
                const routeUrl = '{{ route('sub_two.scan_qr_code_bookings.checkout') }}';

                const actionContainer = document.querySelector('#booking-action-container');
                if (actionContainer) {
                    actionContainer.innerHTML = `
                        <div class="inline-flex flex-col items-center gap-3 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="w-8 h-8 border-2 border-blue-300 border-t-blue-600 rounded-full animate-spin"></div>
                            <div class="text-center">
                                <h3 class="text-sm font-semibold text-blue-700">Processing Check-out</h3>
                                <p class="text-xs text-blue-600 mt-1">Checking out customer...</p>
                            </div>
                        </div>
                    `;
                }

                const response = await fetch(routeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        booking_id: bookingId,
                        checkin_id: checkinId
                    })
                });

                const responseText = await response.text();

                if (!response.ok) {
                    let errorMessage = `Server error (${response.status})`;
                    try {
                        const errorData = JSON.parse(responseText);
                        errorMessage = errorData.message || errorMessage;
                    } catch (e) {
                        errorMessage = responseText || errorMessage;
                    }
                    throw new Error(errorMessage);
                }

                const data = JSON.parse(responseText);

                if (data.success) {
                    let redirectMessage = '';
                    if (data.payment_type === 'order') {
                        redirectMessage = `Redirecting to order payment page (${data.unpaid_orders_count} unpaid order${data.unpaid_orders_count !== 1 ? 's' : ''})...`;
                    } else if (data.payment_type === 'extension') {
                        redirectMessage = 'Redirecting to extension payment page...';
                    } else if (data.payment_type === 'main') {
                        redirectMessage = 'Redirecting to main payment page...';
                    } else {
                        redirectMessage = 'Redirecting to booking list...';
                    }

                    if (actionContainer) {
                        actionContainer.innerHTML = `
                            <div class="inline-flex flex-col items-center gap-3 p-4 bg-green-50 rounded-lg border border-green-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="text-center">
                                    <h3 class="text-sm font-semibold text-green-700">Check-out Successful!</h3>
                                    <p class="text-xs text-green-600 mt-1">Customer has been checked out</p>
                                    <div class="mt-2 space-y-1">
                                        <p class="text-xs text-gray-600">Time Used: ${data.time_used_formatted}</p>
                                        ${data.extended_time_used_formatted ? 
                                            `<p class="text-xs text-purple-600">Extended Time: ${data.extended_time_used_formatted}</p>` : ''}
                                        <p class="text-xs font-medium text-green-700">Total Time: ${data.total_time_used_formatted}</p>
                                        ${data.has_unpaid_orders ? 
                                            `<p class="text-xs font-medium text-orange-600 mt-2">⚠️ ${data.unpaid_orders_count} unpaid order${data.unpaid_orders_count !== 1 ? 's' : ''} need payment</p>` : ''}
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2">${redirectMessage}</p>
                                </div>
                            </div>
                        `;
                    }

                    scanResult.innerHTML =
                        `<div class="scan-success" style="color:#059669">Customer checked out successfully! ${redirectMessage}</div>`;

                    setTimeout(() => {
                        if (data.redirect_url) {
                            window.location.href = data.redirect_url;
                        } else {
                            window.location.href = '{{ route('sub_two.booking_lists.showBookingList') }}';
                        }
                    }, 2000);

                } else {
                    throw new Error(data.message);
                }

            } catch (error) {
                console.error('Error:', error.message);

                const actionContainer = document.querySelector('#booking-action-container');
                if (!actionContainer) return;

                const errorHtml = `
                    <div class="inline-flex flex-col items-center gap-3 p-4 bg-red-50 rounded-lg border border-red-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="text-center">
                            <h3 class="text-sm font-semibold text-red-700">Check-out Failed</h3>
                            <p class="text-xs text-red-600 mt-1">${error.message}</p>
                        </div>
                        <div class="mt-2 flex gap-2">
                            <button onclick="proceedToCheckout(${bookingId}, ${checkinId}, '${bookingRefNo}', '${bookingUuid}')" 
                                class="px-4 py-2 bg-amber-600 text-white text-sm rounded-lg hover:bg-amber-700 transition-colors">
                                Try Again
                            </button>
                        </div>
                    </div>
                `;

                actionContainer.innerHTML = errorHtml;

                scanResult.innerHTML =
                    `<div class="scan-error" style="color:#dc2626">Check-out failed: ${error.message}</div>`;
            }
        }

        // Helper functions for date/time formatting
        function formatDateForDisplay(dateString) {
            if (!dateString || dateString === 'N/A') return 'Date not set';

            try {
                return new Date(dateString).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            } catch (e) {
                return dateString;
            }
        }

        function formatTimeForDisplay(timeString) {
            if (!timeString || timeString === 'N/A') return 'Time not set';

            try {
                return new Date('1970-01-01T' + timeString).toLocaleTimeString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
            } catch (e) {
                if (timeString.includes(':')) {
                    const [hours, minutes] = timeString.split(':');
                    const hour = parseInt(hours);
                    const ampm = hour >= 12 ? 'PM' : 'AM';
                    const displayHour = hour % 12 || 12;
                    return `${displayHour}:${minutes} ${ampm}`;
                }
                return timeString;
            }
        }

        function redirectToCheckinList(bookingRefNo) {
            const url = '{{ route('sub_two.customer_checkins.index') }}?brn=' + encodeURIComponent(bookingRefNo);
            window.location.href = url;
        }

        function fetchBookingManual() {
            const bookingRef = document.getElementById('manual-ref-input').value;
            if (!bookingRef) {
                scanResult.innerHTML =
                    `<div class="scan-error" style="color:#dc2626">Please enter a booking reference</div>`;
                return;
            }

            if (videoStream) stopCamera();
            removeUploadedImage();

            processingSpinner.classList.remove('hidden');
            scanResult.innerHTML = `<div class="scan-success" style="color:#059669">Processing manual entry...</div>`;
            successMessage.classList.remove('hidden');
            fetchBookingInfo(bookingRef);
        }

        function clearAll() {
            stopCamera();
            removeUploadedImage();
            document.getElementById('manual-ref-input').value = '';
            document.getElementById('booking-info').classList.add('hidden');
            document.getElementById('booking-info').innerHTML = '';
            scanResult.innerHTML = '';
            processingSpinner.classList.add('hidden');
            successMessage.classList.add('hidden');
            cameraPermission.classList.remove('hidden');
        }

        document.getElementById('manual-ref-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                fetchBookingManual();
            }
        });

        function proceedToCheckin(bookingId, bookingType, bookingRefNo) {
            if (bookingType === 1) {
                handleOnlineCheckin(bookingId, bookingRefNo);
            } else {
                redirectToCheckinList(bookingRefNo);
            }
        }

        async function handleOnlineCheckin(bookingId, bookingRefNo) {
            try {
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                if (!csrfMeta) throw new Error('CSRF token meta tag not found');

                const csrfToken = csrfMeta.getAttribute('content');
                const routeUrl = '{{ route('sub_two.scan_qr_code_bookings.storeCheckin') }}';

                const actionContainer = document.querySelector('#booking-action-container');
                if (actionContainer) {
                    actionContainer.innerHTML = `
                        <div class="inline-flex flex-col items-center gap-3 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="w-8 h-8 border-2 border-blue-300 border-t-blue-600 rounded-full animate-spin"></div>
                            <div class="text-center">
                                <h3 class="text-sm font-semibold text-blue-700">Saving Check-in</h3>
                                <p class="text-xs text-blue-600 mt-1">Processing online booking check-in...</p>
                            </div>
                        </div>
                    `;
                }

                const response = await fetch(routeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        booking_id: bookingId
                    })
                });

                const responseText = await response.text();

                if (!response.ok) {
                    let errorMessage = `Server error (${response.status})`;
                    try {
                        const errorData = JSON.parse(responseText);
                        errorMessage = errorData.message || errorMessage;
                    } catch (e) {
                        errorMessage = responseText || errorMessage;
                    }
                    throw new Error(errorMessage);
                }

                const data = JSON.parse(responseText);

                if (data.success) {
                    if (actionContainer) {
                        actionContainer.innerHTML = `
                            <div class="inline-flex flex-col items-center gap-3 p-4 bg-green-50 rounded-lg border border-green-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="text-center">
                                    <h3 class="text-sm font-semibold text-green-700">Check-in Saved!</h3>
                                    <p class="text-xs text-green-600 mt-1">Online booking check-in completed</p>
                                    <p class="text-xs text-green-500 mt-2">Time used: ${data.time_used_formatted}</p>
                                    <p class="text-xs text-gray-500 mt-2">Redirecting to check-ins page...</p>
                                </div>
                            </div>
                        `;
                    }

                    scanResult.innerHTML =
                        `<div class="scan-success" style="color:#059669">Online booking check-in completed successfully!</div>`;

                    setTimeout(() => {
                        redirectToCheckinList(bookingRefNo);
                    }, 2000);

                } else {
                    throw new Error(data.message);
                }

            } catch (error) {
                console.error('Error:', error.message);

                const actionContainer = document.querySelector('#booking-action-container');
                const errorHtml = `
                    <div class="inline-flex flex-col items-center gap-3 p-4 bg-red-50 rounded-lg border border-red-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="text-center">
                            <h3 class="text-sm font-semibold text-red-700">Check-in Failed</h3>
                            <p class="text-xs text-red-600 mt-1">${error.message}</p>
                        </div>
                        <button onclick="handleOnlineCheckin(${bookingId}, '${bookingRefNo}')" 
                            class="mt-2 px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition-colors">
                            Try Again
                        </button>
                        <button onclick="redirectToCheckinList('${bookingRefNo}')" 
                            class="mt-1 px-4 py-2 bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 transition-colors">
                            Go to Check-ins Anyway
                        </button>
                    </div>
                `;

                if (actionContainer) {
                    actionContainer.innerHTML = errorHtml;
                }

                scanResult.innerHTML =
                    `<div class="scan-error" style="color:#dc2626">Check-in failed: ${error.message}</div>`;
            }
        }
    </script>
@endsection