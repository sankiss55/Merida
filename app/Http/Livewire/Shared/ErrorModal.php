<?php

namespace App\Http\Livewire\Shared;

use Livewire\Component;

class ErrorModal extends Component
{
    public $visible = false;
    public $title = '';
    public $messages = [];
    public $type = 'error'; // 'error' | 'success'

    protected $listeners = [
        // soportar ambos: emisión con nombre específico 'showImportResult' o llamada directa 'show'
        'showImportResult' => 'show',
        'show' => 'show',
    ];

    public function show($data)
    {
        $this->type = !empty($data['success']) ? 'success' : 'error';
        $this->title = $this->type === 'success' ? 'Éxito' : 'Errores de importación';

        if (!empty($data['success'])) {
            // Allow an array of success messages or a simple string
            $this->messages = is_array($data['success']) ? $data['success'] : [$data['success']];
        } else {
            if (isset($data['errors']) && is_array($data['errors'])) {
                $this->messages = $data['errors'];
            } else {
                $this->messages = [isset($data['errors']) ? $data['errors'] : (isset($data['message']) ? $data['message'] : 'Error en la importación')];
            }
        }

        $this->visible = true;
    }

    public function close()
    {
        $this->visible = false;
    }

    public function render()
    {
        return view('livewire.shared.error-modal');
    }
}
