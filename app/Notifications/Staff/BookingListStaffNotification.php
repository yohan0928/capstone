<?php

namespace App\Notifications\Staff;

use App\Models\Branch;
use App\Models\Booking;
use App\Models\ServiceName;
use Illuminate\Bus\Queueable;
use App\Models\CustomerAccount;
use App\Models\ServiceCategory;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class BookingListStaffNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $booking;
    protected $branch;
    protected $seat;
    protected $customer;
    protected $actor;
    protected $additionalData;
    protected $action;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking, Branch $branch, CustomerAccount $customer, $actor, string $action, array $additionalData = [])
    {
        $this->branch = $branch;
        $this->customer = $customer;
        $this->actor = $actor;
        $this->action = $action;
        $this->additionalData = $additionalData;
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
            'booking_ref_no' => $this->booking->booking_ref_no,
            'branch_uuid' => $this->branch->uuid,
            'customer_uuid' => $this->customer->uuid,
            'actor_id' => $this->actor->id,
            'action' => $this->action,
            'message' => $this->getMessage(),
            'timestamp' => now()->toDateTimeString(),
            'url' => route('sub_two.booking_lists.showBookingList', ['brn' => $this->booking->booking_ref_no]),
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
            case 'notes':
                return "{$actorName} added a NOTE to {$bookingRefNo} - {$customerName} at {$branch}";

            case 'confirmed':
                return "{$actorName} CONFIRMED the booking of {$bookingRefNo} - {$customerName} at {$branch}";

            case 'no_show':
                return "{$actorName} marked booking as NO SHOW for {$bookingRefNo} - {$customerName} at {$branch}";

            case 'feedback':  // Add this case
                $ratingStars = str_repeat('★', $this->additionalData['rating'] ?? 0)
                    . str_repeat('☆', 5 - ($this->additionalData['rating'] ?? 0));
                return "Someone submitted feedback at your {$branch} - Rating: {$ratingStars}";

            default:
                return "performed action on booking list for {$bookingRefNo} - {$customerName} at {$branch}";
        }
    }

    /**
     * Get notification subject for email
     */
    private function getSubject(): string
    {
        switch ($this->action) {
            case 'confirmed':
                return 'Booking Confirmed';

            case 'no_show':
                return 'BookingMarked as No Show';

            case 'notes':
                return 'Added Note';

            case 'feedback':
                return 'Feedback submitted for booking.';

            default:
                return 'Booking List Notification';
        }
    }
}
