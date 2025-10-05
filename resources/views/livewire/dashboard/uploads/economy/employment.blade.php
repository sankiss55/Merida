@section('title','Carga Empleo')
<section class="py-2 overflow-y-auto">
    <div class="container">
<button onclick="window.open('{{ url('help/Formato de Empleo.pdf') }}', '_blank')"
            class="fixed bottom-4 right-4 z-50 bg-blue-600 hover:bg-blue-700 text-white font-bold w-10 h-10 rounded-full shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center text-lg"
            title="Consultar formato y guía de carga">
            ?
        </button>
@livewire('shared.error-modal')
        @livewire('dashboard.uploads.file',['type_id'=>$type_id,'key'=>$key,'regexp'=>$regexp])
        @livewire('dashboard.uploads.history', ['type_id' => $type_id])
    </div>
</section>
