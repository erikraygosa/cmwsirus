<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->orderBy('name')->get();

        // withCount('users') falla en Spatie v6 cuando guard_name no está en atributos.
        // Usamos User::role() que es el scope oficial de Spatie.
        $roles->each(fn($role) => $role->users_count = User::role($role->name)->count());

        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::orderBy('name')->get();
        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
        ]);

        $role = Role::create(['name' => $request->name]);
        $role->syncPermissions($request->permissions ?? []);

        return redirect()->route('roles.index')
            ->with('success', "Rol \"{$role->name}\" creado correctamente.");
    }

    public function edit($id)
    {
        $role        = Role::with('permissions')->findOrFail($id);
        $permissions = Permission::orderBy('name')->get();
        return view('roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'name'        => ['required', 'string', 'max:100', "unique:roles,name,{$id}"],
            'permissions' => ['nullable', 'array'],
        ]);

        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions ?? []);

        return redirect()->route('roles.index')
            ->with('success', "Rol \"{$role->name}\" actualizado.");
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        $usersCount = User::role($role->name)->count();
        if ($usersCount > 0) {
            return back()->with('error',
                "No se puede eliminar: el rol tiene {$usersCount} usuario(s) asignado(s).");
        }

        $role->delete();

        return redirect()->route('roles.index')
            ->with('success', 'Rol eliminado.');
    }
}
