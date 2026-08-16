<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Services\SeoMetaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactPageController extends Controller
{
    public function __invoke(SeoMetaService $seoService)
    {
        $meta = $seoService->generateMeta();

        return view('v2.pages.contact', [
            'meta' => $meta,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        ContactMessage::create([
            ...$validated,
            'user_id' => $request->user()?->id,
            'status' => 'new',
            'ip_address' => $request->ip(),
        ]);

        return redirect()
            ->route('contact')
            ->with('contact_success', 'Your message has been submitted successfully. Our team will review it shortly.');
    }
}
