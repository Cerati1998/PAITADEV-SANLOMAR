<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Port extends Model
{
    use SoftDeletes;
    protected $fillable = ['code', 'name', 'country_code', 'location'];

    public function gateInDetails()
    {
        return $this->hasMany(GateInDetail::class);
    }
}
