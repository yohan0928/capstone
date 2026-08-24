<?php

namespace App\Http\Controllers\Staff;

use Carbon\Carbon;
use App\Models\Branch;
use App\Models\Ingredient;
use App\Models\OwnerAccount;
use App\Models\StaffAccount;
use App\Services\StaffActivityLogger;
use App\Models\StaffActivityLog;
use Illuminate\Http\Request;
use App\Models\DamagedIngredient;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Owner\IngredientNotification;
use App\Notifications\Staff\IngredientStaffNotification;

class IngredientController extends Controller
{
    // Show Ingredient
    public function showIngredient(Request $request)
    {
        // Logged in as Staff
        $staff = Auth::guard('staff')->user();
        $staffId = $staff->id;
        $branchId = $staff->branch_id;
        $ownerId = $staff->owner_account_id;

        $query = Ingredient::with([
            'owner', 'branch'
        ])
            ->where('active', 1)
            ->where('owner_account_id', $ownerId)
            ->where('branch_id', $branchId);

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q
                    ->where('ingredient_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('ingredient_type', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('ingredient_batch_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhereHas('branch', function ($q) use ($searchTerm) {
                        $q->where('branch_name', 'LIKE', "%{$searchTerm}%");
                    });
            });
        }

        // Filter by ingredient type
        if ($request->filled('ingredient_type')) {
            $query->where('ingredient_type', $request->ingredient_type);
        }

        // Filter by status
        if ($request->filled('ingredient_status') && $request->ingredient_status !== '') {
            $query->where('ingredient_status', $request->ingredient_status);
        }

        // Filter by stock level
        if ($request->filled('stock_level')) {
            switch ($request->stock_level) {
                case 'low':
                    $query->whereRaw('stock_quantity_in <= stock_quantity_threshold');
                    break;
                case 'normal':
                    $query->whereRaw('stock_quantity_in > stock_quantity_threshold');
                    break;
            }
        }

        $ingredients = $query->orderBy('date_created', 'desc')->paginate(10);

        $this->runCleanup();
        $expiredCount = $this->handleExpiredIngredients();

        // Get unique ingredient types for filter dropdown
        $ingredientTypes = Ingredient::where('owner_account_id', $ownerId)
            ->where('branch_id', $branchId)
            ->where('active', 1)
            ->distinct()
            ->pluck('ingredient_type')
            ->filter()
            ->values();

        // Staff can only see their own branch
        $branches = Branch::where('owner_account_id', $ownerId)
            ->where('id', $branchId)
            ->where('active', 1)
            ->get();

        // Statistics
        $statsQuery = Ingredient::where('owner_account_id', $ownerId)
            ->where('branch_id', $branchId)
            ->where('active', 1);

        $totalIngredients = $statsQuery->count();
        $availableIngredients = (clone $statsQuery)->where('ingredient_status', 1)->count();
        $unavailableIngredients = (clone $statsQuery)->where('ingredient_status', 0)->count();
        $lowStockIngredients = (clone $statsQuery)->whereRaw('stock_quantity_in <= stock_quantity_threshold')->count();

        $stats = [
            'total_ingredients' => $totalIngredients,
            'available_ingredients' => $availableIngredients,
            'unavailable_ingredients' => $unavailableIngredients,
            'low_stock_ingredients' => $lowStockIngredients,
        ];

        // Return JSON for AJAX requests
        if ($request->ajax()) {
            $response = [
                'success' => true,
                'data' => $ingredients->items(),
                'pagination' => [
                    'current_page' => $ingredients->currentPage(),
                    'last_page' => $ingredients->lastPage(),
                    'per_page' => $ingredients->perPage(),
                    'total' => $ingredients->total(),
                    'from' => $ingredients->firstItem(),
                    'to' => $ingredients->lastItem(),
                ],
                'stats' => $stats,
                'ingredient_types' => $ingredientTypes,
                'branches' => $branches
            ];

            if ($expiredCount > 0) {
                $response['expired_count'] = $expiredCount;
            }

            return response()->json($response);
        }

        // For regular requests
        $view = view('staff.product.ingredient', compact('ingredients', 'stats', 'ingredientTypes', 'branches'));

        if ($expiredCount > 0) {
            session()->flash('success', $expiredCount . ' ingredient(s) have expired and been deactivated.');
        }

        return $view;
    }

    // Store Ingredient (AJAX)
    public function storeIngredientAjax(Request $request)
{
    $staff = Auth::guard('staff')->user();
    $ownerId = $staff->owner_account_id;
    $staffBranchId = $staff->branch_id;

    $validated = $request->validate([
        'branch_id' => 'required|integer|exists:branches,id|in:' . $staffBranchId,
        'ingredient_img' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        'ingredient_type' => 'required|string|max:255',
        'ingredient_name' => 'required|string|max:255',
        'stock_quantity_in' => 'required|numeric|min:0',
        'unit' => 'required|string|max:255',
        'stock_quantity_threshold' => 'required|numeric|min:0',
        'unit_conversion' => 'nullable|numeric|min:0',
        'converted_stock_quantity_in' => 'nullable|numeric|min:0',
        'converted_unit' => 'nullable|string|max:255',
        'date_expiration' => 'nullable|date',
    ]);

    try {
        // Image upload
        $imagePath = null;
        if ($request->hasFile('ingredient_img')) {
            $imagePath = $request->file('ingredient_img')->store('ingredient_images', 'public');
        }

        $ingredient = Ingredient::create([
            'owner_account_id' => $ownerId,
            'branch_id' => $validated['branch_id'],
            'ingredient_batch_no' => 'IBN' . now()->format('dmYHis'),
            'ingredient_img' => $imagePath,
            'ingredient_type' => $validated['ingredient_type'],
            'ingredient_name' => $validated['ingredient_name'],
            'stock_quantity_in' => $validated['stock_quantity_in'],
            'unit' => $validated['unit'],
            'stock_quantity_threshold' => $validated['stock_quantity_threshold'],
            'unit_conversion' => $validated['unit_conversion'],
            'converted_stock_quantity_in' => $validated['converted_stock_quantity_in'],
            'converted_unit' => $validated['converted_unit'],
            'date_stored' => now(),
            'date_expiration' => $validated['date_expiration'],
            'ingredient_status' => 1,
            'created_by' => $staff->id,
            'created_by_type' => 'staff',
            'date_created' => now(),
            'active' => 1,
        ]);

        // LOG: CREATE INGREDIENT ACTION
        StaffActivityLogger::logCreateIngredient($ingredient, $request);

        return response()->json([
            'success' => true,
            'message' => 'Ingredient created successfully.',
            'ingredient' => $ingredient->load('branch')
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to create ingredient. Please try again.'
        ], 500);
    }
}

    // Update Ingredient (AJAX)
    public function updateIngredientAjax(Request $request, $ingredient_uuid)
{
    try {
        $staff = Auth::guard('staff')->user();
        $staffBranchId = $staff->branch_id;
        $ownerId = $staff->owner_account_id;

        $ingredient = Ingredient::where('uuid', $ingredient_uuid)
            ->where('owner_account_id', $ownerId)
            ->where('branch_id', $staffBranchId)
            ->firstOrFail();

        // Capture old data for logging
        $oldData = $ingredient->getAttributes();

        // Prevent branch change if ingredient is linked to products
        if ($ingredient->products()->count() > 0 && $ingredient->branch_id != $request->branch_id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot change branch: this ingredient is linked to existing products. To use it in a different branch, please create a new ingredient for that branch.'
            ], 422);
        }

        $validated = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id|in:' . $staffBranchId,
            'ingredient_img' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'ingredient_type' => 'required|string|max:255',
            'ingredient_name' => 'required|string|max:255',
            'stock_quantity_in' => 'required|numeric|min:0',
            'unit' => 'required|string|max:255',
            'stock_quantity_threshold' => 'required|numeric|min:0',
            'unit_conversion' => 'nullable|numeric|min:0',
            'converted_stock_quantity_in' => 'nullable|numeric|min:0',
            'converted_unit' => 'nullable|string|max:255',
            'date_expiration' => 'nullable|date',
        ]);

        // Handle image upload
        if ($request->hasFile('ingredient_img')) {
            if ($ingredient->ingredient_img) {
                Storage::disk('public')->delete($ingredient->ingredient_img);
            }
            $ingredient->ingredient_img = $request->file('ingredient_img')->store('ingredient_images', 'public');
        }

        // Assign updated values
        $ingredient->branch_id = $validated['branch_id'];
        $ingredient->ingredient_type = $validated['ingredient_type'];
        $ingredient->ingredient_name = $validated['ingredient_name'];
        $ingredient->stock_quantity_in = $validated['stock_quantity_in'];
        $ingredient->unit = $validated['unit'];
        $ingredient->stock_quantity_threshold = $validated['stock_quantity_threshold'];
        $ingredient->unit_conversion = $validated['unit_conversion'];
        $ingredient->converted_unit = $validated['converted_unit'];
        $ingredient->converted_stock_quantity_in = $validated['converted_stock_quantity_in'];
        $ingredient->date_expiration = $validated['date_expiration'];

        // Track updater info
        if (!is_null($ingredient->updated_by)) {
            $ingredient->last_updated_by = $ingredient->updated_by;
            $ingredient->last_updated_by_type = $ingredient->updated_by_type;
            $ingredient->last_date_updated = $ingredient->date_updated;
        }

        $ingredient->updated_by = $staff->id;
        $ingredient->updated_by_type = 'staff';
        $ingredient->date_updated = now();
        $ingredient->save();

        // LOG: UPDATE INGREDIENT ACTION
        StaffActivityLogger::logUpdateIngredient($ingredient, $oldData, $request);

        return response()->json([
            'success' => true,
            'message' => 'Ingredient updated successfully.',
            'ingredient' => $ingredient->fresh()->load('branch')
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update ingredient. Please try again.'
        ], 500);
    }
}

    // Get ingredient data for edit modal
    public function getIngredientData($ingredient_uuid)
    {
        try {
            $staff = Auth::guard('staff')->user();
            $staffBranchId = $staff->branch_id;
            $ownerId = $staff->owner_account_id;

            $ingredient = Ingredient::where('uuid', $ingredient_uuid)
                ->where('owner_account_id', $ownerId)
                ->where('branch_id', $staffBranchId)
                ->where('active', 1)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'ingredient' => $ingredient
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ingredient not found or you do not have permission to edit it.'
            ], 404);
        }
    }

    // Update Ingredient Status
    public function updateIngredientStatus(Request $request, $ingredient_uuid)
{
    $staff = Auth::guard('staff')->user();
    $staffBranchId = $staff->branch_id;
    $ownerId = $staff->owner_account_id;

    $ingredients = Ingredient::where('uuid', $ingredient_uuid)
        ->where('owner_account_id', $ownerId)
        ->where('branch_id', $staffBranchId)
        ->firstOrFail();

    $validated = $request->validate([
        'ingredient_status' => 'required|in:0,1',
    ]);

    if ($ingredients->ingredient_status == $validated['ingredient_status']) {
        return back()->with('info', 'No changes detected.');
    }

    // Capture old status
    $oldStatus = $ingredients->ingredient_status;

    // Update status
    $ingredients->ingredient_status = $validated['ingredient_status'];

    // Only move the *previous* updater into the "last" fields
    if (!is_null($ingredients->updated_by)) {
        $ingredients->last_updated_by = $ingredients->updated_by;
        $ingredients->last_updated_by_type = $ingredients->updated_by_type;
        $ingredients->last_date_updated = $ingredients->date_updated;
    }

    // Now record the current updater
    $ingredients->updated_by = Auth::guard('staff')->id();
    $ingredients->updated_by_type = 'staff';
    $ingredients->date_updated = now();

    $ingredients->save();

    // LOG: UPDATE INGREDIENT STATUS ACTION
    StaffActivityLogger::logUpdateIngredientStatus(
        $ingredients,
        $oldStatus,
        $validated['ingredient_status'],
        $request
    );

    return redirect()
        ->route('sub_two.ingredients.showIngredient')
        ->with('success', 'Ingredient status updated successfully!');
}

    public function showDeactivatedIngredient()
    {
        $staff = Auth::guard('staff')->user();
        $branchId = $staff->branch_id;
        $ownerId = $staff->owner_account_id;

        $archived_ingredients = Ingredient::with('owner', 'branch')
            ->where('active', 0)
            ->where('owner_account_id', $ownerId)
            ->where('branch_id', $branchId)
            ->paginate(10, ['*'], 'archived_page');

        $expired_ingredients = Ingredient::with('owner', 'branch')
            ->where('owner_account_id', $ownerId)
            ->where('branch_id', $branchId)
            ->whereDate('date_expiration', '<=', now())
            ->paginate(10, ['*'], 'expired_page');

        $damaged_ingredients = DamagedIngredient::with('owner', 'branch', 'ingredient')
            ->where('owner_account_id', $ownerId)
            ->where('branch_id', $branchId)
            ->paginate(10, ['*'], 'damaged_page');

        return view('staff.product.ingredient_crud.delete-ingredient', compact(
            'archived_ingredients',
            'expired_ingredients',
            'damaged_ingredients'
        ));
    }

    // Deactivate Ingredient
    public function deactivateIngredient(Request $request, $ingredient_uuid)
{
    $staff = Auth::guard('staff')->user();
    $staffBranchId = $staff->branch_id;
    $ownerId = $staff->owner_account_id;

    $ingredients = Ingredient::where('uuid', $ingredient_uuid)
        ->where('owner_account_id', $ownerId)
        ->where('branch_id', $staffBranchId)
        ->firstOrFail();

    if ($ingredients->active === 0) {
        return back()->with('info', 'Ingredient is already archived.');
    }

    $stockQuantityOut = $request->input('stock_quantity_out', 0);
    $request->input('reasons', null);

    if ($stockQuantityOut > 0) {
        return $this->damageIngredient($request, $ingredient_uuid);
    }

    $ingredients->ingredient_status = 0;
    $ingredients->active = 0;

    if (!is_null($ingredients->updated_by)) {
        $ingredients->last_updated_by = $ingredients->updated_by;
        $ingredients->last_updated_by_type = $ingredients->updated_by_type;
        $ingredients->last_date_updated = $ingredients->date_updated;
    }

    $ingredients->updated_by = Auth::guard('staff')->id();
    $ingredients->updated_by_type = 'staff';
    $ingredients->date_updated = now();

    $ingredients->save();

    // LOG: DEACTIVATE INGREDIENT ACTION
    StaffActivityLogger::logDeactivateIngredient($ingredients, $request);

    return redirect()
        ->route('sub_two.ingredients.showIngredient')
        ->with('success', 'Ingredient deactivated successfully!');
}

    // Reactivate Ingredient
    public function reactivateIngredient($ingredient_uuid)
{
    $staff = Auth::guard('staff')->user();
    $staffBranchId = $staff->branch_id;
    $ownerId = $staff->owner_account_id;

    $ingredient = Ingredient::where('uuid', $ingredient_uuid)
        ->where('owner_account_id', $ownerId)
        ->where('branch_id', $staffBranchId)
        ->firstOrFail();

    if ($ingredient->active === 1) {
        return back()->with('info', 'Ingredient is already active.');
    }

    $expirationDate = Carbon::parse($ingredient->date_expiration);
    $today = Carbon::today();

    if ($expirationDate->lessThanOrEqualTo($today)) {
        return back()->withErrors([
            'error' => 'Cannot reactivate. This ingredient has already expired or expires today.'
        ]);
    }

    $ingredient->ingredient_status = 1;

    if (!is_null($ingredient->updated_by)) {
        $ingredient->last_updated_by = $ingredient->updated_by;
        $ingredient->last_updated_by_type = $ingredient->updated_by_type;
        $ingredient->last_date_updated = $ingredient->date_updated;
    }

    $ingredient->updated_by = Auth::guard('staff')->id();
    $ingredient->updated_by_type = 'staff';
    $ingredient->date_updated = now();
    $ingredient->active = 1;
    $ingredient->save();

    // LOG: REACTIVATE INGREDIENT ACTION
    StaffActivityLogger::logReactivateIngredient($ingredient);

    return redirect()
        ->route('sub_two.ingredients.showDeactivatedIngredient')
        ->with('success', 'Ingredient reactivated successfully!');
}

    // Expired Ingredient
    private function handleExpiredIngredients()
    {
        $staff = Auth::guard('staff')->user();
        $now = now();

        $expired_ingredients = Ingredient::where('active', 1)
            ->whereNotNull('date_expiration')
            ->where('date_expiration', '<=', $now)
            ->get();

        $expiredCount = 0;

        foreach ($expired_ingredients as $ingredient) {
            $ingredient->active = 0;
            $ingredient->ingredient_status = 0;
            $ingredient->save();

            $expiredCount++;
        }

        return $expiredCount;
    }

    // Damage Ingredient
    public function damageIngredient(Request $request, $ingredient_uuid)
{
    $staff = Auth::guard('staff')->user();
    $staffBranchId = $staff->branch_id;
    $ownerId = $staff->owner_account_id;

    $ingredient = Ingredient::where('uuid', $ingredient_uuid)
        ->where('owner_account_id', $ownerId)
        ->where('branch_id', $staffBranchId)
        ->firstOrFail();

    $quantityOut = $request->input('stock_quantity_out', 0);
    $reason = $request->input('reasons', null);

    if ($quantityOut <= 0) {
        return back()->with('info', 'Quantity out must be greater than 0.');
    }

    // Create damaged record
    DamagedIngredient::create([
        'owner_account_id' => $ingredient->owner_account_id,
        'branch_id' => $ingredient->branch_id,
        'ingredient_id' => $ingredient->id,
        'stock_quantity_out' => $quantityOut,
        'reasons' => $reason,
        'date_damaged' => now(),
        'created_by' => Auth::guard('staff')->id(),
        'created_by_type' => 'staff',
        'date_created' => now(),
        'active' => 0,
    ]);

    $ingredient->stock_quantity_in -= $quantityOut;
    if ($ingredient->stock_quantity_in < 0) {
        $ingredient->stock_quantity_in = 0;
    }

    if (!is_null($ingredient->updated_by)) {
        $ingredient->last_updated_by = $ingredient->updated_by;
        $ingredient->last_updated_by_type = $ingredient->updated_by_type;
        $ingredient->last_date_updated = $ingredient->date_updated;
    }

    $ingredient->updated_by = Auth::guard('staff')->id();
    $ingredient->updated_by_type = 'staff';
    $ingredient->date_updated = now();

    $ingredient->save();

    // LOG: DAMAGE INGREDIENT ACTION
    StaffActivityLogger::logDamageIngredient($ingredient, $quantityOut, $reason, $request);

    return redirect()
        ->route('sub_two.ingredients.showIngredient')
        ->with('success', 'Ingredient has damaged.');
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

            $allFiles = $modelClass::pluck($column)->filter()->toArray();
            $existingFiles = [];

            foreach ($allFiles as $fileEntry) {
                if (empty($fileEntry))
                    continue;

                $decoded = json_decode($fileEntry, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $existingFiles = array_merge($existingFiles, $decoded);
                } else {
                    $existingFiles[] = $fileEntry;
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
            'ingredient_images' => ['model' => Ingredient::class, 'column' => 'ingredient_img'],
        ]);
    }
}