@extends('v2.layouts.app')

@section('content')
<section class="bg-white border-b border-[#e4e2e0]">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <p class="text-sm font-semibold text-[#2557a7] mb-3">LEGAL</p>
        <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-[#2d2d2d]">Terms of Use</h1>
        <p class="mt-4 text-[#595959] leading-7">These terms describe the rules that apply when you access or use Best Way Jobs.</p>
        <p class="mt-3 text-sm text-[#767676]">Last updated: August 16, 2026</p>
    </div>
</section>

<section class="bg-[#f7f7f7]">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
        <div class="indeed-card p-6 sm:p-10 space-y-10">
            <section>
                <h2 class="text-xl font-bold text-[#2d2d2d]">1. Using Best Way Jobs</h2>
                <p class="mt-3 text-[#595959] leading-7">Best Way Jobs provides job discovery, search, filtering, profile and related platform features. By using the service, you agree to use it lawfully and in a way that does not interfere with other users or the operation of the website.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#2d2d2d]">2. Accounts</h2>
                <p class="mt-3 text-[#595959] leading-7">If you create an account, you are responsible for providing accurate information, keeping your sign-in credentials secure and for activity carried out through your account. Do not share passwords or attempt to access another person's account.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#2d2d2d]">3. Acceptable use</h2>
                <p class="mt-3 text-[#595959] leading-7">You must not use the platform to:</p>
                <ul class="mt-3 list-disc pl-6 space-y-2 text-[#595959] leading-7">
                    <li>Break applicable laws or regulations.</li>
                    <li>Submit intentionally false, misleading or harmful information.</li>
                    <li>Attempt unauthorized access to accounts, systems or data.</li>
                    <li>Disrupt, overload, probe or bypass the security of the service.</li>
                    <li>Use automated collection or scraping in a way that harms the platform or violates applicable rights.</li>
                    <li>Impersonate another person, employer or organization.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#2d2d2d]">4. Job listings</h2>
                <p class="mt-3 text-[#595959] leading-7">Job information may be provided, imported or linked from different sources. Listings can change, expire or contain information controlled by an employer or third party. Best Way Jobs does not guarantee that every listing is complete, current or still available.</p>
                <p class="mt-3 text-[#595959] leading-7">Users should independently review a role, employer and application destination before sharing personal information or making employment-related decisions.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#2d2d2d]">5. External application links</h2>
                <p class="mt-3 text-[#595959] leading-7">A job's Apply button may send you to an employer or third-party website. Best Way Jobs does not control those sites, their availability, their application process, their content or their hiring decisions. Their own terms and privacy policies apply after you leave this website.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#2d2d2d]">6. No employment guarantee</h2>
                <p class="mt-3 text-[#595959] leading-7">Best Way Jobs helps users discover opportunities but does not guarantee interviews, offers, employment, salary, employer responses or any particular outcome from using the platform.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#2d2d2d]">7. Platform availability</h2>
                <p class="mt-3 text-[#595959] leading-7">Features may be changed, improved, suspended or removed as the service evolves. Although reasonable efforts may be made to keep the platform available, uninterrupted or error-free operation cannot be guaranteed.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#2d2d2d]">8. Intellectual property</h2>
                <p class="mt-3 text-[#595959] leading-7">The Best Way Jobs name, interface, original platform content and related materials may be protected by intellectual-property laws. Third-party employer names, logos, job descriptions and other third-party materials remain subject to the rights of their respective owners.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#2d2d2d]">9. Privacy</h2>
                <p class="mt-3 text-[#595959] leading-7">Use of the platform is also subject to the <a href="{{ route('privacy-policy') }}" class="indeed-link">Privacy Policy</a>, which explains how information may be processed when you use Best Way Jobs.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#2d2d2d]">10. Suspension or restriction</h2>
                <p class="mt-3 text-[#595959] leading-7">Access may be restricted or suspended when reasonably necessary to protect the platform, users or third parties, or when an account materially violates these terms.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#2d2d2d]">11. Disclaimer and limitation</h2>
                <p class="mt-3 text-[#595959] leading-7">The service is provided on an “as available” basis. To the extent permitted by applicable law, Best Way Jobs is not responsible for losses caused by reliance on inaccurate third-party job information, external websites, employer actions, application outcomes or interruptions outside the platform's reasonable control.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#2d2d2d]">12. Changes to these terms</h2>
                <p class="mt-3 text-[#595959] leading-7">These terms may be updated when the platform or applicable requirements change. The latest version will be posted on this page with an updated date.</p>
            </section>

            <section class="border-t border-[#e4e2e0] pt-8">
                <h2 class="text-xl font-bold text-[#2d2d2d]">13. Contact</h2>
                <p class="mt-3 text-[#595959] leading-7">For questions about these terms, use the Best Way Jobs contact page.</p>
                <a href="{{ route('contact') }}" class="btn-secondary inline-flex mt-5">Contact Best Way Jobs</a>
            </section>
        </div>
    </div>
</section>
@endsection