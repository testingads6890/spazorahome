@extends('layouts.app')

@section('title', $product->name . ' - Gallery')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('products.category', $product->category) }}">{{ $product->category->name }}</a></li>
                    <li class="breadcrumb-item active">{{ $product->name }}</li>
                </ol>
            </nav>

            <h1 class="mb-4">{{ $product->name }} - Gallery</h1>

            <div class="gallery-container">
                <div class="row g-3">
                    @foreach($images as $image)
                        <div class="col-md-4 col-sm-6">
                            <a href="{{ asset($image->image) }}" data-lightbox="product-gallery" data-title="{{ $product->name }}">
                                <img src="{{ asset($image->image) }}" 
                                     alt="{{ $product->name }}" 
                                     class="img-fluid rounded shadow-sm gallery-image"
                                     loading="lazy">
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('products.show', $product) }}" class="btn btn-primary">
                    <i class="bi bi-arrow-left"></i> Back to Product
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Lightbox CSS and JS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>

<style>
.gallery-image {
    transition: transform 0.3s ease;
    cursor: pointer;
    width: 100%;
    height: 250px;
    object-fit: cover;
}

.gallery-image:hover {
    transform: scale(1.05);
}

.gallery-container {
    padding: 20px 0;
}
</style>
@endsection