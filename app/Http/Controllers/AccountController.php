<?php

namespace App\Http\Controllers;

use App\Models\CustomerAccount;
use App\Models\OwnerAccount;
use App\Models\StaffAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AccountController extends Controller
{
    /**
     * Display the user's account information page.
     */
    public function showUserAccount()
    {
        $userInfo = $this->getCurrentUserInfo();

        if (!$userInfo) {
            return redirect('/login')->with('toast_error', 'Authentication required.');
        }

        $user = $userInfo['model_class']::where('id', $userInfo['user']->id)
            ->where('active', 1)
            ->where('account_status', 1)
            ->first();

        if (!$user) {
            Auth::guard($userInfo['guard'])->logout();
            return redirect('/login')->with('toast_error', 'Your account is inactive or unverified.');
        }

        $isEligibleForCongrats = false;
        if ($userInfo['guard'] === 'customer') {
            // Ensure we have a CustomerAccount model instance
            $customer = CustomerAccount::find($user->id);
            if ($customer) {
                $isEligibleForCongrats = $customer->isEligibleForCongratulations();
                $customer->refresh();
                $user = $customer;  // Update the user variable with the refreshed model
            }
        }

        return view('accounts.user_accounts', [
            'user' => $user,
            'guard' => $userInfo['guard'],
            'isEligibleForCongrats' => $isEligibleForCongrats,
        ]);
    }

    /**
     * Get current user's guard, model, and user instance
     */
    private function getCurrentUserInfo()
    {
        $guards = ['owner', 'staff', 'customer'];

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $modelClass = $this->getModelClass($guard);
                $user = Auth::guard($guard)->user();

                return [
                    'guard' => $guard,
                    'model_class' => $modelClass,
                    'user' => $user
                ];
            }
        }

        return null;
    }

    /**
     * Map guard to model class
     */
    private function getModelClass($guard)
    {
        return match ($guard) {
            'owner' => OwnerAccount::class,
            'staff' => StaffAccount::class,
            'customer' => CustomerAccount::class,
            default => null
        };
    }

    /**
     * Handle the form submission for updating profile details (name, contact, address).
     */
    public function updateProfileDetails(Request $request)
    {
        $userInfo = $this->getCurrentUserInfo();

        if (!$userInfo) {
            $this->forceLogout($request);
            return redirect('/login')->with('toast_error', 'Session expired or authentication failed.');
        }

        // Validation rules
        $validationRules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'contact_no' => ['required', 'string', 'max:15'],
            'address' => ['required', 'string', 'max:500'],
        ];

        // Add GCash QR code validation for owner ONLY - Updated to remove Staff
        if ($userInfo['guard'] === 'owner') {
            $validationRules['gcash_qr_code_imgs'] = ['nullable', 'array', 'max:5']; // Max 5 images
            $validationRules['gcash_qr_code_imgs.*'] = ['image', 'mimes:jpeg,png,jpg,gif', 'max:2048']; // 2MB max each
        }

        $request->validate($validationRules);

        $updateData = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'contact_no' => $request->contact_no,
            'address' => $request->address,
        ];

        // Handle GCash QR code logic (Delete + Upload) for owner ONLY
        if ($userInfo['guard'] === 'owner') {
            // 1. Start with the current existing images
            $currentImages = $this->getExistingQrCodes($userInfo['user']);
            $imagesModified = false;

            // 2. Process Deletions FIRST
            if ($request->has('delete_qr_codes')) {
                $deleteData = $request->input('delete_qr_codes');
                $imagesToDelete = [];

                if (is_string($deleteData) && !empty($deleteData)) {
                    $imagesToDelete = json_decode($deleteData, true) ?? [];
                } elseif (is_array($deleteData)) {
                    $imagesToDelete = $deleteData;
                }

                if (count($imagesToDelete) > 0) {
                    // Security Check: Ensure we only delete images that belong to this user
                    $validImagesToDelete = array_intersect($imagesToDelete, $currentImages);

                    if (count($validImagesToDelete) > 0) {
                        // Remove from the current list
                        $currentImages = array_values(array_diff($currentImages, $validImagesToDelete));
                        $imagesModified = true;

                        // Delete physical files
                        foreach ($validImagesToDelete as $imageToDelete) {
                            if (Storage::disk('public')->exists($imageToDelete)) {
                                Storage::disk('public')->delete($imageToDelete);
                            }
                        }
                    }
                }
            }

            // 3. Process Uploads SECOND (Append to the potentially modified list)
            if ($request->hasFile('gcash_qr_code_imgs')) {
                foreach ($request->file('gcash_qr_code_imgs') as $file) {
                    $filename = 'gcash_qr_' . $userInfo['user']->id . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('gcash_qr_codes', $filename, 'public');
                    
                    $currentImages[] = $path; // Append new image
                    $imagesModified = true;
                }
            }

            // 4. Update the data array only if changes occurred or if we need to sync state
            // If we processed deletes or uploads, we update the column.
            if ($imagesModified) {
                $updateData['gcash_qr_code_img'] = json_encode(array_values($currentImages));
            }
        }

        $isCustomer = $userInfo['guard'] === 'customer';
        $success = $this->updateAccount($userInfo['user']->id, $userInfo['model_class'], $updateData, $isCustomer);

        if (!$success) {
            return back()->with('toast_error', 'Failed to update profile.');
        }

        return back()->with('toast_success', 'Profile details updated successfully!');
    }

    /**
     * Get existing QR codes as array - HANDLES BOTH STRING AND ARRAY
     */
    private function getExistingQrCodes($user)
    {
        $existingImages = [];
        
        if ($user->gcash_qr_code_img) {
            // Check if it's already an array (due to model casting)
            if (is_array($user->gcash_qr_code_img)) {
                $existingImages = $user->gcash_qr_code_img;
            } 
            // Check if it's a JSON string
            elseif (is_string($user->gcash_qr_code_img)) {
                $decoded = json_decode($user->gcash_qr_code_img, true);
                if ($decoded && is_array($decoded)) {
                    $existingImages = $decoded;
                } else {
                    // Single image stored as string
                    $existingImages = [$user->gcash_qr_code_img];
                }
            }
        }
        
        return $existingImages;
    }

    /**
     * Handle the form submission for updating account details (e.g., password).
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => [
                'required',
                'string',
                'min:12',
                'confirmed',
                'regex:/[A-Z]/',
                'regex:/[!@#$%^&*(),.?":{}|<>]/',
                'regex:/(\D*\d){4,}/'
            ],
        ], [
            'password.required' => 'The new password field is required.',
            'password.min' => 'The new password must be at least 12 characters long.',
            'password.confirmed' => 'The new password confirmation does not match.',
            'password.regex' => 'The new password must contain at least 1 uppercase letter, 1 special character, and 4 numbers.',
        ]);

        $userInfo = $this->getCurrentUserInfo();

        if (!$userInfo) {
            $this->forceLogout($request);
            return redirect('/login')->with('toast_error', 'Session expired or authentication failed.');
        }

        // Verify current password
        if (!Hash::check($request->current_password, $userInfo['user']->password)) {
            return back()->with('toast_error', 'Current password is incorrect.');
        }

        $updateData = [
            'password' => Hash::make($request->password)
        ];

        $isCustomer = $userInfo['guard'] === 'customer';
        $success = $this->updateAccount($userInfo['user']->id, $userInfo['model_class'], $updateData, $isCustomer);

        if (!$success) {
            return back()->with('toast_error', 'Failed to update password.');
        }

        return back()->with('toast_success', 'Password updated successfully!');
    }

    /**
     * Update account information
     */
    private function updateAccount($userId, $modelClass, $updateData, $isCustomer = false)
    {
        try {
            $account = $modelClass::where('id', $userId)->first();

            if (!$account) {
                return false;
            }

            return $account->update($updateData);
        } catch (\Exception $e) {
            \Log::error('Account update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check and update regular status for customer
     */
    public function checkRegularStatus()
    {
        if (Auth::guard('customer')->check()) {
            // Get the customer ID from Auth
            $customerId = Auth::guard('customer')->id();

            // Get the actual CustomerAccount model instance
            $customer = CustomerAccount::find($customerId);

            if (!$customer) {
                return response()->json(['error' => 'Customer not found'], 404);
            }

            // Use the model methods
            $wasUpdated = $customer->updateRegularStatus();

            // Refresh the model to get updated data
            $customer->refresh();

            return response()->json([
                'regular' => $customer->regular,
                'was_updated' => $wasUpdated,
                'eligible_for_congrats' => $customer->isEligibleForCongratulations()
            ]);
        }

        return response()->json(['error' => 'Not a customer'], 403);
    }

    /**
     * Update regular status based on completed bookings
     */
    private function updateRegularStatus($customer)
    {
        try {
            // Count completed bookings (booking_status = 4)
            $completedBookingsCount = DB::table('bookings')
                ->where('customer_account_id', $customer->id)
                ->where('booking_status', 4)  // Completed status
                ->where('active', 1)
                ->count();

            // Define threshold for becoming a regular customer
            $regularThreshold = 5;  // Example: 5 completed bookings makes you a regular

            $newRegularStatus = $completedBookingsCount >= $regularThreshold ? 1 : 0;

            // Update only if status changed
            if ($customer->regular != $newRegularStatus) {
                $customer->regular = $newRegularStatus;
                $customer->save();
                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if customer is eligible for congratulations message
     */
    private function isEligibleForCongratulations($customer)
    {
        try {
            // Check if customer just became regular and hasn't seen congrats yet
            // You might want to add a field like 'congrats_shown' to track this
            if ($customer->regular == 1) {
                // Check if we've already shown congratulations
                // For now, using a session or temporary flag
                return !session('congrats_shown_' . $customer->id, false);
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Mark congratulations as shown for customer
     */
    public function markCongratsShown()
    {
        if (Auth::guard('customer')->check()) {
            $customer = Auth::guard('customer')->user();
            session(['congrats_shown_' . $customer->id => true]);

            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Not a customer'], 403);
    }

    /**
     * Force logout across all guards
     */
    private function forceLogout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}