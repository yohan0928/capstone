<?php

namespace App\Http\Controllers\Customer;

use Carbon\Carbon;
use App\Models\Branch;
use App\Models\Booking;
use App\Models\StaffAccount;
use Illuminate\Http\Request;
use App\Models\CustomerAccount;
use App\Models\CustomerCheckin;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Staff\CustomerCheckinStaffNotification;
use App\Notifications\Customer\CustomerCheckinCustomerNotification;

class CustomerCheckinController extends Controller
{
    public function index(Request $request)
    {
        // Logged in as Customer
        $customer = Auth::guard('customer')->user();
        $customerId = $customer->id;

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

        // Get customer checkins with related data and pagination - ONLY current customer's data
        $query = CustomerCheckin::with([
            'booking' => function ($query) {
                $query->select('id', 'uuid', 'booking_ref_no', 'customer_account_id', 'start_time', 'date_start', 'booking_type', 'end_time', 'date_end');
            },
            'branch' => function ($query) {
                $query->select('id', 'uuid', 'branch_name');
            },
            'serviceCategory' => function ($query) {
                $query->select('id', 'service_category');
            },
            'serviceName' => function ($query) {
                $query->select('id', 'service_name', 'time_duration');
            },
            'seat' => function ($query) {
                $query->select('id', 'seat_no');
            },
            'customerAccount' => function ($query) {
                $query->select('id', 'uuid', 'first_name', 'last_name', 'email');
            }
        ])
            ->where('customer_account_id', $customerId)  // Only current customer's data
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

        // Search by booking reference only (customer can't search other customers)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->whereHas('booking', function ($q) use ($searchTerm) {
                $q->where('booking_ref_no', 'LIKE', "%{$searchTerm}%");
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

        // Default to today's data if no date filters are applied and no search is performed
        if (
            !$request->has('date_start') &&
            !$request->has('date_end') &&
            !$request->has('checkin_status') &&
            !$request->filled('search') &&
            !$request->filled('brn')
        ) {
            $today = Carbon::today()->format('Y-m-d');
            $query->whereDate('date_checked_in', $today);
        }

        $customerCheckins = $query
            ->orderBy('checkin_status', 'desc')
            ->orderBy('date_checked_in', 'desc')
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

            // Format dates and times for display
            $checkin->formatted_date_checked_in = $checkin->date_checked_in ? Carbon::parse($checkin->date_checked_in)->format('M d, Y') : 'N/A';
            $checkin->formatted_time_checked_in = $checkin->time_checked_in ? $this->formatTimeTo12Hour($checkin->time_checked_in) : 'N/A';
            
            // Format booking times
            if ($checkin->booking) {
                $checkin->booking->formatted_start_time = $checkin->booking->start_time ? $this->formatTimeTo12Hour($checkin->booking->start_time) : 'N/A';
                $checkin->booking->formatted_end_time = $checkin->booking->end_time ? $this->formatTimeTo12Hour($checkin->booking->end_time) : 'N/A';
                $checkin->booking->formatted_date_start = $checkin->booking->date_start ? Carbon::parse($checkin->booking->date_start)->format('M d, Y') : 'N/A';
                $checkin->booking->formatted_date_end = $checkin->booking->date_end ? Carbon::parse($checkin->booking->date_end)->format('M d, Y') : 'N/A';
            }

            return $checkin;
        });

        // Get statistics for the dashboard cards - ONLY current customer's data
        $statsQuery = CustomerCheckin::where('customer_account_id', $customerId)
            ->where('active', 1);

        // Apply same filters to stats
        if ($request->filled('brn') && !$request->has('clear_brn')) {
            $brn = $request->brn;
            $statsQuery->whereHas('booking', function ($q) use ($brn) {
                $q->where('booking_ref_no', $brn);
            });
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $statsQuery->whereHas('booking', function ($q) use ($searchTerm) {
                $q->where('booking_ref_no', 'LIKE', "%{$searchTerm}%");
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

        // Default to today's data if no filters are applied and no search is performed
        if (
            !$request->has('date_start') &&
            !$request->has('date_end') &&
            !$request->has('checkin_status') &&
            !$request->filled('search') &&
            !$request->filled('brn')
        ) {
            $today = Carbon::today()->format('Y-m-d');
            $statsQuery->whereDate('date_checked_in', $today);
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

        return view('customer.my_checkins.index', compact('customerCheckins', 'stats', 'scannedBrn'));
    }

    /**
     * Show checkin details (read-only for customers)
     */
    public function show($id)
    {
        try {
            $customer = Auth::guard('customer')->user();
            
            $customerCheckin = CustomerCheckin::with([
                'booking' => function ($query) {
                    $query->select('id', 'uuid', 'booking_ref_no', 'customer_account_id', 'start_time', 'date_start', 'booking_type', 'end_time', 'date_end', 'extended_start_time', 'extended_end_time', 'extended_date_start', 'extended_date_end');
                },
                'branch' => function ($query) {
                    $query->select('id', 'uuid', 'branch_name', 'open_time', 'close_time');
                },
                'serviceCategory' => function ($query) {
                    $query->select('id', 'service_category');
                },
                'serviceName' => function ($query) {
                    $query->select('id', 'service_name', 'time_duration');
                },
                'seat' => function ($query) {
                    $query->select('id', 'seat_no');
                },
                'customerAccount' => function ($query) {
                    $query->select('id', 'uuid', 'first_name', 'last_name', 'email');
                }
            ])
            ->where('customer_account_id', $customer->id)
            ->where('active', 1)
            ->findOrFail($id);

            // Format all dates and times for display
            $customerCheckin->formatted_date_checked_in = $customerCheckin->date_checked_in ? Carbon::parse($customerCheckin->date_checked_in)->format('M d, Y') : 'N/A';
            $customerCheckin->formatted_time_checked_in = $customerCheckin->time_checked_in ? $this->formatTimeTo12Hour($customerCheckin->time_checked_in) : 'N/A';
            
            // Format booking times
            if ($customerCheckin->booking) {
                $customerCheckin->booking->formatted_start_time = $customerCheckin->booking->start_time ? $this->formatTimeTo12Hour($customerCheckin->booking->start_time) : 'N/A';
                $customerCheckin->booking->formatted_end_time = $customerCheckin->booking->end_time ? $this->formatTimeTo12Hour($customerCheckin->booking->end_time) : 'N/A';
                $customerCheckin->booking->formatted_date_start = $customerCheckin->booking->date_start ? Carbon::parse($customerCheckin->booking->date_start)->format('M d, Y') : 'N/A';
                $customerCheckin->booking->formatted_date_end = $customerCheckin->booking->date_end ? Carbon::parse($customerCheckin->booking->date_end)->format('M d, Y') : 'N/A';
                
                // Format extended times if they exist
                if ($customerCheckin->booking->extended_start_time) {
                    $customerCheckin->booking->formatted_extended_start_time = $this->formatTimeTo12Hour($customerCheckin->booking->extended_start_time);
                    $customerCheckin->booking->formatted_extended_end_time = $this->formatTimeTo12Hour($customerCheckin->booking->extended_end_time);
                    $customerCheckin->booking->formatted_extended_date_start = Carbon::parse($customerCheckin->booking->extended_date_start)->format('M d, Y');
                    $customerCheckin->booking->formatted_extended_date_end = Carbon::parse($customerCheckin->booking->extended_date_end)->format('M d, Y');
                }
            }

            // Format branch times
            if ($customerCheckin->branch) {
                $customerCheckin->branch->formatted_open_time = $customerCheckin->branch->open_time ? $this->formatTimeTo12Hour($customerCheckin->branch->open_time) : 'N/A';
                $customerCheckin->branch->formatted_close_time = $customerCheckin->branch->close_time ? $this->formatTimeTo12Hour($customerCheckin->branch->close_time) : 'N/A';
            }

            // Format time used
            $customerCheckin->time_used_formatted = $this->formatDuration($customerCheckin->time_used ?? 0);
            $customerCheckin->extended_time_used_formatted = $this->formatDuration($customerCheckin->extended_time_used ?? 0);
            $customerCheckin->total_time_used_formatted = $this->formatDuration($customerCheckin->total_time_used ?? 0);

            return response()->json([
                'success' => true,
                'checkin' => $customerCheckin
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Check-in record not found.'
            ], 404);
        }
    }

    /**
     * Verify that the authenticated customer has access to the checkin record (same customer)
     */
    private function verifyCustomerAccess(CustomerCheckin $customerCheckin)
    {
        $customer = Auth::guard('customer')->user();

        if ($customerCheckin->customer_account_id !== $customer->id) {
            abort(403, 'Unauthorized access to this checkin record');
        }
    }

    /**
     * Format time from 24-hour format to 12-hour format with AM/PM
     */
    private function formatTimeTo12Hour($time)
    {
        try {
            if (!$time || $time === '00:00:00' || $time === '00:00') {
                return '12:00 AM';
            }

            if (str_contains($time, ':')) {
                if (strlen($time) > 5) {
                    $carbonTime = Carbon::createFromFormat('H:i:s', $time);
                } else {
                    $carbonTime = Carbon::createFromFormat('H:i', $time);
                }
                return $carbonTime->format('h:i A');
            }
            return $time;
        } catch (\Exception $e) {
            return $time;
        }
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
}