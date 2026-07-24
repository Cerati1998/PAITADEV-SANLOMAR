<?php

namespace App\Observers;

use App\Models\Company;
use App\Models\DespatchTransportista;

class DespatchTransportistaObserver
{
    public function creating(DespatchTransportista $despatch)
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
