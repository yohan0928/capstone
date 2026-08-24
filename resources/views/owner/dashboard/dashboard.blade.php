@extends('layouts.app')

@section('title', 'Owner Dashboard')

@section('content')
    <!-- Header Section -->
    <div class="px-6 py-4" style="background-color: #f5f0eb; border-bottom: 1px solid #e6ddd4;">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center space-x-3">
                <div class="p-2 rounded-lg" style="background-color: #e6ddd4;">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #7f5539;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                        </path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-semibold" style="color: #4a3429;">
                        @if ($selectedBranch && $selectedBranch != 'all')
                            {{ $selectedBranchName }} Dashboard
                        @else
                            Dashboard
                        @endif
                    </h1>
                    <p class="text-sm" style="color: #7f5539;">
                        @if ($selectedBranch && $selectedBranch != 'all')
                            {{ $selectedBranchName }} overview and performance metrics
                        @else
                            Business overview and performance metrics
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center space-y-3 sm:space-y-0 sm:space-x-3 mt-4 sm:mt-0">
                <!-- Business Analytics Button -->
                <a href="{{ route('sub_one.business_analytics.showAnalytics') }}"
                    class="px-4 py-2 text-sm font-medium text-white rounded-lg hover:shadow-md transition-all duration-200 order-first sm:order-none inline-flex items-center justify-center shadow-sm"
                    style="background-color: #9c6644;">
                    Business Analytics
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="p-6" style="background-color: #f5f0eb;">
        <!-- Date Filter Section -->
        <div class="mb-6 p-4 bg-white rounded-lg shadow-sm" style="border: 1px solid #e6ddd4;">
            <form action="" method="GET" id="dashboardDateFilterForm">
                <!-- ===================== LARGE TO 2XL SCREENS (Desktop Layout) ===================== -->
                <div class="hidden lg:block">
                    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
                        <!-- Quick Date Buttons -->
                        <div>
                            <label class="block text-xs font-medium mb-1" style="color:#9c6644;">Quick Date Range</label>
                            <div class="inline-flex rounded-lg p-1"
                                style="background-color:#e6ddd4;border:1px solid #d4c4b2;">
                                <div class="flex gap-1">
                                    <button type="button" data-filter="daily"
                                        class="date-filter-btn py-2 px-4 rounded-md text-sm font-medium {{ $filterType === 'daily' ? 'text-white' : '' }}"
                                        style="{{ $filterType === 'daily' ? 'background-color:#9c6644;' : 'background-color:transparent;color:#7f5539;' }}">
                                        Last 24 Hours
                                    </button>
                                    <button type="button" data-filter="weekly"
                                        class="date-filter-btn py-2 px-4 rounded-md text-sm font-medium {{ $filterType === 'weekly' ? 'text-white' : '' }}"
                                        style="{{ $filterType === 'weekly' ? 'background-color:#9c6644;' : 'background-color:transparent;color:#7f5539;' }}">
                                        Last 7 Days
                                    </button>
                                    <button type="button" data-filter="monthly"
                                        class="date-filter-btn py-2 px-4 rounded-md text-sm font-medium {{ $filterType === 'monthly' ? 'text-white' : '' }}"
                                        style="{{ $filterType === 'monthly' ? 'background-color:#9c6644;' : 'background-color:transparent;color:#7f5539;' }}">
                                        Last 30 Days
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Date Inputs + Buttons -->
                        <div class="flex items-end gap-3">
                            <div>
                                <label class="block text-xs font-medium mb-1" style="color:#9c6644;">Date Range</label>
                                <div class="flex items-center gap-2">
                                    <input type="date" name="date_from"
                                        value="{{ $dateFrom->format('Y-m-d') }}"
                                        class="rounded-lg text-sm py-2 px-3 date-picker-input"
                                        style="border:1px solid #d4c4b2;min-width:150px;">
                                    <span style="color:#7f5539;">to</span>
                                    <input type="date" name="date_to"
                                        value="{{ $dateTo->format('Y-m-d') }}"
                                        class="rounded-lg text-sm py-2 px-3 date-picker-input"
                                        style="border:1px solid #d4c4b2;min-width:150px;">
                                    
                                    <!-- Buttons moved here to match logic grouping but kept inline for desktop -->
                                    <button type="button" id="applyCustomDate"
                                        class="px-4 py-2 text-white rounded-lg text-sm font-medium"
                                        style="background-color:#9c6644;">
                                        Apply
                                    </button>
                                    <button type="button" id="clearDashboardDateFilter"
                                        class="px-4 py-2 rounded-lg text-sm font-medium"
                                        style="background-color:#f5f0eb;border:1px solid #d4c4b2;color:#7f5539;">
                                        Clear
                                    </button>
                                </div>
                                <p class="text-xs mt-1" style="color:#9c6644;">Select dates and click Apply</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===================== SMALL + MEDIUM SCREENS (Mobile Layout) ===================== -->
                <div class="block lg:hidden">
                    <!-- Quick Date -->
                    <div class="mb-4">
                        <label class="block text-xs font-medium mb-1" style="color:#9c6644;">Quick Date</label>
                        <div class="inline-flex rounded-lg p-1 w-full"
                            style="background-color:#e6ddd4;border:1px solid #d4c4b2;">
                            <div class="flex w-full">
                                <button type="button" data-filter="daily"
                                    class="date-filter-btn flex-1 py-2 px-3 rounded-md text-sm font-medium {{ $filterType === 'daily' ? 'text-white' : '' }}"
                                    style="{{ $filterType === 'daily' ? 'background-color:#9c6644;' : 'background-color:transparent;color:#7f5539;' }}">
                                    24H
                                </button>
                                <button type="button" data-filter="weekly"
                                    class="date-filter-btn flex-1 py-2 px-3 rounded-md text-sm font-medium {{ $filterType === 'weekly' ? 'text-white' : '' }}"
                                    style="{{ $filterType === 'weekly' ? 'background-color:#9c6644;' : 'background-color:transparent;color:#7f5539;' }}">
                                    7D
                                </button>
                                <button type="button" data-filter="monthly"
                                    class="date-filter-btn flex-1 py-2 px-3 rounded-md text-sm font-medium {{ $filterType === 'monthly' ? 'text-white' : '' }}"
                                    style="{{ $filterType === 'monthly' ? 'background-color:#9c6644;' : 'background-color:transparent;color:#7f5539;' }}">
                                    30D
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Date Inputs -->
                    <div class="mb-3">
                        <label class="block text-xs font-medium mb-1" style="color:#9c6644;">Date Range</label>
                        <div class="grid grid-cols-2 gap-3 mb-2">
                            <div>
                                <input type="date" name="date_from"
                                    value="{{ $dateFrom->format('Y-m-d') }}"
                                    class="w-full rounded-lg text-sm py-2 px-3 date-picker-input"
                                    style="border:1px solid #d4c4b2;">
                            </div>
                            <div>
                                <input type="date" name="date_to"
                                    value="{{ $dateTo->format('Y-m-d') }}"
                                    class="w-full rounded-lg text-sm py-2 px-3 date-picker-input"
                                    style="border:1px solid #d4c4b2;">
                            </div>
                        </div>
                        <p class="text-xs mb-2" style="color:#9c6644; text-align: center;">Select dates and click Apply</p>
                        
                        <!-- Buttons -->
                        <div class="flex space-x-2">
                            <button type="button" id="applyCustomDateMobile"
                                class="flex-1 px-4 py-2 text-white rounded-lg text-sm font-medium"
                                style="background-color:#9c6644;">
                                Apply
                            </button>
                            <button type="button" id="clearDashboardDateFilterMobile"
                                class="flex-1 px-4 py-2 rounded-lg text-sm font-medium"
                                style="background-color:#f5f0eb;border:1px solid #d4c4b2;color:#7f5539;">
                                Clear
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Hidden Inputs -->
                <input type="hidden" name="filter" id="dash_filter_type" value="{{ $filterType }}">
                @if ($selectedBranch)
                    <input type="hidden" name="branch_filter" value="{{ $selectedBranch }}">
                @endif
            </form>
        </div>

        <!-- Branch Filter -->
        <div class="mb-6 p-4 bg-white rounded-lg shadow-sm" style="border: 1px solid #e6ddd4;">
            <form action="" method="GET" id="branchFilterForm">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <!-- Quick Branch Filter -->
                    <div class="inline-flex rounded-lg p-1 overflow-x-auto md:overflow-visible"
                        style="background-color: #e6ddd4; border: 1px solid #d4c4b2; min-width: 100%;">
                        <div class="flex space-x-0">
                            <button type="submit" name="branch_filter" value="all"
                                class="relative overflow-hidden transition-all duration-300 ease-in-out py-2 px-4 rounded-md text-sm font-medium cursor-pointer focus:z-10 focus:outline-none whitespace-nowrap {{ $selectedBranch == 'all' || !$selectedBranch ? 'text-white' : '' }}"
                                style="{{ $selectedBranch == 'all' || !$selectedBranch ? 'background-color: #9c6644;' : 'background-color: transparent; color: #7f5539;' }}">
                                All Branches
                            </button>
                            @foreach ($branches as $branch)
                                <button type="submit" name="branch_filter" value="{{ $branch->uuid }}"
                                    class="relative overflow-hidden transition-all duration-300 ease-in-out py-2 px-4 rounded-md text-sm font-medium cursor-pointer focus:z-10 focus:outline-none whitespace-nowrap {{ $selectedBranch == $branch->uuid ? 'text-white' : '' }}"
                                    style="{{ $selectedBranch == $branch->uuid ? 'background-color: #9c6644;' : 'background-color: transparent; color: #7f5539;' }}">
                                    {{ $branch->branch_name }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                    
                    <!-- Desktop: Clear Branch Filter Button -->
                    @if ($selectedBranch && $selectedBranch != 'all')
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
                <input type="hidden" name="filter" value="{{ $filterType }}">
                <input type="hidden" name="date_from" value="{{ $dateFrom->format('Y-m-d') }}">
                <input type="hidden" name="date_to" value="{{ $dateTo->format('Y-m-d') }}">
            </form>

            <!-- Mobile: Clear Branch Filter Button -->
            @if ($selectedBranch && $selectedBranch != 'all')
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

        <!-- Key Metrics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Bookings -->
            <div class="bg-white rounded-lg p-4 shadow-sm hover:shadow-md transition-all duration-200"
                style="border: 1px solid #e6ddd4;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide" style="color: #7f5539;">Total Bookings</p>
                        <p class="text-xl font-bold mt-1" style="color: #4a3429;">{{ $stats['bookings']['total'] ?? 0 }}
                        </p>
                        <p class="text-xs mt-1" style="color: #b08968;">
                            @if ($stats['bookings']['total'] > 0)
                                {{ round(($stats['bookings'][4] / $stats['bookings']['total']) * 100) }}% Completed
                            @else
                                0% Completed
                            @endif
                        </p>
                    </div>
                    <div class="p-2 rounded-lg" style="background-color: #e6ddd4;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2" style="color: #7f5539;">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total POS Orders -->
            <div class="bg-white rounded-lg p-4 shadow-sm hover:shadow-md transition-all duration-200"
                style="border: 1px solid #e6ddd4;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide" style="color: #7f5539;">Total POS Orders
                        </p>
                        <p class="text-xl font-bold mt-1" style="color: #4a3429;">{{ $stats['total_orders'] ?? 0 }}</p>
                        <p class="text-xs mt-1" style="color: #b08968;">
                            @if ($stats['total_orders'] > 0)
                                ₱{{ number_format(($stats['total_order_revenue'] ?? 0) / $stats['total_orders'], 2) }}
                                avg/order
                            @else
                                ₱0 avg/order
                            @endif
                        </p>
                    </div>
                    <div class="p-2 rounded-lg" style="background-color: #e6ddd4;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2" style="color: #7f5539;">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Booking Revenue -->
            <div class="bg-white rounded-lg p-4 shadow-sm hover:shadow-md transition-all duration-200"
                style="border: 1px solid #e6ddd4;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide" style="color: #7f5539;">Total Booking
                            Revenue</p>
                        <p class="text-xl font-bold mt-1" style="color: #4a3429;">
                            ₱{{ number_format($stats['total_booking_revenue'] ?? 0, 2) }}</p>
                        <p class="text-xs mt-1" style="color: #b08968;">
                            @if ($stats['bookings']['total'] > 0)
                                ₱{{ number_format(($stats['total_booking_revenue'] ?? 0) / $stats['bookings']['total'], 2) }}
                                avg/booking
                            @else
                                ₱0 avg/booking
                            @endif
                        </p>
                    </div>
                    <div class="p-2 rounded-lg" style="background-color: #e6ddd4;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" style="color: #7f5539;"
                            stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total POS Revenue -->
            <div class="bg-white rounded-lg p-4 shadow-sm hover:shadow-md transition-all duration-200"
                style="border: 1px solid #e6ddd4;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide" style="color: #7f5539;">Total POS Revenue
                        </p>
                        <p class="text-xl font-bold mt-1" style="color: #4a3429;">
                            ₱{{ number_format($stats['total_order_revenue'] ?? 0, 2) }}</p>
                        <p class="text-xs mt-1" style="color: #b08968;">
                            @if ($stats['total_order_revenue'] > 0 && $stats['total_booking_revenue'] > 0)
                                {{ round(($stats['total_order_revenue'] / ($stats['total_order_revenue'] + $stats['total_booking_revenue'])) * 100) }}%
                                of total
                            @else
                                0% of total
                            @endif
                        </p>
                    </div>
                    <div class="p-2 rounded-lg" style="background-color: #e6ddd4;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2" style="color: #7f5539;">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Three Column Bento Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column -->
            <div class="space-y-6">
                <!-- Revenue Trend - Line Chart -->
                <div class="bento-card"
                    style="background: white; border-radius: 12px; border: 1px solid #e6ddd4; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); display: flex; flex-direction: column; overflow: hidden; transition: all 0.3s ease; height: 300px;">
                    <div class="card-header"
                        style="padding: 16px; border-bottom: 1px solid #f5f0eb; background: linear-gradient(to right, #f5f0eb, #ffffff);">
                        <div class="flex items-center space-x-2">
                            <div class="p-1.5 rounded-md"
                                style="background: linear-gradient(to right, #9c6644, #6b4f3c);">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                    </path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold" style="color: #4a3429;">Revenue Trend</h3>
                                <p class="text-xs" style="color: #7f5539;">
                                    @if ($selectedBranch && $selectedBranch != 'all')
                                        Revenue for {{ $selectedBranchName }}
                                    @else
                                        Revenue over time
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="card-content" style="padding: 16px; flex: 1; display: flex; flex-direction: column;">
                        @if (isset($stats['revenue_trend']) && count($stats['revenue_trend']['series'] ?? []) > 0)
                            <div id="revenueTrendChart" class="chart-container"
                                style="position: relative; width: 100% !important; height: 100% !important; min-height: 200px;">
                            </div>
                        @else
                            <div class="h-full flex items-center justify-center rounded-lg"
                                style="background-color: #f5f0eb;">
                                <p class="text-sm" style="color: #b08968;">No revenue trend data available.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Top Selling Products - Bar Chart -->
                <div class="bento-card"
                    style="background: white; border-radius: 12px; border: 1px solid #e6ddd4; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); display: flex; flex-direction: column; overflow: hidden; transition: all 0.3s ease; height: 300px;">
                    <div class="card-header"
                        style="padding: 16px; border-bottom: 1px solid #f5f0eb; background: linear-gradient(to right, #f5f0eb, #ffffff);">
                        <div class="flex items-center space-x-2">
                            <div class="p-1.5 rounded-md"
                                style="background: linear-gradient(to right, #b08968, #7f5539);">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold" style="color: #4a3429;">Top Selling Products</h3>
                                <p class="text-xs" style="color: #7f5539;">Most popular products</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-content" style="padding: 16px; flex: 1; display: flex; flex-direction: column;">
                        @if (isset($stats['top_products']) && count($stats['top_products']) > 0)
                            <div id="topProductsChart" class="chart-container"
                                style="position: relative; width: 100% !important; height: 100% !important; min-height: 200px;">
                            </div>
                        @else
                            <div class="h-full flex items-center justify-center rounded-lg"
                                style="background-color: #f5f0eb;">
                                <p class="text-sm" style="color: #b08968;">No product data available.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Booking vs POS Revenue - Stacked Bar Chart -->
                <div class="bento-card"
                    style="background: white; border-radius: 12px; border: 1px solid #e6ddd4; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); display: flex; flex-direction: column; overflow: hidden; transition: all 0.3s ease; height: 300px;">
                    <div class="card-header"
                        style="padding: 16px; border-bottom: 1px solid #f5f0eb; background: linear-gradient(to right, #f5f0eb, #ffffff);">
                        <div class="flex items-center space-x-2">
                            <div class="p-1.5 rounded-md"
                                style="background: linear-gradient(to right, #7f5539, #5c4033);">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                    </path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold" style="color: #4a3429;">Revenue Split</h3>
                                <p class="text-xs" style="color: #7f5539;">Booking vs POS Revenue</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-content" style="padding: 16px; flex: 1; display: flex; flex-direction: column;">
                        <div id="revenueSplitChart" class="chart-container"
                            style="position: relative; width: 100% !important; height: 100% !important; min-height: 200px;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Middle Column -->
            <div class="space-y-6">
                <!-- Peak Booking Hours - Column Chart -->
                <div class="bento-card"
                    style="background: white; border-radius: 12px; border: 1px solid #e6ddd4; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); display: flex; flex-direction: column; overflow: hidden; transition: all 0.3s ease; height: 300px;">
                    <div class="card-header"
                        style="padding: 16px; border-bottom: 1px solid #f5f0eb; background: linear-gradient(to right, #f5f0eb, #ffffff);">
                        <div class="flex items-center space-x-2">
                            <div class="p-1.5 rounded-md"
                                style="background: linear-gradient(to right, #6b4f3c, #4a3429);">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold" style="color: #4a3429;">Peak Booking Hours</h3>
                                <p class="text-xs" style="color: #7f5539;">Most popular booking times</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-content" style="padding: 16px; flex: 1; display: flex; flex-direction: column;">
                        @if (count($stats['peak_hours']) > 0)
                            <div id="peakHoursChart" class="chart-container"
                                style="position: relative; width: 100% !important; height: 100% !important; min-height: 200px;">
                            </div>
                        @else
                            <div class="h-full flex items-center justify-center rounded-lg"
                                style="background-color: #f5f0eb;">
                                <p class="text-sm" style="color: #b08968;">No peak hours data for this period.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Top Customers - Radar Chart -->
                <div class="bento-card"
                    style="background: white; border-radius: 12px; border: 1px solid #e6ddd4; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); display: flex; flex-direction: column; overflow: hidden; transition: all 0.3s ease; height: 300px;">
                    <div class="card-header"
                        style="padding: 16px; border-bottom: 1px solid #f5f0eb; background: linear-gradient(to right, #f5f0eb, #ffffff);">
                        <div class="flex items-center space-x-2">
                            <div class="p-1.5 rounded-md"
                                style="background: linear-gradient(to right, #9c6644, #4a3429);">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                    </path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold" style="color: #4a3429;">Customer Activity</h3>
                                <p class="text-xs" style="color: #7f5539;">Booking frequency analysis</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-content" style="padding: 16px; flex: 1; display: flex; flex-direction: column;">
                        @if (isset($stats['top_customers']) && count($stats['top_customers']) > 0)
                            <div id="customerActivityChart" class="chart-container"
                                style="position: relative; width: 100% !important; height: 100% !important; min-height: 200px;">
                            </div>
                        @else
                            <div class="h-full flex items-center justify-center rounded-lg"
                                style="background-color: #f5f0eb;">
                                <p class="text-sm" style="color: #b08968;">No customer data available.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Low Stock Alerts - Inventory Health -->
                <div class="bento-card"
                    style="background: white; border-radius: 12px; border: 1px solid #e6ddd4; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); display: flex; flex-direction: column; overflow: hidden; transition: all 0.3s ease; height: 300px;">
                    <div class="card-header"
                        style="padding: 16px; border-bottom: 1px solid #f5f0eb; background: linear-gradient(to right, #f5f0eb, #ffffff);">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <div class="p-1.5 rounded-md"
                                    style="background: linear-gradient(to right, #dc2626, #ea580c);">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z">
                                        </path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold" style="color: #4a3429;">Inventory Health</h3>
                                    <p class="text-xs" style="color: #7f5539;">Stock level monitoring</p>
                                </div>
                            </div>
                            @if (count($stats['low_stock_products']) > 0 || count($stats['low_stock_ingredients']) > 0)
                                <a href="{{ route('sub_one.inventory.index') }}"
                                    class="text-xs font-semibold px-2.5 py-1 rounded-full hover:opacity-80 transition-opacity whitespace-nowrap"
                                    style="background-color: #fee2e2; color: #dc2626;">
                                    {{ count($stats['low_stock_products']) + count($stats['low_stock_ingredients']) }} item(s) →
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="card-content" style="padding: 0; flex: 1; display: flex; flex-direction: column; overflow: hidden;">
                        @if (count($stats['low_stock_products']) > 0 || count($stats['low_stock_ingredients']) > 0)
                            <div id="inventoryHealthChart" style="height: 100%; width: 100%;"></div>
                        @else
                            <div class="h-full flex items-center justify-center rounded-lg"
                                style="background-color: #f5f0eb;">
                                <p class="text-sm" style="color: #b08968;">All items are well-stocked.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <!-- Branch Performance - Horizontal Bar Chart -->
                <div class="bento-card"
                    style="background: white; border-radius: 12px; border: 1px solid #e6ddd4; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); display: flex; flex-direction: column; overflow: hidden; transition: all 0.3s ease; height: 300px;">
                    <div class="card-header"
                        style="padding: 16px; border-bottom: 1px solid #f5f0eb; background: linear-gradient(to right, #f5f0eb, #ffffff);">
                        <div class="flex items-center space-x-2">
                            <div class="p-1.5 rounded-md"
                                style="background: linear-gradient(to right, #5c4033, #4a3429);">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                    </path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold" style="color: #4a3429;">Branch Performance</h3>
                                <p class="text-xs" style="color: #7f5539;">Revenue by branch</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-content" style="padding: 16px; flex: 1; display: flex; flex-direction: column;">
                        @if (isset($stats['branch_performance']) && count($stats['branch_performance']) > 0)
                            <div id="branchPerformanceChart" class="chart-container"
                                style="position: relative; width: 100% !important; height: 100% !important; min-height: 200px;">
                            </div>
                        @else
                            <div class="h-full flex items-center justify-center rounded-lg"
                                style="background-color: #f5f0eb;">
                                <p class="text-sm" style="color: #b08968;">
                                    @if ($selectedBranch && $selectedBranch != 'all')
                                        Branch performance chart available when viewing "All Branches"
                                    @else
                                        No branch data available.
                                    @endif
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Revenue by Payment Method - Donut Chart -->
                <div class="bento-card"
                    style="background: white; border-radius: 12px; border: 1px solid #e6ddd4; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); display: flex; flex-direction: column; overflow: hidden; transition: all 0.3s ease; height: 300px;">
                    <div class="card-header"
                        style="padding: 16px; border-bottom: 1px solid #f5f0eb; background: linear-gradient(to right, #f5f0eb, #ffffff);">
                        <div class="flex items-center space-x-2">
                            <div class="p-1.5 rounded-md"
                                style="background: linear-gradient(to right, #b08968, #5c4033);">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0H9m9 0h-3m-6 0H6m6 0v6m6-6v6m-6-6v6" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold" style="color: #4a3429;">Payment Methods</h3>
                                <p class="text-xs" style="color: #7f5539;">Payment method distribution</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-content" style="padding: 16px; flex: 1; display: flex; flex-direction: column;">
                        @if (count($stats['payment_totals'] ?? []) > 0)
                            <div id="paymentMethodChart" class="chart-container"
                                style="position: relative; width: 100% !important; height: 100% !important; min-height: 200px;">
                            </div>
                        @else
                            <div class="h-full flex items-center justify-center rounded-lg"
                                style="background-color: #f5f0eb;">
                                <p class="text-sm" style="color: #b08968;">No payment data for this period.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Booking Status - Polar Area Chart -->
                <div class="bento-card"
                    style="background: white; border-radius: 12px; border: 1px solid #e6ddd4; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); display: flex; flex-direction: column; overflow: hidden; transition: all 0.3s ease; height: 300px;">
                    <div class="card-header"
                        style="padding: 16px; border-bottom: 1px solid #f5f0eb; background: linear-gradient(to right, #f5f0eb, #ffffff);">
                        <div class="flex items-center space-x-2">
                            <div class="p-1.5 rounded-md"
                                style="background: linear-gradient(to right, #7f5539, #4a3429);">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                    </path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold" style="color: #4a3429;">Booking Status</h3>
                                <p class="text-xs" style="color: #7f5539;">Distribution of booking statuses</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-content" style="padding: 16px; flex: 1; display: flex; flex-direction: column;">
                        @if ($stats['bookings']['total'] > 0)
                            <div id="bookingStatusChart" class="chart-container"
                                style="position: relative; width: 100% !important; height: 100% !important; min-height: 200px;">
                            </div>
                        @else
                            <div class="h-full flex items-center justify-center rounded-lg"
                                style="background-color: #f5f0eb;">
                                <p class="text-sm" style="color: #b08968;">No booking data for this period.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    /* Mobile-specific styles */
    @media (max-width: 768px) {
        /* Make the date filter buttons container full width */
        #dateFilterForm .inline-flex {
            width: 100% !important;
        }
        
        /* Make the date filter buttons fill available space */
        #dateFilterForm .inline-flex .flex {
            width: 100% !important;
        }
        
        /* Make the date input fields container full width */
        #dateFilterForm .flex-col.md\\:flex-row.md\\:items-center.md\\:space-x-3 {
            width: 100% !important;
        }
        
        /* Make the date inputs full width */
        #dateFilterForm .flex-col.md\\:flex-row.md\\:space-x-2.w-full {
            width: 100% !important;
        }
        
        /* Branch filter improvements */
        #branchFilterForm .inline-flex {
            width: 100% !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
        }
        
        #branchFilterForm .inline-flex .flex {
            min-width: max-content !important;
        }
    }
    
    .date-picker-input:focus {
        outline: none;
        border-color: #9c6644 !important;
        box-shadow: 0 0 0 2px rgba(156, 102, 68, 0.1) !important;
    }
    
    .date-error {
        animation: slideDown 0.3s ease-out;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endpush

@push('scripts')
    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Global variables for charts
        let bookingStatusChart, peakHoursChart, paymentMethodChart, topProductsChart,
            revenueTrendChart, branchPerformanceChart, revenueSplitChart,
            customerActivityChart, inventoryHealthChart;

        $(document).ready(function() {
            // Initialize all charts
            initializeCharts();

            // Handle window resize
            let resizeTimer;
            $(window).resize(function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    resizeCharts();
                }, 250);
            });

            // Initialize date pickers
            initializeDashboardDatePickers();

            // Set initial button style based on current filter
            // Improved Logic: Explicitly reset all first, then apply active style to ensure consistency
            const currentFilter = "{{ $filterType }}";
            
            // 1. Reset all buttons to inactive state
            $('.date-filter-btn')
                .removeClass('text-white')
                .css('background-color', 'transparent')
                .css('color', '#7f5539');

            // 2. Apply active state if a specific filter is selected (and it's not custom)
            if (currentFilter && currentFilter !== 'custom') {
                $(`.date-filter-btn[data-filter="${currentFilter}"]`)
                    .addClass('text-white')
                    .css('background-color', '#9c6644')
                    .css('color', '#ffffff');
            }

            // Variables to track date selection state
            let dateSelectionTimeout = null;

            // Handle Apply button clicks
            $('#applyCustomDate, #applyCustomDateMobile').on('click', function() {
                applyCustomDateRange();
            });

            // Handle date picker changes - only validate, don't auto-submit
            $('.date-picker-input').on('change', function() {
                // Sync values between desktop/mobile inputs to prevent hidden inputs from overwriting submitted data
                const name = $(this).attr('name');
                const val = $(this).val();
                $(`input[name="${name}"]`).not(this).val(val);

                // Fix: Target only visible inputs to ensure we get value from the active view (mobile or desktop)
                const dateFrom = $('input[name="date_from"]:visible').val();
                const dateTo = $('input[name="date_to"]:visible').val();
                
                // Clear any error messages
                $('.date-error').remove();
                
                // Clear any previous timeout
                if (dateSelectionTimeout) {
                    clearTimeout(dateSelectionTimeout);
                    dateSelectionTimeout = null;
                }
                
                // If both dates are selected, validate dates
                if (dateFrom && dateTo) {
                    if (dateFrom > dateTo) {
                        showDashboardDateError('Start date cannot be after end date.');
                        return;
                    }
                    
                    // If dates are valid, remove highlighting from quick filter buttons
                    $('.date-filter-btn').removeClass('text-white').css('background-color', 'transparent');
                } 
                // If only one date is selected, check if we should show an error
                else if ((dateFrom && !dateTo) || (!dateFrom && dateTo)) {
                    // Wait 1.5 seconds, then check if still only one date is selected
                    dateSelectionTimeout = setTimeout(() => {
                        const currentDateFrom = $('input[name="date_from"]:visible').val();
                        const currentDateTo = $('input[name="date_to"]:visible').val();
                        
                        // Only show error if still only one date is selected
                        if ((currentDateFrom && !currentDateTo) || (!currentDateFrom && currentDateTo)) {
                            showDashboardDateError('Please select both start and end dates to apply custom range.');
                        }
                    }, 1500);
                }
            });

            // Handle quick date filter buttons
            $('.date-filter-btn').on('click', function() {
                const filterType = $(this).data('filter');
                
                // Update hidden filter field
                $('#dash_filter_type').val(filterType);
                
                // Update button styles - Target ALL buttons with this filter (desktop & mobile)
                $('.date-filter-btn').removeClass('text-white').css('background-color', 'transparent').css('color', '#7f5539');
                $(`.date-filter-btn[data-filter="${filterType}"]`).addClass('text-white').css('background-color', '#9c6644').css('color', '#ffffff');
                
                // Clear any error messages
                $('.date-error').remove();
                
                // Clear any pending timeouts
                if (dateSelectionTimeout) {
                    clearTimeout(dateSelectionTimeout);
                    dateSelectionTimeout = null;
                }
                
                // Calculate dates based on filter type
                const today = new Date();
                let startDate = new Date();
                
                switch(filterType) {
                    case 'daily':
                        startDate.setDate(today.getDate() - 1);
                        break;
                    case 'weekly':
                        startDate.setDate(today.getDate() - 7);
                        break;
                    case 'monthly':
                    default:
                        startDate.setDate(today.getDate() - 30);
                        break;
                }
                
                // Update date inputs
                const sDate = formatDate(startDate);
                const eDate = formatDate(today);
                
                // Update all inputs (both mobile and desktop)
                $('input[name="date_from"]').val(sDate);
                $('input[name="date_to"]').val(eDate);
                
                // Submit the form
                $('#dashboardDateFilterForm').submit();
            });

            // Clear Date Filter buttons
            $('#clearDashboardDateFilter, #clearDashboardDateFilterMobile').on('click', function() {
                clearDashboardDateFilters();
            });

            // Clear Branch Filter buttons
            $('#clearBranchFilter, #clearBranchFilterMobile').on('click', function() {
                // Remove branch filter parameter
                const url = new URL(window.location);
                url.searchParams.delete('branch_filter');

                // Preserve other parameters
                const filterType = $('#dash_filter_type').val() || 'monthly';
                const dateFrom = $('input[name="date_from"]:visible').val();
                const dateTo = $('input[name="date_to"]:visible').val();

                // Create clean URL with preserved filters
                let newUrl = window.location.pathname + '?filter=' + filterType;
                if (dateFrom) {
                    newUrl += '&date_from=' + dateFrom;
                }
                if (dateTo) {
                    newUrl += '&date_to=' + dateTo;
                }

                // Navigate to clean URL
                window.location.href = newUrl;
            });

            // Handle branch filter form submission while preserving date filters
            $('button[name="branch_filter"]').on('click', function(e) {
                e.preventDefault();

                // Get current date filter values
                const filterType = $('#dash_filter_type').val();
                const dateFrom = $('input[name="date_from"]:visible').val();
                const dateTo = $('input[name="date_to"]:visible').val();

                // Create a form and submit
                const form = $('<form>').attr({
                    method: 'GET',
                    action: window.location.pathname
                });

                // Add all necessary parameters
                form.append($('<input>').attr({
                    type: 'hidden',
                    name: 'branch_filter',
                    value: $(this).val()
                }));

                form.append($('<input>').attr({
                    type: 'hidden',
                    name: 'filter',
                    value: filterType
                }));

                if (dateFrom) {
                    form.append($('<input>').attr({
                        type: 'hidden',
                        name: 'date_from',
                        value: dateFrom
                    }));
                }

                if (dateTo) {
                    form.append($('<input>').attr({
                        type: 'hidden',
                        name: 'date_to',
                        value: dateTo
                    }));
                }

                $('body').append(form);
                form.submit();
            });
        });

        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function initializeDashboardDatePickers() {
            const today = new Date().toISOString().split('T')[0];
            
            // Set max dates
            $('input[name="date_to"]').attr('max', today);
            $('input[name="date_from"]').attr('max', today);
        }

        function applyCustomDateRange() {
            // Sync visible values to all inputs (hidden ones included) before submitting
            // This prevents the "duplicate input" bug where hidden inputs overwrite visible ones
            const visibleFrom = $('input[name="date_from"]:visible').val();
            const visibleTo = $('input[name="date_to"]:visible').val();
            
            $('input[name="date_from"]').val(visibleFrom);
            $('input[name="date_to"]').val(visibleTo);

            // Fix: Target only visible inputs to ensure we get value from the active view
            const dateFrom = visibleFrom;
            const dateTo = visibleTo;
            
            // Clear any error messages
            $('.date-error').remove();
            
            // Validate dates
            if (!dateFrom || !dateTo) {
                showDashboardDateError('Please select both start and end dates.');
                return;
            }
            
            if (dateFrom > dateTo) {
                showDashboardDateError('Start date cannot be after end date.');
                return;
            }
            
            // IMPORTANT: Set filter type to custom
            $('#dash_filter_type').val('custom');
            
            // Remove highlighting from quick filter buttons
            $('.date-filter-btn').removeClass('text-white').css('background-color', 'transparent');
            
            // Submit the form
            $('#dashboardDateFilterForm').submit();
        }

        function clearDashboardDateFilters() {
            // Direct URL reset: Remove date parameters and reload to default state
            const url = new URL(window.location);
            url.searchParams.delete('date_from');
            url.searchParams.delete('date_to');
            url.searchParams.delete('filter');
            
            // Preserve branch filter if it exists
            // (branch_filter is already in the URL object, we just don't delete it)

            window.location.href = url.toString();
        }

        function showDashboardDateError(message) {
            $('.date-error').remove();
            const errorHtml = `
                <div class="date-error mt-2 p-2 text-xs rounded" 
                     style="background-color: #fee2e2; border: 1px solid #fecaca; color: #dc2626;">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        ${message}
                    </div>
                </div>
            `;
            $('#dashboardDateFilterForm').append(errorHtml);
            setTimeout(() => {
                $('.date-error').fadeOut(300, function() { $(this).remove(); });
            }, 5000);
        }

        function initializeCharts() {
            // Coffee pastel color palette
            const coffeeColors = {
                50: '#f5f0eb',
                100: '#e6ddd4',
                200: '#d4c4b2',
                300: '#c2ab90',
                400: '#b08968',
                500: '#9c6644',
                600: '#7f5539',
                700: '#6b4f3c',
                800: '#5c4033',
                900: '#4a3429'
            };

            // 1. Revenue Trend Chart (Line Chart with Area)
            if (document.getElementById('revenueTrendChart')) {
                const revenueTrendData = @json($stats['revenue_trend'] ?? []);

                let categories, seriesData;

                if (revenueTrendData.categories && revenueTrendData.series) {
                    categories = revenueTrendData.categories;
                    seriesData = revenueTrendData.series;
                } else {
                    // Sample data for demo
                    categories = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                    seriesData = [3100, 4000, 2800, 5100, 4200, 6900, 5000];
                }

                seriesData = seriesData.map(value => typeof value === 'number' ? value : parseFloat(value) || 0);

                revenueTrendChart = new ApexCharts(document.querySelector("#revenueTrendChart"), {
                    series: [{
                        name: 'Revenue',
                        data: seriesData
                    }],
                    chart: {
                        type: 'area',
                        height: '100%',
                        fontFamily: 'Inter, sans-serif',
                        toolbar: {
                            show: false
                        },
                        zoom: {
                            enabled: false
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 2,
                        colors: [coffeeColors['600']]
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.6,
                            opacityTo: 0.1,
                            stops: [0, 90, 100],
                            colorStops: [{
                                    offset: 0,
                                    color: coffeeColors['500'],
                                    opacity: 0.4
                                },
                                {
                                    offset: 100,
                                    color: coffeeColors['100'],
                                    opacity: 0.1
                                }
                            ]
                        }
                    },
                    colors: [coffeeColors['500']],
                    xaxis: {
                        categories: categories,
                        labels: {
                            style: {
                                fontSize: '10px',
                                colors: coffeeColors['700']
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                fontSize: '10px',
                                colors: coffeeColors['700']
                            },
                            formatter: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    },
                    grid: {
                        borderColor: coffeeColors['100'],
                        strokeDashArray: 3
                    },
                    markers: {
                        size: 4,
                        colors: [coffeeColors['500']],
                        strokeColors: '#fff',
                        strokeWidth: 2,
                        hover: {
                            size: 6
                        }
                    }
                });
                revenueTrendChart.render();
            }

            // 2. Top Selling Products Chart (Bar Chart)
            if (document.getElementById('topProductsChart')) {
                const topProductsData = @json($stats['top_products'] ?? []);
                let categories, seriesData;

                if (topProductsData.length > 0) {
                    categories = topProductsData.map(p => p.product_name);
                    seriesData = topProductsData.map(p => p.count);
                } else {
                    categories = ['Espresso', 'Cappuccino', 'Latte', 'Americano', 'Mocha'];
                    seriesData = [45, 32, 28, 22, 18];
                }

                topProductsChart = new ApexCharts(document.querySelector("#topProductsChart"), {
                    series: [{
                        name: 'Units Sold',
                        data: seriesData
                    }],
                    chart: {
                        type: 'bar',
                        height: '100%',
                        fontFamily: 'Inter, sans-serif',
                        toolbar: {
                            show: false
                        },
                        animations: {
                            enabled: true,
                            easing: 'easeinout',
                            speed: 800,
                            animateGradually: {
                                enabled: true,
                                delay: 150
                            },
                            dynamicAnimation: {
                                enabled: true,
                                speed: 350
                            }
                        }
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 0,
                            horizontal: true,
                            barHeight: '70%',
                            distributed: false,
                            dataLabels: {
                                position: 'center'
                            }
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        textAnchor: 'start',
                        style: {
                            fontSize: '11px',
                            fontWeight: '600',
                            colors: ['#fff']
                        },
                        formatter: function(val, opt) {
                            return val + ' units';
                        },
                        offsetX: 10
                    },
                    colors: [coffeeColors['500']],
                    xaxis: {
                        categories: categories,
                        labels: {
                            style: {
                                fontSize: '10px',
                                colors: coffeeColors['700']
                            }
                        },
                        axisBorder: {
                            show: false
                        },
                        axisTicks: {
                            show: false
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                fontSize: '10px',
                                colors: coffeeColors['700']
                            },
                            offsetX: -5
                        }
                    },
                    grid: {
                        borderColor: coffeeColors['100'],
                        strokeDashArray: 3,
                        xaxis: {
                            lines: {
                                show: true
                            }
                        },
                        yaxis: {
                            lines: {
                                show: false
                            }
                        }
                    },
                    tooltip: {
                        custom: function({
                            series,
                            seriesIndex,
                            dataPointIndex,
                            w
                        }) {
                            const product = categories[dataPointIndex];
                            const sales = series[seriesIndex][dataPointIndex];
                            const percentage = (sales / series[seriesIndex].reduce((a, b) => a + b, 0) * 100)
                                .toFixed(1);

                            return `
                    <div class="p-3 bg-white border border-coffee-200 rounded-lg shadow-lg" style="min-width: 180px;">
                        <div class="font-semibold text-coffee-900 mb-2">${product}</div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-coffee-600">Units Sold:</span>
                            <span class="text-sm font-semibold text-coffee-800">${sales}</span>
                        </div>
                        <div class="flex justify-between items-center mt-1">
                            <span class="text-sm text-coffee-600">Share:</span>
                            <span class="text-sm font-semibold text-coffee-800">${percentage}%</span>
                        </div>
                        <div class="mt-2 pt-2 border-t border-coffee-100">
                            <div class="text-xs text-coffee-500">Top selling product</div>
                        </div>
                    </div>
                `;
                        }
                    }
                });
                topProductsChart.render();
            }

            // 3. Revenue Split Chart (Stacked Bar Chart - POS in front, Booking at back)
            if (document.getElementById('revenueSplitChart')) {
                const bookingRevenue = {{ $stats['total_booking_revenue'] ?? 0 }};
                const posRevenue = {{ $stats['total_order_revenue'] ?? 0 }};
                const totalRevenue = bookingRevenue + posRevenue;

                // Calculate percentages
                const bookingPercentage = totalRevenue > 0 ? (bookingRevenue / totalRevenue * 100).toFixed(1) : 0;
                const posPercentage = totalRevenue > 0 ? (posRevenue / totalRevenue * 100).toFixed(1) : 0;

                revenueSplitChart = new ApexCharts(document.querySelector("#revenueSplitChart"), {
                    series: [{
                        name: 'POS Revenue',
                        data: [posRevenue]
                    }, {
                        name: 'Booking Revenue',
                        data: [bookingRevenue]
                    }],
                    chart: {
                        type: 'bar',
                        height: '100%',
                        fontFamily: 'Inter, sans-serif',
                        stacked: true,
                        stackType: '100%',
                        toolbar: {
                            show: false
                        },
                        animations: {
                            enabled: true,
                            easing: 'easeinout',
                            speed: 800
                        }
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 0,
                            horizontal: false,
                            columnWidth: '60%',
                            dataLabels: {
                                position: 'center'
                            }
                        }
                    },
                    colors: [coffeeColors['400'], coffeeColors['600']], // POS lighter, Booking darker
                    dataLabels: {
                        enabled: false, // Disabled data labels on bars
                        formatter: function(val, {
                            seriesIndex,
                            dataPointIndex,
                            w
                        }) {
                            return ''; // Empty string to ensure nothing shows
                        }
                    },
                    stroke: {
                        width: 1,
                        colors: ['#fff']
                    },
                    xaxis: {
                        categories: ['Revenue Distribution'],
                        labels: {
                            show: false
                        },
                        axisBorder: {
                            show: false
                        },
                        axisTicks: {
                            show: false
                        }
                    },
                    yaxis: {
                        show: false,
                        labels: {
                            show: false
                        }
                    },
                    grid: {
                        show: false,
                        padding: {
                            top: -20,
                            bottom: -10
                        }
                    },
                    legend: {
                        position: 'bottom',
                        fontSize: '11px',
                        markers: {
                            size: 8,
                            shape: 'square'
                        },
                        itemMargin: {
                            horizontal: 10,
                            vertical: 5
                        }
                    },
                    tooltip: {
                        shared: true,
                        intersect: false,
                        custom: function({
                            series,
                            seriesIndex,
                            dataPointIndex,
                            w
                        }) {
                            const posRev = series[0][dataPointIndex];
                            const bookingRev = series[1][dataPointIndex];
                            const total = posRev + bookingRev;

                            return `
                    <div class="p-3 bg-white border border-coffee-200 rounded-lg shadow-lg" style="min-width: 220px;">
                        <div class="font-semibold text-coffee-900 mb-2">Revenue Distribution</div>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded mr-2" style="background-color: ${coffeeColors['400']}"></div>
                                    <span class="text-sm text-coffee-700">POS Revenue</span>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-semibold text-coffee-800">₱${Math.round(posRev).toLocaleString()}</div>
                                    <div class="text-xs text-coffee-600">${total > 0 ? ((posRev / total * 100).toFixed(1)) : 0}%</div>
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded mr-2" style="background-color: ${coffeeColors['600']}"></div>
                                    <span class="text-sm text-coffee-700">Booking Revenue</span>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-semibold text-coffee-800">₱${Math.round(bookingRev).toLocaleString()}</div>
                                    <div class="text-xs text-coffee-600">${total > 0 ? ((bookingRev / total * 100).toFixed(1)) : 0}%</div>
                                </div>
                            </div>
                            <div class="pt-2 mt-2 border-t border-coffee-100">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-coffee-900">Total Revenue</span>
                                    <span class="text-sm font-bold text-coffee-900">₱${Math.round(total).toLocaleString()}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                        }
                    },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            plotOptions: {
                                bar: {
                                    columnWidth: '70%'
                                }
                            }
                        }
                    }]
                });
                revenueSplitChart.render();
            }

            // 4. Peak Hours Chart (Column Chart)
            if (document.getElementById('peakHoursChart') && {{ count($stats['peak_hours']) > 0 ? 'true' : 'false' }}) {
                const peakHours = @json($stats['peak_hours']);
                const categories = peakHours.map(h => h.hour_formatted);
                const seriesData = peakHours.map(h => h.count);

                peakHoursChart = new ApexCharts(document.querySelector("#peakHoursChart"), {
                    series: [{
                        name: 'Bookings',
                        data: seriesData
                    }],
                    chart: {
                        type: 'bar',
                        height: '100%',
                        fontFamily: 'Inter, sans-serif',
                        toolbar: {
                            show: false
                        }
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 0,
                            columnWidth: '60%',
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    colors: [coffeeColors['500']],
                    xaxis: {
                        categories: categories,
                        labels: {
                            style: {
                                fontSize: '10px',
                                colors: coffeeColors['700']
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                fontSize: '10px',
                                colors: coffeeColors['700']
                            }
                        }
                    },
                    grid: {
                        borderColor: coffeeColors['100'],
                        strokeDashArray: 3
                    }
                });
                peakHoursChart.render();
            }

            // 5. Customer Activity Chart (Table View)
            if (document.getElementById('customerActivityChart')) {
                const topCustomersData = @json($stats['top_customers'] ?? []);
                const container = document.getElementById('customerActivityChart');

                // Remove padding from parent card-content to allow table to be full width
                // This makes it fit perfectly inside the card style
                const cardContent = container.closest('.card-content');
                if (cardContent) {
                    cardContent.style.padding = '0';
                }

                container.innerHTML = '';
                container.style.height = '100%';
                container.style.width = '100%';

                if (topCustomersData.length > 0) {
                    const sortedCustomers = [...topCustomersData].sort((a, b) =>
                        (b.booking_count || b.count || 0) - (a.booking_count || b.count || 0)
                    ).slice(0, 5);

                    const totalBookings = sortedCustomers.reduce((sum, c) => sum + (c.booking_count || c.count || 0), 0);

                    const tableHtml = `
                        <div style="height: 100%; display: flex; flex-direction: column; overflow: hidden;">
                            <div style="flex: 1 1 0; overflow-y: auto; overflow-x: hidden;">
                                <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                                    <thead style="position: sticky; top: 0; background-color: #f5f0eb; z-index: 10;">
                                        <tr>
                                            <th style="padding: 10px 8px; text-align: center; width: 40px; font-size: 10px; font-weight: 600; color: #7f5539; text-transform: uppercase;">#</th>
                                            <th style="padding: 10px 12px; text-align: left; font-size: 10px; font-weight: 600; color: #7f5539; text-transform: uppercase;">Customer</th>
                                            <th style="padding: 10px 8px; text-align: center; width: 60px; font-size: 10px; font-weight: 600; color: #7f5539; text-transform: uppercase;">Bookings</th>
                                            <th style="padding: 10px 12px; text-align: right; width: 70px; font-size: 10px; font-weight: 600; color: #7f5539; text-transform: uppercase;">Share</th>
                                        </tr>
                                    </thead>
                                    <tbody style="background-color: white;">
                                        ${sortedCustomers.map((customer, index) => {
                                            const count = customer.booking_count || customer.count || 0;
                                            const percent = totalBookings > 0 ? ((count / totalBookings) * 100).toFixed(0) : 0;
                                            const fullName = (customer.first_name + ' ' + customer.last_name).trim();
                                            
                                            // Badge color logic
                                            let badgeBg = '#e6ddd4'; // Default coffee-100
                                            let badgeColor = '#7f5539'; // Default coffee-600
                                            
                                            if (index === 0) { badgeBg = '#9c6644'; badgeColor = '#ffffff'; } // Top 1: coffee-500
                                            else if (index === 1) { badgeBg = '#b08968'; badgeColor = '#ffffff'; } // Top 2: coffee-400
                                            else if (index === 2) { badgeBg = '#c2ab90'; badgeColor = '#ffffff'; } // Top 3: coffee-300

                                            return `
                                            <tr style="border-bottom: 1px solid #f5f0eb;">
                                                <td style="padding: 8px 8px; text-align: center;">
                                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; background-color: ${badgeBg}; color: ${badgeColor}; font-size: 11px; font-weight: 600;">
                                                        ${index + 1}
                                                    </span>
                                                </td>
                                                <td style="padding: 8px 12px;">
                                                    <div style="font-size: 13px; font-weight: 600; color: #4a3429; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                        ${fullName}
                                                    </div>
                                                </td>
                                                <td style="padding: 8px 8px; text-align: center;">
                                                    <span style="font-size: 13px; font-weight: 500; color: #5c4033;">${count}</span>
                                                </td>
                                                <td style="padding: 8px 12px; text-align: right;">
                                                    <div style="font-size: 12px; font-weight: 600; color: #7f5539;">${percent}%</div>
                                                    <div style="width: 100%; background-color: #e6ddd4; height: 3px; border-radius: 2px; margin-top: 4px;">
                                                        <div style="width: ${percent}%; background-color: #9c6644; height: 100%; border-radius: 2px;"></div>
                                                    </div>
                                                </td>
                                            </tr>
                                            `;
                                        }).join('')}
                                    </tbody>
                                </table>
                            </div>
                            <!-- Footer for context -->
                            <div style="padding: 8px 12px; background-color: #faf8f6; border-top: 1px solid #e6ddd4; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                                <span style="font-size: 10px; color: #9c6644;">Based on recent activity</span>
                                <span style="font-size: 10px; font-weight: 600; color: #7f5539;">Total: ${totalBookings}</span>
                            </div>
                        </div>
                    `;
                    container.innerHTML = tableHtml;
                } else {
                    // Empty state
                    container.innerHTML = `
                        <div style="height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px;">
                            <div style="background-color: #f5f0eb; padding: 12px; border-radius: 50%; margin-bottom: 12px;">
                                <svg style="width: 24px; height: 24px; color: #9c6644;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                            <p style="font-size: 13px; color: #7f5539; font-weight: 500;">No customer data found</p>
                        </div>
                    `;
                }
            }

            // 6. Inventory Health (Low Stock List — quantity vs threshold, click-through to Inventory)
            if (document.getElementById('inventoryHealthChart')) {
                const lowStockProducts = @json($stats['low_stock_products'] ?? []);
                const lowStockIngredients = @json($stats['low_stock_ingredients'] ?? []);
                const inventoryUrl = @json(route('sub_one.inventory.index'));
                const container = document.getElementById('inventoryHealthChart');

                // Normalize products + ingredients into one shape
                const combined = [
                    ...lowStockProducts.map(p => ({
                        name: p.product_name,
                        qty: Number(p.quantity_in ?? 0),
                        threshold: Number(p.quantity_threshold ?? 0),
                        unit: p.unit || '',
                        type: 'Product'
                    })),
                    ...lowStockIngredients.map(i => ({
                        name: i.ingredient_name,
                        qty: Number(i.stock_quantity_in ?? 0),
                        threshold: Number(i.stock_quantity_threshold ?? 0),
                        unit: i.unit || '',
                        type: 'Ingredient'
                    }))
                ];

                // Most critical first (furthest below threshold)
                combined.sort((a, b) => (a.qty - a.threshold) - (b.qty - b.threshold));

                if (combined.length > 0) {
                    const tableHtml = `
                        <div style="height: 100%; display: flex; flex-direction: column; overflow: hidden;">
                            <div style="flex: 1 1 0; overflow-y: auto; overflow-x: hidden;">
                                <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                                    <thead style="position: sticky; top: 0; background-color: #f5f0eb; z-index: 10;">
                                        <tr>
                                            <th style="padding: 10px 12px; text-align: left; font-size: 10px; font-weight: 600; color: #7f5539; text-transform: uppercase;">Item</th>
                                            <th style="padding: 10px 8px; text-align: center; width: 90px; font-size: 10px; font-weight: 600; color: #7f5539; text-transform: uppercase;">Stock</th>
                                            <th style="padding: 10px 8px; text-align: center; width: 90px; font-size: 10px; font-weight: 600; color: #7f5539; text-transform: uppercase;">Threshold</th>
                                        </tr>
                                    </thead>
                                    <tbody style="background-color: white;">
                                        ${combined.map(item => {
                                            const isCritical = item.qty <= 0;
                                            return `
                                            <tr class="inventory-health-row" style="border-bottom: 1px solid #f5f0eb; cursor: pointer;" onclick="window.location.href='${inventoryUrl}'">
                                                <td style="padding: 8px 12px;">
                                                    <div style="font-size: 12px; font-weight: 600; color: #4a3429; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                        ${item.name}
                                                    </div>
                                                    <div style="font-size: 10px; color: #9c6644;">${item.type}</div>
                                                </td>
                                                <td style="padding: 8px 8px; text-align: center;">
                                                    <span style="font-size: 12px; font-weight: 700; color: ${isCritical ? '#dc2626' : '#ea580c'};">
                                                        ${item.qty.toLocaleString()} ${item.unit}
                                                    </span>
                                                </td>
                                                <td style="padding: 8px 8px; text-align: center;">
                                                    <span style="font-size: 12px; color: #7f5539;">
                                                        ${item.threshold.toLocaleString()} ${item.unit}
                                                    </span>
                                                </td>
                                            </tr>
                                            `;
                                        }).join('')}
                                    </tbody>
                                </table>
                            </div>
                            <div style="padding: 8px 12px; background-color: #faf8f6; border-top: 1px solid #e6ddd4; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                                <span style="font-size: 10px; color: #9c6644;">Tap a row to open Inventory</span>
                                <span style="font-size: 10px; font-weight: 600; color: #dc2626;">${combined.length} low</span>
                            </div>
                        </div>
                    `;
                    container.innerHTML = tableHtml;

                    container.querySelectorAll('.inventory-health-row').forEach(row => {
                        row.addEventListener('mouseenter', () => row.style.backgroundColor = '#f5f0eb');
                        row.addEventListener('mouseleave', () => row.style.backgroundColor = 'white');
                    });
                }
            }

            // 7. Branch Performance Chart (Horizontal Bar Chart)
            if (document.getElementById('branchPerformanceChart') &&
                {{ $selectedBranch == 'all' && isset($stats['branch_performance']) && count($stats['branch_performance']) > 0 ? 'true' : 'false' }}
                ) {
                const branchPerformanceData = @json($stats['branch_performance'] ?? []);
                let categories, seriesData;

                if (branchPerformanceData.length > 0) {
                    categories = branchPerformanceData.map(branch => branch.branch_name);
                    seriesData = branchPerformanceData.map(branch => branch.revenue || branch.booking_count || branch
                    .count);
                } else {
                    categories = ['Main Branch', 'North Branch', 'South Branch', 'East Branch', 'West Branch'];
                    seriesData = [440, 550, 410, 670, 320];
                }

                branchPerformanceChart = new ApexCharts(document.querySelector("#branchPerformanceChart"), {
                    series: [{
                        name: 'Revenue',
                        data: seriesData
                    }],
                    chart: {
                        type: 'bar',
                        height: '100%',
                        fontFamily: 'Inter, sans-serif',
                        toolbar: {
                            show: false
                        }
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 0,
                            horizontal: true,
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function(val) {
                            return '₱' + Math.round(val).toLocaleString();
                        },
                        style: {
                            fontSize: '10px',
                            colors: ["#fff"]
                        }
                    },
                    colors: [coffeeColors['400']],
                    xaxis: {
                        categories: categories,
                        labels: {
                            style: {
                                fontSize: '10px',
                                colors: coffeeColors['700']
                            },
                            formatter: function(val) {
                                return '₱' + Math.round(val).toLocaleString();
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                fontSize: '10px',
                                colors: coffeeColors['700']
                            }
                        }
                    },
                    grid: {
                        borderColor: coffeeColors['100'],
                        strokeDashArray: 3
                    }
                });
                branchPerformanceChart.render();
            }

            // 8. Payment Method Chart (Donut Chart)
            if (document.getElementById('paymentMethodChart') &&
                {{ count($stats['payment_totals'] ?? []) > 0 ? 'true' : 'false' }}) {
                const paymentData = @json($stats['payment_totals'] ?? []);
                const labels = Object.keys(paymentData);
                const series = Object.values(paymentData);

                paymentMethodChart = new ApexCharts(document.querySelector("#paymentMethodChart"), {
                    series: series,
                    chart: {
                        type: 'donut',
                        height: '100%',
                        fontFamily: 'Inter, sans-serif'
                    },
                    colors: [coffeeColors['400'], coffeeColors['500'], coffeeColors['600'], coffeeColors['700'],
                        coffeeColors['800']
                    ],
                    labels: labels,
                    legend: {
                        position: 'bottom',
                        fontSize: '11px'
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '65%',
                                labels: {
                                    show: true,
                                    name: {
                                        show: true,
                                        fontSize: '12px',
                                        color: coffeeColors['700']
                                    },
                                    value: {
                                        show: true,
                                        fontSize: '16px',
                                        fontFamily: 'Inter, sans-serif',
                                        fontWeight: 600,
                                        color: coffeeColors['900'],
                                        formatter: function(val) {
                                            return '₱' + Math.round(val).toLocaleString();
                                        }
                                    },
                                    total: {
                                        show: true,
                                        showAlways: true,
                                        label: 'Total',
                                        fontSize: '14px',
                                        fontWeight: 600,
                                        color: coffeeColors['800'],
                                        formatter: function(w) {
                                            return '₱' + w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                                                .toLocaleString();
                                        }
                                    }
                                }
                            }
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: {
                                height: 200
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }]
                });
                paymentMethodChart.render();
            }

            // 9. Booking Status Chart (Polar Area Chart)
            if (document.getElementById('bookingStatusChart') &&
                {{ $stats['bookings']['total'] > 0 ? 'true' : 'false' }}) {
                const bookingData = @json($stats['bookings']);
                const statusMap = @json($maps['booking_status']);

                const labels = [];
                const series = [];
                const colors = [];

                Object.entries(statusMap).forEach(([statusCode, statusName]) => {
                    if (bookingData[statusCode] > 0) {
                        labels.push(statusName);
                        series.push(bookingData[statusCode]);

                        // Assign colors based on status
                        switch (statusCode) {
                            case '0':
                                colors.push('#ef4444');
                                break; // Cancelled - red
                            case '1':
                                colors.push(coffeeColors['400']);
                                break; // Booked
                            case '2':
                                colors.push('#f59e0b');
                                break; // Pending - amber
                            case '3':
                                colors.push('#6b7280');
                                break; // No-show - gray
                            case '4':
                                colors.push(coffeeColors['600']);
                                break; // Completed
                            default:
                                colors.push(coffeeColors['300']);
                        }
                    }
                });

                bookingStatusChart = new ApexCharts(document.querySelector("#bookingStatusChart"), {
                    series: series,
                    chart: {
                        type: 'polarArea',
                        height: '100%',
                        fontFamily: 'Inter, sans-serif'
                    },
                    colors: colors,
                    labels: labels,
                    legend: {
                        position: 'bottom',
                        fontSize: '11px'
                    },
                    stroke: {
                        colors: ['#fff']
                    },
                    fill: {
                        opacity: 0.8
                    },
                    yaxis: {
                        show: false
                    },
                    plotOptions: {
                        polarArea: {
                            rings: {
                                strokeWidth: 0
                            },
                            spokes: {
                                strokeWidth: 0
                            }
                        }
                    },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: {
                                height: 200
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }]
                });
                bookingStatusChart.render();
            }
        }

        function resizeCharts() {
            // ApexCharts automatically handles resizing
        }
    </script>
@endpush