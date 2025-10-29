<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Models\Detraction;
use App\Models\Identity;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Perception;
use App\Models\Unit;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index()
    {
        return view('vouchers.index');
    }

    public function show(Invoice $invoice)
    {
        return view('vouchers.show', compact('invoice'));
    }

    public function invoice()
    {
        $identities = Identity::all();
        $units = Unit::all();
        $affectations = Affectation::all();
        $detractions = Detraction::all();
        $payment_methods = PaymentMethod::all();
        $perceptions = Perception::all();

        return view('vouchers.invoice', compact('identities', 'units', 'affectations', 'detractions', 'payment_methods', 'perceptions'));
    }

    public function note()
    {
        $identities = Identity::all();
        $units = Unit::all();
        $affectations = Affectation::all();
        
        return view('vouchers.note', compact('identities', 'units', 'affectations'));
    }
}
