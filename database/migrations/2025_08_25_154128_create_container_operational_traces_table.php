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
        Schema::create('container_operational_traces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gate_in_detail_id')
                ->constrained('gate_in_details')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreignId('container_id')
                ->constrained('containers')
                ->onUpdate('cascade')
                ->onDelete('no action');

            $table->string('status_box', 50)->default('pending');
            $table->date('date_rep_box')->nullable();
            $table->string('status_machine', 50)->nullable()->default('pending');
            $table->date('date_rep_machine')->nullable();
            $table->datetime('date_final_status')->nullable();
            
            $table->foreignId('final_status_user')
            ->nullable()
            ->constrained('users','id')
            ->onUpdate('cascade')
            ->onDelete('no action');
            
            $table->tinyInteger('status')->default(1);
            $table->string('observation', 100)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('container_operational_traces');
    }
};
