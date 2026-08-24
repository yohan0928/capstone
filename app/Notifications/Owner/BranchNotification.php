<?php

namespace App\Notifications\Owner;

use App\Models\Branch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BranchNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $branch;
    public $actor;
    public $action;
    public $additionalData;

    /**
     * Create a new notification instance.
     */
    public function __construct(Branch $branch, $actor, string $action, array $additionalData = [])
    {
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
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'branch_uuid' => $this->branch->uuid,
            'actor_id' => $this->actor->id,
            'action' => $this->action,
            'message' => $this->getMessage(),
            'timestamp' => now()->toDateTimeString(),
            'additional_data' => $this->additionalData,
            'url' => route('sub_one.branches.showBranch'),
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
        $actorName = $this->actor->first_name . ' ' . $this->actor->last_name;
        $branch = $this->branch->branch_name;

        switch ($this->action) {
            case 'status_changed':
                $oldStatus = $this->additionalData['old_status'] ?? 'Unknown';
                $newStatus = $this->additionalData['new_status'] ?? 'Unknown';
                return "{$actorName} changed {$branch} status from {$oldStatus} to {$newStatus}";

            default:
                return "Someone performed an action on the Branch: {$branch}";
        }
    }

    /**
     * Get notification subject for email
     */
    private function getSubject(): string
    {
        switch ($this->action) {
            case 'status_changed':
                return 'Branch Status Changed';

            default:
                return 'Branch Notification';
        }
    }
}
