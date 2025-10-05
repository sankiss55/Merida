<?php

namespace App\Http\Livewire\Dashboard\Tourism;

use Livewire\Component;

class TrafficMonthlyArrivals extends Component
{
    public $months;
    public $arrivals;

    public function mount()
    {
        // Datos de ejemplo
        $this->months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'];
        $this->arrivals = [1200, 1500, 1100, 1800, 1600, 2000];
    }

    public function render()
    {
        return view('livewire.dashboard.tourism.traffic-monthly-arrivals')->extends('layouts.admin')
            ->section('content');
    }
}
