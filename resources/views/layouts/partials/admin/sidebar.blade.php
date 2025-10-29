@php
    $links = [
        [
            'name' => 'Dashboard',
            'icon' => 'fa-solid fa-gauge',
            'route' => route('admin.dashboard'),
            'active' => request()->routeIs('admin.dashboard'),
        ],
    ];
@endphp

<aside id="logo-sidebar"
    class="fixed top-0 left-0 z-40 w-64 h-[100dvh] pt-20 transition-transform bg-white border-r border-gray-200 sm:translate-x-0 dark:bg-gray-800 dark:border-gray-700 -translate-x-full"
    :class="{
        'transform-none': open,
        '-translate-x-full': !open
    }" aria-label="Sidebar">
    <div class="h-full px-3 pb-4 overflow-y-auto bg-white dark:bg-gray-800">
        <ul class="space-y-2 font-medium">
            @foreach ($links as $link)
                @canany($link['can'] ?? [null])
                    <li>
                        @isset($link['header'])
                            <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase dark:text-gray-400">
                                {{ $link['header'] }}
                            </div>
                        @else
                            @isset($link['submenu'])
                                <div x-data="{ open: {{ $link['active'] ? 'true' : 'false' }} }">

                                    <button type="button" x-on:click="open = !open"
                                        class="flex items-center w-full p-2 text-gray-900 transition duration-75 rounded-lg group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700 {{ $link['active'] ? 'bg-gray-100' : 'hover:bg-gray-100' }}"
                                        aria-controls="dropdown-example" data-collapse-toggle="dropdown-example">

                                        <span class="inline-flex w-6 h-6 justify-center items-center">
                                            <i class="{{ $link['icon'] }} text-gray-500"></i>
                                        </span>
                                        <span class="flex-1 ml-3 text-left whitespace-nowrap" sidebar-toggle-item>
                                            {{ $link['name'] }}
                                        </span>

                                        <i class="fa-solid fa-angle-down"
                                            :class="{
                                                'fa-angle-down': !open,
                                                'fa-angle-up': open,
                                            }"></i>

                                    </button>

                                    <ul class="hidden py-2 space-y-2"
                                        :class="{
                                            'hidden': !open,
                                        }">
                                        @foreach ($link['submenu'] as $link)
                                            <li>

                                                <a href="{{ $link['route'] }}"
                                                    class="flex items-center w-full p-2 text-gray-900 transition duration-75 rounded-lg pl-4 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700 {{ $link['active'] ? 'bg-gray-100' : 'hover:bg-gray-100' }}">

                                                    @isset($link['icon'])
                                                        <span class="inline-flex w-6 h-6 justify-center items-center mr-2">
                                                            <i class="{{ $link['icon'] }} text-gray-500"></i>
                                                        </span>
                                                    @endisset

                                                    <span>
                                                        {{ $link['name'] }}
                                                    </span>
                                                </a>
                                            </li>
                                        @endforeach

                                    </ul>
                                </div>
                            @else
                                <a href="{{ $link['route'] ?? '#' }}"
                                    class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 {{ $link['active'] ? 'bg-gray-100' : 'hover:bg-gray-100' }}">

                                    <span class="inline-flex w-6 h-6 justify-center items-center">
                                        <i class="{{ $link['icon'] }} text-gray-500"></i>
                                    </span>

                                    <span class="ml-3">
                                        {{ $link['name'] }}
                                    </span>
                                </a>
                            @endisset
                        @endisset
                    </li>
                @endcanany
            @endforeach

        </ul>
    </div>
</aside>