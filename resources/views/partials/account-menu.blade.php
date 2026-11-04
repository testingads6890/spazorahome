{{-- Customer account menu: bordered list with icons, the current page highlighted, Logout at the end. --}}
<nav class="acc-menu {{ $class ?? '' }}" aria-label="{{ __('My account') }}">
    @foreach(\App\Support\AccountMenu::items() as $item)
        @php $on = request()->routeIs(...(array) $item['pattern']); @endphp
        <a href="{{ route($item['route']) }}" class="{{ $on ? 'active' : '' }}" @if($on) aria-current="page" @endif>
            <i class="bi {{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span>
        </a>
    @endforeach
    <form action="{{ route('logout') }}" method="POST">@csrf
        <button type="submit"><i class="bi bi-box-arrow-right"></i><span>{{ __('Logout') }}</span></button>
    </form>
</nav>
