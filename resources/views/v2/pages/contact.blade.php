@extends('v2.layouts.app')

@section('content')
@php($supportEmail = config('mail.from.address'))

<section class="bg-white border-b border-[#e4e2e0]">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <div class="max-w-3xl">
            <p class="text-sm font-semibold text-[#2557a7] mb-3">CONTACT</p>
            <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-[#2d2d2d]">How can we help?</h1>
            <p class="mt-5 text-lg leading-8 text-[#595959]">
                Contact Best Way Jobs for account questions, incorrect job information, privacy requests, technical issues or general feedback.
            </p>
        </div>
    </div>
</section>

<section class="bg-[#f7f7f7]">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
        <div class="grid gap-8 lg:grid-cols-[1fr_1.1fr]">
            <div class="indeed-card p-6 sm:p-8 h-fit">
                <div class="w-11 h-11 rounded-lg bg-[#eef4fb] text-[#2557a7] flex items-center justify-center mb-5">
                    <i class="las la-envelope text-2xl"></i>
                </div>
                <h2 class="text-xl font-bold text-[#2d2d2d]">Support email</h2>
                <p class="mt-2 text-[#595959] leading-7">Include enough detail for us to understand the issue. For a job listing problem, include the job title and page link.</p>

                @if($supportEmail)
                    <a href="mailto:{{ $supportEmail }}" class="indeed-link inline-flex items-center gap-2 mt-5 break-all">
                        <i class="las la-envelope"></i>
                        {{ $supportEmail }}
                    </a>
                @endif
            </div>

            <div class="indeed-card p-6 sm:p-8">
                <h2 class="text-xl font-bold text-[#2d2d2d]">What to include in your message</h2>
                <div class="mt-6 divide-y divide-[#e4e2e0]">
                    <div class="py-4 first:pt-0">
                        <h3 class="font-bold text-[#2d2d2d]">Account or sign-in issue</h3>
                        <p class="mt-1 text-sm leading-6 text-[#595959]">Mention the email address associated with your account, but never send your password.</p>
                    </div>
                    <div class="py-4">
                        <h3 class="font-bold text-[#2d2d2d]">Job listing correction</h3>
                        <p class="mt-1 text-sm leading-6 text-[#595959]">Send the job title, employer name, listing URL and the information that appears incorrect.</p>
                    </div>
                    <div class="py-4">
                        <h3 class="font-bold text-[#2d2d2d]">Privacy request</h3>
                        <p class="mt-1 text-sm leading-6 text-[#595959]">Clearly state whether you want to access, correct or delete information associated with your account.</p>
                    </div>
                    <div class="py-4 last:pb-0">
                        <h3 class="font-bold text-[#2d2d2d]">Technical problem</h3>
                        <p class="mt-1 text-sm leading-6 text-[#595959]">Include the page URL, what you expected to happen and what happened instead. A screenshot can also be useful.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 rounded-xl border border-[#d4d2d0] bg-white p-6">
            <h2 class="font-bold text-[#2d2d2d]">Before contacting support</h2>
            <p class="mt-2 text-sm leading-6 text-[#595959]">
                If an application link opens another website, that employer or third-party site controls the application process. Best Way Jobs cannot manage accounts, applications or hiring decisions on external websites.
            </p>
        </div>
    </div>
</section>
@endsection