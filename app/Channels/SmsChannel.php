<?php
namespace App\Channels;

use App\Services\SmsService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification)
    {
        if (method_exists($notification, 'toSms')) {
            $data = $notification->toSms($notifiable);
            
            if (isset($data['phone_number']) && isset($data['message'])) {
                $success = $this->smsService->sendSms(
                    $data['phone_number'],
                    $data['message']
                );
                
                if ($success) {
                    Log::info('SMS notification sent via SmsChannel', [
                        'notifiable_id' => $notifiable->id,
                        'notifiable_type' => get_class($notifiable),
                        'notification' => get_class($notification),
                        'phone' => substr($data['phone_number'], 0, 3) . '****' . substr($data['phone_number'], -3)
                    ]);
                }
                
                return $success;
            }
        }
        
        Log::warning('SMS notification missing toSms method or data', [
            'notification' => get_class($notification)
        ]);
        
        return false;
    }
}