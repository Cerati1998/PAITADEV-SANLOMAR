<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Identity;
use App\Models\Unit;
use Illuminate\Http\Request;

class DespatchController extends Controller
{
    public function index()
    {
        return view('despatchs.index');
    }

    public function create()
    {
        $identities = Identity::all();
        $units = Unit::all();
        $branches = Branch::where('company_id', session('company')->id)->get();

        return view('despatchs.create', compact('identities', 'units', 'branches'));
    }
}
