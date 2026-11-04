@extends('layouts.vendor')

@section('title', 'Returns')

@section('content')
<p class="text-muted">Items customers sent back. A credit note has been issued for each one, and its amount is taken out of your payout.</p>
<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Order</th><th>Item</th><th>Customer</th><th class="text-end">Qty returned</th><th class="text-end">Credited</th><th>Returned on</th></tr></thead>
            <tbody>
            @forelse($lines as $line)
                @php $notes = $byItem[$line->order_item_id] ?? collect(); $first = $notes->first(); @endphp
                <tr>
                    <td><a href="{{ route('vendor.orders.show', $line) }}" class="fw-semibold">{{ $line->order?->order_number ?? '#' . $line->order_id }}</a></td>
                    <td>{{ $line->orderItem?->product_name }}<div class="small text-muted">Ordered {{ $line->orderItem?->quantity }}</div></td>
                    <td>{{ $line->order?->user?->name ?? 'Customer' }}</td>
                    <td class="text-end">{{ $notes->sum(fn ($n) => (int) ($n->quantity ?? 0)) ?: '—' }}</td>
                    <td class="text-end">@php $amt = $notes->sum(fn ($n) => (float) ($n->total ?? $n->amount ?? $n->subtotal ?? 0)); @endphp{{ $amt > 0 ? '₹' . number_format($amt, 2) : '—' }}</td>
                    <td class="small">{{ $first?->created_at?->format('d M Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No returns. That is good news.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $lines->links() }}</div>
@endsection
