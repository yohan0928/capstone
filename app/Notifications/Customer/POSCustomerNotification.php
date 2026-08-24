<?php

namespace App\Notifications\Customer;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\CustomerAccount;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class POSCustomerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;
    protected $booking;
    protected $branch;
    protected $customer;
    protected $actor;
    protected $action;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, $booking, Branch $branch, CustomerAccount $customer, $actor, string $action)
    {
        $this->order = $order;
        $this->booking = $booking; // Can be null or Booking object
        $this->branch = $branch;
        $this->customer = $customer;
        $this->actor = $actor;
        $this->action = $action;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        // Only send if we have a valid customer
        if (!$this->customer) {
            return [];
        }
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
        $data = [
            'order_ref_no' => $this->order->order_ref_no,
            'order_uuid' => $this->order->uuid,
            'branch_uuid' => $this->branch->uuid,
            'customer_uuid' => $this->customer->uuid,
            'actor_id' => $this->actor->id,
            'action' => $this->action,
            'message' => $this->getMessage(),
            'timestamp' => now()->toDateTimeString(),
        ];

        // Only add booking data if it exists
        if ($this->booking instanceof Booking) {
            $data['booking_uuid'] = $this->booking->uuid;
            $data['booking_ref_no'] = $this->booking->booking_ref_no;
        }

        return $data;
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
        $orderRefNo = $this->order->order_ref_no;
        $customerName = $this->customer->first_name . ' ' . $this->customer->last_name;
        $branch = $this->branch->branch_name;
        $actorName = $this->actor->first_name . ' ' . $this->actor->last_name;

        switch ($this->action) {
            case 'with_booking':
                return "{$actorName} added a booking order: {$orderRefNo} | {$customerName} at {$branch}";

            case 'no_booking':
                return "Thank you for your order: {$orderRefNo} at {$branch}";

            default:
                return "Someone make the order: {$orderRefNo} has been processed at {$branch}";
        }
    }

    /**
     * Get notification subject for email
     */
    private function getSubject(): string
    {
        switch ($this->action) {
            case 'with_booking':
                return "Booking Order Confirmed";

            case 'no_booking':
                return "Order Confirmation";

            default:
                return "Order Notification";
        }
    }
}