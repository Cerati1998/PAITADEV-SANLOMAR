<?php

namespace App\Livewire;

use App\Livewire\Forms\DespatchForm;
use App\Models\Client;
use App\Models\Despatch;
use App\Models\Product;
use App\Models\ReasonTransfer;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class GenerateDespatch extends Component
{
    public DespatchForm $despatch;

    public $series;

    public $client_id;
    public $product_id;

    public $identities;
    public $reason_transfers;
    public $units;

    public $openModal = false;
    public $product = [
        'cantidad' => 1,
        'unidad' => 'NIU',
        'descripcion' => '',
        'codProducto' => '',
    ];

    public $ml1 = false;

    public function boot()
    {
        //Verificar si hay errores de validacion en el form object
        $this->despatch->withValidator(function ($validator) {
            $validator->after(function ($validator) {

                if ($this->client_id) {
                    if (in_array($this->despatch->envio['codTraslado'], ['02', '04'])) {

                        if (empty($this->despatch->destinatario['tipoDoc'] == '6' && $this->despatch->destinatario['numDoc'] == session('company')->ruc)) {
                            $validator->errors()->add('client_id', 'El cliente debe ser la misma empresa.');
                        }

                    }
                }else{
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
        $this->despatch->fechaEmision = now()->format('Y-m-d');
        $this->despatch->branch_id = session('branch')->id;
        $this->despatch->company_id = session('company')->id;
        $this->despatch->production = session('company')->production;

        $this->despatch->envio['fecTraslado'] = now()->format('Y-m-d');
        $this->despatch->envio['partida']['ruc'] = session('company')->ruc;
        $this->despatch->envio['llegada']['ruc'] = session('company')->ruc;

        $this->getSeries();

        //Razones de traslado
        $this->reason_transfers = ReasonTransfer::all();
    }

    public function updated($property, $value)
    {
        if ($property === 'client_id') {
            $this->clientAdded($value);
        }

        if ($property === 'product_id') {
            
            $product = Product::find($value);

            $this->addDetail($product);

            $this->product_id = null;

        }

        if ($property === 'ml1') {
            
            if ($value) {
                $this->despatch->envio['indicadores'][] = 'SUNAT_Envio_IndicadorTrasladoVehiculoM1L';
            }else{
                $this->despatch->envio['indicadores'] = array_diff($this->despatch->envio['indicadores'], ['SUNAT_Envio_IndicadorTrasladoVehiculoM1L']);
            }

        }

        /* if ($property === 'despatch.envio.codTraslado') {
            if ($value == '04') {
       
                $this->despatch->envio['partida']['ruc'] = session('company')->ruc;
                $this->despatch->envio['llegada']['ruc'] = session('company')->ruc;

            }else{

                $this->despatch->envio['partida']['ruc'] = null;
                $this->despatch->envio['partida']['codLocal'] = null;

                $this->despatch->envio['llegada']['ruc'] = null;
                $this->despatch->envio['llegada']['codLocal'] = null;
            }
        } */
    }

    #[On('clientAdded')]
    public function clientAdded($clientId)
    {
        $this->client_id = $clientId;
        $client = Client::find($clientId);

        if ($client) {

            $this->despatch->destinatario = [
                'tipoDoc' => $client->tipoDoc,
                'numDoc' => $client->numDoc,
                'rznSocial' => $client->rznSocial,
                'address' => [
                    'direccion' => $client->direccion,
                ]
            ];

        }else{

            $this->despatch->destinatario = [
                'tipoDoc' => '6',
                'numDoc' => '',
                'rznSocial' => '',
                'address' => [
                    'direccion' => '',
                ]
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

        $correlativo = Despatch::where('company_id', session('company')->id)
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
        }else{
            $this->despatch->details[] = $detail;
        }

        $this->reset(['product', 'openModal']);
        /* $this->invoice->getData(); */
    }

    public function removeDetail($key)
    {
        unset($this->despatch->details[$key]);
        $this->despatch->details = array_values($this->despatch->details);
    }

    public function save()
    {

        if ($this->despatch->serie) {
            $correlativo = Despatch::where('company_id', session('company')->id)
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
        return view('livewire.generate-despatch');
    }
}
