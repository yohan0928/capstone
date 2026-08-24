<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoyaltyTier extends Model
{
    use SoftDeletes;

    protected $table = 'loyalty_tiers';
    
    public $timestamps = false;

    protected $fillable = [
        'owner_account_id',
        'branch_id',
        'tier_name',
        'reward_required',
        'reward_description',
        'redeemable_item_id',
        'date_start',
        'date_end',
        'start_time',
        'end_time',
        'reward_tier_status',
        'expiry_duration',
        'voucher_prefix',
        'created_by',
        'created_by_type',
        'date_created',
        'last_updated_by',
        'last_updated_by_type',
        'last_date_updated',
        'active'
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
        'expiry_duration' => 'integer',
        'reward_tier_status' => 'integer',
        'active' => 'boolean',
        'deleted_at' => 'datetime'
    ];

    // Relationships
    public function owner()
    {
        return $this->belongsTo(OwnerAccount::class, 'owner_account_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function redeemableItem()
    {
        return $this->belongsTo(RedeemableItem::class);
    }

    public function customerRewards()
    {
        return $this->hasMany(CustomerReward::class, 'loyalty_tier_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', 1)->where('reward_tier_status', 1);
    }

    public function scopeByOwner($query, $ownerId)
    {
        return $query->where('owner_account_id', $ownerId);
    }

    // Helper Methods
    public function isCurrentlyClaimable()
    {
        $now = now();

        // Check date range
        if ($this->date_start && $now->lt($this->date_start)) {
            return false;
        }
        if ($this->date_end && $now->gt($this->date_end)) {
            return false;
        }

        // Check time range
        if ($this->start_time && $now->format('H:i:s') < $this->start_time) {
            return false;
        }
        if ($this->end_time && $now->format('H:i:s') > $this->end_time) {
            return false;
        }

        return true;
    }

    public function getMonetaryValueAttribute()
    {
        if (!$this->redeemableItem) {
            return null;
        }

        return $this->redeemableItem->monetary_value;
    }

    public function getValueDisplayAttribute()
    {
        if (!$this->redeemableItem) {
            return 'N/A';
        }

        return $this->redeemableItem->value_display;
    }

    public function getStatusLabelAttribute()
    {
        return $this->reward_tier_status ? 'Active' : 'Inactive';
    }

    public function getTypeLabelAttribute()
    {
        return $this->redeemableItem ? $this->redeemableItem->item_type : 'Custom';
    }
}