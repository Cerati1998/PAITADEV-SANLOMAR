<?php

namespace App\Models\inspections;

use App\Models\Branch;
use App\Models\ContainerOperationalTrace;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class LeakTest extends Model
{
    protected $fillable = [
        'serie',
        'correlativo',
        'started_at',
        'finished_at',
        'hours',
        'status',
        'branch_id',
        'trace_id',
        'inspector_id',
    ];

    protected $casts = [
        'hours' => 'float',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    public function trace()
    {
        return $this->belongsTo(ContainerOperationalTrace::class, 'trace_id');
    }
    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }
}
