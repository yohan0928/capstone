<?php

namespace App\Http\Controllers\Owner;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\Branch;
use App\Models\Booking;
use App\Models\ServiceName;
use App\Models\OwnerAccount;
use App\Models\StaffAccount;
use Illuminate\Http\Request;
use App\Models\BookingPayment;
use App\Models\CustomerAccount;
use App\Models\CustomerCheckin;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Owner\OrderPaymentNotification;
use App\Notifications\Staff\OrderPaymentStaffNotification;
use App\Notifications\Owner\BookingListPaymentNotification;
use App\Notifications\Customer\OrderPaymentCustomerNotification;
use App\Notifications\Staff\BookingListPaymentStaffNotification;
use App\Notifications\Customer\BookingListPaymentCustomerNotification;

class BookingListPaymentController extends Controller
{
    /**
     * Show main payment page
     */
    public function showMainPaymentPage($booking_uuid)
    {
        $owner = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        // Find the booking by UUID
        $booking = Booking::where('uuid', $booking_uuid)->firstOrFail();

        // Security check
        $branchIds = Branch::where('owner_account_id', $ownerId)->pluck('id');
        if (!$branchIds->contains($booking->branch_id)) {
            abort(403, 'Unauthorized access to this booking.');
        }

        // Load all necessary relationships including checkin
        $booking->load([
            'customerAccount',
            'serviceCategory',
            'serviceName',
            'seat',
            'payment',
            'extensionPayment',
            'branch',
        ]);

        // Load checkin data for actual time used
        $checkin = CustomerCheckin::where('booking_id', $booking->id)->first();
        $totalTimeUsed = $checkin->total_time_used ?? 0;
        $extendedTimeUsed = $checkin->extended_time_used ?? 0;

        // Calculate durations
        $bookingDuration = $this->calculateBookingDurationInMinutes($booking->start_time, $booking->end_time);

        // Calculate main booking amount
        $mainAmount = $this->calculateBookingAmount($booking);

        // Ensure mainAmount is numeric
        if (!is_numeric($mainAmount)) {
            $mainAmount = 0;
        }

        // Get existing main payment if any
        $mainPayment = BookingPayment::where('booking_id', $booking->id)
            ->where('payment_category', 1)
            ->first();

        // Calculate service pricing info for breakdown
        $servicePrice = $booking->serviceName->price ?? 0;
        $serviceTimeDuration = $booking->serviceName->time_duration ?? 1;

        // Calculate rates for display
        $hourlyRate = 0;
        $fifteenMinRate = 0;
        if ($serviceTimeDuration > 0 && is_numeric($servicePrice)) {
            $hourlyRate = floatval($servicePrice) / floatval($serviceTimeDuration);
            $fifteenMinRate = $hourlyRate / 4;
        }

        // Get breakdown for main payment
        $breakdown = $this->getMainPaymentBreakdown($booking, $totalTimeUsed, $mainAmount, $hourlyRate, $fifteenMinRate);

        // Pre-calculate formatted data with consistent duration formatting
        $formattedData = [
            'booking_status_text' => $this->getBookingStatusText($booking->booking_status),
            'booking_status_class' => $this->getBookingStatusClass($booking->booking_status),
            'payment_status_text' => $this->getPaymentStatusText($mainPayment->payment_status ?? 2),
            'payment_status_class' => $this->getPaymentStatusClass($mainPayment->payment_status ?? 2),
            'payment_method_text' => $this->getPaymentMethodText($mainPayment->payment_method ?? 3),
            'booking_date_formatted' => $this->formatDate($booking->booking_date),
            'date_start_formatted' => $this->formatDate($booking->date_start),
            'date_end_formatted' => $this->formatDate($booking->date_end),
            'start_time_formatted' => $this->formatTime($booking->start_time),
            'end_time_formatted' => $this->formatTime($booking->end_time),
            'payment_date_formatted' => $this->formatDateTime($mainPayment->payment_date ?? ''),
            'duration_formatted' => $this->formatDuration($bookingDuration),
            'total_time_used_formatted' => $this->formatDuration($totalTimeUsed),
            'room_seat_text' => $this->getRoomSeatText($booking),  // This should work now
            'service_price_formatted' => $this->formatCurrency($servicePrice),
            'total_amount_formatted' => $this->formatCurrency($mainPayment->total_amount ?? $mainAmount),
            'amount_paid_formatted' => $this->formatCurrency($mainPayment->amount_paid ?? 0),
            'change_formatted' => $this->formatCurrency($mainPayment->change ?? 0),
            'calculated_main_amount_formatted' => $this->formatCurrency($mainAmount),
            // Additional data for breakdown
            'service_time_duration' => $serviceTimeDuration,
            'hourly_rate' => $hourlyRate,
            'fifteen_min_rate' => $fifteenMinRate,
            'breakdown' => $breakdown,
        ];

        // Get unpaid pay-later orders - CORRECTED QUERY
        $unpaidOrders = Order::where('booking_id', $booking->id)
            ->where('branch_id', $booking->branch_id)
            ->whereHas('payments', function ($query) {
                $query
                    ->where('payment_method', 3)  // 3 = pay-later
                    ->where('order_payment_status', 0);  // 0 = unpaid
            })
            ->with(['payments' => function ($query) {
                $query
                    ->where('payment_method', 3)
                    ->where('order_payment_status', 0);
            }, 'items.product', 'customer'])
            ->get();

        $totalUnpaidOrdersAmount = $unpaidOrders->sum(function ($order) {
            return $order
                ->payments
                ->where('payment_method', 3)
                ->where('order_payment_status', 0)
                ->sum('total_amount');
        });

        $hasUnpaidOrders = $unpaidOrders->count() > 0;

        // Calculate grand total (time duration + unpaid orders)
        $grandTotal = $mainAmount + $totalUnpaidOrdersAmount;

        // Add to formattedData
        $formattedData['unpaid_orders_count'] = $unpaidOrders->count();
        $formattedData['total_unpaid_orders_amount_formatted'] = $this->formatCurrency($totalUnpaidOrdersAmount);
        $formattedData['grand_total_formatted'] = $this->formatCurrency($grandTotal);
        $formattedData['has_unpaid_orders'] = $hasUnpaidOrders;  // Use the variable

        return view('owner.booking.booking_list_payment.main_payment_page', compact(
            'booking', 'checkin', 'totalTimeUsed', 'extendedTimeUsed',
            'formattedData', 'mainPayment', 'mainAmount', 'hourlyRate', 'fifteenMinRate',
            'unpaidOrders', 'totalUnpaidOrdersAmount', 'grandTotal', 'hasUnpaidOrders'  // Add hasUnpaidOrders here
        ));
    }

