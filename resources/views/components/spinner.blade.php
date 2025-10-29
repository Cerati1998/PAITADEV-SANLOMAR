@props([
    'targets' => null,
    'message' => 'Procesando, por favor espere...',
    'delay' => 'longer',
    'logo' => null,
    'logoSize' => '48'
])

<div 
    wire:loading{{ $delay ? ".delay.{$delay}" : '' }}
    @if($targets)
        wire:target="{{ is_array($targets) ? implode(',', $targets) : $targets }}"
    @endif
    class="fixed inset-0 z-[9999]"
    style="display: none;"
>
    <!-- Overlay oscuro -->
    <div class="absolute inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-sm"></div>

    <!-- Contenedor centrado -->
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 flex flex-col items-center">
        <!-- Spinner Container con Logo -->
        <div class="relative flex items-center justify-center" style="width: 100px; height: 100px;">
            <!-- Spinner externo (gira lento) -->
            <div class="absolute inset-0 border-4 border-blue-500 border-t-transparent border-r-transparent rounded-full animate-spin" style="animation-duration: 1.5s;"></div>

            <!-- Spinner medio (gira rápido, dirección contraria) -->
            <div class="absolute inset-2 border-4 border-blue-400 border-b-transparent border-l-transparent rounded-full" style="animation: spin 1s linear infinite reverse;"></div>

            <!-- Logo en el centro con efecto pulse -->
            @if($logo)
                <div class="relative z-10 bg-white rounded-full p-2 shadow-lg">
                    <img 
                        src="{{ $logo }}" 
                        alt="Logo" 
                        class="animate-pulse"
                        style="width: {{ $logoSize }}px; height: {{ $logoSize }}px; object-fit: contain;"
                    >
                </div>
            @else
                <!-- Fallback -->
                <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full animate-pulse"></div>
            @endif
        </div>

        <!-- Texto -->
        <p class="mt-6 text-white text-sm font-semibold tracking-wide select-none">
            {{ $message }}
        </p>
    </div>
</div>

@push('css')
<style>
@keyframes spin-slow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.animate-spin-slow {
    animation: spin-slow 1.4s linear infinite;
}
</style>
@endpush
