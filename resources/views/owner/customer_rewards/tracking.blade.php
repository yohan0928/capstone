@extends('layouts.app')

@section('content')
<style>
    [x-cloak] {
        display: none !important;
    }

    html body.modal-open {
        overflow: hidden !important;
    }

    body {
        overflow-y: auto;
    }

    .progress-bar {
        background-color: #e5e7eb;
        border-radius: 9999px;
        overflow: hidden;
        height: 8px;
    }

    .progress-fill {
        height: 100%;
        border-radius: 9999px;
        transition: width 0.3s ease;
    }

    .progress-low {
        background-color: #ef4444;
    }

    .progress-medium {
        background-color: #f59e0b;
    }

    .progress-high {
        background-color: #10b981;
    }

    .progress-complete {
        background-color: #7F5539;
    }

    .scrollable-table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .scrollable-table {
        min-width: 100%;
        white-space: nowrap;
    }

    .overflow-y-auto::-webkit-scrollbar {
        width: 6px;
    }

    .overflow-y-auto::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .overflow-y-auto::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
    }

    .overflow-y-auto::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<div x-data="customerRewardTracking()" x-init="init()" class="p-4">
    <!-- Header -->
    <h1 class="text-2xl font-bold text-gray-900 mt-4 mb-8 text-center">Customer Reward Tracking</h1>

    <!-- Error Alert -->
    @if (isset($error))
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-red-700">{{ $error }}</p>
            </div>
        </div>
    @endif

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Earned Rewards</p>
                    <p class="text-xl font-bold text-gray-900" x-text="stats.total_earned_rewards || 0"></p>
                </div>
                <div class="p-2 bg-[#4A2C1D]/10 rounded-lg">
                    <svg class="w-5 h-5 text-[#7F5539]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Available</p>
                    <p class="text-xl font-bold text-gray-900" x-text="stats.available_rewards || 0"></p>
                </div>
                <div class="p-2 bg-green-50 rounded-lg">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Redeemed</p>
                    <p class="text-xl font-bold text-gray-900" x-text="stats.redeemed_rewards || 0"></p>
                </div>
                <div class="p-2 bg-purple-50 rounded-lg">
                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Potential</p>
                    <p class="text-xl font-bold text-gray-900" x-text="stats.potential_rewards || 0"></p>
                </div>
                <div class="p-2 bg-yellow-50 rounded-lg">
                    <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Unique Customers</p>
                    <p class="text-xl font-bold text-gray-900" x-text="stats.unique_customers || 0"></p>
                </div>
                <div class="p-2 bg-blue-50 rounded-lg">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <!-- Table Header -->
