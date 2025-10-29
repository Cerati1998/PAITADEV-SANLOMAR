<?php

namespace App\Livewire\Branches;

use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ManageVouchers extends Component
{
    public $branch;

    public $documents;
    public $addedDocuments;

    public $newDocument = [
        'document' => '',
        'serie' => '',
        'correlativo' => '',
    ];

    public function mount()
    {
        $this->documents = Document::all();
        $this->getAddedDocuments();
    }

    public function getAddedDocuments()
    {
        $this->addedDocuments = Document::whereHas('branches', function ($query) {
            $query->where('branch_id', $this->branch->id);
        })->with(['branches' => function ($query) {
            $query->where('branch_id', $this->branch->id);
        }])->get()
        ->toArray();
    }

    public function save()
    {
        /* $this->newDocument['serie'] = strtoupper($this->newDocument['serie']); */

        $this->validate([
            'newDocument.document' => 'required|exists:documents,id',
            'newDocument.serie' => [
                'required',
                'size:4',
                Rule::when($this->newDocument['document'] == '01', "regex:/^F/"),
                Rule::when($this->newDocument['document'] == '03', "regex:/^B/"),
                //Puede empezar con F o B
                Rule::when(in_array($this->newDocument['document'], ['07', '08']), ["regex:/^(F|B)/"]),
                Rule::when($this->newDocument['document'] == '09', "regex:/^T/"),
                Rule::unique('branch_company_document', 'serie')
                    ->where(function ($query) {
                        return $query->where('company_id', session('company')->id);
                    }),
            ],
            'newDocument.correlativo' => [
                'required',
                'min:1',
            ],
        ],[],[
            'newDocument.document' => 'tipo de documento',
            'newDocument.serie' => 'serie',
            'newDocument.correlativo' => 'correlativo',
        ]);

        $this->branch->documents()->attach($this->newDocument['document'], [
            'serie' => $this->newDocument['serie'],
            'correlativo' => $this->newDocument['correlativo'],
            'company_id' => session('company')->id,
        ]);

        $this->getAddedDocuments();
        $this->reset('newDocument');
    }


    public function deleteVoucher($id)
    {
        DB::table('branch_company_document')
            ->where('id', $id)
            ->delete();

        $this->getAddedDocuments();

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Voucher eliminado',
            'text' => 'El voucher ha sido eliminado correctamente',
        ]);
    }

    public function render()
    {
        return view('livewire.branches.manage-vouchers');
    }
    
}
