<?php

namespace App\Http\Controllers\Owner;

use App\Models\Booking;
use App\Models\Branch;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class BookingCalendarController extends Controller
{
    public function showBookingCalendar()
    {
        // Get the currently authenticated owner
        $owner = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        // 1. Get all branches for this owner
        $branches = Branch::where('owner_account_id', $ownerId)->get();

        // 2. Get the total max daily bookings across all branches
        $totalMaxDailyBookings = $branches->sum('max_daily_bookings');

        // 3. Get all active bookings for these branches
        $branchIds = $branches->pluck('id');
        
        $bookings = Booking::where('active', 1)
            ->whereIn('branch_id', $branchIds)
            ->select('id', 'date_start', 'date_end', 'booking_status')
            ->get();

        return view('owner.booking.booking_calendar', compact('bookings', 'totalMaxDailyBookings'));
    }
}