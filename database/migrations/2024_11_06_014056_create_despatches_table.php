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
        Schema::create('despatches', function (Blueprint $table) {
            $table->id();

            $table->string('version');
            $table->string('tipoDoc');
            $table->string('serie');
            /* $table->string('correlativo'); */
            $table->integer('correlativo');

            $table->dateTime('fechaEmision');

            $table->json('company');
            $table->json('destinatario');

            $table->json('envio');

            $table->json('details');

            //Paths
            $table->string('pdf_path')->nullable();
            $table->string('xml_path')->nullable();
            $table->string('cdr_path')->nullable();
            $table->string('hash')->nullable();

            //Sunat response
            $table->json('sunatResponse')->nullable();

            //Branch
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
        Schema::dropIfExists('despatches');
    }
};
