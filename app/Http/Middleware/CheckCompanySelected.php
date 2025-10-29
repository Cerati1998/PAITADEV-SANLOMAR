<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCompanySelected
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('company')) {

            $companies = Company::whereHas('users', function ($query) {
                $query->where('user_id', auth()->id());
            })->get();

            if ($companies->count() === 1) {
                $company = $companies->first();
                session()->put('company', $company);

                // Añadimos branch_id justo después de guardar la empresa
                $branches = auth()->user()->branches()
                    ->wherePivot('company_id', $company->id)
                    ->get();

                /* if ($branches->count() === 1) {
                    session()->put('branch', $branches->first());
                } */
                if ($branches->count() >= 1) {
                    session()->put('branch', $branches->first());
                }

                /* 
                DESCOMENTAR CUANDO YA SE TENGA LARAVEL PERMISSION
                elseif ($branches->count() > 1 && auth()->user()->hasRole('admin')) {
                    session()->put('branch_id', $branches->first()->id); // o mostrar selector más adelante
                } */

                return $next($request);
            }

            return redirect()->route('companies.index');
        }


        //Verificamos si la empresa seleccionada aun le pertenece
        $companyId = session('company')->id;
        $company = auth()->user()->companies()->where('company_id', $companyId)->first();

        if (!$company) {
            session()->forget('company');
            return redirect()->route('companies.index');
        }

        return $next($request);
    }
}
