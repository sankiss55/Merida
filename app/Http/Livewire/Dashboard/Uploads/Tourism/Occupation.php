<?php

namespace App\Http\Livewire\Dashboard\Uploads\Tourism;

use App\Imports\OccupationImport;
use App\Models\Type;
use App\Traits\UploadTrait;
use App\Traits\WithSorting;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Occupation extends Component
{
    use  WithSorting, WithPagination;

    public $key;
    public $type_id;
    public $regexp;

    use UploadTrait;

    protected $listeners = ['importFile'];

    public function  mount()
    {
        $this->key='Ocupación Hotelera';
        $this->regexp='regex:/OCUPACIÓN|HOTELERA|ocupación|hotelera|Ocupación|Hotelera/';
        $this->type_id=Type::where('name','REGEXP',"{$this->key}")->firstOrFail()->id;
    }

    public function importFile($Load,$path)
    {
          try {
            $importer =new OccupationImport($Load);
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
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $this->emitTo('dashboard.uploads.file', 'importResult', [
                'success' => false,
                'errors' => ["Error al importar el archivo"]
            ]);
        }

    }

    public function render()
    {
        return view('livewire.dashboard.uploads.tourism.occupation')
        ->extends('layouts.admin')
        ->section('content');
    }
}
