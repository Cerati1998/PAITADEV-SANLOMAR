<?php

namespace Database\Seeders;

use App\Models\ContainerType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContainerTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $containerTypes = [
            // Standard 20'
            [
                'code'        => "22GP",
                'description' => "20'-ST Standard",
                'length'      => 6.06,
                'width'       => 2.44,
                'height'      => 2.59,
                'iso_code'    => "22G1",
            ],
            // Standard 40'
            [
                'code'        => "42GP",
                'description' => "40'-ST Standard",
                'length'      => 12.19,
                'width'       => 2.44,
                'height'      => 2.59,
                'iso_code'    => "42G1",
            ],
            // Standard High Cube 40'
            [
                'code'        => "40HC",
                'description' => "40'-HQ High Cube",
                'length'      => 12.19,
                'width'       => 2.44,
                'height'      => 2.89,
                'iso_code'    => "45G1",
            ],
            // Standard High Cube 45'
            [
                'code'        => "45HC",
                'description' => "45'-HQ High Cube",
                'length'      => 13.72,
                'width'       => 2.44,
                'height'      => 2.89,
                'iso_code'    => "L5G1",
            ],
            // Hardtop 20'
            [
                'code'        => "20HT",
                'description' => "20'-HT Hardtop",
                'length'      => 6.06,
                'width'       => 2.44,
                'height'      => 2.59,
                'iso_code'    => "22U6",
            ],
            // Hardtop 40'
            [
                'code'        => "40HT",
                'description' => "40'-HT Hardtop",
                'length'      => 12.19,
                'width'       => 2.44,
                'height'      => 2.59,
                'iso_code'    => "42U6",
            ],
            // Hardtop High Cube 40'
            [
                'code'        => "40HTHC",
                'description' => "40'-HTHC Hardtop High Cube",
                'length'      => 12.19,
                'width'       => 2.44,
                'height'      => 2.89,
                'iso_code'    => "45U6",
            ],
            // Open Top 20'
            [
                'code'        => "20OT",
                'description' => "20'-OT Open Top",
                'length'      => 6.06,
                'width'       => 2.44,
                'height'      => 2.59,
                'iso_code'    => "22U1",
            ],
            // Open Top 40'
            [
                'code'        => "40OT",
                'description' => "40'-OT Open Top",
                'length'      => 12.19,
                'width'       => 2.44,
                'height'      => 2.59,
                'iso_code'    => "42U1",
            ],
            // Open Top High Cube 40'
            [
                'code'        => "40OTHC",
                'description' => "40'-OTHC Open Top High Cube",
                'length'      => 12.19,
                'width'       => 2.44,
                'height'      => 2.89,
                'iso_code'    => "45U1",
            ],
            // Reefer 20'
            [
                'code'        => "20RF",
                'description' => "20'-RF Reefer",
                'is_reefer' => 1,
                'length'      => 5.45,
                'width'       => 2.29,
                'height'      => 2.27,
                'iso_code'    => "22R1",
            ],
            // Reefer High Cube 40'
            [
                'code'        => "40RF",
                'description' => "40'-RFHC Reefer High Cube",
                'is_reefer' => 1,
                'length'      => 11.56,
                'width'       => 2.29,
                'height'      => 2.59,
                'iso_code'    => "45R1",
            ],
            // Flatrack 20'
            [
                'code'        => "20FR",
                'description' => "20'-FR Flat Rack",
                'length'      => 5.70,
                'width'       => 2.36,
                'height'      => 2.24,
                'iso_code'    => "22P3",
            ],
            // Flatrack 40'
            [
                'code'        => "40FR",
                'description' => "40'-FR Flat Rack",
                'length'      => 12.06,
                'width'       => 2.44,
                'height'      => 2.28,
                'iso_code'    => "42P3",
            ],
            // Platform 40'
            [
                'code'        => "40PF",
                'description' => "40'-PF Platform",
                'length'      => 12.19,
                'width'       => 2.44,
                'height'      => 0.65,
                'iso_code'    => "45P3",
            ],
        ];

        foreach ($containerTypes as $containerType) {
            ContainerType::create($containerType);
        }
    }
}
