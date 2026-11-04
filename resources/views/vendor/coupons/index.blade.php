@extends('layouts.vendor')

@section('title', 'Coupons')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <p class="text-muted mb-0">Create discount codes for your own products. A code only takes money off your items in the customer's cart, and the discount comes out of your earnings.</p>
    <a href="{{ route('vendor.coupons.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New coupon</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Code</th><th>Discount</th><th>Min. order</th><th>Valid</th><th>Used</th><th>Status</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse($coupons as $c)
                @php $problem = $c->problem(); @endphp
                <tr>
                    <td><code class="fs-6">{{ $c->code }}</code>@if($c->title)<div class="small text-muted">{{ $c->title }}</div>@endif</td>
                    <td>{{ $c->label() }}@if($c->max_discount)<div class="small text-muted">up to ₹{{ number_format((float) $c->max_discount) }}</div>@endif</td>
                    <td>{{ (float) $c->min_order > 0 ? '₹' . number_format((float) $c->min_order) : '—' }}</td>
                    <td class="small">{{ $c->starts_at?->format('d M Y') ?? 'Now' }} – {{ $c->ends_at?->format('d M Y') ?? 'No end' }}</td>
                    <td>{{ $c->used_count }}{{ $c->usage_limit ? ' / ' . $c->usage_limit : '' }}</td>
                    <td><span class="badge bg-{{ $problem ? 'secondary' : 'success' }}">{{ $problem ? 'Not usable' : 'Active' }}</span>@if($problem)<div class="small text-muted">{{ $problem }}</div>@endif</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('vendor.coupons.edit', $c) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form method="POST" action="{{ route('vendor.coupons.destroy', $c) }}" class="d-inline" onsubmit="return confirm('Delete coupon {{ $c->code }}?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No coupons yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $coupons->links() }}</div>
@endsection
