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

        Schema::create('departments', function (Blueprint $table) {
            /* $table->id(); */
            $table->string('id')->primary();

            $table->string('name')->index();

            $table->timestamps();
        });

        Schema::create('provinces', function (Blueprint $table) {
            /* $table->id(); */
            $table->string('id')->primary();

            $table->string('name')->index();
            /* $table->foreignId('department_id')->constrained(); */
            $table->string('department_id');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');

            $table->timestamps();
        });

        Schema::create('districts', function (Blueprint $table) {
            /* $table->id(); */
            $table->string('id')->primary();

            $table->string('name')->index();
            /* $table->foreignId('province_id')->constrained()->onDelete('cascade'); */

            $table->string('province_id');
            $table->foreign('province_id')->references('id')->on('provinces')->onDelete('cascade');

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
        Schema::dropIfExists('provinces');
        Schema::dropIfExists('districts');
    }
};
