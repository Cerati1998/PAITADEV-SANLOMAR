<?php

namespace Database\Seeders;

use App\Models\Detraction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DetractionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $detractions = [
            [
                'id' => '001',
                'description' => 'Azúcar y melaza de caña',
                'percent' => 10,
            ],
            [
                'id' => '003',
                'description' => 'Alcohol etílico',
                'percent' => 10,
            ],
            [
                'id' => '004',
                'description' => 'Recursos hidrobiológicos',
                'percent' => 4,
            ],
            [
                'id' => '005',
                'description' => 'Maíz amarillo duro',
                'percent' => 4,
            ],
            [
                'id' => '007',
                'description' => 'Caña de azúcar',
                'percent' => 10,
            ],
            [
                'id' => '008',
                'description' => 'Madera',
                'percent' => 4,
            ],
            [
                'id' => '009',
                'description' => 'Arena y piedra.',
                'percent' => 10,
            ],
            [
                'id' => '010',
                'description' => 'Residuos, subproductos, desechos, recortes, desperdicios y formas primarias derivadas de los mismos',
                'percent' => 15,
            ],
            [
                'id' => '011',
                'description' => 'Bienes gravados con el IGV por renuncia a la exoneración',
                'percent' => 10,
            ],
            [
                'id' => '012',
                'description' => 'Intermediación laboral y tercerización',
                'percent' => 12,
            ],
            [
                'id' => '014',
                'description' => 'Carnes y despojos comestibles',
                'percent' => 4,
            ],
            [
                'id' => '016',
                'description' => 'Aceite de pescado',
                'percent' => 10,
            ],
            [
                'id' => '017',
                'description' => 'Harina, polvo y “pellets” de pescado, crustáceos, moluscos y demás invertebrados acuáticos',
                'percent' => 4,
            ],
            [
                'id' => '019',
                'description' => 'Arrendamiento de bienes',
                'percent' => 10,
            ],
            [
                'id' => '020',
                'description' => 'Mantenimiento y reparación de bienes muebles',
                'percent' => 12,
            ],
            [
                'id' => '021',
                'description' => 'Movimiento de carga',
                'percent' => 10,
            ],
            [
                'id' => '022',
                'description' => 'Otros servicios empresariales',
                'percent' => 12,
            ],
            [
                'id' => '023',
                'description' => 'Leche',
                'percent' => 4,
            ],
            [
                'id' => '024',
                'description' => 'Comisión mercantil',
                'percent' => 10,
            ],
            [
                'id' => '025',
                'description' => 'Fabricación de bienes por encargo',
                'percent' => 10,
            ],
            [
                'id' => '026',
                'description' => 'Servicio de transporte de personas',
                'percent' => 10,
            ],
            [
                'id' => '030',
                'description' => 'Contratos de construcción',
                'percent' => 4,
            ],
            [
                'id' => '031',
                'description' => 'Oro gravado con el IGV',
                'percent' => 10,
            ],
            [
                'id' => '032',
                'description' => 'Páprika y otros frutos de los generos capsicum o pimienta',
                'percent' => 10,
            ],
            [
                'id' => '034',
                'description' => 'Minerales metálicos no auríferos',
                'percent' => 10,
            ],
            [
                'id' => '035',
                'description' => 'Bienes exonerados del IGV',
                'percent' => 1.5,
            ],
            [
                'id' => '036',
                'description' => 'Oro y demás minerales metálicos exonerados del IGV',
                'percent' => 1.5,
            ],
            [
                'id' => '037',
                'description' => 'Demás servicios gravados con el IGV',
                'percent' => 12,
            ],
            [
                'id' => '039',
                'description' => 'Minerales no metálicos',
                'percent' => 10,
            ],
            [
                'id' => '041',
                'description' => 'Plomo',
                'percent' => 15,
            ]
        ];

        foreach ($detractions as $detraction) {
            Detraction::create($detraction);
        }
    }
}
