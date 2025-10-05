<div class="py-2 relative">
    <!-- Overlay de carga -->
    <div wire:loading wire:target="upload, file" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="custom-spin h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                <span wire:loading wire:target="upload">Procesando archivo</span>
                                <span wire:loading wire:target="file">Subiendo archivo</span>
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    <span wire:loading wire:target="upload">Por favor espere mientras se guarda el archivo...</span>
                                    <span wire:loading wire:target="file">Por favor espere mientras se sube el archivo...</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="w-full flex flex-wrap bg-white rounded-lg shadow-lg space-x-2 p-2">
        <div class="w-full flex flex-wrap md:flex-nowrap space-y-2 md:space-x-4">
            <div class="w-full md:w-1/3 flex flex-wrap  items-center justify-center ">
                <label class="w-full flex flex-col px-6 py-4 text-center cursor-pointer  rounded-lg shadow hover:shadow-lg uppercase text-white bg-blue-500 hover:text-blue-500 hover:bg-white  hover:text-blue-500 border border-blue-500">
                    <i class="fas fa-cloud-upload-alt mx-auto text-xl "></i>
                    <span class="mt-2 text-base leading-normal">Upload File</span>
                    <!-- File Input -->
                    <input type="file" class="hidden" wire:model="file">
                    <!-- Progress Bar -->
                </label>
                @error('file')
                <div class="w-full text-red-500 font-semibold inline-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="w-full md:w-2/3 ">
                <div class="w-full py-2">
                    <label for="" class="inline-block w-full font-semibold"> Nombre del Archivo:</label>
                    <div class="flex flex-nowrap space-x-2">
                        <div class="w-[89%]">
                            <input type="text" wire:model="name" placeholder="Ej. {{ $key }}-{{ date('Y') }}-V1" class="p-1 w-full shadow rounded border">
                        </div>
                        <div class="w-[1%] flex items-end">.</div>
                        <div class="w-[10%]">
                            <input type="text" class="p-1 w-full shadow rounded border bg-gray-100" wire:model="ext" disabled>
                        </div>
                    </div>
                    @error('name')
                    <div class="text-red-500 font-semibold">{{ $message }}</div>
                    @enderror
                </div>
                <div class="w-full py-2">
                    <label for="" class="inline-block w-full font-semibold">Fuente de Origen:</label>
                    <select wire:model="source_id" class="rounded-lg shadow border w-full p-2">
                        <option>--SELECT--</option>
                        @foreach($sources as $source)
                        <option value="{{ $source->id }}">{{ $source->name }}</option>
                        @endforeach
                    </select>
                    @error('source_id')
                    <div class="text-red-500 font-semibold">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
        <div class="w-full flex">
            <div class="w-full md:w-1/2 flex items-center">
                {{-- Los mensajes de resultado ahora se muestran en un modal Livewire --}}
            </div>
            <div class="w-full md:w-1/2 flex justify-end items-center">
                <button wire:click="upload" wire:loading.attr="disabled"
                    class="bg-green-600 hover:bg-green-500 text-white py-2 px-6 rounded-lg shadow hover:shadow-lg transition duration-300 ease-in-out">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .custom-spin {
        animation: spin 1s linear infinite;
    }
</style>
