<?php

namespace App\Livewire;

use App\Models\Client;
use App\Services\SunatService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ClientCreate extends Component
{
    public $identities;
    public $openModal = false;

    public $client = [
        'tipoDoc' => '-',
        'numDoc' => null,
        'rznSocial' => null,
        'direccion' => null,
        'email' => null,
        'telephone' => null,
    ];

    public function save()
    {
        $this->validate([
            'client.tipoDoc' => 'required|exists:identities,id',
            'client.numDoc' => [
                Rule::requiredIf($this->client['tipoDoc'] != '-'),
                Rule::when($this->client['tipoDoc'] == 1, 'numeric|digits:8'),
                Rule::when($this->client['tipoDoc'] == 6, ['numeric', 'digits:11', 'regex:/^(10|20)\d{9}$/']),
                Rule::unique('clients', 'numDoc')->where(function ($query) {
                    return $query->where('branch_id', session('branch')->id)
                        ->where('tipoDoc', $this->client['tipoDoc'])
                        ->where('tipoDoc', '!=', '-');
                }),
            ],
            'client.rznSocial' => 'required',
            'client.direccion' => Rule::requiredIf($this->client['tipoDoc'] == 6),
            'client.telephone' => 'nullable',
            'client.email' => 'nullable',
        ]);

        $this->client['branch_id'] = session('branch')->id;
        $this->client['company_id'] = session('company')->id;

        $client = Client::create($this->client);

        $this->reset('client', 'openModal');

        $this->dispatch('clientAdded', $client->id);
    }

    public function searchDocument()
    {
        $this->validate([
            'client.tipoDoc' => 'required|in:1,6',
            'client.numDoc' => [
                Rule::when($this->client['tipoDoc'] == 1, 'numeric|digits:8'),
                Rule::when($this->client['tipoDoc'] == 6, ['numeric', 'digits:11', 'regex:/^(10|20)\d{9}$/']),
            ],
        ]);

        $sunat = app(SunatService::class);
        $docType = (string) $this->client['tipoDoc'];
        $numero  = (string) $this->client['numDoc'];

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
                $this->client['rznSocial'] = $response['data']['razon_social'] ?? null;
                $this->client['direccion'] = $response['data']['direccion'] ?? null;
            } elseif ($docType === '1') {
                $this->client['rznSocial'] = $response['data']['full_name'] ?? null;
                $this->client['direccion'] = '-';
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
        return view('livewire.client-create');
    }
}
