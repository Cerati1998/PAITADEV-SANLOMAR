<?php

namespace App\Models\Dispatches;

use App\Models\Branch;
use App\Models\Client;
use App\Models\ContainerOperationalTrace;
use App\Models\User;
use App\Models\Vessel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Dispatch extends Model
{
    protected $fillable = [
        'wash_date',
        'dispatch_date',
        'reserva_booking',
        'oxalic_wash',
        'eir_gate_out',
        'vessel_id',
        'trace_id',
        'shipper_id',
        'washer_id',
        'dispatcher_id',
        'branch_id',
    ];

    protected $casts = [
        'wash_date' => 'datetime',
        'dispatch_date' => 'datetime',
        'oxalic_wash' => 'boolean'
    ];

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }

    public function trace()
    {
        return $this->belongsTo(ContainerOperationalTrace::class, 'trace_id');
    }

    public function shipper()
    {
        return $this->belongsTo(Client::class, 'shipper_id');
    }

    public function washer()
    {
        return $this->belongsTo(User::class, 'washer_id');
    }
    public function dispatcher()
    {
        return $this->belongsTo(User::class, 'dispatcher_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function washDate(): Attribute
    {
        return  Attribute::make(
            get: fn($value) => Carbon::parse($value)->format('d/m/Y H:i')
        );
    }
    public function dispatchDate(): Attribute
    {
        return  Attribute::make(
            get: fn($value) => $value ? Carbon::parse($value)->format('d/m/Y H:i') : '-'
        );
    }
}
