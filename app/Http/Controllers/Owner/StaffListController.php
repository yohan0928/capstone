<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\OwnerAccount;
use App\Models\StaffAccount;
use App\Models\StaffShiftSchedule;
use App\Notifications\Owner\StaffListOneNotification;
use App\Notifications\Staff\StaffListTwoNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class StaffListController extends Controller
{
    // Show Staff List with Shift Information
    public function showStaffList(Request $request)
    {
        $ownerId = auth()->id();

        $query = StaffAccount::with([
            'branch',
            'staffSchedules' => function ($query) {
                $query
                    ->where('active', 1)
                    ->orderBy('date_created', 'desc')
                    ->with(['checkins' => function ($q) {
                        $q
                            ->where('active', 1)
                            ->orderBy('checkin_time', 'desc')
                            ->limit(1);  // Get only the latest checkin
                    }]);
            }
        ])
            ->where('active', 1)
            ->where('owner_account_id', $ownerId)
            ->where('role', 2);

        // Apply search filter
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q
                    ->where('first_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                    ->orWhereHas('branch', function ($q) use ($searchTerm) {
                        $q->where('branch_name', 'LIKE', "%{$searchTerm}%");
                    });
            });
        }

        // Apply account status filter
        if ($request->filled('account_status')) {
            $query->where('account_status', $request->account_status);
        }

        // Apply branch filter
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Apply shift status filter
        if ($request->filled('shift_status')) {
            if ($request->shift_status === 'with_shift') {
                $query->whereHas('staffSchedules', function ($q) {
                    $q->where('active', 1);
                });
            } elseif ($request->shift_status === 'no_shift') {
                $query->whereDoesntHave('staffSchedules', function ($q) {
                    $q->where('active', 1);
                });
            }
        }

        $staff_accounts = $query->paginate(10);

        // Get all active staff accounts for the dropdown (not paginated)
        $allStaffAccounts = StaffAccount::where('active', 1)
            ->where('owner_account_id', $ownerId)
            ->where('role', 2)
            ->get(['id', 'first_name', 'last_name']);

        // Get branches for shift assignment modal and filtering
        $branches = Branch::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->get();

        // Calculate statistics
        $totalStaff = StaffAccount::where('active', 1)
            ->where('owner_account_id', $ownerId)
            ->where('role', 2)
            ->count();

        $verifiedStaff = StaffAccount::where('active', 1)
            ->where('owner_account_id', $ownerId)
            ->where('role', 2)
            ->where('account_status', 1)
            ->count();

        $suspendedStaff = StaffAccount::where('active', 1)
            ->where('owner_account_id', $ownerId)
            ->where('role', 2)
            ->where('account_status', 0)
            ->count();

        $withShift = StaffAccount::where('active', 1)
            ->where('owner_account_id', $ownerId)
            ->where('role', 2)
            ->whereHas('staffSchedules', function ($q) {
                $q->where('active', 1);
            })
            ->count();

        $stats = [
            'total_staff' => $totalStaff,
            'verified_staff' => $verifiedStaff,
            'suspended_staff' => $suspendedStaff,
            'with_shift' => $withShift,
        ];

        // Transform data for AJAX response
        if ($request->ajax()) {
            $transformedStaff = $staff_accounts->items();

            // Transform each staff item
            $transformedStaff = array_map(function ($staff) {
                return [
                    'id' => $staff->id,
                    'first_name' => $staff->first_name,
                    'last_name' => $staff->last_name,
                    'email' => $staff->email,
                    'contact_no' => $staff->contact_no,
                    'address' => $staff->address,
                    'account_status' => $staff->account_status,
                    'branch' => $staff->branch ? [
                        'id' => $staff->branch->id,
                        'branch_name' => $staff->branch->branch_name
                    ] : null,
                    'staff_schedules' => $staff->staffSchedules->map(function ($schedule) {
                        // Get all checkins for this schedule
                        $checkins = $schedule->checkins->map(function ($checkin) {
                            return [
                                'id' => $checkin->id,
                                'checkin_time' => $checkin->checkin_time,
                                'checkout_time' => $checkin->checkout_time,
                                'time_worked' => $checkin->time_worked,
                                'time_worked_formatted' => $this->formatDuration($checkin->time_worked ?? 0),
                                'checkin_status' => $checkin->checkin_status,
                                'staff_account_id' => $checkin->staff_account_id,
                                'staff_shift_schedule_id' => $checkin->staff_shift_schedule_id
                            ];
                        })->toArray();

                        return [
                            'id' => $schedule->id,
                            'shift_date_start' => $schedule->shift_date_start,
                            'shift_date_end' => $schedule->shift_date_end,
                            'shift_time_start' => $schedule->shift_time_start,
                            'shift_time_end' => $schedule->shift_time_end,
                            'staff_shift_schedule_status' => $schedule->staff_shift_schedule_status,
                            'branch_id' => $schedule->branch_id,
                            'branch_name' => $schedule->branch ? $schedule->branch->branch_name : 'N/A',
                            'checkins' => $checkins,
                        ];
                    })->toArray()
                ];
            }, $transformedStaff);

            return response()->json([
                'success' => true,
                'data' => $transformedStaff,
                'pagination' => [
                    'current_page' => $staff_accounts->currentPage(),
                    'last_page' => $staff_accounts->lastPage(),
                    'per_page' => $staff_accounts->perPage(),
                    'total' => $staff_accounts->total(),
                    'from' => $staff_accounts->firstItem(),
                    'to' => $staff_accounts->lastItem(),
                ],
                'stats' => $stats
            ]);
        }

        return view('owner.staff.staff_list', compact('staff_accounts', 'branches', 'allStaffAccounts', 'stats'));
    }

    private function formatDuration($minutes)
    {
        $totalMinutes = (int) $minutes;

        if ($totalMinutes < 1) {
            return '0 min';
        }

        $hours = (int) floor($totalMinutes / 60);
        $remainingMinutes = $totalMinutes % 60;

        $hourText = "{$hours} hr" . ($hours !== 1 ? 's' : '');
        $minuteText = "{$remainingMinutes} min" . ($remainingMinutes !== 1 ? 's' : '');

        if ($hours > 0 && $remainingMinutes > 0) {
            return "{$hourText} : {$minuteText}";
        } elseif ($hours > 0) {
            return $hourText;
        } else {
            return $minuteText;
        }
    }

    // Show Deactivated Staff List
    public function showDeactivatedStaffList()
    {
        $ownerId = auth()->id();

        $staff_accounts = StaffAccount::with([
            'branch',
            'staffSchedules' => function ($query) {
                $query->where('active', 1)->orderBy('date_created', 'desc');
            }
        ])
            ->where('active', 0)
            ->where('owner_account_id', $ownerId)
            ->where('role', 2)
            ->paginate(10);

        return view('owner.staff.staff_archive', compact('staff_accounts'));
    }

    public function storeStaffAccount(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'contact_no' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'email' => 'required|email|unique:staff_accounts,email',
        ]);

        $generatedPassword = Str::random(12);

        $staffAccount = StaffAccount::create([
            'owner_account_id' => Auth::guard('owner')->id(),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'contact_no' => $validated['contact_no'],
            'address' => $validated['address'],
            'email' => $validated['email'],
            'password' => Hash::make($generatedPassword),
            'role' => 2,
            'regular' => 0,
            'branch_id' => null,  // Explicitly set to null
            'date_joined' => now(),
            'account_status' => 1,
            'two_factor_enabled' => 1,
            'two_factor_enabled_at' => now(),
            'created_by' => Auth::id(),
            'date_created' => now(),
            'active' => 1,
        ]);

        // Send notification for branch update
        $actor = Auth::guard('owner')->user();

        // Pass null for branch since no branch is assigned yet
        $assignedBranch = null;

        // Get the staff account for notification
        $staff = StaffAccount::find($staffAccount->id);

        // Get specific owner to notify
        $owner = Auth::guard('owner')->user();
        $owners = OwnerAccount::where('id', $owner->id)->get();

        // Send notification
        Notification::send($owners, new StaffListOneNotification(
            $assignedBranch,  // This will be null
            $staff,
            $actor,
            'account_created'
        ));

        // Notify Staff in the same branch
        $staffMembers = StaffAccount::where('branch_id', $staffAccount->branch_id)
            ->where('owner_account_id', $actor->id)
            ->where('active', 1)
            ->get();

        // Send notification
        Notification::send($staffMembers, new StaffListTwoNotification(
            $assignedBranch,  // This will be null
            $staff,
            $actor,
            'account_created'
        ));

        return redirect()
            ->route('sub_one.staff.showStaffList')
            ->with('success', 'Staff account created successfully.');
    }

    // Update Staff Account Status
    public function updateStaffAccountStatus(Request $request, $id)
    {
        $staffAccount = StaffAccount::where('id', $id)
            ->where('owner_account_id', Auth::guard('owner')->id())
            ->firstOrFail();

        $validated = $request->validate([
            'account_status' => 'required|integer|in:0,1',
        ]);

        $staffAccount->update([
            'account_status' => $validated['account_status'],
            'date_updated' => now()
        ]);

        // Send notification for branch update
        $actor = Auth::guard('owner')->user();

        // Get the branch only if it exists
        $assignedBranch = $staffAccount->branch_id ? Branch::find($staffAccount->branch_id) : null;

        // Get specific owner to notify
        $owner = Auth::guard('owner')->user();
        $owners = OwnerAccount::where('id', $owner->id)->get();

        // Send notification
        Notification::send($owners, new StaffListOneNotification(
            $assignedBranch,  // This will be null if no branch assigned
            $staffAccount,  // Use the staff account directly
            $actor,
            'account_status_updated'
        ));

        // Notify Staff in the same branch
        $staffMembers = StaffAccount::where('branch_id', $staffAccount->branch_id)
            ->where('owner_account_id', $actor->id)
            ->where('active', 1)
            ->get();

        // Send notification
        Notification::send($staffMembers, new StaffListTwoNotification(
            $assignedBranch,  // This will be null if no branch assigned
            $staffAccount,  // Use the staff account directly
            $actor,
            'account_status_updated'
        ));

        return redirect()
            ->route('sub_one.staff.showStaffList')
            ->with('success', 'Staff account status updated.');
    }

    // Deactivate/Archive Staff Account
    public function deactivateStaffAccount($id)
    {
        $staffAccount = StaffAccount::where('id', $id)
            ->where('owner_account_id', Auth::guard('owner')->id())
            ->firstOrFail();

        $staffAccount->update([
            'active' => 0,
            'date_updated' => now()
        ]);

        // Send notification for branch update
        $actor = Auth::guard('owner')->user();

        // Get the specific branch for this booking
        $assignedBranch = Branch::find($staffAccount->branch_id);

        // Get related models for notification
        $staff = StaffAccount::find($staffAccount->id);

        // Get all owners to notify
        $owners = OwnerAccount::all();

        // Send notification
        Notification::send($owners, new StaffListOneNotification(
            $assignedBranch,
            $staff,
            $actor,
            'account_deactivated'
        ));

        // Notify Staff in the same branch
        $staffMembers = StaffAccount::where('branch_id', $staffAccount->branch_id)
            ->where('owner_account_id', $actor->id)
            ->where('active', 1)
            ->get();

        // Send notification
        Notification::send($staffMembers, new StaffListTwoNotification(
            $assignedBranch,
            $staff,
            $actor,
            'account_deactivated'
        ));

        return redirect()
            ->route('sub_one.staff.showStaffList')
            ->with('success', 'Staff account archived.');
    }

    // Reactivate Staff Account
    public function reactivateStaffAccount($id)
    {
        $staffAccount = StaffAccount::where('id', $id)
            ->where('owner_account_id', Auth::guard('owner')->id())
            ->firstOrFail();

        $staffAccount->update([
            'active' => 1,
            'date_updated' => now()
        ]);

        // Send notification for branch update
        $actor = Auth::guard('owner')->user();

        // Get the specific branch for this booking
        $assignedBranch = Branch::find($staffAccount->branch_id);

        // Get related models for notification
        $staff = StaffAccount::find($staffAccount->id);

        // Get all owners to notify
        $owners = OwnerAccount::all();

        // Send notification
        Notification::send($owners, new StaffListOneNotification(
            $assignedBranch,
            $staff,
            $actor,
            'account_reactivated'
        ));

        // Notify Staff in the same branch
        $staffMembers = StaffAccount::where('branch_id', $staffAccount->branch_id)
            ->where('owner_account_id', $actor->id)
            ->where('active', 1)
            ->get();

        // Send notification
        Notification::send($staffMembers, new StaffListTwoNotification(
            $assignedBranch,
            $staff,
            $actor,
            'account_reactivated'
        ));

        return redirect()
            ->route('sub_one.staff.showDeactivatedStaffList')
            ->with('success', 'Staff account reactivated.');
    }

    // Get schedule data for editing (AJAX)
    public function getScheduleData($id): JsonResponse
    {
        $schedule = StaffShiftSchedule::where('id', $id)
            ->where('owner_account_id', Auth::guard('owner')->id())
            ->firstOrFail();

        return response()->json([
            'branch_id' => $schedule->branch_id,
            'staff_account_id' => $schedule->staff_account_id,
            'shift_date_start' => $schedule->shift_date_start,
            'shift_date_end' => $schedule->shift_date_end,
            'shift_time_start' => $schedule->shift_time_start,
            'shift_time_end' => $schedule->shift_time_end,
        ]);
    }

    // View Staff Shift Schedules
    public function showStaffSchedules($uuid)
    {
        $ownerId = auth()->id();

        $staff = StaffAccount::with([
            'branch',
            'staffSchedules' => function ($query) {
                $query
                    ->where('active', 1)
                    ->orderBy('date_created', 'desc')
                    ->with(['checkins' => function ($q) {
                        $q
                            ->where('active', 1)
                            ->orderBy('checkin_time', 'desc')
                            ->limit(1);
                    }]);
            }
        ])
            ->where('uuid', $uuid)  // Use UUID instead of ID
            ->where('owner_account_id', $ownerId)
            ->where('role', 2)
            ->firstOrFail();

        // Get branches for shift assignment modal
        $branches = Branch::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->get();

        return view('owner.staff.staff_schedules', compact('staff', 'branches'));
    }

    // Delete Staff Shift

    public function deleteStaffShift($id)
    {
        $shift = StaffShiftSchedule::where('id', $id)
            ->where('owner_account_id', Auth::guard('owner')->id())
            ->firstOrFail();

        $shift->update([
            'active' => 0,
            'date_updated' => now()
        ]);

        return redirect()->back()->with('success', 'Shift schedule deleted successfully.');
    }

    // Store Staff Shift Schedule Details
    public function storeStaffShiftSchedule(Request $request)
    {
        // Basic validation without strict time format
        $validated = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'staff_account_id' => 'required|integer|exists:staff_accounts,id',
            'shift_date_start' => 'required|date',
            'shift_date_end' => 'required|date|after_or_equal:shift_date_start',
            'shift_time_start' => 'required',  // Remove the date_format:H:i validation
            'shift_time_end' => 'required',  // Remove the date_format:H:i validation
        ]);

        // Validate and format times using Carbon
        try {
            $startTime = Carbon::parse($validated['shift_time_start'])->format('H:i');
            $endTime = Carbon::parse($validated['shift_time_end'])->format('H:i');

            $validated['shift_time_start'] = $startTime;
            $validated['shift_time_end'] = $endTime;

            if ($startTime === $endTime) {
                return redirect()
                    ->route('sub_one.staff.showStaffList')
                    ->with('error', 'Shift start and end times cannot be the same.');
            }
        } catch (\Exception $e) {
            return redirect()
                ->route('sub_one.staff.showStaffList')
                ->with('error', 'Invalid time format. Please use HH:MM format (e.g., 09:00 or 14:30).');
        }

        // Additional validation for overnight shifts
        if ($validated['shift_time_end'] < $validated['shift_time_start']) {
            // For overnight shifts, ensure end date is exactly one day after start date
            $expectedEndDate = Carbon::parse($validated['shift_date_start'])->addDay()->format('Y-m-d');
            if ($validated['shift_date_end'] !== $expectedEndDate) {
                return redirect()
                    ->route('sub_one.staff.showStaffList')
                    ->with('error', 'For overnight shifts, end date must be exactly one day after start date.');
            }
        } else {
            // For same-day shifts, ensure dates are the same
            if ($validated['shift_date_start'] !== $validated['shift_date_end']) {
                return redirect()
                    ->route('sub_one.staff.showStaffList')
                    ->with('error', 'For same-day shifts, start and end dates must be the same.');
            }
        }

        // Check for overlapping shifts at the SAME BRANCH only
        $newStart = Carbon::parse($validated['shift_date_start'] . ' ' . $validated['shift_time_start']);
        $newEnd = Carbon::parse($validated['shift_date_end'] . ' ' . $validated['shift_time_end']);

        // For overnight shifts, adjust the end datetime
        if ($validated['shift_time_end'] < $validated['shift_time_start']) {
            $newEnd->addDay();
        }

        // Get existing shifts at the SAME BRANCH for this staff
        $existingShifts = StaffShiftSchedule::where('staff_account_id', $validated['staff_account_id'])
            ->where('branch_id', $validated['branch_id'])  // ← Only same branch!
            ->where('active', 1)
            ->get();

        // Check for overlaps
        $hasOverlap = $existingShifts->contains(function ($existingShift) use ($newStart, $newEnd) {
            // Convert existing shift to datetime
            $existingStart = Carbon::parse($existingShift->shift_date_start . ' ' . $existingShift->shift_time_start);
            $existingEnd = Carbon::parse($existingShift->shift_date_end . ' ' . $existingShift->shift_time_end);

            // Adjust for overnight shifts
            if ($existingShift->shift_time_end < $existingShift->shift_time_start) {
                $existingEnd->addDay();
            }

            // Check if shifts overlap
            return $newStart < $existingEnd && $newEnd > $existingStart;
        });

        if ($hasOverlap) {
            return redirect()
                ->route('sub_one.staff.showStaffList')
                ->with('error', 'This staff member already has a shift scheduled at this branch during the selected time period.');
        }

        // 1️⃣ Create the staff shift schedule
        $staff_shift_schedules = StaffShiftSchedule::create([
            'owner_account_id' => Auth::guard('owner')->id(),
            'branch_id' => $validated['branch_id'],
            'staff_account_id' => $validated['staff_account_id'],
            'shift_date_start' => $validated['shift_date_start'],
            'shift_date_end' => $validated['shift_date_end'],
            'shift_time_start' => $validated['shift_time_start'],
            'shift_time_end' => $validated['shift_time_end'],
            'staff_shift_schedule_status' => 2,  // 2=pending
            'date_created' => now(),
            'active' => 1,
        ]);

        // 2️⃣ Update the staff account's branch_id
        StaffAccount::where('id', $staff_shift_schedules->staff_account_id)
            ->update(['branch_id' => $staff_shift_schedules->branch_id]);

        // Send notification for branch update
        $actor = Auth::guard('owner')->user();

        // Get the specific branch for this booking
        $assignedBranch = Branch::find($staff_shift_schedules->branch_id);

        // Get related models for notification
        $staff = StaffAccount::find($staff_shift_schedules->staff_account_id);

        // Get all owners to notify
        $owners = OwnerAccount::all();

        // Send notification
        Notification::send($owners, new StaffListOneNotification(
            $assignedBranch,
            $staff,
            $actor,
            'shift_created'
        ));

        // Notify Staff in the same branch
        $staffMembers = StaffAccount::where('branch_id', $staff_shift_schedules->branch_id)
            ->where('owner_account_id', $actor->id)
            ->where('active', 1)
            ->get();

        // Send notification
        Notification::send($staffMembers, new StaffListTwoNotification(
            $assignedBranch,
            $staff,
            $actor,
            'shift_created'
        ));

        return redirect()
            ->route('sub_one.staff.showStaffList')
            ->with('success', 'Shift schedule saved and staff branch updated.');
    }

    // Update Staff Shift Schedule Details
    public function updateStaffShiftSchedule(Request $request, $id)
    {
        $staff_shift_schedules = StaffShiftSchedule::where('id', $id)
            ->where('owner_account_id', Auth::guard('owner')->id())
            ->firstOrFail();

        // Basic validation without strict time format
        $validated = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'staff_account_id' => 'required|integer|exists:staff_accounts,id',
            'shift_date_start' => 'required|date',
            'shift_date_end' => 'nullable|date',
            'shift_time_start' => 'required',  // Remove date_format:H:i
            'shift_time_end' => 'required',  // Remove date_format:H:i
        ]);

        // Validate and format times using Carbon
        try {
            $startTime = Carbon::parse($validated['shift_time_start'])->format('H:i');
            $endTime = Carbon::parse($validated['shift_time_end'])->format('H:i');

            $validated['shift_time_start'] = $startTime;
            $validated['shift_time_end'] = $endTime;

            if ($startTime === $endTime) {
                return redirect()
                    ->route('sub_one.staff.showStaffList')
                    ->with('error', 'Shift start and end times cannot be the same.');
            }
        } catch (\Exception $e) {
            return redirect()
                ->route('sub_one.staff.showStaffList')
                ->with('error', 'Invalid time format. Please use HH:MM format (e.g., 09:00 or 14:30).');
        }

        // Adjust for overnight shifts
        if ($validated['shift_time_end'] < $validated['shift_time_start']) {
            $validated['shift_date_end'] = Carbon::parse($validated['shift_date_start'])
                ->addDay()
                ->format('Y-m-d');
        } else {
            // if not overnight and end date not provided, default to start date
            $validated['shift_date_end'] = $validated['shift_date_end'] ?? $validated['shift_date_start'];
        }

        // Check for overlapping shifts (excluding current shift)
        $existingShifts = StaffShiftSchedule::where('staff_account_id', $validated['staff_account_id'])
            ->where('branch_id', $validated['branch_id'])
            ->where('active', 1)
            ->where('id', '!=', $id)
            ->get();

        $newStart = Carbon::parse($validated['shift_date_start'] . ' ' . $validated['shift_time_start']);
        $newEnd = Carbon::parse($validated['shift_date_end'] . ' ' . $validated['shift_time_end']);

        // For overnight shifts, adjust the end datetime
        if ($validated['shift_time_end'] < $validated['shift_time_start']) {
            $newEnd->addDay();
        }

        $hasOverlap = $existingShifts->contains(function ($existingShift) use ($newStart, $newEnd) {
            // Convert existing shift to datetime
            $existingStart = Carbon::parse($existingShift->shift_date_start . ' ' . $existingShift->shift_time_start);
            $existingEnd = Carbon::parse($existingShift->shift_date_end . ' ' . $existingShift->shift_time_end);

            // Adjust for overnight shifts
            if ($existingShift->shift_time_end < $existingShift->shift_time_start) {
                $existingEnd->addDay();
            }

            // Check if shifts overlap
            return $newStart < $existingEnd && $newEnd > $existingStart;
        });

        if ($hasOverlap) {
            return redirect()
                ->route('sub_one.staff.showStaffList')
                ->with('error', 'This staff member already has another shift scheduled during the selected time period.');
        }

        $staff_shift_schedules->branch_id = $validated['branch_id'];
        $staff_shift_schedules->staff_account_id = $validated['staff_account_id'];
        $staff_shift_schedules->shift_date_start = $validated['shift_date_start'];
        $staff_shift_schedules->shift_date_end = $validated['shift_date_end'];
        $staff_shift_schedules->shift_time_start = $validated['shift_time_start'];
        $staff_shift_schedules->shift_time_end = $validated['shift_time_end'];

        // Update the staff account's branch_id
        $this->updateStaffBranch($staff_shift_schedules->staff_account_id, $staff_shift_schedules->branch_id);

        $staff_shift_schedules->save();

        // Send notification for branch update
        $actor = Auth::guard('owner')->user();

        // Get the specific branch for this booking
        $assignedBranch = Branch::find($staff_shift_schedules->branch_id);

        // Get related models for notification
        $staff = StaffAccount::find($staff_shift_schedules->staff_account_id);

        // Get all owners to notify
        $owners = OwnerAccount::all();

        // Send notification
        Notification::send($owners, new StaffListOneNotification(
            $assignedBranch,
            $staff,
            $actor,
            'shift_updated'
        ));

        // Notify Staff in the same branch
        $staffMembers = StaffAccount::where('branch_id', $staff_shift_schedules->branch_id)
            ->where('owner_account_id', $actor->id)
            ->where('active', 1)
            ->get();

        // Send notification
        Notification::send($staffMembers, new StaffListTwoNotification(
            $assignedBranch,
            $staff,
            $actor,
            'shift_updated'
        ));

        return redirect()
            ->route('sub_one.staff.showStaffList')
            ->with('success', 'Shift schedule and staff branch updated.');
    }

    private function updateStaffBranch($staffAccountId, $branchId)
    {
        return StaffAccount::where('id', $staffAccountId)
            ->update([
                'branch_id' => $branchId
            ]);
    }
}
