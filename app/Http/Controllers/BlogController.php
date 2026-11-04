<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;

/** Public blog: only published posts whose publish date has arrived. */
class BlogController extends Controller
{
    private function visible()
    {
        return Blog::query()->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function index(Request $request)
    {
        $posts = $this->visible()
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->q . '%';
                $q->where(fn ($w) => $w->where('title', 'like', $term)->orWhere('excerpt', 'like', $term)->orWhere('content', 'like', $term));
            })
            ->orderByDesc('published_at')->orderByDesc('id')
            ->paginate(9)->withQueryString();

        return view('blog.index', compact('posts'));
    }

    public function show(string $slug)
    {
        $post = $this->visible()->where('slug', $slug)->firstOrFail();
        $related = $this->visible()->where('id', '!=', $post->id)->orderByDesc('published_at')->orderByDesc('id')->limit(3)->get();

        return view('blog.show', compact('post', 'related'));
    }
}
