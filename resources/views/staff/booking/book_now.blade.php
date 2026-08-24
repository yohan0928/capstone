@extends('layouts.app')

@section('content')
    <div class="max-w-6xl mx-auto mt-6 p-4 md:p-6" x-data="bookingForm()">
        <!-- Header Section -->
        <div class="text-center mb-8">
            <h1 class="text-4xl sm:text-5xl font-serif font-bold text-coffee-900 mb-3">
                Book Now
            </h1>
            <p class="text-lg text-coffee-700">
                Reserve your perfect spot for an unforgettable experience
            </p>
        </div>

        <form id="bookingForm" method="POST" action="{{ route('sub_two.book_now.store') }}" class="space-y-8">
            @csrf
            <input type="hidden" name="generated_password" x-model="generatedPassword">
            <input type="hidden" name="customer_reward_id" x-model="customerRewardId">
            <input type="hidden" name="reward_discount_amount" x-model="rewardDiscountAmount">
            <input type="hidden" name="reward_voucher_code" x-model="rewardVoucherCode">

            <!-- Customer Information -->
            <div class="bg-coffee-50 rounded-2xl border border-coffee-100 p-6 sm:p-8 mb-4 shadow-sm">
                <div class="text-center mb-8">
                    <h3 class="text-2xl font-serif font-semibold text-coffee-900 mb-2">
                        Customer Information
                    </h3>
                    <div class="h-1 w-16 bg-gradient-to-r from-coffee-400 to-coffee-600 rounded-full mx-auto"></div>
                </div>

                <!-- Customer Selection Button -->
                <div class="mb-6">
                    <button type="button"
                        class="w-full px-8 py-3 bg-gradient-to-r from-coffee-600 to-coffee-800 text-white rounded-xl font-medium shadow-lg hover:shadow-xl transition-all duration-300 hover:from-coffee-700 hover:to-coffee-900 focus:outline-none focus:ring-2 focus:ring-coffee-400 focus:ring-offset-2"
                        @click="showCustomerModal = true">
                        <span class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Select Customer
                        </span>
                    </button>

                    <div x-show="selectedCustomer.first_name"
                        class="mt-6 p-6 bg-white rounded-xl border border-coffee-200 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-coffee-900 text-lg">
                                Selected Customer:
                            </span>
                            <button type="button" class="text-coffee-700 hover:text-coffee-900 transition-colors"
                                @click="clearSelectedCustomer()">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="mt-3 text-lg font-medium text-coffee-800"
                            x-text="selectedCustomer.first_name + ' ' + selectedCustomer.last_name">
                        </div>

                        <div class="mt-2 flex items-center text-sm text-coffee-600">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                            </svg>
                            <span x-text="selectedCustomer.email"></span>
                        </div>

                        <div class="mt-2 flex items-center text-sm text-coffee-600">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                            </svg>
                            <span x-text="selectedCustomer.contact_no"></span>
                        </div>

                        <div class="mt-3">
                            <span class="text-xs bg-coffee-100 text-coffee-700 px-3 py-1 rounded-full" 
                                  x-text="selectedCustomer.id ? 'Returning Customer' : 'New Customer'">
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Hidden fields for form submission -->
                <input type="hidden" name="first_name" x-model="selectedCustomer.first_name">
                <input type="hidden" name="last_name" x-model="selectedCustomer.last_name">
                <input type="hidden" name="email" x-model="selectedCustomer.email">
                <input type="hidden" name="contact_no" x-model="selectedCustomer.contact_no">
                <input type="hidden" name="customer_account_id" x-bind:value="selectedCustomer.id">
            </div>

            <!-- ===== REWARD SECTION - Shows when customer AND branch are selected ===== -->
            <div class="bg-coffee-50 rounded-2xl border border-coffee-100 p-6 sm:p-8 mb-4 shadow-sm" 
                 x-show="selectedCustomer.id && selectedBranch" 
                 x-cloak>
                <div class="text-center mb-6">
                    <h3 class="text-2xl font-serif font-semibold text-coffee-900 mb-2">
                        🎁 Available Rewards
                    </h3>
                    <div class="h-1 w-16 bg-gradient-to-r from-coffee-400 to-coffee-600 rounded-full mx-auto"></div>
                    <p class="text-sm text-coffee-600 mt-2" 
                       x-text="rewardsLoading ? 'Loading your rewards...' : 
                               (availableRewards.length > 0 ? 'You have ' + availableRewards.length + ' reward(s) available!' : 'No rewards available for this branch')">
                    </p>
                </div>

                <!-- Rewards List -->
                <div x-show="availableRewards.length > 0 && !rewardsLoading" x-cloak>
                    <div class="space-y-3 max-h-60 overflow-y-auto pr-2">
                        <template x-for="reward in availableRewards" :key="reward.id">
                            <div class="bg-white rounded-xl p-4 border-2 transition-all duration-300 cursor-pointer hover:shadow-md"
                                 :class="selectedReward && selectedReward.id === reward.id ? 
                                         'border-coffee-600 shadow-md' : 
                                         'border-coffee-200 hover:border-coffee-400'"
                                 @click="toggleRewardSelection(reward)">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-medium text-coffee-900" x-text="reward.description || 'Reward'"></span>
                                            <span x-show="reward.days_left !== null && reward.days_left !== 'N/A'" 
                                                  class="text-xs bg-coffee-100 text-coffee-700 px-2 py-1 rounded-full">
                                                <span x-text="reward.days_left"></span> days left
                                            </span>
                                            <span x-show="reward.is_percentage" 
                                                  class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded-full">
                                                <span x-text="reward.percentage + '% off'"></span>
                                            </span>
                                        </div>
                                        <div class="text-sm text-coffee-600 mt-1" x-text="reward.item_name || ''"></div>
                                        <div class="text-xs text-coffee-500 mt-1 font-mono" x-text="'Voucher: ' + (reward.voucher_code || 'N/A')"></div>
                                    </div>
                                    <div class="text-right ml-4">
                                        <div class="font-bold text-green-600" x-text="reward.discount_display || '₱0.00'"></div>
                                        <div class="text-xs text-coffee-500" x-text="'Expires: ' + (reward.expiration_date || 'No expiry')"></div>
                                    </div>
                                </div>
                                <!-- Selection indicator -->
                                <div x-show="selectedReward && selectedReward.id === reward.id" 
                                     class="mt-2 text-xs text-coffee-600 flex items-center gap-1">
                                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Selected - Will be applied to this booking
                                </div>
                            </div>
                        </template>
                    </div>
                    
                    <!-- Apply Reward Button -->
                    <div class="mt-4 flex justify-end">
                        <button type="button"
                            class="px-6 py-2 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl font-medium hover:from-green-700 hover:to-green-800 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                            @click="applySelectedReward()"
                            :disabled="!selectedReward || rewardApplying">
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
                    
                    <!-- Applied Reward Display -->
                    <div x-show="appliedReward" x-cloak class="mt-4 p-4 bg-green-50 border border-green-200 rounded-xl">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-sm font-medium text-green-800">✅ Reward Applied</span>
                                <p class="text-sm text-green-700 mt-1" x-text="appliedReward ? appliedReward.description : ''"></p>
                                <p class="text-xs text-green-600" x-text="appliedReward ? 'Discount: ₱' + parseFloat(appliedReward.discount_value || 0).toFixed(2) : ''"></p>
                            </div>
                            <button type="button" 
                                    class="text-sm text-red-600 hover:text-red-800"
                                    @click="removeAppliedReward()">
                                Remove
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div x-show="rewardsLoading" x-cloak class="text-center py-4">
                    <svg class="animate-spin h-8 w-8 text-coffee-600 mx-auto" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-coffee-600 mt-2">Loading your available rewards...</p>
                </div>

                <!-- No Rewards Message -->
                <div x-show="!rewardsLoading && availableRewards.length === 0 && selectedCustomer.id" x-cloak>
                    <div class="text-center py-4 text-coffee-600">
                        <svg class="h-12 w-12 text-coffee-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        <p class="text-sm">No rewards available for this branch.</p>
                        <p class="text-xs text-coffee-400 mt-1">Complete more bookings to earn rewards!</p>
                    </div>
                </div>
            </div>

            <!-- Service Information -->
            <div class="bg-coffee-50 rounded-2xl border border-coffee-100 p-6 sm:p-8 mb-4 shadow-sm">
                <div class="text-center mb-8">
                    <h3 class="text-2xl font-serif font-semibold text-coffee-900 mb-2">
                        Service Information
                    </h3>
                    <div class="h-1 w-16 bg-gradient-to-r from-coffee-400 to-coffee-600 rounded-full mx-auto"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Branch Display (Read-only for staff) -->
                    <div>
                        <label class="block text-sm font-medium text-coffee-900 mb-2">
                            Branch
                        </label>
                        <div class="w-full px-4 py-3 border-2 border-coffee-200 rounded-xl bg-coffee-50 text-coffee-700">
                            @if ($branch->count() > 0)
                                @php
                                    $staffBranch = $branch->first();
                                @endphp
                                <div class="flex items-center justify-between">
                                    <span class="font-medium">{{ $staffBranch->branch_name }}</span>
                                    <svg class="w-5 h-5 text-coffee-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <input type="hidden" name="branch_id" value="{{ $staffBranch->id }}">
                            @else
                                <div class="text-coffee-600 italic">
                                    <span class="font-medium">No branch assigned</span>
                                    <p class="text-sm mt-1">Please contact administrator</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Service Category -->
                    <div>
                        <label for="service_category_id" class="block text-sm font-medium text-coffee-900 mb-2">
                            Service Category <span class="text-red-500">*</span>
                        </label>
                        <select
                            class="w-full px-4 py-3 border-2 border-coffee-200 rounded-xl bg-white focus:border-coffee-500 focus:ring-2 focus:ring-coffee-200 focus:ring-opacity-50 transition-all duration-300"
                            id="service_category_id" name="service_category_id" x-model="selectedCategory"
                            @change="onCategoryChange()" required>
                            <option value="" disabled>Select service category</option>
                            <template x-for="category in availableCategories" :key="category.id">
                                <option :value="category.id" x-text="category.service_category"></option>
                            </template>
                            <option value="" x-show="availableCategories.length === 0" disabled>
                                No categories available
                            </option>
                        </select>
                    </div>

                    <!-- Service -->
                    <div>
                        <label for="service_name_id" class="block text-sm font-medium text-coffee-900 mb-2">
                            Service <span class="text-red-500">*</span>
                        </label>
                        <select 
                            class="w-full px-4 py-3 border-2 border-coffee-200 rounded-xl bg-white focus:border-coffee-500 focus:ring-2 focus:ring-coffee-200 focus:ring-opacity-50 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                            id="service_name_id" 
                            name="service_name_id" 
                            x-model="selectedService" 
                            @change="onServiceChange()"
                            :disabled="!availableServices.length"
                            required>
                            <option value="" disabled x-text="selectedCategory ? 'Select a service' : 'Select a category first'"></option>
                            <template x-for="service in availableServices" :key="service.id">
                                <option :value="service.id" 
                                    x-text="service.service_name + ' - ₱' + service.price + ' (' + service.time_duration + ')'">
                                </option>
                            </template>
                        </select>
                        <div x-show="availableServices.length > 0" class="mt-2 text-sm text-coffee-600 italic">
                            <span x-text="availableServices.length + ' hourly service(s) available'"></span>
                            <div x-show="selectedServiceData" class="mt-1 text-coffee-700">
                                <span x-text="selectedServiceData.space_type"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Seat/Room Preference -->
                    <div>
                        <label for="seat_id" class="block text-sm font-medium text-coffee-900 mb-2">
                            Seat/Room Preference <span class="text-red-500">*</span>
                        </label>
                        <select
                            class="w-full px-4 py-3 border-2 border-coffee-200 rounded-xl bg-white focus:border-coffee-500 focus:ring-2 focus:ring-coffee-200 focus:ring-opacity-50 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                            id="seat_id" name="seat_id" x-model="selectedSeat" :disabled="!availableSeats.length"
                            required>
                            <option value="" disabled x-text="selectedCategory ? 'Select a seat/room' : 'Select service category first'"></option>
                            <template x-for="seat in availableSeats" :key="seat.id">
                                <option :value="seat.id"
                                    :class="{
                                        'font-bold text-coffee-800': seat.room_no,
                                        'font-normal': seat.seat_no && !seat.room_no
                                    }"
                                    x-text="getSeatDisplayName(seat)">
                                </option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="bg-coffee-50 rounded-2xl border border-coffee-100 p-6 sm:p-8 mb-4 shadow-sm">
                <div class="text-center mb-8">
                    <h3 class="text-2xl font-serif font-semibold text-coffee-900 mb-2">
                        Payment Information
                    </h3>
                    <div class="h-1 w-16 bg-gradient-to-r from-coffee-400 to-coffee-600 rounded-full mx-auto"></div>
                    <!-- Show discount applied -->
                    <div x-show="appliedReward" x-cloak class="mt-2 text-sm text-green-600 font-medium">
                        ✓ Reward discount applied: -₱<span x-text="appliedReward ? parseFloat(appliedReward.discount_value || 0).toFixed(2) : '0.00'"></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Payment Method -->
                    <div>
                        <label for="payment_method" class="block text-sm font-medium text-coffee-900 mb-2">
                            Payment Method <span class="text-red-500">*</span>
                        </label>
                        <select
                            class="w-full px-4 py-3 border-2 border-coffee-200 rounded-xl bg-white focus:border-coffee-500 focus:ring-2 focus:ring-coffee-200 focus:ring-opacity-50 transition-all duration-300"
                            id="payment_method" name="payment_method" x-model="paymentMethod"
                            @change="onPaymentMethodChange()" required>
                            <option value="" disabled>Select payment method</option>
                            <option value="0">Cash</option>
                            <option value="1">GCash</option>
                            <option value="2">Debit Card</option>
                            <option value="3">Pay Later</option>
                        </select>
                    </div>

                    <!-- Total Amount -->
                    <div>
                        <label for="total_amount" class="block text-sm font-medium text-coffee-900 mb-2">
                            Total Amount <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-coffee-700 font-semibold">
                                ₱
                            </span>
                            <input type="number" step="0.01"
                                class="w-full pl-10 pr-4 py-3 border-2 border-coffee-200 rounded-xl bg-white focus:border-coffee-500 focus:ring-2 focus:ring-coffee-200 focus:ring-opacity-50 transition-all duration-300"
                                id="total_amount" name="total_amount" x-model="totalAmount" placeholder="0.00" readonly>
                        </div>
                        <p class="mt-2 text-sm text-coffee-600 italic" x-text="priceDescription"></p>
                    </div>

                    <!-- Cash Payment Fields -->
                    <template x-if="paymentMethod === '0'">
                        <div>
                            <label for="amount_paid" class="block text-sm font-medium text-coffee-900 mb-2">
                                Amount Paid <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-coffee-700 font-semibold">
                                    ₱
                                </span>
                                <input type="number" step="0.01"
                                    class="w-full pl-10 pr-4 py-3 border-2 border-coffee-200 rounded-xl bg-white focus:border-coffee-500 focus:ring-2 focus:ring-coffee-200 focus:ring-opacity-50 transition-all duration-300"
                                    id="amount_paid" name="amount_paid" x-model="amountPaid" @input="calculateChange()"
                                    placeholder="0.00" required>
                            </div>
                            <p x-show="showPaymentError" class="mt-2 text-sm text-red-600">
                                Amount paid must be greater than or equal to total amount
                            </p>
                        </div>
                    </template>

                    <!-- GCash/Debit Card Payment Fields -->
                    <template x-if="paymentMethod === '1' || paymentMethod === '2'">
                        <div>
                            <input type="hidden" name="amount_paid" x-model="totalAmount">
                            <label for="notes" class="block text-sm font-medium text-coffee-900 mb-2">
                                Notes <span class="text-coffee-600">(Optional)</span>
                                <span class="text-xs text-coffee-500 font-normal ml-2">
                                    <span x-text="paymentNotes.length"></span>/500 characters
                                </span>
                            </label>
                            <div class="relative">
                                <input type="text"
                                    class="w-full px-4 py-3 border-2 border-coffee-200 rounded-xl bg-white focus:border-coffee-500 focus:ring-2 focus:ring-coffee-200 focus:ring-opacity-50 transition-all duration-300"
                                    id="notes" name="notes" x-model="paymentNotes" @input="limitNotesLength()"
                                    placeholder="Enter a notes to this payment..."
                                    maxlength="500">
                            </div>
                            <p class="mt-2 text-sm text-coffee-600">
                                <template x-if="paymentMethod === '1'">
                                    Enter GCash reference number or transaction details
                                </template>
                                <template x-if="paymentMethod === '2'">
                                    Enter debit card transaction details
                                </template>
                            </p>
                        </div>
                    </template>

                    <!-- Change Field (Only for Cash) -->
                    <template x-if="paymentMethod === '0'">
                        <div>
                            <label for="change" class="block text-sm font-medium text-coffee-900 mb-2">
                                Change
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-coffee-700 font-semibold">
                                    ₱
                                </span>
                                <input type="number" step="0.01"
                                    class="w-full pl-10 pr-4 py-3 border-2 border-coffee-200 rounded-xl bg-white focus:border-coffee-500 focus:ring-2 focus:ring-coffee-200 focus:ring-opacity-50 transition-all duration-300"
                                    id="change" name="change" x-model="changeAmount" placeholder="0.00" readonly>
                            </div>
                        </div>
                    </template>

                    <!-- Note Field (For Pay Later) -->
                    <template x-if="paymentMethod === '3'">
                        <div>
                            <label for="notes" class="block text-sm font-medium text-coffee-900 mb-2">
                                Payment Terms <span class="text-coffee-600">(Optional)</span>
                            </label>
                            <textarea
                                class="w-full px-4 py-3 border-2 border-coffee-200 rounded-xl bg-white focus:border-coffee-500 focus:ring-2 focus:ring-coffee-200 focus:ring-opacity-50 transition-all duration-300"
                                id="notes" name="notes" x-model="paymentNotes" rows="2"
                                placeholder="Add payment terms or notes..."></textarea>
                            <p class="mt-1 text-sm text-coffee-600">
                                Add any notes about the payment terms
                            </p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex flex-col sm:flex-row justify-center gap-4 pt-8">
                <button type="button"
                    class="px-8 py-3 border-2 border-coffee-600 text-coffee-700 rounded-xl font-medium hover:bg-coffee-50 hover:border-coffee-700 hover:text-coffee-800 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-coffee-400 focus:ring-offset-2"
                    @click="resetForm()">
                    Reset
                </button>
                <button type="button"
                    class="px-8 py-3 bg-gradient-to-r from-coffee-600 to-coffee-800 text-white rounded-xl font-medium hover:from-coffee-700 hover:to-coffee-900 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300 shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-coffee-400 focus:ring-offset-2"
                    @click="showConfirmationModal()"
                    :disabled="!isFormValid() || submitting">
                    <span x-show="!submitting">Review Booking</span>
                    <span x-show="submitting" class="flex items-center justify-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        Processing...
                    </span>
                </button>
            </div>
        </form>

        <!-- Customer Selection Modal -->
        <div x-show="showCustomerModal" x-cloak x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 z-[9999] overflow-y-auto px-4 py-4 sm:px-0 sm:py-0">
            <div class="flex items-center justify-center min-h-full p-4 sm:p-6 text-center sm:p-0">
                <!-- Backdrop -->
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-900 opacity-75" @click="showCustomerModal = false"></div>
                </div>

                <!-- Modal Content -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl w-full p-4 sm:p-0"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                    <!-- Header -->
                    <div class="bg-gradient-to-r from-coffee-600 to-coffee-800 px-6 py-4 sm:px-6 sm:py-4">
                        <div class="flex justify-between items-center">
                            <h3 class="text-xl font-serif font-semibold text-white">
                                Select Customer
                            </h3>
                            <button @click="showCustomerModal = false"
                                class="text-white hover:text-coffee-200 transition-colors focus:outline-none focus:ring-2 focus:ring-coffee-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="px-4 pt-5 pb-4 sm:p-6">
                        <!-- Customer Type Selection -->
                        <div class="mb-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <button type="button"
                                    :class="{
                                        'bg-gradient-to-r from-coffee-600 to-coffee-800 text-white shadow-lg': customerModalType === 'new',
                                        'bg-white text-coffee-700 border-coffee-200 hover:bg-coffee-50': customerModalType !== 'new'
                                    }"
                                    class="p-4 border-2 rounded-xl font-medium focus:outline-none focus:ring-2 focus:ring-coffee-400 transition-all duration-300"
                                    @click="customerModalType = 'new'">
                                    New Customer
                                </button>
                                <button type="button"
                                    :class="{
                                        'bg-gradient-to-r from-coffee-600 to-coffee-800 text-white shadow-lg': customerModalType === 'returning',
                                        'bg-white text-coffee-700 border-coffee-200 hover:bg-coffee-50': customerModalType !== 'returning'
                                    }"
                                    class="p-4 border-2 rounded-xl font-medium focus:outline-none focus:ring-2 focus:ring-coffee-400 transition-all duration-300"
                                    @click="customerModalType = 'returning'; loadReturningCustomers()">
                                    Returning Customer
                                </button>
                            </div>
                        </div>

                        <!-- New Customer Form -->
                        <div x-show="customerModalType === 'new'" x-cloak>
                            <h4 class="text-lg font-semibold text-coffee-900 border-b border-coffee-200 pb-2 mb-4">
                                New Customer Information
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-coffee-900 mb-2">
                                        First Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                        class="w-full px-4 py-3 border-2 border-coffee-200 rounded-xl bg-white focus:border-coffee-500 focus:ring-2 focus:ring-coffee-200 transition-all duration-300"
                                        x-model="modalCustomer.first_name" placeholder="Enter first name">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-coffee-900 mb-2">
                                        Last Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                        class="w-full px-4 py-3 border-2 border-coffee-200 rounded-xl bg-white focus:border-coffee-500 focus:ring-2 focus:ring-coffee-200 transition-all duration-300"
                                        x-model="modalCustomer.last_name" placeholder="Enter last name">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-coffee-900 mb-2">
                                        Email <span class="text-red-500">*</span>
                                    </label>
                                    <input type="email"
                                        class="w-full px-4 py-3 border-2 border-coffee-200 rounded-xl bg-white focus:border-coffee-500 focus:ring-2 focus:ring-coffee-200 transition-all duration-300"
                                        x-model="modalCustomer.email" placeholder="your.email@example.com">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-coffee-900 mb-2">
                                        Contact Number <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                        class="w-full px-4 py-3 border-2 border-coffee-200 rounded-xl bg-white focus:border-coffee-500 focus:ring-2 focus:ring-coffee-200 transition-all duration-300"
                                        x-model="modalCustomer.contact_no" placeholder="+1 234 567 8900">
                                </div>
                            </div>
                        </div>

                        <!-- Returning Customer Section -->
                        <div x-show="customerModalType === 'returning'" x-cloak>
                            <h4 class="text-lg font-semibold text-coffee-900 border-b border-coffee-200 pb-2 mb-4">
                                Returning Customers
                            </h4>

                            <!-- Search Bar -->
                            <div class="relative mb-4">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-coffee-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <input type="text"
                                    class="w-full pl-10 pr-4 py-3 border-2 border-coffee-200 rounded-xl bg-white focus:border-coffee-500 focus:ring-2 focus:ring-coffee-200 transition-all duration-300"
                                    x-model="customerSearch" placeholder="Search customers by name or email...">
                            </div>

                            <!-- Customers Table -->
                            <div class="overflow-x-auto rounded-xl border border-coffee-200 max-h-96 overflow-y-auto">
                                <table class="min-w-full divide-y divide-coffee-200">
                                    <thead class="bg-coffee-50 sticky top-0">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-coffee-900 uppercase tracking-wider">
                                                Customer Name
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-coffee-900 uppercase tracking-wider">
                                                Email
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-coffee-900 uppercase tracking-wider">
                                                Contact Number
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-coffee-900 uppercase tracking-wider">
                                                Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-coffee-200">
                                        <template x-for="customer in filteredReturningCustomers" :key="customer.id">
                                            <tr :class="{ 'bg-coffee-50': selectedModalCustomer?.id === customer.id }"
                                                class="hover:bg-coffee-50 transition-colors">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-coffee-900">
                                                    <span x-text="customer.first_name + ' ' + customer.last_name"></span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-coffee-600">
                                                    <span x-text="customer.email"></span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-coffee-600">
                                                    <span x-text="customer.contact_no"></span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    <button type="button"
                                                        class="px-4 py-2 bg-gradient-to-r from-coffee-600 to-coffee-800 text-white rounded-lg hover:from-coffee-700 hover:to-coffee-900 transition-colors focus:outline-none focus:ring-2 focus:ring-coffee-400"
                                                        @click="selectModalCustomer(customer)">
                                                        Select
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                        <tr x-show="returningCustomers.length === 0">
                                            <td colspan="4" class="px-6 py-12 text-center text-coffee-600 italic">
                                                No returning customers found.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="bg-coffee-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-coffee-200 gap-3">
                        <button type="button"
                            class="w-full inline-flex justify-center rounded-xl border shadow-sm px-4 py-2 bg-gradient-to-r from-coffee-600 to-coffee-800 text-base font-medium text-white hover:from-coffee-700 hover:to-coffee-900 focus:outline-none focus:ring-2 focus:ring-coffee-400 sm:ml-3 sm:w-auto sm:text-sm transition-all duration-300"
                            x-show="customerModalType === 'new'" @click="selectNewCustomer()">
                            Select Customer
                        </button>
                        <button type="button"
                            class="w-full inline-flex justify-center rounded-xl border shadow-sm px-4 py-2 bg-gradient-to-r from-coffee-600 to-coffee-800 text-base font-medium text-white hover:from-coffee-700 hover:to-coffee-900 focus:outline-none focus:ring-2 focus:ring-coffee-400 disabled:opacity-50 disabled:cursor-not-allowed sm:ml-3 sm:w-auto sm:text-sm transition-all duration-300"
                            x-show="customerModalType === 'returning'" @click="selectReturningCustomer()"
                            :disabled="!selectedModalCustomer">
                            Select Customer
                        </button>
                        <button type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-xl border border-coffee-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-coffee-700 hover:bg-coffee-50 focus:outline-none focus:ring-2 focus:ring-coffee-400 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-all duration-300"
                            @click="showCustomerModal = false">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking Confirmation Modal -->
        <div x-show="showConfirmation" x-cloak x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 z-[9999] overflow-y-auto">
            <div class="flex items-center justify-center px-4 pt-4 pb-20 text-center sm:p-0">
                <!-- Backdrop -->
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-900 opacity-75" @click="showConfirmation = false"></div>
                </div>

                <!-- Modal Content -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full max-h-[85vh]"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                    <!-- Header -->
                    <div class="bg-gradient-to-r from-coffee-600 to-coffee-800 px-6 py-4 sm:px-6 sm:py-4">
                        <div class="flex justify-between items-center">
                            <h3 class="text-xl font-serif font-semibold text-white">
                                Booking Confirmation
                            </h3>
                            <button @click="showConfirmation = false"
                                class="text-white hover:text-coffee-200 transition-colors focus:outline-none focus:ring-2 focus:ring-coffee-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Body - Scrollable Content -->
                    <div class="px-4 pt-5 pb-4 sm:p-6 overflow-y-auto max-h-[60vh]">
                        <div class="space-y-6">
                            <!-- Customer Information -->
                            <div>
                                <h4 class="text-lg font-semibold text-coffee-900 mb-3">Customer Information</h4>
                                <div class="bg-coffee-50 rounded-xl p-4 space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-coffee-700 font-medium">Name:</span>
                                        <span class="text-coffee-900 font-medium text-right" x-text="selectedCustomer.first_name + ' ' + selectedCustomer.last_name"></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-coffee-700 font-medium">Email:</span>
                                        <span class="text-coffee-900 text-right truncate max-w-[200px]" x-text="selectedCustomer.email"></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-coffee-700 font-medium">Contact:</span>
                                        <span class="text-coffee-900 text-right" x-text="selectedCustomer.contact_no"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Service Information -->
                            <div>
                                <h4 class="text-lg font-semibold text-coffee-900 mb-3">Service Information</h4>
                                <div class="bg-coffee-50 rounded-xl p-4 space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-coffee-700 font-medium">Branch:</span>
                                        <span class="text-coffee-900 text-right" x-text="getSelectedBranchName()"></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-coffee-700 font-medium">Category:</span>
                                        <span class="text-coffee-900 text-right" x-text="getSelectedCategoryName()"></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-coffee-700 font-medium">Service:</span>
                                        <span class="text-coffee-900 text-right max-w-[250px] truncate" x-text="getSelectedServiceName()"></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-coffee-700 font-medium">Seat/Room:</span>
                                        <span class="text-coffee-900 text-right" x-text="getSelectedSeatName()"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- ===== REWARD INFORMATION IN CONFIRMATION ===== -->
                            <div x-show="appliedReward" x-cloak>
                                <h4 class="text-lg font-semibold text-green-700 mb-3">🎁 Reward Applied</h4>
                                <div class="bg-green-50 border border-green-200 rounded-xl p-4 space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-green-700 font-medium">Reward:</span>
                                        <span class="text-green-900 text-right" x-text="appliedReward ? appliedReward.description : ''"></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-green-700 font-medium">Discount:</span>
                                        <span class="text-green-900 text-right font-bold">-₱<span x-text="appliedReward ? parseFloat(appliedReward.discount_value || 0).toFixed(2) : '0.00'"></span></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-green-700 font-medium">Voucher:</span>
                                        <span class="text-green-900 text-right font-mono text-sm" x-text="appliedReward ? appliedReward.voucher_code : 'N/A'"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Information -->
                            <div>
                                <h4 class="text-lg font-semibold text-coffee-900 mb-3">Payment Information</h4>
                                <div class="bg-coffee-50 rounded-xl p-4 space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-coffee-700 font-medium">Method:</span>
                                        <span class="text-coffee-900 text-right" x-text="getPaymentMethodName()"></span>
                                    </div>
                                    <div class="flex justify-between items-center" 
                                         :class="appliedReward ? 'text-green-600' : ''">
                                        <span class="text-coffee-700 font-medium">Total Amount:</span>
                                        <span class="text-coffee-900 text-right">₱<span x-text="totalAmount"></span></span>
                                    </div>
                                    <div x-show="appliedReward" class="flex justify-between items-center text-sm text-green-600 border-t border-green-200 pt-2 mt-2">
                                        <span class="font-medium">Original Price:</span>
                                        <span>₱<span x-text="appliedReward ? parseFloat(totalAmount) + parseFloat(appliedReward.discount_value || 0) : totalAmount"></span></span>
                                    </div>
                                    
                                    <!-- Cash Payment Details -->
                                    <template x-if="paymentMethod === '0'">
                                        <div class="space-y-3">
                                            <div class="flex justify-between items-center">
                                                <span class="text-coffee-700 font-medium">Amount Paid:</span>
                                                <span class="text-coffee-900 text-right">₱<span x-text="amountPaid"></span></span>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <span class="text-coffee-700 font-medium">Change:</span>
                                                <span class="text-coffee-900 text-right">₱<span x-text="changeAmount"></span></span>
                                            </div>
                                        </div>
                                    </template>
                                    
                                    <!-- GCash/Debit Card Reference -->
                                    <template x-if="(paymentMethod === '1' || paymentMethod === '2') && paymentNotes">
                                        <div class="flex justify-between items-center">
                                            <span class="text-coffee-700 font-medium">Notes:</span>
                                            <span class="text-coffee-900 text-right max-w-[250px] truncate" x-text="paymentNotes"></span>
                                        </div>
                                    </template>
                                    
                                    <!-- Pay Later Terms -->
                                    <template x-if="paymentMethod === '3' && paymentNotes">
                                        <div class="flex justify-between items-start">
                                            <span class="text-coffee-700 font-medium pt-1">Payment Terms:</span>
                                            <span class="text-coffee-900 text-right max-w-[250px] text-sm" x-text="paymentNotes"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- New Customer Note -->
                            <template x-if="!selectedCustomer.id">
                                <div class="bg-coffee-50 border border-coffee-200 rounded-xl p-4">
                                    <div class="flex items-start">
                                        <svg class="w-5 h-5 text-coffee-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                        <div>
                                            <p class="text-sm text-coffee-700 leading-relaxed">
                                                This is a new customer. A temporary password will be generated and sent to their email along with the booking confirmation.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="bg-coffee-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-coffee-200 gap-3">
                        <button type="button"
                            class="w-full inline-flex justify-center rounded-xl border shadow-sm px-4 py-2 bg-gradient-to-r from-coffee-600 to-coffee-800 text-base font-medium text-white hover:from-coffee-700 hover:to-coffee-900 focus:outline-none focus:ring-2 focus:ring-coffee-400 sm:ml-3 sm:w-auto sm:text-sm transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                            @click="submitForm()" :disabled="submitting">
                            <span x-show="!submitting" class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Confirm & Book Now
                            </span>
                            <span x-show="submitting" class="flex items-center justify-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Processing...
                            </span>
                        </button>
                        <button type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-xl border border-coffee-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-coffee-700 hover:bg-coffee-50 focus:outline-none focus:ring-2 focus:ring-coffee-400 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                            @click="showConfirmation = false" :disabled="submitting">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] {
            display: none !important;
        }
        
        /* Coffee Pastel Color Palette */
        .bg-coffee-50 { background-color: #f5f0eb; }
        .bg-coffee-100 { background-color: #e6ddd4; }
        .bg-coffee-200 { background-color: #d4c4b2; }
        .bg-coffee-300 { background-color: #c2ab90; }
        .bg-coffee-400 { background-color: #b08968; }
        .bg-coffee-500 { background-color: #9c6644; }
        .bg-coffee-600 { background-color: #7f5539; }
        .bg-coffee-700 { background-color: #6b4f3c; }
        .bg-coffee-800 { background-color: #5c4033; }
        .bg-coffee-900 { background-color: #4a3429; }
        
        .text-coffee-50 { color: #f5f0eb; }
        .text-coffee-100 { color: #e6ddd4; }
        .text-coffee-200 { color: #d4c4b2; }
        .text-coffee-300 { color: #c2ab90; }
        .text-coffee-400 { color: #b08968; }
        .text-coffee-500 { color: #9c6644; }
        .text-coffee-600 { color: #7f5539; }
        .text-coffee-700 { color: #6b4f3c; }
        .text-coffee-800 { color: #5c4033; }
        .text-coffee-900 { color: #4a3429; }
        
        .border-coffee-100 { border-color: #e6ddd4; }
        .border-coffee-200 { border-color: #d4c4b2; }
        .border-coffee-300 { border-color: #c2ab90; }
        .border-coffee-400 { border-color: #b08968; }
        .border-coffee-500 { border-color: #9c6644; }
        .border-coffee-600 { border-color: #7f5539; }
        .border-coffee-700 { border-color: #6b4f3c; }
        .border-coffee-800 { border-color: #5c4033; }
        .border-coffee-900 { border-color: #4a3429; }
        
        .ring-coffee-200 { --tw-ring-color: #d4c4b2; }
        .ring-coffee-400 { --tw-ring-color: #b08968; }
        
        .focus\:ring-coffee-200:focus { --tw-ring-color: #d4c4b2; }
        .focus\:ring-coffee-400:focus { --tw-ring-color: #b08968; }
        
        .from-coffee-400 { --tw-gradient-from: #b08968; }
        .from-coffee-600 { --tw-gradient-from: #7f5539; }
        .from-coffee-700 { --tw-gradient-from: #6b4f3c; }
        
        .to-coffee-600 { --tw-gradient-to: #7f5539; }
        .to-coffee-800 { --tw-gradient-to: #5c4033; }
        .to-coffee-900 { --tw-gradient-to: #4a3429; }
        
        .hover\:from-coffee-600:hover { --tw-gradient-from: #7f5539; }
        .hover\:from-coffee-700:hover { --tw-gradient-from: #6b4f3c; }
        
        .hover\:to-coffee-800:hover { --tw-gradient-to: #5c4033; }
        .hover\:to-coffee-900:hover { --tw-gradient-to: #4a3429; }
        
        .divide-coffee-200 > :not([hidden]) ~ :not([hidden]) {
            border-color: #d4c4b2;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f5f0eb;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #d4a574;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #b08968;
        }
    </style>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('bookingForm', () => ({
                // Customer Data
                selectedCustomer: {
                    id: null,
                    first_name: '{{ old('first_name') }}',
                    last_name: '{{ old('last_name') }}',
                    email: '{{ old('email') }}',
                    contact_no: '{{ old('contact_no') }}'
                },
                showCustomerModal: false,
                customerModalType: 'new',
                modalCustomer: {
                    first_name: '',
                    last_name: '',
                    email: '',
                    contact_no: ''
                },
                returningCustomers: [],
                customerSearch: '',
                selectedModalCustomer: null,
                
                // ===== REWARD DATA =====
                availableRewards: [],
                selectedReward: null,
                appliedReward: null,
                rewardsLoading: false,
                rewardApplying: false,
                customerRewardId: null,
                rewardDiscountAmount: 0,
                rewardVoucherCode: null,
                
                // Service Data
                selectedBranch: '{{ $branch->count() > 0 ? $branch->first()->id : '' }}',
                selectedCategory: '{{ old('service_category_id') }}',
                selectedService: '{{ old('service_name_id') }}',
                selectedServiceData: null,
                selectedSeat: '{{ old('seat_id') }}',
                availableCategories: [],
                availableServices: [],
                availableSeats: [],
                
                // Payment Data
                paymentMethod: '{{ old('payment_method') }}',
                paymentNotes: '{{ old('notes', '') }}',
                totalAmount: {{ old('total_amount', 0) }},
                amountPaid: {{ old('amount_paid', 0) }},
                changeAmount: {{ old('change', 0) }},
                showPaymentError: false,
                priceDescription: 'Amount will be calculated based on service selection',
                
                // Confirmation Modal
                showConfirmation: false,
                submitting: false,
                generatedPassword: '',
                
                // Data from backend
                serviceData: @json($serviceCategories->groupBy('branch_id')),
                serviceNameData: @json($serviceNames->groupBy('service_category_id')),
                seatData: @json($seats->groupBy(['branch_id', 'service_category_id'])),
                branchData: @json($branch->keyBy('id')),
                categoryData: @json($serviceCategories->keyBy('id')),
                serviceNameDetailData: @json($serviceNames->keyBy('id')),
                seatDetailData: @json($seats->keyBy('id')),

                init() {
                    if (this.selectedBranch) {
                        this.onBranchChange();
                    }
                    
                    if (this.paymentNotes.length > 500) {
                        this.paymentNotes = this.paymentNotes.substring(0, 500);
                    }
                },

                // ===== REWARD METHODS =====
                async loadCustomerRewards() {
                    if (!this.selectedCustomer.id || !this.selectedBranch) {
                        this.availableRewards = [];
                        this.selectedReward = null;
                        this.appliedReward = null;
                        this.customerRewardId = null;
                        this.rewardDiscountAmount = 0;
                        this.rewardVoucherCode = null;
                        return;
                    }

                    this.rewardsLoading = true;
                    this.availableRewards = [];
                    this.selectedReward = null;

                    try {
                        const response = await fetch('{{ route("sub_two.book_now.get_customer_rewards") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                customer_id: this.selectedCustomer.id,
                                branch_id: this.selectedBranch
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.availableRewards = data.rewards || [];
                        } else {
                            console.error('Error loading rewards:', data.message);
                        }
                    } catch (error) {
                        console.error('Error loading rewards:', error);
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
                        
                        // Automatically select the correct category and service name if this is a free_service reward
                        if (reward.reward_type === 'free_service' && reward.service_category_id && reward.target_service_id) {
                            this.selectedCategory = reward.service_category_id;
                            
                            const allServices = this.serviceNameData[this.selectedCategory] || [];
                            const hourlyRegex = /^1\s+hour(s)?$/i;
                            this.availableServices = allServices.filter(service =>
                                service.time_duration && hourlyRegex.test(service.time_duration)
                            );
                            
                            const targetServiceExists = this.availableServices.some(s => s.id == reward.target_service_id);
                            
                            if (targetServiceExists) {
                                this.selectedService = reward.target_service_id;
                                const selectedServiceObj = this.availableServices.find(service => service.id == this.selectedService);
                                if (selectedServiceObj) {
                                    this.selectedServiceData = selectedServiceObj;
                                    this.totalAmount = selectedServiceObj.price || 0;
                                    this.priceDescription = `${selectedServiceObj.time_duration} rate for ${selectedServiceObj.service_name}`;
                                }
                            } else {
                                if (this.availableServices.length > 0) {
                                    this.selectedService = this.availableServices[0].id;
                                    this.selectedServiceData = this.availableServices[0];
                                    this.totalAmount = this.selectedServiceData.price || 0;
                                    this.priceDescription = `${this.selectedServiceData.time_duration} rate for ${this.selectedServiceData.service_name}`;
                                }
                            }
                            
                            this.validatePayment();
                            this.updateAvailableSeats();
                        }
                    }
                },

                async applySelectedReward() {
                    if (!this.selectedReward) {
                        alert('Please select a reward first.');
                        return;
                    }

                    if (this.rewardApplying) return;

                    this.rewardApplying = true;

                    try {
                        const response = await fetch('{{ route("sub_two.book_now.apply_reward") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                customer_reward_id: this.selectedReward.id,
                                total_amount: parseFloat(this.totalAmount) || 0
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.appliedReward = {
                                id: this.selectedReward.id,
                                voucher_code: this.selectedReward.voucher_code || 'N/A',
                                description: this.selectedReward.description || 'Reward',
                                discount_value: data.discount_value || 0,
                                item_name: this.selectedReward.item_name || ''
                            };
                            
                            this.customerRewardId = this.selectedReward.id;
                            this.rewardDiscountAmount = data.discount_value || 0;
                            this.rewardVoucherCode = this.selectedReward.voucher_code || null;
                            
                            this.totalAmount = data.new_total;
                            
                            if (this.paymentMethod === '0') {
                                this.amountPaid = this.totalAmount;
                                this.calculateChange();
                            }
                            
                            this.priceDescription = '✓ Reward applied: ' + (this.selectedReward.description || 'Discount');
                            this.selectedReward = null;
                        } else {
                            alert('❌ Error: ' + data.message);
                        }
                    } catch (error) {
                        console.error('Error applying reward:', error);
                        alert('Error applying reward. Please try again.');
                    } finally {
                        this.rewardApplying = false;
                    }
                },

                removeAppliedReward() {
                    if (!this.appliedReward) return;
                    
                    const discount = parseFloat(this.appliedReward.discount_value) || 0;
                    this.totalAmount = parseFloat(this.totalAmount) + discount;
                    
                    this.customerRewardId = null;
                    this.rewardDiscountAmount = 0;
                    this.rewardVoucherCode = null;
                    
                    if (this.paymentMethod === '0') {
                        this.amountPaid = this.totalAmount;
                        this.calculateChange();
                    }
                    
                    this.appliedReward = null;
                    this.priceDescription = 'Amount will be calculated based on service selection';
                },

                // ===== CUSTOMER METHODS =====
                clearSelectedCustomer() {
                    this.selectedCustomer = {
                        id: null,
                        first_name: '',
                        last_name: '',
                        email: '',
                        contact_no: ''
                    };
                    this.availableRewards = [];
                    this.selectedReward = null;
                    this.appliedReward = null;
                    this.customerRewardId = null;
                    this.rewardDiscountAmount = 0;
                    this.rewardVoucherCode = null;
                },

                async loadReturningCustomers() {
                    try {
                        const response = await fetch('{{ route('sub_two.book_now.get_returning_customers') }}');
                        const data = await response.json();

                        if (data.success) {
                            this.returningCustomers = data.customers || [];
                        } else {
                            console.error('Error loading returning customers:', data.message);
                            this.returningCustomers = [];
                            alert('Error loading returning customers: ' + data.message);
                        }
                    } catch (error) {
                        console.error('Error loading returning customers:', error);
                        this.returningCustomers = [];
                        alert('Error loading returning customers. Please try again.');
                    }
                },

                get filteredReturningCustomers() {
                    if (!this.customerSearch) return this.returningCustomers;

                    const searchTerm = this.customerSearch.toLowerCase();
                    return this.returningCustomers.filter(customer => {
                        const fullName = `${customer.first_name} ${customer.last_name}`.toLowerCase();
                        const email = customer.email.toLowerCase();
                        return fullName.includes(searchTerm) || email.includes(searchTerm);
                    });
                },

                selectModalCustomer(customer) {
                    this.selectedModalCustomer = customer;
                },

                selectReturningCustomer() {
                    if (this.selectedModalCustomer) {
                        this.selectedCustomer = {
                            ...this.selectedModalCustomer
                        };
                        this.showCustomerModal = false;
                        this.selectedModalCustomer = null;
                        this.customerModalType = 'new';
                        
                        if (this.selectedBranch) {
                            this.loadCustomerRewards();
                        }
                    } else {
                        alert('Please select a customer from the table.');
                    }
                },

                selectNewCustomer() {
                    if (this.validateModalCustomer()) {
                        this.selectedCustomer = {
                            ...this.modalCustomer,
                            id: null
                        };
                        this.modalCustomer = {
                            first_name: '',
                            last_name: '',
                            email: '',
                            contact_no: ''
                        };
                        this.showCustomerModal = false;
                        this.customerModalType = 'new';
                        
                        this.availableRewards = [];
                        this.selectedReward = null;
                        this.appliedReward = null;
                        this.customerRewardId = null;
                        this.rewardDiscountAmount = 0;
                        this.rewardVoucherCode = null;
                    }
                },

                validateModalCustomer() {
                    if (!this.modalCustomer.first_name.trim()) {
                        alert('Please enter first name');
                        return false;
                    }
                    if (!this.modalCustomer.last_name.trim()) {
                        alert('Please enter last name');
                        return false;
                    }
                    if (!this.modalCustomer.email.trim()) {
                        alert('Please enter email');
                        return false;
                    }
                    if (!this.modalCustomer.contact_no.trim()) {
                        alert('Please enter contact number');
                        return false;
                    }

                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(this.modalCustomer.email)) {
                        alert('Please enter a valid email address');
                        return false;
                    }

                    return true;
                },

                // ===== SERVICE METHODS =====
                onBranchChange() {
                    this.availableCategories = this.serviceData[this.selectedBranch] || [];
                    this.selectedCategory = '';
                    this.selectedService = '';
                    this.selectedSeat = '';
                    this.selectedServiceData = null;
                    this.onCategoryChange();
                    
                    if (this.selectedCustomer.id) {
                        this.loadCustomerRewards();
                    }
                },

                onCategoryChange() {
                    const allServices = this.serviceNameData[this.selectedCategory] || [];
                    const hourlyRegex = /^1\s+hour(s)?$/i;
                    
                    this.availableServices = allServices.filter(service =>
                        service.time_duration && hourlyRegex.test(service.time_duration)
                    );
                    
                    if (this.availableServices.length > 0) {
                        this.selectedService = this.availableServices[0].id;
                        this.selectedServiceData = this.availableServices[0];
                        this.onServiceChange();
                    } else {
                        this.selectedService = '';
                        this.selectedServiceData = null;
                        this.totalAmount = 0;
                        this.priceDescription = 'No hourly service available for this category';
                    }
                    
                    this.updateAvailableSeats();
                },

                onServiceChange() {
                    const selectedServiceObj = this.availableServices.find(service => service.id == this.selectedService);
                    
                    if (selectedServiceObj) {
                        this.selectedServiceData = selectedServiceObj;
                        this.totalAmount = selectedServiceObj.price || 0;
                        this.priceDescription = `${selectedServiceObj.time_duration} rate for ${selectedServiceObj.service_name}`;
                    } else {
                        this.selectedServiceData = null;
                        this.totalAmount = 0;
                        this.priceDescription = 'Amount will be calculated based on service selection';
                    }
                    this.validatePayment();
                },

                getSeatDisplayName(seat) {
                    if (seat.room_no) {
                        return `Room ${seat.room_no}`;
                    } else if (seat.seat_no) {
                        return `Seat ${seat.seat_no}`;
                    }
                    return 'Unknown';
                },

                async updateAvailableSeats() {
                    this.availableSeats = [];
                    this.selectedSeat = '';

                    if (this.selectedBranch && this.selectedCategory) {
                        try {
                            const response = await fetch("{{ route('sub_two.book_now.get_available_seats') }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    branch_id: this.selectedBranch,
                                    service_category_id: this.selectedCategory,
                                    date_start: '{{ date('Y-m-d') }}',
                                    date_end: '{{ date('Y-m-d') }}'
                                })
                            });
                            
                            if (!response.ok) throw new Error('Failed to fetch available seats');
                            this.availableSeats = await response.json();
                            
                        } catch (error) {
                            console.error('Error fetching available seats:', error);
                            this.availableSeats = [];
                            alert('Error loading available seats/rooms. Please try again.');
                        }
                    }
                },

                // ===== PAYMENT METHODS =====
                onPaymentMethodChange() {
                    this.amountPaid = 0;
                    this.changeAmount = 0;
                    this.showPaymentError = false;
                    this.paymentNotes = '';
                },

                calculateChange() {
                    const paid = parseFloat(this.amountPaid) || 0;
                    const total = parseFloat(this.totalAmount) || 0;
                    this.changeAmount = paid - total > 0 ? (paid - total).toFixed(2) : '0.00';
                    this.validatePayment();
                },

                validatePayment() {
                    const paid = parseFloat(this.amountPaid) || 0;
                    const total = parseFloat(this.totalAmount) || 0;

                    if (this.paymentMethod === '0') {
                        this.showPaymentError = paid < total;
                    } else {
                        this.showPaymentError = false;
                    }

                    return !this.showPaymentError;
                },

                limitNotesLength() {
                    if (this.paymentNotes.length > 500) {
                        this.paymentNotes = this.paymentNotes.substring(0, 500);
                    }
                },

                // ===== CONFIRMATION METHODS =====
                showConfirmationModal() {
                    if (!this.isFormValid()) {
                        alert('Please fill in all required fields.');
                        return;
                    }

                    if (!this.validatePayment()) {
                        alert('Payment validation failed. Please check the amount paid.');
                        return;
                    }

                    if (this.paymentNotes.length > 500) {
                        alert('Transaction notes cannot exceed 500 characters.');
                        return;
                    }

                    this.showConfirmation = true;
                },

                async submitForm() {
                    this.submitting = true;
                    
                    try {
                        if (!this.selectedCustomer.id) {
                            this.generatedPassword = this.generateRandomPassword();
                        }
                        
                        const form = document.getElementById('bookingForm');
                        form.submit();
                        
                    } catch (error) {
                        console.error('Error submitting form:', error);
                        alert('Error submitting booking: ' + error.message);
                        this.submitting = false;
                    }
                },

                // ===== HELPER METHODS =====
                getSelectedBranchName() {
                    const branch = this.branchData[this.selectedBranch];
                    return branch ? branch.branch_name.substring(0, 30) + (branch.branch_name.length > 30 ? '...' : '') : 'N/A';
                },

                getSelectedCategoryName() {
                    const category = this.categoryData[this.selectedCategory];
                    return category ? category.service_category.substring(0, 30) + (category.service_category.length > 30 ? '...' : '') : 'N/A';
                },

                getSelectedServiceName() {
                    const service = this.serviceNameDetailData[this.selectedService];
                    if (!service) return 'N/A';
                    
                    const name = `${service.service_name} (${service.space_type})`;
                    return name.substring(0, 40) + (name.length > 40 ? '...' : '');
                },

                getSelectedSeatName() {
                    const seat = this.seatDetailData[this.selectedSeat];
                    return seat ? this.getSeatDisplayName(seat) : 'N/A';
                },

                getPaymentMethodName() {
                    const methods = {
                        '0': 'Cash',
                        '1': 'GCash',
                        '2': 'Debit Card',
                        '3': 'Pay Later'
                    };
                    return methods[this.paymentMethod] || 'N/A';
                },

                isFormValid() {
                    const baseValid = this.selectedCustomer.first_name &&
                        this.selectedCustomer.last_name &&
                        this.selectedCustomer.email &&
                        this.selectedCustomer.contact_no &&
                        this.selectedBranch &&
                        this.selectedCategory &&
                        this.selectedService &&
                        this.selectedSeat &&
                        this.paymentMethod;

                    if (this.paymentMethod === '0') {
                        return baseValid && this.amountPaid !== '' && parseFloat(this.amountPaid) >= 0 && !this.showPaymentError;
                    }

                    return baseValid && !this.showPaymentError;
                },

                resetForm() {
                    this.selectedCustomer = {
                        id: null,
                        first_name: '',
                        last_name: '',
                        email: '',
                        contact_no: ''
                    };
                    this.selectedCategory = '';
                    this.selectedService = '';
                    this.selectedServiceData = null;
                    this.selectedSeat = '';
                    this.paymentMethod = '';
                    this.paymentNotes = '';
                    this.totalAmount = 0;
                    this.amountPaid = 0;
                    this.changeAmount = 0;
                    this.showPaymentError = false;
                    this.priceDescription = 'Amount will be calculated based on service selection';
                    this.availableCategories = [];
                    this.availableServices = [];
                    this.availableSeats = [];
                    this.showConfirmation = false;
                    this.availableRewards = [];
                    this.selectedReward = null;
                    this.appliedReward = null;
                    this.customerRewardId = null;
                    this.rewardDiscountAmount = 0;
                    this.rewardVoucherCode = null;
                },

                generateRandomPassword() {
                    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
                    let password = '';
                    for (let i = 0; i < 12; i++) {
                        password += chars.charAt(Math.floor(Math.random() * chars.length));
                    }
                    return password;
                }
            }));
        });
    </script>
@endsection