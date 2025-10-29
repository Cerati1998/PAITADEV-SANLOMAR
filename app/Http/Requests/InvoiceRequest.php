<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Operation;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class InvoiceRequest extends FormRequest
{
    public $company;
    public $route;

    public function __construct()
    {
        $this->route = request()->routeIs('api.invoice.*') ? 'invoice' : 'note';

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
            'tipoDoc' => [
                'required',
                $this->route == 'invoice' ? 'in:01,03' : 'in:07,08',
            ],
            'tipoOperacion' => [
                'required_if:tipoDoc,01,03',
                Rule::exists('document_operation', 'operation_id')
                    ->where(function ($query) {
                        $query->where('document_id', $this->input('tipoDoc'));
                    }),
            ],
            'serie' => ['required', 'string', 'size:4'],
            'correlativo' => [
                'required', 
                'numeric',
                Rule::when(request()->routeIs(['api.invoice.send', 'api.note.send']), [
                    Rule::unique('invoices')
                        ->where(function($query){
                            return $query->where('company_id', $this->company->id)
                                ->where('serie', $this->input('serie'));
                        })
                ]),
            ],

            'fechaEmision' => ['required', 'date'],
            'fecVencimiento' => ['nullable', 'date'],

            'tipDocAfectado' => [
                'required_if:tipoDoc,07,08',
                'in:01,03',
            ],

            'numDocfectado' => ['required_if:tipoDoc,07,08', 'string'],

            'codMotivo' => [
                'required_if:tipoDoc,07,08', 
                $this->tipoDoc == '07' ? 'exists:type_credit_notes,id' : 'exists:type_debit_notes,id',
            ],

            'formaPago' => [
                'required_if:tipoDoc,01,03',
                'array'
            ],
            'formaPago.moneda' => [
                'required_if:tipoDoc,01,03',
                'in:USD,PEN'
            ],
            'formaPago.tipo' => [
                'required_if:tipoDoc,01,03',
                'in:Contado,Credito'
            ],
            'formaPago.monto' => [
                'required_if:formaPago.tipo,Credito', 
                'numeric'
            ],

            'cuotas' => ['required_if:formaPago.tipo,Credito', 'array', 'min:1'],
            'cuotas.*.moneda' => ['required_if:formaPago.tipo,Credito', 'in:USD,PEN'],
            'cuotas.*.monto' => ['required_if:formaPago.tipo,Credito', 'numeric'],
            'cuotas.*.fechaPago' => ['required_if:formaPago.tipo,Credito', 'date'],

            'tipoMoneda' => ['required', 'in:USD,PEN'],

            'guias' => ['nullable', 'array'],
            'guias.*.tipoDoc' => ['required', 'in:09'],
            'guias.*.nroDoc' => ['required', 'string'],

            'client' => ['required', 'array'],
            'client.tipoDoc' => ['required', 'exists:identities,id'],
            'client.numDoc' => [
                request('client.tipoDoc') != '0' ? 'required' : 'nullable',
            ],
            'client.rznSocial' => ['required', 'string'],
            'client.address' => ['nullable', 'array'],
            'client.address.direccion' => ['nullable', 'string'],
            'client.address.provincia' => ['nullable', 'string'],
            'client.address.departamento' => ['nullable', 'string'],
            'client.address.distrito' => ['nullable', 'string'],
            'client.address.ubigueo' => ['nullable', 'exists:districts,id'],

            //Es requerido si tipoOperacion es 1001
            'detraccion' => ['required_if:tipoOperacion,1001', 'array'],
            'detraccion.codBienDetraccion' => ['required_if:tipoOperacion,1001', 'exists:detractions,id'],
            'detraccion.codMedioPago' => ['required_if:tipoOperacion,1001', 'string', 'exists:payment_methods,id'],
            'detraccion.ctaBanco' => ['required_if:tipoOperacion,1001', 'string'],
            'detraccion.percent' => ['required_if:tipoOperacion,1001', 'numeric'],
            'detraccion.mount' => ['required_if:tipoOperacion,1001', 'numeric'],

            //Es requerido si tipoOperacion es 2001
            'perception' => ['required_if:tipoOperacion,2001', 'array'],
            'perception.codReg' => ['required_if:tipoOperacion,2001', 'in:51,52,53'],
            'perception.porcentaje' => ['required_if:tipoOperacion,2001', 'numeric'],
            'perception.mtoBase' => ['required_if:tipoOperacion,2001', 'numeric'],
            'perception.mto' => ['required_if:tipoOperacion,2001', 'numeric'],
            'perception.mtoTotal' => ['required_if:tipoOperacion,2001', 'numeric'],

            //Anticipos
            'anticipos' => ['nullable', 'array'],
            'anticipos.*.tipoDocRel' => ['required', 'in:02,03'],
            'anticipos.*.nroDocRel' => ['required', 'string'],
            'anticipos.*.total' => ['required', 'numeric'],

            //Descuentos
            'descuentos' => ['nullable', 'array', 'min:1'],
            'descuentos.*.codTipo' => ['required', 'in:02,03,04,05,62'],
            'descuentos.*.montoBase' => ['required', 'numeric'],
            'descuentos.*.factor' => ['required', 'numeric'],
            'descuentos.*.monto' => ['required', 'numeric'],

            //Details
            'details' => ['required', 'array'],
            'details.*.codProducto' => ['required', 'alpha_num'],
            'details.*.codProdSunat' => ['nullable', 'alpha_num'],

            'details.*.unidad' => ['required', 'string', 'exists:units,id'],
            'details.*.cantidad' => ['required', 'numeric'],
            'details.*.descripcion' => ['required', 'string'],

            'details.*.descuentos' => ['nullable', 'array'],
            'details.*.descuentos.*.codTipo' => ['required', 'in:00,01'],
            'details.*.descuentos.*.montoBase' => ['required', 'numeric'],
            'details.*.descuentos.*.factor' => ['required', 'numeric'],
            'details.*.descuentos.*.monto' => ['required', 'numeric'],

            'details.*.mtoValorUnitario' => ['required', 'numeric'],
            'details.*.mtoValorGratuito' => ['nullable', 'numeric'],

            'details.*.mtoValorVenta' => ['required', 'numeric'],

            'details.*.mtoBaseIsc' => ['nullable', 'numeric'],
            'details.*.tipSisIsc' => ['nullable', 'in:01,02,03'],
            'details.*.porcentajeIsc' => ['nullable', 'numeric'],
            'details.*.isc' => ['nullable', 'numeric'],

            'details.*.mtoBaseIgv' => ['required', 'numeric'],
            'details.*.porcentajeIgv' => ['required', 'numeric'],
            'details.*.igv' => ['required', 'numeric'],

            'details.*.icbper' => ['nullable', 'numeric'],
            'details.*.factorIcbper' => ['nullable', 'numeric'],

            'details.*.tipAfeIgv' => ['required', 'exists:affectations,id'],
            'details.*.totalImpuestos' => ['required', 'numeric'],
            'details.*.mtoPrecioUnitario' => ['required', 'numeric'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {

                //Serie de factura
                if ($this->tipoDoc == '01' && substr($this->serie, 0, 1) != 'F') {
                    $validator->errors()->add(
                        'serie',
                        'La serie debe empezar con F'
                    );
                }

                //Serie de boleta
                if ($this->tipoDoc == '03' && substr($this->serie, 0, 1) != 'B') {
                    $validator->errors()->add(
                        'serie',
                        'La serie debe empezar con B'
                    );
                }

                if ($this->tipoDoc == '07' || $this->tipoDoc == '08') {
                    if ($this->tipDocAfectado == '01' && substr($this->serie, 0, 1) != 'F') {
                        $validator->errors()->add(
                            'serie',
                            'La serie debe empezar con F'
                        );
                    }

                    if ($this->tipDocAfectado == '03' && substr($this->serie, 0, 1) != 'B') {
                        $validator->errors()->add(
                            'serie',
                            'La serie debe empezar con B'
                        );
                    }
                }

                //Descuentos
                $descuentos = collect($this->descuentos ?? []);

                if ($descuentos->whereIn('codTipo', ['04', '05'])->sum('monto') && !$this->anticipos) {
                    $validator->errors()->add(
                        'anticipos',
                        "Si se informa descuentos globales por anticipo debe existir información de anticipos"
                    );
                }

                //Anticipos
                if(isset($this->anticipos) && !$descuentos->whereIn('codTipo', ['04', '05'])->sum('monto')){
                    $validator->errors()->add(
                        'descuentos',
                        "Si existe información de anticipos, debe consignar los descuentos globales por anticipo con monto mayor a cero"
                    );
                }

                //Crédito
                if ($this->has('cuotas') && $this->formaPago['tipo'] != 'Credito') {
                    $validator->errors()->add(
                        'formaPago.tipo',
                        'Si existe información de cuota de pago, el tipo de transaccion debe ser al credito'
                    );
                }

                
                //Detracción
                if ($this->has('detraccion') && !in_array($this->tipoOperacion, ['1001', '1002', '1003', '1004'])) {
                    $validator->errors()->add(
                        'detraccion',
                        'Solo debe consignar informacion de detracción si el tipo de operación es 1001, 1002, 1003 o 1004 - Operación sujeta a Detracción'
                    );
                }

                //Percepción
                if ($this->has('perception') && $this->tipoOperacion != '2001') {
                    $validator->errors()->add(
                        'perception',
                        'Solo debe consignar informacion de percepciones si el tipo de operación es 2001-Operación sujeta a Percepción'
                    );
                }

                $details = $this->details ?? [];

                foreach ($details as $key => $detail) {

                    if (isset($detail['tipAfeIgv'])) {
                        if ($this->tipoOperacion == '0200' && $detail['tipAfeIgv'] != '40') {
                            $validator->errors()->add(
                                "details.$key.tipAfeIgv",
                                'Operaciones de exportacion, deben consignar Tipo Afectacion igual a 40'
                            );
                        }

                        if (in_array($detail['tipAfeIgv'], ['10', '17', '20', '30', '40'])) {
                            //Son operaciones onerosas
                            if(isset($detail['mtoValorGratuito']) && $detail['mtoValorGratuito'] > 0) {
                                $validator->errors()->add(
                                    "details.$key.mtoValorGratuito",
                                    "Si existe 'Valor referencial unitario en operac. no onerosas' con monto mayor a cero, la operacion debe ser gratuita"
                                );
                            }
                        }
                    }

                    //ICBPER
                    if (isset($detail['icbper']) && !isset($detail['factorIcbper'])) {
                        $validator->errors()->add(
                            "details.$key.factorIcbper",
                            'Si existe información de ICBPER, debe consignar el factor de ICBPER'
                        );
                    }

                    //Isc
                    if (isset($detail['isc'])) {

                        if (!isset($detail['mtoBaseIsc'])) {
                            $validator->errors()->add(
                                "details.$key.mtoBaseIsc",
                                'Si existe información de ISC, debe consignar la base imponible de ISC'
                            );
                        }

                        if (!isset($detail['tipSisIsc'])) {
                            $validator->errors()->add(
                                "details.$key.mtoBaseIsc",
                                'Si existe información de ISC, debe consignar el sistema de ISC'
                            );
                        }

                        if (!isset($detail['porcentajeIsc'])) {
                            $validator->errors()->add(
                                "details.$key.mtoBaseIsc",
                                'Si existe información de ISC, debe consignar el porcentaje de ISC'
                            );
                        }

                    }

                    
             
                }
            }
        ];
    }
}