    private function getMainPaymentBreakdown($booking, $totalTimeUsed, $mainAmount, $hourlyRate, $fifteenMinRate)
    {
        if (!$booking || !$booking->serviceName) {
            return [];
        }

        $bookedService = $booking->serviceName;
        $packageHours = floatval($bookedService->time_duration ?? 1);
        $packagePrice = floatval($bookedService->price ?? 0);
        $packageMinutes = $packageHours * 60;

        // Ensure values are numeric
        if (!is_numeric($packageMinutes) || $packageMinutes < 0) {
            $packageMinutes = 0;
        }
        if (!is_numeric($totalTimeUsed) || $totalTimeUsed < 0) {
            $totalTimeUsed = 0;
        }

        $breakdown = [];

        // Always show package deal
        $breakdown[] = [
            'label' => 'Service Package',
            'time' => $this->formatDuration($packageMinutes),
            'details' => $packageHours . ' hour' . ($packageHours != 1 ? 's' : ''),
            'price' => $this->formatCurrency($packagePrice)
        ];

        // Check if there's extra time beyond package
        $extraMinutes = $totalTimeUsed - $packageMinutes;

        if ($extraMinutes > 0 && is_numeric($extraMinutes)) {
            $extraSegments = ceil($extraMinutes / 15);
            $extraAmount = $extraSegments * $fifteenMinRate;

            $breakdown[] = [
                'label' => 'Extra Time (15-min increments)',
                'time' => $this->formatDuration($extraMinutes),
                'details' => 'Beyond package time',
                'price' => $this->formatCurrency($extraAmount)
            ];
        }

        return $breakdown;
    }

    /**
     * Show extension payment page
     */
    public function showExtensionPaymentPage($booking_uuid)
    {
        $owner = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        // Find the booking by UUID
        $booking = Booking::where('uuid', $booking_uuid)->firstOrFail();

        // Security check
        $branchIds = Branch::where('owner_account_id', $ownerId)->pluck('id');
        if (!$branchIds->contains($booking->branch_id)) {
            abort(403, 'Unauthorized access to this booking.');
        }

        // Load all necessary relationships
        $booking->load([
            'customerAccount',
            'serviceCategory',
            'serviceName',
            'seat',
            'payment',
            'extensionPayment',
            'branch',
        ]);

        // Load checkin data for extended time
        $checkin = CustomerCheckin::where('booking_id', $booking->id)->first();
        $extendedTimeUsed = $checkin->extended_time_used ?? 0;
        $timeUsed = $checkin->time_used ?? 0;
        $totalTimeUsed = $checkin->total_time_used ?? 0;

        // Calculate durations
        $bookingDuration = $this->calculateBookingDurationInMinutes($booking->start_time, $booking->end_time);
        $totalTimeDuration = $bookingDuration + $extendedTimeUsed;

        // Calculate extended time amount using new logic
        $extendedAmount = $this->calculateExtendedTimeAmountNew($booking, $extendedTimeUsed);
        $hasExtension = $extendedAmount > 0;

        // Get existing extension payment if any
        $extensionPayment = BookingPayment::where('booking_id', $booking->id)
            ->where('payment_category', 0)
            ->first();

        // Calculate service pricing info
        $servicePrice = $booking->serviceName->price ?? 0;
        $serviceTimeDuration = $booking->serviceName->time_duration ?? 1;

        // Get the 1-hour service from the same service category to calculate 15-min rate
        $hourlyService = $this->getOneHourService($booking);
        $fifteenMinRate = $this->calculateFifteenMinRate($hourlyService);

        // Get breakdown for table display - Use formatExtensionTime for extension-specific displays
        $breakdown = $this->getExtensionBreakdown($booking, $extendedTimeUsed, $extendedAmount, $fifteenMinRate);

        // Format hourly service data for blade template
        $hourlyServiceData = null;
        if ($hourlyService) {
            $hourlyServiceData = [
                'service_name' => $hourlyService->service_name,
                'price_formatted' => $this->formatCurrency($hourlyService->price),
                'price' => $hourlyService->price,
            ];
        }

        // Pre-calculate formatted data - Use formatExtensionTime for extension time displays
        $formattedData = [
            // Booking Status
            'booking_status_text' => $this->getBookingStatusText($booking->booking_status),
            'booking_status_class' => $this->getBookingStatusClass($booking->booking_status),
            // Extension Payment Information
            'extension_payment_status_text' => $this->getPaymentStatusText($extensionPayment->payment_status ?? 2),
            'extension_payment_status_class' => $this->getPaymentStatusClass($extensionPayment->payment_status ?? 2),
            'extension_payment_method_text' => $this->getPaymentMethodText($extensionPayment->payment_method ?? 3),
            // Dates and Times
            'booking_date_formatted' => $this->formatDate($booking->booking_date),
            'extended_date_start_formatted' => $this->formatDate($booking->extended_date_start),
            'extended_date_end_formatted' => $this->formatDate($booking->extended_date_end),
            'extended_start_time_formatted' => $this->formatTime($booking->extended_start_time),
            'extended_end_time_formatted' => $this->formatTime($booking->extended_end_time),
            'extension_payment_date_formatted' => $this->formatDateTime($extensionPayment->payment_date ?? ''),
            // Duration - Use formatExtensionTime for extension time to show minutes only
            'total_time_used_formatted' => $this->formatDuration($totalTimeUsed),
            'extended_time_formatted' => $this->formatExtensionTime($extendedTimeUsed),  // This will show "17 mins" only
            'time_used_formatted' => $this->formatDuration($timeUsed),  // Regular format for original time
            // Room/Seat
            'room_seat_text' => $this->getRoomSeatTextClean($booking),
            // Currency
            'extension_total_amount_formatted' => $this->formatCurrency($extensionPayment->total_amount ?? $extendedAmount),
            'extension_amount_paid_formatted' => $this->formatCurrency($extensionPayment->amount_paid ?? 0),
            'extension_change_formatted' => $this->formatCurrency($extensionPayment->change ?? 0),
            // Extension pricing if exists
            'base_price_formatted' => $booking->extension ? $this->formatCurrency($booking->extension->base_price) : 'N/A',
            'extension_price_formatted' => $booking->extension ? $this->formatCurrency($booking->extension->extension_price) : 'N/A',
            'extension_total_formatted' => $booking->extension ? $this->formatCurrency($booking->extension->total_price) : 'N/A',
            // Calculated amounts
            'calculated_extension_amount' => $extendedAmount,
            'calculated_extension_amount_formatted' => $this->formatCurrency($extendedAmount),
            'has_extension' => $hasExtension,
            // Service pricing info
            'service_price_formatted' => $this->formatCurrency($servicePrice),
            'service_price' => $servicePrice,
            'service_time_duration' => $serviceTimeDuration,
            'fifteen_min_rate' => $fifteenMinRate,
            'hourly_service' => $hourlyServiceData,
            // Breakdown for table
            'breakdown' => $breakdown,
        ];

        // Get unpaid pay-later orders - CORRECTED QUERY
        $unpaidOrders = Order::where('booking_id', $booking->id)
            ->where('branch_id', $booking->branch_id)
            ->whereHas('payments', function ($query) {
                $query
                    ->where('payment_method', 3)  // 3 = pay-later
                    ->where('order_payment_status', 0);  // 0 = unpaid
            })
            ->with(['payments' => function ($query) {
                $query
                    ->where('payment_method', 3)
                    ->where('order_payment_status', 0);
            }, 'items.product', 'customer'])
            ->get();

        $totalUnpaidOrdersAmount = $unpaidOrders->sum(function ($order) {
            return $order
                ->payments
                ->where('payment_method', 3)
                ->where('order_payment_status', 0)
                ->sum('total_amount');
        });

        $hasUnpaidOrders = $unpaidOrders->count() > 0;

        // Calculate grand total (extension time + unpaid orders)
        $grandTotal = $extendedAmount + $totalUnpaidOrdersAmount;

        // Add to formattedData
        $formattedData['unpaid_orders_count'] = $unpaidOrders->count();
        $formattedData['total_unpaid_orders_amount_formatted'] = $this->formatCurrency($totalUnpaidOrdersAmount);
        $formattedData['grand_total_formatted'] = $this->formatCurrency($grandTotal);
        $formattedData['has_unpaid_orders'] = $hasUnpaidOrders;  // Use the variable

        return view('owner.booking.booking_list_payment.extension_payment_page', compact(
            'booking', 'extendedTimeUsed', 'timeUsed', 'totalTimeUsed', 'totalTimeDuration',
            'formattedData', 'extensionPayment', 'extendedAmount', 'hasExtension',
            'unpaidOrders', 'totalUnpaidOrdersAmount', 'grandTotal', 'hasUnpaidOrders'  // Add hasUnpaidOrders here
        ));
    }

