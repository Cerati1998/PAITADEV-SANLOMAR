<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transport extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'razonSocial',
        'numDoc',
        'tipoDoc',
        'direccion',
        'email',
        'phone',
        'observations',
        'branch_id',
    ];

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }
    public function drivers()
    {
        return $this->hasMany(Driver::class);
    }

    public function branch(){
        return $this->belongsTo(Branch::class);
    }

    public function identity()
    {
        return $this->belongsTo(Identity::class, 'tipoDoc');
    }
}
