@extends('layouts.account')

@section('title', __('Invoices - SpazoraHome'))
@section('heading', __('Invoices'))
@section('crumb', __('Invoices'))

@php $inr = fn ($v) => '₹' . number_format((float) $v, 2); @endphp

@section('account')
    <p class="text-muted">{{ __('GST tax invoices for your orders, and credit notes for anything returned or cancelled. Open one to print or save it as a PDF.') }}</p>

    @forelse($orders as $order)
        <div class="order-card mb-3">
            <div class="d-flex flex-wrap justify-content-between gap-2">
                <div>
                    <div class="fw-bold">{{ __('Invoice') }} {{ $order->invoice_number }}</div>
                    <div class="small text-muted">{{ optional($order->invoice_date ?? $order->created_at)->translatedFormat('d M Y') }} &middot; <a href="{{ route('customer.orders.show', $order) }}">{{ $order->order_number }}</a></div>
                </div>
                <div class="text-end">
                    <div class="fw-bold">{{ $inr($order->total) }}</div>
                    <span class="status-badge {{ $order->status }}">@status($order->status)</span>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3">
                @if($order->supplierInvoices->isNotEmpty())
                    @foreach($order->supplierInvoices as $si)
                        <a href="{{ route('customer.orders.invoices.show', [$order, $si]) }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm"><i class="bi bi-receipt"></i> {{ $si->number }} &middot; {{ $si->supplierName() }}</a>
                    @endforeach
                @else
                    <a href="{{ route('customer.orders.invoice', $order) }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm"><i class="bi bi-receipt"></i> {{ __('View invoice') }}</a>
                @endif
                @foreach($order->creditNotes as $note)
                    <a href="{{ route('customer.orders.credit-notes.show', [$order, $note]) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm"><i class="bi bi-receipt-cutoff"></i> {{ __('Credit note') }} {{ $note->number }} &middot; {{ $inr($note->total) }}</a>
                @endforeach
            </div>
        </div>
    @empty
        <div class="empty"><i class="bi bi-receipt"></i><h5 class="mt-3">{{ __('No invoices yet') }}</h5><p>{{ __('An invoice appears here as soon as an order is confirmed.') }}</p><a href="{{ route('search') }}" class="btn btn-primary">{{ __('Start shopping') }}</a></div>
    @endforelse
    <div class="mt-3">{{ $orders->links() }}</div>
@endsection
