<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Seat extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'seats';

    protected $fillable = [
        'owner_account_id',
        'branch_id',
        'service_category_id',

        'seat_no',
        'room_no',
        'seat_status', // 0=unavailable, 1=available

        'date_created',
        'date_updated',

        'active', // 0=inactive, 1=active
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

    // ServiceCategory BELONGS TO these
    public function owner()
    {
        return $this->belongsTo(OwnerAccount::class, 'owner_account_id');
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
    return $this->belongsTo(ServiceName::class);
}

    public function bookings()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}
