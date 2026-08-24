<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ServiceCategory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'service_categories';

    protected $fillable = [
        'uuid',
        'owner_account_id',
        'branch_id',

        'service_img',
        'service_category',
        'service_category_status', // 0=unavailable, 1=available

        'date_created',
        'date_updated',

        'active', // 0=inactive, 1=active
    ];

    // Cast service_img to array automatically
    protected $casts = [
        'service_img' => 'array',
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

    public function serviceNames()
    {
        return $this->hasMany(ServiceName::class, 'service_category_id');
    }

    public function seats()
    {
        return $this->hasMany(Seat::class, 'service_category_id');
    }

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }
}
