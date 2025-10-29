<?php

namespace App\Http\Controllers;

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

        return view('despatchs.create', compact('identities', 'units'));
    }
}
