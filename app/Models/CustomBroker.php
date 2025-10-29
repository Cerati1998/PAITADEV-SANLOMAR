<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomBroker extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipoDoc',
        'numDoc',
        'rznSocial',
        'address',
        'direccion',
        'phone',
        'email',
    ];

    public function identity()
    {
        return $this->belongsTo(Identity::class, 'tipoDoc');
    }

    public function devolutions(){
        return $this->hasMany(Devolution::class);
    }
}
