@props(['width' => '7xl'])

@php
    switch ($width) {
        case '2xl':
            $maxWidth = 'max-w-2xl';
            break;
        case '3xl':
            $maxWidth = 'max-w-3xl';
            break;
        case '4xl':
            $maxWidth = 'max-w-4xl';
            break;
        case '5xl':
            $maxWidth = 'max-w-5xl';
            break;
        case '6xl':
            $maxWidth = 'max-w-6xl';
            break;
        case '7xl':
            $maxWidth = 'max-w-7xl';
            break;

        case '8xl':
            $maxWidth = 'max-w-[92rem]';
            break;
        
        default:
            $maxWidth = 'max-w-7xl';
            break;
    }
@endphp

<div {{ $attributes->merge(['class' => $maxWidth .' mx-auto sm:px-6 lg:px-8']) }}>
    {{$slot}}
</div>