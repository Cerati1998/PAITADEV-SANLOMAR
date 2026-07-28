<?php

namespace App\Livewire;

use App\Livewire\Forms\DespatchForm;
use App\Models\Client;
use App\Models\Despatch;
use App\Models\Driver;
use App\Models\Product;
use App\Models\Provider;
use App\Models\ReasonTransfer;
use App\Models\Transport;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class GenerateDespatch extends Component
{
    public DespatchForm $despatch;

    public $series;

    public $client_id;
    public $product_id;
    public $provider_id;
    public $transportista_id;

    public $identities;
    public $reason_transfers;
    public $units;
    public $branches;

    // Vehículos/choferes del transporte privado (modTraslado='02'): se
    // seleccionan de la flota de CUALQUIER transportista registrado.
    public $vehiculo_id = null;
    public $vehiculo_info = null;
    public $vehiculo_secundario_ids = [];
    public $vehiculo_secundario_info = [];
    public $chofer_ids = [null];

    // Transportista bajo el cual se registra un vehículo/chofer nuevo desde
    // el botón "+" (la flota no está restringida a un único transportista aquí).
    public $quick_add_transport_id = null;
    public ?Transport $quickAddTransport = null;

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
                        if ($this->despatch->envio['codTraslado'] === '04' && $this->despatch->envio['partida']['codLocal'] == $this->despatch->envio['llegada']['codLocal']) {
                            $validator->errors()->add('client_id', 'El código de establecimiento de partida y llegada no pueden ser el mismo.');
                        }
                    }
                } else {
                    $validator->errors()->add('client_id', 'El campo cliente es obligatorio.');
                }

                if ($this->despatch->envio['codTraslado'] === '02' && !$this->provider_id) {
                    $validator->errors()->add('provider_id', 'El campo proveedor es obligatorio para el motivo de traslado Compra.');
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

        if ($property === 'provider_id') {
            $this->providerAdded($value);
        }

        if ($property === 'transportista_id') {
            $this->transportistaAdded($value);
        }

        if ($property === 'quick_add_transport_id') {
            $this->quickAddTransport = $value ? Transport::find($value) : null;
        }

        if ($property === 'product_id') {

            $product = Product::find($value);

            $this->addDetail($product);

            $this->product_id = null;

        }

        if ($property === 'vehiculo_id') {
            $vehicle = $value ? Vehicle::find($value) : null;
            $this->despatch->envio['vehiculo']['placa'] = $vehicle->code ?? '';
            $this->vehiculo_info = $vehicle;
        }

        if (preg_match('/^vehiculo_secundario_ids\.(\d+)$/', $property, $matches)) {
            $index = (int) $matches[1];
            $vehicle = $value ? Vehicle::find($value) : null;
            $this->despatch->envio['vehiculo']['secundarios'][$index]['placa'] = $vehicle->code ?? '';
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

        if ($property === 'ml1') {

            if ($value) {
                $this->despatch->envio['indicadores'][] = 'SUNAT_Envio_IndicadorTrasladoVehiculoM1L';
            } else {
                $this->despatch->envio['indicadores'] = array_diff($this->despatch->envio['indicadores'], ['SUNAT_Envio_IndicadorTrasladoVehiculoM1L']);
            }

        }

        if ($property === 'despatch.envio.codTraslado') {
            if ($value == '04') {

                $this->despatch->envio['partida']['ruc'] = session('company')->ruc;
                $this->despatch->envio['llegada']['ruc'] = session('company')->ruc;

            } else {

                $this->despatch->envio['partida']['ruc'] = null;
                $this->despatch->envio['partida']['codLocal'] = null;

                $this->despatch->envio['llegada']['ruc'] = null;
                $this->despatch->envio['llegada']['codLocal'] = null;
            }

            if ($value !== '02') {
                $this->provider_id = null;
                $this->despatch->envio['tercero'] = [
                    'tipoDoc' => '6',
                    'numDoc' => '',
                    'rznSocial' => '',
                ];
            }
        }

        if ($property === 'despatch.envio.modTraslado') {
            if ($value !== '01') {
                $this->transportista_id = null;
                $this->despatch->envio['transportista'] = [
                    'tipoDoc' => '6',
                    'numDoc' => '',
                    'rznSocial' => '',
                    'nroMtc' => '',
                ];
            }

            if ($value !== '02') {
                $this->vehiculo_id = null;
                $this->vehiculo_info = null;
                $this->vehiculo_secundario_ids = [];
                $this->vehiculo_secundario_info = [];
                $this->chofer_ids = [null];
                $this->despatch->envio['vehiculo'] = ['placa' => '', 'secundarios' => []];
                $this->despatch->envio['choferes'] = [[
                    'tipo' => 'Principal',
                    'tipoDoc' => '1',
                    'nroDoc' => '',
                    'licencia' => '',
                    'nombres' => '',
                    'apellidos' => '',
                ]];
            }
        }
    }

    #[On('transportAdded')]
    public function transportistaAdded($id)
    {
        $this->transportista_id = $id;
        $transport = Transport::find($id);

        if ($transport) {
            $this->despatch->envio['transportista'] = [
                'tipoDoc' => $transport->tipoDoc,
                'numDoc' => $transport->numDoc,
                'rznSocial' => $transport->razonSocial,
                'nroMtc' => $transport->nroMtc ?? '',
            ];
        } else {
            $this->despatch->envio['transportista'] = [
                'tipoDoc' => '6',
                'numDoc' => '',
                'rznSocial' => '',
                'nroMtc' => '',
            ];
        }

        // El transportista recién creado también queda seleccionado como
        // "transportista para altas nuevas" (transporte privado), para poder
        // agregar de inmediato sus vehículos/choferes.
        $this->quick_add_transport_id = $id;
        $this->quickAddTransport = $transport;
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

        } else {

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

    public function providerAdded($providerId)
    {
        $this->provider_id = $providerId;
        $provider = Provider::find($providerId);

        if ($provider) {

            $this->despatch->envio['tercero'] = [
                'tipoDoc' => $provider->tipoDoc,
                'numDoc' => $provider->numDoc,
                'rznSocial' => $provider->rznSocial,
            ];

        } else {

            $this->despatch->envio['tercero'] = [
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
