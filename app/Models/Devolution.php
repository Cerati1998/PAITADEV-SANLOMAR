<?php

namespace App\Models;

use App\Observers\DevolutionObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(DevolutionObserver::class)]
class Devolution extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'returned_date',
        'client_id',
        'custom_broker_id',
        'bl_number',
        'memo_number',
        'shipping_line_id',
        'vessel_id',
        'week',
        'voyage',
        'regimen',
        'created_by',
        'branch_id',
        'anulated_by',
        'anulated_reason'
    ];

    protected $casts = [
        'returned_date' => 'date',
    ];

    /*  public function containers(): MorphMany
    {
        return $this->morphMany(Container::class, 'origin');
    }
 */

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    public function broker()
    {
        return $this->belongsTo(CustomBroker::class,'custom_broker_id');
    }
    public function customBroker()
    {
        return $this->belongsTo(CustomBroker::class);
    }
    public function shippingLine()
    {
        return $this->belongsTo(ShippingLine::class);
    }

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }

    public function gateInDetails()
    {
        return $this->morphMany(GateInDetail::class, 'originable');
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

     // Accessor para formatear la fecha
    protected function returnedDate(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? Carbon::parse($value)->format('d/m/Y') : null,
        );
    }
}
