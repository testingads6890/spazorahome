<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A document a seller uploaded with their application. Files sit on the private disk, never under public/. */
class VendorDocument extends Model
{
    public const COMPANY_REGISTRATION = 'company_registration';
    public const GST_CERTIFICATE = 'gst_certificate';
    public const ADDITIONAL = 'additional';
    public const AADHAAR = 'aadhaar_card';

    /** What an optional third document may be. */
    public const ADDITIONAL_KINDS = [
        'pan' => 'PAN card',
        'address_proof' => 'Address proof',
        'cancelled_cheque' => 'Cancelled cheque',
        'trade_licence' => 'Trade licence',
        'msme' => 'MSME / Udyam certificate',
        'other' => 'Other',
    ];

    public const MIMES = 'pdf,jpg,jpeg,png,webp';
    public const MAX_KB = 5120;

    protected $fillable = ['vendor_id', 'type', 'label', 'path', 'original_name', 'mime', 'size', 'status', 'note', 'verified_at', 'verified_by'];

    protected $casts = ['verified_at' => 'datetime', 'size' => 'integer'];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** Name shown to people: "Company registration certificate", "GST certificate", or the kind of extra document. */
    public function title(): string
    {
        return match ($this->type) {
            self::COMPANY_REGISTRATION => 'Company registration certificate',
            self::GST_CERTIFICATE => 'GST certificate',
            self::AADHAAR => 'Aadhaar card',
            default => $this->label ?: 'Additional document',
        };
    }

    public function sizeLabel(): string
    {
        return $this->size >= 1048576 ? number_format($this->size / 1048576, 1) . ' MB' : max(1, (int) round($this->size / 1024)) . ' KB';
    }
}
