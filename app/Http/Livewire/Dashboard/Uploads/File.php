<?php

namespace App\Http\Livewire\Dashboard\Uploads;

use App\Traits\UploadTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;


class File extends Component
{
    protected $listeners = ['importResult'];
    use WithFileUploads;

    public $tag;
    public $file;
    public $name;
    public $type_id;
    public $key;
    public $source_id;
    public $regexp;
    public $Load;
    public $path;
    public $ext;

    use UploadTrait;

    public function mount($type_id, $key, $regexp)
    {
        $this->type_id = $type_id;
        $this->key = $key;
        $this->regexp = $regexp;
    }

    public function updatedFile()
    {
        $this->ext = pathinfo($this->file->getClientOriginalName(), PATHINFO_EXTENSION);
        $this->name = pathinfo($this->file->getClientOriginalName(), PATHINFO_FILENAME);
    }

    public function upload()
    {

        $this->validate([
            'file' => 'required|file|mimes:xls,xlsx,csv|max:512000',
            'name' => [
                'required',
                "{$this->regexp}",
                Rule::unique('loads', 'name')->where('type_id', $this->type_id)
            ],
            'source_id' => 'required'
        ]);

        $this->uploadFile();

        try {
            $this->emitUp('importFile', $this->Load, $this->path);
        } catch (\Exception $e) {
            // Enviar el error al modal en lugar de usar session flash
            $payload = ['errors' => ['Error inesperado en la importación.', $e->getMessage()]];
            $this->emitTo('shared.error-modal', 'show', $payload);
        }
        $this->reset(['file', 'name', 'source_id']);
    }

    /**
     * Recibe el resultado de la importación desde Arrivals
     */
    public function importResult($data)
    {try{

        if (!empty($data['success'])) {
            $this->successPulled('success');
        } else {

            $errors = isset($data['errors']) && is_array($data['errors']) ? $data['errors'] : [isset($data['errors']) ? $data['errors'] : 'Error en la importación'];
            $payload = ['errors' => $errors];
        $this->emitTo('shared.error-modal', 'show', $payload);
        
            $this->deleteFile();
        }
    }catch(\Exception $ex){
        Log::error("Error al procesar el resultado de la importación: " . $ex->getMessage());

    }
    }


    public function render()
    {
        return view('livewire.dashboard.uploads.file');
    }
}
