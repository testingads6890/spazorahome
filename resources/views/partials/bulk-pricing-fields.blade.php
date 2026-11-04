{{-- Bulk pricing fields for the seller and admin product forms. Expects $product (may be new). --}}
@php
    $stored = $product->bulk_pricing;
    $bulkMode = old('bulk_mode', $stored === null ? 'default' : (count((array) $stored) ? 'custom' : 'off'));
    $rows = collect((array) $stored)->values();
    $defaults = collect(config('shop.bulk_pricing.default_tiers', []))->map(fn ($off, $min) => $min . '+ units: ' . $off . '% off')->implode(', ');
@endphp
<div class="col-12">
    <label class="form-label fw-semibold d-block">Bulk pricing</label>
    <div class="d-flex flex-wrap gap-3 mb-2">
        @foreach(['default' => 'Store default' . ($defaults ? " ($defaults)" : ''), 'custom' => 'Custom tiers', 'off' => 'No bulk discount'] as $k => $label)
            <div class="form-check">
                <input class="form-check-input" type="radio" name="bulk_mode" id="bulk_mode_{{ $k }}" value="{{ $k }}" @checked($bulkMode === $k) data-bulk-mode>
                <label class="form-check-label" for="bulk_mode_{{ $k }}">{{ $label }}</label>
            </div>
        @endforeach
    </div>
    <div id="bulkRows" @if($bulkMode !== 'custom') hidden @endif>
        <div class="row g-2">
            @for($i = 0; $i < \App\Support\BulkPricing::ROWS; $i++)
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Buy</span>
                        <input type="number" min="2" name="bulk_min[]" class="form-control" placeholder="{{ [2, 5, 10][$i] }}" value="{{ old("bulk_min.$i", $rows[$i]['min'] ?? '') }}" aria-label="Minimum quantity, tier {{ $i + 1 }}">
                        <span class="input-group-text">+ get</span>
                        <input type="number" min="0.5" max="80" step="0.5" name="bulk_off[]" class="form-control" placeholder="{{ [5, 10, 15][$i] }}" value="{{ old("bulk_off.$i", $rows[$i]['off'] ?? '') }}" aria-label="Percent off, tier {{ $i + 1 }}">
                        <span class="input-group-text">% off</span>
                    </div>
                </div>
            @endfor
        </div>
        <div class="form-text">Each unit gets cheaper when a customer buys at least that many. Leave a row empty to skip it.</div>
    </div>
    @error('bulk_min')<div class="text-danger small">{{ $message }}</div>@enderror
    @error('bulk_off')<div class="text-danger small">{{ $message }}</div>@enderror
    @error('bulk_min.*')<div class="text-danger small">{{ $message }}</div>@enderror
    @error('bulk_off.*')<div class="text-danger small">{{ $message }}</div>@enderror
</div>
<script>
    document.querySelectorAll('[data-bulk-mode]').forEach(function (r) {
        r.addEventListener('change', function () { document.getElementById('bulkRows').hidden = this.value !== 'custom'; });
    });
</script>
