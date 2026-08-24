@extends('layouts.app')

@section('title', 'Inventory Report')

@section('content')
<div x-data="inventoryReportData()" x-init="init()" class="p-4">

    <h1 class="text-2xl font-bold text-gray-900 mt-4 mb-6 text-center">Inventory Report</h1>

    {{-- ══════════════════════════════════════════
         SHARED TOGGLE BAR
    ══════════════════════════════════════════════ --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-2 mb-6">
        <div class="flex gap-1 overflow-x-auto">

            <a href="{{ route('sub_two.reports.my_report') }}"
                class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-all whitespace-nowrap flex items-center gap-2
                       text-[#7F5539] hover:bg-[#7F5539]/10">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Sales
            </a>

            <a href="{{ route('sub_two.reports.inventory_report') }}"
                class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-all whitespace-nowrap flex items-center gap-2
                       bg-[#7F5539] text-white shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                Inventory
            </a>

            <a href="{{ route('sub_two.reports.feedback_report') }}"
                class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-all whitespace-nowrap flex items-center gap-2
                       text-[#7F5539] hover:bg-[#7F5539]/10">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
                Ratings
            </a>

        </div>
    </div>

    {{-- Staff Info Banner --}}
    <div class="bg-[#7F5539]/5 border border-[#7F5539]/20 rounded-lg px-4 py-3 mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-[#7F5539]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span class="text-sm text-gray-700">
                <span class="font-medium">Branch:</span> {{ $branch->branch_name ?? 'N/A' }}
                <span class="mx-2 text-gray-300">|</span>
                <span class="font-medium">Staff:</span> {{ auth()->guard('staff')->user()->first_name ?? '' }} {{ auth()->guard('staff')->user()->last_name ?? '' }}
            </span>
        </div>
        <span class="text-xs text-gray-500">Showing data for your assigned branch</span>
    </div>

    {{-- ══════════════════════════════════════════
         FILTERS BAR
    ══════════════════════════════════════════════ --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-end gap-4">

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider">Quick Range</label>
                <div class="flex gap-2">
                    <button @click="setPreset('week')"
                        :class="activePreset === 'week' ? 'bg-[#7F5539] text-white border-[#7F5539]' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                        class="px-3 py-2 border rounded-lg text-sm font-medium transition-colors">Last 7 Days</button>
                    <button @click="setPreset('month')"
                        :class="activePreset === 'month' ? 'bg-[#7F5539] text-white border-[#7F5539]' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                        class="px-3 py-2 border rounded-lg text-sm font-medium transition-colors">Last 30 Days</button>
                    <button @click="setPreset('custom')"
                        :class="activePreset === 'custom' ? 'bg-[#7F5539] text-white border-[#7F5539]' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                        class="px-3 py-2 border rounded-lg text-sm font-medium transition-colors">Custom</button>
                </div>
            </div>

            <div x-show="activePreset === 'custom'" x-cloak class="relative">
                <div class="flex gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider">From</label>
                        <input type="date" x-model="filters.date_from" @change="dateError = ''"
                            :class="dateError ? 'border-red-400' : 'border-gray-300 focus:ring-[#7F5539] focus:border-[#7F5539]'"
                            class="border rounded-lg px-3 py-2 text-sm focus:ring-2">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider">To</label>
                        <input type="date" x-model="filters.date_to"
                            @change="dateError = (filters.date_from && filters.date_to && new Date(filters.date_to) < new Date(filters.date_from)) ? '\"To\" date cannot be earlier than \"From\" date.' : ''"
                            :class="dateError ? 'border-red-400' : 'border-gray-300 focus:ring-[#7F5539] focus:border-[#7F5539]'"
                            class="border rounded-lg px-3 py-2 text-sm focus:ring-2">
                    </div>
                </div>
                <div x-show="dateError" class="absolute left-0 top-full mt-1 flex items-center gap-1.5 text-red-600 text-xs whitespace-nowrap">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span x-text="dateError"></span>
                </div>
            </div>

            <div class="flex gap-2">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider invisible">Action</label>
                    <button @click="fetchReport()" :disabled="isLoading || !!dateError"
                        class="inline-flex items-center px-5 py-2 bg-[#7F5539] hover:bg-[#4A2C1D] text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-60">
                        <svg x-show="isLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="isLoading ? 'Loading...' : 'Generate Report'"></span>
                    </button>
                </div>

                {{-- Export PDF Button --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider invisible">Export</label>
                    <button @click="exportPDF()" :disabled="isLoading || !byBranch.length"
                        class="inline-flex items-center px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export PDF
                    </button>
                </div>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-3"
            x-text="`Showing data from ${formatDate(filters.date_from)} to ${formatDate(filters.date_to)}`"></p>
    </div>

    {{-- ══════════════════════════════════════════
         TOP SUMMARY CARDS (4-up)
    ══════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Net Movement</p>
            <p class="text-2xl font-bold"
                :class="netMovement >= 0 ? 'text-emerald-600' : 'text-red-600'"
                x-text="(netMovement >= 0 ? '+' : '') + netMovement"></p>
            <p class="text-xs text-gray-400 mt-1">stock in minus stock out</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Total Stock In</p>
            <p class="text-2xl font-bold text-emerald-600"
                x-text="byBranch.length ? '+' + summary.total_stock_in : '—'"></p>
            <p class="text-xs text-gray-400 mt-1"
                x-text="byBranch.length ? summary.stock_in_txn_count + ' transaction(s)' : 'no data'"></p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Total Stock Out</p>
            <p class="text-2xl font-bold text-red-600"
                x-text="byBranch.length ? '−' + summary.total_stock_out : '—'"></p>
            <p class="text-xs text-gray-400 mt-1">sold · damaged · expired · pulled out</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Ending Balance</p>
            <p class="text-2xl font-bold text-gray-900"
                x-text="byBranch.length ? summary.ending_balance : '—'"></p>
            <p class="text-xs text-gray-400 mt-1">current items on hand</p>
        </div>

    </div>

    {{-- ══════════════════════════════════════════
         EMPTY STATE
    ══════════════════════════════════════════════ --}}
    <div x-show="!byBranch.length && !isLoading"
        class="bg-white rounded-lg shadow-sm border border-gray-200 px-6 py-16 text-center text-gray-400 mb-6">
        <svg class="mx-auto h-12 w-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        </svg>
        <p class="text-sm font-semibold text-gray-500">No report generated yet</p>
        <p class="text-xs text-gray-400 mt-1">Select a date range and click <span class="font-medium text-[#7F5539]">Generate Report</span> to view inventory data.</p>
    </div>

    {{-- ══════════════════════════════════════════
         PER-BRANCH CARDS
    ══════════════════════════════════════════════ --}}
    <template x-for="branch in byBranch" :key="branch.branch_name">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-5 overflow-hidden">

            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="h-9 w-9 rounded-full bg-[#7F5539]/10 flex items-center justify-center flex-shrink-0">
                        <svg class="h-5 w-5 text-[#7F5539]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900" x-text="branch.branch_name"></h2>
                        <p class="text-xs text-gray-400"
                            x-text="(branch.product_count ?? 0) + ' product(s) · ' + (branch.ingredient_count ?? 0) + ' ingredient(s)'"></p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
                        :class="((branch.total_stock_in ?? 0) - (branch.total_stock_out ?? 0)) >= 0
                            ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'"
                        x-text="'Net ' + (((branch.total_stock_in ?? 0) - (branch.total_stock_out ?? 0)) >= 0 ? '+' : '') + ((branch.total_stock_in ?? 0) - (branch.total_stock_out ?? 0))">
                    </span>
                </div>
            </div>

            <template x-if="(branch.total_stock_in ?? 0) === 0 && (branch.total_stock_out ?? 0) === 0">
                <div class="px-5 py-8 text-center">
                    <p class="text-sm text-gray-400">No inventory movement recorded for this branch in the selected period.</p>
                    <p class="text-xs text-gray-300 mt-1">Beginning balance: <span class="font-medium text-gray-400" x-text="(branch.beginning_balance ?? 0) + ' items'"></span></p>
                </div>
            </template>

            <template x-if="(branch.total_stock_in ?? 0) > 0 || (branch.total_stock_out ?? 0) > 0">
                <div>
                    <div class="grid grid-cols-3 divide-x divide-gray-100 border-b border-gray-100">
                        <div class="px-5 py-4 text-center">
                            <p class="text-xs text-gray-400 mb-1">Beginning</p>
                            <p class="text-xl font-bold text-gray-800" x-text="branch.beginning_balance ?? 0"></p>
                            <p class="text-xs text-gray-400">items</p>
                        </div>
                        <div class="px-5 py-4 text-center">
                            <p class="text-xs text-gray-400 mb-1">Movement</p>
                            <p class="text-sm font-semibold text-emerald-600"
                                x-text="'+' + (branch.total_stock_in ?? 0) + ' in'"></p>
                            <p class="text-sm font-semibold text-red-500 mt-0.5"
                                x-text="'−' + (branch.total_stock_out ?? 0) + ' out'"></p>
                        </div>
                        <div class="px-5 py-4 text-center">
                            <p class="text-xs text-gray-400 mb-1">Ending</p>
                            <p class="text-xl font-bold text-gray-800" x-text="branch.ending_balance ?? 0"></p>
                            <p class="text-xs text-gray-400">items</p>
                        </div>
                    </div>

                    <div class="px-5 pt-4 pb-2">
                        <div class="flex justify-between text-xs text-gray-400 mb-1">
                            <span>Stock flow</span>
                            <span x-text="'In ' + (branch.total_stock_in ?? 0) + '  ·  Out ' + (branch.total_stock_out ?? 0)"></span>
                        </div>
                        <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500"
                                :class="((branch.total_stock_in ?? 0) >= (branch.total_stock_out ?? 0)) ? 'bg-emerald-400' : 'bg-red-400'"
                                :style="`width: ${flowBarWidth(branch)}%`"></div>
                        </div>
                        <div class="flex justify-between text-xs mt-1">
                            <span class="text-emerald-600 font-medium" x-text="(branch.stock_in_txn_count ?? 0) + ' stock-in txn(s)'"></span>
                            <span class="text-gray-400" x-text="turnoverRate(branch) + '% turnover'"></span>
                        </div>
                    </div>

                    <template x-if="(branch.total_stock_out ?? 0) > 0">
                        <div class="mx-5 mb-4 mt-2 rounded-lg border border-gray-100 overflow-hidden">
                            <div class="bg-gray-50 px-4 py-2 flex items-center justify-between">
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Stock-out breakdown</span>
                                <span class="text-xs font-semibold text-red-500" x-text="'−' + (branch.total_stock_out ?? 0) + ' total'"></span>
                            </div>
                            <div class="divide-y divide-gray-50">
                                <template x-if="(branch.total_sold ?? 0) > 0">
                                    <div class="flex items-center justify-between px-4 py-2.5">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-blue-400 flex-shrink-0"></span>
                                            <span class="text-sm text-gray-600">Sold</span>
                                        </div>
                                        <span class="text-sm font-semibold text-gray-800" x-text="'−' + (branch.total_sold ?? 0)"></span>
                                    </div>
                                </template>
                                <template x-if="(branch.total_damaged ?? 0) > 0">
                                    <div class="flex items-center justify-between px-4 py-2.5">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-orange-400 flex-shrink-0"></span>
                                            <span class="text-sm text-gray-600">Damaged</span>
                                        </div>
                                        <span class="text-sm font-semibold text-gray-800" x-text="'−' + (branch.total_damaged ?? 0)"></span>
                                    </div>
                                </template>
                                <template x-if="(branch.total_expired ?? 0) > 0">
                                    <div class="flex items-center justify-between px-4 py-2.5">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-yellow-400 flex-shrink-0"></span>
                                            <span class="text-sm text-gray-600">Expired</span>
                                        </div>
                                        <span class="text-sm font-semibold text-gray-800" x-text="'−' + (branch.total_expired ?? 0)"></span>
                                    </div>
                                </template>
                                <template x-if="(branch.total_pulled_out ?? 0) > 0">
                                    <div class="flex items-center justify-between px-4 py-2.5">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-gray-400 flex-shrink-0"></span>
                                            <span class="text-sm text-gray-600">Pulled out</span>
                                        </div>
                                        <span class="text-sm font-semibold text-gray-800" x-text="'−' + (branch.total_pulled_out ?? 0)"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                </div>
            </template>

        </div>
    </template>

    {{-- ══════════════════════════════════════════
         TRANSACTION LOG
    ══════════════════════════════════════════════ --}}
    <div x-show="byBranch.length" class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Transaction Log</h2>
                    <p class="text-xs text-gray-400 mt-0.5">All approved transactions within the selected period — click Items to expand</p>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700"
                    x-text="txnLog.length + ' transaction(s)'"></span>
            </div>
            <div class="flex gap-0 border-b border-gray-200 -mb-4">
                <template x-for="tab in txnTabs" :key="tab.key">
                    <button @click="switchTxnTab(tab.key)"
                        class="relative px-4 py-2 text-sm font-medium transition-colors focus:outline-none"
                        :class="activeTxnTab === tab.key
                            ? 'text-[#7F5539] border-b-2 border-[#7F5539]'
                            : 'text-gray-500 hover:text-gray-700'">
                        <span x-text="tab.label"></span>
                        <span class="ml-1.5 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-medium rounded-full"
                            :class="activeTxnTab === tab.key ? 'bg-[#7F5539]/10 text-[#7F5539]' : 'bg-gray-100 text-gray-500'"
                            x-text="tab.key === 'all'
                                ? txnLog.length
                                : txnLog.filter(t => t.type === tab.key).length">
                        </span>
                    </button>
                </template>
            </div>
        </div>

        <div x-show="!pagedTxnLog.length" class="px-6 py-10 text-center text-sm text-gray-400">
            No transactions found for this filter.
        </div>

        <div class="overflow-x-auto" x-show="pagedTxnLog.length">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction No.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Processed By</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                    </tr>
                </thead>

                <template x-for="(txn, index) in pagedTxnLog" :key="txn.uuid">
                    <tbody>
                        <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'" class="hover:bg-[#7F5539]/5 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="txn.transaction_no"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700" x-text="txn.branch_name ?? '—'"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900" x-text="formatDate(txn.created_at)"></div>
                                <div class="text-xs text-gray-400" x-text="formatTime(txn.created_at)"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="txn.type === 'stock_in' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'"
                                    x-text="txn.type === 'stock_in' ? 'Stock In' : 'Stock Out'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                    :class="txn.type === 'stock_in' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'"
                                    x-text="(txn.type === 'stock_in' ? '+' : '−') + (txn.total_quantity ?? 0)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <template x-if="txn.type === 'stock_out' && txn.reason">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700"
                                        x-text="reasonLabel(txn.reason)"></span>
                                </template>
                                <template x-if="!(txn.type === 'stock_out' && txn.reason)">
                                    <span class="text-xs text-gray-400">—</span>
                                </template>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700" x-text="txn.processed_by ?? '—'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <button @click="toggleTxnRow(txn.transaction_no)"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border transition-colors"
                                    :class="expandedTxnRows[txn.transaction_no]
                                        ? 'bg-[#7F5539] text-white border-[#7F5539]'
                                        : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'">
                                    <span x-text="(txn.items_count ?? itemsByTxn(txn.transaction_no).length) + ' item(s)'"></span>
                                    <svg class="w-3.5 h-3.5 flex-shrink-0 transition-transform duration-200"
                                        :class="expandedTxnRows[txn.transaction_no] ? 'rotate-180' : ''"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </button>
                            </td>
                        </tr>

                        <tr x-show="expandedTxnRows[txn.transaction_no]" x-cloak>
                            <td colspan="8" class="px-6 pb-4 pt-0 bg-[#faf7f4]">
                                <div class="rounded-lg border border-gray-200 overflow-hidden bg-white mt-1">
                                    <table class="min-w-full text-xs">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Item Type</th>
                                                <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Item Name</th>
                                                <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                                <th x-show="txn.type === 'stock_out'" class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <template x-for="(item, i) in itemsByTxn(txn.transaction_no)" :key="i">
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-2 whitespace-nowrap">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                            :class="item.item_type === 'ingredient' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'"
                                                            x-text="item.item_type === 'ingredient' ? 'Ingredient' : 'Product'"></span>
                                                    </td>
                                                    <td class="px-4 py-2 text-gray-800" x-text="item.item_name ?? '—'"></td>
                                                    <td class="px-4 py-2 whitespace-nowrap text-gray-700"
                                                        x-text="(txn.type === 'stock_in' ? '+' : '−') + (item.quantity ?? 0) + (item.unit ? ' ' + item.unit : '')"></td>
                                                    <td x-show="txn.type === 'stock_out'" class="px-4 py-2 whitespace-nowrap text-gray-600" x-text="reasonLabel(item.reason)"></td>
                                                </tr>
                                            </template>
                                            <template x-if="!itemsByTxn(txn.transaction_no).length">
                                                <tr>
                                                    <td colspan="4" class="px-4 py-4 text-center text-gray-400">No items found for this transaction.</td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </template>
            </table>
        </div>

        {{-- Pagination --}}
        <div x-show="txnPagination.last_page > 1" class="px-4 sm:px-6 py-4 border-t border-gray-200">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="text-sm text-gray-700">
                    Showing
                    <span x-text="txnPagination.from"></span> to
                    <span x-text="txnPagination.to"></span> of
                    <span x-text="txnPagination.total"></span> entries
                </div>
                <div class="flex flex-wrap justify-center items-center gap-2">
                    <button @click="changeTxnPage(txnPagination.current_page - 1)"
                        :disabled="txnPagination.current_page === 1"
                        class="px-3 py-1 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Previous
                    </button>
                    <template x-for="page in txnPaginationLinks" :key="page">
                        <button @click="changeTxnPage(page)"
                            class="px-3 py-1 border rounded-lg text-sm font-medium transition-colors"
                            :class="page === txnPagination.current_page
                                ? 'border-2 border-[#4A2C1D] bg-[#7F5539]/80 text-white'
                                : 'border-gray-300 text-gray-700 hover:bg-gray-50'"
                            :disabled="page === '...'"
                            x-text="page">
                        </button>
                    </template>
                    <button @click="changeTxnPage(txnPagination.current_page + 1)"
                        :disabled="txnPagination.current_page === txnPagination.last_page"
                        class="px-3 py-1 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('inventoryReportData', () => ({

        byBranch:  [],
        txnLog:    [],
        itemsLog:  [],
        isLoading: false,
        dateError: '',
        activePreset: 'week',

        activeTxnTab: 'all',
        txnTabs: [
            { key: 'all',       label: 'All' },
            { key: 'stock_in',  label: 'Stock In' },
            { key: 'stock_out', label: 'Stock Out' },
        ],
        txnCurrentPage:    1,
        txnPerPage:        15,
        txnPagination:     { current_page: 1, last_page: 1, from: 0, to: 0, total: 0 },
        txnPaginationLinks: [],
        expandedTxnRows: {},

        filters: { date_from: '', date_to: '' },

        get filteredTxnLog() {
            if (this.activeTxnTab === 'all') return this.txnLog;
            return this.txnLog.filter(t => t.type === this.activeTxnTab);
        },

        get pagedTxnLog() {
            const start = (this.txnCurrentPage - 1) * this.txnPerPage;
            return this.filteredTxnLog.slice(start, start + this.txnPerPage);
        },

        get summary() {
            if (!this.byBranch.length) {
                return { beginning_balance: 0, total_stock_in: 0, total_stock_out: 0, ending_balance: 0, stock_in_txn_count: 0 };
            }
            return {
                beginning_balance:  this.byBranch.reduce((s, b) => s + (b.beginning_balance ?? 0), 0),
                total_stock_in:     this.byBranch.reduce((s, b) => s + (b.total_stock_in ?? 0),    0),
                total_stock_out:    this.byBranch.reduce((s, b) => s + (b.total_stock_out ?? 0),   0),
                ending_balance:     this.byBranch.reduce((s, b) => s + (b.ending_balance ?? 0),    0),
                stock_in_txn_count: this.byBranch.reduce((s, b) => s + (b.stock_in_txn_count ?? 0), 0),
            };
        },

        get netMovement() {
            return (this.summary.total_stock_in ?? 0) - (this.summary.total_stock_out ?? 0);
        },

        flowBarWidth(branch) {
            const inQty  = branch.total_stock_in  ?? 0;
            const outQty = branch.total_stock_out ?? 0;
            const total  = inQty + outQty;
            if (total === 0) return 0;
            return Math.round((inQty / total) * 100);
        },

        turnoverRate(branch) {
            const begin = branch.beginning_balance ?? 0;
            const out   = branch.total_stock_out   ?? 0;
            if (begin === 0) return out > 0 ? 100 : 0;
            return Math.min(Math.round((out / begin) * 100), 999);
        },

        buildPaginationLinks(currentPage, lastPage) {
            const delta = 2;
            const range = [], result = [];
            for (let i = 1; i <= lastPage; i++) {
                if (i === 1 || i === lastPage || (i >= currentPage - delta && i <= currentPage + delta)) {
                    range.push(i);
                }
            }
            let prev = 0;
            for (let i of range) {
                if (prev && i - prev === 2) result.push(prev + 1);
                else if (prev && i - prev !== 1) result.push('...');
                result.push(i);
                prev = i;
            }
            return result;
        },

        updateTxnPaginationLinks() {
            const total    = this.filteredTxnLog.length;
            const lastPage = Math.max(1, Math.ceil(total / this.txnPerPage));
            const from     = total === 0 ? 0 : (this.txnCurrentPage - 1) * this.txnPerPage + 1;
            const to       = Math.min(this.txnCurrentPage * this.txnPerPage, total);
            this.txnPagination = { current_page: this.txnCurrentPage, last_page: lastPage, from, to, total };
            this.txnPaginationLinks = this.buildPaginationLinks(this.txnCurrentPage, lastPage);
        },

        switchTxnTab(tab) {
            this.activeTxnTab   = tab;
            this.txnCurrentPage = 1;
            this.updateTxnPaginationLinks();
        },

        changeTxnPage(page) {
            if (page === '...' || page < 1 || page > this.txnPagination.last_page) return;
            this.txnCurrentPage = page;
            this.updateTxnPaginationLinks();
        },

        itemsByTxn(transactionNo) {
            return this.itemsLog.filter(i => i.transaction_no === transactionNo);
        },

        toggleTxnRow(transactionNo) {
            this.expandedTxnRows = {
                ...this.expandedTxnRows,
                [transactionNo]: !this.expandedTxnRows[transactionNo],
            };
        },

        init() {
            this.setPreset('week');
            this.fetchReport();
        },

        setPreset(preset) {
            this.activePreset = preset;
            this.dateError    = '';
            const today = new Date();
            const fmt   = d => {
                const y   = d.getFullYear();
                const m   = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${y}-${m}-${day}`;
            };
            if (preset === 'week') {
                const from = new Date(today); from.setDate(today.getDate() - 6);
                this.filters.date_from = fmt(from);
                this.filters.date_to   = fmt(today);
            } else if (preset === 'month') {
                const from = new Date(today); from.setDate(today.getDate() - 29);
                this.filters.date_from = fmt(from);
                this.filters.date_to   = fmt(today);
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

        reasonLabel(r) {
            return { expired: 'Expired', damaged: 'Damaged', pulled_out: 'Pulled out', sold: 'Sold' }[r] ?? r ?? '—';
        },

        async fetchReport() {
            if (this.dateError) return;
            if (this.filters.date_from && this.filters.date_to) {
                if (new Date(this.filters.date_to) < new Date(this.filters.date_from)) {
                    this.dateError = '"To" date cannot be earlier than "From" date.';
                    return;
                }
            }
            this.isLoading = true;
            this.byBranch  = [];
            this.txnLog    = [];
            this.itemsLog  = [];
            try {
                const params = new URLSearchParams({
                    date_from: this.filters.date_from,
                    date_to:   this.filters.date_to,
                    ajax:      'true',
                    type:      'inventory',
                });
                const res  = await fetch(
                    `{{ route('sub_two.reports.inventory_report') }}?${params}`,
                    { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }
                );
                const data = await res.json();
                if (data.success) {
                    this.byBranch = data.by_branch;
                    this.txnLog   = data.transactions ?? [];
                    this.itemsLog = data.items        ?? [];

                    this.txnCurrentPage  = 1;
                    this.expandedTxnRows = {};
                    this.updateTxnPaginationLinks();
                } else {
                    console.error('Inventory report failed:', data);
                }
            } catch (e) {
                console.error('Inventory report error:', e);
            } finally {
                this.isLoading = false;
            }
        },

        exportPDF() {
            if (!this.byBranch.length) {
                alert('No data to export. Please generate the report first.');
                return;
            }
            
            const params = new URLSearchParams({
                date_from: this.filters.date_from,
                date_to: this.filters.date_to,
            });
            
            window.open(`{{ route('sub_two.reports.export_inventory_pdf') }}?${params}`, '_blank');
        },

    }));
});
</script>

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection