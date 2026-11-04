@extends('layouts.admin')

@section('title', 'Wallet: ' . $vendor->business_name)

@php $inr = fn ($v) => '₹' . number_format((float) $v, 2); @endphp

@section('content')
<div class="mb-3"><a href="{{ route('admin.vendor-wallets.index') }}" class="small"><i class="bi bi-arrow-left"></i> All seller wallets</a></div>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="row g-3 mb-3">
    @foreach([
        ['Wallet balance', $money['balance'], 'bi-wallet2'],
        ['On hold', $money['held'], 'bi-hourglass-split'],
        ['Available to withdraw', max(0, $money['available']), 'bi-check2-circle'],
        ['Withdrawn / paid out', $money['withdrawn'], 'bi-bank'],
    ] as [$t, $v, $i])
        <div class="col-6 col-xl-3"><div class="card h-100"><div class="card-body">
            <div class="text-muted small"><i class="bi {{ $i }}"></i> {{ $t }}</div>
            <div class="fs-4 fw-bold {{ $v < 0 ? 'text-danger' : '' }}">{{ $inr($v) }}</div>
        </div></div></div>
    @endforeach
</div>

@if(auth()->user()->hasRole('admin'))
<div class="card mb-3">
    <div class="card-header fw-semibold">Manual adjustment</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.vendor-wallets.adjust', $vendor) }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-sm-3 col-md-2">
                <label class="form-label" for="type">Action</label>
                <select id="type" name="type" class="form-select"><option value="credit">Add money</option><option value="debit">Take money</option></select>
            </div>
            <div class="col-sm-3 col-md-2">
                <label class="form-label" for="amount">Amount (₹)</label>
                <input id="amount" type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="reason">Reason <span class="text-muted">(the seller can read this)</span></label>
                <input id="reason" name="reason" class="form-control" minlength="5" maxlength="250" value="{{ old('reason') }}" required>
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100" onclick="return confirm('Post this adjustment to the seller wallet?')">Post</button></div>
        </form>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header fw-semibold">Statement</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Date</th><th>Description</th><th class="text-end">In</th><th class="text-end">Out</th><th class="text-end">Balance</th><th>By</th></tr></thead>
            <tbody>
            @forelse($transactions as $t)
                <tr>
                    <td class="small text-nowrap">{{ $t->created_at->format('d M Y h:i A') }}</td>
                    <td><span class="fw-semibold">{{ $t->label() }}</span>@if($t->isHeld())<span class="badge text-bg-warning ms-1">Held until {{ $t->available_at->format('d M') }}</span>@endif
                        @if($t->note)<div class="small text-muted">{{ $t->note }}</div>@endif</td>
                    <td class="text-end text-success">{{ $t->type === 'credit' ? $inr($t->amount) : '' }}</td>
                    <td class="text-end text-danger">{{ $t->type === 'debit' ? $inr($t->amount) : '' }}</td>
                    <td class="text-end fw-semibold">{{ $inr($t->balance_after) }}</td>
                    <td class="small text-muted">{{ $t->author?->name ?? 'System' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No activity yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $transactions->links() }}</div>
@endsection
