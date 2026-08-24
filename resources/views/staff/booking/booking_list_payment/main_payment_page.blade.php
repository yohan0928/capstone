@extends('layouts.app')

@section('title', 'Main Payment - Booking #' . $booking->booking_ref_no)

@push('styles')
    <style>
        .change-field:disabled {
            background-color: #f3f4f6;
            color: #6b7280;
            cursor: not-allowed;
        }
        
        .breakdown-table {
            min-width: 500px;
        }
        
        .truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
            display: inline-block;
        }
        
        /* Remove spinner arrows from number input */
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        input[type="number"] {
            -moz-appearance: textfield;
        }
    </style>
@endpush

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
        <!-- Back Link -->
        <a href="{{ route('sub_two.booking_lists.showBookingList') }}" class="inline-flex items-center gap-2 text-amber-900 hover:text-amber-700 font-semibold mb-6 sm:mb-8 transition-colors duration-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Booking List
        </a>

        <!-- Payment Card -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-amber-900 to-amber-700 text-white px-4 sm:px-6 py-6 sm:py-8">
                <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold mb-2">Main Booking Payment</h1>
                        <p class="text-white/90 text-sm sm:text-base">Booking Reference: {{ $booking->booking_ref_no }}</p>
                        <p class="text-white/90 text-sm sm:text-base">Payment Category: 1 (Main Payment)</p>
                    </div>
                    <div class="md:text-right">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $formattedData['payment_status_class'] }}">
                            {{ $formattedData['payment_status_text'] }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Body -->
            <div class="p-4 sm:p-6 lg:p-8">
                @if (session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Booking Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
                    <!-- Customer Information -->
                    <div class="bg-gray-50 rounded-lg p-4 sm:p-6 border-l-4 border-amber-700">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3 sm:mb-4">Customer Information</h3>
                        <div class="space-y-2 sm:space-y-3">
                            <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                                <span class="font-semibold text-gray-700">Customer Name:</span>
                                <span class="font-semibold text-amber-700">
                                    {{ $booking->customerAccount->first_name ?? 'N/A' }}
                                    {{ $booking->customerAccount->last_name ?? '' }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                                <span class="font-semibold text-gray-700">Email:</span>
                                <span class="font-semibold text-amber-700 truncate">{{ $booking->customerAccount->email ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-gray-700">Phone:</span>
                                <span class="font-semibold text-amber-700">{{ $booking->customerAccount->contact_no ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Main Booking Details -->
                    <div class="bg-gray-50 rounded-lg p-4 sm:p-6 border-l-4 border-amber-700">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3 sm:mb-4">Main Booking Details</h3>
                        <div class="space-y-2 sm:space-y-3">
                            <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                                <span class="font-semibold text-gray-700">Branch:</span>
                                <span class="font-semibold text-amber-700">{{ $booking->branch->branch_name ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                                <span class="font-semibold text-gray-700">Category:</span>
                                <span class="font-semibold text-amber-700">{{ $booking->serviceCategory->service_category ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                                <span class="font-semibold text-gray-700">Service:</span>
                                <span class="font-semibold text-amber-700">{{ $booking->serviceName->service_name ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                                <span class="font-semibold text-gray-700">Room/Seat:</span>
                                <span class="font-semibold text-amber-700">{{ $formattedData['room_seat_text'] }}</span>
                            </div>
                            <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                                <span class="font-semibold text-gray-700">Booking Date:</span>
                                <span class="font-semibold text-amber-700">{{ $formattedData['booking_date_formatted'] }}</span>
                            </div>
                            <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                                <span class="font-semibold text-gray-700">Date Start:</span>
                                <span class="font-semibold text-amber-700">{{ $formattedData['date_start_formatted'] }}</span>
                            </div>
                            <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                                <span class="font-semibold text-gray-700">Date End:</span>
                                <span class="font-semibold text-amber-700">{{ $formattedData['date_end_formatted'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-gray-700">Time Slot:</span>
                                <span class="font-semibold text-amber-700">
                                    {{ $formattedData['start_time_formatted'] }} - {{ $formattedData['end_time_formatted'] }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Time Information -->
                    <div class="bg-gray-50 rounded-lg p-4 sm:p-6 border-l-4 border-amber-700">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3 sm:mb-4">Time Information</h3>
                        <div class="space-y-2 sm:space-y-3">
                            <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                                <span class="font-semibold text-gray-700">Original Time Used:</span>
                                <span class="font-semibold text-amber-700">
                                    <div class="flex flex-col items-end">
                                        <span class="text-sm font-semibold text-amber-700">
                                            {{ $formattedData['total_time_used_formatted'] ?? 'N/A' }}
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            {{ $totalTimeUsed }} mins
                                        </span>
                                    </div>
                                </span>
                            </div>
                            <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                                <span class="font-semibold text-gray-700">Service Package:</span>
                                <span class="font-semibold text-amber-700">
                                    @php
                                        $serviceDuration = floatval($booking->serviceName->time_duration ?? 0);
                                        if (is_numeric($serviceDuration) && $serviceDuration > 0) {
                                            echo $serviceDuration . ' hour' . ($serviceDuration != 1 ? 's' : '');
                                        } else {
                                            echo 'N/A';
                                        }
                                    @endphp
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-gray-700">Package Price:</span>
                                <span class="font-semibold text-amber-700">{{ $formattedData['service_price_formatted'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Calculation -->
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 sm:p-6 mb-6 sm:mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Main Payment Calculation</h3>

                    <!-- Payment Breakdown Table -->
                    <div class="overflow-x-auto mb-4 rounded-lg shadow-sm">
                        <table class="w-full bg-white break-down-table">
                            <thead>
                                <tr>
                                    <th class="bg-amber-700 text-white px-4 py-3 text-left text-sm font-semibold whitespace-nowrap">Description</th>
                                    <th class="bg-amber-700 text-white px-4 py-3 text-left text-sm font-semibold whitespace-nowrap">Details</th>
                                    <th class="bg-amber-700 text-white px-4 py-3 text-left text-sm font-semibold whitespace-nowrap">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Package Price -->
                                <tr class="hover:bg-amber-50 transition-colors duration-150">
                                    <td class="px-4 py-3 border-b border-gray-200 text-sm">Service Package</td>
                                    <td class="px-4 py-3 border-b border-gray-200 text-sm">
                                        <div class="flex flex-col items-start">
                                            <span class="text-sm font-semibold text-amber-700">
                                                @php
                                                    $serviceDuration = floatval($booking->serviceName->time_duration ?? 0);
                                                    echo $serviceDuration . ' hour' . ($serviceDuration != 1 ? 's' : '');
                                                @endphp
                                            </span>
                                            <span class="text-xs text-gray-500">
                                                @php
                                                    $serviceDuration = floatval($booking->serviceName->time_duration ?? 0);
                                                    $packageMinutes = $serviceDuration * 60;
                                                    if (is_numeric($packageMinutes) && $packageMinutes >= 0) {
                                                        echo $packageMinutes . ' mins';
                                                    } else {
                                                        echo '0 mins';
                                                    }
                                                @endphp
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 border-b border-gray-200 text-sm">{{ $formattedData['service_price_formatted'] }}</td>
                                </tr>

                                <!-- Extra Time if applicable -->
                                @php
                                    $serviceDuration = floatval($booking->serviceName->time_duration ?? 1);
                                    $servicePrice = floatval($booking->serviceName->price ?? 0);
                                    $packageMinutes = $serviceDuration * 60;
                                    $totalTimeUsedNumeric = is_numeric($totalTimeUsed) ? floatval($totalTimeUsed) : 0;
                                    $extraMinutes = max(0, $totalTimeUsedNumeric - $packageMinutes);

                                    $hourlyRate = 0;
                                    $fifteenMinRate = 0;

                                    if ($serviceDuration > 0 && $servicePrice > 0) {
                                        $hourlyRate = $servicePrice / $serviceDuration;
                                        $fifteenMinRate = $hourlyRate / 4;
                                    }

                                    $extraAmount = 0;
                                    if ($extraMinutes > 0 && $fifteenMinRate > 0) {
                                        $extraSegments = ceil($extraMinutes / 15);
                                        $extraAmount = $extraSegments * $fifteenMinRate;
                                    }

                                    $totalCombined = $servicePrice + $extraAmount;
                                @endphp

                                @if ($extraMinutes > 0)
                                    <tr class="hover:bg-amber-50 transition-colors duration-150">
                                        <td class="px-4 py-3 border-b border-gray-200 text-sm">Extra Time (15-min increments)</td>
                                        <td class="px-4 py-3 border-b border-gray-200 text-sm">
                                            <div class="flex flex-col items-start">
                                                <span class="text-sm font-semibold text-amber-700">
                                                    {{ $extraMinutes }} min{{ $extraMinutes !== 1 ? 's' : '' }}
                                                </span>
                                                <span class="text-xs text-gray-500">
                                                    beyond package time
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 border-b border-gray-200 text-sm">₱{{ number_format($extraAmount, 2) }}</td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot>
                                <tr class="bg-amber-50 font-semibold border-t-2 border-amber-700">
                                    <td colspan="2" class="px-4 py-4 text-sm"><strong>Total Amount Due</strong></td>
                                    <td class="px-4 py-4 text-base">
                                        <strong>₱{{ number_format($totalCombined, 2) }}</strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Rate Information -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
                        <p class="text-sm text-blue-900">
                            <strong>Rate Calculation:</strong><br>
                            Package: {{ $formattedData['service_price_formatted'] }} for {{ $booking->serviceName->time_duration ?? 0 }}<br>
                            @php
                                $serviceDuration = floatval($booking->serviceName->time_duration ?? 1);
                                $servicePrice = floatval($booking->serviceName->price ?? 0);

                                if ($serviceDuration > 0 && $servicePrice > 0) {
                                    $hourlyRate = $servicePrice / $serviceDuration;
                                    $fifteenMinRate = $hourlyRate / 4;
                                    echo 'Hourly Rate: <strong>₱' . number_format($hourlyRate, 2) . '/hour</strong><br>';
                                    echo '15-min Rate: <strong>₱' . number_format($fifteenMinRate, 2) . '/15 minutes</strong>';
                                } else {
                                    echo 'Rate calculation not available';
                                }
                            @endphp
                        </p>
                    </div>
                </div>

                <!-- Unpaid Orders Section -->
                @if($hasUnpaidOrders)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 sm:p-6 mb-6 sm:mb-8">
                    <h3 class="text-lg font-semibold text-blue-800 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        Unpaid Orders (Pay Later)
                    </h3>
                    
                    <div class="space-y-4">
                        <!-- Orders List -->
                        <div class="space-y-3">
                            @foreach($unpaidOrders as $order)
                            <div class="bg-white border border-blue-200 rounded-lg p-4">
                                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-3 mb-3">
                                    <div>
                                        <h4 class="font-semibold text-gray-900">Order #{{ $order->order_ref_no }}</h4>
                                        <p class="text-sm text-gray-600">Date: {{ \Carbon\Carbon::parse($order->order_date)->format('M j, Y') }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Pay Later - Unpaid
                                        </span>
                                        <p class="text-lg font-bold text-blue-700 mt-1">
                                            ₱{{ number_format($order->payments->first()->total_amount ?? 0, 2) }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Order Items -->
                                <div class="border-t border-gray-200 pt-3">
                                    <h5 class="text-sm font-semibold text-gray-700 mb-2">Items:</h5>
                                    <div class="space-y-2">
                                        @foreach($order->items as $item)
                                        <div class="flex justify-between items-center text-sm">
                                            <div class="flex items-center gap-2">
                                                <span class="text-gray-600">
                                                    {{ $item->product->product_name ?? 'N/A' }}
                                                </span>
                                                <span class="text-gray-400">×</span>
                                                <span class="text-gray-600">{{ $item->quantity }}</span>
                                            </div>
                                            <span class="font-medium text-gray-900">
                                                ₱{{ number_format($item->sub_total, 2) }}
                                            </span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <!-- Summary -->
                        <div class="bg-blue-100 border border-blue-300 rounded-lg p-4 mt-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center md:text-left">
                                <div>
                                    <p class="text-sm text-blue-700">Unpaid Orders</p>
                                    <p class="text-lg font-semibold text-blue-900">{{ $unpaidOrders->count() }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-blue-700">Orders Total</p>
                                    <p class="text-lg font-semibold text-blue-900">{{ $formattedData['total_unpaid_orders_amount_formatted'] }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-blue-700">Service + Orders Total</p>
                                    <p class="text-xl font-bold text-blue-900">{{ $formattedData['grand_total_formatted'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Payment Form -->
                @if (!$mainPayment || $mainPayment->payment_status != 1)
                    <div class="bg-gray-50 rounded-lg p-4 sm:p-6 lg:p-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 sm:mb-6">
                            {{ $mainPayment ? 'Update Payment' : 'Process Payment' }}
                        </h3>

                        <form method="POST" action="{{ route('sub_two.booking_lists.updateMainPayment') }}" id="paymentForm">
                            @csrf
                            <input type="hidden" name="booking_id" value="{{ $booking->id }}">

                            <!-- Responsive Grid Layout -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                                <!-- Payment Method Column -->
                                <div class="space-y-4 sm:space-y-6">
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-2">Payment Method</label>
                                        <select name="payment_method" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors duration-200" required id="paymentMethod">
                                            <option value="cash" {{ old('payment_method', $mainPayment->payment_method ?? '') === 'cash' ? 'selected' : '' }}>Cash</option>
                                            <option value="gcash" {{ old('payment_method', $mainPayment->payment_method ?? '') === 'gcash' ? 'selected' : '' }}>GCash</option>
                                            <option value="debit" {{ old('payment_method', $mainPayment->payment_method ?? '') === 'debit' ? 'selected' : '' }}>Debit</option>
                                        </select>
                                    </div>

                                    <!-- Notes Field (for GCash & Debit) -->
                                    <div id="notesField" class="hidden">
                                        <label class="block font-semibold text-gray-700 mb-2">Payment Notes</label>
                                        <textarea name="note" rows="3" maxlength="1000" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors duration-200"
                                            placeholder="Add payment notes (e.g., reference number, transaction details)...">{{ old('note', '') }}</textarea>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <span id="noteCharCount">0</span>/1000 characters
                                        </p>
                                    </div>
                                </div>

                                <!-- Amount Paid Column -->
                                <div class="space-y-4 sm:space-y-6">
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-2">Amount Paid</label>
                                        <input type="number" name="amount_paid" step="0.01" 
                                               min="{{ $hasUnpaidOrders ? $grandTotal : $totalCombined }}" 
                                               value="{{ $hasUnpaidOrders ? $grandTotal : $totalCombined }}" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors duration-200" 
                                               placeholder="Enter amount paid" required id="amountPaid" onkeypress="return isNumberKey(event)" oninput="validateNumberInput(this)">
                                        <p class="text-sm text-gray-500 mt-2">
                                            @if($hasUnpaidOrders)
                                                Total due (Service + Orders): {{ $formattedData['grand_total_formatted'] }}
                                            @else
                                                Total due: {{ $formattedData['calculated_main_amount_formatted'] }}
                                            @endif
                                        </p>
                                        <!-- Warning Message -->
                                        <div id="amountWarning" class="mt-2 hidden">
                                            <div class="flex items-center gap-2 p-3 bg-red-50 border border-red-200 rounded-lg">
                                                <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <p class="text-sm text-red-700" id="warningMessage"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Change Display Column -->
                                <div class="space-y-4 sm:space-y-6">
                                    <div id="changeDisplay" class="hidden">
                                        <label class="block font-semibold text-gray-700 mb-2">Change</label>
                                        <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg change-field bg-green-50" 
                                            id="changeAmount" value="₱0.00" disabled>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 justify-end mt-6 sm:mt-8">
                                <a href="{{ route('sub_two.booking_lists.showBookingList') }}" 
                                   class="inline-flex items-center justify-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition-colors duration-200 order-2 sm:order-1">
                                    Cancel
                                </a>
                                <button type="submit" id="submitButton"
                                        class="inline-flex items-center justify-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors duration-200 order-1 sm:order-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ $mainPayment ? 'Update Payment' : 'Process Payment' }}
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <!-- Payment Already Processed -->
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 sm:p-6">
                        <h3 class="text-lg font-semibold text-green-800 mb-4">Payment Already Processed</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center pb-3 border-b border-green-200">
                                <span class="font-semibold text-gray-700">Payment Status:</span>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    Paid
                                </span>
                            </div>
                            <div class="flex justify-between items-center pb-3 border-b border-green-200">
                                <span class="font-semibold text-gray-700">Payment Method:</span>
                                <span class="font-semibold text-amber-700">{{ $formattedData['payment_method_text'] }}</span>
                            </div>
                            <div class="flex justify-between items-center pb-3 border-b border-green-200">
                                <span class="font-semibold text-gray-700">Amount Paid:</span>
                                <span class="font-semibold text-amber-700">{{ $formattedData['amount_paid_formatted'] }}</span>
                            </div>
                            @if ($mainPayment->payment_method === 0)
                                <div class="flex justify-between items-center pb-3 border-b border-green-200">
                                    <span class="font-semibold text-gray-700">Change:</span>
                                    <span class="font-semibold text-amber-700">{{ $formattedData['change_formatted'] }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-gray-700">Payment Date:</span>
                                <span class="font-semibold text-amber-700">{{ $formattedData['payment_date_formatted'] }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const paymentMethod = document.getElementById('paymentMethod');
        const amountPaid = document.getElementById('amountPaid');
        const changeDisplay = document.getElementById('changeDisplay');
        const changeAmount = document.getElementById('changeAmount');
        const notesField = document.getElementById('notesField');
        const noteTextarea = document.querySelector('textarea[name="note"]');
        const noteCharCount = document.getElementById('noteCharCount');
        const submitButton = document.getElementById('submitButton');
        const amountWarning = document.getElementById('amountWarning');
        const warningMessage = document.getElementById('warningMessage');
        const totalDue = {{ $hasUnpaidOrders ? $grandTotal : $totalCombined }};

        // Initialize character count
        updateCharCount();

        // Character count for notes
        if (noteTextarea) {
            noteTextarea.addEventListener('input', updateCharCount);
        }

        function updateCharCount() {
            if (noteTextarea && noteCharCount) {
                const length = noteTextarea.value.length;
                noteCharCount.textContent = length;
                
                if (length > 900) {
                    noteCharCount.classList.add('text-red-600');
                    noteCharCount.classList.remove('text-yellow-600', 'text-gray-500');
                } else if (length > 700) {
                    noteCharCount.classList.add('text-yellow-600');
                    noteCharCount.classList.remove('text-red-600', 'text-gray-500');
                } else {
                    noteCharCount.classList.add('text-gray-500');
                    noteCharCount.classList.remove('text-red-600', 'text-yellow-600');
                }
            }
        }

        function validatePayment() {
            const paidAmount = parseFloat(amountPaid.value) || 0;
            const method = paymentMethod.value;
            const change = Math.max(0, paidAmount - totalDue);
            const isShort = paidAmount < totalDue;
            const shortAmount = totalDue - paidAmount;

            // Update change display
            changeAmount.value = '₱' + change.toFixed(2);

            // Show/hide change display based on payment method
            if (method === 'cash') {
                changeDisplay.classList.remove('hidden');
                changeDisplay.classList.add('block');
                notesField.classList.remove('block');
                notesField.classList.add('hidden');
            } else {
                changeDisplay.classList.remove('block');
                changeDisplay.classList.add('hidden');
                notesField.classList.remove('hidden');
                notesField.classList.add('block');
            }

            // Show warning if amount is insufficient
            if (isShort) {
                amountWarning.classList.remove('hidden');
                warningMessage.innerHTML = `⚠️ Insufficient amount: You entered ₱${paidAmount.toFixed(2)} but the total due is ₱${totalDue.toFixed(2)}. Short by ₱${shortAmount.toFixed(2)}.`;
                
                // Add red border to input
                amountPaid.classList.add('border-red-500', 'ring-1', 'ring-red-500');
                amountPaid.classList.remove('border-gray-300');
            } else {
                amountWarning.classList.add('hidden');
                warningMessage.innerHTML = '';
                
                // Remove red border
                amountPaid.classList.remove('border-red-500', 'ring-1', 'ring-red-500');
                amountPaid.classList.add('border-gray-300');
            }

            // Enable/disable submit button based on validation
            let isValid = true;
            
            if (!amountPaid.value.trim()) {
                isValid = false;
            } else if (method === 'cash' && paidAmount < totalDue) {
                isValid = false;
            } else if (method !== 'cash' && Math.abs(paidAmount - totalDue) > 0.01) {
                // For non-cash methods, auto-set to exact amount
                amountPaid.value = totalDue.toFixed(2);
                isValid = true;
            } else {
                isValid = true;
            }

            // Disable submit button if not valid
            if (submitButton) {
                submitButton.disabled = !isValid;
                if (!isValid) {
                    submitButton.classList.add('opacity-50', 'cursor-not-allowed');
                    submitButton.classList.remove('hover:bg-green-700');
                } else {
                    submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
                    submitButton.classList.add('hover:bg-green-700');
                }
            }

            return isValid;
        }

        // Event listeners
        paymentMethod.addEventListener('change', function() {
            if (this.value !== 'cash') {
                // Auto-set amount to total due for non-cash methods
                amountPaid.value = totalDue.toFixed(2);
                amountPaid.min = totalDue;
                amountPaid.max = totalDue;
            } else {
                amountPaid.min = totalDue;
                amountPaid.max = '';
            }
            validatePayment();
        });

        amountPaid.addEventListener('input', validatePayment);
        amountPaid.addEventListener('change', validatePayment);
        amountPaid.addEventListener('blur', validatePayment);

        // Initial validation and setup
        if (paymentMethod.value !== 'cash') {
            amountPaid.min = totalDue;
            amountPaid.max = totalDue;
            notesField.classList.remove('hidden');
            notesField.classList.add('block');
        } else {
            changeDisplay.classList.remove('hidden');
            changeDisplay.classList.add('block');
        }
        validatePayment();
    });

    // Prevent typing non-numeric characters
    function isNumberKey(evt) {
        var charCode = (evt.which) ? evt.which : evt.keyCode;
        if (charCode > 31 && (charCode < 48 || charCode > 57) && charCode !== 46) {
            evt.preventDefault();
            return false;
        }
        return true;
    }

    // Validate number input
    function validateNumberInput(input) {
        // Remove any non-numeric characters except decimal point
        input.value = input.value.replace(/[^0-9.]/g, '');
        
        // Ensure only one decimal point
        const decimalCount = (input.value.match(/\./g) || []).length;
        if (decimalCount > 1) {
            input.value = input.value.substring(0, input.value.lastIndexOf('.'));
        }
        
        // Limit to 2 decimal places
        if (input.value.includes('.')) {
            const parts = input.value.split('.');
            if (parts[1].length > 2) {
                parts[1] = parts[1].substring(0, 2);
                input.value = parts.join('.');
            }
        }
        
        // Trigger validation
        if (typeof validatePayment === 'function') {
            validatePayment();
        }
    }
    </script>
@endsection