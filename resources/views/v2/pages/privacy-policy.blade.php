@extends('v2.layouts.app')

@section('content')
<section class="bg-white border-b border-[#e4e2e0]">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <p class="text-sm font-semibold text-[#2557a7] mb-3">LEGAL</p>
        <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-[#2d2d2d]">Privacy Policy</h1>
        <p class="mt-4 text-[#595959] leading-7">This policy explains what information Best Way Jobs may process when you use the website and how that information is handled.</p>
        <p class="mt-3 text-sm text-[#767676]">Last updated: August 16, 2026</p>
    </div>
</section>

<section class="bg-[#f7f7f7]">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
        <div class="indeed-card p-6 sm:p-10 space-y-10">
            <section>
                <h2 class="text-xl font-bold text-[#2d2d2d]">1. Information you provide</h2>
                <p class="mt-3 text-[#595959] leading-7">If you create an account or complete profile features, the platform may store information you submit, such as your name, email address, contact details, location, professional information, work experience, skills, preferences and other profile content.</p>
                <p class="mt-3 text-[#595959] leading-7">Passwords are not stored in plain text. Authentication credentials are protected using the security mechanisms provided by the application framework.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#2d2d2d]">2. Information generated through use</h2>
                <p class="mt-3 text-[#595959] leading-7">The service may process technical and usage information required to operate the site, such as session data, IP-related request information, browser or device information, job views, searches, saved items, application-related activity and timestamps.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#2d2d2d]">3. How information is used</h2>
                <ul class="mt-3 list-disc pl-6 space-y-2 text-[#595959] leading-7">
                    <li>Provide and maintain account, profile, search and job-discovery features.</li>
                    <li>Show relevant job listings and remember selected preferences where applicable.</li>
                    <li>Protect the service, prevent abuse and troubleshoot technical problems.</li>
                    <li>Understand platform usage and improve functionality and performance.</li>
                    <li>Respond to support, privacy and account-related requests.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#2d2d2d]">4. Cookies and sessions</h2>
                <p class="mt-3 text-[#595959] leading-7">Best Way Jobs may use cookies or similar browser storage for essential functions such as authentication, security, session continuity and user preferences. Your browser may allow you to remove or block cookies, but some site features may stop working correctly.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#2d2d2d]">5. Job listings and external websites</h2>
                <p class="mt-3 text-[#595959] leading-7">Some job listings may contain application links that take you to an employer or third-party website. Once you leave Best Way Jobs, the external site's own privacy policy and data practices apply. Best Way Jobs does not control how those external services process information you submit to them.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#2d2d2d]">6. Service providers</h2>
                <p class="mt-3 text-[#595959] leading-7">Technical service providers may process limited information when necessary to host, secure, maintain or operate the platform. Information should only be shared to the extent reasonably required for those services or where disclosure is required by law.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#2d2d2d]">7. Data retention and security</h2>
                <p class="mt-3 text-[#595959] leading-7">Information may be retained for as long as needed to provide the service, maintain legitimate operational records, resolve disputes, protect the platform or satisfy applicable obligations. Reasonable technical and organizational safeguards are used, but no internet service can guarantee absolute security.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#2d2d2d]">8. Your choices</h2>
                <p class="mt-3 text-[#595959] leading-7">Depending on the available account features and applicable law, you may be able to review or update profile information directly. You may also contact Best Way Jobs to ask about access, correction or deletion of personal information associated with your account.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#2d2d2d]">9. Changes to this policy</h2>
                <p class="mt-3 text-[#595959] leading-7">This Privacy Policy may be updated when the platform, its features or applicable requirements change. The date at the top of this page shows when this version was last updated.</p>
            </section>

            <section class="border-t border-[#e4e2e0] pt-8">
                <h2 class="text-xl font-bold text-[#2d2d2d]">10. Contact</h2>
                <p class="mt-3 text-[#595959] leading-7">For questions about this policy or a privacy request, use the Best Way Jobs contact page.</p>
                <a href="{{ route('contact') }}" class="btn-secondary inline-flex mt-5">Contact Best Way Jobs</a>
            </section>
        </div>
    </div>
</section>
@endsection