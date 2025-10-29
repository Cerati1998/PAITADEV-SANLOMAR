<?php

namespace App\Models;

use App\Models\works\WorkOrder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Container extends Model
{
    protected $fillable = [
        'code',
        'iso_code',
        'container_type_id',
        'reefer_technology_id',
        'reefer_machine_id',
        'tare',
        'payload',
        'max_gross',
        'max_height',
        'max_width',
        'build_year',
        'build_month',
        'own_line_id'
    ];

    protected $casts = [
        'build_month' => 'integer',
        'build_year' => 'integer',
        'tare' => 'integer',
        'payload' => 'integer',
    ];

    /* public function origin(): MorphTo
    {
        return $this->morphTo();
    } */

    public function container_type()
    {
        return $this->belongsTo(ContainerType::class);
    }

    public function reefer_technology()
    {
        return $this->belongsTo(ReeferTechnology::class);
    }

    public function machine()
    {
        return $this->belongsTo(ReeferMachine::class,'reefer_machine_id');
    }

    public function line()
    {
        return $this->belongsTo(ShippingLine::class, 'own_line_id');
    }

    public function gateInDetails(){
        return $this->hasMany(GateInDetail::class);
    }

    public function containerOperationalTraces(){
        return $this->hasMany(ContainerOperationalTrace::class);
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }
}
