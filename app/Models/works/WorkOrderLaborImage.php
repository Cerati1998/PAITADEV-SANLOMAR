<?php

namespace App\Models\works;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class WorkOrderLaborImage extends Model
{
    protected $fillable = [
        'path',
        'work_order_labor_id',
    ];

    public function workOrderLabor()
    {
        return $this->belongsTo(WorkOrderLabor::class, 'work_order_labor_id');
    }

    /**
     * Accessor para obtener la URL pública
     * Maneja tanto URLs completas de DigitalOcean como paths relativos
     */
    public function getUrlAttribute(): string
    {
        // Si ya es una URL completa (de DigitalOcean), devolverla tal cual
        if (filter_var($this->path, FILTER_VALIDATE_URL)) {
            return $this->path;
        }

        // Si es un path relativo, generar URL de DigitalOcean
        return Storage::disk('do')->url($this->path);
    }
}
