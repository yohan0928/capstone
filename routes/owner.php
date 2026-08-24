<?php

use App\Http\Controllers\Owner\AnalyticsController;
use App\Http\Controllers\Owner\BookingCalendarController;
use App\Http\Controllers\Owner\BookingListController;
use App\Http\Controllers\Owner\BookingListPaymentController;
use App\Http\Controllers\Owner\BookNowController;
use App\Http\Controllers\Owner\BranchController;
use App\Http\Controllers\Owner\BranchReportController;
use App\Http\Controllers\Owner\CustomerCheckinController;
use App\Http\Controllers\Owner\CustomerFeedbacksController;
use App\Http\Controllers\Owner\CustomerRewardController;
use App\Http\Controllers\Owner\DashboardController;
use App\Http\Controllers\Owner\IngredientController;
use App\Http\Controllers\Owner\InventoryController;
use App\Http\Controllers\Owner\PointOfSaleController;
use App\Http\Controllers\Owner\ProductController;
use App\Http\Controllers\Owner\ProductIngredientController;
use App\Http\Controllers\Owner\RedeemableItemController;
use App\Http\Controllers\Owner\LoyaltyTierController;
use App\Http\Controllers\Owner\ScanQrCodeBookingController;
use App\Http\Controllers\Owner\SeatController;
use App\Http\Controllers\Owner\ServiceCategoryController;
use App\Http\Controllers\Owner\ServiceNameController;
use App\Http\Controllers\Owner\StaffListController;
use App\Http\Controllers\Owner\StaffActivityLogController;
use App\Http\Controllers\Owner\StaffReportController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

