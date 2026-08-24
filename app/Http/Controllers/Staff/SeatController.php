<?php

namespace App\Http\Controllers\Staff;

use App\Models\Seat;
use App\Models\Branch;
use App\Models\ServiceName;
use App\Models\OwnerAccount;
use App\Models\StaffAccount;
use Illuminate\Http\Request;
use App\Models\ServiceCategory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Owner\SeatNotification;
use App\Notifications\Staff\SeatStaffNotification;
use App\Services\StaffActivityLogger;
use App\Models\StaffActivityLog;

class SeatController extends Controller
{
    // Show seat
    public function showSeat($branch_uuid, $service_category_uuid, $service_name_uuid, Request $request)
    {
        // Logged in as Staff
        $staff = Auth::guard('staff')->user();
        $staffId = $staff->id;
        $branchId = $staff->branch_id;  // Staff is assigned to a specific branch
        $ownerId = $staff->owner_account_id;  // Staff belongs to an owner

        // Get the branch
        $branch = Branch::where('uuid', $branch_uuid)
            ->where('owner_account_id', $ownerId)
            ->firstOrFail();

        // Get the service category
        $serviceCategory = ServiceCategory::where('uuid', $service_category_uuid)
            ->where('owner_account_id', $ownerId)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        // Get the service name
        $serviceName = ServiceName::where('uuid', $service_name_uuid)
            ->where('owner_account_id', $ownerId)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        // Get seat with relationships
        $query = Seat::where('owner_account_id', $ownerId)
            ->where('branch_id', $branch->id)
            ->where('service_category_id', $serviceCategory->id)
            ->where('active', 1);

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q
                    ->where('seat_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('room_no', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Filter by seat status
        if ($request->filled('seat_status') && $request->seat_status !== '') {
            $query->where('seat_status', $request->seat_status);
        }

        // Filter by type
        if ($request->filled('type')) {
            if ($request->type === 'seat') {
                $query->whereNotNull('seat_no');
            } elseif ($request->type === 'room') {
                $query->whereNotNull('room_no');
            }
        }

        $seat = $query->orderBy('date_created', 'desc')->paginate(10);

        // Statistics
        $statsQuery = Seat::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->where('branch_id', $branch->id)
            ->where('service_category_id', $serviceCategory->id);

        $totalSeats = $statsQuery->count();
        $availableSeats = (clone $statsQuery)->where('seat_status', 1)->count();
        $unavailableSeats = (clone $statsQuery)->where('seat_status', 0)->count();
        $rooms = (clone $statsQuery)->whereNotNull('room_no')->count();
        $seats = (clone $statsQuery)->whereNotNull('seat_no')->count();

        $stats = [
            'total_seats' => $totalSeats,
            'available_seats' => $availableSeats,
            'unavailable_seats' => $unavailableSeats,
            'rooms' => $rooms,
            'seats' => $seats,
        ];

        // Return JSON for AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $seat->items(),
                'pagination' => [
                    'current_page' => $seat->currentPage(),
                    'last_page' => $seat->lastPage(),
                    'per_page' => $seat->perPage(),
                    'total' => $seat->total(),
                    'from' => $seat->firstItem(),
                    'to' => $seat->lastItem(),
                ],
                'stats' => $stats,
            ]);
        }

        return view('staff.branch.seat', compact('branch', 'serviceCategory', 'serviceName', 'seat', 'stats'));
    }

    // Update Seat Status
    public function updateSeatStatus(Request $request, $seat_uuid)
    {
        // Get the authenticated staff
        $staff = Auth::guard('staff')->user();

        $validated = $request->validate([
            'seat_status' => 'required|in:0,1',  // 0=unavailable, 1=available
            'service_name_id' => 'required|exists:service_names,id',
        ]);

        $seat = Seat::where('uuid', $seat_uuid)->firstOrFail();
        $serviceName = ServiceName::findOrFail($validated['service_name_id']);
        $serviceCategory = ServiceCategory::findOrFail($seat->serviceCategory->id);
        $branch = Branch::findOrFail($seat->branch->id);

        if ($seat->seat_status == $validated['seat_status']) {
            return back()->with('info', 'No changes detected.');
        }

        $oldStatus = $seat->seat_status;
        $statusLabels = [
            0 => 'Unavailable',
            1 => 'Available',
        ];

        $oldStatusLabel = $statusLabels[$oldStatus];
        $newStatusLabel = $statusLabels[$validated['seat_status']];

        $seat->seat_status = $validated['seat_status'];
        $seat->save();  // Save changes
        
        StaffActivityLogger::log(
    StaffActivityLog::ACTION_UPDATE_SEAT_STATUS,
    "Updated seat status for " . ($seat->seat_no ?? $seat->room_no) . " from {$oldStatusLabel} to {$newStatusLabel}",
            null, // No booking ID for seat actions
            [
                'seat_id' => $seat->id,
                'seat_uuid' => $seat->uuid,
                'seat_no' => $seat->seat_no,
                'room_no' => $seat->room_no,
                'old_status' => $oldStatusLabel,
                'new_status' => $newStatusLabel,
                'branch_name' => $branch->branch_name,
                'service_category' => $serviceCategory->service_category_name,
                'service_name' => $serviceName->service_name,
                'staff_name' => $staff->first_name . ' ' . $staff->last_name
            ],
            $request
        );

        // Send notification for status change
        $actor = Auth::guard('staff')->user();

        // Notify the owner about the status change made by staff
        $owners = OwnerAccount::where('id', $staff->owner_account_id)->get();

        Notification::send($owners, new SeatNotification($branch, $serviceCategory, $serviceName, $seat, $actor, 'status_changed', [
            'old_status' => $oldStatusLabel,
            'new_status' => $newStatusLabel
        ]));

        // Notify all staff who are under the same branch and owner (including current staff)
        $staffs = StaffAccount::where('branch_id', $staff->branch_id)
            ->where('owner_account_id', $staff->owner_account_id)
            ->get();

        Notification::send($staffs, new SeatStaffNotification($branch, $serviceCategory, $serviceName, $seat, $actor, 'status_changed', [
            'old_status' => $oldStatusLabel,
            'new_status' => $newStatusLabel
        ]));

        return redirect()
            ->route('sub_two.seats.showSeat', [
                $branch->uuid,
                $serviceCategory->uuid,
                $serviceName->uuid
            ])
            ->with('toast', [
                'type' => 'success',
                'message' => 'Seat Status updated.'
            ]);
    }
}
