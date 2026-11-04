<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * INR is the store currency. The others are inactive and their rates are only rough placeholders (units per
     * 1 INR): check them, then switch them on in Admin > Currencies. They are used for display only.
     */
    public function run(): void
    {
        $rows = [
            ['INR', 'Indian Rupee', '₹', 1.0000, 'before', true, 0],
            ['USD', 'US Dollar', '$', 0.0120, 'before', false, 1],
            ['EUR', 'Euro', '€', 0.0110, 'before', false, 2],
            ['GBP', 'British Pound', '£', 0.0095, 'before', false, 3],
            ['AED', 'UAE Dirham', 'AED', 0.0440, 'after', false, 4],
        ];

        foreach ($rows as [$code, $name, $symbol, $rate, $position, $active, $order]) {
            Currency::firstOrCreate(['code' => $code], ['name' => $name, 'symbol' => $symbol, 'exchange_rate' => $rate, 'position' => $position, 'is_active' => $active, 'sort_order' => $order]);
        }
    }
}
