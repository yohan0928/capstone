@extends('layouts.app')

@section('title', 'Seats')

@section('content')
    <div x-data="seatData()" x-init="init()" class="p-4">
        <!-- Header Section -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8">
            <!-- Title -->
            <div class="text-center lg:text-left mb-4 lg:mb-0">
                <h1 class="text-2xl font-bold text-[#4A2C1D]">
                    {{ $branch->branch_name }}
                    <span class="block lg:inline text-lg font-semibold text-[#7F5539] lg:ml-2">
                        {{ $serviceCategory->service_category }} ({{ $serviceName->service_name }})
                    </span>
                </h1>
            </div>

            <!-- Archive Link -->
            <div class="lg:text-left text-right">
                <a href="{{ route('sub_one.seats.showDeactivatedSeat', [$branch->uuid, $serviceCategory->uuid, $serviceName->uuid]) }}"
                    class="text-sm font-medium text-[#7F5539] hover:underline">
                    View Archives
                </a>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-4 gap-6 mb-8">
            <!-- Total Seats -->
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

            <!-- Seats -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6" x-show="stats.seats > 0">
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

            <!-- Rooms -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6" x-show="stats.rooms > 0">
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
            <a href="{{ route('sub_one.service_names.showServiceName', [$branch->uuid, $serviceCategory->uuid]) }}"
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
                <!-- Large Screens Layout (Desktop) - Changed from sm:flex to lg:flex -->
                <div class="hidden lg:flex items-center justify-between mb-4">
                    <!-- Left: Header -->
                    <h2 class="text-lg font-semibold text-gray-900">Seat/Room Records</h2>

                    <!-- Right: Search + Filter + Add Button -->
                    <div class="flex items-center space-x-3">
                        <!-- Search Input -->
                        <div class="relative w-80">
                            <input type="text" x-model="searchQuery" @input.debounce.500ms="performSearch()"
                                placeholder="Search by seat number, room number, or type..."
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

                        <!-- Add Seat Button -->
                        <button @click="openAddModal()"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539] flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.5" class="w-5 h-5 mr-2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            Add Seat/Room
                        </button>
                    </div>
                </div>

                <!-- Small to Tablet Screens Layout (Mobile + Tablet) - Changed from sm:hidden to lg:hidden -->
                <div class="lg:hidden space-y-4">
                    <!-- First Row: Seat Records + Add Button -->
                    <div class="flex items-center justify-between">
                        <!-- Left: Header -->
                        <h2 class="text-lg font-semibold text-gray-900">Seat/Room Records</h2>

                        <!-- Right: Add Button -->
                        <button @click="openAddModal()"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539] flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.5" class="w-5 h-5 mr-2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <!-- Adaptive Label: "Add" on Mobile (sm:hidden), "Add Seat/Room" on Tablet (sm:inline) -->
                            <span class="sm:hidden">Add</span>
                            <span class="hidden sm:inline">Add Seat/Room</span>
                        </button>
                    </div>

                    <!-- Second Row: Search + Filter -->
                    <div class="flex items-center space-x-3">
                        <!-- Search Input -->
                        <div class="relative flex-1">
                            <input type="text" x-model="searchQuery" @input.debounce.500ms="performSearch()"
                                placeholder="Search by seat number, room number, or type..."
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
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
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
                                                    :action="'{{ url('sub_one/seats/status') }}/' + seat.uuid"
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
                                                    :action="'{{ url('sub_one/seats/status') }}/' + seat.uuid"
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

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <!-- Edit Button -->
                                        <div class="relative group">
                                            <button @click="openEditModal(seat)"
                                                class="text-[#4A2C1D] hover:text-[#7F5539] transition-colors p-2 rounded-full hover:bg-gray-100 flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                </svg>
                                            </button>
                                            <!-- Edit Label -->
                                            <span
                                                class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs font-medium px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none">
                                                Edit <span x-text="seat.room_no ? 'Room' : 'Seat'"></span>
                                            </span>
                                        </div>

                                        <!-- Archive Button -->
                                        <div class="relative group">
                                            <button @click="openArchiveModal(seat)"
                                                class="text-red-600 hover:text-red-800 transition-colors p-2 rounded-full hover:bg-gray-100 flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                            <!-- Archive Label -->
                                            <span
                                                class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs font-medium px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none">
                                                Archive <span x-text="seat.room_no ? 'Room' : 'Seat'"></span>
                                            </span>
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

        <!-- Add Seat Modal -->
        <div x-show="showAddModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[9999] overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeAddModal()"></div>

                <!-- Fixed width: 800px max, responsive down to mobile -->
                <div
                    class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle w-full max-w-2xl">
                    <!-- Header -->
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Add New Seat/Room</h3>
                        <button @click="closeAddModal()" type="button"
                            class="text-gray-400 hover:text-gray-500 focus:outline-none">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Form -->
                    <form id="addSeatForm" @submit.prevent="submitAddForm">
                        @csrf
                        <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                        <input type="hidden" name="service_category_id" value="{{ $serviceCategory->id }}">
                        <input type="hidden" name="service_name_id" value="{{ $serviceName->id }}">

                        <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
                            <!-- Seat Number -->
                            <div>
                                <label for="addSeatNo" class="block text-sm font-medium text-gray-700">Seat No</label>
                                <input type="number" name="seat_no" id="addSeatNo" min="1"
                                    x-model="addFormData.seat_no"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm"
                                    placeholder="Enter seat number (e.g., 1, 2, 3)">
                                <p class="text-xs text-gray-500 mt-1">Enter either seat number OR room number</p>
                            </div>

                            <!-- Divider with OR -->
                            <div class="relative flex items-center justify-center">
                                <div class="border-t border-gray-300 w-full"></div>
                                <span class="absolute bg-white px-3 text-sm text-gray-500">OR</span>
                            </div>

                            <!-- Room Number -->
                            <div>
                                <label for="addRoomNo" class="block text-sm font-medium text-gray-700">Room No</label>
                                <input type="number" name="room_no" id="addRoomNo" min="1"
                                    x-model="addFormData.room_no"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm"
                                    placeholder="Enter room number (e.g., Room 1, Suite A)">
                                <p class="text-xs text-gray-500 mt-1">Enter either seat number OR room number</p>
                            </div>

                            <!-- Validation Message -->
                            <div id="addValidationMessage" class="text-red-600 text-sm hidden">
                                Please enter either a seat number OR a room number, not both.
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" @click="closeAddModal()"
                                class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539]">
                                Cancel
                            </button>
                            <button type="submit" :disabled="isSubmitting"
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539] disabled:opacity-50">
                                <span x-show="!isSubmitting">Add Seat/Room</span>
                                <span x-show="isSubmitting">Adding...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Seat Modal -->
        <div x-show="showEditModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[9999] overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeEditModal()"></div>

                <!-- Fixed width: 800px max, responsive down to mobile -->
                <div
                    class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle w-full max-w-2xl">
                    <!-- Header -->
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Edit Seat/Room</h3>
                        <button @click="closeEditModal()" type="button"
                            class="text-gray-400 hover:text-gray-500 focus:outline-none">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Content -->
                    <div>
                        <!-- Loading State -->
                        <div x-show="!editSeatData && showEditModal" class="py-8 text-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#7F5539] mx-auto"></div>
                            <p class="mt-2 text-sm text-gray-500">Loading seat/room data...</p>
                        </div>

                        <!-- Form (only rendered when data is loaded) -->
                        <template x-if="editSeatData">
                            <form id="editSeatForm" @submit.prevent="submitEditForm">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                                <input type="hidden" name="service_category_id" value="{{ $serviceCategory->id }}">
                                <input type="hidden" name="service_name_id" value="{{ $serviceName->id }}">

                                <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
                                    <!-- Seat Number (Show ONLY if record has seat_no) -->
                                    <div x-show="editSeatData.seat_no !== null">
                                        <label for="editSeatNo" class="block text-sm font-medium text-gray-700">Seat
                                            No</label>
                                        <input type="number" name="seat_no" id="editSeatNo" min="1"
                                            x-model="editSeatData.seat_no"
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm"
                                            placeholder="Enter seat number">
                                    </div>

                                    <!-- Room Number (Show ONLY if record has room_no) -->
                                    <div x-show="editSeatData.room_no !== null">
                                        <label for="editRoomNo" class="block text-sm font-medium text-gray-700">Room
                                            No</label>
                                        <input type="number" name="room_no" id="editRoomNo" min="1"
                                            x-model="editSeatData.room_no"
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm"
                                            placeholder="Enter room number">
                                    </div>

                                    <!-- Validation Message (Only needed if both somehow appear, which shouldn't happen with strict display) -->
                                    <div id="editValidationMessage" class="text-red-600 text-sm hidden">
                                        Please enter valid data.
                                    </div>
                                </div>

                                <!-- Form Actions -->
                                <div class="mt-6 flex justify-end space-x-3">
                                    <button type="button" @click="closeEditModal()"
                                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539]">
                                        Cancel
                                    </button>
                                    <button type="submit" :disabled="isSubmitting"
                                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539] disabled:opacity-50">
                                        <span x-show="!isSubmitting">Update Seat/Room</span>
                                        <span x-show="isSubmitting">Updating...</span>
                                    </button>
                                </div>
                            </form>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Archive Confirmation Modal -->
        <div x-show="showArchiveModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[9999] overflow-y-auto" style="display: none;">
            <div class="flex items-start justify-center min-h-screen px-4 pt-20 pb-20 text-center sm:block sm:p-0">
                <!-- Click handler calls closeArchiveModal() to fix scroll -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-show="showArchiveModal"
                    x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    @click="closeArchiveModal()"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showArchiveModal" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-top sm:mt-20 sm:max-w-md sm:w-full sm:p-6">
                    <div>
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Confirm Archive</h3>
                            <div class="mt-4">
                                <p class="text-sm text-gray-500 mb-4">
                                    Archive <strong class="text-[#4A2C1D]"
                                        x-text="(selectedSeat?.room_no ? 'Room' : 'Seat') + ' #' + (selectedSeat?.seat_no || selectedSeat?.room_no)"></strong>?
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 flex space-x-3">
                        <button type="button" @click="closeArchiveModal()"
                            class="flex-1 inline-flex justify-center px-4 py-2 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            Cancel
                        </button>
                        <button type="button" @click="confirmArchive()"
                            class="flex-1 inline-flex justify-center px-4 py-2 border border-transparent text-base font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                            <!-- Adaptive Label -->
                            <span class="sm:hidden">Confirm</span>
                            <span class="hidden sm:inline">Confirm Archive</span>
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
                    seat_status: '{{ request('seat_status', '') }}',
                    type: '{{ request('type', '') }}',
                },
                clearFilters() {
                    this.filters = {
                        seat_status: '',
                        type: '',
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
                    type: '{{ request('type', '') }}',
                    search: '{{ request('search', '') }}',
                },
                searchQuery: '{{ request('search', '') }}',
                showFilters: false,
                showAddModal: false,
                showEditModal: false,
                showArchiveModal: false,
                selectedSeat: null,
                editSeatData: null,
                paginationLinks: [],
                isLoading: false,
                isSubmitting: false,

                // Add form data
                addFormData: {
                    seat_no: '',
                    room_no: ''
                },

                init() {
                    this.updatePaginationLinks();
                    this.updateActiveFilters();
                    this.initializeFormValidation();
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

                    if (this.currentFilters.type) {
                        const typeText = this.currentFilters.type === 'seat' ? 'Seat' : 'Room';
                        filters.push({
                            key: 'type',
                            label: `Type: ${typeText}`
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

                // Form validation
                initializeFormValidation() {
                    // This will be called after Alpine initializes
                    setTimeout(() => {
                        this.setupFormValidation('addSeatForm', 'addValidationMessage');
                        // Edit form validation will be handled when modal opens
                    }, 100);
                },

                setupFormValidation(formId, messageId) {
                    const form = document.getElementById(formId);
                    if (!form) return;

                    const seatNoInput = form.querySelector('input[name="seat_no"]');
                    const roomNoInput = form.querySelector('input[name="room_no"]');
                    const validationMessage = document.getElementById(messageId);
                    const submitBtn = form.querySelector('button[type="submit"]');

                    // If inputs don't exist (e.g. edit form hidden fields), skip validation logic
                    if (!seatNoInput || !roomNoInput || !validationMessage || !submitBtn) return;

                    const validateInputs = () => {
                        // Check if input is visible before validating (for edit form)
                        const seatVisible = seatNoInput.offsetParent !== null;
                        const roomVisible = roomNoInput.offsetParent !== null;
                        
                        const seatNoValue = seatNoInput.value.trim();
                        const roomNoValue = roomNoInput.value.trim();

                        // Only validate visible inputs
                        if (seatVisible && roomVisible && seatNoValue && roomNoValue) {
                            validationMessage.classList.remove('hidden');
                            submitBtn.disabled = true;
                            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                            return false;
                        } else {
                            validationMessage.classList.add('hidden');
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                            return true;
                        }
                    };

                    // Add event listeners to both inputs
                    seatNoInput.addEventListener('input', validateInputs);
                    roomNoInput.addEventListener('input', validateInputs);

                    // Initial validation
                    validateInputs();
                },

                // Add Modal functions
                openAddModal() {
                    this.showAddModal = true;
                    this.addBodyClass();
                    this.resetAddForm();
                    // Re-initialize validation for add form
                    setTimeout(() => this.setupFormValidation('addSeatForm', 'addValidationMessage'),
                        50);
                },

                closeAddModal() {
                    this.showAddModal = false;
                    this.removeBodyClass();
                    this.resetAddForm();
                },

                resetAddForm() {
                    this.addFormData = {
                        seat_no: '',
                        room_no: ''
                    };
                },

                async submitAddForm() {
                    if (this.isSubmitting) return;

                    this.isSubmitting = true;

                    try {
                        const formData = new FormData();
                        formData.append('branch_id', '{{ $branch->id }}');
                        formData.append('service_category_id', '{{ $serviceCategory->id }}');
                        formData.append('service_name_id', '{{ $serviceName->id }}');
                        formData.append('seat_no', this.addFormData.seat_no || '');
                        formData.append('room_no', this.addFormData.room_no || '');
                        formData.append('_token', '{{ csrf_token() }}');

                        const response = await fetch(
                            '{{ route('sub_one.seats.storeSeatAjax') }}', {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });

                        const data = await response.json();

                        if (data.success) {
                            this.showToast('Seat created successfully.', 'success');
                            this.closeAddModal();

                            // Refresh the table
                            await this.applyFilters(this.currentFilters);
                        } else {
                            throw new Error(data.message || 'Failed to create seat');
                        }
                    } catch (error) {
                        console.error('Error creating seat:', error);
                        this.showToast(error.message || 'Failed to create seat. Please try again.',
                            'error');
                    } finally {
                        this.isSubmitting = false;
                    }
                },

                // Edit Modal functions
                async openEditModal(seat) {
                    this.selectedSeat = seat;

                    try {
                        const response = await fetch(
                            `{{ url('sub_one/seats') }}/{{ $branch->uuid }}/{{ $serviceCategory->uuid }}/{{ $serviceName->uuid }}/${seat.uuid}/data`, {
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });

                        const data = await response.json();

                        if (data.success) {
                            this.editSeatData = data.seat;
                            this.showEditModal = true;
                            this.addBodyClass();
                            // Re-initialize validation for edit form
                            setTimeout(() => this.setupFormValidation('editSeatForm',
                                'editValidationMessage'), 50);
                        } else {
                            throw new Error(data.message || 'Failed to load seat data');
                        }
                    } catch (error) {
                        console.error('Error loading seat data:', error);
                        this.showToast('Failed to load seat data. Please try again.', 'error');
                    }
                },

                closeEditModal() {
                    this.showEditModal = false;
                    this.editSeatData = null;
                    this.removeBodyClass();
                },

                async submitEditForm() {
                    if (this.isSubmitting || !this.editSeatData) return;

                    this.isSubmitting = true;

                    try {
                        const formData = new FormData();
                        formData.append('branch_id', '{{ $branch->id }}');
                        formData.append('service_category_id', '{{ $serviceCategory->id }}');
                        formData.append('service_name_id', '{{ $serviceName->id }}');
                        // Send existing data back
                        formData.append('seat_no', this.editSeatData.seat_no || '');
                        formData.append('room_no', this.editSeatData.room_no || '');
                        formData.append('_token', '{{ csrf_token() }}');
                        formData.append('_method', 'PATCH');

                        const response = await fetch(
                            `{{ url('sub_one/seats/ajax') }}/${this.editSeatData.uuid}/update`, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });

                        const data = await response.json();

                        if (data.success) {
                            this.showToast('Seat updated successfully.', 'success');
                            this.closeEditModal();

                            // Refresh the table
                            await this.applyFilters(this.currentFilters);
                        } else {
                            throw new Error(data.message || 'Failed to update seat');
                        }
                    } catch (error) {
                        console.error('Error updating seat:', error);
                        this.showToast(error.message || 'Failed to update seat. Please try again.',
                            'error');
                    } finally {
                        this.isSubmitting = false;
                    }
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
                        this.showToast('Failed to apply filters. Please try again.', 'error');
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
                        type: '',
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
                        this.showToast('Failed to clear filters. Please try again.', 'error');
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
                        this.showToast('Failed to load page. Please try again.', 'error');
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

                // Archive modal methods
                openArchiveModal(seat) {
                    this.selectedSeat = seat;
                    this.showArchiveModal = true;
                    this.addBodyClass();
                },

                closeArchiveModal() {
                    this.showArchiveModal = false;
                    this.removeBodyClass();
                },

                confirmArchive() {
                    if (!this.selectedSeat) return;

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action =
                        `{{ url('sub_one/seats/deactivate') }}/${this.selectedSeat.uuid}`;

                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';

                    const methodField = document.createElement('input');
                    methodField.type = 'hidden';
                    methodField.name = '_method';
                    methodField.value = 'PATCH';

                    const serviceNameField = document.createElement('input');
                    serviceNameField.type = 'hidden';
                    serviceNameField.name = 'service_name_id';
                    serviceNameField.value = '{{ $serviceName->id }}';

                    form.appendChild(csrfToken);
                    form.appendChild(methodField);
                    form.appendChild(serviceNameField);

                    document.body.appendChild(form);
                    form.submit();
                },

                // Filter modal methods
                closeFilterModal() {
                    this.showFilters = false;
                    this.removeBodyClass();
                },

                showToast(message, type = 'success') {},

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