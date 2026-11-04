@extends('layouts.vendor')

@section('title', 'Add product')

@section('content')
<p class="text-muted">A new product is checked by the marketplace team before it appears on the website.</p>

<form method="POST" action="{{ route('vendor.products.store') }}" enctype="multipart/form-data" novalidate>
    @csrf
    @include('vendor.products._form')
    <div class="d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary">Submit for approval</button>
        <a href="{{ route('vendor.products.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
@endsection
