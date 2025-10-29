<?php

namespace App\Models\inspections;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EirInspectionDetailPhoto extends Model
{
    protected $fillable = [
        'path',
        'eir_inspection_detail_id',
    ];

    public function EirDetail()
    {
        return $this->belongsTo(EirInspectionDetail::class, 'eir_inspection_detail_id');
    }

    public function getUrlAttribute()
    {
        return $this->path;
        //return Storage::url($this->path);
    }
}
