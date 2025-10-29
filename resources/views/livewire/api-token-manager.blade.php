<div>
    <!-- Generate API Token -->
    <x-form-section submit="createApiToken">
        <x-slot name="title">
            {{ __('Create API Token') }}
        </x-slot>

        <x-slot name="description">
            {{ __('API tokens allow third-party services to authenticate with our application on your behalf.') }}
        </x-slot>

        <x-slot name="form">
            <!-- Token Name -->
            <div class="col-span-6 sm:col-span-4">
                <x-label for="name" value="{{ __('Token Name') }}" />
                <x-input id="name" type="text" class="mt-1 block w-full" wire:model="createApiTokenForm.name"
                    autofocus />
                <x-input-error for="name" class="mt-2" />
            </div>

            <!-- Token Permissions -->
            <div class="col-span-6">
                <x-label for="permissions" value="{{ __('Permissions') }}" />

                <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-4">


                    @foreach ($permissions as $permission)
                        
                        <label class="flex items-center">
                            <x-checkbox wire:model="createApiTokenForm.permissions" :value="$permission" />
                            <span class="ms-2 text-sm text-gray-600">
                                {{$permission}}
                            </span>
                        </label>

                    @endforeach

                </div>
            </div>
            
        </x-slot>

        <x-slot name="actions">
            <x-action-message class="me-3" on="created">
                {{ __('Created.') }}
            </x-action-message>

            <x-button>
                {{ __('Create') }}
            </x-button>
        </x-slot>
    </x-form-section>

    @if ($this->company->tokens->isNotEmpty())
        <x-section-border />

        <!-- Manage API Tokens -->
        <div class="mt-10 sm:mt-0">
            <x-action-section>
                <x-slot name="title">
                    {{ __('Manage API Tokens') }}
                </x-slot>

                <x-slot name="description">
                    {{ __('You may delete any of your existing tokens if they are no longer needed.') }}
                </x-slot>

                <!-- API Token List -->
                <x-slot name="content">
                    <div class="space-y-6">
                        @foreach ($this->company->tokens->sortBy('name') as $token)
                            <div class="flex items-center justify-between">
                                <div class="break-all">
                                    {{ $token->name }}
                                </div>

                                <div class="flex items-center ms-2">
                                    @if ($token->last_used_at)
                                        <div class="text-sm text-gray-400">
                                            {{ __('Last used') }} {{ $token->last_used_at->diffForHumans() }}
                                        </div>
                                    @endif

                                    <button class="cursor-pointer ms-6 text-sm text-gray-400 underline"
                                        wire:click="manageApiTokenPermissions({{ $token->id }})">
                                        {{ __('Permissions') }}
                                    </button>

                                    <button class="cursor-pointer ms-6 text-sm text-red-500"
                                        onclick="confirmApiTokenDeletion({{ $token->id }})">
                                        {{ __('Delete') }}
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-slot>
            </x-action-section>
        </div>
    @endif

    <!-- Token Value Modal -->
    <x-dialog-modal wire:model.live="displayingToken">
        <x-slot name="title">
            {{ __('API Token') }}
        </x-slot>

        <x-slot name="content">
            <div>
                {{ __('Please copy your new API token. For your security, it won\'t be shown again.') }}
            </div>

            <x-input x-ref="plaintextToken" type="text" readonly :value="$plainTextToken"
                class="mt-4 bg-gray-100 px-4 py-2 rounded font-mono text-sm text-gray-500 w-full break-all" autofocus
                autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                @showing-token-modal.window="setTimeout(() => $refs.plaintextToken.select(), 250)" />
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('displayingToken', false)" wire:loading.attr="disabled">
                {{ __('Close') }}
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>

    <!-- API Token Permissions Modal -->
    <x-dialog-modal wire:model.live="managingApiTokenPermissions">
        <x-slot name="title">
            {{ __('API Token Permissions') }}
        </x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($permissions as $permission)
                    <label class="flex items-center">
                        <x-checkbox wire:model="updateApiTokenForm.permissions" :value="$permission" />
                        <span class="ms-2 text-sm text-gray-600">
                            {{ $permission }}
                        </span>
                    </label>
                @endforeach
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('managingApiTokenPermissions', false)" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-button class="ms-3" wire:click="updateApiToken" wire:loading.attr="disabled">
                {{ __('Save') }}
            </x-button>
        </x-slot>
    </x-dialog-modal>

    @push('js')
        <script>
            function confirmApiTokenDeletion(tokenId) {
                Swal.fire({
                    title: "Borrar token API",
                    text: "¿Está seguro que desea eliminar este token API?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Sí, eliminar",
                    cancelButtonText: "Cancelar"
                }).then((result) => {
                    if (result.isConfirmed) {

                        Swal.fire({
                            title: "¡Token eliminado!",
                            text: "Se ha eliminado el token API.",
                            icon: "success"
                        });

                        @this.deleteApiToken(tokenId);
                    }
                });
            }
        </script>
    @endpush
</div>
