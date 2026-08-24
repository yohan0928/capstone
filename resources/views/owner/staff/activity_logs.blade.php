@extends('layouts.app')

@section('title', 'Staff Activity Logs')

@section('content')
    <div x-data="activityLogsPage()" x-init="init()" class="p-4">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 md:mb-8 gap-4">
            <h1 class="text-2xl font-bold text-gray-900">Staff Activity Logs</h1>
            
            <!-- Mobile View: Two columns for buttons -->
            <div class="w-full md:w-auto">
                <div class="grid grid-cols-2 gap-3 md:hidden">
                    <button @click="exportLogs()" 
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Export CSV
                    </button>
                    <button @click="showFilters = true" 
                            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                        </svg>
                        Filters
                    </button>
                </div>
                
                <!-- Desktop View: Original layout -->
                <div class="hidden md:flex gap-3">
                    <button @click="exportLogs()" 
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Export CSV
                    </button>
                    <button @click="showFilters = true" 
                            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                        </svg>
                        Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow-sm border p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Actions</p>
                        <p class="text-2xl font-bold" x-text="stats.total_actions">0</p>
                    </div>
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm border p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Today's Actions</p>
                        <p class="text-2xl font-bold" x-text="stats.today_actions">0</p>
                    </div>
                    <div class="p-2 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm border p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Yesterday's Actions</p>
                        <p class="text-2xl font-bold" x-text="stats.yesterday_actions">0</p>
                    </div>
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm border p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">This Week</p>
                        <p class="text-2xl font-bold" x-text="stats.this_week_actions">0</p>
                    </div>
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow-sm border">
            <!-- Table Header -->
            <div class="px-4 md:px-6 py-4 border-b">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <h2 class="text-lg font-semibold">Activity Records</h2>
                    <div class="relative w-full md:w-64">
                        <input type="text" x-model="searchQuery" @input.debounce.500ms="performSearch()"
                               placeholder="Search logs..."
                               class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <div class="absolute left-3 top-2.5">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                            <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Staff</th>
                            <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Branch</th>
                            <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="log in logs" :key="log.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" x-text="formatDateTime(log.created_at)"></div>
                                    <div class="text-xs text-gray-500" x-text="timeAgo(log.created_at)"></div>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="ml-0 md:ml-4">
                                            <div class="text-sm font-medium text-gray-900"
                                                 x-text="log.staff ? log.staff.first_name + ' ' + log.staff.last_name : 'N/A'"></div>
                                            <div class="text-xs text-gray-500"
                                                 x-text="log.staff?.email || ''"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" x-text="log.branch?.branch_name || 'N/A'"></div>
                                </td>
                                <td class="px-4 md:px-6 py-4">
                                    <div class="text-sm text-gray-900" x-text="log.description"></div>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full"
                                          :class="getActionClass(log.action_type)">
                                        <span x-text="getActionLabel(log.action_type)"></span>
                                    </span>
                                </td>
                            </tr>
                        </template>
                        
                        <tr x-show="!logs.length">
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <h5 class="text-sm font-medium text-gray-900">No activity logs found</h5>
                                    <p class="text-sm text-gray-500">When staff perform actions, they will appear here</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div x-show="pagination.last_page > 1" class="px-4 md:px-6 py-4 border-t">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="text-sm text-gray-700">
                        Showing <span x-text="pagination.from"></span> to <span x-text="pagination.to"></span>
                        of <span x-text="pagination.total"></span> entries
                    </div>
                    <div class="flex gap-2 flex-wrap justify-center">
                        <button @click="changePage(pagination.current_page - 1)"
                                :disabled="pagination.current_page === 1"
                                class="px-3 py-1 border rounded disabled:opacity-50 text-sm">Previous</button>
                        
                        <template x-for="page in paginationLinks" :key="page">
                            <button @click="changePage(page)"
                                    :class="page === pagination.current_page ? 'bg-blue-600 text-white' : 'border'"
                                    class="px-3 py-1 rounded text-sm"
                                    x-text="page"></button>
                        </template>
                        
                        <button @click="changePage(pagination.current_page + 1)"
                                :disabled="pagination.current_page === pagination.last_page"
                                class="px-3 py-1 border rounded disabled:opacity-50 text-sm">Next</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Modal -->
        <div x-show="showFilters" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg w-full max-w-md">
                <div class="p-6 border-b flex justify-between items-center">
                    <h3 class="text-lg font-semibold">Filter Logs</h3>
                    <button @click="showFilters = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Branch</label>
                        <select x-model="filters.uuid" class="w-full border rounded-lg p-2">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->uuid }}">{{ $branch->branch_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Date From</label>
                            <input type="date" x-model="filters.date_from" class="w-full border rounded-lg p-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Date To</label>
                            <input type="date" x-model="filters.date_to" class="w-full border rounded-lg p-2">
                        </div>
                    </div>
                </div>
                <div class="p-6 border-t flex justify-end gap-3">
                    <button @click="clearFilters()" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Clear</button>
                    <button @click="applyFilters()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Apply Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Log Details Modal -->
        <div x-show="showDetailsModal" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg w-full max-w-2xl">
                <div class="p-6 border-b flex justify-between items-center">
                    <h3 class="text-lg font-semibold">Activity Details</h3>
                    <button @click="showDetailsModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-6" x-html="logDetailsContent"></div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('activityLogsPage', () => ({
                logs: @json($logs->items()),
                pagination: @json($logs->toArray()),
                stats: @json($stats),
                actionsByType: @json($actionsByType),
                actionTypes: @json($actionTypes),
                
                filters: {
                    uuid: '{{ request('uuid', '') }}',
                    date_from: '{{ request('date_from', '') }}',
                    date_to: '{{ request('date_to', '') }}',
                    search: '{{ request('search', '') }}'
                },
                
                searchQuery: '{{ request('search', '') }}',
                showFilters: false,
                showDetailsModal: false,
                logDetailsContent: '',
                isLoading: false,
                paginationLinks: [],
                
                init() {
                    this.updatePaginationLinks();
                },
                
                async performSearch() {
                    this.filters.search = this.searchQuery;
                    await this.applyFilters();
                },
                
                async applyFilters() {
                    this.isLoading = true;
                    this.showFilters = false;
                    
                    try {
                        const queryParams = new URLSearchParams();
                        Object.entries(this.filters).forEach(([key, value]) => {
                            if (value) queryParams.append(key, value);
                        });
                        
                        queryParams.append('ajax', 'true');
                        
                        const response = await fetch(`?${queryParams.toString()}`, {
                            headers: { 
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        
                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.includes('application/json')) {
                            const text = await response.text();
                            console.error('Non-JSON response:', text.substring(0, 500));
                            throw new Error(`Expected JSON but got: ${contentType}`);
                        }
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            this.logs = data.data;
                            this.pagination = data.pagination;
                            this.stats = data.stats;
                            this.actionsByType = data.actions_by_type;
                            this.updatePaginationLinks();
                            this.updateURL();
                        } else {
                            throw new Error(data.message || 'Request failed');
                        }
                    } catch (error) {
                        console.error('Error applying filters:', error);
                        alert('Error applying filters: ' + error.message);
                    } finally {
                        this.isLoading = false;
                    }
                },
                
                clearFilters() {
                    this.filters = {
                        uuid: '',
                        date_from: '',
                        date_to: '',
                        search: ''
                    };
                    this.searchQuery = '';
                    this.applyFilters();
                },
                
                async changePage(page) {
                    if (page < 1 || page > this.pagination.last_page) return;
                    
                    this.filters.page = page;
                    await this.applyFilters();
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
                        if (prev && i - prev !== 1) {
                            rangeWithDots.push('...');
                        }
                        rangeWithDots.push(i);
                        prev = i;
                    }
                    
                    this.paginationLinks = rangeWithDots;
                },
                
                updateURL() {
                    const queryParams = new URLSearchParams();
                    Object.entries(this.filters).forEach(([key, value]) => {
                        if (value) queryParams.append(key, value);
                    });
                    
                    history.pushState({}, '', `?${queryParams.toString()}`);
                },
                
                async viewLogDetails(logId) {
                    try {
                        const url = `/sub_one/staff-activity-logs/details/${logId}`;
                        
                        const response = await fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            const log = data.log;
                            this.logDetailsContent = `
                                <div class="space-y-6">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-sm text-gray-500">Action Type</p>
                                            <p class="font-medium">${log.action_label}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Date & Time</p>
                                            <p class="font-medium">${log.created_at}</p>
                                            <p class="text-sm text-gray-500">${log.created_at_relative}</p>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Description</p>
                                        <p class="font-medium">${log.description}</p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-sm text-gray-500">Staff</p>
                                            <p class="font-medium">${log.staff ? log.staff.name : 'N/A'}</p>
                                            ${log.staff ? `<p class="text-sm text-gray-500">${log.staff.email}</p>` : ''}
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Branch</p>
                                            <p class="font-medium">${log.branch ? log.branch.name : 'N/A'}</p>
                                        </div>
                                    </div>
                                    ${log.booking ? `
                                    <div>
                                        <p class="text-sm text-gray-500">Booking Reference</p>
                                        <p class="font-medium">${log.booking.ref_no}</p>
                                        ${log.booking.customer ? `
                                        <p class="text-sm text-gray-500 mt-2">Customer</p>
                                        <p class="font-medium">${log.booking.customer.name}</p>
                                        <p class="text-sm text-gray-500">${log.booking.customer.email}</p>
                                        ` : ''}
                                    </div>
                                    ` : ''}
                                    ${log.metadata ? `
                                    <div>
                                        <p class="text-sm text-gray-500">Additional Information</p>
                                        <div class="bg-gray-50 p-3 rounded">
                                            <pre class="text-sm whitespace-pre-wrap">${log.metadata}</pre>
                                        </div>
                                    </div>
                                    ` : ''}
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-sm text-gray-500">IP Address</p>
                                            <p class="font-medium">${log.ip_address || 'N/A'}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">User Agent</p>
                                            <p class="font-medium text-sm truncate">${log.user_agent || 'N/A'}</p>
                                        </div>
                                    </div>
                                </div>
                            `;
                            this.showDetailsModal = true;
                        } else {
                            throw new Error(data.error || 'Failed to load log details');
                        }
                    } catch (error) {
                        console.error('Error fetching log details:', error);
                        alert('Failed to load log details: ' + error.message);
                    }
                },
                
                exportLogs() {
                    const queryParams = new URLSearchParams();
                    Object.entries(this.filters).forEach(([key, value]) => {
                        if (value && key !== 'search') queryParams.append(key, value);
                    });
                    
                    window.location.href = `/sub_one/staff-activity-logs/export?${queryParams.toString()}`;
                },
                
                // Helper methods
                formatDateTime(dateString) {
                    if (!dateString) return 'N/A';
                    const date = new Date(dateString);
                    return date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: 'numeric',
                        minute: '2-digit'
                    });
                },
                
                timeAgo(dateString) {
                    if (!dateString) return '';
                    const date = new Date(dateString);
                    const now = new Date();
                    const diffMs = now - date;
                    const diffSecs = Math.floor(diffMs / 1000);
                    const diffMins = Math.floor(diffSecs / 60);
                    const diffHours = Math.floor(diffMins / 60);
                    const diffDays = Math.floor(diffHours / 24);
                    
                    if (diffDays > 0) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
                    if (diffHours > 0) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
                    if (diffMins > 0) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
                    return 'Just now';
                },
                
                getActionLabel(actionType) {
                    return this.actionTypes[actionType] || actionType;
                },
                
                getActionClass(actionType) {
                    const classes = {
                        'confirm_booking': 'bg-green-100 text-green-800',
                        'mark_no_show': 'bg-red-100 text-red-800',
                        'add_note': 'bg-blue-100 text-blue-800',
                        'update_payment': 'bg-purple-100 text-purple-800',
                        'update_extension_payment': 'bg-indigo-100 text-indigo-800',
                        'update_booking': 'bg-yellow-100 text-yellow-800',
                        'cancel_booking': 'bg-gray-100 text-gray-800',
                        'reschedule_booking': 'bg-orange-100 text-orange-800',
                        'complete_booking': 'bg-teal-100 text-teal-800',
                        'process_main_payment': 'bg-green-100 text-green-800', 
                        'process_extension_payment': 'bg-blue-100 text-blue-800', 
                        'process_order_payment': 'bg-purple-100 text-purple-800',
                        'create_booking': 'bg-green-100 text-green-800',
                        'checkin_customer': 'bg-teal-100 text-teal-800',
                        'update_branch_status': 'bg-yellow-100 text-yellow-800',
                        'extend_time': 'bg-blue-100 text-blue-800',
                        'process_rewards': 'bg-green-100 text-green-800',  
                        'update_reward_status': 'bg-yellow-100 text-yellow-800',  
                        'create_ingredient': 'bg-blue-100 text-blue-800',  
                        'update_ingredient': 'bg-indigo-100 text-indigo-800',  
                        'update_ingredient_status': 'bg-yellow-100 text-yellow-800',  
                        'deactivate_ingredient': 'bg-red-100 text-red-800',  
                        'reactivate_ingredient': 'bg-green-100 text-green-800',  
                        'damage_ingredient': 'bg-orange-100 text-orange-800',
                        'process_pos_order': 'bg-teal-100 text-teal-800',  
                        'update_stock': 'bg-blue-100 text-blue-800',  
                        'create_product': 'bg-green-100 text-green-800',  
                        'update_product': 'bg-indigo-100 text-indigo-800',  
                        'update_product_status': 'bg-yellow-100 text-yellow-800',  
                        'deactivate_product': 'bg-red-100 text-red-800',  
                        'reactivate_product': 'bg-green-100 text-green-800',  
                        'damage_product': 'bg-orange-100 text-orange-800',
                        'add_product_ingredient': 'bg-teal-100 text-teal-800',  
                        'update_product_ingredient': 'bg-indigo-100 text-indigo-800',  
                        'create_reward_tier': 'bg-green-100 text-green-800',  
                        'update_reward_tier': 'bg-yellow-100 text-yellow-800',  
                        'update_reward_tier_status': 'bg-purple-100 text-purple-800',
                        'update_seat_status': 'bg-yellow-100 text-yellow-800',
                        'checkout_customer': 'bg-gray-100 text-gray-800',
                        'update_service_category_status': 'bg-yellow-100 text-yellow-800',
                        'update_service_name_status': 'bg-yellow-100 text-yellow-800',
                        'staff_checkin': 'bg-teal-100 text-teal-800',
                        'staff_checkout': 'bg-gray-100 text-gray-800',
                    };
                    return classes[actionType] || 'bg-gray-100 text-gray-800';
                }
            }));
        });
    </script>
@endsection