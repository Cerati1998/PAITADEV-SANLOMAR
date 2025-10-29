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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            $table->string('ruc')->unique();
            $table->string('razonSocial');
            $table->string('nombreComercial')->nullable();

            $table->string('direccion');
            $table->string('detraction_account',100)->nullable();

            $table->string('ubigeo');
            $table->foreign('ubigeo')->references('id')->on('districts')->onDelete('cascade');
            
            $table->string('sol_user')->default('MODDATOS');
            $table->string('sol_pass')->default('MODDATOS');
            $table->string('client_id')->default('test-85e5b0ae-255c-4891-a595-0b98c65c9854');
            $table->string('client_secret')->default('test-Hty/M6QshYvPgItX2P0+Kw==');

            $table->text('certificate');
            $table->string('logo_path')->nullable();

            $table->string('invoice_header')->nullable();
            $table->string('invoice_footer')->nullable();

            $table->boolean('production')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
