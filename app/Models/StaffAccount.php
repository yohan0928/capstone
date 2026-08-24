<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable; 
use Illuminate\Support\Str;

class StaffAccount extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $guard = 'staff';
    public $timestamps = false;

    protected $table = 'staff_accounts';

    protected $fillable = [
        'uuid',
        'owner_account_id',
        'branch_id',
        'first_name',
        'last_name',
        'contact_no',
        'address',
        'gcash_qr_code_img',
        'email',
        'password',
        'two_factor_enabled',
        'two_factor_enabled_at',
        'regular', // 0=no, 1=yes
        'role',    // 2=staff
        'date_joined',
        'date_deactivated',
        'reasons',
        'account_status', // 0=suspended, 1=verified
        'active',         // 0=inactive, 1=active
    ];

    protected $casts = [
        'two_factor_enabled' => 'boolean',
        'two_factor_enabled_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
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

    /**
     * Enable 2FA for the user
     */
    public function enableTwoFactor()
    {
        $this->update([
            'two_factor_enabled' => true,
            'two_factor_enabled_at' => now(),
        ]);
    }

    /**
     * Disable 2FA for the user
     */
    public function disableTwoFactor()
    {
        $this->update([
            'two_factor_enabled' => false,
        ]);
    }

    /**
     * Check if 2FA is enabled
     */
    public function isTwoFactorEnabled()
    {
        return $this->two_factor_enabled === true;
    }

    // -----------------------------------------------
    // Relations
    // -----------------------------------------------

    public function owner()
    {
        return $this->belongsTo(OwnerAccount::class, 'owner_account_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function staffSchedules()
    {
        return $this->hasMany(StaffShiftSchedule::class, 'staff_account_id');
    }
}