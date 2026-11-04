@extends('layouts.account')

@section('title', __('Edit address - SpazoraHome'))
@section('heading', __('Edit address'))
@section('crumb', __('Edit address'))

@section('account')
    <div class="card card-body">
        <form method="POST" action="{{ route('customer.addresses.update', $address) }}">
            @method('PUT')
            @include('customer.addresses._form', ['address' => $address])
        </form>
    </div>
@endsection
