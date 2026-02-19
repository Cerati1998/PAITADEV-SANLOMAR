<div x-data="dataCreate">
    <form @submit.prevent="save">

        <x-wire-modal-card title="Gestionar Evidencias de Techo" name="manageRoofEvidence" width="3xl"
            wire:model="openModal" :hide-close="true">

            <x-validation-errors class="mb-4" />

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-2 mb-4">
                <x-wire-number :min="0" :max="100" label="Parches soldados"
                    wire:model="form.qty_welded_patch" />
                <x-wire-number :min="0" :max="100" label="Parches remachados"
                    wire:model="form.qty_riveted_patch" />
                <x-wire-number :min="0" :max="100" label="Parches en el riel superior de puerta"
                    wire:model="form.qty_door_rail_patch" />

            </div>
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-2 mb-4">
                <x-wire-number :min="0" :max="100" label="Parches en el riel frontal superior"
                    wire:model="form.qty_front_rail_patch" />
                <x-wire-number :min="0" :max="100" label="Parches cubiertos con silicona"
                    wire:model="form.qty_silicon_patch" />
                <x-wire-number :min="0" :max="100" label="Corte sellado"
                    wire:model="form.qty_sealed_cut" />
                <x-wire-number :min="0" :max="100" label="Corte soldado"
                    wire:model="form.qty_welded_cut" />
            </div>
            <div class="mb-4">
                <x-wire-toggle left-label="Delaminaciones, Abolladuras Y Reseñas leves"
                    wire:model="form.has_additional_damages" sm />
            </div>

            <hr class="border-gray-200">

            {{-- ─── Toggle galería / cámara ────────────────────────────────────── --}}
            <div class="flex items-center justify-center gap-2 sm:gap-3 p-2 sm:p-3 bg-gray-100 rounded-lg">
                <div class="flex items-center gap-1 sm:gap-2">
                    <i class="fas fa-images text-xs sm:text-base"
                        :class="captureMode === 'gallery' ? 'text-blue-500' : 'text-gray-400'"></i>
                    <span class="text-xs sm:text-sm font-medium"
                        :class="captureMode === 'gallery' ? 'text-blue-500' : 'text-gray-500'">Galería</span>
                </div>

                <button @click="toggleCaptureMode()" type="button"
                    class="relative w-12 h-7 sm:w-14 sm:h-8 rounded-full transition-colors duration-300"
                    :class="captureMode === 'camera' ? 'bg-blue-500' : 'bg-gray-300'">
                    <span
                        class="absolute top-0.5 left-0.5 sm:top-1 sm:left-1 w-6 h-6 bg-white rounded-full shadow-md transition-all duration-300"
                        :style="{
                            transform: captureMode === 'camera' ?
                                (window.innerWidth >= 640 ? 'translateX(1.5rem)' : 'translateX(1.25rem)') :
                                'translateX(0)'
                        }">
                    </span>
                </button>

                <div class="flex items-center gap-1 sm:gap-2">
                    <span class="text-xs sm:text-sm font-medium"
                        :class="captureMode === 'camera' ? 'text-blue-500' : 'text-gray-500'">Cámara</span>
                    <i class="fas fa-camera text-xs sm:text-base"
                        :class="captureMode === 'camera' ? 'text-blue-500' : 'text-gray-400'"></i>
                </div>
            </div>

            {{-- ─── Zona de upload ─────────────────────────────────────────────── --}}
            <div x-show="canUploadMore()"
                class="relative group border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-indigo-400 transition mb-3">

                <input type="file" x-on:change="uploadPhoto($event)"
                    :capture="captureMode === 'camera' ? 'environment' : null"
                    class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*">

                {{-- Spinner de carga --}}
                <div x-show="uploading"
                    class="absolute inset-0 flex items-center justify-center bg-black/50 rounded-lg z-10">
                    <div class="text-white text-center">
                        <i class="fas fa-spinner fa-spin text-3xl"></i>
                        <p class="text-sm mt-2" x-text="`${progress}%`"></p>
                    </div>
                </div>

                {{-- Badge modo --}}
                <div class="absolute top-2 right-2 px-2 py-1 rounded text-xs font-medium"
                    :class="captureMode === 'camera' ? 'bg-blue-500 text-white' : 'bg-purple-500 text-white'">
                    <i :class="captureMode === 'camera' ? 'fas fa-camera' : 'fas fa-images'"></i>
                </div>

                <div class="flex flex-col items-center justify-center text-gray-500">
                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                    <p class="text-sm font-medium">Haz clic o arrastra una imagen aquí</p>
                    <p class="text-xs text-gray-400">Formatos: JPG, PNG · Máx. 4 fotos</p>
                    <p class="text-xs text-blue-500 mt-1" x-text="`Slots disponibles: ${4 - photos.length}`"></p>
                </div>
            </div>

            {{-- Aviso cuando ya hay 4 fotos --}}
            <div x-show="!canUploadMore()"
                class="text-center text-sm text-amber-600 bg-amber-50 border border-amber-200 rounded-lg p-3">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                Se ha alcanzado el límite máximo de 4 fotos.
            </div>

            {{-- ─── Grid de fotos ──────────────────────────────────────────────── --}}
            <div x-show="photos.length" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <template x-for="photo in photos" :key="photo.slot">
                    <div class="relative group rounded-lg overflow-hidden shadow-md">
                        <img :src="photo.url" loading="lazy" decoding="async"
                            class="w-full h-40 object-cover rounded-md">

                        {{-- Overlay de acciones --}}
                        <div
                            class="absolute inset-0 bg-black bg-opacity-40 opacity-0
                            group-hover:opacity-100 transition flex items-center justify-center gap-2">

                            <a :href="photo.url" target="_blank"
                                class="inline-flex items-center justify-center bg-blue-500 hover:bg-blue-600 text-white rounded-md p-2 text-xs shadow transition">
                                <i class="fas fa-eye"></i>
                            </a>

                            <button @click="deletePhoto(photo.slot)" type="button"
                                class="inline-flex items-center justify-center bg-red-500 hover:bg-red-600 text-white rounded-md p-2 text-xs shadow transition">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>

                        {{-- Etiqueta de slot --}}
                        <div class="absolute bottom-1 left-1 bg-black/60 text-white text-[10px] px-1.5 py-0.5 rounded"
                            x-text="photo.slot.replace('_', ' ')">
                        </div>
                    </div>
                </template>
            </div>

            {{-- Sin fotos --}}
            <div x-show="!photos.length" class="text-center text-gray-400 text-sm py-4">
                <i class="fas fa-image text-2xl mb-2 block"></i>
                Aún no se han añadido evidencias fotográficas del techo.
            </div>

            <x-slot name="footer" class="flex justify-end gap-x-4">
                <x-wire-button flat label="Cancelar" x-on:click="close" />
                <x-wire-button type="submit" primary label="Guardar" spinner wire:loading.attr="disabled"
                    wire:target="save" icon="arrow-down-tray" />
            </x-slot>

        </x-wire-modal-card>
    </form>
    <x-spinner :targets="['save']" message="Guardando Evidencias..." logo="{{ asset('img/logos/favicon.svg') }}"
        logoSize="56" />
    @push('js')
        <script src="https://unpkg.com/browser-image-compression@2.0.2/dist/browser-image-compression.js"></script>
        <script>
            function dataCreate() {
                return {
                    eirId: @entangle('eirId'),
                    photos: @entangle('photos'), // [{ slot: 'photo_1', url: '...' }, ...]
                    uploading: false,
                    progress: 0,
                    captureMode: 'gallery',

                    init() {
                        if (!Array.isArray(this.photos)) {
                            this.photos = [];
                        }
                    },

                    canUploadMore() {
                        return this.photos.length < 4;
                    },

                    toggleCaptureMode() {
                        this.captureMode = this.captureMode === 'camera' ? 'gallery' : 'camera';
                    },

                    async uploadPhoto(event) {
                        const file = event.target.files[0];
                        if (!file) return;

                        if (!file.type.startsWith('image/')) {
                            Swal.fire('Error', 'Solo se permiten imágenes', 'error');
                            return;
                        }

                        if (!this.canUploadMore()) {
                            Swal.fire('Límite alcanzado', 'Máximo 4 fotos permitidas', 'warning');
                            return;
                        }

                        // Comprimir imagen
                        const options = {
                            maxSizeMB: 0.7,
                            maxWidthOrHeight: 1920,
                            initialQuality: 0.8,
                            useWebWorker: true
                        };

                        this.uploading = true;
                        this.progress = 0;

                        try {
                            const compressed = await imageCompression(file, options);

                            const formData = new FormData();
                            formData.append('photo', compressed);
                            formData.append('eir_id', this.eirId);

                            const response = await axios.post('/inspections/roof-evidence/upload-photo', formData, {
                                headers: {
                                    'Content-Type': 'multipart/form-data'
                                },
                                onUploadProgress: (e) => {
                                    this.progress = Math.round((e.loaded * 100) / e.total);
                                }
                            });

                            if (response.data.success) {
                                this.photos = [...this.photos, response.data.photo];

                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Éxito!',
                                    text: 'Foto subida correctamente',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            }
                        } catch (error) {
                            console.error('Upload error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: error.response?.data?.message || 'Error al subir la imagen'
                            });
                        } finally {
                            this.uploading = false;
                            this.progress = 0;
                            event.target.value = '';
                        }
                    },

                    async deletePhoto(slot) {
                        const result = await Swal.fire({
                            title: '¿Estás seguro?',
                            text: '¡No podrás revertir esto!',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Sí, eliminar',
                            cancelButtonText: 'Cancelar'
                        });

                        if (!result.isConfirmed) return;

                        try {
                            const ok = await @this.call('deletePhoto', slot);

                            if (ok) {
                                this.photos = this.photos.filter(p => p.slot !== slot);

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Eliminado',
                                    text: 'La foto fue eliminada correctamente.',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            }
                        } catch (error) {
                            console.error('Error al eliminar:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'No se pudo eliminar la foto'
                            });
                        }
                    },

                    async downloadAll() {
                        await @this.call('downloadAllPhotos');
                    },

                    save() {

                        Swal.fire({
                            title: "¿Estas seguro de Guardar?",
                            text: "¡No podrás revertir esto!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: '¡Sí, Continua!',
                            cancelButtonText: 'Cancelar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                @this.call('save');
                            }
                        })
                    }
                }

            }
        </script>
    @endpush
</div>
