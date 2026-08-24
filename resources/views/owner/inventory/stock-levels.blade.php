@extends('layouts.app')

@section('title', 'Stock Levels')

@section('content')
    <div x-data="stockLevelsData()" x-init="init()" class="p-4">

        {{-- PAGE HEADER --}}
        <div class="flex items-center justify-between mb-8">
            <a href="{{ route('sub_one.inventory.index') }}"
                class="inline-flex items-center text-sm font-medium text-[#7F5539] hover:text-[#4A2C1D]">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-4 h-4 mr-1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Back to Inventory
            </a>
            <h1 class="text-2xl font-bold text-gray-900 text-center">Stock Levels</h1>
            <div class="w-32"></div> {{-- spacer to balance the back link --}}
        </div>

        {{-- SUMMARY STAT CARDS --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">

            {{-- Total Tracked Items --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Tracked Items</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stockLevels.length"></p>
                        <p class="text-xs text-gray-400 mt-1">products + ingredients</p>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-lg">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- New Arrivals --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">New Stocks</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="newArrivals.length"></p>
                        <button @click="openNewArrivalsModal()"
                            class="text-xs text-[#7F5539] underline hover:text-[#4A2C1D] mt-1"
                            x-text="newArrivals.length + ' item(s) · last 7 days'"></button>
                    </div>
                    <div class="p-3 bg-indigo-50 rounded-lg">
                        <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m0 0-6-6m6 6 6-6" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Low Stock Alerts --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Low Stock</p>
                        <p class="text-2xl font-bold" :class="lowStockItems.length ? 'text-red-600' : 'text-gray-900'" x-text="lowStockItems.length"></p>
                        <button @click="openLowStockModal()"
                            class="text-xs text-[#7F5539] underline hover:text-[#4A2C1D] mt-1"
                            x-text="lowStockItems.length + ' item(s) below threshold'"></button>
                    </div>
                    <div class="p-3 bg-red-50 rounded-lg">
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- STOCK LEVELS TABLE --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200" id="stockLevelsSection">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Stock Levels</h2>
                    <div class="flex items-center gap-3">
                        <div class="relative sm:w-72">
                            <input type="text" x-model="stockSearchQuery" @input="stockPage = 1"
                                placeholder="Search product or ingredient..."
                                class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] w-full text-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        {{-- FILTERS BUTTON --}}
                        <button @click="showFilters = true; addBodyClass()"
                            class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#7F5539] flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                            </svg>
                            Filters
                            <span x-show="activeFilterCount > 0"
                                class="ml-2 inline-flex items-center justify-center h-5 w-5 rounded-full bg-[#7F5539] text-white text-xs"
                                x-text="activeFilterCount"></span>
                        </button>
                    </div>
                </div>

                <!-- Active Filter Badges -->
                <div x-show="hasActiveFilters" class="flex items-center justify-end space-x-2 mb-4">
                    <template x-for="filter in activeFilters" :key="filter.key">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-[#4A2C1D]/10 text-[#7F5539]">
                            <span x-text="filter.label"></span>
                            <button @click="removeFilter(filter.key)" class="ml-1 hover:text-[#4A2C1D]">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </span>
                    </template>
                    <button @click="clearAllFilters()" class="text-sm text-[#4A2C1D] hover:text-[#7F5539] font-medium">
                        Clear all
                    </button>
                </div>

                <div class="flex gap-0 border-b border-gray-200 -mb-4">
                    <template x-for="t in stockTabs" :key="t.key">
                        <button @click="stockTab = t.key; stockPage = 1"
                            class="relative px-4 py-2 text-sm font-medium transition-colors focus:outline-none"
                            :class="stockTab === t.key ? 'text-[#7F5539] border-b-2 border-[#7F5539]' : 'text-gray-500 hover:text-gray-700'"
                            x-text="t.label"></button>
                    </template>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">On Hand</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Threshold</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(item, index) in pagedStockLevels" :key="item.item_type + '-' + item.id">
                            <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="item.name"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="item.item_type === 'ingredient' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
                                        x-text="item.item_type === 'ingredient' ? 'Ingredient' : 'Product'"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700" x-text="item.branch"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="item.quantity + ' ' + (item.unit || '')"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="item.threshold !== null ? item.threshold + ' ' + (item.unit || '') : '—'"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                        :class="{
                                            'bg-red-100 text-red-800':       item.status === 'low',
                                            'bg-yellow-100 text-yellow-800': item.status === 'medium',
                                            'bg-green-100 text-green-800':   item.status === 'high',
                                        }"
                                        x-text="{ low: 'Low Stock', medium: 'Normal', high: 'High' }[item.status] ?? 'Normal'"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <template x-if="item.is_low">
                                        <button @click="openRestockModal(item)"
                                            class="text-xs font-medium text-white bg-[#7F5539] hover:bg-[#4A2C1D] px-3 py-1.5 rounded-lg transition-colors">
                                            Restock
                                        </button>
                                    </template>
                                    <template x-if="!item.is_low">
                                        <span class="text-xs text-gray-300">—</span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="!pagedStockLevels.length">
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <p class="text-sm font-medium text-gray-900">No items found</p>
                                    <p class="text-sm text-gray-500">Try adjusting your search or filter.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div x-show="stockTotalPages > 1" class="px-4 sm:px-6 py-4 border-t border-gray-200">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="text-sm text-gray-700">
                        Showing <span x-text="stockShowingFrom"></span> to <span x-text="stockShowingTo"></span>
                        of <span x-text="filteredStockLevels.length"></span> entries
                    </div>
                    <div class="flex items-center gap-1">
                        <button @click="stockPage = Math.max(1, stockPage - 1)" :disabled="stockPage === 1"
                            class="px-3 py-1 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                        <template x-for="p in stockPageNumbers" :key="p">
                            <button @click="stockPage = p"
                                class="px-3 py-1 border rounded-lg text-sm font-medium"
                                :class="p === stockPage ? 'bg-[#7F5539] text-white border-[#7F5539]' : 'border-gray-300 text-gray-700 hover:bg-gray-50'"
                                x-text="p"></button>
                        </template>
                        <button @click="stockPage = Math.min(stockTotalPages, stockPage + 1)" :disabled="stockPage === stockTotalPages"
                            class="px-3 py-1 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═════════════════════════════════════
             FILTERS MODAL (Branch + Status)
        ═════════════════════════════════════════ --}}
        <div x-show="showFilters" x-cloak
            class="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center p-0 sm:p-4"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="fixed inset-0 bg-gray-500/75" @click="closeFilterModal()"></div>
            <div class="relative z-10 w-full sm:max-w-md bg-white rounded-t-2xl sm:rounded-xl shadow-xl flex flex-col max-h-[85vh]">
                <div class="sm:hidden flex justify-center pt-3 pb-1 flex-shrink-0"><div class="w-10 h-1 bg-gray-300 rounded-full"></div></div>
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <h3 class="text-lg font-semibold text-gray-900">Filter Stock Levels</h3>
                    <button @click="closeFilterModal()" class="text-gray-400 hover:text-gray-500 p-1 rounded-full hover:bg-gray-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div x-data="filterState()" class="flex-1 overflow-y-auto px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                        <select x-model="filters.branch_id"
                            class="block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]">
                            <option value="">All Branches</option>
                            @forelse ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                            @empty
                            @endforelse
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select x-model="filters.status"
                            class="block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]">
                            <option value="">All Status</option>
                            <option value="low">Low Stock</option>
                            <option value="medium">Normal</option>
                            <option value="high">High</option>
                        </select>
                    </div>

                    <div class="mt-6 flex space-x-3">
                        <button @click="clearFilters()"
                            class="flex-1 inline-flex justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50">
                            Clear
                        </button>
                        <button @click="applyFilters()"
                            class="flex-1 inline-flex justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-[#7F5539] hover:bg-[#4A2C1D]">
                            Apply Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═════════════════════════════════════
             NEW ARRIVALS MODAL
        ═════════════════════════════════════════ --}}
        <div x-show="showNewArrivalsModal" x-cloak
            class="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center p-0 sm:p-4"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="fixed inset-0 bg-gray-500/75" @click="closeNewArrivalsModal()"></div>
            <div class="relative z-10 w-full sm:max-w-lg bg-white rounded-t-2xl sm:rounded-xl shadow-xl flex flex-col max-h-[85vh]">
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <h3 class="text-lg font-semibold text-gray-900">New Arrivals — last 7 days</h3>
                    <button @click="closeNewArrivalsModal()" class="text-gray-400 hover:text-gray-500 p-1 rounded-full hover:bg-gray-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-4">
                    <div class="divide-y divide-gray-100">
                        <template x-for="(item, i) in newArrivals" :key="i">
                            <div class="flex items-center justify-between py-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-900" x-text="item.name"></p>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium mt-1"
                                        :class="item.item_type === 'ingredient' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
                                        x-text="item.item_type === 'ingredient' ? 'Ingredient' : 'Product'"></span>
                                </div>
                                <span class="text-sm font-semibold text-green-700" x-text="'+' + item.quantity + ' ' + (item.unit || '')"></span>
                            </div>
                        </template>
                        <p x-show="!newArrivals.length" class="text-sm text-gray-400 text-center py-8">No stock received in the last 7 days.</p>
                    </div>
                </div>
                <div class="flex-shrink-0 px-4 sm:px-6 py-4 border-t border-gray-200 flex justify-end bg-white">
                    <button @click="closeNewArrivalsModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Close</button>
                </div>
            </div>
        </div>

        {{-- ═════════════════════════════════════
             LOW STOCK MODAL
        ═════════════════════════════════════════ --}}
        <div x-show="showLowStockModal" x-cloak
            class="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center p-0 sm:p-4"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="fixed inset-0 bg-gray-500/75" @click="closeLowStockModal()"></div>
            <div class="relative z-10 w-full sm:max-w-lg bg-white rounded-t-2xl sm:rounded-xl shadow-xl flex flex-col max-h-[85vh]">
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <h3 class="text-lg font-semibold text-gray-900">Low Stock Alerts</h3>
                    <button @click="closeLowStockModal()" class="text-gray-400 hover:text-gray-500 p-1 rounded-full hover:bg-gray-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-4">
                    <div class="divide-y divide-gray-100">
                        <template x-for="(item, i) in lowStockItems" :key="i">
                            <div class="flex items-center justify-between py-3 gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate" x-text="item.name"></p>
                                    <p class="text-xs text-gray-500" x-text="item.quantity + ' / ' + item.threshold + ' ' + (item.unit || '') + ' on hand'"></p>
                                </div>
                                <button @click="openRestockModal(item)"
                                    class="flex-shrink-0 text-xs font-medium text-white bg-[#7F5539] hover:bg-[#4A2C1D] px-3 py-1.5 rounded-lg transition-colors">
                                    Restock
                                </button>
                            </div>
                        </template>
                        <p x-show="!lowStockItems.length" class="text-sm text-gray-400 text-center py-8">Everything is above its reorder threshold.</p>
                    </div>
                </div>
                <div class="flex-shrink-0 px-4 sm:px-6 py-4 border-t border-gray-200 flex justify-end bg-white">
                    <button @click="closeLowStockModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Close</button>
                </div>
            </div>
        </div>

        {{-- ═════════════════════════════════════
             RESTOCK FORM MODAL
             Submits a single-item Stock In directly from this page —
             no navigation to the Stock In page.
        ═════════════════════════════════════════ --}}
        <div x-show="showRestockModal" x-cloak
            class="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center p-0 sm:p-4"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="fixed inset-0 bg-gray-500/75" @click="closeRestockModal()"></div>
            <div class="relative z-10 w-full sm:max-w-md bg-white rounded-t-2xl sm:rounded-xl shadow-xl flex flex-col max-h-[85vh]"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full sm:translate-y-4 sm:opacity-0"
                x-transition:enter-end="translate-y-0 sm:opacity-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-y-0 sm:opacity-100" x-transition:leave-end="translate-y-full sm:translate-y-4 sm:opacity-0">

                <div class="sm:hidden flex justify-center pt-3 pb-1 flex-shrink-0"><div class="w-10 h-1 bg-gray-300 rounded-full"></div></div>

                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <h3 class="text-lg font-semibold text-gray-900">Restock Form</h3>
                    <button @click="closeRestockModal()" class="text-gray-400 hover:text-gray-500 p-1 rounded-full hover:bg-gray-100 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <template x-if="restockTarget">
                    <div class="flex-1 overflow-y-auto overscroll-contain px-6 py-4 space-y-4">

                        {{-- Item summary — read-only, item + branch are fixed to what was clicked --}}
                        <div class="bg-gray-50 rounded-lg border border-gray-200 p-3">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="restockTarget.item_type === 'ingredient' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
                                    x-text="restockTarget.item_type === 'ingredient' ? 'Ingredient' : 'Product'"></span>
                                <span class="text-xs text-gray-500" x-text="restockTarget.branch"></span>
                            </div>
                            <p class="text-sm font-medium text-gray-900" x-text="restockTarget.name"></p>
                            <p class="text-xs text-gray-500 mt-1"
                                x-text="'Currently ' + restockTarget.quantity + ' ' + (restockTarget.unit || '') + ' on hand' + (restockTarget.threshold !== null ? ' · threshold ' + restockTarget.threshold : '')"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Qty received</label>
                            <input type="number" x-model.number="restockForm.quantity" min="1"
                                class="block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Note (optional)</label>
                            <input type="text" x-model="restockForm.note" placeholder="e.g. batch A"
                                class="block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]">
                        </div>
                    </div>
                </template>

                <div class="flex-shrink-0 px-6 py-4 border-t border-gray-200 flex justify-end gap-3 bg-white">
                    <button @click="closeRestockModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</button>
                    <button @click="submitRestock()" :disabled="isRestocking"
                        class="px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-[#7F5539] hover:bg-[#4A2C1D] disabled:opacity-50 transition-colors">
                        <span x-text="isRestocking ? 'Saving...' : 'Confirm Restock'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- TOAST NOTIFICATION --}}
        <div x-show="showToast" x-cloak
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed bottom-6 right-6 z-[10000] max-w-sm w-full sm:w-auto">
            <div class="flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg text-sm font-medium"
                :class="toastType === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'">
                <svg x-show="toastType === 'success'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <svg x-show="toastType === 'error'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span x-text="toastMessage"></span>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('alpine:init', () => {

            Alpine.data('filterState', () => ({
                filters: { branch_id: '', status: '' },
                init() {
                    const main = Alpine.$data(document.querySelector('[x-data="stockLevelsData()"]'));
                    this.filters.branch_id = main.filterBranchId;
                    this.filters.status = main.filterStatus;
                },
                clearFilters() {
                    this.filters = { branch_id: '', status: '' };
                },
                applyFilters() {
                    const main = Alpine.$data(document.querySelector('[x-data="stockLevelsData()"]'));
                    main.filterBranchId = this.filters.branch_id;
                    main.filterStatus = this.filters.status;
                    main.stockPage = 1;
                    main.closeFilterModal();
                }
            }));

            Alpine.data('stockLevelsData', () => ({

                stockLevels:   @json($stockLevels ?? []),
                newArrivals:   @json($newArrivals ?? []),
                lowStockItems: @json($lowStockItems ?? []),
                branches:      @json($branches->map->only(['id', 'branch_name'])->values() ?? []),

                stockSearchQuery: '',
                stockTab: 'all',
                stockPage: 1,
                stockPerPage: 10,
                stockTabs: [
                    { key: 'all',        label: 'All' },
                    { key: 'product',    label: 'Products' },
                    { key: 'ingredient', label: 'Ingredients' },
                    { key: 'low',        label: 'Low stock' },
                ],

                // Filters
                showFilters: false,
                filterBranchId: '',
                filterStatus: '',

                showNewArrivalsModal: false,
                showLowStockModal:    false,

                // ── Restock Form Modal ──────────────────────────────
                showRestockModal: false,
                restockTarget:    null,
                restockForm:      { quantity: 1, note: '' },
                isRestocking:     false,

                showToast:    false,
                toastMessage: '',
                toastType:    'success',
                toastTimer:   null,

                get activeFilterCount() {
                    return (this.filterBranchId ? 1 : 0) + (this.filterStatus ? 1 : 0);
                },

                get hasActiveFilters() {
                    return this.filterBranchId !== '' || this.filterStatus !== '';
                },

                get activeFilters() {
                    const filters = [];
                    if (this.filterBranchId) {
                        const b = this.branches.find(b => b.id == this.filterBranchId);
                        filters.push({ key: 'branch_id', label: `Branch: ${b?.branch_name || this.filterBranchId}` });
                    }
                    if (this.filterStatus) {
                        const label = { low: 'Low Stock', medium: 'Normal', high: 'High' }[this.filterStatus] ?? this.filterStatus;
                        filters.push({ key: 'status', label: `Status: ${label}` });
                    }
                    return filters;
                },

                removeFilter(key) {
                    if (key === 'branch_id') this.filterBranchId = '';
                    if (key === 'status') this.filterStatus = '';
                    this.stockPage = 1;
                },

                clearAllFilters() {
                    this.filterBranchId = '';
                    this.filterStatus = '';
                    this.stockPage = 1;
                },

                closeFilterModal() { this.showFilters = false; this.removeBodyClass(); },

                get filteredStockLevels() {
                    let items = this.stockLevels;

                    if (this.stockTab === 'product' || this.stockTab === 'ingredient') {
                        items = items.filter(i => i.item_type === this.stockTab);
                    } else if (this.stockTab === 'low') {
                        items = items.filter(i => i.is_low);
                    }

                    if (this.filterBranchId) {
                        items = items.filter(i => i.branch_id == this.filterBranchId);
                    }
                    if (this.filterStatus) {
                        items = items.filter(i => i.status === this.filterStatus);
                    }

                    if (this.stockSearchQuery.trim()) {
                        const q = this.stockSearchQuery.trim().toLowerCase();
                        items = items.filter(i => i.name.toLowerCase().includes(q));
                    }

                    return items;
                },
                get stockTotalPages() {
                    return Math.max(1, Math.ceil(this.filteredStockLevels.length / this.stockPerPage));
                },
                get pagedStockLevels() {
                    const start = (this.stockPage - 1) * this.stockPerPage;
                    return this.filteredStockLevels.slice(start, start + this.stockPerPage);
                },
                get stockShowingFrom() {
                    return this.filteredStockLevels.length ? (this.stockPage - 1) * this.stockPerPage + 1 : 0;
                },
                get stockShowingTo() {
                    return Math.min(this.stockPage * this.stockPerPage, this.filteredStockLevels.length);
                },
                get stockPageNumbers() {
                    return Array.from({ length: this.stockTotalPages }, (_, i) => i + 1);
                },

                init() {
                    const params = new URLSearchParams(window.location.search);
                    const tab = params.get('tab');
                    const branchId = params.get('branch_id');

                    if (tab && this.stockTabs.some(t => t.key === tab)) {
                        this.stockTab = tab;
                    }

                    if (branchId) {
                        const validBranch = this.branches.find(b => b.id == branchId);
                        if (validBranch) {
                            this.filterBranchId = branchId;
                        }
                    }

                    if (tab || branchId) {
                        this.stockPage = 1;
                        this.$nextTick(() => {
                            document.getElementById('stockLevelsSection')
                                ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        });
                    }
                },

                addBodyClass()    { document.body.classList.add('modal-open'); },
                removeBodyClass() { document.body.classList.remove('modal-open'); },

                openNewArrivalsModal()  { this.showNewArrivalsModal = true; this.addBodyClass(); },
                closeNewArrivalsModal() { this.showNewArrivalsModal = false; this.removeBodyClass(); },
                openLowStockModal()     { this.showLowStockModal = true; this.addBodyClass(); },
                closeLowStockModal()    { this.showLowStockModal = false; this.removeBodyClass(); },

                showToastMsg(message, type = 'success') {
                    this.toastMessage = message;
                    this.toastType = type;
                    this.showToast = true;
                    clearTimeout(this.toastTimer);
                    this.toastTimer = setTimeout(() => { this.showToast = false; }, 3500);
                },

                // ── Restock Form Modal ──────────────────────────────
                // Opens an inline form instead of navigating to the Stock In
                // page. Item + branch are locked to whatever was clicked;
                // only quantity/note are editable.
                openRestockModal(item) {
                    this.restockTarget = item;
                    this.restockForm = { quantity: 1, note: '' };
                    this.showRestockModal = true;
                    this.addBodyClass();
                },
                closeRestockModal() {
                    this.showRestockModal = false;
                    this.restockTarget = null;
                    this.removeBodyClass();
                },

                async submitRestock() {
                    if (this.isRestocking || !this.restockTarget) return;

                    if (!this.restockForm.quantity || this.restockForm.quantity < 1) {
                        alert('Please enter a valid quantity.');
                        return;
                    }

                    this.isRestocking = true;
                    const item = this.restockTarget;

                    try {
                        const response = await fetch('{{ route('sub_one.inventory.stockIn') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                products: [{
                                    branch_id:     item.branch_id,
                                    item_type:     item.item_type,
                                    product_id:    item.item_type === 'product'    ? item.id : null,
                                    ingredient_id: item.item_type === 'ingredient' ? item.id : null,
                                    quantity:      this.restockForm.quantity,
                                    note:          this.restockForm.note,
                                }],
                            }),
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.closeRestockModal();
                            await this.refreshStockLevels();
                            this.showToastMsg('Restocked. Inventory updated.', 'success');
                        } else {
                            throw new Error(data.message || 'Failed to restock');
                        }
                    } catch (e) {
                        this.showToastMsg(e.message || 'Failed to restock. Please try again.', 'error');
                    } finally {
                        this.isRestocking = false;
                    }
                },

                async refreshStockLevels() {
                    try {
                        const response = await fetch(`?ajax=true`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                        if (!response.ok) throw new Error('Network error');
                        const data = await response.json();
                        if (data.success) {
                            this.stockLevels   = data.stock_levels;
                            this.newArrivals   = data.new_arrivals;
                            this.lowStockItems = data.low_stock_items;
                            this.stockPage = 1;
                        }
                    } catch (e) {
                        // Non-fatal — the restock already saved server-side,
                        // it just won't reflect here until a manual refresh.
                    }
                },

            }));
        });
    </script>

    <style>
        .modal-open { overflow: hidden; }
        .modal-open .overflow-y-auto { max-height: 65vh; overflow-y: auto; }
        .overflow-y-auto::-webkit-scrollbar { width: 6px; }
        .overflow-y-auto::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        .overflow-y-auto::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
        .overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #555; }
        [x-cloak] { display: none !important; }
    </style>
@endsection