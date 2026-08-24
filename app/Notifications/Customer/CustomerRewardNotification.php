<?php

namespace App\Notifications\Customer;

use App\Models\Branch;
use App\Models\LoyaltyTier; // Changed from RewardTier to LoyaltyTier
use Illuminate\Bus\Queueable;
use App\Models\CustomerReward;
use App\Models\CustomerAccount;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class CustomerRewardNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $customerReward;
    protected $customer;
    protected $branch;
    protected $rewardTier; // This will now accept LoyaltyTier
    protected $action;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        CustomerReward $customerReward, 
        CustomerAccount $customer, 
        Branch $branch, 
        LoyaltyTier $rewardTier, // Changed from RewardTier to LoyaltyTier
        string $action
    ) {
        $this->customerReward = $customerReward;
        $this->customer = $customer;
        $this->branch = $branch;
        $this->rewardTier = $rewardTier;
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
        $branchName = $this->branch ? $this->branch->branch_name : 'All Branches';
        $customerName = $this->customer->first_name . ' ' . ($this->customer->last_name ?? '');
        $rewardName = $this->rewardTier->reward_description ?? 'Reward';
        $itemName = $this->rewardTier->redeemableItem->item_name ?? 'N/A';
        $voucherCode = $this->customerReward->voucher_code ?? 'N/A';
        
        return (new MailMessage)
            ->subject($this->getSubject())
            ->greeting('Hello ' . $customerName . '!')
            ->line($this->getMessage())
            ->line('**Reward:** ' . $rewardName)
            ->line('**Item:** ' . $itemName)
            ->line('**Voucher Code:** ' . $voucherCode)
            ->line('**Branch:** ' . $branchName)
            ->action('View My Rewards', url('/customer/my-rewards'))
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
        $rewardName = $this->rewardTier->reward_description ?? 'Reward';
        $customerName = $this->customer->first_name . ' ' . ($this->customer->last_name ?? '');

        switch ($this->action) {
            case 'pending':
                return "Your reward '{$rewardName}' is pending approval at {$branchName}. Please wait for confirmation.";

            case 'ready':
                return "Your reward '{$rewardName}' is now READY for redemption at {$branchName}!";

            case 'claimed':
                return "Your reward '{$rewardName}' has been CONFIRMED at {$branchName}.";

            case 'redeemed':
                return "Your reward '{$rewardName}' has been successfully REDEEMED at {$branchName}.";

            case 'declined':
                return "Your reward '{$rewardName}' has been DECLINED at {$branchName}.";

            case 'expired':
                return "Your reward '{$rewardName}' has EXPIRED at {$branchName}.";

            default:
                return "Your reward '{$rewardName}' status has been updated at {$branchName}.";
        }
    }

    /**
     * Get notification subject for email
     */
    private function getSubject(): string
    {
        $rewardName = $this->rewardTier->reward_description ?? 'Reward';

        switch ($this->action) {
            case 'pending':
                return 'Reward Claim Pending Approval';
            
            case 'ready':
                return 'Reward Ready for Redemption!';
            
            case 'claimed':
                return 'Reward Claim Confirmed';
            
            case 'redeemed':
                return 'Reward Redeemed Successfully';
            
            case 'declined':
                return 'Reward Claim Declined';
            
            case 'expired':
                return 'Reward Expired';
            
            default:
                return 'Reward Status Update';
        }
    }
}