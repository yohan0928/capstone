<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\StaffAccount;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StaffReportController extends Controller
{
    public function staffReport(Request $request)
    {
        $owner = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        // Get all staff under the owner's branches
        $staffQuery = StaffAccount::with(['branch'])
            ->whereHas('branch', function ($query) use ($ownerId) {
                $query
                    ->where('owner_account_id', $ownerId)
                    ->where('active', 1);
            })
            ->where('active', 1);

        // Search filter - search only in first_name and last_name
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $staffQuery->where(function ($q) use ($searchTerm) {
                $q
                    ->where('first_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('last_name', 'LIKE', "%{$searchTerm}%");
            });
        }

        $staff = $staffQuery->orderBy('first_name')->paginate(50);

        // Get branches for staff display (but not for filter)
        $branches = Branch::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->orderBy('branch_name')
            ->get();

        return view('owner.reports.staff_report', compact('staff', 'branches'));
    }

    public function reportData($staffUuid, Request $request)
    {
        $owner = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        // Get staff and verify they belong to owner's branch - using UUID
        $staff = StaffAccount::with(['branch'])
            ->where('uuid', $staffUuid)
            ->whereHas('branch', function ($query) use ($ownerId) {
                $query->where('owner_account_id', $ownerId);
            })
            ->firstOrFail();

        // Get all branches for the filter
        $branches = Branch::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->orderBy('branch_name')
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

        // Date range filters
        $dateRange = $this->getDateRange($request);
        $startDate = $dateRange['start_date'];
        $endDate = $dateRange['end_date'];

        // Get statistics based on staff's activities WITH branch filter
        $stats = $this->getStaffStatistics($staff, $startDate, $endDate, $branchIds);

        return view('owner.reports.report_data', compact(
            'staff',
            'stats',
            'startDate',
            'endDate',
            'branches',
            'selectedBranch',
            'selectedBranchName'
        ));
    }

    private function getDateRange(Request $request)
    {
        \Log::info('Date Range Request:', [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'has_start_date' => $request->filled('start_date'),
            'has_end_date' => $request->filled('end_date'),
        ]);

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->subMonth()->startOfDay();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfDay();

        // Ensure end date is not before start date
        if ($endDate->lt($startDate)) {
            $endDate = $startDate->copy()->endOfDay();
        }

        \Log::info('Date Range Calculated:', [
            'start_date' => $startDate->format('Y-m-d H:i:s'),
            'end_date' => $endDate->format('Y-m-d H:i:s'),
        ]);

        return [
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
    }

    private function getStaffStatistics($staff, $startDate, $endDate, $branchIds = null)
    {
        // If branchIds is provided, filter by those branches
        $branchCondition = function ($query) use ($staff, $branchIds) {
            $query->where(function ($q) use ($staff) {
                $q
                    ->where('created_by', $staff->id)
                    ->where('created_by_type', 'staff')
                    ->orWhere('updated_by', $staff->id)
                    ->where('updated_by_type', 'staff')
                    ->orWhere('last_updated_by', $staff->id)
                    ->where('last_updated_by_type', 'staff');
            });

            // Apply branch filter if specified
            if ($branchIds) {
                $query->whereIn('branch_id', $branchIds);
            }
        };

        // 1. Get ALL branches where staff has handled records (regardless of their assigned branch)
        $branchBreakdown = Booking::where($branchCondition)
            ->whereBetween('date_created', [$startDate, $endDate])
            ->with('branch')
            ->get()
            ->groupBy('branch_id')
            ->map(function ($bookings, $branchId) {
                $branch = $bookings->first()->branch ?? null;
                return [
                    'branch_id' => $branchId,
                    'branch_name' => $branch ? $branch->branch_name : 'N/A',
                    'booking_count' => $bookings->count()
                ];
            })
            ->values();

        // 2. Total Bookings created/updated by staff (WITH branch filter)
        $totalBookings = Booking::where($branchCondition)
            ->whereBetween('date_created', [$startDate, $endDate])
            ->count();

        // 3. Total Customers - Count unique customer_account_id from bookings handled by staff (WITH branch filter)
        $totalCustomers = Booking::where($branchCondition)
            ->whereBetween('date_created', [$startDate, $endDate])
            ->distinct('customer_account_id')
            ->count('customer_account_id');

        // 4. Total Hours Used in bookings handled by staff (WITH branch filter)
        $totalMinutesUsed = DB::table('customer_checkins')
            ->join('bookings', 'customer_checkins.booking_id', '=', 'bookings.id')
            ->where(function ($query) use ($staff, $branchIds) {
                $query->where(function ($q) use ($staff) {
                    $q
                        ->where('bookings.created_by', $staff->id)
                        ->where('bookings.created_by_type', 'staff')
                        ->orWhere('bookings.updated_by', $staff->id)
                        ->where('bookings.updated_by_type', 'staff')
                        ->orWhere('bookings.last_updated_by', $staff->id)
                        ->where('bookings.last_updated_by_type', 'staff');
                });

                // Apply branch filter if specified
                if ($branchIds) {
                    $query->whereIn('bookings.branch_id', $branchIds);
                }
            })
            ->whereBetween('bookings.date_created', [$startDate, $endDate])
            ->sum('customer_checkins.total_time_used');

        // Convert to hours and minutes format
        $hours = floor($totalMinutesUsed / 60);
        $minutes = $totalMinutesUsed % 60;

        if ($hours > 0) {
            $totalHoursUsed = "{$hours}h {$minutes}m";
        } else {
            $totalHoursUsed = "{$minutes}m";
        }

        // 5. Services Breakdown based on bookings (WITH branch filter)
        $serviceBreakdown = Booking::where($branchCondition)
            ->whereBetween('date_created', [$startDate, $endDate])
            ->with(['serviceName', 'serviceCategory', 'branch'])
            ->get()
            ->groupBy(function ($booking) {
                return $booking->branch_id . '-' . $booking->service_name_id;
            })
            ->map(function ($bookings) {
                $firstBooking = $bookings->first();
                return [
                    'branch_id' => $firstBooking->branch_id,
                    'branch_name' => $firstBooking->branch ? $firstBooking->branch->branch_name : 'N/A',
                    'service_name' => $firstBooking->serviceName ? $firstBooking->serviceName->service_name : 'N/A',
                    'service_category' => $firstBooking->serviceCategory ? $firstBooking->serviceCategory->service_category : 'N/A',
                    'total_bookings' => $bookings->count(),
                    'total_revenue' => $bookings->sum(function ($booking) {
                        return $booking->serviceName ? $booking->serviceName->price : 0;
                    })
                ];
            })
            ->values();

        // 6. Orders Breakdown with Order Items (WITH branch filter)
        $ordersBreakdown = Order::where(function ($query) use ($staff, $branchIds) {
            $query->where(function ($q) use ($staff) {
                $q
                    ->where('created_by', $staff->id)
                    ->where('created_by_type', 'staff')
                    ->orWhere('updated_by', $staff->id)
                    ->where('updated_by_type', 'staff')
                    ->orWhere('last_updated_by', $staff->id)
                    ->where('last_updated_by_type', 'staff');
            });

            // Apply branch filter if specified
            if ($branchIds) {
                $query->whereIn('branch_id', $branchIds);
            }
        })
            ->whereBetween('date_created', [$startDate, $endDate])
            ->with(['items.product', 'payments', 'branch'])
            ->get();

        // 7. Order Items Breakdown - Products Sold/Bought (WITH branch filter)
        $orderItemsBreakdown = [];
        $orders = Order::where(function ($query) use ($staff, $branchIds) {
            $query->where(function ($q) use ($staff) {
                $q
                    ->where('created_by', $staff->id)
                    ->where('created_by_type', 'staff')
                    ->orWhere('updated_by', $staff->id)
                    ->where('updated_by_type', 'staff')
                    ->orWhere('last_updated_by', $staff->id)
                    ->where('last_updated_by_type', 'staff');
            });

            // Apply branch filter if specified
            if ($branchIds) {
                $query->whereIn('branch_id', $branchIds);
            }
        })
            ->whereBetween('date_created', [$startDate, $endDate])
            ->with(['items.product', 'branch'])
            ->get();

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $key = $order->branch_id . '-' . $item->product_id;

                if (!isset($orderItemsBreakdown[$key])) {
                    $orderItemsBreakdown[$key] = [
                        'branch_id' => $order->branch_id,
                        'branch_name' => $order->branch ? $order->branch->branch_name : 'N/A',
                        'product_name' => $item->product ? $item->product->product_name : 'N/A',
                        'product_type' => $item->product ? $item->product->product_type : 'N/A',
                        'total_quantity_sold' => 0,
                        'total_revenue' => 0,
                        'order_count' => 0,
                        'order_refs' => []
                    ];
                }

                $orderItemsBreakdown[$key]['total_quantity_sold'] += $item->quantity;
                $orderItemsBreakdown[$key]['total_revenue'] += $item->sub_total;

                if (!in_array($order->order_ref_no, $orderItemsBreakdown[$key]['order_refs'])) {
                    $orderItemsBreakdown[$key]['order_refs'][] = $order->order_ref_no;
                    $orderItemsBreakdown[$key]['order_count']++;
                }
            }
        }

        $orderItemsBreakdown = array_values($orderItemsBreakdown);

        // 8. Inventory Deduction Breakdown (Products and Ingredients) - WITH branch filter
        $inventoryDeduction = $this->getInventoryDeduction($staff, $startDate, $endDate, $branchIds);

        // 9. Revenue Breakdown - WITH branch filter
        $bookingRevenue = [];
        $bookingPayments = BookingPayment::whereHas('booking', function ($query) use ($staff, $branchIds) {
            $query->where(function ($q) use ($staff) {
                $q
                    ->where('created_by', $staff->id)
                    ->where('created_by_type', 'staff')
                    ->orWhere('updated_by', $staff->id)
                    ->where('updated_by_type', 'staff')
                    ->orWhere('last_updated_by', $staff->id)
                    ->where('last_updated_by_type', 'staff');
            });

            // Apply branch filter if specified
            if ($branchIds) {
                $query->whereIn('branch_id', $branchIds);
            }
        })
            ->whereBetween('date_created', [$startDate, $endDate])
            ->where('payment_status', 1)
            ->with(['booking.branch'])
            ->get();

        foreach ($bookingPayments as $payment) {
            $key = $payment->booking->branch_id . '-' . $payment->payment_category . '-' . $payment->payment_method;

            if (!isset($bookingRevenue[$key])) {
                $bookingRevenue[$key] = [
                    'branch_id' => $payment->booking->branch_id,
                    'branch_name' => $payment->booking->branch ? $payment->booking->branch->branch_name : 'N/A',
                    'payment_category' => $payment->payment_category,
                    'payment_method' => $payment->payment_method,
                    'payment_count' => 0,
                    'total_amount' => 0
                ];
            }

            $bookingRevenue[$key]['payment_count']++;
            $bookingRevenue[$key]['total_amount'] += $payment->total_amount;
        }

        $bookingRevenue = array_values($bookingRevenue);

        $orderRevenue = [];
        $orderPayments = OrderPayment::whereHas('order', function ($query) use ($staff, $branchIds) {
            $query->where(function ($q) use ($staff) {
                $q
                    ->where('created_by', $staff->id)
                    ->where('created_by_type', 'staff')
                    ->orWhere('updated_by', $staff->id)
                    ->where('updated_by_type', 'staff')
                    ->orWhere('last_updated_by', $staff->id)
                    ->where('last_updated_by_type', 'staff');
            });

            // Apply branch filter if specified
            if ($branchIds) {
                $query->whereIn('branch_id', $branchIds);
            }
        })
            ->whereBetween('date_created', [$startDate, $endDate])
            ->where('order_payment_status', 1)
            ->with(['order.branch'])
            ->get();

        foreach ($orderPayments as $payment) {
            $key = $payment->order->branch_id . '-' . $payment->payment_method;

            if (!isset($orderRevenue[$key])) {
                $orderRevenue[$key] = [
                    'branch_id' => $payment->order->branch_id,
                    'branch_name' => $payment->order->branch ? $payment->order->branch->branch_name : 'N/A',
                    'payment_method' => $payment->payment_method,
                    'payment_count' => 0,
                    'total_amount' => 0
                ];
            }

            $orderRevenue[$key]['payment_count']++;
            $orderRevenue[$key]['total_amount'] += $payment->total_amount;
        }

        $orderRevenue = array_values($orderRevenue);

        $totalRevenue = array_sum(array_column($bookingRevenue, 'total_amount'))
            + array_sum(array_column($orderRevenue, 'total_amount'));

        // 10. Payment Method Breakdown - WITH branch filter
        $paymentMethodBreakdown = [];
        $bookingPaymentsForBreakdown = BookingPayment::whereHas('booking', function ($query) use ($staff, $branchIds) {
            $query->where(function ($q) use ($staff) {
                $q
                    ->where('created_by', $staff->id)
                    ->where('created_by_type', 'staff')
                    ->orWhere('updated_by', $staff->id)
                    ->where('updated_by_type', 'staff')
                    ->orWhere('last_updated_by', $staff->id)
                    ->where('last_updated_by_type', 'staff');
            });

            // Apply branch filter if specified
            if ($branchIds) {
                $query->whereIn('branch_id', $branchIds);
            }
        })
            ->whereBetween('date_created', [$startDate, $endDate])
            ->where('payment_status', 1)
            ->with(['booking.branch'])
            ->get();

        foreach ($bookingPaymentsForBreakdown as $payment) {
            $key = $payment->booking->branch_id . '-' . $payment->payment_method;

            if (!isset($paymentMethodBreakdown[$key])) {
                $paymentMethodBreakdown[$key] = [
                    'branch_id' => $payment->booking->branch_id,
                    'branch_name' => $payment->booking->branch ? $payment->booking->branch->branch_name : 'N/A',
                    'payment_method' => $payment->payment_method,
                    'count' => 0,
                    'total_amount' => 0
                ];
            }

            $paymentMethodBreakdown[$key]['count']++;
            $paymentMethodBreakdown[$key]['total_amount'] += $payment->total_amount;
        }

        $paymentMethodBreakdown = array_values($paymentMethodBreakdown);

        // 11. Order Payments Breakdown - WITH branch filter
        $orderPaymentBreakdown = [];
        $orderPaymentsForBreakdown = OrderPayment::whereHas('order', function ($query) use ($staff, $branchIds) {
            $query->where(function ($q) use ($staff) {
                $q
                    ->where('created_by', $staff->id)
                    ->where('created_by_type', 'staff')
                    ->orWhere('updated_by', $staff->id)
                    ->where('updated_by_type', 'staff')
                    ->orWhere('last_updated_by', $staff->id)
                    ->where('last_updated_by_type', 'staff');
            });

            // Apply branch filter if specified
            if ($branchIds) {
                $query->whereIn('branch_id', $branchIds);
            }
        })
            ->whereBetween('date_created', [$startDate, $endDate])
            ->with(['order.branch'])
            ->get();

        foreach ($orderPaymentsForBreakdown as $payment) {
            $key = $payment->order->branch_id . '-' . $payment->order_payment_status . '-' . $payment->payment_method;

            if (!isset($orderPaymentBreakdown[$key])) {
                $orderPaymentBreakdown[$key] = [
                    'branch_id' => $payment->order->branch_id,
                    'branch_name' => $payment->order->branch ? $payment->order->branch->branch_name : 'N/A',
                    'order_payment_status' => $payment->order_payment_status,
                    'payment_method' => $payment->payment_method,
                    'count' => 0,
                    'total_amount' => 0
                ];
            }

            $orderPaymentBreakdown[$key]['count']++;
            $orderPaymentBreakdown[$key]['total_amount'] += $payment->total_amount;
        }

        $orderPaymentBreakdown = array_values($orderPaymentBreakdown);

        // 12. Get all bookings details for the staff - WITH branch filter
        $bookingsDetails = Booking::where($branchCondition)
            ->whereBetween('date_created', [$startDate, $endDate])
            ->with(['customerAccount', 'serviceName', 'serviceCategory', 'branch', 'seat', 'payment'])
            ->get();

        // 13. Get all orders details for the staff - WITH branch filter
        $ordersDetails = Order::where(function ($query) use ($staff, $branchIds) {
            $query->where(function ($q) use ($staff) {
                $q
                    ->where('created_by', $staff->id)
                    ->where('created_by_type', 'staff')
                    ->orWhere('updated_by', $staff->id)
                    ->where('updated_by_type', 'staff')
                    ->orWhere('last_updated_by', $staff->id)
                    ->where('last_updated_by_type', 'staff');
            });

            // Apply branch filter if specified
            if ($branchIds) {
                $query->whereIn('branch_id', $branchIds);
            }
        })
            ->whereBetween('date_created', [$startDate, $endDate])
            ->with(['items.product', 'payments', 'branch', 'customer'])
            ->get();

        return [
            'branch_breakdown' => $branchBreakdown,
            'total_bookings' => $totalBookings,
            'total_customers' => $totalCustomers,
            'total_hours_used' => $totalHoursUsed,
            'total_minutes_used' => $totalMinutesUsed,
            'service_breakdown' => $serviceBreakdown,
            'orders_breakdown' => $ordersBreakdown,
            'order_items_breakdown' => $orderItemsBreakdown,
            'inventory_deduction' => $inventoryDeduction,
            'booking_revenue_breakdown' => $bookingRevenue,
            'order_revenue_breakdown' => $orderRevenue,
            'payment_method_breakdown' => $paymentMethodBreakdown,
            'order_payment_breakdown' => $orderPaymentBreakdown,
            'bookings_details' => $bookingsDetails,
            'orders_details' => $ordersDetails,
            'total_revenue' => $totalRevenue,
            'booking_revenue' => array_sum(array_column($bookingRevenue, 'total_amount')),
            'order_revenue' => array_sum(array_column($orderRevenue, 'total_amount')),
        ];
    }

    private function getInventoryDeduction($staff, $startDate, $endDate, $branchIds = null)
    {
        // Get products deducted from orders handled by staff - WITH branch filter
        $productDeduction = [];
        $orders = Order::where(function ($query) use ($staff, $branchIds) {
            $query->where(function ($q) use ($staff) {
                $q
                    ->where('created_by', $staff->id)
                    ->where('created_by_type', 'staff')
                    ->orWhere('updated_by', $staff->id)
                    ->where('updated_by_type', 'staff')
                    ->orWhere('last_updated_by', $staff->id)
                    ->where('last_updated_by_type', 'staff');
            });

            // Apply branch filter if specified
            if ($branchIds) {
                $query->whereIn('branch_id', $branchIds);
            }
        })
            ->whereBetween('date_created', [$startDate, $endDate])
            ->with(['items.product', 'branch'])
            ->get();

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                if (!$item->product)
                    continue;

                $key = $order->branch_id . '-' . $item->product_id;

                if (!isset($productDeduction[$key])) {
                    $productDeduction[$key] = [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->product_name ?? 'N/A',
                        'product_type' => $item->product->product_type ?? 'N/A',
                        'branch_id' => $order->branch_id,
                        'branch_name' => $order->branch ? $order->branch->branch_name : 'N/A',
                        'total_quantity_deducted' => 0,
                        'total_value_deducted' => 0
                    ];
                }

                $productDeduction[$key]['total_quantity_deducted'] += $item->quantity;
                $productDeduction[$key]['total_value_deducted'] += ($item->quantity * $item->product->selling_price);
            }
        }

        $productDeduction = array_values($productDeduction);

        // Get ingredients deducted from products with ingredients - WITH branch filter
        $ingredientDeduction = [];

        return [
            'product_deduction' => $productDeduction,
            'ingredient_deduction' => $ingredientDeduction,
        ];
    }

    public function exportReport($staffUuid, Request $request)
    {
        $owner = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        // Get staff and verify they belong to owner's branch - using UUID
        $staff = StaffAccount::with(['branch'])
            ->where('uuid', $staffUuid)
            ->whereHas('branch', function ($query) use ($ownerId) {
                $query->where('owner_account_id', $ownerId);
            })
            ->firstOrFail();

        // Get selected branch from request
        $selectedBranch = $request->input('branch_filter', 'all');
        $selectedBranchName = 'All Branches';

        // Get branches for the filter
        $branches = Branch::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->orderBy('branch_name')
            ->get();

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

        $dateRange = $this->getDateRange($request);
        $startDate = $dateRange['start_date'];
        $endDate = $dateRange['end_date'];

        $stats = $this->getStaffStatistics($staff, $startDate, $endDate, $branchIds);

        // Generate PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('owner.reports.export_pdf', [
            'staff' => $staff,
            'stats' => $stats,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'owner' => $owner,
            'selectedBranchName' => $selectedBranchName,
            'selectedBranch' => $selectedBranch
        ]);

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'dpi' => 150,
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true
        ]);

        $filename = 'staff-report-' . $staff->uuid . '-' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    public function exportImage($staffUuid, Request $request)
    {
        return $this->exportReport($staffUuid, $request);
    }
}
