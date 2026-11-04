<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ThemeController extends Controller
{
    private const LOGO_DIR = 'site';

    public function index()
    {
        $settings = [
            // Show exactly what the storefront is using, so the form never disagrees with the live site.
            'primary_color' => Theme::primary(),
            'secondary_color' => Theme::secondary(),
            'success_color' => Setting::get('success_color', '#198754'),
            'danger_color' => Setting::get('danger_color', '#dc3545'),
            'warning_color' => Setting::get('warning_color', '#ffc107'),
            'info_color' => Setting::get('info_color', '#0dcaf0'),

            'sidebar_style' => Setting::get('sidebar_style', 'dark'),
            'navbar_style' => Setting::get('navbar_style', 'light'),
            'footer_style' => Setting::get('footer_style', 'dark'),
        ];

        $logoUrl = Theme::logoUrl();
        $hasCustomLogo = Setting::get('site_logo', '') !== '';

        return view('admin.theme.index', compact('settings', 'logoUrl', 'hasCustomLogo'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'success_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'danger_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'warning_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'info_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],

            'sidebar_style' => 'required|in:dark,light',
            'navbar_style' => 'required|in:dark,light',
            'footer_style' => 'required|in:dark,light',

            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'remove_logo' => 'nullable|boolean',
        ]);

        $logoFile = $validated['logo'] ?? null;
        $removeLogo = $request->boolean('remove_logo');
        unset($validated['logo'], $validated['remove_logo']);

        foreach ($validated as $key => $value) {
            Setting::set($key, strtolower($value), 'string');
        }

        if ($logoFile || $removeLogo) {
            $previous = Setting::get('site_logo', '');
            if ($previous !== '' && str_starts_with($previous, self::LOGO_DIR . '/')) {
                Storage::disk('public')->delete($previous);
            }

            $path = $logoFile ? $logoFile->store(self::LOGO_DIR, 'public') : '';
            Setting::set('site_logo', $path, 'string');
        }

        Theme::reset();

        return redirect()
            ->route('admin.theme.index')
            ->with('success', 'Theme settings updated successfully.');
    }
}
