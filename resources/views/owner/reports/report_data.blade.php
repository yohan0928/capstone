@extends('layouts.app')

@section('title', 'Staff Performance Report')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <!-- Scroll-to-Top Button -->
        <button id="scrollToTopBtn"
            class="fixed bottom-6 right-6 z-50 p-3 bg-[#7F5539] text-white rounded-full shadow-lg hover:bg-[#4A2C1D] transition-all duration-300 opacity-0 transform translate-y-10">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
            </svg>
        </button>

        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                <h1 class="text-2xl font-bold text-gray-900">
                    @if ($selectedBranch && $selectedBranch != 'all')
                        Staff Performance Report - {{ $selectedBranchName }}
                    @else
                        Staff Performance Report
                    @endif
                </h1>

                <div class="flex space-x-3">
                    <a href="{{ route('sub_one.reports.export', ['staff_uuid' => $staff->uuid, 'branch_filter' => request('branch_filter', 'all')] + request()->query()) }}"
                        class="px-4 py-2 bg-[#7F5539] text-white rounded-lg hover:bg-[#4A2C1D] transition-colors flex items-center no-print w-full sm:w-auto justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export PDF
                    </a>
                </div>
            </div>
        </div>

        <!-- Branch Filter -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6 no-print">
            <form method="GET" action="{{ route('sub_one.reports.report_data', $staff->uuid) }}" id="branchFilterForm">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <!-- Quick Branch Filter -->
                    <div class="inline-flex rounded-lg p-1 overflow-x-auto md:overflow-visible"
                        style="background-color: #e6ddd4; border: 1px solid #d4c4b2; min-width: 100%;">
                        <div class="flex space-x-0">
                            <button type="submit" name="branch_filter" value="all"
                                class="relative overflow-hidden transition-all duration-300 ease-in-out py-2 px-4 rounded-md text-sm font-medium cursor-pointer focus:z-10 focus:outline-none whitespace-nowrap {{ request('branch_filter', 'all') == 'all' ? 'text-white' : '' }}"
                                style="{{ request('branch_filter', 'all') == 'all' ? 'background-color: #9c6644;' : 'background-color: transparent; color: #7f5539;' }}">
                                All Branches
                            </button>
                            @foreach ($branches as $branch)
                                <button type="submit" name="branch_filter" value="{{ $branch->uuid }}"
                                    class="relative overflow-hidden transition-all duration-300 ease-in-out py-2 px-4 rounded-md text-sm font-medium cursor-pointer focus:z-10 focus:outline-none whitespace-nowrap {{ request('branch_filter') == $branch->uuid ? 'text-white' : '' }}"
                                    style="{{ request('branch_filter') == $branch->uuid ? 'background-color: #9c6644;' : 'background-color: transparent; color: #7f5539;' }}">
                                    {{ $branch->branch_name }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Desktop: Clear Branch Filter Button -->
                    @if (request('branch_filter') && request('branch_filter') != 'all')
                        <div class="hidden md:block">
                            <button type="button" id="clearBranchFilter"
                                class="px-4 py-2 rounded-lg transition-all text-sm font-medium shadow-sm flex items-center justify-center hover:opacity-90"
                                style="background-color: #f5f0eb; border: 1px solid #d4c4b2; color: #7f5539;">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Clear Branch Filter
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Hidden inputs to preserve date filter -->
                <input type="hidden" name="start_date" value="{{ request('start_date', $startDate->format('Y-m-d')) }}">
                <input type="hidden" name="end_date" value="{{ request('end_date', $endDate->format('Y-m-d')) }}">
            </form>

            <!-- Mobile: Clear Branch Filter Button -->
            @if (request('branch_filter') && request('branch_filter') != 'all')
                <div class="mt-4 md:hidden">
                    <button type="button" id="clearBranchFilterMobile"
                        class="w-full px-4 py-2 rounded-lg transition-all text-sm font-medium shadow-sm flex items-center justify-center hover:opacity-90"
                        style="background-color: #f5f0eb; border: 1px solid #d4c4b2; color: #7f5539;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Clear Branch Filter
                    </button>
                </div>
            @endif
        </div>

        <!-- Date Range Filter -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6 no-print">
            <form method="GET" action="{{ route('sub_one.reports.report_data', $staff->uuid) }}" id="dateFilterForm">
                <div class="space-y-4">
                    <!-- Quick Date Presets -->
                    <div class="flex flex-wrap gap-2 mb-4">
                        <button type="button" onclick="setDateRange('today')"
                            class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50 transition-colors">
                            Today
                        </button>
                        <button type="button" onclick="setDateRange('yesterday')"
                            class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50 transition-colors">
                            Yesterday
                        </button>
                        <button type="button" onclick="setDateRange('last7days')"
                            class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50 transition-colors">
                            Last 7 Days
                        </button>
                        <button type="button" onclick="setDateRange('last30days')"
                            class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50 transition-colors">
                            Last 30 Days
                        </button>
                        <button type="button" onclick="setDateRange('thismonth')"
                            class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50 transition-colors">
                            This Month
                        </button>
                        <button type="button" onclick="setDateRange('lastmonth')"
                            class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50 transition-colors">
                            Last Month
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Start Date -->
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                            <input type="date" name="start_date" id="start_date"
                                value="{{ old('start_date', request('start_date', $startDate->format('Y-m-d'))) }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]">
                        </div>

                        <!-- End Date -->
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input type="date" name="end_date" id="end_date"
                                value="{{ old('end_date', request('end_date', $endDate->format('Y-m-d'))) }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]">
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-end space-x-3">
                            <button type="submit"
                                class="px-4 py-2 bg-[#7F5539] text-white rounded-lg hover:bg-[#4A2C1D] transition-colors">
                                Apply Filters
                            </button>
                            <a href="{{ route('sub_one.reports.report_data', $staff->uuid) }}?branch_filter={{ request('branch_filter', 'all') }}"
                                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                                Clear Dates
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Hidden input to preserve branch filter -->
                @if (request('branch_filter'))
                    <input type="hidden" name="branch_filter" value="{{ request('branch_filter') }}">
                @endif
            </form>
        </div>

        <!-- Staff Info -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="bg-[#4A2C1D]/10 rounded-lg p-3">
                        <svg class="w-6 h-6 text-[#7F5539]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $staff->first_name }} {{ $staff->last_name }}
                        </h2>
                        <p class="text-sm text-gray-600">{{ $staff->position ?? 'Staff' }} •
                            {{ $staff->branch->branch_name ?? 'N/A' }}</p>
                        <p class="text-sm text-gray-500">Report Period: {{ $startDate->format('M d, Y') }} -
                            {{ $endDate->format('M d, Y') }}</p>
                        @if ($selectedBranch && $selectedBranch != 'all')
                            <p class="text-sm text-gray-500">Branch Filter: {{ $selectedBranchName }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- ORIGINAL Section Navigation - Always visible -->
<nav id="originalSectionNav" class="bg-white border border-gray-200 shadow-sm mb-6 rounded-lg">
    <div class="container mx-auto px-4 py-3">
        <div class="flex space-x-2 sm:space-x-4 overflow-x-auto">
            <a href="#revenue" class="section-nav-link px-3 py-2 text-sm font-medium rounded-md transition-colors"
                data-section="revenue">
                Revenue
            </a>
            <a href="#branches"
                class="section-nav-link px-3 py-2 text-sm font-medium rounded-md transition-colors"
                data-section="branches">
                Branches
            </a>
            <a href="#services"
                class="section-nav-link px-3 py-2 text-sm font-medium rounded-md transition-colors"
                data-section="services">
                Services
            </a>
            <a href="#orders" class="section-nav-link px-3 py-2 text-sm font-medium rounded-md transition-colors"
                data-section="orders">
                Orders
            </a>
            <a href="#products"
                class="section-nav-link px-3 py-2 text-sm font-medium rounded-md transition-colors"
                data-section="products">
                Products
            </a>
            <a href="#inventory"
                class="section-nav-link px-3 py-2 text-sm font-medium rounded-md transition-colors"
                data-section="inventory">
                Inventory
            </a>
        </div>
    </div>
</nav>

<!-- STICKY Section Navigation - Appears at top when scrolling -->
<nav id="stickySectionNav"
    class="fixed top-[63px] left-0 right-0 z-50 bg-white border-b border-gray-200 shadow-lg hidden"
    style="transform: translateY(-100%); transition: transform 0.3s ease-in-out;">
    <div class="container mx-auto px-4 py-3">
        <div class="flex space-x-2 sm:space-x-4 overflow-x-auto">
            <a href="#revenue" class="section-nav-link px-3 py-2 text-sm font-medium rounded-md transition-colors"
                data-section="revenue">
                Revenue
            </a>
            <a href="#branches"
                class="section-nav-link px-3 py-2 text-sm font-medium rounded-md transition-colors"
                data-section="branches">
                Branches
            </a>
            <a href="#services"
                class="section-nav-link px-3 py-2 text-sm font-medium rounded-md transition-colors"
                data-section="services">
                Services
            </a>
            <a href="#orders" class="section-nav-link px-3 py-2 text-sm font-medium rounded-md transition-colors"
                data-section="orders">
                Orders
            </a>
            <a href="#products"
                class="section-nav-link px-3 py-2 text-sm font-medium rounded-md transition-colors"
                data-section="products">
                Products
            </a>
            <a href="#inventory"
                class="section-nav-link px-3 py-2 text-sm font-medium rounded-md transition-colors"
                data-section="inventory">
                Inventory
            </a>
        </div>
    </div>
</nav>

        <!-- Revenue Breakdown -->
        <section id="revenue" class="mb-8">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Revenue Breakdown
                    @if ($selectedBranch && $selectedBranch != 'all')
                        ({{ $selectedBranchName }})
                    @else
                        (All Branches)
                    @endif
                </h3>

                <!-- Total Revenue Summary -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                        <p class="text-2xl font-bold text-gray-900">₱{{ number_format($stats['total_revenue'], 2) }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm font-medium text-gray-600">Booking Revenue</p>
                        <p class="text-2xl font-bold text-gray-900">₱{{ number_format($stats['booking_revenue'], 2) }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm font-medium text-gray-600">Order Revenue</p>
                        <p class="text-2xl font-bold text-gray-900">₱{{ number_format($stats['order_revenue'], 2) }}</p>
                    </div>
                </div>

                <!-- Filtered data note -->
                @if ($selectedBranch && $selectedBranch != 'all')
                    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-sm text-blue-700">
                            <span class="font-semibold">Note:</span> Showing data filtered for {{ $selectedBranchName }} only.
                            To see data from all branches, click "All Branches" in the branch filter above.
                        </p>
                    </div>
                @endif

                <!-- Booking Revenue Details -->
                @if (count($stats['booking_revenue_breakdown']) > 0)
                    <div class="mb-6">
                        <h4 class="text-md font-medium text-gray-700 mb-3">Booking Payments</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Branch
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Category
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Payment Method
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Payments
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Total Amount
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($stats['booking_revenue_breakdown'] as $index => $payment)
                                        <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} hover:bg-gray-100">
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                {{ $payment['branch_name'] }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                @if ($payment['payment_category'] == 1)
                                                    Main Payment
                                                @else
                                                    Extension Payment
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                @if ($payment['payment_method'] == 0)
                                                    Cash
                                                @elseif($payment['payment_method'] == 1)
                                                    GCash
                                                @elseif($payment['payment_method'] == 2)
                                                    Debit Card
                                                @else
                                                    Other
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                {{ $payment['payment_count'] }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                ₱{{ number_format($payment['total_amount'], 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Order Revenue Details -->
                @if (count($stats['order_revenue_breakdown']) > 0)
                    <div>
                        <h4 class="text-md font-medium text-gray-700 mb-3">Order Payments</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Branch
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Payment Method
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Payments
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Total Amount
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($stats['order_revenue_breakdown'] as $index => $payment)
                                        <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} hover:bg-gray-100">
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                {{ $payment['branch_name'] }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                @if ($payment['payment_method'] == 0)
                                                    Cash
                                                @elseif($payment['payment_method'] == 1)
                                                    GCash
                                                @elseif($payment['payment_method'] == 2)
                                                    Debit Card
                                                @elseif($payment['payment_method'] == 3)
                                                    Pay Later
                                                @else
                                                    Other
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                {{ $payment['payment_count'] }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                ₱{{ number_format($payment['total_amount'], 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <!-- Branch Breakdown -->
        @if (count($stats['branch_breakdown']) > 0)
            <section id="branches" class="mb-8">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Branch Breakdown</h3>

                    @if ($selectedBranch && $selectedBranch != 'all')
                        <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <p class="text-sm text-yellow-700">
                                <span class="font-semibold">Note:</span> This section shows all branches where
                                {{ $staff->first_name }} has worked during the selected period.
                                The branch filter above only filters the activity data, not this branch list.
                            </p>
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Branch
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Bookings Handled
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($stats['branch_breakdown'] as $index => $branch)
                                    <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} hover:bg-gray-100">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $branch['branch_name'] }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $branch['booking_count'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif

        <!-- Services Breakdown -->
        @if (count($stats['service_breakdown']) > 0)
            <section id="services" class="mb-8">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Services Breakdown
                        @if ($selectedBranch && $selectedBranch != 'all')
                            ({{ $selectedBranchName }})
                        @else
                            (All Branches)
                        @endif
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Branch
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Service
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Category
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Bookings
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Revenue
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($stats['service_breakdown'] as $index => $service)
                                    <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} hover:bg-gray-100">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $service['branch_name'] }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $service['service_name'] }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $service['service_category'] }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $service['total_bookings'] }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            ₱{{ number_format($service['total_revenue'], 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif

        <!-- Orders Breakdown -->
        @if (count($stats['orders_breakdown']) > 0)
            <section id="orders" class="mb-8">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Orders Breakdown
                        @if ($selectedBranch && $selectedBranch != 'all')
                            ({{ $selectedBranchName }})
                        @else
                            (All Branches)
                        @endif
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Order Ref
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Items Count
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Total Amount
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($stats['orders_breakdown'] as $index => $order)
                                    <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} hover:bg-gray-100">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $order->order_ref_no }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ \Carbon\Carbon::parse($order->order_date)->format('M d, Y') }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if ($order->order_status == 1)
                                                <span
                                                    class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                                    Ordered
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $order->items->count() }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            ₱{{ number_format($order->payments->sum('total_amount'), 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif

        <!-- Order Items Breakdown -->
        @if (count($stats['order_items_breakdown']) > 0)
            <section id="products" class="mb-8">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Products Sold
                        @if ($selectedBranch && $selectedBranch != 'all')
                            ({{ $selectedBranchName }})
                        @else
                            (All Branches)
                        @endif
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Branch
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Product
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Quantity Sold
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Orders
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Revenue
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($stats['order_items_breakdown'] as $index => $item)
                                    <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} hover:bg-gray-100">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $item['branch_name'] }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $item['product_name'] }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $item['product_type'] }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $item['total_quantity_sold'] }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $item['order_count'] }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            ₱{{ number_format($item['total_revenue'], 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif

        <!-- Inventory Deduction -->
        @if (count($stats['inventory_deduction']['product_deduction']) > 0 ||
                count($stats['inventory_deduction']['ingredient_deduction']) > 0)
            <section id="inventory" class="mb-8">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Inventory Deduction
                        @if ($selectedBranch && $selectedBranch != 'all')
                            ({{ $selectedBranchName }})
                        @else
                            (All Branches)
                        @endif
                    </h3>

                    <!-- Products Deducted -->
                    @if (count($stats['inventory_deduction']['product_deduction']) > 0)
                        <div class="mb-6">
                            <h4 class="text-md font-medium text-gray-700 mb-3">Products Deducted</h4>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Branch
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Product
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Type
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Quantity Deducted
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Value Deducted
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach ($stats['inventory_deduction']['product_deduction'] as $index => $product)
                                            <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} hover:bg-gray-100">
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    {{ $product['branch_name'] }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    {{ $product['product_name'] }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    {{ $product['product_type'] }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    {{ $product['total_quantity_deducted'] }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    ₱{{ number_format($product['total_value_deducted'], 2) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <!-- Ingredients Deducted -->
                    @if (count($stats['inventory_deduction']['ingredient_deduction']) > 0)
                        <div>
                            <h4 class="text-md font-medium text-gray-700 mb-3">Ingredients Deducted</h4>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Branch
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Ingredient
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Type
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Quantity Deducted
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Unit
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach ($stats['inventory_deduction']['ingredient_deduction'] as $index => $ingredient)
                                            <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} hover:bg-gray-100">
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    {{ $ingredient->branch_name }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    {{ $ingredient->ingredient_name }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    {{ $ingredient->ingredient_type }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    {{ number_format($ingredient->total_quantity_deducted, 2) }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    {{ $ingredient->unit }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        @endif
    </div>

    <script>
        // Scroll to Top Button Functionality
        const scrollToTopBtn = document.getElementById('scrollToTopBtn');
        const originalSectionNav = document.getElementById('originalSectionNav');
        const stickySectionNav = document.getElementById('stickySectionNav');
        
        // Show/hide scroll-to-top button and handle sticky nav
        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset || document.documentElement.scrollTop;

            // Show/hide scroll-to-top button
            if (currentScroll > 300) {
                scrollToTopBtn.classList.remove('opacity-0', 'translate-y-10');
                scrollToTopBtn.classList.add('opacity-100', 'translate-y-0');
            } else {
                scrollToTopBtn.classList.remove('opacity-100', 'translate-y-0');
                scrollToTopBtn.classList.add('opacity-0', 'translate-y-10');
            }

            // Show/hide sticky nav when scrolling past original nav
            const originalNavOffset = originalSectionNav.offsetTop;
            const originalNavHeight = originalSectionNav.offsetHeight;

            if (currentScroll > originalNavOffset + originalNavHeight) {
                // Show sticky nav - slide down
                stickySectionNav.classList.remove('hidden');
                stickySectionNav.style.transform = 'translateY(0)';
            } else {
                // Hide sticky nav - slide up
                stickySectionNav.style.transform = 'translateY(-100%)';
            }

            // Update active section in navigation
            updateActiveSection();
        });

        // Scroll to top when button is clicked
        scrollToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Section Navigation Functionality - FIXED VERSION
        function setupNavigationLinks() {
            const sectionLinks = document.querySelectorAll('.section-nav-link');
            
            sectionLinks.forEach(link => {
                // Remove any existing click listeners
                link.removeEventListener('click', handleNavClick);
                // Add new click listener
                link.addEventListener('click', handleNavClick);
            });
        }

        function handleNavClick(e) {
    e.preventDefault();
    const targetId = this.getAttribute('href');
    const targetSection = document.querySelector(targetId);

    if (targetSection) {
        // Calculate the correct scroll position
        const isStickyNavVisible = window.getComputedStyle(stickySectionNav).transform !== 'matrix(1, 0, 0, 1, 0, -100)' && 
                                  !stickySectionNav.classList.contains('hidden');
        
        let navHeight;
        let targetPosition;
        
        if (isStickyNavVisible) {
            navHeight = stickySectionNav.offsetHeight;
            // When sticky nav is visible, scroll to position header just below it
            targetPosition = targetSection.offsetTop - navHeight;
        } else {
            navHeight = originalSectionNav.offsetHeight;
            // When at top, scroll to position with small padding
            targetPosition = targetSection.offsetTop - navHeight - 20;
        }
        
        // Get the section header
        const sectionHeader = targetSection.querySelector('h3');
        if (sectionHeader) {
            // Add extra offset to ensure header is fully visible
            const headerHeight = sectionHeader.offsetHeight;
            targetPosition -= headerHeight * 0.5; // Show half of header height above nav
        }

        window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
        });

        // Update active state
        updateActiveNavLink(targetId);
    }
}

        // Update active link in navigation
        function updateActiveNavLink(sectionId) {
            // Get all nav links from both navigation bars
            const allNavLinks = document.querySelectorAll('.section-nav-link');
            
            allNavLinks.forEach(link => {
                link.classList.remove('bg-[#7F5539]', 'text-white');
                link.classList.add('text-gray-600', 'hover:bg-gray-100');

                if (link.getAttribute('href') === sectionId) {
                    link.classList.remove('text-gray-600', 'hover:bg-gray-100');
                    link.classList.add('bg-[#7F5539]', 'text-white');
                }
            });
        }

        // Update active section in navigation based on scroll position
        function updateActiveSection() {
            let currentSection = '';
            const scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
            
            // Get all sections
            const sections = document.querySelectorAll('section[id]');
            
            // Determine which navigation is currently visible
            const isStickyNavVisible = window.getComputedStyle(stickySectionNav).transform !== 'matrix(1, 0, 0, 1, 0, -100)' && 
                                      !stickySectionNav.classList.contains('hidden');
            
            let navHeight;
            if (isStickyNavVisible) {
                navHeight = stickySectionNav.offsetHeight;
            } else {
                navHeight = originalSectionNav.offsetHeight;
            }
            
            // Calculate adjusted scroll position
            const adjustedScrollPosition = scrollPosition + navHeight + 100;

            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;

                if (adjustedScrollPosition >= sectionTop && adjustedScrollPosition < sectionTop + sectionHeight) {
                    currentSection = section.id;
                }
            });

            // Update active link if we found a section
            if (currentSection) {
                updateActiveNavLink(`#${currentSection}`);
            }
        }

        // Hide section nav on print
        window.addEventListener('beforeprint', () => {
            document.querySelectorAll('.no-print, #scrollToTopBtn, #stickySectionNav').forEach(el => {
                el.style.display = 'none';
            });
        });

        window.addEventListener('afterprint', () => {
            document.querySelectorAll('.no-print, #scrollToTopBtn, #stickySectionNav').forEach(el => {
                el.style.display = '';
            });
        });

        // Original date range functions
        function setDateRange(range) {
            const today = new Date();
            let startDate = new Date();
            let endDate = new Date();

            switch (range) {
                case 'today':
                    startDate = today;
                    endDate = today;
                    break;
                case 'yesterday':
                    startDate = new Date(today.setDate(today.getDate() - 1));
                    endDate = startDate;
                    break;
                case 'last7days':
                    startDate = new Date(today.setDate(today.getDate() - 7));
                    endDate = new Date();
                    break;
                case 'last30days':
                    startDate = new Date(today.setDate(today.getDate() - 30));
                    endDate = new Date();
                    break;
                case 'thismonth':
                    startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                    endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                    break;
                case 'lastmonth':
                    startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    endDate = new Date(today.getFullYear(), today.getMonth(), 0);
                    break;
            }

            document.getElementById('start_date').value = formatDate(startDate);
            document.getElementById('end_date').value = formatDate(endDate);
        }

        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // Initialize everything when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Set max dates for date inputs
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('end_date').max = today;
            document.getElementById('start_date').max = today;

            // Setup navigation links
            setupNavigationLinks();
            
            // Initialize active section
            updateActiveSection();

            // Branch Filter JavaScript
            // Clear Branch Filter buttons
            document.querySelectorAll('#clearBranchFilter, #clearBranchFilterMobile').forEach(button => {
                button.addEventListener('click', function() {
                    // Remove branch filter parameter
                    const url = new URL(window.location);
                    url.searchParams.delete('branch_filter');

                    // Preserve other parameters
                    const startDate = url.searchParams.get('start_date') || '';
                    const endDate = url.searchParams.get('end_date') || '';

                    // Create clean URL with preserved filters
                    let newUrl = window.location.pathname;
                    if (startDate || endDate) {
                        newUrl += '?';
                        if (startDate) newUrl += 'start_date=' + startDate;
                        if (endDate) newUrl += (startDate ? '&' : '') + 'end_date=' + endDate;
                    }

                    // Navigate to clean URL
                    window.location.href = newUrl;
                });
            });

            // Handle branch filter form submission while preserving date filters
            document.querySelectorAll('button[name="branch_filter"]').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Get current date filter values
                    const startDate = document.querySelector('input[name="start_date"]').value;
                    const endDate = document.querySelector('input[name="end_date"]').value;

                    // Create a form and submit
                    const form = document.createElement('form');
                    form.method = 'GET';
                    form.action = window.location.pathname;

                    // Add all necessary parameters
                    const branchFilterInput = document.createElement('input');
                    branchFilterInput.type = 'hidden';
                    branchFilterInput.name = 'branch_filter';
                    branchFilterInput.value = this.value;
                    form.appendChild(branchFilterInput);

                    if (startDate) {
                        const startDateInput = document.createElement('input');
                        startDateInput.type = 'hidden';
                        startDateInput.name = 'start_date';
                        startDateInput.value = startDate;
                        form.appendChild(startDateInput);
                    }

                    if (endDate) {
                        const endDateInput = document.createElement('input');
                        endDateInput.type = 'hidden';
                        endDateInput.name = 'end_date';
                        endDateInput.value = endDate;
                        form.appendChild(endDateInput);
                    }

                    document.body.appendChild(form);
                    form.submit();
                });
            });
        });
    </script>

    <style>
    @media print {
        .no-print {
            display: none !important;
        }

        #scrollToTopBtn,
        #stickySectionNav {
            display: none !important;
        }

        body {
            font-size: 12px;
        }

        .container {
            max-width: none;
            padding: 0;
        }

        .bg-white {
            background: white !important;
            border: 1px solid #e5e7eb !important;
        }
        
        /* Remove section padding for print */
        section {
            padding-top: 0 !important;
        }
    }

    /* Smooth scrolling for the whole page */
    html {
        scroll-behavior: smooth;
    }

    /* Smooth transition for sticky nav */
    #stickySectionNav {
        top: 63px;
        left: 0;
        right: 0;
        z-index: 50;
        transition: transform 0.3s ease-in-out;
    }

    /* Scroll to top button styles */
    #scrollToTopBtn {
        transition: all 0.3s ease-in-out;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    #scrollToTopBtn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
    }

    /* Section navigation link styles */
    .section-nav-link {
        white-space: nowrap;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .section-nav-link:hover {
        background-color: #f3f4f6;
    }

    /* Add padding to sections for better positioning */
    section[id] {
        position: relative;
        padding-top: 80px; /* Space for sticky nav */
        margin-top: -60px; /* Pull content up to overlap with padding */
        scroll-margin-top: 80px; /* Scroll positioning */
    }

    /* Ensure section headers are positioned correctly */
    section[id] > div {
        position: relative;
        z-index: 1;
    }

    /* Section headers styling */
    section h3 {
        margin-top: 0;
        padding-top: 10px;
        position: relative;
        z-index: 2;
    }

    /* When sticky nav is not visible, reduce the padding */
    body:not(.sticky-nav-active) section[id] {
        padding-top: 20px;
        margin-top: 0;
    }
</style>
@endsection