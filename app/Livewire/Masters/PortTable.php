<?php

namespace App\Livewire\Masters;

use App\Models\Country;
use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Port;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class PortTable extends DataTableComponent
{
    protected $model = Port::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
        $this->setConfigurableAreas([
            'after-wrapper' => ['masters.ports.modal']
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
            Column::make('Acciones')
                ->label(function ($row) {
                    return view('masters.ports.actions', ['port' => $row]);
                }),
            Column::make("Código", "code")
                ->searchable()
                ->sortable(),
            Column::make("Nombre", "name")
                ->searchable()
                ->sortable(),
            Column::make("Pais", "country_code")
                ->sortable(),
            Column::make("Coordenadas", "location")
                ->sortable(),
            Column::make("Creado", "created_at")
                ->sortable(),
        ];
    }

    #[On('portAdded')]
    public function builder(): Builder
    {
        return Port::query();
    }

    public $countryOptions = [];

    public $portId;
    public $openModal = false;
    public $port = [
        'code' => '',
        'name' => '',
        'country_code' => '',
        'location' => ''
    ];

    public function edit(Port $port)
    {
        $this->portId = $port->id;
        $this->port = $port->only(['code', 'name', 'country_code', 'location']);
        $this->openModal = true;
    }

    public function closeModal()
    {
        $this->reset('openModal', 'portId', 'port');
    }

    public function save()
    {
        $this->validate([
            'port.code' => [
                'required',
                'string',
                'min:5',
                'max:6',
                Rule::unique('ports', 'code')->ignore($this->portId)
            ],
            'port.name' => 'required|string|min:3',
            'port.country_code' => 'required|string|min:2|max:4'
        ], [], [
            'port.code' => 'Código de Puerto',
            'port.name' => 'Nombre de Puerto',
            'port.country_code' => 'Código de País'
        ]);

        Port::find($this->portId)->update($this->port);

        $this->reset('openModal', 'port', 'portId');
        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Puerto actualizado correctamente',
            'icon' => 'success'
        ]);
    }

    public function destroy(Port $port)
    {
        $port->delete();

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Puerto eliminado correctamente',
            'icon' => 'success'
        ]);
    }

    public function mount()
    {
        $this->countryOptions = Country::all()->toArray();
    }
}
