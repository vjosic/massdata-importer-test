<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestImportErrorNotificationLocal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:import-error-local 
                            {--user-id=1 : User ID to send notification to}
                            {--import-id=999 : Import ID to use for testing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test import error notification using log mail driver (for local testing)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user-id');
        $importId = $this->option('import-id');

        $this->info(" Testing import error notification with LOG mail driver...");
        $this->line("");

        // Temporarily set mail driver to log
        config(['mail.default' => 'log']);
        
        $user = \App\Models\User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found!");
            return 1;
        }

        $this->info(" User: {$user->name} ({$user->email})");

        // Check if import exists, or create a mock one
        $import = \App\Models\Import::find($importId);
        if (!$import) {
            $this->warn(" Import with ID {$importId} not found. Creating a mock import record...");
            
            $import = new \App\Models\Import();
            $import->id = $importId;
            $import->user_id = $userId;
            $import->import_type = 'orders';
            $import->status = 'failed';
            $import->created_at = now();
            $import->updated_at = now();
        }

        $this->info(" Import record: ID {$import->id}, Type: {$import->import_type}");

        $errorMessage = "TEST ERROR: Import validation failed - missing required headers 'sku', 'order_date'. Invalid file format detected.";

        $this->info(" Firing ImportErrorOccurred event...");
        
        try {
            event(new \App\Events\ImportErrorOccurred($import, $errorMessage, $userId));
            
            $this->line("");
            $this->info(" Event fired successfully!");
            $this->line("");
            $this->info(" Check the email content in Laravel log:");
            $this->line("tail -f storage/logs/laravel.log | grep -A 20 'Import Error Notification'");
            $this->line("");
            $this->info(" Or check the latest log entries:");
            $this->line("tail -20 storage/logs/laravel.log");
            
        } catch (\Exception $e) {
            $this->error(" Error firing event: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
