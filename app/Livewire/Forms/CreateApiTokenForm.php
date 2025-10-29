<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class CreateApiTokenForm extends Form
{
    public $name;
    public $permissions = [
        'create_invoice',
        'create_credit_note',
        'create_debit_note',
        'create_shipment',
        'create_summary',
        'create_voided',
    ];

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
        ];
    }
}
