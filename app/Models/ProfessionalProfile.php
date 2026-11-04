<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessionalProfile extends Model
{
    protected $fillable = [
        'user_id', 'company_name', 'designation', 'description', 'license_number',
        'website', 'specialization', 'experience_years', 'portfolio_images', 'certifications', 'is_verified',
    ];

    protected $casts = [
        'portfolio_images' => 'array',
        'certifications' => 'array',
        'is_verified' => 'boolean',
        'experience_years' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
