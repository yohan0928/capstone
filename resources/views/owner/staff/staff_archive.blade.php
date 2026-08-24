@extends('layouts.app')

@section('title', 'Archived Staff Accounts')

@section('content')

    <div class="p-4">



        <h1 class="text-2xl font-bold text-[#4A2C1D] text-center mb-8">
            Archived Staff Accounts
        </h1>

        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-[#4A2C1D]">
                Deactivated Staff Accounts
            </h2>

            <div class="flex space-x-4">
                <a href="{{ route('sub_one.staff.showStaffList') }}"
                    class="text-sm font-medium text-[#7F5539] hover:underline">
                    Back to Active Staff
                </a>
            </div>
        </div>

        @if ($staff_accounts->isEmpty())
            <div class="p-4 bg-yellow-100 text-yellow-800 rounded">
                No archived staff accounts found.
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-6">
                @foreach ($staff_accounts as $staff_account)
                    <div x-data="{ reactivateModal: false }"
                        class="relative group/card bg-white border border-gray-300 rounded-2xl flex flex-col justify-end p-4 opacity-75">

                        <!-- Top Row: Staff Name + Reactivate Button -->
                        <div class="flex justify-between items-center mb-3">
                            <h2 class="text-lg font-bold text-gray-600 flex items-center space-x-3">
                                <span>{{ $staff_account->first_name }} {{ $staff_account->last_name }}</span>
                            </h2>

                            <!-- Reactivate Button -->
                            <div class="flex items-center space-x-1">
                                <!-- Status Badge -->
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-200 text-gray-600">
                                    Archived
                                </span>

                                <!-- Reactivate Button -->
                                <div class="relative group">
                                    <button @click="reactivateModal = true"
                                        class="p-1.5 text-gray-500 hover:text-white hover:bg-green-600 rounded-full transition-colors duration-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                        </svg>
                                    </button>

                                    <!-- Tooltip label -->
                                    <span
                                        class="absolute right-full top-1/2 -translate-y-1/2 mr-2 bg-gray-800 text-white text-xs font-medium px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none">
                                        Reactivate Account
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Staff Account Info -->
                        <div class="mb-3 space-y-1">
                            <div class="flex flex-col">
                                <span class="text-gray-500 text-sm">{{ $staff_account->email }}</span>
                                <span class="text-gray-500 text-sm">{{ $staff_account->contact_no }}</span>
                                <span class="text-gray-500 text-sm">{{ $staff_account->address }}</span>
                            </div>
                        </div>

                        <!-- Branch Assigned -->
                        <div class="mb-2">
                            <span class="text-gray-500 text-sm font-semibold">
                                Branch Assigned:
                            </span>
                            <span class="text-gray-500 text-sm">
                                {{ optional($staff_account->branch)->branch_name ?? 'No branch assigned' }}
                            </span>
                        </div>

                        <!-- Shift Information -->
                        @if ($staff_account->staffSchedules->isNotEmpty())
                            @php
                                $latestShift = $staff_account->staffSchedules
                                    ->where('active', 1)
                                    ->sortByDesc('date_created')
                                    ->first();
                            @endphp
                            <div class="mb-2 p-2 bg-gray-100 rounded border border-gray-200">
                                <span class="text-gray-500 text-sm font-semibold">Last Shift:</span>
                                <div class="text-gray-500 text-xs mt-1">
                                    <div>{{ \Carbon\Carbon::parse($latestShift->shift_date_start)->format('M d') }} -
                                        {{ \Carbon\Carbon::parse($latestShift->shift_date_end)->format('M d, Y') }}</div>
                                    <div>{{ \Carbon\Carbon::parse($latestShift->shift_time_start)->format('g:i A') }} -
                                        {{ \Carbon\Carbon::parse($latestShift->shift_time_end)->format('g:i A') }}</div>
                                    @php
                                        $shiftStatusClasses = [
                                            0 => 'bg-green-100 text-green-800',
                                            1 => 'bg-blue-100 text-blue-800',
                                            2 => 'bg-yellow-100 text-yellow-800',
                                        ];
                                        $shiftStatusText = [
                                            0 => 'Completed',
                                            1 => 'On-duty',
                                            2 => 'Pending',
                                        ];
                                    @endphp
                                    <span
                                        class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $shiftStatusClasses[$latestShift->staff_shift_schedule_status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ $shiftStatusText[$latestShift->staff_shift_schedule_status] ?? 'Unknown' }}
                                    </span>
                                </div>
                            </div>
                        @else
                            <div class="mb-2 p-2 bg-gray-100 rounded border border-gray-200">
                                <span class="text-gray-500 text-sm font-semibold">No Shift Assigned</span>
                            </div>
                        @endif

                        <!-- Date Archived -->
                        <div class="mb-2">
                            <span class="text-gray-400 text-xs">
                                Archived on:
                                {{ \Carbon\Carbon::parse($staff_account->date_updated)->format('M d, Y g:i A') }}
                            </span>
                        </div>

                        <hr class="mb-2 border-gray-300">

                        <!-- Reactivate Confirmation Modal -->
                        <div x-show="reactivateModal" x-transition
                            class="absolute inset-0 bg-white/95 backdrop-blur-sm rounded-2xl flex items-center justify-center p-4 z-10"
                            style="display: none;">
                            <div class="text-center w-full">
                                <h4 class="text-lg font-bold text-[#4A2C1D] mb-2">Confirm Reactivation</h4>
                                <p class="text-gray-600 mb-4">
                                    Reactivate account for
                                    <strong>{{ $staff_account->first_name }} {{ $staff_account->last_name }}</strong>?
                                </p>
                                <div class="flex space-x-3">
                                    <button @click="reactivateModal = false"
                                        class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                                        Cancel
                                    </button>
                                    <form action="{{ route('sub_one.staff.reactivateStaffAccount', $staff_account->id) }}"
                                        method="POST" class="flex-1">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                            class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition-colors">
                                            Reactivate
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination Links -->
            <div class="mt-6">
                {{ $staff_accounts->links() }}
            </div>
        @endif
    </div>

@endsection
