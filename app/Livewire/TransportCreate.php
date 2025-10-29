<?php

namespace App\Livewire;

use App\Models\Transport;
use App\Services\SunatService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class TransportCreate extends Component
{
    public $identities;

    public $openModal = false;
    public $transport = [
        'razonSocial' => '',
        'tipoDoc' => '',
        'numDoc' => '',
        'direccion' => '',
        'email' => '',
        'phone' => '',
        'observations' => '',
    ];

    public function save()
    {
        $this->validate([
            'transport.tipoDoc' => 'required|exists:identities,id',
            'transport.numDoc' => [
                Rule::requiredIf($this->transport['tipoDoc'] != '-'),
                Rule::when($this->transport['tipoDoc'] == 1, 'numeric|digits:8'),
                Rule::when($this->transport['tipoDoc'] == 6, ['numeric', 'digits:11', 'regex:/^(10|20)\d{9}$/']),
                Rule::unique('transports', 'numDoc')->where(function ($query) {
                    return $query->where('branch_id', session('branch')->id)
                        ->where('tipoDoc', $this->transport['tipoDoc'])
                        ->where('tipoDoc', '!=', '-')
                        ->whereNull('deleted_at');
                }),
            ],
            'transport.razonSocial' => 'required',
            'transport.direccion' => Rule::requiredIf($this->transport['tipoDoc'] == 6),
            'transport.phone' => 'nullable',
            'transport.email' => 'nullable',
            'transport.observations' => 'nullable',
        ], [], [
            'transport.tipoDoc' => 'Tipo de Documento',
            'transport.numDoc' => 'Número de Documento',
            'transport.razonSocial' => 'Razón Social',
            'transport.direccion' => 'Dirección',
            'transport.phone' => 'Telefono',
            'transport.email' => 'Correo electronico',
            'transport.observations' => 'Observaciones',
        ]);

        // Busca considerando la sucursal actual
        $transport = Transport::withTrashed()
            ->where('numDoc', $this->transport['numDoc'])
            ->where('tipoDoc', $this->transport['tipoDoc'])
            ->where('branch_id', session('branch')->id)
            ->first();

        $wasRestored = false;

        if ($transport) {
            if ($transport->trashed()) {
                $transport->restore();
                $wasRestored = true;
            }
            $transport->update($this->transport);
        } else {
            $transport = Transport::create($this->transport + [
                'branch_id' => session('branch')->id
            ]);
        }

        $this->reset('transport', 'openModal');
        $this->dispatch('transportAdded', $transport->id);

        $this->dispatch('swal', [
            'title' => 'Éxito!',
            'text' => $wasRestored ? 'Transportista reactivado exitosamente' : 'Transportista creado exitosamente',
            'icon' => 'success'
        ]);
    }

    public function searchDocument()
    {
        $this->validate([
            'transport.tipoDoc' => 'required|in:1,6',
            'transport.numDoc' => [
                Rule::when($this->transport['tipoDoc'] == 1, 'numeric|digits:8'),
                Rule::when($this->transport['tipoDoc'] == 6, ['numeric', 'digits:11', 'regex:/^(10|20)\d{9}$/']),
            ],
        ]);

        $sunat = app(SunatService::class);
        $docType = (string) $this->transport['tipoDoc'];
        $numero  = (string) $this->transport['numDoc'];

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
                $this->transport['razonSocial'] = $response['data']['razon_social'] ?? null;
                $this->transport['direccion'] = $response['data']['direccion'] ?? null;
            } elseif ($docType === '1') {
                $this->transport['razonSocial'] = $response['data']['nombre'] ?? null;
                $this->transport['direccion'] = '-';
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
        return view('livewire.transport-create');
    }
}
