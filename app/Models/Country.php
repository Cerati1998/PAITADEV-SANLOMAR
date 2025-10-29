<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{

    public $fillable = [
        'A2',
        'A3',
        'description'
    ];
    
    public $incrementing = true;

    protected $keyType = 'string';
}
