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
        Schema::table('dischargues', function (Blueprint $table) {
            $table->index(['shipping_line_id', 'branch_id'], 'idx_dischargues_line_branch');
        });

        Schema::table('devolutions', function (Blueprint $table) {
            $table->index(['shipping_line_id', 'branch_id'], 'idx_devolutions_line_branch');
        });
    }

    public function down(): void
    {
        Schema::table('dischargues', function (Blueprint $table) {
            $table->dropIndex('idx_dischargues_line_branch');
        });

        Schema::table('devolutions', function (Blueprint $table) {
            $table->dropIndex('idx_devolutions_line_branch');
        });
    }
};
