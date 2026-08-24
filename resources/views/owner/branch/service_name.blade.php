@extends('layouts.app')

@section('title', 'Service Name')

@section('content')
    <div x-data="serviceNameData()" x-init="init()"class="p-4">
        <!-- Header Section -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8">
            <!-- Title -->
            <div class="text-center lg:text-left mb-4 lg:mb-0">
                <h1 class="text-2xl font-bold text-[#4A2C1D]">
                    {{ $branch->branch_name }}
                    <span class="block lg:inline text-lg font-semibold text-[#7F5539] lg:ml-2">
                        ({{ $serviceCategory->service_category }})
                    </span>
                </h1>
                <!-- Discount Button for this Branch -->
                <div class="mt-2">
                    <button @click="openBranchDiscountModal()"
                        class="inline-flex items-center px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm font-medium hover:bg-purple-200 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185zM9.75 9h.008v.008H9.75V9zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 4.5h.008v.008h-.008V13.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                        Manage Branch Discounts
                    </button>
                </div>
            </div>

            <!-- Archive Link -->
            <div class="lg:text-left text-right">
                <a href="{{ route('sub_one.service_names.showDeactivatedServiceName', [$branch->uuid, $serviceCategory->uuid]) }}"
                    class="text-sm font-medium text-[#7F5539] hover:underline">
                    View Archives
                </a>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-6 mb-8">
            <!-- Total Service Names -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Services</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.total_service_names"></p>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-lg">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Available Service Names -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Available</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.available_service_names"></p>
                    </div>
                    <div class="p-3 bg-green-50 rounded-lg">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Unavailable Service Names -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Unavailable</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.unavailable_service_names"></p>
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

        <!-- Back Button -->
        <div class="flex justify-end mb-8">
            <a href="{{ route('sub_one.service_categories.showServiceCategory', $branch->uuid) }}"
                class="inline-flex items-center text-sm font-medium text-[#7F5539] hover:underline">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Service Categories
            </a>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Table Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <!-- Large Screens Layout (Desktop) - Changed from sm:flex to lg:flex -->
                <div class="hidden lg:flex items-center justify-between mb-4">
                    <!-- Left: Header -->
                    <h2 class="text-lg font-semibold text-gray-900">Service Name Records</h2>

                    <!-- Right: Search + Filter + Add Button -->
                    <div class="flex items-center space-x-3">
                        <!-- Search Input -->
                        <div class="relative w-80">
                            <input type="text" x-model="searchQuery" @input.debounce.500ms="performSearch()"
                                placeholder="Search by service name, space type, or duration..."
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

                        <!-- Add Service Name Button -->
                        <button @click="openAddModal()"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539] flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.5" class="w-5 h-5 mr-2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            Add Service
                        </button>
                    </div>
                </div>

                <!-- Small to Tablet Screens Layout (Mobile + Tablet) - Changed from sm:hidden to lg:hidden -->
                <div class="lg:hidden space-y-4">
                    <!-- First Row: Service Name Records + Add Button -->
                    <div class="flex items-center justify-between">
                        <!-- Left: Header -->
                        <h2 class="text-lg font-semibold text-gray-900">Service Name Records</h2>

                        <!-- Right: Add Button -->
                        <button @click="openAddModal()"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539] flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.5" class="w-5 h-5 mr-2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <!-- Adaptive Label: "Add" on Mobile, "Add Service" on Tablet -->
                            <span class="sm:hidden">Add</span>
                            <span class="hidden sm:inline">Add Service</span>
                        </button>
                    </div>

                    <!-- Second Row: Search + Filter -->
                    <div class="flex items-center space-x-3">
                        <!-- Search Input -->
                        <div class="relative flex-1">
                            <input type="text" x-model="searchQuery" @input.debounce.500ms="performSearch()"
                                placeholder="Search by service name, space type, or duration..."
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Service Name
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Price & Discount
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Duration
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Space Type
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
                        <template x-for="service in serviceNames" :key="service.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <!-- Service Name -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900" x-text="service.service_name"></div>
                                    <template x-if="service.discount">
                                        <div class="flex items-center mt-1">
                                            <span class="bg-purple-100 text-purple-800 text-xs font-medium px-2 py-0.5 rounded-full">
                                                <template x-if="service.discount_type === 'percentage'">
                                                    <span x-text="service.discount + '% OFF'"></span>
                                                </template>
                                                <template x-if="service.discount_type === 'amount'">
                                                    <span x-text="'₱' + service.discount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' OFF'"></span>
                                                </template>
                                            </span>
                                        </div>
                                    </template>
                                </td>

                                <!-- Price & Discount -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <template x-if="service.discount">
                                        <div>
                                            <div class="text-sm text-gray-400 line-through">
                                                ₱<span x-text="formatPrice(service.old_price)"></span>
                                            </div>
                                            <div class="text-sm font-semibold text-green-600">
                                                ₱<span x-text="formatPrice(service.price)"></span>
                                            </div>
                                            <div class="text-xs text-green-500">
                                                Save: ₱<span x-text="formatPrice(service.old_price - service.price)"></span>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="!service.discount">
                                        <div class="text-sm font-semibold text-gray-900">
                                            ₱<span x-text="formatPrice(service.price)"></span>
                                        </div>
                                    </template>
                                </td>

                                <!-- Duration -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" x-text="service.time_duration"></div>
                                </td>

                                <!-- Space Type -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-wrap gap-1 max-w-[200px]" x-data="{
                                        getSpaceTypes(spaceType) {
                                            if (!spaceType) return [];
                                            return spaceType.split(',').map(type => type.trim());
                                        }
                                    }">
                                        <template x-for="type in getSpaceTypes(service.space_type)" :key="type">
                                            <span class="bg-[#7F5539] text-white text-xs font-medium px-2 py-1 rounded-full whitespace-nowrap">
                                                <span x-text="type"></span>
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
                                                :class="getStatusClasses(service.service_name_status)">
                                                <span x-text="getStatusText(service.service_name_status)"></span>
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
                                                <form :id="'update-service-status-' + service.uuid + '-1'"
                                                    :action="'{{ url('sub_one/service_names/status') }}/' + service.uuid"
                                                    method="POST" class="hidden">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="service_name_status" value="1">
                                                </form>
                                                <a href="#"
                                                    @click.prevent="document.getElementById('update-service-status-' + service.uuid + '-1').submit(); open = false;"
                                                    class="block px-4 py-2 text-sm hover:bg-gray-100"
                                                    :class="service.service_name_status === 1 ?
                                                        'bg-green-50 text-green-700 font-medium' : 'text-gray-700'">
                                                    Available
                                                </a>

                                                <!-- Unavailable Option -->
                                                <form :id="'update-service-status-' + service.uuid + '-0'"
                                                    :action="'{{ url('sub_one/service_names/status') }}/' + service.uuid"
                                                    method="POST" class="hidden">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="service_name_status" value="0">
                                                </form>
                                                <a href="#"
                                                    @click.prevent="document.getElementById('update-service-status-' + service.uuid + '-0').submit(); open = false;"
                                                    class="block px-4 py-2 text-sm hover:bg-gray-100"
                                                    :class="service.service_name_status === 0 ?
                                                        'bg-red-50 text-red-700 font-medium' : 'text-gray-700'">
                                                    Unavailable
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <!-- Seats Button -->
                                        <div class="relative group">
                                            <a :href="'{{ url('sub_one/seats') }}/' + '{{ $branch->uuid }}' + '/' +
                                            '{{ $serviceCategory->uuid }}' + '/' + service.uuid"
                                                class="text-blue-600 hover:text-blue-800 transition-colors p-2 rounded-full hover:bg-gray-100 flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                                                </svg>
                                            </a>
                                            <!-- Seats Label -->
                                            <span
                                                class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs font-medium px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none">
                                                View Seats
                                            </span>
                                        </div>

                                        <!-- Edit Button -->
                                        <div class="relative group">
                                            <button @click="openEditModal(service)"
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
                                                Edit Service
                                            </span>
                                        </div>

                                        <!-- Archive Button -->
                                        <div class="relative group">
                                            <button @click="openArchiveModal(service)"
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
                                                Archive Service
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <tr x-show="!serviceNames.length">
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" />
                                        </path>
                                    </svg>
                                    <h5 class="text-sm font-medium text-gray-900"
                                        x-text="hasActiveFilters ? 'No services match your filters' : 'No services found'">
                                    </h5>
                                    <p class="text-sm text-gray-500"
                                        x-text="hasActiveFilters ? 'Try adjusting your filters.' : 'Add your first service to get started.'">
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
                <div
                    class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl sm:my-8 sm:align-middle max-w-md w-full sm:p-6">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Filter Services</h3>

                        <div x-data="filterState()">
                            <!-- Filter Inputs -->
                            <div class="space-y-4">
                                <!-- Status Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select x-model="filters.service_name_status"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                        <option value="">All Status</option>
                                        <option value="1">Available</option>
                                        <option value="0">Unavailable</option>
                                    </select>
                                </div>

                                <!-- Discount Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Discount</label>
                                    <select x-model="filters.discount_status"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                        <option value="">All Discount Status</option>
                                        <option value="discounted">Discounted</option>
                                        <option value="not_discounted">Not Discounted</option>
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

        <!-- Add Service Name Modal -->
        <div x-show="showAddModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[9999] overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeAddModal()"></div>

                <!-- Fixed width: 800px max, responsive down to mobile -->
                <div
                    class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle w-full max-w-3xl">
                    <!-- Header -->
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Add New Service</h3>
                        <button @click="closeAddModal()" type="button"
                            class="text-gray-400 hover:text-gray-500 focus:outline-none">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Form -->
                    <form id="addServiceNameForm" @submit.prevent="submitAddForm">
                        @csrf
                        <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                        <input type="hidden" name="service_category_id" value="{{ $serviceCategory->id }}">

                        <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
                            <!-- Service Name -->
                            <div>
                                <label for="addServiceName" class="block text-sm font-medium text-gray-700">Service
                                    Name</label>
                                <input type="text" name="service_name" id="addServiceName" required
                                    x-model="addFormData.service_name"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm"
                                    placeholder="Enter service name">
                            </div>

                            <!-- Price -->
                            <div>
                                <label for="addPrice" class="block text-sm font-medium text-gray-700">Price</label>
                                <input type="number" step="0.01" min="0" name="price" id="addPrice"
                                    required x-model="addFormData.price"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm"
                                    placeholder="Enter price">
                            </div>

                            <!-- Time Duration -->
                            <div>
                                <label for="addTimeDuration" class="block text-sm font-medium text-gray-700">Time
                                    Duration</label>
                                <input type="text" name="time_duration" id="addTimeDuration" required
                                    x-model="addFormData.time_duration"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm"
                                    placeholder="Enter time duration (e.g., 1 hour)">
                            </div>

                            <!-- Space Type -->
                            <div>
                                <label for="addSpaceType" class="block text-sm font-medium text-gray-700">
                                    Space Type
                                </label>
                                <input type="text" 
                                       name="space_type" 
                                       id="addSpaceType" 
                                       required
                                       x-model="addFormData.space_type"
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm"
                                       placeholder="Enter space types separated by commas (e.g., Solo, Shared, Group)">
                                <p class="mt-1 text-xs text-gray-500">Separate multiple types with commas</p>
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
                                <span x-show="!isSubmitting">Add Service</span>
                                <span x-show="isSubmitting">Adding...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Service Name Modal -->
        <div x-show="showEditModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[9999] overflow-y-auto" style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeEditModal()"></div>

        <!-- Fixed width: 800px max, responsive down to mobile -->
        <div
            class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle w-full max-w-3xl">
            <!-- Header -->
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Edit Service</h3>
                <button @click="closeEditModal()" type="button"
                    class="text-gray-400 hover:text-gray-500 focus:outline-none">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Content Container -->
            <div>
                <!-- Loading State -->
                <div x-show="!editServiceNameData && showEditModal" class="py-8 text-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#7F5539] mx-auto"></div>
                    <p class="mt-2 text-sm text-gray-500">Loading service data...</p>
                </div>

                <!-- Form (only rendered when data is loaded) -->
                <template x-if="editServiceNameData">
                    <form id="editServiceNameForm" @submit.prevent="submitEditForm">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                        <input type="hidden" name="service_category_id" value="{{ $serviceCategory->id }}">
                        
                        <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
                            <!-- Service Name -->
                            <div>
                                <label for="editServiceName" class="block text-sm font-medium text-gray-700">Service Name</label>
                                <input type="text" name="service_name" id="editServiceName" required
                                    x-model="editServiceNameData.service_name"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm"
                                    placeholder="Enter service name">
                            </div>

                            <!-- Price -->
                            <div>
                                <label for="editPrice" class="block text-sm font-medium text-gray-700">Price</label>
                                <input type="number" step="0.01" min="0" name="price" id="editPrice" required
                                    x-model="editServiceNameData.price"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm"
                                    placeholder="Enter price">
                            </div>

                            <!-- Time Duration -->
                            <div>
                                <label for="editTimeDuration" class="block text-sm font-medium text-gray-700">Time Duration</label>
                                <input type="text" name="time_duration" id="editTimeDuration" required
                                    x-model="editServiceNameData.time_duration"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm"
                                    placeholder="Enter time duration (e.g., 1 hour)">
                            </div>

                            <!-- Space Type -->
                            <div>
                                <label for="editSpaceType" class="block text-sm font-medium text-gray-700">
                                    Space Type
                                </label>
                                <input type="text" 
                                       name="space_type" 
                                       id="editSpaceType" 
                                       required
                                       x-model="editServiceNameData.space_type"
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#7F5539] focus:border-[#7F5539] sm:text-sm"
                                       placeholder="Enter space types separated by commas (e.g., Solo, Shared, Group)">
                                <p class="mt-1 text-xs text-gray-500">Separate multiple types with commas</p>
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
                                <span x-show="!isSubmitting">Update Service</span>
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
                                        x-text="selectedService?.service_name"></strong> service?
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

        <!-- Branch Discount Modal (Similar to Branch Controller) -->
