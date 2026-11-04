@extends('layouts.account')

@section('title', __('Add address - SpazoraHome'))
@section('heading', __('Add address'))
@section('crumb', __('New address'))

@section('account')
    <div class="card card-body">
        <form method="POST" action="{{ route('customer.addresses.store') }}">
            @include('customer.addresses._form')
        </form>
    </div>
@endsection
