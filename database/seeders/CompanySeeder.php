<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::create([
            'ruc' => '20525842721',
            'razonSocial' => 'SANLOMAR GROUP S.A.C.',
            'nombreComercial' => 'Sanlomar',
            'direccion' => 'Mza. H1 Lote. 10-a a.H. Ciudad Roja del Pescador',
            'ubigeo' => '150113',
        ]);

        $company->users()->attach(1);
        $company->users()->attach(2);
        $company->users()->attach(3);

        $branch = $company->branches()->create([
            'name' => 'Depot Paita - Norte',
            'code' => '0000',
            'ubigeo' => '150113',
            'address' => '1-D Z.I. ZONA INDUSTRIAL II COMPLEMENTARIA PIURA – PAITA',
        ]);

        $branch->documents()->attach("01", [
            'serie' => 'F001',
            'correlativo' => '0001',
            'company_id' => $company->id,
        ]);
        $branch->documents()->attach("03", [
            'serie' => 'B001',
            'correlativo' => '0001',
            'company_id' => $company->id,
        ]);
        $branch->documents()->attach("07", [
            'serie' => 'FC01',
            'correlativo' => '0001',
            'company_id' => $company->id,
        ]);
        $branch->documents()->attach("07", [
            'serie' => 'BC01',
            'correlativo' => '0001',
            'company_id' => $company->id,
        ]);
        $branch->documents()->attach("08", [
            'serie' => 'FD01',
            'correlativo' => '0001',
            'company_id' => $company->id,
        ]);
        $branch->documents()->attach("08", [
            'serie' => 'BD01',
            'correlativo' => '0001',
            'company_id' => $company->id,
        ]);
        $branch->documents()->attach("09", [
            'serie' => 'T001',
            'correlativo' => '0001',
            'company_id' => $company->id,
        ]);
        $branch->documents()->attach("31", [
            'serie' => 'V001',
            'correlativo' => '0001',
            'company_id' => $company->id,
        ]);
        //añado un documento y serie para orden de trabajo
        $branch->documents()->attach("11", [
            'serie' => 'SLOT',
            'correlativo' => '000001',
            'company_id' => $company->id,
        ]);

        $branch->users()->attach(1, [
            'company_id' => $company->id,
        ]);
        $branch->users()->attach(2, [
            'company_id' => $company->id,
        ]);
        $branch->users()->attach(3, [
            'company_id' => $company->id,
        ]);

        //creo cliente y ya no en el observer
        // Crear cliente "Cliente Varios" con branch y company
        Client::create([
            'tipoDoc' => '-',
            'rznSocial' => 'Cliente Varios',
            'branch_id' => $branch->id,
            'company_id' => $company->id,
        ]);
    }
}
