<div>
    <x-wire-card padding="px-4 py-6">
        <x-wire-alert class="mb-4"
            title="Si no generas un número de serie para cada documento, no podrás generar todos los tipos de documento disponibles."
            info />

        <div class="flex items-center mb-8">
            <hr class="w-12 hidden md:block" />

            <span class="mx-4 text-xl font-semibold text-center">Documentos y series</span>

            <hr class="flex-1 hidden md:block" />
        </div>

        <form class="mb-4" wire:submit="save">

            <div class="grid grid-cols-4 gap-6">
                <div class="col-span-4 lg:col-span-1">
                    <x-wire-native-select wire:model="newDocument.document">

                        <option value="" selected disabled>Tipo de documento</option>

                        @foreach ($documents as $document)
                            <option value="{{ $document->id }}">{{ $document->description }}</option>
                        @endforeach

                    </x-wire-native-select>
                </div>

                <div class="col-span-2 lg:col-span-1">

                    <x-wire-maskable wire:model="newDocument.serie" 
                        :mask="['AXXX']"
                        class="w-full placeholder:normal-case valid:uppercase"
                        placeholder="Número de serie"
                        id="serie" />

                </div>

                <div class="col-span-2 lg:col-span-1">

                    <x-wire-input wire:model="newDocument.correlativo" type="number" placeholder="Correlativo" />

                </div>

                <div class="col-span-4 lg:col-span-1 pt-[0.5px]">
                    <x-button class="w-full flex justify-center">
                        Agregar
                    </x-button>
                </div>
            </div>

        </form>

        <div>
            @foreach ($addedDocuments as $document)
                <div class="grid grid-cols-4 gap-6 items-center py-3 border-t" wire:key="document-{{ $document['id'] }}">

                    <div class="col-span-4 lg:col-span-1">
                        {{ $document['description'] }}
                    </div>

                    <div class="col-span-4 lg:col-span-3 space-y-1">

                        @foreach ($document['branches'] as $branch)
                            <div class="grid grid-cols-3 gap-6" wire:key="pivot-{{ $branch['pivot']['id'] }}">
                                <div class="col-span-1">
                                    Serie: {{ $branch['pivot']['serie'] }}
                                </div>

                                <div class="col-span-1">
                                    Correlativo: {{ $branch['pivot']['correlativo'] }}
                                </div>

                                <div class="col-span-1">
                                    <button class="btn btn-outline-red w-full flex justify-center"
                                        onclick="deleteVoucher({{ $branch['pivot']['id'] }})">
                                        Eliminar
                                    </button>
                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>
            @endforeach
        </div>
    </x-wire-card>

    @push('js')
        <script>
            function deleteVoucher(id) {
                Swal.fire({
                    title: "¿Estás seguro?",
                    text: "¡No podrás revertir esto!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "¡Sí, bórralo!",
                    cancelButtontext: "Cancelar"
                }).then((result) => {
                    if (result.isConfirmed) {

                        @this.call('deleteVoucher', id);

                    }
                });
            }
        </script>
    @endpush

    @push('js')
        
        <script>
            document.getElementById('serie').addEventListener('input', function() {    
                this.value = this.value.toUpperCase();
            });
        </script>

    @endpush
</div>
