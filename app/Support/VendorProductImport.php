<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Bulk upload of products from an Excel (.xlsx) or .csv sheet. One row = one product. Every row is checked with the
 * same rules as the add-product form; the good rows become products waiting for admin approval, the others are
 * reported row by row so the seller can fix just those and upload them again.
 */
class VendorProductImport
{
    public const MAX_ROWS = 500;

    /** The main columns: key => [heading, help, example]. */
    public static function baseColumns(): array
    {
        return [
            'category' => ['Category', 'Required. Exactly as in the Categories list sheet.', 'Beds'],
            'name' => ['Product name', 'Required.', 'Teak King Size Bed'],
            'sku' => ['SKU', 'Required. Must be unique.', 'BED-TEAK-001'],
            'hsn_code' => ['HSN code', 'Required. 4, 6 or 8 digits.', '94035000'],
            'tax_rate' => ['GST %', 'Required. One of the allowed slabs.', '18'],
            'price' => ['Price (Rs)', 'Required.', '45000'],
            'sale_price' => ['Sale price (Rs)', 'Optional. Not higher than the price.', '42000'],
            'stock_quantity' => ['Stock', 'Required. Whole number.', '10'],
            'description' => ['Description', 'Required.', 'Solid teak king size bed with storage.'],
            'short_description' => ['Short description', 'Optional.', ''],
            'brand' => ['Brand', 'Optional.', ''],
            'product_type' => ['Product type', 'Optional.', ''],
            'material' => ['Material', 'Optional.', 'Teak wood'],
            'color' => ['Colour', 'Optional.', 'Walnut'],
            'finish' => ['Finish', 'Optional.', 'PU polish'],
            'size' => ['Size', 'Optional.', 'King'],
            'style' => ['Style', 'Optional.', ''],
            'dimensions' => ['Dimensions', 'Optional.', '210 x 190 x 95 cm'],
            'weight' => ['Weight', 'Optional.', '85 kg'],
            'warranty' => ['Warranty', 'Optional. What it covers.', '3 years on frame'],
            'care_instructions' => ['Care instructions', 'Optional.', ''],
            'shipping_info' => ['Shipping info', 'Optional.', ''],
            'customization_info' => ['Customisation', 'Optional.', ''],
            'tags' => ['Tags', 'Optional. Separate with commas.', 'teak, bed, storage'],
            'min_order_qty' => ['Minimum order qty', 'Optional. Default 1.', '1'],
            'low_stock_threshold' => ['Low stock alert at', 'Optional. Default 5.', '3'],
            'video_url' => ['Video link', 'Optional.', ''],
        ];
    }

    /** Every column of the sheet in order: key => [heading, help, example]. */
    public static function columns(): array
    {
        $cols = self::baseColumns();
        foreach (ProductExtraFields::fields() as $name => $field) {
            $help = match ($field['type']) {
                'bool' => 'Yes or No.',
                'select' => 'One of: ' . implode(', ', array_keys($field['options'])) . '.',
                'date' => 'Date as YYYY-MM-DD.',
                'number' => 'Number.',
                'url' => 'Web link (https://...).',
                default => 'Optional.',
            };
            $cols[$name] = [$field['label'], $help, ''];
        }
        $cols['mrp'] = ['Suggested MRP (Rs)', 'Optional. Not lower than the price.', ''];
        $cols['detail_group'] = ['Kind of product', 'Optional. One name from the Kinds of product list sheet.', 'BED'];
        $cols['details'] = ['Specific details', 'Optional. Detail=value; Detail=value. Names from the Kinds of product list sheet. Yes/No details take Yes or No.', 'Wood Species=Teak; Headboard Included=Yes'];

        return $cols;
    }

    private static function norm(string $v): string
    {
        return preg_replace('/[^a-z0-9]/', '', mb_strtolower($v));
    }

