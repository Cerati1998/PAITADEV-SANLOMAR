<?php

namespace App\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Damage;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class DamageTable extends DataTableComponent
{
    protected $model = Damage::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');

        $this->setConfigurableAreas([
            'after-wrapper' => ['codes.damages.modal'],
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
            Column::make('Acciones')
                ->label(function ($row) {
                    return view('codes.damages.actions', ['damage' => $row]);
                }),
            Column::make("Codigo", "code")
                ->searchable()
                ->sortable(),
            Column::make("Descripción", "description")
                ->searchable()
                ->sortable(),
            Column::make("Creado", "created_at")
                ->sortable(),

        ];
    }

    public $openModal = false;
    public $damage_id;

    public $damage = [
        'code' => '',
        'description' => '',
    ];

    // Reseteo el formulario y el id al cerrar el modal
    public function updated($property, $value)
    {
        if ($property == 'openModal') {
            if (!$value && $this->damage_id) {
                $this->reset('damage_id', 'damage');
            }
        }
    }

    public function edit($id)
    {
        $this->damage_id = $id;
        $damage = Damage::find($id);
        $this->damage = $damage->only(['code', 'description']);
        Log::info($damage);
        $this->openModal = true;
    }

    public function closeModal()
    {
        $this->reset('openModal', 'damage_id', 'damage');
    }

    public function save()
    {

        $this->validate([
            'damage.code' => [
                'required',
                'string',
                'max:5',
                Rule::unique('damages', 'code')->ignore($this->damage_id),
            ],
            'damage.description' => 'required|string|max:255',
        ], [], [
            'damage.code' => 'Código',
            'damage.description' => 'Descripción',
        ]);
        try {
            if ($this->damage_id) {
                Damage::findOrFail($this->damage_id)->update($this->damage);
            } else {
                Damage::create($this->damage);
            }

            $this->dispatch('swal', [
                'icon' => 'success',
                'title' => $this->damage_id ? 'Código actualizado' :  'Código agregado',
                'text' => $this->damage_id ? 'El código de daño se actualizó correctamente' : 'El código de daño se agregó correctamente',
            ]);

            $this->reset('openModal', 'damage_id', 'damage');
            //$this->dispatch('refreshDamageTable');

        } catch (Exception $e) {
            Log::error('Error saving damage: ' . $e->getMessage());
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error al agregar código',
                'text' => $e->getMessage(),
            ]);
            return;
        }
    }

    public function destroy(Damage $damage)
    {
        try {
            $damage->delete();
            $this->dispatch('swal', [
                'icon' => 'success',
                'title' => 'Código eliminado',
                'text' => 'El código de daño se eliminó correctamente',
            ]);
        } catch (Exception $e) {
            Log::error('Error, ' . $e->getMessage());
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error al eliminar código',
                'text' => $e->getMessage(),
            ]);
        }
    }
}
