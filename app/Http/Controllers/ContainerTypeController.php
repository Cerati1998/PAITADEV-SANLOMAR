<?php

namespace App\Http\Controllers;

use App\Models\ContainerType;
use Illuminate\Http\Request;

class ContainerTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('masters.technologies.index');
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
    public function show(ContainerType $containerType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ContainerType $containerType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ContainerType $containerType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ContainerType $containerType)
    {
        //
    }
}
