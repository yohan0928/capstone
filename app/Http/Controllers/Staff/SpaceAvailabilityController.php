<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use App\Models\Seat;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SpaceAvailabilityController extends Controller
{
    public function showSpaceAvailability(Request $request)
    {
        // Logged in as Staff
        $staff = Auth::guard('staff')->user();
        $staffId = $staff->id;
        $branchId = $staff->branch_id;
        $ownerId = $staff->owner_account_id;

        // Validate date parameter
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $selectedDate = $request->date;
        
        // Get the staff's branch
        $branch = Branch::where('id', $branchId)
            ->where('owner_account_id', $ownerId)
            ->where('branch_status', 1) // Check for active branch status
            ->first();

        if (!$branch) {
            abort(403, 'Unauthorized access to this branch or branch is inactive');
        }

        // Get all service categories for this branch
        // Added whereHas to ensure we don't fetch categories with 0 active services
        $serviceCategories = ServiceCategory::where('branch_id', $branchId)
            ->where('service_category_status', 1) // Updated to service_category_status
            ->whereHas('serviceNames', function($query) use ($branchId) {
                $query->where('service_name_status', 1) // Updated to service_name_status
                      ->where('branch_id', $branchId);
            })
            ->with(['serviceNames' => function($query) use ($branchId) {
                $query->where('service_name_status', 1) // Updated to service_name_status
                      ->where('branch_id', $branchId)
                      ->orderBy('service_name');
            }])
            ->orderBy('service_category')
            ->get();

        // Get all seats for this branch
        // Ordered by room_no first to ensure logical grouping
        $allSeats = Seat::where('branch_id', $branchId)
            ->where('seat_status', 1) // Updated to seat_status (filters out unavailable seats)
            ->orderBy('room_no')
            ->orderBy('seat_no')
            ->get();

        // Get all bookings for the selected date
        $bookings = Booking::where('branch_id', $branchId)
            ->where('active', 1)
            ->whereDate('date_start', '=', $selectedDate)
            ->whereIn('booking_status', [1, 4]) // Only Booked and Completed statuses
            ->with(['seat', 'serviceName', 'customerAccount'])
            ->get();

        // Group seats by service category
        $seatsByCategory = [];
        $seatCategoryMap = []; // Maps seat_id -> service_category_id
        
        // New: Group seats by Category AND Room for easier View iteration
        $seatsByCategoryAndRoom = [];

        foreach ($allSeats as $seat) {
            if ($seat->service_category_id) {
                // 1. Group by Category (Flat list)
                if (!isset($seatsByCategory[$seat->service_category_id])) {
                    $seatsByCategory[$seat->service_category_id] = [];
                }
                $seatsByCategory[$seat->service_category_id][] = $seat;
                $seatCategoryMap[$seat->id] = $seat->service_category_id;

                // 2. Group by Category THEN Room (Nested structure)
                $roomNo = $seat->room_no ?? 'General';
                if (!isset($seatsByCategoryAndRoom[$seat->service_category_id])) {
                    $seatsByCategoryAndRoom[$seat->service_category_id] = [];
                }
                if (!isset($seatsByCategoryAndRoom[$seat->service_category_id][$roomNo])) {
                    $seatsByCategoryAndRoom[$seat->service_category_id][$roomNo] = [];
                }
                $seatsByCategoryAndRoom[$seat->service_category_id][$roomNo][] = $seat;
            }
        }

        // Filter out Service Categories that have NO active seats.
        // Since $allSeats only contains 'active=1' seats, checking for emptiness here
        // effectively satisfies the condition: "do not display if all statuses are 0".
        $serviceCategories = $serviceCategories->filter(function ($category) use ($seatsByCategory) {
            return isset($seatsByCategory[$category->id]) && count($seatsByCategory[$category->id]) > 0;
        });

        // Create a map of booked seats
        $bookedSeats = [];
        $bookingTimes = [];
        
        foreach ($bookings as $booking) {
            if ($booking->seat_id) {
                $key = $booking->seat_id;
                $bookedSeats[$key] = true;
                
                // Store booking details for display
                $bookingTimes[$key] = [
                    'start_time' => Carbon::parse($booking->start_time)->format('g:i A'),
                    'end_time' => Carbon::parse($booking->end_time)->format('g:i A'),
                    'customer_name' => $booking->customerAccount ? 
                        $booking->customerAccount->first_name . ' ' . $booking->customerAccount->last_name : 
                        'N/A',
                    'booking_ref' => $booking->booking_ref_no,
                    'service_name' => $booking->serviceName ? $booking->serviceName->service_name : 'N/A',
                    'room_no' => $booking->seat ? $booking->seat->room_no : 'N/A', // Added room info
                ];
            }
        }

        // Calculate total and available seats per service category
        $totalSeatsByCategory = [];
        $availableSeatsByCategory = [];
        
        foreach ($seatsByCategory as $categoryId => $seats) {
            $totalSeatsByCategory[$categoryId] = count($seats);
            $bookedCount = 0;
            
            foreach ($seats as $seat) {
                if (isset($bookedSeats[$seat->id])) {
                    $bookedCount++;
                }
            }
            
            $availableSeatsByCategory[$categoryId] = $totalSeatsByCategory[$categoryId] - $bookedCount;
        }

        return view('staff.booking.space_availability', compact(
            'serviceCategories',
            'selectedDate',
            'branch',
            'allSeats',
            'seatsByCategory',
            'seatsByCategoryAndRoom', // New variable passed to view
            'bookedSeats',
            'bookingTimes',
            'totalSeatsByCategory',
            'availableSeatsByCategory',
            'seatCategoryMap'
        ));
    }
}