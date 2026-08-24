<?php

namespace App\Notifications\Owner;

use App\Models\Branch;
use App\Models\LoyaltyTier; // Change this import
use Illuminate\Bus\Queueable;
use App\Models\CustomerReward;
use App\Models\CustomerAccount;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class CustomerRewardOwnerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $customerReward;
    protected $customer;
    protected $branch;
    protected $rewardTier; // This will now accept LoyaltyTier
    protected $actor;
    protected $action;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        CustomerReward $customerReward,
        CustomerAccount $customer,
        Branch $branch,
        LoyaltyTier $rewardTier, // Change type hint from RewardTier to LoyaltyTier
        $actor,
        string $action
    ) {
        $this->customerReward = $customerReward;
        $this->customer = $customer;
        $this->branch = $branch;
        $this->rewardTier = $rewardTier;
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
        $rewardName = $this->rewardTier->reward_description ?? 'Reward';
        $itemName = $this->rewardTier->redeemableItem->item_name ?? 'N/A';
        $customerName = $this->customer->first_name . ' ' . $this->customer->last_name;
        $voucherCode = $this->customerReward->voucher_code ?? 'N/A';
        $branchName = $this->branch ? $this->branch->branch_name : 'All Branches';
        
        return (new MailMessage)
            ->subject($this->getSubject())
            ->greeting('Hello ' . ($notifiable->name ?? 'Owner') . '!')
            ->line($this->getMessage())
            ->line('**Customer:** ' . $customerName)
            ->line('**Reward:** ' . $rewardName)
            ->line('**Item:** ' . $itemName)
            ->line('**Voucher Code:** ' . $voucherCode)
            ->line('**Branch:** ' . $branchName)
            ->action('Review Claim', url('/owner/rewards/' . $this->customerReward->id))
            ->line('Please review and take appropriate action.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'customer_reward_uuid' => $this->customerReward?->uuid,
            'customer_uuid' => $this->customer?->uuid,
            'customer_name' => $this->customer->first_name . ' ' . $this->customer->last_name,
            'branch_uuid' => $this->branch?->uuid,
            'branch_name' => $this->branch?->branch_name,
            'reward_tier_uuid' => $this->rewardTier?->uuid,
            'reward_description' => $this->rewardTier?->reward_description,
            'voucher_code' => $this->customerReward?->voucher_code,
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
        $customerName = $this->customer->first_name . ' ' . $this->customer->last_name;
        $rewardName = $this->rewardTier->reward_description ?? 'Reward';
        $branchName = $this->branch ? $this->branch->branch_name : 'All Branches';

        switch ($this->action) {
            case 'pending':
                return "Customer {$customerName} has claimed '{$rewardName}' and is pending approval at {$branchName}.";

            case 'claimed':
                return "Customer {$customerName} has claimed '{$rewardName}' at {$branchName}.";

            case 'declined':
                return "Customer {$customerName}'s claim for '{$rewardName}' was declined at {$branchName}.";

            case 'redeemed':
                return "Customer {$customerName} has redeemed '{$rewardName}' at {$branchName}.";

            default:
                return "Customer {$customerName} has performed an action on '{$rewardName}' at {$branchName}.";
        }
    }

    /**
     * Get notification subject for email
     */
    private function getSubject(): string
    {
        $customerName = $this->customer->first_name . ' ' . $this->customer->last_name;

        switch ($this->action) {
            case 'pending':
                return "New Reward Claim from {$customerName}";
            
            case 'claimed':
                return "Reward Claim Confirmed - {$customerName}";
            
            case 'declined':
                return "Reward Claim Declined - {$customerName}";
            
            case 'redeemed':
                return "Reward Redeemed - {$customerName}";
            
            default:
                return "Reward Status Update - {$customerName}";
        }
    }
}