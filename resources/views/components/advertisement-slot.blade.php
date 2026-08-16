@props(['placement'])

@php
    $advertisements = \App\Models\Advertisement::forPlacement($placement);
@endphp

@if($advertisements->isNotEmpty())
    <div class="ad-slot ad-slot-{{ str_replace('_', '-', $placement) }}">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="space-y-3">
                @foreach($advertisements as $advertisement)
                    <div class="text-center">
                        <div class="mb-1 text-[10px] uppercase tracking-[0.14em] text-[#767676]">Advertisement</div>

                        @if($advertisement->type === 'code' && filled($advertisement->custom_code))
                            <div class="overflow-hidden rounded-lg">
                                {!! $advertisement->custom_code !!}
                            </div>
                        @elseif($advertisement->type === 'image' && filled($advertisement->image_path))
                            @if($advertisement->target_url)
                                <a href="{{ $advertisement->target_url }}"
                                   @if($advertisement->open_in_new_tab) target="_blank" rel="noopener sponsored" @else rel="sponsored" @endif
                                   class="block overflow-hidden rounded-lg border border-[#e4e2e0] bg-white">
                                    <img
                                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($advertisement->image_path) }}"
                                        alt="{{ $advertisement->alt_text ?: $advertisement->name }}"
                                        class="mx-auto h-auto max-h-[220px] w-full object-contain"
                                        loading="lazy"
                                    >
                                </a>
                            @else
                                <div class="overflow-hidden rounded-lg border border-[#e4e2e0] bg-white">
                                    <img
                                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($advertisement->image_path) }}"
                                        alt="{{ $advertisement->alt_text ?: $advertisement->name }}"
                                        class="mx-auto h-auto max-h-[220px] w-full object-contain"
                                        loading="lazy"
                                    >
                                </div>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
