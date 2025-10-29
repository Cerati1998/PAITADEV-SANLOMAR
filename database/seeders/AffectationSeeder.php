<?php

namespace Database\Seeders;

use App\Models\Affectation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AffectationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $affectations = [
            [
                'id' => '10',
                'description' => 'Gravado - Operación Onerosa',
                'igv' => true,
                'free' => false,
            ],
            [
                'id' => '11',
                'description' => 'Gravado - Retiro por premio',
                'igv' => true,
                'free' => true,
            ],
            [
                'id' => '12',
                'description' => 'Gravado – Retiro por donación',
                'igv' => true,
                'free' => true,
            ],
            [
                'id' => '13',
                'description' => 'Gravado – Retiro',
                'igv' => true,
                'free' => true,
            ],
            [
                'id' => '14',
                'description' => 'Gravado – Retiro por publicidad',
                'igv' => true,
                'free' => true,
            ],
            [
                'id' => '15',
                'description' => 'Gravado – Bonificaciones',
                'igv' => true,
                'free' => true,
            ],
            [
                'id' => '16',
                'description' => 'Gravado – Retiro por entrega a trabajadores',
                'igv' => true,
                'free' => true,
            ],
            [
                'id' => '17',
                'description' => 'Gravado - IVAP',
                'igv' => true,
                'free' => false,
            ],
            [
                'id' => '20',
                'description' => 'Exonerado',
                'igv' => false,
                'free' => false,
            ],
            [
                'id' => '21',
                'description' => 'Exonerado – Transferencia gratuita',
                'igv' => false,
                'free' => true,
            ],
            [
                'id' => '30',
                'description' => 'Inafecto - Operación Onerosa',
                'igv' => false,
                'free' => false,
            ],
            [
                'id' => '31',
                'description' => 'Inafecto – Retiro por Bonificación',
                'igv' => false,
                'free' => true,
            ],
            [
                'id' => '32',
                'description' => 'Inafecto – Retiro',
                'igv' => false,
                'free' => true,
            ],
            [
                'id' => '33',
                'description' => 'Inafecto – Retiro por Muestras Médicas',
                'igv' => false,
                'free' => true,
            ],
            [
                'id' => '34',
                'description' => 'Inafecto - Retiro por Convenio Colectivo',
                'igv' => false,
                'free' => true,
            ],
            [
                'id' => '35',
                'description' => 'Inafecto – Retiro por premio',
                'igv' => false,
                'free' => true,
            ],
            [
                'id' => '36',
                'description' => 'Inafecto - Retiro por publicidad',
                'igv' => false,
                'free' => true,
            ],
            [
                'id' => '37',
                'description' => 'Inafecto - Transferencia gratuita',
                'igv' => false,
                'free' => true,
            ],
            [
                'id' => '40',
                'description' => 'Exportación',
                'igv' => false,
                'free' => false,
            ]
        ];

        foreach ($affectations as $affectation) {
            Affectation::create($affectation);
        }
    }
}
