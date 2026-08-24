@extends('layouts.app')

@section('title', 'Ingredients')

@section('content')
    <div x-data="ingredientData()" x-init="init()" class="p-4">
        <!-- Header Section - Always same position -->
        <div class="flex items-center justify-between mb-8">
            <!-- Left: Empty spacer -->
            <div class="flex-1"></div>
    
            <!-- Center: Title -->
            <h1 class="text-2xl font-bold text-gray-900 text-center">
                Ingredients
            </h1>
    
            <!-- Right: Archive Link - Always on the right side -->
            <div class="flex-1 text-right">
                <a href="{{ route('sub_one.ingredients.showDeactivatedIngredient') }}"
                   class="text-sm font-medium text-[#7F5539] hover:underline">
                   View Archives
                </a>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
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

            <!-- Available Ingredients -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Available</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.available_ingredients"></p>
                    </div>
                    <div class="p-3 bg-green-50 rounded-lg">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Unavailable Ingredients -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Unavailable</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.unavailable_ingredients"></p>
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
                <!-- Medium to Largest Screens Layout -->
                <div class="hidden sm:block">
                    <!-- Stack on medium, row on large -->
                    <div class="lg:flex lg:items-center lg:justify-between mb-4">
                        <!-- Left: Header -->
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 lg:mb-0">Ingredient Records</h2>

                        <!-- Right: Search + Filter + Add Button -->
                        <div class="flex flex-col sm:flex-row gap-4">
                            <!-- Search Input -->
                            <div class="relative flex-1">
                                <input type="text" x-model="searchQuery" @input.debounce.500ms="performSearch()"
                                    placeholder="Search by ingredient name, type, or batch no..."
                                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] w-full">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Filter and Add Buttons -->
                            <div class="flex items-center space-x-3">
                                <!-- Filter Button -->
                                <button @click="showFilters = true; addBodyClass()"
                                    class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] flex-shrink-0 whitespace-nowrap">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                                    </svg>
                                    Filters
                                </button>

                                <!-- Add Product Button -->
                                <button @click="openAddModal()"
                                    class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539] flex-shrink-0 whitespace-nowrap">
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

                <!-- Small to Smallest Screens Layout -->
                <div class="sm:hidden space-y-4">
                    <!-- First Row: Ingredient Records + Add Button -->
                    <div class="flex items-center justify-between">
                        <!-- Left: Header -->
                        <h2 class="text-lg font-semibold text-gray-900">Ingredient Records</h2>

                        <!-- Right: Add Button -->
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

                    <!-- Second Row: Search + Filter -->
                    <div class="flex items-center space-x-3">
                        <!-- Search Input -->
                        <div class="relative flex-1">
                            <input type="text" x-model="searchQuery" @input.debounce.500ms="performSearch()"
                                placeholder="Search by ingredient name, type, or batch no..."
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
                                Image
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 z-30 bg-gray-50 shadow-right">
                                Ingredient Details
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Branch
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Threshold
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
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
                                        <img :src="ingredient.ingredient_img ?
                                            `/storage/app/public/${ingredient.ingredient_img}` :
                                            `https://ui-avatars.com/api/?name=${encodeURIComponent(ingredient.ingredient_name)}&background=7F5539&color=FFFFFF`"
                                            :alt="ingredient.ingredient_name"
                                            class="h-16 w-16 rounded-lg object-cover border border-gray-200">
                                    </div>
                                </td>

                                <!-- Ingredient Details -->
                                <td class="px-6 py-4 whitespace-nowrap sticky left-0 z-20 shadow-right"
                                    :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-100'">
                                    <div class="text-sm font-medium text-gray-900" x-text="ingredient.ingredient_name">
                                    </div>
                                    <div class="text-sm text-gray-500" x-text="ingredient.ingredient_type"></div>
                                    <div class="text-xs text-gray-400 mt-1"
                                        x-text="'Batch: ' + ingredient.ingredient_batch_no"></div>
                                    <div class="text-xs text-gray-400" x-text="'Unit: ' + ingredient.unit"></div>

                                    <!-- Converted Values -->
                                    <template
                                        x-if="ingredient.unit_conversion && ingredient.converted_unit && ingredient.converted_stock_quantity_in">
                                        <div class="mt-2 p-2 bg-gray-50 rounded border border-gray-200">
                                            <div class="text-xs text-gray-600">
                                                <span
                                                    x-text="'Conversion: ' + ingredient.unit_conversion + ' ' + ingredient.converted_unit"></span>
                                            </div>
                                            <div class="text-xs text-gray-600">
                                                <span
                                                    x-text="'Converted Qty: ' + ingredient.converted_stock_quantity_in + ' ' + ingredient.converted_unit"></span>
                                            </div>
                                        </div>
                                    </template>
                                </td>

                                <!-- Branch -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" x-text="ingredient.branch?.branch_name || 'N/A'">
                                    </div>
                                </td>

                                <!-- Threshold -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <span
                                            x-text="formatNumber(ingredient.stock_quantity_threshold) + ' ' + ingredient.unit"></span>
                                    </div>
                                </td>

                                <!-- Status - Simplified Version -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div x-data="{ open: false }" class="relative">
                                        <button @click.prevent="open = !open" @click.away="open = false"
                                            class="flex items-center space-x-1 text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap cursor-pointer"
                                            :class="ingredient.ingredient_status ?
                                                'bg-green-200 text-green-800' :
                                                'bg-red-200 text-red-800'">
                                            <span
                                                x-text="ingredient.ingredient_status ? 'Available' : 'Unavailable'"></span>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor"
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
                                            <form :id="'update-ingredient-status-' + ingredient.uuid + '-available'"
                                                :action="'{{ url('sub_one/ingredients/status') }}/' + ingredient.uuid"
                                                method="POST" class="hidden">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="ingredient_status" value="1">
                                            </form>
                                            <a href="#"
                                                @click.prevent="document.getElementById('update-ingredient-status-' + ingredient.uuid + '-available').submit(); open = false;"
                                                class="block px-4 py-2 text-sm hover:bg-gray-100"
                                                :class="ingredient.ingredient_status ?
                                                    'bg-green-50 text-green-700 font-medium' : 'text-gray-700'">
                                                Available
                                            </a>

                                            <!-- Unavailable Option -->
                                            <form :id="'update-ingredient-status-' + ingredient.uuid + '-unavailable'"
                                                :action="'{{ url('sub_one/ingredients/status') }}/' + ingredient.uuid"
                                                method="POST" class="hidden">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="ingredient_status" value="0">
                                            </form>
                                            <a href="#"
                                                @click.prevent="document.getElementById('update-ingredient-status-' + ingredient.uuid + '-unavailable').submit(); open = false;"
                                                class="block px-4 py-2 text-sm hover:bg-gray-100"
                                                :class="!ingredient.ingredient_status ? 'bg-red-50 text-red-700 font-medium' :
                                                    'text-gray-700'">
                                                Unavailable
                                            </a>
                                        </div>
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

                                        <!-- Archive Button -->
                                        <div class="relative group">
                                            <button @click="openArchiveModal(ingredient)"
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
                                                Archive Ingredient
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
                                        x-text="hasActiveFilters ? 'No ingredients match your filters' : 'No ingredients found'">
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
            <div x-show="pagination && pagination.last_page > 1" class="px-4 sm:px-6 py-4 border-t border-gray-200">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <!-- Results Info - Always visible -->
                    <div class="text-sm text-gray-700 text-center sm:text-left">
                        Showing <span x-text="pagination.from || 0"></span> to <span x-text="pagination.to || 0"></span>
                        of <span x-text="pagination.total || 0"></span> entries
                    </div>
                    
                    <!-- Pagination Controls -->
                    <div class="flex flex-wrap justify-center items-center gap-2">
                        <!-- Previous Button -->
                        <button @click="changePage(pagination.current_page - 1)" 
                                :disabled="pagination.current_page === 1"
                                class="px-3 py-2 sm:py-1 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                            <span class="hidden sm:inline">Previous</span>
                            <span class="sm:hidden">←</span>
                        </button>

                        <!-- Page Numbers - Show fewer on mobile -->
                        <template x-for="page in paginationLinks" :key="page">
                            <button @click="changePage(page)" 
                                    class="px-3 py-2 sm:py-1 border rounded-lg text-sm font-medium transition-colors duration-200"
                                    :class="page === pagination.current_page ?
                                        'border-2 border-[#4A2C1D] bg-[#7F5539]/80 text-white' :
                                        'border-gray-300 text-gray-700 hover:bg-gray-50'"
                                    :disabled="page === '...'"
                                    x-text="page"
                                    :class="{
                                        'hidden sm:inline-flex': shouldHidePageNumber(page)
                                    }"></button>
                        </template>

                        <!-- Next Button -->
                        <button @click="changePage(pagination.current_page + 1)"
                                :disabled="pagination.current_page === pagination.last_page"
                                class="px-3 py-2 sm:py-1 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                            <span class="hidden sm:inline">Next</span>
                            <span class="sm:hidden">→</span>
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
                                <!-- Branch Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                                    <select x-model="filters.branch_id"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                        <option value="">All Branches</option>
                                        <template x-for="branch in availableBranches" :key="branch.id">
                                            <option :value="branch.id" x-text="branch.branch_name"></option>
                                        </template>
                                    </select>
                                </div>

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

                                <!-- Status Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select x-model="filters.ingredient_status"
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

        <!-- Add Ingredient Modal -->
        <div x-show="showAddModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[9999] overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeAddModal()"></div>
        
                <div
                    class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle w-full max-w-5xl">
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
                    <form id="addIngredientForm" @submit.prevent="submitAddForm" enctype="multipart/form-data">
                        @csrf
        
                        {{-- Two-column layout --}}
                        <div class="flex flex-col sm:flex-row gap-6" style="max-height: 70vh;">
        
                            {{-- LEFT SIDE: Image --}}
                            <div class="flex flex-col sm:w-80 w-full flex-shrink-0">
                                <label class="block text-sm font-medium text-[#4A2C1D] mb-2">Ingredient Image</label>
                                <img id="addIngredientPreview"
                                    src="https://ui-avatars.com/api/?name=ingredient&background=7F5539&color=FFFFFF"
                                    alt="Ingredient Preview"
                                    class="w-full flex-1 min-h-[220px] rounded-lg object-cover border-2 border-[#7F5539]">
                                <input id="addIngredientImg" type="file" name="ingredient_img"
                                    @change="handleAddImagePreview"
                                    class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-2 text-sm" accept="image/jpeg,image/png">
                                <p class="text-xs text-gray-500 mt-1">Only JPEG and PNG files are accepted.</p>
                            </div>
        
                            {{-- RIGHT SIDE: Details --}}
                            <div class="flex-1 overflow-y-auto pr-2 space-y-4">
        
                                {{-- Branch Name --}}
                                <div x-data="{
                                    open: false,
                                    selectedBranchId: '',
                                    get selectedBranchName() {
                                        if (!this.selectedBranchId) return 'Select a branch';
                                        const branches = {{ Js::from($branches->map->only(['id', 'branch_name'])) }};
                                        const branch = branches.find(b => b.id == this.selectedBranchId);
                                        return branch ? branch.branch_name : 'Select a branch';
                                    },
                                    selectBranch(id) {
                                        this.selectedBranchId = id;
                                        this.open = false;
                                    }
                                }" class="relative">
                                    <label class="block text-sm font-medium text-[#4A2C1D]">Assign Branch</label>
                                    <input type="hidden" name="branch_id" x-model="selectedBranchId">
                                    <button @click="open=!open" @click.away="open=false" type="button"
                                        class="w-full flex justify-between items-center border-2 border-[#7F5539] rounded px-3 py-2 mt-1 text-left bg-white">
                                        <span x-text="selectedBranchName"
                                            :class="{ 'text-gray-500': !selectedBranchId }"></span>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor"
                                            class="w-4 h-4 transition-transform duration-200 text-gray-500"
                                            :class="{ 'rotate-180': open }">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m19.5 8.25-7.5 7.5-7.5-7.5" />
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
        
                                {{-- Ingredient Type --}}
                                <div>
                                    <label class="block text-sm font-medium text-[#4A2C1D]">Ingredient Type</label>
                                    <input type="text" name="ingredient_type" x-model="addFormData.ingredient_type"
                                        class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1"
                                        placeholder="e.g., Coffee, Biscuit, Cup of Noodle, Water">
                                </div>
        
                                {{-- Ingredient Name --}}
                                <div>
                                    <label class="block text-sm font-medium text-[#4A2C1D]">Ingredient Name</label>
                                    <input type="text" name="ingredient_name" x-model="addFormData.ingredient_name"
                                        required class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1">
                                </div>
        
                                {{-- Threshold & Conversion Section --}}
                                <div x-data="addConversionData()">
                                    {{-- Threshold | Unit --}}
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-[#4A2C1D]">Quantity Threshold</label>
                                            <input type="number" name="stock_quantity_threshold" min="0"
                                                class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1">
                                        </div>
        
                                        <div class="relative">
                                            <label class="block text-sm font-medium text-[#4A2C1D]">Unit</label>
                                            <input type="hidden" name="unit" x-model="selectedUnit">
                                            <button @click="open=!open" @click.away="open=false" type="button"
                                                class="w-full flex justify-between items-center border-2 border-[#7F5539] rounded px-3 py-2 mt-1 bg-white text-left">
                                                <span x-text="selectedUnitName"
                                                    :class="{ 'text-gray-500': !selectedUnitInner }"></span>
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.5" stroke="currentColor"
                                                    class="w-4 h-4 transition-transform duration-200 text-gray-500"
                                                    :class="{ 'rotate-180': open }">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>
                                            <div x-show="open" x-transition
                                                class="absolute mt-1 w-full bg-white rounded-md shadow-lg z-20 border border-gray-200 max-h-60 overflow-y-auto"
                                                style="display:none;">
                                                <template x-for="u in units" :key="u">
                                                    <a href="#" @click.prevent="selectUnit(u)"
                                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                        x-text="u"></a>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
        
                                    {{-- Convert to Base Unit Section --}}
                                    <div class="mt-4 relative">
                                        <div class="flex items-center justify-between mb-2">
                                            <h3 class="text-[#4A2C1D] font-semibold">Convert to Base Unit</h3>
                                            <div class="relative">
                                                <button type="button" @click="showInfo()"
                                                    class="w-6 h-6 flex items-center justify-center bg-gray-200 text-gray-700 rounded-full hover:bg-gray-300 text-sm font-bold transition-colors">
                                                    ?
                                                </button>
                                                <div x-show="showInfoBox" x-transition @click.away="showInfoBox = false"
                                                    class="absolute right-0 bottom-0 -translate-y-full mt-2 w-80 bg-white border border-[#7F5539] rounded-lg shadow-lg z-30 p-4"
                                                    style="display: none;">
                                                    <div class="flex justify-between items-start mb-2">
                                                        <h4 class="font-semibold text-[#4A2C1D]">Unit Conversion Info</h4>
                                                        <button type="button" @click="showInfoBox = false"
                                                            class="text-gray-500 hover:text-gray-700">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                    <p class="text-sm text-gray-600 mb-4">
                                                        Is this an ingredient that needs conversion to standard units? This
                                                        tool helps convert between different measurement units.
                                                    </p>
                                                    <button type="button" @click="enableConversion()"
                                                        class="w-full bg-[#7F5539] text-white px-3 py-2 rounded hover:bg-[#4A2C1D] text-sm font-medium transition-colors">
                                                        Show Conversion Tools
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
        
                                        <div x-show="showConversionSection" x-transition
                                            class="p-4 border-2 border-[#7F5539] rounded bg-white space-y-4">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                                                <div>
                                                    <label class="block text-sm font-medium text-[#4A2C1D]">Unit Conversion Factor</label>
                                                    <input type="number" name="unit_conversion" step="0.0001"
                                                        min="0.0001" x-model.number="unitConversionValue"
                                                        class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1"
                                                        placeholder="e.g., 2.5 for converting 2.5 cups to 1 unit">
                                                </div>
                                                <div class="relative">
                                                    <label class="block text-sm font-medium text-[#4A2C1D]">Target Unit</label>
                                                    <input type="hidden" name="converted_unit" x-model="convertTarget">
                                                    <button @click="openTarget=!openTarget" @click.away="openTarget=false"
                                                        type="button"
                                                        class="w-full flex justify-between items-center border-2 border-[#7F5539] rounded px-3 py-2 mt-1 bg-white text-left">
                                                        <span x-text="selectedTargetName"
                                                            :class="{ 'text-gray-500': !selectedTarget }"></span>
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                            class="w-4 h-4 transition-transform duration-200 text-gray-500"
                                                            :class="{ 'rotate-180': openTarget }">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                        </svg>
                                                    </button>
                                                    <div x-show="openTarget" x-transition
                                                        class="absolute mt-1 w-full bg-white rounded-md shadow-lg z-20 border border-gray-200 max-h-60 overflow-y-auto"
                                                        style="display:none;">
                                                        <template x-for="u in units" :key="u">
                                                            <a href="#" @click.prevent="selectTarget(u)"
                                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                                x-text="u"></a>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                            <div x-show="converted.enabled" x-transition
                                                class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-[#4A2C1D]">Converted Stock Quantity</label>
                                                    <input type="text" name="converted_stock_quantity_in"
                                                        x-model="converted.quantity"
                                                        class="w-full border-2 border-[#7F5539] rounded px-3 py-2 bg-gray-100"
                                                        readonly>
                                                    <p class="text-sm text-gray-600 mt-1">(in <span x-text="converted.unit"></span>)</p>
                                                </div>
                                            </div>
                                            <div class="flex justify-end gap-2 mt-4">
                                                <button type="button"
                                                    class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300"
                                                    @click="cancelConversionSection()">Cancel</button>
                                                <button type="button"
                                                    class="bg-[#7F5539] text-white px-4 py-2 rounded hover:bg-[#4A2C1D]"
                                                    @click="performConversion()">Convert</button>
                                            </div>
                                            <input type="hidden" name="is_converted" x-model="converted.enabled">
                                        </div>
                                    </div>
                                </div>
        
                            </div>
                            {{-- END RIGHT SIDE --}}
                        </div>
        
                        <!-- Form Actions -->
                        <div class="mt-6 flex justify-end space-x-3 border-t pt-4">
                            <button type="button" @click="closeAddModal()"
                                class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539]">
                                Cancel
                            </button>
                            <button type="submit" :disabled="isSubmitting"
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539] disabled:opacity-50">
                                <span x-text="isSubmitting ? 'Adding...' : 'Add Ingredient'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Ingredient Modal -->
        <div x-show="showEditModal" x-cloak x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 z-[9999] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeEditModal()"></div>
        
                <div
                    class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle w-full max-w-5xl">
                    <!-- Header -->
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Edit Ingredient</h3>
                        <button @click="closeEditModal()" type="button"
                            class="text-gray-400 hover:text-gray-500 focus:outline-none">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
        
                    <!-- Form -->
                    <template x-if="editIngredientData">
                        <form id="editIngredientForm" @submit.prevent="submitEditForm" enctype="multipart/form-data">
                            @csrf
                            @method('PATCH')
        
                            {{-- Two-column layout --}}
                            <div class="flex flex-col sm:flex-row gap-6" style="max-height: 70vh;">
        
                                {{-- LEFT SIDE: Image --}}
                                <div class="flex flex-col sm:w-80 w-full flex-shrink-0">
                                    <label class="block text-sm font-medium text-[#4A2C1D] mb-2">Ingredient Image</label>
                                    <img id="editIngredientPreview"
                                        :src="editIngredientData?.ingredient_img ?
                                            `/storage/app/public/${editIngredientData.ingredient_img}` :
                                            `https://ui-avatars.com/api/?name=${encodeURIComponent(editIngredientData?.ingredient_name || 'ingredient')}&background=7F5539&color=FFFFFF`"
                                        alt="Ingredient Preview"
                                        class="w-full flex-1 min-h-[220px] rounded-lg object-cover border-2 border-[#7F5539]">
                                    <input id="editIngredientImg" type="file" name="ingredient_img"
                                        @change="handleEditImagePreview"
                                        class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-2 text-sm" accept="image/jpeg,image/png">
                                    <p class="text-xs text-gray-500 mt-1">Only JPEG and PNG files are accepted.</p>
                                </div>
        
                                {{-- RIGHT SIDE: Details --}}
                                <div class="flex-1 overflow-y-auto pr-2 space-y-4">
        
                                    {{-- Branch Name --}}
                                    <div x-data="{
                                        open: false,
                                        selectedBranchId: editIngredientData?.branch_id || '',
                                        get selectedBranchName() {
                                            if (!this.selectedBranchId) return 'Select a branch';
                                            const branches = {{ Js::from($branches->map->only(['id', 'branch_name'])) }};
                                            const branch = branches.find(b => b.id == this.selectedBranchId);
                                            return branch ? branch.branch_name : 'Select a branch';
                                        },
                                        selectBranch(id) {
                                            this.selectedBranchId = id;
                                            this.open = false;
                                        }
                                    }" x-init="$watch('editIngredientData.branch_id', value => selectedBranchId = value)" class="relative">
                                        <label class="block text-sm font-medium text-[#4A2C1D]">Assign Branch</label>
                                        <input type="hidden" name="branch_id" x-model="selectedBranchId">
                                        <button @click="open=!open" @click.away="open=false" type="button"
                                            class="w-full flex justify-between items-center border-2 border-[#7F5539] rounded px-3 py-2 mt-1 text-left bg-white">
                                            <span x-text="selectedBranchName"
                                                :class="{ 'text-gray-500': !selectedBranchId }"></span>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor"
                                                class="w-4 h-4 transition-transform duration-200 text-gray-500"
                                                :class="{ 'rotate-180': open }">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m19.5 8.25-7.5 7.5-7.5-7.5" />
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
        
                                    {{-- Ingredient Type --}}
                                    <div>
                                        <label class="block text-sm font-medium text-[#4A2C1D]">Ingredient Type</label>
                                        <input type="text" name="ingredient_type"
                                            x-model="editIngredientData.ingredient_type"
                                            class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1"
                                            placeholder="e.g., Coffee, Biscuit, Cup of Noodle, Water">
                                    </div>
        
                                    {{-- Ingredient Name --}}
                                    <div>
                                        <label class="block text-sm font-medium text-[#4A2C1D]">Ingredient Name</label>
                                        <input type="text" name="ingredient_name"
                                            x-model="editIngredientData.ingredient_name" required
                                            class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1">
                                    </div>
        
                                    {{-- Threshold & Conversion Section --}}
                                    <div x-data="editConversionData()" x-init="if (editIngredientData) initEditData()">
                                        {{-- Threshold | Unit --}}
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-[#4A2C1D]">Quantity Threshold</label>
                                                <input type="number" name="stock_quantity_threshold" min="0"
                                                    x-model="editIngredientData.stock_quantity_threshold"
                                                    class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1">
                                            </div>
        
                                            <div class="relative">
                                                <label class="block text-sm font-medium text-[#4A2C1D]">Unit</label>
                                                <input type="hidden" name="unit" x-model="selectedUnit">
                                                <button @click="open=!open" @click.away="open=false" type="button"
                                                    class="w-full flex justify-between items-center border-2 border-[#7F5539] rounded px-3 py-2 mt-1 bg-white text-left">
                                                    <span x-text="selectedUnitName"
                                                        :class="{ 'text-gray-500': !selectedUnitInner }"></span>
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        class="w-4 h-4 transition-transform duration-200 text-gray-500"
                                                        :class="{ 'rotate-180': open }">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                    </svg>
                                                </button>
                                                <div x-show="open" x-transition
                                                    class="absolute mt-1 w-full bg-white rounded-md shadow-lg z-20 border border-gray-200 max-h-60 overflow-y-auto"
                                                    style="display:none;">
                                                    <template x-for="u in units" :key="u">
                                                        <a href="#" @click.prevent="selectUnit(u)"
                                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                            x-text="u"></a>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
        
                                        {{-- Convert to Base Unit Section --}}
                                        <div class="mt-4 relative">
                                            <div class="flex items-center justify-between mb-2">
                                                <h3 class="text-[#4A2C1D] font-semibold">Convert to Base Unit</h3>
                                                <div class="relative">
                                                    <button type="button" @click="showInfo()"
                                                        class="w-6 h-6 flex items-center justify-center bg-gray-200 text-gray-700 rounded-full hover:bg-gray-300 text-sm font-bold transition-colors">
                                                        ?
                                                    </button>
                                                    <div x-show="showInfoBox" x-transition @click.away="showInfoBox = false"
                                                        class="absolute right-0 bottom-0 -translate-y-full mt-2 w-80 bg-white border border-[#7F5539] rounded-lg shadow-lg z-30 p-4"
                                                        style="display: none;">
                                                        <div class="flex justify-between items-start mb-2">
                                                            <h4 class="font-semibold text-[#4A2C1D]">Unit Conversion Info</h4>
                                                            <button type="button" @click="showInfoBox = false"
                                                                class="text-gray-500 hover:text-gray-700">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                        <p class="text-sm text-gray-600 mb-4">
                                                            Is this an ingredient that needs conversion to standard units? This
                                                            tool helps convert between different measurement units.
                                                        </p>
                                                        <button type="button" @click="enableConversion()"
                                                            class="w-full bg-[#7F5539] text-white px-3 py-2 rounded hover:bg-[#4A2C1D] text-sm font-medium transition-colors">
                                                            Show Conversion Tools
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
        
                                            <div x-show="showConversionSection" x-transition
                                                class="p-4 border-2 border-[#7F5539] rounded bg-white space-y-4">
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                                                    <div>
                                                        <label class="block text-sm font-medium text-[#4A2C1D]">Unit Conversion Factor</label>
                                                        <input type="number" name="unit_conversion" step="0.0001"
                                                            min="0.0001" x-model.number="unitConversionValue"
                                                            class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1"
                                                            placeholder="e.g., 2.5 for converting 2.5 cups to 1 unit">
                                                    </div>
                                                    <div class="relative">
                                                        <label class="block text-sm font-medium text-[#4A2C1D]">Target Unit</label>
                                                        <input type="hidden" name="converted_unit" x-model="convertTarget">
                                                        <button @click="openTarget=!openTarget" @click.away="openTarget=false"
                                                            type="button"
                                                            class="w-full flex justify-between items-center border-2 border-[#7F5539] rounded px-3 py-2 mt-1 bg-white text-left">
                                                            <span x-text="selectedTargetName"
                                                                :class="{ 'text-gray-500': !selectedTarget }"></span>
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                                class="w-4 h-4 transition-transform duration-200 text-gray-500"
                                                                :class="{ 'rotate-180': openTarget }">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                            </svg>
                                                        </button>
                                                        <div x-show="openTarget" x-transition
                                                            class="absolute mt-1 w-full bg-white rounded-md shadow-lg z-20 border border-gray-200 max-h-60 overflow-y-auto"
                                                            style="display:none;">
                                                            <template x-for="u in units" :key="u">
                                                                <a href="#" @click.prevent="selectTarget(u)"
                                                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                                    x-text="u"></a>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div x-show="converted.enabled" x-transition
                                                    class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                                    <div>
                                                        <label class="block text-sm font-medium text-[#4A2C1D]">Converted Stock Quantity</label>
                                                        <input type="text" name="converted_stock_quantity_in"
                                                            x-model="converted.quantity"
                                                            class="w-full border-2 border-[#7F5539] rounded px-3 py-2 bg-gray-100"
                                                            readonly>
                                                        <p class="text-sm text-gray-600 mt-1">(in <span x-text="converted.unit"></span>)</p>
                                                    </div>
                                                </div>
                                                <div class="flex justify-end gap-2 mt-4">
                                                    <button type="button"
                                                        class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300"
                                                        @click="cancelConversionSection()">Cancel</button>
                                                    <button type="button"
                                                        class="bg-[#7F5539] text-white px-4 py-2 rounded hover:bg-[#4A2C1D]"
                                                        @click="performConversion()">Convert</button>
                                                </div>
                                                <input type="hidden" name="is_converted" x-model="converted.enabled">
                                            </div>
                                        </div>
                                    </div>
        
                                </div>
                                {{-- END RIGHT SIDE --}}
                            </div>
        
                            <!-- Form Actions -->
                            <div class="mt-6 flex justify-end space-x-3 border-t pt-4">
                                <button type="button" @click="closeEditModal()"
                                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539]">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="isSubmitting"
                                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539] disabled:opacity-50">
                                    <span x-text="isSubmitting ? 'Updating...' : 'Update Ingredient'"></span>
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
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showArchiveModal = false; removeBodyClass();"></div>

                <!-- This element is to trick the browser into centering the modal contents. -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

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
                                    Archive <strong class="text-[#4A2C1D]"
                                        x-text="selectedIngredient?.ingredient_name"></strong>?
                                </p>

                                <div class="mt-4 w-full text-left space-y-2">
                                    <div>
                                        <label for="stock_quantity_out"
                                            class="block text-sm font-medium text-[#4A2C1D]">Quantity Out
                                            (Optional)</label>
                                        <input type="number" name="stock_quantity_out" id="stock_quantity_out"
                                            min="0" :max="selectedIngredient?.stock_quantity_in"
                                            placeholder="e.g., 5"
                                            class="block w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1">
                                    </div>
                                    <div>
                                        <label for="reasons" class="block text-sm font-medium text-[#4A2C1D]">Reason
                                            (Optional)</label>
                                        <textarea name="reasons" id="reasons" rows="2" placeholder="e.g., Damaged goods"
                                            class="block w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 flex space-x-3">
                        <button type="button" @click="showArchiveModal = false; removeBodyClass();"
                            class="flex-1 inline-flex justify-center px-4 py-2 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            Cancel
                        </button>
                        <button type="button" @click="confirmArchive()"
                            class="flex-1 inline-flex justify-center px-4 py-2 border border-transparent text-base font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                            Confirm
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
                    branch_id: '{{ request('branch_id', '') }}',
                    ingredient_type: '{{ request('ingredient_type', '') }}',
                    ingredient_status: '{{ request('ingredient_status', '') }}',
                    stock_level: '{{ request('stock_level', '') }}',
                },
                availableBranches: @json($branches ?? []),
                availableTypes: @json($ingredientTypes ?? []),
                clearFilters() {
                    this.filters = {
                        branch_id: '',
                        ingredient_type: '',
                        ingredient_status: '',
                        stock_level: '',
                    };
                },
                applyFilters() {
                    const mainComponent = Alpine.$data(document.querySelector(
                        '[x-data="ingredientData()"]'));
                    const newFilters = {
                        ...mainComponent.currentFilters,
                        ...this.filters,
                        search: mainComponent.searchQuery
                    };
                    mainComponent.applyFilters(newFilters);
                    mainComponent.removeBodyClass(); // Add this line
                }
            }));

            Alpine.data('addConversionData', () => ({
                selectedUnitInner: '',
                selectedUnit: '',
                unitConversionValue: '',
                convertTarget: '', // This should be defined here
                selectedTarget: '', // Add this for the template reference
                converted: {
                    enabled: false,
                    quantity: '',
                    unit: ''
                },
                showConversionSection: false,
                showInfoBox: false,
                open: false,
                openTarget: false,
                conversionTable: {
                    'g': 1,
                    'kg': 1000,
                    'mg': 0.001,
                    'lb': 453.592,
                    'oz': 28.3495,
                    'ml': 1,
                    'l': 1000,
                    'cup': 240,
                    'tbsp': 15,
                    'tsp': 5,
                    'pcs': 1,
                    'pack': 1
                },
                units: ['g', 'kg', 'mg', 'lb', 'oz', 'ml', 'l', 'cup', 'tbsp', 'tsp', 'pcs', 'pack'],

                get selectedUnitName() {
                    return this.selectedUnitInner || 'Select a unit';
                },
                get selectedTargetName() {
                    return this.convertTarget || 'Select unit';
                },

                selectUnit(u) {
                    this.selectedUnitInner = u;
                    this.selectedUnit = u;
                    this.open = false;
                },

                selectTarget(u) {
                    this.convertTarget = u;
                    this.openTarget = false;
                },

                performConversion() {
                    if (!this.unitConversionValue || !this.convertTarget) return;
                    let factor = this.conversionTable[this.convertTarget] || 1;
                    const baseQty = this.unitConversionValue * (this.conversionTable[this.selectedUnitInner] || 1);
                    const newQty = baseQty / factor;
                    this.converted = {
                        enabled: true,
                        unit: this.convertTarget,
                        quantity: Math.round(newQty)
                    };
                },

                cancelConversion() {
                    this.converted = {
                        enabled: false,
                        quantity: '',
                        unit: ''
                    };
                    this.unitConversionValue = '';
                    this.convertTarget = '';
                },

                showInfo() {
                    this.showInfoBox = !this.showInfoBox;
                },

                enableConversion() {
                    this.showConversionSection = true;
                    this.showInfoBox = false;
                },

                cancelConversionSection() {
                    this.showConversionSection = false;
                    this.converted = {
                        enabled: false,
                        quantity: '',
                        unit: ''
                    };
                    this.unitConversionValue = '';
                    this.convertTarget = '';
                }
            }));

            Alpine.data('editConversionData', () => ({
                selectedUnitInner: '',
                selectedUnit: '',
                unitConversionValue: '',
                convertTarget: '',
                selectedTarget: '',
                converted: {
                    enabled: false,
                    quantity: '',
                    unit: ''
                },
                showConversionSection: false,
                showInfoBox: false,
                open: false,
                openTarget: false,
                conversionTable: {
                    'g': 1,
                    'kg': 1000,
                    'mg': 0.001,
                    'lb': 453.592,
                    'oz': 28.3495,
                    'ml': 1,
                    'l': 1000,
                    'cup': 240,
                    'tbsp': 15,
                    'tsp': 5,
                    'pcs': 1,
                    'pack': 1
                },
                units: ['g', 'kg', 'mg', 'lb', 'oz', 'ml', 'l', 'cup', 'tbsp', 'tsp', 'pcs', 'pack'],

                get selectedUnitName() {
                    return this.selectedUnitInner || 'Select a unit';
                },
                get selectedTargetName() {
                    return this.convertTarget || 'Select unit';
                },

                initEditData() {
                    const mainComponent = Alpine.$data(document.querySelector(
                        '[x-data="ingredientData()"]'));
                    // Check if editIngredientData exists
                    if (mainComponent.editIngredientData) {
                        this.selectedUnitInner = mainComponent.editIngredientData.unit || '';
                        this.selectedUnit = mainComponent.editIngredientData.unit || '';
                        this.unitConversionValue = mainComponent.editIngredientData.unit_conversion ||
                            '';
                        this.convertTarget = mainComponent.editIngredientData.converted_unit || '';

                        if (mainComponent.editIngredientData.converted_stock_quantity_in &&
                            mainComponent
                            .editIngredientData.converted_unit) {
                            this.converted = {
                                enabled: true,
                                quantity: mainComponent.editIngredientData
                                    .converted_stock_quantity_in,
                                unit: mainComponent.editIngredientData.converted_unit
                            };
                            this.showConversionSection = true;
                        }
                    }
                },

                selectUnit(u) {
                    this.selectedUnitInner = u;
                    this.selectedUnit = u;
                    this.open = false;
                },

                selectTarget(u) {
                    this.convertTarget = u;
                    this.openTarget = false;
                },

                performConversion() {
                    if (!this.unitConversionValue || !this.convertTarget) return;
                    let factor = this.conversionTable[this.convertTarget] || 1;
                    const baseQty = this.unitConversionValue * (this.conversionTable[this.selectedUnitInner] || 1);
                    const newQty = baseQty / factor;
                    this.converted = {
                        enabled: true,
                        unit: this.convertTarget,
                        quantity: Math.round(newQty)
                    };
                },

                cancelConversion() {
                    this.converted = {
                        enabled: false,
                        quantity: '',
                        unit: ''
                    };
                    this.unitConversionValue = '';
                    this.convertTarget = '';
                },

                showInfo() {
                    this.showInfoBox = !this.showInfoBox;
                },

                enableConversion() {
                    this.showConversionSection = true;
                    this.showInfoBox = false;
                },

                cancelConversionSection() {
                    this.showConversionSection = false;
                    this.converted = {
                        enabled: false,
                        quantity: '',
                        unit: ''
                    };
                    this.unitConversionValue = '';
                    this.convertTarget = '';
                }
            }));

            // Main component
            Alpine.data('ingredientData', () => ({
                // Initial state
                ingredients: @json($ingredients->items() ?? []),
                pagination: @json($ingredients->toArray()),
                stats: @json($stats),
                ingredientTypes: @json($ingredientTypes ?? []),
                branches: @json($branches ?? []),
                currentFilters: {
                    branch_id: '{{ request('branch_id', '') }}',
                    ingredient_type: '{{ request('ingredient_type', '') }}',
                    ingredient_status: '{{ request('ingredient_status', '') }}',
                    stock_level: '{{ request('stock_level', '') }}',
                    search: '{{ request('search', '') }}',
                },
                addFormData: {
                    ingredient_type: '',
                    ingredient_name: '',
                    unit: '',
                    stock_quantity_threshold: '',
                    unit_conversion: '',
                    converted_stock_quantity_in: '',
                    converted_unit: '',
                },
                searchQuery: '{{ request('search', '') }}',
                showFilters: false,
                showAddModal: false,
                showEditModal: false,
                showArchiveModal: false,
                selectedIngredient: null,
                editIngredientData: null,
                isSubmitting: false,
                statusOptions: [{
                        id: 1,
                        name: 'Available'
                    },
                    {
                        id: 0,
                        name: 'Unavailable'
                    }
                ],
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

                    if (this.currentFilters.branch_id) {
                        const branch = this.branches.find(b => b.id == this.currentFilters
                            .branch_id);
                        filters.push({
                            key: 'branch_id',
                            label: `Branch: ${branch?.branch_name || this.currentFilters.branch_id}`
                        });
                    }

                    if (this.currentFilters.ingredient_type) {
                        filters.push({
                            key: 'ingredient_type',
                            label: `Type: ${this.currentFilters.ingredient_type}`
                        });
                    }

                    if (this.currentFilters.ingredient_status) {
                        filters.push({
                            key: 'ingredient_status',
                            label: `Status: ${this.getStatusLabel(this.currentFilters.ingredient_status)}`
                        });
                    }

                    if (this.currentFilters.stock_level) {
                        filters.push({
                            key: 'stock_level',
                            label: `Stock: ${this.currentFilters.stock_level === 'low' ? 'Low Stock' : 'Normal Stock'}`
                        });
                    }

                    return filters;
                },

                // Utility functions
                formatNumber(number) {
                    return new Intl.NumberFormat().format(number);
                },

                getStatusLabel(status) {
                    switch (status) {
                        case '1':
                            return 'Available';
                        case '0':
                            return 'Unavailable';
                        default:
                            return 'Unknown';
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
                        this.showNotification('Failed to apply filters. Please try again.',
                            'error');
                    } finally {
                        this.isLoading = false;
                        this.removeBodyClass(); // Ensure body class is removed
                    }
                },

                async clearAllFilters() {
                    this.isLoading = true;
                    this.showFilters = false;
                    this.searchQuery = '';
                    this.currentFilters = {
                        branch_id: '',
                        ingredient_type: '',
                        ingredient_status: '',
                        stock_level: '',
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
                        this.showNotification('Failed to clear filters. Please try again.',
                            'error');
                    } finally {
                        this.isLoading = false;
                        this.removeBodyClass(); // Ensure body class is removed
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
                        ingredient_type: '',
                        ingredient_name: '',
                        unit: '',
                        stock_quantity_threshold: '',
                        unit_conversion: '',
                        converted_stock_quantity_in: '',
                        converted_unit: '',
                    };
                    const fileInput = document.getElementById('addIngredientImg');
                    if (fileInput) fileInput.value = '';
                    document.getElementById('addIngredientPreview').src =
                        'https://ui-avatars.com/api/?name=ingredient&background=7F5539&color=FFFFFF';
                },

                handleAddImagePreview(event) {
                    const file = event.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            document.getElementById('addIngredientPreview').src = event.target
                                .result;
                        }
                        reader.readAsDataURL(file);
                    }
                },

                async submitAddForm() {
                    if (this.isSubmitting) return;

                    this.isSubmitting = true;

                    try {
                        const formData = new FormData();
                        const form = document.getElementById('addIngredientForm');
                        const formElements = form.elements;

                        for (let element of formElements) {
                            if (element.name && element.type !== 'button') {
                                if (element.type === 'file') {
                                    if (element.files.length > 0) {
                                        formData.append(element.name, element.files[0]);
                                    }
                                } else if (element.type === 'checkbox') {
                                    formData.append(element.name, element.checked);
                                } else {
                                    formData.append(element.name, element.value);
                                }
                            }
                        }

                        formData.append('_token', '{{ csrf_token() }}');

                        const response = await fetch(
                            '{{ route('sub_one.ingredients.storeIngredientAjax') }}', {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });

                        const data = await response.json();

                        if (data.success) {
                            // Close the modal first
                            this.closeAddModal();

                            // IMPORTANT: Refresh the table data from server
                            await this.applyFilters(this.currentFilters);

                            // No alert - just silently update the table
                        } else {
                            throw new Error(data.message || 'Failed to create ingredient');
                        }
                    } catch (error) {
                        // You could add a subtle error indicator here if needed
                        alert('Failed to create ingredient. Please try again.');
                    } finally {
                        this.isSubmitting = false;
                    }
                },

                // Edit Modal functions
                async openEditModal(ingredient) {
                    this.selectedIngredient = ingredient;

                    try {
                        const response = await fetch(
                            `{{ url('sub_one/ingredients') }}/${ingredient.uuid}/data`, {
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });

                        const data = await response.json();

                        if (data.success) {
                            this.editIngredientData = data.ingredient;
                            this.showEditModal = true;
                            this.addBodyClass();
                        } else {
                            throw new Error(data.message || 'Failed to load ingredient data');
                        }
                    } catch (error) {
                        this.showToast('Failed to load ingredient data. Please try again.',
                        'error');
                    }
                },

                closeEditModal() {
                    this.showEditModal = false;
                    this.editIngredientData = null;
                    this.removeBodyClass();

                    const fileInput = document.getElementById('editIngredientImg');
                    if (fileInput) fileInput.value = '';
                },

                handleEditImagePreview(event) {
                    const file = event.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            document.getElementById('editIngredientPreview').src = event.target
                                .result;
                        }
                        reader.readAsDataURL(file);
                    }
                },

                async submitEditForm() {
                    if (this.isSubmitting || !this.editIngredientData) return;

                    this.isSubmitting = true;

                    try {
                        const formData = new FormData();
                        const form = document.getElementById('editIngredientForm');
                        const formElements = form.elements;

                        for (let element of formElements) {
                            if (element.name && element.type !== 'button') {
                                if (element.type === 'file') {
                                    if (element.files.length > 0) {
                                        formData.append(element.name, element.files[0]);
                                    }
                                } else if (element.type === 'checkbox') {
                                    formData.append(element.name, element.checked);
                                } else {
                                    formData.append(element.name, element.value);
                                }
                            }
                        }

                        formData.append('_token', '{{ csrf_token() }}');
                        formData.append('_method', 'PATCH');

                        const response = await fetch(
                            `{{ url('sub_one/ingredients/ajax') }}/${this.editIngredientData.uuid}/update`, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });

                        const data = await response.json();

                        if (data.success) {
                            // Close the modal
                            this.closeEditModal();

                            // IMPORTANT: Refresh the table data from server
                            await this.applyFilters(this.currentFilters);

                            // No alert - just silently update the table
                        } else {
                            throw new Error(data.message || 'Failed to update ingredient');
                        }
                    } catch (error) {
                        alert('Failed to update ingredient. Please try again.');
                    } finally {
                        this.isSubmitting = false;
                    }
                },

                // Archive modal methods
                openArchiveModal(ingredient) {
                    this.selectedIngredient = ingredient;
                    this.showArchiveModal = true;
                    this.addBodyClass(); // Add this for archive modal too
                },

                confirmArchive() {
                    if (!this.selectedIngredient) return;

                    const quantityInput = document.getElementById('stock_quantity_out');
                    const reasonInput = document.getElementById('reasons');

                    const quantityOut = quantityInput ? quantityInput.value : 0;
                    const reason = reasonInput ? reasonInput.value : '';

                    // Determine which endpoint to call based on whether damage data is provided
                    let actionUrl;
                    if (quantityOut > 0) {
                        // Call damage endpoint
                        actionUrl =
                            `{{ url('sub_one/ingredients/damage') }}/${this.selectedIngredient.uuid}`;
                    } else {
                        // Call regular deactivate/archive endpoint
                        actionUrl =
                            `{{ url('sub_one/ingredients/deactivate') }}/${this.selectedIngredient.uuid}`;
                    }

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = actionUrl;

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

                    // Add quantity out and reason only for damage endpoint
                    if (quantityOut > 0) {
                        const quantityField = document.createElement('input');
                        quantityField.type = 'hidden';
                        quantityField.name = 'stock_quantity_out';
                        quantityField.value = quantityOut;
                        form.appendChild(quantityField);

                        if (reason) {
                            const reasonField = document.createElement('input');
                            reasonField.type = 'hidden';
                            reasonField.name = 'reasons';
                            reasonField.value = reason;
                            form.appendChild(reasonField);
                        }
                    }

                    document.body.appendChild(form);
                    form.submit();
                },

                // Filter modal methods
                closeFilterModal() {
                    this.showFilters = false;
                    this.removeBodyClass();
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

        /* Fix for modal content overflow */
        [x-show] .overflow-y-auto {
            max-height: 70vh;
            overflow-y: auto;
        }

        /* Custom scrollbar styling */
        .overflow-y-auto::-webkit-scrollbar {
            width: 8px;
        }

        .overflow-y-auto::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .overflow-y-auto::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .overflow-y-auto::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Better responsive grid for modals */
        @media (max-width: 768px) {
            .grid-cols-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection