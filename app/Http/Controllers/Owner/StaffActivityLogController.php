<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\StaffAccount;
use App\Models\StaffActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StaffActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $owner = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        // Get all branches owned by this owner
        $branchIds = Branch::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->pluck('id');

        // Base query
        $query = StaffActivityLog::with([
                'staff' => function ($q) {
                    $q->select('id', 'first_name', 'last_name', 'email');
                },
                'branch' => function ($q) {
                    $q->select('id', 'branch_name', 'uuid');
                },
                'booking' => function ($q) {
                    $q->select('id', 'booking_ref_no', 'customer_account_id');
                }
            ])
            ->where('owner_account_id', $ownerId)
            ->whereIn('branch_id', $branchIds);

        // Filters - Use uuid instead of branch_id
        if ($request->filled('uuid')) {
            $query->whereHas('branch', function ($q) use ($request) {
                $q->where('uuid', $request->uuid);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('staff', function ($q) use ($searchTerm) {
                    $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$searchTerm}%")
                      ->orWhere('email', 'LIKE', "%{$searchTerm}%");
                })
                ->orWhereHas('booking', function ($q) use ($searchTerm) {
                    $q->where('booking_ref_no', 'LIKE', "%{$searchTerm}%");
                })
                ->orWhere('description', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Order by latest first
        $logs = $query->orderBy('created_at', 'desc')->paginate(50);

        // Get filter options
        $branches = Branch::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->orderBy('branch_name')
            ->get();

        $actionTypes = StaffActivityLog::getActionLabels();

        // Stats - need to use base query without pagination
        $statsQuery = StaffActivityLog::where('owner_account_id', $ownerId)
            ->whereIn('branch_id', $branchIds);
            
        // Apply same filters to stats query
        if ($request->filled('uuid')) {
            $statsQuery->whereHas('branch', function ($q) use ($request) {
                $q->where('uuid', $request->uuid);
            });
        }
        if ($request->filled('date_from')) {
            $statsQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $statsQuery->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $statsQuery->where(function ($q) use ($searchTerm) {
                $q->whereHas('staff', function ($q) use ($searchTerm) {
                    $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$searchTerm}%")
                      ->orWhere('email', 'LIKE', "%{$searchTerm}%");
                })
                ->orWhereHas('booking', function ($q) use ($searchTerm) {
                    $q->where('booking_ref_no', 'LIKE', "%{$searchTerm}%");
                })
                ->orWhere('description', 'LIKE', "%{$searchTerm}%");
            });
        }

        $stats = [
            'total_actions' => $statsQuery->count(),
            'today_actions' => $statsQuery->clone()->whereDate('created_at', today())->count(),
            'yesterday_actions' => $statsQuery->clone()->whereDate('created_at', today()->subDay())->count(),
            'this_week_actions' => $statsQuery->clone()->whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
        ];

        // Group by action type for chart
        $actionsByType = $statsQuery->clone()
            ->select('action_type', DB::raw('COUNT(*) as count'))
            ->groupBy('action_type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->action_type => $item->count];
            });

        // For AJAX requests, return JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $logs->items(),
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                    'from' => $logs->firstItem(),
                    'to' => $logs->lastItem(),
                ],
                'stats' => $stats,
                'actions_by_type' => $actionsByType,
            ]);
        }

        return view('owner.staff.activity_logs', compact(
            'logs', 'branches', 'actionTypes', 'stats', 'actionsByType'
        ));
    }

    public function getLogDetails(StaffActivityLog $log)
    {
        $owner = Auth::guard('owner')->user();
        
        // Security check
        if ($log->owner_account_id !== $owner->id) {
            abort(403, 'Unauthorized access');
        }

        $log->load([
            'staff' => function ($q) {
                $q->select('id', 'first_name', 'last_name', 'email', 'contact_no');
            },
            'branch' => function ($q) {
                $q->select('id', 'branch_name', 'location', 'uuid');
            },
            'booking' => function ($q) {
                $q->with(['customerAccount' => function ($q) {
                    $q->select('id', 'first_name', 'last_name', 'email');
                }]);
            }
        ]);

        return response()->json([
            'success' => true,
            'log' => [
                'id' => $log->id,
                'action_label' => $log->getActionLabel(),
                'description' => $log->description,
                'staff' => $log->staff ? [
                    'name' => $log->staff->first_name . ' ' . $log->staff->last_name,
                    'email' => $log->staff->email,
                    'contact_no' => $log->staff->contact_no
                ] : null,
                'branch' => $log->branch ? [
                    'name' => $log->branch->branch_name,
                    'address' => $log->branch->address,
                    'uuid' => $log->branch->uuid
                ] : null,
                'booking' => $log->booking ? [
                    'ref_no' => $log->booking->booking_ref_no,
                    'customer' => $log->booking->customerAccount ? [
                        'name' => $log->booking->customerAccount->first_name . ' ' . $log->booking->customerAccount->last_name,
                        'email' => $log->booking->customerAccount->email
                    ] : null
                ] : null,
                'metadata' => $log->getFormattedMetadata(),
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at->format('M j, Y g:i A'),
                'created_at_relative' => $log->created_at->diffForHumans()
            ]
        ]);
    }

    public function export(Request $request)
    {
        $owner = Auth::guard('owner')->user();
        
        $query = StaffActivityLog::with(['staff', 'branch', 'booking.customerAccount'])
            ->where('owner_account_id', $owner->id);

        // Apply filters if any - use uuid
        if ($request->filled('uuid')) {
            $query->whereHas('branch', function ($q) use ($request) {
                $q->where('uuid', $request->uuid);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="staff_activity_logs_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fwrite($file, "\xEF\xBB\xBF");
            
            // Headers
            fputcsv($file, [
                'Date & Time',
                'Staff Name',
                'Branch',
                'Action',
                'Description',
                'Booking Ref',
                'Customer',
                'IP Address',
                'User Agent'
            ]);

            // Data rows
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->staff ? $log->staff->first_name . ' ' . $log->staff->last_name : 'N/A',
                    $log->branch ? $log->branch->branch_name : 'N/A',
                    $log->getActionLabel(),
                    $log->description,
                    $log->booking ? $log->booking->booking_ref_no : 'N/A',
                    $log->booking && $log->booking->customerAccount 
                        ? $log->booking->customerAccount->first_name . ' ' . $log->booking->customerAccount->last_name
                        : 'N/A',
                    $log->ip_address ?? 'N/A',
                    $log->user_agent ?? 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}