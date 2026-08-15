@extends('v2.auth.app')
@section('title') Login - Geezap @endsection

@section('content')
<div class="bg-white dark:bg-[#12122b] rounded-2xl shadow-2xl p-8 sm:p-10 max-w-md w-full space-y-7 border border-gray-200 dark:border-gray-700">
    <div class="text-center space-y-3">
        <div class="mx-auto w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center">
            <i class="las la-sign-in-alt text-3xl text-white"></i>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white font-oxanium-bold">Welcome back</h1>
        <p class="text-gray-600 dark:text-gray-400">Sign in to continue to Geezap</p>
    </div>

    @if(session('status'))
        <div class="rounded-xl bg-green-50 dark:bg-green-900/20 px-4 py-3 text-sm text-green-700 dark:text-green-300">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-300">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('login') }}" method="POST" class="space-y-5">
        @csrf
        <input type="hidden" name="intended_url" value="{{ request('intended_url', url()->previous()) }}">

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email address</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                   placeholder="name@example.com"
                   class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-[#1a1a3a] text-gray-900 dark:text-white placeholder-gray-500 border border-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-pink-500 focus:outline-none">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password"
                   placeholder="Enter your password"
                   class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-[#1a1a3a] text-gray-900 dark:text-white placeholder-gray-500 border border-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-pink-500 focus:outline-none">
        </div>

        <div class="flex items-center justify-between gap-4 text-sm">
            <label class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                Remember me
            </label>
            <a href="{{ route('password.request') }}" class="text-blue-600 dark:text-pink-400 hover:underline">Forgot password?</a>
        </div>

        <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-blue-600 dark:from-pink-500 dark:to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:opacity-90 transition-all">
            Sign In
        </button>
    </form>

    <div class="flex items-center gap-3">
        <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
        <span class="text-xs text-gray-500">OR</span>
        <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
    </div>

    <x-social-login/>

    <p class="text-center text-sm text-gray-600 dark:text-gray-400">
        Don't have an account?
        <a href="{{ route('register') }}" class="font-semibold text-blue-600 dark:text-pink-400 hover:underline">Sign Up</a>
    </p>

    <div class="text-center">
        <a href="{{ route('home') }}" class="text-sm text-gray-500 hover:text-blue-600 dark:hover:text-pink-400">← Back to home</a>
    </div>
</div>
@endsection
