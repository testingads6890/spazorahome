<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Support\Str;

/**
 * A category typed into the product form instead of picked from the list. An existing name is reused
 * (case-insensitive); a new one is created. Admins create it live; sellers create it awaiting approval.
 */
class CategoryInput
{
    public static function resolve(?int $categoryId, ?string $typed, bool $approved): ?int
    {
        $name = trim((string) $typed);
        if ($name === '') {
            return $categoryId;
        }

        $existing = Category::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if ($existing) {
            return $existing->id;
        }

        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        for ($i = 2; Category::withTrashed()->where('slug', $slug)->exists(); $i++) {
            $slug = $base . '-' . $i;
        }

        return Category::create([
            'name' => mb_substr($name, 0, 120),
            'slug' => $slug,
            'status' => $approved,
        ])->id;
    }
}
