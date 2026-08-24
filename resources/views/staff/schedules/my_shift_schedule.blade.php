@extends('layouts.app')

@section('title', 'Shift Schedule')

@section('content')

    <div x-data="staffShiftSchedule()" class="p-4">

        <!-- Loading Overlay -->
        <div x-show="loading" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-lg flex items-center space-x-3">
                <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                    </circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <span class="text-gray-700">Processing...</span>
            </div>
        </div>

        <h1 class="text-2xl font-bold text-[#4A2C1D] text-center mb-8">
            Shift Schedule
        </h1>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-8">
            <!-- Account Status -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Account Status</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="getAccountStatusText(staffData.account_status)">
                        </p>
                    </div>
                    <div class="p-3 rounded-lg" :class="getAccountStatusClass(staffData.account_status)">
                        <svg class="w-6 h-6" :class="getAccountStatusIconClass(staffData.account_status)" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Current Shift Status -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Current Shift</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="hasCurrentShift ? 'Assigned' : 'No Shift'"></p>
                    </div>
                    <div class="p-3 bg-purple-50 rounded-lg">
                        <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Pending Shifts -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Pending Shifts</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="getShiftCount(2)"></p>
                    </div>
                    <div class="p-3 bg-yellow-50 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Active Shifts -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Active Shifts</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="getShiftCount(1)"></p>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-lg">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shift Schedules Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Table Header with Filters -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-4 space-y-4 lg:space-y-0">
                    <h2 class="text-lg font-semibold text-gray-900">My Shift Schedules</h2>

                    <!-- Filters -->
                    <div
                        class="flex flex-col w-full space-y-4 md:space-y-0 md:flex-row md:items-center md:space-x-4 lg:w-auto">

                        <!-- Date Start -->
                        <div class="flex flex-col w-full">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Start Date</label>
                            <input type="date" x-model="filters.date_start" @change="loadShifts()"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Date End -->
                        <div class="flex flex-col w-full">
                            <label class="block text-xs font-medium text-gray-500 mb-1">End Date</label>
                            <input type="date" x-model="filters.date_end" @change="loadShifts()"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Status Filter -->
                        <div class="flex flex-col w-full">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Shift Status</label>
                            <select x-model="filters.shift_status" @change="loadShifts()"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Status</option>
                                <option value="2">Pending</option>
                                <option value="1">On-duty</option>
                                <option value="0">Completed</option>
                            </select>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date & Time
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Branch
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Shift Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Time In
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Time Out
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Working Hours
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Arrival Status
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="schedule in staffData.staff_schedules" :key="schedule.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <!-- Shift Dates & Times -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"
                                        x-text="formatShiftDate(schedule.shift_date_start, schedule.shift_date_end)">
                                    </div>
                                    <div class="text-sm text-gray-500"
                                        x-text="formatShiftTime(schedule.shift_time_start, schedule.shift_time_end)">
                                    </div>
                                </td>

                                <!-- Branch -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span x-text="schedule.branch_name"></span>
                                </td>

                                <!-- Shift Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="getShiftStatusClass(schedule.staff_shift_schedule_status)"
                                        x-text="getShiftStatusText(schedule.staff_shift_schedule_status)">
                                    </span>
                                </td>

                                <!-- Check-in Time -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <template x-if="schedule.latest_checkin && schedule.latest_checkin.checkin_time">
                                        <span x-text="formatDateTime(schedule.latest_checkin.checkin_time)"></span>
                                    </template>
                                    <template x-if="!schedule.latest_checkin || !schedule.latest_checkin.checkin_time">
                                        <span class="text-gray-400">-</span>
                                    </template>
                                </td>

                                <!-- Check-out Time -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <template x-if="schedule.latest_checkin && schedule.latest_checkin.checkout_time">
                                        <span x-text="formatDateTime(schedule.latest_checkin.checkout_time)"></span>
                                    </template>
                                    <template x-if="!schedule.latest_checkin || !schedule.latest_checkin.checkout_time">
                                        <span class="text-gray-400">-</span>
                                    </template>
                                </td>

                                <!-- Time Worked -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <template
                                        x-if="schedule.latest_checkin && schedule.latest_checkin.time_worked_formatted">
                                        <span x-text="schedule.latest_checkin.time_worked_formatted"></span>
                                    </template>
                                    <template
                                        x-if="!schedule.latest_checkin || !schedule.latest_checkin.time_worked_formatted">
                                        <span class="text-gray-400">-</span>
                                    </template>
                                </td>

                                <!-- Check-in Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <template x-if="schedule.latest_checkin">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="schedule.latest_checkin.checkin_status == 1 ?
                                                'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                            x-text="schedule.latest_checkin.checkin_status == 1 ? 'Time In' : 'Time Out'">
                                        </span>
                                    </template>
                                    <template x-if="!schedule.latest_checkin">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Not Checked-in
                                        </span>
                                    </template>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <template x-if="schedule.staff_shift_schedule_status === 2"> <!-- Pending -->
                                        <button @click="checkin(schedule.id)" :disabled="loading"
                                            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors disabled:bg-green-400 disabled:cursor-not-allowed">
                                            <span x-show="!loading">Time in</span>
                                            <span x-show="loading" class="flex items-center">
                                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                                    xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                                        stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor"
                                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                    </path>
                                                </svg>
                                                Processing...
                                            </span>
                                        </button>
                                    </template>

                                    <template x-if="schedule.staff_shift_schedule_status === 1"> <!-- On-duty -->
                                        <!-- Show checkout button if there's an active checkin -->
                                        <template
                                            x-if="schedule.latest_checkin && schedule.latest_checkin.checkout_time === null">
                                            <button @click="checkout(schedule.latest_checkin.id)" :disabled="loading"
                                                class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors disabled:bg-red-400 disabled:cursor-not-allowed">
                                                <span x-show="!loading">Time Out</span>
                                                <span x-show="loading" class="flex items-center">
                                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                                        xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                                            stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor"
                                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                        </path>
                                                    </svg>
                                                    Processing...
                                                </span>
                                            </button>
                                        </template>
                                        <!-- Show completed message if already checked out -->
                                        <template
                                            x-if="schedule.latest_checkin && schedule.latest_checkin.checkout_time !== null">
                                            <span class="text-green-600 text-sm">Already Checked-out</span>
                                        </template>
                                        <!-- Show message if no checkin found -->
                                        <template x-if="!schedule.latest_checkin">
                                            <span class="text-orange-500 text-sm">Check-in data missing</span>
                                        </template>
                                    </template>

                                    <template x-if="schedule.staff_shift_schedule_status === 0"> <!-- Completed -->
                                        <span class="text-gray-400 text-sm">Completed</span>
                                    </template>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <template x-if="!staffData.staff_schedules || staffData.staff_schedules.length === 0">
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <div class="text-gray-400">
                                        <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <h5 class="text-sm font-medium text-gray-900">No Shift Schedules Found</h5>
                                        <p class="text-sm text-gray-500"
                                            x-text="hasActiveFilters ? 'No schedules match your current filters.' : 'You don\'t have any scheduled shifts at the moment.'">
                                        </p>
                                        <template x-if="hasActiveFilters">
                                            <button @click="resetFilters()"
                                                class="mt-2 bg-[#7F5539] text-white px-4 py-2 rounded-lg hover:bg-[#4A2C1D] transition-colors">
                                                Reset Filters
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('staffShiftSchedule', () => ({
                // Initial state
                staffData: @json($staff_data),
                filters: {
                    date_start: '{{ $filters['date_start'] ?? '' }}',
                    date_end: '{{ $filters['date_end'] ?? '' }}',
                    shift_status: '{{ $filters['shift_status'] ?? '' }}'
                },
                loading: false,

                // Computed properties
                get hasCurrentShift() {
                    return this.staffData.staff_schedules && this.staffData.staff_schedules.length >
                        0;
                },

                get hasActiveFilters() {
                    return this.filters.date_start || this.filters.date_end || this.filters
                        .shift_status;
                },

                // Initialize method
                init() {
                    // Set up filter change handling
                    this.$watch('filters', (newFilters, oldFilters) => {
                        // Use a small debounce to prevent too many requests
                        clearTimeout(this.filterTimeout);
                        this.filterTimeout = setTimeout(() => {
                            this.applyFilters();
                        }, 500);
                    });
                },

                // Apply filters by redirecting with filter parameters
                applyFilters() {
                    const params = new URLSearchParams();

                    // Add filters to URL parameters
                    Object.entries(this.filters).forEach(([key, value]) => {
                        if (value) {
                            params.append(key, value);
                        }
                    });

                    // Redirect to the same page with filters
                    const newUrl = `/sub_two/my_shift_schedules?${params.toString()}`;
                    window.location.href = newUrl;
                },

                // Your existing helper methods (keep all of them)
                getShiftCount(status) {
                    if (!this.staffData.staff_schedules) return 0;
                    return this.staffData.staff_schedules.filter(schedule => schedule
                        .staff_shift_schedule_status === status).length;
                },

                getAccountStatusClass(status) {
                    const statusClasses = {
                        0: 'bg-red-100 text-red-800',
                        1: 'bg-green-100 text-green-800',
                    };
                    return statusClasses[status] || 'bg-gray-100 text-gray-800';
                },

                getAccountStatusIconClass(status) {
                    const statusClasses = {
                        0: 'text-red-500',
                        1: 'text-green-500',
                    };
                    return statusClasses[status] || 'text-gray-500';
                },

                getAccountStatusText(status) {
                    const statusText = {
                        0: 'Suspended',
                        1: 'Verified',
                    };
                    return statusText[status] || 'Unknown';
                },

                getShiftStatusClass(status) {
                    const statusClasses = {
                        0: 'bg-green-100 text-green-800',
                        1: 'bg-blue-100 text-blue-800',
                        2: 'bg-yellow-100 text-yellow-800',
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

                formatShiftDate(startDate, endDate) {
                    if (!startDate) return 'N/A';
                    const start = new Date(startDate);
                    const end = new Date(endDate);

                    if (start.toDateString() === end.toDateString()) {
                        return start.toLocaleDateString('en-US', {
                            month: 'short',
                            day: 'numeric',
                            year: 'numeric'
                        });
                    } else {
                        return `${start.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${end.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
                    }
                },

                formatShiftTime(startTime, endTime) {
                    if (!startTime || !endTime) return 'N/A';
                    const formatTime = (timeString) => {
                        const [hours, minutes] = timeString.split(':');
                        const date = new Date();
                        date.setHours(parseInt(hours), parseInt(minutes));
                        return date.toLocaleTimeString('en-US', {
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true
                        });
                    };
                    return `${formatTime(startTime)} - ${formatTime(endTime)}`;
                },

                formatDateTime(dateTimeString) {
                    if (!dateTimeString) return 'N/A';
                    try {
                        const date = new Date(dateTimeString);
                        return date.toLocaleDateString('en-US', {
                            month: 'short',
                            day: 'numeric',
                            year: 'numeric',
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true
                        });
                    } catch (error) {
                        console.error('Error formatting date:', dateTimeString, error);
                        return 'Invalid Date';
                    }
                },

                // API Methods - Use redirects with preserved filters
                async checkin(scheduleId) {
                    if (this.loading) return;

                    this.loading = true;
                    try {
                        const response = await fetch(
                            `/sub_two/my_shift_schedules/${scheduleId}/checkin`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                }
                            });

                        const data = await response.json();

                        if (data.success) {
                            // Redirect while preserving current filters
                            this.redirectWithFilters();
                        } else {
                            console.error('Check-in failed:', data.message);
                            this.loading = false;
                        }
                    } catch (error) {
                        console.error('Error checking in:', error);
                        this.loading = false;
                    }
                },

                async checkout(checkinId) {
                    if (this.loading) return;

                    this.loading = true;
                    try {
                        const response = await fetch(
                            `/sub_two/my_shift_schedules/${checkinId}/checkout`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                }
                            });

                        const data = await response.json();

                        if (data.success) {
                            // Redirect while preserving current filters
                            this.redirectWithFilters();
                        } else {
                            console.error('Check-out failed:', data.message);
                            this.loading = false;
                        }
                    } catch (error) {
                        console.error('Error checking out:', error);
                        this.loading = false;
                    }
                },

                // Helper method to redirect with current filters
                redirectWithFilters() {
                    const params = new URLSearchParams();

                    Object.entries(this.filters).forEach(([key, value]) => {
                        if (value) {
                            params.append(key, value);
                        }
                    });

                    window.location.href = `/sub_two/my_shift_schedules?${params.toString()}`;
                },

                resetFilters() {
                    // Reset filters and redirect
                    this.filters = {
                        date_start: '',
                        date_end: '',
                        shift_status: ''
                    };
                    // Apply the reset immediately
                    this.applyFilters();
                }
            }));
        });
    </script>
@endsection
