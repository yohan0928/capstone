<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ServiceName extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'service_names';

    protected $fillable = [
        'owner_account_id',
        'branch_id',
        'service_category_id',

        'service_name',
        'price',
        
        'old_price',
        'discount',
        'discount_type',
        
        'time_duration',
        'space_type',
        'service_name_status', // 0=unavailable, 1=available

        'date_created',
        'date_updated',

        'active', // 0=inactive, 1=active
    ];
    
    protected $casts = [
        'old_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'price' => 'decimal:2'
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

    public function branches()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'service_name_id');
    }

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class, 'service_name_id');
    }
    
    public function seats()
    {
        return $this->hasMany(Seat::class);
    }
}
