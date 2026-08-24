<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\OwnerAccount;
use App\Models\StaffAccount;
use App\Models\StaffCheckin;
use App\Models\StaffShiftSchedule;
use App\Notifications\Owner\StaffCheckinOneNotification;
use App\Notifications\Staff\StaffCheckinTwoNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Services\StaffActivityLogger;
use App\Models\StaffActivityLog;

class StaffShiftScheduleController extends Controller
{
    // Show Staff's Shift and Checkins
    public function showMyShift(Request $request)
    {
        $staffId = Auth::guard('staff')->id();

        // Get staff data with schedules and checkins
        $staffData = StaffAccount::with([
            'branch',
            'staffSchedules' => function ($query) use ($request) {
                $query->where('active', 1);

                // SIMPLER DATE FILTERING - Check if schedule falls within the selected date range
                if ($request->filled('date_start')) {
                    $query->where('shift_date_end', '>=', $request->date_start);
                }
                if ($request->filled('date_end')) {
                    $query->where('shift_date_start', '<=', $request->date_end);
                }

                // Apply shift status filter
                if ($request->filled('shift_status') && $request->shift_status !== '') {
                    $query->where('staff_shift_schedule_status', $request->shift_status);
                }

                $query
                    ->orderBy('shift_date_start', 'desc')
                    ->orderBy('shift_time_start', 'desc');
            },
            'staffSchedules.checkins' => function ($query) {
                $query
                    ->where('active', 1)
                    ->orderBy('checkin_time', 'desc');
            },
            'staffSchedules.branch'
        ])
            ->where('id', $staffId)
            ->where('active', 1)
            ->firstOrFail();

        // Transform staff data for the view
        $transformedData = [
            'id' => $staffData->id,
            'first_name' => $staffData->first_name,
            'last_name' => $staffData->last_name,
            'email' => $staffData->email,
            'contact_no' => $staffData->contact_no,
            'address' => $staffData->address,
            'account_status' => $staffData->account_status,
            'branch' => $staffData->branch ? [
                'id' => $staffData->branch->id,
                'branch_name' => $staffData->branch->branch_name
            ] : null,
            'staff_schedules' => $staffData->staffSchedules->map(function ($schedule) {
                // Get the latest checkin for this schedule
                $latestCheckin = null;
                if ($schedule->checkins && $schedule->checkins->count() > 0) {
                    $latestCheckin = $schedule->checkins->first();
                }

                return [
                    'id' => $schedule->id,
                    'shift_date_start' => $schedule->shift_date_start,
                    'shift_date_end' => $schedule->shift_date_end,
                    'shift_time_start' => $schedule->shift_time_start,
                    'shift_time_end' => $schedule->shift_time_end,
                    'staff_shift_schedule_status' => $schedule->staff_shift_schedule_status,
                    'branch_id' => $schedule->branch_id,
                    'branch_name' => $schedule->branch ? $schedule->branch->branch_name : 'N/A',
                    'latest_checkin' => $latestCheckin ? [
                        'id' => $latestCheckin->id,
                        'checkin_time' => $latestCheckin->checkin_time,
                        'checkout_time' => $latestCheckin->checkout_time,
                        'time_worked' => $latestCheckin->time_worked,
                        'time_worked_formatted' => $this->formatDuration($latestCheckin->time_worked ?? 0),
                        'checkin_status' => $latestCheckin->checkin_status
                    ] : null
                ];
            })->toArray()
        ];

        // Return JSON for AJAX requests, otherwise return view
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'staff_data' => $transformedData,
                'filters' => [
                    'date_start' => $request->date_start,
                    'date_end' => $request->date_end,
                    'shift_status' => $request->shift_status
                ]
            ]);
        }

        return view('staff.schedules.my_shift_schedule', [
            'staff_data' => $transformedData,
            'filters' => [
                'date_start' => $request->date_start,
                'date_end' => $request->date_end,
                'shift_status' => $request->shift_status
            ]
        ]);
    }

    // Check-in function

    public function checkin(Request $request, $scheduleId)
    {
        $staff = Auth::guard('staff')->user();
        $staffId = $staff->id;
        $appTimezone = 'Asia/Manila';
        $now = Carbon::now($appTimezone);

        // Get the shift schedule
        $schedule = StaffShiftSchedule::where('id', $scheduleId)
            ->where('staff_account_id', $staffId)
            ->where('active', 1)
            ->first();

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Shift schedule not found',
            ], 404);
        }

        // Check if shift is in pending status
        if ($schedule->staff_shift_schedule_status != 2) {
            return response()->json([
                'success' => false,
                'message' => 'This shift cannot be checked into. Status: ' . $this->getShiftStatusText($schedule->staff_shift_schedule_status),
            ], 400);
        }

        // Check if there's already an active check-in for this schedule
        $activeCheckin = StaffCheckin::where('staff_account_id', $staffId)
            ->where('staff_shift_schedule_id', $scheduleId)
            ->where('checkin_status', 1)
            ->where('active', 1)
            ->first();

        if ($activeCheckin) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active check-in for this shift',
            ], 400);
        }

        try {
            // Create new check-in record
            $checkin = new StaffCheckin();
            $checkin->staff_account_id = $staffId;
            $checkin->staff_shift_schedule_id = $scheduleId;
            $checkin->branch_id = $schedule->branch_id;
            $checkin->checkin_time = $now;
            $checkin->checkin_status = 1;
            $checkin->active = 1;
            $checkin->save();

            // Update shift schedule status to on-duty
            $schedule->staff_shift_schedule_status = 1;
            $schedule->save();
            
            StaffActivityLogger::log(
                StaffActivityLog::ACTION_STAFF_CHECKIN, // You'll need to add this constant
                "Checked in for shift at {$now->format('Y-m-d H:i:s')}",
                null,
                [
                    'staff_id' => $staffId,
                    'staff_name' => $staff->first_name . ' ' . $staff->last_name,
                    'shift_date' => $schedule->shift_date_start,
                    'shift_time' => $schedule->shift_time_start . ' - ' . $schedule->shift_time_end,
                    'checkin_time' => $now->format('Y-m-d H:i:s'),
                    'branch_name' => $schedule->branch ? $schedule->branch->branch_name : 'N/A',
                    'schedule_id' => $scheduleId
                ],
                $request
            );

            // Send notification
            $actor = Auth::guard('staff')->user();
            $owners = OwnerAccount::where('id', $staff->owner_account_id)->get();
            $branch = $checkin->branch;

            Notification::send($owners, new StaffCheckinOneNotification($checkin, $branch, $actor, 'checked_in'));

            // Notify Staff in the same branch and under same owner
            $staffMembers = StaffAccount::where('branch_id', $staff->branch_id)
                ->where('owner_account_id', $staff->owner_account_id)
                ->where('active', 1)
                ->get();

            Notification::send($staffMembers, new StaffCheckinTwoNotification($checkin, $branch, $actor, 'checked_in'));

            return response()->json([
                'success' => true,
                'message' => 'Successfully checked in',
                'checkin_id' => $checkin->id,
                'checkin_time' => $checkin->checkin_time->format('Y-m-d H:i:s'),
                'shift_status' => 1
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check in: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Check-out function
    public function checkout(Request $request, $checkinId)
    {
        $staff = Auth::guard('staff')->user();
        $staffId = $staff->id;
        $appTimezone = 'Asia/Manila';
        $now = Carbon::now($appTimezone);

        $checkin = StaffCheckin::with(['staffShiftSchedule'])
            ->where('id', $checkinId)
            ->where('staff_account_id', $staffId)
            ->where('active', 1)
            ->where('checkin_status', 1)
            ->first();

        if (!$checkin) {
            return response()->json([
                'success' => false,
                'message' => 'Check-in record not found or already checked out',
            ], 404);
        }

        try {
            // Calculate time worked
            $checkinTime = Carbon::parse($checkin->checkin_time, $appTimezone);
            $timeWorked = $checkinTime->diffInMinutes($now);

            // Update the checkin
            $checkin->checkout_time = $now;
            $checkin->time_worked = $timeWorked;
            $checkin->checkin_status = 0;
            $checkin->save();

            // Update shift schedule status to completed
            if ($checkin->staffShiftSchedule) {
                $schedule = $checkin->staffShiftSchedule;
                $schedule->staff_shift_schedule_status = 0;
                $schedule->save();
            }
            
            StaffActivityLogger::log(
                StaffActivityLog::ACTION_STAFF_CHECKOUT, // You'll need to add this constant
                "Checked out from shift after {$this->formatDuration($timeWorked)}",
                null,
                [
                    'staff_id' => $staffId,
                    'staff_name' => $staff->first_name . ' ' . $staff->last_name,
                    'checkin_time' => $checkinTime->format('Y-m-d H:i:s'),
                    'checkout_time' => $now->format('Y-m-d H:i:s'),
                    'time_worked_minutes' => $timeWorked,
                    'time_worked_formatted' => $this->formatDuration($timeWorked),
                    'branch_name' => $checkin->branch ? $checkin->branch->branch_name : 'N/A',
                    'checkin_id' => $checkinId,
                    'schedule_id' => $checkin->staff_shift_schedule_id
                ],
                $request
            );

            // Send notification
            $actor = Auth::guard('staff')->user();
            $owners = OwnerAccount::where('id', $staff->owner_account_id)->get();
            $branch = $checkin->branch;

            Notification::send($owners, new StaffCheckinOneNotification($checkin, $branch, $actor, 'checked_out'));

            // Notify Staff in the same branch and under same owner
            $staffMembers = StaffAccount::where('branch_id', $staff->branch_id)
                ->where('owner_account_id', $staff->owner_account_id)
                ->where('active', 1)
                ->get();

            Notification::send($staffMembers, new StaffCheckinTwoNotification($checkin, $branch, $actor, 'checked_out'));

            return response()->json([
                'success' => true,
                'message' => 'Successfully checked out',
                'checkin_status' => $checkin->checkin_status,
                'time_worked' => $checkin->time_worked,
                'time_worked_formatted' => $this->formatDuration($checkin->time_worked),
                'shift_status' => 0
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check out: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format duration in minutes to human readable format
     */
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

    /**
     * Get shift status text
     */
    private function getShiftStatusText($status)
    {
        $statusText = [
            0 => 'Completed',
            1 => 'On-duty',
            2 => 'Pending',
        ];
        return $statusText[$status] ?? 'Unknown';
    }
}
