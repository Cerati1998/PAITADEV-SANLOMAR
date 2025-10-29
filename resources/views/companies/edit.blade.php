<x-dashboard-layout title="Compañías | {{ session('company')->razonSocial }}" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'route' => route('dashboard'),
    ],
    [
        'name' => 'Compañía',
    ],
]">

    <form action="{{ route('companies.update', $company) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <x-validation-errors class="mb-4" />

        <x-wire-card>
            {{-- Imagen --}}
            <div class="flex flex-col items-center mb-4">

                <label
                    class="border flex items-center w-full lg:max-w-96 aspect-[4/1] px-6 cursor-pointer overflow-hidden">
                    <img id="imgPreview" class="w-full object-cover object-center" src="{{ $company->logo }}">

                    <input name="logo" type="file" accept=".png, .jpg, .jpeg" class="hidden"
                        onchange="preview_image(event, '#imgPreview')">
                </label>

                <p>
                    {{-- png o jpg. Máximo 2MB --}}
                    <span class="text-sm text-gray-500">
                        La imagen debe ser en formato png o jpg.
                    </span>
                </p>

            </div>

            <div class="space-y-4 mb-4">

                <x-wire-input name="ruc" value="{{ old('ruc', $company->ruc) }}" placeholder="Ingrese su RUC" />

                <x-wire-input name="razonSocial" value="{{ old('razonSocial', $company->razonSocial) }}"
                    placeholder="Ingrese su razón social" />

                <x-wire-input name="nombreComercial" value="{{ old('nombreComercial', $company->nombreComercial) }}"
                    placeholder="Ingrese su nombre comercial" />

                <x-wire-input name="direccion" value="{{ old('direccion', $company->direccion) }}"
                    placeholder="Ingrese su dirección" />
                    
                <x-wire-input name="detraction_account"
                    value="{{ old('detraction_account', $company->detraction_account) }}"
                    placeholder="Ingrese su cuenta de Detracción" />

            </div>


            {{-- Datos extra --}}
            <div class="md:grid md:grid-cols-2 md:gap-6">
                <div class="mb-4 md:mb-0">
                    <x-wire-textarea name="invoice_header"
                        placeholder="Datos extra para la representación impresa (Ej. teléfonos, correos, etc.)"
                        rows="2">{{ old('invoice_header', $company->invoice_header) }}</x-wire-textarea>
                </div>

                <div>
                    <x-wire-textarea name="invoice_footer"
                        placeholder="Pie de página para la representación impresa (Ej. saludos, cuents, etc.)"
                        rows="2">{{ old('invoice_footer', $company->invoice_footer) }}</x-wire-textarea>
                </div>
            </div>

            <hr class="my-4">

            {{-- Credenciales --}}
            <div class="mb-4">
                <x-wire-input class="mb-4" label="Usuario Sol secundario" name="sol_user" :value="old('sol_user', $company->sol_user)"
                    placeholder="Ingrese su usuario sol secundario" />

                <x-wire-input class="mb-4" label="Clave Sol" name="sol_pass" :value="old('sol_pass', $company->sol_pass)"
                    placeholder="Ingrese su clave sol" />

                <x-wire-input class="mb-4" label="Cliente id" name="client_id" :value="old('client_id', $company->client_id)"
                    placeholder="Ingrese su cliente ID" />

                <x-wire-input class="mb-4" label="Cliente secret" name="client_secret" :value="old('client_secret', $company->client_secret)"
                    placeholder="Ingrese su cliente secret" />

                <div class="mb-4">
                    <x-label class="mb-2">
                        Certificado
                    </x-label>

                    <div class="relative">
                        <div class="px-6 py-4 border h-36 overflow-auto">

                            <div class="absolute top-4 right-8">
                                <button type="button"
                                    onclick="copy_to_clip_board(document.querySelector('#certificate').innerText)"
                                    class="bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg shadow-lg">
                                    <i class="fa-solid fa-copy mr-2"></i>
                                </button>
                            </div>

                            <pre class="text-xs text-gray-600"><code id="certificate">{{ $company->certificate }}</code></pre>

                        </div>
                    </div>
                </div>

                <x-wire-input class="mb-4" label="Seleccione su certificado digital en formato PEM" type="file"
                    accept=".pem" name="certificate" />
            </div>

            <div class="mb-4">

                <input type="hidden" name="production" value="0">

                <label class="inline-flex relative items-center cursor-pointer">
                    <input type="checkbox" value="1" name="production" class="sr-only peer"
                        @checked(old('production', $company->production))>
                    <div
                        class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
                    </div>
                    <span class="ml-3 text-sm text-gray-900">
                        Producción
                    </span>
                </label>

            </div>

            <div class="flex justify-end space-x-2">
                <x-wire-button red onclick="deleteCompany()">
                    Eliminar
                </x-wire-button>
                <x-wire-button type="submit" blue>
                    Actualizar
                </x-wire-button>
            </div>
        </x-wire-card>
    </form>

    <form action="{{ route('companies.destroy') }}" method="POST" id="deleteCompanyForm">
        @csrf
        @method('DELETE')
    </form>

    @push('js')
        <script>
            function deleteCompany() {
                Swal.fire({
                    title: "¿Estás seguro?",
                    text: "¡Se eliminará toda la información de la compañía!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Sí, eliminarlo!",
                    cancelButtonText: "Cancelar"
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('deleteCompanyForm').submit();
                    }
                });
            }
        </script>
    @endpush

</x-dashboard-layout>
