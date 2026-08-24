<?php

namespace App\Notifications\Owner;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class BookingEndingSoonOwnerNotification extends Notification implements ShouldQueue
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
        $branchName = $this->booking->branch->branch_name ?? 'N/A';
        
        return (new MailMessage)
            ->subject('📊 Owner Alert: Booking Ending in 10 Minutes')
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line('**Business Monitoring Alert**')
            ->line('A booking is about to complete at one of your branches.')
            ->line('---')
            ->line('**Booking Summary:**')
            ->line('📋 Reference: ' . $this->booking->booking_ref_no)
            ->line('👤 Customer: ' . $customerName)
            ->line('🔧 Service: ' . $serviceName)
            ->line('⏰ End Time: ' . $endDateTime->format('M j, Y g:i A'))
            ->line('📍 Branch: ' . $branchName)
            ->line('💰 Revenue: $' . number_format($this->booking->total_amount ?? 0, 2))
            ->line('📝 Type: ' . (!empty($this->booking->extended_end_time) ? 'Extended Booking' : 'Regular Booking'))
            ->line('📊 Status: ' . ($this->booking->payment_status == 'paid' ? '✅ Paid' : '❌ Pending'))
            ->action('View Booking Details', url('/owner/bookings/' . $this->booking->id))
            ->line('---')
            ->line('*This is an automated business monitoring alert*');
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
            'type' => 'owner_booking_end_reminder',
            'title' => 'Booking Completing Soon',
            'message' => $message, // Simple message in requested format
            'customer_name' => $customerName,
            'service_name' => $serviceName,
            'branch_name' => $branchName,
            'end_time' => $endDateTime->format('g:i A'),
            'end_date' => $endDateTime->format('M j, Y'),
            'full_end_datetime' => $endDateTime->format('Y-m-d H:i:s'),
            'amount' => $this->booking->total_amount ?? 0,
            'payment_status' => $this->booking->payment_status,
            'url' => route('sub_one.booking_lists.details', $this->booking->id),
            'icon' => 'dollar-sign',
            'color' => 'info',
            'is_extended' => !empty($this->booking->extended_end_time),
            'priority' => 'medium',
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