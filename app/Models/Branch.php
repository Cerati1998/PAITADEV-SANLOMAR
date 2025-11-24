<?php

namespace App\Models;

use App\Models\Dispatches\Dispatch;
use App\Models\inspections\EirInspection;
use App\Models\inspections\LeakTest;
use App\Models\works\WorkOrder;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'phone',
        'email',
        'website',
        'ubigeo',
        'address',
    ];

    public function documents()
    {
        return $this->belongsToMany(Document::class, 'branch_company_document')
            ->withPivot('serie', 'correlativo')
            ->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'branch_company_user')
            ->withPivot('company_id')
            ->withTimestamps();
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }
    public function transports()
    {
        return $this->hasMany(Transport::class);
    }
    public function voideds()
    {
        return $this->hasMany(Voided::class);
    }
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function despatches()
    {
        return $this->hasMany(Despatch::class);
    }
    public function dischargues()
    {
        return $this->hasMany(Dischargue::class);
    }
    public function branchIntegrations()
    {
        return $this->hasMany(BranchIntegration::class);
    }

    public function eirInspections() 
    {
        return $this->hasMany(EirInspection::class);
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }
    public function leakTests()
    {
        return $this->hasMany(LeakTest::class);
    }
    public function dispatches()
    {
        return $this->hasMany(Dispatch::class);
    }

    public function archingCashes()
    {
        return $this->hasMany(ArchingCash::class);
    }
}
