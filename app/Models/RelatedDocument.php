<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RelatedDocument extends Model
{
    protected $table = 'related_documents';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'description',
    ];
}
