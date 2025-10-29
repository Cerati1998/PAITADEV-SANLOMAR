<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';
}
