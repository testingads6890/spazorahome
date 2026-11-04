@extends('layouts.account')

@section('title', __('Returns - SpazoraHome'))
@section('heading', __('Returns'))
@section('crumb', __('Returns'))

@php $badge = ['pending' => 'pending', 'approved' => 'delivered', 'rejected' => 'cancelled', 'cancelled' => 'cancelled']; @endphp

@section('account')
    <p class="text-muted">{{ __('You can ask to return items within :days days of delivery. Once we approve, we collect the item and refund you to the original payment method.', ['days' => (int) config('shop.policies.return_days', 7)]) }} <a href="{{ route('returns') }}">{{ __('Return policy') }}</a></p>

    @if($returnable->isNotEmpty())
        <div class="card card-body mb-4">
            <h2 class="h6 mb-3">{{ __('Start a return') }}</h2>
            <div class="d-grid gap-2">
                @foreach($returnable as $order)
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border rounded p-2">
                        <span><strong>{{ $order->order_number }}</strong> <span class="small text-muted">&middot; {{ $order->created_at->translatedFormat('d M Y') }}</span></span>
                        <a href="{{ route('customer.returns.create', $order) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-return-left"></i> {{ __('Return items') }}</a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <h2 class="h6 mb-3">{{ __('Your return requests') }}</h2>
    @forelse($requests as $r)
        <div class="order-card mb-3">
            <div class="d-flex flex-wrap justify-content-between gap-2">
                <div>
                    <div class="fw-bold">#R{{ $r->id }} &middot; <a href="{{ route('customer.orders.show', $r->order_id) }}">{{ $r->order?->order_number }}</a></div>
                    <div class="small text-muted">{{ $r->created_at->translatedFormat('d M Y') }} &middot; {{ __($r->reasonLabel()) }} &middot; {{ trans_choice(':count item|:count items', array_sum((array) $r->items), ['count' => array_sum((array) $r->items)]) }}</div>
                </div>
                <span class="status-badge {{ $badge[$r->status] ?? 'pending' }}">{{ __(\App\Models\ReturnRequest::STATUSES[$r->status] ?? ucfirst($r->status)) }}</span>
            </div>
            @if($r->customer_note)<p class="small mb-0 mt-2">{{ $r->customer_note }}</p>@endif
            @if($r->admin_note)<div class="small mt-2 p-2 rounded" style="background:#f6f3ee"><strong>{{ __('Our reply') }}:</strong> {{ $r->admin_note }}</div>@endif
            @if($r->credit_notes)<div class="small text-success mt-2"><i class="bi bi-receipt-cutoff"></i> {{ __('Credit note :number issued. See Invoices.', ['number' => $r->credit_notes]) }}</div>@endif
        </div>
    @empty
        <div class="empty"><i class="bi bi-arrow-return-left"></i><h5 class="mt-3">{{ __('No return requests') }}</h5><p>{{ __('Need to send something back? Choose a delivered order above.') }}</p></div>
    @endforelse
    <div class="mt-3">{{ $requests->links() }}</div>
@endsection
