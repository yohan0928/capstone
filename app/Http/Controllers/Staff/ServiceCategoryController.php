<?php

namespace App\Http\Controllers\Staff;

use App\Models\Branch;
use App\Models\OwnerAccount;
use App\Models\StaffAccount;
use Illuminate\Http\Request;
use App\Models\ServiceCategory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Owner\ServiceCategoryNotification;
use App\Notifications\Staff\ServiceCategoryStaffNotification;
use App\Services\StaffActivityLogger;
use App\Models\StaffActivityLog;

class ServiceCategoryController extends Controller
{
    // Show Service Categories
    public function showServiceCategory($branch_uuid, Request $request)
    {
        // Logged in as Staff
        $staff = Auth::guard('staff')->user();
        $staffId = $staff->id;
        $ownerId = $staff->owner_account_id;  // Staff belongs to an owner

        // Get only the branch assigned to this staff
        $branch = Branch::where('uuid', $branch_uuid)
            ->where('owner_account_id', $ownerId)
            ->firstOrFail();

        $query = ServiceCategory::where('owner_account_id', $ownerId)
            ->where('branch_id', $branch->id)
            ->where('active', 1);

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('service_category', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Filter by service category status
        if ($request->filled('service_category_status') && $request->service_category_status !== '') {
            $query->where('service_category_status', $request->service_category_status);
        }

        $serviceCategory = $query->orderBy('date_created', 'desc')->paginate(10);

        // Statistics
        $statsQuery = ServiceCategory::where('owner_account_id', $ownerId)
            ->where('branch_id', $branch->id)
            ->where('active', 1);

        $totalCategories = $statsQuery->count();
        $availableCategories = (clone $statsQuery)->where('service_category_status', 1)->count();
        $unavailableCategories = (clone $statsQuery)->where('service_category_status', 0)->count();

        $stats = [
            'total_categories' => $totalCategories,
            'available_categories' => $availableCategories,
            'unavailable_categories' => $unavailableCategories,
        ];

        // Return JSON for AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $serviceCategory->items(),
                'pagination' => [
                    'current_page' => $serviceCategory->currentPage(),
                    'last_page' => $serviceCategory->lastPage(),
                    'per_page' => $serviceCategory->perPage(),
                    'total' => $serviceCategory->total(),
                    'from' => $serviceCategory->firstItem(),
                    'to' => $serviceCategory->lastItem(),
                ],
                'stats' => $stats,
            ]);
        }

        return view('staff.branch.service_category', compact('serviceCategory', 'branch', 'stats'));
    }

    // Update Service Category Status

    public function updateServiceCategoryStatus(Request $request, $service_category_uuid)
    {
         // Get the authenticated staff
        $staff = Auth::guard('staff')->user();

        $validated = $request->validate([
            'service_category_status' => 'required|in:0,1',
        ]);

        $serviceCategory = ServiceCategory::where('uuid', $service_category_uuid)->firstOrFail();
        $branch = Branch::findOrFail($serviceCategory->branch->id);

        if ($serviceCategory->service_category_status == $validated['service_category_status']) {
            return back()->with('info', 'No changes detected.');
        }

        // Check if staff is authorized to update this branch
        if ($branch->id !== $staff->branch_id) {
            return back()->with('error', 'Unauthorized to update this branch.');
        }

        $oldStatus = $serviceCategory->service_category_status;
        $statusLabels = [
            0 => 'Unavailable',
            1 => 'Available',
        ];

        $oldStatusLabel = $statusLabels[$oldStatus];
        $newStatusLabel = $statusLabels[$validated['service_category_status']];

        // Update status
        $serviceCategory->service_category_status = $validated['service_category_status'];
        $serviceCategory->save();
        
        StaffActivityLogger::log(
            StaffActivityLog::ACTION_UPDATE_SERVICE_CATEGORY_STATUS, // You'll need to add this constant
            "Updated service category status for {$serviceCategory->service_category} from {$oldStatusLabel} to {$newStatusLabel}",
            null,
            [
                'service_category_id' => $serviceCategory->id,
                'service_category_name' => $serviceCategory->service_category,
                'old_status' => $oldStatusLabel,
                'new_status' => $newStatusLabel,
                'branch_name' => $branch->branch_name,
                'staff_name' => $staff->first_name . ' ' . $staff->last_name
            ],
            $request
        );

        // Send notification for status change
        $actor = Auth::guard('staff')->user();

        // Notify the owner about the status change made by staff
        $owners = OwnerAccount::where('id', $staff->owner_account_id)->get();

        Notification::send($owners, new ServiceCategoryNotification($branch, $serviceCategory, $actor, 'status_changed', [
            'old_status' => $oldStatusLabel,
            'new_status' => $newStatusLabel
        ]));

        // Notify all staff who are under the same branch and owner (including current staff)
        $staffs = StaffAccount::where('branch_id', $staff->branch_id)
            ->where('owner_account_id', $staff->owner_account_id)
            ->get();

        Notification::send($staffs, new ServiceCategoryStaffNotification($branch, $serviceCategory, $actor, 'status_changed', [
            'old_status' => $oldStatusLabel,
            'new_status' => $newStatusLabel
        ]));

        return redirect()
            ->route('sub_two.service_categories.showServiceCategory', $branch->uuid)
            ->with('toast', [
                'type' => 'success',
                'message' => 'Service Category Status updated.'
            ]);
    }
}
