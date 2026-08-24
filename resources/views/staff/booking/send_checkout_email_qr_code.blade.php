<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $appName }} - Booking Confirmation</title>
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
        .qr-image {
            border: 2px solid #8d6e63;
            border-radius: 8px;
            padding: 10px;
            background: white;
            display: inline-block;
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
        .password-section {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #4caf50;
        }
        .password-item {
            margin-bottom: 8px;
        }
        .password-label {
            font-weight: bold;
            color: #2e7d32;
            margin-bottom: 5px;
        }
        .password-value {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: bold;
            color: #1b5e20;
            background: white;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #4caf50;
            text-align: center;
            letter-spacing: 2px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e8d6c9;
            color: #8d6e63;
            font-size: 14px;
        }
        .error {
            color: #dc3545;
            background: #f8d7da;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            text-align: center;
        }
        .divider {
            height: 1px;
            background: #e8d6c9;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">{{ $appName }}</div>
            <h1>Booking Confirmed</h1>
        </div>

        <div class="content">
            <div class="greeting">
                Hello <strong>{{ $customer->first_name }} {{ $customer->last_name }}</strong>,
            </div>

            <p>Thank you for your booking! Here are your booking details:</p>

            <!-- Booking Information Header -->
            <h3 class="section-title">Booking Information</h3>

            <!-- Booking Information Section -->
            <div class="booking-info">
                <div class="info-item">
                    <span class="info-label">Booking Reference:</span>
                    <span class="info-value">{{ $booking->booking_ref_no }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Branch:</span>
                    <span class="info-value">{{ $branch->branch_name ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Service:</span>
                    <span class="info-value">
                        {{ $serviceCategory->service_category ?? 'N/A' }} - 
                        {{ $serviceName->service_name ?? 'N/A' }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Space Type:</span>
                    <span class="info-value">{{ $serviceName->space_type ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Seat/Room:</span>
                    <span class="info-value">
                        @if ($seat->room_no)
                            Room {{ $seat->room_no }}
                        @else
                            Seat {{ $seat->seat_no }}
                        @endif
                    </span>
                </div>
                <div class="divider"></div>
                <div class="info-item">
                    <span class="info-label">Booking Date:</span>
                    <span class="info-value">
                        {{ \Carbon\Carbon::parse($booking->booking_date)->format('F j, Y') }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Start Time:</span>
                    <span class="info-value">
                        {{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }}
                    </span>
                </div>
            </div>

            <!-- Payment Information Section -->
            <h3 class="section-title">Payment Information</h3>
            <div class="payment-section">
                <div class="payment-item">
                    <span class="payment-label">Payment Status:</span>
                    <span class="payment-value">
                        @php
                            $paymentStatus = $bookingPayment->payment_status ?? 0;
                            $statusText = [
                                0 => 'Invalid',
                                1 => 'Paid',
                                2 => 'Unpaid'
                            ];
                        @endphp
                        {{ $statusText[$paymentStatus] ?? 'Unknown' }}
                    </span>
                </div>
                <div class="payment-item">
                    <span class="payment-label">Payment Method:</span>
                    <span class="payment-value">
                        @php
                            $paymentMethod = $bookingPayment->payment_method ?? 3;
                            $methodText = [
                                0 => 'Cash',
                                1 => 'GCash',
                                2 => 'Debit Card',
                                3 => 'Pay Later'
                            ];
                        @endphp
                        {{ $methodText[$paymentMethod] ?? 'Unknown' }}
                    </span>
                </div>
                @if($bookingPayment && $bookingPayment->payment_method != 3)
                <div class="payment-item">
                    <span class="payment-label">Total Amount:</span>
                    <span class="payment-value">₱{{ number_format($bookingPayment->total_amount ?? 0, 2) }}</span>
                </div>
                <div class="payment-item">
                    <span class="payment-label">Amount Paid:</span>
                    <span class="payment-value">₱{{ number_format($bookingPayment->amount_paid ?? 0, 2) }}</span>
                </div>
                @if($bookingPayment->change > 0)
                <div class="payment-item">
                    <span class="payment-label">Change:</span>
                    <span class="payment-value">₱{{ number_format($bookingPayment->change ?? 0, 2) }}</span>
                </div>
                @endif
                @endif
            </div>

            <!-- QR Code Section -->
            <div class="qr-section">
                @if($qrCodePath)
                    <div class="note">
                        <p><strong>Important:</strong> Your QR Code is attached below to this email.</p>
                        <p>Scroll down and download the attachment (named <strong>qr-code.png</strong>). You must present this QR code at the front desk when checking out.</p>
                    </div>
                @else
                    <div class="error">
                        <p>QR code could not be generated. Please present your booking reference number at the front desk.</p>
                    </div>
                @endif
            </div>

            <!-- Account Information for New Customers -->
            @if($isNewCustomer && $generatedPassword)
            <h3 class="section-title">Account Information</h3>
            <div class="password-section">
                <div class="note">
                    <p><strong>Important:</strong> Please save this password securely. You will need it to log into your account for future bookings and to access your booking history. You can change your password once you are logged in.</p>
                    <p><strong>Login Email:</strong> {{ $customer->email }}</p>
                </div>
                <div class="password-item">
                    <div class="password-label">Your Account Password:</div>
                    <div class="password-value">{{ $generatedPassword }}</div>
                </div>
            </div>
            @endif

            <p>We look forward to serving you! If you have any questions, please contact us.</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>