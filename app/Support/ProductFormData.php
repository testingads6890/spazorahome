<?php

namespace App\Support;

use App\Models\Product;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Rules and clean-up shared by the admin and seller product forms, so the two can never disagree.
 */
class ProductFormData
{
    /** Validation rules for the new product fields (both forms). */
    public static function rules(bool $isAdmin, Request $request): array
    {
        $choice = fn (array $options) => ['nullable', 'string', Rule::in(array_merge(array_values($options), [ProductChoices::OTHER]))];

        return array_merge(
            ProductChoices::locationRules(),
            [
                'color' => $choice(ProductChoices::COLOURS),
                'color_other' => ['nullable', 'required_if:color,' . ProductChoices::OTHER, 'string', 'max:150'],
                'material' => $choice(ProductChoices::MATERIALS),
                'material_other' => ['nullable', 'required_if:material,' . ProductChoices::OTHER, 'string', 'max:150'],
                'material_grade' => ['nullable', 'string', 'max:150'],
                'secondary_material' => $choice(ProductChoices::MATERIALS),
                'secondary_material_other' => ['nullable', 'required_if:secondary_material,' . ProductChoices::OTHER, 'string', 'max:150'],
                'secondary_material_grade' => ['nullable', 'string', 'max:150'],
                'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:365'],
                'service_type' => ['nullable', Rule::in(array_keys(ProductChoices::SERVICE_TYPES))],
                'max_order_qty' => ['nullable', 'integer', 'min:1', 'max:100000', self::notBelowMinimum($request)],
                // Several PIN codes for the dispatch city; the first one is also kept on the product.
                'pincode_ids' => ['nullable', 'array'],
                'pincode_ids.*' => ['integer', 'exists:pincodes,id'],
                'pincode_ids_sent' => ['nullable'],
            ],
            $isAdmin ? [] : [],
            // The category typed in place of a list choice (admin: live; seller: awaits approval).
            ['custom_category' => ['nullable', 'string', 'max:120']],
        );
    }

    public static function messages(): array
    {
        return ProductChoices::locationMessages() + [
            'color_other.required_if' => __('Type the colour.'),
            'material_other.required_if' => __('Type the primary material.'),
            'secondary_material_other.required_if' => __('Type the secondary material.'),
            'lead_time_days.max' => __('Dispatch time can be up to 365 days.'),
        ];
    }

    private static function notBelowMinimum(Request $request): Closure
    {
        return function (string $attribute, $value, Closure $fail) use ($request) {
            $min = (int) $request->input('min_order_qty', 1) ?: 1;
            if ($value !== null && $value !== '' && (int) $value < $min) {
                $fail(__('Maximum order quantity cannot be less than the minimum order quantity (:min).', ['min' => $min]));
            }
        };
    }

    /**
     * Turns the validated form data into what is stored: dropdown + "Other" become one value,
     * the service dropdown sets the two older yes/no flags, and a typed category becomes a category.
     */
    public static function prepare(array $data, bool $categoryApproved): array
    {
        self::assertLocationChain($data);

        // Only the fields the form actually sent are changed; anything missing keeps its saved value.
        foreach (['color' => 'color_other', 'material' => 'material_other', 'secondary_material' => 'secondary_material_other'] as $field => $otherField) {
            if (array_key_exists($field, $data) || array_key_exists($otherField, $data)) {
                $data[$field] = ProductChoices::resolve($data[$field] ?? null, $data[$otherField] ?? null);
            }
            unset($data[$otherField]);
        }

        // unit_details is only written once its column exists (the migration), so saving never breaks before it runs.
        if ((array_key_exists('unit_details', $data) || array_key_exists('unit', $data)) && \Illuminate\Support\Facades\Schema::hasColumn('products', 'unit_details')) {
            // Every unit takes the same numbers: length, width, height, and the measuring unit typed in (ft, m...).
            $allowed = ['length', 'width', 'height', 'measure'];
            $sent = (array) ($data['unit_details'] ?? []);
            $clean = [];
            foreach ($allowed as $name) {
                $value = $sent[$name] ?? null;
                if ($value === null || trim((string) $value) === '' || ($name !== 'measure' && ! is_numeric($value))) {
                    continue;
                }
                $clean[$name] = $name === 'measure' ? mb_substr(trim((string) $value), 0, 20) : 0 + $value;
            }
            $data['unit_details'] = $clean ?: null;
        }

        if (array_key_exists('pincode_ids', $data)) {
            $ids = array_values(array_filter(array_map('intval', (array) $data['pincode_ids'])));
            $data['pincode_id'] = $ids[0] ?? null;
            unset($data['pincode_ids']);
        }
        unset($data['pincode_ids_sent']);

        if (array_key_exists('service_type', $data)) {
            $data = array_merge($data, ProductChoices::serviceFlags($data['service_type']));
        }

        if (array_key_exists('custom_category', $data)) {
            $data['category_id'] = CategoryInput::resolve(
                filled($data['category_id'] ?? null) ? (int) $data['category_id'] : null,
                $data['custom_category'],
                $categoryApproved,
            );
            unset($data['custom_category']);
        }

        foreach (['max_order_qty', 'lead_time_days'] as $numeric) {
            if (array_key_exists($numeric, $data) && $data[$numeric] === '') {
                $data[$numeric] = null;
            }
        }

        return $data;
    }

    /**
     * The dropdowns only offer children of the level above, but the posted ids could still be mixed up.
     * Each chosen level must belong to the one chosen above it.
     */
    public static function assertLocationChain(array $data): void
    {
        $chain = [
            ['state_id', \App\Models\State::class, 'country_id', 'country_id'],
            ['district_id', \App\Models\District::class, 'state_id', 'state_id'],
            ['city_id', \App\Models\City::class, 'district_id', 'district_id'],
            ['pincode_id', \App\Models\Pincode::class, 'city_id', 'city_id'],
        ];
        foreach ($chain as [$field, $model, $parentColumn, $parentField]) {
            if (! isset($data[$field], $data[$parentField])) {
                continue;
            }
            $row = $model::find($data[$field]);
            if (! $row || (int) $row->{$parentColumn} !== (int) $data[$parentField]) {
                throw \Illuminate\Validation\ValidationException::withMessages([$field => [__('This does not belong to the place chosen above it. Choose again from the list.')]]);
            }
        }
    }
}
