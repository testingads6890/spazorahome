@extends('layouts.account')

@section('title', __('Order :number', ['number' => $order->order_number]) . ' - SpazoraHome')
@section('heading', __('Order :number', ['number' => $order->order_number]))
@section('crumb', $order->order_number)

@php
    $inr = fn ($v) => '₹' . number_format((float) $v, 2);
    $steps = \App\Support\OrderProgress::steps($order);
    $payment = $order->payments->sortByDesc('id')->first();
    $ship = $order->shippingAddress;
    $bill = $order->billingAddress;
    $pdfReady = app(\App\Services\InvoicePdf::class)->available();
    $refunded = (float) $order->payments->sum('refunded_amount');
    $refundDue = (float) $order->payments->sum('refund_due');
@endphp

@section('account')
    @if(auth()->user()->is_guest)
        <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span><i class="bi bi-person-lock"></i> {{ __('You checked out as a guest. Create a password so you can sign in later and see your orders.') }}</span>
            <a href="{{ route('customer.profile.edit') }}#password" class="btn btn-primary btn-sm">{{ __('Create a password') }}</a>
        </div>
    @endif
    <div class="card card-body mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div class="small text-muted">{{ __('Placed on :date', ['date' => $order->created_at->translatedFormat('d M Y, h:i A')]) }}</div>
            <span class="status-badge {{ $order->status }}">@status($order->status)</span>
        </div>

        @if($order->status === 'cancelled')
            <div class="alert alert-danger mb-0">{{ __('This order was cancelled.') }}@if($order->creditNote) {{ __('Credit note :number has been issued.', ['number' => $order->creditNote->number]) }}@endif</div>
        @else
            <ol class="timeline steps-long" aria-label="{{ __('Order progress') }}">
                @foreach($steps as $step)
                    <li class="{{ $step['state'] === 'next' ? '' : $step['state'] }}" @if($step['state'] === 'now') aria-current="step" @endif>{{ $step['label'] }}</li>
                @endforeach
            </ol>
            @if($order->tracking_number)
                <div class="mt-3 small">
                    <i class="bi bi-truck"></i> {{ $order->carrier ?: __('Carrier') }} &middot; {{ __('Tracking') }} <code>{{ $order->tracking_number }}</code>
                    @if($order->tracking_url)&middot; <a href="{{ $order->tracking_url }}" target="_blank" rel="noopener">{{ __('Carrier website') }}</a>@endif
                </div>
            @endif
        @endif

        <div class="d-flex flex-wrap gap-2 mt-3">
            @if($order->supplierInvoices->isNotEmpty())
                @foreach($order->supplierInvoices as $si)
                    <a href="{{ route('customer.orders.invoices.show', [$order, $si]) }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm"><i class="bi bi-receipt"></i> {{ __('Invoice') }}{{ $order->supplierInvoices->count() > 1 ? ': ' . $si->supplierName() : '' }}</a>
                    @if($pdfReady)<a href="{{ route('customer.orders.invoices.pdf', [$order, $si]) }}" class="btn btn-outline-primary btn-sm" aria-label="{{ __('Invoice PDF') }} {{ $si->number }}"><i class="bi bi-file-earmark-pdf"></i> PDF</a>@endif
                @endforeach
            @elseif($order->hasInvoice())<a href="{{ route('customer.orders.invoice', $order) }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm"><i class="bi bi-receipt"></i> {{ __('Invoice') }}</a>
                @if($pdfReady)<a href="{{ route('customer.orders.invoice.pdf', $order) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-file-earmark-pdf"></i> {{ __('Invoice PDF') }}</a>@endif
            @endif
            @foreach($order->creditNotes as $note)
                <a href="{{ route('customer.orders.credit-notes.show', [$order, $note]) }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm"><i class="bi bi-receipt-cutoff"></i> {{ __('Credit note') }} {{ $order->creditNotes->count() > 1 ? $note->number : '' }}</a>
            @endforeach
            @if($order->tracking_number)<a href="{{ route('customer.order-tracking.track', ['tracking_number' => $order->tracking_number]) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-geo-alt"></i> {{ __('Track') }}</a>@endif
            @if(\Illuminate\Support\Facades\Schema::hasTable('return_requests') && \App\Models\ReturnRequest::windowOpen($order) && array_sum($order->returnableQuantities()) > 0)
                @if(\App\Models\ReturnRequest::where('order_id', $order->id)->where('status', 'pending')->exists())
                    <a href="{{ route('customer.returns.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-hourglass-split"></i> {{ __('Return requested') }}</a>
                @else
                    <a href="{{ route('customer.returns.create', $order) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-return-left"></i> {{ __('Return items') }}</a>
                @endif
            @endif
            @if($payment && $payment->method === 'razorpay' && $payment->status === 'pending' && $order->status !== 'cancelled')
                <a href="{{ route('payments.razorpay.initiate', $order) }}" class="btn btn-accent btn-sm"><i class="bi bi-credit-card"></i> {{ __('Pay now') }}</a>
            @endif
            @if($order->isCancellable())
                <form action="{{ route('customer.orders.cancel', $order) }}" method="POST" onsubmit="return confirm(@js(__('Cancel this order?')))">@csrf
                    <button class="btn btn-outline-danger btn-sm">{{ __('Cancel order') }}</button>
                </form>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6"><div class="card card-body h-100">
            <h6 class="text-muted text-uppercase small">{{ __('Delivery address') }}</h6>
            @if($ship)<strong>{{ $ship->full_name }}</strong><span class="small text-muted">{{ $ship->address_line_1 }}@if($ship->address_line_2), {{ $ship->address_line_2 }}@endif<br>{{ $ship->city }}, {{ $ship->state }} {{ $ship->postal_code }}<br>{{ $ship->phone }}</span>@else<span class="text-muted">-</span>@endif
        </div></div>
        <div class="col-md-6"><div class="card card-body h-100">
            <h6 class="text-muted text-uppercase small">{{ __('Payment') }}</h6>
            @if($payment)
                <strong>{{ $payment->method === 'cod' ? __('Cash on delivery') : __('Online payment') }}</strong>
                <span class="small"><span class="status-badge {{ $payment->status === 'completed' ? 'delivered' : ($payment->status === 'pending' ? 'pending' : 'cancelled') }}">@status($payment->status)</span></span>
                @if($payment->transaction_id)<span class="small text-muted mt-1">{{ __('Ref :id', ['id' => $payment->transaction_id]) }}</span>@endif
                @if($refunded > 0)<span class="small text-success mt-1">{{ __('Refunded: :amount', ['amount' => $inr($refunded)]) }}</span>@endif
                @if($refundDue > 0)<span class="small text-muted mt-1">{{ __('Refund in progress: :amount', ['amount' => $inr($refundDue)]) }}</span>@endif
            @endif
            @if($bill && $ship && $bill->id !== $ship->id)<span class="small text-muted mt-2">{{ __('Billing: :place', ['place' => $bill->city . ', ' . $bill->state]) }}</span>@endif
        </div></div>
    </div>

    <div class="card card-body mb-3">
        <h5 class="mb-3">{{ __('Items') }}</h5>
        @foreach($order->items as $item)
            @php $img = $item->product?->photo(); @endphp
            <div class="sum-item {{ $loop->last ? '' : 'border-bottom pb-2 mb-1' }}">
                <span class="mini" style="width:56px;height:56px">@if($img)<img src="{{ $img['thumb'] }}" alt="">@else<i class="bi bi-house-heart"></i>@endif</span>
                <span class="flex-grow-1">
                    @if($item->product)<a href="{{ route('products.show', $item->product) }}" class="fw-semibold">{{ $item->product_name }}</a>@else<span class="fw-semibold">{{ $item->product_name }}</span>@endif
                    <span class="d-block small text-muted">{{ __('SKU :sku', ['sku' => $item->product_sku]) }} &middot; {{ $item->quantity }} &times; {{ $inr($item->unit_price) }}</span>
                    @if($order->status === 'delivered' && $item->product)<a class="small" href="{{ route('products.show', $item->product) }}#reviews">{{ __('Write a review') }}</a>@endif
                </span>
                <strong>{{ $inr($item->subtotal) }}</strong>
            </div>
        @endforeach
    </div>

    <div class="card card-body">
        <h5 class="mb-3">{{ __('Order summary') }}</h5>
        <div class="sum-row"><span>{{ __('Subtotal') }}</span><span>{{ $inr($order->subtotal) }}</span></div>
        <div class="sum-row"><span>{{ __('GST') }}</span><span>{{ $inr($order->tax) }}</span></div>
        <div class="sum-row"><span>{{ __('Shipping') }}</span><span>{{ (float) $order->shipping == 0 ? __('Free') : $inr($order->shipping) }}</span></div>
        @if($order->discount > 0)<div class="sum-row text-success"><span>{{ __('Coupon discount') }} @if($order->coupon_code)({{ $order->coupon_code }})@endif</span><span>-{{ $inr($order->discount) }}</span></div>@endif
        <hr>
        <div class="sum-row total"><span>{{ __('Total') }}</span><strong>{{ $inr($order->total) }}</strong></div>
        @if($order->creditNotes->where('is_full', false)->isNotEmpty())<div class="sum-row text-muted"><span>{{ __('Returned items credited') }}</span><span>-{{ $inr($order->creditNotes->where('is_full', false)->sum('total')) }}</span></div>@endif
        @if($order->is_export)<p class="small text-muted mt-2 mb-0">{{ __('International order: no GST charged.') }}@if($order->charge_currency) {{ __('Your card was charged :amount.', ['amount' => $order->charge_currency . ' ' . number_format((float) $order->charge_total, 2)]) }}@endif</p>@endif
        @if($order->shipping_method)<p class="small text-muted mt-2 mb-0">{{ __('Delivery option: :name', ['name' => $order->shipping_method]) }}</p>@endif
        @if($order->notes)<p class="small text-muted mt-3 mb-0"><strong>{{ __('Your note:') }}</strong> {{ $order->notes }}</p>@endif
    </div>
@endsection
