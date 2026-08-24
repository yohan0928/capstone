<?php

namespace App\Notifications\Staff;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class BookingEndingSoonStaffNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $booking;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toMail($notifiable)
    {
        // Use extended time if available, otherwise use regular time
        $endDate = $this->booking->extended_date_end ?? $this->booking->date_end;
        $endTime = $this->booking->extended_end_time ?? $this->booking->end_time;
        
        $endDateTime = \Carbon\Carbon::parse($endDate . ' ' . $endTime);
        $serviceName = $this->booking->serviceName->service_name ?? 'N/A';
        $customerName = $this->booking->customerAccount ? 
            $this->booking->customerAccount->first_name . ' ' . $this->booking->customerAccount->last_name : 
            'Unknown Customer';
        
        return (new MailMessage)
            ->subject('🔔 Staff Alert: Booking Ending in 10 Minutes')
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line('**A booking at your branch will end soon**')
            ->line('Please prepare for customer check-out.')
            ->line('---')
            ->line('**Booking Details:**')
            ->line('📋 Reference: ' . $this->booking->booking_ref_no)
            ->line('👤 Customer: ' . $customerName)
            ->line('🔧 Service: ' . $serviceName)
            ->line('⏰ End Time: ' . $endDateTime->format('M j, Y g:i A'))
            ->line('📍 Location: ' . ($this->booking->branch->branch_name ?? 'N/A'))
            ->line('📝 Type: ' . (!empty($this->booking->extended_end_time) ? 'Extended Booking' : 'Regular Booking'))
            ->action('View Booking Details', url('/staff/bookings/' . $this->booking->id))
            ->line('---')
            ->line('*This is an automated reminder*');
    }

    public function toArray($notifiable)
    {
        // Use extended time if available, otherwise use regular time
        $endDate = $this->booking->extended_date_end ?? $this->booking->date_end;
        $endTime = $this->booking->extended_end_time ?? $this->booking->end_time;
        
        $endDateTime = \Carbon\Carbon::parse($endDate . ' ' . $endTime);
        $serviceName = $this->booking->serviceName->service_name ?? 'N/A';
        $customerName = $this->booking->customerAccount ? 
            $this->booking->customerAccount->first_name . ' ' . $this->booking->customerAccount->last_name : 
            'Customer';
        $branchName = $this->booking->branch->branch_name ?? 'N/A';
        
        // Format the message as requested: "remind (customer name) 10 mins before their end time"
        $message = 'Remind ' . $customerName . ' 10 mins before their end time.';
        
        return [
            'booking_id' => $this->booking->id,
            'booking_ref_no' => $this->booking->booking_ref_no,
            'type' => 'staff_booking_end_reminder',
            'title' => 'Booking Ending Soon',
            'message' => $message, // Simple message in requested format
            'customer_name' => $customerName,
            'service_name' => $serviceName,
            'branch_name' => $branchName,
            'time' => $endDateTime->format('g:i A'),
            'date' => $endDateTime->format('M j, Y'),
            'full_end_datetime' => $endDateTime->format('Y-m-d H:i:s'),
            'url' => route('sub_two.booking_lists.details', $this->booking->id),
            'icon' => 'clock',
            'color' => 'warning',
            'is_extended' => !empty($this->booking->extended_end_time),
            'priority' => 'high',
            'action' => 'booking_end_reminder',
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'read_at' => null,
            'data' => $this->toArray($notifiable),
            'created_at' => now(),
        ]);
    }
}