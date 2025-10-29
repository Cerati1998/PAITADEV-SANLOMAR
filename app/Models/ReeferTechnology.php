<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReeferTechnology extends Model
{
    use SoftDeletes;
    protected $fillable = [
         'name',
        'temperature_min',
        'temperature_max',
        'humidity_min',
        'humidity_max',
        'ventilation_min',
        'ventilation_max',
        'atmosphere_o2_min',
        'atmosphere_o2_max',
        'atmosphere_co2_min',
        'atmosphere_co2_max',
        'usage',
    ];

    public function containers(){
        return $this->hasMany(Container::class);
    }
}
