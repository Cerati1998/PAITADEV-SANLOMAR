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
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200)->unique();
            $table->text('description');
            $table->string('base_url', 255)->default(url('/integrations/devolutions'));
            $table->timestamps();
        });

        Schema::create('branch_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->onDelete('no action');
            $table->foreignId('integration_id')
                ->constrained('integrations')
                ->onDelete('no action');
            $table->string('api_token', 255)->unique();
            $table->boolean('active')->default(1);
            $table->foreignId('created_by')
            ->constrained('users','id')
            ->onDelete('no action');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
