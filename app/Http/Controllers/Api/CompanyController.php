<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Company::whereHas('users', function ($query){
            $query->where('user_id', auth('jwt')->id());
        })->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'logo' => 'nullable|image',
            'ruc' => [
                'required',
                'digits:11',
                'unique:companies,ruc',
                'regex:/^(10|20)/'
            ],
            'razonSocial' => 'required',
            'nombreComercial' => 'required',
            'direccion' => 'required',
            'ubigeo' => 'required|exists:districts,id',
            'sol_user' => 'nullable',
            'sol_pass' => 'nullable',
            'client_id' => 'nullable',
            'client_secret' => 'nullable',
            'certificate' => 'nullable|mimes:pem,txt',
        ]);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('companies', 'public');
        }

        if ($request->hasFile('certificate')) {
            $data['certificate'] = $request->file('certificate')->get();
        }

        $company = Company::create($data);
        $company->users()->attach(auth('jwt')->id());

        $branch = $company->branches()->create([
            'name' => 'Principal',
            'code' => '0000',
            'ubigeo' => $company->ubigeo,
            'address' => $company->direccion,
        ]);

        $branch->documents()->attach("01", [
            'serie' => 'F001',
            'correlativo' => '0001',
            'company_id' => $company->id,
        ]);
        $branch->documents()->attach("03", [
            'serie' => 'B001',
            'correlativo' => '0001',
            'company_id' => $company->id,
        ]);
        $branch->documents()->attach("07", [
            'serie' => 'FC01',
            'correlativo' => '0001',
            'company_id' => $company->id,
        ]);
        $branch->documents()->attach("07", [
            'serie' => 'BC01',
            'correlativo' => '0001',
            'company_id' => $company->id,
        ]);
        $branch->documents()->attach("08", [
            'serie' => 'FD01',
            'correlativo' => '0001',
            'company_id' => $company->id,
        ]);
        $branch->documents()->attach("08", [
            'serie' => 'BD01',
            'correlativo' => '0001',
            'company_id' => $company->id,
        ]);
        $branch->documents()->attach("09", [
            'serie' => 'T001',
            'correlativo' => '0001',
            'company_id' => $company->id,
        ]);

        $branch->users()->attach(auth('jwt')->id(), [
            'company_id' => $company->id,
        ]);

        return $company;
    }

    /**
     * Display the specified resource.
     */
    public function show($companyId)
    {
        $company = Company::where('id', $companyId)
            ->whereHas('users', function ($query) {
                $query->where('user_id', auth('jwt')->id());
            })->firstOrFail();

        return $company;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $companyId)
    {
        $company = Company::where('id', $companyId)
            ->whereHas('users', function ($query) {
                $query->where('user_id', auth('jwt')->id());
            })->firstOrFail();

        $data = $request->validate([
            'logo' => 'nullable|image',
            'ruc' => [
                'nullable',
                'digits:11',
                'unique:companies,ruc,' . $company->id,
                'regex:/^(10|20)/'
            ],
            'razonSocial' => 'nullable|string|min:3',
            'nombreComercial' => 'nullable|string|min:3',
            'direccion' => 'nullable|string|min:3',
            'ubigeo' => 'nullable|exists:districts,id',
            'sol_user' => 'nullable',
            'sol_pass' => 'nullable',
            'client_id' => 'nullable',
            'client_secret' => 'nullable',
            'certificate' => 'nullable|mimes:pem,txt',
        ]);

        $data = array_filter($data);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('companies', 'public');
        }

        if ($request->hasFile('certificate')) {
            $data['certificate'] = $request->file('certificate')->get();
        }

        $company->update($data);

        return $company;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($companyId)
    {
        $company = Company::where('id', $companyId)
            ->whereHas('users', function ($query) {
                $query->where('user_id', auth('jwt')->id());
            })->firstOrFail();

        $company->delete();

        return response()->noContent();
    }

    public function token(Request $request, $companyId)
    {
        request()->validate([
            'name' => 'required|string',
        ]);

        $company = Company::where('id', $companyId)
            ->whereHas('users', function ($query) {
                $query->where('user_id', auth('jwt')->id());
            })->firstOrFail();

        $token = $company->createToken($request->input('name'));

        return response()->json([
            'token' => $token->plainTextToken,
        ]);
    }
}
