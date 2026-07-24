<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('despatch_transportistas', function (Blueprint $table) {
            $table->id();

            $table->string('version');
            $table->string('tipoDoc'); // Catalogo 01 - siempre "31"
            $table->string('serie');
            $table->integer('correlativo');

            $table->dateTime('fechaEmision');

            // Emisor de la GRE-Transportista (empresa/company logueada)
            $table->json('company');

            // Destinatario final de los bienes (mismo concepto que en la GRE-Remitente)
            $table->json('destinatario')->nullable();

            // Datos del remitente que entrega los bienes al transportista.
            // UBL: /DespatchAdvice/cac:Shipment/cac:Delivery/cac:Despatch/cac:DespatchParty
            $table->json('remitente');

            // Empresa a la que el transportista subcontrata el servicio (opcional).
            // UBL: /DespatchAdvice/cac:Shipment/cac:Consignment/cac:LogisticsOperatorParty
            $table->json('subcontratador')->nullable();

            // Documento emitido por el remitente que sustenta el traslado
            // (Catalogo 61: 09 = Guia Remitente, 92 = Cita/Orden Terminal Portuario, etc.)
            $table->json('documentoRelacionado')->nullable();

            $table->json('envio');

            $table->json('details');

            // Paths
            $table->string('pdf_path')->nullable();
            $table->string('xml_path')->nullable();
            $table->string('cdr_path')->nullable();
            $table->string('sunat_ticket')->nullable();
            $table->string('hash')->nullable();

            // Sunat response
            $table->json('sunatResponse')->nullable();

            // Branch
            $table->foreignId('branch_id')
                ->constrained()
                ->onDelete('no action');

            $table->foreignId('company_id')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->boolean('production');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('despatch_transportistas');
    }
};
