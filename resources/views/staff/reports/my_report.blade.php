@extends('layouts.app')

@section('title', 'My Performance Report')

@section('content')
<div x-data="staffReport()" x-init="init()" class="p-4">

    <h1 class="text-2xl font-bold text-gray-900 text-center mt-4 mb-6">My Performance Report</h1>

    {{-- ══════════════════════════════════════════
         SHARED TOGGLE BAR - Staff Version
    ══════════════════════════════════════════════ --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-2 mb-6">
        <div class="flex gap-1 overflow-x-auto">
            {{-- Staff Report - Active --}}
            <a href="{{ route('sub_two.reports.my_report') }}"
                class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-all whitespace-nowrap flex items-center gap-2
                       bg-[#7F5539] text-white shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                My Performance
            </a>
        </div>
    </div>

    {{-- ══════════════════════════════════════════
         FILTERS BAR
    ══════════════════════════════════════════════ --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-end gap-4">

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider">Quick Range</label>
                <div class="flex gap-2">
                    <button @click="setPreset('today')"
                        :class="activePreset === 'today' ? 'bg-[#7F5539] text-white border-[#7F5539]' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                        class="px-3 py-2 border rounded-lg text-sm font-medium transition-colors">Today</button>
                    <button @click="setPreset('week')"
                        :class="activePreset === 'week' ? 'bg-[#7F5539] text-white border-[#7F5539]' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                        class="px-3 py-2 border rounded-lg text-sm font-medium transition-colors">Last 7 Days</button>
                    <button @click="setPreset('month')"
                        :class="activePreset === 'month' ? 'bg-[#7F5539] text-white border-[#7F5539]' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                        class="px-3 py-2 border rounded-lg text-sm font-medium transition-colors">Last 30 Days</button>
                    <button @click="setPreset('custom')"
                        :class="activePreset === 'custom' ? 'bg-[#7F5539] text-white border-[#7F5539]' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                        class="px-3 py-2 border rounded-lg text-sm font-medium transition-colors">Custom</button>
                </div>
            </div>

            <div x-show="activePreset === 'custom'" x-cloak class="relative">
                <div class="flex gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider">From</label>
                        <input type="date" x-model="filters.start_date" @change="dateError = ''"
                            :class="dateError ? 'border-red-400' : 'border-gray-300 focus:ring-[#7F5539] focus:border-[#7F5539]'"
                            class="border rounded-lg px-3 py-2 text-sm focus:ring-2">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider">To</label>
                        <input type="date" x-model="filters.end_date"
                            @change="dateError = (filters.start_date && filters.end_date && new Date(filters.end_date) < new Date(filters.start_date)) ? 'To date cannot be earlier than From date.' : ''"
                            :class="dateError ? 'border-red-400' : 'border-gray-300 focus:ring-[#7F5539] focus:border-[#7F5539]'"
                            class="border rounded-lg px-3 py-2 text-sm focus:ring-2">
                    </div>
                </div>
                <div x-show="dateError" class="absolute left-0 top-full mt-1 flex items-center gap-1.5 text-red-600 text-xs whitespace-nowrap">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <span x-text="dateError"></span>
                </div>
            </div>

            <div class="flex gap-2">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider invisible">Action</label>
                    <button @click="fetchReport()" :disabled="isLoading || !!dateError"
                        class="inline-flex items-center px-5 py-2 bg-[#7F5539] hover:bg-[#4A2C1D] text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-60">
                        <svg x-show="isLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="isLoading ? 'Loading...' : 'Generate Report'"></span>
                    </button>
                </div>

                {{-- PDF Export Button --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider invisible">Export</label>
                    <button @click="exportPDF()" :disabled="isLoading || !stats.total_bookings"
                        class="inline-flex items-center px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export PDF
                    </button>
                </div>
            </div>

        </div>
        <p class="text-xs text-gray-400 mt-3"
            x-text="`Showing data from ${formatDate(filters.start_date)} to ${formatDate(filters.end_date)}`"></p>
    </div>

    {{-- Staff Info Card --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="flex items-center gap-4">
            <div class="h-14 w-14 rounded-full bg-[#7F5539]/10 flex items-center justify-center flex-shrink-0">
                <svg class="h-8 w-8 text-[#7F5539]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-900" x-text="staffName"></h2>
                <p class="text-sm text-gray-600" x-text="staffPosition + ' • ' + branchName"></p>
                <p class="text-sm text-gray-500" x-text="'Assigned Branch: ' + branchName"></p>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-100 p-3 rounded-lg">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Bookings</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="stats.total_bookings ?? '—'"></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-100 p-3 rounded-lg">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Customers Served</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="stats.total_customers ?? '—'"></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-yellow-100 p-3 rounded-lg">
                    <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Hours Used</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="stats.total_hours_used ?? '—'"></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-[#7F5539]/10 p-3 rounded-lg">
                    <svg class="h-6 w-6 text-[#7F5539]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="stats.total_revenue ? '₱' + Number(stats.total_revenue).toLocaleString('en', {minimumFractionDigits: 2}) : '—'"></p>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════
         EMPTY STATE
    ══════════════════════════════════════════════ --}}
    <div x-show="!stats.total_bookings && !isLoading" class="bg-white rounded-lg shadow-sm border border-gray-200 px-6 py-16 text-center text-gray-400 mb-6">
        <svg class="mx-auto h-12 w-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <p class="text-sm font-semibold text-gray-500">No report generated yet</p>
        <p class="text-xs text-gray-400 mt-1">Select a date range and click <span class="font-medium text-[#7F5539]">Generate Report</span> to view your performance data.</p>
    </div>

    {{-- ══════════════════════════════════════════
         REVENUE BREAKDOWN
    ══════════════════════════════════════════════ --}}
    <div x-show="stats.total_bookings > 0" class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Revenue Breakdown</h2>
            <p class="text-sm text-gray-500 mt-1">Booking and order revenue breakdown</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                    <p class="text-2xl font-bold text-[#7F5539]" x-text="stats.total_revenue ? '₱' + Number(stats.total_revenue).toLocaleString('en', {minimumFractionDigits: 2}) : '—'"></p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm font-medium text-gray-600">Booking Revenue</p>
                    <p class="text-2xl font-bold text-blue-600" x-text="stats.booking_revenue ? '₱' + Number(stats.booking_revenue).toLocaleString('en', {minimumFractionDigits: 2}) : '—'"></p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm font-medium text-gray-600">Order Revenue</p>
                    <p class="text-2xl font-bold text-green-600" x-text="stats.order_revenue ? '₱' + Number(stats.order_revenue).toLocaleString('en', {minimumFractionDigits: 2}) : '—'"></p>
                </div>
            </div>

            {{-- Booking Revenue Details --}}
            <div x-show="stats.booking_revenue_breakdown?.length > 0" class="mb-6">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Booking Payments</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payments</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="(payment, index) in stats.booking_revenue_breakdown" :key="index">
                                <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'" class="hover:bg-gray-100">
                                    <td class="px-4 py-3 whitespace-nowrap" x-text="payment.payment_category == 1 ? 'Main Payment' : 'Extension Payment'"></td>
                                    <td class="px-4 py-3 whitespace-nowrap" x-text="['Cash', 'GCash', 'Debit Card', 'Other'][payment.payment_method]"></td>
                                    <td class="px-4 py-3 whitespace-nowrap" x-text="payment.payment_count"></td>
                                    <td class="px-4 py-3 whitespace-nowrap" x-text="'₱' + Number(payment.total_amount).toLocaleString('en', {minimumFractionDigits: 2})"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Order Revenue Details --}}
            <div x-show="stats.order_revenue_breakdown?.length > 0">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Order Payments</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payments</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="(payment, index) in stats.order_revenue_breakdown" :key="index">
                                <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'" class="hover:bg-gray-100">
                                    <td class="px-4 py-3 whitespace-nowrap" x-text="['Cash', 'GCash', 'Debit Card', 'Pay Later'][payment.payment_method]"></td>
                                    <td class="px-4 py-3 whitespace-nowrap" x-text="payment.payment_count"></td>
                                    <td class="px-4 py-3 whitespace-nowrap" x-text="'₱' + Number(payment.total_amount).toLocaleString('en', {minimumFractionDigits: 2})"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════
         SERVICES BREAKDOWN
    ══════════════════════════════════════════════ --}}
    <div x-show="stats.service_breakdown?.length > 0" class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Services Breakdown</h2>
            <p class="text-sm text-gray-500 mt-1">Services booked and their revenue</p>
        </div>
        <div class="overflow-x-auto p-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bookings</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="(service, index) in stats.service_breakdown" :key="index">
                        <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'" class="hover:bg-gray-100">
                            <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900" x-text="service.service_name"></td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-700" x-text="service.service_category"></td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-700" x-text="service.total_bookings"></td>
                            <td class="px-4 py-3 whitespace-nowrap font-medium text-[#7F5539]" x-text="'₱' + Number(service.total_revenue).toLocaleString('en', {minimumFractionDigits: 2})"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ══════════════════════════════════════════
         PRODUCTS SOLD
    ══════════════════════════════════════════════ --}}
    <div x-show="stats.order_items_breakdown?.length > 0" class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Products Sold</h2>
            <p class="text-sm text-gray-500 mt-1">Products sold through orders</p>
        </div>
        <div class="overflow-x-auto p-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity Sold</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orders</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="(item, index) in stats.order_items_breakdown" :key="index">
                        <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'" class="hover:bg-gray-100">
                            <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900" x-text="item.product_name"></td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-700" x-text="item.product_type"></td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-700" x-text="item.total_quantity_sold"></td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-700" x-text="item.order_count"></td>
                            <td class="px-4 py-3 whitespace-nowrap font-medium text-[#7F5539]" x-text="'₱' + Number(item.total_revenue).toLocaleString('en', {minimumFractionDigits: 2})"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ══════════════════════════════════════════
         INVENTORY DEDUCTION
    ══════════════════════════════════════════════ --}}
    <div x-show="stats.inventory_deduction?.product_deduction?.length > 0" class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Inventory Deduction</h2>
            <p class="text-sm text-gray-500 mt-1">Products deducted from inventory</p>
        </div>
        <div class="overflow-x-auto p-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity Deducted</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value Deducted</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="(product, index) in stats.inventory_deduction.product_deduction" :key="index">
                        <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'" class="hover:bg-gray-100">
                            <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900" x-text="product.product_name"></td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-700" x-text="product.product_type"></td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-700" x-text="product.total_quantity_deducted"></td>
                            <td class="px-4 py-3 whitespace-nowrap font-medium text-red-600" x-text="'₱' + Number(product.total_value_deducted).toLocaleString('en', {minimumFractionDigits: 2})"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ══════════════════════════════════════════
         INVENTORY STATISTICS
    ══════════════════════════════════════════════ --}}
    <div x-show="stats.inventory_stats" class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Inventory Statistics</h2>
            <p class="text-sm text-gray-500 mt-1">Current inventory status</p>
        </div>
        <div class="overflow-x-auto p-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Items</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Available</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Low Stock</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Quantity</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">Products</td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-700" x-text="stats.inventory_stats?.product_stats?.total_products ?? 0"></td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-700" x-text="stats.inventory_stats?.product_stats?.available_products ?? 0"></td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
                                x-text="stats.inventory_stats?.product_stats?.low_stock_products ?? 0"></span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-700" x-text="stats.inventory_stats?.product_stats?.total_quantity ?? 0"></td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">Ingredients</td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-700" x-text="stats.inventory_stats?.ingredient_stats?.total_ingredients ?? 0"></td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-700" x-text="stats.inventory_stats?.ingredient_stats?.available_ingredients ?? 0"></td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
                                x-text="stats.inventory_stats?.ingredient_stats?.low_stock_ingredients ?? 0"></span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-700" x-text="stats.inventory_stats?.ingredient_stats?.total_quantity ?? 0"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {

    function todayStr() {
        return new Date().toISOString().split('T')[0];
    }
    function daysAgoStr(n) {
        const d = new Date();
        d.setDate(d.getDate() - n);
        return d.toISOString().split('T')[0];
    }
    function fmtDate(dateStr) {
        if (!dateStr) return '';
        return new Date(dateStr).toLocaleDateString('en-US', {
            year: 'numeric', month: 'short', day: 'numeric'
        });
    }

    Alpine.data('staffReport', () => ({
        activePreset: 'month',
        isLoading: false,
        dateError: '',
        filters: { 
            start_date: daysAgoStr(29), 
            end_date: todayStr() 
        },
        stats: {},
        staffName: '{{ $staff->first_name }} {{ $staff->last_name }}',
        staffPosition: '{{ $staff->position ?? 'Staff' }}',
        branchName: '{{ $branch->branch_name ?? 'N/A' }}',

        init() {
            // Auto-load report on page load
            this.fetchReport();
        },

        setPreset(preset) {
            this.activePreset = preset;
            this.dateError = '';
            if (preset === 'today') { 
                this.filters.start_date = todayStr(); 
                this.filters.end_date = todayStr(); 
            }
            if (preset === 'week') { 
                this.filters.start_date = daysAgoStr(6); 
                this.filters.end_date = todayStr(); 
            }
            if (preset === 'month') { 
                this.filters.start_date = daysAgoStr(29); 
                this.filters.end_date = todayStr(); 
            }
        },

        formatDate: fmtDate,

        async fetchReport() {
            if (this.dateError) return;
            if (this.filters.start_date && this.filters.end_date) {
                if (new Date(this.filters.end_date) < new Date(this.filters.start_date)) {
                    this.dateError = '"To" date cannot be earlier than "From" date.';
                    return;
                }
            }
            this.isLoading = true;
            try {
                const params = new URLSearchParams({
                    start_date: this.filters.start_date,
                    end_date: this.filters.end_date,
                    ajax: 'true',
                });
                const res = await fetch(`{{ route('sub_two.reports.my_report') }}?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.success) {
                    this.stats = data.stats;
                } else {
                    console.error('Staff report failed:', data);
                }
            } catch (e) {
                console.error('Staff report error:', e);
            } finally {
                this.isLoading = false;
            }
        },

        exportPDF() {
            if (!this.stats.total_bookings) {
                alert('No data to export. Please generate the report first.');
                return;
            }
            
            const params = new URLSearchParams({
                start_date: this.filters.start_date,
                end_date: this.filters.end_date,
            });
            
            window.open(`{{ route('sub_two.reports.export') }}?${params}`, '_blank');
        },
    }));

});
</script>

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection