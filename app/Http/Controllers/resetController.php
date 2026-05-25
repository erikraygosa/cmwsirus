<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;



class resetController extends Controller
{
    public function index(){

        $user = Auth::user();

        $reset = User::where('id', $user->id)
        ->get();
        

    return view('resetpassword.index', compact('reset'));
    
    }
    public function store(Request $request){

        $request->validate([
         
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        $user->password = Hash::make($request->new_password);
        $user->save();
        

    return view('resetpassword.index')->with('status', 'Contraseña cambiada con éxito');
    
    }
}

