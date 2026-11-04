<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /** Only languages that have files under lang/<code> can be switched on; English and Hindi ship with the app. */
    public function run(): void
    {
        foreach ([['en', 'English', 0], ['hi', 'हिन्दी', 1]] as [$code, $name, $order]) {
            Language::firstOrCreate(['code' => $code], ['name' => $name, 'direction' => 'ltr', 'is_active' => true, 'sort_order' => $order]);
        }
    }
}
