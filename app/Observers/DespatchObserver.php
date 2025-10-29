<?php

namespace App\Observers;

use App\Models\Company;
use App\Models\Despatch;

class DespatchObserver
{
    public function creating(Despatch $despatch)
    {
        $company = Company::find($despatch->company_id);

        $despatch->company = [
            'ruc' => $company->ruc,
            'razonSocial' => $company->razonSocial,
            'nombreComercial' => $company->nombreComercial,
            'address' => [
                'direccion' => $company->direccion,
                'distrito' => $company->district->name,
                'provincia' => $company->district->province->name,
                'departamento' => $company->district->province->department->name,
                'ubigueo' => $company->ubigeo
            ]
        ];
    }
}
