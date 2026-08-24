<?php

namespace App\Notifications\Staff;

use App\Models\Branch;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductStaffNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $product;
    public $branch;
    public $actor;
    public $action;
    public $additionalData;
    public $transaction;

    public function __construct(Product|InventoryTransaction $product, Branch $branch, $actor, string $action, array $additionalData = [])
    {
        if ($product instanceof InventoryTransaction) {
            $this->transaction = $product;
            $this->product     = null;
        } else {
            $this->product     = $product;
            $this->transaction = null;
        }

        $this->branch         = $branch;
        $this->actor          = $actor;
        $this->action         = $action;
        $this->additionalData = $additionalData;
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
        if ($this->transaction) {
            $itemCount = $this->transaction->items?->count() ?? 0;
            $totalQty  = $this->transaction->items?->sum('quantity') ?? 0;

            return [
                'branch_uuid'        => $this->branch->uuid,
                'transaction_uuid'   => $this->transaction->uuid,
                'transaction_no'     => $this->transaction->transaction_no,
                'transaction_type'   => $this->transaction->type,
                'transaction_status' => $this->transaction->status,
                'items_count'        => $itemCount,
                'total_qty'          => $totalQty,
                'actor_id'           => $this->actor->id,
                'actor_name'         => $this->actor->first_name . ' ' . $this->actor->last_name,
                'action'             => $this->action,
                'message'            => $this->getMessage(),
                'timestamp'          => now()->toDateTimeString(),
                'additional_data'    => $this->additionalData,
                'url'                => route('sub_two.inventory.index'), // staff-side route
            ];
        }

        return [
            'branch_uuid'    => $this->branch->uuid,
            'product_uuid'   => $this->product->uuid,
            'actor_id'       => $this->actor->id,
            'actor_name'     => $this->actor->first_name . ' ' . $this->actor->last_name,
            'action'         => $this->action,
            'message'        => $this->getMessage(),
            'timestamp'      => now()->toDateTimeString(),
            'additional_data' => $this->additionalData,
            'url'            => route('sub_two.products.showProduct'),
        ];
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
        $branch    = $this->branch->branch_name;
        $actorName = $this->actor->first_name . ' ' . $this->actor->last_name;

        if ($this->transaction) {
            $txnNo     = $this->transaction->transaction_no;
            $itemCount = $this->transaction->items?->count() ?? 0;
            $totalQty  = $this->transaction->items?->sum('quantity') ?? 0;

            switch ($this->action) {
                case 'stock_in':
                    return "{$actorName} added stock in — {$itemCount} product(s), {$totalQty} item(s) total — {$txnNo} at {$branch}.";
                case 'stock_out':
                    return "{$actorName} declared stock out — {$itemCount} product(s), {$totalQty} item(s) total — {$txnNo} at {$branch}.";
                case 'approved':
                    return "{$actorName} approved transaction {$txnNo} at {$branch}.";
                case 'rejected':
                    return "{$actorName} rejected transaction {$txnNo} at {$branch}.";
                default:
                    return "{$actorName} performed an inventory action — {$txnNo} at {$branch}.";
            }
        }

        $product = $this->product->ingredient_name;

        switch ($this->action) {
            case 'created':
                return "{$actorName} created a new product: {$product} at {$branch}.";
            case 'updated':
                return "{$actorName} updated the product: {$product} at {$branch}.";
            case 'status_changed':
                $old = $this->additionalData['old_status'] ?? 'Unknown';
                $new = $this->additionalData['new_status'] ?? 'Unknown';
                return "{$actorName} changed {$product} status from {$old} to {$new} at {$branch}.";
            case 'deactivated':
                return "{$actorName} deactivated the product: {$product} at {$branch}.";
            case 'reactivated':
                return "{$actorName} reactivated the product: {$product} at {$branch}.";
            case 'expired':
                return "The product expired: {$product} at {$branch}.";
            case 'damaged':
                return "{$actorName} marked product as damaged: {$product} at {$branch}.";
            default:
                return "An action was performed on product: {$product} at {$branch}.";
        }
    }

    private function getSubject(): string
    {
        switch ($this->action) {
            case 'stock_in':  return 'New Stock In Transaction';
            case 'stock_out': return 'New Stock Out Transaction';
            case 'approved':  return 'Transaction Approved';
            case 'rejected':  return 'Transaction Rejected';
            case 'created':   return 'New Product Created';
            case 'updated':   return 'Product Updated';
            case 'status_changed': return 'Product Status Changed';
            case 'deactivated':    return 'Product Deactivated';
            case 'reactivated':    return 'Product Reactivated';
            default:               return 'Product Notification';
        }
    }
}