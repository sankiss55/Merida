<?php

namespace App\Http\Livewire\Dashboard;

use App\Models\Load;
use App\Models\Source;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use function view;

class Sources extends Component
{
    public $sources;

    public function mount()
    {
        $this->sources = Source::with(['loads' => function ($sources) {
            $sources->orderBy('id', 'desc');
        }])
            ->orderBy('name', 'ASC')->get();
    }

    public function download($id)
    {
        $load = Load::findOrFail($id);

        if (!Storage::exists("public/files/{$load->file}")) {
            session()->flash('error', 'El archivo no se encontró.');
            return;
        }

        return Storage::download("public/files/{$load->file}");
    }

    public function render()
    {
        return view('livewire.dashboard.sources')
            ->extends('layouts.admin')
            ->section('content');
    }
}
