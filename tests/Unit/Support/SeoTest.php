<?php

namespace Tests\Unit\Support;

use App\Models\Setting;
use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Seo::reset();
    }

    protected function tearDown(): void
    {
        Seo::reset();
        parent::tearDown();
    }

    private function set(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::create(['key' => $key, 'value' => $value]);
        }
        Seo::reset();
    }

    public function test_defaults_when_nothing_is_configured(): void
    {
        $this->assertSame(Seo::DEFAULT_DESCRIPTION, Seo::description());
        $this->assertSame('furniture, interior, home decor', Seo::keywords());
        $this->assertNull(Seo::analyticsId());
        $this->assertFalse(Seo::blocked());
    }

    public function test_reads_configured_values(): void
    {
        $this->set(['site_description' => 'Best sofas', 'site_keywords' => 'sofa', 'google_analytics_id' => 'G-ABC123']);

        $this->assertSame('Best sofas', Seo::description());
        $this->assertSame('sofa', Seo::keywords());
        $this->assertSame('G-ABC123', Seo::analyticsId());
    }

    public function test_rejects_malformed_analytics_id(): void
    {
        $this->set(['google_analytics_id' => '<script>']);
        $this->assertNull(Seo::analyticsId());
    }

    public function test_verification_accepts_bare_code_or_whole_meta_tag(): void
    {
        $this->set([
            'google_site_verification' => '<meta name="google-site-verification" content="abcDEF123_-xyz" />',
            'bing_site_verification' => 'BING1234567',
        ]);

        $this->assertSame('abcDEF123_-xyz', Seo::verification('google_site_verification'));
        $this->assertSame('BING1234567', Seo::verification('bing_site_verification'));
    }

    public function test_noindex_blocks_everything_in_robots(): void
    {
        $this->set(['seo_noindex' => '1']);

        $this->assertTrue(Seo::blocked());
        $this->assertSame('noindex, nofollow', Seo::robotsMeta());
        $this->assertStringContainsString("User-agent: *\nDisallow: /\n", Seo::robotsTxt());
    }

    public function test_robots_txt_disallows_private_areas_and_keeps_only_valid_extra_lines(): void
    {
        $this->set(['robots_extra' => "Disallow: /secret\nnot a directive\nCrawl-delay: 5"]);
        $txt = Seo::robotsTxt();

        foreach (['/admin', '/vendor', '/customer', '/checkout', '/secret'] as $path) {
            $this->assertStringContainsString("Disallow: $path", $txt);
        }
        $this->assertStringContainsString('Crawl-delay: 5', $txt);
        $this->assertStringNotContainsString('not a directive', $txt);
        $this->assertStringContainsString('Sitemap: ', $txt);
    }

    public function test_snippet_is_plain_and_limited(): void
    {
        $this->assertSame('Hello world', Seo::snippet('<p>Hello <b>world</b></p>'));
        $this->assertLessThanOrEqual(163, mb_strlen(Seo::snippet(str_repeat('word ', 100))));
    }
}
