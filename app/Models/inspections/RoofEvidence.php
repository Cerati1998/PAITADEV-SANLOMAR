<?php

namespace App\Models\inspections;

use Illuminate\Database\Eloquent\Model;

class RoofEvidence extends Model
{
    protected $fillable = [
        'eir_inspection_id',
        'qty_welded_patch',
        'qty_riveted_patch',
        'qty_rail_patch',
        'qty_welded_cut',
        'qty_sealed_cut',
        'has_additional_damages',
        'photo_1',
        'photo_2',
        'photo_3',
        'photo_4',
    ];

    protected $casts = [
        'qty_welded_patch' => 'integer',
        'qty_riveted_patch' => 'integer',
        'qty_rail_patch' => 'integer',
        'qty_welded_cut' => 'integer',
        'qty_sealed_cut' => 'integer',
        'has_additional_damages' => 'boolean',
    ];

    public function eirInspection()
    {
        return $this->belongsTo(EirInspection::class);
    }
}
