@include('v2.partials.header')
<body class="bg-white text-[#2d2d2d] font-sans antialiased">
<nav class="sticky top-0 z-40 bg-white border-b border-[#e4e2e0]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center gap-8">
                @include('v2.partials.logo')
                @include('v2.partials.desktop-menu')
            </div>

            @include('v2.partials.header-auth')

            <button class="md:hidden w-10 h-10 rounded-lg hover:bg-[#f3f2f1] transition-colors flex items-center justify-center"
                    onclick="toggleMobileMenu()"
                    id="menu-toggle"
                    aria-label="Open menu">
                <i class="las la-bars text-2xl text-[#2d2d2d] transition-transform duration-300"></i>
            </button>
        </div>
    </div>

    @include('v2.partials.mobile-menu')
</nav>

<main>
    @yield('content')
</main>

@include('v2.partials.footer')
<x-notification />
