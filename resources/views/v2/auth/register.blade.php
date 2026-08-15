@extends('v2.auth.app')
@section('title') Create Account - Geezap @endsection

@section('content')
<div class="bg-white rounded-xl shadow-sm p-7 sm:p-9 max-w-md w-full space-y-6 border border-[#d4d2d0]">
    <div class="space-y-2">
        <h1 class="text-2xl sm:text-3xl font-bold text-[#2d2d2d]">Create an account</h1>
        <p class="text-sm text-[#595959]">Build your profile and start applying for opportunities on Geezap.</p>
    </div>

    @if($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
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
            <label for="name" class="block text-sm font-semibold text-[#2d2d2d] mb-2">Full name</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                   placeholder="Your full name"
                   class="indeed-input">
        </div>

        <div>
            <label for="email" class="block text-sm font-semibold text-[#2d2d2d] mb-2">Email address</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                   placeholder="name@example.com"
                   class="indeed-input">
        </div>

        <div>
            <label for="password" class="block text-sm font-semibold text-[#2d2d2d] mb-2">Password</label>
            <input type="password" id="password" name="password" required autocomplete="new-password"
                   placeholder="Create a strong password"
                   class="indeed-input">
            <p class="mt-2 text-xs text-[#767676]">Use at least 8 characters with uppercase, lowercase, number and symbol.</p>
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-semibold text-[#2d2d2d] mb-2">Confirm password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                   placeholder="Repeat your password"
                   class="indeed-input">
        </div>

        <label class="flex items-start gap-3 text-sm text-[#595959]">
            <input type="checkbox" name="check" value="1" required class="mt-1 rounded border-[#949494] text-[#2557a7] focus:ring-[#2557a7]">
            <span>I agree to the <a href="{{ route('terms') }}" class="font-semibold text-[#2557a7] hover:underline">Terms</a> and <a href="{{ route('privacy-policy') }}" class="font-semibold text-[#2557a7] hover:underline">Privacy Policy</a>.</span>
        </label>

        <button type="submit" class="btn-primary w-full py-3">
            Create account
        </button>
    </form>

    <div class="flex items-center gap-3">
        <div class="h-px flex-1 bg-[#e4e2e0]"></div>
        <span class="text-xs font-medium text-[#767676]">OR</span>
        <div class="h-px flex-1 bg-[#e4e2e0]"></div>
    </div>

    <x-social-login/>

    <p class="text-center text-sm text-[#595959]">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-[#2557a7] hover:underline">Sign in</a>
    </p>
</div>
@endsection
