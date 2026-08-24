<?php

namespace App\Notifications\Customer;

use App\Models\Order;
use App\Models\Branch;
use App\Models\CustomerAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPaymentCustomerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;
    protected $customer;
    protected $branch;
    protected $actor;
    protected $action;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, Branch $branch, CustomerAccount $customerAccount, $actor, string $action)
    {
        $this->branch = $branch;
        $this->customer = $customerAccount;
        $this->actor = $actor;
        $this->action = $action;
        $this->order = $order;
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
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_uuid' => $this->order->uuid,
            'order_ref_no' => $this->order->order_ref_no,
            'customer_uuid' => $this->customer->uuid,
            'branch_uuid' => $this->branch->uuid,
            'actor_id' => $this->actor->id,
            'action' => $this->action,
            'message' => $this->getMessage(),
            'timestamp' => now()->toDateTimeString(),
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
        $orderRefNo = $this->order->order_ref_no;
        $customerName = $this->customer->first_name . ' ' . $this->customer->last_name;
        $branch = $this->branch->branch_name;
        $actorName = $this->actor->first_name . ' ' . $this->actor->last_name;

        switch ($this->action) {
            case 'order_created':
                return "{$actorName} created a new order: {$orderRefNo} | {$customerName} at {$branch}";

            case 'order_payment':
                return "{$actorName} processed order payment: {$orderRefNo} | {$customerName} paid at {$branch}";

            case 'order_updated':
                return "{$actorName} updated order: {$orderRefNo} | {$customerName} at {$branch}";

            case 'order_cancelled':
                return "{$actorName} cancelled order: {$orderRefNo} | {$customerName} at {$branch}";

            case 'order_completed':
                return "{$actorName} completed order: {$orderRefNo} | {$customerName} at {$branch}";

            default:
                return "Someone performed action on order: {$orderRefNo} | {$customerName} at {$branch}";
        }
    }

    /**
     * Get notification subject for email
     */
    private function getSubject(): string
    {
        switch ($this->action) {
            case 'order_created':
                return 'New Order Created';

            case 'order_payment':
                return 'Order Payment Processed';

            case 'order_updated':
                return 'Order Updated';

            case 'order_cancelled':
                return 'Order Cancelled';

            case 'order_completed':
                return 'Order Completed';

            default:
                return 'Order Notification';
        }
    }
}