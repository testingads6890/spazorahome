@extends('layouts.account')

@section('title', __('Write a review - SpazoraHome'))
@section('heading', __('Write a review'))
@section('crumb', __('New review'))

@section('account')
    <div class="card card-body">
        <form method="POST" action="{{ route('customer.reviews.store') }}">
            @include('customer.reviews._form')
        </form>
    </div>
@endsection
