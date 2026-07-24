<?php

namespace App\Livewire\Forms;

use App\Models\DespatchTransportista;
use App\Services\Sunat\Transportista\DocumentTransportistaService;
use App\Services\Sunat\Transportista\UtilTransportistaService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Form;

class DespatchTransportistaForm extends Form
{
    public $version = '2022';
    public $tipoDoc = '31';
    public $serie = '';
    public $correlativo = '';
    public $fechaEmision;

    public $destinatario = [
        'tipoDoc' => '6',
        'numDoc' => '',
        'rznSocial' => '',
    ];

    public $remitente = [
        'tipoDoc' => '6',
        'numDoc' => '',
        'rznSocial' => '',
    ];

    public $subcontratador = [
        'numDoc' => '',
        'rznSocial' => '',
    ];

    public $documentoRelacionado = [
        'tipo' => '09',
        'nro' => '',
        'emisor' => '',
    ];

    public $envio = [
        'codTraslado' => '01',
        'desTraslado' => null,
        'mtc' => '',
        'fecTraslado' => null,
        'pesoTotal' => null,
        'undPesoTotal' => 'KGM',
        'llegada' => [
            'ubigueo' => '',
            'direccion' => '',
        ],
        'partida' => [
            'ubigueo' => '',
            'direccion' => '',
        ],
        'vehiculo' => [
            'placa' => '',
            'secundarios' => [],
        ],
        'choferes' => [
            [
                'tipo' => 'Principal',
                'tipoDoc' => '1',
                'nroDoc' => '',
                'licencia' => '',
                'nombres' => '',
                'apellidos' => '',
            ]
        ],
    ];

    public $details = [];

    public $branch_id;
    public $company_id;
    public $production;

    public function rules()
    {
        return [
            "serie" => ["required", "string", "size:4"],
            "correlativo" => ["required", "numeric"],
            "fechaEmision" => [
                "required",
                "date",
                "after_or_equal:" . now()->subDay()->format('Y-m-d'),
                "before_or_equal:" . now()->addDays(3)->format('Y-m-d')
            ],

            // Confirmado contra SUNAT beta (error XSD 0306: DeliveryCustomerParty
            // obligatorio en la secuencia incluso para GRE-Transportista).
            "destinatario.tipoDoc" => ["required", "exists:identities,id"],
            "destinatario.numDoc" => ["required", "alpha_num"],
            "destinatario.rznSocial" => ["required", "string"],

            "remitente.tipoDoc" => ["required", "exists:identities,id"],
            "remitente.numDoc" => ["required", "alpha_num"],
            "remitente.rznSocial" => ["required", "string"],

            "subcontratador.numDoc" => ["nullable", "digits:11"],
            "subcontratador.rznSocial" => ["required_with:subcontratador.numDoc", "string"],

            "documentoRelacionado.tipo" => ["required_with:documentoRelacionado.nro", "exists:related_documents,id"],
            "documentoRelacionado.nro" => ["nullable", "string"],

            "envio" => ["required", "array"],
            "envio.codTraslado" => ["required", "exists:reason_transfers,id"],
            "envio.desTraslado" => ["nullable", "required_if:envio.codTraslado,13", "string"],
            // N. de inscripcion MTC/RNT propio del transportista (cac:CarrierParty).
            // Confirmado como obligatorio contra XML real de produccion aceptado por SUNAT.
            "envio.mtc" => ["required", "string"],
            "envio.fecTraslado" => [
                "required",
                "date",
                "after_or_equal:fecEmision",
            ],
            "envio.pesoTotal" => ["required", "numeric"],
            "envio.undPesoTotal" => ["required", 'exists:units,id'],

            "envio.llegada.ubigueo" => ["required", "string"],
            "envio.llegada.direccion" => ["required", "string"],

            "envio.partida.ubigueo" => ["required", "string"],
            "envio.partida.direccion" => ["required", "string"],

            "envio.vehiculo.placa" => ["required", 'regex:/^[a-zA-Z0-9]{6}$/'],
            "envio.vehiculo.secundarios" => ['nullable', 'array'],
            "envio.vehiculo.secundarios.*.placa" => ["required"],

            "envio.choferes" => ["required", "array", "min:1"],
            "envio.choferes.*.tipo" => ["required", "in:Principal,Secundario"],
            "envio.choferes.*.tipoDoc" => ["required", "exists:identities,id"],
            "envio.choferes.*.nroDoc" => ["required", "alpha_num"],
            "envio.choferes.*.licencia" => ["required", "string", "min:9", "max:10"],
            "envio.choferes.*.nombres" => ["required", "string"],
            "envio.choferes.*.apellidos" => ["required", "string"],

            "details" => ["required", "array"],
            "details.*.cantidad" => ["required", "numeric", "min:1"],
            "details.*.unidad" => ["required", 'exists:units,id'],
            "details.*.descripcion" => ["required", "string"],
            "details.*.codigo" => ["required", "string"],
        ];
    }

    public function messages()
    {
        return [
            'documentoRelacionado.nro.required_with' => 'Ingrese el número del documento relacionado.',
        ];
    }

