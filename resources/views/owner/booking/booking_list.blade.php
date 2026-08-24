@extends('layouts.app')

@section('title', 'Bookings')

@section('content')
    <style>
        /* Modal scroll lock */
        body.modal-open {
            overflow: hidden !important;
            padding-right: 0 !important;
        }

        /* Modal container styles */
        .modal-container {
            max-height: calc(100vh - 2rem);
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            flex-shrink: 0;
        }

        .modal-content {
            flex: 1;
            overflow-y: auto;
        }

        .modal-footer {
            flex-shrink: 0;
        }

        /* Custom scrollbar for modal content */
        .modal-content::-webkit-scrollbar {
            width: 6px;
        }

        .modal-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .modal-content::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        .shadow-right {
            box-shadow: 4px 0 8px -2px rgba(0, 0, 0, 0.1);
        }

        /* Adjust the shadow for the table header */
        thead .shadow-right {
            box-shadow: 4px 0 8px -2px rgba(0, 0, 0, 0.15);
        }

        /* Ensure proper stacking context */
        .sticky {
            position: sticky;
        }

        /* Filter modal specific styles */
        .filter-modal-body {
            max-height: 60vh;
            overflow-y: auto;
            padding-right: 8px;
        }

        .filter-modal-body::-webkit-scrollbar {
            width: 4px;
        }

        .filter-modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 2px;
        }

        .filter-modal-body::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 2px;
        }
    </style>

    <div x-data="bookingListPage()" x-init="init()" class="p-4">
        <!-- Header -->
        <h1 class="text-2xl font-bold text-gray-900 mb-8 text-center">Booking List</h1>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-5 xl:grid-cols-5 gap-4 mb-8">
            <!-- Total Bookings -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Bookings</p>
                        <p class="text-xl font-bold text-gray-900" x-text="stats.total_bookings"></p>
                    </div>
                    <div class="p-2 bg-[#4A2C1D]/10 rounded-lg">
                        <svg class="w-5 h-5 text-[#7F5539]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Booked (Confirmed) Bookings -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Booked</p>
                        <p class="text-xl font-bold text-gray-900" x-text="stats.booked_bookings || 0"></p>
                    </div>
                    <div class="p-2 bg-green-50 rounded-lg">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Pending Bookings -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Pending</p>
                        <p class="text-xl font-bold text-gray-900" x-text="stats.pending_bookings || 0"></p>
                    </div>
                    <div class="p-2 bg-yellow-50 rounded-lg">
                        <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- No-show Bookings -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">No-show</p>
                        <p class="text-xl font-bold text-gray-900" x-text="stats.no_show_bookings || 0"></p>
                    </div>
                    <div class="p-2 bg-orange-50 rounded-lg">
                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Completed Bookings -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Completed</p>
                        <p class="text-xl font-bold text-gray-900" x-text="stats.completed_bookings || 0"></p>
                    </div>
                    <div class="p-2 bg-purple-50 rounded-lg">
                        <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Table Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-4 space-y-4 lg:space-y-0">
                    <!-- Left: Header -->
                    <h2 class="text-lg font-semibold text-gray-900">Booking Records</h2>

                    <!-- Right: Search + Filter -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-3 w-full lg:w-auto">
                        <div class="flex flex-row items-center space-x-3 w-full justify-end">
                            <!-- Search Input -->
                            <div class="relative w-full sm:w-80">
                                <input type="text" x-model="searchQuery" @input.debounce.500ms="performSearch()"
                                    placeholder="Search by customer name or booking ref..."
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
                                class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                                </svg>
                                Filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Active Filters Badge -->
                <div x-show="hasActiveFilters" class="flex items-center justify-end space-x-2">
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
                    <button @click="clearAllFilters()" class="text-sm text-[#4A2C1D] hover:text-[#7F5539] font-medium"
                        x-show="hasActiveFilters && !isLoading">
                        Clear all
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto relative">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 z-30 bg-gray-50 shadow-right select-text">
                                Customer
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider select-text">
                                Booking Ref No
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Branch
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Service
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Room & Seat
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Booking Type
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Booking Status
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Booking Date
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(booking, index) in bookings" :key="booking.id">
                            <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-100'">
                                <!-- Customer -->
                                <td class="px-6 py-4 whitespace-nowrap sticky left-0 z-20 shadow-right select-text"
                                    :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-100'">
                                    <div class="flex items-center">
                                        <div class="rounded-lg p-2 mr-3" 
                                             :class="index % 2 === 0 ? 'bg-gray-100' : 'bg-white'">
                                            <svg class="w-4 h-4" 
                                                 :class="index % 2 === 0 ? 'text-gray-600' : 'text-gray-600'"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"
                                                x-text="(booking.customer_account?.first_name || 'N/A') + ' ' + (booking.customer_account?.last_name || '')">
                                            </div>
                                        </div>
                                    </div>
                                </td>
            
                                <!-- Booking Ref No -->
                                <td class="px-6 py-4 whitespace-nowrap select-text">
                                    <div class="text-sm font-medium text-gray-900"
                                        x-text="booking.booking_ref_no || 'N/A'"></div>
                                </td>
            
                                <!-- Branch -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="rounded-lg p-2 mr-3" 
                                             :class="index % 2 === 0 ? 'bg-gray-100' : 'bg-white'">
                                            <svg class="w-4 h-4" 
                                                 :class="index % 2 === 0 ? 'text-gray-600' : 'text-gray-600'"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 8v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                                </path>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"
                                                x-text="booking.branch?.branch_name || 'N/A'"></div>
                                        </div>
                                    </div>
                                </td>
            
                                <!-- Service -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="rounded-lg p-2 mr-3" 
                                             :class="index % 2 === 0 ? 'bg-gray-100' : 'bg-white'">
                                            <svg class="w-4 h-4" 
                                                 :class="index % 2 === 0 ? 'text-gray-600' : 'text-gray-600'"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z">
                                                </path>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"
                                                x-text="booking.service_name?.service_name || 'N/A'"></div>
                                            <div class="text-sm text-gray-500"
                                                x-text="booking.service_category?.service_category || 'N/A'"></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Room & Seat -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col space-y-1">
                                        <template x-if="booking?.seat?.room_no">
                                            <div class="flex items-center space-x-1">
                                                <div class="bg-blue-100 rounded p-1">
                                                    <svg class="w-3 h-3 text-blue-600" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 10V6a3 3 0 013-3v0a3 3 0 013 3v4m-6 4h6m-6 4h6m2 5H7a2 2 0 01-2-2v-4a2 2 0 012-2h10a2 2 0 012 2v4a2 2 0 01-2 2z" />
                                                    </svg>
                                                </div>
                                                <span class="text-xs font-medium text-gray-900"
                                                    x-text="'Room ' + booking.seat.room_no"></span>
                                            </div>
                                        </template>
                                        <template x-if="booking?.seat?.seat_no">
                                            <div class="flex items-center space-x-1">
                                                <div class="bg-green-100 rounded p-1">
                                                    <svg class="w-3 h-3 text-green-600" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                                    </svg>
                                                </div>
                                                <span class="text-xs font-medium text-gray-900"
                                                    x-text="'Seat ' + booking.seat.seat_no"></span>
                                            </div>
                                        </template>
                                        <template x-if="!booking?.seat?.room_no && !booking?.seat?.seat_no">
                                            <span class="text-xs text-gray-500">N/A</span>
                                        </template>
                                    </div>
                                </td>

                                <!-- Booking Type -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
                                        :class="booking.booking_type === 1 ? 'bg-blue-100 text-blue-800' :
                                            'bg-green-100 text-green-800'"
                                        x-text="booking.booking_type === 1 ? 'Online' : 'Walk-in'"></span>
                                </td>

                                <!-- Booking Status -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
                                        :class="{
                                            'bg-red-100 text-red-800': booking.booking_status == 0,
                                            'bg-blue-100 text-blue-800': booking.booking_status == 1,
                                            'bg-yellow-100 text-yellow-800': booking.booking_status == 2,
                                            'bg-orange-200 text-orange-600': booking.booking_status == 3,
                                            'bg-green-100 text-green-800': booking.booking_status == 4,
                                            'bg-gray-100 text-gray-800': ![0, 1, 2, 3, 4].includes(booking
                                                .booking_status)
                                        }">
                                        <span x-text="getBookingStatusText(booking.booking_status)"></span>
                                    </span>
                                </td>

                                <!-- Booking Date -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="text-sm text-gray-900" x-text="formatDate(booking.booking_date)"></div>
                                </td>

                                <!-- Actions -->
