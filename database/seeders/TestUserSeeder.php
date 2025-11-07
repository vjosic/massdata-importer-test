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
        // Create editor role if it doesn't exist (will be created by PermissionSeeder)
        $editorRole = Role::firstOrCreate(['name' => 'editor']);
        
        // Create supplier manager role if needed  
        $supplierRole = Role::firstOrCreate(['name' => 'supplier-manager']);

        // Create test user with editor role (more permissions)
        $testUser = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'test',
                'password' => Hash::make('password123'),
            ]
        );

        // Assign editor role to test user (can import all data types)
        if (!$testUser->hasRole('editor')) {
            $testUser->assignRole('editor');
        }

        // Create supplier-specific user
        $supplierUser = User::firstOrCreate(
            ['email' => 'supplier@example.com'],
            [
                'name' => 'supplier',
                'password' => Hash::make('password123'),
            ]
        );

        // Assign supplier-manager role to supplier user
        if (!$supplierUser->hasRole('supplier-manager')) {
            $supplierUser->assignRole('supplier-manager');
        }

        $this->command->info('Test users created successfully!');
        $this->command->line('');
        $this->command->info('   Test User (Editor):');
        $this->command->info('   Email: test@example.com');
        $this->command->info('   Username: test');
        $this->command->info('   Password: password123');
        $this->command->info('   Role: Editor (can import all data types)');
        $this->command->line('');
        $this->command->info('   Supplier User (Supplier Manager):');
        $this->command->info('   Email: supplier@example.com');
        $this->command->info('   Username: supplier');
        $this->command->info('   Password: password123');
        $this->command->info('   Role: Supplier Manager (suppliers only)');
    }
}
