<?php

namespace App\Http\Controllers\Owner;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\Branch;
use App\Models\Booking;
use App\Models\Product;
use App\Models\Feedback;
use App\Models\Ingredient;
use App\Models\ServiceName;
use App\Models\OrderPayment;
use Illuminate\Http\Request;
use App\Models\BookingPayment;
use App\Models\CustomerAccount;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AnalyticsController extends Controller
{
    public function showAnalytics(Request $request)
    {
        $owner = Auth::guard('owner')->user();
        $ownerId = $owner->id;
        
        // Get all branches owned by this owner
        $branches = Branch::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->orderBy('branch_name')
            ->get();
        
        // Get selected branch from request
        $selectedBranch = $request->input('branch_filter', 'all');
        $selectedBranchName = 'All Branches';
        
        // Determine which branches to include in queries
        if ($selectedBranch && $selectedBranch != 'all') {
            $branchIds = [$selectedBranch];
            // Get branch name for display
            $branch = Branch::find($selectedBranch);
            if ($branch) {
                $selectedBranchName = $branch->branch_name;
            }
        } else {
            $branchIds = $branches->pluck('id')->toArray();
        }

        // --- Date Filtering Logic ---
        $filterType = $request->input('filter', 'monthly');  // 'daily', 'weekly', 'monthly', 'custom'
        
        // Set default date range
        $dateTo = Carbon::now()->endOfDay();
        $dateFrom = Carbon::now()->subDays(30)->startOfDay(); // Default to 30 days

        // Handle custom date filter
        if ($filterType === 'custom') {
            // Use date pickers if 'custom' is selected
            if ($request->filled('date_from') && $request->filled('date_to')) {
                $dateFrom = Carbon::parse($request->date_from)->startOfDay();
                $dateTo = Carbon::parse($request->date_to)->endOfDay();
                
                // Ensure date_from is before date_to
                if ($dateFrom->gt($dateTo)) {
                    // Swap if they're reversed
                    [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
                }
            }
        } else {
            // Use button filters with fixed durations
            $dateTo = Carbon::now()->endOfDay();
            switch ($filterType) {
                case 'daily':
                    $dateFrom = Carbon::now()->subDays(1)->startOfDay();  // Last 1 day (24 hours)
                    break;
                case 'weekly':
                    $dateFrom = Carbon::now()->subDays(7)->startOfDay();  // Last 7 days
                    break;
                case 'monthly':
                default:
                    $dateFrom = Carbon::now()->subDays(30)->startOfDay();  // Last 30 days
                    break;
            }
        }

        // Key Metrics (now includes inventory metrics)
        $metrics = $this->calculateKeyMetrics($ownerId, $branchIds, $dateFrom, $dateTo);

        // Booking Analytics (detailed analysis)
        $bookingData = $this->getBookingAnalytics($branchIds, $dateFrom, $dateTo);

        // Product Performance
        $productData = $this->getProductPerformance($ownerId, $branchIds, $dateFrom, $dateTo);

        // Customer Insights (detailed analysis)
        $customerData = $this->getCustomerInsights($branchIds, $dateFrom, $dateTo);

        // Feedback Analytics
        $feedbackData = $this->getFeedbackAnalytics($branchIds, $dateFrom, $dateTo);

        // ML-Based Recommendations
        $recommendations = $this->generateEnhancedRecommendations(
            $ownerId, 
            $branchIds, 
            $metrics, 
            $bookingData, 
            $productData, 
            $feedbackData,
            $dateFrom, 
            $dateTo
        );

        // Service Performance (detailed analysis)
        $servicePerformance = $this->getServicePerformance($branchIds, $dateFrom, $dateTo);

        // Top Customers Data (detailed analysis)
        $topCustomers = $this->getTopCustomers($branchIds, $dateFrom, $dateTo);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'metrics' => $metrics,
                'booking_data' => $bookingData,
                'product_data' => $productData,
                'customer_data' => $customerData,
                'feedback_data' => $feedbackData,
                'recommendations' => $recommendations,
                'service_performance' => $servicePerformance,
                'top_customers' => $topCustomers,
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to' => $dateTo->format('Y-m-d'),
                'filter_type' => $filterType,
                'selected_branch' => $selectedBranch,
                'selected_branch_name' => $selectedBranchName
            ]);
        }

        return view('owner.analytics.business_analytics', compact(
            'metrics',
            'bookingData',
            'productData',
            'customerData',
            'feedbackData',
            'recommendations',
            'servicePerformance',
            'topCustomers',
            'branches',
            'dateFrom',
            'dateTo',
            'filterType',
            'selectedBranch',
            'selectedBranchName'
        ));
    }

    /**
     * Calculate key business metrics including revenue, bookings, and customer retention
     * Combines revenue from both BookingPayments and OrderPayments
     */
    private function calculateKeyMetrics($ownerId, $branchIds, $dateFrom, $dateTo)
    {
        // Total Revenue from Booking Payments
        $bookingRevenue = BookingPayment::whereIn('branch_id', $branchIds)
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->where('payment_status', 1)
            ->sum('total_amount');

        // Total Revenue from Order Payments
        $orderRevenue = OrderPayment::whereHas('order', function ($query) use ($branchIds) {
            $query->whereIn('branch_id', $branchIds);
        })
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->where('order_payment_status', 1)
            ->sum('total_amount');

        $totalRevenue = $bookingRevenue + $orderRevenue;

        // Total Bookings based on date_start
        $totalBookings = Booking::whereIn('branch_id', $branchIds)
            ->whereBetween('date_start', [$dateFrom, $dateTo])
            ->count();

        // Average Booking Value
        $avgBookingValue = $totalBookings > 0 ? $totalRevenue / $totalBookings : 0;

        // Occupancy Rate (simplified)
        $totalPossibleBookings = Booking::whereIn('branch_id', $branchIds)
            ->whereBetween('date_start', [$dateFrom, $dateTo])
            ->count();

        $completedBookings = Booking::whereIn('branch_id', $branchIds)
            ->whereBetween('date_start', [$dateFrom, $dateTo])
            ->whereIn('booking_status', [1, 4])  // Booked and Completed
            ->count();

        $occupancyRate = $totalPossibleBookings > 0 ? ($completedBookings / $totalPossibleBookings) * 100 : 0;

        // Customer Retention Rate
        $repeatCustomersQuery = DB::table('bookings')
            ->select('customer_account_id')
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('date_start', [$dateFrom, $dateTo])
            ->groupBy('customer_account_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $repeatCustomers = $repeatCustomersQuery->count();

        $totalCustomers = DB::table('bookings')
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('date_start', [$dateFrom, $dateTo])
            ->distinct()
            ->count('customer_account_id');

        $retentionRate = $totalCustomers > 0 ? ($repeatCustomers / $totalCustomers) * 100 : 0;

        // Average Rating from Feedbacks
        $averageRating = Feedback::whereIn('branch_id', $branchIds)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('approved', 1)
            ->where('active', 1)
            ->avg('rating') ?? 0;

        // POS Orders Count
        $totalOrders = Order::whereIn('branch_id', $branchIds)
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->where('order_status', 1)
            ->count();

        return [
            'total_revenue' => $totalRevenue,
            'total_bookings' => $totalBookings,
            'total_orders' => $totalOrders,
            'avg_booking_value' => $avgBookingValue,
            'occupancy_rate' => $occupancyRate,
            'retention_rate' => $retentionRate,
            'average_rating' => round($averageRating, 1),
        ];
    }

    /**
     * Get booking analytics
     */
    private function getBookingAnalytics($branchIds, $dateFrom, $dateTo)
    {
        // Bookings by Status - using date_start
        $bookingsByStatus = Booking::whereIn('branch_id', $branchIds)
            ->whereBetween('date_start', [$dateFrom, $dateTo])
            ->select('booking_status', DB::raw('COUNT(*) as count'))
            ->groupBy('booking_status')
            ->get()
            ->mapWithKeys(function ($item) {
                $statusText = $this->getBookingStatusText($item->booking_status);
                return [$statusText => $item->count];
            });

        // Bookings by Type
        $bookingsByType = Booking::whereIn('branch_id', $branchIds)
            ->whereBetween('date_start', [$dateFrom, $dateTo])
            ->select('booking_type', DB::raw('COUNT(*) as count'))
            ->groupBy('booking_type')
            ->get()
            ->mapWithKeys(function ($item) {
                $typeText = $item->booking_type == 1 ? 'Online' : 'Walk-in';
                return [$typeText => $item->count];
            });

        // Booking trends over time
        $bookingTrends = $this->getBookingTrends($branchIds, $dateFrom, $dateTo);

        return [
            'by_status' => $bookingsByStatus,
            'by_type' => $bookingsByType,
            'trends' => $bookingTrends,
        ];
    }

    /**
     * Get booking trends over time
     */
    private function getBookingTrends($branchIds, $dateFrom, $dateTo)
    {
        $daysDiff = $dateFrom->diffInDays($dateTo);

        if ($daysDiff <= 7) {
            $groupBy = 'DATE(date_start) as period';
            $dateFormat = "DATE_FORMAT(date_start, '%b %d') as label";
        } elseif ($daysDiff <= 30) {
            $groupBy = 'YEARWEEK(date_start) as period';
            $dateFormat = "CONCAT('Week ', WEEK(date_start)) as label";
        } else {
            $groupBy = "DATE_FORMAT(date_start, '%Y-%m') as period";
            $dateFormat = "DATE_FORMAT(date_start, '%b %Y') as label";
        }

        $trends = Booking::whereIn('branch_id', $branchIds)
            ->whereBetween('date_start', [$dateFrom, $dateTo])
            ->select(
                DB::raw($groupBy),
                DB::raw($dateFormat),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('period', 'label')
            ->orderBy('period')
            ->get()
            ->map(function ($item) {
                // Ensure count is an integer
                $item->count = (int) $item->count;
                return $item;
            });

        return $trends;
    }

    /**
     * Get product performance including ingredients stock levels
     */
    private function getProductPerformance($ownerId, $branchIds, $dateFrom, $dateTo)
    {
        // Top Products from Orders
        $topProducts = Order::whereIn('branch_id', $branchIds)
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->where('order_status', 1)
            ->with(['items' => function ($query) {
                $query
                    ->where('order_item_status', 1)
                    ->where('active', 1)
                    ->with('product');
            }])
            ->get()
            ->flatMap(function ($order) {
                return $order->items;
            })
            ->filter(function ($item) {
                return $item->product_id && $item->product;
            })
            ->groupBy('product_id')
            ->map(function ($items, $productId) {
                $firstItem = $items->first();
                $productName = $firstItem->product->product_name ?? 'Unknown Product';

                return [
                    'product_id' => $productId,
                    'product_name' => $productName,
                    'quantity' => $items->sum('quantity'),
                    'revenue' => (float) $items->sum('sub_total'),
                    'avg_price' => (float) $items->avg('selling_price'),
                    'order_count' => $items->unique('order_id')->count(),
                    'current_stock' => $firstItem->product->quantity_in ?? 0,
                    'stock_threshold' => $firstItem->product->quantity_threshold ?? 0,
                    'stock_status' => ($firstItem->product->quantity_in ?? 0) <= ($firstItem->product->quantity_threshold ?? 1) ? 'Low' : 'Normal'
                ];
            })
            ->sortByDesc('revenue')
            ->take(10)
            ->values();

        return [
            'top_products' => $topProducts,
        ];
    }

    /**
     * Get customer insights (detailed analysis)
     */
    private function getCustomerInsights($branchIds, $dateFrom, $dateTo)
    {
        // Top Customers by spending - using date_start
        $customerSpending = Booking::whereIn('branch_id', $branchIds)
            ->whereBetween('date_start', [$dateFrom, $dateTo])
            ->whereHas('payment', function ($q) {
                $q->where('payment_status', 1);
            })
            ->with(['customerAccount', 'payment'])
            ->get()
            ->groupBy('customer_account_id')
            ->map(function ($bookings, $customerId) {
                $firstBooking = $bookings->first();
                $customer = $firstBooking->customerAccount;

                // CORRECTED: total_amount is in booking_payments, not bookings
                $totalSpent = $bookings->sum(function ($booking) {
                    return $booking->payment ? (float) $booking->payment->total_amount : 0;
                });

                return [
                    'customer_id' => $customerId,
                    'first_name' => $customer->first_name ?? 'Unknown',
                    'last_name' => $customer->last_name ?? 'Customer',
                    'email' => $customer->email ?? 'N/A',
                    'total_spent' => $totalSpent,
                    'booking_count' => $bookings->count()
                ];
            })
            ->sortByDesc('total_spent')
            ->take(10)
            ->values();

        // Customer segmentation by frequency
        $customerSegmentation = $this->segmentCustomersByFrequency($branchIds, $dateFrom, $dateTo);

        return [
            'top_customers' => $customerSpending,
            'segmentation' => $customerSegmentation,
        ];
    }

    /**
     * Segment customers by booking frequency
     */
    private function segmentCustomersByFrequency($branchIds, $dateFrom, $dateTo)
    {
        $customerBookings = Booking::whereIn('branch_id', $branchIds)
            ->whereBetween('date_start', [$dateFrom, $dateTo])
            ->select('customer_account_id', DB::raw('COUNT(*) as booking_count'))
            ->groupBy('customer_account_id')
            ->get();

        $segments = [
            'new' => 0,        // 1 booking
            'occasional' => 0, // 2-3 bookings
            'regular' => 0,    // 4-10 bookings
            'loyal' => 0,      // 11+ bookings
        ];

        foreach ($customerBookings as $customer) {
            $count = $customer->booking_count;
            
            if ($count == 1) {
                $segments['new']++;
            } elseif ($count >= 2 && $count <= 3) {
                $segments['occasional']++;
            } elseif ($count >= 4 && $count <= 10) {
                $segments['regular']++;
            } elseif ($count > 10) {
                $segments['loyal']++;
            }
        }

        return $segments;
    }

    /**
     * Get feedback analytics including ratings and comments
     */
    private function getFeedbackAnalytics($branchIds, $dateFrom, $dateTo)
    {
        // Rating distribution
        $ratingDistribution = Feedback::whereIn('branch_id', $branchIds)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('approved', 1)
            ->where('active', 1)
            ->select('rating', DB::raw('COUNT(*) as count'))
            ->groupBy('rating')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->rating . ' stars' => $item->count];
            });

        // Average rating by service - UPDATED to include branch
        $ratingByService = Feedback::whereIn('branch_id', $branchIds)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('approved', 1)
            ->where('active', 1)
            ->with(['serviceName', 'branch'])
            ->get()
            ->groupBy('service_name_id')
            ->map(function ($feedbacks, $serviceId) {
                $firstFeedback = $feedbacks->first();
                $serviceName = $firstFeedback->serviceName->service_name ?? 'Unknown Service';
                $branchName = $firstFeedback->branch->branch_name ?? 'Unknown Branch';

                return [
                    'service_id' => $serviceId,
                    'service_name' => $serviceName,
                    'branch_name' => $branchName,
                    'average_rating' => $feedbacks->avg('rating'),
                    'feedback_count' => $feedbacks->count()
                ];
            })
            ->sortByDesc('average_rating')
            ->take(10)
            ->values();

        // Recent feedback with comments
        $recentFeedback = Feedback::whereIn('branch_id', $branchIds)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('approved', 1)
            ->where('active', 1)
            ->whereNotNull('comment')
            ->with(['serviceName', 'customerAccount'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($feedback) {
                return [
                    'customer_name' => $feedback->customerAccount
                        ? $feedback->customerAccount->first_name . ' ' . $feedback->customerAccount->last_name
                        : 'Anonymous',
                    'service_name' => $feedback->serviceName->service_name ?? 'Unknown Service',
                    'rating' => $feedback->rating,
                    'comment' => $feedback->comment,
                    'date' => $feedback->created_at->format('M j, Y')
                ];
            });

        return [
            'rating_distribution' => $ratingDistribution,
            'rating_by_service' => $ratingByService,
            'recent_feedback' => $recentFeedback,
        ];
    }

    private function getServicePerformance($branchIds, $dateFrom, $dateTo)
    {
        // Service Performance - using date_start
        $servicePerformance = Booking::whereIn('branch_id', $branchIds)
            ->whereBetween('date_start', [$dateFrom, $dateTo])
            ->whereHas('payment', function ($q) {
                $q->where('payment_status', 1);
            })
            ->with([
                'serviceName.serviceCategory',
                'payment',  // Make sure to load payment
                'branch'
            ])
            ->get()
            ->groupBy('service_name_id')
            ->map(function ($bookings, $serviceId) {
                $firstBooking = $bookings->first();
                $service = $firstBooking->serviceName;
                $serviceCategory = $service->serviceCategory ?? null;
                $branch = $firstBooking->branch ?? null;

                // CORRECTED: Access total_amount through payment relationship
                $totalRevenue = $bookings->sum(function ($booking) {
                    return $booking->payment ? (float) $booking->payment->total_amount : 0;
                });

                return [
                    'service_id' => $serviceId,
                    'service_name' => $service->service_name ?? 'Unknown Service',
                    'service_category' => $serviceCategory->service_category ?? 'Uncategorized',
                    'branch_name' => $branch->branch_name ?? 'Unknown Branch',
                    'revenue' => $totalRevenue,
                    'booking_count' => $bookings->count(),
                    'price' => $service->price ? (float) $service->price : 0
                ];
            })
            ->sortByDesc('revenue')
            ->take(10)
            ->values();

        return $servicePerformance;
    }

    /**
     * Get top customers with their details
     */
    private function getTopCustomers($branchIds, $dateFrom, $dateTo)
    {
        $topCustomers = Booking::whereIn('branch_id', $branchIds)
            ->whereBetween('date_start', [$dateFrom, $dateTo])
            ->whereHas('payment', function ($q) {
                $q->where('payment_status', 1);
            })
            ->with(['customerAccount', 'payment'])
            ->get()
            ->groupBy('customer_account_id')
            ->map(function ($bookings, $customerId) {
                $firstBooking = $bookings->first();
                $customer = $firstBooking->customerAccount;

                return [
                    'customer_id' => $customerId,
                    'name' => ($customer->first_name ?? 'Unknown') . ' ' . ($customer->last_name ?? 'Customer'),
                    'email' => $customer->email ?? 'N/A',
                    'total_spent' => $bookings->sum(function ($booking) {
                        return $booking->payment ? (float) $booking->payment->total_amount : 0;
                    }),
                    'booking_count' => $bookings->count(),
                    'last_visit' => $bookings->max('date_start')
                ];
            })
            ->sortByDesc('total_spent')
            ->take(5)
            ->values()
            ->map(function ($customer) {
                // Convert string date to Carbon before formatting
                if ($customer['last_visit']) {
                    $lastVisit = Carbon::parse($customer['last_visit']);
                    $customer['last_visit'] = $lastVisit->format('M j, Y');
                } else {
                    $customer['last_visit'] = 'Never';
                }
                return $customer;
            });

        return $topCustomers;
    }

    /**
     * ENHANCED RECOMMENDATION ENGINE
     */
    private function generateEnhancedRecommendations($ownerId, $branchIds, $metrics, $bookingData, $productData, $feedbackData, $dateFrom, $dateTo)
    {
        // Traditional rule-based recommendations
        $ruleBasedRecommendations = $this->generateRuleBasedRecommendations(
            $ownerId, $branchIds, $metrics, $bookingData, $productData, $feedbackData, $dateFrom, $dateTo
        );

        // Score rule-based recommendations
        foreach ($ruleBasedRecommendations as &$rec) {
            $rec['base_score'] = $rec['priority_score'] ?? 0.5;
            $rec['final_score'] = $rec['priority_score'] ?? 0.5;
        }

        // Sort by final score and return top recommendations
        usort($ruleBasedRecommendations, function ($a, $b) {
            $scoreA = $a['final_score'] ?? $a['base_score'] ?? 0;
            $scoreB = $b['final_score'] ?? $b['base_score'] ?? 0;
            return $scoreB <=> $scoreA;
        });

        return array_slice($ruleBasedRecommendations, 0, 12);
    }

    /**
     * Traditional rule-based recommendations (for comparison/backup)
     */
    private function generateRuleBasedRecommendations($ownerId, $branchIds, $metrics, $bookingData, $productData, $feedbackData, $dateFrom, $dateTo)
    {
        $recommendations = [];

        // 1. REVENUE OPTIMIZATION
        $this->generateRevenueRecommendations($recommendations, $metrics, $branchIds, $dateFrom, $dateTo);

        // 2. SERVICE OPTIMIZATION
        $this->generateServiceOptimizationRecommendations($recommendations, $metrics, $bookingData, $branchIds, $dateFrom, $dateTo);

        // 3. CUSTOMER RETENTION & LOYALTY
        $this->generateCustomerRetentionRecommendations($recommendations, $metrics, $branchIds, $dateFrom, $dateTo);

        // 4. POS ORDERS OPTIMIZATION
        $this->generateOrdersOptimizationRecommendations($recommendations, $branchIds, $dateFrom, $dateTo);

        // 5. FEEDBACK-DRIVEN IMPROVEMENTS
        $this->generateFeedbackBasedRecommendations($recommendations, $feedbackData, $branchIds, $dateFrom, $dateTo);

        // 6. MARKETING & PROMOTIONAL RECOMMENDATIONS
        $this->generateMarketingRecommendations($recommendations, $metrics, $branchIds, $dateFrom, $dateTo);

        // 7. OPERATIONAL EFFICIENCY
        $this->generateOperationalRecommendations($recommendations, $metrics, $bookingData, $branchIds, $dateFrom, $dateTo);

        return $recommendations;
    }

    private function generateOrdersOptimizationRecommendations(&$recommendations, $branchIds, $dateFrom, $dateTo)
    {
        // Get order analytics
        $orderAnalytics = $this->analyzeOrderPerformance($branchIds, $dateFrom, $dateTo);

        // 1. Low order volume recommendation
        if ($orderAnalytics['total_orders'] < 10) {
            $priorityScore = min(0.7 + (10 - $orderAnalytics['total_orders']) * 0.05, 0.9);
            $recommendations[] = [
                'type' => 'order_volume_increase',
                'priority' => $this->calculatePriority($priorityScore),
                'priority_score' => $priorityScore,
                'title' => 'Increase POS Order Volume',
                'description' => 'Only ' . $orderAnalytics['total_orders'] . ' POS orders this period. Consider promoting in-store purchases, adding new products, or improving checkout experience.',
                'impact' => 'Medium',
                'estimated_revenue_increase' => '15-25%',
                'action' => 'Promote in-store purchases and add popular products to menu',
                'category' => 'orders'
            ];
        }

        // 2. Low average order value
        if ($orderAnalytics['avg_order_value'] < 150) {
            $priorityScore = min(0.65 + (150 - $orderAnalytics['avg_order_value']) * 0.005, 0.85);
            $recommendations[] = [
                'type' => 'order_value_optimization',
                'priority' => $this->calculatePriority($priorityScore),
                'priority_score' => $priorityScore,
                'title' => 'Increase Average Order Value',
                'description' => 'Average POS order value is ₱' . number_format($orderAnalytics['avg_order_value'], 2) . '. Implement combo meals, upselling, or minimum order promotions.',
                'impact' => 'Medium',
                'estimated_revenue_increase' => '10-20%',
                'action' => 'Create combo deals and train staff on upselling techniques',
                'category' => 'orders'
            ];
        }

        // 3. Best-selling product analysis
        if (count($orderAnalytics['top_products']) > 0) {
            $topProduct = $orderAnalytics['top_products']->first();
            if ($topProduct['quantity'] > 20) {
                $priorityScore = 0.72;
                $recommendations[] = [
                    'type' => 'product_promotion',
                    'priority' => $this->calculatePriority($priorityScore),
                    'priority_score' => $priorityScore,
                    'title' => 'Promote Best-Selling Product',
                    'description' => "'" . $topProduct['product_name'] . "' is your top seller (" . $topProduct['quantity'] . ' units sold). Create special promotions or bundles around this product.',
                    'impact' => 'Medium',
                    'estimated_revenue_increase' => '8-15%',
                    'action' => 'Create special offers featuring best-selling products',
                    'category' => 'orders'
                ];
            }
        }

        // 4. Cross-selling opportunities
        $crossSellOpportunities = $this->identifyCrossSellOpportunities($branchIds, $dateFrom, $dateTo);
        if (count($crossSellOpportunities) > 0) {
            $priorityScore = 0.68;
            $productPairs = array_slice($crossSellOpportunities, 0, 2);
            $description = 'Customers frequently purchase these together: ';
            foreach ($productPairs as $pair) {
                $description .= "'" . $pair['product_a'] . "' + '" . $pair['product_b'] . "', ";
            }
            $description = rtrim($description, ', ') . '. Consider creating combo deals.';

            $recommendations[] = [
                'type' => 'cross_sell_optimization',
                'priority' => $this->calculatePriority($priorityScore),
                'priority_score' => $priorityScore,
                'title' => 'Create Product Bundles',
                'description' => $description,
                'impact' => 'Medium',
                'estimated_revenue_increase' => '10-18%',
                'action' => 'Create combo meals or product bundles',
                'category' => 'orders'
            ];
        }

        // 5. Seasonal product trends
        $seasonalProducts = $this->analyzeSeasonalProductTrends($branchIds);
        if ($seasonalProducts['has_trends']) {
            $priorityScore = 0.65;
            $recommendations[] = [
                'type' => 'seasonal_product_promotion',
                'priority' => $this->calculatePriority($priorityScore),
                'priority_score' => $priorityScore,
                'title' => 'Promote Seasonal Products',
                'description' => 'Seasonal trends detected. ' . $seasonalProducts['trend_description'],
                'impact' => 'Medium',
                'estimated_revenue_increase' => '12-20%',
                'action' => 'Create seasonal product promotions',
                'category' => 'orders'
            ];
        }
    }

    /**
     * Analyze order performance data
     */
    private function analyzeOrderPerformance($branchIds, $dateFrom, $dateTo)
    {
        $totalOrders = Order::whereIn('branch_id', $branchIds)
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->where('order_status', 1)  // Only completed orders
            ->count();

        $totalRevenue = OrderPayment::whereHas('order', function ($query) use ($branchIds) {
            $query
                ->whereIn('branch_id', $branchIds)
                ->where('order_status', 1);
        })
            ->where('order_payment_status', 1)
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->sum('total_amount');

        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // Get top products - FIXED
        $topProducts = Order::whereIn('branch_id', $branchIds)
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->where('order_status', 1)
            ->with(['items' => function ($query) {
                $query
                    ->where('order_item_status', 1)
                    ->where('active', 1)
                    ->with('product');
            }])
            ->get()
            ->flatMap(function ($order) {
                return $order->items;
            })
            ->groupBy('product_id')
            ->map(function ($items, $productId) {
                $firstItem = $items->first();
                $productName = $firstItem->product->product_name ?? 'Unknown Product';

                return [
                    'product_id' => $productId,
                    'product_name' => $productName,
                    'quantity' => $items->sum('quantity'),
                    'revenue' => (float) $items->sum('sub_total'),
                    'avg_price' => (float) $items->avg('selling_price')  // FIXED
                ];
            })
            ->sortByDesc('quantity')
            ->take(5)
            ->values();

        return [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'avg_order_value' => $avgOrderValue,
            'top_products' => $topProducts
        ];
    }

    /**
     * Identify cross-sell opportunities
     */
    private function identifyCrossSellOpportunities($branchIds, $dateFrom, $dateTo)
    {
        $orders = Order::whereIn('branch_id', $branchIds)
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->where('order_status', 1)
            ->with(['items' => function ($query) {
                $query
                    ->where('order_item_status', 1)
                    ->where('active', 1)
                    ->with('product');
            }])
            ->get();

        $productPairs = [];

        foreach ($orders as $order) {
            $products = $order
                ->items
                ->filter(function ($item) {
                    return $item->product && $item->product->product_name;
                })
                ->pluck('product.product_name')
                ->unique()
                ->toArray();

            // Find all pairs of products in the same order
            for ($i = 0; $i < count($products); $i++) {
                for ($j = $i + 1; $j < count($products); $j++) {
                    $pairKey = $products[$i] . '|' . $products[$j];
                    if (!isset($productPairs[$pairKey])) {
                        $productPairs[$pairKey] = [
                            'product_a' => $products[$i],
                            'product_b' => $products[$j],
                            'count' => 0
                        ];
                    }
                    $productPairs[$pairKey]['count']++;
                }
            }
        }

        // Sort by frequency and return top 5
        usort($productPairs, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return array_slice($productPairs, 0, 5);
    }

    /**
     * Analyze seasonal product trends
     */
    private function analyzeSeasonalProductTrends($branchIds)
    {
        $currentMonth = now()->month;

        // Simple seasonal mapping
        $seasonalMapping = [
            'summer' => [6, 7, 8],  // June-August
            'holiday' => [11, 12, 1],  // November-January
            'spring' => [3, 4, 5],  // March-May
            'fall' => [9, 10, 11]  // September-November
        ];

        $currentSeason = '';
        foreach ($seasonalMapping as $season => $months) {
            if (in_array($currentMonth, $months)) {
                $currentSeason = $season;
                break;
            }
        }

        if ($currentSeason) {
            $seasonalProducts = [
                'summer' => 'Cold drinks and refreshing items are popular during summer.',
                'holiday' => 'Festive items and gift sets sell well during holidays.',
                'spring' => 'Light and fresh products are preferred in spring.',
                'fall' => 'Warm beverages and comfort foods sell well in fall.'
            ];

            return [
                'has_trends' => true,
                'current_season' => $currentSeason,
                'trend_description' => $seasonalProducts[$currentSeason] ?? 'Seasonal trends detected.'
            ];
        }

        return ['has_trends' => false];
    }

    /**
     * Generate revenue optimization recommendations
     */
    private function generateRevenueRecommendations(&$recommendations, $metrics, $branchIds, $dateFrom, $dateTo)
    {
        // 1. Average Booking Value Optimization
        if ($metrics['avg_booking_value'] < 500) {
            $priorityScore = min(0.8 + (500 - $metrics['avg_booking_value']) * 0.001, 0.95);
            $recommendations[] = [
                'type' => 'revenue_optimization',
                'priority' => $this->calculatePriority($priorityScore),
                'priority_score' => $priorityScore,
                'title' => 'Increase Average Booking Value',
                'description' => 'Your average booking value is ₱' . number_format($metrics['avg_booking_value'], 2)
                    . '. Consider upselling services, creating service bundles, or introducing premium add-ons.',
                'impact' => 'High',
                'estimated_revenue_increase' => '15-25%',
                'action' => 'Create service bundles and train staff on upselling techniques',
                'category' => 'revenue'
            ];
        }

        // 2. Low Occupancy Rate Recommendation
        if ($metrics['occupancy_rate'] < 60) {
            $priorityScore = min(0.7 + (60 - $metrics['occupancy_rate']) * 0.01, 0.9);
            $recommendations[] = [
                'type' => 'occupancy_optimization',
                'priority' => $this->calculatePriority($priorityScore),
                'priority_score' => $priorityScore,
                'title' => 'Improve Service Occupancy',
                'description' => 'Occupancy rate is ' . number_format($metrics['occupancy_rate'], 1)
                    . '%. Consider optimizing your schedule, offering off-peak discounts, or improving service visibility.',
                'impact' => 'Medium',
                'estimated_revenue_increase' => '10-20%',
                'action' => 'Analyze peak hours and optimize scheduling',
                'category' => 'revenue'
            ];
        }

        // 3. Service Gap Analysis
        $servicePerformance = $this->getServicePerformance($branchIds, $dateFrom, $dateTo);
        if (count($servicePerformance) > 3) {
            $revenueVariance = $this->calculateServiceRevenueVariance($servicePerformance);
            if ($revenueVariance > 3) {
                $priorityScore = min(0.6 + ($revenueVariance - 3) * 0.1, 0.85);
                $recommendations[] = [
                    'type' => 'service_portfolio_optimization',
                    'priority' => $this->calculatePriority($priorityScore),
                    'priority_score' => $priorityScore,
                    'title' => 'Balance Service Portfolio',
                    'description' => 'Revenue distribution across services is uneven. Consider promoting underperforming services or creating balanced service packages.',
                    'impact' => 'Medium',
                    'estimated_revenue_increase' => '8-15%',
                    'action' => 'Review and rebalance service offerings',
                    'category' => 'revenue'
                ];
            }
        }
    }

    /**
     * Generate service optimization recommendations
     */
    private function generateServiceOptimizationRecommendations(&$recommendations, $metrics, $bookingData, $branchIds, $dateFrom, $dateTo)
    {
        // 1. High Cancellation Rate
        $cancellationRate = $this->calculateCancellationRate($bookingData);
        if ($cancellationRate > 15) {
            $priorityScore = min(0.85 + ($cancellationRate - 15) * 0.02, 0.95);
            $recommendations[] = [
                'type' => 'cancellation_reduction',
                'priority' => $this->calculatePriority($priorityScore),
                'priority_score' => $priorityScore,
                'title' => 'Reduce Booking Cancellations',
                'description' => 'Cancellation rate is ' . number_format($cancellationRate, 1)
                    . '%. Implement reminder systems, flexible rescheduling, or cancellation fees.',
                'impact' => 'High',
                'estimated_revenue_increase' => '10-20%',
                'action' => 'Set up automated reminders and review cancellation policies',
                'category' => 'service'
            ];
        }

        // 2. Service Performance Gaps
        $servicePerformance = $this->getServicePerformance($branchIds, $dateFrom, $dateTo);
        if (count($servicePerformance) > 2) {
            $underperformingServices = $this->identifyUnderperformingServices($servicePerformance);
            foreach ($underperformingServices as $service) {
                $priorityScore = 0.65;
                $recommendations[] = [
                    'type' => 'service_improvement',
                    'priority' => $this->calculatePriority($priorityScore),
                    'priority_score' => $priorityScore,
                    'title' => 'Improve Service Performance',
                    'description' => "'{$service['service_name']}' has low bookings (" . $service['booking_count']
                        . '). Consider rebranding, retraining staff, or adjusting pricing.',
                    'impact' => 'Medium',
                    'estimated_revenue_increase' => '5-12%',
                    'action' => 'Review service pricing and marketing strategy',
                    'category' => 'service'
                ];
            }
        }
    }

    /**
     * Generate customer retention recommendations
     */
    private function generateCustomerRetentionRecommendations(&$recommendations, $metrics, $branchIds, $dateFrom, $dateTo)
    {
        // 1. Low Retention Rate - only show if we have enough data
        if ($metrics['retention_rate'] < 30 && $metrics['total_bookings'] >= 10) {
            $priorityScore = min(0.8 + (30 - $metrics['retention_rate']) * 0.02, 0.95);
            $recommendations[] = [
                'type' => 'customer_retention',
                'priority' => $this->calculatePriority($priorityScore),
                'priority_score' => $priorityScore,
                'title' => 'Improve Customer Retention',
                'description' => 'Customer retention rate is ' . number_format($metrics['retention_rate'], 1)
                    . '%. Implement loyalty programs, personalized offers, or better follow-up.',
                'impact' => 'High',
                'estimated_revenue_increase' => '15-30%',
                'action' => 'Launch customer loyalty program',
                'category' => 'customer'
            ];
        }

        // 2. First-time Customer Conversion - only show if we have enough first-timers
        $firstTimeCustomers = $this->analyzeFirstTimeCustomers($branchIds, $dateFrom, $dateTo);

        // Only show recommendation if we have at least 5 first-time customers (statistical significance)
        if ($firstTimeCustomers['total'] >= 5) {
            if ($firstTimeCustomers['conversion_rate'] < 40) {
                $priorityScore = min(0.7 + (40 - $firstTimeCustomers['conversion_rate']) * 0.02, 0.9);
                $recommendations[] = [
                    'type' => 'first_time_conversion',
                    'priority' => $this->calculatePriority($priorityScore),
                    'priority_score' => $priorityScore,
                    'title' => 'Improve First-time Customer Conversion',
                    'description' => 'Only ' . $firstTimeCustomers['formatted_rate'] . '% of first-time customers return (' . $firstTimeCustomers['returning'] . ' out of ' . $firstTimeCustomers['total'] . '). Enhance onboarding and follow-up processes.',
                    'impact' => 'Medium',
                    'estimated_revenue_increase' => '10-20%',
                    'action' => 'Create welcome offers and follow-up sequence',
                    'category' => 'customer'
                ];
            }
        } else if ($firstTimeCustomers['total'] > 0 && $firstTimeCustomers['total'] < 5) {
            // Small sample size - show different recommendation
            $customerGrowth = $this->analyzeCustomerGrowth($branchIds, $dateFrom, $dateTo);
            if ($customerGrowth['new_customers'] > 0 && $customerGrowth['new_customers'] < 5) {
                $priorityScore = 0.6;
                $recommendations[] = [
                    'type' => 'new_customer_nurturing',
                    'priority' => $this->calculatePriority($priorityScore),
                    'priority_score' => $priorityScore,
                    'title' => 'Nurture New Customers',
                    'description' => 'You have ' . $firstTimeCustomers['total'] . ' new customers. Focus on providing excellent service to encourage repeat visits.',
                    'impact' => 'Low',
                    'estimated_revenue_increase' => '5-15%',
                    'action' => 'Personalize service for new customers and gather feedback',
                    'category' => 'customer'
                ];
            }
        }
    }

    /**
     * Generate feedback-based recommendations
     */
    private function generateFeedbackBasedRecommendations(&$recommendations, $feedbackData, $branchIds, $dateFrom, $dateTo)
    {
        // Low-rated services
        $lowRatedServices = $feedbackData['rating_by_service']->filter(function ($service) {
            return $service['average_rating'] < 3.5 && $service['feedback_count'] >= 3;
        });

        foreach ($lowRatedServices as $service) {
            $priorityScore = min(0.7 + (3.5 - $service['average_rating']) * 0.2, 0.9);
            $recommendations[] = [
                'type' => 'service_quality_improvement',
                'priority' => $this->calculatePriority($priorityScore),
                'priority_score' => $priorityScore,
                'title' => 'Improve Service Quality',
                'description' => "'{$service['service_name']}' has low ratings (" . number_format($service['average_rating'], 1)
                    . ' stars). Address customer feedback to improve satisfaction.',
                'impact' => 'High',
                'estimated_revenue_increase' => '10-20%',
                'action' => 'Review and address service delivery issues',
                'category' => 'quality'
            ];
        }

        // Overall rating improvement
        $overallRating = $feedbackData['rating_by_service']->avg('average_rating') ?? 0;
        if ($overallRating < 4.0 && $feedbackData['rating_by_service']->count() > 0) {
            $priorityScore = min(0.65 + (4.0 - $overallRating) * 0.3, 0.85);
            $recommendations[] = [
                'type' => 'overall_quality_improvement',
                'priority' => $this->calculatePriority($priorityScore),
                'priority_score' => $priorityScore,
                'title' => 'Improve Overall Service Quality',
                'description' => 'Overall service rating is ' . number_format($overallRating, 1)
                    . ' stars. Focus on consistent service delivery and staff training.',
                'impact' => 'Medium',
                'estimated_revenue_increase' => '8-15%',
                'action' => 'Implement regular quality checks and staff training',
                'category' => 'quality'
            ];
        }
    }

    /**
     * Generate marketing recommendations
     */
    private function generateMarketingRecommendations(&$recommendations, $metrics, $branchIds, $dateFrom, $dateTo)
    {
        // Customer growth analysis
        $customerGrowth = $this->analyzeCustomerGrowth($branchIds, $dateFrom, $dateTo);

        // New customer acquisition opportunity
        if ($customerGrowth['new_customers'] < 10 && $customerGrowth['growth_rate'] < 20) {
            $priorityScore = min(0.7 + (10 - $customerGrowth['new_customers']) * 0.05, 0.9);
            $recommendations[] = [
                'type' => 'customer_acquisition',
                'priority' => $this->calculatePriority($priorityScore),
                'priority_score' => $priorityScore,
                'title' => 'Boost New Customer Acquisition',
                'description' => 'Only ' . $customerGrowth['new_customers'] . ' new customers this period (' . round($customerGrowth['growth_rate'], 1) . '% growth). Consider targeted marketing campaigns.',
                'impact' => 'Medium',
                'estimated_revenue_increase' => '10-20%',
                'action' => 'Launch referral program or social media campaign',
                'category' => 'marketing'
            ];
        }

        // Seasonal marketing opportunities
        $seasonalTrends = $this->analyzeSeasonalTrends($branchIds);
        if ($seasonalTrends['has_seasonal_pattern']) {
            $priorityScore = min(0.65 + $seasonalTrends['seasonality_strength'], 0.85);
            $recommendations[] = [
                'type' => 'seasonal_marketing',
                'priority' => $this->calculatePriority($priorityScore),
                'priority_score' => $priorityScore,
                'title' => 'Plan Seasonal Marketing Campaign',
                'description' => 'Strong seasonal patterns detected. Plan ahead for upcoming ' . $seasonalTrends['upcoming_season'] . ' season.',
                'impact' => 'Medium',
                'estimated_revenue_increase' => '15-25%',
                'action' => 'Create seasonal promotions and packages',
                'category' => 'marketing'
            ];
        }

        // Email marketing opportunity
        $emailMarketingEffectiveness = $this->analyzeEmailMarketingEffectiveness($branchIds);
        if ($emailMarketingEffectiveness['open_rate'] < 20) {
            $priorityScore = 0.6;
            $recommendations[] = [
                'type' => 'email_marketing_optimization',
                'priority' => $this->calculatePriority($priorityScore),
                'priority_score' => $priorityScore,
                'title' => 'Improve Email Marketing',
                'description' => 'Email open rate is ' . $emailMarketingEffectiveness['open_rate'] . '%. Consider optimizing subject lines and content.',
                'impact' => 'Medium',
                'estimated_revenue_increase' => '8-15%',
                'action' => 'A/B test email campaigns and segment customer list',
                'category' => 'marketing'
            ];
        }
    }

    private function analyzeEmailMarketingEffectiveness($branchIds)
    {
        // This would integrate with email marketing service
        // Return simulated data for now
        return [
            'open_rate' => 18.5,
            'click_rate' => 3.2,
            'conversion_rate' => 1.8,
            'total_sent' => 500,
            'total_opens' => 92
        ];
    }

    /**
     * Generate operational recommendations
     */
    private function generateOperationalRecommendations(&$recommendations, $metrics, $bookingData, $branchIds, $dateFrom, $dateTo)
    {
        // Online vs Walk-in booking optimization
        $totalBookings = array_sum($bookingData['by_type']->toArray());
        $onlineBookings = $bookingData['by_type']['Online'] ?? 0;
        $onlinePercentage = $totalBookings > 0 ? ($onlineBookings / $totalBookings) * 100 : 0;

        if ($onlinePercentage < 30) {
            $priorityScore = min(0.65 + (30 - $onlinePercentage) * 0.02, 0.85);
            $recommendations[] = [
                'type' => 'online_booking_optimization',
                'priority' => $this->calculatePriority($priorityScore),
                'priority_score' => $priorityScore,
                'title' => 'Increase Online Bookings',
                'description' => 'Only ' . round($onlinePercentage, 1) . '% of bookings are made online. Online bookings reduce administrative work and errors.',
                'impact' => 'Medium',
                'estimated_revenue_increase' => '5-12%',
                'action' => 'Promote online booking convenience on website and social media',
                'category' => 'operations'
            ];
        }

        // Multi-branch optimization (if applicable)
        $branchCount = count($branchIds);
        if ($branchCount > 1) {
            $branchPerformance = $this->getRevenueByBranch($branchIds, $dateFrom, $dateTo);
            $performanceVariance = $this->calculateBranchPerformanceVariance($branchPerformance);

            if ($performanceVariance > 2) {
                $priorityScore = min(0.7 + ($performanceVariance - 2) * 0.1, 0.9);
                $recommendations[] = [
                    'type' => 'branch_performance_balancing',
                    'priority' => $this->calculatePriority($priorityScore),
                    'priority_score' => $priorityScore,
                    'title' => 'Balance Branch Performance',
                    'description' => 'Significant performance variance between branches (variance: ' . round($performanceVariance, 2) . 'x).',
                    'impact' => 'Medium',
                    'estimated_revenue_increase' => '8-15%',
                    'action' => 'Share best practices between branches and optimize underperforming locations',
                    'category' => 'operations'
                ];
            }
        }

        // Staff scheduling optimization
        $staffEfficiency = $this->analyzeStaffEfficiency($branchIds, $dateFrom, $dateTo);
        if ($staffEfficiency['utilization_rate'] < 70) {
            $priorityScore = 0.6;
            $recommendations[] = [
                'type' => 'staff_scheduling_optimization',
                'priority' => $this->calculatePriority($priorityScore),
                'priority_score' => $priorityScore,
                'title' => 'Optimize Staff Scheduling',
                'description' => 'Staff utilization rate is ' . round($staffEfficiency['utilization_rate'], 1) . '%. Consider adjusting schedules to match demand.',
                'impact' => 'Medium',
                'estimated_revenue_increase' => '5-10%',
                'action' => 'Implement flexible scheduling and cross-training',
                'category' => 'operations'
            ];
        }
    }

    private function analyzeStaffEfficiency($branchIds, $dateFrom, $dateTo)
    {
        // Simplified staff efficiency analysis
        $totalBookings = Booking::whereIn('branch_id', $branchIds)
            ->whereBetween('date_start', [$dateFrom, $dateTo])
            ->count();

        $totalStaffHours = 8 * 20 * 5;  // 8 hours/day * 20 days * 5 staff (simplified)

        $utilizationRate = ($totalBookings * 1) / $totalStaffHours * 100;  // Assume 1 hour per booking

        return [
            'total_bookings' => $totalBookings,
            'estimated_staff_hours' => $totalStaffHours,
            'utilization_rate' => min($utilizationRate, 100),
            'efficiency_score' => $utilizationRate > 100 ? 1.0 : $utilizationRate / 100
        ];
    }

    /**
     * UTILITY METHODS FOR RECOMMENDATION ENGINE
     */
    private function calculateCancellationRate($bookingData)
    {
        $totalBookings = array_sum($bookingData['by_status']->toArray());
        $cancelledBookings = $bookingData['by_status']['Cancelled'] ?? 0;

        return $totalBookings > 0 ? ($cancelledBookings / $totalBookings) * 100 : 0;
    }

    private function calculateServiceRevenueVariance($servicePerformance)
    {
        if (count($servicePerformance) < 2)
            return 0;

        $revenues = array_column($servicePerformance->toArray(), 'revenue');
        $maxRevenue = max($revenues);
        $minRevenue = min($revenues);

        return $minRevenue > 0 ? $maxRevenue / $minRevenue : 0;
    }

    private function identifyUnderperformingServices($servicePerformance)
    {
        $averageBookings = $servicePerformance->avg('booking_count');
        return $servicePerformance->filter(function ($service) use ($averageBookings) {
            return $service['booking_count'] < ($averageBookings * 0.5) && $service['booking_count'] > 0;
        })->values();
    }

    private function analyzeFirstTimeCustomers($branchIds, $dateFrom, $dateTo)
    {
        // Get all customers who booked in the period
        $allCustomers = Booking::whereIn('branch_id', $branchIds)
            ->whereBetween('date_start', [$dateFrom, $dateTo])
            ->distinct('customer_account_id')
            ->pluck('customer_account_id');

        if ($allCustomers->count() == 0) {
            return [
                'total' => 0,
                'returning' => 0,
                'conversion_rate' => 0
            ];
        }

        // Get all bookings for these customers (not just in this period)
        $customerBookings = Booking::whereIn('customer_account_id', $allCustomers)
            ->orderBy('date_start', 'asc')
            ->get()
            ->groupBy('customer_account_id');

        $firstTimers = [];
        $returningFirstTimers = 0;

        foreach ($customerBookings as $customerId => $bookings) {
            if ($bookings->isEmpty()) {
                continue;
            }

            // Get the first booking for this customer
            $firstBooking = $bookings->first();
            if (!$firstBooking || !$firstBooking->date_start) {
                continue;
            }

            // Convert to Carbon instance
            $firstBookingDate = Carbon::parse($firstBooking->date_start);

            // Check if this first booking was in the selected period
            if ($firstBookingDate->between($dateFrom, $dateTo)) {
                // This is a first-time customer in the selected period
                $firstTimers[] = $customerId;

                // Check if they made more than 1 booking total (not just in this period)
                if ($bookings->count() > 1) {
                    $returningFirstTimers++;
                }
            }
        }

        $totalFirstTimers = count($firstTimers);

        // Calculate conversion rate with proper formatting
        if ($totalFirstTimers > 0) {
            $conversionRate = ($returningFirstTimers / $totalFirstTimers) * 100;
            // Round to 1 decimal place, but show as integer if whole number
            $formattedRate = $conversionRate == floor($conversionRate)
                ? number_format($conversionRate, 0)
                : number_format($conversionRate, 1);
        } else {
            $conversionRate = 0;
            $formattedRate = '0';
        }

        return [
            'total' => $totalFirstTimers,
            'returning' => $returningFirstTimers,
            'conversion_rate' => $conversionRate,
            'formatted_rate' => $formattedRate
        ];
    }

    private function analyzeCustomerGrowth($branchIds, $dateFrom, $dateTo)
    {
        // Current period customers
        $currentPeriodCustomers = Booking::whereIn('branch_id', $branchIds)
            ->whereBetween('date_start', [$dateFrom, $dateTo])
            ->distinct('customer_account_id')
            ->count();

        // Previous period (same length)
        $daysDiff = $dateFrom->diffInDays($dateTo);
        $previousDateFrom = $dateFrom->copy()->subDays($daysDiff);
        $previousDateTo = $dateFrom->copy()->subDay();

        $previousPeriodCustomers = Booking::whereIn('branch_id', $branchIds)
            ->whereBetween('date_start', [$previousDateFrom, $previousDateTo])
            ->distinct('customer_account_id')
            ->count();

        // Calculate growth
        $growthRate = $previousPeriodCustomers > 0
            ? (($currentPeriodCustomers - $previousPeriodCustomers) / $previousPeriodCustomers) * 100
            : ($currentPeriodCustomers > 0 ? 100 : 0);

        return [
            'current_customers' => $currentPeriodCustomers,
            'previous_customers' => $previousPeriodCustomers,
            'new_customers' => max(0, $currentPeriodCustomers - $previousPeriodCustomers),
            'growth_rate' => round($growthRate, 2)
        ];
    }

    private function analyzeSeasonalTrends($branchIds)
    {
        $currentMonth = now()->month;

        $monthlyRevenue = [];
        for ($i = 0; $i < 6; $i++) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd = now()->subMonths($i)->endOfMonth();

            $revenue = DB::table('bookings')
                ->join('booking_payments', 'bookings.id', '=', 'booking_payments.booking_id')
                ->whereIn('bookings.branch_id', $branchIds)
                ->whereBetween('bookings.date_start', [$monthStart, $monthEnd])
                ->where('booking_payments.payment_status', 1)
                ->sum('booking_payments.total_amount');

            $monthlyRevenue[] = [
                'month' => $monthStart->format('M Y'),
                'revenue' => $revenue
            ];
        }

        // Detect seasonal patterns
        $currentSeason = $this->getSeasonFromMonth($currentMonth);
        $upcomingSeason = $this->getSeasonFromMonth(($currentMonth % 12) + 1);

        // Calculate seasonality strength
        $revenues = array_column($monthlyRevenue, 'revenue');
        $avgRevenue = array_sum($revenues) / count($revenues);
        $maxDeviation = max(array_map(function ($rev) use ($avgRevenue) {
            return abs($rev - $avgRevenue);
        }, $revenues));

        $seasonalityStrength = $avgRevenue > 0 ? ($maxDeviation / $avgRevenue) : 0;

        return [
            'has_seasonal_pattern' => $seasonalityStrength > 0.3,
            'current_season' => $currentSeason,
            'upcoming_season' => $upcomingSeason,
            'seasonality_strength' => $seasonalityStrength,
            'monthly_trends' => $monthlyRevenue
        ];
    }

    private function getSeasonFromMonth($month)
    {
        if (in_array($month, [12, 1, 2]))
            return 'Winter';
        if (in_array($month, [3, 4, 5]))
            return 'Spring';
        if (in_array($month, [6, 7, 8]))
            return 'Summer';
        return 'Fall';
    }

    private function calculateBranchPerformanceVariance($branchPerformance)
    {
        if (count($branchPerformance) < 2)
            return 0;

        $revenues = array_column($branchPerformance, 'revenue');
        $maxRevenue = max($revenues);
        $minRevenue = min($revenues);

        return $minRevenue > 0 ? $maxRevenue / $minRevenue : 0;
    }

    /**
     * Get revenue by branch (for branch performance analysis)
     */
    private function getRevenueByBranch($branchIds, $dateFrom, $dateTo)
    {
        // Booking revenue by branch
        $bookingRevenue = BookingPayment::whereIn('branch_id', $branchIds)
            ->where('payment_status', 1)
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->select(
                'branch_id',
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('"booking" as type')
            )
            ->groupBy('branch_id');

        // Order revenue by branch
        $orderRevenue = OrderPayment::whereHas('order', function ($query) use ($branchIds) {
            $query->whereIn('branch_id', $branchIds);
        })
            ->where('order_payment_status', 1)
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->select(
                'branch_id',
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('"order" as type')
            )
            ->groupBy('branch_id');

        // Combine and calculate totals
        $revenueData = $bookingRevenue->unionAll($orderRevenue)->get();

        $branchRevenue = [];
        foreach ($branchIds as $branchId) {
            $branch = Branch::find($branchId);
            if ($branch) {
                $branchRevenue[] = [
                    'branch_id' => $branchId,
                    'branch_name' => $branch->branch_name,
                    'revenue' => (float) $revenueData->where('branch_id', $branchId)->sum('revenue'),
                    'booking_revenue' => (float) $revenueData->where('branch_id', $branchId)->where('type', 'booking')->sum('revenue'),
                    'order_revenue' => (float) $revenueData->where('branch_id', $branchId)->where('type', 'order')->sum('revenue')
                ];
            }
        }

        // Sort by total revenue descending
        usort($branchRevenue, function ($a, $b) {
            return $b['revenue'] <=> $a['revenue'];
        });

        return $branchRevenue;
    }

    /**
     * Calculate priority based on score with dynamic thresholds
     */
    private function calculatePriority($score)
    {
        // Score can be >1.0 due to business context weighting
        // but we'll treat anything >0.8 as high priority
        if ($score >= 0.8) {
            return 'high';
        }
        if ($score >= 0.6) {
            return 'medium';
        }
        return 'low';
    }

    // Helper method from your existing controller
    protected function getBookingStatusText($status)
    {
        switch ($status) {
            case 0:
                return 'Cancelled';
            case 1:
                return 'Booked';
            case 2:
                return 'Pending';
            case 3:
                return 'No-show';
            case 4:
                return 'Completed';
            default:
                return 'Unknown';
        }
    }
}