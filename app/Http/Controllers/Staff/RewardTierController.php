<?php

namespace App\Http\Controllers\Staff;

use App\Models\LoyaltyTier;
use App\Models\RedeemableItem;
use App\Models\Branch;
use App\Models\OwnerAccount;
use App\Models\StaffAccount;
use Illuminate\Http\Request;
use App\Models\CustomerReward;
use App\Services\StaffActivityLogger;
use App\Models\StaffActivityLog;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Owner\RewardTierNotification;
use App\Notifications\Staff\RewardTierStaffNotification;

class LoyaltyTierController extends Controller
{
    public function index(Request $request)
    {
        $staff = Auth::guard('staff')->user();
        $staffId = $staff->id;
        $branchId = $staff->branch_id;
        $ownerId = $staff->owner_account_id;

        // Get branches (only the staff's assigned branch)
        $branches = Branch::where('owner_account_id', $ownerId)
            ->where('id', $branchId)
            ->where('active', 1)
            ->select('id', 'branch_name')
            ->get();

        // Get redeemable items for dropdown (only for this branch or global)
        $redeemableItems = RedeemableItem::where('owner_account_id', $ownerId)
            ->where('is_active', true)
            ->where(function($query) use ($branchId) {
                $query->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
            })
            ->orderBy('item_name')
            ->get();

        // Build query for loyalty tiers
        $query = LoyaltyTier::with(['branch', 'redeemableItem'])
            ->where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->where(function($query) use ($branchId) {
                $query->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
            });

        // Search filter
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('tier_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('reward_description', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('reward_tier_status', $request->status);
        }

        // Get paginated results
        $loyaltyTiers = $query->orderBy('reward_required', 'asc')
            ->paginate(50);

        // Check if each tier is being used in CustomerRewards
        $loyaltyTiers->getCollection()->transform(function ($tier) {
            $isInUse = CustomerReward::where('loyalty_tier_id', $tier->id)
                ->where('active', 1)
                ->exists();
            
            $tier->is_in_use = $isInUse;
            return $tier;
        });

        // Return JSON for AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $loyaltyTiers->items(),
                'pagination' => [
                    'current_page' => $loyaltyTiers->currentPage(),
                    'last_page' => $loyaltyTiers->lastPage(),
                    'per_page' => $loyaltyTiers->perPage(),
                    'total' => $loyaltyTiers->total(),
                    'from' => $loyaltyTiers->firstItem(),
                    'to' => $loyaltyTiers->lastItem(),
                ],
                'branches' => $branches,
                'redeemable_items' => $redeemableItems
            ]);
        }

        return view('staff.loyalty.tiers', compact('loyaltyTiers', 'branches', 'redeemableItems'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
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
            $ownerId = $staff->owner_account_id;
            $branchId = $staff->branch_id;

            // Force branch to staff's branch if not specified
            $branchIdToUse = $validated['branch_id'] ?? $branchId;

            $loyaltyTier = LoyaltyTier::create([
                'owner_account_id' => $ownerId,
                'branch_id' => $branchIdToUse,
                'tier_name' => $validated['tier_name'],
                'reward_required' => $validated['reward_required'],
                'reward_description' => $validated['reward_description'],
                'redeemable_item_id' => $validated['redeemable_item_id'] ?? null,
                'date_start' => $validated['date_start'] ?? null,
                'date_end' => $validated['date_end'] ?? null,
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'expiry_duration' => $validated['expiry_duration'] ?? 30,
                'voucher_prefix' => $validated['voucher_prefix'] ?? null,
                'reward_tier_status' => $validated['reward_tier_status'],
                'created_by' => $staff->id,
                'created_by_type' => 'staff',
                'date_created' => now(),
                'last_updated_by' => $staff->id,
                'last_updated_by_type' => 'staff',
                'last_date_updated' => now(),
                'active' => 1,
            ]);

            // LOG: CREATE LOYALTY TIER ACTION
            StaffActivityLogger::logCreateLoyaltyTier($loyaltyTier, $request);

            DB::commit();

            // Send notification for loyalty tier creation
            $actor = $staff;
            
            // Get specific owner to notify
            $owner = OwnerAccount::find($ownerId);
            if ($owner) {
                Notification::send([$owner], new RewardTierNotification(
                    $loyaltyTier->branch, 
                    $loyaltyTier, 
                    $actor, 
                    'created'
                ));
            }

            // Notify Staff in the same branch
            $staffMembers = StaffAccount::where('branch_id', $branchId)
                ->where('owner_account_id', $ownerId)
                ->where('active', 1)
                ->get();

            Notification::send($staffMembers, new RewardTierStaffNotification(
                $loyaltyTier->branch, 
                $loyaltyTier, 
                $actor, 
                'created'
            ));

            return response()->json([
                'success' => true,
                'message' => 'Loyalty tier created successfully',
                'data' => $loyaltyTier->load(['branch', 'redeemableItem'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create loyalty tier: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
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
            $ownerId = $staff->owner_account_id;
            $branchId = $staff->branch_id;

            $loyaltyTier = LoyaltyTier::where('owner_account_id', $ownerId)
                ->where('active', 1)
                ->where(function($query) use ($branchId) {
                    $query->where('branch_id', $branchId)
                          ->orWhereNull('branch_id');
                })
                ->findOrFail($id);

            // Capture old data for logging
            $oldData = $loyaltyTier->getAttributes();

            $loyaltyTier->update([
                'branch_id' => $validated['branch_id'] ?? $branchId,
                'tier_name' => $validated['tier_name'],
                'reward_required' => $validated['reward_required'],
                'reward_description' => $validated['reward_description'],
                'redeemable_item_id' => $validated['redeemable_item_id'] ?? null,
                'date_start' => $validated['date_start'] ?? null,
                'date_end' => $validated['date_end'] ?? null,
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'expiry_duration' => $validated['expiry_duration'] ?? 30,
                'voucher_prefix' => $validated['voucher_prefix'] ?? null,
                'reward_tier_status' => $validated['reward_tier_status'],
                'last_updated_by' => $staff->id,
                'last_updated_by_type' => 'staff',
                'last_date_updated' => now(),
            ]);

            // LOG: UPDATE LOYALTY TIER ACTION
            StaffActivityLogger::logUpdateLoyaltyTier($loyaltyTier, $oldData, $request);

            DB::commit();

            // Send notification for loyalty tier update
            $actor = $staff;
            
            // Get specific owner to notify
            $owner = OwnerAccount::find($ownerId);
            if ($owner) {
                Notification::send([$owner], new RewardTierNotification(
                    $loyaltyTier->branch, 
                    $loyaltyTier, 
                    $actor, 
                    'updated'
                ));
            }

            // Notify Staff in the same branch
            $staffMembers = StaffAccount::where('branch_id', $branchId)
                ->where('owner_account_id', $ownerId)
                ->where('active', 1)
                ->get();

            Notification::send($staffMembers, new RewardTierStaffNotification(
                $loyaltyTier->branch, 
                $loyaltyTier, 
                $actor, 
                'updated'
            ));

            return response()->json([
                'success' => true,
                'message' => 'Loyalty tier updated successfully',
                'data' => $loyaltyTier->load(['branch', 'redeemableItem'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update loyalty tier: ' . $e->getMessage()
            ], 500);
        }
    }

    public function toggleStatus(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $staff = Auth::guard('staff')->user();
            $ownerId = $staff->owner_account_id;
            $branchId = $staff->branch_id;

            $loyaltyTier = LoyaltyTier::where('owner_account_id', $ownerId)
                ->where('active', 1)
                ->where(function($query) use ($branchId) {
                    $query->where('branch_id', $branchId)
                          ->orWhereNull('branch_id');
                })
                ->findOrFail($id);

            $oldStatus = $loyaltyTier->reward_tier_status;
            
            $loyaltyTier->update([
                'reward_tier_status' => $loyaltyTier->reward_tier_status ? 0 : 1,
                'last_updated_by' => $staff->id,
                'last_updated_by_type' => 'staff',
                'last_date_updated' => now(),
            ]);

            // LOG: UPDATE LOYALTY TIER STATUS ACTION
            StaffActivityLogger::logUpdateLoyaltyTierStatus(
                $loyaltyTier, 
                $oldStatus, 
                $loyaltyTier->reward_tier_status, 
                $request
            );

            DB::commit();

            $statusLabels = [
                0 => 'Inactive',
                1 => 'Active'
            ];

            $oldStatusLabel = $statusLabels[$oldStatus];
            $newStatusLabel = $statusLabels[$loyaltyTier->reward_tier_status];

            // Send notification for status change
            $actor = $staff;
            
            // Get specific owner to notify
            $owner = OwnerAccount::find($ownerId);
            if ($owner) {
                Notification::send([$owner], new RewardTierNotification(
                    $loyaltyTier->branch, 
                    $loyaltyTier, 
                    $actor, 
                    'status_changed',
                    [
                        'old_status' => $oldStatusLabel,
                        'new_status' => $newStatusLabel
                    ]
                ));
            }

            // Notify Staff in the same branch
            $staffMembers = StaffAccount::where('branch_id', $branchId)
                ->where('owner_account_id', $ownerId)
                ->where('active', 1)
                ->get();

            Notification::send($staffMembers, new RewardTierStaffNotification(
                $loyaltyTier->branch, 
                $loyaltyTier, 
                $actor, 
                'status_changed',
                [
                    'old_status' => $oldStatusLabel,
                    'new_status' => $newStatusLabel
                ]
            ));

            return response()->json([
                'success' => true,
                'message' => 'Loyalty tier status updated successfully',
                'data' => $loyaltyTier
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $staff = Auth::guard('staff')->user();
            $ownerId = $staff->owner_account_id;
            $branchId = $staff->branch_id;

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

            // LOG: DELETE LOYALTY TIER ACTION
            StaffActivityLogger::logDeleteLoyaltyTier($loyaltyTier, $request);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Loyalty tier deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete tier: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getTierData($id)
    {
        try {
            $staff = Auth::guard('staff')->user();
            $ownerId = $staff->owner_account_id;
            $branchId = $staff->branch_id;

            $tier = LoyaltyTier::where('owner_account_id', $ownerId)
                ->where('active', 1)
                ->where(function($query) use ($branchId) {
                    $query->where('branch_id', $branchId)
                          ->orWhereNull('branch_id');
                })
                ->with(['branch', 'redeemableItem'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $tier
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tier not found: ' . $e->getMessage()
            ], 404);
        }
    }

    public function getRedeemableItems(Request $request)
    {
        try {
            $staff = Auth::guard('staff')->user();
            $ownerId = $staff->owner_account_id;
            $branchId = $staff->branch_id;

            $query = RedeemableItem::where('owner_account_id', $ownerId)
                ->where('is_active', true)
                ->where(function($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
                })
                ->orderBy('item_name');

            // Filter by branch if specified
            if ($request->filled('branch_id')) {
                $query->where(function ($q) use ($request) {
                    $q->where('branch_id', $request->branch_id)
                      ->orWhereNull('branch_id');
                });
            }

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
            return response()->json([
                'success' => false,
                'message' => 'Error loading redeemable items: ' . $e->getMessage()
            ], 500);
        }
    }
}