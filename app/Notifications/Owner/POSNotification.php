<?php

namespace App\Notifications\Owner;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\CustomerAccount;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class POSNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;
    protected $booking;
    protected $branch;
    protected $customer;
    protected $actor;
    protected $action;

    public function __construct(
        Order $order,
        $booking,
        Branch $branch,
        ?CustomerAccount $customer,
        $actor,
        string $action
    ) {
        $this->order    = $order;
        $this->booking  = $booking;
        $this->branch   = $branch;
        $this->customer = $customer;
        $this->actor    = $actor;
        $this->action   = $action;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->getSubject())
            ->line($this->getMessage())
            ->line('Thank you for using LinkudHub!');
    }

    public function toArray(object $notifiable): array
    {
        // Load items with product if not already loaded
        $this->order->loadMissing('items.product', 'payments');

        $items = $this->order->items->map(fn($item) => [
            'product_name' => $item->product?->product_name ?? 'Unknown',
            'quantity'     => $item->quantity,
            'unit'         => $item->product?->unit ?? '',
            'price'        => $item->selling_price,
            'discount'     => $item->discount_amount ?? 0,
            'sub_total'    => $item->sub_total,
        ]);

        $payment        = $this->order->payments->first();
        $totalAmount    = $payment?->total_amount ?? 0;
        $totalDiscount  = $payment?->discount ?? 0;
        $amountPaid     = $payment?->amount_paid ?? 0;
        $paymentMethod  = $this->resolvePaymentMethod($payment?->payment_method);
        $paymentStatus  = $payment?->order_payment_status === 1 ? 'Paid' : 'Unpaid';

        $data = [
            'type'           => 'pos_order',
            'action'         => $this->action,
            'message'        => $this->getMessage(),

            // Order
            'order_uuid'     => $this->order->uuid,
            'order_ref_no'   => $this->order->order_ref_no,
            'order_date'     => $this->order->order_date,

            // Branch & Actor
            'branch_uuid'    => $this->branch->uuid,
            'branch_name'    => $this->branch->branch_name,
            'actor_id'       => $this->actor->id,
            'actor_name'     => $this->actor->first_name . ' ' . $this->actor->last_name,

            // Items
            'items'          => $items,
            'items_count'    => $items->count(),
            'total_qty'      => $items->sum('quantity'),

            // Payment
            'total_amount'   => $totalAmount,
            'total_discount' => $totalDiscount,
            'amount_paid'    => $amountPaid,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,

            'timestamp'      => now()->toDateTimeString(),
            'url'            => route('sub_one.pos.history', ['orn' => $this->order->order_ref_no]),
        ];

        // Customer
        if ($this->customer) {
            $data['customer_uuid'] = $this->customer->uuid;
            $data['customer_name'] = $this->customer->first_name . ' ' . $this->customer->last_name;
        } else {
            $data['customer_uuid'] = null;
            $data['customer_name'] = 'Walk-in Customer';
        }

        // Booking
        if ($this->booking instanceof Booking) {
            $data['booking_uuid']    = $this->booking->uuid;
            $data['booking_ref_no']  = $this->booking->booking_ref_no;
        }

        return $data;
    }

    public function toBroadcast(object $notifiable): array
    {
        return [
            'id'         => $this->id,
            'type'       => get_class($this),
            'data'       => $this->toArray($notifiable),
            'read_at'    => null,
            'created_at' => now()->toDateTimeString(),
        ];
    }

    private function getMessage(): string
    {
        $this->order->loadMissing('items');

        $ref        = $this->order->order_ref_no;
        $branch     = $this->branch->branch_name;
        $actorName  = $this->actor->first_name . ' ' . $this->actor->last_name;
        $itemCount  = $this->order->items->count();
        $totalQty   = $this->order->items->sum('quantity');
        $customer   = $this->customer
            ? $this->customer->first_name . ' ' . $this->customer->last_name
            : 'Walk-in Customer';

        switch ($this->action) {
            case 'with_booking':
                return "{$actorName} processed booking order {$ref} for {$customer} — {$itemCount} item(s), {$totalQty} unit(s) at {$branch}.";

            case 'no_booking':
                return "{$actorName} processed walk-in order {$ref} for {$customer} — {$itemCount} item(s), {$totalQty} unit(s) at {$branch}.";

            default:
                return "{$actorName} processed order {$ref} at {$branch}.";
        }
    }

    private function getSubject(): string
    {
        return match ($this->action) {
            'with_booking' => 'Booking Order Confirmed — ' . $this->order->order_ref_no,
            'no_booking'   => 'Walk-in Order Confirmed — ' . $this->order->order_ref_no,
            default        => 'Order Notification',
        };
    }

    private function resolvePaymentMethod($method): string
    {
        return match ((string) $method) {
            '0'     => 'Cash',
            '1'     => 'GCash',
            '2'     => 'Debit Card',
            '3'     => 'Pay Later',
            default => 'Unknown',
        };
    }
}