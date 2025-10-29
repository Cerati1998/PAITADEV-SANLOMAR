<?php

namespace App\Livewire;

use App\Models\Provider;
use App\Services\SunatService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ProviderCreate extends Component
{
    public $identities;
    public $openModal = false;

    public $provider = [
        'tipoDoc' => '-',
        'numDoc' => null,
        'rznSocial' => null,
        'direccion' => null,
        'email' => null,
        'telephone' => null,
        'cuentaCte' => null,
        'categoria' => null,
        'detalleServicio' => null,
    ];

    public function save()
    {
        $this->validate([
            'provider.tipoDoc' => 'required|exists:identities,id',
            'provider.numDoc' => [
                Rule::requiredIf($this->provider['tipoDoc'] != '-'),
                Rule::when($this->provider['tipoDoc'] == 1, 'numeric|digits:8'),
                Rule::when($this->provider['tipoDoc'] == 6, ['numeric', 'digits:11', 'regex:/^(10|20)\d{9}$/']),
                Rule::unique('providers', 'numDoc')->where(function ($query) {
                    return $query->where('branch_id', session('branch')->id)
                        ->where('tipoDoc', $this->provider['tipoDoc'])
                        ->where('tipoDoc', '!=', '-');
                }),
            ],
            'provider.rznSocial' => 'required',
            'provider.direccion' => Rule::requiredIf($this->provider['tipoDoc'] == 6),
            'provider.telephone' => 'nullable',
            'provider.email' => 'nullable',
            'provider.categoria' => 'required',
            'provider.detalleServicio' => 'nullable',
        ]);

        $this->provider['branch_id'] = session('branch')->id;
        $this->provider['company_id'] = session('company')->id;

        $provider = Provider::create($this->provider);

        $this->reset('provider', 'openModal');
        
          $this->dispatch('swal',[
            'title' => 'Exito!',
            'text' => 'Proveedor actualizado correctamente',
            'icon' => 'success'
        ]);
        $this->dispatch('providerAdded', $provider->id);
    }

    public function searchDocument()
    {
        $this->validate([
            'provider.tipoDoc' => 'required|in:1,6',
            'provider.numDoc' => [
                Rule::when($this->provider['tipoDoc'] == 1, 'numeric|digits:8'),
                Rule::when($this->provider['tipoDoc'] == 6, ['numeric', 'digits:11', 'regex:/^(10|20)\d{9}$/']),
            ],
        ]);

        $sunat = app(SunatService::class);
        $docType = (string) $this->provider['tipoDoc'];
        $numero  = (string) $this->provider['numDoc'];

        try {
            $response = match ($docType) {
                '6' => $sunat->consultarRUC($numero),
                '1' => $sunat->consultarDNI($numero),
                default => ['success' => false, 'message' => 'Tipo de documento no válido']
            };

            if (!($response['success'] ?? false)) {
                throw new \Exception($response['message'] ?? 'No se encontró información');
            }

            if ($docType === '6') {
                $this->provider['rznSocial'] = $response['data']['razon_social'] ?? null;
                $this->provider['direccion'] = $response['data']['direccion'] ?? null;
            } elseif ($docType === '1') {
                $this->provider['rznSocial'] = $response['data']['nombre'] ?? null;
                $this->provider['direccion'] = '-';
            }
        } catch (\Throwable $e) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text'  => $e->getMessage(),
            ]);
        }
    }


    public function render()
    {
        return view('livewire.provider-create');
    }
}
