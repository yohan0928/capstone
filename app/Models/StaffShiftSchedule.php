<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StaffShiftSchedule extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'staff_shift_schedules';

    protected $fillable = [
        'owner_account_id',
        'branch_id',
        'staff_account_id',
        'shift_time_start',
        'shift_time_end',
        'shift_date_start',
        'shift_date_end',
        'staff_shift_schedule_status',  // 0=completed, 1=on-duty, 2=pending
        'date_created',
        'active',  // 0=inactive, 1=active
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

    // Relationship to the Owner Account
    public function owner()
    {
        return $this->belongsTo(OwnerAccount::class, 'owner_account_id');
    }

    // Relationship to the Branch
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // Relationship to the Staff Account
    public function staffAccount()
    {
        return $this->belongsTo(StaffAccount::class, 'staff_account_id');
    }

    public function checkins()
    {
        return $this->hasMany(StaffCheckin::class, 'staff_shift_schedule_id');
    }
}
