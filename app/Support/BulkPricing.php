<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Reads the bulk-pricing fields of the product forms (seller and admin) into what Product::bulk_pricing stores. */
class BulkPricing
{
    public const ROWS = 3;

    public static function rules(): array
    {
        return [
            'bulk_mode' => 'nullable|in:default,custom,off',
            'bulk_min' => 'nullable|array|max:' . self::ROWS,
            'bulk_min.*' => 'nullable|integer|min:2|max:100000',
            'bulk_off' => 'nullable|array|max:' . self::ROWS,
            'bulk_off.*' => 'nullable|numeric|min:0.5|max:80',
        ];
    }

    /** null = store default, [] = no bulk discount, otherwise [{"min":2,"off":5}, ...]. */
    public static function fromRequest(Request $request): ?array
    {
        $mode = $request->input('bulk_mode', 'default');
        if ($mode === 'off') {
            return [];
        }
        if ($mode !== 'custom') {
            return null;
        }

        $tiers = [];
        foreach ((array) $request->input('bulk_min', []) as $i => $min) {
            $off = $request->input("bulk_off.$i");
            if ($min === null || $min === '' || $off === null || $off === '') {
                continue;
            }
            $tiers[(int) $min] = round((float) $off, 2);
        }
        if (! $tiers) {
            throw ValidationException::withMessages(['bulk_min' => 'Add at least one quantity and discount, or choose another bulk-pricing option.']);
        }
        ksort($tiers);
        $last = 0;
        foreach ($tiers as $pct) {
            if ($pct <= $last) {
                throw ValidationException::withMessages(['bulk_off' => 'A bigger quantity should give a bigger discount.']);
            }
            $last = $pct;
        }

        return collect($tiers)->map(fn ($off, $min) => ['min' => $min, 'off' => $off])->values()->all();
    }
}