    /**
     * Get 1-hour service from the same service category
     */
    private function getOneHourService($booking)
    {
        if (!$booking->serviceCategory) {
            return null;
        }

        // Find a service with time_duration = 1 hour in the same service category
        $oneHourService = ServiceName::where('service_category_id', $booking->serviceCategory->id)
            ->where('active', 1)
            ->where(function ($query) {
                $query
                    ->where('time_duration', 1)
                    ->orWhere('time_duration', '1 hour')
                    ->orWhere('time_duration', '1.0')
                    ->orWhere('time_duration', '1.00');
            })
            ->first();

        return $oneHourService;
    }

    /**
     * Calculate 15-min rate from 1-hour service
     */
    private function calculateFifteenMinRate($hourlyService)
    {
        if (!$hourlyService || !$hourlyService->price) {
            return 0;
        }

        $servicePrice = floatval($hourlyService->price);

        // Calculate 15-min rate: 1-hour service price divided by 4
        return $servicePrice / 4;
    }

    /**
     * Get extension breakdown for table display
     */
    private function getExtensionBreakdown($booking, $extendedTimeUsed, $extendedAmount, $fifteenMinRate)
    {
        if (!$booking || $extendedTimeUsed <= 0) {
            return [];
        }

        $bookedService = $booking->serviceName;
        if (!$bookedService || !$bookedService->price || !$bookedService->time_duration) {
            return [];
        }

        $packageHours = floatval($bookedService->time_duration);
        $packagePrice = floatval($bookedService->price);
        $extendedHours = $extendedTimeUsed / 60;

        $breakdown = [];

        // Pricing logic for breakdown using 15-min rate
        if ($extendedHours <= 1) {
            // Below or equal to 1 hour: charge based on 15-min increments
            $segments = ceil($extendedTimeUsed / 15);
            $amount = $segments * $fifteenMinRate;

            $breakdown[] = [
                'label' => 'Extension Time (15-min increments)',
                'time' => $this->formatExtensionTime($extendedTimeUsed),  // Use minutes-only format
                'price' => $this->formatCurrency($amount)
            ];
        } elseif ($extendedHours > 1 && $extendedHours <= $packageHours) {
            // Above 1 hour but within package: 15-min increments for entire duration
            $segments = ceil($extendedTimeUsed / 15);
            $amount = $segments * $fifteenMinRate;

            $breakdown[] = [
                'label' => 'Extension Time (15-min increments)',
                'time' => $this->formatExtensionTime($extendedTimeUsed),  // Use minutes-only format
                'price' => $this->formatCurrency($amount)
            ];
        } else {
            // Above package: package price + 15-min increments for extra time
            $breakdown[] = [
                'label' => 'Package Deal',
                'time' => $packageHours . ' hr' . ($packageHours != 1 ? 's' : ''),
                'price' => $this->formatCurrency($packagePrice)
            ];

            $minutesBeyondPackage = $extendedTimeUsed - ($packageHours * 60);
            if ($minutesBeyondPackage > 0) {
                $extraSegments = ceil($minutesBeyondPackage / 15);
                $extraAmount = $extraSegments * $fifteenMinRate;

                $breakdown[] = [
                    'label' => 'Extra Time (15-min increments)',
                    'time' => $this->formatExtensionTime($minutesBeyondPackage),  // Use minutes-only format
                    'price' => $this->formatCurrency($extraAmount)
                ];
            }
        }

        return $breakdown;
    }

