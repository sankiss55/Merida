<div>
    @if($visible)
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="fixed inset-0 bg-black opacity-50" wire:click="close"></div>
        <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full mx-4 z-10 overflow-hidden">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-semibold">{{ $title }}</h3>
                <button wire:click="close" class="text-gray-600 hover:text-gray-900">&times;</button>
            </div>
            <div class="p-4">
                @if($type === 'success')
                <div class="p-3 bg-green-50 text-green-800 rounded">
                    @foreach($messages as $m)
                    <div class="py-1">{!! $m !!}</div>
                    @endforeach
                </div>
                @else
                <div>
                    <details class="border rounded-md bg-red-50">
                        <summary class="px-4 py-3 cursor-pointer font-semibold text-red-800">Estos son los errores que tienes</summary>
                        <div class="px-4 py-3">
                            <div class="max-h-64 overflow-y-auto">
                                <ul class="list-disc list-inside space-y-2 text-red-800">
                                    @foreach($messages as $m)
                                    <li>{!! $m !!}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </details>

                    <div class="mt-4 text-sm text-gray-600 bg-gray-50 p-3 rounded">
                        <strong>Nota:</strong> te recomendamos revisar los documentos antes de hacer la carga para que su creación sea de la mejor manera.
                    </div>
                </div>
                @endif
            </div>
            <div class="p-4 border-t flex justify-end">
                <button wire:click="close" class="bg-blue-600 text-white px-4 py-2 rounded">Cerrar</button>
            </div>
        </div>
    </div>
    @endif
</div>