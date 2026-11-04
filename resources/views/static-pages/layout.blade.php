@extends('layouts.app')

{{-- Shared frame for the help and company pages: heading band, the page body, and a side menu of the other pages. --}}
@php
    $helpPages = [
        'faq' => ['FAQ', 'bi-question-circle'],
        'shipping' => ['Shipping policy', 'bi-truck'],
        'returns' => ['Returns & refunds', 'bi-arrow-return-left'],
        'about-us' => ['About us', 'bi-house-heart'],
        'contact-us' => ['Contact us', 'bi-chat-dots'],
        'privacy' => ['Privacy policy', 'bi-shield-lock'],
        'terms' => ['Terms of use', 'bi-file-text'],
    ];
    $current = \Illuminate\Support\Facades\Route::currentRouteName();
@endphp

@section('content')
<div class="page-head doc-head"><div class="container">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li><li class="breadcrumb-item active" aria-current="page">@yield('crumb')</li></ol></nav>
    <span class="kicker">@yield('kicker', 'Help centre')</span>
    <h1>@yield('heading')</h1>
    @hasSection('lead')<p class="doc-lead">@yield('lead')</p>@endif
    @hasSection('updated')<p class="doc-updated"><i class="bi bi-clock-history"></i> Last updated: @yield('updated')</p>@endif
</div></div>

<div class="container py-4 py-lg-5">
    <div class="doc-layout">
        <article class="doc">
            @yield('doc')
        </article>

        <aside class="doc-side" aria-label="Help and company pages">
            <nav class="doc-nav">
                <h2>Help &amp; company</h2>
                @foreach($helpPages as $name => [$label, $icon])
                    <a href="{{ route($name) }}" @class(['active' => $current === $name]) @if($current === $name) aria-current="page" @endif><i class="bi {{ $icon }}"></i><span>{{ $label }}</span></a>
                @endforeach
            </nav>
            <div class="doc-help">
                <i class="bi bi-headset"></i>
                <strong>Still stuck?</strong>
                <p>Tell us your order number and what went wrong. A person reads every ticket.</p>
                <a href="{{ route('customer.support-tickets.index') }}" class="btn btn-accent btn-sm">Raise a ticket</a>
                <a href="{{ route('customer.order-tracking.index') }}" class="btn btn-outline-primary btn-sm">Track an order</a>
            </div>
        </aside>
    </div>
</div>
@endsection
