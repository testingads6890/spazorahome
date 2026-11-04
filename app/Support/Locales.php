<?php

namespace App\Support;

use App\Models\Language;

/**
 * The site languages a visitor can pick. A language is offered only when it is switched on in Admin > Languages
 * AND its translation files exist in lang/<code>, so a half-added language can never show a page of raw keys.
 */
class Locales
{
    public const COOKIE = 'lang';

    private static $available = null;

    /** @return \Illuminate\Support\Collection<int, Language> */
    public static function available()
    {
        return self::$available ??= Language::where('is_active', true)->orderBy('sort_order')->orderBy('code')->get()
            ->filter(fn (Language $l) => is_dir(lang_path($l->code)))->values();
    }

    public static function switchable(): bool
    {
        return self::available()->count() > 1;
    }

    public static function isAvailable(string $code): bool
    {
        return self::available()->contains('code', $code);
    }

    public static function current(): string
    {
        return app()->getLocale();
    }

    /** ltr or rtl for the active language. */
    public static function direction(): string
    {
        return self::available()->firstWhere('code', app()->getLocale())?->direction === 'rtl' ? 'rtl' : 'ltr';
    }

    public static function reset(): void
    {
        self::$available = null;
    }
}
