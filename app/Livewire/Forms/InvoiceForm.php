<?php

namespace App\Livewire\Forms;

use App\Models\Invoice;
use App\Services\Sunat\DocumentService;
use App\Services\Sunat\InvoiceService;
use App\Services\Sunat\UtilService;
use App\Services\SunatService;
use App\Traits\Sunat\DataTrait;
use Closure;
use Greenter\Report\XmlUtils;
use Greenter\Xml\Builder\InvoiceBuilder;
use Greenter\XMLSecLibs\Sunat\SignedXml;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Form;
use Luecano\NumeroALetras\NumeroALetras;

class InvoiceForm extends Form
{
    public $ublVersion = '2.1';
    public $tipoOperacion = '';

    #[Url()]
    public $tipoDoc = '01';
    public $serie = '';
    public $correlativo = '';
    public $fechaEmision = null;
    public $fecVencimiento = null;
    public $formaPago = [
        'tipo' => 'Contado',
        'moneda' => 'PEN',
    ];
    public $cuotas = [];
    public $tipoMoneda = 'PEN';

    public $tipoCambio = 0;
    public $guias = [];

    public $client = [
        'tipoDoc' => '',
        'numDoc' => '',
        'rznSocial' => '',
        'address' => [
            'direccion' => '',
        ]
    ];

    public $sumOtrosDescuentos = 0;
    public $mtoOperGravadas = 0;
    public $mtoOperExoneradas = 0;
    public $mtoOperInafectas = 0;
    public $mtoOperExportacion = 0;
    public $mtoOperGratuitas = 0;
    public $mtoBaseIvap = 0;
    public $mtoBaseIsc = 0;
    public $mtoIGVGratuitas = 0;
    public $mtoIGV = 0;
    public $mtoIvap = 0;
    public $icbper = 0;
    public $mtoISC = 0;
    public $totalImpuestos = 0;
    public $valorVenta = 0;
    public $subTotal = 0;
    public $redondeo = false;
    public $mtoImpVenta = 0;

    public $anticipos = [];
    public $totalAnticipos = 0;

    public $descuentos = [];

    public $detraccion = [
        'codBienDetraccion' => '',
        'codMedioPago' => '',
        'ctaBanco' => '',
        'percent' => '',
        'mount' => '',
    ];

    public $perception = [
        'codReg' => '',
        'porcentaje' => 0,
        'mtoBase' => 0,
        'mto' => 0,
        'mtoTotal' => 0,
    ];

    public $atributos = [];
    public $details = [];

    public $legends = [];

    public $branch_id;
    public $company_id;
    public $production;

    public $huesped = [
        'paisDoc' => '',
        'paisRes' => '',
        'fecIngresoPais' => '',
        'fecIngresoEst' => '',
        'fecSalidaEst' => '',
        'nombre' => '',
        'tipoDoc' => '',
        'numDoc' => '',
    ];

