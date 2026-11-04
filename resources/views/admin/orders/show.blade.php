@extends('layouts.admin')

@section('title', '')

@php
    $pdfReady = app(\App\Services\InvoicePdf::class)->available();
    $hasReturnable = array_sum($returnable) > 0;
    $canCreditShipping = $returnable && (float) $order->shipping > 0 && ! $order->shippingCredited();
@endphp

@section('content')
<div class="container mt-4">
        <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary mb-3">Back</a>
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">Order Info</div>
                    <div class="card-body">
                        <p><strong>Order #:</strong> {{ $order->order_number }}</p>
                        <p><strong>Customer:</strong> {{ $order->user->name ?? 'Guest' }}</p>
                        <p><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $order->status)) }}</p>
                        <ol class="list-unstyled d-flex flex-wrap gap-1 mb-3">
                            @foreach(\App\Support\OrderProgress::steps($order) as $step)
                                <li class="badge rounded-pill {{ $step['state'] === 'done' ? 'text-bg-success' : ($step['state'] === 'now' ? 'text-bg-primary' : 'text-bg-light border') }}">{{ $step['label'] }}</li>
                            @endforeach
                        </ol>
                        @if($order->status !== 'cancelled')
                            <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="d-flex gap-2 mb-3">
                                @csrf @method('PUT')
                                <select name="status" class="form-select w-auto">
                                    @foreach(['pending', 'processing', 'ready_to_ship', 'shipped', 'out_for_delivery', 'delivered', 'cancelled'] as $s)
                                        <option value="{{ $s }}" {{ $order->status === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-primary btn-sm">Update</button>
                            </form>
                        @endif
                        @if($order->shipment && $order->status !== 'cancelled')
                            <form action="{{ route('admin.orders.shipment.update', $order) }}" method="POST" class="d-flex flex-wrap gap-2 align-items-center mb-3">
                                @csrf
                                <label for="shipStatus" class="small text-muted">Shipment ({{ $order->shipment->carrier }} {{ $order->shipment->tracking_number }}):</label>
                                <select name="status" id="shipStatus" class="form-select form-select-sm w-auto">
                                    @foreach(['pending' => 'Booked', 'picked_up' => 'Picked up', 'in_transit' => 'In transit', 'out_for_delivery' => 'Out for delivery', 'delivered' => 'Delivered'] as $k => $l)
                                        <option value="{{ $k }}" {{ $order->shipment->status === $k ? 'selected' : '' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-outline-primary btn-sm">Update shipment</button>
                            </form>
                        @elseif(! in_array($order->status, ['cancelled', 'delivered']))
                            <a href="{{ route('admin.orders.shipping.create', $order) }}" class="btn btn-outline-primary btn-sm mb-3">Create Shipment</a>
                        @endif
                        @if($order->supplierInvoices->isNotEmpty())
                            <div class="mb-3">
                                @foreach($order->supplierInvoices as $si)
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                                        <span><strong>{{ $si->number }}</strong> <small class="text-muted">{{ $si->supplierName() }}</small></span>
                                        <span class="text-nowrap">
                                            <a href="{{ route('admin.orders.invoices.show', [$order, $si]) }}" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener">Invoice</a>
                                            @if($pdfReady)<a href="{{ route('admin.orders.invoices.pdf', [$order, $si]) }}" class="btn btn-outline-secondary btn-sm">PDF</a>@endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @elseif($order->hasInvoice())
                            <a href="{{ route('admin.orders.invoice', $order) }}" class="btn btn-outline-secondary btn-sm mb-3" target="_blank" rel="noopener">Invoice</a>
                            @if($pdfReady)<a href="{{ route('admin.orders.invoice.pdf', $order) }}" class="btn btn-outline-secondary btn-sm mb-3">Invoice PDF</a>@endif
                        @endif
                        @if($order->creditNote && $order->supplierInvoices->isEmpty())
                            <a href="{{ route('admin.orders.credit-note', $order) }}" class="btn btn-outline-secondary btn-sm mb-3" target="_blank" rel="noopener">Credit Note</a>
                        @endif
                        @if($order->tracking_number)
                            <p><strong>Tracking:</strong> {{ $order->carrier }} — {{ $order->tracking_number }}</p>
                        @endif
                        @if($order->shipping_method)<p><strong>Delivery option:</strong> {{ $order->shipping_method }}</p>@endif
                        <p><strong>Subtotal:</strong> {{ number_format($order->subtotal, 2) }}</p>
                        <p><strong>Tax:</strong> {{ number_format($order->tax, 2) }}</p>
                        <p><strong>Shipping:</strong> {{ number_format($order->shipping, 2) }}</p>
                        <p><strong>Total:</strong> {{ number_format($order->total, 2) }}</p>
                        @if($order->creditNotes->isNotEmpty())<p><strong>Credited back:</strong> {{ number_format($order->creditedTotal(), 2) }}</p>@endif
                        <p><strong>Currency:</strong> {{ $order->currency }}</p>
                        @if($order->is_export)<p><a href="{{ route('admin.orders.packing-list', $order) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary"><i class="bi bi-box-seam"></i> Packing list</a></p><p><strong>Export order:</strong> {{ $order->shippingAddress?->country }}, no GST (zero-rated).@if($order->charge_currency) Card charged {{ $order->charge_currency }} {{ number_format((float) $order->charge_total, 2) }} at 1 INR = {{ rtrim(rtrim(number_format((float) $order->exchange_rate, 6), '0'), '.') }} {{ $order->charge_currency }}.@endif</p>@endif
                        <p><strong>Notes:</strong> {{ $order->notes ?? 'N/A' }}</p>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">Payments</div>
                    <div class="card-body">
                        @forelse($order->payments as $payment)
                            <div class="{{ $loop->last ? '' : 'border-bottom pb-2 mb-2' }}">
                                <p class="mb-1"><strong>{{ $payment->method === 'cod' ? 'Cash on delivery' : ucfirst($payment->method) }}</strong>
                                    &middot; {{ number_format($payment->amount, 2) }} &middot; {{ ucfirst($payment->status) }}
                                    @if($payment->transaction_id)<br><small class="text-muted">Ref {{ $payment->transaction_id }}</small>@endif</p>
                                @if((float) $payment->refunded_amount > 0)
                                    <p class="mb-1 text-success">Refunded: {{ number_format($payment->refunded_amount, 2) }}</p>
                                @endif
                                @if((float) $payment->refund_due > 0)
                                    <p class="mb-1 text-danger">Refund due, not sent yet: {{ number_format($payment->refund_due, 2) }}
                                        @if(! empty($payment->payment_details['refund_error']))<br><small>{{ $payment->payment_details['refund_error'] }}</small>@endif</p>
                                    <div class="d-flex flex-wrap gap-2">
                                        @if($payment->method === 'razorpay' && $payment->transaction_id)
                                            <form action="{{ route('admin.orders.payments.refund', [$order, $payment]) }}" method="POST">@csrf
                                                <button class="btn btn-primary btn-sm">Retry refund through Razorpay</button>
                                            </form>
                                        @endif
                                        <form action="{{ route('admin.orders.payments.refunded', [$order, $payment]) }}" method="POST" class="d-flex gap-2">@csrf
                                            <input type="text" name="reference" class="form-control form-control-sm" placeholder="Reference (optional)" maxlength="100">
                                            <button class="btn btn-outline-secondary btn-sm text-nowrap">Mark refunded by hand</button>
                                        </form>
                                    </div>
                                @endif
                                @if($payment->method === 'cod' && $payment->status === 'pending' && $order->status !== 'cancelled')
                                    <form action="{{ route('admin.orders.payments.paid', [$order, $payment]) }}" method="POST">@csrf
                                        <button class="btn btn-outline-primary btn-sm">Mark cash collected</button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <p class="mb-0 text-muted">No payment recorded.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">Order Items</div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
                            <tbody>
                                @foreach($order->items as $item)
                                    <tr>
                                        <td>{{ $item->product_name }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ number_format($item->unit_price, 2) }}</td>
                                        <td>{{ number_format($item->subtotal, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($order->creditNotes->isNotEmpty())
                    <div class="card mb-3">
                        <div class="card-header">Credit Notes</div>
                        <div class="card-body">
                            @foreach($order->creditNotes as $note)
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 {{ $loop->last ? '' : 'border-bottom pb-2 mb-2' }}">
                                    <span>
                                        <strong>{{ $note->number }}</strong> &middot; {{ number_format((float) ($note->total ?? $order->total), 2) }}
                                        <small class="d-block text-muted">{{ $note->issued_at->format('d M Y') }} &middot; {{ $note->is_full ? 'Closing' : 'Partial return' }}@if($note->reason) &middot; {{ $note->reason }}@endif</small>
                                    </span>
                                    <span class="text-nowrap">
                                        <a href="{{ route('admin.orders.credit-notes.show', [$order, $note]) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">View</a>
                                        @if($pdfReady)<a href="{{ route('admin.orders.credit-notes.pdf', [$order, $note]) }}" class="btn btn-outline-secondary btn-sm">PDF</a>@endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($hasReturnable || $canCreditShipping)
                    <div class="card mb-3">
                        <div class="card-header">Return Items</div>
                        <div class="card-body">
                            <p class="text-muted small">For a customer who sends back part of the order. A credit note is issued for the items below and the amount is refunded. To take back the whole order, set the status to Cancelled instead.</p>
                            <form action="{{ route('admin.orders.returns.store', $order) }}" method="POST">
                                @csrf
                                @foreach($order->items as $item)
                                    @if(($returnable[$item->id] ?? 0) > 0)
                                        <div class="d-flex justify-content-between align-items-center gap-3 border-bottom pb-2 mb-2">
                                            <label class="mb-0" for="return-{{ $item->id }}">{{ $item->product_name }}<small class="d-block text-muted">Can return up to {{ $returnable[$item->id] }}</small></label>
                                            <input type="number" id="return-{{ $item->id }}" name="quantities[{{ $item->id }}]" class="form-control form-control-sm" style="width: 90px; flex: none" min="0" max="{{ $returnable[$item->id] }}" value="{{ old('quantities.' . $item->id, 0) }}" aria-label="Quantity returning">
                                        </div>
                                    @endif
                                @endforeach
                                @if($canCreditShipping)
                                    <div class="form-check mb-2">
                                        <input type="hidden" name="credit_shipping" value="0">
                                        <input type="checkbox" class="form-check-input" name="credit_shipping" value="1" id="credit_shipping">
                                        <label class="form-check-label" for="credit_shipping">Also give back the shipping charge ({{ number_format((float) $order->shipping + (float) $order->shipping_tax, 2) }} with GST)</label>
                                    </div>
                                @endif
                                <div class="form-check mb-2">
                                    <input type="hidden" name="restock" value="0">
                                    <input type="checkbox" class="form-check-input" name="restock" value="1" id="restock" checked>
                                    <label class="form-check-label" for="restock">Put the returned items back in stock</label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="reason">Reason</label>
                                    <input type="text" name="reason" id="reason" class="form-control" maxlength="255" value="{{ old('reason') }}" placeholder="e.g. Damaged in transit">
                                </div>
                                <button class="btn btn-primary btn-sm">Issue credit note and refund</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection
