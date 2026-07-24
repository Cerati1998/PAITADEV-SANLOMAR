<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogo N.61 SUNAT: "Documentos relacionados aplicables a las GRE".
 * Distinto del catalogo N.01 (tabla `documents`, usado por las series por
 * sucursal) aunque comparten algunos codigos con significado identico
 * (01, 03, 04, 09, 12, 31): este catalogo es exclusivo para identificar el
 * documento que sustenta el traslado dentro de una guia de remision.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('related_documents', function (Blueprint $table) {
            $table->string('id')->primary(); // Codigo Catalogo 61
            $table->string('description');
            $table->timestamps();
        });

        // Se siembra aqui (y no solo en el seeder) para que una BD ya
        // migrada reciba el catalogo con un simple `php artisan migrate`,
        // sin depender de que alguien corra RelatedDocumentSeeder a mano.
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
            DB::table('related_documents')->insert(array_merge($document, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('related_documents');
    }
};