    public function rules()
    {
        return [
            'tipoOperacion' => [
                'required',
                Rule::exists('document_operation', 'operation_id')
                    ->where(function ($query) {
                        $query->where('document_id', $this->tipoDoc);
                    }),
            ],
            'tipoDoc' => ['required', 'in:01,03'],
            'serie' => [
                'required',
                'string',
                'size:4',
                Rule::when($this->tipoDoc == '01', 'regex:/^F/', 'regex:/^B/'),
            ],

            'correlativo' => ['required', 'numeric'],

            'fechaEmision' => [
                'required',
                'date',
                //Fechas permitidas entre 3 dias antes y hoy
                'after_or_equal:' . now()->subDays(3)->format('Y-m-d'),
                'before_or_equal:' . now()->format('Y-m-d'),
            ],

            'fecVencimiento' => [
                'nullable',
                'date',
                'after_or_equal:fechaEmision',
            ],

            'cuotas' => [
                $this->formaPago['tipo'] == 'Credito' ? 'required' : 'nullable',
                'array'
            ],
            'cuotas.*.monto' => ['required', 'numeric'],
            'cuotas.*.fechaPago' => [
                'required',
                'date',
                'after:fechaEmision',
            ],

            'tipoMoneda' => ['required', 'in:USD,PEN'],
            'tipoCambio' => ['required', 'min:0'],

            'client.tipoDoc' => [
                'required',
                'exists:identities,id',
                Rule::prohibitedIf(function () {
                    if (in_array($this->tipoOperacion, ['0200', '0201', '0202', '0203', '0204', '0205', '0206', '0207', '0208'])) {
                        return in_array($this->client['tipoDoc'], ['1', '6']);
                    }

                    if ($this->tipoDoc == '01' && $this->tipoOperacion != '0401') {
                        return $this->client['tipoDoc'] != '6';
                    }
                }),
            ],


            'detraccion.codBienDetraccion' => [
                Rule::requiredIf($this->tipoOperacion == '1001'),
                'exists:detractions,id',
            ],
            'detraccion.codMedioPago' => [
                Rule::requiredIf($this->tipoOperacion == '1001'),
                'exists:payment_methods,id',
            ],
            'detraccion.ctaBanco' => [
                Rule::requiredIf($this->tipoOperacion == '1001'),
            ],

            'perception.codReg' => [
                'required_if:tipoOperacion,2001',
                'exists:perceptions,id',
            ],


            'huesped.paisDoc' => [
                Rule::requiredIf(in_array($this->tipoOperacion, ['0202', '0205'])),
                'exists:countries,id',
            ],
            'huesped.paisRes' => [
                Rule::requiredIf(in_array($this->tipoOperacion, ['0202'])),
                'exists:countries,id',
            ],
            'huesped.fecIngresoPais' => [
                Rule::requiredIf(in_array($this->tipoOperacion, ['0202'])),
                'date',
            ],
            'huesped.fecIngresoEst' => [
                Rule::requiredIf(in_array($this->tipoOperacion, ['0202'])),
                'date',
            ],
            'huesped.fecSalidaEst' => [
                Rule::requiredIf(in_array($this->tipoOperacion, ['0202'])),
                'date',
                'after:huesped.fecIngresoEst',
            ],
            'huesped.nombre' => [
                Rule::requiredIf(in_array($this->tipoOperacion, ['0202', '0205'])),
            ],
            'huesped.tipoDoc' => [
                Rule::requiredIf(in_array($this->tipoOperacion, ['0202', '0205'])),
                'exists:identities,id',
            ],
            'huesped.numDoc' => [
                Rule::requiredIf(in_array($this->tipoOperacion, ['0202', '0205'])),
                Rule::when(in_array($this->tipoOperacion, ['0202', '0205']), 'min:3'),
            ],

            'details' => ['required', 'array'],
        ];
    }

    public function messages()
    {
        return [
            'client.tipoDoc.required' => 'El campo cliente es obligatorio',
            'client.tipoDoc.prohibited' => 'El tipo de documento del cliente no es válido para esta operación',
            'huesped.paisDoc.required_if' => 'El país de emisión del documento del huesped es obligatorio',
            'huesped.paisRes.required_if' => 'El país de residencia del huesped es obligatorio',
            'huesped.fecIngresoPais.required_if' => 'La fecha de ingreso al país es obligatoria',
            'huesped.fecIngresoEst.required_if' => 'La fecha de ingreso al establecimiento es obligatoria',
            'huesped.fecSalidaEst.required_if' => 'La fecha de salida del establecimiento es obligatoria',
            'huesped.nombre.required_if' => 'El nombre del huesped es obligatorio',
            'huesped.tipoDoc.required_if' => 'El tipo de documento del huesped es obligatorio',
            'huesped.numDoc.required_if' => 'El número de documento del huesped es obligatorio',

            'perception.codReg.required_if' => 'El tipo de percepción es obligatorio',

            'details.required' => 'Debe agregar al menos un item',
        ];
    }

