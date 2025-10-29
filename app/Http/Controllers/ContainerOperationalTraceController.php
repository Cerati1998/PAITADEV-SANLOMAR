<?php

namespace App\Http\Controllers;

use App\Models\ContainerOperationalTrace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContainerOperationalTraceController extends Controller
{
    public function index()
    {
        return view('containers.index');
    }

    public function getContainers(Request $request)
    {
        return ContainerOperationalTrace::query()
            ->select('container_operational_traces.id as id', 'containers.code')
            ->join('containers', 'container_operational_traces.container_id', '=', 'containers.id')
            ->whereIn('container_operational_traces.status', [1, 2, 3])
            ->when(
                $request->search,
                fn(Builder $query) =>
                $query->where('containers.code', 'like', "%{$request->search}%")
            )
            ->when(
                $request->exists('selected'),
                fn(Builder $query) => $query->whereIn('container_operational_traces.id', $request->input('selected', [])),
                //fn(Builder $query) => $query->limit(5)
            )
            ->orderBy('container_operational_traces.id')
            ->get();
    }
}
