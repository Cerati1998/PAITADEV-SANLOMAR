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
        Schema::create('reefer_technologies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            // Temperatura (°C)
            $table->float('temperature_min')->nullable();
            $table->float('temperature_max')->nullable();

            // Ventilación (CBM/H)
            $table->integer('ventilation_min')->nullable();
            $table->integer('ventilation_max')->nullable();

            // Humedad (%)
            $table->float('humidity_min')->nullable();
            $table->float('humidity_max')->nullable();

            // Atmósfera O2 (%)
            $table->float('atmosphere_o2_min')->nullable();
            $table->float('atmosphere_o2_max')->nullable();

            // Atmósfera CO2 (%)
            $table->float('atmosphere_co2_min')->nullable();
            $table->float('atmosphere_co2_max')->nullable();

            // Información de uso u observaciones
            $table->text('usage')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reefer_technologies');
    }
};
