<?php

namespace App\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\ButtonGroupColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use App\Models\ArchingCash;

class ArchingCashTable extends DataTableComponent
{
    protected $model = ArchingCash::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
        /*         $this->setConfigurableAreas([
                    'before-table' => ['cashes.stats'],
                    'after-wrapper' => ['cashes.modal']
                ]); */
        $this->setPerPageAccepted([10, 25, 50, 100]);
        $this->setPerPage(25);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->hideIf(true),
            Column::make('Acciones')
            ->label(function($row){
                return view('cashes.actions',['cash'=>$row]);
            }),
            Column::make("Correlativo", "correlativo")
                ->sortable()
                ->searchable()
                ->format(function ($value, $row) {
                    $badge = $row->estado == 'abierta'
                        ? '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Abierta</span>'
                        : '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Cerrada</span>';
                    return '<div class="flex items-center gap-2">
                                <span class="font-semibold text-gray-900">' . $value . '</span>
                                ' . $badge . '
                            </div>';
                })
                ->html(),

            Column::make("Apertura", "fecha_inicio")
                ->sortable()
                ->format(function ($value) {
                    return '<div class="text-sm">
                                <div class="font-medium text-gray-900">' . \Carbon\Carbon::parse($value)->format('d/m/Y') . '</div>
                                <div class="text-gray-500">' . \Carbon\Carbon::parse($value)->format('H:i') . '</div>
                            </div>';
                })
                ->html(),

            Column::make("Cierre", "fecha_fin")
                ->sortable()
                ->format(function ($value) {
                    if (!$value) {
                        return '<span class="text-gray-400 text-sm">-</span>';
                    }
                    return '<div class="text-sm">
                                <div class="font-medium text-gray-900">' . \Carbon\Carbon::parse($value)->format('d/m/Y') . '</div>
                                <div class="text-gray-500">' . \Carbon\Carbon::parse($value)->format('H:i') . '</div>
                            </div>';
                })
                ->html(),

            Column::make("Monto Inicial", "monto_inicial")
                ->sortable()
                ->format(function ($value) {
                    return '<span class="text-sm font-semibold text-gray-900">S/ ' . number_format($value, 2) . '</span>';
                })
                ->html(),

            Column::make("Monto Final", "monto_final")
                ->sortable()
                ->format(function ($value) {
                    if (is_null($value)) {
                        return '<span class="text-gray-400 text-sm">-</span>';
                    }
                    return '<span class="text-sm font-semibold text-green-600">S/ ' . number_format($value, 2) . '</span>';
                })
                ->html(),

            Column::make("Saldo Disponible")
                ->label(function ($row) {
                    if ($row->estado == 1) {
                        $saldo = $row->monto_inicial - $row->total_salidas + $row->total_ingresos;
                        return '<span class="text-sm font-bold text-green-700">S/ ' . number_format($saldo, 2) . '</span>';
                    }
                    return '<span class="text-gray-400 text-sm">-</span>';
                })
                ->html(),

            Column::make("Abierto por", "opened_by")
                ->format(function ($value, $row) {
                    return '<div class="text-sm">
                                <div class="font-medium text-gray-900">' . ($row->openedBy->name ?? 'N/A') . '</div>
                            </div>';
                })
                ->html(),
        ];
    }

    #[On('archingAdded')]
    public function builder(): Builder
    {
        return ArchingCash::with(['branch', 'openedBy:id,name', 'closedBy:id,name'])
            ->addSelect(['arching_cashes.estado'])
            ->whereHas('branch', function ($branchQuery) {
                $branchQuery->where('id', session('branch')->id);
            })
            ->where('arching_cashes.estado', ">", 0);
    }

    public function delete($id)
    {
        $arching = ArchingCash::find($id);

        if (!$arching) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'Caja no encontrada',
                'icon' => 'warning'
            ]);
            return;
        }

        // Verificar si tiene gastos registrados
        if ($arching->bills()->count() > 0) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'No se puede eliminar una caja con gastos registrados',
                'icon' => 'error'
            ]);
            return;
        }

        // Cambiar estado a 0 (soft delete)
        $arching->update(['estado' => 0]);

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Caja eliminada correctamente',
            'icon' => 'success'
        ]);

    }

    public function closeCash($id)
    {
        // Aquí redirigir al modal de cierre o abrir un modal
        $this->dispatch('open-close-modal', cashId: $id);
    }
}