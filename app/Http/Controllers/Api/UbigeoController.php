<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\District;
use Illuminate\Http\Request;

class UbigeoController extends Controller
{
    public function index(Request $request){

        //Department - Province - District
        return District::with(['province.department'])
            ->when($request->search, function ($query, $search) {

                $terms = explode('-', $search);
                $terms = array_map('trim', $terms);
                $terms = array_filter($terms);

                switch (count($terms)) {
                    case 1:
                        $query->where(function ($query) use ($terms) {
                            $query->where('name', 'like', '%' . $terms[0] . '%')
                                ->orWhereHas('province', function ($query) use ($terms) {
                                    $query->where('name', 'like', '%' . $terms[0] . '%')
                                        ->orWhereHas('department', function ($query) use ($terms) {
                                            $query->where('name', 'like', '%' . $terms[0] . '%');
                                        });
                                });
                        });

                        break;            

                    case 2:
                        
                        $query->whereHas('province', function ($query) use ($terms) {
                            $query->whereHas('department', function ($query) use ($terms) {
                                $query->where('name', 'like', '%' . $terms[0] . '%');
                            })->where('name', 'like', '%' . $terms[1] . '%');
                        });

                        break;

                    case 3:
                        $query->whereHas('province', function ($query) use ($terms) {
                            $query->whereHas('department', function ($query) use ($terms) {
                                $query->where('name', 'like', '%' . $terms[0] . '%');
                            })->where('name', 'like', '%' . $terms[1] . '%');
                        })->where('name', 'like', '%' . $terms[2] . '%');
                        break;
                }
            })
            ->when(
                $request->exists('selected'),
                fn ($query) => $query->whereIn('id', $request->input('selected', [])),
                fn ($query) => $query->limit(10)
            )
            ->get()
            ->map(function ($district) {
                return [
                    'id' => $district->id,
                    'name' => $district->province->department->name . ' - ' . $district->province->name . ' - ' . $district->name,
                ];
            });
    }
}
