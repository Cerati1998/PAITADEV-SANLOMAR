<?php

namespace App\Livewire;

use App\Livewire\Forms\DespatchTransportistaForm;
use App\Models\Client;
use App\Models\DespatchTransportista;
use App\Models\Product;
use App\Models\ReasonTransfer;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class GenerateDespatchTransportista extends Component
{
    public DespatchTransportistaForm $despatch;

    public $series;

    public $destinatario_id;
    public $remitente_id;
    public $product_id;

    public $identities;
    public $reason_transfers;
    public $units;
    public $branches;
    public $related_documents;

    public $openModal = false;
    public $showSubcontratador = false;
    public $showDocumentoRelacionado = false;

    public $product = [
        'cantidad' => 1,
        'unidad' => 'NIU',
        'descripcion' => '',
        'codProducto' => '',
    ];

    public function mount()
    {
        $this->despatch->fechaEmision = now()->format('Y-m-d');
        $this->despatch->branch_id = session('branch')->id;
        $this->despatch->company_id = session('company')->id;
        $this->despatch->production = session('company')->production;

        $this->despatch->envio['fecTraslado'] = now()->format('Y-m-d');

        $this->getSeries();

        $this->reason_transfers = ReasonTransfer::all();
    }

    public function updated($property, $value)
    {
        if ($property === 'remitente_id') {
            $this->remitenteAdded($value);
        }

        if ($property === 'destinatario_id') {
            $this->destinatarioAdded($value);
        }

        if ($property === 'product_id') {
            $product = Product::find($value);
            $this->addDetail($product);
            $this->product_id = null;
        }
    }

    #[On('clientAdded')]
    public function remitenteAdded($id)
    {
        $this->remitente_id = $id;
        $client = Client::find($id);

        if ($client) {
            $this->despatch->remitente = [
                'tipoDoc' => $client->tipoDoc,
                'numDoc' => $client->numDoc,
                'rznSocial' => $client->rznSocial,
            ];
        } else {
            $this->despatch->remitente = [
                'tipoDoc' => '6',
                'numDoc' => '',
                'rznSocial' => '',
            ];
        }
    }

    public function destinatarioAdded($id)
    {
        $this->destinatario_id = $id;
        $client = Client::find($id);

        if ($client) {
            $this->despatch->destinatario = [
                'tipoDoc' => $client->tipoDoc,
                'numDoc' => $client->numDoc,
                'rznSocial' => $client->rznSocial,
            ];
        } else {
            $this->despatch->destinatario = [
                'tipoDoc' => '6',
                'numDoc' => '',
                'rznSocial' => '',
            ];
        }
    }

    public function getSeries()
    {
        $this->reset(['despatch.serie', 'despatch.correlativo']);

        $this->series = DB::table('branch_company_document')
            ->select('serie as name', 'correlativo')
            ->where('branch_id', auth()->user()->branch->id)
            ->where('document_id', $this->despatch->tipoDoc)
            ->get();

        $serie = $this->series->first();
        if ($serie) {
            $this->despatch->serie = $serie->name;
            $this->despatch->correlativo = $serie->correlativo;
        }

        $correlativo = DespatchTransportista::where('company_id', session('company')->id)
            ->where('serie', $this->despatch->serie)
            ->where('production', session('company')->production)
            ->max('correlativo');

        if ($correlativo) {
            $this->despatch->correlativo = $correlativo + 1;
        }
    }

    public function addVehicle()
    {
        $this->despatch->envio['vehiculo']['secundarios'][] = [
            'placa' => '',
        ];
    }

    public function removeVehicle($index)
    {
        unset($this->despatch->envio['vehiculo']['secundarios'][$index]);
        $this->despatch->envio['vehiculo']['secundarios'] = array_values($this->despatch->envio['vehiculo']['secundarios']);
    }

    public function addDriver()
    {
        $this->despatch->envio['choferes'][] = [
            'tipo' => 'Secundario',
            'tipoDoc' => '1',
            'nroDoc' => '',
            'licencia' => '',
            'nombres' => '',
            'apellidos' => '',
        ];
    }

    public function removeDriver($index)
    {
        unset($this->despatch->envio['choferes'][$index]);
        $this->despatch->envio['choferes'] = array_values($this->despatch->envio['choferes']);
    }

    public function editDetail($key)
    {
        $this->product = [
            'key' => $key,
            'cantidad' => $this->despatch->details[$key]['cantidad'],
            'unidad' => $this->despatch->details[$key]['unidad'],
            'descripcion' => $this->despatch->details[$key]['descripcion'],
            'codProducto' => $this->despatch->details[$key]['codigo'],
        ];

        $this->openModal = true;
    }

    public function addDetail($product = null)
    {
        if ($product) {
            $product['cantidad'] = 1;
        } else {
            $this->validate([
                'product.cantidad' => 'required|numeric|min:1',
                'product.unidad' => 'required|exists:units,id',
                'product.descripcion' => 'required',
                'product.codProducto' => 'nullable|string'
            ]);

            $product = $this->product;
        }

        $detail = [
            "cantidad" => $product['cantidad'],
            "unidad" => $product['unidad'],
            "descripcion" => $product['descripcion'],
            "codigo" => $product['codProducto'],
        ];

        if (isset($product['key'])) {
            $this->despatch->details[$product['key']] = $detail;
        } else {
            $this->despatch->details[] = $detail;
        }

        $this->reset(['product', 'openModal']);
    }

    public function removeDetail($key)
    {
        unset($this->despatch->details[$key]);
        $this->despatch->details = array_values($this->despatch->details);
    }

    public function save()
    {
        if ($this->despatch->serie) {
            $correlativo = DespatchTransportista::where('company_id', session('company')->id)
                ->where('serie', $this->despatch->serie)
                ->where('production', session('company')->production)
                ->max('correlativo');

            if ($correlativo) {
                $this->despatch->correlativo = $correlativo + 1;
            }
        }

        $this->despatch->sendDespatch();
    }

    public function render()
    {
        return view('livewire.generate-despatch-transportista');
    }
}
