<div class="relative group inline-block">
    <span class="cursor-pointer">{{ $slot }}</span>
    <div
        class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2
               bg-gray-900 text-white text-[11px] rounded-md px-3 py-1.5
               shadow-md opacity-0 scale-95 transition-all duration-200 ease-out
               group-hover:opacity-100 group-hover:scale-100 
               whitespace-nowrap z-50 min-w-max
               after:content-[''] after:absolute after:top-full
               after:left-1/2 after:-translate-x-1/2 after:border-4 after:border-transparent
               after:border-t-gray-900">
        {{ $text }}
    </div>
</div>