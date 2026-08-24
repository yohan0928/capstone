@extends('layouts.app')

@section('title', 'Two-Factor Authentication')

@section('content')
    <div class="flex items-center justify-center min-h-screen bg-gray-100">
        <div class="bg-white shadow-lg rounded-lg p-8 max-w-md w-full">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-[#4A2C1D] rounded-full mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">Two-Factor Authentication</h2>
                <p class="text-gray-600 mt-2">Enter the verification code sent to your email</p>
            </div>

            @if(session('status'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login.2fa.verify') }}">
                @csrf

                <div class="mb-6">
                    <p class="text-gray-700 mb-4">
                        We've sent a 6-digit verification code to 
                        <span class="font-semibold">{{ $email ?? 'your email' }}</span>.
                        Please enter it below to continue.
                    </p>
                    
                    <label for="verification_code" class="block text-sm font-medium text-gray-700 mb-2">
                        Verification Code
                    </label>
                    <input type="text" 
                           name="verification_code" 
                           id="verification_code" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#4A2C1D] focus:border-transparent"
                           placeholder="Enter 6-digit code"
                           maxlength="6"
                           pattern="[0-9]{6}"
                           required
                           autofocus>
                    <p class="text-sm text-gray-500 mt-2">Enter the 6-digit code sent to your email</p>
                </div>

                <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-sm text-blue-700">
                            The code expires in 10 minutes. Didn't receive it?
                            <button form="resend-form" type="submit"
                               class="font-medium text-blue-600 hover:text-blue-800 underline bg-transparent border-none p-0 cursor-pointer">
                                Click here to resend
                            </button>
                        </p>
                    </div>
                </div>

                <button type="submit"
                        class="w-full bg-[#4A2C1D] text-white font-semibold py-3 px-4 rounded-lg hover:bg-[#5a3c2d] transition duration-300">
                    Verify & Continue
                </button>

                <div class="mt-6 text-center">
                    <a href="{{ route('showLoginForm') }}" 
                       class="text-sm text-gray-600 hover:text-gray-800 underline">
                        ← Back to Login
                    </a>
                </div>
            </form>
            
            <form id="resend-form" method="POST" action="{{ route('login.2fa.resend') }}">
                @csrf
            </form>

            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="flex items-center justify-center text-sm text-gray-600">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v2H2v-4l4.257-4.257A6 6 0 1118 8zm-6-4a1 1 0 100 2 2 2 0 012 2 1 1 0 102 0 4 4 0 00-4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>Need help? Contact support@linkudhub.com</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-focus and format the input
        document.getElementById('verification_code').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Auto-submit when 6 digits are entered
            if (this.value.length === 6) {
                this.form.submit();
            }
        });
    </script>
@endsection