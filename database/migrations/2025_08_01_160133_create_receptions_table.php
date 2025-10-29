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
        Schema::create('dischargues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_line_id')
                ->constrained('shipping_lines')
                ->onUpdate('cascade')
                ->onDelete('no action');

            $table->foreignId('vessel_id')
                ->constrained('vessels')
                ->onUpdate('no action')
                ->onDelete('no action');

            $table->string('voyage', 50)->nullable();
            $table->string('bl_number', 50)->nullable();
            $table->date('eta_date');
            $table->string('week', 50)->nullable();
            $table->string('manifiest_number', 50)->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();

            $table->foreignId('created_by')
                ->constrained('users', 'id')
                ->onUpdate('no action')
                ->onDelete('no action');

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->onUpdate('cascade')
                ->onDelete('no action');

            $table->foreignId('anulated_by')
                ->nullable()
                ->constrained('users', 'id')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->string('anulated_reason', 100)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('devolutions', function (Blueprint $table) {
            $table->id();
            $table->date('returned_date');

            $table->foreignId('client_id')
                ->constrained('clients')
                ->onUpdate('cascade')
                ->onDelete('no action');

            $table->foreignId('custom_broker_id')
                ->constrained('custom_brokers')
                ->onUpdate('cascade')
                ->onDelete('no action');

            $table->string('bl_number', 50)->nullable();
            $table->string('memo_number', 50)->nullable();

            $table->foreignId('shipping_line_id')
                ->constrained('shipping_lines')
                ->onUpdate('cascade')
                ->onDelete('no action');

            $table->foreignId('vessel_id')
                ->constrained('vessels')
                ->onUpdate('no action')
                ->onDelete('no action');

            $table->string('week', 50)->nullable();
            $table->string('voyage', 50)->nullable();

            $table->foreignId('created_by')
                ->constrained('users', 'id')
                ->onUpdate('no action')
                ->onDelete('no action');

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->onUpdate('cascade')
                ->onDelete('no action');

            $table->foreignId('anulated_by')
                ->nullable()
                ->constrained('users', 'id')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->string('regimen', 50);
            $table->string('anulated_reason', 100)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('gate_in_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('container_id')
                ->constrained('containers')
                ->onUpdate('cascade')
                ->onDelete('no action');

            $table->foreignId('vehicle_id')
                ->nullable()
                ->constrained('vehicles', 'id')
                ->onUpdate('cascade')
                ->onDelete('no action');

            $table->foreignId('driver_id')
                ->nullable()
                ->constrained('drivers', 'id')
                ->onUpdate('cascade')
                ->onDelete('no action');

            $table->dateTime('date_in')->nullable();
            $table->string('gate_number')->nullable();
            $table->morphs('originable');

            $table->foreignId('port_id')
                ->constrained('ports', 'id')
                ->onUpdate('cascade')
                ->onDelete('no action');

            $table->string('ticket_in', 50)->nullable();
            $table->enum('container_condition', ['MTY', 'FC']);
            $table->string('observation', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devolutions');
        Schema::dropIfExists('dischargues');
        Schema::dropIfExists('gate_in_details');
    }
};
