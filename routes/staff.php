<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Staff\SeatController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Staff\BranchController;
use App\Http\Controllers\Staff\BookNowController;
use App\Http\Controllers\Staff\InventoryController;
use App\Http\Controllers\Staff\ProductController;
use App\Http\Controllers\Staff\IngredientController;
use App\Http\Controllers\Staff\RewardTierController;
use App\Http\Controllers\Staff\BookingListController;
use App\Http\Controllers\Staff\PointOfSaleController;
use App\Http\Controllers\Staff\ServiceNameController;
use App\Http\Controllers\Staff\StaffReportController;
use App\Http\Controllers\Staff\LoyaltyTierController;
use App\Http\Controllers\Staff\RedeemableItemController;
use App\Http\Controllers\Staff\CustomerRewardController;
use App\Http\Controllers\Staff\BookingCalendarController;
use App\Http\Controllers\Staff\SpaceAvailabilityController;
use App\Http\Controllers\Staff\CustomerCheckinController;
use App\Http\Controllers\Staff\ServiceCategoryController;
use App\Http\Controllers\Staff\CustomerFeedbacksController;
use App\Http\Controllers\Staff\ProductIngredientController;
use App\Http\Controllers\Staff\ScanQrCodeBookingController;
use App\Http\Controllers\Staff\BookingListPaymentController;
use App\Http\Controllers\Staff\StaffShiftScheduleController;

