@extends('layouts.app')

@section('title', 'Stock Out')

@section('content')
    <div x-data="stockOutHistoryData()" x-init="init()" class="p-4">

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
            <h1 class="text-2xl font-bold text-gray-900 text-center">Stock Out</h1>
            <div class="w-32"></div> {{-- spacer to balance the back link --}}
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">All Stock Out Transactions</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Click the eye icon to view items, reasons, and approval details</p>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full lg:w-auto">
                        <div class="relative w-full sm:w-64">
                            <input type="text" x-model="searchQuery" @input.debounce.500ms="txnPage = 1"
                                placeholder="Search by transaction #..."
                                class="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <button @click="openFilterModal()"
                            class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] whitespace-nowrap">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-5 h-5 mr-1">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                            </svg>
                            Filters
                            <span x-show="hasModalFilters" class="ml-1.5 inline-flex h-2 w-2 rounded-full bg-[#7F5539]"></span>
                        </button>

                        {{-- ADD BUTTON — opens the Declare Stock Out modal instead of navigating away --}}
                        <button @click="openAddModal()"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg text-sm font-semibold text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539] whitespace-nowrap">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="w-4 h-4 mr-1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Add
                        </button>
                    </div>
                </div>

                <div x-show="hasModalFilters" x-cloak class="flex flex-wrap items-center justify-end gap-2">
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
                    <button @click="clearAllFilters()" class="text-sm text-[#4A2C1D] hover:text-[#7F5539] font-medium">Clear all</button>
                </div>
            </div>

            {{-- TABLE — same column layout/proportions as the Inventory page,
                 with a wider Transaction No. column since it's the primary key here --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-56">Transaction No.</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(txn, index) in pagedTransactions" :key="txn.uuid">
                            <tr :id="'txn-' + txn.uuid"
                                :class="[
                                    highlightedUuid === txn.uuid ? 'ring-2 ring-inset ring-[#7F5539] bg-[#7F5539]/5' : (index % 2 === 0 ? 'bg-white' : 'bg-gray-50'),
                                    txn.status === 'pending'  ? 'border-l-4 border-l-amber-400' :
                                    txn.status === 'approved' ? 'border-l-4 border-l-green-400' :
                                    txn.status === 'rejected' ? 'border-l-4 border-l-red-400' : ''
                                ]">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="txn.transaction_no"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700" x-text="txn.branch_name ?? txn.branch?.branch_name ?? '—'"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" x-text="formatDate(txn.created_at)"></div>
                                    <div class="text-xs text-gray-400" x-text="formatTime(txn.created_at)"></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800"
                                        x-text="'−' + (txn.display_qty ?? 0)"></span>
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
                                        <button @click="openViewModal(txn)"
                                            class="text-[#4A2C1D] hover:text-[#7F5539] transition-colors p-2 rounded-full hover:bg-gray-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                        </button>
                                        <span class="absolute -top-9 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded shadow-md opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-10">
                                            View Details
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="!pagedTransactions.length">
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <p class="text-sm font-medium text-gray-900" x-text="hasActiveFilters ? 'No transactions match your filters' : 'Nothing here yet'"></p>
                                    <p class="text-sm text-gray-500" x-text="hasActiveFilters ? 'Try adjusting your search or filters.' : 'Stock-out transactions will show up here.'"></p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div x-show="txnTotalPages > 1" class="px-4 sm:px-6 py-4 border-t border-gray-200">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="text-sm text-gray-700">
                        Showing <span x-text="txnShowingFrom"></span> to <span x-text="txnShowingTo"></span>
                        of <span x-text="filteredTransactions.length"></span> entries
                    </div>
                    <div class="flex items-center gap-1">
                        <button @click="txnPage = Math.max(1, txnPage - 1)" :disabled="txnPage === 1"
                            class="px-3 py-1 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                        <template x-for="p in txnPageNumbers" :key="p">
                            <button @click="txnPage = p"
                                class="px-3 py-1 border rounded-lg text-sm font-medium"
                                :class="p === txnPage ? 'bg-[#7F5539] text-white border-[#7F5539]' : 'border-gray-300 text-gray-700 hover:bg-gray-50'"
                                x-text="p"></button>
                        </template>
                        <button @click="txnPage = Math.min(txnTotalPages, txnPage + 1)" :disabled="txnPage === txnTotalPages"
                            class="px-3 py-1 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- FILTER MODAL --}}
        <div x-show="showFilters" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showFilters = false; dateError = ''"></div>
                <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl sm:my-8 sm:align-middle max-w-md w-full">
                    <div class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Filter Stock Out</h3>
                        <button @click="showFilters = false; dateError = ''" class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="px-6 py-5">
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                                <div class="flex gap-2 mb-3">
                                    <button @click="setDatePreset('week')" :class="datePreset === 'week' ? 'bg-[#7F5539] text-white border-[#7F5539]' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'" class="flex-1 px-3 py-1.5 border rounded-lg text-xs font-medium transition-colors focus:outline-none">Last 7 Days</button>
                                    <button @click="setDatePreset('month')" :class="datePreset === 'month' ? 'bg-[#7F5539] text-white border-[#7F5539]' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'" class="flex-1 px-3 py-1.5 border rounded-lg text-xs font-medium transition-colors focus:outline-none">Last 30 Days</button>
                                    <button @click="setDatePreset('custom')" :class="datePreset === 'custom' ? 'bg-[#7F5539] text-white border-[#7F5539]' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'" class="flex-1 px-3 py-1.5 border rounded-lg text-xs font-medium transition-colors focus:outline-none">Custom</button>
                                </div>
                                <div x-show="datePreset === 'custom'" class="flex gap-3">
                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-500 mb-1">From</label>
                                        <input type="date" x-model="draftFilters.date_from" @change="dateError = ''" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]">
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-500 mb-1">To</label>
                                        <input type="date" x-model="draftFilters.date_to" @change="dateError = (draftFilters.date_from && draftFilters.date_to && new Date(draftFilters.date_to) < new Date(draftFilters.date_from)) ? '\"To\" date cannot be earlier than \"From\" date.' : ''" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]">
                                    </div>
                                </div>
                                <div x-show="dateError" x-cloak class="text-red-600 text-xs mt-2" x-text="dateError"></div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                                <select x-model="draftFilters.branch_id" class="block w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] px-3 py-2 text-sm">
                                    <option value="">All Branches</option>
                                    <template x-for="branch in branches" :key="branch.id">
                                        <option :value="branch.id" x-text="branch.branch_name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select x-model="draftFilters.status" class="block w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] px-3 py-2 text-sm">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 pb-5 flex gap-3">
                        <button @click="clearModalFilters()" class="flex-1 inline-flex justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50">Clear</button>
                        <button @click="applyFilters()" class="flex-1 inline-flex justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-[#7F5539] hover:bg-[#4A2C1D]">Apply Filters</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═════════════════════════════════════
             VIEW TRANSACTION DETAILS MODAL
             (matches the one on the Inventory page — includes Reason column)
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
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Stock Out
                                    </span>
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
                                    <p class="text-gray-500">Branch</p>
                                    <p class="font-medium text-gray-900" x-text="viewTransaction.branch_name ?? '—'"></p>
                                </div>
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

                                <div class="grid grid-cols-[5.5rem_15rem_4rem_7rem_1fr] bg-gray-50 border-b border-gray-200 text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="px-4 py-3">Type</div>
                                    <div class="px-4 py-3">Name</div>
                                    <div class="px-4 py-3">Qty</div>
                                    <div class="px-4 py-3">Reason</div>
                                    <div class="px-4 py-3">Note</div>
                                </div>

                                <template x-for="(item, i) in groupedItems(viewTransaction.items)" :key="i">
                                    <div class="grid grid-cols-[5.5rem_15rem_4rem_7rem_1fr] border-b border-gray-100 last:border-b-0 text-sm"
                                        :class="i % 2 === 0 ? 'bg-white' : 'bg-gray-50'">
                                        <div class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                :class="item.is_ingredient ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
                                                x-text="item.is_ingredient ? 'Ingredient' : 'Product'"></span>
                                        </div>
                                        <div class="px-4 py-3 font-medium text-gray-900 truncate" :title="item.product_name" x-text="item.product_name"></div>
                                        <div class="px-4 py-3 text-gray-900 whitespace-nowrap" x-text="item.quantity + ' ' + (item.unit || '')"></div>
                                        <div class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-700 whitespace-nowrap"
                                                x-text="reasonLabel(item.reason)"></span>
                                        </div>
                                        <div class="px-4 py-3 text-gray-500 break-words text-sm leading-relaxed" x-text="item.note || '—'"></div>
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

        {{-- ═════════════════════════════════════
             ADD (DECLARE STOCK OUT) MODAL
             Includes Branch toggle + Processed By
        ═════════════════════════════════════════ --}}
        <div x-show="showAddModal" x-cloak
            class="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center p-0 sm:p-4"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="fixed inset-0 bg-gray-500/75" @click="closeAddModal()"></div>
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
                    <button @click="closeAddModal()" class="text-gray-400 hover:text-gray-500 p-1 rounded-full hover:bg-gray-100 transition-colors">
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

                {{-- Processed By --}}
                <div class="px-6 py-3 bg-gray-50 border-b border-gray-200 flex-shrink-0 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                    <p class="text-sm text-gray-600">Processed by <strong class="text-gray-900" x-text="processedByName"></strong></p>
                </div>

                {{-- Branch Toggle Tabs (no "All Branches" — defaults to Claveria) --}}
                <div class="px-4 sm:px-6 pt-4 flex-shrink-0">
                    <div class="flex rounded-lg p-1 w-full" style="background-color: #e6ddd4; border: 1px solid #d4c4b2;">
                        <template x-for="branch in branches" :key="branch.id">
                            <button
                                @click="selectBranch(branch.id)"
                                class="flex-1 relative transition-all duration-200 py-2 px-4 rounded-md text-sm font-medium focus:outline-none truncate"
                                :style="selectedBranchId == branch.id
                                    ? 'background-color: #9c6644; color: #fff;'
                                    : 'background-color: transparent; color: #7f5539;'"
                                x-text="branch.branch_name">
                            </button>
                        </template>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto overscroll-contain px-4 sm:px-6 py-4">
                    <div class="space-y-3">
                        <template x-for="(item, index) in stockOutItems" :key="index">
                            <div class="relative p-3 bg-gray-50 rounded-lg border border-gray-200 space-y-3">

                                {{-- Remove --}}
                                <button @click.stop="removeStockOutItem(index)" x-show="stockOutItems.length > 1"
                                    class="absolute top-3 right-3 z-10 text-red-400 hover:text-red-600 p-1.5 rounded-full hover:bg-red-50 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>

                                {{-- Item Type Toggle --}}
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

                                {{-- Product selector --}}
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

                                {{-- Ingredient selector --}}
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

                                {{-- Qty | Reason | Note --}}
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
                                            <option value="pulled_out">Pulled out by owner</option>
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
                    <button @click="closeAddModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</button>
                    <button @click="submitStockOut()" :disabled="isSubmitting"
                        class="px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-[#7F5539] hover:bg-[#4A2C1D] disabled:opacity-50 transition-colors">
                        <span x-text="isSubmitting ? 'Submitting...' : 'Submit'"></span>
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
            Alpine.data('stockOutHistoryData', () => ({

                // Expect the controller to pass only type = 'stock_out' transactions
                transactions: @json($transactions ?? []),
                branches:     @json($branches ?? []),

                searchQuery: '',
                filters:      { branch_id: '', status: '', date_from: '', date_to: '' },
                draftFilters: { branch_id: '', status: '', date_from: '', date_to: '' },
                datePreset:  'all',
                showFilters: false,
                dateError:   '',

                txnPage: 1,
                txnPerPage: 10,

                highlightedUuid: null,

                // ── View Details Modal ──────────────────────────────
                showViewModal:   false,
                viewTransaction: null,

                // ── Add / Declare Stock Out Modal ───────────────────
                showAddModal:     false,
                isSubmitting:     false,
                processedByName: @json($processedByName ?? '—'),
                defaultBranchId:  {{ $defaultBranch->id ?? 'null' }},
                selectedBranchId: {{ $defaultBranch->id ?? 'null' }},
                stockOutItems: [{ item_type: 'product', branch_id: null, product_id: '', ingredient_id: '', quantity: 1, reason: '', note: '' }],

                showToast:    false,
                toastMessage: '',
                toastType:    'success',
                toastTimer:   null,

                init() {
                    const params = new URLSearchParams(window.location.search);
                    const highlight = params.get('highlight');
                    if (!highlight) return;

                    this.highlightedUuid = highlight;
                    const txn = this.transactions.find(t => t.uuid === highlight);
                    if (!txn) return;

                    const idx = this.filteredTransactions.findIndex(t => t.uuid === highlight);
                    if (idx > -1) this.txnPage = Math.floor(idx / this.txnPerPage) + 1;

                    this.$nextTick(() => {
                        document.getElementById('txn-' + highlight)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    });

                    this.openViewModal(txn);
                },

                get filteredTransactions() {
                    let list = this.transactions;

                    if (this.filters.branch_id) {
                        list = list.filter(t => String(t.branch_id ?? t.branch?.id ?? '') === String(this.filters.branch_id));
                    }
                    if (this.filters.status) {
                        list = list.filter(t => t.status === this.filters.status);
                    }
                    if (this.filters.date_from) {
                        const from = new Date(this.filters.date_from + 'T00:00:00');
                        list = list.filter(t => new Date(t.created_at) >= from);
                    }
                    if (this.filters.date_to) {
                        const to = new Date(this.filters.date_to + 'T23:59:59');
                        list = list.filter(t => new Date(t.created_at) <= to);
                    }
                    if (this.searchQuery.trim()) {
                        const q = this.searchQuery.trim().toLowerCase();
                        list = list.filter(t => (t.transaction_no ?? '').toLowerCase().includes(q));
                    }
                    return list;
                },
                get txnTotalPages() { return Math.max(1, Math.ceil(this.filteredTransactions.length / this.txnPerPage)); },
                get pagedTransactions() {
                    const start = (this.txnPage - 1) * this.txnPerPage;
                    return this.filteredTransactions.slice(start, start + this.txnPerPage);
                },
                get txnShowingFrom() { return this.filteredTransactions.length ? (this.txnPage - 1) * this.txnPerPage + 1 : 0; },
                get txnShowingTo() { return Math.min(this.txnPage * this.txnPerPage, this.filteredTransactions.length); },
                get txnPageNumbers() { return Array.from({ length: this.txnTotalPages }, (_, i) => i + 1); },

                get hasModalFilters() {
                    return this.filters.branch_id !== '' || this.filters.status !== '' || this.filters.date_from !== '' || this.filters.date_to !== '';
                },
                get hasActiveFilters() { return this.hasModalFilters || this.searchQuery.trim() !== ''; },
                get activeFilters() {
                    const filters = [];
                    if (this.filters.date_from || this.filters.date_to) {
                        const from = this.filters.date_from ? this.formatDate(this.filters.date_from) : '—';
                        const to   = this.filters.date_to   ? this.formatDate(this.filters.date_to)   : '—';
                        filters.push({ key: 'date', label: `Date: ${from} → ${to}` });
                    }
                    if (this.filters.branch_id) {
                        const b = this.branches.find(b => String(b.id) === String(this.filters.branch_id));
                        if (b) filters.push({ key: 'branch_id', label: `Branch: ${b.branch_name}` });
                    }
                    if (this.filters.status) {
                        filters.push({ key: 'status', label: `Status: ${this.statusLabel(this.filters.status)}` });
                    }
                    return filters;
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
                reasonLabel(r) {
                    return { expired: 'Expired', damaged: 'Damaged', pulled_out: 'Pulled out', sold: 'Sold', used_in_mto: 'MTO Ingredient' }[r] ?? r ?? '—';
                },

                groupedItems(items) {
                    if (!items) return [];
                    return items.map(i => ({
                        ...i,
                        is_ingredient: i.item_type === 'ingredient' || (i.ingredient_id && !i.product_id),
                        product_name:  i.product_name ?? i.ingredient_name ?? (i.ingredient ? i.ingredient.ingredient_name : '—'),
                    }));
                },

                openFilterModal() {
                    this.draftFilters = { ...this.filters };
                    this.datePreset   = (this.draftFilters.date_from || this.draftFilters.date_to) ? 'custom' : 'all';
                    this.dateError    = '';
                    this.showFilters  = true;
                },
                setDatePreset(preset) {
                    this.datePreset = preset;
                    const today = new Date();
                    const fmt = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
                    if (preset === 'week') {
                        const from = new Date(today); from.setDate(today.getDate() - 6);
                        this.draftFilters.date_from = fmt(from); this.draftFilters.date_to = fmt(today);
                    } else if (preset === 'month') {
                        const from = new Date(today); from.setDate(today.getDate() - 29);
                        this.draftFilters.date_from = fmt(from); this.draftFilters.date_to = fmt(today);
                    } else if (preset === 'custom') {
                        this.draftFilters.date_from = ''; this.draftFilters.date_to = '';
                    }
                },
                applyFilters() {
                    if (this.draftFilters.date_from && this.draftFilters.date_to
                        && new Date(this.draftFilters.date_to) < new Date(this.draftFilters.date_from)) {
                        this.dateError = '"To" date cannot be earlier than "From" date.';
                        return;
                    }
                    this.dateError = '';
                    this.filters = { ...this.draftFilters };
                    this.showFilters = false;
                    this.txnPage = 1;
                },
                clearModalFilters() {
                    this.draftFilters = { branch_id: '', status: '', date_from: '', date_to: '' };
                    this.datePreset = 'all';
                    this.dateError = '';
                },
                clearAllFilters() {
                    this.searchQuery = '';
                    this.filters = { branch_id: '', status: '', date_from: '', date_to: '' };
                    this.draftFilters = { ...this.filters };
                    this.datePreset = 'all';
                    this.txnPage = 1;
                },
                removeFilter(key) {
                    if (key === 'date') { this.filters.date_from = ''; this.filters.date_to = ''; }
                    else { this.filters[key] = ''; }
                    this.draftFilters = { ...this.filters };
                    this.txnPage = 1;
                },

                addBodyClass()    { document.body.classList.add('modal-open'); },
                removeBodyClass() { document.body.classList.remove('modal-open'); },

                showToastMsg(message, type = 'success') {
                    this.toastMessage = message;
                    this.toastType = type;
                    this.showToast = true;
                    clearTimeout(this.toastTimer);
                    this.toastTimer = setTimeout(() => { this.showToast = false; }, 3500);
                },

                // ── View Details Modal ──────────────────────────────

                async openViewModal(txn) {
                    try {
                        const response = await fetch(`{{ url('sub_one/inventory') }}/${txn.uuid}/details`, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.viewTransaction = data.transaction;
                            this.showViewModal = true;
                            this.addBodyClass();
                        }
                    } catch (e) { alert('Failed to load transaction details.'); }
                },
                closeViewModal() {
                    this.showViewModal = false;
                    this.viewTransaction = null;
                    this.removeBodyClass();
                },

                // ── Add / Declare Stock Out Modal logic ─────────────

                openAddModal() {
                    this.selectedBranchId = this.defaultBranchId;
                    this.stockOutItems = [{
                        item_type:     'product',
                        branch_id:     this.selectedBranchId,
                        product_id:    '',
                        ingredient_id: '',
                        quantity:      1,
                        reason:        '',
                        note:          '',
                    }];
                    this.showAddModal = true;
                    this.addBodyClass();
                },
                closeAddModal() {
                    this.showAddModal = false;
                    this.removeBodyClass();
                },

                // Switching branch applies to all rows in this transaction —
                // a single Stock Out transaction is scoped to one branch.
                selectBranch(id) {
                    this.selectedBranchId = id;
                    this.stockOutItems.forEach(item => { item.branch_id = id; });
                },

                addStockOutItem() {
                    this.stockOutItems.push({
                        item_type:     'product',
                        branch_id:     this.selectedBranchId,
                        product_id:    '',
                        ingredient_id: '',
                        quantity:      1,
                        reason:        '',
                        note:          '',
                    });
                },
                removeStockOutItem(i) { this.stockOutItems.splice(i, 1); },

                async submitStockOut() {
                    if (this.isSubmitting) return;

                    const invalid = this.stockOutItems.some(i => {
                        if (!i.branch_id || i.quantity < 1 || !i.reason) return true;
                        if (i.item_type === 'product'    && !i.product_id)    return true;
                        if (i.item_type === 'ingredient' && !i.ingredient_id) return true;
                        return false;
                    });
                    if (invalid) { alert('Please fill in all required fields (item, quantity, reason) for each row.'); return; }

                    this.isSubmitting = true;
                    try {
                        const response = await fetch('{{ route('sub_one.inventory.stockOut') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                            body: JSON.stringify({
                                products: this.stockOutItems.map(i => ({
                                    branch_id:     i.branch_id,
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
                            this.closeAddModal();
                            await this.refreshTransactions();
                            this.showToastMsg('Stock out declared. Waiting for approval.', 'success');
                        }
                        else throw new Error(data.message || 'Failed to submit stock out');
                    } catch (e) { this.showToastMsg(e.message || 'Failed to submit. Please try again.', 'error');
                    } finally { this.isSubmitting = false; }
                },

                async refreshTransactions() {
                    try {
                        const response = await fetch(`?ajax=true`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                        if (!response.ok) throw new Error('Network error');
                        const data = await response.json();
                        if (data.success) {
                            this.transactions = data.transactions;
                            this.txnPage = 1;
                        }
                    } catch (e) {
                        // Non-fatal — the new transaction is already saved server-side,
                        // it just won't appear until the next manual refresh.
                    }
                },

            }));
        });
    </script>

    <style>
        .modal-open { overflow: hidden; }
        [x-cloak] { display: none !important; }
    </style>
@endsection