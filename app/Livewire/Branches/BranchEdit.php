<?php

namespace App\Livewire\Branches;

use App\Models\Branch;
use Illuminate\Validation\Rule;
use Livewire\Component;

class BranchEdit extends Component
{

    public $branch;

    public $company_id, $name, $code, $phone, $email, $website, $ubigeo, $address;

    public function mount(Branch $branch)
    {
        $this->fill( 
            $branch->only('company_id', 'name', 'code', 'phone', 'email', 'website', 'ubigeo', 'address')
        );
    }

    public function save()
    {
        $this->code = str_pad($this->code, 4, '0', STR_PAD_LEFT);

        $this->validate([
            'company_id' => 'required',
            'code' => [
                'required',
                'numeric',
                'max:4',
                Rule::unique('branches', 'code')
                    ->where('company_id', session('company')->id)
                    ->ignore($this->branch->id),
            ],
            'name' => 'required',
            'address' => 'required',
            'ubigeo' => 'required|exists:districts,id',
            'phone' => 'nullable',
            'email' => 'nullable|email',
            'website' => 'nullable|url',
        ]);

        $this->branch->update([
            'code' => $this->code,
            'name' => $this->name,
            'address' => $this->address,
            'ubigeo' => $this->ubigeo,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
        ]);

        $this->dispatch('swal', [
            'icon' => 'success',
            'title'   => 'Sucursal actualizada',
            'text' => 'La sucursal se actualizó correctamente'
        ]);
    }

    public function render()
    {
        return view('livewire.branches.branch-edit');
    }
}
