@extends('layouts.app')

@section('title', 'Booking Details')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <!-- Header with Back Button -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Booking Details</h1>
                <p class="text-gray-600 mt-1">Reference: {{ $booking->booking_ref_no }}</p>
            </div>
            <a href="{{ route('sub_two.booking_lists.showBookingList') }}"
                class="inline-flex items-center px-4 py-2 bg-amber-900 text-white rounded-md hover:bg-amber-800 transition-colors">
                Back
            </a>
        </div>

        <div class="space-y-6">
            <!-- Customer Information - NO ACCORDION -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Customer Information</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[800px]">
                        <tbody class="divide-y divide-gray-200">
                            <tr>
                                <td class="w-1/4 px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Full Name</td>
                                <td colspan="3" class="px-6 py-4 text-sm text-gray-600">
                                    {{ ($booking->customerAccount->first_name ?? 'N/A') . ' ' . ($booking->customerAccount->last_name ?? '') }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Email</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $booking->customerAccount->email ?? 'N/A' }}</td>
                                <td class="px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Phone</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $booking->customerAccount->contact_no ?? 'N/A' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Time Information - WITH ACCORDION -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center cursor-pointer accordion-toggle"
                    data-target="time-info-accordion">
                    <h2 class="text-lg font-semibold text-gray-900">Time Information</h2>
                    <svg class="w-5 h-5 text-gray-500 transition-transform accordion-arrow" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
                <div id="time-info-accordion" class="accordion-content overflow-hidden transition-all duration-300 ease-in-out"
                    style="max-height: 500px">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[800px]">
                            <tbody class="divide-y divide-gray-200">
                                <tr>
                                    <td class="w-1/4 px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Date Start</td>
                                    <td class="w-1/4 px-6 py-4 text-sm text-gray-600">
                                        {{ $formattedData['date_start_formatted'] ?? 'N/A' }}</td>
                                    <td class="w-1/4 px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Date End</td>
                                    <td class="w-1/4 px-6 py-4 text-sm text-gray-600">
                                        {{ $formattedData['date_end_formatted'] ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Start Time</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $formattedData['start_time_formatted'] ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">End Time</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $formattedData['end_time_formatted'] ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Time Duration</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $formattedData['duration_formatted'] ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Extended Time</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        @if ($formattedData['extended_duration_formatted'] && $formattedData['extended_duration_formatted'] !== '0 min')
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                {{ $formattedData['extended_duration_formatted'] }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">None</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Total Time Duration</td>
                                    <td colspan="3" class="px-6 py-4 text-sm text-gray-600">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ $formattedData['total_duration_formatted'] ?? ($formattedData['duration_formatted'] ?? 'N/A') }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Extended Time Information - WITH ACCORDION -->
            @if ($booking->extended_start_time && $booking->extended_end_time)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center cursor-pointer accordion-toggle"
                        data-target="extended-time-accordion">
                        <h2 class="text-lg font-semibold text-gray-900">Extended Time Information</h2>
                        <svg class="w-5 h-5 text-gray-500 transition-transform accordion-arrow" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                    <div id="extended-time-accordion" class="accordion-content overflow-hidden transition-all duration-300 ease-in-out"
                        style="max-height: 500px">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[800px]">
                                <tbody class="divide-y divide-gray-200">
                                    <tr>
                                        <td class="w-1/4 px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Extended Date Start</td>
                                        <td class="w-1/4 px-6 py-4 text-sm text-gray-600">
                                            {{ $formattedData['extended_date_start_formatted'] ?? 'N/A' }}</td>
                                        <td class="w-1/4 px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Extended Date End</td>
                                        <td class="w-1/4 px-6 py-4 text-sm text-gray-600">
                                            {{ $formattedData['extended_date_end_formatted'] ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Extended Start Time</td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            {{ $formattedData['extended_start_time_formatted'] ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Extended End Time</td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            {{ $formattedData['extended_end_time_formatted'] ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Extended Time Duration</td>
                                        <td colspan="3" class="px-6 py-4 text-sm text-gray-600">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                {{ $formattedData['extended_duration_formatted'] ?? 'N/A' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Main Payment Information - WITH ACCORDION -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-blue-50 px-6 py-4 border-b border-blue-200 flex justify-between items-center cursor-pointer accordion-toggle"
                    data-target="main-payment-accordion">
                    <h2 class="text-lg font-semibold text-blue-900 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Main Service Payment
                    </h2>
                    <svg class="w-5 h-5 text-blue-700 transition-transform accordion-arrow" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
                <div id="main-payment-accordion" class="accordion-content overflow-hidden transition-all duration-300 ease-in-out"
                    style="max-height: 500px">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[800px]">
                            <tbody class="divide-y divide-gray-200">
                                <tr>
                                    <td class="w-1/4 px-6 py-4 bg-blue-50 text-sm font-medium text-blue-900">Payment Status</td>
                                    <td class="w-1/4 px-6 py-4 text-sm text-gray-600">
                                        @php
                                            $mainPaymentStatusClass = match ($formattedData['main_payment_status_class'] ?? '') {
                                                'status-paid' => 'bg-green-100 text-green-800',
                                                'status-unpaid' => 'bg-yellow-100 text-yellow-800',
                                                'status-invalid' => 'bg-red-100 text-red-800',
                                                default => 'bg-yellow-100 text-yellow-800',
                                            };
                                        @endphp
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $mainPaymentStatusClass }}">
                                            {{ $formattedData['main_payment_status_text'] ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="w-1/4 px-6 py-4 bg-blue-50 text-sm font-medium text-blue-900">Payment Method</td>
                                    <td class="w-1/4 px-6 py-4 text-sm text-gray-600">
                                        {{ $formattedData['main_payment_method_text'] ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 bg-blue-50 text-sm font-medium text-blue-900">Total Amount</td>
                                    <td class="px-6 py-4 text-sm text-gray-600 font-semibold">
                                        {{ $formattedData['main_total_amount_formatted'] ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 bg-blue-50 text-sm font-medium text-blue-900">Amount Paid</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $formattedData['main_amount_paid_formatted'] ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 bg-blue-50 text-sm font-medium text-blue-900">Change</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $formattedData['main_change_formatted'] ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 bg-blue-50 text-sm font-medium text-blue-900">Payment Date</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $formattedData['main_payment_date_formatted'] ?? 'N/A' }}</td>
                                </tr>
                                @if ($mainPayment && $mainPayment->payment_method == 1)
                                    {{-- GCash --}}
                                    <tr>
                                        <td class="px-6 py-4 bg-blue-50 text-sm font-medium text-blue-900">GCash Reference No</td>
                                        <td colspan="3" class="px-6 py-4 text-sm text-gray-600">
                                            {{ $mainPayment->gcash_ref_no ?? 'N/A' }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Extension Payment Information - WITH ACCORDION -->
            @if ($extensionPayment)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-purple-50 px-6 py-4 border-b border-purple-200 flex justify-between items-center cursor-pointer accordion-toggle"
                        data-target="extension-payment-accordion">
                        <h2 class="text-lg font-semibold text-purple-900 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Time Extension Payment
                        </h2>
                        <svg class="w-5 h-5 text-purple-700 transition-transform accordion-arrow" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                    <div id="extension-payment-accordion" class="accordion-content overflow-hidden transition-all duration-300 ease-in-out"
                        style="max-height: 500px">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[800px]">
                                <tbody class="divide-y divide-gray-200">
                                    <tr>
                                        <td class="w-1/4 px-6 py-4 bg-purple-50 text-sm font-medium text-purple-900">Payment Status</td>
                                        <td class="w-1/4 px-6 py-4 text-sm text-gray-600">
                                            @php
                                                $extensionPaymentStatusClass = match ($formattedData['extension_payment_status_class'] ?? '') {
                                                    'status-paid' => 'bg-green-100 text-green-800',
                                                    'status-unpaid' => 'bg-yellow-100 text-yellow-800',
                                                    'status-invalid' => 'bg-red-100 text-red-800',
                                                    default => 'bg-yellow-100 text-yellow-800',
                                                };
                                            @endphp
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $extensionPaymentStatusClass }}">
                                                {{ $formattedData['extension_payment_status_text'] ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="w-1/4 px-6 py-4 bg-purple-50 text-sm font-medium text-purple-900">Payment Method</td>
                                        <td class="w-1/4 px-6 py-4 text-sm text-gray-600">
                                            {{ $formattedData['extension_payment_method_text'] ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="px-6 py-4 bg-purple-50 text-sm font-medium text-purple-900">Total Amount</td>
                                        <td class="px-6 py-4 text-sm text-gray-600 font-semibold">
                                            {{ $formattedData['extension_total_amount_formatted'] ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 bg-purple-50 text-sm font-medium text-purple-900">Amount Paid</td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            {{ $formattedData['extension_amount_paid_formatted'] ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="px-6 py-4 bg-purple-50 text-sm font-medium text-purple-900">Change</td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            {{ $formattedData['extension_change_formatted'] ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 bg-purple-50 text-sm font-medium text-purple-900">Payment Date</td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            {{ $formattedData['extension_payment_date_formatted'] ?? 'N/A' }}</td>
                                    </tr>
                                    @if ($extensionPayment->payment_method == 1)
                                        {{-- GCash --}}
                                        <tr>
                                            <td class="px-6 py-4 bg-purple-50 text-sm font-medium text-purple-900">GCash Reference No</td>
                                            <td colspan="3" class="px-6 py-4 text-sm text-gray-600">
                                                {{ $extensionPayment->gcash_ref_no ?? 'N/A' }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Orders Section - WITH ACCORDION -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center cursor-pointer accordion-toggle"
                    data-target="orders-accordion">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <span>Orders ({{ $allOrders->count() }} total)</span>
                        @if ($hasUnpaidOrders)
                            <span
                                class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                {{ $unpaidOrders->count() }} Unpaid
                            </span>
                        @endif
                    </h2>
                    <svg class="w-5 h-5 text-gray-500 transition-transform accordion-arrow" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
                <div id="orders-accordion" class="accordion-content overflow-hidden transition-all duration-300 ease-in-out">
                    <div class="p-6">
                        <!-- Paid Orders -->
                        @if ($hasPaidOrders)
                            <div class="mb-8">
                                <h3 class="flex items-center gap-2 text-lg font-semibold text-green-700 mb-4">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Paid Orders ({{ $paidOrders->count() }})
                                </h3>

                                <div class="overflow-x-auto">
                                    <table class="w-full min-w-[1000px] bg-white rounded-lg shadow-sm border border-gray-200">
                                        <thead>
                                            <tr class="bg-gray-50 border-b border-gray-200">
                                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 whitespace-nowrap">
                                                    Order Details</th>
                                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 whitespace-nowrap">
                                                    Payment Details</th>
                                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 whitespace-nowrap">
                                                    Items</th>
                                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 whitespace-nowrap">
                                                    Qty</th>
                                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 whitespace-nowrap">
                                                    Price</th>
                                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 whitespace-nowrap">
                                                    Amount Paid</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @foreach ($paidOrders as $order)
                                                @php
                                                    $orderTotalAmount = $order->payments
                                                        ->where('order_payment_status', 1)
                                                        ->sum('total_amount');
                                                @endphp
                                                @foreach ($order->items as $index => $item)
                                                    <tr>
                                                        @if ($index === 0)
                                                            <td rowspan="{{ count($order->items) }}" class="px-4 py-3 align-top">
                                                                <div class="font-semibold text-gray-900 text-sm">ORN:
                                                                    {{ $order->order_ref_no }}</div>
                                                                <div class="text-xs text-gray-500 mt-1">
                                                                    {{ \Carbon\Carbon::parse($order->order_date)->format('M j, Y') }}
                                                                </div>
                                                            </td>
                                                            <td rowspan="{{ count($order->items) }}" class="px-4 py-3 align-top">
                                                                @foreach ($order->payments->where('order_payment_status', 1) as $payment)
                                                                    <div
                                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mb-2">
                                                                        Paid
                                                                    </div>
                                                                    <div class="text-sm text-gray-600">
                                                                        @switch($payment->payment_method)
                                                                            @case(0)
                                                                                Cash
                                                                            @break

                                                                            @case(1)
                                                                                GCash
                                                                            @break

                                                                            @case(2)
                                                                                Debit
                                                                            @break

                                                                            @case(3)
                                                                                Pay Later
                                                                            @break

                                                                            @default
                                                                                Unknown
                                                                        @endswitch
                                                                    </div>
                                                                    <div class="text-xs text-gray-400 mt-1">
                                                                        {{ \Carbon\Carbon::parse($payment->payment_date)->format('M j, Y g:i A') }}
                                                                    </div>
                                                                @endforeach
                                                            </td>
                                                        @endif
                                                        <td class="px-4 py-3">
                                                            <div class="text-sm text-gray-900">
                                                                {{ $item->product->product_name ?? 'N/A' }}
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <div class="text-sm text-gray-600 text-center">
                                                                {{ $item->quantity }}
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <div class="text-sm text-gray-900 text-right">
                                                                ₱{{ number_format($item->sub_total / $item->quantity, 2) }}
                                                            </div>
                                                        </td>
                                                        @if ($index === 0)
                                                            <td rowspan="{{ count($order->items) }}" class="px-4 py-3 align-top">
                                                                <div class="text-sm font-semibold text-green-600 text-right">
                                                                    ₱{{ number_format($orderTotalAmount, 2) }}
                                                                </div>
                                                            </td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="bg-gray-50 border-t-2 border-gray-200">
                                                <td colspan="5" class="px-4 py-3 text-sm font-semibold text-gray-900 text-right">Total Amount Paid:</td>
                                                <td class="px-4 py-3 text-base font-bold text-gray-900 text-right">
                                                    ₱{{ number_format(
                                                        $paidOrders->sum(function ($order) {
                                                            return $order->payments->where('order_payment_status', 1)->sum('total_amount');
                                                        }),
                                                        2,
                                                    ) }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        @endif

                        <!-- Unpaid Orders -->
                        @if ($hasUnpaidOrders)
                            <div class="mb-8">
                                <h3 class="text-lg font-semibold text-red-700 mb-4">
                                    Unpaid Orders ({{ $unpaidOrders->count() }})
                                </h3>

                                <div class="overflow-x-auto">
                                    <table class="w-full min-w-[1000px] bg-white rounded-lg shadow-sm border border-gray-200">
                                        <thead>
                                            <tr class="bg-gray-50 border-b border-gray-200">
                                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 whitespace-nowrap">
                                                    Order Details</th>
                                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 whitespace-nowrap">
                                                    Payment Details</th>
                                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 whitespace-nowrap">
                                                    Items</th>
                                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 whitespace-nowrap">
                                                    Qty</th>
                                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 whitespace-nowrap">
                                                    Price</th>
                                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 whitespace-nowrap">
                                                    Amount Due</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @foreach ($unpaidOrders as $order)
                                                @php
                                                    $orderTotalAmount =
                                                        $order->payments->count() > 0
                                                            ? $order->payments
                                                                ->where('payment_method', 3)
                                                                ->where('order_payment_status', 0)
                                                                ->sum('total_amount')
                                                            : $order->items->sum('sub_total');
                                                @endphp
                                                @foreach ($order->items as $index => $item)
                                                    <tr>
                                                        @if ($index === 0)
                                                            <td rowspan="{{ count($order->items) }}" class="px-4 py-3 align-top">
                                                                <div
                                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 mb-2">
                                                                    Pay Later - Unpaid
                                                                </div>
                                                                <div class="font-semibold text-gray-900 text-sm">ORN:
                                                                    {{ $order->order_ref_no }}</div>
                                                                <div class="text-xs text-gray-500 mt-1">
                                                                    {{ \Carbon\Carbon::parse($order->order_date)->format('M j, Y') }}
                                                                </div>
                                                            </td>
                                                            <td rowspan="{{ count($order->items) }}" class="px-4 py-3 align-top">
                                                                @if ($order->payments->count() > 0)
                                                                    @foreach ($order->payments->where('payment_method', 3)->where('order_payment_status', 0) as $payment)
                                                                        <div
                                                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 mb-2">
                                                                            Unpaid
                                                                        </div>
                                                                        <div class="text-sm text-gray-600">Pay Later</div>
                                                                        <div class="text-xs text-gray-400 mt-1">
                                                                            {{ \Carbon\Carbon::parse($payment->payment_date)->format('M j, Y g:i A') }}
                                                                        </div>
                                                                    @endforeach
                                                                @else
                                                                    <div
                                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 mb-2">
                                                                        Unpaid
                                                                    </div>
                                                                    <div class="text-sm text-gray-600">No payment record</div>
                                                                @endif
                                                            </td>
                                                        @endif
                                                        <td class="px-4 py-3">
                                                            <div class="text-sm text-gray-900">
                                                                {{ $item->product->product_name ?? 'N/A' }}
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <div class="text-sm text-gray-600 text-center">
                                                                {{ $item->quantity }}
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <div class="text-sm text-gray-900 text-right">
                                                                ₱{{ number_format($item->sub_total / $item->quantity, 2) }}
                                                            </div>
                                                        </td>
                                                        @if ($index === 0)
                                                            <td rowspan="{{ count($order->items) }}" class="px-4 py-3 align-top">
                                                                <div class="text-sm font-semibold text-red-600 text-right">
                                                                    ₱{{ number_format($orderTotalAmount, 2) }}
                                                                </div>
                                                            </td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="bg-gray-50 border-t-2 border-gray-200">
                                                <td colspan="5" class="px-4 py-3 text-sm font-semibold text-gray-900 text-right">Total Amount Due:</td>
                                                <td class="px-4 py-3 text-base font-bold text-red-600 text-right">
                                                    ₱{{ number_format(
                                                        $unpaidOrders->sum(function ($order) {
                                                            if ($order->payments->count() > 0) {
                                                                return $order->payments->where('payment_method', 3)->where('order_payment_status', 0)->sum('total_amount');
                                                            } else {
                                                                return $order->items->sum('sub_total');
                                                            }
                                                        }),
                                                        2,
                                                    ) }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        @endif

                        <!-- No Orders Message -->
                        @if ($allOrders->count() === 0)
                            <div class="text-center py-12 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50">
                                <div class="flex flex-col items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" class="w-12 h-12 text-gray-400 mx-auto mb-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                    <p class="text-lg font-medium text-gray-500">No Orders Found</p>
                                    <p class="text-sm text-gray-400 mt-1">This booking has no associated orders.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Detailed Payment Summary - WITH ACCORDION -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center cursor-pointer accordion-toggle"
                    data-target="payment-summary-accordion">
                    <h2 class="text-lg font-semibold text-gray-900">Payment Summary</h2>
                    <svg class="w-5 h-5 text-gray-500 transition-transform accordion-arrow" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
                <div id="payment-summary-accordion" class="accordion-content overflow-hidden transition-all duration-300 ease-in-out"
                    style="max-height: 500px">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[800px]">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Payment Type</th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <!-- Service Payments -->
                                <tr class="bg-blue-50">
                                    <td colspan="3" class="px-6 py-3 text-sm font-semibold text-blue-900 uppercase">Service Payments</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-600">Main Service</td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $formattedData['main_payment_status_class'] === 'status-paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ $formattedData['main_payment_status_text'] ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-right">
                                        {{ $formattedData['main_total_amount_formatted'] ?? '₱0.00' }}
                                    </td>
                                </tr>
                                @if ($extensionPayment)
                                    <tr>
                                        <td class="px-6 py-4 text-sm text-gray-600">Time Extension</td>
                                        <td class="px-6 py-4">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $formattedData['extension_payment_status_class'] === 'status-paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ $formattedData['extension_payment_status_text'] ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 text-right">
                                            {{ $formattedData['extension_total_amount_formatted'] ?? '₱0.00' }}
                                        </td>
                                    </tr>
                                @endif
                                <tr class="bg-gray-50">
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-900">Total Service Payments</td>
                                    <td class="px-6 py-4"></td>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-900 text-right border-t">
                                        @php
                                            $totalServicePayments =
                                                ($mainPayment->total_amount ?? 0) + ($extensionPayment->total_amount ?? 0);
                                        @endphp
                                        ₱{{ number_format($totalServicePayments, 2) }}
                                    </td>
                                </tr>

                                <!-- Order Payments -->
                                <tr class="bg-purple-50">
                                    <td colspan="3" class="px-6 py-3 text-sm font-semibold text-purple-900 uppercase">Order Payments</td>
                                </tr>
                                @if ($hasPaidOrders)
                                    <tr>
                                        <td class="px-6 py-4 text-sm text-gray-600">Paid Orders ({{ $paidOrders->count() }})</td>
                                        <td class="px-6 py-4">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Paid
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-green-600 font-semibold text-right">
                                            {{ $formattedData['total_paid_amount_formatted'] ?? '₱0.00' }}
                                        </td>
                                    </tr>
                                @endif
                                @if ($hasUnpaidOrders)
                                    <tr>
                                        <td class="px-6 py-4 text-sm text-gray-600">Unpaid Orders ({{ $unpaidOrders->count() }})</td>
                                        <td class="px-6 py-4">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Unpaid
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-red-600 font-semibold text-right">
                                            {{ $formattedData['total_unpaid_orders_amount_formatted'] ?? '₱0.00' }}
                                        </td>
                                    </tr>
                                @endif
                                <tr class="bg-gray-50">
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-900">Total Order Payments</td>
                                    <td class="px-6 py-4"></td>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-900 text-right border-t">
                                        @php
                                            $totalOrderPayments = $totalPaidAmount + $totalUnpaidOrdersAmount;
                                        @endphp
                                        ₱{{ number_format($totalOrderPayments, 2) }}
                                    </td>
                                </tr>

                                <!-- Grand Total -->
                                <tr class="bg-amber-50 border-t-2 border-gray-300">
                                    <td class="px-6 py-4 text-base font-bold text-gray-900">GRAND TOTAL</td>
                                    <td class="px-6 py-4"></td>
                                    <td class="px-6 py-4 text-lg font-bold text-amber-900 text-right">
                                        ₱{{ number_format($totalServicePayments + $totalOrderPayments, 2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Time Extension Information - WITH ACCORDION -->
            @if ($booking->extension)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center cursor-pointer accordion-toggle"
                        data-target="time-extension-accordion">
                        <h2 class="text-lg font-semibold text-gray-900">Time Extension Details</h2>
                        <svg class="w-5 h-5 text-gray-500 transition-transform accordion-arrow" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                    <div id="time-extension-accordion" class="accordion-content overflow-hidden transition-all duration-300 ease-in-out"
                        style="max-height: 500px">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[800px]">
                                <tbody class="divide-y divide-gray-200">
                                    <tr>
                                        <td class="w-1/4 px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Base Duration</td>
                                        <td class="w-1/4 px-6 py-4 text-sm text-gray-600">
                                            {{ $booking->extension->base_duration }} hour(s)</td>
                                        <td class="w-1/4 px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Extended Duration</td>
                                        <td class="w-1/4 px-6 py-4 text-sm text-gray-600">
                                            {{ $booking->extension->extended_duration }} hour(s)</td>
                                    </tr>
                                    <tr>
                                        <td class="px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Total Duration</td>
                                        <td colspan="3" class="px-6 py-4 text-sm font-semibold text-gray-900">
                                            {{ $booking->extension->base_duration + $booking->extension->extended_duration }} hour(s)
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Base Price</td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            {{ $formattedData['base_price_formatted'] ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Extension Price</td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            {{ $formattedData['extension_price_formatted'] ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Total Price</td>
                                        <td colspan="3" class="px-6 py-4 text-sm font-semibold text-gray-900">
                                            {{ $formattedData['extension_total_formatted'] ?? 'N/A' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Audit Trail - WITH ACCORDION -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center cursor-pointer accordion-toggle"
                    data-target="audit-trail-accordion">
                    <h2 class="text-lg font-semibold text-gray-900">Audit Trail</h2>
                    <svg class="w-5 h-5 text-gray-500 transition-transform accordion-arrow" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
                <div id="audit-trail-accordion" class="accordion-content overflow-hidden transition-all duration-300 ease-in-out"
                    style="max-height: 500px">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[800px]">
                            <tbody class="divide-y divide-gray-200">
                                <tr>
                                    <td class="w-1/4 px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Created By</td>
                                    <td class="w-1/4 px-6 py-4 text-sm text-gray-600">
                                        {{ $formattedData['created_by_formatted'] ?? 'N/A' }}</td>
                                    <td class="w-1/4 px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Date Created</td>
                                    <td class="w-1/4 px-6 py-4 text-sm text-gray-600">
                                        {{ $formattedData['date_created_formatted'] ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Updated By</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $formattedData['updated_by_formatted'] ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Date Updated</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $formattedData['date_updated_formatted'] ?? 'N/A' }}</td>
                                </tr>
                                @if ($booking->last_updated_by)
                                    <tr>
                                        <td class="px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Last Updated By</td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            {{ $formattedData['last_updated_by_formatted'] ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 bg-gray-50 text-sm font-medium text-gray-900">Last Date Updated</td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            {{ $formattedData['last_date_updated_formatted'] ?? 'N/A' }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Additional Information Grid - WITH ACCORDION -->
            <div class="grid grid-cols-1 gap-6">
                <!-- Receipt - WITH ACCORDION -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden w-full">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center cursor-pointer accordion-toggle"
                        data-target="receipt-accordion">
                        <h2 class="text-lg font-semibold text-gray-900">GCash Receipts</h2>
                        <svg class="w-5 h-5 text-gray-500 transition-transform accordion-arrow" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                    <div id="receipt-accordion" class="accordion-content overflow-hidden transition-all duration-300 ease-in-out">
                        <div class="p-6">
                            <div class="grid grid-cols-1 {{ $extensionPayment ? 'md:grid-cols-2' : '' }} gap-6">
                                <!-- Main Payment Receipt (Category 1) -->
                                <div class="flex flex-col">
                                    <div class="mb-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">
                                            Main Service Payment
                                        </span>
                                    </div>
                                    
                                    @if ($mainPayment && $mainPayment->gcash_receipt_img)
                                        <div class="relative group w-full">
                                            <img src="{{ asset('storage/app/public/' . $mainPayment->gcash_receipt_img) }}"
                                                class="w-auto h-auto max-h-[500px] rounded-lg border border-gray-200 shadow-sm cursor-pointer hover:ring-2 hover:ring-blue-500 transition-all"
                                                onclick="window.open('{{ asset('storage/app/public/' . $mainPayment->gcash_receipt_img) }}', '_blank')"
                                                alt="Main Payment GCash Receipt">
                                        </div>
                                    @else
                                        <div class="flex items-center justify-center aspect-[3/4] w-full max-w-md border-2 border-dashed border-gray-200 rounded-lg bg-gray-50">
                                            <p class="text-xs text-gray-400 italic">No main receipt</p>
                                        </div>
                                    @endif
                                </div>

                                <!-- Extension Payment Receipt (Category 0) -->
                                @if ($extensionPayment)
                                    <div class="flex flex-col">
                                        <div class="mb-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 border border-purple-200">
                                                Time Extension Payment
                                            </span>
                                        </div>

                                        @if ($extensionPayment->gcash_receipt_img)
                                            <div class="relative group w-full">
                                                <img src="{{ asset('storage/app/public/' . $extensionPayment->gcash_receipt_img) }}"
                                                    class="w-auto h-auto max-h-[500px] rounded-lg border border-gray-200 shadow-sm cursor-pointer hover:ring-2 hover:ring-purple-500 transition-all"
                                                    onclick="window.open('{{ asset('storage/app/public/' . $extensionPayment->gcash_receipt_img) }}', '_blank')"
                                                    alt="Extension Payment GCash Receipt">
                                            </div>
                                        @else
                                            <div class="flex items-center justify-center aspect-[3/4] w-full max-w-md border-2 border-dashed border-gray-200 rounded-lg bg-gray-50">
                                                <p class="text-xs text-gray-400 italic">No extension receipt</p>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Accordion functionality
            const accordionToggles = document.querySelectorAll('.accordion-toggle');
            
            accordionToggles.forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const target = document.getElementById(targetId);
                    const arrow = this.querySelector('.accordion-arrow');
                    
                    // Toggle content visibility
                    if (target.classList.contains('accordion-open')) {
                        // Collapse
                        target.style.maxHeight = '0px';
                        target.classList.remove('accordion-open');
                        arrow.style.transform = 'rotate(0deg)';
                    } else {
                        // Expand
                        target.style.maxHeight = target.scrollHeight + 'px';
                        target.classList.add('accordion-open');
                        arrow.style.transform = 'rotate(180deg)';
                    }
                });
            });
            
            // Initialize all accordions as CLOSED by default
            accordionToggles.forEach(toggle => {
                const targetId = toggle.getAttribute('data-target');
                const target = document.getElementById(targetId);
                const arrow = toggle.querySelector('.accordion-arrow');
                
                if (target) {
                    target.style.maxHeight = '0px';
                    target.classList.remove('accordion-open');
                    arrow.style.transform = 'rotate(0deg)';
                }
            });

            // Add click functionality for receipt images
            const receiptImages = document.querySelectorAll('img[onclick*="window.open"]');
            receiptImages.forEach(img => {
                img.addEventListener('click', function() {
                    const imageUrl = this.getAttribute('src');
                    window.open(imageUrl, '_blank');
                });
            });
        });
    </script>
@endpush

@push('styles')
    <style>
        .accordion-content {
            transition: max-height 0.3s ease-out, padding 0.3s ease;
            overflow: hidden;
        }
        
        .accordion-toggle {
            transition: background-color 0.2s ease;
        }
        
        .accordion-toggle:hover {
            background-color: #f9fafb;
        }
        
        .accordion-arrow {
            transition: transform 0.3s ease;
        }
        
        /* Style for blue header accordions */
        .bg-blue-50.accordion-toggle:hover {
            background-color: #eff6ff;
        }
        
        /* Style for purple header accordions */
        .bg-purple-50.accordion-toggle:hover {
            background-color: #faf5ff;
        }
    </style>
@endpush