<?php

namespace App\Livewire;

use App\Models\CustomBroker;
use App\Services\SunatService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CustomBrokerCreate extends Component
{
    public $identities;
    public $openModal = false;

    public $broker = [
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
            'broker.tipoDoc' => 'required|exists:identities,id',
            'broker.numDoc' => [
                Rule::requiredIf($this->broker['tipoDoc'] != '-'),
                Rule::when($this->broker['tipoDoc'] == 1, 'numeric|digits:8'),
                Rule::when($this->broker['tipoDoc'] == 6, ['numeric', 'digits:11', 'regex:/^(10|20)\d{9}$/']),
                Rule::unique('custom_brokers', 'numDoc')->where(function ($query) {
                    return $query->where('tipoDoc', $this->broker['tipoDoc'])
                        ->where('tipoDoc', '!=', '-');
                }),
            ],
            'broker.rznSocial' => 'required',
            'broker.direccion' => Rule::requiredIf($this->broker['tipoDoc'] == 6),
            'broker.telephone' => 'nullable',
            'broker.email' => 'nullable',
        ]);

        $broker = CustomBroker::create($this->broker);

        $this->reset('broker', 'openModal');

        $this->dispatch('brokerAdded', $broker->id);
    }

    public function searchDocument()
    {
        $this->validate([
            'broker.tipoDoc' => 'required|in:1,6',
            'broker.numDoc' => [
                Rule::when($this->broker['tipoDoc'] == 1, 'numeric|digits:8'),
                Rule::when($this->broker['tipoDoc'] == 6, ['numeric', 'digits:11', 'regex:/^(10|20)\d{9}$/']),
            ],
        ]);

        $sunat = app(SunatService::class);
        $docType = (string) $this->broker['tipoDoc'];
        $numero  = (string) $this->broker['numDoc'];

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
                $this->broker['rznSocial'] = $response['data']['razon_social'] ?? null;
                $this->broker['direccion'] = $response['data']['direccion'] ?? null;
            } elseif ($docType === '1') {
                $this->broker['rznSocial'] = $response['data']['full_name'] ?? null;
                $this->broker['direccion'] = '-';
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
        return view('livewire.custom-broker-create');
    }
}