<div x-show="showBranchDiscountModal" x-cloak x-transition:enter="ease-out duration-300"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0" class="fixed inset-0 z-[9999] overflow-y-auto" style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeBranchDiscountModal()"></div>

        <div class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle w-full max-w-6xl sm:p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">
                    <span x-text="branchDiscountData.branch ? 'Manage Discounts - ' + branchDiscountData.branch.branch_name : 'Manage Discounts'"></span>
                </h3>
                <button @click="closeBranchDiscountModal()" type="button"
                    class="text-gray-400 hover:text-gray-500 focus:outline-none">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Discount Form -->
            <div x-data="branchDiscountState()" x-init="init()" class="space-y-6">
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
                    <input type="checkbox" id="selectAllBranchServices" x-model="selectAll"
                        @change="toggleSelectAll()"
                        class="h-4 w-4 text-[#7F5539] focus:ring-[#7F5539] border-gray-300 rounded">
                    <label for="selectAllBranchServices" class="ml-2 block text-sm font-medium text-gray-700">
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
                            <template x-if="activeTab === 'all'">
                                All Services
                            </template>
                            <template x-if="activeTab === 'discounted'">
                                Discounted Services
                            </template>
                            <template x-if="activeTab === 'not_discounted'">
                                Services Without Discount
                            </template>
                        </h4>
                        <div class="text-xs text-gray-500">
                            <span x-text="getFilteredServicesCount()"></span> service(s) shown
                        </div>
                    </div>
                    <div class="max-h-96 overflow-y-auto p-4">
                        <!-- All Services Tab -->
                        <template x-if="activeTab === 'all'">
                            <template x-for="category in branchDiscountData.categories" :key="'all-' + category.id">
                                <div class="mb-6 last:mb-0" x-show="category.service_names && category.service_names.length > 0">
                                    <!-- Category Header -->
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
                                    
                                    <!-- Services List -->
                                    <div class="space-y-2 ml-2">
                                        <template x-for="service in category.service_names" :key="service.id">
                                            <div class="flex items-center justify-between hover:bg-gray-50 p-3 rounded-lg border border-gray-100 transition-colors duration-150">
                                                <div class="flex items-center flex-1">
                                                    <input type="checkbox" :id="'branch_service_all_' + service.id"
                                                        x-model="selectedServices" :value="service.id"
                                                        class="h-4 w-4 text-[#7F5539] focus:ring-[#7F5539] border-gray-300 rounded cursor-pointer">
                                                    <label :for="'branch_service_all_' + service.id" class="ml-3 flex-1 cursor-pointer">
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
                                                            <div class="text-xs text-green-500">
                                                                You save: ₱<span x-text="formatPrice((service.old_price || service.price) - service.price)"></span>
                                                            </div>
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
                            <template x-for="category in branchDiscountData.categories" :key="'discounted-' + category.id">
                                <div class="mb-6 last:mb-0" 
                                     x-show="category.service_names && category.service_names.filter(s => s.discount).length > 0">
                                    <!-- Category Header -->
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
                                    
                                    <!-- Services List -->
                                    <div class="space-y-2 ml-2">
                                        <template x-for="service in category.service_names.filter(s => s.discount)" :key="service.id">
                                            <div class="flex items-center justify-between hover:bg-green-50 p-3 rounded-lg border border-green-100 transition-colors duration-150">
                                                <div class="flex items-center flex-1">
                                                    <input type="checkbox" :id="'branch_service_discounted_' + service.id"
                                                        x-model="selectedServices" :value="service.id"
                                                        class="h-4 w-4 text-green-600 focus:ring-green-500 border-green-300 rounded cursor-pointer">
                                                    <label :for="'branch_service_discounted_' + service.id" class="ml-3 flex-1 cursor-pointer">
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
                                                        <div class="text-xs text-green-500">
                                                            You save: ₱<span x-text="formatPrice(service.old_price - service.price)"></span>
                                                        </div>
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
                            <template x-for="category in branchDiscountData.categories" :key="'not_discounted-' + category.id">
                                <div class="mb-6 last:mb-0" 
                                     x-show="category.service_names && category.service_names.filter(s => !s.discount).length > 0">
                                    <!-- Category Header -->
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
                                    
                                    <!-- Services List -->
                                    <div class="space-y-2 ml-2">
                                        <template x-for="service in category.service_names.filter(s => !s.discount)" :key="service.id">
                                            <div class="flex items-center justify-between hover:bg-gray-50 p-3 rounded-lg border border-gray-100 transition-colors duration-150">
                                                <div class="flex items-center flex-1">
                                                    <input type="checkbox" :id="'branch_service_not_discounted_' + service.id"
                                                        x-model="selectedServices" :value="service.id"
                                                        class="h-4 w-4 text-[#7F5539] focus:ring-[#7F5539] border-gray-300 rounded cursor-pointer">
                                                    <label :for="'branch_service_not_discounted_' + service.id" class="ml-3 flex-1 cursor-pointer">
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
                        <template x-if="activeTab === 'all' && (!branchDiscountData.categories || branchDiscountData.categories.length === 0)">
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
                        <button @click="closeBranchDiscountModal()"
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
                    service_name_status: '{{ request('service_name_status', '') }}',
                    discount_status: '{{ request('discount_status', '') }}',
                },
                clearFilters() {
                    this.filters = {
                        service_name_status: '',
                        discount_status: '',
                    };
                },
                applyFilters() {
                    const mainComponent = Alpine.$data(document.querySelector(
                        '[x-data="serviceNameData()"]'));
                    const newFilters = {
                        ...mainComponent.currentFilters,
                        ...this.filters,
                        search: mainComponent.searchQuery
                    };
                    mainComponent.applyFilters(newFilters);
                    mainComponent.removeBodyClass();
                }
            }));

            // Branch discount state
            Alpine.data('branchDiscountState', () => ({
                discountType: 'percentage',
                discountValue: 0,
                selectedServices: [],
                selectAll: false,
                isLoading: false,
                activeTab: 'all', // 'all', 'discounted', 'not_discounted'

                init() {
                    Alpine.effect(() => {
                        const mainComponent = Alpine.$data(document.querySelector('[x-data="serviceNameData()"]'));
                        if (mainComponent.branchDiscountData.categories) {
                            this.selectedServices = [];
                            this.selectAll = false;
                        }
                    });
                },

                getFilteredServices() {
                    const mainComponent = Alpine.$data(document.querySelector('[x-data="serviceNameData()"]'));
                    if (!mainComponent.branchDiscountData.categories) return [];
                    
                    let services = [];
                    mainComponent.branchDiscountData.categories.forEach(category => {
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
                    const mainComponent = Alpine.$data(document.querySelector('[x-data="serviceNameData()"]'));
                    if (!mainComponent.branchDiscountData.categories) return 0;
                    
                    let total = 0;
                    mainComponent.branchDiscountData.categories.forEach(category => {
                        if (category.service_names) {
                            total += category.service_names.length;
                        }
                    });
                    return total;
                },

                getDiscountedServicesCount() {
                    const mainComponent = Alpine.$data(document.querySelector('[x-data="serviceNameData()"]'));
                    if (!mainComponent.branchDiscountData.categories) return 0;
                    
                    let count = 0;
                    mainComponent.branchDiscountData.categories.forEach(category => {
                        if (category.service_names) {
                            count += category.service_names.filter(s => s.discount).length;
                        }
                    });
                    return count;
                },

                getNotDiscountedServicesCount() {
                    const mainComponent = Alpine.$data(document.querySelector('[x-data="serviceNameData()"]'));
                    if (!mainComponent.branchDiscountData.categories) return 0;
                    
                    let count = 0;
                    mainComponent.branchDiscountData.categories.forEach(category => {
                        if (category.service_names) {
                            count += category.service_names.filter(s => !s.discount).length;
                        }
                    });
                    return count;
                },

                isCategorySelected(tab, categoryId) {
                    const mainComponent = Alpine.$data(document.querySelector('[x-data="serviceNameData()"]'));
                    const category = mainComponent.branchDiscountData.categories?.find(c => c.id === categoryId);
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
                    const mainComponent = Alpine.$data(document.querySelector('[x-data="serviceNameData()"]'));
                    const category = mainComponent.branchDiscountData.categories?.find(c => c.id === categoryId);
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
                    const mainComponent = Alpine.$data(document.querySelector('[x-data="serviceNameData()"]'));
                    this.isLoading = true;

                    try {
                        const response = await fetch(`{{ url('sub_one/service_names/discount/apply') }}/{{ $branch->uuid }}`, {
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
                            await mainComponent.loadBranchDiscountData();
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
                    const mainComponent = Alpine.$data(document.querySelector('[x-data="serviceNameData()"]'));
                    this.isLoading = true;

                    try {
                        const response = await fetch(`{{ url('sub_one/service_names/discount/remove') }}/{{ $branch->uuid }}`, {
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
                            await mainComponent.loadBranchDiscountData();
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
            Alpine.data('serviceNameData', () => ({
                // Initial state
                serviceNames: @json($serviceName->items() ?? []),
                pagination: @json($serviceName->toArray()),
                stats: @json($stats),
                currentFilters: {
                    service_name_status: '{{ request('service_name_status', '') }}',
                    discount_status: '{{ request('discount_status', '') }}',
                    search: '{{ request('search', '') }}',
                },
                searchQuery: '{{ request('search', '') }}',
                showFilters: false,
                showAddModal: false,
                showEditModal: false,
                showArchiveModal: false,
                showBranchDiscountModal: false,
                selectedService: null,
                editServiceNameData: null,
                branchDiscountData: {
                    branch: null,
                    categories: []
                },
                paginationLinks: [],
                isLoading: false,
                isSubmitting: false,

                // Add form data
                addFormData: {
                    service_name: '',
                    price: '',
                    time_duration: '',
                    space_type: ''
                },

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

                    if (this.currentFilters.service_name_status) {
                        const statusText = this.getStatusText(this.currentFilters
                            .service_name_status);
                        filters.push({
                            key: 'service_name_status',
                            label: `Status: ${statusText}`
                        });
                    }

                    if (this.currentFilters.discount_status) {
                        const discountText = this.currentFilters.discount_status === 'discounted' ? 'Discounted' : 'Not Discounted';
                        filters.push({
                            key: 'discount_status',
                            label: `Discount: ${discountText}`
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

                formatPrice(price) {
                    if (!price) return '0.00';
                    return parseFloat(price).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                },

                // Add Modal functions
                openAddModal() {
                    this.showAddModal = true;
                    this.addBodyClass();
                    this.resetAddForm();
                },

                closeAddModal() {
                    this.showAddModal = false;
                    this.removeBodyClass();
                    this.resetAddForm();
                },

                resetAddForm() {
                    this.addFormData = {
                        service_name: '',
                        price: '',
                        time_duration: '',
                        space_type: ''
                    };
                },

                async submitAddForm() {
                    if (this.isSubmitting) return;
                    
                    this.isSubmitting = true;
                    
                    try {
                        const formData = new FormData();
                        formData.append('branch_id', '{{ $branch->id }}');
                        formData.append('service_category_id', '{{ $serviceCategory->id }}');
                        formData.append('service_name', this.addFormData.service_name);
                        formData.append('price', this.addFormData.price);
                        formData.append('time_duration', this.addFormData.time_duration);
                        formData.append('space_type', this.addFormData.space_type);
                        formData.append('_token', '{{ csrf_token() }}');

                        const response = await fetch('{{ route("sub_one.service_names.storeServiceNameAjax") }}', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.showNotification('Service created successfully.', 'success');
                            this.closeAddModal();
                            await this.applyFilters(this.currentFilters);
                        } else {
                            throw new Error(data.message || 'Failed to create service');
                        }
                    } catch (error) {
                        console.error('Error creating service:', error);
                        this.showNotification(error.message || 'Failed to create service. Please try again.', 'error');
                    } finally {
                        this.isSubmitting = false;
                    }
                },

                // Edit Modal functions
                async openEditModal(service) {
                    this.selectedService = service;
                    this.editServiceNameData = null;
                    this.showEditModal = true;
                    this.addBodyClass();
                    
                    try {
                        const response = await fetch(`{{ url('sub_one/service_names') }}/{{ $branch->uuid }}/{{ $serviceCategory->uuid }}/${service.uuid}/data`, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.editServiceNameData = data.service_name;
                        } else {
                            throw new Error(data.message || 'Failed to load service data');
                        }
                    } catch (error) {
                        console.error('Error loading service data:', error);
                        this.showNotification('Failed to load service data. Please try again.', 'error');
                        this.closeEditModal();
                    }
                },

                closeEditModal() {
                    this.showEditModal = false;
                    this.editServiceNameData = null;
                    this.selectedService = null;
                    this.removeBodyClass();
                },

                async submitEditForm() {
                    if (this.isSubmitting || !this.editServiceNameData) return;
                    
                    this.isSubmitting = true;
                    
                    try {
                        const formData = new FormData();
                        formData.append('branch_id', '{{ $branch->id }}');
                        formData.append('service_category_id', '{{ $serviceCategory->id }}');
                        formData.append('service_name', this.editServiceNameData.service_name);
                        formData.append('price', this.editServiceNameData.price);
                        formData.append('time_duration', this.editServiceNameData.time_duration);
                        formData.append('space_type', this.editServiceNameData.space_type);
                        formData.append('_token', '{{ csrf_token() }}');
                        formData.append('_method', 'PATCH');

                        const response = await fetch(`{{ url('sub_one/service_names/ajax') }}/${this.editServiceNameData.uuid}/update`, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.showNotification('Service updated successfully.', 'success');
                            this.closeEditModal();
                            await this.applyFilters(this.currentFilters);
                        } else {
                            throw new Error(data.message || 'Failed to update service');
                        }
                    } catch (error) {
                        console.error('Error updating service:', error);
                        this.showNotification(error.message || 'Failed to update service. Please try again.', 'error');
                    } finally {
                        this.isSubmitting = false;
                    }
                },

                // Branch Discount Modal functions
                async openBranchDiscountModal() {
                    try {
                        this.isLoading = true;
                        await this.loadBranchDiscountData();
                        this.showBranchDiscountModal = true;
                        this.addBodyClass();
                    } catch (error) {
                        this.showNotification('Failed to load discount data', 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                async loadBranchDiscountData() {
                    try {
                        const response = await fetch(`{{ url('sub_one/service_names/discount/data') }}/{{ $branch->uuid }}`, {
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
                            this.branchDiscountData = {
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

                closeBranchDiscountModal() {
                    this.showBranchDiscountModal = false;
                    this.branchDiscountData = {
                        branch: null,
                        categories: []
                    };
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
                            this.serviceNames = data.data;
                            this.pagination = data.pagination;
                            this.stats = data.stats;
                            this.updatePaginationLinks();
                            this.updateActiveFilters();
                        } else {
                            throw new Error(data.message || 'Filter application failed');
                        }
                    } catch (error) {
                        console.error('Error applying filters:', error);
                        this.showNotification('Failed to apply filters. Please try again.', 'error');
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
                        service_name_status: '',
                        discount_status: '',
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
                            this.serviceNames = data.data;
                            this.pagination = data.pagination;
                            this.stats = data.stats;
                            this.updatePaginationLinks();
                            this.updateActiveFilters();
                        } else {
                            throw new Error(data.message || 'Filter clearing failed');
                        }
                    } catch (error) {
                        console.error('Error clearing filters:', error);
                        this.showNotification('Failed to clear filters. Please try again.', 'error');
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
                            this.serviceNames = data.data;
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

                // Archive modal methods
                openArchiveModal(service) {
                    this.selectedService = service;
                    this.showArchiveModal = true;
                    this.addBodyClass();
                },

                closeArchiveModal() {
                    this.showArchiveModal = false;
                    this.removeBodyClass();
                },

                confirmArchive() {
                    if (!this.selectedService) return;

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action =
                        `{{ url('sub_one/service_names/deactivate') }}/${this.selectedService.uuid}`;

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