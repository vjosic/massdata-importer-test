<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            // Add file_names column for multiple file support
            $table->json('file_names')->nullable()->after('file_key');
            
            // Add additional columns needed by ImportJob
            $table->integer('error_count')->default(0)->after('skipped_rows');
            $table->timestamp('processed_at')->nullable()->after('finished_at');
            $table->text('error_message')->nullable()->after('processed_at');
            
            // Update status enum to match ImportJob
            $table->dropColumn('status');
        });
        
        // Add new status column with updated enum
        Schema::table('imports', function (Blueprint $table) {
            $table->enum('status', ['pending', 'processing', 'completed', 'completed_with_errors', 'failed'])
                  ->default('pending')->after('original_filename');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            $table->dropColumn(['file_names', 'error_count', 'processed_at', 'error_message']);
            
            // Restore original status enum
            $table->dropColumn('status');
        });
        
        Schema::table('imports', function (Blueprint $table) {
            $table->enum('status', ['pending','processing','success','failed'])
                  ->after('original_filename');
        });
    }
};
