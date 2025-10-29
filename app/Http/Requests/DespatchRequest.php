<?php

namespace App\Http\Requests;

/* use Illuminate\Contracts\Validation\Rule; */

use App\Models\Company;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DespatchRequest extends FormRequest
{
    public $company;

    public function __construct()
    {
        $company = auth('sanctum')->user();

        if (!($company instanceof Company)) {
            throw new AuthenticationException('El token no está asociado a una empresa.');
        }

        $this->company = $company;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "version" => ["nullable"],
            "tipoDoc" => ["required", "in:09"],
            "serie" => ["required", "string", "size:4"],
            "correlativo" => [
                "required", 
                "numeric",
                Rule::when(request()->routeIs(['api.despatch.send']), [
                    Rule::unique('despatches')->where(function ($query) {
                        return $query->where('company_id', $this->company->id)
                            ->where('serie', $this->input('serie'));
                    }),
                ]),
            ],
            "fechaEmision" => ["required", "date"],

            "destinatario" => ["required", "array"],
            "destinatario.tipoDoc" => ['required', 'exists:identities,id'],
            "destinatario.numDoc" => ['required', 'alpha_num'],
            "destinatario.rznSocial" => ['required', 'string'],
            "destinatario.address" => ['nullable', 'array'],
            'destinatario.address.direccion' => ['nullable', 'string'],
            'destinatario.address.provincia' => ['nullable', 'string'],
            'destinatario.address.departamento' => ['nullable', 'string'],
            'destinatario.address.distrito' => ['nullable', 'string'],
            'destinatario.address.ubigueo' => ['nullable', 'exists:districts,id'],

            "envio" => ["required", "array"],
            "envio.codTraslado" => ["required", "exists:reason_transfers,id"],
            "envio.modTraslado" => ["required", "in:01,02"],
            "envio.indicadores" => ["nullable", "array"],
            "envio.fecTraslado" => ["required", "date"],
            "envio.pesoTotal" => ["required", "numeric"],
            "envio.undPesoTotal" => ["required", 'exists:units,id'],
            
            "envio.llegada" => ["required", "array"],
            "envio.llegada.ubigueo" => ["required", "string"],
            "envio.llegada.direccion" => ["required", "string"],

            "envio.partida" => ["required", "array"],
            "envio.partida.ubigueo" => ["required", "string"],
            "envio.partida.direccion" => ["required", "string"],

            "envio.transportista" => ["required_if:envio.modTraslado,01","array"],
            "envio.transportista.tipoDoc" => ["required_if:envio.modTraslado,01","exists:identities,id"],
            "envio.transportista.numDoc" => ["required_if:envio.modTraslado,01","alpha_num"],
            "envio.transportista.rznSocial" => ["required_if:envio.modTraslado,01","string"],
            "envio.transportista.nroMtc" => ["required_if:envio.modTraslado,01","string"],

            "envio.vehiculo" => [
                Rule::requiredIf(function () {
                    
                    if ($this->envio['modTraslado'] === '02') {
                        
                        if (empty(isset($this->envio['indicadores']) && in_array('SUNAT_Envio_IndicadorTrasladoVehiculoM1L', $this->envio['indicadores']))) {
                            return true;
                        }

                    }

                    return false;

                }),
                "array",
            ],
            "envio.vehiculo.placa" => [
                Rule::requiredIf(function () {
                    
                    if ($this->envio['modTraslado'] === '02') {
                        
                        if (empty(isset($this->envio['indicadores']) && in_array('SUNAT_Envio_IndicadorTrasladoVehiculoM1L', $this->envio['indicadores']))) {
                            return true;
                        }

                    }

                    return false;

                }),
                'string',
            ],
            "envio.vehiculo.secundarios" => [
                'nullable',
                'array',
            ],
            "envio.vehiculo.secundarios.*.placa" => [
                'required',
            ],
            
            "envio.choferes" => [
                
                Rule::requiredIf(function () {
                    
                    if (request()->input('envio.modTraslado') === '02') {
                        
                        if (empty(isset($this->envio['indicadores']) && in_array('SUNAT_Envio_IndicadorTrasladoVehiculoM1L', $this->envio['indicadores']))) {
                            return true;
                        }

                    }

                    return false;

                }),

                "array",
            ],
            "envio.choferes.*.tipo" => ["required","in:Principal,Secundario"],
            "envio.choferes.*.tipoDoc" => ["required","exists:identities,id"],
            "envio.choferes.*.nroDoc" => ["required","alpha_num"],
            "envio.choferes.*.licencia" => ["required","string"],
            "envio.choferes.*.nombres" => ["required","string"],
            "envio.choferes.*.apellidos" => ["required","string"],

            "details" => ["required", "array"],
            "details.*.cantidad" => ["required", "numeric"],
            "details.*.unidad" => ["required", 'exists:units,id'],
            "details.*.descripcion" => ["required", "string"],
            "details.*.codigo" => ["required", "string"],
        ];
    }    
}
