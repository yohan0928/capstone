<?php

namespace App\Http\Controllers;

use App\Models\OwnerAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuperAdminController extends Controller
{
    // Show all owner accounts
    public function showOwners(Request $request)
    {
        $query = OwnerAccount::query();

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Filter by account status
        if ($request->filled('account_status') && $request->account_status !== '') {
            $query->where('account_status', $request->account_status);
        }

        $owners = $query->orderBy('date_joined', 'desc')->paginate(10);

        // Statistics
        $totalOwners = OwnerAccount::count();
        $verifiedOwners = OwnerAccount::where('account_status', 1)->count();
        $suspendedOwners = OwnerAccount::where('account_status', 0)->count();
        $pendingOwners = OwnerAccount::where('account_status', 2)->count();

        $stats = [
            'total_owners' => $totalOwners,
            'verified_owners' => $verifiedOwners,
            'suspended_owners' => $suspendedOwners,
            'pending_owners' => $pendingOwners,
        ];

        // Return JSON for AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $owners->items(),
                'pagination' => [
                    'current_page' => $owners->currentPage(),
                    'last_page' => $owners->lastPage(),
                    'per_page' => $owners->perPage(),
                    'total' => $owners->total(),
                    'from' => $owners->firstItem(),
                    'to' => $owners->lastItem(),
                ],
                'stats' => $stats,
            ]);
        }

        return view('super_admin.owners', compact('owners', 'stats'));
    }

    // Update owner account status
    public function updateOwnerAccountStatus(Request $request, $owner_uuid)
    {
        $validated = $request->validate([
            'account_status' => 'required|in:0,1,2',  // 0=suspended, 1=verified, 2=pending
        ]);

        $owner = OwnerAccount::where('uuid', $owner_uuid)->firstOrFail();

        if ($owner->account_status == $validated['account_status']) {
            return back()->with('info', 'No changes detected.');
        }

        $oldStatus = $owner->account_status;
        
        // Update status
        $owner->account_status = $validated['account_status'];
        
        // Update date_deactivated if suspending the account
        if ($validated['account_status'] == 0) {
            $owner->date_deactivated = now();
        } elseif ($oldStatus == 0 && $validated['account_status'] != 0) {
            // If reactivating from suspended, clear date_deactivated
            $owner->date_deactivated = null;
        }
        
        $owner->save();

        return redirect()
            ->route('main.showOwners')
            ->with('toast', [
                'type' => 'success',
                'message' => 'Owner account status updated successfully.'
            ]);
    }
}