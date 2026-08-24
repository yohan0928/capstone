<?php

namespace App\Notifications\Owner;

use App\Models\Branch;
use App\Models\StaffAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffListOneNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $branch;
    public $staff;
    public $actor;
    public $action;
    public $additionalData;

    /**
     * Create a new notification instance.
     */
    public function __construct(?Branch $branch, StaffAccount $staff, $actor, $action)
    {
        $this->branch = $branch;
        $this->actor = $actor;
        $this->action = $action;
        $this->staff = $staff;
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
            'branch_uuid' => $this->getBranchUuid(),
            'staff_uuid' => $this->staff->uuid,
            'actor_id' => $this->actor->id,
            'action' => $this->action,
            'message' => $this->getMessage(),
            'timestamp' => now()->toDateTimeString(),
            'additional_data' => $this->additionalData,
            'url' => route('sub_one.staff.showStaffList', ['staff_uuid' => $this->staff->uuid]),
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
        $staffName = $this->staff->first_name . ' ' . $this->staff->last_name;
        
        // Only include branch name if branch exists and is not null
        $branchName = $this->branch ? $this->branch->branch_name : null;

        switch ($this->action) {
            case 'account_created':
                return "{$actorName} created a new account for {$staffName}.";

            case 'account_status_updated':
                if ($branchName) {
                    return "{$actorName} updated {$staffName}'s account at {$branchName}.";
                }
                return "{$actorName} updated {$staffName}'s account.";

            case 'account_deactivated':
                if ($branchName) {
                    return "{$actorName} deactivated {$staffName}'s account at {$branchName}.";
                }
                return "{$actorName} deactivated {$staffName}'s account.";

            case 'account_reactivated':
                if ($branchName) {
                    return "{$actorName} reactivated {$staffName}'s account at {$branchName}.";
                }
                return "{$actorName} reactivated {$staffName}'s account.";

            case 'shift_created':
                if ($branchName) {
                    return "{$actorName} created a new shift for {$staffName} at {$branchName}.";
                }
                return "{$actorName} created a new shift for {$staffName}.";

            case 'shift_updated':
                if ($branchName) {
                    return "{$actorName} updated {$staffName}'s shift at {$branchName}.";
                }
                return "{$actorName} updated {$staffName}'s shift.";

            case 'shift_deactivated':
                return "{$actorName} deactivated {$staffName}'s shift.";

            default:
                return "Someone performed action on Staff List.";
        }
    }

    /**
     * Get branch UUID safely - returns null if no branch is assigned
     */
    private function getBranchUuid(): ?string
    {
        return $this->branch ? $this->branch->uuid : null;
    }

    /**
     * Get notification subject for email
     */
    private function getSubject(): string
    {
        switch ($this->action) {
            case 'account_created':
                return 'New Staff Account Created';

            case 'account_status_updated':
                return 'Staff Account Status Updated';

            case 'account_deactivated':
                return 'Staff Account Deactivated';

            case 'account_reactivated':
                return 'Staff Account Reactivated';

            case 'shift_created':
                return 'New Shift Created';

            case 'shift_updated':
                return 'Shift Updated';

            case 'shift_deactivated':
                return 'Shift Deactivated';

            default:
                return 'Staff Notification';
        }
    }
}