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
        Schema::table('voideds', function (Blueprint $table) {
            $table->string('sunat_ticket')->nullable()->after('hash');
            $table->string('sunat_status')->nullable()->after('sunat_ticket');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voideds', function (Blueprint $table) {
            $table->dropColumn(['sunat_ticket', 'sunat_status']);
        });
    }
};
