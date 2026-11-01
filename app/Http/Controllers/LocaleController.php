<?php

namespace App\Http\Controllers;

use App\Support\LocaleManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Remember the shopper's language on this browser.
     * The admin store default is left unchanged.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'in:en,bn'],
        ]);

        cookie()->queue(cookie(
            LocaleManager::COOKIE,
            $validated['locale'],
            60 * 24 * 365,
        ));

        return back();
    }
}
