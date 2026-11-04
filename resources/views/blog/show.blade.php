@extends('layouts.app')

@section('title', ($post->meta_title ?: $post->title) . ' - SpazoraHome')
@section('meta_description', $post->meta_description ?: \Illuminate\Support\Str::limit($post->summary(30), 155))

@section('content')
<div class="page-head"><div class="container">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home') }}</a></li><li class="breadcrumb-item"><a href="{{ route('blog.index') }}">{{ __('Blog') }}</a></li><li class="breadcrumb-item active">{{ \Illuminate\Support\Str::limit($post->title, 40) }}</li></ol></nav>
    <h1 class="mb-2">{{ $post->title }}</h1>
    <p class="text-muted mb-0">{{ $post->author_name }} &middot; {{ $post->dateLabel() }} &middot; {{ $post->readingMinutes() }} {{ __('min read') }}</p>
</div></div>

<div class="container py-4" style="max-width: 820px">
    @if($post->imageUrl())<img src="{{ $post->imageUrl() }}" alt="{{ $post->title }}" class="img-fluid rounded-4 w-100 mb-4" style="max-height:440px;object-fit:cover">@endif
    {{-- Post text is cleaned to a short list of safe tags before it is shown. --}}
    <article class="blog-body rich-content">{!! \App\Support\Html::render($post->content) !!}</article>

    @if($related->isNotEmpty())
        <hr class="my-5">
        <h5 class="mb-3">{{ __('More articles') }}</h5>
        <div class="row g-3">
            @foreach($related as $r)
                <div class="col-md-4"><a href="{{ route('blog.show', $r->slug) }}" class="card card-body h-100 text-decoration-none"><small class="text-muted">{{ $r->dateLabel() }}</small><strong class="text-dark">{{ $r->title }}</strong></a></div>
            @endforeach
        </div>
    @endif
    <div class="mt-4"><a href="{{ route('blog.index') }}" class="link-arrow">&larr; {{ __('Back to blog') }}</a></div>
</div>
@endsection
