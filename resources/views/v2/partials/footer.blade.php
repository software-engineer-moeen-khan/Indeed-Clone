<footer class="public-footer mt-14">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex flex-wrap gap-x-6 gap-y-3 text-sm">
            <a href="{{ route('home') }}" class="text-[#2d2d2d] hover:text-[#2557a7] hover:underline">Home</a>
            <a href="{{ route('job.index') }}" class="text-[#2d2d2d] hover:text-[#2557a7] hover:underline">Browse jobs</a>
            <a href="{{ route('job.categories') }}" class="text-[#2d2d2d] hover:text-[#2557a7] hover:underline">Career categories</a>
            <a href="{{ route('about') }}" class="text-[#2d2d2d] hover:text-[#2557a7] hover:underline">About</a>
            <a href="{{ route('contact') }}" class="text-[#2d2d2d] hover:text-[#2557a7] hover:underline">Contact</a>
            <a href="{{ route('privacy-policy') }}" class="text-[#2d2d2d] hover:text-[#2557a7] hover:underline">Privacy</a>
            <a href="{{ route('terms') }}" class="text-[#2d2d2d] hover:text-[#2557a7] hover:underline">Terms</a>
        </div>

        <div class="mt-7 pt-6 border-t border-[#e4e2e0] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-sm text-[#595959]">
            <div class="font-bold text-[#2d2d2d]">Best Way <span class="text-[#2557a7]">Jobs</span></div>
            <p>© {{ date('Y') }} Best Way Jobs. All rights reserved.</p>
        </div>
    </div>
</footer>

@livewireScripts

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const emailShareButton = document.querySelector('button[onclick="shareViaEmail()"]');

        if (emailShareButton && !document.getElementById('whatsapp-share-button')) {
            window.shareOnWhatsApp = function () {
                const jobTitle = document.querySelector('h1')?.textContent?.trim() || document.title;
                const text = encodeURIComponent(`Check out this job opportunity on Best Way Jobs: ${jobTitle}\n${window.location.href}`);
                window.open(`https://wa.me/?text=${text}`, '_blank', 'noopener,noreferrer');
            };

            const whatsappButton = document.createElement('button');
            whatsappButton.id = 'whatsapp-share-button';
            whatsappButton.type = 'button';
            whatsappButton.setAttribute('onclick', 'shareOnWhatsApp()');
            whatsappButton.setAttribute('title', 'Share on WhatsApp');
            whatsappButton.setAttribute('aria-label', 'Share on WhatsApp');
            whatsappButton.className = 'w-9 h-9 bg-[#25D366] hover:bg-[#1ebe5d] text-white rounded-lg flex items-center justify-center transition-colors duration-200';
            whatsappButton.innerHTML = '<i class="lab la-whatsapp text-base"></i>';

            emailShareButton.parentNode.insertBefore(whatsappButton, emailShareButton);
        }
    });
</script>
@stack('extra-js')

</body>
</html>