<?php

namespace App\Livewire\Masters;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\ShippingLine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;

class LineTable extends DataTableComponent
{
    protected $model = ShippingLine::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
        $this->setConfigurableAreas([
            'after-wrapper' => ['masters.lines.modal']
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
            Column::make('Acciones')
                ->label(function ($row) {
                    return view('masters.lines.actions', ['line' => $row]);
                }),
            Column::make("Código", "code")
                ->searchable()
                ->sortable(),
            Column::make("Nombre", "name")
                ->searchable()
                ->sortable(),
            Column::make("Creado", "created_at")
                ->sortable(),
            Column::make("Actualizado", "updated_at")
                ->sortable(),
        ];
    }

    #[On('lineAdded')]
    public function builder(): Builder
    {
        return ShippingLine::query();
    }

    public $line_id;
    public $openModal = false;
    public $line = [
        'code' => '',
        'name' => '',
    ];

    public function closeModal()
    {
        $this->openModal = false;
        $this->line = [
            'code' => '',
            'name' => '',
        ];
    }

    public function edit(ShippingLine $line)
    {
        $this->line_id = $line->id;
        $this->line = $line->only(['code', 'name']);
        $this->openModal = true;
    }

    public function save()
    {
        $this->validate([
            'line.code' => [
                'required',
                'string',
                'min:3',
                Rule::unique('shipping_lines', 'code')->ignore($this->line_id)
            ],
            'line.name' => 'required|string|min:3'
        ], [], [
            'line.code' => 'Código de Linea',
            'line.name' => 'Nombre de Linea'
        ]);

        ShippingLine::find($this->line_id)->update($this->line);

        $this->reset('line_id', 'openModal', 'line');

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Linea actualizada correctamente',
            'icon' => 'success'
        ]);
    }

    public function destroy(ShippingLine $line)
    {
        $line->delete();
        $this->dispatch('swal', [
            'title' => 'Eliminado',
            'text' => 'La línea de envío ha sido eliminada correctamente.',
            'icon' => 'success',
        ]);
    }
}
