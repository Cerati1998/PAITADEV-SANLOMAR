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
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->comment('box,mach,others');
            $table->string('serie', 50);
            $table->integer('correlativo');
            $table->unique(['serie', 'correlativo']);
            $table->string('estimate_number')->nullable();
            $table->date('estimate_date')->nullable();
            $table->foreignId('eir_inspection_id')->nullable()
                ->constrained('eir_inspections')->onDelete('no action')
                ->onUpdate('cascade');
            $table->dateTime('started_at');
            $table->foreignId('opened_by')->constrained('users', 'id')->onDelete('no action')
                ->onUpdate('cascade');
            $table->foreignId('container_id')->nullable()
                ->constrained('containers')->onDelete('no action')
                ->onUpdate('cascade');
            $table->foreignId('provider_id')->nullable()
                ->constrained('providers')->onDelete('no action')
                ->onUpdate('cascade');
            $table->dateTime('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users', 'id')->onDelete('no action')
                ->onUpdate('cascade');
            $table->string('comments')->nullable();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('no action')
                ->onUpdate('cascade');
            $table->string('status', 100)->default('borrador');
            $table->softDeletes();
            $table->timestamps();
        });

        //tabla de labors de orden de trabajo
        Schema::create('work_order_labors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders', 'id')->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('eir_inspection_detail_id')->nullable()->constrained('eir_inspection_details')
                ->onDelete('no action')->onUpdate('cascade');
            $table->integer('qty')->nullable();
            $table->string('description', 100)->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->decimal('hours', 7, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('labor_worker', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_labor_id')->constrained('work_order_labors', 'id')->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('worker_id')->nullable()->constrained('workers')
                ->onDelete('no action')->onUpdate('cascade');
            $table->string('task_description', 200)->nullable();
            $table->boolean('main_worker')->default(0);
            $table->timestamps();
        });
        Schema::create('work_order_labor_images', function (Blueprint $table) {
            $table->id();
            $table->string('path', 255);
            $table->foreignId('work_order_labor_id')
                ->constrained('work_order_labors')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('labor_worker');
        Schema::dropIfExists('work_order_labor_images');
        Schema::dropIfExists('work_order_labors');
        Schema::dropIfExists('work_orders');
    }
};
