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
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            
            // Headers from config/imports.php - stock_levels_file
            $table->string('sku')->index();
            $table->string('warehouse_location');
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->integer('reorder_point')->default(0);
            $table->date('last_counted_date')->nullable();
            
            $table->timestamps();
            
            // Unique constraint based on update_or_create config
            $table->unique(['sku', 'warehouse_location']);
            
            // Foreign key to products table
            $table->foreign('sku')->references('sku')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_levels');
    }
};
