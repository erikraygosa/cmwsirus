<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;




class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role1 = Role::create(['name' => 'Admin']);
        $role2 = Role::create(['name' => 'Supervisor']); 
        $role3 = Role::create(['name' => 'Operador']); 

      

        Permission::create(['name' => 'adminusers',
        'description' => 'Asiganr Rol'])->syncRoles([$role1]);
        Permission::create(['name' => 'accidentes',
        'description' => 'Ver Accidentes'])->syncRoles([$role1, $role2]);
        Permission::create(['name' => 'horararios',
        'description' => 'Ver Horarios'])->syncRoles([$role1, $role2]);
        Permission::create(['name' => 'horarariosope',
        'description' => 'Ver Horarios Ope'])->syncRoles([$role1, $role2, $role3]);



        User::create([
            'name'=> 'Admin',
            'email'=> 'admin@admin.com',
            'password'=> bcrypt('adminadmin')

        ])->assignRole('Admin');
    }
}
