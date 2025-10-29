<?php

namespace App\Livewire;

use App\Livewire\Forms\InvoiceForm;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Operation;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class GenerateInvoice extends Component
{
    public InvoiceForm $invoice;

    public $identities;
    public $units, $affectations, $detractions, $payment_methods, $perceptions;

    public $operations;

    public $series;

    public $client_id;
    public $product_id;

    public $openModal = false;

    public $product_key;
    public $product = [
        'cantidad' => 1,
        'codProducto' => '',
        'unidad' => 'NIU',
        'mtoValor' => 0,
        'precioUnitario' => 0,
        'tipAfeIgv' => 10,
        'porcentajeIgv' => 18,
        'tipSisIsc' => '',
        'porcentajeIsc' => 0,
        'icbper' => 0,
        'factorIcbper' => 0.20,
        'descripcion' => '',
    ];

    public function boot()
    {
        //Verificar si hay errores de validacion en el form object
        $this->invoice->withValidator(function ($validator) {

            $validator->after(function ($validator) {

                //Validar que el tipo de documento del cliente
                $details = collect($this->invoice->details);
                if (in_array($this->invoice->tipoOperacion, ['0200', '0201', '0202', '0205', '0208'])) {

                    if ($details->where('tipAfeIgv', '!=', '40')->count() > 0) {
                        $validator->errors()->add(
                            "details.tipAfeIgv",
                            'Las operaciones de exportacion solo deben incluir ítems con tipo de afectación exportación.'
                        );
                    }
                } else {

                    if ($details->where('tipAfeIgv', '40')->count() > 0) {
                        $validator->errors()->add(
                            "details.tipAfeIgv",
                            'Las operaciones internas no deben incluir ítems con tipo de afectación exportación.'
                        );
                    }
                }

                if ($this->invoice->tipoOperacion == '1001') {
                    $montoMinimo = 700; // Monto mínimo en soles
                    $montoComprobante = $this->invoice->mtoImpVenta;
                    $mensaje = '';
                    // Si la moneda es USD, convertir a PEN usando el tipo de cambio
                    if ($this->invoice->tipoMoneda === 'USD') {
                        if ($this->invoice->tipoCambio <= 0) {
                            $validator->errors()->add('invoice.tipoCambio', 'El tipo de cambio debe ser mayor que cero cuando la moneda es USD');
                        }
                        $montoComprobante = $this->invoice->mtoImpVenta * $this->invoice->tipoCambio;
                        $mensaje .= sprintf(
                            ' (Monto actual: $%s USD = S/. %s)',
                            number_format($this->invoice->mtoImpVenta, 2),
                            number_format($montoComprobante, 2)
                        );
                    } else {
                        $mensaje .= sprintf(' (Monto actual: S/. %s)', number_format($montoComprobante, 2));
                    }

                    if ($montoComprobante < $montoMinimo) {
                        $validator->errors()->add('invoice.mtoImpVenta', "Las operaciones sujeta a detracción deben tener un monto mayor o igual a S/. 700.00. {$mensaje}");
                    }
                }

                //Cuotas
                if ($this->invoice->formaPago['tipo'] == 'Credito') {
                    $cuotas = collect($this->invoice->cuotas);
                    if ($cuotas->sum('monto') > $this->invoice->mtoImpVenta) {
                        $validator->errors()->add('cuotas', 'El monto neto pendiente de pago debe ser menor o igual al Importe total del comprobante.');
                    }
                }

                if ($details->where('tipAfeIgv', '17')->count() > 0) {
                    if ($details->where('tipAfeIgv', '!=', '17')->count() > 0) {
                        $validator->errors()->add(
                            "details.tipAfeIgv",
                            'Las operaciones con IVAP solo deben incluir ítems con tipo de afectación IVAP.'
                        );
                    }
                }
            });

            if ($validator->fails()) {
                $errors = $validator->errors()->toArray();

                $html = "<ul class='text-left'>";
                foreach ($errors as $error) {
                    $html .= "<li>$error[0]</li>";
                }
                $html .= "</ul>";

                $this->dispatch('swal', [
                    'title' => 'Ocurrió un error',
                    'html' => $html,
                    'icon' => 'error',
                ]);
            }
        });
    }

    public function mount()
    {
        if (!in_array($this->invoice->tipoDoc, ['01', '03'])) {
            $this->invoice->tipoDoc = '01';
        }

        $this->invoice->fechaEmision = now()->format('Y-m-d');
        $this->invoice->company_id = session('company')->id;
        $this->invoice->branch_id = session('branch')->id;
        $this->invoice->detraccion['ctaBanco'] = session('company')->detraction_account ?? '';
        $this->invoice->production = session('company')->production;

        $this->invoice->huesped['fecIngresoEst'] = now()->format('Y-m-d');
        $this->invoice->setTipoCambio();
        $this->getOperations();
        $this->getSeries();
    }

    //Ciclo de vida
    public function updated($property, $value)
    {
        if ($property === 'invoice.tipoDoc') {
            $this->getOperations();
            $this->getSeries();
        }
        if ($property === 'invoice.serie') {
            $this->getCorrelativo();
        }
        if ($property === 'invoice.fechaEmision') {
            $this->invoice->setTipoCambio();
        }

        if ($property === 'invoice.detraccion.codBienDetraccion') {
            $detraction = $this->detractions
                ->where('id', $this->invoice->detraccion['codBienDetraccion'])->first();

            $this->invoice->detraccion['percent'] = $detraction ? $detraction->percent : '';

            $this->invoice->setDetraccion();
        }

        if ($property === 'invoice.perception.codReg') {
            $perception = $this->perceptions
                ->where('id', $this->invoice->perception['codReg'])->first();

            $this->invoice->perception['porcentaje'] = $perception ? $perception->porcentaje : 0;

            $this->invoice->setPerception();
        }

        if ($property === 'client_id') {
            $this->clientAdded($value);
        }

        if ($property === 'product_id') {
            $product = Product::find($value)
                ->only([
                    'codProducto',
                    'unidad',
                    'mtoValor',
                    'tipAfeIgv',
                    'porcentajeIgv',
                    'tipSisIsc',
                    'porcentajeIsc',
                    'icbper',
                    'factorIcbper',
                    'descripcion',
                ]);

            $product['cantidad'] = 1;

            $this->reset('product_id');

            $this->addDetail($product);
        }

        if ($property === 'openModal' && !$value) {
            if ($this->product_key) {
                $this->resetValidation();
                $this->reset(['product', 'product_key']);
            }
        }
    }

    //Listeners
    #[On('clientAdded')]
    public function clientAdded($clientId)
    {
        $this->client_id = $clientId;
        $client = Client::find($clientId);

        if ($client) {

            $this->invoice->client = [
                'tipoDoc' => $client->tipoDoc,
                'numDoc' => $client->numDoc,
                'rznSocial' => $client->rznSocial,
                'address' => [
                    'direccion' => $client->direccion,
                ]
            ];

            $this->invoice->huesped['nombre'] = $client->rznSocial;
            $this->invoice->huesped['tipoDoc'] = $client->tipoDoc == '-' ? '0' : $client->tipoDoc;
            $this->invoice->huesped['numDoc'] = $client->numDoc;
        } else {

            $this->invoice->client = [
                'tipoDoc' => '',
                'numDoc' => '',
                'rznSocial' => '',
                'address' => [
                    'direccion' => '',
                ]
            ];
        }
    }

    //Métodos
    public function getSeries()
    {
        $this->reset(['invoice.serie', 'invoice.correlativo']);

        $this->series = DB::table('branch_company_document')
            ->select('serie as name', 'correlativo')
            ->where('branch_id', auth()->user()->branch->id)
            ->where('document_id', $this->invoice->tipoDoc)
            ->get();

        //Serie
        $serie = $this->series->first();
        if ($serie) {
            $this->invoice->serie = $serie->name;
            $this->invoice->correlativo = $serie->correlativo;
        }

        $correlativo = Invoice::where('company_id', session('company')->id)
            ->where('serie', $this->invoice->serie)
            ->where('production', session('company')->production)
            ->max('correlativo');

        if ($correlativo) {
            $this->invoice->correlativo = $correlativo + 1;
        }
    }
    public function getCorrelativo()
    {
        $this->reset(['invoice.correlativo']);

        $correlativo = DB::table('branch_company_document')
            ->select('correlativo')
            ->where('branch_id', auth()->user()->branch->id)
            ->where('document_id', $this->invoice->tipoDoc)
            ->where('serie', $this->invoice->serie)
            ->get();

        //correlativo
        $correlav = $correlativo->first();
        if ($correlav) {
            $this->invoice->correlativo = $correlav->correlativo;
        }

        $correlativo = Invoice::where('company_id', session('company')->id)
            ->where('serie', $this->invoice->serie)
            ->where('production', session('company')->production)
            ->max('correlativo');

        if ($correlativo) {
            $this->invoice->correlativo = $correlativo + 1;
        }
    }

    public function getOperations()
    {
        $this->operations = Operation::whereHas('documents', function ($query) {
            $query->where('document_id', $this->invoice->tipoDoc);
        })->where('active', true)
            ->get();

        $this->invoice->tipoOperacion = '0101';
    }

    //Detail
    public function editDetail($key)
    {
        $this->product_key = $key;

        $item = $this->invoice->details[$key];

        $this->product = [
            'cantidad' => $item['cantidad'],
            'codProducto' => $item['codProducto'],
            'unidad' => $item['unidad'],
            'mtoValor' => $item['mtoValorUnitario'] ?: $item['mtoValorGratuito'],
            'tipAfeIgv' => $item['tipAfeIgv'],
            'porcentajeIgv' => $item['porcentajeIgv'],
            'tipSisIsc' => $item['tipSisIsc'] ?: '',
            'porcentajeIsc' => $item['porcentajeIsc'] ?: 0,
            'icbper' => $item['icbper'] ? 1 : 0,
            'factorIcbper' => $item['factorIcbper'] ?? 0.20,
            'descripcion' => $item['descripcion'],
        ];

        $precioUnitario = $this->product['mtoValor'] * (1 + $this->product['porcentajeIgv'] / 100);
        $this->product['precioUnitario'] = round(($precioUnitario + PHP_FLOAT_EPSILON) * 100) / 100;

        $this->openModal = true;
    }

    public function saveDetail()
    {
        $this->validate([
            'product.codProducto' => 'nullable|string',
            'product.unidad' => 'required|exists:units,id',
            'product.descripcion' => 'required',
            'product.cantidad' => 'required|numeric|min:1',
            'product.mtoValor' => 'required|numeric|min:0.01',
            'product.tipAfeIgv' => 'required|exists:affectations,id',
            'product.porcentajeIgv' => [
                'required',
                Rule::when($this->product['tipAfeIgv'] <= 17, 'in:4,10,18', 'in:0')
            ],
            'product.tipSisIsc' => 'nullable|in:01,02,03',
            'product.porcentajeIsc' => 'nullable|numeric|min:0',
            'product.icbper' => 'required|boolean',
            'product.factorIcbper' => 'required_if:icbper,1|numeric|min:0',
        ], [], [
            'product.codProducto' => 'Código del producto',
            'product.unidad' => 'Unidad',
            'product.descripcion' => 'Descripción',
            'product.cantidad' => 'Cantidad',
            'product.mtoValor' => 'Valor unitario',
            'product.tipAfeIgv' => 'Tipo de afectación del IGV',
            'product.porcentajeIgv' => 'Porcentaje de IGV',
            'product.tipSisIsc' => 'Sistema ISC',
            'product.porcentajeIsc' => 'Porcentaje de ISC',
            'product.icbper' => 'ICBPER',
            'product.factorIcbper' => 'Factor ICBPER',
        ]);

        $this->addDetail($this->product);
    }

    public function addDetail($product)
    {
        $item = [];

        $item['codProducto'] = $product['codProducto'];
        $item['unidad'] = $product['unidad'];
        $item['descripcion'] = $product['descripcion'];
        $item['cantidad'] = $product['cantidad'];

        if (in_array($product['tipAfeIgv'], ['10', '17', '20', '30', '40'])) {
            $item['mtoValorUnitario'] = $product['mtoValor'];
            $item['mtoValorGratuito'] = 0;
        } else {
            $item['mtoValorUnitario'] = 0;
            $item['mtoValorGratuito'] = $product['mtoValor'];
        }

        $item['mtoValorVenta'] = $product['mtoValor'] * $product['cantidad'];

        $item['tipSisIsc'] = $product['tipSisIsc'];
        $item['mtoBaseIsc'] = $product['tipSisIsc'] ? $item['mtoValorVenta'] : 0;
        $item['porcentajeIsc'] = $product['tipSisIsc'] ? $product['porcentajeIsc'] : 0;
        $isc = $product['tipSisIsc'] ? $item['mtoBaseIsc'] * $item['porcentajeIsc'] / 100 : 0;
        $item['isc'] = round(($isc + PHP_FLOAT_EPSILON) * 100) / 100;

        $item['mtoBaseIgv'] = $item['mtoValorVenta'] + $item['isc'];

        $item['porcentajeIgv'] = $product['porcentajeIgv'];

        $igv = $item['porcentajeIgv'] ? $item['mtoBaseIgv'] * $item['porcentajeIgv'] / 100 : 0;
        $item['igv'] = round(($igv + PHP_FLOAT_EPSILON) * 100) / 100;

        $item['factorIcbper'] = $product['icbper'] ? $product['factorIcbper'] : 0;
        $item['icbper'] = $item['factorIcbper'] * $item['cantidad'];

        $item['tipAfeIgv'] = $product['tipAfeIgv'];

        $item['totalImpuestos'] = $item['igv'] + $item['isc'] + $item['icbper'];

        $item['mtoPrecioUnitario'] = ($item['mtoValorVenta'] + $item['igv'] + $item['isc']) / $item['cantidad'];

        if (isset($this->product_key)) {
            $this->invoice->details[$this->product_key] = $item;
        } else {
            $this->invoice->details[] = $item;
        }

        $this->reset(['product', 'product_key', 'openModal']);
        $this->invoice->getData();
    }

    public function removeDetail($key)
    {
        unset($this->invoice->details[$key]);

        //Reindexar el array
        $this->invoice->details = array_values($this->invoice->details);

        $this->invoice->getData();
    }

    public function recalculateDetail($key)
    {

        $item = $this->invoice->details[$key];

        if ($item['cantidad'] < 1) {
            //Eliminar el item
            $item['cantidad'] = 1;
        }

        if (in_array($item['tipAfeIgv'], ['10', '20', '30', '40'])) {
            $item['mtoValorVenta'] = $item['mtoValorUnitario'] * $item['cantidad'];
        } else {
            $item['mtoValorVenta'] = $item['mtoValorGratuito'] * $item['cantidad'];
        }

        $item['mtoBaseIsc'] = $item['tipSisIsc'] ? $item['mtoValorVenta'] : null;
        $isc = $item['tipSisIsc'] ? $item['mtoValorVenta'] * $item['porcentajeIsc'] / 100 : 0;
        $item['isc'] = round(($isc + PHP_FLOAT_EPSILON) * 100) / 100;

        $item['mtoBaseIgv'] = $item['mtoValorVenta'] + $item['isc'];

        $igv = $item['porcentajeIgv'] ? $item['mtoBaseIgv'] * $item['porcentajeIgv'] / 100 : 0;
        $item['igv'] = round(($igv + PHP_FLOAT_EPSILON) * 100) / 100;

        $item['icbper'] = $item['factorIcbper'] * $item['cantidad'];
        $item['totalImpuestos'] = $item['igv'] + $item['isc'];

        if (in_array($item['tipAfeIgv'], ['10', '20', '30', '40'])) {
            $item['mtoPrecioUnitario'] = ($item['mtoValorVenta'] + $item['igv'] + $item['isc']) / $item['cantidad'];
        } else {
            $item['mtoPrecioUnitario'] = 0;
        }

        $this->invoice->details[$key] = $item;

        $this->invoice->getData();
    }

    //Agregar cuota
    public function addCuota()
    {
        $this->invoice->cuotas[] = [
            'monto' => null,
            'fechaPago' => null,
        ];
    }

    public function removeCuota($key)
    {
        unset($this->invoice->cuotas[$key]);

        //Reindexar el array
        $this->invoice->cuotas = array_values($this->invoice->cuotas);
    }

    public function save()
    {

        if ($this->invoice->serie) {
            $correlativo = Invoice::where('company_id', session('company')->id)
                ->where('serie', $this->invoice->serie)
                ->where('production', session('company')->production)
                ->max('correlativo');

            if ($correlativo) {
                $this->invoice->correlativo = $correlativo + 1;
            }
        }

        $this->invoice->sendInvoice();
    }

    public function render()
    {
        return view('livewire.generate-invoice');
    }
}
