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

class UserTable extends DataTableComponent
{

    public $branches;
    public $identities;
    public $userEdit = [
        'open' => false,
        'id' => '',
        'name' => '',
        'tipoDoc' => '',
        'numDoc' => '',
        'email' => '',
        'branch_id' => '',
    ];

    public function configure(): void
    {
        $this->setPrimaryKey('id');

        $this->setConfigurableAreas([
            'after-wrapper' => ['users.edit'],
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable(),
            Column::make("Name", "name")
                ->searchable()
                ->sortable(),
            Column::make("Email", "email")
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
            Column::make("Foto")
                ->label(function ($row) {
                    return '<img src="' . $row->profile_photo_url   . '" alt="Foto" class="h-12 w-12 rounded-full object-cover">';
                })
                ->html(),

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
            ->whereHas('companies', function ($query) {
                $query->where('company_id', session('company')->id);
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
        ], [], [
            'userEdit.tipoDoc' => 'Tipo de Documento',
            'userEdit.numDoc' => 'Número de Documento',
            'userEdit.branch_id' => 'sucursal',
        ]);

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
