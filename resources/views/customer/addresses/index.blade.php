@extends('layouts.account')

@section('title', __('My addresses - SpazoraHome'))
@section('heading', __('My addresses'))
@section('crumb', __('Addresses'))

@section('account')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted mb-0">{{ __('Saved addresses are used at checkout.') }}</p>
        <a href="{{ route('customer.addresses.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> {{ __('Add address') }}</a>
    </div>

    <div class="row g-3">
        @forelse($addresses as $address)
            <div class="col-md-6">
                <div class="card card-body h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-light text-dark border">{{ __(ucfirst($address->type)) }}</span>
                        @if($address->is_default)<span class="badge" style="background:var(--brand)">{{ __('Default') }}</span>@endif
                    </div>
                    <strong>{{ $address->full_name }}</strong>
                    <span class="text-muted small">
                        {{ $address->address_line_1 }}@if($address->address_line_2), {{ $address->address_line_2 }}@endif<br>
                        {{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}<br>{{ $address->country }}
                        @if($address->phone)<br><i class="bi bi-telephone"></i> {{ $address->phone }}@endif
                    </span>
                    <div class="d-flex gap-2 mt-3">
                        <a href="{{ route('customer.addresses.edit', $address) }}" class="btn btn-outline-primary btn-sm">{{ __('Edit') }}</a>
                        <form action="{{ route('customer.addresses.destroy', $address) }}" method="POST" onsubmit="return confirm(@js(__('Delete this address?')))">@csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm">{{ __('Delete') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="empty"><i class="bi bi-geo-alt"></i><h5 class="mt-3">{{ __('No addresses yet') }}</h5><p>{{ __('Add one to check out faster.') }}</p><a href="{{ route('customer.addresses.create') }}" class="btn btn-primary">{{ __('Add address') }}</a></div></div>
        @endforelse
    </div>
    <div class="d-flex justify-content-center mt-3">{{ $addresses->links() }}</div>
@endsection
