<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migración de retrofit para bases de datos ya existentes (no pasa por los
 * seeders iniciales): agrega el catálogo de documento "31" (Guía de Remisión
 * Transportista) y crea la serie por defecto "V001" para cada sucursal que
 * ya tenga configurada una serie de Guía de Remisión Remitente ("09"), sin
 * duplicar si ya existiera.
 */
return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('documents')->where('id', '31')->exists();

        if (!$exists) {
            DB::table('documents')->insert([
                'id' => '31',
                'description' => 'Guía de Remisión Transportista',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $branchesWithRemitente = DB::table('branch_company_document')
            ->where('document_id', '09')
            ->get(['branch_id', 'company_id']);

        foreach ($branchesWithRemitente as $row) {
            $hasTransportista = DB::table('branch_company_document')
                ->where('branch_id', $row->branch_id)
                ->where('company_id', $row->company_id)
                ->where('document_id', '31')
                ->exists();

            if (!$hasTransportista) {
                DB::table('branch_company_document')->insert([
                    'branch_id' => $row->branch_id,
                    'company_id' => $row->company_id,
                    'document_id' => '31',
                    'serie' => 'V001',
                    'correlativo' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('branch_company_document')
            ->where('document_id', '31')
            ->where('serie', 'V001')
            ->delete();

        DB::table('documents')->where('id', '31')->delete();
    }
};
