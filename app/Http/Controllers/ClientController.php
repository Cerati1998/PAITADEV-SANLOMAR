<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Identity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $identities = Identity::all();

        return view('clients.index', compact('identities'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('clients.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'tipoDoc' => 'required|exists:identities,id',
            'numDoc' => [
                Rule::requiredIf($request->tipoDoc != '-'),
                Rule::when(request('tipoDoc') == 1, 'numeric|digits:8'),
                Rule::when(request('tipoDoc') == 6, ['numeric','digits:11','regex:/^(10|20)\d{9}$/']),
                Rule::unique('clients', 'numDoc')->where(function($query){
                    return $query->where('company_id', session('company')->id)
                        ->where('tipoDoc', request('tipoDoc'))
                        ->where('tipoDoc', '!=', '-');
                }),
            ],
            'rznSocial' => 'required',

            'direccion' => Rule::requiredIf($request->tipoDoc == '6'),

            'telephone' => 'nullable',
            'email' => 'nullable',
        ]);

        $data['company_id'] = session('company')->id;

        $client = Client::create($data);

        return $client;
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client)
    {
        return view('clients.show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client)
    {
        return view('clients.edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Client $client)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        //
    }

     public function searchClients(Request $request){
        return Client::query()
        ->select(DB::raw('id,rznSocial'))
        ->when(
            $request->search,
            fn(Builder $query) =>
            $query->where('rznSocial', 'like', "%{$request->search}%")
            ->orWhere('numDoc','like',"%$request->search}")
        )
        ->when(
            $request->exists('selected'),
            fn(Builder $query) => $query->whereIn('id', $request->input('selected', [])),
            fn(Builder $query) => $query->limit(20)
        )
        ->orderBy('id')
        ->get();
    }
}
