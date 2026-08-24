<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DamagedIngredient;
use App\Models\DamagedProduct;
use App\Models\OwnerAccount;
use App\Models\Product;
use App\Models\StaffAccount;
use App\Notifications\Owner\ProductNotification;
use App\Notifications\Staff\ProductStaffNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // Show Product
    public function showProduct(Request $request)
    {
        // Logged in as Owner
        $owner = Auth::guard('owner')->user();

        // Get product with relationships
        $query = Product::with(['branch', 'owner'])
            ->where('owner_account_id', $owner->id)
            ->where('active', 1);

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q
                    ->where('product_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('product_type', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('product_batch_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhereHas('branch', function ($q) use ($searchTerm) {
                        $q->where('branch_name', 'LIKE', "%{$searchTerm}%");
                    });
            });
        }

        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by product type
        if ($request->filled('product_type')) {
            $query->where('product_type', $request->product_type);
        }

        // Filter by status - FIXED: This was causing the issue
        if ($request->filled('product_status') && $request->product_status !== '') {
            // Convert string to integer for comparison
            $query->where('product_status', (int) $request->product_status);
        }

        // Filter by stock level
        if ($request->filled('stock_level')) {
            switch ($request->stock_level) {
                case 'low':
                    $query->whereRaw('quantity_in <= quantity_threshold');
                    break;
                case 'normal':
                    $query->whereRaw('quantity_in > quantity_threshold');
                    break;
            }
        }

        $products = $query->orderBy('date_created', 'desc')->paginate(10);

        $this->runCleanup();
        $expiredCount = $this->handleExpiredProducts();

        // Get unique product types for filter dropdown
        $productTypes = Product::with('owner', 'branch')
            ->where('active', 1)
            ->distinct()
            ->pluck('product_type')
            ->filter()
            ->values();

        // Active branches belonging to this owner
        $branches = Branch::with('owner')
            ->where('active', 1)
            ->get();

        // Statistics
        $statsQuery = Product::with('owner', 'branch')
            ->where('active', 1);

        $totalProducts = $statsQuery->count();
        $availableProducts = (clone $statsQuery)->where('product_status', 1)->count();
        $unavailableProducts = (clone $statsQuery)->where('product_status', 0)->count();
        $lowStockProducts = (clone $statsQuery)->whereRaw('quantity_in <= quantity_threshold')->count();

        $stats = [
            'total_products' => $totalProducts,
            'available_products' => $availableProducts,
            'unavailable_products' => $unavailableProducts,
            'low_stock_products' => $lowStockProducts,
        ];

        // Return JSON for AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $products->items(),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ],
                'stats' => $stats,
                'product_types' => $productTypes,
                'branches' => $branches
            ]);
        }

        // For regular requests, use session flash data
        $view = view('owner.product.product', compact('branches', 'products', 'productTypes', 'stats'));

        if ($expiredCount > 0) {
            session()->flash('success', $expiredCount . ' product(s) have expired and been deactivated.');
        }

        return $view;
    }

    // Store Product Details
    public function storeProduct(Request $request)
    {
        $validatedData = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'product_img' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'product_type' => 'required|string|max:255',
            'product_name' => 'required|string|max:255',
            'quantity_in' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:255',
            'quantity_threshold' => 'nullable|numeric|min:0',
            // converted fields are optional
            'unit_conversion' => 'nullable|numeric|min:0',
            'converted_quantity_in' => 'nullable|numeric|min:0',
            'converted_unit' => 'nullable|string|max:255',
            'selling_price' => 'required|numeric|min:0',
            'date_expiration' => 'nullable|date',
        ]);

        // Handle the file upload
        $imagePaths = null;

        if ($request->hasFile('product_img')) {
            $imagePaths = $request->file('product_img')->store('product_images', 'public');
        }

        $product = Product::create([
            'owner_account_id' => Auth::guard('owner')->id(),
            'branch_id' => $validatedData['branch_id'],
            'product_batch_no' => 'PBN' . now()->format('dmY'),
            'product_img' => $imagePaths,
            'product_type' => $validatedData['product_type'],
            'product_name' => $validatedData['product_name'],
            'quantity_in' => $validatedData['quantity_in'],
            'unit' => $validatedData['unit'],
            'quantity_threshold' => $validatedData['quantity_threshold'],
            // converted fields are optional
            'unit_conversion' => $validatedData['unit_conversion'],
            'converted_quantity_in' => $validatedData['converted_quantity_in'],
            'converted_unit' => $validatedData['converted_unit'],
            'selling_price' => $validatedData['selling_price'],
            'date_stored' => now(),
            'date_expiration' => $validatedData['date_expiration'],
            'product_status' => 1,
            'created_by' => Auth::guard('owner')->id(),
            'created_by_type' => 'owner',
            'date_created' => now(),
            'active' => 1,
        ]);

        // Send notification for product creation
        $this->sendProductNotification($product, 'created');

        return redirect()->route('sub_one.products.showProduct')->with('success', 'Product created successfully!');
    }

    // Update Product Details
    public function updateProduct(Request $request, $product_uuid)
    {
        $products = Product::where('uuid', $product_uuid)->firstOrFail();

        $validated = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'product_img' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'product_type' => 'required|string|max:255',
            'product_name' => 'required|string|max:255',
            'quantity_in' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:255',
            'quantity_threshold' => 'nullable|numeric|min:0',
            // converted fields are optional
            'unit_conversion' => 'nullable|numeric|min:0',
            'converted_quantity_in' => 'nullable|numeric|min:0',
            'converted_unit' => 'nullable|string|max:255',
            'selling_price' => 'required|numeric|min:0',
            'date_expiration' => 'nullable|date',
        ]);

        // Handle file upload
        if ($request->hasFile('product_img')) {
            // Store new file and assign path to the model
            $products->product_img = $request->file('product_img')->store('product_images', 'public');
        }

        // Assign updated values
        $products->branch_id = $validated['branch_id'];
        $products->product_type = $validated['product_type'];
        $products->product_name = $validated['product_name'];
        $products->quantity_in = $validated['quantity_in'];
        $products->unit = $validated['unit'];
        $products->quantity_threshold = $validated['quantity_threshold'];

        // converted fields are optional
        $products->unit_conversion = $validated['unit_conversion'];
        $products->converted_quantity_in = $validated['converted_quantity_in'];
        $products->converted_unit = $validated['converted_unit'];

        $products->selling_price = $validated['selling_price'];

        $products->date_expiration = $validated['date_expiration'];

        // Only move previous updater info to last_* fields
        if (!is_null($products->updated_by)) {
            $products->last_updated_by = $products->updated_by;
            $products->last_updated_by_type = $products->updated_by_type;
            $products->last_date_updated = $products->date_updated;
        }

        // Current updater info
        $products->updated_by = Auth::guard('owner')->id();
        $products->updated_by_type = 'owner';
        $products->date_updated = now();

        $products->save();

        // Send notification for product update
        $this->sendProductNotification($products, 'updated');

        return redirect()->route('sub_one.products.showProduct')->with('success', 'Product updated successfully!');
    }

    // Update Product Status
    public function updateProductStatus(Request $request, $product_uuid)
    {
        $products = Product::where('uuid', $product_uuid)->firstOrFail();

        $validated = $request->validate([
            'product_status' => 'required|in:0,1',  // 0=unavailable, 1=available
        ]);

        if ($products->product_status == $validated['product_status']) {
            return back()->with('info', 'No changes detected.');
        }

        // Update status
        $products->product_status = $validated['product_status'];

        // Only move the *previous* updater into the "last" fields
        if (!is_null($products->updated_by)) {
            $products->last_updated_by = $products->updated_by;
            $products->last_updated_by_type = $products->updated_by_type;
            $products->last_date_updated = $products->date_updated;
        }

        // Now record the current updater
        $products->updated_by = Auth::guard('owner')->id();
        $products->updated_by_type = 'owner';
        $products->date_updated = now();

        $products->save();

        // Send notification for status update
        $this->sendProductNotification($products, 'status_updated');

        return redirect()
            ->route('sub_one.products.showProduct')
            ->with('success', 'Product status updated successfully!');
    }

    // Show Deactivated Products
    public function showDeactivatedProduct()
    {
        // Logged in as Owner
        $owner = Auth::guard('owner')->user();

        $archived_products = Product::with('owner', 'branch')
            ->where('active', 0)
            ->where('owner_account_id', $owner->id)
            ->paginate(10, ['*'], 'archived_page');

        $expired_products = Product::with('owner', 'branch')
            ->where('owner_account_id', $owner->id)
            ->whereDate('date_expiration', '<=', now())
            ->paginate(10, ['*'], 'expired_page');

        $damaged_products = DamagedProduct::with('owner', 'branch', 'product')
            ->where('owner_account_id', $owner->id)
            ->paginate(10, ['*'], 'damaged_page');

        return view('owner.product.product_crud.delete-product', compact(
            'archived_products',
            'expired_products',
            'damaged_products'
        ));
    }

    // Deactivate Product

    public function deactivateProduct(Request $request, $product_uuid)
    {
        $products = Product::where('uuid', $product_uuid)->firstOrFail();

        if ($products->active === 0) {
            return back()->with('info', 'Product is already deactivated.');
        }

        $quantityOut = $request->input('quantity_out', 0);
        $request->input('reasons', null);

        // If quantity out is provided, call the damageProduct function
        if ($quantityOut > 0) {
            // Call the damageProduct function
            return $this->damageAndArchiveProduct($request, $product_uuid);
        }

        // Regular deactivation without damage
        $products->product_status = 0;  // 0=unavailable
        $products->active = 0;

        // Only move the *previous* updater into the "last" fields
        if (!is_null($products->updated_by)) {
            $products->last_updated_by = $products->updated_by;
            $products->last_updated_by_type = $products->updated_by_type;
            $products->last_date_updated = $products->date_updated;
        }

        // Now record the current updater
        $products->updated_by = Auth::guard('owner')->id();
        $products->updated_by_type = 'owner';
        $products->date_updated = now();

        $products->save();

        // Send notification for deactivation
        $this->sendProductNotification($products, 'deactivated');

        return redirect()
            ->route('sub_one.products.showProduct')
            ->with('success', 'Product deactivated successfully!');
    }

    // Reactivate Product
    public function reactivateProduct($product_uuid)
    {
        $products = Product::where('uuid', $product_uuid)->firstOrFail();

        if ($products->active === 1) {
            return back()->with('info', 'Product is already active.');
        }

        $expirationDate = Carbon::parse($products->date_expiration);
        $today = Carbon::today();

        if ($expirationDate->lessThanOrEqualTo($today)) {
            return back()->withErrors([
                'error' => 'Cannot reactivate. The product <strong>'
                    . e($products->product_name)
                    . '</strong> has already expired or expires today.'
            ]);
        }

        $products->product_status = 1;  // 1=available

        // Only move the *previous* updater into the "last" fields
        if (!is_null($products->updated_by)) {
            $products->last_updated_by = $products->updated_by;
            $products->last_updated_by_type = $products->updated_by_type;
            $products->last_date_updated = $products->date_updated;
        }

        // Now record the current updater
        $products->updated_by = Auth::guard('owner')->id();
        $products->updated_by_type = 'owner';
        $products->date_updated = now();
        $products->active = 1;

        $products->save();

        // Send notification for reactivation
        $this->sendProductNotification($products, 'reactivated');

        return redirect()
            ->route('sub_one.products.showDeactivatedProduct')
            ->with('success', 'Product reactivated successfully!');
    }

    // Expired Products - Returns count only

    private function handleExpiredProducts()
    {
        $now = now();

        $expired_products = Product::where('active', 1)
            ->whereNotNull('date_expiration')
            ->where('date_expiration', '<=', $now)
            ->get();

        $expiredCount = 0;

        foreach ($expired_products as $product) {
            $product->active = 0;
            $product->product_status = 0;
            $product->save();

            $this->sendProductNotification($product, 'expired');
            $expiredCount++;
        }

        return $expiredCount;
    }

    // Damage and Archive Product

    public function damageAndArchiveProduct(Request $request, $product_uuid)
    {
        // Load product with its ingredients
        $products = Product::with('productIngredients.ingredient')->where('uuid', $product_uuid)->firstOrFail();

        $quantityOut = $request->input('quantity_out', 0);
        $reason = $request->input('reasons', null);

        // Prevent zero or negative quantity
        if ($quantityOut <= 0) {
            return back()->with('error', 'Quantity out must be greater than 0 for damaged products.');
        }

        // ----------------------------
        // Create damaged product record
        // ----------------------------
        DamagedProduct::create([
            'owner_account_id' => $products->owner_account_id,
            'branch_id' => $products->branch_id,
            'product_id' => $products->id,
            'quantity_out' => $quantityOut,
            'reasons' => $reason,
            'date_damaged' => now(),
            'created_by' => Auth::guard('owner')->id(),
            'created_by_type' => 'owner',
            'date_created' => now(),
            'active' => 0,
        ]);

        // ----------------------------
        // Deduct product stock
        // ----------------------------
        if ($products->quantity_in !== null) {
            $products->quantity_in -= $quantityOut;
            if ($products->quantity_in < 0) {
                $products->quantity_in = 0;
            }
        }

        // ----------------------------
        // Archive the product
        // ----------------------------
        $products->product_status = 0;  // 0=unavailable
        $products->active = 0;

        // Track previous updater
        if (!is_null($products->updated_by)) {
            $products->last_updated_by = $products->updated_by;
            $products->last_updated_by_type = $products->updated_by_type;
            $products->last_date_updated = $products->date_updated;
        }

        $products->updated_by = Auth::guard('owner')->id();
        $products->updated_by_type = 'owner';
        $products->date_updated = now();

        // ----------------------------
        // Deduct ingredient stock
        // ----------------------------
        foreach ($products->productIngredients as $ingredientRelation) {
            $ingredientQuantityNeeded = $ingredientRelation->quantity_needed * $quantityOut;
            $ingredient = $ingredientRelation->ingredient;

            if (!$ingredient)
                continue;

            // ----------------------------
            // Create damaged ingredient record
            // ----------------------------
            DamagedIngredient::create([
                'product_id' => $products->id,
                'ingredient_id' => $ingredient->id,
                'ingredient_name' => $ingredient->ingredient_name,
                'stock_quantity_out' => $ingredientQuantityNeeded,
                'unit' => $ingredientRelation->unit,
                'reasons' => $reason,
                'branch_id' => $products->branch_id,
                'owner_account_id' => $products->owner_account_id,
                'date_damaged' => now(),
                'created_by' => Auth::guard('owner')->id(),
                'created_by_type' => 'owner',
                'date_created' => now(),
            ]);

            // ----------------------------
            // Deduction logic
            // ----------------------------
            if ($ingredient->converted_stock_quantity_in && $ingredient->unit_conversion > 0) {
                $totalNeeded = $ingredientQuantityNeeded;

                // Deduct from converted_stock_quantity_in
                $ingredient->converted_stock_quantity_in -= $totalNeeded;

                // Calculate how many full unit_conversions were deducted
                $fullUnitsDeducted = intdiv($totalNeeded, $ingredient->unit_conversion);

                // Deduct equivalent full units from stock_quantity_in
                $ingredient->stock_quantity_in -= $fullUnitsDeducted;
                if ($ingredient->stock_quantity_in < 0) {
                    $ingredient->stock_quantity_in = 0;
                }

                // Prevent negative converted_stock_quantity_in
                if ($ingredient->converted_stock_quantity_in < 0) {
                    $ingredient->converted_stock_quantity_in = 0;
                }
            } else {
                // Deduct directly from stock_quantity_in if no converted stock
                $ingredient->stock_quantity_in -= $ingredientQuantityNeeded;
                if ($ingredient->stock_quantity_in < 0) {
                    $ingredient->stock_quantity_in = 0;
                }
            }

            $ingredient->save();
        }

        $products->save();

        // Send notification for damaged product
        $this->sendProductNotification($products, 'damaged');

        return redirect()
            ->route('sub_one.products.showProduct')
            ->with('success', 'Product damaged and archived successfully!');
    }

    /**
     * Send notification for product actions
     */
    private function sendProductNotification($product, $action)
    {
        try {
            $actor = Auth::guard('owner')->user();
            // Get specific owner to notify
            $owner = Auth::guard('owner')->user();
            $owners = OwnerAccount::where('id', $owner->id)->get();

            // Get related branch
            $branch = $product->branch;

            Notification::send($owners, new ProductNotification($product, $branch, $actor, $action));

            // Notify Staff in the same branch
            $staffMembers = StaffAccount::where('branch_id', $product->branch_id)
                ->where('owner_account_id', $actor->id)
                ->where('active', 1)
                ->get();

            Notification::send($staffMembers, new ProductStaffNotification($product, $branch, $actor, $action));
        } catch (\Exception $e) {
        }
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
            'product_images' => ['model' => Product::class, 'column' => 'product_img'],
        ]);
    }

    public function getProductData($product_uuid)
    {
        try {
            $owner = Auth::guard('owner')->user();

            $product = Product::where('uuid', $product_uuid)
                ->where('owner_account_id', $owner->id)
                ->where('active', 1)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'product' => $product
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found or you do not have permission to edit it.'
            ], 404);
        }
    }

    // AJAX Store Product for better UX (New Method)
    public function storeProductAjax(Request $request)
    {
        $validatedData = $request->validate([
            'branch_id'          => 'required|integer|exists:branches,id',
            'product_img'        => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'product_category'   => 'required|in:rtd,mto',
            'product_type'       => 'required|string|max:255',
            'product_name'       => 'required|string|max:255',
            'unit'               => 'nullable|string|max:255',
            'quantity_threshold' => 'nullable|numeric|min:0',
            'selling_price'      => 'required|numeric|min:0',
        ]);

        try {
            $owner = Auth::guard('owner')->user();

            $imagePath = null;
            if ($request->hasFile('product_img')) {
                $imagePath = $request->file('product_img')->store('product_images', 'public');
            }

            $product = Product::create([
                'owner_account_id'   => $owner->id,
                'branch_id'          => $validatedData['branch_id'],
                'product_batch_no'   => 'PBN' . now()->format('dmY'),
                'product_img'        => $imagePath,
                'product_category'   => $validatedData['product_category'],
                'product_type'       => $validatedData['product_type'],
                'product_name'       => $validatedData['product_name'],
                'unit'               => $validatedData['unit'],
                'quantity_threshold' => $validatedData['quantity_threshold'],
                'selling_price'      => $validatedData['selling_price'],
                'date_stored'        => now(),
                'product_status'     => 1,
                'created_by'         => $owner->id,
                'created_by_type'    => 'owner',
                'date_created'       => now(),
                'active'             => 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully.',
                'product' => $product->load('branch'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product. Please try again.',
            ], 500);
        }
    }

    // AJAX Update Product for better UX (New Method)
    public function updateProductAjax(Request $request, $product_uuid)
    {
        $validated = $request->validate([
            'branch_id'          => 'required|integer|exists:branches,id',
            'product_img'        => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'product_category'   => 'required|in:rtd,mto',
            'product_type'       => 'required|string|max:255',
            'product_name'       => 'required|string|max:255',
            'unit'               => 'nullable|string|max:255',
            'quantity_threshold' => 'nullable|numeric|min:0',
            'selling_price'      => 'required|numeric|min:0',
        ]);

        try {
            $owner = Auth::guard('owner')->user();

            $product = Product::where('uuid', $product_uuid)
                ->where('owner_account_id', $owner->id)
                ->firstOrFail();

            if ($request->hasFile('product_img')) {
                if ($product->product_img) {
                    Storage::disk('public')->delete($product->product_img);
                }
                $product->product_img = $request->file('product_img')
                    ->store('product_images', 'public');
            }

            $product->branch_id          = $validated['branch_id'];
            $product->product_category   = $validated['product_category'];
            $product->product_type       = $validated['product_type'];
            $product->product_name       = $validated['product_name'];
            $product->unit               = $validated['unit'];
            $product->quantity_threshold = $validated['quantity_threshold'];
            $product->selling_price      = $validated['selling_price'];

            if (!is_null($product->updated_by)) {
                $product->last_updated_by      = $product->updated_by;
                $product->last_updated_by_type = $product->updated_by_type;
                $product->last_date_updated    = $product->date_updated;
            }

            $product->updated_by      = $owner->id;
            $product->updated_by_type = 'owner';
            $product->date_updated    = now();

            $product->save();

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully.',
                'product' => $product->fresh()->load('branch'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product. Please try again.',
            ], 500);
        }
    }
}