Route::middleware(['auth:staff', 'prevent_back_history'])->prefix('sub_two')->name('sub_two.')->group(function () {
    // My Account
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/my-account', [AccountController::class, 'showUserAccount'])->name('user_accounts');
        Route::put('/my-account/update-profile', [AccountController::class, 'updateProfileDetails'])->name('updateProfileDetails');
        Route::put('/my-account/update-password', [AccountController::class, 'updatePassword'])->name('updatePassword');
    });

    // Notification
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('markAsRead');
        Route::post('/{id}/mark-unread', [NotificationController::class, 'markAsUnread'])->name('markAsUnread');

        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('markAllAsRead');
        Route::post('/mark-all-unread', [NotificationController::class, 'markAllAsUnread'])->name('markAllAsUnread');

        Route::get('/counts', [NotificationController::class, 'getCounts'])->name('counts');
    });

    // Branch Route
    Route::prefix('branches')->name('branches.')->group(function () {
        Route::get('/', [BranchController::class, 'showBranch'])->name('showBranch');

        Route::patch('/status/{branch:uuid}', [BranchController::class, 'updateBranchStatus'])->name('updateBranchStatus');
    });

    // Feedback n Reviews
    Route::prefix('feedback')->name('feedback.')->group(function () {
        Route::get('/feedback_n_reviews', [CustomerFeedbacksController::class, 'index'])
            ->name('index');
    });

    // Service Category Route
    Route::prefix('service_categories')->name('service_categories.')->group(function () {
        Route::get('/{branch:uuid}', [ServiceCategoryController::class, 'showServiceCategory'])->name('showServiceCategory');

        Route::patch('/status/{service_category:uuid}', [ServiceCategoryController::class, 'updateServiceCategoryStatus'])->name('updateServiceCategoryStatus');
    });

    // Service Name Route
    Route::prefix('service_names')->name('service_names.')->group(function () {
        Route::get('/{branch:uuid}/{service_category:uuid}', [ServiceNameController::class, 'showServiceName'])->name('showServiceName');

        Route::patch('/status/{service_name:uuid}', [ServiceNameController::class, 'updateServiceNameStatus'])->name('updateServiceNameStatus');
    });

    // Seat Route
    Route::prefix('seats')->name('seats.')->group(function () {
        Route::get('/{branch:uuid}/{service_category:uuid}/{service_name:uuid}', [SeatController::class, 'showSeat'])->name('showSeat');

        Route::patch('/status/{seat:uuid}', [SeatController::class, 'updateSeatStatus'])->name('updateSeatStatus');
    });

    // Booking Calendar
    Route::prefix('booking_calendar')->name('booking_calendar.')->group(function () {
        // Booking List Routes
        Route::get('/', [BookingCalendarController::class, 'showBookingCalendar'])->name('showBookingCalendar');
        
        Route::get('/space-availability', [SpaceAvailabilityController::class, 'showSpaceAvailability'])->name('space_availability');
    });

    // Book Now
    Route::prefix('book_now')->name('book_now.')->group(function () {
        Route::get('/', [BookNowController::class, 'create'])->name('create');
        Route::post('/book-now', [BookNowController::class, 'store'])->name('store');
        Route::post('/book-now/get-time-slots', [BookNowController::class, 'getAvailableTimeSlots'])->name('get_time_slots');
        Route::post('/book-now/get-service-price', [BookNowController::class, 'getServicePrice'])->name('get_service_price');
        Route::post('/book-now/get-available-seats', [BookNowController::class, 'getAvailableSeats'])->name('get_available_seats');
        Route::post('/book_now/get_latest_booking_end', [BookNowController::class, 'getLatestBookingEnd'])->name('get_latest_booking_end');
        Route::get('/book-now/get-returning-customers', [BookNowController::class, 'getReturningCustomers'])->name('get_returning_customers');
        Route::post('/book-now/save-customer', [BookNowController::class, 'saveCustomer'])->name('save_customer');
        
        // Reward routes
Route::post('/book-now/get-customer-rewards', [BookNowController::class, 'getCustomerRewards'])->name('get_customer_rewards');
Route::post('/book-now/apply-reward', [BookNowController::class, 'applyReward'])->name('apply_reward');
    });

    // Scan Booking Qr Code
    Route::prefix('scan_qr_code_bookings')->name('scan_qr_code_bookings.')->group(function () {
        Route::get('/scanner', [ScanQrCodeBookingController::class, 'showQrCodeBookingScanner'])->name('showQrCodeBookingScanner');
        Route::get('/get-booking', [ScanQrCodeBookingController::class, 'getBookingByBookingRefNo'])->name('getBookingByBookingRefNo');
        Route::post('/store-checkin', [ScanQrCodeBookingController::class, 'storeCheckin'])->name('storeCheckin');
        Route::post('/checkout', [ScanQrCodeBookingController::class, 'checkout'])->name('checkout');
        Route::get('/redirect-to-pos', [ScanQrCodeBookingController::class, 'redirectToPos'])->name('redirectToPos');
    });

    // Customer Checkins
    Route::prefix('customer_checkins')->name('customer_checkins.')->group(function () {
        // Customer Checkins Routes
        Route::get('/', [CustomerCheckinController::class, 'index'])->name('index');
        Route::get('/get-end-times/{id}', [CustomerCheckinController::class, 'getEndTimes'])->name('get-end-times');

        Route::patch('/update-status/{id}', [CustomerCheckinController::class, 'updateCheckinStatus'])->name('update-status');
        // Add this new route for check-in
        Route::post('/create_checkin/{booking_id}', [ScanQrCodeBookingController::class, 'createCheckin'])->name('create_checkin');

        Route::post('/bulk-checkout', [CustomerCheckinController::class, 'bulkCheckout'])->name('bulk-checkout');
        Route::get('/{id}', [CustomerCheckinController::class, 'show'])->name('show');
        Route::get('/stats', [CustomerCheckinController::class, 'getStats'])->name('stats');
        Route::post('/search', [CustomerCheckinController::class, 'search'])->name('search');

        Route::get('/get-checkin-details/{id}', [CustomerCheckinController::class, 'getCheckinDetails']);

        Route::get('/extend-time-modal/{id}', [CustomerCheckinController::class, 'showExtendTimeModal'])->name('extend_time_modal');
        Route::post('/extend-time/{id}', [CustomerCheckinController::class, 'extendTime'])->name('extend_time');
    });

    // Booking Lists
    Route::prefix('booking_lists')->name('booking_lists.')->group(function () {
        // Booking List Routes
        Route::get('/', [BookingListController::class, 'showBookingList'])->name('showBookingList');
        Route::post('/search', [BookingListController::class, 'search'])->name('search');
        Route::get('/filter-options', [BookingListController::class, 'getFilterOptions'])->name('filter_options');
        Route::get('/orders/{booking}', [BookingListController::class, 'getBookingOrders'])->name('orders');

        // Notes Routes
        Route::get('/notes/{booking}', [BookingListController::class, 'getNotes'])->name('getNotes');
        Route::post('/update-note', [BookingListController::class, 'updateNote'])->name('updateNote');

        // Payment Routes
        Route::post('/update-main-payment', [BookingListController::class, 'updateMainPayment'])->name('updateMainPayment');
        Route::post('/update-extension-payment', [BookingListController::class, 'updateExtensionPayment'])->name('updateExtensionPayment');
        Route::post('/update-payment-later', [BookingListController::class, 'updatePayLaterPayment'])->name('updatePayLaterPayment');

        // Other routes
        Route::post('/change-schedule', [BookingListController::class, 'changeSchedule'])->name('changeSchedule');
        Route::post('/get-available-time-slots', [BookingListController::class, 'getAvailableTimeSlots'])->name('getAvailableTimeSlots');
        Route::post('/confirm-booking', [BookingListController::class, 'confirmBooking'])->name('confirmBooking');
        Route::post('/mark-no-show', [BookingListController::class, 'markNoShow'])->name('markNoShow');
        Route::get('/details/{booking:uuid}', [BookingListController::class, 'showBookingDetails'])->name('details');

        // Main Payment Routes
        Route::get('/main-payment/{booking_uuid}', [BookingListPaymentController::class, 'showMainPaymentPage'])
            ->name('main_payment');
        Route::post('/update-main-payment', [BookingListPaymentController::class, 'updateMainPayment'])
            ->name('updateMainPayment');

        // Extension Payment Routes
        Route::get('/extension-payment/{booking_uuid}', [BookingListPaymentController::class, 'showExtensionPaymentPage'])
            ->name('extension_payment');
        Route::post('/update-extension-payment', [BookingListPaymentController::class, 'updateExtensionPayment'])
            ->name('updateExtensionPayment');
            
        // Order Payment Route
        Route::get('/order-payment/{booking_uuid}', [BookingListPaymentController::class, 'showOrderPaymentPage'])
            ->name('order_payment');
    
        Route::post('/order-payment', [BookingListPaymentController::class, 'updateOrderPayment'])
            ->name('updateOrderPayment');
    });
    
     // Inventory
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/{uuid}/details', [InventoryController::class, 'details'])->name('details');
        Route::post('/stock-in', [InventoryController::class, 'storeStockIn'])->name('stockIn');
        Route::post('/stock-out', [InventoryController::class, 'storeStockOut'])->name('stockOut');
        Route::get('/{uuid}/data', [InventoryController::class, 'getData'])->name('getData');
    });

    // Product Route
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'showProduct'])->name('showProduct');
        Route::post('/store', [ProductController::class, 'storeProduct'])->name('storeProduct');
        Route::patch('/update/{product:uuid}', [ProductController::class, 'updateProduct'])->name('updateProduct');
        Route::patch('/status/{product:uuid}', [ProductController::class, 'updateProductStatus'])->name('updateProductStatus');
        Route::get('/archive', [ProductController::class, 'showDeactivatedProduct'])->name('showDeactivatedProduct');
        Route::patch('/deactivate/{product:uuid}', [ProductController::class, 'deactivateProduct'])->name('deactivateProduct');
        Route::patch('/damage-archive/{product:uuid}', [ProductController::class, 'damageAndArchiveProduct'])->name('sub_one.products.damageArchive');
        Route::patch('/reactivate/{product:uuid}', [ProductController::class, 'reactivateProduct'])->name('reactivateProduct');

        // New AJAX routes for modal support
        Route::get('/{product_uuid}/data', [ProductController::class, 'getProductData'])->name('getProductData');
        Route::post('/ajax/store', [ProductController::class, 'storeProductAjax'])->name('storeProductAjax');
        Route::patch('/ajax/{product_uuid}/update', [ProductController::class, 'updateProductAjax'])->name('updateProductAjax');
    });

    // Ingredient Route
    Route::prefix('ingredients')->name('ingredients.')->group(function () {
        Route::get('/', [IngredientController::class, 'showIngredient'])->name('showIngredient');

        Route::get('/add', [IngredientController::class, 'showAddIngredientForm'])->name('showAddIngredientForm');
        Route::post('/store', [IngredientController::class, 'storeIngredient'])->name('storeIngredient');

        Route::get('/edit/{id}', [IngredientController::class, 'showEditIngredientForm'])->name('showEditIngredientForm');
        Route::patch('/update/{id}', [IngredientController::class, 'updateIngredient'])->name('updateIngredient');

        Route::patch('/status/{id}', [IngredientController::class, 'updateIngredientStatus'])->name('updateIngredientStatus');

        Route::get('/archive', [IngredientController::class, 'showDeactivatedIngredient'])->name('showDeactivatedIngredient');
        Route::patch('/deactivate/{id}', [IngredientController::class, 'deactivateIngredient'])->name('deactivateIngredient');
        Route::patch('/damage/{id}', [IngredientController::class, 'damageIngredient'])->name('damageIngredient');
        Route::patch('/reactivate/{id}', [IngredientController::class, 'reactivateIngredient'])->name('reactivateIngredient');

        // AJAX Routes for Modal Operations
        Route::post('/ajax', [IngredientController::class, 'storeIngredientAjax'])->name('storeIngredientAjax');
        Route::get('/{ingredient_uuid}/data', [IngredientController::class, 'getIngredientData'])->name('getIngredientData');
        Route::patch('/ajax/{ingredient_uuid}/update', [IngredientController::class, 'updateIngredientAjax'])->name('updateIngredientAjax');
    });

    // Product with Ingredient Route
    Route::prefix('product_ingredients')->name('product_ingredients.')->group(function () {
        Route::get('/{product_uuid}', [ProductIngredientController::class, 'showProductIngredient'])->name('showProductIngredient');

        // AJAX Routes
    Route::post('/ajax/store', [ProductIngredientController::class, 'storeProductIngredientAjax'])
        ->name('storeProductIngredientAjax');
    
    Route::get('/data/{product_uuid}/{product_ingredient_uuid}', [ProductIngredientController::class, 'getProductIngredientData'])
        ->name('getProductIngredientData');
    
    Route::patch('/ajax/{product_ingredient_uuid}/update', [ProductIngredientController::class, 'updateProductIngredientAjax'])
        ->name('updateProductIngredientAjax');
    
    Route::get('/{product_uuid}/ingredients', [ProductIngredientController::class, 'getIngredientsForProduct'])
        ->name('getIngredientsForProduct');
    });

    // POS System
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PointOfSaleController::class, 'index'])->name('index');
        Route::get('/customers', [PointOfSaleController::class, 'getCustomers'])->name('get-customers');
        Route::post('/customers', [PointOfSaleController::class, 'storeCustomer'])->name('store-customer');
        Route::get('/search-product', [PointOfSaleController::class, 'searchProduct'])->name('search-product');
        Route::post('/check-stock', [PointOfSaleController::class, 'checkStock'])->name('check-stock');
        Route::post('/process-order', [PointOfSaleController::class, 'processOrder'])->name('process-order');
        Route::get('/receipt/{order:uuid}', [PointOfSaleController::class, 'receipt'])->name('receipt');
        Route::get('/history', [PointOfSaleController::class, 'orderHistory'])->name('history');
        Route::post('/change-branch', [PointOfSaleController::class, 'changeBranch'])->name('change-branch');
        
        Route::get('/customer-rewards', [PointOfSaleController::class, 'getCustomerRewards'])->name('get-customer-rewards');
    Route::post('/apply-reward', [PointOfSaleController::class, 'applyReward'])->name('apply-reward');
    });

    // Reward Tiers
    Route::prefix('reward_tiers')->name('reward_tiers.')->group(function () {
        Route::get('/', [LoyaltyTierController::class, 'index'])->name('index');
        Route::post('/', [LoyaltyTierController::class, 'store'])->name('store');
        Route::patch('/{id}', [LoyaltyTierController::class, 'update'])->name('update');
        Route::patch('/{id}/status', [LoyaltyTierController::class, 'toggleStatus'])->name('updateStatus');
        Route::get('/redeemable-items', [LoyaltyTierController::class, 'getRedeemableItems'])->name('get-redeemable-items');
    });
    
    // Loyalty Tiers
    Route::prefix('loyalty-tiers')->name('loyalty_tiers.')->group(function () {
    Route::get('/', [LoyaltyTierController::class, 'index'])->name('index');
    Route::post('/', [LoyaltyTierController::class, 'store'])->name('store');
    Route::get('/{id}/data', [LoyaltyTierController::class, 'getTierData'])->name('get-data');
    Route::patch('/{id}', [LoyaltyTierController::class, 'update'])->name('update');
    Route::patch('/{id}/status', [LoyaltyTierController::class, 'toggleStatus'])->name('toggle-status');
    Route::delete('/{id}', [LoyaltyTierController::class, 'destroy'])->name('destroy');
    Route::get('/redeemable-items', [LoyaltyTierController::class, 'getRedeemableItems'])->name('get-redeemable-items');
});

