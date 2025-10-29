<?php

namespace App\Livewire\Masters;

use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Vessel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class VesselTable extends DataTableComponent
{
    protected $model = Vessel::class;

    public $shippingLineId;

    public function mount($shipping_line = null)
    {
        if ($shipping_line) {
            $this->shippingLineId = $shipping_line->id;
        }
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
        $this->setConfigurableAreas([
            'after-wrapper' => ['masters.lines.vessel']
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
            Column::make('Acciones')
                ->label(function ($row) {
                    return view('masters.lines.vessel-actions', ['vessel' => $row]);
                }),
            Column::make("IMO", "imo_number")
                ->searchable()
                ->sortable(),
            Column::make("Nombre", "name")
                ->searchable()
                ->sortable(),
            Column::make("Linea", "shippingLine.name")
                ->sortable(),
            Column::make('Capacidad', "pallets")
                ->sortable(),
            Column::make("Categoria", "type")
                ->sortable(),
            Column::make("Creado", "created_at")
                ->sortable(),
        ];
    }

    #[On('vesselAdded')]
    public function builder(): Builder
    {
        $query = Vessel::query()->with(['shippingLine']);

        // Solo aplicar el filtro si shippingLineId está establecido
        if ($this->shippingLineId) {
            $query->where('shipping_line_id', $this->shippingLineId);
        }

        return $query;
    }

    public $vesselId;
    public $openModal = false;
    public $vessel = [
        'imo_number' => '',
        'name' => '',
        'type' => '',
        'pallets' => 0,
    ];

    public function edit(Vessel $vessel)
    {
        $this->vesselId = $vessel->id;
        $this->vessel = $vessel->only(['imo_number', 'name', 'type', 'pallets']);
        $this->openModal = true;
    }

    public function closeModal()
    {
        $this->reset('openModal', 'vessel', 'vesselId');
    }

    public function save()
    {
        $this->validate([
            'vessel.imo_number' => 'string',
            'vessel.name' => [
                'required',
                'string',
                'min:3',
                Rule::unique('vessels', 'name')->ignore($this->vesselId)
            ],
            'vessel.type' => 'required|string|in:container,bulk,tanker',
            'vessel.pallets' => 'integer'
        ],[],[
            'vessel.imo_number' => 'IMO de Nave',
            'vessel.name' => 'Nombre de Nave',
            'vessel.type' => 'Categoria',
            'vesse.pallets' => 'Capacidad de Pallets'
        ]);

        Vessel::find($this->vesselId)->update($this->vessel);

        $this->reset('openModal', 'vesselId', 'vessel');
        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Nave actualizada correctamente',
            'icon' => 'success'
        ]);
    }

    public function destroy(Vessel $vessel)
    {
        $vessel->delete();

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Nave Eliminada correctamente',
            'icon' => 'success'
        ]);
    }
}
