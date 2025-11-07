<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Import;
use App\Models\ImportError;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:cleanup 
                            {--days=30 : Number of days to keep imports} 
                            {--status=completed : Only cleanup imports with this status}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old import records and associated files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $status = $this->option('status');
        $dryRun = $this->option('dry-run');

        $cutoffDate = now()->subDays($days);

        $this->info("Import Cleanup - Looking for imports older than {$days} days ({$cutoffDate->format('Y-m-d H:i:s')})");
        
        if ($status) {
            $this->info("Filtering by status: {$status}");
        }

        // Find imports to cleanup
        $query = Import::where('created_at', '<', $cutoffDate);
        
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $imports = $query->get();

        if ($imports->isEmpty()) {
            $this->info('No imports found matching cleanup criteria.');
            return 0;
        }

        $this->info("Found {$imports->count()} imports to cleanup:");

        $totalErrors = 0;
        $totalAudits = 0;

        foreach ($imports as $import) {
            $errorCount = ImportError::where('import_id', $import->id)->count();
            $auditCount = DB::table('audits')->where('import_id', $import->id)->count();
            
            $totalErrors += $errorCount;
            $totalAudits += $auditCount;

            $this->line("  - Import #{$import->id} ({$import->type}) - {$import->status} - {$errorCount} errors, {$auditCount} audits");
        }

        $this->info("Total: {$totalErrors} import errors, {$totalAudits} audit records");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No data will be deleted');
            return 0;
        }

        if (!$this->confirm('Do you want to proceed with cleanup?')) {
            $this->info('Cleanup cancelled.');
            return 0;
        }

        $deletedImports = 0;
        $deletedErrors = 0;
        $deletedAudits = 0;

        foreach ($imports as $import) {
            // Delete import errors
            $errorCount = ImportError::where('import_id', $import->id)->count();
            ImportError::where('import_id', $import->id)->delete();
            $deletedErrors += $errorCount;

            // Delete audit records
            $auditCount = DB::table('audits')->where('import_id', $import->id)->count();
            DB::table('audits')->where('import_id', $import->id)->delete();
            $deletedAudits += $auditCount;

            // Delete import files if they exist
            if ($import->file_paths) {
                $filePaths = json_decode($import->file_paths, true);
                foreach ($filePaths as $filePath) {
                    if (Storage::disk('public')->exists($filePath)) {
                        Storage::disk('public')->delete($filePath);
                        $this->line("  Deleted file: {$filePath}");
                    }
                }
            }

            // Delete the import record
            $import->delete();
            $deletedImports++;

            $this->line("  Cleaned up import #{$import->id}");
        }

        $this->info("Cleanup completed successfully:");
        $this->info("  - {$deletedImports} imports deleted");
        $this->info("  - {$deletedErrors} import errors deleted");
        $this->info("  - {$deletedAudits} audit records deleted");

        return 0;
    }
}
