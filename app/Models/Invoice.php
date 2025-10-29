<?php

namespace App\Models;

use App\Observers\InvoiceObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[ObservedBy(InvoiceObserver::class)]
class Invoice extends Model
{
    protected $fillable = [
        'ublVersion',
        'tipoOperacion',
        'tipoDoc',
        'serie',
        'correlativo',
        'fechaEmision',
        'fecVencimiento',
        'formaPago',
        'cuotas',
        'tipoCambio',
        'tipoMoneda',
        
        'tipDocAfectado',
        'numDocfectado',
        'codMotivo',
        'desMotivo',

        'guias',

        'company',
        'client',
        
        'mtoOperGravadas',
        'mtoOperExoneradas',
        'mtoOperInafectas',
        'mtoOperExportacion',
        'mtoOperGratuitas',
        'mtoBaseIvap',
        'mtoBaseIsc',

        'mtoIGV',
        'mtoIGVGratuitas',
        'mtoIvap',
        'icbper',
        'mtoISC',
        'totalImpuestos',

        'valorVenta',
        'subTotal',
        'redondeo',
        'mtoImpVenta',

        'descuentos',
        'sumOtrosDescuentos',

        'anticipos',
        'totalAnticipos',

        'detraccion',

        'perception',

        'atributos',

        'details',
        'legends',

        'pdf_path',
        'xml_path',
        'cdr_path',
        'hash',

        'sunatResponse',

        'voided',
        'branch_id',

        'company_id',

        'production',

        'tipo_cambio',
    ];

    protected $casts = [
        'fechaEmision' => 'datetime',
        'fecVencimiento' => 'datetime',
        'formaPago' => 'array',
        'cuotas' => 'array',
        'guias' => 'array',
        'company' => 'array',
        'client' => 'array',
        'descuentos' => 'array',
        'anticipos' => 'array',
        'detraccion' => 'array',
        'perception' => 'array',
        'atributos' => 'array',
        'details' => 'array',
        'legends' => 'array',
        'sunatResponse' => 'array',
        'production' => 'boolean',
    ];

    //Mutadores y accesores
    protected function type():Attribute
    {
        return new Attribute(
            get: fn() => $this->document->description,
        );
    }

    protected function xml():Attribute
    {
        return new Attribute(
            get: fn() => Storage::get($this->xml_path),
        );
    }

    protected function cdr():Attribute
    {
        return new Attribute(
            get: fn() => Storage::get($this->cdr_path),
        );
    }

    protected function fecVencimiento():Attribute
    {
        return new Attribute(
            get: fn($value) => $value ?? $this->fechaEmision,
        );
    }

    //Relaciones
    public function document()
    {
        return $this->belongsTo(Document::class, 'tipoDoc');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'tipoMoneda');
    }

    public function branch(){
        return $this->belongsTo(Branch::class);
    }
}
