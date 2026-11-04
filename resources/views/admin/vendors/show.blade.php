@extends('layouts.admin')

@section('title', 'Vendor: ' . $vendor->business_name)

@php
    $bank = (array) ($vendor->bank_details ?? []);
    $needs = $vendor->appliedWithDocuments() ? $vendor->unverifiedRequiredDocuments() : [];
    $badge = ['approved' => 'success', 'pending' => 'warning', 'under_review' => 'info', 'rejected' => 'danger', 'suspended' => 'secondary'][$vendor->status] ?? 'secondary';
    $docBadge = ['verified' => 'success', 'pending' => 'warning', 'rejected' => 'danger'];
    $row = fn (string $label, $value) => filled($value) ? '<dt class="col-sm-4 text-muted fw-normal">' . e($label) . '</dt><dd class="col-sm-8">' . e($value) . '</dd>' : '';
@endphp

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <a href="{{ route('admin.vendors.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> All vendors</a>
    <span class="badge bg-{{ $badge }} fs-6">{{ ucfirst(str_replace('_', ' ', $vendor->status)) }}</span>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

{{-- Decision --}}
<div class="card mb-3">
    <div class="card-header fw-semibold">Verification</div>
    <div class="card-body">
        @if($vendor->status === 'approved')
            <p class="mb-3">Approved {{ optional($vendor->approved_at)->format('j M Y, g:i a') }}. The store is {{ $vendor->is_active ? 'live' : 'not active' }}.</p>
            <form action="{{ route('admin.vendors.suspend', $vendor) }}" method="POST" onsubmit="return confirm('Suspend this seller? Their store goes offline.')">@csrf
                <button class="btn btn-warning btn-sm">Suspend seller</button>
            </form>
        @else
            @if($needs)
                <p class="text-danger mb-3"><i class="bi bi-exclamation-triangle"></i> Still to verify before approval: <strong>{{ collect($needs)->map(fn ($t) => ucfirst(str_replace('_', ' ', $t)))->implode(', ') }}</strong>.</p>
            @elseif($vendor->appliedWithDocuments())
                <p class="text-success mb-3"><i class="bi bi-check-circle"></i> All required documents are verified. This seller can be approved.</p>
            @else
                <p class="text-muted mb-3">This account was created before documents were collected, so there is nothing to verify.</p>
            @endif
            @if($vendor->status === 'rejected' && $vendor->rejection_reason)<p class="mb-3"><strong>Rejected:</strong> {{ $vendor->rejection_reason }}</p>@endif
            <div class="d-flex flex-wrap gap-2 align-items-start">
                @if(in_array($vendor->status, ['pending', 'rejected'], true))
                    <form action="{{ route('admin.vendors.under-review', $vendor) }}" method="POST">@csrf
                        <button class="btn btn-outline-info"><i class="bi bi-search"></i> Mark under review</button>
                    </form>
                @endif
                <form action="{{ route('admin.vendors.approve', $vendor) }}" method="POST">@csrf
                    <button class="btn btn-success" @if($needs) disabled title="Verify the required documents first" @endif><i class="bi bi-check-lg"></i> Approve seller</button>
                </form>
                <form action="{{ route('admin.vendors.reject', $vendor) }}" method="POST" class="d-flex flex-wrap gap-2 flex-grow-1">@csrf
                    <input type="text" name="reason" class="form-control" style="flex:1 1 240px" placeholder="Reason shown to the seller" aria-label="Reason for rejecting" maxlength="1000" required>
                    <button class="btn btn-outline-danger">Reject application</button>
                </form>
            </div>
        @endif
    </div>
</div>

