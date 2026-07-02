<?php

namespace App\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Spatie\Permission\Models\Role;

class UserTable extends DataTableComponent
{

    public $branches;
    public $identities;
    public $roles;
    public $userEdit = [
        'open' => false,
        'id' => '',
        'name' => '',
        'tipoDoc' => '',
        'numDoc' => '',
        'email' => '',
        'role' => '',
        'branch_id' => '',
    ];
    public $password = NULL;

    public $roleChange = [
        'open' => false,
        'id' => '',
        'name' => '',
        'role_id' => '',
    ];

    public function configure(): void
    {
        $this->setPrimaryKey('id');

         $this->setConfigurableAreas([
            'after-wrapper' => ['users.modal']
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
            Column::make("Nombres", "name")
                ->searchable()
                ->sortable(),
            Column::make("Email", "email")
                ->sortable(),
            Column::make("Tipo Doc", "identity.description")
                ->sortable()
                ->format(fn($value) => '<div class="text-center">' . $value . '</div>')
                ->html(),
            Column::make("Nro Documento", "numDoc")
                ->searchable()
                ->sortable()
                ->format(fn($value) => '<div class="text-center">' . $value . '</div>')
                ->html(),
            Column::make("Foto")
                ->label(function ($row) {
                    return '<img src="' . $row->profile_photo_url . '" alt="Foto" class="h-12 w-12 rounded-full object-cover">';
                })
                ->html(),
            Column::make("Rol")
                ->label(function ($row) {
                    return $row->roles->pluck('name')->implode(', ') ?: 'Sin rol';
                }),

            Column::make("Sucursal")
                ->label(function ($row) {
                    return $row->branch->name;
                }),

            Column::make('actions')
                ->label(function ($row) {
                    return view('users.actions', ['user' => $row]);
                }),
        ];
    }

    #[On('UserAdded')]
    public function builder(): Builder
    {
        return User::query()
            ->with(['roles', 'branches', 'identity'])
            ->whereHas('companies', function ($query) {
                $query->where('company_id', session('company')->id);
            })
            ->whereDoesntHave('roles', function ($query) {
                $query->where('name', 'Super');
            });
    }

    public function edit(User $user)
    {
        $this->userEdit = [
            'open' => true,
            'id' => $user->id,
            'name' => $user->name,
            'tipoDoc' => $user->tipoDoc,
            'numDoc' => $user->numDoc,
            'email' => $user->email,
            'branch_id' => $user->branch->id,
        ];
    }

    public function update()
    {
        $this->validate([
            'userEdit.tipoDoc' => 'required|exists:identities,id',
            'userEdit.numDoc' => [
                'required',
                Rule::when($this->userEdit['tipoDoc'] === '1', 'numeric|digits:8'),
                Rule::when($this->userEdit['tipoDoc'] === '2', 'numeric|digits:7'),
                Rule::unique('users', 'numDoc')->ignore($this->userEdit['id'])
            ],
            'userEdit.branch_id' => 'required|exists:branches,id',
            'password' => 'nullable|min:5'
        ], [], [
            'userEdit.tipoDoc' => 'Tipo de Documento',
            'userEdit.numDoc' => 'Número de Documento',
            'userEdit.branch_id' => 'sucursal',
            'password' => 'Contraseña de Usuario'
        ]);
        //si la clave no es nula se agrega al payload
        if ($this->password) {
            $this->userEdit['password'] = bcrypt($this->password);
        }
        //actualizo info de usuario
        User::find($this->userEdit['id'])->update($this->userEdit);
        //actualizo sucursal
        DB::table('branch_company_user')
            ->where('user_id', $this->userEdit['id'])
            ->where('company_id', session('company')->id)
            ->update(['branch_id' => $this->userEdit['branch_id']]);

        $this->reset('userEdit');
        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Usuario actualizado Correctamente',
            'icon' => 'success'
        ]);
    }

    public function editRole(User $user)
    {
        $this->roleChange = [
            'open' => true,
            'id' => $user->id,
            'name' => $user->name,
            'role_id' => $user->roles->first()?->id ?? '',
        ];
    }

    public function updateRole()
    {
        if (!auth()->user()->can('users.role.update')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        $this->validate([
            'roleChange.role_id' => 'required|exists:roles,id',
        ], [], [
            'roleChange.role_id' => 'Rol',
        ]);

        if ($this->roleChange['id'] == auth()->id()) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No puedes cambiar tu propio rol',
            ]);
            return;
        }

        $role = Role::findOrFail($this->roleChange['role_id']);

        if ($role->name === 'Super' && !auth()->user()->hasRole('Super')) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No tienes permiso para asignar el rol Super',
            ]);
            return;
        }

        User::findOrFail($this->roleChange['id'])->syncRoles([$role->name]);

        $this->reset('roleChange');

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Rol actualizado correctamente',
            'icon' => 'success',
        ]);
    }

    public function destroy($userId)
    {
        if ($userId == auth()->id()) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No puedes eliminar tu propio usuario',
            ]);

            return;
        }

        DB::table('company_user')
            ->where('user_id', $userId)
            ->where('company_id', session('company')->id)
            ->delete();

        DB::table('branch_company_user')
            ->where('user_id', $userId)
            ->where('company_id', session('company')->id)
            ->delete();
    }
}
