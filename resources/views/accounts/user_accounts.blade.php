@extends('layouts.app')

@section('title', 'My Account')

@section('content')
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-[#4A2C1D] mt-6 mb-4 border-b-2 border-[#7F5539] pb-2">
            My Account Profile
        </h1>

        {{-- Profile Information Card --}}
        <div class="bg-white shadow-xl rounded-xl p-6 mb-8 border-t-4 border-[#7F5539]">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Account Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-4">

                {{-- Full Name --}}
                <div>
                    <p class="text-sm font-medium text-gray-500">Full Name</p>
                    <p class="text-lg font-semibold text-gray-900">
                        {{ $user->first_name ?? 'N/A' }} {{ $user->last_name ?? 'N/A' }}
                    </p>
                </div>

                {{-- Email Address --}}
                <div>
                    <p class="text-sm font-medium text-gray-500">Email Address</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $user->email ?? 'N/A' }}</p>
                </div>

                {{-- Contact No --}}
                <div>
                    <p class="text-sm font-medium text-gray-500">Contact Number</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $user->contact_no ?? 'N/A' }}</p>
                </div>

                {{-- Address --}}
                <div class="md:col-span-2">
                    <p class="text-sm font-medium text-gray-500">Address</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $user->address ?? 'N/A' }}</p>
                </div>

                {{-- Date Joined --}}
                <div>
                    <p class="text-sm font-medium text-gray-500">Date Joined</p>
                    <p class="text-lg font-semibold text-gray-900">
                        {{ \Carbon\Carbon::parse($user->date_joined ?? now())->format('F d, Y') }}
                    </p>
                </div>

                {{-- Account Status --}}
                <div>
                    <p class="text-sm font-medium text-gray-500">Account Status</p>
                    <span
                        class="mt-2 inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        Verified
                    </span>
                </div>

                {{-- GCash QR Code (Owner only) --}}
@if ($guard === 'owner')
    <div class="md:col-span-2">
        <p class="text-sm font-medium text-gray-500 mb-2">GCash QR Codes</p>
        @php
            // Get QR codes as array - NO JSON DECODE NEEDED
            $qrCodes = [];
            if ($user->gcash_qr_code_img) {
                // Check if it's already an array (due to model casting)
                if (is_array($user->gcash_qr_code_img)) {
                    $qrCodes = $user->gcash_qr_code_img;
                } 
                // Check if it's a JSON string
                elseif (is_string($user->gcash_qr_code_img)) {
                    $decoded = json_decode($user->gcash_qr_code_img, true);
                    if ($decoded && is_array($decoded)) {
                        $qrCodes = $decoded;
                    } else {
                        // Single image stored as string
                        $qrCodes = [$user->gcash_qr_code_img];
                    }
                }
            }
        @endphp

        @if (count($qrCodes) > 0)
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-4">
                @foreach ($qrCodes as $index => $qrCodePath)
                    <div class="relative group">
                        @php
                            // Clean the path for display
                            $displayPath = str_replace('storage/app/public/', '', $qrCodePath);
                        @endphp
                        <img src="{{ asset('storage/app/public/' . $displayPath) }}"
                             class="w-full h-32 object-contain border border-gray-300 rounded-lg cursor-pointer hover:opacity-80 transition-opacity"
                             onclick="window.open('{{ asset('storage/app/public/' . $displayPath) }}', '_blank')"
                             alt="GCash QR Code {{ $index + 1 }}">
                        <!-- Delete button for each image -->
                        <button type="button"
                                onclick="removeQrCode('{{ $qrCodePath }}')"
                                class="absolute top-2 right-2 bg-red-500 text-white p-1 rounded-full opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        <div class="text-xs text-center mt-1 text-gray-600">QR Code {{ $index + 1 }}</div>
                    </div>
                @endforeach
            </div>
            <p class="text-sm text-gray-600 mt-2">
                You have {{ count($qrCodes) }} QR code(s) uploaded. 
                Scan any QR code to send payment via GCash.
            </p>
        @else
            <div class="flex items-center space-x-2 text-amber-600">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd" />
                </svg>
                <span class="text-sm font-medium">No GCash QR codes uploaded yet</span>
            </div>
        @endif
    </div>
