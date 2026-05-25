<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;

use Livewire\WithPagination;

class Adminuser extends Component
{

    use WithPagination;

    public $search;

    protected $paginationTheme = "bootstrap";

    public function updatingSearch(){
        $this->resetPage();
        
    }

    public function render()
    {

        $users = User::where('name', 'LIKE', '%'. $this->search . '%')
        ->orWhere('email', 'LIKE', '%'. $this->search . '%')
        ->paginate();

        return view('livewire.adminuser', compact('users'));
    }
}
