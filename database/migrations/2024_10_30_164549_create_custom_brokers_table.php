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
        Schema::create('custom_brokers', function (Blueprint $table) {
            $table->id();
            $table->string('tipoDoc');
            $table->foreign('tipoDoc')
                ->references('id')
                ->on('identities')
                ->onDelete('cascade');

            $table->string('numDoc')
                ->index();

            $table->string('rznSocial')
                ->index();

            $table->string('direccion')
                ->nullable();

            $table->string('email')->nullable();

            $table->string('telephone')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_brokers');
    }
};
