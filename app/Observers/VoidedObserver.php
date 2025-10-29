<?php

namespace App\Observers;

use App\Models\Company;
use App\Models\Voided;

class VoidedObserver
{
    public function creating(Voided $voided)
    {
        $company = Company::find($voided->company_id);

        $voided->correlativo = Voided::where('company_id', $voided->company_id)
            ->max('correlativo') + 1;

        $voided->company = [
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
