@extends('layouts.admin')

@section('title', 'Vendor Payouts')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="mb-0">Vendor Payouts</h1>
    <a href="{{ route('admin.exports.vendor-payouts') }}" class="btn btn-outline-primary"><i class="bi bi-download"></i> Export CSV</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<form action="{{ route('admin.vendor-payouts.store') }}" method="POST" class="row g-2 mb-4">
    @csrf
    <div class="col-md-3">
        <select name="vendor_id" class="form-select" required>
            <option value="">Vendor</option>
            @foreach($vendors as $v)<option value="{{ $v->id }}">{{ $v->business_name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2"><input type="number" step="0.01" min="0" name="amount" class="form-control" placeholder="Amount" required></div>
    <div class="col-md-2"><input type="text" name="method" class="form-control" placeholder="Method (bank, upi...)" required></div>
    <div class="col-md-3"><input type="text" name="transaction_id" class="form-control" placeholder="Transaction ID (optional)"></div>
    <div class="col-md-2"><button class="btn btn-primary w-100">Create Payout</button></div>
</form>

<table class="table table-bordered">
    <thead>
        <tr><th>Vendor</th><th>Amount</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
        @foreach($payouts as $payout)
            <tr>
                <td>{{ $payout->vendor->business_name }}</td>
                <td>₹{{ number_format($payout->amount, 2) }}</td>
                <td>{{ $payout->status }}</td>
                <td>
                    @if($payout->status == 'pending')
                        <form action="{{ route('admin.vendor-payouts.complete', $payout) }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-success">Complete</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
{{ $payouts->links() }}
@endsection
