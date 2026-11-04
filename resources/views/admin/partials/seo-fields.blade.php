{{-- Search-engine title and description. Left empty, pages build them from the content. --}}
@php $m = $model ?? null; @endphp
<div class="card card-body bg-light border-0 mb-3" data-seo-box>
    <h6 class="mb-1"><i class="bi bi-search"></i> Search engine listing (SEO)</h6>
    <p class="small text-muted">What Google shows for this page. Leave empty to use the title and description above.</p>
    <div class="mb-3">
        <label for="meta_title" class="form-label">Meta title <small class="text-muted">(up to 60 characters)</small></label>
        <input type="text" id="meta_title" name="meta_title" class="form-control" maxlength="70" value="{{ old('meta_title', $m?->meta_title) }}" data-seo-title>
        @error('meta_title')<div class="text-danger">{{ $message }}</div>@enderror
    </div>
    <div class="mb-2">
        <label for="meta_description" class="form-label">Meta description <small class="text-muted">(120 to 160 characters works best)</small></label>
        <textarea id="meta_description" name="meta_description" class="form-control" rows="2" maxlength="320" data-seo-desc>{{ old('meta_description', $m?->meta_description) }}</textarea>
        <div class="small text-muted"><span data-seo-count>0</span> / 160</div>
        @error('meta_description')<div class="text-danger">{{ $message }}</div>@enderror
    </div>
</div>
<script>
document.querySelectorAll('[data-seo-box]').forEach(function (box) {
    var d = box.querySelector('[data-seo-desc]'), c = box.querySelector('[data-seo-count]');
    function upd() { c.textContent = d.value.length; c.style.color = d.value.length > 160 ? '#b42318' : ''; }
    d.addEventListener('input', upd); upd();
});
</script>