<div class="px-6 py-4 border-b border-gray-200">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
        <!-- Search and Filters -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full lg:w-auto">
            <h2 class="text-lg font-semibold text-gray-900 whitespace-nowrap">Customer Reward Progress</h2>
            
            <!-- Search Input -->
            <div class="relative flex-1 w-full sm:w-64">
                <input type="text" 
                    x-model="searchQuery" 
                    @input.debounce.500ms="performSearch()"
                    placeholder="Search customers..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <!-- Branch Filter -->
            <select x-model="currentFilters.branch_id" @change="applyFilters(currentFilters)"
                class="w-full sm:w-auto px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]">
                <option value="">All Branches</option>
                <template x-for="branch in branches" :key="branch.id">
                    <option :value="branch.id" x-text="branch.branch_name"></option>
                </template>
            </select>

            <!-- Min Bookings Filter -->
            <select x-model="currentFilters.min_bookings" @change="applyFilters(currentFilters)"
                class="w-full sm:w-auto px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]">
                <option value="">Min Bookings</option>
                <option value="1">1+ bookings</option>
                <option value="5">5+ bookings</option>
                <option value="10">10+ bookings</option>
                <option value="20">20+ bookings</option>
                <option value="50">50+ bookings</option>
            </select>
        </div>
    </div>

    <!-- Active Filters Badge -->
    <div x-show="hasActiveFilters" class="flex items-center flex-wrap gap-2 mt-3">
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Customer</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total Bookings</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Earned Rewards</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Last Booking</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="customer in customers" :key="customer.id">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
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
                                            x-text="(customer.first_name || 'N/A') + ' ' + (customer.last_name || '')">
                                        </div>
                                        <div class="text-sm text-gray-500" x-text="customer.email || 'N/A'"></div>
                                        <div class="text-xs text-gray-400"
                                            x-text="customer.contact_no || 'No contact'"></div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="text-center">
                                    <div class="text-lg font-bold text-[#7F5539]"
                                        x-text="customer.bookings ? customer.bookings.length : 0"></div>
                                    <div class="text-xs text-gray-500">Completed Bookings</div>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="text-center">
                                    <div class="text-lg font-bold text-green-600"
                                        x-text="customer.earned_rewards_count || 0"></div>
                                    <div class="text-xs text-gray-500">Rewards Earned</div>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <template x-if="customer.bookings && customer.bookings.length > 0">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"
                                            x-text="formatDate(customer.bookings[0].date_start)"></div>
                                        <div class="text-xs text-gray-500"
                                            x-text="customer.bookings[0].branch ? customer.bookings[0].branch.branch_name : 'N/A'"></div>
                                    </div>
                                </template>
                                <template x-if="!customer.bookings || customer.bookings.length === 0">
                                    <span class="text-sm text-gray-400">No bookings</span>
                                </template>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <button @click="viewCustomerDetails(customer.id)"
                                    class="px-3 py-1 bg-[#7F5539] text-white rounded-lg hover:bg-[#4A2C1D] transition-colors text-sm font-medium">
                                    View Details
                                </button>
                            </td>
                        </tr>
                    </template>

                    <!-- Loading State -->
                    <tr x-show="isLoading && customers.length === 0">
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="flex justify-center items-center space-x-3">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#7F5539]"></div>
                                <span class="text-gray-600">Loading customers...</span>
                            </div>
                        </td>
                    </tr>

                    <!-- Empty State -->
                    <tr x-show="!isLoading && customers.length === 0">
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="text-gray-400">
                                <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                                <h5 class="text-sm font-medium text-gray-900"
                                    x-text="hasActiveFilters ? 'No customers match your filters' : 'No customer data found'">
                                </h5>
                                <p class="text-sm text-gray-500"
                                    x-text="hasActiveFilters ? 'Try adjusting your filters.' : 'Customer reward tracking data will appear here.'">
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
                <div class="text-sm text-gray-700 text-center sm:text-left">
                    Showing <span x-text="pagination.from || 0"></span> to <span x-text="pagination.to || 0"></span>
                    of <span x-text="pagination.total || 0"></span> entries
                </div>

                <div class="flex flex-wrap justify-center items-center gap-2">
                    <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                        <span class="hidden sm:inline">Previous</span>
                        <span class="sm:hidden">←</span>
                    </button>

                    <template x-for="page in paginationLinks" :key="page">
                        <button @click="changePage(page)"
                            class="px-3 py-2 border rounded-lg text-sm font-medium transition-colors duration-200"
                            :class="page === pagination.current_page ?
                                'border-2 border-[#4A2C1D] bg-[#7F5539]/80 text-white' :
                                'border-gray-300 text-gray-700 hover:bg-gray-50'"
                            :disabled="page === '...'" x-text="page"></button>
                    </template>

                    <button @click="changePage(pagination.current_page + 1)"
                        :disabled="pagination.current_page === pagination.last_page"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                        <span class="hidden sm:inline">Next</span>
                        <span class="sm:hidden">→</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- CUSTOMER DETAILS MODAL -->
    <!-- ============================================================ -->
    <div x-show="showCustomerModal" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeCustomerModal()">
            </div>

            <div class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle w-full max-w-6xl">
                <!-- Modal Header -->
                <div class="bg-white px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Customer Reward Details</h3>
                    <button @click="closeCustomerModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
