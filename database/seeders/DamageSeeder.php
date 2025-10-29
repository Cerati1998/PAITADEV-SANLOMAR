<?php

namespace Database\Seeders;

use App\Models\Damage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DamageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          $damages = [
            ['code' => 'BK', 'description' => 'BLOCKED'],
            ['code' => 'BN', 'description' => 'BURNED'],
            ['code' => 'BR', 'description' => 'BROKEN/SPLIT'],
            ['code' => 'BT', 'description' => 'BENT'],
            ['code' => 'BW', 'description' => 'BOWED'],
            ['code' => 'CL', 'description' => 'COMPRESION LINE'],
            ['code' => 'CD', 'description' => 'CONSEQUENTIAL DAMAGE'],
            ['code' => 'CK', 'description' => 'CRACKED'],
            ['code' => 'CO', 'description' => 'CORRODED/RUSTY'],
            ['code' => 'CT', 'description' => 'CONTAMINATED'],
            ['code' => 'CU', 'description' => 'CUT'],
            ['code' => 'DB', 'description' => 'DEBRIS/DUNNANGE'],
            ['code' => 'DI', 'description' => 'DISCONNECTED'],
            ['code' => 'DL', 'description' => 'DELAMINATED'],
            ['code' => 'DT', 'description' => 'DENT/BENT'],
            ['code' => 'DY', 'description' => 'DIRTY'],
            ['code' => 'FZ', 'description' => 'FROZEN/SEIzED'],
            ['code' => 'GD', 'description' => 'GORGED'],
            ['code' => 'GP', 'description' => 'GRP CRACKED FIBERGLASS AND PLYWOOD'],
            ['code' => 'HO', 'description' => 'HOLED'],
            ['code' => 'IR', 'description' => 'IMPROPER REPAIR'],
            ['code' => 'LB', 'description' => 'LOW BATTERY '],
            ['code' => 'LF', 'description' => 'LOW FLUID LEVEL'],
            ['code' => 'LK', 'description' => 'LEAK'],
            ['code' => 'LO', 'description' => 'LOOSE'],
            ['code' => 'ME', 'description' => 'MANUFACTURING dEFECT (DELETED"EXISTING")'],
            ['code' => 'ML', 'description' => 'MARKINGS/LABELS'],
            ['code' => 'MS', 'description' => 'MISSING/LOST'],
            ['code' => 'MX', 'description' => 'EQUIPMENT MISUSE'],
            ['code' => 'NI', 'description' => 'NOT WITHIN ISO DIMENSIONS'],
            ['code' => 'NL', 'description' => 'NAILS IN FLOORING'],
            ['code' => 'NO', 'description' => 'NOT AS REQUIRED BY OWNER'],
            ['code' => 'OD', 'description' => 'OUT-OF-DATE'],
            ['code' => 'OL', 'description' => 'OIL SATURATED'],
            ['code' => 'OR', 'description' => 'ODOUR'],
            ['code' => 'OS', 'description' => 'OIL STAINS'],
            ['code' => 'PF', 'description' => 'PAINT FAILURE'],
            ['code' => 'PS', 'description' => 'PITTED SURFACE'],
            ['code' => 'RA', 'description' => 'REMOVE FOR ACCESS'],
            ['code' => 'RC', 'description' => 'CONVERSION REQUIRED'],
            ['code' => 'SH', 'description' => 'SHORT/OPEN CIRCUIT'],
            ['code' => 'SO', 'description' => 'GRP RAJADO, AGRIETADO (SOLO LA FIBRA DE VIDRIO)'],
            ['code' => 'TR', 'description' => 'TEST REQUIRED'],
            ['code' => 'WN', 'description' => 'REPAIR CARRIED OUT WITH INADEQUATE MATERIAL'],
            ['code' => 'WT', 'description' => 'WEAR: INEVITABLE DETERIORATION OF A COMPONENT DUE TO USE UNDER HARSH CONDITIONS'],
            ];

        foreach ($damages as $damage) {
            Damage::create($damage);
        }
    }
}
