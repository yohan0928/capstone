<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class RewardTier extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'reward_tiers';

    protected $fillable = [
        'owner_account_id',
        'branch_id',
        'reward_type', // 0=product, 1=service (streak or frequent)
        'booking_required',
        'expiry_duration',
        'reward_description',
        'redeemable_item_id',
        'discount_type', // fixed, percentage
        'discount_value',
        'voucher_prefix',
        'start_time',
        'end_time',
        'date_start',
        'date_end',
        'reward_tier_status', // 0=unavailable, 1=available
        'created_by',
        'created_by_type',
        'date_created',
        'last_updated_by',
        'last_updated_by_type',
        'last_date_updated',
        'updated_by',
        'updated_by_type',
        'date_updated',
        'active', // 0=no, 1=yes
    ];

    // Constants for reward type
    const TYPE_STREAK = 0;
    const TYPE_FREQUENT = 1;

    /**
     * Boot method for UUID generation
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            
            // Set default voucher prefix if not set
            if (empty($model->voucher_prefix)) {
                $model->voucher_prefix = 'RWD';
            }
        });
    }

    /*--------------------------------------------------------------
    | Relationships
    --------------------------------------------------------------*/

    public function owner()
    {
        return $this->belongsTo(OwnerAccount::class, 'owner_account_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function customerRewards()
    {
        return $this->hasMany(CustomerReward::class, 'reward_tier_id');
    }

    /**
     * Get the redeemable item associated with this reward tier
     */
    public function redeemableItem()
    {
        return $this->belongsTo(RedeemableItem::class, 'redeemable_item_id');
    }

    // ----------------------
    // Dynamic user accessors
    // ----------------------

    protected function resolveUser($id, $type)
    {
        if (!$id || !$type) return null;

        return match ($type) {
            'owner' => OwnerAccount::find($id),
            'staff' => StaffAccount::find($id),
            default => null,
        };
    }

    public function getCreatorAttribute()
    {
        return $this->resolveUser($this->created_by, $this->created_by_type);
    }

    public function getLastUpdatorAttribute()
    {
        return $this->resolveUser($this->last_updated_by, $this->last_updated_by_type);
    }

    public function getUpdatorAttribute()
    {
        return $this->resolveUser($this->updated_by, $this->updated_by_type);
    }

    /*--------------------------------------------------------------
    | Methods
    --------------------------------------------------------------*/

    /**
     * Get the monetary value of this reward
     */
    public function getMonetaryValue(): ?float
    {
        // If there's a redeemable item, use its monetary value
        if ($this->redeemableItem) {
            return $this->redeemableItem->monetary_value;
        }
        
        // If it's a discount, use the discount value
        if ($this->discount_type && $this->discount_value !== null) {
            return $this->discount_value;
        }
        
        return null;
    }

    /**
     * Get the voucher prefix for this tier
     */
    public function getVoucherPrefix(): string
    {
        return $this->voucher_prefix ?? 'RWD';
    }

    /**
     * Get the reward type label
     */
    public function getRewardTypeLabelAttribute(): string
    {
        return $this->reward_type == self::TYPE_STREAK ? 'Streak' : 'Frequent';
    }

    /**
     * Get the reward type class for badges
     */
    public function getRewardTypeClassAttribute(): string
    {
        return $this->reward_type == self::TYPE_STREAK 
            ? 'bg-blue-100 text-blue-800' 
            : 'bg-purple-100 text-purple-800';
    }

    /**
     * Check if this reward tier has date/time restrictions
     */
    public function hasRestrictions(): bool
    {
        return $this->date_start !== null 
            || $this->date_end !== null 
            || $this->start_time !== null 
            || $this->end_time !== null;
    }

    /**
     * Check if the reward tier is currently available based on date/time
     */
    public function isCurrentlyAvailable(): bool
    {
        $now = now();
        
        if ($this->reward_tier_status != 1 || $this->active != 1) {
            return false;
        }
        
        // Check date range
        if ($this->date_start && $now->lt(Carbon::parse($this->date_start)->startOfDay())) {
            return false;
        }
        
        if ($this->date_end && $now->gt(Carbon::parse($this->date_end)->endOfDay())) {
            return false;
        }
        
        // Check time range
        if ($this->start_time) {
            $startTime = Carbon::parse($this->start_time);
            $currentTime = Carbon::parse($now->format('H:i:s'));
            if ($currentTime->lt($startTime)) {
                return false;
            }
        }
        
        if ($this->end_time) {
            $endTime = Carbon::parse($this->end_time);
            $currentTime = Carbon::parse($now->format('H:i:s'));
            if ($currentTime->gt($endTime)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get the availability message for display
     */
    public function getAvailabilityMessage(): string
    {
        if (!$this->hasRestrictions()) {
            return 'Always Available';
        }
        
        $messages = [];
        
        // Date messages
        if ($this->date_start && $this->date_end) {
            $startDate = Carbon::parse($this->date_start);
            $endDate = Carbon::parse($this->date_end);
            
            if ($startDate->equalTo($endDate)) {
                $messages[] = 'Available on ' . $startDate->format('M d, Y');
            } else {
                $messages[] = 'Available from ' . $startDate->format('M d') . ' to ' . $endDate->format('M d, Y');
            }
        } elseif ($this->date_start) {
            $startDate = Carbon::parse($this->date_start);
            $messages[] = 'Available from ' . $startDate->format('M d, Y');
        } elseif ($this->date_end) {
            $endDate = Carbon::parse($this->date_end);
            $messages[] = 'Available until ' . $endDate->format('M d, Y');
        }
        
        // Time messages
        if ($this->start_time && $this->end_time) {
            $startTime = Carbon::parse($this->start_time);
            $endTime = Carbon::parse($this->end_time);
            $messages[] = $startTime->format('g:i A') . ' - ' . $endTime->format('g:i A');
        } elseif ($this->start_time) {
            $startTime = Carbon::parse($this->start_time);
            $messages[] = 'From ' . $startTime->format('g:i A');
        } elseif ($this->end_time) {
            $endTime = Carbon::parse($this->end_time);
            $messages[] = 'Until ' . $endTime->format('g:i A');
        }
        
        return implode(' • ', $messages);
    }

    /**
     * Get the expiration duration in days
     */
    public function getExpirationDaysAttribute($value)
    {
        return $value ?? 30;
    }

    /**
     * Check if this reward tier expires
     */
    public function getDoesExpireAttribute()
    {
        return !is_null($this->expiry_duration) && $this->expiry_duration > 0;
    }

    /**
     * Get the redemption item name
     */
    public function getRedemptionItemNameAttribute(): string
    {
        if ($this->redeemableItem) {
            return $this->redeemableItem->item_name;
        }
        
        return $this->reward_description;
    }

    /**
     * Get the redemption item type
     */
    public function getRedemptionItemTypeAttribute(): string
    {
        if ($this->redeemableItem) {
            return $this->redeemableItem->item_type;
        }
        
        return 'product';
    }
}