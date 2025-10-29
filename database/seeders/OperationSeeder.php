<?php

namespace Database\Seeders;

use App\Models\Operation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OperationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $operations = [
            [
                'id' => '0101',
                'description' => 'Venta interna',
                'active' => true,
                'documents' => [
                    '01',
                    '03'
                ]
            ],
            [
                'id' => '0112',
                'description' => 'Venta Interna - Sustenta Gastos Deducibles Persona Natural',
                'active' => false,
                'documents' => [
                    '01', 
                ]
            ],
            [
                'id' => '0113',
                'description' => 'Venta Interna-NRUS',
                'active' => false,
                'documents' => [
                    '03'
                ]
            ],
            [
                'id' => '0200',
                'description' => 'Exportación de Bienes',
                'active' => true,
                'documents' => [
                    '01',
                    '03'
                ]
            ],
            [
                'id' => '0201',
                'description' => 'Exportación de Servicios – Realizados en el país',
                'active' => true,
                'documents' => [
                    '01', '03'
                ]
            ],
            [
                'id' => '0202',
                'description' => 'Exportación de Servicios – Hospedaje No Domiciliado',
                'active' => true,
                'documents' => [
                    '01'
                ]
            ],
            [
                'id' => '0203',
                'description' => 'Exportación de Servicios – Transporte de navieras',
                'active' => true,
                'documents' => [
                    '01', '03'
                ]
            ],
            [
                'id' => '0204',
                'description' => 'Exportación de Servicios – Servicios  a naves y aeronaves de bandera extranjera',
                'active' => true,
                'documents' => [
                    '01', '03'
                ]
            ],
            [
                'id' => '0205',
                'description' => 'Exportación de Servicios  - Paquete Turístico',
                'active' => true,
                'documents' => [
                    '01'
                ]
            ],
            [
                'id' => '0206',
                'description' => 'Exportación de Servicios – Servicios complementarios al transporte de carga',
                'active' => true,
                'documents' => [
                    '01', '03'
                ]
            ],
            [
                'id' => '0207',
                'description' => 'Exportación de Servicios – Suministro de energía eléctrica a favor de sujetos domiciliados en ZED',
                'active' => true,
                'documents' => [
                    '01', '03'
                ]
            ],
            [
                'id' => '0208',
                'description' => 'Exportación de Servicios – Realizados parcialmente en el extranjero',
                'active' => true,
                'documents' => [
                    '01', '03'
                ]
            ],
            [
                'id' => '0301',
                'description' => 'Operaciones con Carta de porte aéreo (emitidas en el ámbito nacional)',
                'active' => false,
                'documents' => [
                    '01', '03'
                ]
            ],
            [
                'id' => '0302',
                'description' => 'Operaciones de Transporte ferroviario de pasajeros',
                'active' => false,
                'documents' => [
                    '01', '03'
                ]
            ],
            [
                'id' => '0401',
                'description' => 'Ventas no domiciliados que no es exportación',
                'active' => true,
                'documents' => [
                    '01', '03'
                ]
            ],
            [
                'id' => '1001',
                'description' => 'Operación Sujeta a Detracción',
                'active' => true,
                'documents' => [
                    '01', '03'
                ]
            ],
            [
                'id' => '1002',
                'description' => 'Operación Sujeta a Detracción- Recursos Hidrobiológicos',
                'active' => false,
                'documents' => [
                    '01', '03'
                ]
            ],
            [
                'id' => '1003',
                'description' => 'Operación Sujeta a Detracción- Servicios de Transporte Pasajeros',
                'active' => false,
                'documents' => [
                    '01', '03'
                ]
            ],
            [
                'id' => '1004',
                'description' => 'Operación Sujeta a Detracción- Servicios de Transporte Carga',
                'active' => false,
                'documents' => [
                    '01', '03'
                ]
            ],
            [
                'id' => '2001',
                'description' => 'Operación Sujeta a Percepción',
                'active' => true,
                'documents' => [
                    '01', '03'
                ]
            ],
            [
                'id' => '2100',
                'description' => 'Créditos a empresas',
                'active' => false,
                'documents' => [
                    '01', '03'
                ]
            ],
            [
                'id' => '2101',
                'description' => 'Créditos de consumo revolvente',
                'active' => false,
                'documents' => [
                    '01', '03'
                ]
            ],
            [
                'id' => '2102',
                'description' => 'Créditos de consumo no revolvente',
                'active' => false,
                'documents' => [
                    '01', '03'
                ]
            ],
            [
                'id' => '2103',
                'description' => 'Otras operaciones no gravadas - Empresas del sistema financiero',
                'active' => false,
                'documents' => [
                    '01', '03'
                ]
            ],
            [
                'id' => '2104',
                'description' => 'Otras operaciones no  gravadas - Empresas del sistema de seguros',
                'active' => false,
                'documents' => [
                    '01', '03'
                ]
            ],
        ];

        foreach ($operations as $operation) {
            $item = Operation::create([
                'id' => $operation['id'],
                'description' => $operation['description'],
                'active' => $operation['active']
            ]);

            $item->documents()->attach($operation['documents']);
        }
    }
}
