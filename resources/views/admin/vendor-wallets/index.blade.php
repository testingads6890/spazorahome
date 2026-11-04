@extends('layouts.admin')

@section('title', 'Seller wallets')

@php $inr = fn ($v) => '₹' . number_format((float) $v, 2); @endphp

@section('content')
@unless($ready)
    <div class="alert alert-warning">The wallet table is not on this server yet. Run <code>php artisan migrate</code> to switch the wallet on.</div>
@endunless

<form method="GET" class="row g-2 mb-3">
    <div class="col-sm-6 col-md-4"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Search seller"></div>
    <div class="col-auto"><button class="btn btn-outline-primary">Search</button></div>
    <div class="col text-md-end small text-muted align-self-center">Hold period: {{ $holdDays > 0 ? $holdDays . ' day(s) after delivery' : 'none' }} (set <code>WALLET_HOLD_DAYS</code> in .env)</div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Seller</th><th class="text-end">Wallet balance</th><th class="text-end">On hold</th><th class="text-end">Available</th><th class="text-end">Earned</th><th class="text-end">Withdrawn / paid</th><th></th></tr></thead>
            <tbody>
            @forelse($vendors as $v)
                @php $m = $money[$v->id]; @endphp
                <tr>
                    <td><a href="{{ route('admin.vendor-wallets.show', $v) }}">{{ $v->business_name }}</a>@if($v->status !== 'approved')<span class="badge text-bg-secondary ms-1">{{ ucfirst($v->status) }}</span>@endif</td>
                    <td class="text-end {{ $m['balance'] < 0 ? 'text-danger' : '' }}">{{ $inr($m['balance']) }}</td>
                    <td class="text-end">{{ $inr($m['held']) }}</td>
                    <td class="text-end fw-bold">{{ $inr(max(0, $m['available'])) }}</td>
                    <td class="text-end">{{ $inr($m['earned']) }}</td>
                    <td class="text-end">{{ $inr($m['withdrawn']) }}</td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.vendor-wallets.show', $v) }}">Statement</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No sellers found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $vendors->links() }}</div>
@endsection
