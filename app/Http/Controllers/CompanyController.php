<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Despatch;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $companies = Company::whereHas('users', function ($query) {
            $query->where('user_id', auth()->id());
        })->get();

        return view('companies.index', compact('companies'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('companies.create');
    }

    /**
     * Display the specified resource.
     */
    public function show($companyId)
    {
        $company = Company::whereHas('users', function ($query) {
            $query->where('user_id', auth()->id());
        })->findOrFail($companyId);

        session()->put('company', $company);
        return redirect()->route('dashboard');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        return view('companies.edit', [
            'company' => session('company'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $company = session('company');

        $data = $request->validate([
            //Debe ser .png, .jpg, .jpeg
            'logo' => 'nullable|mimes:png,jpg,jpeg',
            'ruc' => [
                'required',
                'digits:11',
                'unique:companies,ruc,' . $company->id,
                'regex:/^(10|20)/'
            ],
            'razonSocial' => 'required',
            'nombreComercial' => 'required',
            'direccion' => 'required',
            'detraction_account' => 'nullable|string|max:100',
            'sol_user' => 'required',
            'sol_pass' => 'required',
            'client_id' => 'required',
            'client_secret' => 'required',
            'certificate' => 'nullable|mimes:pem,txt',
            'invoice_header' => 'nullable',
            'invoice_footer' => 'nullable',
            'production' => 'required|boolean',
        ]);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('companies', 'public');
        }

        if ($request->hasFile('certificate')) {
            $data['certificate'] = $request->file('certificate')->get();
        }

        $company->update($data);
        session()->put('company', Company::find($company->id));

        session()->flash('swal', [
            'icon' => 'success',
            'title' => 'Empresa actualizada',
            'text' => 'La empresa ha sido actualizada correctamente',
        ]);

        return redirect()->route('companies.edit', $company);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy()
    {
        $company = session('company');

        //Invoices
        $invoices = Invoice::where('company_id', $company->id)->get();

        Storage::delete($invoices->pluck('pdf_path')->filter(function ($pdf) {
            return $pdf !== null;
        })->toArray());

        Storage::delete($invoices->pluck('xml_path')->filter(function ($xml) {
            return $xml !== null;
        })->toArray());

        //Despatchs
        $despatchs = Despatch::where('company_id', $company->id)->get();

        Storage::delete($despatchs->pluck('pdf_path')->filter(function ($pdf) {
            return $pdf !== null;
        })->toArray());

        Storage::delete($despatchs->pluck('xml_path')->filter(function ($xml) {
            return $xml !== null;
        })->toArray());

        $company->delete();

        session()->forget('company');

        return redirect()->route('companies.index');
    }

    public function apiToken()
    {
        return view('companies.api-token', [
            'company' => session('company'),
        ]);
    }
}
