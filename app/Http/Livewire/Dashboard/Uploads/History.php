<?php

namespace App\Http\Livewire\Dashboard\Uploads;

use App\Models\Load;
use App\Models\Data;
use App\Models\Type;
use App\Traits\WithSorting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class History extends Component
{
    use WithSorting, WithPagination;

    public $type_id = 0;

    public $search = '';
    public $perPage = '10';

    public $columns = [
        'id',
        'name'
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => '10']
    ];

    protected $listeners = ['refreshHistory' => '$refresh'];

    public function mount($type_id)
    {
        $this->type_id = $type_id;
    }

    public function download($id)
    {
        $load = Load::findOrFail($id);

        if (!Storage::exists("public/files/{$load->file}")) {
            session()->flash('error', 'El archivo no se encontró.');
            return;
        }

        try {
            return Storage::download("public/files/{$load->file}");
        } catch (\Exception $e) {
            Log::error('Error al descargar archivo:', ['error' => $e->getMessage()]);
            session()->flash('error', 'Error al descargar el archivo.');
        }
    }

   public function destroy($id)
{
    try {
        Data::where('load_id', $id)->delete();

        // Eliminar el Load
        Load::findOrFail($id)->delete();

        Log::info("Load {$id} y datos asociados eliminados correctamente.");
    } catch (\Exception $e) {
        Log::error($e->getMessage());
    }
}



    public function render()
    {
        $loads = Load::where('type_id', '=', $this->type_id)->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.dashboard.uploads.history', ['loads' => $loads])
            ->extends('layouts.admin')
            ->section('content');
    }
}
