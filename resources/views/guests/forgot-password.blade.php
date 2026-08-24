@extends('layouts.app')

@section('title', 'Forgot Password')

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
                    <p class="text-base sm:text-lg font-medium text-white opacity-90">Welcome to</p>
                    <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-white">Linkud Hub</h2>
                </div>
            </div>

            <!-- Right: Form Section -->
            <div class="w-full md:w-1/2 p-8 relative">
                <!-- X Button -->
                <a href="{{ route('welcome') }}"
                    class="absolute top-3 right-3 text-[#7F5539] hover:bg-[#4A2C1D] hover:text-white rounded p-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </a>

                <h1 class="text-2xl font-bold mb-2 mt-6 text-center text-[#4A2C1D]">Forgot Password</h1>

                <!-- Sub text -->
                <p class="text-sm md:text-base text-center text-[#4A2C1D] opacity-80 mb-4">
                    Enter your email address and we'll send you a link to reset your password.
                </p>

                <hr class="mb-6 border-[#4A2C1D]">

                <!-- Success Message -->
                @if(session('status'))
                    <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- Error Messages -->
                @if($errors->any())
                    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <!-- Email -->
                    <div class="mb-6">
                        <label for="email" class="block text-sm font-medium text-[#4A2C1D]">Email</label>
                        <input type="email" name="email" id="email"
                            class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1 focus:outline-none focus:border-[#4A2C1D] focus:ring-1 focus:ring-[#4A2C1D]"
                            value="{{ old('email') }}" required autofocus>
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-4">
                        <button type="submit"
                            class="bg-[#7F5539] w-full text-white font-semibold px-4 py-2 rounded hover:bg-[#4A2C1D]">
                            Send Reset Link
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
@endsection