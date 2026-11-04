@extends('layouts.app')

@section('title', __('Blog') . ' - SpazoraHome')
@section('meta_description', __('Design ideas, buying guides and news from SpazoraHome.'))

@section('content')
<div class="page-head"><div class="container">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home') }}</a></li><li class="breadcrumb-item active">{{ __('Blog') }}</li></ol></nav>
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
        <div><h1>{{ __('Blog') }}</h1><p class="text-muted mb-0">{{ __('Design ideas, buying guides and news.') }}</p></div>
        <form method="GET" class="d-flex gap-2" role="search">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ __('Search articles') }}" aria-label="{{ __('Search articles') }}" style="min-width:220px">
            <button class="btn btn-primary">{{ __('Search') }}</button>
        </form>
    </div>
</div></div>

<div class="container py-4">
    <div class="row g-4">
        @forelse($posts as $post)
            <div class="col-md-6 col-lg-4">
                <a href="{{ route('blog.show', $post->slug) }}" class="card h-100 blog-card text-decoration-none">
                    @if($post->imageUrl())<img src="{{ $post->imageUrl() }}" alt="" class="card-img-top" loading="lazy" style="aspect-ratio:16/10;object-fit:cover">@else<div class="blog-ph"><i class="bi bi-journal-richtext"></i></div>@endif
                    <div class="card-body d-flex flex-column">
                        <small class="text-muted">{{ $post->dateLabel() }} &middot; {{ $post->readingMinutes() }} {{ __('min read') }}</small>
                        <h5 class="my-2 text-dark">{{ $post->title }}</h5>
                        <p class="text-muted small mb-3">{{ $post->summary() }}</p>
                        <span class="link-arrow mt-auto">{{ __('Read more') }}</span>
                    </div>
                </a>
            </div>
        @empty
            <div class="empty"><i class="bi bi-journal-text"></i><h5 class="mt-3">{{ __('No articles yet') }}</h5><p>{{ request('q') ? __('Try a different search.') : __('Please check back soon.') }}</p>@if(request('q'))<a href="{{ route('blog.index') }}" class="btn btn-primary">{{ __('Clear search') }}</a>@endif</div>
        @endforelse
    </div>
    <div class="d-flex justify-content-center mt-4">{{ $posts->links() }}</div>
</div>
@endsection
