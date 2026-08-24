<?php

namespace App\Notifications\Staff;

use App\Models\Branch;
use App\Models\CustomerAccount;
use App\Models\CustomerReward;
use App\Models\LoyaltyTier; // Changed from RewardTier to LoyaltyTier
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerRewardStaffNotification extends Notification implements ShouldQueue
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
        LoyaltyTier $rewardTier, // Changed from RewardTier to LoyaltyTier
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
        $customerName = $this->customer->first_name . ' ' . $this->customer->last_name;
        $branchName = $this->branch ? $this->branch->branch_name : 'All Branches';
        $rewardName = $this->rewardTier->reward_description ?? 'Reward';
        $voucherCode = $this->customerReward->voucher_code ?? 'N/A';
        $itemName = $this->rewardTier->redeemableItem->item_name ?? 'N/A';
        
        return (new MailMessage)
            ->subject($this->getSubject())
            ->greeting('Hello ' . ($notifiable->name ?? 'Staff') . '!')
            ->line($this->getMessage())
            ->line('**Customer:** ' . $customerName)
            ->line('**Reward:** ' . $rewardName)
            ->line('**Item:** ' . $itemName)
            ->line('**Voucher Code:** ' . $voucherCode)
            ->line('**Branch:** ' . $branchName)
            ->action('View Reward', url('/staff/rewards/' . $this->customerReward->id))
            ->line('Thank you for using LinkudHub!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'customer_reward_uuid' => $this->customerReward?->uuid,
            'customer_uuid' => $this->customer?->uuid,
            'branch_uuid' => $this->branch?->uuid,
            'reward_tier_uuid' => $this->rewardTier?->uuid,
            'reward_description' => $this->rewardTier?->reward_description,
            'voucher_code' => $this->customerReward?->voucher_code,
            'actor_id' => $this->actor?->id,
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
        $branchName = $this->branch ? $this->branch->branch_name : 'All Branches';
        $customerName = $this->customer->first_name . ' ' . $this->customer->last_name;
        $rewardName = $this->rewardTier->reward_description ?? 'Reward';
        
        // Determine actor name
        $actorName = 'System';
        if ($this->actor) {
            if (method_exists($this->actor, 'first_name')) {
                $actorName = $this->actor->first_name . ' ' . ($this->actor->last_name ?? '');
            } else {
                $actorName = $this->actor->name ?? 'Unknown';
            }
        }

        switch ($this->action) {
            case 'pending':
                return "{$customerName} has claimed '{$rewardName}' and is waiting for approval at {$branchName}.";

            case 'claimed':
                return "You confirmed the reward '{$rewardName}' for {$customerName} at {$branchName}.";

            case 'ready':
                return "The reward '{$rewardName}' for {$customerName} is now ready for redemption at {$branchName}.";

            case 'redeemed':
                return "{$customerName} has redeemed '{$rewardName}' at {$branchName}.";

            case 'declined':
                return "{$actorName} declined the reward '{$rewardName}' for {$customerName} at {$branchName}.";

            case 'expired':
                return "The reward '{$rewardName}' for {$customerName} has expired at {$branchName}.";

            default:
                return "{$customerName}'s reward '{$rewardName}' has been updated at {$branchName}.";
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
            
            case 'ready':
                return "Reward Ready for Redemption - {$customerName}";
            
            case 'redeemed':
                return "Reward Redeemed - {$customerName}";
            
            case 'declined':
                return "Reward Claim Declined - {$customerName}";
            
            case 'expired':
                return "Reward Expired - {$customerName}";
            
            default:
                return "Reward Status Update - {$customerName}";
        }
    }
}