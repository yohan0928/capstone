@extends('layouts.app')

@section('title', 'Branch')

@section('content')
    <div x-data="branchData()" x-init="init()" class="p-4">
        <!-- Header Section -->
        <div class="flex items-center justify-between mb-8">
            <!-- Left: Empty spacer -->
            <div class="flex-1"></div>
    
            <!-- Center: Title -->
            <h1 class="text-2xl font-bold text-gray-900 text-center">
                Branches
            </h1>
    
            <!-- Right: Archive Link -->
            <div class="flex-1 text-right">
                <a href="{{ route('sub_one.branches.showDeactivatedBranch') }}"
                   class="text-sm font-medium text-[#7F5539] hover:underline">
                   View Archives
                </a>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6 mb-8">
            <!-- Total Branches -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Branches</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.total_branches"></p>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-lg">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Open Branches -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Open</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.open_branches"></p>
                    </div>
                    <div class="p-3 bg-green-50 rounded-lg">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Closed Branches -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Closed</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.closed_branches"></p>
                    </div>
                    <div class="p-3 bg-red-50 rounded-lg">
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Coming Soon Branches -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Coming Soon</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.coming_soon_branches"></p>
                    </div>
                    <div class="p-3 bg-yellow-50 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Has Coordinates -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Has Coordinates</p>
                        <p class="text-2xl font-bold text-green-600" x-text="stats.has_coordinates"></p>
                    </div>
                    <div class="p-3 bg-green-50 rounded-lg">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 6.75V15m6-6.75V15m-6 0a3 3 0 01-3-3V6.75m9 5.25a3 3 0 01-3-3V6.75M3 18.75h18" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- No Coordinates -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">No Coordinates</p>
                        <p class="text-2xl font-bold text-yellow-600" x-text="stats.no_coordinates"></p>
                    </div>
                    <div class="p-3 bg-yellow-50 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Table Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <!-- Large Screens Only Layout (Desktop) -->
                <div class="hidden lg:flex items-center justify-between mb-4">
                    <!-- Left: Header -->
                    <h2 class="text-lg font-semibold text-gray-900">Branch Records</h2>

                    <!-- Right: Search + Filter + Add Button -->
                    <div class="flex items-center space-x-3">
                        <!-- Search Input -->
                        <div class="relative w-80">
                            <input type="text" x-model="searchQuery" @input.debounce.500ms="performSearch()"
                                placeholder="Search by branch name, location, or features..."
                                class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] w-full">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Filter Button -->
                        <button @click="showFilters = true; addBodyClass()"
                            class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-5 h-5 mr-2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                            </svg>
                            Filters
                        </button>

                        <!-- Batch Geocode Button -->
                        <button @click="batchGeocode()"
                            class="inline-flex items-center justify-center px-4 py-2 border border-[#7F5539] rounded-lg text-sm font-medium text-[#7F5539] bg-white hover:bg-[#f5f0eb] focus:outline-none focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6.75V15m-6 0a3 3 0 01-3-3V6.75m9 5.25a3 3 0 01-3-3V6.75M3 18.75h18" />
                            </svg>
                            Batch Geocode
                        </button>

                        <!-- Add Branch Button -->
                        <button @click="openAddModal()"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539] flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.5" class="w-5 h-5 mr-2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            Add Branch
                        </button>
                    </div>
                </div>

                <!-- Small to Tablet Screens Layout (Mobile + Tablet) -->
                <div class="lg:hidden space-y-4">
                    <!-- First Row: Branch Records + Add Button -->
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">Branch Records</h2>
                        <div class="flex items-center space-x-2">
                            <button @click="batchGeocode()"
                                class="inline-flex items-center justify-center px-3 py-2 border border-[#7F5539] rounded-lg text-sm font-medium text-[#7F5539] bg-white hover:bg-[#f5f0eb] flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6.75V15m-6 0a3 3 0 01-3-3V6.75m9 5.25a3 3 0 01-3-3V6.75M3 18.75h18" />
                                </svg>
                            </button>
                            <button @click="openAddModal()"
                                class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-[#7F5539] hover:bg-[#4A2C1D] flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5 mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                <span class="sm:hidden">Add</span>
                                <span class="hidden sm:inline">Add Branch</span>
                            </button>
                        </div>
                    </div>

                    <!-- Second Row: Search + Filter -->
                    <div class="flex items-center space-x-3">
                        <div class="relative flex-1">
                            <input type="text" x-model="searchQuery" @input.debounce.500ms="performSearch()"
                                placeholder="Search branches..."
                                class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] w-full">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>
                        <button @click="showFilters = true; addBodyClass()"
                            class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Image
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Branch Details
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Coordinates
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
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900" x-text="branch.branch_name">
                                    </div>
                                    <div class="text-sm text-gray-500" x-text="branch.location"></div>
                                    <div class="text-xs text-gray-400 truncate max-w-[200px]" x-text="branch.address"></div>
                                    <a :href="branch.google_map_url" target="_blank"
                                        class="text-xs text-blue-600 hover:underline mt-1 inline-block">
                                        View on Google Maps
                                    </a>
                                </td>

                                <!-- Coordinates -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <template x-if="branch.latitude && branch.longitude">
                                        <div>
                                            <div class="text-xs text-gray-600">
                                                <span class="font-medium">Lat:</span> <span x-text="parseFloat(branch.latitude).toFixed(6)"></span>
                                            </div>
                                            <div class="text-xs text-gray-600">
                                                <span class="font-medium">Lng:</span> <span x-text="parseFloat(branch.longitude).toFixed(6)"></span>
                                            </div>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mt-1">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Geocoded
                                            </span>
                                        </div>
                                    </template>
                                    <template x-if="!branch.latitude || !branch.longitude">
                                        <div>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                                </svg>
                                                No Coordinates
                                            </span>
                                            <button @click="geocodeSingleBranch(branch)" 
                                                class="block mt-1 text-xs text-[#7F5539] hover:underline">
                                                <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 6.75V15m6-6.75V15m-6 0a3 3 0 01-3-3V6.75m9 5.25a3 3 0 01-3-3V6.75M3 18.75h18" />
                                                </svg>
                                                Geocode Now
                                            </button>
                                        </div>
                                    </template>
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
                                                    :action="'{{ url('sub_one/branches/status') }}/' + branch.uuid"
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
                                                    :action="'{{ url('sub_one/branches/status') }}/' + branch.uuid"
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

                                                <!-- Coming Soon Option -->
                                                <form :id="'update-branch-status-' + branch.uuid + '-2'"
                                                    :action="'{{ url('sub_one/branches/status') }}/' + branch.uuid"
                                                    method="POST" class="hidden">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="branch_status" value="2">
                                                </form>
                                                <a href="#"
                                                    @click.prevent="document.getElementById('update-branch-status-' + branch.uuid + '-2').submit(); open = false;"
                                                    class="block px-4 py-2 text-sm hover:bg-gray-100"
                                                    :class="branch.branch_status === 2 ?
                                                        'bg-yellow-50 text-yellow-700 font-medium' : 'text-gray-700'">
                                                    Coming Soon
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
                                            <a :href="'{{ url('sub_one/service_categories') }}/' + branch.uuid"
                                                class="text-blue-600 hover:text-blue-800 transition-colors p-2 rounded-full hover:bg-gray-100 flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                                                </svg>
                                            </a>
                                            <span
                                                class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs font-medium px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none">
                                                View Service Categories
                                            </span>
                                        </div>

                                        <!-- Discount Button -->
                                        <div class="relative group">
                                            <button @click="openDiscountModal(branch)"
                                                class="text-purple-600 hover:text-purple-800 transition-colors p-2 rounded-full hover:bg-gray-100 flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185zM9.75 9h.008v.008H9.75V9zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 4.5h.008v.008h-.008V13.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                                </svg>
                                            </button>
                                            <span
                                                class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs font-medium px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none">
                                                Manage Discounts
                                            </span>
                                        </div>

                                        <!-- Edit Button -->
                                        <div class="relative group">
                                            <button @click="openEditModal(branch)"
                                                class="text-[#4A2C1D] hover:text-[#7F5539] transition-colors p-2 rounded-full hover:bg-gray-100 flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                </svg>
                                            </button>
                                            <span
                                                class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs font-medium px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none">
                                                Edit Branch
                                            </span>
                                        </div>

                                        <!-- Archive Button -->
                                        <div class="relative group">
                                            <button @click="openArchiveModal(branch)"
                                                class="text-red-600 hover:text-red-800 transition-colors p-2 rounded-full hover:bg-gray-100 flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                            <span
                                                class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs font-medium px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none">
                                                Archive Branch
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <tr x-show="!branches.length">
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" />
                                        </path>
                                    </svg>
                                    <h5 class="text-sm font-medium text-gray-900"
                                        x-text="hasActiveFilters ? 'No branches match your filters' : 'No branches found'">
                                    </h5>
                                    <p class="text-sm text-gray-500"
                                        x-text="hasActiveFilters ? 'Try adjusting your filters.' : 'Add your first branch to get started.'">
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
                        <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
                            class="px-3 py-1 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            Previous
                        </button>
                        <template x-for="page in paginationLinks" :key="page">
                            <button @click="changePage(page)" class="px-3 py-1 border rounded-lg text-sm font-medium"
                                :class="page === pagination.current_page ?
                                    'border-2 border-[#4A2C1D] bg-[#7F5539]/80 text-white' :
                                    'border-gray-300 text-gray-700 hover:bg-gray-50'"
                                :disabled="page === '...'" x-text="page"></button>
                        </template>
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
                <div class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl sm:my-8 sm:align-middle max-w-md w-full sm:p-6">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Filter Branches</h3>
                        <div x-data="filterState()">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select x-model="filters.branch_status"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                        <option value="">All Status</option>
                                        <option value="1">Open</option>
                                        <option value="0">Closed</option>
                                        <option value="2">Coming Soon</option>
                                    </select>
                                </div>
                                <!-- Geocoding Status Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Geocoding Status</label>
                                    <select x-model="filters.has_coordinates"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                        <option value="">All Branches</option>
                                        <option value="1">Has Coordinates</option>
                                        <option value="0">No Coordinates</option>
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

        <!-- ============================================================ -->
        <!-- ADD BRANCH MODAL WITH GEOCODING                               -->
        <!-- ============================================================ -->
        <div x-show="showAddModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[9999] overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeAddModal()"></div>

                <div class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle w-full max-w-3xl sm:p-6 md:p-10">
                    <!-- Header -->
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Add New Branch</h3>
                        <button @click="closeAddModal()" type="button"
                            class="text-gray-400 hover:text-gray-500 focus:outline-none">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Form -->
                    <form action="{{ route('sub_one.branches.storeBranch') }}" method="POST"
                        enctype="multipart/form-data" id="addBranchForm">
                        @csrf
                        <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
                            <!-- Branch Profile -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Branch Profile</label>
                                <div class="mt-1 flex items-center space-x-4">
                                    <div class="flex-shrink-0">
                                        <img id="addBranchPreview"
                                            src="https://ui-avatars.com/api/?name=Branch&background=7F5539&color=FFFFFF"
                                            alt="Branch Preview"
                                            class="h-32 w-32 rounded-lg object-cover border-2 border-gray-300">
                                    </div>
                                    <div class="flex-1">
                                        <input id="addBranchProfile" type="file" name="branch_profile"
                                            accept="image/*"
                                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#7F5539] file:text-white hover:file:bg-[#4A2C1D]">
                                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (Will be optimized)</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Branch Name -->
                            <div>
                                <label for="addBranchName" class="block text-sm font-medium text-gray-700">Branch Name</label>
                                <input type="text" name="branch_name" id="addBranchName"
                                    value="{{ old('branch_name') }}"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                            </div>

                            <!-- Location -->
                            <div>
                                <label for="addLocation" class="block text-sm font-medium text-gray-700">Location (City/Area)</label>
                                <input type="text" name="location" id="addLocation" value="{{ old('location') }}"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                <p class="text-xs text-gray-500 mt-1">e.g., Matina, Davao City</p>
                            </div>

                            <!-- Full Address with Geocoding -->
                            <div>
                                <label for="addAddress" class="block text-sm font-medium text-gray-700">Complete Address</label>
                                <div class="flex gap-2">
                                    <input type="text" name="address" id="addAddress" 
                                        value="{{ old('address') }}"
                                        placeholder="Enter complete street address"
                                        class="flex-1 mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                    <button type="button" @click="geocodeAddress('add')"
                                        class="mt-1 px-4 py-2 bg-[#7F5539] text-white rounded-md hover:bg-[#4A2C1D] transition-colors whitespace-nowrap text-sm flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 6.75V15m6-6.75V15m-6 0a3 3 0 01-3-3V6.75m9 5.25a3 3 0 01-3-3V6.75M3 18.75h18" />
                                        </svg>
                                        Get Coordinates
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Enter the complete address and click "Get Coordinates" to auto-fill latitude and longitude.</p>
                            </div>

                            <!-- Coordinates (Auto-filled) -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="addLatitude" class="block text-sm font-medium text-gray-700">Latitude</label>
                                    <input type="number" name="latitude" id="addLatitude" 
                                        value="{{ old('latitude') }}"
                                        step="any"
                                        placeholder="Auto-filled"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-gray-50 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                </div>
                                <div>
                                    <label for="addLongitude" class="block text-sm font-medium text-gray-700">Longitude</label>
                                    <input type="number" name="longitude" id="addLongitude" 
                                        value="{{ old('longitude') }}"
                                        step="any"
                                        placeholder="Auto-filled"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-gray-50 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                </div>
                            </div>

                            <!-- Geocoding Status -->
                            <div id="addGeocodingStatus" class="hidden">
                                <div id="addGeocodingStatusContent" class="p-3 rounded-lg text-sm">
                                </div>
                            </div>

                            <!-- Google Map URL -->
                            <div>
                                <label for="addGoogleMapUrl" class="block text-sm font-medium text-gray-700">Google Map URL</label>
                                <input type="url" name="google_map_url" id="addGoogleMapUrl"
                                    value="{{ old('google_map_url') }}"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                            </div>

                            <!-- Features -->
                            <div>
                                <label for="addFeatures" class="block text-sm font-medium text-gray-700">Features</label>
                                <input type="text" name="features" id="addFeatures" value="{{ old('features') }}"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                <p class="text-xs text-gray-500 mt-1">Separate features with commas (e.g., WiFi, Parking, Outdoor Seating)</p>
                            </div>

                            <!-- Open Hours -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Open Hours</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Opening Time -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Opening Time</label>
                                        <div class="grid grid-cols-3 gap-2">
                                            <select name="open_hour"
                                                class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                                @for ($h = 1; $h <= 12; $h++)
                                                    @php $hourValue = sprintf('%02d', $h); @endphp
                                                    <option value="{{ $hourValue }}" {{ $h == 8 ? 'selected' : '' }}>
                                                        {{ $hourValue }}
                                                    </option>
                                                @endfor
                                            </select>
                                            <select name="open_minute"
                                                class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                                @for ($m = 0; $m < 60; $m += 15)
                                                    @php $minuteValue = sprintf('%02d', $m); @endphp
                                                    <option value="{{ $minuteValue }}" {{ $m == 0 ? 'selected' : '' }}>
                                                        {{ $minuteValue }}
                                                    </option>
                                                @endfor
                                            </select>
                                            <select name="open_ampm"
                                                class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                                <option value="AM" selected>AM</option>
                                                <option value="PM">PM</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Closing Time -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Closing Time</label>
                                        <div class="grid grid-cols-3 gap-2">
                                            <select name="close_hour"
                                                class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                                @for ($h = 1; $h <= 12; $h++)
                                                    @php $hourValue = sprintf('%02d', $h); @endphp
                                                    <option value="{{ $hourValue }}" {{ $h == 5 ? 'selected' : '' }}>
                                                        {{ $hourValue }}
                                                    </option>
                                                @endfor
                                            </select>
                                            <select name="close_minute"
                                                class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                                @for ($m = 0; $m < 60; $m += 15)
                                                    @php $minuteValue = sprintf('%02d', $m); @endphp
                                                    <option value="{{ $minuteValue }}" {{ $m == 0 ? 'selected' : '' }}>
                                                        {{ $minuteValue }}
                                                    </option>
                                                @endfor
                                            </select>
                                            <select name="close_ampm"
                                                class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                                <option value="AM">AM</option>
                                                <option value="PM" selected>PM</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Open Days -->
                            <div>
                                <label for="addOpenDays" class="block text-sm font-medium text-gray-700">Open Days</label>
                                <input type="text" name="open_days" id="addOpenDays" value="{{ old('open_days') }}"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                <p class="text-xs text-gray-500 mt-1">e.g., Monday-Friday, Weekends, Everyday</p>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" @click="closeAddModal()"
                                class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539]">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539]">
                                Add Branch
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- EDIT BRANCH MODAL WITH GEOCODING                             -->
        <!-- ============================================================ -->
        <div x-show="showEditModal" x-cloak x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 z-[9999] overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeEditModal()"></div>

                <div class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle w-full max-w-3xl sm:p-6 md:p-10">
                    <!-- Header -->
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Edit Branch</h3>
                        <button @click="closeEditModal()" type="button"
                            class="text-gray-400 hover:text-gray-500 focus:outline-none">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Form -->
                    <template x-if="editBranchData">
                        <form :action="`{{ url('sub_one/branches/update') }}/${editBranchData.uuid}`" method="POST"
                            enctype="multipart/form-data" id="editBranchForm">
                            @csrf
                            @method('PATCH')
                            <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
                                <!-- Branch Profile -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Branch Profile</label>
                                    <div class="mt-1 flex items-center space-x-4">
                                        <div class="flex-shrink-0">
                                            <img :src="editBranchData.branch_profile ?
                                                `/storage/app/public/${editBranchData.branch_profile}` :
                                                `https://ui-avatars.com/api/?name=${encodeURIComponent(editBranchData.branch_name)}&background=7F5539&color=FFFFFF`"
                                                alt="Branch Preview"
                                                class="h-32 w-32 rounded-lg object-cover border-2 border-gray-300"
                                                id="editBranchPreview">
                                        </div>
                                        <div class="flex-1">
                                            <input type="file" name="branch_profile" accept="image/*"
                                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#7F5539] file:text-white hover:file:bg-[#4A2C1D]"
                                                @change="handleEditImageUpload($event)">
                                            <p class="text-xs text-gray-500 mt-1">Recommended: Square image, max 5MB</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Branch Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Branch Name</label>
                                    <input type="text" name="branch_name" x-model="editBranchData.branch_name"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                </div>

                                <!-- Location -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Location (City/Area)</label>
                                    <input type="text" name="location" x-model="editBranchData.location"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                </div>

                                <!-- Full Address with Geocoding -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Complete Address</label>
                                    <div class="flex gap-2">
                                        <input type="text" name="address" id="editAddress" x-model="editBranchData.address"
                                            placeholder="Enter complete street address"
                                            class="flex-1 mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                        <button type="button" @click="geocodeAddress('edit')"
                                            class="mt-1 px-4 py-2 bg-[#7F5539] text-white rounded-md hover:bg-[#4A2C1D] transition-colors whitespace-nowrap text-sm flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 6.75V15m6-6.75V15m-6 0a3 3 0 01-3-3V6.75m9 5.25a3 3 0 01-3-3V6.75M3 18.75h18" />
                                            </svg>
                                            Get Coordinates
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Click "Get Coordinates" to auto-fill latitude and longitude from the address.</p>
                                </div>

                                <!-- Coordinates -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Latitude</label>
                                        <input type="number" name="latitude" id="editLatitude" x-model="editBranchData.latitude"
                                            step="any"
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-gray-50 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Longitude</label>
                                        <input type="number" name="longitude" id="editLongitude" x-model="editBranchData.longitude"
                                            step="any"
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-gray-50 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                    </div>
                                </div>

                                <!-- Geocoding Status -->
                                <div id="editGeocodingStatus" class="hidden">
                                    <div id="editGeocodingStatusContent" class="p-3 rounded-lg text-sm">
                                    </div>
                                </div>

                                <!-- Google Map URL -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Google Map URL</label>
                                    <input type="url" name="google_map_url" x-model="editBranchData.google_map_url"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                </div>

                                <!-- Features -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Features</label>
                                    <input type="text" name="features" x-model="editBranchData.features"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                    <p class="text-xs text-gray-500 mt-1">Separate features with commas</p>
                                </div>

                                <!-- Open Hours -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Open Hours</label>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- Opening Time -->
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Opening Time</label>
                                            <div class="grid grid-cols-3 gap-2">
                                                <select name="open_hour"
                                                    class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                                    @for ($h = 1; $h <= 12; $h++)
                                                        @php $hourValue = sprintf('%02d', $h); @endphp
                                                        <option :selected="getTimeComponent(editBranchData?.open_time, 'hour') === '{{ $hourValue }}'"
                                                            value="{{ $hourValue }}">
                                                            {{ $hourValue }}
                                                        </option>
                                                    @endfor
                                                </select>
                                                <select name="open_minute"
                                                    class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                                    @for ($m = 0; $m < 60; $m += 15)
                                                        @php $minuteValue = sprintf('%02d', $m); @endphp
                                                        <option :selected="getTimeComponent(editBranchData?.open_time, 'minute') === '{{ $minuteValue }}'"
                                                            value="{{ $minuteValue }}">
                                                            {{ $minuteValue }}
                                                        </option>
                                                    @endfor
                                                </select>
                                                <select name="open_ampm"
                                                    class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                                    <option :selected="getTimeComponent(editBranchData?.open_time, 'ampm') === 'AM'" value="AM">AM</option>
                                                    <option :selected="getTimeComponent(editBranchData?.open_time, 'ampm') === 'PM'" value="PM">PM</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Closing Time -->
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Closing Time</label>
                                            <div class="grid grid-cols-3 gap-2">
                                                <select name="close_hour"
                                                    class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                                    @for ($h = 1; $h <= 12; $h++)
                                                        @php $hourValue = sprintf('%02d', $h); @endphp
                                                        <option :selected="getTimeComponent(editBranchData?.close_time, 'hour') === '{{ $hourValue }}'"
                                                            value="{{ $hourValue }}">
                                                            {{ $hourValue }}
                                                        </option>
                                                    @endfor
                                                </select>
                                                <select name="close_minute"
                                                    class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                                    @for ($m = 0; $m < 60; $m += 15)
                                                        @php $minuteValue = sprintf('%02d', $m); @endphp
                                                        <option :selected="getTimeComponent(editBranchData?.close_time, 'minute') === '{{ $minuteValue }}'"
                                                            value="{{ $minuteValue }}">
                                                            {{ $minuteValue }}
                                                        </option>
                                                    @endfor
                                                </select>
                                                <select name="close_ampm"
                                                    class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                                    <option :selected="getTimeComponent(editBranchData?.close_time, 'ampm') === 'AM'" value="AM">AM</option>
                                                    <option :selected="getTimeComponent(editBranchData?.close_time, 'ampm') === 'PM'" value="PM">PM</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Open Days -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Open Days</label>
                                    <input type="text" name="open_days" x-model="editBranchData.open_days"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm">
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="mt-6 flex justify-end space-x-3">
                                <button type="button" @click="closeEditModal()"
                                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539]">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539]">
                                    Update Branch
                                </button>
                            </div>
                        </form>
                    </template>
                </div>
            </div>
        </div>

        <!-- Archive Confirmation Modal -->
        <div x-show="showArchiveModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[9999] overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-show="showArchiveModal"
                    x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    @click="closeArchiveModal()"></div>

                <div x-show="showArchiveModal" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full sm:p-6">
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
                                    Archive <strong class="text-[#4A2C1D]" x-text="selectedBranch?.branch_name"></strong>
                                    branch?
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
                            <span class="sm:hidden">Confirm</span>
                            <span class="hidden sm:inline">Confirm Archive</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Discount Modal -->
        <div x-show="showDiscountModal" x-cloak x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 z-[9999] overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeDiscountModal()"></div>

                <div class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle w-full max-w-6xl sm:p-6">
                    <!-- Header -->
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <span x-text="discountData.branch ? 'Manage Discounts - ' + discountData.branch.branch_name : 'Manage Discounts'"></span>
                        </h3>
                        <button @click="closeDiscountModal()" type="button"
                            class="text-gray-400 hover:text-gray-500 focus:outline-none">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Discount Form -->
                    <div x-data="discountState()" x-init="init()" class="space-y-6">
                        <!-- Discount Type & Value -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Discount Type</label>
                                <div class="flex space-x-4">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="discount_type" x-model="discountType" value="percentage"
                                            class="form-radio h-4 w-4 text-[#7F5539] focus:ring-[#7F5539]">
                                        <span class="ml-2 text-sm text-gray-700">Percentage (%)</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="discount_type" x-model="discountType" value="amount"
                                            class="form-radio h-4 w-4 text-[#7F5539] focus:ring-[#7F5539]">
                                        <span class="ml-2 text-sm text-gray-700">Amount (₱)</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <span x-text="discountType === 'percentage' ? 'Discount Percentage' : 'Discount Amount'"></span>
                                </label>
                                <div class="relative">
                                    <input type="number" x-model="discountValue" :min="0" :max="discountType === 'percentage' ? 100 : null"
                                        step="0.01"
                                        class="block w-full border border-gray-300 rounded-md shadow-sm py-2 pl-3 pr-8 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm"
                                        placeholder="Enter discount value">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm" x-text="discountType === 'percentage' ? '%' : '₱'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Select All Toggle -->
                        <div class="flex items-center">
                            <input type="checkbox" id="selectAllServices" x-model="selectAll"
                                @change="toggleSelectAll()"
                                class="h-4 w-4 text-[#7F5539] focus:ring-[#7F5539] border-gray-300 rounded">
                            <label for="selectAllServices" class="ml-2 block text-sm font-medium text-gray-700">
                                Select All Services
                            </label>
                        </div>

                        <!-- Tab Navigation -->
                        <div class="border-b border-gray-200">
                            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                                <button @click="activeTab = 'all'"
                                    :class="activeTab === 'all' 
                                        ? 'border-[#7F5539] text-[#7F5539]' 
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    All Services (<span x-text="getTotalServices()"></span>)
                                </button>
                                <button @click="activeTab = 'discounted'"
                                    :class="activeTab === 'discounted' 
                                        ? 'border-[#7F5539] text-[#7F5539]' 
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    Discounted (<span x-text="getDiscountedServicesCount()"></span>)
                                </button>
                                <button @click="activeTab = 'not_discounted'"
                                    :class="activeTab === 'not_discounted' 
                                        ? 'border-[#7F5539] text-[#7F5539]' 
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    Not Discounted (<span x-text="getNotDiscountedServicesCount()"></span>)
                                </button>
                            </nav>
                        </div>

                        <!-- Services List -->
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                                <h4 class="text-sm font-medium text-gray-700">
                                    <template x-if="activeTab === 'all'">All Services</template>
                                    <template x-if="activeTab === 'discounted'">Discounted Services</template>
                                    <template x-if="activeTab === 'not_discounted'">Services Without Discount</template>
                                </h4>
                                <div class="text-xs text-gray-500">
                                    <span x-text="getFilteredServicesCount()"></span> service(s) shown
                                </div>
                            </div>
                            <div class="max-h-96 overflow-y-auto p-4">
                                <!-- All Services Tab -->
                                <template x-if="activeTab === 'all'">
                                    <template x-for="category in discountData.categories" :key="'all-' + category.id">
                                        <div class="mb-6 last:mb-0" x-show="category.service_names && category.service_names.length > 0">
                                            <div class="flex items-center justify-between mb-3 bg-gray-100 px-3 py-2 rounded-lg">
                                                <h5 class="text-sm font-bold text-gray-800 flex items-center">
                                                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                                    </svg>
                                                    <template x-if="category.service_category">
                                                        <span x-text="category.service_category"></span>
                                                    </template>
                                                    <template x-if="!category.service_category && category.name">
                                                        <span x-text="category.name"></span>
                                                    </template>
                                                    <template x-if="!category.service_category && !category.name">
                                                        <span>Uncategorized</span>
                                                    </template>
                                                    <span class="ml-2 text-xs font-normal text-gray-500">
                                                        (<span x-text="category.service_names ? category.service_names.length : 0"></span> services)
                                                    </span>
                                                </h5>
                                                <button @click="toggleCategorySelection('all', category.id)"
                                                    class="text-xs text-[#7F5539] hover:text-[#4A2C1D] font-bold uppercase tracking-wide">
                                                    <span x-text="isCategorySelected('all', category.id) ? 'Deselect All' : 'Select All'"></span>
                                                </button>
                                            </div>
                                            <div class="space-y-2 ml-2">
                                                <template x-for="service in category.service_names" :key="service.id">
                                                    <div class="flex items-center justify-between hover:bg-gray-50 p-3 rounded-lg border border-gray-100 transition-colors duration-150">
                                                        <div class="flex items-center flex-1">
                                                            <input type="checkbox" :id="'service_all_' + service.id"
                                                                x-model="selectedServices" :value="service.id"
                                                                class="h-4 w-4 text-[#7F5539] focus:ring-[#7F5539] border-gray-300 rounded cursor-pointer">
                                                            <label :for="'service_all_' + service.id" class="ml-3 flex-1 cursor-pointer">
                                                                <span class="text-sm font-medium text-gray-700" x-text="service.service_name"></span>
                                                                <div class="text-xs text-gray-500 mt-1 space-y-1">
                                                                    <div class="mb-1">
                                                                        <span class="inline-block bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded-full">
                                                                            <template x-if="category.service_category">
                                                                                <span x-text="category.service_category"></span>
                                                                            </template>
                                                                            <template x-if="!category.service_category && category.name">
                                                                                <span x-text="category.name"></span>
                                                                            </template>
                                                                            <template x-if="!category.service_category && !category.name">
                                                                                <span>Uncategorized</span>
                                                                            </template>
                                                                        </span>
                                                                    </div>
                                                                    <div class="flex items-center space-x-4">
                                                                        <span>Original Price: <span class="font-semibold">₱<span x-text="formatPrice(service.old_price || service.price)"></span></span></span>
                                                                        <template x-if="service.discount">
                                                                            <span class="text-green-600 font-medium">
                                                                                Discount: <span x-text="service.discount"></span><span x-text="service.discount_type === 'percentage' ? '%' : '₱'"></span>
                                                                            </span>
                                                                        </template>
                                                                    </div>
                                                                </div>
                                                            </label>
                                                        </div>
                                                        <div class="text-right">
                                                            <template x-if="service.discount">
                                                                <div class="space-y-1">
                                                                    <div class="text-xs text-gray-400 line-through">₱<span x-text="formatPrice(service.old_price)"></span></div>
                                                                    <div class="text-sm font-semibold text-green-600">₱<span x-text="formatPrice(service.price)"></span></div>
                                                                    <div class="text-xs text-green-500">You save: ₱<span x-text="formatPrice((service.old_price || service.price) - service.price)"></span></div>
                                                                </div>
                                                            </template>
                                                            <template x-if="!service.discount">
                                                                <div class="text-sm font-semibold text-gray-900">₱<span x-text="formatPrice(service.price)"></span></div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </template>

                                <!-- Discounted Services Tab -->
                                <template x-if="activeTab === 'discounted'">
                                    <template x-for="category in discountData.categories" :key="'discounted-' + category.id">
                                        <div class="mb-6 last:mb-0" 
                                             x-show="category.service_names && category.service_names.filter(s => s.discount).length > 0">
                                            <div class="flex items-center justify-between mb-3 bg-green-50 px-3 py-2 rounded-lg border border-green-100">
                                                <h5 class="text-sm font-bold text-gray-800 flex items-center">
                                                    <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                    </svg>
                                                    <template x-if="category.service_category">
                                                        <span x-text="category.service_category"></span>
                                                    </template>
                                                    <template x-if="!category.service_category && category.name">
                                                        <span x-text="category.name"></span>
                                                    </template>
                                                    <template x-if="!category.service_category && !category.name">
                                                        <span>Uncategorized</span>
                                                    </template>
                                                    <span class="ml-2 text-xs font-normal text-green-700">
                                                        (<span x-text="category.service_names ? category.service_names.filter(s => s.discount).length : 0"></span> discounted)
                                                    </span>
                                                </h5>
                                                <button @click="toggleCategorySelection('discounted', category.id)"
                                                    class="text-xs text-green-700 hover:text-green-900 font-bold uppercase tracking-wide">
                                                    <span x-text="isCategorySelected('discounted', category.id) ? 'Deselect All' : 'Select All'"></span>
                                                </button>
                                            </div>
                                            <div class="space-y-2 ml-2">
                                                <template x-for="service in category.service_names.filter(s => s.discount)" :key="service.id">
                                                    <div class="flex items-center justify-between hover:bg-green-50 p-3 rounded-lg border border-green-100 transition-colors duration-150">
                                                        <div class="flex items-center flex-1">
                                                            <input type="checkbox" :id="'service_discounted_' + service.id"
                                                                x-model="selectedServices" :value="service.id"
                                                                class="h-4 w-4 text-green-600 focus:ring-green-500 border-green-300 rounded cursor-pointer">
                                                            <label :for="'service_discounted_' + service.id" class="ml-3 flex-1 cursor-pointer">
                                                                <span class="text-sm font-medium text-gray-700" x-text="service.service_name"></span>
                                                                <div class="text-xs text-gray-500 mt-1 space-y-1">
                                                                    <div class="mb-1">
                                                                        <span class="inline-block bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full">
                                                                            <template x-if="category.service_category">
                                                                                <span x-text="category.service_category"></span>
                                                                            </template>
                                                                            <template x-if="!category.service_category && category.name">
                                                                                <span x-text="category.name"></span>
                                                                            </template>
                                                                            <template x-if="!category.service_category && !category.name">
                                                                                <span>Uncategorized</span>
                                                                            </template>
                                                                        </span>
                                                                    </div>
                                                                    <div class="flex items-center space-x-4">
                                                                        <span>Original Price: <span class="font-semibold">₱<span x-text="formatPrice(service.old_price)"></span></span></span>
                                                                        <span class="text-green-600 font-medium">
                                                                            Discount: <span x-text="service.discount"></span><span x-text="service.discount_type === 'percentage' ? '%' : '₱'"></span>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </label>
                                                        </div>
                                                        <div class="text-right">
                                                            <div class="space-y-1">
                                                                <div class="text-xs text-gray-400 line-through">₱<span x-text="formatPrice(service.old_price)"></span></div>
                                                                <div class="text-sm font-semibold text-green-600">₱<span x-text="formatPrice(service.price)"></span></div>
                                                                <div class="text-xs text-green-500">You save: ₱<span x-text="formatPrice(service.old_price - service.price)"></span></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </template>

                                <!-- Not Discounted Services Tab -->
                                <template x-if="activeTab === 'not_discounted'">
                                    <template x-for="category in discountData.categories" :key="'not_discounted-' + category.id">
                                        <div class="mb-6 last:mb-0" 
                                             x-show="category.service_names && category.service_names.filter(s => !s.discount).length > 0">
                                            <div class="flex items-center justify-between mb-3 bg-gray-100 px-3 py-2 rounded-lg">
                                                <h5 class="text-sm font-bold text-gray-800 flex items-center">
                                                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <template x-if="category.service_category">
                                                        <span x-text="category.service_category"></span>
                                                    </template>
                                                    <template x-if="!category.service_category && category.name">
                                                        <span x-text="category.name"></span>
                                                    </template>
                                                    <template x-if="!category.service_category && !category.name">
                                                        <span>Uncategorized</span>
                                                    </template>
                                                    <span class="ml-2 text-xs font-normal text-gray-500">
                                                        (<span x-text="category.service_names ? category.service_names.filter(s => !s.discount).length : 0"></span> without discount)
                                                    </span>
                                                </h5>
                                                <button @click="toggleCategorySelection('not_discounted', category.id)"
                                                    class="text-xs text-[#7F5539] hover:text-[#4A2C1D] font-bold uppercase tracking-wide">
                                                    <span x-text="isCategorySelected('not_discounted', category.id) ? 'Deselect All' : 'Select All'"></span>
                                                </button>
                                            </div>
                                            <div class="space-y-2 ml-2">
                                                <template x-for="service in category.service_names.filter(s => !s.discount)" :key="service.id">
                                                    <div class="flex items-center justify-between hover:bg-gray-50 p-3 rounded-lg border border-gray-100 transition-colors duration-150">
                                                        <div class="flex items-center flex-1">
                                                            <input type="checkbox" :id="'service_not_discounted_' + service.id"
                                                                x-model="selectedServices" :value="service.id"
                                                                class="h-4 w-4 text-[#7F5539] focus:ring-[#7F5539] border-gray-300 rounded cursor-pointer">
                                                            <label :for="'service_not_discounted_' + service.id" class="ml-3 flex-1 cursor-pointer">
                                                                <span class="text-sm font-medium text-gray-700" x-text="service.service_name"></span>
                                                                <div class="text-xs text-gray-500 mt-1">
                                                                    <div class="mb-1">
                                                                        <span class="inline-block bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded-full">
                                                                            <template x-if="category.service_category">
                                                                                <span x-text="category.service_category"></span>
                                                                            </template>
                                                                            <template x-if="!category.service_category && category.name">
                                                                                <span x-text="category.name"></span>
                                                                            </template>
                                                                            <template x-if="!category.service_category && !category.name">
                                                                                <span>Uncategorized</span>
                                                                            </template>
                                                                        </span>
                                                                    </div>
                                                                    <span>Price: <span class="font-semibold">₱<span x-text="formatPrice(service.price)"></span></span></span>
                                                                </div>
                                                            </label>
                                                        </div>
                                                        <div class="text-right">
                                                            <div class="text-sm font-semibold text-gray-900">₱<span x-text="formatPrice(service.price)"></span></div>
                                                            <div class="text-xs text-gray-400">No discount applied</div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </template>
                                
                                <!-- Empty States -->
                                <template x-if="activeTab === 'all' && (!discountData.categories || discountData.categories.length === 0)">
                                    <div class="text-center py-8">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900">No services found</h3>
                                        <p class="mt-1 text-sm text-gray-500">Add services to this branch first.</p>
                                    </div>
                                </template>

                                <template x-if="activeTab === 'discounted' && getDiscountedServicesCount() === 0">
                                    <div class="text-center py-8">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900">No discounted services</h3>
                                        <p class="mt-1 text-sm text-gray-500">Apply discounts to see them here.</p>
                                    </div>
                                </template>

                                <template x-if="activeTab === 'not_discounted' && getNotDiscountedServicesCount() === 0">
                                    <div class="text-center py-8">
                                        <svg class="mx-auto h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900">All services have discounts!</h3>
                                        <p class="mt-1 text-sm text-gray-500">Great! All services in this branch have discounts applied.</p>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Summary Stats -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="text-sm font-medium text-blue-700">Total Services</div>
                                <div class="text-2xl font-bold text-blue-900" x-text="getTotalServices()"></div>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="text-sm font-medium text-green-700">Discounted</div>
                                <div class="text-2xl font-bold text-green-900" x-text="getDiscountedServicesCount()"></div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm font-medium text-gray-700">Not Discounted</div>
                                <div class="text-2xl font-bold text-gray-900" x-text="getNotDiscountedServicesCount()"></div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-between space-x-3">
                            <button @click="removeDiscount()" :disabled="selectedServices.length === 0 || isLoading"
                                class="px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                Remove Discount
                            </button>
                            <div class="flex space-x-3">
                                <button @click="closeDiscountModal()"
                                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539]">
                                    Cancel
                                </button>
                                <button @click="applyDiscount()" :disabled="discountValue <= 0 || selectedServices.length === 0 || isLoading"
                                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539] disabled:opacity-50 disabled:cursor-not-allowed">
                                    Apply Discount
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
                    branch_status: '{{ request('branch_status', '') }}',
                    has_coordinates: '',
                },
                clearFilters() {
                    this.filters = {
                        branch_status: '',
                        has_coordinates: '',
                    };
                },
                applyFilters() {
                    const mainComponent = Alpine.$data(document.querySelector(
                        '[x-data="branchData()"]'));
                    const newFilters = {
                        ...mainComponent.currentFilters,
                        ...this.filters,
                        search: mainComponent.searchQuery
                    };
                    mainComponent.applyFilters(newFilters);
                    mainComponent.removeBodyClass();
                }
            }));

            // Discount state
            Alpine.data('discountState', () => ({
                discountType: 'percentage',
                discountValue: 0,
                selectedServices: [],
                selectAll: false,
                isLoading: false,
                activeTab: 'all',

                init() {
                    Alpine.effect(() => {
                        const mainComponent = Alpine.$data(document.querySelector('[x-data="branchData()"]'));
                        if (mainComponent.discountData.categories) {
                            this.selectedServices = [];
                            this.selectAll = false;
                        }
                    });
                },

                getFilteredServices() {
                    const mainComponent = Alpine.$data(document.querySelector('[x-data="branchData()"]'));
                    if (!mainComponent.discountData.categories) return [];
                    
                    let services = [];
                    mainComponent.discountData.categories.forEach(category => {
                        if (category.service_names) {
                            if (this.activeTab === 'all') {
                                services = services.concat(category.service_names);
                            } else if (this.activeTab === 'discounted') {
                                services = services.concat(category.service_names.filter(s => s.discount));
                            } else if (this.activeTab === 'not_discounted') {
                                services = services.concat(category.service_names.filter(s => !s.discount));
                            }
                        }
                    });
                    return services;
                },

                getFilteredServicesCount() {
                    return this.getFilteredServices().length;
                },

                getTotalServices() {
                    const mainComponent = Alpine.$data(document.querySelector('[x-data="branchData()"]'));
                    if (!mainComponent.discountData.categories) return 0;
                    
                    let total = 0;
                    mainComponent.discountData.categories.forEach(category => {
                        if (category.service_names) {
                            total += category.service_names.length;
                        }
                    });
                    return total;
                },

                getDiscountedServicesCount() {
                    const mainComponent = Alpine.$data(document.querySelector('[x-data="branchData()"]'));
                    if (!mainComponent.discountData.categories) return 0;
                    
                    let count = 0;
                    mainComponent.discountData.categories.forEach(category => {
                        if (category.service_names) {
                            count += category.service_names.filter(s => s.discount).length;
                        }
                    });
                    return count;
                },

                getNotDiscountedServicesCount() {
                    const mainComponent = Alpine.$data(document.querySelector('[x-data="branchData()"]'));
                    if (!mainComponent.discountData.categories) return 0;
                    
                    let count = 0;
                    mainComponent.discountData.categories.forEach(category => {
                        if (category.service_names) {
                            count += category.service_names.filter(s => !s.discount).length;
                        }
                    });
                    return count;
                },

                isCategorySelected(tab, categoryId) {
                    const mainComponent = Alpine.$data(document.querySelector('[x-data="branchData()"]'));
                    const category = mainComponent.discountData.categories?.find(c => c.id === categoryId);
                    if (!category || !category.service_names) return false;
                    
                    let categoryServices;
                    if (tab === 'all') {
                        categoryServices = category.service_names;
                    } else if (tab === 'discounted') {
                        categoryServices = category.service_names.filter(s => s.discount);
                    } else if (tab === 'not_discounted') {
                        categoryServices = category.service_names.filter(s => !s.discount);
                    } else {
                        categoryServices = [];
                    }
                    
                    if (categoryServices.length === 0) return false;
                    
                    return categoryServices.every(service => 
                        this.selectedServices.includes(service.id)
                    );
                },

                toggleCategorySelection(tab, categoryId) {
                    const mainComponent = Alpine.$data(document.querySelector('[x-data="branchData()"]'));
                    const category = mainComponent.discountData.categories?.find(c => c.id === categoryId);
                    if (!category || !category.service_names) return;
                    
                    let categoryServices;
                    if (tab === 'all') {
                        categoryServices = category.service_names;
                    } else if (tab === 'discounted') {
                        categoryServices = category.service_names.filter(s => s.discount);
                    } else if (tab === 'not_discounted') {
                        categoryServices = category.service_names.filter(s => !s.discount);
                    } else {
                        categoryServices = [];
                    }
                    
                    if (categoryServices.length === 0) return;
                    
                    const allSelected = categoryServices.every(service => 
                        this.selectedServices.includes(service.id)
                    );
                    
                    if (allSelected) {
                        this.selectedServices = this.selectedServices.filter(id => 
                            !categoryServices.some(service => service.id === id)
                        );
                    } else {
                        categoryServices.forEach(service => {
                            if (!this.selectedServices.includes(service.id)) {
                                this.selectedServices.push(service.id);
                            }
                        });
                    }
                },

                toggleSelectAll() {
                    if (this.selectAll) {
                        this.selectedServices = [];
                        const filteredServices = this.getFilteredServices();
                        filteredServices.forEach(service => {
                            this.selectedServices.push(service.id);
                        });
                    } else {
                        this.selectedServices = [];
                    }
                },

                async applyDiscount() {
                    const mainComponent = Alpine.$data(document.querySelector('[x-data="branchData()"]'));
                    this.isLoading = true;

                    try {
                        const response = await fetch(`{{ url('sub_one/branches/discount/apply') }}/${mainComponent.discountData.branch.uuid}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                discount_type: this.discountType,
                                discount_value: parseFloat(this.discountValue),
                                selected_services: this.selectedServices
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            mainComponent.showNotification(data.message, 'success');
                            await mainComponent.loadDiscountData(mainComponent.discountData.branch.uuid);
                            this.discountValue = 0;
                            this.selectedServices = [];
                            this.selectAll = false;
                        } else {
                            throw new Error(data.message || 'Failed to apply discount');
                        }
                    } catch (error) {
                        mainComponent.showNotification(error.message, 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                async removeDiscount() {
                    const mainComponent = Alpine.$data(document.querySelector('[x-data="branchData()"]'));
                    this.isLoading = true;

                    try {
                        const response = await fetch(`{{ url('sub_one/branches/discount/remove') }}/${mainComponent.discountData.branch.uuid}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                selected_services: this.selectedServices
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            mainComponent.showNotification(data.message, 'success');
                            await mainComponent.loadDiscountData(mainComponent.discountData.branch.uuid);
                            this.selectedServices = [];
                            this.selectAll = false;
                        } else {
                            throw new Error(data.message || 'Failed to remove discount');
                        }
                    } catch (error) {
                        mainComponent.showNotification(error.message, 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                formatPrice(price) {
                    if (!price) return '0.00';
                    return parseFloat(price).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            }));

            // Main component
            Alpine.data('branchData', () => ({
                // Initial state
                branches: @json($branches->items() ?? []),
                pagination: @json($branches->toArray()),
                stats: @json($stats),
                currentFilters: {
                    branch_status: '{{ request('branch_status', '') }}',
                    search: '{{ request('search', '') }}',
                    has_coordinates: '',
                },
                searchQuery: '{{ request('search', '') }}',
                showFilters: false,
                showAddModal: false,
                showEditModal: false,
                showArchiveModal: false,
                showDiscountModal: false,
                selectedBranch: null,
                editBranchData: null,
                discountData: {
                    branch: null,
                    categories: []
                },
                paginationLinks: [],
                isLoading: false,
                isGeocoding: false,

                init() {
                    this.updatePaginationLinks();
                    this.updateActiveFilters();
                    this.setupAddImagePreview();
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

                    if (this.currentFilters.branch_status) {
                        const statusText = this.getStatusText(this.currentFilters.branch_status);
                        filters.push({
                            key: 'branch_status',
                            label: `Status: ${statusText}`
                        });
                    }

                    if (this.currentFilters.has_coordinates) {
                        filters.push({
                            key: 'has_coordinates',
                            label: this.currentFilters.has_coordinates === '1' ? 'Has Coordinates' : 'No Coordinates'
                        });
                    }

                    return filters;
                },

                // Utility functions
                getStatusClasses(status) {
                    const statusClasses = {
                        0: 'bg-red-200 text-red-800',
                        1: 'bg-green-200 text-green-800',
                        2: 'bg-yellow-200 text-yellow-800',
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

                getTimeComponent(timeString, component) {
                    if (!timeString) return component === 'ampm' ? 'AM' : '01';

                    const time = new Date(`2000-01-01 ${timeString}`);
                    if (isNaN(time.getTime())) {
                        return component === 'ampm' ? 'AM' : '01';
                    }

                    const hour24 = time.getHours();
                    let hour12 = hour24 % 12;
                    if (hour12 === 0) hour12 = 12;
                    const minute = time.getMinutes();
                    const ampm = hour24 >= 12 ? 'PM' : 'AM';

                    switch (component) {
                        case 'hour':
                            return hour12.toString().padStart(2, '0');
                        case 'minute':
                            return minute.toString().padStart(2, '0');
                        case 'ampm':
                            return ampm;
                        default:
                            return '';
                    }
                },

                // ================================================================
                // GEOCODING FUNCTIONS
                // ================================================================
                
                /**
                 * Geocode an address for add or edit modal
                 */
                async geocodeAddress(mode) {
                    const isAdd = mode === 'add';
                    const prefix = isAdd ? 'add' : 'edit';
                    
                    // Find elements
                    let addressInput = document.getElementById(`${prefix}Address`);
                    let latitudeInput = document.getElementById(`${prefix}Latitude`);
                    let longitudeInput = document.getElementById(`${prefix}Longitude`);
                    let statusDiv = document.getElementById(`${prefix}GeocodingStatus`);
                    let statusContent = document.getElementById(`${prefix}GeocodingStatusContent`);
                    
                    // For edit modal, try alternative selectors if not found
                    if (!isAdd) {
                        if (!addressInput) {
                            addressInput = document.querySelector('#editBranchForm [name="address"]') || 
                                          document.querySelector('#editBranchForm input[name="address"]');
                        }
                        if (!latitudeInput) {
                            latitudeInput = document.querySelector('#editBranchForm [name="latitude"]') || 
                                           document.querySelector('#editBranchForm input[name="latitude"]');
                        }
                        if (!longitudeInput) {
                            longitudeInput = document.querySelector('#editBranchForm [name="longitude"]') || 
                                            document.querySelector('#editBranchForm input[name="longitude"]');
                        }
                    }
                    
                    if (!addressInput) {
                        console.error(`Address input not found for ${prefix}`);
                        alert('Could not find address field. Please refresh the page and try again.');
                        return;
                    }

                    const address = addressInput.value.trim();
                    
                    if (!address) {
                        alert('Please enter an address first.');
                        return;
                    }

                    this.isGeocoding = true;

                    // Create status elements if they don't exist
                    if (!statusDiv) {
                        const parent = addressInput.closest('.space-y-4') || addressInput.parentElement;
                        statusDiv = document.createElement('div');
                        statusDiv.id = `${prefix}GeocodingStatus`;
                        statusDiv.className = 'hidden';
                        statusContent = document.createElement('div');
                        statusContent.id = `${prefix}GeocodingStatusContent`;
                        statusContent.className = 'p-3 rounded-lg text-sm';
                        statusDiv.appendChild(statusContent);
                        parent.appendChild(statusDiv);
                    }

                    // Show loading status
                    statusDiv.classList.remove('hidden');
                    statusContent.className = 'p-3 rounded-lg bg-blue-50 text-blue-700 text-sm';
                    statusContent.innerHTML = `
                        <svg class="animate-spin h-4 w-4 inline mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg> 
                        Geocoding address...
                    `;

                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        
                        const formData = new FormData();
                        formData.append('_token', csrfToken);
                        formData.append('address', address);

                        const response = await fetch('{{ route("sub_one.branches.geocode") }}', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        if (!response.ok) {
                            const errorData = await response.json();
                            throw new Error(errorData.message || 'Geocoding request failed');
                        }

                        const data = await response.json();

                        if (data.success) {
                            // Fill the coordinates
                            if (latitudeInput) latitudeInput.value = data.data.latitude;
                            if (longitudeInput) longitudeInput.value = data.data.longitude;

                            // Show success status
                            statusContent.className = 'p-3 rounded-lg bg-green-50 text-green-700 text-sm';
                            statusContent.innerHTML = `
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Coordinates found successfully!
                                <span class="block mt-1 text-xs">
                                    Latitude: ${data.data.latitude}, Longitude: ${data.data.longitude}
                                    ${data.data.formatted_address ? `<br>Formatted: ${data.data.formatted_address}` : ''}
                                    <br><span class="text-green-600">Provider: ${data.data.provider}</span>
                                </span>
                            `;

                            // Highlight the coordinates fields
                            [latitudeInput, longitudeInput].forEach(el => {
                                if (el) {
                                    el.style.transition = 'background-color 0.5s';
                                    el.style.backgroundColor = '#d1fae5';
                                    setTimeout(() => {
                                        el.style.backgroundColor = '#f9fafb';
                                    }, 2000);
                                }
                            });

                            if (typeof this.showNotification === 'function') {
                                this.showNotification('Address geocoded successfully!', 'success');
                            }
                        } else {
                            // Show error status
                            statusContent.className = 'p-3 rounded-lg bg-yellow-50 text-yellow-700 text-sm';
                            statusContent.innerHTML = `
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                </svg>
                                ${data.message || 'Could not geocode this address. Please enter coordinates manually.'}
                            `;
                        }
                    } catch (error) {
                        console.error('Geocoding error:', error);
                        
                        statusContent.className = 'p-3 rounded-lg bg-red-50 text-red-700 text-sm';
                        statusContent.innerHTML = `
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            ${error.message || 'An error occurred during geocoding. Please try again or enter coordinates manually.'}
                        `;
                    } finally {
                        this.isGeocoding = false;
                    }
                },

                /**
                 * Geocode a single branch from the table
                 */
                async geocodeSingleBranch(branch) {
                    if (!branch.address) {
                        this.showNotification('This branch has no address to geocode.', 'error');
                        return;
                    }

                    if (!confirm(`Geocode "${branch.branch_name}"? This will update its latitude and longitude.`)) {
                        return;
                    }

                    this.isLoading = true;

                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        
                        const formData = new FormData();
                        formData.append('_token', csrfToken);
                        formData.append('address', branch.address);

                        const response = await fetch('{{ route("sub_one.branches.geocode") }}', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        if (!response.ok) {
                            throw new Error('Geocoding request failed');
                        }

                        const data = await response.json();

                        if (data.success) {
                            // Update the branch in the local array
                            const index = this.branches.findIndex(b => b.uuid === branch.uuid);
                            if (index !== -1) {
                                this.branches[index].latitude = data.data.latitude;
                                this.branches[index].longitude = data.data.longitude;
                            }
                            
                            this.showNotification(`"${branch.branch_name}" geocoded successfully!`, 'success');
                            
                            // Refresh the branch list
                            await this.applyFilters(this.currentFilters);
                        } else {
                            throw new Error(data.message || 'Failed to geocode branch');
                        }
                    } catch (error) {
                        console.error('Geocoding error:', error);
                        this.showNotification(error.message || 'Failed to geocode branch. Please try again.', 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                /**
                 * Batch geocode all branches without coordinates
                 */
                async batchGeocode() {
                    if (!confirm('This will geocode all branches without coordinates. Continue?')) {
                        return;
                    }

                    this.isLoading = true;

                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        
                        const formData = new FormData();
                        formData.append('_token', csrfToken);

                        const response = await fetch('{{ route("sub_one.branches.batch-geocode") }}', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        if (!response.ok) {
                            throw new Error('Batch geocoding request failed');
                        }

                        const data = await response.json();

                        if (data.success) {
                            this.showNotification(data.message, 'success');
                            // Refresh the branch list
                            await this.applyFilters(this.currentFilters);
                        } else {
                            throw new Error(data.message || 'Failed to batch geocode branches');
                        }
                    } catch (error) {
                        console.error('Batch geocoding error:', error);
                        this.showNotification(error.message || 'Failed to batch geocode branches. Please try again.', 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                // Modal functions
                openAddModal() {
                    this.showAddModal = true;
                    this.addBodyClass();
                    const statusDiv = document.getElementById('addGeocodingStatus');
                    if (statusDiv) statusDiv.classList.add('hidden');
                },

                closeAddModal() {
                    this.showAddModal = false;
                    this.removeBodyClass();
                },

                openEditModal(branch) {
                    this.editBranchData = {
                        ...branch
                    };
                    this.showEditModal = true;
                    this.addBodyClass();
                    const statusDiv = document.getElementById('editGeocodingStatus');
                    if (statusDiv) statusDiv.classList.add('hidden');
                },

                closeEditModal() {
                    this.showEditModal = false;
                    this.editBranchData = null;
                    this.removeBodyClass();
                },

                async openDiscountModal(branch) {
                    try {
                        this.isLoading = true;
                        await this.loadDiscountData(branch.uuid);
                        this.showDiscountModal = true;
                        this.addBodyClass();
                    } catch (error) {
                        this.showNotification('Failed to load discount data', 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                async loadDiscountData(branchUuid) {
                    try {
                        const response = await fetch(`{{ url('sub_one/branches/discount/data') }}/${branchUuid}`, {
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
                            this.discountData = {
                                branch: data.branch,
                                categories: data.categories
                            };
                        } else {
                            throw new Error(data.message || 'Failed to load discount data');
                        }
                    } catch (error) {
                        console.error('Error loading discount data:', error);
                        throw error;
                    }
                },

                closeDiscountModal() {
                    this.showDiscountModal = false;
                    this.discountData = {
                        branch: null,
                        categories: []
                    };
                    this.removeBodyClass();
                },

                handleEditImageUpload(event) {
                    const file = event.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            document.getElementById('editBranchPreview').src = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                },

                setupAddImagePreview() {
                    const addBranchProfileInput = document.getElementById('addBranchProfile');
                    if (addBranchProfileInput) {
                        addBranchProfileInput.addEventListener('change', (e) => {
                            const file = e.target.files[0];
                            if (file) {
                                const reader = new FileReader();
                                reader.onload = (event) => {
                                    document.getElementById('addBranchPreview').src = event
                                        .target.result;
                                };
                                reader.readAsDataURL(file);
                            }
                        });
                    }
                },

                openArchiveModal(branch) {
                    this.selectedBranch = branch;
                    this.showArchiveModal = true;
                    this.addBodyClass();
                },

                closeArchiveModal() {
                    this.showArchiveModal = false;
                    this.removeBodyClass();
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
                            this.branches = data.data;
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
                        branch_status: '',
                        search: '',
                        has_coordinates: '',
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
                            this.branches = data.data;
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
                            this.branches = data.data;
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

                confirmArchive() {
                    if (!this.selectedBranch) return;

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action =
                        `{{ url('sub_one/branches/deactivate') }}/${this.selectedBranch.uuid}`;

                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';

                    const methodField = document.createElement('input');
                    methodField.type = 'hidden';
                    methodField.name = '_method';
                    methodField.value = 'PATCH';

                    form.appendChild(csrfToken);
                    form.appendChild(methodField);

                    document.body.appendChild(form);
                    form.submit();
                },

                closeFilterModal() {
                    this.showFilters = false;
                    this.removeBodyClass();
                },

                showNotification(message, type = 'success') {
                    console.log(`${type}: ${message}`);
                },

                addBodyClass() {
                    document.body.classList.add('modal-open');
                },

                removeBodyClass() {
                    document.body.classList.remove('modal-open');
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

        [x-cloak] {
            display: none !important;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }
        .animate-spin {
            animation: spin 1s linear infinite;
        }
    </style>
@endsection