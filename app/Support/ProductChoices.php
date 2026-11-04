<?php

namespace App\Support;

/**
 * Dropdown choices for the product form. Each list ends with "Other", which lets the seller type their own value.
 */
class ProductChoices
{
    public const OTHER = '__other';

    public const COLOURS = ['White', 'Off-white', 'Ivory', 'Beige', 'Cream', 'Grey', 'Black', 'Brown', 'Tan', 'Walnut', 'Teak', 'Oak', 'Maroon', 'Red', 'Orange', 'Yellow', 'Green', 'Olive', 'Blue', 'Navy', 'Teal', 'Purple', 'Pink', 'Gold', 'Silver', 'Multicolour'];

    public const MATERIALS = ['Solid wood', 'Engineered wood', 'Plywood', 'MDF', 'Particle board', 'Metal', 'Stainless steel', 'Iron', 'Aluminium', 'Glass', 'Marble', 'Granite', 'Stone', 'Rattan', 'Cane', 'Bamboo', 'Fabric', 'Leather', 'Faux leather', 'Plastic', 'Acrylic', 'Brass', 'Ceramic', 'Jute', 'Velvet'];

    public const SERVICE_TYPES = [
        'none' => 'No assembly or installation',
        'assembly' => 'Assembly',
        'installation' => 'Installation',
        'assembly_installation' => 'Assembly and installation',
    ];

    /**
     * Turns "dropdown value + typed other value" into the single value we store.
     * Returns null when nothing usable was entered.
     */
    public static function resolve(?string $choice, ?string $other): ?string
    {
        $value = $choice === self::OTHER ? trim((string) $other) : trim((string) $choice);

        return $value === '' ? null : mb_substr($value, 0, 150);
    }

    /** Keep the value on the form when re-showing it: an unknown value appears under "Other". */
    public static function split(?string $stored, array $options): array
    {
        if ($stored === null || $stored === '') {
            return ['', ''];
        }
        foreach ($options as $option) {
            if (mb_strtolower($option) === mb_strtolower($stored)) {
                return [$option, ''];
            }
        }

        return [self::OTHER, $stored];
    }

    /** Service type from the dropdown, and the two older yes/no flags kept in step with it. */
    public static function serviceFlags(?string $type): array
    {
        return match ($type) {
            'assembly' => ['service_type' => 'assembly', 'assembly_required' => true, 'installation_included' => false],
            'installation' => ['service_type' => 'installation', 'assembly_required' => false, 'installation_included' => true],
            'assembly_installation' => ['service_type' => 'assembly_installation', 'assembly_required' => true, 'installation_included' => true],
            default => ['service_type' => 'none', 'assembly_required' => false, 'installation_included' => false],
        };
    }

    /** Mandatory dispatch location: every level must be chosen and must exist and be in the chain. */
    public static function locationRules(): array
    {
        return [
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            // Only the country is needed (India is preselected). Narrower places are optional: a product with none is sold across India.
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'pincode_id' => ['nullable', 'integer', 'exists:pincodes,id'],
        ];
    }

    public static function locationMessages(): array
    {
        return [
            'country_id.required' => __('Choose the country the product ships from.'),
            'state_id.required' => __('Choose the state the product ships from.'),
            'district_id.required' => __('Choose the district the product ships from.'),
            'city_id.required' => __('Choose the city the product ships from.'),
            'pincode_id.required' => __('Choose the PIN code the product ships from.'),
        ];
    }
}
