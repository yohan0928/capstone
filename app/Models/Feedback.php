<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedbacks';

    protected $fillable = [
        'branch_id',
        'service_category_id',
        'service_name_id',
        'customer_account_id',
        'booking_id',
        'rating',
        'comment',
        'approved',
        'active'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    // Relationships
    public function serviceName()
    {
        return $this->belongsTo(ServiceName::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function customerAccount()
    {
        return $this->belongsTo(CustomerAccount::class);
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('feedbacks.approved', 1);
    }

    public function scopeActive($query)
    {
        return $query->where('feedbacks.active', 1);
    }

    public function scopeForService($query, $serviceNameId)
    {
        return $query->where('service_name_id', $serviceNameId);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('service_category_id', $categoryId);
    }
}
