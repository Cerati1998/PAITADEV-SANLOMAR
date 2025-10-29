<?php

namespace App\Http\Controllers;

use App\Models\CustomBroker;
use App\Models\Identity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomBrokerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $identities = Identity::all();

        return view('brokers.index', compact('identities'));
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
    public function show(CustomBroker $customBroker)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CustomBroker $customBroker)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CustomBroker $customBroker)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomBroker $customBroker)
    {
        //
    }

      public function searchBrokers(Request $request){
        return CustomBroker::query()
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
