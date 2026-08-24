<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class InventoryBatch extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'uuid',
        'owner_account_id',
        'branch_id',
        'item_type',
        'product_id',
        'ingredient_id',
        'inventory_transaction_id',
        'quantity_received',
        'quantity_remaining',
        'unit',
        'note',
        'received_at',
    ];

    protected $casts = [
        'quantity_received'  => 'decimal:4',
        'quantity_remaining' => 'decimal:4',
        'received_at'        => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function transaction()
    {
        return $this->belongsTo(InventoryTransaction::class, 'inventory_transaction_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function owner()
    {
        return $this->belongsTo(OwnerAccount::class, 'owner_account_id');
    }

    // ── Scopes ────────────────────────────────────────────────

    // FIFO: oldest batches with remaining stock first
    public function scopeAvailable($query)
    {
        return $query->where('quantity_remaining', '>', 0)
                     ->orderBy('received_at', 'asc')
                     ->orderBy('id', 'asc');
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('item_type', 'product')
                     ->where('product_id', $productId);
    }

    public function scopeForIngredient($query, int $ingredientId)
    {
        return $query->where('item_type', 'ingredient')
                     ->where('ingredient_id', $ingredientId);
    }

    public function scopeForOwner($query, int $ownerAccountId)
    {
        return $query->where('owner_account_id', $ownerAccountId);
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    // ── Helpers ───────────────────────────────────────────────

    public function isEmpty(): bool
    {
        return $this->quantity_remaining <= 0;
    }

    public function getAvailableQuantityAttribute(): float
    {
        return (float) $this->quantity_remaining;
    }
}