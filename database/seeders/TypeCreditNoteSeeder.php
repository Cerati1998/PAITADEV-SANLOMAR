<?php

namespace Database\Seeders;

use App\Models\TypeCreditNote;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TypeCreditNoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'id' => '01',
                'description' => 'Anulación de la operación',
            ],
            [
                'id' => '02',
                'description' => 'Anulación por error en el RUC',
            ],
            [
                'id' => '03',
                'description' => 'Corrección por error en la descripción',
            ],
            [
                'id' => '04',
                'description' => 'Descuento global',
            ],
            [
                'id' => '05',
                'description' => 'Descuento por ítem',
            ],
            [
                'id' => '06',
                'description' => 'Devolución total',
            ],
            [
                'id' => '07',
                'description' => 'Devolución por ítem',
            ],
            [
                'id' => '08',
                'description' => 'Bonificación',
            ],
            [
                'id' => '09',
                'description' => 'Disminución en el valor',
            ],
            [
                'id' => '10',
                'description' => 'Otros Conceptos',
            ],
            [
                'id' => '11',
                'description' => 'Ajustes de operaciones de exportación',
            ],
            [
                'id' => '12',
                'description' => 'Ajustes afectos al IVAP',
            ],
            [
                'id' => '13',
                'description' => 'Corrección del monto neto pendiente de pago y/o la(s) fechas(s) de vencimiento del pago único o de las cuotas y/o los montos correspondientes a cada cuota, de ser el caso.',
            ],
        ];

        foreach ($types as $type) {
            TypeCreditNote::create($type);
        }
    }
}
