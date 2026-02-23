<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('eir_inspections', function (Blueprint $table) {
            $table->id();
            $table->dateTime('started_at');
            $table->dateTime('closed_at')->nullable();
            $table->string('serie', 50);
            $table->integer('correlativo');
            $table->unique(['serie', 'correlativo']);
            $table->boolean('normal_wash')->default(0);
            $table->boolean('quimic_wash')->default(0);
            $table->string('reason_quimic_wash', 100)->nullable();
            $table->boolean('special_wash')->default(0);
            $table->string('reason_special_wash', 100)->nullable();
            $table->string('container_category', 50)->nullable();
            $table->string('observations', 200)->nullable();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('no action');
            $table->foreignId('inspector_id')->constrained('users')->onDelete('no action');
            $table->foreignId('trace_id')->constrained('container_operational_traces')->onUpdate('cascade')
                ->onDelete('no action');
            $table->foreignId('closed_by')->nullable()->constrained('users')->onDelete('no action');
            $table->foreignId('stimated_by')->nullable()->constrained('users')->onDelete('no action');
            $table->dateTime('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->onDelete('no action');
            $table->boolean('fast_repair')->default(0);
            $table->boolean('normal_repair')->default(0);
            $table->boolean('quoted_repair')->default(0);
            $table->string('path_photo_1', 200)->nullable();
            $table->string('path_photo_2', 200)->nullable();
            $table->string('path_photo_3', 200)->nullable();
            $table->string('path_photo_4', 200)->nullable();
            $table->string('status', 50)->default('borrador');
            $table->timestamps();
        });


        Schema::create('eir_inspection_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eir_inspection_id')->constrained('eir_inspections')->onDelete('cascade');
            $table->foreignId('location_id')->constrained('locations')->onDelete('no action');
            ;
            $table->foreignId('damage_id')->constrained('damages')->onDelete('no action');
            ;
            $table->foreignId('component_id')->constrained('components')->onDelete('no action');
            ;
            $table->foreignId('method_id')->constrained('methods')->onDelete('no action');
            $table->integer('qty')->nullable();
            $table->integer('length')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('comments', 100)->nullable();
            $table->boolean('approved')->default(true);
            $table->timestamps();
        });

        Schema::create('eir_inspection_detail_photos', function (Blueprint $table) {
            $table->id();
            $table->string('path', 255);
            $table->foreignId('eir_inspection_detail_id')
                ->constrained('eir_inspection_details')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eir_inspection_detail_images');
        Schema::dropIfExists('eir_inspection_details');
        Schema::dropIfExists('eir_inspections');
    }
};
