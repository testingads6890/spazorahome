<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\District;
use App\Models\Pincode;
use App\Models\State;
use Illuminate\Database\Eloquent\Builder;

/** Feeds the cascading country > state > district > city > PIN dropdowns. Returns [{id, name}] only. */
class LocationOptionsController extends Controller
{
    public const LEVELS = ['countries', 'states', 'districts', 'cities', 'pincodes'];

    public function options(string $level, ?int $parent = null)
    {
        abort_unless(in_array($level, self::LEVELS, true), 404);

        $query = match ($level) {
            'countries' => Country::query()->where('is_active', true),
            'states' => State::query()->where('is_active', true)->when($parent, fn (Builder $q) => $q->where('country_id', $parent)),
            'districts' => District::query()->where('is_active', true)->when($parent, fn (Builder $q) => $q->where('state_id', $parent)),
            'cities' => City::query()->where('is_active', true)->when($parent, fn (Builder $q) => $q->where('district_id', $parent)),
            'pincodes' => Pincode::query()->where('is_active', true)->when($parent, fn (Builder $q) => $q->where('city_id', $parent)),
        };

        $column = $level === 'pincodes' ? 'code' : 'name';
        $rows = $query->orderBy($column)->get(['id', $column])
            ->map(fn ($row) => ['id' => $row->id, 'name' => (string) $row->{$column}]);

        return response()->json($rows)->header('Cache-Control', 'private, max-age=300');
    }
}
