<?php

namespace App\Notifications\Owner;

use App\Models\Branch;
use App\Models\StaffAccount;
use App\Models\StaffCheckin;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffCheckinOneNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $branch;
    public $checkin;
    public $actor;
    public $action;

    /**
     * Create a new notification instance.
     */
    public function __construct(StaffCheckin $checkin, Branch $branch, $actor, string $action)
    {
        $this->checkin = $checkin;
        $this->branch = $branch;
        $this->actor = $actor;
        $this->action = $action;
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
            'staff_checkin_uuid' => $this->checkin->uuid,
            'branch_uuid' => $this->branch->uuid,
            'actor_id' => $this->actor->id,
            'action' => $this->action,
            'message' => $this->getMessage(),
            'timestamp' => now()->toDateTimeString(),
            'url' => route('sub_one.staff.showStaffList'),
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
        $actorName = $this->actor->first_name . ' ' . $this->actor->last_name;

        switch ($this->action) {
            case 'checked_out':
                return "{$actorName} checked out at {$branch}";

            case 'checked_in':
                return "{$actorName} checked in at {$branch}";

            default:
                return "Someone performed action on Staff Checkins at {$branch}";
        }
    }

    /**
     * Get notification subject for email
     */
    private function getSubject(): string
    {
        switch ($this->action) {
            case 'checked_out':
                return 'Staff Checked-out';

            case 'checked_in':
                return 'Staff Checked-in';

            default:
                return 'Staff Checkin Notification';
        }
    }
}