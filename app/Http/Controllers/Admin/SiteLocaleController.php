<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SettingType;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SiteLocaleController extends Controller
{
    /**
     * Set the language used by the admin panel and as the website default.
     */
    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('settings.update'), 403);

        $validated = $request->validate([
            'locale' => ['required', 'in:en,bn'],
        ]);

        Setting::updateOrCreate(
            ['key' => 'site_locale'],
            [
                'label' => 'Site Language',
                'value' => $validated['locale'],
                'type' => SettingType::String,
                'group' => 'general',
                'is_public' => true,
                'is_system' => false,
            ],
        );

        Cache::forget('setting.site_locale');

        return back();
    }
}
