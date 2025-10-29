<div x-data="data">

    <form @submit.prevent="save">

        <x-wire-modal-card title="Cliente" name="clientCreationModal" width="3xl">

            {{-- Si hay errores, se mostrarán aquí --}}
            <div x-show="errors.length" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                <strong class="font-bold">¡Ups! Algo salió mal.</strong>
                <ul class="mt-3 list-disc list-inside text-sm text-red-600">
                    <template x-for="error in errors" :key="error">
                        <li x-text="error"></li>
                    </template>
                </ul>
            </div>

            <div class="grid grid-cols-2 gap-4">

                <div class="col-span-1">
                    <x-label class="mb-1">
                        Tipo de Documento
                    </x-label>
                    <x-select required x-model="client.tipoDoc" class="w-full">
                        @foreach ($identities as $identity)
                            <option value="{{ $identity->id }}">{{ $identity->description }}</option>
                        @endforeach
                    </x-select>
                </div>

                <div class="col-span-1">
                    <x-label class="mb-1">
                        Número de Documento
                    </x-label>
                    <x-input x-model="client.numDoc" placeholder="Ingrese el número de documento" class="w-full" x-bind:disabled="client.tipoDoc == '-'" />
                </div>

                <div class="col-span-2">
                    <x-wire-input required label="Razón Social" x-model="client.rznSocial" placeholder="Ingrese la razón social" />
                </div>

                <div class="col-span-2">
                    <x-wire-input label="Dirección" x-model="client.direccion" placeholder="Ingrese la dirección" />
                </div>
                
                <x-wire-input label="Correo Electrónico" x-model="client.email" placeholder="Ingrese el correo electrónico (Opcional)" />

                <x-wire-input label="Teléfono" x-model="client.telephone" placeholder="Ingrese el teléfono (Opcional)" />

            </div>

            <x-slot name="footer" class="flex justify-end gap-x-4">
                <x-wire-button flat label="Cancelar" x-on:click="close" />

                <x-wire-button type="submit" primary label="Guardar" spinner icon="arrow-down-tray" />
            </x-slot>

        </x-wire-modal-card>
    </form>

</div>

@push('js')
    <script>
        function data() {
            return {
                errors: [],

                client: {
                    tipoDoc: '-',
                    numDoc: '',
                    rznSocial: '',
                    direccion: '',
                    email: '',
                    telephone: ''
                },

                save() {

                    axios.post('/clients', this.client).then(response => {

                        console.log(response.data.id);

                        $closeModal('clientCreationModal');

                        Livewire.dispatch('clientAdded', {
                            clientId: response.data.id,
                        });

                        this.errors = [];

                        this.client = {
                            tipoDoc: '-',
                            numDoc: '',
                            rznSocial: '',
                            direccion: '',
                            email: '',
                            telephone: ''
                        };

                    }).catch(error => {
                        if (error.response.status === 422) {
                            this.errors = Object.values(error.response.data.errors).flat();
                        }

                        console.log(error.response.data);
                    });
                },

                init(){
                    this.$watch('client.tipoDoc', value => {
                        
                        if(value == '-') {
                            this.client.numDoc = '';
                        }
                    });
                }
            }
        }
    </script>
@endpush