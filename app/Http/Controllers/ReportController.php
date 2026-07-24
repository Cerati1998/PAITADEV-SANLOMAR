<?php

namespace App\Http\Controllers;

use App\Exports\DispatchExport;
use App\Exports\InspectionExport;
use App\Exports\InvoiceExport;
use App\Exports\WorkOrderExport;
use App\Models\Client;
use App\Models\ContainerOperationalTrace;
use App\Models\Dispatches\Dispatch;
use App\Models\Document;
use App\Models\inspections\EirInspection;
use App\Models\Invoice;
use App\Models\Operation;
use App\Models\ShippingLine;
use App\Models\works\WorkOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index', ['documents' => Document::orderBy('id')->get()]);
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

        // Obtener todos los traces que llegaron a status 5 (Despachado) en el rango de fechas
        $dispatchedTraces = ContainerOperationalTrace::with([
            'container:id,code,own_line_id',
            'container.line:id,name',
            'EirInspection.workOrder',
            'LeakTest',
            'gateInDetail.originable.shippingLine:id,name',
            'gateInDetail.originable.vessel:id,name'
        ])
            ->where('status', 5)
            // Filtro por fecha de cambio a status final
            ->when($request->filled('since'), function ($query) use ($request) {
                $query->whereDate('date_final_status', '>=', $request->since);
            })
            ->when($request->filled('until'), function ($query) use ($request) {
                $query->whereDate('date_final_status', '<=', $request->until);
            })
            // Filtro por línea naviera
            ->when($request->filled('line'), function ($query) use ($request) {
                $query->whereHas('container', function ($q) use ($request) {
                    $q->where('own_line_id', $request->line);
                });
            })
            // Filtro por branch
            ->whereHas('gateInDetail', function ($query) {
                $query->whereHasMorph('originable', [
                    'App\Models\Dischargue',
                    'App\Models\Devolution'
                ], function ($morphQuery) {
                    $morphQuery->where('branch_id', session('branch')->id);
                });
            })
            ->get();

        if ($dispatchedTraces->isEmpty()) {
            session()->flash('swal', [
                'icon' => 'error',
                'title' => 'Error!',
                'text' => 'No se ha encontrado data de Despachos con los filtros aplicados'
            ]);
            return back();
        }

        // Obtener IDs de traces para buscar relaciones
        $traceIds = $dispatchedTraces->pluck('id');

        // Obtener Dispatches relacionados (solo los que tienen registro en tabla dispatch)
        $dispatches = Dispatch::with(['washer:id,name', 'dispatcher:id,name', 'vessel:id,name'])
            ->whereIn('trace_id', $traceIds)
            ->get()
            ->keyBy('trace_id');

        // Obtener Work Orders relacionados a través de EirInspection
        $eirInspectionIds = EirInspection::whereIn('trace_id', $traceIds)
            ->pluck('id');

        $workOrders = WorkOrder::whereIn('eir_inspection_id', $eirInspectionIds)
            ->get()
            ->groupBy('eir_inspection_id');

        // Transformar datos
        $datos = $dispatchedTraces->map(function (ContainerOperationalTrace $trace) use ($dispatches, $workOrders, $request) {
            $dispatch = $dispatches->get($trace->id);
            //dd($trace->EirInspection?->oxalic_wash);
            // Obtener semana del dispatch o del trace
            $weekDispatch = null;
            if ($dispatch && $dispatch->getRawOriginal('dispatch_date')) {
                $weekDispatch = Carbon::parse($dispatch->getRawOriginal('dispatch_date'))->weekOfYear;
            } elseif ($trace->updated_at) {
                $weekDispatch = Carbon::parse($trace->updated_at)->weekOfYear;
            }

            return [
                'container' => $trace->container?->code ?? '-',
                'fecha_dispatch' => $dispatch?->dispatch_date
                    ? Carbon::parse($dispatch->getRawOriginal('dispatch_date'))->format('d/m/Y H:i')
                    : Carbon::parse($trace->getRawOriginal('date_final_status'))->format('d/m/Y H:i'),
                'week_dispatch' => $weekDispatch ?? '-',
                'booking_dispatch' => $dispatch?->reserva_booking ?? '-',
                'nave_dispatch' => $dispatch?->vessel?->name ?? '-',
                'name_dispatcher' => $dispatch?->dispatcher?->name ?? '-',
                'has_washed' => $dispatch ? 'SI' : '',
                'has_eir' => $trace->EirInspection ? 'SI' : '',
                'has_leak_test' => $trace->LeakTest ? 'SI' : '',
                'has_work_order' => $trace->EirInspection?->workOrder ? 'SI' : '',
                'has_oxalic_wash' => $trace->EirInspection?->oxalic_wash ? 'SI' : '',
            ];
        })->toArray();

        // Generar nombre de archivo
        $fileName = 'Despachos';
        if ($request->filled('since') && $request->filled('until')) {
            $fileName .= '_' . Carbon::parse($request->since)->format('dmY') .
                '-' . Carbon::parse($request->until)->format('dmY');
        }
        $fileName .= '.xlsx';

        // Obtener nombre de línea para el título
        $lineaNombre = 'TODAS LAS LÍNEAS';
        if ($request->filled('line')) {
            $linea = ShippingLine::find($request->line);
            $lineaNombre = $linea?->name ?? 'LÍNEA DESCONOCIDA';
        }

        // Calcular semana basada en la fecha 'since' del filtro
        $week = $request->filled('since')
            ? str_pad(Carbon::parse($request->since)->weekOfYear, 2, '0', STR_PAD_LEFT)
            : str_pad(Carbon::now()->weekOfYear, 2, '0', STR_PAD_LEFT);

        $data = [
            'title' => strtoupper("SEMANA {$week} - {$lineaNombre}"),
            'datos' => $datos
        ];

        return Excel::download(new DispatchExport($data), $fileName);
    }

    public function exportInvoices(Request $request)
    {
        // Validar los filtros
        $request->validate([
            'since' => 'nullable|date',
            'until' => 'nullable|date|after_or_equal:since',
            'client' => 'nullable|exists:clients,id',
            'tipoDoc' => 'nullable|exists:documents,id',
        ]);

        // Scoping multi-tenant obligatorio ANTES de cualquier filtro opcional
        $invoices = Invoice::query()
            ->where('company_id', session('company')->id)
            ->where('branch_id', session('branch')->id)
            ->when($request->filled('since'), function ($query) use ($request) {
                $query->whereDate('fechaEmision', '>=', $request->since);
            })
            ->when($request->filled('until'), function ($query) use ($request) {
                $query->whereDate('fechaEmision', '<=', $request->until);
            })
            ->when($request->filled('tipoDoc'), function ($query) use ($request) {
                $query->where('tipoDoc', $request->tipoDoc);
            })
            ->when($request->filled('client'), function ($query) use ($request) {
                $numDoc = Client::find($request->client)?->numDoc;
                $query->where('client->numDoc', $numDoc);
            })
            ->with('document')
            ->orderBy('fechaEmision')
            ->get();

        if ($invoices->isEmpty()) {
            session()->flash('swal', [
                'icon' => 'error',
                'title' => 'Error!',
                'text' => 'No se ha encontrado data de Comprobantes con los filtros aplicados',
            ]);

            return back();
        }

        $operations = Operation::all()->keyBy('id');

        // Transformar datos en el orden de columnas de InvoiceExport::headings()
        $datos = $invoices->map(function (Invoice $invoice) use ($operations) {
            return [
                'fecha_emision' => $invoice->fechaEmision->format('d/m/Y'),
                'tipo_documento' => $invoice->document?->description ?? '-',
                'serie_correlativo' => $invoice->serie.'-'.$invoice->correlativo,
                'cliente' => ($invoice->client['numDoc'] ?? 'S/D').' - '.($invoice->client['rznSocial'] ?? 'S/D'),
                'moneda' => $invoice->tipoMoneda,
                'tipo_cambio' => $invoice->tipoCambio,
                'tipo_operacion' => $operations->get($invoice->tipoOperacion)?->description ?? ($invoice->tipoOperacion ?? '-'),
                'detraccion' => $invoice->detraccion['mount'] ?? 0,
                'subtotal' => $invoice->subTotal,
                'igv' => $invoice->mtoIGV,
                'total' => $invoice->mtoImpVenta,
                'estado_sunat' => InvoiceExport::estadoSunat($invoice->sunatResponse, (bool) $invoice->voided),
            ];
        })->values()->toArray();

        // Generar nombre de archivo
        $fileName = 'Comprobantes';
        if ($request->filled('since') && $request->filled('until')) {
            $fileName .= '_'.Carbon::parse($request->since)->format('dmY').
                '-'.Carbon::parse($request->until)->format('dmY');
        }
        $fileName .= '.xlsx';

        return Excel::download(new InvoiceExport($datos), $fileName);
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
        $weekNumber = $sinceDate->weekOfYear();

        // Si el rango abarca múltiples semanas, puedes mostrar el rango
        $weekSince = $sinceDate->weekOfYear();
        $weekUntil = $untilDate->weekOfYear();

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
            } else if ($inspection->oxalic_wash) {
                $tipo_lavado = 'OXALICO';
            } else if ($inspection->special_wash) {
                $tipo_lavado = 'ESPECIAL';
            } else {
                $tipo_lavado = '-';
            }

            $nota = ($inspection->quimic_wash) ? $inspection->reason_quimic_wash : $inspection->reason_special_wash;

            return [
                'id' => $inspection->id,
                'container' => $inspection->trace?->container?->code,
                'size' => substr($inspection->trace?->container?->container_type?->code, 0, 2),
                'type' => substr($inspection->trace?->container?->container_type?->code, 2, 2),
                'year' => $inspection->trace?->container?->build_year,
                'tare' => $inspection->trace?->container?->tare,
                'payload' => $inspection->trace?->container?->payload,
                'condition' => ($inspection->fast_repair || $inspection->normal_repair || $inspection->quoted_repair) ? 'DAÑADO' : 'OPERATIVO',
                'height' => 'Altura Max: ' . $inspection?->trace?->container?->max_height,
                'provider' => 'SANLOMAR GROUP',
                'wash' => $tipo_lavado,
                'note' => $nota,
            ];
        })->toArray();

        // Generar nombre de archivo dinámico con filtros
        $fileName = "Inspeccion_{$weekDisplay}_{$lineName}_" .
            $sinceDate->format('dmY') . '-' . $untilDate->format('dmY') . '.xlsx';

        return Excel::download(new InspectionExport($datos, $titulo), $fileName);
    }

    public function exportWorkOrders(Request $request)
    {
        // Validar los filtros
        $request->validate([
            'since' => 'nullable|date',
            'until' => 'nullable|date|after_or_equal:since',
            'line' => 'nullable|exists:shipping_lines,id',
        ]);

        // Obtener todos los traces que llegaron a status 5 (Despachado) en el rango de fechas
        $workOrders = WorkOrder::with([
            'container:id,code,own_line_id,container_type_id', //container_type_id
            'container.container_type:id,code'
        ])
            ->where('status', "completado")
            // Filtro por fecha de cambio a status final
            ->when($request->filled('since'), function ($query) use ($request) {
                $query->whereDate('closed_at', '>=', $request->since);
            })
            ->when($request->filled('until'), function ($query) use ($request) {
                $query->whereDate('closed_at', '<=', $request->until);
            })
            // Filtro por línea naviera
            ->when($request->filled('line'), function ($query) use ($request) {
                $query->whereHas('container', function ($q) use ($request) {
                    $q->where('own_line_id', $request->line);
                });
            })
            // Filtro por branch
            ->where('branch_id', session('branch')->id)
            ->get();

        if ($workOrders->isEmpty()) {
            session()->flash('swal', [
                'icon' => 'error',
                'title' => 'Error!',
                'text' => 'No se ha encontrado data de Reparaciones con los filtros aplicados'
            ]);
            return back();
        }

        $datos = $workOrders->map(function ($workOrder) {

            return [
                'year' => $workOrder->closed_at->format('Y'),
                'month' => $workOrder->closed_at->format('m'),
                'week' => str_pad(Carbon::parse($workOrder->closed_at)->weekOfYear, 2, '0', STR_PAD_LEFT),
                'container' => $workOrder->container?->code,
                'type' => $workOrder->container?->container_type?->code,
                'date_repair' => Carbon::parse($workOrder->closed_at)->format('d/m/Y'),
            ];
        })->toArray();

        // Generar nombre de archivo
        $fileName = 'Reparaciones';
        if ($request->filled('since') && $request->filled('until')) {
            $fileName .= '_' . Carbon::parse($request->since)->format('dmY') .
                '-' . Carbon::parse($request->until)->format('dmY');
        }
        $fileName .= '.xlsx';

        // Obtener nombre de línea para el título
        $lineaNombre = 'TODAS LAS LÍNEAS';
        if ($request->filled('line')) {
            $linea = ShippingLine::find($request->line);
            $lineaNombre = $linea?->name ?? 'LÍNEA DESCONOCIDA';
        }

        // Calcular semana basada en la fecha 'since' del filtro
        $week = $request->filled('since')
            ? str_pad(Carbon::parse($request->since)->weekOfYear, 2, '0', STR_PAD_LEFT)
            : str_pad(Carbon::now()->weekOfYear, 2, '0', STR_PAD_LEFT);

        $data = [
            'title' => strtoupper("REPORTE DE REPARACION SEMANA {$week} - {$lineaNombre}"),
            'datos' => $datos
        ];

        return Excel::download(new WorkOrderExport($data), $fileName);
    }
}
