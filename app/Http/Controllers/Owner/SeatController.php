<?php

namespace App\Http\Controllers\Owner;

use Carbon\Carbon;
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

class SeatController extends Controller
{
    // Show seat
    public function showSeat($branch_uuid, $service_category_uuid, $service_name_uuid, Request $request)
    {
        // Logged in as Owner
        $owner = Auth::guard('owner')->user();

        // Get the branch
        $branch = Branch::where('uuid', $branch_uuid)
            ->where('owner_account_id', $owner->id)
            ->firstOrFail();

        // Get the service category
        $serviceCategory = ServiceCategory::where('uuid', $service_category_uuid)
            ->where('owner_account_id', $owner->id)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        // Get the service name
        $serviceName = ServiceName::where('uuid', $service_name_uuid)
            ->where('owner_account_id', $owner->id)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        // Get seat with relationships
        $query = Seat::where('owner_account_id', $owner->id)
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
        $statsQuery = Seat::where('owner_account_id', $owner->id)
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

        return view('owner.branch.seat', compact('branch', 'serviceCategory', 'serviceName', 'seat', 'stats'));
    }

    // Store Seat Details
    public function storeSeat(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'service_category_id' => 'required|exists:service_categories,id',
            'service_name_id' => 'required|exists:service_names,id',
            'seat_no' => 'nullable|string|max:255',
            'room_no' => 'nullable|string|max:255',
        ]);

        $serviceName = ServiceName::findOrFail($validated['service_name_id']);
        $serviceCategory = ServiceCategory::findOrFail($validated['service_category_id']);
        $branch = Branch::findOrFail($validated['branch_id']);

        // Validate that only one field is filled
        if (empty($validated['seat_no']) && empty($validated['room_no'])) {
            return back()->withErrors(['seat_no' => 'Please enter either a seat number or room number.'])->withInput();
        }

        if (!empty($validated['seat_no']) && !empty($validated['room_no'])) {
            return back()->withErrors(['seat_no' => 'Please enter either a seat number OR room number, not both.'])->withInput();
        }

        // Create Seat/Room
        Seat::create([
            'owner_account_id' => Auth::guard('owner')->id(),
            'branch_id' => $validated['branch_id'],
            'service_category_id' => $validated['service_category_id'],
            'seat_no' => $validated['seat_no'] ?? null,
            'room_no' => $validated['room_no'] ?? null,
            'seat_status' => 1,  // 1=available
            'date_created' => now(),
            'active' => 1,
        ]);

        return redirect()
            ->route('sub_one.seats.showSeat', [
                $branch->uuid,
                $serviceCategory->uuid,
                $serviceName->uuid
            ]);
    }

    // Update Seat Details
    public function updateSeat(Request $request, $seat_uuid)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'service_category_id' => 'required|exists:service_categories,id',
            'service_name_id' => 'required|exists:service_names,id',  // Keep for redirection
            'seat_no' => 'nullable|string|max:255',
            'room_no' => 'nullable|string|max:255',
        ]);

        $seat = Seat::where('uuid', $seat_uuid)->firstOrFail();
        $serviceName = ServiceName::findOrFail($validated['service_name_id']);
        $serviceCategory = ServiceCategory::findOrFail($validated['service_category_id']);
        $branch = Branch::findOrFail($validated['branch_id']);

        // Store old values for notification
        $oldSeatNo = $seat->seat_no;
        $oldRoomNo = $seat->room_no;

        // Validate that only one field is filled
        if (empty($validated['seat_no']) && empty($validated['room_no'])) {
            return back()->withErrors(['seat_no' => 'Please enter either a seat number or room number.'])->withInput();
        }

        if (!empty($validated['seat_no']) && !empty($validated['room_no'])) {
            return back()->withErrors(['seat_no' => 'Please enter either a seat number OR room number, not both.'])->withInput();
        }

        // Update fields (don't include service_name_id since it doesn't exist in seats table)
        $seat->branch_id = $validated['branch_id'];
        $seat->service_category_id = $validated['service_category_id'];
        $seat->seat_no = $validated['seat_no'] ?? null;
        $seat->room_no = $validated['room_no'] ?? null;
        $seat->seat_status = 1;  // available

        $seat->save();

        // In the updateSeat method, modify the changes tracking:
        $changes = [];

        // Check for seat_no changes
        if ($oldSeatNo !== $seat->seat_no) {
            if ($oldSeatNo && $seat->seat_no) {
                $changes['seat'] = ['from' => $oldSeatNo, 'to' => $seat->seat_no];
            } elseif ($seat->seat_no) {
                $changes['seat'] = ['from' => null, 'to' => $seat->seat_no];
            } elseif ($oldSeatNo) {
                $changes['seat'] = ['from' => $oldSeatNo, 'to' => null];
            }
        }

        // Check for room_no changes
        if ($oldRoomNo !== $seat->room_no) {
            if ($oldRoomNo && $seat->room_no) {
                $changes['room'] = ['from' => $oldRoomNo, 'to' => $seat->room_no];
            } elseif ($seat->room_no) {
                $changes['room'] = ['from' => null, 'to' => $seat->room_no];
            } elseif ($oldRoomNo) {
                $changes['room'] = ['from' => $oldRoomNo, 'to' => null];
            }
        }

        return redirect()
            ->route('sub_one.seats.showSeat', [
                $branch->uuid,
                $serviceCategory->uuid,
                $serviceName->uuid
            ])
            ->with('toast', [
                'type' => 'success',
                'message' => 'Seat updated.'
            ]);
    }

    // Update Seat Status
    public function updateSeatStatus(Request $request, $seat_uuid)
    {
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
        $seat->save();  // ✅ Save changes

        // Send notification for status change
        $actor = Auth::guard('owner')->user();
        // Get specific owner to notify
        $owner = Auth::guard('owner')->user();
        $owners = OwnerAccount::where('id', $owner->id)->get();

        Notification::send($owners, new SeatNotification($branch, $serviceCategory, $serviceName, $seat, $actor, 'status_changed', [
            'old_status' => $oldStatusLabel,
            'new_status' => $newStatusLabel
        ]));

        // Notify Staff in the same branch
        $staffMembers = StaffAccount::where('branch_id', $seat->branch_id)
            ->where('owner_account_id', $actor->id)
            ->where('active', 1)
            ->get();

        Notification::send($staffMembers, new SeatStaffNotification($branch, $serviceCategory, $serviceName, $seat, $actor, 'status_changed', [
            'old_status' => $oldStatusLabel,
            'new_status' => $newStatusLabel
        ]));

        return redirect()
            ->route('sub_one.seats.showSeat', [
                $branch->uuid,
                $serviceCategory->uuid,
                $serviceName->uuid
            ])
            ->with('toast', [
                'type' => 'success',
                'message' => 'Seat Status updated.'
            ]);
    }

    // Show Deactivated Seat
    public function showDeactivatedSeat($branch_uuid, $service_category_uuid, $service_name_uuid, Request $request)
    {
        $owner = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        // 🔹 Get the branch
        $branch = Branch::where('uuid', $branch_uuid)
            ->where('owner_account_id', $ownerId)
            ->firstOrFail();

        // 🔹 Get the service category
        $serviceCategory = ServiceCategory::where('uuid', $service_category_uuid)
            ->where('owner_account_id', $ownerId)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        // 🔹 Get the service name
        $serviceName = ServiceName::where('uuid', $service_name_uuid)
            ->where('owner_account_id', $ownerId)
            ->where('branch_id', $branch->id)
            ->where('service_category_id', $serviceCategory->id)
            ->firstOrFail();

        $today = now();

        // 🔹 Get deactivated seats (only those within the last 30 days)
        $query = Seat::with(['owner', 'branch', 'serviceCategory', 'serviceName'])
            ->where('active', 0)
            ->where('owner_account_id', $ownerId)
            ->where('branch_id', $branch->id)
            ->where('service_category_id', $serviceCategory->id)
            ->where('date_updated', '>=', $today->copy()->subDays(30));  // Hide expired ones

        // 🔍 Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q
                    ->where('seat_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('room_no', 'LIKE', "%{$searchTerm}%");
            });
        }

        // 🪑 Filter by type (seat or room)
        if ($request->filled('type')) {
            if ($request->type === 'seat') {
                $query->whereNotNull('seat_no');
            } elseif ($request->type === 'room') {
                $query->whereNotNull('room_no');
            }
        }

        // 🏷️ Filter by seat status
        if ($request->filled('seat_status') && $request->seat_status !== '') {
            $query->where('seat_status', $request->seat_status);
        }

        // ⏳ Filter by days left category
        if ($request->filled('days_left')) {
            switch ($request->days_left) {
                case 'critical':
                    // Critical: 0–10 days left (archived within last 10 days)
                    $query->where('date_updated', '>=', $today->copy()->subDays(10));
                    break;

                case 'warning':
                    // Warning: 11–20 days left (archived 11–20 days ago)
                    $query->whereBetween('date_updated', [
                        $today->copy()->subDays(20),
                        $today->copy()->subDays(11)
                    ]);
                    break;

                case 'normal':
                    // Normal: 21–30 days left (archived 21–30 days ago)
                    $query->whereBetween('date_updated', [
                        $today->copy()->subDays(30),
                        $today->copy()->subDays(21)
                    ]);
                    break;
            }
        }

        // 🔹 Paginate
        $seats = $query->orderBy('date_updated', 'desc')->paginate(50);

        // 🔹 Calculate stats
        $totalArchived = (clone $query)->count();
        $archivedSeats = (clone $query)->whereNotNull('seat_no')->count();
        $archivedRooms = (clone $query)->whereNotNull('room_no')->count();
        $archivedAvailable = (clone $query)->where('seat_status', 1)->count();
        $archivedUnavailable = (clone $query)->where('seat_status', 0)->count();

        // 🔹 Average days left (30-day retention)
        $avgDaysLeft = 30;
        $archivedSeatsCollection = (clone $query)->get();
        if ($archivedSeatsCollection->count() > 0) {
            $totalDaysLeft = 0;
            /** @var \App\Models\Seat $seat */
            foreach ($archivedSeatsCollection as $seat) {
                $archivedDate = $seat->date_updated ?: $seat->date_created;
                $archivedDate = Carbon::parse($archivedDate);
                $daysSinceArchived = $archivedDate->diffInDays(now());
                $daysLeft = max(0, 30 - $daysSinceArchived);  // 30-day retention
                $totalDaysLeft += $daysLeft;
            }
            $avgDaysLeft = round($totalDaysLeft / $archivedSeatsCollection->count());
        }

        $stats = [
            'total_archived' => $totalArchived,
            'archived_seats' => $archivedSeats,
            'archived_rooms' => $archivedRooms,
            'archived_available' => $archivedAvailable,
            'archived_unavailable' => $archivedUnavailable,
            'avg_days_left' => $avgDaysLeft,
        ];

        // 🔹 AJAX response
        if ($request->ajax()) {
            $seatsData = $seats->getCollection()->map(function ($seat) {
                $archivedDate = $seat->date_updated ?: $seat->date_created;
                $archivedDate = Carbon::parse($archivedDate);
                $daysSinceArchived = $archivedDate->diffInDays(now());
                $seat->days_left = max(0, 30 - $daysSinceArchived);
                return $seat;
            });

            return response()->json([
                'success' => true,
                'data' => $seatsData,
                'pagination' => [
                    'current_page' => $seats->currentPage(),
                    'last_page' => $seats->lastPage(),
                    'per_page' => $seats->perPage(),
                    'total' => $seats->total(),
                    'from' => $seats->firstItem(),
                    'to' => $seats->lastItem(),
                ],
                'stats' => $stats,
                'branch' => $branch,
                'service_category' => $serviceCategory,
                'service_name' => $serviceName,
            ]);
        }

        // 🔹 Blade view
        return view('owner.branch.archives.delete-seat', compact('branch', 'serviceCategory', 'serviceName', 'seats', 'stats'));
    }

    // Deactivate Seat
    public function deactivateSeat(Request $request, $seat_uuid)
    {
        $owner = Auth::guard('owner')->user();

        $seat = Seat::where('uuid', $seat_uuid)->firstOrFail();
        $serviceCategory = ServiceCategory::findOrFail($seat->serviceCategory->id);
        $branch = Branch::findOrFail($seat->branch->id);
        $serviceName = ServiceName::where('owner_account_id', $owner->id)
            ->where('branch_id', $branch->id)
            ->where('service_category_id', $serviceCategory->id)
            ->firstOrFail();

        if ($seat->active === 0) {
            return back()->with('info', 'Seat is already deactivated.');
        }

        $seat->active = 0;
        $seat->seat_status = 0;  // 0=unavailable
        $seat->date_updated = now();  // Set archive date

        $seat->save();

        return redirect()
            ->route('sub_one.seats.showSeat', [
                $branch->uuid,
                $serviceCategory->uuid,
                $serviceName->uuid
            ])
            ->with('toast', [
                'type' => 'success',
                'message' => 'Seat deactivated.'
            ]);
    }

    // Reactivate Seat
    public function reactivateSeat(Request $request, $seat_uuid)
    {
        $owner = Auth::guard('owner')->user();

        $seat = Seat::where('uuid', $seat_uuid)->firstOrFail();
        $serviceCategory = ServiceCategory::findOrFail($seat->serviceCategory->id);
        $branch = Branch::findOrFail($seat->branch->id);
        $serviceName = ServiceName::where('owner_account_id', $owner->id)
            ->where('branch_id', $branch->id)
            ->where('service_category_id', $serviceCategory->id)
            ->firstOrFail();

        if ($seat->active === 1) {
            return back()->with('info', 'Seat is already deactivated.');
        }

        $seat->active = 1;
        $seat->seat_status = 1;  // 1=available
        $seat->date_updated = now();  // Set archive date

        $seat->save();

        return redirect()
            ->route('sub_one.seats.showDeactivatedSeat', [
                $branch->uuid,
                $serviceCategory->uuid,
                $serviceName->uuid
            ])
            ->with('toast', [
                'type' => 'success',
                'message' => 'Seat reactivated.'
            ]);
    }

    // Get Seat Data for Edit Modal
