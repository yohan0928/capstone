<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'products';

    protected $fillable = [
        'owner_account_id',
        'branch_id',
        'product_batch_no',
        'product_img',
        'product_type',
        'product_name',
        'quantity_in',
        'unit',
        'quantity_threshold',

        // remove this entities
        'unit_conversion',
        'converted_unit',
        'converted_quantity_in',

        'selling_price',
        'date_stored',
        'date_expiration',
        'product_status',  // 0=unavailable, 1=available
        'created_by',
        'created_by_type',
        'date_created',
        'last_updated_by',
        'last_updated_by_type',
        'last_date_updated',
        'updated_by',
        'updated_by_type',
        'date_updated',
        'active',  // 0=inactive, 1=active
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
        });
    }

    /*--------------------------------------------------------------
    | Relationships
    --------------------------------------------------------------*/

    // Product BELONGS TO these
    public function owner()
    {
        return $this->belongsTo(OwnerAccount::class, 'owner_account_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // Product HAS MANY relationships
    public function damagedIngredients()
    {
        return $this->hasMany(DamagedProduct::class, 'product_id');
    }

    public function productIngredients()
    {
        return $this->hasMany(ProductIngredient::class, 'product_id');
    }

    public function orderItems()
{
    return $this->hasMany(OrderItem::class, 'product_id');
}

    // ----------------------
    // Dynamic user accessors
    // ----------------------

    // Helper to resolve the user dynamically
    protected function resolveUser($id, $type)
    {
        if (!$id || !$type)
            return null;

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
}
