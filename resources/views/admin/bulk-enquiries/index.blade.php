@extends('layouts.admin')

@section('title', 'Bulk enquiries')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Bulk enquiries</h1>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Sr. No.</th><th>Received</th><th>Name</th><th>Contact</th><th>Product</th><th>Category</th><th class="text-end">Qty</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($enquiries as $e)
                <tr>
                    <td>{{ $enquiries->firstItem() + $loop->index }}</td>
                    <td class="small">{{ $e->created_at?->format('d M Y H:i') }}</td>
                    <td>{{ $e->name }}</td>
                    <td class="small">WhatsApp: {{ $e->whatsapp }}@if($e->other_phone)<br>Other: {{ $e->other_phone }}@endif<br>{{ $e->email }}</td>
                    <td>{{ $e->product_type ?: $e->product_name }}@if($e->product_name && $e->product_type)<div class="small text-muted">{{ $e->product_name }}</div>@endif</td>
                    <td class="small">{{ $e->categoryNames() }}</td>
                    <td class="text-end">{{ $e->quantity }}</td>
                    <td><span class="badge bg-secondary-subtle text-secondary-emphasis">{{ \App\Models\BulkEnquiry::STATUSES[$e->status] ?? $e->status }}</span></td>
                    <td><a href="{{ route('admin.bulk-enquiries.show', $e) }}" class="btn btn-sm btn-outline-primary">Open</a></td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-4">No enquiries yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $enquiries->links() }}</div>
@endsection
