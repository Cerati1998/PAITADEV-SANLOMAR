<?php

namespace App\Livewire\Masters;

use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\ReeferMachine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class MachineTable extends DataTableComponent
{
    protected $model = ReeferMachine::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
        $this->setConfigurableAreas([
            'after-wrapper' => ['masters.technologies.machine']
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
            Column::make('Acciones')
                ->label(function ($row) {
                    return view('masters.technologies.machine-actions', ['machine' => $row]);
                }),
            Column::make("Código", "code")
                ->searchable()
                ->sortable(),
            Column::make("Nombre", "name")
                ->searchable()
                ->sortable(),
            Column::make("Creado", "created_at")
                ->sortable(),
        ];
    }

    #[On('machineAdded')]
    public function builder(): Builder
    {
        return ReeferMachine::query();
    }

    public $openModal = false;
    public $machineId;
    public $machine = [
        'code' => '',
        'name' => ''
    ];

    public function edit(ReeferMachine $ReeferMachine)
    {
        $this->machine = $ReeferMachine->only('code', 'name');
        $this->machineId = $ReeferMachine->id;
        $this->openModal = true;
    }

    public function save()
    {
        $this->validate([
            'machine.code' => [
                'required',
                'string',
                'min:2',
                Rule::unique('reefer_machines', 'code')->ignore($this->machineId)
            ],
            'machine.name' => 'required|string|min:4'
        ], [], [
            'machine.code' => 'Código de Tecnologia Reefer',
            'machine.name' => 'Nombre de Tecnologia Reefer'
        ]);

        ReeferMachine::find($this->machineId)->update($this->machine);

        $this->reset('openModal', 'machineId', 'machine');
        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Tecnología Reefer actualizada correctamente',
            'icon' => 'success'
        ]);
    }

    public function destroy(ReeferMachine $ReeferMachine)
    {
        $ReeferMachine->delete();

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Tecnología Reefer eliminada correctamente',
            'icon' => 'success'
        ]);
    }
}
