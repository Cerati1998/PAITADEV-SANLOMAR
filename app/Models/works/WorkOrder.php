<?php

namespace App\Models\works;

use App\Models\Branch;
use App\Models\Container;
use App\Models\inspections\EirInspection;
use App\Models\Provider;
use App\Models\User;
use App\Models\works\WorkOrderLabor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrder extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'type',
        'serie',
        'correlativo',
        'estimate_number',
        'estimate_date',
        'started_at',
        'closed_at',
        'eir_inspection_id',
        'started_at',
        'opened_by',
        'container_id',
        'provider_id',
        'closed_at',
        'closed_by',
        'branch_id',
        'comments',
        'status'
    ];

    protected $casts = [
        'estimate_date' => 'date',
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function labors()
    {
        return $this->hasMany(WorkOrderLabor::class);
    }
    public function container()
    {
        return $this->belongsTo(Container::class);
    }

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }
    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function getStartedAtDateAttribute()
    {
        return Carbon::parse($this->started_at)->format('d/m/Y');
    }

    public function getStartedAtTimeAttribute()
    {
        return Carbon::parse($this->started_at)->format('H:i');
    }
    public function getClosedAtDateAttribute()
    {
        return $this->closed_at ? Carbon::parse($this->closed_at)->format('d/m/Y') : '-';
    }

    public function getClosedAtTimeAttribute()
    {
        return $this->closed_at ? Carbon::parse($this->closed_at)->format('H:i') : '';
    }

    public function eirInspection()
    {
        return $this->belongsTo(EirInspection::class);
    }

    public function hasWorkers(): bool
    {
        // Carga las relaciones necesarias
        $this->loadMissing('labors.laborWorkers');

        // Recorre los trabajos y verifica si alguno tiene trabajadores
        foreach ($this->labors as $labor) {
            if ($labor->laborWorkers->isNotEmpty()) {
                return true;
            }
        }

        return false;
    }

    public function typeFormatted(): Attribute
    {
        return Attribute::make(
            get: fn() => match ($this->type) {
                'box' => asset('img/icons/container.png') ?? asset('img/icons/soldador.png'),
                'machine' => asset('img/icons/machine.png') ?? asset('img/icons/soldador.png'),
                'others' => asset('img/icons/soldador.png') ?? asset('img/icons/soldador.png'),
                default => asset('img/icons/soldador.png') ?? asset('img/icons/soldador.png')
            }
        );
    }
}
