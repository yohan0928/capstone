@extends('layouts.app')

@section('title', 'Staff Reports')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-8 text-center">Staff Reports</h1>

        <!-- Simplified Search Bar -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <form method="GET" action="{{ route('sub_one.reports.staff_report') }}" class="max-w-md">
                <div class="flex items-center space-x-3">
                    <div class="flex-1">
                        <label for="search" class="sr-only">Search Staff</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}"
                            placeholder="Search by staff name..."
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-[#7F5539] focus:border-[#7F5539]">
                    </div>
                    <button type="submit"
                        class="px-6 py-3 bg-[#7F5539] text-white rounded-lg hover:bg-[#4A2C1D] transition-colors font-medium">
                        Search
                    </button>
                </div>
                @if(request('search'))
                    <div class="mt-3 text-sm text-gray-600">
                        Showing results for "{{ request('search') }}"
                        <a href="{{ route('sub_one.reports.staff_report') }}" 
                           class="ml-2 text-[#7F5539] hover:underline">
                            Clear search
                        </a>
                    </div>
                @endif
            </form>
        </div>

        <!-- Staff Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Staff Name
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Email
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Contact
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($staff as $staffMember)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="bg-[#4A2C1D]/10 rounded-lg p-2 mr-3">
                                            <svg class="w-4 h-4 text-[#7F5539]" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $staffMember->first_name }} {{ $staffMember->last_name }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ $staffMember->position ?? 'Staff' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $staffMember->email }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $staffMember->contact_no ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <!-- View Reports Button -->
                                    <a href="{{ route('sub_one.reports.report_data', $staffMember->uuid) }}"
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        View Reports
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <div class="text-gray-400">
                                        <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                        </svg>
                                        <h5 class="text-sm font-medium text-gray-900">No staff members found</h5>
                                        <p class="text-sm text-gray-500">
                                            @if(request('search'))
                                                No staff found matching "{{ request('search') }}"
                                            @else
                                                When staff are added to your branches, they will appear here.
                                            @endif
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($staff->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-700">
                            Showing {{ $staff->firstItem() }} to {{ $staff->lastItem() }} of {{ $staff->total() }}
                            entries
                        </div>
                        <div class="flex space-x-2">
                            {{ $staff->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection