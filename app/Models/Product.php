<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use \App\Models\Concerns\CleansRichText;

    /** Columns written in the CKEditor: cleaned on every save. */
    protected array $richText = ['description', 'short_description'];

    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'category_id', 'vendor_id', 'name', 'slug', 'description', 'short_description',
        'sku', 'price', 'sale_price', 'cost_price', 'stock_quantity',
        'low_stock_threshold', 'is_active', 'is_featured', 'export_available', 'export_weight_kg', 'country_of_origin', 'export_min_qty', 'export_blocked_countries', 'meta_title', 'meta_description',
        'vendor_status', 'vendor_rejection_reason', 'brand', 'material', 'color', 'finish',
        'size', 'style', 'product_type', 'weight', 'dimensions', 'warranty', 'care_instructions',
        'customization_info', 'shipping_info', 'tags', 'is_new_arrival', 'view_count', 'purchase_count',
        'user_role', 'tax_rate', 'hsn_code',
        'video_url', 'min_order_qty', 'specifications', 'bulk_pricing',
        // Extra details from the vendor data sheet (see App\Support\ProductExtraFields)
        'vendor_sku', 'manufacturer', 'model_no', 'collection_series', 'material_grade', 'secondary_material', 'colour_code',
        'finish_type', 'finish_code', 'features', 'mrp', 'unit', 'unit_details', 'lead_time_days', 'is_customizable', 'made_to_order',
        'assembly_required', 'assembly_time', 'assembly_difficulty', 'assembly_instructions', 'installation_included', 'installation_charges',
        'warranty_months', 'warranty_exclusions', 'return_eligible', 'return_policy_days', 'return_policy', 'replacement_policy', 'damage_policy',
        'packaging_quantity', 'packaging_weight', 'packed_length_cm', 'packed_width_cm', 'packed_height_cm', 'dispatch_from',
        'shipping_restrictions', 'is_fragile', 'is_bulky', 'catalogue_url', 'cad_url', 'image_folder_url', 'vendor_notes', 'category_attributes',
        'whats_included', 'whats_not_included', 'faq_1_question', 'faq_1_answer', 'faq_2_question', 'faq_2_answer', 'faq_3_question', 'faq_3_answer',
        'payment_terms', 'price_valid_until',
        'max_order_qty', 'running_ft', 'secondary_material_grade', 'service_type', 'country_id', 'state_id', 'district_id', 'city_id', 'pincode_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'export_available' => 'boolean',
        'export_weight_kg' => 'decimal:2',
        'export_min_qty' => 'integer',
        'export_blocked_countries' => 'array',
        'is_new_arrival' => 'boolean',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tags' => 'array',
        'view_count' => 'integer',
        'purchase_count' => 'integer',
        'min_order_qty' => 'integer',
        'bulk_pricing' => 'array',
        'category_attributes' => 'array',
        'mrp' => 'decimal:2',
        'price_valid_until' => 'date',
        'lead_time_days' => 'integer',
        'running_ft' => 'decimal:2',
        'unit_details' => 'array',
        'is_customizable' => 'boolean',
        'made_to_order' => 'boolean',
        'assembly_required' => 'boolean',
        'installation_included' => 'boolean',
        'return_eligible' => 'boolean',
        'is_fragile' => 'boolean',
        'is_bulky' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Every product gets a readable web address (/product/oak-dining-table), unique across the shop.
        static::saving(function (Product $product) {
            if (blank($product->attributes['slug'] ?? null) && filled($product->name)) {
                $base = \Illuminate\Support\Str::slug($product->name) ?: 'product';
                $slug = $base;
                for ($i = 2; static::withTrashed()->where('slug', $slug)->whereKeyNot($product->getKey())->exists(); $i++) {
                    $slug = "{$base}-{$i}";
                }
                $product->slug = $slug;
            }
        });
    }

    /** An old product saved without a slug still gets a working address: /product/item-123. */
    public function getSlugAttribute($value)
    {
        return $value ?: ($this->exists ? 'item-' . $this->getKey() : $value);
    }

    /** Storefront addresses use the slug; "item-123" finds a product that has none. Everything else keeps using the id. */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field !== 'slug') {
            return parent::resolveRouteBinding($value, $field);
        }

        return $this->newQuery()->where('slug', $value)->first()
            ?? (preg_match('/^item-(\d+)$/', (string) $value, $m) ? $this->newQuery()->whereKey((int) $m[1])->where(fn ($q) => $q->whereNull('slug')->orWhere('slug', ''))->first() : null);
    }

    /**
     * Bulk (quantity) discounts as [minimum quantity => percent off], lowest quantity first.
     * A product's own tiers win; null means "use the store default", an empty list means "no bulk discount".
     */
    public function bulkTiers(): array
    {
        if (! config('shop.bulk_pricing.enabled', true)) {
            return [];
        }
        $raw = $this->bulk_pricing === null ? config('shop.bulk_pricing.default_tiers', []) : $this->bulk_pricing;
        $tiers = [];
        foreach ((array) $raw as $key => $row) {
            // Accepts [2 => 5] from config and [{"min":2,"off":5}] from the database.
            [$min, $off] = is_array($row) ? [(int) ($row['min'] ?? 0), (float) ($row['off'] ?? 0)] : [(int) $key, (float) $row];
            if ($min >= 2 && $off > 0 && $off < 90) {
                $tiers[$min] = round($off, 2);
            }
        }
        ksort($tiers);

        return $tiers;
    }

    /** Percent off that applies when this many units are bought in one line. */
    public function bulkDiscountFor(int $quantity): float
    {
        $off = 0.0;
        foreach ($this->bulkTiers() as $min => $pct) {
            if ($quantity >= $min) {
                $off = $pct;
            }
        }

        return $off;
    }

    /** The price of one unit when $quantity units are bought together. */
    public function bulkUnitPrice(float $unitPrice, int $quantity): float
    {
        $off = $this->bulkDiscountFor($quantity);

        return $off > 0 ? round($unitPrice * (100 - $off) / 100, 2) : round($unitPrice, 2);
    }

    /** GST percent for this product: its own rate, else the store default. */
    public function taxRate(): float
    {
        return (float) ($this->tax_rate ?? config('shop.tax_rate'));
    }

    /** May this product be sent to that country? (Ticked for export, and the country is not on its own blocked list.) */
    public function exportableTo(?string $country): bool
    {
        $blocked = array_map(fn ($c) => mb_strtolower(trim((string) $c)), $this->export_blocked_countries ?? []);

        return $this->export_available && ! in_array(mb_strtolower(trim((string) $country)), $blocked, true);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /** Pictures in display order; the first one (sort_order 0) is the thumbnail. */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Pictures to show on the storefront: the uploaded ones, else free stock photos for the product's
     * category (config/product_photos.php). Each entry has: url (card), large (product page), thumb, alt.
     */
    public function photos(): \Illuminate\Support\Collection
    {
        if ($this->images->isNotEmpty()) {
            return $this->images->map(function (ProductImage $image) {
                $url = asset('storage/' . $image->path);

                return ['url' => $url, 'large' => $url, 'thumb' => $url, 'alt' => $image->alt_text ?: $this->name, 'stock' => false];
            })->values();
        }

        return collect(\App\Support\StockPhotos::forProduct((int) $this->id, $this->category_id))->map(fn (string $id) => [
            'url' => \App\Support\StockPhotos::url($id, 640, 800),
            'large' => \App\Support\StockPhotos::url($id, 1200, 1200),
            'thumb' => \App\Support\StockPhotos::url($id, 240, 240),
            'alt' => $this->name,
            'stock' => true,
        ]);
    }

    /** The first picture, or null when there is none. */
    public function photo(): ?array
    {
        return $this->photos()->first();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }
}
