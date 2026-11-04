<?php

namespace Tests\Feature;

use App\Models\Blog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBlogTest extends TestCase
{
    use RefreshDatabase;

    private function makePost(array $o = []): Blog
    {
        return Blog::create($o + ['title' => 'Pick a sofa', 'slug' => 'pick-a-sofa-' . uniqid(), 'content' => "First para.\n\nSecond para.", 'author_name' => 'Team', 'is_published' => true, 'published_at' => now()->subDay()]);
    }

    public function test_published_posts_are_listed_and_open(): void
    {
        $p = $this->makePost();
        $this->get(route('blog.index'))->assertOk()->assertSee('Pick a sofa');
        $this->get(route('blog.show', $p->slug))->assertOk()->assertSee('First para.')->assertSee('Second para.');
    }

    public function test_drafts_and_future_posts_stay_hidden(): void
    {
        $draft = $this->makePost(['title' => 'Secret draft', 'is_published' => false]);
        $future = $this->makePost(['title' => 'Coming soon post', 'published_at' => now()->addWeek()]);
        $this->get(route('blog.index'))->assertOk()->assertDontSee('Secret draft')->assertDontSee('Coming soon post');
        $this->get(route('blog.show', $draft->slug))->assertNotFound();
        $this->get(route('blog.show', $future->slug))->assertNotFound();
    }

    public function test_post_text_cannot_inject_scripts(): void
    {
        $p = $this->makePost(['content' => '<script>alert(1)</script>Hello']);
        $this->get(route('blog.show', $p->slug))->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('Hello');
    }

    public function test_search_filters_posts(): void
    {
        $this->makePost(['title' => 'Walnut tables guide']);
        $this->makePost(['title' => 'Curtain ideas']);
        $this->get(route('blog.index', ['q' => 'Walnut']))->assertOk()->assertSee('Walnut tables guide')->assertDontSee('Curtain ideas');
    }

    public function test_empty_blog_page_still_opens(): void
    {
        $this->get(route('blog.index'))->assertOk()->assertSee('No articles yet');
    }
}
