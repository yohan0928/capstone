<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class OwnerAccount extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $guard = 'owner';
    public $timestamps = false;
    protected $table = 'owner_accounts';

    protected $fillable = [
        'uuid',
        'first_name',
        'last_name',
        'contact_no',
        'address',
        'gcash_qr_code_img',
        'email',
        'password',
         'two_factor_enabled',
        'two_factor_enabled_at',
        'regular',  // 0=no, 1=yes
        'role',  // 1 = owner
        'date_joined',
        'date_deactivated',
        'reasons',
        'account_status',  // 0=suspended, 1=verified
        'active',  // 0=inactive, 1=active
    ];

     protected $casts = [
        'two_factor_enabled' => 'boolean',
        'two_factor_enabled_at' => 'datetime',
        
        'gcash_qr_code_img' => 'array',
    ];

    protected $hidden = [
        'password'
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

    /*--------------------------------------------------------------
    | Relationships
    --------------------------------------------------------------*/

    // Owner HAS MANY relationships

    public function branches()
    {
        return $this->hasMany(Branch::class, 'owner_account_id');
    }

    public function staffAccounts()
    {
        return $this->hasMany(StaffAccount::class, 'owner_account_id');
    }

    public function staffSchedules()
    {
        return $this->hasMany(StaffShiftSchedule::class, 'owner_account_id');
    }
}