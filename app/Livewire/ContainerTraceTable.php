<?php

namespace App\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\ContainerOperationalTrace;
use App\Models\ShippingLine;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class ContainerTraceTable extends DataTableComponent
{
    protected $model = ContainerOperationalTrace::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
            Column::make('Actions')
                ->label(function ($row) {
                    return view('containers.actions', ['trace' => $row]);
                }),
            Column::make("Contenedor", "container.code")
                ->searchable()
                ->sortable(),
            Column::make("ISO", "container.iso_code")
                ->searchable()
                ->sortable(),
            /*             Column::make("Origen")
                ->label(function ($row) {
                    return $row->origen ?? '-';
                }), */

            Column::make("Linea")
                ->label(function ($row) {
                    return $row->linea ?? '-';
                }),
            Column::make("EIR", 'EirInspection')
                ->label(function ($row) {
                    if ($row->EirInspection->isEmpty()) {
                        return '<span class="text-gray-400 text-sm">-</span>';
                    }

                    $eir = $row->EirInspection->sortByDesc('created_at')->first();
                    $correlativo = str_pad($eir->correlativo, 6, '0', STR_PAD_LEFT);
                    $count = $row->EirInspection->count();
                    $badge = $count > 1
                        ? sprintf(
                            " <span class='inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800'>+%d</span>",
                            $count
                        )
                        : '';

                    return sprintf(
                        '<a class="text-blue-600 hover:underline font-medium" href="%s" target="_blank">%s-%s</a>%s',
                        route('inspections.eir.edit', $eir->id),
                        $eir->serie,
                        $correlativo,
                        $badge
                    );
                })
                ->html(),

            Column::make("Leak Test", 'LeakTest')
                ->label(function ($row) {
                    if (!$row->LeakTest) {
                        return '<span class="text-gray-400 text-sm">-</span>';
                    }

                    $leakTest = $row->LeakTest;

                    $statusConfig = [
                        'pending' => [
                            'label' => 'Pendiente',
                            'color' => 'bg-yellow-100 text-yellow-800'
                        ],
                        'ok' => [
                            'label' => 'OK',
                            'color' => 'bg-green-100 text-green-800'
                        ],
                        'fail' => [
                            'label' => 'Fallo',
                            'color' => 'bg-red-100 text-red-800'
                        ],
                    ];

                    $config = $statusConfig[$leakTest->status] ?? [
                        'label' => 'N/A',
                        'color' => 'bg-gray-100 text-gray-800'
                    ];

                    $number = $leakTest->serie . '-' . str_pad($leakTest->correlativo, 6, '0', STR_PAD_LEFT);

                    return sprintf(
                        '<div class="flex flex-col gap-1 text-center">
                <span class="font-medium text-gray-900">%s</span>
                <span class="inline-flex items-center w-fit px-2 py-0.5 rounded-full text-xs font-medium %s">%s</span>
            </div>',
                        $number,
                        $config['color'],
                        $config['label']
                    );
                })
                ->html(),

            Column::make("Box", "status_box")
                ->format(function ($value, $row) {
                    //dd($row->containerOperationalTrace->currentStatus);
                    return view('components.badge', [
                        'icon' => $row->statusBoxBadge['icon'] ?? '',
                        'styleBg' => $row->statusBoxBadge['styleBg'] ?? '',
                        'slot' => $row->statusBoxBadge['description'] ?? '',
                    ]);
                }),
            Column::make("Mach", "status_machine")
                ->format(function ($value, $row) {
                    return view('components.badge', [
                        'icon' => $row->statusMachineBadge['icon'] ?? '',
                        'styleBg' => $row->statusMachineBadge['styleBg'] ?? '',
                        'slot' => $row->statusMachineBadge['description'] ?? '',
                    ]);
                }),
            Column::make("Estado", "status")
                ->format(function ($value, $row) {
                    //dd($row->containerOperationalTrace->currentStatus);
                    return view('components.badge', [
                        'icon' => $row->currentStatus['icon'] ?? '',
                        'styleBg' => $row->currentStatus['styleBg'] ?? '',
                        'slot' => $row->currentStatus['description'] ?? '',
                    ]);
                }),
            Column::make("Registro", "created_at")
                ->format(function ($value, $row) {
                    return Carbon::parse($value)->format('d/m/Y');
                })
                ->sortable(),
        ];
    }

    public function builder(): Builder
    {
        return ContainerOperationalTrace::query()
            ->select('container_operational_traces.*')
            ->with([
                'gateInDetail.originable.branch',
                'gateInDetail.originable.shippingLine',
                'EirInspection:id,trace_id,correlativo,serie,created_at',
                'LeakTest:id,serie,correlativo,trace_id,status,started_at,finished_at', // ← Agregar trace_id y status
                'container:id,code,iso_code'
            ])
            ->where('container_operational_traces.status', '>', 0)
            ->whereDate('container_operational_traces.created_at', '>=', now()->subMonths(3))
            ->whereHas('gateInDetail', function ($query) {
                $query->whereHasMorph('originable', ['App\Models\Dischargue', 'App\Models\Devolution'], function ($morphQuery) {
                    $morphQuery->where('branch_id', session('branch')->id);
                });
            });
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
                        ->whereDate('container_operational_traces.created_at', '>=', $dateRange['minDate'])
                        ->whereDate('container_operational_traces.created_at', '<=', $dateRange['maxDate']);
                }),
            SelectFilter::make('Línea')
                ->options(
                    ['' => 'Todas las líneas'] + ShippingLine::query()
                        ->whereHas('dischargues', function ($query) {
                            $query->whereHas('gateInDetails.containerOperationalTrace', function ($traceQuery) {
                                $traceQuery->whereHas('gateInDetail', function ($gateQuery) {
                                    $gateQuery->whereHasMorph('originable', ['App\Models\Dischargue'], function ($originableQuery) {
                                        $originableQuery->where('branch_id', session('branch')->id);
                                    });
                                });
                            });
                        })
                        ->orWhereHas('devolutions', function ($query) {
                            $query->whereHas('gateInDetails.containerOperationalTrace', function ($traceQuery) {
                                $traceQuery->whereHas('gateInDetail', function ($gateQuery) {
                                    $gateQuery->whereHasMorph('originable', ['App\Models\Devolution'], function ($originableQuery) {
                                        $originableQuery->where('branch_id', session('branch')->id);
                                    });
                                });
                            });
                        })
                        ->pluck('name', 'id')
                        ->toArray()
                ),

            SelectFilter::make('Estado')
                ->options([
                    '' => 'Todos los estados',
                    '0' => 'Anulado',
                    '1' => 'Anunciado',
                    '2' => 'Recibido',
                    '3' => 'Damage',
                    '4' => 'Operativo',
                    '5' => 'Despachado',
                    '6' => 'Devolución Interna',
                ])
                ->filter(function (Builder $builder, $value) {
                    if ($value !== '') {
                        $builder->where('status', $value);
                    }
                }),

            SelectFilter::make('Caja')
                ->options(
                    /* ['' => 'Todas las líneas'] + ContainerOperationalTrace::query()
                        ->pluck('status_box', 'status_box')
                        ->toArray() */
                    [
                        '' => 'Todos los estados',
                        'pending' => 'Sin Inspección',
                        'OP' => 'Operativa',
                        'DM' => 'Dañada'
                    ]

                )->filter(function (Builder $builder, $value) {
                    if ($value !== '') {
                        $builder->where('status_box', $value);
                    }
                })
        ];
    }
}
