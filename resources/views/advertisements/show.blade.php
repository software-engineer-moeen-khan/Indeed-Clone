<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advertisement | Best Way Jobs</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f7f7f7] text-[#2d2d2d] antialiased">
    <main class="min-h-screen flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-4xl rounded-2xl border border-[#e4e2e0] bg-white shadow-sm overflow-hidden">
            <div class="border-b border-[#e4e2e0] px-5 py-4 sm:px-6">
                <div class="text-[10px] font-semibold uppercase tracking-[0.16em] text-[#767676]">Advertisement</div>
                <p class="mt-1 text-sm text-[#595959]">Sponsored content opened from Best Way Jobs.</p>
            </div>

            <div class="p-5 sm:p-8">
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
                                class="mx-auto h-auto max-h-[70vh] w-full object-contain"
                            >
                        </a>
                    @else
                        <div class="overflow-hidden rounded-xl border border-[#e4e2e0] bg-white">
                            <img
                                src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($advertisement->image_path) }}"
                                alt="{{ $advertisement->alt_text ?: $advertisement->name }}"
                                class="mx-auto h-auto max-h-[70vh] w-full object-contain"
                            >
                        </div>
                    @endif
                @else
                    <div class="py-12 text-center text-[#595959]">This advertisement is currently unavailable.</div>
                @endif
            </div>
        </div>
    </main>
</body>
</html>
