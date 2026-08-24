<?php

namespace App\Notifications\Owner;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\CustomerAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingListPaymentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $booking;
    protected $customer;
    protected $branch;
    protected $actor;
    protected $action;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking, Branch $branch, CustomerAccount $customerAccount, $actor, string $action)
    {
        $this->branch = $branch;
        $this->customer = $customerAccount;
        $this->actor = $actor;
        $this->action = $action;
        $this->booking = $booking;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->getSubject())
            ->line($this->getMessage())
            ->line('Thank you for using LinkudHub!');
    }

    /**
     * Get the array representation of the notification.
     */
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
            'url' => route('sub_one.booking_lists.showBookingList', ['brn' => $this->booking->booking_ref_no]),
        ];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): array
    {
        return [
            'id' => $this->id,
            'type' => get_class($this),
            'data' => $this->toArray($notifiable),
            'read_at' => null,
            'created_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get notification message based on action
     */
    private function getMessage(): string
    {
        $bookingRefNo = $this->booking->booking_ref_no;
        $customerName = $this->customer->first_name . ' ' . $this->customer->last_name;
        $branch = $this->branch->branch_name;
        $actorName = $this->actor->first_name . ' ' . $this->actor->last_name;

        switch ($this->action) {
            case 'main_payment':
                return "{$actorName} updated the main payment for this booking: {$bookingRefNo} | {$customerName} paid at {$branch}";

            case 'extension_payment':
                return "{$actorName} updated the extension payment for this booking: {$bookingRefNo} | {$customerName} paid at {$branch}";

            default:
                return "Someone performed an action on the Booking List Payment: {$bookingRefNo} | {$customerName} at {$branch}";
        }
    }

    /**
     * Get notification subject for email
     */
    private function getSubject(): string
    {
        switch ($this->action) {
            case 'main_payment':
                return 'Main Payment updated';

            case 'extension_payment':
                return 'Extension Payment updated';

            default:
                return 'Booking List Payment Notification';
        }
    }
}
