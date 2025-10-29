<?php

namespace Database\Seeders;

use App\Models\CustomBroker;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomBrokerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customBrokers = [
            ['tipoDoc' => 6, 'numDoc' => '20101409199', 'rznSocial' => 'TRANSOCEANIC AGENCIA DE ADUANA S.A.C.', 'direccion' => 'AV. SAENZ PENA NRO 1426', 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20387138090', 'rznSocial' => 'AGENCIA DE ADUANA LAMA S.A.', 'direccion' => 'AV. TRINIDAD MORAN NRO 971 DEP. 201', 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20100412366', 'rznSocial' => 'SAVAR AGENCIA DE ADUANA S.A.', 'direccion' => 'AV. BOCANEGRA NRO 274 URB. URB. IND. FUNDO BOCANEGRA', 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20101369464', 'rznSocial' => 'ADUANDINA AGENCIA DE ADUANA S.A.', 'direccion' => 'JR. HERMANOS CATARI NRO 323 URB. MARANGA', 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20543952215', 'rznSocial' => 'ATENAS AGENCIA DE ADUANA S.A.C.', 'direccion' => 'JR. NICOLAS DE PIEROLA NRO 241 DEP. 2 URB. BELLAVISTA', 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20269413931', 'rznSocial' => 'ADUANERA CAPRICORNIO S.A.', 'direccion' => 'AV. DOS DE MAYO NRO 671', 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20385817836', 'rznSocial' => 'DHL EXPRESS ADUANAS PERU S.A.C.', 'direccion' => 'CAL. 1 MZA. A LOTE 06 U. IND BOCANEGRA', 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20389395481', 'rznSocial' => 'DHL GLOBAL FORWARDING ADUANAS PERU S.A.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20478175524', 'rznSocial' => 'CLI GESTIONES ADUANERAS S.A.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20101390749', 'rznSocial' => 'AGENCIA PORTUARIA S.A.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20257094465', 'rznSocial' => 'MACROMAR AGENCIA DE ADUANA S.A.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20546192947', 'rznSocial' => 'IPH AGENCIA DE ADUANA E.I.R.L.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20484296376', 'rznSocial' => 'MACEPIMA AGENCIA DE ADUANA S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20526643578', 'rznSocial' => 'LA ESMERALDA AGENCIA DE ADUANA S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20546138063', 'rznSocial' => 'V.I.I. AGENCIA DE ADUANA S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20213635531', 'rznSocial' => 'DOGANA S.A. (AGENCIA DE ADUANA)', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20251967751', 'rznSocial' => 'AGENTES DE ADUANA SAN NICOLAS S.A.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20100255325', 'rznSocial' => 'AGENCIAS RANSA S.A.C. (AGENCIA DE ADUANA)', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20100417082', 'rznSocial' => 'GARCÍA PÉRSICO S.A.C. (AGENCIA DE ADUANA)', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20101408460', 'rznSocial' => 'FRANCISCO PICCO VIEIRA S.A. AG. DE ADUANA', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20100567645', 'rznSocial' => 'RODOLFO BUSTAMANTE S.A. AGENTE DE ADUANAS', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20451757581', 'rznSocial' => 'ANDINA NEGOCIOS INTERNACIONALES S.A.C. (AG. ADUANA)', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20609610761', 'rznSocial' => 'NEXUS AGENCIA DE ADUANAS DEL PERÚ S.A.C. (OEA)', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20506138443', 'rznSocial' => 'GALAXY AGENCIA DE ADUANA S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20508870141', 'rznSocial' => 'FOX ADUANAS S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20445237010', 'rznSocial' => 'EMPRESA ADUANERA OLIMPYA S.A.C. AGENTE DE ADUANA', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20119204543', 'rznSocial' => 'MAR Y MAR AGENTES DE ADUANA S.A.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20504363903', 'rznSocial' => 'SAKJ DEPOT S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20335082801', 'rznSocial' => 'COSCO SHIPPING LINES (PERU) S.A.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20504703933', 'rznSocial' => 'Q.MAR S.A.C. AGENTE DE ADUANA', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20614442418', 'rznSocial' => 'GFA AGENCIA DE ADUANAS S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20101383700', 'rznSocial' => 'QUEIROLO M S.A. AGENCIA DE ADUANA', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20508745592', 'rznSocial' => 'ABACUS LOGISTICA INTERNACIONAL S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20506601330', 'rznSocial' => 'ADUALINK S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20551340067', 'rznSocial' => 'ADUASER OPERADOR INTERNACIONAL S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20101425127', 'rznSocial' => 'ADUATEC S.R.L. AGENTES DE ADUANA', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20516118882', 'rznSocial' => 'ANTFAB LOGISTIC EXPORT IMPORT S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20512722009', 'rznSocial' => 'AXIS GL AGENCIA DE ADUANA S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20503478421', 'rznSocial' => 'AXIS GLOBAL LOGISTICS S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20127705969', 'rznSocial' => 'BEAGLE AGENTES DE ADUANA S.A.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20492822907', 'rznSocial' => 'CAP LOGISTIC ADUANAS S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20101396861', 'rznSocial' => 'CARLOS BELLO S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20538616959', 'rznSocial' => 'CRF ADUANAS S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20480864469', 'rznSocial' => 'DESPACHOS ADUANEROS CHAVIMOCHIC S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20536743562', 'rznSocial' => 'DESPACHOS Y SERVICIOS ADUANEROS S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20100010217', 'rznSocial' => 'DP WORLD LOGISTICS S.R.L.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20254310655', 'rznSocial' => 'EL PACIFICO AGENCIA DE ADUANAS S.A.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20544847041', 'rznSocial' => 'EXPRESS FREIGHT PERU S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20517078931', 'rznSocial' => 'GRUPO PML S.A.C. AGENTES DE ADUANAS', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20518254970', 'rznSocial' => 'INFINIA OPERADOR LOGISTICO S.A.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20512943277', 'rznSocial' => 'INTERNATIONAL CUSTOMS CORPORATION S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20514497118', 'rznSocial' => 'ISCO ADUANA S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20100407362', 'rznSocial' => 'JAIME RAMIREZ MC CUBBIN S.C.R. LTDA.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20133863584', 'rznSocial' => 'JULIO ABAD S.A. AGENTES DE ADUANA', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20100118336', 'rznSocial' => 'LA HANSEATICA S.A.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20100008824', 'rznSocial' => 'LAVALLE SUITO DESPACHADORES ADUANEROS S.A.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20543731472', 'rznSocial' => 'LIP ADUANAS S.A.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20511495998', 'rznSocial' => 'LOGISTIA LOS OLIVOS S.A.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20474360955', 'rznSocial' => 'MACROMAR LOGISTICS S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20453110584', 'rznSocial' => 'MALHER OPERADOR LOGISTICO S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20606084871', 'rznSocial' => 'MARINA LOGISTICS S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20520677314', 'rznSocial' => 'MEGADUANAS PERU S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20546805973', 'rznSocial' => 'MOCAYAS LOGISTICA ADUANERA S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20253319403', 'rznSocial' => 'NEW TRANSPORT S.A.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20603266600', 'rznSocial' => 'ONE CUSTOMS LOGISTIC S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20426107041', 'rznSocial' => 'PALACIOS & ASOCIADOS AGENTES DE ADUANA S.A.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20605592652', 'rznSocial' => 'PCL ADUANAS S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20395379233', 'rznSocial' => 'PRONATUR S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20100039207', 'rznSocial' => 'RANSA COMERCIAL S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20609563045', 'rznSocial' => 'SADA OPERADOR MULTIPLE E.I.R.L.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20526325363', 'rznSocial' => 'SAN MIGUEL SERVICIOS LOGISTICOS S.C.R.L.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20504041027', 'rznSocial' => 'SAN REMO OPERADOR LOGISTICO S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20463958590', 'rznSocial' => 'SCHARFF LOGISTICA INTEGRADA S.A.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20100412790', 'rznSocial' => 'SERVICIOS EN ADUANAS S.R.L.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20609947994', 'rznSocial' => 'SL EXPRESS E.I.R.L.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20601839327', 'rznSocial' => 'SPECIALIZED REEFER LOGISTICS S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20252062077', 'rznSocial' => 'TRANSITARIO INTERNACIONAL MULTIMODAL S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20100259665', 'rznSocial' => 'UNIVERSAL ADUANERA S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20557954369', 'rznSocial' => 'WICARGO S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20601908302', 'rznSocial' => 'WORLD CUSTOMS GROUP S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20550232988', 'rznSocial' => 'WORLD INTERNATIONAL ADUANAS S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
            ['tipoDoc' => 6, 'numDoc' => '20611173122', 'rznSocial' => 'ZAREM LOGISTICS S.A.C.', 'direccion' => null, 'email' => null, 'telephone' => null],
        ];

        foreach($customBrokers as $broker){
            CustomBroker::create($broker);
        }
    }
}
