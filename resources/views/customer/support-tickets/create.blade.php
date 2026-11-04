@extends('layouts.account')

@section('title', __('New ticket - SpazoraHome'))
@section('heading', __('New support ticket'))
@section('crumb', __('New ticket'))

@section('account')
    <div class="card card-body">
        <form method="POST" action="{{ route('customer.support-tickets.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold" for="subject">{{ __('Subject') }}</label>
                    <input type="text" id="subject" name="subject" maxlength="255" value="{{ old('subject') }}" class="form-control @error('subject') is-invalid @enderror" required>
                    @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="category">{{ __('Category') }}</label>
                    <select id="category" name="category" class="form-select @error('category') is-invalid @enderror">
                        @foreach(['order' => __('Order'), 'product' => __('Product'), 'shipping' => __('Shipping'), 'payment' => __('Payment'), 'return' => __('Return'), 'other' => __('Other')] as $k => $l)
                            <option value="{{ $k }}" {{ old('category', 'order') === $k ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                    @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="priority">{{ __('Priority') }}</label>
                    <select id="priority" name="priority" class="form-select @error('priority') is-invalid @enderror">
                        @foreach(['low' => __('Low'), 'medium' => __('Medium'), 'high' => __('High'), 'urgent' => __('Urgent')] as $k => $l)
                            <option value="{{ $k }}" {{ old('priority', 'medium') === $k ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                    @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold" for="description">{{ __('Describe your issue') }}</label>
                    <textarea id="description" name="description" rows="6" class="form-control @error('description') is-invalid @enderror" required>{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary">{{ __('Submit ticket') }}</button>
                <a href="{{ route('customer.support-tickets.index') }}" class="btn btn-outline-primary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
@endsection
