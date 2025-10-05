<?php

namespace App\Http\Livewire\Dashboard\Uploads\Maps;

use App\Models\Type;
use App\Traits\UploadTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Airdna extends Component
{
    use WithFileUploads, WithPagination, UploadTrait; 

    public $file;
    public $name;
    public $source_id;
    public $type_id;
    public $key;
    public $regexp;
    public $path;
    public $Load;

    public $ext;
    public function mount()
    {
        $this->key = 'airdna';
        $this->type_id = Type::where('name', 'REGEXP', "{$this->key}")->firstOrFail()->id;
        $this->regexp = 'regex:/^[a-zA-Z0-9_\- ]+$/';
    }

    public function updatedFile()
    
    {
    
        if ($this->file) {
            $this->ext = $this->file->getClientOriginalExtension();
            $this->name = pathinfo($this->file->getClientOriginalName(), PATHINFO_FILENAME);
            Log::info('Archivo seleccionado:', [
                'nombre' => $this->name,
                'extension' => $this->ext
            ]);
        }
    }

    public function upload()
    {
    
        try {
            $this->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv',
                'name' => [
                    'required',
                    $this->regexp,
                    Rule::unique('loads', 'name')->where('type_id', $this->type_id)
                ],
                'source_id' => 'required'
            ]);

            $filename = $this->name . '.' . $this->ext;
            $this->path = $this->file->storeAs('uploads', $filename, 'public');

            $this->uploadFile();
            $this->emitUp('importFile', $this->Load, $this->path);

            session()->flash('success', 'Archivo subido correctamente');
            $this->reset(['file', 'name', 'source_id']);

            return redirect()->route('dashboard.uploads.maps.airdna');
        } catch (\Exception $e) {
        
            Log::error('Error en la carga:', ['error' => $e->getMessage()]);
            session()->flash('error', $e->getMessage());
        }
        
    }

    public function errorPulled($message)
    {
        session()->flash('error', $message);
    }

    public function render()
    {
        return view('livewire.dashboard.uploads.maps.airdna')
            ->extends('layouts.admin')
            ->section('content');
    }
}
