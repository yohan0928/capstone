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
use App\Models\StaffAccount;
use App\Notifications\Customer\ScanQrCodeBookingCustomerNotification;
use App\Notifications\Owner\ScanQrCodeBookingNotification;
use App\Notifications\Staff\ScanQrCodeBookingStaffNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ScanQrCodeBookingController extends Controller
{
    public function showQrCodeBookingScanner()
    {
        return view('owner.booking.scan_qr_code_booking');
    }

    /**
     * Redirect to POS with customer and booking information
     */
    public function redirectToPos(Request $request)
    {
        try {
            $bookingId = $request->get('booking_id');
            $checkinId = $request->get('checkin_id');
            
            if (!$bookingId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking ID is required'
                ], 400);
            }

            $booking = Booking::with(['customerAccount', 'branch'])->find($bookingId);
            
            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found'
                ], 404);
            }

            // Build POS URL with parameters
            $posUrl = route('sub_one.pos.index');
            $params = [];

            // Add customer UUID
            if ($booking->customerAccount && $booking->customerAccount->uuid) {
                $params['cust'] = $booking->customerAccount->uuid;
            }

            // Add booking UUID
            if ($booking->uuid) {
                $params['bkg'] = $booking->uuid;
            }

            // Add checkin UUID if available
            if ($checkinId) {
                $checkin = CustomerCheckin::where('id', $checkinId)->where('active', 1)->first();
                if ($checkin && $checkin->uuid) {
                    $params['chk'] = $checkin->uuid;
                }
            } else {
                // Try to find an active checkin for this booking
                $checkin = CustomerCheckin::where('booking_id', $booking->id)
                    ->where('active', 1)
                    ->first();
                if ($checkin && $checkin->uuid) {
                    $params['chk'] = $checkin->uuid;
                }
            }

            // Add branch UUID
            if ($booking->branch && $booking->branch->uuid) {
                $params['brn'] = $booking->branch->uuid;
            }

            // Add booking reference
            if ($booking->booking_ref_no) {
                $params['ref'] = $booking->booking_ref_no;
            }

            // Build the full URL
            if (!empty($params)) {
                $posUrl .= '?' . http_build_query($params);
            }

            return response()->json([
                'success' => true,
                'redirect_url' => $posUrl,
                'message' => 'Redirecting to POS...'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in redirectToPos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error redirecting to POS: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getBookingByBookingRefNo(Request $request)
    {
        try {
            $bookingRef = $request->get('booking_ref');

            if (!$bookingRef) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking reference is required'
                ], 400);
            }

            $booking = Booking::with([
                'customerAccount',
                'branch',
                'serviceCategory',
                'serviceName',
                'seat',
                'customerCheckin',
                'payment',
                'extensionPayment'
            ])->where('booking_ref_no', $bookingRef)->first();

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found'
                ], 404);
            }

            // Check for unpaid pay-later orders (order_payment_status = 0)
            $unpaidOrdersCount = Order::where('booking_id', $booking->id)
                ->where('branch_id', $booking->branch_id)
                ->whereHas('payments', function ($query) {
                    $query->where('order_payment_status', 0);
                })
                ->count();

            // Check if there are any unpaid order payments
            $hasUnpaidOrders = $unpaidOrdersCount > 0;

            // Get the active checkin record
            $checkin = $booking->customerCheckin->where('active', 1)->first();
            
            // IMPORTANT: Check if checkin_status is 1 (checked_in) even if the checkin record exists
            $isCheckedIn = false;
            $checkinId = null;
            $checkinUuid = null;
            $checkinStatus = null;
            
            if ($checkin) {
                $checkinId = $checkin->id;
                $checkinUuid = $checkin->uuid;
                $checkinStatus = $checkin->checkin_status;
                $isCheckedIn = ($checkin->checkin_status == 1);
            }

            $customerAccount = $booking->customerAccount;
            $branch = $booking->branch;
            $serviceCategory = $booking->serviceCategory;
            $serviceName = $booking->serviceName;
            $seat = $booking->seat;

            // Get main payment (payment_category = 1)
            $mainPayment = BookingPayment::where('booking_id', $booking->id)
                ->where('payment_category', 1)
                ->where('active', 1)
                ->first();

            // Get extended payment if exists (payment_category = 0)
            $extendedPayment = BookingPayment::where('booking_id', $booking->id)
                ->where('payment_category', 0)
                ->where('active', 1)
                ->first();

            // Check if booking has extended schedule
            $hasExtendedSchedule = $booking->extended_date_start ||
                $booking->extended_date_end ||
                $booking->extended_start_time ||
                $booking->extended_end_time;

            // Get the latest payment for display (extended payment takes priority)
            $displayPayment = $extendedPayment ?? $mainPayment;

            // ================================================================
            // QR CODE VALIDITY - For informational display only
            // ================================================================
            $isQrCodeValid = false;
            $validityMessage = '';
            $validityDetails = [];

            // Check 1: booking_status must be 1 (booked)
            $isBookingStatusValid = ($booking->booking_status == 1);
            
            // Check 2: checkin_status must be 1 (checked_in)
            $isCheckinStatusValid = $isCheckedIn;
            
            // Check 3: main payment_status must be 2 (unpaid) OR extension payment_status must be 2 (unpaid)
            $isMainPaymentUnpaid = $mainPayment && $mainPayment->payment_status == 2;
            $isExtensionPaymentUnpaid = $extendedPayment && $extendedPayment->payment_status == 2;
            $isPaymentUnpaid = $isMainPaymentUnpaid || $isExtensionPaymentUnpaid;
            
            // Check 4: order_payment_status must be 0 (unpaid) - only if there are orders
            $areOrdersUnpaid = true;
            if ($unpaidOrdersCount > 0) {
                $areOrdersUnpaid = $hasUnpaidOrders;
            }

            // Determine if QR code is valid based on ALL conditions
            if ($isBookingStatusValid && $isCheckinStatusValid && $isPaymentUnpaid && $areOrdersUnpaid) {
                $isQrCodeValid = true;
                $validityMessage = 'QR Code is valid. You may proceed with ordering.';
            } else {
                $validityMessage = 'QR Code is not valid for ordering.';
                if (!$isBookingStatusValid) {
                    $validityDetails[] = 'Booking status is not "Booked" (Status: ' . $booking->booking_status . ')';
                }
                if (!$isCheckinStatusValid) {
                    $validityDetails[] = 'Customer is not checked in (Status: ' . ($checkin ? $checkin->checkin_status : 'No checkin') . ')';
                }
                if (!$isPaymentUnpaid) {
                    $validityDetails[] = 'All payments have been settled (Main: ' . ($mainPayment ? $mainPayment->payment_status : 'None') . ', Extension: ' . ($extendedPayment ? $extendedPayment->payment_status : 'None') . ')';
                }
                if (!$areOrdersUnpaid) {
                    $validityDetails[] = 'Orders have been paid already.';
                }
            }

            // ================================================================
            // ORDER ELIGIBILITY - Determines if Order button should show
            // Order button shows if:
            // 1. checkin_status = 1 (checked_in)
            // ================================================================
            $isBooked = $booking->booking_status == 1;
            $isOrderEligible = $isCheckedIn || $isBooked;
            
            // Order button shows if customer is active/checked-in
            $showOrderButton = $isCheckedIn;

            // Check for booking expiration (time passed) - FOR DISPLAY ONLY
            $appTimezone = 'Asia/Manila';
            $now = Carbon::now($appTimezone);
            $isBookingExpired = false;
            $bookingDate = null;

            // Determine which date to use for expiration check
            $displayDateStart = $hasExtendedSchedule ? 
                ($booking->extended_date_start ?? $booking->date_start) : 
                $booking->date_start;

            $displayDateEnd = $hasExtendedSchedule ?
                ($booking->extended_date_end ?? $booking->date_end) :
                $booking->date_end;

            $displayStartTime = $hasExtendedSchedule ?
                ($booking->extended_start_time ?? $booking->start_time) :
                $booking->start_time;

            $displayEndTime = $hasExtendedSchedule ?
                ($booking->extended_end_time ?? $booking->end_time) :
                $booking->end_time;

            if ($displayDateStart && $displayDateEnd && $displayStartTime && $displayEndTime) {
                try {
                    $startDateTime = Carbon::parse($displayDateStart . ' ' . $displayStartTime, $appTimezone);
                    $endDateTime = Carbon::parse($displayDateEnd . ' ' . $displayEndTime, $appTimezone);
                    
                    // Check if the current time is past the end time of the booking
                    $isBookingExpired = $now->greaterThan($endDateTime);
                    $bookingDate = $displayDateStart;
                } catch (\Exception $e) {
                    try {
                        $bookingDateObj = Carbon::parse($displayDateStart, $appTimezone);
                        $isBookingExpired = $now->greaterThan($bookingDateObj->endOfDay());
                        $bookingDate = $displayDateStart;
                    } catch (\Exception $e2) {
                        $isBookingExpired = false;
                    }
                }
            }

            // If booking status is completed (4), it's considered expired for display
            if ($booking->booking_status == 4) {
                $isBookingExpired = true;
            }

            return response()->json([
                'success' => true,
                'booking' => [
                    'booking_ref_no' => $booking->booking_ref_no,
                    'customer_first_name' => $customerAccount ? $customerAccount->first_name : 'N/A',
                    'customer_last_name' => $customerAccount ? $customerAccount->last_name : 'N/A',
                    'customer_email' => $customerAccount ? $customerAccount->email : 'N/A',
                    'customer_contact_no' => $customerAccount ? $customerAccount->contact_no : 'N/A',
                    'customer_uuid' => $customerAccount ? $customerAccount->uuid : null,
                    'branch_name' => $branch ? $branch->branch_name : 'N/A',
                    'branch_uuid' => $branch ? $branch->uuid : null,
                    'service_category' => $serviceCategory ? $serviceCategory->service_category : 'N/A',
                    'service_name' => $serviceName ? $serviceName->service_name : 'N/A',
                    // Main Schedule
                    'main_date_start' => $booking->date_start,
                    'main_date_end' => $booking->date_end,
                    'main_start_time' => $booking->start_time,
                    'main_end_time' => $booking->end_time,
                    // Extended Schedule
                    'extended_date_start' => $booking->extended_date_start,
                    'extended_date_end' => $booking->extended_date_end,
                    'extended_start_time' => $booking->extended_start_time,
                    'extended_end_time' => $booking->extended_end_time,
                    'has_extended_schedule' => $hasExtendedSchedule,
                    'seat_no' => $seat ? $seat->seat_no : 'N/A',
                    'room_no' => $seat ? $seat->room_no : 'N/A',
                    // Main Payment (payment_category = 1)
                    'main_payment_total_amount' => $mainPayment ? $mainPayment->total_amount : 0,
                    'main_payment_amount_paid' => $mainPayment ? $mainPayment->amount_paid : 0,
                    'main_payment_change' => $mainPayment ? $mainPayment->change : 0,
                    'main_payment_method' => $mainPayment ? $mainPayment->payment_method : null,
                    'main_payment_status' => $mainPayment ? $mainPayment->payment_status : null,
                    'has_main_payment' => !is_null($mainPayment),
                    'main_payment_category' => $mainPayment ? $mainPayment->payment_category : null,
                    // Extended Payment (payment_category = 0)
                    'extended_payment_total_amount' => $extendedPayment ? $extendedPayment->total_amount : 0,
                    'extended_payment_amount_paid' => $extendedPayment ? $extendedPayment->amount_paid : 0,
                    'extended_payment_change' => $extendedPayment ? $extendedPayment->change : 0,
                    'extended_payment_method' => $extendedPayment ? $extendedPayment->payment_method : null,
                    'extended_payment_status' => $extendedPayment ? $extendedPayment->payment_status : null,
                    'has_extended_payment' => !is_null($extendedPayment),
                    'extended_payment_category' => $extendedPayment ? $extendedPayment->payment_category : null,
                    // Additional payment details
                    'main_gcash_ref_no' => $mainPayment ? $mainPayment->gcash_ref_no : null,
                    'extended_gcash_ref_no' => $extendedPayment ? $extendedPayment->gcash_ref_no : null,
                    'main_payment_date' => $mainPayment ? $mainPayment->payment_date : null,
                    'extended_payment_date' => $extendedPayment ? $extendedPayment->payment_date : null,
                    // For compatibility with existing code
                    'total_amount' => $displayPayment ? $displayPayment->total_amount : 0,
                    'amount_paid' => $displayPayment ? $displayPayment->amount_paid : 0,
                    'change' => $displayPayment ? $displayPayment->change : 0,
                    'payment_method' => $displayPayment ? $displayPayment->payment_method : null,
                    'payment_status' => $displayPayment ? $displayPayment->payment_status : null,
                    'booking_status' => $booking->booking_status,
                    'booking_id' => $booking->id,
                    'booking_type' => $booking->booking_type,
                    // Checkin information - properly set
                    'checkin_id' => $checkinId,
                    'checkin_uuid' => $checkinUuid,
                    'checkin_status' => $checkinStatus,
                    'uuid' => $booking->uuid,
                    'has_unpaid_orders' => $hasUnpaidOrders,
                    'unpaid_orders_count' => $unpaidOrdersCount,
                    // ============================================================
                    // QR CODE VALIDITY STATUS - For informational display only
                    // ============================================================
                    'is_qr_code_valid' => $isQrCodeValid,
                    'validity_message' => $validityMessage,
                    'validity_details' => $validityDetails,
                    // ============================================================
                    // ORDER ELIGIBILITY STATUS - Controls Order button display
                    // ============================================================
                    'is_checked_in' => $isCheckedIn,
                    'is_booked' => $isBooked,
                    'is_order_eligible' => $isOrderEligible,
                    'show_order_button' => $showOrderButton,
                    // ============================================================
                    // EXPIRATION STATUS - FOR DISPLAY ONLY
                    // ============================================================
                    'is_expired' => $isBookingExpired,
                    'booking_date' => $bookingDate,
                    'display_date_start' => $displayDateStart,
                    'display_date_end' => $displayDateEnd,
                    'display_start_time' => $displayStartTime,
                    'display_end_time' => $displayEndTime,
                    // Status checks for informational display
                    'is_booking_status_valid' => $isBookingStatusValid,
                    'is_checkin_status_valid' => $isCheckinStatusValid,
                    'is_payment_unpaid' => $isPaymentUnpaid,
                    'are_orders_unpaid' => $areOrdersUnpaid,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getBookingByBookingRefNo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving booking information: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeCheckin(Request $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'booking_id' => 'required|integer|exists:bookings,id',
            ]);

            $booking = Booking::with(['customerAccount', 'customerCheckin', 'branch', 'serviceCategory', 'serviceName', 'seat'])->find($validated['booking_id']);

            if (!$booking) {
                throw new \Exception('Booking not found');
            }

            $existingCheckin = CustomerCheckin::where('booking_id', $booking->id)
                ->where('active', 1)
                ->first();

            if ($existingCheckin) {
                return response()->json([
                    'success' => false,
                    'message' => 'This booking is already checked in.',
                    'booking_ref_no' => $booking->booking_ref_no
                ], 409);
            }

            // Calculate three different time durations
            $timeUsed = $this->calculateMainTimeUsed($booking);
            $extendedTimeUsed = $this->calculateExtendedTimeUsed($booking);
            $totalTimeUsed = $timeUsed + $extendedTimeUsed;

            $checkin = CustomerCheckin::create([
                'customer_account_id' => $booking->customer_account_id,
                'branch_id' => $booking->branch_id,
                'service_category_id' => $booking->service_category_id,
                'service_name_id' => $booking->service_name_id,
                'seat_id' => $booking->seat_id,
                'booking_id' => $booking->id,
                'time_used' => $timeUsed,
                'extended_time_used' => $extendedTimeUsed,
                'total_time_used' => $totalTimeUsed,
                'checkin_status' => 1,
                'date_checked_in' => now('Asia/Manila'),
                'created_by' => Auth::guard('owner')->id(),
                'created_by_type' => 'owner',
                'date_created' => now(),
                'active' => 1,
            ]);

            // Send notifications
            $actor = Auth::guard('owner')->user();
            $bookingBranch = Branch::find($booking->branch_id);
            $customer = CustomerAccount::find($booking->customer_account_id);
            $owner = Auth::guard('owner')->user();
            $owners = OwnerAccount::where('id', $owner->id)->get();

            Notification::send($owners, new ScanQrCodeBookingNotification(
                $booking,
                $bookingBranch,
                $customer,
                $actor,
                'checked_in'
            ));

            $staffMembers = StaffAccount::where('branch_id', $booking->branch_id)
                ->where('owner_account_id', $actor->id)
                ->where('active', 1)
                ->get();

            Notification::send($staffMembers, new ScanQrCodeBookingStaffNotification(
                $booking,
                $bookingBranch,
                $customer,
                $actor,
                'checked_in'
            ));

            Notification::send($customer, new ScanQrCodeBookingCustomerNotification(
                $booking,
                $bookingBranch,
                $customer,
                $actor,
                'checked_in'
            ));

            DB::commit();

            session()->flash('success', 'Customer checked in.');

            return response()->json([
                'success' => true,
                'message' => 'Customer checked in successfully',
                'booking_ref_no' => $booking->booking_ref_no,
                'checkin_id' => $checkin->id,
                'time_used' => $timeUsed,
                'extended_time_used' => $extendedTimeUsed,
                'total_time_used' => $totalTimeUsed,
                'time_used_formatted' => $this->formatDuration($timeUsed),
                'extended_time_used_formatted' => $this->formatDuration($extendedTimeUsed),
                'total_time_used_formatted' => $this->formatDuration($totalTimeUsed),
                'redirect_url' => route('sub_one.customer_checkins.index') . '?brn=' . urlencode($booking->booking_ref_no)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to check-in customer: ' . $e->getMessage(),
                'booking_ref_no' => isset($booking) ? $booking->booking_ref_no : null
            ], 500);
        }
    }

    public function checkout(Request $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'booking_id' => 'required|integer|exists:bookings,id',
                'checkin_id' => 'required|integer|exists:customer_checkins,id',
            ]);

            $checkin = CustomerCheckin::with(['booking', 'branch', 'serviceCategory', 'serviceName', 'seat', 'customerAccount'])
                ->where('active', 1)
                ->find($validated['checkin_id']);

            if (!$checkin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Check-in record not found'
                ], 404);
            }

            // Verify the owner has access to this checkin
            $owner = Auth::guard('owner')->user();
            if ($checkin->branch->owner_account_id != $owner->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this checkin record'
                ], 403);
            }

            // Update checkin status to 0 (checked out)
            $checkin->checkin_status = 0;

            $isOnlineBooking = $checkin->booking->booking_type == 1;
            $appTimezone = 'Asia/Manila';
            $now = Carbon::now($appTimezone);

            // Check if booking has extended time values
            $hasExtendedTime = !is_null($checkin->booking->extended_start_time) &&
                !is_null($checkin->booking->extended_end_time) &&
                !is_null($checkin->booking->extended_date_start) &&
                !is_null($checkin->booking->extended_date_end);

            // Apply update tracking logic if extended time exists
            if ($hasExtendedTime) {
                if (!is_null($checkin->booking->updated_by)) {
                    $checkin->booking->last_updated_by = $checkin->booking->updated_by;
                    $checkin->booking->last_updated_by_type = $checkin->booking->updated_by_type;
                    $checkin->booking->last_date_updated = $checkin->booking->date_updated;
                }

                $checkin->booking->updated_by = Auth::guard('owner')->id();
                $checkin->booking->updated_by_type = 'owner';
                $checkin->booking->date_updated = now();
            }

            // DO NOT update booking status to 4 (completed) - keep as is

            if ($isOnlineBooking) {
                if (!$checkin->total_time_used || $checkin->total_time_used == 0) {
                    $checkin->total_time_used = $checkin->time_used ?? 0;
                }
            } else {
                // Walk-in booking - set end_time to current time
                $checkin->booking->end_time = $now->format('H:i:s');
                $checkin->booking->date_end = $now->format('Y-m-d');

                if ($checkin->booking->date_start && $checkin->booking->start_time) {
                    $start = Carbon::parse($checkin->booking->date_start . ' ' . $checkin->booking->start_time, $appTimezone);
                    $end = $now;

                    if ($end->lt($start)) {
                        $checkin->time_used = 0;
                    } else {
                        $checkin->time_used = $start->diffInMinutes($end);
                    }

                    $checkin->total_time_used = $checkin->time_used;
                }
            }

            $checkin->booking->save();
            $checkin->save();

            DB::commit();

            // Send notifications
            $actor = Auth::guard('owner')->user();
            $branch = $checkin->branch;
            $booking = $checkin->booking;
            $customer = $checkin->customerAccount;
            $owners = OwnerAccount::where('id', $owner->id)->get();

            Notification::send($owners, new ScanQrCodeBookingNotification(
                $booking,
                $branch,
                $customer,
                $actor,
                'checked_out'
            ));

            $staffMembers = StaffAccount::where('branch_id', $checkin->branch_id)
                ->where('owner_account_id', $actor->id)
                ->where('active', 1)
                ->get();

            Notification::send($staffMembers, new ScanQrCodeBookingStaffNotification(
                $booking,
                $branch,
                $customer,
                $actor,
                'checked_out'
            ));

            Notification::send($customer, new ScanQrCodeBookingCustomerNotification(
                $booking,
                $branch,
                $customer,
                $actor,
                'checked_out'
            ));

            // Calculate time used formatted
            $timeUsedFormatted = $this->formatDuration($checkin->time_used);
            $extendedTimeUsedFormatted = $this->formatDuration($checkin->extended_time_used ?? 0);
            $totalTimeUsedFormatted = $this->formatDuration($checkin->total_time_used);

            // Check for unpaid pay-later orders (order_payment_status = 0)
            $unpaidOrdersCount = Order::where('booking_id', $booking->id)
                ->where('branch_id', $booking->branch_id)
                ->whereHas('payments', function ($query) {
                    $query->where('order_payment_status', 0);
                })
                ->count();

            $hasUnpaidOrders = $unpaidOrdersCount > 0;

            // Check payment statuses
            $hasMainPayment = BookingPayment::where('booking_id', $booking->id)
                ->where('payment_category', 1)
                ->where('payment_status', 1)
                ->exists();
            
            $hasExtensionPayment = BookingPayment::where('booking_id', $booking->id)
                ->where('payment_category', 0)
                ->where('payment_status', 1)
                ->exists();

            $hasExtendedTimeUsed = ($checkin->extended_time_used ?? 0) > 0;

            // Decision logic for redirect:
            if ($hasUnpaidOrders) {
                $redirectUrl = route('sub_one.booking_lists.order_payment', ['booking_uuid' => $booking->uuid]);
                $paymentType = 'order';
            } elseif ($hasExtendedTimeUsed && !$hasExtensionPayment) {
                $redirectUrl = route('sub_one.booking_lists.extension_payment', ['booking_uuid' => $booking->uuid]);
                $paymentType = 'extension';
            } elseif (!$hasMainPayment) {
                $redirectUrl = route('sub_one.booking_lists.main_payment', ['booking_uuid' => $booking->uuid]);
                $paymentType = 'main';
            } else {
                $redirectUrl = route('sub_one.booking_lists.showBookingList');
                $paymentType = 'none';
            }

            return response()->json([
                'success' => true,
                'message' => 'Customer checked out successfully',
                'checkin_status' => 0,
                'time_used' => $checkin->time_used,
                'extended_time_used' => $checkin->extended_time_used ?? 0,
                'total_time_used' => $checkin->total_time_used,
                'time_used_formatted' => $timeUsedFormatted,
                'extended_time_used_formatted' => $extendedTimeUsedFormatted,
                'total_time_used_formatted' => $totalTimeUsedFormatted,
                'redirect_url' => $redirectUrl,
                'booking_uuid' => $booking->uuid,
                'payment_type' => $paymentType,
                'has_unpaid_orders' => $hasUnpaidOrders,
                'unpaid_orders_count' => $unpaidOrdersCount
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in checkout: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check out customer: ' . $e->getMessage()
            ], 500);
        }
    }

    private function calculateMainTimeUsed($booking)
    {
        if (!$booking->date_start || !$booking->start_time || !$booking->date_end || !$booking->end_time) {
            return 0;
        }

        try {
            $appTimezone = 'Asia/Manila';
            $start = Carbon::parse($booking->date_start . ' ' . $booking->start_time, $appTimezone);
            $end = Carbon::parse($booking->date_end . ' ' . $booking->end_time, $appTimezone);

            if ($end->lte($start)) {
                return 0;
            }

            return $start->diffInMinutes($end);
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function calculateExtendedTimeUsed($booking)
    {
        $hasExtendedSchedule = $booking->extended_date_start ||
            $booking->extended_date_end ||
            $booking->extended_start_time ||
            $booking->extended_end_time;

        if (!$hasExtendedSchedule) {
            return 0;
        }

        $dateStart = $booking->extended_date_start ?: null;
        $dateEnd = $booking->extended_date_end ?: null;
        $startTime = $booking->extended_start_time ?: null;
        $endTime = $booking->extended_end_time ?: null;

        if (!$dateStart || !$startTime || !$dateEnd || !$endTime) {
            return 0;
        }

        try {
            $appTimezone = 'Asia/Manila';
            $start = Carbon::parse($dateStart . ' ' . $startTime, $appTimezone);
            $end = Carbon::parse($dateEnd . ' ' . $endTime, $appTimezone);

            if ($end->lte($start)) {
                return 0;
            }

            return $start->diffInMinutes($end);
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function formatDuration($minutes)
    {
        if ($minutes === null || $minutes === 0) {
            return '0 minutes';
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        $parts = [];
        if ($hours > 0) {
            $parts[] = $hours . ' ' . ($hours == 1 ? 'hr' : 'hrs');
        }
        if ($mins > 0) {
            $parts[] = $mins . ' ' . ($mins == 1 ? 'min' : 'mins');
        }

        return count($parts) > 0 ? implode(', ', $parts) : '0 minutes';
    }
}