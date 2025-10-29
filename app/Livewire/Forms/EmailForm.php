<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class EmailForm extends Form
{
    public $openModal = false;

    public $document;
    public $client;

    public $value;

    public function rules()
    {
        return [
            'value' => 'required|email',
        ];
    }
}
