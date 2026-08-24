<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Ingredient extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'ingredients';

    protected $fillable = [
        'owner_account_id',
        'branch_id',
        'ingredient_batch_no',
        'ingredient_img',
        'ingredient_type',
        'ingredient_name',
        'stock_quantity_in',
        'unit',
        'stock_quantity_threshold',
        'unit_conversion',
        'converted_unit',
        'converted_stock_quantity_in',
        'date_stored',
        'date_expiration',
        'ingredient_status',  // 0=unavailable, 1=available
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

    // Ingredient HAS MANY relationships
    public function damagedIngredients()
    {
        return $this->hasMany(DamagedIngredient::class, 'ingredient_id');
    }

    public function productIngredients()
    {
        return $this->hasMany(ProductIngredient::class, 'ingredient_id');
    }

    public function products()
    {
        return $this
            ->belongsToMany(Product::class, 'product_ingredients', 'ingredient_id', 'product_id')
            ->withPivot('branch_id');  // if you also want to access pivot branch_id
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
