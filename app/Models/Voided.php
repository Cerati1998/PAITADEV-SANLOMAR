<?php

namespace App\Models;

use App\Observers\VoidedObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[ObservedBy(VoidedObserver::class)]
class Voided extends Model
{
    protected $fillable = [
        'correlativo',
        'fecGeneracion',
        'fecComunicacion',
        'details',

        'pdf_path',
        'xml_path',
        'cdr_path',
        'hash',

        'sunatResponse',
        'branch_id',
        'company_id',
        'production'
    ];

    protected $casts = [
        'fecGeneracion' => 'datetime',
        'fecComunicacion' => 'datetime',
        'company' => 'array',
        'details' => 'array',
        'sunatResponse' => 'array',
    ];

    //Mutadores y accesores
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

    public function branch(){
        return $this->belongsTo(Branch::class);
    }
}
