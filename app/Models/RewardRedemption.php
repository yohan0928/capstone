<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RewardRedemption extends Model
{
    protected $table = 'reward_redemptions';
    
    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'customer_reward_id',
        'customer_account_id',
        'booking_id',
        'order_id',
        'service_category_id',
        'service_name_id',
        'product_id',
        'reward_type',
        'target_type',
        'discount_value',
        'discount_amount',
        'original_amount',
        'final_amount',
        'receipt_number',
        'redeemed_by',
        'redeemed_by_type',
        'branch_id',
        'notes',
        'redeemed_at',
        'active',
        // Audit fields (matching Bookings table)
        'created_by',
        'created_by_type',
        'date_created',
        'last_updated_by',
        'last_updated_by_type',
        'last_date_updated',
        'updated_by',
        'updated_by_type',
        'date_updated',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'redeemed_at' => 'datetime',
        'date_created' => 'datetime',
        'date_updated' => 'datetime',
        'active' => 'boolean'
    ];

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
            
            // Set created_by if not already set
            if (empty($model->created_by) && auth()->check()) {
                $user = auth()->user();
                $model->created_by = $user->id;
                $model->created_by_type = class_basename($user);
            }
        });

        static::updating(function ($model) {
            $model->date_updated = now();
            
            // Set updated_by if not already set
            if (empty($model->updated_by) && auth()->check()) {
                $user = auth()->user();
                $model->updated_by = $user->id;
                $model->updated_by_type = class_basename($user);
                
                // Store in the last_updated fields as well
                $model->last_updated_by = $user->id;
                $model->last_updated_by_type = class_basename($user);
                $model->last_date_updated = now();
            }
        });
    }

    // ================================================================
    // RELATIONSHIPS
    // ================================================================
    
    public function customerReward()
    {
        return $this->belongsTo(CustomerReward::class, 'customer_reward_id');
    }

    public function customer()
    {
        return $this->belongsTo(CustomerAccount::class, 'customer_account_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function serviceName()
    {
        return $this->belongsTo(ServiceName::class, 'service_name_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // ================================================================
    // DYNAMIC USER ACCESSORS (Matching Booking model pattern)
    // ================================================================

    protected $appends = ['creator', 'updator', 'last_updator'];

    protected function resolveUser($id, $type)
{
    if (!$id || !$type) {
        return null;
    }

    // Map both formats to their model classes
    $typeMap = [
        // Full class names
        'OwnerAccount' => OwnerAccount::class,
        'StaffAccount' => StaffAccount::class,
        'CustomerAccount' => CustomerAccount::class,
        // Lowercase short names (matching Booking model pattern)
        'owner' => OwnerAccount::class,
        'staff' => StaffAccount::class,
        'customer' => CustomerAccount::class,
        // Capitalized short names
        'Owner' => OwnerAccount::class,
        'Staff' => StaffAccount::class,
        'Customer' => CustomerAccount::class,
    ];

    // Normalize the type for lookup
    $normalizedType = $type;
    if (strpos($type, 'App\\Models\\') === 0) {
        $normalizedType = str_replace('App\\Models\\', '', $type);
    }

    $modelClass = $typeMap[$normalizedType] ?? null;

    if ($modelClass && class_exists($modelClass)) {
        return $modelClass::find($id);
    }

    return null;
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

    /**
     * Get the redeemer (the user who processed the redemption)
     */
    public function getRedeemerAttribute()
    {
        return $this->resolveUser($this->redeemed_by, $this->redeemed_by_type);
    }

    /**
     * Get the redeemer's full name
     */
    public function getRedeemedByNameAttribute()
    {
        $redeemer = $this->getRedeemerAttribute();
        
        if (!$redeemer) {
            return 'N/A';
        }

        if ($redeemer instanceof OwnerAccount || 
            $redeemer instanceof StaffAccount || 
            $redeemer instanceof CustomerAccount) {
            return $redeemer->first_name . ' ' . $redeemer->last_name;
        }

        return 'Unknown';
    }

    /**
     * Get the redeemer's role
     */
    public function getRedeemedByRoleAttribute()
    {
        $redeemer = $this->getRedeemerAttribute();
        
        if (!$redeemer) {
            return 'N/A';
        }

        if ($redeemer instanceof OwnerAccount) {
            return 'Owner';
        }

        if ($redeemer instanceof StaffAccount) {
            return 'Staff';
        }

        if ($redeemer instanceof CustomerAccount) {
            return 'Customer';
        }

        return 'Unknown';
    }

    /**
     * Get the redeemer's email
     */
    public function getRedeemedByEmailAttribute()
    {
        $redeemer = $this->getRedeemerAttribute();
        
        if (!$redeemer) {
            return 'N/A';
        }

        if (property_exists($redeemer, 'email')) {
            return $redeemer->email;
        }

        return 'N/A';
    }

    /**
     * Get the redeemer type label
     */
    public function getRedeemedByTypeLabelAttribute()
    {
        if (!$this->redeemed_by_type) {
            return 'N/A';
        }

        $type = $this->redeemed_by_type;
        $type = str_replace('App\\Models\\', '', $type);
        $type = str_replace('Account', '', $type);
        
        return $type;
    }

    // ================================================================
    // SCOPES
    // ================================================================
    
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_account_id', $customerId);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('redeemed_at', [$startDate, $endDate]);
    }

    public function scopeByRewardType($query, $type)
    {
        return $query->where('reward_type', $type);
    }

    public function scopeByTargetType($query, $targetType)
    {
        return $query->where('target_type', $targetType);
    }

    // ================================================================
    // ACCESSORS
    // ================================================================
    
    public function getRewardTypeLabelAttribute()
    {
        $labels = [
            'free_service' => 'Free Service',
            'free_product' => 'Free Product',
            'fixed_discount' => 'Fixed Discount',
            'percentage_discount' => 'Percentage Discount'
        ];
        return $labels[$this->reward_type] ?? $this->reward_type;
    }

    public function getTargetLabelAttribute()
    {
        if ($this->target_type === 'service' && $this->serviceName) {
            return $this->serviceName->service_name;
        }
        if ($this->target_type === 'product' && $this->product) {
            return $this->product->product_name;
        }
        return 'N/A';
    }

    public function getDiscountDisplayAttribute()
    {
        switch ($this->reward_type) {
            case 'free_service':
            case 'free_product':
                return 'Free';
            case 'fixed_discount':
                return '₱' . number_format($this->discount_value, 2);
            case 'percentage_discount':
                return $this->discount_value . '%';
            default:
                return 'N/A';
        }
    }

    public function getSavingsDisplayAttribute()
    {
        if ($this->discount_amount) {
            return '₱' . number_format($this->discount_amount, 2);
        }
        return 'N/A';
    }

    public function getContextTypeAttribute()
    {
        if ($this->booking_id) {
            return 'Booking';
        }
        if ($this->order_id) {
            return 'Order';
        }
        return 'N/A';
    }

    public function getContextIdAttribute()
    {
        if ($this->booking_id) {
            return $this->booking_id;
        }
        if ($this->order_id) {
            return $this->order_id;
        }
        return null;
    }

    public function getFormattedRedeemedAtAttribute()
    {
        if ($this->redeemed_at) {
            return $this->redeemed_at->format('M d, Y h:i A');
        }
        return 'N/A';
    }

    public function getStatusBadgeClassAttribute()
    {
        return 'bg-green-100 text-green-800';
    }

    public function getStatusLabelAttribute()
    {
        return 'Redeemed';
    }

    public function getTargetDetailsAttribute()
    {
        $details = [];
        
        if ($this->target_type === 'service') {
            if ($this->serviceName) {
                $details['service_name'] = $this->serviceName->service_name;
                $details['service_category'] = $this->serviceName->serviceCategory->service_category ?? null;
                $details['price'] = $this->serviceName->price;
                $details['time_duration'] = $this->serviceName->time_duration;
            }
            if ($this->serviceCategory) {
                $details['category'] = $this->serviceCategory->service_category;
            }
        }
        
        if ($this->target_type === 'product' && $this->product) {
            $details['product_name'] = $this->product->product_name;
            $details['price'] = $this->product->selling_price ?? 0;
            $details['product_type'] = $this->product->product_type ?? 'simple';
        }
        
        return !empty($details) ? $details : null;
    }

    // ================================================================
    // HELPER METHODS
    // ================================================================
    
    public static function createFromReward($customerReward, $context, $data = [])
    {
        $redemption = new self();
        $redemption->customer_reward_id = $customerReward->id;
        $redemption->customer_account_id = $customerReward->customer_account_id;
        $redemption->branch_id = $customerReward->branch_id ?? $context['branch_id'] ?? null;
        
        // Set context based on type
        if (isset($context['booking_id'])) {
            $redemption->booking_id = $context['booking_id'];
        }
        if (isset($context['order_id'])) {
            $redemption->order_id = $context['order_id'];
        }
        
        // Set service or product details
        if (isset($context['service_name_id'])) {
            $redemption->service_name_id = $context['service_name_id'];
            $redemption->target_type = 'service';
        }
        if (isset($context['service_category_id'])) {
            $redemption->service_category_id = $context['service_category_id'];
        }
        if (isset($context['product_id'])) {
            $redemption->product_id = $context['product_id'];
            $redemption->target_type = 'product';
        }
        
        // Set reward details
        $redemption->reward_type = $customerReward->loyaltyTier->redeemableItem->reward_type ?? null;
        
        // Set financial details
        $redemption->original_amount = $data['original_amount'] ?? 0;
        $redemption->discount_value = $data['discount_value'] ?? null;
        $redemption->discount_amount = $data['discount_amount'] ?? 0;
        $redemption->final_amount = $data['final_amount'] ?? 0;
        
        // Set redemption metadata
        $redemption->receipt_number = $data['receipt_number'] ?? null;
        $redemption->redeemed_by = $data['redeemed_by'] ?? null;
        $redemption->redeemed_by_type = $data['redeemed_by_type'] ?? null;
        $redemption->notes = $data['notes'] ?? null;
        $redemption->redeemed_at = now();
        
        $redemption->save();
        
        return $redemption;
    }
}