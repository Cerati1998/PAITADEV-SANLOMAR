<?php

namespace App\Observers;

use App\Models\Client;
use App\Models\Company;

class CompanyObserver
{

    public function creating(Company $company)
    {
        $company->certificate = file_get_contents(public_path('certificates/certificate.pem'));
    }

    /* public function created(Company $company)
    {
        Client::create([
            'tipoDoc' => '-',
            'rznSocial' => 'Cliente Varios',
            'branch_id' => 1,
            'company_id' => $company->id
        ]);
    } */
}
