<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\District;
use App\Models\Pincode;
use App\Models\State;
use Illuminate\Console\Command;

/**
 * Loads districts, cities and PIN codes from a CSV with the header: state,district,city,pincode
 * (one row per PIN code). States must already exist (run the LocationMasterSeeder first).
 */
class ImportLocations extends Command
{
    protected $signature = 'location:import {file : Path to the CSV file}';

    protected $description = 'Import districts, cities and PIN codes under existing states';

    public function handle(): int
    {
        $path = (string) $this->argument('file');
        if (! is_file($path) || ! is_readable($path)) {
            $this->error("Cannot read {$path}.");

            return self::FAILURE;
        }

        $handle = fopen($path, 'r');
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), fgetcsv($handle) ?: []);
        foreach (['state', 'district', 'city', 'pincode'] as $column) {
            if (! in_array($column, $header, true)) {
                $this->error("The CSV needs a '{$column}' column.");
                fclose($handle);

                return self::FAILURE;
            }
        }

        $counts = ['rows' => 0, 'skipped' => 0];
        while (($row = fgetcsv($handle)) !== false) {
            $counts['rows']++;
            $data = array_combine($header, array_pad($row, count($header), ''));
            $state = State::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($data['state']))])->first();
            $district = trim($data['district']);
            $city = trim($data['city']);
            $pin = preg_replace('/\D/', '', $data['pincode']);

            if (! $state || $district === '' || $city === '' || strlen($pin) !== 6) {
                $counts['skipped']++;
                continue;
            }

            $districtModel = District::firstOrCreate(['state_id' => $state->id, 'name' => $district]);
            $cityModel = City::firstOrCreate(['district_id' => $districtModel->id, 'name' => $city]);
            Pincode::firstOrCreate(['city_id' => $cityModel->id, 'code' => $pin]);
        }
        fclose($handle);

        $this->info("Imported {$counts['rows']} rows, skipped {$counts['skipped']} (unknown state or invalid PIN).");

        return self::SUCCESS;
    }
}