<td class="px-6 py-4 whitespace-nowrap">
    <div class="flex items-center gap-2 min-h-[40px] w-full">
        <!-- Left side - Buttons -->
        <div class="flex flex-wrap gap-2">
            <!-- View Button -->
            <div class="flex-1 min-w-[70px]">
                <a :href="`/sub_one/booking_lists/details/${booking.uuid}`"
                    class="inline-flex items-center justify-center w-full px-4 py-1.5 bg-[#7F5539] text-white rounded-lg hover:bg-[#4A2C1D] transition-colors text-xs font-medium whitespace-nowrap">
                    View
                </a>
            </div>

            <!-- Add Note Button -->
            <div class="flex-1 min-w-[70px]">
                <button @click="openNoteModal(booking); addBodyClass()"
                    class="inline-flex items-center justify-center w-full px-4 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-xs font-medium whitespace-nowrap">
                    Notes
                </button>
            </div>

            <!-- Confirm Booking Button (Only for Pending Online Bookings) -->
            <div class="flex-1 min-w-[70px]"
                x-show="booking.booking_status == 2 && booking.booking_type == 1">
                <button @click="openConfirmModal(booking); addBodyClass()"
                    class="inline-flex items-center justify-center w-full px-5 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-xs font-medium whitespace-nowrap">
                    Confirm
                </button>
            </div>

            <!-- No Show Button (Only for Booked status that are not checked in) -->
            <div class="flex-1 min-w-[70px]"
                x-show="booking.booking_status == 1 && booking.checkin_status !== 1">
                <button @click="openNoShowModal(booking); addBodyClass()"
                    class="inline-flex items-center justify-center w-full px-4 py-1.5 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors text-xs font-medium whitespace-nowrap">
                    No Show
                </button>
            </div>

            <!-- Main Payment Button - Shows for Booked status (1) when customer has checked out -->
            <div class="flex-1 min-w-[70px]"
                x-show="booking.booking_status == 1 && booking.checkin_status === 0 && booking.payment?.payment_status !== 1">
                <a :href="`/sub_one/booking_lists/main-payment/${booking.uuid}`"
                    class="inline-flex items-center justify-center w-full px-4 py-1.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-xs font-medium whitespace-nowrap">
                    Main Pay
                </a>
            </div>

            <!-- Extension Payment Button - Shows for Booked status (1) when customer has checked out AND has extension time -->
            <div class="flex-1 min-w-[70px]"
                x-show="booking.booking_status == 1 && booking.checkin_status === 0 && booking.extended_time_used > 0 && booking.extension_payment?.payment_status !== 1">
                <a :href="`/sub_one/booking_lists/extension-payment/${booking.uuid}`"
                    class="inline-flex items-center justify-center w-full px-4 py-1.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-xs font-medium whitespace-nowrap">
                    Ext Pay
                </a>
            </div>

            <!-- Update Main Payment Button - Shows for Completed status (4) when payment is unpaid -->
            <div class="flex-1 min-w-[70px]"
                x-show="booking.booking_status == 4 && booking.payment?.payment_status === 2">
                <a :href="`/sub_one/booking_lists/main-payment/${booking.uuid}`"
                    class="inline-flex items-center justify-center w-full px-4 py-1.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-xs font-medium whitespace-nowrap">
                    Main Pay
                </a>
            </div>

            <!-- Update Extension Payment Button - Shows for Completed status (4) when extension payment is unpaid -->
            <div class="flex-1 min-w-[70px]"
                x-show="booking.booking_status == 4 && booking.extended_time_used > 0 && booking.extension_payment?.payment_status === 2">
                <a :href="`/sub_one/booking_lists/extension-payment/${booking.uuid}`"
                    class="inline-flex items-center justify-center w-full px-4 py-1.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-xs font-medium whitespace-nowrap">
                    Ext Pay
                </a>
            </div>

            <!-- Order Payment Button - Shows when checkin_status is 0 (checked out), main AND extension payments are paid, and there are unpaid pay-later orders -->
            <div class="flex-1 min-w-[70px]"
                x-show="hasUnpaidPayLaterOrders(booking)">
                <a :href="`/sub_one/booking_lists/order-payment/${booking.uuid}`"
                    class="inline-flex items-center justify-center w-full px-4 py-1.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-xs font-medium whitespace-nowrap">
                    Ord Pay
                </a>
            </div>
        </div>
    </div>
