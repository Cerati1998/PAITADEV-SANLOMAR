<x-guest-layout>

    <x-container class="py-8 px-4">
        
        <a href="{{route('dashboard')}}">
            <img class="h-10 mb-3 md:mb-0" src="{{asset('img/logos/inspectec.png')}}" alt="Coders Free">
        </a>

        <h1 class="text-2xl text-center font-bold text-gray-700 mb-6">
            ¿Con qué empresa deseas trabajar?
        </h1>


        <div class="flex justify-center flex-wrap">
            @foreach ($companies as $company)
            
                <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 xl:w-1/6 px-2 py-2">
                    <a href="{{route('companies.show', $company)}}" 
                        class="block w-full py-8 px-3 overflow-hidden border-2 border-blue-400 hover:border-blue-600 text-center rounded-lg">
                        <i class="fa-regular fa-building text-4xl mb-4"></i>

                        <p class="truncate">
                            {{-- {{$company->razonSocial}} --}}
                            {{
                                Str::limit($company->razonSocial, 20, '...')
                            }}
                        </p>
                    </a>
                </div>

            @endforeach

            <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 xl:w-1/6 px-2 py-2">
                <a href="{{route('companies.create')}}" 
                    class="block w-full py-8 px-3 overflow-hidden border-2 bg-blue-500 text-white text-center rounded-lg"
                    {{-- class="block w-full py-8 px-3 bg-blue-500 text-white text-center rounded-lg" --}}>
                    
                    <i class="fa-solid fa-circle-plus text-4xl mb-4"></i>

                    <p class="px-3 truncate">
                        Agregar empresa
                    </p>

                </a>
            </div>

        </div>


        {{-- <div class="flex justify-center flex-wrap space-x-6">

            @foreach ($companies as $company)
  

                <div class="w-40 overflow-hidden">
                    <a href="{{route('companies.show', $company)}}" class="block w-full py-8 px-3 overflow-hidden border-2 border-blue-400 hover:border-blue-600 text-center rounded-lg">
                        <i class="fa-regular fa-building text-4xl mb-4"></i>

                        <p class="truncate">
                            {{$company->razonSocial}}
                        </p>
                    </a>
                </div>
                

            @endforeach


            <a href="{{route('companies.create')}}" 
                class="bg-blue-500 text-white py-8 text-center rounded-lg w-40">
                
                <i class="fa-solid fa-circle-plus text-4xl mb-4"></i>

                <p class="px-3 truncate">
                    Agregar empresa
                </p>

            </a>

        </div> --}}

        <form action="{{route('logout')}}" method="POST" class="flex justify-center mt-4">
            @csrf

            <button type="submit" class="underline hover:text-blue-700">
                Cerrar sesión
            </button>
        </form>

    </x-container>

</x-guest-layout>