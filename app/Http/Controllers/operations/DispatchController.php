<?php

namespace App\Http\Controllers\operations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DispatchController extends Controller
{
    public function manage_dispatches()
    {
        return view('dispatches.index');
    }
}
