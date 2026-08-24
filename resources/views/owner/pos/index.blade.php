@extends('layouts.app')

@section('title', 'Point of Sale')

@section('content')
    <div x-data="posSystem()" x-init="init()">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="py-4">
                    <!-- Mobile Layout (small screens) -->
                    <div class="sm:hidden">
                        <div class="flex flex-col gap-3">
                            <!-- Top Row: POS Label and Cashier -->
                            <div class="flex items-center justify-between">
                                <h1 class="text-2xl font-bold text-gray-900">POS</h1>
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-[#4A2C1D] rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                        {{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}{{ strtoupper(substr(auth()->user()->last_name, 0, 1)) }}
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-gray-900 truncate max-w-[80px]">
                                            {{ auth()->user()->first_name }} {{ substr(auth()->user()->last_name, 0, 1) }}.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="w-full">
                                <div class="relative w-full">
                                    <div x-show="isCustomerFromCheckin">
                                        <div class="inline-flex items-center w-full px-3 py-2 border-2 border-emerald-500 rounded-lg bg-emerald-50">
                                            <span x-text="getBranchName(selectedBranch)" class="font-medium text-emerald-800 truncate text-sm"></span>
                                            <span class="ml-1 text-xs text-emerald-600 font-semibold">✓</span>
                                        </div>
                                    </div>
                                    <div x-show="!isCustomerFromCheckin">
                                        <template x-if="branches.length === 0">
                                            <div class="w-full px-3 py-2 text-sm text-gray-500 italic bg-gray-100 rounded-lg">No branch available</div>
                                        </template>
                                        <template x-if="branches.length === 1">
                                            <div class="w-full px-3 py-2 border-2 border-[#7F5539] rounded-lg bg-white">
                                                <span x-text="getBranchName(selectedBranch)" class="font-medium text-[#4A2C1D] truncate text-sm"></span>
                                            </div>
                                        </template>
                                        <template x-if="branches.length > 1">
                                            <div x-data="{ open: false }" class="relative w-full">
                                                <button @click="open=!open" type="button" class="flex items-center justify-between w-full px-3 py-2 border-2 border-[#7F5539] rounded-lg bg-white hover:bg-gray-50 transition-colors">
                                                    <span x-text="selectedBranch ? getBranchName(selectedBranch) : 'Select Branch'" class="truncate font-medium text-[#4A2C1D] text-sm text-left"></span>
                                                    <svg :class="{ 'rotate-180': open }" class="w-4 h-4 ml-2 text-gray-500 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </button>
                                                <div x-show="open" @click.away="open=false" x-transition class="absolute left-0 right-0 top-full mt-1 w-full bg-white rounded-lg shadow-lg border border-gray-200 z-50 max-h-60 overflow-y-auto">
                                                    <template x-for="branch in branches" :key="branch.id">
                                                        <button @click="onBranchChange(branch.id); open=false" class="w-full px-3 py-2 text-left hover:bg-gray-50 transition-colors truncate text-sm" :class="{ 'bg-[#4A2C1D] text-white hover:bg-[#5A3C2D]': selectedBranch == branch.id }">
                                                            <span x-text="branch.branch_name"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Desktop Layout -->
                    <div class="hidden sm:grid sm:grid-cols-3 items-center gap-4">
                        <h1 class="text-2xl font-bold text-gray-900">Point of Sale</h1>
                        <div class="flex justify-center">
                            <div class="relative w-full max-w-md">
                                <div x-show="isCustomerFromCheckin">
                                    <div class="inline-flex items-center px-3 py-2 border-2 border-emerald-500 rounded-lg bg-emerald-50">
                                        <span x-text="getBranchName(selectedBranch)" class="font-medium text-emerald-800 truncate max-w-[200px]"></span>
                                        <span class="ml-2 text-xs text-emerald-600 font-semibold">(From Check-in)</span>
                                    </div>
                                </div>
                                <div x-show="!isCustomerFromCheckin">
                                    <template x-if="branches.length === 0">
                                        <div class="px-3 py-2 text-sm text-gray-500 italic bg-gray-100 rounded-lg">No branches</div>
                                    </template>
                                    <template x-if="branches.length === 1">
                                        <div class="px-3 py-2 border-2 border-[#7F5539] rounded-lg bg-white w-full">
                                            <span x-text="getBranchName(selectedBranch)" class="font-medium text-[#4A2C1D] truncate"></span>
                                        </div>
                                    </template>
                                    <template x-if="branches.length > 1">
                                        <div x-data="{ open: false }" class="relative w-full">
                                            <button @click="open=!open" type="button" class="inline-flex items-center justify-between px-3 py-2 w-full border-2 border-[#7F5539] rounded-lg bg-white hover:bg-gray-50 transition-colors">
                                                <span x-text="selectedBranch ? getBranchName(selectedBranch) : 'Select Branch'" class="truncate font-medium text-[#4A2C1D]"></span>
                                                <svg :class="{ 'rotate-180': open }" class="w-4 h-4 ml-2 text-gray-500 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                            <div x-show="open" @click.away="open=false" x-transition class="absolute left-0 right-0 top-full mt-1 bg-white rounded-lg shadow-lg border border-gray-200 z-50 max-h-60 overflow-y-auto">
                                                <template x-for="branch in branches" :key="branch.id">
                                                    <button @click="onBranchChange(branch.id); open=false" class="w-full px-3 py-2 text-left hover:bg-gray-50 transition-colors truncate" :class="{ 'bg-[#4A2C1D] text-white hover:bg-[#5A3C2D]': selectedBranch == branch.id }">
                                                        <span x-text="branch.branch_name"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <div class="flex items-center space-x-3">
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-gray-900">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</p>
                                    <p class="text-xs text-gray-500">Cashier</p>
                                </div>
                                <div class="w-8 h-8 bg-[#4A2C1D] rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                    {{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}{{ strtoupper(substr(auth()->user()->last_name, 0, 1)) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="px-4 sm:px-6 lg:px-8 py-4">
            <!-- Mobile & Medium Layout -->
            <div class="block lg:hidden space-y-4">
                <!-- Customer Info Section -->
                <div x-show="isCustomerFromCheckin" class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200 bg-gradient-to-r from-emerald-50 to-white">
                        <h2 class="font-semibold text-gray-900">Customer Info (From Booking)</h2>
                    </div>
                    <div class="p-4">
                        <template x-if="selectedCustomerId && selectedCustomerName">
                            <div class="space-y-4">
                                <div>
                                    <div class="flex items-start gap-3">
                                        <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-gray-900 truncate" x-text="selectedCustomerName"></p>
                                            <template x-if="selectedCustomerEmail">
                                                <p class="text-sm text-gray-600 mt-1 truncate" x-text="selectedCustomerEmail"></p>
                                            </template>
                                            <template x-if="selectedCustomerContact">
                                                <p class="text-sm text-gray-600 mt-1 truncate" x-text="selectedCustomerContact"></p>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gradient-to-r from-emerald-50 to-emerald-100 border border-emerald-200 rounded-lg p-3">
                                    <div class="flex items-center gap-2 mb-2">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="text-xs font-semibold text-emerald-800">Booking Information</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-emerald-700">Booking Reference:</span>
                                        <span class="text-xs font-bold text-emerald-800 truncate ml-2 max-w-[120px]" x-text="bookingReference?.booking_ref_no || 'N/A'"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- ===== REWARDS SECTION - MOBILE ===== -->
                <div x-show="showRewardsSection && selectedCustomerId" x-cloak class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-white">
                        <h2 class="font-semibold text-gray-900">🎁 Available Rewards</h2>
                    </div>
                    <div class="p-4">
                        <div x-show="rewardsLoading" class="text-center py-4">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mx-auto"></div>
                            <p class="text-sm text-gray-500 mt-2">Loading rewards...</p>
                        </div>

                        <div x-show="!rewardsLoading && availableRewards.length > 0">
                            <div class="space-y-2 max-h-48 overflow-y-auto">
                                <template x-for="reward in availableRewards" :key="reward.id">
                                    <div class="bg-gray-50 rounded-lg p-3 border-2 transition-all duration-200 cursor-pointer hover:shadow-md"
                                         :class="selectedReward && selectedReward.id === reward.id ? 'border-purple-600 bg-purple-50' : 'border-gray-200 hover:border-purple-300'"
                                         @click="toggleRewardSelection(reward)">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="font-medium text-gray-900 text-sm" x-text="reward.description"></span>
                                                    <span x-show="reward.days_left !== null" class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full">
                                                        <span x-text="reward.days_left"></span> days
                                                    </span>
                                                </div>
                                                <div class="text-xs text-gray-600 mt-1" x-text="reward.item_name"></div>
                                            </div>
                                            <div class="text-right ml-2">
                                                <div class="font-bold text-purple-600 text-sm" x-text="reward.discount_display"></div>
                                                <div class="text-xs text-gray-400 font-mono" x-text="reward.voucher_code"></div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="mt-3 flex justify-end">
                                <button type="button" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                    @click="applyReward()" :disabled="!selectedReward || rewardApplying">
                                    <span x-show="!rewardApplying">Apply Reward</span>
                                    <span x-show="rewardApplying" class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Applying...
                                    </span>
                                </button>
                            </div>

                            <div x-show="appliedReward" x-cloak class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="text-sm font-medium text-green-800">✅ Reward Applied</span>
                                        <p class="text-sm text-green-700 mt-1" x-text="appliedReward.description"></p>
                                        <p class="text-xs text-green-600">Discount: ₱<span x-text="parseFloat(appliedReward.discount_value || 0).toFixed(2)"></span></p>
                                    </div>
                                    <button type="button" class="text-sm text-red-600 hover:text-red-800" @click="removeAppliedReward()">Remove</button>
                                </div>
                            </div>
                        </div>

                        <div x-show="!rewardsLoading && availableRewards.length === 0" class="text-center py-4">
                            <p class="text-sm text-gray-500">No rewards available for this branch.</p>
                        </div>
                    </div>
                </div>

                <!-- ===== CURRENT ORDER SECTION - MOBILE ===== -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200 bg-gradient-to-r from-[#4A2C1D]/5 to-white">
                        <div class="flex items-center justify-between">
                            <h2 class="font-semibold text-gray-900">Current Order</h2>
                            <button @click="clearCart()" x-show="cart.length > 0" class="text-sm text-red-600 hover:text-red-700 font-medium px-2 py-1 hover:bg-red-50 rounded-lg transition-colors">Clear All</button>
                        </div>
                    </div>
                    <div class="p-4">
                        <template x-if="cart.length === 0">
                            <div class="flex flex-col items-center justify-center text-center p-4">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 a2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">Your Cart is Empty</h3>
                                <p class="text-gray-500 text-sm">Add products from the list below or apply a reward</p>
                            </div>
                        </template>

                        <div x-show="cart.length > 0" class="space-y-4">
                            <template x-for="(item, index) in cart" :key="item.product_id">
                                <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <h3 class="font-medium text-gray-900 truncate" x-text="item.name"></h3>
                                            <p class="text-sm text-gray-600 mt-1" x-text="`₱${item.price.toFixed(2)} each`"></p>
                                            <!-- ===== FROM REWARD BADGE ===== -->
                                            <span x-show="item.from_reward" class="text-xs text-purple-600 font-medium">🎁 From Reward</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="flex items-center bg-white border border-gray-300 rounded-lg">
                                                <button @click="updateQuantity(index, -1)" class="w-8 h-8 flex items-center justify-center text-gray-600 hover:bg-gray-100 rounded-l-lg transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                                    </svg>
                                                </button>
                                                <input type="number" x-model="item.quantity" @change="validateQuantity(index, $event.target.value)" @keydown.enter="$event.target.blur()" class="w-10 text-center py-1 border-x border-gray-300 focus:outline-none focus:ring-0" min="1" :max="getMaxQuantity(item.product_id)">
                                                <button @click="updateQuantity(index, 1)" class="w-8 h-8 flex items-center justify-center text-gray-600 hover:bg-gray-100 rounded-r-lg transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="text-right">
                                                <div class="font-bold text-gray-900" x-text="`₱${item.sub_total.toFixed(2)}`"></div>
                                                <button @click="removeFromCart(index)" class="text-xs text-red-600 hover:text-red-700 hover:bg-red-50 px-1 py-0.5 rounded transition-colors">Remove</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3 pt-3 border-t border-gray-200">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-medium text-gray-700">Item Discount</span>
                                            <div x-show="item.discount > 0" class="text-right">
                                                <span class="text-sm text-green-600 font-medium">-₱<span x-text="calculateItemDiscount(item).toFixed(2)"></span></span>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="flex gap-3">
                                                <label class="flex-1">
                                                    <input type="radio" x-model="item.discount_type" value="amount" class="sr-only">
                                                    <div class="w-full px-4 py-3 text-center border-2 rounded-lg transition-all cursor-pointer" :class="{ 'border-[#4A2C1D] bg-[#4A2C1D] text-white': item.discount_type === 'amount', 'border-gray-300 text-gray-700 hover:border-gray-400 hover:bg-gray-50': item.discount_type !== 'amount' }">
                                                        <span class="font-medium">Amount (₱)</span>
                                                    </div>
                                                </label>
                                                <label class="flex-1">
                                                    <input type="radio" x-model="item.discount_type" value="percentage" class="sr-only">
                                                    <div class="w-full px-4 py-3 text-center border-2 rounded-lg transition-all cursor-pointer" :class="{ 'border-[#4A2C1D] bg-[#4A2C1D] text-white': item.discount_type === 'percentage', 'border-gray-300 text-gray-700 hover:border-gray-400 hover:bg-gray-50': item.discount_type !== 'percentage' }">
                                                        <span class="font-medium">Percentage (%)</span>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="relative">
                                            <input type="number" x-model.number="item.discount" @input.debounce.300ms="updateItemDiscount(index)" class="w-full p-3 pr-20 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] text-base" :placeholder="item.discount_type === 'percentage' ? 'Enter percentage (0-100)' : 'Enter discount amount'" :min="0" :max="item.discount_type === 'percentage' ? 100 : item.price * item.quantity" step="0.01">
                                            <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                                <span class="text-gray-500 font-medium" x-text="item.discount_type === 'percentage' ? '%' : '₱'"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="border-t border-gray-200 p-4 bg-gray-50">
                        <div class="space-y-2 mb-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="font-medium" x-text="`₱${orderSummary.subtotal.toFixed(2)}`"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Total Discount</span>
                                <span class="font-medium text-red-600" x-text="`-₱${orderSummary.totalDiscount.toFixed(2)}`"></span>
                            </div>
                            <div x-show="appliedReward" class="flex justify-between text-sm text-purple-600">
                                <span class="text-gray-600">🎁 Reward Discount</span>
                                <span class="font-medium" x-text="`-₱${orderSummary.rewardDiscount.toFixed(2)}`"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">VAT Sales</span>
                                <span class="font-medium" x-text="`₱${orderSummary.vatSales.toFixed(2)}`"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">VAT (12%)</span>
                                <span class="font-medium" x-text="`₱${orderSummary.vatAmount.toFixed(2)}`"></span>
                            </div>
                        </div>
                        <div class="border-t border-gray-300 pt-3">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-gray-900">Total</span>
                                <span class="text-2xl font-bold text-[#4A2C1D]" x-text="`₱${orderSummary.total.toFixed(2)}`"></span>
                            </div>
                        </div>
                        <button x-show="cart.length > 0 && !showPayment" @click="showPayment = true" class="w-full mt-4 py-3 bg-gradient-to-r from-[#4A2C1D] to-[#5A3C2D] text-white font-bold rounded-lg hover:from-[#5A3C2D] hover:to-[#6A4C3D] transition-all shadow-sm hover:shadow">Proceed to Payment</button>
                    </div>
                </div>

                <!-- Payment Section -->
                <div x-show="showPayment" x-transition.opacity class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-white">
                        <div class="flex items-center justify-between">
                            <h2 class="font-semibold text-gray-900">Payment</h2>
                            <button @click="showPayment = false" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                            <div class="grid grid-cols-2 gap-2">
                                <template x-for="paymentMethod in paymentMethods" :key="paymentMethod.value">
                                    <div x-show="!(paymentMethod.value === '3' && !selectedCustomerId)">
                                        <label class="relative block cursor-pointer">
                                            <input type="radio" x-model="payment.method" :value="paymentMethod.value" class="sr-only peer">
                                            <div class="p-3 text-center border-2 rounded-lg text-sm font-medium transition-all peer-checked:border-[#4A2C1D] peer-checked:bg-[#4A2C1D] peer-checked:text-white border-gray-200 hover:border-gray-300 text-gray-700 hover:text-gray-900" :class="{ 'border-[#4A2C1D] bg-[#4A2C1D] text-white': payment.method === paymentMethod.value }">
                                                <span x-text="paymentMethod.label" :class="{ 'text-white': payment.method === paymentMethod.value, 'text-gray-700 hover:text-gray-900': payment.method !== paymentMethod.value }"></span>
                                            </div>
                                        </label>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div x-show="['0','1','2'].includes(payment.method)" x-transition.scale.origin.top>
                            <div :class="{ 'bg-blue-50 border-blue-200': payment.method === '0', 'bg-green-50 border-green-200': payment.method === '1', 'bg-purple-50 border-purple-200': payment.method === '2' }" class="p-4 rounded-lg border">
                                <h3 class="font-semibold mb-3" :class="{ 'text-blue-800': payment.method === '0', 'text-green-800': payment.method === '1', 'text-purple-800': payment.method === '2' }">
                                    <span x-show="payment.method === '0'">Cash Payment</span>
                                    <span x-show="payment.method === '1'">GCash Payment</span>
                                    <span x-show="payment.method === '2'">Debit Card Payment</span>
                                </h3>
                                <div class="mb-3">
                                    <label class="block text-sm font-medium mb-2" :class="{ 'text-blue-700': payment.method === '0', 'text-green-700': payment.method === '1', 'text-purple-700': payment.method === '2' }">Amount Received</label>
                                    <input type="number" x-model.number="payment.amountPaid" min="0" step="0.01" @input="validateAmountPaid()" class="w-full p-3 border rounded-lg focus:ring-2 focus:outline-none transition-colors" :class="getAmountPaidClasses()" placeholder="Enter amount received">
                                    <div x-show="payment.amountPaid > 0" class="mt-2">
                                        <template x-if="payment.method === '0'">
                                            <div>
                                                <div x-show="payment.amountPaid < orderSummary.total" class="text-sm text-red-600 font-medium">
                                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    Amount is less than total. Add ₱<span x-text="(orderSummary.total - payment.amountPaid).toFixed(2)"></span> more.
                                                </div>
                                                <div x-show="payment.amountPaid === orderSummary.total" class="text-sm text-green-600 font-medium">
                                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    Exact amount received. No change needed.
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="payment.method === '1' || payment.method === '2'">
                                            <div>
                                                <div x-show="payment.amountPaid < orderSummary.total" class="text-sm text-red-600 font-medium">
                                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    Please input the exact amount: ₱<span x-text="orderSummary.total.toFixed(2)"></span>
                                                </div>
                                                <div x-show="payment.amountPaid === orderSummary.total" class="text-sm text-green-600 font-medium">
                                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    Exact payment!
                                                </div>
                                                <div x-show="payment.amountPaid > orderSummary.total" class="text-sm text-yellow-600 font-medium">
                                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                                    </svg>
                                                    For exact payment methods, please input exactly ₱<span x-text="orderSummary.total.toFixed(2)"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <div x-show="payment.method === '1' || payment.method === '2'" class="mb-3">
                                    <label class="block text-sm font-medium mb-2" :class="{ 'text-green-700': payment.method === '1', 'text-purple-700': payment.method === '2' }">Notes (Optional)</label>
                                    <textarea x-model="payment.notes" class="w-full p-3 border rounded-lg focus:ring-2 focus:outline-none" :class="{ 'border-green-300 focus:ring-green-500 focus:border-green-500': payment.method === '1', 'border-purple-300 focus:ring-purple-500 focus:border-purple-500': payment.method === '2' }" rows="2" placeholder="Enter any notes about this payment..."></textarea>
                                </div>
                                <div x-show="payment.method === '0' && payment.amountPaid > orderSummary.total" class="bg-gradient-to-r from-emerald-50 to-emerald-100 border border-emerald-200 rounded-lg p-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-emerald-800">Change Due:</span>
                                        <span class="text-lg font-bold text-emerald-800" x-text="`₱${change.toFixed(2)}`"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div x-show="payment.method === '3'" x-transition.scale.origin.top class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <h3 class="font-semibold text-yellow-800 mb-3">Pay Later</h3>
                            <div class="flex items-start gap-3">
                                <input type="checkbox" x-model="payment.termsAccepted" id="termsAcceptedMobile" class="mt-1 h-4 w-4 text-[#4A2C1D] focus:ring-[#4A2C1D] border-yellow-300 rounded">
                                <label for="termsAcceptedMobile" class="text-sm text-yellow-700 flex-1">Customer agrees to pay <span x-text="`₱${orderSummary.total.toFixed(2)}`" class="font-bold"></span> later.</label>
                            </div>
                        </div>

                        <button @click="processOrder()" :disabled="!canProcessOrder()" :class="{ 'bg-gray-400 cursor-not-allowed': !canProcessOrder(), 'bg-gradient-to-r from-[#4A2C1D] to-[#5A3C2D] hover:from-[#5A3C2D] hover:to-[#6A4C3D]': canProcessOrder() }" class="w-full py-4 text-white font-bold rounded-lg transition-all shadow-sm hover:shadow flex items-center justify-center gap-2 mt-2">
                            <template x-if="processingOrder">
                                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <span x-text="processingOrder ? 'Processing...' : getProcessButtonText()"></span>
                        </button>
                    </div>
                </div>

                <!-- List of Products -->
                <div class="mt-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="border-b border-gray-200 px-4 py-3">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-gray-900">List of Products</h2>
                                <div x-data="{ searchOpen: false }" class="relative">
                                    <div class="flex items-center gap-2">
                                        <button @click="searchOpen = !searchOpen; if(searchOpen) $nextTick(() => $refs.searchInput.focus())" :class="{ 'bg-gray-100': searchOpen }" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </button>
                                        <div x-show="searchOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="absolute right-0 top-full mt-1 z-50">
                                            <div class="relative">
                                                <input x-ref="searchInput" type="text" x-model="searchTerm" @input.debounce.300ms="searchProducts()" placeholder="Search..." class="w-64 pl-3 pr-8 py-2 border border-gray-300 rounded-lg shadow-lg bg-white focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]">
                                                <button @click="clearSearch(); searchOpen = false" class="absolute right-2 top-1/2 transform -translate-y-1/2 p-1 rounded hover:bg-gray-100">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <template x-if="!selectedBranch">
                                <div class="flex flex-col items-center justify-center text-center p-6">
                                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                    </div>
                                    <p class="text-gray-500">Please select a branch to view products</p>
                                </div>
                            </template>
                            <template x-if="selectedBranch && products.length === 0">
                                <div class="flex flex-col items-center justify-center text-center p-6">
                                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <p class="text-gray-500">No products found for this branch</p>
                                </div>
                            </template>
                            <div x-show="selectedBranch && products.length > 0" class="space-y-4">
                                <template x-for="product in filteredProducts" :key="product.id">
                                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                                        <div class="flex gap-4">
                                            <div class="flex-shrink-0">
                                                <img :src="product.product_img ? `/storage/app/public/${product.product_img}` : 'https://placehold.co/100x100/eeeeee/cccccc?text=No+Image'" :alt="product.product_name" class="w-16 h-16 rounded-lg object-cover bg-gray-100">
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex justify-between items-start">
                                                    <div class="flex-1">
                                                        <h3 class="font-semibold text-gray-900 truncate" x-text="product.product_name"></h3>
                                                        <p class="text-sm text-gray-500 mt-1" x-text="product.product_batch_no"></p>
                                                    </div>
                                                    <div class="font-bold text-[#4A2C1D] text-lg ml-2" x-text="`₱${parseFloat(product.selling_price).toFixed(2)}`"></div>
                                                </div>
                                                <div class="mt-2 flex items-center justify-between">
                                                    <template x-if="!product.product_ingredients || product.product_ingredients.length === 0">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="{ 'bg-green-100 text-green-800': product.quantity_in > 10, 'bg-yellow-100 text-yellow-800': product.quantity_in > 5 && product.quantity_in <= 10, 'bg-red-100 text-red-800': product.quantity_in > 0 && product.quantity_in <= 5, 'bg-gray-100 text-gray-800': product.quantity_in === 0 }">
                                                            <span x-text="product.quantity_in + ' in stock'"></span>
                                                        </span>
                                                    </template>
                                                    <template x-if="product.product_ingredients && product.product_ingredients.length > 0">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="{ 'bg-green-100 text-green-800': getProductQuantity(product) > 10, 'bg-yellow-100 text-yellow-800': getProductQuantity(product) > 5 && getProductQuantity(product) <= 10, 'bg-red-100 text-red-800': getProductQuantity(product) > 0 && getProductQuantity(product) <= 5, 'bg-gray-100 text-gray-800': getProductQuantity(product) === 0 }">
                                                            <span x-text="getProductQuantity(product) > 0 ? 'In Stock' : 'Out of Stock'"></span>
                                                        </span>
                                                    </template>
                                                    <button @click="addToCart(product)" :disabled="getProductQuantity(product) === 0" :class="{ 'bg-[#4A2C1D] hover:bg-[#5A3C2D]': getProductQuantity(product) > 0, 'bg-gray-300 cursor-not-allowed': getProductQuantity(product) === 0 }" class="px-4 py-1.5 text-white font-medium rounded-lg transition-colors text-sm ml-2" x-text="getProductQuantity(product) === 0 ? 'Out' : 'Add'"></button>
                                                </div>
                                                <div x-show="product.product_ingredients && product.product_ingredients.length > 0" class="mt-3">
                                                    <button @click="product.showStock = !product.showStock" class="text-sm text-blue-600 hover:text-blue-700 font-medium flex items-center gap-1">
                                                        <span>View Ingredients Stock</span>
                                                        <svg :class="{ 'rotate-180': product.showStock }" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                        </svg>
                                                    </button>
                                                    <div x-show="product.showStock" class="mt-2 space-y-2">
                                                        <template x-for="ingredient in product.product_ingredients" :key="ingredient.id">
                                                            <div class="text-sm p-2 bg-white border border-gray-200 rounded-lg">
                                                                <div class="font-medium text-gray-700" x-text="ingredient.ingredient.ingredient_name"></div>
                                                                <div class="text-xs text-gray-500 mt-1">
                                                                    <template x-if="hasIngredientStock(ingredient.ingredient)">
                                                                        <div>
                                                                            <span class="font-semibold">Available: </span>
                                                                            <span x-text="getIngredientStockDisplay(ingredient.ingredient)"></span>
                                                                        </div>
                                                                    </template>
                                                                    <template x-if="!hasIngredientStock(ingredient.ingredient)">
                                                                        <div class="text-red-500 font-semibold">No stock available</div>
                                                                    </template>
                                                                </div>
                                                                <div class="text-xs text-gray-500 mt-1">
                                                                    <span x-text="`${ingredient.quantity_needed}${ingredient.unit} needed per unit`"></span>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Large Desktop Layout -->
            <div class="hidden lg:block">
                <div class="flex gap-4">
                    <!-- LEFT: Products -->
                    <div class="w-1/2 flex-shrink-0">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col sticky top-[90px] h-[calc(100vh-8rem)]">
                            <div class="border-b border-gray-200 px-4 py-3 bg-gradient-to-r from-[#4A2C1D]/5 to-white flex-shrink-0">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-lg font-semibold text-gray-900">List of Products</h2>
                                    <div x-data="{ searchOpen: false }" class="relative">
                                        <div class="flex items-center gap-2">
                                            <button @click="searchOpen = !searchOpen; if(searchOpen) $nextTick(() => $refs.searchInput.focus())" :class="{ 'bg-gray-100': searchOpen }" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                </svg>
                                            </button>
                                            <div x-show="searchOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="absolute right-0 top-full mt-1 z-50">
                                                <div class="relative">
                                                    <input x-ref="searchInput" type="text" x-model="searchTerm" @input.debounce.300ms="searchProducts()" placeholder="Search products..." class="w-64 pl-3 pr-8 py-2 border border-gray-300 rounded-lg shadow-lg bg-white focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]">
                                                    <button @click="clearSearch(); searchOpen = false" class="absolute right-2 top-1/2 transform -translate-y-1/2 p-1 rounded hover:bg-gray-100">
                                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-1 overflow-auto p-4">
                                <template x-if="!selectedBranch">
                                    <div class="h-full flex flex-col items-center justify-center p-8 text-center">
                                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-medium text-gray-900 mb-2">Select a Branch</h3>
                                        <p class="text-gray-500 max-w-sm">Please select a branch from the dropdown to view list of products.</p>
                                    </div>
                                </template>
                                <template x-if="selectedBranch && products.length === 0">
                                    <div class="h-full flex flex-col items-center justify-center p-8 text-center">
                                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Products Found</h3>
                                        <p class="text-gray-500 max-w-sm">No products available for this branch.</p>
                                    </div>
                                </template>
                                <div x-show="selectedBranch && products.length > 0" class="grid grid-cols-2 gap-3">
                                    <template x-for="product in filteredProducts" :key="product.id">
                                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-200 hover:border-[#7F5539]/50 transition-colors flex flex-col">
                                            <div class="mb-2 flex-shrink-0">
                                                <img :src="product.product_img ? `/storage/app/public/${product.product_img}` : 'https://placehold.co/200x200/eeeeee/cccccc?text=No+Image'" :alt="product.product_name" class="w-full h-40 object-contain rounded-lg bg-gray-100">
                                            </div>
                                            <div class="flex-1 flex flex-col">
                                                <h3 class="font-medium text-gray-900 text-sm truncate mb-1" x-text="product.product_name"></h3>
                                                <div class="flex justify-between items-center mb-2">
                                                    <span class="font-bold text-[#4A2C1D] text-sm" x-text="`₱${parseFloat(product.selling_price).toFixed(2)}`"></span>
                                                    <template x-if="!product.product_ingredients || product.product_ingredients.length === 0">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" :class="{ 'bg-green-100 text-green-800': product.quantity_in > 10, 'bg-yellow-100 text-yellow-800': product.quantity_in > 5 && product.quantity_in <= 10, 'bg-red-100 text-red-800': product.quantity_in > 0 && product.quantity_in <= 5, 'bg-gray-100 text-gray-800': product.quantity_in === 0 }">
                                                            <span x-text="product.quantity_in + ' in stock'"></span>
                                                        </span>
                                                    </template>
                                                    <template x-if="product.product_ingredients && product.product_ingredients.length > 0">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" :class="{ 'bg-green-100 text-green-800': getProductQuantity(product) > 10, 'bg-yellow-100 text-yellow-800': getProductQuantity(product) > 5 && getProductQuantity(product) <= 10, 'bg-red-100 text-red-800': getProductQuantity(product) > 0 && getProductQuantity(product) <= 5, 'bg-gray-100 text-gray-800': getProductQuantity(product) === 0 }">
                                                            <span x-text="getProductQuantity(product) > 0 ? 'In Stock' : 'Out of Stock'"></span>
                                                        </span>
                                                    </template>
                                                </div>
                                                <div x-show="product.product_ingredients && product.product_ingredients.length > 0" class="mb-2">
                                                    <button @click="product.showStock = !product.showStock" class="w-full text-xs text-blue-600 hover:text-blue-700 font-medium flex items-center justify-center gap-1 py-1 hover:bg-blue-50 rounded transition-colors">
                                                        <span>View Ingredients Stock</span>
                                                        <svg :class="{ 'rotate-180': product.showStock }" class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                        </svg>
                                                    </button>
                                                    <div x-show="product.showStock" class="mt-1 space-y-1">
                                                        <template x-for="ingredient in product.product_ingredients" :key="ingredient.id">
                                                            <div class="text-xs p-2 bg-white border border-gray-200 rounded">
                                                                <div class="font-medium text-gray-700 truncate" x-text="ingredient.ingredient.ingredient_name"></div>
                                                                <div class="text-gray-500 mt-0.5">
                                                                    <template x-if="hasIngredientStock(ingredient.ingredient)">
                                                                        <div>
                                                                            <span class="font-semibold">Available: </span>
                                                                            <span x-text="getIngredientStockDisplay(ingredient.ingredient)"></span>
                                                                        </div>
                                                                    </template>
                                                                    <template x-if="!hasIngredientStock(ingredient.ingredient)">
                                                                        <div class="text-red-500 font-semibold">No stock available</div>
                                                                    </template>
                                                                </div>
                                                                <div class="text-gray-500 mt-0.5">
                                                                    <span x-text="`${ingredient.quantity_needed}${ingredient.unit} needed per unit`"></span>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                                <div class="mt-auto">
                                                    <button @click="addToCart(product)" :disabled="getProductQuantity(product) === 0" :class="{ 'bg-[#4A2C1D] hover:bg-[#5A3C2D]': getProductQuantity(product) > 0, 'bg-gray-300 cursor-not-allowed': getProductQuantity(product) === 0 }" class="w-full py-1.5 text-white font-medium rounded-lg transition-colors text-xs" x-text="getProductQuantity(product) === 0 ? 'Out of Stock' : 'Add Item'"></button>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Customer, Rewards, Current Order, Payment -->
                    <div class="w-1/2 flex-shrink-0 flex flex-col h-[calc(100vh-8rem)] sticky top-[90px]">
                        <div class="flex flex-col h-full gap-4">
                            
                            <!-- Top Row (Customer & Rewards) -->
                            <div class="grid gap-4 flex-shrink-0" :class="showRewardsSection && selectedCustomerId ? 'grid-cols-2' : 'grid-cols-1'">
                                <!-- Customer Info Card -->
                                <div class="bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col max-h-48">
                                    <div x-show="isCustomerFromCheckin" class="border-b border-gray-200 px-4 py-3 bg-gradient-to-r from-emerald-50 to-white flex-shrink-0">
                                        <h2 class="font-semibold text-gray-900">Customer Info</h2>
                                    </div>
                                    <div x-show="!isCustomerFromCheckin" class="border-b border-gray-200 px-4 py-3 bg-gradient-to-r from-gray-50 to-white flex-shrink-0">
                                        <h2 class="font-semibold text-gray-900">Customer</h2>
                                    </div>
                                    <div class="flex-1 p-4 overflow-y-auto">
                                        <template x-if="isCustomerFromCheckin && selectedCustomerId && selectedCustomerName">
                                            <div class="space-y-3">
                                                <div>
                                                    <div class="flex items-start gap-2">
                                                        <div class="w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0">
                                                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                            </svg>
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <p class="font-bold text-gray-900 truncate text-sm" x-text="selectedCustomerName"></p>
                                                            <template x-if="selectedCustomerEmail">
                                                                <p class="text-xs text-gray-600 mt-1 truncate" x-text="selectedCustomerEmail"></p>
                                                            </template>
                                                            <template x-if="selectedCustomerContact">
                                                                <p class="text-xs text-gray-600 mt-1 truncate" x-text="selectedCustomerContact"></p>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="bg-gradient-to-r from-emerald-50 to-emerald-100 border border-emerald-200 rounded-lg p-2">
                                                    <div class="flex items-center gap-1 mb-1">
                                                        <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <span class="text-xs font-semibold text-emerald-800">From Booking</span>
                                                    </div>
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-xs text-emerald-700">Ref:</span>
                                                        <span class="text-xs font-bold text-emerald-800 truncate ml-1 max-w-[80px]" x-text="bookingReference?.booking_ref_no || 'N/A'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="!isCustomerFromCheckin">
                                            <div class="flex flex-col items-center justify-center text-center h-full">
                                                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                    </svg>
                                                </div>
                                                <p class="text-sm text-gray-500">Walk-in customer</p>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- ===== REWARDS CARD - DESKTOP ===== -->
                                <div x-show="showRewardsSection && selectedCustomerId" x-cloak class="bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col max-h-48">
                                    <div class="border-b border-gray-200 px-4 py-3 bg-gradient-to-r from-purple-50 to-white flex-shrink-0">
                                        <h2 class="font-semibold text-gray-900">🎁 Available Rewards</h2>
                                    </div>
                                    <div class="flex-1 p-3 overflow-y-auto">
                                        <div x-show="rewardsLoading" class="text-center py-4">
                                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mx-auto"></div>
                                            <p class="text-xs text-gray-500 mt-2">Loading rewards...</p>
                                        </div>
                                        <div x-show="!rewardsLoading && availableRewards.length > 0">
                                            <div class="space-y-2">
                                                <template x-for="reward in availableRewards" :key="reward.id">
                                                    <div class="bg-gray-50 rounded-lg p-2 border-2 transition-all duration-200 cursor-pointer hover:shadow-md" :class="selectedReward && selectedReward.id === reward.id ? 'border-purple-600 bg-purple-50' : 'border-gray-200 hover:border-purple-300'" @click="toggleRewardSelection(reward)">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex-1 min-w-0">
                                                                <div class="flex items-center gap-1 flex-wrap">
                                                                    <span class="font-medium text-gray-900 text-xs" x-text="reward.description"></span>
                                                                    <span x-show="reward.days_left !== null" class="text-xs bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded-full text-[10px]">
                                                                        <span x-text="reward.days_left"></span>d
                                                                    </span>
                                                                </div>
                                                                <div class="text-xs text-gray-600 truncate" x-text="reward.item_name"></div>
                                                            </div>
                                                            <div class="text-right ml-2 flex-shrink-0">
                                                                <div class="font-bold text-purple-600 text-xs" x-text="reward.discount_display"></div>
                                                                <div class="text-[10px] text-gray-400 font-mono" x-text="reward.voucher_code"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="mt-2 flex justify-end">
                                                <button type="button" class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed" @click="applyReward()" :disabled="!selectedReward || rewardApplying">
                                                    <span x-show="!rewardApplying">Apply</span>
                                                    <span x-show="rewardApplying" class="flex items-center">
                                                        <svg class="animate-spin -ml-1 mr-1 h-3 w-3 text-white" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        ...
                                                    </span>
                                                </button>
                                            </div>
                                            <div x-show="appliedReward" x-cloak class="mt-2 p-2 bg-green-50 border border-green-200 rounded-lg">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <span class="text-xs font-medium text-green-800">✅ Applied</span>
                                                        <p class="text-xs text-green-700 truncate" x-text="appliedReward.description"></p>
                                                        <p class="text-[10px] text-green-600">-₱<span x-text="parseFloat(appliedReward.discount_value || 0).toFixed(2)"></span></p>
                                                    </div>
                                                    <button type="button" class="text-xs text-red-600 hover:text-red-800" @click="removeAppliedReward()">Remove</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div x-show="!rewardsLoading && availableRewards.length === 0" class="text-center py-4">
                                            <p class="text-xs text-gray-500">No rewards available</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom Row (Current Order & Payment) -->
                            <div class="grid gap-4 flex-1 min-h-0" :class="showPayment ? 'grid-cols-2' : 'grid-cols-1'">
                                
                                <!-- Current Order Card -->
                                <div class="bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col h-full overflow-hidden">
                                    <div class="border-b border-gray-200 px-4 py-3 bg-gradient-to-r from-[#4A2C1D]/5 to-white flex-shrink-0">
                                        <div class="flex items-center justify-between">
                                            <h2 class="font-semibold text-gray-900">Current Order</h2>
                                            <button @click="clearCart()" x-show="cart.length > 0" class="text-sm text-red-600 hover:text-red-700 font-medium px-2 py-1 hover:bg-red-50 rounded-lg transition-colors">Clear All</button>
                                        </div>
                                    </div>
                                    <div class="flex-1 overflow-y-auto p-3">
                                        <template x-if="cart.length === 0">
                                            <div class="h-full flex flex-col items-center justify-center text-center p-4">
                                                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 a2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                                    </svg>
                                                </div>
                                                <h3 class="text-sm font-medium text-gray-900 mb-1">Cart Empty</h3>
                                                <p class="text-gray-500 text-xs">Add products from the list or apply a reward</p>
                                            </div>
                                        </template>
                                        <div x-show="cart.length > 0" class="space-y-3">
                                            <template x-for="(item, index) in cart" :key="index">
                                                <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <div class="flex-1 min-w-0">
                                                            <h3 class="font-medium text-gray-900 truncate text-sm" x-text="item.name"></h3>
                                                            <p class="text-xs text-gray-600 mt-0.5" x-text="`₱${item.price.toFixed(2)} each`"></p>
                                                            <!-- ===== FROM REWARD BADGE ===== -->
                                                            <span x-show="item.from_reward" class="text-[10px] text-purple-600 font-medium">🎁 From Reward</span>
                                                        </div>
                                                        <div class="flex items-center gap-1">
                                                            <div class="flex items-center bg-white border border-gray-300 rounded">
                                                                <button @click="updateQuantity(index, -1)" class="w-6 h-6 flex items-center justify-center text-gray-600 hover:bg-gray-100 rounded-l transition-colors">
                                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                                                    </svg>
                                                                </button>
                                                                <input type="number" x-model="item.quantity" @change="validateQuantity(index, $event.target.value)" @keydown.enter="$event.target.blur()" class="w-10 text-center py-0.5 border-x border-gray-300 focus:outline-none focus:ring-0 text-sm" min="1" :max="getMaxQuantity(item.product_id)">
                                                                <button @click="updateQuantity(index, 1)" class="w-6 h-6 flex items-center justify-center text-gray-600 hover:bg-gray-100 rounded-r transition-colors">
                                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                            <div class="text-right">
                                                                <div class="font-bold text-gray-900 text-sm" x-text="`₱${item.sub_total.toFixed(2)}`"></div>
                                                                <button @click="removeFromCart(index)" class="text-xs text-red-600 hover:text-red-700 hover:bg-red-50 px-1 py-0.5 rounded transition-colors">Remove</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mt-2 pt-2 border-t border-gray-200">
                                                        <div class="flex items-center justify-between mb-2">
                                                            <span class="text-xs font-medium text-gray-700">Discount</span>
                                                            <div x-show="item.discount > 0" class="text-right">
                                                                <span class="text-xs text-green-600 font-medium">-₱<span x-text="calculateItemDiscount(item).toFixed(2)"></span></span>
                                                            </div>
                                                        </div>
                                                        <div class="mb-2">
                                                            <div class="flex gap-2">
                                                                <label class="flex-1">
                                                                    <input type="radio" x-model="item.discount_type" value="amount" class="sr-only">
                                                                    <div class="w-full px-3 py-2 text-center border-2 rounded transition-all cursor-pointer" :class="{ 'border-[#4A2C1D] bg-[#4A2C1D] text-white': item.discount_type === 'amount', 'border-gray-300 text-gray-700 hover:border-gray-400 hover:bg-gray-50': item.discount_type !== 'amount' }">
                                                                        <span class="text-xs font-medium">Amount (₱)</span>
                                                                    </div>
                                                                </label>
                                                                <label class="flex-1">
                                                                    <input type="radio" x-model="item.discount_type" value="percentage" class="sr-only">
                                                                    <div class="w-full px-3 py-2 text-center border-2 rounded transition-all cursor-pointer" :class="{ 'border-[#4A2C1D] bg-[#4A2C1D] text-white': item.discount_type === 'percentage', 'border-gray-300 text-gray-700 hover:border-gray-400 hover:bg-gray-50': item.discount_type !== 'percentage' }">
                                                                        <span class="text-xs font-medium">Percentage (%)</span>
                                                                    </div>
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="relative">
                                                            <input type="number" x-model.number="item.discount" @input.debounce.300ms="updateItemDiscount(index)" class="w-full p-2 pr-12 border border-gray-300 rounded focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539] text-sm" :placeholder="item.discount_type === 'percentage' ? '0-100' : '0.00'" :min="0" :max="item.discount_type === 'percentage' ? 100 : item.price * item.quantity" step="0.01">
                                                            <div class="absolute right-2 top-1/2 transform -translate-y-1/2">
                                                                <span class="text-gray-500 text-sm font-medium" x-text="item.discount_type === 'percentage' ? '%' : '₱'"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="border-t border-gray-200 p-3 bg-gray-50 flex-shrink-0">
                                        <div class="space-y-1 mb-2">
                                            <div class="flex justify-between text-xs">
                                                <span class="text-gray-600">Subtotal</span>
                                                <span class="font-medium" x-text="`₱${orderSummary.subtotal.toFixed(2)}`"></span>
                                            </div>
                                            <div class="flex justify-between text-xs">
                                                <span class="text-gray-600">Total Discount</span>
                                                <span class="font-medium text-red-600" x-text="`-₱${orderSummary.totalDiscount.toFixed(2)}`"></span>
                                            </div>
                                            <div x-show="appliedReward" class="flex justify-between text-xs text-purple-600">
                                                <span class="text-gray-600">🎁 Reward</span>
                                                <span class="font-medium" x-text="`-₱${orderSummary.rewardDiscount.toFixed(2)}`"></span>
                                            </div>
                                            <div class="flex justify-between text-xs">
                                                <span class="text-gray-600">VAT Sales</span>
                                                <span class="font-medium" x-text="`₱${orderSummary.vatSales.toFixed(2)}`"></span>
                                            </div>
                                            <div class="flex justify-between text-xs">
                                                <span class="text-gray-600">VAT (12%)</span>
                                                <span class="font-medium" x-text="`₱${orderSummary.vatAmount.toFixed(2)}`"></span>
                                            </div>
                                        </div>
                                        <div class="border-t border-gray-300 pt-2">
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm font-bold text-gray-900">Total</span>
                                                <span class="text-lg font-bold text-[#4A2C1D]" x-text="`₱${orderSummary.total.toFixed(2)}`"></span>
                                            </div>
                                        </div>
                                        <button x-show="cart.length > 0 && !showPayment" @click="showPayment = true" class="w-full mt-2 py-2 bg-gradient-to-r from-[#4A2C1D] to-[#5A3C2D] text-white font-bold rounded-lg hover:from-[#5A3C2D] hover:to-[#6A4C3D] transition-all shadow-sm hover:shadow text-xs">Proceed to Payment</button>
                                    </div>
                                </div>

                                <!-- Payment Section -->
                                <div x-show="showPayment" x-cloak x-transition.opacity class="bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col h-full overflow-hidden">
                                    <div class="border-b border-gray-200 px-4 py-3 bg-gradient-to-r from-blue-50 to-white flex-shrink-0">
                                        <div class="flex items-center justify-between">
                                            <h2 class="font-semibold text-gray-900">Payment</h2>
                                            <button @click="showPayment = false" class="w-6 h-6 flex items-center justify-center rounded hover:bg-gray-100 transition-colors">
                                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="p-4 space-y-3 flex-1 overflow-y-auto">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                                            <div class="grid grid-cols-2 gap-2">
                                                <template x-for="paymentMethod in paymentMethods" :key="paymentMethod.value">
                                                    <div x-show="!(paymentMethod.value === '3' && !selectedCustomerId)">
                                                        <label class="relative block cursor-pointer">
                                                            <input type="radio" x-model="payment.method" :value="paymentMethod.value" class="sr-only peer">
                                                            <div class="p-2 text-center border-2 rounded-lg text-xs font-medium transition-all peer-checked:border-[#4A2C1D] peer-checked:bg-[#4A2C1D] peer-checked:text-white border-gray-200 hover:border-gray-300 text-gray-700 hover:text-gray-900" :class="{ 'border-[#4A2C1D] bg-[#4A2C1D] text-white': payment.method === paymentMethod.value }">
                                                                <span x-text="paymentMethod.label" :class="{ 'text-white': payment.method === paymentMethod.value, 'text-gray-700 hover:text-gray-900': payment.method !== paymentMethod.value }"></span>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                        <div x-show="['0','1','2'].includes(payment.method)" x-transition.scale.origin.top>
                                            <div :class="{ 'bg-blue-50 border-blue-200': payment.method === '0', 'bg-green-50 border-green-200': payment.method === '1', 'bg-purple-50 border-purple-200': payment.method === '2' }" class="p-3 rounded-lg border">
                                                <h3 class="font-semibold mb-2 text-xs" :class="{ 'text-blue-800': payment.method === '0', 'text-green-800': payment.method === '1', 'text-purple-800': payment.method === '2' }">
                                                    <span x-show="payment.method === '0'">Cash Payment</span>
                                                    <span x-show="payment.method === '1'">GCash Payment</span>
                                                    <span x-show="payment.method === '2'">Debit Card Payment</span>
                                                </h3>
                                                <div class="mb-2">
                                                    <label class="block text-xs font-medium mb-1" :class="{ 'text-blue-700': payment.method === '0', 'text-green-700': payment.method === '1', 'text-purple-700': payment.method === '2' }">Amount Received</label>
                                                    <input type="number" x-model.number="payment.amountPaid" min="0" step="0.01" @input="validateAmountPaid()" class="w-full p-2 border rounded-lg focus:ring-2 focus:outline-none transition-colors text-sm" :class="getAmountPaidClasses()" placeholder="Enter amount received">
                                                    <div x-show="payment.amountPaid >= 0" class="mt-1">
                                                        <template x-if="payment.method === '0'">
                                                            <div>
                                                                <div x-show="payment.amountPaid < orderSummary.total" class="text-xs text-red-600 font-medium">
                                                                    <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    </svg>
                                                                    Add ₱<span x-text="(orderSummary.total - payment.amountPaid).toFixed(2)"></span> more.
                                                                </div>
                                                                <div x-show="payment.amountPaid === orderSummary.total" class="text-xs text-green-600 font-medium">
                                                                    <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    </svg>
                                                                    Exact amount. No change.
                                                                </div>
                                                            </div>
                                                        </template>
                                                        <template x-if="payment.method === '1' || payment.method === '2'">
                                                            <div>
                                                                <div x-show="payment.amountPaid < orderSummary.total" class="text-xs text-red-600 font-medium">
                                                                    <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    </svg>
                                                                    Input exactly ₱<span x-text="orderSummary.total.toFixed(2)"></span>
                                                                </div>
                                                                <div x-show="payment.amountPaid === orderSummary.total" class="text-xs text-green-600 font-medium">
                                                                    <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    </svg>
                                                                    Exact payment received.
                                                                </div>
                                                                <div x-show="payment.amountPaid > orderSummary.total" class="text-xs text-yellow-600 font-medium">
                                                                    <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                                                    </svg>
                                                                    Input exactly ₱<span x-text="orderSummary.total.toFixed(2)"></span>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                                <div x-show="payment.method === '1' || payment.method === '2'" class="mb-2">
                                                    <label class="block text-xs font-medium mb-1" :class="{ 'text-green-700': payment.method === '1', 'text-purple-700': payment.method === '2' }">Notes (Optional)</label>
                                                    <textarea x-model="payment.notes" class="w-full p-2 border rounded-lg focus:ring-2 focus:outline-none text-sm" :class="{ 'border-green-300 focus:ring-green-500 focus:border-green-500': payment.method === '1', 'border-purple-300 focus:ring-purple-500 focus:border-purple-500': payment.method === '2' }" rows="2" placeholder="Enter any notes..."></textarea>
                                                </div>
                                                <div x-show="payment.method === '0' && payment.amountPaid > orderSummary.total" class="bg-gradient-to-r from-emerald-50 to-emerald-100 border border-emerald-200 rounded-lg p-2">
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs font-medium text-emerald-800">Change Due:</span>
                                                        <span class="text-sm font-bold text-emerald-800" x-text="`₱${change.toFixed(2)}`"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div x-show="payment.method === '3'" x-transition.scale.origin.top class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                                            <h3 class="font-semibold text-yellow-800 mb-2 text-xs">Pay Later</h3>
                                            <div class="flex items-start gap-2">
                                                <input type="checkbox" x-model="payment.termsAccepted" id="termsAcceptedDesktop" class="mt-0.5 h-3 w-3 text-[#4A2C1D] focus:ring-[#4A2C1D] border-yellow-300 rounded">
                                                <label for="termsAcceptedDesktop" class="text-xs text-yellow-700 flex-1">Customer agrees to pay <span x-text="`₱${orderSummary.total.toFixed(2)}`" class="font-bold"></span> later.</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-4 border-t border-gray-200 bg-gray-50 flex-shrink-0">
                                        <button @click="processOrder()" :disabled="!canProcessOrder()" :class="{ 'bg-gray-400 cursor-not-allowed': !canProcessOrder(), 'bg-gradient-to-r from-[#4A2C1D] to-[#5A3C2D] hover:from-[#5A3C2D] hover:to-[#6A4C3D]': canProcessOrder() }" class="w-full py-2.5 text-white font-bold rounded-lg transition-all shadow-sm hover:shadow flex items-center justify-center gap-2 text-sm">
                                            <template x-if="processingOrder">
                                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </template>
                                            <span x-text="processingOrder ? 'Processing...' : getProcessButtonText()"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Order Confirmation Modal -->
        <div x-cloak x-show="showConfirmationModal" x-transition.opacity class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-[9999] p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full max-h-[90vh] overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-[#4A2C1D] to-[#5A3C2D]">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-white">Confirm Order</h3>
                        <button @click="showConfirmationModal = false" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-white/10 transition-colors">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="flex-1 overflow-auto p-6">
                    <div class="mb-6">
                        <h4 class="font-semibold text-gray-900 mb-3">Order Summary</h4>
                        <div class="space-y-2 bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="font-medium" x-text="`₱${orderSummary.subtotal.toFixed(2)}`"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Total Discount</span>
                                <span class="font-medium text-red-600" x-text="`-₱${orderSummary.totalDiscount.toFixed(2)}`"></span>
                            </div>
                            <div x-show="appliedReward" class="flex justify-between text-sm text-purple-600">
                                <span class="text-gray-600">🎁 Reward Discount</span>
                                <span class="font-medium" x-text="`-₱${orderSummary.rewardDiscount.toFixed(2)}`"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">VAT Sales</span>
                                <span class="font-medium" x-text="`₱${orderSummary.vatSales.toFixed(2)}`"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">VAT (12%)</span>
                                <span class="font-medium" x-text="`₱${orderSummary.vatAmount.toFixed(2)}`"></span>
                            </div>
                            <div class="border-t border-gray-300 pt-2 mt-2">
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-gray-900">Total</span>
                                    <span class="text-lg font-bold text-[#4A2C1D]" x-text="`₱${orderSummary.total.toFixed(2)}`"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-6">
                        <h4 class="font-semibold text-gray-900 mb-3">Customer</h4>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <template x-if="isCustomerFromCheckin">
                                <div>
                                    <p class="font-medium text-gray-900" x-text="selectedCustomerName"></p>
                                    <template x-if="selectedCustomerEmail">
                                        <p class="text-sm text-gray-600 mt-1" x-text="selectedCustomerEmail"></p>
                                    </template>
                                    <template x-if="selectedCustomerContact">
                                        <p class="text-sm text-gray-600 mt-1" x-text="selectedCustomerContact"></p>
                                    </template>
                                    <div class="mt-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">From Check-in</span>
                                    </div>
                                </div>
                            </template>
                            <template x-if="!isCustomerFromCheckin && customer.name">
                                <div>
                                    <p class="font-medium text-gray-900" x-text="customer.name"></p>
                                    <template x-if="customer.email">
                                        <p class="text-sm text-gray-600 mt-1" x-text="customer.email"></p>
                                    </template>
                                    <template x-if="customer.contact">
                                        <p class="text-sm text-gray-600 mt-1" x-text="customer.contact"></p>
                                    </template>
                                    <div class="mt-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Walk-in Customer</span>
                                    </div>
                                </div>
                            </template>
                            <template x-if="!isCustomerFromCheckin && !customer.name">
                                <p class="text-gray-500 italic">Walk-in customer (No name provided)</p>
                            </template>
                        </div>
                    </div>
                    <div class="mb-6">
                        <h4 class="font-semibold text-gray-900 mb-3">Payment Details</h4>
                        <div class="bg-gray-50 p-4 rounded-lg space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Method:</span>
                                <span class="font-medium text-[#4A2C1D]" x-text="getPaymentMethodLabel()"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Amount Paid:</span>
                                <span class="font-medium" x-text="`₱${(parseFloat(payment.amountPaid) || 0).toFixed(2)}`"></span>
                            </div>
                            <div x-show="payment.method === '0' && payment.amountPaid > orderSummary.total" class="flex justify-between items-center pt-2 border-t border-gray-200">
                                <span class="text-gray-600">Change Due:</span>
                                <span class="font-medium text-green-600" x-text="`₱${change.toFixed(2)}`"></span>
                            </div>
                            <template x-if="payment.method === '3'">
                                <div class="flex items-center gap-2 pt-2 border-t border-gray-200">
                                    <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    <span class="text-sm text-yellow-700 font-medium">Customer agrees to pay later</span>
                                </div>
                            </template>
                            <template x-if="payment.notes">
                                <div class="mt-3 pt-3 border-t border-gray-200">
                                    <p class="text-sm text-gray-600">Notes:</p>
                                    <p class="text-sm text-gray-800 mt-1" x-text="payment.notes"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-3">Items</h4>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            <template x-for="(item, index) in cart" :key="index">
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 text-sm" x-text="item.name"></p>
                                        <p class="text-xs text-gray-600" x-text="`₱${item.price.toFixed(2)} × ${item.quantity}`"></p>
                                        <div x-show="item.discount > 0" class="mt-1">
                                            <span class="text-xs text-green-600">Discount: <span x-text="item.discount_type === 'percentage' ? item.discount + '%' : '₱' + item.discount.toFixed(2)"></span> (-₱<span x-text="calculateItemDiscount(item).toFixed(2)"></span>)</span>
                                        </div>
                                        <span x-show="item.from_reward" class="text-[10px] text-purple-600 font-medium">🎁 From Reward</span>
                                    </div>
                                    <div class="font-bold text-[#4A2C1D] text-sm" x-text="`₱${item.sub_total.toFixed(2)}`"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
                    <button @click="showConfirmationModal = false" class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
                    <button @click="confirmProcessOrder()" :disabled="processingOrder" :class="{ 'bg-gray-400 cursor-not-allowed': processingOrder, 'bg-gradient-to-r from-[#4A2C1D] to-[#5A3C2D] hover:from-[#5A3C2D] hover:to-[#6A4C3D]': !processingOrder }" class="px-6 py-2 text-white font-bold rounded-lg transition-all shadow-sm hover:shadow flex items-center gap-2">
                        <template x-if="processingOrder">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <span x-text="processingOrder ? 'Processing...' : 'Confirm'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function posSystem() {
            return {
                products: [],
                branches: @json($branches),
                selectedBranch: null,
                searchTerm: '',
                cart: [],
                processingOrder: false,
                showPayment: false,
                showSearchInput: false,
                showConfirmationModal: false,
                customer: {
                    name: '',
                    contact: '',
                    email: '',
                    address: ''
                },
                selectedCustomerId: {{ $prefilledCustomer->id ?? 'null' }},
                selectedCustomerName: '{{ $prefilledCustomer ? addslashes($prefilledCustomer->first_name . ' ' . $prefilledCustomer->last_name) : '' }}',
                selectedCustomerEmail: '{{ $prefilledCustomer->email ?? '' }}',
                selectedCustomerContact: '{{ $prefilledCustomer->contact_no ?? '' }}',
                isCustomerFromCheckin: {{ $prefilledCustomer ? 'true' : 'false' }},
                payment: {
                    method: '0',
                    amountPaid: '',
                    gcashRefNo: '',
                    termsAccepted: false,
                    notes: ''
                },
                bookingReference: @json($bookingReference ?? null),
                VAT_RATE: 0.12,
                paymentMethods: [
                    { value: '0', label: 'Cash' },
                    { value: '1', label: 'GCash' },
                    { value: '2', label: 'Debit Card' },
                    { value: '3', label: 'Pay Later' }
                ],
                baseUrls: {
                    searchProduct: '{{ route('sub_one.pos.search-product') }}',
                    processOrder: '{{ route('sub_one.pos.process-order') }}',
                    changeBranch: '{{ route('sub_one.pos.change-branch') }}',
                    getCustomerRewards: '{{ route('sub_one.pos.get-customer-rewards') }}',
                    applyReward: '{{ route('sub_one.pos.apply-reward') }}'
                },

                // ===== REWARD PROPERTIES =====
                availableRewards: @json($customerRewards ?? []),
                selectedReward: null,
                appliedReward: null,
                rewardsLoading: false,
                rewardApplying: false,
                customerRewardId: null,
                rewardDiscountAmount: 0,
                rewardVoucherCode: null,
                showRewardsSection: {{ $prefilledCustomer ? 'true' : 'false' }},

                // Computed properties
                get filteredProducts() {
                    if (!this.searchTerm) return this.products;
                    const term = this.searchTerm.toLowerCase();
                    return this.products.filter(p =>
                        p.product_name.toLowerCase().includes(term) ||
                        (p.product_batch_no && p.product_batch_no.toLowerCase().includes(term))
                    );
                },

                get orderSummary() {
                    const subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                    const totalDiscount = this.cart.reduce((sum, item) => sum + this.calculateItemDiscount(item), 0);
                    
                    // Dynamic client-side recalculation of reward deductions
                    let dynamicRewardDiscount = 0;
                    if (this.appliedReward) {
                        if (this.appliedReward.reward_type === 'free_product' || this.appliedReward.reward_type === 'product_discount') {
                            const rewardProductId = this.appliedReward.product_data?.product_id || this.appliedReward.product_data?.id;
                            const cartItem = this.cart.find(item => item.product_id === rewardProductId);
                            if (cartItem) {
                                if (this.appliedReward.reward_type === 'free_product') {
                                    // Discount equals the unit price of the target free product
                                    dynamicRewardDiscount = this.appliedReward.discount_value || cartItem.price;
                                } else if (this.appliedReward.reward_type === 'product_discount') {
                                    if (this.appliedReward.is_percentage) {
                                        dynamicRewardDiscount = (cartItem.price * this.appliedReward.percentage) / 100;
                                    } else {
                                        dynamicRewardDiscount = Math.min(this.appliedReward.discount_value, cartItem.price);
                                    }
                                }
                            }
                        } else if (this.appliedReward.reward_type === 'percentage_discount') {
                            dynamicRewardDiscount = (subtotal * this.appliedReward.percentage) / 100;
                        } else {
                            dynamicRewardDiscount = this.appliedReward.discount_value;
                        }
                    }

                    // Keep reward discount bounded safely by remaining balance
                    const cappedRewardDiscount = Math.min(dynamicRewardDiscount, Math.max(0, subtotal - totalDiscount));

                    const total = Math.max(0, subtotal - totalDiscount - cappedRewardDiscount);
                    const vatSales = total / (1 + this.VAT_RATE);
                    const vatAmount = total - vatSales;
                    return {
                        subtotal,
                        totalDiscount,
                        rewardDiscount: cappedRewardDiscount,
                        vatSales,
                        vatAmount,
                        total
                    };
                },

                get change() {
                    if (this.payment.method !== '0') return 0;
                    const amountPaid = parseFloat(this.payment.amountPaid) || 0;
                    const total = this.orderSummary.total;
                    return Math.max(0, amountPaid - total);
                },

                getIngredientStockDisplay(ingredient) {
                    if (!ingredient) return 'N/A';
                    let display = '';
                    if (ingredient.stock_quantity_in > 0) {
                        display += `${ingredient.stock_quantity_in}${ingredient.unit}`;
                    }
                    if (ingredient.converted_stock_quantity_in > 0 && ingredient.converted_unit) {
                        if (display) display += ' + ';
                        display += `${ingredient.converted_stock_quantity_in}${ingredient.converted_unit}`;
                    }
                    if (ingredient.unit_conversion && ingredient.unit_conversion > 0) {
                        display += ` (1${ingredient.unit} = ${ingredient.unit_conversion}${ingredient.converted_unit})`;
                    }
                    return display || '0';
                },

                // Initialize
                init() {
                    const hasCheckinInfo = this.isCustomerFromCheckin;
                    const checkinBranchId = {{ $bookingReference['branch_id'] ?? 'null' }};

                    if (hasCheckinInfo && checkinBranchId && checkinBranchId !== 'null') {
                        this.selectedBranch = parseInt(checkinBranchId);
                    } else {
                        this.loadSelectedBranch();
                    }

                    this.loadFromStorage();
                    this.prepareProducts();
                    this.initializeCustomerFromServer();

                    if (this.selectedBranch) {
                        this.loadProductsForBranch();
                    }

                    // Load rewards if customer is already selected
                    if (this.selectedCustomerId && this.selectedBranch) {
                        this.loadCustomerRewards();
                    }

                    // Watch for customer selection changes
                    this.$watch('selectedCustomerId', (newVal, oldVal) => {
                        if (newVal && this.selectedBranch) {
                            this.loadCustomerRewards();
                        } else {
                            this.showRewardsSection = false;
                            this.availableRewards = [];
                        }
                    });

                    this.$watch('selectedBranch', (newVal, oldVal) => {
                        if (newVal && this.selectedCustomerId) {
                            this.loadCustomerRewards();
                        }
                    });

                    this.$watch('cart', () => {
                        this.saveToStorage();
                        this.updateCartSubtotals();
                    }, { deep: true });

                    this.$watch('customer', () => this.saveToStorage(), { deep: true });

                    this.$watch('selectedBranch', (newBranch) => {
                        if (newBranch && !this.isCustomerFromCheckin) {
                            this.saveSelectedBranch();
                        }
                    });

                    this.$watch('selectedBranch', () => {
                        this.showSearchInput = false;
                    });
                },

                // ===== REWARD METHODS =====
                async loadCustomerRewards() {
                    if (!this.selectedCustomerId || !this.selectedBranch) {
                        this.availableRewards = [];
                        this.selectedReward = null;
                        this.appliedReward = null;
                        this.customerRewardId = null;
                        this.rewardDiscountAmount = 0;
                        this.rewardVoucherCode = null;
                        this.showRewardsSection = false;
                        return;
                    }

                    this.rewardsLoading = true;
                    this.availableRewards = [];
                    this.selectedReward = null;

                    try {
                        const response = await fetch(`${this.baseUrls.getCustomerRewards}?customer_id=${this.selectedCustomerId}&branch_id=${this.selectedBranch}`, {
                            method: 'GET',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.availableRewards = data.rewards || [];
                            this.showRewardsSection = this.availableRewards.length > 0;
                        } else {
                            console.error('Error loading rewards:', data.message);
                            this.showRewardsSection = false;
                        }
                    } catch (error) {
                        console.error('Error loading rewards:', error);
                        this.showRewardsSection = false;
                    } finally {
                        this.rewardsLoading = false;
                    }
                },

                toggleRewardSelection(reward) {
                    if (this.appliedReward) {
                        this.removeAppliedReward();
                    }
                    
                    if (this.selectedReward && this.selectedReward.id === reward.id) {
                        this.selectedReward = null;
                    } else {
                        this.selectedReward = reward;
                    }
                },

                async applyReward() {
                    if (!this.selectedReward) {
                        this.showNotification('Please select a reward first.', 'warning');
                        return;
                    }

                    if (this.rewardApplying) return;

                    this.rewardApplying = true;

                    try {
                        const response = await fetch(this.baseUrls.applyReward, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                customer_reward_id: this.selectedReward.id,
                                total_amount: parseFloat(this.orderSummary.total) || 0
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.appliedReward = {
                                id: this.selectedReward.id,
                                voucher_code: this.selectedReward.voucher_code || 'N/A',
                                description: this.selectedReward.description || 'Reward',
                                discount_value: data.discount_value || 0,
                                item_name: this.selectedReward.item_name || '',
                                reward_type: data.reward_type || this.selectedReward.reward_type,
                                product_data: data.product_data || this.selectedReward.product_data
                            };
                            
                            this.customerRewardId = this.selectedReward.id;
                            this.rewardDiscountAmount = data.discount_value || 0;
                            this.rewardVoucherCode = this.selectedReward.voucher_code || null;
                            
                            // ===== AUTO-ADD PRODUCT TO CART =====
                            // If this is a product reward, auto-add the product to cart
                            if (this.appliedReward.product_data && 
                                (this.appliedReward.reward_type === 'free_product' || 
                                 this.appliedReward.reward_type === 'product_discount')) {
                                this.autoAddProductFromReward(this.appliedReward.product_data);
                            }
                            
                            this.selectedReward = null;
                            this.showNotification('Reward applied successfully!', 'success');
                        } else {
                            this.showNotification('❌ Error: ' + data.message, 'error');
                        }
                    } catch (error) {
                        console.error('Error applying reward:', error);
                        this.showNotification('Error applying reward. Please try again.', 'error');
                    } finally {
                        this.rewardApplying = false;
                    }
                },

                // ===== AUTO-ADD PRODUCT TO CURRENT ORDER =====
                autoAddProductFromReward(productData) {
                    if (!productData) return;
                    
                    console.log('Auto-adding product from reward:', productData);
                    
                    // The backend could return 'id' or 'product_id' depending on the serialization. Checking both.
                    const pId = productData.product_id || productData.id;
                    const pName = productData.product_name || productData.name;
                    const pPrice = productData.selling_price || productData.price;
                    
                    // Check if product is already in cart
                    const existingItem = this.cart.find(item => item.product_id === pId);
                    
                    // Get the full product details from the loaded POS products list
                    const fullProduct = this.products.find(p => p.id === pId);
                    
                    if (existingItem) {
                        // If product already exists in cart, increase quantity
                        existingItem.quantity++;
                        existingItem.from_reward = true;
                        this.updateItemSubtotal(existingItem);
                        this.showNotification(`Added another "${pName || fullProduct?.product_name || 'Product'}" to cart from reward!`, 'success');
                    } else {
                        // Add new product to cart using dynamically pulled attributes
                        const newItem = {
                            product_id: pId,
                            name: pName || fullProduct?.product_name || 'Product',
                            price: parseFloat(pPrice) || parseFloat(fullProduct?.selling_price) || 0,
                            quantity: 1,
                            discount: 0,
                            discount_type: 'amount',
                            from_reward: true,
                            product_data: fullProduct || productData
                        };
                        
                        this.cart.push(newItem);
                        this.updateItemSubtotal(newItem);
                        
                        // Show a detailed notification mentioning ingredients if applicable
                        const hasIngredients = fullProduct?.product_ingredients && fullProduct.product_ingredients.length > 0;
                        const productType = hasIngredients ? ' (with ingredients)' : '';
                        this.showNotification(`✅ Product "${newItem.name}"${productType} added to Current Order from reward!`, 'success');
                    }
                    
                    // Force a cart update
                    this.saveToStorage();
                    this.updateCartSubtotals();
                },

                removeAppliedReward() {
                    if (!this.appliedReward) return;
                    
                    // Remove product if it was added from reward
                    if (this.appliedReward.product_data) {
                        const productId = this.appliedReward.product_data.product_id || this.appliedReward.product_data.id;
                        const index = this.cart.findIndex(item => 
                            item.product_id === productId && item.from_reward === true
                        );
                        if (index !== -1) {
                            this.cart.splice(index, 1);
                            this.showNotification(`Product removed from cart.`, 'info');
                        }
                    }
                    
                    this.customerRewardId = null;
                    this.rewardDiscountAmount = 0;
                    this.rewardVoucherCode = null;
                    this.appliedReward = null;
                    this.showNotification('Reward removed.', 'info');
                },

                // Methods
                getBranchName(id) {
                    if (!id) return 'Select a branch';
                    const branch = this.branches.find(b => b.id == id);
                    return branch ? branch.branch_name : 'Unknown Branch';
                },

                initializeCustomerFromServer() {
                    const serverCustomer = @json($prefilledCustomer);

                    if (serverCustomer && serverCustomer.id) {
                        this.selectedCustomerId = serverCustomer.id;
                        this.selectedCustomerName = serverCustomer.first_name + ' ' + serverCustomer.last_name;
                        this.selectedCustomerEmail = serverCustomer.email || '';
                        this.selectedCustomerContact = serverCustomer.contact_no || '';

                        this.customer = {
                            name: this.selectedCustomerName,
                            email: this.selectedCustomerEmail,
                            contact: this.selectedCustomerContact,
                            address: ''
                        };

                        this.saveCustomerToStorage();
                    }
                },

                loadSelectedBranch() {
                    try {
                        const savedBranch = localStorage.getItem('pos_selected_branch');

                        if (savedBranch) {
                            const branchExists = this.branches.some(branch => branch.id == savedBranch);
                            if (branchExists) {
                                this.selectedBranch = parseInt(savedBranch);
                                return;
                            }
                        }

                        if (this.branches.length > 0) {
                            this.selectedBranch = this.branches[0].id;
                            return;
                        }

                        this.selectedBranch = null;

                    } catch (error) {
                        console.error('Error loading selected branch:', error);
                        if (this.branches.length > 0) {
                            this.selectedBranch = this.branches[0].id;
                        }
                    }
                },

                saveSelectedBranch() {
                    try {
                        if (this.selectedBranch) {
                            localStorage.setItem('pos_selected_branch', this.selectedBranch.toString());
                        }
                    } catch (error) {
                        console.error('Error saving selected branch:', error);
                    }
                },

                saveCustomerToStorage() {
                    try {
                        if (this.selectedCustomerId) {
                            localStorage.setItem('pos_selected_customer_id', this.selectedCustomerId);
                            localStorage.setItem('pos_selected_customer_name', this.selectedCustomerName);
                            localStorage.setItem('pos_selected_customer_email', this.selectedCustomerEmail || '');
                            localStorage.setItem('pos_selected_customer_contact', this.selectedCustomerContact || '');
                        } else {
                            localStorage.removeItem('pos_selected_customer_id');
                            localStorage.removeItem('pos_selected_customer_name');
                            localStorage.removeItem('pos_selected_customer_email');
                            localStorage.removeItem('pos_selected_customer_contact');
                        }
                    } catch (error) {
                        console.error('Error saving customer to storage:', error);
                    }
                },

                saveToStorage() {
                    try {
                        if (this.selectedBranch) {
                            localStorage.setItem(`pos_cart_${this.selectedBranch}`, JSON.stringify(this.cart));
                        }

                        const hasCustomerData = this.customer && (
                            this.customer.name ||
                            this.customer.email ||
                            this.customer.contact ||
                            this.customer.address
                        );

                        if (hasCustomerData) {
                            localStorage.setItem('pos_customer', JSON.stringify(this.customer));
                        } else {
                            localStorage.removeItem('pos_customer');
                        }
                    } catch (error) {
                        console.error('Error saving to storage:', error);
                    }
                },

                loadFromStorage() {
                    try {
                        if (this.selectedBranch) {
                            const savedCart = localStorage.getItem(`pos_cart_${this.selectedBranch}`);
                            this.cart = savedCart ? JSON.parse(savedCart) : [];
                        } else {
                            this.cart = [];
                        }

                        if (!this.isCustomerFromCheckin) {
                            const savedCustomer = localStorage.getItem('pos_customer');
                            if (savedCustomer) {
                                const parsedCustomer = JSON.parse(savedCustomer);
                                if (parsedCustomer && (parsedCustomer.name || parsedCustomer.email || parsedCustomer.contact)) {
                                    this.customer = parsedCustomer;
                                } else {
                                    this.customer = { name: '', contact: '', email: '', address: '' };
                                }
                            }
                        }
                    } catch (error) {
                        console.error('Error loading from storage:', error);
                        this.cart = [];
                        this.customer = { name: '', contact: '', email: '', address: '' };
                    }
                },

                prepareProducts() {
                    this.products = this.products.map(product => {
                        product.available_quantity = (product.product_ingredients && product.product_ingredients.length > 0) ?
                            this.calculateProductQuantity(product) :
                            product.quantity_in;
                        if (!product.hasOwnProperty('showIngredients')) {
                            product.showIngredients = false;
                        }
                        return product;
                    });
                },

                calculateProductQuantity(product) {
                    if (!product.product_ingredients || product.product_ingredients.length === 0) return product.quantity_in;
                    let maxPossible = Infinity;
                    for (const prodIngredient of product.product_ingredients) {
                        const ing = prodIngredient.ingredient;
                        if (!ing) continue;

                        let totalStock = 0;

                        if (ing.stock_quantity_in > 0) {
                            if (ing.unit_conversion && ing.unit_conversion > 0) {
                                totalStock += ing.stock_quantity_in * ing.unit_conversion;
                            } else {
                                totalStock += ing.stock_quantity_in;
                            }
                        }

                        if (ing.converted_stock_quantity_in > 0) {
                            totalStock += ing.converted_stock_quantity_in;
                        }

                        const possibleFromIngredient = Math.floor(totalStock / prodIngredient.quantity_needed);
                        if (possibleFromIngredient < maxPossible) maxPossible = possibleFromIngredient;
                    }
                    return maxPossible === Infinity ? 0 : maxPossible;
                },

                getProductQuantity(product) {
                    return product.available_quantity ?? 0;
                },

                getMaxQuantity(productId) {
                    const product = this.products.find(p => p.id === productId);
                    return product ? this.getProductQuantity(product) : 0;
                },

                async onBranchChange(branchId) {
                    if (this.selectedBranch === branchId) return;

                    this.selectedBranch = branchId;

                    try {
                        this.saveSelectedBranch();

                        await fetch(this.baseUrls.changeBranch, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ branch_id: branchId })
                        });

                        this.loadFromStorage();
                        await this.loadProductsForBranch();
                    } catch (error) {
                        console.error('Branch change error:', error);
                    }
                },

                async loadProductsForBranch() {
                    if (!this.selectedBranch) return;

                    try {
                        const url = `${this.baseUrls.searchProduct}?branch_id=${this.selectedBranch}`;
                        const response = await fetch(url);

                        if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);

                        this.products = await response.json();
                        this.prepareProducts();
                    } catch (error) {
                        console.error('Failed to load products:', error);
                        this.products = [];
                    }
                },

                async searchProducts() {
                    if (!this.selectedBranch) return;

                    try {
                        let url = `${this.baseUrls.searchProduct}?branch_id=${this.selectedBranch}`;
                        if (this.searchTerm) {
                            url += `&search=${encodeURIComponent(this.searchTerm)}`;
                        }
                        const response = await fetch(url);
                        if (!response.ok) throw new Error('Search failed.');
                        this.products = await response.json();
                        this.prepareProducts();
                    } catch (error) {
                        console.error('Failed to search for products:', error);
                    }
                },

                clearSearch() {
                    this.searchTerm = '';
                    this.searchProducts();
                },

                // Cart methods
                addToCart(product) {
                    const available = this.getProductQuantity(product);
                    if (available <= 0) {
                        this.showNotification('Product is out of stock.', 'warning');
                        return;
                    }

                    const existingItem = this.cart.find(item => item.product_id === product.id);
                    if (existingItem && existingItem.quantity >= available) {
                        this.showNotification(`Cannot add more than ${available} units.`, 'warning');
                        return;
                    }

                    if (existingItem) {
                        existingItem.quantity++;
                    } else {
                        this.cart.push({
                            product_id: product.id,
                            name: product.product_name,
                            price: parseFloat(product.selling_price),
                            quantity: 1,
                            discount: 0,
                            discount_type: 'amount',
                            from_reward: false
                        });
                    }

                    this.updateItemSubtotal(existingItem || this.cart[this.cart.length - 1]);
                },

                calculateItemDiscount(item) {
                    if (!item || !item.discount || item.discount <= 0) return 0;

                    const itemTotal = item.price * item.quantity;
                    
                    if (item.discount_type === 'percentage') {
                        const percentage = Math.min(100, Math.max(0, item.discount));
                        return Math.min(itemTotal, (itemTotal * percentage) / 100);
                    } else {
                        return Math.min(itemTotal, item.discount);
                    }
                },

                updateItemSubtotal(item) {
                    if (!item) return;
                    
                    const discountAmount = this.calculateItemDiscount(item);
                    const itemTotal = item.price * item.quantity;
                    item.sub_total = itemTotal - discountAmount;
                },

                updateItemDiscount(index) {
                    const item = this.cart[index];
                    if (!item) return;

                    if (item.discount_type === 'percentage') {
                        if (item.discount < 0) item.discount = 0;
                        if (item.discount > 100) item.discount = 100;
                    } else {
                        if (item.discount < 0) item.discount = 0;
                        const maxDiscount = item.price * item.quantity;
                        if (item.discount > maxDiscount) item.discount = maxDiscount;
                    }

                    this.updateItemSubtotal(item);
                },

                updateQuantity(index, change) {
                    const item = this.cart[index];
                    const newQuantity = item.quantity + change;

                    if (newQuantity < 1) {
                        this.removeFromCart(index);
                        return;
                    }

                    const maxQty = this.getMaxQuantity(item.product_id);
                    if (newQuantity > maxQty) {
                        this.showNotification(`Cannot add more than ${maxQty} units.`, 'warning');
                        return;
                    }

                    item.quantity = newQuantity;
                    this.updateItemSubtotal(item);
                },

                validateQuantity(index, newValue) {
                    const item = this.cart[index];
                    let newQty = parseInt(newValue);

                    if (isNaN(newQty) || newQty < 1) {
                        this.$nextTick(() => {
                            item.quantity = 1;
                            this.updateItemSubtotal(item);
                        });
                        return;
                    }

                    const maxQty = this.getMaxQuantity(item.product_id);
                    if (newQty > maxQty) {
                        this.$nextTick(() => {
                            item.quantity = maxQty;
                            this.updateItemSubtotal(item);
                        });
                        this.showNotification(`Maximum quantity is ${maxQty}.`, 'warning');
                        return;
                    }

                    item.quantity = newQty;
                    this.updateItemSubtotal(item);
                },

                updateCartSubtotals() {
                    this.cart.forEach(item => {
                        this.updateItemSubtotal(item);
                    });
                },

                removeFromCart(index) {
                    this.cart.splice(index, 1);
                },

                clearCart() {
                    this.cart = [];
                    this.showPayment = false;
                    this.saveToStorage();
                },

                // Payment methods
                getAmountPaidClasses() {
                    const amountPaid = parseFloat(this.payment.amountPaid) || 0;
                    const total = this.orderSummary.total;

                    if (amountPaid === 0) {
                        if (this.payment.method === '0') {
                            return 'border-blue-300 focus:ring-blue-500 focus:border-blue-500';
                        } else if (this.payment.method === '1') {
                            return 'border-green-300 focus:ring-green-500 focus:border-green-500';
                        } else if (this.payment.method === '2') {
                            return 'border-purple-300 focus:ring-purple-500 focus:border-purple-500';
                        }
                    }

                    if (this.payment.method === '0') {
                        if (amountPaid >= total) {
                            return 'border-green-500 bg-green-50 focus:ring-green-500 focus:border-green-500';
                        } else {
                            return 'border-red-500 bg-red-50 focus:ring-red-500 focus:border-red-500';
                        }
                    } else {
                        if (amountPaid === total) {
                            return 'border-green-500 bg-green-50 focus:ring-green-500 focus:border-green-500';
                        } else {
                            return 'border-red-500 bg-red-50 focus:ring-red-500 focus:border-red-500';
                        }
                    }
                },

                validateAmountPaid() {
                    return true;
                },

                canProcessOrder() {
                    if (this.cart.length === 0 || this.processingOrder) return false;

                    const amountPaid = parseFloat(this.payment.amountPaid) || 0;
                    const total = this.orderSummary.total;

                    switch (this.payment.method) {
                        case '0':
                            return amountPaid >= total;
                        case '1':
                        case '2':
                            return amountPaid === total;
                        case '3':
                            return this.payment.termsAccepted && this.selectedCustomerId;
                        default:
                            return false;
                    }
                },

                getProcessButtonText() {
                    const texts = {
                        '0': 'Process Cash',
                        '1': 'Process GCash',
                        '2': 'Process Debit',
                        '3': 'Process Pay Later'
                    };
                    return texts[this.payment.method] || 'Process Order';
                },

                async processOrder() {
                    if (!this.canProcessOrder()) return;
                    this.showConfirmationModal = true;
                },

                async confirmProcessOrder() {
                    if (!this.canProcessOrder()) return;

                    this.processingOrder = true;
                    try {
                        const isWalkInCustomer = !this.isCustomerFromCheckin && !this.selectedCustomerId;

                        const orderData = {
                            items: this.cart.map(item => ({
                                product_id: item.product_id,
                                quantity: item.quantity,
                                price: item.price,
                                discount: item.discount || 0,
                                discount_type: item.discount_type || 'amount'
                            })),
                            payment_method: this.payment.method,
                            amount_paid: parseFloat(this.payment.amountPaid) || 0,
                            vat_sales: this.orderSummary.vatSales,
                            vat_amount: this.orderSummary.vatAmount,
                            change: this.change,
                            notes: this.payment.notes || '',
                            gcash_ref_no: this.payment.gcashRefNo,
                            branch_id: this.selectedBranch,
                            customer: this.customer,
                            selected_customer_id: isWalkInCustomer ? null : this.selectedCustomerId,
                            booking_uuid: this.bookingReference?.booking_uuid || null,
                            booking_id: this.bookingReference?.booking_id || null,
                            booking_ref_no: this.bookingReference?.booking_ref_no || null,
                            checkin_uuid: this.bookingReference?.checkin_uuid || null,
                            checkin_id: this.bookingReference?.checkin_id || null,
                            branch_uuid: this.bookingReference?.branch_uuid || null,
                            customer_uuid: this.bookingReference?.customer_uuid || null,
                            customer_reward_id: this.customerRewardId,
                            reward_discount_amount: this.orderSummary.rewardDiscount
                        };

                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = this.baseUrls.processOrder;

                        const csrfToken = document.createElement('input');
                        csrfToken.type = 'hidden';
                        csrfToken.name = '_token';
                        csrfToken.value = '{{ csrf_token() }}';
                        form.appendChild(csrfToken);

                        const orderDataInput = document.createElement('input');
                        orderDataInput.type = 'hidden';
                        orderDataInput.name = 'order_data';
                        orderDataInput.value = JSON.stringify(orderData);
                        form.appendChild(orderDataInput);

                        document.body.appendChild(form);
                        form.submit();

                        this.resetAfterOrder();

                        setTimeout(() => {
                            this.showConfirmationModal = false;
                        }, 500);

                    } catch (error) {
                        console.error('Error processing order:', error);
                        this.showNotification('An error occurred while processing the order.', 'error');
                        this.processingOrder = false;
                    }
                },

                resetAfterOrder() {
                    this.cart = [];

                    if (!this.isCustomerFromCheckin) {
                        this.resetCustomerData();
                    }

                    if (this.bookingReference) {
                        this.bookingReference = null;
                        sessionStorage.removeItem('pos_booking_reference');

                        if (this.isCustomerFromCheckin) {
                            this.isCustomerFromCheckin = false;
                            this.resetCustomerData();
                        }
                    }

                    this.payment = {
                        method: '0',
                        amountPaid: '',
                        gcashRefNo: '',
                        termsAccepted: false,
                        notes: ''
                    };

                    // Reset reward state
                    this.availableRewards = [];
                    this.selectedReward = null;
                    this.appliedReward = null;
                    this.customerRewardId = null;
                    this.rewardDiscountAmount = 0;
                    this.rewardVoucherCode = null;
                    this.showRewardsSection = false;

                    this.searchTerm = '';
                    this.showPayment = false;

                    this.saveToStorage();
                    this.clearLocalStorage();

                    if (this.selectedBranch) {
                        this.loadProductsForBranch();
                    }

                    this.showNotification('Order processed successfully!', 'success');
                    this.processingOrder = false;
                    this.showConfirmationModal = false;
                },

                getPaymentMethodLabel() {
                    const methodMap = {
                        '0': 'Cash',
                        '1': 'GCash',
                        '2': 'Debit Card',
                        '3': 'Pay Later'
                    };
                    return methodMap[this.payment.method] || 'Unknown';
                },

                resetCustomerData() {
                    this.selectedCustomerId = null;
                    this.selectedCustomerName = '';
                    this.selectedCustomerEmail = '';
                    this.selectedCustomerContact = '';
                    this.customer = { name: '', contact: '', email: '', address: '' };
                },

                clearLocalStorage() {
                    try {
                        if (this.selectedBranch) {
                            localStorage.removeItem(`pos_cart_${this.selectedBranch}`);
                        }
                        localStorage.removeItem('pos_selected_customer_id');
                        localStorage.removeItem('pos_selected_customer_name');
                        localStorage.removeItem('pos_selected_customer_email');
                        localStorage.removeItem('pos_selected_customer_contact');
                        localStorage.removeItem('pos_customer');
                        localStorage.removeItem('pos_last_order');
                        localStorage.removeItem('pos_last_customer');
                    } catch (error) {
                        console.error('Error clearing localStorage:', error);
                    }
                },

                hasIngredientStock(ingredient) {
                    if (!ingredient) return false;

                    let totalStock = 0;

                    if (ingredient.stock_quantity_in > 0) {
                        if (ingredient.unit_conversion && ingredient.unit_conversion > 0) {
                            totalStock += ingredient.stock_quantity_in * ingredient.unit_conversion;
                        } else {
                            totalStock += ingredient.stock_quantity_in;
                        }
                    }

                    if (ingredient.converted_stock_quantity_in > 0) {
                        totalStock += ingredient.converted_stock_quantity_in;
                    }

                    return totalStock > 0;
                },

                showNotification(message, type = 'info') {
                    const notification = document.createElement('div');
                    notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white font-medium transform transition-all duration-300 ${
                        type === 'error' ? 'bg-red-500' :
                        type === 'warning' ? 'bg-yellow-500' :
                        type === 'success' ? 'bg-green-500' :
                        'bg-blue-500'
                    }`;
                    notification.textContent = message;

                    document.body.appendChild(notification);

                    setTimeout(() => {
                        notification.style.opacity = '0';
                        notification.style.transform = 'translateY(-20px)';
                        setTimeout(() => {
                            document.body.removeChild(notification);
                        }, 300);
                    }, 3000);

                    console.log(`[${type.toUpperCase()}] ${message}`);
                }
            };
        }
    </script>
@endsection