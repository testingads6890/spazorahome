{{-- Thumbnail + gallery fields, shared by the seller and admin product forms. Expects $product (may be new). The form needs enctype="multipart/form-data". --}}
@php
    $existing = $product->exists ? $product->images()->get() : collect();
    $primary = (int) old('primary_image', $existing->first()?->id);
    $removing = array_map('intval', (array) old('remove_images', []));
    $maxPics = \App\Support\ProductImages::MAX;
@endphp
<div class="pimg">
    @if($existing->isNotEmpty())
        <p class="form-label mb-1">Current pictures</p>
        <p class="small text-muted mb-2">Choose which one is the <strong>thumbnail</strong> (shown on product cards and first on the product page), or tick <em>Remove</em>.</p>
        <div class="pimg-grid mb-3">
            @foreach($existing as $image)
                <div class="pimg-item {{ $image->id === $primary ? 'is-thumb' : '' }}">
                    <img src="{{ asset('storage/' . $image->path) }}" alt="" loading="lazy">
                    <span class="pimg-badge">Thumbnail</span>
                    <label class="small d-block mt-1"><input type="radio" name="primary_image" value="{{ $image->id }}" @checked($image->id === $primary) data-primary> Thumbnail</label>
                    <label class="small d-block text-danger"><input type="checkbox" name="remove_images[]" value="{{ $image->id }}" @checked(in_array($image->id, $removing, true))> Remove</label>
                </div>
            @endforeach
        </div>
    @endif

    <div class="row g-3">
        <div class="col-md-5">
            <label for="thumbnail" class="form-label fw-semibold">{{ $existing->isNotEmpty() ? 'New thumbnail' : 'Thumbnail (main picture)' }}</label>
            <input type="file" id="thumbnail" name="thumbnail" class="form-control @error('thumbnail') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp" data-preview="thumbPreview">
            @error('thumbnail')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Square or 4:5 works best, at least 800 px wide. {{ $existing->isNotEmpty() ? 'Uploading one replaces the current thumbnail choice.' : '' }}</div>
            <div class="pimg-grid mt-2" id="thumbPreview"></div>
        </div>
        <div class="col-md-7">
            <label for="images" class="form-label fw-semibold">Gallery pictures</label>
            <input type="file" id="images" name="images[]" class="form-control @error('images') is-invalid @enderror @error('images.*') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp" multiple data-preview="galleryPreview">
            @error('images')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @error('images.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Select several at once (Ctrl / Shift). Up to {{ $maxPics }} pictures in total, JPG, PNG or WEBP, 4 MB each.</div>
            <div class="pimg-grid mt-2" id="galleryPreview"></div>
        </div>
    </div>
</div>
<style>
    .pimg-grid{display:flex;flex-wrap:wrap;gap:10px}
    .pimg-item{position:relative;width:112px;border:1px solid #dee2e6;border-radius:8px;padding:5px;text-align:left;background:#fff}
    .pimg-item img{width:100%;height:100px;object-fit:cover;border-radius:6px;display:block}
    .pimg-item.is-thumb{border:2px solid var(--bs-primary,var(--brand))}
    .pimg-badge{display:none;position:absolute;top:8px;left:8px;background:var(--bs-primary,var(--brand));color:#fff;font-size:.65rem;font-weight:700;padding:1px 6px;border-radius:4px}
    .pimg-item.is-thumb .pimg-badge{display:block}
    .pimg-new{width:90px;height:90px;object-fit:cover;border-radius:6px;border:1px solid #dee2e6}
</style>
<script>
    (function () {
        // Preview chosen files before upload.
        document.querySelectorAll('.pimg [data-preview]').forEach(function (input) {
            input.addEventListener('change', function () {
                var box = document.getElementById(input.dataset.preview); box.innerHTML = '';
                Array.prototype.slice.call(input.files, 0, {{ $maxPics }}).forEach(function (f) {
                    if (!/^image\//.test(f.type)) return;
                    var img = document.createElement('img'); img.className = 'pimg-new'; img.alt = f.name; img.src = URL.createObjectURL(f);
                    box.appendChild(img);
                });
            });
        });
        // Highlight the picture chosen as thumbnail.
        document.querySelectorAll('.pimg [data-primary]').forEach(function (r) {
            r.addEventListener('change', function () {
                document.querySelectorAll('.pimg .pimg-item').forEach(function (i) { i.classList.remove('is-thumb'); });
                r.closest('.pimg-item').classList.add('is-thumb');
            });
        });
    })();
</script>
