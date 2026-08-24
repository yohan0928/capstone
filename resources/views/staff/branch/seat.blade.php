@extends('layouts.app')

@section('title', 'Seats')

@section('content')
    <div x-data="seatData()" x-init="init()" class="p-4">
        <!-- Header Section -->
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-bold text-[#4A2C1D]">
                {{ $branch->branch_name }}
                <span class="block text-lg font-semibold text-[#7F5539] mt-1">
                    {{ $serviceCategory->service_category }} ({{ $serviceName->service_name }})
                </span>
            </h1>
        </div>

        <!-- Dynamic Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
            <!-- Total Seats/Rooms -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Seats/Rooms</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.total_seats"></p>
                    </div>
                    <div class="p-3 bg-[#4A2C1D]/10 rounded-lg">
                        <svg class="w-6 h-6 text-[#4A2C1D]/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Seats Stat Card (Only show if there are seats) -->
            <template x-if="stats.seats > 0">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Seats</p>
                            <p class="text-2xl font-bold text-gray-900" x-text="stats.seats"></p>
                        </div>
                        <div class="p-3 bg-blue-50 rounded-lg">
                            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Rooms Stat Card (Only show if there are rooms) -->
            <template x-if="stats.rooms > 0">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Rooms</p>
                            <p class="text-2xl font-bold text-gray-900" x-text="stats.rooms"></p>
                        </div>
                        <div class="p-3 bg-purple-50 rounded-lg">
                            <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 10V6a3 3 0 013-3v0a3 3 0 013 3v4m-6 4h6m-6 4h6m2 5H7a2 2 0 01-2-2v-4a2 2 0 012-2h10a2 2 0 012 2v4a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Available Seats -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Available</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.available_seats"></p>
                    </div>
                    <div class="p-3 bg-green-50 rounded-lg">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Unavailable Seats -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Unavailable</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.unavailable_seats"></p>
                    </div>
                    <div class="p-3 bg-red-50 rounded-lg">
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end mb-8">
            <a href="{{ route('sub_two.service_names.showServiceName', [$branch->uuid, $serviceCategory->uuid]) }}"
                class="inline-flex items-center text-sm font-medium text-[#7F5539] hover:underline">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Service Names
            </a>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Table Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <!-- Medium to Largest Screens Layout -->
                <div class="hidden sm:flex items-center justify-between mb-4">
                    <!-- Left: Header -->
                    <h2 class="text-lg font-semibold text-gray-900">Seat/Room Records</h2>

                    <!-- Right: Search + Filter + Add Button -->
                    <div class="flex items-center space-x-3">
                        <!-- Search Input -->
                        <div class="relative w-80">
                            <input type="text" x-model="searchQuery" @input.debounce.500ms="performSearch()"
                                placeholder="Search by seat number or room number..."
                                class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] w-full">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Filter Button -->
                        <button @click="showFilters = true; addBodyClass()"
                            class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                            </svg>
                            Filters
                        </button>
                    </div>
                </div>

                <!-- Small to Smallest Screens Layout -->
                <div class="sm:hidden space-y-4">
                    <!-- First Row: Seat Records + Add Button -->
                    <div class="flex items-center justify-between">
                        <!-- Left: Header -->
                        <h2 class="text-lg font-semibold text-gray-900">Seat/Room Records</h2>
                    </div>

                    <!-- Second Row: Search + Filter -->
                    <div class="flex items-center space-x-3">
                        <!-- Search Input -->
                        <div class="relative flex-1">
                            <input type="text" x-model="searchQuery" @input.debounce.500ms="performSearch()"
                                placeholder="Search by seat number or room number..."
                                class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] w-full">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Filter Button -->
                        <button @click="showFilters = true; addBodyClass()"
                            class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Active Filters Badge -->
                <div x-show="hasActiveFilters" class="flex items-center justify-end space-x-2 mt-4">
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
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Type
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Number
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="seat in seats" :key="seat.uuid">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <!-- Type -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex justify-center items-center">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="seat.room_no ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'">
                                            <span x-text="seat.room_no ? 'Room' : 'Seat'"></span>
                                        </span>
                                    </div>
                                </td>

                                <!-- Number -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 text-center">
                                        <span x-text="seat.seat_no || seat.room_no"></span>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex justify-center items-center">
                                        <div x-data="{ open: false }" class="relative">
                                            <button @click.prevent="open = !open" @click.away="open = false"
                                                class="flex items-center space-x-1 text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap cursor-pointer"
                                                :class="getStatusClasses(seat.seat_status)">
                                                <span x-text="getStatusText(seat.seat_status)"></span>
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
                                                <!-- Available Option -->
                                                <form :id="'update-seat-status-' + seat.uuid + '-1'"
                                                    :action="'{{ url('sub_two/seats/status') }}/' + seat.uuid"
                                                    method="POST" class="hidden">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="seat_status" value="1">
                                                    <input type="hidden" name="service_name_id"
                                                        value="{{ $serviceName->id }}">
                                                </form>
                                                <a href="#"
                                                    @click.prevent="document.getElementById('update-seat-status-' + seat.uuid + '-1').submit(); open = false;"
                                                    class="block px-4 py-2 text-sm hover:bg-gray-100"
                                                    :class="seat.seat_status === 1 ? 'bg-green-50 text-green-700 font-medium' :
                                                        'text-gray-700'">
                                                    Available
                                                </a>

                                                <!-- Unavailable Option -->
                                                <form :id="'update-seat-status-' + seat.uuid + '-0'"
                                                    :action="'{{ url('sub_two/seats/status') }}/' + seat.uuid"
                                                    method="POST" class="hidden">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="seat_status" value="0">
                                                    <input type="hidden" name="service_name_id"
                                                        value="{{ $serviceName->id }}">
                                                </form>
                                                <a href="#"
                                                    @click.prevent="document.getElementById('update-seat-status-' + seat.uuid + '-0').submit(); open = false;"
                                                    class="block px-4 py-2 text-sm hover:bg-gray-100"
                                                    :class="seat.seat_status === 0 ? 'bg-red-50 text-red-700 font-medium' :
                                                        'text-gray-700'">
                                                    Unavailable
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <tr x-show="!seats.length">
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                        </path>
                                    </svg>
                                    <h5 class="text-sm font-medium text-gray-900"
                                        x-text="hasActiveFilters ? 'No seats match your filters' : 'No seats found'">
                                    </h5>
                                    <p class="text-sm text-gray-500"
                                        x-text="hasActiveFilters ? 'Try adjusting your filters.' : 'Add your first seat to get started.'">
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
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeFilterModal()"></div>
                <!-- Keep the same max-w-md across all screen sizes -->
                <div
                    class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl sm:my-8 sm:align-middle max-w-md w-full sm:p-6">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Filter Seats/Rooms</h3>

                        <div x-data="filterState()">
                            <!-- Filter Inputs -->
                            <div class="space-y-4">
                                <!-- Status Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select x-model="filters.seat_status"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                        <option value="">All Status</option>
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
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            // Filter state for the sidebar
            Alpine.data('filterState', () => ({
                filters: {
                    seat_status: '{{ request('seat_status', '') }}',
                },
                clearFilters() {
                    this.filters = {
                        seat_status: '',
                    };
                },
                applyFilters() {
                    const mainComponent = Alpine.$data(document.querySelector(
                        '[x-data="seatData()"]'));
                    const newFilters = {
                        ...mainComponent.currentFilters,
                        ...this.filters,
                        search: mainComponent.searchQuery
                    };
                    mainComponent.applyFilters(newFilters);
                    mainComponent.removeBodyClass();
                }
            }));

            // Main component
            Alpine.data('seatData', () => ({
                // Initial state
                seats: @json($seat->items() ?? []),
                pagination: @json($seat->toArray()),
                stats: @json($stats),
                currentFilters: {
                    seat_status: '{{ request('seat_status', '') }}',
                    search: '{{ request('search', '') }}',
                },
                searchQuery: '{{ request('search', '') }}',
                showFilters: false,
                showArchiveModal: false,
                selectedSeat: null,
                paginationLinks: [],
                isLoading: false,

                init() {
                    this.updatePaginationLinks();
                    this.updateActiveFilters();
                },

                // Computed properties
                get hasActiveFilters() {
                    return Object.values(this.currentFilters).some(value => value !== '');
                },

                get activeFilters() {
                    const filters = [];

                    if (this.currentFilters.search) {
                        filters.push({
                            key: 'search',
                            label: `Search: ${this.currentFilters.search}`
                        });
                    }

                    if (this.currentFilters.seat_status) {
                        const statusText = this.getStatusText(this.currentFilters.seat_status);
                        filters.push({
                            key: 'seat_status',
                            label: `Status: ${statusText}`
                        });
                    }

                    return filters;
                },

                // Utility functions
                getStatusClasses(status) {
                    const statusClasses = {
                        0: 'bg-red-200 text-red-800', // Unavailable
                        1: 'bg-green-200 text-green-800', // Available
                    };
                    return statusClasses[status] || 'bg-gray-200 text-gray-800';
                },

                getStatusText(status) {
                    const statusText = {
                        0: 'Unavailable',
                        1: 'Available',
                    };
                    return statusText[status] || 'Unknown';
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
                            this.seats = data.data;
                            this.pagination = data.pagination;
                            this.stats = data.stats;
                            this.updatePaginationLinks();
                            this.updateActiveFilters();
                        } else {
                            throw new Error(data.message || 'Filter application failed');
                        }
                    } catch (error) {
                        console.error('Error applying filters:', error);
                        this.showNotification('Failed to apply filters. Please try again.',
                            'error');
                    } finally {
                        this.isLoading = false;
                        this.removeBodyClass();
                    }
                },

                async clearAllFilters() {
                    this.isLoading = true;
                    this.showFilters = false;
                    this.searchQuery = '';
                    this.currentFilters = {
                        seat_status: '',
                        search: '',
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
                            this.seats = data.data;
                            this.pagination = data.pagination;
                            this.stats = data.stats;
                            this.updatePaginationLinks();
                            this.updateActiveFilters();
                        } else {
                            throw new Error(data.message || 'Filter clearing failed');
                        }
                    } catch (error) {
                        console.error('Error clearing filters:', error);
                        this.showNotification('Failed to clear filters. Please try again.',
                            'error');
                    } finally {
                        this.isLoading = false;
                        this.removeBodyClass();
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
                            this.seats = data.data;
                            this.pagination = data.pagination;
                            this.stats = data.stats;
                            this.updatePaginationLinks();
                        } else {
                            throw new Error(data.message || 'Pagination failed');
                        }
                    } catch (error) {
                        console.error('Error changing page:', error);
                        this.showNotification('Failed to load page. Please try again.', 'error');
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

                // Filter modal methods
                closeFilterModal() {
                    this.showFilters = false;
                    this.removeBodyClass();
                },

                showNotification(message, type = 'success') {
                    // You can integrate your existing toast notification system here
                    console.log(`${type}: ${message}`);
                },

                // Add body class for modal
                addBodyClass() {
                    document.body.classList.add('modal-open');
                },

                // Remove body class for modal
                removeBodyClass() {
                    document.body.classList.remove('modal-open');
                }
            }));
        });
    </script>

    <style>
        .modal-open {
            overflow: hidden;
        }
        
        /* Ensure grid layout adjusts properly */
        .grid {
            display: grid;
        }
    </style>
@endsection