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
        // Tabla transport (Transportistas)
        Schema::create('transports', function (Blueprint $table) {
            $table->id();
            $table->string('razonSocial', 255);
            $table->string('numDoc', 45)->index();
            $table->string('tipoDoc');
            $table->foreign('tipoDoc')
                ->references('id')
                ->on('identities')
                ->onDelete('cascade')
                ->onUpdate('cascade'); // FK a documents
            $table->string('direccion', 255);
            $table->string('email', 200)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('observations', 200)->nullable();
            $table->foreignId('branch_id')
                ->constrained()
                ->onDelete('no action')
                ->onUpdate('cascade');
            $table->softDeletes();    
            $table->timestamps();

        });

        // Tabla drivers (Conductores)
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('nombres', 200);
            $table->string('apellidos', 255);
            $table->string('tipoDoc');
            $table->foreign('tipoDoc')
                ->references('id')
                ->on('identities')
                ->onDelete('cascade'); // FK a documents
            $table->string('numDoc', 50);
            $table->string('brevete', 50);
            $table->string('direccion', 255)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->foreignId('transport_id')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->timestamps();
        });

        // Tabla vehicles (Unidades)
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->nullable();
            $table->string('brand', 200)->nullable();
            $table->string('model', 200)->nullable();
            $table->string('category', 45);
            $table->string('type', 200);
            $table->string('color', 100)->nullable();
            $table->foreignId('transport_id')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('no action'); // FK a transport
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('transports');
    }
};
