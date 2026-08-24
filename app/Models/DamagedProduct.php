<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DamagedProduct extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'damaged_products';

    protected $fillable = [
        'owner_account_id',
        'branch_id',
        'product_id',

        'quantity_out',
        'reasons',

        'date_damaged',

        'created_by',
        'created_by_type',
        'date_created',

        'active', // 0=inactive
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

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }


    // ----------------------
    // Dynamic user accessors
    // ----------------------

    // Helper to resolve the user dynamically
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
}
