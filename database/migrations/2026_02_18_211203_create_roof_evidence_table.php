<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roof_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eir_inspection_id')->nullable()
                ->constrained('eir_inspections')->onDelete('no action')
                ->onUpdate('cascade');
            $table->tinyInteger('qty_welded_patch')->default(0)->comment('parche soldado');
            $table->tinyInteger('qty_riveted_patch')->default(0)->comment('parche remachado');
            $table->tinyInteger('qty_door_rail_patch')->default(0)->comment('parches en riel superior de la puerta');
            $table->tinyInteger('qty_front_rail_patch')->default(0)->comment('parches en el riel frontal superior');
            $table->tinyInteger('qty_welded_cut')->default(0)->comment('corte soldado');
            $table->tinyInteger('qty_sealed_cut')->default(0)->comment('corte sellado');
            $table->boolean('has_additional_damages')->default(false)->comment('Delaminaciones, Abolladuras Y  Reseñas leves');
            $table->string('photo_1', 255)->nullable();
            $table->string('photo_2', 255)->nullable();
            $table->string('photo_3', 255)->nullable();
            $table->string('photo_4', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roof_evidence');
    }
};
