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
        Schema::create('shipping_lines', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->string('name', 200);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('vessels', function (Blueprint $table) {
            $table->id();
            $table->string('imo_number', 15)->nullable();
            $table->string('name', 200);
            $table->enum('type', ['container', 'bulk', 'tanker', 'other'])->default('container');
            $table->foreignId('shipping_line_id')
                ->constrained('shipping_lines')
                ->references('id')
                ->onDelete('cascade');
            $table->Integer('pallets')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('ports', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5)->unique();
            $table->string('name', 200);
            $table->string('country_code', 3)->nullable(); //Optional, for findOrCreate in load discharge;
            $table->geometry('location')->nullable(); // Optional, for geographical coordinates
            $table->softDeletes(); // For soft delete functionality
            $table->timestamps();
        });

        Schema::create('container_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 6)->unique();
            $table->string('iso_code', 4)->unique();
            $table->string('description', 100);
            $table->boolean('is_reefer')->default(false);
            $table->decimal('length', 8, 2)->nullable(); // Length in meters
            $table->decimal('width', 8, 2)->nullable(); // Width in meters
            $table->decimal('height', 8, 2)->nullable(); // Height in meters
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('reefer_machines', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_lines');
        Schema::dropIfExists('vessels');
        Schema::dropIfExists('ports');
        Schema::dropIfExists('container_types');
        Schema::dropIfExists('reefer_machines');
    }
};
