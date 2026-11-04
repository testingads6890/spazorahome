<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaticPage;
use Illuminate\Http\Request;

class StaticPageController extends Controller
{
    public function index()
    {
        $pages = StaticPage::paginate(20);
        return view('admin.static-pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.static-pages.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:static_pages',
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        StaticPage::create($request->all());

        return redirect()->route('admin.static-pages.index')
            ->with('success', 'Static page created successfully.');
    }

    public function edit(StaticPage $staticPage)
    {
        return view('admin.static-pages.edit', compact('staticPage'));
    }

    public function update(Request $request, StaticPage $staticPage)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:static_pages,slug,' . $staticPage->id,
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $staticPage->update($request->all());

        return redirect()->route('admin.static-pages.index')
            ->with('success', 'Static page updated successfully.');
    }

    public function destroy(StaticPage $staticPage)
    {
        $staticPage->delete();
        return redirect()->route('admin.static-pages.index')
            ->with('success', 'Static page deleted successfully.');
    }

    public function show($slug)
    {
        return $this->page($slug);
    }

    public function contact()
    {
        return $this->page('contact-us');
    }

    public function terms()
    {
        return $this->page('terms-conditions');
    }

    public function privacy()
    {
        return $this->page('privacy-policy');
    }

    public function shipping()
    {
        return $this->page('shipping-policy');
    }

    public function returns()
    {
        return $this->page('return-policy');
    }

    public function faq()
    {
        return $this->page('faq');
    }

    /**
     * A page saved in Admin > Static pages wins. Until one exists for the slug, the built-in page in
     * resources/views/static-pages/defaults is shown, so the storefront links never lead to a 404.
     */
    private function page(string $slug)
    {
        $page = StaticPage::where('slug', $slug)->where('is_active', true)->first();

        if ($page && trim(strip_tags((string) $page->content)) !== '') {
            return view('static-pages.show', compact('page'));
        }

        $default = 'static-pages.defaults.' . $slug;
        abort_unless(preg_match('/^[a-z0-9-]+$/', $slug) && view()->exists($default), 404);

        return view($default, ['shop' => $this->shopFacts()]);
    }

    /** Figures the built-in pages quote. They come from config/shop.php so a policy change is made in one place. */
    private function shopFacts(): array
    {
        return [
            'name' => config('shop.seller.name') ?: 'SpazoraHome',
            'address' => (string) config('shop.seller.address'),
            'state' => config('shop.seller.state') ?: 'Maharashtra',
            'gstin' => (string) config('shop.seller.gstin'),
            'email' => (string) config('shop.contact.email'),
            'phone' => (string) config('shop.contact.phone'),
            'hours' => config('shop.contact.hours') ?: 'Monday to Saturday, 10 am to 6 pm IST',
            'free_over' => "\u{20B9}" . number_format((float) config('shop.free_shipping_over', 5000)),
            'fee' => "\u{20B9}" . number_format((float) config('shop.shipping_fee', 149)),
            'return_days' => (int) config('shop.policies.return_days', 7),
            'damage_hours' => (int) config('shop.policies.damage_report_hours', 48),
            'refund_days' => config('shop.policies.refund_days') ?: '5 to 7',
            'dispatch_days' => config('shop.policies.dispatch_days') ?: '2 to 4',
            'delivery_metro' => config('shop.policies.delivery_days_metro') ?: '5 to 8',
            'delivery_other' => config('shop.policies.delivery_days_other') ?: '7 to 14',
            'export' => (bool) config('shop.export.enabled'),
            'updated' => config('shop.policies.updated') ?: '11 October 2026',
        ];
    }
}
