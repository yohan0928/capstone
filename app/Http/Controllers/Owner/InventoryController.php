<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Ingredient;
use App\Models\InventoryBatch;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\OwnerAccount;
use App\Models\Product;
use App\Models\StaffAccount;
use App\Notifications\Owner\ProductNotification;
use App\Notifications\Staff\ProductStaffNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class InventoryController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // ANNOTATE TRANSACTIONS (items_count / total_quantity)
    // Shared by the pending-approvals list.
    // ─────────────────────────────────────────────────────────────
    private function annotateTransactions($collection)
    {
        return $collection->map(function ($txn) {

            $isIngredientRow = fn($i) =>
                $i->item_type === 'ingredient'
                || (!empty($i->ingredient_id) && empty($i->product_id));

            $ingredientRows = $txn->items->filter($isIngredientRow);
            $productRows    = $txn->items->filter(fn($i) => !$isIngredientRow($i));

            $mtoIngredients = $ingredientRows->filter(
                fn($i) => $i->note && str_contains($i->note, 'MTO:')
            );

            $manualIngredients = $ingredientRows->filter(
                fn($i) => !($i->note && str_contains($i->note, 'MTO:'))
            );

            $mtoProducts = $mtoIngredients->mapWithKeys(function ($i) {
                preg_match('/MTO:\s*(.+?)\s*x(\d+)/i', $i->note, $m);
                $key = trim($m[1] ?? 'unknown') . '|' . ($m[2] ?? 1);
                return [$key => (int)($m[2] ?? 1)];
            });

            $txn->total_quantity = $productRows->sum('quantity')
                                 + $mtoProducts->sum()
                                 + $manualIngredients->sum('quantity');

            $txn->items_count    = $productRows->count()
                                 + $mtoProducts->count()
                                 + $manualIngredients->count();

            return $txn;
        })->values();
    }

    // ─────────────────────────────────────────────────────────────
    // SUMMARY (monthly ledger totals — unchanged)
    // ─────────────────────────────────────────────────────────────
    private function getSummary(int $ownerAccountId): array
    {
        $now   = Carbon::now();
        $start = $now->copy()->startOfMonth();
        $end   = $now->copy()->endOfMonth();

        $txns = InventoryTransaction::with('items')
            ->where('owner_account_id', $ownerAccountId)
            ->where('active', 1)
            ->whereIn('status', ['approved', 'done'])
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $totalIn          = 0;
        $totalSold        = 0;
        $totalDamaged     = 0;
        $totalExpired     = 0;
        $totalPulledOut   = 0;
        $stockInTxnCount  = 0;

        foreach ($txns as $txn) {

            if ($txn->type === 'stock_in') {

                foreach ($txn->items as $item) {
                    $totalIn += $item->quantity;
                }
                $stockInTxnCount++;

            } else {

                foreach ($txn->items as $item) {
                    $reason = $item->reason ?? $txn->reason;
                    $qty = $item->quantity;

                    match ($reason) {
                        'sold'       => $totalSold      += $qty,
                        'damaged'    => $totalDamaged   += $qty,
                        'expired'    => $totalExpired   += $qty,
                        'pulled_out' => $totalPulledOut += $qty,
                        default      => null,
                    };
                }
            }
        }

        $productBalance = Product::where('owner_account_id', $ownerAccountId)
            ->where('active', 1)
            ->sum('quantity_in');

        $ingredientBalance = Ingredient::where('owner_account_id', $ownerAccountId)
            ->where('active', 1)
            ->sum('stock_quantity_in');

        $endingBalance = $productBalance + $ingredientBalance;

        $totalOut         = $totalSold + $totalDamaged + $totalExpired + $totalPulledOut;
        $beginningBalance = $endingBalance - $totalIn + $totalOut;

        return [
            'beginning_balance'     => max(0, $beginningBalance),
            'total_stock_in'        => $totalIn,
            'stock_in_transactions' => $stockInTxnCount,
            'total_sold'            => $totalSold,
            'total_damaged'         => $totalDamaged,
            'total_expired'         => $totalExpired,
            'total_pulled_out'      => $totalPulledOut,
            'total_stock_out'       => $totalOut,
            'ending_balance'        => $endingBalance,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // STOCK LEVELS (products + ingredients, current on-hand qty)
    // Tri-color status:
    //   low    -> quantity below threshold      (red)
    //   medium -> quantity exactly at threshold  (yellow)
    //   high   -> quantity above threshold, or no threshold set (green)
    // ─────────────────────────────────────────────────────────────
    private function getStockLevels(int $ownerAccountId)
    {
        $classifyStatus = function (float $qty, ?float $threshold): string {
            if ($threshold === null) {
                return 'high';
            }
            if ($qty < $threshold) {
                return 'low';
            }
            if ($qty == $threshold) {
                return 'medium';
            }
            return 'high';
        };

        $products = Product::with('branch')
            ->where('owner_account_id', $ownerAccountId)
            ->where('active', 1)
            ->whereNotIn('product_category', ['mto', 'made_to_order'])
            ->orderBy('product_name')
            ->get()
            ->map(function ($p) use ($classifyStatus) {
                $threshold = $p->quantity_threshold !== null ? (float) $p->quantity_threshold : null;
                $quantity  = (float) $p->quantity_in;
                $status    = $classifyStatus($quantity, $threshold);

                return [
                    'uuid'      => $p->uuid,
                    'id'        => $p->id,
                    'item_type' => 'product',
                    'name'      => $p->product_name,
                    'branch'    => $p->branch->branch_name ?? '—',
                    'branch_id' => $p->branch_id,
                    'quantity'  => $quantity,
                    'unit'      => $p->unit,
                    'threshold' => $threshold,
                    'status'    => $status,       // 'low' | 'medium' | 'high'
                    'is_low'    => $status === 'low',
                ];
            });

        $ingredients = Ingredient::with('branch')
            ->where('owner_account_id', $ownerAccountId)
            ->where('active', 1)
            ->orderBy('ingredient_name')
            ->get()
            ->map(function ($i) use ($classifyStatus) {
                $threshold = $i->stock_quantity_threshold !== null ? (float) $i->stock_quantity_threshold : null;
                $quantity  = (float) $i->stock_quantity_in;
                $status    = $classifyStatus($quantity, $threshold);

                return [
                    'uuid'      => $i->uuid,
                    'id'        => $i->id,
                    'item_type' => 'ingredient',
                    'name'      => $i->ingredient_name,
                    'branch'    => $i->branch->branch_name ?? '—',
                    'branch_id' => $i->branch_id,
                    'quantity'  => $quantity,
                    'unit'      => $i->unit,
                    'threshold' => $threshold,
                    'status'    => $status,
                    'is_low'    => $status === 'low',
                ];
            });

        return $products->concat($ingredients)->values();
    }

    // ─────────────────────────────────────────────────────────────
    // NEW ARRIVALS (stock-in transaction items from the last 7 days)
    // ─────────────────────────────────────────────────────────────
    private function getNewArrivals(int $ownerAccountId)
    {
        $since = Carbon::now()->subDays(7);

        $items = InventoryTransactionItem::whereHas('inventoryTransaction', function ($q) use ($ownerAccountId, $since) {
                $q->where('owner_account_id', $ownerAccountId)
                  ->where('type', 'stock_in')
                  ->where('active', 1)
                  ->where('created_at', '>=', $since);
            })
            ->with(['product', 'ingredient', 'inventoryTransaction'])
            ->get();

        return $items
            ->groupBy(function ($item) {
                $isIngredient = $item->item_type === 'ingredient'
                    || (!empty($item->ingredient_id) && empty($item->product_id));
                return ($isIngredient ? 'ingredient' : 'product') . '-' . ($item->ingredient_id ?? $item->product_id);
            })
            ->map(function ($group) {
                $first = $group->first();
                $isIngredient = $first->item_type === 'ingredient'
                    || (!empty($first->ingredient_id) && empty($first->product_id));

                return [
                    'item_type'         => $isIngredient ? 'ingredient' : 'product',
                    'name'              => $isIngredient
                        ? ($first->ingredient->ingredient_name ?? 'Unknown')
                        : ($first->product->product_name ?? 'Unknown'),
                    'quantity'          => $group->sum('quantity'),
                    'unit'              => $first->unit,
                    'last_received_at'  => $group->max(fn($i) => optional($i->inventoryTransaction)->created_at),
                ];
            })
            ->values();
    }

    // ─────────────────────────────────────────────────────────────
    // LOW STOCK ITEMS
    // ─────────────────────────────────────────────────────────────
    private function getLowStockItems(int $ownerAccountId)
    {
        return $this->getStockLevels($ownerAccountId)
            ->filter(fn($i) => $i['is_low'])
            ->values();
    }

    // ─────────────────────────────────────────────────────────────
    // PENDING + RECENTLY RESOLVED TRANSACTIONS
    // Shows all pending transactions, plus approved/rejected ones
    // resolved within the last N days, so staff can see the outcome
    // inline in the table instead of the row just disappearing.
    // ─────────────────────────────────────────────────────────────
    private function getPendingTransactions(int $ownerAccountId, int $resolvedWithinDays = 3, int $resolvedLimit = 15)
    {
        $pending = InventoryTransaction::with(['items.product', 'items.ingredient', 'branch'])
            ->where('owner_account_id', $ownerAccountId)
            ->where('status', 'pending')
            ->where('active', 1)
            ->latest()
            ->get();

        $resolved = InventoryTransaction::with(['items.product', 'items.ingredient', 'branch'])
            ->where('owner_account_id', $ownerAccountId)
            ->whereIn('status', ['approved', 'rejected'])
            ->where('active', 1)
            ->where('approved_at', '>=', Carbon::now()->subDays($resolvedWithinDays))
            ->orderByDesc('approved_at')
            ->limit($resolvedLimit)
            ->get();

        $combined = $pending->concat($resolved)
            ->sortByDesc(fn($txn) => $txn->status === 'pending' ? $txn->created_at : $txn->approved_at)
            ->values();

        return $this->annotateTransactions($combined);
    }

    // ─────────────────────────────────────────────────────────────
    // INDEX
    // ─────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $owner = Auth::guard('owner')->user();

        $summary             = $this->getSummary($owner->id);
        $stockLevels         = $this->getStockLevels($owner->id);
        $newArrivals         = $this->getNewArrivals($owner->id);
        $lowStockItems       = $this->getLowStockItems($owner->id);
        $pendingTransactions = $this->getPendingTransactions($owner->id);
        $pendingCount        = $pendingTransactions->where('status', 'pending')->count();

        // Products (exclude MTO) — used to populate Stock In / Stock Out selectors
        $products = Product::with('branch')
            ->where('owner_account_id', $owner->id)
            ->where('active', 1)
            ->whereNotIn('product_category', ['mto', 'made_to_order'])
            ->orderBy('product_name')
            ->get(['id', 'uuid', 'branch_id', 'product_name', 'unit', 'quantity_in', 'quantity_threshold']);

        // Ingredients
        $ingredients = Ingredient::with('branch')
            ->where('owner_account_id', $owner->id)
            ->where('active', 1)
            ->orderBy('ingredient_name')
            ->get([
                'id',
                'uuid',
                'branch_id',
                'ingredient_name',
                'unit',
                'stock_quantity_in',
                'stock_quantity_threshold',
            ]);

        $branches = Branch::where('active', 1)
            ->orderBy('branch_name')
            ->get();

        $periodLabel = now()->format('F Y');

        $stats = [
            'total_transactions' => InventoryTransaction::where('owner_account_id', $owner->id)
                ->where('active', 1)->count(),

            'approved_transactions' => InventoryTransaction::where('owner_account_id', $owner->id)
                ->where('status', 'approved')->where('active', 1)->count(),

            'pending_transactions' => $pendingCount,

            'rejected_transactions' => InventoryTransaction::where('owner_account_id', $owner->id)
                ->where('status', 'rejected')->where('active', 1)->count(),
        ];

        if ($request->ajax()) {
            return response()->json([
                'success'              => true,
                'summary'              => $summary,
                'stats'                => $stats,
                'pending_count'        => $pendingCount,
                'pending_transactions' => $pendingTransactions,
                'stock_levels'         => $stockLevels,
                'new_arrivals'         => $newArrivals,
                'low_stock_items'      => $lowStockItems,
                'products'             => $products,
                'ingredients'          => $ingredients,
                'branches'             => $branches,
            ]);
        }

        return view('owner.product.inventory', compact(
            'summary',
            'pendingCount',
            'pendingTransactions',
            'stockLevels',
            'newArrivals',
            'lowStockItems',
            'products',
            'ingredients',
            'branches',
            'periodLabel',
            'stats'
        ));
    }

    // ─────────────────────────────────────────────────────────────
    // DETAILS
    // ─────────────────────────────────────────────────────────────
    public function details(string $uuid)
    {
        try {
            $txn = InventoryTransaction::with([
                    'items.product',
                    'items.ingredient',
                    'branch',
                ])
                ->where('uuid', $uuid)
                ->where('owner_account_id', Auth::guard('owner')->id())
                ->firstOrFail();

            $items = $txn->items->map(function ($item) {

                if ($item->item_type === 'ingredient' || (!empty($item->ingredient_id) && empty($item->product_id))) {
                    return [
                        'item_type'       => 'ingredient',
                        'ingredient_name' => $item->ingredient?->ingredient_name ?? 'Unknown',
                        'product_name'    => null,
                        'quantity'        => $item->quantity,
                        'unit'            => $item->unit ?? $item->ingredient?->unit,
                        'reason'          => $item->reason,
                        'note'            => $item->note,
                    ];
                }

                return [
                    'item_type'         => $item->item_type ?? 'product',
                    'product_name'      => $item->product?->product_name ?? 'Unknown',
                    'quantity'          => $item->quantity,
                    'base_quantity'     => null,
                    'unit'              => $item->unit ?? $item->product?->unit,
                    'received_unit'     => null,
                    'conversion_factor' => null,
                    'reason'            => $item->reason,
                    'note'              => $item->note,
                ];
            });

            return response()->json([
                'success'     => true,
                'transaction' => [
                    'uuid'             => $txn->uuid,
                    'transaction_no'   => $txn->transaction_no,
                    'type'             => $txn->type,
                    'status'           => $txn->status,
                    'reason'           => $txn->reason,
                    'processed_by'     => $txn->processed_by,
                    'approved_by'      => $txn->approved_by,
                    'rejected_reason'  => $txn->rejected_reason,
                    'created_at'       => $txn->created_at,
                    'items'            => $items,
                    'items_count'      => $items->count(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.',
            ], 404);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // OWNER STOCK IN  (auto-approved for both products & ingredients)
    // ─────────────────────────────────────────────────────────────
    public function storeStockIn(Request $request)
    {
        $validated = $request->validate([
            'products'                         => 'required|array|min:1',
            'products.*.branch_id'             => 'required|exists:branches,id',
            'products.*.item_type'             => 'required|in:product,ingredient',
            'products.*.product_id'            => 'nullable|exists:products,id',
            'products.*.ingredient_id'         => 'nullable|exists:ingredients,id',
            'products.*.quantity'              => 'required|numeric|min:1',
            'products.*.note'                  => 'nullable|string|max:500',
        ]);

        foreach ($validated['products'] as $index => $item) {
            if ($item['item_type'] === 'product' && empty($item['product_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => "Row " . ($index + 1) . ": product_id is required for product items.",
                ], 422);
            }
            if ($item['item_type'] === 'ingredient' && empty($item['ingredient_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => "Row " . ($index + 1) . ": ingredient_id is required for ingredient items.",
                ], 422);
            }
        }

        $owner = Auth::guard('owner')->user();

        DB::beginTransaction();
        try {
            $transaction = InventoryTransaction::create([
                'owner_account_id'  => $owner->id,
                'branch_id'         => $validated['products'][0]['branch_id'],
                'transaction_no'    => 'INV-IN-' . now()->format('YmdHis'),
                'type'              => 'stock_in',
                'status'            => 'approved',
                'processed_by'      => $owner->first_name . ' ' . $owner->last_name,
                'processed_by_type' => 'owner',
                'approved_by_id'    => $owner->id,
                'approved_by'       => $owner->first_name . ' ' . $owner->last_name,
                'approved_at'       => now(),
                'active'            => 1,
            ]);

            foreach ($validated['products'] as $item) {

                if ($item['item_type'] === 'ingredient') {

                    $ingredient = Ingredient::findOrFail($item['ingredient_id']);
                    $ingredient->increment('stock_quantity_in', $item['quantity']);

                    InventoryTransactionItem::create([
                        'inventory_transaction_id' => $transaction->id,
                        'item_type'                => 'ingredient',
                        'ingredient_id'            => $ingredient->id,
                        'quantity'                 => $item['quantity'],
                        'unit'                     => $ingredient->unit,
                        'note'                     => $item['note'] ?? null,
                    ]);

                    InventoryBatch::create([
                        'owner_account_id'         => $owner->id,
                        'branch_id'                => $item['branch_id'],
                        'item_type'                => 'ingredient',
                        'ingredient_id'            => $ingredient->id,
                        'inventory_transaction_id' => $transaction->id,
                        'quantity_received'        => $item['quantity'],
                        'quantity_remaining'       => $item['quantity'],
                        'unit'                     => $ingredient->unit,
                        'note'                     => $item['note'] ?? null,
                        'received_at'              => now(),
                    ]);

                } else {

                    $product = Product::findOrFail($item['product_id']);
                    $product->increment('quantity_in', $item['quantity']);

                    InventoryTransactionItem::create([
                        'inventory_transaction_id' => $transaction->id,
                        'item_type'                => 'product',
                        'product_id'               => $product->id,
                        'quantity'                 => $item['quantity'],
                        'unit'                     => $product->unit,
                        'note'                     => $item['note'] ?? null,
                    ]);

                    InventoryBatch::create([
                        'owner_account_id'         => $owner->id,
                        'branch_id'                => $item['branch_id'],
                        'item_type'                => 'product',
                        'product_id'               => $product->id,
                        'inventory_transaction_id' => $transaction->id,
                        'quantity_received'        => $item['quantity'],
                        'quantity_remaining'       => $item['quantity'],
                        'unit'                     => $product->unit,
                        'note'                     => $item['note'] ?? null,
                        'received_at'              => now(),
                    ]);
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to save stock in: ' . $e->getMessage(),
            ], 500);
        }

        $this->sendInventoryNotification($transaction, 'stock_in');

        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────
    // OWNER STOCK OUT  (auto-approved; owner only hits this route)
    // ─────────────────────────────────────────────────────────────
    public function storeStockOut(Request $request)
    {
        $validated = $request->validate([
            'products'                     => 'required|array|min:1',
            'products.*.branch_id'         => 'required|exists:branches,id',
            'products.*.item_type'         => 'required|in:product,ingredient',
            'products.*.product_id'        => 'nullable|exists:products,id',
            'products.*.ingredient_id'     => 'nullable|exists:ingredients,id',
            'products.*.quantity'          => 'required|numeric|min:1',
            'products.*.reason'            => 'required|string|max:255',
            'products.*.note'              => 'nullable|string|max:500',
        ]);

        foreach ($validated['products'] as $index => $item) {
            if ($item['item_type'] === 'product' && empty($item['product_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => "Row " . ($index + 1) . ": product_id is required.",
                ], 422);
            }
            if ($item['item_type'] === 'ingredient' && empty($item['ingredient_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => "Row " . ($index + 1) . ": ingredient_id is required.",
                ], 422);
            }
        }

        $owner = Auth::guard('owner')->user();

        foreach ($validated['products'] as $index => $item) {
            if ($item['item_type'] === 'ingredient') {
                $ingredient = Ingredient::find($item['ingredient_id']);
                if (!$ingredient || $ingredient->stock_quantity_in < $item['quantity']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient stock for ingredient: ' . ($ingredient->ingredient_name ?? 'Unknown'),
                    ], 422);
                }
            } else {
                $product = Product::find($item['product_id']);
                if (!$product || $product->quantity_in < $item['quantity']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient stock for ' . ($product->product_name ?? 'Unknown'),
                    ], 422);
                }
            }
        }

        DB::beginTransaction();
        try {
            $transaction = InventoryTransaction::create([
                'owner_account_id'  => $owner->id,
                'branch_id'         => $validated['products'][0]['branch_id'],
                'transaction_no'    => 'INV-OUT-' . now()->format('YmdHis'),
                'type'              => 'stock_out',
                'status'            => 'approved',
                'processed_by'      => $owner->first_name . ' ' . $owner->last_name,
                'processed_by_type' => 'owner',
                'approved_by_id'    => $owner->id,
                'approved_by'       => $owner->first_name . ' ' . $owner->last_name,
                'approved_at'       => now(),
                'active'            => 1,
            ]);

            foreach ($validated['products'] as $item) {

                if ($item['item_type'] === 'ingredient') {

                    $ingredient = Ingredient::findOrFail($item['ingredient_id']);
                    $ingredient->decrement('stock_quantity_in', $item['quantity']);

                    InventoryTransactionItem::create([
                        'inventory_transaction_id' => $transaction->id,
                        'item_type'                => 'ingredient',
                        'ingredient_id'            => $ingredient->id,
                        'quantity'                 => $item['quantity'],
                        'unit'                     => $ingredient->unit,
                        'reason'                   => $item['reason'],
                        'note'                     => $item['note'] ?? null,
                    ]);

                    $this->deductFifo(
                        ownerAccountId: $owner->id,
                        branchId:       $item['branch_id'],
                        itemType:       'ingredient',
                        itemId:         $ingredient->id,
                        quantity:       (float) $item['quantity'],
                    );

                } else {

                    $product = Product::findOrFail($item['product_id']);
                    $product->decrement('quantity_in', $item['quantity']);

                    InventoryTransactionItem::create([
                        'inventory_transaction_id' => $transaction->id,
                        'item_type'                => 'product',
                        'product_id'               => $product->id,
                        'quantity'                 => $item['quantity'],
                        'unit'                     => $product->unit,
                        'reason'                   => $item['reason'],
                        'note'                     => $item['note'] ?? null,
                    ]);

                    $this->deductFifo(
                        ownerAccountId: $owner->id,
                        branchId:       $item['branch_id'],
                        itemType:       'product',
                        itemId:         $product->id,
                        quantity:       (float) $item['quantity'],
                    );
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to save stock out: ' . $e->getMessage(),
            ], 500);
        }

        $this->sendInventoryNotification($transaction, 'stock_out');

        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────
    // FIFO DEDUCTION HELPER
    // ─────────────────────────────────────────────────────────────
    private function deductFifo(int $ownerAccountId, int $branchId, string $itemType, int $itemId, float $quantity): void
    {
        $remaining = $quantity;

        $batches = InventoryBatch::where('owner_account_id', $ownerAccountId)
            ->where('branch_id', $branchId)
            ->where('item_type', $itemType)
            ->where($itemType === 'product' ? 'product_id' : 'ingredient_id', $itemId)
            ->available()
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            if ($batch->quantity_remaining <= $remaining) {
                $remaining -= $batch->quantity_remaining;
                $batch->quantity_remaining = 0;
            } else {
                $batch->quantity_remaining -= $remaining;
                $remaining = 0;
            }

            $batch->save();
        }

        if ($remaining > 0) {
            \Log::warning('FIFO deduction: batch stock insufficient', [
                'item_type' => $itemType,
                'item_id'   => $itemId,
                'shortage'  => $remaining,
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // APPROVE PENDING TRANSACTION
    // ─────────────────────────────────────────────────────────────
    public function approve(string $uuid)
    {
        $owner = Auth::guard('owner')->user();

        $transaction = InventoryTransaction::with('items')
            ->where('uuid', $uuid)
            ->where('owner_account_id', $owner->id)
            ->where('status', 'pending')
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Pending transaction not found.',
            ], 404);
        }

        DB::beginTransaction();
        try {
            foreach ($transaction->items as $item) {

                $isIngredient = $item->item_type === 'ingredient'
                    || (!empty($item->ingredient_id) && empty($item->product_id));

                if ($isIngredient) {
                    $ingredient = Ingredient::findOrFail($item->ingredient_id);

                    if ($transaction->type === 'stock_in') {
                        $ingredient->increment('stock_quantity_in', $item->quantity);

                        InventoryBatch::create([
                            'owner_account_id'         => $owner->id,
                            'branch_id'                => $transaction->branch_id,
                            'item_type'                => 'ingredient',
                            'ingredient_id'            => $ingredient->id,
                            'inventory_transaction_id' => $transaction->id,
                            'quantity_received'        => $item->quantity,
                            'quantity_remaining'       => $item->quantity,
                            'unit'                     => $ingredient->unit,
                            'note'                     => $item->note,
                            'received_at'              => now(),
                        ]);
                    } else {
                        if ($ingredient->stock_quantity_in < $item->quantity) {
                            throw new \Exception("Insufficient stock for ingredient: {$ingredient->ingredient_name}");
                        }
                        $ingredient->decrement('stock_quantity_in', $item->quantity);

                        $this->deductFifo(
                            ownerAccountId: $owner->id,
                            branchId:       $transaction->branch_id,
                            itemType:       'ingredient',
                            itemId:         $ingredient->id,
                            quantity:       (float) $item->quantity,
                        );
                    }

                } else {
                    $product = Product::findOrFail($item->product_id);

                    if ($transaction->type === 'stock_in') {
                        $product->increment('quantity_in', $item->quantity);

                        InventoryBatch::create([
                            'owner_account_id'         => $owner->id,
                            'branch_id'                => $transaction->branch_id,
                            'item_type'                => 'product',
                            'product_id'               => $product->id,
                            'inventory_transaction_id' => $transaction->id,
                            'quantity_received'        => $item->quantity,
                            'quantity_remaining'       => $item->quantity,
                            'unit'                     => $product->unit,
                            'note'                     => $item->note,
                            'received_at'              => now(),
                        ]);
                    } else {
                        if ($product->quantity_in < $item->quantity) {
                            throw new \Exception("Insufficient stock for {$product->product_name}");
                        }
                        $product->decrement('quantity_in', $item->quantity);

                        $this->deductFifo(
                            ownerAccountId: $owner->id,
                            branchId:       $transaction->branch_id,
                            itemType:       'product',
                            itemId:         $product->id,
                            quantity:       (float) $item->quantity,
                        );
                    }
                }
            }

            $transaction->update([
                'status'         => 'approved',
                'approved_by_id' => $owner->id,
                'approved_by'    => $owner->first_name . ' ' . $owner->last_name,
                'approved_at'    => now(),
            ]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve transaction: ' . $e->getMessage(),
            ], 500);
        }

        $this->sendInventoryNotification($transaction, 'approved');

        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────
    // REJECT PENDING TRANSACTION
    // ─────────────────────────────────────────────────────────────
    public function reject(Request $request, string $uuid)
    {
        $request->validate([
            'rejected_reason' => 'required|string|max:500',
        ]);

        $owner = Auth::guard('owner')->user();

        $transaction = InventoryTransaction::where('uuid', $uuid)
            ->where('owner_account_id', $owner->id)
            ->where('status', 'pending')
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Pending transaction not found.',
            ], 404);
        }

        $transaction->update([
            'status'           => 'rejected',
            'rejected_reason'  => $request->rejected_reason,
            'approved_by_id'   => $owner->id,
            'approved_by'      => $owner->first_name . ' ' . $owner->last_name,
            'approved_at'      => now(),
        ]);

        $this->sendInventoryNotification($transaction, 'rejected');

        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────
    // GET TRANSACTION DATA
    // ─────────────────────────────────────────────────────────────
    public function getData(string $uuid)
    {
        try {
            $owner = Auth::guard('owner')->user();

            $transaction = InventoryTransaction::with([
                    'items.product',
                    'items.ingredient',
                    'branch',
                ])
                ->where('uuid', $uuid)
                ->where('owner_account_id', $owner->id)
                ->firstOrFail();

            return response()->json([
                'success'     => true,
                'transaction' => $transaction,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.',
            ], 404);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // SEND NOTIFICATIONS
    // ─────────────────────────────────────────────────────────────
    private function sendInventoryNotification($transaction, $action)
    {
        try {
            $actor  = Auth::guard('owner')->user();
            $owners = OwnerAccount::where('id', $actor->id)->get();
            $branch = $transaction->branch;

            Notification::send(
                $owners,
                new ProductNotification($transaction, $branch, $actor, $action)
            );

            $staffMembers = StaffAccount::where('branch_id', $transaction->branch_id)
                ->where('owner_account_id', $actor->id)
                ->where('active', 1)
                ->get();

            Notification::send(
                $staffMembers,
                new ProductStaffNotification($transaction, $branch, $actor, $action)
            );

        } catch (\Exception $e) {
            //
        }
    }
}