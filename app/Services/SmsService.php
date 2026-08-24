<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected $apiToken;
    protected $apiUrl;
    protected $senderName;
    
    public function __construct()
    {
        $this->apiToken = config('services.iprogsms.api_token');
        $this->apiUrl = config('services.iprogsms.api_url');
        $this->senderName = config('services.iprogsms.sender_name');
    }
    
    /**
     * Send SMS via iProgSMS API
     */
    public function sendSms($phoneNumber, $message)
    {
        try {
            // Remove +63 if present and ensure it's just 10 digits
            $phoneNumber = $this->formatPhoneNumber($phoneNumber);
            
            if (!$this->isValidPhoneNumber($phoneNumber)) {
                Log::error('Invalid phone number format: ' . $phoneNumber);
                return false;
            }
            
            $response = Http::post($this->apiUrl, [
                'api_token' => $this->apiToken,
                'message' => $message,
                'phone_number' => $phoneNumber,
                'sender_name' => $this->senderName,
            ]);
            
            if ($response->successful()) {
                Log::info('SMS sent successfully to ' . $phoneNumber, [
                    'response' => $response->json(),
                    'message' => $message
                ]);
                return true;
            } else {
                Log::error('Failed to send SMS to ' . $phoneNumber, [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'message' => $message
                ]);
                return false;
            }
            
        } catch (\Exception $e) {
            Log::error('Exception while sending SMS: ' . $e->getMessage(), [
                'phone_number' => $phoneNumber,
                'message' => $message
            ]);
            return false;
        }
    }
    
    /**
     * Format phone number to match iProgSMS requirements
     */
    protected function formatPhoneNumber($phoneNumber)
    {
        // Remove all non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // If starts with 63, remove it (iProgSMS wants 09... format)
        if (substr($phoneNumber, 0, 2) === '63') {
            $phoneNumber = '0' . substr($phoneNumber, 2);
        }
        
        // If starts with +63, remove it
        if (substr($phoneNumber, 0, 3) === '+63') {
            $phoneNumber = '0' . substr($phoneNumber, 3);
        }
        
        // Ensure it starts with 09 and has exactly 10 digits
        if (strlen($phoneNumber) === 10 && substr($phoneNumber, 0, 2) !== '09') {
            $phoneNumber = '09' . substr($phoneNumber, 2);
        }
        
        return $phoneNumber;
    }
    
    /**
     * Validate Philippine phone number
     */
    protected function isValidPhoneNumber($phoneNumber)
    {
        // Should be 10 digits starting with 09
        return preg_match('/^09[0-9]{9}$/', $phoneNumber);
    }
}