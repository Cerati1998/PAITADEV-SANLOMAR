<?php

namespace App\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Client;
use App\Services\SunatService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;

class ClientTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');

        $this->setConfigurableAreas([
            'after-wrapper' => ['clients.modal'],
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),

            Column::make("Tipo Doc", "identity.description")
                ->sortable(),

            Column::make("Num. Doc.", "numDoc")
                ->searchable()
                ->sortable()
                ->format(function ($value) {
                    return $value ? $value : 'S/N';
                }),

            Column::make("Razon Social", "rznSocial")
                ->searchable()
                ->sortable(),

            Column::make('actions')
                ->label(function ($row) {
                    return view('clients.actions', ['client' => $row]);
                }),

        ];
    }

    #[On('clientAdded')]
    public function builder(): Builder
    {
        return Client::query()
            ->where('branch_id', session('branch')->id)
            ->where('company_id', session('company')->id);
    }

    //Configuracion adicional
    public $identities;

    public $openModal = false;
    public $client_id;

    public $client = [
        'tipoDoc' => '-',
        'numDoc' => null,
        'rznSocial' => null,
        'direccion' => null,
        'email' => null,
        'telephone' => null,
    ];

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

    public function edit(Client $client)
    {
        $this->client_id = $client->id;
        $this->openModal = true;

        $this->client = [
            'tipoDoc' => $client->tipoDoc,
            'numDoc' => $client->numDoc,
            'rznSocial' => $client->rznSocial,
            'direccion' => $client->direccion,
            'email' => $client->email,
            'telephone' => $client->telephone,
        ];
    }

    public function save()
    {
        if (!auth()->user()->can('clients.update')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        $this->validate([
            'client.tipoDoc' => 'required|exists:identities,id',
            'client.numDoc' => [
                Rule::requiredIf($this->client['tipoDoc'] != '-'),
                Rule::when($this->client['tipoDoc'] == 1, 'numeric|digits:8'),
                Rule::when($this->client['tipoDoc'] == 6, ['numeric', 'digits:11', 'regex:/^(10|20)\d{9}$/']),
                Rule::unique('clients', 'numDoc')->where(function ($query) {
                    return $query->where('company_id', session('company')->id)
                        ->where('tipoDoc', $this->client['tipoDoc'])
                        ->where('tipoDoc', '!=', '-');
                })->ignore($this->client_id),
            ],
            'client.rznSocial' => 'required',
            'client.direccion' => Rule::requiredIf($this->client['tipoDoc'] == 6),
            'client.telephone' => 'nullable',
            'client.email' => 'nullable',
        ]);

        $this->client['company_id'] = session('company')->id;

        Client::find($this->client_id)->update($this->client);

        $this->reset('client', 'client_id', 'openModal');
    }

    public function destroy(Client $client)
    {
         if (!auth()->user()->can('clients.delete')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        $client->delete();
    }
}
