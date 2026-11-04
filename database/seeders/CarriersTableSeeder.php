<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CarriersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $carriers = [
            [
                'name' => 'United Parcel Service',
                'code' => 'UPS',
                'tracking_url' => 'https://www.ups.com/track?loc=en_US&requester=ST/track', 'tracking_number_pattern' => '1Z9999999999999999',
                'api_endpoint' => 'https://www.ups.com/api/tracking',
                'api_key' => env('UPS_API_KEY'),
                'is_active' => true,
            ],
            [
                'name' => 'Federal Express',
                'code' => 'FedEx',
                'tracking_url' => 'https://www.fedex.com/track?trackingNumber={tracking_number}',
                'tracking_number_pattern' => 'DESC9999999999999999',
                'api_endpoint' => 'https://api.fedex.com/track',
                'api_key' => env('FEDEX_API_KEY'),
                'is_active' => true,
            ],
            [
                'name' => 'United States Postal Service',
                'code' => 'USPS',
                'tracking_url' => 'https://tools.usps.com/go/TrackConfirmAction_input',
                'tracking_number_pattern' => '9999999999999999999999',
                'api_endpoint' => null,
                'api_key' => null,
                'is_active' => true,
            ],
            [
                'name' => 'DHL Express',
                'code' => 'DHL',
                'tracking_url' => 'https://www.dhl.com/track?trackingNumber={tracking_number}',
                'tracking_number_pattern' => '10-digit tracking',
                'api_endpoint' => 'https://api.dhl.com/track',
                'api_key' => env('DHL_API_KEY'),
                'is_active' => true,
            ],
            [
                'name' => 'Other Carrier',
                'code' => 'Other',
                'tracking_url' => 'https://example.com/track/{tracking_number}',
                'tracking_number_pattern' => 'any',
                'api_endpoint' => null,
                'api_key' => null,
                'is_active' => true,
            ],
        ];

        foreach ($carriers as $carrier) {
            \App\Models\Carrier::create($carrier);
        }
    }
}
