<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'bookings';

    protected $fillable = [
        'booking_ref_no',
        'customer_account_id',
        'branch_id',
        'service_category_id',
        'service_name_id',
        'seat_id',
        // Base booking time & date
        'start_time',
        'end_time',
        'date_start',
        'date_end',
        // --- Extension fields (separated time and date) ---
        'extended_start_time',
        'extended_end_time',
        'extended_date_start',
        'extended_date_end',
        // Other booking-related info
        'booking_date',
        'booking_type',  // 0=walk-in, 1=online
        'booking_status',  // 0=cancelled, 1=booked, 2=pending, 3=no-show, 4=completed
        // Auto Notification
        'start_reminder_sent',
        'start_reminder_sent_at',
        'end_reminder_sent',
        'end_reminder_sent_at',
        // Audit fields
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
     * Automatically assign UUID when creating a booking.
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

    public function payment()
    {
        return $this->hasOne(BookingPayment::class, 'booking_id');
    }

    public function extensionPayment()
    {
        return $this->hasOne(BookingPayment::class, 'booking_id')->where('payment_category', 0);
    }

    public function customerCheckin()
    {
        return $this->hasMany(CustomerCheckin::class, 'booking_id');
    }
    
     public function orders()
    {
        return $this->hasMany(Order::class, 'booking_id');
    }

    /*--------------------------------------------------------------
    | Accessors (for dynamic user resolution)
    --------------------------------------------------------------*/

    protected $appends = ['creator', 'updator', 'last_updator'];

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

    /*--------------------------------------------------------------
    | Helper Methods
    --------------------------------------------------------------*/

    /**
     * Compute total duration (base + extension)
     */
    public function getComputedTotalDurationAttribute()
    {
        $baseDuration = $this->calculateDurationInHours($this->start_time, $this->end_time);
        $extensionDuration = $this->extended_duration_hours ?? 0;
        return $baseDuration + $extensionDuration;
    }

    /**
     * Helper: Calculate duration between two times in hours
     */
    private function calculateDurationInHours($startTime, $endTime)
    {
        if (!$startTime || !$endTime)
            return 0;

        $start = strtotime($startTime);
        $end = strtotime($endTime);

        if ($end < $start) {
            // Handle crossing midnight
            $end += 24 * 60 * 60;
        }

        return round(($end - $start) / 3600, 2);
    }

    public function getStatusTextAttribute()
    {
        return match ($this->booking_status) {
            0 => 'Cancelled',
            1 => 'Confirmed',
            2 => 'Pending',
            3 => 'No Show',
            4 => 'Completed',
            default => 'Pending',
        };
    }
}
