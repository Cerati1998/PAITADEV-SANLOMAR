<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ArchingCashController extends Controller
{
    public function get_cashes()
    {
        return view("cashes.index");
    }
    public function get_bills()
    {

    }
    public function get_deposits()
    {

    }
}
