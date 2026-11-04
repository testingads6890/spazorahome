<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Site-wide look set in Admin > Theme: brand colours and logo.
 * Every layout (storefront, customer account, admin, seller) reads from here so one save applies everywhere.
 * Reads are safe: a missing table or bad value falls back to the defaults and never breaks a page.
 */
class Theme
{
    public const DEFAULT_PRIMARY = '#c8643c';   // accent: buttons, highlights (storefront default)
    public const DEFAULT_SECONDARY = '#1f3d36'; // brand: header, footer, panel navigation (storefront default)

    private static ?array $cache = null;

    public static function reset(): void
    {
        self::$cache = null;
    }

    /** @return array<string, string> */
    private static function values(): array
    {
        if (self::$cache === null) {
            try {
                self::$cache = Setting::whereIn('key', ['primary_color', 'secondary_color', 'success_color', 'danger_color', 'site_logo'])
                    ->get()
                    ->mapWithKeys(fn ($s) => [$s->key => is_string($s->value) ? trim($s->value) : ''])
                    ->all();
            } catch (\Throwable) {
                self::$cache = [];
            }
        }

        return self::$cache;
    }

    public static function primary(): string
    {
        return self::hex(self::values()['primary_color'] ?? '', self::DEFAULT_PRIMARY);
    }

    public static function secondary(): string
    {
        return self::hex(self::values()['secondary_color'] ?? '', self::DEFAULT_SECONDARY);
    }

    /** Logo uploaded in Admin > Theme, or the bundled default when none is set. */
    /** True when an admin has uploaded a logo and its file is really there (a missing file counts as no logo). */
    public static function hasLogo(): bool
    {
        $path = (string) (self::values()['site_logo'] ?? '');

        return $path !== '' && \Illuminate\Support\Facades\Storage::disk('public')->exists($path);
    }

    public static function logoUrl(): string
    {
        return self::hasLogo()
            ? asset('storage/' . ltrim((string) self::values()['site_logo'], '/'))
            : asset('images/logo.png');
    }

    /**
     * Inline :root overrides placed after the stylesheets, so the saved colours win over the defaults in the CSS files.
     */
    public static function cssVariables(): string
    {
        $primary = self::primary();
        $secondary = self::secondary();

        $vars = [
            '--accent' => $primary,
            '--accent-600' => self::shade($primary, -0.12),
            '--accent-100' => self::mix($primary, '#ffffff', 0.1),
            '--brand' => $secondary,
            '--brand-600' => self::shade($secondary, 0.12),
            '--brand-100' => self::mix($secondary, '#ffffff', 0.1),
            '--bs-primary' => $secondary,
            '--bs-primary-rgb' => implode(', ', self::rgb($secondary)),
            // Darker shade for the footer and dark panels, and the secondary colour as R, G, B for soft shadows.
            '--brand-dark' => self::shade($secondary, -0.35),
            '--brand-rgb' => implode(', ', self::rgb($secondary)),
            '--bs-link-color' => $secondary,
            '--bs-link-hover-color' => $primary,
            '--success' => self::hex(self::values()['success_color'] ?? '', '#198754'),
            '--danger' => self::hex(self::values()['danger_color'] ?? '', '#dc3545'),
        ];

        $css = ':root{';
        foreach ($vars as $name => $value) {
            $css .= $name . ':' . $value . ';';
        }

        return $css . '}';
    }

    private static function hex(string $value, string $default): string
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? strtolower($value) : $default;
    }

    /** @return array{0:int,1:int,2:int} */
    private static function rgb(string $hex): array
    {
        return [hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2))];
    }

    private static function toHex(array $rgb): string
    {
        return sprintf('#%02x%02x%02x', ...array_map(fn ($c) => max(0, min(255, (int) round($c))), $rgb));
    }

    /** Positive $amount lightens towards white, negative darkens towards black. */
    private static function shade(string $hex, float $amount): string
    {
        $target = $amount >= 0 ? 255 : 0;

        return self::toHex(array_map(fn ($c) => $c + ($target - $c) * abs($amount), self::rgb($hex)));
    }

    /** Blend $hex with $other; $weight is the share of $hex. */
    private static function mix(string $hex, string $other, float $weight): string
    {
        $a = self::rgb($hex);
        $b = self::rgb($other);

        return self::toHex([
            $a[0] * $weight + $b[0] * (1 - $weight),
            $a[1] * $weight + $b[1] * (1 - $weight),
            $a[2] * $weight + $b[2] * (1 - $weight),
        ]);
    }
}