public function getSeatData($branch_uuid, $service_category_uuid, $service_name_uuid, $seat_uuid)
{
    try {
        $owner = Auth::guard('owner')->user();
        
        $seat = Seat::where('uuid', $seat_uuid)
            ->where('owner_account_id', $owner->id)
            ->where('branch_id', function($query) use ($branch_uuid, $owner) {
                $query->select('id')
                    ->from('branches')
                    ->where('uuid', $branch_uuid)
                    ->where('owner_account_id', $owner->id)
                    ->limit(1);
            })
            ->where('service_category_id', function($query) use ($service_category_uuid, $owner) {
                $query->select('id')
                    ->from('service_categories')
                    ->where('uuid', $service_category_uuid)
                    ->where('owner_account_id', $owner->id)
                    ->limit(1);
            })
            ->where('active', 1)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'seat' => $seat
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Seat not found or you do not have permission to edit it.'
        ], 404);
    }
}

// AJAX Store Seat
public function storeSeatAjax(Request $request)
{
    $validated = $request->validate([
        'branch_id' => 'required|exists:branches,id',
        'service_category_id' => 'required|exists:service_categories,id',
        'service_name_id' => 'required|exists:service_names,id',
        'seat_no' => 'nullable|string|max:255',
        'room_no' => 'nullable|string|max:255',
    ]);

    try {
        $owner = Auth::guard('owner')->user();
        $serviceName = ServiceName::findOrFail($validated['service_name_id']);
        $serviceCategory = ServiceCategory::findOrFail($validated['service_category_id']);
        $branch = Branch::findOrFail($validated['branch_id']);

        // Validate that only one field is filled
        if (empty($validated['seat_no']) && empty($validated['room_no'])) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter either a seat number or room number.'
            ], 422);
        }

        if (!empty($validated['seat_no']) && !empty($validated['room_no'])) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter either a seat number OR room number, not both.'
            ], 422);
        }

        // Create Seat/Room
        $seat = Seat::create([
            'owner_account_id' => $owner->id,
            'branch_id' => $validated['branch_id'],
            'service_category_id' => $validated['service_category_id'],
            'seat_no' => $validated['seat_no'] ?? null,
            'room_no' => $validated['room_no'] ?? null,
            'seat_status' => 1,
            'date_created' => now(),
            'active' => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Seat created successfully.',
            'seat' => $seat
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to create seat. Please try again.'
        ], 500);
    }
}