<div class="px-6 py-4">
    <div x-show="selectedCustomer && customerDetails" class="space-y-6">
        <!-- Customer Information -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Customer</label>
                    <p class="font-medium text-gray-900"
                        x-text="(selectedCustomer?.first_name || '') + ' ' + (selectedCustomer?.last_name || '')">
                    </p>
                    <p class="text-sm text-gray-500" x-text="selectedCustomer?.email"></p>
                </div>
                <div>
                    <label class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Contact</label>
                    <p class="font-medium text-gray-900" x-text="selectedCustomer?.contact_no || 'N/A'"></p>
                </div>
                <div>
                    <label class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Total Bookings</label>
                    <p class="font-medium text-gray-900" x-text="customerDetails?.total_bookings || 0"></p>
                </div>
            </div>
        </div>

        <!-- Reward Tabs -->
        <div>
            <div class="border-b border-gray-200 mb-4">
                <nav class="-mb-px flex space-x-8">
                    <button @click="activeRewardTab = 'pending'"
                        :class="activeRewardTab === 'pending' ?
                            'border-[#7F5539] text-[#7F5539]' :
                            'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                        In Progress
                        <span x-show="getInProgressCount() > 0"
                            class="ml-2 px-2 py-0.5 text-xs rounded-full bg-[#7F5539]/20 text-[#7F5539]"
                            x-text="getInProgressCount()"></span>
                    </button>
                    <button @click="activeRewardTab = 'completed'"
                        :class="activeRewardTab === 'completed' ?
                            'border-[#7F5539] text-[#7F5539]' :
                            'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                        Completed
                        <span x-show="getCompletedCount() > 0"
                            class="ml-2 px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-800"
                            x-text="getCompletedCount()"></span>
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div>
                <!-- In Progress Tab -->
                <div x-show="activeRewardTab === 'pending'" class="space-y-4">
                    <div x-show="getInProgressRewards().length > 0">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <template x-for="progress in getInProgressRewards()"
                                :key="progress.loyalty_tier.id">
                                <div class="border border-gray-200 rounded-lg p-4 bg-white h-full flex flex-col hover:shadow-md transition-shadow duration-200">
                                    <!-- Header -->
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="flex-1">
                                            <h5 class="font-semibold text-gray-800 text-sm line-clamp-2"
                                                x-text="progress.loyalty_tier.reward_description || progress.loyalty_tier.tier_name"></h5>
                                            <div class="flex flex-wrap items-center gap-2 mt-1">
                                                <span class="text-xs px-2 py-1 rounded-full"
                                                    :class="progress.loyalty_tier.booking_type === 0 ?
                                                        'bg-blue-100 text-blue-800' :
                                                        'bg-purple-100 text-purple-800'"
                                                    x-text="progress.loyalty_tier.booking_type === 0 ? 'Streak' : 'Frequent'"></span>
                                                <span class="text-xs text-gray-600"
                                                    x-text="progress.loyalty_tier.branch ? progress.loyalty_tier.branch.branch_name : 'All Branches'">
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Reward Type Badge -->
                                    <div class="mb-2">
                                        <span class="text-xs px-2 py-1 rounded-full bg-[#4A2C1D]/10 text-[#7F5539]"
                                            x-text="progress.reward_type_label || 'Custom'"></span>
                                        <span x-show="progress.value_display" 
                                            class="text-xs font-medium text-green-600 ml-2"
                                            x-text="progress.value_display"></span>
                                    </div>

                                    <!-- Progress -->
                                    <div class="space-y-2 flex-grow">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">Progress</span>
                                            <span class="text-sm font-bold text-gray-900"
                                                x-text="Math.min(progress.booking_count, progress.booking_required) + '/' + progress.booking_required"></span>
                                        </div>

                                        <!-- Progress Bar -->
                                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                                            <div class="h-2.5 rounded-full transition-all duration-500"
                                                :class="progress.progress_percentage >= 75 ?
                                                    'bg-green-500' :
                                                    progress.progress_percentage >= 50 ?
                                                    'bg-yellow-500' :
                                                    'bg-red-500'"
                                                :style="'width: ' + Math.min(progress.progress_percentage || 0, 100) + '%'">
                                            </div>
                                        </div>

                                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                                            <span>Completion</span>
                                            <span class="font-medium" 
                                                :class="progress.progress_percentage >= 75 ? 'text-green-600' :
                                                        progress.progress_percentage >= 50 ? 'text-yellow-600' :
                                                        'text-red-600'"
                                                x-text="Math.round(progress.progress_percentage || 0) + '%'"></span>
                                        </div>
                                    </div>

                                    <!-- Status Badge -->
                                    <div class="mt-3 pt-3 border-t border-gray-200">
                                        <div class="flex items-center justify-center">
                                            <div class="px-3 py-1.5 rounded-full border"
                                                :class="progress.progress_percentage >= 100 && progress.booking_required > 0 ?
                                                    'bg-green-100 border-green-200' :
                                                    'bg-yellow-100 border-yellow-200'">
                                                <div class="flex items-center space-x-2">
                                                    <svg x-show="progress.progress_percentage < 100 || progress.booking_required === 0" 
                                                        class="w-4 h-4 text-yellow-600 animate-pulse" 
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <svg x-show="progress.progress_percentage >= 100 && progress.booking_required > 0" 
                                                        class="w-4 h-4 text-green-600" 
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span class="text-sm font-medium"
                                                        :class="progress.progress_percentage >= 100 && progress.booking_required > 0 ?
                                                            'text-green-700' :
                                                            'text-yellow-700'"
                                                        x-text="progress.progress_percentage >= 100 && progress.booking_required > 0 ?
                                                            'Ready to Claim' :
                                                            'In Progress'">
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div x-show="getInProgressRewards().length === 0"
                        class="text-center text-gray-400 py-8 border-2 border-dashed border-gray-300 rounded-lg">
                        <svg class="mx-auto h-12 w-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                        <p>No in-progress rewards for this customer.</p>
                    </div>
                </div>

                <!-- Completed Tab -->
                <div x-show="activeRewardTab === 'completed'" class="space-y-4">
                    <!-- Claimed Rewards -->
                    <div x-show="getClaimedRewards().length > 0">
                        <h5 class="text-md font-semibold text-gray-700 mb-3">Claimed Rewards</h5>
                        <div class="space-y-3">
                            <template x-for="progress in getClaimedRewards()"
                                :key="progress.loyalty_tier.id">
                                <div class="border border-green-200 rounded-lg p-4 bg-green-50">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h5 class="font-semibold text-gray-800"
                                                x-text="progress.loyalty_tier.reward_description || progress.loyalty_tier.tier_name"></h5>
                                            <p class="text-sm text-gray-600"
                                                x-text="progress.loyalty_tier.branch ? 'Branch: ' + progress.loyalty_tier.branch.branch_name : 'All Branches'">
                                            </p>
                                        </div>
                                        <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-800">
                                            Claimed
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-600 space-y-1">
                                        <p>Earned on: <span x-text="formatDate(progress.customer_reward.date_created)"></span></p>
                                        <p class="text-green-600 font-medium">✓ Reward has been claimed</p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Redeemed Rewards -->
                    <div x-show="getRedeemedRewards().length > 0">
                        <h5 class="text-md font-semibold text-gray-700 mb-3 mt-6">Redeemed Rewards</h5>
                        <div class="space-y-3">
                            <template x-for="progress in getRedeemedRewards()"
                                :key="progress.loyalty_tier.id">
                                <div class="border border-purple-200 rounded-lg p-4 bg-purple-50">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h5 class="font-semibold text-gray-800"
                                                x-text="progress.loyalty_tier.reward_description || progress.loyalty_tier.tier_name"></h5>
                                            <p class="text-sm text-gray-600"
                                                x-text="progress.loyalty_tier.branch ? 'Branch: ' + progress.loyalty_tier.branch.branch_name : 'All Branches'">
                                            </p>
                                        </div>
                                        <span class="text-xs px-2 py-1 rounded-full bg-purple-100 text-purple-800">
                                            Redeemed
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-600 space-y-1">
                                        <p>Redeemed on: <span x-text="formatDate(progress.customer_reward.redeemed_at)"></span></p>
                                        <p class="text-purple-600 font-medium">✓ Reward has been redeemed</p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Expired Rewards -->
                    <div x-show="getExpiredRewards().length > 0">
                        <h5 class="text-md font-semibold text-gray-700 mb-3 mt-6">Expired Rewards</h5>
                        <div class="space-y-3">
                            <template x-for="progress in getExpiredRewards()"
                                :key="progress.loyalty_tier.id">
                                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h5 class="font-semibold text-gray-800"
                                                x-text="progress.loyalty_tier.reward_description || progress.loyalty_tier.tier_name"></h5>
                                            <p class="text-sm text-gray-600"
                                                x-text="progress.loyalty_tier.branch ? 'Branch: ' + progress.loyalty_tier.branch.branch_name : 'All Branches'">
                                            </p>
                                        </div>
                                        <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-800">
                                            Expired
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-600 space-y-1">
                                        <p>Earned on: <span x-text="formatDate(progress.customer_reward.date_created)"></span></p>
                                        <p class="text-gray-600 font-medium">⚠️ Reward has expired</p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div x-show="getCompletedRewards().length === 0"
                        class="text-center text-gray-400 py-8 border-2 border-dashed border-gray-300 rounded-lg">
                        <svg class="mx-auto h-12 w-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                        <p>No completed rewards for this customer.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div x-show="customerDetails?.customer?.bookings && customerDetails.customer.bookings.length > 0">
            <h4 class="text-lg font-semibold text-gray-900 mb-3">Recent Bookings</h4>
            <div class="scrollable-table-container">
                <table class="scrollable-table divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Booking Ref</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Branch</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <template x-for="booking in (customerDetails?.customer?.bookings || []).slice(0, 5)"
                            :key="booking.id">
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900" x-text="booking.booking_ref_no"></td>
                                <td class="px-4 py-3 text-sm text-gray-600" x-text="booking.branch?.branch_name || 'N/A'"></td>
                                <td class="px-4 py-3 text-sm text-gray-600" x-text="booking.service_name?.service_name || 'N/A'"></td>
                                <td class="px-4 py-3 text-sm text-gray-600" x-text="formatDate(booking.date_start)"></td>
                                <td class="px-4 py-3 text-sm text-center">
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                        Completed
                                    </span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="selectedCustomer && !customerDetails" class="text-center py-8">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#7F5539] mx-auto"></div>
        <p class="mt-4 text-gray-600">Loading customer details...</p>
    </div>
