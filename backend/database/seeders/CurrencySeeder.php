<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MultiCurrency;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        MultiCurrency::updateOrCreate(
            ['currency_code' => 'TRY'],
            [
                'currency_name' => '₺-TRY',
                'country_code' => 'TR',
                'currency_icon' => '₺',
                'is_default' => 'Yes',
                'currency_rate' => 1,
                'currency_position' => 'right',
                'status' => 1,
            ]
        );

        MultiCurrency::updateOrCreate(
            ['currency_code' => 'USD'],
            [
                'currency_name' => '$-USD',
                'country_code' => 'USD',
                'currency_icon' => '$',
                'is_default' => 'No',
                'currency_rate' => 1,
                'currency_position' => 'right',
                'status' => 0,
            ]
        );
    }
}
