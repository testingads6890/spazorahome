<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Support\Str;

/**
 * Free stock photos that stand in for products and categories with no picture of their own.
 * The lists live in config/product_photos.php.
 */
class StockPhotos
{
    /** @var array<int, string>|null category id => name, read once per request */
    private static ?array $names = null;

    public static function enabled(): bool
    {
        return (bool) config('product_photos.enabled', true);
    }

    /** Photo ids for a category, by its name. Unknown categories get the default list. */
    public static function pool(?string $categoryName): array
    {
        $lists = (array) config('product_photos.categories', []);
        $key = Str::slug((string) $categoryName);

        return $lists[$key] ?? $lists[config('product_photos.default')] ?? [];
    }

    /**
     * Up to $count different photos for a product. The pick depends only on the product id,
     * so a product keeps the same pictures on every page and after every reload.
     *
     * @return array<int, string> photo ids
     */
    public static function forProduct(int $productId, ?int $categoryId, int $count = 3): array
    {
        if (! self::enabled()) {
            return [];
        }

        $pool = self::pool(self::categoryName($categoryId));
        $n = count($pool);
        if ($n === 0) {
            return [];
        }

        // Spread the picks across the list so the second and third picture are not the neighbour's first one.
        $picked = [];
        foreach ([0, intdiv($n, 2), intdiv($n, 3) ?: 1] as $offset) {
            $id = $pool[($productId + $offset) % $n];
            if (! in_array($id, $picked, true)) {
                $picked[] = $id;
            }
        }

        return array_slice($picked, 0, $count);
    }

    /** One photo for a category tile, or null when stand-ins are off. */
    public static function categoryUrl(?string $categoryName, int $width = 600, int $height = 750): ?string
    {
        $pool = self::enabled() ? self::pool($categoryName) : [];

        return $pool ? self::url($pool[0], $width, $height) : null;
    }

    /** Address of a photo cropped to the given size by the image server. */
    public static function url(string $id, int $width, int $height): string
    {
        return config('product_photos.base') . $id . '?auto=format&fit=crop&w=' . $width . '&h=' . $height . '&q=70';
    }

    private static function categoryName(?int $categoryId): ?string
    {
        self::$names ??= Category::query()->pluck('name', 'id')->all();

        return self::$names[$categoryId] ?? null;
    }
}
