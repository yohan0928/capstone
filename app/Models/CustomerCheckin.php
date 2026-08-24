<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CustomerCheckin extends Model
{
    use HasFactory;
    
    public $timestamps = false;

    protected $table = 'customer_checkins';

    protected $fillable = [
        'customer_account_id',
        'branch_id',
        'service_category_id',
        'service_name_id',
        'seat_id',
        'booking_id',
        'time_used',
        'extended_time_used',
        'total_time_used',
        'checkin_status',  // 0=checked-out, 1=checked-in
        'date_checked_in',
        'created_by',
        'created_by_type',
        'date_created',
        'updated_by',
        'updated_by_type',
        'date_updated',
        'last_updated_by',
        'last_updated_by_type',
        'last_date_updated',
        'active',  // 0=no, 1=yes
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

    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function serviceName()
    {
        return $this->belongsTo(ServiceName::class, 'service_name_id');
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class, 'seat_id');
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

