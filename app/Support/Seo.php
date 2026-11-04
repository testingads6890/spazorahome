<?php

namespace App\Support;

use App\Models\Setting;

/** Site-wide search-engine settings (Admin > SEO), read safely: a missing table or key never breaks a page. */
class Seo
{
    private static ?array $cache = null;

    public const DEFAULT_DESCRIPTION = 'SpazoraHome - quality furniture and home decor, delivered across India';

    public static function reset(): void
    {
        self::$cache = null;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        if (self::$cache === null) {
            try {
                self::$cache = Setting::whereIn('key', [
                    'site_title', 'site_description', 'site_keywords', 'og_image', 'google_analytics_id',
                    'google_site_verification', 'bing_site_verification', 'seo_noindex', 'robots_extra',
                ])->get()->mapWithKeys(fn ($s) => [$s->key => is_array($s->value) ? '' : (string) $s->value])->all();
            } catch (\Throwable) {
                self::$cache = [];
            }
        }
        $v = trim((string) (self::$cache[$key] ?? ''));

        return $v !== '' ? $v : $default;
    }

    public static function description(): string
    {
        return (string) self::get('site_description', self::DEFAULT_DESCRIPTION);
    }

    public static function keywords(): string
    {
        return (string) self::get('site_keywords', 'furniture, interior, home decor');
    }

    public static function analyticsId(): ?string
    {
        $id = self::get('google_analytics_id');

        return $id && preg_match('/^[A-Za-z0-9-]{4,20}$/', $id) ? $id : null;
    }

    /** Verification codes are pasted by admins either as the bare code or as the whole <meta> tag. */
    public static function verification(string $key): ?string
    {
        $v = self::get($key);
        if ($v && preg_match('/content=["\']([^"\']+)["\']/', $v, $m)) {
            $v = $m[1];
        }

        return $v && preg_match('/^[\w\-.]{8,120}$/', $v) ? $v : null;
    }

    public static function blocked(): bool
    {
        return self::get('seo_noindex') === '1';
    }

    /** Pages that belong to one person or to the shop team never belong in search results. */
    public static function privatePage(): bool
    {
        return request()->routeIs('admin.*', 'vendor.*', 'customer.*', 'login', 'register', 'password.*', 'verification.*', 'checkout.*', 'payments.*', 'dashboard', 'customer.cart.*');
    }

    public static function robotsMeta(): ?string
    {
        return self::blocked() || self::privatePage() ? 'noindex, nofollow' : null;
    }

    /** One clean meta description: tags removed, spaces collapsed, cut at a word near 160 characters. */
    public static function snippet(?string $html, int $max = 160): string
    {
        return Html::text($html, $max);
    }

    public static function robotsTxt(): string
    {
        $base = rtrim(request()->getBasePath(), '/');
        $lines = ['User-agent: *'];
        if (self::blocked()) {
            $lines[] = 'Disallow: /';
        } else {
            foreach (['/admin', '/vendor', '/customer', '/cart', '/checkout', '/login', '/register', '/forgot-password', '/reset-password', '/email', '/payments', '/language', '/currency'] as $p) {
                $lines[] = 'Disallow: ' . $base . $p;
            }
            $lines[] = 'Disallow: ' . $base . '/*?*sort=';
            $lines[] = 'Disallow: ' . $base . '/*?*q=';
            $lines[] = 'Allow: ' . ($base ?: '') . '/';
        }
        foreach (preg_split('/\R/', (string) self::get('robots_extra', '')) as $extra) {
            $extra = trim($extra);
            if ($extra !== '' && preg_match('/^(Disallow|Allow|Crawl-delay|User-agent)\s*:/i', $extra)) {
                $lines[] = $extra;
            }
        }
        $lines[] = '';
        $lines[] = 'Sitemap: ' . url('/sitemap.xml');

        return implode("\n", $lines) . "\n";
    }
}
