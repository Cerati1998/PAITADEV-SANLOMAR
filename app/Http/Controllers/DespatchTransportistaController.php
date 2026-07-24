<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Identity;
use App\Models\RelatedDocument;
use App\Models\Unit;

class DespatchTransportistaController extends Controller
{
    public function index()
    {
        return view('despatch-transportistas.index');
    }

    public function create()
    {
        $identities = Identity::all();
        $units = Unit::all();
        $branches = Branch::where('company_id', session('company')->id)->get();
        $related_documents = RelatedDocument::all();

        return view('despatch-transportistas.create', compact('identities', 'units', 'branches', 'related_documents'));
    }
}
