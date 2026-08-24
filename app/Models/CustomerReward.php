<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CustomerReward extends Model
{
    // Constants for claim status
    const CLAIM_STATUS_DECLINED = 0;
    const CLAIM_STATUS_CLAIMED = 1;
    const CLAIM_STATUS_PENDING = 2;
    const CLAIM_STATUS_EXPIRED = 3;

    // Constants for redemption status - Using string values to match ENUM
    const REDEMPTION_STATUS_PENDING = 'pending';
    const REDEMPTION_STATUS_READY = 'ready';
    const REDEMPTION_STATUS_REDEEMED = 'redeemed';
    const REDEMPTION_STATUS_CANCELLED = 'cancelled';

    protected $table = 'customer_rewards';
    
    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'customer_account_id',
        'loyalty_tier_id',
        'voucher_code',
        'monetary_value',
        'branch_id',
        'claim_status',
        'redemption_status',
        'expiration_date',
        'decline_reason',
        'redeemed_at',
        'redeemed_at_branch_id',
        'date_created',
        'date_updated',
        'active'
    ];

    protected $casts = [
        'monetary_value' => 'decimal:2',
        'expiration_date' => 'datetime',
        'redeemed_at' => 'datetime',
        'active' => 'boolean',
        'claim_status' => 'integer',
        'redemption_status' => 'string'
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(CustomerAccount::class, 'customer_account_id');
    }

    public function loyaltyTier()
    {
        return $this->belongsTo(LoyaltyTier::class, 'loyalty_tier_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function redeemedAtBranch()
    {
        return $this->belongsTo(Branch::class, 'redeemed_at_branch_id');
    }

    /**
     * Get the redemptions for this customer reward.
     * A customer reward can have multiple redemption records (though typically one).
     */
    public function redemptions()
    {
        return $this->hasMany(RewardRedemption::class, 'customer_reward_id');
    }

    /**
     * Get the latest redemption for this customer reward.
     */
    public function latestRedemption()
    {
        return $this->hasOne(RewardRedemption::class, 'customer_reward_id')
                    ->where('active', 1)
                    ->latest('redeemed_at');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function scopePending($query)
    {
        return $query->where('claim_status', self::CLAIM_STATUS_PENDING);
    }

    public function scopeClaimed($query)
    {
        return $query->where('claim_status', self::CLAIM_STATUS_CLAIMED);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expiration_date')
              ->orWhere('expiration_date', '>', now());
        });
    }

    public function scopeReadyForRedemption($query)
    {
        return $query->where('redemption_status', self::REDEMPTION_STATUS_READY)
                    ->where('claim_status', self::CLAIM_STATUS_CLAIMED)
                    ->where('active', 1);
    }

    public function scopeRedeemed($query)
    {
        return $query->where('redemption_status', self::REDEMPTION_STATUS_REDEEMED)
                    ->where('active', 1);
    }

    // Helper Methods
    public function isExpired()
    {
        if (!$this->expiration_date) {
            return false;
        }
        return now()->gt($this->expiration_date);
    }

    public function getDaysLeftAttribute()
    {
        if (!$this->expiration_date) {
            return null;
        }
        return max(0, now()->diffInDays($this->expiration_date, false));
    }

    public function getStatusLabelAttribute()
    {
        if ($this->isExpired()) {
            return 'Expired';
        }

        $statuses = [
            self::CLAIM_STATUS_DECLINED => 'Declined',
            self::CLAIM_STATUS_CLAIMED => 'Claimed',
            self::CLAIM_STATUS_PENDING => 'Pending Approval',
            self::CLAIM_STATUS_EXPIRED => 'Expired'
        ];

        return $statuses[$this->claim_status] ?? 'Unknown';
    }

    public function getRedemptionStatusLabelAttribute()
    {
        $statuses = [
            self::REDEMPTION_STATUS_PENDING => 'Pending',
            self::REDEMPTION_STATUS_READY => 'Ready for Redemption',
            self::REDEMPTION_STATUS_REDEEMED => 'Redeemed',
            self::REDEMPTION_STATUS_CANCELLED => 'Cancelled'
        ];

        return $statuses[$this->redemption_status] ?? 'Unknown';
    }

    public function getStatusBadgeClassAttribute()
    {
        if ($this->isExpired()) {
            return 'bg-gray-100 text-gray-800';
        }

        $classes = [
            self::CLAIM_STATUS_DECLINED => 'bg-red-100 text-red-800',
            self::CLAIM_STATUS_CLAIMED => 'bg-green-100 text-green-800',
            self::CLAIM_STATUS_PENDING => 'bg-yellow-100 text-yellow-800',
            self::CLAIM_STATUS_EXPIRED => 'bg-gray-100 text-gray-800'
        ];

        return $classes[$this->claim_status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getMonetaryValueDisplayAttribute()
    {
        if (!$this->monetary_value) {
            return 'N/A';
        }
        return '₱' . number_format($this->monetary_value, 2);
    }

    /**
     * Check if the reward is ready for redemption
     */
    public function isReadyForRedemption()
    {
        return $this->redemption_status === self::REDEMPTION_STATUS_READY 
            && $this->claim_status === self::CLAIM_STATUS_CLAIMED 
            && !$this->isExpired()
            && $this->active == 1;
    }

    /**
     * Check if the reward has been redeemed
     */
    public function isRedeemed()
    {
        return $this->redemption_status === self::REDEMPTION_STATUS_REDEEMED;
    }

    /**
     * Get the redemption details with proper formatting
     */
    public function getRedemptionDetailsAttribute()
    {
        if ($this->redemptions && $this->redemptions->isNotEmpty()) {
            return $this->redemptions->first();
        }
        return null;
    }

    // Boot method for UUID
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->date_created)) {
                $model->date_created = now();
            }
            if (empty($model->active)) {
                $model->active = 1;
            }
        });

        static::updating(function ($model) {
            $model->date_updated = now();
        });
    }
}