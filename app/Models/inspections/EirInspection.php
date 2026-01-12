<?php

namespace App\Models\inspections;

use App\Models\Branch;
use App\Models\ContainerOperationalTrace;
use App\Models\User;
use App\Models\works\WorkOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EirInspection extends Model
{
    use HasFactory;

    protected $table = 'eir_inspections';
    protected $primaryKey = 'id';

    protected $fillable = [
        'started_at',
        'closed_at',
        'serie',
        'correlativo',
        'normal_wash',
        'quimic_wash',
        'oxalic_wash',
        'reason_quimic_wash',
        'special_wash',
        'reason_special_wash',
        'container_category',
        'observations',
        'inspector_id',
        'branch_id',
        'trace_id', // FK al flujo principal
        'stimated_by',
        'voided_at',
        'voided_by',
        'fast_repair',
        'normal_repair',
        'quoted_repair',
        'path_photo_1',
        'path_photo_2',
        'path_photo_3',
        'path_photo_4',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
        'voided_at' => 'datetime',
        'normal_wash' => 'boolean',
        'quimic_wash' => 'boolean',
        'oxalic_wash' => 'boolean',
        'special_wash' => 'boolean',
        'fast_repair' => 'boolean',
        'normal_repair' => 'boolean',
        'quoted_repair' => 'boolean',
    ];

    public function details()
    {
        return $this->hasMany(EirInspectionDetail::class);
    }

    public function trace()
    {
        return $this->belongsTo(ContainerOperationalTrace::class, 'trace_id');
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }
    public function estimator()
    {
        return $this->belongsTo(User::class, 'stimated_by');
    }
    public function voider()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function workOrder()
    {
        return $this->hasOne(WorkOrder::class);
    }

    //Accesor para las fotos
    public function getPhoto1Url()
    {
        return $this->path_photo_1 ? $this->path_photo_1 : null;
        //return $this->path_photo_1 ? Storage::url($this->path_photo_1) : null;
    }
    public function getPhoto2Url()
    {
        return $this->path_photo_2 ? $this->path_photo_2 : null;
        //return $this->path_photo_2 ? Storage::url($this->path_photo_2) : null;
    }
    public function getPhoto3Url()
    {
        return $this->path_photo_3 ? $this->path_photo_3 : null;
        //return $this->path_photo_3 ? Storage::url($this->path_photo_3) : null;
    }
    public function getPhoto4Url()
    {
        return $this->path_photo_4 ? $this->path_photo_4 : null;
        //return $this->path_photo_4 ? Storage::url($this->path_photo_4) : null;
    }

    // Método helper para obtener todas las fotos
    public function getPhotosAttribute()
    {
        return [
            1 => $this->path_photo_1,
            2 => $this->path_photo_2,
            3 => $this->path_photo_3,
            4 => $this->path_photo_4,
        ];
    }

    // Método para limpiar fotos huérfanas al eliminar inspección
    protected static function booted()
    {
        static::deleting(function ($inspection) {
            // Eliminar archivos de fotos cuando se elimina la inspección
            $photos = [$inspection->path_photo_1, $inspection->path_photo_2, $inspection->path_photo_3, $inspection->path_photo_4];

            foreach ($photos as $photo) {
                if ($photo && Storage::disk('public')->exists($photo)) {
                    Storage::disk('public')->delete($photo);
                }
            }
        });
    }
}
