@extends('layouts.app')

@section('content')
    <!-- Header -->
    <h1 class="text-2xl font-bold text-gray-900 mt-4 mb-8 text-center">Customer Feedback & Reviews</h1>

    <div x-data="feedbackData()" x-init="init()" class="p-4">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- Overall Rating -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-[#7F5539]/10 p-3 rounded-lg">
                        <svg class="h-6 w-6 text-[#7F5539]" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Overall Rating</p>
                        <p class="text-2xl font-semibold text-gray-900" x-text="(overallRating ?? {{ number_format($overallRating, 1) }}) + '/5.0'"></p>
                    </div>
                </div>
            </div>

            <!-- Total Feedbacks -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-100 p-3 rounded-lg">
                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Feedbacks</p>
                        <p class="text-2xl font-semibold text-gray-900" x-text="pagination.total ?? {{ $totalFeedbacks }}"></p>
                    </div>
                </div>
            </div>

            <!-- 5-Star Reviews -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-100 p-3 rounded-lg">
                        <svg class="h-6 w-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">5-Star Reviews</p>
                        <p class="text-2xl font-semibold text-gray-900" x-text="fiveStarCount || 0"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Branch Filter Tabs -->
        <div class="mb-4 p-3 bg-white rounded-lg shadow-sm" style="border: 1px solid #e6ddd4;">
            <div class="inline-flex rounded-lg p-1 w-full overflow-x-auto" style="background-color: #e6ddd4; border: 1px solid #d4c4b2;">
                <div class="flex space-x-1 w-full">
                    <!-- All Branches -->
                    <button
                        @click="toggleBranch('')"
                        class="relative transition-all duration-200 py-2 px-4 rounded-md text-sm font-medium focus:outline-none whitespace-nowrap"
                        :style="filters.branch_id === ''
                            ? 'background-color: #9c6644; color: #fff;'
                            : 'background-color: transparent; color: #7f5539;'">
                        All Branches
                    </button>
                    <!-- Per Branch -->
                    <template x-for="branch in branches" :key="branch.id">
                        <button
                            @click="toggleBranch(branch.id)"
                            class="relative transition-all duration-200 py-2 px-4 rounded-md text-sm font-medium focus:outline-none whitespace-nowrap"
                            :style="filters.branch_id == branch.id
                                ? 'background-color: #9c6644; color: #fff;'
                                : 'background-color: transparent; color: #7f5539;'"
                            x-text="branch.branch_name">
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Table Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-4 space-y-4 lg:space-y-0">

                    <!-- Left: Active Branch Label -->
                    <div class="flex items-center">
                        <h2 class="text-lg font-semibold text-gray-900">
                            <span x-show="filters.branch_id === ''">All Branches</span>
                            <template x-for="branch in branches" :key="branch.id">
                                <span x-show="filters.branch_id == branch.id" x-text="branch.branch_name + ' Branch'"></span>
                            </template>
                        </h2>
                    </div>

                    <!-- Right: Search + Filter -->
                    <div class="flex items-center space-x-3">
                        <!-- Search Input -->
                        <div class="relative">
                            <input type="text" x-model="searchQuery" @input.debounce.500ms="performSearch()"
                                placeholder="Search feedbacks..."
                                class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] w-full lg:w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Filter Button -->
                        <button @click="showFilters = true"
                            class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-5 h-5 mr-1">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                            </svg>
                            Filters
                            <!-- Active filter dot indicator -->
                            <span x-show="hasModalFilters"
                                class="ml-1.5 inline-flex h-2 w-2 rounded-full bg-[#7F5539]"></span>
                        </button>
                    </div>
                </div>

                <!-- Active Filters Badges -->
                <div x-show="showActiveFilters" x-cloak class="flex flex-wrap items-center gap-2 mt-3">
                    <template x-for="filter in activeFilters" :key="filter.key">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-[#4A2C1D]/10 text-[#7F5539]">
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Feedback</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="feedback in feedbacks" :key="feedback.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <!-- Customer -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">Anonymous</div>
                                </td>

                                <!-- Rating -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex">
                                            <template x-for="i in 5" :key="i">
                                                <svg :class="i <= feedback.rating ? 'text-yellow-400' : 'text-gray-300'"
                                                    class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                            </template>
                                        </div>
                                        <span class="ml-2 text-sm font-medium text-gray-900" x-text="feedback.rating + '/5'"></span>
                                    </div>
                                </td>

                                <!-- Branch -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" x-text="feedback.branch?.branch_name || 'N/A'"></div>
                                </td>

                                <!-- Service Category -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" x-text="feedback.service_category?.service_category || 'N/A'"></div>
                                </td>

                                <!-- Service Name -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" x-text="feedback.service_name?.service_name || 'N/A'"></div>
                                </td>

                                <!-- Feedback Comment -->
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 max-w-xs" x-text="feedback.comment || 'No comment'"></div>
                                </td>

                                <!-- Date -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" x-text="formatDate(feedback.created_at)"></div>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <tr x-show="!feedbacks.length">
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                    </svg>
                                    <h5 class="text-sm font-medium text-gray-900"
                                        x-text="hasActiveFilters ? 'No feedbacks match your filters' : 'No feedbacks found'"></h5>
                                    <p class="text-sm text-gray-500"
                                        x-text="hasActiveFilters ? 'Try adjusting your filters.' : 'Customer feedbacks will appear here.'"></p>
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
                        <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
                            class="px-3 py-1 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            Previous
                        </button>
                        <template x-for="page in paginationLinks" :key="page">
                            <button @click="changePage(page)" class="px-3 py-1 border rounded-lg text-sm font-medium"
                                :class="page === pagination.current_page ?
                                    'border-2 border-[#4A2C1D] bg-[#7F5539]/80 text-white' :
                                    'border-gray-300 text-gray-700 hover:bg-gray-50'"
                                :disabled="page === '...'" x-text="page"></button>
                        </template>
                        <button @click="changePage(pagination.current_page + 1)"
                            :disabled="pagination.current_page === pagination.last_page"
                            class="px-3 py-1 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Filter Modal ── -->
        <div x-show="showFilters" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showFilters = false"></div>

                <!-- Modal Panel -->
                <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl sm:my-8 sm:align-middle max-w-md w-full">

                    <!-- Modal Header with X button -->
                    <div class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Filter Feedback</h3>
                        <button @click="showFilters = false; dateError = ''"
                            class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-5">
                        <div class="space-y-5">

                            <!-- Date Range -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                                <div class="flex gap-2 mb-3">
                                    <button @click="setDatePreset('week')"
                                        :class="datePreset === 'week'
                                            ? 'bg-[#7F5539] text-white border-[#7F5539]'
                                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                        class="flex-1 px-3 py-1.5 border rounded-lg text-xs font-medium transition-colors focus:outline-none">
                                        Last 7 Days
                                    </button>
                                    <button @click="setDatePreset('month')"
                                        :class="datePreset === 'month'
                                            ? 'bg-[#7F5539] text-white border-[#7F5539]'
                                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                        class="flex-1 px-3 py-1.5 border rounded-lg text-xs font-medium transition-colors focus:outline-none">
                                        Last 30 Days
                                    </button>
                                    <button @click="setDatePreset('custom')"
                                        :class="datePreset === 'custom'
                                            ? 'bg-[#7F5539] text-white border-[#7F5539]'
                                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                        class="flex-1 px-3 py-1.5 border rounded-lg text-xs font-medium transition-colors focus:outline-none">
                                        Custom
                                    </button>
                                </div>
                                <div x-show="datePreset === 'custom'" class="flex flex-col gap-2">
                                    <div class="flex gap-3">
                                        <div class="flex-1">
                                            <label class="block text-xs text-gray-500 mb-1">From</label>
                                            <input type="date" x-model="filters.date_from"
                                                @change="dateError = ''"
                                                :class="dateError ? 'border-red-400 focus:ring-red-400 focus:border-red-400' : 'border-gray-300 focus:ring-[#7F5539] focus:border-[#7F5539]'"
                                                class="block w-full border rounded-lg px-3 py-2 text-sm focus:ring-2">
                                        </div>
                                        <div class="flex-1">
                                            <label class="block text-xs text-gray-500 mb-1">To</label>
                                            <input type="date" x-model="filters.date_to"
                                                @change="dateError = (filters.date_from && filters.date_to && new Date(filters.date_to) < new Date(filters.date_from)) ? '\"To\" date cannot be earlier than \"From\" date.' : ''"
                                                :class="dateError ? 'border-red-400 focus:ring-red-400 focus:border-red-400' : 'border-gray-300 focus:ring-[#7F5539] focus:border-[#7F5539]'"
                                                class="block w-full border rounded-lg px-3 py-2 text-sm focus:ring-2">
                                        </div>
                                    </div>
                                    <!-- Inline error message -->
                                    <div x-show="dateError" x-cloak class="flex items-center gap-1.5 text-red-600 text-xs mt-0.5">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        <span x-text="dateError"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Service Category -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Service Category</label>
                                <select x-model="filters.service_category_id"
                                    class="block w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] px-3 py-2 text-sm">
                                    <option value="">All Categories</option>
                                    <template x-for="category in serviceCategories" :key="category.id">
                                        <option :value="category.id" x-text="category.service_category"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Service Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Service Name</label>
                                <select x-model="filters.service_name_id"
                                    class="block w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] px-3 py-2 text-sm">
                                    <option value="">All Services</option>
                                    <template x-for="service in serviceNames" :key="service.id">
                                        <option :value="service.id" x-text="service.service_name"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Rating -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Rating</label>
                                <select x-model="filters.rating"
                                    class="block w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] px-3 py-2 text-sm">
                                    <option value="">All Ratings</option>
                                    <option value="5">5 Stars</option>
                                    <option value="4">4 Stars</option>
                                    <option value="3">3 Stars</option>
                                    <option value="2">2 Stars</option>
                                    <option value="1">1 Star</option>
                                </select>
                            </div>

                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 pb-5 flex gap-3">
                        <button @click="clearModalFilters()"
                            class="flex-1 inline-flex justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539]">
                            Clear
                        </button>
                        <button @click="applyFilters()"
                            class="flex-1 inline-flex justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539]">
                            Apply Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('feedbackData', () => ({
                feedbacks: @json($feedbacks->items() ?? []),
                pagination: @json($feedbacks->toArray()),
                branches: @json($branches ?? []),
                serviceCategories: @json($uniqueServiceCategories ?? []),
                serviceNames: @json($uniqueServiceNames ?? []),
                filters: {
                    branch_id: '{{ request('branch_id', '') }}',
                    service_category_id: '{{ request('service_category_id', '') }}',
                    service_name_id: '{{ request('service_name_id', '') }}',
                    rating: '{{ request('rating', '') }}',
                    date_from: '{{ request('date_from', '') }}',
                    date_to: '{{ request('date_to', '') }}',
                },
                datePreset: 'all',
                searchQuery: '{{ request('search', '') }}',
                showFilters: false,
                showActiveFilters: {{ request('service_category_id') || request('service_name_id') || request('rating') || request('date_from') ? 'true' : 'false' }},
                paginationLinks: [],
                isLoading: false,
                fiveStarCount: 0,
                overallRating: {{ number_format($overallRating, 1) }},
                dateError: '',

                init() {
                    this.updatePaginationLinks();
                    this.calculateFiveStarCount();
                    this.showActiveFilters = this.hasModalFilters;
                },

                // ── Computed ──────────────────────────────────────────────

                get hasActiveFilters() {
                    return Object.values(this.filters).some(v => v !== '') || this.searchQuery;
                },

                get hasModalFilters() {
                    // Branch is NOT a modal filter — it's handled by the tab buttons
                    return this.filters.service_category_id !== ''
                        || this.filters.service_name_id !== ''
                        || this.filters.rating !== ''
                        || this.filters.date_from !== ''
                        || this.filters.date_to !== '';
                },

                get activeFilters() {
                    const filters = [];

                    if (this.filters.date_from || this.filters.date_to) {
                        const from = this.filters.date_from ? this.formatDate(this.filters.date_from) : '—';
                        const to   = this.filters.date_to   ? this.formatDate(this.filters.date_to)   : '—';
                        filters.push({ key: 'date', label: `Date: ${from} → ${to}` });
                    }

                    if (this.filters.service_category_id) {
                        const cat = this.serviceCategories.find(c => c.id == this.filters.service_category_id);
                        if (cat) filters.push({ key: 'service_category_id', label: `Category: ${cat.service_category}` });
                    }

                    if (this.filters.service_name_id) {
                        const svc = this.serviceNames.find(s => s.id == this.filters.service_name_id);
                        if (svc) filters.push({ key: 'service_name_id', label: `Service: ${svc.service_name}` });
                    }

                    if (this.filters.rating) {
                        filters.push({ key: 'rating', label: `Rating: ${this.filters.rating} stars` });
                    }

                    return filters;
                },

                // ── Helpers ───────────────────────────────────────────────

                formatDate(dateString) {
                    if (!dateString) return 'N/A';
                    return new Date(dateString).toLocaleDateString('en-US', {
                        year: 'numeric', month: 'short', day: 'numeric'
                    });
                },

                calculateFiveStarCount() {
                    this.fiveStarCount = this.feedbacks.filter(f => f.rating === 5).length;
                },

                // ── Branch toggle (tab buttons) ────────────────────────────

                toggleBranch(branchId) {
                    this.filters.branch_id = branchId;
                    this.applyFilters();
                },

                // ── Date preset ───────────────────────────────────────────

                setDatePreset(preset) {
                    this.datePreset = preset;
                    const today = new Date();
                    // Use local date to avoid UTC-shift issues (e.g. PH timezone)
                    const fmt = d => {
                        const y = d.getFullYear();
                        const m = String(d.getMonth() + 1).padStart(2, '0');
                        const day = String(d.getDate()).padStart(2, '0');
                        return `${y}-${m}-${day}`;
                    };
                
                    if (preset === 'week') {
                        const from = new Date(today);
                        from.setDate(today.getDate() - 6); // last 7 days inclusive of today
                        this.filters.date_from = fmt(from);
                        this.filters.date_to   = fmt(today);
                        this.applyFilters();             // ← actually trigger the fetch
                    } else if (preset === 'month') {
                        const from = new Date(today);
                        from.setDate(today.getDate() - 29); // last 30 days inclusive of today
                        this.filters.date_from = fmt(from);
                        this.filters.date_to   = fmt(today);
                        this.applyFilters();             // ← actually trigger the fetch
                    } else {
                        // 'custom' — just show the pickers, don't fetch yet
                        this.filters.date_from = '';
                        this.filters.date_to   = '';
                    }
                },

                // ── Search ────────────────────────────────────────────────

                async performSearch() {
                    await this.applyFilters();
                },

                // ── Apply filters (AJAX) ──────────────────────────────────

                async applyFilters() {
                    // Validate date range before doing anything
                    if (this.filters.date_from && this.filters.date_to) {
                        if (new Date(this.filters.date_to) < new Date(this.filters.date_from)) {
                            this.dateError = '"To" date cannot be earlier than "From" date.';
                            return;
                        }
                    }
                    this.dateError = '';
                    this.isLoading = true;
                    this.showFilters = false;
                    this.showActiveFilters = this.hasModalFilters;

                    try {
                        const queryParams = new URLSearchParams();

                        Object.entries(this.filters).forEach(([key, value]) => {
                            if (value) queryParams.append(key, value);
                        });

                        if (this.searchQuery) queryParams.append('search', this.searchQuery);
                        queryParams.append('ajax', 'true');

                        const url = `{{ route('sub_one.feedback.index') }}?${queryParams.toString()}`;
                        const response = await fetch(url, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });

                        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

                        const data = await response.json();

                        if (data.success) {
                            this.feedbacks = data.data;
                            this.pagination = data.pagination;
                            this.overallRating = data.overall_rating ?? this.overallRating;
                            this.updatePaginationLinks();
                            this.calculateFiveStarCount();
                        } else {
                            throw new Error(data.message || 'Filter application failed');
                        }
                    } catch (error) {
                        console.error('Error applying filters:', error);
                    } finally {
                        this.isLoading = false;
                    }
                },

                // Clear only the modal filters (not the branch tab)
                clearModalFilters() {
                    this.filters.service_category_id = '';
                    this.filters.service_name_id     = '';
                    this.filters.rating              = '';
                    this.filters.date_from           = '';
                    this.filters.date_to             = '';
                    this.datePreset                  = 'all';
                    this.dateError                   = '';
                },

                async clearAllFilters() {
                    this.searchQuery = '';
                    this.clearModalFilters();
                    this.filters.branch_id   = '';
                    this.showFilters         = false;
                    this.showActiveFilters   = false;

                    try {
                        this.isLoading = true;
                        const url = `{{ route('sub_one.feedback.index') }}?ajax=true`;
                        const response = await fetch(url, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.feedbacks = data.data;
                            this.pagination = data.pagination;
                            this.overallRating = data.overall_rating ?? this.overallRating;
                            this.updatePaginationLinks();
                            this.calculateFiveStarCount();
                        }
                    } catch (error) {
                        console.error('Error clearing filters:', error);
                    } finally {
                        this.isLoading = false;
                    }
                },

                removeFilter(filterKey) {
                    if (filterKey === 'date') {
                        this.filters.date_from = '';
                        this.filters.date_to   = '';
                        this.datePreset        = 'all';
                    } else {
                        this.filters[filterKey] = '';
                    }
                    this.applyFilters();
                    if (!this.hasModalFilters) this.showActiveFilters = false;
                },

                // ── Pagination ────────────────────────────────────────────

                async changePage(page) {
                    if (page < 1 || page > this.pagination.last_page) return;
                    try {
                        this.isLoading = true;
                        const queryParams = new URLSearchParams();

                        Object.entries(this.filters).forEach(([key, value]) => {
                            if (value) queryParams.append(key, value);
                        });

                        if (this.searchQuery) queryParams.append('search', this.searchQuery);
                        queryParams.append('page', page);
                        queryParams.append('ajax', 'true');

                        const url = `{{ route('sub_one.feedback.index') }}?${queryParams.toString()}`;
                        const response = await fetch(url, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const data = await response.json();

                        if (data.success) {
                            this.feedbacks = data.data;
                            this.pagination = data.pagination;
                            this.updatePaginationLinks();
                        }
                    } catch (error) {
                        console.error('Error changing page:', error);
                    } finally {
                        this.isLoading = false;
                    }
                },

                updatePaginationLinks() {
                    if (!this.pagination || !this.pagination.last_page) {
                        this.paginationLinks = [];
                        return;
                    }
                    const current = this.pagination.current_page;
                    const last    = this.pagination.last_page;
                    const delta   = 2;
                    const range   = [];
                    const rangeWithDots = [];

                    for (let i = 1; i <= last; i++) {
                        if (i === 1 || i === last || (i >= current - delta && i <= current + delta)) {
                            range.push(i);
                        }
                    }

                    let prev = 0;
                    for (let i of range) {
                        if (prev) {
                            if (i - prev === 2)      rangeWithDots.push(prev + 1);
                            else if (i - prev !== 1) rangeWithDots.push('...');
                        }
                        rangeWithDots.push(i);
                        prev = i;
                    }
                    this.paginationLinks = rangeWithDots;
                },

                // kept for legacy calls but unused
                updateActiveFilters() {},
                showNotification(message, type = 'success') {
                    console.log(`${type}: ${message}`);
                },
            }));
        });
    </script>
@endsection