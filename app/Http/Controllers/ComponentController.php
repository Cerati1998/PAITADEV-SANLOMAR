<?php

namespace App\Http\Controllers;

use App\Models\Component;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComponentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('codes.components.index');
    }

    public function getComponents(Request $request)
    {
        return Component::query()
            ->select(DB::raw("id,code as name,description"))
            ->when(
                $request->search,
                fn(Builder $query) =>
                $query->where(function ($q) use ($request) {
                    $q->where('code', 'like', "%{$request->search}%")
                        ->orWhere('description', 'like', "%{$request->search}%");
                })
            )
            ->when(
                $request->exists('selected'),
                fn(Builder $query) => $query->whereIn('id', $request->input('selected', [])),
                fn(Builder $query) => $query->limit(10)
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
    public function show(Component $component)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Component $component)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Component $component)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Component $component)
    {
        //
    }
}
