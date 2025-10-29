<?php

namespace App\Livewire\Forms;

use App\Models\Despatch;
use App\Services\Sunat\DespatchService;
use App\Services\Sunat\DocumentService;
use App\Services\Sunat\UtilService;
use Greenter\Report\XmlUtils;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class DespatchForm extends Form
{
    public $version = '2022';
    public $tipoDoc = '09';
    public $serie = '';
    public $correlativo = '';
    public $fechaEmision;
    public $destinatario = [
        'tipoDoc' => '6',
        'numDoc' => '',
        'rznSocial' => '',
        'address' => [
            'direccion' => '',
        ]
    ];
    public $envio = [
        'codTraslado' => '01',
        'modTraslado' => '02',
        'indicadores' => [],
        'fecTraslado' => null,
        'pesoTotal' => null,
        'undPesoTotal' => 'KGM',
        'llegada' => [
            'ubigueo' => '',
            'direccion' => '',
            'ruc' => null,
            'codLocal' => null,
        ],
        'partida' => [
            'ubigueo' => '',
            'direccion' => '',
            'ruc' => null,
            'codLocal' => null,
        ],
        'vehiculo' => [
            'placa' => '',
            'secundarios' => [

            ],
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
        'transportista' => [
            'tipoDoc' => '6',
            'numDoc' => '',
            'rznSocial' => '',
            'nroMtc' => '',
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

            "envio" => ["required", "array"],
            "envio.codTraslado" => ["required", "exists:reason_transfers,id"],
            "envio.modTraslado" => ["required", "in:01,02"],
            "envio.indicadores" => ["nullable", "array"],
            "envio.fecTraslado" => [
                "required", 
                "date",
                "after_or_equal:fecEmision",
            ],
            "envio.pesoTotal" => ["required", "numeric"],
            "envio.undPesoTotal" => ["required", 'exists:units,id'],

            "envio.llegada" => ["required", "array"],
            "envio.llegada.ubigueo" => ["required", "string"],
            "envio.llegada.direccion" => ["required", "string"],

            "envio.partida" => ["required", "array"],
            "envio.partida.ubigueo" => ["required", "string"],
            "envio.partida.direccion" => ["required", "string"],

            //Transportista
            "envio.transportista" => ["required_if:envio.modTraslado,01","array"],
            "envio.transportista.tipoDoc" => ["required_if:envio.modTraslado,01","exists:identities,id"],
            "envio.transportista.numDoc" => ["required_if:envio.modTraslado,01","alpha_num"],
            "envio.transportista.rznSocial" => ["required_if:envio.modTraslado,01","string"],
            "envio.transportista.nroMtc" => ["required_if:envio.modTraslado,01","string",],

            //Vehiculo
            "envio.vehiculo" => [
                Rule::requiredIf(fn() => $this->envio['modTraslado'] === '02' && empty(in_array('SUNAT_Envio_IndicadorTrasladoVehiculoM1L', $this->envio['indicadores']))),
                "array",
            ],
            "envio.vehiculo.placa" => [
                Rule::requiredIf(fn() => $this->envio['modTraslado'] === '02' && empty(in_array('SUNAT_Envio_IndicadorTrasladoVehiculoM1L', $this->envio['indicadores']))),
                'regex:/^[a-zA-Z0-9]{6}$/'
            ],
            "envio.vehiculo.secundarios" => ['nullable','array'],
            "envio.vehiculo.secundarios.*.placa" => [
                Rule::requiredIf(fn() => $this->envio['modTraslado'] === '02' && empty(in_array('SUNAT_Envio_IndicadorTrasladoVehiculoM1L', $this->envio['indicadores']))),
            ],

            //Choferes
            "envio.choferes" => [
                Rule::requiredIf(fn() => $this->envio['modTraslado'] === '02' && empty(in_array('SUNAT_Envio_IndicadorTrasladoVehiculoM1L', $this->envio['indicadores']))),
                "array"
            ],
            "envio.choferes.*.tipo" => [
                Rule::requiredIf(fn() => $this->envio['modTraslado'] === '02' && empty(in_array('SUNAT_Envio_IndicadorTrasladoVehiculoM1L', $this->envio['indicadores']))),
                "in:Principal,Secundario"
            ],
            "envio.choferes.*.tipoDoc" => [
                Rule::requiredIf(fn() => $this->envio['modTraslado'] === '02' && empty(in_array('SUNAT_Envio_IndicadorTrasladoVehiculoM1L', $this->envio['indicadores']))),
                "exists:identities,id"
            ],
            "envio.choferes.*.nroDoc" => [
                Rule::requiredIf(fn() => $this->envio['modTraslado'] === '02' && empty(in_array('SUNAT_Envio_IndicadorTrasladoVehiculoM1L', $this->envio['indicadores']))),
                "alpha_num",
                //Si tipoDoc es 1 el numDoc debe ser numérico y de 8 dígitos

            ],
            "envio.choferes.*.licencia" => [
                /* Rule::requiredIf(fn() => $this->envio['modTraslado'] === '02' && empty(in_array('SUNAT_Envio_IndicadorTrasladoVehiculoM1L', $this->envio['indicadores']))), */
                "string",
                Rule::when($this->envio['modTraslado'] === '02' && empty(in_array('SUNAT_Envio_IndicadorTrasladoVehiculoM1L', $this->envio['indicadores'])),[
                    'required',
                    'min:9',
                    'max:10',
                ])
            ],
            "envio.choferes.*.nombres" => [
                Rule::requiredIf(fn() => $this->envio['modTraslado'] === '02' && empty(in_array('SUNAT_Envio_IndicadorTrasladoVehiculoM1L', $this->envio['indicadores']))),
                "string"
            ],
            "envio.choferes.*.apellidos" => [
                Rule::requiredIf(fn() => $this->envio['modTraslado'] === '02' && empty(in_array('SUNAT_Envio_IndicadorTrasladoVehiculoM1L', $this->envio['indicadores']))),
                "string"
            ],

            "details" => ["required", "array"],
            "details.*.cantidad" => [
                "required", 
                "numeric",
                "min:1"
            ],
            "details.*.unidad" => ["required", 'exists:units,id'],
            "details.*.descripcion" => ["required", "string"],
            "details.*.codigo" => ["required", "string"],
        ];
    }

    public function validationAttributes() 
    {
        return [
            'serie' => 'serie',
            'correlativo' => 'correlativo',
            'fechaEmision' => 'fecha de emisión',
            'envio.codTraslado' => 'código de traslado',
            'envio.modTraslado' => 'modo de traslado',
            'envio.fecTraslado' => 'fecha de traslado',
            'envio.pesoTotal' => 'peso total',
            'envio.undPesoTotal' => 'unidad de peso total',
            'envio.llegada.ubigueo' => 'ubigeo de llegada',
            'envio.llegada.direccion' => 'dirección de llegada',
            'envio.partida.ubigueo' => 'ubigeo de partida',
            'envio.partida.direccion' => 'dirección de partida',
            'envio.transportista.tipoDoc' => 'tipo de documento del transportista',
            'envio.transportista.numDoc' => 'número de documento del transportista',
            'envio.transportista.rznSocial' => 'razón social del transportista',
            'envio.transportista.nroMtc' => 'número de MTC del transportista',
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
            'details.*.codigo' => 'código',
        ];
    }

    public function sendDespatch()
    {
        $this->validate();

        $despatch = Despatch::create($this->toArray());

        $util = new UtilService(session('company'));
        $document = new DocumentService();

        $despatchGreenter = $document->getDespatch($despatch);

        //Directorio
        $directory = $this->production ? 'sunat/' : 'sunat/beta/';

        //Generar XML
        $xml = $util->getXmlSigned($despatchGreenter);
        $despatch->hash = $util->getHashSign($xml);

        //Guardar XML
        $despatch->xml_path = $directory . 'xml/' . $despatchGreenter->getName() . '.xml';
        Storage::put($despatch->xml_path, $xml);

        //Guardar PDF
        $pdf = $util->getReportPdf($despatchGreenter, $despatch->hash);
        $despatch->pdf_path = $directory . 'cpe/' . $despatchGreenter->getName() . '.pdf';
        Storage::put($despatch->pdf_path, $pdf);
        $despatch->save();

        //Enviar a SUNAT
        try {

            $api = $util->getSeeApi();
            $result = $api->sendXml($despatchGreenter->getName(), $xml);

            if (!$result->isSuccess()) {

                $despatch->sunatResponse = $util->getErrorResponse($result);
                $despatch->save();

                $this->showResponse($despatch);

                return redirect()->route('despatchs.index');
            }

            $ticket = $result->getTicket();
            $result = $api->getStatus($ticket);

            if (!$result->isSuccess()) {

                $despatch->sunatResponse = $util->getErrorResponse($result);
                $despatch->save();

                $this->showResponse($despatch);

                return redirect()->route('despatchs.index');
            }

            // Guardamos el CDR
            $despatch->cdr_path = $directory . 'cdr/R-' . $despatchGreenter->getName() . '.zip';
            Storage::put($despatch->cdr_path, $result->getCdrZip());

            $cdr = $result->getCdrResponse();
            $despatch->sunatResponse = $util->readCdr($cdr);
            $despatch->save();
            
            $this->showResponse($despatch);

            return redirect()->route('despatchs.index');

        } catch (\Exception $e) {
            
            session()->flash('swal', [
                'icon' => 'error',
                'title' => 'Error al enviar el comprobante',
                'text' => $e->getMessage()
            ]);

            return redirect()->route('despatchs.index');

        }

    }

    public function showResponse(Despatch $despatch)
    {
        $title = 'Detalle de la guía de remisión';

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
