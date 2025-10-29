@props([
    'title' => config('app.name'),
    'breadcrumbs' => []
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{asset('vendor/fontawesome-free-6.6.0-web/css/all.min.css')}}">

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('img/logos/favicon.png') }}">

    <!-- Scripts -->
    <wireui:scripts />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Styles -->
    @livewireStyles

    @stack('css')
</head>

<body class="font-sans antialiased !p-0 bg-gray-50 sm:overflow-auto" :class="{
    'overflow-hidden': open,
}"
    x-data="{ open: false }">

    @include('layouts.partials.admin.navigation')
    
    @include('layouts.partials.admin.sidebar')
    
    <div class="p-4 sm:ml-64">

        <div class="mt-14 flex items-center">

            @include('layouts.partials.admin.breadcrumb')

            @isset($action)
                <div class="ml-auto">

                    {{ $action }}
                </div>
            @endisset

        </div>


        <div class="p-4 border-2 border-gray-200 border-dashed rounded-lg dark:border-gray-700">

            {{ $slot }}

        </div>
    </div>

    <div style="display: none" x-show="open" x-on:click="open = false"
        class="bg-gray-900 bg-opacity-50 dark:bg-opacity-80 fixed inset-0 z-30 sm:hidden"></div>

    @livewireScripts

    <script src="{{asset('vendor/sweetalert2/sweetalert.min.js')}}"></script>

    <script>
        Livewire.on('swal', data => {
            Swal.fire(data[0]);
        });
    </script>

    @if (session('swal'))
        <script>
            Swal.fire({!! json_encode(session('swal')) !!});
        </script>
    @endif

    @if ($errors->any())
        <script>
            Swal.fire({
                title: 'Ocurrió un error',
                html: `<ul class="text-left">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>`,
                icon: 'error',
            });
        </script>
    @endif

    @stack('js')
</body>

</html>