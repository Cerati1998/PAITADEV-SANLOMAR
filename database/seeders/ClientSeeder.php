<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Client::factory(10)->create();
        $clients = [
            [
                'tipoDoc' => 6,
                'numDoc' => '20395379233',
                'rznSocial' => 'PRONATUR S.A.C.',
                'direccion' => 'mza. k parque industrial lambayeque - chiclayo',
                'branch_id' => 1,
                'company_id' => 1,
            ],
            [
                'tipoDoc' => 6,
                'numDoc' => '20481065799',
                'rznSocial' => 'DOMINUS S.A.C.',
                'direccion' => 'car.panamericana norte km. 1076 fnd. valle san andres tambogrande piura',
                'branch_id' => 1,
                'company_id' => 1,
            ],
            [
                'tipoDoc' => 6,
                'numDoc' => '20213521447',
                'rznSocial' => 'MACROMAR SAC',
                'direccion' => 'carretera paita-sullana',
                'branch_id' => 1,
                'company_id' => 1,
            ],
            [
                'tipoDoc' => 6,
                'numDoc' => '20604393796',
                'rznSocial' => 'COOPERATIVA AGRARIA EXPORTADORA DE PRODUCTOS ORGANICOS DEL PERU COOPAGRO',
                'direccion' => 'ca. atahualpa mz. g lote. 09 int. 301 a.h.las flores paita',
                'branch_id' => 1,
                'company_id' => 1,
            ],
            [
                'tipoDoc' => 6,
                'numDoc' => '20484002488',
                'rznSocial' => 'ASOCIACION DE PRODUCTORES DE BANANO ORGANICO VALLE DEL CHIRA - APBOVAL',
                'direccion' => 'ca. atahualpa mz. g lote. 09 int. 301 a.h.las flores paita',
                'branch_id' => 1,
                'company_id' => 1,
            ],
            [
                'tipoDoc' => 6,
                'numDoc' => '20525706622',
                'rznSocial' => 'ASOC. DE PEQUENOS PROD. DE BANANO ORG. DE MONTENEGRO VALLE DEL CHIRA SULLANA',
                'direccion' => 'ca. atahualpa mz. g lote. 09 int. 301 a.h.las flores paita',
                'branch_id' => 1,
                'company_id' => 1,
            ],

        ];

        foreach ($clients as $client) {
            Client::create($client);
        }
    }
}
