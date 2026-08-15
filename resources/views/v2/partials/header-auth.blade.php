<div class="hidden md:flex items-center gap-3">
    @guest
        <a href="{{ route('login') }}"
           class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-[#2557a7] hover:bg-[#eef4fb] rounded-lg transition-colors">
            Sign in
        </a>
        <a href="{{ route('register') }}"
           class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-semibold text-white bg-[#2557a7] hover:bg-[#164081] rounded-lg transition-colors">
            Sign up
        </a>
    @endguest

    @auth
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" @click.away="open = false"
                    class="flex items-center gap-2.5 text-[#2d2d2d] px-2.5 py-2 rounded-lg hover:bg-[#f3f2f1] transition-colors">
                <img src="{{ auth()->user()->profile_image_or_default }}" alt="{{ auth()->user()->name }}" class="w-8 h-8 rounded-full object-cover border border-[#d4d2d0]">
                <span class="text-sm font-semibold max-w-36 truncate">{{ auth()->user()->name }}</span>
                <i class="las la-angle-down text-sm transition-transform" :class="{ 'rotate-180': open }"></i>
            </button>

            <div x-show="open"
                 x-cloak
                 x-transition
                 class="absolute right-0 mt-2 w-56 rounded-xl bg-white border border-[#d4d2d0] shadow-lg z-50 overflow-hidden">
                <div class="p-2">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm text-[#2d2d2d] hover:bg-[#f3f2f1] rounded-lg">
                        <i class="las la-user-circle text-lg text-[#2557a7]"></i> Profile
                    </a>
                    <a href="{{ route('applications') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm text-[#2d2d2d] hover:bg-[#f3f2f1] rounded-lg">
                        <i class="las la-briefcase text-lg text-[#2557a7]"></i> My applications
                    </a>
                    <a href="{{ route('profile.preferences') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm text-[#2d2d2d] hover:bg-[#f3f2f1] rounded-lg">
                        <i class="las la-cog text-lg text-[#2557a7]"></i> Preferences
                    </a>
                    <div class="border-t border-[#e4e2e0] my-2"></div>
                    <a href="{{ route('logout') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm text-red-600 hover:bg-red-50 rounded-lg">
                        <i class="las la-sign-out-alt text-lg"></i> Sign out
                    </a>
                </div>
            </div>
        </div>
    @endauth
</div>
