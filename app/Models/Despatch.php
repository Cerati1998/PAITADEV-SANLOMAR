<?php

namespace App\Models;

use App\Observers\DespatchObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[ObservedBy(DespatchObserver::class)]
class Despatch extends Model
{
    protected $fillable = [
        'version',
        'tipoDoc',
        'serie',
        'correlativo',
        'fechaEmision',
        'company',
        'destinatario',
        'envio',
        'details',

        'pdf_path',
        'xml_path',
        'cdr_path',
        'hash',

        'sunatResponse',
        'branch_id',

        'company_id',
        'production',
    ];

    protected $casts = [
        'fechaEmision' => 'datetime',
        'company' => 'array',
        'destinatario' => 'array',
        'envio' => 'array',
        'details' => 'array',
        'sunatResponse' => 'array',
        'production' => 'boolean',
    ];

    //Mutadores y accesores
    protected function type():Attribute
    {
        return new Attribute(
            get: fn() => 'GUÍA DE REMISIÓN',
        );
    }

    protected function xml():Attribute
    {
        return new Attribute(
            get: fn() => Storage::get($this->xml_path),
        );
    }

    public function branch(){
        return $this->belongsTo(Branch::class);
    }
}
