@extends('layouts.app')

@section('content')
<style>
    .filter-btn {
        transition: all 0.2s ease;
    }
    .filter-btn.active {
        background: #7F5539 !important;
        color: white !important;
    }
    .filter-btn:not(.active):hover {
        background: #f3f4f6;
    }
    .stat-card {
        background: white;
        border-radius: 1rem;
        padding: 1.25rem;
        border: 1px solid #e5e7eb;
        transition: all 0.3s ease;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.05);
    }
    .tab-nav {
        border-bottom: 2px solid #e5e7eb;
        margin-bottom: 2rem;
    }
    .tab-nav a {
        display: inline-flex;
        align-items: center;
        padding: 0.75rem 1.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        border-bottom: 2px solid transparent;
        transition: all 0.2s ease;
        text-decoration: none;
        color: #6b7280;
    }
    .tab-nav a:hover {
        color: #4A2C1D;
        border-bottom-color: #d1d5db;
    }
    .tab-nav a.active {
        color: #7F5539;
        border-bottom-color: #7F5539;
    }
    .tab-nav a .badge {
        margin-left: 0.5rem;
        padding: 0.1rem 0.5rem;
        font-size: 0.7rem;
        border-radius: 9999px;
        background: #f3f4f6;
        color: #6b7280;
    }
    .tab-nav a.active .badge {
        background: #ede9fe;
        color: #5b21b6;
    }
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
    
    /* Auto width columns */
    .table-auto-width th,
    .table-auto-width td {
        white-space: nowrap;
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    .table-auto-width th:first-child,
    .table-auto-width td:first-child {
        padding-left: 1rem;
    }
    .table-auto-width th:last-child,
    .table-auto-width td:last-child {
        padding-right: 1rem;
    }
</style>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Redemption History</h1>
            <p class="text-gray-600 mt-1">Track all your redeemed rewards and savings</p>
        </div>
        <div class="mt-4 md:mt-0">
            <a href="{{ route('sub_three.my_rewards.showMyRewards') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Rewards
            </a>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="tab-nav">
        <a href="{{ route('sub_three.my_rewards.showMyRewards') }}">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v1m0-1v-1m0 1v1m0-1v1m0-1v1m0-1v1m0-1v1m0-1v1m0-1v1m0-1v1m0-1v1m0-1v1"/>
            </svg>
            My Rewards
            <span class="badge">{{ $stats['total_earned_rewards'] ?? 0 }}</span>
        </a>
        <a href="{{ route('sub_three.my_rewards.redemptionHistory') }}" class="active">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            Redemption History
            <span class="badge">{{ $summary['total_redemptions'] ?? 0 }}</span>
        </a>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="stat-card">
            <p class="text-sm font-medium text-gray-500">Total Redemptions</p>
            <p class="text-2xl font-bold text-purple-600">{{ $summary['total_redemptions'] ?? 0 }}</p>
        </div>
        <div class="stat-card">
            <p class="text-sm font-medium text-gray-500">Free Services</p>
            <p class="text-2xl font-bold text-blue-600">{{ $summary['total_free_services'] ?? 0 }}</p>
        </div>
        <div class="stat-card">
            <p class="text-sm font-medium text-gray-500">Free Products</p>
            <p class="text-2xl font-bold text-teal-600">{{ $summary['total_free_products'] ?? 0 }}</p>
        </div>
        <div class="stat-card">
            <p class="text-sm font-medium text-gray-500">Discounts Used</p>
            <p class="text-2xl font-bold text-orange-600">{{ ($summary['total_fixed_discounts'] ?? 0) + ($summary['total_percentage_discounts'] ?? 0) }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex flex-wrap gap-2">
                <button class="filter-btn active px-4 py-2 rounded-lg text-sm font-medium bg-[#7F5539] text-white" data-filter="all">
                    All
                </button>
                <button class="filter-btn px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200" data-filter="free_service">
                    Free Services
                </button>
                <button class="filter-btn px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200" data-filter="free_product">
                    Free Products
                </button>
                <button class="filter-btn px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200" data-filter="fixed_discount">
                    Fixed Discounts
                </button>
                <button class="filter-btn px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200" data-filter="percentage_discount">
                    Percentage Discounts
                </button>
            </div>
            <div class="flex items-center gap-2">
                <input type="text" id="searchInput" placeholder="Search vouchers, services..." 
                       class="flex-1 md:w-64 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#7F5539] focus:border-transparent">
                <button id="searchBtn" class="px-4 py-2 bg-[#7F5539] text-white rounded-lg hover:bg-[#4A2C1D] transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Redemption History Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 table-auto-width">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Claimed</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reward</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Original</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Discount</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Final</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200" id="historyTableBody">
                    @forelse($redemptionHistory as $redemption)
                        <tr class="hover:bg-gray-50 transition-colors cursor-pointer" onclick="viewRedemptionDetails({{ $redemption->id }})">
                            <!-- Date Claimed -->
                            <td class="px-4 py-4 text-sm text-gray-600 whitespace-nowrap">
                                {{ $redemption->redeemed_at ? $redemption->redeemed_at->format('M d, Y h:i A') : 'N/A' }}
                            </td>
                            <!-- Branch -->
                            <td class="px-4 py-4 text-sm text-gray-600 whitespace-nowrap">
                                {{ $redemption->branch->branch_name ?? 'N/A' }}
                            </td>
                            <!-- Reward -->
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $redemption->customerReward->loyaltyTier->reward_description ?? 'N/A' }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    Voucher: {{ $redemption->customerReward->voucher_code ?? 'N/A' }}
                                </div>
                            </td>
                            <!-- Type -->
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="badge-type {{ $redemption->reward_type }}">
                                    {{ $redemption->reward_type_label }}
                                </span>
                            </td>
                            <!-- Original (from service_names price) -->
                            <td class="px-4 py-4 text-sm text-gray-600 whitespace-nowrap">
                                ₱{{ number_format($redemption->original_price ?? 0, 2) }}
                            </td>
                            <!-- Discount -->
                            <td class="px-4 py-4 text-sm text-green-600 font-medium whitespace-nowrap">
                                -₱{{ number_format($redemption->discount_amount ?? 0, 2) }}
                                @if($redemption->discount_value && $redemption->reward_type == 'percentage_discount')
                                    <span class="text-xs text-gray-500 block">({{ $redemption->discount_value }}% off)</span>
                                @endif
                            </td>
                            <!-- Final (Computed: Original - Discount) -->
                            <td class="px-4 py-4 text-sm font-bold text-gray-900 whitespace-nowrap">
                                ₱{{ number_format($redemption->computed_final_amount ?? $redemption->final_amount ?? 0, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                                <p class="text-lg font-medium text-gray-900">No redemption history yet</p>
                                <p class="text-sm text-gray-400 mt-1">Start redeeming your rewards to see them here</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($redemptionHistory->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $redemptionHistory->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Redemption Details Modal -->
<div id="redemptionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4 border-b pb-3">
            <h3 class="text-lg font-semibold text-gray-900">Redemption Details</h3>
            <button onclick="closeRedemptionModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="redemptionDetailsContent">
            <!-- Dynamic content will be loaded here -->
        </div>
    </div>
</div>

<script>
// ============================================================
// HELPER FUNCTIONS
// ============================================================

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function parseFloatOrZero(val) {
    const num = parseFloat(val);
    return isNaN(num) ? 0 : num;
}

function getRewardTypeClass(rewardType) {
    // Map reward types to CSS classes
    const classMap = {
        'free_service': 'free_service',
        'free_product': 'free_product',
        'fixed_discount': 'fixed_discount',
        'percentage_discount': 'percentage_discount',
        'product_discount': 'product_discount'
    };
    return classMap[rewardType] || 'custom';
}

function getRewardTypeLabel(rewardType) {
    const labelMap = {
        'free_service': 'Free Service',
        'free_product': 'Free Product',
        'fixed_discount': 'Fixed Discount',
        'percentage_discount': 'Percentage Discount',
        'product_discount': 'Product Discount'
    };
    return labelMap[rewardType] || 'Custom';
}

// ============================================================
// DOM READY
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const tableBody = document.getElementById('historyTableBody');
    
    let currentFilter = 'all';
    let currentSearch = '';

    function loadHistory(filter = 'all', search = '') {
        const url = new URL('{{ route("sub_three.my_rewards.getRedemptionHistory") }}', window.location.origin);
        url.searchParams.set('filter', filter);
        if (search) {
            url.searchParams.set('search', search);
        }

        fetch(url.toString(), {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateTable(data.data.data);
                updatePagination(data.data);
                updateSummary(data.summary);
            }
        })
        .catch(error => {
            console.error('Error loading history:', error);
        });
    }

    function updateTable(redemptions) {
        if (!redemptions || redemptions.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        <p class="text-lg font-medium text-gray-900">No matching redemptions</p>
                        <p class="text-sm text-gray-400 mt-1">Try adjusting your filters</p>
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        redemptions.forEach(r => {
            // Get reward type - try multiple sources
            let rewardType = r.reward_type || 
                            r.customer_reward?.loyalty_tier?.redeemable_item?.reward_type || 
                            r.customer_reward?.loyalty_tier?.redeemableItem?.reward_type || 
                            'custom';
            
            let rewardTypeLabel = r.reward_type_label || 
                                 getRewardTypeLabel(rewardType);
            
            // Ensure rewardType is valid for CSS class
            const rewardTypeClass = getRewardTypeClass(rewardType);
            
            const originalPrice = parseFloatOrZero(r.original_price);
            const discountAmount = parseFloatOrZero(r.discount_amount);
            const finalAmount = parseFloatOrZero(r.computed_final_amount || r.final_amount);
            
            html += `
                <tr class="hover:bg-gray-50 transition-colors cursor-pointer" onclick="viewRedemptionDetails(${r.id})">
                    <td class="px-4 py-4 text-sm text-gray-600 whitespace-nowrap">
                        ${r.redeemed_at ? new Date(r.redeemed_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'N/A'}
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-600 whitespace-nowrap">
                        ${escapeHtml(r.branch?.branch_name || 'N/A')}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            ${escapeHtml(r.customer_reward?.loyalty_tier?.reward_description || 'N/A')}
                        </div>
                        <div class="text-xs text-gray-500">
                            Voucher: ${escapeHtml(r.customer_reward?.voucher_code || 'N/A')}
                        </div>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap">
                        <span class="badge-type ${rewardTypeClass}">
                            ${escapeHtml(rewardTypeLabel)}
                        </span>
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-600 whitespace-nowrap">
                        ₱${originalPrice.toFixed(2)}
                    </td>
                    <td class="px-4 py-4 text-sm text-green-600 font-medium whitespace-nowrap">
                        -₱${discountAmount.toFixed(2)}
                        ${r.discount_value && rewardType == 'percentage_discount' ? `<span class="text-xs text-gray-500 block">(${escapeHtml(r.discount_value)}% off)</span>` : ''}
                    </td>
                    <td class="px-4 py-4 text-sm font-bold text-gray-900 whitespace-nowrap">
                        ₱${finalAmount.toFixed(2)}
                    </td>
                </tr>
            `;
        });
        tableBody.innerHTML = html;
    }

    function updatePagination(data) {
        const paginationContainer = document.querySelector('.px-6.py-4.border-t');
        if (!paginationContainer) return;
        
        if (data.last_page > 1) {
            let html = '<nav class="flex items-center justify-between">';
            html += '<div class="flex-1 flex justify-between sm:hidden">';
            if (data.prev_page_url) {
                html += `<a href="#" onclick="loadPage('${data.prev_page_url}')" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>`;
            }
            if (data.next_page_url) {
                html += `<a href="#" onclick="loadPage('${data.next_page_url}')" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>`;
            }
            html += '</div>';
            
            html += '<div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">';
            html += `<div><p class="text-sm text-gray-700">Showing <span class="font-medium">${data.from}</span> to <span class="font-medium">${data.to}</span> of <span class="font-medium">${data.total}</span> results</p></div>`;
            
            html += '<div><nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">';
            
            if (data.prev_page_url) {
                html += `<a href="#" onclick="loadPage('${data.prev_page_url}')" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">‹</a>`;
            } else {
                html += `<span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-300 cursor-not-allowed">‹</span>`;
            }
            
            for (let i = 1; i <= data.last_page; i++) {
                if (i === data.current_page) {
                    html += `<span class="relative inline-flex items-center px-4 py-2 border border-[#7F5539] bg-[#7F5539] text-sm font-medium text-white">${i}</span>`;
                } else if (Math.abs(i - data.current_page) <= 2 || i === 1 || i === data.last_page) {
                    html += `<a href="#" onclick="loadPage('${data.path}?page=${i}')" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">${i}</a>`;
                } else if (Math.abs(i - data.current_page) === 3) {
                    html += `<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>`;
                }
            }
            
            if (data.next_page_url) {
                html += `<a href="#" onclick="loadPage('${data.next_page_url}')" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">›</a>`;
            } else {
                html += `<span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-300 cursor-not-allowed">›</span>`;
            }
            
            html += '</nav></div></div></nav>';
            paginationContainer.innerHTML = html;
        } else {
            paginationContainer.innerHTML = '';
        }
    }

    function updateSummary(summary) {
        const stats = document.querySelectorAll('.stat-card');
        if (stats.length >= 4) {
            stats[0].querySelector('p.text-2xl').textContent = summary.total_redemptions || 0;
            stats[1].querySelector('p.text-2xl').textContent = summary.total_free_services || 0;
            stats[2].querySelector('p.text-2xl').textContent = summary.total_free_products || 0;
            stats[3].querySelector('p.text-2xl').textContent = (summary.total_fixed_discounts || 0) + (summary.total_percentage_discounts || 0);
        }
    }

    function loadPage(url) {
        fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateTable(data.data.data);
                updatePagination(data.data);
            }
        })
        .catch(error => {
            console.error('Error loading page:', error);
        });
    }

    // Event Listeners
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            loadHistory(currentFilter, currentSearch);
        });
    });

    searchBtn.addEventListener('click', function() {
        currentSearch = searchInput.value.trim();
        loadHistory(currentFilter, currentSearch);
    });

    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            currentSearch = this.value.trim();
            loadHistory(currentFilter, currentSearch);
        }
    });

    // Initial load
    loadHistory('all', '');
});

