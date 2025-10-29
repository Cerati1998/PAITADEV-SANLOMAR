<?php

namespace Database\Seeders;

use App\Models\Method;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            ['code' => 'CC', 'description' => strtoupper('Chemical clean')],
            ['code' => 'DO', 'description' => strtoupper('Deodorise')],
            ['code' => 'FR', 'description' => strtoupper('Free-up / includ. lubricate')],
            ['code' => 'FT', 'description' => strtoupper('Refit')],
            ['code' => 'GS', 'description' => strtoupper('Dent Removal / Straightening')],
            ['code' => 'GT', 'description' => strtoupper('Remove glue and tape')],
            ['code' => 'GW', 'description' => strtoupper('Straighten and weld')],
            ['code' => 'IN', 'description' => strtoupper('Install')],
            ['code' => 'IT', 'description' => strtoupper('Insert')],
            ['code' => 'MC', 'description' => strtoupper('Mechanical Cleaning (Sweep, scrap off paint)')],
            ['code' => 'MV', 'description' => strtoupper('Remove labels, brands, logos, graffiti')],
            ['code' => 'PA', 'description' => strtoupper('Paint')],
            ['code' => 'PL', 'description' => strtoupper('FLOOR POLISHING')],
            ['code' => 'PR', 'description' => strtoupper('REMOVE LOCALIZED CORROSION AND REPAINT')],
            ['code' => 'PS', 'description' => strtoupper('Surface preparation and paint')],
            ['code' => 'PT', 'description' => strtoupper('Patch')],
            ['code' => 'PX', 'description' => strtoupper('Patch and foam')],
            ['code' => 'RA', 'description' => strtoupper('Realign')],
            ['code' => 'RD', 'description' => strtoupper('Remove and dispose')],
            ['code' => 'RE', 'description' => strtoupper('REASEGURAR PERNOS O COMPONENTES SUELTOS')],
            ['code' => 'RM', 'description' => strtoupper('Remove (without re-placement)')],
            ['code' => 'RP', 'description' => strtoupper('Replace')],
            ['code' => 'RR', 'description' => strtoupper('Remove and refit after repair')],
            ['code' => 'SE', 'description' => strtoupper('Seal/reseal')],
            ['code' => 'SI', 'description' => strtoupper('Splice')],
            ['code' => 'SN', 'description' => strtoupper('Section')],
            ['code' => 'SW', 'description' => strtoupper('SCANNING')],
            ['code' => 'XW', 'description' => strtoupper('GRINDING AND WELDING')],
            ['code' => 'WD', 'description' => strtoupper('Weld')],
            ['code' => 'WP', 'description' => strtoupper('Sweep')],
            ['code' => 'WW', 'description' => strtoupper('Water Wash')],
        ];

        foreach ($methods as $method) {
            Method::create($method);
        }
    }
}
