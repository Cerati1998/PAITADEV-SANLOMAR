<?php

namespace Database\Seeders;

use App\Models\ReeferMachine;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReeferMachineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reeferTechnologies = [
            // Main Brands
            [
                'code' => 'THERMOKING',
                'name' => 'Thermo King'
            ],
            [
                'code' => 'CARRIER',
                'name' => 'Carrier Transicold'
            ],
            [
                'code' => 'DAIKIN',
                'name' => 'Daikin Reefer'
            ],
            [
                'code' => 'STARCOOL',
                'name' => 'Star Cool (Mitsubishi)'
            ]
        ];

        foreach ($reeferTechnologies as $technology) {
            ReeferMachine::create($technology);
        }
    }
}
