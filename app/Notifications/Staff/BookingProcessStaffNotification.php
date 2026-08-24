<?php

namespace App\Notifications\Staff;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\CustomerAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingProcessStaffNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $booking;
    public $customer;
    public $branch;
    public $actor;
    public $action;

    public function __construct(Booking $booking, Branch $branch, CustomerAccount $customer, $actor, string $action)
    {
        $this->booking = $booking;
        $this->branch = $branch;
        $this->customer = $customer;
        $this->actor = $actor;
        $this->action = $action;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->getSubject())
            ->line($this->getMessage());
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_uuid' => $this->booking->uuid,
            'booking_ref_no' => $this->booking->booking_ref_no,
            'customer_uuid' => $this->customer->uuid,
            'branch_uuid' => $this->branch->uuid,
            'actor_id' => $this->actor->id,
            'action' => $this->action,
            'message' => $this->getMessage(),
            'timestamp' => now()->toDateTimeString(),
            'url' => $this->getUrl(),
        ];
    }

    private function getMessage(): string
    {
        $bookingRefNo = $this->booking->booking_ref_no;
        $customerName = $this->customer->first_name . ' ' . $this->customer->last_name;
        $branch = $this->branch->branch_name;
        $actorName = $this->actor->first_name . ' ' . $this->actor->last_name;

        switch ($this->action) {
            case 'created':
                return "New booking {$bookingRefNo} created by {$customerName} for {$branch}. Requires staff verification.";
            default:
                return "Booking {$bookingRefNo} updated by {$customerName}.";
        }
    }

    private function getSubject(): string
    {
        switch ($this->action) {
            case 'created':
                return 'New Booking Created - Requires Verification';
            default:
                return 'Booking Update Notification';
        }
    }

    private function getUrl(): string
    {
        // This should be the staff booking details URL
        return route('sub_two.booking_lists.showBookingList', ['brn' => $this->booking->booking_ref_no]);
    }
}