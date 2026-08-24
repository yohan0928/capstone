<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Branch;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the owner's dashboard.
     */
    public function showDashboard(Request $request)
{
    $owner = Auth::guard('owner')->user();
    $ownerId = $owner->id;
    
    // Get all branches owned by this owner
    $branches = Branch::where('owner_account_id', $ownerId)
        ->orderBy('branch_name')
        ->where('active', 1)
        ->get();
    
    // Get selected branch from request
    $selectedBranch = $request->input('branch_filter', 'all');
    $selectedBranchName = 'All Branches';
    
    // Determine which branches to include in queries
    if ($selectedBranch && $selectedBranch != 'all') {
        // Find branch by UUID
        $branch = Branch::where('uuid', $selectedBranch)->first();

        if ($branch) {
            $branchIds = [$branch->id];
            $selectedBranchName = $branch->branch_name;
        } else {
            // Fallback if UUID not found
            $branchIds = $branches->pluck('id')->toArray();
            $selectedBranch = 'all';
        }
    } else {
        $branchIds = $branches->pluck('id')->toArray();
    }

    // --- Date Filtering Logic ---
    $filterType = $request->input('filter', 'monthly');  // 'daily', 'weekly', 'monthly', 'custom'

    // Check if we have date parameters
    $hasDateParams = $request->filled('date_from') && $request->filled('date_to');
    
    // Check if the requested filter is a preset
    $isPresetFilter = in_array($filterType, ['daily', 'weekly', 'monthly']);

    // Initialize date variables
    $dateFrom = null;
    $dateTo = null;

    // Logic: If it's a preset filter, use preset logic (even if dates are passed, to ensure consistency).
    // If it's custom or just dates are passed without a preset, use the dates.
    if ($isPresetFilter) {
        // Use button filters with fixed durations
        $dateTo = now()->endOfDay();
        switch ($filterType) {
            case 'daily':
                $dateFrom = now()->subDays(1)->startOfDay();  // Last 1 day (24 hours)
                break;
            case 'weekly':
                $dateFrom = now()->subDays(7)->startOfDay();  // Last 7 days
                break;
            case 'monthly':
            default:
                $dateFrom = now()->subDays(30)->startOfDay();  // Last 30 days
                break;
        }
    } elseif ($filterType === 'custom' || $hasDateParams) {
        // Use date pickers if 'custom' is selected OR if dates are provided without a preset
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : now()->subDays(30)->startOfDay();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : now()->endOfDay();
        
        // Ensure filter type is set to custom
        $filterType = 'custom';
    } else {
        // Fallback default (should logically be covered by $isPresetFilter if default is 'monthly')
        $filterType = 'monthly';
        $dateTo = now()->endOfDay();
        $dateFrom = now()->subDays(30)->startOfDay();
    }

    // If 'date_from' is after 'date_to', swap them
    if ($dateFrom && $dateTo && $dateFrom->gt($dateTo)) {
        [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
    }

    // Debug: Log the dates being used
    \Log::info('Dashboard Filter:', [
        'filter_type' => $filterType,
        'date_from' => $dateFrom ? $dateFrom->format('Y-m-d H:i:s') : null,
        'date_to' => $dateTo ? $dateTo->format('Y-m-d H:i:s') : null,
        'selected_branch' => $selectedBranch,
        'branch_ids' => $branchIds,
        'has_date_params' => $hasDateParams
    ]);

    // Initialize stats array
    $stats = [];

    // --- 1. Booking & Extension Stats ---

    // Apply date filter to booking query
    $bookingQuery = Booking::whereIn('branch_id', $branchIds);

    // For custom date range, filter bookings that occur within the date range
    if ($dateFrom && $dateTo) {
        $bookingQuery->where(function ($query) use ($dateFrom, $dateTo) {
            // Bookings that start OR end within the date range
            // OR bookings that span the entire date range
            $query
                ->whereBetween('date_start', [$dateFrom, $dateTo])
                ->orWhereBetween('date_end', [$dateFrom, $dateTo])
                ->orWhere(function ($q) use ($dateFrom, $dateTo) {
                    $q
                        ->where('date_start', '<=', $dateFrom)
                        ->where('date_end', '>=', $dateTo);
                });
        });
    }

    $bookingIds = (clone $bookingQuery)->pluck('id');

    $bookingCounts = (clone $bookingQuery)
        ->select('booking_status', DB::raw('count(*) as count'))
        ->groupBy('booking_status')
        ->get()
        ->mapWithKeys(fn($item) => [$item->booking_status => $item->count]);

    $stats['bookings'] = [
        'total' => $bookingCounts->sum(),
        0 => $bookingCounts->get(0, 0),  // Cancelled
        1 => $bookingCounts->get(1, 0),  // Booked
        2 => $bookingCounts->get(2, 0),  // Pending
        3 => $bookingCounts->get(3, 0),  // No-show
        4 => $bookingCounts->get(4, 0),  // Completed
    ];

    // Top Customers (for chart)
    $topCustomersQuery = Booking::whereIn('bookings.branch_id', $branchIds)
        ->join('customer_accounts', 'bookings.customer_account_id', '=', 'customer_accounts.id');

    if ($dateFrom && $dateTo) {
        $topCustomersQuery->where(function ($query) use ($dateFrom, $dateTo) {
            $query
                ->whereBetween('date_start', [$dateFrom, $dateTo])
                ->orWhereBetween('date_end', [$dateFrom, $dateTo])
                ->orWhere(function ($q) use ($dateFrom, $dateTo) {
                    $q
                        ->where('date_start', '<=', $dateFrom)
                        ->where('date_end', '>=', $dateTo);
                });
        });
    }

    $stats['top_customers'] = $topCustomersQuery
        ->select(
            'customer_account_id',
            'customer_accounts.first_name',
            'customer_accounts.last_name',
            DB::raw('count(bookings.id) as booking_count')
        )
        ->groupBy('customer_account_id', 'customer_accounts.first_name', 'customer_accounts.last_name')
        ->orderBy('booking_count', 'desc')
        ->limit(10)
        ->get();

        // Top Services (for chart)
        $topServicesQuery = Booking::whereIn('bookings.branch_id', $branchIds)
            ->join('service_names', 'bookings.service_name_id', '=', 'service_names.id')
            ->join('service_categories', 'bookings.service_category_id', '=', 'service_categories.id')
            ->join('branches', 'bookings.branch_id', '=', 'branches.id');

        if ($dateFrom && $dateTo) {
            $topServicesQuery->where(function ($query) use ($dateFrom, $dateTo) {
                $query
                    ->whereBetween('date_start', [$dateFrom, $dateTo])
                    ->orWhereBetween('date_end', [$dateFrom, $dateTo])
                    ->orWhere(function ($q) use ($dateFrom, $dateTo) {
                        $q
                            ->where('date_start', '<=', $dateFrom)
                            ->where('date_end', '>=', $dateTo);
                    });
            });
        }

        $stats['top_services'] = $topServicesQuery
            ->select(
                'service_name_id',
                'service_names.service_name',
                'service_categories.service_category',
                'branches.branch_name',
                DB::raw('count(bookings.id) as booking_count')
            )
            ->groupBy('service_name_id', 'service_names.service_name', 'service_categories.service_category', 'branches.branch_name')
            ->orderBy('booking_count', 'desc')
            ->limit(10)
            ->get();

        // Peak Hours (for chart)
        $peakHoursQuery = Booking::whereIn('branch_id', $branchIds);

        if ($dateFrom && $dateTo) {
            $peakHoursQuery->where(function ($query) use ($dateFrom, $dateTo) {
                $query
                    ->whereBetween('date_start', [$dateFrom, $dateTo])
                    ->orWhereBetween('date_end', [$dateFrom, $dateTo])
                    ->orWhere(function ($q) use ($dateFrom, $dateTo) {
                        $q
                            ->where('date_start', '<=', $dateFrom)
                            ->where('date_end', '>=', $dateTo);
                    });
            });
        }

        $stats['peak_hours'] = $peakHoursQuery
            ->select(DB::raw('HOUR(start_time) as hour'), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour', 'asc')
            ->get()
            ->map(function ($item) {
                $hour = $item->hour;
                $hour_12 = ($hour % 12 == 0) ? 12 : $hour % 12;
                $am_pm = ($hour >= 12) ? 'PM' : 'AM';
                $item->hour_formatted = "{$hour_12}:00 {$am_pm}";
                return $item;
            });

        // --- 2. Booking Payment Stats ---

        // This query is for cash flow, filtered by PAYMENT DATE.
        $bookingPaymentQuery = BookingPayment::whereIn('branch_id', $branchIds)
            ->where('payment_status', 1);  // Only paid

        if ($dateFrom && $dateTo) {
            $bookingPaymentQuery->whereBetween('payment_date', [$dateFrom, $dateTo]);  // Filter by payment date
        }

        // Total booking revenue
        $stats['total_booking_revenue'] = $bookingPaymentQuery->sum('total_amount');

        // Booking payment methods
        $stats['booking_payment_methods'] = (clone $bookingPaymentQuery)
            ->select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as sum'))
            ->groupBy('payment_method')
            ->get();

        // --- 3. Order & POS Stats ---

        $orderQuery = Order::whereIn('branch_id', $branchIds);

        if ($dateFrom && $dateTo) {
            $orderQuery->whereBetween('order_date', [$dateFrom, $dateTo]);  // Filter
        }

        $orderIds = (clone $orderQuery)->pluck('id');

        $stats['total_orders'] = (clone $orderQuery)->where('order_status', 1)->count();

        $orderPaymentQuery = OrderPayment::whereIn('branch_id', $branchIds)
            ->where('order_payment_status', 1);  // Only paid

        if ($dateFrom && $dateTo) {
            $orderPaymentQuery->whereBetween('payment_date', [$dateFrom, $dateTo]);  // Filter
        }

        $stats['total_order_revenue'] = (clone $orderPaymentQuery)->sum('total_amount');

        // Top Products (for chart)
        $topProductsQuery = OrderItem::whereIn('order_items.branch_id', $branchIds)
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('order_items.order_item_status', 1);

        if ($dateFrom && $dateTo) {
            $topProductsQuery->whereBetween('orders.order_date', [$dateFrom, $dateTo]);
        }

        $stats['top_products'] = $topProductsQuery
            ->select(
                'product_id',
                'products.product_name',
                DB::raw('sum(order_items.quantity) as count')
            )
            ->groupBy('product_id', 'products.product_name')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        $stats['order_payment_methods'] = (clone $orderPaymentQuery)
            ->select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as sum'))
            ->groupBy('payment_method')
            ->get();

        // --- 4. Revenue Trend Data ---
        $stats['revenue_trend'] = $this->getRevenueTrendData($branchIds, $dateFrom, $dateTo, $filterType, $selectedBranch);

        // --- 5. Branch Performance Data ---
        // Only show branch performance chart if viewing all branches
        if ($selectedBranch == 'all') {
            $stats['branch_performance'] = $this->getBranchPerformanceData($branchIds, $dateFrom, $dateTo);
        } else {
            $stats['branch_performance'] = [];
        }

        // --- 6. Payment Method Totals ---
        $paymentTotals = [];

        // Initialize all payment methods with 0
        $allPaymentMethods = [0 => 'Cash', 1 => 'GCash', 2 => 'Debit Card', 3 => 'Pay Later'];
        foreach ($allPaymentMethods as $methodId => $methodName) {
            $paymentTotals[$methodName] = 0;
        }

        // Process booking payments
        foreach ($stats['booking_payment_methods'] as $payment) {
            $methodName = $allPaymentMethods[$payment->payment_method] ?? 'Unknown';
            $paymentTotals[$methodName] += $payment->sum;
        }

        // Process order payments
        foreach ($stats['order_payment_methods'] as $payment) {
            $methodName = $allPaymentMethods[$payment->payment_method] ?? 'Unknown';
            $paymentTotals[$methodName] += $payment->sum;
        }

        // Remove payment methods with 0 revenue
        $stats['payment_totals'] = array_filter($paymentTotals, function ($amount) {
            return $amount > 0;
        });

        // --- 7. Inventory Stats (NOT date filtered) ---
        // Uses the same low-stock classification as InventoryController::getStockLevels()
        // so counts stay consistent between the Dashboard and Inventory pages:
        //   - scoped by owner_account_id (NOT by whether the branch itself is "active"),
        //     matching how the Inventory page pulls stock levels. Previously this scoped
        //     by whereIn('branch_id', $branchIds), where $branchIds for "All Branches" only
        //     included branches with active = 1 — so a deactivated branch's low-stock items
        //     silently disappeared from the Dashboard while still showing on Inventory.
        //   - when the owner has explicitly picked one specific branch, we still narrow to
        //     that branch id so the per-branch view stays meaningful
        //   - active items only
        //   - MTO products excluded (they aren't stock-tracked)
        //   - strict quantity < threshold (items exactly at threshold are NOT "low")
        //   - no product_status filter (Unavailable items still count as stock, same as Inventory)
        //   - ingredients compared on stock_quantity_in vs stock_quantity_threshold directly,
        //     regardless of unit_conversion (matches how stock-in/out actually updates the record)

        $lowStockProductsQuery = Product::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->whereNotIn('product_category', ['mto', 'made_to_order'])
            ->whereNotNull('quantity_threshold')
            ->whereColumn('quantity_in', '<', 'quantity_threshold');

        $lowStockIngredientsQuery = Ingredient::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->whereNotNull('stock_quantity_threshold')
            ->whereColumn('stock_quantity_in', '<', 'stock_quantity_threshold');

        // Only narrow to a specific branch when one is explicitly selected. When viewing
        // "All Branches", intentionally do NOT filter by $branchIds here, since that list
        // is limited to active branches and would hide items belonging to inactive ones.
        if ($selectedBranch && $selectedBranch != 'all' && !empty($branchIds)) {
            $lowStockProductsQuery->whereIn('branch_id', $branchIds);
            $lowStockIngredientsQuery->whereIn('branch_id', $branchIds);
        }

        // Low Stock Products
        $stats['low_stock_products'] = $lowStockProductsQuery
            ->orderBy('quantity_in', 'asc')
            ->limit(10)
            ->get();

        // Low Stock Ingredients
        $stats['low_stock_ingredients'] = $lowStockIngredientsQuery
            ->orderBy('stock_quantity_in', 'asc')
            ->limit(10)
            ->get();

        // --- 8. Helper Maps ---
        $maps = [
            'booking_status' => [
                0 => 'Cancelled',
                1 => 'Booked',
                2 => 'Pending',
                3 => 'No-show',
                4 => 'Completed',
            ],
            'payment_method' => [
                0 => 'Cash',
                1 => 'GCash',
                2 => 'Debit Card',
                3 => 'Pay Later',
            ],
            'order_payment_status' => [
                1 => 'Paid',
                3 => 'Unpaid',
            ]
        ];

        // Debug: Log the stats being returned
        \Log::info('Dashboard Stats Summary:', [
            'total_bookings' => $stats['bookings']['total'] ?? 0,
            'total_orders' => $stats['total_orders'] ?? 0,
            'booking_revenue' => $stats['total_booking_revenue'] ?? 0,
            'order_revenue' => $stats['total_order_revenue'] ?? 0,
            'top_products_count' => count($stats['top_products'] ?? []),
            'top_customers_count' => count($stats['top_customers'] ?? [])
        ]);

        // Pass data to view
        return view('owner.dashboard.dashboard', compact(
            'stats', 
            'maps', 
            'filterType', 
            'dateFrom', 
            'dateTo',
            'branches',
            'selectedBranch',
            'selectedBranchName'
        ));
    }

    /**
     * Get revenue trend data based on filter type
     */
    private function getRevenueTrendData($branchIds, $dateFrom, $dateTo, $filterType, $selectedBranch = null)
    {
        $revenueData = [];
        $categories = [];
        $seriesData = [];

        $daysDiff = $dateFrom->diffInDays($dateTo);

        // If viewing a single branch, get branch name for chart label
        $branchName = '';
        if ($selectedBranch && $selectedBranch != 'all') {
            $branch = Branch::where('uuid', $selectedBranch)->first();
            if ($branch) {
                $branchName = $branch->branch_name;
            }
        }

        switch ($filterType) {
            case 'daily':
                // Last 1 day - show hourly data
                $currentDate = $dateFrom->copy();
                $hoursToShow = min(24, $dateFrom->diffInHours($dateTo) + 1);
                
                for ($i = 0; $i < $hoursToShow; $i++) {
                    $hourStart = $currentDate->copy()->addHours($i);
                    $hourEnd = $hourStart->copy()->addHour();

                    $bookingRevenue = BookingPayment::whereIn('branch_id', $branchIds)
                        ->where('payment_status', 1)
                        ->whereBetween('payment_date', [$hourStart, $hourEnd])
                        ->sum('total_amount');

                    $orderRevenue = OrderPayment::whereIn('branch_id', $branchIds)
                        ->where('order_payment_status', 1)
                        ->whereBetween('payment_date', [$hourStart, $hourEnd])
                        ->sum('total_amount');

                    $categories[] = $hourStart->format('g A');
                    $seriesData[] = floatval($bookingRevenue + $orderRevenue);
                }
                break;

            case 'weekly':
                // Last 7 days - show daily data
                $currentDate = $dateFrom->copy();
                $daysToShow = min(7, $daysDiff + 1);
                
                for ($i = 0; $i < $daysToShow; $i++) {
                    $dayStart = $currentDate->copy()->addDays($i)->startOfDay();
                    $dayEnd = $dayStart->copy()->endOfDay();

                    $bookingRevenue = BookingPayment::whereIn('branch_id', $branchIds)
                        ->where('payment_status', 1)
                        ->whereBetween('payment_date', [$dayStart, $dayEnd])
                        ->sum('total_amount');

                    $orderRevenue = OrderPayment::whereIn('branch_id', $branchIds)
                        ->where('order_payment_status', 1)
                        ->whereBetween('payment_date', [$dayStart, $dayEnd])
                        ->sum('total_amount');

                    $categories[] = $dayStart->format('D');
                    $seriesData[] = floatval($bookingRevenue + $orderRevenue);
                }
                break;

            case 'monthly':
            default:
                // Last 30 days - group appropriately based on actual duration
                if ($daysDiff <= 14) {
                    // Show daily data for shorter periods
                    $currentDate = $dateFrom->copy();
                    $daysToShow = min(30, $daysDiff + 1);
                    
                    for ($i = 0; $i < $daysToShow; $i++) {
                        $dayStart = $currentDate->copy()->addDays($i)->startOfDay();
                        $dayEnd = $dayStart->copy()->endOfDay();

                        $bookingRevenue = BookingPayment::whereIn('branch_id', $branchIds)
                            ->where('payment_status', 1)
                            ->whereBetween('payment_date', [$dayStart, $dayEnd])
                            ->sum('total_amount');

                        $orderRevenue = OrderPayment::whereIn('branch_id', $branchIds)
                            ->where('order_payment_status', 1)
                            ->whereBetween('payment_date', [$dayStart, $dayEnd])
                            ->sum('total_amount');

                        $categories[] = $dayStart->format('M j');
                        $seriesData[] = floatval($bookingRevenue + $orderRevenue);
                    }
                } else {
                    // Show weekly data for longer periods
                    $currentDate = $dateFrom->copy()->startOfWeek();
                    $endDate = $dateTo->copy()->endOfWeek();
                    $weekCount = 0;
                    $maxWeeks = 6;

                    while ($currentDate <= $endDate && $weekCount < $maxWeeks) {
                        $weekStart = $currentDate->copy()->startOfWeek();
                        $weekEnd = $currentDate->copy()->endOfWeek();

                        // Ensure we don't go beyond the selected date range
                        $effectiveWeekStart = $weekStart->max($dateFrom);
                        $effectiveWeekEnd = $weekEnd->min($dateTo);

                        $bookingRevenue = BookingPayment::whereIn('branch_id', $branchIds)
                            ->where('payment_status', 1)
                            ->whereBetween('payment_date', [$effectiveWeekStart, $effectiveWeekEnd])
                            ->sum('total_amount');

                        $orderRevenue = OrderPayment::whereIn('branch_id', $branchIds)
                            ->where('order_payment_status', 1)
                            ->whereBetween('payment_date', [$effectiveWeekStart, $effectiveWeekEnd])
                            ->sum('total_amount');

                        $categories[] = 'W' . ($weekCount + 1);
                        $seriesData[] = floatval($bookingRevenue + $orderRevenue);

                        $currentDate->addWeek();
                        $weekCount++;
                    }
                }
                break;
        }

        // If no data, use sample data
        if (empty($seriesData) || array_sum($seriesData) === 0) {
            return $this->getSampleRevenueData($filterType, $daysDiff, $branchName);
        }

        return [
            'categories' => $categories,
            'series' => $seriesData,
            'branch_name' => $branchName
        ];
    }

    /**
     * Get sample revenue data for demonstration
     */
    private function getSampleRevenueData($filterType, $daysDiff = null, $branchName = '')
    {
        // Adjust sample data based on branch name for more realistic demo
        $branchMultiplier = $branchName ? 0.7 : 1.0; // Single branch typically has less revenue than all branches

        switch ($filterType) {
            case 'daily':
                // Sample hourly data for 1 day
                $categories = ['6 AM', '9 AM', '12 PM', '3 PM', '6 PM', '9 PM', '12 AM'];
                $series = [800 * $branchMultiplier, 1200 * $branchMultiplier, 2500 * $branchMultiplier, 
                          1800 * $branchMultiplier, 3200 * $branchMultiplier, 1500 * $branchMultiplier, 
                          600 * $branchMultiplier];
                break;

            case 'weekly':
                // Sample daily data for 7 days
                $categories = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                $series = [3100 * $branchMultiplier, 4000 * $branchMultiplier, 2800 * $branchMultiplier, 
                          5100 * $branchMultiplier, 4200 * $branchMultiplier, 6900 * $branchMultiplier, 
                          5000 * $branchMultiplier];
                break;

            case 'monthly':
            default:
                if ($daysDiff && $daysDiff <= 14) {
                    // Sample daily data for short monthly period
                    $days = min(14, $daysDiff);
                    $categories = [];
                    $series = [];
                    $currentDate = now()->subDays($days - 1);
                    
                    for ($i = 0; $i < $days; $i++) {
                        $categories[] = $currentDate->copy()->addDays($i)->format('M j');
                        $series[] = rand(2000, 7000) * $branchMultiplier;
                    }
                } else {
                    // Sample weekly data for longer monthly period
                    $categories = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
                    $series = [15000 * $branchMultiplier, 18000 * $branchMultiplier, 
                              22000 * $branchMultiplier, 19000 * $branchMultiplier];
                }
                break;
        }

        return [
            'categories' => $categories,
            'series' => $series,
            'branch_name' => $branchName
        ];
    }

    /**
     * Get branch performance data
     */
    private function getBranchPerformanceData($branchIds, $dateFrom, $dateTo)
    {
        $branchPerformance = [];

        foreach ($branchIds as $branchId) {
            $branch = Branch::find($branchId);

            if (!$branch)
                continue;

            // Booking revenue for the branch
            $bookingRevenue = BookingPayment::where('branch_id', $branchId)
                ->where('payment_status', 1);

            if ($dateFrom && $dateTo) {
                $bookingRevenue->whereBetween('payment_date', [$dateFrom, $dateTo]);
            }

            $bookingRevenue = $bookingRevenue->sum('total_amount');

            // Order revenue for the branch
            $orderRevenue = OrderPayment::where('branch_id', $branchId)
                ->where('order_payment_status', 1);

            if ($dateFrom && $dateTo) {
                $orderRevenue->whereBetween('payment_date', [$dateFrom, $dateTo]);
            }

            $orderRevenue = $orderRevenue->sum('total_amount');

            $totalRevenue = $bookingRevenue + $orderRevenue;

            // Booking count for the branch
            $bookingCountQuery = Booking::where('branch_id', $branchId);
            
            if ($dateFrom && $dateTo) {
                $bookingCountQuery->where(function ($query) use ($dateFrom, $dateTo) {
                    $query
                        ->whereBetween('date_start', [$dateFrom, $dateTo])
                        ->orWhereBetween('date_end', [$dateFrom, $dateTo])
                        ->orWhere(function ($q) use ($dateFrom, $dateTo) {
                            $q
                                ->where('date_start', '<=', $dateFrom)
                                ->where('date_end', '>=', $dateTo);
                        });
                });
            }

            $bookingCount = $bookingCountQuery->count();

            // Order count for the branch
            $orderCountQuery = Order::where('branch_id', $branchId)
                ->where('order_status', 1);
                
            if ($dateFrom && $dateTo) {
                $orderCountQuery->whereBetween('order_date', [$dateFrom, $dateTo]);
            }

            $orderCount = $orderCountQuery->count();

            $branchPerformance[] = [
                'branch_name' => $branch->branch_name,
                'revenue' => floatval($totalRevenue),
                'booking_count' => $bookingCount,
                'order_count' => $orderCount
            ];
        }

        // Sort by revenue descending
        usort($branchPerformance, function ($a, $b) {
            return $b['revenue'] <=> $a['revenue'];
        });

        return array_slice($branchPerformance, 0, 10);  // Return top 10 branches
    }
}