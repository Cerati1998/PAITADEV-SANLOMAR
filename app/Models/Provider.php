<?php

namespace App\Models;

use App\Models\works\WorkOrder;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $fillable = [
        'tipoDoc',
        'numDoc',
        'rznSocial',
        'address',
        'direccion',
        'phone',
        'email',
        'cuentaCte',
        'categoria',
        'detalleServicio',
        'branch_id',
        'company_id'
    ];

    public function identity()
    {
        return $this->belongsTo(Identity::class, 'tipoDoc');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }
}
