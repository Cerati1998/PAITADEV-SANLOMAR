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
        Schema::create('arching_cashes', function (Blueprint $table) {
            $table->id();
            $table->string('correlativo')->unique();
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin')->nullable();

            // Campos de montos
            $table->decimal('saldo_heredado', 10, 2)->default(0);
            $table->decimal('deposito_inicial', 10, 2)->default(0);
            $table->decimal('monto_inicial', 10, 2); // saldo_heredado + deposito_inicial
            $table->decimal('monto_final', 10, 2)->nullable();
            $table->decimal('total_salidas', 10, 2)->default(0);
            $table->decimal('total_ingresos', 10, 2)->default(0);

            $table->enum('estado', ['abierta', 'cerrada'])->default('abierta');
            $table->text('observaciones')->nullable();

            // Relaciones
            $table->foreignId('opened_by')->constrained('users');
            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('previous_arching_id')->nullable()->constrained('arching_cashes');

            $table->timestamps();

            // Índices
            $table->index('estado');
            $table->index('branch_id');
            $table->index(['branch_id', 'estado']);
        });

        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('correlativo')->unique();

            // Fechas
            $table->date('fecha_emision');
            $table->dateTime('fecha_registro')->useCurrent();

            // Comprobante
            $table->string('tipo_comprobante'); // factura, boleta, recibo
            $table->string('numero_comprobante')->nullable();
            
            // Montos
            $table->decimal('monto', 10, 2);
            $table->string('moneda', 3)->default('PEN');
            $table->decimal('tipo_cambio', 8, 4)->default(1);

            // Descripción
            $table->text('detalle');
            $table->text('observaciones')->nullable();

            // Relaciones
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->foreignId('provider_id')->nullable()->constrained('providers');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('arching_cash_id')->constrained('arching_cashes');
            $table->foreignId('branch_id')->constrained('branches');

            $table->timestamps();

            // Índices
            $table->index('fecha_emision');
            $table->index('arching_cash_id');
        });

        Schema::create('cash_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arching_cash_id')->constrained('arching_cashes');

            $table->enum('tipo_deposito', ['inicial', 'adicional', 'reembolso']);
            $table->decimal('monto', 10, 2);
            $table->string('moneda', 3)->default('PEN');
            $table->dateTime('fecha_deposito');

            $table->string('metodo'); // efectivo, transferencia, cheque
            $table->string('numero_operacion')->nullable();
            $table->text('detalle')->nullable();
            $table->string('comprobante')->nullable();

            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('branch_id')->constrained('branches');

            $table->timestamps();

            $table->index('arching_cash_id');
            $table->index('tipo_deposito');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_deposits');
        Schema::dropIfExists('bills');
        Schema::dropIfExists('arching_cashes');
    }
};