    /**
     * Format duration to show only minutes when appropriate
     */
    protected function formatDurationMinutesOnly($minutes)
    {
        if (!$minutes || $minutes < 1)
            return '0 min';

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours === 0) {
            // Only minutes - show "17 mins"
            return $remainingMinutes . ' min' . ($remainingMinutes !== 1 ? 's' : '');
        } elseif ($remainingMinutes === 0) {
            // Only hours - show "2 hrs"
            return $hours . ' hr' . ($hours !== 1 ? 's' : '');
        } else {
            // Both hours and minutes - show "2 hrs : 17 mins" (WITH COLON like booking list)
            return $hours . ' hr' . ($hours !== 1 ? 's' : '') . ' : ' . $remainingMinutes . ' min' . ($remainingMinutes !== 1 ? 's' : '');
        }
    }

    /**
     * Calculate extended time amount with new pricing logic
     */
    private function calculateExtendedTimeAmountNew($booking, $extendedTimeUsed)
    {
        if (!$booking || $extendedTimeUsed <= 0) {
            return 0;
        }

        $bookedService = $booking->serviceName;
        if (!$bookedService || !$bookedService->price || !$bookedService->time_duration) {
            return 0;
        }

        $packageHours = floatval($bookedService->time_duration);
        $packagePrice = floatval($bookedService->price);
        $extendedHours = $extendedTimeUsed / 60;

        // Get the 1-hour service to calculate 15-min rate
        $hourlyService = $this->getOneHourService($booking);
        $fifteenMinRate = $this->calculateFifteenMinRate($hourlyService);

        // New pricing logic using only 15-min rate
        if ($extendedHours <= $packageHours) {
            // Within or equal to package: charge based on 15-min increments
            $segments = ceil($extendedTimeUsed / 15);
            return $segments * $fifteenMinRate;
        } else {
            // Above package: package price + 15-min increments for extra time
            $minutesBeyondPackage = $extendedTimeUsed - ($packageHours * 60);
            $extraSegments = ceil($minutesBeyondPackage / 15);
            $extraAmount = $extraSegments * $fifteenMinRate;
            return $packagePrice + $extraAmount;
        }
    }

    /**
     * Update main payment (payment_category = 1)
     */
    public function updateMainPayment(Request $request)
{
    $request->validate([
        'booking_id' => 'required|exists:bookings,id',
        'payment_method' => 'required|in:gcash,cash,debit',
        'amount_paid' => 'required|numeric|min:0',
        'note' => 'nullable|string|max:1000',
    ]);

    $booking = Booking::with('payment', 'branch', 'customerAccount')->findOrFail($request->booking_id);

    // Calculate main booking amount
    $mainAmount = $this->calculateBookingAmount($booking);

    // Get unpaid orders
    $unpaidOrders = Order::where('booking_id', $booking->id)
        ->where('branch_id', $booking->branch_id)
        ->whereHas('payments', function ($query) {
            $query
                ->where('payment_method', 3)
                ->where('order_payment_status', 0);
        })
        ->with(['payments' => function ($query) {
            $query
                ->where('payment_method', 3)
                ->where('order_payment_status', 0);
        }])
        ->get();

    $totalUnpaidOrdersAmount = $unpaidOrders->sum(function ($order) {
        return $order
            ->payments
            ->where('payment_method', 3)
            ->where('order_payment_status', 0)
            ->sum('total_amount');
    });

    $grandTotal = $mainAmount + $totalUnpaidOrdersAmount;

    // Create or find main payment (payment_category = 1)
    $mainPayment = BookingPayment::where('booking_id', $booking->id)
        ->where('payment_category', 1)
        ->first();

    if (!$mainPayment) {
        $mainPayment = new BookingPayment();
        $mainPayment->booking_id = $booking->id;
        $mainPayment->branch_id = $booking->branch_id;
        $mainPayment->customer_account_id = $booking->customer_account_id;
        $mainPayment->payment_category = 1;
        $mainPayment->payment_status = 2;
        $mainPayment->active = 1;

        // Add creation data only if it doesn't exist
        if (!$mainPayment->created_by) {
            $mainPayment->created_by = Auth::guard('owner')->id();
            $mainPayment->created_by_type = 'owner';
            $mainPayment->date_created = now();
        }

        // Initialize empty notes array for new payment
        $mainPayment->notes = [];
    }

    // Set payment amounts
    $mainPayment->total_amount = $grandTotal;

    $mainPayment->payment_method = match ($request->payment_method) {
        'cash' => 0,
        'gcash' => 1,
        'debit' => 2,
    };

    $paymentSuccess = false;

    if ($request->payment_method === 'cash') {
        $mainPayment->amount_paid = $request->amount_paid;
        $mainPayment->change = max(0, $mainPayment->amount_paid - $mainPayment->total_amount);

        if ($mainPayment->amount_paid >= $mainPayment->total_amount) {
            $mainPayment->payment_status = 1;
            $paymentSuccess = true;

            // UPDATE ORDER PAYMENTS if main payment is successful
            $this->updateOrderPayments($unpaidOrders, $request->payment_method);
        } else {
            $mainPayment->payment_status = 2;
        }
    } else {
        // For GCash and Debit, amount paid should equal total amount
        $mainPayment->amount_paid = $grandTotal;
        $mainPayment->change = 0;
        $mainPayment->payment_status = 1;
        $paymentSuccess = true;

        // UPDATE ORDER PAYMENTS for non-cash payments
        $this->updateOrderPayments($unpaidOrders, $request->payment_method);
    }

    $mainPayment->payment_date = now();

    // Add notes for GCash and Debit payments
    if (in_array($request->payment_method, ['gcash', 'debit']) && $request->filled('note')) {
        $this->addPaymentNote($mainPayment, $request->note);
    }

    // Payment audit trail
    if (!is_null($mainPayment->updated_by)) {
        $mainPayment->last_updated_by = $mainPayment->updated_by;
        $mainPayment->last_updated_by_type = $mainPayment->updated_by_type;
        $mainPayment->last_date_updated = $mainPayment->date_updated;
    }
    $mainPayment->updated_by = Auth::guard('owner')->id();
    $mainPayment->updated_by_type = 'owner';
    $mainPayment->date_updated = now();

    // If this is a new payment and created_by wasn't set, set it now
    if (!$mainPayment->created_by) {
        $mainPayment->created_by = Auth::guard('owner')->id();
        $mainPayment->created_by_type = 'owner';
        $mainPayment->date_created = now();
    }

    // UPDATE BOOKING STATUS TO COMPLETED (4) IF PAYMENT IS SUCCESSFUL
    if ($paymentSuccess) {
        // Check if extension payment also exists and is paid, or if no extension is needed
        $extensionPayment = BookingPayment::where('booking_id', $booking->id)
            ->where('payment_category', 0)
            ->first();
        
        $hasUnpaidExtension = $extensionPayment && $extensionPayment->payment_status != 1;
        $hasExtensionTime = ($booking->extended_date_start || $booking->extended_date_end || 
                            $booking->extended_start_time || $booking->extended_end_time);
        
        // Only update booking status to completed if:
        // 1. No extension time exists, OR
        // 2. Extension exists AND extension payment is already paid
        if (!$hasExtensionTime || ($hasExtensionTime && !$hasUnpaidExtension)) {
            // Booking audit trail before status change
            if (!is_null($booking->updated_by)) {
                $booking->last_updated_by = $booking->updated_by;
                $booking->last_updated_by_type = $booking->updated_by_type;
                $booking->last_date_updated = $booking->date_updated;
            }
            
            $booking->booking_status = 4; // Completed
            $booking->updated_by = Auth::guard('owner')->id();
            $booking->updated_by_type = 'owner';
            $booking->date_updated = now();
        }
    }

    // Booking audit trail (for non-status changes)
    if (!is_null($booking->updated_by) && $booking->booking_status != 4) {
        $booking->last_updated_by = $booking->updated_by;
        $booking->last_updated_by_type = $booking->updated_by_type;
        $booking->last_date_updated = $booking->date_updated;
    }
    $booking->updated_by = Auth::guard('owner')->id();
    $booking->updated_by_type = 'owner';
    $booking->date_updated = now();

    // If booking doesn't have creation data, set it
    if (!$booking->created_by) {
        $booking->created_by = Auth::guard('owner')->id();
        $booking->created_by_type = 'owner';
        $booking->date_created = now();
    }

    $mainPayment->save();
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
    Notification::send($owners, new BookingListPaymentNotification(
        $booking,
        $bookingBranch,
        $customer,
        $actor,
        'main_payment'
    ));

    $staffMembers = StaffAccount::where('branch_id', $booking->branch_id)
        ->where('owner_account_id', $owner->id)
        ->where('active', 1)
        ->get();

    // Send notification
    Notification::send($staffMembers, new BookingListPaymentStaffNotification(
        $booking,
        $bookingBranch,
        $customer,
        $actor,
        'main_payment'
    ));

    // Send notification
    Notification::send($customer, new BookingListPaymentCustomerNotification(
        $booking,
        $bookingBranch,
        $customer,
        $actor,
        'main_payment'
    ));

    return redirect()
        ->route('sub_one.booking_lists.showBookingList', ['brn' => $booking->booking_ref_no])
        ->with('success', 'Main payment updated successfully!');
}

