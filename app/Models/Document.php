<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_company_document')
            ->withPivot('id', 'serie', 'correlativo')
            ->withTimestamps();
    }
}
