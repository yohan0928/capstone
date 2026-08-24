<?php

namespace App\Http\Controllers\Staff;

use App\Models\Branch;
use App\Models\ServiceName;
use App\Models\OwnerAccount;
use App\Models\StaffAccount;
use Illuminate\Http\Request;
use App\Models\ServiceCategory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Owner\ServiceNameNotification;
use App\Notifications\Staff\ServiceNameStaffNotification;
use App\Services\StaffActivityLogger;
use App\Models\StaffActivityLog;

class ServiceNameController extends Controller
{
    // Show Service Names
    public function showServiceName($branch_uuid, $service_category_uuid, Request $request)
    {
        // Logged in as Staff
        $staff = Auth::guard('staff')->user();
        $staffId = $staff->id;
        $branchId = $staff->branch_id;  // Staff is assigned to a specific branch
        $ownerId = $staff->owner_account_id;  // Staff belongs to an owner

        $branch = Branch::where('uuid', $branch_uuid)
            ->where('owner_account_id', $ownerId)
            ->firstOrFail();

        $serviceCategory = ServiceCategory::where('uuid', $service_category_uuid)
            ->where('owner_account_id', $ownerId)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        $query = ServiceName::where('owner_account_id', $ownerId)
            ->where('branch_id', $branch->id)
            ->where('service_category_id', $serviceCategory->id)
            ->where('active', 1);

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q
                    ->where('service_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('space_type', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('time_duration', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Filter by service name status
        if ($request->filled('service_name_status') && $request->service_name_status !== '') {
            $query->where('service_name_status', $request->service_name_status);
        }

        // Filter by discount status (NEW)
        if ($request->filled('discount_status') && $request->discount_status !== '') {
            if ($request->discount_status === 'discounted') {
                $query->whereNotNull('discount')->where('discount', '>', 0);
            } elseif ($request->discount_status === 'not_discounted') {
                $query->where(function ($q) {
                    $q->whereNull('discount')->orWhere('discount', '=', 0);
                });
            }
        }

        $serviceName = $query->orderBy('date_created', 'desc')->paginate(10);

        // Statistics
        $statsQuery = ServiceName::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->where('branch_id', $branch->id)
            ->where('service_category_id', $serviceCategory->id);

        $totalServiceNames = $statsQuery->count();
        $availableServiceNames = (clone $statsQuery)->where('service_name_status', 1)->count();
        $unavailableServiceNames = (clone $statsQuery)->where('service_name_status', 0)->count();
        
        // Discount statistics (NEW)
        $discountedServices = (clone $statsQuery)->whereNotNull('discount')->where('discount', '>', 0)->count();
        $notDiscountedServices = (clone $statsQuery)->where(function ($q) {
            $q->whereNull('discount')->orWhere('discount', '=', 0);
        })->count();

        $stats = [
            'total_service_names' => $totalServiceNames,
            'available_service_names' => $availableServiceNames,
            'unavailable_service_names' => $unavailableServiceNames,
            'discounted_services' => $discountedServices, // NEW
            'not_discounted_services' => $notDiscountedServices, // NEW
        ];

        // Calculate total savings from discounts (NEW)
        $totalSavings = ServiceName::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->where('branch_id', $branch->id)
            ->where('service_category_id', $serviceCategory->id)
            ->whereNotNull('discount')
            ->where('discount', '>', 0)
            ->get()
            ->sum(function ($service) {
                $oldPrice = $service->old_price ?: $service->price;
                return $oldPrice - $service->price;
            });

        $stats['total_savings'] = $totalSavings; // NEW

        // Return JSON for AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $serviceName->items(),
                'pagination' => [
                    'current_page' => $serviceName->currentPage(),
                    'last_page' => $serviceName->lastPage(),
                    'per_page' => $serviceName->perPage(),
                    'total' => $serviceName->total(),
                    'from' => $serviceName->firstItem(),
                    'to' => $serviceName->lastItem(),
                ],
                'stats' => $stats,
            ]);
        }

        return view('staff.branch.service_name', compact('branch', 'serviceCategory', 'serviceName', 'stats'));
    }

    // Update Service Name Status
    public function updateServiceNameStatus(Request $request, $service_name_uuid)
    {
        // Get the authenticated staff
        $staff = Auth::guard('staff')->user();

        $validated = $request->validate([
            'service_name_status' => 'required|in:0,1',  // 0=unavailable, 1=available
        ]);

        $serviceName = ServiceName::where('uuid', $service_name_uuid)->firstOrFail();
        $serviceCategory = ServiceCategory::findOrFail($serviceName->service_category_id);
        $branch = Branch::findOrFail($serviceName->branch_id);

        if ($serviceName->service_name_status == $validated['service_name_status']) {
            return back()->with('info', 'No changes detected.');
        }

        $oldStatus = $serviceName->service_name_status;
        $statusLabels = [
            0 => 'Unavailable',
            1 => 'Available',
        ];

        $oldStatusLabel = $statusLabels[$oldStatus];
        $newStatusLabel = $statusLabels[$validated['service_name_status']];

        // Update status
        $serviceName->service_name_status = $validated['service_name_status'];
        $serviceName->save();
        
        StaffActivityLogger::log(
            StaffActivityLog::ACTION_UPDATE_SERVICE_NAME_STATUS,
            "Updated service name status for {$serviceName->service_name} from {$oldStatusLabel} to {$newStatusLabel}",
            null,
            [
                'service_name_id' => $serviceName->id,
                'service_name' => $serviceName->service_name,
                'service_category' => $serviceCategory->service_category,
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

        Notification::send($owners, new ServiceNameNotification($branch, $serviceCategory, $serviceName, $actor, 'status_changed', [
            'old_status' => $oldStatusLabel,
            'new_status' => $newStatusLabel
        ]));

        // Notify all staff who are under the same branch and owner (including current staff)
        $staffs = StaffAccount::where('branch_id', $staff->branch_id)
            ->where('owner_account_id', $staff->owner_account_id)
            ->get();

        Notification::send($staffs, new ServiceNameStaffNotification($branch, $serviceCategory, $serviceName, $actor, 'status_changed', [
            'old_status' => $oldStatusLabel,
            'new_status' => $newStatusLabel
        ]));

        return redirect()
            ->route('sub_two.service_names.showServiceName', [
                $branch->uuid,
                $serviceCategory->uuid
            ])
            ->with('toast', [
                'type' => 'success',
                'message' => 'Service Name Status updated.'
            ]);
    }
}