<?php

namespace App\Http\Controllers;

use App\Models\ShippingLine;
use App\Models\Vessel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShippingLineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('masters.lines.index');
    }

    public function vessels($id)
    {
        $shipping_line = ShippingLine::findOrFail($id);
        return view('masters.lines.vessels', compact('shipping_line'));
    }
    public function containerCategories($id)
    {
        $shipping_line = ShippingLine::findOrFail($id);
        return view('masters.lines.container-categories', compact('shipping_line'));
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
    public function show(ShippingLine $shippingLine)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ShippingLine $shippingLine)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ShippingLine $shippingLine)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ShippingLine $shippingLine)
    {
        //
    }

    public function searchLines(Request $request)
    {
        return ShippingLine::query()
            ->select( DB::raw("id,CONCAT(code,'-',name) as name"))
            ->when(
                $request->search,
                fn(Builder $query) =>
                $query->where(function ($q) use ($request) {
                    $q->where('code', 'like', "%{$request->search}%")
                        ->orWhere('name', 'like', "%{$request->search}%");
                })
            )
            ->when(
                $request->exists('selected'),
                fn(Builder $query) => $query->whereIn('id', $request->input('selected', [])),
                fn(Builder $query) => $query->limit(20)
            )
            ->orderBy('id')
            ->get();
    }
    public function searchVessels(Request $request)
    {
        return Vessel::query()
            ->select(['id', 'name'])
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
}
