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
        Schema::create('branch_company_document', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('company_id')
                ->constrained()
                ->onDelete('cascade');

            $table->string('document_id');
            $table->foreign('document_id')
                ->references('id')
                ->on('documents');

            $table->string('serie');

            $table->integer('correlativo');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_company_document');
    }
};
