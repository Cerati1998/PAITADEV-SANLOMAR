<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhoneCode;
use Illuminate\Http\Request;

class PhoneCodeController extends Controller
{
    public function index()
    {
        return PhoneCode::select('id', 'name')
            ->when(request()->search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%');
            })->when(
                request()->exists('selected'),
                fn ($query) => $query->whereIn('id', request()->input('selected', []))
            )->get();
    }
}
