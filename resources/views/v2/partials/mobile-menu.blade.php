<div class="md:hidden fixed inset-0 bg-black/30 opacity-0 pointer-events-none transition-opacity duration-300 z-40"
     id="menu-backdrop"></div>

<div class="md:hidden fixed inset-y-0 right-0 w-[300px] max-w-[88vw] bg-white z-50 transform transition-all duration-300 ease-in-out translate-x-full opacity-0 shadow-2xl border-l border-[#e4e2e0]"
     id="mobile-menu">
    <div class="flex flex-col h-full">
        <div class="flex justify-between items-center p-5 border-b border-[#e4e2e0]">
            @auth
                <div class="flex items-center gap-3 min-w-0">
                    <img src="{{ auth()->user()->profile_image_or_default }}" alt="{{ auth()->user()->name }}" class="w-10 h-10 rounded-full object-cover border border-[#d4d2d0]">
                    <div class="min-w-0">
                        <div class="font-semibold text-[#2d2d2d] truncate">{{ auth()->user()->name }}</div>
                        <div class="text-xs text-[#595959] truncate">{{ auth()->user()->email }}</div>
                    </div>
                </div>
            @else
                <div class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#2557a7] text-sm font-bold text-white">G</span>
                    <span class="font-bold text-[#2d2d2d]">Geezap</span>
                </div>
            @endauth

            <button onclick="toggleMobileMenu()" class="w-10 h-10 rounded-lg hover:bg-[#f3f2f1] transition-colors flex items-center justify-center text-[#2d2d2d]" aria-label="Close menu">
                <i class="las la-times text-xl"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-1">
            <a href="{{ route('job.index') }}"
               class="mobile-menu-item flex items-center gap-3 px-4 py-3 rounded-lg text-[#2d2d2d] hover:bg-[#f3f2f1] hover:text-[#2557a7] {{ request()->routeIs('job.index') || request()->routeIs('job.show') ? 'bg-[#eef4fb] text-[#2557a7] font-semibold' : '' }}">
                <i class="las la-search text-xl"></i>
                Find jobs
            </a>

            <a href="{{ route('job.categories') }}"
               class="mobile-menu-item flex items-center gap-3 px-4 py-3 rounded-lg text-[#2d2d2d] hover:bg-[#f3f2f1] hover:text-[#2557a7] {{ request()->routeIs('job.categories') ? 'bg-[#eef4fb] text-[#2557a7] font-semibold' : '' }}">
                <i class="las la-layer-group text-xl"></i>
                Browse categories
            </a>

            @auth
                <div class="my-3 border-t border-[#e4e2e0]"></div>
                <a href="{{ route('dashboard') }}" class="mobile-menu-item flex items-center gap-3 px-4 py-3 rounded-lg text-[#2d2d2d] hover:bg-[#f3f2f1] hover:text-[#2557a7]">
                    <i class="las la-user-circle text-xl"></i>
                    Profile
                </a>
                <a href="{{ route('applications') }}" class="mobile-menu-item flex items-center gap-3 px-4 py-3 rounded-lg text-[#2d2d2d] hover:bg-[#f3f2f1] hover:text-[#2557a7]">
                    <i class="las la-briefcase text-xl"></i>
                    My applications
                </a>
                <a href="{{ route('profile.preferences') }}" class="mobile-menu-item flex items-center gap-3 px-4 py-3 rounded-lg text-[#2d2d2d] hover:bg-[#f3f2f1] hover:text-[#2557a7]">
                    <i class="las la-cog text-xl"></i>
                    Preferences
                </a>
            @endauth
        </div>

        <div class="p-5 space-y-3 border-t border-[#e4e2e0] bg-[#faf9f8]">
            @auth
                <a href="{{ route('logout') }}" class="w-full border border-[#d4d2d0] bg-white hover:bg-red-50 text-red-600 px-5 py-3 rounded-lg font-semibold transition-colors flex items-center justify-center gap-2">
                    <i class="las la-sign-out-alt"></i>
                    Sign out
                </a>
            @else
                <a href="{{ route('login') }}" class="w-full border border-[#2557a7] bg-white hover:bg-[#eef4fb] text-[#2557a7] px-5 py-3 rounded-lg font-semibold transition-colors flex items-center justify-center">
                    Sign in
                </a>
                <a href="{{ route('register') }}" class="w-full bg-[#2557a7] hover:bg-[#164081] text-white px-5 py-3 rounded-lg font-semibold transition-colors flex items-center justify-center">
                    Sign up
                </a>
            @endauth
        </div>
    </div>
</div>
