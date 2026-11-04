@extends('layouts.vendor')

@section('title', 'Withdrawals')

@php
    $inr = fn ($v) => '₹' . number_format((float) $v, 2);
    $badge = ['pending' => 'warning', 'approved' => 'info', 'paid' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary'];
    $labels = \App\Models\VendorWithdrawal::STATUSES;
    $canRequest = $summary['available'] >= $minimum;
@endphp

@section('content')
<div class="row g-3 mb-3">
    @foreach([
        ['Available to withdraw', $summary['available'], 'bi-wallet2', $summary['lines'] . ' delivered order line(s)'],
        ['In open requests', $summary['requested'], 'bi-hourglass-split', 'Waiting for transfer'],
        ['Withdrawn so far', $summary['withdrawn'], 'bi-check2-circle', 'Paid to your account'],
        ['On the way', $summary['on_the_way'], 'bi-truck', 'Becomes available once delivered'],
    ] as [$t, $v, $i, $hint])
        <div class="col-6 col-xl-3"><div class="card h-100"><div class="card-body">
            <div class="text-muted small"><i class="bi {{ $i }}"></i> {{ $t }}</div>
            <div class="fs-4 fw-bold">{{ $inr($v) }}</div>
            <div class="small text-muted">{{ $hint }}</div>
        </div></div></div>
    @endforeach
</div>

@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header fw-semibold">Request a withdrawal</div>
            <div class="card-body">
                @if($canRequest)
                    <form method="POST" action="{{ route('vendor.withdrawals.store') }}" class="row g-3">
                        @csrf
                        <div class="col-sm-6">
                            <label class="form-label" for="amount">Amount (₹)</label>
                            <input type="number" step="0.01" min="{{ $minimum }}" max="{{ $summary['available'] }}" id="amount" name="amount" class="form-control" value="{{ old('amount', $summary['available']) }}" required>
                            <div class="form-text">Minimum {{ $inr($minimum) }}. Paid in whole orders, oldest first.@if($fee > 0) Fee {{ $inr($fee) }} per transfer.@endif</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="method">Pay to</label>
                            <select id="method" name="method" class="form-select">
                                <option value="bank_transfer" @disabled(empty($bank['account_number']))>Bank transfer{{ ! empty($bank['account_number']) ? ' (A/c ending ' . substr((string) $bank['account_number'], -4) . ')' : ' (add bank first)' }}</option>
                                <option value="upi" @disabled(empty($bank['upi'])) @selected(empty($bank['account_number']) && ! empty($bank['upi']))>UPI{{ ! empty($bank['upi']) ? ' (' . $bank['upi'] . ')' : ' (add UPI first)' }}</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="note">Note for our team <span class="text-muted">(optional)</span></label>
                            <input id="note" name="note" class="form-control" maxlength="500" value="{{ old('note') }}">
                        </div>
                        <div class="col-12"><button class="btn btn-primary"><i class="bi bi-send"></i> Request withdrawal</button></div>
                    </form>
                @else
                    <p class="text-muted mb-0">You can request a withdrawal once at least {{ $inr($minimum) }} is due on delivered orders. Right now {{ $inr($summary['available']) }} is available.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100"><div class="card-body small">
            <strong>How withdrawals work</strong>
            <ol class="mt-2 mb-2 ps-3">
                <li>Money from an order becomes available once it is <strong>delivered</strong>.</li>
                <li>You request a withdrawal; our team checks and approves it.</li>
                <li>We transfer it to your bank or UPI and add the transfer reference here.</li>
            </ol>
            @if(empty($bank['account_number']) && empty($bank['upi']))
                <div class="alert alert-warning py-2 mb-0">Add your bank account or UPI in <a href="{{ route('vendor.profile.edit') }}">Business profile</a> before requesting.</div>
            @else
                <a href="{{ route('vendor.profile.edit') }}">Change payout account</a>
            @endif
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-header fw-semibold">History</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>#</th><th>Requested</th><th class="text-end">Amount</th><th class="text-end">Fee</th><th class="text-end">You get</th><th>Pay to</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($requests as $w)
                <tr>
                    <td>W-{{ $w->id }}</td>
                    <td class="small">{{ $w->created_at->format('d M Y') }}<div class="text-muted">{{ count((array) $w->vendor_order_ids) }} order line(s)</div></td>
                    <td class="text-end">{{ $inr($w->amount) }}</td>
                    <td class="text-end text-danger">{{ (float) $w->fee > 0 ? '− ' . $inr($w->fee) : '—' }}</td>
                    <td class="text-end fw-bold">{{ $inr($w->net()) }}</td>
                    <td class="small">{{ $w->method === 'upi' ? 'UPI ' . ($w->payout_details['upi'] ?? '') : 'Bank ' . (isset($w->payout_details['account_number']) ? '···' . substr((string) $w->payout_details['account_number'], -4) : '') }}</td>
                    <td><span class="badge bg-{{ $badge[$w->status] ?? 'secondary' }}">{{ $labels[$w->status] ?? ucfirst($w->status) }}</span>
                        @if($w->transaction_ref)<div class="small text-muted">Ref {{ $w->transaction_ref }}</div>@endif
                        @if($w->admin_note)<div class="small text-muted">{{ $w->admin_note }}</div>@endif</td>
                    <td class="text-end">@if($w->status === 'pending')<form method="POST" action="{{ route('vendor.withdrawals.cancel', $w) }}">@csrf<button class="btn btn-sm btn-outline-secondary">Cancel</button></form>@endif</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No withdrawal requests yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $requests->links() }}</div>
@endsection
