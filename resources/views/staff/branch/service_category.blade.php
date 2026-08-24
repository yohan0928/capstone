@extends('layouts.app')

@section('title', 'Service Categories')

@section('content')
    <div x-data="serviceCategoryData()" x-init="init()" class="p-4">
        <!-- Header Section -->
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-bold text-[#4A2C1D]">
                {{ $branch->branch_name }}
                <span class="block text-lg font-semibold text-[#7F5539] mt-1">
                    (Service Categories)
                </span>
            </h1>
        </div>


        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-6 mb-8">
            <!-- Total Service Categories -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Categories</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.total_categories"></p>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-lg">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Available Categories -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Available</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.available_categories"></p>
                    </div>
                    <div class="p-3 bg-green-50 rounded-lg">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Unavailable Categories -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Unavailable</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.unavailable_categories"></p>
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
            <a href="{{ route('sub_two.branches.showBranch') }}"
                class="inline-flex items-center text-sm font-medium text-[#7F5539] hover:underline">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Branches
            </a>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Table Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <!-- Medium to Largest Screens Layout -->
                <div class="hidden sm:flex items-center justify-between mb-4">
                    <!-- Left: Header -->
                    <h2 class="text-lg font-semibold text-gray-900">Service Category Records</h2>

                    <!-- Right: Search + Filter + Add Button -->
                    <div class="flex items-center space-x-3">
                        <!-- Search Input -->
                        <div class="relative w-80">
                            <input type="text" x-model="searchQuery" @input.debounce.500ms="performSearch()"
                                placeholder="Search by category name..."
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
                    </div>
                </div>

                <!-- Small to Smallest Screens Layout -->
                <div class="sm:hidden space-y-4">
                    <!-- First Row: Service Category Records + Add Button -->
                    <div class="flex items-center justify-between">
                        <!-- Left: Header -->
                        <h2 class="text-lg font-semibold text-gray-900">Service Category Records</h2>
                    </div>

                    <!-- Second Row: Search + Filter -->
                    <div class="flex items-center space-x-3">
                        <!-- Search Input -->
                        <div class="relative flex-1">
                            <input type="text" x-model="searchQuery" @input.debounce.500ms="performSearch()"
                                placeholder="Search by category name..."
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
                                Image
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Category Name
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
                        <template x-for="category in categories" :key="category.uuid">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <!-- Image -->
                                <td class="px-6 py-4 whitespace-nowrap flex justify-center">
                                    <div class="flex-shrink-0 h-16 w-16">
                                        <button @click="openImageModal(category.service_img || [])"
                                            class="focus:outline-none">
                                            <img :src="category.service_img && category.service_img.length > 0 ?
                                                `/storage/app/public/${category.service_img[0]}` :
                                                `https://ui-avatars.com/api/?name=${encodeURIComponent(category.service_category)}&background=7F5539&color=FFFFFF`"
                                                :alt="category.service_category"
                                                class="h-16 w-16 rounded-lg object-cover border border-gray-200 hover:opacity-80 transition-opacity">
                                        </button>
                                    </div>
                                </td>

                                <!-- Category Name -->
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 text-center"
                                        x-text="category.service_category">
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex justify-center items-center">
                                        <div x-data="{ open: false }" class="relative">
                                            <button @click.prevent="open = !open" @click.away="open = false"
                                                class="flex items-center space-x-1 text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap cursor-pointer"
                                                :class="getStatusClasses(category.service_category_status)">
                                                <span x-text="getStatusText(category.service_category_status)"></span>
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
                                                <form :id="'update-category-status-' + category.uuid + '-1'"
                                                    :action="'{{ url('sub_two/service_categories/status') }}/' + category.uuid"
                                                    method="POST" class="hidden">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="service_category_status" value="1">
                                                </form>
                                                <a href="#"
                                                    @click.prevent="document.getElementById('update-category-status-' + category.uuid + '-1').submit(); open = false;"
                                                    class="block px-4 py-2 text-sm hover:bg-gray-100"
                                                    :class="category.service_category_status === 1 ?
                                                        'bg-green-50 text-green-700 font-medium' : 'text-gray-700'">
                                                    Available
                                                </a>

                                                <!-- Unavailable Option -->
                                                <form :id="'update-category-status-' + category.uuid + '-0'"
                                                    :action="'{{ url('sub_two/service_categories/status') }}/' + category.uuid"
                                                    method="POST" class="hidden">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="service_category_status" value="0">
                                                </form>
                                                <a href="#"
                                                    @click.prevent="document.getElementById('update-category-status-' + category.uuid + '-0').submit(); open = false;"
                                                    class="block px-4 py-2 text-sm hover:bg-gray-100"
                                                    :class="category.service_category_status === 0 ?
                                                        'bg-red-50 text-red-700 font-medium' : 'text-gray-700'">
                                                    Unavailable
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center justify-center space-x-4">
                                        <!-- Services Button -->
                                        <div class="relative group">
                                            <a :href="'{{ url('sub_two/service_names') }}/' + '{{ $branch->uuid }}' + '/' +
                                            category.uuid"
                                                class="text-blue-600 hover:text-blue-800 transition-colors p-2 rounded-full hover:bg-gray-100 flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                                                </svg>
                                            </a>
                                            <!-- Service Names Label -->
                                            <span
                                                class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs font-medium px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none">
                                                View Service Names
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <tr x-show="!categories.length">
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" />
                                        </path>
                                    </svg>
                                    <h5 class="text-sm font-medium text-gray-900"
                                        x-text="hasActiveFilters ? 'No categories match your filters' : 'No categories found'">
                                    </h5>
                                    <p class="text-sm text-gray-500"
                                        x-text="hasActiveFilters ? 'Try adjusting your filters.' : 'Add your first category to get started.'">
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
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Filter Categories</h3>

                        <div x-data="filterState()">
                            <!-- Filter Inputs -->
                            <div class="space-y-4">
                                <!-- Status Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select x-model="filters.service_category_status"
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

        <!-- Image Modal -->
        <div x-data="imageModal()" x-show="showImageModal" @click.self="closeImageModal"
            @keydown.escape.window="closeImageModal" x-cloak
            class="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-[9999] p-4">

            <!-- Image Display Container -->
            <div class="flex items-center justify-center w-full h-full">
                <template x-if="images.length > 0">
                    <img :src="`/storage/app/public/${images[currentIndex]}`"
                        class="max-h-[90vh] max-w-[90vw] object-contain rounded-lg shadow-lg">
                </template>
                <template x-if="images.length === 0">
                    <div class="text-white text-lg">No images available</div>
                </template>
            </div>

            <!-- Previous Button -->
            <button @click="prevImage" x-show="images.length > 1"
                class="absolute left-4 top-1/2 -translate-y-1/2 p-2 bg-white/80 backdrop-blur-sm rounded-full hover:bg-white transition-colors z-[9999]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-black" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <!-- Next Button -->
            <button @click="nextImage" x-show="images.length > 1"
                class="absolute right-4 top-1/2 -translate-y-1/2 p-2 bg-white/80 backdrop-blur-sm rounded-full hover:bg-white transition-colors z-[9999]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-black" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <!-- Close Button -->
            <button @click="closeImageModal"
                class="absolute top-4 right-4 p-2 bg-white/80 backdrop-blur-sm rounded-full hover:bg-white transition-colors z-[9999]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-black" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Image Counter -->
            <div x-show="images.length > 1"
                class="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-black/50 text-white px-3 py-1 rounded-full text-sm">
                <span x-text="currentIndex + 1"></span> / <span x-text="images.length"></span>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            // Filter state for the sidebar
            Alpine.data('filterState', () => ({
                filters: {
                    service_category_status: '{{ request('service_category_status', '') }}',
                },
                clearFilters() {
                    this.filters = {
                        service_category_status: '',
                    };
                },
                applyFilters() {
                    const mainComponent = Alpine.$data(document.querySelector(
                        '[x-data="serviceCategoryData()"]'));
                    const newFilters = {
                        ...mainComponent.currentFilters,
                        ...this.filters,
                        search: mainComponent.searchQuery
                    };
                    mainComponent.applyFilters(newFilters);
                    mainComponent.removeBodyClass();
                }
            }));

            // Image Modal Component
            Alpine.data('imageModal', () => ({
                showImageModal: false,
                images: [],
                currentIndex: 0,

                openImageModal(imageList) {
                    if (!imageList || imageList.length === 0) return;
                    this.images = imageList;
                    this.currentIndex = 0;
                    this.showImageModal = true;
                    document.body.style.overflow = 'hidden';
                },

                closeImageModal() {
                    this.showImageModal = false;
                    document.body.style.overflow = 'auto';
                },

                nextImage() {
                    this.currentIndex = (this.currentIndex + 1) % this.images.length;
                },

                prevImage() {
                    this.currentIndex = (this.currentIndex === 0) ? this.images.length - 1 : this
                        .currentIndex - 1;
                },

                init() {
                    document.addEventListener('keydown', (e) => {
                        if (!this.showImageModal) return;
                        switch (e.key) {
                            case 'ArrowLeft':
                                this.prevImage();
                                break;
                            case 'ArrowRight':
                                this.nextImage();
                                break;
                            case 'Escape':
                                this.closeImageModal();
                                break;
                        }
                    });
                }
            }));

            // Main component
            Alpine.data('serviceCategoryData', () => ({
                // Initial state
                categories: @json($serviceCategory->items() ?? []),
                pagination: @json($serviceCategory->toArray()),
                stats: @json($stats),
                currentFilters: {
                    service_category_status: '{{ request('service_category_status', '') }}',
                    search: '{{ request('search', '') }}',
                },
                searchQuery: '{{ request('search', '') }}',
                showFilters: false,
                showArchiveModal: false,
                selectedCategory: null,
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

                    if (this.currentFilters.service_category_status) {
                        const statusText = this.getStatusText(this.currentFilters
                            .service_category_status);
                        filters.push({
                            key: 'service_category_status',
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

                // Image Modal function
                openImageModal(images) {
                    const imageModal = Alpine.$data(document.querySelector('[x-data="imageModal()"]'));
                    imageModal.openImageModal(images);
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
                            this.categories = data.data;
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
                        service_category_status: '',
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
                            this.categories = data.data;
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
                            this.categories = data.data;
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
    </style>
@endsection
