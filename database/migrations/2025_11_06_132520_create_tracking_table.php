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
        Schema::create('tracking', function (Blueprint $table) {
            $table->id();
            
            // Headers from config/imports.php - tracking_file
            $table->string('so_num')->index();
            $table->string('tracking_number');
            $table->enum('carrier', ['UPS', 'FedEx', 'DHL', 'USPS']);
            $table->date('shipped_date');
            $table->date('estimated_delivery')->nullable();
            
            $table->timestamps();
            
            // Unique constraint based on update_or_create config
            $table->unique(['so_num', 'tracking_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking');
    }
};
