<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $appName }} - Booking Confirmed</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            padding: 20px 0;
            background: linear-gradient(135deg, #8d6e63, #6d4c41);
            color: white;
            border-radius: 10px 10px 0 0;
            margin: -20px -20px 20px -20px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .content {
            padding: 20px;
        }

        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #5d4037;
        }

        h3.section-title {
            color: #5d4037;
            border-bottom: 2px solid #8d6e63;
            padding-bottom: 5px;
            margin-top: 30px;
            margin-bottom: 15px;
            text-transform: uppercase;
            font-size: 16px;
        }

        .booking-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #8d6e63;
        }

        .info-item {
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }

        .info-label {
            font-weight: bold;
            color: #5d4037;
            min-width: 150px;
        }

        .info-value {
            flex: 1;
            text-align: right;
        }

        .payment-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #8d6e63;
        }

        .payment-item {
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }

        .payment-label {
            font-weight: bold;
            color: #5d4037;
            min-width: 150px;
        }

        .payment-value {
            flex: 1;
            text-align: right;
        }

        .qr-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .qr-instruction {
            background: #e8f5e8;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #4caf50;
            margin: 20px 0;
            text-align: center;
        }

        .qr-instruction h4 {
            color: #2e7d32;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .qr-steps {
            text-align: left;
            display: inline-block;
            margin: 15px 0;
        }

        .qr-steps li {
            margin-bottom: 10px;
            padding-left: 5px;
        }

        .download-icon {
            color: #2196f3;
            font-weight: bold;
        }

        .note {
            background: #fff8e1;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
            margin: 20px 0;
            font-size: 14px;
            color: #7a5a00;
        }

        .error {
            color: #dc3545;
            background: #f8d7da;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
            border-left: 4px solid #dc3545;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e8d6c9;
            color: #8d6e63;
            font-size: 14px;
        }

        .success-badge {
            background: #4caf50;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 15px;
        }

        .divider {
            height: 1px;
            background: #e8d6c9;
            margin: 15px 0;
        }

        .icon {
            font-size: 20px;
            margin-right: 8px;
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="logo">{{ $appName }}</div>
            <h1>Booking Confirmed</h1>
            <div class="success-badge">Payment Successful</div>
        </div>

        <div class="content">
            <div class="greeting">
                Hello <strong>{{ $customer->first_name }} {{ $customer->last_name }}</strong>,
            </div>

            <p>Thank you for booking! Your payment has been processed successfully and your booking is now
                confirmed.</p>

            <p style="font-style: italic; color: #5d4037; background: #f8f9fa; padding: 10px; border-radius: 5px; text-align: center;">Please scroll down to find instructions for downloading your attached QR code.</p>

            <!-- Booking Information Header -->
            <h3 class="section-title">Booking Information</h3>

            <!-- Booking Information Section -->
            <div class="booking-info">
                <div class="info-item">
                    <span class="info-label">Booking Reference:</span>
                    <span class="info-value"><strong>{{ $booking->booking_ref_no }}</strong></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Branch:</span>
                    <span class="info-value">{{ $branch->branch_name ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Location:</span>
                    <span class="text-left">{{ $branch->location ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Service Category:</span>
                    <span class="info-value">{{ $serviceCategory->service_category ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Service:</span>
                    <span class="info-value">{{ $serviceName->service_name ?? 'N/A' }}</span>
                </div>
                @if ($serviceName->space_type)
                    <div class="info-item">
                        <span class="info-label">Space Type:</span>
                        <span class="info-value">{{ $serviceName->space_type }}</span>
                    </div>
                @endif
                @if ($seat)
                    <div class="info-item">
                        <span class="info-label">Seat/Room:</span>
                        <span class="info-value">
                            @if ($seat->room_no)
                                Room {{ $seat->room_no }}
                            @elseif($seat->seat_no)
                                Seat {{ $seat->seat_no }}
                            @else
                                N/A
                            @endif
                        </span>
                    </div>
                @endif
                <div class="divider"></div>
                <div class="info-item">
                    <span class="info-label">Date:</span>
                    <span class="info-value">
                        {{ \Carbon\Carbon::parse($booking->date_start)->format('F j, Y') }}
                        @if ($booking->date_end && $booking->date_end != $booking->date_start)
                            - {{ \Carbon\Carbon::parse($booking->date_end)->format('F j, Y') }}
                        @endif
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Time:</span>
                    <span class="info-value">
                        {{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }} -
                        {{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Duration:</span>
                    <span class="info-value">{{ $serviceName->time_duration ?? 'N/A' }}</span>
                </div>
            </div>

            <!-- Payment Information Section -->
            <h3 class="section-title">Payment Information</h3>
            <div class="payment-section">
                <div class="payment-item">
                    <span class="payment-label">Payment Status:</span>
                    <span class="payment-value" style="color: #4caf50; font-weight: bold;">
                        @php
                            $paymentStatus = $bookingPayment->payment_status ?? 0;
                            $statusText = [
                                0 => 'Invalid',
                                1 => 'Paid',
                                2 => 'Unpaid',
                            ];
                        @endphp
                        {{ $statusText[$paymentStatus] ?? 'Unknown' }}
                    </span>
                </div>
                <div class="payment-item">
                    <span class="payment-label">Payment Method:</span>
                    <span class="payment-value">
                        @php
                            $paymentMethod = $bookingPayment->payment_method ?? 1;
                            $methodText = [
                                0 => 'Cash',
                                1 => 'GCash',
                                2 => 'Debit Card',
                                3 => 'Pay Later',
                            ];
                        @endphp
                        {{ $methodText[$paymentMethod] ?? 'Unknown' }}
                    </span>
                </div>
                @if ($bookingPayment)
                    <div class="payment-item">
                        <span class="payment-label">GCash Reference:</span>
                        <span class="payment-value">{{ $bookingPayment->gcash_ref_no ?? 'N/A' }}</span>
                    </div>
                    <div class="divider"></div>
                    <div class="payment-item">
                        <span class="payment-label">Total Amount:</span>
                        <span class="payment-value"
                            style="font-weight: bold; font-size: 16px;">₱{{ number_format($bookingPayment->total_amount ?? 0, 2) }}</span>
                    </div>
                    <div class="payment-item">
                        <span class="payment-label">Amount Paid:</span>
                        <span class="payment-value"
                            style="font-weight: bold; color: #4caf50;">₱{{ number_format($bookingPayment->amount_paid ?? 0, 2) }}</span>
                    </div>
                @endif
            </div>

            <!-- QR Code Section -->
            <div class="qr-section">
                {{-- This assumes your controller will pass a boolean $qrCodeAttached --}}
                @if (isset($qrCodeAttached) && $qrCodeAttached) 
                    <h4>📱 Your QR Code is Attached!</h4>
                    <p>Please check the attachments to this email for your QR code.</p>
                    
                    <div class="qr-instruction">
                        <h4><span class="icon">📥</span> Download Your QR Code</h4>
                        <p>A QR code file is attached to this email. Please download it and have it ready to be scanned at the front desk.</p>
                        <ul class="qr-steps">
                            <li>Check the attachments section of this email.</li>
                            <li>Download the file (usually named 'qr_code.png').</li>
                            <li>Save it to your phone's gallery.</li>
                            <li>Present this QR code for check-in.</li>
                        </ul>
                    </div>
                @else
                    <div class="error">
                        <span class="icon">⚠️</span>
                        <h4>QR Code Not Available</h4>
                        <p>We were unable to generate a QR code for your booking.</p>
                        <p><strong>Don't worry!</strong> You can still use your booking by presenting your reference number at the front desk:</p>
                        <p style="font-size: 18px; font-weight: bold; margin: 15px 0; color: #5d4037;">
                            {{ $booking->booking_ref_no }}
                        </p>
                        <p>The staff will be able to look up your booking using this reference number.</p>
                    </div>
                @endif
            </div>

            <!-- Important Notes -->
            <div class="note">
                <p><strong>📌 Reminder:</strong></p>
                <ul>
                    <li>Please arrive on time for your booking</li>
                    <li>Bring a valid ID for verification</li>
                    <li>Present your QR code or booking reference number at the front desk</li>
                    <li>Contact us if you need to modify or cancel your booking</li>
                </ul>
            </div>

            <p>We look forward to serving you at {{ $branch->branch_name ?? 'N/A' }}! If you have any questions,
                please don't hesitate to contact us.</p>

            <p>Best regards,<br>
                <strong>The {{ $appName }} Team</strong>
            </p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
            <p>Contact us: linkudhub@gmail.com | 09084557940</p>
        </div>
    </div>
</body>

</html>