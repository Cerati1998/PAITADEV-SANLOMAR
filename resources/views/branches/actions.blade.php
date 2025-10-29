<div class="w-36">
    <x-wire-button green icon="arrow-top-right-on-square" href="{{ route('branches.edit', $branch) }}" title="Editar" sm />
    <x-wire-button blue icon="newspaper" href="{{ route('branches.series', $branch) }}" title="Series" sm />
    <x-wire-button icon="key" title="Integraciones" href="{{ route('branches.integrations', $branch) }}" sm>
    </x-wire-button>
</div>
