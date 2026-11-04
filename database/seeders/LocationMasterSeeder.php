<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\State;
use App\Support\GstState;
use Illuminate\Database\Seeder;

/** India and its states / union territories. Districts, cities and PIN codes come from an authoritative import (php artisan location:import). */
class LocationMasterSeeder extends Seeder
{
    public function run(): void
    {
        $india = Country::firstOrCreate(['name' => 'India'], ['iso2' => 'IN', 'is_active' => true]);

        foreach (GstState::names() as $state) {
            State::firstOrCreate(['country_id' => $india->id, 'name' => $state], ['is_active' => true]);
        }
    }
}
