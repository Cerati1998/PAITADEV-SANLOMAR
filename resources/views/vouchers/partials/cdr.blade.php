<div>
    @if ($row->cdr_path)
        <button wire:click="downloadCDR({{$row->id}})" xs>
            <img class='h-6' src="{{asset('img/icons/cdr.png')}}"/>
        </button>
    @else
        <button wire:click="sendXml({{$row->id}})" xs>
            <img class='h-6' src="{{asset('img/icons/get_cdr.svg')}}"/>
        </button>
    @endif
</div>