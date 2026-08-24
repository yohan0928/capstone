<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Branch extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'branches';

    protected $fillable = [
        'owner_account_id',

        'branch_profile',
        'branch_name',
        'location',
        'address',       
        'latitude',
        'longitude',
        'google_map_url',
        'features',
        'open_time',
        'close_time',
        'open_days',
        'branch_status', // 0=closed, 1=open, 2=soon

        'date_created',
        'date_updated',

        'active', // 0=inactive, 1=active
    ];

     /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'uuid' => 'string',
        'branch_status' => 'integer',
        'active' => 'boolean',
        'date_created' => 'datetime',
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
    public function owner()
    {
        return $this->belongsTo(OwnerAccount::class, 'owner_account_id');
    }

    // Branch HAS MANY relationships
    public function serviceCategories()
    {
        return $this->hasMany(ServiceCategory::class, 'branch_id');
    }

    public function serviceNames()
    {
        return $this->hasMany(ServiceName::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'branch_id');
    }

    public function staffSchedules()
    {
        return $this->hasMany(StaffShiftSchedule::class, 'branch_id');
    }

    public function staffAccounts()
    {
        return $this->hasMany(StaffAccount::class, 'branch_id');
    }

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class, 'service_name_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}