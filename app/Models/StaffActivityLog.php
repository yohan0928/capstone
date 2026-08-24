<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_account_id',
        'owner_account_id',
        'branch_id',
        'booking_id',
        'action_type',
        'description',
        'metadata',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function staff()
    {
        return $this->belongsTo(StaffAccount::class, 'staff_account_id');
    }

    public function owner()
    {
        return $this->belongsTo(OwnerAccount::class, 'owner_account_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    // Action type constants
    const ACTION_CONFIRM_BOOKING = 'confirm_booking';
    const ACTION_MARK_NO_SHOW = 'mark_no_show';
    const ACTION_ADD_NOTE = 'add_note';
    const ACTION_UPDATE_PAYMENT = 'update_payment';
    const ACTION_UPDATE_EXTENSION_PAYMENT = 'update_extension_payment';
    const ACTION_UPDATE_BOOKING = 'update_booking';
    const ACTION_CANCEL_BOOKING = 'cancel_booking';
    const ACTION_RESCHEDULE_BOOKING = 'reschedule_booking';
    const ACTION_COMPLETE_BOOKING = 'complete_booking';
    
    const ACTION_PROCESS_MAIN_PAYMENT = 'process_main_payment';
const ACTION_PROCESS_EXTENSION_PAYMENT = 'process_extension_payment';
const ACTION_PROCESS_ORDER_PAYMENT = 'process_order_payment';

const ACTION_CREATE_BOOKING = 'create_booking';
const ACTION_CHECKIN_CUSTOMER = 'checkin_customer';

const ACTION_UPDATE_BRANCH_STATUS = 'update_branch_status';

const ACTION_EXTEND_TIME = 'extend_time';

const ACTION_PROCESS_REWARDS = 'process_rewards';
const ACTION_UPDATE_REWARD_STATUS = 'update_reward_status';
const ACTION_CREATE_INGREDIENT = 'create_ingredient';
const ACTION_UPDATE_INGREDIENT = 'update_ingredient';
const ACTION_UPDATE_INGREDIENT_STATUS = 'update_ingredient_status';
const ACTION_DEACTIVATE_INGREDIENT = 'deactivate_ingredient';
const ACTION_REACTIVATE_INGREDIENT = 'reactivate_ingredient';
const ACTION_DAMAGE_INGREDIENT = 'damage_ingredient';
const ACTION_PROCESS_POS_ORDER = 'process_pos_order';
const ACTION_UPDATE_STOCK = 'update_stock';
const ACTION_CREATE_PRODUCT = 'create_product';
const ACTION_UPDATE_PRODUCT = 'update_product';
const ACTION_UPDATE_PRODUCT_STATUS = 'update_product_status';
const ACTION_DEACTIVATE_PRODUCT = 'deactivate_product';
const ACTION_REACTIVATE_PRODUCT = 'reactivate_product';
const ACTION_DAMAGE_PRODUCT = 'damage_product';

const ACTION_ADD_PRODUCT_INGREDIENT = 'add_product_ingredient';
const ACTION_UPDATE_PRODUCT_INGREDIENT = 'update_product_ingredient';
const ACTION_CREATE_REWARD_TIER = 'create_reward_tier';
const ACTION_UPDATE_REWARD_TIER = 'update_reward_tier';
const ACTION_UPDATE_REWARD_TIER_STATUS = 'update_reward_tier_status';

const ACTION_UPDATE_SEAT_STATUS = 'update_seat_status';

const ACTION_CHECKOUT_CUSTOMER = 'checkout_customer';

const ACTION_UPDATE_SERVICE_CATEGORY_STATUS = 'update_service_category_status';
    const ACTION_UPDATE_SERVICE_NAME_STATUS = 'update_service_name_status';
    const ACTION_STAFF_CHECKIN = 'staff_checkin';
    const ACTION_STAFF_CHECKOUT = 'staff_checkout';

    // Helper to get action labels
    public static function getActionLabels()
    {
        return [
            self::ACTION_CONFIRM_BOOKING => 'Confirmed Booking',
            self::ACTION_MARK_NO_SHOW => 'Marked as No Show',
            self::ACTION_ADD_NOTE => 'Added Note',
            self::ACTION_UPDATE_PAYMENT => 'Updated Main Payment',
            self::ACTION_UPDATE_EXTENSION_PAYMENT => 'Updated Extension Payment',
            self::ACTION_UPDATE_BOOKING => 'Updated Booking',
            self::ACTION_CANCEL_BOOKING => 'Cancelled Booking',
            self::ACTION_RESCHEDULE_BOOKING => 'Rescheduled Booking',
            self::ACTION_COMPLETE_BOOKING => 'Completed Booking',
            self::ACTION_PROCESS_MAIN_PAYMENT => 'Processed Main Payment', 
        self::ACTION_PROCESS_EXTENSION_PAYMENT => 'Processed Extension Payment',
        self::ACTION_PROCESS_ORDER_PAYMENT => 'Processed Order Payment',
        self::ACTION_CREATE_BOOKING => 'Created Booking',
        self::ACTION_CHECKIN_CUSTOMER => 'Checked In Customer', 
        self::ACTION_UPDATE_BRANCH_STATUS => 'Updated Branch Status',
        self::ACTION_EXTEND_TIME => 'Extended Time',
        self::ACTION_PROCESS_REWARDS => 'Processed Rewards',  
        self::ACTION_UPDATE_REWARD_STATUS => 'Updated Reward Status',  
        self::ACTION_CREATE_INGREDIENT => 'Created Ingredient',  
        self::ACTION_UPDATE_INGREDIENT => 'Updated Ingredient',  
        self::ACTION_UPDATE_INGREDIENT_STATUS => 'Updated Ingredient Status',  
        self::ACTION_DEACTIVATE_INGREDIENT => 'Deactivated Ingredient',  
        self::ACTION_REACTIVATE_INGREDIENT => 'Reactivated Ingredient',  
        self::ACTION_DAMAGE_INGREDIENT => 'Damaged Ingredient',
        self::ACTION_PROCESS_POS_ORDER => 'Processed POS Order',  
        self::ACTION_UPDATE_STOCK => 'Updated Stock',  
        self::ACTION_CREATE_PRODUCT => 'Created Product',  
        self::ACTION_UPDATE_PRODUCT => 'Updated Product',  
        self::ACTION_UPDATE_PRODUCT_STATUS => 'Updated Product Status',  
        self::ACTION_DEACTIVATE_PRODUCT => 'Deactivated Product',  
        self::ACTION_REACTIVATE_PRODUCT => 'Reactivated Product',  
        self::ACTION_DAMAGE_PRODUCT => 'Damaged Product',
        self::ACTION_ADD_PRODUCT_INGREDIENT => 'Added Product Ingredient',  
        self::ACTION_UPDATE_PRODUCT_INGREDIENT => 'Updated Product Ingredient',  
        self::ACTION_CREATE_REWARD_TIER => 'Created Reward Tier',  
        self::ACTION_UPDATE_REWARD_TIER => 'Updated Reward Tier',  
        self::ACTION_UPDATE_REWARD_TIER_STATUS => 'Updated Reward Tier Status',
        self::ACTION_UPDATE_SEAT_STATUS => 'Updated Seat Status',
         self::ACTION_CHECKOUT_CUSTOMER => 'Checked Out Customer',
         self::ACTION_UPDATE_SERVICE_CATEGORY_STATUS => 'Updated Service Category Status',
            self::ACTION_UPDATE_SERVICE_NAME_STATUS => 'Updated Service Name Status',
            self::ACTION_STAFF_CHECKIN => 'Staff Checked In',
            self::ACTION_STAFF_CHECKOUT => 'Staff Checked Out',
        ];
    }

    public function getActionLabel()
    {
        return self::getActionLabels()[$this->action_type] ?? $this->action_type;
    }

    public function getFormattedMetadata()
{
    if (!$this->metadata) {
        return null;
    }

    $metadata = $this->metadata;
    
    // If metadata is a string, decode it
    if (is_string($metadata)) {
        $decoded = json_decode($metadata, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $metadata = $decoded;
        } else {
            // If it's a string that can't be decoded, treat it as a single value
            return [['Key' => 'Information', 'Value' => $metadata]];
        }
    }
    
    // If metadata is already an array, use it
    if (!is_array($metadata)) {
        return null;
    }

    $formatted = [];

    foreach ($metadata as $key => $value) {
        $formattedKey = str_replace('_', ' ', ucfirst($key));
        
        if (is_array($value)) {
            $formattedValue = json_encode($value, JSON_PRETTY_PRINT);
        } elseif (is_bool($value)) {
            $formattedValue = $value ? 'Yes' : 'No';
        } elseif ($value === null) {
            $formattedValue = 'N/A';
        } else {
            $formattedValue = (string) $value;
        }
        
        $formatted[] = [
            'key' => $formattedKey,
            'value' => $formattedValue
        ];
    }

    return $formatted;
}
}