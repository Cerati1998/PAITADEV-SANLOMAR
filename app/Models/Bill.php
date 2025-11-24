<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    protected $fillable = [
        'correlativo',          // NUEVO: Número correlativo del gasto
        'fecha_emision',
        'fecha_registro',       // NUEVO: Fecha en que se registró el gasto
        'tipo_comprobante',     // NUEVO: factura, boleta, recibo, etc.
        'serie_comprobante',    // NUEVO: Serie del comprobante
        'numero_comprobante',   // NUEVO: Número del comprobante
        'product_id',
        'provider_id',
        'category_id',          // NUEVO: Categoría del gasto
        'monto',
        'moneda',               // NUEVO: PEN, USD, etc.
        'tipo_cambio',          // NUEVO: Si es necesario convertir
        'comprobante',          // Ruta del archivo
        'detalle',
        'observaciones',        // NUEVO: Observaciones adicionales
        'estado',               // NUEVO: pendiente, aprobado, rechazado, anulado
        'user_id',              // Usuario que registró
        'approved_by',          // NUEVO: Usuario que aprobó
        'arching_cash_id',
        'branch_id',            // NUEVO: Sucursal
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_registro' => 'datetime',
        'monto' => 'decimal:2',
        'tipo_cambio' => 'decimal:4',
    ];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function archingCash()
    {
        return $this->belongsTo(ArchingCash::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Scopes
    public function scopeByArching($query, $archingId)
    {
        return $query->where('arching_cash_id', $archingId);
    }

    public function scopeApproved($query)
    {
        return $query->where('estado', 'aprobado');
    }
}