</td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <tr x-show="!bookings.length">
                            <td colspan="15" class="px-6 py-12 text-center bg-white">
                                <div class="text-gray-400">
                                    <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    <h5 class="text-sm font-medium text-gray-900"
                                        x-text="hasActiveFilters ? 'No bookings match your filters' : 'No bookings found'">
                                    </h5>
                                    <p class="text-sm text-gray-500"
                                        x-text="hasActiveFilters ? 'Try adjusting your filters.' : 'When bookings are made, they will appear here.'">
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div x-show="pagination && pagination.last_page > 1" class="px-4 sm:px-6 py-4 border-t border-gray-200">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <!-- Results Info - Always visible -->
                    <div class="text-sm text-gray-700 text-center sm:text-left">
                        Showing <span x-text="pagination.from || 0"></span> to <span x-text="pagination.to || 0"></span>
                        of <span x-text="pagination.total || 0"></span> entries
                    </div>
                    
                    <!-- Pagination Controls -->
                    <div class="flex flex-wrap justify-center items-center gap-2">
                        <!-- Previous Button -->
                        <button @click="changePage(pagination.current_page - 1)" 
                                :disabled="pagination.current_page === 1"
                                class="px-3 py-2 sm:py-1 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                            <span class="hidden sm:inline">Previous</span>
                            <span class="sm:hidden">←</span>
                        </button>

                        <!-- Page Numbers - Show fewer on mobile -->
                        <template x-for="page in paginationLinks" :key="page">
                            <button @click="changePage(page)" 
                                    class="px-3 py-2 sm:py-1 border rounded-lg text-sm font-medium transition-colors duration-200"
                                    :class="page === pagination.current_page ?
                                        'border-2 border-[#4A2C1D] bg-[#7F5539]/80 text-white' :
                                        'border-gray-300 text-gray-700 hover:bg-gray-50'"
                                    :disabled="page === '...'"
                                    x-text="page"
                                    :class="{
                                        'hidden sm:inline-flex': shouldHidePageNumber(page)
                                    }"></button>
                        </template>

                        <!-- Next Button -->
                        <button @click="changePage(pagination.current_page + 1)"
                                :disabled="pagination.current_page === pagination.last_page"
                                class="px-3 py-2 sm:py-1 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                            <span class="hidden sm:inline">Next</span>
                            <span class="sm:hidden">→</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Modal - Fixed Design -->
        <div x-show="showFilters" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showFilters = false; removeBodyClass()">
                </div>
                <div class="relative inline-block w-full max-w-md bg-white rounded-lg shadow-xl transform transition-all">
                    <div x-data="filterState()" class="modal-container">
                        <!-- Filter Modal Header -->
                        <div class="modal-header border-b border-gray-200 p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Filter Search</h3>
                            <button type="button" @click="showFilters = false; removeBodyClass()"
                                class="absolute right-4 top-4 text-gray-400 hover:text-gray-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Filter Modal Body - Scrollable -->
                        <div class="filter-modal-body p-6">
                            <div class="space-y-4">
                                <!-- Booking Type -->
                                <div>
                                    <label for="filter_booking_type"
                                        class="block text-sm font-medium text-gray-700 mb-1">Booking Type</label>
                                    <select id="filter_booking_type" x-model="filters.booking_type"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                        <option value="all">All Booking Types</option>
                                        <option value="1">Online</option>
                                        <option value="0">Walk-in</option>
                                    </select>
                                </div>

                                <!-- Date Range -->
                                <div>
                                    <label for="filter_date_start" class="block text-sm font-medium text-gray-700 mb-1">Date
                                        Start</label>
                                    <input id="filter_date_start" type="date" x-model="filters.date_start"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                </div>
                                <div>
                                    <label for="filter_date_end" class="block text-sm font-medium text-gray-700 mb-1">Date
                                        End</label>
                                    <input id="filter_date_end" type="date" x-model="filters.date_end"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                </div>

                                <!-- Booking Date -->
                                <div>
                                    <label for="filter_booking_date"
                                        class="block text-sm font-medium text-gray-700 mb-1">
                                        Booking Date
                                    </label>

                                    <p class="text-xs text-gray-500 mb-1">
                                        Don't include the date start and date end when applying the booking date filter.
                                    </p>

                                    <input id="filter_booking_date" type="date" x-model="filters.booking_date"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                </div>

                                <!-- Branch -->
                                <div>
                                    <label for="filter_branch_id"
                                        class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                                    <select id="filter_branch_id" x-model="filters.branch_id" @change="updateFilterOptions()"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                        <option value="">All Branches</option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Service Category -->
                                <div>
                                    <label for="filter_service_category_id"
                                        class="block text-sm font-medium text-gray-700 mb-1">Service Category</label>
                                    <select id="filter_service_category_id" x-model="filters.service_category_id"
                                        @change="updateFilterOptions()"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                        <option value="">All Categories</option>
                                        <template x-for="category in dropdowns.categories" :key="category.id">
                                            <option :value="category.id" x-text="category.service_category"></option>
                                        </template>
                                    </select>
                                </div>

                                <!-- Service Name -->
                                <div>
                                    <label for="filter_service_name_id"
                                        class="block text-sm font-medium text-gray-700 mb-1">Service Name</label>
                                    <select id="filter_service_name_id" x-model="filters.service_name_id"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                        <option value="">All Services</option>
                                        <template x-for="service in dropdowns.services" :key="service.id">
                                            <option :value="service.id" x-text="service.service_name"></option>
                                        </template>
                                    </select>
                                </div>

                                <!-- Payment Status -->
                                <div>
                                    <label for="filter_payment_status"
                                        class="block text-sm font-medium text-gray-700 mb-1">Payment Status</label>
                                    <select id="filter_payment_status" x-model="filters.payment_status"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                        <option value="">All Payment Status</option>
                                        <option value="1">Paid</option>
                                        <option value="2">Unpaid</option>
                                    </select>
                                </div>

                                <!-- Booking Status -->
                                <div>
                                    <label for="filter_booking_status"
                                        class="block text-sm font-medium text-gray-700 mb-1">Booking Status</label>
                                    <select id="filter_booking_status" x-model="filters.booking_status"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                        <option value="">All Booking Status</option>
                                        <option value="1">Booked</option>
                                        <option value="2">Pending</option>
                                        <option value="3">No-show</option>
                                        <option value="4">Completed</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Filter Modal Footer -->
                        <div class="modal-footer border-t border-gray-200 p-6 flex justify-center items-center gap-3">
                            <button @click="clearFilters()" type="button"
                                class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539] transition-colors">
                                Clear
                            </button>
                            <button @click="applyFilters()"
                                class="px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539] transition-colors">
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm Booking Modal -->
        <div x-show="showConfirmModal" x-cloak @click.self="closeConfirmModal()"
            class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50 backdrop-blur-sm">
            <div class="flex items-center justify-center min-h-screen p-4 h-[90vh]">
                <div class="bg-white rounded-lg shadow-xl w-full max-w-md relative">

                    <form method="POST" action="{{ route('sub_one.booking_lists.confirmBooking') }}"
                        id="confirmBookingForm">
                        @csrf
                        <input type="hidden" name="booking_id" :value="selectedBooking?.id">

                        <!-- Header (NOT scrollable) -->
                        <div class="p-6 border-b">
                            <h2 class="text-xl font-bold text-gray-900 mb-2 text-center">Confirm Booking</h2>
                            <p class="text-sm text-gray-700 text-center">
                                Are you sure you want to confirm this booking? QR code will be sent to the customer's email.
                            </p>
                        </div>

                        <!-- Scrollable Body ONLY -->
                        <div class="max-h-[70vh] overflow-y-auto">
                            <div class="p-6">

                                <!-- Form Fields for Validation (HIDDEN) -->
                                <div class="hidden">
                                    <input type="email" name="customer_email" id="customer_email"
                                        :value="selectedBooking?.customer_account?.email || ''">
                                    <input type="text" name="contact_no" id="contact_no"
                                        :value="selectedBooking?.customer_account?.contact_no || ''">
                                </div>

                                <!-- Customer Information -->
                                <template x-if="selectedBooking">
                                    <div class="mb-6 text-left p-4 bg-gray-50 rounded-lg border space-y-3">

                                        <p><span class="font-medium text-gray-700">Customer:</span>
                                            <span class="font-semibold text-gray-900"
                                                x-text="(selectedBooking.customer_account?.first_name || '') + ' ' +
                                            (selectedBooking.customer_account?.last_name || '')"></span>
                                        </p>

                                        <p><span class="font-medium text-gray-700">Email:</span>
                                            <span class="font-semibold text-gray-900"
                                                x-text="selectedBooking.customer_account?.email || 'N/A'"></span>
                                        </p>

                                        <p><span class="font-medium text-gray-700">Contact No:</span>
                                            <span class="font-semibold text-gray-900"
                                                x-text="selectedBooking.customer_account?.contact_no || 'N/A'"></span>
                                        </p>

                                        <p><span class="font-medium text-gray-700">Branch:</span>
                                            <span class="font-semibold text-gray-900"
                                                x-text="selectedBooking.branch?.branch_name || 'N/A'"></span>
                                        </p>

                                        <p><span class="font-medium text-gray-700">Service Category:</span>
                                            <span class="font-semibold text-gray-900"
                                                x-text="selectedBooking.service_category?.service_category || 'N/A'"></span>
                                        </p>

                                        <p><span class="font-medium text-gray-700">Service:</span>
                                            <span class="font-semibold text-gray-900"
                                                x-text="selectedBooking.service_name?.service_name || 'N/A'"></span>
                                        </p>

                                        <p><span class="font-medium text-gray-700">Booking Date:</span>
                                            <span class="font-semibold text-gray-900"
                                                x-text="formatDate(selectedBooking.date_start) + ' - ' +
                                            formatDate(selectedBooking.date_end)"></span>
                                        </p>

                                        <p><span class="font-medium text-gray-700">Time:</span>
                                            <span class="font-semibold text-gray-900"
                                                x-text="formatTime(selectedBooking.start_time) + ' - ' +
                                            formatTime(selectedBooking.end_time)"></span>
                                        </p>

                                        <p><span class="font-medium text-gray-700">Payment Status:</span>
                                            <span class="font-semibold text-gray-900"
                                                x-text="getPaymentStatusText(selectedBooking.payment?.payment_status)"></span>
                                        </p>

                                        <p><span class="font-medium text-gray-700">Amount Paid:</span>
                                            <span class="font-semibold text-gray-900"
                                                x-text="selectedBooking.payment
                                            ? '₱' + Number(selectedBooking.payment.amount_paid || 0)
                                                .toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})
                                            : '₱0.00'"></span>
                                        </p>
                                    </div>
                                </template>

                                <!-- Email Notification -->
                                <div class="p-3 bg-green-50 rounded-lg border border-green-200">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        <p class="text-sm text-green-800 font-medium">QR code will be sent automatically to
                                            customer's email</p>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Footer (NOT scrollable) -->
                        <div class="border-t border-gray-200 p-6 flex justify-end gap-3">
                            <button type="button" @click="closeConfirmModal(); removeBodyClass()"
                                class="px-4 py-2 font-semibold text-gray-800 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors">
                                Cancel
                            </button>

                            <button type="submit"
                                class="px-4 py-2 font-semibold text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors">
                                Confirm & Send QR Code
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <!-- No Show Modal -->
        <div x-show="showNoShowModal" x-cloak @click.self="closeNoShowModal()"
            class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50 backdrop-blur-sm">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="bg-white rounded-lg shadow-xl w-full max-w-md relative text-center">
                    <form method="POST" action="{{ route('sub_one.booking_lists.markNoShow') }}">
                        @csrf
                        <input type="hidden" name="booking_id" :value="selectedBooking?.id">
                        <div class="border-b border-gray-200 p-6 px-8">
                            <h2 class="text-xl font-bold text-gray-900">Mark as No Show</h2>
                        </div>
                        <div class="p-8">
                            <p class="text-sm text-gray-700">Are you sure you want to mark this booking as "No Show"?</p>
                            <template x-if="selectedBooking">
                                <div class="mt-4 text-left p-4 bg-orange-50 rounded-lg border border-orange-200 space-y-2">
                                    <p><span class="font-medium text-gray-700">Customer:</span> <span
                                            class="font-semibold text-gray-900"
                                            x-text="(selectedBooking.customer_account?.first_name || '') + ' ' + (selectedBooking.customer_account?.last_name || '')"></span>
                                    </p>
                                    <p><span class="font-medium text-gray-700">Service:</span> <span
                                            class="font-semibold text-gray-900"
                                            x-text="selectedBooking.service_name?.service_name || 'N/A'"></span></p>
                                    <p><span class="font-medium text-gray-700">Time:</span> <span
                                            class="font-semibold text-gray-900"
                                            x-text="formatTime(selectedBooking.start_time) + ' - ' + formatTime(selectedBooking.end_time)"></span>
                                    </p>
                                </div>
                            </template>
                            <div class="mt-4 p-3 bg-orange-100 border border-orange-300 rounded-lg">
                                <p class="text-sm text-orange-800 font-medium">⚠️ This action cannot be undone.</p>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 p-6 px-8 flex justify-end gap-3">
                            <button type="button" @click="closeNoShowModal(); removeBodyClass()"
                                class="px-4 py-2 font-semibold text-gray-800 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 font-semibold text-white bg-orange-600 rounded-lg hover:bg-orange-700 transition-colors focus:outline-none focus:ring-2 focus:ring-orange-500">
                                Mark as No Show
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add Note Modal -->
        <div x-show="showNoteModal" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50 backdrop-blur-sm">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="modal-container bg-white rounded-lg shadow-xl w-full max-w-2xl relative">
                    <!-- Header -->
                    <div class="modal-header border-b border-gray-200 p-6 px-8">
                        <h2 class="text-xl font-bold text-gray-900">Add Note</h2>
                        <button type="button" @click="closeNoteModal()"
                            class="absolute right-6 top-6 text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="modal-content p-8">
                        <!-- Success Message -->
                        <div x-show="noteSuccessMessage" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-green-800 font-medium" x-text="noteSuccessMessage"></span>
                            </div>
                        </div>

                        <!-- Booking Info -->
                        <template x-if="selectedBooking">
                            <div class="mb-6 p-4 bg-gray-50 rounded-lg border">
                                <div class="flex items-center space-x-2 text-sm">
                                    <span class="font-medium text-gray-700">Booking Ref:</span>
                                    <span class="text-gray-900 font-semibold"
                                        x-text="selectedBooking.booking_ref_no || 'N/A'"></span>
                                </div>
                            </div>
                        </template>

                        <!-- Existing Notes -->
                        <div class="mb-6">
                            <h3 class="font-semibold text-gray-900 mb-4">Previous Notes</h3>

                            <!-- Loading State -->
                            <div x-show="isLoadingNotes" class="text-center py-8">
                                <div class="inline-flex items-center text-gray-500">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    Loading notes...
                                </div>
                            </div>

                            <!-- Notes Content -->
                            <div x-show="!isLoadingNotes">
                                <div x-show="notes.length > 0" class="space-y-3 max-h-48 overflow-y-auto pr-2">
                                    <template x-for="(note, index) in notes" :key="index">
                                        <div
                                            class="p-4 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                            <div class="flex justify-between items-start mb-2">
                                                <span class="text-sm font-medium text-gray-700"
                                                    x-text="note.added_by_type || 'System'"></span>
                                                <span class="text-xs text-gray-500"
                                                    x-text="formatNoteDate(note.added_at)"></span>
                                            </div>
                                            <p class="text-sm text-gray-800 leading-relaxed" x-text="note.content"></p>
                                        </div>
                                    </template>
                                </div>

                                <!-- Empty State -->
                                <div x-show="notes.length === 0 && !isLoadingNotes"
                                    class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg border border-dashed">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                    <p class="text-sm">No previous notes</p>
                                </div>
                            </div>
                        </div>

                        <!-- New Note Form -->
                        <div>
                            <label for="newNote" class="block text-sm font-medium text-gray-700 mb-3">Add New
                                Note</label>
                            <textarea id="newNote" x-model="newNote" @input="limitCharacters()" rows="4" maxlength="1000"
                                :class="{
                                    'border-gray-300 focus:border-blue-500': newNote.length < 900,
                                    'border-yellow-400 focus:border-yellow-500': newNote.length >= 900 && newNote
                                        .length < 1000,
                                    'border-red-400 focus:border-red-500': newNote.length >= 1000
                                }"
                                class="w-full px-4 py-3 border-2 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none"
                                placeholder="Enter your note here..."></textarea>
                            <div class="flex justify-between items-center mt-2">
                                <p class="text-sm"
                                    :class="{
                                        'text-gray-500': newNote.length < 900,
                                        'text-yellow-600': newNote.length >= 900 && newNote.length < 1000,
                                        'text-red-600': newNote.length >= 1000
                                    }">
                                    <span x-text="newNote.length"></span>/1000 characters
                                </p>
                                <div>
                                    <p x-show="newNote.length >= 900 && newNote.length < 1000"
                                        class="text-xs text-yellow-600 font-medium">
                                        <span x-text="1000 - newNote.length"></span> characters remaining
                                    </p>
                                    <p x-show="newNote.length >= 1000" class="text-xs text-red-600 font-medium">
                                        Character limit reached
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer border-t border-gray-200 p-6 px-8 flex justify-end gap-3">
                        <button type="button" @click="closeNoteModal()"
                            class="px-6 py-2.5 font-semibold text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
                            Cancel
                        </button>
                        <button type="button" @click="submitNote()" :disabled="!newNote.trim() || isSubmittingNote"
                            class="px-6 py-2.5 font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            x-text="isSubmittingNote ? 'Adding Note...' : 'Add Note'">
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            // Filter state for the sidebar
            Alpine.data('filterState', () => ({
                filters: {
                    date_start: '{{ request('date_start', '') }}',
                    date_end: '{{ request('date_end', '') }}',
                    booking_date: '{{ request('booking_date', '') }}',
                    search: '{{ request('search', '') }}',
                    branch_id: '{{ request('branch_id', '') }}',
                    service_category_id: '{{ request('service_category_id', '') }}',
                    service_name_id: '{{ request('service_name_id', '') }}',
                    payment_status: '{{ request('payment_status', '') }}',
                    booking_status: '{{ request('booking_status', '') }}',
                    booking_type: '{{ request('booking_type', 'all') }}',
                },
                dropdowns: {
                    categories: @json($categories),
                    services: @json($services),
                    seats: @json($seats),
                },

                clearFilters() {
                    this.filters = {
                        date_start: '',
                        date_end: '',
                        booking_date: '',
                        search: '',
                        branch_id: '',
                        service_category_id: '',
                        service_name_id: '',
                        payment_status: '',
                        booking_status: '',
                        booking_type: 'all',
                    };

                    // Reset dropdowns to initial state
                    this.dropdowns.categories = @json($categories);
                    this.dropdowns.services = @json($services);
                    this.dropdowns.seats = @json($seats);
                },

                async updateFilterOptions() {
                    try {
                        const queryParams = new URLSearchParams();
                        if (this.filters.branch_id) {
                            queryParams.append('branch_id', this.filters.branch_id);
                        }
                        if (this.filters.service_category_id) {
                            queryParams.append('service_category_id', this.filters
                                .service_category_id);
                        }

                        const response = await fetch(
                            `/owner/booking-lists/filter-options?${queryParams.toString()}`, {
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });

                        if (response.ok) {
                            const data = await response.json();
                            this.dropdowns.categories = data.categories;
                            this.dropdowns.services = data.services;
                            this.dropdowns.seats = data.seats;
                        }
                    } catch (error) {
                        console.error('Error updating filter options:', error);
                    }
                },

                applyFilters() {
                    const mainComponent = Alpine.$data(document.querySelector(
                        '[x-data="bookingListPage()"]'));
                    mainComponent.applyFilters(this.filters);
                    mainComponent.showFilters = false;
                    mainComponent.removeBodyClass(); // Call from main component
                }
            }));

            // Main component
            Alpine.data('bookingListPage', () => ({
                // Initial state
                bookings: @json($bookings->items() ?? []),
                pagination: @json($bookings->toArray()),
                stats: @json($stats),
                currentFilters: {
                    brn: '{{ request('brn', '') }}',
                    date_start: '{{ request('date_start', '') }}',
                    date_end: '{{ request('date_end', '') }}',
                    booking_date: '{{ request('booking_date', '') }}',
                    search: '{{ request('search', '') }}',
                    branch_id: '{{ request('branch_id', '') }}',
                    service_category_id: '{{ request('service_category_id', '') }}',
                    service_name_id: '{{ request('service_name_id', '') }}',
                    payment_status: '{{ request('payment_status', '') }}',
                    booking_status: '{{ request('booking_status', '') }}',
                    booking_type: '{{ request('booking_type', 'all') }}',
                },
                searchQuery: '{{ request('search', '') }}',
                showFilters: false,
                showConfirmModal: false,
                showNoShowModal: false,
                showNoteModal: false,
                selectedBooking: null,
                paginationLinks: [],
                isLoading: false,
                // Note modal properties
                notes: [],
                newNote: '',
                noteSuccessMessage: '',
                isSubmittingNote: false,
                isLoadingNotes: false,

                addBodyClass() {
                    document.body.classList.add('modal-open');
                },

                removeBodyClass() {
                    document.body.classList.remove('modal-open');
                },

                init() {
                    this.updatePaginationLinks();
                    this.updateActiveFilters();
                },

                // Computed properties
                get hasActiveFilters() {
                    return Object.values(this.currentFilters).some(value => 
                        value !== '' && value !== null && value !== undefined && value !== 'all'
                    );
                },

                get activeFilters() {
                    const filters = [];

                    if (this.currentFilters.brn) {
                        filters.push({
                            key: 'brn',
                            label: `BRN: ${this.currentFilters.brn}`
                        });
                    }

                    if (this.currentFilters.search) {
                        filters.push({
                            key: 'search',
                            label: `Search: ${this.currentFilters.search}`
                        });
                    }

                    if (this.currentFilters.booking_type && this.currentFilters.booking_type !==
                        'all') {
                        const typeLabel = this.currentFilters.booking_type === '1' ? 'Online' :
                            'Walk-in';
                        filters.push({
                            key: 'booking_type',
                            label: `Type: ${typeLabel}`
                        });
                    }

                    const branches = @json($branches);
                    const categories = @json($categories);
                    const services = @json($services);

                    if (this.currentFilters.branch_id) {
                        const branch = branches.find(b => b.id == this.currentFilters.branch_id);
                        filters.push({
                            key: 'branch_id',
                            label: `Branch: ${branch ? branch.branch_name : 'Unknown'}`
                        });
                    }

                    if (this.currentFilters.service_category_id) {
                        const category = categories.find(c => c.id == this.currentFilters
                            .service_category_id);
                        filters.push({
                            key: 'service_category_id',
                            label: `Category: ${category ? category.service_category : 'Unknown'}`
                        });
                    }

                    if (this.currentFilters.service_name_id) {
                        const service = services.find(s => s.id == this.currentFilters
                            .service_name_id);
                        filters.push({
                            key: 'service_name_id',
                            label: `Service: ${service ? service.service_name : 'Unknown'}`
                        });
                    }

                    if (this.currentFilters.payment_status) {
                        filters.push({
                            key: 'payment_status',
                            label: `${this.getPaymentStatusLabel(this.currentFilters.payment_status)}`
                        });
                    }

                    if (this.currentFilters.booking_status) {
                        filters.push({
                            key: 'booking_status',
                            label: `${this.getBookingStatusLabel(this.currentFilters.booking_status)}`
                        });
                    }

                    if (this.currentFilters.date_start || this.currentFilters.date_end || this
                        .currentFilters.booking_date) {
                        let dateLabel = '';
                        if (this.currentFilters.booking_date) {
                            dateLabel +=
                                `Booking Date: ${this.formatDisplayDate(this.currentFilters.booking_date)}`;
                        } else if (this.currentFilters.date_start && this.currentFilters.date_end) {
                            dateLabel +=
                                `${this.formatDisplayDate(this.currentFilters.date_start)} - ${this.formatDisplayDate(this.currentFilters.date_end)}`;
                        } else if (this.currentFilters.date_start) {
                            dateLabel +=
                                `From ${this.formatDisplayDate(this.currentFilters.date_start)}`;
                        } else if (this.currentFilters.date_end) {
                            dateLabel +=
                                `To ${this.formatDisplayDate(this.currentFilters.date_end)}`;
                        }
                        filters.push({
                            key: 'date_range',
                            label: dateLabel
                        });
                    }
                    return filters;
                },

                limitCharacters() {
                    if (this.newNote.length > 1000) {
                        this.newNote = this.newNote.substring(0, 1000);
                    }
                },

                async submitNote() {
                    if (!this.newNote.trim() || !this.selectedBooking || this.newNote.length > 1000)
                        return;

                    this.isSubmittingNote = true;
                    this.noteSuccessMessage = '';

                    try {
                        const trimmedNote = this.newNote.substring(0, 1000).trim();
                        const formData = new FormData();
                        formData.append('booking_id', this.selectedBooking.id);
                        formData.append('note', trimmedNote);

                        const response = await fetch(
                            '{{ route('sub_one.booking_lists.updateNote') }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: formData
                            });

                        const data = await response.json();

                        if (data.success) {
                            this.notes = data.notes || [];
                            this.newNote = '';

                            // Show success message
                            this.noteSuccessMessage = data.message || 'Note added successfully!';

                            // Auto-hide success message after 4 seconds
                            setTimeout(() => {
                                if (this.noteSuccessMessage === data.message) {
                                    this.noteSuccessMessage = '';
                                }
                            }, 4000);

                        } else {
                            throw new Error(data.error || 'Failed to add note');
                        }
                    } catch (error) {
                        console.error('Error adding note:', error);
                        this.noteSuccessMessage = 'Failed to add note: ' + error.message;

                        // Auto-hide error message after 5 seconds
                        setTimeout(() => {
                            this.noteSuccessMessage = '';
                        }, 5000);
                    } finally {
                        this.isSubmittingNote = false;
                    }
                },

                openNoteModal(booking) {
                    this.selectedBooking = booking;
                    this.showNoteModal = true;
                    this.newNote = '';
                    this.noteSuccessMessage = '';
                    this.isLoadingNotes = true;

                    // Lock body scroll
                    document.body.classList.add('modal-open');

                    this.loadNotes(booking.id).then(() => {
                        this.isLoadingNotes = false;
                    });
                },

                // Methods
                // Confirm Modal Methods
                openConfirmModal(booking) {
                    this.selectedBooking = booking;
                    this.showConfirmModal = true;
                    this.addBodyClass();
                },
                closeConfirmModal() {
                    this.showConfirmModal = false;
                    this.removeBodyClass();
                    setTimeout(() => this.selectedBooking = null, 300);
                },

                // No Show Modal Methods
                openNoShowModal(booking) {
                    this.selectedBooking = booking;
                    this.showNoShowModal = true;
                    this.addBodyClass();
                },
                closeNoShowModal() {
                    this.showNoShowModal = false;
                    this.removeBodyClass();
                    setTimeout(() => this.selectedBooking = null, 300);
                },

                // Note Modal Methods
                closeNoteModal() {
                    this.showNoteModal = false;
                    this.newNote = '';
                    this.noteSuccessMessage = '';

                    // Unlock body scroll
                    document.body.classList.remove('modal-open');

                    setTimeout(() => {
                        this.selectedBooking = null;
                    }, 300);
                },

                async loadNotes(bookingId) {
                    try {
                        this.isLoadingNotes = true;

                        // Use the correct route - this should now work
                        const response = await fetch(`/sub_one/booking_lists/notes/${bookingId}`, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            credentials: 'same-origin'
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }

                        const data = await response.json();

                        if (data.success) {
                            this.notes = Array.isArray(data.notes) ? data.notes : [];
                            console.log('Notes loaded successfully:', this.notes);
                        } else {
                            console.error('Failed to load notes:', data.error);
                            this.notes = [];
                        }
                    } catch (error) {
                        console.error('Error loading notes:', error);
                        this.notes = [];
                    } finally {
                        this.isLoadingNotes = false;
                    }
                },
                
                hasUnpaidPayLaterOrders(booking) {
    // Check if customer has checked out (checkin_status === 0)
    const isCheckedOut = booking.checkin_status === 0;
    
    // If not checked out, don't show the button
    if (!isCheckedOut) {
        return false;
    }
    
    // Check if main payment is paid (or not required)
    const isMainPaymentPaid = !booking.payment || booking.payment?.payment_status === 1;
    
    // Check if extension payment is paid (or not required)
    const isExtensionPaymentPaid = !booking.extended_time_used || 
                                   booking.extended_time_used === 0 || 
                                   booking.extension_payment?.payment_status === 1;
    
    // Check if there are unpaid pay-later orders
    const hasUnpaidOrders = (booking.unpaid_pay_later_orders_count || 0) > 0;
    
    // Show button only when:
    // 1. Customer has checked out (checkin_status === 0)
    // 2. Both main and extension payments are paid
    // 3. There are unpaid orders
    return isCheckedOut && isMainPaymentPaid && isExtensionPaymentPaid && hasUnpaidOrders;
},

                // Format duration with proper pluralization
                formatDuration(minutes) {
                    if (!minutes || minutes < 1) return '0 min';

                    const hours = Math.floor(minutes / 60);
                    const remainingMinutes = minutes % 60;

                    if (hours === 0) {
                        return `${remainingMinutes} min${remainingMinutes !== 1 ? 's' : ''}`;
                    } else if (remainingMinutes === 0) {
                        return `${hours} hr${hours !== 1 ? 's' : ''}`;
                    } else {
                        return `${hours} hr${hours !== 1 ? 's' : ''} : ${remainingMinutes} min${remainingMinutes !== 1 ? 's' : ''}`;
                    }
                },

                formatNoteDate(dateString) {
                    if (!dateString) return '';
                    const date = new Date(dateString);
                    return date.toLocaleString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: 'numeric',
                        minute: '2-digit'
                    });
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
                    this.removeBodyClass();

                    this.currentFilters = {
                        ...filters
                    };

                    try {
                        const queryParams = new URLSearchParams();
                        Object.entries(this.currentFilters).forEach(([key, value]) => {
                            if (value && value !== 'all') {
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
                            // Map the data to match your frontend structure
                            this.bookings = data.data.map(booking => ({
                                ...booking,
                                customer_account: booking.customer_account,
                                branch: booking.branch,
                                service_category: booking.service_category,
                                service_name: booking.service_name,
                                seat: booking.seat,
                                payment: booking.payment,
                                extension_payment: booking.extension_payment
                            }));
                            this.pagination = data.pagination;
                            this.stats = data.stats;
                            this.updatePaginationLinks();
                            this.updateActiveFilters();
                        } else {
                            throw new Error(data.message || 'Filter application failed');
                        }
                    } catch (error) {
                        console.error('Error applying filters:', error);
                        alert('Error applying filters: ' + error.message);
                    } finally {
                        this.isLoading = false;
                    }
                },

                async clearAllFilters() {
                    this.isLoading = true;
                    this.showFilters = false;
                    this.removeBodyClass();
                    this.searchQuery = '';

                    // Reset all filters to default values
                    this.currentFilters = {
                        brn: '',
                        date_start: '',
                        date_end: '',
                        booking_date: '',
                        search: '',
                        branch_id: '',
                        service_category_id: '',
                        service_name_id: '',
                        payment_status: '',
                        booking_status: '',
                        booking_type: 'all',
                    };

                    try {
                        const url = `${window.location.pathname}?ajax=true`;
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
                            this.bookings = data.data;
                            this.pagination = data.pagination;
                            this.stats = data.stats;
                            this.updatePaginationLinks();

                            // Clear URL parameters - this is the key fix
                            window.history.pushState({}, '', window.location.pathname);
                        } else {
                            throw new Error(data.message || 'Filter clearing failed');
                        }
                    } catch (error) {
                        console.error('Error clearing filters:', error);
                        alert('Error clearing filters: ' + error.message);
                    } finally {
                        this.isLoading = false;
                    }
                },

                removeFilter(filterKey) {
                    if (filterKey === 'date_range') {
                        this.currentFilters.date_start = '';
                        this.currentFilters.date_end = '';
                        this.currentFilters.booking_date = '';
                    } else if (filterKey === 'search') {
                        this.searchQuery = '';
                        this.currentFilters.search = '';
                    } else if (filterKey === 'brn') {
                        this.currentFilters.brn = '';
                    } else {
                        this.currentFilters[filterKey] = '';
                    }
                    this.applyFilters(this.currentFilters);
                },

                updateActiveFilters() {
                    const queryParams = new URLSearchParams();
                    Object.entries(this.currentFilters).forEach(([key, value]) => {
                        if (value && value !== 'all') {
                            queryParams.append(key, value);
                        }
                    });

                    const newUrl = `${window.location.pathname}?${queryParams.toString()}`;
                    window.history.pushState({}, '', newUrl);
                },

                async changePage(page) {
                    if (!page || page < 1 || (this.pagination && page > this.pagination.last_page))
                        return;

                    try {
                        this.isLoading = true;

                        const queryParams = new URLSearchParams();
                        Object.entries(this.currentFilters).forEach(([key, value]) => {
                            if (value && value !== 'all') {
                                queryParams.append(key, value);
                            }
                        });

                        if (!queryParams.has('booking_type') && this.currentFilters.booking_type !== 'all') {
                            queryParams.append('booking_type', this.currentFilters.booking_type);
                        }

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
                            this.bookings = data.data;
                            this.pagination = data.pagination;
                            this.stats = data.stats;
                            this.updatePaginationLinks();
                        } else {
                            throw new Error(data.message || 'Pagination failed');
                        }
                    } catch (error) {
                        console.error('Error changing page:', error);
                        alert('Error changing page: ' + error.message);
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

                // Format date for display
                formatDate(dateString) {
                    if (!dateString) return 'N/A';
                    const date = new Date(dateString);
                    return date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                },

                // Format time for display
                formatTime(dateString) {
                    if (!dateString) return 'N/A';

                    if (dateString.includes(' ') || dateString.includes('T')) {
                        const date = new Date(dateString);
                        return date.toLocaleTimeString('en-US', {
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true
                        });
                    }

                    if (typeof dateString === 'string' && dateString.includes(':')) {
                        const [hours, minutes] = dateString.split(':');
                        const hour = parseInt(hours, 10);
                        const minute = parseInt(minutes, 10);

                        if (isNaN(hour) || isNaN(minute)) return 'N/A';

                        const period = hour >= 12 ? 'PM' : 'AM';
                        let displayHour = hour % 12;
                        if (displayHour === 0) displayHour = 12;
                        return `${displayHour}:${String(minute).padStart(2, '0')} ${period}`;
                    }
                    return 'N/A';
                },

                formatDisplayDate(dateString) {
                    if (!dateString) return '';
                    const date = new Date(dateString);
                    return date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                },

                getPaymentStatusLabel(status) {
                    switch (status) {
                        case '1':
                            return 'Paid';
                        case '2':
                            return 'Unpaid';
                        case '0':
                            return 'Invalid';
                        default:
                            return 'Unknown';
                    }
                },

                getBookingStatusLabel(status) {
                    switch (status) {
                        case '0':
                            return 'Cancelled';
                        case '1':
                            return 'Booked';
                        case '2':
                            return 'Pending';
                        case '3':
                            return 'No-show';
                        case '4':
                            return 'Completed';
                        default:
                            return 'Unknown';
                    }
                },

                getPaymentStatusText(status) {
                    switch (status) {
                        case 1:
                            return 'Paid';
                        case 2:
                            return 'Unpaid';
                        case 0:
                            return 'Invalid';
                        default:
                            return 'N/A';
                    }
                },

                getBookingStatusText(status) {
                    switch (status) {
                        case 0:
                            return 'Cancelled';
                        case 1:
                            return 'Booked';
                        case 2:
                            return 'Pending';
                        case 3:
                            return 'No-show';
                        case 4:
                            return 'Completed';
                        default:
                            return 'Unknown';
                    }
                },

                getPaymentMethodText(method) {
                    switch (method) {
                        case 0:
                            return 'Cash';
                        case 1:
                            return 'GCash';
                        case 2:
                            return 'Debit';
                        case 3:
                            return 'Pay Later';
                        default:
                            return 'N/A';
                    }
                },

                shouldHidePageNumber(page) {
                    if (typeof page !== 'number') return false;
                    const current = this.pagination.current_page;
                    const last = this.pagination.last_page;
                    
                    // Always show first, last, current, and pages around current
                    if (page === 1 || page === last || page === current) return false;
                    
                    // Hide page numbers on mobile that are far from current
                    return Math.abs(page - current) > 1;
                }
            }));
        });
    </script>
@endsection