/**
 * Update extension payment with notes support
 */
public function updateExtensionPayment(Request $request)
{
    $request->validate([
        'booking_id' => 'required|exists:bookings,id',
        'payment_method' => 'required|in:gcash,cash,debit',
        'amount_paid' => 'required|numeric|min:0',
        'note' => 'nullable|string|max:1000',
    ]);

    $booking = Booking::with('extensionPayment', 'branch', 'customerAccount')->findOrFail($request->booking_id);

    // Load checkin data for extended time
    $checkin = CustomerCheckin::where('booking_id', $booking->id)->first();
    $extendedTimeUsed = $checkin->extended_time_used ?? 0;

    // Calculate extended time amount
    $extendedAmount = $this->calculateExtendedTimeAmountNew($booking, $extendedTimeUsed);
    $hasExtension = $extendedAmount > 0;

    // Get unpaid orders
    $unpaidOrders = Order::where('booking_id', $booking->id)
        ->where('branch_id', $booking->branch_id)
        ->whereHas('payments', function ($query) {
            $query
                ->where('payment_method', 3)
                ->where('order_payment_status', 0);
        })
        ->with(['payments' => function ($query) {
            $query
                ->where('payment_method', 3)
                ->where('order_payment_status', 0);
        }])
        ->get();

    $totalUnpaidOrdersAmount = $unpaidOrders->sum(function ($order) {
        return $order
            ->payments
            ->where('payment_method', 3)
            ->where('order_payment_status', 0)
            ->sum('total_amount');
    });

    $grandTotal = $extendedAmount + $totalUnpaidOrdersAmount;

    // Only proceed if there's an extension to charge for
    if (!$hasExtension || $extendedAmount <= 0) {
        return redirect()->back()->with('error', 'No extension time found to charge.');
    }

    // Create or find extension payment
    $extensionPayment = BookingPayment::where('booking_id', $booking->id)
        ->where('payment_category', 0)
        ->first();

    if (!$extensionPayment) {
        $extensionPayment = new BookingPayment();
        $extensionPayment->booking_id = $booking->id;
        $extensionPayment->branch_id = $booking->branch_id;
        $extensionPayment->customer_account_id = $booking->customer_account_id;
        $extensionPayment->payment_category = 0;
        $extensionPayment->payment_status = 2;
        $extensionPayment->payment_date = now();
        $extensionPayment->active = 1;

        // Add creation data only if it doesn't exist
        if (!$extensionPayment->created_by) {
            $extensionPayment->created_by = Auth::guard('owner')->id();
            $extensionPayment->created_by_type = 'owner';
            $extensionPayment->date_created = now();
        }

        // Initialize empty notes array for new payment
        $extensionPayment->notes = [];
    }

    // Set payment amounts
    $extensionPayment->total_amount = $grandTotal;

    $extensionPayment->payment_method = match ($request->payment_method) {
        'cash' => 0,
        'gcash' => 1,
        'debit' => 2,
    };

    $paymentSuccess = false;

    if ($request->payment_method === 'cash') {
        $extensionPayment->amount_paid = $request->amount_paid;
        $extensionPayment->change = max(0, $extensionPayment->amount_paid - $extensionPayment->total_amount);

        if ($extensionPayment->amount_paid >= $extensionPayment->total_amount) {
            $extensionPayment->payment_status = 1;
            $paymentSuccess = true;

            // UPDATE ORDER PAYMENTS if extension payment is successful
            $this->updateOrderPayments($unpaidOrders, $request->payment_method);
        } else {
            $extensionPayment->payment_status = 2;
        }
    } else {
        // For GCash and Debit, amount paid should equal total amount
        $extensionPayment->amount_paid = $grandTotal;
        $extensionPayment->change = 0;
        $extensionPayment->payment_status = 1;
        $paymentSuccess = true;

        // UPDATE ORDER PAYMENTS for non-cash payments
        $this->updateOrderPayments($unpaidOrders, $request->payment_method);
    }

    $extensionPayment->payment_date = now();

    // Add notes for GCash and Debit payments
    if (in_array($request->payment_method, ['gcash', 'debit']) && $request->filled('note')) {
        $this->addPaymentNote($extensionPayment, $request->note);
    }

    // Payment audit trail
    if (!is_null($extensionPayment->updated_by)) {
        $extensionPayment->last_updated_by = $extensionPayment->updated_by;
        $extensionPayment->last_updated_by_type = $extensionPayment->updated_by_type;
        $extensionPayment->last_date_updated = $extensionPayment->date_updated;
    }
    $extensionPayment->updated_by = Auth::guard('owner')->id();
    $extensionPayment->updated_by_type = 'owner';
    $extensionPayment->date_updated = now();

    // If this is a new payment and created_by wasn't set, set it now
    if (!$extensionPayment->created_by) {
        $extensionPayment->created_by = Auth::guard('owner')->id();
        $extensionPayment->created_by_type = 'owner';
        $extensionPayment->date_created = now();
    }

    // UPDATE BOOKING STATUS TO COMPLETED (4) IF EXTENSION PAYMENT IS SUCCESSFUL
    if ($paymentSuccess) {
        // Check if main payment also exists and is paid
        $mainPayment = BookingPayment::where('booking_id', $booking->id)
            ->where('payment_category', 1)
            ->first();
        
        $hasUnpaidMain = $mainPayment && $mainPayment->payment_status != 1;
        
        // Only update booking status to completed if main payment is already paid
        if (!$hasUnpaidMain) {
            // Booking audit trail before status change
            if (!is_null($booking->updated_by)) {
                $booking->last_updated_by = $booking->updated_by;
                $booking->last_updated_by_type = $booking->updated_by_type;
                $booking->last_date_updated = $booking->date_updated;
            }
            
            $booking->booking_status = 4; // Completed
            $booking->updated_by = Auth::guard('owner')->id();
            $booking->updated_by_type = 'owner';
            $booking->date_updated = now();
        }
    }

    // Booking audit trail (for non-status changes)
    if (!is_null($booking->updated_by) && $booking->booking_status != 4) {
        $booking->last_updated_by = $booking->updated_by;
        $booking->last_updated_by_type = $booking->updated_by_type;
        $booking->last_date_updated = $booking->date_updated;
    }
    $booking->updated_by = Auth::guard('owner')->id();
    $booking->updated_by_type = 'owner';
    $booking->date_updated = now();

    // If booking doesn't have creation data, set it
    if (!$booking->created_by) {
        $booking->created_by = Auth::guard('owner')->id();
        $booking->created_by_type = 'owner';
        $booking->date_created = now();
    }

    $extensionPayment->save();
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
    Notification::send($owners, new BookingListPaymentNotification(
        $booking,
        $bookingBranch,
        $customer,
        $actor,
        'extension_payment'
    ));

    $staffMembers = StaffAccount::where('branch_id', $booking->branch_id)
        ->where('owner_account_id', $owner->id)
        ->where('active', 1)
        ->get();

    // Send notification
    Notification::send($staffMembers, new BookingListPaymentStaffNotification(
        $booking,
        $bookingBranch,
        $customer,
        $actor,
        'extension_payment'
    ));

    // Send notification
    Notification::send($customer, new BookingListPaymentCustomerNotification(
        $booking,
        $bookingBranch,
        $customer,
        $actor,
        'extension_payment'
    ));

    return redirect()
        ->route('sub_one.booking_lists.showBookingList', ['brn' => $booking->booking_ref_no])
        ->with('success', 'Extension payment updated successfully!');
}