    /** The template workbook: sheet 1 is the one to fill, the others explain it. */
    public static function writeTemplate(string $path): void
    {
        $cols = self::columns();
        $sheet1 = [array_column($cols, 0)];
        $guide = [['Column', 'What to enter', 'Example']];
        foreach ($cols as $c) {
            $guide[] = [$c[0], $c[1], $c[2]];
        }
        $cats = [['Category (use this exact name)', 'Parent']];
        $all = Category::where('status', true)->orderBy('name')->get(['id', 'name', 'parent_id']);
        foreach ($all as $c) {
            $cats[] = [$c->name, optional($all->firstWhere('id', $c->parent_id))->name ?? ''];
        }
        $kinds = [['Kind of product', 'Name', 'Detail names you can use (Yes/No ones are marked)']];
        foreach (ProductExtraFields::groups() as $key => $g) {
            $kinds[] = [$key, $g['label'], implode(' | ', array_map(fn ($f) => $f['label'] . ($f['type'] === 'bool' ? ' (Yes/No)' : ''), $g['fields']))];
        }
        $notes = [['Read me'],
            ['One row = one product. Fill the first sheet (Products) and upload it in your seller panel: Products > Import from Excel.'],
            ['Every row is checked. Good rows are added and wait for the marketplace team to approve them; rows with a mistake are listed with the reason, so you can fix just those and upload them again.'],
            ['Rows whose SKU is already in your products are skipped, so uploading the same file twice is safe.'],
            ['Up to ' . self::MAX_ROWS . ' rows per file. Photos are added afterwards from Products > Edit.'],
            ['Allowed GST %: ' . implode(', ', (array) config('shop.gst_slabs'))],
            ['Keep the heading row as it is. Columns you do not need can stay empty.']];
        $w = ['Products' => array_map(fn ($c) => max(14, min(40, mb_strlen($c[0]) + 4)), array_values($cols)), 'Column guide' => [26, 70, 40], 'Categories' => [36, 24], 'Kinds of product' => [26, 28, 120], 'Read me' => [120]];
        SimpleXlsx::write($path, ['Products' => $sheet1, 'Column guide' => $guide, 'Categories' => $cats, 'Kinds of product' => $kinds, 'Read me' => $notes], $w);
    }

