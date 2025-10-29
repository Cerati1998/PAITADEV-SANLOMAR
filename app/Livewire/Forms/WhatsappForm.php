<?php

namespace App\Livewire\Forms;

use App\Models\Invoice;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;
use Livewire\Form;

class WhatsappForm extends Form
{
    public $openModal = false;

    public $document;
    public $client;

    public $phone_code = 51;
    public $phone_number;

    public function rules()
    {
        return [
            'phone_code' => 'required|numeric|exists:phone_codes,id',
            'phone_number' => 'required|numeric',
        ];
    }

}
