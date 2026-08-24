@extends('layouts.app')

@section('content')
<style>
    [x-cloak] { display: none !important; }
    
    .type-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
        font-size: 0.65rem;
        font-weight: 500;
    }
    
    .type-badge.product {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .type-badge.service {
        background: #fce7f3;
        color: #9d174d;
    }
    
    .type-badge.discount_fixed {
        background: #d1fae5;
        color: #065f46;
    }
    
    .type-badge.discount_percentage {
        background: #fed7aa;
        color: #9a3412;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
        font-size: 0.65rem;
        font-weight: 500;
    }
    
    .status-badge.active {
        background: #dcfce7;
        color: #166534;
    }
    
    .status-badge.inactive {
        background: #f3f4f6;
        color: #6b7280;
    }
    
    .status-badge.in-use {
        background: #e0e7ff;
        color: #3730a3;
    }
</style>

<!-- Header -->
<h1 class="text-2xl font-bold text-gray-900 mt-4 mb-8 text-center">Redeemable Items</h1>

<div x-data="redeemableItemData()" x-init="init()" class="p-4">
    <!-- Main Content -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <!-- Table Header -->
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-4 space-y-4 lg:space-y-0">
                <!-- Left: Header + Add Button -->
                <div class="flex items-center justify-between w-full lg:w-auto lg:justify-start">
                    <h2 class="text-lg font-semibold text-gray-900 mr-4">Redeemable Items</h2>
                    
                    <button @click="openAddModal()"
                        class="lg:hidden inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] flex-shrink-0">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Add
                    </button>
                </div>

                <!-- Right: Search + Filter + Add Button -->
                <div class="flex items-center space-x-3 w-full lg:w-auto">
                    <!-- Search Input -->
                    <div class="relative flex-1">
                        <input type="text" x-model="searchQuery" @input.debounce.500ms="performSearch()"
                            placeholder="Search items..."
                            class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] w-full">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Filter and Add Buttons -->
                    <div class="flex items-center space-x-3 flex-shrink-0">
                        <button @click="showFilters = true; addBodyClass()"
                            class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                            </svg>
                        </button>

                        <button @click="openAddModal()"
                            class="hidden lg:inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Add Item
                        </button>
                    </div>
                </div>
            </div>

            <!-- Active Filters Badge -->
            <div x-show="hasActiveFilters" class="flex items-center justify-end space-x-2 mt-3">
                <template x-for="filter in activeFilters" :key="filter.key">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-[#4A2C1D]/10 text-[#7F5539]">
                        <span x-text="filter.label"></span>
                        <button @click="removeFilter(filter.key)" class="ml-1 hover:text-[#4A2C1D]">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="item in items" :key="item.id">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <!-- Item Name -->
                            <td class="px-6 py-4">
                                <div>
                                    <div class="text-sm font-medium text-gray-900" x-text="item.item_name"></div>
                                    <div class="text-xs text-gray-500" x-text="item.item_description || ''"></div>
                                </div>
                            </td>
                            
                            <!-- Type -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="type-badge" :class="item.item_type">
                                    <span x-text="getTypeLabel(item.item_type)"></span>
                                </span>
                            </td>
                            
                            <!-- Value -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <template x-if="item.item_type === 'discount_percentage'">
                                        <span x-text="item.discount_percentage + '%'"></span>
                                    </template>
                                    <template x-if="item.item_type === 'discount_fixed'">
                                        <span x-text="formatCurrency(item.monetary_value)"></span>
                                    </template>
                                    <template x-if="item.item_type === 'product' || item.item_type === 'service'">
                                        <span x-text="formatCurrency(item.monetary_value)"></span>
                                    </template>
                                    <template x-if="!item.monetary_value && item.item_type !== 'discount_percentage'">
                                        <span class="text-gray-400">N/A</span>
                                    </template>
                                </div>
                            </td>
                            
                            <!-- Branch -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900" x-text="item.branch?.branch_name || 'All Branches'"></div>
                            </td>
                            
                            <!-- Category -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900" x-text="item.category || 'N/A'"></div>
                            </td>
                            
                            <!-- Status -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="status-badge" :class="item.active ? 'active' : 'inactive'">
                                    <span x-text="item.active ? 'Active' : 'Inactive'"></span>
                                </span>
                                <span x-show="item.is_in_use" class="status-badge in-use ml-1">
                                    In Use
                                </span>
                            </td>
                            
                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex justify-center space-x-2">
                                    <button @click="openEditModal(item)"
                                        class="px-3 py-1 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm font-medium">
                                        Edit
                                    </button>
                                    
                                    <button @click="toggleStatus(item)"
                                        class="px-3 py-1 rounded-lg transition-colors text-sm font-medium"
                                        :class="item.active ? 'bg-yellow-500 text-white hover:bg-yellow-600' : 'bg-green-500 text-white hover:bg-green-600'"
                                        x-text="item.active ? 'Deactivate' : 'Activate'">
                                    </button>
                                    
                                    <button @click="deleteItem(item)" x-show="!item.is_in_use"
                                        class="px-3 py-1 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors text-sm font-medium">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty State -->
                    <tr x-show="!items.length">
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="text-gray-400">
                                <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                                <h5 class="text-sm font-medium text-gray-900" x-text="hasActiveFilters ? 'No items match your filters' : 'No redeemable items found'"></h5>
                                <p class="text-sm text-gray-500" x-text="hasActiveFilters ? 'Try adjusting your filters.' : 'Create your first redeemable item to get started.'"></p>
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
                            :class="page === pagination.current_page ? 'border-2 border-[#4A2C1D] bg-[#7F5539]/80 text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-50'"
                            :disabled="page === '...'" x-text="page"></button>
                    </template>
                    <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page"
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
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showFilters = false"></div>
            <div class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl sm:my-8 sm:align-middle max-w-md w-full sm:p-6">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Filter Items</h3>
                    <div class="space-y-4">
                        <!-- Item Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Item Type</label>
                            <select x-model="filters.item_type" class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                <option value="">All Types</option>
                                <option value="product">Product</option>
                                <option value="service">Service</option>
                                <option value="discount_fixed">Fixed Discount</option>
                                <option value="discount_percentage">Percentage Discount</option>
                            </select>
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <select x-model="filters.category" class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                <option value="">All Categories</option>
                                <template x-for="category in categories" :key="category">
                                    <option :value="category" x-text="category.charAt(0).toUpperCase() + category.slice(1)"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Branch -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                            <select x-model="filters.branch_id" class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                <option value="">All Branches</option>
                                <template x-for="branch in branches" :key="branch.id">
                                    <option :value="branch.id" x-text="branch.branch_name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select x-model="filters.active" class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                <option value="">All Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 flex space-x-3">
                        <button @click="clearFilters()" class="flex-1 inline-flex justify-center px-4 py-2 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539]">
                            Clear
                        </button>
                        <button @click="applyFilters()" class="flex-1 inline-flex justify-center px-4 py-2 border border-transparent text-base font-medium rounded-lg text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539]">
                            Apply Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div x-show="showItemModal" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showItemModal = false"></div>

            <div class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div>
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full" :class="isEditing ? 'bg-blue-100' : 'bg-green-100'">
                        <svg class="h-6 w-6" :class="isEditing ? 'text-blue-600' : 'text-green-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-5">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" x-text="isEditing ? 'Edit Redeemable Item' : 'Add New Redeemable Item'"></h3>
                        <div class="mt-4 text-left space-y-4">
                            <!-- Item Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Item Name *</label>
                                <input type="text" x-model="itemForm.item_name" class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2" placeholder="Enter item name">
                            </div>

                            <!-- Item Type -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Item Type *</label>
                                <select x-model="itemForm.item_type" @change="onItemTypeChange()" class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                    <option value="product">Product</option>
                                    <option value="service">Service</option>
                                    <option value="discount_fixed">Fixed Discount</option>
                                    <option value="discount_percentage">Percentage Discount</option>
                                </select>
                            </div>

                            <!-- Monetary Value (for product/service/fixed discount) -->
                            <div x-show="showMonetaryValue">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <span x-text="itemForm.item_type === 'discount_fixed' ? 'Discount Amount *' : 'Monetary Value *'"></span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">₱</span>
                                    <input type="number" x-model="itemForm.monetary_value" step="0.01" min="0"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 pl-8 pr-3 py-2"
                                        :placeholder="itemForm.item_type === 'discount_fixed' ? 'Enter discount amount' : 'Enter monetary value'">
                                </div>
                            </div>

                            <!-- Discount Percentage -->
                            <div x-show="itemForm.item_type === 'discount_percentage'">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Discount Percentage *</label>
                                <div class="relative">
                                    <input type="number" x-model="itemForm.discount_percentage" step="0.01" min="0" max="100"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2"
                                        placeholder="Enter discount percentage">
                                    <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500">%</span>
                                </div>
                            </div>

                            <!-- Item Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <textarea x-model="itemForm.item_description" rows="2" class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2" placeholder="Item description"></textarea>
                            </div>

                            <!-- Category -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                <input type="text" x-model="itemForm.category" class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2" placeholder="e.g., Coffee, Pastry, Beverage">
                            </div>

                            <!-- Branch -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                                <select x-model="itemForm.branch_id" class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                    <option value="">All Branches</option>
                                    <template x-for="branch in branches" :key="branch.id">
                                        <option :value="branch.id" x-text="branch.branch_name"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Active Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select x-model="itemForm.active" class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                    <option :value="1">Active</option>
                                    <option :value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-6 flex space-x-3">
                    <button type="button" @click="showItemModal = false" class="flex-1 inline-flex justify-center px-4 py-2 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </button>
                    <button type="button" @click="submitItemForm()" class="flex-1 inline-flex justify-center px-4 py-2 border border-transparent text-base font-medium rounded-lg text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539]">
                        <span x-text="isEditing ? 'Update' : 'Create'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('redeemableItemData', () => ({
        // State
        items: @json($redeemableItems->items() ?? []),
        pagination: @json($redeemableItems->toArray() ?? []),
        branches: @json($branches ?? []),
        categories: @json($categories ?? []),
        currentFilters: {
            item_type: '{{ request('item_type', '') }}',
            category: '{{ request('category', '') }}',
            branch_id: '{{ request('branch_id', '') }}',
            active: '{{ request('active', '') }}',
        },
        searchQuery: '{{ request('search', '') }}',
        showFilters: false,
        showItemModal: false,
        isEditing: false,
        currentItemId: null,
        itemForm: {
            branch_id: '',
            item_name: '',
            item_description: '',
            item_type: 'product',
            monetary_value: '',
            discount_percentage: '',
            category: '',
            active: 1,
        },
        paginationLinks: [],
        isLoading: false,

        init() {
            this.updatePaginationLinks();
            this.updateActiveFilters();
        },

        get hasActiveFilters() {
            return Object.values(this.currentFilters).some(value => value !== '') || this.searchQuery;
        },

        get activeFilters() {
            const filters = [];
            
            if (this.searchQuery) {
                filters.push({ key: 'search', label: `Search: ${this.searchQuery}` });
            }
            
            if (this.currentFilters.item_type) {
                filters.push({ key: 'item_type', label: `Type: ${this.getTypeLabel(this.currentFilters.item_type)}` });
            }
            
            if (this.currentFilters.category) {
                filters.push({ key: 'category', label: `Category: ${this.currentFilters.category}` });
            }
            
            if (this.currentFilters.branch_id) {
                const branch = this.branches.find(b => b.id == this.currentFilters.branch_id);
                if (branch) {
                    filters.push({ key: 'branch_id', label: `Branch: ${branch.branch_name}` });
                }
            }
            
            if (this.currentFilters.active !== '') {
                filters.push({ key: 'active', label: `Status: ${this.currentFilters.active == 1 ? 'Active' : 'Inactive'}` });
            }
            
            return filters;
        },

        get showMonetaryValue() {
            return ['product', 'service', 'discount_fixed'].includes(this.itemForm.item_type);
        },

        // Helper methods
        getTypeLabel(type) {
            const labels = {
                product: 'Product',
                service: 'Service',
                discount_fixed: 'Fixed Discount',
                discount_percentage: 'Percentage Discount'
            };
            return labels[type] || type;
        },

        formatCurrency(value) {
            if (!value) return '₱0.00';
            return '₱' + parseFloat(value).toFixed(2);
        },

        onItemTypeChange() {
            if (this.itemForm.item_type === 'discount_percentage') {
                this.itemForm.monetary_value = '';
            } else if (['product', 'service', 'discount_fixed'].includes(this.itemForm.item_type)) {
                this.itemForm.discount_percentage = '';
            }
        },

        // Search
        async performSearch() {
            this.currentFilters.search = this.searchQuery;
            await this.applyFilters(this.currentFilters);
        },

        // Filters
        async applyFilters(filters) {
            this.isLoading = true;
            this.showFilters = false;
            this.currentFilters = { ...filters };
            
            try {
                const queryParams = new URLSearchParams();
                Object.entries(this.currentFilters).forEach(([key, value]) => {
                    if (value) queryParams.append(key, value);
                });
                queryParams.append('ajax', 'true');
                
                const url = `?${queryParams.toString()}`;
                const response = await fetch(url, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                
                const data = await response.json();
                if (data.success) {
                    this.items = data.data;
                    this.pagination = data.pagination;
                    this.updatePaginationLinks();
                    this.updateActiveFilters();
                }
            } catch (error) {
                console.error('Error applying filters:', error);
                this.showNotification('Failed to apply filters', 'error');
            } finally {
                this.isLoading = false;
            }
        },

        async clearAllFilters() {
            this.isLoading = true;
            this.searchQuery = '';
            this.currentFilters = { item_type: '', category: '', branch_id: '', active: '' };
            
            try {
                const url = `?ajax=true`;
                const response = await fetch(url, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const data = await response.json();
                if (data.success) {
                    this.items = data.data;
                    this.pagination = data.pagination;
                    this.updatePaginationLinks();
                    this.updateActiveFilters();
                }
            } catch (error) {
                console.error('Error clearing filters:', error);
                this.showNotification('Failed to clear filters', 'error');
            } finally {
                this.isLoading = false;
            }
        },

        clearFilters() {
            this.filters = { item_type: '', category: '', branch_id: '', active: '' };
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
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const data = await response.json();
                if (data.success) {
                    this.items = data.data;
                    this.pagination = data.pagination;
                    this.updatePaginationLinks();
                }
            } catch (error) {
                console.error('Error changing page:', error);
                this.showNotification('Failed to change page', 'error');
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

        // Modal methods
        openAddModal() {
            this.isEditing = false;
            this.currentItemId = null;
            this.itemForm = {
                branch_id: '',
                item_name: '',
                item_description: '',
                item_type: 'product',
                monetary_value: '',
                discount_percentage: '',
                category: '',
                active: 1,
            };
            this.showItemModal = true;
            this.addBodyClass();
        },

        openEditModal(item) {
            this.isEditing = true;
            this.currentItemId = item.id;
            this.itemForm = {
                branch_id: item.branch_id || '',
                item_name: item.item_name,
                item_description: item.item_description || '',
                item_type: item.item_type,
                monetary_value: item.monetary_value || '',
                discount_percentage: item.discount_percentage || '',
                category: item.category || '',
                active: item.active,
            };
            this.showItemModal = true;
            this.addBodyClass();
        },

        // Submit form
        async submitItemForm() {
            // Validate
            if (!this.itemForm.item_name.trim()) {
                this.showNotification('Item name is required', 'error');
                return;
            }
            
            if (['product', 'service', 'discount_fixed'].includes(this.itemForm.item_type)) {
                if (!this.itemForm.monetary_value || parseFloat(this.itemForm.monetary_value) <= 0) {
                    this.showNotification(
                        this.itemForm.item_type === 'discount_fixed' 
                            ? 'Discount amount is required' 
                            : 'Monetary value is required',
                        'error'
                    );
                    return;
                }
            }
            
            if (this.itemForm.item_type === 'discount_percentage') {
                if (!this.itemForm.discount_percentage || parseFloat(this.itemForm.discount_percentage) <= 0) {
                    this.showNotification('Discount percentage is required', 'error');
                    return;
                }
            }
            
            try {
                const url = this.isEditing 
                    ? `/owner/redeemable-items/${this.currentItemId}` 
                    : '/owner/redeemable-items';
                const method = this.isEditing ? 'PATCH' : 'POST';
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.itemForm)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showItemModal = false;
                    this.removeBodyClass();
                    this.showNotification(
                        this.isEditing ? 'Item updated successfully!' : 'Item created successfully!',
                        'success'
                    );
                    await this.applyFilters(this.currentFilters);
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error('Error saving item:', error);
                this.showNotification(error.message || 'Failed to save item', 'error');
            }
        },

        // Toggle status
        async toggleStatus(item) {
            const newStatus = item.active ? 0 : 1;
            const action = newStatus ? 'activate' : 'deactivate';
            
            if (!confirm(`Are you sure you want to ${action} "${item.item_name}"?`)) {
                return;
            }
            
            try {
                const response = await fetch(`/owner/redeemable-items/${item.id}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ active: newStatus })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showNotification(`Item ${action}d successfully!`, 'success');
                    await this.applyFilters(this.currentFilters);
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error('Error toggling status:', error);
                this.showNotification(error.message || 'Failed to toggle status', 'error');
            }
        },

        // Delete item
        async deleteItem(item) {
            if (!confirm(`Are you sure you want to delete "${item.item_name}"? This action cannot be undone.`)) {
                return;
            }
            
            try {
                const response = await fetch(`/owner/redeemable-items/${item.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showNotification('Item deleted successfully!', 'success');
                    await this.applyFilters(this.currentFilters);
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error('Error deleting item:', error);
                this.showNotification(error.message || 'Failed to delete item', 'error');
            }
        },

        // Notification
        showNotification(message, type = 'success') {
            localStorage.setItem('toastType', type);
            localStorage.setItem('toastMessage', message);
            localStorage.setItem('toastStart', Date.now().toString());
            localStorage.setItem('toastDuration', '10000');
            localStorage.setItem('toastFade', '500');
            localStorage.setItem('toastActive', 'true');
            
            if (typeof window.showToastFromStorage === 'function') {
                window.showToastFromStorage();
            }
        },

        // Body class management
        addBodyClass() {
            document.body.classList.add('modal-open');
        },

        removeBodyClass() {
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.height = '';
        }
    }));
});
</script>
@endsection