@extends('layouts.app')

@section('title', 'Archived Service Categories')

@section('content')
    <div x-data="archivedServiceCategoryData()" x-init="init()" class="p-4">
        <!-- Header Section -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8">
            <!-- Title -->
            <div class="text-center lg:text-left mb-4 lg:mb-0">
                <h1 class="text-2xl font-bold text-[#4A2C1D]">
                    {{ $branch->branch_name }}
                    <span class="block lg:inline text-lg font-semibold text-[#7F5539] lg:ml-2">
                        (Archived Service Categories)
                    </span>
                </h1>
                <p class="text-sm text-gray-500 mt-1">Archived categories will be permanently deleted after 30 days</p>
            </div>

            <!-- Back Link -->
            <div class="lg:text-left text-right">
                <a href="{{ route('sub_one.service_categories.showServiceCategory', $branch->uuid) }}"
                    class="text-sm font-medium text-[#7F5539] hover:underline">
                    Back
                </a>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Total Archived -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Archived</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.total_archived || 0"></p>
                    </div>
                    <div class="p-3 bg-yellow-50 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Days Left -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Avg. Days Left</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.avg_days_left || 0"></p>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-lg">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Table Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <!-- Medium to Largest Screens Layout -->
                <div class="hidden sm:flex items-center justify-between mb-4">
                    <!-- Left: Header -->
                    <h2 class="text-lg font-semibold text-gray-900">Archived Service Categories</h2>

                    <!-- Right: Search -->
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
                    </div>
                </div>

                <!-- Small to Smallest Screens Layout -->
                <div class="sm:hidden space-y-4">
                    <!-- First Row: Header -->
                    <div class="flex items-center justify-between">
                        <!-- Left: Header -->
                        <h2 class="text-lg font-semibold text-gray-900">Archived Service Categories</h2>
                    </div>

                    <!-- Second Row: Search -->
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
                                Archived Status
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Archived Date
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Days Left
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

                                <!-- Archived Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex justify-center items-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="getArchivedStatusClasses(category.service_category_status)">
                                            <span x-text="getArchivedStatusText(category.service_category_status)"></span>
                                        </span>
                                    </div>
                                </td>

                                <!-- Archived Date -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 text-center" x-text="formatDate(category.date_updated)"></div>
                                </td>

                                <!-- Days Left -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex justify-center items-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="getDaysLeftClass(category.days_left)">
                                            <span x-text="category.days_left !== undefined ? category.days_left + ' days' : 'Calculating...'"></span>
                                        </span>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center justify-center space-x-4">
                                        <!-- Reactivate Button -->
                                        <div class="relative group">
                                            <form :id="'reactivate-category-' + category.uuid"
                                                :action="'{{ url('sub_one/service_categories/reactivate') }}/' + category.uuid" method="POST"
                                                class="hidden">
                                                @csrf
                                                @method('PATCH')
                                            </form>
                                            <button @click="confirmReactivate(category)"
                                                class="text-green-600 hover:text-green-800 transition-colors p-2 rounded-full hover:bg-gray-100 flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                                </svg>
                                            </button>
                                            <!-- Reactivate Label -->
                                            <span
                                                class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs font-medium px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none">
                                                Reactivate
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <tr x-show="!categories.length">
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <h5 class="text-sm font-medium text-gray-900"
                                        x-text="currentFilters.search ? 'No archived categories found' : (hasActiveFilters ? 'No archived categories match your filters' : 'No archived categories found')">
                                    </h5>
                                    <p class="text-sm text-gray-500"
                                        x-text="currentFilters.search ? 'Try adjusting your search terms.' : (hasActiveFilters ? 'Try adjusting your filters.' : 'All archived categories will appear here.')">
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

        <!-- Reactivate Confirmation Modal -->
        <!-- Updated positioning: items-start, pt-20 -->
        <div x-show="showReactivateModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[9999] overflow-y-auto" style="display: none;">
            <div class="flex items-start justify-center min-h-screen px-4 pt-20 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-show="showReactivateModal"
                    @click="showReactivateModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showReactivateModal" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-top sm:mt-20 sm:max-w-md sm:w-full sm:p-6">
                    <div>
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Confirm Reactivation</h3>
                            <div class="mt-4">
                                <p class="text-sm text-gray-500 mb-4">
                                    Reactivate <strong class="text-[#4A2C1D]"
                                        x-text="selectedCategory?.service_category"></strong> category?
                                </p>
                                <p class="text-xs text-gray-400">This will make the category available again.</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 flex space-x-3">
                        <button type="button" @click="showReactivateModal = false"
                            class="flex-1 inline-flex justify-center px-4 py-2 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            Cancel
                        </button>
                        <button type="button" @click="confirmReactivateAction()"
                            class="flex-1 inline-flex justify-center px-4 py-2 border border-transparent text-base font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                            Confirm
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Modal -->
        <div x-data="imageModal()" x-show="showImageModal" @click.self="closeImageModal"
            @keydown.escape.window="closeImageModal" x-cloak
            class="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50 p-4">

            <!-- Image Display Container -->
            <div class="flex items-center justify-center w-full h-full">
                <template x-if="images.length > 0">
                    <img :src="`/storage/${images[currentIndex]}`"
                        class="max-h-[90vh] max-w-[90vw] object-contain rounded-lg shadow-lg">
                </template>
                <template x-if="images.length === 0">
                    <div class="text-white text-lg">No images available</div>
                </template>
            </div>

            <!-- Previous Button -->
            <button @click="prevImage" x-show="images.length > 1"
                class="absolute left-4 top-1/2 -translate-y-1/2 p-2 bg-white/80 backdrop-blur-sm rounded-full hover:bg-white transition-colors z-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-black" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <!-- Next Button -->
            <button @click="nextImage" x-show="images.length > 1"
                class="absolute right-4 top-1/2 -translate-y-1/2 p-2 bg-white/80 backdrop-blur-sm rounded-full hover:bg-white transition-colors z-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-black" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <!-- Close Button -->
            <button @click="closeImageModal"
                class="absolute top-4 right-4 p-2 bg-white/80 backdrop-blur-sm rounded-full hover:bg-white transition-colors z-50">
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
            Alpine.data('archivedServiceCategoryData', () => ({
                // Initial state
                categories: @json($serviceCategories->items() ?? []),
                pagination: @json($serviceCategories->toArray()),
                stats: @json($stats ?? []),
                currentFilters: {
                    service_category_status: '{{ request('service_category_status', '') }}',
                    days_left: '{{ request('days_left', '') }}',
                    search: '{{ request('search', '') }}',
                },
                searchQuery: '{{ request('search', '') }}',
                showReactivateModal: false,
                selectedCategory: null,
                paginationLinks: [],
                isLoading: false,

                init() {
                    this.updatePaginationLinks();
                    this.updateActiveFilters();
                    this.calculateDaysLeft(); // Calculate on init
                },

                // New helper method to calculate days left
                calculateDaysLeft() {
                    this.categories.forEach(category => {
                        const archiveDate = category.date_updated;
                        if (archiveDate) {
                            try {
                                const archivedDate = new Date(archiveDate);
                                const today = new Date();
                                const daysSinceArchived = Math.floor((today - archivedDate) / (1000 * 60 * 60 * 24));
                                category.days_left = Math.max(0, 30 - daysSinceArchived);
                            } catch (error) {
                                console.error('Error calculating days left for category:', category.id, error);
                                category.days_left = 30; // Default fallback
                            }
                        } else {
                            category.days_left = 30; // Default fallback
                        }
                    });
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
                        const statusText = this.getArchivedStatusText(this.currentFilters
                            .service_category_status);
                        filters.push({
                            key: 'service_category_status',
                            label: `Status: ${statusText}`
                        });
                    }

                    if (this.currentFilters.days_left) {
                        const daysText = {
                            'critical': 'Critical (1-7 days)',
                            'warning': 'Warning (8-15 days)',
                            'normal': 'Normal (16-30 days)'
                        }[this.currentFilters.days_left] || this.currentFilters.days_left;
                        filters.push({
                            key: 'days_left',
                            label: `Days: ${daysText}`
                        });
                    }

                    return filters;
                },

                // Utility functions
                getArchivedStatusClasses(status) {
                    const statusClasses = {
                        0: 'bg-red-200 text-red-800', // Unavailable when archived
                        1: 'bg-green-200 text-green-800', // Available when archived
                    };
                    return statusClasses[status] || 'bg-gray-200 text-gray-800';
                },

                getArchivedStatusText(status) {
                    const statusText = {
                        0: 'Unavailable',
                        1: 'Available',
                    };
                    return statusText[status] || 'Unknown';
                },

                getDaysLeftClass(daysLeft) {
                    if (daysLeft <= 7) return 'bg-red-100 text-red-800';
                    if (daysLeft <= 15) return 'bg-yellow-100 text-yellow-800';
                    return 'bg-green-100 text-green-800';
                },

                formatDate(dateString) {
                    if (!dateString) return 'N/A';
                    const date = new Date(dateString);
                    return date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
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
                    this.currentFilters = { ...filters };

                    try {
                        const queryParams = new URLSearchParams();
                        Object.entries(this.currentFilters).forEach(([key, value]) => {
                            if (value) queryParams.append(key, value);
                        });

                        const url = `?${queryParams.toString()}&ajax=true`;
                        const response = await fetch(url, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

                        const data = await response.json();
                        if (data.success) {
                            this.categories = data.data;
                            this.pagination = data.pagination;
                            this.stats = data.stats;
                            this.calculateDaysLeft(); // RECALCULATE for new search results
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
                    this.searchQuery = '';
                    this.currentFilters = { service_category_status: '', days_left: '', search: '' };

                    try {
                        const url = `?ajax=true`;
                        const response = await fetch(url, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                        const data = await response.json();

                        if (data.success) {
                            this.categories = data.data;
                            this.pagination = data.pagination;
                            this.stats = data.stats;
                            this.calculateDaysLeft(); // RECALCULATE for cleared filters
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
                        if (value) queryParams.append(key, value);
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
                            if (value) queryParams.append(key, value);
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

                        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                        const data = await response.json();

                        if (data.success) {
                            this.categories = data.data;
                            this.pagination = data.pagination;
                            this.stats = data.stats;
                            this.calculateDaysLeft(); // RECALCULATE for new page
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

                // Reactivate modal methods
                confirmReactivate(category) {
                    this.selectedCategory = category;
                    this.showReactivateModal = true;
                    this.addBodyClass();
                },

                confirmReactivateAction() {
                    if (!this.selectedCategory) return;
                    const form = document.getElementById(`reactivate-category-${this.selectedCategory.uuid}`);
                    if (form) form.submit();
                },

                showNotification(message, type = 'success') {
                    // Integrate with your toast notification system
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
    </script>

    <style>
        .modal-open {
            overflow: hidden;
        }
    </style>
@endsection