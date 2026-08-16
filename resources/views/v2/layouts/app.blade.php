@include('v2.partials.header')
<body class="bg-white text-[#2d2d2d] font-sans antialiased {{ request()->routeIs('job.show') ? 'job-detail-page' : '' }}">
<nav class="public-nav sticky top-0 z-40">
    <div class="public-nav-inner px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center min-h-16">
            <div class="flex items-center gap-6 lg:gap-8">
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

<x-advertisement-slot placement="global_after_header" />

@if(request()->routeIs('job.show'))
    <x-advertisement-slot placement="job_details_top" />
@endif

<main>
    @yield('content')
</main>

<x-advertisement-slot placement="global_before_footer" />

@include('v2.partials.footer')
<x-notification />

<x-click-ad-modal placement="on_find_jobs" />
<x-click-ad-modal placement="on_apply_now" />

<button
    type="button"
    id="back-to-top"
    class="fixed bottom-5 right-5 sm:bottom-7 sm:right-7 z-50 flex h-11 w-11 items-center justify-center rounded-full bg-[#2557a7] text-white shadow-lg opacity-0 invisible translate-y-3 transition-all duration-200 hover:bg-[#164081] focus:outline-none focus:ring-4 focus:ring-[#2557a7]/25"
    aria-label="Back to top"
    title="Back to top"
>
    <i class="las la-arrow-up text-xl" aria-hidden="true"></i>
</button>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const backToTopButton = document.getElementById('back-to-top');

        if (!backToTopButton) {
            return;
        }

        const updateBackToTopVisibility = () => {
            const shouldShow = window.scrollY > 350;

            backToTopButton.classList.toggle('opacity-0', !shouldShow);
            backToTopButton.classList.toggle('invisible', !shouldShow);
            backToTopButton.classList.toggle('translate-y-3', !shouldShow);
            backToTopButton.classList.toggle('opacity-100', shouldShow);
            backToTopButton.classList.toggle('visible', shouldShow);
            backToTopButton.classList.toggle('translate-y-0', shouldShow);
        };

        window.addEventListener('scroll', updateBackToTopVisibility, { passive: true });
        updateBackToTopVisibility();

        backToTopButton.addEventListener('click', function () {
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            window.scrollTo({
                top: 0,
                behavior: reduceMotion ? 'auto' : 'smooth',
            });
        });
    });
</script>
