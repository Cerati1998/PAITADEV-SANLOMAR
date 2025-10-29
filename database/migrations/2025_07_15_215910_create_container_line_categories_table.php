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
        Schema::create('container_line_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->string('description', 200)->nullable();
            $table->foreignId('shipping_line_id')->constrained('shipping_lines')->onDelete('no action');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('container_line_categories');
    }
};