/**
 * Add note to payment
 */
private function addPaymentNote($payment, $note)
{
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
        'content' => $note,
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
}

    /**
     * Update order payments when main/extension payment is processed
     */
    private function updateOrderPayments($unpaidOrders, $paymentMethod)
    {
        $notifiedOrders = [];  // Track orders we've notified for

        foreach ($unpaidOrders as $order) {
            // Get the unpaid pay-later payments for this order
            $unpaidPayments = $order
                ->payments
                ->where('payment_method', 3)
                ->where('order_payment_status', 0);

            $hasProcessedPayment = false;

            foreach ($unpaidPayments as $payment) {
                // Update payment status to paid
                $payment->order_payment_status = 1;  // 1 = paid
                $payment->payment_method = match ($paymentMethod) {
                    'cash' => 0,
                    'gcash' => 1,
                    'debit' => 2,
                    default => $payment->payment_method
                };
                $payment->amount_paid = $payment->total_amount;
                $payment->change = 0;
                $payment->payment_date = now();

                // Payment audit trail
                if (!is_null($payment->updated_by)) {
                    $payment->last_updated_by = $payment->updated_by;
                    $payment->last_updated_by_type = $payment->updated_by_type;
                    $payment->last_date_updated = $payment->date_updated;
                }
                $payment->updated_by = Auth::guard('owner')->id();
                $payment->updated_by_type = 'owner';
                $payment->date_updated = now();

                // If payment doesn't have creation data, set it
                if (!$payment->created_by) {
                    $payment->created_by = Auth::guard('owner')->id();
                    $payment->created_by_type = 'owner';
                    $payment->date_created = now();
                }

                $payment->save();
                $hasProcessedPayment = true;
            }

            // Update order status if we processed any payments
            if ($hasProcessedPayment) {
                $order->order_status = 1;

                // Order audit trail
                if (!is_null($order->updated_by)) {
                    $order->last_updated_by = $order->updated_by;
                    $order->last_updated_by_type = $order->updated_by_type;
                    $order->last_date_updated = $order->date_updated;
                }
                $order->updated_by = Auth::guard('owner')->id();
                $order->updated_by_type = 'owner';
                $order->date_updated = now();

                // If order doesn't have creation data, set it
                if (!$order->created_by) {
                    $order->created_by = Auth::guard('owner')->id();
                    $order->created_by_type = 'owner';
                    $order->date_created = now();
                }

                $order->save();

                // Send notification for this order (only once per order)
                $this->sendOrderPaymentNotifications($order, $paymentMethod);
            }
        }
    }
    
    /**
 * Show order payment page for pay-later orders
 */
