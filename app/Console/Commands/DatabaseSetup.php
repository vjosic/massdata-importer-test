<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use PDO;
use Exception;

class DatabaseSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:setup 
                            {--force : Force setup without confirmation}
                            {--skip-db : Skip database creation}
                            {--skip-migrate : Skip migrations}
                            {--skip-seed : Skip seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Complete database setup: create database, run migrations, seed permissions and admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info(' Starting Mass Data Importer Database Setup...');
        $this->line('');

        // Get database configuration
        $dbName = config('database.connections.mysql.database');
        $dbHost = config('database.connections.mysql.host');
        $dbUsername = config('database.connections.mysql.username');

        $this->info("Database: {$dbName}");
        $this->info("Host: {$dbHost}");
        $this->info("Username: {$dbUsername}");
        $this->line('');

        // Confirm setup unless --force is used
        if (!$this->option('force')) {
            if (!$this->confirm('Do you want to proceed with database setup?')) {
                $this->info('Setup cancelled.');
                return 0;
            }
        }

        try {
            // Step 1: Create database
            if (!$this->option('skip-db')) {
                $this->createDatabase($dbName, $dbHost, $dbUsername);
            } else {
                $this->warn('  Skipping database creation');
            }

            // Step 2: Run migrations
            if (!$this->option('skip-migrate')) {
                $this->runMigrations();
            } else {
                $this->warn('  Skipping migrations');
            }

            // Step 3: Seed database (permissions, roles, and users)
            if (!$this->option('skip-seed')) {
                $this->seedDatabase();
            } else {
                $this->warn('  Skipping database seeding');
            }

            $this->line('');
            $this->info(' Database setup completed successfully!');
            $this->line('');
            $this->info(' Available User Accounts:');
            $this->line('');
            $this->info('  Administrator (Full Access):');
            $this->info('   Email: admin@example.com');
            $this->info('   Username: admin');
            $this->info('   Password: password123');
            $this->info('   Role: Admin (all permissions)');
            $this->line('');
            $this->info('  Test User (Editor - All Imports):');
            $this->info('   Username: test');            
            $this->info('   Email: test@example.com');
            $this->info('   Password: password123');
            $this->info('   Role: Editor (import all data types + view/export)');
            $this->line('');
            $this->info('   Supplier Manager (Limited Access):');
            $this->info('   Email: supplier@example.com');
            $this->info('   Username: supplier');
            $this->info('   Password: password123');
            $this->info('   Role: Supplier Manager (suppliers only)');

            return 0;

        } catch (Exception $e) {
            $this->error('Setup failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Create database if it doesn't exist
     */
    private function createDatabase($dbName, $dbHost, $dbUsername)
    {
        $this->info('Creating database...');
        
        try {
            // Connect to MySQL without selecting database
            $password = config('database.connections.mysql.password');
            
            if (!$password) {
                $password = $this->secret('Please enter MySQL password:');
            }

            $pdo = new PDO(
                "mysql:host={$dbHost}",
                $dbUsername,
                $password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Create database if it doesn't exist
            $stmt = $pdo->prepare("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $stmt->execute();

            $this->info("Database '{$dbName}' created successfully (or already exists)");

        } catch (Exception $e) {
            throw new Exception("Failed to create database: " . $e->getMessage());
        }
    }

    /**
     * Run database migrations
     */
    private function runMigrations()
    {
        $this->info('Running migrations...');
        
        $exitCode = Artisan::call('migrate', ['--force' => true]);
        
        if ($exitCode === 0) {
            $this->info('Migrations completed successfully');
            
            // Show migration output if verbose
            if ($this->getOutput()->isVerbose()) {
                $this->line(Artisan::output());
            }
        } else {
            throw new Exception('Migration failed');
        }
    }

    /**
     * Seed database with permissions, roles, and users
     */
    private function seedDatabase()
    {
        $this->info('Seeding database (permissions, roles, and users)...');
        
        $exitCode = Artisan::call('db:seed', ['--force' => true]);
        
        if ($exitCode === 0) {
            $this->info('Database seeded successfully');
            
            // Show seeder output if verbose
            if ($this->getOutput()->isVerbose()) {
                $this->line(Artisan::output());
            }
        } else {
            throw new Exception('Database seeding failed');
        }
    }
}
