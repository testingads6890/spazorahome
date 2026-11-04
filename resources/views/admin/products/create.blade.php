@extends('layouts.admin')

@section('title', 'Add product')

@section('content')
<div class="container mt-4">
    <a href="{{ route('admin.products.index') }}" class="btn btn-secondary mb-3">Back</a>
    <div class="card">
        <div class="card-header">Add Product</div>
        <div class="card-body">
            <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @include('admin.products._form')
                <button class="btn btn-primary">Save product</button>
            </form>
        </div>
    </div>
</div>
@endsection
