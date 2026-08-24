<?php

namespace App\Notifications\Staff;

use App\Models\Branch;
use App\Models\RewardTier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RewardTierStaffNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $branch;
    protected $rewardTier;
    protected $actor;
    protected $action;
    public $additionalData;

    /**
     * Create a new notification instance.
     */
    public function __construct(Branch $branch, RewardTier $rewardTier, $actor, string $action, array $additionalData = [])
    {
        $this->branch = $branch;
        $this->rewardTier = $rewardTier;
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
            'reward_uuid' => $this->rewardTier->uuid,
            'actor_id' => $this->actor->id,
            'action' => $this->action,
            'message' => $this->getMessage(),
            'timestamp' => now()->toDateTimeString(),
            'url' => route('sub_two.reward_tiers.index'),
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
        $rewardTier = $this->rewardTier->reward_description;
        $actorName = $this->actor->first_name . ' ' . $this->actor->last_name;

        switch ($this->action) {
            case 'created':
                return "{$actorName} created a new {$rewardTier} at {$branch}";

            case 'updated':
                return "{$actorName} updated the {$rewardTier} reward at {$branch}";

            case 'status_changed':
                $oldStatus = $this->additionalData['old_status'] ?? 'Unknown';
                $newStatus = $this->additionalData['new_status'] ?? 'Unknown';
                return "{$actorName} changed {$rewardTier} status from {$oldStatus} to {$newStatus}";

            default:
                return "Someone performed action on Reward Tier: {$rewardTier}";
        }
    }

    /**
     * Get notification subject for email
     */
    private function getSubject(): string
    {
        switch ($this->action) {
            case 'created':
                return 'New Reward Tier Created';

            case 'updated':
                return 'Reward Tier Updated';

            case 'status_changed':
                return 'Reward Tier Status Changed';

            case 'deactivated':
                return 'Reward Tier Deactivated';

            case 'reactivated':
                return 'Reward Tier Reactivated';

            default:
                return 'Reward Tier Notification';
        }
    }
}
