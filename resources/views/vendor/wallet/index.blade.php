@extends('layouts.vendor')

@section('title', 'Wallet')

@php
    $inr = fn ($v) => '₹' . number_format((float) $v, 2);
    $filters = ['' => 'All', 'credit' => 'Credits', 'debit' => 'Debits'];
@endphp

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-0">Wallet</h1>
        <div class="small text-muted">Your earnings, held amounts and withdrawals. New order earnings are held for {{ $holdDays }} day(s) after delivery.</div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('vendor.wallet.export') }}"><i class="bi bi-download"></i> Download statement</a>
        <a class="btn btn-primary btn-sm" href="{{ route('vendor.withdrawals.index') }}"><i class="bi bi-cash-coin"></i> Withdraw</a>
    </div>
</div>

<div class="row g-3 mb-3">
    @foreach([
        ['Balance', $money['balance'], 'bi-wallet2', 'Everything credited to you, less withdrawals'],
        ['Available to withdraw', $money['available'], 'bi-check2-circle', 'Ready to withdraw'],
        ['Held', $money['held'], 'bi-hourglass-split', $money['next_release'] ? 'Next release ' . \Illuminate\Support\Carbon::parse($money['next_release'])->format('d M Y') : 'Released after the hold period'],
        ['Total earned', $money['earned'], 'bi-graph-up-arrow', 'From delivered orders'],
    ] as [$t, $v, $i, $hint])
        <div class="col-6 col-xl-3"><div class="card h-100"><div class="card-body">
            <div class="text-muted small"><i class="bi {{ $i }}"></i> {{ $t }}</div>
            <div class="fs-4 fw-bold">{{ $inr($v) }}</div>
            <div class="small text-muted">{{ $hint }}</div>
        </div></div></div>
    @endforeach
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fw-semibold">Transactions</span>
        <div class="btn-group btn-group-sm" role="group" aria-label="Filter">
            @foreach($filters as $value => $label)
                <a class="btn btn-outline-secondary {{ (string) $filter === (string) $value ? 'active' : '' }}"
                   href="{{ route('vendor.wallet.index', $value === '' ? [] : ['type' => $value]) }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th class="text-end">Amount</th>
                    <th class="text-end">Balance after</th>
                    <th>Available from</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                    <tr>
                        <td class="small text-nowrap">{{ $tx->created_at?->format('d M Y, h:i A') }}</td>
                        <td>
                            <div>{{ $tx->label() }}</div>
                            @if($tx->note)<div class="small text-muted">{{ $tx->note }}</div>@endif
                        </td>
                        <td class="text-end fw-semibold {{ $tx->type === 'credit' ? 'text-success' : 'text-danger' }}">
                            {{ $tx->type === 'credit' ? '+' : '−' }}{{ $inr($tx->amount) }}
                        </td>
                        <td class="text-end">{{ $tx->balance_after !== null ? $inr($tx->balance_after) : '—' }}</td>
                        <td class="small text-nowrap">
                            @if($tx->available_at && $tx->available_at->isFuture())
                                <span class="badge bg-warning text-dark">{{ $tx->available_at->format('d M Y') }}</span>
                            @elseif($tx->available_at)
                                <span class="text-muted">Released</span>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No wallet activity yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transactions->hasPages())
        <div class="card-footer">{{ $transactions->links() }}</div>
    @endif
</div>
@endsection
