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
        Schema::create('documents', function (Blueprint $table) {
            $table->string('id')->primary();
            
            $table->string('description')->index();

            $table->timestamps();
        });


        Schema::create('operations', function (Blueprint $table) {
            $table->string('id')->primary();
            
            $table->string('description')->index();
            $table->boolean('active')->default(true);

            $table->timestamps();
        });

        Schema::create('document_operation', function (Blueprint $table) {
            $table->id();

            $table->string('document_id');
            $table->foreign('document_id')
                ->references('id')
                ->on('documents');
            
            $table->string('operation_id');
            $table->foreign('operation_id')
                ->references('id')
                ->on('operations');

            $table->timestamps();
        });

        Schema::create('identities', function (Blueprint $table) {
            $table->string('id')->primary();
            
            $table->string('description')->index();

            $table->timestamps();
        });

        Schema::create('currencies', function (Blueprint $table) {
            
            $table->string('id')->primary();
            $table->string('symbol');
            $table->string('description');

            $table->timestamps();
        });

        Schema::create('units', function (Blueprint $table) {
            
            $table->string('id')->primary();
            $table->string('description')->index();

            $table->timestamps();
        });

        Schema::create('affectations', function (Blueprint $table) {
            $table->string('id')->primary();

            $table->string('description')->index();
            $table->boolean('igv');
            $table->boolean('free');

            $table->timestamps();
        });

        Schema::create('type_credit_notes', function (Blueprint $table) {
            $table->string('id')->primary();

            $table->string('description')->index();

            $table->timestamps();
        });

        Schema::create('type_debit_notes', function (Blueprint $table) {
            $table->string('id')->primary();

            $table->string('description')->index();

            $table->timestamps();
        });

        Schema::create('detractions', function (Blueprint $table) {
            $table->string('id')->primary();

            $table->string('description')->index();

            $table->decimal('percent', 5, 2);

            $table->timestamps();
        });

        Schema::create('perceptions', function (Blueprint $table) {
            $table->string('id')->primary();

            $table->string('description')->index();

            $table->decimal('porcentaje', 5, 3);

            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->string('id')->primary();

            $table->string('description')->index();

            $table->timestamps();
        });

        Schema::create('reason_transfers', function (Blueprint $table) {
            $table->string('id')->primary();

            $table->string('description')->index();

            $table->timestamps();
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('A3')->unique();
            $table->string('description')->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('operations');
        Schema::dropIfExists('document_operation');
        Schema::dropIfExists('identities');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('units');
        Schema::dropIfExists('affectations');
        Schema::dropIfExists('type_credit_notes');
        Schema::dropIfExists('type_debit_notes');
        Schema::dropIfExists('detractions');
        Schema::dropIfExists('perceptions');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('reason_transfers');            
        Schema::dropIfExists('countries');

    }
};
