@component('mail::message')
# {{strtoupper($document->type)}} ELECTRÓNICA {{$document->serie}}-{{str_pad($document->correlativo, 6, '0', STR_PAD_LEFT)}}

Estimados {{$client['rznSocial']}}

Se adjunta en este mensaje una {{strtoupper($document->type)}} ELECTRÓNICA

@component('mail::panel')
- **{{strtoupper($document->type)}} ELECTRÓNICA {{$document->serie}}-{{str_pad($document->correlativo, 6, '0', STR_PAD_LEFT)}}**
- Fecha de emisión: **{{$document->fechaEmision->format('d/m/Y')}}**
@if ($document->fecVencimiento)
- Fecha de vencimiento: **{{$document->fecVencimiento->format('d/m/Y')}}**
@endif
@if ($document->mtoImpVenta)
- Total: **{{$document->currency->symbol}} {{$document->mtoImpVenta}}**
@endif
@endcomponent

Se adjunta en este mensaje el documento electrónico en formatos PDF y XML. La representación impresa en PDF tiene la misma validez que una emitida de manera tradicional.

También puedes ver el documento visitando el siguiente link.

@component('mail::button', ['url' => Storage::url($document->pdf_path) , 'color' => 'primary'])
VER {{strtoupper($document->type)}} ELECTRÓNICA
@endcomponent

Atentamente, <br>
**{{strtoupper($document->company['razonSocial'])}}** <br>
**RUC {{$document->company['ruc']}}**

@endcomponent