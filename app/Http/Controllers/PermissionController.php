<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::with('roles')
            ->orderBy('name')
            ->get();

        return view('permisos.index', compact('permissions'));
    }

    public function create()
    {
        return view('permisos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:permissions,name',
                              'regex:/^[a-z0-9_]+$/'],
            'description' => ['nullable', 'string', 'max:200'],
        ]);

        Permission::create([
            'name'        => $request->name,
            'description' => $request->description ?? '',
        ]);

        return redirect()->route('permisos.index')
            ->with('success', "Permiso \"{$request->name}\" creado.");
    }

    public function destroy($id)
    {
        $permission = Permission::withCount('roles')->findOrFail($id);

        if ($permission->roles_count > 0) {
            return back()->with('error',
                "No se puede eliminar: el permiso está asignado a {$permission->roles_count} rol(es).");
        }

        $permission->delete();

        return redirect()->route('permisos.index')
            ->with('success', 'Permiso eliminado.');
    }
}
