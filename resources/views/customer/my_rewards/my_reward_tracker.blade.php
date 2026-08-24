@extends('layouts.app')

@section('content')
<style>
    [x-cloak] { display: none !important; }

    /* Progress Step Styles */
    .progress-steps-wrapper {
        position: relative;
        margin: 2rem 0;
        width: 100%;
        overflow-x: auto;
        padding: 0.5rem 0;
    }
    
    .progress-steps {
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
        width: 100%;
        min-width: 300px;
    }
    
    .progress-bar-line {
        position: absolute;
        top: 25px;
        left: 0;
        right: 0;
        height: 4px;
        background: #e5e7eb;
        z-index: 1;
    }
    
    .progress-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 2;
        cursor: pointer;
        transition: all 0.3s ease;
        flex: 1;
        min-width: 0;
        padding: 0 0.25rem;
    }
    
    .progress-step:hover {
        transform: translateY(-3px);
    }
    
    .step-circle {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        font-weight: bold;
        transition: all 0.3s ease;
        position: relative;
        z-index: 2;
        background: #e5e7eb;
        color: #9ca3af;
        border: 2px dashed #d1d5db;
        flex-shrink: 0;
    }
    
    .step-circle.completed {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        border: none;
    }
    
    .step-circle.active {
        background: linear-gradient(135deg, #7F5539, #4A2C1D);
        color: white;
        box-shadow: 0 4px 12px rgba(127, 85, 57, 0.4);
        animation: pulse 2s infinite;
        border: none;
    }
    
    .step-circle.locked {
        background: #e5e7eb;
        color: #9ca3af;
        border: 2px dashed #d1d5db;
    }
    
    .step-circle.selected-milestone {
        border: 3px solid #7F5539;
        transform: scale(1.1);
    }
    
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(127, 85, 57, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(127, 85, 57, 0); }
        100% { box-shadow: 0 0 0 0 rgba(127, 85, 57, 0); }
    }
    
    .step-label {
        margin-top: 0.5rem;
        font-size: 0.7rem;
        font-weight: 500;
        text-align: center;
        transition: all 0.3s ease;
        word-break: break-word;
        max-width: 80px;
    }
    
    .step-label.completed { color: #10b981; }
    .step-label.active { color: #7F5539; }
    .step-label.locked { color: #9ca3af; }
    .step-label.selected { color: #7F5539; font-weight: bold; }
    
    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #10b981, #7F5539);
        transition: width 0.5s ease;
        border-radius: 4px;
        width: 0%;
    }
    
    .rewards-grid {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin-top: 2rem;
    }
    
    .reward-card {
        background: white;
        border-radius: 1rem;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #e5e7eb;
        position: relative;
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
    }
    
    .reward-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        border-color: #7F5539;
    }
    
    .reward-card-info {
        flex: 2;
        padding: 1.25rem;
        min-width: 250px;
    }
    
    .reward-card-action {
        flex: 1;
        padding: 1.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-left: 1px solid #e5e7eb;
        background: #f9fafb;
        min-width: 200px;
    }
    
    @media (max-width: 640px) {
        .reward-card { flex-direction: column; }
        .reward-card-action {
            border-left: none;
            border-top: 1px solid #e5e7eb;
        }
    }
    
    .reward-card.claimed { opacity: 0.75; background: #f3f4f6; }
    .reward-card.redeemed { background: #faf5ff; border-color: #d8b4fe; }
    
    .reward-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 500;
    }
    
    .reward-badge.available { background: #dbeafe; color: #1e40af; }
    .reward-badge.claimed { background: #dcfce7; color: #166534; }
    .reward-badge.redeemed { background: #f3e8ff; color: #6b21a8; }
    .reward-badge.expired { background: #fee2e2; color: #991b1b; }
    
    .reward-type-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.65rem;
        font-weight: 500;
    }
    
    .reward-type-badge.free_service { background: #dbeafe; color: #1e40af; }
    .reward-type-badge.free_product { background: #d1fae5; color: #065f46; }
    .reward-type-badge.fixed_discount { background: #fef3c7; color: #92400e; }
    .reward-type-badge.percentage_discount { background: #ede9fe; color: #5b21b6; }
    .reward-type-badge.custom { background: #f3f4f6; color: #374151; }
    
    .claim-btn {
        background: linear-gradient(135deg, #7F5539, #4A2C1D);
        color: white;
        padding: 0.6rem 1.5rem;
        border-radius: 0.5rem;
        font-weight: 500;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        white-space: nowrap;
    }
    
    .claim-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(127, 85, 57, 0.3);
    }
    
    .claim-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .claimed-btn {
        background: #10b981;
        color: white;
        padding: 0.6rem 1.5rem;
        border-radius: 0.5rem;
        font-weight: 500;
        cursor: pointer;
        white-space: nowrap;
        border: none;
        transition: all 0.3s ease;
    }
    
    .claimed-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    .redeemed-btn {
        background: #8b5cf6;
        color: white;
        padding: 0.6rem 1.5rem;
        border-radius: 0.5rem;
        font-weight: 500;
        cursor: pointer;
        white-space: nowrap;
        border: none;
        transition: all 0.3s ease;
    }
    
    .redeemed-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
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
    
    .availability-info {
        font-size: 0.75rem;
        color: #6b7280;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-top: 0.5rem;
    }
    
    .target-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8rem;
        color: #4b5563;
        background: #f3f4f6;
        padding: 0.25rem 0.75rem;
        border-radius: 0.5rem;
        margin-top: 0.25rem;
        flex-wrap: wrap;
    }
    
    .target-info .target-tag {
        display: inline-block;
        padding: 0.1rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.6rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .target-info .target-tag.service { background: #dbeafe; color: #1e40af; }
    .target-info .target-tag.product { background: #d1fae5; color: #065f46; }
    
    .target-info .target-name {
        font-weight: 500;
        color: #1f2937;
    }
    
    .target-info .target-category {
        color: #6b7280;
        font-size: 0.7rem;
    }

    /* Modal Styles */
    #rewardModal {
        animation: fadeIn 0.3s ease-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    #rewardModal .modal-content {
        animation: slideDown 0.3s ease-out;
    }
    
    @keyframes slideDown {
        from { transform: translateY(-50px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    .voucher-code {
        font-family: 'Courier New', monospace;
        font-size: 1.1rem;
        font-weight: bold;
        letter-spacing: 1px;
        background: #f3f4f6;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        border: 1px dashed #d1d5db;
        display: inline-block;
    }
    
    .voucher-code-wrapper {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .copy-btn {
        background: #e5e7eb;
        border: none;
        padding: 0.25rem 0.75rem;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        cursor: pointer;
        transition: all 0.2s ease;
        color: #374151;
    }
    
    .copy-btn:hover {
        background: #d1d5db;
    }
    
    .copy-btn.copied {
        background: #10b981;
        color: white;
    }
    
    .no-rewards-state {
        text-align: center;
        padding: 3rem 1rem;
    }
    
    .no-rewards-state svg {
        margin: 0 auto 1rem;
    }
    
    .empty-state {
        text-align: center;
        padding: 2rem 1rem;
    }
    
    .status-claimed { background: #dcfce7; color: #166534; }
    .status-redeemed { background: #f3e8ff; color: #6b21a8; }
    .status-declined { background: #fee2e2; color: #991b1b; }
    .status-expired { background: #f3f4f6; color: #6b7280; }
    
    .redeemed-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1rem;
    }
    
    .redeemed-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 1rem;
        padding: 1.25rem;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .redeemed-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
        border-color: #8b5cf6;
    }
    
    .redeemed-card .voucher-code-display {
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        background: #f3f4f6;
        padding: 0.25rem 0.75rem;
        border-radius: 0.375rem;
        border: 1px solid #e5e7eb;
        display: inline-block;
    }

    .modal-status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.35rem 1rem;
        border-radius: 9999px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .modal-status-badge.claimed { background: #dcfce7; color: #166534; }
    .modal-status-badge.redeemed { background: #f3e8ff; color: #6b21a8; }
    .modal-status-badge.expired { background: #fee2e2; color: #991b1b; }
    .modal-status-badge.pending { background: #fef3c7; color: #92400e; }
    .modal-status-badge.declined { background: #fee2e2; color: #991b1b; }
    
    .modal-redeemed-info {
        background: #faf5ff;
        border: 1px solid #e9d5ff;
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
    }
    
    .modal-redeemed-info .label {
        color: #6b21a8;
        font-weight: 500;
    }
    
    .modal-divider {
        border-top: 1px solid #e5e7eb;
        margin: 0.5rem 0;
    }
    
    .monetary-value-display {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.8rem;
        color: #4b5563;
        background: #f3f4f6;
        padding: 0.15rem 0.6rem;
        border-radius: 9999px;
    }
    
    .monetary-value-display .label {
        color: #6b7280;
        font-weight: 400;
    }
    
    .monetary-value-display .value {
        font-weight: 600;
        color: #7F5539;
    }
    
    .discount-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.2rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .discount-badge.percentage {
        background: #ede9fe;
        color: #5b21b6;
    }
    
    .discount-badge .monetary {
        background: rgba(139, 92, 246, 0.15);
        padding: 0.05rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        color: #5b21b6;
    }

    .section-divider {
        position: relative;
        text-align: center;
        margin: 2rem 0 1.5rem;
    }
    
    .section-divider::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, #d1d5db, transparent);
    }
    
    .section-divider span {
        background: white;
        padding: 0 1rem;
        position: relative;
        z-index: 1;
        color: #6b7280;
        font-size: 0.85rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .reward-status-badge {
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
        padding: 0.15rem 0.6rem;
        border-radius: 9999px;
        font-size: 0.6rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .reward-status-badge.claimed {
        background: #dcfce7;
        color: #166534;
    }
    
    .reward-status-badge.redeemed {
        background: #f3e8ff;
        color: #6b21a8;
    }

    /* Tab Navigation */
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
</style>

<!-- Header -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">My Reward Tracker</h1>
            <p class="text-gray-600 mt-1">Track your progress and claim exciting rewards</p>
        </div>
        <div class="text-sm text-gray-500">
            <span class="font-medium text-[#7F5539]">{{ $customer->first_name ?? 'Customer' }}</span>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="tab-nav">
        <a href="{{ route('sub_three.my_rewards.showMyRewards') }}" class="active">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v1m0-1v-1m0 1v1m0-1v1m0-1v1m0-1v1m0-1v1m0-1v1m0-1v1m0-1v1m0-1v1m0-1v1"/>
            </svg>
            My Rewards
            <span class="badge">{{ $stats['total_earned_rewards'] ?? 0 }}</span>
        </a>
        <a href="{{ route('sub_three.my_rewards.redemptionHistory') }}">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            Redemption History
            <span class="badge">{{ $stats['redemption_history_count'] ?? 0 }}</span>
        </a>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Bookings</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $stats['total_bookings'] ?? 0 }}</p>
                </div>
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Current Streak</p>
                    <p class="text-2xl font-bold text-orange-600">{{ $stats['current_streak'] ?? 0 }}</p>
                </div>
                <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Claimed Rewards</p>
                    <p class="text-2xl font-bold text-green-600">{{ $stats['claimed_rewards'] ?? 0 }}</p>
                </div>
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Redeemed Rewards</p>
                    <p class="text-2xl font-bold text-purple-600">{{ $stats['redeemed_rewards'] ?? 0 }}</p>
                </div>
                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Completion Rate</p>
                    <p class="text-2xl font-bold text-indigo-600">{{ $stats['completion_rate'] ?? 0 }}%</p>
                </div>
                <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Steps Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Your Reward Journey</h2>
        <p class="text-sm text-gray-600 mb-6">Click on any milestone to see available rewards</p>
        
        @if($loyaltyTiers && $loyaltyTiers->isNotEmpty())
            <div class="progress-steps-wrapper" id="progressStepsWrapper">
                <div class="progress-steps" id="progressSteps">
                    <!-- Steps will be dynamically generated by JavaScript -->
                </div>
                <div class="progress-bar-line">
                    <div class="progress-bar-fill" id="progressBarFill"></div>
                </div>
            </div>
            
            <div class="mt-8 text-center" id="progressMessageContainer">
                <!-- Progress message will be dynamically generated -->
            </div>
        @else
            <div class="empty-state py-8">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-gray-600">No loyalty tiers available yet.</p>
                <p class="text-sm text-gray-400 mt-1">Complete more bookings to unlock rewards!</p>
            </div>
        @endif
    </div>

    <!-- Rewards Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 class="text-lg font-semibold text-gray-900" id="rewardsTitle">
                    @if($loyaltyTiers && $loyaltyTiers->isNotEmpty())
                        Available Rewards
                    @else
                        No Rewards Available
                    @endif
                </h2>
                <p class="text-sm text-gray-500" id="rewardsSubtitle">
                    @if($loyaltyTiers && $loyaltyTiers->isNotEmpty())
                        Click on a milestone above to see rewards
                    @else
                        Start completing bookings to earn rewards!
                    @endif
                </p>
            </div>
            <button id="clearSelectionBtn" class="text-sm text-[#7F5539] hover:text-[#4A2C1D] font-medium hidden">
                Clear Selection
            </button>
        </div>
        
        <div class="rewards-grid" id="rewardsGrid">
            @if($loyaltyTiers && $loyaltyTiers->isNotEmpty())
                <!-- Rewards will be dynamically generated -->
            @else
                <div class="no-rewards-state">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    <h5 class="text-lg font-medium text-gray-900 mb-2">No rewards available</h5>
                    <p class="text-gray-600">Complete more bookings to earn rewards!</p>
                    <p class="text-sm text-gray-400 mt-2">You have completed {{ $totalCompletedBookings ?? 0 }} booking(s) so far.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Reward Details Modal -->
<div id="rewardModal" x-data="rewardModalData()" x-show="showModal" x-cloak 
     class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
     @click.away="closeModal()">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-lg bg-white modal-content">
        <div class="flex justify-between items-center mb-4 border-b pb-3">
            <h3 class="text-lg font-semibold text-gray-900">Reward Details</h3>
            <button @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <template x-if="reward">
            <div class="space-y-4">
                <div>
                    <h4 class="font-medium text-gray-700">Reward</h4>
                    <p class="text-gray-900 font-semibold text-lg" x-text="reward.loyalty_tier?.reward_description || 'N/A'"></p>
                </div>

                <div>
                    <h4 class="font-medium text-gray-700">Status</h4>
                    <div class="mt-1">
                        <span class="modal-status-badge" :class="getStatusBadgeClass(reward)">
                            <span x-text="getStatusText(reward)"></span>
                        </span>
                        <span x-show="reward.redemption_status_label" 
                              class="modal-status-badge ml-2" 
                              :class="getRedemptionStatusBadgeClass(reward)">
                            <span x-text="reward.redemption_status_label"></span>
                        </span>
                    </div>
                </div>

                <div>
                    <h4 class="font-medium text-gray-700">Reward Type</h4>
                    <p>
                        <span class="reward-type-badge" :class="reward.reward_type_label || 'custom'">
                            <span x-text="reward.reward_type_label || 'N/A'"></span>
                        </span>
                    </p>
                </div>

                <div x-show="reward.target_details">
                    <h4 class="font-medium text-gray-700">Target</h4>
                    <div class="target-info">
                        <span class="target-tag" :class="reward.target_details.type">
                            <span x-text="reward.target_details.type"></span>
                        </span>
                        <span class="target-name" x-text="reward.target_details.name"></span>
                        <span class="target-category" x-show="reward.target_details.category">
                            <span x-text="'(' + reward.target_details.category + ')'"></span>
                        </span>
                    </div>
                </div>

                <div x-show="reward.value_display">
                    <h4 class="font-medium text-gray-700">Value</h4>
                    <div>
                        <span class="discount-badge" :class="reward.reward_type_label === 'Percentage Discount' ? 'percentage' : ''">
                            <span x-text="reward.value_display"></span>
                            <span x-show="reward.reward_type_label === 'Percentage Discount' && reward.monetary_value > 0" 
                                  class="monetary">
                                ₱<span x-text="parseFloat(reward.monetary_value).toFixed(2)"></span>
                            </span>
                        </span>
                    </div>
                </div>

                <div>
                    <h4 class="font-medium text-gray-700">Voucher Code</h4>
                    <div class="voucher-code-wrapper mt-1">
                        <span class="voucher-code" x-text="reward.voucher_code || 'N/A'"></span>
                        <button @click="copyVoucherCode()" class="copy-btn" :class="copied ? 'copied' : ''">
                            <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                        </button>
                    </div>
                </div>

                <div x-show="reward.monetary_value && reward.monetary_value > 0">
                    <h4 class="font-medium text-gray-700">Monetary Value</h4>
                    <p class="text-gray-900 font-medium">
                        ₱<span x-text="parseFloat(reward.monetary_value).toFixed(2)"></span>
                    </p>
                </div>

                <div x-show="reward.discount_percentage">
                    <h4 class="font-medium text-gray-700">Discount Percentage</h4>
                    <p class="text-gray-900 font-medium">
                        <span x-text="reward.discount_percentage + '% off'"></span>
                    </p>
                </div>

                <div>
                    <h4 class="font-medium text-gray-700">Claimed On</h4>
                    <p class="text-gray-900" x-text="formatDate(reward.date_created)"></p>
                </div>

                <div x-show="reward.redeemed_at" class="modal-redeemed-info">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="label font-semibold">✅ Redeemed Information</span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <span class="text-xs text-gray-500">Redeemed On</span>
                            <p class="text-gray-900 font-medium" x-text="formatDate(reward.redeemed_at)"></p>
                        </div>
                        <div x-show="reward.redeemed_at_branch">
                            <span class="text-xs text-gray-500">Redeemed At Branch</span>
                            <p class="text-gray-900 font-medium" x-text="reward.redeemed_at_branch?.branch_name || 'N/A'"></p>
                        </div>
                    </div>
                </div>

                <div x-show="reward.expiration_date">
                    <h4 class="font-medium text-gray-700">Expires On</h4>
                    <p class="text-gray-900" x-text="formatDate(reward.expiration_date)"></p>
                    <p x-show="reward.days_left !== 'N/A' && reward.days_left !== null" 
                       class="text-sm" :class="reward.days_left < 7 ? 'text-red-600' : 'text-gray-500'">
                        <span x-text="reward.days_left + ' days left'"></span>
                    </p>
                </div>

                <div>
                    <h4 class="font-medium text-gray-700">Branch</h4>
                    <p class="text-gray-900" x-text="reward.branch?.branch_name || 'All Branches'"></p>
                </div>
            </div>
        </template>

        <div class="mt-6 flex space-x-3">
            <button @click="closeModal()" class="flex-1 px-4 py-2 bg-[#7F5539] text-white rounded-lg hover:bg-[#4A2C1D] transition">
                Close
            </button>
        </div>
    </div>
</div>

<script>
// ============================================================
// STORE DATA FROM BACKEND
// ============================================================
let loyaltyTiersData = @json($loyaltyTiers ?? []);
let processedLoyaltyTiersData = @json($processedLoyaltyTiers ?? []);
let uniqueMilestones = @json($uniqueMilestones ?? []);
let totalBookingsData = @json($totalCompletedBookings ?? 0);
let maxRequiredData = @json($maxRequired ?? 0);
let statsData = @json($stats ?? []);
let claimedRewardsData = @json($claimedRewards ?? []);
let redeemedRewardsData = @json($redeemedRewards ?? []);
let loyaltyTiersForDisplay = processedLoyaltyTiersData;

let selectedMilestone = null;

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    } catch (e) {
        return 'Invalid date';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

const nextMilestone = uniqueMilestones.find(m => totalBookingsData < m);

function getStepClass(requiredBookings){
    if(totalBookingsData >= requiredBookings)
        return "completed";
    if(requiredBookings === nextMilestone)
        return "active";
    return "locked";
}

function isLoyaltyTierClaimable(tier) {
    const meetsBookingReq = totalBookingsData >= tier.reward_required;
    const notClaimed = !claimedRewardsData.some(r => r.loyalty_tier_id === tier.id);
    const notRedeemed = !redeemedRewardsData.some(r => r.loyalty_tier_id === tier.id);
    const isAvailable = tier.is_currently_claimable !== false;
    return meetsBookingReq && isAvailable && notClaimed && notRedeemed;
}

function isLoyaltyTierClaimed(tier) {
    return claimedRewardsData.some(r => r.loyalty_tier_id === tier.id);
}

function isLoyaltyTierRedeemed(tier) {
    return redeemedRewardsData.some(r => r.loyalty_tier_id === tier.id);
}

function isLoyaltyTierLocked(tier) {
    return totalBookingsData < tier.reward_required;
}

function getLoyaltyTierStatusText(tier) {
    if (isLoyaltyTierRedeemed(tier)) return 'Redeemed';
    if (isLoyaltyTierClaimed(tier)) return 'Claimed';
    if (isLoyaltyTierClaimable(tier)) return 'Available';
    return 'Locked';
}

function getLoyaltyTierBadgeClass(tier) {
    if (isLoyaltyTierRedeemed(tier)) return 'redeemed';
    if (isLoyaltyTierClaimed(tier)) return 'claimed';
    if (isLoyaltyTierClaimable(tier)) return 'available';
    return 'expired';
}

function getLoyaltyTierCardClass(tier) {
    if (isLoyaltyTierRedeemed(tier)) return 'reward-card redeemed';
    if (isLoyaltyTierClaimed(tier)) return 'reward-card claimed';
    return 'reward-card';
}

function getCustomerLoyaltyTierId(tier) {
    const claimed = claimedRewardsData.find(r => r.loyalty_tier_id === tier.id);
    if (claimed) return claimed.id;
    const redeemed = redeemedRewardsData.find(r => r.loyalty_tier_id === tier.id);
    if (redeemed) return redeemed.id;
    return null;
}

function getItemTypeLabel(type) {
    const labels = {
        free_service: 'Free Service',
        free_product: 'Free Product',
        fixed_discount: 'Fixed Discount',
        percentage_discount: 'Percentage Discount'
    };
    return labels[type] || 'Custom';
}

function getRewardStatus(reward) {
    if (reward.claim_status === 1) return 'claimed';
    if (reward.claim_status === 0) return 'declined';
    if (reward.claim_status === 3) return 'expired';
    if (reward.claim_status === 2) return 'pending';
    return 'unknown';
}

function getStatusBadgeClass(reward) {
    const status = getRewardStatus(reward);
    const classes = {
        claimed: 'claimed',
        declined: 'declined',
        expired: 'expired',
        pending: 'pending',
        unknown: 'expired'
    };
    return classes[status] || 'expired';
}

function getStatusText(reward) {
    const status = getRewardStatus(reward);
    const texts = {
        claimed: '✅ Claimed',
        declined: '❌ Declined',
        expired: '⏰ Expired',
        pending: '⏳ Pending Approval',
        unknown: 'Unknown'
    };
    return texts[status] || 'Unknown';
}

function getRedemptionStatusBadgeClass(reward) {
    const status = reward.redemption_status;
    const classes = {
        'pending': 'pending',
        'ready': 'claimed',
        'redeemed': 'redeemed',
        'cancelled': 'declined'
    };
    return classes[status] || 'expired';
}

function getRedemptionStatusText(reward) {
    const status = reward.redemption_status;
    const texts = {
        'pending': 'Pending',
        'ready': 'Ready for Redemption',
        'redeemed': '✅ Redeemed',
        'cancelled': 'Cancelled'
    };
    return texts[status] || 'Unknown';
}

function getValueDisplay(item) {
    if (!item) return 'N/A';
    if (item.reward_type === 'percentage_discount') {
        const percentage = item.discount_percentage + '% off';
        const monetary = item.monetary_value ? ' (₱' + parseFloat(item.monetary_value).toFixed(2) + ')' : '';
        return percentage + monetary;
    } else if (item.reward_type === 'fixed_discount') {
        return '₱' + parseFloat(item.monetary_value || 0).toFixed(2) + ' off';
    } else if (['free_service', 'free_product'].includes(item.reward_type)) {
        if (item.target_details && item.target_details.name) {
            return item.target_details.name;
        }
        return 'Free ' + (item.reward_type === 'free_service' ? 'Service' : 'Product');
    }
    return 'N/A';
}

function renderProgressSteps() {
    const progressContainer = document.getElementById('progressSteps');
    if (!progressContainer) return;
    
    if (!uniqueMilestones || uniqueMilestones.length === 0) {
        progressContainer.innerHTML = '<div class="text-center py-8 text-gray-500 w-full">No reward milestones available</div>';
        return;
    }
    
    let html = '';
    uniqueMilestones.forEach(requiredBookings => {
        const isSelected = selectedMilestone === requiredBookings;
        const isCompleted = totalBookingsData >= requiredBookings;
        const isActive = totalBookingsData >= requiredBookings - 1 && totalBookingsData < requiredBookings;
        
        html += `
            <div class="progress-step" onclick="selectMilestone(${requiredBookings})" data-required="${requiredBookings}">
                <div class="step-circle ${isCompleted ? 'completed' : isActive ? 'active' : 'locked'} ${isSelected ? 'selected-milestone' : ''}">
                    <span>${requiredBookings}</span>
                </div>
                <div class="step-label ${isCompleted ? 'completed' : isActive ? 'active' : 'locked'} ${isSelected ? 'selected' : ''}">
                    <span>${requiredBookings} Booking${requiredBookings !== 1 ? 's' : ''}</span>
                </div>
            </div>
        `;
    });
    progressContainer.innerHTML = html;
    
    const progressPercent = maxRequiredData > 0 ? (totalBookingsData / maxRequiredData * 100) : 0;
    const progressFill = document.getElementById('progressBarFill');
    if (progressFill) {
        progressFill.style.width = `${Math.min(progressPercent, 100)}%`;
    }
    
    const nextMilestone = uniqueMilestones.find(required => totalBookingsData < required);
    const messageContainer = document.getElementById('progressMessageContainer');
    if (!messageContainer) return;
    
    let messageHtml = '';
    if (nextMilestone) {
        const remaining = nextMilestone - totalBookingsData;
        messageHtml = `
            <p class="text-sm text-gray-600">
                You have completed <span class="font-bold text-[#7F5539]">${totalBookingsData}</span> out of 
                <span class="font-bold">${nextMilestone}</span> bookings needed for your next reward
            </p>
            <div class="mt-2 w-full bg-gray-200 rounded-full h-2 max-w-md mx-auto">
                <div class="bg-[#7F5539] h-2 rounded-full transition-all duration-500" style="width: ${Math.min((totalBookingsData / nextMilestone) * 100, 100)}%"></div>
            </div>
            <p class="text-xs text-gray-500 mt-1">${remaining} more booking${remaining !== 1 ? 's' : ''} to go!</p>
        `;
    } else if (totalBookingsData > 0) {
        messageHtml = `
            <p class="text-sm text-gray-600">
                🎉 You have completed <span class="font-bold text-[#7F5539]">${totalBookingsData}</span> bookings!
                You've reached the highest reward milestone.
            </p>
        `;
    } else {
        messageHtml = `
            <p class="text-sm text-gray-600">
                Start completing bookings to unlock rewards!
            </p>
        `;
    }
    messageContainer.innerHTML = messageHtml;
}

function renderRewards() {
    const rewardsGrid = document.getElementById('rewardsGrid');
    if (!rewardsGrid) return;
    
    let availableTiers = [];
    let claimedTiers = [];
    let redeemedTiers = [];
    
    if (selectedMilestone) {
        const filtered = loyaltyTiersForDisplay.filter(tier => tier.reward_required === selectedMilestone);
        filtered.forEach(tier => {
            if (isLoyaltyTierRedeemed(tier)) {
                redeemedTiers.push(tier);
            } else if (isLoyaltyTierClaimed(tier)) {
                claimedTiers.push(tier);
            } else {
                availableTiers.push(tier);
            }
        });
    } else {
        loyaltyTiersForDisplay.forEach(tier => {
            if (isLoyaltyTierRedeemed(tier)) {
                redeemedTiers.push(tier);
            } else if (isLoyaltyTierClaimed(tier)) {
                claimedTiers.push(tier);
            } else {
                availableTiers.push(tier);
            }
        });
    }
    
    if (selectedMilestone) {
        document.getElementById('rewardsTitle').innerHTML = `Rewards for ${selectedMilestone} Booking${selectedMilestone !== 1 ? 's' : ''}`;
        const totalCount = availableTiers.length + claimedTiers.length + redeemedTiers.length;
        document.getElementById('rewardsSubtitle').innerHTML = `${totalCount} reward${totalCount !== 1 ? 's' : ''} total`;
        const clearBtn = document.getElementById('clearSelectionBtn');
        if (clearBtn) clearBtn.classList.remove('hidden');
    } else {
        document.getElementById('rewardsTitle').innerHTML = 'All Available Rewards';
        const totalCount = availableTiers.length + claimedTiers.length + redeemedTiers.length;
        document.getElementById('rewardsSubtitle').innerHTML = `${totalCount} reward${totalCount !== 1 ? 's' : ''} total`;
        const clearBtn = document.getElementById('clearSelectionBtn');
        if (clearBtn) clearBtn.classList.add('hidden');
    }
    
    if (availableTiers.length === 0 && claimedTiers.length === 0 && redeemedTiers.length === 0) {
        rewardsGrid.innerHTML = `
            <div class="col-span-full text-center py-12">
                <div class="text-gray-400">
                    <svg class="mx-auto h-16 w-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    <h5 class="text-lg font-medium text-gray-900 mb-2">${selectedMilestone ? 'No rewards available for this milestone yet.' : 'No rewards available at the moment.'}</h5>
                    <p class="text-gray-600">${selectedMilestone ? 'Try selecting a different milestone.' : 'Complete more bookings to unlock rewards!'}</p>
                </div>
            </div>
        `;
        return;
    }
    
    let html = '';
    
    if (availableTiers.length > 0) {
        html += `
            <div class="section-divider">
                <span>🟢 Available to Claim (${availableTiers.length})</span>
            </div>
        `;
        availableTiers.forEach(tier => {
            html += renderRewardCard(tier, 'available');
        });
    }
    
    if (claimedTiers.length > 0) {
        html += `
            <div class="section-divider">
                <span>🟡 Claimed Rewards (${claimedTiers.length})</span>
            </div>
        `;
        claimedTiers.forEach(tier => {
            html += renderRewardCard(tier, 'claimed');
        });
    }
    
    if (redeemedTiers.length > 0) {
        html += `
            <div class="section-divider">
                <span>🟣 Redeemed Rewards (${redeemedTiers.length})</span>
            </div>
        `;
        redeemedTiers.forEach(tier => {
            html += renderRewardCard(tier, 'redeemed');
        });
    }
    
    rewardsGrid.innerHTML = html;
}

function renderRewardCard(tier, status) {
    const progressPercent = Math.min(totalBookingsData, tier.reward_required) / tier.reward_required * 100;
    const isClaimable = status === 'available';
    const isClaimed = status === 'claimed';
    const isRedeemed = status === 'redeemed';
    const hasDateTimeRestrictions = tier.date_start || tier.date_end || tier.start_time || tier.end_time;
    const hasRedeemableItem = tier.redeemable_item_id && tier.redemption_item_name;
    
    const rewardTypeClass = tier.reward_type_label ? tier.reward_type_label.toLowerCase().replace(' ', '_') : 'custom';
    
    let discountDisplay = '';
    if (tier.reward_type_label === 'Percentage Discount' && tier.discount_percentage) {
        const monetaryValue = tier.monetary_value_original || tier.monetary_value || 0;
        if (monetaryValue > 0) {
            discountDisplay = tier.discount_percentage + '% off (₱' + parseFloat(monetaryValue).toFixed(2) + ')';
        } else {
            discountDisplay = tier.discount_percentage + '% off';
        }
    } else if (tier.monetary_value) {
        discountDisplay = '₱' + parseFloat(tier.monetary_value).toFixed(2);
    }
    
    let statusBadgeClass = 'available';
    let statusText = 'Available';
    if (isRedeemed) {
        statusBadgeClass = 'redeemed';
        statusText = 'Redeemed';
    } else if (isClaimed) {
        statusBadgeClass = 'claimed';
        statusText = 'Claimed';
    }
    
    let targetDisplay = '';
    if (tier.target_details) {
        targetDisplay = `
            <div class="target-info">
                <span class="target-tag ${tier.target_details.type}">${tier.target_details.type}</span>
                <span class="target-name">${escapeHtml(tier.target_details.name)}</span>
                ${tier.target_details.category ? `<span class="target-category">(${escapeHtml(tier.target_details.category)})</span>` : ''}
            </div>
        `;
    }
    
    const statusBadgeHtml = `
        <span class="reward-status-badge ${statusBadgeClass}">
            ${statusText}
        </span>
    `;
    
    return `
        <div class="${getLoyaltyTierCardClass(tier)}" style="position: relative;">
            ${statusBadgeHtml}
            <div class="reward-card-info">
                <div class="flex justify-between items-start flex-wrap gap-2 mb-2">
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900">${escapeHtml(tier.reward_description)}</h3>
                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                            <span class="reward-type-badge ${rewardTypeClass}">
                                ${tier.reward_type_label || 'Custom'}
                            </span>
                            ${discountDisplay ? `<span class="text-sm font-medium text-green-600">${discountDisplay}</span>` : ''}
                        </div>
                    </div>
                </div>
                
                ${targetDisplay}
                
                ${hasRedeemableItem ? `
                    <div class="flex items-center text-sm text-gray-600 mb-1">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span>${escapeHtml(tier.redemption_item_name)}</span>
                        ${tier.redemption_value ? `<span class="ml-2 font-medium text-gray-700">(${tier.redemption_value})</span>` : ''}
                    </div>
                ` : `
                    <div class="flex items-center text-sm text-gray-400 mb-1">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Custom reward</span>
                    </div>
                `}
                
                ${tier.reward_type_label === 'Percentage Discount' && tier.monetary_value_original > 0 ? `
                    <div class="flex items-center text-sm text-gray-600 mb-1">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v1m0-1v-1m0 1v1m0-1v1m0-1v1m0-1v1m0-1v1m0-1v1m0-1v1m0-1v1m0-1v1m0-1v1"/>
                        </svg>
                        <span>Monetary Value: <strong>₱${parseFloat(tier.monetary_value_original).toFixed(2)}</strong></span>
                    </div>
                ` : ''}
                
                <div class="flex items-center text-sm text-gray-600 mb-2">
                    <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span>${tier.branch ? tier.branch.branch_name : 'All Branches'}</span>
                </div>
                
                ${hasDateTimeRestrictions && tier.availability_message && tier.availability_message !== 'Always Available' ? `
                    <div class="availability-info">
                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>${tier.availability_message}</span>
                    </div>
                ` : ''}
                
                <div class="mt-3">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Your Progress</span>
                        <span class="font-medium">${Math.min(totalBookingsData, tier.reward_required)}/${tier.reward_required}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-[#7F5539] h-2 rounded-full transition-all duration-500" style="width: ${Math.min(progressPercent, 100)}%"></div>
                    </div>
                </div>
            </div>
            
            <div class="reward-card-action">
                ${isClaimable ? `
                    <button onclick="claimLoyaltyTier(${tier.id})" class="claim-btn">
                        <span class="flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Claim Reward
                        </span>
                    </button>
                ` : isRedeemed ? `
                    <button onclick="viewRewardDetails(${getCustomerLoyaltyTierId(tier)})" class="redeemed-btn">
                        <span class="flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            View Voucher
                        </span>
                    </button>
                ` : isClaimed ? `
                    <button onclick="viewRewardDetails(${getCustomerLoyaltyTierId(tier)})" class="claimed-btn">
                        <span class="flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            View Voucher
                        </span>
                    </button>
                ` : `
                    <div class="text-center">
                        <button disabled class="px-4 py-2 bg-gray-300 text-gray-500 rounded-lg cursor-not-allowed text-sm font-medium">
                            <span class="flex items-center justify-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                Locked
                            </span>
                        </button>
                        <p class="text-xs text-gray-500 text-center mt-2">
                            Need ${tier.reward_required - totalBookingsData} more booking${tier.reward_required - totalBookingsData !== 1 ? 's' : ''}
                        </p>
                    </div>
                `}
            </div>
        </div>
    `;
}

function selectMilestone(requiredBookings) {
    if (selectedMilestone === requiredBookings) {
        selectedMilestone = null;
    } else {
        selectedMilestone = requiredBookings;
    }
    renderProgressSteps();
    renderRewards();
}

function clearSelectedMilestone() {
    selectedMilestone = null;
    renderProgressSteps();
    renderRewards();
}

async function claimLoyaltyTier(tierId) {
    if (!confirm('Are you sure you want to claim this reward?')) {
        return;
    }
    
    try {
        const response = await fetch('{{ route("sub_three.my_rewards.claim") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ loyalty_tier_id: tierId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('✅ ' + data.message);
            window.location.reload();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        alert('❌ ' + error.message);
    }
}

function viewRewardDetails(rewardId) {
    if (!rewardId) return;
    
    const url = `{{ route("sub_three.my_rewards.getRewardDetails", "") }}/${rewardId}`;
    
    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showRewardDetailsModal(data.data);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to load reward details');
    });
}

function rewardModalData() {
    return {
        showModal: false,
        reward: null,
        copied: false,
        
        openModal(reward) {
            this.reward = reward;
            this.showModal = true;
            this.copied = false;
            document.body.style.overflow = 'hidden';
            document.body.classList.add('modal-open');
        },
        
        closeModal() {
            this.showModal = false;
            this.reward = null;
            document.body.style.overflow = 'auto';
            document.body.classList.remove('modal-open');
        },
        
        copyVoucherCode() {
            if (!this.reward || !this.reward.voucher_code) return;
            
            navigator.clipboard.writeText(this.reward.voucher_code).then(() => {
                this.copied = true;
                setTimeout(() => {
                    this.copied = false;
                }, 3000);
            }).catch(() => {
                const input = document.createElement('input');
                input.value = this.reward.voucher_code;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                this.copied = true;
                setTimeout(() => {
                    this.copied = false;
                }, 3000);
            });
        },
        
        getStatusBadgeClass(reward) {
            return getStatusBadgeClass(reward);
        },
        
        getStatusText(reward) {
            return getStatusText(reward);
        },
        
        getRedemptionStatusBadgeClass(reward) {
            return getRedemptionStatusBadgeClass(reward);
        },
        
        getRedemptionStatusText(reward) {
            return getRedemptionStatusText(reward);
        },
        
        formatDate(dateString) {
            return formatDate(dateString);
        }
    };
}

document.addEventListener('alpine:init', () => {
    Alpine.data('rewardModalData', rewardModalData);
});

function showRewardDetailsModal(reward) {
    const modalComponent = document.querySelector('[x-data="rewardModalData()"]');
    if (modalComponent && modalComponent._x_dataStack) {
        const data = modalComponent._x_dataStack[0];
        if (data && data.openModal) {
            data.openModal(reward);
            return;
        }
    }
    
    const isRedeemed = reward.redemption_status === 'redeemed';
    const isPercentageDiscount = reward.reward_type_label === 'Percentage Discount';
    const monetaryValue = reward.monetary_value || 0;
    const discountPercentage = reward.discount_percentage || 0;
    
    const redeemedInfoHtml = isRedeemed ? `
        <div class="modal-redeemed-info">
            <div class="flex items-center gap-2 mb-2">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="label font-semibold">✅ Redeemed Information</span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <span class="text-xs text-gray-500">Redeemed On</span>
                    <p class="text-gray-900 font-medium">${formatDateTime(reward.redeemed_at)}</p>
                </div>
                ${reward.redeemed_at_branch ? `
                    <div>
                        <span class="text-xs text-gray-500">Redeemed At Branch</span>
                        <p class="text-gray-900 font-medium">${reward.redeemed_at_branch.branch_name || 'N/A'}</p>
                    </div>
                ` : ''}
            </div>
        </div>
    ` : '';
    
    const monetaryValueHtml = isPercentageDiscount && monetaryValue > 0 ? `
        <div>
            <h4 class="font-medium text-gray-700">Monetary Value</h4>
            <p class="text-gray-900 font-medium">₱${parseFloat(monetaryValue).toFixed(2)}</p>
        </div>
    ` : '';
    
    const discountPercentageHtml = isPercentageDiscount && discountPercentage > 0 ? `
        <div>
            <h4 class="font-medium text-gray-700">Discount Percentage</h4>
            <p class="text-gray-900 font-medium">${discountPercentage}% off</p>
        </div>
    ` : '';
    
    const modalHtml = `
        <div id="rewardModalFallback" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-lg bg-white">
                <div class="flex justify-between items-center mb-4 border-b pb-3">
                    <h3 class="text-lg font-semibold text-gray-900">Reward Details</h3>
                    <button onclick="document.getElementById('rewardModalFallback').remove()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <h4 class="font-medium text-gray-700">Reward</h4>
                        <p class="text-gray-900 font-semibold text-lg">${escapeHtml(reward.loyalty_tier?.reward_description || 'N/A')}</p>
                    </div>
                    
                    <div>
                        <h4 class="font-medium text-gray-700">Status</h4>
                        <div class="mt-1">
                            <span class="modal-status-badge ${getStatusBadgeClass(reward)}">${getStatusText(reward)}</span>
                            ${reward.redemption_status_label ? `<span class="modal-status-badge ml-2 ${getRedemptionStatusBadgeClass(reward)}">${getRedemptionStatusText(reward)}</span>` : ''}
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="font-medium text-gray-700">Reward Type</h4>
                        <p>
                            <span class="reward-type-badge ${reward.reward_type_label || 'custom'}">${reward.reward_type_label || 'N/A'}</span>
                        </p>
                    </div>
                    
                    ${reward.target_details ? `
                        <div>
                            <h4 class="font-medium text-gray-700">Target</h4>
                            <div class="target-info">
                                <span class="target-tag ${reward.target_details.type}">${reward.target_details.type}</span>
                                <span class="target-name">${escapeHtml(reward.target_details.name)}</span>
                                ${reward.target_details.category ? `<span class="target-category">(${escapeHtml(reward.target_details.category)})</span>` : ''}
                            </div>
                        </div>
                    ` : ''}
                    
                    ${reward.value_display ? `
                        <div>
                            <h4 class="font-medium text-gray-700">Value</h4>
                            <p class="text-gray-900 font-semibold">${reward.value_display}</p>
                        </div>
                    ` : ''}
                    
                    ${monetaryValueHtml}
                    ${discountPercentageHtml}
                    
                    <div>
                        <h4 class="font-medium text-gray-700">Voucher Code</h4>
                        <div class="voucher-code-wrapper mt-1">
                            <span class="voucher-code">${reward.voucher_code || 'N/A'}</span>
                            <button onclick="copyVoucherCodeFallback('${reward.voucher_code}')" class="copy-btn" id="copyBtnFallback">
                                Copy
                            </button>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="font-medium text-gray-700">Claimed On</h4>
                        <p class="text-gray-900">${formatDate(reward.date_created)}</p>
                    </div>
                    
                    ${redeemedInfoHtml}
                    
                    ${reward.expiration_date ? `
                        <div>
                            <h4 class="font-medium text-gray-700">Expires On</h4>
                            <p class="text-gray-900">${formatDate(reward.expiration_date)}</p>
                            ${reward.days_left !== 'N/A' && reward.days_left !== null ? `<p class="text-sm ${reward.days_left < 7 ? 'text-red-600' : 'text-gray-500'}">${reward.days_left} days left</p>` : ''}
                        </div>
                    ` : ''}
                    
                    <div>
                        <h4 class="font-medium text-gray-700">Branch</h4>
                        <p class="text-gray-900">${reward.branch?.branch_name || 'All Branches'}</p>
                    </div>
                </div>
                <div class="mt-6">
                    <button onclick="document.getElementById('rewardModalFallback').remove()" class="w-full px-4 py-2 bg-[#7F5539] text-white rounded-lg hover:bg-[#4A2C1D] transition">
                        Close
                    </button>
                </div>
            </div>
        </div>
    `;
    
    const existing = document.getElementById('rewardModalFallback');
    if (existing) existing.remove();
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    document.body.style.overflow = 'hidden';
}

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return 'Invalid date';
    }
}

function copyVoucherCodeFallback(code) {
    if (!code) return;
    
    const input = document.createElement('input');
    input.value = code;
    document.body.appendChild(input);
    input.select();
    document.execCommand('copy');
    document.body.removeChild(input);
    
    const btn = document.getElementById('copyBtnFallback');
    if (btn) {
        btn.textContent = 'Copied!';
        btn.classList.add('copied');
        setTimeout(() => {
            btn.textContent = 'Copy';
            btn.classList.remove('copied');
        }, 3000);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const hasTiers = loyaltyTiersForDisplay && loyaltyTiersForDisplay.length > 0;
    
    if (hasTiers) {
        renderProgressSteps();
        renderRewards();
    } else {
        const rewardsGrid = document.getElementById('rewardsGrid');
        if (rewardsGrid) {
            rewardsGrid.innerHTML = `
                <div class="no-rewards-state">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    <h5 class="text-lg font-medium text-gray-900 mb-2">No rewards available</h5>
                    <p class="text-gray-600">Complete more bookings to earn rewards!</p>
                    <p class="text-sm text-gray-400 mt-2">You have completed ${totalBookingsData || 0} booking(s) so far.</p>
                </div>
            `;
        }
        
        const progressContainer = document.getElementById('progressSteps');
        if (progressContainer) {
            progressContainer.innerHTML = '<div class="text-center py-8 text-gray-500 w-full">No reward milestones available</div>';
        }
    }
    
    const clearBtn = document.getElementById('clearSelectionBtn');
    if (clearBtn) {
        clearBtn.addEventListener('click', clearSelectedMilestone);
    }
});
</script>
@endsection