<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ExpedientesPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Crear permiso si no existe
        $permission = Permission::firstOrCreate(
            ['name' => 'expedientes'],
            ['description' => 'Acceso al módulo de Expedientes Digitales']
        );

        // Asignar a Admin y Supervisor
        foreach (['Admin', 'Supervisor'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role && !$role->hasPermissionTo('expedientes')) {
                $role->givePermissionTo($permission);
            }
        }

        $this->command->info('Permiso "expedientes" creado y asignado a Admin y Supervisor.');
    }
}
