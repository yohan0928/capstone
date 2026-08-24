<?php

namespace App\Notifications\Customer;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class BookingStartReminderNotification extends Notification implements ShouldQueue
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
        // Use ONLY regular start time (not extended)
        $startDateTime = \Carbon\Carbon::parse($this->booking->date_start . ' ' . $this->booking->start_time);

        return (new MailMessage)
            ->subject('Booking Reminder: Starting Soon')
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line('Your booking is scheduled to start in 2 hours.')
            ->line('Booking Details:')
            ->line('Reference: ' . $this->booking->booking_ref_no)
            ->line('Service: ' . ($this->booking->serviceName->service_name ?? 'N/A'))
            ->line('Start Time: ' . $startDateTime->format('M j, Y g:i A'))
            ->line('Location: ' . ($this->booking->branch->branch_name ?? 'N/A'))
            ->action('View Booking Details', url('/sub_three/my_bookings/details/' . $this->booking->uuid))
            ->line('Thank you for choosing our service!');
    }

    public function toSms($notifiable)
    {
        // Parse using correct format
        $startDateTime = \Carbon\Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $this->booking->date_start . ' ' . $this->booking->start_time
        );

        $serviceName = $this->booking->serviceName->service_name ?? 'N/A';
        $branchName = $this->booking->branch->branch_name ?? 'N/A';

        // Create SMS-friendly message
        $message = "Hi {$notifiable->first_name}! Your {$serviceName} booking at {$branchName} starts in 2 hours at {$startDateTime->format('g:i A')}. Ref: {$this->booking->booking_ref_no}";

        return [
            'phone_number' => $notifiable->contact_no,
            'message' => $message
        ];
    }

    public function toArray($notifiable)
    {
        // Use ONLY regular start time (not extended)
        $startDateTime = \Carbon\Carbon::parse($this->booking->date_start . ' ' . $this->booking->start_time);
        $serviceName = $this->booking->serviceName->service_name ?? 'N/A';

        // Keep customer message format as before
        $message = 'Your booking for ' . $serviceName . ' starts in 2 hours.';

        return [
            'booking_id' => $this->booking->id,
            'booking_ref_no' => $this->booking->booking_ref_no,
            'type' => 'booking_start_reminder',
            'title' => 'Booking Starting Soon',
            'message' => $message,
            'service_name' => $serviceName,
            'time' => $startDateTime->format('g:i A'),
            'date' => $startDateTime->format('M j, Y'),
            'full_start_datetime' => $startDateTime->format('Y-m-d H:i:s'),
            'url' => '/sub_three/my_bookings/details/' . $this->booking->uuid,
            'icon' => 'clock',
            'color' => 'blue',
            'is_extended' => false,
            'action' => 'booking_start_reminder',
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