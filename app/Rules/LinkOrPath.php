<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A link an admin types in: a full http(s) address, or a path on this site such as /search?category=3.
 * Anything else (javascript:, //other-site, bare words) is refused.
 */
class LinkOrPath implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! static::passes($value)) {
            $fail('The :attribute must be a full address (https://...) or a path on this site starting with /.');
        }
    }

    public static function passes(mixed $value): bool
    {
        if (! is_string($value) || $value === '' || preg_match('/[\s<>"\']/', $value)) {
            return false;
        }
        if (str_starts_with($value, '/')) {
            // "//host" and "/\host" are treated by browsers as another site.
            return ! str_starts_with($value, '//') && ! str_starts_with($value, '/\\');
        }

        return (bool) preg_match('~^https?://~i', $value) && filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
}
