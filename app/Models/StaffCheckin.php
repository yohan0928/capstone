<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StaffCheckin extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_account_id',
        'branch_id',
        'staff_shift_schedule_id',
        'checkin_time',
        'checkout_time',
        'time_worked',
        'checkin_status',
        'active'
    ];

    protected $casts = [
        'checkin_time' => 'datetime',
        'checkout_time' => 'datetime',
        'checkin_status' => 'boolean',
        'active' => 'boolean'
    ];

    public function staffAccount()
    {
        return $this->belongsTo(StaffAccount::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function staffShiftSchedule()
    {
        return $this->belongsTo(StaffShiftSchedule::class, 'staff_shift_schedule_id');
    }

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
}