<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catalogo N.61 SUNAT (version 2.0): "Documentos relacionados aplicables a
 * las GRE". Fuente: Anexo I de la R.S. N.000108-2026/SUNAT.
 */
class RelatedDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $documents = [
            ['id' => '01', 'description' => 'Factura'],
            ['id' => '03', 'description' => 'Boleta de Venta'],
            ['id' => '04', 'description' => 'Liquidación de Compra'],
            ['id' => '09', 'description' => 'Guía de Remisión Remitente'],
            ['id' => '12', 'description' => 'Ticket o cinta emitido por máquina registradora'],
            ['id' => '31', 'description' => 'Guía de Remisión Transportista'],
            ['id' => '48', 'description' => 'Comprobante de Operaciones – Ley N.° 29972'],
            ['id' => '49', 'description' => 'Constancia de Depósito - IVAP (Ley 28211)'],
            ['id' => '50', 'description' => 'Declaración Aduanera de Mercancías (DAM)'],
            ['id' => '52', 'description' => 'Declaración Simplificada (DS)'],
            ['id' => '80', 'description' => 'Constancia de Depósito – Detracción'],
            ['id' => '81', 'description' => 'Código de autorización emitida por el SCOP'],
            ['id' => '82', 'description' => 'Declaración jurada de mudanza'],
            ['id' => '91', 'description' => 'Manifiesto de Carga (MC)'],
            ['id' => '92', 'description' => 'Número de Cita/Orden Entrega Mercancías del Terminal Portuario'],
            ['id' => '93', 'description' => 'Documento ZOFRATACNA'],
            ['id' => '94', 'description' => 'Solicitud de Traslado - ZED'],
            ['id' => '95', 'description' => 'Solicitud de Traslado - ZOFRATACNA'],
        ];

        foreach ($documents as $document) {
            DB::table('related_documents')->updateOrInsert(
                ['id' => $document['id']],
                array_merge($document, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
