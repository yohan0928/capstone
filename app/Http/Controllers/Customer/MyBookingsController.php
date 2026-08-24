<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Branch;
use App\Models\CustomerAccount;
use App\Models\CustomerCheckin;
use App\Models\Feedback;
use App\Models\Order;
use App\Models\OwnerAccount;
use App\Models\Seat;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use App\Models\StaffAccount;
use App\Notifications\Customer\BookingListCustomerNotification;
use App\Notifications\Owner\BookingListNotification;
use App\Notifications\Staff\BookingListStaffNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class MyBookingsController extends Controller
{
    public function showMyBookings(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        $customerId = $customer->id;

        // Base query for customer's bookings only
        $baseQuery = Booking::where('customer_account_id', $customerId);

        // Booking query with filters - include all necessary relationships
        $query = $baseQuery
            ->clone()
            ->with([
                'customerAccount',
                'serviceCategory',
                'serviceName',
                'seat',
                'payment',
                'extensionPayment',
                'branch'
            ]);

        if ($request->filled('brn')) {
            $query->where('booking_ref_no', $request->brn);
        }

        // Filter by booking_type if provided (otherwise show all)
        if ($request->filled('booking_type') && $request->booking_type !== 'all') {
            $query->where('booking_type', $request->booking_type);
        }

        // Filter by specific booking_id if provided
        if ($request->filled('booking_id')) {
            $query->where('id', $request->booking_id);
        }

        // Search by booking reference only (customer can't search other customers)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where('booking_ref_no', 'LIKE', "%{$searchTerm}%");
        }

        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by service category
        if ($request->filled('service_category_id')) {
            $selectedCategory = ServiceCategory::find($request->service_category_id);
            if ($selectedCategory) {
                $categoryName = $selectedCategory->service_category;
                $matchingCategoryIds = ServiceCategory::where('service_category', $categoryName)
                    ->pluck('id');
                $query->whereIn('service_category_id', $matchingCategoryIds);
            } else {
                $query->where('service_category_id', $request->service_category_id);
            }
        }

        // Filter by service name
        if ($request->filled('service_name_id')) {
            $selectedService = ServiceName::find($request->service_name_id);
            if ($selectedService) {
                $serviceName = $selectedService->service_name;
                $matchingServiceIds = ServiceName::where('service_name', $serviceName)
                    ->pluck('id');
                $query->whereIn('service_name_id', $matchingServiceIds);
            } else {
                $query->where('service_name_id', $request->service_name_id);
            }
        }

        // Filter by seat
        if ($request->filled('seat_id')) {
            $query->where('seat_id', $request->seat_id);
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->whereHas('payment', fn($q) => $q->where('payment_status', $request->payment_status));
        }

        // Filter by booking status
        if ($request->filled('booking_status')) {
            $query->where('booking_status', $request->booking_status);
        }

        // Filter by date_start and date_end (range)
        if ($request->filled('date_start') && $request->filled('date_end')) {
            $query->whereBetween('date_start', [
                $request->date_start . ' 00:00:00',
                $request->date_end . ' 23:59:59'
            ]);
        } elseif ($request->filled('date_start')) {
            $query->whereDate('date_start', '>=', $request->date_start);
        } elseif ($request->filled('date_end')) {
            $query->whereDate('date_start', '<=', $request->date_end);
        }

        // Filter by booking_date (single date)
        if ($request->filled('booking_date')) {
            $query->whereDate('booking_date', $request->booking_date);
        }

        // Calculate Stats (based on filtered query) - Customer specific
        $statsQuery = $query->clone();

        $totalBookings = $statsQuery->count();
        $bookedBookings = $query->clone()->where('booking_status', 1)->count();
        $pendingBookings = $query->clone()->where('booking_status', 2)->count();
        $cancelledBookings = $query->clone()->where('booking_status', 0)->count();
        $noShowBookings = $query->clone()->where('booking_status', 3)->count();
        $completedBookings = $query->clone()->where('booking_status', 4)->count();

        // Booking type specific stats
        $onlineBookings = $query->clone()->where('booking_type', 1)->count();
        $walkinBookings = $query->clone()->where('booking_type', 0)->count();
        $pendingOnlineBookings = $query->clone()->where('booking_type', 1)->where('booking_status', 2)->count();

        $stats = [
            'total_bookings' => $totalBookings,
            'booked_bookings' => $bookedBookings,
            'pending_bookings' => $pendingBookings,
            'cancelled_bookings' => $cancelledBookings,
            'no_show_bookings' => $noShowBookings,
            'completed_bookings' => $completedBookings,
            'online_bookings' => $onlineBookings,
            'walkin_bookings' => $walkinBookings,
            'pending_online_bookings' => $pendingOnlineBookings,
        ];

        // Order by latest booking and paginate
        $bookings = $query->orderBy('booking_date', 'desc')->paginate(50);

        // Load checkin status and time usage for each booking
        $bookingIds = $bookings->pluck('id');
        $checkins = CustomerCheckin::whereIn('booking_id', $bookingIds)
            ->select('booking_id', 'checkin_status', 'time_used', 'extended_time_used', 'total_time_used')
            ->get()
            ->keyBy('booking_id');

        // Add checkin status and time usage to each booking
        foreach ($bookings as $booking) {
            $checkinData = $checkins[$booking->id] ?? null;
            $booking->checkin_status = $checkinData->checkin_status ?? 0;
            $booking->time_used = $checkinData->time_used ?? 0;
            $booking->extended_time_used = $checkinData->extended_time_used ?? 0;
            $booking->total_time_used = $checkinData->total_time_used ?? 0;

            // Load extension payment if exists
            $booking->load('extensionPayment');
        }

        // Get branches that the customer has booked with - only active branches
        $branches = Branch::where('active', 1)
            ->whereIn('id', function ($query) use ($customerId) {
                $query
                    ->select('branch_id')
                    ->from('bookings')
                    ->where('customer_account_id', $customerId);
            })
            ->orderBy('branch_name')
            ->get();

        // Get branch IDs for dynamic dropdowns
        $branchIds = $branches->pluck('id');

        // Dynamic dropdowns based on filters - Show unique values
        $categories = ServiceCategory::where('active', 1)
            ->whereIn('branch_id', $branchIds)
            // Removed branch_id filter to show all categories by text
            ->orderBy('service_category')
            ->get()
            ->unique('service_category') // Get unique by service_category field
            ->values(); // Reset array keys

        $servicesQuery = ServiceName::where('active', 1)
            ->whereIn('branch_id', $branchIds);

        // Filter services by category text if category is selected
        if ($request->service_category_id) {
            $selectedCategory = ServiceCategory::find($request->service_category_id);
            if ($selectedCategory) {
                $categoryName = $selectedCategory->service_category;
                $matchingCategoryIds = ServiceCategory::where('service_category', $categoryName)
                    ->pluck('id');
                $servicesQuery->whereIn('service_category_id', $matchingCategoryIds);
            } else {
                $servicesQuery->where('service_category_id', $request->service_category_id);
            }
        }

        $services = $servicesQuery->orderBy('service_name')
            ->get()
            ->unique('service_name') // Get unique by service_name field
            ->values(); // Reset array keys

        $seats = Seat::where('active', 1)
            ->whereIn('branch_id', $branchIds)
            ->when($request->branch_id, fn($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->service_category_id, fn($q) => $q->where('service_category_id', $request->service_category_id))
            ->orderBy('seat_no')
            ->get();

        // Handle AJAX Request
        if ($request->ajax()) {
            // Transform bookings for JSON response
            $bookingsData = $bookings->map(function ($booking) use ($checkins) {
                $checkinData = $checkins[$booking->id] ?? null;
                
                return [
                    'id' => $booking->id,
                    'uuid' => $booking->uuid,
                    'booking_ref_no' => $booking->booking_ref_no,
                    'booking_type' => $booking->booking_type,
                    'booking_status' => $booking->booking_status,
                    'booking_date' => $booking->booking_date,
                    'date_start' => $booking->date_start,
                    'date_end' => $booking->date_end,
                    'start_time' => $booking->start_time,
                    'end_time' => $booking->end_time,
                    'extended_start_time' => $booking->extended_start_time,
                    'extended_end_time' => $booking->extended_end_time,
                    'checkin_status' => $checkinData->checkin_status ?? 0,
                    'time_used' => $checkinData->time_used ?? 0,
                    'extended_time_used' => $checkinData->extended_time_used ?? 0,
                    'total_time_used' => $checkinData->total_time_used ?? 0,
                    'customer_account' => $booking->customerAccount ? [
                        'first_name' => $booking->customerAccount->first_name,
                        'last_name' => $booking->customerAccount->last_name,
                        'email' => $booking->customerAccount->email,
                    ] : null,
                    'branch' => $booking->branch ? [
                        'branch_name' => $booking->branch->branch_name,
                    ] : null,
                    'service_category' => $booking->serviceCategory ? [
                        'service_category' => $booking->serviceCategory->service_category,
                    ] : null,
                    'service_name' => $booking->serviceName ? [
                        'service_name' => $booking->serviceName->service_name,
                    ] : null,
                    'seat' => $booking->seat ? [
                        'room_no' => $booking->seat->room_no,
                        'seat_no' => $booking->seat->seat_no,
                    ] : null,
                    'payment' => $booking->payment ? [
                        'payment_status' => $booking->payment->payment_status,
                        'payment_method' => $booking->payment->payment_method,
                        'payment_date' => $booking->payment->payment_date,
                    ] : null,
                    'extension_payment' => $booking->extensionPayment ? [
                        'payment_status' => $booking->extensionPayment->payment_status,
                        'payment_method' => $booking->extensionPayment->payment_method,
                        'payment_date' => $booking->extensionPayment->payment_date,
                    ] : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $bookingsData->toArray(),
                'pagination' => [
                    'current_page' => $bookings->currentPage(),
                    'last_page' => $bookings->lastPage(),
                    'per_page' => $bookings->perPage(),
                    'total' => $bookings->total(),
                    'from' => $bookings->firstItem(),
                    'to' => $bookings->lastItem(),
                ],
                'stats' => $stats,
                'dropdowns' => [
                    'categories' => $categories,
                    'services' => $services,
                    'seats' => $seats,
                ]
            ]);
        }

        return view('customer.my_bookings.booking_list', compact(
            'bookings', 'branches', 'categories', 'services', 'seats', 'stats'
        ))->with('oldFilters', $request->all());
    }

    public function getFilterOptions(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        $customerId = $customer->id;

        // Get branches that the customer has booked with - only active branches
        $branchIds = Branch::where('active', 1)
            ->whereIn('id', function ($query) use ($customerId) {
                $query
                    ->select('branch_id')
                    ->from('bookings')
                    ->where('customer_account_id', $customerId);
            })
            ->pluck('id');

        // Get unique service categories (no duplicates)
        $categories = ServiceCategory::where('active', 1)
            ->whereIn('branch_id', $branchIds)
            // Removed branch_id filter to show all categories by text
            ->orderBy('service_category')
            ->get()
            ->unique('service_category')
            ->values();

        // Get unique service names (no duplicates)
        $servicesQuery = ServiceName::where('active', 1)
            ->whereIn('branch_id', $branchIds);

        // Filter services by category text if category is selected
        if ($request->service_category_id) {
            $selectedCategory = ServiceCategory::find($request->service_category_id);
            if ($selectedCategory) {
                $categoryName = $selectedCategory->service_category;
                $matchingCategoryIds = ServiceCategory::where('service_category', $categoryName)
                    ->pluck('id');
                $servicesQuery->whereIn('service_category_id', $matchingCategoryIds);
            } else {
                $servicesQuery->where('service_category_id', $request->service_category_id);
            }
        }

        $services = $servicesQuery->orderBy('service_name')
            ->get()
            ->unique('service_name')
            ->values();

        $seats = Seat::where('active', 1)
            ->whereIn('branch_id', $branchIds)
            ->when($request->branch_id, fn($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->service_category_id, fn($q) => $q->where('service_category_id', $request->service_category_id))
            ->orderBy('seat_no')
            ->get();

        return response()->json([
            'categories' => $categories,
            'services' => $services,
            'seats' => $seats,
        ]);
    }

    public function getBookingOrders(Booking $booking)
    {
        try {
            $customer = Auth::guard('customer')->user();
            
            // Security check - ensure the booking belongs to the logged-in customer
            if ($booking->customer_account_id !== $customer->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Get customer's unpaid orders for this booking
            $orders = Order::with(['items.product', 'payments'])
                ->where('booking_id', $booking->id)
                ->where('order_status', '!=', 2)  // Exclude cancelled orders
                ->get();

            // Filter orders to only include unpaid ones and calculate total
            $unpaidOrders = $orders->filter(function ($order) {
                // Check if order has payments and if any payment is unpaid
                if ($order->payments->isEmpty()) {
                    return true;  // No payments means unpaid
                }

                // Check if all payments are unpaid (status 0)
                return $order->payments->every(function ($payment) {
                    return $payment->order_payment_status == 0;
                });
            });

            // Calculate total unpaid amount
            $unpaidTotal = 0;

            /** @var \App\Models\Order $order */
            foreach ($unpaidOrders as $order) {
                foreach ($order->payments as $payment) {
                    if ($payment->order_payment_status == 0) {  // Unpaid
                        $unpaidTotal += $payment->total_amount;
                    }
                }
                // If no payments, use order total from items
                if ($order->payments->isEmpty()) {
                    $unpaidTotal += $order->items->sum('sub_total');
                }
            }

            // Get extended time data
            $checkin = CustomerCheckin::where('booking_id', $booking->id)->first();
            $timeUsed = $checkin->time_used ?? 0;
            $extendedTimeUsed = $checkin->extended_time_used ?? 0;
            $totalTimeUsed = $checkin->total_time_used ?? 0;

            // Transform orders for JSON response
            $ordersData = $unpaidOrders->map(function ($order) {
                $orderTotal = $order->payments->isEmpty()
                    ? $order->items->sum('sub_total')
                    : $order->payments->where('order_payment_status', 0)->sum('total_amount');

                return [
                    'id' => $order->id,
                    'order_ref_no' => $order->order_ref_no,
                    'order_date' => $order->order_date,
                    'order_status' => $order->order_status,
                    'items' => $order->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product' => [
                                'product_name' => $item->product->product_name ?? 'N/A'
                            ],
                            'selling_price' => $item->selling_price,
                            'quantity' => $item->quantity,
                            'sub_total' => $item->sub_total
                        ];
                    }),
                    'payments' => $order->payments->map(function ($payment) {
                        return [
                            'id' => $payment->id,
                            'total_amount' => $payment->total_amount,
                            'order_payment_status' => $payment->order_payment_status,
                            'payment_method' => $payment->payment_method
                        ];
                    }),
                    'order_total' => $orderTotal
                ];
            });

            return response()->json([
                'success' => true,
                'orders' => $ordersData,
                'unpaid_total' => $unpaidTotal,
                // Time Usage Data
                'time_used' => $timeUsed,
                'extended_time_used' => $extendedTimeUsed,
                'total_time_used' => $totalTimeUsed,
                'has_extension' => $extendedTimeUsed > 0,
                'booking_type' => $booking->booking_type,
                // Time fields for frontend calculations
                'end_time' => $booking->end_time,
                'date_end' => $booking->date_end,
                'extended_time' => $booking->extended_time,
                'extended_date' => $booking->extended_date,
                'extended_start_time' => $booking->extended_start_time,
                'extended_end_time' => $booking->extended_end_time,
                'extended_date_start' => $booking->extended_date_start,
                'extended_date_end' => $booking->extended_date_end,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch orders: ' . $e->getMessage()
            ], 500);
        }
    }

    public function showBookingDetails($booking_uuid)
    {
        $customer = Auth::guard('customer')->user();
        
        // Find the booking by UUID with all necessary relationships
        $booking = Booking::with([
            'customerAccount',
            'serviceCategory',
            'serviceName',
            'seat',
            'payment',  // Single payment relationship
            'branch',
        ])->where('uuid', $booking_uuid)->firstOrFail();

        // Security check - ensure the booking belongs to the logged-in customer
        if ($booking->customer_account_id !== $customer->id) {
            abort(403, 'Unauthorized access to this booking.');
        }

        // Get main payment (payment_category = 1) for this booking
        $mainPayment = BookingPayment::where('booking_id', $booking->id)
            ->where('payment_category', 1)
            ->first();

        // Get extension payment (payment_category = 0) with same customer_account_id
        $extensionPayment = BookingPayment::where('customer_account_id', $booking->customer_account_id)
            ->where('payment_category', 0)
            ->where('booking_id', $booking->id)  // Ensure it's for the same booking
            ->first();

        // Load checkin data for time usage
        $checkin = CustomerCheckin::where('booking_id', $booking->id)->first();
        $timeUsed = $checkin->time_used ?? 0;
        $extendedTimeUsed = $checkin->extended_time_used ?? 0;
        $totalTimeUsed = $checkin->total_time_used ?? 0;

        // Calculate durations
        $bookingDuration = $this->calculateBookingDurationInMinutes($booking->start_time, $booking->end_time);
        $extendedDuration = $this->calculateBookingDurationInMinutes($booking->extended_start_time, $booking->extended_end_time);
        $totalDuration = $bookingDuration + $extendedDuration;

        // Get ALL orders for this booking
        $allOrders = Order::where('booking_id', $booking->id)
            ->where('branch_id', $booking->branch_id)
            ->with(['payments', 'items.product'])
            ->orderBy('order_date', 'desc')
            ->get();

        // Filter PAID orders (order_payment_status = 1)
        $paidOrders = $allOrders->filter(function ($order) {
            return $order->payments->where('order_payment_status', 1)->count() > 0;
        });

        // Calculate total paid amount
        $totalPaidAmount = $paidOrders->sum(function ($order) {
            return $order->payments->where('order_payment_status', 1)->sum('total_amount');
        });

        // Filter UNPAID orders
        $unpaidOrders = $allOrders->filter(function ($order) {
            $hasUnpaidPayLater = $order
                ->payments
                ->where('payment_method', 3)
                ->where('order_payment_status', 0)
                ->count() > 0;

            $hasNoPayments = $order->payments->count() === 0;

            return $hasUnpaidPayLater || $hasNoPayments;
        });

        // Calculate total unpaid amount
        $totalUnpaidOrdersAmount = $unpaidOrders->sum(function ($order) {
            if ($order->payments->count() > 0) {
                return $order
                    ->payments
                    ->where('payment_method', 3)
                    ->where('order_payment_status', 0)
                    ->sum('total_amount');
            } else {
                return $order->items->sum('sub_total');
            }
        });

        $hasUnpaidOrders = $unpaidOrders->count() > 0;
        $hasPaidOrders = $paidOrders->count() > 0;

        // Also get orders with other payment statuses (if any)
        $otherOrders = $allOrders->filter(function ($order) use ($paidOrders, $unpaidOrders) {
            return !$paidOrders->contains('id', $order->id) && !$unpaidOrders->contains('id', $order->id);
        });

        // Calculate grand total for payments
        $mainTotal = $mainPayment->total_amount ?? 0;
        $extensionTotal = $extensionPayment->total_amount ?? 0;
        $grandTotal = $mainTotal + $extensionTotal;

        // Pre-calculate ALL formatted data needed in the view
        $formattedData = [
            // Booking Status
            'booking_status_text' => $this->getBookingStatusText($booking->booking_status),
            'booking_status_class' => $this->getBookingStatusClass($booking->booking_status),
            // Main Payment Information (payment_category = 1)
            'main_payment_status_text' => $this->getPaymentStatusText($mainPayment->payment_status ?? 2),
            'main_payment_status_class' => $this->getPaymentStatusClass($mainPayment->payment_status ?? 2),
            'main_payment_method_text' => $this->getPaymentMethodText($mainPayment->payment_method ?? 3),
            'main_total_amount_formatted' => $this->formatCurrency($mainPayment->total_amount ?? 0),
            'main_amount_paid_formatted' => $this->formatCurrency($mainPayment->amount_paid ?? 0),
            'main_change_formatted' => $this->formatCurrency($mainPayment->change ?? 0),
            'main_payment_date_formatted' => $this->formatDateTime($mainPayment->payment_date ?? ''),
            // Extension Payment Information (payment_category = 0 with same customer_account_id)
            'extension_payment_status_text' => $this->getPaymentStatusText($extensionPayment->payment_status ?? 2),
            'extension_payment_status_class' => $this->getPaymentStatusClass($extensionPayment->payment_status ?? 2),
            'extension_payment_method_text' => $this->getPaymentMethodText($extensionPayment->payment_method ?? 3),
            'extension_total_amount_formatted' => $this->formatCurrency($extensionPayment->total_amount ?? 0),
            'extension_amount_paid_formatted' => $this->formatCurrency($extensionPayment->amount_paid ?? 0),
            'extension_change_formatted' => $this->formatCurrency($extensionPayment->change ?? 0),
            'extension_payment_date_formatted' => $this->formatDateTime($extensionPayment->payment_date ?? ''),
            // Payment Summary
            'grand_total_formatted' => $this->formatCurrency($grandTotal),
            // Dates and Times
            'booking_date_formatted' => $this->formatDate($booking->booking_date),
            'date_start_formatted' => $this->formatDate($booking->date_start),
            'date_end_formatted' => $this->formatDate($booking->date_end),
            'start_time_formatted' => $this->formatTime($booking->start_time),
            'end_time_formatted' => $this->formatTime($booking->end_time),
            // Extended Dates and Times
            'extended_date_start_formatted' => $this->formatDate($booking->extended_date_start),
            'extended_date_end_formatted' => $this->formatDate($booking->extended_date_end),
            'extended_start_time_formatted' => $this->formatTime($booking->extended_start_time),
            'extended_end_time_formatted' => $this->formatTime($booking->extended_end_time),
            // Duration Information
            'duration_formatted' => $this->formatDuration($bookingDuration),
            'extended_duration_formatted' => $this->formatDuration($extendedDuration),
            'total_duration_formatted' => $this->formatDuration($totalDuration),
            // Time Usage - Direct from database
            'time_used_formatted' => $this->formatDuration($timeUsed),
            'extended_time_used_formatted' => $this->formatDuration($extendedTimeUsed),
            'total_time_used_formatted' => $this->formatDuration($totalTimeUsed),
            'time_used_minutes' => $timeUsed,
            'extended_time_used_minutes' => $extendedTimeUsed,
            'total_time_used_minutes' => $totalTimeUsed,
            // Room/Seat
            'room_seat_text' => $this->getRoomSeatText($booking),
            // Currency
            'service_price_formatted' => $this->formatCurrency($booking->serviceName->price ?? 0),
            // Extension pricing if exists
            'base_price_formatted' => $booking->extension ? $this->formatCurrency($booking->extension->base_price) : 'N/A',
            'extension_price_formatted' => $booking->extension ? $this->formatCurrency($booking->extension->extension_price) : 'N/A',
            'extension_total_formatted' => $booking->extension ? $this->formatCurrency($booking->extension->total_price) : 'N/A',
            // Orders Data
            'unpaid_orders_count' => $unpaidOrders->count(),
            'total_unpaid_orders_amount_formatted' => $this->formatCurrency($totalUnpaidOrdersAmount),
            'has_unpaid_orders' => $hasUnpaidOrders,
            'paid_orders_count' => $paidOrders->count(),
            'total_paid_amount_formatted' => $this->formatCurrency($totalPaidAmount),
            'has_paid_orders' => $hasPaidOrders,
            'total_orders_count' => $allOrders->count(),
            'other_orders_count' => $otherOrders->count(),
        ];

        return view('customer.my_bookings.booking_details', compact(
            'booking',
            'mainPayment',
            'extensionPayment',
            'timeUsed',
            'extendedTimeUsed',
            'totalTimeUsed',
            'formattedData',
            'allOrders',
            'paidOrders',
            'unpaidOrders',
            'otherOrders',
            'totalUnpaidOrdersAmount',
            'totalPaidAmount',
            'hasUnpaidOrders',
            'hasPaidOrders',
            'grandTotal'
        ));
    }

    // Make sure this helper method exists in your controller
    protected function calculateBookingDurationInMinutes($startTime, $endTime)
    {
        if (!$startTime || !$endTime)
            return 0;

        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        return $start->diffInMinutes($end);
    }

    public function updateNote(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'note' => 'required|string|max:1000',
        ]);

        $booking = Booking::with('payment', 'serviceName')->findOrFail($request->booking_id);
        $payment = $booking->payment;

        if (!$payment) {
            $payment = new BookingPayment();
            $payment->booking_id = $booking->id;
            $payment->branch_id = $booking->branch_id;
            $payment->customer_account_id = $booking->customer_account_id;
            $payment->total_amount = $booking->extension ? $booking->extension->total_price : ($booking->serviceName->price ?? 0);
            $payment->payment_status = 3;
            $payment->payment_method = 3;
            $payment->active = 1;

            // Initialize empty notes array for new payment
            $payment->notes = [];
        }

        // Get existing notes and ensure it's a proper array
        $existingNotes = $payment->notes ?? [];

        // Fix: Properly decode if it's a JSON string, or handle nested strings
        if (is_string($existingNotes)) {
            // Try to decode JSON first
            $decoded = json_decode($existingNotes, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $existingNotes = $decoded;
            } else {
                // If it's a malformed string, start fresh
                $existingNotes = [];
            }
        }

        // Ensure we have a clean array (remove any nested string issues)
        if (!is_array($existingNotes)) {
            $existingNotes = [];
        }

        // Add new note with timestamp
        $newNote = [
            'content' => $request->note,
            'added_by_type' => 'Customer',
            'added_at' => now()->toDateTimeString(),
        ];

        // Add new note to the beginning of the array (most recent first)
        array_unshift($existingNotes, $newNote);

        // Keep only the last 50 notes to prevent database bloat
        if (count($existingNotes) > 50) {
            $existingNotes = array_slice($existingNotes, 0, 50);
        }

        // Store as proper JSON array
        $payment->notes = $existingNotes;
        $payment->save();
        $booking->save();

        // Send notification for branch update
        $actor = Auth::guard('customer')->user();

        // Get the specific branch for this booking
        $bookingBranch = Branch::find($booking->branch_id);

        // Get related models for notification
        $customer = CustomerAccount::find($booking->customer_account_id);

        // Get owner to notify
        $owner = OwnerAccount::find($bookingBranch->owner_account_id);
        $owners = collect([$owner])->filter();

        // Send notification
        Notification::send($owners, new BookingListNotification(
            $booking,
            $bookingBranch,
            $customer,
            $actor,
            'notes'
        ));

        $staffMembers = StaffAccount::where('branch_id', $booking->branch_id)
            ->where('active', 1)
            ->get();

        Notification::send($staffMembers, new BookingListStaffNotification(
            $booking,
            $bookingBranch,
            $customer,
            $actor,
            'notes'
        ));

        Notification::send($customer, new BookingListCustomerNotification(
            $booking,
            $bookingBranch,
            $customer,
            $actor,
            'notes'
        ));

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Note added successfully.',
                'notes' => $existingNotes
            ]);
        }

        return redirect()->back()->with('success', 'Note added successfully!');
    }

    public function getNotes(Booking $booking)
    {
        try {
            $customer = Auth::guard('customer')->user();

            // Security check
            if ($booking->customer_account_id !== $customer->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $payment = $booking->payment;
            $notes = [];

            if ($payment && $payment->notes) {
                $notesData = $payment->notes;

                // Handle both array and JSON string
                if (is_string($notesData)) {
                    $decoded = json_decode($notesData, true);
                    $notes = (json_last_error() === JSON_ERROR_NONE) ? $decoded : [];
                } else {
                    $notes = $notesData;
                }

                // Ensure we have a clean array
                if (!is_array($notes)) {
                    $notes = [];
                }
            }

            return response()->json([
                'success' => true,
                'notes' => $notes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch notes: ' . $e->getMessage()
            ], 500);
        }
    }

    public function markNoShow(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);

        $booking = Booking::findOrFail($request->booking_id);
        
        // Security check
        $customer = Auth::guard('customer')->user();
        if ($booking->customer_account_id !== $customer->id) {
            return redirect()->back()->with('error', 'You are not authorized to update this booking.');
        }

        // Check if booking status is Booked (1) only
        if ($booking->booking_status == 1) {
            $booking->booking_status = 3;  // No-show
            $booking->save();

            // Send notification for branch update
            $actor = Auth::guard('customer')->user();

            // Get the specific branch for this booking
            $bookingBranch = Branch::find($booking->branch_id);

            // Get related models for notification
            $customer = CustomerAccount::find($booking->customer_account_id);

            // Get owner to notify
            $owner = OwnerAccount::find($bookingBranch->owner_account_id);
            $owners = collect([$owner])->filter();

            // Send notification
            Notification::send($owners, new BookingListNotification(
                $booking,
                $bookingBranch,
                $customer,
                $actor,
                'no_show'
            ));

            $staffMembers = StaffAccount::where('branch_id', $booking->branch_id)
                ->where('active', 1)
                ->get();

            Notification::send($staffMembers, new BookingListStaffNotification(
                $booking,
                $bookingBranch,
                $customer,
                $actor,
                'no_show'
            ));

            Notification::send($customer, new BookingListCustomerNotification(
                $booking,
                $bookingBranch,
                $customer,
                $actor,
                'no_show'
            ));
        }
        
        return redirect()->back()->with('success', 'Booking marked as No Show.');
    }

    // ============================================
    // RE-SCHEDULE FUNCTIONALITY
    // ============================================

    /**
     * Show re-schedule form
     */
    public function showRescheduleForm($booking_uuid)
    {
        $customer = Auth::guard('customer')->user();

        // Get the booking with all necessary relationships
        $booking = Booking::with([
            'customerAccount',
            'serviceCategory',
            'serviceName',
            'seat',
            'payment',
            'branch'
        ])
            ->where('uuid', $booking_uuid)
            ->where('customer_account_id', $customer->id)
            ->where('booking_status', 3)  // Only allow re-schedule for no-show status
            ->firstOrFail();

        // Check if booking can be re-scheduled (within allowed timeframe)
        $bookingDate = Carbon::parse($booking->date_start);
        $now = Carbon::now();

        // Allow re-schedule only if booking is at least 24 hours or above
        if ($bookingDate->diffInMinutes($now) < 1) {
            return redirect()
                ->route('sub_three.my_bookings.showMyBookings')
                ->with('error', 'You can only re-schedule bookings at least 24 hours or above in advance.');
        }

        // Get branch details
        $branch = Branch::findOrFail($booking->branch_id);

        // Get service details
        $service = ServiceName::findOrFail($booking->service_name_id);

        // Get the seat
        $seat = Seat::findOrFail($booking->seat_id);

        // Get all available seats for this service category (excluding current seat)
        $availableSeats = Seat::where('branch_id', $branch->id)
            ->where('service_category_id', $booking->service_category_id)
            ->where('active', 1)
            ->where('seat_status', 1)
            ->where('id', '!=', $seat->id)  // Exclude current seat
            ->get()
            ->map(function ($seatItem) {
                // Determine the display label
                if ($seatItem->room_no !== null) {
                    $seatItem->display_label = 'Room ' . $seatItem->room_no;
                    $seatItem->display_number = $seatItem->room_no;
                } else {
                    $seatItem->display_label = 'Seat ' . $seatItem->seat_no;
                    $seatItem->display_number = $seatItem->seat_no;
                }
                return $seatItem;
            })
            ->sortBy('display_number');

        // Format branch hours for display
        $openTimeFormatted = $this->formatTimeForDisplay($branch->open_time);
        $closeTimeFormatted = $this->formatTimeForDisplay($branch->close_time);

        // Get service packages for extended time calculation
        $servicePackages = ServiceName::where('branch_id', $branch->id)
            ->where('service_category_id', $booking->service_category_id)
            ->where('active', 1)
            ->where('service_name_status', 1)
            ->orderBy('price')
            ->get()
            ->map(function ($serviceItem) {
                return [
                    'id' => $serviceItem->id,
                    'service_name' => $serviceItem->service_name,
                    'price' => (float) $serviceItem->price,
                    'time_duration' => $serviceItem->time_duration,
                    'duration_minutes' => $this->parseDuration($serviceItem->time_duration)
                ];
            })
            ->toArray();

        // Get hourly rate for extended time calculation
        $oneHourService = ServiceName::where('branch_id', $branch->id)
            ->where('service_category_id', $booking->service_category_id)
            ->where('active', 1)
            ->where('service_name_status', 1)
            ->where(function ($query) {
                $query
                    ->where('time_duration', 'like', '%1 hour%')
                    ->orWhere('time_duration', 'like', '%1 Hour%')
                    ->orWhere('time_duration', 'like', '%1 hr%')
                    ->orWhere('time_duration', 'like', '%60 minute%');
            })
            ->first();

        $hourlyRate = $oneHourService ? $oneHourService->price : 0;

        // Calculate duration in minutes
        $durationMinutes = $this->parseDuration($service->time_duration);

        // Generate time slots for the next 60 days - USING THE SAME LOGIC AS BOOKING FORM
        $timeSlots = $this->generateTimeSlotsForNextDays(60, 15, $branch->open_time, $branch->close_time, $branch->open_days);

        return view('customer.my_bookings.reschedule_form', compact(
            'booking',
            'branch',
            'service',
            'seat',
            'availableSeats',
            'openTimeFormatted',
            'closeTimeFormatted',
            'servicePackages',
            'hourlyRate',
            'timeSlots',
            'durationMinutes'
        ));
    }

    /**
     * Process re-schedule preview (for extended time)
     */
    public function reschedulePreview(Request $request, $booking_uuid)
    {
        $customer = Auth::guard('customer')->user();

        $booking = Booking::with(['serviceName', 'branch'])
            ->where('uuid', $booking_uuid)
            ->where('customer_account_id', $customer->id)
            ->where('booking_status', 3)  // Only no-show bookings
            ->firstOrFail();

        // Validate request
        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
            'booking_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'additional_hours' => 'integer|min:0',
            'additional_minutes' => 'integer|min:0|max:45',
            'additional_price' => 'numeric|min:0',
            'total_price' => 'numeric|min:0',
            'main_duration' => 'integer|min:1',
            'total_duration' => 'integer|min:1',
            'extended_duration_total' => 'integer|min:0',
            'extended_start_time' => 'nullable|date_format:H:i',
            'extended_end_time' => 'nullable|date_format:H:i',
            'extended_start_date' => 'nullable|date',
            'extended_end_date' => 'nullable|date',
        ]);

        // Check for booking conflicts (same as booking form)
        $conflictCheck = $this->checkBookingConflict(
            $booking->branch_id,
            $booking->service_category_id,
            $booking->service_name_id,
            $booking->seat_id,
            $validated['date_from'],
            $validated['date_to'],
            $validated['booking_time'],
            $validated['end_time'],
            $booking->id  // Exclude current booking from conflict check
        );

        if ($conflictCheck['has_conflict']) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'The selected time slot conflicts with an existing booking. Please choose a different time.');
        }

        // Calculate extended time
        $additionalHours = $request->additional_hours ?? 0;
        $additionalMinutes = $request->additional_minutes ?? 0;
        $extendedDuration = ($additionalHours * 60) + $additionalMinutes;

        // Store in session for payment processing
        $rescheduleData = [
            'booking_id' => $booking->id,
            'booking_uuid' => $booking_uuid,
            'date_from' => $validated['date_from'],
            'date_to' => $validated['date_to'],
            'booking_time' => $validated['booking_time'],
            'end_time' => $validated['end_time'],
            'additional_hours' => $additionalHours,
            'additional_minutes' => $additionalMinutes,
            'extended_duration' => $extendedDuration,
            'additional_price' => $validated['additional_price'] ?? 0,
            'total_price' => $validated['total_price'] ?? $booking->serviceName->price,
            'main_duration' => $validated['main_duration'] ?? 0,
            'total_duration' => $validated['total_duration'] ?? 0,
            'extended_duration_total' => $validated['extended_duration_total'] ?? 0,
            'extended_start_time' => $validated['extended_start_time'] ?? null,
            'extended_end_time' => $validated['extended_end_time'] ?? null,
            'extended_start_date' => $validated['extended_start_date'] ?? null,
            'extended_end_date' => $validated['extended_end_date'] ?? null,
            'original_booking_data' => [
                'date_from' => $booking->date_start,
                'date_to' => $booking->date_end,
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
                'extended_start_time' => $booking->extended_start_time,
                'extended_end_time' => $booking->extended_end_time,
                'extended_date_start' => $booking->extended_date_start,
                'extended_date_end' => $booking->extended_date_end,
            ]
        ];

        session(['reschedule_data' => $rescheduleData]);

        // If there's extended time, redirect to payment preview
        if ($extendedDuration > 0) {
            return redirect()->route('sub_three.my_bookings.reschedule.payment', $booking_uuid);
        }

        // If no extended time, update booking directly
        return $this->processReschedule($booking, $rescheduleData);
    }

    /**
     * Show payment preview for extended time
     */
    public function showReschedulePayment($booking_uuid)
    {
        $rescheduleData = session('reschedule_data');

        if (!$rescheduleData || $rescheduleData['booking_uuid'] !== $booking_uuid) {
            return redirect()
                ->route('sub_three.my_bookings.showMyBookings')
                ->with('error', 'Invalid re-schedule session.');
        }

        $customer = Auth::guard('customer')->user();

        $booking = Booking::with(['serviceName', 'branch'])
            ->where('uuid', $booking_uuid)
            ->where('customer_account_id', $customer->id)
            ->where('booking_status', 3)  // Only no-show bookings
            ->firstOrFail();

        // Get the branch's owner to get GCash QR codes
        $branch = $booking->branch;

        // Get GCash QR codes from the owner of this branch
        $owner = OwnerAccount::where('id', $branch->owner_account_id)->first();

        // Initialize array for QR codes
        $staffGcashQrCode = [];

        // Check if owner has GCash QR code images - using gcash_qr_code_img column
        if ($owner && !empty($owner->gcash_qr_code_img)) {
            $qrCodeData = $owner->gcash_qr_code_img;

            // Handle different storage formats
            // Option 1: JSON array string
            if (is_string($qrCodeData) && strpos($qrCodeData, '[') === 0) {
                $decoded = json_decode($qrCodeData, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $staffGcashQrCode = $decoded;
                }
            }
            // Option 2: Single string path
            else if (is_string($qrCodeData)) {
                $staffGcashQrCode = [$qrCodeData];
            }
            // Option 3: Already an array
            else if (is_array($qrCodeData)) {
                $staffGcashQrCode = $qrCodeData;
            }
        }

        // Check if booking has extended time
        $hasExtendedTime = $rescheduleData['extended_duration'] > 0;

        return view('customer.my_bookings.reschedule_payment', compact(
            'booking',
            'rescheduleData',
            'staffGcashQrCode',
            'hasExtendedTime'
        ));
    }

    /**
     * Process re-schedule with payment
     */
    public function processReschedulePayment(Request $request, $booking_uuid)
    {
        $rescheduleData = session('reschedule_data');

        if (!$rescheduleData || $rescheduleData['booking_uuid'] !== $booking_uuid) {
            return redirect()
                ->route('sub_three.my_bookings.showMyBookings')
                ->with('error', 'Invalid re-schedule session.');
        }

        $customer = Auth::guard('customer')->user();

        $booking = Booking::where('uuid', $booking_uuid)
            ->where('customer_account_id', $customer->id)
            ->where('booking_status', 3)  // Only no-show bookings
            ->firstOrFail();

        // Validate payment method
        $validated = $request->validate([
            'payment_method' => 'required|in:1,3',  // 1 = GCash, 3 = Pay Later
            'payment_status' => 'required|in:1,2',  // 1 = Paid, 2 = Unpaid
            'booking_details' => 'required|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Additional validation for GCash payments
        if ($validated['payment_method'] == 1) {
            $request->validate([
                'gcash_ref_no' => 'required|string|max:50',
                'gcash_receipt_img' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
            
            // Ensure payment status is 1 for GCash
            $validated['payment_status'] = 1;
        }

        // For Pay Later, ensure payment status is 2
        if ($validated['payment_method'] == 3) {
            $validated['payment_status'] = 2;
        }

        DB::beginTransaction();

        try {
            // Decode booking details
            $decoded = base64_decode($validated['booking_details']);
            if ($decoded === false) {
                throw new \Exception('Invalid booking data format.');
            }

            $bookingDetails = json_decode($decoded, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid booking data.');
            }

            // Process payment if there's extended time
            if ($rescheduleData['extended_duration'] > 0) {
                $payment = $this->createReschedulePayment($booking, $rescheduleData['additional_price'], $request, $validated);
            }

            // Then update booking
            $result = $this->processReschedule($booking, $rescheduleData);

            DB::commit();

            // Clear session data
            session()->forget('reschedule_data');

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Reschedule payment error: ' . $e->getMessage(), [
                'exception' => $e,
                'customer_id' => $customer->id,
                'booking_uuid' => $booking_uuid
            ]);
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to process payment and re-schedule: ' . $e->getMessage());
        }
    }
    
    /**
     * Create payment for re-schedule - UPDATED FOR BOTH GCASH AND PAY LATER
     */
    private function createReschedulePayment($booking, $amount, $request, $paymentData)
    {
        // Handle file upload for GCash receipt (only for GCash payments)
        $receiptPath = null;
        if ($paymentData['payment_method'] == 1 && $request->hasFile('gcash_receipt_img')) {
            $file = $request->file('gcash_receipt_img');
            $filename = 'gcash_receipt_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $receiptPath = $file->storeAs('public/gcash_receipts', $filename);
        }

        $payment = new BookingPayment();
        $payment->uuid = Str::uuid();
        $payment->booking_id = $booking->id;
        $payment->customer_account_id = $booking->customer_account_id;
        $payment->branch_id = $booking->branch_id;
        $payment->payment_category = 0;  // Extended time payment

        // Set amounts correctly
        $payment->total_amount = $amount;
        
        // For GCash payments, amount_paid equals total_amount
        // For Pay Later, amount_paid is 0
        if ($paymentData['payment_method'] == 1) {
            $payment->amount_paid = $amount;  // Full amount paid for GCash
            $payment->change = 0;
        } else {
            $payment->amount_paid = 0;  // No payment made yet for Pay Later
            $payment->change = 0;
        }

        // Set payment details based on payment method
        $payment->payment_status = $paymentData['payment_status'];  // 1 for Paid, 2 for Unpaid
        $payment->payment_method = $paymentData['payment_method'];  // 1 for GCash, 3 for Pay Later
        
        // Only set GCash details for GCash payments
        if ($paymentData['payment_method'] == 1) {
            $payment->gcash_ref_no = $request->input('gcash_ref_no');
            $payment->gcash_receipt_img = $receiptPath;
        }
        
        $payment->payment_date = now();

        // Handle notes
        if ($request->filled('notes')) {
            $notes = [[
                'content' => trim($request->notes),
                'added_by_type' => 'Customer',
                'added_at' => now()->toDateTimeString(),
            ]];
            $payment->notes = $notes;
        }

        // Add payment type note for Pay Later
        if ($paymentData['payment_method'] == 3) {
            $existingNotes = $payment->notes ?? [];
            array_unshift($existingNotes, [
                'content' => 'Payment method: Pay Later - To be paid at branch',
                'added_by_type' => 'System',
                'added_at' => now()->toDateTimeString(),
            ]);
            $payment->notes = $existingNotes;
        }

        $payment->active = 1;
        $payment->save();

        return $payment;
    }

    /**
     * Process the actual re-schedule
     */
    private function processReschedule($booking, $data)
    {
        DB::beginTransaction();

        try {
            // Update booking dates and times AND change status from no-show to booked
            $booking->update([
                'date_start' => $data['date_from'],
                'date_end' => $data['date_to'],
                'start_time' => $data['booking_time'],
                'end_time' => $data['end_time'],
                'booking_status' => 1,  // Change from no-show (3) to booked (1)
                'updated_at' => now(),
            ]);

            // If there's extended time, update those fields too
            if ($data['extended_duration'] > 0) {
                $booking->update([
                    'extended_start_time' => $data['extended_start_time'],
                    'extended_end_time' => $data['extended_end_time'],
                    'extended_date_start' => $data['extended_start_date'],
                    'extended_date_end' => $data['extended_end_date'],
                ]);
            } else {
                // Clear extended time fields if no extended time
                $booking->update([
                    'extended_start_time' => null,
                    'extended_end_time' => null,
                    'extended_date_start' => null,
                    'extended_date_end' => null,
                ]);
            }

            // Create booking history log
            $this->createRescheduleHistory($booking, $data['original_booking_data']);

            // Send notifications
            $this->sendRescheduleNotifications($booking);

            DB::commit();

            // Clear session data
            session()->forget('reschedule_data');

            // Different success messages based on extended time
            if ($data['extended_duration'] > 0) {
                return redirect()
                    ->route('sub_three.my_bookings.showMyBookings')
                    ->with('success', 'Booking re-scheduled successfully with extended time! Status changed from No-show to Booked.');
            } else {
                return redirect()
                    ->route('sub_three.my_bookings.showMyBookings')
                    ->with('success', 'Booking re-scheduled successfully! Status changed from No-show to Booked.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Reschedule processing error: ' . $e->getMessage(), [
                'exception' => $e,
                'booking_id' => $booking->id
            ]);
            
            return redirect()
                ->back()
                ->with('error', 'Failed to re-schedule booking: ' . $e->getMessage());
        }
    }

    /**
     * Check for booking conflicts (same as booking form)
     */
    private function checkBookingConflict($branchId, $categoryId, $serviceId, $seatId, $dateFrom, $dateTo, $startTime, $endTime, $excludeBookingId = null)
    {
        $query = Booking::where('branch_id', $branchId)
            ->where('service_category_id', $categoryId)
            ->where('service_name_id', $serviceId)
            ->where('seat_id', $seatId)
            ->whereIn('booking_status', [1, 2, 4])  // Booked, Pending, Completed (exclude no-show)
            ->where(function ($query) use ($dateFrom, $dateTo, $startTime, $endTime) {
                // Check for overlapping date ranges
                $query->where(function ($q) use ($dateFrom, $dateTo) {
                    $q
                        ->whereBetween('date_start', [$dateFrom, $dateTo])
                        ->orWhereBetween('date_end', [$dateFrom, $dateTo])
                        ->orWhere(function ($q2) use ($dateFrom, $dateTo) {
                            $q2
                                ->where('date_start', '<=', $dateFrom)
                                ->where('date_end', '>=', $dateTo);
                        });
                });
            });

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        $conflictingBookings = $query->get();

        $hasConflict = false;
        $conflictDetails = [];

        foreach ($conflictingBookings as $existingBooking) {
            // Convert times to Carbon for comparison
            $existingStart = Carbon::parse($existingBooking->date_start . ' ' . $existingBooking->start_time);
            $existingEnd = Carbon::parse($existingBooking->date_end . ' ' . $existingBooking->end_time);
            $newStart = Carbon::parse($dateFrom . ' ' . $startTime);
            $newEnd = Carbon::parse($dateTo . ' ' . $endTime);

            // Check for time overlap
            if ($newStart->lt($existingEnd) && $newEnd->gt($existingStart)) {
                $hasConflict = true;
                $conflictDetails[] = [
                    'booking_ref' => $existingBooking->booking_ref_no,
                    'date' => $existingBooking->date_start,
                    'start_time' => Carbon::parse($existingBooking->start_time)->format('g:i A'),
                    'end_time' => Carbon::parse($existingBooking->end_time)->format('g:i A'),
                ];
            }

            // Also check extended time if it exists
            if ($existingBooking->extended_start_time && $existingBooking->extended_end_time) {
                $existingExtendedStart = Carbon::parse($existingBooking->extended_date_start . ' ' . $existingBooking->extended_start_time);
                $existingExtendedEnd = Carbon::parse($existingBooking->extended_date_end . ' ' . $existingBooking->extended_end_time);

                if ($newStart->lt($existingExtendedEnd) && $newEnd->gt($existingExtendedStart)) {
                    $hasConflict = true;
                    $conflictDetails[] = [
                        'booking_ref' => $existingBooking->booking_ref_no,
                        'date' => $existingBooking->extended_date_start,
                        'start_time' => Carbon::parse($existingBooking->extended_start_time)->format('g:i A'),
                        'end_time' => Carbon::parse($existingBooking->extended_end_time)->format('g:i A'),
                        'type' => 'extended'
                    ];
                }
            }
        }

        return [
            'has_conflict' => $hasConflict,
            'conflicts' => $conflictDetails
        ];
    }

    /**
     * Create additional payment for extended time - UPDATED (ONLY GCash)
     */
    private function createAdditionalPayment($booking, $amount, $request)
    {
        // Handle file upload for GCash receipt
        $receiptPath = null;
        if ($request->hasFile('gcash_receipt_img')) {
            $file = $request->file('gcash_receipt_img');
            $filename = 'gcash_receipt_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $receiptPath = $file->storeAs('public/gcash_receipts', $filename);
        }

        $payment = new BookingPayment();
        $payment->uuid = Str::uuid();
        $payment->booking_id = $booking->id;
        $payment->customer_account_id = $booking->customer_account_id;
        $payment->branch_id = $booking->branch_id;
        $payment->payment_category = 0;  // Extended time payment

        // Set amounts correctly - amount_paid should be the same as total_amount for GCash payment
        $payment->total_amount = $amount;
        $payment->amount_paid = $amount;  // Customer is paying the full amount with GCash
        $payment->change = 0;

        // Set GCash payment details
        $payment->payment_status = 1;  // PAID (GCash is always paid)
        $payment->payment_method = 1;  // GCash
        $payment->gcash_ref_no = $request->input('gcash_ref_no');
        $payment->gcash_receipt_img = $receiptPath;
        $payment->payment_date = now();

        // Handle notes
        if ($request->filled('notes')) {
            $notes = [[
                'content' => trim($request->notes),
                'added_by_type' => 'Customer',
                'added_at' => now()->toDateTimeString(),
            ]];
            $payment->notes = $notes;
        }

        $payment->active = 1;
        $payment->save();

        return $payment;
    }

    /**
     * Create re-schedule history log
     */
    private function createRescheduleHistory($booking, $originalData)
    {
        // You can create a booking_history table or use activity logs
        // This is a simplified version - create if you have the table
        if (DB::getSchemaBuilder()->hasTable('booking_histories')) {
            $history = [
                'booking_id' => $booking->id,
                'action' => 'reschedule',
                'original_data' => json_encode($originalData),
                'new_data' => json_encode([
                    'date_start' => $booking->date_start,
                    'date_end' => $booking->date_end,
                    'start_time' => $booking->start_time,
                    'end_time' => $booking->end_time,
                    'booking_status' => $booking->booking_status,
                    'extended_start_time' => $booking->extended_start_time,
                    'extended_end_time' => $booking->extended_end_time,
                    'extended_date_start' => $booking->extended_date_start,
                    'extended_date_end' => $booking->extended_date_end,
                ]),
                'changed_by' => 'customer',
                'changed_by_id' => Auth::guard('customer')->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('booking_histories')->insert($history);
        }
    }

    /**
     * Send notifications for re-schedule
     */
    private function sendRescheduleNotifications($booking)
    {
        $actor = Auth::guard('customer')->user();
        $bookingBranch = Branch::find($booking->branch_id);
        $customerAccount = CustomerAccount::find($booking->customer_account_id);
        $owner = OwnerAccount::find($bookingBranch->owner_account_id);
        $staffs = StaffAccount::where('branch_id', $booking->branch_id)->get();

        // Send notifications (using your existing notification classes)
        Notification::send(collect([$owner]), new BookingListNotification(
            $booking,
            $bookingBranch,
            $customerAccount,
            $actor,
            'reschedule'
        ));

        Notification::send($staffs, new BookingListStaffNotification(
            $booking,
            $bookingBranch,
            $customerAccount,
            $actor,
            'reschedule'
        ));

        Notification::send($customerAccount, new BookingListCustomerNotification(
            $booking,
            $bookingBranch,
            $customerAccount,
            $actor,
            'reschedule'
        ));
    }

    /**
     * Helper function to calculate extended end time
     */
    private function calculateExtendedEndTime($startTime, $durationMinutes)
    {
        $start = Carbon::parse($startTime);
        $end = $start->copy()->addMinutes($durationMinutes);
        return $end->format('H:i:s');
    }

    /**
     * Helper function to calculate extended end date
     */
    private function calculateExtendedEndDate($startDate, $durationMinutes)
    {
        $start = Carbon::parse($startDate . ' 00:00:00');
        $end = $start->copy()->addMinutes($durationMinutes);
        return $end->format('Y-m-d');
    }

    /**
     * Calculate hourly rate from service price and duration
     */
    private function calculateHourlyRate($price, $durationString)
    {
        $duration = $this->parseDuration($durationString);
        $hours = $duration / 60;
        return $price / $hours;
    }

    /**
     * Calculate extended time price
     */
    private function calculateExtendedTimePrice($hourlyRate, $durationMinutes)
    {
        $hours = floor($durationMinutes / 60);
        $remainingMinutes = $durationMinutes % 60;
        $fifteenMinuteBlocks = ceil($remainingMinutes / 15);

        $hourlyCost = $hours * $hourlyRate;
        $minuteCost = $fifteenMinuteBlocks * ($hourlyRate / 4);

        return $hourlyCost + $minuteCost;
    }

    /**
     * Parse duration string to minutes (same as in BookingFormController)
     */
    private function parseDuration($duration)
    {
        if (str_contains($duration, 'hour')) {
            $hours = (int) $duration;
            return $hours * 60;
        } elseif (str_contains($duration, 'minute')) {
            return (int) $duration;
        }
        return 60;
    }

    /**
     * Format time for display (same as in BookingFormController)
     */
    private function formatTimeForDisplay($timeString)
    {
        try {
            $time = Carbon::createFromTimeString($timeString);
            return $time->format('g:i A');
        } catch (\Exception $e) {
            return $timeString;
        }
    }

    /**
     * Generate time slots (simplified version - you can copy from BookingFormController)
     */
    private function generateTimeSlotsForNextDays($days = 60, $interval = 15, $openTime = null, $closeTime = null, $openDaysStr = null)
    {
        $timeSlots = [];
        $startDate = Carbon::today();

        // Parse dynamic branch hours from database
        try {
            $openTimeCarbon = $openTime ? Carbon::createFromTimeString($openTime) : Carbon::createFromTime(11, 0, 0);
            $closeTimeCarbon = $closeTime ? Carbon::createFromTimeString($closeTime) : Carbon::createFromTime(7, 0, 0);
        } catch (\Exception $e) {
            // Fallback if time parsing fails
            $openTimeCarbon = Carbon::createFromTime(11, 0, 0);
            $closeTimeCarbon = Carbon::createFromTime(7, 0, 0);
        }

        // Format times for display
        $openTimeDisplay = $openTimeCarbon->format('g:i A');
        $closeTimeDisplay = $closeTimeCarbon->format('g:i A');

        // Determine operation type based on times
        $isOvernightOperation = $closeTimeCarbon < $openTimeCarbon;

        for ($i = 0; $i < $days; $i++) {
            $currentDate = $startDate->copy()->addDays($i);

            // Check if branch is open on this day based on open_days string
            if ($openDaysStr && !$this->isBranchOpenOnDay($currentDate, $openDaysStr)) {
                continue;  // Skip generation for closed days
            }

            $dateKey = $currentDate->format('Y-m-d');
            $dateLabel = $currentDate->format('M j, Y');

            if ($isOvernightOperation) {
                $slots = $this->generateOvernightOperationSlots($interval, $openTimeCarbon, $closeTimeCarbon, $currentDate, $openTimeDisplay, $closeTimeDisplay);
            } else {
                $slots = $this->generateSameDayOperationSlots($interval, $openTimeCarbon, $closeTimeCarbon, $currentDate, $openTimeDisplay, $closeTimeDisplay);
            }

            $timeSlots[$dateKey] = [
                'label' => $dateLabel,
                'slots' => $slots,
                'open_time' => $openTimeCarbon->format('H:i:s'),
                'close_time' => $closeTimeCarbon->format('H:i:s'),
                'is_overnight' => $isOvernightOperation
            ];
        }

        return $timeSlots;
    }

    /**
     * Check if branch is open on a specific day based on the open_days string
     */
    private function isBranchOpenOnDay($date, $openDaysStr)
    {
        if (empty($openDaysStr))
            return true;  // Default to open if not specified

        $dayName = strtolower($date->format('D'));  // mon, tue, etc.
        $openDaysStr = strtolower($openDaysStr);

        // Handle "Daily" or "Everyday"
        if (strpos($openDaysStr, 'daily') !== false || strpos($openDaysStr, 'everyday') !== false) {
            return true;
        }

        // Map days to integers (1=Mon, ..., 7=Sun)
        $daysMap = ['mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6, 'sun' => 7];
        $targetDayNum = $daysMap[$dayName] ?? 0;

        if ($targetDayNum === 0)
            return true;  // Safety fallback

        // Normalize string and split by comma to handle lists like "Mon-Wed, Fri"
        $parts = explode(',', $openDaysStr);

        foreach ($parts as $part) {
            $part = trim($part);

            if (strpos($part, '-') !== false) {
                // Handle Range (e.g., "Mon - Fri")
                list($start, $end) = explode('-', $part);
                $start = trim($start);
                $end = trim($end);

                // If it's a valid range
                if (isset($daysMap[$start]) && isset($daysMap[$end])) {
                    $startNum = $daysMap[$start];
                    $endNum = $daysMap[$end];

                    if ($startNum <= $endNum) {
                        // Normal range (e.g., Mon - Fri)
                        if ($targetDayNum >= $startNum && $targetDayNum <= $endNum)
                            return true;
                    } else {
                        // Wrap around range (e.g., Fri - Mon)
                        if ($targetDayNum >= $startNum || $targetDayNum <= $endNum)
                            return true;
                    }
                }
            } else {
                // Handle Single Day (e.g., "Mon")
                if (isset($daysMap[$part]) && $daysMap[$part] == $targetDayNum)
                    return true;
            }
        }

        return false;
    }

    /**
     * Generate time slots for overnight operation (close time is next day)
     */
    private function generateOvernightOperationSlots($interval, $openTime, $closeTime, $currentDate, $openTimeDisplay, $closeTimeDisplay)
    {
        $slots = [];

        // Overnight period (12:00 AM to close time) - AVAILABLE
        $overnightStart = $currentDate->copy()->setTime(0, 0, 0);
        $overnightEnd = $currentDate->copy()->setTime($closeTime->hour, $closeTime->minute, $closeTime->second);

        $overnightCurrent = $overnightStart->copy();
        while ($overnightCurrent < $overnightEnd) {
            $slots[] = [
                'value' => $overnightCurrent->format('H:i'),
                'label' => $overnightCurrent->format('g:i A'),
                'available' => true,
                'timestamp' => $overnightCurrent->timestamp,
                'date_key' => $currentDate->format('Y-m-d'),
                'date_label' => $currentDate->format('M j, Y'),
                'period_type' => 'overnight',
                'period_label' => "Overnight (12:00 AM - {$closeTimeDisplay})"
            ];
            $overnightCurrent->addMinutes($interval);
        }

        // IMPORTANT: Include the EXACT closing time in the overnight period for END TIME selection
        $exactClosingTime = $currentDate->copy()->setTime($closeTime->hour, $closeTime->minute, $closeTime->second);
        $slots[] = [
            'value' => $exactClosingTime->format('H:i'),
            'label' => $exactClosingTime->format('g:i A'),
            'available' => true,  // Available for end time
            'timestamp' => $exactClosingTime->timestamp,
            'date_key' => $currentDate->format('Y-m-d'),
            'date_label' => $currentDate->format('M j, Y'),
            'period_type' => 'closing_time',  // Special type
            'period_label' => "Closing Time ({$closeTimeDisplay})"
        ];

        // Break period (15 minutes after closing to open time) - CLOSED
        $breakStart = $currentDate->copy()->setTime($closeTime->hour, $closeTime->minute, $closeTime->second)->addMinutes($interval);
        $breakEnd = $currentDate->copy()->setTime($openTime->hour, $openTime->minute, $openTime->second);

        $breakCurrent = $breakStart->copy();
        while ($breakCurrent < $breakEnd && $breakCurrent->format('Y-m-d') === $currentDate->format('Y-m-d')) {
            $slots[] = [
                'value' => $breakCurrent->format('H:i'),
                'label' => $breakCurrent->format('g:i A'),
                'available' => false,
                'timestamp' => $breakCurrent->timestamp,
                'date_key' => $currentDate->format('Y-m-d'),
                'date_label' => $currentDate->format('M j, Y'),
                'period_type' => 'break',
                'period_label' => "Branch Closed ({$closeTimeDisplay} - {$openTimeDisplay})"
            ];
            $breakCurrent->addMinutes($interval);
        }

        // Day period (open time to 11:59 PM) - AVAILABLE
        $dayStart = $currentDate->copy()->setTime($openTime->hour, $openTime->minute, $openTime->second);
        $dayEnd = $currentDate->copy()->setTime(23, 59, 59);

        $dayCurrent = $dayStart->copy();
        while ($dayCurrent < $dayEnd) {
            $slots[] = [
                'value' => $dayCurrent->format('H:i'),
                'label' => $dayCurrent->format('g:i A'),
                'available' => true,
                'timestamp' => $dayCurrent->timestamp,
                'date_key' => $currentDate->format('Y-m-d'),
                'date_label' => $currentDate->format('M j, Y'),
                'period_type' => 'day',
                'period_label' => "Day ({$openTimeDisplay} - 11:59 PM)"
            ];
            $dayCurrent->addMinutes($interval);
        }

        return $slots;
    }

    /**
     * Generate time slots for same-day operation
     */
    private function generateSameDayOperationSlots($interval, $openTime, $closeTime, $currentDate, $openTimeDisplay, $closeTimeDisplay)
    {
        $slots = [];

        // Closed period before opening (12:00 AM to open time)
        $beforeOpenStart = $currentDate->copy()->setTime(0, 0, 0);
        $beforeOpenEnd = $currentDate->copy()->setTime($openTime->hour, $openTime->minute, $openTime->second);

        $beforeOpenCurrent = $beforeOpenStart->copy();
        while ($beforeOpenCurrent < $beforeOpenEnd) {
            $slots[] = [
                'value' => $beforeOpenCurrent->format('H:i'),
                'label' => $beforeOpenCurrent->format('g:i A'),
                'available' => false,
                'timestamp' => $beforeOpenCurrent->timestamp,
                'date_key' => $currentDate->format('Y-m-d'),
                'date_label' => $currentDate->format('M j, Y'),
                'period_type' => 'closed',
                'period_label' => "Closed (12:00 AM - {$openTimeDisplay})"
            ];
            $beforeOpenCurrent->addMinutes($interval);
        }

        // Open period (open time to close time) - AVAILABLE
        $openStart = $currentDate->copy()->setTime($openTime->hour, $openTime->minute, $openTime->second);
        $openEnd = $currentDate->copy()->setTime($closeTime->hour, $closeTime->minute, $closeTime->second);

        $openCurrent = $openStart->copy();
        while ($openCurrent < $openEnd) {
            $slots[] = [
                'value' => $openCurrent->format('H:i'),
                'label' => $openCurrent->format('g:i A'),
                'available' => true,
                'timestamp' => $openCurrent->timestamp,
                'date_key' => $currentDate->format('Y-m-d'),
                'date_label' => $currentDate->format('M j, Y'),
                'period_type' => 'open',
                'period_label' => "Open ({$openTimeDisplay} - {$closeTimeDisplay})"
            ];
            $openCurrent->addMinutes($interval);
        }

        // Closed period after closing (close time to 11:59 PM)
        $afterCloseStart = $currentDate->copy()->setTime($closeTime->hour, $closeTime->minute, $closeTime->second);
        $afterCloseEnd = $currentDate->copy()->setTime(23, 59, 59);

        $afterCloseCurrent = $afterCloseStart->copy();
        while ($afterCloseCurrent < $afterCloseEnd) {
            $slots[] = [
                'value' => $afterCloseCurrent->format('H:i'),
                'label' => $afterCloseCurrent->format('g:i A'),
                'available' => false,
                'timestamp' => $afterCloseCurrent->timestamp,
                'date_key' => $currentDate->format('Y-m-d'),
                'date_label' => $currentDate->format('M j, Y'),
                'period_type' => 'closed',
                'period_label' => "Closed ({$closeTimeDisplay} - 11:59 PM)"
            ];
            $afterCloseCurrent->addMinutes($interval);
        }

        return $slots;
    }

    /**
     * Get existing bookings for time slot checking (same as booking form)
     */
    public function getExistingBookingsForReschedule(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|integer',
            'service_category_id' => 'required|integer',
            'service_name_id' => 'required|integer',
            'seat_id' => 'required|integer',
            'date_start' => 'required|date',
            'date_end' => 'required|date',
            'exclude_booking_id' => 'nullable|integer',
        ]);

        $query = Booking::where('branch_id', $validated['branch_id'])
            ->where('service_category_id', $validated['service_category_id'])
            ->where('service_name_id', $validated['service_name_id'])
            ->where('seat_id', $validated['seat_id'])
            ->whereIn('booking_status', [1, 2, 4])  // Booked, Pending, Completed (exclude no-show)
            ->where(function ($query) use ($validated) {
                // Check for overlapping date ranges
                $query->where(function ($q) use ($validated) {
                    $q
                        ->whereBetween('date_start', [$validated['date_start'], $validated['date_end']])
                        ->orWhereBetween('date_end', [$validated['date_start'], $validated['date_end']])
                        ->orWhere(function ($q2) use ($validated) {
                            $q2
                                ->where('date_start', '<=', $validated['date_start'])
                                ->where('date_end', '>=', $validated['date_end']);
                        });
                });
            });

        if (!empty($validated['exclude_booking_id'])) {
            $query->where('id', '!=', $validated['exclude_booking_id']);
        }

        $existingBookings = $query->get([
            'id',
            'booking_ref_no',
            'start_time',
            'end_time',
            'date_start',
            'date_end',
            'extended_start_time',
            'extended_end_time',
            'extended_date_start',
            'extended_date_end',
            'booking_status'
        ]);

        return response()->json($existingBookings);
    }

    /**
     * Show feedback form for completed booking
     */
    public function showFeedbackForm($booking_uuid)
    {
        $customer = Auth::guard('customer')->user();

        $booking = Booking::with(['serviceName', 'branch', 'serviceCategory'])
            ->where('uuid', $booking_uuid)
            ->where('customer_account_id', $customer->id)
            ->where('booking_status', 4)  // Only completed bookings
            ->firstOrFail();

        // Check if feedback already exists for this service, branch, and customer
        $existingFeedback = Feedback::where('booking_id', $booking->id)
            ->where('service_category_id', $booking->service_category_id)
            ->where('service_name_id', $booking->service_name_id)
            ->where('branch_id', $booking->branch_id)
            ->where('customer_account_id', $customer->id)
            ->first();

        return view('customer.my_bookings.feedback_form', compact('booking', 'existingFeedback'));
    }

    /**
     * Submit feedback for completed booking
     */
    public function submitFeedback(Request $request, $booking_uuid)
    {
        $customer = Auth::guard('customer')->user();

        $booking = Booking::with(['serviceName', 'branch', 'serviceCategory'])
            ->where('uuid', $booking_uuid)
            ->where('customer_account_id', $customer->id)
            ->where('booking_status', 4)  // Only completed bookings
            ->firstOrFail();

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        // Check if feedback already exists for this service, branch, and customer
        $existingFeedback = Feedback::where('booking_id', $booking->id)
            ->where('service_category_id', $booking->service_category_id)
            ->where('service_name_id', $booking->service_name_id)
            ->where('branch_id', $booking->branch_id)
            ->where('customer_account_id', $customer->id)
            ->first();

        if ($existingFeedback) {
            return redirect()->back()->with('error', 'You have already submitted feedback for this service at this branch.');
        }

        // Create new feedback
        Feedback::create([
            'booking_id' => $booking->id,
            'customer_account_id' => $customer->id,
            'service_name_id' => $booking->service_name_id,
            'branch_id' => $booking->branch_id,
            'service_category_id' => $booking->service_category_id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            'approved' => 1,
            'active' => 1,
        ]);

        // Send notifications
        $actor = Auth::guard('customer')->user();
        $bookingBranch = Branch::find($booking->branch_id);
        $customerAccount = CustomerAccount::find($booking->customer_account_id);
        $owner = OwnerAccount::find($bookingBranch->owner_account_id);
        $staffs = StaffAccount::where('branch_id', $booking->branch_id)->get();

        // Prepare additional data for notifications
        $additionalData = [
            'rating' => $validated['rating'],
            'comment' => substr($validated['comment'], 0, 100)  // Limit comment length
        ];

        // Send notification to owner
        Notification::send(collect([$owner]), new BookingListNotification(
            $booking,
            $bookingBranch,
            $customerAccount,
            $actor,
            'feedback',
            $additionalData
        ));

        // Send notification to staff
        Notification::send($staffs, new BookingListStaffNotification(
            $booking,
            $bookingBranch,
            $customerAccount,
            $actor,
            'feedback',
            $additionalData
        ));

        // Send notification to customer
        Notification::send($customerAccount, new BookingListCustomerNotification(
            $booking,
            $bookingBranch,
            $customerAccount,
            $actor,
            'feedback',
            $additionalData
        ));

        return redirect()
            ->route('sub_three.my_bookings.showMyBookings')
            ->with('success', 'Thank you for your feedback!');
    }

    // PROTECTED HELPER METHODS
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

    protected function getBookingStatusClass($status)
    {
        switch ($status) {
            case 0:
                return 'status-cancelled';
            case 1:
                return 'status-booked';
            case 2:
                return 'status-pending';
            case 3:
                return 'status-no-show';
            case 4:
                return 'status-completed';
            default:
                return 'status-pending';
        }
    }

    protected function getPaymentStatusText($status)
    {
        switch ($status) {
            case 1:
                return 'Paid';
            case 2:
                return 'Unpaid';
            case 0:
                return 'Invalid';
            default:
                return 'N/A';
        }
    }

    protected function getPaymentStatusClass($status)
    {
        switch ($status) {
            case 1:
                return 'status-paid';
            case 2:
                return 'status-unpaid';
            case 0:
                return 'status-invalid';
            default:
                return 'status-unpaid';
        }
    }

    protected function getPaymentMethodText($method)
    {
        switch ($method) {
            case 0:
                return 'Cash';
            case 1:
                return 'GCash';
            case 2:
                return 'Debit';
            case 3:
                return 'Pay Later';
            default:
                return 'N/A';
        }
    }

    protected function formatDate($dateString)
    {
        if (!$dateString)
            return 'N/A';
        return Carbon::parse($dateString)->format('M j, Y');
    }

    protected function formatTime($timeString)
    {
        if (!$timeString)
            return 'N/A';
        return Carbon::parse($timeString)->format('g:i A');
    }

    protected function formatDateTime($dateTimeString)
    {
        if (!$dateTimeString)
            return 'N/A';
        return Carbon::parse($dateTimeString)->format('M j, Y g:i A');
    }

    protected function formatCurrency($amount)
    {
        if ($amount === null)
            return 'N/A';
        return '₱' . number_format($amount, 2);
    }

    protected function formatDuration($minutes)
    {
        if (!$minutes || $minutes < 1)
            return '0 min';

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours === 0) {
            return $remainingMinutes . ' min' . ($remainingMinutes !== 1 ? 's' : '');
        } elseif ($remainingMinutes === 0) {
            return $hours . ' hr' . ($hours !== 1 ? 's' : '');
        } else {
            return $hours . ' hr' . ($hours !== 1 ? 's' : '') . ' : ' . $remainingMinutes . ' min' . ($remainingMinutes !== 1 ? 's' : '');
        }
    }

    protected function getRoomSeatText($booking)
    {
        $parts = [];
        if ($booking->seat?->room_no) {
            $parts[] = 'Room ' . $booking->seat->room_no;
        }
        if ($booking->seat?->seat_no) {
            $parts[] = 'Seat ' . $booking->seat->seat_no;
        }
        return count($parts) > 0 ? implode(' / ', $parts) : 'N/A';
    }
}