<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use App\Models\OwnerAccount;
use App\Models\StaffAccount;
use Illuminate\Http\Request;
use App\Mail\ResetPasswordMail;
use App\Models\CustomerAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cookie; // Added Cookie facade
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Password;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('guests.login');
    }

    // Show forgot password form
    public function showForgotPasswordForm()
    {
        return view('guests.forgot-password');
    }

    // Show reset password form
    public function showResetForm(Request $request, $token = null)
    {
        // Get user email from request
        $email = $request->email;
        $userName = 'User';
        
        // Try to find the user to get their name
        $account = null;
        if ($account = CustomerAccount::where('email', $email)->first()) {
            $userName = $account->first_name;
        } elseif ($account = StaffAccount::where('email', $email)->first()) {
            $userName = $account->first_name;
        } elseif ($account = OwnerAccount::where('email', $email)->first()) {
            $userName = $account->first_name;
        }
        
        // Return the FORM view, not the email template
        return view('guests.reset-password')->with([
            'token' => $token,
            'email' => $email,
            'userName' => $userName
        ]);
    }

    public function submitLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Please fill in all required fields.',
            'password.required' => 'Please fill in all required fields.',
            'email.email' => 'Please provide a valid email address.',
        ]);

        $account = null;
        $guard = null;

        if ($account = OwnerAccount::where('email', $credentials['email'])->first()) {
            $guard = 'owner';
        } elseif ($account = StaffAccount::where('email', $credentials['email'])->first()) {
            $guard = 'staff';
        } elseif ($account = CustomerAccount::where('email', $credentials['email'])->first()) {
            $guard = 'customer';
        }

        if (!$account || !Hash::check($credentials['password'], $account->password)) {
            return back()->withErrors(['email' => 'Invalid email or password'])->withInput();
        }

        // Perform role-based verification before proceeding
        $verificationError = $this->verifyAccountStatus($account, $guard);
        if ($verificationError) {
            return back()->withErrors(['email' => $verificationError])->withInput();
        }

        // --- 2FA FOR ALL USERS ---
        // Check if 2FA is enabled for the account
        $twoFactorEnabled = false;
        
        // Check if this device is already trusted (cookie exists)
        // Cookie name format: 'trusted_device_{user_id}'
        $isTrustedDevice = Cookie::get('trusted_device_' . $account->id);

        // Only enforce 2FA if it's enabled AND the device is NOT trusted
        if (!$isTrustedDevice) {
            if ($guard === 'owner' && $account->two_factor_enabled) {
                $twoFactorEnabled = true;
            } elseif ($guard === 'staff' && $account->two_factor_enabled) {
                $twoFactorEnabled = true;
            } elseif ($guard === 'customer' && $account->two_factor_enabled) {
                $twoFactorEnabled = true;
            }
        }

        if ($twoFactorEnabled) {
            // Generate 6-digit verification code
            $verificationCode = rand(100000, 999999);

            // Store in session with expiration (10 minutes)
            $request->session()->put('2fa_user_id', $account->id);
            $request->session()->put('2fa_user_guard', $guard);
            $request->session()->put('2fa_remember', $request->boolean('remember'));
            $request->session()->put('2fa_verification_code', $verificationCode);
            $request->session()->put('2fa_code_expires', now()->addMinutes(10));

            // Send email with verification code
            $this->sendTwoFactorCodeEmail($account->email, $verificationCode, $account->first_name);

            return redirect()->route('login.2fa.form');
        }

        Auth::guard($guard)->login($account, $request->boolean('remember'));
        $request->session()->regenerate();

        return match ($guard) {
            'owner' => redirect()->route('sub_one.dashboard.showDashboard')->with('success', 'Successfully logged in!'),
            'staff' => redirect()->route('sub_two.my_shift_schedules.showMyShift')->with('success', 'Successfully logged in!'),
            'customer' => redirect()->route('sub_three.home.showHome')->with('success', 'Successfully logged in!'),
            default => redirect()->route('showLoginForm')->with('success', 'Successfully logged in!')
        };
    }

    // Send reset link email
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Check if email exists
        $account = null;
        $accountType = null;
        $userName = 'User'; // Default

        if ($account = OwnerAccount::where('email', $request->email)->first()) {
            $accountType = 'owner';
            $userName = $account->first_name;
        } elseif ($account = StaffAccount::where('email', $request->email)->first()) {
            $accountType = 'staff';
            $userName = $account->first_name;
        } elseif ($account = CustomerAccount::where('email', $request->email)->first()) {
            $accountType = 'customer';
            $userName = $account->first_name;
        }

        // Always show success for security
        if (!$account) {
            return back()->with('status', 'If your email exists in our system, you will receive a password reset link.');
        }

        // Generate token
        $token = Str::random(64);

        // Store in custom_password_resets table
        \DB::table('custom_password_resets')->updateOrInsert(
            ['email' => $request->email],
            [
                'email' => $request->email,
                'token' => Hash::make($token),
                'account_type' => $accountType,
                'account_id' => $account->id,
                'created_at' => now()
            ]
        );

        // Generate the reset link
        $resetLink = route('password.reset', ['token' => $token, 'email' => $request->email]);

        // Send email with user name
        Mail::to($request->email)->send(new ResetPasswordMail($resetLink, $userName));

        // Remove the test_link in production
        return back()->with([
            'status' => 'Password reset link has been sent to your email.',
            // 'test_link' => "Reset Link: $resetLink"  // Remove this in production
        ]);
    }

    // Reset password
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',  // Start simple
        ]);

        // Find the reset record
        $resetRecord = \DB::table('custom_password_resets')
            ->where('email', $request->email)
            ->first();

        if (!$resetRecord) {
            return back()->withErrors(['email' => 'Invalid password reset token.']);
        }

        // Verify token
        if (!Hash::check($request->token, $resetRecord->token)) {
            return back()->withErrors(['email' => 'Invalid password reset token.']);
        }

        // Check expiration (60 minutes)
        if (now()->diffInMinutes($resetRecord->created_at) > 60) {
            \DB::table('custom_password_resets')->where('email', $request->email)->delete();
            return back()->withErrors(['email' => 'This password reset link has expired.']);
        }

        // Find the account
        $account = null;
        switch ($resetRecord->account_type) {
            case 'owner':
                $account = OwnerAccount::find($resetRecord->account_id);
                break;
            case 'staff':
                $account = StaffAccount::find($resetRecord->account_id);
                break;
            case 'customer':
                $account = CustomerAccount::find($resetRecord->account_id);
                break;
        }

        if (!$account) {
            return back()->withErrors(['email' => 'Account not found.']);
        }

        // Update password
        $account->password = Hash::make($request->password);
        $account->save();

        // Delete the reset token
        \DB::table('custom_password_resets')->where('email', $request->email)->delete();

        return redirect()
            ->route('showLoginForm')
            ->with('success', 'Your password has been reset successfully!');
    }

    /**
     * Redirect to Google OAuth for LOGIN
     * This is for ALL user types (Owner, Staff, Customer)
     */
    public function redirectToGoogleLogin(Request $request)
    {
        $request->session()->put('google_auth_action', 'login');
        return Socialite::driver('google')->redirect();
    }

    /**
     * Redirect to Google OAuth for REGISTER
     * This is ONLY for Customers
     */
    public function redirectToGoogleRegister(Request $request)
    {
        $request->session()->put('google_auth_action', 'register');
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth Callback - MODIFIED to support all user types
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $action = $request->session()->pull('google_auth_action', 'login');

            // Check email in all account types for login
            $owner = OwnerAccount::where('email', $googleUser->getEmail())->first();
            $staff = StaffAccount::where('email', $googleUser->getEmail())->first();
            $customer = CustomerAccount::where('email', $googleUser->getEmail())->first();

            // --- HANDLE GOOGLE LOGIN (FOR ALL USER TYPES) ---
            if ($action === 'login') {
                $account = $owner ?? $staff ?? $customer;
                $guard = null;

                if ($owner) {
                    $guard = 'owner';
                    $account = $owner;
                } elseif ($staff) {
                    $guard = 'staff';
                    $account = $staff;
                } elseif ($customer) {
                    $guard = 'customer';
                    $account = $customer;
                }

                // If account doesn't exist in any table
                if (!$account) {
                    return redirect()
                        ->route('showLoginForm')
                        ->with('error', 'This account is not registered. Please register your account first.')
                        ->withInput();
                }

                // Update Google ID if not set
                if (empty($account->google_id)) {
                    $account->google_id = $googleUser->getId();
                    $account->email_verified_at = now();
                    $account->save();
                }

                // Account exists, proceed with login
                $verificationError = $this->verifyAccountStatus($account, $guard);
                if ($verificationError) {
                    return back()->withErrors(['email' => $verificationError])->withInput();
                }

                // --- 2FA ADDITION WITH TRUSTED DEVICE CHECK ---
                // Check for trusted device cookie
                $isTrustedDevice = Cookie::get('trusted_device_' . $account->id);

                if ($account->two_factor_enabled && !$isTrustedDevice) {
                    // Generate 6-digit verification code
                    $verificationCode = rand(100000, 999999);

                    // Store in session
                    $request->session()->put('2fa_user_id', $account->id);
                    $request->session()->put('2fa_user_guard', $guard);
                    $request->session()->put('2fa_remember', false);
                    $request->session()->put('2fa_verification_code', $verificationCode);
                    $request->session()->put('2fa_code_expires', now()->addMinutes(10));

                    // Send email with verification code
                    $this->sendTwoFactorCodeEmail($account->email, $verificationCode, $account->first_name);

                    return redirect()->route('login.2fa.form');
                }
                // --- END 2FA ADDITION ---

                // Google login - remember me is not applicable for OAuth
                Auth::guard($guard)->login($account, false);
                $request->session()->regenerate();

                return match ($guard) {
                    'owner' => redirect()
                        ->route('sub_one.dashboard.showDashboard')
                        ->with('success', 'Successfully logged in with Google!'),
                    'staff' => redirect()
                        ->route('sub_two.my_shift_schedules.showMyShift')
                        ->with('success', 'Successfully logged in with Google!'),
                    'customer' => redirect()
                        ->route('sub_three.home.showHome')
                        ->with('success', 'Successfully logged in with Google!'),
                    default => redirect()
                        ->route('showLoginForm')
                        ->with('error', 'Unknown user type.')
                };
            }
            // --- HANDLE GOOGLE REGISTER (ONLY FOR CUSTOMERS) ---
            elseif ($action === 'register') {
                // Check if email already exists in ANY table
                if ($owner || $staff || $customer) {
                    return redirect()
                        ->route('showLoginForm')
                        ->with('error', 'This email is already registered. Please log in.')
                        ->withInput();
                }

                // Account doesn't exist, create new CUSTOMER account
                $nameParts = explode(' ', $googleUser->getName(), 2);
                $firstName = $nameParts[0];
                $lastName = $nameParts[1] ?? '';

                $newCustomer = CustomerAccount::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => Hash::make(Str::random(32)),  // Set a random, unusable password
                    'account_status' => 1,  // Auto-verify
                    'active' => 1,
                    'role' => 3,
                    'email_verified_at' => now(),  // Verified via Google
                    'remember_token' => Str::random(60),  // Add remember token
                    'two_factor_enabled' => 1,
                'two_factor_enabled_at' => now(),
                ]);

                // Send 2FA verification before granting access
                // after verify Log in the new customer - no remember me for OAuth registration
                $verificationCode = rand(100000, 999999);

                $request->session()->put('2fa_user_id', $newCustomer->id);
                $request->session()->put('2fa_user_guard', 'customer');
                $request->session()->put('2fa_remember', false);
                $request->session()->put('2fa_verification_code', $verificationCode);
                $request->session()->put('2fa_code_expires', now()->addMinutes(10));
            
                $this->sendTwoFactorCodeEmail($newCustomer->email, $verificationCode, $newCustomer->first_name);
            
                return redirect()
                    ->route('login.2fa.form')
                    ->with('status', 'Account created! Please verify your email to continue.');
            }

            // Fallback for unknown action
            return redirect()
                ->route('showLoginForm')
                ->with('error', 'An unknown error occurred. Please try again.');
        } catch (\Exception $e) {
            \Log::error('Google authentication error: ' . $e->getMessage());
            return redirect()
                ->route('showLoginForm')
                ->with('error', 'Google authentication failed. Please try again.');
        }
    }

    /**
     * Show customer registration form
     */
    public function showCustomerRegistration()
    {
        $recaptchaSiteKey = config('services.recaptcha.site_key');
        return view('guests.customer-register', compact('recaptchaSiteKey'));
    }

    /**
     * Handle customer registration
     */
    public function registerCustomer(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:customer_accounts,email',
            'password' => [
                'required',
                'string',
                'min:12',
                'confirmed',
                'regex:/[A-Z]/',  // At least one uppercase
                'regex:/[!@#$%^&*(),.?":{}|<>]/',  // At least one special
                'regex:/(\D*\d){4,}/'  // At least 4 numbers
            ],
            'g-recaptcha-response' => 'required'
        ], [
            'password.min' => 'Password must be at least 12 characters long.',
            'password.regex' => 'The password must contain at least 1 uppercase letter, 1 special character, and 4 numbers.',
            'g-recaptcha-response.required' => 'Please complete the reCAPTCHA verification.'
        ]);

        // Verify reCAPTCHA using modern HTTP client
        $recaptchaVerified = $this->verifyRecaptcha($validated['g-recaptcha-response']);

        if (!$recaptchaVerified) {
            return back()
                ->withErrors(['recaptcha' => 'reCAPTCHA verification failed. Please try again.'])
                ->withInput($request->except('password', 'password_confirmation'));
        }

        try {
            $customer = CustomerAccount::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'account_status' => 1,  // 1=verified
                'active' => 1,
                'role' => 3,
                'email_verified_at' => now(),  // Auto-verify email
                'remember_token' => Str::random(60),  // Add remember token
                'date_joined' => now(),  // Add date joined
                'two_factor_enabled' => 1,
                'two_factor_enabled_at' => now(),
            ]);
            
            // Send 2FA verification before granting access
            // Log in the customer after verify registration - no remember me for registration
            $verificationCode = rand(100000, 999999);
        
            $request->session()->put('2fa_user_id', $customer->id);
            $request->session()->put('2fa_user_guard', 'customer');
            $request->session()->put('2fa_remember', false);
            $request->session()->put('2fa_verification_code', $verificationCode);
            $request->session()->put('2fa_code_expires', now()->addMinutes(10));
        
            $this->sendTwoFactorCodeEmail($customer->email, $verificationCode, $customer->first_name);
        
            return redirect()
                ->route('login.2fa.form')
                ->with('status', 'Account created! Please verify your email to continue.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Registration failed. Please try again.')
                ->withInput($request->except('password', 'password_confirmation'));
        }
    }

    /**
     * Verify reCAPTCHA response
     */
    private function verifyRecaptcha($recaptchaResponse)
    {
        if (empty($recaptchaResponse)) {
            return false;
        }

        $secret = config('services.recaptcha.secret_key');

        if (empty($secret)) {
            \Log::error('reCAPTCHA secret key not configured');
            return false;
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secret,
                'response' => $recaptchaResponse,
                'remoteip' => request()->ip()
            ]);

            $data = $response->json();

            return $data['success'] ?? false;
        } catch (\Exception $e) {
            \Log::error('reCAPTCHA verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Show set password form for Google users
     */
    public function showSetPasswordForm()
    {
        // Only allow access for logged-in customers
        if (!Auth::guard('customer')->check()) {
            return redirect()->route('showLoginForm');
        }

        return view('guests.set-password');
    }

    /**
     * Handle password setup for Google users
     */
    public function setPassword(Request $request)
    {
        // Only allow for logged-in customers
        if (!Auth::guard('customer')->check()) {
            return redirect()->route('showLoginForm');
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $customer = Auth::guard('customer')->user();

        $customer->update([
            'password' => Hash::make($request->password)
        ]);

        // Mark that user has set a custom password
        session(['has_set_password' => true]);

        return redirect()
            ->route('sub_three.home.showHome')
            ->with('success', 'Password set successfully! You can now login with email and password.');
    }

    /**
     * Skip password setup
     */
    public function skipPasswordSetup()
    {
        // Only allow for logged-in customers
        if (!Auth::guard('customer')->check()) {
            return redirect()->route('showLoginForm');
        }

        // Mark that user has "skipped" for this session
        session(['has_set_password' => true]);

        return redirect()
            ->route('sub_three.home.showHome')
            ->with('success', 'Password setup skipped for this session.');
    }

    /**
     * Show the 2FA verification form for ALL users
     */
    public function show2faForm()
    {
        // Only show if the user has been partially authenticated
        if (!Session::has('2fa_user_id')) {
            return redirect()->route('showLoginForm');
        }

        $email = $this->getUserEmailFromSession();

        return view('guests.login-2fa', [
            'email' => $email
        ]);
    }

    /**
     * Verify the 2FA token and log the user in for ALL users
     */
    public function verify2fa(Request $request)
    {
        $request->validate([
            'verification_code' => 'required|string|size:6',
        ]);

        $userId = $request->session()->get('2fa_user_id');
        $guard = $request->session()->get('2fa_user_guard');
        $remember = $request->session()->get('2fa_remember', false);
        $storedCode = $request->session()->get('2fa_verification_code');
        $codeExpires = $request->session()->get('2fa_code_expires');

        if (!$userId || !$guard || !$storedCode || !$codeExpires) {
            return redirect()->route('showLoginForm')->withErrors(['email' => 'Session expired. Please log in again.']);
        }

        // Check if code is expired
        if (now()->gt($codeExpires)) {
            // Clear session and redirect back to login
            $request->session()->forget([
                '2fa_user_id',
                '2fa_user_guard',
                '2fa_remember',
                '2fa_verification_code',
                '2fa_code_expires'
            ]);

            return redirect()
                ->route('showLoginForm')
                ->withErrors(['email' => 'Verification code has expired. Please log in again.']);
        }

        // Verify the code
        if ($request->verification_code != $storedCode) {
            return back()->withErrors(['verification_code' => 'Invalid verification code.']);
        }

        // Find the account based on guard type
        $account = null;
        switch ($guard) {
            case 'owner':
                $account = OwnerAccount::find($userId);
                break;
            case 'staff':
                $account = StaffAccount::find($userId);
                break;
            case 'customer':
                $account = CustomerAccount::find($userId);
                break;
        }

        if (!$account) {
            return redirect()->route('showLoginForm')->withErrors(['email' => 'User not found.']);
        }

        // Success! Log them in
        Auth::guard($guard)->login($account, $remember);
        $request->session()->regenerate();

        // --- ADDED TRUSTED DEVICE COOKIE ---
        // Set a cookie that lasts 30 days (43200 minutes)
        // This marks this specific browser as trusted for this user
        Cookie::queue('trusted_device_' . $account->id, 'true', 43200);
        // -----------------------------------

        // Clear 2FA session data
        $request->session()->forget([
            '2fa_user_id',
            '2fa_user_guard',
            '2fa_remember',
            '2fa_verification_code',
            '2fa_code_expires'
        ]);

        $successMessage = 'Successfully logged in!';

        switch ($guard) {
            case 'owner':
                return redirect()->route('sub_one.dashboard.showDashboard')->with('success', $successMessage);
            case 'staff':
                return redirect()->route('sub_two.my_shift_schedules.showMyShift')->with('success', $successMessage);
            case 'customer':
                return redirect()->route('sub_three.home.showHome')->with('success', $successMessage);
            default:
                return redirect()->route('showLoginForm')->with('success', $successMessage);
        }
    }

    /**
     * Resend 2FA verification code
     */
    public function resend2faCode(Request $request)
    {
        if (!Session::has('2fa_user_id')) {
            return redirect()->route('showLoginForm');
        }

        $userId = $request->session()->get('2fa_user_id');
        $guard = $request->session()->get('2fa_user_guard');

        // Find the account
        $account = null;
        switch ($guard) {
            case 'owner':
                $account = OwnerAccount::find($userId);
                break;
            case 'staff':
                $account = StaffAccount::find($userId);
                break;
            case 'customer':
                $account = CustomerAccount::find($userId);
                break;
        }

        if (!$account) {
            return redirect()->route('showLoginForm')->withErrors(['email' => 'User not found.']);
        }

        // Generate new verification code
        $verificationCode = rand(100000, 999999);

        // Update session with new code
        $request->session()->put('2fa_verification_code', $verificationCode);
        $request->session()->put('2fa_code_expires', now()->addMinutes(10));

        // Send email with new verification code
        $this->sendTwoFactorCodeEmail($account->email, $verificationCode, $account->first_name);

        return back()->with('status', 'A new verification code has been sent to your email.');
    }

    /**
     * Enable 2FA for any user type
     */
    public function enable2fa(Request $request)
    {
        $user = Auth::user();
        $guard = Auth::getDefaultDriver();

        // Enable 2FA based on guard
        switch ($guard) {
            case 'owner':
                OwnerAccount::where('id', $user->id)->update(['two_factor_enabled' => true]);
                break;
            case 'staff':
                StaffAccount::where('id', $user->id)->update(['two_factor_enabled' => true]);
                break;
            case 'customer':
                CustomerAccount::where('id', $user->id)->update(['two_factor_enabled' => true]);
                break;
        }

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication enabled successfully.'
        ]);
    }

    /**
     * Disable 2FA for any user type
     */
    public function disable2fa(Request $request)
    {
        $user = Auth::user();
        $guard = Auth::getDefaultDriver();

        $request->validate([
            'password' => 'required|string',
        ]);

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password.'
            ], 401);
        }

        // Disable 2FA based on guard
        switch ($guard) {
            case 'owner':
                OwnerAccount::where('id', $user->id)->update(['two_factor_enabled' => false]);
                break;
            case 'staff':
                StaffAccount::where('id', $user->id)->update(['two_factor_enabled' => false]);
                break;
            case 'customer':
                CustomerAccount::where('id', $user->id)->update(['two_factor_enabled' => false]);
                break;
        }

        // Also clear the trusted device cookie for this user
        Cookie::queue(Cookie::forget('trusted_device_' . $user->id));

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication disabled successfully.'
        ]);
    }

    /**
     * Send 2FA verification code email
     */
    private function sendTwoFactorCodeEmail($email, $code, $name = 'User')
    {
        try {
            // Send actual email
            Mail::send('emails.two-factor-code', [
                'code' => $code,
                'name' => $name,
                'expires' => 10  // minutes
            ], function ($message) use ($email, $name) {
                $message
                    ->to($email)
                    ->subject('Your Two-Factor Authentication Code - Linkud Hub');
            });

            // Log for debugging
            \Log::info('2FA Verification Code sent to ' . $email . ': ' . $code);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to send 2FA email to ' . $email . ': ' . $e->getMessage());

            // For development, you can still log the code
            \Log::info('2FA Code (not sent due to error) for ' . $email . ': ' . $code);

            return false;
        }
    }

    /**
     * Get user email from session for 2FA form
     */
    private function getUserEmailFromSession()
    {
        $userId = Session::get('2fa_user_id');
        $guard = Session::get('2fa_user_guard');

        if (!$userId || !$guard) {
            return null;
        }

        $account = null;
        switch ($guard) {
            case 'owner':
                $account = OwnerAccount::find($userId);
                break;
            case 'staff':
                $account = StaffAccount::find($userId);
                break;
            case 'customer':
                $account = CustomerAccount::find($userId);
                break;
        }

        return $account ? $this->maskEmail($account->email) : null;
    }

    /**
     * Mask email for display (e.g., j****@example.com)
     */
    private function maskEmail($email)
    {
        $parts = explode('@', $email);
        if (count($parts) != 2) {
            return $email;
        }

        $username = $parts[0];
        $domain = $parts[1];

        if (strlen($username) <= 1) {
            $maskedUsername = str_repeat('*', strlen($username));
        } else {
            $maskedUsername = $username[0] . str_repeat('*', strlen($username) - 1);
        }

        return $maskedUsername . '@' . $domain;
    }

    /**
     * Role-based account verification logic
     */
    private function verifyAccountStatus($account, $guard)
    {
        // Owner = role 1
        if ($guard === 'owner') {
            if ($account->account_status != 1 || $account->active != 1) {
                return 'Your account is not verified or not active.';
            }
        }

        // Staff = role 2
        if ($guard === 'staff') {
            if (empty($account->branch_id)) {
                return 'Your account is not linked to any branch.';
            }
            if ($account->account_status != 1 || $account->active != 1) {
                return 'Your account is not verified or not active.';
            }
        }

        // Customer = role 3
        if ($guard === 'customer') {
            if ($account->account_status != 1 || $account->active != 1) {
                return 'Your account is not verified or not active.';
            }
        }

        return null;  // Passed verification
    }

    // Logout User
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('showLoginForm')->with('success', 'Successfully logged out.');
    }
}