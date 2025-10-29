<?php

namespace App\Livewire\Masters;

use App\Models\ReeferTechnology;
use Livewire\Component;

class TechnologyCreate extends Component
{
    public $openModal = false;
    public $technology = [
        'name' => '',
        'temperature_min' => '',
        'temperature_max' => '',
        'humidity_min' => '',
        'humidity_max' => '',
        'ventilation_min' => '',
        'ventilation_max' => '',
        'atmosphere_o2_min' => '',
        'atmosphere_o2_max' => '',
        'atmosphere_co2_min' => '',
        'atmosphere_co2_max' => '',
        'usage' => '',
    ];

    public function save()
    {
        $this->validate([
            'technology.name' => 'required|string|min:3|unique:reefer_technologies,name',
            'technology.usage' => 'string|min:4',
            'technology.temperature_min' => 'nullable|numeric|min:-100|max:100',
            'technology.temperature_max' => 'nullable|numeric|min:-100|max:100',
            'technology.humidity_min' => 'nullable|numeric|min:0|max:100',
            'technology.humidity_max' => 'nullable|numeric|min:0|max:100',
            'technology.ventilation_min' => 'nullable|numeric|min:-100|max:100',
            'technology.ventilation_max' => 'nullable|numeric|min:-100|max:100',
            'technology.atmosphere_o2_min' => 'nullable|numeric|min:0|max:100',
            'technology.atmosphere_o2_max' => 'nullable|numeric|min:0|max:100',
            'technology.atmosphere_co2_min' => 'nullable|numeric|min:0|max:100',
            'technology.atmosphere_co2_max' => 'nullable|numeric|min:0|max:100',
        ], [], [
            'technology.name' => 'Nombre de tecnología',
            'technology.usage' => 'Descripción de Uso o aplicación de tecnología',
            'technology.temperature_min' => 'Valor minimo de Temperatura',
            'technology.temperature_max' => 'Valor maximo de Temperatura',
            'technology.humidity_min' => 'Valor minimo de Humedad',
            'technology.humidity_max' => 'Valor maximo de Humedad',
            'technology.ventilation_min' => 'Valor minimo de Ventilación',
            'technology.ventilation_max' => 'Valor maximo de Ventilación',
            'technology.atmosphere_o2_min' => 'Valor minimo de o2',
            'technology.atmosphere_o2_max' => 'Valor maximo de o2',
            'technology.atmosphere_co2_min' => 'Valor minimo de co2',
            'technology.atmosphere_co2_max' => 'Valor maximo de co2',
        ]);

        ReeferTechnology::create($this->technology);

        $this->reset('technology', 'openModal');
        $this->dispatch('technologyAdded');
        $this->dispatch('swal', [
            'title' => 'Exito!',
            'text' => 'Tecnología de Contenedor creada correctamente',
            'icon' => 'success'
        ]);
    }
    public function render()
    {
        return view('livewire.masters.technology-create');
    }
}
