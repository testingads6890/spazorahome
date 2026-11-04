<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The extra product details a vendor can fill in (from the vendor data sheet), in one place:
 * the vendor form draws them from sections() and the controller validates them with rules(), so the two cannot drift apart.
 *
 * Types: text, number, textarea, url, date, bool (a tick box), select (needs 'options').
 * All of them are optional. On an edit, a field the form did not send keeps its saved value.
 */
class ProductExtraFields
{
    /** Built-in units; more can be added by admins and sellers and are kept in the product_units table. */
    public const UNITS = ['running_ft' => 'Running ft', 'rm' => 'Running metre', 'sqft' => 'Sq ft', 'sqm' => 'Sq m', 'roll' => 'Roll', 'box' => 'Box', 'pcs' => 'Piece', 'set' => 'Set', 'pair' => 'Pair'];


    public const DIFFICULTY = ['Easy' => 'Easy', 'Medium' => 'Medium', 'Hard' => 'Hard (professional help advised)'];

    /** @return array<string, array{title: string, hint: ?string, fields: array<int, array<string, mixed>>}> */
    public static function sections(): array
    {
        $f = fn (string $name, string $label, string $type = 'text', string $col = 'col-md-4', array $more = []) => ['name' => $name, 'label' => $label, 'type' => $type, 'col' => $col] + $more;

        return [
            'identity' => ['title' => 'Identity, material and finish', 'hint' => 'Be specific: species, grade, thickness and composition, not just "wood" or "fabric".', 'fields' => [
                $f('vendor_sku', 'Your own SKU / code', 'text', 'col-md-4', ['max' => 100]),
                $f('manufacturer', 'Manufacturer', 'text', 'col-md-4', ['max' => 150]),
                $f('model_no', 'Model / style number', 'text', 'col-md-4', ['max' => 100]),
                $f('collection_series', 'Collection / series', 'text', 'col-md-4', ['max' => 150]),
                $f('material_grade', 'Material grade / quality', 'text', 'col-md-4', ['max' => 150, 'placeholder' => 'Teak, grade A, 18 mm']),
                $f('secondary_material', 'Secondary material', 'text', 'col-md-4', ['max' => 150]),
                $f('colour_code', 'Colour code', 'text', 'col-6 col-md-3', ['max' => 50]),
                $f('finish_type', 'Finish type', 'text', 'col-6 col-md-3', ['max' => 100, 'placeholder' => 'PU, melamine, powder coat']),
                $f('finish_code', 'Finish code', 'text', 'col-6 col-md-3', ['max' => 50]),
                $f('country_of_origin', 'Country of origin', 'text', 'col-6 col-md-3', ['max' => 100]),
                $f('features', 'Key features', 'textarea', 'col-12', ['max' => 3000, 'rows' => 3, 'hint' => 'One per line.']),
            ]],
            'ordering' => ['title' => 'Unit, lead time and customisation', 'hint' => null, 'fields' => [
                $f('unit', 'Sold per', 'select', 'col-6 col-md-3', ['options' => \App\Models\ProductUnit::options()]),
                $f('lead_time_days', 'Lead time (days)', 'number', 'col-6 col-md-3', ['min' => 0, 'max' => 365, 'step' => 1, 'hint' => 'Days from order to dispatch.']),
                $f('made_to_order', 'Made to order', 'bool', 'col-6 col-md-3', ['hint' => 'Built after the order is placed.']),
                $f('is_customizable', 'Can be customised', 'bool', 'col-6 col-md-3', ['hint' => 'Describe what can change under Customisation above.']),
            ]],
            'assembly' => ['title' => 'Assembly and installation', 'hint' => null, 'fields' => [
                $f('assembly_required', 'Assembly required', 'bool', 'col-6 col-md-3'),
                $f('assembly_time', 'Assembly time', 'text', 'col-6 col-md-3', ['max' => 100, 'placeholder' => '30 minutes']),
                $f('assembly_difficulty', 'Assembly difficulty', 'select', 'col-6 col-md-3', ['options' => self::DIFFICULTY, 'blank' => 'Not set']),
                $f('installation_included', 'Installation included', 'bool', 'col-6 col-md-3'),
                $f('installation_charges', 'Installation charges (₹)', 'number', 'col-6 col-md-3', ['min' => 0, 'max' => 9999999, 'step' => 0.01, 'hint' => 'If it is not included.']),
                $f('assembly_instructions', 'Assembly / installation notes', 'textarea', 'col-md-9', ['max' => 3000, 'rows' => 2]),
            ]],
            'warranty' => ['title' => 'Warranty and returns', 'hint' => 'What the buyer can expect. Say plainly what is not covered.', 'fields' => [
                $f('warranty_months', 'Warranty (months)', 'number', 'col-6 col-md-3', ['min' => 0, 'max' => 600, 'step' => 1, 'hint' => 'What it covers goes in Warranty above.']),
                $f('warranty_exclusions', 'Warranty exclusions', 'textarea', 'col-md-9', ['max' => 2000, 'rows' => 2, 'hint' => 'Misuse, wear, natural variation, outdoor use...']),
                $f('return_eligible', 'Returns accepted', 'bool', 'col-6 col-md-3', ['default' => true]),
                $f('return_policy_days', 'Return window (days)', 'number', 'col-6 col-md-3', ['min' => 0, 'max' => 365, 'step' => 1]),
                $f('return_policy', 'Return terms', 'textarea', 'col-md-6', ['max' => 3000, 'rows' => 2, 'hint' => 'Custom-product restrictions, who pays return freight, refund process.']),
                $f('replacement_policy', 'Replacement terms', 'textarea', 'col-md-6', ['max' => 3000, 'rows' => 2]),
                $f('damage_policy', 'Damage in transit', 'textarea', 'col-12', ['max' => 3000, 'rows' => 2, 'hint' => 'How and by when the buyer reports it, and what you do.']),
            ]],
            'packing' => ['title' => 'Packing and dispatch', 'hint' => 'The packed (boxed) size, not the product size.', 'fields' => [
                $f('packed_length_cm', 'Packed length (cm)', 'number', 'col-6 col-md-3', ['min' => 0, 'max' => 100000, 'step' => 0.01]),
                $f('packed_width_cm', 'Packed width (cm)', 'number', 'col-6 col-md-3', ['min' => 0, 'max' => 100000, 'step' => 0.01]),
                $f('packed_height_cm', 'Packed height (cm)', 'number', 'col-6 col-md-3', ['min' => 0, 'max' => 100000, 'step' => 0.01]),
                $f('packaging_weight', 'Packed weight (kg)', 'number', 'col-6 col-md-3', ['min' => 0, 'max' => 1000000, 'step' => 0.01]),
                $f('packaging_quantity', 'Number of packages', 'number', 'col-6 col-md-3', ['min' => 1, 'max' => 1000, 'step' => 1, 'integer' => true]),
                $f('dispatch_from', 'Dispatch from', 'text', 'col-6 col-md-3', ['max' => 150, 'placeholder' => 'Jaipur, Rajasthan']),
                $f('is_fragile', 'Fragile', 'bool', 'col-6 col-md-3'),
                $f('is_bulky', 'Oversize / bulky', 'bool', 'col-6 col-md-3'),
                $f('shipping_restrictions', 'Shipping restrictions', 'textarea', 'col-12', ['max' => 2000, 'rows' => 2, 'hint' => 'Places or carriers you cannot ship to.']),
            ]],
            'content' => ['title' => "What's included and FAQs", 'hint' => 'Shown on the product page. Answer what buyers ask most.', 'fields' => [
                $f('whats_included', "What's included", 'textarea', 'col-md-6', ['max' => 2000, 'rows' => 3, 'hint' => 'One per line.']),
                $f('whats_not_included', "What's not included", 'textarea', 'col-md-6', ['max' => 2000, 'rows' => 3, 'hint' => 'One per line (mattress, bulbs, installation...).']),
                $f('faq_1_question', 'Question 1', 'text', 'col-md-4', ['max' => 255]),
                $f('faq_1_answer', 'Answer 1', 'textarea', 'col-md-8', ['max' => 1500, 'rows' => 2]),
                $f('faq_2_question', 'Question 2', 'text', 'col-md-4', ['max' => 255]),
                $f('faq_2_answer', 'Answer 2', 'textarea', 'col-md-8', ['max' => 1500, 'rows' => 2]),
                $f('faq_3_question', 'Question 3', 'text', 'col-md-4', ['max' => 255]),
                $f('faq_3_answer', 'Answer 3', 'textarea', 'col-md-8', ['max' => 1500, 'rows' => 2]),
            ]],
            'commercial' => ['title' => 'Payment terms and price validity', 'hint' => 'For the marketplace team only. Customers do not see this.', 'fields' => [
                $f('payment_terms', 'Your payment terms', 'text', 'col-md-8', ['max' => 255, 'placeholder' => '50% advance, balance on delivery']),
                $f('price_valid_until', 'Price valid until', 'date', 'col-md-4'),
            ]],
            'links' => ['title' => 'Links and notes', 'hint' => null, 'fields' => [
                $f('catalogue_url', 'Catalogue link', 'url', 'col-md-4', ['max' => 500]),
                $f('cad_url', 'CAD / 3D file link', 'url', 'col-md-4', ['max' => 500]),
                $f('image_folder_url', 'Image folder link', 'url', 'col-md-4', ['max' => 500]),
                $f('vendor_notes', 'Note for the marketplace team', 'textarea', 'col-12', ['max' => 3000, 'rows' => 2, 'hint' => 'Private. Customers do not see this.']),
            ]],
        ];
    }

