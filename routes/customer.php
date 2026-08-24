<?php

use App\Http\Controllers\Customer\AdditionalInformationController;
use App\Http\Controllers\Customer\BookingFormController;
use App\Http\Controllers\Customer\HomeController;
use App\Http\Controllers\Customer\MyBookingsController;
use App\Http\Controllers\Customer\MyRewardTrackerController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TwoFactorAuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

Route::middleware(['auth:customer', 'prevent_back_history'])->prefix('sub_three')->name('sub_three.')->group(function () {
    /**
     * ===============================
     * ACCOUNT ROUTES
     * ===============================
     */
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [AccountController::class, 'showUserAccount'])->name('user_accounts');
        Route::put('/update-profile', [AccountController::class, 'updateProfileDetails'])->name('updateProfileDetails');
        Route::put('/update-password', [AccountController::class, 'updatePassword'])->name('updatePassword');
        Route::get('/check-regular-status', [AccountController::class, 'checkRegularStatus'])->name('checkRegularStatus');
    });

    /** PASSWORD SETUP (for Google users) */
    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/set-password', [AuthController::class, 'showSetPasswordForm'])->name('set-password.form');
        Route::post('/set-password', [AuthController::class, 'setPassword'])->name('set-password');
        Route::get('/skip-password', [AuthController::class, 'skipPasswordSetup'])->name('skip-password');
    });

    /**
     * ===============================
     * 2FA ROUTES
     * ===============================
     */
    Route::get('/2fa/setup', [TwoFactorAuthController::class, 'showSetupForm'])->name('2fa.setup');
    Route::post('/2fa/enable', [TwoFactorAuthController::class, 'enableTwoFactor'])->name('2fa.enable');
    Route::get('/2fa/backup-codes', [TwoFactorAuthController::class, 'showBackupCodes'])->name('2fa.backup-codes');
    Route::post('/2fa/disable', [TwoFactorAuthController::class, 'disableTwoFactor'])->name('2fa.disable');
    Route::post('/2fa/regenerate-backup-codes', [TwoFactorAuthController::class, 'regenerateBackupCodes'])->name('2fa.regenerate-backup-codes');

    /**
     * ===============================
     * NOTIFICATIONS
     * ===============================
     */
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('markAsRead');
        Route::post('/{id}/mark-unread', [NotificationController::class, 'markAsUnread'])->name('markAsUnread');

        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('markAllAsRead');
        Route::post('/mark-all-unread', [NotificationController::class, 'markAllAsUnread'])->name('markAllAsUnread');

        Route::get('/counts', [NotificationController::class, 'getCounts'])->name('counts');
    });

    /**
     * ===============================
     * CUSTOMER QR CODE ROUTES
     * ===============================
     */
    Route::prefix('qr')->name('qr.')->group(function () {
        Route::get('/my-qr-code', [App\Http\Controllers\Customer\CustomerQRCodeController::class, 'showMyQRCode'])
            ->name('show');
        Route::get('/download', [App\Http\Controllers\Customer\CustomerQRCodeController::class, 'downloadQRCode'])
            ->name('download');
        Route::post('/regenerate', [App\Http\Controllers\Customer\CustomerQRCodeController::class, 'regenerateQRCode'])
            ->name('regenerate');
        Route::get('/data', [App\Http\Controllers\Customer\CustomerQRCodeController::class, 'getQRCodeData'])
            ->name('data');
    });

    /**
     * ===============================
     * HOME / BOOKING ROUTES
     * ===============================
     */
    Route::prefix('home')->name('home.')->group(function () {
        // --- 1. General Home Routes ---
        Route::get('/', [HomeController::class, 'showHome'])->name('showHome');
        
        Route::get('/skip-preferences', function() {
            Session::put('preferences_redirect_shown', true);
            return redirect()->route('sub_three.home.showHome');
        })->name('skip.preferences');

        // --- Location Routes ---
        Route::prefix('location')->name('location.')->group(function () {
            Route::post('/update', [HomeController::class, 'updateLocation'])->name('update');
            Route::post('/clear', [HomeController::class, 'clearLocation'])->name('clear');
            Route::get('/status', [HomeController::class, 'getLocationStatus'])->name('status');
        });

        // --- Recommendations Routes ---
        Route::prefix('recommendations')->name('recommendations.')->group(function () {
            Route::get('/{type}', [HomeController::class, 'showAllRecommendations'])->name('all');
        });

        // --- Preferences Routes ---
        Route::prefix('preferences')->name('preferences.')->group(function () {
            Route::get('/', [HomeController::class, 'showPreferencesForm'])->name('form');
            Route::post('/save', [HomeController::class, 'savePreferences'])->name('save');
            Route::post('/update', [HomeController::class, 'updatePreferences'])->name('update');
            Route::post('/get-features', [HomeController::class, 'getFeaturesForBranches'])->name('get.features');
            Route::get('/get-features-all', [HomeController::class, 'getAllFeatures'])->name('get.features.all');
            Route::post('/get-categories', [HomeController::class, 'getCategoriesForBranches'])->name('get.categories');
            Route::post('/get-services', [HomeController::class, 'getServicesForCategories'])->name('get.services');
            Route::get('/get-space-types-all', [HomeController::class, 'getAllSpaceTypes'])->name('get.space.types.all');
            Route::post('/get-space-types', [HomeController::class, 'getSpaceTypesForServices'])->name('get.space.types');
            Route::get('/get-time-durations', [HomeController::class, 'getTimeDurationsAjax'])->name('get.time.durations');
            Route::get('/get-time-slots', [HomeController::class, 'getTimeSlots'])->name('get.time.slots');
        });

        // --- API Routes ---
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/features/all', [HomeController::class, 'getAllFeatures'])->name('features.all');
            Route::get('/branch/{branchUuid}/features', [HomeController::class, 'getBranchFeatures'])->name('branch.features');
            Route::get('/branch/{branchUuid}/recommended-services', [HomeController::class, 'getBranchRecommendedServices'])->name('branch.recommended-services');
            Route::get('/service-categories/{branchUuid}', [HomeController::class, 'getServiceCategoriesByBranch'])->name('service-categories');
            Route::get('/service-names/{branchUuid}/{categoryUuid}', [HomeController::class, 'getServiceNamesByCategory'])->name('service-names');
            Route::get('/space-type/{serviceUuid}', [HomeController::class, 'getSpaceTypeByService'])->name('space-type');
            Route::get('/available-seats', [BookingFormController::class, 'getAvailableSeatsWithAvailability'])->name('available-seats');
            Route::get('/check-seat-availability', [BookingFormController::class, 'checkSeatAvailabilityForTime']);
            Route::get('/available-time-slots-with-bookings', [BookingFormController::class, 'getAvailableTimeSlotsWithBookings']);
            Route::get('/existing-bookings', [BookingFormController::class, 'getExistingBookingsForDateRange']);
            Route::post('/get-existing-bookings', [BookingFormController::class, 'getExistingBookings'])->name('get.existing.bookings');
            Route::get('/service-packages/{serviceCategoryId}', [BookingFormController::class, 'getServicePackages'])->name('service-packages');
            Route::get('/check-additional-time-availability', [BookingFormController::class, 'checkAdditionalTimeAvailability']);
            Route::get('/branch-match/{branchId}', [HomeController::class, 'getBranchMatchPercentage'])->name('branch.match');
            Route::get('/all-branch-matches', [HomeController::class, 'getAllBranchMatchPercentages'])->name('all.branch.matches');
            Route::get('/branch-match-uuid/{branchUuid}', [HomeController::class, 'getBranchMatchPercentageByUuid'])->name('branch.match.uuid');
        });

        // --- Additional Information Routes ---
        Route::post('/send-message', [AdditionalInformationController::class, 'sendMessage'])->name('sendMessage');
        
        // ================================================================
        // REWARD ROUTES
        // ================================================================
        Route::prefix('rewards')->name('rewards.')->group(function () {
            Route::post('/get', [AdditionalInformationController::class, 'getCustomerRewards'])->name('get');
            Route::post('/apply', [AdditionalInformationController::class, 'applyReward'])->name('apply');
        });
        
        // Payment Options
        Route::match(['get', 'post'], '/payment-options', [AdditionalInformationController::class, 'showPaymentOptions'])->name('payment.options');

        // --- Feedback Routes ---
        Route::get('/feedbacks', [HomeController::class, 'showFeedbacks'])->name('feedbacks');
        Route::get('/service/{serviceUuid}/feedbacks', [HomeController::class, 'showServiceFeedbacks'])->name('service.feedbacks');
        Route::get('/branch/{branchUuid}/feedbacks', [HomeController::class, 'showBranchFeedbacks'])->name('branch.feedbacks');
        Route::get('/service/{serviceUuid}/feedbacks-data', [HomeController::class, 'getServiceFeedbacks'])->name('service.feedbacks.data');

        // --- Booking Flow Routes ---
        Route::post('/check-duplicate-booking', [BookingFormController::class, 'checkDuplicateBooking'])->name('api.check.duplicate.booking');
        Route::post('/check-booking-availability', [AdditionalInformationController::class, 'checkBookingAvailability'])->name('check.availability');
        Route::post('/booking/preview', [AdditionalInformationController::class, 'showBookingPreview'])->name('booking.preview');
        Route::post('/process-payment', [AdditionalInformationController::class, 'processPayment'])->name('processPayment');
        Route::get('/booking/confirmation/{booking_uuid?}', [AdditionalInformationController::class, 'showConfirmation'])->name('booking.confirmation');

        // --- Wildcard Routes (MUST BE LAST) ---
        Route::get('/booking/{branch_uuid}/{service_category_uuid}/{service_name_uuid}', [BookingFormController::class, 'showBookingForm'])->name('booking.form');
        Route::get('/branch/{branch_uuid}', [AdditionalInformationController::class, 'showBranch'])->name('branch.details');
        Route::get('/branch/{branch_uuid}/category/{service_category_uuid}', [AdditionalInformationController::class, 'showServiceCategory'])->name('service.category');
    });

    /**
     * ===============================
     * MY BOOKINGS
     * ===============================
     */
    Route::prefix('my_bookings')->name('my_bookings.')->group(function () {
        Route::get('/', [MyBookingsController::class, 'showMyBookings'])->name('showMyBookings');
        Route::get('/notes/{booking}', [MyBookingsController::class, 'getNotes'])->name('getNotes');
        Route::post('/update-note', [MyBookingsController::class, 'updateNote'])->name('updateNote');
        Route::post('/mark-no-show', [MyBookingsController::class, 'markNoShow'])->name('markNoShow');
        Route::get('/details/{booking_uuid}', [MyBookingsController::class, 'showBookingDetails'])->name('details');
        Route::get('/feedback/{booking_uuid}', [MyBookingsController::class, 'showFeedbackForm'])->name('feedback');
        Route::post('/feedback/{booking_uuid}', [MyBookingsController::class, 'submitFeedback'])->name('submitFeedback');
        Route::get('/reschedule/{booking_uuid}', [MyBookingsController::class, 'showRescheduleForm'])->name('reschedule.form');
        Route::post('/reschedule/{booking_uuid}/preview', [MyBookingsController::class, 'reschedulePreview'])->name('reschedule.preview');
        Route::get('/reschedule/{booking_uuid}/payment', [MyBookingsController::class, 'showReschedulePayment'])->name('reschedule.payment');
        Route::post('/reschedule/{booking_uuid}/process', [MyBookingsController::class, 'processReschedulePayment'])->name('reschedule.process');
        Route::post('/api/get-existing-bookings-reschedule', [MyBookingsController::class, 'getExistingBookingsForReschedule'])->name('api.get.existing.bookings.reschedule');
    });

    /**
     * ===============================
     * MY REWARD TRACKER
     * ===============================
     */
    Route::prefix('my_rewards')->name('my_rewards.')->group(function () {
        // Original Routes (KEPT AS IS)
        Route::get('/', [MyRewardTrackerController::class, 'showMyRewards'])->name('showMyRewards');
        Route::get('/booking-history', [MyRewardTrackerController::class, 'getBookingHistory'])->name('getBookingHistory');
        Route::get('/reward/{rewardId}', [MyRewardTrackerController::class, 'getRewardDetails'])->name('getRewardDetails');
        Route::post('/claim', [MyRewardTrackerController::class, 'claimReward'])->name('claim');
        Route::get('/transactions', [MyRewardTrackerController::class, 'getTransactionHistory'])->name('transactions');
        
        // ================================================================
        // NEW REWARD REDEMPTION ROUTES (ADDED BELOW)
        // ================================================================
        Route::get('/voucher/{voucherCode}', [MyRewardTrackerController::class, 'getVoucherDetails'])->name('getVoucherDetails');
        Route::get('/redemption-history', [MyRewardTrackerController::class, 'showRedemptionHistory'])->name('redemptionHistory');
        Route::get('/redemption-history/data', [MyRewardTrackerController::class, 'getRedemptionHistory'])->name('getRedemptionHistory');
        Route::post('/redeem', [MyRewardTrackerController::class, 'redeemReward'])->name('redeem');
    });
});