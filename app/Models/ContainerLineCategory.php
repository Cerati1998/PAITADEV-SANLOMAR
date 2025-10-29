<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContainerLineCategory extends Model
{
    protected $fillable = [
        'code',
        'description',
        'active',
        'shipping_line_id'
    ];

    protected $casts = [
        'active' => 'integer'
    ];

    public function shippingLine()
    {
        return $this->belongsTo(ShippingLine::class);
    }
}
