@extends('layouts.app')

@section('title', 'Reset Your Password')

@section('content')
    <div class="flex flex-col md:flex-row items-center justify-center bg-gray-50 min-h-screen py-8">
        <div class="bg-white shadow-md rounded-lg overflow-hidden flex flex-col md:flex-row w-full max-w-4xl">

            <!-- Left: Image Section -->
            <div
                class="w-full md:w-1/2 bg-[#4A2C1D] 
                       h-auto sm:h-40 md:h-auto 
                       flex flex-row md:flex-col items-center justify-center 
                       text-center md:text-center px-4 sm:px-6 py-4 sm:py-6">

                <!-- Logo -->
                <div
                    class="bg-white rounded-full w-12 h-12 sm:w-14 sm:h-14 
            flex items-center justify-center overflow-hidden
            mb-0 md:mb-4 mr-3 md:mr-0">
                    <img src="{{ asset('storage/logo.png') }}" alt="Logo" class="w-full h-full object-cover">
                </div>

                <!-- Main text -->
                <div class="flex flex-col items-start md:items-center">
                    <p class="text-base sm:text-lg font-medium text-white opacity-90">Welcome back to</p>
                    <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-white">Linkud Hub</h2>
                </div>
            </div>

            <!-- Right: Form Section -->
            <div class="w-full md:w-1/2 p-8 relative">

                <!-- X Button -->
                <a href="{{ route('showLoginForm') }}"
                    class="absolute top-3 right-3 text-[#7F5539] hover:bg-[#4A2C1D] hover:text-white rounded p-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </a>

                <h1 class="text-2xl font-bold mb-2 mt-6 text-center text-[#4A2C1D]">Reset Your Password</h1>

                <!-- Sub text -->
                <p class="text-sm md:text-base text-center text-[#4A2C1D] opacity-80 mb-4">
                    Hello {{ $userName ?? 'User' }}, please enter your new password below.
                </p>

                <hr class="mb-6 border-[#4A2C1D]">

                <!-- Error Messages -->
                @if($errors->any())
                    <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 rounded">
                        <p class="font-medium">Please fix the following errors:</p>
                        <ul class="mt-1 list-disc list-inside text-sm">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf

                    <!-- Hidden fields for token and email -->
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="email" value="{{ $email }}">

                    <!-- New Password -->
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-[#4A2C1D]">New Password</label>
                        <div class="relative">
                            <input type="password" name="password" id="password"
                                class="w-full border-2 rounded px-3 py-2 mt-1 focus:outline-none focus:ring-1
                                       @error('password') border-red-500 focus:border-red-500 focus:ring-red-500 
                                       @else border-[#7F5539] focus:border-[#4A2C1D] focus:ring-[#4A2C1D] @enderror"
                                required autofocus>
                            <button type="button" id="togglePassword"
                                class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hidden">
                                <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5 hidden">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.243 4.243l-4.243-4.243" />
                                </svg>
                            </button>
                        </div>

                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        <!-- Password Validation Rules -->
                        <div id="password-rules" class="mt-2 text-xs text-gray-500 space-y-1 hidden">
                            <p id="rule-length" class="flex items-center transition-colors duration-200">
                                <svg class="w-4 h-4 mr-1.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <circle cx="10" cy="10" r="3"></circle>
                                </svg>
                                At least 12 characters
                            </p>
                            <p id="rule-upper" class="flex items-center transition-colors duration-200">
                                <svg class="w-4 h-4 mr-1.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <circle cx="10" cy="10" r="3"></circle>
                                </svg>
                                At least 1 uppercase letter
                            </p>
                            <p id="rule-special" class="flex items-center transition-colors duration-200">
                                <svg class="w-4 h-4 mr-1.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <circle cx="10" cy="10" r="3"></circle>
                                </svg>
                                At least 1 special character (!@#$...)
                            </p>
                            <p id="rule-number" class="flex items-center transition-colors duration-200">
                                <svg class="w-4 h-4 mr-1.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <circle cx="10" cy="10" r="3"></circle>
                                </svg>
                                At least 4 numbers
                            </p>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-6">
                        <label for="password_confirmation" class="block text-sm font-medium text-[#4A2C1D]">Confirm New Password</label>
                        <div class="relative">
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                class="w-full border-2 rounded px-3 py-2 mt-1 focus:outline-none focus:ring-1
                                       @error('password') border-red-500 focus:border-red-500 focus:ring-red-500 
                                       @else border-[#7F5539] focus:border-[#4A2C1D] focus:ring-[#4A2C1D] @enderror"
                                required>
                            <button type="button" id="togglePasswordConfirmation"
                                class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hidden">
                                <svg id="eyeOpenConfirmation" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <svg id="eyeClosedConfirmation" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 hidden">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.243 4.243l-4.243-4.243" />
                                </svg>
                            </button>
                        </div>

                        <!-- Password Match Rule -->
                        <div id="password-match-rule" class="mt-2 text-xs text-gray-500 space-y-1 hidden">
                            <p id="rule-match" class="flex items-center transition-colors duration-200">
                                <svg class="w-4 h-4 mr-1.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <circle cx="10" cy="10" r="3"></circle>
                                </svg>
                                Passwords match
                            </p>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="mt-4">
                        <button type="submit" id="submitButton"
                            class="bg-[#7F5539] w-full text-white font-semibold px-4 py-2 rounded hover:bg-[#4A2C1D] transition duration-150">
                            Reset Password
                        </button>
                    </div>
                </form>

                <!-- Back to Login -->
                <div class="mt-4 text-center">
                    <p class="text-sm text-gray-600">
                        Remember your password?
                        <a href="{{ route('showLoginForm') }}"
                            class="text-[#7F5539] hover:text-[#4A2C1D] font-medium underline">
                            Back to Login
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password toggle functionality
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');
        const eyeOpen = document.getElementById('eyeOpen');
        const eyeClosed = document.getElementById('eyeClosed');

        const passwordConfirmationInput = document.getElementById('password_confirmation');
        const togglePasswordConfirmation = document.getElementById('togglePasswordConfirmation');
        const eyeOpenConfirmation = document.getElementById('eyeOpenConfirmation');
        const eyeClosedConfirmation = document.getElementById('eyeClosedConfirmation');

        // Function to toggle main password visibility
        function toggleMainPasswordVisibility() {
            if (passwordInput) {
                const isHidden = passwordInput.type === 'password';
                passwordInput.type = isHidden ? 'text' : 'password';
                if (eyeOpen) eyeOpen.classList.toggle('hidden', isHidden);
                if (eyeClosed) eyeClosed.classList.toggle('hidden', !isHidden);
            }
        }

        // Function to toggle main eye icon visibility based on input
        function toggleMainEyeIconVisibility() {
            if (passwordInput && togglePassword) {
                if (passwordInput.value.length > 0) {
                    togglePassword.classList.remove('hidden');
                } else {
                    togglePassword.classList.add('hidden');
                    // Reset to default state
                    passwordInput.type = 'password';
                    if (eyeOpen) eyeOpen.classList.remove('hidden');
                    if (eyeClosed) eyeClosed.classList.add('hidden');
                }
            }
        }

        // Function to toggle confirmation password visibility
        function toggleConfirmPasswordVisibility() {
            if (passwordConfirmationInput) {
                const isHidden = passwordConfirmationInput.type === 'password';
                passwordConfirmationInput.type = isHidden ? 'text' : 'password';
                if (eyeOpenConfirmation) eyeOpenConfirmation.classList.toggle('hidden', isHidden);
                if (eyeClosedConfirmation) eyeClosedConfirmation.classList.toggle('hidden', !isHidden);
            }
        }

        // Function to toggle confirmation eye icon visibility based on input
        function toggleConfirmEyeIconVisibility() {
            if (passwordConfirmationInput && togglePasswordConfirmation) {
                if (passwordConfirmationInput.value.length > 0) {
                    togglePasswordConfirmation.classList.remove('hidden');
                } else {
                    togglePasswordConfirmation.classList.add('hidden');
                    // Reset to default state
                    passwordConfirmationInput.type = 'password';
                    if (eyeOpenConfirmation) eyeOpenConfirmation.classList.remove('hidden');
                    if (eyeClosedConfirmation) eyeClosedConfirmation.classList.add('hidden');
                }
            }
        }

        // Event listeners
        if (togglePassword) {
            togglePassword.addEventListener('click', toggleMainPasswordVisibility);
        }
        if (passwordInput) {
            passwordInput.addEventListener('input', toggleMainEyeIconVisibility);
        }
        if (togglePasswordConfirmation) {
            togglePasswordConfirmation.addEventListener('click', toggleConfirmPasswordVisibility);
        }
        if (passwordConfirmationInput) {
            passwordConfirmationInput.addEventListener('input', toggleConfirmEyeIconVisibility);
        }

        // Password Validation Helpers
        const svgIcon = {
            neutral: '<circle cx="10" cy="10" r="3"></circle>',
            valid: '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>',
            invalid: '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>'
        };

        function updateRuleUI(element, isValid) {
            const svg = element.querySelector('svg');
            if (isValid) {
                element.classList.remove('text-gray-500', 'text-red-600');
                element.classList.add('text-green-600');
                svg.innerHTML = svgIcon.valid;
            } else {
                element.classList.remove('text-gray-500', 'text-green-600');
                element.classList.add('text-red-600');
                svg.innerHTML = svgIcon.invalid;
            }
        }

        function resetRuleUI(element) {
            element.classList.remove('text-green-600', 'text-red-600');
            element.classList.add('text-gray-500');
            element.querySelector('svg').innerHTML = svgIcon.neutral;
        }

        // Password strength and match validation
        function validatePasswords() {
            const password = passwordInput.value;
            const confirmPassword = passwordConfirmationInput.value;
            const submitButton = document.getElementById('submitButton');

            let rulesValid = {
                length: false,
                upper: false,
                special: false,
                number: false,
                match: false
            };

            // Get rule elements
            const ruleLength = document.getElementById('rule-length');
            const ruleUpper = document.getElementById('rule-upper');
            const ruleSpecial = document.getElementById('rule-special');
            const ruleNumber = document.getElementById('rule-number');
            const ruleMatch = document.getElementById('rule-match');
            const matchRuleContainer = document.getElementById('password-match-rule');
            const passwordRulesContainer = document.getElementById('password-rules');

            // Validate Password Rules
            if (password.length > 0) {
                passwordRulesContainer.classList.remove('hidden');

                // Rule 1: Length
                rulesValid.length = password.length >= 12;
                updateRuleUI(ruleLength, rulesValid.length);

                // Rule 2: Uppercase
                rulesValid.upper = /[A-Z]/.test(password);
                updateRuleUI(ruleUpper, rulesValid.upper);

                // Rule 3: Special Character
                rulesValid.special = /[!@#$%^&*(),.?":{}|<>]/.test(password);
                updateRuleUI(ruleSpecial, rulesValid.special);

                // Rule 4: Numbers
                const numberCount = (password.match(/\d/g) || []).length;
                rulesValid.number = numberCount >= 4;
                updateRuleUI(ruleNumber, rulesValid.number);
            } else {
                passwordRulesContainer.classList.add('hidden');
                resetRuleUI(ruleLength);
                resetRuleUI(ruleUpper);
                resetRuleUI(ruleSpecial);
                resetRuleUI(ruleNumber);
                rulesValid.length = false;
                rulesValid.upper = false;
                rulesValid.special = false;
                rulesValid.number = false;
            }

            // Validate Password Match
            if (confirmPassword.length > 0) {
                matchRuleContainer.classList.remove('hidden');

                // Check match
                rulesValid.match = (password === confirmPassword);
                updateRuleUI(ruleMatch, rulesValid.match);

                // Update confirm input border
                if (rulesValid.match) {
                    passwordConfirmationInput.classList.remove('border-red-500');
                    passwordConfirmationInput.classList.add('border-green-600');
                } else {
                    passwordConfirmationInput.classList.remove('border-green-600');
                    passwordConfirmationInput.classList.add('border-red-500');
                }
            } else {
                matchRuleContainer.classList.add('hidden');
                resetRuleUI(ruleMatch);
                passwordConfirmationInput.classList.remove('border-red-500', 'border-green-600');
                rulesValid.match = false;
                if (password.length === 0) {
                    rulesValid.match = true;
                }
            }

            // Update input borders based on validation
            const allPasswordRulesValid = rulesValid.length && rulesValid.upper && rulesValid.special && rulesValid.number;
            
            if (password.length > 0) {
                if (allPasswordRulesValid) {
                    passwordInput.classList.remove('border-red-500');
                    passwordInput.classList.add('border-green-600');
                } else {
                    passwordInput.classList.remove('border-green-600');
                    passwordInput.classList.add('border-red-500');
                }
            } else {
                passwordInput.classList.remove('border-red-500', 'border-green-600');
            }

            // Enable/disable submit button based on validation
            if (submitButton) {
                const allValid = allPasswordRulesValid && rulesValid.match;
                if (allValid) {
                    submitButton.disabled = false;
                    submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
                } else {
                    submitButton.disabled = true;
                    submitButton.classList.add('opacity-50', 'cursor-not-allowed');
                }
            }
        }

        // Real-time validation
        if (passwordInput) {
            passwordInput.addEventListener('input', validatePasswords);
        }
        if (passwordConfirmationInput) {
            passwordConfirmationInput.addEventListener('input', validatePasswords);
        }

        // Initial state
        document.addEventListener('DOMContentLoaded', function() {
            // Check initial eye icon state on load
            toggleMainEyeIconVisibility();
            toggleConfirmEyeIconVisibility();

            // Run validation on load
            if (passwordInput && passwordConfirmationInput) {
                validatePasswords();
            }
        });
    </script>
@endsection