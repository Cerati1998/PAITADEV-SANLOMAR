<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReeferMachine extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'code',
        'name',
    ];

    public function containers()
    {
        return $this->hasMany(Container::class,'reefer_machine_id');
    }
}
