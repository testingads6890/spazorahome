@if(\App\Support\Locales::switchable())
    <form action="{{ route('language.switch') }}" method="POST" class="currency-switch" title="{{ __('shop.nav.language') }}">
        @csrf
        <label class="visually-hidden" for="lang-{{ $id ?? 'x' }}">{{ __('shop.nav.language') }}</label>
        <select id="lang-{{ $id ?? 'x' }}" name="language" onchange="this.form.submit()" class="{{ $class ?? '' }}">
            @foreach(\App\Support\Locales::available() as $l)
                <option value="{{ $l->code }}" {{ \App\Support\Locales::current() === $l->code ? 'selected' : '' }}>{{ $l->name }}</option>
            @endforeach
        </select>
    </form>
@endif
