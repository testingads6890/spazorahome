@foreach($cartItems as $item)
    @php $img = $item->product->photo(); @endphp
    <div class="sum-item">
        <span class="mini">@if($img)<img src="{{ $img['thumb'] }}" alt="">@else<i class="bi bi-house-heart"></i>@endif<b>{{ $item->quantity }}</b></span>
        <span class="flex-grow-1 small">
            {{ $item->product->name }}
            @if($item->variant)<span class="d-block text-muted">{{ $item->variant->label }}</span>@endif
        </span>
        <span class="small fw-semibold">{{ $inr($item->currentUnitPrice() * $item->quantity) }}</span>
    </div>
@endforeach
<hr>

                    <div class="coupon-box mb-3">
                        @if($couponCode)
                            <div class="d-flex justify-content-between align-items-center gap-2 p-2 rounded" style="background:var(--brand-100)">
                                <span class="small"><i class="bi bi-tag-fill text-accent"></i> {{ __('shop.coupon.applied', ['code' => $couponCode]) }}</span>
                                <form action="{{ route('customer.coupon.remove') }}" method="POST">@csrf @method('DELETE')<button class="btn btn-link btn-sm text-danger p-0">{{ __('shop.coupon.remove') }}</button></form>
                            </div>
                        @else
                            <form action="{{ route('customer.coupon.apply') }}" method="POST" class="d-flex gap-2">
                                @csrf
                                <input type="text" name="code" value="{{ old('code') }}" class="form-control form-control-sm text-uppercase flex-grow-1" style="min-width:0" placeholder="{{ __('shop.coupon.code') }}" maxlength="50" aria-label="{{ __('shop.coupon.code') }}" autocomplete="off">
                                <button class="btn btn-outline-primary btn-sm">{{ __('shop.coupon.apply') }}</button>
                            </form>
                            @if(session('coupon_error'))<div class="text-danger small mt-1">{{ session('coupon_error') }}</div>@endif
                            @error('coupon')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        @endif
                    </div>
<div class="sum-row"><span>{{ __('shop.cart.subtotal') }}</span><span>{{ $inr($subtotal) }}</span></div>
@if($discount > 0)<div class="sum-row text-success"><span>{{ __('shop.cart.coupon_discount') }}</span><span>-{{ $inr($discount) }}</span></div>@endif
<div class="sum-row"><span>{{ __('shop.checkout.gst') }}</span><span class="js-tax">{{ $inr($tax) }}</span></div>
<div class="sum-row"><span>{{ __('shop.checkout.shipping') }}</span><span class="js-shipping">{{ $default ? ($shipping == 0 ? __('shop.checkout.free') : $inr($shipping)) : __('shop.checkout.choose_address') }}</span></div>
<hr>
<div class="sum-row total"><span>{{ __('shop.checkout.total') }}</span><strong class="js-total">{{ $inr($total) }}</strong></div>
