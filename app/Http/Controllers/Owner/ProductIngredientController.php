<?php

namespace App\Http\Controllers\Owner;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Ingredient;
use App\Models\OwnerAccount;
use App\Models\StaffAccount;
use Illuminate\Http\Request;
use App\Models\ProductIngredient;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Owner\ProductIngredientNotification;
use App\Notifications\Staff\ProductIngredientStaffNotification;

class ProductIngredientController extends Controller
{
    // Show only Product with Ingredients
    public function showProductIngredient($product_uuid, Request $request)
    {
        // Logged in as Owner
        $owner = Auth::guard('owner')->user();
        $ownerId = $owner->id;

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

        return view('owner.product.product_ingredient', compact(
            'product_ingredients',
            'products',
            'branches',
            'stats',
            'ingredientTypes',
            'units',
            'ingredients'
        ));
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
            $owner = Auth::guard('owner')->user();
            $product = Product::findOrFail($validated['product_id']);
            $branchId = $product->branch_id;

            // Create a new ProductIngredient record
            $productIngredient = ProductIngredient::create([
                'owner_account_id' => $owner->id,
                'branch_id' => $branchId,
                'product_id' => $validated['product_id'],
                'ingredient_id' => $validated['ingredient_id'],
                'unit' => $validated['unit'],
                'quantity_needed' => $validated['quantity_needed'],
                'quantity_in_base_unit' => $validated['quantity_in_base_unit'],
                'base_unit' => $validated['base_unit'],
                'created_by' => $owner->id,
                'created_by_type' => 'owner',
                'date_created' => now(),
                'active' => 1,
            ]);

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
            $owner = Auth::guard('owner')->user();

            $productIngredient = ProductIngredient::with(['ingredient', 'product'])
                ->where('uuid', $product_ingredient_uuid)
                ->where('owner_account_id', $owner->id)
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
            $owner = Auth::guard('owner')->user();
            
            $productIngredient = ProductIngredient::where('uuid', $product_ingredient_uuid)
                ->where('owner_account_id', $owner->id)
                ->firstOrFail();

            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'ingredient_id' => 'required|exists:ingredients,id',
                'unit' => 'required|string|max:255',
                'quantity_needed' => 'required|numeric|min:0',
                'quantity_in_base_unit' => 'required|numeric|min:0',
                'base_unit' => 'required|string|max:255',
            ]);

            // Get product to determine branch
            $products = Product::findOrFail($validated['product_id']);
            $branchId = $products->branch_id;

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

            $productIngredient->updated_by = $owner->id;
            $productIngredient->updated_by_type = 'owner';
            $productIngredient->date_updated = now();
            $productIngredient->save();

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
            $owner = Auth::guard('owner')->user();
            
            $product = Product::where('uuid', $product_uuid)
                ->where('owner_account_id', $owner->id)
                ->firstOrFail();

            $ingredients = Ingredient::where('owner_account_id', $owner->id)
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
            $owner = Auth::guard('owner')->user();
            $owners = OwnerAccount::where('id', $owner->id)->get();

            $actor = Auth::guard('owner')->user();

            // Get related models
            $product = $productIngredient->product;
            $ingredient = $productIngredient->ingredient;
            $branch = $productIngredient->branch;

            Notification::send($owners, new ProductIngredientNotification($productIngredient, $product, $ingredient, $branch, $actor, $action, $additionalData));

            // Notify Staff in the same branch
            $staffMembers = StaffAccount::where('branch_id', $productIngredient->branch_id)
                ->where('owner_account_id', $actor->id)
                ->where('active', 1)
                ->get();

            Notification::send($staffMembers, new ProductIngredientStaffNotification($productIngredient, $product, $ingredient, $branch, $actor, $action, $additionalData));
        } catch (\Exception $e) {
            // Silently fail for notifications
        }
    }
}