<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    public const LOCATIONS = ['home' => 'Home page (top)'];

    public const MAX_SLIDES = 5;

    protected $fillable = ['name', 'location', 'slides', 'is_active', 'sort_order'];

    protected $casts = [
        'slides' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** The slider shown in a location: the first active one that has at least one slide. */
    public static function forLocation(string $location): ?self
    {
        return static::where('is_active', true)->where('location', $location)->orderBy('sort_order')->orderBy('id')->get()
            ->first(fn (self $s) => ! empty($s->slides));
    }
}
