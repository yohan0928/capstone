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
    // DEFAULT BRANCH
    // Used to pre-fill Stock In / Stock Out forms so the owner
    // doesn't have to pick a branch every time. Falls back to the
    // first active branch alphabetically if "Claveria" isn't found.
    // ─────────────────────────────────────────────────────────────
    private function getDefaultBranch()
    {
        return Branch::where('active', 1)
            ->where('branch_name', 'like', '%Claveria%')
            ->first()
            ?? Branch::where('active', 1)->orderBy('branch_name')->first();
    }

    // ─────────────────────────────────────────────────────────────
    // FLATTEN ITEMS FOR DISPLAY
    // Turns each InventoryTransactionItem (with its `product` /
    // `ingredient` relations) into a plain array with a single
    // `product_name` (products) or `ingredient_name` (ingredients)
    // field, so blade/Alpine can render it directly — no separate
    // "details" fetch needed. Shared by the transactions log and by
    // the Stock In / Stock Out history pages.
    // ─────────────────────────────────────────────────────────────
    private function mapItemsForDisplay($items)
    {
        return $items->map(function ($item) {

            $isIngredient = $item->item_type === 'ingredient'
                || (!empty($item->ingredient_id) && empty($item->product_id));

            if ($isIngredient) {
                return [
                    'item_type'       => 'ingredient',
                    'ingredient_id'   => $item->ingredient_id,
                    'product_id'      => null,
                    'ingredient_name' => $item->ingredient?->ingredient_name ?? 'Unknown',
                    'product_name'    => null,
                    'quantity'        => $item->quantity,
                    'unit'            => $item->unit ?? $item->ingredient?->unit,
                    'reason'          => $item->reason,
                    'note'            => $item->note,
                ];
            }

            return [
                'item_type'       => $item->item_type ?? 'product',
                'product_id'      => $item->product_id,
                'ingredient_id'   => null,
                'product_name'    => $item->product?->product_name ?? 'Unknown',
                'ingredient_name' => null,
                'quantity'        => $item->quantity,
                'unit'            => $item->unit ?? $item->product?->unit,
                'reason'          => $item->reason,
                'note'            => $item->note,
            ];
        })->values();
    }

    // ─────────────────────────────────────────────────────────────
    // ANNOTATE TRANSACTIONS (items_count / total_quantity / display_qty)
    // Shared by the Inventory transactions log and the Stock In /
    // Stock Out history pages.
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

            // ── Same "displayed qty" rule as the Inventory Report page ──
            // Stock In: unchanged, total_quantity (real units received).
            // Stock Out: raw COUNT of line items, excluding MTO-drink rows
            // (product-type rows measured in "cup" — these represent the
            // drink itself, not a distinct stocked-out item; the ingredient
            // rows are what actually get counted).
            $txn->display_qty = $txn->type !== 'stock_out'
                ? $txn->total_quantity
                : $txn->items->reject(
                    fn($i) => $i->item_type === 'product' && $i->unit === 'cup'
                  )->count();

            // Branch name, flattened for the frontend so it doesn't need
            // to reach into a nested relation.
            $txn->branch_name = $txn->branch->branch_name ?? '—';

            // Flatten items last (after the counts above, which rely on
            // the raw item_type/ingredient_id/product_id/note fields) so
            // the frontend gets ready-to-render item rows without a
            // separate details() fetch.
            $txn->items = $this->mapItemsForDisplay($txn->items);

            return $txn;
        })->values();
    }

    // ─────────────────────────────────────────────────────────────
    // SUMMARY (monthly ledger totals — used by Inventory Report page)
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
    // Used by the Stock Levels page.
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
    // LOW STOCK ITEMS — used by the Stock Levels page.
    // ─────────────────────────────────────────────────────────────
    private function getLowStockItems(int $ownerAccountId)
    {
        return $this->getStockLevels($ownerAccountId)
            ->filter(fn($i) => $i['is_low'])
            ->values();
    }

    // ─────────────────────────────────────────────────────────────
    // INVENTORY PAGE — Stock Transactions log (all types, one list —
    // type/in-out tabs were removed from the frontend; this endpoint
    // still returns everything and the blade filters/searches client-side)
    // ─────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $owner = Auth::guard('owner')->user();
    
        $transactions = $this->annotateTransactions(
            InventoryTransaction::with(['items.product', 'items.ingredient', 'branch'])
                ->where('owner_account_id', $owner->id)
                ->where('active', 1)
                ->latest()
                ->limit(300)
                ->get()
        );
    
        $branches = Branch::where('active', 1)->orderBy('branch_name')->get(['id', 'branch_name']);
    
        if ($request->ajax()) {
            return response()->json([
                'success'      => true,
                'transactions' => $transactions,
            ]);
        }
    
        return view('owner.inventory.inventory', compact('transactions', 'branches'));
    }

    // ─────────────────────────────────────────────────────────────
    // STOCK IN HISTORY PAGE
    // All stock_in transactions, items already flattened for display —
    // the page renders item details inline (no modal / no details() call).
    // The Inventory page's eye icon links here with ?highlight={uuid}.
    // ─────────────────────────────────────────────────────────────
    public function stockInHistory(Request $request)
    {
        $owner = Auth::guard('owner')->user();
    
        $transactions = $this->annotateTransactions(
            InventoryTransaction::with(['items.product', 'items.ingredient', 'branch'])
                ->where('owner_account_id', $owner->id)
                ->where('type', 'stock_in')
                ->where('active', 1)
                ->latest()
                ->limit(300)
                ->get()
        );
    
        if ($request->ajax()) {
            return response()->json(['success' => true, 'transactions' => $transactions]);
        }
    
        $products = Product::where('owner_account_id', $owner->id)
            ->where('active', 1)
            ->whereNotIn('product_category', ['mto', 'made_to_order'])
            ->orderBy('product_name')
            ->get(['id', 'uuid', 'branch_id', 'product_name', 'unit']);
    
        $ingredients = Ingredient::where('owner_account_id', $owner->id)
            ->where('active', 1)
            ->orderBy('ingredient_name')
            ->get(['id', 'uuid', 'branch_id', 'ingredient_name', 'unit']);
    
        $defaultBranch    = $this->getDefaultBranch();
        $branches         = Branch::where('active', 1)->orderBy('branch_name')->get(['id', 'branch_name']);
        $processedByName  = $owner->first_name . ' ' . $owner->last_name;
    
        return view('owner.inventory.stock-in-history', compact(
            'transactions', 'branches', 'products', 'ingredients', 'defaultBranch', 'processedByName'
        ));
    }

    // STOCK OUT HISTORY PAGE
    // All stock_out transactions, items already flattened for display —
    // includes approved_by / rejected_reason so the page can show them
    // without a separate details() call.
    // The Inventory page's eye icon links here with ?highlight={uuid}.
    // ─────────────────────────────────────────────────────────────
    public function stockOutHistory(Request $request)
    {
        $owner = Auth::guard('owner')->user();
    
        $transactions = $this->annotateTransactions(
            InventoryTransaction::with(['items.product', 'items.ingredient', 'branch'])
                ->where('owner_account_id', $owner->id)
                ->where('type', 'stock_out')
                ->where('active', 1)
                ->latest()
                ->limit(300)
                ->get()
        );
    
        if ($request->ajax()) {
            return response()->json([
                'success'      => true,
                'transactions' => $transactions,
            ]);
        }
    
        $products = Product::where('owner_account_id', $owner->id)
            ->where('active', 1)
            ->whereNotIn('product_category', ['mto', 'made_to_order'])
            ->orderBy('product_name')
            ->get(['id', 'uuid', 'branch_id', 'product_name', 'unit']);
    
        $ingredients = Ingredient::where('owner_account_id', $owner->id)
            ->where('active', 1)
            ->orderBy('ingredient_name')
            ->get(['id', 'uuid', 'branch_id', 'ingredient_name', 'unit']);
    
        $defaultBranch    = $this->getDefaultBranch();
        $branches         = Branch::where('active', 1)->orderBy('branch_name')->get(['id', 'branch_name']);
        $processedByName  = $owner->first_name . ' ' . $owner->last_name;
    
        return view('owner.inventory.stock-out-history', compact(
            'transactions', 'branches', 'products', 'ingredients', 'defaultBranch', 'processedByName'
        ));
    }

    // ─────────────────────────────────────────────────────────────
    // STOCK LEVELS PAGE
    // ─────────────────────────────────────────────────────────────
    public function stockLevels(Request $request)
    {
        $owner = Auth::guard('owner')->user();

        $stockLevels   = $this->getStockLevels($owner->id);
        $newArrivals   = $this->getNewArrivals($owner->id);
        $lowStockItems = $this->getLowStockItems($owner->id);
        $branches      = Branch::where('active', 1)->orderBy('branch_name')->get();

        if ($request->ajax()) {
            return response()->json([
                'success'         => true,
                'stock_levels'    => $stockLevels,
                'new_arrivals'    => $newArrivals,
                'low_stock_items' => $lowStockItems,
            ]);
        }

        return view('owner.inventory.stock-levels', compact(
            'stockLevels',
            'newArrivals',
            'lowStockItems',
            'branches'
        ));
    }

    // ─────────────────────────────────────────────────────────────
    // STOCK IN PAGE
    // ─────────────────────────────────────────────────────────────
    public function stockInPage()
    {
        $owner = Auth::guard('owner')->user();
    
        $products = Product::where('owner_account_id', $owner->id)
            ->where('active', 1)
            ->whereNotIn('product_category', ['mto', 'made_to_order'])
            ->orderBy('product_name')
            ->get(['id', 'uuid', 'branch_id', 'product_name', 'unit']);
    
        $ingredients = Ingredient::where('owner_account_id', $owner->id)
            ->where('active', 1)
            ->orderBy('ingredient_name')
            ->get(['id', 'uuid', 'branch_id', 'ingredient_name', 'unit']);
    
        $defaultBranch = $this->getDefaultBranch();
        $branches      = Branch::where('active', 1)->orderBy('branch_name')->get();
    
        return view('owner.inventory.stock-in', compact('products', 'ingredients', 'defaultBranch', 'branches'));
    }

    // ─────────────────────────────────────────────────────────────
    // STOCK OUT PAGE
    // ─────────────────────────────────────────────────────────────
    public function stockOutPage()
    {
        $owner = Auth::guard('owner')->user();

        $products = Product::where('owner_account_id', $owner->id)
            ->where('active', 1)
            ->whereNotIn('product_category', ['mto', 'made_to_order'])
            ->orderBy('product_name')
            ->get(['id', 'uuid', 'branch_id', 'product_name', 'unit']);

        $ingredients = Ingredient::where('owner_account_id', $owner->id)
            ->where('active', 1)
            ->orderBy('ingredient_name')
            ->get(['id', 'uuid', 'branch_id', 'ingredient_name', 'unit']);

        $defaultBranch = $this->getDefaultBranch();
        $branches      = Branch::where('active', 1)->orderBy('branch_name')->get();

        return view('owner.inventory.stock-out', compact('products', 'ingredients', 'defaultBranch', 'branches'));
    }

    // ─────────────────────────────────────────────────────────────
    // DETAILS
    // Kept for backward compatibility (e.g. if anything else still
    // links to it) even though the Inventory page's eye icon no
    // longer opens a modal that calls this endpoint.
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

            $items = $this->mapItemsForDisplay($txn->items);

            return response()->json([
                'success'     => true,
                'transaction' => [
                    'uuid'             => $txn->uuid,
                    'transaction_no'   => $txn->transaction_no,
                    'type'             => $txn->type,
                    'status'           => $txn->status,
                    'branch_name'      => $txn->branch->branch_name ?? '—',
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