<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RewardRedemption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class BranchReportController extends Controller
{
    /**
     * Payment method labels, keyed by the integer stored on
     * booking_payments.payment_method / order_payments.payment_method
     * 0=cash, 1=gcash, 2=debit-card, 3=pay-later
     */
    private const PAYMENT_METHOD_LABELS = [
        0 => 'Cash',
        1 => 'GCash',
        2 => 'Debit Card',
        3 => 'Pay Later',
    ];

    public function index(Request $request)
    {
        $branches = Branch::where('active', 1)
            ->orderBy('branch_name')
            ->get();
    
        $isAjax = $request->ajax() || $request->get('ajax') === 'true';
    
        if ($isAjax && $request->type === 'sales') {
            return $this->salesData($request);
        }
    
        if ($isAjax && $request->type === 'inventory') {
            return $this->inventoryData($request);
        }
    
        // ↓ Route-aware view selection
        if ($request->routeIs('sub_one.reports.inventory_report')) {
            return view('owner.reports.inventory_report', compact('branches'));
        }
    
        return view('owner.reports.branch_report', compact('branches'));
    }

    // ════════════════════════════════════════════════════════════════════
    //  SALES REPORT - UPDATED WITH REWARD REDEMPTIONS
    // ════════════════════════════════════════════════════════════════════
    private function salesData(Request $request)
    {
        $dateFrom = $request->date_from
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::now()->subDays(6)->startOfDay();

        $dateTo = $request->date_to
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::now()->endOfDay();

        $branchId = $request->branch_id ?: null;

        // ── Booking revenue by branch ────────────────────────────────────
        $bookingQuery = DB::table('booking_payments as bp')
            ->join('bookings as b', 'b.id', '=', 'bp.booking_id')
            ->join('branches as br', 'br.id', '=', 'b.branch_id')
            ->whereBetween('bp.date_created', [$dateFrom, $dateTo])
            ->where('br.active', 1)
            ->where('bp.payment_status', 1)
            ->where('bp.active', 1)
            ->select(
                'br.id as branch_id',
                'br.branch_name',
                DB::raw('COUNT(DISTINCT b.id) as total_bookings'),
                DB::raw('SUM(bp.total_amount) as booking_revenue')
            )
            ->groupBy('br.id', 'br.branch_name');

        if ($branchId) {
            $bookingQuery->where('b.branch_id', $branchId);
        }
        $bookingRows = $bookingQuery->get()->keyBy('branch_id');

        // ── Order revenue by branch ──────────────────────────────────────
        $orderQuery = DB::table('order_payments as op')
            ->join('orders as o', 'o.id', '=', 'op.order_id')
            ->join('branches as br', 'br.id', '=', 'o.branch_id')
            ->whereBetween('op.date_created', [$dateFrom, $dateTo])
            ->where('br.active', 1)
            ->where('op.order_payment_status', 1)
            ->where('op.active', 1)
            ->select(
                'br.id as branch_id',
                'br.branch_name',
                DB::raw('COUNT(DISTINCT o.id) as total_orders'),
                DB::raw('SUM(op.total_amount) as order_revenue')
            )
            ->groupBy('br.id', 'br.branch_name');

        if ($branchId) {
            $orderQuery->where('o.branch_id', $branchId);
        }
        $orderRows = $orderQuery->get()->keyBy('branch_id');

        // ── Reward Redemption Revenue by Branch ────────────────────────
        $rewardQuery = RewardRedemption::whereBetween('redeemed_at', [$dateFrom, $dateTo])
            ->where('active', 1)
            ->with('branch')
            ->select(
                'branch_id',
                DB::raw('COUNT(id) as total_redemptions'),
                DB::raw('SUM(discount_amount) as total_discount_amount')
            )
            ->groupBy('branch_id');

        if ($branchId) {
            $rewardQuery->where('branch_id', $branchId);
        }
        $rewardRows = $rewardQuery->get()->keyBy('branch_id');

        // ── Merge All Branches ──────────────────────────────────────────
        $allBranchIds = $bookingRows->keys()
            ->merge($orderRows->keys())
            ->merge($rewardRows->keys())
            ->unique();

        $byBranch = $allBranchIds->map(function ($id) use ($bookingRows, $orderRows, $rewardRows) {
            $b = $bookingRows->get($id);
            $o = $orderRows->get($id);
            $r = $rewardRows->get($id);
            
            $bookingRev = (float) ($b->booking_revenue ?? 0);
            $orderRev   = (float) ($o->order_revenue ?? 0);
            $rewardDiscount = (float) ($r->total_discount_amount ?? 0);
            
            // Net Revenue = Total Revenue - Reward Discounts
            $totalRevenue = $bookingRev + $orderRev;
            $netRevenue = $totalRevenue - $rewardDiscount;
            
            return [
                'branch_name'          => $b->branch_name ?? $o->branch_name ?? ($r->branch->branch_name ?? 'Unknown'),
                'booking_revenue'      => number_format($bookingRev, 2, '.', ''),
                'order_revenue'        => number_format($orderRev, 2, '.', ''),
                'reward_discount'      => number_format($rewardDiscount, 2, '.', ''),
                'total_revenue'        => number_format($totalRevenue, 2, '.', ''),
                'net_revenue'          => number_format($netRevenue, 2, '.', ''),
                'total_bookings'       => (int) ($b->total_bookings ?? 0),
                'total_orders'         => (int) ($o->total_orders ?? 0),
                'total_redemptions'    => (int) ($r->total_redemptions ?? 0),
            ];
        })->values();

        // ── Grand Totals ─────────────────────────────────────────────────
        $grandTotalRevenue = $byBranch->sum(fn ($r) => (float) $r['total_revenue']);
        $grandTotalBookings = $byBranch->sum(fn ($r) => $r['total_bookings']);
        $grandTotalOrders = $byBranch->sum(fn ($r) => $r['total_orders']);
        $grandTotalRedemptions = $byBranch->sum(fn ($r) => $r['total_redemptions']);
        $grandTotalRewardDiscount = $byBranch->sum(fn ($r) => (float) $r['reward_discount']);
        $grandTotalNetRevenue = $byBranch->sum(fn ($r) => (float) $r['net_revenue']);

        return response()->json([
            'success'               => true,
            'total_revenue'         => number_format($grandTotalRevenue, 2, '.', ''),
            'total_net_revenue'     => number_format($grandTotalNetRevenue, 2, '.', ''),
            'total_bookings'        => $grandTotalBookings,
            'total_orders'          => $grandTotalOrders,
            'total_redemptions'     => $grandTotalRedemptions,
            'total_reward_discount' => number_format($grandTotalRewardDiscount, 2, '.', ''),
            'by_branch'             => $byBranch,

            // ── payment method, service popularity, orders, products sold ──
            'payment_methods'       => $this->paymentMethodBreakdown($dateFrom, $dateTo, $branchId),
            'service_breakdown'     => $this->serviceBreakdown($dateFrom, $dateTo, $branchId),
            'orders'                => $this->ordersBreakdown($dateFrom, $dateTo, $branchId),
            'products_sold'         => $this->productsSold($dateFrom, $dateTo, $branchId),
        ]);
    }

    // ════════════════════════════════════════════════════════════════════
    //  PAYMENT METHOD BREAKDOWN (bookings + orders combined)
    // ════════════════════════════════════════════════════════════════════
    private function paymentMethodBreakdown($dateFrom, $dateTo, $branchId = null)
    {
        $bookingPayments = DB::table('booking_payments')
            ->whereBetween('date_created', [$dateFrom, $dateTo])
            ->where('payment_status', 1)
            ->where('active', 1)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->select('payment_method', DB::raw('COUNT(*) as cnt'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('payment_method')
            ->get();

        $orderPayments = DB::table('order_payments')
            ->whereBetween('date_created', [$dateFrom, $dateTo])
            ->where('order_payment_status', 1)
            ->where('active', 1)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->select('payment_method', DB::raw('COUNT(*) as cnt'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('payment_method')
            ->get();

        $merged = [];
        foreach ([$bookingPayments, $orderPayments] as $set) {
            foreach ($set as $row) {
                $key = (int) $row->payment_method;
                if (!isset($merged[$key])) {
                    $merged[$key] = ['count' => 0, 'total' => 0.0];
                }
                $merged[$key]['count'] += (int) $row->cnt;
                $merged[$key]['total'] += (float) $row->total;
            }
        }

        $result = [];
        foreach ($merged as $method => $data) {
            $result[] = [
                'method'       => self::PAYMENT_METHOD_LABELS[$method] ?? 'Unknown',
                'payments'     => $data['count'],
                'total_amount' => number_format($data['total'], 2, '.', ''),
            ];
        }

        usort($result, fn ($a, $b) => $b['payments'] <=> $a['payments']);

        return $result;
    }

    // ════════════════════════════════════════════════════════════════════
    //  SERVICE POPULARITY (bookings — walk-in + online, by category/service)
    // ════════════════════════════════════════════════════════════════════
    private function serviceBreakdown($dateFrom, $dateTo, $branchId = null)
    {
        $bookingQuery = Booking::with(['serviceCategory', 'serviceName'])
            ->whereBetween('booking_date', [$dateFrom, $dateTo])
            ->where('active', 1)
            ->whereIn('booking_status', [1, 4]); // confirmed + completed

        if ($branchId) {
            $bookingQuery->where('branch_id', $branchId);
        }

        $bookings = $bookingQuery->get();

        $paymentTotals = BookingPayment::whereIn('booking_id', $bookings->pluck('id'))
            ->where('payment_status', 1)
            ->where('active', 1)
            ->select('booking_id', DB::raw('SUM(total_amount) as total'))
            ->groupBy('booking_id')
            ->pluck('total', 'booking_id');

        $grouped = [];
        foreach ($bookings as $booking) {
            $category = $booking->serviceCategory->service_category ?? 'Uncategorized';
            $service  = $booking->serviceName->service_name ?? 'Unknown';
            $key      = $category . '|' . $service;

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'category' => $category,
                    'service'  => $service,
                    'hours'    => 0.0,
                    'revenue'  => 0.0,
                ];
            }

            $grouped[$key]['hours']   += (float) $booking->computed_total_duration;
            $grouped[$key]['revenue'] += (float) ($paymentTotals[$booking->id] ?? 0);
        }

        $result = array_values($grouped);
        usort($result, fn ($a, $b) => $b['hours'] <=> $a['hours']);

        foreach ($result as &$r) {
            $r['hours']   = round($r['hours'], 1);
            $r['revenue'] = number_format($r['revenue'], 2, '.', '');
        }
        unset($r);

        return $result;
    }

    // ════════════════════════════════════════════════════════════════════
    //  ORDERS BREAKDOWN (with line items for the "view details" action)
    // ════════════════════════════════════════════════════════════════════
    private function ordersBreakdown($dateFrom, $dateTo, $branchId = null)
    {
        $orderQuery = Order::with(['branch', 'items.product', 'payments'])
            ->whereBetween('date_created', [$dateFrom, $dateTo])
            ->where('active', 1)
            ->latest('date_created');

        if ($branchId) {
            $orderQuery->where('branch_id', $branchId);
        }

        $orders = $orderQuery->get();

        return $orders->map(function ($order) {
            $payment = $order->payments->firstWhere('order_payment_status', 1)
                ?? $order->payments->first();

            return [
                'order_ref_no'   => $order->order_ref_no,
                'branch_name'    => $order->branch->branch_name ?? '—',
                'date'           => optional($order->date_created)->format('M d, Y h:i A'),
                'payment_method' => $payment
                    ? (self::PAYMENT_METHOD_LABELS[(int) $payment->payment_method] ?? 'Unknown')
                    : '—',
                'items_qty'      => (int) $order->items->sum('quantity'),
                'total_amount'   => $payment
                    ? number_format($payment->total_amount, 2, '.', '')
                    : number_format($order->items->sum('sub_total'), 2, '.', ''),
                'items' => $order->items->map(fn ($i) => [
                    'product_name'  => $i->product->product_name ?? '—',
                    'quantity'      => (int) $i->quantity,
                    'selling_price' => number_format($i->selling_price, 2, '.', ''),
                    'sub_total'     => number_format($i->sub_total, 2, '.', ''),
                ])->values(),
            ];
        })->values();
    }

    // ════════════════════════════════════════════════════════════════════
    //  PRODUCTS SOLD (RTD/Package + MTO)
    // ════════════════════════════════════════════════════════════════════
    private function productsSold($dateFrom, $dateTo, $branchId = null)
    {
        $items = OrderItem::with('product')
            ->where('active', 1)
            ->where('order_item_status', 1) // bought
            ->whereHas('order', function ($q) use ($dateFrom, $dateTo, $branchId) {
                $q->whereBetween('date_created', [$dateFrom, $dateTo])->where('active', 1);
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            })
            ->get();

        $grouped = [];
        foreach ($items as $item) {
            $productId = $item->product_id;

            if (!isset($grouped[$productId])) {
                $grouped[$productId] = [
                    'product'  => $item->product->product_name ?? 'Unknown',
                    'type'     => $item->product->product_type ?? 'unknown',
                    'quantity' => 0,
                    'revenue'  => 0.0,
                ];
            }

            $grouped[$productId]['quantity'] += (int) $item->quantity;
            $grouped[$productId]['revenue']  += (float) $item->sub_total;
        }

        $result = array_values($grouped);
        usort($result, fn ($a, $b) => $b['quantity'] <=> $a['quantity']);

        foreach ($result as &$r) {
            $r['revenue'] = number_format($r['revenue'], 2, '.', '');
        }
        unset($r);

        return $result;
    }

    // ════════════════════════════════════════════════════════════════════
    //  INVENTORY REPORT
    // ════════════════════════════════════════════════════════════════════
    private function inventoryData(Request $request)
    {
        $owner = Auth::guard('owner')->user();
    
        $dateFrom = $request->date_from
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::now()->subDays(6)->startOfDay();
    
        $dateTo = $request->date_to
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::now()->endOfDay();
    
        $branchId = $request->branch_id ?: null;
    
        // ── Branches to report on ────────────────────────────────────────
        $branchQuery = Branch::where('active', 1)->orderBy('branch_name');
        if ($branchId) {
            $branchQuery->where('id', $branchId);
        }
        $branches = $branchQuery->get();
    
        $byBranch = $branches->map(function ($branch) use ($owner, $dateFrom, $dateTo) {
    
            $txns = InventoryTransaction::with('items')
                ->where('owner_account_id', $owner->id)
                ->where('branch_id', $branch->id)
                ->where('active', 1)
                ->whereIn('status', ['approved', 'done'])
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->get();
    
            $totalIn          = 0;
            $totalSold        = 0;
            $totalDamaged     = 0;
            $totalExpired     = 0;
            $totalPulledOut   = 0;
            $stockInTxnCount  = 0;
            $stockOutTxnCount = 0;
    
            foreach ($txns as $txn) {
                if ($txn->type === 'stock_in') {
                    foreach ($txn->items as $item) {
                        $totalIn += $item->quantity;
                    }
                    $stockInTxnCount++;
                } else {
                    foreach ($txn->items as $item) {
                        $reason = $item->reason ?? $txn->reason;
                        $qty    = $item->quantity;
    
                        match ($reason) {
                            'sold'       => $totalSold      += $qty,
                            'damaged'    => $totalDamaged   += $qty,
                            'expired'    => $totalExpired   += $qty,
                            'pulled_out' => $totalPulledOut += $qty,
                            default      => null,
                        };
                    }
                    $stockOutTxnCount++;
                }
            }
    
            $productBalance = Product::where('owner_account_id', $owner->id)
                ->where('branch_id', $branch->id)
                ->where('active', 1)
                ->sum('quantity_in');
    
            $ingredientBalance = Ingredient::where('owner_account_id', $owner->id)
                ->where('branch_id', $branch->id)
                ->where('active', 1)
                ->sum('stock_quantity_in');
    
            $endingBalance    = (int) $productBalance + (int) $ingredientBalance;
            $totalOut         = $totalSold + $totalDamaged + $totalExpired + $totalPulledOut;
            $beginningBalance = max(0, $endingBalance - $totalIn + $totalOut);
    
            $productCount = Product::where('owner_account_id', $owner->id)
                ->where('branch_id', $branch->id)
                ->where('active', 1)
                ->count();
    
            $ingredientCount = Ingredient::where('owner_account_id', $owner->id)
                ->where('branch_id', $branch->id)
                ->where('active', 1)
                ->count();
    
            return [
                'branch_name'         => $branch->branch_name,
                'beginning_balance'   => (int) $beginningBalance,
                'total_stock_in'      => (int) $totalIn,
                'total_sold'          => (int) $totalSold,
                'total_damaged'       => (int) $totalDamaged,
                'total_expired'       => (int) $totalExpired,
                'total_pulled_out'    => (int) $totalPulledOut,
                'total_stock_out'     => (int) $totalOut,
                'ending_balance'      => (int) $endingBalance,
                'stock_in_txn_count'  => $stockInTxnCount,
                'stock_out_txn_count' => $stockOutTxnCount,
                'product_count'       => $productCount,
                'ingredient_count'    => $ingredientCount,
            ];
    
        })->values();
    
        // ── Transaction log ──────────────────────────────────────────────
        $txnQuery = InventoryTransaction::with(['branch', 'items.product', 'items.ingredient'])
            ->where('owner_account_id', $owner->id)
            ->where('active', 1)
            ->whereIn('status', ['approved', 'done'])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->latest();
    
        if ($branchId) {
            $txnQuery->where('branch_id', $branchId);
        }
    
        $rawTxns = $txnQuery->get();
    
        $isIngredientRow = fn($i) => $i->item_type === 'ingredient'
            || (!empty($i->ingredient_id) && empty($i->product_id));
    
        $transactions = $rawTxns->map(function ($txn) use ($isIngredientRow) {
            $ingredientRows    = $txn->items->filter($isIngredientRow);
            $productRows       = $txn->items->filter(fn($i) => !$isIngredientRow($i));
            $mtoIngredients    = $ingredientRows->filter(fn($i) => $i->note && str_contains($i->note, 'MTO:'));
            $manualIngredients = $ingredientRows->filter(fn($i) => !($i->note && str_contains($i->note, 'MTO:')));
    
            $mtoProducts = $mtoIngredients->mapWithKeys(function ($i) {
                preg_match('/MTO:\s*(.+?)\s*x(\d+)/i', $i->note, $m);
                $key = trim($m[1] ?? 'unknown') . '|' . ($m[2] ?? 1);
                return [$key => (int)($m[2] ?? 1)];
            });
    
            return [
                'uuid'           => $txn->uuid,
                'transaction_no' => $txn->transaction_no,
                'branch_name'    => $txn->branch->branch_name ?? '—',
                'type'           => $txn->type,
                'status'         => $txn->status,
                'reason'         => $txn->reason,
                'processed_by'   => $txn->processed_by,
                'created_at'     => $txn->created_at,
                'total_quantity' => $productRows->sum('quantity') + $mtoProducts->sum() + $manualIngredients->sum('quantity'),
                'items_count'    => $productRows->count() + $mtoProducts->count() + $manualIngredients->count(),
            ];
        });
    
        // ── Items breakdown (one row per line item) ───────────────────────
        $items = $rawTxns->flatMap(function ($txn) use ($isIngredientRow) {
            $branchName    = $txn->branch->branch_name ?? '—';
            $txnNo         = $txn->transaction_no;
            $txnType       = $txn->type;
            $createdAt     = $txn->created_at;
    
            return $txn->items->map(function ($item) use ($branchName, $txnNo, $txnType, $createdAt, $isIngredientRow, $txn) {
                $isIngredient = $isIngredientRow($item);
    
                // Resolve a human-readable name
                $itemName = match(true) {
                    $isIngredient && $item->ingredient !== null
                        => $item->ingredient->ingredient_name,
                    !$isIngredient && $item->product !== null
                        => $item->product->product_name,
                    default
                        => $item->note ?? '—',
                };
    
                // Unit: prefer item-level, fall back to the related model
                $unit = $item->unit
                    ?? ($isIngredient ? ($item->ingredient->unit ?? '') : '');
    
                // Reason: item-level overrides transaction header
                $reason = $item->reason ?? $txn->reason ?? null;
    
                return [
                    'transaction_no' => $txnNo,
                    'branch_name'    => $branchName,
                    'created_at'     => $createdAt,
                    'txn_type'       => $txnType,
                    'item_type'      => $isIngredient ? 'ingredient' : 'product',
                    'item_name'      => $itemName,
                    'quantity'       => (int) $item->quantity,
                    'unit'           => $unit,
                    'reason'         => $reason,
                    'note'           => $item->note,
                ];
            });
        })->values();
    
        return response()->json([
            'success'      => true,
            'by_branch'    => $byBranch,
            'transactions' => $transactions,
            'items'        => $items,
        ]);
    }

    // ════════════════════════════════════════════════════════════════════
    //  SALES DATA HELPER (for PDF export)
    // ════════════════════════════════════════════════════════════════════
    private function getSalesData($dateFrom, $dateTo, $branchId = null)
    {
        // ── Booking revenue by branch ────────────────────────────────────
        $bookingQuery = DB::table('booking_payments as bp')
            ->join('bookings as b', 'b.id', '=', 'bp.booking_id')
            ->join('branches as br', 'br.id', '=', 'b.branch_id')
            ->whereBetween('bp.date_created', [$dateFrom, $dateTo])
            ->where('br.active', 1)
            ->where('bp.payment_status', 1)
            ->where('bp.active', 1)
            ->select(
                'br.id as branch_id',
                'br.branch_name',
                DB::raw('COUNT(DISTINCT b.id) as total_bookings'),
                DB::raw('SUM(bp.total_amount) as booking_revenue')
            )
            ->groupBy('br.id', 'br.branch_name');

        if ($branchId) {
            $bookingQuery->where('b.branch_id', $branchId);
        }
        $bookingRows = $bookingQuery->get()->keyBy('branch_id');

        // ── Order revenue by branch ──────────────────────────────────────
        $orderQuery = DB::table('order_payments as op')
            ->join('orders as o', 'o.id', '=', 'op.order_id')
            ->join('branches as br', 'br.id', '=', 'o.branch_id')
            ->whereBetween('op.date_created', [$dateFrom, $dateTo])
            ->where('br.active', 1)
            ->where('op.order_payment_status', 1)
            ->where('op.active', 1)
            ->select(
                'br.id as branch_id',
                'br.branch_name',
                DB::raw('COUNT(DISTINCT o.id) as total_orders'),
                DB::raw('SUM(op.total_amount) as order_revenue')
            )
            ->groupBy('br.id', 'br.branch_name');

        if ($branchId) {
            $orderQuery->where('o.branch_id', $branchId);
        }
        $orderRows = $orderQuery->get()->keyBy('branch_id');

        // ── Reward Redemption Revenue by Branch ────────────────────────
        $rewardQuery = RewardRedemption::whereBetween('redeemed_at', [$dateFrom, $dateTo])
            ->where('active', 1)
            ->with('branch')
            ->select(
                'branch_id',
                DB::raw('COUNT(id) as total_redemptions'),
                DB::raw('SUM(discount_amount) as total_discount_amount')
            )
            ->groupBy('branch_id');

        if ($branchId) {
            $rewardQuery->where('branch_id', $branchId);
        }
        $rewardRows = $rewardQuery->get()->keyBy('branch_id');

        // ── Merge All Branches ──────────────────────────────────────────
        $allBranchIds = $bookingRows->keys()
            ->merge($orderRows->keys())
            ->merge($rewardRows->keys())
            ->unique();

        $byBranch = $allBranchIds->map(function ($id) use ($bookingRows, $orderRows, $rewardRows) {
            $b = $bookingRows->get($id);
            $o = $orderRows->get($id);
            $r = $rewardRows->get($id);
            
            $bookingRev = (float) ($b->booking_revenue ?? 0);
            $orderRev   = (float) ($o->order_revenue ?? 0);
            $rewardDiscount = (float) ($r->total_discount_amount ?? 0);
            
            $totalRevenue = $bookingRev + $orderRev;
            $netRevenue = $totalRevenue - $rewardDiscount;
            
            return [
                'branch_name'          => $b->branch_name ?? $o->branch_name ?? ($r->branch->branch_name ?? 'Unknown'),
                'booking_revenue'      => $bookingRev,
                'order_revenue'        => $orderRev,
                'reward_discount'      => $rewardDiscount,
                'total_revenue'        => $totalRevenue,
                'net_revenue'          => $netRevenue,
                'total_bookings'       => (int) ($b->total_bookings ?? 0),
                'total_orders'         => (int) ($o->total_orders ?? 0),
                'total_redemptions'    => (int) ($r->total_redemptions ?? 0),
            ];
        })->values();

        // ── Grand Totals ─────────────────────────────────────────────────
        $grandTotalRevenue = $byBranch->sum(fn ($r) => $r['total_revenue']);
        $grandTotalBookings = $byBranch->sum(fn ($r) => $r['total_bookings']);
        $grandTotalOrders = $byBranch->sum(fn ($r) => $r['total_orders']);
        $grandTotalRedemptions = $byBranch->sum(fn ($r) => $r['total_redemptions']);
        $grandTotalRewardDiscount = $byBranch->sum(fn ($r) => $r['reward_discount']);
        $grandTotalNetRevenue = $byBranch->sum(fn ($r) => $r['net_revenue']);

        return [
            'by_branch' => $byBranch,
            'total_revenue' => $grandTotalRevenue,
            'total_net_revenue' => $grandTotalNetRevenue,
            'total_bookings' => $grandTotalBookings,
            'total_orders' => $grandTotalOrders,
            'total_redemptions' => $grandTotalRedemptions,
            'total_reward_discount' => $grandTotalRewardDiscount,

            // ── same breakdowns, available to the PDF view too ──
            'payment_methods'   => $this->paymentMethodBreakdown($dateFrom, $dateTo, $branchId),
            'service_breakdown' => $this->serviceBreakdown($dateFrom, $dateTo, $branchId),
            'orders'            => $this->ordersBreakdown($dateFrom, $dateTo, $branchId),
            'products_sold'     => $this->productsSold($dateFrom, $dateTo, $branchId),
        ];
    }

    // ════════════════════════════════════════════════════════════════════
    //  SALES REPORT - PDF EXPORT
    // ════════════════════════════════════════════════════════════════════
    public function exportSalesPdf(Request $request)
    {
        $owner = Auth::guard('owner')->user();
        
        $dateFrom = $request->date_from
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::now()->subDays(6)->startOfDay();

        $dateTo = $request->date_to
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::now()->endOfDay();

        $branchId = $request->branch_id ?: null;

        $salesData = $this->getSalesData($dateFrom, $dateTo, $branchId);
        
        $branch = null;
        if ($branchId) {
            $branch = Branch::find($branchId);
        }

        $data = [
            'date_from' => $dateFrom->format('M d, Y'),
            'date_to' => $dateTo->format('M d, Y'),
            'branch' => $branch,
            'salesData' => $salesData,
            'generated_at' => now()->format('M d, Y h:i A'),
            'company_name' => 'Linkud Hub',
            'generated_by' => $owner ? $owner->first_name . ' ' . $owner->last_name : 'System',
            'generated_by_email' => $owner ? $owner->email : 'system@linkudhub.com',
        ];

        $pdf = Pdf::loadView('owner.reports.pdf.sales_report', $data);
        $pdf->setPaper('A4', 'landscape');
        
        $filename = 'sales_report_' . date('Y-m-d_His') . '.pdf';
        return $pdf->download($filename);
    }

    // ════════════════════════════════════════════════════════════════════
    //  INVENTORY REPORT - PDF EXPORT
    // ════════════════════════════════════════════════════════════════════
    public function exportInventoryPdf(Request $request)
    {
        $owner = Auth::guard('owner')->user();
        
        $dateFrom = $request->date_from
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::now()->subDays(6)->startOfDay();

        $dateTo = $request->date_to
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::now()->endOfDay();

        $branchId = $request->branch_id ?: null;

        // Get inventory data for PDF
        $inventoryData = $this->getInventoryDataForPdf($dateFrom, $dateTo, $branchId);
        
        $branch = null;
        if ($branchId) {
            $branch = Branch::find($branchId);
        }

        $data = [
            'date_from' => $dateFrom->format('M d, Y'),
            'date_to' => $dateTo->format('M d, Y'),
            'branch' => $branch,
            'inventoryData' => $inventoryData,
            'generated_at' => now()->format('M d, Y h:i A'),
            'company_name' => 'Linkud Hub',
            'generated_by' => $owner ? $owner->first_name . ' ' . $owner->last_name : 'System',
            'generated_by_email' => $owner ? $owner->email : 'system@linkudhub.com',
        ];

        $pdf = Pdf::loadView('owner.reports.pdf.inventory_report', $data);
        $pdf->setPaper('A4', 'landscape');
        
        $filename = 'inventory_report_' . date('Y-m-d_His') . '.pdf';
        return $pdf->download($filename);
    }

    // ════════════════════════════════════════════════════════════════════
    //  INVENTORY DATA HELPER (for PDF export)
    // ════════════════════════════════════════════════════════════════════
    private function getInventoryDataForPdf($dateFrom, $dateTo, $branchId = null)
    {
        $owner = Auth::guard('owner')->user();
    
        $branchQuery = Branch::where('active', 1)->orderBy('branch_name');
        if ($branchId) {
            $branchQuery->where('id', $branchId);
        }
        $branches = $branchQuery->get();
    
        $byBranch = $branches->map(function ($branch) use ($owner, $dateFrom, $dateTo) {
    
            $txns = InventoryTransaction::with('items')
                ->where('owner_account_id', $owner->id)
                ->where('branch_id', $branch->id)
                ->where('active', 1)
                ->whereIn('status', ['approved', 'done'])
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->get();
    
            $totalIn          = 0;
            $totalSold        = 0;
            $totalDamaged     = 0;
            $totalExpired     = 0;
            $totalPulledOut   = 0;
            $stockInTxnCount  = 0;
            $stockOutTxnCount = 0;
    
            foreach ($txns as $txn) {
                if ($txn->type === 'stock_in') {
                    foreach ($txn->items as $item) {
                        $totalIn += $item->quantity;
                    }
                    $stockInTxnCount++;
                } else {
                    foreach ($txn->items as $item) {
                        $reason = $item->reason ?? $txn->reason;
                        $qty    = $item->quantity;
    
                        match ($reason) {
                            'sold'       => $totalSold      += $qty,
                            'damaged'    => $totalDamaged   += $qty,
                            'expired'    => $totalExpired   += $qty,
                            'pulled_out' => $totalPulledOut += $qty,
                            default      => null,
                        };
                    }
                    $stockOutTxnCount++;
                }
            }
    
            $productBalance = Product::where('owner_account_id', $owner->id)
                ->where('branch_id', $branch->id)
                ->where('active', 1)
                ->sum('quantity_in');
    
            $ingredientBalance = Ingredient::where('owner_account_id', $owner->id)
                ->where('branch_id', $branch->id)
                ->where('active', 1)
                ->sum('stock_quantity_in');
    
            $endingBalance    = (int) $productBalance + (int) $ingredientBalance;
            $totalOut         = $totalSold + $totalDamaged + $totalExpired + $totalPulledOut;
            $beginningBalance = max(0, $endingBalance - $totalIn + $totalOut);
    
            $productCount = Product::where('owner_account_id', $owner->id)
                ->where('branch_id', $branch->id)
                ->where('active', 1)
                ->count();
    
            $ingredientCount = Ingredient::where('owner_account_id', $owner->id)
                ->where('branch_id', $branch->id)
                ->where('active', 1)
                ->count();
    
            return [
                'branch_name'         => $branch->branch_name,
                'beginning_balance'   => (int) $beginningBalance,
                'total_stock_in'      => (int) $totalIn,
                'total_sold'          => (int) $totalSold,
                'total_damaged'       => (int) $totalDamaged,
                'total_expired'       => (int) $totalExpired,
                'total_pulled_out'    => (int) $totalPulledOut,
                'total_stock_out'     => (int) $totalOut,
                'ending_balance'      => (int) $endingBalance,
                'stock_in_txn_count'  => $stockInTxnCount,
                'stock_out_txn_count' => $stockOutTxnCount,
                'product_count'       => $productCount,
                'ingredient_count'    => $ingredientCount,
            ];
    
        })->values();

        $totalBeginningBalance = $byBranch->sum('beginning_balance');
        $totalStockIn = $byBranch->sum('total_stock_in');
        $totalStockOut = $byBranch->sum('total_stock_out');
        $totalEndingBalance = $byBranch->sum('ending_balance');

        // Get transactions for PDF
        $txnQuery = InventoryTransaction::with(['branch', 'items.product', 'items.ingredient'])
            ->where('owner_account_id', $owner->id)
            ->where('active', 1)
            ->whereIn('status', ['approved', 'done'])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->latest();

        if ($branchId) {
            $txnQuery->where('branch_id', $branchId);
        }

        $rawTxns = $txnQuery->get();

        $isIngredientRow = fn($i) => $i->item_type === 'ingredient'
            || (!empty($i->ingredient_id) && empty($i->product_id));

        $transactions = $rawTxns->map(function ($txn) use ($isIngredientRow) {
            $ingredientRows    = $txn->items->filter($isIngredientRow);
            $productRows       = $txn->items->filter(fn($i) => !$isIngredientRow($i));
            $mtoIngredients    = $ingredientRows->filter(fn($i) => $i->note && str_contains($i->note, 'MTO:'));
            $manualIngredients = $ingredientRows->filter(fn($i) => !($i->note && str_contains($i->note, 'MTO:')));

            $mtoProducts = $mtoIngredients->mapWithKeys(function ($i) {
                preg_match('/MTO:\s*(.+?)\s*x(\d+)/i', $i->note, $m);
                $key = trim($m[1] ?? 'unknown') . '|' . ($m[2] ?? 1);
                return [$key => (int)($m[2] ?? 1)];
            });

            return [
                'uuid'           => $txn->uuid,
                'transaction_no' => $txn->transaction_no,
                'branch_name'    => $txn->branch->branch_name ?? '—',
                'type'           => $txn->type,
                'status'         => $txn->status,
                'reason'         => $txn->reason,
                'processed_by'   => $txn->processed_by,
                'created_at'     => $txn->created_at,
                'total_quantity' => $productRows->sum('quantity') + $mtoProducts->sum() + $manualIngredients->sum('quantity'),
                'items_count'    => $productRows->count() + $mtoProducts->count() + $manualIngredients->count(),
            ];
        });

        $items = $rawTxns->flatMap(function ($txn) use ($isIngredientRow) {
            $branchName    = $txn->branch->branch_name ?? '—';
            $txnNo         = $txn->transaction_no;
            $txnType       = $txn->type;
            $createdAt     = $txn->created_at;

            return $txn->items->map(function ($item) use ($branchName, $txnNo, $txnType, $createdAt, $isIngredientRow, $txn) {
                $isIngredient = $isIngredientRow($item);

                $itemName = match(true) {
                    $isIngredient && $item->ingredient !== null
                        => $item->ingredient->ingredient_name,
                    !$isIngredient && $item->product !== null
                        => $item->product->product_name,
                    default
                        => $item->note ?? '—',
                };

                $unit = $item->unit
                    ?? ($isIngredient ? ($item->ingredient->unit ?? '') : '');

                $reason = $item->reason ?? $txn->reason ?? null;

                return [
                    'transaction_no' => $txnNo,
                    'branch_name'    => $branchName,
                    'created_at'     => $createdAt,
                    'txn_type'       => $txnType,
                    'item_type'      => $isIngredient ? 'ingredient' : 'product',
                    'item_name'      => $itemName,
                    'quantity'       => (int) $item->quantity,
                    'unit'           => $unit,
                    'reason'         => $reason,
                    'note'           => $item->note,
                    'product_category'  => !$isIngredient ? ($item->product->product_category ?? null) : null,

                ];
            });
        })->values();

        return [
            'by_branch' => $byBranch,
            'total_beginning_balance' => $totalBeginningBalance,
            'total_stock_in' => $totalStockIn,
            'total_stock_out' => $totalStockOut,
            'total_ending_balance' => $totalEndingBalance,
            'transactions' => $transactions,
            'items' => $items,
        ];
    }
}