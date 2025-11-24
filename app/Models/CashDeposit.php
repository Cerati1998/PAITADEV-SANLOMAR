<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CashDeposit extends Model
{
    protected $fillable = [
        'arching_cash_id',
        'tipo_deposito',        // inicial, adicional, reembolso
        'monto',
        'moneda',
        'fecha_deposito',
        'metodo',               // efectivo, transferencia, cheque
        'numero_operacion',     // Número de operación bancaria si aplica
        'detalle',
        'comprobante',          // Archivo del comprobante
        'user_id',
        'branch_id',
    ];

    protected $casts = [
        'fecha_deposito' => 'datetime',
        'monto' => 'decimal:2',
    ];

    public function archingCash()
    {
        return $this->belongsTo(ArchingCash::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}