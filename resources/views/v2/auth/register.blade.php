@extends('v2.auth.app')
@section('title') Sign Up - Geezap @endsection

@section('content')
<div class="bg-white dark:bg-[#12122b] rounded-2xl shadow-2xl p-8 sm:p-10 max-w-md w-full space-y-7 border border-gray-200 dark:border-gray-700">
    <div class="text-center space-y-3">
        <div class="mx-auto w-16 h-16 bg-gradient-to-br from-green-500 to-blue-600 rounded-2xl flex items-center justify-center">
            <i class="las la-user-plus text-3xl text-white"></i>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white font-oxanium-bold">Create your account</h1>
        <p class="text-gray-600 dark:text-gray-400">Join Geezap and start applying for jobs</p>
    </div>

    @if($errors->any())
        <div class="rounded-xl bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-300">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('register') }}" method="POST" class="space-y-4">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Full name</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                   placeholder="Your full name"
                   class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-[#1a1a3a] text-gray-900 dark:text-white placeholder-gray-500 border border-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-pink-500 focus:outline-none">
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email address</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                   placeholder="name@example.com"
                   class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-[#1a1a3a] text-gray-900 dark:text-white placeholder-gray-500 border border-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-pink-500 focus:outline-none">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Password</label>
            <input type="password" id="password" name="password" required autocomplete="new-password"
                   placeholder="Minimum 8 characters"
                   class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-[#1a1a3a] text-gray-900 dark:text-white placeholder-gray-500 border border-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-pink-500 focus:outline-none">
            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Use uppercase, lowercase and at least one number.</p>
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Confirm password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                   placeholder="Repeat your password"
                   class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-[#1a1a3a] text-gray-900 dark:text-white placeholder-gray-500 border border-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-pink-500 focus:outline-none">
        </div>

        <label class="flex items-start gap-3 text-sm text-gray-600 dark:text-gray-300">
            <input type="checkbox" name="check" value="1" required class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <span>I agree to the <a href="{{ route('terms') }}" class="text-blue-600 dark:text-pink-400 hover:underline">Terms</a> and <a href="{{ route('privacy-policy') }}" class="text-blue-600 dark:text-pink-400 hover:underline">Privacy Policy</a>.</span>
        </label>

        <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-blue-600 dark:from-pink-500 dark:to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:opacity-90 transition-all">
            Create Account
        </button>
    </form>

    <div class="flex items-center gap-3">
        <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
        <span class="text-xs text-gray-500">OR</span>
        <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
    </div>

    <x-social-login/>

    <p class="text-center text-sm text-gray-600 dark:text-gray-400">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-blue-600 dark:text-pink-400 hover:underline">Sign In</a>
    </p>

    <div class="text-center">
        <a href="{{ route('home') }}" class="text-sm text-gray-500 hover:text-blue-600 dark:hover:text-pink-400">← Back to home</a>
    </div>
</div>
@endsection
