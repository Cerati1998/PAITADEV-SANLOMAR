<?php

namespace App\Livewire\Masters;

use App\Models\Vessel;
use Livewire\Component;

class VesselCreate extends Component
{
    public $isExtern = false;
    public $openModal = false;
    public $shippingLineId = null;

    public $vessel = [
        'imo_number' => '',
        'name' => '',
        'type' => '',
        'pallets' => 0
    ];

    public function closeModal()
    {
        $this->reset('openModal', 'vessel');
    }

    protected $listeners = ['setShippingLineId'];

    public function setShippingLineId($shippingLineId = null)
    {
        $this->shippingLineId = $shippingLineId;
        $this->isExtern = true;
    }

    public function mount($shipping_line = null)
    {

        if ($shipping_line) {
            $this->shippingLineId = $shipping_line->id;
        }
    }

    public function save()
    {
        if (!$this->shippingLineId || $this->shippingLineId === '') {
            $this->dispatch('swal', [
                'title' => "Error!",
                'text' => 'No se ha podido encontrar la Linea, asegurese de escoger una',
                'icon' => 'error'
            ]);
            return;
        }

        $this->vessel['type'] = (!$this->vessel['type'] || $this->vessel['type'] === '') ? 'container' : $this->vessel['type'];

        $this->validate([
            'vessel.imo_number' => 'string',
            'vessel.name' => [
                'required',
                'string',
                'min:3',
                'unique:vessels,name'
            ],
            'vessel.type' => 'required|string|in:container,bulk,tanker',
            'vessel.pallets' => 'integer'
        ], [], [
            'vessel.imo_number' => 'IMO de Nave',
            'vessel.name' => 'Nombre de Nave',
            'vessel.type' => 'Categoria',
            'vesse.pallets' => 'Capacidad de Pallets'
        ]);



        Vessel::create([
            ...$this->vessel,
            'shipping_line_id' => $this->shippingLineId
        ]);
        $this->reset('openModal', 'vessel');
        $this->dispatch('vesselAdded');

        if ($this->isExtern) {
            $this->dispatch('vesselExternAdded',$this->shippingLineId);
        }

        $this->dispatch('swal', [
            'title' => "Exito!",
            'text' => 'Nave agregada correctamente',
            'icon' => 'success'
        ]);
    }

    public function render()
    {
        return view('livewire.masters.vessel-create');
    }
}
