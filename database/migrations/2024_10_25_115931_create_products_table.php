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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            //Codigo de producto
            $table->string('codProducto')->unique();

            //Codigo de barras
            $table->string('codBarras');

            $table->string('unidad');
            $table->foreign('unidad')
                ->references('id')
                ->on('units');

            $table->float('mtoValor');
            $table->float('mtoValorCompra')->default(0);
            $table->decimal('minimalStock',8,2)->default(0);
            
            $table->string('tipAfeIgv');
            $table->foreign('tipAfeIgv')
                ->references('id')
                ->on('affectations');
            
            //IGV
            $table->integer('porcentajeIgv');

            //ISC
            $table->string('tipSisIsc')->nullable();
            $table->float('porcentajeIsc')->nullable();
            
            //ICBPER
            $table->boolean('icbper')->default(false);
            $table->float('factorIcbper')->nullable();

            $table->string('descripcion');

            $table->foreignId('category_id')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('no action');
                
            $table->foreignId('branch_id')
                ->constrained('branches','id')
                ->onUpdate('cascade')
                ->onDelete('no action');

            $table->integer('order');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
