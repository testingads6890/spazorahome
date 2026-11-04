<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Language;
use App\Models\Product;
use App\Models\User;
use App\Support\Locales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LanguageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\LanguageSeeder::class);
        Locales::reset();
    }

    private function hindi(): static
    {
        return $this->withUnencryptedCookie(Locales::COOKIE, 'hi');
    }

    private function product(array $o = []): Product
    {
        $cat = Category::firstOrCreate(['slug' => 'sofas'], ['name' => 'Sofas']);

        return Product::create(array_merge(['category_id' => $cat->id, 'name' => 'Oak Sofa', 'slug' => 'oak-' . uniqid(), 'description' => 'x', 'sku' => 'S-' . uniqid(), 'price' => 1000, 'stock_quantity' => 5, 'is_active' => true], $o));
    }

    private function customer(): User
    {
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $u = User::factory()->create();
        $u->assignRole('customer');

        return $u;
    }

    public function test_hindi_and_english_files_have_exactly_the_same_keys_and_placeholders(): void
    {
        $files = array_map('basename', glob(lang_path('en/*.php')));
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $this->assertFileExists(lang_path("hi/{$file}"), "hi/{$file} is missing");
            $en = Arr::dot(require lang_path("en/{$file}"));
            $hi = Arr::dot(require lang_path("hi/{$file}"));

            $this->assertSame([], array_values(array_diff(array_keys($en), array_keys($hi))), "{$file}: keys missing in Hindi");
            $this->assertSame([], array_values(array_diff(array_keys($hi), array_keys($en))), "{$file}: keys only in Hindi");

            foreach ($en as $key => $text) {
                preg_match_all('/:[a-z]+/', $text, $a);
                preg_match_all('/:[a-z]+/', $hi[$key], $b);
                $this->assertEqualsCanonicalizing($a[0], $b[0], "{$file}: placeholders differ for {$key}");
                $this->assertNotSame('', trim($hi[$key]), "{$file}: empty Hindi text for {$key}");
            }
        }
    }

    public function test_english_is_the_default_and_hindi_is_offered_in_the_switcher(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee('Track order')->assertSee('Sign in')->assertDontSee('ऑर्डर ट्रैक करें')
            ->assertSee('name="language"', false)->assertSee('हिन्दी')->assertSee('<html lang="en"', false);
    }

    public function test_choosing_hindi_sets_a_cookie_and_the_header_footer_and_html_lang_follow(): void
    {
        $this->post(route('language.switch'), ['language' => 'hi'])->assertRedirect()->assertCookie(Locales::COOKIE, 'hi', false);

        $this->hindi()->get(route('home'))->assertOk()
            ->assertSee('<html lang="hi"', false)
            ->assertSee('ऑर्डर ट्रैक करें')->assertSee('साइन इन')->assertSee('हमारे बारे में')->assertSee('सुरक्षित चेकआउट')
            ->assertDontSee('Track order')->assertDontSee('shop.nav.');   // no raw keys leaking through
    }

    public function test_product_cards_are_translated(): void
    {
        $this->product();
        $this->product(['stock_quantity' => 0, 'name' => 'Gone']);

        $this->hindi()->get(route('search'))->assertOk()->assertSee('कार्ट में जोड़ें')->assertSee('स्टॉक खत्म')->assertDontSee('Add to cart');
    }

    public function test_cart_page_and_its_messages_are_translated(): void
    {
        $u = $this->customer();
        $p = $this->product();
        $this->hindi()->actingAs($u)->post(route('customer.cart.store', $p), ['quantity' => 2])->assertSessionHas('success', 'आइटम कार्ट में जोड़ दिया गया।');

        $this->hindi()->actingAs($u)->get(route('customer.cart.index'))->assertOk()
            ->assertSee('शॉपिंग कार्ट')->assertSee('2 आइटम')->assertSee('ऑर्डर सारांश')->assertSee('चेकआउट पर जाएँ')->assertSee('कूपन कोड')
            ->assertSee('मुफ़्त डिलीवरी के लिए')->assertDontSee('Proceed to checkout');

        $this->hindi()->actingAs($u)->post(route('customer.cart.store', $this->product(['stock_quantity' => 1])), ['quantity' => 5])
            ->assertSessionHas('error', 'इस आइटम की सिर्फ़ 1 इकाई स्टॉक में है।');
    }

    public function test_empty_cart_and_checkout_are_translated(): void
    {
        $u = $this->customer();
        $this->hindi()->actingAs($u)->get(route('customer.cart.index'))->assertSee('आपका कार्ट खाली है')->assertSee('खरीदारी शुरू करें');

        Address::create(['user_id' => $u->id, 'type' => 'shipping', 'first_name' => 'A', 'last_name' => 'B', 'address_line_1' => '1 Rd', 'city' => 'Pune', 'state' => 'Maharashtra', 'postal_code' => '411001', 'country' => 'India']);
        $this->actingAs($u)->post(route('customer.cart.store', $this->product()), ['quantity' => 1]);
        $this->hindi()->actingAs($u)->get(route('customer.checkout.index'))->assertOk()
            ->assertSee('डिलीवरी का पता')->assertSee('भुगतान का तरीका')->assertSee('कैश ऑन डिलीवरी')->assertSee('ऑर्डर करें')
            ->assertSee('बिलिंग का पता डिलीवरी के पते जैसा ही है')->assertSee('ऑर्डर नोट')->assertDontSee('Place order');
    }

    public function test_placing_an_order_in_hindi_still_works_and_amounts_stay_in_rupees(): void
    {
        $u = $this->customer();
        $a = Address::create(['user_id' => $u->id, 'type' => 'shipping', 'first_name' => 'A', 'last_name' => 'B', 'address_line_1' => '1 Rd', 'city' => 'Pune', 'state' => 'Maharashtra', 'postal_code' => '411001', 'country' => 'India']);
        $this->actingAs($u)->post(route('customer.cart.store', $this->product(['price' => 1000])), ['quantity' => 1]);

        $this->hindi()->actingAs($u)->post(route('customer.checkout.store'), ['shipping_address_id' => $a->id, 'billing_address_id' => $a->id, 'payment_method' => 'cod'])
            ->assertRedirect()->assertSessionHas('success', 'ऑर्डर सफलतापूर्वक हो गया।');
        $this->assertSame('INR', \App\Models\Order::firstOrFail()->currency);
    }

    public function test_unavailable_languages_are_refused_and_a_stale_cookie_falls_back_to_english(): void
    {
        $this->post(route('language.switch'), ['language' => 'fr'])->assertStatus(422);        // not a language at all
        Language::create(['name' => 'Tamil', 'code' => 'ta', 'direction' => 'ltr', 'is_active' => true, 'sort_order' => 5]);
        Locales::reset();
        $this->post(route('language.switch'), ['language' => 'ta'])->assertStatus(422);        // switched on, but no lang/ta files
        $this->post(route('language.switch'), ['language' => ''])->assertSessionHasErrors('language');

        $this->withUnencryptedCookie(Locales::COOKIE, 'ta')->get(route('home'))->assertOk()->assertSee('Track order')->assertSee('<html lang="en"', false);
    }

    public function test_deactivating_hindi_hides_the_switcher_and_returns_visitors_to_english(): void
    {
        Language::where('code', 'hi')->update(['is_active' => false]);
        Locales::reset();

        $this->hindi()->get(route('home'))->assertOk()->assertDontSee('name="language"', false)->assertSee('Track order')->assertDontSee('ऑर्डर ट्रैक करें');
    }

    public function test_rtl_languages_set_the_page_direction(): void
    {
        $this->assertSame('ltr', Locales::direction());
        Language::where('code', 'hi')->update(['direction' => 'rtl']);
        Locales::reset();

        $this->hindi()->get(route('home'))->assertSee('dir="rtl"', false);
    }

    public function test_home_page_is_translated_including_the_hero_and_sections(): void
    {
        $this->product(['is_featured' => true]);

        $this->get(route('home'))->assertOk()->assertSee('Curated furniture and decor')->assertSee('Explore the collection')->assertSee('Be first to know');
        $this->hindi()->get(route('home'))->assertOk()
            ->assertSee('चुना हुआ फ़र्नीचर और सजावट')->assertSee('<em>अंदाज़</em>', false)->assertSee('कलेक्शन देखें')
            ->assertSee('शुरुआत कमरे से कीजिए।')->assertSee('सबसे पहले जानें')
            ->assertSee('सब्सक्राइब करें')->assertSee('मुफ़्त डिलीवरी')->assertSee('आसान रिटर्न')
            ->assertDontSee('Curated furniture and decor')->assertDontSee('pages.home.');
    }

    public function test_product_page_is_translated_specs_tabs_reviews_and_gst_note(): void
    {
        $p = $this->product(['brand' => 'Acme', 'material' => 'Oak', 'tax_rate' => 12, 'hsn_code' => '9403']);
        $u = User::factory()->create();
        \App\Models\Review::create(['user_id' => $u->id, 'product_id' => $p->id, 'rating' => 5, 'comment' => 'Great', 'is_approved' => true]);

        $this->hindi()->get(route('products.show', $p))->assertOk()
            ->assertSee('स्टॉक में है')->assertSee('कार्ट में जोड़ें')->assertSee('विशलिस्ट')->assertSee('शेयर करें')
            ->assertSee('विवरण')->assertSee('विशेषताएँ')->assertSee('रिव्यू (1)')->assertSee('शिपिंग और देखभाल')
            ->assertSee('ब्रांड')->assertSee('सामग्री')->assertSee('HSN कोड')
            ->assertSee('GST @ 12%')->assertSee('चेकआउट पर जुड़ता है')->assertSee('1 रिव्यू')->assertSee('सत्यापित खरीदार')
            ->assertSee('रिटर्न नीति')->assertSee('साइन इन')
            ->assertDontSee('In stock')->assertDontSee('Add to cart')->assertDontSee('pages.product.');
    }

    public function test_product_page_language_switch_keeps_js_strings_and_prices_intact(): void
    {
        $p = $this->product();

        $html = $this->hindi()->get(route('products.show', $p))->getContent();
        // @json escapes non-ASCII, so compare against the same encoding the page uses.
        $this->assertStringContainsString(json_encode('(सिर्फ़ :count बचे हैं)'), $html);   // placeholder kept for the variant script to fill
        $this->assertStringContainsString(json_encode('लिंक कॉपी हो गया'), $html);
        $this->assertStringContainsString('₹1,000', $html);                     // rupees are never translated
    }

    public function test_quick_view_and_search_are_translated(): void
    {
        $p = $this->product(['sale_price' => 800]);

        $this->hindi()->get(route('products.quick-view', $p))->assertOk()->assertSee('स्टॉक में है')->assertSee('कार्ट में जोड़ें')->assertSee('पूरा विवरण देखें')->assertDontSee('View full details');
        $this->hindi()->get(route('search'))->assertOk()
            ->assertSee('सभी प्रोडक्ट')->assertSee('फ़िल्टर')->assertSee('कीवर्ड या SKU')->assertSee('सभी श्रेणियाँ')->assertSee('दाम (₹)')
            ->assertSee('फ़िल्टर लगाएँ')->assertSee('1 प्रोडक्ट')->assertSee('सबसे नए पहले')->assertSee('चुनिंदा')
            ->assertDontSee('Apply filters')->assertDontSee('pages.search.');
        $this->hindi()->get(route('search', ['q' => 'zzz', 'min_price' => 5, 'on_sale' => 1]))->assertOk()
            ->assertSee('"zzz" के नतीजे')->assertSee('न्यूनतम ₹5')->assertSee('छूट पर')->assertSee('कोई प्रोडक्ट नहीं मिला')->assertSee('फ़िल्टर हटाएँ');
    }

    /** Windows treats Pages.php and pages.php as the same file, so a plain word like __('Pages') would load the whole file. */
    public function test_plain_translation_keys_never_match_a_language_file_name(): void
    {
        $groups = array_map(fn ($f) => strtolower(pathinfo($f, PATHINFO_FILENAME)), glob(lang_path('en/*.php')));
        $bad = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))) as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.php')) {
                continue;
            }
            preg_match_all("/(?:__|trans)\\(\\s*(['\"])([^'\"]+)\\1/", file_get_contents($file->getPathname()), $m);
            foreach ($m[2] as $key) {
                if (! str_contains($key, '.') && in_array(strtolower($key), $groups, true)) {
                    $bad[] = $key . ' in ' . $file->getFilename();
                }
            }
        }
        $this->assertSame([], $bad, 'These keys load a whole language file on Windows: ' . implode(', ', $bad));
    }
}
