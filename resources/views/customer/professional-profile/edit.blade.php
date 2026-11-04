@extends('layouts.account')

@section('title', __('Professional profile - SpazoraHome'))
@section('heading', __('Professional profile'))
@section('crumb', __('Professional profile'))

@php $pp = auth()->user()->professionalProfile; @endphp

@section('account')
    <div class="card card-body">
        @if($pp)<p class="mb-3">{{ __('Status:') }} <span class="status-badge {{ $pp->is_verified ? 'delivered' : 'pending' }}">{{ $pp->is_verified ? __('Verified') : __('Verification pending') }}</span></p>@endif
        <form method="POST" action="{{ route('customer.professional-profile.store') }}">
            @csrf
            <div class="row g-3">
                @foreach([['company_name', __('Company name'), 'text', 'col-md-6', true], ['designation', __('Designation'), 'text', 'col-md-6', false], ['experience_years', __('Years of experience'), 'number', 'col-md-4', false], ['license_number', __('License number'), 'text', 'col-md-4', false], ['website', __('Website'), 'url', 'col-md-4', false]] as [$n, $l, $t, $c, $r])
                    <div class="{{ $c }}">
                        <label class="form-label fw-semibold" for="{{ $n }}">{{ $l }}</label>
                        <input type="{{ $t }}" id="{{ $n }}" name="{{ $n }}" value="{{ old($n, $pp?->{$n}) }}" class="form-control @error($n) is-invalid @enderror" {{ $r ? 'required' : '' }} @if($t === 'number') min="0" @endif>
                        @error($n)<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endforeach
                <div class="col-12">
                    <label class="form-label fw-semibold" for="specialization">{{ __('Specialization') }}</label>
                    <input type="text" id="specialization" name="specialization" value="{{ old('specialization', $pp?->specialization) }}" class="form-control @error('specialization') is-invalid @enderror">
                    @error('specialization')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold" for="description">{{ __('About') }}</label>
                    <textarea id="description" name="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', $pp?->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <button class="btn btn-primary mt-4">{{ __('Save professional profile') }}</button>
        </form>
    </div>
@endsection
