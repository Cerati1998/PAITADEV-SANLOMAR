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
        Schema::create('containers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Código del contenedor, tipo: SEKU9362719
            $table->string('iso_code')->nullable(); // Código ISO como 22G1, 45R1, etc.

            $table->foreignId('container_type_id')->constrained()->onUpdate('cascade')->onDelete('no action'); // Tipo (Dry, Reefer, etc.)
            $table->foreignId('reefer_technology_id')->nullable()->constrained()->nullOnDelete(); // si aplica tecnología refrigerada
            $table->foreignId('reefer_machine_id')->nullable()->constrained()->nullOnDelete(); // si aplica maquina refrigerada

            $table->integer('tare')->nullable();
            $table->integer('payload')->nullable();
            $table->decimal('max_gross',8,2)->nullable();
            $table->decimal('max_height',6,2)->nullable();
            $table->decimal('max_width',6,2)->nullable();
            $table->year('build_year')->nullable();
            $table->unsignedTinyInteger('build_month')->nullable();
            $table->foreignId('own_line_id')->constrained('shipping_lines')->onUpdate('cascade')->onDelete('no action'); // Línea propietaria
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('containers');
    }
};