public function showOrderPaymentPage($booking_uuid)
{
    $owner = Auth::guard('owner')->user();
    $ownerId = $owner->id;

    // Find the booking by UUID
    $booking = Booking::where('uuid', $booking_uuid)->firstOrFail();

    // Security check
    $branchIds = Branch::where('owner_account_id', $ownerId)->pluck('id');
    if (!$branchIds->contains($booking->branch_id)) {
        abort(403, 'Unauthorized access to this booking.');
    }

    // Load necessary relationships
    $booking->load([
        'customerAccount',
        'branch',
        'payment',
        'extensionPayment',
    ]);

    // Get unpaid pay-later orders
    $unpaidOrders = Order::where('booking_id', $booking->id)
        ->where('branch_id', $booking->branch_id)
        ->whereHas('payments', function ($query) {
            $query
                ->where('payment_method', 3)  // 3 = pay-later
                ->where('order_payment_status', 0);  // 0 = unpaid
        })
        ->with(['payments' => function ($query) {
            $query
                ->where('payment_method', 3)
                ->where('order_payment_status', 0);
        }, 'items.product', 'customer'])
        ->get();

    $totalUnpaidOrdersAmount = $unpaidOrders->sum(function ($order) {
        return $order
            ->payments
            ->where('payment_method', 3)
            ->where('order_payment_status', 0)
            ->sum('total_amount');
    });

    $hasUnpaidOrders = $unpaidOrders->count() > 0;

    if (!$hasUnpaidOrders) {
        return redirect()
            ->route('sub_one.booking_lists.showBookingList')
            ->with('error', 'No unpaid orders found for this booking.');
    }

    return view('owner.booking.booking_list_payment.order_payment_page', compact(
        'booking',
        'unpaidOrders',
        'totalUnpaidOrdersAmount'
    ));
}

/**
 * Update order payment
 */
