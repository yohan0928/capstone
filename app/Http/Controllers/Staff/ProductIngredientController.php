<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Ingredient;
use App\Models\OwnerAccount;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Models\StaffAccount;
use App\Services\StaffActivityLogger;
use App\Models\StaffActivityLog;
use App\Notifications\Owner\ProductIngredientNotification;
use App\Notifications\Staff\ProductIngredientStaffNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class ProductIngredientController extends Controller
{
    // Show only Product with Ingredients
     // Show only Product with Ingredients
    public function showProductIngredient($product_uuid, Request $request)
    {
        $staff = Auth::guard('staff')->user();
        $staffId = $staff->id;
        $branchId = $staff->branch_id;  // Staff is assigned to a specific branch
        $ownerId = $staff->owner_account_id;  // Staff belongs to an owner

        $products = Product::where('uuid', $product_uuid)
            ->where('owner_account_id', $ownerId)
            ->firstOrFail();

        // Fetch the branch using the product's branch_id
        $branches = Branch::where('id', $products->branch_id)
            ->where('owner_account_id', $ownerId)
            ->firstOrFail();

        $query = ProductIngredient::with(['product', 'ingredient', 'branch'])
            ->where('active', 1)
            ->where('owner_account_id', $ownerId)
            ->where('product_id', $products->id);

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q
                    ->whereHas('ingredient', function ($q) use ($searchTerm) {
                        $q
                            ->where('ingredient_name', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('ingredient_type', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('ingredient_batch_no', 'LIKE', "%{$searchTerm}%");
                    })
                    ->orWhere('unit', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('base_unit', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Filter by ingredient type
        if ($request->filled('ingredient_type')) {
            $query->whereHas('ingredient', function ($q) use ($request) {
                $q->where('ingredient_type', $request->ingredient_type);
            });
        }

        // Filter by unit
        if ($request->filled('unit')) {
            $query->where('unit', $request->unit);
        }

        $product_ingredients = $query->orderBy('date_created', 'desc')->paginate(10);

        // Get unique ingredient types for filter dropdown
        $ingredientTypes = ProductIngredient::with('ingredient')
            ->where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->where('product_id', $products->id)
            ->get()
            ->pluck('ingredient.ingredient_type')
            ->filter()
            ->unique()
            ->values();

        // Get unique units for filter dropdown
        $units = ProductIngredient::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->where('product_id', $products->id)
            ->distinct()
            ->pluck('unit')
            ->filter()
            ->values();

        // Get all ingredients for the modal dropdown
        $ingredients = Ingredient::where('owner_account_id', $ownerId)
            ->where('branch_id', $branchId)  // Staff can only use ingredients from their branch
            ->where('active', 1)
            ->get();

        // Statistics
        $statsQuery = ProductIngredient::where('owner_account_id', $ownerId)
            ->where('product_id', $products->id)
            ->where('active', 1);

        $totalIngredients = $statsQuery->count();

        $stats = [
            'total_ingredients' => $totalIngredients,
        ];

        // Return JSON for AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $product_ingredients->items(),
                'pagination' => [
                    'current_page' => $product_ingredients->currentPage(),
                    'last_page' => $product_ingredients->lastPage(),
                    'per_page' => $product_ingredients->perPage(),
                    'total' => $product_ingredients->total(),
                    'from' => $product_ingredients->firstItem(),
                    'to' => $product_ingredients->lastItem(),
                ],
                'stats' => $stats,
                'ingredient_types' => $ingredientTypes,
                'units' => $units
            ]);
        }

        return view('staff.product.product_ingredient', compact(
            'product_ingredients',
            'products',
            'branches',
            'stats',
            'ingredientTypes',
            'units',
            'ingredients'
        ));
    }

    // Show Add Product with Ingredients Form
    public function showAddProductIngredientForm($product_uuid)
    {
        // Logged in as Staff
        $staff = Auth::guard('staff')->user();
        $ownerId = $staff->owner_account_id;
        $branchId = $staff->branch_id;

        // Get branches - ONLY STAFF'S BRANCH
        $branches = Branch::where('owner_account_id', $ownerId)
            ->where('id', $branchId)  // Only staff's branch
            ->first();

        // Get product - ensure it belongs to staff's branch
        $products = Product::where('uuid', $product_uuid)
            ->where('owner_account_id', $ownerId)
            ->where('branch_id', $branchId)  // Check branch
            ->firstOrFail();

        // Active ingredients belonging to this owner AND BRANCH
        $ingredients = Ingredient::where('owner_account_id', $ownerId)
            ->where('branch_id', $branchId)  // Add this - staff can only use ingredients from their branch
            ->where('active', 1)
            ->get();

        return view(
            'staff.product.product_ingredient_crud.add-product-ingredient',
            compact('branches', 'products', 'ingredients')
        );
    }

    // Store Product with Ingredient Details
    public function storeProductIngredient(Request $request)
{
    // Validate the incoming request
    $validated = $request->validate([
        'product_id' => 'required|exists:products,id',
        'ingredient_id' => 'required|exists:ingredients,id',
        'unit' => 'required|string|max:255',
        'quantity_needed' => 'required|numeric|min:0',
        'quantity_in_base_unit' => 'required|numeric|min:0',
        'base_unit' => 'required|string|max:255',
    ]);

    // Get staff info
    $staff = Auth::guard('staff')->user();
    $ownerId = $staff->owner_account_id;
    $branchId = $staff->branch_id;

    // Get product - ensure it belongs to staff's branch
    $product = Product::where('id', $validated['product_id'])
        ->where('owner_account_id', $ownerId)
        ->where('branch_id', $branchId)  // Check branch
        ->firstOrFail();

    // Check if ingredient belongs to staff's branch
    $ingredient = Ingredient::where('id', $validated['ingredient_id'])
        ->where('owner_account_id', $ownerId)
        ->where('branch_id', $branchId)  // Check branch
        ->first();

    if (!$ingredient) {
        return back()->withErrors(['ingredient_id' => 'Ingredient not found in your branch.']);
    }

    // Create a new ProductIngredient record
    $productIngredient = ProductIngredient::create([
        'owner_account_id' => $ownerId,
        'branch_id' => $branchId,  // Use staff's branch
        'product_id' => $validated['product_id'],
        'ingredient_id' => $validated['ingredient_id'],
        'unit' => $validated['unit'],
        'quantity_needed' => $validated['quantity_needed'],
        'quantity_in_base_unit' => $validated['quantity_in_base_unit'],
        'base_unit' => $validated['base_unit'],
        'created_by' => Auth::guard('staff')->id(),
        'created_by_type' => 'staff',
        'date_created' => now(),
        'active' => 1,
    ]);

    // LOG: ADD PRODUCT INGREDIENT ACTION
    StaffActivityLogger::logAddProductIngredient($productIngredient, $product, $ingredient, $request);

    // Send notification for product creation
    $actor = Auth::guard('staff')->user();
    $owners = OwnerAccount::where('id', $staff->owner_account_id)->get();
    $branch = $product->branch;

    Notification::send($owners, new ProductIngredientStaffNotification($productIngredient, $product, $ingredient, $branch, $actor, 'added'));

    // Notify Staff in the same branch and under same owner
    $staffMembers = StaffAccount::where('branch_id', $staff->branch_id)
        ->where('owner_account_id', $staff->owner_account_id)
        ->where('active', 1)
        ->get();

    Notification::send($staffMembers, new ProductIngredientStaffNotification($productIngredient, $product, $ingredient, $branch, $actor, 'added'));

    return redirect()
        ->route('sub_two.product_ingredients.showProductIngredient', $product->uuid)
        ->with('success', 'Product with ingredient created successfully!');
}

    // Show Edit Product with Ingredient Form
    public function showEditProductIngredientForm($product_uuid, $product_ingredient_uuid)
    {
        // Logged in as Staff
        $staff = Auth::guard('staff')->user();
        $ownerId = $staff->owner_account_id;
        $branchId = $staff->branch_id;

        // Get product ingredient - ensure it belongs to staff's branch
        $productIngredient = ProductIngredient::where('uuid', $product_ingredient_uuid)
            ->where('owner_account_id', $ownerId)
            ->where('branch_id', $branchId)  // Add branch check
            ->firstOrFail();

        // Get branch - only staff's branch
        $branch = Branch::where('owner_account_id', $ownerId)
            ->where('id', $branchId)  // Only staff's branch
            ->firstOrFail();

        // Get product - ensure it belongs to staff's branch
        $products = Product::where('uuid', $product_uuid)
            ->where('owner_account_id', $ownerId)
            ->where('branch_id', $branchId)  // Check branch
            ->firstOrFail();

        // Active ingredients belonging to this owner AND BRANCH
        $ingredients = Ingredient::where('owner_account_id', $ownerId)
            ->where('branch_id', $branchId)  // Only ingredients from staff's branch
            ->where('active', 1)
            ->get();

        return view('staff.product.product_ingredient_crud.edit-product-ingredient',
            compact('branch', 'products', 'ingredients', 'productIngredient'));
    }

    // Update Product with Ingredient Details
    public function updateProductIngredient(Request $request, $product_ingredient_uuid)
{
    // Get staff info
    $staff = Auth::guard('staff')->user();
    $ownerId = $staff->owner_account_id;
    $branchId = $staff->branch_id;

    // Get product ingredient - ensure it belongs to staff's branch
    $productIngredient = ProductIngredient::where('uuid', $product_ingredient_uuid)
        ->where('owner_account_id', $ownerId)
        ->where('branch_id', $branchId)  // Check branch
        ->firstOrFail();

    // Capture old data for logging
    $oldData = $productIngredient->getAttributes();

    $validated = $request->validate([
        'product_id' => 'required|exists:products,id',
        'ingredient_id' => 'required|exists:ingredients,id',
        'unit' => 'required|string|max:255',
        'quantity_needed' => 'required|numeric|min:0',
        'quantity_in_base_unit' => 'required|numeric|min:0',
        'base_unit' => 'required|string|max:255',
    ]);

    // Get product - ensure it belongs to staff's branch
    $product = Product::where('id', $validated['product_id'])
        ->where('owner_account_id', $ownerId)
        ->where('branch_id', $branchId)  // Check branch
        ->firstOrFail();

    // Check if ingredient belongs to staff's branch
    $ingredient = Ingredient::where('id', $validated['ingredient_id'])
        ->where('owner_account_id', $ownerId)
        ->where('branch_id', $branchId)  // Check branch
        ->first();

    if (!$ingredient) {
        return back()->withErrors(['ingredient_id' => 'Ingredient not found in your branch.']);
    }

    // Update fields - ensure branch stays as staff's branch
    $productIngredient->branch_id = $branchId;  // Use staff's branch, not product's branch
    $productIngredient->product_id = $validated['product_id'];
    $productIngredient->ingredient_id = $validated['ingredient_id'];
    $productIngredient->unit = $validated['unit'];
    $productIngredient->quantity_needed = $validated['quantity_needed'];
    $productIngredient->quantity_in_base_unit = $validated['quantity_in_base_unit'];
    $productIngredient->base_unit = $validated['base_unit'];

    // Only move the *previous* updater into the "last" fields
    if (!is_null($productIngredient->updated_by)) {
        $productIngredient->last_updated_by = $productIngredient->updated_by;
        $productIngredient->last_updated_by_type = $productIngredient->updated_by_type;
        $productIngredient->last_date_updated = $productIngredient->date_updated;
    }

    // Now record the current updater
    $productIngredient->updated_by = Auth::guard('staff')->id();
    $productIngredient->updated_by_type = 'staff';
    $productIngredient->date_updated = now();

    $productIngredient->save();

    // LOG: UPDATE PRODUCT INGREDIENT ACTION
    StaffActivityLogger::logUpdateProductIngredient($productIngredient, $product, $ingredient, $oldData, $request);

    // Send notification for product creation
    $actor = Auth::guard('staff')->user();
    $owners = OwnerAccount::where('id', $staff->owner_account_id)->get();
    $branch = $productIngredient->branch;

    Notification::send($owners, new ProductIngredientStaffNotification($productIngredient, $product, $ingredient, $branch, $actor, 'updated'));

    // Notify Staff in the same branch and under same owner
    $staffMembers = StaffAccount::where('branch_id', $staff->branch_id)
        ->where('owner_account_id', $staff->owner_account_id)
        ->where('active', 1)
        ->get();

    Notification::send($staffMembers, new ProductIngredientStaffNotification($productIngredient, $product, $ingredient, $branch, $actor, 'updated'));

    return redirect()
        ->route('sub_two.product_ingredients.showProductIngredient', $product->uuid)
        ->with('success', 'Product with ingredient updated successfully!');
}

    // AJAX: Store Product Ingredient
    public function storeProductIngredientAjax(Request $request)
{
    // Validate the incoming request
    $validated = $request->validate([
        'product_id' => 'required|exists:products,id',
        'ingredient_id' => 'required|exists:ingredients,id',
        'unit' => 'required|string|max:255',
        'quantity_needed' => 'required|numeric|min:0',
        'quantity_in_base_unit' => 'required|numeric|min:0',
        'base_unit' => 'required|string|max:255',
    ]);

    try {
        $staff = Auth::guard('staff')->user();
        $ownerId = $staff->owner_account_id;
        $branchId = $staff->branch_id;
        
        $product = Product::findOrFail($validated['product_id']);

        // Check if ingredient belongs to staff's branch
        $ingredient = Ingredient::where('id', $validated['ingredient_id'])
            ->where('owner_account_id', $ownerId)
            ->where('branch_id', $branchId)
            ->first();

        if (!$ingredient) {
            return response()->json([
                'success' => false,
                'message' => 'Ingredient not found in your branch.'
            ], 403);
        }

        // Create a new ProductIngredient record
        $productIngredient = ProductIngredient::create([
            'owner_account_id' => $ownerId,
            'branch_id' => $branchId,
            'product_id' => $validated['product_id'],
            'ingredient_id' => $validated['ingredient_id'],
            'unit' => $validated['unit'],
            'quantity_needed' => $validated['quantity_needed'],
            'quantity_in_base_unit' => $validated['quantity_in_base_unit'],
            'base_unit' => $validated['base_unit'],
            'created_by' => $staff->id,
            'created_by_type' => 'staff',
            'date_created' => now(),
            'active' => 1,
        ]);

        // LOG: ADD PRODUCT INGREDIENT ACTION (AJAX)
        StaffActivityLogger::logAddProductIngredient($productIngredient, $product, $ingredient, $request);

        // Send notification
        $this->sendProductIngredientNotification($productIngredient, 'created');

        return response()->json([
            'success' => true,
            'message' => 'Product ingredient created successfully!',
            'product_ingredient' => $productIngredient->load(['ingredient', 'branch'])
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to create product ingredient. Please try again.'
        ], 500);
    }
}

    // AJAX: Get Product Ingredient data for edit
    public function getProductIngredientData($product_uuid, $product_ingredient_uuid)
    {
        try {
            $staff = Auth::guard('staff')->user();
            $ownerId = $staff->owner_account_id;
            $branchId = $staff->branch_id;

            $productIngredient = ProductIngredient::with(['ingredient', 'product'])
                ->where('uuid', $product_ingredient_uuid)
                ->where('owner_account_id', $ownerId)
                ->where('branch_id', $branchId)  // Check staff's branch
                ->where('active', 1)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'product_ingredient' => $productIngredient
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product ingredient not found or you do not have permission to edit it.'
            ], 404);
        }
    }

    // AJAX: Update Product Ingredient
    public function updateProductIngredientAjax(Request $request, $product_ingredient_uuid)
{
    try {
        $staff = Auth::guard('staff')->user();
        $ownerId = $staff->owner_account_id;
        $branchId = $staff->branch_id;
        
        $productIngredient = ProductIngredient::where('uuid', $product_ingredient_uuid)
            ->where('owner_account_id', $ownerId)
            ->where('branch_id', $branchId)  // Check staff's branch
            ->firstOrFail();

        // Capture old data for logging
        $oldData = $productIngredient->getAttributes();

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'ingredient_id' => 'required|exists:ingredients,id',
            'unit' => 'required|string|max:255',
            'quantity_needed' => 'required|numeric|min:0',
            'quantity_in_base_unit' => 'required|numeric|min:0',
            'base_unit' => 'required|string|max:255',
        ]);

        // Get product and ingredient for logging
        $product = Product::find($validated['product_id']);
        $ingredient = Ingredient::where('id', $validated['ingredient_id'])
            ->where('owner_account_id', $ownerId)
            ->where('branch_id', $branchId)
            ->first();

        if (!$ingredient) {
            return response()->json([
                'success' => false,
                'message' => 'Ingredient not found in your branch.'
            ], 403);
        }

        // Update fields
        $productIngredient->branch_id = $branchId;
        $productIngredient->product_id = $validated['product_id'];
        $productIngredient->ingredient_id = $validated['ingredient_id'];
        $productIngredient->unit = $validated['unit'];
        $productIngredient->quantity_needed = $validated['quantity_needed'];
        $productIngredient->quantity_in_base_unit = $validated['quantity_in_base_unit'];
        $productIngredient->base_unit = $validated['base_unit'];

        // Track updater info
        if (!is_null($productIngredient->updated_by)) {
            $productIngredient->last_updated_by = $productIngredient->updated_by;
            $productIngredient->last_updated_by_type = $productIngredient->updated_by_type;
            $productIngredient->last_date_updated = $productIngredient->date_updated;
        }

        $productIngredient->updated_by = $staff->id;
        $productIngredient->updated_by_type = 'staff';
        $productIngredient->date_updated = now();
        $productIngredient->save();

        // LOG: UPDATE PRODUCT INGREDIENT ACTION (AJAX)
        StaffActivityLogger::logUpdateProductIngredient($productIngredient, $product, $ingredient, $oldData, $request);

        // Send notification
        $this->sendProductIngredientNotification($productIngredient, 'updated');

        return response()->json([
            'success' => true,
            'message' => 'Product ingredient updated successfully!',
            'product_ingredient' => $productIngredient->fresh()->load(['ingredient', 'branch'])
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update product ingredient. Please try again.'
        ], 500);
    }
}

    // Get all ingredients for dropdown
    public function getIngredientsForProduct($product_uuid)
    {
        try {
            $staff = Auth::guard('staff')->user();
            $ownerId = $staff->owner_account_id;
            $branchId = $staff->branch_id;
            
            $product = Product::where('uuid', $product_uuid)
                ->where('owner_account_id', $ownerId)
                ->firstOrFail();

            $ingredients = Ingredient::where('owner_account_id', $ownerId)
                ->where('branch_id', $branchId)  // Only ingredients from staff's branch
                ->where('active', 1)
                ->get();

            return response()->json([
                'success' => true,
                'ingredients' => $ingredients,
                'product' => $product
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load ingredients.'
            ], 500);
        }
    }

    /**
     * Send notification for product ingredient actions
     */
    private function sendProductIngredientNotification($productIngredient, $action, $additionalData = [])
    {
        try {
            $staff = Auth::guard('staff')->user();
            $ownerId = $staff->owner_account_id;

            // Notify Owner
            $owners = OwnerAccount::where('id', $ownerId)->get();

            // Get related models
            $product = $productIngredient->product;
            $ingredient = $productIngredient->ingredient;
            $branch = $productIngredient->branch;

            Notification::send($owners, new ProductIngredientNotification($productIngredient, $product, $ingredient, $branch, $staff, $action, $additionalData));

            // Notify Staff in the same branch
            $staffMembers = StaffAccount::where('branch_id', $productIngredient->branch_id)
                ->where('owner_account_id', $ownerId)
                ->where('active', 1)
                ->get();

            Notification::send($staffMembers, new ProductIngredientStaffNotification($productIngredient, $product, $ingredient, $branch, $staff, $action, $additionalData));
        } catch (\Exception $e) {
            // Silently fail for notifications
        }
    }
}
