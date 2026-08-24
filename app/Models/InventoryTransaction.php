<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $table = 'inventory_transactions';

    protected $fillable = [
        'owner_account_id',
        'branch_id',
        
        'transaction_no',
        
        'type',

        'status',
        
        'reason',
        'note',
        
        'processed_by',
        'processed_by_type',


        'approved_by_id',
        'approved_by',
        
        'approved_at',

        'rejected_reason',
        'rejected_at',

        'active',
    ];

    /**
     * UUID Auto Generate
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {

            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            if (empty($model->transaction_no)) {
                $model->transaction_no = self::generateTransactionNo($model->type);
            }
        });
    }

    /**
     * Generate Transaction Number
     * Example:
     * SI-2026-0001
     * SO-2026-0001
     */
    public static function generateTransactionNo($type)
    {
        $prefix = $type === 'stock_in' ? 'SI' : 'SO';

        $year = now()->year;

        $latest = self::where('type', $type)
            ->latest('id')
            ->first();

        $nextNumber = 1;

        if ($latest && preg_match('/(\d+)$/', $latest->transaction_no, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        }

        return $prefix . '-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /*--------------------------------------------------------------
    | Relationships
    --------------------------------------------------------------*/

    // BELONGS TO

    public function owner()
    {
        return $this->belongsTo(OwnerAccount::class, 'owner_account_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // HAS MANY

    public function items()
    {
        return $this->hasMany(
            InventoryTransactionItem::class,
            'inventory_transaction_id'
        );
    }

    /*--------------------------------------------------------------
    | Dynamic User Accessors
    --------------------------------------------------------------*/

    protected function resolveUser($id, $type)
    {
        if (!$id || !$type) {
            return null;
        }

        return match ($type) {
            'owner' => OwnerAccount::find($id),
            'staff' => StaffAccount::find($id),
            default => null,
        };
    }

    public function getProcessorAttribute()
    {
        return $this->resolveUser(
            $this->processed_by_id,
            $this->processed_by_type
        );
    }
}