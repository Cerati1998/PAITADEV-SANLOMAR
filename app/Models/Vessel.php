<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vessel extends Model
{
    use SoftDeletes;
    protected $fillable = ['imo_number', 'name', 'type', 'pallets', 'shipping_line_id'];

    public function shippingLine()
    {
        return $this->belongsTo(ShippingLine::class);
    }

    public function dischargues(){
        return $this->hasMany(Dischargue::class);
    }
    public function devolutions(){
        return $this->hasMany(Devolution::class);
    }
}
