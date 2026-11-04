@extends('layouts.vendor')

@section('title', 'Wallet')

@php
    $inr = fn ($v) => '₹' . number_format((float) $v, 2);
    $available = max(0, $money['available']);
    $canWithdraw = $available >= $minimum;
@endphp

@section('content')
<div class="row g-3 mb-3">
    <div class="col-12 col-xl-4">
        <div class="card h-100 border-success"><div class="card-body">
            <div class="text-muted small"><i class="bi bi-wallet2"></i> Available to withdraw</div>
            <div class="display-6 fw-bold text-success">{{ $inr($available) }}</div>
            <div class="small text-muted mb-3">Wallet balance {{ $inr($money['balance']) }}@if($money['balance'] < 0) <span class="text-danger">(negative: a refund came after payout; it is set off against your next earnings)</span>@endif</div>
            @if($canWithdraw)
                <a href="{{ route('vendor.withdrawals.index') }}" class="btn btn-success"><i class="bi bi-bank"></i> Withdraw</a>
            @else
                <div class="small text-muted">You can withdraw once {{ $inr($minimum) }} is available.</div>
            @endif
        </div></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small"><i class="bi bi-hourglass-split"></i> On hold</div>
            <div class="fs-4 fw-bold">{{ $inr($money['held']) }}</div>
            <div class="small text-muted">
                @if($money['next_release']) {{ $inr($money['next_release_amount']) }} frees up on {{ $money['next_release']->format('d M') }}
                @elseif($holdDays > 0) Earnings wait {{ $holdDays }} day(s) after delivery
                @else Nothing is held @endif
            </div>
        </div></div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small"><i class="bi bi-graph-up-arrow"></i> Earned from orders</div>
            <div class="fs-4 fw-bold">{{ $inr($money['earned']) }}</div>
            <div class="small text-muted">After commission and TCS, delivered orders</div>
        </div></div>
    </div>
    <div class="col-12 col-xl-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small"><i class="bi bi-check2-circle"></i> Withdrawn / paid out</div>
            <div class="fs-4 fw-bold">{{ $inr($money['withdrawn']) }}</div>
            <div class="small text-muted">Includes requests waiting for transfer</div>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fw-semibold">Statement</span>
        <div class="d-flex flex-wrap gap-2">
            <div class="btn-group btn-group-sm" role="group" aria-label="Filter">
                <a class="btn btn-outline-secondary {{ ! $filter ? 'active' : '' }}" href="{{ route('vendor.wallet.index') }}">All</a>
                <a class="btn btn-outline-secondary {{ $filter === 'credit' ? 'active' : '' }}" href="{{ route('vendor.wallet.index', ['type' => 'credit']) }}">Money in</a>
                <a class="btn btn-outline-secondary {{ $filter === 'debit' ? 'active' : '' }}" href="{{ route('vendor.wallet.index', ['type' => 'debit']) }}">Money out</a>
            </div>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('vendor.wallet.export') }}"><i class="bi bi-download"></i> CSV</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Date</th><th>Description</th><th class="text-end">Money in</th><th class="text-end">Money out</th><th class="text-end">Balance</th></tr></thead>
            <tbody>
            @forelse($transactions as $t)
                <tr>
                    <td class="small text-nowrap">{{ $t->created_at->format('d M Y') }}<div class="text-muted">{{ $t->created_at->format('h:i A') }}</div></td>
                    <td>
                        <span class="fw-semibold">{{ $t->label() }}</span>
                        @if($t->isHeld())<span class="badge text-bg-warning ms-1">Held until {{ $t->available_at->format('d M') }}</span>@endif
                        @if($t->note)<div class="small text-muted">{{ $t->note }}</div>@endif
                    </td>
                    <td class="text-end text-success">{{ $t->type === 'credit' ? '+ ' . $inr($t->amount) : '' }}</td>
                    <td class="text-end text-danger">{{ $t->type === 'debit' ? '− ' . $inr($t->amount) : '' }}</td>
                    <td class="text-end fw-semibold">{{ $inr($t->balance_after) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No wallet activity yet. Money appears here when an order is delivered.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $transactions->links() }}</div>
@endsection
