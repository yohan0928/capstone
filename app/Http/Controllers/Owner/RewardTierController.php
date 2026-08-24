<?php

namespace App\Http\Controllers\Owner;

use App\Models\LoyaltyTier;
use App\Models\RedeemableItem;
use App\Models\Branch;
use App\Models\OwnerAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class LoyaltyTierController extends Controller
{
    public function index(Request $request)
    {
        $owner = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        // Get branches for filter dropdown
        $branches = Branch::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->select('id', 'branch_name')
            ->get();

        // Get redeemable items for dropdown
        $redeemableItems = RedeemableItem::where('owner_account_id', $ownerId)
            ->where('is_active', true)
            ->orderBy('item_name')
            ->get();

        // Build query
        $query = LoyaltyTier::with(['branch', 'redeemableItem'])
            ->where('owner_account_id', $ownerId)
            ->where('active', 1);

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

        // Branch filter
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Get paginated results
        $loyaltyTiers = $query->orderBy('reward_required', 'asc')
            ->paginate(50);

        return view('owner.loyalty.tiers', compact('loyaltyTiers', 'branches', 'redeemableItems'));
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

            $owner = Auth::guard('owner')->user();

            $loyaltyTier = LoyaltyTier::create([
                'owner_account_id' => $owner->id,
                'branch_id' => $validated['branch_id'] ?? null,
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
                'created_by' => $owner->id,
                'created_by_type' => 'owner',
                'date_created' => now(),
                'last_updated_by' => $owner->id,
                'last_updated_by_type' => 'owner',
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

            $owner = Auth::guard('owner')->user();

            $loyaltyTier = LoyaltyTier::where('owner_account_id', $owner->id)
                ->where('active', 1)
                ->findOrFail($id);

            $loyaltyTier->update([
                'branch_id' => $validated['branch_id'] ?? null,
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
                'last_updated_by' => $owner->id,
                'last_updated_by_type' => 'owner',
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

            $owner = Auth::guard('owner')->user();

            $loyaltyTier = LoyaltyTier::where('owner_account_id', $owner->id)
                ->where('active', 1)
                ->findOrFail($id);

            $loyaltyTier->update([
                'reward_tier_status' => $loyaltyTier->reward_tier_status ? 0 : 1,
                'last_updated_by' => $owner->id,
                'last_updated_by_type' => 'owner',
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

            $owner = Auth::guard('owner')->user();

            $loyaltyTier = LoyaltyTier::where('owner_account_id', $owner->id)
                ->where('active', 1)
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
                'last_updated_by' => $owner->id,
                'last_updated_by_type' => 'owner',
                'last_date_updated' => now(),
            ]);

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
            $owner = Auth::guard('owner')->user();

            $tier = LoyaltyTier::where('owner_account_id', $owner->id)
                ->where('active', 1)
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
        $owner = Auth::guard('owner')->user();

        $query = RedeemableItem::where('owner_account_id', $owner->id)
            ->where('is_active', true)
            ->orderBy('item_name');

        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('branch_id', $request->branch_id)
                  ->orWhereNull('branch_id');
            });
        }

        $items = $query->get(['id', 'item_name', 'item_type', 'monetary_value', 'discount_percentage']);

        return response()->json([
            'success' => true,
            'data' => $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->item_name,
                    'type' => $item->item_type,
                    'type_label' => $item->type_label,
                    'value_display' => $item->value_display
                ];
            })
        ]);
    }
}