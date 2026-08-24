@extends('layouts.app')

@section('title', 'Space Availability')

@section('content')
    <style>
        .seat-container {
            transition: all 0.3s ease;
        }
        .seat {
            border-radius: 6px;
            padding: 8px;
            margin: 4px 0;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .seat-available {
            background-color: #d1fae5;
            border-color: #10b981;
            color: #065f46;
        }
        .seat-booked {
            background-color: #fee2e2;
            border-color: #ef4444;
            color: #991b1b;
            position: relative;
            overflow: hidden;
        }
        .seat-disabled {
            background-color: #f3f4f6;
            border-color: #d1d5db;
            color: #6b7280;
            opacity: 0.7;
        }
        .booking-time-badge {
            font-size: 11px; /* Increased from 10px */
            padding: 1px 4px;
            border-radius: 3px;
            background-color: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            margin-top: 2px;
            display: inline-block;
        }
        .customer-name {
            font-size: 12px; /* Increased from 11px */
            color: #7c2d12;
            margin-top: 1px;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .service-card {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
        }
        .service-card:hover {
            border-color: #7f5539;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .availability-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 13px; /* Increased from 12px */
            font-weight: 600;
        }
        .availability-good {
            background-color: #d1fae5;
            color: #065f46;
        }
        .availability-warning {
            background-color: #fef3c7;
            color: #92400e;
        }
        .availability-full {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .service-name-badge {
            font-size: 11px; /* Increased from 10px */
            padding: 1px 6px;
            border-radius: 3px;
            background-color: #e0e7ff;
            color: #3730a3;
            margin-left: 4px;
        }
        
        /* Custom Scrollbar for the cards content */
        .service-card-content::-webkit-scrollbar {
            width: 6px;
        }
        .service-card-content::-webkit-scrollbar-track {
            background: #f9fafb;
            border-radius: 4px;
        }
        .service-card-content::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 4px;
        }
        .service-card-content::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }
    </style>
    
    <!-- Add Branch Button -->
    <a href="{{ route('sub_two.book_now.create') }}" class="fixed bottom-6 right-6 z-50">
        <span class="relative flex items-center justify-center w-12 h-12">
            <!-- Pulsing circle behind -->
            <span class="absolute flex items-center justify-center">
                <span class="w-16 h-16 rounded-full bg-[#7F5539] opacity-40 animate-pulse-slow"></span>
            </span>

            <!-- Foreground button wrapped in a group -->
            <span
                class="relative group flex items-center justify-center w-12 h-12 bg-[#7F5539] text-white rounded-full shadow-lg hover:bg-[#4A2C1D] transition duration-300 ease-in-out cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="1.5" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>

                <!-- Tooltip label on the left -->
                <span
                    class="absolute right-full top-1/2 -translate-y-1/2 mr-2 bg-gray-800 text-white text-xs font-medium px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none">
                    Book Now
                </span>
            </span>
        </span>
    </a>

    <!-- Changed container to w-full and reduced padding -->
    <div class="w-full px-4 py-4">
        <!-- Header -->
        <div class="mb-4"> <!-- Reduced margin -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <!-- Increased font size to text-2xl -->
                    <h1 class="text-2xl font-bold text-gray-900">Space Availability</h1>
                    <div class="mt-1">
                        <!-- Increased font size to text-sm -->
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-sm font-medium bg-[#7F5539]/10 text-[#7F5539]">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            {{ \Carbon\Carbon::parse($selectedDate)->format('F j, Y') }}
                        </span>
                        <!-- Increased font size to text-sm -->
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-sm font-medium bg-blue-100 text-blue-800 ml-2">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 8v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            {{ $branch->branch_name }}
                        </span>
                    </div>
                </div>
                
                <div class="flex space-x-2">
                    <!-- Increased button font size to text-sm -->
                    <a href="{{ route('sub_two.booking_lists.showBookingList') . '?date_start=' . $selectedDate . '&date_end=' . $selectedDate }}"
                       class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        View Bookings
                    </a>
                    <a href="{{ route('sub_two.booking_calendar.showBookingCalendar') }}"
                       class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539]">
                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Back to Calendar
                    </a>
                </div>
            </div>
        </div>

        <!-- Legend and Summary Row - Layout change to side-by-side on large screens -->
        <div class="grid grid-cols-1 xl:grid-cols-4 gap-4 mb-4">
            <!-- Legend (Takes 1 part on XL) -->
            <div class="xl:col-span-1 grid grid-cols-1 sm:grid-cols-3 xl:grid-cols-1 gap-2 h-full">
                <div class="p-3 bg-white border border-gray-200 rounded-lg shadow-sm flex flex-col justify-center h-full">
                    <div class="flex items-center mb-1">
                        <div class="w-3 h-3 rounded-full bg-green-500 mr-2"></div>
                        <!-- Increased to text-sm -->
                        <span class="text-sm font-medium text-gray-700">Available</span>
                    </div>
                    <!-- Increased to text-xs -->
                    <p class="text-xs text-gray-500">Free to book</p>
                </div>
                <div class="p-3 bg-white border border-gray-200 rounded-lg shadow-sm flex flex-col justify-center h-full">
                    <div class="flex items-center mb-1">
                        <div class="w-3 h-3 rounded-full bg-red-500 mr-2"></div>
                        <span class="text-sm font-medium text-gray-700">Booked</span>
                    </div>
                    <p class="text-xs text-gray-500">Already reserved</p>
                </div>
                <div class="p-3 bg-white border border-gray-200 rounded-lg shadow-sm flex flex-col justify-center h-full">
                    <div class="flex items-center mb-1">
                        <div class="w-3 h-3 rounded-full bg-gray-400 mr-2"></div>
                        <span class="text-sm font-medium text-gray-700">No Seat</span>
                    </div>
                    <p class="text-xs text-gray-500">Not assigned</p>
                </div>
            </div>
            
            <!-- Summary (Takes 3 parts on XL) -->
            <div class="xl:col-span-3 p-3 bg-gray-50 rounded-lg border border-gray-200 flex flex-col h-full">
                <div class="flex justify-between items-center mb-2">
                    <!-- Increased to text-base -->
                    <h3 class="text-base font-semibold text-gray-900">Summary</h3>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 flex-grow h-full">
                    <div class="text-center p-2 bg-white rounded border flex flex-col justify-center h-full">
                        <!-- Increased to text-3xl -->
                        <div class="text-3xl font-bold text-blue-600 leading-none">
                            {{ $serviceCategories->count() }}
                        </div>
                        <!-- Increased to text-sm -->
                        <div class="text-sm text-gray-600 mt-1">Service Categories</div>
                    </div>
                    <div class="text-center p-2 bg-white rounded border flex flex-col justify-center h-full">
                        <div class="text-3xl font-bold text-purple-600 leading-none">
                            {{ $serviceCategories->sum(fn($cat) => $cat->serviceNames->count()) }}
                        </div>
                        <div class="text-sm text-gray-600 mt-1">Total Services</div>
                    </div>
                    <div class="text-center p-2 bg-white rounded border flex flex-col justify-center h-full">
                        <div class="text-3xl font-bold text-green-600 leading-none">
                            {{ array_sum($availableSeatsByCategory) }}
                        </div>
                        <div class="text-sm text-gray-600 mt-1">Available Seats</div>
                    </div>
                    <div class="text-center p-2 bg-white rounded border flex flex-col justify-center h-full">
                        <div class="text-3xl font-bold text-red-600 leading-none">
                            {{ array_sum($totalSeatsByCategory) - array_sum($availableSeatsByCategory) }}
                        </div>
                        <div class="text-sm text-gray-600 mt-1">Booked Seats</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Categories and Availability -->
        @if($serviceCategories->isEmpty())
            <div class="text-center py-8 bg-white rounded-lg border border-gray-200">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 8v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <!-- Increased to text-base -->
                <h3 class="mt-2 text-base font-medium text-gray-900">No services available</h3>
                <!-- Increased to text-sm -->
                <p class="mt-1 text-sm text-gray-500">There are no active services in this branch.</p>
            </div>
        @else
            <!-- Changed grid to be more responsive to large screens (3 columns) -->
            <div class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-4">
                @foreach($serviceCategories as $category)
                    @if($category->service_category_status == 1)
                        @php
                            $categorySeats = $seatsByCategory[$category->id] ?? [];
                            $availableCount = $availableSeatsByCategory[$category->id] ?? 0;
                            $totalCount = $totalSeatsByCategory[$category->id] ?? 0;
                            
                            if ($totalCount === 0) {
                                $badgeClass = 'availability-full';
                                $badgeText = 'No Seats';
                            } elseif ($availableCount === 0) {
                                $badgeClass = 'availability-full';
                                $badgeText = 'Full';
                            } else {
                                $badgeClass = 'availability-good';
                                $badgeText = 'Available';
                            }
                        @endphp
                        
                        <div class="service-card bg-white flex flex-col 2xl:h-[600px]">
                            <!-- Category Header -->
                            <div class="bg-gray-50 px-4 py-3 border-b flex-shrink-0">
                                <div class="flex justify-between items-center">
                                    <!-- Increased to text-lg -->
                                    <h2 class="text-lg font-semibold text-gray-900 truncate pr-2">{{ $category->service_category }}</h2>
                                    <span class="availability-badge {{ $badgeClass }} flex-shrink-0">
                                        {{ $availableCount }}/{{ $totalCount }} {{ $badgeText }}
                                    </span>
                                </div>
                            </div>

                            <!-- Services List and Seats - Scrollable Content -->
                            <div class="p-4 flex-grow overflow-y-auto service-card-content">
                                <!-- Available Services in this Category -->
                                <div class="mb-4">
                                    <!-- Increased to text-sm -->
                                    <h3 class="font-medium text-sm text-gray-500 uppercase tracking-wider mb-2">Services</h3>
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse($category->serviceNames as $service)
                                            @if($service->service_name_status == 1)
                                                <!-- Increased to text-xs -->
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                                                    {{ $service->service_name }}
                                                </span>
                                            @endif
                                        @empty
                                            <span class="text-xs text-gray-400 italic">None</span>
                                        @endforelse
                                    </div>
                                </div>

                                <!-- Seats by Room -->
                                @if(count($categorySeats) > 0)
                                    @php
                                        $seatsByRoom = [];
                                        foreach ($categorySeats as $seat) {
                                            $roomNo = $seat->room_no ?? 'General';
                                            if (!isset($seatsByRoom[$roomNo])) {
                                                $seatsByRoom[$roomNo] = [];
                                            }
                                            $seatsByRoom[$roomNo][] = $seat;
                                        }
                                    @endphp

                                    <!-- MOVED GRID OUTSIDE THE LOOP -->
                                    <!-- This creates a continuous grid for all rooms in the category, solving the vertical stacking issue -->
                                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-2 mt-2">
                                        @foreach($seatsByRoom as $roomNo => $seatsInRoom)
                                            @foreach($seatsInRoom as $seat)
                                                @if($seat->seat_status == 1)
                                                    @php
                                                        $isBooked = isset($bookedSeats[$seat->id]);
                                                        $bookingInfo = $bookingTimes[$seat->id] ?? null;
                                                    @endphp
                                                    
                                                    <div class="seat {{ $isBooked ? 'seat-booked' : 'seat-available' }}">
                                                        <div class="flex items-center justify-between mb-1">
                                                            <!-- Updated Label Logic -->
                                                            <!-- Increased to text-sm -->
                                                            <span class="font-bold text-sm">
                                                                @if($seat->seat_no == $roomNo || empty($seat->seat_no))
                                                                    Room-{{ $roomNo }}
                                                                @else
                                                                    Seat-{{ $seat->seat_no }}
                                                                @endif
                                                            </span>
                                                            <span class="text-xs opacity-75">
                                                                @if($isBooked)
                                                                    <svg class="w-3.5 h-3.5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                                    </svg>
                                                                @else
                                                                    <svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                                    </svg>
                                                                @endif
                                                            </span>
                                                        </div>
                                                        
                                                        @if($isBooked && $bookingInfo)
                                                            <div class="mt-1">
                                                                <div class="booking-time-badge w-full text-center">
                                                                    {{ \Carbon\Carbon::parse($bookingInfo['start_time'])->format('g:i A') }} - {{ \Carbon\Carbon::parse($bookingInfo['end_time'])->format('g:i A') }}
                                                                </div>
                                                                <div class="customer-name mt-1" title="{{ $bookingInfo['customer_name'] }}">
                                                                    {{ $bookingInfo['customer_name'] }}
                                                                </div>
                                                            </div>
                                                        @elseif(!$isBooked)
                                                            <!-- Increased to text-xs -->
                                                            <div class="text-xs text-green-700 mt-1 font-medium text-center bg-green-50 rounded py-0.5">
                                                                Available
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            @endforeach
                                        @endforeach
                                    </div>
                                @else
                                    <div class="seat seat-disabled mt-2">
                                        <div class="text-center py-3">
                                            <svg class="mx-auto h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <p class="mt-1 text-sm text-gray-500">No seats</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
@endsection