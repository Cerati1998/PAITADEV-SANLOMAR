<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ArchingCash extends Model
{
    protected $fillable = [
        'correlativo',
        'fecha_inicio',
        'fecha_fin',
        'monto_inicial',
        'monto_final',
        'saldo_heredado',      // NUEVO: Saldo de la caja anterior
        'deposito_inicial',     // NUEVO: Monto depositado en esta apertura
        'total_salidas',
        'total_ingresos',       // NUEVO: Por si hay ingresos/reembolsos
        'estado',               // abierta, cerrada
        'observaciones',        // NUEVO: Notas sobre la apertura/cierre
        'opened_by',
        'closed_by',
        'branch_id',
        'previous_arching_id'   // NUEVO: Referencia a la caja anterior
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'monto_inicial' => 'decimal:2',
        'monto_final' => 'decimal:2',
        'saldo_heredado' => 'decimal:2',
        'deposito_inicial' => 'decimal:2',
        'total_salidas' => 'decimal:2',
        'total_ingresos' => 'decimal:2',
    ];

    // Relaciones existentes
    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // NUEVAS RELACIONES
    public function previousArching()
    {
        return $this->belongsTo(ArchingCash::class, 'previous_arching_id');
    }

    public function nextArching()
    {
        return $this->hasOne(ArchingCash::class, 'previous_arching_id');
    }

    public function bills()
    {
        return $this->hasMany(Bill::class, 'arching_cash_id');
    }

    public function deposits()
    {
        return $this->hasMany(CashDeposit::class, 'arching_cash_id');
    }

    // MÉTODOS ÚTILES
    public function isOpen()
    {
        return $this->estado === 'abierta';
    }

    public function getSaldoDisponibleAttribute()
    {
        return $this->monto_inicial - $this->total_salidas + $this->total_ingresos;
    }

    public function scopeOpen($query)
    {
        return $query->where('estado', 'abierta');
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}