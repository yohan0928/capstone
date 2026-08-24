<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class SuperAdminAccount extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $guard = 'super_admin';
    public $timestamps = false;
    protected $table = 'super_admin_accounts';

    protected $fillable = [
        'uuid',
        'first_name',
        'last_name',
        'contact_no',
        'address',
        'gcash_qr_code_img',
        'email',
        'password',
        'regular',  // 0=no, 1=yes
        'role',  // 0 = super admin
        'date_joined',
        'date_deactivated',
        'reasons',
        'account_status',  // 0=suspended, 1=verified, 2=pending
        'active',  // 0=inactive, 1=active
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date_joined' => 'datetime',
        'date_deactivated' => 'datetime',
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

    // Super Admin can have access to all data, so we define relationships to all relevant models

    /**
     * Get all owners managed by this super admin
     */
    public function ownerAccounts()
    {
        return $this->hasMany(OwnerAccount::class, 'super_admin_id');
    }

    /**
     * Get all branches that can be accessed by super admin
     */
    public function branches()
    {
        return $this->hasManyThrough(Branch::class, OwnerAccount::class);
    }

    /**
     * Get all staff accounts that can be accessed by super admin
     */
    public function staffAccounts()
    {
        return $this->hasManyThrough(StaffAccount::class, OwnerAccount::class);
    }

    /*--------------------------------------------------------------
    | Accessors & Mutators
    --------------------------------------------------------------*/

    /**
     * Get the full name of the super admin
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Check if super admin is active and verified
     */
    public function getIsActiveAttribute()
    {
        return $this->active === 1 && $this->account_status === 1;
    }

    /**
     * Check if super admin is suspended
     */
    public function getIsSuspendedAttribute()
    {
        return $this->account_status === 0;
    }

    /**
     * Check if super admin is pending verification
     */
    public function getIsPendingAttribute()
    {
        return $this->account_status === 2;
    }

    /*--------------------------------------------------------------
    | Scopes
    --------------------------------------------------------------*/

    /**
     * Scope a query to only include active super admins
     */
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    /**
     * Scope a query to only include verified super admins
     */
    public function scopeVerified($query)
    {
        return $query->where('account_status', 1);
    }

    /**
     * Scope a query to only include suspended super admins
     */
    public function scopeSuspended($query)
    {
        return $query->where('account_status', 0);
    }

    /**
     * Scope a query to only include pending super admins
     */
    public function scopePending($query)
    {
        return $query->where('account_status', 2);
    }

    /*--------------------------------------------------------------
    | Business Logic
    --------------------------------------------------------------*/

    /**
     * Suspend the super admin account
     */
    public function suspend($reason = null)
    {
        $this->update([
            'account_status' => 0,
            'date_deactivated' => now(),
            'reasons' => $reason,
        ]);
    }

    /**
     * Activate the super admin account
     */
    public function activate()
    {
        $this->update([
            'account_status' => 1,
            'active' => 1,
            'date_deactivated' => null,
        ]);
    }

    /**
     * Check if super admin can perform administrative actions
     */
    public function canPerformAdminActions()
    {
        return $this->is_active;
    }
}