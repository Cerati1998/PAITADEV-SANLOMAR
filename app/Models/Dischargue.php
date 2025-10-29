<?php

namespace App\Models;

use App\Observers\DischargueObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(DischargueObserver::class)]
class Dischargue extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'shipping_line_id',
        'vessel_id',
        'voyage',
        'bl_number',
        'eta_date',
        'week',
        'manifiest_number',
        'started_at',
        'completed_at',
        'created_by',
        'branch_id',
        'anulated_by',
        'anulated_reason'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];


    public function shippingLine()
    {
        return $this->belongsTo(ShippingLine::class);
    }
    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function anulator()
    {
        return $this->belongsTo(User::class, 'anulated_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function gateInDetails(){
        return $this->morphMany(GateInDetail::class,'originable');
    }

    // Accessor para formatear la fecha
    protected function etaDate(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? Carbon::parse($value)->format('d/m/Y') : null,
        );
    }
}
