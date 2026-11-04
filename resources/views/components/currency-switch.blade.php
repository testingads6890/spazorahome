@if(\App\Support\Money::switchable())
    <form action="{{ route('currency.switch') }}" method="POST" class="currency-switch" title="{{ __('shop.nav.currency_title') }}">
        @csrf
        <label class="visually-hidden" for="currency-{{ $id ?? 'x' }}">{{ __('shop.nav.display_currency') }}</label>
        <select id="currency-{{ $id ?? 'x' }}" name="currency" onchange="this.form.submit()" class="{{ $class ?? '' }}">
            @foreach(\App\Support\Money::options() as $c)
                <option value="{{ $c->code }}" {{ \App\Support\Money::code() === $c->code ? 'selected' : '' }}>{{ $c->code }} {{ $c->symbol }}</option>
            @endforeach
        </select>
    </form>
@endif
