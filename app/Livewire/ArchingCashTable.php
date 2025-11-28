<?php

namespace App\Livewire;

use Carbon\Carbon;
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
    public $selectedCashId; // Para el modal de depósito
    public $showSummaryModal = false;
    public $summaryData = [];



    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
        $this->setConfigurableAreas([
            //'before-table' => ['cashes.stats'],
            'after-wrapper' => ['cashes.summary']
        ]);
        $this->setPerPageAccepted([10, 25, 50, 100]);
        $this->setPerPage(25);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->hideIf(true),
            Column::make('Acciones')
                ->label(function ($row) {
                    return view('cashes.actions', ['cash' => $row]);
                }),
            Column::make("Correlativo", "correlativo")
                ->sortable()
                ->searchable()
                ->format(function ($value, $row) {
                    $badge = $row->estado == 'abierta'
                        ? '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Abierta</span>'
                        : '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Cerrada</span>';
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
                                <div class="font-medium text-gray-900">' . Carbon::parse($value)->format('d/m/Y') . '</div>
                                <div class="text-gray-500">' . Carbon::parse($value)->format('H:i') . '</div>
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
                                <div class="font-medium text-gray-900">' . Carbon::parse($value)->format('d/m/Y') . '</div>
                                <div class="text-gray-500">' . Carbon::parse($value)->format('H:i') . '</div>
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

    public function register_deposit($cashId)
    {
        if (!auth()->user()->can('deposits.index')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        $this->selectedCashId = $cashId;
        $this->dispatch('open-deposit-modal', cashId: $cashId);
    }
    public function summary($cashId)
    {
        $archingCash = ArchingCash::with([
            'bills',
            'deposits',
            'openedBy:id,name',
            'closedBy:id,name'
        ])->find($cashId);

        if (!$archingCash) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'Caja no encontrada',
                'icon' => 'error'
            ]);
            return;
        }

        // CORRECCIÓN: Separar depósito inicial de los demás
        $depositoInicial = $archingCash->deposits
            ->where('tipo_deposito', 'inicial')
            ->sum('monto');

        // Solo sumar depósitos adicionales y reembolsos (NO el inicial)
        $depositosAdicionales = $archingCash->deposits
            ->whereIn('tipo_deposito', ['adicional', 'reembolso'])
            ->sum('monto');

        // Total de todos los depósitos (para mostrar)
        $totalDepositos = $archingCash->deposits->sum('monto');

        // Agrupar depósitos por tipo
        $depositosPorTipo = $archingCash->deposits->groupBy('tipo_deposito')->map(function ($deposits, $tipo) {
            $labels = [
                'inicial' => 'DEPÓSITO INICIAL',
                'adicional' => 'DEPÓSITO ADICIONAL',
                'reembolso' => 'REEMBOLSO'
            ];

            return [
                'label' => $labels[$tipo] ?? strtoupper($tipo),
                'monto' => $deposits->sum('monto'),
                'cantidad' => $deposits->count()
            ];
        })->values()->toArray();

        // Calcular totales de gastos
        $totalGastos = $archingCash->bills->sum('monto');

        // Listar gastos individualmente SIN agrupar por categoría
        $gastosDetalle = $archingCash->bills->map(function ($bill) {
            return [
                'producto' => $bill->producto ?? null,
                'descripcion' => $bill->detalle ?? null,
                'monto' => $bill->monto,
                'fecha' => $bill->created_at?->format('d/m/Y H:i'),
            ];
        })->toArray();


        // CORRECCIÓN: Calcular saldo correctamente
        // monto_inicial YA incluye el depósito inicial, solo sumamos los adicionales
        $saldoCalculado = $archingCash->monto_inicial + $depositosAdicionales - $totalGastos;

        // Calcular diferencia si la caja está cerrada
        $diferencia = 0;
        if ($archingCash->estado === 'cerrada' && $archingCash->monto_final) {
            $diferencia = $archingCash->monto_final - $saldoCalculado;
        }

        // Armar el payload
        $this->summaryData = [
            'caja' => [
                'correlativo' => $archingCash->correlativo,
                'estado' => $archingCash->estado,
                'fecha_inicio' => $archingCash->fecha_inicio->format('d/m/Y H:i'),
                'fecha_fin' => $archingCash->fecha_fin ? $archingCash->fecha_fin->format('d/m/Y H:i') : null,
                'abierta_por' => $archingCash->openedBy->name ?? 'N/A',
                'cerrada_por' => $archingCash->closedBy->name ?? 'N/A',
                'saldo_heredado' => $archingCash->saldo_heredado ?? 0,
                'monto_inicial' => $archingCash->monto_inicial,
                'monto_final' => $archingCash->monto_final,
            ],
            'depositos' => [
                'total' => $totalDepositos,
                'inicial' => $depositoInicial,
                'adicionales' => $depositosAdicionales, // Nuevo: separar para claridad
                'cantidad' => $archingCash->deposits->count(),
                'por_tipo' => $depositosPorTipo
            ],
            'gastos' => [
                'total' => $totalGastos,
                'cantidad' => $archingCash->bills->count(),
                'detalle' => $gastosDetalle
            ],
            'saldo_calculado' => $saldoCalculado,
            'diferencia' => $diferencia
        ];

        $this->selectedCashId = $cashId;
        $this->showSummaryModal = true;
    }
    public function closeSummaryModal()
    {
        $this->showSummaryModal = false;
        $this->summaryData = [];
        $this->selectedCashId = null;
    }

    public function closeCash(ArchingCash $archingCash)
    {
        // Cargar relaciones necesarias para el cálculo
        $archingCash->load(['deposits', 'bills']);

        $saldoHerencia = $archingCash->saldo_heredado ?? 0;
        // Total depósitos (inicial + adicionales + reembolsos)
        $totalDepositos = $archingCash->deposits->sum('monto');

        // Total de gastos
        $totalGastos = $archingCash->bills->sum('monto');

        // Calcular monto final
        $montoFinal = $saldoHerencia + $totalDepositos - $totalGastos;

        // Actualizar caja
        $archingCash->update([
            'monto_final' => $montoFinal,
            'estado' => 'cerrada',
            'fecha_fin' => Carbon::now(),
            'closed_by' => auth()->user()->id
        ]);

        $this->dispatch('cashUpdated');

        $this->dispatch('swal', [
            'title' => 'Éxito!',
            'text' => 'Caja cerrada correctamente',
            'icon' => 'success'
        ]);
    }

    public function voidCash(ArchingCash $archingCash)
    {
        if (!auth()->user()->can('cashes.void')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        //primero valido si no hay nuevas cajas
        $lastCaja = ArchingCash::where('branch_id', session('branch')->id)
            ->where('estado', "abierta")
            ->where('id', '!=', $archingCash->id)
            ->orderBy('fecha_fin', 'desc')
            ->count();

        //dd($lastCaja);

        if ($lastCaja > 0) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'No se puede reaperturar, ya hay una nueva caja abierta disponible',
                'icon' => 'warning'
            ]);
            return;
        }

        // Aquí redirigir al modal de cierre o abrir un modal
        $archingCash->update([
            'estado' => 'abierta',
        ]);

        $this->dispatch('cashUpdated');

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Caja reaperturada correctamente',
            'icon' => 'success'
        ]);
    }
}