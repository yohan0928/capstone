<?php

namespace App\Notifications\Staff;

use App\Models\Branch;
use App\Models\ServiceCategory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServiceCategoryStaffNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $branch;
    protected $serviceCategory;
    protected $actor;
    protected $action;
    protected $additionalData;

    /**
     * Create a new notification instance.
     */
    public function __construct(Branch $branch, ServiceCategory $serviceCategory, $actor, string $action, array $additionalData = [])
    {
        $this->branch = $branch;
        $this->serviceCategory = $serviceCategory;
        $this->actor = $actor;
        $this->action = $action;
        $this->additionalData = $additionalData;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
            'service_category_uuid' => $this->serviceCategory->uuid,
            'actor_id' => $this->actor->id,
            'action' => $this->action,
            'message' => $this->getMessage(),
            'timestamp' => now()->toDateTimeString(),
            'additional_data' => $this->additionalData,
            'url' => route('sub_two.service_categories.showServiceCategory', ['branch' => $this->branch->uuid]),
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
        $serviceCategory = $this->serviceCategory->service_category;
        $actorName = $this->actor->first_name . ' ' . $this->actor->last_name;

        switch ($this->action) {
            case 'status_changed':
                $oldStatus = $this->additionalData['old_status'] ?? 'Unknown';
                $newStatus = $this->additionalData['new_status'] ?? 'Unknown';
                return "{$actorName} changed {$serviceCategory} status from {$oldStatus} to {$newStatus} at {$branch}";

            default:
                return "Someone performed action on Service Category: {$serviceCategory} at {$branch}";
        }
    }

    /**
     * Get notification subject for email
     */
    private function getSubject(): string
    {
        switch ($this->action) {
            case 'status_changed':
                return 'Service Category Status Changed';

            default:
                return 'Service Category Notification';
        }
    }
}