// AJAX Update Seat
public function updateSeatAjax(Request $request, $seat_uuid)
{
    $validated = $request->validate([
        'branch_id' => 'required|exists:branches,id',
        'service_category_id' => 'required|exists:service_categories,id',
        'service_name_id' => 'required|exists:service_names,id',
        'seat_no' => 'nullable|string|max:255',
        'room_no' => 'nullable|string|max:255',
    ]);

    try {
        $owner = Auth::guard('owner')->user();
        
        $seat = Seat::where('uuid', $seat_uuid)
            ->where('owner_account_id', $owner->id)
            ->firstOrFail();

        $serviceName = ServiceName::findOrFail($validated['service_name_id']);
        $serviceCategory = ServiceCategory::findOrFail($validated['service_category_id']);
        $branch = Branch::findOrFail($validated['branch_id']);

        // Validate that only one field is filled
        if (empty($validated['seat_no']) && empty($validated['room_no'])) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter either a seat number or room number.'
            ], 422);
        }

        if (!empty($validated['seat_no']) && !empty($validated['room_no'])) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter either a seat number OR room number, not both.'
            ], 422);
        }

        // Update fields
        $seat->seat_no = $validated['seat_no'] ?? null;
        $seat->room_no = $validated['room_no'] ?? null;
        $seat->date_updated = now();
        $seat->save();

        return response()->json([
            'success' => true,
            'message' => 'Seat updated successfully.',
            'seat' => $seat->fresh()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update seat. Please try again.'
        ], 500);
    }
}
}