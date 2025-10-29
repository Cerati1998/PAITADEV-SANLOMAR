<?php

namespace App\Livewire;

use App\Livewire\Forms\NoteForm;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\TypeCreditNote;
use App\Models\TypeDebitNote;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class GenerateNote extends Component
{
    public NoteForm $note;

    public $units, $affectations;

    public $series;

    public $reasons = [];

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
        $this->note->withValidator(function ($validator) {
            $validator->after(function ($validator) {
                if (!$this->client_id) {
                    
                    $validator->errors()->add('client_id', 'El campo cliente es obligatorio.');
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
        if (!in_array($this->note->tipoDoc, ['07', '08'])) {
            $this->note->tipoDoc = '07';
        }

        $this->note->fechaEmision = now()->format('Y-m-d');
        $this->note->company_id = session('company')->id;
        $this->note->production = session('company')->production;

        $this->getSeries();
        $this->getReasons();
    }

    //Ciclo de vida
    public function updated($property, $value)
    {
        if ($property === 'note.tipoDoc') {
            $this->getSeries();
            $this->getReasons();
        }

        if ($property === 'note.codMotivo') {

            $reason = collect($this->reasons)->firstWhere('id', $value);

            if ($reason) {
                $this->note->desMotivo = $reason->description;
            } else {
                $this->note->desMotivo = '';
            }
        }

        if ($property === 'client_id') {
            $client = Client::find($value);

            if ($client) {

                $this->note->client = [
                    'tipoDoc' => $client->tipoDoc,
                    'numDoc' => $client->numDoc,
                    'rznSocial' => $client->rznSocial,
                    'address' => [
                        'direccion' => $client->direccion,
                    ]
                ];
            } else {

                $this->note->client = [
                    'tipoDoc' => '6',
                    'numDoc' => '',
                    'rznSocial' => '',
                    'address' => [
                        'direccion' => '',
                    ]
                ];
            }
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

        if ($property === 'note.serie') {
            $this->note->tipDocAfectado = str_starts_with($value, 'F') ? '01' : '03';
        }
    }

    //Listeners
    #[On('clientAdded')]
    public function clientAdded($clientId)
    {
        $this->client_id = $clientId;
        $client = Client::find($clientId);

        $this->note->client = [
            'tipoDoc' => $client->tipoDoc,
            'numDoc' => $client->numDoc,
            'rznSocial' => $client->rznSocial,
            'address' => [
                'direccion' => $client->direccion,
            ]
        ];
    }

    //Metodos
    public function getSeries()
    {
        $this->reset(['note.serie', 'note.correlativo']);

        $this->series = DB::table('branch_company_document')
            ->select('serie as name', 'correlativo')
            ->where('branch_id', auth()->user()->branch->id)
            ->where('document_id', $this->note->tipoDoc)
            ->get();

        $serie = $this->series->first();
        if ($serie) {
            $this->note->serie = $serie->name;
            $this->note->correlativo = $serie->correlativo;
        }

        $correlativo = Invoice::where('company_id', session('company')->id)
            ->where('serie', $this->note->serie)
            ->where('production', session('company')->production)
            ->max('correlativo');

        if ($correlativo) {
            $this->note->correlativo = $correlativo + 1;
        }
        
    }

    public function getReasons()
    {
        if ($this->note->tipoDoc == '07') {
            $this->reasons = TypeCreditNote::get();
            $this->note->desMotivo = 'Anulación de la operación';
        }

        if ($this->note->tipoDoc == '08') {
            $this->reasons = TypeDebitNote::get();
            $this->note->desMotivo = 'Intereses por mora';
        }

        $this->note->codMotivo = '01';
    }

    //Detail
    public function editDetail($key)
    {
        $this->product_key = $key;

        $item = $this->note->details[$key];

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
        ],[],[
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
        }else{
            $item['mtoValorUnitario'] = 0;
            $item['mtoValorGratuito'] = $product['mtoValor'];
        }

        $item['mtoValorVenta'] = $product['mtoValor'] * $product['cantidad'];

        $item['tipSisIsc'] = $product['tipSisIsc'];
        $item['mtoBaseIsc'] = $product['tipSisIsc'] ? $item['mtoValorVenta'] : 0;
        $item['porcentajeIsc'] = $product['tipSisIsc'] ? $product['porcentajeIsc'] : 0;
        $item['isc'] = $product['tipSisIsc'] ? $item['mtoBaseIsc'] * $item['porcentajeIsc'] / 100 : 0;

        $item['mtoBaseIgv'] = $item['mtoValorVenta'] + $item['isc'];

        $item['porcentajeIgv'] = $product['porcentajeIgv'];
        $item['igv'] = $item['porcentajeIgv'] ? $item['mtoBaseIgv'] * $item['porcentajeIgv'] / 100 : 0;

        $item['factorIcbper'] = $product['icbper'] ? $product['factorIcbper'] : 0;
        $item['icbper'] = $item['factorIcbper'] * $item['cantidad'];
        
        $item['tipAfeIgv'] = $product['tipAfeIgv'];

        $item['totalImpuestos'] = $item['igv'] + $item['isc'] + $item['icbper'];

        $item['mtoPrecioUnitario'] = ($item['mtoValorVenta'] + $item['igv'] + $item['isc']) / $item['cantidad'];

        if (isset($this->product_key)) {
            $this->note->details[$this->product_key] = $item;
        } else {
            $this->note->details[] = $item;
        }

        $this->reset(['product', 'product_key', 'openModal']);
        $this->note->getData();
    }    

    public function removeDetail($key)
    {
        unset($this->note->details[$key]);

        //Reindexar el array
        $this->note->details = array_values($this->note->details);

        $this->note->getData();
    }

    public function recalculateDetail($key)
    {

        $item = $this->note->details[$key];

        if ($item['cantidad'] < 1) {
            //Eliminar el item
            $item['cantidad'] = 1;
        }

        if (in_array($item['tipAfeIgv'], ['10', '20', '30', '40'])) {
            $item['mtoValorVenta'] = $item['mtoValorUnitario'] * $item['cantidad'];
        }else{
            $item['mtoValorVenta'] = $item['mtoValorGratuito'] * $item['cantidad'];
        }

        $item['mtoBaseIsc'] = $item['tipSisIsc'] ? $item['mtoValorVenta'] : null;
        $item['isc'] = $item['tipSisIsc'] ? $item['mtoValorVenta'] * $item['porcentajeIsc'] / 100 : 0;

        $item['mtoBaseIgv'] = $item['mtoValorVenta'] + $item['isc'];

        if ($item['porcentajeIgv']) {
            $item['igv'] = $item['mtoBaseIgv'] * $item['porcentajeIgv'] / 100;
        }else{
            $item['igv'] = 0;
        }

        $item['icbper'] = $item['factorIcbper'] * $item['cantidad'];
        $item['totalImpuestos'] = $item['igv'] + $item['isc'];

        if(in_array($item['tipAfeIgv'], ['10', '20', '30', '40'])){
            $item['mtoPrecioUnitario'] = ($item['mtoValorVenta'] + $item['igv'] + $item['isc']) / $item['cantidad'];
        }else{
            $item['mtoPrecioUnitario'] = 0;
        }

        $this->note->details[$key] = $item;

        $this->note->getData();

    }

    public function save()
    {

        if ($this->note->serie) {
            $correlativo = Invoice::where('company_id', session('company')->id)
                ->where('serie', $this->note->serie)
                ->where('production', session('company')->production)
                ->max('correlativo');

            if ($correlativo) {
                $this->note->correlativo = $correlativo + 1;
            }
        }

        $this->note->sendInvoice();
    }

    public function render()
    {
        return view('livewire.generate-note');
    }
}
