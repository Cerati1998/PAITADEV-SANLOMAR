<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('description', 100);
            $table->timestamps();
        });

        Schema::create('operations', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('description', 200);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('document_operation', function (Blueprint $table) {
            $table->id();
            $table->string('document_id', 50);
            $table->foreign('document_id')
                ->references('id')
                ->on('documents')
                ->onDelete('cascade');

            $table->string('operation_id', 50);
            $table->foreign('operation_id')
                ->references('id')
                ->on('operations')
                ->onDelete('cascade');

            $table->timestamps();
        });

        Schema::create('identities', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('description', 200);
            $table->timestamps();
        });

        Schema::create('currencies', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('symbol', 10);
            $table->string('description', 100);
            $table->timestamps();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('description', 200);
            $table->timestamps();
        });

        Schema::create('affectations', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('description', 200);
            $table->boolean('igv');
            $table->boolean('free');
            $table->timestamps();
        });

        Schema::create('type_credit_notes', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('description', 250);
            $table->timestamps();
        });

        Schema::create('type_debit_notes', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('description', 200);
            $table->timestamps();
        });

        Schema::create('detractions', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('description', 200);
            $table->decimal('percent', 5, 2);
            $table->timestamps();
        });

        Schema::create('perceptions', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('description', 200);
            $table->decimal('porcentaje', 5, 3);
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('description', 250);
            $table->timestamps();
        });

        Schema::create('reason_transfers', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('description', 250);
            $table->timestamps();
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('A3', 3)->unique();
            $table->string('description', 200);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_operation');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('operations');
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
