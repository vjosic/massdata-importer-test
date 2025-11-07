<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create supplier role if it doesn't exist
        $supplierRole = Role::firstOrCreate(['name' => 'supplier-manager']);

        // Ensure import-suppliers permission exists
        $importSuppliersPermission = Permission::firstOrCreate(['name' => 'import-suppliers']);

        // Assign permission to role
        if (!$supplierRole->hasPermissionTo('import-suppliers')) {
            $supplierRole->givePermissionTo('import-suppliers');
        }

        // Create test user
        $testUser = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password123'),
            ]
        );

        // Assign role to user
        if (!$testUser->hasRole('supplier-manager')) {
            $testUser->assignRole('supplier-manager');
        }

        $this->command->info('Test user created:');
        $this->command->info('Name: Test User');
        $this->command->info('Email: test@example.com');
        $this->command->info('Password: password123');
        $this->command->info('Role: supplier-manager (can import suppliers only)');
    }
}
