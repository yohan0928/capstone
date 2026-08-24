<?php

namespace App\Http\Controllers\Owner;

use Carbon\Carbon;
use App\Models\Branch;
use App\Models\OwnerAccount;
use App\Models\StaffAccount;
use Illuminate\Http\Request;
use App\Models\ServiceCategory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Owner\ServiceCategoryNotification;
use App\Notifications\Staff\ServiceCategoryStaffNotification;

class ServiceCategoryController extends Controller
{
    // Show Service Categories
    public function showServiceCategory($branch_uuid, Request $request)
    {
        // Logged in as Owner
        $owner = Auth::guard('owner')->user();

        $branch = Branch::where('uuid', $branch_uuid)
            ->where('owner_account_id', $owner->id)
            ->firstOrFail();

        $query = ServiceCategory::where('owner_account_id', $owner->id)
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

        $this->runCleanup();

        // Statistics
        $statsQuery = ServiceCategory::where('owner_account_id', $owner->id)
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

        return view('owner.branch.service_category', compact('serviceCategory', 'branch', 'stats'));
    }

    // Store Service Category Details
    public function storeServiceCategory(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'service_img.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'service_category' => 'required|string|max:255',
        ]);

        $branch = Branch::findOrFail($validated['branch_id']);

        $imagePaths = [];

        if ($request->hasFile('service_img')) {
            foreach ($request->file('service_img') as $image) {
                $imagePaths[] = $image->store('service_images', 'public');
            }
        }

        // Create Service Category (store image paths as array directly)
        $serviceCategory = ServiceCategory::create([
            'owner_account_id' => Auth::guard('owner')->id(),
            'branch_id' => $validated['branch_id'],
            'service_img' => $imagePaths,  // store array directly
            'service_category' => $validated['service_category'],
            'service_category_status' => 1,  // 1=available
            'date_created' => now(),
            'active' => 1,
        ]);

        return redirect()
            ->route('sub_one.service_categories.showServiceCategory', $branch->uuid)
            ->with('toast', [
                'type' => 'success',
                'message' => 'Service Category created.'
            ]);
    }

    // Update Service Category Details
    public function updateServiceCategory(Request $request, $service_category_uuid)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'service_category' => 'required|string|max:255',
            'service_img.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'existing_images' => 'sometimes|array',
            'removed_images' => 'sometimes|string',
        ]);

        $serviceCategory = ServiceCategory::where('uuid', $service_category_uuid)->firstOrFail();
        $branch = Branch::findOrFail($validated['branch_id']);

        // Store old values for notification
        $oldServiceCategory = $serviceCategory->service_category;
        $oldServiceImage = $serviceCategory->service_img;

        // Handle image management
        $currentImages = $serviceCategory->service_img ?? [];

        // Remove images that were deleted by user
        if ($request->has('removed_images')) {
            $removedImages = json_decode($request->removed_images, true) ?? [];
            foreach ($removedImages as $removedImage) {
                // Remove from array
                $currentImages = array_filter($currentImages, function ($image) use ($removedImage) {
                    return $image !== $removedImage;
                });

                // Optional: Delete the actual file from storage
                // Storage::disk('public')->delete($removedImage);
            }
            $currentImages = array_values($currentImages);  // Reindex array
        }

        // Add new images
        if ($request->hasFile('service_img')) {
            foreach ($request->file('service_img') as $image) {
                $currentImages[] = $image->store('service_images', 'public');
            }
        }

        // Update the service images
        $serviceCategory->service_img = $currentImages;

        // Update other fields
        $serviceCategory->branch_id = $validated['branch_id'];
        $serviceCategory->service_category = $validated['service_category'];
        $serviceCategory->service_category_status = 1;

        $serviceCategory->save();

        // Send notification about branch update
        $changes = [];
        if ($oldServiceCategory !== $serviceCategory->service_category) {
            $changes[] = "name from '{$oldServiceCategory}' to '{$serviceCategory->service_category}'";
        }
        if ($oldServiceImage !== $serviceCategory->service_img) {
            $changes[] = 'service category image';
        }

        return redirect()
            ->route('sub_one.service_categories.showServiceCategory', $branch->uuid)
            ->with('toast', [
                'type' => 'success',
                'message' => 'Service Category updated.'
            ]);
    }

    // Update Service Category Status
    public function updateServiceCategoryStatus(Request $request, $service_category_uuid)
    {
        $validated = $request->validate([
            'service_category_status' => 'required|in:0,1',
        ]);

        $serviceCategory = ServiceCategory::where('uuid', $service_category_uuid)->firstOrFail();
        $branch = Branch::findOrFail($serviceCategory->branch->id);

        if ($serviceCategory->service_category_status == $validated['service_category_status']) {
            return back()->with('info', 'No changes detected.');
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

        // Send notification for status change
        $actor = Auth::guard('owner')->user();
        // Get specific owner to notify
        $owner = Auth::guard('owner')->user();
        $owners = OwnerAccount::where('id', $owner->id)->get();

        Notification::send($owners, new ServiceCategoryNotification($branch, $serviceCategory, $actor, 'status_changed', [
            'old_status' => $oldStatusLabel,
            'new_status' => $newStatusLabel
        ]));


        // Notify Staff in the same branch
        $staffMembers = StaffAccount::where('branch_id', $serviceCategory->branch_id)
            ->where('owner_account_id', $actor->id)
            ->where('active', 1)
            ->get();

        Notification::send($staffMembers, new ServiceCategoryStaffNotification($branch, $serviceCategory, $actor, 'status_changed', [
            'old_status' => $oldStatusLabel,
            'new_status' => $newStatusLabel
        ]));

        return redirect()
            ->route('sub_one.service_categories.showServiceCategory', $branch->uuid)
            ->with('toast', [
                'type' => 'success',
                'message' => 'Service Category Status updated.'
            ]);
    }

    // Show Deactivated Service Categories
    public function showDeactivatedServiceCategory($branch_uuid, Request $request)
    {
        $owner = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        // Get the branch
        $branch = Branch::where('uuid', $branch_uuid)
            ->where('owner_account_id', $ownerId)
            ->firstOrFail();

        $today = now();

        // Get deactivated service categories (only within 30 days)
        $query = ServiceCategory::with(['owner', 'branch'])
            ->where('active', 0)
            ->where('owner_account_id', $ownerId)
            ->where('branch_id', $branch->id)
            ->where('date_updated', '>=', $today->copy()->subDays(30));

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q
                    ->where('service_category', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Filter by service category status
        if ($request->filled('service_category_status') && $request->service_category_status !== '') {
            $query->where('service_category_status', $request->service_category_status);
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

        $serviceCategories = $query->orderBy('date_updated', 'desc')->paginate(10);

        // Stats
        $totalArchived = (clone $query)->count();
        $archivedAvailable = (clone $query)->where('service_category_status', 1)->count();
        $archivedUnavailable = (clone $query)->where('service_category_status', 0)->count();

        // Average days left (30 days)
        $avgDaysLeft = 30;
        $archivedItems = (clone $query)->get();
        if ($archivedItems->count() > 0) {
            $totalDaysLeft = 0;
            /** @var \App\Models\ServiceCategory $item */
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
            $serviceCategoriesData = $serviceCategories->getCollection()->map(function ($serviceCategory) {
                $archivedDate = $serviceCategory->date_updated ?: $serviceCategory->date_created;
                $archivedDate = Carbon::parse($archivedDate);
                $daysSinceArchived = $archivedDate->diffInDays(now());
                $serviceCategory->days_left = max(0, 30 - $daysSinceArchived);
                return $serviceCategory;
            });

            return response()->json([
                'success' => true,
                'data' => $serviceCategoriesData,
                'pagination' => [
                    'current_page' => $serviceCategories->currentPage(),
                    'last_page' => $serviceCategories->lastPage(),
                    'per_page' => $serviceCategories->perPage(),
                    'total' => $serviceCategories->total(),
                    'from' => $serviceCategories->firstItem(),
                    'to' => $serviceCategories->lastItem(),
                ],
                'stats' => $stats,
                'branch' => $branch,
            ]);
        }

        return view('owner.branch.archives.delete-service-category', compact('branch', 'serviceCategories', 'stats'));
    }

    // Deactivate Service Category
    public function deactivateServiceCategory($service_category_uuid)
    {
        $owner = Auth::guard('owner')->user();

        $serviceCategory = ServiceCategory::where('uuid', $service_category_uuid)
            ->where('owner_account_id', $owner->id)
            ->firstOrFail();

        $branch = Branch::findOrFail($serviceCategory->branch->id);

        if ($serviceCategory->active === 0) {
            return back()->with('info', 'Service Category is already deactivated.');
        }

        $serviceCategory->active = 0;
        $serviceCategory->service_category_status = 0;  // 0=unavailable
        $serviceCategory->date_updated = now();  // Set archive date

        $serviceCategory->save();

        return redirect()
            ->route('sub_one.service_categories.showServiceCategory', $branch->uuid)
            ->with('toast', [
                'type' => 'success',
                'message' => 'Service Category deactivated.'
            ]);
    }

    // Reactivate Service Category
    public function reactivateServiceCategory($service_category_uuid)
    {
        $owner = Auth::guard('owner')->user();

        $serviceCategory = ServiceCategory::where('uuid', $service_category_uuid)
            ->where('owner_account_id', $owner->id)
            ->firstOrFail();

        $branch = Branch::findOrFail($serviceCategory->branch_id);

        if ($serviceCategory->active === 1) {
            return back()->with('info', 'Service Category is already deactivated.');
        }

        $serviceCategory->active = 1;
        $serviceCategory->service_category_status = 1;  // 1=available
        $serviceCategory->date_updated = now();  // Set archive date

        $serviceCategory->save();

        return redirect()
            ->route('sub_one.service_categories.showDeactivatedServiceCategory', $branch->uuid)
            ->with('toast', [
                'type' => 'success',
                'message' => 'Service Category reactivated.'
            ]);
    }

    /**
     * ===========================================================
     * REMOVE FILES THAT ARE NOT EXISTING IN THE DATABASE
     * ===========================================================
     */
    private function cleanOrphanedFilesDynamic(array $folders): array
    {
        $allDeletedFiles = [];

        foreach ($folders as $folder => $config) {
            $modelClass = $config['model'];
            $column = $config['column'];

            $modelInstance = new $modelClass;

            if (!Schema::hasTable($modelInstance->getTable()) || !Schema::hasColumn($modelInstance->getTable(), $column)) {
                continue;
            }

            // Get all entries for the column
            $allFiles = $modelClass::pluck($column)->filter()->toArray();
            $existingFiles = [];

            foreach ($allFiles as $fileEntry) {
                if (empty($fileEntry))
                    continue;

                if (is_array($fileEntry)) {
                    // Already an array, merge directly
                    $existingFiles = array_merge($existingFiles, $fileEntry);
                } else {
                    // In case it's still a JSON string, decode it
                    $decoded = json_decode($fileEntry, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $existingFiles = array_merge($existingFiles, $decoded);
                    } else {
                        $existingFiles[] = $fileEntry;
                    }
                }
            }

            $existingFiles = array_map(fn($f) => ltrim($f, '/'), $existingFiles);
            $storageFiles = array_map(fn($f) => ltrim($f, '/'), Storage::disk('public')->files($folder));

            foreach ($storageFiles as $file) {
                if (!in_array($file, $existingFiles)) {
                    Storage::disk('public')->delete($file);
                    $allDeletedFiles[] = $file;
                }
            }
        }

        return $allDeletedFiles;
    }

    public function runCleanup(): array
    {
        return $this->cleanOrphanedFilesDynamic([
            'service_images' => ['model' => ServiceCategory::class, 'column' => 'service_img'],
        ]);
    }

    // Get Service Category Data for Edit Modal (New Method)
public function getServiceCategoryData($branch_uuid, $service_category_uuid)
{
    try {
        $owner = Auth::guard('owner')->user();
        
        $serviceCategory = ServiceCategory::where('uuid', $service_category_uuid)
            ->where('owner_account_id', $owner->id)
            ->where('branch_id', function($query) use ($branch_uuid, $owner) {
                $query->select('id')
                    ->from('branches')
                    ->where('uuid', $branch_uuid)
                    ->where('owner_account_id', $owner->id)
                    ->limit(1);
            })
            ->where('active', 1)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'service_category' => $serviceCategory
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Service Category not found or you do not have permission to edit it.'
        ], 404);
    }
}

// AJAX Store Service Category for better UX (New Method)
public function storeServiceCategoryAjax(Request $request)
{
    $validated = $request->validate([
        'branch_id' => 'required|exists:branches,id',
        'service_img.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        'service_category' => 'required|string|max:255',
    ]);

    try {
        $owner = Auth::guard('owner')->user();
        $branch = Branch::findOrFail($validated['branch_id']);

        $imagePaths = [];

        if ($request->hasFile('service_img')) {
            foreach ($request->file('service_img') as $image) {
                $imagePaths[] = $image->store('service_images', 'public');
            }
        }

        // Create Service Category
        $serviceCategory = ServiceCategory::create([
            'owner_account_id' => $owner->id,
            'branch_id' => $validated['branch_id'],
            'service_img' => $imagePaths,
            'service_category' => $validated['service_category'],
            'service_category_status' => 1,
            'date_created' => now(),
            'active' => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Service Category created successfully.',
            'service_category' => $serviceCategory
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to create service category. Please try again.'
        ], 500);
    }
}

// AJAX Update Service Category for better UX (New Method)
public function updateServiceCategoryAjax(Request $request, $service_category_uuid)
{
    $validated = $request->validate([
        'branch_id' => 'required|exists:branches,id',
        'service_category' => 'required|string|max:255',
        'service_img.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        'existing_images' => 'sometimes|array',
        'removed_images' => 'sometimes|string',
    ]);

    try {
        $owner = Auth::guard('owner')->user();
        
        $serviceCategory = ServiceCategory::where('uuid', $service_category_uuid)
            ->where('owner_account_id', $owner->id)
            ->firstOrFail();

        $branch = Branch::findOrFail($validated['branch_id']);

        // Handle image management
        $currentImages = $serviceCategory->service_img ?? [];

        // Remove images that were deleted by user
        if ($request->has('removed_images')) {
            $removedImages = json_decode($request->removed_images, true) ?? [];
            foreach ($removedImages as $removedImage) {
                // Remove from array
                $currentImages = array_filter($currentImages, function ($image) use ($removedImage) {
                    return $image !== $removedImage;
                });

                // Delete the actual file from storage
                Storage::disk('public')->delete($removedImage);
            }
            $currentImages = array_values($currentImages);  // Reindex array
        }

        // Add new images
        if ($request->hasFile('service_img')) {
            foreach ($request->file('service_img') as $image) {
                $currentImages[] = $image->store('service_images', 'public');
            }
        }

        // Update the service images
        $serviceCategory->service_img = $currentImages;

        // Update other fields
        $serviceCategory->service_category = $validated['service_category'];
        $serviceCategory->date_updated = now();
        $serviceCategory->save();

        return response()->json([
            'success' => true,
            'message' => 'Service Category updated successfully.',
            'service_category' => $serviceCategory->fresh()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update service category. Please try again.'
        ], 500);
    }
}
}