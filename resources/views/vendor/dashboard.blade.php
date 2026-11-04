@extends('layouts.vendor')

@section('title', 'Seller dashboard')

@php $inr = fn ($v) => '₹' . number_format((float) $v, 2); @endphp

@section('content')
<div class="mb-4">
    <h2 class="h4 mb-1">Welcome back, {{ $vendor->business_name }}</h2>
    <p class="text-muted mb-0">Here is how your store is doing.@if($can['orders'] && $stats['new_orders'] > 0) <a href="{{ route('vendor.orders.index') }}" class="fw-semibold">{{ $stats['new_orders'] }} new {{ \Illuminate\Support\Str::plural('order', $stats['new_orders']) }}</a> to process.@endif</p>
</div>

<div class="row g-3 mb-4">
    @foreach(array_filter([
        ['Products', $stats['products'], 'bi-box-seam', 'primary', $can['products'] ? route('vendor.products.index') : null],
        ['Orders', $stats['orders'], 'bi-cart3', 'info', $can['orders'] ? route('vendor.orders.index') : null],
        $can['payouts'] ? ['Gross sales', $inr($stats['sales']), 'bi-graph-up-arrow', 'success', null] : null,
        $can['payouts'] ? ['Payable to you', $inr($stats['unpaid']), 'bi-wallet2', 'warning', route('vendor.payouts.index')] : null,
    ]) as [$label, $value, $icon, $tone, $href])
        <div class="col-6 col-xl-3">
            <div class="card stats-card"><div class="card-body d-flex justify-content-between align-items-center gap-2">
                <div>
                    <div class="text-muted small">{{ $label }}</div>
                    <div class="fs-4 fw-bold">{{ $value }}</div>
                    @if($href)<a href="{{ $href }}" class="small">View</a>@endif
                </div>
                <div class="stats-icon bg-{{ $tone }} text-white"><i class="bi {{ $icon }}"></i></div>
            </div></div>
        </div>
    @endforeach
</div>

@if($can['payouts'] || $can['orders'])
@php
    $maxSales = max(1, $chart->max('sales'));
    $change = $period['previous_sales'] > 0 ? round(($period['sales'] - $period['previous_sales']) / $period['previous_sales'] * 100) : null;
