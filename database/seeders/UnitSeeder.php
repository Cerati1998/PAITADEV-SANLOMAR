<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            [
                'id' => 'BJ',
                'description' => 'BALDE'
            ],
            [
                'id' => 'BLL',
                'description' => 'BARRILES'
            ],
            [
                'id' => '4A	',
                'description' => 'BOBINAS'
            ],
            [
                'id' => 'BG',
                'description' => 'BOLSA'
            ],
            [
                'id' => 'BO',
                'description' => 'BOTELLAS'
            ],
            [
                'id' => 'BX',
                'description' => 'CAJAS'
            ],
            [
                'id' => 'CT',
                'description' => 'CARTONES'
            ],
            [
                'id' => 'CMK',
                'description' => 'CENTIMETRO CUADRADO'
            ],
            [
                'id' => 'CMQ',
                'description' => 'CENTIMETRO CUBICO'
            ],
            [
                'id' => 'CMT',
                'description' => 'CENTIMETRO LINEAL'
            ],
            [
                'id' => 'CEN',
                'description' => 'CIENTO DE UNIDADES'
            ],
            [
                'id' => 'CY',
                'description' => 'CILINDRO'
            ],
            [
                'id' => 'CJ',
                'description' => 'CONOS'
            ],
            [
                'id' => 'DZN',
                'description' => 'DOCENA'
            ],
            [
                'id' => 'DZP',
                'description' => 'DOCENA POR 10**6'
            ],
            [
                'id' => 'BE',
                'description' => 'FARDO'
            ],
            [
                'id' => 'GLI',
                'description' => 'GALON INGLES (4,545956L)'
            ],
            [
                'id' => 'GRM',
                'description' => 'GRAMO'
            ],
            [
                'id' => 'GRO',
                'description' => 'GRUESA'
            ],
            [
                'id' => 'HLT',
                'description' => 'HELECTROLITO'
            ],
            [
                'id' => 'LEF',
                'description' => 'HOJA'
            ],
            [
                'id' => 'SET',
                'description' => 'JUEGO'
            ],
            [
                'id' => 'KGM',
                'description' => 'KILOGRAMO'
            ],
            [
                'id' => 'KTM',
                'description' => 'KILOMETRO'
            ],
            [
                'id' => 'KWH',
                'description' => 'KILOVATIO HORA'
            ],
            [
                'id' => 'KT',
                'description' => 'KIT'
            ],
            [
                'id' => 'CA',
                'description' => 'LATAS'
            ],
            [
                'id' => 'LBR',
                'description' => 'LIBRAS'
            ],
            [
                'id' => 'LTR',
                'description' => 'LITROS'
            ],
            [
                'id' => 'MWH',
                'description' => 'MEGAWHAT HORA'
            ],
            [
                'id' => 'MTR',
                'description' => 'METRO'
            ],
            [
                'id' => 'MTK',
                'description' => 'METRO CUADRADO'
            ],
            [
                'id' => 'MTQ',
                'description' => 'METRO CUBICO'
            ],
            [
                'id' => 'MGM',
                'description' => 'MILIGRAMOS'
            ],
            [
                'id' => 'MLT',
                'description' => 'MILILITRO'
            ],
            [
                'id' => 'MMT',
                'description' => 'MILIMETRO'
            ],
            [
                'id' => 'MMK',
                'description' => 'MILIMETRO CUADRADO'
            ],
            [
                'id' => 'MMQ',
                'description' => 'MILIMETRO CUBICO'
            ],
            [
                'id' => 'MLL',
                'description' => 'MILLARES'
            ],
            [
                'id' => 'MU',
                'description' => 'MILLON DE UNIDADES'
            ],
            [
                'id' => 'ONZ',
                'description' => 'ONZAS'
            ],
            [
                'id' => 'PF',
                'description' => 'PALETAS'
            ],
            [
                'id' => 'PK',
                'description' => 'PAQUETE'
            ],
            [
                'id' => 'PR',
                'description' => 'PAR'
            ],
            [
                'id' => 'FOT',
                'description' => 'PIES'
            ],
            [
                'id' => 'FTK',
                'description' => 'PIES CUADRADOS'
            ],
            [
                'id' => 'FTQ',
                'description' => 'PIES CUBICOS'
            ],
            [
                'id' => 'C62',
                'description' => 'PIEZAS'
            ],
            [
                'id' => 'PG',
                'description' => 'PLACAS'
            ],
            [
                'id' => 'ST',
                'description' => 'PLIEGO'
            ],
            [
                'id' => 'INH',
                'description' => 'PULGADAS'
            ],
            [
                'id' => 'RM',
                'description' => 'RESMA'
            ],
            [
                'id' => 'DR',
                'description' => 'TAMBOR'
            ],
            [
                'id' => 'STN',
                'description' => 'TONELADA CORTA'
            ],
            [
                'id' => 'LTN',
                'description' => 'TONELADA LARGA'
            ],
            [
                'id' => 'TNE',
                'description' => 'TONELADAS'
            ],
            [
                'id' => 'TU',
                'description' => 'TUBOS'
            ],
            [
                'id' => 'NIU',
                'description' => 'UNIDAD (BIENES)'
            ],
            [
                'id' => 'ZZ',
                'description' => 'UNIDAD (SERVICIOS)'
            ],
            [
                'id' => 'GLL',
                'description' => 'US GALON (3,7843 L)'
            ],
            [
                'id' => 'YRD',
                'description' => 'YARDA'
            ],
            [
                'id' => 'YDK',
                'description' => 'YARDA CUADRADA'
            ],
        ];

        foreach ($units as $unit) {
            Unit::create($unit);
        }
    }
}
