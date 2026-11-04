@extends('layouts.admin')

@section('title', 'Return requests')

@section('content')
<ul class="nav nav-pills mb-3 flex-wrap">
    @foreach($statuses as $key => $label)
        <li class="nav-item"><a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ route('admin.return-requests.index', ['status' => $key]) }}">{{ $label }} <span class="badge text-bg-light border">{{ (int) ($counts[$key] ?? 0) }}</span></a></li>
    @endforeach
</ul>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

@forelse($requests as $r)
    @php $lines = $r->order?->items->keyBy('id') ?? collect(); @endphp
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-2">
                <div>
                    <strong>#R{{ $r->id }}</strong> &middot; <a href="{{ route('admin.orders.show', $r->order_id) }}">{{ $r->order?->order_number }}</a>
                    <div class="small text-muted">{{ $r->user?->name }} ({{ $r->user?->email }}) &middot; {{ $r->created_at->format('d M Y, g:i a') }}</div>
                </div>
                <span class="badge bg-{{ ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'][$r->status] ?? 'secondary' }} align-self-start">{{ $statuses[$r->status] ?? $r->status }}</span>
            </div>
            <div class="mt-2"><strong>Reason:</strong> {{ $r->reasonLabel() }}@if($r->customer_note)<div class="small text-muted">“{{ $r->customer_note }}”</div>@endif</div>
            <ul class="small mt-2 mb-2">
                @foreach((array) $r->items as $itemId => $qty)
                    <li>{{ $qty }} × {{ $lines[$itemId]->product_name ?? 'Item #' . $itemId }}</li>
                @endforeach
            </ul>
            @if($r->status === 'pending')
                <div class="d-flex flex-wrap gap-2 align-items-start">
                    <form method="POST" action="{{ route('admin.return-requests.approve', $r) }}" class="d-flex flex-wrap gap-2 align-items-center">@csrf
                        <div class="form-check"><input type="hidden" name="restock" value="0"><input class="form-check-input" type="checkbox" name="restock" value="1" id="restock{{ $r->id }}" checked><label class="form-check-label small" for="restock{{ $r->id }}">Put back in stock</label></div>
                        <input name="admin_note" class="form-control form-control-sm" style="width:220px" placeholder="Note to customer (optional)" maxlength="1000">
                        <button class="btn btn-sm btn-success" onclick="return confirm('Approve: issue the credit note and refund this customer?')">Approve and refund</button>
                    </form>
                    <form method="POST" action="{{ route('admin.return-requests.reject', $r) }}" class="d-flex gap-2">@csrf
                        <input name="admin_note" class="form-control form-control-sm" style="width:220px" placeholder="Reason for declining" required maxlength="1000">
                        <button class="btn btn-sm btn-outline-danger">Decline</button>
                    </form>
                </div>
            @else
                @if($r->credit_notes)<div class="small text-success">Credit note {{ $r->credit_notes }}</div>@endif
                @if($r->admin_note)<div class="small text-muted">Note: {{ $r->admin_note }}</div>@endif
            @endif
        </div>
    </div>
@empty
    <div class="card card-body text-center text-muted py-5">Nothing here.</div>
@endforelse
<div class="mt-3">{{ $requests->links() }}</div>
@endsection
