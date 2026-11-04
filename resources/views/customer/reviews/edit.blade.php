@extends('layouts.account')

@section('title', __('Edit review - SpazoraHome'))
@section('heading', __('Edit review'))
@section('crumb', __('Edit review'))

@section('account')
    <div class="card card-body">
        <p class="mb-3">{{ __('Reviewing') }} <strong>{{ $review->product?->name }}</strong></p>
        <form method="POST" action="{{ route('customer.reviews.update', $review) }}">
            @method('PUT')
            @include('customer.reviews._form', ['review' => $review, 'products' => null])
        </form>
    </div>
@endsection
