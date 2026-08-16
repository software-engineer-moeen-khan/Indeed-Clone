@extends('v2.auth.app')
@section('title') Sign In - Geezap @endsection

@section('content')
<div class="bg-white rounded-xl shadow-sm p-7 sm:p-9 max-w-md w-full space-y-6 border border-[#d4d2d0]">
    <div class="space-y-2">
        <h1 class="text-2xl sm:text-3xl font-bold text-[#2d2d2d]">Sign in</h1>
        <p class="text-sm text-[#595959]">Access your Geezap profile, applications and saved preferences.</p>
    </div>

    @if(session('status'))
        <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('login') }}" method="POST" class="space-y-5">
        @csrf
        <input type="hidden" name="intended_url" value="{{ request('intended_url', url()->previous()) }}">

        <div>
            <label for="email" class="block text-sm font-semibold text-[#2d2d2d] mb-2">Email address</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                   placeholder="name@example.com"
                   class="indeed-input">
        </div>

        <div>
            <div class="flex items-center justify-between mb-2">
                <label for="password" class="block text-sm font-semibold text-[#2d2d2d]">Password</label>
                <a href="{{ route('password.request') }}" class="text-sm font-semibold text-[#2557a7] hover:underline">Forgot password?</a>
            </div>
            <input type="password" id="password" name="password" required autocomplete="current-password"
                   placeholder="Enter your password"
                   class="indeed-input">
        </div>

        <label class="flex items-center gap-2 text-sm text-[#595959]">
            <input type="checkbox" name="remember" class="rounded border-[#949494] text-[#2557a7] focus:ring-[#2557a7]">
            Keep me signed in
        </label>

        <button type="submit" class="btn-primary w-full py-3">
            Sign in
        </button>
    </form>

    <p class="text-center text-sm text-[#595959]">
        New to Geezap?
        <a href="{{ route('register') }}" class="font-semibold text-[#2557a7] hover:underline">Create an account</a>
    </p>
</div>
@endsection
