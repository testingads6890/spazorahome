@extends('layouts.vendor')

@section('title', 'Edit product')

@php $resubmit = in_array($product->vendor_status, ['rejected', 'changes_requested'], true); @endphp

@section('content')
@if($product->vendor_status === 'changes_requested')
    <div class="alert alert-warning"><strong>The marketplace team asked for changes:</strong> {{ $product->vendor_rejection_reason ?: 'Please review the listing.' }}<span class="d-block small mt-1">Make the changes and save. The listing then goes back for approval.</span></div>
@elseif($product->vendor_status === 'rejected')
    <div class="alert alert-danger"><strong>This listing was rejected:</strong> {{ $product->vendor_rejection_reason ?: 'No reason was given.' }}<span class="d-block small mt-1">You can correct it and save to send it for approval again.</span></div>
@elseif($product->vendor_status === 'pending')
    <div class="alert alert-info">This listing is waiting for approval. You can keep editing it meanwhile.</div>
@endif

<form method="POST" action="{{ route('vendor.products.update', $product) }}" enctype="multipart/form-data" novalidate>
    @csrf
    @method('PUT')
    @include('vendor.products._form')
    <div class="d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary">{{ $resubmit ? 'Save and send for approval' : 'Save changes' }}</button>
        <a href="{{ route('vendor.products.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<form method="POST" action="{{ route('vendor.products.destroy', $product) }}" class="mt-4" onsubmit="return confirm('Remove this product from your store?')">
    @csrf
    @method('DELETE')
    <button class="btn btn-link text-danger p-0">Remove this product</button>
</form>
@endsection
