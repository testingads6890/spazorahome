{{-- One labelled input of the seller application. $x comes from the $field() helper in vendor-register.blade.php. --}}
<div class="{{ $x['col'] }}">
    <label for="{{ $x['name'] }}" class="form-label fw-semibold">{{ $x['label'] }}@if($x['optional']) <span class="vform-opt">{{ __('Optional') }}</span>@endif</label>
    <input type="{{ $x['type'] }}" id="{{ $x['name'] }}" name="{{ $x['name'] }}" value="{{ $x['value'] }}" class="form-control @error($x['name']) is-invalid @enderror" autocomplete="{{ $x['auto'] }}" {!! $x['attrs'] !!} @unless($x['optional']) required @endunless @if($x['hint']) aria-describedby="{{ $x['name'] }}Hint" @endif>
    @error($x['name'])<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if($x['hint'])<div class="form-text" id="{{ $x['name'] }}Hint">{{ $x['hint'] }}</div>@endif
</div>
