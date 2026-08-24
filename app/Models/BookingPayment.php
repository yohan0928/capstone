<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BookingPayment extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'booking_payments';

    protected $fillable = [
        'customer_account_id',
        'branch_id',
        'booking_id',
        'payment_date',
        'payment_category',  // 0=extension, 1=main(main payment)
        'payment_method',  // 0=cash, 1=gcash, 2=debit-card, 3=pay-later
        'total_amount',
        'amount_paid',
        'change',
        'gcash_ref_no',
        'gcash_receipt_img',
        'notes',
        'payment_status',  // 1=paid, 2=unpaid
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

    protected $casts = [
        'notes' => 'array',
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

    public function customerAccount()
    {
        return $this->belongsTo(CustomerAccount::class, 'customer_account_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
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
