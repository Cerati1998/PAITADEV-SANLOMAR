<?php

namespace App\Http\Controllers;

use App\Models\Identity;
use Illuminate\Http\Request;

class ArchingCashController extends Controller
{
    public function get_cashes()
    {
        return view("cashes.index");
    }
    public function get_bills()
    {
        $identities = Identity::all();
        return view("bills.index",compact('identities'));

    }
}
