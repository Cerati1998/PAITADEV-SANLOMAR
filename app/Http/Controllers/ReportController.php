<?php

namespace App\Http\Controllers;

use App\Exports\DispatchExport;
use App\Models\Dispatches\Dispatch;
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
            'line' => 'nullable|exists:lines,id',
            'client' => 'nullable|exists:clients,id',
        ]);

        $dispatchData = Dispatch::with([
            'trace',
            'trace.container:id,code',
            'trace.LeakTest',
            'shipper', // Necesario para filtro de cliente
            'washer'
        ])
            // Filtro por fecha
            ->when($request->filled('since'), function ($query) use ($request) {
                $query->whereDate('dispatch_date', '>=', $request->since);
            })
            ->when($request->filled('until'), function ($query) use ($request) {
                $query->whereDate('dispatch_date', '<=', $request->until);
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

        if ($dispatchData->isEmpty()) {
            session()->flash('swal', [
                'icon' => 'error',
                'title' => 'Error!',
                'text' => 'No se ha encontrado data de Despachos con los filtros aplicados'
            ]);
            return back();
        }

        // Transformación limpia con map()
        $datos = $dispatchData->map(function ($dispatch) {
            return [
                'id' => $dispatch->id,
                'container' => $dispatch->trace?->container?->code,
                'despacho' => Carbon::parse($dispatch->dispatch_date)->format('d/m/Y H:i'),
                'booking' => $dispatch->reserva_booking,
                'nave' => $dispatch->trace?->nave,
                'despachador' => $dispatch->washer?->name,
                'repair_box' => $dispatch->trace->date_rep_box ? 'SI' : '',
                'wash_oxalic' => $dispatch->oxalic_wash ? 'SI' : '',
                'leak_test' => $dispatch->trace?->LeakTest?->status === 'ok' ? 'SI' : '',
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
}
