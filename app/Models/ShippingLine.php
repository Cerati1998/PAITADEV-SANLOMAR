<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingLine extends Model
{
    use SoftDeletes;
    protected $fillable = ['code', 'name'];

    public function vessels()
    {
        return $this->hasMany(Vessel::class);
    }

    public function dischargues()
    {
        return $this->hasMany(Dischargue::class);
    }
    public function devolutions()
    {
        return $this->hasMany(Devolution::class);
    }
    public function containers()
    {
        return $this->hasMany(Container::class);
    }

    public function ContainerCategories()
    {
        return $this->hasMany(ContainerLineCategory::class);
    }
}
