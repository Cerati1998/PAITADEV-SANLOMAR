<?php

namespace App\Livewire\Roles;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleTable extends DataTableComponent
{
    protected $model = Role::class;

    protected array $protectedRoles = ['Super', 'Admin'];

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'asc');
        $this->setConfigurableAreas([
            'after-wrapper' => ['roles.permissions-modal'],
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
            Column::make('Acciones')
                ->label(function ($row) {
                    return view('roles.actions', ['role' => $row, 'protectedRoles' => $this->protectedRoles]);
                }),
            Column::make("Nombre", "name")
                ->searchable()
                ->sortable(),
            Column::make("Permisos")
                ->label(fn($row) => $row->permissions_count),
            Column::make("Usuarios")
                ->label(fn($row) => $row->users_count),
            Column::make("Creado", "created_at")
                ->sortable(),
        ];
    }

    #[On('roleAdded')]
    public function builder(): Builder
    {
        return Role::query()
            ->withCount(['permissions', 'users']);
    }

    public $permissionGroups = [];

    public $roleId;
    public $openModal = false;
    public $role = [
        'name' => '',
        'permissions' => [],
    ];

    public function mount()
    {
        $this->permissionGroups = Permission::all()
            ->groupBy(fn($permission) => Str::before($permission->name, '.'))
            ->map(fn($group) => $group->pluck('name')->values()->all())
            ->sortKeys()
            ->all();
    }

    public function managePermissions(Role $role)
    {
        $this->roleId = $role->id;
        $this->role = [
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->toArray(),
        ];
        $this->openModal = true;
    }

    public function closeModal()
    {
        $this->reset('openModal', 'roleId', 'role');
    }

    public function updatePermissions()
    {
        if (!auth()->user()->can('roles.update')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        $this->validate([
            'role.permissions' => 'array',
            'role.permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::findOrFail($this->roleId);

        if (in_array($role->name, $this->protectedRoles) && !auth()->user()->hasRole('Super')) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No tienes permiso para modificar los permisos de este rol',
            ]);
            return;
        }

        $role->syncPermissions($this->role['permissions']);

        $this->reset('openModal', 'roleId', 'role');

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Permisos actualizados correctamente',
            'icon' => 'success',
        ]);
    }

    public function destroy(Role $role)
    {
        if (!auth()->user()->can('roles.delete')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        if (in_array($role->name, $this->protectedRoles)) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'Este rol no puede ser eliminado',
            ]);
            return;
        }

        if ($role->users()->exists()) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No puedes eliminar un rol que tiene usuarios asignados',
            ]);
            return;
        }

        $role->delete();

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Rol eliminado correctamente',
            'icon' => 'success',
        ]);
    }
}
