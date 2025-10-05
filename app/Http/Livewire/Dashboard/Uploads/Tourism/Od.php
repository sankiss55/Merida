<?php

namespace App\Http\Livewire\Dashboard\Uploads\Tourism;

use App\Imports\OdImport;
use App\Models\Type;
use App\Traits\UploadTrait;
use App\Traits\WithSorting;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Od extends Component
{
    use  WithSorting, WithPagination;

    public $key;
    public $type_id;
    public $regexp;

    use UploadTrait;

    protected $listeners = ['importFile'];

    public function  mount()
    {
        $this->key='Origen Destino';
        $this->regexp='regex:/origen|destino|Origen|Destino|ORIGEN|DESTINO/';
        $this->type_id=Type::where('name','REGEXP',"{$this->key}")->firstOrFail()->id;
    }

    public function importFile($Load,$path)
    {
        try {
            $importer = new OdImport($Load);
            $result = Excel::import($importer, $path);
            if (!empty($importer->errors)) {
                $this->emitTo('dashboard.uploads.file', 'importResult', [
                    'success' => false,
                    'errors' => $importer->errors
                ]);
                return;
            }
            $this->emit('refreshHistory');
            $this->emitTo('dashboard.uploads.file', 'importResult', [
                'success' => true
            ]);
        }catch (\Exception $e){
            Log::error($e->getMessage());
        }

    }

    public function render()
    {
        return view('livewire.dashboard.uploads.tourism.od')
            ->extends('layouts.admin')
            ->section('content');
    }
}
