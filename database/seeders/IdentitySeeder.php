<?php

namespace Database\Seeders;

use App\Models\Identity;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IdentitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $identities = [
            [
                'id' => '-',
                'description' => 'Sin documento'
            ],
            [
                'id' => 0,
                'description' => 'DOC.TRIB. (no domiciliado)'
            ],
            [
                'id' => 1,
                'description' => 'DNI'
            ],
            [
                'id' => 2,
                'description' => 'Código IICL'
            ],
            [
                'id' => 4,
                'description' => 'Carnet de extranjería'
            ],
            [
                'id' => 6,
                'description' => 'RUC'
            ],
            [
                'id' => 7,
                'description' => 'Pasaporte'
            ],
            [
                'id' => 'A',
                'description' => 'Cédula Diplomática de identidad'
            ],
            [
                'id' => 'B',
                'description' => 'DOC.IDENT.EXTRANJERO'
            ],
            [
                'id' => 'C',
                'description' => 'TIN - Tax Identification Number'
            ],
            [
                'id' => 'D',
                'description' => 'IN - Identification Number'
            ],
            [
                'id' => 'E',
                'description' => 'TAM- Tarjeta Andina de Migración'
            ],
        ];

        foreach ($identities as $identity) {
            Identity::create($identity);
        }
    }
}
