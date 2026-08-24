<?php
// File: app/Console/Commands/CheckExpiredRewards.php

namespace App\Console\Commands;

use App\Models\CustomerReward;
use App\Models\RewardTier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiredRewards extends Command
{
    protected $signature = 'rewards:check-expired';
    protected $description = 'Check and mark expired rewards';

    public function handle()
    {
        $this->info('Checking for expired rewards...');

        try {
            $count = CustomerReward::processExpiredRewards();
            $this->info("Processed {$count} expired rewards.");
            
            Log::info("Expired rewards check completed", ['processed' => $count]);
        } catch (\Exception $e) {
            $this->error('Error processing expired rewards: ' . $e->getMessage());
            Log::error('Error in rewards:check-expired', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return 0;
    }
}