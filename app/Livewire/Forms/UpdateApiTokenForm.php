<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class UpdateApiTokenForm extends Form
{
    public $id;
    public $permissions = [];

    public function rules()
    {
        return [
            'permissions' => 'nullable|array',
        ];
    }
}
