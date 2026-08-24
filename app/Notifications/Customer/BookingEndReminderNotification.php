<?php

namespace App\Notifications\Customer;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\Facades\Log;

class BookingEndReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $booking;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    public function via($notifiable)
    {
        $channels = ['database', 'broadcast', 'mail'];
        
        // Check if customer has a valid phone number for SMS
        if ($notifiable->contact_no && $this->shouldSendSms($notifiable)) {
            $channels[] = 'sms';
        }
        
        return $channels;
    }

    public function toMail($notifiable)
    {
        // Use extended time if available, otherwise use regular time
        $endDate = $this->booking->extended_date_end ?? $this->booking->date_end;
        $endTime = $this->booking->extended_end_time ?? $this->booking->end_time;
        
        $endDateTime = \Carbon\Carbon::parse($endDate . ' ' . $endTime);
        
        return (new MailMessage)
            ->subject('Booking Reminder: Ending Soon')
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line('Your booking will end in 10 minutes.')
            ->line('Booking Details:')
            ->line('Reference: ' . $this->booking->booking_ref_no)
            ->line('Service: ' . ($this->booking->serviceName->service_name ?? 'N/A'))
            ->line('End Time: ' . $endDateTime->format('M j, Y g:i A'))
            ->line('Location: ' . ($this->booking->branch->branch_name ?? 'N/A'))
            ->action('View Booking Details', url('/sub_three/my_bookings/details/' . $this->booking->uuid))
            ->line('Please prepare to check out. Thank you!');
    }

    public function toSms($notifiable)
    {
        // Use extended time if available, otherwise use regular time
        $endDate = $this->booking->extended_date_end ?? $this->booking->date_end;
        $endTime = $this->booking->extended_end_time ?? $this->booking->end_time;
        
        $endDateTime = \Carbon\Carbon::parse($endDate . ' ' . $endTime);
        $serviceName = $this->booking->serviceName->service_name ?? 'N/A';
        $branchName = $this->booking->branch->branch_name ?? 'N/A';
        
        // Create SMS-friendly message
        $message = "Hi {$notifiable->first_name}! Your {$serviceName} booking at {$branchName} ends in 10 minutes at {$endDateTime->format('g:i A')}. Ref: {$this->booking->booking_ref_no}";
        
        // Ensure message doesn't exceed 160 characters
        if (strlen($message) > 160) {
            $message = "Hi {$notifiable->first_name}! Your {$serviceName} booking ends in 10 minutes at {$endDateTime->format('g:i A')}. Ref: {$this->booking->booking_ref_no}";
        }
        
        return [
            'phone_number' => $notifiable->contact_no,
            'message' => $message
        ];
    }

    public function toArray($notifiable)
    {
        // Use extended time if available, otherwise use regular time
        $endDate = $this->booking->extended_date_end ?? $this->booking->date_end;
        $endTime = $this->booking->extended_end_time ?? $this->booking->end_time;
        
        $endDateTime = \Carbon\Carbon::parse($endDate . ' ' . $endTime);
        $serviceName = $this->booking->serviceName->service_name ?? 'N/A';
        
        // Keep customer message format as before
        $message = 'Your booking for ' . $serviceName . ' ends in 10 minutes.';
        
        return [
            'booking_id' => $this->booking->id,
            'booking_ref_no' => $this->booking->booking_ref_no,
            'type' => 'booking_end_reminder',
            'title' => 'Booking Ending Soon',
            'message' => $message,
            'service_name' => $serviceName,
            'time' => $endDateTime->format('g:i A'),
            'date' => $endDateTime->format('M j, Y'),
            'full_end_datetime' => $endDateTime->format('Y-m-d H:i:s'),
            'url' => '/sub_three/my_bookings/details/' . $this->booking->uuid,
            'icon' => 'clock',
            'color' => 'orange',
            'is_extended' => !empty($this->booking->extended_end_time),
            'action' => 'booking_end_reminder',
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'read_at' => null,
            'data' => $this->toArray($notifiable),
        ]);
    }

    /**
     * Determine if SMS should be sent
     */
    protected function shouldSendSms($notifiable)
    {
        // Check if phone number exists and is valid
        if (empty($notifiable->contact_no)) {
            return false;
        }
        
        return true;
    }
}