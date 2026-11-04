<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Pictures for the seller and admin product forms: one thumbnail (the main picture, shown on cards
 * and first on the product page) plus a gallery. The thumbnail is simply the picture with sort_order 0.
 */
class ProductImages
{
    public const MAX = 8;

    public static function rules(): array
    {
        return [
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'images' => 'nullable|array|max:' . self::MAX,
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'integer',
            'primary_image' => 'nullable|integer',
        ];
    }

    public static function messages(): array
    {
        return [
            'thumbnail.image' => 'The thumbnail must be a JPG, PNG or WEBP picture.',
            'thumbnail.max' => 'The thumbnail can be up to 4 MB.',
            'images.max' => 'A product can have up to ' . self::MAX . ' pictures.',
            'images.*.max' => 'Each picture can be up to 4 MB.',
            'images.*.image' => 'Pictures must be JPG, PNG or WEBP files.',
        ];
    }

    /** How many more pictures fit, after the removals ticked on this form. */
    public static function check(Request $request, ?Product $product = null, bool $required = false): void
    {
        $existing = $product ? $product->images()->reorder()->whereNotIn('id', array_map('intval', (array) $request->input('remove_images', [])))->count() : 0;
        $adding = count((array) $request->file('images', [])) + ($request->hasFile('thumbnail') ? 1 : 0);
        if ($existing + $adding > self::MAX) {
            throw ValidationException::withMessages(['images' => 'A product can have up to ' . self::MAX . ' pictures. Remove some first.']);
        }
        if ($required && $existing + $adding === 0) {
            throw ValidationException::withMessages(['thumbnail' => 'Add at least a thumbnail picture.']);
        }
    }

    /** Removes ticked pictures, stores new ones and puts the chosen thumbnail first. */
    public static function save(Request $request, Product $product): void
    {
        $remove = array_map('intval', (array) $request->input('remove_images', []));
        if ($remove) {
            foreach ($product->images()->whereIn('id', $remove)->get() as $image) {
                Storage::disk('public')->delete($image->path);
                $image->delete();
            }
        }

        $order = (int) $product->images()->reorder()->max('sort_order');
        $primaryId = (int) $request->input('primary_image') ?: null;

        if ($request->hasFile('thumbnail')) {
            $thumb = $product->images()->create([
                'path' => $request->file('thumbnail')->store('products', 'public'),
                'alt_text' => $product->name,
                'sort_order' => 0,
            ]);
            $primaryId = $thumb->id;
        }
        foreach ((array) $request->file('images', []) as $file) {
            $product->images()->create([
                'path' => $file->store('products', 'public'),
                'alt_text' => $product->name,
                'sort_order' => ++$order,
            ]);
        }

        // Renumber: the thumbnail is 0, the rest keep their order after it.
        $images = $product->images()->get();
        if ($primaryId && ! $images->contains('id', $primaryId)) {
            $primaryId = null;
        }
        $primaryId ??= $images->first()?->id;
        $n = 1;
        foreach ($images as $image) {
            $want = $image->id === $primaryId ? 0 : $n++;
            if ((int) $image->sort_order !== $want) {
                $image->update(['sort_order' => $want]);
            }
        }
    }
}
