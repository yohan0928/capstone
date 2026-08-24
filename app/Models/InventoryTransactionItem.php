<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InventoryTransactionItem extends Model
{
    use HasFactory;

    protected $table = 'inventory_transaction_items';

    protected $fillable = [
        'inventory_transaction_id',
        'item_type',           // 'product' | 'ingredient'
        'product_id',          // nullable when item_type = ingredient
        'ingredient_id',       // nullable when item_type = product
        'quantity',            // qty received (in received_unit for ingredients)
        'unit',                // base unit (ml, g, pcs…)
        'received_unit',       // e.g. "bottle" — ingredient stock-in only
        'conversion_factor',   // 1 received_unit = N base units
        'base_quantity',       // quantity × conversion_factor
        'reason',
        'note',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:4',
        'base_quantity'     => 'decimal:4',
    ];

    /*--------------------------------------------------------------
    | Boot
    --------------------------------------------------------------*/
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
    public function inventoryTransaction()
    {
        return $this->belongsTo(
            InventoryTransaction::class,
            'inventory_transaction_id'
        );
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class, 'ingredient_id');
    }

    /*--------------------------------------------------------------
    | Helpers
    --------------------------------------------------------------*/
    /**
     * Returns the display name regardless of item type.
     */
    public function getItemNameAttribute(): string
    {
        if ($this->item_type === 'ingredient') {
            return $this->ingredient?->ingredient_name ?? 'Unknown ingredient';
        }

        return $this->product?->product_name ?? 'Unknown product';
    }

    public function branch()
    {
        return $this->inventoryTransaction?->branch();
    }

    public function owner()
    {
        return $this->inventoryTransaction?->owner();
    }
}