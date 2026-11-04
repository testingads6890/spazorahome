<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use \App\Models\Concerns\CleansRichText;

    /** Columns written in the CKEditor: cleaned on every save. */
    protected array $richText = ['description'];

    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = ['name', 'slug', 'description', 'parent_id', 'product_type_id', 'image', 'status', 'meta_title', 'meta_description'];

    public function productType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }

    /** Category picture: an uploaded file on the public disk, or a full web address. Null when there is none. */
    public function imageUrl(): ?string
    {
        $img = trim((string) $this->image);
        if ($img === '') {
            return null;
        }

        return preg_match('#^(https?:)?//#i', $img) ? $img : asset('storage/' . ltrim($img, '/'));
    }

    protected $casts = [
        'status' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Every category gets a web address of its own: "Kitchen & Dining" => kitchen-dining.
        static::saving(function (Category $category) {
            // A category can never sit inside itself or one of its own subcategories (that would loop forever).
            if ($category->parent_id) {
                $seen = [];
                for ($id = (int) $category->parent_id; $id; $id = (int) static::withTrashed()->whereKey($id)->value('parent_id')) {
                    if (($category->exists && $id === (int) $category->getKey()) || isset($seen[$id])) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['parent_id' => 'A category cannot be placed inside itself or one of its own subcategories.']);
                    }
                    $seen[$id] = true;
                }
            }
            if (blank($category->slug) && filled($category->name)) {
                $base = \Illuminate\Support\Str::slug($category->name) ?: 'category';
                $slug = $base;
                for ($i = 2; static::withTrashed()->where('slug', $slug)->whereKeyNot($category->getKey())->exists(); $i++) {
                    $slug = "{$base}-{$i}";
                }
                $category->slug = $slug;
            }
        });
    }

    /** The word used for this category in a shop address (/shop/living-room). */
    public function urlSlug(): string
    {
        return ($this->attributes['slug'] ?? null) ?: (\Illuminate\Support\Str::slug((string) $this->name) ?: (string) $this->getKey());
    }

    /** Address of this category's listing page, with optional filters (sort, q, page...). */
    public function shopUrl(array $query = []): string
    {
        return route('search', ['category' => $this->urlSlug()] + $query);
    }

    /** Find an active category from the word in the address: its slug, its name in slug form, or (old links) its number. */
    public static function fromUrl(string $value): ?self
    {
        $active = static::where('status', true);

        return (clone $active)->where('slug', $value)->first()
            ?? (ctype_digit($value) ? (clone $active)->find((int) $value) : null)
            ?? $active->get()->first(fn (Category $c) => $c->urlSlug() === $value);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
