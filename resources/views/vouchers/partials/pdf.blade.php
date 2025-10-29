<div>
    <button wire:click="downloadPDF({{$row->id}})" xs>
        <img class='h-6' src="{{asset('img/icons/pdf_cpe.svg')}}"/>
    </button>
</div>