<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    protected $fillable = [
        'name',
        'description',
        'base_url',
    ];

    public function branchIntegrations(){
        return $this->hasMany(BranchIntegration::class);
    }
}
