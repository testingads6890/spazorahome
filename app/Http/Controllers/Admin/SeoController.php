<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SeoController extends Controller
{
    public function index()
    {
        $settings = [
            'site_title' => \App\Models\Setting::get('site_title', 'SpazoraHome Marketplace'),
            'site_description' => \App\Models\Setting::get('site_description', 'Your one-stop shop for interior furniture'),
            'site_keywords' => \App\Models\Setting::get('site_keywords', 'furniture, interior, home decor'),
            'og_image' => \App\Models\Setting::get('og_image', ''),
            'google_analytics_id' => \App\Models\Setting::get('google_analytics_id', ''),
            'google_site_verification' => \App\Models\Setting::get('google_site_verification', ''),
            'bing_site_verification' => \App\Models\Setting::get('bing_site_verification', ''),
            'seo_noindex' => \App\Models\Setting::get('seo_noindex', '0'),
            'robots_extra' => \App\Models\Setting::get('robots_extra', ''),
        ];
        return view('admin.seo.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'site_title' => 'required|string|max:255',
            'site_description' => 'required|string|max:500',
            'site_keywords' => 'nullable|string|max:500',
            'og_image' => 'nullable|string',
            'google_analytics_id' => 'nullable|string|max:50',
            'google_site_verification' => 'nullable|string|max:100',
            'bing_site_verification' => 'nullable|string|max:100',
            'seo_noindex' => 'nullable|boolean',
            'robots_extra' => 'nullable|string|max:2000',
        ]);

        // Only the fields on this page are saved (never the form token or anything else that was posted).
        foreach (['site_title', 'site_description', 'site_keywords', 'og_image', 'google_analytics_id', 'google_site_verification', 'bing_site_verification', 'robots_extra'] as $key) {
            \App\Models\Setting::set($key, (string) $request->input($key, ''), 'string');
        }
        \App\Models\Setting::set('seo_noindex', $request->boolean('seo_noindex') ? '1' : '0', 'string');
        \App\Support\Seo::reset();

        return redirect()->route('admin.seo.index')
            ->with('success', 'SEO settings updated successfully.');
    }

    /** /sitemap.xml: every page a search engine should know, at its readable address. */
    public function sitemap()
    {
        $today = now()->format('Y-m-d');
        $urls = [
            ['loc' => route('home'), 'lastmod' => $today, 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => route('search'), 'lastmod' => $today, 'changefreq' => 'daily', 'priority' => '0.9'],
        ];

        foreach (\App\Models\Category::where('status', true)->orderBy('name')->get() as $category) {
            $urls[] = ['loc' => $category->shopUrl(), 'lastmod' => optional($category->updated_at)->format('Y-m-d') ?: $today, 'changefreq' => 'weekly', 'priority' => '0.8'];
        }

        $products = \App\Models\Product::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('vendor_id')->orWhere('vendor_status', 'approved'))
            ->orderBy('id')->get(['id', 'slug', 'updated_at']);
        foreach ($products as $product) {
            $urls[] = ['loc' => route('products.show', $product), 'lastmod' => optional($product->updated_at)->format('Y-m-d') ?: $today, 'changefreq' => 'weekly', 'priority' => '0.6'];
        }

        if (\App\Support\Stores::enabled()) {
            $urls[] = ['loc' => route('stores.index'), 'lastmod' => $today, 'changefreq' => 'weekly', 'priority' => '0.5'];
        }
        foreach (\App\Support\Stores::enabled() ? \App\Models\Vendor::where('status', 'approved')->where('is_active', true)->whereNotNull('slug')->get(['slug', 'updated_at']) : collect() as $vendor) {
            $urls[] = ['loc' => route('stores.show', $vendor->slug), 'lastmod' => optional($vendor->updated_at)->format('Y-m-d') ?: $today, 'changefreq' => 'weekly', 'priority' => '0.5'];
        }

        $urls[] = ['loc' => route('blog.index'), 'lastmod' => $today, 'changefreq' => 'weekly', 'priority' => '0.5'];
        $posts = \App\Models\Blog::where('is_published', true)->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))->orderBy('id')->get(['slug', 'updated_at']);
        foreach ($posts as $post) {
            $urls[] = ['loc' => route('blog.show', $post->slug), 'lastmod' => optional($post->updated_at)->format('Y-m-d') ?: $today, 'changefreq' => 'monthly', 'priority' => '0.5'];
        }

        foreach (['about-us', 'contact-us', 'faq', 'shipping', 'returns', 'privacy', 'terms', 'vendor.register'] as $name) {
            $urls[] = ['loc' => route($name), 'lastmod' => $today, 'changefreq' => 'monthly', 'priority' => '0.4'];
        }

        return response()->view('admin.seo.sitemap', compact('urls'))
            ->header('Content-Type', 'application/xml');
    }

    /** /robots.txt: what search engines may crawl, plus the sitemap address. */
    public function robots()
    {
        return response(\App\Support\Seo::robotsTxt(), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
