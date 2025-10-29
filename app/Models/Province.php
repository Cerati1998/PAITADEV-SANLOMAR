<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
