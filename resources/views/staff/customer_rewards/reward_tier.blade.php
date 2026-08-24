@extends('layouts.app')

@section('content')
    
    <div x-data="rewardTierData()" x-init="init()" class="p-4">
        <div>
            <!-- Header -->
            <h1 class="text-2xl font-bold text-gray-900 mb-8 text-center">Reward Tiers</h1>
        </div>
        
        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Table Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-4 space-y-4 lg:space-y-0">
                    <!-- Left: Header + Add Button -->
                    <div class="flex items-center justify-between w-full lg:w-auto lg:justify-start">
                        <!-- Header -->
                        <h2 class="text-lg font-semibold text-gray-900 mr-4">Reward Tier Records</h2>

                        <!-- Add Reward Tier Button (visible on mobile/tablet) -->
                        <button @click="openAddModal()"
                            class="lg:hidden inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] flex-shrink-0">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add
                        </button>
                    </div>

                    <!-- Right: Search + Filter + Add Button (desktop) -->
                    <div class="flex items-center space-x-3 w-full lg:w-auto">
                        <!-- Search Input -->
                        <div class="relative flex-1">
                            <input type="text" x-model="searchQuery" @input.debounce.500ms="performSearch()"
                                placeholder="Search reward tiers..."
                                class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] w-full">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Filter and Add Buttons -->
                        <div class="flex items-center space-x-3 flex-shrink-0">
                            <!-- Filter Button -->
                            <button @click="showFilters = true; addBodyClass()"
                                class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                                </svg>
                            </button>

                            <!-- Add Reward Tier Button (desktop - hidden on mobile) -->
                            <button @click="openAddModal()"
                                class="hidden lg:inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Add Reward Tier
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Active Filters Badge -->
                <div x-show="hasActiveFilters" class="flex items-center justify-end space-x-2 mt-3">
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Booking Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Bookings Required</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Valid Period</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Reward Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Created Date</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="tier in rewardTiers" :key="tier.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <!-- 1. Branch -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"
                                        x-text="tier.branch?.branch_name || 'All Branches'"></div>
                                </td>
                                <!-- 2. Booking Type -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="tier.booking_type === 0 ?
                                            'bg-blue-100 text-blue-800' :
                                            'bg-purple-100 text-purple-800'">
                                        <span x-text="getBookingTypeLabel(tier.booking_type)"></span>
                                    </span>
                                </td>
                                <!-- 3. Bookings Required -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900" x-text="tier.booking_required"></div>
                                </td>
                                <!-- Valid Period -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <template x-if="tier.date_start && tier.date_end">
                                            <div>
                                                <div
                                                    x-text="formatDate(tier.date_start) + ' to ' + formatDate(tier.date_end)">
                                                </div>
                                                <div class="text-xs text-gray-500"
                                                    x-text="formatTimeRange(tier.start_time, tier.end_time)"></div>
                                            </div>
                                        </template>
                                        <template x-if="!tier.date_start || !tier.date_end">
                                            <div class="text-gray-400 italic">No date restriction</div>
                                        </template>
                                    </div>
                                </td>
                                <!-- 4. Reward Description -->
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900" x-text="tier.reward_description"></div>
                                </td>
                                <!-- 5. Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                                        :class="tier.reward_tier_status ? 'bg-green-100 text-green-800' :
                                            'bg-gray-100 text-gray-800'">
                                        <span x-text="tier.reward_tier_status ? 'Available' : 'Unavailable'"></span>
                                    </span>
                                </td>
                                <!-- 6. Created Date -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" x-text="formatDate(tier.date_created)"></div>
                                </td>
                                <!-- 7. Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex justify-center space-x-2">
                                        <!-- Disabled Edit button when in use (optional tooltip) -->
                                        <div x-show="tier.is_in_use" class="relative group">
                                            <button disabled
                                                class="px-3 py-1 bg-gray-300 text-gray-500 rounded-lg cursor-not-allowed text-sm font-medium">
                                                Edit
                                            </button>

                                            <!-- Tooltip -->
                                            <div
                                                class="absolute top-1/2 right-full transform -translate-y-1/2 mr-2 hidden group-hover:block w-auto z-50">

                                                <div class="bg-gray-800 text-white text-xs rounded py-1 px-2 shadow-lg">
                                                    Cannot edit: This tier is already assigned to customers
                                                </div>

                                                <!-- Tooltip arrow -->
                                                <div
                                                    class="w-2 h-2 bg-gray-800 transform rotate-45 absolute top-1/2 -translate-y-1/2 -right-1">
                                                </div>
                                            </div>
                                        </div>


                                        <!-- Edit button - hidden if tier is in use -->
                                        <button x-show="!tier.is_in_use" @click="openEditModal(tier)"
                                            class="px-3 py-1 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm font-medium">
                                            Edit
                                        </button>

                                        <!-- Status toggle button - always visible -->
                                        <button @click="toggleStatus(tier.id, !tier.reward_tier_status)"
                                            class="px-3 py-1 rounded-lg transition-colors text-sm font-medium"
                                            :class="tier.reward_tier_status ?
                                                'bg-yellow-500 text-white hover:bg-yellow-600' :
                                                'bg-green-500 text-white hover:bg-green-600'"
                                            x-text="tier.reward_tier_status ? 'Deactivate' : 'Activate'">
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <tr x-show="!rewardTiers.length">
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <h5 class="text-sm font-medium text-gray-900"
                                        x-text="hasActiveFilters ? 'No reward tiers match your filters' : 'No reward tiers found'">
                                    </h5>
                                    <p class="text-sm text-gray-500"
                                        x-text="hasActiveFilters ? 'Try adjusting your filters.' : 'Create your first reward tier to get started.'">
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

        <!-- Filter Modal -->
        <div x-show="showFilters" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showFilters = false"></div>
                <!-- Keep the same max-w-md across all screen sizes -->
                <div
                    class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl sm:my-8 sm:align-middle max-w-md w-full sm:p-6">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Filter Search</h3>

                        <div x-data="filterState()">
                            <!-- Booking Type -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Booking Type</label>
                                    <select x-model="filters.booking_type"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                        <option value="" disabled>All Booking Types</option>
                                        <option value="0">Streak</option>
                                        <option value="1">Frequent</option>
                                    </select>
                                </div>

                                <!-- Status -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select x-model="filters.reward_tier_status"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                        <option value="" disabled>All Status</option>
                                        <option value="1">Available</option>
                                        <option value="0">Unavailable</option>
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

        <!-- Add/Edit Modal -->
        <div x-show="showTierModal" x-cloak x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 z-[9999] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                    @click="showTierModal = false; $event.stopPropagation()" x-show="showTierModal"
                    x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showTierModal" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div>
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full"
                            :class="isEditing ? 'bg-blue-100' : 'bg-green-100'">
                            <svg class="h-6 w-6" :class="isEditing ? 'text-blue-600' : 'text-green-600'" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-lg leading-6 font-medium text-gray-900"
                                x-text="isEditing ? 'Edit Reward Tier' : 'Add New Reward Tier'"></h3>
                            <div class="mt-4 space-y-4">
                                <!-- Branch Selection -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 text-left mb-2">Branch</label>
                                    <select x-model="tierForm.branch_id"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                        <option value="" disabled>All Branches</option>
                                        <template x-for="branch in branches" :key="branch.id">
                                            <option :value="branch.id" x-text="branch.branch_name"></option>
                                        </template>
                                    </select>
                                </div>

                                <!-- Booking Type -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 text-left mb-2">Booking
                                        Type</label>
                                    <select x-model="tierForm.booking_type"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                        <option value="0">Streak</option>
                                        <option value="1">Frequent</option>
                                    </select>
                                </div>

                                <!-- Bookings Required -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 text-left mb-2">Bookings
                                        Required</label>
                                    <input type="number" x-model="tierForm.booking_required" min="1"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2"
                                        placeholder="Enter number of bookings required">
                                </div>

                                <!-- Date Range Section -->
                                <div class="border-t pt-4">
                                    <h4 class="text-sm font-medium text-gray-700 mb-3">Valid Period (Optional)</h4>

                                    <!-- Date Start -->
                                    <div class="mb-3">
                                        <label class="block text-sm text-gray-600 mb-1">Start Date</label>
                                        <input type="date" x-model="tierForm.date_start"
                                            class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                    </div>

                                    <!-- Date End -->
                                    <div class="mb-3">
                                        <label class="block text-sm text-gray-600 mb-1">End Date</label>
                                        <input type="date" x-model="tierForm.date_end"
                                            class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                    </div>

                                    <!-- Time Range -->
                                    <div class="mb-3">
                                        <label class="block text-sm text-gray-600 mb-1">Time Range (Optional)</label>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs text-gray-500 mb-1">Start Time</label>
                                                <input type="time" x-model="tierForm.start_time"
                                                    class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-500 mb-1">End Time</label>
                                                <input type="time" x-model="tierForm.end_time"
                                                    class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                            </div>
                                        </div>
                                    </div>

                                    <p class="text-xs text-gray-500 mt-2">
                                        Leave dates empty if the reward tier has no date restrictions.
                                        If only one date is provided, the reward tier will be valid only on that specific
                                        day.
                                    </p>
                                </div>

                                <!-- Reward Description -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 text-left mb-2">Reward
                                        Description</label>
                                    <textarea x-model="tierForm.reward_description" rows="3"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2"
                                        placeholder="Describe the reward..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 flex space-x-3">
                        <button type="button" @click="showTierModal = false; $event.stopPropagation()"
                            class="flex-1 inline-flex justify-center px-4 py-2 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            Cancel
                        </button>
                        <button type="button" @click="submitTierForm()"
                            class="flex-1 inline-flex justify-center px-4 py-2 border border-transparent text-base font-medium rounded-lg text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539] transition-colors duration-200">
                            <span x-text="isEditing ? 'Update' : 'Create'"></span>
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
                    booking_type: '{{ request('booking_type', '') }}',
                    reward_tier_status: '{{ request('reward_tier_status', '') }}',
                    branch_id: '{{ request('branch_id', '') }}',
                },
                branches: @json($branches),
                clearFilters() {
                    this.filters = {
                        booking_type: '',
                        reward_tier_status: '',
                        branch_id: '',
                    };
                },
                applyFilters() {
                    // Get the main component instance and call its applyFilters method
                    const mainComponent = Alpine.$data(document.querySelector(
                        '[x-data="rewardTierData()"]'));
                    mainComponent.applyFilters(this.filters);
                }
            }));

            // Main component
            Alpine.data('rewardTierData', () => ({
                // Initial state
                rewardTiers: @json($rewardTiers->items() ?? []),
                pagination: @json($rewardTiers->toArray()),
                branches: @json($branches),
                currentFilters: {
                    booking_type: '{{ request('booking_type', '') }}',
                    reward_tier_status: '{{ request('reward_tier_status', '') }}',
                    branch_id: '{{ request('branch_id', '') }}',
                },
                searchQuery: '{{ request('search', '') }}',
                showFilters: false,
                showTierModal: false,
                isEditing: false,
                currentTierId: null,
                tierForm: {
                    branch_id: '',
                    booking_type: 0,
                    booking_required: 1,
                    date_start: '',
                    date_end: '',
                    start_time: '',
                    end_time: '',
                    reward_description: '',
                    reward_tier_status: 1,
                },
                paginationLinks: [],
                isLoading: false,

                init() {
                    this.updatePaginationLinks();
                    this.updateActiveFilters();
                },

                // Computed properties
                get hasActiveFilters() {
                    return Object.values(this.currentFilters).some(value => value !== '') || this
                        .searchQuery;
                },

                get activeFilters() {
                    const filters = [];

                    if (this.currentFilters.search) {
                        filters.push({
                            key: 'search',
                            label: `Search: ${this.currentFilters.search}`
                        });
                    }

                    if (this.currentFilters.booking_type) {
                        filters.push({
                            key: 'booking_type',
                            label: `Type: ${this.getBookingTypeLabel(this.currentFilters.booking_type)}`
                        });
                    }

                    if (this.currentFilters.reward_tier_status) {
                        filters.push({
                            key: 'reward_tier_status',
                            label: `Status: ${this.currentFilters.reward_tier_status == 1 ? 'Active' : 'Inactive'}`
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
                getBookingTypeLabel(type) {
                    return type == 0 ? 'Streak' : 'Frequent';
                },

                formatDate(dateString) {
                    if (!dateString) return 'N/A';
                    const date = new Date(dateString);
                    return date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                },

                formatTimeRange(startTime, endTime) {
                    if (!startTime && !endTime) return '';

                    let result = '';
                    if (startTime) {
                        const start = startTime.substring(0, 5); // Format as HH:mm
                        result += start;
                    }

                    if (endTime) {
                        const end = endTime.substring(0, 5); // Format as HH:mm
                        if (result) {
                            result += ' - ' + end;
                        } else {
                            result = 'Until ' + end;
                        }
                    }

                    return result;
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
                            this.rewardTiers = data.data;
                            this.pagination = data.pagination;
                            this.updatePaginationLinks();
                            this.updateActiveFilters();
                        } else {
                            throw new Error(data.message || 'Filter application failed');
                        }
                    } catch (error) {
                        console.error('Error applying filters:', error);
                        this.showNotification('Failed to apply filters', 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                async clearAllFilters() {
                    this.isLoading = true;
                    this.showFilters = false;
                    this.searchQuery = '';
                    this.currentFilters = {
                        booking_type: '',
                        reward_tier_status: '',
                        branch_id: '',
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
                            this.rewardTiers = data.data;
                            this.pagination = data.pagination;
                            this.updatePaginationLinks();
                            this.updateActiveFilters();
                        } else {
                            throw new Error(data.message || 'Filter clearing failed');
                        }
                    } catch (error) {
                        console.error('Error clearing filters:', error);
                        this.showNotification('Failed to clear filters', 'error');
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
                            this.rewardTiers = data.data;
                            this.pagination = data.pagination;
                            this.updatePaginationLinks();
                        } else {
                            throw new Error(data.message || 'Pagination failed');
                        }
                    } catch (error) {
                        console.error('Error changing page:', error);
                        this.showNotification('Failed to change page', 'error');
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

                // Tier management methods
                openAddModal() {
                    this.isEditing = false;
                    this.currentTierId = null;
                    this.tierForm = {
                        branch_id: '',
                        booking_type: 0,
                        booking_required: 1,
                        date_start: '',
                        date_end: '',
                        start_time: '',
                        end_time: '',
                        reward_description: '',
                        reward_tier_status: 1,
                    };
                    this.showTierModal = true;
                },

                openEditModal(tier) {
    this.isEditing = true;
    this.currentTierId = tier.id;
    
    // ✅ Format time for input[type="time"] display (removes seconds if present)
    const formatTimeForDisplay = (timeString) => {
        if (!timeString) return '';
        // If time already has seconds, remove them for display
        if (timeString.includes(':')) {
            const parts = timeString.split(':');
            if (parts.length === 3) {
                return parts[0] + ':' + parts[1]; // Return HH:MM
            }
        }
        return timeString;
    };
    
    this.tierForm = {
        branch_id: tier.branch_id || '',
        booking_type: tier.booking_type.toString(),
        booking_required: tier.booking_required,
        date_start: tier.date_start || '',
        date_end: tier.date_end || '',
        start_time: formatTimeForDisplay(tier.start_time),
        end_time: formatTimeForDisplay(tier.end_time),
        reward_description: tier.reward_description,
        reward_tier_status: tier.reward_tier_status,
    };
    
    console.log('Form Data Set for Edit:', this.tierForm);
    this.showTierModal = true;
},

                async submitTierForm() {
    // Prevent the modal from closing
    event?.preventDefault();

    // Validate required fields
    if (!this.tierForm.reward_description.trim()) {
        this.showNotification('Reward description is required', 'error');
        return;
    }

    // Validate date range if provided
    if (this.tierForm.date_start && this.tierForm.date_end) {
        const startDate = new Date(this.tierForm.date_start);
        const endDate = new Date(this.tierForm.date_end);

        if (startDate > endDate) {
            this.showNotification('End date must be after start date', 'error');
            return;
        }
    }

    // Validate time range if provided
    if (this.tierForm.start_time && this.tierForm.end_time) {
        if (this.tierForm.start_time >= this.tierForm.end_time) {
            this.showNotification('End time must be after start time', 'error');
            return;
        }
    }

    try {
        const url = this.isEditing ?
            `/sub_two/reward_tiers/${this.currentTierId}` :
            '/sub_two/reward_tiers';

        const method = this.isEditing ? 'PATCH' : 'POST';

        console.log('Sending request:', {
            url: url,
            method: method,
            data: this.tierForm
        });

        // ✅ Format time to remove seconds (HH:MM:SS → HH:MM)
        const formatTimeForSubmission = (timeString) => {
            if (!timeString || timeString === '') return null;
            // Remove seconds if present
            return timeString.substring(0, 5); // Takes "HH:MM"
        };

        // ✅ Prepare data exactly like the store function does
        const formData = {
            branch_id: this.tierForm.branch_id || null,
            booking_type: parseInt(this.tierForm.booking_type),
            booking_required: parseInt(this.tierForm.booking_required),
            reward_description: this.tierForm.reward_description,
            date_start: this.tierForm.date_start || null,
            date_end: this.tierForm.date_end || null,
            // ✅ Format times to remove seconds
            start_time: formatTimeForSubmission(this.tierForm.start_time),
            end_time: formatTimeForSubmission(this.tierForm.end_time),
            reward_tier_status: this.tierForm.reward_tier_status || 1,
        };

        console.log('Formatted Data for Submission:', formData);

        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        console.log('Response status:', response.status);

        const data = await response.json();
        console.log('Response data:', data);

        if (data.success) {
            // Only close modal on success
            this.showTierModal = false;
            this.showNotification(
                this.isEditing ? 'Reward tier updated successfully!' :
                'Reward tier created successfully!',
                'success'
            );

            // Reload the data
            await this.applyFilters(this.currentFilters);
        } else {
            console.error('Server error:', data.message);
            throw new Error(data.message || 'Failed to save reward tier');
        }
    } catch (error) {
        console.error('Error saving reward tier:', error);
        this.showNotification(
            error.message || 'Failed to save reward tier',
            'error'
        );
        // Don't close modal on error
    }
},

                async toggleStatus(tierId, newStatus) {
                    if (!confirm(
                            `Are you sure you want to ${newStatus ? 'activate' : 'deactivate'} this reward tier?`
                        )) {
                        return;
                    }

                    try {
                        const response = await fetch(`/sub_two/reward_tiers/${tierId}/status`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                reward_tier_status: newStatus ? 1 : 0
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.showNotification(
                                `Reward tier ${newStatus ? 'activated' : 'deactivated'} successfully!`,
                                'success'
                            );

                            // Update local state
                            const tierIndex = this.rewardTiers.findIndex(t => t.id === tierId);
                            if (tierIndex !== -1) {
                                this.rewardTiers[tierIndex].reward_tier_status = newStatus ? 1 : 0;
                            }
                        } else {
                            throw new Error(data.message || 'Failed to update status');
                        }
                    } catch (error) {
                        console.error('Error updating tier status:', error);
                        this.showNotification(
                            error.message || 'Failed to update status',
                            'error'
                        );
                    }
                },

                showNotification(message, type = 'success') {
                    localStorage.setItem('toastType', type);
                    localStorage.setItem('toastMessage', message);
                    localStorage.setItem('toastStart', Date.now().toString());
                    localStorage.setItem('toastDuration', '10000');
                    localStorage.setItem('toastFade', '500');
                    localStorage.setItem('toastActive', 'true');

                    if (typeof window.showToastFromStorage === 'function') {
                        window.showToastFromStorage();
                    }
                }
            }));
        });
    </script>
@endsection