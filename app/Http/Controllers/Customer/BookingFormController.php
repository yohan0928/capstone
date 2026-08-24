<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\OwnerAccount;
use App\Models\Seat;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class BookingFormController extends Controller
{
    public function showBookingForm($branch_uuid, $service_category_uuid, $service_name_uuid)
    {
        try {
            // Check authentication
            if (!Auth::guard('customer')->check()) {
                return redirect()->route('showLoginForm')->with('error', 'Please login to book a service.');
            }

            $customer = Auth::guard('customer')->user();

            // Get branch details
            $branch = Branch::where('uuid', $branch_uuid)
                ->where('active', 1)
                ->where('branch_status', 1)
                ->firstOrFail();

            // Format branch hours for display
            $openTimeFormatted = $this->formatTimeForDisplay($branch->open_time);
            $closeTimeFormatted = $this->formatTimeForDisplay($branch->close_time);

            // Get service category details
            $serviceCategory = ServiceCategory::where('uuid', $service_category_uuid)
                ->where('active', 1)
                ->where('service_category_status', 1)
                ->firstOrFail();

            // Get service name details
            $service = ServiceName::with(['serviceCategory', 'branch'])
                ->where('uuid', $service_name_uuid)
                ->where('active', 1)
                ->where('service_name_status', 1)
                ->where('branch_id', $branch->id)
                ->where('service_category_id', $serviceCategory->id)
                ->firstOrFail();

            // Get the 1-hour service price for this category (for extended time calculations)
            $oneHourService = ServiceName::where('branch_id', $branch->id)
                ->where('service_category_id', $serviceCategory->id)
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

            // If no 1-hour service found, use the shortest duration service as hourly reference
            if (!$oneHourService) {
                $oneHourService = ServiceName::where('branch_id', $branch->id)
                    ->where('service_category_id', $serviceCategory->id)
                    ->where('active', 1)
                    ->where('service_name_status', 1)
                    ->orderByRaw("
            CASE 
                WHEN time_duration LIKE '%hour%' THEN CAST(SUBSTRING_INDEX(time_duration, ' ', 1) AS UNSIGNED)
                WHEN time_duration LIKE '%minute%' THEN CAST(SUBSTRING_INDEX(time_duration, ' ', 1) AS UNSIGNED) / 60
                ELSE 999
            END ASC
        ")
                    ->first();
            }

            $hourlyRate = $oneHourService ? $oneHourService->price : 0;

            // Also get the user's selected service details
            $selectedService = ServiceName::find($service->id);
            $selectedServicePrice = $selectedService->price;
            $selectedServiceDuration = $this->parseDuration($selectedService->time_duration);

            // Determine the actual space type based on service category name
            // If the category name contains "room" or "private", treat it as a room service
            $categoryName = strtolower($serviceCategory->service_category);
            $actualSpaceType = $service->space_type;  // Start with service's space_type

            if (str_contains($categoryName, 'room') || str_contains($categoryName, 'private')) {
                $actualSpaceType = 'room';
            } elseif (str_contains($categoryName, 'seat') || str_contains($categoryName, 'workstation')) {
                $actualSpaceType = 'seat';
            }

            // Get available seats/rooms for this specific branch and service category
            $seats = Seat::where('branch_id', $branch->id)
                ->where('service_category_id', $serviceCategory->id)
                ->where('active', 1)
                ->where('seat_status', 1)
                ->get()
                ->map(function ($seat) use ($actualSpaceType) {
                    // Determine the display label based on actual space type and database values
                    if ($actualSpaceType == 'room') {
                        // For room services, prioritize room_no
                        if ($seat->room_no !== null) {
                            $seat->display_label = 'Room ' . $seat->room_no;
                            $seat->display_number = $seat->room_no;
                        } else {
                            // If no room_no, use seat_no as fallback
                            $seat->display_label = 'Room ' . $seat->seat_no;
                            $seat->display_number = $seat->seat_no;
                        }
                    } else {
                        // For seat services, prioritize seat_no
                        if ($seat->seat_no !== null) {
                            $seat->display_label = 'Seat ' . $seat->seat_no;
                            $seat->display_number = $seat->seat_no;
                        } else {
                            // If no seat_no, use room_no as fallback
                            $seat->display_label = 'Seat ' . $seat->room_no;
                            $seat->display_number = $seat->room_no;
                        }
                    }
                    return $seat;
                })
                ->sortBy('display_number');

            // Get all service packages for the same branch and category
            $servicePackages = ServiceName::where('branch_id', $branch->id)
                ->where('service_category_id', $serviceCategory->id)
                ->where('active', 1)
                ->where('service_name_status', 1)
                ->orderBy('price')
                ->get()
                ->map(function ($service) {
                    return [
                        'id' => $service->id,
                        'service_name' => $service->service_name,
                        'price' => (float) $service->price,  // Ensure it's a float
                        'time_duration' => $service->time_duration,
                        'duration_minutes' => $this->parseDuration($service->time_duration)
                    ];
                })
                ->toArray();

            // Calculate default duration from service time_duration
            $defaultDuration = $this->parseDuration($service->time_duration);

            // Generate time slots for the next 60 days (dynamic) based on branch open days
            $timeSlots = $this->generateTimeSlotsForNextDays(60, 15, $branch->open_time, $branch->close_time, $branch->open_days);

            return view('customer.home.booking-form', compact(
                'branch',
                'serviceCategory',
                'service',
                'seats',
                'defaultDuration',
                'customer',
                'timeSlots',
                'openTimeFormatted',
                'closeTimeFormatted',
                'actualSpaceType',
                'servicePackages',
                'hourlyRate'
            ));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()
                ->route('sub_three.home.showHome')
                ->with('error', 'Service, branch, or category not found.');
        } catch (\Exception $e) {
            return redirect()
                ->route('sub_three.home.showHome')
                ->with('error', 'An error occurred while loading the booking form.');
        }
    }

    public function getExistingBookings(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|integer',
            'service_category_id' => 'required|integer',
            'service_name_id' => 'required|integer',
            'seat_id' => 'required|integer',
            'date_start' => 'required|date',
            'date_end' => 'required|date',
        ]);

        // Assuming you have a Booking model
        $existingBookings = Booking::where('branch_id', $validated['branch_id'])
            ->where('service_category_id', $validated['service_category_id'])
            ->where('service_name_id', $validated['service_name_id'])
            ->where('seat_id', $validated['seat_id'])
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
            })
            // For tinyinteger field, use numeric values: 1=booked, 2=pending
            ->whereIn('booking_status', [1, 2, 4])
            ->get([
                'start_time',
                'end_time',
                'date_start',
                'date_end',
                'extended_start_time',
                'extended_end_time',
                'extended_date_start',
                'extended_date_end'
            ]);

        return response()->json($existingBookings);
    }

    /**
     * Show Payment Options Page with Owner's QR Codes
     */
    public function showPaymentOptions(Request $request)
    {
        // 1. Fetch Owner QR Codes using the branch_id from the request
        $qrCodes = [];
        $branch = Branch::find($request->branch_id);
        
        if ($branch) {
            // Find the owner associated with this branch
            $owner = OwnerAccount::find($branch->owner_account_id);
            
            if ($owner && $owner->gcash_qr_code_img) {
                // Decode JSON if necessary or use direct value
                if (is_array($owner->gcash_qr_code_img)) {
                    $qrCodes = $owner->gcash_qr_code_img;
                } elseif (is_string($owner->gcash_qr_code_img)) {
                    $decoded = json_decode($owner->gcash_qr_code_img, true);
                    $qrCodes = is_array($decoded) ? $decoded : [$owner->gcash_qr_code_img];
                }
            }
        }

        // 2. Structure Booking Details for View
        // We create objects to match the view's expectation (e.g., $details['branch']->branch_name)
        $bookingDetails = [
            'branch' => (object)[
                'id' => $request->branch_id,
                'uuid' => $request->branch_uuid,
                'branch_name' => $request->branch_name,
                'location' => $request->branch_location,
                'open_time' => $request->branch_open_time,
                'close_time' => $request->branch_close_time,
            ],
            'service_category' => (object)[
                'id' => $request->service_category_id,
                'uuid' => $request->service_category_uuid,
                'service_category' => $request->service_category_name,
            ],
            'service_name' => (object)[
                'id' => $request->service_name_id,
                'uuid' => $request->service_name_uuid,
                'service_name' => $request->service_name,
                'time_duration' => $request->service_time_duration,
                'price' => $request->service_price,
                'space_type' => $request->service_space_type,
            ],
            'seat' => $request->seat_id ? (object)[
                'id' => $request->seat_id,
                'seat_no' => str_contains($request->seat_display_label, 'Seat') ? str_replace('Seat ', '', $request->seat_display_label) : null,
                'room_no' => str_contains($request->seat_display_label, 'Room') ? str_replace('Room ', '', $request->seat_display_label) : null,
                'display_label' => $request->seat_display_label,
            ] : null,
            
            // Flat fields
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'booking_time' => $request->booking_time,
            'end_time' => $request->end_time,
            'main_duration' => $request->main_duration,
            'total_duration' => $request->total_duration,
            'additional_hours' => $request->additional_hours,
            'additional_minutes' => $request->additional_minutes,
            'additional_price' => $request->additional_price,
            'total_price' => $request->total_price,
            'notes' => $request->notes,
            
            // Extended info
            'extended_start_time' => $request->extended_start_time,
            'extended_end_time' => $request->extended_end_time,
            'extended_date_start' => $request->extended_start_date,
            'extended_date_end' => $request->extended_end_date,
            'extended_duration_minutes' => $request->extended_duration_total,
        ];

        return view('customer.home.booking-preview-payment', [
            'bookingDetails' => $bookingDetails,
            'ownerGcashQrCode' => $qrCodes, // Updated variable name for the view
            'showPayment' => true 
        ]);
    }

    /**
     * Parse duration string to minutes
     */
    private function parseDuration($duration)
    {
        if (str_contains($duration, 'hour')) {
            $hours = (int) $duration;
            return $hours * 60;
        } elseif (str_contains($duration, 'minute')) {
            return (int) $duration;
        }
        return 60;  // Default to 1 hour
    }

    /**
     * Format time for display (12-hour format without dates)
     */
    private function formatTimeForDisplay($timeString)
    {
        try {
            $time = Carbon::createFromTimeString($timeString);
            return $time->format('g:i A');
        } catch (\Exception $e) {
            // Fallback if time parsing fails
            return $timeString;
        }
    }

    /**
     * Generate time slots for the next N days using dynamic branch hours
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
                continue; // Skip generation for closed days
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
        if (empty($openDaysStr)) return true; // Default to open if not specified

        $dayName = strtolower($date->format('D')); // mon, tue, etc.
        $openDaysStr = strtolower($openDaysStr);
        
        // Handle "Daily" or "Everyday"
        if (strpos($openDaysStr, 'daily') !== false || strpos($openDaysStr, 'everyday') !== false) {
            return true;
        }

        // Map days to integers (1=Mon, ..., 7=Sun)
        $daysMap = ['mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6, 'sun' => 7];
        $targetDayNum = $daysMap[$dayName] ?? 0;

        if ($targetDayNum === 0) return true; // Safety fallback

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
                        if ($targetDayNum >= $startNum && $targetDayNum <= $endNum) return true;
                    } else {
                        // Wrap around range (e.g., Fri - Mon)
                        if ($targetDayNum >= $startNum || $targetDayNum <= $endNum) return true;
                    }
                }
            } else {
                // Handle Single Day (e.g., "Mon")
                if (isset($daysMap[$part]) && $daysMap[$part] == $targetDayNum) return true;
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
}