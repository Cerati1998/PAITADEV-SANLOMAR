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
        Schema::create('leak_tests', function (Blueprint $table) {
            $table->id();
            $table->string('serie', 50);
            $table->integer('correlativo');
            $table->unique(['serie', 'correlativo']);
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->decimal('hours',4,2)->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('no action');
            $table->foreignId('trace_id')->constrained('container_operational_traces')->onDelete('no action');
            $table->foreignId('inspector_id')->constrained('users')->onDelete('no action');
            $table->timestamps();
        });

        Schema::create('leak_test_materials', function (Blueprint $table) {
            $table->id();
            $table->string('material_description', 100);
            $table->foreignId('leak_test_id')->constrained('leak_tests')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leak_test_materials');
        Schema::dropIfExists('leak_tests');
    }
};
