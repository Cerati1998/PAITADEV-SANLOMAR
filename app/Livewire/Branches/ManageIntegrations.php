<?php

namespace App\Livewire\Branches;

use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Branch;
use App\Models\BranchIntegration;
use App\Models\Integration;
use Illuminate\Database\Eloquent\Builder;

class ManageIntegrations extends DataTableComponent
{
    protected $model = BranchIntegration::class;
    public $integrations;
    public $branch;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
        $this->setConfigurableAreas([
            'after-wrapper' => ['branches.add-integrate']
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
            Column::make("Nombre", "integration.name")
                ->sortable()
                ->searchable(),
            Column::make("Descripción", "integration.description"),
            Column::make("Endpoint", "integration.base_url")
                ->sortable()
                ->searchable(),

            Column::make("Active", "active"),
            Column::make('Creado Por', 'creator.name')
                ->searchable(),
            Column::make('Acciones')
                ->label(function ($row) {
                    return <<<HTML
            <div class="w-full">
                <button
                    type="button"
                    class="btn btn-outline-red w-full flex justify-center"
                    onclick="confirmDelete({$row->id})"
                >
                    Eliminar
                </button>
            </div>
        HTML;
                })
                ->html()
        ];
    }

    public function builder(): Builder
    {
        $this->integrations = Integration::all();
        $this->branch = session('branch')->id;
        return BranchIntegration::query()
            ->with(['branch', 'integration', 'creator'])
            ->where('branch_id', session('branch')->id)
            ->where('active', true);
    }

    public $openModal = false;
    public $integrate = [
        'integration_id' => '',
        'branch_id' => '',
        'api_token' => '',
        'created_by' => ''
    ];

    public function save()
    {
        $this->validate([
            'integrate.integration_id' => 'required|integer|exists:integrations,id',
            'integrate.api_token' => 'required|string|min:64|max:255|unique:branch_integrations,api_token'
        ], [
            'integrate.api_token.required' => 'Asegurate de primero generar y guardar el token'
        ], [
            'integrate.integration_id' => 'Tipo de API para Integración',
            'integrate.api_token' => 'Credencial Token para integración'
        ]); #

        $this->integrate['branch_id'] = session('branch')->id;
        $this->integrate['api_token'] = hash('sha256', $this->integrate['api_token']);
        $this->integrate['created_by'] = auth()->user()->id;

        BranchIntegration::create($this->integrate);

        $this->reset('integrate', 'openModal');
        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'API para integración generada con Exito',
            'icon' => 'success'
        ]);
    }

    public function generatetoken()
    {
        $this->integrate['api_token'] = Str::random(64);
    }

    public function destroy(BranchIntegration $branchIntegration)
    {
        $branchIntegration->active = false;
        $branchIntegration->save();

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Token e integración deshabilitados con Éxito',
            'icon' => 'success'
        ]);
    }
}
