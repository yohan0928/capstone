<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class RedeemableItem extends Model
{
    use SoftDeletes;

    protected $table = 'redeemable_items';
    
    public $timestamps = false;

    protected $fillable = [
        'owner_account_id',
        'branch_id',
        'uuid',
        'item_name',
        'item_description',
        'reward_type',
        'target_service_id',
        'target_product_id',
        'monetary_value',
        'discount_percentage',
        'category',
        'image_path',
        'active',
        'is_active',
        'created_by',
        'created_by_type',
        'date_created',
        'last_updated_by',
        'last_updated_by_type',
        'last_date_updated'
    ];

    protected $casts = [
        'monetary_value' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'active' => 'boolean',
        'is_active' => 'boolean',
        'date_created' => 'datetime',
        'last_date_updated' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * For UUID
     */
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
            if (empty($model->is_active)) {
                $model->is_active = 1;
            }
        });

        static::updating(function ($model) {
            $model->last_date_updated = now();
        });
    }

    // Relationships
    public function owner()
    {
        return $this->belongsTo(OwnerAccount::class, 'owner_account_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function loyaltyTiers()
    {
        return $this->hasMany(LoyaltyTier::class, 'redeemable_item_id');
    }

    /**
     * Service Relationship
     * Path: owner_account_id > branch_id > service_category_id > service_name_id
     * target_service_id references ServiceName (the actual service)
     */
    public function targetService()
    {
        return $this->belongsTo(ServiceName::class, 'target_service_id', 'id');
    }

    /**
     * Product Relationship
     * Path: owner_account_id > branch_id > product_id
     * For products with ingredients, the product_id is the same
     */
    public function targetProduct()
    {
        return $this->belongsTo(Product::class, 'target_product_id', 'id');
    }

    // Get the service category through the service
    public function getServiceCategoryAttribute()
    {
        if ($this->targetService && $this->targetService->serviceCategory) {
            return $this->targetService->serviceCategory;
        }
        return null;
    }

    // Get product ingredients if it's a product with ingredients
    public function getProductIngredientsAttribute()
    {
        if ($this->targetProduct) {
            return $this->targetProduct->productIngredients()->with('ingredient')->get();
        }
        return null;
    }

    // Created by relationship (polymorphic)
    public function createdBy()
    {
        return $this->morphTo('created_by', 'created_by_type', 'created_by');
    }

    public function lastUpdatedBy()
    {
        return $this->morphTo('last_updated_by', 'last_updated_by_type', 'last_updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true)->where('is_active', true);
    }

    public function scopeByOwner($query, $ownerId)
    {
        return $query->where('owner_account_id', $ownerId);
    }

    public function scopeByRewardType($query, $type)
    {
        return $query->where('reward_type', $type);
    }

    // Accessors
    public function getValueDisplayAttribute()
    {
        switch ($this->reward_type) {
            case 'free_service':
                if ($this->targetService) {
                    return $this->targetService->service_name ?? 'Free Service';
                }
                return 'Free Service';
            case 'free_product':
                if ($this->targetProduct) {
                    return $this->targetProduct->product_name ?? 'Free Product';
                }
                return 'Free Product';
            case 'fixed_discount':
                return '₱' . number_format($this->monetary_value, 2) . ' off';
            case 'percentage_discount':
                return $this->discount_percentage . '% off';
            default:
                return 'N/A';
        }
    }

    public function getTypeLabelAttribute()
    {
        $labels = [
            'free_service' => 'Free Service',
            'free_product' => 'Free Product',
            'fixed_discount' => 'Fixed Discount',
            'percentage_discount' => 'Percentage Discount'
        ];
        return $labels[$this->reward_type] ?? $this->reward_type;
    }

    public function getStatusLabelAttribute()
    {
        return ($this->active && $this->is_active) ? 'Active' : 'Inactive';
    }

    public function getStatusBadgeClassAttribute()
    {
        return ($this->active && $this->is_active) ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
    }

    // Get target details for display
    public function getTargetDetailsAttribute()
    {
        switch ($this->reward_type) {
            case 'free_service':
                if ($this->targetService) {
                    return [
                        'type' => 'service',
                        'id' => $this->target_service_id,
                        'name' => $this->targetService->service_name,
                        'category' => $this->targetService->serviceCategory->service_category ?? null,
                        'category_id' => $this->targetService->service_category_id,
                        'branch' => $this->targetService->branch->branch_name ?? null,
                        'branch_id' => $this->targetService->branch_id,
                        'price' => $this->targetService->price ?? 0
                    ];
                }
                return null;

            case 'free_product':
                if ($this->targetProduct) {
                    $details = [
                        'type' => 'product',
                        'id' => $this->target_product_id,
                        'name' => $this->targetProduct->product_name,
                        'product_type' => $this->targetProduct->product_type ?? 'simple',
                        'branch' => $this->targetProduct->branch->branch_name ?? null,
                        'branch_id' => $this->targetProduct->branch_id,
                        'price' => $this->targetProduct->selling_price ?? 0
                    ];
                    
                    // Check if product has ingredients
                    $ingredients = $this->targetProduct->productIngredients;
                    if ($ingredients && $ingredients->count() > 0) {
                        $details['has_ingredients'] = true;
                        $details['ingredients'] = $ingredients->map(function($pi) {
                            return [
                                'id' => $pi->ingredient_id,
                                'name' => $pi->ingredient->ingredient_name ?? 'Unknown',
                                'quantity' => $pi->quantity_needed,
                                'unit' => $pi->unit
                            ];
                        });
                    } else {
                        $details['has_ingredients'] = false;
                    }
                    
                    return $details;
                }
                return null;

            default:
                return null;
        }
    }

    // Helper method to apply the reward
    public function applyReward($target = null)
    {
        switch ($this->reward_type) {
            case 'free_service':
                if ($this->targetService) {
                    return [
                        'type' => 'free_service',
                        'service_id' => $this->target_service_id,
                        'service_name' => $this->targetService->service_name,
                        'service_category_id' => $this->targetService->service_category_id,
                        'branch_id' => $this->targetService->branch_id,
                        'discount_amount' => $this->targetService->price ?? 0,
                        'monetary_value' => $this->monetary_value,
                        'message' => "Free {$this->targetService->service_name} applied"
                    ];
                }
                break;

            case 'free_product':
                if ($this->targetProduct) {
                    $result = [
                        'type' => 'free_product',
                        'product_id' => $this->target_product_id,
                        'product_name' => $this->targetProduct->product_name,
                        'branch_id' => $this->targetProduct->branch_id,
                        'discount_amount' => $this->targetProduct->selling_price ?? 0,
                        'monetary_value' => $this->monetary_value,
                        'message' => "Free {$this->targetProduct->product_name} applied"
                    ];
                    
                    // Include ingredients info if applicable
                    $ingredients = $this->targetProduct->productIngredients;
                    if ($ingredients && $ingredients->count() > 0) {
                        $result['has_ingredients'] = true;
                        $result['ingredients'] = $ingredients->map(function($pi) {
                            return [
                                'ingredient_id' => $pi->ingredient_id,
                                'ingredient_name' => $pi->ingredient->ingredient_name ?? 'Unknown',
                                'quantity_needed' => $pi->quantity_needed,
                                'unit' => $pi->unit
                            ];
                        });
                    }
                    
                    return $result;
                }
                break;

            case 'fixed_discount':
                return [
                    'type' => 'fixed_discount',
                    'discount_amount' => $this->monetary_value,
                    'monetary_value' => $this->monetary_value,
                    'message' => "₱" . number_format($this->monetary_value, 2) . " discount applied"
                ];
                break;

            case 'percentage_discount':
                return [
                    'type' => 'percentage_discount',
                    'discount_percentage' => $this->discount_percentage,
                    'message' => $this->discount_percentage . "% discount applied"
                ];
                break;
        }

        return null;
    }

    // Validation rules - FIXED: Added monetary_value validation for free_service and free_product
    public static function getValidationRules($rewardType = null)
    {
        $rules = [
            'item_name' => 'required|string|max:100',
            'reward_type' => 'required|in:free_service,free_product,fixed_discount,percentage_discount',
            'item_description' => 'nullable|string|max:500',
            'branch_id' => 'nullable|exists:branches,id',
            'category' => 'nullable|string|max:50',
            'image_path' => 'nullable|string|max:255'
        ];

        // Add conditional rules based on reward type
        switch ($rewardType) {
            case 'free_service':
                $rules['target_service_id'] = 'required|exists:service_names,id';
                $rules['monetary_value'] = 'required|numeric|min:0.01'; // ADDED THIS
                break;
            case 'free_product':
                $rules['target_product_id'] = 'required|exists:products,id';
                $rules['monetary_value'] = 'required|numeric|min:0.01'; // ADDED THIS
                break;
            case 'fixed_discount':
                $rules['monetary_value'] = 'required|numeric|min:0.01';
                break;
            case 'percentage_discount':
                $rules['discount_percentage'] = 'required|numeric|min:0.01|max:100';
                break;
        }

        return $rules;
    }

    // Helper to get required fields for UI
    public static function getRequiredFields($rewardType)
    {
        $fields = [
            'free_service' => ['target_service_id' => 'Service', 'monetary_value' => 'Service Price'],
            'free_product' => ['target_product_id' => 'Product', 'monetary_value' => 'Product Price'],
            'fixed_discount' => ['monetary_value' => 'Discount Amount'],
            'percentage_discount' => ['discount_percentage' => 'Discount Percentage']
        ];

        return $fields[$rewardType] ?? [];
    }

    // Get reward type options for dropdown
    public static function getRewardTypeOptions()
    {
        return [
            ['value' => 'free_service', 'label' => 'Free Service'],
            ['value' => 'free_product', 'label' => 'Free Product'],
            ['value' => 'fixed_discount', 'label' => 'Fixed Discount'],
            ['value' => 'percentage_discount', 'label' => 'Percentage Discount']
        ];
    }
}