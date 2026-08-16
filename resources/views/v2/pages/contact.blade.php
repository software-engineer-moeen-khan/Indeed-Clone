@extends('v2.layouts.app')

@section('content')
<section class="bg-white border-b border-[#e4e2e0]">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <div class="max-w-3xl">
            <p class="text-sm font-semibold text-[#2557a7] mb-3">CONTACT</p>
            <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-[#2d2d2d]">How can we help?</h1>
            <p class="mt-5 text-lg leading-8 text-[#595959]">
                Send us a message about account questions, incorrect job information, privacy requests, technical issues or general feedback.
            </p>
        </div>
    </div>
</section>

<section class="bg-[#f7f7f7]">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
        @if(session('contact_success'))
            <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-green-800" role="alert">
                <div class="flex items-start gap-3">
                    <i class="las la-check-circle text-xl mt-0.5"></i>
                    <p>{{ session('contact_success') }}</p>
                </div>
            </div>
        @endif

        <div class="grid gap-8 lg:grid-cols-[1.2fr_0.8fr] items-start">
            <div class="indeed-card p-6 sm:p-8">
                <div class="mb-7">
                    <div class="w-11 h-11 rounded-lg bg-[#eef4fb] text-[#2557a7] flex items-center justify-center mb-4">
                        <i class="las la-envelope text-2xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-[#2d2d2d]">Contact Us</h2>
                    <p class="mt-2 text-sm leading-6 text-[#595959]">Fill in the form below. Your message will be sent directly to the Best Way Jobs admin panel.</p>
                </div>

                <form action="{{ route('contact.store') }}" method="POST" class="space-y-5">
                    @csrf

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="contact-name" class="block text-sm font-semibold text-[#2d2d2d] mb-2">Name</label>
                            <input
                                id="contact-name"
                                name="name"
                                type="text"
                                value="{{ old('name', auth()->user()?->name) }}"
                                maxlength="120"
                                required
                                autocomplete="name"
                                class="w-full rounded-lg border border-[#949494] bg-white px-4 py-3 text-[#2d2d2d] outline-none transition focus:border-[#2557a7] focus:ring-1 focus:ring-[#2557a7]"
                                placeholder="Your full name"
                            >
                            @error('name')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="contact-email" class="block text-sm font-semibold text-[#2d2d2d] mb-2">Email</label>
                            <input
                                id="contact-email"
                                name="email"
                                type="email"
                                value="{{ old('email', auth()->user()?->email) }}"
                                maxlength="255"
                                required
                                autocomplete="email"
                                class="w-full rounded-lg border border-[#949494] bg-white px-4 py-3 text-[#2d2d2d] outline-none transition focus:border-[#2557a7] focus:ring-1 focus:ring-[#2557a7]"
                                placeholder="you@example.com"
                            >
                            @error('email')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="contact-subject" class="block text-sm font-semibold text-[#2d2d2d] mb-2">Subject</label>
                        <input
                            id="contact-subject"
                            name="subject"
                            type="text"
                            value="{{ old('subject') }}"
                            maxlength="200"
                            required
                            class="w-full rounded-lg border border-[#949494] bg-white px-4 py-3 text-[#2d2d2d] outline-none transition focus:border-[#2557a7] focus:ring-1 focus:ring-[#2557a7]"
                            placeholder="What do you need help with?"
                        >
                        @error('subject')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="contact-message" class="block text-sm font-semibold text-[#2d2d2d] mb-2">Message</label>
                        <textarea
                            id="contact-message"
                            name="message"
                            rows="7"
                            minlength="10"
                            maxlength="5000"
                            required
                            class="w-full resize-y rounded-lg border border-[#949494] bg-white px-4 py-3 text-[#2d2d2d] outline-none transition focus:border-[#2557a7] focus:ring-1 focus:ring-[#2557a7]"
                            placeholder="Please describe your question or issue in detail."
                        >{{ old('message') }}</textarea>
                        @error('message')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-1">
                        <p class="text-xs leading-5 text-[#767676]">Please do not include passwords, card details or other sensitive credentials.</p>
                        <button type="submit" class="btn-primary shrink-0 min-w-[150px]">Send message</button>
                    </div>
                </form>
            </div>

            <div class="space-y-6">
                <div class="indeed-card p-6 sm:p-7">
                    <h2 class="text-lg font-bold text-[#2d2d2d]">What to include</h2>
                    <div class="mt-5 divide-y divide-[#e4e2e0]">
                        <div class="pb-4">
                            <h3 class="font-semibold text-[#2d2d2d]">Account issue</h3>
                            <p class="mt-1 text-sm leading-6 text-[#595959]">Mention the email connected to your account, but never send your password.</p>
                        </div>
                        <div class="py-4">
                            <h3 class="font-semibold text-[#2d2d2d]">Job correction</h3>
                            <p class="mt-1 text-sm leading-6 text-[#595959]">Include the job title, employer and listing URL so we can identify it quickly.</p>
                        </div>
                        <div class="py-4">
                            <h3 class="font-semibold text-[#2d2d2d]">Privacy request</h3>
                            <p class="mt-1 text-sm leading-6 text-[#595959]">State whether you want to access, correct or delete account information.</p>
                        </div>
                        <div class="pt-4">
                            <h3 class="font-semibold text-[#2d2d2d]">Technical problem</h3>
                            <p class="mt-1 text-sm leading-6 text-[#595959]">Include the page URL and describe what happened and what you expected.</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-[#d4d2d0] bg-white p-6">
                    <h2 class="font-bold text-[#2d2d2d]">External applications</h2>
                    <p class="mt-2 text-sm leading-6 text-[#595959]">
                        If an Apply link opens another website, that employer or third-party site controls the application process and hiring decision.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
