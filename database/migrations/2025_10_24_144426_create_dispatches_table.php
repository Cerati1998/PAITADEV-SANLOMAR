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
        Schema::create('dispatches', function (Blueprint $table) {
            $table->id();
            $table->dateTime('wash_date');
            $table->dateTime('dispatch_date')->nullable();
            $table->string('reserva_booking', 50);
            $table->boolean('oxalic_wash')->default(false);
            $table->string('eir_gate_out', 50)->nullable();
            $table->foreignId('vessel_id')->nullable()->constrained('vessels')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('trace_id')->constrained('container_operational_traces')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('shipper_id')->nullable()->constrained('clients', 'id')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('washer_id')->nullable()->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('dispatcher_id')->nullable()->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('dispatch_photos', function (Blueprint $table) {
            $table->id();
            $table->string('path', 200);
            $table->string('type', 50)->comment('wash|dispatch');
            $table->foreignId('dispatch_id')->constrained('dispatches')->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispatch_photos');
        Schema::dropIfExists('dispatches');
    }
};
