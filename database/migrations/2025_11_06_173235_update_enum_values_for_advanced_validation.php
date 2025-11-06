<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update orders table channel enum
        DB::statement("ALTER TABLE orders MODIFY COLUMN channel ENUM('PT', 'Amazon', 'eBay') NOT NULL");
        
        // Update tracking table carrier enum  
        DB::statement("ALTER TABLE tracking MODIFY COLUMN carrier ENUM('UPS', 'FedEx', 'DHL', 'USPS', 'Posta') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE orders MODIFY COLUMN channel ENUM('PT', 'Amazon') NOT NULL");
        DB::statement("ALTER TABLE tracking MODIFY COLUMN carrier ENUM('UPS', 'FedEx', 'DHL', 'USPS') NOT NULL");
    }
};
