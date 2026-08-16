<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use Illuminate\Contracts\View\View;

class AdvertisementController extends Controller
{
    public function show(Advertisement $advertisement): View
    {
        abort_unless(
            Advertisement::query()
                ->currentlyActive()
                ->whereKey($advertisement->getKey())
                ->exists(),
            404
        );

        return view('advertisements.show', compact('advertisement'));
    }
}
