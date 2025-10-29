<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TypeDebitNoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'id' => '01',
                'description' => 'Intereses por mora',
            ],
            [
                'id' => '02',
                'description' => 'Aumento en el valor',
            ],
            [
                'id' => '03',
                'description' => 'Penalidades/ otros conceptos',
            ],
            [
                'id' => '11',
                'description' => 'Ajustes de operaciones de exportación',
            ],
            [
                'id' => '12',
                'description' => 'Ajustes afectos al IVAP',
            ],
        ];

        foreach ($types as $type) {
            \App\Models\TypeDebitNote::create($type);
        }
    }
}
