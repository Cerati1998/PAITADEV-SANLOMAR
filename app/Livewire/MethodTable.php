<?php

namespace App\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Method;
use Exception;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;

class MethodTable extends DataTableComponent
{
    protected $model = Method::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
        $this->setConfigurableAreas([
            'after-wrapper' => ['codes.methods.modal'],
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
            Column::make('Acciones')
                ->label(function ($row) {
                    return view('codes.methods.actions', ['method' => $row]);
                }),
            Column::make("Código", "code")
                ->searchable()
                ->sortable(),
            Column::make("Descripción", "description")
                ->searchable()
                ->sortable(),
            Column::make("Creado", "created_at")
                ->sortable(),
        ];
    }

    #[On('methodAdded')]
    public function builder(): Builder
    {
        return Method::query();
    }

    public $method_id;
    public $openModal = false;
    public $method = [
        'code' => '',
        'description' => '',
    ];

    public function edit(Method $method)
    {
        $this->method_id = $method->id;
        $this->method = $method->only(['code', 'description']);
        $this->openModal = true;
    }

    public function closeModal()
    {
        $this->reset('openModal', 'method_id', 'method');
    }

    public function save()
    {
        $this->validate([
            'method.code' => [
                'required',
                'string',
                'min:2',
                Rule::unique('methods', 'code')->ignore($this->method_id)
            ],
            'method.description' => 'required|string|min:5',
        ], [], [
            'method.code' => 'Código de Método',
            'method.description' => 'Descripción del Método',
        ]);

        try {
            if (!Method::find($this->method_id)->update($this->method)) {
                throw new Exception("No se ha podido actualizar el Método. Verifique que exista");
            }

            $this->reset('openModal', 'method_id', 'method');
            $this->dispatch('swal', [
                'title' => 'Éxito',
                'text' => 'Método actualizado correctamente.',
                'icon' => 'success',
            ]);
        } catch (Exception $e) {
            $this->dispatch('swal', [
                'title' => 'Error',
                'text' => $e->getMessage(),
                'icon' => 'error'
            ]);
        }
    }

    public function destroy(Method $method)
    {
        try {
            $method->delete();
            $this->dispatch('swal', [
                'title' => 'Éxito',
                'text' => 'Método eliminado correctamente.',
                'icon' => 'success',
            ]);
        } catch (Exception $e) {
            $this->dispatch('swal', [
                'title' => 'Error',
                'text' => $e->getMessage(),
                'icon' => 'error',
            ]);
        }
    }
}