</div>

                <!-- Modal Footer -->
                <div class="bg-gray-50 px-6 py-3 border-t border-gray-200 flex justify-end">
                    <button @click="closeCustomerModal()"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors text-sm font-medium">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('customerRewardTracking', () => ({
            // Data from backend - using JSON parsed data
            customers: @json($customersJson ?? '[]'),
            pagination: @json($paginationJson ?? '{}'),
            stats: @json($statsJson ?? '{}'),
            branches: @json($branchesJson ?? '[]'),
            loyaltyTiers: @json($loyaltyTiersJson ?? '[]'),
            
            // State
            searchQuery: '{{ request('search', '') }}',
            currentFilters: {
                branch_id: '{{ request('branch_id', '') }}',
                min_bookings: '{{ request('min_bookings', '') }}',
            },
            isLoading: false,
            showCustomerModal: false,
            selectedCustomer: null,
            customerDetails: null,
            activeRewardTab: 'pending',
            paginationLinks: [],

            init() {
                // Parse JSON data if they are strings
                if (typeof this.customers === 'string') {
                    this.customers = JSON.parse(this.customers);
                }
                if (typeof this.pagination === 'string') {
                    this.pagination = JSON.parse(this.pagination);
                }
                if (typeof this.stats === 'string') {
                    this.stats = JSON.parse(this.stats);
                }
                if (typeof this.branches === 'string') {
                    this.branches = JSON.parse(this.branches);
                }
                if (typeof this.loyaltyTiers === 'string') {
                    this.loyaltyTiers = JSON.parse(this.loyaltyTiers);
                }
                
                this.updatePaginationLinks();
                
                // If no customers, try to refresh
                if (!this.customers || this.customers.length === 0) {
                    this.refreshData();
                }
            },

            refreshData() {
                this.isLoading = true;
                const url = window.location.pathname + '?ajax=true';
                
                fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.customers = data.data || [];
                        this.pagination = data.pagination || {};
                        this.stats = data.stats || {};
                        this.branches = data.branches || [];
                        this.loyaltyTiers = data.loyalty_tiers || [];
                        this.updatePaginationLinks();
                    } else {
                        console.error('Failed to load data:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error refreshing data:', error);
                })
                .finally(() => {
                    this.isLoading = false;
                });
            },

            get hasActiveFilters() {
                return Object.values(this.currentFilters).some(value => value !== '') || this.searchQuery;
            },

            get activeFilters() {
                const filters = [];
                if (this.currentFilters.branch_id) {
                    const branch = this.branches.find(b => b.id == this.currentFilters.branch_id);
                    if (branch) {
                        filters.push({ key: 'branch_id', label: `Branch: ${branch.branch_name}` });
                    }
                }
                if (this.currentFilters.min_bookings) {
                    filters.push({ key: 'min_bookings', label: `Min Bookings: ${this.currentFilters.min_bookings}` });
                }
                if (this.searchQuery) {
                    filters.push({ key: 'search', label: `Search: ${this.searchQuery}` });
                }
                return filters;
            },

            async performSearch() {
                this.currentFilters.search = this.searchQuery;
                await this.applyFilters(this.currentFilters);
            },

            async applyFilters(filters) {
                this.isLoading = true;
                this.currentFilters = { ...filters };

                try {
                    const queryParams = new URLSearchParams();
                    Object.entries(this.currentFilters).forEach(([key, value]) => {
                        if (value) queryParams.append(key, value);
                    });
                    queryParams.append('ajax', 'true');

                    const response = await fetch(`?${queryParams.toString()}`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await response.json();
                    if (data.success) {
                        this.customers = data.data || [];
                        this.pagination = data.pagination || {};
                        this.stats = data.stats || {};
                        this.branches = data.branches || [];
                        this.loyaltyTiers = data.loyalty_tiers || [];
                        this.updatePaginationLinks();
                    }
                } catch (error) {
                    console.error('Error applying filters:', error);
                } finally {
                    this.isLoading = false;
                }
            },

            async clearAllFilters() {
                this.searchQuery = '';
                this.currentFilters = {
                    branch_id: '',
                    min_bookings: ''
                };
                await this.applyFilters(this.currentFilters);
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

            async changePage(page) {
                if (page < 1 || page > this.pagination.last_page) return;

                this.isLoading = true;

                try {
                    const queryParams = new URLSearchParams();
                    Object.entries(this.currentFilters).forEach(([key, value]) => {
                        if (value) queryParams.append(key, value);
                    });
                    queryParams.append('page', page);
                    queryParams.append('ajax', 'true');

                    const response = await fetch(`?${queryParams.toString()}`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await response.json();
                    if (data.success) {
                        this.customers = data.data || [];
                        this.pagination = data.pagination || {};
                        this.stats = data.stats || {};
                        this.branches = data.branches || [];
                        this.loyaltyTiers = data.loyalty_tiers || [];
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

            async viewCustomerDetails(customerId) {
                try {
                    this.selectedCustomer = this.customers.find(c => c.id === customerId);
                    this.customerDetails = null;
                    this.showCustomerModal = true;
                    document.body.classList.add('modal-open');

                    const url = `/sub_one/customer-rewards/${customerId}/progress`;
                    
                    const response = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await response.json();
                    if (data.success && data.data) {
                        this.customerDetails = data.data;
                        this.activeRewardTab = 'pending';
                    } else {
                        throw new Error(data.message || 'Failed to load customer details');
                    }
                } catch (error) {
                    console.error('Error loading customer details:', error);
                    this.customerDetails = false;
                    alert('Failed to load customer details: ' + error.message);
                }
            },

            closeCustomerModal() {
                this.showCustomerModal = false;
                this.selectedCustomer = null;
                this.customerDetails = null;
                document.body.classList.remove('modal-open');
            },

            // ============================================================
            // REWARD FILTERING BASED ON ACTUAL REWARDS
            // ============================================================

            // Get in-progress rewards (not yet earned)
            getInProgressRewards() {
                if (!this.customerDetails?.progress) return [];
                return this.customerDetails.progress.filter(progress => {
                    return !progress.is_earned;
                });
            },

            getInProgressCount() {
                return this.getInProgressRewards().length;
            },

            // Get completed rewards (earned and claimed)
            getCompletedRewards() {
                if (!this.customerDetails?.progress) return [];
                return this.customerDetails.progress.filter(progress => {
                    return progress.is_earned && progress.customer_reward;
                });
            },

            getCompletedCount() {
                return this.getCompletedRewards().length;
            },

            // Get claimed rewards (claim_status = 1)
            getClaimedRewards() {
                if (!this.customerDetails?.progress) return [];
                return this.customerDetails.progress.filter(progress => {
                    return progress.is_earned && 
                           progress.customer_reward && 
                           progress.is_claimed === true;
                });
            },

            // Get redeemed rewards (redemption_status = 'redeemed')
            getRedeemedRewards() {
                if (!this.customerDetails?.progress) return [];
                return this.customerDetails.progress.filter(progress => {
                    return progress.is_earned && 
                           progress.customer_reward && 
                           progress.is_redeemed === true;
                });
            },

            // Get expired rewards
            getExpiredRewards() {
                if (!this.customerDetails?.progress) return [];
                return this.customerDetails.progress.filter(progress => {
                    return progress.is_earned && 
                           progress.customer_reward && 
                           progress.is_expired === true;
                });
            },

            // Get available rewards (claimed but not redeemed and not expired)
            getAvailableRewards() {
                if (!this.customerDetails?.progress) return [];
                return this.customerDetails.progress.filter(progress => {
                    return progress.is_earned && 
                           progress.customer_reward && 
                           progress.is_claimed === true &&
                           progress.is_redeemed === false &&
                           progress.is_expired === false;
                });
            },

            // Get reward status text
            getRewardStatusText(progress) {
                if (!progress.is_earned) return 'In Progress';
                if (progress.is_expired) return 'Expired';
                if (progress.is_redeemed) return 'Redeemed';
                if (progress.is_claimed) return 'Claimed';
                return 'Earned';
            },

            // Get reward status badge class
            getRewardStatusClass(progress) {
                if (!progress.is_earned) return 'bg-yellow-100 text-yellow-800';
                if (progress.is_expired) return 'bg-gray-100 text-gray-800';
                if (progress.is_redeemed) return 'bg-purple-100 text-purple-800';
                if (progress.is_claimed) return 'bg-green-100 text-green-800';
                return 'bg-blue-100 text-blue-800';
            },

            // ============================================================
            // PROCESS REWARDS
            // ============================================================

            async processRewards() {
                if (!confirm('This will check all customers and create rewards for those who qualify. Continue?')) {
                    return;
                }

                try {
                    const response = await fetch('{{ route("sub_one.customer_rewards.process") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();
                    if (data.success) {
                        alert('✅ ' + data.message);
                        await this.refreshData();
                    } else {
                        throw new Error(data.message);
                    }
                } catch (error) {
                    alert('❌ Error processing rewards: ' + error.message);
                }
            },

            // ============================================================
            // UTILITY FUNCTIONS
            // ============================================================

            formatDate(dateString) {
                if (!dateString) return 'N/A';
                try {
                    const date = new Date(dateString);
                    return date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                } catch (e) {
                    return 'N/A';
                }
            }
        }));
    });
</script>
@endsection