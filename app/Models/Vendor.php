<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use \App\Models\Concerns\CleansRichText;

    /** Columns written in the CKEditor: cleaned on every save. */
    protected array $richText = ['description'];

    use SoftDeletes;

    protected $fillable = [
        'user_id', 'business_name', 'slug', 'email', 'phone', 'whatsapp', 'other_phone', 'seller_type',
        'address', 'city', 'state', 'country', 'postal_code',
        'tax_number', 'bank_details', 'commission_rate',
        'status', 'is_active', 'approved_at',
        'contact_name', 'business_type', 'website', 'description', 'product_categories',
        'registration_number', 'gst_registered', 'terms_accepted_at', 'rejection_reason',
        'logo', 'cover', 'social_links',
    ];

    /** What the seller is: the three kinds of professional or maker who may apply. */
    public const SELLER_TYPES = [
        'architect' => 'Architect',
        'interior_designer' => 'Interior designer',
        'manufacturer' => 'Manufacturer',
    ];

    /** Kinds of business a seller can apply as. */
    public const BUSINESS_TYPES = [
        'manufacturer' => 'Manufacturer',
        'workshop' => 'Workshop / artisan',
        'brand' => 'Brand owner',
        'wholesaler' => 'Wholesaler / distributor',
        'retailer' => 'Retailer',
        'importer' => 'Importer',
        'exporter' => 'Exporter',
        'other' => 'Other',
    ];

    protected $casts = [
        'bank_details' => 'array',
        'commission_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
        'product_categories' => 'array',
        'gst_registered' => 'boolean',
        'terms_accepted_at' => 'datetime',
        'social_links' => 'array',
    ];

    /** Approved, active sellers: the only ones shown publicly. */
    public function scopeLive($query)
    {
        return $query->where('status', 'approved')->where('is_active', true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function vendorProducts(): HasMany
    {
        return $this->hasMany(VendorProduct::class);
    }

    public function vendorUsers(): HasMany
    {
        return $this->hasMany(VendorUser::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(VendorOrder::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VendorDocument::class);
    }

    /** Commission taken from a seller's sales when no rate is set for them. Admin can change it (Marketplace page). */
    public const DEFAULT_COMMISSION_RATE = 10;

    public static function defaultCommissionRate(): float
    {
        $value = \App\Models\Setting::get('default_commission_rate');

        return is_numeric($value) ? (float) $value : (float) self::DEFAULT_COMMISSION_RATE;
    }

    /** The rate that applies to this seller: their own rate when admin set one, otherwise the default. */
    public function effectiveCommissionRate(): float
    {
        return $this->commission_rate !== null ? (float) $this->commission_rate : self::defaultCommissionRate();
    }

    /** Documents every application must carry: the registration certificate, and the GST certificate when GST-registered. */
    public function requiredDocumentTypes(): array
    {
        return array_values(array_filter([
            VendorDocument::COMPANY_REGISTRATION,
            VendorDocument::AADHAAR,
            $this->gst_registered ? VendorDocument::GST_CERTIFICATE : null,
        ]));
    }

    /** Required documents that are missing or not verified yet (empty when the seller is ready to approve). */
    public function unverifiedRequiredDocuments(): array
    {
        $verified = $this->documents->where('status', 'verified')->pluck('type')->all();

        return array_values(array_diff($this->requiredDocumentTypes(), $verified));
    }

    /** Sellers who applied through the full form (older accounts have no documents to check). */
    public function appliedWithDocuments(): bool
    {
        return $this->terms_accepted_at !== null;
    }

    public function businessTypeLabel(): ?string
    {
        return self::BUSINESS_TYPES[$this->business_type] ?? null;
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(VendorPayout::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(VendorWithdrawal::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(VendorCoupon::class);
    }

    /** Store logo and cover banner as web addresses, or null when none is uploaded. */
    public function logoUrl(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }

    public function coverUrl(): ?string
    {
        return $this->cover ? asset('storage/' . $this->cover) : null;
    }

    /** Social links that are filled in: network => url. */
    public const SOCIAL = ['instagram' => ['Instagram', 'bi-instagram'], 'facebook' => ['Facebook', 'bi-facebook'], 'youtube' => ['YouTube', 'bi-youtube'], 'pinterest' => ['Pinterest', 'bi-pinterest'], 'linkedin' => ['LinkedIn', 'bi-linkedin'], 'whatsapp' => ['WhatsApp', 'bi-whatsapp']];

    public function socialLinks(): array
    {
        return array_filter(array_intersect_key((array) ($this->social_links ?? []), self::SOCIAL));
    }
}
