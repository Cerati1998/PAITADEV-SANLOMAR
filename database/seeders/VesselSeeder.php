<?php

namespace Database\Seeders;

use App\Models\ShippingLine;
use App\Models\Vessel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VesselSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //añadir con el seeder solamente las naves de la linea seatrade
        $seatrade_id = ShippingLine::where('code', 'SEAT')->first()->id;

        $vessels = [
            ['imo_number' => '9059640', 'name' => 'HOOD ISLAND', 'type' => 'container', 'shipping_line_id' => $seatrade_id, 'pallets' => 5540],
            ['imo_number' => '9179256', 'name' => 'ATLANTIC REEFER', 'type' => 'container', 'shipping_line_id' => $seatrade_id, 'pallets' => 8550],
            ['imo_number' => '9059602', 'name' => 'ALBEMARLE ISLAND', 'type' => 'container', 'shipping_line_id' => $seatrade_id, 'pallets' => 5540],
            ['imo_number' => '9015199', 'name' => 'NEDERLAND STREAM', 'type' => 'container', 'shipping_line_id' => $seatrade_id, 'pallets' => 8610],
            ['imo_number' => '9051791', 'name' => 'COLD STREAM', 'type' => 'container', 'shipping_line_id' => $seatrade_id, 'pallets' => 5950],
            ['imo_number' => '9059614', 'name' => 'BARRINGTON ISLAND', 'type' => 'container', 'shipping_line_id' => $seatrade_id, 'pallets' => 5540],

            ['imo_number' => '9059638', 'name' => 'DUNCAN ISLAND', 'type' => 'container', 'shipping_line_id' => $seatrade_id, 'pallets' => 5540],
            ['imo_number' => '9059626', 'name' => 'CHARLES ISLAND', 'type' => 'container', 'shipping_line_id' => $seatrade_id, 'pallets' => 5540],
            ['imo_number' => '9015216', 'name' => 'SCHWEIZ STREAM', 'type' => 'container', 'shipping_line_id' => $seatrade_id, 'pallets' => 8611],
            ['imo_number' => '9030149', 'name' => 'SWEDISH STREAM', 'type' => 'container', 'shipping_line_id' => $seatrade_id, 'pallets' => 8611],
            ['imo_number' => '9179268', 'name' => 'PACIFIC REEFER', 'type' => 'container', 'shipping_line_id' => $seatrade_id, 'pallets' => 8550],
            ['imo_number' => '9015187', 'name' => 'HELLAS STREAM', 'type' => 'container', 'shipping_line_id' => $seatrade_id, 'pallets' => 8610],
            ['imo_number' => '9045924', 'name' => 'PACIFIC MERMAID', 'type' => 'container', 'shipping_line_id' => $seatrade_id, 'pallets' => 5740],
            ['imo_number' => '9030137', 'name' => 'ITALIA STREAM', 'type' => 'container', 'shipping_line_id' => $seatrade_id, 'pallets' => 8610],
            ['imo_number' => '9438482', 'name' => 'CS SERVICE', 'type' => 'container', 'shipping_line_id' => $seatrade_id, 'pallets' => 0],
        ];

        foreach ($vessels as $vessel) {
            Vessel::create($vessel);
        }
    }
}
