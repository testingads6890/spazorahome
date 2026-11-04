@extends('layouts.app')

@section('title', __('shop.checkout.title') . ' - SpazoraHome')

@php
    extract($totals);
    $inr = fn ($v) => '₹' . number_format((float) $v, 2);
    $itemCount = $cartItems->sum('quantity');
    $selectedShip = (int) old('shipping_address_id', $default?->id);
    $selectedBill = (int) old('billing_address_id', $default?->id);
    $method = old('payment_method', $razorpayEnabled ? 'razorpay' : 'cod');
    $selectedDelivery = (int) old('shipping_method_id', $methods->first()?->id);
    $step = 1;
    $exportText = ['export' => __('shop.checkout.export_note'), 'charge' => __('shop.checkout.export_charge'), 'noDelivery' => __('shop.checkout.no_delivery'), 'onlineOnly' => __('shop.checkout.export_online_only')];
@endphp

@section('content')
<div class="page-head"><div class="container">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('shop.cart.home') }}</a></li><li class="breadcrumb-item"><a href="{{ route('customer.cart.index') }}">{{ __('shop.nav.cart') }}</a></li><li class="breadcrumb-item active">{{ __('shop.checkout.title') }}</li></ol></nav>
    <h1>{{ __('shop.checkout.title') }}</h1>
    <ol class="steps mt-3 mb-0"><li class="done"><i class="bi bi-check-lg"></i> {{ __('shop.checkout.step_cart') }}</li><li class="now">{{ __('shop.checkout.step_details') }}</li><li>{{ __('shop.checkout.step_confirm') }}</li></ol>
</div></div>