    /** Fields only the marketplace team sees (never shown to customers). */
    public const PRIVATE = ['vendor_sku', 'vendor_notes', 'payment_terms', 'price_valid_until', 'cad_url', 'image_folder_url', 'catalogue_url', 'dispatch_from'];

    /** One field's value as text for display; null when empty. */
    private static function display(array $field, $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return match ($field['type']) {
            'bool' => $value ? 'Yes' : 'No',
            'date' => $value instanceof \DateTimeInterface ? $value->format('d M Y') : (string) $value,
            'select' => ($field['options'][$value] ?? $value),
            default => (string) (is_numeric($value) && str_contains((string) $value, '.') ? rtrim(rtrim((string) $value, '0'), '.') : $value),
        };
    }

    /**
     * What was filled in, as [section title => [label => text]]. $forCustomer hides the private fields and
     * leaves out "No" ticks that say nothing (fragile: No...). The per-category details come last, under their own title.
     */
    public static function present($product, bool $forCustomer = false): array
    {
        $out = [];
        foreach (self::sections() as $section) {
            $rows = [];
            foreach ($section['fields'] as $field) {
                if ($forCustomer && in_array($field['name'], self::PRIVATE, true)) {
                    continue;
                }
                $value = $product->{$field['name']} ?? null;
                if ($forCustomer && $field['type'] === 'bool' && ! $value && ! in_array($field['name'], ['return_eligible', 'installation_included'], true)) {
                    continue;
                }
                $text = self::display($field, $value);
                if ($text !== null) {
                    $rows[$field['label']] = $text;
                }
            }
            if ($rows) {
                $out[$section['title']] = $rows;
            }
        }
        if ($product->mrp !== null && (float) $product->mrp > 0) {
            $out['Price'] = ['Suggested MRP' => '₹' . number_format((float) $product->mrp, 2)];
        }

        $attrs = (array) ($product->category_attributes ?? []);
        $config = self::groups()[$attrs['group'] ?? ''] ?? null;
        if ($config && ! empty($attrs['values'])) {
            $rows = [];
            foreach ($config['fields'] as $field) {
                if (isset($attrs['values'][$field['key']])) {
                    $rows[$field['label']] = $attrs['values'][$field['key']];
                }
            }
            if ($rows) {
                $out[$config['label'] . ' details'] = $rows;
            }
        }

        return $out;
    }

