@extends('layouts.account')

@section('title', __('Return items - SpazoraHome'))
@section('heading', __('Return items from :number', ['number' => $order->order_number]))
@section('crumb', __('Returns'))

@section('account')
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('customer.returns.store', $order) }}" class="card card-body">
        @csrf
        <h2 class="h6">{{ __('1. Which items?') }}</h2>
        <div class="table-responsive mb-3">
            <table class="table align-middle mb-0">
                <thead><tr><th>{{ __('Item') }}</th><th class="text-end">{{ __('Can return') }}</th><th style="width:130px">{{ __('Return') }}</th></tr></thead>
                <tbody>
                @foreach($order->items as $item)
                    @php $max = (int) ($left[$item->id] ?? 0); @endphp
                    <tr class="{{ $max === 0 ? 'text-muted' : '' }}">
                        <td>{{ $item->product_name }}<div class="small text-muted">{{ __('Ordered :count', ['count' => $item->quantity]) }}</div></td>
                        <td class="text-end">{{ $max }}</td>
                        <td><input type="number" name="quantities[{{ $item->id }}]" min="0" max="{{ $max }}" value="{{ old('quantities.' . $item->id, $max === 1 ? 1 : 0) }}" class="form-control form-control-sm" aria-label="{{ __('Quantity to return of :item', ['item' => $item->product_name]) }}" @disabled($max === 0)></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <h2 class="h6">{{ __('2. Why?') }}</h2>
        <div class="row g-2 mb-3">
            @foreach($reasons as $key => $label)
                <div class="col-sm-6 col-lg-4">
                    <input type="radio" class="btn-check" name="reason" id="reason_{{ $key }}" value="{{ $key }}" @checked(old('reason') === $key) required>
                    <label class="btn btn-outline-secondary w-100 text-start" for="reason_{{ $key }}">{{ __($label) }}</label>
                </div>
            @endforeach
        </div>
        <label for="customer_note" class="form-label fw-semibold">{{ __('3. Anything we should know?') }} <span class="text-muted fw-normal">{{ __('(optional)') }}</span></label>
        <textarea id="customer_note" name="customer_note" rows="3" class="form-control mb-3" maxlength="2000" placeholder="{{ __('For damage, describe it. You can also share photos with our support team.') }}">{{ old('customer_note') }}</textarea>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-primary"><i class="bi bi-send"></i> {{ __('Send return request') }}</button>
            <a href="{{ route('customer.orders.show', $order) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
