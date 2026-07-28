<?php

namespace App\Livewire;

use App\Livewire\Forms\DespatchTransportistaForm;
use App\Models\Client;
use App\Models\DespatchTransportista;
use App\Models\Driver;
use App\Models\Product;
use App\Models\ReasonTransfer;
use App\Models\Transport;
use App\Models\Vehicle;
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

    // Transportista propio (RUC = RUC de la empresa): único autorizado a
    // emitir esta guía, por lo que sus vehículos/choferes son los únicos
    // seleccionables aquí.
    public ?Transport $ownTransport = null;

    public $vehiculo_id = null;
    public $vehiculo_info = null;
    public $vehiculo_secundario_ids = [];
    public $vehiculo_secundario_info = [];
    public $chofer_ids = [];

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

        $this->ownTransport = Transport::where('tipoDoc', '6')
            ->where('numDoc', session('company')->ruc)
            ->where('branch_id', session('branch')->id)
            ->first();

        if ($this->ownTransport) {
            $this->despatch->envio['mtc'] = $this->ownTransport->nroMtc ?? '';
        }

        $this->chofer_ids = [null];

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

        if ($property === 'vehiculo_id') {
            $vehicle = $value ? Vehicle::find($value) : null;
            $this->despatch->envio['vehiculo']['placa'] = $vehicle->code ?? '';
            $this->despatch->envio['vehiculo']['nroAutorizacion'] = $vehicle->autorization_number ?? '';
            $this->vehiculo_info = $vehicle;
        }

        if (preg_match('/^vehiculo_secundario_ids\.(\d+)$/', $property, $matches)) {
            $index = (int) $matches[1];
            $vehicle = $value ? Vehicle::find($value) : null;
            $this->despatch->envio['vehiculo']['secundarios'][$index]['placa'] = $vehicle->code ?? '';
            $this->despatch->envio['vehiculo']['secundarios'][$index]['nroAutorizacion'] = $vehicle->autorization_number ?? '';
            $this->vehiculo_secundario_info[$index] = $vehicle;
        }

        if (preg_match('/^chofer_ids\.(\d+)$/', $property, $matches)) {
            $index = (int) $matches[1];
            $driver = $value ? Driver::find($value) : null;

            $this->despatch->envio['choferes'][$index] = [
                'tipo' => $index === 0 ? 'Principal' : 'Secundario',
                'tipoDoc' => $driver->tipoDoc ?? '1',
                'nroDoc' => $driver->numDoc ?? '',
                'licencia' => $driver->brevete ?? '',
                'nombres' => $driver->nombres ?? '',
                'apellidos' => $driver->apellidos ?? '',
            ];
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
            'nroAutorizacion' => '',
        ];
        $this->vehiculo_secundario_ids[] = null;
        $this->vehiculo_secundario_info[] = null;
    }

    public function removeVehicle($index)
    {
        unset($this->despatch->envio['vehiculo']['secundarios'][$index]);
        $this->despatch->envio['vehiculo']['secundarios'] = array_values($this->despatch->envio['vehiculo']['secundarios']);

        unset($this->vehiculo_secundario_ids[$index]);
        $this->vehiculo_secundario_ids = array_values($this->vehiculo_secundario_ids);

        unset($this->vehiculo_secundario_info[$index]);
        $this->vehiculo_secundario_info = array_values($this->vehiculo_secundario_info);
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
        $this->chofer_ids[] = null;
    }

    public function removeDriver($index)
    {
        unset($this->despatch->envio['choferes'][$index]);
        $this->despatch->envio['choferes'] = array_values($this->despatch->envio['choferes']);

        unset($this->chofer_ids[$index]);
        $this->chofer_ids = array_values($this->chofer_ids);
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
