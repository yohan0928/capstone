@extends('layouts.app')

@section('title', 'Ratings & Feedback Report')

@section('content')
<div x-data="reportData()" x-init="init()" class="p-4">

    <h1 class="text-2xl font-bold text-gray-900 mt-4 mb-6 text-center">Ratings &amp; Feedback Report</h1>

    {{-- ══════════════════════════════════════════
         SHARED TOGGLE BAR
    ══════════════════════════════════════════════ --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-2 mb-6">
        <div class="flex gap-1 overflow-x-auto">

            {{-- Sales --}}
            <a href="{{ route('sub_one.reports.branch_report') }}"
                class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-all whitespace-nowrap flex items-center gap-2
                       text-[#7F5539] hover:bg-[#7F5539]/10">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Sales
            </a>

            {{-- Inventory --}}
            <a href="{{ route('sub_one.reports.inventory_report') }}"
                class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-all whitespace-nowrap flex items-center gap-2
                       text-[#7F5539] hover:bg-[#7F5539]/10">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                Inventory
            </a>

            {{-- Ratings — active on this page --}}
            <a href="{{ route('sub_one.reports.feedback_report') }}"
                class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-all whitespace-nowrap flex items-center gap-2
                       bg-[#7F5539] text-white shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
                Ratings
            </a>

        </div>
    </div>


    {{-- ══════════════════════════════════════════
         RATINGS REPORT CONTENT
    ══════════════════════════════════════════════ --}}

    {{-- ── FILTERS BAR ── --}}
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
                        <input type="date" x-model="filters.date_from"
                            @change="dateError = ''"
                            :class="dateError ? 'border-red-400 focus:ring-red-400 focus:border-red-400' : 'border-gray-300 focus:ring-[#7F5539] focus:border-[#7F5539]'"
                            class="border rounded-lg px-3 py-2 text-sm focus:ring-2">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider">To</label>
                        <input type="date" x-model="filters.date_to"
                            @change="dateError = (filters.date_from && filters.date_to && new Date(filters.date_to) < new Date(filters.date_from)) ? 'To date cannot be earlier than From date.' : ''"
                            :class="dateError ? 'border-red-400 focus:ring-red-400 focus:border-red-400' : 'border-gray-300 focus:ring-[#7F5539] focus:border-[#7F5539]'"
                            class="border rounded-lg px-3 py-2 text-sm focus:ring-2">
                    </div>
                </div>
                <div x-show="dateError"
                    class="absolute left-0 top-full mt-1 flex items-center gap-1.5 text-red-600 text-xs whitespace-nowrap">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    <span x-text="dateError"></span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider">Branch</label>
                <select x-model="filters.branch_id"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] min-w-[160px]">
                    <option value="">All Branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider invisible">Action</label>
                    <button @click="fetchReport()"
                        :disabled="isLoading"
                        class="inline-flex items-center px-5 py-2 bg-[#7F5539] hover:bg-[#4A2C1D] text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-60">
                        <svg x-show="isLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="isLoading ? 'Loading...' : 'Generate Report'"></span>
                    </button>
                </div>

                {{-- PDF Export Button --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider invisible">Export</label>
                    <button @click="exportPDF()"
                        :disabled="isLoading || !byBranch.length"
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

    {{-- ── TOP SUMMARY CARDS ── --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-[#7F5539]/10 p-3 rounded-lg">
                    <svg class="h-6 w-6 text-[#7F5539]" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Overall Avg Rating</p>
                    <p class="text-2xl font-semibold text-gray-900" x-text="overallAvgRating + '/5.0'"></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-100 p-3 rounded-lg">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Feedbacks</p>
                    <p class="text-2xl font-semibold text-gray-900" x-text="totalFeedbacks"></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-100 p-3 rounded-lg">
                    <svg class="h-6 w-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Branches Covered</p>
                    <p class="text-2xl font-semibold text-gray-900" x-text="byBranch.length"></p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── BY BRANCH SECTION ── --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Rating Summary by Branch</h2>
            <p class="text-sm text-gray-500 mt-1">Average star ratings and feedback volume per branch</p>
        </div>
        <div x-show="!byBranch.length && !isLoading" class="px-6 py-12 text-center text-gray-400">
            <svg class="mx-auto h-10 w-10 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-sm">Click "Generate Report" to load data.</p>
        </div>
        <div class="overflow-x-auto" x-show="byBranch.length">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Rating</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Feedbacks</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-64">Star Distribution</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="branch in byBranch" :key="branch.branch_name">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="h-8 w-8 rounded-full bg-[#7F5539]/10 flex items-center justify-center">
                                        <svg class="h-4 w-4 text-[#7F5539]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <span class="text-sm font-medium text-gray-900" x-text="branch.branch_name"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="flex">
                                        <template x-for="i in 5" :key="i">
                                            <svg :class="i <= Math.round(branch.avg_rating) ? 'text-yellow-400' : 'text-gray-300'"
                                                class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                        </template>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900" x-text="branch.avg_rating + '/5'"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                    x-text="branch.total + ' reviews'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-1 w-56">
                                    <template x-for="star in [5,4,3,2,1]" :key="star">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-gray-500 w-4" x-text="star"></span>
                                            <div class="flex-1 bg-gray-200 rounded-full h-1.5">
                                                <div class="bg-yellow-400 h-1.5 rounded-full transition-all duration-500"
                                                    :style="`width: ${branch.total ? (branch.star_distribution[star] / branch.total * 100) : 0}%`"></div>
                                            </div>
                                            <span class="text-xs text-gray-400 w-6 text-right" x-text="branch.star_distribution[star]"></span>
                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── BY SERVICE CATEGORY SECTION ── --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Rating Summary by Service Category</h2>
            <p class="text-sm text-gray-500 mt-1">Average ratings per service type with AI-generated feedback summaries</p>
        </div>
        <div x-show="!byCategory.length && !isLoading" class="px-6 py-12 text-center text-gray-400">
            <svg class="mx-auto h-10 w-10 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-sm">Click "Generate Report" to load data.</p>
        </div>
        <div class="divide-y divide-gray-100" x-show="byCategory.length">
            <template x-for="(cat, index) in byCategory" :key="cat.category_name">
                <div class="p-6">
                    <div class="flex flex-col lg:flex-row lg:items-start gap-6">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="h-9 w-9 rounded-lg bg-[#7F5539]/10 flex items-center justify-center">
                                    <svg class="h-5 w-5 text-[#7F5539]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900" x-text="cat.category_name"></h3>
                                    <p class="text-xs text-gray-500" x-text="cat.total + ' feedbacks'"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 mb-4">
                                <div class="flex">
                                    <template x-for="i in 5" :key="i">
                                        <svg :class="i <= Math.round(cat.avg_rating) ? 'text-yellow-400' : 'text-gray-300'"
                                            class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    </template>
                                </div>
                                <span class="text-sm font-bold text-gray-900" x-text="cat.avg_rating + ' / 5.0'"></span>
                                <span class="text-xs text-gray-400" x-text="`(${cat.total} reviews)`"></span>
                            </div>
                            <div class="space-y-1 max-w-xs">
                                <template x-for="star in [5,4,3,2,1]" :key="star">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500 w-4" x-text="star + '★'"></span>
                                        <div class="flex-1 bg-gray-200 rounded-full h-1.5">
                                            <div class="bg-yellow-400 h-1.5 rounded-full transition-all duration-700"
                                                :style="`width: ${cat.total ? (cat.star_distribution[star] / cat.total * 100) : 0}%`"></div>
                                        </div>
                                        <span class="text-xs text-gray-400 w-5 text-right" x-text="cat.star_distribution[star]"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="lg:w-96">
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <div class="flex items-center justify-between px-4 py-2 bg-gray-50 border-b border-gray-200">
                                    <div class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-[#7F5539]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                        </svg>
                                        <span class="text-xs font-semibold text-gray-700">AI Feedback Summary</span>
                                    </div>
                                    <button
                                        @click="generateSummary(index)"
                                        :disabled="cat.summaryLoading"
                                        class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium rounded-md bg-[#7F5539] hover:bg-[#4A2C1D] text-white transition-colors disabled:opacity-50">
                                        <svg x-show="cat.summaryLoading" class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        <span x-text="cat.summaryLoading ? 'Generating...' : (cat.summary ? 'Regenerate' : 'Generate Summary')"></span>
                                    </button>
                                </div>
                                <div class="px-4 py-3 min-h-[80px]">
                                    <p x-show="!cat.summary && !cat.summaryLoading" class="text-xs text-gray-400 italic">
                                        Click "Generate Summary" to get an AI-powered analysis of all customer comments for this service category.
                                    </p>
                                    <div x-show="cat.summaryLoading" class="flex items-center gap-2 py-2">
                                        <div class="flex gap-1">
                                            <span class="h-2 w-2 bg-[#7F5539] rounded-full animate-bounce" style="animation-delay:0ms"></span>
                                            <span class="h-2 w-2 bg-[#7F5539] rounded-full animate-bounce" style="animation-delay:150ms"></span>
                                            <span class="h-2 w-2 bg-[#7F5539] rounded-full animate-bounce" style="animation-delay:300ms"></span>
                                        </div>
                                        <span class="text-xs text-gray-400">Analyzing feedback...</span>
                                    </div>
                                    <p x-show="cat.summary && !cat.summaryLoading"
                                        class="text-sm text-gray-700 leading-relaxed"
                                        x-text="cat.summary"></p>
                                </div>
                                <div x-show="cat.total === 0 || !cat.has_comments" class="px-4 py-2 bg-amber-50 border-t border-amber-100">
                                    <p class="text-xs text-amber-600">No written comments available for this category.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('reportData', () => ({
            byBranch:     @json($byBranch ?? []),
            byCategory:   @json($byCategory ?? []),
            filters: {
                date_from: '{{ $dateFrom ?? '' }}',
                date_to:   '{{ $dateTo ?? '' }}',
                branch_id: '{{ $branchId ?? '' }}',
            },
            activePreset: 'week',
            isLoading:    false,
            dateError:    '',

            init() {
                this.byCategory = this.byCategory.map(cat => ({
                    ...cat,
                    summary:        '',
                    summaryLoading: false,
                    has_comments:   cat.comments && cat.comments.length > 0,
                }));
                if (!this.filters.date_from || !this.filters.date_to) {
                    this.setPreset('week');
                }
            },

            get overallAvgRating() {
                if (!this.byBranch.length) return '—';
                const total = this.byBranch.reduce((sum, b) => sum + b.avg_rating * b.total, 0);
                const count = this.byBranch.reduce((sum, b) => sum + b.total, 0);
                if (!count) return '—';
                return (total / count).toFixed(1);
            },

            get totalFeedbacks() {
                return this.byBranch.reduce((sum, b) => sum + b.total, 0);
            },

            setPreset(preset) {
                this.activePreset = preset;
                this.dateError = '';
                const today = new Date();
                const fmt = d => {
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

            formatDate(dateStr) {
                if (!dateStr) return '';
                return new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            },

            async fetchReport() {
                if (this.filters.date_from && this.filters.date_to) {
                    if (new Date(this.filters.date_to) < new Date(this.filters.date_from)) {
                        this.dateError = '"To" date cannot be earlier than "From" date.';
                        return;
                    }
                }
                this.dateError  = '';
                this.isLoading  = true;
                try {
                    const params = new URLSearchParams({
                        date_from: this.filters.date_from,
                        date_to:   this.filters.date_to,
                        ajax:      'true',
                    });
                    if (this.filters.branch_id) params.append('branch_id', this.filters.branch_id);

                    const res  = await fetch(`{{ route('sub_one.reports.feedback_report') }}?${params}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.byBranch   = data.by_branch;
                        this.byCategory = data.by_category.map(cat => ({
                            ...cat,
                            summary:        '',
                            summaryLoading: false,
                            has_comments:   cat.comments && cat.comments.length > 0,
                        }));
                    }
                } catch (e) {
                    console.error('Report fetch error:', e);
                } finally {
                    this.isLoading = false;
                }
            },

            async generateSummary(index) {
                const cat = this.byCategory[index];
                if (!cat.has_comments) return;
                this.byCategory[index].summaryLoading = true;
                this.byCategory[index].summary        = '';
                try {
                    const res  = await fetch(`{{ route('sub_one.feedback.ai-summary') }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept':       'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            comments:   cat.comments,
                            context:    cat.category_name,
                            avg_rating: cat.avg_rating,
                            total:      cat.total,
                        }),
                    });
                    const data = await res.json();
                    this.byCategory[index].summary = data.summary || 'Unable to generate summary.';
                } catch (e) {
                    this.byCategory[index].summary = 'Error generating summary. Please try again.';
                } finally {
                    this.byCategory[index].summaryLoading = false;
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
                    branch_id: this.filters.branch_id,
                });
                
                window.open(`{{ route('sub_one.reports.export_feedback_pdf') }}?${params}`, '_blank');
            },
        }));
    });
</script>
@endsection