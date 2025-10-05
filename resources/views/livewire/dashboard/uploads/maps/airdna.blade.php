<section class="py-2 overflow-y-auto">
    @section('title','Airdna')
    <!-- Overlay de carga -->
    <div id="loading-overlay" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
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
                                <span id="loading-text">Procesando archivo</span>
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500" id="loading-description">
                                    Por favor espere mientras se procesa el archivo...
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="w-full flex flex-wrap" wire:ignore>
            <div class="w-full rounded-lg bg-white shadow-lg p-2">
                <h2 class="w-full text-start text-xl font-semibold">AirDNA</h2>
                <div class="w-full flex">
                    <div class="w-full md:w-1/3 flex flex-wrap p-4 items-center justify-center">
                        <label class="w-full flex flex-col px-6 py-4 text-center cursor-pointer rounded-lg shadow hover:shadow-lg uppercase text-white bg-blue-500 hover:text-blue-500 hover:bg-white hover:text-blue-500 border border-blue-500">
                            <i class="fas fa-cloud-upload-alt mx-auto text-xl"></i>
                            <span class="mt-2 text-base leading-normal">Upload File</span>
                            <!-- File Input -->
                            <input type="file" onchange="loadName(this)" class="hidden" name="file" id="file" accept=".xlsx,.xls,.csv">
                        </label>
                    </div>

                    <div class="w-full md:w-2/3 p-2">
                        <div class="w-full py-2">
                            <label class="inline-block w-full font-semibold">Nombre del Archivo:</label>
                            <div class="flex flex-nowrap space-x-2">
                                <div class="w-[89%]">
                                    <input type="text" name="name" id="name" placeholder="Ej. Airdna-{{ date('Y') }}" class="p-1 w-full shadow rounded border">
                                </div>
                                <div class="w-[1%] flex items-end">.</div>
                                <div class="w-[10%]">
                                    <input type="text" name="ext" id="ext" class="p-1 w-full shadow rounded border bg-gray-100" readonly>
                                </div>
                            </div>
                        </div>
                        <p class="inline-block w-full py-2">
                            <label class="inline-block w-full font-semibold">Fuente de Origen:</label>
                        </p>
                        <p class="inline-block w-full py-2">
                            <select name="source_id" id="source_id" class="rounded-lg shadow border w-full p-2">
                                <option>--SELECT--</option>
                                @foreach($sources as $source)
                                <option value="{{ $source->id }}">{{ $source->name }}</option>
                                @endforeach
                            </select>
                        </p>
                    </div>
                </div>

                <div class="w-full flex">
                    <div class="w-full md:w-1/2 flex items-center">
                        <ul class="list-inside list-disc text-red-800 font-semibold" id="messages">
                        </ul>
                    </div>
                    <div class="w-1/2 flex justify-end">
                        <button id="submit" onclick="submit(this)"
                            class="bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded-lg transition-all duration-300 ease-in-out">
                            <i class="fas fa-save"></i> <span id="text-submit">Guardar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @livewire('dashboard.uploads.history', ['type_id' => $type_id])
    </div>
</section>

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

<script>
    let body = {};
    let formData = {};
    let name, file, source_id;
    let lists = '';
    let messages = [];

    function submit(element) {
        lists = '';
        messages = [];
        if (document.getElementById('messages'))
            document.getElementById('messages').innerHTML = '';

        // Validaciones
        if (!document.getElementById('file').files.length) {
            messages.push('Por favor seleccione un archivo');
            showMessages();
            return;
        }

        if (!document.getElementById('name').value) {
            messages.push('Por favor ingrese un nombre de archivo');
            showMessages();
            return;
        }

        if (!document.getElementById('ext').value) {
            messages.push('El archivo debe tener una extensión válida');
            showMessages();
            return;
        }

        if (!document.getElementById('source_id').value || document.getElementById('source_id').value === '--SELECT--') {
            messages.push('Por favor seleccione una fuente de origen');
            showMessages();
            return;
        }

        element.disabled = true;
        if (document.getElementById("text-submit"))
            document.getElementById("text-submit").innerHTML = 'Guardando...';

        // Mostrar overlay de carga
        document.getElementById('loading-overlay').classList.remove('hidden');
        document.getElementById('loading-text').textContent = 'Subiendo archivo';
        document.getElementById('loading-description').textContent = 'Por favor espere mientras se sube el archivo...';

        getForm();
        sendAirdna();
    }

    function loadName(file) {
        let fullName = file.files[0].name;
        let lastDot = fullName.lastIndexOf('.');
        let fileName = fullName.substring(0, lastDot);
        let fileExt = fullName.substring(lastDot + 1);

        if (document.getElementById('name')) {
            document.getElementById('name').value = fileName;
        }
        if (document.getElementById('ext')) {
            document.getElementById('ext').value = fileExt;
        }
    }

    function getForm() {
        formData = new FormData();

        if (document.getElementById('name') && document.getElementById('ext')) {
            const fileName = document.getElementById('name').value + '.' + document.getElementById('ext').value;
            formData.append("name", fileName);
        }

        if (document.getElementById('source_id'))
            formData.append("source_id", document.getElementById('source_id').value);

        if (document.getElementById('file'))
            formData.append("file", document.getElementById('file').files[0]);
    }

    async function sendAirdna() {
        let codestatus = 200;
        try {
            // Verificar que tenemos la extensión correcta
            const fileExt = document.getElementById('ext').value.toLowerCase();
            if (!['xlsx', 'xls', 'csv'].includes(fileExt)) {
                throw new Error('El tipo de archivo debe ser xlsx, xls o csv');
            }

            const URL = host + "/api/v1/upload/maps/airdna?api_key=" + Api_key;
            const response = await fetch(URL, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            codestatus = response.status;
            if (response.status === 200) {
                messages.push(result.message);
                body = result.body;
                messages.push('Successful Filled');
                messages.push(document.getElementById('name').value);
            } else {
                for (let x in result) {
                    Object.values(result[x]).forEach((value, index) => {
                        messages.push(value);
                    });
                }
            }
        } catch (error) {
            console.log(error);
            messages.push('Successful Filled');
            messages.push(document.getElementById('name').value);
        }

        showMessages();

        if (codestatus !== 400) {
            setTimeout(function() {
                document.location.reload();
            }, 5000)
        }
    }

    function showMessages() {
        messages = Array.from(new Set(messages));

        messages.forEach((value, index) => {
            lists += '<li>' + value + '</li>';
        });
        if (document.getElementById('messages')) {
            document.getElementById('messages').innerHTML = lists;
        }

        if (document.getElementById("text-submit"))
            document.getElementById("text-submit").innerHTML = 'Guardar';

        if (document.getElementById("submit"))
            document.getElementById("submit").disabled = false;

        // Ocultar overlay de carga
        document.getElementById('loading-overlay').classList.add('hidden');
    }
</script>