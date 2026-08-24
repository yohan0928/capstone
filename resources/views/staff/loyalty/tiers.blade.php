@extends('layouts.app')

@section('content')
<style>
    [x-cloak] { display: none !important; }
    
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
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
    }
    
    .type-product {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .type-service {
        background: #fce7f3;
        color: #9d174d;
    }
    
    .type-discount-fixed {
        background: #fef3c7;
        color: #92400e;
    }
    
    .type-discount-percentage {
        background: #ede9fe;
        color: #5b21b6;
    }

    .btn-secondary {
        background: #6b7280;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #4b5563;
    }

    /* Modal Styles */
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
        max-width: 700px;
        width: 100%;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        animation: slideDown 0.3s ease-out;
        overflow: hidden;
    }

    .modal-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        flex-shrink: 0;
        background: white;
        border-radius: 0.75rem 0.75rem 0 0;
    }

    .modal-body {
        padding: 1.5rem;
        overflow-y: auto;
        flex: 1 1 auto;
        max-height: calc(90vh - 140px);
    }

    .modal-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid #e5e7eb;
        flex-shrink: 0;
        background: white;
        border-radius: 0 0 0.75rem 0.75rem;
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }

    .modal-body::-webkit-scrollbar {
        width: 6px;
    }

    .modal-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .modal-body::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
    }

    .modal-body::-webkit-scrollbar-thumb:hover {
        background: #555;
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

    body.modal-open {
        overflow: hidden;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.25rem;
    }

    .form-control {
        width: 100%;
        padding: 0.5rem 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .form-control:focus {
        outline: none;
        border-color: #7F5539;
        box-shadow: 0 0 0 3px rgba(127, 85, 57, 0.2);
    }

    .form-control:disabled {
        background-color: #f3f4f6;
        cursor: not-allowed;
    }

    textarea.form-control {
        resize: vertical;
        min-height: 60px;
    }

    select.form-control {
        appearance: auto;
    }

    .branch-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        background: #f3f4f6;
        border-radius: 9999px;
        font-size: 0.75rem;
        color: #374151;
    }
</style>

@php
    $formattedItems = $redeemableItems ? $redeemableItems->map(function($item) {
        return [
            'id' => $item->id,
            'item_name' => $item->item_name,
            'item_type' => $item->item_type,
            'monetary_value' => $item->monetary_value,
            'discount_percentage' => $item->discount_percentage,
            'branch_id' => $item->branch_id,
            'value_display' => $item->value_display,
            'type_label' => $item->type_label
        ];
    })->values() : collect();
@endphp

<script>
    window.loyaltyTiersData = {
        branches: @json($branches ?? []),
        redeemableItems: @json($formattedItems),
        tiersData: @json($loyaltyTiers->items() ?? [])
    };
</script>

<!-- Header -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Loyalty Tiers</h1>
            <p class="text-gray-600">Manage your branch's loyalty program tiers and rewards</p>
            @if($branches->count() > 0)
                <p class="text-sm text-coffee-600 mt-1">
                    <span class="font-medium">Branch:</span> {{ $branches->first()->branch_name }}
                </p>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('sub_two.redeemable_items.index') }}" 
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                Manage Redeemable Items
            </a>
            <button onclick="openAddModal()" 
                    class="px-4 py-2 bg-[#7F5539] text-white rounded-lg hover:bg-[#4A2C1D] transition-colors flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Loyalty Tier
            </button>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <p class="text-sm text-gray-600">Total Tiers</p>
            <p class="text-2xl font-bold text-gray-900">{{ $loyaltyTiers->total() }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <p class="text-sm text-gray-600">Active Tiers</p>
            <p class="text-2xl font-bold text-green-600">
                {{ $loyaltyTiers->where('reward_tier_status', 1)->count() }}
            </p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <p class="text-sm text-gray-600">Linked to Items</p>
            <p class="text-2xl font-bold text-blue-600">
                {{ $loyaltyTiers->whereNotNull('redeemable_item_id')->count() }}
            </p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <p class="text-sm text-gray-600">Total Rewards Claimed</p>
            <p class="text-2xl font-bold text-purple-600">0</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <p class="text-sm text-gray-600">Redeemable Items</p>
            <p class="text-2xl font-bold text-orange-600">{{ $redeemableItems->count() }}</p>
        </div>
    </div>

    <!-- Info Alert -->
    @if($redeemableItems->isEmpty())
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    <strong>No redeemable items found!</strong> 
                    You need to create redeemable items before you can link them to loyalty tiers.
                </p>
                <a href="{{ route('sub_two.redeemable_items.index') }}" 
                   class="mt-2 inline-flex items-center px-3 py-1 bg-yellow-100 text-yellow-800 rounded-lg hover:bg-yellow-200 transition-colors text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Create Redeemable Items Now
                </a>
            </div>
        </div>
    </div>
    @endif

    <!-- Search and Filter -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex-1">
                <input type="text" 
                       id="searchInput"
                       placeholder="Search by tier name or description..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]">
            </div>
            <div class="flex flex-wrap gap-2">
                <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#7F5539]">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
                <button onclick="applyFilters()" class="px-4 py-2 bg-[#7F5539] text-white rounded-lg hover:bg-[#4A2C1D] transition-colors">
                    Apply Filters
                </button>
                <button onclick="resetFilters()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                    Reset
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Tier Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Bookings Required</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap" style="min-width: 280px;">Redeemable Item</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($loyaltyTiers as $tier)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $tier->tier_name }}</div>
                            <div class="text-xs text-gray-500">Prefix: {{ $tier->voucher_prefix ?? 'N/A' }}</div>
                            @if($tier->is_in_use)
                                <span class="inline-block mt-1 text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">In Use</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-[#7F5539]">{{ $tier->reward_required }}</div>
                            <div class="text-xs text-gray-500">bookings</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 max-w-xs truncate">{{ $tier->reward_description }}</div>
                            @if($tier->date_start || $tier->date_end)
                                <div class="text-xs text-gray-500">
                                    @if($tier->date_start)
                                        {{ \Carbon\Carbon::parse($tier->date_start)->format('M d') }}
                                    @endif
                                    @if($tier->date_end)
                                        - {{ \Carbon\Carbon::parse($tier->date_end)->format('M d, Y') }}
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($tier->redeemableItem)
                                <div class="text-sm text-gray-900">{{ $tier->redeemableItem->item_name }}</div>
                                <span class="type-badge type-product text-xs">
                                    {{ $tier->redeemableItem->type_label }}
                                </span>
                                <div class="text-xs text-gray-500">
                                    {{ $tier->redeemableItem->value_display }}
                                </div>
                            @else
                                <span class="text-sm text-gray-400">No item linked</span>
                                <a href="{{ route('sub_two.redeemable_items.index') }}" 
                                   class="text-xs text-blue-600 hover:text-blue-800 block mt-1">
                                    Create Item →
                                </a>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="status-badge {{ $tier->reward_tier_status ? 'status-active' : 'status-inactive' }}">
                                {{ $tier->reward_tier_status ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-2">
                                <button onclick="openEditModal('{{ $tier->id }}')" 
                                        class="px-3 py-1 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm transition-colors">
                                    Edit
                                </button>
                                <button onclick="toggleStatus('{{ $tier->id }}')" 
                                        class="px-3 py-1 {{ $tier->reward_tier_status ? 'bg-yellow-500 hover:bg-yellow-600' : 'bg-green-500 hover:bg-green-600' }} text-white rounded-lg text-sm transition-colors">
                                    {{ $tier->reward_tier_status ? 'Deactivate' : 'Activate' }}
                                </button>
                                @if(!$tier->is_in_use)
                                    <button onclick="deleteTier('{{ $tier->id }}')" 
                                            class="px-3 py-1 bg-red-500 text-white rounded-lg hover:bg-red-600 text-sm transition-colors">
                                        Delete
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400 italic">In Use</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            <p class="text-lg font-medium text-gray-900">No loyalty tiers created yet</p>
                            <p class="text-sm text-gray-500">Create your first loyalty tier to start rewarding customers</p>
                            @if($redeemableItems->isEmpty())
                                <p class="text-sm text-yellow-600 mt-2">
                                    <a href="{{ route('sub_two.redeemable_items.index') }}" class="text-blue-600 hover:underline">
                                        Create redeemable items first →
                                    </a>
                                </p>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $loyaltyTiers->links() }}
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="tierModal" class="modal-overlay hidden" onclick="if(event.target === this) closeModal()">
    <div class="modal-content">
        <!-- Fixed Header -->
        <div class="modal-header">
            <div class="flex justify-between items-center">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">Add Loyalty Tier</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Scrollable Body -->
        <div class="modal-body">
            <form id="tierForm" onsubmit="submitForm(event)">
                @csrf
                <input type="hidden" id="tierId" name="tierId">
                <input type="hidden" id="_method" name="_method" value="POST">
                <input type="hidden" id="branchId" name="branch_id" value="{{ $branches->first()->id ?? '' }}">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Tier Name -->
                    <div class="form-group">
                        <label class="form-label" for="tierName">Tier Name *</label>
                        <input type="text" id="tierName" name="tier_name" required
                               class="form-control"
                               placeholder="e.g., Bronze, Silver, Gold">
                    </div>

                    <!-- Bookings Required -->
                    <div class="form-group">
                        <label class="form-label" for="rewardRequired">Bookings Required *</label>
                        <input type="number" id="rewardRequired" name="reward_required" required min="1"
                               class="form-control"
                               placeholder="e.g., 5">
                    </div>

                    <!-- Branch Display (Read-only) -->
                    <div class="form-group">
                        <label class="form-label">Branch</label>
                        <div class="form-control bg-gray-100 cursor-not-allowed" style="background-color: #f3f4f6;">
                            @if($branches->count() > 0)
                                {{ $branches->first()->branch_name }}
                            @else
                                No branch assigned
                            @endif
                        </div>
                    </div>

                    <!-- Redeemable Item -->
                    <div class="form-group">
                        <label class="form-label" for="redeemableItemId">Redeemable Item</label>
                        <select id="redeemableItemId" name="redeemable_item_id" 
                                class="form-control">
                            <option value="">Select Item</option>
                            @foreach($redeemableItems as $item)
                                <option value="{{ $item->id }}">
                                    {{ $item->item_name }} ({{ $item->value_display }})
                                </option>
                            @endforeach
                        </select>
                        @if($redeemableItems->isEmpty())
                            <p class="text-xs text-yellow-600 mt-1">
                                <a href="{{ route('sub_two.redeemable_items.index') }}" class="text-blue-600 hover:underline">
                                    No items available. Create one →
                                </a>
                            </p>
                        @endif
                    </div>

                    <!-- Reward Description -->
                    <div class="form-group md:col-span-2">
                        <label class="form-label" for="rewardDescription">Reward Description *</label>
                        <textarea id="rewardDescription" name="reward_description" rows="2" required
                                  class="form-control"
                                  placeholder="Describe what the customer gets..."></textarea>
                    </div>

                    <!-- Date Start -->
                    <div class="form-group">
                        <label class="form-label" for="dateStart">Start Date</label>
                        <input type="date" id="dateStart" name="date_start"
                               class="form-control">
                    </div>

                    <!-- Date End -->
                    <div class="form-group">
                        <label class="form-label" for="dateEnd">End Date</label>
                        <input type="date" id="dateEnd" name="date_end"
                               class="form-control">
                    </div>

                    <!-- Start Time -->
                    <div class="form-group">
                        <label class="form-label" for="startTime">Start Time</label>
                        <input type="time" id="startTime" name="start_time"
                               class="form-control">
                    </div>

                    <!-- End Time -->
                    <div class="form-group">
                        <label class="form-label" for="endTime">End Time</label>
                        <input type="time" id="endTime" name="end_time"
                               class="form-control">
                    </div>

                    <!-- Expiry Duration -->
                    <div class="form-group">
                        <label class="form-label" for="expiryDuration">Expiry Duration (days)</label>
                        <input type="number" id="expiryDuration" name="expiry_duration" value="30" min="1" max="365"
                               class="form-control">
                    </div>

                    <!-- Voucher Prefix -->
                    <div class="form-group">
                        <label class="form-label" for="voucherPrefix">Voucher Prefix</label>
                        <input type="text" id="voucherPrefix" name="voucher_prefix" maxlength="10"
                               class="form-control"
                               placeholder="e.g., RWD, COFFEE">
                    </div>
                </div>

                <!-- Status -->
                <div class="form-group mt-2">
                    <label class="form-label" for="rewardTierStatus">Status</label>
                    <select id="rewardTierStatus" name="reward_tier_status" 
                            class="form-control">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- Fixed Footer -->
        <div class="modal-footer">
            <button type="button" onclick="closeModal()" 
                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                Cancel
            </button>
            <button type="submit" form="tierForm" id="submitButton"
                    class="px-4 py-2 bg-[#7F5539] text-white rounded-lg hover:bg-[#4A2C1D] transition-colors">
                <span id="submitButtonText">Create Tier</span>
            </button>
        </div>
    </div>
</div>

<script>
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const allRedeemableItems = @json($redeemableItems ?? []);
    const allTiersData = @json($loyaltyTiers->items() ?? []);

    // Open Add Modal
    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'Add Loyalty Tier';
        document.getElementById('submitButtonText').textContent = 'Create Tier';
        document.getElementById('tierForm').reset();
        document.getElementById('tierId').value = '';
        document.getElementById('_method').value = 'POST';
        document.getElementById('tierModal').classList.remove('hidden');
        document.getElementById('rewardTierStatus').value = '1';
        document.body.classList.add('modal-open');
    }

    // Open Edit Modal
    function openEditModal(id) {
        document.getElementById('modalTitle').textContent = 'Loading...';
        document.getElementById('submitButtonText').textContent = 'Loading...';
        document.getElementById('tierModal').classList.remove('hidden');
        document.body.classList.add('modal-open');
        
        const url = `/sub_two/loyalty-tiers/${id}/data`;
        
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Cache-Control': 'no-cache'
            },
            credentials: 'include',
            cache: 'no-cache'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const tier = data.data;
                document.getElementById('modalTitle').textContent = 'Edit Loyalty Tier';
                document.getElementById('submitButtonText').textContent = 'Update Tier';
                document.getElementById('tierId').value = tier.id;
                document.getElementById('_method').value = 'PATCH';
                document.getElementById('tierName').value = tier.tier_name || '';
                document.getElementById('rewardRequired').value = tier.reward_required || '';
                document.getElementById('rewardDescription').value = tier.reward_description || '';
                document.getElementById('dateStart').value = tier.date_start || '';
                document.getElementById('dateEnd').value = tier.date_end || '';
                document.getElementById('startTime').value = tier.start_time || '';
                document.getElementById('endTime').value = tier.end_time || '';
                document.getElementById('expiryDuration').value = tier.expiry_duration || 30;
                document.getElementById('voucherPrefix').value = tier.voucher_prefix || '';
                document.getElementById('rewardTierStatus').value = tier.reward_tier_status || 1;
                document.getElementById('redeemableItemId').value = tier.redeemable_item_id || '';
            } else {
                throw new Error(data.message || 'Failed to load tier data');
            }
        })
        .catch(error => {
            console.error('Error fetching tier data:', error);
            alert('Failed to load tier data: ' + error.message);
            closeModal();
        });
    }

    // Close Modal
    function closeModal() {
        document.getElementById('tierModal').classList.add('hidden');
        document.body.classList.remove('modal-open');
    }

    // Submit Form
    function submitForm(event) {
        event.preventDefault();
        
        const formData = new FormData(document.getElementById('tierForm'));
        const tierId = document.getElementById('tierId').value;
        const method = document.getElementById('_method').value;
        const url = tierId ? `/sub_two/loyalty-tiers/${tierId}` : '/sub_two/loyalty-tiers';
        
        const data = {
            tier_name: formData.get('tier_name'),
            reward_required: parseInt(formData.get('reward_required')),
            reward_description: formData.get('reward_description'),
            branch_id: formData.get('branch_id') || null,
            redeemable_item_id: formData.get('redeemable_item_id') || null,
            date_start: formData.get('date_start') || null,
            date_end: formData.get('date_end') || null,
            start_time: formData.get('start_time') || null,
            end_time: formData.get('end_time') || null,
            expiry_duration: parseInt(formData.get('expiry_duration')) || 30,
            voucher_prefix: formData.get('voucher_prefix') || null,
            reward_tier_status: parseInt(formData.get('reward_tier_status')) || 1
        };

        const submitBtn = document.getElementById('submitButton');
        
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';
        }

        fetch(url, {
            method: method === 'PATCH' ? 'PATCH' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = tierId ? 'Update Tier' : 'Create Tier';
                }
            }
        })
        .catch(error => {
            console.error('Error submitting form:', error);
            alert('Failed to save loyalty tier: ' + error.message);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = tierId ? 'Update Tier' : 'Create Tier';
            }
        });
    }

    // Toggle Status
    function toggleStatus(id) {
        if (!confirm('Are you sure you want to toggle the status of this tier?')) return;
        
        fetch(`/sub_two/loyalty-tiers/${id}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error toggling status:', error);
            alert('Failed to toggle status');
        });
    }

    // Delete Tier
    function deleteTier(id) {
        if (!confirm('Are you sure you want to delete this tier? This action cannot be undone.')) return;
        
        fetch(`/sub_two/loyalty-tiers/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error deleting tier:', error);
            alert('Failed to delete tier');
        });
    }

    // Apply Filters
    function applyFilters() {
        const search = document.getElementById('searchInput').value;
        const status = document.getElementById('statusFilter').value;
        
        let url = new URL(window.location.href);
        if (search) url.searchParams.set('search', search);
        else url.searchParams.delete('search');
        if (status) url.searchParams.set('status', status);
        else url.searchParams.delete('status');
        
        window.location.href = url.toString();
    }

    // Reset Filters
    function resetFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('statusFilter').value = '';
        window.location.href = window.location.pathname;
    }

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
</script>
@endsection