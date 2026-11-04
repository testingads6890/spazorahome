@extends('layouts.vendor')

@section('title', 'My orders')

@php
    $tone = ['pending' => 'warning', 'processing' => 'info', 'ready_to_ship' => 'primary', 'shipped' => 'primary', 'delivered' => 'success'];
    $next = ['pending' => ['processing', 'Start processing'], 'processing' => ['ready_to_ship', 'Mark ready to ship'], 'ready_to_ship' => ['shipped', 'Mark shipped'], 'shipped' => ['delivered', 'Mark delivered']];
    $inr = fn ($v) => '₹' . number_format((float) $v, 2);
@endphp

@section('content')
<div class="d-flex flex-wrap justify-content-end mb-3">
    <a href="{{ route('vendor.orders.export') }}" class="btn btn-outline-primary"><i class="bi bi-download"></i> Export CSV</a>
</div>

<ul class="nav nav-pills flex-nowrap overflow-auto gap-1 mb-3 pb-1">
    @foreach($tabs as $key => $label)
        <li class="nav-item"><a class="nav-link text-nowrap {{ $tab === $key ? 'active' : '' }}" href="{{ route('vendor.orders.index', $key === 'all' ? [] : ['status' => $key]) }}">{{ $label }} <span class="badge {{ $tab === $key ? 'text-bg-light' : 'text-bg-secondary' }}">{{ $counts[$key] }}</span></a></li>
    @endforeach
</ul>

<div class="table-responsive">
<table class="table table-bordered align-middle">
    <thead><tr><th>Order</th><th>Product</th><th>Customer</th><th>Amount</th><th>Status</th><th></th></tr></thead>
    <tbody>
        @forelse($orders as $vo)
            @php
                $cancelled = $vo->payout_status === 'failed';
                $wasReturned = in_array($vo->order_item_id, $returnedIds, true);
                $ship = $vo->order?->shippingAddress;
            @endphp
            <tr>
                <td class="text-nowrap"><a href="{{ route('vendor.orders.show', $vo) }}" class="fw-semibold">{{ $vo->order?->order_number ?? '#' . $vo->order_id }}</a><div class="small text-muted">{{ $vo->created_at?->format('d M Y') }}</div></td>
                <td>{{ $vo->orderItem?->product_name }}<div class="small text-muted">Qty {{ $vo->orderItem?->quantity }}</div></td>
                <td>{{ $vo->order?->user?->name ?? 'Customer' }}@if($ship)<div class="small text-muted">{{ collect([$ship->city, $ship->state])->filter()->implode(', ') }}</div>@endif</td>
                <td class="text-nowrap">{{ $inr($vo->subtotal) }}</td>
                <td>
                    @if($cancelled)<span class="badge text-bg-danger">Cancelled</span>
                    @else<span class="badge text-bg-{{ $tone[$vo->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_', ' ', $vo->status)) }}</span>@endif
                    @if($wasReturned)<span class="badge text-bg-secondary">Returned</span>@endif
                </td>
                <td class="text-nowrap">
                    @if(! $cancelled && isset($next[$vo->status]))
                        <form action="{{ route('vendor.orders.updateStatus', $vo) }}" method="POST" class="d-inline">@csrf @method('PUT')
                            <input type="hidden" name="status" value="{{ $next[$vo->status][0] }}">
                            <button class="btn btn-sm btn-primary">{{ $next[$vo->status][1] }}</button>
                        </form>
                    @endif
                    <a href="{{ route('vendor.orders.show', $vo) }}" class="btn btn-sm btn-outline-secondary">View</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No orders here.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
{{ $orders->links() }}
@endsection
