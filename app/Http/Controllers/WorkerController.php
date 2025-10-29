<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Identity;
use App\Models\works\Worker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WorkerController extends Controller
{
    public function index()
    {
        $branches = Branch::where('company_id', session('company')->id)->get();
        //$identities = Identity::whereIn('id', [1, 2])->get();
        $identities = Identity::find([1, 2]);
        $cargos = ['Reparador', 'Inspector', 'Asistente', 'Despachador', 'Checker', 'Soldador', 'Reefer', 'Otro'];
        return view('works.workers.index', compact('branches', 'identities', 'cargos'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'telefono' => ['nullable', 'numeric', 'digits:9'],
            'cargo' => ['required', 'string'],
            'tipoDoc' => ['required', 'exists:identities,id'],
            'numDoc' => [
                'required',
                Rule::when($request->tipoDoc == 1, 'numeric|digits:8'),
                Rule::when($request->tipoDoc == 2, 'numeric|digits:7'),
                Rule::unique('workers', 'numDoc')
            ],
            'branch_id' => ['required', 'exists:branches,id'],
        ], [], [
            'numDoc' => 'Número de Documento',
            'branch_id' => 'sucursal',
            'name' => 'Nombres',
            'email' => 'Correo Electronico',
            'cargo' => 'Cargo de Trabajador',
        ]);

        //Buscar o crear usuario
        $worker = Worker::firstOrCreate([
            'numDoc' => $data['numDoc'],
        ], [
            'name' => $data['name'],
            'tipoDoc' => $data['tipoDoc'],
            'numDoc' => $data['numDoc'],
            'branch_id' => $data['branch_id'],
            'cargo' => $data['cargo'],
            'telefono' => $data['telefono'],
            'email' => $data['email'],
        ]);

        return $worker;
    }

    public function searchWorkers(Request $request)
    {
        return Worker::query()
            ->select(DB::raw("id,name"))
            ->when(
                $request->search,
                fn(Builder $query) =>
                $query->where(function ($q) use ($request) {
                    $q->where('numDoc', 'like', "%{$request->search}%")
                        ->orWhere('name', 'like', "%{$request->search}%");
                })
            )
            ->whereHas('branch', function ($branch_query) {
                $branch_query->where('branch_id', session('branch')->id);
            })
            ->when(
                $request->exists('selected'),
                fn(Builder $query) => $query->whereIn('id', $request->input('selected', [])),
                fn(Builder $query) => $query->limit(20)
            )
            ->orderBy('id')
            ->get();
    }
}
