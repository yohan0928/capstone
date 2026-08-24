@extends('layouts.app')

@section('title', 'Staff Account Lists')

@section('content')

    <div x-data="staffManagement()" class="p-4">

        <!-- Header Section -->
        <div class="flex items-center mb-8">
            <!-- Left spacer -->
            <div class="flex-1"></div>
        
            <!-- Center Title -->
            <h1 class="text-2xl font-bold text-gray-900 text-center">
                Staff Account Lists
            </h1>
        
            <!-- Right: Archive Link -->
            <div class="flex-1 text-right">
                <div class="flex justify-end space-x-3">
                    <!-- Activity Logs Button -->
                    <a href="{{ route('sub_one.staff_activity_logs.index') }}"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Activity Logs
                    </a>
                    
                    <!-- Archive Link -->
                    <a href="{{ route('sub_one.staff.showDeactivatedStaffList') }}"
                       class="inline-flex items-center px-4 py-2 border border-[#7F5539] text-[#7F5539] rounded-lg hover:bg-[#7F5539] hover:text-white transition-colors text-sm font-medium">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                        View Archives
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-5 xl:grid-cols-5 gap-6 mb-8">
            <!-- Total Staff -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Staff</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.total_staff"></p>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-lg">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Verified Staff -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Verified</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.verified_staff"></p>
                    </div>
                    <div class="p-3 bg-green-50 rounded-lg">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Suspended Staff -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Suspended</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.suspended_staff"></p>
                    </div>
                    <div class="p-3 bg-red-50 rounded-lg">
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- With Shift -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">With Shift</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.with_shift"></p>
                    </div>
                    <div class="p-3 bg-purple-50 rounded-lg">
                        <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Badge -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Recent Activity</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.recent_activity || '0'"></p>
                        <p class="text-xs text-gray-500 mt-1">Last 24 hours</p>
                    </div>
                    <div class="p-3 bg-orange-50 rounded-lg">
                        <a href="{{ route('sub_one.staff_activity_logs.index') }}" class="text-orange-500 hover:text-orange-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Table Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <!-- Header Row - Title and Add Button on same row -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-4 space-y-4 lg:space-y-0">
                    <!-- Left: Title and Add Button on same row -->
                    <div class="flex items-center justify-between w-full lg:w-auto">
                        <h2 class="text-lg font-semibold text-gray-900">Staff Account Lists</h2>

                        <!-- Add Staff Account Button - Now on right side of label on mobile -->
                        <button @click="openAddStaffModal()"
                            class="inline-flex items-center px-4 py-2 bg-[#7F5539] text-white rounded-lg hover:bg-[#4A2C1D] transition-colors lg:hidden">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.5" class="w-4 h-4 mr-2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                            </svg>
                            Add
                        </button>
                    </div>

                    <!-- Right: Add Staff Button (Desktop) and Search/Filter -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-3 w-full lg:w-auto">
                        <!-- Bottom Row: Search + Filter -->
                        <div class="flex flex-row items-center justify-end space-x-3 w-full lg:w-auto">
                            <!-- Search Input -->
                            <div class="relative w-full sm:w-80">
                                <input type="text" x-model="searchQuery" @input.debounce.500ms="performSearch()"
                                    placeholder="Search by staff name, email, or branch..."
                                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] w-full">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Filter Button -->
                            <button @click="showFilters = true; addBodyClass()"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                                </svg>
                            </button>
                        </div>

                        <div class="hidden lg:flex justify-end">
                            <button @click="openAddStaffModal()"
                                class="inline-flex items-center px-4 py-2 bg-[#7F5539] text-white rounded-lg hover:bg-[#4A2C1D] transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.5" class="w-4 h-4 mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                                </svg>
                                Add Staff Account
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Active Filters Badge -->
                <div x-show="hasActiveFilters" class="flex items-center justify-end space-x-2 mt-2">
                    <template x-for="filter in activeFilters" :key="filter.key">
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-[#4A2C1D]/10 text-[#7F5539]">
                            <span x-text="filter.label"></span>
                            <button @click="removeFilter(filter.key)" class="ml-1 hover:text-[#4A2C1D]">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </span>
                    </template>
                    <button @click="clearAllFilters()" class="text-sm text-[#4A2C1D] hover:text-[#7F5539] font-medium">
                        Clear all
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <!-- Staff Name - Increased width -->
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">
                    Staff Name
                </th>
                <!-- Contact Information - Reduced width -->
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">
                    Contact Information
                </th>
                <!-- Current Shift - Increased width -->
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-1/5">
                    Current Shift
                </th>
                <!-- Status - Increased width -->
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">
                    Status
                </th>
                <!-- Actions - Increased width -->
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-1/8">
                    Actions
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <template x-for="staff in staffAccounts" :key="staff.id">
                <tr class="hover:bg-gray-50 transition-colors">
                    <!-- Staff Name -->
                    <td class="px-6 py-4 whitespace-nowrap w-1/4">
                        <div class="flex items-center">
                            <div class="bg-[#4A2C1D]/10 rounded-lg p-2 mr-3">
                                <svg class="w-4 h-4 text-[#7F5539]" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900"
                                    x-text="staff.first_name + ' ' + staff.last_name">
                                </div>
                                <div class="text-xs text-gray-500 mt-1"
                                    x-text="staff.branch?.branch_name || 'No branch assigned'">
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Contact Information -->
                    <td class="px-6 py-4 whitespace-nowrap w-1/4">
                        <div class="space-y-1">
                            <div class="flex items-center text-sm text-gray-900">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span x-text="staff.email"></span>
                            </div>
                            <div class="flex items-center text-sm text-gray-500">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <span x-text="staff.contact_no"></span>
                            </div>
                            <div class="flex items-center text-sm text-gray-500">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span x-text="staff.address"></span>
                            </div>
                        </div>
                    </td>

                    <!-- Current Shift -->
                    <td class="px-6 py-4 whitespace-nowrap w-1/5">
                        <template x-if="getCurrentShift(staff)">
                            <div class="flex flex-col items-center justify-center space-y-2 text-center">
                                <!-- Shift Dates -->
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        <span x-text="formatShiftDate(getCurrentShift(staff).shift_date_start)"></span>
                                        <template
                                            x-if="getCurrentShift(staff).shift_date_end && getCurrentShift(staff).shift_date_start !== getCurrentShift(staff).shift_date_end">
                                            <span>
                                                -
                                                <span x-text="formatShiftDate(getCurrentShift(staff).shift_date_end)"></span>
                                            </span>
                                        </template>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <span x-text="formatTime(getCurrentShift(staff).shift_time_start)"></span>
                                        -
                                        <span x-text="formatTime(getCurrentShift(staff).shift_time_end)"></span>
                                    </div>
                                </div>

                                <!-- Shift Status -->
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="getShiftStatusClass(getCurrentShift(staff).staff_shift_schedule_status)">
                                    <span x-text="getShiftStatusText(getCurrentShift(staff).staff_shift_schedule_status)"></span>
                                </span>
                            </div>
                        </template>

                        <template x-if="!getCurrentShift(staff)">
                            <div class="flex justify-center items-center">
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    No Current Shift
                                </span>
                            </div>
                        </template>
                    </td>

                    <!-- Status (Account Status) -->
                    <td class="px-6 py-4 whitespace-nowrap w-1/6">
                        <div x-data="{ open: false }" class="relative inline-block">
                            <button @click.prevent="open = !open" @click.away="open = false"
                                class="flex items-center space-x-1 text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap cursor-pointer"
                                :class="getAccountStatusClass(staff.account_status)">
                                <span x-text="getAccountStatusText(staff.account_status)"></span>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor"
                                    class="w-3 h-3 transition-transform duration-200"
                                    :class="{ 'rotate-180': open }">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>

                            <div x-show="open"
                                class="absolute left-0 mt-2 w-40 bg-white rounded-md shadow-lg z-[1000] border border-gray-200"
                                style="display: none;">
                                <!-- Verified Option -->
                                <form :id="`update-status-${staff.id}-1`"
                                    :action="`{{ route('sub_one.staff.updateStaffAccountStatus', '') }}/${staff.id}`"
                                    method="POST" class="hidden">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="account_status" value="1">
                                </form>
                                <button
                                    @click="event.preventDefault(); document.getElementById(`update-status-${staff.id}-1`).submit();"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 first:rounded-t-md"
                                    :class="{ 'bg-green-50 text-green-700': staff.account_status == 1 }">
                                    Verified
                                </button>

                                <!-- Suspended Option -->
                                <form :id="`update-status-${staff.id}-0`"
                                    :action="`{{ route('sub_one.staff.updateStaffAccountStatus', '') }}/${staff.id}`"
                                    method="POST" class="hidden">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="account_status" value="0">
                                </form>
                                <button
                                    @click="event.preventDefault(); document.getElementById(`update-status-${staff.id}-0`).submit();"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 last:rounded-b-md"
                                    :class="{ 'bg-red-50 text-red-700': staff.account_status == 0 }">
                                    Suspended
                                </button>
                            </div>
                        </div>
                    </td>

                    <!-- Actions -->
                    <td class="px-6 py-4 whitespace-nowrap text-center w-1/8">
                        <div class="flex items-center justify-center space-x-2">
                            <!-- View Schedules Button -->
                            <div class="relative group">
                                <a :href="`/sub_one/staff/${staff.uuid}/schedules`"
                                    class="p-1.5 text-[#4A2C1D] hover:text-white hover:bg-[#4A2C1D] rounded-full transition-colors duration-200 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                        class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                    </svg>
                                </a>
                                <span
                                    class="absolute right-full top-1/2 -translate-y-1/2 mr-2 bg-gray-800 text-white text-xs font-medium px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none">
                                    View Schedules
                                </span>
                            </div>

                            <!-- Archive Button -->
                            <div class="relative group">
                                <button
                                    @click="confirmArchive(staff.id, staff.first_name + ' ' + staff.last_name)"
                                    class="p-1.5 text-[#4A2C1D] hover:text-white hover:bg-red-600 rounded-full transition-colors duration-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                        class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                                <span
                                    class="absolute right-full top-1/2 -translate-y-1/2 mr-2 bg-gray-800 text-white text-xs font-medium px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none">
                                    Archive Account
                                </span>
                            </div>
                        </div>
                    </td>
                </tr>
            </template>

            <!-- Empty State -->
            <tr x-show="!staffAccounts.length">
                <td colspan="5" class="px-6 py-12 text-center">
                    <div class="text-gray-400">
                        <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                        <h5 class="text-sm font-medium text-gray-900"
                            x-text="hasActiveFilters ? 'No staff accounts match your filters' : 'No staff accounts found'">
                        </h5>
                        <p class="text-sm text-gray-500"
                            x-text="hasActiveFilters ? 'Try adjusting your filters.' : 'When you add staff accounts, they will appear here.'">
                        </p>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>

            <!-- Pagination -->
            <div x-show="pagination && pagination.last_page > 1" class="px-6 py-4 border-t border-gray-200">
                <div class="flex justify-between items-center">
                    <div class="text-sm text-gray-700">
                        Showing <span x-text="pagination.from || 0"></span> to <span x-text="pagination.to || 0"></span>
                        of <span x-text="pagination.total || 0"></span> entries
                    </div>
                    <div class="flex space-x-2">
                        <!-- Previous Button -->
                        <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
                            class="px-3 py-1 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            Previous
                        </button>

                        <!-- Page Numbers -->
                        <template x-for="page in paginationLinks" :key="page">
                            <button @click="changePage(page)" class="px-3 py-1 border rounded-lg text-sm font-medium"
                                :class="page === pagination.current_page ?
                                    'border-2 border-[#4A2C1D] bg-[#7F5539]/80 text-white' :
                                    'border-gray-300 text-gray-700 hover:bg-gray-50'"
                                :disabled="page === '...'" x-text="page"></button>
                        </template>

                        <!-- Next Button -->
                        <button @click="changePage(pagination.current_page + 1)"
                            :disabled="pagination.current_page === pagination.last_page"
                            class="px-3 py-1 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- ============ ADD STAFF ACCOUNT MODAL ============================== -->
        <!-- =================================================================== -->
        <div x-show="addStaffModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
            style="display: none;">

            <!-- Modal Overlay -->
            <div x-show="addStaffModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/50" @click="closeAddStaffModal()"></div>

            <!-- Modal Content -->
            <div x-show="addStaffModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative bg-white shadow-md rounded-lg overflow-hidden w-full max-w-2xl border border-[#4A2C1D] max-h-[90vh] flex flex-col">

                <!-- Modal Header  -->
                <div class="relative p-6 border-b border-gray-200">
                    <button @click.prevent="closeAddStaffModal()"
                        class="absolute top-3 right-3 text-[#7F5539] hover:bg-[#4A2C1D] hover:text-white rounded p-1 z-10">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <h1 class="text-2xl font-bold text-[#4A2C1D] text-center">Add Staff Account</h1>
                </div>

                <!-- Modal Body (Scrollable) -->
                <div class="p-6 space-y-4 overflow-y-auto">
                    @if ($errors->any())
                        <div class="mb-4 text-red-700 bg-red-100 border border-red-300 rounded p-2">
                            <ul class="list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="addStaffForm" action="{{ route('sub_one.staff.storeStaffAccount') }}" method="POST"
                        class="space-y-4">
                        @csrf

                        <!-- First & Last Name in one row -->
                        <div class="mb-3 flex flex-col sm:flex-row sm:space-x-4">
                            <!-- First Name -->
                            <div class="w-full sm:w-1/2 mb-3 sm:mb-0">
                                <label for="first_name" class="block text-sm font-medium text-[#4A2C1D]">First
                                    Name</label>
                                <input type="text" name="first_name" id="first_name"
                                    class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1 focus:outline-none focus:border-[#4A2C1D] focus:ring-1 focus:ring-[#4A2C1D]"
                                    autofocus value="{{ old('first_name') }}">
                            </div>

                            <!-- Last Name -->
                            <div class="w-full sm:w-1/2">
                                <label for="last_name" class="block text-sm font-medium text-[#4A2C1D]">Last Name</label>
                                <input type="text" name="last_name" id="last_name"
                                    class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1 focus:outline-none focus:border-[#4A2C1D] focus:ring-1 focus:ring-[#4A2C1D]"
                                    value="{{ old('last_name') }}">
                            </div>
                        </div>

                        <!-- Contact Number -->
                        <div class="mb-3">
                            <label for="contact_no" class="block text-sm font-medium text-[#4A2C1D]">Contact
                                Number</label>
                            <input type="text" name="contact_no" id="contact_no"
                                class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1 focus:outline-none focus:border-[#4A2C1D] focus:ring-1 focus:ring-[#4A2C1D]"
                                value="{{ old('contact_no') }}">
                        </div>

                        <!-- Address -->
                        <div class="mb-3">
                            <label for="address" class="block text-sm font-medium text-[#4A2C1D]">Address</label>
                            <input type="text" name="address" id="address"
                                class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1 focus:outline-none focus:border-[#4A2C1D] focus:ring-1 focus:ring-[#4A2C1D]"
                                value="{{ old('address') }}">
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="block text-sm font-medium text-[#4A2C1D]">Email</label>
                            <input type="email" name="email" id="email"
                                class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1 focus:outline-none focus:border-[#4A2C1D] focus:ring-1 focus:ring-[#4A2C1D]"
                                value="{{ old('email') }}">
                        </div>
                    </form>
                </div>

                <!-- Modal Footer (Fixed) -->
                <div class="p-6 border-t border-gray-200">
                    <div class="flex gap-3">
                        <button type="submit" form="addStaffForm"
                            class="bg-[#7F5539] text-white w-full px-4 py-2 rounded hover:bg-[#4A2C1D]">
                            Add Staff Account
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <!-- Archive Confirmation Modal -->
        <div x-show="archiveConfirmationModal" x-cloak
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" @click="archiveConfirmationModal = false"></div>
            <div class="relative bg-white rounded-lg p-6 w-full max-w-md">
                <h4 class="text-lg font-bold text-[#4A2C1D] mb-2">Confirm Archive</h4>
                <p class="text-gray-600 mb-4">
                    Archive account for <strong x-text="archiveStaffName"></strong>?
                </p>
                <div class="flex space-x-3">
                    <button @click="archiveConfirmationModal = false"
                        class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                        Cancel
                    </button>
                    <form :action="`{{ route('sub_one.staff.deactivateStaffAccount', '') }}/${archiveStaffId}`" method="POST" class="flex-1">
                @csrf
                @method('PATCH')
                <button type="submit"
                    class="w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-700 transition-colors">
                    Archive
                </button>
            </form>
                </div>
            </div>
        </div>

        <!-- Filter Modal -->
        <div x-show="showFilters" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showFilters = false"></div>
                <!-- Keep the same max-w-md across all screen sizes -->
                <div
                    class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl sm:my-8 sm:align-middle max-w-md w-full sm:p-6">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Filter Staff</h3>

                        <div x-data="filterState()">
                            <!-- Filter Inputs -->
                            <div class="space-y-4">
                                <!-- Account Status -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Account Status</label>
                                    <select x-model="filters.account_status"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                        <option value="">All Status</option>
                                        <option value="1">Verified</option>
                                        <option value="0">Suspended</option>
                                    </select>
                                </div>
                                <!-- Shift Status -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Shift Status</label>
                                    <select x-model="filters.shift_status"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                        <option value="">All Shift Status</option>
                                        <option value="with_shift">With Shift</option>
                                        <option value="no_shift">No Shift</option>
                                    </select>
                                </div>
                                <!-- Branch Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                                    <select x-model="filters.branch_id"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                        <option value="">All Branches</option>
                                        <template x-for="branch in branches" :key="branch.id">
                                            <option :value="branch.id" x-text="branch.branch_name"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-6 flex space-x-3">
                                <button @click="clearFilters()"
                                    class="flex-1 inline-flex justify-center px-4 py-2 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539]">
                                    Clear
                                </button>
                                <button @click="applyFilters()"
                                    class="flex-1 inline-flex justify-center px-4 py-2 border border-transparent text-base font-medium rounded-lg text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539]">
                                    Apply Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            // Filter state for the sidebar
            Alpine.data('filterState', () => ({
                branches: @json($branches->map->only(['id', 'branch_name'])),
                filters: {
                    account_status: '{{ request('account_status', '') }}',
                    shift_status: '{{ request('shift_status', '') }}',
                    branch_id: '{{ request('branch_id', '') }}',
                },
                clearFilters() {
                    this.filters = {
                        account_status: '',
                        shift_status: '',
                        branch_id: '',
                    };
                },
                applyFilters() {
                    // Get the main component instance and call its applyFilters method
                    const mainComponent = Alpine.$data(document.querySelector(
                        '[x-data="staffManagement()"]'));

                    // Merge modal filters with the main search query
                    const newFilters = {
                        ...mainComponent.currentFilters, // Start with all current filters
                        ...this.filters, // Overwrite with modal filters
                        search: mainComponent.searchQuery // Ensure search is in sync
                    };

                    mainComponent.applyFilters(newFilters);
                }
            }));

            // Main component
            Alpine.data('staffManagement', () => ({
                // Initial state
                staffAccounts: @json($staff_accounts->items() ?? []),
                pagination: @json($staff_accounts->toArray()),
                stats: @json($stats),
                currentFilters: {
                    account_status: '{{ request('account_status', '') }}',
                    shift_status: '{{ request('shift_status', '') }}',
                    branch_id: '{{ request('branch_id', '') }}',
                    search: '{{ request('search', '') }}',
                },
                searchQuery: '{{ request('search', '') }}',
                showFilters: false,
                paginationLinks: [],
                isLoading: false,

                // Modal states
                addStaffModal: @json($errors->any()),
                archiveConfirmationModal: false,
                archiveStaffId: null,
                archiveStaffName: '',

                // Data for dropdowns
                branches: @json($branches->map->only(['id', 'branch_name'])),
                allStaffAccounts: @json($allStaffAccounts),
                accountStatuses: [{
                        id: 1,
                        name: 'Verified'
                    },
                    {
                        id: 0,
                        name: 'Suspended'
                    }
                ],

                init() {
                    this.updatePaginationLinks();
                    this.updateActiveFilters();
                },

                // Computed properties
                get hasActiveFilters() {
                    return Object.values(this.currentFilters).some(value => value !== '');
                },

                get activeFilters() {
                    const filters = [];

                    if (this.currentFilters.search) {
                        filters.push({
                            key: 'search',
                            label: `Search: ${this.currentFilters.search}`
                        });
                    }

                    if (this.currentFilters.account_status) {
                        filters.push({
                            key: 'account_status',
                            label: `Status: ${this.getAccountStatusText(this.currentFilters.account_status)}`
                        });
                    }

                    if (this.currentFilters.shift_status) {
                        filters.push({
                            key: 'shift_status',
                            label: `Shift: ${this.currentFilters.shift_status === 'with_shift' ? 'With Shift' : 'No Shift'}`
                        });
                    }

                    if (this.currentFilters.branch_id) {
                        const branch = this.branches.find(b => b.id == this.currentFilters
                            .branch_id);
                        if (branch) {
                            filters.push({
                                key: 'branch_id',
                                label: `Branch: ${branch.branch_name}`
                            });
                        }
                    }

                    return filters;
                },

                // Helper methods
                getAccountStatusClass(status) {
                    const statusClasses = {
                        0: 'bg-red-100 text-red-800',
                        1: 'bg-green-100 text-green-800',
                    };
                    return statusClasses[status] || 'bg-gray-100 text-gray-800';
                },

                getAccountStatusText(status) {
                    const statusText = {
                        0: 'Suspended',
                        1: 'Verified',
                    };
                    return statusText[status] || 'Unknown';
                },

                // New methods for shift display
                getCurrentShift(staff) {
                    if (!staff.staff_schedules || staff.staff_schedules.length === 0) {
                        return null;
                    }

                    // Get the most current shift (pending or on-duty)
                    const now = new Date();

                    // Try to find an on-duty shift first
                    let currentShift = staff.staff_schedules.find(schedule =>
                        schedule.staff_shift_schedule_status == 1 // On-duty
                    );

                    // If no on-duty shift, find the next pending shift
                    if (!currentShift) {
                        currentShift = staff.staff_schedules.find(schedule =>
                            schedule.staff_shift_schedule_status == 2 // Pending
                        );
                    }

                    return currentShift || null;
                },

                getShiftStatusClass(status) {
                    const statusClasses = {
                        0: 'bg-green-100 text-green-800', // Completed
                        1: 'bg-blue-100 text-blue-800', // On-duty
                        2: 'bg-yellow-100 text-yellow-800', // Pending
                    };
                    return statusClasses[status] || 'bg-gray-100 text-gray-800';
                },

                getShiftStatusText(status) {
                    const statusText = {
                        0: 'Completed',
                        1: 'On-duty',
                        2: 'Pending',
                    };
                    return statusText[status] || 'Unknown';
                },

                formatShiftDate(dateString) {
                    if (!dateString) return '';
                    const date = new Date(dateString);
                    return date.toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric'
                    });
                },

                formatTime(timeString) {
                    if (!timeString) return '';
                    const [hours, minutes] = timeString.split(':');
                    const hour = parseInt(hours);
                    const ampm = hour >= 12 ? 'PM' : 'AM';
                    const displayHour = hour % 12 || 12;
                    return `${displayHour}:${minutes} ${ampm}`;
                },

                // Modal methods
                openAddStaffModal() {
                    this.addStaffModal = true;
                },

                closeAddStaffModal() {
                    this.addStaffModal = false;
                },

                confirmArchive(staffId, staffName) {
                    this.archiveStaffId = staffId;
                    this.archiveStaffName = staffName;
                    this.archiveConfirmationModal = true;
                },

                // Search functionality
                async performSearch() {
                    this.currentFilters.search = this.searchQuery;
                    await this.applyFilters(this.currentFilters);
                },

                // Filter functionality
                async applyFilters(filters) {
                    this.isLoading = true;
                    this.showFilters = false;
                    this.currentFilters = {
                        ...filters
                    };

                    try {
                        const queryParams = new URLSearchParams();

                        // Add filters to query params
                        Object.entries(this.currentFilters).forEach(([key, value]) => {
                            if (value) {
                                queryParams.append(key, value);
                            }
                        });

                        const url = `?${queryParams.toString()}&ajax=true`;
                        const response = await fetch(url, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }

                        const data = await response.json();

                        if (data.success) {
                            this.staffAccounts = data.data;
                            this.pagination = data.pagination;
                            this.stats = data.stats;
                            this.updatePaginationLinks();
                            this.updateActiveFilters();
                        } else {
                            throw new Error(data.message || 'Filter application failed');
                        }
                    } catch (error) {
                        console.error('Error applying filters:', error);
                    } finally {
                        this.isLoading = false;
                    }
                },

                async clearAllFilters() {
                    this.isLoading = true;
                    this.showFilters = false;
                    this.searchQuery = '';
                    this.currentFilters = {
                        account_status: '',
                        shift_status: '',
                        branch_id: '',
                        search: '',
                    };

                    try {
                        const url = `?ajax=true`;
                        const response = await fetch(url, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }

                        const data = await response.json();

                        if (data.success) {
                            this.staffAccounts = data.data;
                            this.pagination = data.pagination;
                            this.stats = data.stats;
                            this.updatePaginationLinks();
                            this.updateActiveFilters();
                        } else {
                            throw new Error(data.message || 'Filter clearing failed');
                        }
                    } catch (error) {
                        console.error('Error clearing filters:', error);
                    } finally {
                        this.isLoading = false;
                    }
                },

                removeFilter(filterKey) {
                    if (filterKey === 'search') {
                        this.searchQuery = '';
                        this.currentFilters.search = '';
                    } else {
                        this.currentFilters[filterKey] = '';
                    }
                    this.applyFilters(this.currentFilters);
                },

                updateActiveFilters() {
                    // This method updates the URL without reloading the page
                    const queryParams = new URLSearchParams();
                    Object.entries(this.currentFilters).forEach(([key, value]) => {
                        if (value) {
                            queryParams.append(key, value);
                        }
                    });

                    const newUrl = `${window.location.pathname}?${queryParams.toString()}`;
                    window.history.replaceState({}, '', newUrl);
                },

                // Pagination
                async changePage(page) {
                    if (page < 1 || page > this.pagination.last_page) return;

                    try {
                        this.isLoading = true;

                        const queryParams = new URLSearchParams();

                        // Add current filters to query params
                        Object.entries(this.currentFilters).forEach(([key, value]) => {
                            if (value) {
                                queryParams.append(key, value);
                            }
                        });

                        queryParams.append('page', page);
                        queryParams.append('ajax', 'true');

                        const url = `?${queryParams.toString()}`;
                        const response = await fetch(url, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }

                        const data = await response.json();

                        if (data.success) {
                            this.staffAccounts = data.data;
                            this.pagination = data.pagination;
                            this.stats = data.stats;
                            this.updatePaginationLinks();
                        } else {
                            throw new Error(data.message || 'Pagination failed');
                        }
                    } catch (error) {
                        console.error('Error changing page:', error);
                    } finally {
                        this.isLoading = false;
                    }
                },

                // Update pagination links
                updatePaginationLinks() {
                    if (!this.pagination || !this.pagination.last_page) {
                        this.paginationLinks = [];
                        return;
                    }

                    const current = this.pagination.current_page;
                    const last = this.pagination.last_page;
                    const delta = 2;
                    const range = [];
                    const rangeWithDots = [];

                    for (let i = 1; i <= last; i++) {
                        if (i === 1 || i === last || (i >= current - delta && i <= current + delta)) {
                            range.push(i);
                        }
                    }

                    let prev = 0;
                    for (let i of range) {
                        if (prev) {
                            if (i - prev === 2) {
                                rangeWithDots.push(prev + 1);
                            } else if (i - prev !== 1) {
                                rangeWithDots.push('...');
                            }
                        }
                        rangeWithDots.push(i);
                        prev = i;
                    }

                    this.paginationLinks = rangeWithDots;
                },

                addBodyClass() {
                    document.body.classList.add('modal-open');
                }
            }));
        });
    </script>

    <style>
        .modal-open {
            overflow: hidden;
        }
    </style>
@endsection
