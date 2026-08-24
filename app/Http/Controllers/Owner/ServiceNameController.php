<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\OwnerAccount;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use App\Models\StaffAccount;
use App\Notifications\Owner\ServiceNameNotification;
use App\Notifications\Staff\ServiceNameStaffNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class ServiceNameController extends Controller
{
    // Show Service Names with Discount Info
    public function showServiceName($branch_uuid, $service_category_uuid, Request $request)
    {
        // Logged in as Owner
        $owner = Auth::guard('owner')->user();

        $branch = Branch::where('uuid', $branch_uuid)
            ->where('owner_account_id', $owner->id)
            ->firstOrFail();

        $serviceCategory = ServiceCategory::where('uuid', $service_category_uuid)
            ->where('owner_account_id', $owner->id)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        $query = ServiceName::where('owner_account_id', $owner->id)
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

        // Filter by discount status
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
        $statsQuery = ServiceName::where('owner_account_id', $owner->id)
            ->where('active', 1)
            ->where('branch_id', $branch->id)
            ->where('service_category_id', $serviceCategory->id);

        $totalServiceNames = $statsQuery->count();
        $availableServiceNames = (clone $statsQuery)->where('service_name_status', 1)->count();
        $unavailableServiceNames = (clone $statsQuery)->where('service_name_status', 0)->count();
        
        // Discount statistics
        $discountedServices = (clone $statsQuery)->whereNotNull('discount')->where('discount', '>', 0)->count();
        $notDiscountedServices = (clone $statsQuery)->where(function ($q) {
            $q->whereNull('discount')->orWhere('discount', '=', 0);
        })->count();

        $stats = [
            'total_service_names' => $totalServiceNames,
            'available_service_names' => $availableServiceNames,
            'unavailable_service_names' => $unavailableServiceNames,
            'discounted_services' => $discountedServices,
            'not_discounted_services' => $notDiscountedServices,
        ];

        // Calculate total savings from discounts
        $totalSavings = ServiceName::where('owner_account_id', $owner->id)
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

        $stats['total_savings'] = $totalSavings;

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

        return view('owner.branch.service_name', compact('branch', 'serviceCategory', 'serviceName', 'stats'));
    }

    // Store Service Name Details
    public function storeServiceName(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'service_category_id' => 'required|exists:service_categories,id',
            'service_name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'time_duration' => 'required|string|max:255',
            'space_type' => 'required|string|max:255',
        ]);

        $serviceCategory = ServiceCategory::findOrFail($validated['service_category_id']);
        $branch = Branch::findOrFail($validated['branch_id']);

        // Create Service Name
        $serviceName = ServiceName::create([
            'owner_account_id' => Auth::guard('owner')->id(),
            'branch_id' => $validated['branch_id'],
            'service_category_id' => $validated['service_category_id'],
            'service_name' => $validated['service_name'],
            'price' => $validated['price'],
            'old_price' => $validated['price'], // Set old_price same as price initially
            'time_duration' => $validated['time_duration'],
            'space_type' => $validated['space_type'],
            'service_name_status' => 1,  // 1=available
            'date_created' => now(),
            'active' => 1,
        ]);

        return redirect()
            ->route('sub_one.service_names.showServiceName', [
                $branch->uuid,
                $serviceCategory->uuid
            ])
            ->with('toast', [
                'type' => 'success',
                'message' => 'Service Name created.'
            ]);
    }

    // Update Service Name Details
    public function updateServiceName(Request $request, $service_name_uuid)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'service_category_id' => 'required|exists:service_categories,id',
            'service_name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'time_duration' => 'required|string|max:255',
            'space_type' => 'required|string|max:255',
        ]);

        $serviceName = ServiceName::where('uuid', $service_name_uuid)->firstOrFail();
        $serviceCategory = ServiceCategory::findOrFail($validated['service_category_id']);
        $branch = Branch::findOrFail($validated['branch_id']);

        // Store old values for notification
        $oldServiceName = $serviceName->service_name;
        $oldPrice = $serviceName->price;
        $oldTimeDuration = $serviceName->time_duration;
        $oldSpaceType = $serviceName->space_type;

        // Update other fields
        $serviceName->branch_id = $validated['branch_id'];
        $serviceName->service_category_id = $validated['service_category_id'];
        $serviceName->service_name = $validated['service_name'];
        $serviceName->price = $validated['price'];
        $serviceName->time_duration = $validated['time_duration'];
        $serviceName->space_type = $validated['space_type'];
        $serviceName->service_name_status = 1;  // available

        // If price is changing and service has discount, update old_price
        if ($oldPrice != $validated['price'] && $serviceName->discount) {
            $serviceName->old_price = $validated['price'];
            // Recalculate discounted price
            if ($serviceName->discount_type === 'percentage') {
                $discountAmount = ($serviceName->old_price * $serviceName->discount) / 100;
                $serviceName->price = max($serviceName->old_price - $discountAmount, 0);
            } else {
                $serviceName->price = max($serviceName->old_price - $serviceName->discount, 0);
            }
        }

        $serviceName->save();

        return redirect()
            ->route('sub_one.service_names.showServiceName', [
                $branch->uuid,
                $serviceCategory->uuid
            ])
            ->with('toast', [
                'type' => 'success',
                'message' => 'Service Name updated.'
            ]);
    }

    // Update Service Name Status
    public function updateServiceNameStatus(Request $request, $service_name_uuid)
    {
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

        // Send notification for status change
        $actor = Auth::guard('owner')->user();
        // Get specific owner to notify
        $owner = Auth::guard('owner')->user();
        $owners = OwnerAccount::where('id', $owner->id)->get();

        Notification::send($owners, new ServiceNameNotification($branch, $serviceCategory, $serviceName, $actor, 'status_changed', [
            'old_status' => $oldStatusLabel,
            'new_status' => $newStatusLabel
        ]));

        // Notify Staff in the same branch
        $staffMembers = StaffAccount::where('branch_id', $serviceName->branch_id)
            ->where('owner_account_id', $actor->id)
            ->where('active', 1)
            ->get();

        Notification::send($staffMembers, new ServiceNameStaffNotification($branch, $serviceCategory, $serviceName, $actor, 'status_changed', [
            'old_status' => $oldStatusLabel,
            'new_status' => $newStatusLabel
        ]));

        return redirect()
            ->route('sub_one.service_names.showServiceName', [
                $branch->uuid,
                $serviceCategory->uuid
            ])
            ->with('toast', [
                'type' => 'success',
                'message' => 'Service Name Status updated.'
            ]);
    }

    // Show Deactivated Service Names
    public function showDeactivatedServiceName($branch_uuid, $service_category_uuid, Request $request)
    {
        $owner = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        // Get branch
        $branch = Branch::where('uuid', $branch_uuid)
            ->where('owner_account_id', $ownerId)
            ->firstOrFail();

        // Get service category
        $serviceCategory = ServiceCategory::where('uuid', $service_category_uuid)
            ->where('owner_account_id', $ownerId)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        $today = now();

        // Get deactivated service names (only within 30 days)
        $query = ServiceName::with(['owner', 'branch', 'serviceCategory'])
            ->where('active', 0)
            ->where('owner_account_id', $ownerId)
            ->where('branch_id', $branch->id)
            ->where('service_category_id', $serviceCategory->id)
            ->where('date_updated', '>=', $today->copy()->subDays(30));

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

        // Filter by status
        if ($request->filled('service_name_status') && $request->service_name_status !== '') {
            $query->where('service_name_status', $request->service_name_status);
        }

        // Filter by days left
        if ($request->filled('days_left')) {
            switch ($request->days_left) {
                case 'critical':
                    $query->where('date_updated', '>=', $today->copy()->subDays(10));
                    break;
                case 'warning':
                    $query->whereBetween('date_updated', [
                        $today->copy()->subDays(20),
                        $today->copy()->subDays(11)
                    ]);
                    break;
                case 'normal':
                    $query->whereBetween('date_updated', [
                        $today->copy()->subDays(30),
                        $today->copy()->subDays(21)
                    ]);
                    break;
            }
        }

        $serviceNames = $query->orderBy('date_updated', 'desc')->paginate(10);

        // Stats
        $totalArchived = (clone $query)->count();
        $archivedAvailable = (clone $query)->where('service_name_status', 1)->count();
        $archivedUnavailable = (clone $query)->where('service_name_status', 0)->count();

        // Average days left (30-day retention)
        $avgDaysLeft = 30;
        $archivedItems = (clone $query)->get();
        if ($archivedItems->count() > 0) {
            $totalDaysLeft = 0;
            /** @var \App\Models\ServiceName $item */
            foreach ($archivedItems as $item) {
                $archivedDate = $item->date_updated ?: $item->date_created;
                $archivedDate = Carbon::parse($archivedDate);
                $daysSinceArchived = $archivedDate->diffInDays(now());
                $daysLeft = max(0, 30 - $daysSinceArchived);
                $totalDaysLeft += $daysLeft;
            }
            $avgDaysLeft = round($totalDaysLeft / $archivedItems->count());
        }

        $stats = [
            'total_archived' => $totalArchived,
            'archived_available' => $archivedAvailable,
            'archived_unavailable' => $archivedUnavailable,
            'avg_days_left' => $avgDaysLeft,
        ];

        // AJAX response
        if ($request->ajax()) {
            $serviceNamesData = $serviceNames->getCollection()->map(function ($serviceName) {
                $archivedDate = $serviceName->date_updated ?: $serviceName->date_created;
                $archivedDate = Carbon::parse($archivedDate);
                $daysSinceArchived = $archivedDate->diffInDays(now());
                $serviceName->days_left = max(0, 30 - $daysSinceArchived);
                return $serviceName;
            });

            return response()->json([
                'success' => true,
                'data' => $serviceNamesData,
                'pagination' => [
                    'current_page' => $serviceNames->currentPage(),
                    'last_page' => $serviceNames->lastPage(),
                    'per_page' => $serviceNames->perPage(),
                    'total' => $serviceNames->total(),
                    'from' => $serviceNames->firstItem(),
                    'to' => $serviceNames->lastItem(),
                ],
                'stats' => $stats,
                'branch' => $branch,
                'service_category' => $serviceCategory,
            ]);
        }

        return view('owner.branch.archives.delete-service-name', compact('branch', 'serviceCategory', 'serviceNames', 'stats'));
    }

    // Deactivate Service Name
    public function deactivateServiceName($service_name_uuid)
    {
        $serviceName = ServiceName::where('uuid', $service_name_uuid)->firstOrFail();
        $serviceCategory = ServiceCategory::findOrFail($serviceName->serviceCategory->id);
        $branch = Branch::findOrFail($serviceName->branch->id);

        if ($serviceName->active === 0) {
            return back()->with('info', 'Service Name is already deactivated.');
        }

        $serviceName->active = 0;
        $serviceName->service_name_status = 0;  // 0=unavailable
        $serviceName->date_updated = now();  // Set archive date

        $serviceName->save();

        return redirect()
            ->route('sub_one.service_names.showServiceName', [
                $branch->uuid,
                $serviceCategory->uuid
            ])
            ->with('toast', [
                'type' => 'success',
                'message' => 'Service Name deactivated.'
            ]);
    }

    // Reactivate Service Name
    public function reactivateServiceName($service_name_uuid)
    {
        $serviceName = ServiceName::where('uuid', $service_name_uuid)->firstOrFail();
        $serviceCategory = ServiceCategory::findOrFail($serviceName->serviceCategory->id);
        $branch = Branch::findOrFail($serviceName->branch->id);

        if ($serviceName->active === 1) {
            return back()->with('info', 'Service Name is already active.');
        }

        $serviceName->active = 1;
        $serviceName->service_name_status = 1;  // 1=available
        $serviceName->date_updated = now();  // Set archive date

        $serviceName->save();

        return redirect()
            ->route('sub_one.service_names.showDeactivatedServiceName', [
                $branch->uuid,
                $serviceCategory->uuid
            ])
            ->with('toast', [
                'type' => 'success',
                'message' => 'Service Name reactivated.'
            ]);
    }

    // Get Service Name Data for Edit Modal
    public function getServiceNameData($branch_uuid, $service_category_uuid, $service_name_uuid)
    {
        try {
            $owner = Auth::guard('owner')->user();

            $serviceName = ServiceName::where('uuid', $service_name_uuid)
                ->where('owner_account_id', $owner->id)
                ->where('branch_id', function ($query) use ($branch_uuid, $owner) {
                    $query
                        ->select('id')
                        ->from('branches')
                        ->where('uuid', $branch_uuid)
                        ->where('owner_account_id', $owner->id)
                        ->limit(1);
                })
                ->where('service_category_id', function ($query) use ($service_category_uuid, $owner) {
                    $query
                        ->select('id')
                        ->from('service_categories')
                        ->where('uuid', $service_category_uuid)
                        ->where('owner_account_id', $owner->id)
                        ->limit(1);
                })
                ->where('active', 1)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'service_name' => $serviceName
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service Name not found or you do not have permission to edit it.'
            ], 404);
        }
    }

    // AJAX Store Service Name
    public function storeServiceNameAjax(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'service_category_id' => 'required|exists:service_categories,id',
            'service_name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'time_duration' => 'required|string|max:255',
            'space_type' => 'required|string|max:255',
        ]);

        try {
            $owner = Auth::guard('owner')->user();
            $serviceCategory = ServiceCategory::findOrFail($validated['service_category_id']);
            $branch = Branch::findOrFail($validated['branch_id']);

            // Create Service Name
            $serviceName = ServiceName::create([
                'owner_account_id' => $owner->id,
                'branch_id' => $validated['branch_id'],
                'service_category_id' => $validated['service_category_id'],
                'service_name' => $validated['service_name'],
                'price' => $validated['price'],
                'old_price' => $validated['price'], // Set old_price same as price initially
                'time_duration' => $validated['time_duration'],
                'space_type' => $validated['space_type'],
                'service_name_status' => 1,
                'date_created' => now(),
                'active' => 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Service Name created successfully.',
                'service_name' => $serviceName
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create service name. Please try again.'
            ], 500);
        }
    }

    // AJAX Update Service Name
    public function updateServiceNameAjax(Request $request, $service_name_uuid)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'service_category_id' => 'required|exists:service_categories,id',
            'service_name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'time_duration' => 'required|string|max:255',
            'space_type' => 'required|string|max:255',
        ]);

        try {
            $owner = Auth::guard('owner')->user();

            $serviceName = ServiceName::where('uuid', $service_name_uuid)
                ->where('owner_account_id', $owner->id)
                ->firstOrFail();

            $serviceCategory = ServiceCategory::findOrFail($validated['service_category_id']);
            $branch = Branch::findOrFail($validated['branch_id']);

            // Store old price before update
            $oldPrice = $serviceName->price;

            // Update fields
            $serviceName->service_name = $validated['service_name'];
            $serviceName->price = $validated['price'];
            $serviceName->time_duration = $validated['time_duration'];
            $serviceName->space_type = $validated['space_type'];
            $serviceName->date_updated = now();

            // If price is changing and service has discount, update old_price
            if ($oldPrice != $validated['price'] && $serviceName->discount) {
                $serviceName->old_price = $validated['price'];
                // Recalculate discounted price
                if ($serviceName->discount_type === 'percentage') {
                    $discountAmount = ($serviceName->old_price * $serviceName->discount) / 100;
                    $serviceName->price = max($serviceName->old_price - $discountAmount, 0);
                } else {
                    $serviceName->price = max($serviceName->old_price - $serviceName->discount, 0);
                }
            }

            $serviceName->save();

            return response()->json([
                'success' => true,
                'message' => 'Service Name updated successfully.',
                'service_name' => $serviceName->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update service name. Please try again.'
            ], 500);
        }
    }

    // Get discount data for this branch (for discount button)
    public function getBranchDiscountData($branch_uuid)
    {
        try {
            $owner = Auth::guard('owner')->user();
            
            $branch = Branch::where('uuid', $branch_uuid)
                ->where('owner_account_id', $owner->id)
                ->where('active', 1)
                ->first();

            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch not found or you do not have permission.'
                ], 404);
            }

            // Get all service categories with their services under this branch
            $categories = ServiceCategory::with(['serviceNames' => function($query) use ($branch) {
                    $query->where('branch_id', $branch->id)
                          ->where('active', 1)
                          ->select('id', 'service_name', 'price', 'old_price', 'discount', 'discount_type', 'service_category_id');
                }])
                ->where('branch_id', $branch->id)
                ->where('active', 1)
                ->get()
                ->map(function ($category) {
                    if (empty($category->category_name) && !empty($category->name)) {
                        $category->category_name = $category->name;
                    }
                    return $category;
                });

            return response()->json([
                'success' => true,
                'branch' => [
                    'uuid' => $branch->uuid,
                    'branch_name' => $branch->branch_name,
                    'id' => $branch->id
                ],
                'categories' => $categories,
                'debug' => [
                    'branch_id' => $branch->id,
                    'categories_count' => $categories->count()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getBranchDiscountData: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Apply discount to selected services in this branch
    public function applyDiscount(Request $request, $branch_uuid)
    {
        try {
            $validated = $request->validate([
                'discount_type' => 'required|in:amount,percentage',
                'discount_value' => 'required|numeric|min:0',
                'selected_services' => 'required|array',
                'selected_services.*' => 'exists:service_names,id'
            ]);

            $owner = Auth::guard('owner')->user();
            
            $branch = Branch::where('uuid', $branch_uuid)
                ->where('owner_account_id', $owner->id)
                ->where('active', 1)
                ->first();

            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch not found or you do not have permission.'
                ], 404);
            }

            // Get all services if "all" is selected
            if (in_array('all', $validated['selected_services'])) {
                $services = ServiceName::where('branch_id', $branch->id)
                    ->where('active', 1)
                    ->get();
            } else {
                $services = ServiceName::whereIn('id', $validated['selected_services'])
                    ->where('branch_id', $branch->id)
                    ->where('active', 1)
                    ->get();
            }

            $updatedCount = 0;
            $errors = [];

            foreach ($services as $service) {
                try {
                    // Save old price if not already saved
                    if (!$service->old_price) {
                        $service->old_price = $service->price;
                    }

                    // Calculate new price based on discount type
                    $discount = $validated['discount_value'];
                    
                    if ($validated['discount_type'] === 'percentage') {
                        $discount = min($discount, 100);
                        $discountAmount = ($service->old_price * $discount) / 100;
                        $newPrice = $service->old_price - $discountAmount;
                    } else {
                        $discountAmount = min($discount, $service->old_price);
                        $newPrice = $service->old_price - $discountAmount;
                    }

                    // Update service
                    $service->update([
                        'price' => max($newPrice, 0),
                        'discount' => $discount,
                        'discount_type' => $validated['discount_type'],
                        'date_updated' => now()
                    ]);

                    $updatedCount++;
                } catch (\Exception $e) {
                    $errorMsg = "Failed to update service '{$service->service_name}': " . $e->getMessage();
                    $errors[] = $errorMsg;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Discount applied to {$updatedCount} services successfully.",
                'updated_count' => $updatedCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in applyDiscount: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to apply discount: ' . $e->getMessage()
            ], 500);
        }
    }

    // Remove discount from selected services
    public function removeDiscount(Request $request, $branch_uuid)
    {
        try {
            $validated = $request->validate([
                'selected_services' => 'required|array',
                'selected_services.*' => 'exists:service_names,id'
            ]);

            $owner = Auth::guard('owner')->user();
            
            $branch = Branch::where('uuid', $branch_uuid)
                ->where('owner_account_id', $owner->id)
                ->where('active', 1)
                ->first();

            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch not found or you do not have permission.'
                ], 404);
            }

            // Get all services if "all" is selected
            if (in_array('all', $validated['selected_services'])) {
                $services = ServiceName::where('branch_id', $branch->id)
                    ->where('active', 1)
                    ->whereNotNull('old_price')
                    ->get();
            } else {
                $services = ServiceName::whereIn('id', $validated['selected_services'])
                    ->where('branch_id', $branch->id)
                    ->where('active', 1)
                    ->whereNotNull('old_price')
                    ->get();
            }

            $updatedCount = 0;

            foreach ($services as $service) {
                // Restore original price
                $service->update([
                    'price' => $service->old_price,
                    'old_price' => null,
                    'discount' => null,
                    'discount_type' => null,
                    'date_updated' => now()
                ]);
                $updatedCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "Discount removed from {$updatedCount} services. Original prices restored.",
                'updated_count' => $updatedCount
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in removeDiscount: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove discount: ' . $e->getMessage()
            ], 500);
        }
    }
}