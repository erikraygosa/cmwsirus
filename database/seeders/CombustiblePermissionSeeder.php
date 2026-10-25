<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class CombustiblePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        // La administración existente acepta minúsculas y guiones bajos (no puntos).
        foreach ([
            'ver' => 'Consultar cargas de combustible', 'crear' => 'Capturar combustible',
            'editar' => 'Editar cargas de combustible', 'cancelar' => 'Cancelar cargas de combustible',
            'exportar' => 'Exportar combustible', 'precios' => 'Administrar precios de diésel',
            'auditoria' => 'Consultar auditoría de combustible',
        ] as $action => $description) {
            Permission::firstOrCreate(['name' => 'combustible_'.$action, 'guard_name' => 'web'], ['description' => $description]);
        }
        // Asignación explícita desde Roles/Usuarios: no conceder acceso automáticamente.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