Route::middleware(['auth:owner', 'prevent_back_history'])->prefix('sub_one')->name('sub_one.')->group(function () {
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
    
    Route::get('/test-staff-log', function() {
        $log = \App\Models\StaffActivityLog::first();
        if ($log) {
            return response()->json(['log' => $log]);
        }
        return response()->json(['message' => 'No logs found']);
    });
    
    // Staff Activity Logs
    Route::prefix('staff-activity-logs')->name('staff_activity_logs.')->group(function () {
        Route::get('/', [StaffActivityLogController::class, 'index'])->name('index');
        Route::get('/details/{log}', [StaffActivityLogController::class, 'getLogDetails'])->name('details');
        Route::get('/export', [StaffActivityLogController::class, 'export'])->name('export');
        Route::get('/staff-by-branch', [StaffActivityLogController::class, 'getStaffByBranch'])->name('staff_by_branch');
    });

    // Dashboard
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/', [DashboardController::class, 'showDashboard'])->name('showDashboard');
    });

    // Branches
    Route::prefix('branches')->name('branches.')->group(function () {
        Route::get('/', [BranchController::class, 'showBranch'])->name('showBranch');
        Route::post('/store', [BranchController::class, 'storeBranch'])->name('storeBranch');
        Route::patch('/update/{branch:uuid}', [BranchController::class, 'updateBranch'])->name('updateBranch');
        Route::patch('/status/{branch:uuid}', [BranchController::class, 'updateBranchStatus'])->name('updateBranchStatus');
        Route::get('/archive', [BranchController::class, 'showDeactivatedBranch'])->name('showDeactivatedBranch');
        Route::patch('/deactivate/{branch:uuid}', [BranchController::class, 'deactivateBranch'])->name('deactivateBranch');
        Route::patch('/reactivate/{branch:uuid}', [BranchController::class, 'reactivateBranch'])->name('reactivateBranch');

        // New routes for modal support
        Route::get('/{branch_uuid}/data', [BranchController::class, 'getBranchData'])->name('getBranchData');

        // Optional AJAX routes
        Route::post('/ajax/store', [BranchController::class, 'storeBranchAjax'])->name('storeBranchAjax');
        Route::patch('/ajax/{branch_uuid}/update', [BranchController::class, 'updateBranchAjax'])->name('updateBranchAjax');
        
        // Discount routes
        Route::get('/discount/data/{branch_uuid}', [BranchController::class, 'getDiscountData'])
            ->name('discount.data');
        Route::post('/discount/apply/{branch_uuid}', [BranchController::class, 'applyDiscount'])
            ->name('discount.apply');
        Route::post('/discount/remove/{branch_uuid}', [BranchController::class, 'removeDiscount'])
            ->name('discount.remove');
            
        // AJAX Geocode Address
        Route::post('/geocode', [BranchController::class, 'geocodeAddress'])
            ->name('geocode');
        
        // Batch Geocode All Branches
        Route::post('/batch-geocode', [BranchController::class, 'batchGeocode'])
            ->name('batch-geocode');
    });

    // Feedback n Reviews
    Route::prefix('feedback')->name('feedback.')->group(function () {
        Route::get('/feedback_n_reviews', [CustomerFeedbacksController::class, 'index'])
            ->name('index');
        Route::post('/ai-summary', [CustomerFeedbacksController::class, 'generateAISummary'])
            ->name('ai-summary');
        Route::post('/ai-summary-overall', [CustomerFeedbacksController::class, 'generateOverallSummary'])
            ->name('ai-summary-overall');
    });

    // Service Categories
    Route::prefix('service_categories')->name('service_categories.')->group(function () {
        Route::get('/{branch:uuid}', [ServiceCategoryController::class, 'showServiceCategory'])->name('showServiceCategory');
        Route::post('/store', [ServiceCategoryController::class, 'storeServiceCategory'])->name('storeServiceCategory');
        Route::patch('/update/{service_category:uuid}', [ServiceCategoryController::class, 'updateServiceCategory'])->name('updateServiceCategory');
        Route::patch('/status/{service_category:uuid}', [ServiceCategoryController::class, 'updateServiceCategoryStatus'])->name('updateServiceCategoryStatus');
        Route::get('/archive/{branch:uuid}', [ServiceCategoryController::class, 'showDeactivatedServiceCategory'])->name('showDeactivatedServiceCategory');
        Route::patch('/deactivate/{service_category:uuid}', [ServiceCategoryController::class, 'deactivateServiceCategory'])->name('deactivateServiceCategory');
        Route::patch('/reactivate/{service_category:uuid}', [ServiceCategoryController::class, 'reactivateServiceCategory'])->name('reactivateServiceCategory');

        // New AJAX routes for modal support
        Route::get('/{branch_uuid}/{service_category_uuid}/data', [ServiceCategoryController::class, 'getServiceCategoryData'])->name('getServiceCategoryData');
        Route::post('/ajax/store', [ServiceCategoryController::class, 'storeServiceCategoryAjax'])->name('storeServiceCategoryAjax');
        Route::patch('/ajax/{service_category_uuid}/update', [ServiceCategoryController::class, 'updateServiceCategoryAjax'])->name('updateServiceCategoryAjax');
    });

    // Service Names
    Route::prefix('service_names')->name('service_names.')->group(function () {
        Route::get('/{branch:uuid}/{service_category:uuid}', [ServiceNameController::class, 'showServiceName'])->name('showServiceName');
        Route::post('/store', [ServiceNameController::class, 'storeServiceName'])->name('storeServiceName');
        Route::patch('/update/{service_name:uuid}', [ServiceNameController::class, 'updateServiceName'])->name('updateServiceName');
        Route::patch('/status/{service_name:uuid}', [ServiceNameController::class, 'updateServiceNameStatus'])->name('updateServiceNameStatus');
        Route::get('/archive/{branch:uuid}/{service_category:uuid}', [ServiceNameController::class, 'showDeactivatedServiceName'])->name('showDeactivatedServiceName');
        Route::patch('/deactivate/{service_name:uuid}', [ServiceNameController::class, 'deactivateServiceName'])->name('deactivateServiceName');
        Route::patch('/reactivate/{service_name:uuid}', [ServiceNameController::class, 'reactivateServiceName'])->name('reactivateServiceName');

        // New AJAX routes for modal support
        Route::get('/{branch_uuid}/{service_category_uuid}/{service_name_uuid}/data', [ServiceNameController::class, 'getServiceNameData'])->name('getServiceNameData');
        Route::post('/ajax/store', [ServiceNameController::class, 'storeServiceNameAjax'])->name('storeServiceNameAjax');
        Route::patch('/ajax/{service_name_uuid}/update', [ServiceNameController::class, 'updateServiceNameAjax'])->name('updateServiceNameAjax');
        
        // Discount routes
        Route::get('discount/data/{branch_uuid}', [ServiceNameController::class, 'getBranchDiscountData'])->name('discount.data');
        Route::post('discount/apply/{branch_uuid}', [ServiceNameController::class, 'applyDiscount'])->name('discount.apply');
        Route::post('discount/remove/{branch_uuid}', [ServiceNameController::class, 'removeDiscount'])->name('discount.remove');
    });

    // Seats
    Route::prefix('seats')->name('seats.')->group(function () {
        Route::get('/{branch:uuid}/{service_category:uuid}/{service_name:uuid}', [SeatController::class, 'showSeat'])->name('showSeat');
        Route::post('/store', [SeatController::class, 'storeSeat'])->name('storeSeat');
        Route::patch('/update/{seat:uuid}', [SeatController::class, 'updateSeat'])->name('updateSeat');
        Route::patch('/status/{seat:uuid}', [SeatController::class, 'updateSeatStatus'])->name('updateSeatStatus');
        Route::get('/archive/{branch:uuid}/{service_category:uuid}/{service_name:uuid}', [SeatController::class, 'showDeactivatedSeat'])->name('showDeactivatedSeat');
        Route::patch('/deactivate/{seat:uuid}', [SeatController::class, 'deactivateSeat'])->name('deactivateSeat');
        Route::patch('/reactivate/{seat:uuid}', [SeatController::class, 'reactivateSeat'])->name('reactivateSeat');

        Route::get('/{branch_uuid}/{service_category_uuid}/{service_name_uuid}/{seat_uuid}/data', [SeatController::class, 'getSeatData'])->name('getSeatData');
        Route::post('/ajax/store', [SeatController::class, 'storeSeatAjax'])->name('storeSeatAjax');
        Route::patch('/ajax/{seat_uuid}/update', [SeatController::class, 'updateSeatAjax'])->name('updateSeatAjax');
    });

    // Booking Calendar
    Route::prefix('booking_calendar')->name('booking_calendar.')->group(function () {
        Route::get('/', [BookingCalendarController::class, 'showBookingCalendar'])->name('showBookingCalendar');
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

    // ================================================================
    // SCAN QR CODE BOOKING - COMPLETE ROUTES
    // ================================================================
    Route::prefix('scan_qr_code_bookings')->name('scan_qr_code_bookings.')->group(function () {
        // Main scanner page
        Route::get('/scanner', [ScanQrCodeBookingController::class, 'showQrCodeBookingScanner'])
            ->name('showQrCodeBookingScanner');
        
        // Get booking by reference number
        Route::get('/get-booking', [ScanQrCodeBookingController::class, 'getBookingByBookingRefNo'])
            ->name('getBookingByBookingRefNo');
        
        // Store check-in
        Route::post('/store-checkin', [ScanQrCodeBookingController::class, 'storeCheckin'])
            ->name('storeCheckin');
        
        // Check-out customer
        Route::post('/checkout', [ScanQrCodeBookingController::class, 'checkout'])
            ->name('checkout');
        
        // ===== NEW ROUTE: Redirect to POS =====
        Route::get('/redirect-to-pos', [ScanQrCodeBookingController::class, 'redirectToPos'])
            ->name('redirectToPos');
    });

    // Customer Checkins
    Route::prefix('customer_checkins')->name('customer_checkins.')->group(function () {
        Route::get('/', [CustomerCheckinController::class, 'index'])->name('index');
        Route::get('/get-end-times/{id}', [CustomerCheckinController::class, 'getEndTimes'])->name('get-end-times');

        Route::patch('/update-status/{id}', [CustomerCheckinController::class, 'updateCheckinStatus'])->name('update-status');
        Route::post('/create_checkin/{booking_id}', [ScanQrCodeBookingController::class, 'createCheckin'])->name('create_checkin');

        Route::post('/bulk-checkout', [CustomerCheckinController::class, 'bulkCheckout'])->name('bulk-checkout');

        Route::get('/extend-time-modal/{id}', [CustomerCheckinController::class, 'showExtendTimeModal'])->name('extend_time_modal');

        Route::get('/{id}', [CustomerCheckinController::class, 'show'])->name('show');
        Route::get('/stats', [CustomerCheckinController::class, 'getStats'])->name('stats');
        Route::post('/search', [CustomerCheckinController::class, 'search'])->name('search');

        Route::get('/get-checkin-details/{id}', [CustomerCheckinController::class, 'getCheckinDetails']);

        Route::post('/extend-time/{id}', [CustomerCheckinController::class, 'extendTime'])->name('extend_time');
    });

    // Booking Lists
    Route::prefix('booking_lists')->name('booking_lists.')->group(function () {
        Route::get('/', [BookingListController::class, 'showBookingList'])->name('showBookingList');
        Route::post('/search', [BookingListController::class, 'search'])->name('search');
        Route::get('/filter-options', [BookingListController::class, 'getFilterOptions'])->name('filter_options');
        Route::get('/orders/{booking}', [BookingListController::class, 'getBookingOrders'])->name('orders');
        Route::post('/update-main-payment', [BookingListController::class, 'updateMainPayment'])->name('updateMainPayment');
        Route::post('/update-extension-payment', [BookingListController::class, 'updateExtensionPayment'])->name('updateExtensionPayment');
        Route::post('/update-payment-later', [BookingListController::class, 'updatePayLaterPayment'])->name('updatePayLaterPayment');
        Route::post('/update-note', [BookingListController::class, 'updateNote'])->name('updateNote');
        Route::get('/notes/{booking}', [BookingListController::class, 'getNotes'])->name('getNotes');
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

    // Products
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'showProduct'])->name('showProduct');
        Route::post('/store', [ProductController::class, 'storeProduct'])->name('storeProduct');
        Route::patch('/update/{product:uuid}', [ProductController::class, 'updateProduct'])->name('updateProduct');
        Route::patch('/status/{product:uuid}', [ProductController::class, 'updateProductStatus'])->name('updateProductStatus');
        Route::get('/archive', [ProductController::class, 'showDeactivatedProduct'])->name('showDeactivatedProduct');
        Route::patch('/deactivate/{product:uuid}', [ProductController::class, 'deactivateProduct'])->name('deactivateProduct');
        Route::patch('/damage-archive/{product:uuid}', [ProductController::class, 'damageAndArchiveProduct'])->name('damageArchive');
        Route::patch('/reactivate/{product:uuid}', [ProductController::class, 'reactivateProduct'])->name('reactivateProduct');

        Route::get('/{product_uuid}/data', [ProductController::class, 'getProductData'])->name('getProductData');
        Route::post('/ajax/store', [ProductController::class, 'storeProductAjax'])->name('storeProductAjax');
        Route::patch('/ajax/{product_uuid}/update', [ProductController::class, 'updateProductAjax'])->name('updateProductAjax');
    });

    // Ingredients
    Route::prefix('ingredients')->name('ingredients.')->group(function () {
        Route::get('/', [IngredientController::class, 'showIngredient'])->name('showIngredient');
        Route::get('/add', [IngredientController::class, 'showAddIngredientForm'])->name('showAddIngredientForm');
        Route::post('/store', [IngredientController::class, 'storeIngredient'])->name('storeIngredient');
        Route::get('/edit/{ingredient:uuid}', [IngredientController::class, 'showEditIngredientForm'])->name('showEditIngredientForm');
        Route::patch('/update/{ingredient:id}', [IngredientController::class, 'updateIngredient'])->name('updateIngredient');
        Route::patch('/status/{ingredient:uuid}', [IngredientController::class, 'updateIngredientStatus'])->name('updateIngredientStatus');
        Route::get('/archive', [IngredientController::class, 'showDeactivatedIngredient'])->name('showDeactivatedIngredient');
        Route::patch('/deactivate/{ingredient:uuid}', [IngredientController::class, 'deactivateIngredient'])->name('deactivateIngredient');
        Route::patch('/damage/{ingredient:uuid}', [IngredientController::class, 'damageIngredient'])->name('damageIngredient');
        Route::patch('/reactivate/{ingredient:uuid}', [IngredientController::class, 'reactivateIngredient'])->name('reactivateIngredient');

        Route::get('/{ingredient_uuid}/data', [IngredientController::class, 'getIngredientData'])->name('getIngredientData');
        Route::post('/ajax/store', [IngredientController::class, 'storeIngredientAjax'])->name('storeIngredientAjax');
        Route::patch('/ajax/{ingredient_uuid}/update', [IngredientController::class, 'updateIngredientAjax'])->name('updateIngredientAjax');
    });

    // Product Ingredients
    Route::prefix('product_ingredients')->name('product_ingredients.')->group(function () {
        Route::get('/{product:uuid}', [ProductIngredientController::class, 'showProductIngredient'])->name('showProductIngredient');
        Route::post('/ajax/store', [ProductIngredientController::class, 'storeProductIngredientAjax'])->name('storeProductIngredientAjax');
        Route::get('/data/{product_uuid}/{product_ingredient_uuid}', [ProductIngredientController::class, 'getProductIngredientData'])->name('getProductIngredientData');
        Route::post('/ajax/{product_ingredient_uuid}/update', [ProductIngredientController::class, 'updateProductIngredientAjax'])->name('updateProductIngredientAjax');
    });
    
   // Inventory
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/stock-levels', [InventoryController::class, 'stockLevels'])->name('stockLevels');
        Route::get('/stock-in', [InventoryController::class, 'stockInPage'])->name('stockInPage');
        Route::get('/stock-out', [InventoryController::class, 'stockOutPage'])->name('stockOutPage');
        Route::get('/stock-in-history', [InventoryController::class, 'stockInHistory'])->name('stockInHistory');
        Route::get('/stock-out-history', [InventoryController::class, 'stockOutHistory'])->name('stockOutHistory');
    
        Route::get('/{uuid}/details', [InventoryController::class, 'details'])->name('details');
        Route::post('/stock-in', [InventoryController::class, 'storeStockIn'])->name('stockIn');
        Route::post('/stock-out', [InventoryController::class, 'storeStockOut'])->name('stockOut');
        Route::patch('/{uuid}/approve', [InventoryController::class, 'approve'])->name('approve');
        Route::patch('/{uuid}/reject', [InventoryController::class, 'reject'])->name('reject');
        Route::get('/{uuid}/data', [InventoryController::class, 'getData'])->name('getData');
    });
    
    // POS System
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PointOfSaleController::class, 'index'])->name('index');
        Route::get('/customers', [PointOfSaleController::class, 'getCustomers'])->name('get-customers');
        Route::post('/customers', [PointOfSaleController::class, 'storeCustomer'])->name('store-customer');
        Route::get('/search-product', [PointOfSaleController::class, 'searchProduct'])->name('search-product');
        Route::post('/check-stock', [PointOfSaleController::class, 'checkStock'])->name('check-stock');
        Route::post('/process-order', [PointOfSaleController::class, 'processOrder'])->name('process-order');
        Route::get('/history', [PointOfSaleController::class, 'orderHistory'])->name('history');
        Route::post('/cancel-order/{id}', [PointOfSaleController::class, 'cancelOrder'])->name('cancel-order');
        Route::post('/change-branch', [PointOfSaleController::class, 'changeBranch'])->name('change-branch');
        
        // ===== NEW REWARD ROUTES =====
        Route::get('/customer-rewards', [PointOfSaleController::class, 'getCustomerRewards'])->name('get-customer-rewards');
        Route::post('/apply-reward', [PointOfSaleController::class, 'applyReward'])->name('apply-reward');
    });

    // Staff
    Route::prefix('staff')->name('staff.')->group(function () {
        Route::get('/', [StaffListController::class, 'showStaffList'])->name('showStaffList');
        Route::get('/add', [StaffListController::class, 'showAddStaffAccountForm'])->name('showAddStaffAccountForm');
        Route::post('/store', [StaffListController::class, 'storeStaffAccount'])->name('storeStaffAccount');
        Route::patch('/status/{id}', [StaffListController::class, 'updateStaffAccountStatus'])->name('updateStaffAccountStatus');
        Route::get('/archive', [StaffListController::class, 'showDeactivatedStaffList'])->name('showDeactivatedStaffList');
        Route::patch('/deactivate/{id}', [StaffListController::class, 'deactivateStaffAccount'])->name('deactivateStaffAccount');
        Route::patch('/reactivate/{uid}', [StaffListController::class, 'reactivateStaffAccount'])->name('reactivateStaffAccount');

        Route::get('/{id}/edit-data', [StaffListController::class, 'getScheduleData'])->name('getScheduleData');
        Route::post('/shifts', [StaffListController::class, 'storeStaffShiftSchedule'])->name('storeStaffShiftSchedule');
        Route::patch('/shifts/{id}', [StaffListController::class, 'updateStaffShiftSchedule'])->name('updateStaffShiftSchedule');

        Route::get('/{shift_uuid}/schedules', [StaffListController::class, 'showStaffSchedules'])->name('staff_schedules');
        Route::delete('/staff/shifts/{id}/delete', [StaffListController::class, 'deleteStaffShift'])
            ->name('deleteStaffShift');
    });

    // ================================================================
    // LOYALTY & REWARDS SYSTEM
    // ================================================================

    // 1. Redeemable Items Management
    Route::prefix('redeemable-items')->name('redeemable_items.')->group(function () {
        Route::get('/', [RedeemableItemController::class, 'index'])->name('index');
        Route::post('/', [RedeemableItemController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [RedeemableItemController::class, 'edit'])->name('edit');
        Route::patch('/{id}', [RedeemableItemController::class, 'update'])->name('update');
        Route::patch('/{id}/status', [RedeemableItemController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{id}', [RedeemableItemController::class, 'destroy'])->name('destroy');
        
        Route::get('/categories', [RedeemableItemController::class, 'getCategories'])->name('categories');
        Route::get('/dropdown', [RedeemableItemController::class, 'getItemsForDropdown'])->name('dropdown');
        
        Route::get('/{id}/data', [RedeemableItemController::class, 'getItemData'])->name('get-data');
        Route::post('/ajax/store', [RedeemableItemController::class, 'storeAjax'])->name('store-ajax');
        Route::patch('/ajax/{id}', [RedeemableItemController::class, 'updateAjax'])->name('update-ajax');
    });

    // 2. Loyalty Tiers Management
    Route::prefix('loyalty-tiers')->name('loyalty_tiers.')->group(function () {
        Route::get('/', [LoyaltyTierController::class, 'index'])->name('index');
        Route::post('/', [LoyaltyTierController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [LoyaltyTierController::class, 'edit'])->name('edit');
        Route::patch('/{id}', [LoyaltyTierController::class, 'update'])->name('update');
        Route::patch('/{id}/status', [LoyaltyTierController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{id}', [LoyaltyTierController::class, 'destroy'])->name('destroy');
        
        Route::get('/{id}/data', [LoyaltyTierController::class, 'getTierData'])->name('get-data');
        Route::post('/ajax/store', [LoyaltyTierController::class, 'storeAjax'])->name('store-ajax');
        Route::patch('/ajax/{id}', [LoyaltyTierController::class, 'updateAjax'])->name('update-ajax');
        
        Route::get('/redeemable-items', [LoyaltyTierController::class, 'getRedeemableItems'])->name('get-redeemable-items');
    });

    // 3. Customer Rewards Management (Owner View)
    Route::prefix('customer-rewards')->name('customer_rewards.')->group(function () {
        Route::get('/', [CustomerRewardController::class, 'index'])->name('index');
        Route::get('/{customerId}/progress', [CustomerRewardController::class, 'getCustomerProgress'])->name('progress');
        Route::get('/reward/{rewardId}', [CustomerRewardController::class, 'getCustomerRewardDetails'])->name('details');
        Route::post('/{rewardId}/status', [CustomerRewardController::class, 'updateRewardStatus'])->name('status');
        Route::post('/process', [CustomerRewardController::class, 'processRewards'])->name('process');
        Route::get('/export', [CustomerRewardController::class, 'export'])->name('export');
        Route::get('/stats', [CustomerRewardController::class, 'getStats'])->name('stats');
        Route::get('/data', [CustomerRewardController::class, 'getData'])->name('data');
    });

    // 4. Staff Rewards Management (Approval & Redemption)
    Route::prefix('staff-rewards')->name('staff_rewards.')->group(function () {
        Route::get('/pending', [CustomerRewardController::class, 'getPendingRewards'])->name('pending');
        Route::post('/redeem', [CustomerRewardController::class, 'redeemVoucher'])->name('redeem');
        Route::get('/voucher/{code}', [CustomerRewardController::class, 'getVoucherDetails'])->name('voucher-details');
        Route::get('/history', [CustomerRewardController::class, 'getStaffRedemptionHistory'])->name('history');
    });

    // 5. Customer Rewards API (For Frontend/Customer Portal)
    Route::prefix('api/customer-rewards')->name('api.customer_rewards.')->group(function () {
        Route::post('/claim', [CustomerRewardController::class, 'claimReward'])->name('claim');
        Route::get('/my-progress', [CustomerRewardController::class, 'getMyProgress'])->name('my-progress');
        Route::get('/my-history', [CustomerRewardController::class, 'getMyHistory'])->name('my-history');
        Route::get('/my-reward/{rewardId}', [CustomerRewardController::class, 'getMyRewardDetails'])->name('my-reward');
        Route::get('/available', [CustomerRewardController::class, 'getAvailableRewards'])->name('available');
        Route::get('/tiers', [CustomerRewardController::class, 'getRewardTiers'])->name('tiers');
    });

    // 6. Legacy Reward Tiers (Deprecated - Kept for Compatibility)
    Route::prefix('reward_tiers')->name('reward_tiers.')->group(function () {
        Route::get('/', [LoyaltyTierController::class, 'index'])->name('index');
        Route::post('/', [LoyaltyTierController::class, 'store'])->name('store');
        Route::patch('/{id}', [LoyaltyTierController::class, 'update'])->name('update');
        Route::patch('/{id}/status', [LoyaltyTierController::class, 'toggleStatus'])->name('updateStatus');
        Route::get('/redeemable-items', [LoyaltyTierController::class, 'getRedeemableItems'])->name('get-redeemable-items');
    });

    // 7. Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/branch-report', [BranchReportController::class, 'index'])->name('branch_report');
        Route::get('/staff-report', [StaffReportController::class, 'staffReport'])->name('staff_report');
        Route::get('/report-data/{staff_uuid}', [StaffReportController::class, 'reportData'])->name('report_data');
        Route::get('/export-report/{staff_uuid}', [StaffReportController::class, 'exportReport'])->name('export');
        Route::get('/export-image/{staff_uuid}', [StaffReportController::class, 'exportImage'])->name('export_image');
        Route::get('/feedback-report', [CustomerFeedbacksController::class, 'report'])->name('feedback_report');
        Route::post('/ai-summary', [CustomerFeedbacksController::class, 'generateAISummary'])->name('ai-summary');
        Route::post('/feedback/ai-summary', [CustomerFeedbacksController::class, 'generateAISummary'])->name('feedback.ai-summary');
        Route::post('/feedback/ai-summary-overall', [CustomerFeedbacksController::class, 'generateOverallSummary'])->name('feedback.ai-summary-overall');
        Route::get('/inventory-report', [BranchReportController::class, 'index'])->name('inventory_report');
        
        // Loyalty Reports
        Route::get('/loyalty-performance', [CustomerRewardController::class, 'getLoyaltyPerformanceReport'])
            ->name('loyalty_performance');
        Route::get('/redemption-report', [CustomerRewardController::class, 'getRedemptionReport'])
            ->name('redemption_report');
        Route::get('/customer-loyalty', [CustomerRewardController::class, 'getCustomerLoyaltyReport'])
            ->name('customer_loyalty');
        Route::get('/export-loyalty', [CustomerRewardController::class, 'exportLoyaltyReport'])
            ->name('export_loyalty');
            
        // PDF Export Routes
    Route::get('/export-sales-pdf', [BranchReportController::class, 'exportSalesPdf'])->name('export_sales_pdf');
    Route::get('/export-inventory-pdf', [BranchReportController::class, 'exportInventoryPdf'])->name('export_inventory_pdf');
    Route::get('/export-feedback-pdf', [CustomerFeedbacksController::class, 'exportFeedbackPdf'])->name('export_feedback_pdf');
    });

    // Business Analytics
    Route::prefix('business_analytics')->name('business_analytics.')->group(function () {
        Route::get('/show', [AnalyticsController::class, 'showAnalytics'])->name('showAnalytics');
    });
});