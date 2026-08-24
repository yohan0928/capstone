@extends('layouts.app')

@section('title', 'Login')

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

                    <!-- Responsive optimized image -->
                    <picture>
                        <!-- WebP format for modern browsers -->
                        <source
                            srcset="{{ asset('storage/logo-84.webp') }} 1x,
                                    {{ asset('storage/logo-168.webp') }} 2x"
                            type="image/webp">

                        <!-- PNG fallback for older browsers -->
                        <source
                            srcset="{{ asset('storage/logo-84.png') }} 1x,
                                    {{ asset('storage/logo-168.png') }} 2x"
                            type="image/png">

                        <!-- Final fallback with proper attributes -->
                        <img src="{{ asset('storage/logo-84.png') }}" alt="Linkud Hub Logo" class="w-full h-full object-cover"
                            width="84" height="84" loading="lazy" decoding="async">
                    </picture>
                </div>

                <!-- Main text -->
                <div class="flex flex-col items-start md:items-center">
                    <p class="text-base sm:text-lg font-medium text-white opacity-90">Welcome to</p>
                    <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-white">Linkud Hub</h2>
                </div>
            </div>

            <!-- Right: Form Section -->
            <div class="w-full md:w-1/2 p-8 relative">
                <!-- X Button -->
                <a href="{{ route('welcome') }}"
                    class="absolute top-3 right-3 text-[#7F5539] hover:bg-[#4A2C1D] hover:text-white rounded p-1"
                    aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </a>

                <h1 class="text-2xl font-bold mb-2 mt-6 text-center text-[#4A2C1D]">Sign in</h1>

                <!-- Sub text moved here -->
                <p class="text-sm md:text-base text-center text-[#4A2C1D] opacity-80 mb-4">
                    Sign in to continue your journey with Linkud Hub.
                </p>

                <hr class="mb-6 border-[#4A2C1D]">

                <!-- Display General Errors -->
                @if ($errors->has('login_error'))
                    <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 rounded">
                        <p class="font-medium">{{ $errors->first('login_error') }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('submitLogin') }}" id="loginForm">
                    @csrf

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="block text-sm font-medium text-[#4A2C1D]">Email</label>
                        <input type="email" name="email" id="email"
                            class="w-full border-2 rounded px-3 py-2 mt-1 focus:outline-none focus:ring-1 
                                   @error('email') border-red-500 focus:border-red-500 focus:ring-red-500 
                                   @else border-[#7F5539] focus:border-[#4A2C1D] focus:ring-[#4A2C1D] @enderror"
                            value="{{ old('email') }}" autofocus>
                        @error('email')
                            <p class="mt-1 text-sm text-red-600 server-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-[#4A2C1D]">Password</label>

                        <div class="relative mt-1">
                            <input type="password" name="password" id="password"
                                class="w-full border-2 rounded px-3 py-2 focus:outline-none focus:ring-1 pr-10
                   @error('password') border-red-500 focus:border-red-500 focus:ring-red-500 
                   @else border-[#7F5539] focus:border-[#4A2C1D] focus:ring-[#4A2C1D] @enderror">

                            <!-- Eye Icon - Now centered properly within the input field -->
                            <button type="button" id="togglePassword" tabindex="-1"
                                class="absolute top-1/2 right-3 transform -translate-y-1/2 flex items-center text-[#7F5539] hover:text-[#4A2C1D] hidden">
                                <!-- Eye Closed Icon -->
                                <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>

                                <!-- Eye Open Icon -->
                                <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor" class="w-5 h-5 hidden">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                            </button>
                        </div>

                        @error('password')
                            <p class="mt-1 text-sm text-red-600 server-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit -->
                    <div class="mt-4">
                        <button type="submit"
                            class="bg-[#7F5539] w-full text-white font-semibold px-4 py-2 rounded hover:bg-[#4A2C1D]">
                            Sign in
                        </button>
                    </div>
                </form>

                <!-- "Or" Divider -->
                <div class="flex items-center my-4">
                    <hr class="flex-grow border-t border-[#4A2C1D]">
                    <span class="mx-4 text-sm font-medium text-[#4A2C1D]">or</span>
                    <hr class="flex-grow border-t border-[#4A2C1D]">
                </div>

                <!-- Google Login Button -->
                <div>
                    <a href="{{ route('redirectToGoogleLogin') }}"
                        class="flex items-center justify-center w-full bg-white border-2 border-gray-300 rounded px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:border-gray-400 transition duration-150 ease-in-out">
                        <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24">
                            <path fill="#4285F4"
                                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                            <path fill="#34A853"
                                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                            <path fill="#FBBC05"
                                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                            <path fill="#EA4335"
                                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                        </svg>
                        Continue with Google
                    </a>
                </div>

                <!-- Links Section -->
                <div class="mt-4 text-center">
                    <!-- On larger screens: Side by side -->
                    <div class="hidden md:flex justify-between items-center text-sm text-gray-600">
                        <!-- Forgot Password Button -->
                        <div>
                            <a href="{{ route('password.request') }}"
                                class="text-[#7F5539] hover:text-[#4A2C1D] font-medium hover:underline">
                                Forgot Password?
                            </a>
                        </div>

                        <!-- Register Link -->
                        <div>
                            <p class="inline text-gray-600">
                                Don't have an account?
                                <a href="{{ route('showCustomerRegistration') }}"
                                    class="text-[#7F5539] hover:text-[#4A2C1D] font-medium underline ml-1">
                                    Sign up
                                </a>
                            </p>
                        </div>
                    </div>

                    <!-- On small screens: Stacked -->
                    <div class="flex flex-col space-y-3 md:hidden text-sm text-gray-600">
                        <!-- Forgot Password Button -->
                        <div class="text-center mb-4">
                            <a href="{{ route('password.request') }}"
                                class="text-[#7F5539] hover:text-[#4A2C1D] font-medium hover:underline">
                                Forgot Password?
                            </a>
                        </div>

                        <!-- Register Link -->
                        <div class="text-center">
                            <p class="text-gray-600">
                                Don't have an account?
                                <a href="{{ route('showCustomerRegistration') }}"
                                    class="text-[#7F5539] hover:text-[#4A2C1D] font-medium underline ml-1">
                                    Sign up
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const toggleButton = document.getElementById('togglePassword');
        const eyeOpen = document.getElementById('eyeOpen');
        const eyeClosed = document.getElementById('eyeClosed');
        const emailInput = document.getElementById('email');

        // Function to toggle password visibility
        function togglePasswordVisibility() {
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            eyeOpen.classList.toggle('hidden', isHidden);
            eyeClosed.classList.toggle('hidden', !isHidden);
        }

        // Function to toggle eye icon visibility based on input value
        function toggleEyeIconVisibility() {
            if (passwordInput.value.length > 0) {
                toggleButton.classList.remove('hidden');
            } else {
                toggleButton.classList.add('hidden');
                // Reset to default state (password hidden, eye open visible)
                passwordInput.type = 'password';
                eyeOpen.classList.remove('hidden');
                eyeClosed.classList.add('hidden');
            }
        }

        // Function to clear error states when user starts typing
        function clearErrorOnInput(fieldId) {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('input', function() {
                    // Check if there's a server error for this field
                    const errorElement = this.nextElementSibling;
                    const hasServerError = errorElement &&
                        errorElement.classList.contains('server-error') &&
                        errorElement.textContent.trim() !== '';

                    if (hasServerError) {
                        // Remove red border classes
                        this.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
                        // Add default styling
                        this.classList.add('border-[#7F5539]', 'focus:border-[#4A2C1D]', 'focus:ring-[#4A2C1D]');

                        // Hide the error message
                        errorElement.style.display = 'none';

                        // For password field, also show eye icon if there's text
                        if (fieldId === 'password' && this.value.length > 0) {
                            toggleButton.classList.remove('hidden');
                        }
                    }
                });
            }
        }

        // Function to preserve server-side error styling
        function preserveServerErrors() {
            // Check for email server error
            const emailError = document.querySelector('#email ~ .server-error');
            if (emailError && emailError.textContent.trim() !== '') {
                // Email already has red border from Blade classes
                // Just ensure it's visible
                emailError.style.display = 'block';
            }

            // Check for password server error
            const passwordError = document.querySelector('#password ~ .server-error');
            if (passwordError && passwordError.textContent.trim() !== '') {
                // Password already has red border from Blade classes
                // Ensure error is visible and hide eye icon if password is empty
                passwordError.style.display = 'block';

                // If password is empty with server error, hide eye icon
                if (passwordInput.value.length === 0) {
                    toggleButton.classList.add('hidden');
                } else {
                    toggleButton.classList.remove('hidden');
                }
            }
        }

        // Event listeners
        if (toggleButton) {
            toggleButton.addEventListener('click', togglePasswordVisibility);
        }

        if (passwordInput) {
            passwordInput.addEventListener('input', toggleEyeIconVisibility);
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Preserve server-side errors on load
            preserveServerErrors();

            // Setup error clearing for input fields
            clearErrorOnInput('email');
            clearErrorOnInput('password');

            // Initialize eye icon state
            toggleEyeIconVisibility();

            // Clear general login error when user starts typing
            const loginForm = document.getElementById('loginForm');
            if (loginForm) {
                loginForm.addEventListener('input', function() {
                    const generalError = document.querySelector('.bg-red-100');
                    if (generalError) {
                        generalError.style.display = 'none';
                    }
                });
            }
        });
    </script>
@endsection
