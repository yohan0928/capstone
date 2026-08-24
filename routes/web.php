<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Guest\GuestController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

// Guest Routes
Route::middleware(['web', 'guest', 'prevent_back_history'])->group(function () {
    // Welcome page - Updated to use GuestController
    Route::get('/', [GuestController::class, 'showHome'])->name('welcome');
    
    // Guest location routes (AJAX)
    Route::post('/guest/location/update', [GuestController::class, 'updateLocation'])->name('guest.location.update');
    Route::post('/guest/location/clear', [GuestController::class, 'clearLocation'])->name('guest.location.clear');
    
    // Guest feedback routes
    Route::get('/feedbacks', [GuestController::class, 'showFeedbacks'])->name('guest.feedbacks');
    Route::get('/feedbacks/service/{serviceUuid}', [GuestController::class, 'showServiceFeedbacks'])->name('guest.service.feedbacks');
    Route::get('/feedbacks/branch/{branchUuid}', [GuestController::class, 'showBranchFeedbacks'])->name('guest.branch.feedbacks');
    
    // Contact form
    Route::post('/contact', [GuestController::class, 'sendMessage'])->name('guest.sendMessage');
    
    // Login
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('showLoginForm');
    Route::post('/login', [AuthController::class, 'submitLogin'])->name('submitLogin');

    // Password Reset Routes
    Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->name('password.update');

    // FOR CUSTOMERS ONLY
    // Google OAuth Routes
    Route::get('auth/google/login', [AuthController::class, 'redirectToGoogleLogin'])->name('redirectToGoogleLogin');
    Route::get('auth/google/register', [AuthController::class, 'redirectToGoogleRegister'])->name('redirectToGoogleRegister');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('google.callback');

    // Customer Registration Routes
    Route::get('/register/customer', [AuthController::class, 'showCustomerRegistration'])->name('showCustomerRegistration');
    Route::post('/register/customer', [AuthController::class, 'registerCustomer'])->name('customer.register.submit');

    // 2FA Verification Routes - FIXED
    Route::get('/login/2fa', [AuthController::class, 'show2faForm'])->name('login.2fa.form');
    Route::post('/login/2fa/verify', [AuthController::class, 'verify2fa'])->name('login.2fa.verify');
    Route::post('/login/2fa/resend', [AuthController::class, 'resend2faCode'])->name('login.2fa.resend');

    // Legal Pages Routes
    Route::get('/privacy-policy', [AuthController::class, 'showPrivacyPolicy'])->name('showPrivacyPolicy');
    Route::get('/terms-of-service', [AuthController::class, 'showTermsOfService'])->name('showTermsOfService');

    // Guest API Routes for filters and recommendations (accessible without authentication)
    Route::prefix('api/guest')->group(function () {
        Route::get('/features/all', [GuestController::class, 'getAllFeatures'])->name('guest.api.features.all');
        Route::get('/branch/{branchUuid}/recommended-services', [GuestController::class, 'getBranchRecommendedServices'])->name('guest.api.branch.recommended.services');
        Route::get('/service-categories', [GuestController::class, 'getServiceCategories'])->name('guest.api.service.categories');
        Route::get('/service-names', [GuestController::class, 'getServiceNames'])->name('guest.api.service.names');
        Route::get('/space-types', [GuestController::class, 'getSpaceTypes'])->name('guest.api.space.types');
        Route::get('/branch/{branchUuid}/categories', [GuestController::class, 'getServiceCategoriesByBranch'])->name('guest.api.branch.categories');
        Route::get('/branch/{branchUuid}/category/{categoryUuid}/services', [GuestController::class, 'getServiceNamesByCategory'])->name('guest.api.branch.category.services');
    });
});

// Authenticated user routes
Route::middleware(['prevent_back_history'])->group(function () {
    // Logout – we'll handle guard inside the controller
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // 2FA Management Routes (for authenticated users to enable/disable)
    Route::middleware(['auth'])->group(function () {
        Route::post('/account/enable-2fa', [AuthController::class, 'enable2fa'])->name('account.2fa.enable');
        Route::post('/account/disable-2fa', [AuthController::class, 'disable2fa'])->name('account.2fa.disable');
        Route::post('/account/verify-activate-2fa', [AuthController::class, 'verifyAndActivate2fa'])->name('account.2fa.verify.activate');
    });

    // Owner, staff, customer routes each define their own guard
    require __DIR__ . '/super_admin.php';
    require __DIR__ . '/owner.php';
    require __DIR__ . '/staff.php';
    require __DIR__ . '/customer.php';
});

Route::get('/auto-logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect('/')->with('message', 'You have been automatically logged out.');
})->name('auto.logout');

Route::get('/test-email', function() {
    try {
        Mail::raw('Test email', function($message) {
            $message->to('ivanchristophersoberano@gmail.com')
                    ->subject('Test Email');
        });
        return 'Email sent successfully';
    } catch (\Exception $e) {
        return 'Failed: ' . $e->getMessage();
    }
});