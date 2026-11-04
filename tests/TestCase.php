<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    //

    /** A valid India dispatch location (country > state > district > city > PIN) for product form payloads. */
    protected function dispatchLocation(): array
    {
        $country = \App\Models\Country::firstOrCreate(['name' => 'India'], ['iso2' => 'IN', 'is_active' => true]);
        $state = \App\Models\State::firstOrCreate(['country_id' => $country->id, 'name' => 'Rajasthan'], ['is_active' => true]);
        $district = \App\Models\District::firstOrCreate(['state_id' => $state->id, 'name' => 'Jaipur'], ['is_active' => true]);
        $city = \App\Models\City::firstOrCreate(['district_id' => $district->id, 'name' => 'Jaipur'], ['is_active' => true]);
        $pin = \App\Models\Pincode::firstOrCreate(['city_id' => $city->id, 'code' => '302001'], ['is_active' => true]);

        return ['country_id' => $country->id, 'state_id' => $state->id, 'district_id' => $district->id, 'city_id' => $city->id, 'pincode_id' => $pin->id];
    }
}
