<?php

namespace Database\Seeders;

use App\Models\Integration;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IntegrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $integrations = [
            [
                'name' => 'devolutions_api',
                'description' => 'Funcionalidad de integración para importar y actualizar registros externos de anuncio de Contenedores
                por Anulación',
                'base_url' => url('/integrations/devolutions')
            ]
        ];

        foreach($integrations as $integration){
            Integration::create($integration);
        }
    }
}
