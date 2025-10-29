<?php

namespace App\Livewire\Forms;

use App\Models\Invoice;
use App\Services\Sunat\DocumentService;
use App\Services\Sunat\InvoiceService;
use App\Services\Sunat\UtilService;
use Greenter\Report\XmlUtils;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Form;
use Luecano\NumeroALetras\NumeroALetras;

class NoteForm extends Form
{
    public $ublVersion = '2.1';

    #[Url]
    public $tipoDoc = '07';
    public $serie;
    public $correlativo = '';
    public $fechaEmision = null;

    public $tipDocAfectado = '01';
    public $numDocfectado = '';

    public $codMotivo = '';
    public $desMotivo = '';

    public $tipoMoneda = 'PEN';

    public $client = [
        'tipoDoc' => '6',
        'numDoc' => '',
        'rznSocial' => '',
        'address' => [
            'direccion' => '',
        ]
    ];

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
    public $redondeo = 0;
    public $mtoImpVenta = 0;

    public $details = [];

    public $legends = [];

    public $company_id;
    public $production;

    public function rules()
    {
        //Inicial serie
        $initialSerie = substr($this->serie, 0, 1);

        return [
            'tipoDoc' => ['required', 'in:07,08'],
            'serie' => [
                'required',
                'string', 
                'size:4',
            ],
            'correlativo' => 'required',

            'fechaEmision' => ['required', 'date'],

            'numDocfectado' => [
                'required',
                "regex:/^[" . $initialSerie . "][A-Z0-9]{3}-\d+$/"
            ],

            'codMotivo' => [
                'required',
                $this->tipoDoc == '07' ? 'exists:type_credit_notes,id' : 'exists:type_debit_notes,id'
            ],

            'tipoMoneda' => ['required', 'in:USD,PEN'],

            'details' => ['required', 'array'],
        ];
    }

    public function sendInvoice()
    {
        $this->validate();
        $this->getData();

        $invoice = Invoice::create($this->all());
        
        $util = new UtilService(session('company'));
        $document = new DocumentService();

        $invoiceGreenter = $document->getNote($invoice);

        //Directorio
        $directory = $this->production ? 'sunat/' : 'sunat/beta/';

        //Generar XML
        $xml = $util->getXmlSigned($invoiceGreenter);
        $invoice->hash = $util->getHashSign($xml);

        //Guardar XML
        $invoice->xml_path = $directory . 'xml/' . $invoiceGreenter->getName() . '.xml';
        Storage::put($invoice->xml_path, $xml);

        //PDF
        $pdf = $util->getReportPdf($invoiceGreenter, $invoice->hash);
        $invoice->pdf_path = $directory . 'cpe/' . $invoiceGreenter->getName() . '.pdf';
        Storage::put($invoice->pdf_path, $pdf);
        $invoice->save();

        //Enviar a SUNAT
        try {

            $see = $util->getSee();
            $result = $see->sendXmlFile($xml);

            if (!$result->isSuccess()) {

                $invoice->sunatResponse = $util->getErrorResponse($result);
                $invoice->save();

                $this->showResponse($invoice);

                return redirect()->route('vouchers.index');
            }

            // Guardamos el CDR
            $invoice->cdr_path = $directory . 'cdr/R-' . $invoiceGreenter->getName() . '.zip';
            Storage::put($invoice->cdr_path, $result->getCdrZip());
            
            $cdr = $result->getCdrResponse();
            $invoice->sunatResponse = $util->readCdr($cdr);
            $invoice->save();

            $this->showResponse($invoice);

            return redirect()->route('vouchers.index');
            
        } catch (\Exception $e) {
            
            session()->flash('swal', [
                'icon' => 'error',
                'title' => 'Error al enviar el comprobante',
                'text' => $e->getMessage()
            ]);

            return redirect()->route('vouchers.index');
        }

    }

    public function showResponse(Invoice $invoice)
    {
        $title = 'Detalle de la ';
        $title .= match ($invoice->tipoDoc) {
            '07' => 'nota de crédito',
            '08' => 'nota de débito',
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
        $this->getTotales();
        $this->getLegends();
    }

    public function getTotales()
    {
        $details = collect($this->details);

        $this->tipDocAfectado = str_starts_with($this->serie, 'F') ? '01' : '03';

        $this->mtoOperGravadas = $details->where('tipAfeIgv', '10')->sum('mtoValorVenta');
        $this->mtoOperExoneradas = $details->where('tipAfeIgv', '20')->sum('mtoValorVenta');
        $this->mtoOperInafectas = $details->where('tipAfeIgv', '30')->sum('mtoValorVenta');
        $this->mtoOperExportacion = $details->where('tipAfeIgv', '40')->sum('mtoValorVenta');
        $this->mtoOperGratuitas = $details->whereNotIn('tipAfeIgv', ['10', '17', '20', '30', '40'])->sum('mtoValorVenta');
        $this->mtoBaseIvap = $details->where('tipAfeIgv', '17')->sum('mtoValorVenta');
        $this->mtoBaseIsc = $details->sum('mtoBaseIsc');

        $this->mtoIGV = $details->whereIn('tipAfeIgv', ['10', '20', '30', '40'])->sum('igv');
        $this->mtoIGVGratuitas = $details->whereNotIn('tipAfeIgv', ['10', '17', '20', '30', '40'])->sum('igv');
        $this->mtoIvap = $details->where('tipAfeIgv', '17')->sum('igv');
        $this->icbper = $details->sum('icbper');
        $this->mtoISC = $details->sum('isc');

        $this->totalImpuestos = $this->mtoIGV + $this->icbper + $this->mtoIvap + $this->mtoISC;

        $this->valorVenta = $details->whereIn('tipAfeIgv', ['10', '17', '20', '30', '40'])->sum('mtoValorVenta');
        $this->subTotal = $this->valorVenta + $this->totalImpuestos;
        $mtoImpVenta = $this->subTotal;
        $this->mtoImpVenta = floor($mtoImpVenta * 10) / 10;
        $this->redondeo =  $mtoImpVenta - $this->mtoImpVenta;
    }

    public function getLegends()
    {
        $formatter = new NumeroALetras();

        $legends = [];

        if ($this->mtoImpVenta) {
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

        if ($this->mtoOperGratuitas) {
            $legends[] = [
                'code' => '1002',
                'value' => 'TRANSFERENCIA GRATUITA DE UN BIEN Y/O SERVICIO PRESTADO GRATUITAMENTE'
            ];
        }

        if ($this->mtoBaseIvap) {
            $legends[] = [
                'code' => '2007',
                'value' => 'Operación sujeta al IVAP'
            ];
        }

        $this->legends = $legends;
    }
}
