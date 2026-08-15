<footer class="bg-white border-t border-[#e4e2e0] mt-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            <div>
                <h4 class="text-sm font-bold text-[#2d2d2d] mb-4">Job seekers</h4>
                <ul class="space-y-3 text-sm">
                    <li><a href="{{ route('job.index') }}" class="text-[#595959] hover:text-[#2557a7] hover:underline">Browse jobs</a></li>
                    <li><a href="{{ route('job.categories') }}" class="text-[#595959] hover:text-[#2557a7] hover:underline">Browse categories</a></li>
                    <li><a href="{{ route('dashboard') }}" class="text-[#595959] hover:text-[#2557a7] hover:underline">My profile</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-sm font-bold text-[#2d2d2d] mb-4">Company</h4>
                <ul class="space-y-3 text-sm">
                    <li><a href="{{ route('home') }}" class="text-[#595959] hover:text-[#2557a7] hover:underline">Home</a></li>
                    <li><a href="{{ route('about') }}" class="text-[#595959] hover:text-[#2557a7] hover:underline">About</a></li>
                    <li><a href="{{ route('contact') }}" class="text-[#595959] hover:text-[#2557a7] hover:underline">Contact</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-sm font-bold text-[#2d2d2d] mb-4">Connect</h4>
                <ul class="space-y-3 text-sm">
                    <li><a href="https://facebook.com/geezap247" target="_blank" rel="noopener" class="text-[#595959] hover:text-[#2557a7] hover:underline">Facebook</a></li>
                    <li><a href="https://github.com/theihasan/geezap" target="_blank" rel="noopener" class="text-[#595959] hover:text-[#2557a7] hover:underline">GitHub</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-sm font-bold text-[#2d2d2d] mb-4">Legal</h4>
                <ul class="space-y-3 text-sm">
                    <li><a href="{{ route('privacy-policy') }}" class="text-[#595959] hover:text-[#2557a7] hover:underline">Privacy</a></li>
                    <li><a href="{{ route('terms') }}" class="text-[#595959] hover:text-[#2557a7] hover:underline">Terms</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-10 pt-6 border-t border-[#e4e2e0] flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between text-sm text-[#595959]">
            <div class="flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-md bg-[#2557a7] text-xs font-bold text-white">G</span>
                <span class="font-semibold text-[#2d2d2d]">Geezap</span>
            </div>
            <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</footer>

@livewireScripts

<script>
    window.addEventListener('load', () => {
        if ('requestIdleCallback' in window) {
            requestIdleCallback(() => {
                const analyticsScript = document.createElement('script');
                analyticsScript.src = 'https://scripts.simpleanalyticscdn.com/latest.js';
                analyticsScript.async = true;
                document.body.appendChild(analyticsScript);
            });
        } else {
            setTimeout(() => {
                const analyticsScript = document.createElement('script');
                analyticsScript.src = 'https://scripts.simpleanalyticscdn.com/latest.js';
                analyticsScript.async = true;
                document.body.appendChild(analyticsScript);
            }, 2000);
        }
    });
</script>
@stack('extra-js')

</body>
</html>
