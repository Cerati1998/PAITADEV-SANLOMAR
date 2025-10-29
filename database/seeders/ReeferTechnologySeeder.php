<?php

namespace Database\Seeders;

use App\Models\ReeferTechnology;
use Illuminate\Database\Seeder;

class ReeferTechnologySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $technologies = [
            [
                'name' => 'COLD TREATMENT',
                'temperature_min' => -1,
                'temperature_max' => 1,
                'humidity_min' => 50,
                'humidity_max' => 95,
                'ventilation_min' => 5,
                'ventilation_max' => 25,
                'atmosphere_o2_min' => null,
                'atmosphere_o2_max' => null,
                'atmosphere_co2_min' => null,
                'atmosphere_co2_max' => null,
                'usage' => 'Tecnología para tratamiento en frío',
            ],
            [
                'name' => 'CONVENCIONAL',
                'temperature_min' => -23,
                'temperature_max' => 30.5,
                'humidity_min' => 50,
                'humidity_max' => 95,
                'ventilation_min' => 5,
                'ventilation_max' => 250,
                'atmosphere_o2_min' => null,
                'atmosphere_o2_max' => 12,
                'atmosphere_co2_min' => null,
                'atmosphere_co2_max' => 12,
                'usage' => 'Tecnología convencional con humedad',
            ],
            [
                'name' => 'CONVENCIONAL-SIN HUMEDAD',
                'temperature_min' => -10,
                'temperature_max' => -30,
                'humidity_min' => 0,
                'humidity_max' => 0,
                'ventilation_min' => null,
                'ventilation_max' => null,
                'atmosphere_o2_min' => 0,
                'atmosphere_o2_max' => 0,
                'atmosphere_co2_min' => 0,
                'atmosphere_co2_max' => 0,
                'usage' => 'Convencional sin control de humedad',
            ],
            [
                'name' => 'DAIKIN ACTIVE CA',
                'temperature_min' => -1,
                'temperature_max' => 15,
                'humidity_min' => 50,
                'humidity_max' => 95,
                'ventilation_min' => 0,
                'ventilation_max' => 0,
                'atmosphere_o2_min' => 1,
                'atmosphere_o2_max' => 21,
                'atmosphere_co2_min' => 1,
                'atmosphere_co2_max' => 17,
                'usage' => 'Control activo con CA',
            ],
            [
                'name' => 'DAIKIN CA',
                'temperature_min' => -1,
                'temperature_max' => 15,
                'humidity_min' => 50,
                'humidity_max' => 95,
                'ventilation_min' => 0,
                'ventilation_max' => 0,
                'atmosphere_o2_min' => 1,
                'atmosphere_o2_max' => 21,
                'atmosphere_co2_min' => 1,
                'atmosphere_co2_max' => 17,
                'usage' => 'CA estándar Daikin',
            ],
            [
                'name' => 'EVERFRESH',
                'temperature_min' => -0.5,
                'temperature_max' => 15,
                'humidity_min' => 50,
                'humidity_max' => 95,
                'ventilation_min' => null,
                'ventilation_max' => null,
                'atmosphere_o2_min' => 2,
                'atmosphere_o2_max' => 17,
                'atmosphere_co2_min' => 1,
                'atmosphere_co2_max' => 19,
                'usage' => 'Tecnología de atmósfera EverFresh',
            ],
            [
                'name' => 'LIVENTUS',
                'temperature_min' => -1,
                'temperature_max' => 15,
                'humidity_min' => 50,
                'humidity_max' => 95,
                'ventilation_min' => null,
                'ventilation_max' => null,
                'atmosphere_o2_min' => 3,
                'atmosphere_o2_max' => 21,
                'atmosphere_co2_min' => 1,
                'atmosphere_co2_max' => 19,
                'usage' => 'Liventus control de atmósfera',
            ],

            [
                'name' => 'MAXTEND',
                'temperature_min' => -1,
                'temperature_max' => 15,
                'humidity_min' => 50,
                'humidity_max' => 95,
                'ventilation_min' => 0,
                'ventilation_max' => 40,
                'atmosphere_o2_min' => 3,
                'atmosphere_o2_max' => 21,
                'atmosphere_co2_min' => 1,
                'atmosphere_co2_max' => 19,
                'usage' => 'MaxTend segura',
            ],
            [
                'name' => 'MAXTEND+CT',
                'temperature_min' => -1,
                'temperature_max' => 1,
                'humidity_min' => 50,
                'humidity_max' => 95,
                'ventilation_min' => null,
                'ventilation_max' => null,
                'atmosphere_o2_min' => 3,
                'atmosphere_o2_max' => 21,
                'atmosphere_co2_min' => 1,
                'atmosphere_co2_max' => 19,
                'usage' => 'MaxTend combinado con Cold Treatment',
            ],
            [
                'name' => 'SEACARE',
                'temperature_min' => -0.5,
                'temperature_max' => 15,
                'humidity_min' => 50,
                'humidity_max' => 95,
                'ventilation_min' => null,
                'ventilation_max' => null,
                'atmosphere_o2_min' => 3,
                'atmosphere_o2_max' => 21,
                'atmosphere_co2_min' => 4,
                'atmosphere_co2_max' => 20,
                'usage' => 'Tecnología SeaCare para ambiente controlado',
            ],
        ];

        foreach ($technologies as $tech) {
            ReeferTechnology::create($tech);
        }
    }
}
