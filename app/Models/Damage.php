<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Damage extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'code',
        'description'
    ];
}
