<?php

namespace App\Livewire\Branches;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;

class BranchTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');

        $this->setAdditionalSelects([
            'branches.id as id',
        ]);
    }

    public function columns(): array
    {
        return [

            Column::make("Código", "code")
                ->searchable()
                ->sortable(),

            Column::make("Nombre", "name")
                ->searchable()
                ->sortable(),

            Column::make("Teléfono", "phone")
                ->format(function($value) {
                    return $value ? $value : 'S/N';
                })
                ->searchable()
                ->sortable(),

            Column::make("Email")
                ->format(function($value) {
                    return $value ? $value : 'S/N';
                    
                })
                ->searchable()
                ->sortable(),

            Column::make('actions')
                ->label(function($row) {
                    return view('branches.actions', ['branch' => $row]);
                }),
        ];
    }

    public function builder(): Builder
    {
        return Branch::query()
            ->where('company_id', session('company')->id);
    }
}
