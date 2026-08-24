<?php

namespace App\Http\Controllers\Staff;

use App\Models\RedeemableItem;
use App\Models\Branch;
use App\Models\ServiceName;
use App\Models\ServiceCategory;
use App\Models\Product;
use App\Models\ProductIngredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RedeemableItemController extends Controller
{
    /**
     * Display a listing of redeemable items for staff's branch.
     */
    public function index(Request $request)
    {
        $staff = Auth::guard('staff')->user();
        $ownerId = $staff->owner_account_id;
        $branchId = $staff->branch_id;

        // Get branches (only the staff's assigned branch)
        $branches = Branch::where('owner_account_id', $ownerId)
            ->where('id', $branchId)
            ->where('active', 1)
            ->select('id', 'branch_name')
            ->get();

        // Build query - only show items for this branch or global items
        $query = RedeemableItem::with([
            'branch', 
            'loyaltyTiers', 
            'targetService', 
            'targetService.serviceCategory',
            'targetService.branch',
            'targetProduct',
            'targetProduct.branch',
            'targetProduct.productIngredients',
            'targetProduct.productIngredients.ingredient'
        ])
        ->where('owner_account_id', $ownerId)
        ->whereNull('deleted_at')
        ->where(function($q) use ($branchId) {
            $q->where('branch_id', $branchId)
              ->orWhereNull('branch_id');
        });

        // Search filter
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where('item_name', 'LIKE', "%{$searchTerm}%");
        }

        // Type filter
        if ($request->filled('reward_type')) {
            $query->where('reward_type', $request->reward_type);
        }

        // Status filter
        if ($request->filled('status')) {
            $status = $request->status == '1' ? 1 : 0;
            $query->where('active', $status)->where('is_active', $status);
        }

        // Get paginated results
        $items = $query->orderBy('item_name')
            ->paginate(50);

        // Transform the collection to ensure accessors are applied
        $items->getCollection()->transform(function ($item) {
            $item->type_label = $item->type_label;
            $item->value_display = $item->value_display;
            $item->status_label = $item->status_label;
            $item->loyalty_tiers_count = $item->loyaltyTiers()->count();
            return $item;
        });

        // Calculate stats for this branch
        $stats = [
            'total' => RedeemableItem::where('owner_account_id', $ownerId)
                ->whereNull('deleted_at')
                ->where(function($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
                })
                ->count(),
            'active' => RedeemableItem::where('owner_account_id', $ownerId)
                ->where('active', true)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->where(function($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
                })
                ->count(),
            'inactive' => RedeemableItem::where('owner_account_id', $ownerId)
                ->where(function($q) {
                    $q->where('active', false)
                      ->orWhere('is_active', false);
                })
                ->whereNull('deleted_at')
                ->where(function($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
                })
                ->count(),
            'linked' => RedeemableItem::where('owner_account_id', $ownerId)
                ->whereHas('loyaltyTiers')
                ->whereNull('deleted_at')
                ->where(function($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
                })
                ->count()
        ];

        // Get services for this branch only
        $services = ServiceName::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->where('branch_id', $branchId)
            ->with(['serviceCategory', 'branch'])
            ->select('id', 'branch_id', 'service_category_id', 'service_name', 'price')
            ->get()
            ->map(function($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->service_name,
                    'category' => $service->serviceCategory->service_category ?? 'Uncategorized',
                    'category_id' => $service->service_category_id,
                    'branch' => $service->branch->branch_name ?? 'N/A',
                    'branch_id' => $service->branch_id,
                    'price' => $service->price ?? 0,
                    'display' => $service->service_name . ' (' . ($service->branch->branch_name ?? 'N/A') . ')'
                ];
            });

        // Get products for this branch only
        $products = Product::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->where('branch_id', $branchId)
            ->with(['branch', 'productIngredients', 'productIngredients.ingredient'])
            ->select('id', 'branch_id', 'product_name', 'selling_price', 'product_type')
            ->get()
            ->map(function($product) {
                $display = $product->product_name . ' (' . ($product->branch->branch_name ?? 'N/A') . ')';
                
                $hasIngredients = $product->productIngredients && $product->productIngredients->count() > 0;
                if ($hasIngredients) {
                    $display .= ' [With Ingredients]';
                }
                
                return [
                    'id' => $product->id,
                    'name' => $product->product_name,
                    'product_type' => $product->product_type ?? 'simple',
                    'branch' => $product->branch->branch_name ?? 'N/A',
                    'branch_id' => $product->branch_id,
                    'price' => $product->selling_price ?? 0,
                    'has_ingredients' => $hasIngredients,
                    'ingredients_count' => $product->productIngredients ? $product->productIngredients->count() : 0,
                    'display' => $display
                ];
            });

        // For AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $items->items(),
                'pagination' => [
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                    'per_page' => $items->perPage(),
                    'total' => $items->total(),
                    'from' => $items->firstItem(),
                    'to' => $items->lastItem(),
                ],
                'branches' => $branches,
                'services' => $services,
                'products' => $products,
                'stats' => $stats,
                'rewardTypes' => RedeemableItem::getRewardTypeOptions()
            ]);
        }

        return view('staff.loyalty.redeemable_items', compact('items', 'branches', 'services', 'products', 'stats'));
    }

    /**
     * Store a newly created redeemable item.
     */
    public function store(Request $request)
    {
        try {
            Log::info('Staff Store method called', $request->all());

            // Validate based on reward type
            $rules = [
                'item_name' => 'required|string|max:255',
                'reward_type' => 'required|in:free_service,free_product,fixed_discount,percentage_discount',
                'item_description' => 'nullable|string',
                'category' => 'nullable|string|max:255',
                'branch_id' => 'nullable|exists:branches,id',
                'is_active' => 'required|boolean',
            ];

            // Add type-specific validation
            if ($request->reward_type === 'free_service') {
                $rules['target_service_id'] = 'required|exists:service_names,id';
                $rules['monetary_value'] = 'required|numeric|min:0.01';
            } elseif ($request->reward_type === 'free_product') {
                $rules['target_product_id'] = 'required|exists:products,id';
                $rules['monetary_value'] = 'required|numeric|min:0.01';
            } elseif ($request->reward_type === 'fixed_discount') {
                $rules['monetary_value'] = 'required|numeric|min:0.01';
            } elseif ($request->reward_type === 'percentage_discount') {
                $rules['discount_percentage'] = 'required|numeric|min:0.01|max:100';
                $rules['monetary_value'] = 'nullable|numeric|min:0';
            }

            $validated = $request->validate($rules);

            Log::info('Validation passed', $validated);

            DB::beginTransaction();

            $staff = Auth::guard('staff')->user();
            $branchId = $staff->branch_id;
            
            if (!$staff) {
                return response()->json([
                    'success' => false,
                    'message' => 'Staff not authenticated'
                ], 401);
            }

            Log::info('Staff ID: ' . $staff->id . ', Branch ID: ' . $branchId);

            // Force branch to staff's branch if not specified
            $branchIdToUse = $validated['branch_id'] ?? $branchId;

            // For percentage discount, convert percentage to decimal and store in monetary_value
            $monetaryValue = $validated['monetary_value'] ?? null;
            $discountPercentage = $validated['discount_percentage'] ?? null;

            if ($validated['reward_type'] === 'percentage_discount' && $discountPercentage) {
                // Convert percentage to decimal (e.g., 10% -> 0.10)
                $monetaryValue = $discountPercentage / 100;
            }

            // Create the item
            $item = RedeemableItem::create([
                'owner_account_id' => $staff->owner_account_id,
                'branch_id' => $branchIdToUse,
                'item_name' => $validated['item_name'],
                'item_description' => $validated['item_description'] ?? null,
                'reward_type' => $validated['reward_type'],
                'target_service_id' => $validated['target_service_id'] ?? null,
                'target_product_id' => $validated['target_product_id'] ?? null,
                'monetary_value' => $monetaryValue,
                'discount_percentage' => $discountPercentage,
                'category' => $validated['category'] ?? null,
                'active' => $validated['is_active'] ?? 1,
                'is_active' => $validated['is_active'] ?? 1,
                'created_by' => $staff->id,
                'created_by_type' => 'staff',
                'date_created' => now()
            ]);

            Log::info('Item created', ['id' => $item->id, 'monetary_value' => $monetaryValue, 'discount_percentage' => $discountPercentage]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Redeemable item created successfully',
                'data' => $item->load([
                    'branch', 
                    'targetService', 
                    'targetService.serviceCategory',
                    'targetProduct',
                    'targetProduct.productIngredients',
                    'targetProduct.productIngredients.ingredient'
                ])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating item: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified redeemable item.
     */
    public function update(Request $request, $id)
    {
        try {
            Log::info('Staff Update method called', ['id' => $id, 'data' => $request->all()]);

            // Validate based on reward type
            $rules = [
                'item_name' => 'required|string|max:255',
                'reward_type' => 'required|in:free_service,free_product,fixed_discount,percentage_discount',
                'item_description' => 'nullable|string',
                'category' => 'nullable|string|max:255',
                'branch_id' => 'nullable|exists:branches,id',
                'is_active' => 'required|boolean',
            ];

            // Add type-specific validation
            if ($request->reward_type === 'free_service') {
                $rules['target_service_id'] = 'required|exists:service_names,id';
                $rules['monetary_value'] = 'required|numeric|min:0.01';
            } elseif ($request->reward_type === 'free_product') {
                $rules['target_product_id'] = 'required|exists:products,id';
                $rules['monetary_value'] = 'required|numeric|min:0.01';
            } elseif ($request->reward_type === 'fixed_discount') {
                $rules['monetary_value'] = 'required|numeric|min:0.01';
            } elseif ($request->reward_type === 'percentage_discount') {
                $rules['discount_percentage'] = 'required|numeric|min:0.01|max:100';
                $rules['monetary_value'] = 'nullable|numeric|min:0';
            }

            $validated = $request->validate($rules);

            Log::info('Validation passed for update', $validated);

            DB::beginTransaction();

            $staff = Auth::guard('staff')->user();
            $branchId = $staff->branch_id;
            
            if (!$staff) {
                return response()->json([
                    'success' => false,
                    'message' => 'Staff not authenticated'
                ], 401);
            }

            $item = RedeemableItem::where('owner_account_id', $staff->owner_account_id)
                ->whereNull('deleted_at')
                ->where(function($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
                })
                ->findOrFail($id);

            // Force branch to staff's branch if not specified
            $branchIdToUse = $validated['branch_id'] ?? $branchId;

            // For percentage discount, convert percentage to decimal and store in monetary_value
            $monetaryValue = $validated['monetary_value'] ?? null;
            $discountPercentage = $validated['discount_percentage'] ?? null;

            if ($validated['reward_type'] === 'percentage_discount' && $discountPercentage) {
                // Convert percentage to decimal (e.g., 10% -> 0.10)
                $monetaryValue = $discountPercentage / 100;
            }

            $item->update([
                'branch_id' => $branchIdToUse,
                'item_name' => $validated['item_name'],
                'item_description' => $validated['item_description'] ?? null,
                'reward_type' => $validated['reward_type'],
                'target_service_id' => $validated['target_service_id'] ?? null,
                'target_product_id' => $validated['target_product_id'] ?? null,
                'monetary_value' => $monetaryValue,
                'discount_percentage' => $discountPercentage,
                'category' => $validated['category'] ?? null,
                'active' => $validated['is_active'] ?? 1,
                'is_active' => $validated['is_active'] ?? 1,
                'last_updated_by' => $staff->id,
                'last_updated_by_type' => 'staff',
                'last_date_updated' => now()
            ]);

            Log::info('Item updated', ['id' => $item->id, 'monetary_value' => $monetaryValue, 'discount_percentage' => $discountPercentage]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Redeemable item updated successfully',
                'data' => $item->load([
                    'branch', 
                    'targetService', 
                    'targetService.serviceCategory',
                    'targetProduct',
                    'targetProduct.productIngredients',
                    'targetProduct.productIngredients.ingredient'
                ])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating item: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle the status of the specified redeemable item.
     */
    public function toggleStatus(Request $request, $id)
    {
        try {
            $staff = Auth::guard('staff')->user();
            $branchId = $staff->branch_id;

            $item = RedeemableItem::where('owner_account_id', $staff->owner_account_id)
                ->whereNull('deleted_at')
                ->where(function($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
                })
                ->findOrFail($id);

            $newStatus = !$item->active;

            $item->update([
                'active' => $newStatus,
                'is_active' => $newStatus,
                'last_updated_by' => $staff->id,
                'last_updated_by_type' => 'staff',
                'last_date_updated' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Item status toggled successfully',
                'data' => $item
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified redeemable item (soft delete).
     */
    public function destroy($id)
    {
        try {
            $staff = Auth::guard('staff')->user();
            $branchId = $staff->branch_id;

            $item = RedeemableItem::where('owner_account_id', $staff->owner_account_id)
                ->whereNull('deleted_at')
                ->where(function($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
                })
                ->findOrFail($id);

            // Check if item is linked to any loyalty tiers
            if ($item->loyaltyTiers()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this item as it is linked to one or more loyalty tiers'
                ], 400);
            }

            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Redeemable item deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get item data for editing.
     */
    public function getItemData($id)
    {
        try {
            $staff = Auth::guard('staff')->user();
            $branchId = $staff->branch_id;

            $item = RedeemableItem::where('owner_account_id', $staff->owner_account_id)
                ->whereNull('deleted_at')
                ->where(function($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
                })
                ->with([
                    'targetService', 
                    'targetService.serviceCategory',
                    'targetProduct',
                    'targetProduct.productIngredients',
                    'targetProduct.productIngredients.ingredient'
                ])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $item
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }
    }

    /**
     * Get items for dropdown.
     */
    public function getItemsForDropdown(Request $request)
    {
        $staff = Auth::guard('staff')->user();
        $branchId = $staff->branch_id;

        $query = RedeemableItem::where('owner_account_id', $staff->owner_account_id)
            ->where('active', true)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->where(function($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->orderBy('item_name');

        $items = $query->get(['id', 'item_name', 'reward_type', 'monetary_value', 'discount_percentage']);

        return response()->json([
            'success' => true,
            'data' => $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->item_name,
                    'type' => $item->reward_type,
                    'type_label' => $item->type_label,
                    'value_display' => $item->value_display,
                    'monetary_value' => $item->monetary_value
                ];
            })
        ]);
    }

    /**
     * Get reward categories/types.
     */
    public function getCategories()
    {
        return response()->json([
            'success' => true,
            'data' => RedeemableItem::getRewardTypeOptions()
        ]);
    }

    /**
     * Apply reward to a booking or order
     */
    public function applyReward(Request $request, $id)
    {
        try {
            $staff = Auth::guard('staff')->user();
            $branchId = $staff->branch_id;
            
            $item = RedeemableItem::where('owner_account_id', $staff->owner_account_id)
                ->where('active', true)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->where(function($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
                })
                ->with([
                    'targetService', 
                    'targetService.serviceCategory',
                    'targetProduct',
                    'targetProduct.productIngredients',
                    'targetProduct.productIngredients.ingredient'
                ])
                ->findOrFail($id);

            $result = $item->applyReward();

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not apply this reward'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result,
                'reward' => $item,
                'monetary_value' => $item->monetary_value
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to apply reward: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service price for auto-fill
     */
    public function getServicePrice($id)
    {
        try {
            $staff = Auth::guard('staff')->user();
            
            $service = ServiceName::where('owner_account_id', $staff->owner_account_id)
                ->where('active', 1)
                ->where('branch_id', $staff->branch_id)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'price' => $service->price ?? 0,
                    'service_name' => $service->service_name,
                    'branch_id' => $service->branch_id
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get product price for auto-fill
     */
    public function getProductPrice($id)
    {
        try {
            $staff = Auth::guard('staff')->user();
            
            $product = Product::where('owner_account_id', $staff->owner_account_id)
                ->where('active', 1)
                ->where('branch_id', $staff->branch_id)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'price' => $product->selling_price ?? 0,
                    'product_name' => $product->product_name,
                    'branch_id' => $product->branch_id
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get items by branch for dropdown
     */
    public function getItemsByBranch(Request $request)
    {
        try {
            $staff = Auth::guard('staff')->user();
            $branchId = $staff->branch_id;
            
            $query = RedeemableItem::where('owner_account_id', $staff->owner_account_id)
                ->where('active', true)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->where(function($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
                })
                ->orderBy('item_name');

            $items = $query->get(['id', 'item_name', 'reward_type', 'monetary_value', 'discount_percentage']);

            return response()->json([
                'success' => true,
                'data' => $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->item_name,
                        'type' => $item->reward_type,
                        'type_label' => $item->type_label,
                        'value_display' => $item->value_display,
                        'monetary_value' => $item->monetary_value
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load items: ' . $e->getMessage()
            ], 500);
        }
    }
}