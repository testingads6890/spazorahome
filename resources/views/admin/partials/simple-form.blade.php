@php $editing = isset($model) && $model; @endphp
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>{{ $title }}</h2>
        <a href="{{ $back }}" class="btn btn-secondary">Back to List</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ $action }}" method="POST">
                @csrf
                @if($editing) @method('PUT') @endif

                @foreach($fields as $f)
                    @php $val = old($f['name'], $editing ? $model->{$f['name']} : ($f['default'] ?? null)); @endphp
                    <div class="mb-3">
                        @if(($f['type'] ?? 'text') === 'checkbox')
                            <input type="hidden" name="{{ $f['name'] }}" value="0">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="{{ $f['name'] }}" name="{{ $f['name'] }}" value="1" {{ $val ? 'checked' : '' }}>
                                <label class="form-check-label" for="{{ $f['name'] }}">{{ $f['label'] }}</label>
                            </div>
                        @else
                            <label for="{{ $f['name'] }}" class="form-label">{{ $f['label'] }}</label>
                            @if(($f['type'] ?? 'text') === 'textarea')
                                <textarea id="{{ $f['name'] }}" name="{{ $f['name'] }}" rows="8" class="form-control">{{ $val }}</textarea>
                            @elseif(($f['type'] ?? 'text') === 'select')
                                <select id="{{ $f['name'] }}" name="{{ $f['name'] }}" class="form-select">
                                    @foreach($f['options'] as $k => $label)
                                        <option value="{{ $k }}" {{ (string) $val === (string) $k ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="{{ $f['type'] ?? 'text' }}" id="{{ $f['name'] }}" name="{{ $f['name'] }}" value="{{ $val }}" class="form-control" @if(isset($f['step'])) step="{{ $f['step'] }}" @endif {{ ($f['required'] ?? false) ? 'required' : '' }}>
                            @endif
                        @endif
                        @error($f['name'])<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                @endforeach

                <button type="submit" class="btn btn-primary">Save</button>
            </form>
        </div>
    </div>
</div>
