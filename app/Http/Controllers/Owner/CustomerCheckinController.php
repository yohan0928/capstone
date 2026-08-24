<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\CustomerAccount;
use App\Models\CustomerCheckin;
use App\Models\OwnerAccount;
use App\Models\StaffAccount;
use App\Notifications\Customer\CustomerCheckinNotification;
use App\Notifications\Owner\CustomerCheckinOwnerNotification;
use App\Notifications\Staff\CustomerCheckinStaffNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CustomerCheckinController extends Controller
{
    public function index(Request $request)
    {
        // Get the currently authenticated owner
        $owner = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        // Clear BRN from session if requested
        if ($request->has('clear_brn')) {
            session()->forget('scanned_brn');
            // If this is an AJAX request for clearing BRN, return success
            if ($request->ajax() && $request->clear_brn) {
                return response()->json([
                    'success' => true,
                    'message' => 'BRN cleared successfully',
                    'data' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => 50,
                        'total' => 0,
                        'from' => null,
                        'to' => null,
                    ],
                    'stats' => [
                        'total_checkins' => 0,
                        'active_checkins' => 0,
                        'checked_out' => 0,
                    ],
                    'scanned_brn' => null
                ]);
            }
        }

        // Get customer checkins with related data and pagination
        $query = CustomerCheckin::with([
            'booking' => function ($query) {
                $query->select('id', 'uuid', 'booking_ref_no', 'customer_account_id', 'start_time', 'date_start', 'booking_type');
            },
            'branch' => function ($query) {
                $query->select('id', 'uuid', 'branch_name');
            },
            'serviceCategory' => function ($query) {
                $query->select('id', 'service_category');
            },
            'serviceName' => function ($query) {
                $query->select('id', 'service_name');
            },
            'seat' => function ($query) {
                $query->select('id', 'seat_no');
            },
            'customerAccount' => function ($query) {
                $query->select('id', 'uuid', 'first_name', 'last_name', 'email');
            }
        ])
            ->whereHas('branch', function ($query) use ($ownerId) {
                $query->where('owner_account_id', $ownerId);
            })
            ->where('active', 1);

        // Filter by BRN from URL parameter - ONLY if not clearing
        if ($request->filled('brn') && !$request->has('clear_brn')) {
            $brn = $request->brn;
            $query->whereHas('booking', function ($q) use ($brn) {
                $q->where('booking_ref_no', $brn);
            });

            // Store the BRN in session for the view
            session(['scanned_brn' => $brn]);
        }

        // Search by customer full name or booking reference
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q
                    ->whereHas('customerAccount', function ($q) use ($searchTerm) {
                        $q->where(DB::raw("CONCAT(first_name,' ',last_name)"), 'LIKE', "%{$searchTerm}%");
                    })
                    ->orWhereHas('booking', function ($q) use ($searchTerm) {
                        $q->where('booking_ref_no', 'LIKE', "%{$searchTerm}%");
                    });
            });
        }

        // Apply filters
        if ($request->has('date_start') && $request->date_start) {
            $query->whereDate('date_checked_in', '>=', $request->date_start);
        }

        if ($request->has('date_end') && $request->date_end) {
            $query->whereDate('date_checked_in', '<=', $request->date_end);
        }

        if ($request->has('checkin_status') && $request->checkin_status !== '') {
            $query->where('checkin_status', $request->checkin_status);
        }

        // REMOVED THE COMMENTED CODE - Always show data, don't default to today
        // Only apply date filter if explicitly requested
        if (!$request->has('date_start') && !$request->has('date_end')) {
            // If no date filters are applied, show all checkins (not just today's)
            // This matches the booking list behavior
        }

        $customerCheckins = $query
            ->orderBy('date_checked_in', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(50);

        // Format time used for each checkin and include booking_type
        $customerCheckins->getCollection()->transform(function ($checkin) {
            // Ensure total_time_used is always set
            if (!$checkin->total_time_used || $checkin->total_time_used == 0) {
                $checkin->total_time_used = $checkin->time_used ?? 0;
            }

            $checkin->time_used_formatted = $this->formatDuration($checkin->time_used ?? 0);
            $checkin->total_time_used_formatted = $this->formatDuration($checkin->total_time_used);
            $checkin->booking_type = $checkin->booking->booking_type ?? 0;

            // Also format the extended time used
            $checkin->extended_time_used_formatted = $this->formatDuration($checkin->extended_time_used ?? 0);

            return $checkin;
        });

        // Get statistics for the dashboard cards
        $statsQuery = CustomerCheckin::whereHas('branch', function ($query) use ($ownerId) {
            $query->where('owner_account_id', $ownerId);
        })->where('active', 1);

        // Apply same filters to stats
        if ($request->filled('brn') && !$request->has('clear_brn')) {
            $brn = $request->brn;
            $statsQuery->whereHas('booking', function ($q) use ($brn) {
                $q->where('booking_ref_no', $brn);
            });
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $statsQuery->where(function ($q) use ($searchTerm) {
                $q
                    ->whereHas('customerAccount', function ($q) use ($searchTerm) {
                        $q->where(DB::raw("CONCAT(first_name,' ',last_name)"), 'LIKE', "%{$searchTerm}%");
                    })
                    ->orWhereHas('booking', function ($q) use ($searchTerm) {
                        $q->where('booking_ref_no', 'LIKE', "%{$searchTerm}%");
                    });
            });
        }

        if ($request->has('date_start') && $request->date_start) {
            $statsQuery->whereDate('date_checked_in', '>=', $request->date_start);
        }

        if ($request->has('date_end') && $request->date_end) {
            $statsQuery->whereDate('date_checked_in', '<=', $request->date_end);
        }

        if ($request->has('checkin_status') && $request->checkin_status !== '') {
            $statsQuery->where('checkin_status', $request->checkin_status);
        }

        // REMOVED the default today filter for stats too
        // Only filter by today if no date filters at all
        if (
            !$request->has('date_start') &&
            !$request->has('date_end') &&
            !$request->has('checkin_status') &&
            !$request->filled('search') &&
            !$request->filled('brn')
        ) {
            // When all filters are clear, show all data (not just today's)
            // This is the key change - removed the today filter
        }

        $totalCheckins = $statsQuery->count();
        $activeCheckins = (clone $statsQuery)->where('checkin_status', 1)->count();
        $checkedOut = (clone $statsQuery)->where('checkin_status', 0)->count();

        $stats = [
            'total_checkins' => $totalCheckins,
            'active_checkins' => $activeCheckins,
            'checked_out' => $checkedOut,
        ];

        // Get the scanned BRN for the view - check if we're clearing it first
        $scannedBrn = $request->has('clear_brn') ? null : ($request->brn ?: session('scanned_brn'));

        // Return JSON for AJAX requests, otherwise return view
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $customerCheckins->items(),
                'pagination' => [
                    'current_page' => $customerCheckins->currentPage(),
                    'last_page' => $customerCheckins->lastPage(),
                    'per_page' => $customerCheckins->perPage(),
                    'total' => $customerCheckins->total(),
                    'from' => $customerCheckins->firstItem(),
                    'to' => $customerCheckins->lastItem(),
                ],
                'stats' => $stats,
                'scanned_brn' => $scannedBrn
            ]);
        }

        return view('owner.booking.customer_checkins', compact('customerCheckins', 'stats', 'scannedBrn'));
    }

    /**
     * Show extend time modal with available time slots
     */
    public function showExtendTimeModal($id)
    {
        try {
            \Log::info('Showing extend time modal for checkin ID: ' . $id);

            // Find the checkin record
            $customerCheckin = CustomerCheckin::with([
                'booking',
                'branch',
                'serviceCategory',
                'serviceName',
                'seat',
                'customerAccount'
            ])->where('active', 1)->find($id);

            if (!$customerCheckin) {
                \Log::error('Checkin not found: ' . $id);
                return response()->json([
                    'success' => false,
                    'message' => 'Check-in record not found.'
                ], 404);
            }

            // Verify owner access
            $this->verifyOwnerAccess($customerCheckin);

            // Only allow extend time for online bookings (booking_type = 1)
            if ($customerCheckin->booking->booking_type !== 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Time extension is only available for online bookings.'
                ], 403);
            }

            // Get basic times
            $booking = $customerCheckin->booking;

            // Check for existing extension
            $hasExtendedColumns = DB::getSchemaBuilder()->hasColumn('bookings', 'extended_start_time');
            $hasExistingExtension = false;

            if ($hasExtendedColumns) {
                $hasExistingExtension = $booking->extended_start_time &&
                    $booking->extended_end_time &&
                    $booking->extended_date_start &&
                    $booking->extended_date_end;
            }

            // Determine which times to use (extended or original)
            if ($hasExistingExtension) {
                $currentEndTime = $booking->extended_end_time;
                $currentDateEnd = $booking->extended_date_end;
                $currentStartTime = $booking->extended_start_time;
                $currentDateStart = $booking->extended_date_start;
            } else {
                $currentEndTime = $booking->end_time;
                $currentDateEnd = $booking->date_end;
                $currentStartTime = $booking->start_time;
                $currentDateStart = $booking->date_start;
            }

            // Use time_used from customer_checkins
            $timeUsed = $customerCheckin->time_used ?? 0;
            $extendedTimeUsed = $customerCheckin->extended_time_used ?? 0;
            $totalTimeUsed = $timeUsed + $extendedTimeUsed;

            // Check if this is a next-day midnight booking
            $isNextDayMidnight = ($currentEndTime === '00:00:00' || $currentEndTime === '00:00');

            // Get existing bookings for the same seat/branch to prevent overlap
            $existingBookings = $this->getExistingBookingsForSeat(
                $customerCheckin->branch_id,
                $customerCheckin->seat_id,
                $currentDateEnd,
                $currentEndTime,
                $customerCheckin->id
            );

            // Calculate maximum available duration considering existing bookings
            $maxAvailableDuration = $this->calculateMaxAvailableDuration(
                $currentDateEnd,
                $currentEndTime,
                $customerCheckin->branch->close_time,
                $existingBookings
            );

            $response = [
                'success' => true,
                'checkIn' => $customerCheckin,
                'time_used' => $timeUsed,
                'extended_time_used' => $extendedTimeUsed,
                'total_time_used' => $totalTimeUsed,
                'branch_open_time' => $this->safeFormatTime($customerCheckin->branch->open_time),
                'branch_close_time' => $this->safeFormatTime($customerCheckin->branch->close_time),
                'branch_close_time_raw' => $customerCheckin->branch->close_time,
                'branch_open_time_raw' => $customerCheckin->branch->open_time,
                'is_next_day_midnight' => $isNextDayMidnight,
                'has_existing_extension' => $hasExistingExtension,
                'has_extended_columns' => $hasExtendedColumns,
                'current_start_time' => $currentStartTime,
                'current_end_time' => $currentEndTime,
                'current_date_start' => $currentDateStart,
                'current_date_end' => $currentDateEnd,
                'time_used_formatted' => $this->formatDuration($timeUsed),
                'extended_time_used_formatted' => $this->formatDuration($extendedTimeUsed),
                'total_time_used_formatted' => $this->formatDuration($totalTimeUsed),
                'existing_bookings' => $existingBookings,
                'max_available_duration' => $maxAvailableDuration, // in minutes
            ];

            \Log::info('Returning extend time modal data:', $response);

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::error('Error in showExtendTimeModal: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load extend time options. Please try again.'
            ], 500);
        }
    }

    /**
     * Get existing bookings for the same seat to prevent time overlap
     */
    private function getExistingBookingsForSeat($branchId, $seatId, $currentDate, $currentTime, $excludeCheckinId)
    {
        // Parse current datetime
        $currentDateTime = Carbon::parse("$currentDate $currentTime");
        
        // Get bookings for the next 24 hours from the current end time
        $startDate = $currentDateTime->copy()->subHours(1); // Look back 1 hour too
        $endDate = $currentDateTime->copy()->addHours(24);
        
        $bookings = Booking::where('branch_id', $branchId)
            ->where('seat_id', $seatId)
            ->where('active', 1)
            ->where(function($query) use ($startDate, $endDate) {
                // Check for overlapping time ranges
                $query->where(function($q) use ($startDate, $endDate) {
                    // Original booking times
                    $q->where(function($inner) use ($startDate, $endDate) {
                        $inner->where('date_start', '<=', $endDate->format('Y-m-d'))
                              ->where('date_end', '>=', $startDate->format('Y-m-d'));
                    })
                    // OR extended booking times
                    ->orWhere(function($inner) use ($startDate, $endDate) {
                        $inner->whereNotNull('extended_date_start')
                              ->whereNotNull('extended_date_end')
                              ->where('extended_date_start', '<=', $endDate->format('Y-m-d'))
                              ->where('extended_date_end', '>=', $startDate->format('Y-m-d'));
                    });
                });
            })
            ->where('id', '!=', $excludeCheckinId) // Exclude current booking
            ->get();
        
        $formattedBookings = [];
        
        foreach ($bookings as $booking) {
            // Use extended times if they exist, otherwise use original times
            if ($booking->extended_date_start && $booking->extended_date_end) {
                $formattedBookings[] = [
                    'start' => Carbon::parse("{$booking->extended_date_start} {$booking->extended_start_time}"),
                    'end' => Carbon::parse("{$booking->extended_date_end} {$booking->extended_end_time}"),
                    'booking_ref_no' => $booking->booking_ref_no,
                    'type' => 'extended'
                ];
            }
            
            // Always include original booking times
            $formattedBookings[] = [
                'start' => Carbon::parse("{$booking->date_start} {$booking->start_time}"),
                'end' => Carbon::parse("{$booking->date_end} {$booking->end_time}"),
                'booking_ref_no' => $booking->booking_ref_no,
                'type' => 'original'
            ];
        }
        
        return $formattedBookings;
    }

    /**
     * Calculate maximum available duration considering branch closing time and existing bookings
     */
    private function calculateMaxAvailableDuration($currentDate, $currentTime, $branchCloseTime, $existingBookings)
    {
        try {
            $currentDateTime = Carbon::parse("$currentDate $currentTime");
            
            // Calculate until branch closing time on the same day
            $closeDateTime = Carbon::parse("$currentDate $branchCloseTime");
            
            // If close time is earlier than current time, it's for the next day
            if ($closeDateTime->lte($currentDateTime)) {
                $closeDateTime->addDay();
            }
            
            // Start with maximum possible duration (until closing time)
            $maxDuration = $currentDateTime->diffInMinutes($closeDateTime);
            
            // Adjust for existing bookings
            foreach ($existingBookings as $booking) {
                $bookingStart = $booking['start'];
                $bookingEnd = $booking['end'];
                
                // If this booking starts after our current time and overlaps with our potential extension
                if ($bookingStart->gte($currentDateTime)) {
                    // Calculate how much time we have until this booking starts
                    $availableUntilBooking = $currentDateTime->diffInMinutes($bookingStart);
                    
                    // Take the minimum between branch closing and next booking start
                    $maxDuration = min($maxDuration, $availableUntilBooking);
                }
            }
            
            // Round down to nearest 15 minutes
            $maxDuration = floor($maxDuration / 15) * 15;
            
            // Ensure minimum 15 minutes
            return max(15, $maxDuration);
            
        } catch (\Exception $e) {
            \Log::error('Error calculating max available duration: ' . $e->getMessage());
            return 480; // Default 8 hours if error
        }
    }

    /**
     * Safe time formatting without Carbon
     */
    private function safeFormatTime($time)
    {
        if (empty($time) || $time === '00:00:00' || $time === '00:00') {
            return '12:00 AM';
        }

        try {
            $time = substr($time, 0, 5);  // Get HH:MM
            list($hour, $minute) = explode(':', $time);

            $hour = (int) $hour;
            $minute = (int) $minute;

            // FIXED: Proper 12-hour conversion
            $period = $hour >= 12 ? 'PM' : 'AM';
            $displayHour = $hour % 12;
            if ($displayHour === 0) {
                $displayHour = 12;  // 0 or 12 becomes 12
            }

            // Special case: midnight is 12:00 AM, not 0:00 AM
            if ($hour === 0) {
                $displayHour = 12;
                $period = 'AM';
            }
            // Special case: noon is 12:00 PM, not 0:00 PM
            elseif ($hour === 12) {
                $displayHour = 12;
                $period = 'PM';
            }

            return sprintf('%d:%02d %s', $displayHour, $minute, $period);
        } catch (\Exception $e) {
            return $time;
        }
    }

    /**
     * Process time extension
     */
    public function extendTime(Request $request, $id)
    {
        \Log::info('Extend time request received:', $request->all());

        $request->validate([
            'extended_start_time' => 'required',
            'extended_end_time' => 'required',
            'extended_date_start' => 'required|date',
            'extended_date_end' => 'required|date',
            'additional_duration' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            // Load the necessary relationships
            $customerCheckin = CustomerCheckin::with([
                'booking',
                'branch',
                'customerAccount'
            ])->where('active', 1)->findOrFail($id);

            \Log::info('Customer checkin found:', [
                'id' => $customerCheckin->id,
                'booking_id' => $customerCheckin->booking_id,
                'current_time_used' => $customerCheckin->time_used,
                'current_extended_time_used' => $customerCheckin->extended_time_used,
                'current_total_time_used' => $customerCheckin->total_time_used
            ]);

            // Verify the owner has access to this checkin
            $this->verifyOwnerAccess($customerCheckin);

            $booking = $customerCheckin->booking;
            $ownerId = Auth::guard('owner')->id();

            // Check for existing bookings that would overlap with the extension
            $existingBookings = $this->getExistingBookingsForSeat(
                $customerCheckin->branch_id,
                $customerCheckin->seat_id,
                $request->extended_date_start,
                $request->extended_start_time,
                $customerCheckin->id
            );

            // Parse the proposed extension end datetime
            $proposedEndDateTime = Carbon::parse("{$request->extended_date_end} {$request->extended_end_time}");
            
            // Check for conflicts
            foreach ($existingBookings as $existing) {
                if ($proposedEndDateTime->gt($existing['start']) && $proposedEndDateTime->lte($existing['end'])) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Time extension conflicts with existing booking ' . $existing['booking_ref_no']
                    ], 409);
                }
            }

            // Check if this is the first extension
            $isFirstExtension = is_null($booking->extended_start_time) &&
                is_null($booking->extended_end_time) &&
                is_null($booking->extended_date_start) &&
                is_null($booking->extended_date_end);

            // Format the times properly
            $extendedStartTime = $request->extended_start_time;
            $extendedEndTime = $request->extended_end_time;

            // Ensure times are in H:i:s format
            if (strlen($extendedStartTime) <= 5) {
                $extendedStartTime .= ':00';
            }
            if (strlen($extendedEndTime) <= 5) {
                $extendedEndTime .= ':00';
            }

            if ($isFirstExtension) {
                // First extension - set all extended fields
                $booking->extended_start_time = $extendedStartTime;
                $booking->extended_end_time = $extendedEndTime;
                $booking->extended_date_start = $request->extended_date_start;
                $booking->extended_date_end = $request->extended_date_end;

                // Set creation tracking fields
                $booking->created_by = $ownerId;
                $booking->created_by_type = 'owner';
                $booking->date_created = now();
            } else {
                // Subsequent extension - preserve start, update end only
                $booking->extended_end_time = $extendedEndTime;
                $booking->extended_date_end = $request->extended_date_end;

                // Move previous update info to last_* fields
                $booking->last_updated_by = $booking->updated_by;
                $booking->last_updated_by_type = $booking->updated_by_type;
                $booking->last_date_updated = $booking->date_updated;
            }

            // Always set current update tracking
            $booking->updated_by = $ownerId;
            $booking->updated_by_type = 'owner';
            $booking->date_updated = now();

            // Calculate extended time used
            $additionalDuration = (int) $request->additional_duration;

            // Update customer checkin time fields
            if ($isFirstExtension) {
                // First extension: set extended_time_used to additional duration
                $customerCheckin->extended_time_used = $additionalDuration;
            } else {
                // Subsequent extension: add to existing extended time
                $customerCheckin->extended_time_used = ($customerCheckin->extended_time_used ?? 0) + $additionalDuration;
            }

            // Update total time used
            $originalTimeUsed = $customerCheckin->time_used ?? 0;
            $customerCheckin->total_time_used = $originalTimeUsed + ($customerCheckin->extended_time_used ?? 0);

            // Save changes
            $bookingSaved = $booking->save();
            $checkinSaved = $customerCheckin->save();

            \Log::info('Save results:', [
                'booking_saved' => $bookingSaved,
                'checkin_saved' => $checkinSaved,
                'booking_after_save' => [
                    'extended_start_time' => $booking->extended_start_time,
                    'extended_end_time' => $booking->extended_end_time,
                    'extended_date_start' => $booking->extended_date_start,
                    'extended_date_end' => $booking->extended_date_end
                ],
                'checkin_after_save' => [
                    'extended_time_used' => $customerCheckin->extended_time_used,
                    'total_time_used' => $customerCheckin->total_time_used
                ]
            ]);

            DB::commit();

            // Send notifications
            $actor = Auth::guard('owner')->user();
            $branch = Branch::find($customerCheckin->branch_id);
            $customer = CustomerAccount::find($customerCheckin->customer_account_id);
            $owner = Auth::guard('owner')->user();
            $owners = OwnerAccount::where('id', $owner->id)->get();

            Notification::send($owners, new CustomerCheckinOwnerNotification(
                $customerCheckin,
                $booking,
                $branch,
                $customer,
                $actor,
                'extend_time'
            ));

            // Notify Staff in the same branch
            $staffMembers = StaffAccount::where('branch_id', $booking->branch_id)
                ->where('owner_account_id', $actor->id)
                ->where('active', 1)
                ->get();

            Notification::send($staffMembers, new CustomerCheckinStaffNotification(
                $customerCheckin,
                $booking,
                $branch,
                $customer,
                $actor,
                'extend_time'
            ));

            Notification::send($customer, new CustomerCheckinNotification(
                $customerCheckin,
                $booking,
                $branch,
                $customer,
                $actor,
                'extend_time'
            ));

            $message = 'Time extended successfully!';

            \Log::info('Time extension completed successfully');

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'redirect_url' => url()->previous(),
                    'updated_checkin' => [
                        'extended_time_used' => $customerCheckin->extended_time_used,
                        'total_time_used' => $customerCheckin->total_time_used,
                        'extended_time_used_formatted' => $this->formatDuration($customerCheckin->extended_time_used),
                        'total_time_used_formatted' => $this->formatDuration($customerCheckin->total_time_used)
                    ]
                ]);
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = 'Failed to extend time: ' . $e->getMessage();
            \Log::error('Error in extendTime:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }

            return redirect()->back()->with('error', $errorMessage);
        }
    }

    // Add this temporary method to your controller to check database structure

    public function checkDatabaseStructure()
    {
        try {
            $connection = DB::connection();
            $schema = $connection->getSchemaBuilder();

            $columns = [
                'created_by',
                'created_by_type',
                'date_created',
                'updated_by',
                'updated_by_type',
                'date_updated',
                'last_updated_by',
                'last_updated_by_type',
                'last_date_updated'
            ];

            $results = [];
            foreach ($columns as $column) {
                $results[$column] = $schema->hasColumn('bookings', $column);
            }

            return response()->json([
                'success' => true,
                'columns_exist' => $results,
                'sample_booking' => Booking::select($columns)->first()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate extended time used based on extended start and end times
     */
    private function calculateExtendedTimeUsed($extendedDateStart, $extendedStartTime, $extendedDateEnd, $extendedEndTime)
    {
        try {
            $appTimezone = 'Asia/Manila';

            $extendedStart = Carbon::parse("$extendedDateStart $extendedStartTime", $appTimezone);
            $extendedEnd = Carbon::parse("$extendedDateEnd $extendedEndTime", $appTimezone);

            // Calculate difference in minutes
            $extendedMinutes = $extendedStart->diffInMinutes($extendedEnd);

            return max(0, $extendedMinutes);  // Ensure non-negative
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Generate available time slots for extension
     */
    private function generateExtendTimeSlots($customerCheckin, $existingBookings = [], $isNextDayMidnight = false, $currentEndDate, $currentEndTime, $incrementMinutes = 30)
    {
        $branch = $customerCheckin->branch;
        $booking = $customerCheckin->booking;

        // Get current time
        $now = Carbon::now();

        // Calculate the start time for extension
        if ($isNextDayMidnight) {
            // For next-day midnight bookings, start from 12:00 AM next day
            $currentTime = Carbon::parse($currentEndDate)->addDay()->startOfDay();
        } else {
            // Calculate the two possible start times for same-day bookings
            $startFromEndTime = null;
            if ($currentEndTime !== null && $currentEndTime !== '00:00:00') {
                $startFromEndTime = Carbon::parse($currentEndDate . ' ' . $currentEndTime);
            }

            $startFromCurrentTime = $now->copy();

            // Use the LATER of the two times
            if ($startFromEndTime && $startFromEndTime->gt($startFromCurrentTime)) {
                $currentTime = $startFromEndTime;
            } else {
                $currentTime = $startFromCurrentTime;
            }
        }

        $openTime = $branch->open_time ? Carbon::createFromFormat('H:i:s', $branch->open_time) : null;
        $closeTime = $branch->close_time ? Carbon::createFromFormat('H:i:s', $branch->close_time) : null;

        // If no open/close times, generate slots until end of day
        if (!$openTime || !$closeTime) {
            $closeTime = $currentTime->copy()->setTime(23, 59, 0);
        }

        $slots = [];

        $isOvernight = $closeTime && $openTime && $closeTime->lt($openTime);

        if ($isOvernight) {
            // Handle overnight schedule
            $morningEnd = $currentTime->copy()->startOfDay()->setTimeFrom($closeTime);
            $eveningStart = $currentTime->copy()->setTimeFrom($openTime);
            $eveningEnd = $currentTime->copy()->addDay()->setTimeFrom($closeTime);

            if ($currentTime->lt($morningEnd)) {
                // In morning session
                while ($currentTime <= $morningEnd) {
                    if ($currentTime->gt($now) && $this->isTimeSlotAvailable($currentTime, $customerCheckin, $existingBookings, $incrementMinutes)) {
                        $slots[] = $currentTime->format('H:i');
                    }
                    $currentTime->addMinutes($incrementMinutes);  // Use increment
                }
            }

            // Add evening session slots
            $currentTime = $eveningStart->copy()->max($currentTime);
            while ($currentTime <= $eveningEnd) {
                if ($currentTime->gt($now) && $this->isTimeSlotAvailable($currentTime, $customerCheckin, $existingBookings, $incrementMinutes)) {
                    $slots[] = $currentTime->format('H:i');
                }
                $currentTime->addMinutes($incrementMinutes);  // Use increment
            }
        } else {
            // Normal schedule
            $startTime = $currentTime;

            // For next-day midnight, use the full next day
            if ($isNextDayMidnight) {
                $closeDateTime = $currentTime->copy()->setTimeFrom($closeTime);
            } else {
                $closeDateTime = $currentTime->copy()->setTimeFrom($closeTime);

                // If close time is earlier than current time, it means next day
                if ($closeDateTime->lt($currentTime)) {
                    $closeDateTime->addDay();
                }
            }

            $currentSlot = $startTime->copy();
            while ($currentSlot <= $closeDateTime) {
                // Only include if it's not in the past and doesn't conflict
                if ($currentSlot->gt($now) && $this->isTimeSlotAvailable($currentSlot, $customerCheckin, $existingBookings, $incrementMinutes)) {
                    $slots[] = $currentSlot->format('H:i');
                }
                $currentSlot->addMinutes($incrementMinutes);  // Use increment
            }
        }

        return $slots;
    }

    /**
     * Format duration in minutes to human readable format
     */
    private function formatDuration($minutes)
    {
        $totalMinutes = (int) $minutes;

        if ($totalMinutes < 1) {
            return '0 min';
        }

        $hours = (int) floor($totalMinutes / 60);
        $remainingMinutes = $totalMinutes % 60;

        $hourText = "{$hours} hr" . ($hours !== 1 ? 's' : '');
        $minuteText = "{$remainingMinutes} min" . ($remainingMinutes !== 1 ? 's' : '');

        if ($hours > 0 && $remainingMinutes > 0) {
            return "{$hourText} : {$minuteText}";
        } elseif ($hours > 0) {
            return $hourText;
        } else {
            return $minuteText;
        }
    }

    /**
     * Verify that the authenticated owner has access to the checkin record
     */
    private function verifyOwnerAccess(CustomerCheckin $customerCheckin)
    {
        $ownerId = Auth::guard('owner')->id();

        if ($customerCheckin->branch->owner_account_id != $ownerId) {
            abort(403, 'Unauthorized access to this checkin record');
        }
    }

    /**
     * Check if time slot is available (doesn't conflict with existing bookings)
     */
    private function isTimeSlotAvailable($slotTime, $customerCheckin, $existingBookings, $durationMinutes = 30)
    {
        $slotEnd = $slotTime->copy()->addMinutes($durationMinutes);
        
        foreach ($existingBookings as $booking) {
            $bookingStart = $booking['start'];
            $bookingEnd = $booking['end'];
            
            // Check for overlap
            if ($slotTime->lt($bookingEnd) && $slotEnd->gt($bookingStart)) {
                return false;
            }
        }
        
        return true;
    }
}