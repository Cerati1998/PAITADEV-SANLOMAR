<?php

namespace App\Livewire\Operations\Inspections;

use App\Models\Container;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\inspections\EirInspection;
use App\Models\ShippingLine;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class EirTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
        $this->setConfigurableAreas([
            'after-wrapper' => ['inspections.eir.detail']
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
            Column::make('Acciones')
                ->label(function ($row) {
                    return view('inspections.eir.actions', ['eir' => $row]);
                }),
            Column::make("Inicio", "started_at")
                ->sortable(),
            Column::make("Nave", "trace")
                ->label(fn($row) => $row->trace?->nave)
                ->searchable(),
            Column::make("Estado", "status")
                ->label(callback: function ($row) {
                    $icon = match ($row->status) {
                        'completado' => '<i class="text-xl fas fa-circle-check text-green-500" title="Culminado"></i>',
                        'borrador' => '<i class="text-xl fas fa-spinner text-red-500" title="Borrador"></i>',
                        'cotizado' => '<i class="text-xl fas fa-hourglass-half text-blue-500" title="Por aprobar estimado"></i>',
                        default => ''
                    };

                    return '<div class="flex justify-center">' . $icon . '</div>';
                })->html(),
            Column::make("Serie", "serie")
                ->sortable(),
            Column::make("Correlativo", "correlativo")
                ->format(function ($value) {
                    return str_pad($value, 6, '0', STR_PAD_LEFT);
                })
                ->sortable(),
            Column::make("Contenedor", "trace.container.code")
                ->searchable()
                ->sortable(),
            Column::make("Inspector", "inspector.name")
                ->sortable(),
        ];
    }

    public function builder(): Builder
    {
        return EirInspection::with(['trace', 'inspector'])
            ->select('eir_inspections.*')
            ->whereDate('eir_inspections.created_at', '>=', now()->subMonths(6))
            ->whereHas('trace', function ($query) {
                $query->whereHas('gateInDetail', function ($q) {
                    $q->whereHasMorph('originable', ['App\Models\Dischargue', 'App\Models\Devolution'], function ($morphQuery) {
                        $morphQuery->where('branch_id', session('branch')->id);
                    });
                });
            });
    }


    public function filters(): array
    {
        $branchId = session('branch')->id;

        return [
            DateRangeFilter::make('Fecha Creado')
                ->config([
                    'altFormat' => 'F j, Y', // Date format that will be displayed once selected
                    'ariaDateFormat' => 'F j, Y', // An aria-friendly date format
                    'dateFormat' => 'Y-m-d', // Date format that will be received by the filter
                    'placeholder' => 'Introduzca el rango de fechas', // A placeholder value
                    'locale' => 'en',
                ])
                ->filter(function (Builder $builder, array $dateRange) {
                    $builder
                        ->whereDate('eir_inspections.started_at', '>=', $dateRange['minDate'])
                        ->whereDate('eir_inspections.closed_at', '<=', $dateRange['maxDate']);
                }),
            SelectFilter::make('Linea')
                ->options(
                    ['' => 'Todas'] + ShippingLine::query()
                        ->whereHas('dischargues', function ($query) {
                            $query->whereHas('gateInDetails.containerOperationalTrace.EirInspection')
                                ->whereHas('gateInDetails.containerOperationalTrace', function ($traceQuery) {
                                    $traceQuery->whereHas('gateInDetail', function ($gateQuery) {
                                        $gateQuery->whereHasMorph('originable', ['App\Models\Dischargue'], function ($originableQuery) {
                                            $originableQuery->where('branch_id', session('branch')->id);
                                        });
                                    });
                                });
                        })
                        ->orWhereHas('devolutions', function ($query) {
                            $query->whereHas('gateInDetails.containerOperationalTrace.EirInspection')
                                ->whereHas('gateInDetails.containerOperationalTrace', function ($traceQuery) {
                                    $traceQuery->whereHas('gateInDetail', function ($gateQuery) {
                                        $gateQuery->whereHasMorph('originable', ['App\Models\Devolution'], function ($originableQuery) {
                                            $originableQuery->where('branch_id', session('branch')->id);
                                        });
                                    });
                                });
                        })
                        ->pluck('name', 'id')
                        ->toArray()
                )
                ->filter(function (Builder $builder, $shippingLineId) {
                    if (!empty($shippingLineId)) {
                        $builder->whereHas('trace', function ($traceQuery) use ($shippingLineId) {
                            $traceQuery->whereHas('gateInDetail', function ($gateQuery) use ($shippingLineId) {
                                $gateQuery->where(function ($originQuery) use ($shippingLineId) {
                                    // Para Descarga (Dischargue)
                                    $originQuery->whereHasMorph('originable', ['App\Models\Dischargue'], function ($dischargueQuery) use ($shippingLineId) {
                                        $dischargueQuery->where('shipping_line_id', $shippingLineId);
                                    })
                                        // Para Devolución (Devolution)
                                        ->orWhereHasMorph('originable', ['App\Models\Devolution'], function ($devolutionQuery) use ($shippingLineId) {
                                        $devolutionQuery->where('shipping_line_id', $shippingLineId);
                                    });
                                });
                            });
                        });
                    }
                }),
            SelectFilter::make('Tipo')
                ->options([
                    '' => 'Todas los métodos',
                    'fast_repair' => 'Rápida',
                    'normal_repair' => 'Normal',
                    'quoted_repair' => 'Monto Alto',
                ])
                ->filter(function (Builder $builder, $value) {
                    if ($value !== '') {
                        $builder->where($value, true);
                    }
                }),
            SelectFilter::make('Estado')
                ->options(
                    ['' => 'Todos'] + EirInspection::query()
                        ->whereHas('trace', function ($query) use ($branchId) {
                            $query->whereHas('gateInDetail', function ($q) use ($branchId) {
                                $q->whereHasMorph('originable', ['App\Models\Dischargue', 'App\Models\Devolution'], function ($morphQuery) use ($branchId) {
                                    $morphQuery->where('branch_id', $branchId);
                                });
                            });
                        })
                        ->distinct()
                        ->pluck('eir_inspections.status', 'status')
                        ->filter() // Elimina valores null/empty
                        ->toArray()
                )
                ->filter(function (Builder $builder, $status) {
                    if (!empty($status)) {
                        $builder->where('eir_inspections.status', $status);
                    }
                })

            /* SelectFilter::make('Contenedor')
                ->options(
                    ['Todos' => ''] + Container::query()
                        ->whereHas('gateInDetails.containerOperationalTrace.EirInspection')
                        ->whereHas('gateInDetails', function ($gateQuery) use ($branchId) {
                            $gateQuery->where(function ($originQuery) use ($branchId) {
                                // Para Descarga (Dischargue)
                                $originQuery->whereHasMorph('originable', ['App\Models\Dischargue'], function ($dischargueQuery) use ($branchId) {
                                    $dischargueQuery->where('branch_id', $branchId);
                                })
                                    // Para Devolución (Devolution)
                                    ->orWhereHasMorph('originable', ['App\Models\Devolution'], function ($devolutionQuery) use ($branchId) {
                                        $devolutionQuery->where('branch_id', $branchId);
                                    });
                            });
                        })
                        ->pluck('code', 'id')
                        ->toArray()
                )
                ->filter(function (Builder $builder, $containerId) {
                    if (!empty($containerId)) {
                        $builder->whereHas('trace', function ($traceQuery) use ($containerId) {
                            $traceQuery->where('container_id', $containerId);
                        });
                    }
                }) */
        ];
    }

    public function destroy(EirInspection $eirInspection)
    {
        if (!auth()->user()->can('inspections.eir.delete')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        if ($eirInspection->status !== 'borrador') {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'El EIR ya se encuentra completado',
                'icon' => 'warning'
            ]);

            return;
        }

        $eirInspection->delete();

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'EIR eliminado correctamente',
            'icon' => 'success'
        ]);
    }
}