    /**
     * @param  array<int, array<int, string>>  $rows  rows from SimpleXlsx::read (first one is the heading row)
     * @return array{created: int, skipped: array, errors: array, total: int, fatal: ?string}
     */
    public static function run(Vendor $vendor, array $rows): array
    {
        $result = ['created' => 0, 'skipped' => [], 'errors' => [], 'total' => 0, 'fatal' => null];

        // Heading row: find each column by its heading or its key.
        $rows = array_filter($rows, fn ($r) => implode('', $r) !== '');   // keeps each row's original position
        if (! $rows) {
            $result['fatal'] = 'The file is empty.';

            return $result;
        }
        $lookup = [];
        foreach (self::columns() as $key => $c) {
            $lookup[self::norm($c[0])] = $key;
            $lookup[self::norm($key)] = $key;
        }
        $map = [];
        $headingKey = array_key_first($rows);
        foreach ($rows[$headingKey] as $i => $heading) {
            if (isset($lookup[self::norm((string) $heading)])) {
                $map[$i] = $lookup[self::norm((string) $heading)];
            }
        }
        $missing = array_diff(['category', 'name', 'sku', 'hsn_code', 'tax_rate', 'price', 'stock_quantity', 'description'], $map);
        if ($missing) {
            $result['fatal'] = 'These columns are missing from the heading row: ' . implode(', ', array_map(fn ($k) => self::columns()[$k][0], $missing)) . '. Please download the template and use its first row.';

            return $result;
        }
        $body = array_slice($rows, 1, null, true);
        if (count($body) > self::MAX_ROWS) {
            $result['fatal'] = 'A file can have up to ' . self::MAX_ROWS . ' product rows. Please split it.';

            return $result;
        }

        $categories = Category::where('status', true)->get(['id', 'name', 'slug']);
        $fields = ProductExtraFields::fields();
        $seen = [];
        foreach ($body as $n => $cells) {
            $line = $n + 1;   // the row number Excel shows
            $data = [];
            foreach ($map as $i => $key) {
                $data[$key] = trim((string) ($cells[$i] ?? ''));
            }
            if (implode('', $data) === '') {
                continue;   // blank row
            }
            $result['total']++;
            $name = $data['name'] ?: '(no name)';
            $errors = [];
            $badCategory = false;

            // Category by name or slug
            $cat = $categories->first(fn ($c) => self::norm($c->name) === self::norm($data['category']) || $c->slug === Str::slug($data['category']));
            if (! $cat) {
                $errors[] = $data['category'] === '' ? 'Category is required.' : 'Category "' . $data['category'] . '" was not found. Use a name from the Categories sheet.';
                $badCategory = true;
            }

            // Text cells to the shapes the form uses
            foreach ($fields as $fname => $f) {
                if (! array_key_exists($fname, $data)) {
                    continue;
                }
                if ($f['type'] === 'bool') {
                    $b = self::bool($data[$fname]);
                    if ($b === null) {
                        if ($data[$fname] !== '') {
                            $errors[] = $f['label'] . ': write Yes or No.';
                        }
                        unset($data[$fname]);
                    } else {
                        $data[$fname] = $b;
                    }
                } elseif ($f['type'] === 'select' && $data[$fname] !== '') {
                    $data[$fname] = self::option($f['options'], $data[$fname]);
                }
            }
            $data['tax_rate'] = self::plain($data['tax_rate']);

            // SKU: already ours = skip; someone else's or repeated in the file = error
            $sku = $data['sku'];
            if ($sku !== '') {
                $mine = Product::withTrashed()->where('vendor_id', $vendor->id)->where('sku', $sku)->exists();
                if ($mine) {
                    $result['skipped'][] = ['row' => $line, 'name' => $name, 'reason' => 'SKU ' . $sku . ' is already in your products.'];
                    continue;
                }
                if (isset($seen[mb_strtolower($sku)])) {
                    $errors[] = 'SKU ' . $sku . ' is repeated (also in row ' . $seen[mb_strtolower($sku)] . ').';
                }
                $seen[mb_strtolower($sku)] ??= $line;
            }

            $validator = Validator::make(
                collect($data)->except(['category', 'details', 'detail_group'])->map(fn ($v) => $v === '' ? null : $v)->all() + ['category_id' => $cat?->id],
                VendorProductRules::base() + ProductExtraFields::rules(),
                VendorProductRules::MESSAGES + ProductExtraFields::messages() + ['tax_rate.in' => 'GST % must be one of: ' . implode(', ', (array) config('shop.gst_slabs')) . '.']
            );
            foreach ($validator->errors()->messages() as $field => $messages) {
                if ($field === 'category_id' && ! empty($badCategory)) {
                    continue;   // already explained above
                }
                array_push($errors, ...$messages);
            }

            // Specific details for one kind of product
            [$category, $detailErrors] = self::details($data['detail_group'] ?? '', $data['details'] ?? '');
            array_push($errors, ...$detailErrors);

            if ($errors) {
                $result['errors'][] = ['row' => $line, 'name' => $name, 'messages' => array_values(array_unique($errors))];
                continue;
            }

            try {
                self::create($vendor, $cat, $data, $category);
                $result['created']++;
            } catch (\Throwable $e) {
                report($e);
                $result['errors'][] = ['row' => $line, 'name' => $name, 'messages' => ['This row could not be saved. Please check it and try again.']];
            }
        }

        return $result;
    }

