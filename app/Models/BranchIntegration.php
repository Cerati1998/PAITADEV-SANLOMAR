<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class BranchIntegration extends Model
{
    protected $fillable = [
        'branch_id',
        'integration_id',
        'api_token',
        'created_by',
        'active'
    ];

    protected $casts = [
        'active' => 'boolean'
    ];

    public function integration()
    {
        return $this->belongsTo(Integration::class);
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function active():Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ?  'Activo' : 'Inactivo'
        );
    }
}
