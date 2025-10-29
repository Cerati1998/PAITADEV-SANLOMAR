<?php

namespace App\Http\Controllers;

use App\Models\Port;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PortController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('masters.ports.index');
    }

    public function get_countries(Request $request)
    {
        $countries = [
            ['code' => 'PER', 'name' => 'PERÚ', 'estado' => 1],
            ['code' => 'USA', 'name' => 'ESTADOS UNIDOS', 'estado' => 1],
            ['code' => 'CHN', 'name' => 'CHINA', 'estado' => 1],
            ['code' => 'BRA', 'name' => 'BRASIL', 'estado' => 1],
            ['code' => 'CHL', 'name' => 'CHILE', 'estado' => 1],
            ['code' => 'ECU', 'name' => 'ECUADOR', 'estado' => 1],
            ['code' => 'COL', 'name' => 'COLOMBIA', 'estado' => 1],
            ['code' => 'MEX', 'name' => 'MÉXICO', 'estado' => 1],
            ['code' => 'JPN', 'name' => 'JAPÓN', 'estado' => 1],
            ['code' => 'KOR', 'name' => 'COREA DEL SUR', 'estado' => 1],
            ['code' => 'CAN', 'name' => 'CANADÁ', 'estado' => 1],
            ['code' => 'AUS', 'name' => 'AUSTRALIA', 'estado' => 1],
            ['code' => 'SGP', 'name' => 'SINGAPUR', 'estado' => 1],
            ['code' => 'VNM', 'name' => 'VIETNAM', 'estado' => 1],
            ['code' => 'THA', 'name' => 'TAILANDIA', 'estado' => 1],
            ['code' => 'PAN', 'name' => 'PANAMÁ', 'estado' => 1],
            ['code' => 'ARG', 'name' => 'ARGENTINA', 'estado' => 1],
            ['code' => 'BOL', 'name' => 'BOLIVIA', 'estado' => 1],
            ['code' => 'DEU', 'name' => 'ALEMANIA', 'estado' => 1],
            ['code' => 'FRA', 'name' => 'FRANCIA', 'estado' => 1],
            ['code' => 'ITA', 'name' => 'ITALIA', 'estado' => 1],
            ['code' => 'ESP', 'name' => 'ESPAÑA', 'estado' => 1],
            ['code' => 'NLD', 'name' => 'PAÍSES BAJOS', 'estado' => 1],
            ['code' => 'BEL', 'name' => 'BÉLGICA', 'estado' => 1],
            ['code' => 'IND', 'name' => 'INDIA', 'estado' => 1],
            ['code' => 'ZAF', 'name' => 'SUDÁFRICA', 'estado' => 1],
            ['code' => 'MYS', 'name' => 'MALASIA', 'estado' => 1],
            ['code' => 'NZL', 'name' => 'NUEVA ZELANDA', 'estado' => 1],
            ['code' => 'RUS', 'name' => 'RUSIA', 'estado' => 1],
            ['code' => 'TUR', 'name' => 'TURQUÍA', 'estado' => 1],
            ['code' => 'ISR', 'name' => 'ISRAEL', 'estado' => 1],
            ['code' => 'PRT', 'name' => 'PORTUGAL', 'estado' => 1],
            ['code' => 'SWE', 'name' => 'SUECIA', 'estado' => 1],
            ['code' => 'FIN', 'name' => 'FINLANDIA', 'estado' => 1],
            ['code' => 'NOR', 'name' => 'NORUEGA', 'estado' => 1],
            ['code' => 'DNK', 'name' => 'DINAMARCA', 'estado' => 1],
            ['code' => 'AUT', 'name' => 'AUSTRIA', 'estado' => 1],
            ['code' => 'POL', 'name' => 'POLONIA', 'estado' => 1],
            ['code' => 'CZE', 'name' => 'REPUBLICA CHECA', 'estado' => 1],
            ['code' => 'EGY', 'name' => 'EGIPTO', 'estado' => 1],
            ['code' => 'DZA', 'name' => 'ARGELIA', 'estado' => 1],
            ['code' => 'MAR', 'name' => 'MARRUECOS', 'estado' => 1],
            ['code' => 'NGA', 'name' => 'NIGERIA', 'estado' => 1],
            ['code' => 'KEN', 'name' => 'KENIA', 'estado' => 1],
            ['code' => 'TUN', 'name' => 'TÚNEZ', 'estado' => 1],
            ['code' => 'GHA', 'name' => 'GHANA', 'estado' => 1],
            ['code' => 'CMR', 'name' => 'CAMERÚN', 'estado' => 1],
            ['code' => 'CIV', 'name' => 'COSTA DE MARFIL', 'estado' => 1],
            ['code' => 'SEN', 'name' => 'SENEGAL', 'estado' => 1]
        ];

        if ($request->has('search')) {
            $search = Str::lower($request->search);

            // Si hay búsqueda, filtrar
            if ($search !== '') {
                $filtered = array_filter($countries, function ($country) use ($search) {
                    return Str::contains(Str::lower($country['name']), $search);
                });
                return response()->json(array_values($filtered));
            }

            return response()->json(array_values($countries));
        }
    }

    public function searchPorts(Request $request){
        return Port::query()
        ->select(DB::raw('id,CONCAT(code,"-",name) as full_name'))
        ->when(
            $request->search,
            fn(Builder $query) =>
            $query->where('name', 'like', "%{$request->search}%")
        )
        ->when(
            $request->exists('selected'),
            fn(Builder $query) => $query->whereIn('id', $request->input('selected', [])),
            fn(Builder $query) => $query->limit(20)
        )
        ->orderBy('id')
        ->get();
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Port $port)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Port $port)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Port $port)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Port $port)
    {
        //
    }
}
