<?php

namespace App\Models\works;

use App\Models\works\WorkOrderLabor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaborWorker extends Model
{
    use HasFactory;
    protected $table = 'labor_worker';

    protected $fillable = [
        'work_order_labor_id',
        'worker_id',
        'task_description',
        'main_worker',
    ];

    protected $casts = [
        'task_description' => 'string',
        'main_worker' => 'boolean'
    ];

    public function worker()
    {
        return $this->belongsTo(Worker::class);
        /*  ->withPivot('task_description', 'main_worker')
            ->withTimestamps(); */
    }

    public function workOrderLabor()
    {
        return $this->belongsTo(WorkOrderLabor::class);
    }
}
