@extends('layouts.app')

@section('title', 'Branch')

@section('content')
    <div x-data="branchData()" x-init="init()" class="p-4">
        <!-- Header Section -->
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900">
                Branch Details
            </h1>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Table Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <!-- Header only -->
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Your Branch Information</h2>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Image
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Branch Details
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Open Hours & Days
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Features
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(branch, index) in branches" :key="branch.uuid">
                            <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-100'">
                                <!-- Image -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex-shrink-0 h-16 w-16">
                                        <img :src="branch.branch_profile ?
                                            `/storage/app/public/${branch.branch_profile}` :
                                            `https://ui-avatars.com/api/?name=${encodeURIComponent(branch.branch_name)}&background=7F5539&color=FFFFFF`"
                                            :alt="branch.branch_name"
                                            class="h-16 w-16 rounded-lg object-cover border border-gray-200">
                                    </div>
                                </td>

                                <!-- Branch Details -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900" x-text="branch.branch_name">
                                    </div>
                                    <div class="text-sm text-gray-500" x-text="branch.location"></div>
                                    <a :href="branch.google_map_url" target="_blank"
                                        class="text-xs text-blue-600 hover:underline mt-1 inline-block">
                                        View on Google Maps
                                    </a>
                                </td>

                                <!-- Open Hours & Days -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <template x-if="branch.open_time && branch.close_time">
                                            <span
                                                x-text="`${formatTime(branch.open_time)} - ${formatTime(branch.close_time)}`"></span>
                                        </template>
                                    </div>
                                    <div class="text-sm text-gray-500" x-text="branch.open_days"></div>
                                </td>

                                <!-- Features -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-wrap gap-1 max-w-[200px]">
                                        <template x-for="feature in branch.features.split(',')" :key="feature">
                                            <span
                                                class="bg-[#7F5539] text-white text-xs font-medium px-2 py-1 rounded-full whitespace-nowrap">
                                                <span x-text="feature.trim()"></span>
                                            </span>
                                        </template>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex justify-center items-center">
                                        <div x-data="{ open: false }" class="relative">
                                            <button @click.prevent="open = !open" @click.away="open = false"
                                                class="flex items-center space-x-1 text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap cursor-pointer"
                                                :class="getStatusClasses(branch.branch_status)">
                                                <span x-text="getStatusText(branch.branch_status)"></span>
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="w-3 h-3 transition-transform duration-200"
                                                    :class="{ 'rotate-180': open }">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>

                                            <!-- Dropdown -->
                                            <div x-show="open"
                                                class="absolute left-0 mt-2 w-40 bg-white rounded-md shadow-lg z-10 border border-gray-200"
                                                style="display:none;">
                                                <!-- Open Option -->
                                                <form :id="'update-branch-status-' + branch.uuid + '-1'"
                                                    :action="'{{ url('sub_two/branches/status') }}/' + branch.uuid"
                                                    method="POST" class="hidden">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="branch_status" value="1">
                                                </form>
                                                <a href="#"
                                                    @click.prevent="document.getElementById('update-branch-status-' + branch.uuid + '-1').submit(); open = false;"
                                                    class="block px-4 py-2 text-sm hover:bg-gray-100"
                                                    :class="branch.branch_status === 1 ?
                                                        'bg-green-50 text-green-700 font-medium' : 'text-gray-700'">
                                                    Open
                                                </a>

                                                <!-- Closed Option -->
                                                <form :id="'update-branch-status-' + branch.uuid + '-0'"
                                                    :action="'{{ url('sub_two/branches/status') }}/' + branch.uuid"
                                                    method="POST" class="hidden">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="branch_status" value="0">
                                                </form>
                                                <a href="#"
                                                    @click.prevent="document.getElementById('update-branch-status-' + branch.uuid + '-0').submit(); open = false;"
                                                    class="block px-4 py-2 text-sm hover:bg-gray-100"
                                                    :class="branch.branch_status === 0 ? 'bg-red-50 text-red-700 font-medium' :
                                                        'text-gray-700'">
                                                    Closed
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <!-- Service Category Button -->
                                        <div class="relative group">
                                            <a :href="'{{ url('sub_two/service_categories') }}/' + branch.uuid"
                                                class="text-blue-600 hover:text-blue-800 transition-colors p-2 rounded-full hover:bg-gray-100 flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                                                </svg>
                                            </a>
                                            <!-- Service Categories Label -->
                                            <span
                                                class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs font-medium px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none">
                                                View Service Categories
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <tr x-show="!branches.length">
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" />
                                        </path>
                                    </svg>
                                    <h5 class="text-sm font-medium text-gray-900">
                                        No branch assigned
                                    </h5>
                                    <p class="text-sm text-gray-500">
                                        You are not assigned to any branch.
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
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            // Main component
            Alpine.data('branchData', () => ({
                // Initial state
                branches: @json($branches->items() ?? []),
                pagination: @json($branches->toArray()),
                paginationLinks: [],

                init() {
                    this.updatePaginationLinks();
                },

                // Utility functions
                getStatusClasses(status) {
                    const statusClasses = {
                        0: 'bg-red-200 text-red-800', // Closed
                        1: 'bg-green-200 text-green-800', // Open
                        2: 'bg-yellow-200 text-yellow-800', // Coming Soon
                    };
                    return statusClasses[status] || 'bg-gray-200 text-gray-800';
                },

                getStatusText(status) {
                    const statusText = {
                        0: 'Closed',
                        1: 'Open',
                        2: 'Coming Soon',
                    };
                    return statusText[status] || 'Unknown';
                },

                // Pagination
                async changePage(page) {
                    if (page < 1 || page > this.pagination.last_page) return;

                    try {
                        const url = `?page=${page}&ajax=true`;
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
                            this.branches = data.data;
                            this.pagination = data.pagination;
                            this.updatePaginationLinks();
                        } else {
                            throw new Error(data.message || 'Pagination failed');
                        }
                    } catch (error) {
                        console.error('Error changing page:', error);
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
                }
            }));
        });

        function formatTime(timeString) {
            if (!timeString) return '';
            const [hour, minute] = timeString.split(':').map(Number);
            const date = new Date();
            date.setHours(hour);
            date.setMinutes(minute);
            return date.toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    </script>

    <style>
        .modal-open {
            overflow: hidden;
        }

        /* Custom scrollbar for modal content */
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
    </style>
@endsection