<div class="container py-4">
    @if(\App\Support\Money::isForeign())
        <div class="alert alert-info d-flex gap-2 align-items-start" id="inrNotice"><i class="bi bi-info-circle"></i><span>{{ __('shop.cart.inr_notice') }}</span></div>
    @endif
    @foreach(['success' => 'success', 'error' => 'danger'] as $key => $cls)
        @if(session($key))<div class="alert alert-{{ $cls }}">{{ session($key) }}</div>@endif
    @endforeach
    @if($errors->any())<div class="alert alert-danger">{{ __('shop.checkout.fix_fields') }}</div>@endif

    {{-- Mobile: collapsible order summary --}}
    <div class="d-lg-none mb-3">
        <button class="summary-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#mobileSummary" aria-expanded="false">
            <span><i class="bi bi-bag"></i> {{ __('shop.checkout.show_summary') }} <i class="bi bi-chevron-down small"></i></span>
            <strong class="js-total">{{ $inr($total) }}</strong>
        </button>
        <div class="collapse" id="mobileSummary"><div class="card card-body mt-2">@include('customer.checkout._summary')</div></div>
    </div>

    <form method="POST" action="{{ route('customer.checkout.store') }}" id="checkoutForm" novalidate>
        @csrf
        <div class="row g-4">
            <div class="col-lg-7 col-xl-8">
                <section class="card card-body mb-3">
                    <h5 class="mb-3"><span class="step-no">{{ $step++ }}</span> {{ __('shop.checkout.address') }}</h5>
                    @if($addresses->isEmpty())
                        <div class="empty py-3">
                            <i class="bi bi-geo-alt"></i>
                            <p class="mb-3 mt-2">{{ __('shop.checkout.add_address_prompt') }}</p>
                            <a href="{{ route('customer.addresses.create') }}" class="btn btn-primary">{{ __('shop.checkout.add_address') }}</a>
                        </div>
                    @else
                        <div class="addr-grid" role="radiogroup" aria-label="{{ __('shop.checkout.address') }}">
                            @foreach($addresses as $a)
                                <label class="addr-card">
                                    <input type="radio" name="shipping_address_id" value="{{ $a->id }}" {{ $selectedShip === $a->id ? 'checked' : '' }} required>
                                    <span class="body">
                                        <strong>{{ $a->full_name }}</strong> <span class="badge bg-light text-dark border ms-1">{{ __('shop.checkout.type_' . $a->type) }}</span>
                                        <span class="d-block small text-muted">{{ $a->address_line_1 }}@if($a->address_line_2), {{ $a->address_line_2 }}@endif</span>
                                        <span class="d-block small text-muted">{{ $a->city }}, {{ $a->state }} {{ $a->postal_code }}</span>
                                        @if($a->phone)<span class="d-block small text-muted"><i class="bi bi-telephone"></i> {{ $a->phone }}</span>@endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('shipping_address_id')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                        {{-- International delivery: filled in by the script below for the chosen address. --}}
                        <div class="alert alert-info mt-3 mb-0" id="exportNote" hidden></div>
                        <div class="alert alert-danger mt-3 mb-0" id="exportBlock" hidden></div>
                        <a href="{{ route('customer.addresses.create') }}" class="small mt-2 d-inline-block"><i class="bi bi-plus-circle"></i> {{ __('shop.checkout.add_new_address') }}</a>

                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" id="sameBilling" checked>
                            <label class="form-check-label" for="sameBilling">{{ __('shop.checkout.same_billing') }}</label>
                        </div>
                        <div id="billingBlock" class="mt-3" hidden>
                            <label class="form-label fw-semibold" for="billing_address_id">{{ __('shop.checkout.billing') }}</label>
                            <select name="billing_address_id" id="billing_address_id" class="form-select">
                                @foreach($addresses as $a)
                                    <option value="{{ $a->id }}" {{ $selectedBill === $a->id ? 'selected' : '' }}>{{ $a->full_name }}, {{ $a->city }} {{ $a->postal_code }}</option>
                                @endforeach
                            </select>
                            @error('billing_address_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    @endif
                </section>

                @if($methods->isNotEmpty())
                    <section class="card card-body mb-3">
                        <h5 class="mb-3"><span class="step-no">{{ $step++ }}</span> {{ __('shop.checkout.delivery') }}</h5>
                        <div class="pay-grid" role="radiogroup" aria-label="{{ __('shop.checkout.delivery') }}">
                            @foreach($methods as $m)
                                @php $fee = $default ? (float) $quotes[$default->id][$m->id]['shipping'] : null; @endphp
                                <label class="pay-card js-method" data-courier="{{ $m->type === 'flat_rate' ? 1 : 0 }}">
                                    <input type="radio" name="shipping_method_id" value="{{ $m->id }}" {{ $selectedDelivery === $m->id ? 'checked' : '' }} required>
                                    <span class="body"><i class="bi {{ $m->type === 'local_pickup' ? 'bi-shop' : 'bi-truck' }}"></i><span>
                                        <strong>{{ $m->name }}</strong>
                                        <small class="d-block text-muted"><span class="js-method-fee" data-method="{{ $m->id }}">{{ $fee === null ? '' : ($fee == 0 ? __('shop.checkout.free') : $inr($fee)) }}</span>@if($m->delivery_estimate) &middot; {{ $m->delivery_estimate }}@endif</small>
                                        @if($m->description)<small class="d-block text-muted">{{ $m->description }}</small>@endif
                                    </span></span>
                                </label>
                            @endforeach
                        </div>
                        @error('shipping_method_id')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                    </section>
                @endif

                <section class="card card-body mb-3">
                    <h5 class="mb-3"><span class="step-no">{{ $step++ }}</span> {{ __('shop.checkout.payment') }}</h5>
                    <div class="pay-grid">
                        @if($razorpayEnabled)
                            <label class="pay-card">
                                <input type="radio" name="payment_method" value="razorpay" {{ $method === 'razorpay' ? 'checked' : '' }}>
                                <span class="body"><i class="bi bi-credit-card-2-front"></i><span><strong>{{ __('shop.checkout.pay_online') }}</strong><small class="d-block text-muted">{{ __('shop.checkout.pay_online_text') }}</small></span></span>
                            </label>
                        @endif
                        <label class="pay-card js-cod">
                            <input type="radio" name="payment_method" value="cod" {{ $method === 'cod' ? 'checked' : '' }}>
                            <span class="body"><i class="bi bi-cash-coin"></i><span><strong>{{ __('shop.checkout.cod') }}</strong><small class="d-block text-muted">{{ __('shop.checkout.cod_text') }}</small></span></span>
                        </label>
                    </div>
                    @error('payment_method')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                </section>

                <section class="card card-body mb-3">
                    <h5 class="mb-3"><span class="step-no">{{ $step++ }}</span> {{ __('shop.checkout.notes') }} <small class="text-muted fw-normal">{{ __('shop.checkout.optional') }}</small></h5>
                    <textarea name="notes" class="form-control" rows="2" maxlength="1000" placeholder="{{ __('shop.checkout.notes_placeholder') }}">{{ old('notes') }}</textarea>
                    @error('notes')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </section>
            </div>

            <div class="col-lg-5 col-xl-4 d-none d-lg-block">
                <div class="summary-card card card-body">
                    <h5 class="mb-3">{{ __('shop.checkout.summary') }}</h5>
                    @include('customer.checkout._summary')
                    <button type="submit" class="btn btn-accent btn-lg w-100 mt-3" data-submit><i class="bi bi-lock"></i> {{ __('shop.checkout.place_order') }}</button>
                    <p class="small text-muted text-center mt-2 mb-0">{!! __('shop.checkout.terms', ['link' => '<a href="' . route('terms') . '">' . e(__('shop.checkout.terms_link')) . '</a>']) !!}</p>
                </div>
            </div>
        </div>

        {{-- Mobile: sticky place-order bar above the bottom navigation --}}
        <div class="sticky-buy d-lg-none">
            <div class="flex-grow-1 lh-sm"><div class="small text-muted">{{ __('shop.checkout.total') }}</div><strong class="price js-total" style="font-size:1.15rem">{{ $inr($total) }}</strong></div>
            <button type="submit" class="btn btn-accent" data-submit><i class="bi bi-lock"></i> {{ __('shop.checkout.place_order') }}</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var quotes = @json($quotes);
        var money = function (n) { return '₹' + Number(n).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
        var form = document.getElementById('checkoutForm');
        var T = @json($exportText);

        // Live totals when the delivery address or delivery option changes (shipping and tax depend on both).
        function refresh() {
            var r = form.querySelector('input[name=shipping_address_id]:checked'); var byMethod = r && quotes[r.value]; if (!byMethod) return;
            // Abroad only courier options are offered; with none of them the order goes at the country's plain rate.
            var abroad = !!byMethod[0] && byMethod[0].export && form.querySelector('input[name=shipping_method_id]');
            form.querySelectorAll('.js-method').forEach(function (l) {
                var off = abroad && l.dataset.courier !== '1', i = l.querySelector('input');
                l.hidden = off; i.disabled = off; if (off) i.checked = false;
            });
            var m = form.querySelector('input[name=shipping_method_id]:checked');
            if (!m && (m = form.querySelector('input[name=shipping_method_id]:not(:disabled)'))) m.checked = true;
            var q = byMethod[m ? m.value : 0]; if (!q) return;
            var inr = document.getElementById('inrNotice'); if (inr) inr.classList.toggle('d-none', !!(q.export && q.charge));
            var fee = function (v) { return v == 0 ? @json(__('shop.checkout.free')) : money(v); };
            document.querySelectorAll('.js-shipping').forEach(function (e) { e.textContent = fee(q.shipping); });
            document.querySelectorAll('.js-tax').forEach(function (e) { e.textContent = money(q.tax); });
            document.querySelectorAll('.js-total').forEach(function (e) { e.textContent = money(q.total); });
            document.querySelectorAll('.js-method-fee').forEach(function (e) { var o = byMethod[e.dataset.method]; if (o) e.textContent = fee(o.shipping); });

            // An address outside India: no GST, online payment only, and it must be somewhere we deliver.
            var note = document.getElementById('exportNote'), block = document.getElementById('exportBlock'), cod = form.querySelector('.js-cod');
            var online = form.querySelector('input[name=payment_method][value=razorpay]');
            var stopped = q.export && (!q.deliverable || !online);
            if (note) {
                note.hidden = !q.export || stopped;
                note.textContent = q.export ? T.export + (q.charge ? ' ' + T.charge.replace(':amount', q.charge.label).replace(':total', money(q.total)) : '') : '';
                block.hidden = !stopped;
                block.textContent = !stopped ? '' : (q.blocked.length ? q.blocked.join(' ') : (!q.deliverable ? T.noDelivery.replace(':country', q.country) : T.onlineOnly));
            }
            if (cod) {
                cod.hidden = q.export;
                cod.querySelector('input').disabled = q.export;
                if (q.export && online) online.checked = true;
            }
            form.querySelectorAll('[data-submit]').forEach(function (b) { b.disabled = stopped; });
        }
        form.querySelectorAll('input[name=shipping_address_id], input[name=shipping_method_id]').forEach(function (r) { r.addEventListener('change', refresh); });

        // "Same as delivery": hide the billing select and keep it in sync.
        var same = document.getElementById('sameBilling'), block = document.getElementById('billingBlock'), bill = document.getElementById('billing_address_id');
        function sync() {
            if (!same) return; block.hidden = same.checked;
            if (same.checked) { var r = form.querySelector('input[name=shipping_address_id]:checked'); if (r) bill.value = r.value; }
        }
        if (same) { same.addEventListener('change', sync); form.querySelectorAll('input[name=shipping_address_id]').forEach(function (r) { r.addEventListener('change', sync); }); sync(); }
        refresh();

        // Prevent double submits.
        form.addEventListener('submit', function (e) {
            if (!form.reportValidity()) return;
            form.querySelectorAll('[data-submit]').forEach(function (b) { b.disabled = true; b.innerHTML = '<span class="spinner-border spinner-border-sm"></span> ' + @json(__('shop.checkout.placing')); });
        });
    })();
</script>
@endpush
