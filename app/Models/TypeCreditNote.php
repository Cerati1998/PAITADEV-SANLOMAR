<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeCreditNote extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';
}
