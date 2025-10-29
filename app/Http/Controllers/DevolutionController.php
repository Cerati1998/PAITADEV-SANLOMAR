<?php

namespace App\Http\Controllers;

use App\Models\Devolution;
use App\Models\Identity;
use Illuminate\Http\Request;

class DevolutionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $identities = Identity::all();
        return view('devolutions.index', compact('identities'));
    }

    public function devolution_containers($id)
    {

        $devolution = Devolution::findOrFail($id);
        return view('devolutions.containers', compact('devolution'));
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
    public function show(Devolution $devolution)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Devolution $devolution)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Devolution $devolution)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Devolution $devolution)
    {
        //
    }
}
