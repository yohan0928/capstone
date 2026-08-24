<?php

namespace App\Http\Controllers\Owner;

use Carbon\Carbon;
use App\Models\Branch;
use App\Models\Ingredient;
use App\Models\OwnerAccount;
use App\Models\StaffAccount;
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
    // ─────────────────────────────────────────────────────────────
    // SHOW INGREDIENT LIST
    // ─────────────────────────────────────────────────────────────
    public function showIngredient(Request $request)
    {
        $owner   = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        $query = Ingredient::with(['owner', 'branch'])
            ->where('active', 1)
            ->where('owner_account_id', $ownerId);

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('ingredient_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('ingredient_type', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('ingredient_batch_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhereHas('branch', function ($q) use ($searchTerm) {
                        $q->where('branch_name', 'LIKE', "%{$searchTerm}%");
                    });
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('ingredient_type')) {
            $query->where('ingredient_type', $request->ingredient_type);
        }

        if ($request->filled('ingredient_status') && $request->ingredient_status !== '') {
            $query->where('ingredient_status', $request->ingredient_status);
        }

        // Low-stock filter: based on running stock_quantity_in vs threshold
        if ($request->filled('stock_level')) {
            match ($request->stock_level) {
                'low'    => $query->whereRaw('stock_quantity_in <= stock_quantity_threshold'),
                'normal' => $query->whereRaw('stock_quantity_in > stock_quantity_threshold'),
                default  => null,
            };
        }

        $ingredients = $query->orderBy('date_created', 'desc')->paginate(10);

        $this->runCleanup();
        $expiredCount = $this->handleExpiredIngredients();

        $ingredientTypes = Ingredient::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->distinct()
            ->pluck('ingredient_type')
            ->filter()
            ->values();

        $branches = Branch::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->get();

        // Stats — still uses stock_quantity_in (updated by stock-in transactions)
        $statsQuery            = Ingredient::where('owner_account_id', $ownerId)->where('active', 1);
        $totalIngredients      = $statsQuery->count();
        $availableIngredients  = (clone $statsQuery)->where('ingredient_status', 1)->count();
        $unavailableIngredients = (clone $statsQuery)->where('ingredient_status', 0)->count();
        $lowStockIngredients   = (clone $statsQuery)->whereRaw('stock_quantity_in <= stock_quantity_threshold')->count();

        $stats = [
            'total_ingredients'       => $totalIngredients,
            'available_ingredients'   => $availableIngredients,
            'unavailable_ingredients' => $unavailableIngredients,
            'low_stock_ingredients'   => $lowStockIngredients,
        ];

        if ($request->ajax()) {
            return response()->json([
                'success'          => true,
                'data'             => $ingredients->items(),
                'pagination'       => [
                    'current_page' => $ingredients->currentPage(),
                    'last_page'    => $ingredients->lastPage(),
                    'per_page'     => $ingredients->perPage(),
                    'total'        => $ingredients->total(),
                    'from'         => $ingredients->firstItem(),
                    'to'           => $ingredients->lastItem(),
                ],
                'stats'            => $stats,
                'ingredient_types' => $ingredientTypes,
                'branches'         => $branches,
            ]);
        }

        $view = view('owner.product.ingredient', compact(
            'ingredients', 'stats', 'ingredientTypes', 'branches'
        ));

        if ($expiredCount > 0) {
            session()->flash('success', $expiredCount . ' ingredient(s) have expired and been deactivated.');
        }

        return $view;
    }

    // ─────────────────────────────────────────────────────────────
    // STORE INGREDIENT  (definition only — no stock qty, conversion, or expiration)
    // ─────────────────────────────────────────────────────────────
    public function storeIngredient(Request $request)
    {
        $validated = $request->validate([
            'branch_id'               => 'required|integer|exists:branches,id',
            'ingredient_img'          => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'ingredient_type'         => 'required|string|max:255',
            'ingredient_name'         => 'required|string|max:255',
            'unit'                    => 'required|string|max:255',
            'stock_quantity_threshold'=> 'required|numeric|min:0',
        ]);

        $imagePath = $request->hasFile('ingredient_img')
            ? $request->file('ingredient_img')->store('ingredient_images', 'public')
            : null;

        Ingredient::create([
            'owner_account_id'        => Auth::guard('owner')->id(),
            'branch_id'               => $validated['branch_id'],
            'ingredient_batch_no'     => 'IBN' . now()->format('dmYHis'),
            'ingredient_img'          => $imagePath,
            'ingredient_type'         => $validated['ingredient_type'],
            'ingredient_name'         => $validated['ingredient_name'],
            'stock_quantity_in'       => 0, // starts at zero; built up via stock-in transactions
            'unit'                    => $validated['unit'],
            'stock_quantity_threshold'=> $validated['stock_quantity_threshold'],
            // conversion columns no longer set at definition time
            'unit_conversion'               => null,
            'converted_stock_quantity_in'   => null,
            'converted_unit'                => null,
            'date_stored'             => now(),
            'ingredient_status'       => 1,
            'created_by'              => Auth::guard('owner')->id(),
            'created_by_type'         => 'owner',
            'date_created'            => now(),
            'active'                  => 1,
        ]);

        return redirect()
            ->route('sub_one.ingredients.showIngredient')
            ->with('success', 'Ingredient created successfully!');
    }

    // ─────────────────────────────────────────────────────────────
    // UPDATE INGREDIENT  (definition only — no stock qty, conversion, or expiration)
    // ─────────────────────────────────────────────────────────────
    public function updateIngredient(Request $request, $ingredient_uuid)
    {
        $ingredient = Ingredient::where('uuid', $ingredient_uuid)->firstOrFail();

        $owner    = Auth::guard('owner')->user();
        $ownerId  = $owner->id;

        $branches = Branch::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->get();

        if ($ingredient->products()->count() > 0 && $ingredient->branch_id != $request->branch_id) {
            return redirect()
                ->route('sub_one.ingredients.showEditIngredientForm', ['id' => $ingredient->id])
                ->with([
                    'branches' => $branches,
                    'error'    => 'Cannot change branch: this ingredient is linked to existing products.',
                ]);
        }

        $validated = $request->validate([
            'branch_id'               => 'required|integer|exists:branches,id',
            'ingredient_img'          => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'ingredient_type'         => 'required|string|max:255',
            'ingredient_name'         => 'required|string|max:255',
            'unit'                    => 'required|string|max:255',
            'stock_quantity_threshold'=> 'required|numeric|min:0',
        ]);

        if ($request->hasFile('ingredient_img')) {
            if ($ingredient->ingredient_img) {
                Storage::disk('public')->delete($ingredient->ingredient_img);
            }
            $ingredient->ingredient_img = $request->file('ingredient_img')
                ->store('ingredient_images', 'public');
        }

        $ingredient->branch_id               = $validated['branch_id'];
        $ingredient->ingredient_type         = $validated['ingredient_type'];
        $ingredient->ingredient_name         = $validated['ingredient_name'];
        $ingredient->unit                    = $validated['unit'];
        $ingredient->stock_quantity_threshold= $validated['stock_quantity_threshold'];

        // Preserve audit trail
        if (!is_null($ingredient->updated_by)) {
            $ingredient->last_updated_by      = $ingredient->updated_by;
            $ingredient->last_updated_by_type = $ingredient->updated_by_type;
            $ingredient->last_date_updated    = $ingredient->date_updated;
        }

        $ingredient->updated_by      = Auth::guard('owner')->id();
        $ingredient->updated_by_type = 'owner';
        $ingredient->date_updated    = now();
        $ingredient->save();

        return redirect()
            ->route('sub_one.ingredients.showIngredient')
            ->with('success', 'Ingredient updated successfully!');
    }

    // ─────────────────────────────────────────────────────────────
    // UPDATE STATUS
    // ─────────────────────────────────────────────────────────────
    public function updateIngredientStatus(Request $request, $ingredient_uuid)
    {
        $ingredient = Ingredient::where('uuid', $ingredient_uuid)->firstOrFail();

        $validated = $request->validate([
            'ingredient_status' => 'required|in:0,1',
        ]);

        if ($ingredient->ingredient_status == $validated['ingredient_status']) {
            return back()->with('info', 'No changes detected.');
        }

        $ingredient->ingredient_status = $validated['ingredient_status'];

        if (!is_null($ingredient->updated_by)) {
            $ingredient->last_updated_by      = $ingredient->updated_by;
            $ingredient->last_updated_by_type = $ingredient->updated_by_type;
            $ingredient->last_date_updated    = $ingredient->date_updated;
        }

        $ingredient->updated_by      = Auth::guard('owner')->id();
        $ingredient->updated_by_type = 'owner';
        $ingredient->date_updated    = now();
        $ingredient->save();

        return redirect()
            ->route('sub_one.ingredients.showIngredient')
            ->with('success', 'Ingredient status updated successfully!');
    }

    // ─────────────────────────────────────────────────────────────
    // SHOW ARCHIVE
    // ─────────────────────────────────────────────────────────────
    public function showDeactivatedIngredient()
    {
        $owner   = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        $archived_ingredients = Ingredient::with('owner', 'branch')
            ->where('active', 0)
            ->where('owner_account_id', $ownerId)
            ->paginate(10, ['*'], 'archived_page');

        $expired_ingredients = Ingredient::with('owner', 'branch')
            ->where('owner_account_id', $ownerId)
            ->whereDate('date_expiration', '<=', now())
            ->paginate(10, ['*'], 'expired_page');

        $damaged_ingredients = DamagedIngredient::with('owner', 'branch', 'ingredient')
            ->where('owner_account_id', $ownerId)
            ->paginate(10, ['*'], 'damaged_page');

        return view('owner.product.ingredient_crud.delete-ingredient', compact(
            'archived_ingredients',
            'expired_ingredients',
            'damaged_ingredients'
        ));
    }

    // ─────────────────────────────────────────────────────────────
    // DEACTIVATE / ARCHIVE
    // ─────────────────────────────────────────────────────────────
    public function deactivateIngredient(Request $request, $ingredient_uuid)
    {
        $ingredient = Ingredient::where('uuid', $ingredient_uuid)->firstOrFail();

        if ($ingredient->active === 0) {
            return back()->with('info', 'Ingredient is already archived.');
        }

        $stockQuantityOut = $request->input('stock_quantity_out', 0);

        if ($stockQuantityOut > 0) {
            return $this->damageIngredient($request, $ingredient_uuid);
        }

        $ingredient->ingredient_status = 0;
        $ingredient->active            = 0;

        if (!is_null($ingredient->updated_by)) {
            $ingredient->last_updated_by      = $ingredient->updated_by;
            $ingredient->last_updated_by_type = $ingredient->updated_by_type;
            $ingredient->last_date_updated    = $ingredient->date_updated;
        }

        $ingredient->updated_by      = Auth::guard('owner')->id();
        $ingredient->updated_by_type = 'owner';
        $ingredient->date_updated    = now();
        $ingredient->save();

        return redirect()
            ->route('sub_one.ingredients.showIngredient')
            ->with('success', 'Ingredient archived successfully!');
    }

    // ─────────────────────────────────────────────────────────────
    // REACTIVATE
    // ─────────────────────────────────────────────────────────────
    public function reactivateIngredient($ingredient_uuid)
    {
        $ingredient = Ingredient::where('uuid', $ingredient_uuid)->firstOrFail();

        if ($ingredient->active === 1) {
            return back()->with('info', 'Ingredient is already active.');
        }

        $expirationDate = Carbon::parse($ingredient->date_expiration);
        $today          = Carbon::today();

        if ($expirationDate->lessThanOrEqualTo($today)) {
            return back()->withErrors([
                'error' => 'Cannot reactivate. This ingredient has already expired or expires today.',
            ]);
        }

        $ingredient->ingredient_status = 1;

        if (!is_null($ingredient->updated_by)) {
            $ingredient->last_updated_by      = $ingredient->updated_by;
            $ingredient->last_updated_by_type = $ingredient->updated_by_type;
            $ingredient->last_date_updated    = $ingredient->date_updated;
        }

        $ingredient->updated_by      = Auth::guard('owner')->id();
        $ingredient->updated_by_type = 'owner';
        $ingredient->date_updated    = now();
        $ingredient->active          = 1;
        $ingredient->save();

        return redirect()
            ->route('sub_one.ingredients.showDeactivatedIngredient')
            ->with('success', 'Ingredient reactivated successfully!');
    }

    // ─────────────────────────────────────────────────────────────
    // DAMAGE
    // ─────────────────────────────────────────────────────────────
    public function damageIngredient(Request $request, $ingredient_uuid)
    {
        $ingredient  = Ingredient::where('uuid', $ingredient_uuid)->firstOrFail();
        $quantityOut = $request->input('stock_quantity_out', 0);
        $reason      = $request->input('reasons', null);

        if ($quantityOut <= 0) {
            return back()->with('info', 'Quantity out must be greater than 0.');
        }

        DamagedIngredient::create([
            'owner_account_id' => $ingredient->owner_account_id,
            'branch_id'        => $ingredient->branch_id,
            'ingredient_id'    => $ingredient->id,
            'stock_quantity_out' => $quantityOut,
            'reasons'          => $reason,
            'date_damaged'     => now(),
            'created_by'       => Auth::guard('owner')->id(),
            'created_by_type'  => 'owner',
            'date_created'     => now(),
            'active'           => 0,
        ]);

        $ingredient->stock_quantity_in -= $quantityOut;
        if ($ingredient->stock_quantity_in < 0) {
            $ingredient->stock_quantity_in = 0;
        }

        if (!is_null($ingredient->updated_by)) {
            $ingredient->last_updated_by      = $ingredient->updated_by;
            $ingredient->last_updated_by_type = $ingredient->updated_by_type;
            $ingredient->last_date_updated    = $ingredient->date_updated;
        }

        $ingredient->updated_by      = Auth::guard('owner')->id();
        $ingredient->updated_by_type = 'owner';
        $ingredient->date_updated    = now();
        $ingredient->save();

        return redirect()
            ->route('sub_one.ingredients.showIngredient')
            ->with('success', 'Ingredient damage recorded.');
    }

    // ─────────────────────────────────────────────────────────────
    // EXPIRED — auto-deactivate (legacy: only affects rows with a
    // pre-existing date_expiration value; the field is no longer
    // set by the create/update forms)
    // ─────────────────────────────────────────────────────────────
    private function handleExpiredIngredients(): int
    {
        $expired = Ingredient::where('active', 1)
            ->whereNotNull('date_expiration')
            ->where('date_expiration', '<=', now())
            ->get();

        foreach ($expired as $ingredient) {
            $ingredient->active            = 0;
            $ingredient->ingredient_status = 0;
            $ingredient->save();
        }

        return $expired->count();
    }

    // ─────────────────────────────────────────────────────────────
    // ORPHAN FILE CLEANUP
    // ─────────────────────────────────────────────────────────────
    private function cleanOrphanedFilesDynamic(array $folders): array
    {
        $allDeleted = [];

        foreach ($folders as $folder => $config) {
            $modelClass    = $config['model'];
            $column        = $config['column'];
            $modelInstance = new $modelClass;

            if (!Schema::hasTable($modelInstance->getTable()) ||
                !Schema::hasColumn($modelInstance->getTable(), $column)) {
                continue;
            }

            $allFiles     = $modelClass::pluck($column)->filter()->toArray();
            $existingFiles = [];

            foreach ($allFiles as $fileEntry) {
                if (empty($fileEntry)) continue;
                $decoded = json_decode($fileEntry, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $existingFiles = array_merge($existingFiles, $decoded);
                } else {
                    $existingFiles[] = $fileEntry;
                }
            }

            $existingFiles = array_map(fn($f) => ltrim($f, '/'), $existingFiles);
            $storageFiles  = array_map(fn($f) => ltrim($f, '/'), Storage::disk('public')->files($folder));

            foreach ($storageFiles as $file) {
                if (!in_array($file, $existingFiles)) {
                    Storage::disk('public')->delete($file);
                    $allDeleted[] = $file;
                }
            }
        }

        return $allDeleted;
    }

    public function runCleanup(): array
    {
        return $this->cleanOrphanedFilesDynamic([
            'ingredient_images' => ['model' => Ingredient::class, 'column' => 'ingredient_img'],
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // AJAX STORE  (definition only)
    // ─────────────────────────────────────────────────────────────
    public function storeIngredientAjax(Request $request)
    {
        $validated = $request->validate([
            'branch_id'               => 'required|integer|exists:branches,id',
            'ingredient_img'          => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'ingredient_type'         => 'required|string|max:255',
            'ingredient_name'         => 'required|string|max:255',
            'unit'                    => 'required|string|max:255',
            'stock_quantity_threshold'=> 'required|numeric|min:0',
        ]);

        try {
            $owner     = Auth::guard('owner')->user();
            $imagePath = null;

            if ($request->hasFile('ingredient_img')) {
                $imagePath = $request->file('ingredient_img')
                    ->store('ingredient_images', 'public');
            }

            $ingredient = Ingredient::create([
                'owner_account_id'        => $owner->id,
                'branch_id'               => $validated['branch_id'],
                'ingredient_batch_no'     => 'IBN' . now()->format('dmYHis'),
                'ingredient_img'          => $imagePath,
                'ingredient_type'         => $validated['ingredient_type'],
                'ingredient_name'         => $validated['ingredient_name'],
                'stock_quantity_in'       => 0,
                'unit'                    => $validated['unit'],
                'stock_quantity_threshold'=> $validated['stock_quantity_threshold'],
                'unit_conversion'               => null,
                'converted_stock_quantity_in'   => null,
                'converted_unit'                => null,
                'date_stored'             => now(),
                'ingredient_status'       => 1,
                'created_by'              => $owner->id,
                'created_by_type'         => 'owner',
                'date_created'            => now(),
                'active'                  => 1,
            ]);

            return response()->json([
                'success'    => true,
                'message'    => 'Ingredient created successfully.',
                'ingredient' => $ingredient->load('branch'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create ingredient. Please try again.',
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // AJAX UPDATE  (definition only)
    // ─────────────────────────────────────────────────────────────
    public function updateIngredientAjax(Request $request, $ingredient_uuid)
    {
        try {
            $owner = Auth::guard('owner')->user();

            $ingredient = Ingredient::where('uuid', $ingredient_uuid)
                ->where('owner_account_id', $owner->id)
                ->firstOrFail();

            if ($ingredient->products()->count() > 0 && $ingredient->branch_id != $request->branch_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot change branch: this ingredient is linked to existing products.',
                ], 422);
            }

            $validated = $request->validate([
                'branch_id'               => 'required|integer|exists:branches,id',
                'ingredient_img'          => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'ingredient_type'         => 'required|string|max:255',
                'ingredient_name'         => 'required|string|max:255',
                'unit'                    => 'required|string|max:255',
                'stock_quantity_threshold'=> 'required|numeric|min:0',
            ]);

            if ($request->hasFile('ingredient_img')) {
                if ($ingredient->ingredient_img) {
                    Storage::disk('public')->delete($ingredient->ingredient_img);
                }
                $ingredient->ingredient_img = $request->file('ingredient_img')
                    ->store('ingredient_images', 'public');
            }

            $ingredient->branch_id                = $validated['branch_id'];
            $ingredient->ingredient_type          = $validated['ingredient_type'];
            $ingredient->ingredient_name          = $validated['ingredient_name'];
            $ingredient->unit                     = $validated['unit'];
            $ingredient->stock_quantity_threshold = $validated['stock_quantity_threshold'];

            if (!is_null($ingredient->updated_by)) {
                $ingredient->last_updated_by      = $ingredient->updated_by;
                $ingredient->last_updated_by_type = $ingredient->updated_by_type;
                $ingredient->last_date_updated    = $ingredient->date_updated;
            }

            $ingredient->updated_by      = $owner->id;
            $ingredient->updated_by_type = 'owner';
            $ingredient->date_updated    = now();
            $ingredient->save();

            return response()->json([
                'success'    => true,
                'message'    => 'Ingredient updated successfully.',
                'ingredient' => $ingredient->fresh()->load('branch'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update ingredient. Please try again.',
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // GET DATA FOR EDIT MODAL
    // ─────────────────────────────────────────────────────────────
    public function getIngredientData($ingredient_uuid)
    {
        try {
            $owner = Auth::guard('owner')->user();

            $ingredient = Ingredient::where('uuid', $ingredient_uuid)
                ->where('owner_account_id', $owner->id)
                ->where('active', 1)
                ->firstOrFail();

            return response()->json([
                'success'    => true,
                'ingredient' => $ingredient,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ingredient not found or you do not have permission to edit it.',
            ], 404);
        }
    }
}