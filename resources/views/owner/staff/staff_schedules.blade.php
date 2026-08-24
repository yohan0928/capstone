@extends('layouts.app')

@section('title', 'Staff Schedules - ' . $staff->first_name . ' ' . $staff->last_name)

@section('content')

    <div x-data="staffSchedules()" class="p-4">

        <!-- Header Section -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <!-- Staff Information -->
                <div class="flex items-center space-x-4 mb-4 lg:mb-0">
                    <div class="bg-[#4A2C1D]/10 rounded-lg p-3">
                        <svg class="w-6 h-6 text-[#7F5539]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-[#4A2C1D]">
                            {{ $staff->first_name }} {{ $staff->last_name }}
                        </h1>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center space-x-3">
                    <!-- Back Button -->
                    <a href="{{ route('sub_one.staff.showStaffList') }}"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Staff List
                    </a>
                </div>
            </div>
        </div>

        <!-- Schedules Table Section -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Table Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            Shift Schedules
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ $staff->first_name }} {{ $staff->last_name }}
                        </p>
                    </div>

                    <!-- Add Shift Button aligned to the right -->
                    <button @click="openAddShiftModal()"
                        class="inline-flex items-center px-4 py-2 bg-[#7F5539] text-white rounded-lg hover:bg-[#4A2C1D] transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Branch
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Current Shift
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Check-in Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Time Worked
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($staff->staffSchedules as $schedule)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <!-- Branch -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $schedule->branch->branch_name ?? 'No branch assigned' }}
                                    </div>
                                </td>

                                <!-- Current Shift -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="space-y-1">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ \Carbon\Carbon::parse($schedule->shift_date_start)->format('M j, Y') }}
                                            @if ($schedule->shift_date_end && $schedule->shift_date_start != $schedule->shift_date_end)
                                                - {{ \Carbon\Carbon::parse($schedule->shift_date_end)->format('M j, Y') }}
                                            @endif
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ \Carbon\Carbon::parse($schedule->shift_time_start)->format('g:i A') }} -
                                            {{ \Carbon\Carbon::parse($schedule->shift_time_end)->format('g:i A') }}
                                        </div>
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            @if ($schedule->staff_shift_schedule_status == 0) bg-green-100 text-green-800
                                            @elseif($schedule->staff_shift_schedule_status == 1) bg-blue-100 text-blue-800
                                            @elseif($schedule->staff_shift_schedule_status == 2) bg-yellow-100 text-yellow-800
                                            @else bg-gray-100 text-gray-800 @endif">
                                            @if ($schedule->staff_shift_schedule_status == 0)
                                                Completed
                                            @elseif($schedule->staff_shift_schedule_status == 1)
                                                On-duty
                                            @elseif($schedule->staff_shift_schedule_status == 2)
                                                Pending
                                            @else
                                                Unknown
                                            @endif
                                        </span>
                                    </div>
                                </td>

                                <!-- Check-in Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($schedule->checkins->count() > 0)
                                        @foreach ($schedule->checkins as $checkin)
                                            <div class="space-y-1 mb-2 last:mb-0">
                                                <!-- Date Range -->
                                                <div class="text-sm font-medium text-gray-900">
                                                    @php
                                                        $checkinDate = \Carbon\Carbon::parse($checkin->checkin_time);
                                                        $checkoutDate = $checkin->checkout_time
                                                            ? \Carbon\Carbon::parse($checkin->checkout_time)
                                                            : null;

                                                        if (
                                                            $checkoutDate &&
                                                            $checkinDate->toDateString() ===
                                                                $checkoutDate->toDateString()
                                                        ) {
                                                            echo $checkinDate->format('M j, Y');
                                                        } elseif ($checkoutDate) {
                                                            echo $checkinDate->format('M j') .
                                                                ' - ' .
                                                                $checkoutDate->format('M j, Y');
                                                        } else {
                                                            echo $checkinDate->format('M j, Y');
                                                        }
                                                    @endphp
                                                </div>

                                                <!-- Time Range -->
                                                <div class="text-sm text-gray-500">
                                                    {{ \Carbon\Carbon::parse($checkin->checkin_time)->format('g:i A') }}
                                                    @if ($checkin->checkout_time)
                                                        -
                                                        {{ \Carbon\Carbon::parse($checkin->checkout_time)->format('g:i A') }}
                                                    @endif
                                                </div>

                                                <!-- Status Badge -->
                                                <div>
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                        @if ($checkin->checkin_status === true) bg-green-100 text-green-800
                                                        @elseif($checkin->checkin_status === false) bg-blue-100 text-blue-800
                                                        @else bg-gray-100 text-gray-800 @endif">
                                                        @if ($checkin->checkin_status === true)
                                                            Checked-in
                                                        @elseif($checkin->checkin_status === false)
                                                            Checked-out
                                                        @else
                                                            Unknown
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="text-left py-2">
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                No Check-ins
                                            </span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Time Worked -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($schedule->checkins->count() > 0)
                                        @foreach ($schedule->checkins as $checkin)
                                            <div class="mb-2 last:mb-0 text-left">
                                                @if ($checkin->time_worked)
                                                    <div>
                                                        <div class="font-medium text-gray-900">
                                                            @php
                                                                $totalMinutes = $checkin->time_worked;
                                                                $hours = floor($totalMinutes / 60);
                                                                $minutes = $totalMinutes % 60;

                                                                if ($hours > 0 && $minutes > 0) {
                                                                    echo "{$hours} hr" .
                                                                        ($hours !== 1 ? 's' : '') .
                                                                        " : {$minutes} min" .
                                                                        ($minutes !== 1 ? 's' : '');
                                                                } elseif ($hours > 0) {
                                                                    echo "{$hours} hr" . ($hours !== 1 ? 's' : '');
                                                                } else {
                                                                    echo "{$minutes} min" . ($minutes !== 1 ? 's' : '');
                                                                }
                                                            @endphp
                                                        </div>
                                                        <div class="text-xs text-gray-500 mt-1">
                                                            ({{ $checkin->time_worked }} mins)
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="text-center py-2">
                                            <span class="text-gray-400">-</span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <!-- Edit Shift Button -->
                                        <div class="relative group">
                                            <button @click="openEditShiftModal({{ $schedule->id }})"
                                                class="p-1.5 text-[#4A2C1D] hover:text-white hover:bg-[#4A2C1D] rounded-full transition-colors duration-200">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                </svg>
                                            </button>
                                            <span
                                                class="absolute right-full top-1/2 -translate-y-1/2 mr-2 bg-gray-800 text-white text-xs font-medium px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none">
                                                Edit Shift
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="text-gray-400">
                                        <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <h5 class="text-sm font-medium text-gray-900">No shift schedules found</h5>
                                        <p class="text-sm text-gray-500">When you add shifts, they will appear here.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Shift Modal -->
        <div x-show="addShiftModalOpen" class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
            style="display: none;">
            <!-- Modal Overlay -->
            <div x-show="addShiftModalOpen" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black/50" @click="closeAddShiftModal()"></div>

            <!-- Modal Content -->
            <div x-show="addShiftModalOpen" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative bg-white shadow-md rounded-lg overflow-hidden w-full max-w-2xl border border-[#4A2C1D] max-h-[90vh] flex flex-col">

                <!-- Modal Header -->
                <div class="relative p-6 border-b border-gray-200">
                    <button @click.prevent="closeAddShiftModal()"
                        class="absolute top-3 right-3 text-[#7F5539] hover:bg-[#4A2C1D] hover:text-white rounded p-1 z-10">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <h1 class="text-2xl font-bold text-[#4A2C1D] text-center">Add Shift Schedule</h1>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4 overflow-y-auto">
                    <form id="addShiftForm" action="{{ route('sub_one.staff.storeStaffShiftSchedule') }}" method="POST"
                        class="space-y-4">
                        @csrf

                        <!-- Branch Selection -->
                        <div>
                            <label class="block text-sm font-medium text-[#4A2C1D] mb-2">Assign Branch</label>
                            <select name="branch_id" required
                                class="w-full border-2 border-[#7F5539] rounded-lg px-4 py-3 focus:border-[#4A2C1D] focus:ring-1 focus:ring-[#4A2C1D] transition-colors">
                                <option value="">Select a branch</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Staff Information (Hidden) -->
                        <input type="hidden" name="staff_account_id" value="{{ $staff->id }}">

                        <!-- Date Inputs -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-[#4A2C1D] mb-2">Shift Start Date</label>
                                <input type="date" name="shift_date_start" required
                                    class="w-full border-2 border-[#7F5539] rounded-lg px-4 py-3 focus:border-[#4A2C1D] focus:ring-1 focus:ring-[#4A2C1D] transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-[#4A2C1D] mb-2">Shift End Date</label>
                                <input type="date" name="shift_date_end"
                                    class="w-full border-2 border-[#7F5539] rounded-lg px-4 py-3 focus:border-[#4A2C1D] focus:ring-1 focus:ring-[#4A2C1D] transition-colors">
                            </div>
                        </div>

                        <!-- Time Inputs -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-[#4A2C1D] mb-2">Shift Start Time</label>
                                <input type="time" name="shift_time_start" required
                                    class="w-full border-2 border-[#7F5539] rounded-lg px-4 py-3 focus:border-[#4A2C1D] focus:ring-1 focus:ring-[#4A2C1D] transition-colors"
                                    step="60">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-[#4A2C1D] mb-2">Shift End Time</label>
                                <input type="time" name="shift_time_end" required
                                    class="w-full border-2 border-[#7F5539] rounded-lg px-4 py-3 focus:border-[#4A2C1D] focus:ring-1 focus:ring-[#4A2C1D] transition-colors"
                                    step="60">
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="p-6 border-t border-gray-200">
                    <div class="flex gap-3">
                        <button type="button" @click="closeAddShiftModal()"
                            class="flex-1 bg-gray-200 text-gray-800 px-6 py-3 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                            Cancel
                        </button>
                        <button type="submit" form="addShiftForm"
                            class="flex-1 bg-[#7F5539] text-white px-6 py-3 rounded-lg hover:bg-[#4A2C1D] transition-colors font-medium">
                            Add Shift
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Shift Modal -->
        <div x-show="editShiftModalOpen" class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
            style="display: none;">
            <!-- Modal Overlay -->
            <div x-show="editShiftModalOpen" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black/50" @click="closeEditShiftModal()">
            </div>

            <!-- Modal Content -->
            <div x-show="editShiftModalOpen" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative bg-white shadow-md rounded-lg overflow-hidden w-full max-w-2xl border border-[#4A2C1D] max-h-[90vh] flex flex-col">

                <!-- Modal Header -->
                <div class="relative p-6 border-b border-gray-200">
                    <button @click.prevent="closeEditShiftModal()"
                        class="absolute top-3 right-3 text-[#7F5539] hover:bg-[#4A2C1D] hover:text-white rounded p-1 z-10">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <h1 class="text-2xl font-bold text-[#4A2C1D] text-center">Edit Shift Schedule</h1>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4 overflow-y-auto">
                    <form id="editShiftForm" class="space-y-4">
                        @csrf
                        @method('PATCH')

                        <!-- Branch Selection -->
                        <div>
                            <label class="block text-sm font-medium text-[#4A2C1D] mb-2">Assign Branch</label>
                            <select name="branch_id" x-model="editShiftFormData.branch_id" required
                                class="w-full border-2 border-[#7F5539] rounded-lg px-4 py-3 focus:border-[#4A2C1D] focus:ring-1 focus:ring-[#4A2C1D] transition-colors">
                                <option value="">Select a branch</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Date Inputs -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-[#4A2C1D] mb-2">Shift Start Date</label>
                                <input type="date" name="shift_date_start"
                                    x-model="editShiftFormData.shift_date_start" required
                                    class="w-full border-2 border-[#7F5539] rounded-lg px-4 py-3 focus:border-[#4A2C1D] focus:ring-1 focus:ring-[#4A2C1D] transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-[#4A2C1D] mb-2">Shift End Date</label>
                                <input type="date" name="shift_date_end" x-model="editShiftFormData.shift_date_end"
                                    class="w-full border-2 border-[#7F5539] rounded-lg px-4 py-3 focus:border-[#4A2C1D] focus:ring-1 focus:ring-[#4A2C1D] transition-colors">
                            </div>
                        </div>

                        <!-- Time Inputs -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-[#4A2C1D] mb-2">Shift Start Time</label>
                                <input type="time" name="shift_time_start"
                                    x-model="editShiftFormData.shift_time_start" required
                                    class="w-full border-2 border-[#7F5539] rounded-lg px-4 py-3 focus:border-[#4A2C1D] focus:ring-1 focus:ring-[#4A2C1D] transition-colors"
                                    step="60">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-[#4A2C1D] mb-2">Shift End Time</label>
                                <input type="time" name="shift_time_end" x-model="editShiftFormData.shift_time_end"
                                    required
                                    class="w-full border-2 border-[#7F5539] rounded-lg px-4 py-3 focus:border-[#4A2C1D] focus:ring-1 focus:ring-[#4A2C1D] transition-colors"
                                    step="60">
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="p-6 border-t border-gray-200">
                    <div class="flex gap-3">
                        <button type="button" @click="closeEditShiftModal()"
                            class="flex-1 bg-gray-200 text-gray-800 px-6 py-3 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                            Cancel
                        </button>
                        <button type="button" @click="submitEditShiftForm()"
                            class="flex-1 bg-[#7F5539] text-white px-6 py-3 rounded-lg hover:bg-[#4A2C1D] transition-colors font-medium">
                            Update Shift
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('staffSchedules', () => ({
                // Modal states
                addShiftModalOpen: false,
                editShiftModalOpen: false,

                // Edit form data
                editShiftFormData: {
                    branch_id: '',
                    shift_date_start: '',
                    shift_date_end: '',
                    shift_time_start: '',
                    shift_time_end: ''
                },

                currentShiftId: null,

                // Modal methods
                openAddShiftModal() {
                    this.addShiftModalOpen = true;
                },

                closeAddShiftModal() {
                    this.addShiftModalOpen = false;
                },

                async openEditShiftModal(shiftId) {
                    this.editShiftModalOpen = true;
                    this.currentShiftId = shiftId;
                    await this.loadShiftData(shiftId);
                },

                closeEditShiftModal() {
                    this.editShiftModalOpen = false;
                    this.resetEditShiftForm();
                },

                resetEditShiftForm() {
                    this.editShiftFormData = {
                        branch_id: '',
                        shift_date_start: '',
                        shift_date_end: '',
                        shift_time_start: '',
                        shift_time_end: ''
                    };
                    this.currentShiftId = null;
                },

                async loadShiftData(shiftId) {
                    try {
                        const response = await fetch(`/sub_one/staff/${shiftId}/edit-data`);

                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }

                        const shift = await response.json();

                        this.editShiftFormData = {
                            branch_id: shift.branch_id || '',
                            shift_date_start: shift.shift_date_start || '',
                            shift_date_end: shift.shift_date_end || '',
                            shift_time_start: shift.shift_time_start || '',
                            shift_time_end: shift.shift_time_end || ''
                        };

                    } catch (error) {
                        console.error('Error loading shift data:', error);
                        alert('Failed to load shift data. Please try again.');
                    }
                },

                async submitEditShiftForm() {
                    try {
                        const formData = new FormData();
                        formData.append('_token', '{{ csrf_token() }}');
                        formData.append('_method', 'PATCH');
                        formData.append('branch_id', this.editShiftFormData.branch_id);
                        formData.append('staff_account_id', '{{ $staff->id }}');
                        formData.append('shift_date_start', this.editShiftFormData
                            .shift_date_start);
                        formData.append('shift_date_end', this.editShiftFormData.shift_date_end);
                        formData.append('shift_time_start', this.editShiftFormData
                            .shift_time_start);
                        formData.append('shift_time_end', this.editShiftFormData.shift_time_end);

                        const response = await fetch(
                            `/sub_one/staff/shifts/${this.currentShiftId}`, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message ||
                                `HTTP error! status: ${response.status}`);
                        }

                        if (data.success) {
                            this.closeEditShiftModal();
                            window.location.reload(); // Refresh to show updated data
                        } else {
                            throw new Error(data.message || 'Update failed');
                        }

                    } catch (error) {
                        console.error('Error updating shift:', error);
                        alert(error.message || 'Failed to update shift. Please try again.');
                    }
                }
            }));
        });
    </script>

@endsection
