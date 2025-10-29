<?php

namespace App\Livewire\Operations\Works;

use DragonCode\Support\Facades\Helpers\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\works\Worker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;


class WorkerTable extends DataTableComponent
{
    protected $model = Worker::class;
    public $branches;
    public $identities;
    public $cargos;
    public $workerCreate = [
        'id' => '',
        'name' => '',
        'tipoDoc' => '',
        'numDoc' => '',
        'email' => '',
        'cargo' => '',
        'telefono' => '',
        'branch_id' => '',
    ];
    public $workerEdit = [
        'open' => false,
        'id' => '',
        'name' => '',
        'tipoDoc' => '',
        'numDoc' => '',
        'email' => '',
        'cargo' => '',
        'telefono' => '',
        'branch_id' => '',
    ];

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
        $this->setConfigurableAreas([
            'after-wrapper' => ['works.workers.modal']
        ]);
    }

    public function columns(): array
    {
        return [

            Column::make("Id", "id")
                ->deselected(),
            Column::make('Acciones')
                ->label(function ($row) {
                    return view('works.workers.actions', ['worker' => $row]);
                }),
            Column::make("Nombres", "name")
                ->searchable()
                ->sortable(),
            Column::make("Tipo Documento", "identity.description")
                ->sortable()
                ->format(fn($value) => '<div class="text-center">' . $value . '</div>')
                ->html(),
            Column::make("Numero Documento", "numDoc")
                ->searchable()
                ->sortable()
                ->format(fn($value) => '<div class="text-center">' . $value . '</div>')
                ->html(),
            Column::make("Sucursal", "branch.name"),
            Column::make("Email", "email")
                ->format(function ($value) {
                    return $value ?? '-';
                }),
            Column::make("Telefono", "telefono")
                ->sortable(),
            Column::make("Cargo", "cargo")
                ->sortable(),
            Column::make("Creado", "created_at")
                ->sortable(),

        ];
    }

    #[On('WorkerAdded')]
    public function builder(): Builder
    {
        return Worker::query()
            ->whereHas('branch', function ($query) {
                $query->where('branch_id', session('branch')->id);
            });
    }

    public function edit(Worker $worker)
    {
        $this->workerEdit = $worker->only([
            'id',
            'name',
            'tipoDoc',
            'numDoc',
            'email',
            'cargo',
            'telefono',
            'branch_id'
        ]);

        $this->workerEdit['open'] = true;
    }

    public function update()
    {
        $this->validate([
            'workerEdit.name' => 'required|string|max:255',
            'workerEdit.tipoDoc' => 'required|numeric|exists:identities,id',
            'workerEdit.numDoc' => [
                'required',
                Rule::when($this->workerEdit['tipoDoc'] === 1, 'unique|digits:8'),
                Rule::when($this->workerEdit['tipoDoc'] === 2, 'unique|digits:7'),
            ],
            'workerEdit.email' => 'nullable|string|email|max:100',
            'workerEdit.telefono' => 'nullable|numeric|digits:9',
            'workerEdit.cargo' => 'required|string',
            'workerEdit.branch_id' => 'required|exists:branches,id',
        ], [], [
            'workerEdit.name' => 'Nombres de Trabajador',
            'workerEdit.numDoc' => 'Número de Documento',
            'workerEdit.email' => 'Correo electronico',
            'workerEdit.telefono' => 'Número telefonico',
            'workerEdit.cargo' => 'Cargo de trabajador',
            'workerEdit.branch_id' => 'Sucursal',

        ]);
        $this->workerEdit['name'] = Str::upper($this->workerEdit['name']);
        Worker::find($this->workerEdit['id'])->update($this->workerEdit);

        $this->reset('workerEdit');
        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Trabajador actualizado correctamente',
            'icon' => 'success'
        ]);
    }

    public function destroy(Worker $worker)
    {
        $worker->delete();

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Trabajador eliminado correctamente',
            'icon' => 'success'
        ]);
    }
}