public function updateOrderPayment(Request $request)
{
    $request->validate([
        'booking_id' => 'required|exists:bookings,id',
        'payment_method' => 'required|in:gcash,cash,debit',
        'amount_paid' => 'required|numeric|min:0',
        'note' => 'nullable|string|max:1000',
    ]);

    $booking = Booking::with('branch', 'customerAccount')->findOrFail($request->booking_id);

    // Get unpaid orders
    $unpaidOrders = Order::where('booking_id', $booking->id)
        ->where('branch_id', $booking->branch_id)
        ->whereHas('payments', function ($query) {
            $query
                ->where('payment_method', 3)
                ->where('order_payment_status', 0);
        })
        ->with(['payments' => function ($query) {
            $query
                ->where('payment_method', 3)
                ->where('order_payment_status', 0);
        }])
        ->get();

    $totalUnpaidOrdersAmount = $unpaidOrders->sum(function ($order) {
        return $order
            ->payments
            ->where('payment_method', 3)
            ->where('order_payment_status', 0)
            ->sum('total_amount');
    });

    $paymentSuccess = false;
    $allPaymentsProcessed = true;

    // Process each unpaid order
    foreach ($unpaidOrders as $order) {
        $unpaidPayments = $order->payments
            ->where('payment_method', 3)
            ->where('order_payment_status', 0);

        foreach ($unpaidPayments as $payment) {
            // Update payment status to paid
            $payment->order_payment_status = 1;  // 1 = paid
            $payment->payment_method = match ($request->payment_method) {
                'cash' => 0,
                'gcash' => 1,
                'debit' => 2,
                default => $payment->payment_method
            };
            $payment->amount_paid = $payment->total_amount;
            $payment->change = max(0, $request->amount_paid - $totalUnpaidOrdersAmount);
            $payment->payment_date = now();

            // Add note if provided
            if ($request->filled('note')) {
                $existingNotes = $payment->notes ?? [];
                if (is_string($existingNotes)) {
                    $existingNotes = json_decode($existingNotes, true) ?: [];
                }
                $newNote = [
                    'content' => $request->note,
                    'added_by_type' => 'Owner',
                    'added_at' => now()->toDateTimeString(),
                ];
                array_unshift($existingNotes, $newNote);
                $payment->notes = $existingNotes;
            }

            // Payment audit trail
            if (!is_null($payment->updated_by)) {
                $payment->last_updated_by = $payment->updated_by;
                $payment->last_updated_by_type = $payment->updated_by_type;
                $payment->last_date_updated = $payment->date_updated;
            }
            $payment->updated_by = Auth::guard('owner')->id();
            $payment->updated_by_type = 'owner';
            $payment->date_updated = now();

            if (!$payment->created_by) {
                $payment->created_by = Auth::guard('owner')->id();
                $payment->created_by_type = 'owner';
                $payment->date_created = now();
            }

            $payment->save();
            $paymentSuccess = true;
        }

        // Update order status
        $order->order_status = 1;  // 1 = paid/completed
        
        // Order audit trail
        if (!is_null($order->updated_by)) {
            $order->last_updated_by = $order->updated_by;
            $order->last_updated_by_type = $order->updated_by_type;
            $order->last_date_updated = $order->date_updated;
        }
        $order->updated_by = Auth::guard('owner')->id();
        $order->updated_by_type = 'owner';
        $order->date_updated = now();

        if (!$order->created_by) {
            $order->created_by = Auth::guard('owner')->id();
            $order->created_by_type = 'owner';
            $order->date_created = now();
        }

        $order->save();
    }

    // UPDATE BOOKING STATUS TO COMPLETED (4) IF ORDER PAYMENT IS SUCCESSFUL
    if ($paymentSuccess) {
        // Check if main payment and extension payment are also paid or not required
        $mainPayment = BookingPayment::where('booking_id', $booking->id)
            ->where('payment_category', 1)
            ->first();
        
        $extensionPayment = BookingPayment::where('booking_id', $booking->id)
            ->where('payment_category', 0)
            ->first();
        
        $hasUnpaidMain = $mainPayment && $mainPayment->payment_status != 1;
        $hasUnpaidExtension = $extensionPayment && $extensionPayment->payment_status != 1;
        
        $hasExtensionTime = ($booking->extended_date_start || $booking->extended_date_end || 
                            $booking->extended_start_time || $booking->extended_end_time);
        
        // Check if all required payments are paid
        $allPaymentsPaid = true;
        
        // Check main payment (always required if booking exists)
        if ($hasUnpaidMain) {
            $allPaymentsPaid = false;
        }
        
        // Check extension payment (only if extension time exists)
        if ($hasExtensionTime && $hasUnpaidExtension) {
            $allPaymentsPaid = false;
        }
        
        // Update booking status to completed if all required payments are paid
        if ($allPaymentsPaid) {
            // Booking audit trail before status change
            if (!is_null($booking->updated_by)) {
                $booking->last_updated_by = $booking->updated_by;
                $booking->last_updated_by_type = $booking->updated_by_type;
                $booking->last_date_updated = $booking->date_updated;
            }
            
            $booking->booking_status = 4; // Completed
            $booking->updated_by = Auth::guard('owner')->id();
            $booking->updated_by_type = 'owner';
            $booking->date_updated = now();
            
            // If booking doesn't have creation data, set it
            if (!$booking->created_by) {
                $booking->created_by = Auth::guard('owner')->id();
                $booking->created_by_type = 'owner';
                $booking->date_created = now();
            }
            
            $booking->save();
        } else {
            // Still save booking even if status not updated
            $booking->save();
        }
    }

    // Send order payment notifications
    foreach ($unpaidOrders as $order) {
        $this->sendOrderPaymentNotifications($order, $request->payment_method);
    }

    return redirect()
        ->route('sub_one.booking_lists.showBookingList', ['brn' => $booking->booking_ref_no])
        ->with('success', 'Order payment processed successfully!');
}

    /**
     * Send notifications for order payment
     */
    private function sendOrderPaymentNotifications($order, $paymentMethod)
    {
        try {
            $actor = Auth::guard('owner')->user();
            $branch = Branch::find($order->branch_id);
            $customer = CustomerAccount::find($order->customer_account_id);

            if (!$branch || !$customer) {
                return;
            }

            // Notify Owner
            $owners = OwnerAccount::where('id', $actor->id)->get();

            Notification::send($owners, new OrderPaymentNotification(
                $order,
                $branch,
                $customer,
                $actor,
                'order_payment'
            ));

            // Notify Staff in the same branch
            $staffMembers = StaffAccount::where('branch_id', $order->branch_id)
                ->where('owner_account_id', $actor->id)
                ->where('active', 1)
                ->get();

            Notification::send($staffMembers, new OrderPaymentStaffNotification(
                $order,
                $branch,
                $customer,
                $actor,
                'order_payment'
            ));

            Notification::send($customer, new OrderPaymentCustomerNotification(
                $order,
                $branch,
                $customer,
                $actor,
                'order_payment'
            ));
        } catch (\Exception $e) {
            // Log error but don't break the payment process
            \Log::error('Failed to send order payment notifications: ' . $e->getMessage());
        }
    }

    /**
     * Get room/seat text for display
     */
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

    private function calculateBookingAmount($booking)
    {
        if (!$booking || !$booking->serviceName) {
            return 0;
        }

        $packageHours = floatval($booking->serviceName->time_duration);
        $packagePrice = floatval($booking->serviceName->price);

        if ($packageHours === 0.0) {
            return 0;  // Avoid division by zero
        }

        $hourlyRate = $packagePrice / $packageHours;

        $durationMinutes = $this->calculateBookingDurationInMinutes(
            $booking->start_time,
            $booking->end_time
        );

        if ($durationMinutes <= 0) {
            return 0;
        }

        $packageMinutes = $packageHours * 60;

        if ($durationMinutes <= $packageMinutes) {
            // Within package - pay full package price
            return $packagePrice;
        } else {
            // Exceeded package - pay package + extra time
            $extraMinutes = $durationMinutes - $packageMinutes;
            $extraSegments = ceil($extraMinutes / 15.0);
            $extraAmount = $extraSegments * ($hourlyRate / 4.0);
            return floatval($packagePrice + $extraAmount);
        }
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
            // Only minutes - show "17 mins" (NO "0 hrs :" prefix)
            return $remainingMinutes . ' min' . ($remainingMinutes !== 1 ? 's' : '');
        } elseif ($remainingMinutes === 0) {
            // Only hours - show "2 hrs"
            return $hours . ' hr' . ($hours !== 1 ? 's' : '');
        } else {
            // Both hours and minutes - show "2 hrs : 17 mins"
            return $hours . ' hr' . ($hours !== 1 ? 's' : '') . ' : ' . $remainingMinutes . ' min' . ($remainingMinutes !== 1 ? 's' : '');
        }
    }

    /**
     * Special format for extension time - ALWAYS show minutes only
     */
    protected function formatExtensionTime($minutes)
    {
        if (!$minutes || $minutes < 1)
            return '0 mins';

        return $minutes . ' min' . ($minutes !== 1 ? 's' : '');
    }

    /**
     * Clean duration format without "0 hrs"
     */
    protected function formatDurationClean($minutes)
    {
        if (!$minutes || $minutes < 1)
            return '0 mins';

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours === 0) {
            return $remainingMinutes . ' min' . ($remainingMinutes !== 1 ? 's' : '');
        } elseif ($remainingMinutes === 0) {
            return $hours . ' hr' . ($hours !== 1 ? 's' : '');
        } else {
            // Don't show "0 hrs" - just show the hours and minutes
            return $hours . ' hr' . ($hours !== 1 ? 's' : '') . ' : ' . $remainingMinutes . ' min' . ($remainingMinutes !== 1 ? 's' : '');
        }
    }

    protected function calculateBookingDurationInMinutes($startTime, $endTime)
    {
        if (!$startTime || !$endTime)
            return 0;

        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        return $start->diffInMinutes($end);
    }

    /**
     * Clean room/seat text display
     */
    protected function getRoomSeatTextClean($booking)
    {
        $seat = $booking->seat;
        if (!$seat) {
            return 'N/A';
        }

        $parts = [];

        // Only add room if room_no has value
        if (!empty($seat->room_no) && $seat->room_no !== null && $seat->room_no !== '') {
            $parts[] = 'Room ' . $seat->room_no;
        }

        // Only add seat if seat_no has value
        if (!empty($seat->seat_no) && $seat->seat_no !== null && $seat->seat_no !== '') {
            $parts[] = 'Seat ' . $seat->seat_no;
        }

        return count($parts) > 0 ? implode(' / ', $parts) : 'N/A';
    }
}
