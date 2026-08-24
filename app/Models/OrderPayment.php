<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrderPayment extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'order_payments';

    protected $fillable = [
        'customer_account_id',
        'branch_id',
        'order_id',
        'payment_date',
        'payment_method', // 0=cash, 1=gcash, 2=debit, 3=pay-later
        'total_amount',
        'discount',
        'vat_sales',
        'vat_amount',
        'amount_paid',
        'change',
        'notes',
        'gcash_ref_no',
        'order_payment_status', // 0=unpaid, 1=paid
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

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'vat_sales' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'change' => 'decimal:2',
        'payment_date' => 'datetime',
        'date_created' => 'datetime',
        'last_date_updated' => 'datetime',
        'date_updated' => 'datetime',
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

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function customer()
    {
        return $this->belongsTo(CustomerAccount::class, 'customer_account_id');
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
}