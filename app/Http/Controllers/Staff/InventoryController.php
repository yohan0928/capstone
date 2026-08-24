<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Ingredient;
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
    // SUMMARY (scoped to the staff member's branch)
    // ─────────────────────────────────────────────────────────────
    private function getSummary(int $ownerAccountId, int $branchId): array
    {
        $now   = Carbon::now();
        $start = $now->copy()->startOfMonth();
        $end   = $now->copy()->endOfMonth();

        $txns = InventoryTransaction::with('items')
            ->where('owner_account_id', $ownerAccountId)
            ->where('branch_id', $branchId)
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
            ->where('branch_id', $branchId)
            ->where('active', 1)
            ->sum('quantity_in');

        $ingredientBalance = Ingredient::where('owner_account_id', $ownerAccountId)
            ->where('branch_id', $branchId)
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
    // STOCK LEVELS (own branch only)
    // ─────────────────────────────────────────────────────────────
    private function getStockLevels(int $ownerAccountId, int $branchId)
    {
        $classifyStatus = function (float $qty, ?float $threshold): string {
            if ($threshold === null) return 'high';
            if ($qty < $threshold)   return 'low';
            if ($qty == $threshold)  return 'medium';
            return 'high';
        };

        $products = Product::with('branch')
            ->where('owner_account_id', $ownerAccountId)
            ->where('branch_id', $branchId)
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
                    'status'    => $status,
                    'is_low'    => $status === 'low',
                ];
            });

        $ingredients = Ingredient::with('branch')
            ->where('owner_account_id', $ownerAccountId)
            ->where('branch_id', $branchId)
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
    // NEW ARRIVALS (own branch, last 7 days)
    // ─────────────────────────────────────────────────────────────
    private function getNewArrivals(int $ownerAccountId, int $branchId)
    {
        $since = Carbon::now()->subDays(7);

        $items = InventoryTransactionItem::whereHas('inventoryTransaction', function ($q) use ($ownerAccountId, $branchId, $since) {
                $q->where('owner_account_id', $ownerAccountId)
                  ->where('branch_id', $branchId)
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
                    'item_type'        => $isIngredient ? 'ingredient' : 'product',
                    'name'             => $isIngredient
                        ? ($first->ingredient->ingredient_name ?? 'Unknown')
                        : ($first->product->product_name ?? 'Unknown'),
                    'quantity'         => $group->sum('quantity'),
                    'unit'             => $first->unit,
                    'last_received_at' => $group->max(fn($i) => optional($i->inventoryTransaction)->created_at),
                ];
            })
            ->values();
    }

    // ─────────────────────────────────────────────────────────────
    // LOW STOCK ITEMS
    // ─────────────────────────────────────────────────────────────
    private function getLowStockItems(int $ownerAccountId, int $branchId)
    {
        return $this->getStockLevels($ownerAccountId, $branchId)
            ->filter(fn($i) => $i['is_low'])
            ->values();
    }

    // ─────────────────────────────────────────────────────────────
    // PENDING + RECENTLY RESOLVED TRANSACTIONS (view-only for staff;
    // own branch). Doubles as a stock in/out history log — staff can
    // see the outcome (approved/rejected) and reason for each of
    // their own declarations, not just the ones still pending.
    // ─────────────────────────────────────────────────────────────
    private function getPendingTransactions(int $ownerAccountId, int $branchId, int $resolvedWithinDays = 14, int $resolvedLimit = 50)
    {
        $pending = InventoryTransaction::with(['items.product', 'items.ingredient', 'branch'])
            ->where('owner_account_id', $ownerAccountId)
            ->where('branch_id', $branchId)
            ->where('status', 'pending')
            ->where('active', 1)
            ->latest()
            ->get();

        $resolved = InventoryTransaction::with(['items.product', 'items.ingredient', 'branch'])
            ->where('owner_account_id', $ownerAccountId)
            ->where('branch_id', $branchId)
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
        $staff     = Auth::guard('staff')->user();
        $ownerId   = $staff->owner_account_id;
        $branchId  = $staff->branch_id;

        $summary             = $this->getSummary($ownerId, $branchId);
        $stockLevels         = $this->getStockLevels($ownerId, $branchId);
        $newArrivals         = $this->getNewArrivals($ownerId, $branchId);
        $lowStockItems       = $this->getLowStockItems($ownerId, $branchId);
        $pendingTransactions = $this->getPendingTransactions($ownerId, $branchId);
        $pendingCount        = $pendingTransactions->where('status', 'pending')->count();

        $products = Product::where('owner_account_id', $ownerId)
            ->where('branch_id', $branchId)
            ->where('active', 1)
            ->whereNotIn('product_category', ['mto', 'made_to_order'])
            ->orderBy('product_name')
            ->get(['id', 'uuid', 'branch_id', 'product_name', 'unit', 'quantity_in', 'quantity_threshold']);

        $ingredients = Ingredient::where('owner_account_id', $ownerId)
            ->where('branch_id', $branchId)
            ->where('active', 1)
            ->orderBy('ingredient_name')
            ->get(['id', 'uuid', 'branch_id', 'ingredient_name', 'unit', 'stock_quantity_in', 'stock_quantity_threshold']);

        // Staff only operates within their own branch — no branch picker needed
        $branches = Branch::where('id', $branchId)->get();

        $periodLabel = now()->format('F Y');

        $stats = [
            'total_transactions' => InventoryTransaction::where('owner_account_id', $ownerId)
                ->where('branch_id', $branchId)->where('active', 1)->count(),

            'approved_transactions' => InventoryTransaction::where('owner_account_id', $ownerId)
                ->where('branch_id', $branchId)->where('status', 'approved')->where('active', 1)->count(),

            'pending_transactions' => $pendingCount,

            'rejected_transactions' => InventoryTransaction::where('owner_account_id', $ownerId)
                ->where('branch_id', $branchId)->where('status', 'rejected')->where('active', 1)->count(),
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

        return view('staff.product.inventory', compact(
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
    // DETAILS (own branch's transactions only)
    // ─────────────────────────────────────────────────────────────
    public function details(string $uuid)
    {
        try {
            $staff = Auth::guard('staff')->user();

            $txn = InventoryTransaction::with(['items.product', 'items.ingredient', 'branch'])
                ->where('uuid', $uuid)
                ->where('owner_account_id', $staff->owner_account_id)
                ->where('branch_id', $staff->branch_id)
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
                    'item_type'    => $item->item_type ?? 'product',
                    'product_name' => $item->product?->product_name ?? 'Unknown',
                    'quantity'     => $item->quantity,
                    'unit'         => $item->unit ?? $item->product?->unit,
                    'reason'       => $item->reason,
                    'note'         => $item->note,
                ];
            });

            return response()->json([
                'success'     => true,
                'transaction' => [
                    'uuid'            => $txn->uuid,
                    'transaction_no'  => $txn->transaction_no,
                    'type'            => $txn->type,
                    'status'          => $txn->status,
                    'reason'          => $txn->reason,
                    'processed_by'    => $txn->processed_by,
                    'approved_by'     => $txn->approved_by,
                    'rejected_reason' => $txn->rejected_reason,
                    'created_at'      => $txn->created_at,
                    'items'           => $items,
                    'items_count'     => $items->count(),
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
    // STAFF STOCK IN  →  always pending, no quantity change yet
    // ─────────────────────────────────────────────────────────────
    public function storeStockIn(Request $request)
    {
        $validated = $request->validate([
            'products'                 => 'required|array|min:1',
            'products.*.item_type'     => 'required|in:product,ingredient',
            'products.*.product_id'    => 'nullable|exists:products,id',
            'products.*.ingredient_id' => 'nullable|exists:ingredients,id',
            'products.*.quantity'      => 'required|numeric|min:1',
            'products.*.note'          => 'nullable|string|max:500',
        ]);

        foreach ($validated['products'] as $index => $item) {
            if ($item['item_type'] === 'product' && empty($item['product_id'])) {
                return response()->json(['success' => false, 'message' => "Row " . ($index + 1) . ": product_id is required."], 422);
            }
            if ($item['item_type'] === 'ingredient' && empty($item['ingredient_id'])) {
                return response()->json(['success' => false, 'message' => "Row " . ($index + 1) . ": ingredient_id is required."], 422);
            }
        }

        $staff = Auth::guard('staff')->user();

        DB::beginTransaction();
        try {
            $transaction = InventoryTransaction::create([
                'owner_account_id'  => $staff->owner_account_id,
                'branch_id'         => $staff->branch_id,
                'transaction_no'    => 'INV-IN-' . now()->format('YmdHis'),
                'type'              => 'stock_in',
                'status'            => 'pending',
                'processed_by'      => $staff->first_name . ' ' . $staff->last_name,
                'processed_by_type' => 'staff',
                'active'            => 1,
            ]);

            foreach ($validated['products'] as $item) {
                if ($item['item_type'] === 'ingredient') {
                    $ingredient = Ingredient::findOrFail($item['ingredient_id']);

                    InventoryTransactionItem::create([
                        'inventory_transaction_id' => $transaction->id,
                        'item_type'                => 'ingredient',
                        'ingredient_id'            => $ingredient->id,
                        'quantity'                 => $item['quantity'],
                        'unit'                     => $ingredient->unit,
                        'note'                     => $item['note'] ?? null,
                    ]);
                } else {
                    $product = Product::findOrFail($item['product_id']);

                    InventoryTransactionItem::create([
                        'inventory_transaction_id' => $transaction->id,
                        'item_type'                => 'product',
                        'product_id'               => $product->id,
                        'quantity'                 => $item['quantity'],
                        'unit'                     => $product->unit,
                        'note'                     => $item['note'] ?? null,
                    ]);
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to save stock in: ' . $e->getMessage()], 500);
        }

        $this->sendInventoryNotification($transaction, 'stock_in');

        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────
    // STAFF STOCK OUT  →  always pending, no quantity change yet
    // ─────────────────────────────────────────────────────────────
    public function storeStockOut(Request $request)
    {
        $validated = $request->validate([
            'products'                 => 'required|array|min:1',
            'products.*.item_type'     => 'required|in:product,ingredient',
            'products.*.product_id'    => 'nullable|exists:products,id',
            'products.*.ingredient_id' => 'nullable|exists:ingredients,id',
            'products.*.quantity'      => 'required|numeric|min:1',
            'products.*.reason'        => 'required|string|max:255',
            'products.*.note'          => 'nullable|string|max:500',
        ]);

        foreach ($validated['products'] as $index => $item) {
            if ($item['item_type'] === 'product' && empty($item['product_id'])) {
                return response()->json(['success' => false, 'message' => "Row " . ($index + 1) . ": product_id is required."], 422);
            }
            if ($item['item_type'] === 'ingredient' && empty($item['ingredient_id'])) {
                return response()->json(['success' => false, 'message' => "Row " . ($index + 1) . ": ingredient_id is required."], 422);
            }
        }

        $staff = Auth::guard('staff')->user();

        // Sanity-check available stock, but don't deduct yet — deduction happens on owner approval.
        foreach ($validated['products'] as $item) {
            if ($item['item_type'] === 'ingredient') {
                $ingredient = Ingredient::find($item['ingredient_id']);
                if (!$ingredient || $ingredient->stock_quantity_in < $item['quantity']) {
                    return response()->json(['success' => false, 'message' => 'Insufficient stock for ingredient: ' . ($ingredient->ingredient_name ?? 'Unknown')], 422);
                }
            } else {
                $product = Product::find($item['product_id']);
                if (!$product || $product->quantity_in < $item['quantity']) {
                    return response()->json(['success' => false, 'message' => 'Insufficient stock for ' . ($product->product_name ?? 'Unknown')], 422);
                }
            }
        }

        DB::beginTransaction();
        try {
            $transaction = InventoryTransaction::create([
                'owner_account_id'  => $staff->owner_account_id,
                'branch_id'         => $staff->branch_id,
                'transaction_no'    => 'INV-OUT-' . now()->format('YmdHis'),
                'type'              => 'stock_out',
                'status'            => 'pending',
                'processed_by'      => $staff->first_name . ' ' . $staff->last_name,
                'processed_by_type' => 'staff',
                'active'            => 1,
            ]);

            foreach ($validated['products'] as $item) {
                if ($item['item_type'] === 'ingredient') {
                    $ingredient = Ingredient::findOrFail($item['ingredient_id']);

                    InventoryTransactionItem::create([
                        'inventory_transaction_id' => $transaction->id,
                        'item_type'                => 'ingredient',
                        'ingredient_id'            => $ingredient->id,
                        'quantity'                 => $item['quantity'],
                        'unit'                     => $ingredient->unit,
                        'reason'                   => $item['reason'],
                        'note'                     => $item['note'] ?? null,
                    ]);
                } else {
                    $product = Product::findOrFail($item['product_id']);

                    InventoryTransactionItem::create([
                        'inventory_transaction_id' => $transaction->id,
                        'item_type'                => 'product',
                        'product_id'               => $product->id,
                        'quantity'                 => $item['quantity'],
                        'unit'                     => $product->unit,
                        'reason'                   => $item['reason'],
                        'note'                     => $item['note'] ?? null,
                    ]);
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to submit stock out: ' . $e->getMessage()], 500);
        }

        $this->sendInventoryNotification($transaction, 'stock_out');

        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────
    // GET TRANSACTION DATA
    // ─────────────────────────────────────────────────────────────
    public function getData(string $uuid)
    {
        try {
            $staff = Auth::guard('staff')->user();

            $transaction = InventoryTransaction::with(['items.product', 'items.ingredient', 'branch'])
                ->where('uuid', $uuid)
                ->where('owner_account_id', $staff->owner_account_id)
                ->where('branch_id', $staff->branch_id)
                ->firstOrFail();

            return response()->json(['success' => true, 'transaction' => $transaction]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // SEND NOTIFICATIONS (notifies the owner that a staff request is pending)
    // ─────────────────────────────────────────────────────────────
    private function sendInventoryNotification($transaction, $action)
    {
        try {
            $actor  = Auth::guard('staff')->user();
            $owners = OwnerAccount::where('id', $actor->owner_account_id)->get();
            $branch = $transaction->branch;

            Notification::send(
                $owners,
                new ProductNotification($transaction, $branch, $actor, $action)
            );

        } catch (\Exception $e) {
            //
        }
    }
}