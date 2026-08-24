<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - {{ $bookingDetails['service_name']->service_name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        blue: {
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        },
                        green: {
                            600: '#16a34a',
                            700: '#15803d',
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-blue-600 text-white p-6 text-center">
                <i class="fas fa-credit-card text-4xl mb-3"></i>
                <h1 class="text-2xl font-bold">Complete Your Payment</h1>
                <p class="text-blue-100">Secure payment for your booking</p>
            </div>

            <div class="p-6">
                <!-- Booking Summary -->
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Booking Summary</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Service:</span>
                            <span class="font-medium">{{ $bookingDetails['service_name']->service_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Branch:</span>
                            <span class="font-medium">{{ $bookingDetails['branch']->branch_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Date & Time:</span>
                            <span class="font-medium">
                                {{ \Carbon\Carbon::parse($bookingDetails['date_from'])->format('M j') }} -
                                {{ \Carbon\Carbon::parse($bookingDetails['date_to'])->format('M j, Y') }} at
                                {{ \Carbon\Carbon::parse($bookingDetails['booking_time'])->format('h:i A') }} -
                                {{ \Carbon\Carbon::parse($bookingDetails['end_time'])->format('h:i A') }}
                            </span>
                        </div>
                        <div class="flex justify-between border-t border-gray-200 pt-2">
                            <span class="text-lg font-semibold text-gray-800">Total Amount:</span>
                            <span
                                class="text-lg font-bold text-green-600">${{ number_format($bookingDetails['total_price'], 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Form -->
                <form action="{{ route('sub_three.home.processPayment') }}" method="POST" enctype="multipart/form-data"
                    class="space-y-6">
                    @csrf
                    <input type="hidden" name="booking_details"
                        value="{{ base64_encode(json_encode($bookingDetails)) }}">

                    <!-- Payment Method -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-mobile-alt text-green-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-800">GCash</h4>
                                    <p class="text-sm text-gray-600">Pay using your GCash account</p>
                                </div>
                                <input type="radio" name="payment_method" value="2" checked class="ml-auto">
                            </div>
                        </div>
                    </div>

                    <!-- GCash Reference Number -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">GCash Reference Number</label>
                        <input type="text" name="gcash_ref_no" required placeholder="Enter GCash reference number"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-sm text-gray-500 mt-1">Find this in your GCash transaction history</p>
                    </div>

                    <!-- GCash Receipt Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">GCash Receipt Screenshot</label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                            <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-3"></i>
                            <p class="text-gray-600 mb-2">Upload screenshot of your GCash payment</p>
                            <input type="file" name="gcash_receipt_img" accept="image/*" required
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <p class="text-xs text-gray-500 mt-2">PNG, JPG, GIF up to 2MB</p>
                        </div>
                    </div>

                    <!-- Payment Instructions -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <h4 class="font-semibold text-yellow-800 mb-2 flex items-center">
                            <i class="fas fa-info-circle mr-2"></i>
                            GCash Payment Instructions
                        </h4>

                        <!-- NEW: GCash QR Code Display -->
                        <div class="my-4 text-center">
                            @if(isset($staffGcashQrCode) && $staffGcashQrCode)
                                <p class="text-sm text-yellow-700 mb-2">Scan the QR code below:</p>
                                <img src="{{ $staffGcashQrCode }}" alt="GCash QR Code" class="mx-auto w-48 h-48 rounded-lg border border-gray-300 object-contain">
                                
                                <!-- NEWLY ADDED BUTTON -->
                                <a href="{{ $staffGcashQrCode }}" download="gcash-qr-code.png" class="mt-4 inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg shadow-sm transition duration-300">
                                    <i class="fas fa-download mr-2"></i>
                                    Download QR
                                </a>
                                <!-- END OF NEWLY ADDED BUTTON -->
                            @else
                                <p class="text-sm text-yellow-700 mb-2">Pay via mobile number:</p>
                            @endif
                        </div>
                        <!-- END NEW -->

                        <ol class="text-yellow-700 text-sm space-y-1 list-decimal list-inside">
                            <li>Open your GCash app</li>
                            <li>Go to "Send Money" @if(isset($staffGcashQrCode) && $staffGcashQrCode) or "Scan QR" @endif</li>
                            <li>Enter mobile number: <strong>09084557940</strong> @if(isset($staffGcashQrCode) && $staffGcashQrCode) (or scan QR above) @endif</li>
                            <li>Amount: <strong>${{ number_format($bookingDetails['total_price'], 2) }}</strong></li>
                            <li>Complete the transaction and take a screenshot</li>
                            <li>Upload the screenshot and enter reference number above</li>
                        </ol>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col md:flex-row justify-between space-y-4 md:space-y-0 md:space-x-4 pt-6">
                        <a href="{{ route('sub_three.home.preview.page') }}"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold transition duration-300 text-center flex items-center justify-center">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Preview
                        </a>

                        <button type="submit"
                            class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg font-semibold transition duration-300 flex items-center justify-center">
                            <i class="fas fa-lock mr-2"></i>
                            Complete Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // File upload preview
        document.querySelector('input[type="file"]').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                const label = this.previousElementSibling;
                label.querySelector('p').textContent = `Selected: ${fileName}`;
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const fileInput = document.querySelector('input[type="file"]');
            const refNoInput = document.querySelector('input[name="gcash_ref_no"]');

            if (!refNoInput.value.trim()) {
                e.preventDefault();
                alert('Please enter GCash reference number');
                refNoInput.focus();
                return false;
            }

            if (!fileInput.files.length) {
                e.preventDefault();
                alert('Please upload GCash receipt screenshot');
                return false;
            }
        });
    </script>
</body>

</html>