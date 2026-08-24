<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardTransaction extends Model
{
    use HasFactory;

    protected $table = 'reward_transactions';

    protected $fillable = [
        'customer_reward_id',
        'customer_account_id',
        'reward_tier_id',
        'transaction_type',
        'transaction_status',
        'reward_count_at_claim',
        'transaction_date',
        'reference_number',
        'notes',
        'active'
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'active' => 'boolean',
        'reward_count_at_claim' => 'integer'
    ];

    // Relationships
    public function customerReward()
    {
        return $this->belongsTo(CustomerReward::class, 'customer_reward_id');
    }

    public function customer()
    {
        return $this->belongsTo(CustomerAccount::class, 'customer_account_id');
    }

    public function rewardTier()
    {
        return $this->belongsTo(RewardTier::class, 'reward_tier_id');
    }

    // Scopes for common queries
    public function scopeCompleted($query)
    {
        return $query->where('transaction_status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('transaction_status', 'pending');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    // Helper methods
    public function isCompleted()
    {
        return $this->transaction_status === 'completed';
    }

    public function isPending()
    {
        return $this->transaction_status === 'pending';
    }

    // Generate reference number automatically
    public static function generateReferenceNumber()
    {
        return 'RWD-' . strtoupper(uniqid()) . '-' . date('YmdHis');
    }
}