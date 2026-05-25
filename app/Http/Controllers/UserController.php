<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index');
    }
    public function edit($id)
    {
        $roles = Role::all();

        $user = User::find($id);

        return view('users.edit', compact('user', 'roles'));

      

        
    }

    

    public function update(Request $request, string $id)
    {
        $user = User::find($id);

        $user->roles()->sync($request->roles);

        return redirect()->route('users.edit', $user)->with('info' ,'Se asigno  los roles correctamente');


    }
}
