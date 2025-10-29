<x-dashboard-layout title="Categoria de Contenedores | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Lineas',
        'route' => route('lines.index'),
    ],
    [
        'name' => 'Categorias Contenedor',
    ],
]">

    <x-slot name="action">
        <x-wire-button label="Nueva" x-on:click="$openModal('categoryContainerCreate')" blue />
    </x-slot>

    @livewire('masters.line-category-container-table', ['shipping_line' => $shipping_line], key('masters.line-category-container-table'))
    @livewire('masters.line-category-container-create', ['shipping_line' => $shipping_line], key('masters.line-category-container-create'))
</x-dashboard-layout>
