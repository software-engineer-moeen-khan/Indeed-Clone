@props(['placement'])

@php
    $advertisement = \App\Models\Advertisement::forPlacement($placement)->first();
    $modalId = 'click-ad-modal-' . str_replace('_', '-', $placement);
@endphp

@if($advertisement)
    <div
        id="{{ $modalId }}"
        class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/60 p-4"
        role="dialog"
        aria-modal="true"
        aria-hidden="true"
        aria-label="Advertisement"
    >
        <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl" data-click-ad-dialog>
            <div class="flex items-center justify-between border-b border-[#e4e2e0] px-5 py-4">
                <div>
                    <div class="text-[10px] font-semibold uppercase tracking-[0.16em] text-[#767676]">Advertisement</div>
                    <div class="mt-1 text-sm text-[#595959]">Continue when you are ready.</div>
                </div>
                <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-full text-[#595959] transition hover:bg-[#f3f2f1] hover:text-[#2d2d2d]"
                    data-click-ad-close
                    aria-label="Close advertisement"
                >
                    <i class="las la-times text-xl" aria-hidden="true"></i>
                </button>
            </div>

            <div class="max-h-[70vh] overflow-y-auto p-5 sm:p-6">
                @if($advertisement->type === 'code' && filled($advertisement->custom_code))
                    <div class="overflow-hidden rounded-xl">
                        {!! $advertisement->custom_code !!}
                    </div>
                @elseif($advertisement->type === 'image' && filled($advertisement->image_path))
                    @if($advertisement->target_url)
                        <a
                            href="{{ $advertisement->target_url }}"
                            @if($advertisement->open_in_new_tab) target="_blank" rel="noopener sponsored" @else rel="sponsored" @endif
                            class="block overflow-hidden rounded-xl border border-[#e4e2e0] bg-white"
                        >
                            <img
                                src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($advertisement->image_path) }}"
                                alt="{{ $advertisement->alt_text ?: $advertisement->name }}"
                                class="mx-auto h-auto max-h-[420px] w-full object-contain"
                            >
                        </a>
                    @else
                        <div class="overflow-hidden rounded-xl border border-[#e4e2e0] bg-white">
                            <img
                                src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($advertisement->image_path) }}"
                                alt="{{ $advertisement->alt_text ?: $advertisement->name }}"
                                class="mx-auto h-auto max-h-[420px] w-full object-contain"
                            >
                        </div>
                    @endif
                @endif
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-[#e4e2e0] bg-[#fafafa] px-5 py-4 sm:flex-row sm:justify-end">
                <button type="button" class="btn-secondary" data-click-ad-close>Close</button>
                <button type="button" class="btn-primary" data-click-ad-continue>Continue</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const placement = @json($placement);
            const modal = document.getElementById(@json($modalId));

            if (!modal) return;

            let pendingTarget = null;
            let pendingType = null;

            const openModal = (target, type) => {
                pendingTarget = target;
                pendingType = type;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.setAttribute('aria-hidden', 'false');
                document.documentElement.classList.add('overflow-hidden');
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modal.setAttribute('aria-hidden', 'true');
                document.documentElement.classList.remove('overflow-hidden');
            };

            const clearPending = () => {
                pendingTarget = null;
                pendingType = null;
            };

            const cancelAction = () => {
                closeModal();
                clearPending();
            };

            const continueAction = () => {
                const target = pendingTarget;
                const type = pendingType;

                closeModal();
                clearPending();

                if (!target) return;

                target.dataset.clickAdBypass = '1';

                if (type === 'form') {
                    if (typeof target.requestSubmit === 'function') {
                        target.requestSubmit();
                    } else {
                        target.submit();
                    }
                    return;
                }

                target.click();
            };

            document.addEventListener('submit', function (event) {
                const form = event.target;

                if (!(form instanceof HTMLFormElement)) return;
                if (form.dataset.clickAdPlacement !== placement) return;

                if (form.dataset.clickAdBypass === '1') {
                    delete form.dataset.clickAdBypass;
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();
                openModal(form, 'form');
            }, true);

            document.addEventListener('click', function (event) {
                const trigger = event.target.closest(`[data-click-ad-placement="${placement}"]`);

                if (!trigger) return;

                if (trigger.dataset.clickAdBypass === '1') {
                    delete trigger.dataset.clickAdBypass;
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();
                openModal(trigger, 'click');
            }, true);

            modal.querySelectorAll('[data-click-ad-close]').forEach((button) => {
                button.addEventListener('click', cancelAction);
            });

            const continueButton = modal.querySelector('[data-click-ad-continue]');
            if (continueButton) {
                continueButton.addEventListener('click', continueAction);
            }

            modal.addEventListener('click', function (event) {
                if (event.target === modal) cancelAction();
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
                    cancelAction();
                }
            });
        });
    </script>
@endif