@endphp
<div class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span><i class="bi bi-bar-chart"></i> Sales, last {{ $period['days'] }} days</span>
                <div class="btn-group btn-group-sm">
                    @foreach([7, 30, 90] as $d)<a href="{{ route('vendor.dashboard', ['days' => $d]) }}" class="btn {{ $period['days'] === $d ? 'btn-primary' : 'btn-outline-primary' }}">{{ $d }}d</a>@endforeach
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-4 mb-3">
                    <div><div class="small text-muted">Sales</div><div class="fs-5 fw-bold">{{ $inr($period['sales']) }}</div>
                        @if($change !== null)<div class="small {{ $change >= 0 ? 'text-success' : 'text-danger' }}"><i class="bi bi-arrow-{{ $change >= 0 ? 'up' : 'down' }}"></i> {{ abs($change) }}% vs previous {{ $period['days'] }} days</div>@endif</div>
                    @if($can['payouts'])<div><div class="small text-muted">Your earnings</div><div class="fs-5 fw-bold">{{ $inr($period['earnings']) }}</div></div>@endif
                    <div><div class="small text-muted">Order lines</div><div class="fs-5 fw-bold">{{ $period['orders'] }}</div></div>
                </div>
                <div class="vchart" role="img" aria-label="Daily sales for the last {{ $period['days'] }} days">
                    @foreach($chart as $pt)
                        <div class="vbar" title="{{ \Illuminate\Support\Carbon::parse($pt['date'])->format('d M') }}: {{ $inr($pt['sales']) }} · {{ $pt['orders'] }} order line(s)">
                            <span style="height: {{ $pt['sales'] > 0 ? max(3, round($pt['sales'] / $maxSales * 100)) : 0 }}%"></span>
                        </div>
                    @endforeach
                </div>
                <div class="d-flex justify-content-between small text-muted mt-1">
                    <span>{{ \Illuminate\Support\Carbon::parse($chart->first()['date'])->format('d M') }}</span>
                    <span>{{ \Illuminate\Support\Carbon::parse($chart->last()['date'])->format('d M') }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="row g-3 h-100">
            @if($can['payouts'])
                <div class="col-6 col-xl-12"><div class="card h-100"><div class="card-body">
                    <div class="text-muted small"><i class="bi bi-bank"></i> Ready to withdraw</div>
                    <div class="fs-4 fw-bold">{{ $inr($stats['available']) }}</div>
                    <a href="{{ route('vendor.withdrawals.index') }}" class="small">Request withdrawal</a>
                </div></div></div>
                <div class="col-6 col-xl-12"><div class="card h-100"><div class="card-body">
                    <div class="text-muted small"><i class="bi bi-coin"></i> Total earnings</div>
                    <div class="fs-4 fw-bold">{{ $inr($stats['earnings']) }}</div>
                    <div class="small text-muted">After commission {{ $inr($stats['commission']) }}</div>
                </div></div></div>
            @endif
            <div class="col-12"><div class="card h-100"><div class="card-body">
                <div class="text-muted small"><i class="bi bi-star"></i> Customer rating</div>
                <div class="fs-4 fw-bold">{{ $stats['reviews'] ? number_format($stats['rating'], 1) . ' / 5' : '—' }}</div>
                <div class="small text-muted">{{ $stats['reviews'] }} review(s)@if($can['products']) · <a href="{{ route('vendor.reviews.index') }}">See reviews</a>@endif</div>
            </div></div></div>
        </div>
    </div>
</div>
<style>
    .vchart{display:flex;align-items:flex-end;gap:2px;height:180px;border-bottom:1px solid #dee2e6;padding-top:6px}
    .vbar{flex:1 1 0;height:100%;display:flex;align-items:flex-end}
    .vbar span{display:block;width:100%;background:var(--bs-primary,var(--brand));border-radius:3px 3px 0 0;opacity:.85}
    .vbar:hover span{opacity:1}
</style>
@endif

{{-- Notifications: what is waiting on you --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center"><span><i class="bi bi-bell"></i> Notifications</span><span class="badge text-bg-{{ $notices->isEmpty() ? 'success' : 'warning' }}">{{ $notices->count() }}</span></div>
    <div class="card-body">
        @forelse($notices as $n)
            @php $allowed = str_contains($n['url'], '/orders/') ? $can['orders'] : $can['products']; @endphp
            <div class="d-flex gap-3 align-items-start py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                <i class="bi {{ $n['icon'] }} text-{{ $n['tone'] }} fs-5"></i>
                <div class="flex-grow-1">
                    @if($allowed)<a href="{{ $n['url'] }}" class="fw-semibold">{{ $n['text'] }}</a>@else<span class="fw-semibold">{{ $n['text'] }}</span>@endif
                    @if($n['note'])<div class="small text-muted">{{ \Illuminate\Support\Str::limit($n['note'], 160) }}</div>@endif
                </div>
                <span class="small text-muted text-nowrap">{{ $n['at']?->diffForHumans(null, true) }}</span>
            </div>
        @empty
            <p class="text-muted mb-0">Nothing needs your attention right now.</p>
        @endforelse
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">Listings</div>
            <div class="card-body">
                @foreach([['Live on the website', $listings['approved'], 'success', 'approved'], ['Waiting for approval', $listings['pending'], 'warning', 'pending'], ['Changes requested', $listings['changes_requested'], 'info', 'changes_requested'], ['Rejected', $listings['rejected'], 'danger', 'rejected']] as [$label, $count, $tone, $key])
                    <div class="d-flex justify-content-between align-items-center py-1">
                        @if($can['products'])<a href="{{ route('vendor.products.index', ['status' => $key]) }}">{{ $label }}</a>@else<span>{{ $label }}</span>@endif
                        <span class="badge text-bg-{{ $count ? $tone : 'light' }} {{ $count ? '' : 'border' }}">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">Orders by stage</div>
            <div class="card-body">
                @foreach($orderStages as $label => $count)
                    <div class="d-flex justify-content-between align-items-center py-1"><span>{{ $label }}</span><span class="badge text-bg-{{ $count ? ($label === 'Cancelled' ? 'danger' : 'primary') : 'light' }} {{ $count ? '' : 'border' }}">{{ $count }}</span></div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">Inventory</div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-1"><span>Units in stock</span><strong>{{ number_format($inventory['units']) }}</strong></div>
                <div class="d-flex justify-content-between align-items-center py-1"><span>Well stocked</span><span class="badge text-bg-{{ $inventory['in_stock'] ? 'success' : 'light' }} {{ $inventory['in_stock'] ? '' : 'border' }}">{{ $inventory['in_stock'] }}</span></div>
                <div class="d-flex justify-content-between align-items-center py-1">@if($can['products'])<a href="{{ route('vendor.products.index', ['status' => 'low_stock']) }}">Running low</a>@else<span>Running low</span>@endif<span class="badge text-bg-{{ $inventory['low'] ? 'warning' : 'light' }} {{ $inventory['low'] ? '' : 'border' }}">{{ $inventory['low'] }}</span></div>
                <div class="d-flex justify-content-between align-items-center py-1"><span>Out of stock</span><span class="badge text-bg-{{ $inventory['out'] ? 'danger' : 'light' }} {{ $inventory['out'] ? '' : 'border' }}">{{ $inventory['out'] }}</span></div>
            </div>
        </div>
    </div>
</div>

@if($can['payouts'])
<div class="row g-3 mb-4">
    <div class="col-md-6"><div class="card card-body h-100"><div class="text-muted small">Commission charged</div><div class="fs-5 fw-bold">{{ $inr($stats['commission']) }}</div></div></div>
    <div class="col-md-6"><div class="card card-body h-100"><div class="text-muted small">Paid out to you so far</div><div class="fs-5 fw-bold">{{ $inr($stats['paid_out']) }}</div></div></div>
</div>
@endif

<div class="row g-3">
    @if($can['orders'])
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">Recent orders <a class="small fw-normal" href="{{ route('vendor.orders.index') }}">View all</a></div>
            <div class="card-body">
                @forelse($recentOrders as $vo)
                    <a href="{{ route('vendor.orders.show', $vo) }}" class="d-flex justify-content-between align-items-center py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                        <span>
                            <strong class="text-dark">{{ $vo->order?->order_number }}</strong>
                            <span class="d-block small text-muted">{{ $vo->created_at->format('d M Y') }} @if($can['payouts'])&middot; you earn {{ $inr($vo->payout_amount) }}@endif</span>
                        </span>
                        @php $cancelled = $vo->payout_status === 'failed'; @endphp
                        <span class="badge text-bg-{{ $cancelled ? 'danger' : ($vo->status === 'delivered' ? 'success' : ($vo->status === 'pending' ? 'warning' : 'info')) }}">{{ $cancelled ? 'Cancelled' : ucfirst($vo->status) }}</span>
                    </a>
                @empty
                    <p class="text-muted mb-0">No orders yet.</p>
                @endforelse
            </div>
        </div>
    </div>
    @endif
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Top products</div>
            <div class="card-body">
                @forelse($topProducts as $row)
                    <div class="d-flex justify-content-between align-items-center py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                        <span>{{ $row->product_name }}</span>
                        <span class="text-muted small">{{ (int) $row->units }} {{ \Illuminate\Support\Str::plural('unit', (int) $row->units) }}@if($can['payouts']) &middot; {{ $inr($row->revenue) }}@endif</span>
                    </div>
                @empty
                    <p class="text-muted mb-0">No sales yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
