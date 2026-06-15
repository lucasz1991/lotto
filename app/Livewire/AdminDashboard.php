<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class AdminDashboard extends Component
{
    public int $totalUsers = 0;

    public function mount(): void
    {
        $this->totalUsers = Schema::hasTable('users') ? User::count() : 0;
    }

    public function render()
    {
        return view('livewire.admin-dashboard')->layout('layouts.master');
    }
}