    public function validationAttributes()
    {
        return [
            'tipoOperacion' => 'tipo de operación',
            'tipoDoc' => 'tipo de documento',
            'serie' => 'serie',
            'correlativo' => 'correlativo',
            'fechaEmision' => 'fecha de emisión',
            'fecVencimiento' => 'fecha de vencimiento',
            'cuotas' => 'cuotas',
            'cuotas.*.monto' => 'monto',
            'cuotas.*.fechaPago' => 'fecha de pago',
            'tipoMoneda' => 'tipo de moneda',
            'tipoCambio' => 'tipo de cambio',

            'detraccion.codBienDetraccion' => 'código de bienes sujeto a detracción',
            'detraccion.codMedioPago' => 'código de medio de pago',
            'detraccion.ctaBanco' => 'cuenta bancaria',

            'perception.codReg' => 'tipo de percepción',

            'huesped.paisDoc' => 'país de emisión del documento del huesped',
            'huesped.paisRes' => 'país de residencia del huesped',
            'huesped.fecIngresoPais' => 'fecha de ingreso al país',
            'huesped.fecIngresoEst' => 'fecha de ingreso al establecimiento',
            'huesped.fecSalidaEst' => 'fecha de salida del establecimiento',
            'huesped.nombre' => 'nombre del huesped',
            'huesped.tipoDoc' => 'tipo de documento del huesped',
            'huesped.numDoc' => 'número de documento del huesped',

            'details' => 'detalles',
        ];
    }

    public function sendInvoice()
    {
        $this->validate();
        $this->getData();

        $invoice = Invoice::create($this->all());
        $fileName = $this->client['numDoc'] . '-' . $this->tipoDoc . '-' . $this->serie . '-' . str_pad($this->correlativo, 6, 0, STR_PAD_LEFT);

        try {
            $util = new UtilService(session('company'));
            $document = new DocumentService();
            $invoiceGreenter = $document->getInvoice($invoice);

            //Directorio
            $directory = $this->production ? 'sunat/' : 'sunat/beta/';

            //Generar XML
            $xml = $util->getXmlSigned($invoiceGreenter);
            $invoice->hash = $util->getHashSign($xml);

            //Guardar XML
            $invoice->xml_path = $directory . 'xml/' . $fileName . '.xml';
            Storage::put($invoice->xml_path, $xml);

            //PDF
            $pdf = $util->getReportPdf($invoiceGreenter, $invoice->hash, $invoice->tipoCambio);
            $invoice->pdf_path = $directory . 'cpe/' . $fileName . '.pdf';
            Storage::put($invoice->pdf_path, $pdf);

            //Guardamos cambios
            $invoice->save();

            //Enviar a SUNAT
            $see = $util->getSee();
            $result = $see->sendXmlFile($xml);

            $invoice->sunatResponse = $util->getResponse($result);

            // Guardamos el CDR
            if ($result->getCdrZip()) {
                $invoice->cdr_path = $directory . 'cdr/R-' . $fileName . '.zip';
                Storage::put($invoice->cdr_path, $result->getCdrZip());
            }

            $invoice->save();

            $this->showResponse($invoice);

            return redirect()->route('vouchers.index');
        } catch (\Throwable $e) {

            $invoice->sunatResponse = [
                'success' => false,
                'error' => [
                    'code' => null,
                    'message' => $e->getMessage(),
                ],
            ];
            $invoice->save();

            session()->flash('swal', [
                'icon' => 'error',
                'title' => 'Error al generar el comprobante',
                'text' => $e->getMessage()
            ]);

            return redirect()->route('vouchers.index');
        }
    }

    public function showResponse(Invoice $invoice)
    {
        $title = 'Detalle de la ';
        $title .= match ($invoice->tipoDoc) {
            '01' => 'factura',
            '03' => 'boleta',
            default => 'Otro',
        };

        $html = view('vouchers.partials.sunatResponse', [
            'document' => $invoice
        ])->render();

        session()->flash('swal', [
            'icon' => $invoice->sunatResponse['success'] ? 'info' : 'error',
            'title' => $title,
            'html' => $html
        ]);
    }

    //Completar data
    public function getData()
    {
        $this->setTotales();
        $this->setLegends();
        $this->setTipoCambio();
        $this->setDetraccion();
        $this->setPerception();
        $this->setAtributos();
    }

