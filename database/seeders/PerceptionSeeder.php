<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PerceptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $perceptions = [
            [
                'id' => '51',
                'description' => 'Percepción venta interna',
                'porcentaje' => 0.02,
            ],
            [
                'id' => '52',
                'description' => 'Percepción a la adquisición de combustible',
                'porcentaje' => 0.01,
            ],
            [
                'id' => '53',
                'description' => 'Percepción realizada al agente de percepción con tasa especial',
                'porcentaje' => 0.005,
            ],
        ];

        foreach ($perceptions as $perception) {
            \App\Models\Perception::create($perception);
        }
    }
}
