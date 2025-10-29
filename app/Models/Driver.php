<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = [
        'nombres',
        'apellidos',
        'tipoDoc',
        'numDoc',
        'brevete',
        'direccion',
        'telefono',
        'transport_id',
    ];

    public function transport()
    {
        return $this->belongsTo(Transport::class);
    }

      public function identity()
    {
        return $this->belongsTo(Identity::class, 'tipoDoc');
    }

    public function gateInDetails(){
        return $this->hasMany(GateInDetail::class);
    }
}
