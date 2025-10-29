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
        Schema::create('voideds', function (Blueprint $table) {
            $table->id();

            $table->integer('correlativo');

            //$table->dateTime()

            $table->dateTime('fecGeneracion');
            $table->dateTime('fecComunicacion');

            $table->json('company');

            $table->json('details');

            //Paths
            $table->string('pdf_path')->nullable();
            $table->string('xml_path')->nullable();
            $table->string('cdr_path')->nullable();

            $table->string('hash')->nullable();

            $table->json('sunatResponse')->nullable();

            //Branch
            $table->foreignId('branch_id')
                ->constrained()
                ->onDelete('no action');

            $table->foreignId('company_id')
                ->constrained()
                ->onDelete('cascade');

            $table->boolean('production')
                ->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voideds');
    }
};
