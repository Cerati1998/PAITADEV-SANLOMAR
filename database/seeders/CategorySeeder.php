<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Repuestos y Refacciones',
                'description' => 'engranajes, poleas, etc.'
            ],
            [
                'name' => 'Consumibles',
                'description' => 'Discos, soldadura, etc.'
            ],
            [
                'name' => 'Kits de Limpieza',
                'description' => 'acido, siliconas, etc.'
            ],
            [
                'name' => 'Herramientas',
                'description' => 'Desarmador, llaves, etc.'
            ],
            [
                'name' => 'Accesorios',
                'description' => 'adaptadores, extensiones, etc.'
            ],
            [
                'name' => 'Servicios',
                'description' => 'reparaciones, asesorias, etc.'
            ],
        ];

        foreach($categories as $category){
            Category::create($category);
        }
    }
}