// ============================================================
// VIEW REDEMPTION DETAILS - Using old code style
// ============================================================

function viewRedemptionDetails(id) {
    const modal = document.getElementById('redemptionModal');
    const content = document.getElementById('redemptionDetailsContent');
    
    if (!modal || !content) return;
    
    modal.classList.remove('hidden');
    content.innerHTML = `
        <div class="flex items-center justify-center py-8">
            <svg class="animate-spin h-8 w-8 text-[#7F5539]" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="ml-3 text-gray-600">Loading redemption details...</span>
        </div>
    `;
    
    fetch(`{{ route("sub_three.my_rewards.getRedemptionHistory") }}?id=${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data.data) {
            const r = data.data.data.find(item => item.id == id);
            if (r) {
                // Get reward type - try multiple sources
                let rewardType = r.reward_type || 
                                r.customer_reward?.loyalty_tier?.redeemable_item?.reward_type || 
                                r.customer_reward?.loyalty_tier?.redeemableItem?.reward_type || 
                                'custom';
                
                let rewardTypeLabel = r.reward_type_label || 
                                     getRewardTypeLabel(rewardType);
                
                // Ensure rewardType is valid for CSS class
                const rewardTypeClass = getRewardTypeClass(rewardType);
                
                const originalPrice = parseFloatOrZero(r.original_price);
                const discountAmount = parseFloatOrZero(r.discount_amount);
                const finalAmount = parseFloatOrZero(r.computed_final_amount || r.final_amount);
                
                content.innerHTML = `
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h4 class="font-medium text-gray-700">Reward</h4>
                                <p class="text-gray-900">${escapeHtml(r.customer_reward?.loyalty_tier?.reward_description || 'N/A')}</p>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-700">Voucher Code</h4>
                                <p class="text-gray-900 font-mono">${escapeHtml(r.customer_reward?.voucher_code || 'N/A')}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h4 class="font-medium text-gray-700">Type</h4>
                                <span class="badge-type ${rewardTypeClass}">
                                    ${escapeHtml(rewardTypeLabel)}
                                </span>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-700">Branch</h4>
                                <p class="text-gray-900">${escapeHtml(r.branch?.branch_name || 'N/A')}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <h4 class="font-medium text-gray-700">Original Price</h4>
                                <p class="text-gray-900">₱${originalPrice.toFixed(2)}</p>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-700">Discount</h4>
                                <p class="text-green-600 font-medium">-₱${discountAmount.toFixed(2)}</p>
                                ${r.discount_value && rewardType == 'percentage_discount' ? `<p class="text-xs text-gray-500">(${escapeHtml(r.discount_value)}% off)</p>` : ''}
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-700">Final Amount</h4>
                                <p class="text-gray-900 font-bold">₱${finalAmount.toFixed(2)}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h4 class="font-medium text-gray-700">Redeemed On</h4>
                                <p class="text-gray-900">${r.redeemed_at ? new Date(r.redeemed_at).toLocaleString() : 'N/A'}</p>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-700">Receipt Number</h4>
                                <p class="text-gray-900">${escapeHtml(r.receipt_number || 'N/A')}</p>
                            </div>
                        </div>
                        ${r.notes ? `
                            <div>
                                <h4 class="font-medium text-gray-700">Notes</h4>
                                <p class="text-gray-900">${escapeHtml(r.notes)}</p>
                            </div>
                        ` : ''}
                        ${r.customer_name ? `
                            <div>
                                <h4 class="font-medium text-gray-700">Customer</h4>
                                <p class="text-gray-900">${escapeHtml(r.customer_name)}</p>
                            </div>
                        ` : ''}
                    </div>
                `;
            } else {
                content.innerHTML = '<p class="text-red-500 text-center py-8">Redemption record not found.</p>';
            }
        } else {
            content.innerHTML = `<p class="text-red-500 text-center py-8">${escapeHtml(data.message || 'Error loading details.')}</p>`;
        }
    })
    .catch(error => {
        console.error('Error loading redemption details:', error);
        content.innerHTML = '<p class="text-red-500 text-center py-8">Error loading details. Please try again.</p>';
    });
}

function closeRedemptionModal() {
    const modal = document.getElementById('redemptionModal');
    if (modal) modal.classList.add('hidden');
}

// ============================================================
// BIND GLOBAL FUNCTIONS
// ============================================================

window.loadPage = loadPage;
window.viewRedemptionDetails = viewRedemptionDetails;
window.closeRedemptionModal = closeRedemptionModal;
</script>
@endsection