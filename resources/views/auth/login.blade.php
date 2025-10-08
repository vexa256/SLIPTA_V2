<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SLIPTA Digital Assessment System - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-neutral-50">

    <div class="min-h-screen flex items-center justify-center p-4">

        <!-- Login Card -->
        <div class="w-full max-w-md">

            <!-- Card Container -->
            <div class="bg-white rounded-2xl shadow-sm border border-neutral-200 p-8">

                <!-- Logo & Header Section -->
                <div class="text-center mb-8">
                    <!-- Logo -->
                    <div class="mb-6">
                        <img src="https://ecsahc.org/wp-content/uploads/2021/09/ecsahc_web_logo1-1.png"
                             alt="ECSA-HC Logo"
                             class="h-16 mx-auto mb-4">
                    </div>

                    <!-- Title -->
                    <h1 class="text-2xl font-semibold text-neutral-900 mb-2">SLIPTA Digital Assessment</h1>
                    <p class="text-sm text-neutral-500 mb-1">Stepwise Laboratory Quality Improvement</p>
                    <p class="text-xs text-neutral-400">Supported by <span class="font-medium text-neutral-600">ASLM</span></p>
                </div>

                <!-- Session Status Alert -->
                @if (session('status'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl">
                        <p class="text-sm text-green-800">{{ session('status') }}</p>
                    </div>
                @endif

                <!-- Login Form -->
                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-neutral-700 mb-2">
                            Email Address
                        </label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            class="w-full px-4 py-3 bg-white border border-neutral-200 rounded-xl text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900 transition-all duration-150 @error('email') border-red-500 @enderror"
                            placeholder="your.email@example.com">

                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-neutral-700 mb-2">
                            Password
                        </label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            class="w-full px-4 py-3 bg-white border border-neutral-200 rounded-xl text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900 transition-all duration-150 @error('password') border-red-500 @enderror"
                            placeholder="Enter your password">

                        @error('password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember Me Checkbox -->
                    <div class="flex items-center">
                        <input
                            id="remember_me"
                            type="checkbox"
                            name="remember"
                            class="w-4 h-4 rounded border-neutral-300 text-neutral-900 focus:ring-2 focus:ring-neutral-900/10 transition-all duration-150">
                        <label for="remember_me" class="ml-2 text-sm text-neutral-600">
                            Remember me for 30 days
                        </label>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-4">
                        <!-- Login Button -->
                        <button
                            type="submit"
                            class="w-full bg-neutral-900 text-white py-3 px-4 rounded-xl font-medium hover:bg-black focus:outline-none focus:ring-2 focus:ring-neutral-900 focus:ring-offset-2 transition-all duration-150">
                            Sign In
                        </button>

                        <!-- Forgot Password Link -->
                        @if (Route::has('password.request'))
                            <div class="text-center">
                                <a href="{{ route('password.request') }}"
                                   class="text-sm text-neutral-600 hover:text-neutral-900 transition-colors duration-150">
                                    Forgot your password?
                                </a>
                            </div>
                        @endif
                    </div>
                </form>

            </div>

            <!-- Footer Info -->
            <div class="mt-6 text-center">
                <p class="text-xs text-neutral-500">
                    WHO AFRO SLIPTA Programme • ISO 15189:2022
                </p>
                <p class="text-xs text-neutral-400 mt-2">
                    Version 3:2023 • Confidential System
                </p>
            </div>

        </div>
    </div>

</body>
</html>