{{-- Documents --}}
<div class="card mb-3">
    <div class="card-header fw-semibold">Documents</div>
    <div class="card-body">
        @forelse($vendor->documents as $doc)
            <div class="border rounded p-3 mb-2">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <strong>{{ $doc->title() }}</strong>
                        @if(in_array($doc->type, $vendor->requiredDocumentTypes(), true))<span class="badge text-bg-light border">Required</span>@endif
                        <div class="small text-muted">{{ $doc->original_name }} &middot; {{ $doc->sizeLabel() }} &middot; uploaded {{ $doc->updated_at->format('j M Y') }}</div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="badge bg-{{ $docBadge[$doc->status] ?? 'secondary' }}">{{ ucfirst($doc->status) }}</span>
                        <a href="{{ route('admin.vendors.documents.show', [$vendor, $doc]) }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm"><i class="bi bi-eye"></i> Open</a>
                    </div>
                </div>
                @if($doc->status === 'verified')
                    <p class="small text-success mt-2 mb-0">Verified {{ optional($doc->verified_at)->format('j M Y, g:i a') }}@if($doc->verifier) by {{ $doc->verifier->name }}@endif</p>
                @endif
                @if($doc->status === 'rejected' && $doc->note)<p class="small text-danger mt-2 mb-0">Rejected: {{ $doc->note }}</p>@endif
                <div class="d-flex flex-wrap gap-2 mt-3">
                    @if($doc->status !== 'verified')
                        <form action="{{ route('admin.vendors.documents.verify', [$vendor, $doc]) }}" method="POST">@csrf
                            <button class="btn btn-success btn-sm"><i class="bi bi-patch-check"></i> Mark verified</button>
                        </form>
                    @endif
                    @if($doc->status !== 'rejected')
                        <form action="{{ route('admin.vendors.documents.reject', [$vendor, $doc]) }}" method="POST" class="d-flex flex-wrap gap-2 flex-grow-1">@csrf
                            <input type="text" name="note" class="form-control form-control-sm" style="flex:1 1 220px" placeholder="What is wrong with this file?" aria-label="Reason for rejecting {{ $doc->title() }}" maxlength="500" required>
                            <button class="btn btn-outline-danger btn-sm">Reject file</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-muted mb-0">No documents were uploaded with this account.</p>
        @endforelse
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Business</div>
            <div class="card-body">
                <dl class="row mb-0">
                    {!! $row('Business name', $vendor->business_name) !!}
                    {!! $row('Business type', $vendor->businessTypeLabel()) !!}
                    {!! $row('Contact person', $vendor->contact_name ?: $vendor->user?->name) !!}
                    {!! $row('Email', $vendor->email) !!}
                    {!! $row('Mobile', $vendor->phone) !!}
                    @if($vendor->website)<dt class="col-sm-4 text-muted fw-normal">Website</dt><dd class="col-sm-8"><a href="{{ $vendor->website }}" target="_blank" rel="noopener nofollow">{{ $vendor->website }}</a></dd>@endif
                    {!! $row('Sells', $categories->implode(', ')) !!}
                    {!! $row('Address', collect([$vendor->address, $vendor->city, $vendor->state, $vendor->postal_code, $vendor->country])->filter()->implode(', ')) !!}
                    {!! $row('Applied', $vendor->created_at?->format('j M Y')) !!}
                    {!! $row('Terms accepted', $vendor->terms_accepted_at?->format('j M Y, g:i a')) !!}
                    {!! $row('Commission', number_format($vendor->effectiveCommissionRate(), 2) . '%' . ($vendor->commission_rate === null ? ' (default)' : '')) !!}
                </dl>
                <form action="{{ route('admin.vendors.commission.update', $vendor) }}" method="POST" class="border-top pt-3 mt-3">
                    @csrf
                    @method('PUT')
                    <label for="commission_rate" class="form-label fw-semibold">Commission for this seller (%)</label>
                    <div class="input-group mb-1">
                        <input type="number" id="commission_rate" name="commission_rate" class="form-control" min="0" max="100" step="0.01" placeholder="{{ number_format(\App\Models\Vendor::defaultCommissionRate(), 2) }} (default)" value="{{ old('commission_rate', $vendor->commission_rate) }}">
                        <span class="input-group-text">%</span>
                        <button class="btn btn-outline-primary" type="submit">Save</button>
                    </div>
                    @error('commission_rate')<div class="text-danger small mb-1">{{ $message }}</div>@enderror
                    <div class="form-text">Leave blank to use the site default ({{ number_format(\App\Models\Vendor::defaultCommissionRate(), 2) }}%).</div>
                </form>
                @if($vendor->description)<hr><p class="small text-muted mb-1">About the business</p><p class="mb-0" style="white-space:pre-line">{{ $vendor->description }}</p>@endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Registration and GST</div>
            <div class="card-body">
                <dl class="row mb-0">
                    {!! $row('Registration no.', $vendor->registration_number) !!}
                    <dt class="col-sm-4 text-muted fw-normal">GST registered</dt><dd class="col-sm-8">{{ $vendor->gst_registered ? 'Yes' : ($vendor->tax_number ? 'Yes' : 'No') }}</dd>
                    {!! $row('GSTIN', $vendor->tax_number) !!}
                </dl>
            </div>
        </div>
        <div class="card">
            <div class="card-header fw-semibold">Bank details for payouts</div>
            <div class="card-body">
                @if($bank)
                    <dl class="row mb-0">
                        {!! $row('Account holder', $bank['account_holder'] ?? null) !!}
                        {!! $row('Bank', $bank['bank_name'] ?? null) !!}
                        {!! $row('Account number', $bank['account_number'] ?? null) !!}
                        {!! $row('IFSC / SWIFT', $bank['ifsc'] ?? null) !!}
                        {!! $row('UPI ID', $bank['upi'] ?? null) !!}
                    </dl>
                @else
                    <p class="text-muted mb-0">No bank details on file.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<h3 class="h5 mt-4">Products ({{ $vendor->products->count() }})</h3>
<div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead><tr><th>Name</th><th>Price</th><th>Status</th></tr></thead>
        <tbody>
            @forelse($vendor->products as $product)
                <tr><td>{{ $product->name }}</td><td>₹{{ number_format($product->price, 2) }}</td><td>{{ $product->vendor_status }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-muted">No products yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
