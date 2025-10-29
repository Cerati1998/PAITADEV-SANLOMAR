<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class GateInDetail extends Model
{
    protected $fillable = [
        'container_id',
        'vehicle_id',
        'driver_id',
        'date_in',
        'gate_number',
        'originable_id', //puede ser discharge_id o devolution_id
        'originable_type', //puede ser discharge o devolution
        'port_id',
        'ticket_in',
        'container_condition',
        'observation',
    ];

    protected $casts = [
        'date_in' => 'datetime'
    ];

    public function container()
    {
        return $this->belongsTo(Container::class);
    }
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function originable()
    {
        return $this->morphTo();
    }

    public function port()
    {
        return $this->belongsTo(Port::class);
    }

    public function containerOperationalTrace()
    {
        return $this->hasOne(ContainerOperationalTrace::class);
    }

    // Accessor para formatear la fecha
    protected function dateIn(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? Carbon::parse($value)->format('d/m/Y H:i') : null,
        );
    }

    //Accesor para mostrar la linea
    protected function linea(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->originable?->shippingLine?->name
        );
    }
}
