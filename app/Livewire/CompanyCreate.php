<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\District;
use Livewire\Component;

class CompanyCreate extends Component
{

    public $ruc;
    public $razonSocial;
    public $nombreComercial;
    public $ubigeo;
    public $direccion;

    public function search()
    {
        $this->validate([
            'ruc' => [
                'required',
                'numeric',
                'digits:11',
                'regex:/^(10|20)\d{9}$/',
            ],
        ]);

        $sunat = new \jossmp\sunat\ruc();
        $response = $sunat->consulta($this->ruc);

        if ($response->success) {
            $this->razonSocial = $response->result->razon_social;
            $this->nombreComercial = $response->result->nombre_comercial;
            $this->direccion = $response->result->direccion;

            $district = District::where('name', $response->result->distrito)->first();

            if ($district) {
                $this->ubigeo = $district->id;
            }
        } else {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No se encontró la empresa',
            ]);
        }
    }

    public function save()
    {
        $this->validate([
            'ruc' => [
                'required',
                'numeric',
                'digits:11',
                'regex:/^(10|20)\d{9}$/',
                'unique:companies,ruc',
            ],
            'razonSocial' => 'required',
            'nombreComercial' => 'nullable|string',
            'ubigeo' => 'required|exists:districts,id',
            'direccion' => 'required',
        ]);

        //validar que solo exista una empresa
        $count = Company::all()->count();
        if ($count > 0) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'Ya existe una empresa registrada en el sistema',
                'icon' => 'error'
            ]);

            return;
        }

        $company = Company::create([
            'ruc' => $this->ruc,
            'razonSocial' => $this->razonSocial,
            'nombreComercial' => $this->nombreComercial,
            'ubigeo' => $this->ubigeo,
            'direccion' => $this->direccion,
        ]);

        $company->users()->attach(auth()->id());

        $branch = $company->branches()->create([
            'name' => 'Principal',
            'code' => '0000',
            'ubigeo' => $company->ubigeo,
            'address' => $company->direccion,
        ]);

        //documents
        $branch->documents()->attach("01", [
            'serie' => 'F001',
            'correlativo' => '1',
            'company_id' => $company->id,
        ]);
        $branch->documents()->attach("03", [
            'serie' => 'B001',
            'correlativo' => '1',
            'company_id' => $company->id,
        ]);
        $branch->documents()->attach("07", [
            'serie' => 'FC01',
            'correlativo' => '1',
            'company_id' => $company->id,
        ]);
        $branch->documents()->attach("07", [
            'serie' => 'BC01',
            'correlativo' => '1',
            'company_id' => $company->id,
        ]);
        $branch->documents()->attach("08", [
            'serie' => 'FD01',
            'correlativo' => '1',
            'company_id' => $company->id,
        ]);
        $branch->documents()->attach("08", [
            'serie' => 'BD01',
            'correlativo' => '1',
            'company_id' => $company->id,
        ]);
        $branch->documents()->attach("09", [
            'serie' => 'T001',
            'correlativo' => '1',
            'company_id' => $company->id,
        ]);

        $branch->users()->attach(auth()->id(), [
            'company_id' => $company->id,
        ]);

        session()->put('company', Company::find($company->id));

        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.company-create');
    }
}
