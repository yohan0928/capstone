<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class CustomerAccount extends Authenticatable
{
    use HasFactory, Notifiable;

    public $timestamps = false;

    protected $guard = 'customer';

    protected $table = 'customer_accounts';

    protected $fillable = [
        'first_name',
        'last_name',
        'contact_no',
        'address',
        'email',
        'password',
        'google_id',
        'email_verified_at',
        'two_factor_enabled',        // Only this column is needed for simplified version
        'two_factor_enabled_at',     // Optional: Track when 2FA was enabled
        'regular',
        'role',
        'date_joined',
        'date_deactivated',
        'reasons',
        'account_status',
        'active',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'two_factor_enabled' => 'boolean',
        'email_verified_at' => 'datetime',
        'date_joined' => 'datetime',
        'date_deactivated' => 'datetime',
        'two_factor_enabled_at' => 'datetime',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'customer_account_id');
    }

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class, 'service_name_id');
    }

    public function rewards()
    {
        return $this->hasMany(CustomerReward::class, 'customer_account_id');
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

    /**
     * Enable 2FA for the user (Simplified - just set flag)
     */
    public function enableTwoFactor()
    {
        $this->update([
            'two_factor_enabled' => true,
            'two_factor_enabled_at' => now(),
        ]);
    }

    /**
     * Disable 2FA for the user (Simplified - just unset flag)
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

    /**
     * Check if user can use 2FA
     * Additional checks like verified email, active account, etc.
     */
    public function canUseTwoFactor()
    {
        return $this->two_factor_enabled 
            && $this->account_status == 1 
            && $this->active == 1
            && $this->email_verified_at !== null;
    }

    /**
     * Automatically update regular status based on 30-day usage
     */
    public function updateRegularStatus()
    {
        if ($this->regular == 1) {
            return true;
        }

        $joinDate = $this->date_joined ?? $this->created_at;

        if (!$joinDate) {
            return false;
        }

        $joinDate = Carbon::parse($joinDate);
        $daysSinceJoining = $joinDate->diffInDays(Carbon::now());

        if ($daysSinceJoining >= 30) {
            $this->update(['regular' => 1]);
            return true;
        }

        return false;
    }

    /**
     * Check if customer is eligible for congratulations
     */
    public function isEligibleForCongratulations()
    {
        $this->updateRegularStatus();

        if ($this->regular != 1) {
            return false;
        }

        $joinDate = $this->date_joined ?? $this->created_at;

        if (!$joinDate) {
            return false;
        }

        $joinDate = Carbon::parse($joinDate);
        $daysSinceJoining = $joinDate->diffInDays(Carbon::now());

        return $daysSinceJoining >= 30;
    }

    /**
     * Get masked email for display (e.g., j****@example.com)
     */
    public function getMaskedEmailAttribute()
    {
        $email = $this->email;
        $parts = explode('@', $email);
        
        if (count($parts) != 2) {
            return $email;
        }
        
        $username = $parts[0];
        $domain = $parts[1];
        
        if (strlen($username) <= 1) {
            $maskedUsername = str_repeat('*', strlen($username));
        } else {
            $maskedUsername = $username[0] . str_repeat('*', strlen($username) - 1);
        }
        
        return $maskedUsername . '@' . $domain;
    }

    /**
     * Check if this is a Google account (no password set)
     */
    public function isGoogleAccount()
    {
        return !empty($this->google_id);
    }

    /**
     * Check if account is verified and active
     */
    public function isActiveAndVerified()
    {
        return $this->account_status == 1 
            && $this->active == 1
            && $this->email_verified_at !== null;
    }
}