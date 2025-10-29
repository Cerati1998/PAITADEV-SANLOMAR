<?php

namespace Database\Seeders;

use App\Models\ShippingLine;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShippingLineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shippingLines = [
            ['code' => 'MAEU', 'name' => 'Maersk Line'],
            ['code' => 'MSCU', 'name' => 'Mediterranean Shipping Company (MSC)'],
            ['code' => 'CMDU', 'name' => 'CMA CGM Group'],
            ['code' => 'COSU', 'name' => 'COSCO Shipping Lines'],
            ['code' => 'HLCU', 'name' => 'Hapag-Lloyd'],
            ['code' => 'EVEG', 'name' => 'Evergreen Marine'],
            ['code' => 'ONEY', 'name' => 'Ocean Network Express (ONE)'],
            ['code' => 'YMLU', 'name' => 'Yang Ming Marine Transport'],
            ['code' => 'HMMU', 'name' => 'Hyundai Merchant Marine (HMM)'],
            ['code' => 'ZIMU', 'name' => 'ZIM Integrated Shipping Services'],
            ['code' => 'WANH', 'name' => 'Wan Hai Lines'],
            ['code' => 'SEAL', 'name' => 'Sealand (Maersk)'],
            ['code' => 'KMTL', 'name' => 'KMT Line'],
            ['code' => 'SEAT', 'name' => 'Seatrade'],
            ['code' => 'HAMB', 'name' => 'Hamburg Süd'],
            ['code' => 'OOLU', 'name' => 'OOCL'],
            ['code' => 'NYKL', 'name' => 'NYK Line'],
            ['code' => 'KLEL', 'name' => 'K Line'],
            ['code' => 'UASC', 'name' => 'United Arab Shipping Company'],
            ['code' => 'APLU', 'name' => 'APL'],
            ['code' => 'EMCL', 'name' => 'Emirates Shipping Line'],
            ['code' => 'HEUN', 'name' => 'Heung-A Line'],
            ['code' => 'DLMT', 'name' => 'Del Monte Shipping'],
            ['code' => 'CHQT', 'name' => 'Chiquita Brands International'],
            ['code' => 'STFR', 'name' => 'Star Fruit'],
            ['code' => 'FCFR', 'name' => 'FCF Reefer'],
            ['code' => 'GREX', 'name' => 'Great White Fleet (Del Monte)'],
            ['code' => 'BANF', 'name' => 'Banana Fleet'],
            ['code' => 'DOLP', 'name' => 'Dole Ocean Cargo Express'],
        ];

        foreach ($shippingLines as $line) {
            ShippingLine::create($line);
        }
    }
}
