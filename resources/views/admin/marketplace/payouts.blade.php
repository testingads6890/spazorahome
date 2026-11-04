@extends('layouts.admin')

@section('title', 'Payouts')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb-3">
        <h2 class="h4 mb-0">Payouts</h2>
        <a href="{{ route('admin.marketplace.index') }}" class="btn btn-secondary">Back</a>
    </div>
    <table class="table table-hover align-middle">
        <thead class="table-light"><tr><th>Vendor</th><th>Amount</th><th>Status</th><th>Paid at</th></tr></thead>
        <tbody>
        @forelse($payouts as $row)
            <tr><td>{{ $row->vendor->business_name ?? "-" }}</td><td>{{ number_format($row->amount, 2) }}</td><td>{{ ucfirst($row->status) }}</td><td>{{ $row->paid_at }}</td></tr>
        @empty
            <tr><td colspan="10" class="text-center text-muted">Nothing here yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $payouts->links() }}
</div>
@endsection
