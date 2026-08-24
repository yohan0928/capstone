<?php

namespace App\Http\Controllers\Staff;

use App\Models\LoyaltyTier;
use App\Models\RedeemableItem;
use App\Models\Branch;
use App\Models\StaffAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class LoyaltyTierController extends Controller
{
    /**
     * Display a listing of loyalty tiers for staff's branch
     */
    public function index(Request $request)
    {
        $staff = Auth::guard('staff')->user();
        $staffId = $staff->id;
        $branchId = $staff->branch_id;
        $ownerId = $staff->owner_account_id;

        // Get the staff's branch
        $branches = Branch::where('owner_account_id', $ownerId)
            ->where('id', $branchId)
            ->where('active', 1)
            ->select('id', 'branch_name')
            ->get();

        // Get redeemable items for this branch (or global)
        $redeemableItems = RedeemableItem::where('owner_account_id', $ownerId)
            ->where('is_active', true)
            ->where(function($query) use ($branchId) {
                $query->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
            })
            ->orderBy('item_name')
            ->get();

        // Query loyalty tiers for this branch (or global)
        $query = LoyaltyTier::with(['branch', 'redeemableItem'])
            ->where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->where(function($query) use ($branchId) {
                $query->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
            });

        // Apply filters
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('tier_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('reward_description', 'LIKE', "%{$searchTerm}%");
            });
        }

        if ($request->filled('reward_type')) {
            $query->where('reward_type', $request->reward_type);
        }

        if ($request->filled('status')) {
            $query->where('reward_tier_status', $request->status);
        }

        $loyaltyTiers = $query
            ->orderBy('reward_required')
            ->paginate(50);

        // Pass tiers data as JSON for JavaScript (for edit modal)
        $tiersData = $loyaltyTiers->map(function ($tier) {
            return [
                'id' => $tier->id,
                'tier_name' => $tier->tier_name,
                'reward_required' => $tier->reward_required,
                'reward_description' => $tier->reward_description,
                'branch_id' => $tier->branch_id,
                'redeemable_item_id' => $tier->redeemable_item_id,
                'date_start' => $tier->date_start,
                'date_end' => $tier->date_end,
                'start_time' => $tier->start_time,
                'end_time' => $tier->end_time,
                'expiry_duration' => $tier->expiry_duration,
                'voucher_prefix' => $tier->voucher_prefix,
                'reward_tier_status' => $tier->reward_tier_status
            ];
        });

        return view('staff.loyalty.tiers', compact('loyaltyTiers', 'branches', 'redeemableItems', 'tiersData'));
    }

    /**
     * Store a newly created loyalty tier
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tier_name' => 'required|string|max:255',
            'reward_required' => 'required|integer|min:1',
            'reward_description' => 'required|string|max:500',
            'redeemable_item_id' => 'nullable|exists:redeemable_items,id',
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'expiry_duration' => 'nullable|integer|min:1|max:365',
            'voucher_prefix' => 'nullable|string|max:10'
        ]);

        try {
            DB::beginTransaction();

            $staff = Auth::guard('staff')->user();
            $branchId = $staff->branch_id;
            $ownerId = $staff->owner_account_id;

            $loyaltyTier = LoyaltyTier::create([
                'owner_account_id' => $ownerId,
                'branch_id' => $branchId,
                'tier_name' => $validated['tier_name'],
                'reward_required' => $validated['reward_required'],
                'reward_description' => $validated['reward_description'],
                'redeemable_item_id' => $validated['redeemable_item_id'] ?: null,
                'date_start' => $validated['date_start'] ?: null,
                'date_end' => $validated['date_end'] ?: null,
                'start_time' => $validated['start_time'] ?: null,
                'end_time' => $validated['end_time'] ?: null,
                'expiry_duration' => $validated['expiry_duration'] ?? 30,
                'voucher_prefix' => $validated['voucher_prefix'] ?? 'RWD',
                'reward_tier_status' => 1,
                'created_by' => $staff->id,
                'created_by_type' => 'staff',
                'date_created' => now(),
                'last_updated_by' => $staff->id,
                'last_updated_by_type' => 'staff',
                'last_date_updated' => now(),
                'active' => 1,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Loyalty tier created successfully',
                'data' => $loyaltyTier->load(['branch', 'redeemableItem'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating loyalty tier: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create loyalty tier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified loyalty tier
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'tier_name' => 'required|string|max:255',
            'reward_required' => 'required|integer|min:1',
            'reward_description' => 'required|string|max:500',
            'redeemable_item_id' => 'nullable|exists:redeemable_items,id',
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'expiry_duration' => 'nullable|integer|min:1|max:365',
            'voucher_prefix' => 'nullable|string|max:10',
            'reward_tier_status' => 'required|in:0,1'
        ]);

        try {
            DB::beginTransaction();

            $staff = Auth::guard('staff')->user();
            $branchId = $staff->branch_id;
            $ownerId = $staff->owner_account_id;

            $loyaltyTier = LoyaltyTier::where('owner_account_id', $ownerId)
                ->where('active', 1)
                ->where(function($query) use ($branchId) {
                    $query->where('branch_id', $branchId)
                          ->orWhereNull('branch_id');
                })
                ->findOrFail($id);

            $loyaltyTier->update([
                'tier_name' => $validated['tier_name'],
                'reward_required' => $validated['reward_required'],
                'reward_description' => $validated['reward_description'],
                'redeemable_item_id' => $validated['redeemable_item_id'] ?: null,
                'date_start' => $validated['date_start'] ?: null,
                'date_end' => $validated['date_end'] ?: null,
                'start_time' => $validated['start_time'] ?: null,
                'end_time' => $validated['end_time'] ?: null,
                'expiry_duration' => $validated['expiry_duration'] ?? 30,
                'voucher_prefix' => $validated['voucher_prefix'] ?? 'RWD',
                'reward_tier_status' => $validated['reward_tier_status'],
                'last_updated_by' => $staff->id,
                'last_updated_by_type' => 'staff',
                'last_date_updated' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Loyalty tier updated successfully',
                'data' => $loyaltyTier->load(['branch', 'redeemableItem'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating loyalty tier: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update loyalty tier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tier data for editing
     */
    public function getTierData($id)
    {
        try {
            Log::info('getTierData called for ID: ' . $id);
            
            $staff = Auth::guard('staff')->user();
            $branchId = $staff->branch_id;
            $ownerId = $staff->owner_account_id;
            
            if (!$staff) {
                Log::warning('No authenticated staff found');
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please log in again.'
                ], 401);
            }

            Log::info('Staff ID: ' . $staff->id . ', Branch ID: ' . $branchId);

            $tier = LoyaltyTier::where('owner_account_id', $ownerId)
                ->where('active', 1)
                ->where(function($query) use ($branchId) {
                    $query->where('branch_id', $branchId)
                          ->orWhereNull('branch_id');
                })
                ->with(['branch', 'redeemableItem'])
                ->find($id);

            if (!$tier) {
                Log::warning('Tier not found for ID: ' . $id);
                return response()->json([
                    'success' => false,
                    'message' => 'Tier not found'
                ], 404);
            }

            Log::info('Tier found successfully');

            return response()->json([
                'success' => true,
                'data' => $tier
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getTierData: ' . $e->getMessage());
            Log::error('Error trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error loading tier data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle tier status
     */
    public function toggleStatus(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $staff = Auth::guard('staff')->user();
            $branchId = $staff->branch_id;
            $ownerId = $staff->owner_account_id;

            $loyaltyTier = LoyaltyTier::where('owner_account_id', $ownerId)
                ->where('active', 1)
                ->where(function($query) use ($branchId) {
                    $query->where('branch_id', $branchId)
                          ->orWhereNull('branch_id');
                })
                ->findOrFail($id);

            $loyaltyTier->update([
                'reward_tier_status' => $loyaltyTier->reward_tier_status ? 0 : 1,
                'last_updated_by' => $staff->id,
                'last_updated_by_type' => 'staff',
                'last_date_updated' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Loyalty tier status updated successfully',
                'data' => $loyaltyTier
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error toggling status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete tier
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $staff = Auth::guard('staff')->user();
            $branchId = $staff->branch_id;
            $ownerId = $staff->owner_account_id;

            $loyaltyTier = LoyaltyTier::where('owner_account_id', $ownerId)
                ->where('active', 1)
                ->where(function($query) use ($branchId) {
                    $query->where('branch_id', $branchId)
                          ->orWhereNull('branch_id');
                })
                ->findOrFail($id);

            // Check if tier is being used by any customer rewards
            if ($loyaltyTier->customerRewards()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this tier as it has customer rewards associated with it.'
                ], 400);
            }

            $loyaltyTier->update([
                'active' => 0,
                'last_updated_by' => $staff->id,
                'last_updated_by_type' => 'staff',
                'last_date_updated' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Loyalty tier deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting tier: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete tier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get redeemable items for dropdown (AJAX)
     */
    public function getRedeemableItems(Request $request)
    {
        try {
            $staff = Auth::guard('staff')->user();
            $branchId = $staff->branch_id;
            $ownerId = $staff->owner_account_id;
            
            if (!$staff) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $query = RedeemableItem::where('owner_account_id', $ownerId)
                ->where('is_active', true)
                ->where(function($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
                })
                ->orderBy('item_name');

            $items = $query->get(['id', 'item_name', 'item_type', 'monetary_value', 'discount_percentage', 'branch_id']);

            return response()->json([
                'success' => true,
                'data' => $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->item_name,
                        'type' => $item->item_type,
                        'type_label' => $item->type_label,
                        'value_display' => $item->value_display,
                        'branch_id' => $item->branch_id
                    ];
                })
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting redeemable items: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading redeemable items: ' . $e->getMessage()
            ], 500);
        }
    }
}