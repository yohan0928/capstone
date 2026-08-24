@extends('layouts.app')

@section('content')
    <!-- Header -->
    <h1 class="text-2xl font-bold text-gray-900 mt-4 mb-8 text-center">Customer Check-ins</h1>

    <div x-data="checkinData" x-init="init()" class="p-4">
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-4 gap-6 mb-8">
            <!-- Active Check-ins -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Check-ins</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.active_checkins"></p>
                    </div>
                    <div class="p-3 bg-green-50 rounded-lg">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Checked-out -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Checked-out</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.checked_out"></p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
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
                    <h2 class="text-lg font-semibold text-gray-900">Check-in Records</h2>

                    <!-- Right: Search + Filter -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-3 w-full lg:w-auto">
                        <!-- Search Input + Filter Button Row (responsive) -->
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
                            <button @click="showFilters = true"
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
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 z-30 bg-gray-50 shadow-right">
                                Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Booking Ref No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Service</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Seat
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Booking Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Check-in Date</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Time Used</th>
                            <!-- NEW COLUMNS ADDED HERE -->
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Extended Time</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total Time</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(checkin, index) in checkins" :key="checkin.id">
                            <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-100'">
                                <!-- 1. Customer -->
                                <td class="px-6 py-4 sticky left-0 z-20"
                                    :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-100'">
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
                                                x-text="(checkin.customer_account?.first_name || 'N/A') + ' ' + (checkin.customer_account?.last_name || '')">
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <!-- 2. Booking Ref No -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"
                                        x-text="checkin.booking?.booking_ref_no || 'N/A'"></div>
                                </td>
                                <!-- 3. Branch -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"
                                        x-text="checkin.branch?.branch_name || 'N/A'"></div>
                                </td>
                                <!-- 4. Service -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"
                                            x-text="checkin.service_name?.service_name || 'N/A'"></div>
                                        <div class="text-sm text-gray-500"
                                            x-text="checkin.service_category?.service_category || 'N/A'"></div>
                                    </div>
                                </td>
                                <!-- 5. Seat -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#4A2C1D]/10 text-[#7F5539]">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                        </svg>
                                        <span x-text="checkin.seat?.seat_no || 'N/A'"></span>
                                    </span>
                                </td>
                                <!-- 6. Booking Type -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="checkin.booking_type === 1 ?
                                            'bg-blue-100 text-blue-800' :
                                            'bg-green-100 text-green-800'"
                                        x-text="checkin.booking_type === 1 ? 'Online' : 'Walk-in'">
                                    </span>
                                </td>
                                <!-- 7. Check-in Date -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" x-text="formatDate(checkin.date_checked_in)"></div>
                                    <div class="text-sm text-gray-500" x-text="formatTime(checkin.date_checked_in)"></div>
                                </td>
                                <!-- 8. Time Used (Original) -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <span class="text-lg font-bold text-[#7F5539]"
                                            x-text="formatDuration(checkin.time_used) || '0 min'"></span>
                                    </div>
                                </td>
                                <!-- 9. Extended Time Used (NEW COLUMN) -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <template x-if="checkin.extended_time_used > 0">
                                            <span class="text-lg font-bold text-purple-600"
                                                x-text="formatDuration(checkin.extended_time_used)"></span>
                                        </template>
                                        <template x-if="!checkin.extended_time_used || checkin.extended_time_used === 0">
                                            <span class="text-sm text-gray-400">-</span>
                                        </template>
                                    </div>
                                </td>
                                <!-- 10. Total Time Used (NEW COLUMN) -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <span class="text-lg font-bold text-green-600"
                                            x-text="formatDuration(checkin.total_time_used) || formatDuration(checkin.time_used) || '0 min'"></span>
                                    </div>
                                </td>
                                <!-- 11. Status -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                                        :class="checkin.checkin_status ? 'bg-green-100 text-green-800' :
                                            'bg-gray-100 text-gray-800'">
                                        <span x-text="checkin.checkin_status ? 'Checked-in' : 'Checked-out'"></span>
                                    </span>
                                </td>
                                <!-- 12. Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex justify-start items-center gap-2 min-h-[40px]">
                                        <!-- Check-out / Checked-out Button -->
                                        <div class="flex-shrink-0 w-auto">
                                            <template x-if="!checkin.checkin_status">
                                                <button
                                                    class="w-full px-2 py-1.5 bg-gray-300 text-gray-600 rounded-lg text-sm font-medium cursor-not-allowed"
                                                    disabled>
                                                    Checked-out
                                                </button>
                                            </template>
                                        </div>

                                        <!-- Extend Time Button - Only for checked-in customers AND booking_type 1 (online) -->
                                        <div class="flex-shrink-0 w-auto"
                                            x-show="checkin.checkin_status && checkin.booking_type === 1">
                                            <button @click="showExtendTimeModal(checkin)"
                                                class="w-full px-2 py-1.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium flex items-center justify-center gap-1">
                                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span class="truncate">Extend Time</span>
                                            </button>
                                        </div>

                                        <!-- Add Order Button - Always visible for checked-in customers -->
                                        <div class="flex-shrink-0 w-auto"
                                            x-show="checkin.checkin_status && checkin.customer_account">
                                            <button @click="addOrder(checkin)"
                                                class="w-full px-2 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium flex items-center justify-center gap-1">
                                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                </svg>
                                                <span class="truncate">Add Order</span>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <tr x-show="!checkins.length">
                            <td colspan="12" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    <h5 class="text-sm font-medium text-gray-900">
                                        <span
                                            x-text="hasActiveFilters || scannedBrn ? 'No check-ins match your filters' : 'No customer check-ins found for today'"></span>
                                    </h5>
                                    <p class="text-sm text-gray-500">
                                        <span
                                            x-text="hasActiveFilters || scannedBrn ? 'Try adjusting your filters.' : 'When customers book services for today, their check-ins will appear here.'"></span>
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

        <!-- Extend Time Modal -->
        <div x-show="isExtendTimeModalOpen" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 py-6">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="isExtendTimeModalOpen = false"></div>

                <div
                    class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl sm:max-w-4xl w-full flex flex-col max-h-[90vh] sm:my-8">
                    <!-- Header -->
                    <div class="bg-blue-600 text-white p-6 flex-shrink-0">
                        <div class="flex justify-between items-center">
                            <div>
                                <h1 class="text-2xl font-bold">LinkudHub Time Extension</h1>
                                <p class="text-blue-100">Extend your booking duration</p>
                            </div>
                            <button type="button" @click="isExtendTimeModalOpen = false"
                                class="text-white hover:text-blue-200 font-medium">
                                Close
                            </button>
                        </div>

                        <template x-if="extendTimeData && extendTimeData.checkIn">
                            <div class="mt-4">
                                <h2 class="text-xl font-semibold"
                                    x-text="extendTimeData.checkIn.service_name?.service_name || 'N/A'"></h2>
                                <p class="text-blue-100" x-text="extendTimeData.checkIn.branch?.branch_name || 'N/A'"></p>
                            </div>
                        </template>
                    </div>

                    <div class="bg-white p-6 overflow-y-auto flex-grow">
                        <!-- Loading State -->
                        <div x-show="isExtendTimeModalOpen && !extendTimeData" class="text-center py-12">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                            <p class="text-lg font-medium text-gray-900">Loading extend time options</p>
                        </div>

                        <!-- Success State -->
                        <template x-if="extendTimeData && extendTimeData.checkIn">
                            <div class="space-y-6">
                                <!-- Current Booking Information -->
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Current Booking Information</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="space-y-3">
                                            <div class="flex justify-between">
                                                <span class="text-gray-600 font-medium">Branch:</span>
                                                <span class="font-medium"
                                                    x-text="extendTimeData.checkIn.branch?.branch_name || 'N/A'"></span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600 font-medium">Seat/Room:</span>
                                                <span class="font-medium"
                                                    x-text="extendTimeData.checkIn.seat?.seat_no || 'N/A'"></span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600 font-medium">Date Start:</span>
                                                <span class="font-medium"
                                                    x-text="formatDisplayDate(extendTimeData.current_date_start)"></span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600 font-medium">Date End:</span>
                                                <span class="font-medium"
                                                    x-text="formatDisplayDate(extendTimeData.current_date_end)"></span>
                                            </div>
                                        </div>
                                        <div class="space-y-3">
                                            <div class="flex justify-between">
                                                <span class="text-gray-600 font-medium">Start Time:</span>
                                                <span class="font-medium"
                                                    x-text="formatTimeForDisplay(extendTimeData.current_start_time)"></span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600 font-medium">End Time:</span>
                                                <span class="font-medium"
                                                    x-text="formatTimeForDisplay(extendTimeData.current_end_time)"></span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600 font-medium">Time Used:</span>
                                                <span class="font-medium text-green-600"
                                                    x-text="extendTimeData.time_used_formatted || '0 min'"></span>
                                            </div>
                                            <template x-if="extendTimeData.has_existing_extension">
                                                <div class="flex justify-between text-blue-600">
                                                    <span class="text-gray-600 font-medium">Extended Booking:</span>
                                                    <span class="font-medium">Yes</span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Time Extension Section -->
                                <div class="space-y-4">
                                    <h3 class="text-xl font-semibold text-gray-800 border-b pb-2">Extend Booking Time</h3>

                                    <!-- Time Duration Input - COMBINED ROW -->
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Additional
                                                Time</label>
                                            <div class="flex flex-col items-center">
                                                <div class="flex items-center justify-center space-x-4 mb-4">
                                                    <!-- Hours Section -->
                                                    <div class="text-center">
                                                        <label
                                                            class="block text-sm font-medium text-gray-700 mb-2">Hours</label>
                                                        <div class="flex items-center space-x-2">
                                                            <button type="button" @click="decrementHours()"
                                                                class="w-10 h-10 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                                                :disabled="extendHours <= 0">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2" d="M20 12H4" />
                                                                </svg>
                                                            </button>

                                                            <div class="relative">
                                                                <!-- Hours Input -->
                                                                <input type="number" x-model="extendHours"
                                                                    min="0" max="23" step="1"
                                                                    @input="updateDurationFromInput($event)"
                                                                    name="extendHours"
                                                                    class="w-20 py-3 px-4 text-center text-lg font-semibold border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                            </div>

                                                            <button type="button" @click="incrementHours()"
                                                                class="w-10 h-10 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2" d="M12 4v16m8-8H4" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div class="flex flex-col items-center">
                                                        <span class="text-2xl font-bold text-gray-400 mb-6">:</span>
                                                        <div class="h-6"></div> <!-- Spacer for alignment -->
                                                    </div>

                                                    <!-- Minutes Section -->
                                                    <div class="text-center">
                                                        <label
                                                            class="block text-sm font-medium text-gray-700 mb-2">Minutes</label>
                                                        <div class="flex items-center space-x-2">
                                                            <button type="button" @click="decrementMinutes()"
                                                                class="w-10 h-10 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                                                :disabled="extendMinutes <= 0">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2" d="M20 12H4" />
                                                                </svg>
                                                            </button>

                                                            <div class="relative">
                                                                <input type="number" x-model="extendMinutes"
                                                                    min="0" max="45" step="15"
                                                                    @input="updateDurationFromInput($event)"
                                                                    name="extendMinutes"
                                                                    class="w-20 py-3 px-4 text-center text-lg font-semibold border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                            </div>

                                                            <button type="button" @click="incrementMinutes()"
                                                                class="w-10 h-10 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2" d="M12 4v16m8-8H4" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Total Duration Display -->
                                                <div class="text-center">
                                                    <div class="inline-block bg-blue-50 px-4 py-2 rounded-lg">
                                                        <span class="text-sm text-gray-600">Total Additional Time:</span>
                                                        <span class="ml-2 text-lg font-bold text-green-600"
                                                            x-text="formatDuration(calculateTotalDuration())"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Quick Duration Buttons -->
                                        <div class="mt-6">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Quick Add</label>
                                            <div class="flex flex-wrap gap-2 justify-center">
                                                <template x-for="quickDuration in quickDurations"
                                                    :key="quickDuration.label">
                                                    <button type="button"
                                                        @click="addQuickDuration(quickDuration.minutes)"
                                                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors">
                                                        <span x-text="quickDuration.label"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Extended Time Summary -->
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 space-y-3">
                                        <h4 class="text-lg font-semibold text-blue-800">Extended Time Summary</h4>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="space-y-2">
                                                <div class="flex justify-between">
                                                    <span class="text-gray-700 font-medium">Extended From:</span>
                                                    <span class="font-medium text-blue-600"
                                                        x-text="formatTimeForDisplay(extendTimeData.current_end_time)"></span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-gray-700 font-medium">Extended To:</span>
                                                    <span class="font-medium text-blue-600"
                                                        x-text="calculateExtendedEndTime()"></span>
                                                </div>
                                            </div>
                                            <div class="space-y-2">
                                                <div class="flex justify-between">
                                                    <span class="text-gray-700 font-medium">Date:</span>
                                                    <span class="font-medium text-blue-600"
                                                        x-text="formatDisplayDate(extendTimeData.current_date_end)"></span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-gray-700 font-medium">Extended Date:</span>
                                                    <span class="font-medium text-blue-600"
                                                        x-text="calculateExtendedDate()"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="pt-3 border-t border-blue-200">
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <div class="flex justify-between items-center mb-2">
                                                        <span class="text-gray-700 font-medium">Original Time:</span>
                                                        <span class="font-medium text-gray-600"
                                                            x-text="extendTimeData.time_used_formatted"></span>
                                                    </div>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-gray-700 font-medium">Additional Time:</span>
                                                        <span class="text-lg font-bold text-green-600"
                                                            x-text="formatDuration(calculateTotalDuration())"></span>
                                                    </div>
                                                </div>
                                                <div class="border-l border-blue-200 pl-4">
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-gray-700 font-medium">Total Time:</span>
                                                        <span class="text-lg font-bold text-green-700"
                                                            x-text="formatDuration(extendTimeData.time_used + calculateTotalDuration())"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Maximum Duration Warning and Adjust Button -->
                                    <div x-show="isDurationExceedingMax"
                                        class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.698-.833-2.464 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                                </svg>
                                                <div>
                                                    <p class="text-sm font-medium text-yellow-800">
                                                        Duration exceeds available time until closing (<span
                                                            x-text="formatTimeForDisplay(extendTimeData.branch_close_time_raw)"></span>)
                                                    </p>
                                                    <p class="text-xs text-yellow-600 mt-1">
                                                        Maximum available extension: <span
                                                            x-text="formatDuration(maxAvailableDuration)"></span>
                                                    </p>
                                                </div>
                                            </div>
                                            <button type="button" @click="adjustToMaxDuration"
                                                class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg text-sm font-medium transition-colors">
                                                Set to Maximum
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Existing Extension Warning and Adjust Button -->
                                    <div x-show="hasExistingExtension && isDurationExceedingMax"
                                        class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <svg class="w-5 h-5 text-purple-600 mr-2" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <div>
                                                    <p class="text-sm font-medium text-purple-800">
                                                        You have an existing time extension
                                                    </p>
                                                    <p class="text-xs text-purple-600 mt-1">
                                                        Total extended time will be combined with previous extension
                                                    </p>
                                                </div>
                                            </div>
                                            <button type="button" @click="adjustToMaxDuration"
                                                class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition-colors">
                                                Set to Maximum
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Modal Footer -->
                    <template x-if="extendTimeData && extendTimeData.checkIn">
                        <div class="bg-white p-6 border-t border-gray-200 flex-shrink-0 flex justify-end space-x-4">
                            <button @click="isExtendTimeModalOpen = false"
                                class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-300">
                                Cancel
                            </button>
                            <button @click="processTimeExtension()"
                                :disabled="(extendHours === 0 && extendMinutes === 0) || isDurationExceedingMax || isExtendingTime"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition duration-300 flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fas fa-clock mr-2"></i>
                                <span x-text="isExtendingTime ? 'Extending...' : 'Extend Time'"></span>
                            </button>
                        </div>
                    </template>
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
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Filter Search</h3>

                        <div x-data="filterState">
                            <!-- Filter Inputs -->
                            <div class="space-y-4">
                                <!-- Date Start -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Start</label>
                                    <input type="date" x-model="filters.date_start"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                </div>
                                <!-- Date End -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date End</label>
                                    <input type="date" x-model="filters.date_end"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                </div>
                                <!-- Check-in Status -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Check-in Status</label>
                                    <select x-model="filters.checkin_status"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                        <option value="">All Check-in Status</option>
                                        <option value="0">Checked-out</option>
                                        <option value="1">Checked-in</option>
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

    <style>
        /* Add this new style to prevent body scroll */
        body.modal-open {
            overflow: hidden;
        }

        /* STYLES COPIED FROM BOOKING FORM */
        .scroll-column {
            max-height: 200px;
            overflow-y: auto;
            scroll-behavior: smooth;
        }

        .option-item {
            padding: 8px 12px;
            font-size: 0.875rem;
            cursor: pointer;
            border-bottom: 1px solid #f1f1f1;
            transition: background-color 0.2s;
            position: relative;
            scroll-margin: 10px;
        }

        .option-item:hover {
            background-color: #f8fafc;
        }

        .option-item.selected {
            background-color: #3b82f6;
            color: white;
        }

        .option-item.today-highlight {
            background-color: #fffbeb;
            border-left: 3px solid #f59e0b;
            font-weight: 600;
        }

        .option-item.today-highlight::before {
            content: "Today";
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.7rem;
            color: #f59e0b;
            font-weight: 500;
        }

        .option-item.selected.today-highlight {
            background-color: #3b82f6;
            color: white;
            border-left: 3px solid #1d4ed8;
        }

        .option-item.selected.today-highlight::before {
            color: white;
        }

        .scroll-column::-webkit-scrollbar {
            width: 8px;
        }

        .scroll-column::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .scroll-column::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .scroll-column::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        html {
            scroll-behavior: smooth;
        }

        .now-indicator {
            display: inline-block;
            background: #10b981;
            color: white;
            font-size: 0.6rem;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 8px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .option-item.current-time {
            background-color: #ecfdf5;
            border-left: 3px solid #10b981;
            font-weight: 600;
        }

        .option-item.selected.current-time {
            background-color: #3b82f6;
            color: white;
            border-left: 3px solid #1d4ed8;
        }

        .option-item.selected.current-time .now-indicator {
            background: #1d4ed8;
            color: white;
        }

        .option-item.past-time {
            background-color: #f3f4f6;
            color: #9ca3af;
            cursor: not-allowed !important;
        }

        .option-item.past-time:hover {
            background-color: #f3f4f6 !important;
        }

        .time-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding: 8px 12px;
            background: #f8fafc;
            border-radius: 6px;
            font-size: 0.875rem;
        }

        .current-time-info {
            color: #059669;
            font-weight: 500;
        }

        .current-time-info i {
            margin-right: 4px;
        }

        .option-item.unavailable-time {
            background-color: #fef2f2;
            color: #dc2626;
            cursor: not-allowed !important;
        }

        .option-item.unavailable-time:hover {
            background-color: #fef2f2 !important;
        }

        .option-item.unavailable-time::before {
            content: "⨯";
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.7rem;
            color: #dc2626;
            font-weight: bold;
        }

        .option-item.available-time {
            background-color: #f0f9ff;
            border-left: 2px solid #0ea5e9;
            cursor: pointer;
        }

        .option-item.available-time:hover {
            background-color: #e0f2fe;
        }

        .option-item.selected.available-time {
            background-color: #3b82f6;
            color: white;
            border-left: 2px solid #1d4ed8;
        }

        .option-item.past-time {
            background-color: #f8fafc;
            color: #9ca3af;
            cursor: not-allowed;
        }

        .option-item.past-time::before {
            content: "⌛";
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.7rem;
            color: #9ca3af;
        }

        .option-item.unavailable-time {
            background-color: #fef2f2;
            color: #dc2626;
            cursor: not-allowed;
        }

        .option-item.unavailable-time::before {
            content: "⨯";
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.7rem;
            color: #dc2626;
            font-weight: bold;
        }

        /* END OF COPIED STYLES */


        /* Original Extend Time Modal Styles (kept for safety, can be pruned) */
        .extend-time-option {
            padding: 10px 12px;
            font-size: 0.875rem;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s ease;
            position: relative;
        }

        .extend-time-option:hover:not(.past-time) {
            background-color: #f8fafc;
            transform: translateX(2px);
        }

        .extend-time-option.selected {
            background-color: #3b82f6;
            color: white;
            border-left: 3px solid #1d4ed8;
        }

        .extend-time-option.past-time {
            background-color: #f8fafc;
            color: #9ca3af;
            cursor: not-allowed;
        }

        .extend-time-option.available-time {
            background-color: #f0f9ff;
            border-left: 2px solid #0ea5e9;
        }

        .extend-time-option.selected.available-time {
            background-color: #3b82f6;
            color: white;
            border-left: 2px solid #1d4ed8;
        }

        .option-item.unavailable-time {
            background-color: #fef2f2;
            color: #dc2626;
            cursor: not-allowed !important;
        }

        .option-item.unavailable-time:hover {
            background-color: #fef2f2 !important;
        }

        .option-item.unavailable-time::before {
            content: "⨯";
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.7rem;
            color: #dc2626;
            font-weight: bold;
        }
    </style>

    <script>
        document.addEventListener('alpine:init', () => {
            // Filter state for the sidebar
            Alpine.data('filterState', () => ({
                filters: {
                    date_start: '{{ request('date_start', '') }}',
                    date_end: '{{ request('date_end', '') }}',
                    checkin_status: '{{ request('checkin_status', '') }}',
                },
                clearFilters() {
                    this.filters = {
                        date_start: '',
                        date_end: '',
                        checkin_status: '',
                    };
                },
                applyFilters() {
                    const mainComponent = Alpine.$data(document.querySelector(
                        '[x-data="checkinData"]'));
                    const newFilters = {
                        ...mainComponent.currentFilters,
                        ...this.filters,
                        search: mainComponent.searchQuery
                    };
                    mainComponent.applyFilters(newFilters);
                    this.$nextTick(() => {
                        mainComponent.showFilters = false;
                    });
                }
            }));

            // Main component
            Alpine.data('checkinData', () => ({
                // Initial state
                checkins: @json($customerCheckins->items() ?? []),
                pagination: @json($customerCheckins->toArray()),
                stats: @json($stats),
                scannedBrn: '{{ $scannedBrn ?? '' }}',
                currentFilters: {
                    date_start: '{{ request('date_start', '') }}',
                    date_end: '{{ request('date_end', '') }}',
                    checkin_status: '{{ request('checkin_status', '') }}',
                    search: '{{ request('search', '') }}',
                    brn: '{{ request('brn', '') }}',
                },
                searchQuery: '{{ request('search', '') }}',
                showFilters: false,
                isExtendTimeModalOpen: false,
                paginationLinks: [],
                isLoading: false,

                // Extend Time Modal State
                extendTimeData: null,
                currentExtendCheckinId: null,
                extendHours: 0,
                extendMinutes: 0,
                isExtendingTime: false,
                // Quick duration options
                quickDurations: [{
                        label: '+15 mins',
                        minutes: 15
                    },
                    {
                        label: '+30 mins',
                        minutes: 30
                    },
                    {
                        label: '+1 hour',
                        minutes: 60
                    },
                    {
                        label: '+2 hours',
                        minutes: 120
                    },
                    {
                        label: '+3 hours',
                        minutes: 180
                    }
                ],
                maxAvailableDuration: 0,
                isDurationExceedingMax: false,
                hasExistingExtension: false,

                init() {
                    this.updatePaginationLinks();
                    this.updateActiveFilters();

                    this.$watch('isExtendTimeModalOpen', (value) => {
                        value ? this.addBodyClass() : this.removeBodyClass();
                    });

                    this.$watch('showFilters', (value) => {
                        value ? this.addBodyClass() : this.removeBodyClass();
                    });
                },

                // Initialize duration inputs
                initDurationInputs() {
                    console.log('Initializing duration inputs');
                    this.extendHours = 0;
                    this.extendMinutes = 0;
                    this.calculateMaxAvailableDuration();
                    this.checkIfDurationExceedsMax();
                },

                // Calculate maximum available duration until closing time
                calculateMaxAvailableDuration() {
                    if (!this.extendTimeData || !this.extendTimeData.branch_close_time_raw) {
                        this.maxAvailableDuration = 8 * 60; // Default 8 hours if no closing time
                        return;
                    }

                    try {
                        const currentEndTime = this.extendTimeData.current_end_time;
                        const branchCloseTime = this.extendTimeData.branch_close_time_raw;

                        // Parse times
                        const [currentHour, currentMinute] = currentEndTime.split(':').map(Number);
                        const [closeHour, closeMinute] = branchCloseTime.split(':').map(Number);

                        // Calculate minutes from midnight
                        const currentMinutes = currentHour * 60 + currentMinute;
                        const closeMinutes = closeHour * 60 + closeMinute;

                        // Handle overnight schedule
                        let maxDuration;
                        if (closeMinutes < currentMinutes) {
                            // Overnight - closing time is next day
                            maxDuration = (24 * 60 - currentMinutes) + closeMinutes;
                        } else {
                            // Same day
                            maxDuration = closeMinutes - currentMinutes;
                        }

                        // Ensure minimum 15 minutes increment
                        this.maxAvailableDuration = Math.floor(maxDuration / 15) * 15;

                        // If calculated duration is less than 15 minutes, set to 0
                        if (this.maxAvailableDuration < 15) {
                            this.maxAvailableDuration = 0;
                        }

                        console.log('Max available duration calculated:', this.maxAvailableDuration,
                            'minutes');

                    } catch (error) {
                        console.error('Error calculating max duration:', error);
                        this.maxAvailableDuration = 8 * 60; // Default fallback
                    }
                },

                // Check if current duration exceeds maximum
                checkIfDurationExceedsMax() {
                    const totalMinutes = (this.extendHours * 60) + this.extendMinutes;
                    const isExceeding = totalMinutes > this.maxAvailableDuration;
                    console.log('Checking if duration exceeds max:', {
                        totalMinutes,
                        maxAvailableDuration: this.maxAvailableDuration,
                        isExceeding
                    });
                    this.isDurationExceedingMax = isExceeding;
                },

                // Adjust to maximum available duration
                adjustToMaxDuration() {
                    console.log('Adjusting to max duration:', this.maxAvailableDuration);
                    if (this.maxAvailableDuration > 0) {
                        const hours = Math.floor(this.maxAvailableDuration / 60);
                        const minutes = this.maxAvailableDuration % 60;

                        this.extendHours = hours;
                        this.extendMinutes = minutes;
                        console.log('Set extendHours to:', hours, 'extendMinutes to:', minutes);
                        this.checkIfDurationExceedsMax();
                    }
                },

                // Add quick duration
                addQuickDuration(additionalMinutes) {
                    console.log('Adding quick duration:', additionalMinutes);
                    const currentTotal = (this.extendHours * 60) + this.extendMinutes;
                    const newTotal = currentTotal + additionalMinutes;

                    console.log('Current total:', currentTotal, 'New total:', newTotal);

                    // Check if new total exceeds maximum
                    if (newTotal > this.maxAvailableDuration) {
                        console.log('New total exceeds max, adjusting to max');
                        this.adjustToMaxDuration();
                        return;
                    }

                    this.extendHours = Math.floor(newTotal / 60);
                    this.extendMinutes = newTotal % 60;
                    console.log('Set extendHours to:', this.extendHours, 'extendMinutes to:', this
                        .extendMinutes);
                    this.checkIfDurationExceedsMax();
                },

                // Calculate extended end time
                calculateExtendedEndTime() {
                    console.log('Calculating extended end time:', {
                        extendHours: this.extendHours,
                        extendMinutes: this.extendMinutes,
                        currentEndTime: this.extendTimeData?.current_end_time
                    });

                    if ((this.extendHours === 0 && this.extendMinutes === 0) || !this.extendTimeData) {
                        const result = this.formatTimeForDisplay(this.extendTimeData
                            ?.current_end_time || '00:00');
                        console.log('No extension, returning current time:', result);
                        return result;
                    }

                    try {
                        const currentEndTime = this.extendTimeData.current_end_time;
                        const [currentHour, currentMinute] = currentEndTime.split(':').map(Number);

                        let totalMinutes = (currentHour * 60) + currentMinute;
                        const additionalMinutes = (this.extendHours * 60) + this.extendMinutes;

                        totalMinutes += additionalMinutes;

                        // Handle overflow to next day
                        let newHour = Math.floor(totalMinutes / 60) % 24;
                        const newMinute = totalMinutes % 60;

                        console.log('New hour/minute:', newHour, newMinute, 'totalMinutes:',
                            totalMinutes);

                        // Format to 12-hour time
                        let period = 'AM';
                        if (newHour >= 12) {
                            period = newHour >= 24 ? 'AM' : 'PM';
                        }

                        const displayHour = newHour % 12;
                        const finalHour = displayHour === 0 ? 12 : displayHour;

                        const result =
                            `${finalHour}:${newMinute.toString().padStart(2, '0')} ${period}`;
                        console.log('Formatted result:', result);
                        return result;

                    } catch (error) {
                        console.error('Error calculating extended end time:', error);
                        return 'Invalid Time';
                    }
                },

                // Calculate extended date
                calculateExtendedDate() {
                    console.log('Calculating extended date:', {
                        extendHours: this.extendHours,
                        extendMinutes: this.extendMinutes,
                        currentDateEnd: this.extendTimeData?.current_date_end
                    });

                    if ((this.extendHours === 0 && this.extendMinutes === 0) || !this.extendTimeData) {
                        const result = this.formatDisplayDate(this.extendTimeData?.current_date_end ||
                            new Date().toISOString().split('T')[0]);
                        console.log('No extension, returning current date:', result);
                        return result;
                    }

                    try {
                        const currentEndTime = this.extendTimeData.current_end_time;
                        const currentDate = this.extendTimeData.current_date_end;

                        const [currentHour, currentMinute] = currentEndTime.split(':').map(Number);
                        const totalMinutes = (currentHour * 60) + currentMinute;
                        const additionalMinutes = (this.extendHours * 60) + this.extendMinutes;

                        const newTotalMinutes = totalMinutes + additionalMinutes;

                        // Calculate days to add
                        const daysToAdd = Math.floor(newTotalMinutes / (24 * 60));

                        if (daysToAdd > 0) {
                            const newDate = new Date(currentDate);
                            newDate.setDate(newDate.getDate() + daysToAdd);
                            const result = this.formatDisplayDate(newDate.toISOString().split('T')[0]);
                            console.log('Days added:', daysToAdd, 'Result date:', result);
                            return result;
                        }

                        const result = this.formatDisplayDate(currentDate);
                        console.log('No days added, same date:', result);
                        return result;

                    } catch (error) {
                        console.error('Error calculating extended date:', error);
                        return this.formatDisplayDate(this.extendTimeData?.current_date_end ||
                            new Date().toISOString().split('T')[0]);
                    }
                },

                // Calculate total duration in minutes
                calculateTotalDuration() {
                    const total = (this.extendHours * 60) + this.extendMinutes;
                    console.log('Calculating total duration:', {
                        extendHours: this.extendHours,
                        extendMinutes: this.extendMinutes,
                        total: total
                    });
                    return total;
                },

                // Increment/Decrement functions - FIXED
                incrementHours() {
                    console.log('Incrementing hours, current:', this.extendHours);
                    if (this.extendHours < 23) {
                        this.extendHours++;
                        console.log('Hours incremented to:', this.extendHours);
                        this.checkIfDurationExceedsMax();
                    }
                },

                decrementHours() {
                    console.log('Decrementing hours, current:', this.extendHours);
                    if (this.extendHours > 0) {
                        this.extendHours--;
                        console.log('Hours decremented to:', this.extendHours);
                        this.checkIfDurationExceedsMax();
                    }
                },

                incrementMinutes() {
                    console.log('Incrementing minutes, current:', this.extendMinutes);
                    if (this.extendMinutes < 45) {
                        this.extendMinutes += 15;
                    } else if (this.extendMinutes === 45) {
                        this.extendMinutes = 0;
                        this.extendHours = Math.min(this.extendHours + 1, 23);
                    }
                    console.log('Minutes updated to:', this.extendMinutes, 'Hours:', this.extendHours);
                    this.checkIfDurationExceedsMax();
                },

                decrementMinutes() {
                    console.log('Decrementing minutes, current:', this.extendMinutes);
                    if (this.extendMinutes > 0) {
                        this.extendMinutes -= 15;
                    } else if (this.extendMinutes === 0 && this.extendHours > 0) {
                        this.extendMinutes = 45;
                        this.extendHours--;
                    }
                    console.log('Minutes updated to:', this.extendMinutes, 'Hours:', this.extendHours);
                    this.checkIfDurationExceedsMax();
                },

                // Update duration from input
                updateDurationFromInput(event) {
                    console.log('Input event:', event);
                    console.log('Current values:', {
                        extendHours: this.extendHours,
                        extendMinutes: this.extendMinutes
                    });

                    // Get the target element
                    const target = event.target;
                    const fieldName = target.name || 'unknown';
                    const value = parseInt(target.value) || 0;

                    console.log('Field:', fieldName, 'Value:', value);

                    if (fieldName.includes('hour') || fieldName === 'extendHours') {
                        // Ensure hours are between 0-23
                        this.extendHours = Math.max(0, Math.min(23, value));
                    } else if (fieldName.includes('minute') || fieldName === 'extendMinutes') {
                        // Ensure minutes are valid (0, 15, 30, 45)
                        let minutes = Math.max(0, Math.min(45, value));
                        // Round to nearest 15
                        this.extendMinutes = Math.round(minutes / 15) * 15;
                    }

                    console.log('After update:', {
                        extendHours: this.extendHours,
                        extendMinutes: this.extendMinutes
                    });

                    this.checkIfDurationExceedsMax();
                },

                // Clear BRN filter
                async clearBrnFilter() {
                    this.scannedBrn = '';
                    this.currentFilters.brn = '';

                    try {
                        const clearUrl =
                            `{{ route('sub_two.customer_checkins.index') }}?clear_brn=true&ajax=true`;
                        const response = await fetch(clearUrl, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (response.ok) {
                            const data = await response.json();
                        }
                    } catch (error) {
                        showAppToast('Error clearing BRN filter.', 'error');
                    }

                    await this.applyFilters(this.currentFilters);
                },

                // Computed properties
                get hasActiveFilters() {
                    return Object.values(this.currentFilters).some(value => value !== '') || this
                        .scannedBrn;
                },

                get activeFilters() {
                    const filters = [];

                    if (this.scannedBrn && this.scannedBrn.trim() !== '') {
                        filters.push({
                            key: 'brn',
                            label: `BRN: ${this.scannedBrn}`
                        });
                    }

                    if (this.currentFilters.search) {
                        filters.push({
                            key: 'search',
                            label: `Search: ${this.currentFilters.search}`
                        });
                    }

                    if (this.currentFilters.checkin_status) {
                        filters.push({
                            key: 'checkin_status',
                            label: `${this.getStatusLabel(this.currentFilters.checkin_status)}`
                        });
                    }
                    if (this.currentFilters.date_start || this.currentFilters.date_end) {
                        let dateLabel = '';
                        if (this.currentFilters.date_start && this.currentFilters.date_end) {
                            dateLabel +=
                                `${this.formatDisplayDate(this.currentFilters.date_start)} - ${this.formatDisplayDate(this.currentFilters.date_end)}`;
                        } else if (this.currentFilters.date_start) {
                            dateLabel +=
                                `From ${this.formatDisplayDate(this.currentFilters.date_start)}`;
                        } else {
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

                // Format date for display
                formatDate(dateString) {
                    if (!dateString) return 'N/A';
                    try {
                        const date = new Date(dateString);
                        return date.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                        });
                    } catch (error) {
                        return 'Invalid Date';
                    }
                },

                // Format time for display
                formatTime(dateString) {
                    if (!dateString) return 'N/A';
                    try {
                        const date = new Date(dateString);
                        return date.toLocaleTimeString('en-US', {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    } catch (error) {
                        return 'Invalid Time';
                    }
                },

                formatDisplayDate(dateString) {
                    if (!dateString) return '';
                    try {
                        const date = new Date(dateString);
                        return date.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                        });
                    } catch (error) {
                        return 'Invalid Date';
                    }
                },

                // Improved time formatting function
                formatTimeForDisplay(timeInput) {
                    if (!timeInput) {
                        return '12:00 AM';
                    }

                    try {
                        // Handle Date objects
                        if (timeInput instanceof Date) {
                            const hours = timeInput.getHours();
                            const minutes = timeInput.getMinutes();

                            // Proper 12-hour conversion
                            let displayHour, period;

                            if (hours === 0) {
                                displayHour = 12;
                                period = 'AM';
                            } else if (hours === 12) {
                                displayHour = 12;
                                period = 'PM';
                            } else if (hours > 12) {
                                displayHour = hours - 12;
                                period = 'PM';
                            } else {
                                displayHour = hours;
                                period = 'AM';
                            }

                            const displayMinute = minutes.toString().padStart(2, '0');
                            return `${displayHour}:${displayMinute} ${period}`;
                        }

                        // Handle string time formats
                        if (typeof timeInput === 'string') {
                            // Remove any date part if present
                            let timePart = timeInput;
                            if (timeInput.includes(' ')) {
                                const parts = timeInput.split(' ');
                                timePart = parts[1] || parts[0];
                            }

                            if (timeInput.includes('T')) {
                                // ISO format like "2023-12-03T21:15:00"
                                const date = new Date(timeInput);
                                return this.formatTimeForDisplay(date);
                            }

                            // Handle different time formats
                            let hours, minutes;
                            if (timePart.includes(':')) {
                                const parts = timePart.split(':');
                                hours = parseInt(parts[0], 10);
                                minutes = parseInt(parts[1], 10);
                            } else if (timePart.length === 4) {
                                // Handle "HHmm" format
                                hours = parseInt(timePart.substring(0, 2), 10);
                                minutes = parseInt(timePart.substring(2, 4), 10);
                            } else {
                                return 'Invalid Time';
                            }

                            // Validate hours and minutes
                            if (isNaN(hours) || isNaN(minutes) || hours < 0 || hours > 23 || minutes <
                                0 || minutes > 59) {
                                return 'Invalid Time';
                            }

                            // Proper 12-hour conversion
                            let displayHour, period;

                            if (hours === 0) {
                                displayHour = 12;
                                period = 'AM';
                            } else if (hours === 12) {
                                displayHour = 12;
                                period = 'PM';
                            } else if (hours > 12) {
                                displayHour = hours - 12;
                                period = 'PM';
                            } else {
                                displayHour = hours;
                                period = 'AM';
                            }

                            const displayMinute = minutes.toString().padStart(2, '0');

                            return `${displayHour}:${displayMinute} ${period}`;
                        }

                        return 'Invalid Time';
                    } catch (error) {
                        return 'Invalid Time';
                    }
                },

                getStatusLabel(status) {
                    switch (status) {
                        case '1':
                            return 'Checked-in';
                        case '0':
                            return 'Checked-out';
                        default:
                            return 'Unknown';
                    }
                },

                formatDuration(minutes) {
                    const totalMinutes = parseInt(minutes, 10) || 0;

                    if (totalMinutes < 1) return '0 min';

                    const hours = Math.floor(totalMinutes / 60);
                    const remainingMinutes = totalMinutes % 60;

                    const hourText = `${hours} hr${hours !== 1 ? 's' : ''}`;
                    const minuteText = `${remainingMinutes} min${remainingMinutes !== 1 ? 's' : ''}`;

                    if (hours > 0 && remainingMinutes > 0) {
                        return `${hourText} : ${minuteText}`;
                    } else if (hours > 0) {
                        return hourText;
                    } else {
                        return minuteText;
                    }
                },

                // Extend Time functionality
                async showExtendTimeModal(checkin) {
                    let checkinId;
                    try {
                        checkinId = checkin?.id;

                        if (!checkinId) {
                            showAppToast('Unable to load check-in details.', 'error');
                            return;
                        }

                        console.log('Opening extend time modal for checkin ID:', checkinId);

                        // Reset state
                        this.extendTimeData = null;
                        this.currentExtendCheckinId = checkinId;
                        this.isExtendingTime = false;

                        // Initialize duration state
                        this.extendHours = 0;
                        this.extendMinutes = 0;
                        this.maxAvailableDuration = 0;
                        this.isDurationExceedingMax = false;
                        this.hasExistingExtension = false;

                        // Show modal
                        this.isExtendTimeModalOpen = true;

                        const url = `/sub_two/customer_checkins/extend-time-modal/${checkinId}`;
                        console.log('Fetching from URL:', url);

                        const response = await fetch(url, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        console.log('Response status:', response.status);

                        if (!response.ok) {
                            const errorText = await response.text();
                            console.error('Response error:', errorText);
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }

                        const data = await response.json();
                        console.log('Response data:', data);

                        if (data.success && data.checkIn) {
                            this.extendTimeData = data;
                            this.hasExistingExtension = data.has_existing_extension || false;

                            // Initialize duration inputs
                            this.initDurationInputs();

                        } else {
                            throw new Error(data.message || 'Failed to load extend time options');
                        }
                    } catch (error) {
                        console.error('Error in showExtendTimeModal:', error);
                        showAppToast('Failed to load extend time options: ' + error.message,
                            'error');
                        this.isExtendTimeModalOpen = false;
                    }
                },

                // Process time extension
                async processTimeExtension() {
                    console.log('Processing time extension, current values:', {
                        extendHours: this.extendHours,
                        extendMinutes: this.extendMinutes,
                        isDurationExceedingMax: this.isDurationExceedingMax
                    });

                    if ((this.extendHours === 0 && this.extendMinutes === 0) || this
                        .isDurationExceedingMax) {
                        showAppToast('Please select a valid duration', 'error');
                        return;
                    }

                    this.isExtendingTime = true;

                    try {
                        const additionalDuration = this.calculateTotalDuration();

                        // Calculate extended end time
                        const currentEndTime = this.extendTimeData.current_end_time;
                        const currentDate = this.extendTimeData.current_date_end;
                        const [currentHour, currentMinute] = currentEndTime.split(':').map(Number);

                        const totalMinutes = (currentHour * 60) + currentMinute +
                            additionalDuration;

                        // Calculate new time and date
                        let daysToAdd = Math.floor(totalMinutes / (24 * 60));
                        let newHour = Math.floor(totalMinutes % (24 * 60) / 60);
                        let newMinute = totalMinutes % 60;

                        const extendedEndTime =
                            `${newHour.toString().padStart(2, '0')}:${newMinute.toString().padStart(2, '0')}`;
                        const extendedDateEnd = new Date(currentDate);
                        extendedDateEnd.setDate(extendedDateEnd.getDate() + daysToAdd);
                        const formattedExtendedDateEnd = extendedDateEnd.toISOString().split('T')[
                            0];

                        console.log('Processing time extension:', {
                            additionalDuration,
                            currentEndTime,
                            currentDate,
                            extendedEndTime,
                            formattedExtendedDateEnd,
                            daysToAdd
                        });

                        // Create a form and submit it
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action =
                            `/sub_two/customer_checkins/extend-time/${this.currentExtendCheckinId}`;

                        // Add CSRF token
                        const csrfToken = document.createElement('input');
                        csrfToken.type = 'hidden';
                        csrfToken.name = '_token';
                        csrfToken.value = '{{ csrf_token() }}';
                        form.appendChild(csrfToken);

                        // Add other form data
                        const fields = {
                            extended_start_time: this.extendTimeData.current_end_time,
                            extended_end_time: extendedEndTime +
                                ':00', // Add seconds for database
                            extended_date_start: this.extendTimeData.current_date_end,
                            extended_date_end: formattedExtendedDateEnd,
                            additional_duration: additionalDuration
                        };

                        for (const [key, value] of Object.entries(fields)) {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = key;
                            input.value = value;
                            form.appendChild(input);
                        }

                        document.body.appendChild(form);
                        form.submit();

                    } catch (error) {
                        showAppToast('Failed to extend time: ' + error.message, 'error');
                        this.isExtendingTime = false;
                    }
                },

                // Search functionality
                async performSearch() {
                    this.currentFilters.search = this.searchQuery;
                    await this.applyFilters(this.currentFilters);
                },

                // Filter functionality - UPDATED TO MATCH OWNER VERSION
                async applyFilters(filters) {
                    this.isLoading = true;
                    this.showFilters = false;
                    this.currentFilters = {
                        ...filters
                    };

                    // Include BRN in filters if present
                    if (this.scannedBrn) {
                        this.currentFilters.brn = this.scannedBrn;
                    }

                    try {
                        const queryParams = new URLSearchParams();

                        // Add filters to query params
                        Object.entries(this.currentFilters).forEach(([key, value]) => {
                            if (value) {
                                queryParams.append(key, value);
                            }
                        });

                        // Always include ajax parameter
                        queryParams.append('ajax', 'true');

                        const url = `{{ route('sub_two.customer_checkins.index') }}?${queryParams.toString()}`;
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
                            this.checkins = data.data;
                            this.pagination = data.pagination;
                            this.stats = data.stats;
                            // Update scanned BRN from response
                            if (data.scanned_brn) {
                                this.scannedBrn = data.scanned_brn;
                            } else {
                                this.scannedBrn = '';
                            }
                            this.updatePaginationLinks();
                            this.updateActiveFilters();
                        } else {
                            throw new Error(data.message || 'Filter application failed');
                        }
                    } catch (error) {
                        console.error('Error applying filters:', error);
                        showAppToast('Error applying filters.', 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                // Remove filter
                async removeFilter(filterKey) {
                    if (filterKey === 'date_range') {
                        this.currentFilters.date_start = '';
                        this.currentFilters.date_end = '';
                    } else if (filterKey === 'search') {
                        this.searchQuery = '';
                        this.currentFilters.search = '';
                    } else if (filterKey === 'brn') {
                        // Clear BRN completely
                        this.scannedBrn = '';
                        this.currentFilters.brn = '';

                        // Also clear from session
                        try {
                            const clearUrl =
                                `{{ route('sub_two.customer_checkins.index') }}?clear_brn=true&ajax=true`;
                            await fetch(clearUrl, {
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                        } catch (error) {
                            showAppToast('Error clearing BRN filter.', 'error');
                        }
                    } else {
                        this.currentFilters[filterKey] = '';
                    }

                    await this.applyFilters(this.currentFilters);
                },

                // Clear filters - UPDATED TO MATCH OWNER VERSION
                async clearFilters() {
                    this.isLoading = true;
                    this.showFilters = false;
                    this.searchQuery = '';
                    this.scannedBrn = ''; // Clear scanned BRN
                    this.currentFilters = {
                        date_start: '',
                        date_end: '',
                        checkin_status: '',
                        search: '',
                        brn: '', // Clear BRN filter
                    };

                    try {
                        // Clear BRN from session first
                        const clearUrl = `{{ route('sub_two.customer_checkins.index') }}?clear_brn=true&ajax=true`;
                        const clearResponse = await fetch(clearUrl, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!clearResponse.ok) {
                            throw new Error(`HTTP error! status: ${clearResponse.status}`);
                        }

                        // Now fetch all data without filters
                        const queryParams = new URLSearchParams();
                        queryParams.append('ajax', 'true');
                        
                        const url = `{{ route('sub_two.customer_checkins.index') }}?${queryParams.toString()}`;
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
                            this.checkins = data.data;
                            this.pagination = data.pagination;
                            this.stats = data.stats;
                            this.scannedBrn = ''; // Ensure it's cleared
                            this.updatePaginationLinks();
                            this.updateActiveFilters();
                        } else {
                            throw new Error(data.message || 'Filter clearing failed');
                        }
                    } catch (error) {
                        console.error('Error clearing filters:', error);
                        showAppToast('Error clearing filters.', 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                async clearAllFilters() {
                    await this.clearFilters();
                },

                updateActiveFilters() {
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
                            this.checkins = data.data;
                            this.pagination = data.pagination;
                            this.stats = data.stats;
                            this.updatePaginationLinks();
                        } else {
                            throw new Error(data.message || 'Pagination failed');
                        }
                    } catch (error) {
                        showAppToast('Error changing page.', 'error');
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

                // Checkout functionality
                async confirmCheckout(checkinId) {
                    try {
                        const response = await fetch(
                            `/sub_two/customer_checkins/update-status/${checkinId}`, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    checkin_status: 0
                                })
                            });

                        const data = await response.json();

                        if (data.success) {
                            showAppToast('Customer checked-out successfully!', 'success');

                            // Update the local checkin data
                            const updatedCheckin = this.checkins.find(c => c.id === checkinId);
                            if (updatedCheckin) {
                                updatedCheckin.checkin_status = 0;
                                updatedCheckin.time_used = data.time_used;
                                updatedCheckin.time_used_formatted = data.time_used_formatted;
                            }

                            // Update stats
                            this.stats.active_checkins = Math.max(0, this.stats.active_checkins -
                                1);
                            this.stats.checked_out = this.stats.checked_out + 1;

                            // Redirect to booking list with BRN for BOTH booking types
                            if (data.redirect_url) {
                                setTimeout(() => {
                                    window.location.href = data.redirect_url;
                                }, 1500);
                            }
                        } else {
                            showAppToast(data.message || 'Failed to check-out', 'error');
                        }
                    } catch (error) {
                        showAppToast('An error occurred during check-out.', 'error');
                    }
                },

                // Add Order functionality
                async addOrder(checkin) {
                    try {
                        const params = new URLSearchParams();

                        // Checkin UUID
                        if (checkin.uuid) {
                            params.append('chk', checkin.uuid);
                        }

                        // Booking UUID
                        if (checkin.booking && checkin.booking.uuid) {
                            params.append('bkg', checkin.booking.uuid);
                        }

                        // Branch UUID
                        if (checkin.branch && checkin.branch.uuid) {
                            params.append('brn', checkin.branch.uuid);
                        }

                        // Customer UUID
                        if (checkin.customer_account && checkin.customer_account.uuid) {
                            params.append('cust', checkin.customer_account.uuid);
                        }

                        // Always include booking_ref_no
                        if (checkin.booking && checkin.booking.booking_ref_no) {
                            params.append('ref', checkin.booking.booking_ref_no);
                        }

                        const posUrl = `{{ route('sub_two.pos.index') }}?${params.toString()}`;
                        window.location.href = posUrl;

                    } catch (error) {
                        showAppToast('Failed to redirect to POS.', 'error');
                    }
                },

                // Add body class for modal
                addBodyClass() {
                    document.body.classList.add('modal-open');
                },

                // Remove body class
                removeBodyClass() {
                    document.body.classList.remove('modal-open');
                }
            }));
        });
    </script>
@endsection