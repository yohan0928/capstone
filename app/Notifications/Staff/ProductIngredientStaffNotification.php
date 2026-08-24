<?php

namespace App\Notifications\Staff;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Ingredient;
use App\Models\ProductIngredient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductIngredientStaffNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $productIngredient;
    protected $products;
    protected $ingredient;
    protected $branch;
    protected $actor;
    protected $action;
    protected $additionalData;

    /**
     * Create a new notification instance.
     */
    public function __construct(ProductIngredient $productIngredient, Product $product, Ingredient $ingredient, Branch $branch, $actor, string $action, array $additionalData = [])
    {
        $this->productIngredient = $productIngredient;
        $this->products = $product;
        $this->ingredient = $ingredient;
        $this->branch = $branch;
        $this->actor = $actor;
        $this->action = $action;
        $this->additionalData = $additionalData;
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
            'branch_uuid' => $this->branch->uuid,
            'product_ingredient_uuid' => $this->productIngredient->uuid,
            'product_uuid' => $this->products->uuid,
            'ingredient_uuid' => $this->ingredient->uuid,
            'actor_id' => $this->actor->id,
            'action' => $this->action,
            'message' => $this->getMessage(),
            'timestamp' => now()->toDateTimeString(),
            'additional_data' => $this->additionalData,
            'url' => route('sub_two.product_ingredients.showProductIngredient', ['product_uuid' => $this->products->uuid]),
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
        $branch = $this->branch->branch_name;
        $ingredient = $this->ingredient->ingredient_name;
        $quantity = $this->productIngredient->quantity_needed;
        $unit = $this->productIngredient->unit;
        $actorName = $this->actor->first_name . ' ' . $this->actor->last_name;

        switch ($this->action) {
            case 'created':
                return "{$actorName} created a new Ingredient in the Product Ingredient: {$ingredient} {$quantity} {$unit} at {$branch}";

            case 'updated':
                return "{$actorName} updated the Ingredient in the Product Ingredient: {$ingredient} to {$quantity} {$unit} at {$branch}";

            default:
                return "Someone performed an action on the Product Ingredient: {$ingredient} at {$branch}";
        }
    }

    /**
     * Get notification subject for email
     */
    private function getSubject(): string
    {
        switch ($this->action) {
            case 'created':
                return 'New Inredient Created';

            case 'updated':
                return 'Inredient Updated';

            case 'status_changed':
                return 'Inredient Status Changed';

            default:
                return 'Inredient Notification';
        }
    }
}