@endif

                {{-- Regular Status (Customer specific) --}}
                @if ($guard === 'customer')
                    <div>
                        <p class="text-sm font-medium text-gray-500">Regular Customer Status</p>
                        @if (($user->regular ?? 0) == 1)
                            <span
                                class="mt-2 inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                Regular
                            </span>
                        @else
                            <span
                                class="mt-2 inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                Not Regular
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Update Profile Details Card --}}
        <div class="bg-white shadow-xl rounded-xl p-6 mb-8 border-t-4 border-[#7F5539]">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Update Profile Details</h2>

            @php
                // Route prefix setup
                $routePrefix = '';
                if ($guard === 'owner') {
                    $routePrefix = 'sub_one';
                } elseif ($guard === 'staff') {
                    $routePrefix = 'sub_two';
                } elseif ($guard === 'customer') {
                    $routePrefix = 'sub_three';
                }
            @endphp

            <form method="POST" action="{{ route($routePrefix . '.accounts.updateProfileDetails') }}"
                enctype="multipart/form-data" id="profileForm">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- First Name --}}
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                        <input type="text" name="first_name" id="first_name" required
                            value="{{ old('first_name', $user->first_name ?? '') }}"
                            class="block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-[#7F5539] focus:border-[#7F5539]">
                        @error('first_name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Last Name --}}
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                        <input type="text" name="last_name" id="last_name" required
                            value="{{ old('last_name', $user->last_name ?? '') }}"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-[#7F5539] focus:border-[#7F5539]">
                        @error('last_name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Contact Number --}}
                    <div>
                        <label for="contact_no" class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                        <input type="text" name="contact_no" id="contact_no" required
                            value="{{ old('contact_no', $user->contact_no ?? '') }}"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-[#7F5539] focus:border-[#7F5539]">
                        @error('contact_no')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Address --}}
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <input type="text" name="address" id="address" required
                            value="{{ old('address', $user->address ?? '') }}"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-[#7F5539] focus:border-[#7F5539]">
                        @error('address')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- GCash QR Code Upload (Owner only) --}}
@if ($guard === 'owner')
    <div class="md:col-span-2">
        <label for="gcash_qr_code_imgs" class="block text-sm font-medium text-gray-700 mb-1">
            GCash QR Code Images
        </label>
        <div class="flex items-center space-x-4">
            <!-- Change to array input and add multiple attribute -->
            <input type="file" 
                   name="gcash_qr_code_imgs[]" 
                   id="gcash_qr_code_imgs"
                   multiple
                   accept="image/jpeg,image/png,image/jpg,image/gif"
                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-[#7F5539] file:text-white hover:file:bg-[#4A2C1D]">

            @php
                // Get QR codes as array - NO JSON DECODE NEEDED
                $qrCodes = [];
                if ($user->gcash_qr_code_img) {
                    if (is_array($user->gcash_qr_code_img)) {
                        $qrCodes = $user->gcash_qr_code_img;
                    } elseif (is_string($user->gcash_qr_code_img)) {
                        $decoded = json_decode($user->gcash_qr_code_img, true);
                        if ($decoded && is_array($decoded)) {
                            $qrCodes = $decoded;
                        } else {
                            $qrCodes = [$user->gcash_qr_code_img];
                        }
                    }
                }
            @endphp
            @if (count($qrCodes) > 0)
                <div class="flex-shrink-0">
                    <span class="text-sm text-green-600 font-medium">✓ {{ count($qrCodes) }} QR code(s) uploaded</span>
                </div>
            @endif
        </div>
        <p class="mt-1 text-sm text-gray-500">
            Upload multiple clear images of your GCash QR codes (JPEG, PNG, JPG, GIF, max 2MB each, max 5 files)
        </p>
        <p class="mt-1 text-sm text-blue-600">
            You can select multiple files at once by holding Ctrl (Windows) or Command (Mac) while clicking.
        </p>
        
        <!-- Hidden input for delete functionality -->
        <input type="hidden" name="delete_qr_codes" id="delete_qr_codes">
        
        @error('gcash_qr_code_imgs')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
        @error('gcash_qr_code_imgs.*')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
        
        {{-- Preview of current QR codes --}}
        @if (count($qrCodes) > 0)
            <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                <p class="text-sm font-medium text-gray-700 mb-2">Current QR Codes (click to mark for deletion):</p>
                <div id="currentQrCodesContainer" class="flex flex-wrap gap-2">
                    @foreach ($qrCodes as $index => $qrCodePath)
                        @php
                            $displayPath = str_replace('storage/app/public/', '', $qrCodePath);
                        @endphp
                        <div class="relative group qr-code-item cursor-pointer" data-path="{{ $qrCodePath }}" onclick="markForDeletion('{{ $qrCodePath }}', this)" title="Click to toggle deletion">
                            <img src="{{ asset('storage/app/public/' . $displayPath) }}" 
                                 alt="GCash QR Code {{ $index + 1 }}"
                                 class="w-24 h-24 object-contain border border-gray-300 rounded hover:opacity-80 transition-opacity">
                            <div class="text-xs text-center mt-1 text-gray-600">Click to remove</div>
                            <div class="absolute inset-0 bg-red-100 bg-opacity-50 hidden flex items-center justify-center pointer-events-none">
                                <span class="text-red-600 font-bold">REMOVE</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endif
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit"
                        class="px-6 py-3 bg-[#7F5539] text-white font-semibold rounded-lg shadow-md hover:bg-[#4A2C1D] transition-colors focus:outline-none focus:ring-2 focus:ring-[#7F5539] focus:ring-offset-2">
                        Update Profile
                    </button>
                </div>
            </form>
        </div>

        {{-- Change Password Card --}}
        <div class="bg-white shadow-xl rounded-xl p-6 mb-6 border-t-4 border-gray-400">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Update Password</h2>

            {{-- FIX: Map the guard name to the correct route prefix --}}
            @php
                $routePrefix = '';
                if ($guard === 'owner') {
                    $routePrefix = 'sub_one';
                } elseif ($guard === 'staff') {
                    $routePrefix = 'sub_two';
                } elseif ($guard === 'customer') {
                    $routePrefix = 'sub_three';
                }
            @endphp

            <form method="POST" action="{{ route($routePrefix . '.accounts.updatePassword') }}">
                @csrf
                @method('PUT') {{-- Assuming you're using PUT or PATCH for updates --}}

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>

                        {{-- Password Input Field with Toggle Icon --}}
                        <div class="relative mt-1">
                            <input type="password" name="password" id="password" required
                                class="block w-full border border-gray-300 rounded-md shadow-sm p-3 pr-10 focus:ring-[#7F5539] focus:border-[#7F5539]">

                            {{-- Toggle Icon (Eye icon SVG) --}}
                            <span id="togglePassword"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer text-gray-400 hover:text-gray-600"
                                title="Toggle password visibility">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    {{-- Eye open path (Visible by default) --}}
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" id="eyeOpen" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    {{-- Eye slash path (Hidden by default, shown by JS) --}}
                                    <path id="eyeClosed" style="display:none;" stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.564-3.525M14.125 7.175A10.05 10.05 0 0112 5c-4.478 0-8.268 2.943-9.542 7a9.97 9.97 0 001.564 3.525m11.561-1.25M17.65 14.125A6.999 6.999 0 0112 19a6.999 6.999 0 01-5.65-2.875M12 10a2 2 0 100 4 2 2 0 000-4z" />
                                </svg>
                            </span>
                        </div>

                        {{-- Real-Time Validation List --}}
                        <div id="password-requirements" class="mt-2 text-sm space-y-1">
                            <p id="req-length" class="text-gray-500 flex items-center">
                                <span class="mr-2">&bull;</span> Minimum 12 characters
                            </p>
                            <p id="req-uppercase" class="text-gray-500 flex items-center">
                                <span class="mr-2">&bull;</span> At least one uppercase letter (A-Z)
                            </p>
                            <p id="req-special" class="text-gray-500 flex items-center">
                                <span class="mr-2">&bull;</span> At least one special character (!@#$...)
                            </p>
                            <p id="req-number" class="text-gray-500 flex items-center">
                                <span class="mr-2">&bull;</span> At least 4 numbers (0-9)
                            </p>
                        </div>

                        @error('password')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm
                            Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-[#7F5539] focus:border-[#7F5539]">

                        {{-- Real-Time Confirmation Match Status --}}
                        <div id="confirmation-requirements" class="mt-2 text-sm space-y-1">
                            <p id="req-match" class="text-gray-500 flex items-center">
                                <span class="mr-2">&bull;</span> Passwords match
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit"
                        class="px-6 py-3 bg-[#7F5539] text-white font-semibold rounded-lg shadow-md hover:bg-[#4A2C1D] transition-colors focus:outline-none focus:ring-2 focus:ring-[#7F5539] focus:ring-offset-2">
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Floating Congratulations Modal --}}
    @if ($guard === 'customer' && ($user->regular ?? 0) == 1 && $isEligibleForCongrats)
        <div id="floatingCongratsModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 transition-opacity duration-300">
            <div
                class="bg-gradient-to-br from-[#F8F4F0] to-[#E6CCB2] rounded-2xl shadow-2xl p-8 mx-4 max-w-md w-full transform transition-all duration-500 scale-95 hover:scale-100 border-4 border-[#7F5539]">
                {{-- Close Button --}}
                <button id="closeCongratsBtn"
                    class="absolute top-4 right-4 text-[#7F5539] hover:text-[#4A2C1D] transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>

                {{-- Celebration Content --}}
                <div class="text-center">
                    {{-- Animated Icon --}}
                    <div class="mb-6 animate-bounce">
                        <div class="relative inline-flex">
                            <div
                                class="w-20 h-20 bg-gradient-to-r from-[#7F5539] to-[#4A2C1D] rounded-full flex items-center justify-center shadow-lg">
                                <svg class="w-10 h-10 text-[#E6CCB2]" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="absolute -top-2 -right-2">
                                <span class="flex h-6 w-6">
                                    <span
                                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#7F5539] opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-6 w-6 bg-[#4A2C1D]"></span>
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Title --}}
                    <h2 class="text-3xl font-bold text-[#4A2C1D] mb-4">
                        Congratulations! 🎉
                    </h2>

                    {{-- Message --}}
                    <div class="space-y-3 mb-6">
                        <p class="text-lg font-semibold text-[#7F5539]">
                            You've Achieved Regular Status!
                        </p>
                        <p class="text-[#5D4037] leading-relaxed">
                            Welcome to our exclusive circle of regular customers!
                            Your loyalty for 30+ days has been recognized automatically.
                        </p>
                        <p class="text-sm text-[#7F5539] font-medium">
                            🗓️ Thank you for being with us for over a month!
                        </p>
                        <p class="text-xs text-[#9C6644]">
                            Your status was automatically updated to "Regular"
                        </p>
                    </div>

                    {{-- Action Button --}}
                    <button id="celebrateAgainBtn"
                        class="w-full bg-gradient-to-r from-[#7F5539] to-[#4A2C1D] text-[#E6CCB2] font-bold py-3 px-6 rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300 flex items-center justify-center hover:from-[#4A2C1D] hover:to-[#7F5539]">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M10 3.22l-.61-.6a5.5 5.5 0 00-7.78 7.77L10 18.78l8.39-8.4a5.5 5.5 0 00-7.78-7.77l-.61.61z" />
                        </svg>
                        Celebrate Again!
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div id="toast-container" class="fixed top-[66px] right-6 z-[9999] max-w-xs flex flex-col gap-3"></div>
@endsection

{{-- QR Code Management Script --}}
<script>
    // Array to track images marked for deletion
    let imagesToDelete = [];
    
    // Function to mark an image for deletion
    function markForDeletion(imagePath, element) {
        // Element is now the container div due to onclick move
        const container = element;
        // Select the image inside this container
        const img = container.querySelector('img'); 
        // Select the overlay inside this container
        const overlay = container.querySelector('.absolute');

        if (container.classList.contains('marked-for-deletion')) {
            // Unmark for deletion
            container.classList.remove('marked-for-deletion');
            overlay.classList.add('hidden');
            img.classList.remove('opacity-50', 'border-red-500'); // Apply styles to img
            img.classList.add('hover:opacity-80');
            
            // Remove from deletion array
            imagesToDelete = imagesToDelete.filter(path => path !== imagePath);
        } else {
            // Mark for deletion
            container.classList.add('marked-for-deletion');
            overlay.classList.remove('hidden');
            img.classList.add('opacity-50', 'border-red-500'); // Apply styles to img
            img.classList.remove('hover:opacity-80');
            
            // Add to deletion array
            if (!imagesToDelete.includes(imagePath)) {
                imagesToDelete.push(imagePath);
            }
        }
        
        updateDeleteInput();
    }
    
    // Function to remove QR code from display (in profile section)
    function removeQrCode(imagePath) {
        if (confirm('Are you sure you want to remove this QR code?')) {
            // Add to deletion array
            if (!imagesToDelete.includes(imagePath)) {
                imagesToDelete.push(imagePath);
            }
            updateDeleteInput();
            
            // Find and mark corresponding image in form for deletion
            const formImage = document.querySelector(`.qr-code-item[data-path="${imagePath}"]`);
            if (formImage) {
                // Manually apply styles since we're calling this from outside
                const img = formImage.querySelector('img');
                const overlay = formImage.querySelector('.absolute');
                
                formImage.classList.add('marked-for-deletion');
                img.classList.add('opacity-50', 'border-red-500');
                img.classList.remove('hover:opacity-80');
                overlay.classList.remove('hidden');
            }
            
            alert('QR code marked for deletion. Submit the form to complete removal.');
        }
    }
    
    // Update the hidden input with images to delete
    function updateDeleteInput() {
        const deleteInput = document.getElementById('delete_qr_codes');
        deleteInput.value = JSON.stringify(imagesToDelete);
    }
    
    // File input validation
    document.getElementById('gcash_qr_code_imgs')?.addEventListener('change', function(e) {
        const files = e.target.files;
        if (files.length > 5) {
            alert('You can only upload a maximum of 5 files.');
            this.value = '';
        }
        
        // Validate file sizes
        let totalSize = 0;
        const maxSize = 2 * 1024 * 1024; // 2MB in bytes
        
        for (let i = 0; i < files.length; i++) {
            if (files[i].size > maxSize) {
                alert(`File "${files[i].name}" exceeds 2MB limit.`);
                this.value = '';
                return;
            }
            totalSize += files[i].size;
        }
        
        if (totalSize > 10 * 1024 * 1024) { // 10MB total limit
            alert('Total file size exceeds 10MB limit.');
            this.value = '';
        }
    });
    
    // Form submission validation
    document.getElementById('profileForm')?.addEventListener('submit', function(e) {
        const fileInput = document.getElementById('gcash_qr_code_imgs');
        if (fileInput && fileInput.files.length + imagesToDelete.length > 5) {
            e.preventDefault();
            alert('Cannot have more than 5 QR codes total (including new uploads minus deletions).');
            return false;
        }
    });
</script>

{{-- Confetti and Floating Modal Script --}}
@if ($guard === 'customer' && ($user->regular ?? 0) == 1)
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script>
        // Add this to your existing JavaScript
        function checkRegularStatus() {
            fetch('{{ $guard === 'customer' ? route('sub_three.accounts.checkRegularStatus') : '' }}')
                .then(response => response.json())
                .then(data => {
                    if (data.regular && data.eligible_for_congrats && !document.getElementById(
                            'floatingCongratsModal')) {
                        // Reload the page to show the congratulations modal
                        location.reload();
                    }
                })
                .catch(error => console.error('Error checking regular status:', error));
        }

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('floatingCongratsModal');
            if (modal) {
                const closeBtn = document.getElementById('closeCongratsBtn');
                const celebrateBtn = document.getElementById('celebrateAgainBtn');

                // Show modal with animation
                setTimeout(() => {
                    modal.classList.remove('opacity-0');
                    modal.classList.add('opacity-100');
                    modal.querySelector('.scale-95').classList.remove('scale-95');
                    modal.querySelector('.scale-95').classList.add('scale-100');

                    // Trigger confetti when modal appears
                    triggerConfetti();
                }, 1000);

                // Close modal function
                function closeModal() {
                    modal.classList.remove('opacity-100');
                    modal.classList.add('opacity-0');
                    setTimeout(() => {
                        modal.style.display = 'none';
                    }, 300);
                }

                // Event listeners
                closeBtn.addEventListener('click', closeModal);

                // Close modal when clicking outside
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal();
                    }
                });

                // Celebrate again button
                celebrateBtn.addEventListener('click', function() {
                    triggerConfetti();

                    // Add button feedback
                    celebrateBtn.classList.add('animate-pulse');
                    setTimeout(() => {
                        celebrateBtn.classList.remove('animate-pulse');
                    }, 600);
                });

                // Enhanced confetti function with coffee-themed colors
                function triggerConfetti() {
                    // Main burst with coffee colors
                    confetti({
                        particleCount: 150,
                        spread: 100,
                        origin: {
                            y: 0.6
                        },
                        colors: ['#7F5539', '#4A2C1D', '#9C6644', '#B08968', '#E6CCB2', '#D4A574'],
                        scalar: 1.2
                    });

                    // Left side burst
                    setTimeout(() => {
                        confetti({
                            particleCount: 80,
                            angle: 60,
                            spread: 80,
                            origin: {
                                x: 0.1
                            },
                            colors: ['#7F5539', '#4A2C1D', '#9C6644'],
                            scalar: 1.1
                        });
                    }, 200);

                    // Right side burst
                    setTimeout(() => {
                        confetti({
                            particleCount: 80,
                            angle: 120,
                            spread: 80,
                            origin: {
                                x: 0.9
                            },
                            colors: ['#B08968', '#E6CCB2', '#D4A574'],
                            scalar: 1.1
                        });
                    }, 400);

                    // Final burst
                    setTimeout(() => {
                        confetti({
                            particleCount: 50,
                            spread: 100,
                            scalar: 1.5,
                            colors: ['#7F5539', '#4A2C1D', '#9C6644'],
                            origin: {
                                y: 0.8
                            }
                        });
                    }, 600);
                }

                // Check regular status every minute
                setInterval(checkRegularStatus, 60000);
            }
        });
    </script>

    <style>
        /* Custom animations */
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        
        /* QR Code deletion styles */
        .qr-code-item.marked-for-deletion {
            position: relative;
        }
        
        .qr-code-item.marked-for-deletion img {
            opacity: 0.5;
            border-color: #ef4444;
        }
        
        .qr-code-item.marked-for-deletion .absolute {
            display: flex !important;
            background-color: rgba(254, 202, 202, 0.7);
        }
    </style>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('password_confirmation');
        const toggleIcon = document.getElementById('togglePassword');
        const eyeOpen = document.getElementById('eyeOpen');
        const eyeClosed = document.getElementById('eyeClosed');
        const matchRequirement = document.getElementById('req-match');

        // --- Password Visibility Toggle Logic ---
        if (toggleIcon && passwordInput) {
            toggleIcon.addEventListener('click', function() {
                // Toggle the type attribute
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Toggle the SVG icon paths
                if (type === 'text') {
                    if (eyeOpen) eyeOpen.style.display = 'none';
                    if (eyeClosed) eyeClosed.style.display = 'inline';
                } else {
                    if (eyeOpen) eyeOpen.style.display = 'inline';
                    if (eyeClosed) eyeClosed.style.display = 'none';
                }
            });
        }

        // --- Real-Time Validation Logic (New Password Complexity) ---
        const requirements = {
            length: {
                element: document.getElementById('req-length'),
                regex: /^.{12,}$/,
                text: "Minimum 12 characters"
            },
            uppercase: {
                element: document.getElementById('req-uppercase'),
                regex: /[A-Z]/,
                text: "At least one uppercase letter (A-Z)"
            },
            special: {
                element: document.getElementById('req-special'),
                regex: /[!@#$%^&*(),.?":{}|<>]/,
                text: "At least one special character (!@#$...)"
            },
            number: {
                element: document.getElementById('req-number'),
                // Matches the PHP regex /(\D*\d){4,}/ - ensuring at least four digits are present
                regex: /(\D*\d){4,}/,
                text: "At least 4 numbers (0-9)"
            }
        };

        function updateValidation(value) {
            for (const key in requirements) {
                const req = requirements[key];
                if (!req.element) continue;
                
                const passed = req.regex.test(value);

                // Update text and color based on passing the requirement
                if (passed) {
                    req.element.classList.remove('text-gray-500', 'text-red-500');
                    req.element.classList.add('text-green-600');
                    req.element.querySelector('span').innerHTML = '&#x2713;'; // Checkmark
                } else {
                    req.element.classList.remove('text-green-600', 'text-red-500');
                    req.element.classList.add('text-gray-500');
                    req.element.querySelector('span').innerHTML = '&bull;'; // Bullet
                }

                // If the input is not empty and validation fails, highlight failure
                if (value.length > 0 && !passed) {
                    req.element.classList.remove('text-gray-500');
                    req.element.classList.add('text-red-500');
                }
            }
            // CRUCIAL: Also update the confirmation field validation whenever the new password changes
            if (confirmInput) {
                updateConfirmationValidation(confirmInput.value);
            }
        }

        // --- Real-Time Validation Logic (Confirm Password Match) ---
        function updateConfirmationValidation(confirmValue) {
            if (!matchRequirement || !passwordInput) return;
            
            const originalValue = passwordInput.value;
            const isNotEmpty = confirmValue.length > 0;
            const passed = isNotEmpty && confirmValue === originalValue;

            // If both are empty, reset to default gray bullet
            if (originalValue.length === 0 && confirmValue.length === 0) {
                matchRequirement.classList.remove('text-green-600', 'text-red-500');
                matchRequirement.classList.add('text-gray-500');
                matchRequirement.querySelector('span').innerHTML = '&bull;';
                return;
            }

            if (passed) {
                matchRequirement.classList.remove('text-gray-500', 'text-red-500');
                matchRequirement.classList.add('text-green-600');
                matchRequirement.querySelector('span').innerHTML = '&#x2713;'; // Checkmark (✓)
            } else {
                matchRequirement.classList.remove('text-green-600', 'text-gray-500');
                matchRequirement.classList.add('text-red-500');
                matchRequirement.querySelector('span').innerHTML = '&#x2715;'; // Cross mark (✕)
            }
        }

        // Event listener for the confirmation field
        if (confirmInput) {
            confirmInput.addEventListener('input', function(e) {
                updateConfirmationValidation(e.target.value);
            });
        }

        if (passwordInput) {
            passwordInput.addEventListener('input', function(e) {
                updateValidation(e.target.value);
            });

            // Run validation once on load for both fields
            updateValidation(passwordInput.value);
        }
        
        if (confirmInput) {
            updateConfirmationValidation(confirmInput.value);
        }
    });
</script>