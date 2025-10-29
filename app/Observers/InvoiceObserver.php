<?php

namespace App\Observers;

use App\Models\Company;
use App\Models\Invoice;

class InvoiceObserver
{
    public function creating(Invoice $invoice)
    {
        $company = Company::find($invoice->company_id);

        $invoice->company = [
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
