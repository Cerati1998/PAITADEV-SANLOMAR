<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SummaryRequest extends FormRequest
{
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
            'fecGeneracion' => ['required', 'date'],
            'fecResumen' => ['required', 'date'],
            'correlativo' => ['required', 'numeric'],
            'moneda' => ['required', 'exists:currencies,id'],
            'details' => ['required', 'array'],
            'details.*.tipoDoc' => ['required', 'in:03,07'],
            'details.*.serieNro' => ['required', 'string'],
            'details.*.clienteTipo' => ['required', 'exists:identities,id'],
            'details.*.clienteNro' => ['required', 'alpha_num'],

            'details.*.docReferencia' => ['nullable', 'array'],
            'details.*.docReferencia.tipoDoc' => [
                'nullable',
                'in:01,03,07,08,20'
            ],
            'details.*.docReferencia.nroDoc' => ['nullable', 'string'],

            'details.*.percepcion' => ['nullable', 'array'],
            'details.*.percepcion.codReg' => ['nullable', 'in:01,02,03'],
            'details.*.percepcion.tasa' => ['nullable', 'numeric'],
            'details.*.percepcion.mtoBase' => ['nullable', 'numeric'],
            'details.*.percepcion.mto' => ['nullable', 'numeric'],
            'details.*.percepcion.mtoTotal' => ['nullable', 'numeric'],

            'details.*.estado' => ['required', 'in:1,2,3'],
            'details.*.total' => ['required', 'numeric'],
            'details.*."mtoOperGravadas' => ['nullable', 'numeric'],
            'details.*."mtoOperInafectas' => ['nullable', 'numeric'],
            'details.*."mtoOperExoneradas' => ['nullable', 'numeric'],
            'details.*."mtoOperExportacion' => ['nullable', 'numeric'],
            'details.*."mtoOperGratuitas' => ['nullable', 'numeric'],
            'details.*."mtoOtrosCargos' => ['nullable', 'numeric'],
            'details.*."mtoIGV' => ['nullable', 'numeric'],
            'details.*."mtoIvap' => ['nullable', 'numeric'],
            'details.*."mtoISC' => ['nullable', 'numeric'],
            'details.*."mtoOtrosTributos' => ['nullable', 'numeric'],
            'details.*."mtoIcbper' => ['nullable', 'numeric'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {

                foreach ($this->details as $key => $detail) {                    

                }

            }
        ];
    }
}
