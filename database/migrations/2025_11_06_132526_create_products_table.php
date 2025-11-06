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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            
            // Headers from config/imports.php - products_file
            $table->string('sku')->unique();
            $table->string('product_name');
            $table->string('category');
            $table->decimal('weight', 8, 2); // Weight in kg
            $table->string('dimensions')->nullable(); // LxWxH format
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['sku']);
            $table->index(['category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
