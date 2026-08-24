@extends('layouts.app')

@section('title', 'Product with Ingredients')

@section('content')
    <div x-data="productIngredientData()" x-init="init()" class="p-4">
        <!-- Header Section -->
        <div class="flex items-center justify-between mb-8">
            <!-- Left: Title -->
            <h1 class="text-2xl font-bold text-[#4A2C1D]">
                {{ $products->product_name }} - Ingredients
            </h1>

            <!-- Right: Back to Products Link -->
            <div>
                <a href="{{ route('sub_one.products.showProduct') }}"
                    class="text-sm font-medium text-[#7F5539] hover:underline">
                    Back
                </a>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-4 gap-6 mb-8">
            <!-- Total Ingredients -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Ingredients</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.total_ingredients"></p>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-lg">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
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
                    <h2 class="text-lg font-semibold text-gray-900">Ingredient Records</h2>

                    <!-- Right: Search + Filter + Add Button -->
                    <div class="flex items-center space-x-3">
                        <!-- Search Input -->
                        <div class="relative w-80">
                            <input type="text" x-model="searchQuery" @input.debounce.500ms="performSearch()"
                                placeholder="Search by ingredient name, type, or batch no..."
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

                        <!-- Add Ingredient Button (changed to modal trigger) -->
                        <button @click="openAddModal()"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539] flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.5" class="w-5 h-5 mr-2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            Add Ingredient
                        </button>
                    </div>
                </div>

                <!-- Small to Smallest Screens Layout -->
                <div class="sm:hidden space-y-4">
                    <!-- First Row: Ingredient Records + Add Button -->
                    <div class="flex items-center justify-between">
                        <!-- Left: Header -->
                        <h2 class="text-lg font-semibold text-gray-900">Ingredient Records</h2>

                        <!-- Right: Add Button -->
                        <button @click="openAddModal()"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539] flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.5" class="w-5 h-5 mr-2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            Add
                        </button>
                    </div>

                    <!-- Second Row: Search + Filter -->
                    <div class="flex items-center space-x-3">
                        <!-- Search Input -->
                        <div class="relative flex-1">
                            <input type="text" x-model="searchQuery" @input.debounce.500ms="performSearch()"
                                placeholder="Search by ingredient name, type, or batch no..."
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
                                stroke="currentColor" class="w-5 h-5">
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
                                Image
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 z-30 bg-gray-50 shadow-right">
                                Ingredient Details
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Branch
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Quantity Needed
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(ingredient, index) in ingredients" :key="ingredient.uuid">
                            <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-100'">
                                <!-- Image -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex-shrink-0 h-16 w-16">
                                        <img :src="ingredient.ingredient?.ingredient_img ?
                                            `/storage/app/public/${ingredient.ingredient.ingredient_img}` :
                                            `https://ui-avatars.com/api/?name=${encodeURIComponent(ingredient.ingredient?.ingredient_name || 'Ingredient')}&background=7F5539&color=FFFFFF`"
                                            :alt="ingredient.ingredient?.ingredient_name || 'Ingredient'"
                                            class="h-16 w-16 rounded-lg object-cover border border-gray-200">
                                    </div>
                                </td>

                                <!-- Ingredient Details -->
                                <td class="px-6 py-4 whitespace-nowrap sticky left-0 z-20 shadow-right"
                                    :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-100'">
                                    <div class="text-sm font-medium text-gray-900"
                                        x-text="ingredient.ingredient?.ingredient_name || 'N/A'">
                                    </div>
                                    <div class="text-sm text-gray-500"
                                        x-text="ingredient.ingredient?.ingredient_type || 'N/A'"></div>
                                    <div class="text-xs text-gray-400 mt-1"
                                        x-text="'Batch: ' + (ingredient.ingredient?.ingredient_batch_no || 'N/A')"></div>
                                    <div class="text-xs text-gray-400" x-text="'Unit: ' + ingredient.unit"></div>
                                </td>

                                <!-- Branch -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" x-text="ingredient.branch?.branch_name || 'N/A'">
                                    </div>
                                </td>

                                <!-- Quantity Needed -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1">
                                        <span class="text-sm font-medium text-gray-900"
                                            x-text="formatNumber(ingredient.quantity_needed)"></span>
                                        <span class="text-sm text-gray-500" x-text="ingredient.unit"></span>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <!-- Edit Button -->
                                        <div class="relative group">
                                            <button @click="openEditModal(ingredient)"
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
                                                Edit Ingredient
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <tr x-show="!ingredients.length">
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                        </path>
                                    </svg>
                                    <h5 class="text-sm font-medium text-gray-900"
                                        x-text="hasActiveFilters ? 'No ingredients match your filters' : 'No ingredients found for {{ $products->product_name }}'">
                                    </h5>
                                    <p class="text-sm text-gray-500"
                                        x-text="hasActiveFilters ? 'Try adjusting your filters.' : 'Add your first ingredient to get started.'">
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
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Filter Ingredients</h3>

                        <div x-data="filterState()">
                            <!-- Filter Inputs -->
                            <div class="space-y-4">
                                <!-- Ingredient Type Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Ingredient Type</label>
                                    <select x-model="filters.ingredient_type"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                        <option value="">All Types</option>
                                        <template x-for="type in availableTypes" :key="type">
                                            <option :value="type" x-text="type"></option>
                                        </template>
                                    </select>
                                </div>

                                <!-- Unit Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                                    <select x-model="filters.unit"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                        <option value="">All Units</option>
                                        <template x-for="unit in availableUnits" :key="unit">
                                            <option :value="unit" x-text="unit"></option>
                                        </template>
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

        <!-- Add Product Ingredient Modal -->
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
                        <h3 class="text-lg font-semibold text-gray-900">Add New Ingredient</h3>
                        <button @click="closeAddModal()" type="button"
                            class="text-gray-400 hover:text-gray-500 focus:outline-none">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Form -->
                    <form id="addProductIngredientForm" @submit.prevent="submitAddForm" x-data="ingredientCalculator()">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $products->id }}">
                        <input type="hidden" name="branch_id" value="{{ $products->branch_id }}">
                        <input type="hidden" name="quantity_in_base_unit" x-model="convertedQuantity">
                        <input type="hidden" name="base_unit" x-model="baseUnit">

                        <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
                            <!-- Ingredient Dropdown -->
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Select Ingredient</label>
                                <input type="hidden" name="ingredient_id" x-model="selectedIngredientId">
                                <button @click="openIngredient = !openIngredient; openUnit = false" @click.away="openIngredient = false"
                                    type="button"
                                    class="w-full flex justify-between items-center border-2 border-[#7F5539] rounded px-3 py-2 text-left bg-white">
                                    <span x-text="selectedIngredientName" :class="{ 'text-gray-500': !selectedIngredientId }"></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor" class="w-4 h-4 transition-transform duration-200 text-gray-500"
                                        :class="{ 'rotate-180': openIngredient }">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>
                                <div x-show="openIngredient" x-transition
                                    class="absolute mt-1 w-full bg-white rounded-md shadow-lg z-20 border border-gray-200 max-h-60 overflow-y-auto"
                                    style="display:none;">
                                    <template x-for="ingredient in filteredIngredients" :key="ingredient.id">
                                        <a href="#" @click.prevent="selectIngredient(ingredient)"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" x-text="ingredient.ingredient_name"></a>
                                    </template>
                                    <div x-show="!filteredIngredients.length" class="px-4 py-2 text-sm text-gray-500">
                                        No ingredients available for this branch
                                    </div>
                                </div>
                            </div>

                            <!-- Unit Dropdown -->
                            <div class="flex gap-4 mt-4">
                                <div class="flex-1 relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                                    <input type="hidden" name="unit" x-model="selectedUnit">
                                    <button @click="openUnit=!openUnit; openIngredient=false" @click.away="openUnit=false"
                                        type="button"
                                        class="w-full flex justify-between items-center border-2 border-[#7F5539] rounded px-3 py-2 text-left bg-white">
                                        <span x-text="selectedUnitName" :class="{ 'text-gray-500': !selectedUnit }"></span>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor" class="w-4 h-4 transition-transform duration-200 text-gray-500"
                                            :class="{ 'rotate-180': openUnit }">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>
                                    <div x-show="openUnit" x-transition
                                        class="absolute mt-1 w-full bg-white rounded-md shadow-lg z-20 border border-gray-200 max-h-60 overflow-y-auto"
                                        style="display:none;">
                                        <template x-for="unit in availableUnits" :key="unit">
                                            <a href="#" @click.prevent="selectUnit(unit)"
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" x-text="unit"></a>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Quantity Needed -->
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Quantity Needed</label>
                                <input type="number" name="quantity_needed" min="0" x-model.number="quantityNeeded"
                                    @input="computeConversion()" class="w-full border-2 border-[#7F5539] rounded px-3 py-2"
                                    placeholder="Enter quantity needed for this product">
                            </div>

                            <!-- Converted Quantity Display -->
                            <div class="mt-4 p-4 border border-gray-200 rounded" x-show="convertedQuantity !== null">
                                <h2 class="font-bold mb-2" x-text="selectedIngredientName"></h2>
                                <p>Available: <span x-text="totalStockInBase + ' ' + baseUnit"></span></p>
                                <p>Quantity entered: <span x-text="quantityNeeded + ' ' + selectedUnit"></span></p>
                                <p>Converted to base unit: <span x-text="convertedQuantity + ' ' + baseUnit"></span></p>
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
                                <span x-show="!isSubmitting">Add Ingredient</span>
                                <span x-show="isSubmitting">Adding...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Product Ingredient Modal -->
        <div x-show="showEditModal"
         @close-edit-modal.window="closeEditModal(); applyFilters(currentFilters)"
         @ingredient-updated.window="closeEditModal(); applyFilters(currentFilters)"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[9999] overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 @click="$dispatch('close-edit-modal')"></div> {{-- ✅ CHANGED: was closeEditModal() --}}
    
            <div class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle w-full max-w-2xl">
                <!-- Header -->
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Ingredient</h3>
                    <!-- ✅ CHANGED: was closeEditModal() -->
                    <button @click="$dispatch('close-edit-modal')" type="button"
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
                    <div x-show="!editIngredientData && showEditModal" class="py-8 text-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#7F5539] mx-auto"></div>
                        <p class="mt-2 text-sm text-gray-500">Loading ingredient data...</p>
                    </div>
    
                    <!-- Form (only rendered when data is loaded) -->
                    <template x-if="editIngredientData">
                        <!-- ✅ CHANGED: @submit.prevent now calls the method inside editIngredientCalculator -->
                        <form id="editProductIngredientForm"
                              @submit.prevent="submitEditForm()"
                              x-data="editIngredientCalculator(editIngredientData)">
                            @csrf
                            
                            <input type="hidden" name="product_id" value="{{ $products->id }}">
                            <input type="hidden" name="branch_id" value="{{ $products->branch_id }}">
                            <input type="hidden" name="quantity_in_base_unit" x-model="convertedQuantity">
                            <input type="hidden" name="base_unit" x-model="baseUnit">
    
                            <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
                                <!-- Ingredient Dropdown -->
                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Ingredient</label>
                                    <input type="hidden" name="ingredient_id" x-model="selectedIngredientId">
                                    <button @click="openIngredient = !openIngredient; openUnit = false"
                                        @click.away="openIngredient = false"
                                        type="button"
                                        class="w-full flex justify-between items-center border-2 border-[#7F5539] rounded px-3 py-2 text-left bg-white">
                                        <span x-text="selectedIngredientName"
                                              :class="{ 'text-gray-500': !selectedIngredientId }"></span>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor"
                                            class="w-4 h-4 transition-transform duration-200 text-gray-500"
                                            :class="{ 'rotate-180': openIngredient }">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>
                                    <div x-show="openIngredient" x-transition
                                        class="absolute mt-1 w-full bg-white rounded-md shadow-lg z-20 border border-gray-200 max-h-60 overflow-y-auto"
                                        style="display:none;">
                                        <template x-for="ingredient in filteredIngredients" :key="ingredient.id">
                                            <a href="#" @click.prevent="selectIngredient(ingredient)"
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                x-text="ingredient.ingredient_name"></a>
                                        </template>
                                        <div x-show="!filteredIngredients.length"
                                             class="px-4 py-2 text-sm text-gray-500">
                                            No ingredients available for this branch
                                        </div>
                                    </div>
                                </div>
    
                                <!-- Unit Dropdown -->
                                <div class="flex gap-4 mt-4">
                                    <div class="flex-1 relative">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                                        <input type="hidden" name="unit" x-model="selectedUnit">
                                        <button @click="openUnit=!openUnit; openIngredient=false"
                                            @click.away="openUnit=false"
                                            type="button"
                                            class="w-full flex justify-between items-center border-2 border-[#7F5539] rounded px-3 py-2 text-left bg-white">
                                            <span x-text="selectedUnitName"
                                                  :class="{ 'text-gray-500': !selectedUnit }"></span>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor"
                                                class="w-4 h-4 transition-transform duration-200 text-gray-500"
                                                :class="{ 'rotate-180': openUnit }">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                            </svg>
                                        </button>
                                        <div x-show="openUnit" x-transition
                                            class="absolute mt-1 w-full bg-white rounded-md shadow-lg z-20 border border-gray-200 max-h-60 overflow-y-auto"
                                            style="display:none;">
                                            <template x-for="unit in availableUnits" :key="unit">
                                                <a href="#" @click.prevent="selectUnit(unit)"
                                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                    x-text="unit"></a>
                                            </template>
                                        </div>
                                    </div>
                                </div>
    
                                <!-- Quantity Needed -->
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity Needed</label>
                                    <input type="number" name="quantity_needed" min="0"
                                        x-model.number="quantityNeeded"
                                        @input="computeConversion()"
                                        class="w-full border-2 border-[#7F5539] rounded px-3 py-2"
                                        placeholder="Enter quantity needed for this product">
                                </div>
    
                                <!-- Converted Quantity Display -->
                                <div class="mt-4 p-4 border border-gray-200 rounded"
                                     x-show="convertedQuantity !== null">
                                    <h2 class="font-bold mb-2" x-text="selectedIngredientName"></h2>
                                    <p>Available: <span x-text="totalStockInBase + ' ' + baseUnit"></span></p>
                                    <p>Quantity entered: <span x-text="quantityNeeded + ' ' + selectedUnit"></span></p>
                                    <p>Converted to base unit: <span x-text="convertedQuantity + ' ' + baseUnit"></span></p>
                                </div>
                            </div>
    
                            <!-- Form Actions -->
                            <div class="mt-6 flex justify-end space-x-3">
                                <!-- ✅ CHANGED: was @click="closeEditModal()" — now dispatches event to parent -->
                                <button type="button"
                                    @click="$dispatch('close-edit-modal')"
                                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539]">
                                    Cancel
                                </button>
                                <!-- ✅ CHANGED: isSubmitting is now local to editIngredientCalculator -->
                                <button type="submit" :disabled="isSubmitting"
                                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539] disabled:opacity-50">
                                    <span x-show="!isSubmitting">Update Ingredient</span>
                                    <span x-show="isSubmitting">Updating...</span>
                                </button>
                            </div>
                        </form>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            // Filter state for the sidebar
            Alpine.data('filterState', () => ({
                filters: {
                    ingredient_type: '{{ request('ingredient_type', '') }}',
                    unit: '{{ request('unit', '') }}',
                },
                availableTypes: @json($ingredientTypes ?? []),
                availableUnits: @json($units ?? []),
                clearFilters() {
                    this.filters = {
                        ingredient_type: '',
                        unit: '',
                    };
                },
                applyFilters() {
                    const mainComponent = Alpine.$data(document.querySelector(
                        '[x-data="productIngredientData()"]'));
                    const newFilters = {
                        ...mainComponent.currentFilters,
                        ...this.filters,
                        search: mainComponent.searchQuery
                    };
                    mainComponent.applyFilters(newFilters);
                    mainComponent.removeBodyClass();
                }
            }));

            // Ingredient Calculator Component for Add Modal
            Alpine.data('ingredientCalculator', () => ({
                allIngredients: @json($ingredients ?? []),
                productBranchId: {{ $products->branch_id }},
                selectedIngredientId: '',
                selectedUnit: '',
                quantityNeeded: 0,
                convertedQuantity: null,
                totalStockInBase: null,
                baseUnit: '',
                openIngredient: false,
                openUnit: false,
                units: ['g', 'kg', 'mg', 'lb', 'oz', 'ml', 'l', 'cup', 'tbsp', 'tsp', 'pcs', 'pack'],

                // Filter ingredients by product's branch_id
                get filteredIngredients() {
                    return this.allIngredients.filter(ingredient => 
                        ingredient.branch_id == this.productBranchId
                    );
                },

                get selectedIngredient() {
                    return this.filteredIngredients.find(i => i.id == this.selectedIngredientId);
                },

                get selectedIngredientName() {
                    return this.selectedIngredient ? this.selectedIngredient.ingredient_name : 'Select Ingredient';
                },

                get selectedUnitName() {
                    return this.selectedUnit || 'Select Unit';
                },

                get availableUnits() {
                    // Use ingredient-specific conversion table if exists
                    return this.selectedIngredient && this.selectedIngredient.unit_conversions ?
                        Object.keys(this.selectedIngredient.unit_conversions) :
                        this.units;
                },

                selectIngredient(ingredient) {
                    this.selectedIngredientId = ingredient.id;
                    this.selectedUnit = '';
                    this.computeConversion();
                    this.openIngredient = false;
                },

                selectUnit(unit) {
                    this.selectedUnit = unit;
                    this.computeConversion();
                    this.openUnit = false;
                },

                computeConversion() {
                    const ingredient = this.selectedIngredient;
                    if (!ingredient || !this.selectedUnit || !this.quantityNeeded) {
                        this.convertedQuantity = null;
                        this.totalStockInBase = null;
                        this.baseUnit = null;
                        return;
                    }

                    const conversions = ingredient.unit_conversions || {};

                    // Use ingredient.unit as the base unit
                    const baseUnit = ingredient.converted_unit ?? ingredient.unit; // fallback
                    this.baseUnit = baseUnit;

                    // Conversion factor: only if both selectedUnit and baseUnit exist in conversions
                    const factor = conversions[this.selectedUnit] ?? 1;

                    // Converted quantity
                    this.convertedQuantity = this.quantityNeeded * factor;

                    // Total stock in base unit: prefer converted_stock_quantity_in, fallback to stock_quantity_in
                    const stockQty = ingredient.converted_stock_quantity_in ?? ingredient.stock_quantity_in ?? 0;
                    this.totalStockInBase = stockQty;
                }
            }));

            // Edit Ingredient Calculator Component
            Alpine.data('editIngredientCalculator', (editIngredientData) => ({
                allIngredients: @json($ingredients ?? []),
                productBranchId: {{ $products->branch_id }},
                selectedIngredientId: editIngredientData.ingredient_id || '',
                selectedUnit: editIngredientData.unit || '',
                quantityNeeded: editIngredientData.quantity_needed || 0,
                convertedQuantity: null,
                totalStockInBase: null,
                baseUnit: editIngredientData.base_unit || '',
                openIngredient: false,
                openUnit: false,
                isSubmitting: false,
                units: ['g', 'kg', 'mg', 'lb', 'oz', 'ml', 'l', 'cup', 'tbsp', 'tsp', 'pcs', 'pack'],

                // Filter ingredients by product's branch_id
                get filteredIngredients() {
                    return this.allIngredients.filter(ingredient => 
                        ingredient.branch_id == this.productBranchId
                    );
                },

                get selectedIngredient() {
                    return this.filteredIngredients.find(i => i.id == this.selectedIngredientId);
                },

                get selectedIngredientName() {
                    return this.selectedIngredient ? this.selectedIngredient.ingredient_name : 'Select Ingredient';
                },

                get selectedUnitName() {
                    return this.selectedUnit || 'Select Unit';
                },

                get availableUnits() {
                    // Use ingredient-specific conversion table if exists
                    return this.selectedIngredient && this.selectedIngredient.unit_conversions ?
                        Object.keys(this.selectedIngredient.unit_conversions) :
                        this.units;
                },

                init() {
                    this.computeConversion();
                },

                selectIngredient(ingredient) {
                    this.selectedIngredientId = ingredient.id;
                    this.selectedUnit = '';
                    this.computeConversion();
                    this.openIngredient = false;
                },

                selectUnit(unit) {
                    this.selectedUnit = unit;
                    this.computeConversion();
                    this.openUnit = false;
                },

                computeConversion() {
                    const ingredient = this.selectedIngredient;
                    if (!ingredient || !this.selectedUnit || !this.quantityNeeded) {
                        this.convertedQuantity = null;
                        this.totalStockInBase = null;
                        this.baseUnit = null;
                        return;
                    }

                    const conversions = ingredient.unit_conversions || {};

                    // Use ingredient.unit as the base unit
                    const baseUnit = ingredient.converted_unit ?? ingredient.unit; // fallback
                    this.baseUnit = baseUnit;

                    // Conversion factor: only if both selectedUnit and baseUnit exist in conversions
                    const factor = conversions[this.selectedUnit] ?? 1;

                    // Converted quantity
                    this.convertedQuantity = this.quantityNeeded * factor;

                    // Total stock in base unit: prefer converted_stock_quantity_in, fallback to stock_quantity_in
                    const stockQty = ingredient.converted_stock_quantity_in ?? ingredient.stock_quantity_in ?? 0;
                    this.totalStockInBase = stockQty;
                },
                async submitEditForm() {
                    if (this.isSubmitting) return;
            
                    this.isSubmitting = true;
            
                    try {
                        const form = document.getElementById('editProductIngredientForm');
                        const formData = new FormData(form);
            
                        const response = await fetch(
                            `/sub_one/product_ingredients/ajax/${editIngredientData.uuid}/update`, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
            
                        const data = await response.json();
            
                        if (data.success) {
                            // ✅ CHANGED: Dispatch event to parent to close modal and refresh table
                            this.$dispatch('ingredient-updated');
                        } else {
                            throw new Error(data.message || 'Failed to update product ingredient');
                        }
                    } catch (error) {
                        console.error('Error updating product ingredient:', error);
                        alert(error.message || 'Failed to update product ingredient. Please try again.');
                    } finally {
                        this.isSubmitting = false;
                    }
                }
            }));

            // Main component
            Alpine.data('productIngredientData', () => ({
                // Initial state
                ingredients: @json($product_ingredients->items() ?? []),
                pagination: @json($product_ingredients->toArray()),
                stats: @json($stats),
                ingredientTypes: @json($ingredientTypes ?? []),
                units: @json($units ?? []),
                allIngredients: @json($ingredients ?? []),
                currentFilters: {
                    ingredient_type: '{{ request('ingredient_type', '') }}',
                    unit: '{{ request('unit', '') }}',
                    search: '{{ request('search', '') }}',
                },
                searchQuery: '{{ request('search', '') }}',
                showFilters: false,
                showAddModal: false,
                showEditModal: false,
                selectedIngredient: null,
                editIngredientData: null,
                paginationLinks: [],
                isLoading: false,
                isSubmitting: false,

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

                    if (this.currentFilters.ingredient_type) {
                        filters.push({
                            key: 'ingredient_type',
                            label: `Type: ${this.currentFilters.ingredient_type}`
                        });
                    }

                    if (this.currentFilters.unit) {
                        filters.push({
                            key: 'unit',
                            label: `Unit: ${this.currentFilters.unit}`
                        });
                    }

                    return filters;
                },

                // Utility functions
                formatNumber(number) {
                    return new Intl.NumberFormat().format(number);
                },

                // Modal functions
                openAddModal() {
                    this.showAddModal = true;
                    this.addBodyClass();
                },

                closeAddModal() {
                    this.showAddModal = false;
                    this.removeBodyClass();
                },

                async openEditModal(ingredient) {
                    this.selectedIngredient = ingredient;
                    this.editIngredientData = null;
                    this.showEditModal = true;
                    this.addBodyClass();
                    
                    try {
                        const response = await fetch(
                            `{{ url('sub_one/product_ingredients/data') }}/{{ $products->uuid }}/${ingredient.uuid}`, {
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });

                        const data = await response.json();

                        if (data.success) {
                            this.editIngredientData = data.product_ingredient;
                        } else {
                            throw new Error(data.message || 'Failed to load ingredient data');
                        }
                    } catch (error) {
                        console.error('Error loading ingredient data:', error);
                        this.showNotification('Failed to load ingredient data. Please try again.', 'error');
                        this.closeEditModal();
                    }
                },

                closeEditModal() {
                    this.showEditModal = false;
                    this.editIngredientData = null;
                    this.selectedIngredient = null;
                    this.removeBodyClass();
                },

                // Form submission
                async submitAddForm() {
                    if (this.isSubmitting) return;
                    
                    this.isSubmitting = true;
                    
                    try {
                        const form = document.getElementById('addProductIngredientForm');
                        const formData = new FormData(form);

                        const response = await fetch(
                            '{{ route("sub_one.product_ingredients.storeProductIngredientAjax") }}', {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });

                        const data = await response.json();

                        if (data.success) {
                            this.showNotification('Product ingredient created successfully.', 'success');
                            this.closeAddModal();
                            
                            // Refresh the table
                            await this.applyFilters(this.currentFilters);
                        } else {
                            throw new Error(data.message || 'Failed to create product ingredient');
                        }
                    } catch (error) {
                        console.error('Error creating product ingredient:', error);
                        this.showNotification(error.message || 'Failed to create product ingredient. Please try again.', 'error');
                    } finally {
                        this.isSubmitting = false;
                    }
                },

                async submitEditForm() {
                    if (this.isSubmitting || !this.editIngredientData) return;
                    
                    this.isSubmitting = true;
                    
                    try {
                        const form = document.getElementById('editProductIngredientForm');
                        const formData = new FormData(form);

                        const response = await fetch(
                            `{{ url('sub_one/product_ingredients/ajax') }}/${this.editIngredientData.uuid}/update`, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });

                        const data = await response.json();

                        if (data.success) {
                            this.showNotification('Product ingredient updated successfully.', 'success');
                            this.closeEditModal();
                            
                            // Refresh the table
                            await this.applyFilters(this.currentFilters);
                        } else {
                            throw new Error(data.message || 'Failed to update product ingredient');
                        }
                    } catch (error) {
                        console.error('Error updating product ingredient:', error);
                        this.showNotification(error.message || 'Failed to update product ingredient. Please try again.', 'error');
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
                            this.ingredients = data.data;
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
                        ingredient_type: '',
                        unit: '',
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
                            this.ingredients = data.data;
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
                            this.ingredients = data.data;
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