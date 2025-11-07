<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions
        $permissions = [
            // User Management
            'user-management',
            
            // Import Permissions
            'import-orders',
            'import-inventory', 
            'import-suppliers',
            
            // Additional System Permissions
            'view-audits',
            'export-data',
            'delete-imports',
            'manage-imports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $editorRole = Role::firstOrCreate(['name' => 'editor']);
        $supplierManagerRole = Role::firstOrCreate(['name' => 'supplier-manager']);

        // Assign permissions to roles
        
        // Admin gets all permissions
        $adminRole->givePermissionTo(Permission::all());

        // Editor gets import and view permissions (no user management)
        $editorRole->givePermissionTo([
            'import-orders',
            'import-inventory', 
            'import-suppliers',
            'view-audits',
            'export-data',
            'manage-imports',
        ]);

        // Supplier Manager gets only supplier-related permissions
        $supplierManagerRole->givePermissionTo([
            'import-suppliers',
            'view-audits',
            'export-data',
        ]);

        // Create admin user if doesn't exist and assign admin role
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password123'),
            ]
        );

        // Assign admin role to admin user
        if (!$adminUser->hasRole('admin')) {
            $adminUser->assignRole('admin');
        }

        $this->command->info('Permissions and roles created successfully!');
        $this->command->info('Admin user created/updated with admin role');
        $this->command->line('');
        $this->command->info('Roles and their permissions:');
        $this->command->line('');
        $this->command->info('🔹 Admin Role: ALL PERMISSIONS');
        $this->command->info('🔹 Editor Role: import-orders, import-inventory, import-suppliers, view-audits, export-data, manage-imports');
        $this->command->info('🔹 Supplier Manager Role: import-suppliers, view-audits, export-data');
    }
}
