@extends('layouts.app')

@section('title', 'Business Analytics')

@section('content')
    <!-- Header Section -->
    <div class="px-6 py-4" style="background-color: #f5f0eb; border-bottom: 1px solid #e6ddd4;">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center space-x-3">
                <div class="p-2 rounded-lg" style="background-color: #e6ddd4;">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #7f5539;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                        </path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-semibold" style="color: #4a3429;">
                        @if ($selectedBranch && $selectedBranch != 'all')
                            {{ $selectedBranchName }} Analytics
                        @else
                            Business Analytics
                        @endif
                    </h1>
                    <p class="text-sm" style="color: #7f5539;">
                        @if ($selectedBranch && $selectedBranch != 'all')
                            {{ $selectedBranchName }} deep insights and recommendations
                        @else
                            Advanced business insights and actionable recommendations
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center space-y-3 sm:space-y-0 sm:space-x-3 mt-4 sm:mt-0">
                <!-- Back to Dashboard Button -->
                <a href="{{ route('sub_one.dashboard.showDashboard') }}"
                    class="px-4 py-2 text-sm font-medium rounded-lg hover:shadow-md transition-all duration-200 inline-flex items-center justify-center shadow-sm"
                    style="background-color: #f5f0eb; border: 1px solid #d4c4b2; color: #7f5539;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="p-6" style="background-color: #f5f0eb;">
        
        <!-- Date Filter Section (Dashboard Design) -->
        <div class="mb-6 p-4 bg-white rounded-lg shadow-sm" style="border: 1px solid #e6ddd4;">
            <form action="{{ route('sub_one.business_analytics.showAnalytics') }}" method="GET" id="analyticsDateFilterForm">
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
                                    <button type="button" id="clearAnalyticsDateFilter"
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
                            <button type="button" id="clearAnalyticsDateFilterMobile"
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
            <form action="{{ route('sub_one.business_analytics.showAnalytics') }}" method="GET" id="branchFilterForm">
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
                                <button type="submit" name="branch_filter" value="{{ $branch->id }}"
                                    class="relative overflow-hidden transition-all duration-300 ease-in-out py-2 px-4 rounded-md text-sm font-medium cursor-pointer focus:z-10 focus:outline-none whitespace-nowrap {{ $selectedBranch == $branch->id ? 'text-white' : '' }}"
                                    style="{{ $selectedBranch == $branch->id ? 'background-color: #9c6644;' : 'background-color: transparent; color: #7f5539;' }}">
                                    {{ $branch->branch_name }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Branch Dropdown (for mobile) -->
                    <div class="md:hidden w-full">
                        <label for="branch_dropdown" class="block text-xs font-medium mb-1"
                            style="color: #9c6644;">Select Branch</label>
                        <select name="branch_filter" id="branch_dropdown"
                            class="w-full rounded-lg shadow-sm text-sm py-2 px-3" style="border: 1px solid #d4c4b2;"
                            onchange="this.form.submit()">
                            <option value="all" {{ $selectedBranch == 'all' || !$selectedBranch ? 'selected' : '' }}>
                                All Branches</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}"
                                    {{ $selectedBranch == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->branch_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Clear Branch Filter Button (Desktop) -->
                    <div class="hidden md:block">
                        @if ($selectedBranch && $selectedBranch != 'all')
                            <button type="button" id="clearBranchFilter"
                                class="px-4 py-2 rounded-lg transition-all text-sm font-medium shadow-sm flex items-center justify-center hover:opacity-90 hover:shadow-md active:scale-95"
                                style="background-color: #f5f0eb; border: 1px solid #d4c4b2; color: #7f5539;">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Clear Branch
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Hidden inputs to preserve date filter -->
                <input type="hidden" name="filter" value="{{ $filterType }}">
                <input type="hidden" name="date_from" value="{{ $dateFrom->format('Y-m-d') }}">
                <input type="hidden" name="date_to" value="{{ $dateTo->format('Y-m-d') }}">
            </form>

            <!-- Clear Branch Filter Button (Mobile) -->
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

        <!-- Key Metrics Grid - FIXED HEIGHTS -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Revenue -->
            <div class="bg-white rounded-lg p-5 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col h-full"
                style="border: 1px solid #e6ddd4;">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide" style="color: #7f5539;">Total Revenue</p>
                        <p class="text-2xl font-bold mt-2" style="color: #4a3429;">
                            ₱{{ number_format($metrics['total_revenue'] ?? 0, 2) }}</p>
                    </div>
                    <div class="p-2 rounded-lg flex-shrink-0" style="background-color: #e6ddd4;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" style="color: #7f5539;"
                            stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                        </svg>
                    </div>
                </div>
                <p class="text-xs mt-auto" style="color: #b08968;">
                    Combined booking and POS revenue
                </p>
            </div>

            <!-- Total Bookings -->
            <div class="bg-white rounded-lg p-5 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col h-full"
                style="border: 1px solid #e6ddd4;">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide" style="color: #7f5539;">Total Bookings</p>
                        <p class="text-2xl font-bold mt-2" style="color: #4a3429;">{{ $metrics['total_bookings'] ?? 0 }}</p>
                    </div>
                    <div class="p-2 rounded-lg flex-shrink-0" style="background-color: #e6ddd4;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2" style="color: #7f5539;">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
                <p class="text-xs mt-auto" style="color: #b08968;">
                    @if ($metrics['total_bookings'] > 0)
                        ₱{{ number_format($metrics['avg_booking_value'], 2) }} avg/booking
                    @else
                        ₱0 avg/booking
                    @endif
                </p>
            </div>

            <!-- Total POS Orders -->
            <div class="bg-white rounded-lg p-5 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col h-full"
                style="border: 1px solid #e6ddd4;">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide" style="color: #7f5539;">Total POS Orders</p>
                        <p class="text-2xl font-bold mt-2" style="color: #4a3429;">{{ $metrics['total_orders'] ?? 0 }}</p>
                    </div>
                    <div class="p-2 rounded-lg flex-shrink-0" style="background-color: #e6ddd4;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2" style="color: #7f5539;">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                </div>
                <p class="text-xs mt-auto" style="color: #b08968;">
                    @if ($metrics['total_orders'] > 0)
                        {{ round($metrics['occupancy_rate'], 1) }}% occupancy rate
                    @else
                        0% occupancy rate
                    @endif
                </p>
            </div>

            <!-- Customer Retention -->
            <div class="bg-white rounded-lg p-5 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col h-full"
                style="border: 1px solid #e6ddd4;">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide" style="color: #7f5539;">Customer Retention</p>
                        <p class="text-2xl font-bold mt-2" style="color: #4a3429;">
                            {{ round($metrics['retention_rate'], 1) }}%</p>
                    </div>
                    <div class="p-2 rounded-lg flex-shrink-0" style="background-color: #e6ddd4;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2" style="color: #7f5539;">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
                <p class="text-xs mt-auto" style="color: #b08968;">
                    @if ($metrics['average_rating'] > 0)
                        {{ round($metrics['average_rating'], 1) }}⭐ average rating
                    @else
                        No ratings yet
                    @endif
                </p>
            </div>
        </div>

        <!-- Recommendations Section -->
        <div class="mb-6">
            <div class="bento-card"
                style="background: white; border-radius: 12px; border: 1px solid #e6ddd4; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); display: flex; flex-direction: column; overflow: hidden; transition: all 0.3s ease; min-height: 400px; max-height: 500px;">
                <div class="card-header"
                    style="padding: 16px; border-bottom: 1px solid #f5f0eb; background: linear-gradient(to right, #f5f0eb, #ffffff);">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <div class="p-1.5 rounded-md"
                                style="background: linear-gradient(to right, #6b4f3c, #4a3429);">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold" style="color: #4a3429;">Recommendations</h3>
                                <p class="text-xs" style="color: #7f5539;">Recommendations to improve your business</p>
                            </div>
                        </div>
                        <div class="text-xs" style="color: #7f5539;">
                            @php
                                // Check if there is any actual business data for the selected period
                                $hasData = ($metrics['total_revenue'] ?? 0) > 0 || 
                                          ($metrics['total_bookings'] ?? 0) > 0 || 
                                          ($metrics['total_orders'] ?? 0) > 0;

                                // Filter recommendations to only show those with actual data values
                                $displayRecommendations = [];
                                
                                // Only process recommendations if we have data and recommendations exist
                                if ($hasData && isset($recommendations)) {
                                    $displayRecommendations = array_filter($recommendations, function($rec) {
                                        $val = $rec['estimated_revenue_increase'] ?? null;
                                        if (!$val) return false; // Filter out null/empty
                                        
                                        // Normalize value for comparison
                                        $cleanVal = trim($val);
                                        
                                        // Strict list of invalid/empty values to hide
                                        $invalidValues = [
                                            'N/A', 'No Change', '', 
                                            '0%', '0', '0.0', '0.0%', '0.00', '0.00%',
                                            '₱0', '₱0.00', '₱0.0', 
                                            '+0%', '+0', '+0.0', '+0.0%'
                                        ];
                                        
                                        if (in_array($cleanVal, $invalidValues)) return false;

                                        // Extra robust check: try to parse the number
                                        // Remove non-numeric chars except dot
                                        $justNumbers = preg_replace('/[^0-9.]/', '', $cleanVal);
                                        
                                        // If it looks like a number and equals 0
                                        if (is_numeric($justNumbers) && (float)$justNumbers === 0.0) {
                                            return false;
                                        }

                                        return true;
                                    });
                                }
                            @endphp
                            <span id="recommendationCount">{{ count($displayRecommendations) }}</span> recommendations available
                        </div>
                    </div>
                </div>
                <div class="card-content" style="padding: 16px; flex: 1; overflow-y: auto;">
                    @if (count($displayRecommendations) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="recommendationsContainer">
                            @foreach (array_slice($displayRecommendations, 0, 6) as $index => $rec)
                                @php
                                    $priority = $rec['priority'] ?? 'medium';
                                    $priorityColor = match ($priority) {
                                        'high' => '#ef4444',
                                        'medium' => '#f59e0b',
                                        'low' => '#10b981',
                                        default => '#6b7280',
                                    };
                                    $category = $rec['category'] ?? 'general';
                                    
                                    // Store recommendation data in data attributes
                                    $dataAttributes = '';
                                    foreach ($rec as $key => $value) {
                                        if (!is_array($value) && !is_object($value)) {
                                            $dataAttributes .= ' data-' . str_replace('_', '-', $key) . '="' . htmlspecialchars($value) . '"';
                                        }
                                    }
                                @endphp
                                <div class="recommendation-card p-4 rounded-lg border hover:shadow-md transition-all duration-200 flex flex-col h-full cursor-pointer transform hover:-translate-y-1 active:scale-95"
                                    style="border-color: #e6ddd4; background-color: #f9f7f5; min-height: 180px;"
                                    data-index="{{ $index }}"
                                    {!! $dataAttributes !!}>
                                    <div class="flex items-start justify-between mb-3">
                                        <div>
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                style="background-color: {{ $priorityColor }}20; color: {{ $priorityColor }};">
                                                {{ ucfirst($priority) }} Priority
                                            </span>
                                            <span
                                                class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                style="background-color: #e6ddd4; color: #7f5539;">
                                                {{ $category }}
                                            </span>
                                        </div>
                                        <div class="text-xs" style="color: #b08968;">
                                            {{ $rec['impact'] ?? 'Medium' }}
                                        </div>
                                    </div>
                                    <h4 class="text-sm font-semibold mb-2 flex-grow" style="color: #4a3429;">
                                        {{ $rec['title'] ?? 'Untitled Recommendation' }}
                                    </h4>
                                    <div class="mt-auto">
                                        <p class="text-xs mb-3 line-clamp-2" style="color: #7f5539;">
                                            {{ $rec['description'] ?? 'No description available.' }}
                                        </p>
                                        <div class="flex items-center justify-between text-xs pt-2 border-t" style="border-color: #e6ddd4;">
                                            <span style="color: #b08968;">Click to view details →</span>
                                            @if (isset($rec['estimated_revenue_increase']) && $rec['estimated_revenue_increase'] && $rec['estimated_revenue_increase'] !== 'N/A')
                                                <span class="font-medium" style="color: #9c6644;">{{ $rec['estimated_revenue_increase'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <!-- Show more button if there are more recommendations -->
                        @if (count($displayRecommendations) > 6)
                            <div class="mt-4 text-center">
                                <button id="showMoreRecommendations" class="px-4 py-2 text-sm rounded-lg transition-all hover:bg-gray-100" style="color: #7f5539; border: 1px solid #d4c4b2;">
                                    Show All Recommendations
                                </button>
                            </div>
                        @endif
                    @else
                        <!-- No Data Available Message -->
                        <div class="flex flex-col items-center justify-center h-full text-center p-8">
                            <p class="text-sm text-[#7f5539]">No recommendations available</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Analytical Insights Section - FIXED LAYOUT -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Left Column: Business Performance -->
            <div class="space-y-6">
                <!-- Booking Analytics - Fixed Height -->
                <div class="bento-card" style="background: white; border-radius: 12px; border: 1px solid #e6ddd4; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); display: flex; flex-direction: column; height: 520px; overflow: hidden; transition: all 0.3s ease;">
                    <div class="card-header" style="padding: 16px; border-bottom: 1px solid #f5f0eb; background: linear-gradient(to right, #f5f0eb, #ffffff); flex-shrink: 0;">
                        <div class="flex items-center space-x-2">
                            <div class="p-1.5 rounded-md" style="background: linear-gradient(to right, #9c6644, #4a3429);">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold" style="color: #4a3429;">Booking Analytics</h3>
                                <p class="text-xs" style="color: #7f5539;">Detailed booking performance analysis</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-content" style="padding: 16px; flex: 1; display: flex; flex-direction: column; overflow: hidden;">
                        <div class="grid grid-cols-2 gap-4 mb-4" style="flex-shrink: 0;">
                            <!-- Booking Status Distribution -->
                            <div>
                                <h4 class="text-xs font-medium mb-2" style="color: #7f5539;">Booking Status</h4>
                                <div class="space-y-1" style="max-height: 100px; overflow-y: auto;">
                                    @foreach ($bookingData['by_status'] ?? [] as $status => $count)
                                        <div class="flex justify-between items-center py-1 px-1 hover:bg-gray-50 rounded">
                                            <span class="text-xs truncate" style="color: #4a3429; max-width: 60%;">{{ $status }}</span>
                                            <span class="text-xs font-medium shrink-0 px-2 py-1 rounded" style="background-color: #f5f0eb; color: #9c6644;">{{ $count }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            
                            <!-- Booking Type Distribution -->
                            <div>
                                <h4 class="text-xs font-medium mb-2" style="color: #7f5539;">Booking Type</h4>
                                <div class="space-y-1" style="max-height: 100px; overflow-y: auto;">
                                    @foreach ($bookingData['by_type'] ?? [] as $type => $count)
                                        <div class="flex justify-between items-center py-1 px-1 hover:bg-gray-50 rounded">
                                            <span class="text-xs truncate" style="color: #4a3429; max-width: 60%;">{{ $type }}</span>
                                            <span class="text-xs font-medium shrink-0 px-2 py-1 rounded" style="background-color: #f5f0eb; color: #9c6644;">{{ $count }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        
                        <!-- Booking Trends - Fixed -->
                        @if (isset($bookingData['trends']) && count($bookingData['trends']) > 0)
                        <div class="mt-3 border-t border-gray-100 pt-3" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
                            <h4 class="text-xs font-medium mb-3" style="color: #7f5539; flex-shrink: 0;">Booking Trends Over Time</h4>
                            <div id="bookingTrendsChart" style="flex: 1; min-height: 0;"></div>
                        </div>
                        @else
                        <div class="flex-1 flex items-center justify-center">
                            <div class="text-center">
                                <div class="text-xs" style="color: #b08968;">No booking trends data available</div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Service Performance - Fixed Height -->
                <div class="bento-card" style="background: white; border-radius: 12px; border: 1px solid #e6ddd4; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); display: flex; flex-direction: column; height: 520px; overflow: hidden; transition: all 0.3s ease;">
                    <div class="card-header" style="padding: 16px; border-bottom: 1px solid #f5f0eb; background: linear-gradient(to right, #f5f0eb, #ffffff); flex-shrink: 0;">
                        <div class="flex items-center space-x-2">
                            <div class="p-1.5 rounded-md" style="background: linear-gradient(to right, #b08968, #7f5539);">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold" style="color: #4a3429;">Service Performance</h3>
                                <p class="text-xs" style="color: #7f5539;">Top performing services</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-content" style="padding: 16px; flex: 1; display: flex; flex-direction: column; overflow: hidden;">
                        <div class="overflow-hidden" style="flex: 1; display: flex; flex-direction: column;">
                            <div class="overflow-y-auto flex-1">
                                <table class="w-full">
                                    <thead>
                                        <tr style="background-color: #f5f0eb; position: sticky; top: 0; z-index: 10;">
                                            <th class="px-3 py-2 text-left text-xs font-medium" style="color: #7f5539;">Service</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium" style="color: #7f5539;">Revenue</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium" style="color: #7f5539;">Bookings</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($servicePerformance ?? [] as $service)
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="px-3 py-2 text-xs truncate" style="color: #4a3429; max-width: 150px;">
                                                {{ $service['service_name'] }}
                                            </td>
                                            <td class="px-3 py-2 text-xs font-medium" style="color: #4a3429;">
                                                ₱{{ number_format($service['revenue'], 2) }}
                                            </td>
                                            <td class="px-3 py-2 text-xs font-medium" style="color: #4a3429;">
                                                {{ $service['booking_count'] }}
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="3" class="px-3 py-8 text-center text-xs" style="color: #b08968;">
                                                No service data available
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="pt-3 mt-3 border-t border-gray-100">
                                <div class="flex justify-between text-xs">
                                    <span style="color: #7f5539;">{{ count($servicePerformance ?? []) }} services</span>
                                    <span style="color: #9c6644;">Sorted by revenue</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Customer & Inventory Insights -->
            <div class="space-y-6">
                <!-- Customer Insights - Fixed Height -->
                <div class="bento-card" style="background: white; border-radius: 12px; border: 1px solid #e6ddd4; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); display: flex; flex-direction: column; height: 520px; overflow: hidden; transition: all 0.3s ease;">
                    <div class="card-header" style="padding: 16px; border-bottom: 1px solid #f5f0eb; background: linear-gradient(to right, #f5f0eb, #ffffff); flex-shrink: 0;">
                        <div class="flex items-center space-x-2">
                            <div class="p-1.5 rounded-md" style="background: linear-gradient(to right, #6b4f3c, #4a3429);">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold" style="color: #4a3429;">Customer Insights</h3>
                                <p class="text-xs" style="color: #7f5539;">Customer behavior and segmentation</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-content" style="padding: 16px; flex: 1; display: flex; flex-direction: column; overflow: hidden;">
                        <!-- Customer Segmentation -->
                        @if (isset($customerData['segmentation']))
                        <div class="mb-4" style="flex-shrink: 0;">
                            <h4 class="text-xs font-medium mb-2" style="color: #7f5539;">Customer Segmentation</h4>
                            <div class="grid grid-cols-2 gap-2">
                                @php
                                    $segments = [
                                        'new' => ['color' => '#e6ddd4', 'text' => '#4a3429'],
                                        'occasional' => ['color' => '#d4c4b2', 'text' => '#4a3429'],
                                        'regular' => ['color' => '#b08968', 'text' => '#ffffff'],
                                        'loyal' => ['color' => '#9c6644', 'text' => '#ffffff']
                                    ];
                                @endphp
                                @foreach ($customerData['segmentation'] as $segment => $count)
                                <div class="rounded p-2" style="background-color: {{ $segments[$segment]['color'] ?? '#e6ddd4' }};">
                                    <div class="text-xs font-medium" style="color: {{ $segments[$segment]['text'] ?? '#4a3429' }};">
                                        {{ ucfirst($segment) }}
                                    </div>
                                    <div class="text-xs font-bold" style="color: {{ $segments[$segment]['text'] ?? '#4a3429' }};">{{ $count }} customers</div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <!-- Top Customers -->
                        <div style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
                            <h4 class="text-xs font-medium mb-2" style="color: #7f5539; flex-shrink: 0;">Top Customers</h4>
                            <div class="space-y-2 overflow-y-auto" style="flex: 1; min-height: 0;">
                                @forelse ($topCustomers ?? [] as $customer)
                                <div class="flex justify-between items-center p-3 bg-gray-50 rounded hover:bg-gray-100 transition-colors">
                                    <div class="min-w-0 flex-1">
                                        <div class="text-xs font-medium truncate" style="color: #4a3429;">{{ $customer['name'] }}</div>
                                        <div class="text-xs mt-1" style="color: #7f5539;">
                                            {{ $customer['booking_count'] }} bookings • Last: {{ $customer['last_visit'] }}
                                        </div>
                                    </div>
                                    <div class="text-xs font-bold shrink-0 pl-3" style="color: #9c6644;">
                                        ₱{{ number_format($customer['total_spent'], 2) }}
                                    </div>
                                </div>
                                @empty
                                <div class="text-center text-xs py-8" style="color: #b08968;">
                                    No customer data available
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feedback Analytics - Fixed Height -->
                <div class="bento-card" style="background: white; border-radius: 12px; border: 1px solid #e6ddd4; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); display: flex; flex-direction: column; height: 520px; overflow: hidden; transition: all 0.3s ease;">
                    <div class="card-header" style="padding: 16px; border-bottom: 1px solid #f5f0eb; background: linear-gradient(to right, #f5f0eb, #ffffff); flex-shrink: 0;">
                        <div class="flex items-center space-x-2">
                            <div class="p-1.5 rounded-md" style="background: linear-gradient(to right, #9c6644, #6b4f3c);">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold" style="color: #4a3429;">Feedback Analytics</h3>
                                <p class="text-xs" style="color: #7f5539;">Customer ratings and reviews analysis</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-content" style="padding: 16px; flex: 1; display: flex; flex-direction: column; overflow: hidden;">
                        <!-- Rating Overview -->
                        <div class="mb-4" style="flex-shrink: 0;">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-xs" style="color: #7f5539;">Average Rating</div>
                                    <div class="flex items-center mt-1">
                                        <div class="text-lg font-bold mr-2" style="color: #4a3429;">
                                            {{ round($metrics['average_rating'], 1) }}
                                        </div>
                                        <div class="flex">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <svg class="w-3 h-3 {{ $i <= round($metrics['average_rating']) ? 'text-yellow-500' : 'text-gray-300' }}" 
                                                     fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.54-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                            @endfor
                                        </div>
                                    </div>
                                </div>
                                @if (isset($feedbackData['rating_distribution']))
                                <div class="text-xs" style="color: #7f5539;">
                                    {{ array_sum($feedbackData['rating_distribution']->toArray()) }} ratings
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Rating Distribution -->
                        @if (isset($feedbackData['rating_distribution']))
                        <div class="mb-4" style="flex-shrink: 0;">
                            <h4 class="text-xs font-medium mb-2" style="color: #7f5539;">Rating Distribution</h4>
                            <div class="space-y-1">
                                @foreach ($feedbackData['rating_distribution'] ?? [] as $rating => $count)
                                <div class="flex items-center">
                                    <div class="w-8 text-xs" style="color: #4a3429;">{{ $rating }}</div>
                                    <div class="flex-1 ml-2">
                                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                            @php
                                                $totalRatings = array_sum($feedbackData['rating_distribution']->toArray());
                                                $percentage = $totalRatings > 0 ? ($count / $totalRatings) * 100 : 0;
                                            @endphp
                                            <div class="h-full bg-yellow-500 rounded-full" 
                                                 style="width: {{ $percentage }}%">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="w-8 text-right text-xs font-medium" style="color: #9c6644;">{{ $count }}</div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <!-- Recent Feedback -->
                        <div style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
                            <h4 class="text-xs font-medium mb-2" style="color: #7f5539; flex-shrink: 0;">Recent Feedback</h4>
                            <div class="space-y-2 overflow-y-auto" style="flex: 1; min-height: 0;">
                                @forelse ($feedbackData['recent_feedback'] ?? [] as $feedback)
                                <div class="bg-gray-50 rounded p-3 hover:bg-gray-100 transition-colors">
                                    <div class="flex justify-between items-start mb-1">
                                        <div class="text-xs font-medium" style="color: #4a3429;">Anonymous</div>
                                        <div class="text-xs" style="color: #7f5539;">{{ $feedback['date'] }}</div>
                                    </div>
                                    <div class="flex items-center mb-1">
                                        <div class="flex mr-2">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <svg class="w-2 h-2 {{ $i <= $feedback['rating'] ? 'text-yellow-500' : 'text-gray-300' }}" 
                                                     fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.54-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                            @endfor
                                        </div>
                                        <div class="text-xs" style="color: #7f5539;">{{ $feedback['service_name'] }}</div>
                                    </div>
                                    <div class="text-xs mt-1 line-clamp-2" style="color: #5c4033;">"{{ $feedback['comment'] }}"</div>
                                </div>
                                @empty
                                <div class="text-center text-xs py-8" style="color: #b08968;">
                                    No feedback data available
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recommendation Details Modal -->
        <!-- Added explicit style="z-index: 9999;" to ensure it appears on top -->
        <div id="recommendationModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-[9999] hidden" style="z-index: 9999;">
            <div class="bg-white rounded-lg w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
                <!-- Modal Header -->
                <div class="p-6 border-b flex items-center justify-between bg-white">
                    <div class="flex items-center space-x-3">
                        <div id="modalPriorityBadge" class="px-3 py-1 rounded-full text-xs font-medium"></div>
                        <div>
                            <h3 id="modalTitle" class="text-lg font-semibold" style="color: #4a3429;"></h3>
                            <p id="modalCategory" class="text-sm" style="color: #7f5539;"></p>
                        </div>
                    </div>
                    <button id="closeModal" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #7f5539;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="flex-1 overflow-y-auto p-6">
                    <div class="space-y-6">
                        <!-- Description Section -->
                        <div>
                            <h4 class="text-sm font-medium mb-2" style="color: #7f5539;">Description</h4>
                            <p id="modalDescription" class="text-sm leading-relaxed" style="color: #4a3429;"></p>
                        </div>

                        <!-- Impact & Metrics Section -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="text-sm font-medium mb-2" style="color: #7f5539;">Impact Assessment</h4>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-sm" style="color: #4a3429;">Impact Level:</span>
                                        <span id="modalImpact" class="text-sm font-medium"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm" style="color: #4a3429;">Priority Score:</span>
                                        <span id="modalPriorityScore" class="text-sm font-medium"></span>
                                    </div>
                                    <div id="modalRevenueContainer" class="flex justify-between">
                                        <span class="text-sm" style="color: #4a3429;">Estimated Revenue:</span>
                                        <span id="modalRevenue" class="text-sm font-medium"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="text-sm font-medium mb-2" style="color: #7f5539;">Implementation Details</h4>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-sm" style="color: #4a3429;">Category:</span>
                                        <span id="modalCategoryDetail" class="text-sm font-medium"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm" style="color: #4a3429;">Type:</span>
                                        <span id="modalType" class="text-sm font-medium"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recommended Action Section -->
                        <div>
                            <h4 class="text-sm font-medium mb-2" style="color: #7f5539;">Recommended Action</h4>
                            <div id="modalAction" class="bg-blue-50 p-4 rounded-lg">
                                <p id="modalActionText" class="text-sm" style="color: #4a3429;"></p>
                            </div>
                        </div>

                        <!-- Additional Data Section -->
                        <div>
                            <h4 class="text-sm font-medium mb-2" style="color: #7f5539;">Supporting Data</h4>
                            <div id="modalAdditionalData" class="bg-gray-50 p-4 rounded-lg">
                                <!-- Dynamic content will be inserted here -->
                            </div>
                        </div>

                        <!-- Insights & Next Steps -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <h4 class="text-sm font-medium mb-2" style="color: #7f5539;">Key Insights</h4>
                                <ul id="modalInsights" class="text-sm space-y-1" style="color: #4a3429;">
                                    <!-- Insights will be inserted here -->
                                </ul>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium mb-2" style="color: #7f5539;">Next Steps</h4>
                                <ol id="modalNextSteps" class="text-sm space-y-1" style="color: #4a3429;">
                                    <!-- Next steps will be inserted here -->
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-6 border-t bg-gray-50 flex justify-between items-center">
                    <div class="text-xs" style="color: #7f5539;" id="modalGeneratedInfo">
                        <!-- Generated info will be inserted here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Toast -->
        <div id="successToast" class="fixed top-4 right-4 bg-green-500 text-white px-4 py-3 rounded-lg shadow-lg transform translate-y-[-100px] transition-transform duration-300 z-50 hidden">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span id="toastMessage">Action completed successfully!</span>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    @php
        // Pre-calculate recommendations for JS to avoid Blade parser issues with complex closures inside @json
        $jsRecommendations = [];
        
        // Re-check data presence for JS consistency
        $hasDataJS = ($metrics['total_revenue'] ?? 0) > 0 || 
                    ($metrics['total_bookings'] ?? 0) > 0 || 
                    ($metrics['total_orders'] ?? 0) > 0;

        if ($hasDataJS && isset($recommendations)) {
            $jsFiltered = array_filter($recommendations, function($rec) {
                $val = $rec['estimated_revenue_increase'] ?? null;
                if (!$val) return false;
                $cleanVal = trim($val);
                
                // Strict list of invalid/empty values to hide
                $invalidValues = [
                    'N/A', 'No Change', '', 
                    '0%', '0', '0.0', '0.0%', '0.00', '0.00%',
                    '₱0', '₱0.00', '₱0.0', 
                    '+0%', '+0', '+0.0', '+0.0%'
                ];
                
                if (in_array($cleanVal, $invalidValues)) return false;

                // Extra robust check: try to parse the number
                $justNumbers = preg_replace('/[^0-9.]/', '', $cleanVal);
                
                if (is_numeric($justNumbers) && (float)$justNumbers === 0.0) {
                    return false;
                }

                return true;
            });
            $jsRecommendations = array_values($jsFiltered);
        }
    @endphp

    <script>
    // FIX: Define recommendations globally using the pre-filtered PHP variable
    const rawRecommendations = @json($jsRecommendations);
    const allRecommendations = Array.isArray(rawRecommendations) ? rawRecommendations : Object.values(rawRecommendations);

    $(document).ready(function() {
        // FIX: Move modal to body to ensure it appears above all other content (fixes z-index/stacking context issues)
        $('#recommendationModal').appendTo('body');

        initializeCharts();
        
        // Handle window resize
        let resizeTimer;
        $(window).resize(function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                resizeCharts();
            }, 250);
        });

        // Initialize date pickers (updated logic)
        initializeDatePickers();

        // Variables to track date selection state
        let dateSelectionTimeout = null;

        // Set initial button style based on current filter
        const currentFilter = "{{ $filterType }}";
        if (currentFilter === 'custom') {
            $('.date-filter-btn').removeClass('text-white').css('background-color', 'transparent');
        } else {
            $(`.date-filter-btn[data-filter="${currentFilter}"]`).addClass('text-white').css('background-color', '#9c6644');
        }

        // Handle Apply button clicks
        $('#applyCustomDate, #applyCustomDateMobile').on('click', function() {
            applyCustomDateRange();
        });

        // Handle date picker changes - only validate, don't auto-submit
        $('.date-picker-input').on('change', function() {
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
                    showDateError('Start date cannot be after end date.');
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
                        showDateError('Please select both start and end dates to apply custom range.');
                    }
                }, 1500);
            }
        });

        // Handle quick date filter buttons
        $('.date-filter-btn').on('click', function() {
            const filterType = $(this).data('filter');
            
            // Update hidden filter field
            $('#dash_filter_type').val(filterType);
            
            // Update button styles
            $('.date-filter-btn').removeClass('text-white').css('background-color', 'transparent');
            $(this).addClass('text-white').css('background-color', '#9c6644');
            
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
            
            // Format dates
            const sDate = formatDate(startDate);
            const eDate = formatDate(today);
            
            // Update all inputs (both mobile and desktop)
            $('input[name="date_from"]').val(sDate);
            $('input[name="date_to"]').val(eDate);
            
            // Submit the form
            $('#analyticsDateFilterForm').submit();
        });

        // Clear Date Filter buttons
        $('#clearAnalyticsDateFilter, #clearAnalyticsDateFilterMobile').on('click', function() {
            clearDateFilters();
        });

        // Clear Branch Filter buttons
        $('#clearBranchFilter, #clearBranchFilterMobile').on('click', function() {
            clearBranchFilter();
        });

        // Handle branch filter form submission while preserving date filters
        $('button[name="branch_filter"]').on('click', function(e) {
            e.preventDefault();
            submitBranchFilter($(this).val());
        });

        // Handle mobile dropdown change
        $('#branch_dropdown').on('change', function() {
            submitBranchFilter($(this).val());
        });

        // ============================================
        // RECOMMENDATION MODAL FUNCTIONALITY
        // ============================================

        let displayedRecommendations = 6;

        // Click handler for recommendation cards
        $(document).on('click', '.recommendation-card', function() {
            const index = $(this).data('index');
            // Ensure we are accessing the array safely
            const recommendation = allRecommendations[index];
            
            if (recommendation) {
                openRecommendationModal(recommendation, index);
            } else {
                console.error('Recommendation data not found for index:', index);
            }
        });

        // Show more recommendations
        $('#showMoreRecommendations').on('click', function() {
            displayAllRecommendations();
        });

        // Close modal
        $('#closeModal').on('click', function() {
            closeRecommendationModal();
        });

        // Close modal on ESC key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                closeRecommendationModal();
            }
        });

        // Close modal when clicking outside
        $('#recommendationModal').on('click', function(e) {
            if (e.target === this) {
                closeRecommendationModal();
            }
        });

        // Initialize recommendation cards with hover effects
        initializeRecommendationCards();
    });

    function initializeDatePickers() {
        // Set max date for inputs to today
        const today = new Date().toISOString().split('T')[0];
        $('input[name="date_to"]').attr('max', today);
        $('input[name="date_from"]').attr('max', today);
    }

    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function applyCustomDateRange() {
        // Target only visible inputs to ensure we get value from the active view
        const dateFrom = $('input[name="date_from"]:visible').val();
        const dateTo = $('input[name="date_to"]:visible').val();
        
        // Clear any error messages
        $('.date-error').remove();
        
        // Validate dates
        if (!dateFrom || !dateTo) {
            showDateError('Please select both start and end dates.');
            return;
        }
        
        if (dateFrom > dateTo) {
            showDateError('Start date cannot be after end date.');
            return;
        }
        
        // Update ALL date inputs to match the visible ones (syncs mobile/desktop)
        // This is crucial because hidden inputs might retain old values and override visible ones on submit
        $('input[name="date_from"]').val(dateFrom);
        $('input[name="date_to"]').val(dateTo);
        
        // IMPORTANT: Set filter type to custom
        $('#dash_filter_type').val('custom');
        
        // Remove highlighting from quick filter buttons
        $('.date-filter-btn').removeClass('text-white').css('background-color', 'transparent');
        
        // Submit the form
        $('#analyticsDateFilterForm').submit();
    }

    function clearDateFilters() {
        // Reset to default monthly filter (30 days)
        const today = new Date();
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(today.getDate() - 30);

        // Format dates
        const sDate = formatDate(thirtyDaysAgo);
        const eDate = formatDate(today);

        // Set date inputs to default values
        $('input[name="date_from"]').val(sDate);
        $('input[name="date_to"]').val(eDate);

        // Set filter to monthly
        $('#dash_filter_type').val('monthly');

        // Update button styles
        $('.date-filter-btn').removeClass('text-white').css('background-color', 'transparent');
        $('.date-filter-btn[data-filter="monthly"]').addClass('text-white').css('background-color', '#9c6644');

        // Submit the form
        $('#analyticsDateFilterForm').submit();
    }

    function showDateError(message) {
        // Remove any existing error messages
        $('.date-error').remove();
        
        // Create error message
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
        
        // Insert error message
        $('#analyticsDateFilterForm').append(errorHtml);
        
        // Auto-remove error after 5 seconds
        setTimeout(() => {
            $('.date-error').fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    function clearBranchFilter() {
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
    }

    function submitBranchFilter(branchValue) {
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
            value: branchValue
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
    }

    // ============================================
    // RECOMMENDATION MODAL FUNCTIONS
    // ============================================

    function openRecommendationModal(recommendation, index) {
        // Set priority color
        const priorityColor = getPriorityColor(recommendation.priority);
        
        // Update modal header
        $('#modalPriorityBadge').html(`
            <span style="color: ${priorityColor.text}; background-color: ${priorityColor.bg}20; padding: 4px 8px; border-radius: 9999px;">
                ${recommendation.priority || 'medium'} Priority
            </span>
        `);
        
        $('#modalTitle').text(recommendation.title || 'Untitled Recommendation');
        $('#modalCategory').text(recommendation.category ? recommendation.category.charAt(0).toUpperCase() + recommendation.category.slice(1) : 'General');
        
        // Update description
        $('#modalDescription').text(recommendation.description || 'No description available.');
        
        // Update impact & metrics
        $('#modalImpact').html(`
            <span style="color: ${priorityColor.text}; font-weight: 600;">
                ${recommendation.impact || 'Medium'}
            </span>
        `);
        
        $('#modalPriorityScore').html(`
            <span style="color: #9c6644; font-weight: 600;">
                ${(recommendation.priority_score || 0.5).toFixed(2)}
            </span>
        `);
        
        // Hide revenue if not available or N/A (Based on date/value check)
        if (recommendation.estimated_revenue_increase && recommendation.estimated_revenue_increase !== 'N/A') {
            $('#modalRevenueContainer').show();
            $('#modalRevenue').html(`
                <span style="color: #10b981; font-weight: 600;">
                    ${recommendation.estimated_revenue_increase}
                </span>
            `);
        } else {
            $('#modalRevenueContainer').hide();
        }
        
        // Update implementation details
        $('#modalCategoryDetail').html(`
            <span style="color: #9c6644; font-weight: 600;">
                ${recommendation.category ? recommendation.category.charAt(0).toUpperCase() + recommendation.category.slice(1) : 'General'}
            </span>
        `);
        
        $('#modalType').html(`
            <span style="color: #7f5539; font-weight: 600;">
                ${recommendation.type ? recommendation.type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'General'}
            </span>
        `);
        
        // Update recommended action
        $('#modalActionText').text(recommendation.action || 'No specific action provided.');
        
        // Generate additional data based on recommendation type
        generateAdditionalData(recommendation);
        
        // Generate insights
        generateInsights(recommendation);
        
        // Generate next steps
        generateNextSteps(recommendation);
        
        // Set generated info
        const currentDate = new Date().toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        $('#modalGeneratedInfo').html(`
            Generated on ${currentDate} • Based on recent performance data
        `);
        
        // Show modal with animation
        $('#recommendationModal').removeClass('hidden').addClass('flex');
        $('body').addClass('overflow-hidden');
        
        // Add animation class
        setTimeout(() => {
            $('#recommendationModal .bg-white').addClass('modal-enter-active');
        }, 10);
    }

    function closeRecommendationModal() {
        // Add exit animation
        $('#recommendationModal .bg-white').removeClass('modal-enter-active').addClass('modal-exit-active');
        
        setTimeout(() => {
            $('#recommendationModal').addClass('hidden').removeClass('flex');
            $('#recommendationModal .bg-white').removeClass('modal-exit-active');
            $('body').removeClass('overflow-hidden');
        }, 300);
    }

    function getPriorityColor(priority) {
        switch(priority?.toLowerCase()) {
            case 'high':
                return { bg: '#ef4444', text: '#ef4444' };
            case 'medium':
                return { bg: '#f59e0b', text: '#f59e0b' };
            case 'low':
                return { bg: '#10b981', text: '#10b981' };
            default:
                return { bg: '#6b7280', text: '#6b7280' };
        }
    }

    function generateAdditionalData(recommendation) {
        let additionalData = '';
        // FIX: Ensure description is safe string to prevent match errors
        const desc = recommendation.description || '';
        
        switch(recommendation.type) {
            case 'order_volume_increase':
                additionalData = `
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm" style="color: #4a3429;">Current Order Volume:</span>
                            <span class="text-sm font-medium" style="color: #9c6644;">Low</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm" style="color: #4a3429;">Target Order Volume:</span>
                            <span class="text-sm font-medium" style="color: #10b981;">15-25% increase</span>
                        </div>
                        <div class="mt-2 p-2 bg-yellow-50 rounded text-xs" style="color: #7f5539;">
                            <strong>Opportunity:</strong> Only ${(desc.match(/\d+/) || ['few'])[0]} orders this period indicates untapped potential.
                        </div>
                    </div>
                `;
                break;
                
            case 'customer_retention':
                additionalData = `
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm" style="color: #4a3429;">Current Retention:</span>
                            <span class="text-sm font-medium" style="color: #9c6644;">${(desc.match(/\d+\.?\d*/) || ['N/A'])[0]}%</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm" style="color: #4a3429;">Industry Average:</span>
                            <span class="text-sm font-medium" style="color: #6b7280;">40-60%</span>
                        </div>
                        <div class="mt-2 p-2 bg-blue-50 rounded text-xs" style="color: #7f5539;">
                            <strong>Note:</strong> Increasing retention by 5% can increase profits by 25-95%.
                        </div>
                    </div>
                `;
                break;
                
            case 'service_quality_improvement':
                additionalData = `
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm" style="color: #4a3429;">Current Rating:</span>
                            <span class="text-sm font-medium" style="color: #9c6644;">${(desc.match(/\d+\.?\d*/) || ['N/A'])[0]}/5</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm" style="color: #4a3429;">Target Rating:</span>
                            <span class="text-sm font-medium" style="color: #10b981;">4.0+</span>
                        </div>
                    </div>
                `;
                break;
                
            default:
                // Removed redundant Time Period display if no specific value based on date exists
                additionalData = `
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm" style="color: #4a3429;">Based on:</span>
                            <span class="text-sm font-medium" style="color: #9c6644;">Recent performance data analysis</span>
                        </div>
                    </div>
                `;
        }
        
        $('#modalAdditionalData').html(additionalData);
    }

    function generateInsights(recommendation) {
        let insights = [];
        
        // Generate insights based on recommendation type
        switch(recommendation.type) {
            case 'order_volume_increase':
                insights = [
                    'Low order volume suggests untapped market potential',
                    'Current customers are not maximizing their purchase frequency',
                    'Competition analysis shows room for growth',
                    'Seasonal trends indicate upcoming opportunities'
                ];
                break;
                
            case 'customer_retention':
                insights = [
                    'Repeat customers spend 67% more than new customers',
                    'Acquiring a new customer costs 5x more than retaining existing ones',
                    'Loyal customers are more likely to recommend your business',
                    'Small improvements in retention lead to exponential profit growth'
                ];
                break;
                
            case 'service_quality_improvement':
                insights = [
                    'Service ratings directly impact customer loyalty',
                    'Negative reviews can deter 22% of potential customers',
                    'Improving service quality increases customer lifetime value',
                    'Consistent quality builds brand reputation'
                ];
                break;
                
            default:
                insights = [
                    'This recommendation is based on recent performance trends',
                    'Implementing this could improve overall business efficiency',
                    'The suggested action aligns with industry best practices',
                    'Regular monitoring will help track improvement'
                ];
        }
        
        const insightsHtml = insights.map(insight => 
            `<li class="flex items-start">
                <svg class="w-3 h-3 mt-0.5 mr-2 flex-shrink-0" style="color: #9c6644;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                ${insight}
            </li>`
        ).join('');
        
        $('#modalInsights').html(insightsHtml);
    }

    function generateNextSteps(recommendation) {
        let nextSteps = [];
        
        // Generate next steps based on recommendation type
        switch(recommendation.type) {
            case 'order_volume_increase':
                nextSteps = [
                    'Review current product offerings and pricing',
                    'Create promotional campaigns for slow periods',
                    'Train staff on upselling techniques',
                    'Monitor results weekly and adjust strategy'
                ];
                break;
                
            case 'customer_retention':
                nextSteps = [
                    'Implement loyalty program within 2 weeks',
                    'Create personalized follow-up emails',
                    'Gather feedback from recent customers',
                    'Set retention goals and track monthly'
                ];
                break;
                
            case 'service_quality_improvement':
                nextSteps = [
                    'Conduct staff training on service standards',
                    'Implement customer feedback system',
                    'Monitor service quality metrics weekly',
                    'Reward staff for positive customer feedback'
                ];
                break;
                
            default:
                nextSteps = [
                    'Review the recommendation details carefully',
                    'Assign responsibility for implementation',
                    'Set a timeline for completion',
                    'Establish metrics to measure success'
                ];
        }
        
        const nextStepsHtml = nextSteps.map((step, index) => 
            `<li class="flex items-start">
                <span class="inline-flex items-center justify-center w-4 h-4 mr-2 text-xs rounded-full flex-shrink-0" style="background-color: #e6ddd4; color: #7f5539;">
                    ${index + 1}
                </span>
                ${step}
            </li>`
        ).join('');
        
        $('#modalNextSteps').html(nextStepsHtml);
    }

    function showToast(message) {
        $('#toastMessage').text(message);
        $('#successToast').removeClass('hidden');
        
        // Animate in
        setTimeout(() => {
            $('#successToast').css('transform', 'translateY(0)');
        }, 10);
        
        // Animate out after 3 seconds
        setTimeout(() => {
            $('#successToast').css('transform', 'translateY(-100px)');
            setTimeout(() => {
                $('#successToast').addClass('hidden');
            }, 300);
        }, 3000);
    }

    function displayAllRecommendations() {
    const container = $('#recommendationsContainer');
    const showMoreBtn = $('#showMoreRecommendations');
    
    // Clear existing cards
    container.empty();
    
    // Display all recommendations
    allRecommendations.forEach((rec, index) => {
        const priority = rec.priority || 'medium';
        const priorityColor = getPriorityColor(priority);
        const category = rec.category || 'general';
        
        // Escape HTML content to prevent curly braces and other issues
        const safeTitle = escapeHtml(rec.title || 'Untitled Recommendation');
        const safeDescription = escapeHtml(rec.description || 'No description available.');
        const safeImpact = escapeHtml(rec.impact || 'Medium');
        const safeRevenue = escapeHtml(rec.estimated_revenue_increase || 'N/A');
        
        const cardHtml = `
            <div class="recommendation-card p-4 rounded-lg border hover:shadow-md transition-all duration-200 flex flex-col h-full cursor-pointer transform hover:-translate-y-1 active:scale-95"
                style="border-color: #e6ddd4; background-color: #f9f7f5; min-height: 180px;"
                data-index="${index}">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                            style="background-color: ${priorityColor.bg}20; color: ${priorityColor.text};">
                            ${priority.charAt(0).toUpperCase() + priority.slice(1)} Priority
                        </span>
                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                            style="background-color: #e6ddd4; color: #7f5539;">
                            ${category.charAt(0).toUpperCase() + category.slice(1)}
                        </span>
                    </div>
                    <div class="text-xs" style="color: #b08968;">
                        ${safeImpact}
                    </div>
                </div>
                <h4 class="text-sm font-semibold mb-2 flex-grow" style="color: #4a3429;">
                    ${safeTitle}
                </h4>
                <div class="mt-auto">
                    <p class="text-xs mb-3 line-clamp-2" style="color: #7f5539;">
                        ${safeDescription}
                    </p>
                    <div class="flex items-center justify-between text-xs pt-2 border-t" style="border-color: #e6ddd4;">
                        <span style="color: #b08968;">Click to view details →</span>
                        <span class="font-medium" style="color: #9c6644;">
                            ${safeRevenue}
                        </span>
                    </div>
                </div>
            </div>
        `;
        
        container.append(cardHtml);
    });
    
    // Re-attach click handlers to new cards
    container.find('.recommendation-card').on('click', function() {
        const index = $(this).data('index');
        const recommendation = allRecommendations[index];
        if (recommendation) {
            openRecommendationModal(recommendation, index);
        }
    });
    
    // Hide the show more button
    showMoreBtn.hide();
    
    // Update count
    $('#recommendationCount').text(allRecommendations.length);
}

// Helper function to escape HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

    function updateRecommendationCount() {
        const remainingCards = $('.recommendation-card').length;
        $('#recommendationCount').text(remainingCards);
        
        if (remainingCards === 0) {
            $('#recommendationsContainer').html(`
                <div class="col-span-3 py-12 text-center">
                    <div class="text-sm" style="color: #b08968;">
                        All recommendations have been addressed. Great job!
                    </div>
                </div>
            `);
        }
    }

    function initializeRecommendationCards() {
        // Check localStorage for dismissed/implemented recommendations
        const dismissed = JSON.parse(localStorage.getItem('dismissedRecommendations') || '{}');
        const implemented = JSON.parse(localStorage.getItem('implementedRecommendations') || '{}');
        
        // In a real application, you would filter recommendations here
        // based on what's in localStorage
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

        // Booking Trends Chart
        if (document.getElementById('bookingTrendsChart')) {
            const bookingTrends = @json($bookingData['trends'] ?? []);
            
            let categories = [];
            let seriesData = [];

            if (bookingTrends.length > 0) {
                categories = bookingTrends.map(item => item.label);
                seriesData = bookingTrends.map(item => item.count);
            }

            const bookingTrendsChart = new ApexCharts(document.querySelector("#bookingTrendsChart"), {
                series: [{
                    name: 'Bookings',
                    data: seriesData
                }],
                chart: {
                    type: 'line',
                    height: '100%',
                    parentHeightOffset: 0,
                    fontFamily: 'Inter, sans-serif',
                    toolbar: {
                        show: false
                    },
                    zoom: {
                        enabled: false
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                colors: [coffeeColors['500']],
                stroke: {
                    curve: 'smooth',
                    width: 3,
                    lineCap: 'round'
                },
                markers: {
                    size: 5,
                    colors: [coffeeColors['500']],
                    strokeColors: '#ffffff',
                    strokeWidth: 2,
                    hover: {
                        size: 7
                    }
                },
                xaxis: {
                    categories: categories,
                    labels: {
                        style: {
                            fontSize: '10px',
                            colors: coffeeColors['700'],
                            fontFamily: 'Inter, sans-serif'
                        },
                        rotate: -45,
                        rotateAlways: true,
                        trim: true,
                        maxHeight: 50
                    },
                    tickPlacement: 'on',
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
                            colors: coffeeColors['700'],
                            fontFamily: 'Inter, sans-serif'
                        }
                    },
                    min: 0,
                    forceNiceScale: true,
                    tickAmount: 5
                },
                grid: {
                    borderColor: coffeeColors['100'],
                    strokeDashArray: 3,
                    padding: {
                        top: 10,
                        bottom: 10,
                        left: 10,
                        right: 10
                    },
                    xaxis: {
                        lines: {
                            show: true
                        }
                    },
                    yaxis: {
                        lines: {
                            show: true
                        }
                    }
                },
                tooltip: {
                    enabled: true,
                    style: {
                        fontSize: '11px',
                        fontFamily: 'Inter, sans-serif'
                    },
                    x: {
                        show: true,
                        format: 'dd MMM'
                    },
                    y: {
                        formatter: function(value) {
                            return value + ' bookings';
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                },
                legend: {
                    show: false
                }
            });
            bookingTrendsChart.render();
        }
    }

    function resizeCharts() {
        // ApexCharts automatically handles resizing
        // Trigger window resize event for charts
        window.dispatchEvent(new Event('resize'));
    }
    
    // Add keyboard shortcuts for better UX
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + F to focus on date from field
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            $('#date_from').focus();
        }
        
        // Escape to clear filters or close modal
        if (e.key === 'Escape') {
            const activeElement = document.activeElement;
            if (!$(activeElement).is('input, textarea, select')) {
                if ($('#recommendationModal').is(':visible')) {
                    closeRecommendationModal();
                } else {
                    clearDateFilters();
                }
            }
        }
    });
</script>

<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .date-filter-btn:hover {
        background-color: #b08968 !important;
        color: white !important;
    }
    
    /* Scrollbar styling */
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    
    ::-webkit-scrollbar-track {
        background: #f5f0eb;
        border-radius: 3px;
    }
    
    ::-webkit-scrollbar-thumb {
        background: #d4c4b2;
        border-radius: 3px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: #b08968;
    }
    
    /* Date input styling */
    input[type="date"] {
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    input[type="date"]:hover {
        border-color: #b08968 !important;
    }
    
    input[type="date"]:focus {
        outline: none;
        border-color: #9c6644 !important;
        box-shadow: 0 0 0 2px rgba(156, 102, 68, 0.1) !important;
    }
    
    /* Custom date picker styling */
    ::-webkit-calendar-picker-indicator {
        cursor: pointer;
        filter: invert(0.3);
        padding: 2px;
        border-radius: 3px;
        transition: all 0.2s ease;
    }
    
    ::-webkit-calendar-picker-indicator:hover {
        filter: invert(0.5);
        background-color: #f5f0eb;
    }
    
    /* Input Error Styling (added) */
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
    
    /* Modal animations */
    #recommendationModal .bg-white {
        transform: scale(0.9);
        opacity: 0;
        transition: all 0.3s ease-out;
    }
    
    #recommendationModal .bg-white.modal-enter-active {
        transform: scale(1);
        opacity: 1;
    }
    
    #recommendationModal .bg-white.modal-exit-active {
        transform: scale(0.9);
        opacity: 0;
    }
    
    /* Toast animations */
    #successToast {
        transition: transform 0.3s ease-out;
    }
    
    /* Recommendation card hover effects */
    .recommendation-card {
        transition: all 0.2s ease;
    }
    
    .recommendation-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
    }
    
    .recommendation-card:active {
        transform: scale(0.98);
    }
</style>
@endpush