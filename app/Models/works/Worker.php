<?php

namespace App\Models\works;

use App\Models\Branch;
use App\Models\Identity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Worker extends Model
{
    use HasFactory;
    public $fillable = [
        'id',
        'name',
        'tipoDoc',
        'numDoc',
        'branch_id',
        'email',
        'telefono',
        'cargo',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function labors()
    {
        return $this->hasMany(WorkOrderLabor::class, 'labor_worker');
    }

    public function identity()
    {
        return $this->belongsTo(Identity::class, 'tipoDoc');
    }
}
