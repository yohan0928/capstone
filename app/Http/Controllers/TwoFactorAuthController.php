<?php

namespace App\Http\Controllers;

use App\Models\CustomerAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthController extends Controller
{
    /**
     * Show 2FA setup form
     */
    public function showSetupForm()
    {
        $user = Auth::guard('customer')->user();
        
        if ($user->two_factor_enabled) {
            return redirect()->route('sub_three.home.showHome')
                ->with('toast_info', '2FA is already enabled.');
        }

        // Generate secret if not exists
        if (!$user->two_factor_secret) {
            $secret = $user->generateTwoFactorSecret();
        } else {
            $secret = decrypt($user->two_factor_secret);
        }

        return view('customer.two_factor_authentication.2fa-setup', compact('secret'));
    }

    /**
     * Enable 2FA
     */
    public function enableTwoFactor(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = Auth::guard('customer')->user();

        if ($user->two_factor_enabled) {
            return back()->withErrors(['code' => '2FA is already enabled.']);
        }

        // Verify the code
        if (!$user->verifyTwoFactorCode($request->code)) {
            return back()->withErrors(['code' => 'Invalid verification code.']);
        }

        // Generate backup codes
        $backupCodes = $user->generateBackupCodes();
        
        // Enable 2FA
        $user->enableTwoFactor($backupCodes);

        return redirect()->route('sub_three.2fa.setup')
            ->with('backup_codes', $backupCodes)
            ->with('toast_success', 'Two-factor authentication has been enabled!');
    }

    /**
     * Show backup codes
     */
    public function showBackupCodes()
    {
        if (!session('backup_codes')) {
            return redirect()->route('sub_three.home.showHome');
        }

        $backupCodes = session('backup_codes');
        
        return view('customer.two_factor_authentication.2fa-backup-codes', compact('backupCodes'));
    }

    /**
     * Disable 2FA
     */
    public function disableTwoFactor(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password:customer',
        ]);

        $user = Auth::guard('customer')->user();
        $user->disableTwoFactor();

        return redirect()->route('sub_three.home.showHome')
            ->with('toast_success', 'Two-factor authentication has been disabled.');
    }

    /**
     * Regenerate backup codes
     */
    public function regenerateBackupCodes(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password:customer',
        ]);

        $user = Auth::guard('customer')->user();
        
        if (!$user->two_factor_enabled) {
            return back()->withErrors(['password' => '2FA is not enabled.']);
        }

        $backupCodes = $user->generateBackupCodes();
        $user->enableTwoFactor($backupCodes);

        return redirect()->route('sub_three.2fa.setup')
            ->with('backup_codes', $backupCodes)
            ->with('toast_success', 'Backup codes regenerated successfully!');
    }
}