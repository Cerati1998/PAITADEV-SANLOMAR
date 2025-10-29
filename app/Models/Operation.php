<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operation extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    //Relacion muchos a muchos
    public function documents()
    {
        return $this->belongsToMany(Document::class);
    }
}
