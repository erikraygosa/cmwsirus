<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $busqueda = $request->input('q');

        $users = User::with('roles')
            ->when($busqueda, fn($q) => $q->where('name', 'like', "%{$busqueda}%")
                ->orWhere('email', 'like', "%{$busqueda}%"))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('users.index', compact('users', 'busqueda'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'roles'    => ['nullable', 'array'],
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->roles()->sync($request->roles ?? []);

        return redirect()->route('users.index')
            ->with('success', "Usuario {$user->name} creado correctamente.");
    }

    public function edit($id)
    {
        $user  = User::with('roles')->findOrFail($id);
        $roles = Role::orderBy('name')->get();
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', "unique:users,email,{$id}"],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'roles'    => ['nullable', 'array'],
        ]);

        $data = ['name' => $request->name, 'email' => $request->email];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->roles()->sync($request->roles ?? []);

        return redirect()->route('users.index')
            ->with('success', "Usuario {$user->name} actualizado.");
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Usuario eliminado.');
    }
}
