@extends('layouts.app')

@section('content')
<style>
    [x-cloak] { display: none !important; }
    
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
        display: inline-block;
    }
    
    .status-active {
        background: #d1fae5;
        color: #065f46;
    }
    
    .status-inactive {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .type-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
        display: inline-block;
        min-width: 80px;
        text-align: center;
        white-space: nowrap;
    }
    
    .type-free_service {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .type-free_product {
        background: #d1fae5;
        color: #065f46;
    }
    
    .type-fixed_discount {
        background: #fef3c7;
        color: #92400e;
    }
    
    .type-percentage_discount {
        background: #ede9fe;
        color: #5b21b6;
    }
    
    .type-default {
        background: #f3f4f6;
        color: #374151;
    }

    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .modal-content {
        background: white;
        border-radius: 0.75rem;
        max-width: 650px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        padding: 1.5rem;
        position: relative;
        animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
        from {
            transform: translateY(-30px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .modal-overlay.hidden {
        display: none;
    }

    .spinner {
        border: 3px solid #f3f3f3;
        border-top: 3px solid #7F5539;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @media (max-width: 1024px) {
        .type-badge {
            min-width: 70px;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
        }
        .table-cell-padding {
            padding: 0.5rem 0.5rem !important;
        }
        .actions-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
        }
        .actions-container .btn-text {
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
        }
        .text-sm {
            font-size: 0.75rem !important;
        }
        .text-xs {
            font-size: 0.6rem !important;
        }
    }

    @media (max-width: 768px) {
        .type-badge {
            min-width: 55px;
            font-size: 0.6rem;
            padding: 0.15rem 0.35rem;
        }
        .table-cell-padding {
            padding: 0.35rem 0.35rem !important;
        }
        .actions-container {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }
        .actions-container .btn-text {
            font-size: 0.6rem;
            padding: 0.15rem 0.3rem;
        }
        .text-sm {
            font-size: 0.65rem !important;
        }
        .text-xs {
            font-size: 0.55rem !important;
        }
        .status-badge {
            padding: 0.15rem 0.4rem;
            font-size: 0.6rem;
        }
        .monetary-value {
            font-size: 0.65rem !important;
        }
        .type-column {
            width: 1px !important;
            white-space: nowrap;
        }
    }

    @media (max-width: 480px) {
        .type-badge {
            min-width: 40px;
            font-size: 0.5rem;
            padding: 0.1rem 0.2rem;
        }
        .table-cell-padding {
            padding: 0.25rem 0.2rem !important;
        }
        .actions-container .btn-text {
            font-size: 0.55rem;
            padding: 0.1rem 0.25rem;
        }
        .text-sm {
            font-size: 0.6rem !important;
        }
        .text-xs {
            font-size: 0.5rem !important;
        }
        .status-badge {
            padding: 0.1rem 0.3rem;
            font-size: 0.5rem;
        }
        .monetary-value {
            font-size: 0.6rem !important;
        }
    }

    @media (min-width: 769px) {
        .actions-container {
            display: flex;
            flex-wrap: nowrap;
            gap: 0.5rem;
        }
    }

    .target-details {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 0.75rem;
        margin-top: 0.5rem;
    }

    .target-details p {
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }

    .target-details strong {
        color: #475569;
    }

    .ingredient-badge {
        display: inline-block;
        background: #e0e7ff;
        color: #3730a3;
        font-size: 0.7rem;
        padding: 0.15rem 0.5rem;
        border-radius: 9999px;
        margin: 0.1rem;
    }

    .dropdown-container {
        position: relative;
        width: 100%;
    }

    .dropdown-search {
        width: 100%;
        padding: 0.5rem 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        transition: all 0.2s;
        background: white;
    }

    .dropdown-search:focus {
        outline: none;
        border-color: #7F5539;
        box-shadow: 0 0 0 3px rgba(127, 85, 57, 0.1);
    }

    .dropdown-search::placeholder {
        color: #9ca3af;
    }

    .dropdown-search:disabled {
        background: #f3f4f6;
        cursor: not-allowed;
    }

    .dropdown-list {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        max-height: 250px;
        overflow-y: auto;
        background: white;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        z-index: 50;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        display: none;
    }

    .dropdown-list.show {
        display: block;
        animation: dropdownFade 0.15s ease-out;
    }

    @keyframes dropdownFade {
        from {
            opacity: 0;
            transform: translateY(-8px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .dropdown-item {
        padding: 0.5rem 0.75rem;
        cursor: pointer;
        transition: background 0.15s;
        font-size: 0.875rem;
        border-bottom: 1px solid #f3f4f6;
    }

    .dropdown-item:last-child {
        border-bottom: none;
    }

    .dropdown-item:hover {
        background: #f3f4f6;
    }

    .dropdown-item.selected {
        background: #dbeafe;
        color: #1e40af;
    }

    .dropdown-item .item-category {
        color: #6b7280;
        font-size: 0.75rem;
    }

    .dropdown-item .item-price {
        color: #059669;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .dropdown-item .ingredient-tag {
        display: inline-block;
        background: #e0e7ff;
        color: #3730a3;
        font-size: 0.65rem;
        padding: 0.1rem 0.4rem;
        border-radius: 9999px;
        margin-left: 0.25rem;
    }

    .dropdown-empty {
        padding: 0.75rem;
        text-align: center;
        color: #6b7280;
        font-size: 0.875rem;
    }

    .selected-item-display {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0.75rem;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        margin-top: 0.5rem;
    }

    .selected-item-display .clear-btn {
        color: #ef4444;
        cursor: pointer;
        font-size: 1.25rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        transition: background 0.15s;
        line-height: 1;
        background: none;
        border: none;
    }

    .selected-item-display .clear-btn:hover {
        background: #fee2e2;
    }

    .selected-item-display .item-info {
        flex: 1;
    }

    .selected-item-display .item-name {
        font-weight: 500;
        font-size: 0.875rem;
    }

    .selected-item-display .item-meta {
        font-size: 0.75rem;
        color: #6b7280;
    }

    .selected-item-display .item-price-display {
        font-weight: 600;
        color: #059669;
        font-size: 0.875rem;
        margin-left: 0.5rem;
    }

    .branch-auto-fill {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.75rem;
        color: #059669;
        background: #d1fae5;
        padding: 0.15rem 0.5rem;
        border-radius: 9999px;
        margin-left: 0.5rem;
    }

    .branch-auto-fill svg {
        width: 14px;
        height: 14px;
    }

    .monetary-auto-fill {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.7rem;
        color: #059669;
        background: #d1fae5;
        padding: 0.1rem 0.4rem;
        border-radius: 9999px;
        margin-left: 0.5rem;
    }

    .monetary-auto-fill svg {
        width: 12px;
        height: 12px;
    }

    .price-auto-filled {
        border-color: #10b981 !important;
        background-color: #f0fdf4 !important;
    }

    .dropdown-list::-webkit-scrollbar {
        width: 6px;
    }

    .dropdown-list::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .dropdown-list::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 3px;
    }

    .dropdown-list::-webkit-scrollbar-thumb:hover {
        background: #9ca3af;
    }

    .modal-alert {
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .modal-alert-success {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #166534;
    }

    .modal-alert-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }

    .modal-alert-warning {
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #92400e;
    }

    .modal-alert-info {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e40af;
    }

    .modal-alert .alert-icon {
        flex-shrink: 0;
        margin-top: 0.1rem;
    }

    .modal-alert .alert-content {
        flex: 1;
    }

    .modal-alert .alert-title {
        font-weight: 600;
        font-size: 0.875rem;
    }

    .modal-alert .alert-message {
        font-size: 0.875rem;
        margin-top: 0.1rem;
    }

    .modal-alert .alert-close {
        background: none;
        border: none;
        color: inherit;
        cursor: pointer;
        font-size: 1.25rem;
        padding: 0 0.25rem;
        opacity: 0.7;
        transition: opacity 0.2s;
        flex-shrink: 0;
    }

    .modal-alert .alert-close:hover {
        opacity: 1;
    }

    .currency-input {
        position: relative;
    }

    .currency-input .currency-symbol {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6b7280;
        font-weight: 500;
    }

    .currency-input input {
        padding-left: 1.75rem;
    }

    .currency-input .percentage-symbol {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6b7280;
        font-weight: 500;
    }

    .currency-input input[type="number"] {
        -moz-appearance: textfield;
    }

    .currency-input input[type="number"]::-webkit-outer-spin-button,
    .currency-input input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .value-display {
        display: flex !important;
        flex-direction: column !important;
        align-items: flex-start;
        padding: 0.25rem 0;
        margin: 0;
    }

    .value-display[style*="display: none"],
    .value-display[style*="display:none"] {
        display: none !important;
    }

    .value-display .value-amount,
    .value-display .value-label,
    .value-display .value-savings {
        position: static !important;
        float: none !important;
        display: block !important;
        width: 100%;
    }

    .value-display .value-amount {
        font-size: 0.875rem;
        font-weight: 600;
        line-height: 1.2;
        padding: 0;
        margin: 0 0 0.2rem 0;
    }

    .value-display .value-amount.fixed {
        color: #92400e;
    }

    .value-display .value-amount.percentage {
        color: #5b21b6;
    }

    .value-display .value-amount.free {
        color: #059669;
    }

    .value-display .value-label {
        font-size: 0.65rem;
        color: #6b7280;
        font-weight: 400;
        line-height: 1.2;
        padding: 0;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .value-display .value-savings {
        font-size: 0.65rem;
        color: #059669;
        font-weight: 500;
        line-height: 1.2;
        padding: 0;
        margin: 0;
    }

    .table-cell-padding {
        vertical-align: middle !important;
    }

    td .value-display {
        margin: 0 !important;
        padding: 0 !important;
    }

    .calculated-value-box {
        margin-top: 0.5rem;
        padding: 0.75rem 1rem;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .calculated-value-box .label {
        font-size: 0.875rem;
        color: #166534;
        font-weight: 500;
    }

    .calculated-value-box .value {
        font-size: 1.125rem;
        font-weight: 700;
        color: #065f46;
    }

    .calculated-value-box .detail {
        font-size: 0.75rem;
        color: #6b7280;
    }

    @media (max-width: 480px) {
        .value-display .value-amount {
            font-size: 0.7rem !important;
        }
        .value-display .value-label {
            font-size: 0.5rem !important;
        }
        .value-display .value-savings {
            font-size: 0.55rem !important;
        }
        .value-display {
            min-height: 2rem !important;
        }
        .calculated-value-box {
            flex-direction: column;
            align-items: stretch;
            text-align: center;
        }
    }
</style>

<script>
    window.redeemableItemsData = {
        items: @json($items->items() ?? []),
        branches: @json($branches ?? []),
        services: @json($services ?? []),
        products: @json($products ?? []),
        pagination: @json($items->toArray() ?? []),
        stats: @json($stats ?? []),
        rewardTypes: @json(\App\Models\RedeemableItem::getRewardTypeOptions() ?? [])
    };
</script>

<div x-data="redeemableItemsData()" x-init="init()" x-cloak>
    <!-- Header -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Redeemable Rewards</h1>
                <p class="text-gray-600">Manage rewards that customers can redeem through loyalty tiers</p>
            </div>
            <button @click="openAddModal()" 
                    class="w-full sm:w-auto px-4 py-2 bg-[#7F5539] text-white rounded-lg hover:bg-[#4A2C1D] transition-colors flex items-center justify-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add New Reward
            </button>
        </div>

        <!-- Stats Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                <p class="text-sm text-gray-600">Total Rewards</p>
                <p class="text-2xl font-bold text-gray-900" x-text="stats.total || 0"></p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                <p class="text-sm text-gray-600">Active Rewards</p>
                <p class="text-2xl font-bold text-green-600" x-text="stats.active || 0"></p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                <p class="text-sm text-gray-600">Inactive Rewards</p>
                <p class="text-2xl font-bold text-red-600" x-text="stats.inactive || 0"></p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                <p class="text-sm text-gray-600">Linked to Tiers</p>
                <p class="text-2xl font-bold text-blue-600" x-text="stats.linked || 0"></p>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex-1">
                    <input type="text" 
                           x-model="searchQuery"
                           @input.debounce.500ms="applyFilters()"
                           placeholder="Search by reward name..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]">
                </div>
                <div class="flex flex-wrap gap-2">
                    <select x-model="filterRewardType" @change="applyFilters()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]">
                        <option value="">All Types</option>
                        <template x-for="type in rewardTypes" :key="type.value">
                            <option :value="type.value" x-text="type.label"></option>
                        </template>
                    </select>
                    <select x-model="filterStatus" @change="applyFilters()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                    <select x-model="filterBranch" @change="applyFilters()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]">
                        <option value="">All Branches</option>
                        <template x-for="branch in branches" :key="branch.id">
                            <option :value="branch.id" x-text="branch.branch_name"></option>
                        </template>
                    </select>
                    <button @click="clearFilters()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                        Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reward Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider type-column">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Linked</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="item in items" :key="item.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 table-cell-padding">
                                    <div class="text-sm font-medium text-gray-900" x-text="item.item_name"></div>
                                    <div class="text-xs text-gray-500" x-text="item.item_description || ''"></div>
                                </td>
                                <td class="px-4 py-3 table-cell-padding type-column">
                                    <span class="type-badge" :class="'type-' + item.reward_type" x-text="item.type_label"></span>
                                </td>
                                <td class="px-4 py-3 table-cell-padding">
                                    <!-- Fixed Discount Display -->
                                    <div x-show="item.reward_type === 'fixed_discount'" class="value-display">
                                        <span class="value-amount fixed">- ₱<span x-text="parseFloat(item.monetary_value || 0).toFixed(2)"></span></span>
                                        <span class="value-label">Fixed Discount</span>
                                    </div>
                                    
                                    <!-- Percentage Discount Display -->
                                    <div x-show="item.reward_type === 'percentage_discount'" class="value-display">
                                        <span class="value-amount percentage"><span x-text="parseFloat(item.discount_percentage || 0).toFixed(0)"></span>% OFF</span>
                                        <span class="value-label">(₱<span x-text="parseFloat(item.monetary_value || 0).toFixed(2)"></span> savings)</span>
                                    </div>
                                    
                                    <!-- Free Service Display -->
                                    <div x-show="item.reward_type === 'free_service'" class="value-display">
                                        <span class="value-amount free">FREE</span>
                                        <span x-show="item.monetary_value" class="value-label">(₱<span x-text="parseFloat(item.monetary_value).toFixed(2)"></span> value)</span>
                                    </div>
                                    
                                    <!-- Free Product Display -->
                                    <div x-show="item.reward_type === 'free_product'" class="value-display">
                                        <span class="value-amount free">FREE</span>
                                        <span x-show="item.monetary_value" class="value-label">(₱<span x-text="parseFloat(item.monetary_value).toFixed(2)"></span> value)</span>
                                    </div>
                                    
                                    <!-- Fallback for any other type -->
                                    <div x-show="!['fixed_discount', 'percentage_discount', 'free_service', 'free_product'].includes(item.reward_type)" class="value-display">
                                        <span class="value-amount" x-text="item.monetary_value ? '₱' + parseFloat(item.monetary_value).toFixed(2) : 'N/A'"></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 table-cell-padding">
                                    <div class="text-sm text-gray-900">
                                        <span x-show="item.target_service_id" x-text="item.target_service?.service_name || 'N/A'"></span>
                                        <span x-show="item.target_product_id" x-text="item.target_product?.product_name || 'N/A'"></span>
                                        <span x-show="!item.target_service_id && !item.target_product_id" class="text-gray-400">N/A</span>
                                    </div>
                                    <div x-show="item.target_service_id && item.target_service" class="text-xs text-gray-500">
                                        <span x-text="item.target_service?.service_category?.service_category || 'Uncategorized'"></span>
                                    </div>
                                    <div x-show="item.target_product_id && item.target_product" class="text-xs text-gray-500">
                                        <span x-show="item.target_product?.product_ingredients?.length > 0" class="text-blue-600">
                                            <span x-text="(item.target_product?.product_ingredients?.length || 0) + ' ingredients'"></span>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 table-cell-padding">
                                    <div class="text-sm text-gray-900" x-text="item.branch?.branch_name || 'All Branches'"></div>
                                </td>
                                <td class="px-4 py-3 table-cell-padding">
                                    <span class="text-sm" :class="item.loyalty_tiers_count > 0 ? 'text-blue-600 font-medium' : 'text-gray-400'">
                                        <span x-text="item.loyalty_tiers_count || 0"></span> tier<span x-show="item.loyalty_tiers_count !== 1">s</span>
                                    </span>
                                </td>
                                <td class="px-4 py-3 table-cell-padding">
                                    <span class="status-badge" :class="(item.active && item.is_active) ? 'status-active' : 'status-inactive'">
                                        <span x-text="(item.active && item.is_active) ? 'Active' : 'Inactive'"></span>
                                    </span>
                                </td>
                                <td class="px-4 py-3 table-cell-padding">
                                    <div class="actions-container">
                                        <button @click="openEditModal(item.id)" 
                                                class="px-3 py-1 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm transition-colors whitespace-nowrap btn-text">
                                            Edit
                                        </button>
                                        <button @click="toggleStatus(item.id)" 
                                                class="px-3 py-1 text-white rounded-lg text-sm transition-colors whitespace-nowrap btn-text"
                                                :class="(item.active && item.is_active) ? 'bg-yellow-500 hover:bg-yellow-600' : 'bg-green-500 hover:bg-green-600'"
                                                x-text="(item.active && item.is_active) ? 'Deactivate' : 'Activate'">
                                        </button>
                                        <button @click="deleteItem(item.id)" 
                                                class="px-3 py-1 bg-red-500 text-white rounded-lg hover:bg-red-600 text-sm transition-colors whitespace-nowrap btn-text"
                                                :disabled="item.loyalty_tiers_count > 0"
                                                :class="item.loyalty_tiers_count > 0 ? 'opacity-50 cursor-not-allowed' : ''">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="isLoading">
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex justify-center">
                                    <div class="spinner"></div>
                                </div>
                                <p class="mt-2 text-sm text-gray-500">Loading rewards...</p>
                            </td>
                        </tr>

                        <tr x-show="!isLoading && (!items || items.length === 0)">
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                <p class="text-lg font-medium text-gray-900">No redeemable rewards created yet</p>
                                <p class="text-sm text-gray-500">Create your first reward to start building loyalty tiers</p>
                                <button @click="openAddModal()" class="mt-4 px-4 py-2 bg-[#7F5539] text-white rounded-lg hover:bg-[#4A2C1D] transition-colors">
                                    Create First Reward
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div x-show="pagination && pagination.last_page > 1" class="px-6 py-4 border-t border-gray-200">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="text-sm text-gray-700">
                        Showing <span x-text="pagination.from || 0"></span> to <span x-text="pagination.to || 0"></span>
                        of <span x-text="pagination.total || 0"></span> entries
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button @click="changePage(pagination.current_page - 1)" 
                                :disabled="pagination.current_page === 1"
                                class="px-3 py-1 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            Previous
                        </button>
                        <template x-for="page in paginationLinks" :key="page">
                            <button @click="changePage(page)" 
                                    class="px-3 py-1 border rounded-lg text-sm font-medium transition-colors"
                                    :class="page === pagination.current_page ? 
                                        'border-2 border-[#4A2C1D] bg-[#7F5539]/80 text-white' : 
                                        'border-gray-300 text-gray-700 hover:bg-gray-50'"
                                    :disabled="page === '...'" 
                                    x-text="page">
                            </button>
                        </template>
                        <button @click="changePage(pagination.current_page + 1)" 
                                :disabled="pagination.current_page === pagination.last_page"
                                class="px-3 py-1 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div x-show="showModal" x-cloak class="modal-overlay" @click.away="closeModal()">
        <div class="modal-content" @click.stop>
            <!-- Modal Header -->
            <div class="flex justify-between items-center mb-4 border-b pb-3">
                <h3 class="text-lg font-semibold text-gray-900" x-text="isEditing ? 'Edit Reward' : 'Create New Reward'"></h3>
                <button @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Modal Alert -->
            <div x-show="alert.show" class="modal-alert" :class="'modal-alert-' + alert.type">
                <span class="alert-icon">
                    <svg x-show="alert.type === 'success'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <svg x-show="alert.type === 'error'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <svg x-show="alert.type === 'warning'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <svg x-show="alert.type === 'info'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                <div class="alert-content">
                    <div class="alert-title" x-text="alert.title"></div>
                    <div class="alert-message" x-text="alert.message"></div>
                </div>
                <button @click="alert.show = false" class="alert-close">×</button>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitForm()">
                @csrf
                <input type="hidden" x-model="form.id">
                <input type="hidden" name="_method" x-model="form.method">

                <div class="space-y-4">
                    <!-- Reward Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reward Name <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.item_name" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]"
                               placeholder="e.g., Free 1-Hour Study Pod, 20% Off All Rentals"
                               required>
                    </div>

                    <!-- Reward Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reward Type <span class="text-red-500">*</span></label>
                        <select x-model="form.reward_type" @change="onTypeChange()" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]"
                                required>
                            <option value="">Select Reward Type</option>
                            <template x-for="type in rewardTypes" :key="type.value">
                                <option :value="type.value" x-text="type.label"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-xs text-gray-500" x-show="form.reward_type">
                            <span x-show="form.reward_type === 'free_service'">Customer gets the selected space/service for free</span>
                            <span x-show="form.reward_type === 'free_product'">Customer gets the selected product for free</span>
                            <span x-show="form.reward_type === 'fixed_discount'">Customer gets a fixed amount discount (e.g., ₱100 off)</span>
                            <span x-show="form.reward_type === 'percentage_discount'">Customer gets a percentage discount (e.g., 20% off)</span>
                        </p>
                    </div>

                    <!-- Target Service -->
                    <div x-show="form.reward_type === 'free_service'" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Target Space/Service <span class="text-red-500">*</span></label>
                        <div class="dropdown-container">
                            <input type="text" 
                                   x-model="serviceSearch"
                                   @focus="showServiceDropdown = true"
                                   @blur="setTimeout(() => { showServiceDropdown = false }, 200)"
                                   @keydown.escape="showServiceDropdown = false"
                                   @keydown.enter.prevent="selectFirstService()"
                                   @input="if (!serviceSearch) { clearSelectedService() }"
                                   placeholder="Search for a space/service..."
                                   class="dropdown-search"
                                   :disabled="isSubmitting"
                                   autocomplete="off">
                            
                            <div x-show="form.target_service_id && selectedService" class="selected-item-display">
                                <div class="item-info">
                                    <div class="item-name" x-text="selectedService?.name"></div>
                                    <div class="item-meta" x-text="(selectedService?.category || '') + ' | ' + (selectedService?.branch || '')"></div>
                                </div>
                                <div>
                                    <span class="item-price-display" x-text="'₱' + (parseFloat(selectedService?.price) || 0).toFixed(2) + '/hr'"></span>
                                    <button type="button" @click="clearSelectedService()" class="clear-btn">×</button>
                                </div>
                            </div>

                            <div class="dropdown-list" :class="showServiceDropdown && filteredServices.length > 0 ? 'show' : ''">
                                <template x-for="service in filteredServices" :key="service.id">
                                    <div class="dropdown-item" :class="form.target_service_id == service.id ? 'selected' : ''"
                                         @click="selectService(service)"
                                         @mousedown.prevent>
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <span x-text="service.name"></span>
                                                <span class="item-category block" x-text="service.category + ' | ' + service.branch"></span>
                                            </div>
                                            <span class="item-price" x-text="'₱' + (parseFloat(service.price) || 0).toFixed(2) + '/hr'"></span>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="filteredServices.length === 0" class="dropdown-empty">
                                    <span x-show="serviceSearch && serviceSearch.length > 0">No spaces/services found matching "<span x-text="serviceSearch"></span>"</span>
                                    <span x-show="!serviceSearch || serviceSearch.length === 0">No spaces/services available</span>
                                </div>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Spaces are organized by: Category → Space Name (Branch)</p>
                    </div>

                    <!-- Target Product -->
                    <div x-show="form.reward_type === 'free_product'" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Target Product <span class="text-red-500">*</span></label>
                        <div class="dropdown-container">
                            <input type="text" 
                                   x-model="productSearch"
                                   @focus="showProductDropdown = true"
                                   @blur="setTimeout(() => { showProductDropdown = false }, 200)"
                                   @keydown.escape="showProductDropdown = false"
                                   @keydown.enter.prevent="selectFirstProduct()"
                                   @input="if (!productSearch) { clearSelectedProduct() }"
                                   placeholder="Search for a product..."
                                   class="dropdown-search"
                                   :disabled="isSubmitting"
                                   autocomplete="off">
                            
                            <div x-show="form.target_product_id && selectedProduct" class="selected-item-display">
                                <div class="item-info">
                                    <div class="item-name" x-text="selectedProduct?.name"></div>
                                    <div class="item-meta">
                                        <span x-text="selectedProduct?.branch || ''"></span>
                                        <span x-show="selectedProduct?.has_ingredients" class="ingredient-badge" style="margin-left: 0.5rem;">
                                            <span x-text="(selectedProduct?.ingredients_count || 0) + ' ingredients'"></span>
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <span class="item-price-display" x-text="'₱' + (parseFloat(selectedProduct?.price) || 0).toFixed(2)"></span>
                                    <button type="button" @click="clearSelectedProduct()" class="clear-btn">×</button>
                                </div>
                            </div>

                            <div class="dropdown-list" :class="showProductDropdown && filteredProducts.length > 0 ? 'show' : ''">
                                <template x-for="product in filteredProducts" :key="product.id">
                                    <div class="dropdown-item" :class="form.target_product_id == product.id ? 'selected' : ''"
                                         @click="selectProduct(product)"
                                         @mousedown.prevent>
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <span x-text="product.name"></span>
                                                <span class="item-category block" x-text="product.branch"></span>
                                                <span x-show="product.has_ingredients" class="ingredient-tag">
                                                    <span x-text="product.ingredients_count + ' ingredients'"></span>
                                                </span>
                                            </div>
                                            <span class="item-price" x-text="'₱' + (parseFloat(product.price) || 0).toFixed(2)"></span>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="filteredProducts.length === 0" class="dropdown-empty">
                                    <span x-show="productSearch && productSearch.length > 0">No products found matching "<span x-text="productSearch"></span>"</span>
                                    <span x-show="!productSearch || productSearch.length === 0">No products available</span>
                                </div>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Products with ingredients are marked with ingredient count</p>
                    </div>

                    <!-- Fixed Discount - Monetary Value -->
                    <div x-show="form.reward_type === 'fixed_discount'" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Discount Amount <span class="text-red-500">*</span>
                        </label>
                        <div class="currency-input">
                            <span class="currency-symbol">₱</span>
                            <input type="number" x-model="form.monetary_value" 
                                   step="0.01" min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]"
                                   placeholder="0.00"
                                   :required="form.reward_type === 'fixed_discount'">
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Enter the fixed discount amount (e.g., 100 for ₱100 off)</p>
                    </div>

                    <!-- Percentage Discount -->
                    <div x-show="form.reward_type === 'percentage_discount'" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Discount Percentage <span class="text-red-500">*</span>
                        </label>
                        <div class="currency-input">
                            <input type="number" 
                                   x-model="form.discount_percentage" 
                                   @input="calculateMonetaryValue()"
                                   step="0.01" 
                                   min="0.01" 
                                   max="100"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]"
                                   placeholder="e.g., 20"
                                   :required="form.reward_type === 'percentage_discount'">
                            <span class="percentage-symbol">%</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Percentage discount (e.g., 20 for 20% off)</p>
                        
                        <!-- Show converted decimal value -->
                        <div x-show="form.monetary_value !== null && form.monetary_value !== '' && form.discount_percentage" 
                             class="calculated-value-box">
                            <div>
                                <span class="label">📊 Stored Value</span>
                                <span class="detail">(Converted to decimal for calculation)</span>
                            </div>
                            <div>
                                <span class="value"><span x-text="form.discount_percentage"></span>% → <span x-text="parseFloat(form.monetary_value).toFixed(4)"></span></span>
                            </div>
                        </div>
                    </div>

                    <!-- Monetary Value for Free Service/Product (Auto-filled) -->
                    <div x-show="form.reward_type && ['free_service', 'free_product'].includes(form.reward_type)" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <span x-show="form.reward_type === 'free_service'">Service Price</span>
                            <span x-show="form.reward_type === 'free_product'">Product Price</span>
                            <span class="text-red-500">*</span>
                            <span x-show="monetaryAutoFilled" class="monetary-auto-fill">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Auto-filled
                            </span>
                        </label>
                        <div class="currency-input">
                            <span class="currency-symbol">₱</span>
                            <input type="number" x-model="form.monetary_value" 
                                   step="0.01" min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]"
                                   :class="monetaryAutoFilled ? 'price-auto-filled' : ''"
                                   placeholder="0.00"
                                   @focus="monetaryAutoFilled = false"
                                   :required="form.reward_type && ['free_service', 'free_product'].includes(form.reward_type)">
                        </div>
                        <p class="mt-1 text-xs text-gray-500" x-show="form.reward_type === 'free_service'">
                            <span x-show="monetaryAutoFilled">Automatically set from the selected service price. You can manually override.</span>
                            <span x-show="!monetaryAutoFilled">Enter the service price or select a service to auto-fill.</span>
                        </p>
                        <p class="mt-1 text-xs text-gray-500" x-show="form.reward_type === 'free_product'">
                            <span x-show="monetaryAutoFilled">Automatically set from the selected product price. You can manually override.</span>
                            <span x-show="!monetaryAutoFilled">Enter the product price or select a product to auto-fill.</span>
                        </p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea x-model="form.item_description" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]"
                                  placeholder="Detailed description of the reward..."></textarea>
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category (Optional)</label>
                        <input type="text" x-model="form.category" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]"
                               placeholder="e.g., Study Spaces, Meeting Rooms, Food Items">
                        <p class="mt-1 text-xs text-gray-500">Optional category to help organize rewards</p>
                    </div>

                    <!-- Branch Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                        <div class="relative">
                            <select x-model="form.branch_id" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]"
                                    :class="branchAutoFilled ? 'border-green-400 bg-green-50' : ''">
                                <option value="">All Branches</option>
                                <template x-for="branch in branches" :key="branch.id">
                                    <option :value="branch.id" x-text="branch.branch_name"></option>
                                </template>
                            </select>
                            
                            <div x-show="branchAutoFilled" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                <span class="branch-auto-fill">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Auto-filled
                                </span>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500" x-show="!branchAutoFilled">Leave empty to make this reward available at all branches</p>
                        <p class="mt-1 text-xs text-green-600" x-show="branchAutoFilled">
                            Branch automatically set based on selected target. 
                            <button type="button" @click="clearBranchAutoFill()" class="text-red-500 hover:text-red-700 underline">Clear</button>
                        </p>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <div class="flex items-center space-x-6">
                            <label class="inline-flex items-center">
                                <input type="radio" x-model="form.is_active" :value="1" class="form-radio text-[#7F5539]">
                                <span class="ml-2 text-sm text-gray-700">Active</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" x-model="form.is_active" :value="0" class="form-radio text-[#7F5539]">
                                <span class="ml-2 text-sm text-gray-700">Inactive</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="mt-6 flex justify-end space-x-3 border-t pt-4">
                    <button type="button" @click="closeModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-[#7F5539] text-white rounded-lg hover:bg-[#4A2C1D] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="isSubmitting">
                        <span x-show="!isSubmitting" x-text="isEditing ? 'Update Reward' : 'Create Reward'"></span>
                        <span x-show="isSubmitting" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Saving...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', function() {
    Alpine.data('redeemableItemsData', () => ({
        // State - initialize with server data
        items: window.redeemableItemsData?.items || [],
        branches: window.redeemableItemsData?.branches || [],
        services: window.redeemableItemsData?.services || [],
        products: window.redeemableItemsData?.products || [],
        pagination: window.redeemableItemsData?.pagination || null,
        stats: window.redeemableItemsData?.stats || {},
        rewardTypes: window.redeemableItemsData?.rewardTypes || [],
        paginationLinks: [],
        isLoading: false,
        
        // Filters
        searchQuery: '',
        filterRewardType: '',
        filterStatus: '',
        filterBranch: '',
        
        // Modal
        showModal: false,
        isEditing: false,
        isSubmitting: false,
        branchAutoFilled: false,
        monetaryAutoFilled: false,
        
        form: {
            id: null,
            method: 'POST',
            item_name: '',
            reward_type: '',
            target_service_id: null,
            target_product_id: null,
            monetary_value: null,
            discount_percentage: null,
            item_description: '',
            category: '',
            branch_id: '',
            is_active: 1
        },

        // Alert
        alert: {
            show: false,
            type: 'success',
            title: '',
            message: '',
            timeout: null
        },

        // Searchable Dropdowns
        serviceSearch: '',
        productSearch: '',
        showServiceDropdown: false,
        showProductDropdown: false,
        selectedService: null,
        selectedProduct: null,

        // Computed: Filtered Services
        get filteredServices() {
            if (!this.serviceSearch || this.serviceSearch.trim() === '') {
                return this.services || [];
            }
            const search = this.serviceSearch.toLowerCase().trim();
            return (this.services || []).filter(service => {
                return (service.name || '').toLowerCase().includes(search) ||
                       (service.category || '').toLowerCase().includes(search) ||
                       (service.branch || '').toLowerCase().includes(search);
            });
        },

        // Computed: Filtered Products
        get filteredProducts() {
            if (!this.productSearch || this.productSearch.trim() === '') {
                return this.products || [];
            }
            const search = this.productSearch.toLowerCase().trim();
            return (this.products || []).filter(product => {
                return (product.name || '').toLowerCase().includes(search) ||
                       (product.branch || '').toLowerCase().includes(search);
            });
        },

        // Init
        init() {
            if (!this.items || this.items.length === 0) {
                this.loadItems();
            } else {
                this.updatePaginationLinks();
            }
        },

        // Alert Helper
        showAlert(type, title, message) {
            if (this.alert.timeout) {
                clearTimeout(this.alert.timeout);
            }
            this.alert.type = type;
            this.alert.title = title;
            this.alert.message = message;
            this.alert.show = true;
            this.alert.timeout = setTimeout(() => {
                this.alert.show = false;
            }, 5000);
        },

        // Load Items
        async loadItems(page = 1) {
            this.isLoading = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    search: this.searchQuery,
                    reward_type: this.filterRewardType,
                    status: this.filterStatus,
                    branch_id: this.filterBranch,
                    ajax: true
                });

                const response = await fetch(`/sub_one/redeemable-items?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) throw new Error('Failed to load items');

                const data = await response.json();
                
                if (data.success) {
                    this.items = data.data || [];
                    this.pagination = data.pagination || null;
                    this.branches = data.branches || [];
                    this.services = data.services || [];
                    this.products = data.products || [];
                    this.stats = data.stats || {};
                    this.rewardTypes = data.rewardTypes || [];
                    this.updatePaginationLinks();
                } else {
                    throw new Error(data.message || 'Failed to load items');
                }
            } catch (error) {
                console.error('Error loading items:', error);
                this.showAlert('error', 'Error', 'Failed to load rewards. Please refresh the page.');
            } finally {
                this.isLoading = false;
            }
        },

        // Pagination
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

        async changePage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            await this.loadItems(page);
            document.querySelector('.overflow-x-auto')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        },

        // Filters
        async applyFilters() {
            await this.loadItems(1);
        },

        clearFilters() {
            this.searchQuery = '';
            this.filterRewardType = '';
            this.filterStatus = '';
            this.filterBranch = '';
            this.applyFilters();
        },

        // Modal
        openAddModal() {
            this.isEditing = false;
            this.branchAutoFilled = false;
            this.monetaryAutoFilled = false;
            this.alert.show = false;
            this.form = {
                id: null,
                method: 'POST',
                item_name: '',
                reward_type: '',
                target_service_id: null,
                target_product_id: null,
                monetary_value: null,
                discount_percentage: null,
                item_description: '',
                category: '',
                branch_id: '',
                is_active: 1
            };
            this.selectedService = null;
            this.selectedProduct = null;
            this.serviceSearch = '';
            this.productSearch = '';
            this.showServiceDropdown = false;
            this.showProductDropdown = false;
            this.showModal = true;
            setTimeout(() => {
                const firstInput = document.querySelector('.modal-content input:not([type="hidden"])');
                if (firstInput) firstInput.focus();
            }, 100);
        },

        async openEditModal(id) {
            try {
                const response = await fetch(`/sub_one/redeemable-items/${id}/data`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) throw new Error('Failed to load item data');

                const data = await response.json();
                
                if (data.success) {
                    const item = data.data;
                    this.isEditing = true;
                    this.branchAutoFilled = false;
                    this.monetaryAutoFilled = false;
                    this.alert.show = false;
                    this.form = {
                        id: item.id,
                        method: 'PATCH',
                        item_name: item.item_name || '',
                        reward_type: item.reward_type || '',
                        target_service_id: item.target_service_id || null,
                        target_product_id: item.target_product_id || null,
                        monetary_value: item.monetary_value || null,
                        discount_percentage: item.discount_percentage || null,
                        item_description: item.item_description || '',
                        category: item.category || '',
                        branch_id: item.branch_id || '',
                        is_active: item.is_active ? 1 : 0
                    };
                    
                    if (item.target_service_id) {
                        this.selectedService = (this.services || []).find(s => s.id === item.target_service_id);
                        if (this.selectedService) {
                            this.serviceSearch = this.selectedService.name || '';
                            if (this.selectedService.branch_id && !this.form.branch_id) {
                                this.form.branch_id = this.selectedService.branch_id;
                                this.branchAutoFilled = true;
                            }
                            if (this.selectedService.price && !this.form.monetary_value && this.form.reward_type === 'free_service') {
                                this.form.monetary_value = parseFloat(this.selectedService.price) || 0;
                                this.monetaryAutoFilled = true;
                            }
                        }
                    }
                    if (item.target_product_id) {
                        this.selectedProduct = (this.products || []).find(p => p.id === item.target_product_id);
                        if (this.selectedProduct) {
                            this.productSearch = this.selectedProduct.name || '';
                            if (this.selectedProduct.branch_id && !this.form.branch_id) {
                                this.form.branch_id = this.selectedProduct.branch_id;
                                this.branchAutoFilled = true;
                            }
                            if (this.selectedProduct.price && !this.form.monetary_value && this.form.reward_type === 'free_product') {
                                this.form.monetary_value = parseFloat(this.selectedProduct.price) || 0;
                                this.monetaryAutoFilled = true;
                            }
                        }
                    }
                    
                    // For percentage discount, calculate the monetary value if both percentage and target exist
                    if (this.form.reward_type === 'percentage_discount' && this.form.discount_percentage) {
                        this.calculateMonetaryValue();
                    }
                    
                    this.showServiceDropdown = false;
                    this.showProductDropdown = false;
                    this.showModal = true;
                }
            } catch (error) {
                console.error('Error loading item:', error);
                this.showAlert('error', 'Error', 'Failed to load reward data');
            }
        },

        closeModal() {
            this.showModal = false;
            this.isSubmitting = false;
            this.showServiceDropdown = false;
            this.showProductDropdown = false;
            this.branchAutoFilled = false;
            this.monetaryAutoFilled = false;
            this.alert.show = false;
        },

        onTypeChange() {
            this.form.target_service_id = null;
            this.form.target_product_id = null;
            this.form.monetary_value = null;
            this.form.discount_percentage = null;
            this.selectedService = null;
            this.selectedProduct = null;
            this.serviceSearch = '';
            this.productSearch = '';
            this.showServiceDropdown = false;
            this.showProductDropdown = false;
            this.monetaryAutoFilled = false;
            if (this.branchAutoFilled) {
                this.form.branch_id = '';
                this.branchAutoFilled = false;
            }
        },

        // Calculate Monetary Value for Percentage Discount
        calculateMonetaryValue() {
            if (this.form.reward_type === 'percentage_discount') {
                const discountPercentage = parseFloat(this.form.discount_percentage);
                if (discountPercentage && discountPercentage > 0) {
                    // Convert percentage to decimal (e.g., 10% -> 0.10)
                    const decimalValue = discountPercentage / 100;
                    this.form.monetary_value = decimalValue;
                    this.monetaryAutoFilled = true;
                } else {
                    if (this.form.discount_percentage === '' || parseFloat(this.form.discount_percentage) === 0) {
                        this.form.monetary_value = null;
                        this.monetaryAutoFilled = false;
                    }
                }
            }
        },

        // Service Dropdown Methods
        selectService(service) {
            if (!service) return;
            this.form.target_service_id = service.id;
            this.selectedService = service;
            this.serviceSearch = service.name || '';
            this.showServiceDropdown = false;
            
            if (service.branch_id) {
                this.form.branch_id = service.branch_id;
                this.branchAutoFilled = true;
            } else {
                this.branchAutoFilled = false;
            }
            
            // For free_service, auto-fill monetary value
            if (this.form.reward_type === 'free_service') {
                const price = parseFloat(service.price) || 0;
                if (price > 0) {
                    this.form.monetary_value = price;
                    this.monetaryAutoFilled = true;
                } else {
                    this.monetaryAutoFilled = false;
                }
            }
            
            // For percentage_discount, calculate the monetary value
            if (this.form.reward_type === 'percentage_discount' && this.form.discount_percentage) {
                this.calculateMonetaryValue();
            }
        },

        selectFirstService() {
            if (this.filteredServices.length > 0) {
                this.selectService(this.filteredServices[0]);
            }
        },

        clearSelectedService() {
            this.form.target_service_id = null;
            this.selectedService = null;
            this.serviceSearch = '';
            this.showServiceDropdown = false;
            if (this.branchAutoFilled) {
                this.form.branch_id = '';
                this.branchAutoFilled = false;
            }
            if (this.monetaryAutoFilled) {
                this.form.monetary_value = null;
                this.monetaryAutoFilled = false;
            }
            // Recalculate percentage discount if applicable
            if (this.form.reward_type === 'percentage_discount' && this.form.discount_percentage) {
                this.calculateMonetaryValue();
            }
        },

        // Product Dropdown Methods
        selectProduct(product) {
            if (!product) return;
            this.form.target_product_id = product.id;
            this.selectedProduct = product;
            this.productSearch = product.name || '';
            this.showProductDropdown = false;
            
            if (product.branch_id) {
                this.form.branch_id = product.branch_id;
                this.branchAutoFilled = true;
            } else {
                this.branchAutoFilled = false;
            }
            
            // For free_product, auto-fill monetary value
            if (this.form.reward_type === 'free_product') {
                const price = parseFloat(product.price) || 0;
                if (price > 0) {
                    this.form.monetary_value = price;
                    this.monetaryAutoFilled = true;
                } else {
                    this.monetaryAutoFilled = false;
                }
            }
            
            // For percentage_discount, calculate the monetary value
            if (this.form.reward_type === 'percentage_discount' && this.form.discount_percentage) {
                this.calculateMonetaryValue();
            }
        },

        selectFirstProduct() {
            if (this.filteredProducts.length > 0) {
                this.selectProduct(this.filteredProducts[0]);
            }
        },

        clearSelectedProduct() {
            this.form.target_product_id = null;
            this.selectedProduct = null;
            this.productSearch = '';
            this.showProductDropdown = false;
            if (this.branchAutoFilled) {
                this.form.branch_id = '';
                this.branchAutoFilled = false;
            }
            if (this.monetaryAutoFilled) {
                this.form.monetary_value = null;
                this.monetaryAutoFilled = false;
            }
            // Recalculate percentage discount if applicable
            if (this.form.reward_type === 'percentage_discount' && this.form.discount_percentage) {
                this.calculateMonetaryValue();
            }
        },

        // Branch Auto-Fill Methods
        clearBranchAutoFill() {
            this.form.branch_id = '';
            this.branchAutoFilled = false;
        },

        // Submit Form
        async submitForm() {
            console.log('Submitting form...', this.form);

            // Basic validation
            if (!this.form.item_name || !this.form.item_name.trim()) {
                this.showAlert('error', 'Validation Error', 'Reward name is required');
                return;
            }

            if (!this.form.reward_type) {
                this.showAlert('error', 'Validation Error', 'Reward type is required');
                return;
            }

            // Validate based on reward type
            if (this.form.reward_type === 'free_service') {
                if (!this.form.target_service_id) {
                    this.showAlert('error', 'Validation Error', 'Please select a target space/service');
                    return;
                }
                const monetaryValue = parseFloat(this.form.monetary_value);
                if (!this.form.monetary_value || isNaN(monetaryValue) || monetaryValue <= 0) {
                    this.showAlert('error', 'Validation Error', 'Service price is required and must be greater than 0');
                    return;
                }
                this.form.monetary_value = monetaryValue;
            } else if (this.form.reward_type === 'free_product') {
                if (!this.form.target_product_id) {
                    this.showAlert('error', 'Validation Error', 'Please select a target product');
                    return;
                }
                const monetaryValue = parseFloat(this.form.monetary_value);
                if (!this.form.monetary_value || isNaN(monetaryValue) || monetaryValue <= 0) {
                    this.showAlert('error', 'Validation Error', 'Product price is required and must be greater than 0');
                    return;
                }
                this.form.monetary_value = monetaryValue;
            } else if (this.form.reward_type === 'fixed_discount') {
                const monetaryValue = parseFloat(this.form.monetary_value);
                if (!this.form.monetary_value || isNaN(monetaryValue) || monetaryValue <= 0) {
                    this.showAlert('error', 'Validation Error', 'Discount amount is required and must be greater than 0');
                    return;
                }
                this.form.monetary_value = monetaryValue;
                this.form.discount_percentage = null;
            } else if (this.form.reward_type === 'percentage_discount') {
                const discountValue = parseFloat(this.form.discount_percentage);
                if (!this.form.discount_percentage || isNaN(discountValue) || discountValue <= 0) {
                    this.showAlert('error', 'Validation Error', 'Discount percentage is required and must be greater than 0');
                    return;
                }
                if (discountValue > 100) {
                    this.showAlert('error', 'Validation Error', 'Discount percentage cannot exceed 100');
                    return;
                }
                this.form.discount_percentage = discountValue;
                // Ensure monetary_value is calculated
                if (!this.form.monetary_value || parseFloat(this.form.monetary_value) <= 0) {
                    this.form.monetary_value = discountValue / 100;
                }
            }

            this.isSubmitting = true;

            try {
                const url = this.isEditing 
                    ? `/sub_one/redeemable-items/${this.form.id}`
                    : '/sub_one/redeemable-items';
                
                const method = this.isEditing ? 'PATCH' : 'POST';

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                const payload = {
                    item_name: this.form.item_name.trim(),
                    reward_type: this.form.reward_type,
                    target_service_id: this.form.reward_type === 'free_service' ? parseInt(this.form.target_service_id) : null,
                    target_product_id: this.form.reward_type === 'free_product' ? parseInt(this.form.target_product_id) : null,
                    monetary_value: this.form.monetary_value ? parseFloat(this.form.monetary_value) : null,
                    discount_percentage: this.form.reward_type === 'percentage_discount' ? parseFloat(this.form.discount_percentage) : null,
                    item_description: this.form.item_description ? this.form.item_description.trim() : null,
                    category: this.form.category ? this.form.category.trim() : null,
                    branch_id: this.form.branch_id || null,
                    is_active: this.form.is_active === 1 ? true : false
                };

                console.log('Sending payload:', payload);

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                });

                let responseData;
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    responseData = await response.json();
                } else {
                    const text = await response.text();
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned non-JSON response. Check server logs.');
                }

                if (responseData.success) {
                    this.showAlert('success', 'Success', responseData.message);
                    setTimeout(() => {
                        this.closeModal();
                        this.loadItems();
                    }, 1000);
                } else {
                    if (responseData.errors) {
                        let errorMsg = '';
                        for (const [field, errors] of Object.entries(responseData.errors)) {
                            errorMsg += `${field}: ${errors.join(', ')}\n`;
                        }
                        this.showAlert('error', 'Validation Error', errorMsg);
                    } else {
                        throw new Error(responseData.message || 'Failed to save reward');
                    }
                }
            } catch (error) {
                console.error('Error saving reward:', error);
                this.showAlert('error', 'Error', error.message || 'Failed to save reward. Please check console for details.');
            } finally {
                this.isSubmitting = false;
            }
        },

        // Toggle Status
        async toggleStatus(id) {
            if (!confirm('Are you sure you want to toggle the status of this reward?')) return;

            try {
                const response = await fetch(`/sub_one/redeemable-items/${id}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showAlert('success', 'Success', data.message);
                    await this.loadItems();
                } else {
                    throw new Error(data.message || 'Failed to toggle status');
                }
            } catch (error) {
                console.error('Error toggling status:', error);
                this.showAlert('error', 'Error', error.message || 'Failed to toggle status');
            }
        },

        // Delete Item
        async deleteItem(id) {
            const item = this.items.find(i => i.id === id);
            if (item && item.loyalty_tiers_count > 0) {
                this.showAlert('error', 'Cannot Delete', 'This reward is linked to one or more loyalty tiers and cannot be deleted');
                return;
            }

            if (!confirm('Are you sure you want to delete this reward? This action cannot be undone.')) return;

            try {
                const response = await fetch(`/sub_one/redeemable-items/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showAlert('success', 'Success', data.message);
                    await this.loadItems();
                } else {
                    throw new Error(data.message || 'Failed to delete reward');
                }
            } catch (error) {
                console.error('Error deleting reward:', error);
                this.showAlert('error', 'Error', error.message || 'Failed to delete reward');
            }
        }
    }));
});
</script>
@endsection