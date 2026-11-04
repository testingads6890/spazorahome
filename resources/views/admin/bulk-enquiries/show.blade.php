@extends('layouts.admin')

@section('title', 'Bulk enquiry #' . $enquiry->id)

@section('content')
<a href="{{ route('admin.bulk-enquiries.index') }}" class="btn btn-secondary btn-sm mb-3">Back</a>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card card-body">
            <h1 class="h5">{{ $enquiry->product_type ?: $enquiry->product_name }} × {{ $enquiry->quantity }}</h1>
            <dl class="row mb-0">
                <dt class="col-sm-4">Name</dt><dd class="col-sm-8">{{ $enquiry->name }}</dd>
                <dt class="col-sm-4">Other number</dt><dd class="col-sm-8">@if($enquiry->other_phone)<a href="tel:{{ $enquiry->other_phone }}">{{ $enquiry->other_phone }}</a>@else —@endif</dd>
                <dt class="col-sm-4">Email</dt><dd class="col-sm-8"><a href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a></dd>
                <dt class="col-sm-4">WhatsApp</dt><dd class="col-sm-8">@if($enquiry->whatsapp)<a href="https://wa.me/{{ preg_replace('/\D/', '', $enquiry->whatsapp) }}" target="_blank" rel="noopener">{{ $enquiry->whatsapp }}</a>@else —@endif</dd>
                <dt class="col-sm-4">Product type</dt><dd class="col-sm-8">{{ $enquiry->product_type ?: '—' }}</dd>
                <dt class="col-sm-4">Categories</dt><dd class="col-sm-8">{{ $enquiry->categoryNames() }}</dd>
                <dt class="col-sm-4">Product name</dt><dd class="col-sm-8">{{ $enquiry->product_name ?: '—' }}</dd>
                <dt class="col-sm-4">Message</dt><dd class="col-sm-8">{{ $enquiry->message ?: '—' }}</dd>
                <dt class="col-sm-4">Received</dt><dd class="col-sm-8">{{ $enquiry->created_at?->format('d M Y H:i') }}</dd>
            </dl>
        </div>
    </div>
    <div class="col-lg-5">
        <form action="{{ route('admin.bulk-enquiries.update', $enquiry) }}" method="POST" class="card card-body">
            @csrf @method('PUT')
            <label for="status" class="form-label">Status</label>
            <select id="status" name="status" class="form-select mb-3">
                @foreach(\App\Models\BulkEnquiry::STATUSES as $key => $label)<option value="{{ $key }}" @selected($enquiry->status === $key)>{{ $label }}</option>@endforeach
            </select>
            <label for="admin_note" class="form-label">Note for the team</label>
            <textarea id="admin_note" name="admin_note" rows="4" class="form-control mb-3">{{ old('admin_note', $enquiry->admin_note) }}</textarea>
            <button class="btn btn-primary">Save</button>
        </form>
    </div>
</div>
@endsection
