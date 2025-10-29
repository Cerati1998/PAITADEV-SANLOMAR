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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->string('ublVersion')
                ->default('2.1');

            $table->string('tipoOperacion')->nullable();
            $table->foreign('tipoOperacion')
                ->references('id')
                ->on('operations');

            $table->string('tipoDoc');
            $table->foreign('tipoDoc')
                ->references('id')
                ->on('documents');

            $table->string('serie');
            $table->integer('correlativo');

            $table->dateTime('fechaEmision');
            $table->dateTime('fecVencimiento')->nullable();

            $table->json('formaPago')->nullable();

            $table->json('cuotas')->nullable();

            $table->string('tipoMoneda');
            $table->decimal('tipoCambio',5,3)->default(0);

            //Nota de credito / debito
            $table->string('tipDocAfectado')->nullable();
            $table->string('numDocfectado')->nullable();
            $table->string('codMotivo')->nullable();
            $table->string('desMotivo')->nullable();

            $table->json('guias')->nullable();

            $table->json('company');
            $table->json('client');

            //Montos
            $table->float('mtoOperGravadas');
            $table->float('mtoOperExoneradas');
            $table->float('mtoOperInafectas');
            $table->float('mtoOperExportacion');
            $table->float('mtoOperGratuitas');
            $table->float('mtoBaseIvap');
            $table->float('mtoBaseIsc');

            //Impuestos
            $table->float('mtoIGV')->default(0);
            $table->float('mtoIGVGratuitas')->default(0);
            $table->float('mtoIvap')->default(0);
            $table->float('icbper')->default(0);
            $table->float('mtoISC')->default(0);
            $table->float('totalImpuestos')->default(0);

            //Totales
            $table->float('valorVenta')->default(0);
            $table->float('subTotal')->default(0);
            $table->float('redondeo')->default(0);
            $table->float('mtoImpVenta')->default(0);

            //Descuentos
            $table->json('descuentos')->nullable();
            $table->float('sumOtrosDescuentos')->default(0);

            //Anticipos
            $table->json('anticipos')->nullable();
            $table->float('totalAnticipos')->default(0);

            //Detracción
            $table->json('detraccion')->nullable();

            //Percepcion
            $table->json('perception')->nullable();

            $table->json('atributos')->nullable();

            $table->json('details');
            $table->json('legends');

            //Paths
            $table->string('pdf_path')->nullable();
            $table->string('xml_path')->nullable();
            $table->string('cdr_path')->nullable();

            $table->string('hash')->nullable();

            //Sunat response
            $table->json('sunatResponse')->nullable();

            $table->boolean('voided')
                ->default(false);

            //Branch
            $table->foreignId('branch_id')
                ->constrained()
                ->onDelete('no action');

            //Company
            $table->foreignId('company_id')
                ->constrained()
                ->onDelete('cascade');

            $table->boolean('production')
                ->default(false);

            $table->float('tipo_cambio')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
