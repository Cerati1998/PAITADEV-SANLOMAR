<?php

namespace App\Livewire\Operations\Inspections;

use App\Models\Container;
use App\Models\ContainerOperationalTrace;
use App\Models\inspections\LeakTest;
use App\Models\ShippingLine;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class LeakTestTable extends DataTableComponent
{

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
        $this->setConfigurableAreas([
            'after-wrapper' => ['inspections.leak.modal']
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
            Column::make('Acciones')
                ->label(function ($row) {
                    return view('inspections.leak.actions', ['leaktest' => $row]);
                }),
            Column::make("Serie", "serie")
                ->sortable(),
            Column::make("Correlativo", "correlativo")
                ->format(function ($value) {
                    return str_pad($value, 6, 0, STR_PAD_LEFT);
                }),
            Column::make('Contenedor', 'trace.container')
                ->label(function ($row) {
                    //dd($row);
                    return $row->trace?->container->code ?? 'N/A';
                })
                ->searchable(),
            Column::make("Estado", "work_orders.status")
                ->label(function ($row) {
                    $icon = match ($row->status) {
                        'ok' => '<i class="text-xl fas fa-thumbs-up text-green-500" title="OK TEST"></i>',
                        'fail' => '<i class="text-xl fas fa-thumbs-down text-red-500" title="FAIL TEST"></i>',
                        default => ''
                    };
                    return '<div class="flex justify-center">' . $icon . '</div>';
                })->html(),
            Column::make("Inicio", "started_at")
                ->sortable(),
            Column::make("Tiempo", "hours")
                ->format(function ($value) {
                    return $value . ' mins';
                }),
            Column::make('Inspector', 'inspector')
                ->label(function ($row) {
                    //dd($row);
                    return $row?->inspector->name ?? 'N/A';
                })
                ->searchable(),
        ];
    }

    #[On('leakTestCreated')]
    public function builder(): Builder
    {
        return LeakTest::query()
            ->with([
                'branch:id',
                'trace.container:id,code',
                'inspector:id,name'
            ])
            ->addSelect(['leak_tests.*'])
            ->where('leak_tests.branch_id', session('branch')->id);
    }


    public function filters(): array
    {
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
                        ->whereDate('leak_tests.started_at', '>=', $dateRange['minDate'])
                        ->whereDate('leak_tests.started_at', '<=', $dateRange['maxDate']);
                }),
            SelectFilter::make('Linea')
                ->options(
                    ['' => 'Todas'] + ShippingLine::query()
                        ->whereHas('dischargues', function ($query) {
                            $query->whereHas('gateInDetails.containerOperationalTrace.LeakTest')
                                ->whereHas('gateInDetails.containerOperationalTrace', function ($traceQuery) {
                                    $traceQuery->whereHas('gateInDetail', function ($gateQuery) {
                                        $gateQuery->whereHasMorph('originable', ['App\Models\Dischargue'], function ($originableQuery) {
                                            $originableQuery->where('branch_id', session('branch')->id);
                                        });
                                    });
                                });
                        })
                        ->orWhereHas('devolutions', function ($query) {
                            $query->whereHas('gateInDetails.containerOperationalTrace.LeakTest')
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

        ];
    }

    public $openModal = false;
    public $leaktestId;
    public $leaktest = [
        'serie' => '',
        'correlativo' => 0,
        'started_at' => null,
        'finished_at' => null,
        'hours' => null,
        'status' => 'pending',
        'branch_id' => null,
        'trace_id' => null,
        'inspector_id' => null,
    ];

    public $containerData = [
        'machine' => '',
        'technology' => '',
        'year' => '',
    ];


    #[Computed()]
    public function traces()
    {
        $traces = ContainerOperationalTrace::with([
            'gateInDetail.originable.branch',
            'container.container_type'
        ])
            ->whereHas('gateInDetail.originable.branch', function ($query) {
                $query->where('branch_id', session('branch')->id);
            })
            ->whereHas('container.container_type', function ($typeQuery) {
                $typeQuery->whereIn('iso_code', ['45R1', '22R1']);
            })
            ->where('container_operational_traces.status', '<', 5)
            ->orderBy('id', 'desc')
            ->get();

        return $traces->map(function ($trace) {
            return [
                'id' => $trace->id,
                'name' => $trace->container->code ?? 'N/A',
                'description' => strtoupper($trace->nave ?? '') . ' - ' . strtoupper($trace->linea ?? ''),
            ];
        })->values()->toArray();
    }

    public function edit(LeakTest $leakTest)
    {
        if (!auth()->user()->can('inspections.leak.edit')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        try {
            $this->leaktest = $leakTest->only([
                'serie',
                'correlativo',
                'started_at',
                'finished_at',
                'hours',
                'status',
                'branch_id',
                'trace_id',
                'inspector_id',
            ]);

            //  buscar el trace
            $trace = ContainerOperationalTrace::with([
                'container.reefer_technology',
                'container.machine'
            ])->find($this->leaktest['trace_id']);

            if (!$trace) {
                throw new Exception('No se encontró el trace seleccionado');
            }

            // Acceder al contenedor a través del trace
            $container = $trace?->container;

            if (!$container) {
                throw new Exception('No se encontró el contenedor asociado al trace');
            }

            // Asignar los datos del contenedor
            $this->containerData['technology'] = $container->reefer_technology?->name ?? 'N/A';
            $this->containerData['machine'] = $container->machine?->code ?? 'N/A';
            $this->containerData['year'] = $container->build_year ?? 'N/A';

            //  Asignar las horas de trabajo por tecnología/máquina
            $machineCode = strtolower($this->containerData['machine']);

            $this->leaktest['hours'] = match ($machineCode) {
                'daikin' => 3,
                'starcool' => 12,
                default => 5,
            };

            //asigno el id
            $this->leaktestId = $leakTest->id;
            $this->openModal = true;
        } catch (Exception $e) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => $e->getMessage(),
                'icon' => 'error'
            ]);
        }
    }

    public function save()
    {
        if (!auth()->user()->can('inspections.leak.update')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        DB::transaction(function () {
            $this->validate([
                'leaktest.serie' => 'required|string|max:4',
                'leaktest.correlativo' => 'numeric|required',
                'leaktest.started_at' => 'date|required',
                'leaktest.finished_at' => 'required|date',
                'leaktest.hours' => 'required|numeric|max:24',
                'leaktest.status' => 'required|in:ok,fail',
                'leaktest.branch_id' => 'required|exists:branches,id',
                'leaktest.trace_id' => [
                    'required',
                    'exists:container_operational_traces,id',
                    Rule::unique('leak_tests', 'trace_id')->ignore($this->leaktestId)
                ],
                'leaktest.inspector_id' => 'required|exists:users,id'

            ], [
                'leaktest.trace_id.unique' => 'El contenedor seleccionado ya cuenta con Leak Test'
            ], [
                'leaktest.serie' => 'Serie de documento',
                'leaktest.correlativo' => 'Correlativo de documento',
                'leaktest.started_at' => 'Fecha de inicio',
                'leaktest.finished_at' => 'Fecha de termino',
                'leaktest.hours' => 'Horas de test',
                'leaktest.status' => 'Estado final de Test',
                'leaktest.branch_id' => 'Sucursal',
                'leaktest.trace_id' => 'Contenedor',
                'leaktest.inspector_id' => 'Inspector'
            ]);

            $leaktest = LeakTest::findOrFail($this->leaktestId);
            $leaktest->update($this->leaktest);
        });

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Leak Test actualizado correctamente',
            'icon' => 'success'
        ]);
        $this->dispatch('leakTestCreated');
        $this->reset('openModal', 'leaktest', 'containerData', 'leaktestId');
    }

    public function destroy(LeakTest $leakTest)
    {
        if (!auth()->user()->can('inspections.leak.delete')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Acceso no autorizado',
                'text' => 'No tienes el permiso para ejecutar esta acción',
            ]);
            return;
        }

        if ($leakTest->status !== 'borrador') {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'El Leak Test ya se encuentra completado',
                'icon' => 'warning'
            ]);

            return;
        }

        $leakTest->delete();

        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Leak Test eliminado correctamente',
            'icon' => 'success'
        ]);
    }
}
