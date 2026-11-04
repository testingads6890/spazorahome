@extends('layouts.vendor')

@section('title', 'Payouts')

@php
    $inr = fn ($v) => '₹' . number_format((float) $v, 2);
    $badge = ['pending' => 'warning', 'paid' => 'success', 'failed' => 'secondary'];
    $label = ['pending' => 'Due', 'paid' => 'Paid', 'failed' => 'Cancelled'];
    $bank = (array) ($vendor->bank_details ?? []);
@endphp

@section('content')
<div class="row g-3 mb-3">
    @foreach([
        ['Due to you', $summary['due'], 'bi-hourglass-split', 'Paid out after delivery and the return window.'],
        ['Ready (delivered)', $summary['delivered_due'], 'bi-truck', 'Due on orders already delivered.'],
        ['Paid so far', $summary['paid'], 'bi-check2-circle', 'Transferred to your bank account.'],
        ['Total sales', $summary['sales'], 'bi-graph-up', 'Before commission and TCS.'],
    ] as [$t, $v, $i, $hint])
        <div class="col-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small"><i class="bi {{ $i }}"></i> {{ $t }}</div>
                <div class="fs-4 fw-bold">{{ $inr($v) }}</div>
                <div class="small text-muted">{{ $hint }}</div>
            </div></div>
        </div>
    @endforeach
</div>

@if(\Illuminate\Support\Facades\Route::has('vendor.withdrawals.index'))
    <div class="alert alert-light border d-flex flex-wrap justify-content-between align-items-center gap-2"><span><i class="bi bi-bank"></i> Money from delivered orders can be withdrawn to your bank or UPI.</span><a href="{{ route('vendor.withdrawals.index') }}" class="btn btn-sm btn-primary">Request withdrawal</a></div>
@endif

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card h-100"><div class="card-body small">
            <strong>How your payout is worked out</strong>
            <div class="d-flex justify-content-between mt-2"><span>Sales</span><span>{{ $inr($summary['sales']) }}</span></div>
            <div class="d-flex justify-content-between text-danger"><span>Marketplace commission</span><span>− {{ $inr($summary['commission']) }}</span></div>
            <div class="d-flex justify-content-between text-danger"><span>TCS held under GST</span><span>− {{ $inr($summary['tcs']) }}</span></div>
            <hr class="my-2">
            <div class="d-flex justify-content-between fw-bold"><span>Your earnings</span><span>{{ $inr($summary['sales'] - $summary['commission'] - $summary['tcs']) }}</span></div>
        </div></div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100"><div class="card-body small">
            <strong>Paid to</strong>
            @if($bank)
                <div class="mt-2">{{ $bank['account_holder'] ?? $bank['holder'] ?? $vendor->business_name }}</div>
                <div class="text-muted">{{ $bank['bank_name'] ?? '' }}@if(! empty($bank['account_number'] ?? $bank['account'] ?? null)) &middot; A/c ending {{ substr((string) ($bank['account_number'] ?? $bank['account']), -4) }}@endif</div>
                @if(! empty($bank['ifsc']))<div class="text-muted">IFSC {{ $bank['ifsc'] }}</div>@endif
            @else
                <div class="text-muted mt-2">No bank details yet.</div>
            @endif
            @if(\Illuminate\Support\Facades\Route::has('vendor.profile.edit'))<a href="{{ route('vendor.profile.edit') }}" class="d-inline-block mt-2">Update bank details</a>@endif
        </div></div>
    </div>
</div>

<ul class="nav nav-pills mb-3 flex-wrap">
    @foreach($tabs as $key => $t)
        <li class="nav-item"><a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ route('vendor.payouts.index', ['tab' => $key]) }}">{{ $t }} <span class="badge text-bg-light border">{{ $counts[$key] }}</span></a></li>
    @endforeach
</ul>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Order</th><th>Item</th><th class="text-end">Sale</th><th class="text-end">Commission</th><th class="text-end">TCS</th><th class="text-end">Your payout</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($lines as $line)
                <tr>
                    <td class="text-nowrap"><a href="{{ route('vendor.orders.show', $line) }}" class="fw-semibold">{{ $line->order?->order_number ?? '#' . $line->order_id }}</a><div class="small text-muted">{{ $line->created_at?->format('d M Y') }}</div></td>
                    <td>{{ $line->orderItem?->product_name ?? '—' }}@if($line->orderItem)<div class="small text-muted">Qty {{ $line->orderItem->quantity }}</div>@endif</td>
                    <td class="text-end">{{ $inr($line->subtotal) }}</td>
                    <td class="text-end text-danger">− {{ $inr($line->commission) }}</td>
                    <td class="text-end text-danger">− {{ $inr($line->tcs_amount) }}</td>
                    <td class="text-end fw-bold">{{ $inr($line->payout_amount) }}</td>
                    <td><span class="badge bg-{{ $badge[$line->payout_status] ?? 'secondary' }}">{{ $label[$line->payout_status] ?? ucfirst((string) $line->payout_status) }}</span>@if($line->paid_at)<div class="small text-muted">{{ \Illuminate\Support\Carbon::parse($line->paid_at)->format('d M Y') }}</div>@endif</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No earnings yet. They appear here as soon as a customer orders one of your products.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $lines->links() }}</div>
@endsection
