<?php
// app/Services/VoucherService.php

namespace App\Services;

use App\Models\CustomerReward;
use Illuminate\Support\Str;

class VoucherService
{
    public function generateVoucherCode(CustomerReward $customerReward): string
    {
        $prefix = $customerReward->rewardTier->getVoucherPrefix();
        $uniqueId = Str::random(8);
        $checksum = $this->calculateChecksum($prefix . $uniqueId);
        
        return strtoupper($prefix . '-' . $uniqueId . '-' . $checksum);
    }
    
    private function calculateChecksum(string $input): string
    {
        // Simple checksum - first 2 characters of hash
        return substr(md5($input), 0, 2);
    }
    
    public function validateVoucherCode(string $code): bool
    {
        // Check format: PREFIX-XXXXXXXX-XX
        $pattern = '/^[A-Z]{2,5}-[A-Z0-9]{8}-[A-Z0-9]{2}$/';
        return preg_match($pattern, $code) === 1;
    }
    
    public function isVoucherUnique(string $code): bool
    {
        return !CustomerReward::where('voucher_code', $code)->exists();
    }
}