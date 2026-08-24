<?php

namespace App\Notifications\Owner;

use App\Models\Branch;
use App\Models\Ingredient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IngredientNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $ingredient;
    protected $branch;
    protected $actor;
    protected $action;
    protected $additionalData;

    /**
     * Create a new notification instance.
     */
    public function __construct(Ingredient $ingredient, Branch $branch, $actor, string $action, array $additionalData = [])
    {
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
            'ingredient_uuid' => $this->ingredient->uuid,
            'actor_id' => $this->actor->id,
            'action' => $this->action,
            'message' => $this->getMessage(),
            'timestamp' => now()->toDateTimeString(),
            'additional_data' => $this->additionalData,
            'url' => route('sub_one.ingredients.showIngredient'),
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
        $actorName = $this->actor->first_name . ' ' . $this->actor->last_name;

        switch ($this->action) {
            case 'created':
                return "{$actorName} created a new ingredient: {$ingredient} at {$branch}";

            case 'updated':
                return "{$actorName} updated the ingredient: {$ingredient} at {$branch}";

            case 'status_changed':
                $oldStatus = $this->additionalData['old_status'] ?? 'Unknown';
                $newStatus = $this->additionalData['new_status'] ?? 'Unknown';
                return "{$actorName} changed {$ingredient} status from {$oldStatus} to {$newStatus} at {$branch}";

            case 'deactivated':
                return "{$actorName} deactivated the ingredient: {$ingredient} at {$branch}";

            case 'reactivated':
                return "{$actorName} reactivated the ingredient: {$ingredient} at {$branch}";

            case 'expired':
                return "The ingredient expired: {$ingredient} at {$branch}";

            case 'damaged':
                return "{$actorName} updated the ingredient to damaged: {$ingredient} at {$branch}";

            default:
                return "performed action on the Ingredient: {$ingredient} at {$branch}";
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

            case 'deactivated':
                return 'Inredient Deactivated';

            case 'reactivated':
                return 'Inredient Reactivated';

            default:
                return 'Inredient Notification';
        }
    }
}