    public function setTotales()
    {
        $details = collect($this->details);
        $anticipos = collect($this->anticipos);
        $descuentos = collect($this->descuentos);

        if ($this->formaPago['tipo'] == 'Contado') {
            $this->cuotas = [];
            unset($this->formaPago['monto']);
        } else {
            $this->formaPago['monto'] = collect($this->cuotas)->sum('monto');
        }

        $this->sumOtrosDescuentos = $descuentos->where('codTipo', '03')->sum('monto')
            + $details->flatMap(function ($detail) {
                return $detail['descuentos'] ?? [];
            })
            ->where('codTipo', '01')
            ->sum('monto');

        $this->totalAnticipos = $anticipos->sum('total');

        $this->mtoOperGravadas = $details->where('tipAfeIgv', '10')->sum('mtoValorVenta') - $descuentos->whereIn('codTipo', ['02', '04'])->sum('monto');
        $this->mtoOperExoneradas = $details->where('tipAfeIgv', '20')->sum('mtoValorVenta');
        $this->mtoOperInafectas = $details->where('tipAfeIgv', '30')->sum('mtoValorVenta');
        $this->mtoOperExportacion = $details->where('tipAfeIgv', '40')->sum('mtoValorVenta');
        $this->mtoOperGratuitas = $details->whereNotIn('tipAfeIgv', ['10', '17', '20', '30', '40'])->sum('mtoValorVenta');
        $this->mtoBaseIvap = $details->where('tipAfeIgv', '17')->sum('mtoValorVenta');
        $this->mtoBaseIsc = $details->sum('mtoBaseIsc');

        $this->mtoIGV = $details->whereIn('tipAfeIgv', ['10', '20', '30', '40'])->sum('igv') - $descuentos->whereIn('codTipo', ['02', '04'])->sum('monto') * 0.18;
        $this->mtoIGVGratuitas = $details->whereNotIn('tipAfeIgv', ['10', '17', '20', '30', '40'])->sum('igv');
        $this->mtoIvap = $details->where('tipAfeIgv', '17')->sum('igv');
        $this->icbper = $details->sum('icbper');
        $this->mtoISC = $details->sum('isc');

        $this->totalImpuestos = $this->mtoIGV + $this->icbper + $this->mtoIvap + $this->mtoISC;

        $this->valorVenta = $details->whereIn('tipAfeIgv', ['10', '17', '20', '30', '40'])->sum('mtoValorVenta') - $descuentos->where('codTipo', '02')->sum('monto');
        $this->subTotal = $this->valorVenta + $this->totalImpuestos + $descuentos->where('codTipo', '04')->sum('monto') * 0.18;
        $mtoImpVenta = $this->subTotal - $this->sumOtrosDescuentos - $this->totalAnticipos;
        //$this->mtoImpVenta = floor($mtoImpVenta * 10) / 10;
        $this->mtoImpVenta = round($mtoImpVenta, 2);
        $this->redondeo =  $this->redondeo ? $mtoImpVenta - $this->mtoImpVenta : false;
    }

    public function setLegends()
    {
        $formatter = new NumeroALetras();

        $details = collect($this->details);
        $legends = [];

        if ($details->whereIn('tipAfeIgv', ['10', '17', '20', '30', '40'])->count()) {

            $currency = match ($this->tipoMoneda) {
                'PEN' => 'SOLES',
                'USD' => 'DÓLARES AMERICANOS',
                default => $this->tipoMoneda,
            };

            $legends[] = [
                'code' => '1000',
                'value' => $formatter->toInvoice($this->mtoImpVenta, 2, $currency)
            ];
        }

        if ($details->whereNotIn('tipAfeIgv', ['10', '17', '20', '30', '40'])->count()) {
            $legends[] = [
                'code' => '1002',
                'value' => 'TRANSFERENCIA GRATUITA DE UN BIEN Y/O SERVICIO PRESTADO GRATUITAMENTE'
            ];
        }

        if ($details->where('tipAfeIgv', '17')->count()) {
            $legends[] = [
                'code' => '2007',
                'value' => 'Operación sujeta al IVAP'
            ];
        }

        if (in_array($this->tipoDoc, ['01', '03'])) {

            if ($this->tipoOperacion == '1001') {
                $legends[] = [
                    'code' => '2006',
                    'value' => 'Operación sujeta a detracción'
                ];
            }

            if ($this->tipoOperacion == '2001') {
                $legends[] = [
                    'code' => '2000',
                    'value' => 'COMPROBANTE DE PERCEPCIÓN'
                ];
            }
        }

        $this->legends = $legends;
    }

