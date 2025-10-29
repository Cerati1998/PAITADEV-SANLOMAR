<?php

namespace App\Livewire;

use App\Models\Container;
use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\ContainerOperationalTrace;
use App\Models\ContainerType;
use App\Models\Devolution;
use App\Models\GateInDetail;
use App\Models\ReeferTechnology;
use Illuminate\Database\Eloquent\Builder;

class DVContainerTable extends DataTableComponent
{
    protected $model = GateInDetail::class;
    public $originType; // Ej: App\Models\Dischargue o App\Models\Devolution
    public $originId;
    public $gateInInfo;
    public array $bulkActions = [
        'bulkAnulateConsult' => 'Anular Seleccionados'
    ];

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
        $this->setConfigurableAreas([
            'after-wrapper' => ['devolutions.add-container']
        ]);
        // Activar checkboxes de selección múltiple
        $this->setBulkActionsEnabled();
        $this->setBulkActions($this->bulkActions);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
               /*  Column::make('Acción')
                ->label(function($row){
                    return view('devolutions.actions-container',['trace' => $row->containerOperationalTrace]);
                }), */
            Column::make("Contenedor", "container.code")
                ->searchable()
                ->sortable(),
            Column::make("ISO", "container.iso_code")
                ->searchable()
                ->sortable(),
            Column::make("Tecnologia", "container.reefer_technology.name")
                ->format(function ($row) {
                    return $row ?? '-';
                })
                ->searchable()
                ->sortable(),
            Column::make("Tipo", "container.container_type.code"),
            Column::make("T. Descripcion", "container.container_type.description")
                ->sortable(),
            Column::make("Origén", "port.code")
                ->sortable(),
            Column::make("Condition", "container_condition")
                ->sortable(),
            Column::make("Estado", "containerOperationalTrace.status")
                ->format(function ($value, $row) {
                    //dd($row->containerOperationalTrace->currentStatus);
                    return view('components.badge', [
                        'icon' => $row->containerOperationalTrace->currentStatus['icon'] ?? '',
                        'styleBg' => $row->containerOperationalTrace->currentStatus['styleBg'] ?? '',
                        'slot' => $row->containerOperationalTrace->currentStatus['description'] ?? '',
                    ]);
                }),
                
        ];
    }

    public function builder(): Builder
    {
        $query = GateInDetail::query()->with(['originable', 'container', 'port', 'containerOperationalTrace']);

        if ($this->originType && $this->originId) {
            $query->whereHasMorph(
                'originable',
                $this->originType,
                function (Builder $q) {
                    $q->where('id', $this->originId);
                }
            )
                ->where('status', '>=', 0);;
        }

        return $query;
    }

    public function mount()
    {
        $this->gateInInfo = $this->originType::find($this->originId);
        $this->container['own_line_id'] = $this->gateInInfo->shipping_line_id;
    }

    // Propiedades computadas (se calculan solo cuando se acceden)
    public function getTypeContainersProperty()
    {
        /*         return ContainerType::all()->pluck('id', 'code')->toArray();*/
        return ContainerType::select('id', 'description')->orderBy('id', 'asc')->get();
    }

    public function getReeferTechnologiesProperty()
    {
        return ReeferTechnology::select('id', 'name')->orderBy('id', 'asc')->get();
    }


    public $openModal = false;
    public $container = [
        'code' => '',
        'iso_code' => '',
        'container_type_id' => null,
        'reefer_technology_id' => null,
        'port_id' => null,
        'own_line_id' => null,
    ];

    public function updatedContainerContainerTypeId($value)
    {
        if (!empty($value)) {
            $container = ContainerType::select('iso_code')
                ->where('id', $value)->get()->first();
            $this->container['iso_code'] = $container->iso_code;
        }
    }

    public function searchContainer()
    {
        $this->validate([
            'container.code' => 'required|size:11,regex:/^[A-Z]{4}\d{7}$/',
        ], [
            'container.regex' => 'Formato de Contenedor Invalido. Use el formato: CAIU1234567'
        ], [
            'container.code' => 'Número de Contenedor'
        ]);

        $container = Container::where('code', $this->container['code'])->first();
        if ($container) {
            $this->container = $container->only(['code', 'iso_code', 'container_type_id', 'reefer_technology_id', 'own_line_id']);
        } else {
            $this->dispatch('swal', [
                'title' => 'Advertencia!',
                'text' => 'Contenedor no encontrado, ingresalo manualmente',
                'icon' => 'warning'
            ]);
        }
    }

    public function save()
    {
        $this->validate([
            'container.code' => 'required|size:11,regex:/^[A-Z]{4}\d{7}$/',
            'container.iso_code' => 'required|min:4|max:5',
            'container.reefer_technology_id' => 'nullable|exists:reefer_technologies,id',
            'container.container_type_id' => 'required|exists:container_types,id',
            'container.port_id' => 'required|exists:ports,id'
        ], [
            'container.regex' => 'El formato del contenedor es inválido.',
        ], [
            'container.code' => 'Contenedor',
            'container.iso_code' => 'ISO',
            'container.reefer_technology_id' => 'Tecnología de Refrigeración',
            'container.container_type_id' => 'Tipo de Contenedor',
            'container.port_id' => 'Puerto de Origen',
        ]);
        //valido si el contenedor ya estaba en el repositorio, si no es asi lo creo
        $container = Container::updateOrCreate(['code' => $this->container['code']], [
            'iso_code' => $this->container['iso_code'],
            'container_type_id' => $this->container['container_type_id'],
            'reefer_technology_id' => $this->container['reefer_technology_id'],
            'own_line_id' => $this->container['own_line_id'],
        ]);

        //agrego validador, si el contenedor ya se encuentra anunciado aborto la subida
        $containerIn = ContainerOperationalTrace::where('container_id', $container->id)
            ->whereBetween('status', [1, 4])
            ->whereHas('gateInDetail.originable', function ($query) {
                $query->where('branch_id', session('branch')->id)
                    ->where('originable_type', Devolution::class);
            })
            ->exists();

        if ($containerIn) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => "El contenedor '{$this->container['code']}' se encuentra en el patio, no se puede anunciar nuevamente.",
                'icon' => 'error'
            ]);
            return;
        }

        //ahora ingresamos en el gate in detail y en el containerOperationalTrace
        //creo el detalle del Gate In
        $gateInDetail = GateInDetail::create([
            'container_id' => $container->id,
            'port_id' => $this->container['port_id'],
            'originable_id' => $this->originId,
            'originable_type' => $this->originType,
            'container_condition' => 'MTY',
        ]);

        //creo el registro para la trazabilidad operativa del Contenedor
        ContainerOperationalTrace::create([
            'gate_in_detail_id' => $gateInDetail->id,
            'container_id' => $container->id,
        ]);
        $this->reset('openModal', 'container');
        $this->dispatch('swal', [
            'title' => 'Éxito!',
            'text' => 'Contenedor agregado Correctamente a Gate In',
            'icon' => 'success'
        ]);
    }


    public function bulkAnulateConsult()
    {
        $this->dispatch('bulkAnulateConsult', [
            'title' => '¿Estas seguro de anular?',
            'msg' => 'No podrás trabajar los contenedores anulados'
        ]);
    }

    #[On('bulkAnulate')]
    public function bulkAnulate()
    {
        $selected = $this->getSelected();

        if (empty($selected)) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'Debe seleccionar mínimo un contenedor para anular.',
                'icon' => 'error'
            ]);
            return;
        }

        // Solo obtener los que tengan status = 1
        $validContainers = ContainerOperationalTrace::whereIn('id', $selected)
            ->where('status', 1)
            ->whereHas('gateInDetail.originable', function ($query) {
                $query->where('originable_id', $this->originId)
                    ->where('originable_type', $this->originType);
            })
            ->pluck('id')
            ->toArray();

        if (empty($validContainers)) {
            $this->dispatch('swal', [
                'title' => 'Atención!',
                'text' => 'Ninguno de los contenedores seleccionados puede ser anulado (solo se permite anunciados).',
                'icon' => 'error'
            ]);
            return;
        }

        // Actualizar solo los válidos
        ContainerOperationalTrace::whereIn('id', $validContainers)->update([
            'status' => 0
        ]);

        $this->clearSelected();

        $this->dispatch('swal', [
            'title' => 'Éxito!',
            'text' => 'Contenedores anulados con éxito.',
            'icon' => 'success'
        ]);
    }
}
