<?php

namespace App\Http\Livewire\Dashboard\Tourism;

use Livewire\Component;

class ArrivalOfCruise extends Component
{
    public function render()
    {
        return view('livewire.dashboard.tourism.arrival-of-cruise')
            ->extends('layouts.admin')
            ->section('content');
    }
}
