@extends('layouts.app')

@section('title', 'Products')

@section('content')
    <div x-data="productData()" x-init="init()" class="p-4">
        <!-- Header Section -->
        <div class="flex items-center justify-between mb-8">
            <div class="flex-1"></div>
            <h1 class="text-2xl font-bold text-gray-900 text-center">Products</h1>
            <div class="flex-1 text-right">
                <a href="{{ route('sub_one.products.showDeactivatedProduct') }}"
                   class="text-sm font-medium text-[#7F5539] hover:underline">
                   View Archives
                </a>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
            <!-- Total Products -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Products</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.total_products"></p>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-lg">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Available Products -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Available</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.available_products"></p>
                    </div>
                    <div class="p-3 bg-green-50 rounded-lg">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Unavailable Products -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Unavailable</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.unavailable_products"></p>
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

        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Table Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <!-- Medium+ Screens -->
                <div class="hidden sm:block">
                    <div class="lg:flex lg:items-center lg:justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 lg:mb-0">Product Records</h2>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <div class="relative flex-1">
                                <input type="text" x-model="searchQuery" @input.debounce.500ms="performSearch()"
                                    placeholder="Search by product name or type..."
                                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] w-full">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <button @click="showFilters = true; addBodyClass()"
                                    class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#7F5539] flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                                    </svg>
                                    Filters
                                </button>
                                <button @click="openAddModal()"
                                    class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539] flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="1.5" class="w-5 h-5 mr-2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    Add
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Small Screens -->
                <div class="sm:hidden space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">Product Records</h2>
                        <button @click="openAddModal()"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.5" class="w-5 h-5 mr-2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            Add
                        </button>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="relative flex-1">
                            <input type="text" x-model="searchQuery" @input.debounce.500ms="performSearch()"
                                placeholder="Search by product name or type..."
                                class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] w-full">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>
                        <button @click="showFilters = true; addBodyClass()"
                            class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Active Filter Badges -->
                <div x-show="hasActiveFilters" class="flex items-center justify-end space-x-2 mt-4">
                    <template x-for="filter in activeFilters" :key="filter.key">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-[#4A2C1D]/10 text-[#7F5539]">
                            <span x-text="filter.label"></span>
                            <button @click="removeFilter(filter.key)" class="ml-1 hover:text-[#4A2C1D]">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 z-30 bg-gray-50 shadow-right">
                                Product Details
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Threshold</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(product, index) in products" :key="product.uuid">
                            <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-100'">
                                <!-- Image -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex-shrink-0 h-16 w-16">
                                        <img :src="product.product_img ?
                                            `/storage/app/public/${product.product_img}` :
                                            `https://ui-avatars.com/api/?name=${encodeURIComponent(product.product_name)}&background=7F5539&color=FFFFFF`"
                                            :alt="product.product_name"
                                            class="h-16 w-16 rounded-lg object-cover border border-gray-200">
                                    </div>
                                </td>

                                <!-- Product Details -->
                                <td class="px-6 py-4 whitespace-nowrap sticky left-0 z-20 shadow-right"
                                    :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-100'">
                                    <div class="text-sm font-medium text-gray-900" x-text="product.product_name"></div>
                                    <div class="text-sm text-gray-500" x-text="product.product_type"></div>
                                    <div class="text-xs text-gray-400 mt-1" x-text="'Unit: ' + product.unit"></div>
                                </td>

                                <!-- Branch -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" x-text="product.branch?.branch_name || 'N/A'"></div>
                                </td>

                                <!-- Price -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <span x-text="'₱' + formatNumber(product.selling_price)"></span>
                                    </div>
                                    <div class="text-sm text-gray-500" x-text="'per ' + product.unit"></div>
                                </td>

                                <!-- Threshold — read-only, stock levels tracked via inventory transactions -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-center">
                                        <template x-if="product.quantity_threshold !== null && product.quantity_threshold !== undefined">
                                            <div class="text-sm font-medium text-gray-900">
                                                <span x-text="formatNumber(product.quantity_threshold) + ' ' + product.unit"></span>
                                            </div>
                                        </template>
                                        <template x-if="product.quantity_threshold === null || product.quantity_threshold === undefined">
                                            <span class="text-xs text-gray-400 italic">None</span>
                                        </template>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div x-data="{ open: false }" class="relative">
                                        <button @click.prevent="open = !open" @click.away="open = false"
                                            class="flex items-center space-x-1 text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap cursor-pointer"
                                            :class="product.product_status ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800'">
                                            <span x-text="product.product_status ? 'Available' : 'Unavailable'"></span>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor"
                                                class="w-3 h-3 transition-transform duration-200"
                                                :class="{ 'rotate-180': open }">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                            </svg>
                                        </button>
                                        <div x-show="open"
                                            class="absolute left-0 mt-2 w-40 bg-white rounded-md shadow-lg z-10 border border-gray-200"
                                            style="display:none;">
                                            <form :id="'update-product-status-' + product.uuid + '-available'"
                                                :action="'{{ url('sub_one/products/status') }}/' + product.uuid"
                                                method="POST" class="hidden">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="product_status" value="1">
                                            </form>
                                            <a href="#"
                                                @click.prevent="document.getElementById('update-product-status-' + product.uuid + '-available').submit(); open = false;"
                                                class="block px-4 py-2 text-sm hover:bg-gray-100"
                                                :class="product.product_status ? 'bg-green-50 text-green-700 font-medium' : 'text-gray-700'">
                                                Available
                                            </a>
                                            <form :id="'update-product-status-' + product.uuid + '-unavailable'"
                                                :action="'{{ url('sub_one/products/status') }}/' + product.uuid"
                                                method="POST" class="hidden">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="product_status" value="0">
                                            </form>
                                            <a href="#"
                                                @click.prevent="document.getElementById('update-product-status-' + product.uuid + '-unavailable').submit(); open = false;"
                                                class="block px-4 py-2 text-sm hover:bg-gray-100"
                                                :class="!product.product_status ? 'bg-red-50 text-red-700 font-medium' : 'text-gray-700'">
                                                Unavailable
                                            </a>
                                        </div>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <!-- View Ingredients -->
                                        <div class="relative group">
                                            <a :href="'{{ url('sub_one/product_ingredients') }}/' + product.uuid"
                                                class="text-blue-600 hover:text-blue-800 transition-colors p-2 rounded-full hover:bg-gray-100 flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                                                </svg>
                                            </a>
                                            <span class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs font-medium px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none">
                                                View Ingredients
                                            </span>
                                        </div>

                                        <!-- Edit -->
                                        <div class="relative group">
                                            <button @click="openEditModal(product)"
                                                class="text-[#4A2C1D] hover:text-[#7F5539] transition-colors p-2 rounded-full hover:bg-gray-100 flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                </svg>
                                            </button>
                                            <span class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs font-medium px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none">
                                                Edit Product
                                            </span>
                                        </div>

                                        <!-- Archive -->
                                        <div class="relative group">
                                            <button @click="openArchiveModal(product)"
                                                class="text-red-600 hover:text-red-800 transition-colors p-2 rounded-full hover:bg-gray-100 flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                            <span class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs font-medium px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none">
                                                Archive Product
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <tr x-show="!products.length">
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    <h5 class="text-sm font-medium text-gray-900"
                                        x-text="hasActiveFilters ? 'No products match your filters' : 'No products found'"></h5>
                                    <p class="text-sm text-gray-500"
                                        x-text="hasActiveFilters ? 'Try adjusting your filters.' : 'Add your first product to get started.'"></p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div x-show="pagination && pagination.last_page > 1" class="px-4 sm:px-6 py-4 border-t border-gray-200">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="text-sm text-gray-700 text-center sm:text-left">
                        Showing <span x-text="pagination.from || 0"></span> to <span x-text="pagination.to || 0"></span>
                        of <span x-text="pagination.total || 0"></span> entries
                    </div>
                    <div class="flex flex-wrap justify-center items-center gap-2">
                        <button @click="changePage(pagination.current_page - 1)"
                                :disabled="pagination.current_page === 1"
                                class="px-3 py-2 sm:py-1 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span class="hidden sm:inline">Previous</span>
                            <span class="sm:hidden">←</span>
                        </button>
                        <template x-for="page in paginationLinks" :key="page">
                            <button @click="changePage(page)"
                                    class="px-3 py-2 sm:py-1 border rounded-lg text-sm font-medium transition-colors duration-200"
                                    :class="page === pagination.current_page ?
                                        'border-2 border-[#4A2C1D] bg-[#7F5539]/80 text-white' :
                                        'border-gray-300 text-gray-700 hover:bg-gray-50'"
                                    :disabled="page === '...'"
                                    x-text="page"></button>
                        </template>
                        <button @click="changePage(pagination.current_page + 1)"
                                :disabled="pagination.current_page === pagination.last_page"
                                class="px-3 py-2 sm:py-1 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span class="hidden sm:inline">Next</span>
                            <span class="sm:hidden">→</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== FILTER MODAL ===== -->
        <div x-show="showFilters" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeFilterModal()"></div>
                <div class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl sm:my-8 sm:align-middle max-w-md w-full sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Filter Products</h3>
                    <div x-data="filterState()">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                                <select x-model="filters.branch_id"
                                    class="block w-full border border-gray-300 rounded-lg px-3 py-2">
                                    <option value="">All Branches</option>
                                    <template x-for="branch in availableBranches" :key="branch.id">
                                        <option :value="branch.id" x-text="branch.branch_name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Product Type</label>
                                <select x-model="filters.product_type"
                                    class="block w-full border border-gray-300 rounded-lg px-3 py-2">
                                    <option value="">All Types</option>
                                    <template x-for="type in availableTypes" :key="type">
                                        <option :value="type" x-text="type"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select x-model="filters.product_status"
                                    class="block w-full border border-gray-300 rounded-lg px-3 py-2">
                                    <option value="">All Status</option>
                                    <option value="1">Available</option>
                                    <option value="0">Unavailable</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-6 flex space-x-3">
                            <button @click="clearFilters()"
                                class="flex-1 inline-flex justify-center px-4 py-2 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50">
                                Clear
                            </button>
                            <button @click="applyFilters()"
                                class="flex-1 inline-flex justify-center px-4 py-2 border border-transparent text-base font-medium rounded-lg text-white bg-[#7F5539] hover:bg-[#4A2C1D]">
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== ADD PRODUCT MODAL ===== -->
        <div x-show="showAddModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[9999] overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeAddModal()"></div>
                <div class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle w-full max-w-5xl">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">New Product</h3>
                        <button @click="closeAddModal()" type="button" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form id="addProductForm" @submit.prevent="submitAddForm" enctype="multipart/form-data">
                        @csrf
                        <div class="flex flex-col sm:flex-row gap-6" style="max-height: 70vh;">

                            {{-- LEFT: Image --}}
                            <div class="flex flex-col sm:w-80 w-full flex-shrink-0">
                                <label class="block text-sm font-medium text-[#4A2C1D] mb-2">Image</label>
                                <img id="addProductPreview"
                                    src="https://ui-avatars.com/api/?name=product&background=7F5539&color=FFFFFF"
                                    alt="Product Preview"
                                    class="w-full rounded-lg object-cover border-2 border-[#7F5539] [aspect-ratio:1/1] sm:[aspect-ratio:3/3.8]">
                                <input id="addProductImg" type="file" name="product_img"
                                    @change="handleAddImagePreview"
                                    class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-2 text-sm"
                                    accept="image/jpeg,image/png">
                                <p class="text-xs text-gray-500 mt-1">JPEG or PNG only.</p>
                            </div>

                            {{-- RIGHT: Fields --}}
                            <div class="flex-1 overflow-y-auto pr-2 space-y-4">

                                {{-- Branch --}}
                                <div x-data="{
                                    open: false,
                                    selectedBranchId: '',
                                    get selectedBranchName() {
                                        if (!this.selectedBranchId) return 'Select a branch';
                                        const branches = {{ Js::from($branches->map->only(['id', 'branch_name'])) }};
                                        const branch = branches.find(b => b.id == this.selectedBranchId);
                                        return branch ? branch.branch_name : 'Select a branch';
                                    },
                                    selectBranch(id) { this.selectedBranchId = id; this.open = false; }
                                }" class="relative">
                                    <label class="block text-sm font-medium text-[#4A2C1D]">Assign Branch</label>
                                    <input type="hidden" name="branch_id" x-model="selectedBranchId">
                                    <button @click="open=!open" @click.away="open=false" type="button"
                                        class="w-full flex justify-between items-center border-2 border-[#7F5539] rounded px-3 py-2 mt-1 text-left bg-white">
                                        <span x-text="selectedBranchName" :class="{ 'text-gray-500': !selectedBranchId }"></span>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500"
                                            :class="{ 'rotate-180': open }">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>
                                    <div x-show="open" x-transition
                                        class="absolute mt-1 w-full bg-white rounded-md shadow-lg z-20 border border-gray-200 max-h-60 overflow-y-auto"
                                        style="display:none;">
                                        @forelse($branches as $branch)
                                            <a href="#" @click.prevent="selectBranch({{ $branch->id }})"
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ $branch->branch_name }}</a>
                                        @empty
                                            <span class="block px-4 py-2 text-sm text-gray-500">No branches available</span>
                                        @endforelse
                                    </div>
                                </div>

                                {{-- Product Category --}}
                                <div x-data="{
                                    open: false,
                                    selectedCategory: '',
                                    categories: [
                                        { value: 'rtd', label: 'RTD / Packaged' },
                                        { value: 'mto', label: 'Made-to-Order' },
                                    ],
                                    get selectedLabel() {
                                        if (!this.selectedCategory) return 'Select a category';
                                        const cat = this.categories.find(c => c.value === this.selectedCategory);
                                        return cat ? cat.label : 'Select a category';
                                    },
                                    selectCategory(value) { this.selectedCategory = value; this.open = false; }
                                }" class="relative">
                                    <label class="block text-sm font-medium text-[#4A2C1D]">Category</label>
                                    <input type="hidden" name="product_category" x-model="selectedCategory">
                                    <button @click="open=!open" @click.away="open=false" type="button"
                                        class="w-full flex justify-between items-center border-2 border-[#7F5539] rounded px-3 py-2 mt-1 bg-white text-left">
                                        <span x-text="selectedLabel" :class="{ 'text-gray-500': !selectedCategory }"></span>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500"
                                            :class="{ 'rotate-180': open }">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>
                                    <div x-show="open" x-transition
                                        class="absolute mt-1 w-full bg-white rounded-md shadow-lg z-20 border border-gray-200"
                                        style="display:none;">
                                        <template x-for="cat in categories" :key="cat.value">
                                            <a href="#" @click.prevent="selectCategory(cat.value)"
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                x-text="cat.label"></a>
                                        </template>
                                    </div>
                                </div>

                                {{-- Product Type --}}
                                <div>
                                    <label class="block text-sm font-medium text-[#4A2C1D]">Type</label>
                                    <input type="text" name="product_type" x-model="addFormData.product_type"
                                        class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1"
                                        placeholder="e.g., Coffee, Biscuit, Water">
                                </div>

                                {{-- Product Name --}}
                                <div>
                                    <label class="block text-sm font-medium text-[#4A2C1D]">Name</label>
                                    <input type="text" name="product_name" x-model="addFormData.product_name" required
                                        class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1">
                                </div>

                                {{-- Unit & Threshold --}}
                                <div class="grid grid-cols-2 gap-4">
                                    <div x-data="{
                                        open: false,
                                        selectedUnit: '',
                                        units: ['g', 'kg', 'mg', 'lb', 'oz', 'ml', 'l', 'cup', 'tbsp', 'tsp', 'pcs', 'pack'],
                                        get selectedUnitName() { return this.selectedUnit || 'Select a unit'; },
                                        selectUnit(u) { this.selectedUnit = u; this.open = false; }
                                    }" class="relative">
                                        <label class="block text-sm font-medium text-[#4A2C1D]">Unit</label>
                                        <input type="hidden" name="unit" x-model="selectedUnit">
                                        <button @click="open=!open" @click.away="open=false" type="button"
                                            class="w-full flex justify-between items-center border-2 border-[#7F5539] rounded px-3 py-2 mt-1 bg-white text-left">
                                            <span x-text="selectedUnitName" :class="{ 'text-gray-500': !selectedUnit }"></span>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500"
                                                :class="{ 'rotate-180': open }">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                            </svg>
                                        </button>
                                        <div x-show="open" x-transition
                                            class="absolute mt-1 w-full bg-white rounded-md shadow-lg z-20 border border-gray-200 max-h-60 overflow-y-auto"
                                            style="display:none;">
                                            <template x-for="u in units" :key="u">
                                                <a href="#" @click.prevent="selectUnit(u)"
                                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" x-text="u"></a>
                                            </template>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-[#4A2C1D]">Quantity Threshold</label>
                                        <input type="number" name="quantity_threshold" step="0.01" min="0"
                                            x-model="addFormData.quantity_threshold"
                                            class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1"
                                            placeholder="Low stock alert level">
                                    </div>
                                </div>

                                {{-- Selling Price --}}
                                <div>
                                    <label class="block text-sm font-medium text-[#4A2C1D]">Selling Price (₱)</label>
                                    <input type="number" name="selling_price" step="0.01" min="0"
                                        x-model="addFormData.selling_price"
                                        class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1"
                                        placeholder="Enter selling price">
                                </div>

                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3 border-t pt-4">
                            <button type="button" @click="closeAddModal()"
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" :disabled="isSubmitting"
                                class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-[#7F5539] hover:bg-[#4A2C1D] disabled:opacity-50">
                                <span x-text="isSubmitting ? 'Adding...' : 'Add'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ===== EDIT PRODUCT MODAL ===== -->
        <div x-show="showEditModal" x-cloak x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 z-[9999] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeEditModal()"></div>
                <div class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle w-full max-w-5xl">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Edit Product</h3>
                        <button @click="closeEditModal()" type="button" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <template x-if="editProductData">
                        <form id="editProductForm" @submit.prevent="submitEditForm" enctype="multipart/form-data">
                            @csrf
                            @method('PATCH')
                            <div class="flex flex-col sm:flex-row gap-6" style="max-height: 70vh;">

                                {{-- LEFT: Image --}}
                                <div class="flex flex-col sm:w-80 w-full flex-shrink-0">
                                    <label class="block text-sm font-medium text-[#4A2C1D] mb-2">Image</label>
                                    <img id="editProductPreview"
                                        :src="editProductData?.product_img ?
                                            `/storage/app/public/${editProductData.product_img}` :
                                            `https://ui-avatars.com/api/?name=${encodeURIComponent(editProductData?.product_name || 'product')}&background=7F5539&color=FFFFFF`"
                                        alt="Product Preview"
                                        class="w-full rounded-lg object-cover border-2 border-[#7F5539] [aspect-ratio:1/1] sm:[aspect-ratio:3/3.8]">
                                    <input id="editProductImg" type="file" name="product_img"
                                        @change="handleEditImagePreview"
                                        class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-2 text-sm"
                                        accept="image/jpeg,image/png">
                                    <p class="text-xs text-gray-500 mt-1">JPEG or PNG only.</p>
                                </div>

                                {{-- RIGHT: Fields --}}
                                <div class="flex-1 overflow-y-auto pr-2 space-y-4">

                                    {{-- Branch --}}
                                    <div x-data="{
                                        open: false,
                                        selectedBranchId: editProductData?.branch_id || '',
                                        get selectedBranchName() {
                                            if (!this.selectedBranchId) return 'Select a branch';
                                            const branches = {{ Js::from($branches->map->only(['id', 'branch_name'])) }};
                                            const branch = branches.find(b => b.id == this.selectedBranchId);
                                            return branch ? branch.branch_name : 'Select a branch';
                                        },
                                        selectBranch(id) { this.selectedBranchId = id; this.open = false; }
                                    }" x-init="$watch('editProductData.branch_id', value => selectedBranchId = value)" class="relative">
                                        <label class="block text-sm font-medium text-[#4A2C1D]">Assign Branch</label>
                                        <input type="hidden" name="branch_id" x-model="selectedBranchId">
                                        <button @click="open=!open" @click.away="open=false" type="button"
                                            class="w-full flex justify-between items-center border-2 border-[#7F5539] rounded px-3 py-2 mt-1 text-left bg-white">
                                            <span x-text="selectedBranchName" :class="{ 'text-gray-500': !selectedBranchId }"></span>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500"
                                                :class="{ 'rotate-180': open }">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                            </svg>
                                        </button>
                                        <div x-show="open" x-transition
                                            class="absolute mt-1 w-full bg-white rounded-md shadow-lg z-20 border border-gray-200 max-h-60 overflow-y-auto"
                                            style="display:none;">
                                            @forelse($branches as $branch)
                                                <a href="#" @click.prevent="selectBranch({{ $branch->id }})"
                                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ $branch->branch_name }}</a>
                                            @empty
                                                <span class="block px-4 py-2 text-sm text-gray-500">No branches available</span>
                                            @endforelse
                                        </div>
                                    </div>

                                    {{-- Product Category --}}
                                    <div x-data="{
                                        open: false,
                                        selectedCategory: editProductData?.product_category || '',
                                        categories: [
                                            { value: 'rtd', label: 'RTD / Packaged' },
                                            { value: 'mto', label: 'Made-to-Order' },
                                        ],
                                        get selectedLabel() {
                                            if (!this.selectedCategory) return 'Select a category';
                                            const cat = this.categories.find(c => c.value === this.selectedCategory);
                                            return cat ? cat.label : 'Select a category';
                                        },
                                        selectCategory(value) { this.selectedCategory = value; this.open = false; }
                                    }" x-init="$watch('editProductData.product_category', value => selectedCategory = value)"
                                    class="relative">
                                        <label class="block text-sm font-medium text-[#4A2C1D]">Category</label>
                                        <input type="hidden" name="product_category" x-model="selectedCategory">
                                        <button @click="open=!open" @click.away="open=false" type="button"
                                            class="w-full flex justify-between items-center border-2 border-[#7F5539] rounded px-3 py-2 mt-1 bg-white text-left">
                                            <span x-text="selectedLabel" :class="{ 'text-gray-500': !selectedCategory }"></span>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500"
                                                :class="{ 'rotate-180': open }">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                            </svg>
                                        </button>
                                        <div x-show="open" x-transition
                                            class="absolute mt-1 w-full bg-white rounded-md shadow-lg z-20 border border-gray-200"
                                            style="display:none;">
                                            <template x-for="cat in categories" :key="cat.value">
                                                <a href="#" @click.prevent="selectCategory(cat.value)"
                                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                    x-text="cat.label"></a>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Product Type --}}
                                    <div>
                                        <label class="block text-sm font-medium text-[#4A2C1D]">Type</label>
                                        <input type="text" name="product_type" x-model="editProductData.product_type"
                                            class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1"
                                            placeholder="e.g., Coffee, Biscuit, Water">
                                    </div>

                                    {{-- Product Name --}}
                                    <div>
                                        <label class="block text-sm font-medium text-[#4A2C1D]">Name</label>
                                        <input type="text" name="product_name" x-model="editProductData.product_name"
                                            required class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1">
                                    </div>

                                    {{-- Unit & Threshold --}}
                                    <div class="grid grid-cols-2 gap-4">
                                        <div x-data="{
                                            open: false,
                                            selectedUnit: editProductData?.unit || '',
                                            units: ['g', 'kg', 'mg', 'lb', 'oz', 'ml', 'l', 'cup', 'tbsp', 'tsp', 'pcs', 'pack'],
                                            get selectedUnitName() { return this.selectedUnit || 'Select a unit'; },
                                            selectUnit(u) { this.selectedUnit = u; this.open = false; }
                                        }" x-init="$watch('editProductData.unit', value => selectedUnit = value)" class="relative">
                                            <label class="block text-sm font-medium text-[#4A2C1D]">Unit</label>
                                            <input type="hidden" name="unit" x-model="selectedUnit">
                                            <button @click="open=!open" @click.away="open=false" type="button"
                                                class="w-full flex justify-between items-center border-2 border-[#7F5539] rounded px-3 py-2 mt-1 bg-white text-left">
                                                <span x-text="selectedUnitName" :class="{ 'text-gray-500': !selectedUnit }"></span>
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500"
                                                    :class="{ 'rotate-180': open }">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>
                                            <div x-show="open" x-transition
                                                class="absolute mt-1 w-full bg-white rounded-md shadow-lg z-20 border border-gray-200 max-h-60 overflow-y-auto"
                                                style="display:none;">
                                                <template x-for="u in units" :key="u">
                                                    <a href="#" @click.prevent="selectUnit(u)"
                                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" x-text="u"></a>
                                                </template>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-[#4A2C1D]">Quantity Threshold</label>
                                            <input type="number" name="quantity_threshold" step="0.01" min="0"
                                                x-model="editProductData.quantity_threshold"
                                                class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1"
                                                placeholder="Low stock alert level">
                                        </div>
                                    </div>

                                    {{-- Selling Price --}}
                                    <div>
                                        <label class="block text-sm font-medium text-[#4A2C1D]">Selling Price (₱)</label>
                                        <input type="number" name="selling_price" step="0.01" min="0"
                                            x-model="editProductData.selling_price"
                                            class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1"
                                            placeholder="Enter selling price">
                                    </div>

                                </div>
                            </div>

                            <div class="mt-6 flex justify-end space-x-3 border-t pt-4">
                                <button type="button" @click="closeEditModal()"
                                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="isSubmitting"
                                    class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-[#7F5539] hover:bg-[#4A2C1D] disabled:opacity-50">
                                    <span x-text="isSubmitting ? 'Updating...' : 'Update Product'"></span>
                                </button>
                            </div>
                        </form>
                    </template>
                </div>
            </div>
        </div>

        <!-- ===== ARCHIVE CONFIRMATION MODAL ===== -->
        <div x-show="showArchiveModal" x-cloak x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 z-[9999] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeArchiveModal()"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full sm:p-6">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-5">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Archive Product</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Are you sure you want to archive
                                <strong class="text-[#4A2C1D]" x-text="selectedProduct?.product_name"></strong>?
                                This deactivates the product definition. Stock movements are tracked separately via inventory transactions.
                            </p>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 flex space-x-3">
                        <button type="button" @click="closeArchiveModal()"
                            class="flex-1 inline-flex justify-center px-4 py-2 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                            Cancel
                        </button>
                        <button type="button" @click="confirmArchive()"
                            class="flex-1 inline-flex justify-center px-4 py-2 border border-transparent text-base font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors duration-200">
                            Archive
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('alpine:init', () => {

            Alpine.data('filterState', () => ({
                filters: {
                    branch_id: '{{ request('branch_id', '') }}',
                    product_type: '{{ request('product_type', '') }}',
                    product_status: '{{ request('product_status', '') }}',
                },
                availableBranches: @json($branches ?? []),
                availableTypes: @json($productTypes ?? []),
                clearFilters() {
                    this.filters = { branch_id: '', product_type: '', product_status: '' };
                },
                applyFilters() {
                    const main = Alpine.$data(document.querySelector('[x-data="productData()"]'));
                    main.applyFilters({ ...main.currentFilters, ...this.filters, search: main.searchQuery });
                    main.removeBodyClass();
                }
            }));

            Alpine.data('productData', () => ({
                products: @json($products->items() ?? []),
                pagination: @json($products->toArray()),
                stats: @json($stats),
                branches: @json($branches ?? []),
                productTypes: @json($productTypes ?? []),
                currentFilters: {
                    branch_id: '{{ request('branch_id', '') }}',
                    product_type: '{{ request('product_type', '') }}',
                    product_status: '{{ request('product_status', '') }}',
                    search: '{{ request('search', '') }}',
                },
                addFormData: { product_type: '', product_name: '', unit: '', quantity_threshold: '', selling_price: '' },
                searchQuery: '{{ request('search', '') }}',
                showFilters: false,
                showAddModal: false,
                showEditModal: false,
                showArchiveModal: false,
                selectedProduct: null,
                editProductData: null,
                isSubmitting: false,
                paginationLinks: [],
                isLoading: false,

                init() {
                    this.updatePaginationLinks();
                    this.updateActiveFilters();
                },

                get hasActiveFilters() {
                    return Object.values(this.currentFilters).some(v => v !== '');
                },

                get activeFilters() {
                    const filters = [];
                    if (this.currentFilters.search) filters.push({ key: 'search', label: `Search: ${this.currentFilters.search}` });
                    if (this.currentFilters.branch_id) {
                        const b = this.branches.find(b => b.id == this.currentFilters.branch_id);
                        filters.push({ key: 'branch_id', label: `Branch: ${b?.branch_name || this.currentFilters.branch_id}` });
                    }
                    if (this.currentFilters.product_type) filters.push({ key: 'product_type', label: `Type: ${this.currentFilters.product_type}` });
                    if (this.currentFilters.product_status) {
                        const label = this.currentFilters.product_status === '1' ? 'Available' : 'Unavailable';
                        filters.push({ key: 'product_status', label: `Status: ${label}` });
                    }
                    return filters;
                },

                formatNumber(n) { return new Intl.NumberFormat().format(n); },

                async performSearch() {
                    this.currentFilters.search = this.searchQuery;
                    await this.applyFilters(this.currentFilters);
                },

                async applyFilters(filters) {
                    this.isLoading = true;
                    this.showFilters = false;
                    this.currentFilters = { ...filters };
                    try {
                        const q = new URLSearchParams();
                        Object.entries(this.currentFilters).forEach(([k, v]) => { if (v) q.append(k, v); });
                        const res = await fetch(`?${q.toString()}&ajax=true`, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (!res.ok) throw new Error();
                        const data = await res.json();
                        if (data.success) {
                            this.products = data.data;
                            this.pagination = data.pagination;
                            this.stats = data.stats;
                            this.updatePaginationLinks();
                            this.updateActiveFilters();
                        }
                    } catch { alert('Failed to apply filters. Please try again.'); }
                    finally { this.isLoading = false; this.removeBodyClass(); }
                },

                async clearAllFilters() {
                    this.searchQuery = '';
                    this.currentFilters = { branch_id: '', product_type: '', product_status: '', search: '' };
                    await this.applyFilters(this.currentFilters);
                },

                removeFilter(key) {
                    if (key === 'search') { this.searchQuery = ''; this.currentFilters.search = ''; }
                    else this.currentFilters[key] = '';
                    this.applyFilters(this.currentFilters);
                },

                updateActiveFilters() {
                    const q = new URLSearchParams();
                    Object.entries(this.currentFilters).forEach(([k, v]) => { if (v) q.append(k, v); });
                    window.history.replaceState({}, '', `${window.location.pathname}?${q.toString()}`);
                },

                async changePage(page) {
                    if (page < 1 || page > this.pagination.last_page) return;
                    this.isLoading = true;
                    try {
                        const q = new URLSearchParams();
                        Object.entries(this.currentFilters).forEach(([k, v]) => { if (v) q.append(k, v); });
                        q.append('page', page); q.append('ajax', 'true');
                        const res = await fetch(`?${q.toString()}`, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const data = await res.json();
                        if (data.success) { this.products = data.data; this.pagination = data.pagination; this.stats = data.stats; this.updatePaginationLinks(); }
                    } catch { alert('Failed to load page.'); }
                    finally { this.isLoading = false; }
                },

                updatePaginationLinks() {
                    if (!this.pagination?.last_page) { this.paginationLinks = []; return; }
                    const current = this.pagination.current_page, last = this.pagination.last_page;
                    const range = [], result = [];
                    for (let i = 1; i <= last; i++) {
                        if (i === 1 || i === last || (i >= current - 2 && i <= current + 2)) range.push(i);
                    }
                    let prev = 0;
                    for (let i of range) {
                        if (prev) { if (i - prev === 2) result.push(prev + 1); else if (i - prev !== 1) result.push('...'); }
                        result.push(i); prev = i;
                    }
                    this.paginationLinks = result;
                },

                openAddModal() { this.showAddModal = true; this.addBodyClass(); this.resetAddForm(); },
                closeAddModal() { this.showAddModal = false; this.removeBodyClass(); this.resetAddForm(); },

                resetAddForm() {
                    this.addFormData = { product_type: '', product_name: '', unit: '', quantity_threshold: '', selling_price: '' };
                    const fi = document.getElementById('addProductImg');
                    if (fi) fi.value = '';
                    const prev = document.getElementById('addProductPreview');
                    if (prev) prev.src = 'https://ui-avatars.com/api/?name=product&background=7F5539&color=FFFFFF';
                },

                handleAddImagePreview(e) {
                    const file = e.target.files[0];
                    if (file) { const r = new FileReader(); r.onload = ev => { document.getElementById('addProductPreview').src = ev.target.result; }; r.readAsDataURL(file); }
                },

                async submitAddForm() {
                    if (this.isSubmitting) return;
                    this.isSubmitting = true;
                    try {
                        const fd = new FormData();
                        for (let el of document.getElementById('addProductForm').elements) {
                            if (el.name && el.type !== 'button') {
                                if (el.type === 'file') { if (el.files.length > 0) fd.append(el.name, el.files[0]); }
                                else fd.append(el.name, el.value);
                            }
                        }
                        fd.append('_token', '{{ csrf_token() }}');
                        const res = await fetch('{{ route('sub_one.products.storeProductAjax') }}', {
                            method: 'POST', body: fd,
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const data = await res.json();
                        if (data.success) { this.closeAddModal(); await this.applyFilters(this.currentFilters); }
                        else throw new Error(data.message || 'Failed');
                    } catch { alert('Failed to create product. Please try again.'); }
                    finally { this.isSubmitting = false; }
                },

                async openEditModal(product) {
                    this.selectedProduct = product;
                    try {
                        const res = await fetch(`{{ url('sub_one/products') }}/${product.uuid}/data`, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const data = await res.json();
                        if (data.success) { this.editProductData = data.product; this.showEditModal = true; this.addBodyClass(); }
                        else throw new Error();
                    } catch { alert('Failed to load product data. Please try again.'); }
                },

                closeEditModal() {
                    this.showEditModal = false; this.editProductData = null; this.removeBodyClass();
                    const fi = document.getElementById('editProductImg'); if (fi) fi.value = '';
                },

                handleEditImagePreview(e) {
                    const file = e.target.files[0];
                    if (file) { const r = new FileReader(); r.onload = ev => { document.getElementById('editProductPreview').src = ev.target.result; }; r.readAsDataURL(file); }
                },

                async submitEditForm() {
                    if (this.isSubmitting || !this.editProductData) return;
                    this.isSubmitting = true;
                    try {
                        const fd = new FormData();
                        for (let el of document.getElementById('editProductForm').elements) {
                            if (el.name && el.type !== 'button') {
                                if (el.type === 'file') { if (el.files.length > 0) fd.append(el.name, el.files[0]); }
                                else fd.append(el.name, el.value);
                            }
                        }
                        fd.append('_token', '{{ csrf_token() }}');
                        fd.append('_method', 'PATCH');
                        const res = await fetch(`{{ url('sub_one/products/ajax') }}/${this.editProductData.uuid}/update`, {
                            method: 'POST', body: fd,
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const data = await res.json();
                        if (data.success) { this.closeEditModal(); await this.applyFilters(this.currentFilters); }
                        else throw new Error(data.message || 'Failed');
                    } catch { alert('Failed to update product. Please try again.'); }
                    finally { this.isSubmitting = false; }
                },

                openArchiveModal(product) { this.selectedProduct = product; this.showArchiveModal = true; this.addBodyClass(); },
                closeArchiveModal() { this.showArchiveModal = false; this.removeBodyClass(); },

                confirmArchive() {
                    if (!this.selectedProduct) return;
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `{{ url('sub_one/products/deactivate') }}/${this.selectedProduct.uuid}`;
                    const csrf = document.createElement('input'); csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = '{{ csrf_token() }}';
                    const method = document.createElement('input'); method.type = 'hidden'; method.name = '_method'; method.value = 'PATCH';
                    form.appendChild(csrf); form.appendChild(method);
                    document.body.appendChild(form);
                    form.submit();
                },

                closeFilterModal() { this.showFilters = false; this.removeBodyClass(); },
                addBodyClass() { document.body.classList.add('modal-open'); },
                removeBodyClass() { document.body.classList.remove('modal-open'); }
            }));
        });
    </script>

    <style>
        .modal-open { overflow: hidden; }
        .overflow-y-auto::-webkit-scrollbar { width: 8px; }
        .overflow-y-auto::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        .overflow-y-auto::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
        .overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #555; }
    </style>
@endsection