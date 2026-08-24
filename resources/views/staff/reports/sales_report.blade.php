@extends('layouts.app')

@section('title', 'Sales Report')

@section('content')
<div class="p-4">

    <h1 class="text-2xl font-bold text-gray-900 text-center mt-4 mb-6">Sales Report</h1>

    {{-- ══════════════════════════════════════════
         SHARED TOGGLE BAR - Staff Version
    ══════════════════════════════════════════════ --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-2 mb-6">
        <div class="flex gap-1 overflow-x-auto">
            {{-- Sales — active on this page --}}
            <a href="{{ route('sub_two.reports.my_report') }}"
                class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-all whitespace-nowrap flex items-center gap-2
                       bg-[#7F5539] text-white shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Sales
            </a>

            {{-- Inventory --}}
            <a href="{{ route('sub_two.reports.inventory_report') }}"
                class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-all whitespace-nowrap flex items-center gap-2
                       text-[#7F5539] hover:bg-[#7F5539]/10">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                Inventory
            </a>

            {{-- Ratings --}}
            <a href="{{ route('sub_two.reports.feedback_report') }}"
                class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-all whitespace-nowrap flex items-center gap-2
                       text-[#7F5539] hover:bg-[#7F5539]/10">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
                Ratings
            </a>
        </div>
    </div>

    {{-- Branch Info Badge --}}
    <div class="mb-4">
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-[#7F5539]/10 text-[#7F5539]">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            {{ $branch->branch_name ?? 'My Branch' }}
        </span>
    </div>

    {{-- ══════════════════════════════════════════
         SALES REPORT CONTENT
    ══════════════════════════════════════════════ --}}
    <div x-data="salesReport()" x-init="init()">

        {{-- Filter Bar --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-end gap-4">

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider">Quick Range</label>
                    <div class="flex gap-2">
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
                            <input type="date" x-model="filters.date_from" @change="dateError = ''"
                                :class="dateError ? 'border-red-400' : 'border-gray-300 focus:ring-[#7F5539] focus:border-[#7F5539]'"
                                class="border rounded-lg px-3 py-2 text-sm focus:ring-2">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider">To</label>
                            <input type="date" x-model="filters.date_to"
                                @change="dateError = (filters.date_from && filters.date_to && new Date(filters.date_to) < new Date(filters.date_from)) ? 'To date cannot be earlier than From date.' : ''"
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
                        <button @click="exportPDF()" :disabled="isLoading || !salesData.by_branch?.length"
                            class="inline-flex items-center px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export PDF
                        </button>
                    </div>
                </div>

            </div>
            <div class="flex justify-between items-center mt-3">
                <p class="text-xs text-gray-400"
                    x-text="`Showing data from ${formatDate(filters.date_from)} to ${formatDate(filters.date_to)}`"></p>
                <p class="text-xs text-gray-400" x-show="salesData.branch_name" x-text="`Branch: ${salesData.branch_name}`"></p>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-[#7F5539]/10 p-3 rounded-lg">
                        <svg class="h-6 w-6 text-[#7F5539]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                        <p class="text-2xl font-bold text-gray-900"
                            x-text="salesData.total_revenue ? '₱' + Number(salesData.total_revenue).toLocaleString('en', {minimumFractionDigits: 2}) : '—'"></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-emerald-100 p-3 rounded-lg">
                        <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Net Revenue</p>
                        <p class="text-2xl font-bold text-emerald-700"
                            x-text="salesData.total_net_revenue ? '₱' + Number(salesData.total_net_revenue).toLocaleString('en', {minimumFractionDigits: 2}) : '—'"></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-100 p-3 rounded-lg">
                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Bookings</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="salesData.total_bookings ?? '—'"></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-100 p-3 rounded-lg">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Orders</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="salesData.total_orders ?? '—'"></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-100 p-3 rounded-lg">
                        <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Redemptions</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="salesData.total_redemptions ?? '—'"></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sales by Branch Table --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Sales Summary</h2>
                    <p class="text-sm text-gray-500 mt-1">Booking revenue, order revenue, reward discounts, and net revenue</p>
                </div>
                <div class="text-xs text-gray-400" x-show="salesData.by_branch?.length">
                    <span x-text="`${salesData.by_branch?.length || 0} branch(es)`"></span>
                </div>
            </div>

            <div x-show="!salesData.by_branch?.length && !isLoading" class="px-6 py-12 text-center text-gray-400">
                <svg class="mx-auto h-10 w-10 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <p class="text-sm">Click "Generate Report" to load sales data.</p>
            </div>

            <div x-show="isLoading" class="px-6 py-12 text-center">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-[#7F5539] border-t-transparent"></div>
                <p class="text-sm text-gray-500 mt-2">Loading report data...</p>
            </div>

            <div class="overflow-x-auto" x-show="salesData.by_branch?.length && !isLoading">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Booking Revenue</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Order Revenue</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Reward Discount</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Revenue</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-emerald-600 uppercase tracking-wider">Net Revenue</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Bookings</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Orders</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Redemptions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(branch, index) in salesData.by_branch" :key="branch.branch_name">
                            <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'" class="hover:bg-[#7F5539]/5 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="h-8 w-8 rounded-full bg-[#7F5539]/10 flex items-center justify-center flex-shrink-0">
                                            <svg class="h-4 w-4 text-[#7F5539]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900" x-text="branch.branch_name"></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-right"
                                    x-text="'₱' + Number(branch.booking_revenue).toLocaleString('en', {minimumFractionDigits: 2})"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-right"
                                    x-text="'₱' + Number(branch.order_revenue).toLocaleString('en', {minimumFractionDigits: 2})"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 text-right"
                                    x-text="'- ₱' + Number(branch.reward_discount).toLocaleString('en', {minimumFractionDigits: 2})"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm font-semibold text-gray-900"
                                        x-text="'₱' + Number(branch.total_revenue).toLocaleString('en', {minimumFractionDigits: 2})"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm font-bold text-emerald-600"
                                        x-text="'₱' + Number(branch.net_revenue).toLocaleString('en', {minimumFractionDigits: 2})"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                        x-text="branch.total_bookings"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                        x-text="branch.total_orders"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800"
                                        x-text="branch.total_redemptions"></span>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="salesData.by_branch?.length > 0" class="bg-[#7F5539]/5 font-semibold border-t-2 border-[#7F5539]/20">
                            <td class="px-6 py-4 text-sm font-bold text-gray-900">Grand Total</td>
                            <td class="px-6 py-4 text-sm text-gray-900 text-right"
                                x-text="'₱' + salesData.by_branch?.reduce((s,b) => s + Number(b.booking_revenue), 0).toLocaleString('en', {minimumFractionDigits: 2})"></td>
                            <td class="px-6 py-4 text-sm text-gray-900 text-right"
                                x-text="'₱' + salesData.by_branch?.reduce((s,b) => s + Number(b.order_revenue), 0).toLocaleString('en', {minimumFractionDigits: 2})"></td>
                            <td class="px-6 py-4 text-sm text-red-600 text-right"
                                x-text="'- ₱' + salesData.by_branch?.reduce((s,b) => s + Number(b.reward_discount), 0).toLocaleString('en', {minimumFractionDigits: 2})"></td>
                            <td class="px-6 py-4 text-sm font-bold text-[#7F5539] text-right"
                                x-text="'₱' + Number(salesData.total_revenue).toLocaleString('en', {minimumFractionDigits: 2})"></td>
                            <td class="px-6 py-4 text-sm font-bold text-emerald-700 text-right"
                                x-text="'₱' + Number(salesData.total_net_revenue).toLocaleString('en', {minimumFractionDigits: 2})"></td>
                            <td class="px-6 py-4 text-sm text-gray-900 text-center" x-text="salesData.total_bookings"></td>
                            <td class="px-6 py-4 text-sm text-gray-900 text-center" x-text="salesData.total_orders"></td>
                            <td class="px-6 py-4 text-sm text-gray-900 text-center" x-text="salesData.total_redemptions"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ══════════════════════════════════════════
             Payment Method Breakdown
        ══════════════════════════════════════════════ --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6" x-show="salesData.payment_methods?.length && !isLoading">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Payment Method Breakdown</h2>
                <p class="text-sm text-gray-500 mt-1">Number of payments and total amount collected per method</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Payments</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(pm, index) in salesData.payment_methods" :key="pm.method">
                            <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'" class="hover:bg-[#7F5539]/5 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="pm.method"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700" x-text="pm.payments"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 text-right"
                                    x-text="'₱' + Number(pm.total_amount).toLocaleString('en', {minimumFractionDigits: 2})"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ══════════════════════════════════════════
             Most Popular Services (Bookings)
        ══════════════════════════════════════════════ --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6" x-show="salesData.service_breakdown?.length && !isLoading">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Most Popular Services</h2>
                <p class="text-sm text-gray-500 mt-1">Walk-in and online bookings, by category and service — sorted by hours spent</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Hours Spent</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(s, index) in salesData.service_breakdown" :key="s.category + '-' + s.service">
                            <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'" class="hover:bg-[#7F5539]/5 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700" x-text="s.category"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="s.service"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-right" x-text="s.hours + ' hr(s)'"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 text-right"
                                    x-text="'₱' + Number(s.revenue).toLocaleString('en', {minimumFractionDigits: 2})"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ══════════════════════════════════════════
             Products Sold (RTD/Package + MTO)
        ══════════════════════════════════════════════ --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6" x-show="salesData.products_sold?.length && !isLoading">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Products Sold</h2>
                <p class="text-sm text-gray-500 mt-1">RTD / Package and MTO product performance — sorted by quantity sold</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity Sold</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(p, index) in salesData.products_sold" :key="p.product + '-' + p.type">
                            <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'" class="hover:bg-[#7F5539]/5 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="p.product"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="p.type === 'mto' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'"
                                        x-text="productTypeLabel(p.type)"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-center" x-text="p.quantity"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 text-right"
                                    x-text="'₱' + Number(p.revenue).toLocaleString('en', {minimumFractionDigits: 2})"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ══════════════════════════════════════════
             Orders Breakdown (with expandable item details)
        ══════════════════════════════════════════════ --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6" x-show="salesData.orders?.length && !isLoading">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Orders Breakdown</h2>
                    <p class="text-sm text-gray-500 mt-1">Orders you handled in the selected period</p>
                </div>
                <span class="text-xs text-gray-400" x-text="`${salesData.orders?.length || 0} order(s)`"></span>
            </div>

            <div x-show="!pagedOrders.length" class="px-6 py-10 text-center text-sm text-gray-400">
                No orders found for this filter.
            </div>

            <div class="overflow-x-auto" x-show="pagedOrders.length">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order Ref.</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Items Qty</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <template x-for="(order, index) in pagedOrders" :key="order.order_ref_no">
                        <tbody>
                            <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'" class="hover:bg-[#7F5539]/5 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="order.order_ref_no"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700" x-text="order.branch_name"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700" x-text="order.date"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700" x-text="order.payment_method"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-center" x-text="order.items_qty"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 text-right"
                                    x-text="'₱' + Number(order.total_amount).toLocaleString('en', {minimumFractionDigits: 2})"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button @click="toggleOrder(order.order_ref_no)"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border transition-colors"
                                        :class="expandedOrders[order.order_ref_no]
                                            ? 'bg-[#7F5539] text-white border-[#7F5539]'
                                            : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'">
                                        <span x-text="expandedOrders[order.order_ref_no] ? 'Hide Details' : 'View Details'"></span>
                                        <svg class="w-3.5 h-3.5 flex-shrink-0 transition-transform duration-200"
                                            :class="expandedOrders[order.order_ref_no] ? 'rotate-180' : ''"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>

                            <tr x-show="expandedOrders[order.order_ref_no]" x-cloak>
                                <td colspan="7" class="px-6 pb-4 pt-0 bg-[#faf7f4]">
                                    <div class="rounded-lg border border-gray-200 overflow-hidden bg-white mt-1">
                                        <table class="min-w-full text-xs">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                                    <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                                    <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                                    <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                <template x-for="(item, i) in order.items" :key="i">
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-4 py-2 text-gray-800" x-text="item.product_name"></td>
                                                        <td class="px-4 py-2 text-gray-700" x-text="item.quantity"></td>
                                                        <td class="px-4 py-2 text-gray-700" x-text="'₱' + item.selling_price"></td>
                                                        <td class="px-4 py-2 text-gray-700" x-text="'₱' + item.sub_total"></td>
                                                    </tr>
                                                </template>
                                                <template x-if="!order.items || !order.items.length">
                                                    <tr>
                                                        <td colspan="4" class="px-4 py-4 text-center text-gray-400">No items found for this order.</td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </template>
                </table>
            </div>

            {{-- Orders Pagination --}}
            <div x-show="orderTotalPages > 1" class="px-4 sm:px-6 py-4 border-t border-gray-200">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="text-sm text-gray-700">
                        Page <span x-text="orderPage"></span> of <span x-text="orderTotalPages"></span>
                        &middot; <span x-text="salesData.orders?.length || 0"></span> total order(s)
                    </div>
                    <div class="flex flex-wrap justify-center items-center gap-2">
                        <button @click="orderPage = Math.max(1, orderPage - 1)"
                            :disabled="orderPage === 1"
                            class="px-3 py-1 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            Previous
                        </button>
                        <button @click="orderPage = Math.min(orderTotalPages, orderPage + 1)"
                            :disabled="orderPage === orderTotalPages"
                            class="px-3 py-1 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- No Data Message --}}
        <div x-show="salesData.by_branch?.length === 0 && !isLoading && salesData.total_revenue !== undefined"
             class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <h3 class="text-lg font-medium text-gray-900">No sales data found</h3>
            <p class="text-sm text-gray-500 mt-1">Try adjusting your date range.</p>
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

    Alpine.data('salesReport', () => ({
        activePreset: 'week',
        isLoading:    false,
        dateError:    '',
        filters: { date_from: daysAgoStr(6), date_to: todayStr() },
        salesData: {},

        // ── Orders Breakdown pagination + expand state ──
        orderPage:      1,
        orderPerPage:   10,
        expandedOrders: {},

        get pagedOrders() {
            const orders = this.salesData.orders || [];
            const start  = (this.orderPage - 1) * this.orderPerPage;
            return orders.slice(start, start + this.orderPerPage);
        },
        get orderTotalPages() {
            return Math.max(1, Math.ceil((this.salesData.orders?.length || 0) / this.orderPerPage));
        },
        toggleOrder(ref) {
            this.expandedOrders = { ...this.expandedOrders, [ref]: !this.expandedOrders[ref] };
        },
        productTypeLabel(type) {
            const map = { rtd: 'RTD', package: 'Package', mto: 'MTO' };
            if (!type) return 'Unknown';
            return map[type] ?? (type.charAt(0).toUpperCase() + type.slice(1));
        },

        init() {
            // Auto-load report on page load
            this.fetchReport();
        },

        setPreset(preset) {
            this.activePreset = preset;
            this.dateError = '';
            if (preset === 'week')  { this.filters.date_from = daysAgoStr(6);  this.filters.date_to = todayStr(); }
            if (preset === 'month') { this.filters.date_from = daysAgoStr(29); this.filters.date_to = todayStr(); }
        },

        formatDate: fmtDate,

        async fetchReport() {
            if (this.dateError) return;
            this.isLoading = true;
            try {
                const params = new URLSearchParams({
                    date_from: this.filters.date_from,
                    date_to:   this.filters.date_to,
                    ajax:      'true',
                    type:      'sales',
                });
                const res  = await fetch(`{{ route('sub_two.reports.my_report') }}?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.success) {
                    this.salesData = data;
                    // Reset orders pagination + expand state on every new fetch
                    this.orderPage = 1;
                    this.expandedOrders = {};
                } else {
                    console.error('Sales report failed:', data);
                }
            } catch (e) {
                console.error('Sales report error:', e);
            } finally {
                this.isLoading = false;
            }
        },

        exportPDF() {
            if (!this.salesData.by_branch?.length) {
                alert('No data to export. Please generate the report first.');
                return;
            }

            const params = new URLSearchParams({
                date_from: this.filters.date_from,
                date_to: this.filters.date_to,
            });

            window.open(`{{ route('sub_two.reports.export_sales_pdf') }}?${params}`, '_blank');
        },
    }));

});
</script>

<style>
[x-cloak] { display: none !important; }
</style>

@endsection