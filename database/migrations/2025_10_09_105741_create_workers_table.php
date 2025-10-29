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
        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('tipoDoc');
            $table->foreign('tipoDoc')
                ->references('id')
                ->on('identities')
                ->onDelete('no action');
            $table->string('numDoc', 8)->unique();
            $table->string('email', 100)->nullable();
            $table->string('telefono', 15)->nullable();
            $table->enum('cargo', ['Reparador', 'Inspector', 'Asistente', 'Despachador', 'Checker', 'Soldador', 'Reefer', 'Otro'])->default('Otro');
            $table->foreignId('branch_id')->constrained()->onDelete('no action')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workers');
    }
};