    /** Every simple field (not the per-category ones), by name. */
    public static function fields(): array
    {
        $all = [];
        foreach (self::sections() as $section) {
            foreach ($section['fields'] as $field) {
                $all[$field['name']] = $field;
            }
        }

        return $all;
    }

    /** The per-category groups from config/product_category_attributes.php. */
    public static function groups(): array
    {
        return (array) config('product_category_attributes.groups', []);
    }

    /** Group => fields, with only what the browser needs to draw the form. */
    public static function groupsForForm(): array
    {
        $out = [];
        foreach (self::groups() as $key => $group) {
            $out[$key] = ['label' => $group['label'], 'fields' => array_map(fn ($f) => ['key' => $f['key'], 'label' => $f['label'], 'type' => $f['type']], $group['fields'])];
        }

        return $out;
    }

    /** The extra field rules, plus the MRP rule that sits with the price. */
    public static function rules(): array
    {
        $rules = [
            'unit_details' => ['nullable', 'array'],
            'unit_details.*' => ['nullable', 'max:100'],
        ];
        foreach (self::fields() as $name => $field) {
            $rules[$name] = match ($field['type']) {
                'bool' => ['sometimes', 'boolean'],
                'number' => ['nullable', empty($field['integer']) && ($field['step'] ?? 1) == 1 ? 'integer' : 'numeric', 'min:' . $field['min'], 'max:' . $field['max']],
                'url' => ['nullable', 'url', 'max:' . $field['max']],
                'date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
                'select' => ['nullable', Rule::in(array_keys($field['options']))],
                default => ['nullable', 'string', 'max:' . $field['max']],
            };
        }
        $rules['price_valid_until'] = ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'];
        $rules['packaging_quantity'] = ['nullable', 'integer', 'min:1', 'max:1000'];
        $rules['mrp'] = ['nullable', 'numeric', 'min:0', 'max:99999999', 'gte:price'];

        // Per-category details: one chosen group, free text or Yes / No for each detail.
        $rules['detail_group'] = ['nullable', Rule::in(array_keys(self::groups()))];
        $rules['attr'] = ['nullable', 'array'];
        foreach (self::groups() as $groupKey => $group) {
            foreach ($group['fields'] as $field) {
                $rules["attr.{$groupKey}.{$field['key']}"] = $field['type'] === 'bool' ? ['nullable', Rule::in(['Yes', 'No'])] : ['nullable', 'string', 'max:500'];
            }
        }

        return $rules;
    }

    public static function messages(): array
    {
        return ['mrp.gte' => 'The suggested MRP cannot be lower than the price.', 'price_valid_until.after_or_equal' => 'The price validity date cannot be in the past.'];
    }

    /**
     * What to store in category_attributes: the chosen group and the details filled in for it
     * (null when none were chosen or filled). Details of other groups that were sent are ignored.
     */
    public static function categoryAttributes(Request $request): ?array
    {
        $group = $request->input('detail_group');
        $config = self::groups()[$group] ?? null;
        if (! $config) {
            return null;
        }
        $sent = (array) data_get($request->input('attr'), $group, []);
        $values = [];
        foreach ($config['fields'] as $field) {
            $value = trim((string) ($sent[$field['key']] ?? ''));
            if ($value !== '') {
                $values[$field['key']] = $value;
            }
        }

        return $values ? ['group' => $group, 'values' => $values] : null;
    }
}
