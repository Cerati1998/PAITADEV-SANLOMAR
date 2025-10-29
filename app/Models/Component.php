<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Component extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'code',
        'description'
    ];
}
