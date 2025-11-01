<?php

namespace App\Livewire;

use App\Models\Container;
use App\Models\ContainerOperationalTrace;
use App\Models\Vessel;
use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Dischargue;
use App\Models\ShippingLine;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class DischargueTable extends DataTableComponent
{
    protected $model = Dischargue::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
        $this->setConfigurableAreas([
            'after-wrapper' => ['dischargues.dischargue']
        ]);
    }

    #[On('dischargueAdded')]
    public function builder(): Builder
    {
        return Dischargue::with(['vessel', 'shippingLine', 'creator', 'branch'])
            ->where('branch_id', session('branch')->id);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
            Column::make('Acciones')
                ->label(function ($row) {
                    return view('dischargues.actions', ['dischargue' => $row]);
                }),
            Column::make("Nave", "vessel.name")
                ->searchable()
                ->sortable(),
            Column::make("Linea", "shippingLine.name")
                ->searchable()
                ->sortable(),
            //->format(fn($value, $row) => $row->shippingLine ? "{$row->shippingLine->name} ({$row->shippingLine->code})" : '-'),
            Column::make("Bl", "bl_number")
                ->searchable()
                ->sortable(),
            Column::make("ETA", "eta_date")
                ->sortable(),
            Column::make("Week", "week")
                ->searchable()
                ->sortable(),
            Column::make("Usuario", "creator.name")
                ->sortable(),
            Column::make("Sucursal", "branch.name")
                ->searchable()
                ->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            DateRangeFilter::make('Fecha ETA')
                ->config([
                    'altFormat' => 'F j, Y', // Date format that will be displayed once selected
                    'ariaDateFormat' => 'F j, Y', // An aria-friendly date format
                    'dateFormat' => 'Y-m-d', // Date format that will be received by the filter
                    'placeholder' => 'Introduzca el rango de fechas', // A placeholder value
                    'locale' => 'en',
                ])
                ->filter(function (Builder $builder, array $dateRange) {
                    $builder
                        ->whereDate('eta_date', '>=', $dateRange['minDate'])
                        ->whereDate('eta_date', '<=', $dateRange['maxDate']);
                }),
                SelectFilter::make('Linea')
                ->options(
                    [''=>'Todas'] + ShippingLine::query()
                    ->whereHas('dischargues',function($query){
                        $query->where('dischargues.branch_id', session('branch')->id);
                    })
                    ->pluck('name','id')
                    ->toArray()
                )
                ->filter(function($query,$value){
                    if($value){
                        $query->whereHas('shippingLine', function($q) use ($value){
                            $q->where('id',$value);
                        });
                    }
                })
        ];
    }

    public $dischargueId;

    public $vessels = [];


    public $openModal = false;
    public $selectedDischargeId = null;
    public $openModalAnulate = false;
    public $anulateReason = '';
    public $dischargue = [
        'shipping_line_id' => '',
        'vessel_id' => '',
        'bl_number' => '',
        'eta_date' => '',
        'week' => '',
        'voyage' => '',
        'manifiest_number' => ''
    ];

    public function setSelectedDischargeId($id)
    {
        $this->selectedDischargeId = $id;
        $this->openModalAnulate = true;
    }

    public function edit(Dischargue $dischargue)
    {
        if (!auth()->user()->can('discharges.edit')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        $this->dischargueId = $dischargue->id;
        $this->dischargue = $dischargue->only([
            'shipping_line_id',
            'vessel_id',
            'bl_number',
            'eta_date',
            'week',
            'voyage',
            'manifiest_number',
        ]);

        $this->dischargue['eta_date'] = Carbon::createFromFormat('d/m/Y', $this->dischargue['eta_date'])->format('Y-m-d');
        $this->vessels = Vessel::where('shipping_line_id', $this->dischargue['shipping_line_id'])
            ->get()
            ->toArray();

        $this->openModal = true;
    }

    #[On('vesselExternAdded')]
    public function updatedDischargueShippingLineId($value)
    {
        // Resetear el vessel seleccionado
        $this->dischargue['vessel'] = '';

        // Cargar las naves de la nueva línea naviera
        if (!empty($value)) {
            $this->vessels = Vessel::where('shipping_line_id', $value)
                ->get()
                ->toArray();
        } else {
            $this->vessels = [];
        }
    }

    public function save()
    {

        $this->validate([
            'dischargue.shipping_line_id' => 'required|numeric|exists:shipping_lines,id',
            'dischargue.vessel_id' => 'required|numeric|exists:vessels,id',
            'dischargue.eta_date' => 'required|date',
            'dischargue.bl_number' => 'nullable|string|min:5',
            'dischargue.manifiest_number' => 'nullable|numeric|min:2',
        ], [], [
            'dischargue.shipping_line_id' => 'Linea Naviera',
            'dischargue.vessel_id' => 'Nave',
            'dischargue.eta_date' => 'Fecha ETA',
            'dischargue.bl_number' => 'Número de BL',
            'dischargue.manifiest_number' => 'Número de Manifiesto',

        ]);

        $dischargue = Dischargue::find($this->dischargueId);
        $dischargue->update($this->dischargue);


        $this->reset('dischargueId', 'dischargue', 'openModal');
        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Descarga actualizada con Exito!',
            'icon' => 'success'
        ]);
    }

    public function destroy()
    {
        if (!auth()->user()->can('discharges.anulate')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        //valido que tengo el id
        if (!$this->selectedDischargeId) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'No se ha seleccionado ninguna descarga.',
                'icon' => 'error'
            ]);
            return;
        }

        if ($this->anulateReason === '' || strlen($this->anulateReason) < 5) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'El motivo de anulación es requerido y debe tener al menos 5 caracteres',
                'icon' => 'error'
            ]);
            return;
        }

        $dischargue = Dischargue::find($this->selectedDischargeId);

        //primero verifico que ningun contenedor ingresado se encuentre con status != ANUNCIADO
        $containersIn = ContainerOperationalTrace::query()
            ->whereHas(
                'gateInDetail.originable',
                fn($query) =>
                $query->where('originable_type', Dischargue::class)
                    ->where('originable_id', $dischargue->id)
            )
            ->where('status', '>', 1)
            ->count();

        if ($containersIn > 0) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'No se puede anular la Descarga, hay contenedores de esta que ya estan en patio',
                'icon' => 'error'
            ]);
            $this->reset('selectedDischargeId', 'openModalAnulate', 'anulateReason');
            return;
        }

        //actualizo a estado 0 todos los contenedores
        ContainerOperationalTrace::query()
            ->whereHas('gateInDetail.originable', fn($query) =>
            $query->where('originable_id', $dischargue->id)
                ->where('originable_type', "App\Models\Dischargue"))->update([
                'status' => 0
            ]);

        //actualizo la descarga añadiendo razon de anulacion y anulated by
        $dischargue->anulated_reason = $this->anulateReason;
        $dischargue->anulated_by = auth()->user()->id;
        $dischargue->save();
        $dischargue->delete();

        $this->reset('selectedDischargeId', 'openModalAnulate', 'anulateReason');

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Anuncio de Descarga anulado',
            'icon' => 'success'
        ]);
    }
}
