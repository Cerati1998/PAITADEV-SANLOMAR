<?php

namespace App\Models\works;

use App\Models\inspections\EirInspectionDetail;
use App\Models\works\WorkOrder;
use App\Models\works\LaborWorker;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderLabor extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id',
        'eir_inspection_detail_id',
        'qty',
        'description',
        'started_at',
        'finished_at',
        'hours',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'hours' => 'decimal:2',
        'qty' => 'integer'
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function laborWorkers()
    {
        return $this->hasMany(LaborWorker::class);
    }

    public function inspectionDetail()
    {
        return $this->belongsTo(EirInspectionDetail::class, 'eir_inspection_detail_id');
    }

     public function photos()
    {
        return $this->hasMany(WorkOrderLaborImage::class,'work_order_labor_id');
    }
}
