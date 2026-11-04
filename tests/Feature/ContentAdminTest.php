<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Menu;
use App\Models\Newsletter;
use App\Models\Slider;
use App\Models\User;
use App\Rules\LinkOrPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Admin pages for menus, sliders and newsletter subscribers, and how they show on the storefront. */
class ContentAdminTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_content_pages_are_admin_only_and_listed_in_the_sidebar(): void
    {
        $names = ['admin.menus.index', 'admin.sliders.index', 'admin.newsletters.index', 'admin.newsletters.export'];
        foreach ($names as $name) {
            $this->get(route($name))->assertRedirect(route('login'));
        }
        $customer = $this->user('customer');
        foreach ($names as $name) {
            $this->actingAs($customer)->get(route($name))->assertForbidden();
        }

        $this->actingAs($this->user('admin'))->get(route('admin.menus.index'))->assertOk()
            ->assertSee(route('admin.sliders.index'), false)->assertSee(route('admin.newsletters.index'), false)->assertSee('No menus yet.');
    }

    public function test_admin_builds_a_menu_and_its_links_show_on_the_storefront(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->post(route('admin.menus.store'), [
            'name' => 'Top links', 'location' => 'header', 'is_active' => 1,
            'links' => "Sale | /search?sort=price_asc\n\nOur blog | https://blog.example.com/",
        ])->assertRedirect(route('admin.menus.index'));
        $menu = Menu::first();
        $this->assertSame([['label' => 'Sale', 'url' => '/search?sort=price_asc'], ['label' => 'Our blog', 'url' => 'https://blog.example.com/']], $menu->items);

        $this->actingAs($admin)->post(route('admin.menus.store'), ['name' => 'Foot', 'location' => 'footer', 'is_active' => 1, 'links' => 'Careers | /careers']);
        $this->get(route('home'))->assertOk()
            ->assertSee('href="/search?sort=price_asc"', false)->assertSee('Our blog')->assertSee('href="/careers"', false);

        // The edit form shows the links back as typed; switching the menu off removes it from the shop.
        $this->actingAs($admin)->get(route('admin.menus.edit', $menu))->assertOk()->assertSee('Our blog | https://blog.example.com/');
        $this->actingAs($admin)->put(route('admin.menus.update', $menu), ['name' => 'Top links', 'location' => 'header', 'is_active' => 0, 'links' => 'Sale | /search'])
            ->assertRedirect(route('admin.menus.index'));
        $this->get(route('home'))->assertOk()->assertDontSee('Our blog')->assertSee('href="/careers"', false);

        $this->actingAs($admin)->delete(route('admin.menus.destroy', $menu))->assertRedirect(route('admin.menus.index'));
        $this->assertSame(1, Menu::count());
    }

    public function test_menu_links_must_be_real_addresses_or_site_paths(): void
    {
        $admin = $this->user('admin');
        $bad = ['No separator here', 'Click | javascript:alert(1)', 'Other | //evil.example.com', 'Bare | example.com', ' | /empty-label', 'Quote | /a"onclick="x'];
        foreach ($bad as $line) {
            $this->actingAs($admin)->post(route('admin.menus.store'), ['name' => 'M', 'location' => 'header', 'links' => $line])->assertSessionHasErrors('links');
        }
        $this->actingAs($admin)->post(route('admin.menus.store'), ['name' => 'M', 'location' => 'sidebar', 'links' => 'A | /a'])->assertSessionHasErrors('location');
        $this->assertSame(0, Menu::count());

        $this->assertTrue(LinkOrPath::passes('/shop'));
        $this->assertTrue(LinkOrPath::passes('https://example.com/a?b=1'));
        $this->assertFalse(LinkOrPath::passes('ftp://example.com'));
        $this->assertFalse(LinkOrPath::passes('/\\evil.example.com'));
    }

    public function test_admin_builds_a_slider_that_replaces_the_home_page_banner(): void
    {
        $admin = $this->user('admin');
        $this->get(route('home'))->assertOk()->assertSee('class="hx-hero"', false)->assertDontSee('homeSlider');

        $this->actingAs($admin)->post(route('admin.sliders.store'), [
            'name' => 'Diwali', 'location' => 'home', 'sort_order' => 1, 'is_active' => 1,
            'slides' => [
                ['image' => 'https://cdn.example.com/a.jpg', 'title' => 'Festive sofas', 'text' => 'Up to 40% off', 'button' => 'Shop sofas', 'link' => '/search?category=1'],
                ['image' => '', 'title' => '', 'text' => '', 'button' => '', 'link' => ''],
                ['image' => '/storage/sliders/b.jpg', 'title' => 'Second slide'],
            ],
        ])->assertRedirect(route('admin.sliders.index'));
        $slider = Slider::first();
        $this->assertCount(2, $slider->slides); // the empty row is dropped

        $this->get(route('home'))->assertOk()->assertSee('homeSlider')->assertDontSee('class="hx-hero"', false)
            ->assertSee('https://cdn.example.com/a.jpg', false)->assertSee('Festive sofas')->assertSee('Shop sofas')
            ->assertSee('href="/search?category=1"', false)->assertSee('/storage/sliders/b.jpg', false);
        $this->actingAs($admin)->get(route('admin.sliders.edit', $slider))->assertOk()->assertSee('Festive sofas')->assertSee('Slide 5');

        // Switched off, or left without slides: the normal banner is back.
        $this->actingAs($admin)->put(route('admin.sliders.update', $slider), ['name' => 'Diwali', 'location' => 'home', 'is_active' => 0, 'slides' => [['image' => '/a.jpg']]]);
        $this->get(route('home'))->assertOk()->assertSee('class="hx-hero"', false);
        $this->actingAs($admin)->put(route('admin.sliders.update', $slider), ['name' => 'Diwali', 'location' => 'home', 'is_active' => 1]);
        $this->assertSame([], $slider->fresh()->slides);
        $this->get(route('home'))->assertOk()->assertSee('class="hx-hero"', false);
    }

    public function test_slides_need_an_image_and_safe_links(): void
    {
        $admin = $this->user('admin');
        $base = ['name' => 'S', 'location' => 'home', 'is_active' => 1];

        $this->actingAs($admin)->post(route('admin.sliders.store'), $base + ['slides' => [['image' => '', 'title' => 'Heading without a picture']]])->assertSessionHasErrors('slides.0.image');
        $this->actingAs($admin)->post(route('admin.sliders.store'), $base + ['slides' => [['image' => 'javascript:alert(1)']]])->assertSessionHasErrors('slides.0.image');
        $this->actingAs($admin)->post(route('admin.sliders.store'), $base + ['slides' => [['image' => '/a.jpg', 'link' => 'javascript:alert(1)']]])->assertSessionHasErrors('slides.0.link');
        $this->actingAs($admin)->post(route('admin.sliders.store'), $base + ['slides' => array_fill(0, 6, ['image' => '/a.jpg'])])->assertSessionHasErrors('slides');
        $this->assertSame(0, Slider::count());
    }

    public function test_admin_manages_and_exports_newsletter_subscribers(): void
    {
        $admin = $this->user('admin');
        $this->post(route('newsletter.subscribe'), ['email' => 'First@Example.com']);
        Newsletter::create(['email' => '=cmd@example.com', 'is_active' => false, 'subscribed_at' => now()]);

        $this->actingAs($admin)->get(route('admin.newsletters.index'))->assertOk()
            ->assertSee('first@example.com')->assertSee('1 active of 2')->assertSee('Unsubscribed');
        $this->actingAs($admin)->get(route('admin.newsletters.index', ['status' => 'inactive']))->assertOk()->assertDontSee('first@example.com');
        $this->actingAs($admin)->get(route('admin.newsletters.index', ['q' => 'first']))->assertOk()->assertDontSee('cmd@example.com');

        $this->actingAs($admin)->post(route('admin.newsletters.store'), ['email' => 'new@example.com'])->assertSessionHas('success', 'Subscriber added.');
        $this->actingAs($admin)->post(route('admin.newsletters.store'), ['email' => 'NEW@example.com'])->assertSessionHas('success', 'That address is already on the list.');
        $this->actingAs($admin)->post(route('admin.newsletters.store'), ['email' => 'not-an-email'])->assertSessionHasErrors('email');
        $this->assertSame(3, Newsletter::count());

        $first = Newsletter::where('email', 'first@example.com')->first();
        $this->actingAs($admin)->put(route('admin.newsletters.update', $first))->assertSessionHas('success');
        $this->assertFalse($first->fresh()->is_active);
        $this->actingAs($admin)->put(route('admin.newsletters.update', $first));
        $this->assertTrue($first->fresh()->is_active);

        $csv = $this->actingAs($admin)->get(route('admin.newsletters.export'))->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8')->streamedContent();
        $this->assertStringContainsString("email,status,subscribed_at\n", $csv);
        $this->assertStringContainsString('first@example.com,active,', $csv);
        $this->assertStringContainsString("'=cmd@example.com,unsubscribed,", $csv); // not runnable as a spreadsheet formula
        $this->assertStringNotContainsString('first@example.com', $this->actingAs($admin)->get(route('admin.newsletters.export', ['status' => 'inactive']))->streamedContent());

        $this->actingAs($admin)->delete(route('admin.newsletters.destroy', $first))->assertSessionHas('success');
        $this->assertSame(2, Newsletter::count());
    }

    public function test_banner_link_may_be_a_path_on_this_site(): void
    {
        $admin = $this->user('admin');
        $base = ['title' => 'Sale', 'image' => 'banners/sale.jpg', 'position' => 'homepage', 'sort_order' => 0, 'is_active' => 1];

        $this->actingAs($admin)->post(route('admin.banners.store'), $base + ['link' => '/search?sort=price_asc'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.banners.store'), $base + ['link' => 'https://example.com/sale'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.banners.store'), $base)->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.banners.store'), $base + ['link' => 'shop'])->assertSessionHasErrors('link');
        $this->actingAs($admin)->post(route('admin.banners.store'), $base + ['link' => 'javascript:alert(1)'])->assertSessionHasErrors('link');
        $this->assertSame(3, Banner::count());
        $this->assertSame('/search?sort=price_asc', Banner::first()->link);
    }

    public function test_variant_attributes_are_typed_one_per_line_and_make_the_label(): void
    {
        $admin = $this->user('admin');
        $category = \App\Models\Category::create(['name' => 'Sofas', 'slug' => 'sofas']);
        $product = \App\Models\Product::create(['category_id' => $category->id, 'name' => 'Sofa', 'slug' => 'sofa', 'description' => 'x', 'sku' => 'S1', 'price' => 10, 'stock_quantity' => 1, 'is_active' => true]);
        $base = ['sku' => 'S1-RED', 'price' => 12, 'stock_quantity' => 3];

        $this->actingAs($admin)->post(route('admin.products.variants.store', $product), $base + ['attributes' => "Colour: Red\nSize: Large"])->assertSessionHasNoErrors();
        $variant = $product->variants()->first();
        $this->assertSame(['Colour' => 'Red', 'Size' => 'Large'], $variant->getAttribute('attributes'));
        $this->assertSame('Red / Large', $variant->label);
        $this->actingAs($admin)->get(route('admin.products.variants.edit', [$product, $variant]))->assertOk()->assertSee('Colour: Red');

        // The older JSON form still works; an empty box clears the attributes; nonsense is refused.
        $this->actingAs($admin)->put(route('admin.products.variants.update', [$product, $variant]), $base + ['attributes' => '{"colour": "Blue"}'])->assertSessionHasNoErrors();
        $this->assertSame(['colour' => 'Blue'], $variant->fresh()->getAttribute('attributes'));
        $this->actingAs($admin)->put(route('admin.products.variants.update', [$product, $variant]), $base + ['attributes' => 'just some words'])->assertSessionHasErrors('attributes');
        $this->actingAs($admin)->put(route('admin.products.variants.update', [$product, $variant]), $base + ['attributes' => ''])->assertSessionHasNoErrors();
        $this->assertNull($variant->fresh()->getAttribute('attributes'));
    }
}
