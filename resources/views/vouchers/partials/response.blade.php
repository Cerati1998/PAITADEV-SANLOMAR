<div>

    @if (isset($value) && $value['success'])
        <button wire:click="showResponse({{$row->id}})" xs>
            <x-wire-icon name="check-circle" class="w-6.5 h-6.5 text-green-500" solid />
        </button>
    @else

        @if (empty($value))
            
            <button wire:click="sendXml({{$row->id}})" xs>
                <img class='h-6' src="{{asset('img/icons/get_cdr.svg')}}"/>
            </button>

        @else

            <button wire:click="showResponse({{$row->id}})" xs>
                <i class="text-xl fas fa-ban text-red-500"></i>
            </button>

        @endif

    @endif

</div>