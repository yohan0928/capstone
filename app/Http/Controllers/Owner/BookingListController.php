<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Branch;
use App\Models\CustomerAccount;
use App\Models\CustomerCheckin;
use App\Models\Order;
use App\Models\OwnerAccount;
use App\Models\Seat;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use App\Models\StaffAccount;
use App\Services\StaffActivityLogger;
use App\Notifications\Customer\BookingListCustomerNotification;
use App\Notifications\Owner\BookingListNotification;
use App\Notifications\Staff\BookingListStaffNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class BookingListController extends Controller
{
    public function showBookingList(Request $request)
    {
        $owner = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        $branchIds = Branch::where('owner_account_id', $ownerId)->where('active', 1)->pluck('id');

        // Base query for owner's branches
        $baseQuery = Booking::whereIn('branch_id', $branchIds);

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
            ])
            // Add the count of unpaid pay-later orders
            ->withCount(['orders as unpaid_pay_later_orders_count' => function($query) {
                $query->whereHas('payments', function($q) {
                    $q->where('payment_method', 3)
                      ->where('order_payment_status', 0);
                });
            }]);

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

        // Search by customer full name or booking reference
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('customerAccount', function ($q) use ($searchTerm) {
                    $q->where(DB::raw("CONCAT(first_name,' ',last_name)"), 'LIKE', "%{$searchTerm}%");
                })->orWhere('booking_ref_no', 'LIKE', "%{$searchTerm}%");
            });
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
                $matchingCategoryIds = ServiceCategory::whereIn('branch_id', $branchIds)
                    ->where('service_category', $categoryName)
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
                $matchingServiceIds = ServiceName::whereIn('branch_id', $branchIds)
                    ->where('service_name', $serviceName)
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

        // Calculate Stats (based on filtered query)
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

        // Dropdown options - initial load
        $branches = Branch::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->orderBy('branch_name')
            ->get();

        // Dynamic dropdowns based on filters - Show unique values
        $categories = ServiceCategory::whereIn('branch_id', $branchIds)
            ->where('active', 1)
            ->orderBy('service_category')
            ->get()
            ->unique('service_category')
            ->values();

        $servicesQuery = ServiceName::whereIn('branch_id', $branchIds)
            ->where('active', 1);

        // Filter services by category text if category is selected
        if ($request->service_category_id) {
            $selectedCategory = ServiceCategory::find($request->service_category_id);
            if ($selectedCategory) {
                $categoryName = $selectedCategory->service_category;
                $matchingCategoryIds = ServiceCategory::whereIn('branch_id', $branchIds)
                    ->where('service_category', $categoryName)
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

        $seats = Seat::whereIn('branch_id', $branchIds)
            ->where('active', 1)
            ->when($request->branch_id, fn($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->service_category_id, fn($q) => $q->where('service_category_id', $request->service_category_id))
            ->orderBy('seat_no')
            ->get();

        // Get all services for calculation logic
        $allServicesForCalc = ServiceName::whereIn('branch_id', $branchIds)
            ->where('active', 1)
            ->orderBy('service_category_id')
            ->orderBy('time_duration', 'desc')
            ->get();

        // Handle AJAX Request
        if ($request->ajax()) {
            // Transform bookings for JSON response
            $bookingsData = $bookings->map(function ($booking) {
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
                    'checkin_status' => $booking->checkin_status,
                    'time_used' => $booking->time_used,
                    'extended_time_used' => $booking->extended_time_used,
                    'total_time_used' => $booking->total_time_used,
                    'unpaid_pay_later_orders_count' => $booking->unpaid_pay_later_orders_count ?? 0, // ADD THIS LINE
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

        // Return view for regular request
        return view('owner.booking.booking_list', compact(
            'bookings', 'branches', 'categories', 'services', 'seats', 'allServicesForCalc', 'stats'
        ))->with('oldFilters', $request->all());
    }

    public function getFilterOptions(Request $request)
    {
        $owner = Auth::guard('owner')->user();
        $ownerId = $owner->id;
        $branchIds = Branch::where('owner_account_id', $ownerId)->pluck('id');

        // Get unique service categories (no duplicates)
        $categories = ServiceCategory::whereIn('branch_id', $branchIds)
            ->where('active', 1)
            // Removed branch_id filter to show all categories by text
            ->orderBy('service_category')
            ->get()
            ->unique('service_category') // Get unique by service_category field
            ->values(); // Reset array keys

        // Get unique service names (no duplicates)
        $servicesQuery = ServiceName::whereIn('branch_id', $branchIds)
            ->where('active', 1);

        // Filter services by category text if category is selected
        if ($request->service_category_id) {
            $selectedCategory = ServiceCategory::find($request->service_category_id);
            if ($selectedCategory) {
                $categoryName = $selectedCategory->service_category;
                $matchingCategoryIds = ServiceCategory::whereIn('branch_id', $branchIds)
                    ->where('service_category', $categoryName)
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

        $seats = Seat::whereIn('branch_id', $branchIds)
            ->where('active', 1)
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
            $owner = Auth::guard('owner')->user();
            $ownerId = $owner->id;

            // Security check - ensure the booking belongs to owner's branch
            $branchIds = Branch::where('owner_account_id', $ownerId)->pluck('id');
            if (!$branchIds->contains($booking->branch_id)) {
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
                // Time Usage Data - CORRECTED
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
        // Login as Owner
        $owner = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        // Find the booking by UUID with all necessary relationships
        $booking = Booking::with([
            'customerAccount',
            'serviceCategory',
            'serviceName',
            'seat',
            'payment',  // Single payment relationship
            'branch',
        ])->where('uuid', $booking_uuid)->firstOrFail();

        $branchIds = Branch::where('owner_account_id', $ownerId)->pluck('id');
        if (!$branchIds->contains($booking->branch_id)) {
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
            // Audit Trail
            'created_by_formatted' => $this->formatAuditUser($booking->creator, $booking->created_by_type),
            'date_created_formatted' => $this->formatDateTime($booking->date_created),
            'updated_by_formatted' => $this->formatAuditUser($booking->updator, $booking->updated_by_type),
            'date_updated_formatted' => $this->formatDateTime($booking->date_updated),
            'last_updated_by_formatted' => $booking->last_updated_by ? $this->formatAuditUser($booking->lastUpdator, $booking->last_updated_by_type) : 'N/A',
            'last_date_updated_formatted' => $booking->last_date_updated ? $this->formatDateTime($booking->last_date_updated) : 'N/A',
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

        return view('owner.booking.booking_details', compact(
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

            // Add creation data only if it doesn't exist
            if (!$payment->created_by) {
                $payment->created_by = Auth::guard('owner')->id();
                $payment->created_by_type = 'owner';
                $payment->date_created = now();
            }

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
            'added_by_type' => 'Owner',
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

        // Payment audit trail - only update if data exists, otherwise set creation data
        if (!is_null($payment->updated_by)) {
            $payment->last_updated_by = $payment->updated_by;
            $payment->last_updated_by_type = $payment->updated_by_type;
            $payment->last_date_updated = $payment->date_updated;
        }

        $payment->updated_by = Auth::guard('owner')->id();
        $payment->updated_by_type = 'owner';
        $payment->date_updated = now();

        // If this is a new payment and created_by wasn't set, set it now
        if (!$payment->created_by) {
            $payment->created_by = Auth::guard('owner')->id();
            $payment->created_by_type = 'owner';
            $payment->date_created = now();
        }

        // Booking audit trail
        if (!is_null($booking->updated_by)) {
            $booking->last_updated_by = $booking->updated_by;
            $booking->last_updated_by_type = $booking->updated_by_type;
            $booking->last_date_updated = $booking->date_updated;
        }
        $booking->updated_by = Auth::guard('owner')->id();
        $booking->updated_by_type = 'owner';
        $booking->date_updated = now();

        $payment->save();
        $booking->save();

        // Send notification for branch update
        $actor = Auth::guard('owner')->user();

        // Get the specific branch for this booking
        $bookingBranch = Branch::find($booking->branch_id);

        // Get related models for notification
        $customer = CustomerAccount::find($booking->customer_account_id);

        // Get specific owner to notify
        $owner = Auth::guard('owner')->user();
        $owners = OwnerAccount::where('id', $owner->id)->get();

        // Send notification
        Notification::send($owners, new BookingListNotification(
            $booking,
            $bookingBranch,
            $customer,
            $actor,
            'notes'
        ));

        $staffMembers = StaffAccount::where('branch_id', $booking->branch_id)
            ->where('owner_account_id', $owner->id)
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
            $owner = Auth::guard('owner')->user();
            $ownerId = $owner->id;

            // Security check
            $branchIds = Branch::where('owner_account_id', $ownerId)->pluck('id');
            if (!$branchIds->contains($booking->branch_id)) {
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

    public function confirmBooking(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'customer_email' => 'required|email',
            'contact_no' => 'nullable|string|max:20',
        ]);

        $booking = Booking::with(['customerAccount', 'serviceName', 'seat'])->findOrFail($request->booking_id);
        $owner = Auth::guard('owner')->user();
        $branchIds = Branch::where('owner_account_id', $owner->id)->pluck('id');

        // Security check
        if (!$branchIds->contains($booking->branch_id)) {
            return redirect()->back()->with('error', 'You are not authorized to update this booking.');
        }

        // Check if booking is 'Pending' and 'Online'
        if ($booking->booking_status == 2 && $booking->booking_type == 1) {
            $booking->booking_status = 1;

            // Booking audit trail
            if (!is_null($booking->updated_by)) {
                $booking->last_updated_by = $booking->updated_by;
                $booking->last_updated_by_type = $booking->updated_by_type;
                $booking->last_date_updated = $booking->date_updated;
            }
            $booking->updated_by = Auth::guard('owner')->id();
            $booking->updated_by_type = 'owner';
            $booking->date_updated = now();

            // Add creation data only if it doesn't exist
            if (!$booking->created_by) {
                $booking->created_by = Auth::guard('owner')->id();
                $booking->created_by_type = 'owner';
                $booking->date_created = now();
            }

            $booking->save();

            // Update customer email and contact number if provided
            $customer = $booking->customerAccount;
            if ($customer) {
                $customer->email = $request->customer_email;
                if ($request->contact_no) {
                    $customer->contact_no = $request->contact_no;
                }
                $customer->save();
            } else {
                // Create customer account if it doesn't exist (fallback)
                $customer = CustomerAccount::create([
                    'first_name' => 'Customer',
                    'last_name' => 'Booking #' . $booking->id,
                    'email' => $request->customer_email,
                    'contact_no' => $request->contact_no,
                    'active' => 1,
                ]);
                $booking->customer_account_id = $customer->id;
                $booking->save();
            }

            // Send notification for branch update
            $actor = Auth::guard('owner')->user();

            // Get the specific branch for this booking
            $bookingBranch = Branch::find($booking->branch_id);
            $serviceCategory = ServiceCategory::find($bookingBranch->service_category_id);
            $serviceName = ServiceName::find($bookingBranch->service_name_id);

            // Get specific owner to notify
            $owner = Auth::guard('owner')->user();
            $owners = OwnerAccount::where('id', $owner->id)->get();

            // Send notification
            Notification::send($owners, new BookingListNotification(
                $booking,
                $bookingBranch,
                $customer,
                $actor,
                'confirmed'
            ));

            $staffMembers = StaffAccount::where('branch_id', $booking->branch_id)
                ->where('owner_account_id', $owner->id)
                ->where('active', 1)
                ->get();

            // Send notification
            Notification::send($staffMembers, new BookingListStaffNotification(
                $booking,
                $bookingBranch,
                $customer,
                $actor,
                'confirmed'
            ));

            // Send notification
            Notification::send($customer, new BookingListCustomerNotification(
                $booking,
                $bookingBranch,
                $customer,
                $actor,
                'confirmed'
            ));

            // Send QR code email directly
            $this->sendQRCodeEmail($customer, $booking, $booking->serviceName, $booking->seat);

            return redirect()->back()->with('success', 'Booking confirmed successfully! QR code sent to ' . $request->customer_email);
        }

        return redirect()->back()->with('error', 'Booking cannot be confirmed.');
    }

    /**
     * Send QR code email to customer
     */
    public function sendQRCodeEmail($customer, $booking, $serviceName, $seat)
    {
        $qrCodeData = null;
        $tempPath = null;
        $qrCodeAttached = false;  // Initialize the variable

        try {
            \Log::info('Attempting to send QR code email to: ' . $customer->email);

            // Get additional data needed for the email
            $branch = Branch::find($booking->branch_id);
            $serviceCategory = ServiceCategory::find($booking->service_category_id);
            $bookingPayment = BookingPayment::where('booking_id', $booking->id)->first();

            // Generate QR Code and get the raw image data
            $qrCodeData = $this->generateQRCode($booking);
            if ($qrCodeData) {
                $tempDir = storage_path('app/temp');
                if (!file_exists($tempDir)) {
                    mkdir($tempDir, 0777, true);
                }
                $tempPath = $tempDir . '/' . Str::random(40) . '.png';
                file_put_contents($tempPath, $qrCodeData);
                $qrCodeAttached = true;  // Set to true if QR code is generated
            }

            $emailData = [
                'customer' => $customer,
                'booking' => $booking,
                'serviceName' => $serviceName,
                'serviceCategory' => $serviceCategory,
                'branch' => $branch,
                'seat' => $seat,
                'bookingPayment' => $bookingPayment,
                'qrCodePath' => null,
                'appName' => config('app.name', 'LinkudHub'),
                'qrCodeAttached' => $qrCodeAttached,  // <-- This is the fix
            ];

            try {
                Mail::send('owner.booking.send_confirmation_email_qr_code', $emailData, function ($message) use ($customer, $booking, $tempPath) {
                    $message
                        ->to($customer->email)
                        ->subject('From LinkudHub')
                        ->from(config('mail.from.address'), config('mail.from.name'));

                    if ($tempPath && file_exists($tempPath)) {
                        $message->attach($tempPath, [
                            'as' => 'qr-code.png',
                            'mime' => 'image/png',
                        ]);
                    }
                });
            } finally {
                if ($tempPath && file_exists($tempPath)) {
                    unlink($tempPath);
                }
            }
            \Log::info('QR code email sent successfully to: ' . $customer->email);
        } catch (\Exception $e) {
            \Log::error('Failed to send QR code email: ' . $e->getMessage());
            \Log::error('Email error details: ', ['exception' => $e]);
            $this->sendEmailWithoutQRCode($customer, $booking, $serviceName, $seat);
        }
    }

    /**
     * Generate QR code for booking
     */
    private function generateQRCode($booking)
    {
        try {
            $qrContent = json_encode([
                'booking_ref' => $booking->booking_ref_no,
                'customer_id' => $booking->customer_account_id,
                'booking_id' => $booking->id,
                'timestamp' => now()->timestamp
            ]);

            // Use a simpler, more reliable QR code API
            $apiUrl = 'https://api.qrserver.com/v1/create-qr-code/';
            $client = new \GuzzleHttp\Client();

            $response = $client->get($apiUrl, [
                'query' => [
                    'data' => $qrContent,
                    'size' => '300x300',
                    'format' => 'png',
                ],
                'timeout' => 10  // 10 second timeout
            ]);

            if ($response->getStatusCode() === 200) {
                // Return the raw PNG image data
                return $response->getBody()->getContents();
            }

            \Log::error('QR Server API returned status: ' . $response->getStatusCode());
            return null;
        } catch (\Exception $e) {
            \Log::error('Failed to generate QR code using qrserver: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Send fallback email without QR code
     */
    private function sendEmailWithoutQRCode($customer, $booking, $serviceName, $seat)
    {
        try {
            $branch = Branch::find($booking->branch_id);
            $serviceCategory = ServiceCategory::find($booking->service_category_id);
            $bookingPayment = BookingPayment::where('booking_id', $booking->id)->first();

            $emailData = [
                'customer' => $customer,
                'booking' => $booking,
                'serviceName' => $serviceName,
                'serviceCategory' => $serviceCategory,
                'branch' => $branch,
                'seat' => $seat,
                'bookingPayment' => $bookingPayment,
                'qrCodePath' => null,
                'appName' => config('app.name', 'LinkudHub'),
                'qrCodeAttached' => false,  // <-- Added this
            ];

            Mail::send('customer.home.send_email_qr_code', $emailData, function ($message) use ($customer, $booking) {
                $message
                    ->to($customer->email)
                    ->subject('From LinkudHub')
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });
        } catch (\Exception $e) {
            // Silent fail for email errors
        }
    }

    public function markNoShow(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);

        $booking = Booking::findOrFail($request->booking_id);
        $owner = Auth::guard('owner')->user();
        $branch = Branch::where('owner_account_id', $owner->id)->pluck('id');

        // Security check
        if (!$branch->contains($booking->branch_id)) {
            return redirect()->back()->with('error', 'You are not authorized to update this booking.');
        }

        // Check if booking status is Booked (1) only
        if ($booking->booking_status == 1) {
            $booking->booking_status = 3;  // No-show

            // Booking audit trail
            if (!is_null($booking->updated_by)) {
                $booking->last_updated_by = $booking->updated_by;
                $booking->last_updated_by_type = $booking->updated_by_type;
                $booking->last_date_updated = $booking->date_updated;
            }
            $booking->updated_by = Auth::guard('owner')->id();
            $booking->updated_by_type = 'owner';
            $booking->date_updated = now();

            // Add creation data only if it doesn't exist
            if (!$booking->created_by) {
                $booking->created_by = Auth::guard('owner')->id();
                $booking->created_by_type = 'owner';
                $booking->date_created = now();
            }

            $booking->save();

            // Send notification for branch update
            $actor = Auth::guard('owner')->user();

            // Get the specific branch for this booking
            $bookingBranch = Branch::find($booking->branch_id);

            // Get related models for notification
            $customer = CustomerAccount::find($booking->customer_account_id);

            // Get specific owner to notify
            $owner = Auth::guard('owner')->user();
            $owners = OwnerAccount::where('id', $owner->id)->get();

            // Send notification
            Notification::send($owners, new BookingListNotification(
                $booking,
                $bookingBranch,
                $customer,
                $actor,
                'no_show'
            ));

            $staffMembers = StaffAccount::where('branch_id', $booking->branch_id)
                ->where('owner_account_id', $owner->id)
                ->where('active', 1)
                ->get();

            // Send notification
            Notification::send($staffMembers, new BookingListStaffNotification(
                $booking,
                $bookingBranch,
                $customer,
                $actor,
                'no_show'
            ));

            // Send notification
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

    protected function formatAuditUser($user, $type)
    {
        if (!$type)
            return 'N/A';

        $name = '';
        if ($user) {
            $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        }

        if (!$name) {
            $name = 'Check your profile.';
        }

        return $name . ' (' . $type . ')';
    }
}