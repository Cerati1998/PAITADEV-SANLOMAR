<?php

namespace App\Models\inspections;

use App\Models\Component;
use App\Models\Damage;
use App\Models\Location;
use App\Models\Method;
use App\Models\works\WorkOrderLabor;
use App\Observers\EirInspectionDetailObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(EirInspectionDetailObserver::class)]
class EirInspectionDetail extends Model
{
    protected $fillable = [
        'eir_inspection_id',
        'location_id',
        'damage_id',
        'component_id',
        'method_id',
        'qty',
        'length',
        'width',
        'height',
        'approved',
        'comments',
    ];

    protected $casts = [
        'qty' => 'integer',
        'length' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'approved' => 'boolean'
    ];

    public function inspectionHeader()
    {
        return $this->belongsTo(EirInspection::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function damage()
    {
        return $this->belongsTo(Damage::class);
    }

    public function component()
    {
        return $this->belongsTo(Component::class);
    }

    public function method()
    {
        return $this->belongsTo(Method::class);
    }

    public function photos()
    {
        return $this->hasMany(EirInspectionDetailPhoto::class);
    }

    public function workLabors()
    {
        return $this->hasMany(WorkOrderLabor::class,'eir_inspection_detail_id');
    }
}
