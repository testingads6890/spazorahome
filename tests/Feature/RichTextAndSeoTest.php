<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\Html;
use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RichTextAndSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Seo::reset();
    }

    public function test_clean_keeps_formatting_and_removes_scripts_and_handlers(): void
    {
        $dirty = '<p onclick="x()">Hi <strong>there</strong></p><script>alert(1)</script><img src="x" onerror="alert(1)"><a href="javascript:alert(1)">bad</a><a href="https://ok.test/a">good</a><iframe src="https://evil"></iframe><div style="color:red"><span>kept text</span></div>';
        $clean = Html::clean($dirty);

        $this->assertStringContainsString('<strong>there</strong>', $clean);
        $this->assertStringContainsString('kept text', $clean);
        $this->assertStringContainsString('href="https://ok.test/a"', $clean);
        foreach (['<script', 'alert(1)</', 'onclick', 'onerror', 'javascript:', '<iframe', 'style=', '<div', '<span'] as $bad) {
            $this->assertStringNotContainsString($bad, $clean, "still contains {$bad}");
        }
    }

    public function test_plain_text_becomes_paragraphs_and_html_is_escaped(): void
    {
        $out = Html::render("Line one\nsecond line\n\nTom & Jerry, 3 < 5");
        $this->assertSame(2, substr_count($out, '<p>'));
        $this->assertStringContainsString('3 &lt; 5', $out);
        $this->assertSame('Hello world', Html::text('<p>Hello</p><p>world</p>'));
        $this->assertSame('Tom & Jerry', Html::text('<p>Tom &amp; Jerry</p>'));
    }

    public function test_models_clean_rich_text_on_every_save(): void
    {
        $cat = Category::create(['name' => 'Sofas', 'slug' => 'sofas', 'description' => '<p>Soft</p><script>bad()</script>', 'status' => true]);
        $this->assertSame('<p>Soft</p>', $cat->fresh()->description);

        $blog = Blog::create(['title' => 'T', 'slug' => 't', 'content' => '<p onmouseover="x()">Body</p>', 'author_name' => 'A', 'is_published' => true]);
        $this->assertStringNotContainsString('onmouseover', $blog->fresh()->content);
        $blog->update(['content' => '<h2>Head</h2><script>x</script>']);
        $this->assertSame('<h2>Head</h2>', $blog->fresh()->content);
    }

    public function test_product_page_shows_formatting_but_no_script_and_cards_show_plain_text(): void
    {
        $cat = Category::create(['name' => 'Beds', 'slug' => 'beds', 'status' => true]);
        $p = Product::create(['category_id' => $cat->id, 'name' => 'Oak Bed', 'slug' => 'oak-bed', 'description' => '<h3>Details</h3><ul><li>Solid oak</li></ul>', 'short_description' => '<p>Strong <strong>frame</strong></p>', 'sku' => 'B-1', 'price' => 5000, 'stock_quantity' => 3, 'is_active' => true]);
        $p->forceFill(['description' => '<p>Safe</p><script>steal()</script>'])->saveQuietly(); // as if old unclean data were in the database

        $page = $this->get(route('products.show', $p))->assertOk();
        $page->assertSee('<strong>frame</strong>', false)->assertSee('<p>Safe</p>', false)->assertDontSee('steal()', false);
        $this->get(route('home'))->assertOk();
        $this->assertStringNotContainsString('<strong>', Html::text($p->short_description));
    }

    public function test_robots_txt_points_to_sitemap_and_hides_private_areas(): void
    {
        $txt = $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')->getContent();
        $this->assertStringContainsString('Disallow: /admin', $txt);
        $this->assertStringContainsString('Disallow: /checkout', $txt);
        $this->assertStringContainsString('Sitemap: ' . url('/sitemap.xml'), $txt);
        $this->assertStringNotContainsString("Disallow: /\n", $txt);

        Setting::set('seo_noindex', '1');
        Seo::reset();
        $this->assertStringContainsString("Disallow: /\n", $this->get('/robots.txt')->getContent());
        $this->get(route('home'))->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_sitemap_lists_products_categories_and_published_blog_posts_only(): void
    {
        $cat = Category::create(['name' => 'Chairs', 'slug' => 'chairs', 'status' => true]);
        Product::create(['category_id' => $cat->id, 'name' => 'Cane Chair', 'slug' => 'cane-chair', 'description' => 'x', 'sku' => 'C-1', 'price' => 900, 'stock_quantity' => 3, 'is_active' => true]);
        Blog::create(['title' => 'Live post', 'slug' => 'live-post', 'content' => 'x', 'author_name' => 'A', 'is_published' => true, 'published_at' => now()->subDay()]);
        Blog::create(['title' => 'Draft post', 'slug' => 'draft-post', 'content' => 'x', 'author_name' => 'A', 'is_published' => false]);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringContainsString('cane-chair', $xml);
        $this->assertStringContainsString('/blog/live-post', $xml);
        $this->assertStringNotContainsString('draft-post', $xml);
        $this->assertStringContainsString('/blog</loc>', $xml);
    }

    public function test_meta_description_uses_own_text_then_content_then_site_default(): void
    {
        $cat = Category::create(['name' => 'Tables', 'slug' => 'tables', 'status' => true]);
        $p = Product::create(['category_id' => $cat->id, 'name' => 'Teak Table', 'slug' => 'teak-table', 'description' => '<p>Long description here</p>', 'short_description' => '<p>Short <b>words</b></p>', 'sku' => 'T-1', 'price' => 900, 'stock_quantity' => 3, 'is_active' => true]);
        $this->get(route('products.show', $p))->assertSee('<meta name="description" content="Short words">', false);
        $p->update(['meta_description' => 'My own SEO text']);
        $this->get(route('products.show', $p))->assertSee('<meta name="description" content="My own SEO text">', false);

        $blog = Blog::create(['title' => 'B', 'slug' => 'b-post', 'content' => '<p>Blog body text</p>', 'author_name' => 'A', 'is_published' => true, 'meta_description' => 'Blog SEO text', 'meta_title' => 'Blog SEO title']);
        $this->get(route('blog.show', 'b-post'))->assertSee('content="Blog SEO text"', false)->assertSee('Blog SEO title - SpazoraHome');

        Setting::set('site_description', 'Our shop default text');
        Seo::reset();
        $this->get(route('home'))->assertSee('content="Our shop default text"', false);
    }

    public function test_seo_settings_save_only_known_fields(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->put(route('admin.seo.update'), ['site_title' => 'My Shop', 'site_description' => 'About my shop', 'google_site_verification' => 'abcdEFGH12345', 'seo_noindex' => '1', 'robots_extra' => "Disallow: /private\nbad line", 'evil_key' => 'x'])->assertRedirect();

        $this->assertSame('My Shop', Setting::get('site_title'));
        $this->assertNull(Setting::where('key', 'evil_key')->first());
        $this->assertNull(Setting::where('key', '_token')->first());
        Seo::reset();
        $txt = Seo::robotsTxt();
        $this->assertStringContainsString('Disallow: /private', $txt);
        $this->assertStringNotContainsString('bad line', $txt);
    }

    public function test_private_pages_are_marked_noindex(): void
    {
        $this->get(route('login'))->assertSee('noindex', false);
        $this->get(route('home'))->assertDontSee('name="robots"', false);
    }
}
