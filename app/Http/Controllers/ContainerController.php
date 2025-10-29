<?php

namespace App\Http\Controllers;

use App\Models\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContainerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function getContainers(Request $request)
    {
        return Container::query()
            ->select(DB::raw('id,code'))
            ->whereHas('containerOperationalTraces', function ($traceQuery) {
                $traceQuery->whereIn('status', [1, 2]);
            })
            ->when(
                $request->search,
                fn(Builder $query) =>
                $query->where('code', 'like', "%{$request->search}%")
            )
            ->when(
                $request->exists('selected'),
                fn(Builder $query) => $query->whereIn('id', $request->input('selected', [])),
                fn(Builder $query) => $query->limit(5)
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
    public function show(Container $container)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Container $container)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Container $container)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Container $container)
    {
        //
    }
}
