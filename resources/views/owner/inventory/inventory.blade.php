@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
    <div x-data="inventoryData()" x-init="init()" class="p-4">

        {{-- PAGE HEADER --}}
        {{-- Stock In / Stock Out buttons removed — they now live in the
             Inventory nav dropdown and open the dedicated history pages. --}}
        <div class="flex items-center justify-between mb-8">
            <div class="flex-1"></div>
            <h1 class="text-2xl font-bold text-gray-900 text-center">Inventory</h1>
            <div class="flex-1 flex justify-end items-center gap-3">
                <a href="{{ route('sub_one.inventory.stockLevels') }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#7F5539]">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-4 h-4 mr-2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    Stock Levels
                </a>
            </div>
        </div>

        {{-- ═════════════════════════════════════
             STOCK TRANSACTIONS LOG
             Tabs removed — this now shows every
             transaction (in + out) in one list.
             Dedicated Stock In / Stock Out history
             pages are reachable from the eye icon
             and from the nav dropdown.
        ═════════════════════════════════════════ --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Stock Transactions</h2>
                        <p class="text-xs text-gray-400 mt-0.5">All stock in / stock out activity — click the eye icon for full details</p>
                    </div>

                    {{-- Search + Filter --}}
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full lg:w-auto">
                        <div class="relative w-full sm:w-64">
                            <input type="text" x-model="searchQuery" @input.debounce.500ms="txnPage = 1"
                                placeholder="Search by transaction # or processed by..."
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
                            <span x-show="hasModalFilters"
                                class="ml-1.5 inline-flex h-2 w-2 rounded-full bg-[#7F5539]"></span>
                        </button>
                    </div>
                </div>

                {{-- Active Filter Badges (reflect only what was APPLIED, not live modal edits) --}}
                <div x-show="hasModalFilters" x-cloak class="flex flex-wrap items-center justify-end gap-2 mb-2">
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

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction No.</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(txn, index) in pagedTransactions" :key="txn.uuid">
                            <tr :class="[
                                    index % 2 === 0 ? 'bg-white' : 'bg-gray-50',
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
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="txn.type === 'stock_in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                        x-text="txn.type === 'stock_in' ? 'Stock In' : 'Stock Out'"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                        :class="txn.type === 'stock_in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                        x-text="(txn.type === 'stock_in' ? '+' : '−') + (txn.display_qty ?? 0)"></span>
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
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="relative group">
                                            {{-- Eye icon now navigates straight to the matching
                                                 Stock In / Stock Out history page instead of a modal --}}
                                            <button
                                                @click="viewTransactionDetail(txn)"
                                                class="text-[#4A2C1D] hover:text-[#7F5539] transition-colors p-2 rounded-full hover:bg-gray-100">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                </svg>
                                            </button>

                                            <!-- Tooltip -->
                                            <span class="absolute -top-9 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded shadow-md opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-10"
                                                x-text="'View Details'"></span>
                                        </div>

                                        <template x-if="txn.status === 'pending'">
                                            <div class="flex items-center gap-2">
                                                <div class="relative group">
                                                    <button @click="openApproveModal(txn)" class="text-green-600 hover:text-green-800 transition-colors p-2 rounded-full hover:bg-gray-100">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                        </svg>
                                                    </button>
                                                    <span class="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">Approve</span>
                                                </div>
                                                <div class="relative group">
                                                    <button @click="openRejectModal(txn)" class="text-red-600 hover:text-red-800 transition-colors p-2 rounded-full hover:bg-gray-100">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                        </svg>
                                                    </button>
                                                    <span class="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">Reject</span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="!pagedTransactions.length">
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <p class="text-sm font-medium text-gray-900" x-text="hasActiveFilters ? 'No transactions match your filters' : 'Nothing here yet'"></p>
                                    <p class="text-sm text-gray-500" x-text="hasActiveFilters ? 'Try adjusting your search or filters.' : 'Stock-in and stock-out transactions will show up here.'"></p>
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

        {{-- ═════════════════════════════════════
             FILTER MODAL (unchanged)
        ═════════════════════════════════════════ --}}
        <div x-show="showFilters" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showFilters = false; dateError = ''"></div>

                <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl sm:my-8 sm:align-middle max-w-md w-full">

                    <div class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Filter Transactions</h3>
                        <button @click="showFilters = false; dateError = ''"
                            class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="px-6 py-5">
                        <div class="space-y-5">

                            {{-- Date Range --}}
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
                                            <input type="date" x-model="draftFilters.date_from"
                                                @change="dateError = ''"
                                                :class="dateError ? 'border-red-400 focus:ring-red-400 focus:border-red-400' : 'border-gray-300 focus:ring-[#7F5539] focus:border-[#7F5539]'"
                                                class="block w-full border rounded-lg px-3 py-2 text-sm focus:ring-2">
                                        </div>
                                        <div class="flex-1">
                                            <label class="block text-xs text-gray-500 mb-1">To</label>
                                            <input type="date" x-model="draftFilters.date_to"
                                                @change="dateError = (draftFilters.date_from && draftFilters.date_to && new Date(draftFilters.date_to) < new Date(draftFilters.date_from)) ? '\"To\" date cannot be earlier than \"From\" date.' : ''"
                                                :class="dateError ? 'border-red-400 focus:ring-red-400 focus:border-red-400' : 'border-gray-300 focus:ring-[#7F5539] focus:border-[#7F5539]'"
                                                class="block w-full border rounded-lg px-3 py-2 text-sm focus:ring-2">
                                        </div>
                                    </div>
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

                            {{-- Branch --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                                <select x-model="draftFilters.branch_id"
                                    class="block w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] px-3 py-2 text-sm">
                                    <option value="">All Branches</option>
                                    <template x-for="branch in branches" :key="branch.id">
                                        <option :value="branch.id" x-text="branch.branch_name"></option>
                                    </template>
                                </select>
                            </div>

                            {{-- Status --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select x-model="draftFilters.status"
                                    class="block w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] px-3 py-2 text-sm">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>

                        </div>
                    </div>

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

        {{-- VIEW TRANSACTION DETAILS MODAL removed —
             the eye icon now navigates to the Stock In / Stock Out
             history page (see viewTransactionDetail() below). --}}

        {{-- APPROVE MODAL (unchanged) --}}
        <div x-show="showApproveModal" x-cloak
            class="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center p-0 sm:p-4"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="fixed inset-0 bg-gray-500/75" @click="closeApproveModal()"></div>
            <div class="relative z-10 w-full sm:max-w-md bg-white rounded-t-2xl sm:rounded-xl shadow-xl flex flex-col max-h-[92dvh] sm:max-h-[85vh]"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full sm:translate-y-4 sm:opacity-0"
                x-transition:enter-end="translate-y-0 sm:opacity-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-y-0 sm:opacity-100" x-transition:leave-end="translate-y-full sm:translate-y-4 sm:opacity-0">
                <div class="sm:hidden flex justify-center pt-3 pb-1 flex-shrink-0"><div class="w-10 h-1 bg-gray-300 rounded-full"></div></div>
                <div class="px-6 pt-6 pb-6 flex flex-col gap-4">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <div class="text-center">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Approve Stock Out</h3>
                        <p class="text-sm text-gray-500 mb-1">Approve transaction <strong class="text-[#4A2C1D]" x-text="selectedTransaction?.transaction_no"></strong>?</p>
                        <p class="text-sm text-gray-500">This will deduct <strong x-text="selectedTransaction?.display_qty"></strong> item(s) from inventory.</p>
                    </div>
                    <div class="flex gap-3">
                        <button @click="closeApproveModal()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</button>
                        <button @click="confirmApprove()" :disabled="isSubmitting"
                            class="flex-1 px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-green-600 hover:bg-green-700 disabled:opacity-50 transition-colors">
                            <span x-text="isSubmitting ? 'Approving...' : 'Approve'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- REJECT MODAL (unchanged) --}}
        <div x-show="showRejectModal" x-cloak
            class="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center p-0 sm:p-4"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="fixed inset-0 bg-gray-500/75" @click="closeRejectModal()"></div>
            <div class="relative z-10 w-full sm:max-w-md bg-white rounded-t-2xl sm:rounded-xl shadow-xl flex flex-col max-h-[92dvh] sm:max-h-[85vh]"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full sm:translate-y-4 sm:opacity-0"
                x-transition:enter-end="translate-y-0 sm:opacity-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-y-0 sm:opacity-100" x-transition:leave-end="translate-y-full sm:translate-y-4 sm:opacity-0">
                <div class="sm:hidden flex justify-center pt-3 pb-1 flex-shrink-0"><div class="w-10 h-1 bg-gray-300 rounded-full"></div></div>
                <div class="px-6 pt-6 pb-6 flex flex-col gap-4">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </div>
                    <div class="text-center">
                        <h3 class="text-lg font-medium text-gray-900 mb-1">Reject Stock Out</h3>
                        <p class="text-sm text-gray-500">Transaction <strong class="text-[#4A2C1D]" x-text="selectedTransaction?.transaction_no"></strong></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#4A2C1D] mb-1">Reason for rejection</label>
                        <textarea x-model="rejectReason" rows="3" placeholder="e.g. Incorrect quantity declared" class="w-full border-2 border-[#7F5539] rounded px-3 py-2 text-sm"></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button @click="closeRejectModal()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</button>
                        <button @click="confirmReject()" :disabled="isSubmitting"
                            class="flex-1 px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 transition-colors">
                            <span x-text="isSubmitting ? 'Rejecting...' : 'Reject'"></span>
                        </button>
                    </div>
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
                <button @click="showToast = false" class="ml-auto text-white/80 hover:text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('alpine:init', () => {

            Alpine.data('inventoryData', () => ({

                isSubmitting: false,

                // Toast
                showToast:    false,
                toastMessage: '',
                toastType:    'success', // 'success' | 'error'
                toastTimer:   null,

                transactions: @json($transactions ?? []),
                branches:     @json($branches ?? []),

                // Search + Filters
                searchQuery: '',
                filters: {                 // ← APPLIED filters (drive the table + badges)
                    branch_id: '',
                    status:    '',
                    date_from: '',
                    date_to:   '',
                },
                draftFilters: {             // ← in-progress edits inside the modal only
                    branch_id: '',
                    status:    '',
                    date_from: '',
                    date_to:   '',
                },
                datePreset:  'all',
                showFilters: false,
                dateError:   '',

                // Pagination
                txnPage: 1,
                txnPerPage: 10,

                get pendingCount() {
                    return this.transactions.filter(t => t.status === 'pending').length;
                },

                // Tabs removed — this is now the full, unfiltered-by-type list.
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
                        list = list.filter(t =>
                            (t.transaction_no ?? '').toLowerCase().includes(q) ||
                            (t.processed_by ?? '').toLowerCase().includes(q)
                        );
                    }

                    return list;
                },

                get txnTotalPages() {
                    return Math.max(1, Math.ceil(this.filteredTransactions.length / this.txnPerPage));
                },
                get pagedTransactions() {
                    const start = (this.txnPage - 1) * this.txnPerPage;
                    return this.filteredTransactions.slice(start, start + this.txnPerPage);
                },
                get txnShowingFrom() {
                    return this.filteredTransactions.length ? (this.txnPage - 1) * this.txnPerPage + 1 : 0;
                },
                get txnShowingTo() {
                    return Math.min(this.txnPage * this.txnPerPage, this.filteredTransactions.length);
                },
                get txnPageNumbers() {
                    return Array.from({ length: this.txnTotalPages }, (_, i) => i + 1);
                },

                get hasModalFilters() {
                    return this.filters.branch_id !== ''
                        || this.filters.status !== ''
                        || this.filters.date_from !== ''
                        || this.filters.date_to !== '';
                },
                get hasActiveFilters() {
                    return this.hasModalFilters || this.searchQuery.trim() !== '';
                },
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

                showApproveModal: false,
                showRejectModal:  false,

                selectedTransaction: null,
                rejectReason:        '',

                init() {},

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

                addBodyClass()    { document.body.classList.add('modal-open'); },
                removeBodyClass() { document.body.classList.remove('modal-open'); },

                showToastMsg(message, type = 'success') {
                    this.toastMessage = message;
                    this.toastType = type;
                    this.showToast = true;
                    clearTimeout(this.toastTimer);
                    this.toastTimer = setTimeout(() => { this.showToast = false; }, 3500);
                },

                // ── Open modal: seed the draft from whatever is currently applied ──
                openFilterModal() {
                    this.draftFilters = { ...this.filters };
                    this.datePreset   = (this.draftFilters.date_from || this.draftFilters.date_to) ? 'custom' : 'all';
                    this.dateError    = '';
                    this.showFilters  = true;
                },

                // ── Date preset (edits the draft only — nothing applied yet) ──
                setDatePreset(preset) {
                    this.datePreset = preset;
                    const today = new Date();
                    const fmt = d => {
                        const y = d.getFullYear();
                        const m = String(d.getMonth() + 1).padStart(2, '0');
                        const day = String(d.getDate()).padStart(2, '0');
                        return `${y}-${m}-${day}`;
                    };

                    if (preset === 'week') {
                        const from = new Date(today);
                        from.setDate(today.getDate() - 6);
                        this.draftFilters.date_from = fmt(from);
                        this.draftFilters.date_to   = fmt(today);
                    } else if (preset === 'month') {
                        const from = new Date(today);
                        from.setDate(today.getDate() - 29);
                        this.draftFilters.date_from = fmt(from);
                        this.draftFilters.date_to   = fmt(today);
                    } else if (preset === 'custom') {
                        this.draftFilters.date_from = '';
                        this.draftFilters.date_to   = '';
                    }
                },

                applyFilters() {
                    if (this.draftFilters.date_from && this.draftFilters.date_to) {
                        if (new Date(this.draftFilters.date_to) < new Date(this.draftFilters.date_from)) {
                            this.dateError = '"To" date cannot be earlier than "From" date.';
                            return;
                        }
                    }
                    this.dateError   = '';
                    this.filters     = { ...this.draftFilters };
                    this.showFilters = false;
                    this.txnPage     = 1;
                },

                clearModalFilters() {
                    this.draftFilters = { branch_id: '', status: '', date_from: '', date_to: '' };
                    this.datePreset   = 'all';
                    this.dateError    = '';
                },

                clearAllFilters() {
                    this.searchQuery  = '';
                    this.filters      = { branch_id: '', status: '', date_from: '', date_to: '' };
                    this.draftFilters = { ...this.filters };
                    this.datePreset   = 'all';
                    this.showFilters  = false;
                    this.txnPage      = 1;
                },

                removeFilter(filterKey) {
                    if (filterKey === 'date') {
                        this.filters.date_from = '';
                        this.filters.date_to   = '';
                    } else {
                        this.filters[filterKey] = '';
                    }
                    this.draftFilters = { ...this.filters };
                    this.txnPage = 1;
                },

                async refreshData() {
                    try {
                        const response = await fetch(`?ajax=true`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                        if (!response.ok) throw new Error('Network error');
                        const data = await response.json();
                        if (data.success) {
                            this.transactions = data.transactions;
                            this.txnPage       = 1;
                        }
                    } catch (e) {
                        alert('Failed to refresh inventory data. Please try again.');
                    }
                },

                // ── Eye icon: send the user to the matching history page,
                //    highlighting this transaction there instead of a modal ──
                viewTransactionDetail(txn) {
                    const base = txn.type === 'stock_in'
                        ? '{{ route('sub_one.inventory.stockInHistory') }}'
                        : '{{ route('sub_one.inventory.stockOutHistory') }}';
                    window.location.href = `${base}?highlight=${txn.uuid}`;
                },

                openApproveModal(txn) { this.selectedTransaction = txn; this.showApproveModal = true; this.addBodyClass(); },
                closeApproveModal()   { this.showApproveModal = false; this.selectedTransaction = null; this.removeBodyClass(); },

                async confirmApprove() {
                    if (this.isSubmitting || !this.selectedTransaction) return;
                    this.isSubmitting = true;
                    const txnNo = this.selectedTransaction.transaction_no;
                    try {
                        const response = await fetch(`{{ url('sub_one/inventory') }}/${this.selectedTransaction.uuid}/approve`, {
                            method: 'PATCH',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.closeApproveModal();
                            await this.refreshData();
                            this.showToastMsg(`${txnNo} approved. Inventory updated.`, 'success');
                        }
                        else throw new Error(data.message || 'Approval failed');
                    } catch (e) { this.showToastMsg(e.message || 'Failed to approve. Please try again.', 'error');
                    } finally { this.isSubmitting = false; }
                },

                openRejectModal(txn) { this.selectedTransaction = txn; this.rejectReason = ''; this.showRejectModal = true; this.addBodyClass(); },
                closeRejectModal()   { this.showRejectModal = false; this.selectedTransaction = null; this.rejectReason = ''; this.removeBodyClass(); },

                async confirmReject() {
                    if (this.isSubmitting || !this.selectedTransaction) return;
                    if (!this.rejectReason.trim()) { alert('Please enter a reason for rejection.'); return; }
                    this.isSubmitting = true;
                    const txnNo = this.selectedTransaction.transaction_no;
                    try {
                        const response = await fetch(`{{ url('sub_one/inventory') }}/${this.selectedTransaction.uuid}/reject`, {
                            method: 'PATCH',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                            body: JSON.stringify({ rejected_reason: this.rejectReason }),
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.closeRejectModal();
                            await this.refreshData();
                            this.showToastMsg(`${txnNo} rejected.`, 'error');
                        }
                        else throw new Error(data.message || 'Rejection failed');
                    } catch (e) { this.showToastMsg(e.message || 'Failed to reject. Please try again.', 'error');
                    } finally { this.isSubmitting = false; }
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