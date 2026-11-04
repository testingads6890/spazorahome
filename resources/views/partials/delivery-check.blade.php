{{-- Delivery check for the product page: the shopper types a PIN and sees whether this product comes to it. --}}
@php
    $deliveryArea = \App\Support\DeliveryArea::current();
    $deliverable = \App\Support\DeliveryArea::canDeliver($product, $deliveryArea);
    $limited = \App\Support\DeliveryArea::hasLocation($product);
@endphp
<div class="delivery-check mb-3 small">
    <form action="{{ route('delivery-area.store') }}" method="POST" class="d-flex flex-wrap gap-2 align-items-center">
        @csrf
        <i class="bi bi-geo-alt" aria-hidden="true"></i>
        <label for="delivery-pin" class="visually-hidden">{{ __('Delivery PIN code') }}</label>
        <input id="delivery-pin" name="pincode" type="text" inputmode="numeric" maxlength="6" class="form-control form-control-sm" style="max-width:9rem" placeholder="{{ __('Enter PIN') }}" value="{{ $deliveryArea?->code }}">
        <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Check') }}</button>
        @if($deliveryArea)
            <button type="submit" formaction="{{ route('delivery-area.clear') }}" class="btn btn-sm btn-link p-0">{{ __('Change') }}</button>
        @endif
    </form>
    <div class="mt-2">
        @if($deliveryArea && $deliverable)
            <span class="text-success"><i class="bi bi-check-circle-fill"></i> {{ __('Delivers to :pin', ['pin' => $deliveryArea->code]) }}</span>
        @elseif($deliveryArea && ! $deliverable)
            <span class="text-danger"><i class="bi bi-x-circle-fill"></i> {{ __('Not delivered to :pin. Try another PIN code.', ['pin' => $deliveryArea->code]) }}</span>
        @elseif($limited)
            <span class="text-muted">{{ __('Enter your PIN code to check delivery to your area.') }}</span>
        @else
            <span class="text-muted">{{ __('Delivered across India.') }}</span>
        @endif
    </div>
</div>
