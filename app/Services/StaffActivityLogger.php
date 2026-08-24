<?php

namespace App\Services;

use App\Models\StaffActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffActivityLogger
{
    public static function log(
        string $actionType,
        string $description,
        ?int $bookingId = null,
        ?array $metadata = null,
        ?Request $request = null
    ) {
        // Get the authenticated user (could be owner or staff)
        $user = null;
        $ownerId = null;
        $branchId = null;
        $staffId = null;
        
        if (Auth::guard('staff')->check()) {
            $user = Auth::guard('staff')->user();
            $ownerId = $user->owner_account_id;
            $branchId = $user->branch_id;
            $staffId = $user->id;
        } elseif (Auth::guard('owner')->check()) {
            $user = Auth::guard('owner')->user();
            $ownerId = $user->id;
            
            // If we have a booking ID, get the branch from the booking
            if ($bookingId) {
                $booking = \App\Models\Booking::find($bookingId);
                $branchId = $booking ? $booking->branch_id : null;
            }
            $staffId = null; // Owner actions don't have staff ID
        } else {
            return; // No authenticated user
        }

        // Get request info if available
        $ipAddress = $request ? $request->ip() : request()->ip();
        $userAgent = $request ? $request->userAgent() : request()->userAgent();

        // Create the log entry
        StaffActivityLog::create([
            'staff_account_id' => $staffId,
            'owner_account_id' => $ownerId,
            'branch_id' => $branchId,
            'booking_id' => $bookingId,
            'action_type' => $actionType,
            'description' => $description,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent
        ]);
    }

    // Helper methods for common actions
    public static function logConfirmBooking($booking, Request $request = null)
    {
        self::log(
            StaffActivityLog::ACTION_CONFIRM_BOOKING,
            "Confirmed booking #{$booking->booking_ref_no}",
            $booking->id,
            [
                'booking_ref_no' => $booking->booking_ref_no,
                'customer_id' => $booking->customer_account_id,
                'customer_email' => $booking->customerAccount->email,
                'service' => $booking->serviceName->service_name ?? 'N/A',
                'booking_date' => $booking->date_start,
                'status_before' => 'pending',
                'status_after' => 'confirmed'
            ],
            $request
        );
    }

    public static function logMarkNoShow($booking, Request $request = null)
    {
        self::log(
            StaffActivityLog::ACTION_MARK_NO_SHOW,
            "Marked booking #{$booking->booking_ref_no} as No Show",
            $booking->id,
            [
                'booking_ref_no' => $booking->booking_ref_no,
                'customer_id' => $booking->customer_account_id,
                'customer_name' => "{$booking->customerAccount->first_name} {$booking->customerAccount->last_name}",
                'service' => $booking->serviceName->service_name ?? 'N/A',
                'scheduled_time' => $booking->start_time,
                'status_before' => 'booked',
                'status_after' => 'no_show'
            ],
            $request
        );
    }

    public static function logAddNote($booking, $noteContent, Request $request = null)
    {
        self::log(
            StaffActivityLog::ACTION_ADD_NOTE,
            "Added note to booking #{$booking->booking_ref_no}",
            $booking->id,
            [
                'booking_ref_no' => $booking->booking_ref_no,
                'customer_id' => $booking->customer_account_id,
                'note_preview' => substr($noteContent, 0, 100) . (strlen($noteContent) > 100 ? '...' : ''),
                'note_length' => strlen($noteContent)
            ],
            $request
        );
    }

    public static function logUpdatePayment($booking, $paymentData, Request $request = null)
    {
        self::log(
            StaffActivityLog::ACTION_UPDATE_PAYMENT,
            "Updated payment for booking #{$booking->booking_ref_no}",
            $booking->id,
            [
                'booking_ref_no' => $booking->booking_ref_no,
                'customer_id' => $booking->customer_account_id,
                'payment_amount' => $paymentData['amount'] ?? 0,
                'payment_method' => $paymentData['method'] ?? 'unknown',
                'payment_status' => $paymentData['status'] ?? 'unknown',
                'previous_status' => $paymentData['previous_status'] ?? 'unknown'
            ],
            $request
        );
    }
    
    public static function logProcessMainPayment($booking, $paymentData, Request $request = null)
{
    self::log(
        StaffActivityLog::ACTION_PROCESS_MAIN_PAYMENT,
        "Processed main payment for booking #{$booking->booking_ref_no}",
        $booking->id,
        [
            'booking_ref_no' => $booking->booking_ref_no,
            'customer_id' => $booking->customer_account_id,
            'customer_name' => "{$booking->customerAccount->first_name} {$booking->customerAccount->last_name}",
            'payment_method' => $paymentData['method'] ?? 'unknown',
            'total_amount' => $paymentData['total_amount'] ?? 0,
            'amount_paid' => $paymentData['amount_paid'] ?? 0,
            'change' => $paymentData['change'] ?? 0,
            'payment_status' => $paymentData['status'] ?? 'unknown',
            'unpaid_orders_count' => $paymentData['unpaid_orders_count'] ?? 0,
            'unpaid_orders_amount' => $paymentData['unpaid_orders_amount'] ?? 0,
            'grand_total' => $paymentData['grand_total'] ?? 0
        ],
        $request
    );
}

public static function logProcessExtensionPayment($booking, $paymentData, Request $request = null)
{
    self::log(
        StaffActivityLog::ACTION_PROCESS_EXTENSION_PAYMENT,
        "Processed extension payment for booking #{$booking->booking_ref_no}",
        $booking->id,
        [
            'booking_ref_no' => $booking->booking_ref_no,
            'customer_id' => $booking->customer_account_id,
            'customer_name' => "{$booking->customerAccount->first_name} {$booking->customerAccount->last_name}",
            'payment_method' => $paymentData['method'] ?? 'unknown',
            'extended_time_minutes' => $paymentData['extended_time'] ?? 0,
            'extended_time_formatted' => $paymentData['extended_time_formatted'] ?? '0 mins',
            'extension_amount' => $paymentData['extension_amount'] ?? 0,
            'total_amount' => $paymentData['total_amount'] ?? 0,
            'amount_paid' => $paymentData['amount_paid'] ?? 0,
            'change' => $paymentData['change'] ?? 0,
            'payment_status' => $paymentData['status'] ?? 'unknown',
            'unpaid_orders_count' => $paymentData['unpaid_orders_count'] ?? 0,
            'unpaid_orders_amount' => $paymentData['unpaid_orders_amount'] ?? 0,
            'grand_total' => $paymentData['grand_total'] ?? 0
        ],
        $request
    );
}

public static function logProcessOrderPayment($order, $paymentData, Request $request = null)
{
    self::log(
        StaffActivityLog::ACTION_PROCESS_ORDER_PAYMENT,
        "Processed order payment for order #{$order->order_reference}",
        null, // No booking ID for standalone orders
        [
            'order_id' => $order->id,
            'order_reference' => $order->order_reference ?? 'N/A',
            'customer_id' => $order->customer_account_id,
            'customer_name' => $order->customer ? "{$order->customer->first_name} {$order->customer->last_name}" : 'N/A',
            'payment_method' => $paymentData['method'] ?? 'unknown',
            'total_amount' => $paymentData['total_amount'] ?? 0,
            'amount_paid' => $paymentData['amount_paid'] ?? 0,
            'change' => $paymentData['change'] ?? 0,
            'payment_status' => $paymentData['status'] ?? 'unknown',
            'previous_status' => $paymentData['previous_status'] ?? 'unknown'
        ],
        $request
    );
}

public static function logCreateBooking($booking, $customer, $paymentData, Request $request = null)
{
    self::log(
        StaffActivityLog::ACTION_CREATE_BOOKING,
        "Created new booking #{$booking->booking_ref_no} for {$customer->first_name} {$customer->last_name}",
        $booking->id,
        [
            'booking_ref_no' => $booking->booking_ref_no,
            'customer_id' => $customer->id,
            'customer_name' => "{$customer->first_name} {$customer->last_name}",
            'customer_email' => $customer->email,
            'booking_type' => $booking->booking_type == 0 ? 'walk-in' : 'online',
            'service_name' => $paymentData['service_name'] ?? 'N/A',
            'service_price' => $paymentData['service_price'] ?? 0,
            'room_seat' => $paymentData['room_seat'] ?? 'N/A',
            'payment_method' => $paymentData['payment_method'] ?? 'unknown',
            'total_amount' => $paymentData['total_amount'] ?? 0,
            'payment_status' => $paymentData['payment_status'] ?? 'unknown',
            'is_new_customer' => $paymentData['is_new_customer'] ?? false,
            'notes' => $paymentData['notes'] ?? null
        ],
        $request
    );
}

public static function logCheckinCustomer($booking, $customer, $checkinData, Request $request = null)
{
    self::log(
        StaffActivityLog::ACTION_CHECKIN_CUSTOMER,
        "Checked in customer {$customer->first_name} {$customer->last_name} for booking #{$booking->booking_ref_no}",
        $booking->id,
        [
            'booking_ref_no' => $booking->booking_ref_no,
            'customer_id' => $customer->id,
            'customer_name' => "{$customer->first_name} {$customer->last_name}",
            'checkin_time' => $checkinData['checkin_time'] ?? now(),
            'service_name' => $checkinData['service_name'] ?? 'N/A',
            'room_seat' => $checkinData['room_seat'] ?? 'N/A',
            'branch_name' => $checkinData['branch_name'] ?? 'N/A'
        ],
        $request
    );
}

public static function logUpdateBranchStatus($branch, $oldStatus, $newStatus, Request $request = null)
{
    self::log(
        StaffActivityLog::ACTION_UPDATE_BRANCH_STATUS,
        "Updated branch status for {$branch->branch_name} from {$oldStatus} to {$newStatus}",
        null, // No booking ID for branch actions
        [
            'branch_id' => $branch->id,
            'branch_name' => $branch->branch_name,
            'branch_uuid' => $branch->uuid,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'location' => $branch->location,
            'owner_id' => $branch->owner_account_id
        ],
        $request
    );
}

public static function logExtendTime($booking, $customer, $extensionData, Request $request = null)
{
    self::log(
        StaffActivityLog::ACTION_EXTEND_TIME,
        "Extended time for booking #{$booking->booking_ref_no} for {$customer->first_name} {$customer->last_name}",
        $booking->id,
        [
            'booking_ref_no' => $booking->booking_ref_no,
            'customer_id' => $customer->id,
            'customer_name' => "{$customer->first_name} {$customer->last_name}",
            'booking_type' => $booking->booking_type == 0 ? 'walk-in' : 'online',
            'original_end_time' => $extensionData['original_end_time'] ?? 'N/A',
            'new_end_time' => $extensionData['new_end_time'] ?? 'N/A',
            'additional_duration_minutes' => $extensionData['additional_duration'] ?? 0,
            'additional_duration_formatted' => $extensionData['additional_duration_formatted'] ?? '0 mins',
            'total_extended_minutes' => $extensionData['total_extended_minutes'] ?? 0,
            'is_first_extension' => $extensionData['is_first_extension'] ?? false,
            'new_date_end' => $extensionData['new_date_end'] ?? 'N/A',
            'branch_name' => $extensionData['branch_name'] ?? 'N/A'
        ],
        $request
    );
}

public static function logProcessRewards($results, Request $request = null)
{
    self::log(
        StaffActivityLog::ACTION_PROCESS_REWARDS,
        "Processed customer rewards - {$results['new_rewards_created']} new rewards created",
        null, // No booking ID for reward processing
        [
            'new_rewards_created' => $results['new_rewards_created'] ?? 0,
            'total_customers_processed' => $results['total_customers_processed'] ?? 0,
            'total_reward_tiers' => $results['total_reward_tiers'] ?? 0,
            'processing_time' => $results['processing_time'] ?? 'N/A'
        ],
        $request
    );
}

public static function logUpdateRewardStatus($reward, $action, $reason = null, Request $request = null)
{
    self::log(
        StaffActivityLog::ACTION_UPDATE_REWARD_STATUS,
        "Updated reward #{$reward->id} status to: {$action}",
        null, // No booking ID for reward actions
        [
            'reward_id' => $reward->id,
            'customer_id' => $reward->customer_account_id,
            'customer_name' => $reward->customer ? "{$reward->customer->first_name} {$reward->customer->last_name}" : 'N/A',
            'reward_tier_id' => $reward->reward_tier_id,
            'reward_description' => $reward->rewardTier ? $reward->rewardTier->reward_description : 'N/A',
            'old_status' => $reward->getOriginal('claim_status'),
            'new_status' => $reward->claim_status,
            'action' => $action,
            'decline_reason' => $reason,
            'updated_by' => 'staff'
        ],
        $request
    );
}

public static function logCreateIngredient($ingredient, Request $request = null)
{
    self::log(
        StaffActivityLog::ACTION_CREATE_INGREDIENT,
        "Created new ingredient: {$ingredient->ingredient_name}",
        null, // No booking ID for ingredient actions
        [
            'ingredient_id' => $ingredient->id,
            'ingredient_uuid' => $ingredient->uuid,
            'ingredient_name' => $ingredient->ingredient_name,
            'ingredient_type' => $ingredient->ingredient_type,
            'ingredient_batch_no' => $ingredient->ingredient_batch_no,
            'stock_quantity_in' => $ingredient->stock_quantity_in,
            'stock_quantity_threshold' => $ingredient->stock_quantity_threshold,
            'unit' => $ingredient->unit,
            'branch_name' => $ingredient->branch ? $ingredient->branch->branch_name : 'N/A',
            'date_expiration' => $ingredient->date_expiration
        ],
        $request
    );
}

public static function logUpdateIngredient($ingredient, $oldData, Request $request = null)
{
    self::log(
        StaffActivityLog::ACTION_UPDATE_INGREDIENT,
        "Updated ingredient: {$ingredient->ingredient_name}",
        null, // No booking ID for ingredient actions
        [
            'ingredient_id' => $ingredient->id,
            'ingredient_uuid' => $ingredient->uuid,
            'ingredient_name' => $ingredient->ingredient_name,
            'changes' => $this->getIngredientChanges($ingredient, $oldData),
            'branch_name' => $ingredient->branch ? $ingredient->branch->branch_name : 'N/A'
        ],
        $request
    );
}

public static function logUpdateIngredientStatus($ingredient, $oldStatus, $newStatus, Request $request = null)
{
    self::log(
        StaffActivityLog::ACTION_UPDATE_INGREDIENT_STATUS,
        "Updated ingredient status for {$ingredient->ingredient_name} from {$oldStatus} to {$newStatus}",
        null, // No booking ID for ingredient actions
        [
            'ingredient_id' => $ingredient->id,
            'ingredient_uuid' => $ingredient->uuid,
            'ingredient_name' => $ingredient->ingredient_name,
            'old_status' => $oldStatus == 1 ? 'Available' : 'Unavailable',
            'new_status' => $newStatus == 1 ? 'Available' : 'Unavailable',
            'branch_name' => $ingredient->branch ? $ingredient->branch->branch_name : 'N/A'
        ],
        $request
    );
}

public static function logDeactivateIngredient($ingredient, Request $request = null)
{
    self::log(
        StaffActivityLog::ACTION_DEACTIVATE_INGREDIENT,
        "Deactivated ingredient: {$ingredient->ingredient_name}",
        null, // No booking ID for ingredient actions
        [
            'ingredient_id' => $ingredient->id,
            'ingredient_uuid' => $ingredient->uuid,
            'ingredient_name' => $ingredient->ingredient_name,
            'ingredient_type' => $ingredient->ingredient_type,
            'remaining_stock' => $ingredient->stock_quantity_in,
            'branch_name' => $ingredient->branch ? $ingredient->branch->branch_name : 'N/A'
        ],
        $request
    );
}

public static function logReactivateIngredient($ingredient, Request $request = null)
{
    self::log(
        StaffActivityLog::ACTION_REACTIVATE_INGREDIENT,
        "Reactivated ingredient: {$ingredient->ingredient_name}",
        null, // No booking ID for ingredient actions
        [
            'ingredient_id' => $ingredient->id,
            'ingredient_uuid' => $ingredient->uuid,
            'ingredient_name' => $ingredient->ingredient_name,
            'ingredient_type' => $ingredient->ingredient_type,
            'stock_quantity_in' => $ingredient->stock_quantity_in,
            'date_expiration' => $ingredient->date_expiration,
            'branch_name' => $ingredient->branch ? $ingredient->branch->branch_name : 'N/A'
        ],
        $request
    );
}

public static function logDamageIngredient($ingredient, $quantity, $reason, Request $request = null)
{
    self::log(
        StaffActivityLog::ACTION_DAMAGE_INGREDIENT,
        "Recorded damaged ingredient: {$ingredient->ingredient_name} - {$quantity} {$ingredient->unit}",
        null, // No booking ID for ingredient actions
        [
            'ingredient_id' => $ingredient->id,
            'ingredient_uuid' => $ingredient->uuid,
            'ingredient_name' => $ingredient->ingredient_name,
            'damaged_quantity' => $quantity,
            'unit' => $ingredient->unit,
            'remaining_stock' => $ingredient->stock_quantity_in,
            'reason' => $reason,
            'branch_name' => $ingredient->branch ? $ingredient->branch->branch_name : 'N/A'
        ],
        $request
    );
}

// Helper method to get ingredient changes
private static function getIngredientChanges($ingredient, $oldData)
{
    $changes = [];
    
    foreach ($oldData as $key => $oldValue) {
        if (isset($ingredient->$key) && $ingredient->$key != $oldValue) {
            $changes[$key] = [
                'old' => $oldValue,
                'new' => $ingredient->$key
            ];
        }
    }
    
    return $changes;
}

public static function logProcessPOSOrder($order, $customer, $paymentData, Request $request = null)
{
    $customerName = $customer ? "{$customer->first_name} {$customer->last_name}" : 'Walk-in Customer';
    
    self::log(
        StaffActivityLog::ACTION_PROCESS_POS_ORDER,
        "Processed POS order #{$order->order_ref_no} for {$customerName}",
        $order->booking_id, // Link to booking if exists
        [
            'order_id' => $order->id,
            'order_ref_no' => $order->order_ref_no,
            'customer_id' => $customer ? $customer->id : null,
            'customer_name' => $customerName,
            'customer_email' => $customer ? $customer->email : 'N/A',
            'booking_id' => $order->booking_id,
            'booking_ref_no' => $paymentData['booking_ref_no'] ?? null,
            'items_count' => $paymentData['items_count'] ?? 0,
            'subtotal' => $paymentData['subtotal'] ?? 0,
            'discount' => $paymentData['discount'] ?? 0,
            'total_amount' => $paymentData['total_amount'] ?? 0,
            'payment_method' => $paymentData['payment_method'] ?? 'unknown',
            'payment_status' => $paymentData['payment_status'] ?? 'unknown',
            'vat_amount' => $paymentData['vat_amount'] ?? 0,
            'vat_sales' => $paymentData['vat_sales'] ?? 0,
            'is_walk_in' => $paymentData['is_walk_in'] ?? false,
            'is_pay_later' => $paymentData['is_pay_later'] ?? false,
            'branch_name' => $paymentData['branch_name'] ?? 'N/A'
        ],
        $request
    );
}

public static function logUpdateStock($product, $action, $quantity, $reason = null, Request $request = null)
{
    self::log(
        StaffActivityLog::ACTION_UPDATE_STOCK,
        "{$action} stock for product: {$product->product_name}",
        null, // No booking ID for stock updates
        [
            'product_id' => $product->id,
            'product_uuid' => $product->uuid,
            'product_name' => $product->product_name,
            'action' => $action,
            'quantity_change' => $quantity,
            'new_quantity' => $product->quantity_in,
            'converted_quantity' => $product->converted_quantity_in,
            'reason' => $reason,
            'branch_name' => $product->branch ? $product->branch->branch_name : 'N/A'
        ],
        $request
    );
}

public static function logCreateProduct($product, Request $request = null)
{
    self::log(
        StaffActivityLog::ACTION_CREATE_PRODUCT,
        "Created new product: {$product->product_name}",
        null, // No booking ID for product actions
        [
            'product_id' => $product->id,
            'product_uuid' => $product->uuid,
            'product_name' => $product->product_name,
            'product_type' => $product->product_type,
            'product_batch_no' => $product->product_batch_no,
            'quantity_in' => $product->quantity_in,
            'quantity_threshold' => $product->quantity_threshold,
            'selling_price' => $product->selling_price,
            'unit' => $product->unit,
            'is_converted' => $product->unit_conversion ? true : false,
            'unit_conversion' => $product->unit_conversion,
            'converted_quantity_in' => $product->converted_quantity_in,
            'converted_unit' => $product->converted_unit,
            'date_expiration' => $product->date_expiration,
            'branch_name' => $product->branch ? $product->branch->branch_name : 'N/A'
        ],
        $request
    );
}

public static function logUpdateProduct($product, $oldData, Request $request = null)
{
    self::log(
        StaffActivityLog::ACTION_UPDATE_PRODUCT,
        "Updated product: {$product->product_name}",
        null, // No booking ID for product actions
        [
            'product_id' => $product->id,
            'product_uuid' => $product->uuid,
            'product_name' => $product->product_name,
            'changes' => self::getProductChanges($product, $oldData),
            'branch_name' => $product->branch ? $product->branch->branch_name : 'N/A'
        ],
        $request
    );
}

public static function logUpdateProductStatus($product, $oldStatus, $newStatus, Request $request = null)
{
    self::log(
        StaffActivityLog::ACTION_UPDATE_PRODUCT_STATUS,
        "Updated product status for {$product->product_name} from {$oldStatus} to {$newStatus}",
        null, // No booking ID for product actions
        [
            'product_id' => $product->id,
            'product_uuid' => $product->uuid,
            'product_name' => $product->product_name,
            'old_status' => $oldStatus == 1 ? 'Available' : 'Unavailable',
            'new_status' => $newStatus == 1 ? 'Available' : 'Unavailable',
            'branch_name' => $product->branch ? $product->branch->branch_name : 'N/A'
        ],
        $request
    );
}

public static function logDeactivateProduct($product, Request $request = null)
{
    self::log(
        StaffActivityLog::ACTION_DEACTIVATE_PRODUCT,
        "Deactivated product: {$product->product_name}",
        null, // No booking ID for product actions
        [
            'product_id' => $product->id,
            'product_uuid' => $product->uuid,
            'product_name' => $product->product_name,
            'product_type' => $product->product_type,
            'remaining_stock' => $product->quantity_in,
            'converted_stock' => $product->converted_quantity_in,
            'branch_name' => $product->branch ? $product->branch->branch_name : 'N/A'
        ],
        $request
    );
}

public static function logReactivateProduct($product, Request $request = null)
{
    self::log(
        StaffActivityLog::ACTION_REACTIVATE_PRODUCT,
        "Reactivated product: {$product->product_name}",
        null, // No booking ID for product actions
        [
            'product_id' => $product->id,
            'product_uuid' => $product->uuid,
            'product_name' => $product->product_name,
            'product_type' => $product->product_type,
            'quantity_in' => $product->quantity_in,
            'converted_quantity_in' => $product->converted_quantity_in,
            'date_expiration' => $product->date_expiration,
            'branch_name' => $product->branch ? $product->branch->branch_name : 'N/A'
        ],
        $request
    );
}

public static function logDamageProduct($product, $quantity, $reason, Request $request = null)
{
    self::log(
        StaffActivityLog::ACTION_DAMAGE_PRODUCT,
        "Recorded damaged product: {$product->product_name} - {$quantity} {$product->unit}",
        null, // No booking ID for product actions
        [
            'product_id' => $product->id,
            'product_uuid' => $product->uuid,
            'product_name' => $product->product_name,
            'damaged_quantity' => $quantity,
            'unit' => $product->unit,
            'remaining_stock' => $product->quantity_in,
            'remaining_converted_stock' => $product->converted_quantity_in,
            'reason' => $reason,
            'branch_name' => $product->branch ? $product->branch->branch_name : 'N/A'
        ],
        $request
    );
}

// Helper method to get product changes
private static function getProductChanges($product, $oldData)
{
    $changes = [];
    
    foreach ($oldData as $key => $oldValue) {
        if (isset($product->$key) && $product->$key != $oldValue) {
            $changes[$key] = [
                'old' => $oldValue,
                'new' => $product->$key
            ];
        }
    }
    
    return $changes;
}

public static function logAddProductIngredient($productIngredient, $product, $ingredient, Request $request = null)
{
    self::log(
        StaffActivityLog::ACTION_ADD_PRODUCT_INGREDIENT,
        "Added ingredient {$ingredient->ingredient_name} to product {$product->product_name}",
        null, // No booking ID for product ingredient actions
        [
            'product_ingredient_id' => $productIngredient->id,
            'product_id' => $product->id,
            'product_name' => $product->product_name,
            'ingredient_id' => $ingredient->id,
            'ingredient_name' => $ingredient->ingredient_name,
            'ingredient_type' => $ingredient->ingredient_type,
            'quantity_needed' => $productIngredient->quantity_needed,
            'quantity_in_base_unit' => $productIngredient->quantity_in_base_unit,
            'unit' => $productIngredient->unit,
            'base_unit' => $productIngredient->base_unit,
            'branch_name' => $productIngredient->branch ? $productIngredient->branch->branch_name : 'N/A'
        ],
        $request
    );
}

public static function logUpdateProductIngredient($productIngredient, $product, $ingredient, $oldData, Request $request = null)
{
    self::log(
        StaffActivityLog::ACTION_UPDATE_PRODUCT_INGREDIENT,
        "Updated ingredient {$ingredient->ingredient_name} for product {$product->product_name}",
        null, // No booking ID for product ingredient actions
        [
            'product_ingredient_id' => $productIngredient->id,
            'product_id' => $product->id,
            'product_name' => $product->product_name,
            'ingredient_id' => $ingredient->id,
            'ingredient_name' => $ingredient->ingredient_name,
            'changes' => self::getProductIngredientChanges($productIngredient, $oldData),
            'branch_name' => $productIngredient->branch ? $productIngredient->branch->branch_name : 'N/A'
        ],
        $request
    );
}

public static function logCreateRewardTier($rewardTier, Request $request = null)
{
    $branchName = $rewardTier->branch ? $rewardTier->branch->branch_name : 'Global (All Branches)';
    $bookingTypeText = $rewardTier->booking_type == 0 ? 'Streak' : 'Frequent';
    
    self::log(
        StaffActivityLog::ACTION_CREATE_REWARD_TIER,
        "Created reward tier: {$rewardTier->reward_description}",
        null, // No booking ID for reward tier actions
        [
            'reward_tier_id' => $rewardTier->id,
            'reward_description' => $rewardTier->reward_description,
            'booking_type' => $bookingTypeText,
            'booking_required' => $rewardTier->booking_required,
            'branch_name' => $branchName,
            'date_start' => $rewardTier->date_start,
            'date_end' => $rewardTier->date_end,
            'start_time' => $rewardTier->start_time,
            'end_time' => $rewardTier->end_time,
            'reward_tier_status' => $rewardTier->reward_tier_status == 1 ? 'Active' : 'Inactive'
        ],
        $request
    );
}

public static function logUpdateRewardTier($rewardTier, $oldData, Request $request = null)
{
    $branchName = $rewardTier->branch ? $rewardTier->branch->branch_name : 'Global (All Branches)';
    
    self::log(
        StaffActivityLog::ACTION_UPDATE_REWARD_TIER,
        "Updated reward tier: {$rewardTier->reward_description}",
        null, // No booking ID for reward tier actions
        [
            'reward_tier_id' => $rewardTier->id,
            'reward_description' => $rewardTier->reward_description,
            'changes' => self::getRewardTierChanges($rewardTier, $oldData),
            'branch_name' => $branchName,
            'booking_type' => $rewardTier->booking_type == 0 ? 'Streak' : 'Frequent',
            'booking_required' => $rewardTier->booking_required,
            'current_status' => $rewardTier->reward_tier_status == 1 ? 'Active' : 'Inactive'
        ],
        $request
    );
}

public static function logUpdateRewardTierStatus($rewardTier, $oldStatus, $newStatus, Request $request = null)
{
    $branchName = $rewardTier->branch ? $rewardTier->branch->branch_name : 'Global (All Branches)';
    
    self::log(
        StaffActivityLog::ACTION_UPDATE_REWARD_TIER_STATUS,
        "Updated reward tier status for: {$rewardTier->reward_description}",
        null, // No booking ID for reward tier actions
        [
            'reward_tier_id' => $rewardTier->id,
            'reward_description' => $rewardTier->reward_description,
            'old_status' => $oldStatus == 1 ? 'Active' : 'Inactive',
            'new_status' => $newStatus == 1 ? 'Active' : 'Inactive',
            'branch_name' => $branchName,
            'booking_type' => $rewardTier->booking_type == 0 ? 'Streak' : 'Frequent',
            'booking_required' => $rewardTier->booking_required
        ],
        $request
    );
}

// Helper method to get product ingredient changes
private static function getProductIngredientChanges($productIngredient, $oldData)
{
    $changes = [];
    
    foreach ($oldData as $key => $oldValue) {
        if (isset($productIngredient->$key) && $productIngredient->$key != $oldValue) {
            $changes[$key] = [
                'old' => $oldValue,
                'new' => $productIngredient->$key
            ];
        }
    }
    
    return $changes;
}

// Helper method to get reward tier changes
private static function getRewardTierChanges($rewardTier, $oldData)
{
    $changes = [];
    
    foreach ($oldData as $key => $oldValue) {
        if (isset($rewardTier->$key) && $rewardTier->$key != $oldValue) {
            // Format special fields for better readability
            if ($key === 'booking_type') {
                $changes[$key] = [
                    'old' => $oldValue == 0 ? 'Streak' : 'Frequent',
                    'new' => $rewardTier->$key == 0 ? 'Streak' : 'Frequent'
                ];
            } elseif ($key === 'reward_tier_status') {
                $changes[$key] = [
                    'old' => $oldValue == 1 ? 'Active' : 'Inactive',
                    'new' => $rewardTier->$key == 1 ? 'Active' : 'Inactive'
                ];
            } else {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $rewardTier->$key
                ];
            }
        }
    }
    
    return $changes;
}
}