<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Branch;
use App\Models\Feedback;
use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\RewardRedemption;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class StaffReportController extends Controller
{
    /**
     * Main reports page with tab switching
     */
    public function index(Request $request)
    {
        $staff = Auth::guard('staff')->user();
        $branch = Branch::where('id', $staff->branch_id)->first();

        $isAjax = $request->ajax() || $request->get('ajax') === 'true';

        // Route-aware view selection
        if ($request->routeIs('sub_two.reports.inventory_report')) {
            return view('staff.reports.inventory_report', compact('branch'));
        }

        if ($request->routeIs('sub_two.reports.feedback_report')) {
            return view('staff.reports.feedback_report', compact('branch'));
        }

        // Sales report - default
        if ($isAjax && $request->type === 'sales') {
            return $this->salesData($request);
        }

        if ($isAjax && $request->type === 'inventory') {
            return $this->inventoryData($request);
        }

        if ($isAjax && $request->type === 'feedback') {
            return $this->feedbackData($request);
        }

        return view('staff.reports.sales_report', compact('branch'));
    }

    // ════════════════════════════════════════════════════════════════════
    //  STAFF AUDIT-TRAIL CONDITION HELPERS
    // ════════════════════════════════════════════════════════════════════

    private function getBookingStaffCondition($staff)
    {
        return function ($query) use ($staff) {
            $query->where(function ($q) use ($staff) {
                $q->where('b.created_by', $staff->id)
                    ->where('b.created_by_type', 'staff')
                    ->orWhere(function ($subQ) use ($staff) {
                        $subQ->where('b.updated_by', $staff->id)
                            ->where('b.updated_by_type', 'staff');
                    })
                    ->orWhere(function ($subQ) use ($staff) {
                        $subQ->where('b.last_updated_by', $staff->id)
                            ->where('b.last_updated_by_type', 'staff');
                    });
            });
        };
    }

    private function getOrderStaffCondition($staff)
    {
        return function ($query) use ($staff) {
            $query->where(function ($q) use ($staff) {
                $q->where('o.created_by', $staff->id)
                    ->where('o.created_by_type', 'staff')
                    ->orWhere(function ($subQ) use ($staff) {
                        $subQ->where('o.updated_by', $staff->id)
                            ->where('o.updated_by_type', 'staff');
                    })
                    ->orWhere(function ($subQ) use ($staff) {
                        $subQ->where('o.last_updated_by', $staff->id)
                            ->where('o.last_updated_by_type', 'staff');
                    });
            });
        };
    }

    private function getRewardStaffCondition($staff)
    {
        return function ($query) use ($staff) {
            $query->where(function ($q) use ($staff) {
                $q->where('rr.created_by', $staff->id)
                    ->where('rr.created_by_type', 'staff')
                    ->orWhere(function ($subQ) use ($staff) {
                        $subQ->where('rr.updated_by', $staff->id)
                            ->where('rr.updated_by_type', 'staff');
                    })
                    ->orWhere(function ($subQ) use ($staff) {
                        $subQ->where('rr.last_updated_by', $staff->id)
                            ->where('rr.last_updated_by_type', 'staff');
                    });
            });
        };
    }

    private function getFeedbackStaffCondition($staff)
    {
        return function ($query) use ($staff) {
            $query->where(function ($q) use ($staff) {
                $q->where('created_by', $staff->id)
                    ->where('created_by_type', 'staff')
                    ->orWhere(function ($subQ) use ($staff) {
                        $subQ->where('updated_by', $staff->id)
                            ->where('updated_by_type', 'staff');
                    })
                    ->orWhere(function ($subQ) use ($staff) {
                        $subQ->where('last_updated_by', $staff->id)
                            ->where('last_updated_by_type', 'staff');
                    });
            });
        };
    }

    private function getInventoryStaffCondition($staff)
    {
        $staffName = $staff->first_name . ' ' . $staff->last_name;
        return function ($query) use ($staff, $staffName) {
            $query->where(function ($q) use ($staff, $staffName) {
                $q->where('created_by', $staff->id)
                    ->where('created_by_type', 'staff')
                    ->orWhere(function ($subQ) use ($staff) {
                        $subQ->where('updated_by', $staff->id)
                            ->where('updated_by_type', 'staff');
                    })
                    ->orWhere(function ($subQ) use ($staff) {
                        $subQ->where('last_updated_by', $staff->id)
                            ->where('last_updated_by_type', 'staff');
                    })
                    ->orWhere('processed_by', $staffName);
            });
        };
    }

    // ════════════════════════════════════════════════════════════════════
    //  SALES REPORT - Staff Version
    // ════════════════════════════════════════════════════════════════════
    private function salesData(Request $request)
    {
        $staff = Auth::guard('staff')->user();
        $branchId = $staff->branch_id;

        $dateFrom = $request->date_from
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::now()->subDays(6)->startOfDay();

        $dateTo = $request->date_to
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::now()->endOfDay();

        // ── 1. BOOKING REVENUE ──
        $bookingQuery = DB::table('booking_payments as bp')
            ->join('bookings as b', 'b.id', '=', 'bp.booking_id')
            ->join('branches as br', 'br.id', '=', 'b.branch_id')
            ->whereBetween('bp.date_created', [$dateFrom, $dateTo])
            ->where('br.active', 1)
            ->where('bp.payment_status', 1)
            ->where('bp.active', 1)
            ->where('b.branch_id', $branchId)
            ->where($this->getBookingStaffCondition($staff))
            ->select(
                'br.id as branch_id',
                'br.branch_name',
                DB::raw('COUNT(DISTINCT b.id) as total_bookings'),
                DB::raw('SUM(bp.total_amount) as booking_revenue')
            )->groupBy('br.id', 'br.branch_name');

        $bookingRows = $bookingQuery->get()->keyBy('branch_id');

        // ── 2. EXTENSION REVENUE ──
        $extensionQuery = DB::table('booking_payments as bp')
            ->join('bookings as b', 'b.id', '=', 'bp.booking_id')
            ->join('branches as br', 'br.id', '=', 'b.branch_id')
            ->whereBetween('bp.date_created', [$dateFrom, $dateTo])
            ->where('br.active', 1)
            ->where('bp.payment_status', 1)
            ->where('bp.active', 1)
            ->where('b.branch_id', $branchId)
            ->where('bp.payment_category', 2)
            ->where($this->getBookingStaffCondition($staff))
            ->select(
                'br.id as branch_id',
                'br.branch_name',
                DB::raw('COUNT(DISTINCT b.id) as total_extensions'),
                DB::raw('SUM(bp.total_amount) as extension_revenue')
            )->groupBy('br.id', 'br.branch_name');

        $extensionRows = $extensionQuery->get()->keyBy('branch_id');

        // ── 3. ORDER REVENUE ──
        $orderQuery = DB::table('order_payments as op')
            ->join('orders as o', 'o.id', '=', 'op.order_id')
            ->join('branches as br', 'br.id', '=', 'o.branch_id')
            ->whereBetween('op.date_created', [$dateFrom, $dateTo])
            ->where('br.active', 1)
            ->where('op.order_payment_status', 1)
            ->where('op.active', 1)
            ->where('o.branch_id', $branchId)
            ->where($this->getOrderStaffCondition($staff))
            ->select(
                'br.id as branch_id',
                'br.branch_name',
                DB::raw('COUNT(DISTINCT o.id) as total_orders'),
                DB::raw('SUM(op.total_amount) as order_revenue')
            )->groupBy('br.id', 'br.branch_name');

        $orderRows = $orderQuery->get()->keyBy('branch_id');

        // ── 4. REWARD REDEMPTIONS ──
        $rewardQuery = DB::table('reward_redemptions as rr')
            ->join('branches as br', 'br.id', '=', 'rr.branch_id')
            ->whereBetween('rr.redeemed_at', [$dateFrom, $dateTo])
            ->where('rr.active', 1)
            ->where('rr.branch_id', $branchId)
            ->where($this->getRewardStaffCondition($staff))
            ->select(
                'br.id as branch_id',
                'br.branch_name',
                DB::raw('COUNT(rr.id) as total_redemptions'),
                DB::raw('SUM(rr.discount_amount) as total_discount_amount')
            )->groupBy('br.id', 'br.branch_name');

        $rewardRows = $rewardQuery->get()->keyBy('branch_id');

        // ── 5. MERGE ──
        $allBranchIds = $bookingRows->keys()
            ->merge($extensionRows->keys())
            ->merge($orderRows->keys())
            ->merge($rewardRows->keys())
            ->unique();

        $byBranch = $allBranchIds->map(function ($id) use ($bookingRows, $extensionRows, $orderRows, $rewardRows) {
            $b = $bookingRows->get($id);
            $e = $extensionRows->get($id);
            $o = $orderRows->get($id);
            $r = $rewardRows->get($id);

            $bookingRev = (float) ($b->booking_revenue ?? 0);
            $extensionRev = (float) ($e->extension_revenue ?? 0);
            $orderRev = (float) ($o->order_revenue ?? 0);
            $rewardDiscount = (float) ($r->total_discount_amount ?? 0);

            $totalRevenue = $bookingRev + $extensionRev + $orderRev;

            return [
                'branch_name'          => $b->branch_name ?? $e->branch_name ?? $o->branch_name ?? ($r->branch_name ?? 'Unknown'),
                'booking_revenue'      => number_format($bookingRev, 2, '.', ''),
                'extension_revenue'    => number_format($extensionRev, 2, '.', ''),
                'order_revenue'        => number_format($orderRev, 2, '.', ''),
                'reward_discount'      => number_format($rewardDiscount, 2, '.', ''),
                'total_revenue'        => number_format($totalRevenue, 2, '.', ''),
                'total_bookings'       => (int) ($b->total_bookings ?? 0),
                'total_extensions'     => (int) ($e->total_extensions ?? 0),
                'total_orders'         => (int) ($o->total_orders ?? 0),
                'total_redemptions'    => (int) ($r->total_redemptions ?? 0),
            ];
        })->values();

        // ── 6. GRAND TOTALS ──
        $grandTotalRevenue = $byBranch->sum(fn ($r) => (float) $r['total_revenue']);
        $grandTotalBookings = $byBranch->sum(fn ($r) => $r['total_bookings']);
        $grandTotalExtensions = $byBranch->sum(fn ($r) => $r['total_extensions']);
        $grandTotalOrders = $byBranch->sum(fn ($r) => $r['total_orders']);
        $grandTotalRedemptions = $byBranch->sum(fn ($r) => $r['total_redemptions']);
        $grandTotalRewardDiscount = $byBranch->sum(fn ($r) => (float) $r['reward_discount']);

        return response()->json([
            'success'              => true,
            'total_revenue'        => number_format($grandTotalRevenue, 2, '.', ''),
            'total_bookings'       => $grandTotalBookings,
            'total_extensions'     => $grandTotalExtensions,
            'total_orders'         => $grandTotalOrders,
            'total_redemptions'    => $grandTotalRedemptions,
            'total_reward_discount' => number_format($grandTotalRewardDiscount, 2, '.', ''),
            'by_branch'            => $byBranch,
            'branch_name'          => $byBranch->first()['branch_name'] ?? 'My Branch',
            'staff_name'           => $staff->first_name . ' ' . $staff->last_name,
        ]);
    }

    // ════════════════════════════════════════════════════════════════════
    //  INVENTORY REPORT - Staff Version
    // ════════════════════════════════════════════════════════════════════
    private function inventoryData(Request $request)
    {
        $staff = Auth::guard('staff')->user();
        $branchId = $staff->branch_id;
        $staffName = $staff->first_name . ' ' . $staff->last_name;

        $dateFrom = $request->date_from
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::now()->subDays(6)->startOfDay();

        $dateTo = $request->date_to
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::now()->endOfDay();

        $branch = Branch::where('id', $branchId)->where('active', 1)->first();

        if (!$branch) {
            return response()->json([
                'success' => true,
                'by_branch' => [],
                'transactions' => [],
                'items' => [],
                'branch_name' => 'No Branch',
                'staff_name' => $staffName,
            ]);
        }

        // ── Get transactions handled by this staff ──
        $txns = InventoryTransaction::with('items')
            ->where('owner_account_id', $staff->owner_account_id)
            ->where('branch_id', $branch->id)
            ->where('active', 1)
            ->whereIn('status', ['approved', 'done'])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->where($this->getInventoryStaffCondition($staff))
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

        // Current inventory balances
        $productBalance = Product::where('owner_account_id', $staff->owner_account_id)
            ->where('branch_id', $branch->id)
            ->where('active', 1)
            ->sum('quantity_in');

        $ingredientBalance = Ingredient::where('owner_account_id', $staff->owner_account_id)
            ->where('branch_id', $branch->id)
            ->where('active', 1)
            ->sum('stock_quantity_in');

        $endingBalance    = (int) $productBalance + (int) $ingredientBalance;
        $totalOut         = $totalSold + $totalDamaged + $totalExpired + $totalPulledOut;
        $beginningBalance = max(0, $endingBalance - $totalIn + $totalOut);

        $productCount = Product::where('owner_account_id', $staff->owner_account_id)
            ->where('branch_id', $branch->id)
            ->where('active', 1)
            ->count();

        $ingredientCount = Ingredient::where('owner_account_id', $staff->owner_account_id)
            ->where('branch_id', $branch->id)
            ->where('active', 1)
            ->count();

        $byBranch = [
            [
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
            ]
        ];

        // ── Transaction log ──
        $txnQuery = InventoryTransaction::with(['branch', 'items.product', 'items.ingredient'])
            ->where('owner_account_id', $staff->owner_account_id)
            ->where('branch_id', $branchId)
            ->where('active', 1)
            ->whereIn('status', ['approved', 'done'])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->where($this->getInventoryStaffCondition($staff))
            ->latest();

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
                ];
            });
        })->values();

        return response()->json([
            'success'      => true,
            'by_branch'    => $byBranch,
            'transactions' => $transactions,
            'items'        => $items,
            'branch_name'  => $branch->branch_name,
            'staff_name'   => $staff->first_name . ' ' . $staff->last_name,
        ]);
    }

    // ════════════════════════════════════════════════════════════════════
    //  FEEDBACK REPORT - Staff Version
    // ════════════════════════════════════════════════════════════════════
    private function feedbackData(Request $request)
    {
        $staff = Auth::guard('staff')->user();
        $branchId = $staff->branch_id;

        $dateFrom = $request->date_from
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::now()->subDays(6)->startOfDay();

        $dateTo = $request->date_to
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::now()->endOfDay();

        $query = Feedback::with([
            'serviceName' => fn($q) => $q->select('id', 'service_name'),
            'branch' => fn($q) => $q->select('id', 'branch_name'),
            'serviceCategory' => fn($q) => $q->select('id', 'service_category'),
        ])
        ->where('branch_id', $branchId)
        ->where('approved', 1)
        ->where('active', 1)
        ->whereBetween('created_at', [$dateFrom, $dateTo])
        ->where($this->getFeedbackStaffCondition($staff));

        $feedbacks = $query->get();

        $byBranch = $feedbacks
            ->groupBy('branch_id')
            ->map(function ($group) {
                $branch = $group->first()->branch;
                return [
                    'branch_name'       => $branch?->branch_name ?? 'N/A',
                    'avg_rating'        => round($group->avg('rating'), 1),
                    'total'             => $group->count(),
                    'star_distribution' => [
                        5 => $group->where('rating', 5)->count(),
                        4 => $group->where('rating', 4)->count(),
                        3 => $group->where('rating', 3)->count(),
                        2 => $group->where('rating', 2)->count(),
                        1 => $group->where('rating', 1)->count(),
                    ],
                    'comments' => $group
                        ->pluck('comment')
                        ->filter(fn($c) => !empty(trim($c ?? '')))
                        ->values()
                        ->toArray(),
                ];
            })
            ->values()
            ->toArray();

        $byCategory = $feedbacks
            ->groupBy('service_category_id')
            ->map(function ($group) {
                $category = $group->first()->serviceCategory;
                return [
                    'id'                => $group->first()->service_category_id,
                    'category_name'     => $category?->service_category ?? 'N/A',
                    'avg_rating'        => round($group->avg('rating'), 1),
                    'total'             => $group->count(),
                    'star_distribution' => [
                        5 => $group->where('rating', 5)->count(),
                        4 => $group->where('rating', 4)->count(),
                        3 => $group->where('rating', 3)->count(),
                        2 => $group->where('rating', 2)->count(),
                        1 => $group->where('rating', 1)->count(),
                    ],
                    'comments' => $group
                        ->pluck('comment')
                        ->filter(fn($c) => !empty(trim($c ?? '')))
                        ->values()
                        ->toArray(),
                ];
            })
            ->values()
            ->toArray();

        $overallRating = $feedbacks->avg('rating') ?? 0;
        $totalFeedbacks = $feedbacks->count();

        return response()->json([
            'success'          => true,
            'by_branch'        => $byBranch,
            'by_category'      => $byCategory,
            'overall_rating'   => round($overallRating, 1),
            'total_feedbacks'  => $totalFeedbacks,
            'branch_name'      => $byBranch[0]['branch_name'] ?? 'My Branch',
            'staff_name'       => $staff->first_name . ' ' . $staff->last_name,
        ]);
    }

    // ════════════════════════════════════════════════════════════════════
    //  SALES DATA HELPER (for PDF export)
    // ════════════════════════════════════════════════════════════════════
    private function getSalesData($dateFrom, $dateTo)
    {
        $staff = Auth::guard('staff')->user();
        $branchId = $staff->branch_id;

        $bookingQuery = DB::table('booking_payments as bp')
            ->join('bookings as b', 'b.id', '=', 'bp.booking_id')
            ->join('branches as br', 'br.id', '=', 'b.branch_id')
            ->whereBetween('bp.date_created', [$dateFrom, $dateTo])
            ->where('br.active', 1)
            ->where('bp.payment_status', 1)
            ->where('bp.active', 1)
            ->where('b.branch_id', $branchId)
            ->where($this->getBookingStaffCondition($staff))
            ->select(
                'br.id as branch_id',
                'br.branch_name',
                DB::raw('COUNT(DISTINCT b.id) as total_bookings'),
                DB::raw('SUM(bp.total_amount) as booking_revenue')
            )->groupBy('br.id', 'br.branch_name');

        $bookingRows = $bookingQuery->get()->keyBy('branch_id');

        $extensionQuery = DB::table('booking_payments as bp')
            ->join('bookings as b', 'b.id', '=', 'bp.booking_id')
            ->join('branches as br', 'br.id', '=', 'b.branch_id')
            ->whereBetween('bp.date_created', [$dateFrom, $dateTo])
            ->where('br.active', 1)
            ->where('bp.payment_status', 1)
            ->where('bp.active', 1)
            ->where('b.branch_id', $branchId)
            ->where('bp.payment_category', 2)
            ->where($this->getBookingStaffCondition($staff))
            ->select(
                'br.id as branch_id',
                'br.branch_name',
                DB::raw('COUNT(DISTINCT b.id) as total_extensions'),
                DB::raw('SUM(bp.total_amount) as extension_revenue')
            )->groupBy('br.id', 'br.branch_name');

        $extensionRows = $extensionQuery->get()->keyBy('branch_id');

        $orderQuery = DB::table('order_payments as op')
            ->join('orders as o', 'o.id', '=', 'op.order_id')
            ->join('branches as br', 'br.id', '=', 'o.branch_id')
            ->whereBetween('op.date_created', [$dateFrom, $dateTo])
            ->where('br.active', 1)
            ->where('op.order_payment_status', 1)
            ->where('op.active', 1)
            ->where('o.branch_id', $branchId)
            ->where($this->getOrderStaffCondition($staff))
            ->select(
                'br.id as branch_id',
                'br.branch_name',
                DB::raw('COUNT(DISTINCT o.id) as total_orders'),
                DB::raw('SUM(op.total_amount) as order_revenue')
            )->groupBy('br.id', 'br.branch_name');

        $orderRows = $orderQuery->get()->keyBy('branch_id');

        $rewardQuery = DB::table('reward_redemptions as rr')
            ->join('branches as br', 'br.id', '=', 'rr.branch_id')
            ->whereBetween('rr.redeemed_at', [$dateFrom, $dateTo])
            ->where('rr.active', 1)
            ->where('rr.branch_id', $branchId)
            ->where($this->getRewardStaffCondition($staff))
            ->select(
                'br.id as branch_id',
                'br.branch_name',
                DB::raw('COUNT(rr.id) as total_redemptions'),
                DB::raw('SUM(rr.discount_amount) as total_discount_amount')
            )->groupBy('br.id', 'br.branch_name');

        $rewardRows = $rewardQuery->get()->keyBy('branch_id');

        $allBranchIds = $bookingRows->keys()
            ->merge($extensionRows->keys())
            ->merge($orderRows->keys())
            ->merge($rewardRows->keys())
            ->unique();

        $byBranch = $allBranchIds->map(function ($id) use ($bookingRows, $extensionRows, $orderRows, $rewardRows) {
            $b = $bookingRows->get($id);
            $e = $extensionRows->get($id);
            $o = $orderRows->get($id);
            $r = $rewardRows->get($id);

            $bookingRev = (float) ($b->booking_revenue ?? 0);
            $extensionRev = (float) ($e->extension_revenue ?? 0);
            $orderRev = (float) ($o->order_revenue ?? 0);
            $rewardDiscount = (float) ($r->total_discount_amount ?? 0);

            $totalRevenue = $bookingRev + $extensionRev + $orderRev;

            return [
                'branch_name'          => $b->branch_name ?? $e->branch_name ?? $o->branch_name ?? ($r->branch_name ?? 'Unknown'),
                'booking_revenue'      => $bookingRev,
                'extension_revenue'    => $extensionRev,
                'order_revenue'        => $orderRev,
                'reward_discount'      => $rewardDiscount,
                'total_revenue'        => $totalRevenue,
                'total_bookings'       => (int) ($b->total_bookings ?? 0),
                'total_extensions'     => (int) ($e->total_extensions ?? 0),
                'total_orders'         => (int) ($o->total_orders ?? 0),
                'total_redemptions'    => (int) ($r->total_redemptions ?? 0),
            ];
        })->values();

        $grandTotalRevenue = $byBranch->sum(fn ($r) => $r['total_revenue']);
        $grandTotalBookings = $byBranch->sum(fn ($r) => $r['total_bookings']);
        $grandTotalExtensions = $byBranch->sum(fn ($r) => $r['total_extensions']);
        $grandTotalOrders = $byBranch->sum(fn ($r) => $r['total_orders']);
        $grandTotalRedemptions = $byBranch->sum(fn ($r) => $r['total_redemptions']);
        $grandTotalRewardDiscount = $byBranch->sum(fn ($r) => $r['reward_discount']);

        return [
            'by_branch' => $byBranch,
            'total_revenue' => $grandTotalRevenue,
            'total_bookings' => $grandTotalBookings,
            'total_extensions' => $grandTotalExtensions,
            'total_orders' => $grandTotalOrders,
            'total_redemptions' => $grandTotalRedemptions,
            'total_reward_discount' => $grandTotalRewardDiscount,
        ];
    }

    // ════════════════════════════════════════════════════════════════════
    //  PDF EXPORT METHODS
    // ════════════════════════════════════════════════════════════════════
    public function exportSalesPdf(Request $request)
    {
        $staff = Auth::guard('staff')->user();
        $branch = Branch::where('id', $staff->branch_id)->first();

        $dateFrom = $request->date_from
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::now()->subDays(6)->startOfDay();

        $dateTo = $request->date_to
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::now()->endOfDay();

        $salesData = $this->getSalesData($dateFrom, $dateTo);

        $data = [
            'date_from' => $dateFrom->format('M d, Y'),
            'date_to' => $dateTo->format('M d, Y'),
            'branch' => $branch,
            'salesData' => $salesData,
            'generated_at' => now()->format('M d, Y h:i A'),
            'company_name' => 'Linkud Hub',
            'generated_by' => $staff ? $staff->first_name . ' ' . $staff->last_name : 'System',
            'generated_by_email' => $staff ? $staff->email : 'system@linkudhub.com',
        ];

        $pdf = Pdf::loadView('staff.reports.pdf.sales_report', $data);
        $pdf->setPaper('A4', 'landscape');

        $filename = 'sales_report_' . date('Y-m-d_His') . '.pdf';
        return $pdf->download($filename);
    }

    public function exportInventoryPdf(Request $request)
    {
        $staff = Auth::guard('staff')->user();
        $branch = Branch::where('id', $staff->branch_id)->first();

        $dateFrom = $request->date_from
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::now()->subDays(6)->startOfDay();

        $dateTo = $request->date_to
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::now()->endOfDay();

        $inventoryData = $this->getInventoryDataForPdf($dateFrom, $dateTo);

        $data = [
            'date_from' => $dateFrom->format('M d, Y'),
            'date_to' => $dateTo->format('M d, Y'),
            'branch' => $branch,
            'inventoryData' => $inventoryData,
            'generated_at' => now()->format('M d, Y h:i A'),
            'company_name' => 'Linkud Hub',
            'generated_by' => $staff ? $staff->first_name . ' ' . $staff->last_name : 'System',
            'generated_by_email' => $staff ? $staff->email : 'system@linkudhub.com',
        ];

        $pdf = Pdf::loadView('staff.reports.pdf.inventory_report', $data);
        $pdf->setPaper('A4', 'landscape');

        $filename = 'inventory_report_' . date('Y-m-d_His') . '.pdf';
        return $pdf->download($filename);
    }

    public function exportFeedbackPdf(Request $request)
    {
        $staff = Auth::guard('staff')->user();
        $branch = Branch::where('id', $staff->branch_id)->first();

        $dateFrom = $request->date_from
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::now()->subDays(6)->startOfDay();

        $dateTo = $request->date_to
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::now()->endOfDay();

        $feedbackData = $this->getFeedbackDataForPdf($dateFrom, $dateTo);

        $data = [
            'date_from' => $dateFrom->format('M d, Y'),
            'date_to' => $dateTo->format('M d, Y'),
            'branch' => $branch,
            'feedbackData' => $feedbackData,
            'generated_at' => now()->format('M d, Y h:i A'),
            'company_name' => 'Linkud Hub',
            'generated_by' => $staff ? $staff->first_name . ' ' . $staff->last_name : 'System',
            'generated_by_email' => $staff ? $staff->email : 'system@linkudhub.com',
        ];

        $pdf = Pdf::loadView('staff.reports.pdf.feedback_report', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'feedback_report_' . date('Y-m-d_His') . '.pdf';
        return $pdf->download($filename);
    }

    // ════════════════════════════════════════════════════════════════════
    //  INVENTORY DATA HELPER (for PDF export)
    // ════════════════════════════════════════════════════════════════════
    private function getInventoryDataForPdf($dateFrom, $dateTo)
    {
        $staff = Auth::guard('staff')->user();
        $branchId = $staff->branch_id;
        $staffName = $staff->first_name . ' ' . $staff->last_name;

        $branch = Branch::where('id', $branchId)->where('active', 1)->first();

        if (!$branch) {
            return [
                'by_branch' => [],
                'total_beginning_balance' => 0,
                'total_stock_in' => 0,
                'total_stock_out' => 0,
                'total_ending_balance' => 0,
                'transactions' => [],
                'items' => [],
            ];
        }

        $txns = InventoryTransaction::with('items')
            ->where('owner_account_id', $staff->owner_account_id)
            ->where('branch_id', $branch->id)
            ->where('active', 1)
            ->whereIn('status', ['approved', 'done'])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->where($this->getInventoryStaffCondition($staff))
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

        $productBalance = Product::where('owner_account_id', $staff->owner_account_id)
            ->where('branch_id', $branch->id)
            ->where('active', 1)
            ->sum('quantity_in');

        $ingredientBalance = Ingredient::where('owner_account_id', $staff->owner_account_id)
            ->where('branch_id', $branch->id)
            ->where('active', 1)
            ->sum('stock_quantity_in');

        $endingBalance    = (int) $productBalance + (int) $ingredientBalance;
        $totalOut         = $totalSold + $totalDamaged + $totalExpired + $totalPulledOut;
        $beginningBalance = max(0, $endingBalance - $totalIn + $totalOut);

        $productCount = Product::where('owner_account_id', $staff->owner_account_id)
            ->where('branch_id', $branch->id)
            ->where('active', 1)
            ->count();

        $ingredientCount = Ingredient::where('owner_account_id', $staff->owner_account_id)
            ->where('branch_id', $branch->id)
            ->where('active', 1)
            ->count();

        $byBranch = [
            [
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
            ]
        ];

        $txnQuery = InventoryTransaction::with(['branch', 'items.product', 'items.ingredient'])
            ->where('owner_account_id', $staff->owner_account_id)
            ->where('branch_id', $branchId)
            ->where('active', 1)
            ->whereIn('status', ['approved', 'done'])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->where($this->getInventoryStaffCondition($staff))
            ->latest();

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
                ];
            });
        })->values();

        return [
            'by_branch' => $byBranch,
            'total_beginning_balance' => $beginningBalance,
            'total_stock_in' => $totalIn,
            'total_stock_out' => $totalOut,
            'total_ending_balance' => $endingBalance,
            'transactions' => $transactions,
            'items' => $items,
        ];
    }

    // ════════════════════════════════════════════════════════════════════
    //  FEEDBACK DATA HELPER (for PDF export)
    // ════════════════════════════════════════════════════════════════════
    private function getFeedbackDataForPdf($dateFrom, $dateTo)
    {
        $staff = Auth::guard('staff')->user();
        $branchId = $staff->branch_id;

        $query = Feedback::with([
            'serviceName' => fn($q) => $q->select('id', 'service_name'),
            'branch' => fn($q) => $q->select('id', 'branch_name'),
            'serviceCategory' => fn($q) => $q->select('id', 'service_category'),
        ])
        ->where('branch_id', $branchId)
        ->where('approved', 1)
        ->where('active', 1)
        ->whereBetween('created_at', [$dateFrom, $dateTo])
        ->where($this->getFeedbackStaffCondition($staff));

        $feedbacks = $query->get();

        $byBranch = $feedbacks
            ->groupBy('branch_id')
            ->map(function ($group) {
                $branch = $group->first()->branch;
                return [
                    'branch_name'       => $branch?->branch_name ?? 'N/A',
                    'avg_rating'        => round($group->avg('rating'), 1),
                    'total'             => $group->count(),
                    'star_distribution' => [
                        5 => $group->where('rating', 5)->count(),
                        4 => $group->where('rating', 4)->count(),
                        3 => $group->where('rating', 3)->count(),
                        2 => $group->where('rating', 2)->count(),
                        1 => $group->where('rating', 1)->count(),
                    ],
                ];
            })
            ->values()
            ->toArray();

        $byCategory = $feedbacks
            ->groupBy('service_category_id')
            ->map(function ($group) {
                $category = $group->first()->serviceCategory;
                return [
                    'id'                => $group->first()->service_category_id,
                    'category_name'     => $category?->service_category ?? 'N/A',
                    'avg_rating'        => round($group->avg('rating'), 1),
                    'total'             => $group->count(),
                    'star_distribution' => [
                        5 => $group->where('rating', 5)->count(),
                        4 => $group->where('rating', 4)->count(),
                        3 => $group->where('rating', 3)->count(),
                        2 => $group->where('rating', 2)->count(),
                        1 => $group->where('rating', 1)->count(),
                    ],
                    'comments' => $group
                        ->pluck('comment')
                        ->filter(fn($c) => !empty(trim($c ?? '')))
                        ->values()
                        ->toArray(),
                ];
            })
            ->values()
            ->toArray();

        return [
            'by_branch' => $byBranch,
            'by_category' => $byCategory,
        ];
    }

    // ════════════════════════════════════════════════════════════════════
    //  LEGACY METHODS - Keep for compatibility with existing routes
    // ════════════════════════════════════════════════════════════════════
    public function myReport(Request $request)
    {
        return $this->index($request);
    }

    public function exportMyReport(Request $request)
    {
        return $this->exportSalesPdf($request);
    }
}