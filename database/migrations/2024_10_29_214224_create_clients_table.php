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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            $table->string('tipoDoc');
            $table->foreign('tipoDoc')
                ->references('id')
                ->on('identities')
                ->onDelete('cascade');

            $table->string('numDoc')
                ->nullable()
                ->index();

            $table->string('rznSocial')
                ->index();

            $table->string('direccion')
                ->nullable();

            $table->string('email')->nullable();

            $table->string('telephone')->nullable();

            $table->foreignId('branch_id')
                ->constrained()
                ->onDelete('no action');
            $table->foreignId('company_id')
                ->constrained()
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
