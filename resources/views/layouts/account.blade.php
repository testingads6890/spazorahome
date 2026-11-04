@extends('layouts.app')

@php
    $accCurrent = \App\Support\AccountMenu::current();
    $onOverview = request()->routeIs('customer.dashboard');
@endphp

@section('content')
<div class="page-head"><div class="container">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home') }}</a></li><li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">{{ __('My account') }}</a></li>@hasSection('crumb')<li class="breadcrumb-item active">@yield('crumb')</li>@endif</ol></nav>
    <h1>@yield('heading', __('My account'))</h1>
</div></div>

<div class="container py-4">
    {{-- Mobile: the full menu on Overview; on other pages a tap-to-open menu showing where you are. --}}
    <details class="acc-menu-mobile d-lg-none mb-3" @if($onOverview) open @endif>
        <summary>
            <span><i class="bi {{ $accCurrent['icon'] ?? 'bi-person' }}"></i> {{ $accCurrent['label'] ?? __('My account') }}</span>
            <i class="bi bi-chevron-down chev"></i>
        </summary>
        @include('partials.account-menu')
    </details>

    <div class="row g-4">
        <aside class="col-lg-3 d-none d-lg-block">
            <div class="account-nav position-sticky" style="top: 150px">
                <div class="acc-user">
                    <span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    <span class="min-w-0"><small>{{ __('Hello') }}</small><strong class="text-truncate d-block">{{ auth()->user()->name }}</strong></span>
                </div>
                @include('partials.account-menu')
            </div>
        </aside>
        <div class="col-lg-9">
            @foreach(['success' => 'success', 'error' => 'danger'] as $key => $cls)
                @if(session($key))<div class="alert alert-{{ $cls }}">{{ session($key) }}</div>@endif
            @endforeach
            @yield('account')
        </div>
    </div>
</div>
@endsection
