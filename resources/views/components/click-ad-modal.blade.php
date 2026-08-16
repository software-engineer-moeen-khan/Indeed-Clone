@props(['placement'])

@php
    $advertisement = \App\Models\Advertisement::forPlacement($placement)->first();
    $advertisementUrl = $advertisement ? route('advertisement.open', $advertisement) : null;
@endphp

@if($advertisement && $advertisementUrl)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const placement = @json($placement);
            const advertisementUrl = @json($advertisementUrl);

            const openAdvertisement = () => {
                const adWindow = window.open(advertisementUrl, '_blank', 'noopener');

                if (adWindow) {
                    try {
                        adWindow.opener = null;
                    } catch (error) {
                        // Browser security restrictions can prevent access to the new window.
                    }
                }
            };

            document.addEventListener('submit', function (event) {
                const form = event.target;

                if (!(form instanceof HTMLFormElement)) return;
                if (form.dataset.clickAdPlacement !== placement) return;

                // Open the advertisement in a separate tab, but do not stop the form.
                // The original Find Jobs action continues in the current tab.
                openAdvertisement();
            }, true);

            document.addEventListener('click', function (event) {
                const trigger = event.target.closest(`[data-click-ad-placement="${placement}"]`);

                if (!trigger) return;

                openAdvertisement();

                // Application links previously opened in a new tab. Keep the ad in the
                // new tab and move the current tab to the intended application URL.
                if (trigger instanceof HTMLAnchorElement && trigger.href) {
                    event.preventDefault();

                    const destination = trigger.href;

                    // Allow existing click handlers (including Livewire apply tracking)
                    // to run before navigating the current tab away.
                    window.setTimeout(function () {
                        window.location.assign(destination);
                    }, 180);
                }
            }, true);
        });
    </script>
@endif
