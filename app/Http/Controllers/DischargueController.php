<?php

namespace App\Http\Controllers;

use App\Models\Dischargue;
use Illuminate\Http\Request;

class DischargueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('dischargues.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    public function dischargue_containers($id) {

        $dischargue = Dischargue::findOrFail($id);
        return view('dischargues.containers',compact('dischargue'));
        

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
    public function show(Dischargue $dischargue)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Dischargue $dischargue)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Dischargue $dischargue)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Dischargue $dischargue)
    {
        //
    }
}
