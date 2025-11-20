<?php

namespace App\Http\Controllers;

use App\Exports\DispatchExport;
use App\Exports\InspectionExport;
use App\Models\Dispatches\Dispatch;
use App\Models\inspections\EirInspection;
use App\Models\ShippingLine;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function exportDispatches(Request $request)
    {
        // Validar los filtros
        $request->validate([
            'since' => 'nullable|date',
            'until' => 'nullable|date|after_or_equal:since',
            'line' => 'nullable|exists:shipping_lines,id',
            'client' => 'nullable|exists:clients,id',
        ]);

        $inspectionData = Dispatch::with([
            'trace',
            'trace.container:id,code',
            'trace.LeakTest',
            'shipper', // Necesario para filtro de cliente
            'washer'
        ])
            // Filtro por fecha
            ->when($request->filled('since'), function ($query) use ($request) {
                $query->whereDate('inspection_date', '>=', $request->since);
            })
            ->when($request->filled('until'), function ($query) use ($request) {
                $query->whereDate('inspection_date', '<=', $request->until);
            })
            // Filtro por cliente
            ->when($request->filled('client'), function ($query) use ($request) {
                $query->where('shipper_id', $request->client);
            })
            ->whereHas('trace', function ($queryTrace) use ($request) {
                $queryTrace->where('status', 5)
                    // Filtro por línea
                    ->when($request->filled('line'), function ($q) use ($request) {
                        $q->whereHas('container', function ($containerQuery) use ($request) {
                            $containerQuery->where('line_id', $request->line);
                        });
                    })
                    ->whereHas('gateInDetail', function ($query) {
                        $query->whereHasMorph('originable', [
                            'App\Models\Dischargue',
                            'App\Models\Devolution'
                        ], function ($morphQuery) {
                            $morphQuery->where('branch_id', session('branch')->id);
                        });
                    });
            })
            ->get();

        if ($inspectionData->isEmpty()) {
            session()->flash('swal', [
                'icon' => 'error',
                'title' => 'Error!',
                'text' => 'No se ha encontrado data de Despachos con los filtros aplicados'
            ]);
            return back();
        }

        // Transformación limpia con map()
        $datos = $inspectionData->map(function ($inspection) {
            return [
                'id' => $inspection->id,
                'container' => $inspection->trace?->container?->code,
                'despacho' => Carbon::parse($inspection->inspection_date)->format('d/m/Y H:i'),
                'booking' => $inspection->reserva_booking,
                'nave' => $inspection->trace?->nave,
                'despachador' => $inspection->washer?->name,
                'repair_box' => $inspection->trace->date_rep_box ? 'SI' : '',
                'wash_oxalic' => $inspection->oxalic_wash ? 'SI' : '',
                'leak_test' => $inspection->trace?->LeakTest?->status === 'ok' ? 'SI' : '',
            ];
        })->toArray();

        // Generar nombre de archivo dinámico con filtros
        $fileName = 'Despachos';
        if ($request->filled('since') && $request->filled('until')) {
            $fileName .= '_' . Carbon::parse($request->since)->format('dmY') .
                '-' . Carbon::parse($request->until)->format('dmY');
        }
        $fileName .= '.xlsx';

        return Excel::download(new DispatchExport($datos), $fileName);
    }

    public function exportInspections(Request $request)
    {
        // Validar los filtros (ahora obligatorios)
        $request->validate([
            'since' => 'required|date',
            'until' => 'required|date|after_or_equal:since',
            'line' => 'required|exists:shipping_lines,id',
        ]);

        $inspectionData = EirInspection::with([
            'trace',
            'workOrder',
            'trace.container:id,code,build_year,tare,payload,max_height,own_line_id,container_type_id',
            'trace.container.container_type:id,code',
            'trace.container.line:id,name',
        ])
            // Filtro por fecha
            ->when($request->filled('since'), function ($query) use ($request) {
                $query->whereDate('started_at', '>=', $request->since);
            })
            ->when($request->filled('until'), function ($query) use ($request) {
                $query->whereDate('closed_at', '<=', $request->until);
            })
            ->whereHas('trace', function ($queryTrace) use ($request) {
                $queryTrace
                    // Filtro por línea
                    ->when($request->filled('line'), function ($q) use ($request) {
                        $q->whereHas('container', function ($containerQuery) use ($request) {
                            $containerQuery->where('own_line_id', $request->line);
                        });
                    })
                    ->whereHas('gateInDetail', function ($query) {
                        $query->whereHasMorph('originable', [
                            'App\Models\Dischargue',
                            'App\Models\Devolution'
                        ], function ($morphQuery) {
                            $morphQuery->where('branch_id', session('branch')->id);
                        });
                    });
            })
            ->get();

        if ($inspectionData->isEmpty()) {
            session()->flash('swal', [
                'icon' => 'error',
                'title' => 'Error!',
                'text' => 'No se ha encontrado data de Inspecciones con los filtros aplicados'
            ]);
            return back();
        }

        // Obtener el nombre de la línea
        $lineName = strtoupper(ShippingLine::find($request->line)->name ?? 'N/A');

        // Calcular la semana (WK) del rango de fechas
        $sinceDate = Carbon::parse($request->since);
        $untilDate = Carbon::parse($request->until);

        // Usamos la semana de la fecha "since" o el promedio si prefieres
        $weekNumber = $sinceDate->week();

        // Si el rango abarca múltiples semanas, puedes mostrar el rango
        $weekSince = $sinceDate->week();
        $weekUntil = $untilDate->week();

        $weekDisplay = ($weekSince === $weekUntil)
            ? "WK{$weekNumber}"
            : "WK{$weekSince}-WK{$weekUntil}";

        // Generar el título dinámico
        $titulo = "INSPECCION SEMANA {$weekDisplay} LINEA {$lineName}";

        // Transformación limpia con map()
        $datos = $inspectionData->map(function ($inspection) {
            $tipo_lavado = '';

            if ($inspection->normal_wash) {
                $tipo_lavado = 'NORMAL';
            } else if ($inspection->quimic_wash) {
                $tipo_lavado = 'QUIMICO';
            } else {
                $tipo_lavado = 'ESPECIAL';
            }

            return [
                'id' => $inspection->id,
                'container' => $inspection->trace?->container?->code,
                'size' => substr($inspection->trace?->container?->container_type?->code, 0, 2),
                'type' => substr($inspection->trace?->container?->container_type?->code, 2, 2),
                'year' => $inspection->trace?->container?->build_year,
                'tare' => $inspection->trace?->container?->tare,
                'payload' => $inspection->trace?->container?->payload,
                'condition' => ($inspection?->workOrder) ? 'DAÑADO' : 'OPERATIVO',
                'height' => 'Altura Max: ' . $inspection?->trace?->container?->max_height,
                'provider' => 'SANLOMAR GROUP',
                'wash' => $tipo_lavado,
                'note' => $inspection?->observations,
            ];
        })->toArray();

        // Generar nombre de archivo dinámico con filtros
        $fileName = "Inspeccion_{$weekDisplay}_{$lineName}_" .
            $sinceDate->format('dmY') . '-' . $untilDate->format('dmY') . '.xlsx';

        return Excel::download(new InspectionExport($datos, $titulo), $fileName);
    }
}
