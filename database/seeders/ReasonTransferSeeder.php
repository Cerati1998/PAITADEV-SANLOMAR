<?php

namespace Database\Seeders;

use App\Models\ReasonTransfer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReasonTransferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $transfers = [
            [
                "id" => "01",
                "description" => "Venta",
            ],
            [
                "id" => "02",
                "description" => "Compra",
            ],
            [
                "id" => "04",
                "description" => "Traslado entre establecimientos de la misma empresa",
            ],
            [
                "id" => "08",
                "description" => "Importación",
            ],
            [
                "id" => "09",
                "description" => "Exportación",
            ],
            [
                "id" => "13",
                "description" => "Otros",
            ],
            [
                "id" => "14",
                "description" => "Venta sujeta a confirmación del comprador",
            ],
            [
                "id" => "18",
                "description" => "Traslado emisor itinerante CP",
            ],
            [
                "id" => "19",
                "description" => "Traslado a zona primaria",
            ]
        ];

        foreach ($transfers as $transfer) {
            ReasonTransfer::create($transfer);
        }
    }
}
