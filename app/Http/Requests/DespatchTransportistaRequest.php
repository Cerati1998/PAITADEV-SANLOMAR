<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DespatchTransportistaRequest extends FormRequest
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
            "tipoDoc" => ["required", "in:31"],
            "serie" => ["required", "string", "size:4"],
            "correlativo" => [
                "required",
                "numeric",
                Rule::when(request()->routeIs(['api.despatch-transportista.send']), [
                    Rule::unique('despatch_transportistas')->where(function ($query) {
                        return $query->where('company_id', $this->company->id)
                            ->where('serie', $this->input('serie'));
                    }),
                ]),
            ],
            "fechaEmision" => ["required", "date"],

            // Confirmado contra SUNAT beta (error XSD 0306: DeliveryCustomerParty
            // obligatorio en la secuencia incluso para GRE-Transportista).
            "destinatario" => ["required", "array"],
            "destinatario.tipoDoc" => ['required', 'exists:identities,id'],
            "destinatario.numDoc" => ['required', 'alpha_num'],
            "destinatario.rznSocial" => ['required', 'string'],

            "remitente" => ["required", "array"],
            "remitente.tipoDoc" => ["required", "exists:identities,id"],
            "remitente.numDoc" => ["required", "alpha_num"],
            "remitente.rznSocial" => ["required", "string"],

            "subcontratador" => ["nullable", "array"],
            "subcontratador.numDoc" => ["nullable", "digits:11"],
            "subcontratador.rznSocial" => ["required_with:subcontratador.numDoc", "string"],

            "documentoRelacionado" => ["nullable", "array"],
            "documentoRelacionado.tipo" => ["required_with:documentoRelacionado.nro", "exists:related_documents,id"],
            "documentoRelacionado.nro" => ["nullable", "string"],
            "documentoRelacionado.emisor" => ["nullable", "digits:11"],

            "envio" => ["required", "array"],
            "envio.codTraslado" => ["required", "exists:reason_transfers,id"],
            // N. de inscripcion MTC/RNT propio del transportista (cac:CarrierParty).
            // Confirmado como obligatorio contra XML real de produccion aceptado por SUNAT.
            "envio.mtc" => ["required", "string"],
            "envio.fecTraslado" => ["required", "date"],
            "envio.pesoTotal" => ["required", "numeric"],
            "envio.undPesoTotal" => ["required", 'exists:units,id'],

            "envio.llegada" => ["required", "array"],
            "envio.llegada.ubigueo" => ["required", "string"],
            "envio.llegada.direccion" => ["required", "string"],

            "envio.partida" => ["required", "array"],
            "envio.partida.ubigueo" => ["required", "string"],
            "envio.partida.direccion" => ["required", "string"],

            "envio.vehiculo" => ["required", "array"],
            "envio.vehiculo.placa" => ["required", "string"],
            "envio.vehiculo.secundarios" => ["nullable", "array"],
            "envio.vehiculo.secundarios.*.placa" => ["required"],

            "envio.choferes" => ["required", "array", "min:1"],
            "envio.choferes.*.tipo" => ["required", "in:Principal,Secundario"],
            "envio.choferes.*.tipoDoc" => ["required", "exists:identities,id"],
            "envio.choferes.*.nroDoc" => ["required", "alpha_num"],
            "envio.choferes.*.licencia" => ["required", "string"],
            "envio.choferes.*.nombres" => ["required", "string"],
            "envio.choferes.*.apellidos" => ["required", "string"],

            "details" => ["required", "array"],
            "details.*.cantidad" => ["required", "numeric"],
            "details.*.unidad" => ["required", 'exists:units,id'],
            "details.*.descripcion" => ["required", "string"],
            "details.*.codigo" => ["required", "string"],
        ];
    }
}
