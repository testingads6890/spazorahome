@extends('layouts.auth')

@section('title', 'Application status')
@section('pitch_title', 'Almost there.')
@section('pitch_text', 'Our team verifies every seller and their documents before a store goes live.')

@php
    $status = $vendor?->status;
    $docs = $vendor ? $vendor->documents()->orderBy('id')->get() : collect();
    $required = $vendor ? $vendor->requiredDocumentTypes() : [];
    $titles = [\App\Models\VendorDocument::COMPANY_REGISTRATION => 'Company registration certificate', \App\Models\VendorDocument::GST_CERTIFICATE => 'GST certificate'];
    $missing = $vendor && $vendor->appliedWithDocuments() ? array_values(array_diff($required, $docs->pluck('type')->all())) : [];
    $accept = '.' . str_replace(',', ',.', \App\Models\VendorDocument::MIMES);
    $stateLabel = ['pending' => 'Being checked', 'verified' => 'Verified', 'rejected' => 'Needs a new file'];
@endphp

@section('content')
    <div class="text-center">
        <div class="display-4 mb-2" style="color:var(--accent)"><i class="bi {{ $status === 'rejected' ? 'bi-exclamation-circle' : ($status === 'suspended' ? 'bi-pause-circle' : 'bi-hourglass-split') }}"></i></div>
        @if($status === 'rejected')
            <h1 class="auth-title">Application not approved</h1>
            <p class="text-muted">We could not approve your seller account yet.@if($vendor->rejection_reason) Our team's note:@endif</p>
            @if($vendor->rejection_reason)<p class="alert alert-warning text-start">{{ $vendor->rejection_reason }}</p>@endif
            <p class="text-muted small">If a document below needs replacing, upload a new file and your application goes back for review.</p>
        @elseif($status === 'suspended')
            <h1 class="auth-title">Account suspended</h1>
            <p class="text-muted">Your seller account is currently suspended. Please contact support.</p>
        @elseif($vendor && $status === 'under_review')
            <h1 class="auth-title">Verification in progress</h1>
            <p class="text-muted">Our team is now checking <strong>{{ $vendor->business_name }}</strong> and your documents. This usually takes 1&ndash;2 working days. We will open your seller dashboard as soon as the account is approved.</p>
            <ol class="small text-start text-muted mx-auto" style="max-width:340px">
                <li class="text-success">Application received</li>
                <li class="fw-semibold">Documents being verified</li>
                <li>Account approved and store live</li>
            </ol>
        @elseif($vendor)
            <h1 class="auth-title">Application under review</h1>
            <p class="text-muted">Thanks for applying, <strong>{{ $vendor->business_name }}</strong>. You get your seller dashboard as soon as our team has verified your documents and approved the account.</p>
        @else
            <h1 class="auth-title">No seller account</h1>
            <p class="text-muted">This account is not linked to a seller profile.</p>
        @endif
    </div>

    @if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif

    @if($vendor && $status !== 'suspended' && ($docs->isNotEmpty() || $missing))
        <h2 class="h6 mt-4 mb-2">Your documents</h2>
        <ul class="doc-list">
            @foreach($docs as $doc)
                <li>
                    <span class="doc-name">{{ $doc->title() }}<small><a href="{{ route('vendor.documents.show', $doc) }}" target="_blank" rel="noopener">{{ $doc->original_name ?: 'View file' }}</a> &middot; {{ $doc->sizeLabel() }}</small></span>
                    <span class="doc-state {{ $doc->status }}">{{ $stateLabel[$doc->status] ?? ucfirst($doc->status) }}</span>
                    @if($doc->status === 'rejected')
                        @if($doc->note)<span class="doc-note"><i class="bi bi-info-circle"></i> {{ $doc->note }}</span>@endif
                        <form action="{{ route('vendor.documents.store') }}" method="POST" enctype="multipart/form-data">@csrf
                            <input type="hidden" name="type" value="{{ $doc->type }}">
                            @if($doc->type === \App\Models\VendorDocument::ADDITIONAL)<input type="hidden" name="kind" value="{{ array_search($doc->label, \App\Models\VendorDocument::ADDITIONAL_KINDS, true) ?: 'other' }}">@endif
                            <input type="file" name="file" accept="{{ $accept }}" class="form-control form-control-sm" style="flex:1 1 200px" aria-label="New file for {{ $doc->title() }}" required>
                            <button class="btn btn-primary btn-sm">Upload new file</button>
                        </form>
                    @endif
                </li>
            @endforeach
            @foreach($missing as $type)
                <li>
                    <span class="doc-name">{{ $titles[$type] ?? $type }}<small>Required to approve your account</small></span>
                    <span class="doc-state missing">Not uploaded</span>
                    <form action="{{ route('vendor.documents.store') }}" method="POST" enctype="multipart/form-data">@csrf
                        <input type="hidden" name="type" value="{{ $type }}">
                        <input type="file" name="file" accept="{{ $accept }}" class="form-control form-control-sm" style="flex:1 1 200px" aria-label="{{ $titles[$type] ?? $type }}" required>
                        <button class="btn btn-primary btn-sm">Upload</button>
                    </form>
                </li>
            @endforeach
        </ul>
        <p class="small text-muted mt-2 mb-0">PDF, JPG, PNG or WEBP, up to 5 MB. Only our verification team can open these files.</p>
    @endif

    <form action="{{ route('logout') }}" method="POST" class="mt-4 text-center">@csrf
        <button class="btn btn-outline-primary">Sign out</button>
        <a href="{{ route('home') }}" class="btn btn-primary">Back to store</a>
    </form>
@endsection