    public function setTipoCambio()
    {
        try {
            $sunat = app(SunatService::class);
            $response = $sunat->consultarTC($this->fechaEmision);
            if (!($response['success'] ?? false)) {
                throw new \Exception($response['message'] ?? 'No se encontró información');
            }
            $this->tipoCambio = $response['data']['sell_price'];
        } catch (\Exception $e) {
            Log::info("Error al intentar asignar el tipo de Cambio: {$e->getMessage()}");
            $this->tipoCambio = 0;
        }
    }
    public function setDetraccion()
    {
        if ($this->tipoOperacion === '1001' && $this->tipoMoneda === 'USD') {
            $this->detraccion['mount'] = $this->detraccion['percent'] ? round($this->tipoCambio * ($this->mtoImpVenta * $this->detraccion['percent'] / 100), 2) : '';
        } else {
            $this->detraccion['mount'] = $this->detraccion['percent'] ? round($this->mtoImpVenta * $this->detraccion['percent'] / 100, 2) : '';
        }
    }

    public function setPerception()
    {
        $this->perception['mtoBase'] = $this->mtoImpVenta;
        $this->perception['mto'] = $this->mtoImpVenta * $this->perception['porcentaje'];
        $this->perception['mtoTotal'] = $this->perception['mtoBase'] + $this->perception['mto'];
    }

    public function setAtributos()
    {
        $this->atributos = [];

        if (in_array($this->tipoOperacion, ['0202', '0205'])) {
            $this->atributos = [
                [
                    'name' => 'Código de país de emisión del pasaporte',
                    'code' => '4000',
                    'value' => $this->huesped['paisDoc']
                ],
                [
                    'name' => 'Nombres y apellidos del huesped',
                    'code' => '4007',
                    'value' => $this->huesped['nombre']
                ],
                [
                    'name' => 'Tipo de documento de identidad del huesped',
                    'code' => '4008',
                    'value' => $this->huesped['tipoDoc'] == '-' ? '0' : $this->huesped['tipoDoc']
                ],
                [
                    'name' => 'Número de documento de identidad del huesped',
                    'code' => '4009',
                    'value' => $this->huesped['numDoc']
                ],
            ];

            if ($this->tipoOperacion == '0202') {
                $this->atributos = array_merge($this->atributos, [
                    [
                        'name' => 'Código de país de residencia del sujeto no domiciliado',
                        'code' => '4001',
                        'value' => $this->huesped['paisRes']
                    ],
                    [
                        'name' => 'Fecha de ingreso al país',
                        'code' => '4002',
                        'value' => '4',
                        'fecInicio' => $this->huesped['fecIngresoPais']
                    ],
                    [
                        'name' => 'Fecha de Ingreso al Establecimiento',
                        'code' => '4003',
                        'value' => '4',
                        'fecInicio' => $this->huesped['fecIngresoEst']
                    ],
                    [
                        'name' => 'Fecha de Salida del Establecimiento',
                        'code' => '4004',
                        'value' => '4',
                        'fecInicio' => $this->huesped['fecSalidaEst']
                    ],
                    [
                        'name' => 'Número de Días de Permanencia',
                        'code' => '4005',
                        'value' => '4',
                        'duracion' => Date::parse($this->huesped['fecIngresoEst'])->diffInDays($this->huesped['fecSalidaEst'])
                    ],
                    [
                        'name' => 'Fecha de Consumo',
                        'code' => '4006',
                        'value' => '4',
                        'fecInicio' => $this->huesped['fecIngresoEst']
                    ],
                ]);
            }
        }
    }
}
