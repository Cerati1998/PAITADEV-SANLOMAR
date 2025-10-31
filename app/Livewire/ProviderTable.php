<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Provider;
use App\Services\SunatService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class ProviderTable extends DataTableComponent
{
    protected $model = Provider::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');

        $this->setConfigurableAreas([
            'after-wrapper' => ['providers.modal'],
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),

            Column::make('Acciones')
                ->label(function ($row) {
                    return view('providers.actions', ['provider' => $row]);
                }),
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
            Column::make("Categoria", "categoria")
                ->searchable()
                ->sortable(),
            Column::make("Detalle Categoria", "detalleServicio")
                ->searchable(),

        ];
    }

    #[On('providerAdded')]
    public function builder(): Builder
    {
        return Provider::query()
            ->where('branch_id', session('branch')->id)
            ->where('company_id', session('company')->id);
    }

    //Configuracion adicional
    public $identities;

    public $openModal = false;
    public $provider_id;

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

    public function edit(Provider $provider)
    {
        $this->provider_id = $provider->id;
        $this->openModal = true;

        $this->provider = [
            'tipoDoc' => $provider->tipoDoc,
            'numDoc' => $provider->numDoc,
            'rznSocial' => $provider->rznSocial,
            'direccion' => $provider->direccion,
            'email' => $provider->email,
            'telephone' => $provider->telephone,
            'cuentaCte' => $provider->cuentaCte,
            'categoria' => $provider->categoria,
            'detalleServicio' => $provider->detalleServicio,
        ];
    }

    public function save()
    {
        if (!auth()->user()->can('providers.update')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        $this->validate([
            'provider.tipoDoc' => 'required|exists:identities,id',
            'provider.numDoc' => [
                Rule::requiredIf($this->provider['tipoDoc'] != '-'),
                Rule::when($this->provider['tipoDoc'] == 1, 'numeric|digits:8'),
                Rule::when($this->provider['tipoDoc'] == 6, ['numeric', 'digits:11', 'regex:/^(10|20)\d{9}$/']),
                Rule::unique('Providers', 'numDoc')->where(function ($query) {
                    return $query->where('company_id', session('company')->id)
                        ->where('tipoDoc', $this->provider['tipoDoc'])
                        ->where('tipoDoc', '!=', '-');
                })->ignore($this->provider_id),
            ],
            'provider.rznSocial' => 'required',
            'provider.direccion' => Rule::requiredIf($this->provider['tipoDoc'] == 6),
            'provider.telephone' => 'nullable',
            'provider.email' => 'nullable',
            'provider.categoria' => 'required',
            'provider.detalleServicio' => 'nullable',
        ]);

        Provider::find($this->provider_id)->update($this->provider);

        $this->reset('provider', 'provider_id', 'openModal');
        
        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Proveedor actualizado correctamente',
            'icon' => 'success'
        ]);
    }

    public function destroy(Provider $provider)
    {
        if (!auth()->user()->can('providers.delete')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }
        $provider->delete();
    }
}
