<div x-data="data">

    <form @submit.prevent="save">

        <x-wire-modal-card title="Usuario" name="simpleModal" width="xl">

            {{-- Si hay errores, se mostrarán aquí --}}
            <div x-show="errors.length"
                class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                <strong class="font-bold">¡Ups! Algo salió mal.</strong>
                <ul class="mt-3 list-disc list-inside text-sm text-red-600">
                    <template x-for="error in errors" :key="error">
                        <li x-text="error"></li>
                    </template>
                </ul>
            </div>

            <div>
                <div class="flex space-x-4 mb-4">
                    <div class="flex-1">
                        <x-label class="mb-1">
                            Tipo de Documento
                        </x-label>
                        <x-select required x-model="user.tipoDoc" class="w-full">
                            <option value="" hidden>Seleccione...</option>
                            @foreach ($identities as $identity)
                                <option value="{{ $identity->id }}">{{ $identity->description }}</option>
                            @endforeach
                        </x-select>
                    </div>

                    <div class="flex-1">
                        <x-label class="mb-1">
                            Nº de Documento
                        </x-label>
                        <x-input x-model="user.numDoc" placeholder="Ingrese el número de documento" class="w-full"
                            x-bind:disabled="user.tipoDoc == '-'" />
                    </div>

                    <div class="shrink-0 mt-6.5">
                        <x-wire-mini-button type="button" x-on:click="searchDocument" spinner="searchDocument"
                            icon="magnifying-glass" x-bind:disabled="!(user.tipoDoc == '1')" />
                    </div>
                </div>
                <div class="mt-4">
                    <x-label value="{{ __('Name') }}" />
                    <x-input class="block mt-1 w-full" type="text" placeholder="Nombre del usuario"
                        x-model="user.name" required />
                </div>

                <div class="mt-4">
                    <x-label value="{{ __('Email') }}" />
                    <x-input class="block mt-1 w-full" type="email" placeholder="Correo electrónico"
                        x-model="user.email" required autocomplete="username" />
                </div>

                <div class="mt-4">
                    <x-label value="{{ __('Password') }}" />
                    <x-input class="block mt-1 w-full" type="password" placeholder="Contraseña" x-model="user.password"
                        required autocomplete="new-password" />
                </div>

                <div class="mt-4">
                    <x-label value="{{ __('Confirm Password') }}" />
                    <x-input class="block mt-1 w-full" type="password" placeholder="Confirmar contraseña"
                        x-model="user.password_confirmation" required autocomplete="new-password" />
                </div>

                <div class="mt-4">
                    <x-label value="Sucursal" />

                    <x-select class="block mt-1 w-full" x-model="user.branch_id">
                        <option value="">Selecciona una sucursal</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </x-select>

                </div>

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

                user: {
                    name: "",
                    email: "",
                    tipoDoc: "",
                    numDoc: "",
                    password: "",
                    password_confirmation: "",
                    branch_id: ""
                },

                async searchDocument() {
                    if (!this.user.tipoDoc || !this.user.numDoc) {
                        this.errors = ['Debe seleccionar tipo y número de documento'];
                        return;
                    }
                    if (this.user.tipoDoc !== "1") {
                        this.errors = ['Solo debe escoger tipo de Documento DNI para realizar la consulta'];
                        return;
                    }

                    this.isLoading = true;
                    this.errors = []; // Limpiar errores anteriores

                    try {
                        const response = await axios.post('/searchDocument', {
                            tipoDoc: this.user.tipoDoc,
                            numDoc: this.user.numDoc
                        });

                        if (response.status === 200) {
                            this.user.name = response.data.nombres;
                        }
                    } catch (error) {
                        if (error.response) {
                            // Manejar errores 422 de validación
                            if (error.response.status === 422) {
                                // Extraer todos los mensajes de error
                                const errorMessages = Object.values(error.response.data.errors).flat();
                                this.errors = errorMessages;
                            }
                            // Manejar otros errores (400, 500, etc.)
                            else if (error.response.data.message) {
                                this.errors = [error.response.data.message];
                            } else {
                                this.errors = ['Error en la consulta del documento'];
                            }
                        } else {
                            this.errors = ['Error de conexión con el servidor'];
                        }
                    } finally {
                        this.isLoading = false;
                    }
                },
                save() {

                    axios.post('/users', this.user).then(response => {

                        $closeModal('simpleModal');

                        Livewire.dispatch('UserAdded');

                        this.errors = [];

                        this.user = {
                            name: "",
                            email: "",
                            tipoDoc: "",
                            numDoc: "",
                            password: "",
                            password_confirmation: "",
                            branch_id: ""
                        };

                    }).catch(error => {

                        if (error.response.status === 422) {
                            this.errors = Object.values(error.response.data.errors).flat();
                            console.log(error.response.data);
                        }

                    });
                }
            }
        }
    </script>
@endpush