// Redeemable Items
Route::prefix('redeemable-items')->name('redeemable_items.')->group(function () {
    Route::get('/', [RedeemableItemController::class, 'index'])->name('index');
    Route::post('/', [RedeemableItemController::class, 'store'])->name('store');
    Route::get('/{id}/data', [RedeemableItemController::class, 'getItemData'])->name('get-data');
    Route::patch('/{id}', [RedeemableItemController::class, 'update'])->name('update');
    Route::patch('/{id}/status', [RedeemableItemController::class, 'toggleStatus'])->name('toggle-status');
    Route::delete('/{id}', [RedeemableItemController::class, 'destroy'])->name('destroy');
    Route::get('/dropdown', [RedeemableItemController::class, 'getItemsForDropdown'])->name('dropdown');
    Route::get('/categories', [RedeemableItemController::class, 'getCategories'])->name('categories');
    Route::get('/by-branch', [RedeemableItemController::class, 'getItemsByBranch'])->name('by-branch');
    Route::get('/service-price/{id}', [RedeemableItemController::class, 'getServicePrice'])->name('service-price');
    Route::get('/product-price/{id}', [RedeemableItemController::class, 'getProductPrice'])->name('product-price');
    Route::post('/{id}/apply', [RedeemableItemController::class, 'applyReward'])->name('apply');
});

    // Customer Rewards
    Route::prefix('customer_rewards')->name('customer_rewards.')->group(function () {
        Route::get('/', [CustomerRewardController::class, 'index'])->name('index');
        Route::get('/{customerId}/progress', [CustomerRewardController::class, 'getCustomerProgress'])->name('progress');
        Route::post('/process', [CustomerRewardController::class, 'processRewards'])->name('process');
        Route::get('/reward/{rewardId}', [CustomerRewardController::class, 'getCustomerRewardDetails'])->name('details');
        Route::post('/{rewardId}/status', [CustomerRewardController::class, 'updateRewardStatus'])->name('status');
    });

    // Staff Shift Schedule Route
    Route::prefix('my_shift_schedules')->name('my_shift_schedules.')->group(function () {
        Route::get('/', [StaffShiftScheduleController::class, 'showMyShift'])->name('showMyShift');
        Route::post('/{scheduleId}/checkin', [StaffShiftScheduleController::class, 'checkin'])->name('shift-schedules.checkin');
        Route::post('/{checkinId}/checkout', [StaffShiftScheduleController::class, 'checkout'])->name('checkins.checkout');
    });

    // Staff Reports
    Route::prefix('reports')->name('reports.')->group(function () {
    // Main reports page (Sales tab)
    Route::get('/staff-report', [StaffReportController::class, 'index'])->name('my_report');
    
    // Inventory report page
    Route::get('/inventory-report', [StaffReportController::class, 'index'])->name('inventory_report');
    
    // Feedback report page
    Route::get('/feedback-report', [StaffReportController::class, 'index'])->name('feedback_report');
    
    // PDF Export Routes
    Route::get('/export-sales-pdf', [StaffReportController::class, 'exportSalesPdf'])->name('export_sales_pdf');
    Route::get('/export-inventory-pdf', [StaffReportController::class, 'exportInventoryPdf'])->name('export_inventory_pdf');
    Route::get('/export-feedback-pdf', [StaffReportController::class, 'exportFeedbackPdf'])->name('export_feedback_pdf');
});
});
