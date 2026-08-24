@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
    <div x-data="inventoryData()" x-init="init()" class="p-4">

        {{-- PAGE HEADER --}}
        <div class="flex items-center justify-between mb-8">
            <div class="flex-1"></div>
            <h1 class="text-2xl font-bold text-gray-900 text-center">Inventory</h1>
            <div class="flex-1 flex justify-end items-center gap-3">
                <button @click="openStockOutModal()"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#7F5539]">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-4 h-4 mr-2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" />
                    </svg>
                    Stock Out
                </button>
                <button @click="openStockInModal()"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539]">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-4 h-4 mr-2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5 12 21m0 0-7.5-7.5M12 21V3" />
                    </svg>
                    Stock In
                </button>
            </div>
        </div>

        {{-- SUMMARY STAT CARDS --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Beginning Balance</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="summary.beginning_balance ?? '—'"></p>
                        <p class="text-xs text-gray-400 mt-1" x-text="'Start of ' + currentPeriodLabel"></p>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-lg">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                </div>
            </div>

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

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Ending Balance</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="summary.ending_balance ?? '—'"></p>
                        <p class="text-xs text-gray-400 mt-1">current on hand</p>
                    </div>
                    <div class="p-3 bg-yellow-50 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- STOCK LEVELS --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8" id="stockLevelsSection">
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
                                        <button @click="restockItem(item)"
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
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <p class="text-sm font-medium text-gray-900">No items found</p>
                                    <p class="text-sm text-gray-500">Try adjusting your search or filter.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

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

        {{-- MY STOCK REQUESTS (view-only history log — pending + approved + rejected) --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">My Stock Requests</h2>
                    <span x-show="pendingCount > 0"
                        class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-800 rounded-full"
                        x-text="pendingCount + ' pending'"></span>
                </div>

                {{-- Status tabs — doubles as a stock in/out history log --}}
                <div class="flex gap-0 border-b border-gray-200 -mb-4">
                    <template x-for="t in transactionTabs" :key="t.key">
                        <button @click="transactionTab = t.key"
                            class="relative px-4 py-2 text-sm font-medium transition-colors focus:outline-none"
                            :class="transactionTab === t.key ? 'text-[#7F5539] border-b-2 border-[#7F5539]' : 'text-gray-500 hover:text-gray-700'"
                            x-text="t.label"></button>
                    </template>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction No.</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(txn, index) in filteredTransactions" :key="txn.uuid">
                            <tr :class="[
                                    index % 2 === 0 ? 'bg-white' : 'bg-gray-50',
                                    txn.status === 'pending'  ? 'border-l-4 border-l-amber-400' :
                                    txn.status === 'approved' ? 'border-l-4 border-l-green-400' :
                                    txn.status === 'rejected' ? 'border-l-4 border-l-red-400' : ''
                                ]">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="txn.transaction_no"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" x-text="formatDate(txn.created_at)"></div>
                                    <div class="text-xs text-gray-400" x-text="formatTime(txn.created_at)"></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                        :class="txn.type === 'stock_in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                        x-text="(txn.type === 'stock_in' ? '+' : '−') + (txn.total_quantity ?? 0)"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-semibold text-[#7F5539]"
                                        x-text="txn.items_count"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                        :class="{
                                            'bg-amber-100 text-amber-800': txn.status === 'pending',
                                            'bg-green-100 text-green-800': txn.status === 'approved',
                                            'bg-red-100 text-red-800':     txn.status === 'rejected',
                                        }"
                                        x-text="statusLabel(txn.status)"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="relative group inline-block">
                                        <button @click="openViewModal(txn)" class="text-[#4A2C1D] hover:text-[#7F5539] transition-colors p-2 rounded-full hover:bg-gray-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                        </button>
                                        <span class="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">View Details</span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="!filteredTransactions.length">
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <p class="text-sm font-medium text-gray-900" x-text="transactionTab === 'all' ? 'Nothing here yet' : 'No ' + transactionTab + ' requests'"></p>
                                    <p class="text-sm text-gray-500">Your stock-in and stock-out declarations will show up here.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- FILTERS MODAL (Status only) --}}
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

        {{-- NEW ARRIVALS MODAL --}}
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

        {{-- LOW STOCK MODAL --}}
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
                                <button @click="restockItem(item); closeLowStockModal()"
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

        {{-- STOCK IN MODAL (no branch selector — staff's own branch is implicit) --}}
        <div x-show="showStockInModal" x-cloak
            class="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center p-0 sm:p-4"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="fixed inset-0 bg-gray-500/75" @click="closeStockInModal()"></div>
            <div class="relative z-10 w-full sm:max-w-2xl bg-white rounded-t-2xl sm:rounded-xl shadow-xl flex flex-col max-h-[92dvh] sm:max-h-[85vh]"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full sm:translate-y-4 sm:opacity-0"
                x-transition:enter-end="translate-y-0 sm:opacity-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-y-0 sm:opacity-100" x-transition:leave-end="translate-y-full sm:translate-y-4 sm:opacity-0">

                <div class="sm:hidden flex justify-center pt-3 pb-1 flex-shrink-0"><div class="w-10 h-1 bg-gray-300 rounded-full"></div></div>

                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">New Stock In Transaction</h3>
                        <p class="text-sm text-gray-500 mt-0.5">Add multiple items in one transaction</p>
                    </div>
                    <button @click="closeStockInModal()" class="text-gray-400 hover:text-gray-500 p-1 rounded-full hover:bg-gray-100 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="px-6 py-3 bg-amber-50 border-b border-amber-100 flex-shrink-0">
                    <p class="text-sm text-amber-700">This will be sent to the owner for approval before inventory is updated.</p>
                </div>

                <div class="flex-1 overflow-y-auto overscroll-contain px-4 sm:px-6 py-4">
                    <div class="space-y-3">
                        <template x-for="(item, index) in stockInItems" :key="index">
                            <div class="relative p-3 bg-gray-50 rounded-lg border border-gray-200 space-y-3">

                                <button @click.stop="removeStockInItem(index)"
                                    :class="stockInItems.length > 1 ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'"
                                    class="absolute top-3 right-3 z-10 text-red-400 hover:text-red-600 p-1.5 rounded-full hover:bg-red-50 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>

                                <div>
                                    <label class="block text-sm font-medium text-[#4A2C1D] mb-1">Item Type</label>
                                    <div class="flex rounded-lg border-2 border-[#7F5539] overflow-hidden">
                                        <button type="button"
                                            @click="item.item_type = 'product'; item.ingredient_id = '';"
                                            class="flex-1 py-2 text-sm font-medium transition-colors"
                                            :class="item.item_type === 'product' ? 'bg-[#7F5539] text-white' : 'bg-white text-[#7F5539] hover:bg-[#7F5539]/5'">
                                            Product
                                        </button>
                                        <button type="button"
                                            @click="item.item_type = 'ingredient'; item.product_id = '';"
                                            class="flex-1 py-2 text-sm font-medium transition-colors border-l-2 border-[#7F5539]"
                                            :class="item.item_type === 'ingredient' ? 'bg-[#7F5539] text-white' : 'bg-white text-[#7F5539] hover:bg-[#7F5539]/5'">
                                            Ingredient
                                        </button>
                                    </div>
                                </div>

                                <template x-if="item.item_type === 'product'">
                                    <div x-data="{
                                        open: false,
                                        get selectedName() {
                                            if (!item.product_id) return 'Select a product';
                                            const products = {{ Js::from($products->map->only(['id', 'product_name'])) }};
                                            const p = products.find(p => p.id == item.product_id);
                                            return p ? p.product_name : 'Select a product';
                                        },
                                        select(id) { item.product_id = id; this.open = false; }
                                    }" class="relative">
                                        <label class="block text-sm font-medium text-[#4A2C1D]">Product</label>
                                        <button @click="open = !open" @click.away="open = false" type="button"
                                            class="w-full flex justify-between items-center border-2 border-[#7F5539] rounded px-3 py-2 mt-1 text-left bg-white">
                                            <span x-text="selectedName" :class="{ 'text-gray-500': !item.product_id }"></span>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500" :class="{ 'rotate-180': open }"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                                        </button>
                                        <div x-show="open" x-transition class="absolute mt-1 w-full bg-white rounded-md shadow-lg z-20 border border-gray-200 max-h-60 overflow-y-auto" style="display:none;">
                                            @forelse ($products as $product)
                                                <a href="#" @click.prevent="select({{ $product->id }})" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ $product->product_name }}</a>
                                            @empty
                                                <span class="block px-4 py-2 text-sm text-gray-500">No products available</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </template>

                                <template x-if="item.item_type === 'ingredient'">
                                    <div x-data="{
                                        open: false,
                                        get selectedName() {
                                            if (!item.ingredient_id) return 'Select an ingredient';
                                            const ingredients = {{ Js::from($ingredients->map->only(['id', 'ingredient_name', 'unit'])) }};
                                            const i = ingredients.find(i => i.id == item.ingredient_id);
                                            return i ? i.ingredient_name + ' (' + i.unit + ')' : 'Select an ingredient';
                                        },
                                        select(id) { item.ingredient_id = id; this.open = false; }
                                    }" class="relative">
                                        <label class="block text-sm font-medium text-[#4A2C1D]">Ingredient</label>
                                        <button @click="open = !open" @click.away="open = false" type="button"
                                            class="w-full flex justify-between items-center border-2 border-[#7F5539] rounded px-3 py-2 mt-1 text-left bg-white">
                                            <span x-text="selectedName" :class="{ 'text-gray-500': !item.ingredient_id }"></span>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500" :class="{ 'rotate-180': open }"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                                        </button>
                                        <div x-show="open" x-transition class="absolute mt-1 w-full bg-white rounded-md shadow-lg z-20 border border-gray-200 max-h-60 overflow-y-auto" style="display:none;">
                                            @forelse ($ingredients as $ingredient)
                                                <a href="#" @click.prevent="select({{ $ingredient->id }})" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    {{ $ingredient->ingredient_name }} <span class="text-gray-400 text-xs">({{ $ingredient->unit }})</span>
                                                </a>
                                            @empty
                                                <span class="block px-4 py-2 text-sm text-gray-500">No ingredients available</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </template>

                                <div class="flex gap-3 items-end">
                                    <div class="w-32 flex-shrink-0">
                                        <label class="block text-sm font-medium text-[#4A2C1D]">Qty received</label>
                                        <input type="number" x-model.number="item.quantity" min="1" class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1 text-sm">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <label class="block text-sm font-medium text-[#4A2C1D]">Note (optional)</label>
                                        <input type="text" x-model="item.note" placeholder="e.g. batch A" class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1 text-sm">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <button @click="addStockInItem()"
                        class="mt-3 w-full flex items-center justify-center gap-2 px-4 py-2 border-2 border-dashed border-[#7F5539] rounded-lg text-sm font-medium text-[#7F5539] hover:bg-[#7F5539]/5 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Add another item
                    </button>
                </div>

                <div class="flex-shrink-0 px-4 sm:px-6 py-4 border-t border-gray-200 flex justify-end gap-3 bg-white">
                    <button @click="closeStockInModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</button>
                    <button @click="submitStockIn()" :disabled="isSubmitting"
                        class="px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-[#7F5539] hover:bg-[#4A2C1D] disabled:opacity-50 transition-colors">
                        <span x-text="isSubmitting ? 'Submitting...' : 'Submit for approval'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- STOCK OUT MODAL (no branch selector) --}}
        <div x-show="showStockOutModal" x-cloak
            class="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center p-0 sm:p-4"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="fixed inset-0 bg-gray-500/75" @click="closeStockOutModal()"></div>
            <div class="relative z-10 w-full sm:max-w-2xl bg-white rounded-t-2xl sm:rounded-xl shadow-xl flex flex-col max-h-[92dvh] sm:max-h-[85vh]"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full sm:translate-y-4 sm:opacity-0"
                x-transition:enter-end="translate-y-0 sm:opacity-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-y-0 sm:opacity-100" x-transition:leave-end="translate-y-full sm:translate-y-4 sm:opacity-0">

                <div class="sm:hidden flex justify-center pt-3 pb-1 flex-shrink-0"><div class="w-10 h-1 bg-gray-300 rounded-full"></div></div>

                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Declare Stock Out</h3>
                        <p class="text-sm text-gray-500 mt-0.5">Select multiple items — one transaction</p>
                    </div>
                    <button @click="closeStockOutModal()" class="text-gray-400 hover:text-gray-500 p-1 rounded-full hover:bg-gray-100 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="px-6 py-3 bg-amber-50 border-b border-amber-100 flex-shrink-0">
                    <p class="text-sm text-amber-700 flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 flex-shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                        This transaction will be sent for approval before inventory is updated.
                    </p>
                </div>

                <div class="flex-1 overflow-y-auto overscroll-contain px-4 sm:px-6 py-4">
                    <div class="space-y-3">
                        <template x-for="(item, index) in stockOutItems" :key="index">
                            <div class="relative p-3 bg-gray-50 rounded-lg border border-gray-200 space-y-3">

                                <button @click.stop="removeStockOutItem(index)" x-show="stockOutItems.length > 1"
                                    class="absolute top-3 right-3 z-10 text-red-400 hover:text-red-600 p-1.5 rounded-full hover:bg-red-50 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>

                                <div>
                                    <label class="block text-sm font-medium text-[#4A2C1D] mb-1">Item Type</label>
                                    <div class="flex rounded-lg border-2 border-[#7F5539] overflow-hidden">
                                        <button type="button"
                                            @click="item.item_type = 'product'; item.ingredient_id = '';"
                                            class="flex-1 py-2 text-sm font-medium transition-colors"
                                            :class="item.item_type === 'product' ? 'bg-[#7F5539] text-white' : 'bg-white text-[#7F5539] hover:bg-[#7F5539]/5'">
                                            Product
                                        </button>
                                        <button type="button"
                                            @click="item.item_type = 'ingredient'; item.product_id = '';"
                                            class="flex-1 py-2 text-sm font-medium transition-colors border-l-2 border-[#7F5539]"
                                            :class="item.item_type === 'ingredient' ? 'bg-[#7F5539] text-white' : 'bg-white text-[#7F5539] hover:bg-[#7F5539]/5'">
                                            Ingredient
                                        </button>
                                    </div>
                                </div>

                                <template x-if="item.item_type === 'product'">
                                    <div x-data="{
                                        open: false,
                                        get selectedName() {
                                            if (!item.product_id) return 'Select a product';
                                            const products = {{ Js::from($products->map->only(['id', 'product_name'])) }};
                                            const p = products.find(p => p.id == item.product_id);
                                            return p ? p.product_name : 'Select a product';
                                        },
                                        select(id) { item.product_id = id; this.open = false; }
                                    }" class="relative">
                                        <label class="block text-sm font-medium text-[#4A2C1D]">Product</label>
                                        <button @click="open = !open" @click.away="open = false" type="button"
                                            class="w-full flex justify-between items-center border-2 border-[#7F5539] rounded px-3 py-2 mt-1 text-left bg-white">
                                            <span x-text="selectedName" :class="{ 'text-gray-500': !item.product_id }"></span>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500" :class="{ 'rotate-180': open }"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                                        </button>
                                        <div x-show="open" x-transition class="absolute mt-1 w-full bg-white rounded-md shadow-lg z-20 border border-gray-200 max-h-60 overflow-y-auto" style="display:none;">
                                            @forelse ($products as $product)
                                                <a href="#" @click.prevent="select({{ $product->id }})" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ $product->product_name }}</a>
                                            @empty
                                                <span class="block px-4 py-2 text-sm text-gray-500">No products available</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </template>

                                <template x-if="item.item_type === 'ingredient'">
                                    <div x-data="{
                                        open: false,
                                        get selectedName() {
                                            if (!item.ingredient_id) return 'Select an ingredient';
                                            const ingredients = {{ Js::from($ingredients->map->only(['id', 'ingredient_name', 'unit'])) }};
                                            const i = ingredients.find(i => i.id == item.ingredient_id);
                                            return i ? i.ingredient_name + ' (' + i.unit + ')' : 'Select an ingredient';
                                        },
                                        select(id) { item.ingredient_id = id; this.open = false; }
                                    }" class="relative">
                                        <label class="block text-sm font-medium text-[#4A2C1D]">Ingredient</label>
                                        <button @click="open = !open" @click.away="open = false" type="button"
                                            class="w-full flex justify-between items-center border-2 border-[#7F5539] rounded px-3 py-2 mt-1 text-left bg-white">
                                            <span x-text="selectedName" :class="{ 'text-gray-500': !item.ingredient_id }"></span>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500" :class="{ 'rotate-180': open }"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                                        </button>
                                        <div x-show="open" x-transition class="absolute mt-1 w-full bg-white rounded-md shadow-lg z-20 border border-gray-200 max-h-60 overflow-y-auto" style="display:none;">
                                            @forelse ($ingredients as $ingredient)
                                                <a href="#" @click.prevent="select({{ $ingredient->id }})" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    {{ $ingredient->ingredient_name }} <span class="text-gray-400 text-xs">({{ $ingredient->unit }})</span>
                                                </a>
                                            @empty
                                                <span class="block px-4 py-2 text-sm text-gray-500">No ingredients available</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </template>

                                <div class="flex gap-3 items-end">
                                    <div class="w-32 flex-shrink-0">
                                        <label class="block text-sm font-medium text-[#4A2C1D]">Qty out</label>
                                        <input type="number" x-model.number="item.quantity" min="1" class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1 text-sm">
                                    </div>
                                    <div class="sm:w-44">
                                        <label class="block text-sm font-medium text-[#4A2C1D]">Reason</label>
                                        <select x-model="item.reason" class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1 text-sm bg-white">
                                            <option value="">— select —</option>
                                            <option value="expired">Expired</option>
                                            <option value="damaged">Damaged</option>
                                            <option value="pulled_out">Pulled out</option>
                                        </select>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <label class="block text-sm font-medium text-[#4A2C1D]">Note (optional)</label>
                                        <input type="text" x-model="item.note" placeholder="e.g. found on shelf check" class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1 text-sm">
                                    </div>
                                </div>

                            </div>
                        </template>
                    </div>
                    <button @click="addStockOutItem()"
                        class="mt-3 w-full flex items-center justify-center gap-2 px-4 py-2 border-2 border-dashed border-[#7F5539] rounded-lg text-sm font-medium text-[#7F5539] hover:bg-[#7F5539]/5 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Add another item
                    </button>
                </div>

                <div class="flex-shrink-0 px-4 sm:px-6 py-4 border-t border-gray-200 flex justify-end gap-3 bg-white">
                    <button @click="closeStockOutModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</button>
                    <button @click="submitStockOut()" :disabled="isSubmitting"
                        class="px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-[#7F5539] hover:bg-[#4A2C1D] disabled:opacity-50 transition-colors">
                        <span x-text="isSubmitting ? 'Submitting...' : 'Submit'"></span>
                    </button>
                </div>
            </div>
        </div>

         {{-- ═════════════════════════════════════
             VIEW TRANSACTION DETAILS MODAL
        ═════════════════════════════════════════ --}}
        <div x-show="showViewModal" x-cloak
            class="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center p-0 sm:p-4"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="fixed inset-0 bg-gray-500/75" @click="closeViewModal()"></div>
            <div class="relative z-10 w-full sm:max-w-4xl bg-white rounded-t-2xl sm:rounded-xl shadow-xl flex flex-col max-h-[92dvh] sm:max-h-[85vh]"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full sm:translate-y-4 sm:opacity-0"
                x-transition:enter-end="translate-y-0 sm:opacity-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-y-0 sm:opacity-100" x-transition:leave-end="translate-y-full sm:translate-y-4 sm:opacity-0">

                <template x-if="viewTransaction">
                    <div class="flex flex-col flex-1 min-h-0">
                        <div class="sm:hidden flex justify-center pt-3 pb-1 flex-shrink-0"><div class="w-10 h-1 bg-gray-300 rounded-full"></div></div>

                        <div class="flex justify-between items-start px-6 py-4 border-b border-gray-200 flex-shrink-0">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900" x-text="viewTransaction.transaction_no"></h3>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="viewTransaction.type === 'stock_in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                        x-text="viewTransaction.type === 'stock_in' ? 'Stock In' : 'Stock Out'"></span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                        :class="{
                                            'bg-green-100 text-green-800': viewTransaction.status === 'approved',
                                            'bg-amber-100 text-amber-800': viewTransaction.status === 'pending',
                                            'bg-red-100 text-red-800':    viewTransaction.status === 'rejected',
                                            'bg-gray-100 text-gray-700':  viewTransaction.status === 'done',
                                        }"
                                        x-text="statusLabel(viewTransaction.status)"></span>
                                </div>
                            </div>
                            <button @click="closeViewModal()" class="text-gray-400 hover:text-gray-500 p-1 rounded-full hover:bg-gray-100 transition-colors">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="flex-1 overflow-y-auto overscroll-contain px-4 sm:px-6 py-4">
                            <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                                <div>
                                    <p class="text-gray-500">Date</p>
                                    <p class="font-medium text-gray-900" x-text="formatDate(viewTransaction.created_at)"></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Processed by</p>
                                    <p class="font-medium text-gray-900" x-text="viewTransaction.processed_by ?? '—'"></p>
                                </div>
                                <template x-if="viewTransaction.approved_by">
                                    <div>
                                        <p class="text-gray-500">Approved by</p>
                                        <p class="font-medium text-gray-900" x-text="viewTransaction.approved_by"></p>
                                    </div>
                                </template>
                                <template x-if="viewTransaction.rejected_reason">
                                    <div class="col-span-2">
                                        <p class="text-gray-500">Rejection reason</p>
                                        <p class="font-medium text-red-700" x-text="viewTransaction.rejected_reason"></p>
                                    </div>
                                </template>
                            </div>

                            <p class="text-sm font-medium text-gray-700 mb-2">Items</p>
                            <div class="overflow-x-auto rounded-lg border border-gray-200">

                                {{-- HEADER ROW: Type | Name | Qty | Reason (stock_out only) | Note --}}
                                <div class="grid bg-gray-50 border-b border-gray-200 text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    :class="viewTransaction.type === 'stock_out'
                                        ? 'grid-cols-[5.5rem_15rem_4rem_7rem_1fr]'
                                        : 'grid-cols-[5.5rem_15rem_4rem_1fr]'">
                                    <div class="px-4 py-3">Type</div>
                                    <div class="px-4 py-3">Name</div>
                                    <div class="px-4 py-3">Qty</div>
                                    <template x-if="viewTransaction.type === 'stock_out'">
                                        <div class="px-4 py-3">Reason</div>
                                    </template>
                                    <div class="px-4 py-3">Note</div>
                                </div>

                                <template x-for="(item, i) in groupedItems(viewTransaction.items)" :key="i">
                                    <div>
                                        <div class="grid border-b border-gray-100 last:border-b-0 text-sm"
                                            :class="[
                                                viewTransaction.type === 'stock_out'
                                                    ? 'grid-cols-[5.5rem_15rem_4rem_7rem_1fr]'
                                                    : 'grid-cols-[5.5rem_15rem_4rem_1fr]',
                                                i % 2 === 0 ? 'bg-white' : 'bg-gray-50'
                                            ]">

                                            <div class="px-4 py-3 flex items-start pt-3.5">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                    :class="item.is_mto ? 'bg-amber-100 text-amber-700' : item.is_ingredient ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
                                                    x-text="item.is_mto ? 'MTO' : item.is_ingredient ? 'Ingredient' : 'Product'"></span>
                                            </div>

                                            <div class="px-4 py-3 flex flex-col gap-0.5 min-w-0">
                                                <span class="font-medium text-gray-900 truncate block" :title="item.product_name" x-text="item.product_name"></span>
                                                <template x-if="item.is_mto && item.ingredients && item.ingredients.length">
                                                    <button
                                                        @click="expandedRows[i] = !expandedRows[i]; expandedRows = {...expandedRows}"
                                                        class="text-xs text-indigo-600 hover:text-indigo-800 hover:underline inline-flex items-center gap-1 w-fit transition-colors">
                                                        <svg class="w-3 h-3 flex-shrink-0 transition-transform duration-200"
                                                            :class="expandedRows[i] ? 'rotate-90' : ''"
                                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                        </svg>
                                                        <span x-text="expandedRows[i] ? 'Hide ingredients' : 'View ingredients (' + item.ingredients.length + ')'"></span>
                                                    </button>
                                                </template>
                                            </div>

                                            <div class="px-4 py-3 text-gray-900 whitespace-nowrap" x-text="item.quantity + ' ' + (item.unit || '')"></div>

                                            <template x-if="viewTransaction.type === 'stock_out'">
                                                <div class="px-4 py-3">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-700 whitespace-nowrap"
                                                        x-text="reasonLabel(item.reason)"></span>
                                                </div>
                                            </template>

                                            <div class="px-4 py-3 text-gray-500 break-words text-sm leading-relaxed" x-text="item.note || '—'"></div>
                                        </div>

                                        <template x-if="expandedRows[i] && item.is_mto && item.ingredients && item.ingredients.length">
                                            <div>
                                                <template x-for="(ing, j) in item.ingredients" :key="'ing-' + i + '-' + j">
                                                    <div class="grid border-b border-amber-100 last:border-b-0 bg-amber-50 border-l-2 border-l-amber-300 text-sm"
                                                        :class="viewTransaction.type === 'stock_out'
                                                            ? 'grid-cols-[5.5rem_15rem_4rem_7rem_1fr]'
                                                            : 'grid-cols-[5.5rem_15rem_4rem_1fr]'">

                                                        <div class="px-4 py-2 flex items-start pt-2.5">
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">Ingredient</span>
                                                        </div>

                                                        <div class="px-4 py-2 flex items-center gap-1.5 min-w-0 text-gray-700">
                                                            <span class="text-amber-400 flex-shrink-0">↳</span>
                                                            <span class="break-words" x-text="ing.ingredient_name || ing.product_name || '—'"></span>
                                                        </div>

                                                        <div class="px-4 py-2 text-gray-700 whitespace-nowrap" x-text="ing.quantity + ' ' + (ing.unit || '')"></div>

                                                        <template x-if="viewTransaction.type === 'stock_out'">
                                                            <div class="px-4 py-2">
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-700 whitespace-nowrap"
                                                                    x-text="reasonLabel(ing.reason)"></span>
                                                            </div>
                                                        </template>

                                                        <div class="px-4 py-2 text-xs text-gray-400 break-words leading-relaxed" x-text="ing.note || '—'"></div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="!viewTransaction.items || !viewTransaction.items.length">
                                    <div class="px-6 py-8 text-center text-sm text-gray-400">No items found.</div>
                                </template>
                            </div>
                        </div>

                        <div class="flex-shrink-0 px-4 sm:px-6 py-4 border-t border-gray-200 flex justify-end bg-white">
                            <button @click="closeViewModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Close</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('alpine:init', () => {

            Alpine.data('filterState', () => ({
                filters: { status: '' },
                init() {
                    const main = Alpine.$data(document.querySelector('[x-data="inventoryData()"]'));
                    this.filters.status = main.filterStatus;
                },
                clearFilters() {
                    this.filters = { status: '' };
                },
                applyFilters() {
                    const main = Alpine.$data(document.querySelector('[x-data="inventoryData()"]'));
                    main.filterStatus = this.filters.status;
                    main.stockPage = 1;
                    main.closeFilterModal();
                }
            }));

            Alpine.data('inventoryData', () => ({

                summary:      @json($summary ?? []),
                stats:        @json($stats ?? []),
                isSubmitting: false,

                pendingCount:        @json($pendingCount ?? 0),
                pendingTransactions: @json($pendingTransactions ?? []),
                stockLevels:         @json($stockLevels ?? []),
                newArrivals:         @json($newArrivals ?? []),
                lowStockItems:       @json($lowStockItems ?? []),
                currentPeriodLabel:  @json($periodLabel ?? 'this month'),

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

                // Status tabs for "My Stock Requests" — acts as a history log
                transactionTab: 'all',
                transactionTabs: [
                    { key: 'all',      label: 'All' },
                    { key: 'pending',  label: 'Pending' },
                    { key: 'approved', label: 'Approved' },
                    { key: 'rejected', label: 'Rejected' },
                ],
                get filteredTransactions() {
                    if (this.transactionTab === 'all') return this.pendingTransactions;
                    return this.pendingTransactions.filter(t => t.status === this.transactionTab);
                },

                showFilters: false,
                filterStatus: '',

                get activeFilterCount() {
                    return (this.filterStatus ? 1 : 0);
                },
                get hasActiveFilters() {
                    return this.filterStatus !== '';
                },
                get activeFilters() {
                    const filters = [];
                    if (this.filterStatus) {
                        const label = { low: 'Low Stock', medium: 'At Threshold', high: 'OK' }[this.filterStatus] ?? this.filterStatus;
                        filters.push({ key: 'status', label: `Status: ${label}` });
                    }
                    return filters;
                },
                removeFilter(key) {
                    if (key === 'status') this.filterStatus = '';
                    this.stockPage = 1;
                },
                clearAllFilters() {
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

                showStockInModal:     false,
                showStockOutModal:    false,
                showViewModal:        false,
                showNewArrivalsModal: false,
                showLowStockModal:    false,

                stockInItems: [{ item_type: 'product', product_id: '', ingredient_id: '', quantity: 1, note: '' }],
                stockOutItems: [{ item_type: 'product', product_id: '', ingredient_id: '', quantity: 1, reason: '', note: '' }],

                viewTransaction: null,
                expandedRows:    {},

                init() {
                    const params = new URLSearchParams(window.location.search);
                    const tab = params.get('tab');

                    if (tab && this.stockTabs.some(t => t.key === tab)) {
                        this.stockTab = tab;
                    }

                    if (tab) {
                        this.stockPage = 1;
                        this.$nextTick(() => {
                            document.getElementById('stockLevelsSection')
                                ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        });
                    }
                },

                formatDate(d) {
                    if (!d) return '—';
                    return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                },
                formatTime(d) {
                    if (!d) return '';
                    return new Date(d).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                },
                statusLabel(s) {
                    return { approved: 'Approved', pending: 'Pending', rejected: 'Rejected', done: 'Done' }[s] ?? s;
                },
                groupedItems(items) {
                    if (!items) return [];

                    items = items.map(i => {
                        const isIngredient = i.item_type === 'ingredient'
                            || (i.ingredient_id && !i.product_id);
                        const isMtoProduct = i.item_type === 'mto_product';
                        return { ...i, _resolved_type: isIngredient ? 'ingredient' : isMtoProduct ? 'mto_product' : 'product' };
                    });

                    const productRows    = items.filter(i => i._resolved_type === 'product' || i._resolved_type === 'mto_product');
                    const ingredientRows = items.filter(i => i._resolved_type === 'ingredient');

                    const mtoIngredientRows    = ingredientRows.filter(i => i.note && i.note.includes('MTO:'));
                    const manualIngredientRows = ingredientRows.filter(i => !(i.note && i.note.includes('MTO:')));

                    const result = [];

                    productRows.forEach(row => {
                        const isMto = row._resolved_type === 'mto_product';
                        const children = isMto
                            ? mtoIngredientRows.filter(ing =>
                                ing.note && ing.note.toLowerCase().includes(
                                    ('mto: ' + row.product_name).toLowerCase()
                                )
                              )
                            : [];
                        result.push({ ...row, is_mto: isMto, ingredients: children });
                    });

                    manualIngredientRows.forEach(ing => {
                        result.push({
                            ...ing,
                            is_mto:       false,
                            is_ingredient: true,
                            product_name: ing.ingredient_name || (ing.ingredient ? ing.ingredient.ingredient_name : '—'),
                            ingredients:  [],
                        });
                    });

                    if (productRows.length === 0 && mtoIngredientRows.length > 0) {
                        const mtoGroups = {};
                        mtoIngredientRows.forEach(item => {
                            const mtoMatch = item.note && item.note.match(/MTO:\s*(.+?)\s*x(\d+)/);
                            if (mtoMatch) {
                                const productName = mtoMatch[1].trim();
                                const productQty  = mtoMatch[2];
                                const key         = productName + '|' + productQty;
                                if (!mtoGroups[key]) {
                                    mtoGroups[key] = {
                                        product_name: productName,
                                        quantity:     productQty,
                                        unit:         'pcs',
                                        reason:       item.reason ?? '',
                                        note:         '',
                                        is_mto:       true,
                                        ingredients:  [],
                                    };
                                }
                                mtoGroups[key].ingredients.push(item);
                            }
                        });
                        Object.values(mtoGroups).forEach(g => result.push(g));
                    }

                    return result;
                },
                reasonLabel(r) {
                    return { expired: 'Expired', damaged: 'Damaged', pulled_out: 'Pulled out', sold: 'Sold', used_in_mto: 'MTO Ingredient' }[r] ?? r ?? '—';
                },
                addBodyClass()    { document.body.classList.add('modal-open'); },
                removeBodyClass() { document.body.classList.remove('modal-open'); },

                async refreshData() {
                    try {
                        const response = await fetch(`?ajax=true`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                        if (!response.ok) throw new Error('Network error');
                        const data = await response.json();
                        if (data.success) {
                            this.summary             = data.summary;
                            this.stats                = data.stats;
                            this.pendingCount         = data.pending_count;
                            this.pendingTransactions  = data.pending_transactions;
                            this.stockLevels          = data.stock_levels;
                            this.newArrivals          = data.new_arrivals;
                            this.lowStockItems        = data.low_stock_items;
                        }
                    } catch (e) {
                        alert('Failed to refresh inventory data. Please try again.');
                    }
                },

                openNewArrivalsModal()  { this.showNewArrivalsModal = true; this.addBodyClass(); },
                closeNewArrivalsModal() { this.showNewArrivalsModal = false; this.removeBodyClass(); },
                openLowStockModal()     { this.showLowStockModal = true; this.addBodyClass(); },
                closeLowStockModal()    { this.showLowStockModal = false; this.removeBodyClass(); },

                restockItem(item) {
                    this.stockInItems = [{
                        item_type:     item.item_type,
                        product_id:    item.item_type === 'product'    ? item.id : '',
                        ingredient_id: item.item_type === 'ingredient' ? item.id : '',
                        quantity:      1,
                        note:          '',
                    }];
                    this.showStockInModal = true;
                    this.addBodyClass();
                },

                openStockInModal() {
                    this.stockInItems = [{ item_type: 'product', product_id: '', ingredient_id: '', quantity: 1, note: '' }];
                    this.showStockInModal = true; this.addBodyClass();
                },
                closeStockInModal() { this.showStockInModal = false; this.removeBodyClass(); },
                addStockInItem()    { this.stockInItems.push({ item_type: 'product', product_id: '', ingredient_id: '', quantity: 1, note: '' }); },
                removeStockInItem(i) { this.stockInItems.splice(i, 1); },

                async submitStockIn() {
                    if (this.isSubmitting) return;
                    const invalid = this.stockInItems.some(i => {
                        if (i.quantity < 1) return true;
                        if (i.item_type === 'product'    && !i.product_id)    return true;
                        if (i.item_type === 'ingredient' && !i.ingredient_id) return true;
                        return false;
                    });
                    if (invalid) { alert('Please fill in all required fields for each item.'); return; }
                    this.isSubmitting = true;
                    try {
                        const response = await fetch('{{ route('sub_two.inventory.stockIn') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                            body: JSON.stringify({
                                products: this.stockInItems.map(i => ({
                                    item_type:     i.item_type,
                                    product_id:    i.item_type === 'product'    ? i.product_id    : null,
                                    ingredient_id: i.item_type === 'ingredient' ? i.ingredient_id : null,
                                    quantity:      i.quantity,
                                    note:          i.note,
                                }))
                            }),
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.closeStockInModal();
                            await this.refreshData();
                            this.transactionTab = 'pending';
                        }
                        else throw new Error(data.message || 'Failed to save stock in');
                    } catch (e) { alert(e.message || 'Failed to save. Please try again.');
                    } finally { this.isSubmitting = false; }
                },

                openStockOutModal() {
                    this.stockOutItems = [{ item_type: 'product', product_id: '', ingredient_id: '', quantity: 1, reason: '', note: '' }];
                    this.showStockOutModal = true; this.addBodyClass();
                },
                closeStockOutModal() { this.showStockOutModal = false; this.removeBodyClass(); },
                addStockOutItem()    { this.stockOutItems.push({ item_type: 'product', product_id: '', ingredient_id: '', quantity: 1, reason: '', note: '' }); },
                removeStockOutItem(i) { this.stockOutItems.splice(i, 1); },

                async submitStockOut() {
                    if (this.isSubmitting) return;
                    const invalid = this.stockOutItems.some(i => {
                        if (i.quantity < 1 || !i.reason) return true;
                        if (i.item_type === 'product'    && !i.product_id)    return true;
                        if (i.item_type === 'ingredient' && !i.ingredient_id) return true;
                        return false;
                    });
                    if (invalid) { alert('Please fill in all required fields (item, quantity, reason) for each row.'); return; }
                    this.isSubmitting = true;
                    try {
                        const response = await fetch('{{ route('sub_two.inventory.stockOut') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                            body: JSON.stringify({
                                products: this.stockOutItems.map(i => ({
                                    item_type:     i.item_type,
                                    product_id:    i.item_type === 'product'    ? i.product_id    : null,
                                    ingredient_id: i.item_type === 'ingredient' ? i.ingredient_id : null,
                                    quantity:      i.quantity,
                                    reason:        i.reason,
                                    note:          i.note,
                                }))
                            }),
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.closeStockOutModal();
                            await this.refreshData();
                            this.transactionTab = 'pending';
                        }
                        else throw new Error(data.message || 'Failed to submit stock out');
                    } catch (e) { alert(e.message || 'Failed to submit. Please try again.');
                    } finally { this.isSubmitting = false; }
                },

                async openViewModal(txn) {
                    try {
                        const response = await fetch(`{{ url('sub_two/inventory') }}/${txn.uuid}/details`, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const data = await response.json();
                        if (data.success) { this.viewTransaction = data.transaction; this.expandedRows = {}; this.showViewModal = true; this.addBodyClass(); }
                    } catch (e) { alert('Failed to load transaction details.'); }
                },
                closeViewModal() { this.showViewModal = false; this.viewTransaction = null; this.expandedRows = {}; this.removeBodyClass(); },

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
    </style>
@endsection