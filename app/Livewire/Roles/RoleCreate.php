<?php

namespace App\Livewire\Roles;

use Illuminate\Support\Str;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleCreate extends Component
{
    public $permissionGroups = [];
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

    public function closeModal()
    {
        $this->reset('openModal', 'role');
    }

    public function save()
    {
        if (!auth()->user()->can('roles.create')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        $this->validate([
            'role.name' => 'required|string|min:3|max:50|unique:roles,name',
            'role.permissions' => 'array',
            'role.permissions.*' => 'exists:permissions,name',
        ], [], [
            'role.name' => 'Nombre del Rol',
        ]);

        $role = Role::create(['name' => $this->role['name']]);
        $role->syncPermissions($this->role['permissions']);

        $this->reset('openModal', 'role');
        $this->dispatch('roleAdded');

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Rol creado correctamente',
            'icon' => 'success',
        ]);
    }

    public function render()
    {
        return view('livewire.roles.role-create');
    }
}
