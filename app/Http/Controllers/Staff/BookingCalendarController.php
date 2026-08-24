<?php

namespace App\Http\Controllers\Staff;

use App\Models\Booking;
use App\Models\Branch;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class BookingCalendarController extends Controller
{
    public function showBookingCalendar()
    {
        // Logged in as Staff
        $staff = Auth::guard('staff')->user();
        $staffId = $staff->id;
        $branchId = $staff->branch_id;  // Staff is assigned to a specific branch
        $ownerId = $staff->owner_account_id;  // Staff belongs to an owner

        // 1. Get the staff's specific branch with authorization check
        $branch = Branch::where('id', $branchId)
            ->where('owner_account_id', $ownerId)
            ->first(); // Use first() only expect one branch

        // If branch not found or staff doesn't have access
        if (!$branch) {
            abort(403, 'Unauthorized access to this branch');
        }

        // 2. Get the max daily bookings for the staff's branch
        $totalMaxDailyBookings = $branch->max_daily_bookings;

        // 3. Get all active bookings for the staff's branch only
        $bookings = Booking::where('active', 1)
            ->where('branch_id', $branchId)
            ->select('id', 'date_start', 'date_end', 'booking_status')
            ->get();

        return view('staff.booking.booking_calendar', compact('bookings', 'totalMaxDailyBookings', 'branch'));
    }
}