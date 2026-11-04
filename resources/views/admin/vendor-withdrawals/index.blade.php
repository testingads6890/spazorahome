@extends('layouts.admin')

@section('title', 'Seller withdrawals')

@php $inr = fn ($v) => '₹' . number_format((float) $v, 2); @endphp

@section('content')
<ul class="nav nav-pills mb-3 flex-wrap">
    @foreach($statuses as $key => $label)
        <li class="nav-item"><a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ route('admin.vendor-withdrawals.index', ['status' => $key]) }}">{{ $label }} <span class="badge text-bg-light border">{{ (int) ($counts[$key] ?? 0) }}</span></a></li>
    @endforeach
</ul>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>#</th><th>Seller</th><th class="text-end">Amount</th><th class="text-end">Fee</th><th class="text-end">To transfer</th><th>Pay to</th><th>Requested</th><th style="min-width:280px"></th></tr></thead>
            <tbody>
            @forelse($requests as $w)
                @php $d = (array) $w->payout_details; @endphp
                <tr>
                    <td>W-{{ $w->id }}</td>
                    <td><a href="{{ route('admin.vendors.show', $w->vendor_id) }}">{{ $w->vendor?->business_name }}</a><div class="small"><a href="{{ route('admin.vendor-wallets.show', $w->vendor_id) }}">Wallet</a></div>@if($w->note)<div class="small text-muted">“{{ $w->note }}”</div>@endif</td>
                    <td class="text-end">{{ $inr($w->amount) }}</td>
                    <td class="text-end">{{ $inr($w->fee) }}</td>
                    <td class="text-end fw-bold">{{ $inr($w->net()) }}</td>
                    <td class="small">
                        @if($w->method === 'upi') UPI: {{ $d['upi'] ?? '—' }}
                        @else {{ $d['account_holder'] ?? '' }}<br>{{ $d['bank_name'] ?? '' }} {{ $d['account_number'] ?? '' }}<br>IFSC {{ $d['ifsc'] ?? '—' }} @endif
                    </td>
                    <td class="small">{{ $w->created_at->format('d M Y') }}@if($w->processor)<div class="text-muted">by {{ $w->processor->name }}</div>@endif</td>
                    <td>
                        @if($w->status === 'pending')
                            <form method="POST" action="{{ route('admin.vendor-withdrawals.approve', $w) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success">Approve</button></form>
                        @endif
                        @if(in_array($w->status, ['pending', 'approved'], true))
                            <form method="POST" action="{{ route('admin.vendor-withdrawals.paid', $w) }}" class="d-flex gap-1 mt-1">@csrf
                                <input name="transaction_ref" class="form-control form-control-sm" placeholder="UTR / transfer ref" required maxlength="100">
                                <button class="btn btn-sm btn-success text-nowrap">Mark paid</button>
                            </form>
                            <form method="POST" action="{{ route('admin.vendor-withdrawals.reject', $w) }}" class="d-flex gap-1 mt-1">@csrf
                                <input name="admin_note" class="form-control form-control-sm" placeholder="Reason for rejecting" required maxlength="1000">
                                <button class="btn btn-sm btn-outline-danger">Reject</button>
                            </form>
                        @else
                            @if($w->transaction_ref)<div class="small">Ref {{ $w->transaction_ref }}</div>@endif
                            @if($w->admin_note)<div class="small text-muted">{{ $w->admin_note }}</div>@endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">Nothing here.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $requests->links() }}</div>
@endsection
