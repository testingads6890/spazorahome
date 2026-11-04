@extends('layouts.app')

@section('title', __('Bulk enquiry') . ' - SpazoraHome')
@section('meta_description', __('Ask us about bulk and trade orders for your project.'))

@section('content')
<div class="container py-4" style="max-width: 760px">
    <h1 class="h3 mb-1">{{ __('Bulk enquiry') }}</h1>
    <p class="text-muted mb-4">{{ __('Buying for a project, office or a shop? Tell us what you need and we will reply with pricing.') }}</p>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <form action="{{ route('bulk-enquiry.store') }}" method="POST" class="card card-body">
        @csrf
        {{-- Hidden from people; bots fill it in. --}}
        <div style="position:absolute;left:-9999px" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="be-name" class="form-label">{{ __('Your name') }} *</label>
                <input id="be-name" name="name" type="text" maxlength="120" required class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="be-email" class="form-label">{{ __('Email') }} *</label>
                <input id="be-email" name="email" type="email" required class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="be-wa" class="form-label">{{ __('WhatsApp number') }} *</label>
                <input id="be-wa" name="whatsapp" type="tel" required class="form-control @error('whatsapp') is-invalid @enderror" value="{{ old('whatsapp') }}" placeholder="+91 98xxxxxxxx">
                @error('whatsapp')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="be-other" class="form-label">{{ __('Other phone number') }}</label>
                <input id="be-other" name="other_phone" type="tel" class="form-control @error('other_phone') is-invalid @enderror" value="{{ old('other_phone') }}">
                @error('other_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                @include('partials.category-picker', ['product' => null, 'pickerTypeField' => 'product_type_id'])
            </div>

            <div class="col-12">
                <label for="be-product" class="form-label">{{ __('Product name, if you have one') }}</label>
                <input id="be-product" name="product_name" type="text" maxlength="255" class="form-control" value="{{ old('product_name') }}" placeholder="{{ __('e.g. Sheesham dining chair') }}">
            </div>
            <div class="col-12">
                <label for="be-msg" class="form-label">{{ __('Message') }}</label>
                <textarea id="be-msg" name="message" rows="4" maxlength="2000" class="form-control">{{ old('message') }}</textarea>
            </div>
        </div>
        <button class="btn btn-primary mt-4">{{ __('Send enquiry') }}</button>
    </form>
</div>
@endsection

