<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'user-management',
            'user-create',
            'user-edit', 
            'user-delete',
            'user-view',
            'permission-create',
            'permission-edit',
            'permission-delete',
            'permission-view',
            'permission-assign',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create admin role and assign all permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Assign admin role to admin user
        $adminUser = User::where('name', 'admin')->first();
        if ($adminUser) {
            $adminUser->assignRole('admin');
        }
    }
}
