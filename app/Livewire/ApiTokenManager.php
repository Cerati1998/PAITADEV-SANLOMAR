<?php

namespace App\Livewire;

use App\Livewire\Forms\CreateApiTokenForm;
use App\Livewire\Forms\UpdateApiTokenForm;
use App\Models\Company;
use Livewire\Component;

class ApiTokenManager extends Component
{
    public $company;

    public $permissions = [
        'create_invoice',
        'create_credit_note',
        'create_debit_note',
        'create_shipment',
        'create_summary',
        'create_voided',
    ];

    public createApiTokenForm $createApiTokenForm;

    public $displayingToken = false;
    public $plainTextToken;

    public $managingApiTokenPermissions = false;
    public UpdateApiTokenForm $updateApiTokenForm;

    /* public function mount()
    {
        $this->company = Company::find(session('company')->id);
    } */

    public function createApiToken()
    {
        $this->createApiTokenForm->validate();

        $this->plainTextToken = $this->company->createToken(
            $this->createApiTokenForm->name,
            $this->createApiTokenForm->permissions
        )->plainTextToken;

        $this->displayingToken = true;
    }

    public function manageApiTokenPermissions($tokenId)
    {
        $token = $this->company->tokens()->findOrFail($tokenId);

        $this->updateApiTokenForm->fill([
            'id' => $token->id,
            'permissions' => $token->abilities,
        ]);

        $this->managingApiTokenPermissions = true;
    }

    public function updateApiToken()
    {
        $this->updateApiTokenForm->validate();

        $token = $this->company->tokens()->findOrFail($this->updateApiTokenForm->id);

        $token->forceFill([
            'abilities' => $this->updateApiTokenForm->permissions,
        ])->save();

        $this->managingApiTokenPermissions = false;
    }

    public function deleteApiToken($tokenId)
    {
        $this->company->tokens()->findOrFail($tokenId)->delete();
    }

    public function render()
    {
        return view('livewire.api-token-manager');
    }
}
