<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\OwnerAccount;
use App\Models\StaffAccount;
use App\Services\StaffActivityLogger;
use App\Models\StaffActivityLog;
use App\Notifications\Staff\BranchStaffNotification;
use App\Notifications\Owner\BranchNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class BranchController extends Controller
{
    // Show Branches (only branches assigned to this staff)
    public function showBranch(Request $request)
{
    // Logged in as Staff
    $staff = Auth::guard('staff')->user();
    $staffId = $staff->id;
    $branchId = $staff->branch_id;
    $ownerId = $staff->owner_account_id;

    // Get only the branch assigned to this staff
    $query = Branch::with('owner')
        ->where('active', 1)
        ->where('id', $branchId)
        ->where('owner_account_id', $ownerId);

    $branches = $query->orderBy('date_created', 'desc')->paginate(10);

    // Return JSON for AJAX requests (pagination only)
    if ($request->ajax()) {
        return response()->json([
            'success' => true,
            'data' => $branches->items(),
            'pagination' => [
                'current_page' => $branches->currentPage(),
                'last_page' => $branches->lastPage(),
                'per_page' => $branches->perPage(),
                'total' => $branches->total(),
                'from' => $branches->firstItem(),
                'to' => $branches->lastItem(),
            ]
        ]);
    }

    return view('staff.branch.branch', compact('branches'));
}

    // Update Branch Status (only for their assigned branch)
    public function updateBranchStatus(Request $request, $branch_uuid)
{
    $validated = $request->validate([
        'branch_status' => 'required|in:0,1,2',  // 0=Closed, 1=Open, 2=Soon
    ]);

    // Get staff and their assigned branch
    $staff = Auth::guard('staff')->user();
    $branch = Branch::where('uuid', $branch_uuid)
        ->where('id', $staff->branch_id)
        ->where('owner_account_id', $staff->owner_account_id)
        ->firstOrFail();

    // Check if staff is authorized to update this branch
    if ($branch->id !== $staff->branch_id) {
        return back()->with('error', 'Unauthorized to update this branch.');
    }

    if ($branch->branch_status == $validated['branch_status']) {
        return back()->with('info', 'No changes detected.');
    }

    $oldStatus = $branch->branch_status;
    $statusLabels = [
        0 => 'Closed',
        1 => 'Open',
        2 => 'Coming Soon',
    ];

    $oldStatusLabel = $statusLabels[$oldStatus] ?? 'Unknown';
    $newStatusLabel = $statusLabels[$validated['branch_status']] ?? 'Unknown';

    // LOG: UPDATE BRANCH STATUS
    StaffActivityLogger::logUpdateBranchStatus(
        $branch,
        $oldStatusLabel,
        $newStatusLabel,
        $request
    );

    // Update status
    $branch->branch_status = $validated['branch_status'];

    $branch->save();

    // Send notification for status change
    $actor = Auth::guard('staff')->user();

    // Notify the owner about the status change made by staff
    $owners = OwnerAccount::where('id', $staff->owner_account_id)->get();

    Notification::send($owners, new BranchNotification($branch, $actor, 'status_changed', [
        'old_status' => $oldStatusLabel,
        'new_status' => $newStatusLabel
    ]));

    // Notify all staff who are under the same branch and owner
    $staffs = StaffAccount::where('branch_id', $staff->branch_id)
        ->where('owner_account_id', $staff->owner_account_id)
        ->get();

    Notification::send($staffs, new BranchStaffNotification($branch, $actor, 'status_changed', [
        'old_status' => $oldStatusLabel,
        'new_status' => $newStatusLabel
    ]));

    return redirect()
        ->route('sub_two.branches.showBranch')
        ->with('toast', [
            'type' => 'success',
            'message' => 'Branch Status updated.'
        ]);
}
}
