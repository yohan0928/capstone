@extends('layouts.app')

@section('title', 'Order Lists')

@section('content')
    <!-- Header -->
    <h1 class="text-2xl font-bold text-gray-900 mt-4 mb-8 text-center">Order Lists</h1>

    <div x-data="orderHistoryData()" x-init="init()" class="p-4">
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-4 gap-6 mb-8">
            <!-- Total Orders -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Orders</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.total_orders"></p>
                    </div>
                    <div class="p-3 bg-[#4A2C1D]/10 rounded-lg">
                        <svg class="w-6 h-6 text-[#7F5539]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Completed Orders -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Completed Orders</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.completed_orders"></p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Table Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-4 space-y-4 lg:space-y-0">
                    <h2 class="text-lg font-semibold text-gray-900">Order Records</h2>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-3 w-full lg:w-auto">
                        <div class="flex flex-row items-center space-x-3 w-full justify-end">
                            <!-- Search Input -->
                            <div class="relative w-full sm:w-80">
                                <input type="text" x-model="searchQuery" @input.debounce.500ms="performSearch()"
                                    placeholder="Search by customer name or order ref..."
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
                </div>

                <!-- Active Filters Badge -->
                <div x-show="hasActiveFilters" class="flex items-center justify-end space-x-2">
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
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 z-30 bg-gray-50 shadow-right">
                                Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Order Ref No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date & Time</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total Amount</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Discount</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Discount Type</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Payment Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Order Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(order, index) in orders" :key="order.id">
                            <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-100'">
                                <td class="px-6 py-4 whitespace-nowrap sticky left-0 z-10 shadow-right"
                                    :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-100'">
                                    <div class="flex items-center">
                                        <div class="bg-[#4A2C1D]/10 rounded-lg p-2 mr-3">
                                            <svg class="w-4 h-4 text-[#7F5539]" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"
                                                x-text="order?.customer ? 
                                                    (order.customer.first_name && order.customer.last_name && order.customer.last_name.toLowerCase() !== 'customer' ? 
                                                        order.customer.first_name + ' ' + order.customer.last_name :
                                                        order.customer.first_name || (order.customer.last_name && order.customer.last_name.toLowerCase() !== 'customer' ? order.customer.last_name : '') || 'Walk-in Customer'
                                                    ) : 
                                                    'Walk-in Customer'">
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900" x-text="order.order_ref_no"></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" x-text="formatDate(order.date_created)"></div>
                                    <div class="text-sm text-gray-500" x-text="formatTime(order.date_created)"></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <span class="text-lg font-bold text-[#7F5539]"
                                            x-text="formatCurrency(order.payments?.[0]?.total_amount || 0)"></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <template x-if="order.payments?.[0]?.discount > 0">
                                        <div>
                                            <span class="text-sm font-medium text-red-600"
                                                x-text="`-${formatCurrency(order.payments[0].discount)}`"></span>
                                        </div>
                                    </template>
                                    <template x-if="!order.payments?.[0]?.discount || order.payments[0].discount <= 0">
                                        <span class="text-sm text-gray-400">No discount</span>
                                    </template>
                                </td>
                                <!-- ================================================================ -->
                                <!-- DISCOUNT TYPE - Using the same badge-type classes as customer side -->
                                <!-- ================================================================ -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <template x-if="order.payments?.[0]?.discount > 0">
                                        <span class="badge-type" 
                                            :class="getRewardTypeClass(order)">
                                            <span x-text="getDiscountTypeLabel(order)"></span>
                                        </span>
                                    </template>
                                    <template x-if="!order.payments?.[0]?.discount || order.payments[0].discount <= 0">
                                        <span class="text-sm text-gray-400">None</span>
                                    </template>
                                </td>
                                <!-- ================================================================ -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                                        :class="{
                                            'bg-green-100 text-green-800': order.payments?.[0]?.order_payment_status == 1,
                                            'bg-yellow-100 text-yellow-800': order.payments?.[0]?.order_payment_status == 0,
                                        }"
                                        x-text="order.payments?.[0]?.payment_status_text || 'N/A'">
                                    </span>
                                    <div class="text-xs text-gray-500 mt-1"
                                        x-text="order.payments?.[0]?.payment_method_text || ''"></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800"
                                        x-text="order.status_text || getOrderStatusText(order.order_status)">
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex justify-center space-x-2">
                                        <button @click="openViewModal(order)"
                                            class="px-3 py-1 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm font-medium">
                                            View
                                        </button>
                                        <button @click="openReceiptModal(order)"
                                            class="px-3 py-1 bg-[#7F5539] text-white rounded-lg hover:bg-[#4A2C1D] transition-colors text-sm font-medium">
                                            Receipt
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <tr x-show="!orders.length">
                            <td colspan="9" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                        </path>
                                    </svg>
                                    <h5 class="text-sm font-medium text-gray-900"
                                        x-text="hasActiveFilters ? 'No orders match your filters' : 'No orders found'">
                                    </h5>
                                    <p class="text-sm text-gray-500"
                                        x-text="hasActiveFilters ? 'Try adjusting your filters.' : 'When orders are placed, they will appear here.'">
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
                    <div class="text-sm text-gray-700 text-center sm:text-left">
                        Showing <span x-text="pagination.from || 0"></span> to <span x-text="pagination.to || 0"></span>
                        of <span x-text="pagination.total || 0"></span> entries
                    </div>
                    
                    <div class="flex flex-wrap justify-center items-center gap-2">
                        <button @click="changePage(pagination.current_page - 1)" 
                                :disabled="pagination.current_page === 1"
                                class="px-3 py-2 sm:py-1 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
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
                                    x-text="page"
                                    :class="{
                                        'hidden sm:inline-flex': shouldHidePageNumber(page)
                                    }"></button>
                        </template>

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

        <!-- Filters Sidebar -->
        <div x-show="showFilters" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showFilters = false"></div>
                <div
                    class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl sm:my-8 sm:align-middle max-w-md w-full sm:p-6">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Filter Orders</h3>

                        <div x-data="filterState()">
                            <div class="space-y-4">
                                <!-- Full Name Field -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                    <input type="text" x-model="filters.full_name"
                                        placeholder="Search by first or last name"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                </div>

                                <!-- Date Start -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Start</label>
                                    <input type="date" x-model="filters.date_start"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                </div>

                                <!-- Date End -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date End</label>
                                    <input type="date" x-model="filters.date_end"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                </div>

                                <!-- Payment Method -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                                    <select x-model="filters.payment_method"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                        <option value="">All Payment Methods</option>
                                        <option value="0">Cash</option>
                                        <option value="1">GCash</option>
                                        <option value="2">Debit Card</option>
                                        <option value="3">Pay Later</option>
                                    </select>
                                </div>

                                <!-- Payment Status -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Status</label>
                                    <select x-model="filters.payment_status"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                        <option value="">All Status</option>
                                        <option value="1">Paid</option>
                                        <option value="0">Unpaid</option>
                                    </select>
                                </div>

                                <!-- Discount Type -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Discount Type</label>
                                    <select x-model="filters.discount_type"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                        <option value="">All Types</option>
                                        <option value="free_service">Free Service</option>
                                        <option value="free_product">Free Product</option>
                                        <option value="percentage_discount">Percentage Discount</option>
                                        <option value="fixed_discount">Fixed Discount</option>
                                        <option value="product_discount">Product Discount</option>
                                        <option value="none">No Discount</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-6 flex space-x-3">
                                <button @click="clearFilters()"
                                    class="flex-1 inline-flex justify-center px-4 py-2 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#7F5539]">
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

        <!-- Receipt Modal -->
        <div x-show="showReceiptModal" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeReceiptModal"></div>

                <div
                    class="relative inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all">
                    <!-- Receipt Content -->
                    <div class="bg-white p-4"
                        style="width: 320px; min-width: 320px; max-width: 320px; font-family: 'Courier New', monospace; font-size: 12px; margin: 0 auto;">

                        <!-- Header -->
                        <div class="text-center mb-2">
                            <h1 class="text-sm font-bold uppercase" style="font-size: 13px;">
                                {{ config('app.name', 'LinkudHub') }}</h1>
                            <p class="text-xs">Branch: <span
                                    x-text="selectedReceiptOrder?.branch?.branch_name || 'Main'"></span></p>
                            <p class="text-xs"
                                x-text="selectedReceiptOrder?.date_created ? formatDateTime(selectedReceiptOrder.date_created) : ''">
                            </p>
                            <hr class="my-1 border-t border-dashed border-gray-400">
                        </div>

                        <!-- Order Info -->
                        <div class="mb-2">
                            <div class="flex justify-between text-xs">
                                <span>Order Ref:</span>
                                <span class="font-bold" x-text="selectedReceiptOrder?.order_ref_no || 'N/A'"></span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span>Date:</span>
                                <span
                                    x-text="selectedReceiptOrder?.order_date ? formatDate(selectedReceiptOrder.order_date) : formatDate(selectedReceiptOrder?.date_created)"></span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span>Cashier:</span>
                                <span>{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span>
                            </div>
                        </div>

                        <hr class="my-1 border-t border-dashed border-gray-400">

                        <!-- Customer Info -->
                        <div class="mb-2"
                            x-show="selectedReceiptOrder?.customer || (selectedReceiptOrder?.payments && selectedReceiptOrder.payments[0]?.customer_data)">
                            <p class="text-xs font-bold mb-0.5">CUSTOMER:</p>
                            <template x-if="selectedReceiptOrder?.customer">
                                <p class="text-xs"
                                    x-text="selectedReceiptOrder.customer.first_name + ' ' + (selectedReceiptOrder.customer.last_name || '')">
                                </p>
                            </template>
                            <template
                                x-if="!selectedReceiptOrder?.customer && selectedReceiptOrder?.payments && selectedReceiptOrder.payments[0]?.customer_data">
                                <p class="text-xs"
                                    x-text="JSON.parse(selectedReceiptOrder.payments[0].customer_data)?.name || 'Walk-in Customer'">
                                </p>
                            </template>
                        </div>

                        <hr class="my-1 border-t border-dashed border-gray-400">

                        <!-- Order Items -->
                        <div class="mb-2">
                            <p class="text-xs font-bold mb-1">ITEMS:</p>
                            <div class="space-y-1">
                                <template x-for="item in selectedReceiptOrder?.items || []" :key="item.id">
                                    <div class="text-xs">
                                        <div class="flex justify-between">
                                            <span x-text="item.product?.product_name || 'N/A'" class="truncate"
                                                style="max-width: 180px;"></span>
                                            <span>x<span x-text="item.quantity"></span></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="pl-2">@<span
                                                    x-text="formatCurrency(item.selling_price || 0)"></span></span>
                                            <span
                                                x-text="formatCurrency((item.selling_price || 0) * item.quantity)"></span>
                                        </div>
                                        <!-- Item Discount Info -->
                                        <div x-show="item.discount_amount > 0" class="pl-2 text-green-600 text-xs">
                                            Discount: 
                                            <template x-if="item.discount_type === 'percentage'">
                                                <span x-text="item.discount_value + '%'"></span>
                                            </template>
                                            <template x-if="item.discount_type !== 'percentage'">
                                                <span x-text="formatCurrency(item.discount_value)"></span>
                                            </template>
                                            (-<span x-text="formatCurrency(item.discount_amount || 0)"></span>)
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <hr class="my-1 border-t border-dashed border-gray-400">

                        <!-- Payment Summary with Discount Type -->
                        <div class="mb-2"
                            x-show="selectedReceiptOrder?.payments && selectedReceiptOrder.payments.length > 0">
                            <template x-if="selectedReceiptOrder.payments[0]">
                                <div class="space-y-1 text-xs">
                                    <div class="flex justify-between">
                                        <span>Subtotal:</span>
                                        <span x-text="formatCurrency(calculateOrderSubtotal(selectedReceiptOrder))"></span>
                                    </div>

                                    <div class="flex justify-between"
                                        x-show="selectedReceiptOrder.payments[0]?.discount > 0">
                                        <span>Total Discount:</span>
                                        <span class="text-red-600">-<span
                                                x-text="formatCurrency(selectedReceiptOrder.payments[0]?.discount || 0)"></span></span>
                                    </div>

                                    <!-- ================================================================ -->
                                    <!-- DISCOUNT TYPE IN RECEIPT - Using badge-type class -->
                                    <!-- ================================================================ -->
                                    <div class="flex justify-between"
                                        x-show="selectedReceiptOrder.payments[0]?.discount > 0">
                                        <span>Discount Type:</span>
                                        <span class="badge-type" 
                                            :class="getRewardTypeClass(selectedReceiptOrder)"
                                            x-text="getDiscountTypeLabel(selectedReceiptOrder)">
                                        </span>
                                    </div>

                                    <!-- Discount Details -->
                                    <div x-show="hasDiscountDetails(selectedReceiptOrder)" class="mt-1">
                                        <template x-for="(detail, key) in getDiscountDetails(selectedReceiptOrder)" :key="key">
                                            <div class="flex justify-between text-gray-600" x-show="detail > 0">
                                                <span class="pl-2" x-text="key + ':'"></span>
                                                <span class="font-medium" x-text="formatCurrency(detail)"></span>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="flex justify-between"
                                        x-show="selectedReceiptOrder.payments[0]?.vat_sales > 0">
                                        <span>VAT Sales:</span>
                                        <span
                                            x-text="formatCurrency(selectedReceiptOrder.payments[0]?.vat_sales || 0)"></span>
                                    </div>

                                    <div class="flex justify-between"
                                        x-show="selectedReceiptOrder.payments[0]?.vat_amount > 0">
                                        <span>VAT (12%):</span>
                                        <span
                                            x-text="formatCurrency(selectedReceiptOrder.payments[0]?.vat_amount || 0)"></span>
                                    </div>

                                    <div
                                        class="flex justify-between font-bold border-t border-dashed border-gray-400 pt-1">
                                        <span>TOTAL:</span>
                                        <span
                                            x-text="formatCurrency(selectedReceiptOrder.payments[0]?.total_amount || 0)"></span>
                                    </div>

                                    <div class="flex justify-between">
                                        <span>Payment Method:</span>
                                        <span
                                            x-text="selectedReceiptOrder.payments[0]?.payment_method_text || getPaymentMethodLabel(selectedReceiptOrder.payments[0]?.payment_method)"></span>
                                    </div>

                                    <div class="flex justify-between text-xs"
                                        x-show="selectedReceiptOrder?.payments && selectedReceiptOrder.payments[0]?.notes">
                                        <span>Notes:</span>
                                        <span class="text-right max-w-[200px] break-words"
                                            x-text="selectedReceiptOrder.payments[0]?.notes"></span>
                                    </div>

                                    <div class="flex justify-between"
                                        x-show="selectedReceiptOrder.payments[0]?.amount_paid > 0">
                                        <span>Amount Paid:</span>
                                        <span
                                            x-text="formatCurrency(selectedReceiptOrder.payments[0]?.amount_paid || 0)"></span>
                                    </div>

                                    <div class="flex justify-between"
                                        x-show="selectedReceiptOrder.payments[0]?.change > 0">
                                        <span>Change:</span>
                                        <span
                                            x-text="formatCurrency(selectedReceiptOrder.payments[0]?.change || 0)"></span>
                                    </div>

                                    <div class="flex justify-between">
                                        <span>Status:</span>
                                        <span
                                            :class="{
                                                'bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs': selectedReceiptOrder
                                                    ?.payments && selectedReceiptOrder.payments[0]
                                                    ?.order_payment_status == 1,
                                                'bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded text-xs': selectedReceiptOrder
                                                    ?.payments && selectedReceiptOrder.payments[0]
                                                    ?.order_payment_status == 0
                                            }"
                                            x-text="selectedReceiptOrder?.payments && selectedReceiptOrder.payments[0]?.payment_status_text || 'N/A'">
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Fallback if no payments -->
                        <div class="mb-2"
                            x-show="!selectedReceiptOrder?.payments || selectedReceiptOrder.payments.length === 0">
                            <div class="space-y-1 text-xs">
                                <div class="flex justify-between font-bold">
                                    <span>TOTAL:</span>
                                    <span x-text="formatCurrency(calculateOrderTotal(selectedReceiptOrder))"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Payment Status:</span>
                                    <span class="bg-gray-100 text-gray-800 px-2 py-0.5 rounded text-xs">
                                        No Payment Info
                                    </span>
                                </div>
                            </div>
                        </div>

                        <hr class="my-1 border-t border-dashed border-gray-400">

                        <!-- Footer -->
                        <div class="text-center mt-2">
                            <p class="text-xs">Thank you for your purchase!</p>
                            <p class="text-xs text-gray-600">Receipt ID: <span
                                    x-text="selectedReceiptOrder?.order_ref_no || 'N/A'"></span></p>
                            <p class="text-xs text-gray-500">Printed: <span
                                    x-text="new Date().toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'})"></span>
                            </p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="bg-gray-50 px-4 py-3 flex justify-center gap-2">
                        <button type="button" @click="printReceipt"
                            class="inline-flex items-center justify-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-[#7F5539]">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Print Receipt
                        </button>
                        <button type="button" @click="closeReceiptModal"
                            class="inline-flex items-center justify-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-[#7F5539]">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Order Modal -->
        <div x-show="showViewModal" x-cloak class="fixed inset-0 z-[9999]">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="closeViewModal"></div>
                <div
                    class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6 w-full mx-2">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Order Details</h3>

                        <div x-show="selectedOrder" class="space-y-6">
                            <!-- Order Information -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h4 class="font-medium text-gray-900 mb-3">Order Information</h4>
                                    <dl class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <dt class="text-gray-600">Order Ref:</dt>
                                            <dd class="font-medium" x-text="selectedOrder?.order_ref_no || 'N/A'"></dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-gray-600">Customer:</dt>
                                            <dd class="font-medium"
                                                x-text="selectedOrder?.customer ? (selectedOrder.customer.first_name + ' ' + (selectedOrder.customer.last_name || '')).trim() || 'Walk-in Customer' : 'Walk-in Customer'">
                                            </dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-gray-600">Order Date:</dt>
                                            <dd class="font-medium"
                                                x-text="selectedOrder?.date_created ? formatDateTime(selectedOrder.date_created) : 'N/A'">
                                            </dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-gray-600">Order Status:</dt>
                                            <dd class="font-medium">
                                                <span
                                                    x-text="selectedOrder?.status_text || (selectedOrder ? getOrderStatusText(selectedOrder.order_status) : 'N/A')"
                                                    class="status-badge bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">
                                                </span>
                                            </dd>
                                        </div>
                                    </dl>
                                </div>

                                <!-- Payment Information with Discount Type -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h4 class="font-medium text-gray-900 mb-3">Payment Information</h4>
                                    <template x-if="selectedOrder?.payments?.length > 0">
                                        <dl class="space-y-2 text-sm">
                                            <!-- Total Discount -->
                                            <div class="flex justify-between"
                                                x-show="selectedOrder.payments[0]?.discount > 0">
                                                <dt class="text-gray-600">Total Discount:</dt>
                                                <dd class="font-medium text-red-600">
                                                    -<span
                                                        x-text="formatCurrency(selectedOrder.payments[0]?.discount || 0)"></span>
                                                </dd>
                                            </div>

                                            <!-- ================================================================ -->
                                            <!-- DISCOUNT TYPE IN VIEW MODAL - Using badge-type class -->
                                            <!-- ================================================================ -->
                                            <div class="flex justify-between"
                                                x-show="selectedOrder.payments[0]?.discount > 0">
                                                <dt class="text-gray-600">Discount Type:</dt>
                                                <dd class="font-medium">
                                                    <span class="badge-type" 
                                                        :class="getRewardTypeClass(selectedOrder)"
                                                        x-text="getDiscountTypeLabel(selectedOrder)">
                                                    </span>
                                                </dd>
                                            </div>

                                            <!-- Discount Details -->
                                            <div x-show="hasDiscountDetails(selectedOrder)" class="mt-2">
                                                <dt class="text-gray-600 text-xs mb-1">Discount Breakdown:</dt>
                                                <template x-for="(detail, key) in getDiscountDetails(selectedOrder)" :key="key">
                                                    <div class="flex justify-between text-xs ml-2" 
                                                        x-show="detail && (typeof detail === 'string' || typeof detail === 'number' || detail.discount_amount)">
                                                        <span class="text-gray-500" x-text="key + ':'"></span>
                                                        <span class="font-medium" 
                                                            x-text="typeof detail === 'object' && detail.discount_amount ? formatCurrency(detail.discount_amount) : formatCurrency(detail)"></span>
                                                    </div>
                                                </template>
                                            </div>

                                            <div class="flex justify-between">
                                                <dt class="text-gray-600">Payment Method:</dt>
                                                <dd class="font-medium"
                                                    x-text="selectedOrder.payments[0]?.payment_method_text || 'N/A'"></dd>
                                            </div>

                                            <div class="flex justify-between" x-show="selectedOrder.payments[0]?.notes">
                                                <dt class="text-gray-600">Notes:</dt>
                                                <dd class="font-medium max-w-xs text-right break-words"
                                                    x-text="selectedOrder.payments[0]?.notes"></dd>
                                            </div>

                                            <div class="flex justify-between">
                                                <dt class="text-gray-600">Total Amount:</dt>
                                                <dd class="font-medium"
                                                    x-text="formatCurrency(selectedOrder.payments[0]?.total_amount || 0)">
                                                </dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-gray-600">Amount Paid:</dt>
                                                <dd class="font-medium"
                                                    x-text="formatCurrency(selectedOrder.payments[0]?.amount_paid || 0)">
                                                </dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-gray-600">Change:</dt>
                                                <dd class="font-medium"
                                                    x-text="formatCurrency(selectedOrder.payments[0]?.change || 0)"></dd>
                                            </div>

                                            <div class="flex justify-between">
                                                <dt class="text-gray-600">Payment Status:</dt>
                                                <dd class="font-medium">
                                                    <span x-text="selectedOrder.payments[0]?.payment_status_text || 'N/A'"
                                                        :class="{
                                                            'bg-green-100 text-green-800': selectedOrder.payments[0]
                                                                ?.order_payment_status == 1,
                                                            'bg-yellow-100 text-yellow-800': selectedOrder.payments[0]
                                                                ?.order_payment_status == 0,
                                                        }"
                                                        class="status-badge px-2 py-1 rounded-full text-xs">
                                                    </span>
                                                </dd>
                                            </div>
                                        </dl>
                                    </template>
                                    <template x-if="!selectedOrder?.payments?.length">
                                        <p class="text-sm text-gray-500">No payment information available</p>
                                    </template>
                                </div>
                            </div>

                            <!-- Order Items with Discount Details -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-900 mb-3">Order Items</h4>
                                <template x-if="selectedOrder?.items?.length > 0">
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm">
                                            <thead>
                                                <tr class="border-b">
                                                    <th class="text-left pb-2">Product</th>
                                                    <th class="text-center pb-2">Quantity</th>
                                                    <th class="text-right pb-2">Price</th>
                                                    <th class="text-right pb-2">Discount</th>
                                                    <th class="text-right pb-2">Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="item in selectedOrder.items" :key="item.id">
                                                    <tr class="border-b">
                                                        <td class="py-2" x-text="item.product?.product_name || 'N/A'"></td>
                                                        <td class="py-2 text-center" x-text="item.quantity"></td>
                                                        <td class="py-2 text-right"
                                                            x-text="formatCurrency(item.selling_price || 0)"></td>
                                                        <td class="py-2 text-right">
                                                            <template x-if="item.discount_amount > 0">
                                                                <div>
                                                                    <div class="text-red-600 font-medium"
                                                                        x-text="`-${formatCurrency(item.discount_amount)}`"></div>
                                                                    <div class="text-xs text-gray-500">
                                                                        <template x-if="item.discount_type === 'percentage'">
                                                                            (<span x-text="item.discount_value"></span>%)
                                                                        </template>
                                                                        <template x-if="item.discount_type !== 'percentage'">
                                                                            (Amount)
                                                                        </template>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                            <template x-if="!item.discount_amount || item.discount_amount <= 0">
                                                                <span class="text-gray-400 text-sm">None</span>
                                                            </template>
                                                        </td>
                                                        <td class="py-2 text-right font-medium"
                                                            x-text="formatCurrency((item.selling_price || 0) * item.quantity - (item.discount_amount || 0))">
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                            <tfoot>
                                                <!-- Original Subtotal -->
                                                <tr>
                                                    <td colspan="3" class="text-right font-medium pt-2">Original Subtotal:</td>
                                                    <td class="text-right pt-2"></td>
                                                    <td class="text-right font-medium pt-2"
                                                        x-text="formatCurrency(selectedOrder ? calculateOrderSubtotal(selectedOrder) : 0)">
                                                    </td>
                                                </tr>
                                                <!-- Total Discount -->
                                                <tr x-show="selectedOrder?.payments?.[0]?.discount > 0">
                                                    <td colspan="3" class="text-right font-medium">Total Discount:</td>
                                                    <td class="text-right"></td>
                                                    <td class="text-right font-medium text-red-600"
                                                        x-text="`-${formatCurrency(selectedOrder?.payments?.[0]?.discount || 0)}`">
                                                    </td>
                                                </tr>
                                                <!-- ================================================================ -->
                                                <!-- DISCOUNT TYPE IN VIEW MODAL TFOOT - Using badge-type class -->
                                                <!-- ================================================================ -->
                                                <tr x-show="selectedOrder?.payments?.[0]?.discount > 0">
                                                    <td colspan="3" class="text-right font-medium">Discount Type:</td>
                                                    <td class="text-right"></td>
                                                    <td class="text-right font-medium">
                                                        <span class="badge-type" 
                                                            :class="getRewardTypeClass(selectedOrder)"
                                                            x-text="getDiscountTypeLabel(selectedOrder)">
                                                        </span>
                                                    </td>
                                                </tr>
                                                <!-- VAT Information -->
                                                <tr x-show="selectedOrder?.payments?.[0]?.vat_amount > 0">
                                                    <td colspan="3" class="text-right font-medium">VAT (12%):</td>
                                                    <td class="text-right"></td>
                                                    <td class="text-right font-medium"
                                                        x-text="formatCurrency(selectedOrder?.payments?.[0]?.vat_amount || 0)">
                                                    </td>
                                                </tr>
                                                <!-- Final Total -->
                                                <tr class="border-t border-gray-300">
                                                    <td colspan="3" class="text-right font-bold pt-2">Total Amount:</td>
                                                    <td class="text-right pt-2"></td>
                                                    <td class="text-right font-bold text-[#4A2C1D] text-lg pt-2"
                                                        x-text="formatCurrency(selectedOrder?.payments?.[0]?.total_amount || calculateOrderTotal(selectedOrder))">
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </template>
                                <template x-if="!selectedOrder?.items?.length">
                                    <p class="text-sm text-gray-500">No order items available</p>
                                </template>
                            </div>
                        </div>

                        <!-- Loading state -->
                        <div x-show="!selectedOrder" class="text-center py-8">
                            <p class="text-gray-500">Loading order details...</p>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6">
                        <button type="button" @click="closeViewModal"
                            class="w-full inline-flex justify-center px-4 py-2 border border-transparent text-base font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Receipt Modal Styles */
        .receipt-modal-content {
            width: 80mm !important;
            max-width: 80mm !important;
            padding: 4mm !important;
            font-family: 'Courier New', monospace !important;
            font-size: 9pt !important;
            line-height: 1.2 !important;
        }

        /* Print-specific styles */
        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
                padding: 0;
            }

            body {
                margin: 0 !important;
                padding: 0 !important;
                font-family: 'Courier New', monospace !important;
                font-size: 9pt !important;
                width: 80mm !important;
            }

            .receipt-print {
                width: 80mm !important;
                padding: 2mm !important;
                margin: 0 !important;
                box-shadow: none !important;
                border: none !important;
                }

            .no-print {
                display: none !important;
            }

            .receipt-print * {
                margin-top: 0.5mm !important;
                margin-bottom: 0.5mm !important;
                padding-top: 0 !important;
                padding-bottom: 0 !important;
            }

            .receipt-print hr {
                margin: 1mm 0 !important;
                border: none !important;
                border-top: 1px dashed #000 !important;
            }
        }

        [style*="width: 80mm"] {
            width: 80mm !important;
            min-width: 80mm !important;
            max-width: 80mm !important;
        }

        /* Status badge colors */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.65rem;
            font-weight: 500;
        }

        /* ================================================================ */
        /* BADGE-TYPE CLASSES - Same as customer side redemption history */
        /* ================================================================ */
        .badge-type {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .badge-type.free_service { background: #dbeafe; color: #1e40af; }
        .badge-type.free_product { background: #d1fae5; color: #065f46; }
        .badge-type.fixed_discount { background: #fef3c7; color: #92400e; }
        .badge-type.percentage_discount { background: #ede9fe; color: #5b21b6; }
        .badge-type.product_discount { background: #d1fae5; color: #065f46; }
        .badge-type.reward { background: #dcfce7; color: #166534; }
        .badge-type.global { background: #f3e8ff; color: #6b21a8; }
        .badge-type.item_level { background: #dbeafe; color: #1e40af; }
        .badge-type.custom { background: #f3f4f6; color: #374151; }
    </style>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    // Filter state for the sidebar
    Alpine.data('filterState', () => ({
        filters: {
            full_name: '{{ request('first_name', '') }} {{ request('last_name', '') }}'.trim(),
            payment_method: '{{ request('payment_method', '') }}',
            payment_status: '{{ request('payment_status', '') }}',
            date_start: '{{ request('date_start', '') }}',
            date_end: '{{ request('date_end', '') }}',
            discount_type: '{{ request('discount_type', '') }}',
        },
        clearFilters() {
            this.filters = {
                full_name: '',
                payment_method: '',
                payment_status: '',
                date_start: '',
                date_end: '',
                discount_type: '',
            };
        },
        applyFilters() {
            const mainComponent = Alpine.$data(document.querySelector(
                '[x-data="orderHistoryData()"]'));
            mainComponent.applyFilters(this.filters);
        }
    }));

    // Main component
    Alpine.data('orderHistoryData', () => ({
        // Initial state
        orders: @json($orders->items() ?? []),
        pagination: @json($orders->toArray()),
        stats: @json($stats),
        currentFilters: {
            full_name: '{{ request('first_name', '') }} {{ request('last_name', '') }}'.trim(),
            payment_method: '{{ request('payment_method', '') }}',
            payment_status: '{{ request('payment_status', '') }}',
            date_start: '{{ request('date_start', '') }}',
            date_end: '{{ request('date_end', '') }}',
            discount_type: '{{ request('discount_type', '') }}',
        },
        searchQuery: '{{ request('search', '') }}',
        showFilters: false,
        showViewModal: false,
        selectedOrder: null,
        paginationLinks: [],
        isLoading: false,
        showReceiptModal: false,
        selectedReceiptOrder: null,

        init() {
            this.updatePaginationLinks();
            this.updateActiveFilters();
        },

        // Computed properties
        get hasActiveFilters() {
            return Object.values(this.currentFilters).some(value => value !== '') || this.searchQuery;
        },

        get activeFilters() {
            const filters = [];
            
            if (this.searchQuery) {
                filters.push({ key: 'search', label: `Search: ${this.searchQuery}` });
            }
            
            if (this.currentFilters.full_name) {
                filters.push({ key: 'full_name', label: `Customer: ${this.currentFilters.full_name}` });
            }

            if (this.currentFilters.payment_method) {
                filters.push({ key: 'payment_method', label: `${this.getPaymentMethodLabel(this.currentFilters.payment_method)}` });
            }

            if (this.currentFilters.payment_status) {
                filters.push({ key: 'payment_status', label: `Status: ${this.currentFilters.payment_status == 1 ? 'Paid' : 'Unpaid'}` });
            }

            if (this.currentFilters.discount_type) {
                const discountLabels = {
                    'free_service': 'Free Service',
                    'free_product': 'Free Product',
                    'percentage_discount': 'Percentage Discount',
                    'fixed_discount': 'Fixed Discount',
                    'product_discount': 'Product Discount',
                    'none': 'No Discount'
                };
                filters.push({ key: 'discount_type', label: `Discount: ${discountLabels[this.currentFilters.discount_type] || this.currentFilters.discount_type}` });
            }

            if (this.currentFilters.date_start || this.currentFilters.date_end) {
                let dateLabel = '';
                if (this.currentFilters.date_start && this.currentFilters.date_end) {
                    dateLabel += `${this.formatDisplayDate(this.currentFilters.date_start)} - ${this.formatDisplayDate(this.currentFilters.date_end)}`;
                } else if (this.currentFilters.date_start) {
                    dateLabel += `From ${this.formatDisplayDate(this.currentFilters.date_start)}`;
                } else {
                    dateLabel += `To ${this.formatDisplayDate(this.currentFilters.date_end)}`;
                }
                filters.push({ key: 'date_range', label: dateLabel });
            }

            return filters;
        },

        // Format date for display
        formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },

        // Format time for display
        formatTime(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        // Format date time for modal
        formatDateTime(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        },

        formatDisplayDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },

        formatCurrency(amount) {
            if (amount === null || typeof amount === 'undefined' || isNaN(amount))
                return '₱0.00';
            return `₱${parseFloat(amount).toFixed(2)}`;
        },

        getPaymentMethodLabel(method) {
            const labels = {
                '0': 'Cash',
                '1': 'GCash',
                '2': 'Debit Card',
                '3': 'Pay Later'
            };
            return labels[method] || 'Unknown';
        },

        getOrderStatusText(status) {
            return status === 1 ? 'Ordered' : 'Unknown';
        },

        // Calculate original order subtotal (before discounts)
        calculateOrderSubtotal(order) {
            if (!order?.items || !Array.isArray(order.items)) return 0;
            return order.items.reduce((total, item) => {
                const price = item.selling_price || 0;
                const quantity = item.quantity || 0;
                return total + (price * quantity);
            }, 0);
        },

        // Calculate order total with discounts applied
        calculateOrderTotal(order) {
            if (!order?.items || !Array.isArray(order.items)) return 0;
            return order.items.reduce((total, item) => {
                const price = item.selling_price || 0;
                const quantity = item.quantity || 0;
                const discount = item.discount_amount || 0;
                return total + (price * quantity) - discount;
            }, 0);
        },

        // ================================================================
        // DISCOUNT TYPE HELPER METHODS - Same as customer side
        // ================================================================

        // Get the reward type class for badge display
        getRewardTypeClass(order) {
            const discountType = this.getDiscountType(order);
            const classMap = {
                'free_service': 'free_service',
                'free_product': 'free_product',
                'percentage_discount': 'percentage_discount',
                'fixed_discount': 'fixed_discount',
                'product_discount': 'product_discount',
                'reward': 'reward',
                'global': 'global',
                'item_level': 'item_level'
            };
            return classMap[discountType] || 'custom';
        },

        getDiscountType(order) {
            if (!order?.payments?.[0]) return 'none';
            
            const payment = order.payments[0];
            
            // Parse customer data manually first to ensure rewards override stale 'global' records
            let customerData = null;
            if (payment.customer_data) {
                try {
                    customerData = JSON.parse(payment.customer_data);
                } catch (e) {
                    console.error('Error parsing customer_data:', e);
                }
            }

            // First check for reward_applied with reward_type
            if (customerData?.reward_applied) {
                if (customerData.reward_applied.reward_type) {
                    return customerData.reward_applied.reward_type;
                }
                return 'reward';
            }

            // Prioritize already resolved backend discount type value
            if (payment.discount_type && payment.discount_type !== 'none') {
                return payment.discount_type;
            }

            // Fallback check on the parsed customer_data
            if (customerData?.discount_type && customerData.discount_type !== 'none') {
                return customerData.discount_type;
            }
            
            if (payment.discount > 0) {
                const hasItemDiscount = order.items?.some(item => (item.discount_amount || 0) > 0);
                if (hasItemDiscount) {
                    const item = order.items.find(item => (item.discount_amount || 0) > 0);
                    if (item && item.discount_type === 'percentage') {
                        return 'percentage_discount';
                    }
                    return 'fixed_discount';
                }

                // Recognize a reward discount even when reward_applied is missing
                if (customerData?.discount_type === 'reward') {
                    return 'reward';
                }

                return 'global';
            }
            
            return 'none';
        },

        // ================================================================
        // DISCOUNT TYPE LABEL - Returns the proper reward type label
        // ================================================================
        getDiscountTypeLabel(order) {
            if (order?.payments?.[0]?.discount_type_label) {
                return order.payments[0].discount_type_label;
            }
            const discountType = this.getDiscountType(order);
            const labels = {
                'free_service': 'Free Service',
                'free_product': 'Free Product',
                'percentage_discount': 'Percentage Discount',
                'fixed_discount': 'Fixed Discount',
                'product_discount': 'Product Discount',
                'item_level': 'Item-Level Discount',
                'global': 'Global Discount',
                'reward': 'Reward Discount',
                'none': 'None'
            };
            return labels[discountType] || 'Unknown';
        },

        getDiscountDetails(order) {
            if (!order?.payments?.[0]) return {};
            
            const payment = order.payments[0];
            const details = {};
            
            // Get item-level discounts
            if (order.items) {
                const itemDiscounts = order.items.filter(item => (item.discount_amount || 0) > 0);
                if (itemDiscounts.length > 0) {
                    details['Item Discounts'] = itemDiscounts.reduce((sum, item) => sum + (item.discount_amount || 0), 0);
                }
            }
            
            // Check for reward
            if (payment.customer_data) {
                try {
                    const customerData = JSON.parse(payment.customer_data);
                    if (customerData.reward_applied) {
                        const rewardLabel = customerData.reward_applied.reward_type_label || payment.discount_type_label || this.getDiscountTypeLabel(order) || 'Reward Discount';
                        details[rewardLabel] = customerData.reward_applied.discount_amount || 0;
                    }
                } catch (e) {}
            }
            
            return details;
        },

        hasDiscountDetails(order) {
            return Object.keys(this.getDiscountDetails(order)).length > 0;
        },

        getItemLevelDiscounts(order) {
            if (!order?.items) return [];
            return order.items.filter(item => (item.discount_amount || 0) > 0);
        },

        getTotalItemDiscounts(order) {
            return this.getItemLevelDiscounts(order).reduce((sum, item) => sum + (item.discount_amount || 0), 0);
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

                if (this.searchQuery) {
                    queryParams.append('search', this.searchQuery);
                }

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
                    this.orders = data.data;
                    this.pagination = data.pagination;
                    this.stats = data.stats;
                    this.updatePaginationLinks();
                    this.updateActiveFilters();
                } else {
                    throw new Error(data.message || 'Filter application failed');
                }
            } catch (error) {
                console.error('Error applying filters:', error);
            } finally {
                this.isLoading = false;
            }
        },

        // Search functionality
        async performSearch() {
            await this.applyFilters(this.currentFilters);
        },

        async clearFilters() {
            this.isLoading = true;
            this.showFilters = false;
            this.searchQuery = '';
            this.currentFilters = {
                full_name: '',
                payment_method: '',
                payment_status: '',
                date_start: '',
                date_end: '',
                discount_type: '',
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
                    this.orders = data.data;
                    this.pagination = data.pagination;
                    this.stats = data.stats;
                    this.updatePaginationLinks();
                    this.updateActiveFilters();
                } else {
                    throw new Error(data.message || 'Filter clearing failed');
                }
            } catch (error) {
                console.error('Error clearing filters:', error);
            } finally {
                this.isLoading = false;
            }
        },

        async clearAllFilters() {
            await this.clearFilters();
        },

        removeFilter(filterKey) {
            if (filterKey === 'date_range') {
                this.currentFilters.date_start = '';
                this.currentFilters.date_end = '';
            } else if (filterKey === 'search') {
                this.searchQuery = '';
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
            if (this.searchQuery) {
                queryParams.append('search', this.searchQuery);
            }

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

                if (this.searchQuery) {
                    queryParams.append('search', this.searchQuery);
                }

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
                    this.orders = data.data;
                    this.pagination = data.pagination;
                    this.stats = data.stats;
                    this.updatePaginationLinks();
                } else {
                    throw new Error(data.message || 'Pagination failed');
                }
            } catch (error) {
                console.error('Error changing page:', error);
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

        shouldHidePageNumber(page) {
            if (typeof page !== 'number') return false;
            const current = this.pagination?.current_page || 1;
            const last = this.pagination?.last_page || 1;
            return page !== 1 && page !== last && Math.abs(page - current) > 2;
        },

        // View Modal
        openViewModal(order) {
            this.selectedOrder = {
                ...order,
                status_text: order.status_text || this.getOrderStatusText(order.order_status),
                payments: order.payments || [],
                items: order.items || []
            };
            this.showViewModal = true;
            this.addBodyClass();
        },

        closeViewModal() {
            this.showViewModal = false;
            setTimeout(() => {
                this.selectedOrder = null;
                this.removeBodyClass();
            }, 300);
        },

        // Receipt Modal
        openReceiptModal(order) {
            this.selectedReceiptOrder = {
                ...order,
                status_text: order.status_text || this.getOrderStatusText(order.order_status),
                payments: order.payments || [],
                items: order.items || []
            };
            this.showReceiptModal = true;
            this.addBodyClass();
        },

        closeReceiptModal() {
            this.showReceiptModal = false;
            setTimeout(() => {
                this.selectedReceiptOrder = null;
                this.removeBodyClass();
            }, 300);
        },

        // Print receipt
        printReceipt() {
            const printWindow = window.open('', '_blank', 'width=350,height=600');

            const order = this.selectedReceiptOrder;
            if (!order) {
                console.error('No order selected for printing');
                return;
            }

            const subtotal = this.calculateOrderSubtotal(order);
            const totalDiscount = order.payments?.[0]?.discount || 0;
            const vatSales = order.payments?.[0]?.vat_sales || 0;
            const vatAmount = order.payments?.[0]?.vat_amount || 0;
            const totalAmount = order.payments?.[0]?.total_amount || 0;
            const amountPaid = order.payments?.[0]?.amount_paid || 0;
            const change = order.payments?.[0]?.change || 0;
            const paymentMethod = order.payments?.[0]?.payment_method;
            const paymentNotes = order.payments?.[0]?.notes || '';
            const paymentMethodText = order.payments?.[0]?.payment_method_text || this.getPaymentMethodLabel(paymentMethod);
            const paymentStatus = order.payments?.[0]?.order_payment_status;
            const paymentStatusText = order.payments?.[0]?.payment_status_text || (paymentStatus == 1 ? 'Paid' : 'Unpaid');

            // Get discount type label
            const discountType = order.payments?.[0]?.discount_type_label || this.getDiscountTypeLabel(order);
            const discountTypeClass = this.getRewardTypeClass(order);

            let customerName = 'Walk-in Customer';
            if (order.customer) {
                const firstName = order.customer.first_name || '';
                const lastName = order.customer.last_name || '';
                customerName = `${firstName} ${lastName}`.trim();
            } else if (order.payments?.[0]?.customer_data) {
                try {
                    const customerData = JSON.parse(order.payments[0].customer_data);
                    customerName = customerData?.name || 'Walk-in Customer';
                } catch (e) {
                    customerName = 'Walk-in Customer';
                }
            }

            let itemsHTML = '';
            if (order.items && order.items.length > 0) {
                itemsHTML = order.items.map(item => {
                    const productName = item.product?.product_name || 'N/A';
                    const quantity = item.quantity || 0;
                    const price = item.selling_price || 0;
                    const discountAmount = item.discount_amount || 0;
                    const discountValue = item.discount_value || 0;
                    const discountType = item.discount_type || 'amount';
                    const itemTotal = (price * quantity) - discountAmount;

                    const maxNameLength = 18;
                    const displayName = productName.length > maxNameLength ?
                        productName.substring(0, maxNameLength - 3) + '...' :
                        productName;

                    let discountHTML = '';
                    if (discountAmount > 0) {
                        discountHTML = `
                            <div style="font-size: 7pt; color: #059669; padding-left: 4mm;">
                                Disc: ${discountType === 'percentage' ? discountValue + '%' : this.formatCurrency(discountValue)} (-${this.formatCurrency(discountAmount)})
                            </div>
                        `;
                    }

                    return `
                        <div class="item-row">
                            <div style="display: flex; justify-content: space-between; font-size: 8pt;">
                                <div style="width: 60%;">${displayName}</div>
                                <div style="width: 10%; text-align: center;">x${quantity}</div>
                                <div style="width: 30%; text-align: right;">${this.formatCurrency(itemTotal)}</div>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 7pt; color: #666;">
                                <div style="width: 60%; padding-left: 2mm;">@ ${this.formatCurrency(price)} each</div>
                                <div style="width: 40%; text-align: right;">${this.formatCurrency(price * quantity)}</div>
                            </div>
                            ${discountHTML}
                        </div>
                    `;
                }).join('');
            } else {
                itemsHTML = '<div class="item-row"><div style="font-size: 8pt; text-align: center;">No items</div></div>';
            }

            const now = new Date();
            const printedDate = now.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            const appName = window.APP_CONFIG?.name || 'LinkudHub';

            const printHTML = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Receipt - ${order.order_ref_no || 'Order'}</title>
            <meta charset="UTF-8">
            <style>
                @media print {
                    @page {
                        size: 80mm auto;
                        margin: 0;
                    }
                    body {
                        width: 80mm !important;
                        margin: 0 auto !important;
                        padding: 2mm !important;
                        font-family: 'Courier New', monospace !important;
                        font-size: 9pt !important;
                        line-height: 1.1 !important;
                    }
                    .no-print {
                        display: none !important;
                    }
                    .item-row {
                        margin-bottom: 1mm !important;
                        padding-bottom: 1mm !important;
                        border-bottom: 0.5px dotted #ddd !important;
                    }
                }
                
                body {
                    width: 80mm;
                    margin: 0 auto;
                    padding: 2mm;
                    font-family: 'Courier New', monospace;
                    font-size: 9pt;
                    line-height: 1.1;
                }
                
                .receipt-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 1mm;
                    font-size: 8pt;
                }
                
                .receipt-label {
                    flex: 1;
                    text-align: left;
                }
                
                .receipt-value {
                    flex: 1;
                    text-align: right;
                }
                
                .section-title {
                    font-weight: bold;
                    margin: 2mm 0 1mm 0;
                    border-bottom: 0.5px dotted #000;
                    padding-bottom: 0.5mm;
                    font-size: 9pt;
                }
                
                .total-row {
                    border-top: 1px dashed #000;
                    padding-top: 1mm;
                    margin-top: 1mm;
                    font-weight: bold;
                }
                
                .center {
                    text-align: center;
                }
                
                hr {
                    border: none;
                    border-top: 0.5px dashed #000;
                    margin: 1mm 0;
                }
                
                .status-badge {
                    display: inline-block;
                    padding: 0.5mm 1mm;
                    border-radius: 1mm;
                    font-size: 7pt;
                    font-weight: bold;
                }
                
                .paid {
                    background-color: #d1fae5;
                    color: #065f46;
                }
                
                .unpaid {
                    background-color: #fef3c7;
                    color: #92400e;
                }
                
                .discount {
                    color: #dc2626;
                }
                
                .badge-type {
                    display: inline-block;
                    padding: 0.5mm 2mm;
                    border-radius: 3mm;
                    font-size: 7pt;
                    font-weight: 500;
                }
                .badge-type.free_service { background: #dbeafe; color: #1e40af; }
                .badge-type.free_product { background: #d1fae5; color: #065f46; }
                .badge-type.fixed_discount { background: #fef3c7; color: #92400e; }
                .badge-type.percentage_discount { background: #ede9fe; color: #5b21b6; }
                .badge-type.product_discount { background: #d1fae5; color: #065f46; }
                .badge-type.reward { background: #dcfce7; color: #166534; }
                .badge-type.global { background: #f3e8ff; color: #6b21a8; }
                .badge-type.item_level { background: #dbeafe; color: #1e40af; }
                .badge-type.custom { background: #f3f4f6; color: #374151; }
            </style>
        </head>
        <body>
            <div class="receipt-container">
                <!-- Header -->
                <div class="center">
                    <h3 style="margin: 0 0 1mm 0; font-size: 10pt; font-weight: bold;">${appName}</h3>
                    <p style="margin: 0.5mm 0; font-size: 7pt;">Branch: ${order.branch?.branch_name || 'Main'}</p>
                    <p style="margin: 0.5mm 0; font-size: 7pt;">${this.formatDateTime(order.date_created)}</p>
                    <hr>
                </div>
                
                <!-- Order Info -->
                <div>
                    <div class="receipt-row">
                        <span class="receipt-label">Order Ref:</span>
                        <span class="receipt-value" style="font-weight: bold;">${order.order_ref_no || 'N/A'}</span>
                    </div>
                    <div class="receipt-row">
                        <span class="receipt-label">Date:</span>
                        <span class="receipt-value">${this.formatDate(order.order_date || order.date_created)}</span>
                    </div>
                    <div class="receipt-row">
                        <span class="receipt-label">Cashier:</span>
                        <span class="receipt-value">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span>
                    </div>
                </div>
                
                <hr>
                
                <!-- Customer -->
                <div>
                    <div class="section-title">CUSTOMER</div>
                    <div class="receipt-row">
                        <span class="receipt-label">Name:</span>
                        <span class="receipt-value">${customerName}</span>
                    </div>
                </div>
                
                <hr>
                
                <!-- Items Section -->
                <div>
                    <div class="section-title">ITEMS</div>
                    <div style="margin: 1mm 0;">
                        ${itemsHTML}
                    </div>
                </div>
                
                <hr>
                
                <!-- Payment Summary -->
                <div>
                    <div class="section-title">PAYMENT SUMMARY</div>
                    
                    <div class="receipt-row">
                        <span class="receipt-label">Subtotal:</span>
                        <span class="receipt-value">${this.formatCurrency(subtotal)}</span>
                    </div>
                    
                    ${totalDiscount > 0 ? `
                            <div class="receipt-row">
                                <span class="receipt-label">Total Discount:</span>
                                <span class="receipt-value discount">-${this.formatCurrency(totalDiscount)}</span>
                            </div>
                            <div class="receipt-row">
                                <span class="receipt-label">Discount Type:</span>
                                <span class="receipt-value">
                                    <span class="badge-type ${discountTypeClass}">${discountType}</span>
                                </span>
                            </div>
                            ` : ''}
                    
                    ${vatSales > 0 ? `
                            <div class="receipt-row">
                                <span class="receipt-label">VAT Sales:</span>
                                <span class="receipt-value">${this.formatCurrency(vatSales)}</span>
                            </div>
                            ` : ''}
                    
                    ${vatAmount > 0 ? `
                            <div class="receipt-row">
                                <span class="receipt-label">VAT (12%):</span>
                                <span class="receipt-value">${this.formatCurrency(vatAmount)}</span>
                            </div>
                            ` : ''}
                    
                    <div class="receipt-row total-row">
                        <span class="receipt-label">TOTAL:</span>
                        <span class="receipt-value" style="font-size: 10pt;">${this.formatCurrency(totalAmount)}</span>
                    </div>

                    ${paymentNotes ? `
                            <div class="receipt-row">
                                <span class="receipt-label">Notes:</span>
                                <span class="receipt-value" style="font-style: italic; font-size: 7pt;">${paymentNotes}</span>
                            </div>
                        ` : ''}
                    
                    <div class="receipt-row">
                        <span class="receipt-label">Payment Method:</span>
                        <span class="receipt-value">${paymentMethodText}</span>
                    </div>
                    
                    ${amountPaid > 0 ? `
                            <div class="receipt-row">
                                <span class="receipt-label">Amount Paid:</span>
                                <span class="receipt-value">${this.formatCurrency(amountPaid)}</span>
                            </div>
                            ` : ''}
                    
                    ${change > 0 ? `
                            <div class="receipt-row">
                                <span class="receipt-label">Change:</span>
                                <span class="receipt-value">${this.formatCurrency(change)}</span>
                            </div>
                            ` : ''}
                    
                    <div class="receipt-row">
                        <span class="receipt-label">Status:</span>
                        <span class="receipt-value">
                            <span class="status-badge ${paymentStatus == 1 ? 'paid' : 'unpaid'}">
                                ${paymentStatusText}
                            </span>
                        </span>
                    </div>
                </div>
                
                <hr>
                
                <!-- Footer -->
                <div class="center">
                    <p style="margin: 1mm 0; font-weight: bold; font-size: 8pt;">Thank you for your purchase!</p>
                    <p style="margin: 0.5mm 0; font-size: 7pt; color: #666;">Receipt ID: ${order.order_ref_no || 'N/A'}</p>
                    <p style="margin: 0.5mm 0; font-size: 6pt; color: #999;">Printed: ${printedDate}</p>
                </div>
            </div>
            
            <div class="no-print" style="text-align: center; margin-top: 20px;">
                <button onclick="window.print()" style="padding: 8px 16px; background: #7F5539; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px;">
                    Print Receipt
                </button>
                <button onclick="window.close()" style="padding: 8px 16px; background: #6b7280; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Close
                </button>
            </div>
            
            <script>
                window.onload = function() {
                    setTimeout(function() {
                        window.print();
                        setTimeout(function() {
                            window.close();
                        }, 1000);
                    }, 500);
                };
                
                window.addEventListener('afterprint', function() {
                    setTimeout(function() {
                        window.close();
                    }, 500);
                });
                
                setTimeout(function() {
                    if (document.readyState === 'complete') {
                        window.print();
                    }
                }, 1000);
            <\/script>
        </body>
        </html>
        `;

            printWindow.document.write(printHTML);
            printWindow.document.close();

            this.closeReceiptModal();
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
@endpush