<?php

namespace App\Models;

use App\Models\inspections\EirInspection;
use App\Models\inspections\LeakTest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ContainerOperationalTrace extends Model
{
    protected $fillable = [
        'gate_in_detail_id',
        'container_id',
        'status_box',
        'date_rep_box',
        'status_machine',
        'date_rep_machine',
        'date_final_status',
        'final_status_user',
        'status',
        'observation',
    ];

    protected $casts = [
        'date_rep_box' => 'datetime',
        'date_rep_machine' => 'datetime',
        'date_final_status' => 'datetime',
    ];

    public function gateInDetail()
    {
        return $this->belongsTo(GateInDetail::class, 'gate_in_detail_id');
    }

    public function container()
    {
        return $this->belongsTo(Container::class, 'container_id');
    }

    public function finalStatusUser()
    {
        return $this->belongsTo(User::class, 'final_status_user');
    }

    public function EirInspection()
    {
        return $this->hasOne(EirInspection::class, 'trace_id');
    }

    public function LeakTest()
    {
        return $this->hasOne(LeakTest::class,'trace_id');
    }

    /**
     * Filtra por trazas cuyo evento de origen (Dischargue/Devolution) pertenece al
     * branch indicado. Replica el criterio de ContainerTraceTable::builder(). El
     * parámetro explícito permite verificar el scope desde tinker, donde no hay sesión.
     */
    public function scopeForBranch(Builder $query, $branchId = null): Builder
    {
        $branchId = $branchId ?? session('branch')->id;

        return $query->whereHas('gateInDetail', function ($query) use ($branchId) {
            $query->whereHasMorph('originable', [Dischargue::class, Devolution::class], function ($morphQuery) use ($branchId) {
                $morphQuery->where('branch_id', $branchId);
            });
        });
    }

    // Accessor para formatear la fecha
    protected function dateRepBox(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? Carbon::parse($value)->format('d/m/Y H:i') : null,
        );
    }
    protected function dateRepMachine(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? Carbon::parse($value)->format('d/m/Y H:i') : null,
        );
    }
    protected function dateFinalStatus(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? Carbon::parse($value)->format('d/m/Y H:i') : null,
        );
    }

    protected function origen(): Attribute
    {
        return Attribute::make(
            get: fn() => match ($this->gateInDetail?->originable_type) {
                //Log::info($this->gateInDetail?->originable_type),
                \App\Models\Dischargue::class  => 'Descarga',
                \App\Models\Devolution::class => 'Devolución',
                default => 'Otro',
            }
        );
    }

    // Accessor para obtener el id de la linea
    protected function lineaId(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->gateInDetail?->originable?->shippingLine?->id
        );
    }
    // Accessor para mostrar la línea
    protected function linea(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->gateInDetail?->originable?->shippingLine?->name
        );
    }
    // Accessor para mostrar la nave
    protected function nave(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->gateInDetail?->originable?->vessel?->name
        );
    }

    public function currentStatus(): Attribute
    {
        $status = [
            [
                'description' => 'Anulado',
                'code' => 'AN',
                'icon' => 'fas fa-ban',
                'styleBg' => 'bg-stone-500'
            ],
            [
                'description' => 'Anunciado',
                'code' => 'AC',
                'icon' => 'fas fa-bullhorn',
                'styleBg' => 'bg-yellow-500'
            ],
            [
                'description' => 'Recibido',
                'code' => 'RC',
                'icon' => 'fas fa-door-open',
                'styleBg' => 'bg-sky-500'
            ],
            [
                'description' => 'Damage',
                'code' => 'DM',
                'icon' => 'fas fa-screwdriver-wrench',
                'styleBg' => 'bg-red-500'
            ],
            [
                'description' => 'Operativo',
                'code' => 'AV',
                'icon' => 'fas fa-check-circle',
                'styleBg' => 'bg-green-500'
            ],
            [
                'description' => 'Despachado',
                'code' => 'DP',
                'icon' => 'fas fa-paper-plane',
                'styleBg' => 'bg-blue-500'
            ],
            [
                'description' => 'Devolución Interna',
                'code' => 'DI',
                'icon' => 'fas fa-undo',
                'styleBg' => 'bg-purple-500'
            ],
        ];
        return Attribute::make(
            get: fn() => $status[$this->status] ?? 'Desconocido'
        );
    }


    protected function statusMachineBadge(): Attribute
{
    return Attribute::make(
        get: fn() => match ($this->getAttribute('status_machine')) {
            'pending' => [
                'description' => 'WI',
                'icon' => 'fas fa-clock',
                'styleBg' => 'bg-blue-500'
            ],
            'OP' => [
                'description' => 'AV',
                'icon' => 'fas fa-check-circle',
                'styleBg' => 'bg-green-500'
            ],
            'DM' => [
                'description' => 'DM',
                'icon' => 'fas fa-wrench',
                'styleBg' => 'bg-red-500'
            ],
            NULL => [
                'description' => 'NM', // No Machine
                'icon' => 'fas fa-ban',
                'styleBg' => 'bg-gray-500'
            ],
            default => 'Otro',
        }
    );
}

protected function statusBoxBadge(): Attribute
{
    return Attribute::make(
        get: fn() => match ($this->getAttribute('status_box')) {
            'pending' => [
                'description' => 'WI',
                'icon' => 'fas fa-clock',
                'styleBg' => 'bg-blue-500'
            ],
            'OP' => [
                'description' => 'AV',
                'icon' => 'fas fa-check-circle',
                'styleBg' => 'bg-green-500'
            ],
            'DM' => [
                'description' => 'DM',
                'icon' => 'fas fa-screwdriver-wrench',
                'styleBg' => 'bg-red-500'
            ],
            'OS' => [
                'description' => 'DY',
                'icon' => 'fas fa-soap',
                'styleBg' => 'bg-yellow-600'
            ],
            default => 'Otro',
        }
    );
}

}