    private static function create(Vendor $vendor, Category $cat, array $data, ?array $categoryAttributes): void
    {
        $values = collect($data)->except(['category', 'details', 'detail_group'])->map(fn ($v) => is_string($v) && $v === '' ? null : $v)->all();
        $values['category_id'] = $cat->id;
        if (($values['country_of_origin'] ?? null) === null) {
            unset($values['country_of_origin']);   // keep the database default
        }
        $values['tags'] = collect(explode(',', (string) ($values['tags'] ?? '')))->map(fn ($t) => trim($t))->filter()
            ->unique(fn ($t) => mb_strtolower($t))->take(15)->values()->all() ?: null;
        $values['min_order_qty'] = (int) ($values['min_order_qty'] ?? 1) ?: 1;
        $values['low_stock_threshold'] = (int) ($values['low_stock_threshold'] ?? 5);
        $values['category_attributes'] = $categoryAttributes;

        DB::transaction(function () use ($vendor, $values) {
            $product = Product::create($values + [
                'vendor_id' => $vendor->id,
                'vendor_status' => 'pending',
                'slug' => self::uniqueSlug((string) $values['name']),
                'is_active' => true,
                'is_featured' => false,
            ]);
            VendorProduct::create(['vendor_id' => $vendor->id, 'product_id' => $product->id, 'status' => 'pending']);
        });
    }

    /** @return array{0: ?array, 1: array<int, string>} category_attributes and the problems found */
    private static function details(string $group, string $text): array
    {
        if ($group === '' && $text === '') {
            return [null, []];
        }
        $groups = ProductExtraFields::groups();
        $key = collect($groups)->keys()->first(fn ($k) => self::norm($k) === self::norm($group) || self::norm($groups[$k]['label']) === self::norm($group));
        if (! $key) {
            return [null, [$group === '' ? 'Specific details need a "Kind of product".' : 'Kind of product "' . $group . '" was not found. Use a name from the Kinds of product sheet.']];
        }
        $fieldMap = [];
        foreach ($groups[$key]['fields'] as $f) {
            $fieldMap[self::norm($f['label'])] = $f;
            $fieldMap[self::norm($f['key'])] = $f;
        }
        $values = [];
        $errors = [];
        foreach (preg_split('/;|\r\n|\r|\n/', $text) as $pair) {
            $pair = trim($pair);
            if ($pair === '') {
                continue;
            }
            $parts = preg_split('/\s*[=:]\s*/', $pair, 2);
            $f = $fieldMap[self::norm($parts[0])] ?? null;
            $v = trim((string) ($parts[1] ?? ''));
            if (! $f) {
                $errors[] = '"' . $parts[0] . '" is not a detail of ' . $groups[$key]['label'] . '.';
            } elseif ($v === '') {
                continue;
            } elseif ($f['type'] === 'bool') {
                $b = self::bool($v);
                if ($b === null) {
                    $errors[] = $f['label'] . ': write Yes or No.';
                } else {
                    $values[$f['key']] = $b ? 'Yes' : 'No';
                }
            } elseif (mb_strlen($v) > 500) {
                $errors[] = $f['label'] . ' is longer than 500 characters.';
            } else {
                $values[$f['key']] = $v;
            }
        }

        return [$errors || ! $values ? null : ['group' => $key, 'values' => $values], $errors];
    }

    private static function bool(string $v): ?bool
    {
        return match (mb_strtolower(trim($v))) {
            'yes', 'y', 'true', '1' => true,
            'no', 'n', 'false', '0' => false,
            default => null,
        };
    }

    /** Accept either the stored key or the label shown on the form. */
    private static function option(array $options, string $v): string
    {
        foreach ($options as $key => $label) {
            if (self::norm($v) === self::norm((string) $key) || self::norm($v) === self::norm((string) $label)) {
                return (string) $key;
            }
        }

        return $v;
    }

    private static function plain(string $v): string
    {
        $v = rtrim(trim($v), '% ');

        return is_numeric($v) ? (string) (0 + $v) : $v;
    }

    private static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        for ($i = 2; Product::withTrashed()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
