@extends('layouts.admin')

@section('title', 'Edit product')

@section('content')
<div class="container mt-4">
    <a href="{{ route('admin.products.index') }}" class="btn btn-secondary mb-3">Back</a>
    <div class="card">
        <div class="card-header">Edit Product</div>
        <div class="card-body">
            <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                @include('admin.products._form')
                <button class="btn btn-primary">Update</button>
            </form>
        </div>
    </div>
</div>
@endsection