    public function validationAttributes()
    {
        return [
            'serie' => 'serie',
            'correlativo' => 'correlativo',
            'fechaEmision' => 'fecha de emisión',
            'remitente.tipoDoc' => 'tipo de documento del remitente',
            'remitente.numDoc' => 'número de documento del remitente',
            'remitente.rznSocial' => 'razón social del remitente',
            'subcontratador.numDoc' => 'RUC de la empresa subcontratada',
            'subcontratador.rznSocial' => 'razón social de la empresa subcontratada',
            'documentoRelacionado.tipo' => 'tipo de documento relacionado',
            'documentoRelacionado.nro' => 'número de documento relacionado',
            'envio.codTraslado' => 'código de traslado',
            'envio.desTraslado' => 'Especificación de traslado OTROS',
            'envio.mtc' => 'N° de inscripción MTC/RNT',
            'envio.fecTraslado' => 'fecha de traslado',
            'envio.pesoTotal' => 'peso total',
            'envio.undPesoTotal' => 'unidad de peso total',
            'envio.llegada.ubigueo' => 'ubigeo de llegada',
            'envio.llegada.direccion' => 'dirección de llegada',
            'envio.partida.ubigueo' => 'ubigeo de partida',
            'envio.partida.direccion' => 'dirección de partida',
            'envio.vehiculo.placa' => 'placa del vehículo',
            'envio.vehiculo.secundarios.*.placa' => 'placa del vehículo secundario',
            'envio.choferes.*.tipo' => 'tipo de chofer',
            'envio.choferes.*.tipoDoc' => 'tipo de documento del chofer',
            'envio.choferes.*.nroDoc' => 'número de documento del chofer',
            'envio.choferes.*.licencia' => 'licencia del chofer',
            'envio.choferes.*.nombres' => 'nombres del chofer',
            'envio.choferes.*.apellidos' => 'apellidos del chofer',
            'details.*.cantidad' => 'cantidad',
            'details.*.unidad' => 'unidad',
            'details.*.descripcion' => 'descripción',
            'details.*.codigo' => 'código de producto',
        ];
    }

    public function sendDespatch()
    {
        $this->validate();

        $data = $this->toArray();

        // No enviar bloques opcionales vacios al modelo Greenter (destinatario
        // es obligatorio, confirmado contra SUNAT beta - no se anula).
        if (empty($data['subcontratador']['numDoc'])) {
            $data['subcontratador'] = null;
        }
        if (empty($data['documentoRelacionado']['nro'])) {
            $data['documentoRelacionado'] = null;
        }

        $despatch = DespatchTransportista::create($data);

        $util = new UtilTransportistaService(session('company'));
        $document = new DocumentTransportistaService();
        $despatchGreenter = $document->getDespatch($despatch);

        $directory = $this->production ? 'sunat/' : 'sunat/beta/';

        // Generar y guardar XML
        $xml = $util->getXmlSigned($despatchGreenter);
        $despatch->hash = $util->getHashSign($xml);
        $despatch->xml_path = $directory . 'xml/' . $despatchGreenter->getName() . '.xml';
        Storage::put($despatch->xml_path, $xml);

        // Guardar PDF
        $pdf = $util->getReportPdf($despatchGreenter, $despatch->hash);
        $despatch->pdf_path = $directory . 'cpe/' . $despatchGreenter->getName() . '.pdf';
        Storage::put($despatch->pdf_path, $pdf);
        $despatch->save();

        // Enviar a SUNAT (misma GRE API REST usada por la guia remitente)
        try {
            $api = $util->getSeeApi();
            $result = $api->sendXml($despatchGreenter->getName(), $xml);

            if (!$result->isSuccess()) {
                $despatch->sunatResponse = $util->getErrorResponse($result);
                $despatch->save();
                $this->showResponse($despatch);
                return redirect()->route('despatch-transportistas.index');
            }

            if (!($result instanceof \Greenter\Model\Response\SummaryResult)) {
                session()->flash('swal', [
                    'icon' => 'error',
                    'title' => 'Error inesperado',
                    'text' => 'SUNAT no devolvió un ticket para esta guía.',
                ]);
                return redirect()->route('despatch-transportistas.index');
            }

            $ticket = $result->getTicket();
            $despatch->sunat_ticket = $ticket;
            $despatch->save();

            $statusResult = $api->getStatus($ticket);

            if (!$statusResult->isSuccess()) {
                $code = $statusResult->getError()?->getCode();

                if ($code === '98') {
                    session()->flash('swal', [
                        'icon' => 'info',
                        'title' => 'En proceso',
                        'text' => 'SUNAT recibió la guía pero aún la procesa. Usa "Consultar estado" en unos segundos.',
                    ]);
                    return redirect()->route('despatch-transportistas.index');
                }

                $despatch->sunatResponse = $util->getErrorResponse($statusResult);
                $despatch->save();
                $this->showResponse($despatch);
                return redirect()->route('despatch-transportistas.index');
            }

            $despatch->cdr_path = $directory . 'cdr/R-' . $despatchGreenter->getName() . '.zip';
            Storage::put($despatch->cdr_path, $statusResult->getCdrZip());

            $despatch->sunatResponse = $util->readCdr($statusResult->getCdrResponse());
            $despatch->sunat_ticket = null;
            $despatch->save();

            $this->showResponse($despatch);
            return redirect()->route('despatch-transportistas.index');

        } catch (\Exception $e) {
            session()->flash('swal', [
                'icon' => 'error',
                'title' => 'Error al enviar el comprobante',
                'text' => $e->getMessage(),
            ]);
            return redirect()->route('despatch-transportistas.index');
        }
    }

    public function showResponse(DespatchTransportista $despatch)
    {
        $title = 'Detalle de la guía de remisión transportista';

        $html = view('vouchers.partials.sunatResponse', [
            'document' => $despatch
        ])->render();

        session()->flash('swal', [
            'icon' => $despatch->sunatResponse['success'] ? 'info' : 'error',
            'title' => $title,
            'html' => $html
        ]);
    }
}
