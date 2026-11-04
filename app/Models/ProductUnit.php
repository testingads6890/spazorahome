<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/** A unit a product is sold per, with the numbers the seller enters for it (length, area, pieces...). */
class ProductUnit extends Model
{
    /** Numbers a unit can ask for: field name => [label, type]. */
    public const FIELD_CHOICES = [
        'length' => ['Length', 'number'],
        'height' => ['Height', 'number'],
        'width' => ['Width', 'number'],
        'area' => ['Area', 'number'],
        'count' => ['Number of pieces', 'number'],
        'pieces' => ['Pieces per pack', 'number'],
        'coverage' => ['Coverage (text)', 'text'],
    ];

    protected $fillable = ['key', 'label', 'fields', 'is_active', 'created_by'];

    protected $casts = ['fields' => 'array', 'is_active' => 'boolean'];

    /** Active units as key => label, in the order they were created. */
    public static function options(): array
    {
        if (! Schema::hasTable('product_units')) {
            return ['pcs' => 'Piece'];
        }

        return self::where('is_active', true)->orderBy('id')->pluck('label', 'key')->all();
    }

    /** The numbers a unit asks for: [['name' => ..., 'label' => ..., 'type' => ...], ...]. */
    public static function fieldsFor(?string $key): array
    {
        if (! $key || ! Schema::hasTable('product_units')) {
            return [];
        }

        return self::where('key', $key)->first()?->fields ?? [];
    }
}
